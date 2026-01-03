# 📱 Ojol Finance - Integrasi Telegram Notification

Aplikasi keuangan untuk driver ojek online dengan fitur notifikasi otomatis ke Telegram.

## ✨ Fitur Notifikasi Telegram

- 💵 **Notifikasi Pendapatan** - Otomatis saat input pendapatan baru
- 💸 **Notifikasi Pengeluaran** - Otomatis saat input pengeluaran
- 🧾 **Notifikasi Tagihan** - Saat menambah tagihan baru
- 🎯 **Notifikasi Target** - Saat target harian/mingguan/bulanan tercapai
- 📊 **Ringkasan Harian** - Summary lengkap performa hari ini
- ⚠️ **Pengingat Tagihan** - Alert untuk tagihan yang akan jatuh tempo

## 📋 Persyaratan Sistem

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Extension PHP: `curl`, `json`, `pdo_mysql`
- Web server (Apache/Nginx)
- Akses internet untuk Telegram API

## 🚀 Instalasi

### 1. Setup Database

```sql
-- Jalankan file ojol_finance.sql
mysql -u root -p < ojol_finance.sql
```

### 2. Upload File Aplikasi

Upload semua file ke server Anda:

```
/your-app-folder/
├── index.php                    (Updated dengan Telegram)
├── telegram_notifier.php        (Service notifikasi)
├── telegram_settings.php        (Halaman pengaturan)
├── load_env.php                 (Loader environment)
├── .env.example                 (Template konfigurasi)
├── config.php
├── charts.js
├── styles.css
└── ojol_finance.sql
```

### 3. Buat File .env

Copy `.env.example` menjadi `.env`:

```bash
cp .env.example .env
```

Edit file `.env` dan kosongkan dulu:

```env
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=

DB_HOST=localhost
DB_NAME=ojol_finance
DB_USER=root
DB_PASS=
```

### 4. Set Permission File

```bash
chmod 644 .env
chmod 755 telegram_notifier.php
chmod 755 telegram_settings.php
chmod 755 load_env.php
```

## 🤖 Setup Telegram Bot

### Langkah 1: Buat Bot di Telegram

1. Buka aplikasi Telegram
2. Cari dan chat dengan **@BotFather**
3. Kirim perintah: `/newbot`
4. Ikuti instruksi:
   - Berikan nama bot (contoh: "Ojol Finance Bot")
   - Berikan username bot (harus diakhiri 'bot', contoh: "ojolfinance_bot")
5. **SIMPAN Bot Token** yang diberikan BotFather
   - Format: `1234567890:ABCdefGHIjklMNOpqrsTUVwxyz`

### Langkah 2: Dapatkan Chat ID

#### Metode A: Via Browser (Mudah)

1. Buka bot Anda di Telegram
2. Kirim pesan apa saja (contoh: "test")
3. Buka browser dan akses URL:
   ```
   https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates
   ```
   Ganti `<YOUR_BOT_TOKEN>` dengan token dari BotFather
4. Cari di JSON response:
   ```json
   "chat": {
     "id": 1164425209,
     "type": "private"
   }
   ```
5. **SIMPAN angka ID** tersebut (contoh: 1164425209)

#### Metode B: Via Bot (Alternatif)

1. Cari bot **@userinfobot** di Telegram
2. Kirim `/start`
3. Bot akan reply dengan Chat ID Anda

### Langkah 3: Konfigurasi di Aplikasi

#### Opsi A: Via Web Interface (Recommended)

1. Buka aplikasi di browser
2. Klik icon 📱 di pojok kanan atas
3. Atau akses langsung: `http://your-domain.com/telegram_settings.php`
4. Masukkan **Bot Token** dan **Chat ID**
5. Klik **Simpan Pengaturan**
6. Klik **Test Koneksi** untuk verifikasi
7. Cek Telegram Anda, seharusnya ada pesan test

#### Opsi B: Manual Edit .env

Edit file `.env`:

```env
TELEGRAM_BOT_TOKEN=1234567890:ABCdefGHIjklMNOpqrsTUVwxyz
TELEGRAM_CHAT_ID=1164425209
```

## 🧪 Testing

### Test 1: Test Koneksi

1. Buka `telegram_settings.php`
2. Klik tombol **Test Koneksi**
3. Cek Telegram, seharusnya menerima pesan:

   ```
   🔔 TEST NOTIFIKASI

   Telegram bot berhasil terhubung!
   Waktu: 04/01/2026 14:30:00

   ✅ Notifikasi siap digunakan!
   ```

### Test 2: Input Pendapatan

1. Kembali ke halaman utama (`index.php`)
2. Input pendapatan baru
3. Cek Telegram, seharusnya menerima:

   ```
   💵 PENDAPATAN BARU

   📅 Tanggal: 04/01/2026
   💰 Nominal: Rp 150.000
   🏢 Platform: GrabBike
   📝 Catatan: Shift pagi
   ⏰ Jam Kerja: 08:00 - 12:00
   ⚡ Efisiensi: Rp 37.500/jam

   ✅ Input berhasil disimpan!
   ```

### Test 3: Input Pengeluaran

1. Input pengeluaran baru
2. Cek Telegram untuk notifikasi:

   ```
   💸 PENGELUARAN BARU

   📅 Tanggal: 04/01/2026
   💰 Nominal: Rp 25.000
   🏷️ Kategori: Bensin
   📝 Keterangan: Pertamax 3L

   ✅ Pengeluaran berhasil dicatat!
   ```

## 📱 Format Notifikasi

### Notifikasi Pendapatan

```
💵 PENDAPATAN BARU

📅 Tanggal: [tanggal]
💰 Nominal: Rp [nominal]
🏢 Platform: [platform]
📝 Catatan: [catatan]
⏰ Jam Kerja: [jam_mulai - jam_selesai]
⚡ Efisiensi: Rp [per_jam]/jam

✅ Input berhasil disimpan!
```

### Notifikasi Pengeluaran

```
💸 PENGELUARAN BARU

📅 Tanggal: [tanggal]
💰 Nominal: Rp [nominal]
🏷️ Kategori: [kategori]
📝 Keterangan: [keterangan]

✅ Pengeluaran berhasil dicatat!
```

### Notifikasi Target Tercapai

```
🎉 SELAMAT!

Target Harian tercapai!
💰 Rp [nominal]

Terus semangat dan tingkatkan lagi! 💪
```

### Notifikasi Tagihan

```
🧾 TAGIHAN BARU

📋 Nama: [nama_tagihan]
💰 Nominal: Rp [nominal]
🏷️ Kategori: [kategori]
📅 Jatuh Tempo: [tanggal]

⏰ Jangan lupa bayar tepat waktu!
```

## 🔧 Troubleshooting

### Masalah: Notifikasi tidak terkirim

**Solusi:**

1. **Cek file .env**

   ```bash
   cat .env
   ```

   Pastikan `TELEGRAM_BOT_TOKEN` dan `TELEGRAM_CHAT_ID` terisi

2. **Cek permission file**

   ```bash
   ls -la .env
   ```

   Harus readable (644)

3. **Test manual dengan curl**

   ```bash
   curl -X POST "https://api.telegram.org/bot<BOT_TOKEN>/sendMessage" \
   -d "chat_id=<CHAT_ID>" \
   -d "text=Test manual"
   ```

4. **Cek error log PHP**
   ```bash
   tail -f /var/log/apache2/error.log
   # atau
   tail -f /var/log/nginx/error.log
   ```

### Masalah: Bot Token invalid

**Error:** `"Unauthorized"` atau `"Bot token is invalid"`

**Solusi:**

- Pastikan copy Bot Token dengan benar (tidak ada spasi)
- Token format: `1234567890:ABCdefGHI...`
- Jika hilang, chat @BotFather dan gunakan `/token`

### Masalah: Chat ID tidak ditemukan

**Error:** `"Bad Request: chat not found"`

**Solusi:**

- Pastikan sudah kirim pesan ke bot minimal 1x
- Chat ID adalah angka (contoh: 1164425209)
- Gunakan Chat ID Anda sendiri, bukan orang lain

### Masalah: cURL error

**Error:** `"cURL Error: ..."`

**Solusi:**

1. Pastikan PHP cURL extension terinstall:

   ```bash
   php -m | grep curl
   ```

2. Install jika belum ada:

   ```bash
   # Ubuntu/Debian
   sudo apt-get install php-curl
   sudo systemctl restart apache2

   # CentOS/RHEL
   sudo yum install php-curl
   sudo systemctl restart httpd
   ```

## 🔐 Keamanan

### Environment Variables

- ✅ **JANGAN** commit file `.env` ke Git
- ✅ **JANGAN** share Bot Token ke siapapun
- ✅ **JANGAN** hardcode token di kode
- ✅ Gunakan `.env` untuk production
- ✅ Backup `.env` secara terpisah

### File .gitignore

Tambahkan ke `.gitignore`:

```
.env
.env.local
.env.*.local
```

### Izin File

```bash
# .env hanya readable oleh owner
chmod 600 .env

# PHP files executable
chmod 755 *.php
```

## 📊 Monitoring

### Cek Status Bot

Akses halaman `telegram_settings.php` untuk melihat:

- ✅ Status koneksi (Aktif/Tidak Aktif)
- 🤖 Informasi bot (username, ID)
- 👤 Chat ID yang digunakan

### Log Notifikasi

Error notifikasi dicatat di PHP error log:

```bash
# Lihat error log
tail -f /var/log/apache2/error.log | grep Telegram
```

## 🎯 Best Practices

1. **Selalu test setelah setup**

   - Test koneksi terlebih dahulu
   - Test setiap jenis notifikasi

2. **Monitor notifikasi**

   - Cek apakah semua notifikasi terkirim
   - Periksa error log secara berkala

3. **Backup konfigurasi**

   - Simpan copy file `.env`
   - Catat Bot Token di tempat aman

4. **Update berkala**
   - Cek update dari Telegram Bot API
   - Update PHP dan dependencies

## 📞 Support

Jika mengalami masalah:

1. Cek troubleshooting di atas
2. Periksa error log PHP
3. Test manual dengan curl
4. Verifikasi Bot Token dan Chat ID

---

**Dibuat oleh Nabsx ❤️ untuk driver ojek online Indonesia** 🏍️🇮🇩
