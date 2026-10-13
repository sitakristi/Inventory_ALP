<?php
session_start();
if (!isset($_SESSION['user_email'])) {
    header('Location: index.php');
    exit();
}

// 1. PANGGIL KONEKSI FIREBASE
include 'firebase_config.php';

// 2. TANGANI FORM SUBMIT (UPDATE STOK RUSAK)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'edit_rusak') {
        $kode = trim($_POST['kode'] ?? '');
        $stok_rusak = (int)($_POST['stok_rusak'] ?? 0);
        
        // Ambil stok total dan dipinjam dari form hidden untuk kalkulasi ulang
        $stok_total = (int)($_POST['stok_total'] ?? 0);
        $stok_dipinjam = (int)($_POST['stok_dipinjam'] ?? 0);
        
        $stok_tersedia = $stok_total - $stok_rusak - $stok_dipinjam;
        
        if ($stok_rusak + $stok_dipinjam > $stok_total) {
            echo "<script>alert('Gagal! Jumlah barang rusak + dipinjam melebihi Stok Total fisik.');</script>";
        } else {
            try {
                // Hanya perbarui field stok_rusak dan stok_tersedia
                $database->getReference('barang/' . $kode)->update([
                    'stok_rusak' => $stok_rusak,
                    'stok_tersedia' => $stok_tersedia
                ]);
                echo "<script>alert('Data stok rusak berhasil diperbarui!');</script>";
            } catch (Exception $e) {
                $errMsg = addslashes($e->getMessage());
                echo "<script>alert('Error Firebase: {$errMsg}');</script>";
            }
        }
    }
}

// 3. AMBIL DATA DARI FIREBASE UNTUK DITAMPILKAN
try {
    $dataBarangRaw = $database->getReference('barang')->getValue() ?? [];
    $dataKategoriRaw = $database->getReference('kategori')->getValue() ?? [];
} catch (Exception $e) {
    $dataBarangRaw = [];
    $dataKategoriRaw = [];
}

$jsDataBarang = json_encode($dataBarangRaw);
$jsMasterKategori = json_encode($dataKategoriRaw);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Barang Rusak</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Panggil style.css lebih dulu baru addproduct.css -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/addproduct.css">
</head>
<body>

    <div id="sidebar-container"></div>

    <main class="main-content">
        <div id="topbar-container"></div>
        <section class="content-body">

            <!-- Area Tabel (Read Barang) -->
            <div class="table-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                    <h2 class="table-title" style="margin-bottom: 0;">Laporan Barang Rusak</h2>
                    
                    <div style="display: flex; gap: 10px;">
                        <select id="filter-kategori" class="form-control" style="width: auto; padding: 6px 12px;">
                            <option value="all">Semua Kategori</option>
                        </select>
                        <select id="filter-kondisi" class="form-control" style="width: auto; padding: 6px 12px;">
                            <option value="all">Semua Kondisi</option>
                            <option value="baik">Kondisi Baik</option>
                            <option value="rusak">Ada Kerusakan</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Stok</th>
                                <th style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tabel-semua-barang">
                            <tr><td colspan="5" style="text-align: center;">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <!-- POPUP MODAL UNTUK EDIT KHUSUS BARANG RUSAK -->
    <div id="editModal" class="modal">
        <div class="modal-content table-container">
            <span class="close-modal">&times;</span>
            <h2 class="table-title" id="edit-title">Update Stok Rusak</h2>
            <form id="form-edit" class="form-grid" method="POST" action="">
                <input type="hidden" name="action" value="edit_rusak">
                <input type="hidden" id="edit_kode" name="kode">
                <input type="hidden" id="edit_stok_total" name="stok_total">
                <input type="hidden" id="edit_stok_dipinjam" name="stok_dipinjam">
                
                <div class="form-group">
                    <label>Nama Barang (Terkunci)</label>
                    <input type="text" id="edit_nama" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Stok Rusak Saat Ini <span style="color: var(--danger-text);">*</span></label>
                    <input type="number" id="edit_stok_rusak" name="stok_rusak" class="form-control" required min="0" style="border-color: var(--danger-border);">
                    <small style="color: var(--text-muted); display: block; margin-top: 5px;">Ubah angka ini untuk menambah atau mengurangi jumlah fisik barang yang rusak.</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-danger" style="padding: 10px 20px; font-weight: bold;">Simpan Laporan Rusak</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/layout.js"></script>
    <script>
        const allBarangData = <?php echo empty($jsDataBarang) ? '{}' : $jsDataBarang; ?>;
        const masterKategori = <?php echo empty($jsMasterKategori) ? '{}' : $jsMasterKategori; ?>;
    </script>
    <script src="assets/js/reportbarangrusak.js"></script>
</body>
</html>