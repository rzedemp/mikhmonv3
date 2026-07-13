const { 
  default: makeWASocket, 
  DisconnectReason, 
  useMultiFileAuthState,
  fetchLatestBaileysVersion
} = require('@whiskeysockets/baileys');
const express = require('express');
const qrcode = require('qrcode-terminal');
const pino = require('pino');
const path = require('path');
const fs = require('fs');
require('dotenv').config();

const app = express();
app.use(express.json());

const PORT = process.env.PORT || 3001;
const API_KEY = process.env.API_KEY || 'mikhmon-secret-key-2026';

// Anti-ban configuration variables
const MAX_MSGS_PER_DAY = parseInt(process.env.MAX_MSGS_PER_DAY || 50);
const MAX_MSGS_PER_HOUR = parseInt(process.env.MAX_MSGS_PER_HOUR || 10);
const SEND_MIN_DELAY_MS = parseInt(process.env.SEND_MIN_DELAY_MS || 3000);
const SEND_MAX_DELAY_MS = parseInt(process.env.SEND_MAX_DELAY_MS || 8000);
const WARMUP_MODE = process.env.WARMUP_MODE === 'true';
const WARMUP_DAYS_ELAPSED = parseInt(process.env.WARMUP_DAYS_ELAPSED || 0);

let sock = null;
let connectionState = 'disconnected'; // 'disconnected', 'connecting', 'connected', 'qrcode'
let qrCodeText = '';

// Queue state
const queue = [];
let isProcessingQueue = false;
let consecutiveFailures = 0;
let isPaused = false;
let pauseTimeout = null;

// Cache for checked numbers (JID -> boolean) to limit onWhatsApp calls
const checkedNumbersCache = new Map();

// Counter persistence
const counterFile = path.join(__dirname, 'data', 'send_counter.json');

function loadCounter() {
  try {
    if (fs.existsSync(counterFile)) {
      const data = JSON.parse(fs.readFileSync(counterFile, 'utf8'));
      const today = new Date().toISOString().split('T')[0];
      const currentHour = new Date().toISOString().substring(0, 13);
      
      let changed = false;
      if (data.date !== today) {
        data.date = today;
        data.dailyCount = 0;
        changed = true;
      }
      if (!data.hourlyCounts) {
        data.hourlyCounts = {};
        changed = true;
      }
      for (const hr in data.hourlyCounts) {
        if (!hr.startsWith(today)) {
          delete data.hourlyCounts[hr];
          changed = true;
        }
      }
      if (!data.hourlyCounts[currentHour]) {
        data.hourlyCounts[currentHour] = 0;
        changed = true;
      }
      if (changed) {
        saveCounter(data);
      }
      return data;
    }
  } catch (e) {
    console.error("Failed to load counter file:", e);
  }
  
  const today = new Date().toISOString().split('T')[0];
  const currentHour = new Date().toISOString().substring(0, 13);
  return {
    date: today,
    dailyCount: 0,
    hourlyCounts: {
      [currentHour]: 0
    }
  };
}

function saveCounter(data) {
  try {
    const dir = path.dirname(counterFile);
    if (!fs.existsSync(dir)) {
      fs.mkdirSync(dir, { recursive: true });
    }
    fs.writeFileSync(counterFile, JSON.stringify(data, null, 2), 'utf8');
  } catch (e) {
    console.error("Failed to save counter file:", e);
  }
}

function incrementCounter() {
  const data = loadCounter();
  const currentHour = new Date().toISOString().substring(0, 13);
  data.dailyCount++;
  data.hourlyCounts[currentHour] = (data.hourlyCounts[currentHour] || 0) + 1;
  saveCounter(data);
}

function getLimits() {
  if (WARMUP_MODE) {
    const days = WARMUP_DAYS_ELAPSED;
    if (days >= 0 && days <= 3) {
      return { day: 10, hour: 2 };
    } else if (days >= 4 && days <= 7) {
      return { day: 30, hour: 5 };
    } else if (days >= 8 && days <= 14) {
      return { day: 80, hour: 15 };
    }
  }
  return { day: MAX_MSGS_PER_DAY, hour: MAX_MSGS_PER_HOUR };
}

function isLimitExceeded() {
  if (isPaused) {
    return { type: 'paused', limit: 0, count: 0 };
  }
  const limits = getLimits();
  const data = loadCounter();
  const currentHour = new Date().toISOString().substring(0, 13);
  const hourlyCount = data.hourlyCounts[currentHour] || 0;
  
  if (data.dailyCount >= limits.day) {
    return { type: 'daily', limit: limits.day, count: data.dailyCount };
  }
  if (hourlyCount >= limits.hour) {
    return { type: 'hourly', limit: limits.hour, count: hourlyCount };
  }
  return null;
}

// Check WhatsApp number availability
async function isWhatsAppNumber(jid, phone) {
  if (checkedNumbersCache.has(phone)) {
    return checkedNumbersCache.get(phone);
  }
  try {
    const [result] = await sock.onWhatsApp(jid);
    const exists = !!(result && result.exists);
    checkedNumbersCache.set(phone, exists);
    // Cache for 24 hours
    setTimeout(() => checkedNumbersCache.delete(phone), 24 * 60 * 60 * 1000);
    return exists;
  } catch (err) {
    console.error(`Error checking JID ${jid}:`, err);
    // In case of error (rate limit, etc.), assume true to be safe
    return true;
  }
}

// Queue processor helper
function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

async function processQueue() {
  if (isProcessingQueue) return;
  if (queue.length === 0) return;
  
  isProcessingQueue = true;
  console.log(`Queue process started. Length: ${queue.length}`);
  
  try {
    while (queue.length > 0) {
      if (connectionState !== 'connected') {
        console.log("WhatsApp disconnected. Pausing queue processing.");
        break;
      }
      
      const limitError = isLimitExceeded();
      if (limitError) {
        console.warn(`Queue paused: rate limits exceeded (${limitError.type}).`);
        break;
      }
      
      const item = queue.shift();
      const { jid, message, phone } = item;
      
      // Calculate delay
      const delay = SEND_MIN_DELAY_MS + Math.random() * (SEND_MAX_DELAY_MS - SEND_MIN_DELAY_MS);
      console.log(`Waiting ${Math.round(delay)}ms before sending to ${phone}...`);
      await sleep(delay);
      
      try {
        const sent = await sock.sendMessage(jid, { text: message });
        incrementCounter();
        consecutiveFailures = 0;
        console.log(`Successfully sent message to ${phone}. ID: ${sent.key.id}`);
      } catch (sendErr) {
        console.error(`Failed to send message to ${phone}:`, sendErr);
        consecutiveFailures++;
        
        // Put back in queue to retry later if not permanent error
        queue.unshift(item);
        
        if (consecutiveFailures >= 5) {
          console.warn("5 consecutive failures detected. Pausing gateway for 30 minutes.");
          isPaused = true;
          consecutiveFailures = 0;
          
          if (pauseTimeout) clearTimeout(pauseTimeout);
          pauseTimeout = setTimeout(() => {
            isPaused = false;
            console.log("Auto-pause lifted. Resuming queue processing.");
            processQueue();
          }, 30 * 60 * 1000);
          break;
        }
        
        // Wait a bit longer after a failure
        await sleep(5000);
      }
    }
  } catch (e) {
    console.error("Critical error in queue processing:", e);
  } finally {
    isProcessingQueue = false;
    console.log(`Queue process finished. Remaining: ${queue.length}`);
  }
}

// Auth token check middleware
app.use((req, res, next) => {
  const apiKey = req.headers['x-api-key'];
  if (apiKey !== API_KEY) {
    return res.status(401).json({ error: 'Unauthorized' });
  }
  next();
});

// Initialize Baileys WhatsApp connection
async function connectToWhatsApp() {
  const authDir = path.join(__dirname, 'auth');
  const { state, saveCreds } = await useMultiFileAuthState(authDir);

  // Fetch the latest version from WhatsApp servers dynamically
  let version = [2, 3000, 1035194821]; // fallback version
  try {
    const { version: latestVersion } = await fetchLatestBaileysVersion();
    if (latestVersion) {
      version = latestVersion;
      console.log(`Using fetched WhatsApp version: ${version.join('.')}`);
    }
  } catch (err) {
    console.error("Failed to fetch latest WhatsApp version, using fallback:", err);
  }

  sock = makeWASocket({
    auth: state,
    printQRInTerminal: false,
    version,
    logger: pino({ level: 'silent' })
  });

  sock.ev.on('creds.update', saveCreds);

  sock.ev.on('connection.update', (update) => {
    const { connection, lastDisconnect, qr } = update;

    if (qr) {
      qrCodeText = qr;
      connectionState = 'qrcode';
      console.log('Scan the QR code below to link your WhatsApp:');
      qrcode.generate(qr, { small: true });
    }

    if (connection === 'close') {
      connectionState = 'disconnected';
      const shouldReconnect = lastDisconnect?.error?.output?.statusCode !== DisconnectReason.loggedOut;
      console.log('Connection closed due to ', lastDisconnect?.error, ', reconnecting ', shouldReconnect);
      if (shouldReconnect) {
        setTimeout(connectToWhatsApp, 5000);
      } else {
        console.log('Logged out. Please delete the "auth" folder and scan the new QR code.');
        try {
          fs.rmSync(authDir, { recursive: true, force: true });
        } catch (e) {
          console.error('Failed to clear auth folder:', e);
        }
        setTimeout(connectToWhatsApp, 5000);
      }
    } else if (connection === 'open') {
      connectionState = 'connected';
      qrCodeText = '';
      console.log('WhatsApp connection opened successfully!');
      processQueue();
    } else if (connection === 'connecting') {
      connectionState = 'connecting';
      console.log('Connecting to WhatsApp...');
    }
  });
}

// REST Endpoints
app.get('/status', (req, res) => {
  const limits = getLimits();
  const counter = loadCounter();
  const currentHour = new Date().toISOString().substring(0, 13);
  res.json({
    status: connectionState,
    qr: qrCodeText,
    connected: connectionState === 'connected',
    user: sock?.user?.id ? sock.user.id.split(':')[0] : null,
    antiBan: {
      queueLength: queue.length,
      dailyCount: counter.dailyCount,
      dailyLimit: limits.day,
      hourlyCount: counter.hourlyCounts[currentHour] || 0,
      hourlyLimit: limits.hour,
      isPaused: isPaused,
      warmupMode: WARMUP_MODE,
      warmupDays: WARMUP_DAYS_ELAPSED
    }
  });
});

app.post('/send', async (req, res) => {
  const { phone, message } = req.body;
  if (!phone || !message) {
    return res.status(400).json({ error: 'Missing phone or message parameter' });
  }

  if (connectionState !== 'connected') {
    return res.status(503).json({ error: 'WhatsApp is not connected', state: connectionState });
  }

  // Check rate limit first
  const limitError = isLimitExceeded();
  if (limitError) {
    if (limitError.type === 'paused') {
      return res.status(503).json({ error: 'Gateway is temporarily paused due to consecutive failures' });
    }
    return res.status(429).json({ 
      error: 'Rate limit exceeded', 
      type: limitError.type, 
      limit: limitError.limit, 
      count: limitError.count 
    });
  }

  // Sanitize phone number and append WhatsApp suffix
  let cleanPhone = phone.replace(/[^0-9]/g, '');
  if (!cleanPhone.startsWith('62') && cleanPhone.startsWith('0')) {
    cleanPhone = '62' + cleanPhone.substring(1);
  }
  const jid = `${cleanPhone}@s.whatsapp.net`;

  // Validate JID registered on WhatsApp
  const isValid = await isWhatsAppNumber(jid, cleanPhone);
  if (!isValid) {
    return res.status(404).json({ error: 'Number not registered on WhatsApp' });
  }

  // Queue the message
  queue.push({ jid, message, phone: cleanPhone });
  
  // Trigger processor
  processQueue();

  res.json({ 
    status: 'queued', 
    to: cleanPhone, 
    queueLength: queue.length 
  });
});

// Start the Express server and WhatsApp connection
app.listen(PORT, '0.0.0.0', () => {
  console.log(`Baileys WA Gateway running on http://127.0.0.1:${PORT}`);
  connectToWhatsApp();
});
