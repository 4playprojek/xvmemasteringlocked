const { app, BrowserWindow, Menu, shell, ipcMain } = require('electron');
const path = require('path');
const fs = require('fs');
const https = require('https');
const http = require('http');
const os = require('os');
const { machineIdSync } = require('node-machine-id');

// ==========================================================
// GANTI INI dengan URL hosting tempat kamu upload folder server/
// Contoh: 'https://domainkamu.com/xvme-license/api'
// ==========================================================
const LICENSE_API_BASE = 'https://xvme-apps.com/server/api';

const LICENSE_CACHE_FILE = path.join(app.getPath('userData'), 'license-cache.json');

let licenseWindow;
let mainWindow;

function getHardwareId() {
  try {
    return machineIdSync(true); // true = raw, tetap stabil per komputer
  } catch (e) {
    return os.hostname() + '-' + os.platform() + '-' + os.arch();
  }
}

function readCache() {
  try {
    return JSON.parse(fs.readFileSync(LICENSE_CACHE_FILE, 'utf8'));
  } catch (e) {
    return null;
  }
}

function writeCache(data) {
  try {
    fs.writeFileSync(LICENSE_CACHE_FILE, JSON.stringify(data), 'utf8');
  } catch (e) {}
}

function clearCache() {
  try {
    fs.unlinkSync(LICENSE_CACHE_FILE);
  } catch (e) {}
}

function apiPost(endpoint, payload) {
  return new Promise((resolve) => {
    const url = new URL(`${LICENSE_API_BASE}/${endpoint}`);
    const lib = url.protocol === 'https:' ? https : http;
    const body = JSON.stringify(payload);

    const req = lib.request(
      {
        hostname: url.hostname,
        port: url.port || (url.protocol === 'https:' ? 443 : 80),
        path: url.pathname,
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Content-Length': Buffer.byteLength(body),
        },
        timeout: 15000,
      },
      (res) => {
        let raw = '';
        res.on('data', (chunk) => (raw += chunk));
        res.on('end', () => {
          try {
            resolve(JSON.parse(raw));
          } catch (e) {
            resolve({ ok: false, message: 'Respons server tidak valid.' });
          }
        });
      }
    );

    req.on('timeout', () => {
      req.destroy();
      resolve({ ok: false, message: 'Koneksi ke server lisensi timeout. Periksa internet kamu.' });
    });

    req.on('error', () => {
      resolve({ ok: false, message: 'Tidak bisa terhubung ke server lisensi. Periksa internet / URL server.' });
    });

    req.write(body);
    req.end();
  });
}

function createLicenseWindow() {
  licenseWindow = new BrowserWindow({
    width: 440,
    height: 560,
    resizable: false,
    backgroundColor: '#0d0c14',
    icon: path.join(__dirname, 'build', 'icon.ico'),
    autoHideMenuBar: true,
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      nodeIntegration: false,
      contextIsolation: true,
    },
  });
  licenseWindow.loadFile(path.join(__dirname, 'license.html'));
  Menu.setApplicationMenu(null);
}

function createMainWindow() {
  mainWindow = new BrowserWindow({
    width: 1440,
    height: 900,
    minWidth: 1000,
    minHeight: 650,
    backgroundColor: '#0d0c14',
    icon: path.join(__dirname, 'build', 'icon.ico'),
    autoHideMenuBar: true,
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true,
    },
  });

  mainWindow.loadFile(path.join(__dirname, 'app', 'index.html'));

  mainWindow.webContents.setWindowOpenHandler(({ url }) => {
    shell.openExternal(url);
    return { action: 'deny' };
  });

  Menu.setApplicationMenu(null);
}

async function tryAutoLoginWithCache() {
  const cached = readCache();
  if (!cached) return false;

  const hardwareId = getHardwareId();
  const result = await apiPost('validate.php', {
    username: cached.username,
    license_key: cached.licenseKey,
    hardware_id: hardwareId,
  });

  return !!result.ok;
}

ipcMain.handle('license:get-cached', () => {
  return readCache();
});

ipcMain.handle('license:activate', async (event, { username, licenseKey }) => {
  const hardwareId = getHardwareId();
  const result = await apiPost('activate.php', {
    username,
    license_key: licenseKey,
    hardware_id: hardwareId,
    computer_name: os.hostname(),
  });

  if (result.ok) {
    writeCache({ username, licenseKey });
    setTimeout(() => {
      createMainWindow();
      if (licenseWindow) {
        licenseWindow.close();
        licenseWindow = null;
      }
    }, 600);
  }

  return result;
});

ipcMain.handle('license:release', async (event, { username, licenseKey }) => {
  const hardwareId = getHardwareId();
  const result = await apiPost('release.php', {
    username,
    license_key: licenseKey,
    hardware_id: hardwareId,
  });
  if (result.ok) {
    clearCache();
  }
  return result;
});

app.whenReady().then(async () => {
  const validCache = await tryAutoLoginWithCache();
  if (validCache) {
    createMainWindow();
  } else {
    createLicenseWindow();
  }

  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) {
      createLicenseWindow();
    }
  });
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') app.quit();
});
