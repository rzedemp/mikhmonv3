<?php
/*****************************
 *
 * RouterOS REST API class for Mikhmon
 * Works over HTTP/HTTPS JSON endpoint (/rest)
 * Compatible with RouterOS v7.x+
 *
 ******************************/

class RouterosRestAPI {
    public $debug = false;
    public $connected = false;
    public $port = 443;
    public $ssl = true;
    public $timeout = 5;
    
    private $ip;
    private $login;
    private $password;

    public function connect($ip, $login, $password) {
        $this->ip = $ip;
        $this->login = $login;
        $this->password = $password;
        
        // Test connection by checking system resource
        $res = $this->request('GET', '/rest/system/resource', [], 2);
        if ($res !== false) {
            $this->connected = true;
            return true;
        }
        return false;
    }

    public function disconnect() {
        $this->connected = false;
    }

    public function comm($com, $arr = array()) {
        $com = trim($com, '/');
        $parts = explode('/', $com);
        $action = end($parts);
        
        // Extract command action and path
        if (in_array($action, ['print', 'add', 'set', 'remove', 'enable', 'disable'])) {
            array_pop($parts);
            $basePath = '/rest/' . implode('/', $parts);
        } else {
            $basePath = '/rest/' . implode('/', $parts);
            $action = 'print'; // Default action is print (GET)
        }

        $method = 'GET';
        $params = [];

        // Parse arguments ($arr)
        if (is_array($arr)) {
            foreach ($arr as $k => $v) {
                $cleanKey = ltrim($k, '?=~. ');
                $params[$cleanKey] = $v;
            }
        }

        switch ($action) {
            case 'print':
                $method = 'GET';
                $queryString = '';
                if (!empty($params)) {
                    $queryParts = [];
                    foreach ($params as $k => $v) {
                        if ($k === 'count-only') continue;
                        $queryParts[] = urlencode($k) . '=' . urlencode($v);
                    }
                    if (!empty($queryParts)) {
                        $queryString = '?' . implode('&', $queryParts);
                    }
                }
                $response = $this->request($method, $basePath . $queryString);
                
                // Compatibility wrapping
                if (is_array($response)) {
                    if (isset($params['count-only'])) {
                        return count($response);
                    }
                    // Wrap single associative array response in a sequential array
                    if (!empty($response) && !isset($response[0])) {
                        $response = array($response);
                    }
                    return $response;
                }
                return array();

            case 'add':
                $method = 'POST';
                $response = $this->request($method, $basePath, $params);
                if (is_array($response) && !isset($response[0]) && !empty($response)) {
                    $response = array($response);
                }
                return is_array($response) ? $response : array();

            case 'set':
                $method = 'PATCH';
                $id = isset($params['.id']) ? $params['.id'] : (isset($params['numbers']) ? $params['numbers'] : null);
                if ($id !== null) {
                    unset($params['.id']);
                    unset($params['numbers']);
                    $response = $this->request($method, $basePath . '/' . urlencode($id), $params);
                } else {
                    $response = $this->request('POST', $basePath . '/set', $params);
                }
                if (is_array($response) && !isset($response[0]) && !empty($response)) {
                    $response = array($response);
                }
                return is_array($response) ? $response : array();

            case 'remove':
                $method = 'DELETE';
                $id = isset($params['.id']) ? $params['.id'] : (isset($params['numbers']) ? $params['numbers'] : null);
                if ($id !== null) {
                    $response = $this->request($method, $basePath . '/' . urlencode($id));
                } else {
                    $response = $this->request('POST', $basePath . '/remove', $params);
                }
                if (is_array($response) && !isset($response[0]) && !empty($response)) {
                    $response = array($response);
                }
                return is_array($response) ? $response : array();

            case 'enable':
            case 'disable':
                $response = $this->request('POST', $basePath . '/' . $action, $params);
                if (is_array($response) && !isset($response[0]) && !empty($response)) {
                    $response = array($response);
                }
                return is_array($response) ? $response : array();

            default:
                $response = $this->request('POST', '/rest/' . $com, $params);
                if (is_array($response) && !isset($response[0]) && !empty($response)) {
                    $response = array($response);
                }
                return is_array($response) ? $response : array();
        }
    }

    private function request($method, $path, $body = null, $customTimeout = null) {
        $protocol = $this->ssl ? 'https' : 'http';
        $url = $protocol . '://' . $this->ip . ':' . $this->port . $path;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, $customTimeout !== null ? $customTimeout : $this->timeout);
        
        // Trust self-signed certificates
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // HTTP Basic Auth
        curl_setopt($ch, CURLOPT_USERPWD, $this->login . ':' . $this->password);

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        if ($body !== null && ($method === 'POST' || $method === 'PATCH' || $method === 'PUT')) {
            $jsonBody = json_encode($body);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
            $headers[] = 'Content-Length: ' . strlen($jsonBody);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return false;
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            $decoded = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
            return $response;
        }
        
        return false;
    }
}
