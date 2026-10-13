# Laporan Tugas Kelompok Sesi 13: Inventaris - Sistem Manajemen Peminjaman dan Inventaris Barang Berbasis Cloud

**Disusun Oleh:**
* **Frederich Irawan** (NIM: 1076012514001)
* **Rasita Kristi Ulliyani** (NIM: 0706012414016)

**Institusi:** UC Online Learning  
**Repositori GitHub:** [https://github.com/sitakristi/Inventory_ALP.git](https://github.com/sitakristi/Inventory_ALP.git)

---

## 1. Deskripsi Aplikasi & Website
Aplikasi ini merupakan **Sistem Manajemen Peminjaman dan Inventaris Barang Berbasis Cloud** yang dirancang untuk mempermudah pengelolaan data barang, peminjaman, pelaporan barang rusak, serta memberikan analisis rekomendasi berbasis AI.

### Fitur Utama Aplikasi:
* **Autentikasi Pengguna:** Sistem login dan logout pengguna (`index.php`, `logout.php`).
* **Dashboard Utama:** Halaman ringkasan status inventaris dan aktivitas sistem (`dashboard.php`).
* **Manajemen Produk (CRUD Barang):** Fitur untuk menampilkan daftar produk (`product.php`) dan menambahkan produk baru (`addproduct.php`) yang terhubung langsung ke Firebase Realtime Database.
* **Proses Peminjaman & Checkout:** Peminjaman barang oleh pengguna (`checkout.php`).
* **Riwayat Peminjaman (History):** Pemrosesan dan pencatatan riwayat transaksi peminjaman barang (`history.php`, `proses_history.php`).
* **Pelaporan Barang Rusak:** Fitur pelaporan kondisi dan kerusakan barang (`reportbarangrusak.php`).
* **Rekomendasi AI:** Fitur cerdas berbasis AI untuk membantu analisis rekomendasi data inventaris (`rekomendasi_ai.php`).

---

## 2. Layanan Cloud & Teknologi yang Digunakan

* **Firebase Realtime Database:** Layanan database cloud dari Google yang digunakan untuk menyimpan data produk, transaksi peminjaman, dan riwayat secara *real-time* (`firebase_config.php`).
* **Docker / Cloud Containerization:** Aplikasi dikemas menggunakan Docker container (`Dockerfile`) untuk memudahkan *deployment* ke penyedia platform cloud.
* **PHP & Composer:** Bahasa pemrograman utama server-side beserta manajemen dependensi library (`composer.json`).

---

## 3. Integrasi Firebase Realtime Database & Fungsi CRUD

Aplikasi ini telah terhubung ke Firebase Realtime Database menggunakan kredensial Firebase SDK (`firebase_config.php`, `firebase_credentials.json`). Fungsi CRUD yang diimplementasikan meliputi:
1. **Create:** Menambahkan data barang/produk baru ke database real-time (`addproduct.php`).
2. **Read:** Membaca dan menampilkan daftar barang, riwayat peminjaman, serta laporan dari database (`product.php`, `dashboard.php`, `history.php`).
3. **Update:** Memperbarui status barang dan proses peminjaman (`proses_history.php`, `checkout.php`).
4. **Delete / Pelaporan:** Mengelola status kelayakan barang serta pelaporan barang rusak (`reportbarangrusak.php`).

---

## 4. Struktur Direktori Proyek

```text
ALP_cloudcomputing/
├── assets/
├── other/
├── vendor/
├── .gitattributes
├── .gitignore
├── addproduct.php
├── checkout.php
├── composer.json
├── composer.lock
├── dashboard.php
├── Dockerfile
├── firebase_config.php
├── firebase_credentials.json
├── history.php
├── index.php
├── logout.php
├── product.php
├── proses_history.php
├── rekomendasi_ai.php
└── reportbarangrusak.php