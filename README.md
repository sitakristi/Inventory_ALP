# Inventaris Cloud — Sistem Manajemen Peminjaman Barang Berbasis Cloud

Proyek Aplikasi Web Sistem Manajemen Peminjaman dan Inventaris Barang Berbasis Cloud untuk memenuhi tugas **Application Development Application / Cloud Computing** di Universitas Ciputra Surabaya.

---

## Anggota Kelompok 4
1. **Frederich Irawan** — `1076012514001`
2. **Rasita Kristi Ulliyani** — `0706012414016`

---

## Deskripsi Proyek
Sistem Inventaris ini dirancang untuk mempermudah pencatatan stok, transaksi peminjaman, serta pelaporan kerusakan barang secara terpusat dan otomatis dengan memanfaatkan **Firebase Realtime Database**.

### Fitur Utama (Progress Checkpoint 2 — 50%+)
1. **Inisialisasi Firebase SDK (`firebase_config.php`)**
   - Menggunakan `Kreait\Firebase\SDK` untuk menghubungkan aplikasi PHP ke Firebase Realtime Database.
2. **Manajemen Master Data (`addproduct.php`)**
   - **Kategori:** Tambah, edit nama, dan hapus kategori.
   - **Barang:** Tambah barang, update stok (total, rusak, dipinjam), serta hapus barang dengan validasi stok otomatis.
3. **Katalog Peminjaman (`product.php`)**
   - Menampilkan barang yang memiliki `stok_tersedia > 0` secara realtime dan memfilter barang berdasarkan kategori.
4. **Checkout Transaksi (`checkout.php`)**
   - Pembuatan ID Transaksi otomatis (`trx_YYMMDDXX`).
   - Pencatatan transaksi peminjaman baru ke node `transaksi`.
   - Pembaruan stok barang (`stok_dipinjam` & `stok_tersedia`) secara otomatis.

---

## Teknologi & Stack
- **Language:** PHP 8.x, JavaScript (ES6), HTML5, CSS3
- **Database & Auth:** Firebase Realtime Database, Firebase Authentication
- **SDK / Library:** `Kreait\Firebase\SDK`, FontAwesome 6
- **Version Control:** Git & GitHub

---

## Cara Menjalankan secara Lokal

1. **Clone Repositori:**
   ```bash
   git clone [https://github.com/sitakristi/Inventory](https://github.com/sitakristi/Inventory) ALP.git
   cd "Inventory ALP"