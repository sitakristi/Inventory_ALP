<?php
session_start();
if (!isset($_SESSION['user_email'])) {
    header('Location: index.php');
    exit();
}

$user_email = $_SESSION['user_email'];
include 'firebase_config.php';

$success_message = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    $nama_peminjam = trim($_POST['nama_peminjam'] ?? '');
    $tanggal_kembali = trim($_POST['tanggal_kembali'] ?? '');
    $cart_json = $_POST['cart_data'] ?? '{}';
    
    $cart_data = json_decode($cart_json, true);

    if (!empty($nama_peminjam) && !empty($tanggal_kembali) && !empty($cart_data) && is_array($cart_data)) {
        try {
            // 1. Generate ID Transaksi (Format: trx_YYMMDDXX) - Anti Bentrok/Overwrite
            $today_prefix = 'trx_' . date('ymd'); // cth: trx_261013
            $transaksiRef = $database->getReference('transaksi');
            
            $snapshot = $transaksiRef->getValue();
            $allTransaksi = is_array($snapshot) ? $snapshot : [];
            $max_num = 0;
            
            foreach ($allTransaksi as $id => $val) {
                if (strpos($id, $today_prefix) === 0) {
                    // Ambil 2 digit terakhir dan cari angka terbesar
                    $num = (int)substr($id, strlen($today_prefix));
                    if ($num > $max_num) {
                        $max_num = $num;
                    }
                }
            }
            $next_num = $max_num + 1;
            $trx_id = $today_prefix . str_pad($next_num, 2, '0', STR_PAD_LEFT);
            
            // 2. Susun Data Transaksi
            $waktu_sekarang = date('Y-m-d\TH:i:s\Z');
            $tgl_kembali_formatted = date('Y-m-d\TH:i:s\Z', strtotime($tanggal_kembali));

            $data_transaksi = [
                'email_peminjam' => $user_email,
                'nama_peminjam' => $nama_peminjam,
                'status' => 'Sedang Dipinjam',
                'tanggal_pinjam' => $waktu_sekarang,
                'tanggal_kembali' => $tgl_kembali_formatted,
                'barang_dipinjam' => $cart_data
            ];
            
            // 3. Simpan Transaksi Baru
            $database->getReference('transaksi/' . $trx_id)->set($data_transaksi);
            
            // 4. Update Stok Barang dengan Pengaman (Fail-safe)
            foreach ($cart_data as $idBarang => $item) {
                $qtyPinjam = (int)($item['qty'] ?? 0);
                
                $refBarang = $database->getReference('barang/' . $idBarang);
                $stokSaatIni = $refBarang->getValue();
                
                if ($stokSaatIni && $qtyPinjam > 0) {
                    $stok_tersedia_lama = (int)($stokSaatIni['stok_tersedia'] ?? 0);
                    $stok_dipinjam_lama = (int)($stokSaatIni['stok_dipinjam'] ?? 0);
                    
                    $stok_tersedia_baru = max(0, $stok_tersedia_lama - $qtyPinjam);
                    $stok_dipinjam_baru = $stok_dipinjam_lama + $qtyPinjam;
                    
                    $refBarang->update([
                        'stok_dipinjam' => $stok_dipinjam_baru,
                        'stok_tersedia' => $stok_tersedia_baru
                    ]);
                }
            }
            
            $success_message = true;
            
        } catch (\Exception $e) {
            $errMsg = addslashes($e->getMessage());
            echo "<script>alert('Error Checkout: {$errMsg}');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Peminjaman</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/addproduct.css">
</head>
<body>
    <div id="sidebar-container"></div>

    <main class="main-content">
        <div id="topbar-container"></div>
        
        <section class="content-body">
            
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                
                <!-- KOLOM KIRI: Daftar Keranjang -->
                <div class="table-container" style="flex: 2; min-width: 300px;">
                    <h2 class="table-title">Daftar Barang Dipinjam</h2>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID Barang</th>
                                    <th>Nama Barang</th>
                                    <th style="text-align: center;">Qty</th>
                                    <th style="text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="cart-table-body">
                                <tr><td colspan="4" style="text-align: center;">Memuat keranjang...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- KOLOM KANAN: Form Konfirmasi -->
                <div class="table-container" style="flex: 1; min-width: 300px; height: fit-content;">
                    <h2 class="table-title">Detail Peminjam</h2>
                    <form id="form-checkout" method="POST" action="" class="form-grid">
                        <input type="hidden" name="action" value="checkout">
                        <input type="hidden" id="cart_data_input" name="cart_data">
                        
                        <div class="form-group">
                            <label>Email Pengguna (Otomatis)</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user_email); ?>" readonly style="background-color: var(--bg-main); opacity: 0.7; cursor: not-allowed;">
                        </div>
                        
                        <div class="form-group">
                            <label>Nama Peminjam <span style="color: var(--danger-text);">*</span></label>
                            <input type="text" name="nama_peminjam" class="form-control" required placeholder="Masukkan nama lengkap peminjam">
                        </div>
                        
                        <div class="form-group">
                            <label>Batas Waktu Pengembalian <span style="color: var(--danger-text);">*</span></label>
                            <input type="datetime-local" id="tanggal_kembali" name="tanggal_kembali" class="form-control" required>
                        </div>
                        
                        <div class="form-actions" style="margin-top: 20px;">
                            <button type="submit" id="btn-submit-checkout" class="btn-submit" style="width: 100%;">Konfirmasi Peminjaman</button>
                        </div>
                    </form>
                </div>
                
            </div>
            
        </section>
    </main>

    <script src="assets/js/layout.js"></script>
    <script>
        const userEmail = "<?php echo htmlspecialchars($user_email); ?>";
        const isSuccess = <?php echo $success_message ? 'true' : 'false'; ?>;
    </script>
    <script src="assets/js/checkout.js"></script>
</body>
</html>