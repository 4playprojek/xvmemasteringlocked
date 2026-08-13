# XVME APPS - Audio Mastering (Desktop / Standalone)

Versi desktop dari web app **XVME APPS Audio Mastering**. Kode aplikasi asli
(`app/index.html`) **tidak diubah sama sekali** — hanya dibungkus dengan
[Electron](https://www.electronjs.org/) agar bisa berjalan sebagai aplikasi
Windows standalone (`.exe` installer), tanpa perlu browser atau server.

## Struktur project

```
xvme-mastering-desktop/
├── app/
│   └── index.html          <- kode asli kamu, TIDAK diubah
├── build/
│   └── icon.ico             <- ikon aplikasi
├── main.js                  <- proses utama Electron (cuma membuka index.html)
├── package.json              <- konfigurasi electron-builder (NSIS installer)
└── .github/workflows/
    └── build-windows.yml     <- GitHub Actions: build .exe otomatis
```

## Cara pakai (paling gampang) — build otomatis lewat GitHub

1. Buat repository baru di GitHub, lalu upload/push semua isi folder ini.
2. Buat tag versi dan push, GitHub Actions akan otomatis build:

   ```bash
   git init
   git add .
   git commit -m "Initial commit: XVME Mastering desktop"
   git branch -M main
   git remote add origin https://github.com/USERNAME/NAMA-REPO.git
   git push -u origin main

   git tag v1.0.0
   git push origin v1.0.0
   ```

3. Buka tab **Actions** di repo GitHub kamu → workflow "Build Windows
   Installer" akan berjalan otomatis di runner Windows (butuh beberapa
   menit).
4. Setelah selesai, installer otomatis muncul di halaman **Releases**
   repo kamu (`XVME-Mastering-Setup-1.0.0.exe`) — tinggal dibagikan link
   downloadnya atau diunduh langsung dari sana.

   Tanpa membuat tag pun kamu bisa memicu build manual: buka tab
   **Actions → Build Windows Installer → Run workflow**, hasil build bisa
   diunduh sebagai artifact di halaman run tersebut.

## Cara build manual di PC Windows sendiri (opsional)

Kalau ingin build langsung di laptop Windows kamu (tanpa GitHub Actions):

```bash
npm install
npm run dist:win
```

Hasil installer akan ada di folder `dist/`.

## Menjalankan dalam mode development (opsional, cek dulu sebelum build)

```bash
npm install
npm start
```

## Catatan

- Tidak ada satu baris pun logika/fungsi audio mastering yang diubah —
  file `app/index.html` identik dengan file PHP/HTML yang kamu upload,
  hanya berganti nama ekstensi jadi `.html` karena isinya memang HTML/JS
  murni (tidak ada kode PHP di dalamnya).
- Semua library eksternal (Bootstrap Icons, Google Fonts, lamejs) tetap
  dimuat dari CDN seperti aslinya — pastikan komputer yang menjalankan
  aplikasi terhubung ke internet saat pertama kali dibuka (browser engine
  Electron butuh akses CDN yang sama seperti versi web-nya).
- Installer dibuat dengan NSIS (lewat electron-builder), mendukung
  shortcut Desktop & Start Menu, serta pilihan folder instalasi.

## Dukungan Windows 7

Electron versi 23 ke atas **sudah tidak mendukung Windows 7/8/8.1**
(Chromium 110 yang dipakai Electron 23 menghentikan dukungan OS
tersebut). Karena itu project ini dikunci ke **Electron 22.3.27** — versi
resmi terakhir yang masih berjalan penuh di Windows 7, 8, dan 8.1, selain
tentu saja Windows 10/11.

Build juga menghasilkan dua installer sekaligus:
- `XVME-Mastering-Setup-1.0.0-x64.exe` — untuk Windows 7/8/8.1/10/11 64-bit
- `XVME-Mastering-Setup-1.0.0-ia32.exe` — untuk Windows 7/8/8.1 32-bit (masih cukup banyak dipakai di PC lama)

Konsekuensi mengunci ke Electron 22: tidak akan menerima update
keamanan Chromium terbaru lagi (Electron sendiri sudah menghentikan
dukungannya sejak Oktober 2023), tapi ini satu-satunya cara agar
aplikasi tetap bisa dibuka di Windows 7. Kalau suatu saat kamu tidak
lagi butuh dukungan Windows 7, cukup ubah versi `"electron"` di
`package.json` ke versi terbaru untuk mendapat update keamanan lagi.

## Sistem Lisensi (node-locked, dikontrol dari database hosting)

Aplikasi sekarang meminta **username + license key** setiap kali dibuka
di komputer baru, dan mengunci lisensi itu ke komputer tersebut (via
hardware ID). Komputer lain tidak bisa memakai license key yang sama
sampai dilepas.

### 1. Upload backend ke hosting

Upload seluruh isi folder `server/` ke hosting (cPanel / shared hosting
apa saja yang mendukung PHP + MySQL), misalnya ke
`https://domainkamu.com/xvme-license/`.

### 2. Buat database

1. Di cPanel → **MySQL Databases**, buat database baru + user + kasih
   semua privilege ke database itu.
2. Buka **phpMyAdmin**, pilih database itu, import file `server/schema.sql`
   (tab Import).

### 3. Atur `server/config.php`

Isi `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` sesuai kredensial dari
langkah 2.

**Ganti password admin!** Password default saat ini `admin123` — segera
ganti. Cara generate hash baru (jalankan lewat terminal hosting yang ada
PHP, atau situs generator bcrypt manapun):
```
php -r "echo password_hash('password_baru_kamu', PASSWORD_DEFAULT);"
```
Tempel hasilnya ke `ADMIN_PASSWORD_HASH` di `config.php`.

### 4. Buka panel admin

Akses `https://domainkamu.com/xvme-license/admin/login.php`, login pakai
`admin` + password yang sudah kamu atur.

Dari situ kamu bisa:
- **Daftarkan user baru** → license key (format `XXXX-XXXX-XXXX-XXXX`)
  otomatis digenerate begitu username disubmit.
- Lihat status tiap lisensi: aktif/revoked, dan sedang terkunci di
  komputer mana (kalau sudah dipakai).
- **"Lepas Paksa"** → melepas kunci lisensi dari komputer yang
  memakainya (misalnya komputer lama hilang/rusak), supaya bisa dipakai
  di komputer lain.
- **Nonaktifkan/Aktifkan** lisensi kapan saja tanpa menghapus datanya.

### 5. Sambungkan aplikasi desktop ke server

Buka `main.js`, ganti baris ini dengan URL hosting kamu:
```js
const LICENSE_API_BASE = 'https://GANTI-DOMAIN-KAMU.com/xvme-license/api';
```
Lalu build ulang (`git push` tag baru → GitHub Actions otomatis build
ulang exe dengan URL server yang benar).

### Alur pemakaian untuk end-user

1. Buka aplikasi → muncul form **Aktivasi Lisensi** (username + license key).
2. Kalau valid & belum dipakai komputer lain → aplikasi terkunci ke
   komputer ini, langsung masuk ke aplikasi mastering.
3. Kalau license key itu sudah aktif di komputer lain → ditolak, dengan
   pesan bahwa lisensi harus dilepas dulu dari komputer lama.
4. **Pindah komputer**: di komputer LAMA, buka lagi aplikasinya → klik
   tombol **"Lepas lisensi dari komputer ini"**. Setelah itu, baru bisa
   diaktifkan di komputer baru. (Kalau komputer lama sudah tidak bisa
   diakses, admin bisa pakai tombol **"Lepas Paksa"** di panel admin.)
5. Setelah aktif, aplikasi otomatis login sendiri di kunjungan
   berikutnya (selama masih online untuk verifikasi ke server) — tidak
   perlu masukin ulang username/key tiap buka app.

### Catatan jujur soal keamanan

Ini sistem lisensi standar (node-locked, umum dipakai software
komersial kecil-menengah) — cukup untuk mencegah pemakaian sembarangan
dan berbagi key secara kasual. Tapi karena app-nya berjalan di komputer
end-user (client-side), seseorang yang punya kemampuan reverse-engineering
tinggi secara teknis bisa memodifikasi file aplikasi untuk melewati
pengecekan ini — sama seperti kebanyakan software desktop berbasis
lisensi lain (bukan cuma app ini). Kalau butuh proteksi yang jauh lebih
kuat (obfuscation berlapis, server-side rendering sebagian logika, dll),
itu di luar cakupan setup dasar ini dan bisa didiskusikan terpisah.

Juga: aplikasi butuh koneksi internet untuk aktivasi & verifikasi ke
server — pastikan `LICENSE_API_BASE` bisa diakses dari komputer
end-user (HTTPS aktif di hosting kamu).
