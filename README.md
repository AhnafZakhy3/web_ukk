# 🛒 NusaPOS — Point of Sale & Retail Management System

<div align="center">

![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)
![Vanilla JS](https://img.shields.io/badge/Vanilla_JS-ES6%2B-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![Tests](https://img.shields.io/badge/Automated_Tests-30%2F30_PASSED-success?style=for-the-badge&logo=checkmarx&logoColor=white)
![Status](https://img.shields.io/badge/Status-Production_Ready-brightgreen?style=for-the-badge)

<p align="center">
  <strong>Aplikasi Web Kasir Ritel Modern Berstandar Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak & Industri Ritel</strong>
</p>

[📖 Baca Dokumentasi Teknis Penuh (10 Bab Lengkap)](DOKUMENTASI_PROYEK.md) • [🚀 Panduan Instalasi Cepat](#panduan-instalasi-cepat) • [👤 Akun Demo](#kredensial-akun-demo) • [🧪 Hasil Pengujian](#hasil-pengujian-sistem)

</div>

---

## 🌟 Tentang NusaPOS

**NusaPOS (Nusantara Point of Sale)** adalah perangkat lunak kasir ritel berbasis web yang dirancang untuk kecepatan kasir, presisi inventori stok, dan transparansi laporan keuangan. Sistem dibangun tanpa beban dependensi eksternal (*zero heavy frameworks*), mengedepankan kode PHP prosedural modular yang bersih, pengamanan berlapis berstandar OWASP, serta antarmuka modern yang responsif.

Aplikasi ini memenuhi seluruh indikator kelulusan **Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak** tingkat mahir serta siap dioperasikan langsung pada toko ritel, minimarket modern, maupun UMKM.

---

## ✨ Fitur Unggulan

### ⚡ 1. Terminal Kasir Berkecepatan Tinggi
- **Pencarian Cepat & Barcode Scanning:** Cari nama produk, kode SKU, atau pindai barcode produk secara instan.
- **Pintasan Cepat Antarmuka:** Tombol pembatalan/penutup modal instan (<kbd>Esc</kbd>) dan toggle bilah samping (<kbd>Ctrl+B</kbd>).
- **Pilihan Denominasi Cepat (Quick Cash):** Tombol kalkulasi uang pas, Rp20.000, Rp50.000, Rp100.000 dengan kalkulator kembalian otomatis.
- **Multimetode Pembayaran:** Mendukung Uang Tunai (*Cash*), QRIS Dinamis, Kartu Debit EDC Bank, dan Transfer Rekening.

### 🧾 2. Pratinjau & Cetak Struk Thermal 80mm
- Desain struk thermal profesional dengan nomor faktur otomatis `TRX-YYYYMMDD-XXXX`.
- Memuat nama toko, cabang, identitas kasir, waktu transaksi, rincian barang, diskon, PPN 11%, dan pesan penutup struk.
- Terintegrasi dengan fitur cetak native peramban (*browser print*) yang kompatibel dengan seluruh printer POS thermal USB/Bluetooth.

### 🔒 3. Integritas Data & Transaksi Atomik
- Menggunakan transaksi basis data ACID (`beginTransaction`, `commit`, `rollback`).
- Pemotongan stok dilakukan dengan penguncian baris (`SELECT ... FOR UPDATE`), mencegah kondisi persaingan stok (*race conditions*) atau *overselling*.

### 📦 4. Manajemen Inventori & Klasifikasi
- **Unggah Gambar Produk Lokal & URL:** Mendukung upload file foto produk dari perangkat (JPG, PNG, WEBP) maupun input URL gambar eksternal.
- **Slider Horizontal Ergonomis:** Bilah geser tabel produk dan kategori selalu terjangkau di bagian bawah layar.
- **Filter Tampilan Baris:** Pengaturan jumlah baris tabel (10, 30, atau 50 produk per halaman) dengan paginasi responsif.
- Pemantauan level stok multi-indikator: *Tersedia*, *Menipis* (di bawah batas minimum stok), dan *Habis*.
- Manajemen kategori departemen dan unit kemasan (Pcs, Botol, Pack, Dus, Cup, Kg, Liter).
- Proteksi integritas relasi: Kategori yang masih menaungi produk tidak dapat dihapus sembarangan.
- Fitur ekspor katalog ke CSV, unduh template impor, dan pengunggahan stok massal.

### 📊 5. Laporan Penjualan & Riwayat Faktur
- Dashboard analitik omzet kotor, total transaksi, setoran PPN, dan rerata belanja pelanggan.
- Filter fleksibel berdasarkan rentang tanggal kalender, nama kasir, dan metode pembayaran.
- Penelusuran rincian item transaksi dengan sistem *eager loading* untuk kecepatan render data.
- Ekspor laporan penjualan terfilter ke format spreadsheet CSV.

### 👥 6. Manajemen Pengguna & Hak Akses (RBAC)
- Pemisahan otoritas tegas antara **Administrator** (Katalog, Kategori, Akun, Analitik Laba) dan **Kasir** (Transaksi POS, Cetak Struk, Riwayat).
- **Foto Profil Mandiri & Topbar:** Seluruh pengguna (Kasir maupun Admin) dapat mengganti foto profil mereka sendiri secara instan dari dropdown topbar.
- Proteksi *Self-Deletion*: Administrator tidak dapat menghapus atau menonaktifkan akun miliknya sendiri saat sedang bertugas.

### 🌓 7. Dual Theme & Penyesuaian Antarmuka
- Sakelar tema visual gelap dan terang dengan deteksi preferensi sistem otomatis dan anti-kedip (*Anti-FOUC*).
- **Toggle Collapse Sidebar (<kbd>Ctrl+B</kbd>):** Opsi menyembunyikan sidebar di desktop untuk ruang transaksi kasir maksimal.

---

## 🛠️ Tumpukan Teknologi (Tech Stack)

| Lapisan | Teknologi | Spesifikasi |
| :--- | :--- | :--- |
| **Backend** | PHP 8.2+ / 8.3 | Procedural Modular, Strict Types, PDO Singleton |
| **Database** | MySQL 8.0+ / MariaDB 10.4+ | InnoDB Engine, `utf8mb4_unicode_ci`, Foreign Key Constraints |
| **Styling** | Tailwind CSS v4 CLI | Dikompilasi via `@tailwindcss/cli`, Desain Sistem Kustom |
| **Frontend** | Vanilla JavaScript (ES6+) | Tanpa framework berat, DOM API, Web Storage |
| **Icons & Fonts** | Google Fonts | Plus Jakarta Sans & Material Symbols Outlined |
| **Server Target**| Laragon / Apache | Berjalan mulus di server lokal tanpa ketergantungan Docker |

---

<a id="panduan-instalasi-cepat"></a>
## 🚀 Panduan Instalasi Cepat

### Prasyarat:
- Web server lokal terpasang: **Laragon** (Sangat Direkomendasikan) atau **XAMPP** dengan PHP 8.2+ dan MySQL berjalan.

### Langkah-Langkah:

1. **Tempatkan Proyek pada Root Web Server:**
   Pastikan folder proyek berada di direktori web server, misalnya:
   ```text
   C:\laragon\www\web_ukk\   atau   c:\ahnaf\web_ukk\
   ```

2. **Jalankan Inisialisasi Basis Data Otomatis:**
   Buka terminal PowerShell dan jalankan skrip migrasi bawaan:
   ```powershell
   cd c:\ahnaf\web_ukk
   php setup_db.php
   ```
   *Skrip ini akan otomatis membuat basis data `nusapos_db`, menyusun 7 tabel InnoDB, dan mengisi data awal (seed). Jika ingin mulai secara bersih tanpa data dummy katalog/transaksi, gunakan flag `php setup_db.php --clean`.*

3. **Buka Aplikasi pada Browser:**
   Akses melalui browser favorit Anda:
   ```text
   http://localhost/web_ukk/
   ```

4. **(Opsional) Kompilasi Ulang Styling Tailwind CSS v4:**
   Jika Anda mengubah berkas `src/input.css`:
   ```powershell
   npm install
   npm run build:css
   ```

---

<a id="kredensial-akun-demo"></a>
## 👤 Kredensial Akun Demo

Gunakan akun demo bawaan berikut untuk mencoba sistem sesuai peran:

| Peran (Role) | Username | Password | Deskripsi Kewenangan |
| :--- | :--- | :--- | :--- |
| **Administrator** | `budisantoso` | `password123` | Hak penuh: Akses inventori, kategori, akun user, dan laporan omzet |
| **Kasir Utama** | `sitikasir1` | `password123` | Hak operasional: Terminal kasir, pembayaran, cetak struk, dan riwayat |
| **Kasir Shift** | `andrirahmat` | `password123` | Hak operasional kasir |
| **Akun Nonaktif** | `dewilestari` | `password123` | Akun diblokir (untuk pengujian penolakan login) |

---

<a id="hasil-pengujian-sistem"></a>
## 🧪 Hasil Pengujian Sistem

Sistem dilengkapi suite pengujian otomatis end-to-end (`tests/squad_test.php`) yang memverifikasi 30 skenario krusial:

```powershell
php tests/squad_test.php
```

```text
====================================================
   NusaPOS Automated End-to-End Test Suite          
====================================================

1. AUTHENTICATION & ACCESS CONTROL TESTS:
  [PASS] Unauthenticated kasir.php redirects to login.php
  [PASS] Unauthenticated pengguna.php redirects to login.php
  [PASS] Login with invalid credentials fails
  [PASS] Login as Kasir redirects to kasir.php
  [PASS] Kasir role blocked from pengguna.php (redirects to kasir.php)
  [PASS] Kasir can access kasir.php successfully

2. CHECKOUT & INVENTORY ATOMICITY TESTS:
  [PASS] Checkout valid responds HTTP 200 with success=true
  [PASS] Product stock accurately decremented by 2 in DB
  [PASS] Transaction row created in `transaksi` table
  [PASS] Checkout overstock rejected with HTTP 400
  [PASS] Checkout underpayment rejected with HTTP 400

3. ADMINISTRATOR PRIVILEGES & CRUD TESTS:
  [PASS] Login as Administrator succeeds
  [PASS] Valid CSRF token extracted from session page
  [PASS] Admin can access pengguna.php successfully
  [PASS] Action without valid CSRF token rejected
  [PASS] Admin can create new category with CSRF token
  [PASS] New category exists in database
  [PASS] Duplicate category code rejected with flash error
  [PASS] Admin can create new product
  [PASS] New product exists in database
  [PASS] Prevent deleting category with active products
  [PASS] Product without transaction history deleted permanently
  [PASS] Empty category deleted permanently

4. SECURITY & EDGE CASES:
  [PASS] Admin cannot delete own currently logged in account
  [PASS] Export CSV template responds with CSV headers
  [PASS] Export CSV products responds with CSV headers and records
  [PASS] Export CSV transactions responds with CSV headers and records
  [PASS] Transaksi page renders successfully with eager-loaded details
  [PASS] Logout redirects to login.php
  [PASS] Session cleared after logout

====================================================
TEST RESULTS SUMMARY:
  Total Passed: 30
  Total Failed: 0
====================================================
```

---

## 📚 Dokumentasi Teknis Penuh (Standar UKK)

Untuk laporan lengkap berstandar pengujian UKK Rekayasa Perangkat Lunak, silakan buka:
👉 **[DOKUMENTASI_PROYEK.md](DOKUMENTASI_PROYEK.md)**

Dokumentasi tersebut memuat 10 bab terstruktur:
1. **Bab 1:** Profil & Latar Belakang Proyek
2. **Bab 2:** Spesifikasi Teknis & Kebutuhan Sistem
3. **Bab 3:** Matriks Fitur Lengkap & Hak Akses (RBAC)
4. **Bab 4:** Alur Bisnis & Flowchart Aplikasi (Mermaid)
5. **Bab 5:** Entity Relationship Diagram (ERD Mermaid) & Kamus Data 7 Tabel Lengkap
6. **Bab 6:** Pemodelan UML Lengkap (Use Case Diagram & Tabel Skenario, 4 Activity Diagram, 2 Sequence Diagram, Class Diagram)
7. **Bab 7:** Arsitektur Keamanan Sistem (PDO Prepared Statements, CSRF Token 32-Byte, Sanitasi XSS, Bcrypt, Sesi HttpOnly)
8. **Bab 8:** Struktur Direktori & Penjelasan Peran Berkas
9. **Bab 9:** Panduan Instalasi, Konfigurasi Server & Setup Database
10. **Bab 10:** Catatan Hasil Pengujian Sistem (Automated Suite & Live Testing)

---

<div align="center">
  <p>© 2026 NusaPOS — Dibangun untuk Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak</p>
</div>
