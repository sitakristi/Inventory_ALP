<?php
session_start();

if (!isset($_SESSION['user_email'])) {
    header('Location: index.php');
    exit();
}

$user_email = $_SESSION['user_email'];

include 'firebase_config.php';

try {
    $dataBarangRaw = $database->getReference('barang')->getValue() ?? [];
    $dataKategoriRaw = $database->getReference('kategori')->getValue() ?? [];
} catch (\Exception $e) {
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
    <title>Katalog Peminjaman</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/addproduct.css">
</head>
<body>
    <div id="sidebar-container"></div>

    <main class="main-content">
        <div id="topbar-container"></div>
        
        <section class="content-body">
            
            <div class="table-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h2 class="table-title" style="margin-bottom: 5px;">Katalog Barang</h2>
                        <p style="color: var(--text-muted); font-size: 14px;">Pilih barang yang ingin dipinjam (hanya menampilkan stok tersedia).</p>
                    </div>
                    
                    <!-- Dropdown Filter -->
                    <div style="display: flex; gap: 10px;">
                        <select id="filter-kategori" class="form-control" style="width: auto; padding: 6px 12px;">
                            <option value="all">Semua Kategori</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th style="text-align: center;">Stok Tersedia</th>
                                <th style="width: 120px; text-align: center;">Qty</th>
                                <th style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tabel-katalog">
                            <tr><td colspan="5" style="text-align: center;">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </section>
    </main>

    <!-- Tombol Keranjang Floating untuk Lanjut ke Checkout -->
    <a href="checkout.php" class="floating-cart" title="Lanjut ke Checkout">
        <i class="fa-solid fa-cart-shopping"></i>
        <span class="cart-badge" id="cart-count">0</span>
    </a>

    <script src="assets/js/layout.js"></script>
    <script>
        const allBarangData = <?php echo empty($jsDataBarang) ? '{}' : $jsDataBarang; ?>;
        const masterKategori = <?php echo empty($jsMasterKategori) ? '{}' : $jsMasterKategori; ?>;
        const userEmail = "<?php echo htmlspecialchars($user_email); ?>";
    </script>
    <script src="assets/js/product.js"></script>
</body>
</html>