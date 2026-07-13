# Sprint 3 Walkthrough — UX & Notification Integration

This document outlines the detailed changes, architecture, and verification of Sprint 3.

## 🌟 Sprint 3 Highlights
Sprint 3 successfully modernizes Mikhmon v3's user experience (with a premium dark mode theme, typography updates, and CDN integrations) and delivers a robust, real-time operations alerting engine (WhatsApp/Telegram notification system) without altering Mikhmon's core lightweight, zero-dependency PHP architecture.

---

## 🛠️ Key Deliverables

### 1. Modernized UI & Theme Engine
*   **CSS Variables & Theme Layer:** Created `css/mikhmon-theme.css` to introduce central CSS variables and seamless styling overrides for **Dark Mode** via `body[data-theme="dark"]`.
*   **Dark Mode Toggle:** Integrated a theme switcher button in the navbar (`include/menu.php`) with state persistence in browser `localStorage` to avoid flash-of-light-theme on load.
*   **Typography:** Bundled the sleek **Inter** font locally (`css/fonts/inter/`) for high-legibility interface copy.
*   **Font Awesome 6:** Upgraded from version 4.x to **Font Awesome 6 Free** with backward compatibility shims (`v4-shims.min.css`) to map existing layout icons automatically.
*   **Highcharts CDN:** Moved the resource-heavy Highcharts libraries from local storage to high-speed CDNs, updating Content-Security-Policy (CSP) headers accordingly.

### 2. Notification Dispatcher (`lib/notification.php`)
*   Provides an abstract API layer to dispatch critical operational notifications.
*   **State & Cooldown Tracking:** Uses file-based JSON persistence (`logs/router_states.json` and `logs/notif_cooldown.json`) to track transition changes and prevent notification floods (defaulting to a 5-minute cooldown for system alerts).
*   **Operational Event Hooks:**
    *   **NOC Performance Alerts:** Hooked into `dashboard/noc_fetch.php` to notify administrators when a router drops offline, recovers online, or exceeds CPU/Memory usage thresholds.
    *   **Hotspot User Alerts:** Hooked into `hotspot/adduser.php` to send instant notifications upon single user voucher creations.
    *   **Hotspot Batch Vouchers:** Hooked into `hotspot/generateuser.php` to push a single summary alert for batch voucher generation runs (preventing notification spam).

### 3. WhatsApp (Baileys) Gateway Microservice (`wa-gateway/`)
*   A decoupled **Node.js** microservice powered by `@whiskeysockets/baileys` to manage multi-device WhatsApp sessions.
*   Exposes secure REST API endpoints `/status` and `/send`, authenticated via a configurable `API_KEY`.
*   Connects locally via loopback interface (`127.0.0.1:3001`), keeping execution isolated and independent of the PHP core.

### 4. Interactive Configuration UI (`settings/notif_settings.php`)
*   A dedicated Settings dashboard for managing global alert thresholds and enabling/disabling alerting routes.
*   Features inline testing buttons ("Test Telegram" and "Test WhatsApp") with instant callback status responses.
*   Monitors local gateway status and displays real-time connection state.

---

## 📸 Interface Verification

The newly designed **Notifications** panel matches Mikhmon's visual guidelines and implements dynamic states beautifully:

![Notifications Settings Saved](file:///C:/Users/yppnu/.gemini/antigravity/brain/73ad805b-30f9-47fe-9130-08f8144d6b9a/notifications_settings_saved_1783827270323.png)

---

## 🚀 Running the Services

### Start the WhatsApp Gateway (Node.js)
Navigate to the gateway directory and run the service:
```bash
cd wa-gateway
npm install
node index.js
```
*(For production, it is recommended to run the gateway using PM2: `pm2 start index.js --name "mikhmon-wa-gateway"`)*

### Setup Verification
1. Access Mikhmon and navigate to the **Notifications** menu.
2. The **Gateway Status** panel should show `OFFLINE` (service connecting).
3. If the Node.js console prints a QR code, scan it using WhatsApp Link Device.
4. Once paired, the Status panel will change to `ONLINE` with the connected phone number.
5. Click **Test WhatsApp** to receive a verification ping.
