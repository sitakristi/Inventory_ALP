<?php
session_start();
include 'firebase_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. PROSES EDIT & PENGEMBALIAN STOK
    if ($action === 'edit') {
        $id_trx = $_POST['id_transaksi'];
        $status_baru = $_POST['status'];
        
        $refTrx = $database->getReference('transaksi/' . $id_trx);
        $trxLama = $refTrx->getValue();

        if ($trxLama) {
            $status_lama = $trxLama['status'] ?? 'Sedang Dipinjam';

            // JIKA STATUS BERUBAH (ADA PERBEDAAN)
            if ($status_lama !== $status_baru) {
                if (isset($trxLama['barang_dipinjam']) && is_array($trxLama['barang_dipinjam'])) {
                    foreach ($trxLama['barang_dipinjam'] as $id_barang => $detail) {
                        $qty = $detail['qty'] ?? 0;
                        
                        $refBarang = $database->getReference('barang/' . $id_barang);
                        $dataBarang = $refBarang->getValue();
                        
                        if ($dataBarang) {
                            $stok_tersedia = $dataBarang['stok_tersedia'] ?? 0;
                            $stok_dipinjam = $dataBarang['stok_dipinjam'] ?? 0;
                            $stok_total = $dataBarang['stok_total'] ?? ($stok_tersedia + $stok_dipinjam);

                            // KASUS A: Dari "Sedang Dipinjam" berubah jadi "Sudah Dikembalikan"
                            // (Stok tersedia bertambah, stok dipinjam berkurang)
                            if ($status_lama === 'Sedang Dipinjam' && $status_baru === 'Sudah Dikembalikan') {
                                $stok_tersedia += $qty;
                                $stok_dipinjam -= $qty;
                            }
                            // KASUS B: Dari "Sudah Dikembalikan" berubah BALIK jadi "Sedang Dipinjam"
                            // (Stok tersedia berkurang, stok dipinjam bertambah kembali)
                            else if ($status_lama === 'Sudah Dikembalikan' && $status_baru === 'Sedang Dipinjam') {
                                $stok_tersedia -= $qty;
                                $stok_dipinjam += $qty;
                            }

                            // Pastikan angka stok tidak pernah minus atau melebihi total
                            $stok_tersedia = max(0, min($stok_tersedia, $stok_total));
                            $stok_dipinjam = max(0, min($stok_dipinjam, $stok_total));

                            // Simpan perubahan stok ke database barang
                            $refBarang->update([
                                'stok_tersedia' => $stok_tersedia,
                                'stok_dipinjam' => $stok_dipinjam
                            ]);
                        }
                    }
                }
            }

            // Update data transaksi di database
            $updateData = [
                'nama_peminjam' => $_POST['nama_peminjam'],
                'tanggal_pinjam' => date('Y-m-d\TH:i:s\Z', strtotime($_POST['tanggal_pinjam'])),
                'tanggal_kembali' => date('Y-m-d\TH:i:s\Z', strtotime($_POST['tanggal_kembali'])),
                'status' => $status_baru
            ];
            
            $refTrx->update($updateData);
        }
        
        header('Location: history.php?msg=edit_success');
        exit();
    }

    // 2. PROSES HAPUS SATUAN
    if ($action === 'delete_single') {
        $id_trx = $_POST['id_transaksi'];
        $refTrx = $database->getReference('transaksi/' . $id_trx);
        $trxLama = $refTrx->getValue();

        // Jika transaksi dihapus saat masih "Sedang Dipinjam", kembalikan stoknya
        if ($trxLama && isset($trxLama['status']) && $trxLama['status'] === 'Sedang Dipinjam') {
            if (isset($trxLama['barang_dipinjam']) && is_array($trxLama['barang_dipinjam'])) {
                foreach ($trxLama['barang_dipinjam'] as $id_barang => $detail) {
                    $qty_kembali = $detail['qty'] ?? 0;
                    $refBarang = $database->getReference('barang/' . $id_barang);
                    $dataBarang = $refBarang->getValue();
                    
                    if ($dataBarang) {
                        $stok_tersedia = $dataBarang['stok_tersedia'] ?? 0;
                        $stok_dipinjam = $dataBarang['stok_dipinjam'] ?? 0;
                        
                        $refBarang->update([
                            'stok_tersedia' => $stok_tersedia + $qty_kembali,
                            'stok_dipinjam' => max(0, $stok_dipinjam - $qty_kembali)
                        ]);
                    }
                }
            }
        }

        $refTrx->remove();
        header('Location: history.php?msg=del_success');
        exit();
    }

    // 3. PROSES HAPUS MASSAL (BULAN & TAHUN)
    if ($action === 'bulk_delete') {
        $bulan_target = str_pad($_POST['bulan'], 2, "0", STR_PAD_LEFT);
        $tahun_target = $_POST['tahun'];

        $refSemua = $database->getReference('transaksi');
        $semuaTrx = $refSemua->getValue() ?? [];

        foreach ($semuaTrx as $id => $trx) {
            if (isset($trx['tanggal_pinjam'])) {
                $waktu = strtotime($trx['tanggal_pinjam']);
                $bulan_trx = date('m', $waktu);
                $tahun_trx = date('Y', $waktu);

                // Jika bulan & tahun cocok, eksekusi penghapusan
                if ($bulan_trx === $bulan_target && $tahun_trx === $tahun_target) {
                    
                    // Jika ada data "Sedang Dipinjam" yang ikut terhapus massal, amankan stoknya!
                    if (isset($trx['status']) && $trx['status'] === 'Sedang Dipinjam') {
                        if (isset($trx['barang_dipinjam']) && is_array($trx['barang_dipinjam'])) {
                            foreach ($trx['barang_dipinjam'] as $id_barang => $detail) {
                                $qty_kembali = $detail['qty'] ?? 0;
                                $refBarang = $database->getReference('barang/' . $id_barang);
                                $dataBarang = $refBarang->getValue();
                                
                                if ($dataBarang) {
                                    $stok_tersedia = $dataBarang['stok_tersedia'] ?? 0;
                                    $stok_dipinjam = $dataBarang['stok_dipinjam'] ?? 0;
                                    
                                    $refBarang->update([
                                        'stok_tersedia' => $stok_tersedia + $qty_kembali,
                                        'stok_dipinjam' => max(0, $stok_dipinjam - $qty_kembali)
                                    ]);
                                }
                            }
                        }
                    }
                    
                    $database->getReference('transaksi/' . $id)->remove();
                }
            }
        }
        header('Location: history.php?msg=bulk_success');
        exit();
    }
}
?>