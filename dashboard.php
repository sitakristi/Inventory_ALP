<?php
session_start();
if (!isset($_SESSION['user_email'])) {
    header('Location: index.php');
    exit();
}

include 'firebase_config.php';

try {
    // 1. Ambil data Barang
    $refBarang = $database->getReference('barang');
    $dataBarangRaw = $refBarang->getValue() ?? [];

    // 2. Ambil data Kategori (Diperbaiki dari 'master_kategori' menjadi 'kategori')
    $refKat = $database->getReference('kategori'); 
    $masterKategori = $refKat->getValue() ?? [];
    
    // 3. Ambil data Transaksi (Peminjaman)
    $refTransaksi = $database->getReference('transaksi');
    $dataTransaksiRaw = $refTransaksi->getValue() ?? [];
    
} catch (\Exception $e) {
    $dataBarangRaw = [];
    $masterKategori = [];
    $dataTransaksiRaw = [];
}

$jsDataBarangRusak = [];
$jsDataPeminjaman = [];

// Looping data barang untuk mencari yang rusak
foreach ($dataBarangRaw as $id => $item) {
    if (isset($item['stok_rusak']) && $item['stok_rusak'] > 0) {
        $jsDataBarangRusak[] = [
            'id' => $id, 
            'nama' => $item['nama'] ?? '-', 
            'kategori' => $item['kategori'] ?? '-', 
            'stok_rusak' => $item['stok_rusak'] ?? 0 
        ];
    }
}

// Looping data transaksi untuk mencari peminjaman aktif
foreach ($dataTransaksiRaw as $id_trx => $trx) {
    if (isset($trx['status']) && ($trx['status'] === 'Sedang Dipinjam')) {
        
        $rincian_barang = "";
        if (isset($trx['barang_dipinjam']) && is_array($trx['barang_dipinjam'])) {
            $barang_list = [];
            foreach ($trx['barang_dipinjam'] as $item_id => $item_detail) {
                $qty = $item_detail['qty'] ?? 1;
                $nama = $item_detail['nama'] ?? 'Barang';
                $barang_list[] = "$qty x $nama";
            }
            $rincian_barang = implode(', ', $barang_list); 
        }

        $jsDataPeminjaman[] = [
            'id_transaksi' => $id_trx,
            'nama_peminjam' => $trx['nama_peminjam'] ?? '-',
            'email' => $trx['email_peminjam'] ?? '-',
            'rincian' => $rincian_barang,
            'tanggal_pinjam' => isset($trx['tanggal_pinjam']) ? date('d M Y, H:i', strtotime($trx['tanggal_pinjam'])) : '-',
            'tanggal_kembali' => isset($trx['tanggal_kembali']) ? date('d M Y, H:i', strtotime($trx['tanggal_kembali'])) : '-',
            'status' => $trx['status'] ?? 'Unknown'
        ];
    }
}

$jsDataPeminjaman = array_reverse($jsDataPeminjaman);

$jsMasterKat = json_encode($masterKategori);
$jsDataBarangRusakJson = json_encode($jsDataBarangRusak);
$jsDataPeminjamanJson = json_encode($jsDataPeminjaman);
$jsKatalogBarangJson = json_encode($dataBarangRaw);
$currentUserEmail = $_SESSION['user_email'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Inventaris</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/addproduct.css">
</head>
<body>

    <div id="sidebar-container"></div>

    <main class="main-content">
        <div id="topbar-container"></div>

        <section class="content-body">
            
            <!-- Fitur AI: Rekomendasi Peminjaman Berdasarkan Acara -->
            <div class="table-container" style="margin-bottom: 20px; border-left: 5px solid var(--primary-color);">
                <h2 class="table-title"><i class="fa-solid fa-wand-magic-sparkles" style="color: var(--primary-color);"></i> AI Asisten Rekomendasi Acara</h2>
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 15px;">Masukkan nama acara/kegiatan untuk mendapatkan saran barang yang tersedia di database.</p>
                
                <div style="display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap;">
                    <input type="text" id="inputNamaAcara" placeholder="Contoh: Seminar IoT Kampus / Rapat BEM" class="form-control" style="flex: 1; min-width: 250px;">
                    <button type="button" class="btn-primary" onclick="mintaRekomendasiAI()" style="padding: 10px 20px; font-weight: bold;">
                        <i class="fa-solid fa-robot"></i> Minta Saran AI
                    </button>
                </div>

                <!-- ID diperbaiki menjadi boxHasilAi agar sesuai dengan fungsi JS -->
                <div id="boxHasilAi" style="padding: 15px; background-color: var(--bg-main); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-main); font-size: 14px; white-space: pre-line;">
                    Belum ada acara yang dianalisis. Masukkan nama acara di atas lalu klik tombol "Minta Saran AI".
                </div>

                <div id="containerTombolCheckout" style="display: none; margin-top: 15px;">
                    <button type="button" onclick="kirimRekomendasiKeCheckout()" style="background-color: var(--success-color); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold;">
                        <i class="fa-solid fa-cart-shopping"></i> Masukkan ke Form Checkout & Lanjutkan <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Tabel 1: Peminjaman Aktif -->
            <div class="table-container" style="margin-bottom: 20px;">
                <h2 class="table-title">Daftar Peminjaman Aktif</h2>
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 15px;">Menampilkan transaksi yang sedang berjalan (Status: Sedang Dipinjam).</p>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Transaksi</th>
                                <th>Nama Peminjam</th>
                                <th>Rincian Barang</th>
                                <th>Waktu Pinjam</th>
                                <th>Tenggat Waktu</th>
                                <th style="text-align: center;">Status</th>
                            </tr>
                        </thead>
                        <tbody id="data-peminjaman">
                            <tr>
                                <td colspan="6" style="text-align: center;">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabel 2: Barang Rusak -->
            <div class="table-container">
                <h2 class="table-title" style="margin-bottom: 15px;">Daftar Barang Rusak</h2>
                <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                    <label for="limit-barang-rusak" style="font-weight: bold;">Tampilkan:</label>
                    <select id="limit-barang-rusak" class="form-control" style="width: auto; padding: 5px 10px;">
                        <option value="5" selected>5</option>
                        <option value="10">10</option>
                        <option value="50">50</option>
                        <option value="all">Semua</option>
                    </select>
                    <span>data</span>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Jumlah Rusak</th>
                            </tr>
                        </thead>
                        <tbody id="data-barang-rusak">
                            <tr><td colspan="4" style="text-align: center;">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </section>
    </main>

    <script src="assets/js/layout.js"></script>
    <script>
        const masterKategori = <?php echo empty($jsMasterKat) ? '{}' : $jsMasterKat; ?>;
        const dataBarangRusakAll = <?php echo empty($jsDataBarangRusakJson) ? '[]' : $jsDataBarangRusakJson; ?>;
        const dataPeminjamanAll = <?php echo empty($jsDataPeminjamanJson) ? '[]' : $jsDataPeminjamanJson; ?>;
        const katalogBarangFirebase = <?php echo empty($jsKatalogBarangJson) ? '{}' : $jsKatalogBarangJson; ?>;
        const currentUserEmail = "<?php echo htmlspecialchars($currentUserEmail); ?>";
    </script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>