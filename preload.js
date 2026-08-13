const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('licenseAPI', {
  activate: (username, licenseKey) => ipcRenderer.invoke('license:activate', { username, licenseKey }),
  release: (username, licenseKey) => ipcRenderer.invoke('license:release', { username, licenseKey }),
  getCached: () => ipcRenderer.invoke('license:get-cached'),
});
