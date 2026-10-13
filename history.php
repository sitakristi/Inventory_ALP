<?php
session_start();
if (!isset($_SESSION['user_email'])) {
    header('Location: index.php');
    exit();
}

include 'firebase_config.php';

try {
    $refTransaksi = $database->getReference('transaksi');
    $dataTransaksiRaw = $refTransaksi->getValue() ?? [];
} catch (\Exception $e) {
    $dataTransaksiRaw = [];
}

$jsDataHistory = []; 
foreach ($dataTransaksiRaw as $id_trx => $trx) {
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

    $jsDataHistory[] = [
        'id_transaksi' => $id_trx,
        'nama_peminjam' => $trx['nama_peminjam'] ?? '-',
        'email' => $trx['email_peminjam'] ?? '-',
        'rincian' => $rincian_barang,
        'tgl_pinjam_raw' => isset($trx['tanggal_pinjam']) ? date('Y-m-d\TH:i', strtotime($trx['tanggal_pinjam'])) : '',
        'tgl_kembali_raw' => isset($trx['tanggal_kembali']) ? date('Y-m-d\TH:i', strtotime($trx['tanggal_kembali'])) : '',
        'tanggal_pinjam' => isset($trx['tanggal_pinjam']) ? date('d M Y, H:i', strtotime($trx['tanggal_pinjam'])) : '-',
        'tanggal_kembali' => isset($trx['tanggal_kembali']) ? date('d M Y, H:i', strtotime($trx['tanggal_kembali'])) : '-',
        'status' => $trx['status'] ?? 'Unknown'
    ];
}

$jsDataHistory = array_reverse($jsDataHistory);
$jsDataHistoryJson = json_encode($jsDataHistory);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/history.css">
</head>
<body>
    <div id="sidebar-container"></div>
    <main class="main-content">
        <div id="topbar-container"></div>

        <section class="content-body">

            <!-- Panel Kontrol Atas (Hapus Massal) -->
            <div class="control-panel table-container" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h2 class="table-title" style="margin: 0;"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Transaksi</h2>
                    <p style="color: var(--text-muted); font-size: 14px; margin-top: 5px;">Kelola data peminjaman dan pengembalian barang.</p>
                </div>
                <div style="background: var(--danger-bg); padding: 12px 15px; border-radius: 8px; border: 1px solid var(--danger-border);">
                    <form action="proses_history.php" method="POST" onsubmit="return confirm('Yakin ingin menghapus riwayat di bulan ini? Aksi ini tidak dapat dibatalkan.');" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <input type="hidden" name="action" value="bulk_delete">
                        <strong style="color: var(--danger-text); font-size: 14px;">Hapus Massal:</strong>
                        <select name="bulan" class="form-control" style="width: auto; padding: 6px 10px;" required>
                            <option value="">-- Bulan --</option>
                            <?php for($i=1; $i<=12; $i++) { echo "<option value='$i'>Bulan $i</option>"; } ?>
                        </select>
                        <select name="tahun" class="form-control" style="width: auto; padding: 6px 10px;" required>
                            <option value="">-- Tahun --</option>
                            <option value="2025">2025</option>
                            <option value="2026" selected>2026</option>
                            <option value="2027">2027</option>
                        </select>
                        <button type="submit" class="btn-danger"><i class="fa-solid fa-trash-can"></i> Hapus</button>
                    </form>
                </div>
            </div>

            <!-- Tabel Riwayat -->
            <div class="table-container">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Transaksi</th>
                                <th>Peminjam</th>
                                <th>Rincian Barang</th>
                                <th>Waktu Pinjam</th>
                                <th style="text-align: center;">Status</th>
                                <th style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="data-peminjaman">
                            <tr><td colspan="6" style="text-align: center;">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal Edit Transaksi -->
    <div id="modalEdit" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Transaksi</h2>
                <button type="button" class="close-btn" onclick="closeModal('modalEdit')">&times;</button>
            </div>
            <form action="proses_history.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id_transaksi">

                <div class="form-group">
                    <label>Nama Peminjam</label>
                    <input type="text" id="edit_nama" name="nama_peminjam" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Rincian Barang (Read-only)</label>
                    <textarea id="edit_rincian" class="form-control" rows="2" readonly></textarea>
                    <small style="color: var(--text-muted); font-size: 12px;">*Rincian barang bersifat tetap untuk menjaga keakuratan stok.</small>
                </div>

                <div class="form-group" style="display: flex; gap: 15px;">
                    <div style="flex: 1;">
                        <label>Tgl Pinjam</label>
                        <input type="datetime-local" id="edit_tgl_pinjam" name="tanggal_pinjam" class="form-control" required>
                    </div>
                    <div style="flex: 1;">
                        <label>Tgl Kembali</label>
                        <input type="datetime-local" id="edit_tgl_kembali" name="tanggal_kembali" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Status Transaksi</label>
                    <select id="edit_status" name="status" class="form-control" required>
                        <option value="Sedang Dipinjam">Sedang Dipinjam</option>
                        <option value="Sudah Dikembalikan">Sudah Dikembalikan</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('modalEdit')">Batal</button>
                    <button type="submit" class="btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/layout.js"></script>
    <script>
        const dataHistory = <?php echo empty($jsDataHistoryJson) ? '[]' : $jsDataHistoryJson; ?>; 
    </script>
    <script src="assets/js/history.js"></script>
</body>
</html>