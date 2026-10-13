<?php
session_start();
if (!isset($_SESSION['user_email'])) {
    header('Location: index.php');
    exit();
}

include 'firebase_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. LOGIKA DELETE BARANG
    if ($action === 'delete') {
        $kode = trim($_POST['kode'] ?? '');
        try {
            $database->getReference('barang/' . $kode)->remove();
            echo "<script>alert('Data barang berhasil dihapus!');</script>";
        } catch (Exception $e) {
            $errMsg = addslashes($e->getMessage());
            echo "<script>alert('Error Hapus: {$errMsg}');</script>";
        }
    }
    
    // 2. LOGIKA TAMBAH KATEGORI
    if ($action === 'add_kategori') {
        $id_kategori = trim($_POST['id_kategori'] ?? '');
        $nama_kategori = trim($_POST['nama_kategori'] ?? '');
        
        if (!empty($id_kategori) && !empty($nama_kategori)) {
            try {
                $database->getReference('kategori/' . $id_kategori)->set(['nama' => $nama_kategori]);
                echo "<script>alert('Kategori baru berhasil ditambahkan!');</script>";
            } catch (Exception $e) {
                $errMsg = addslashes($e->getMessage());
                echo "<script>alert('Error Kategori: {$errMsg}');</script>";
            }
        }
    }

    // 3. LOGIKA EDIT KATEGORI
    if ($action === 'edit_kategori') {
        $id_kategori = trim($_POST['id_kategori'] ?? '');
        $nama_kategori = trim($_POST['nama_kategori'] ?? '');
        
        if (!empty($id_kategori) && !empty($nama_kategori)) {
            try {
                $database->getReference('kategori/' . $id_kategori)->update(['nama' => $nama_kategori]);
                echo "<script>alert('Nama kategori berhasil diperbarui!');</script>";
            } catch (Exception $e) {
                $errMsg = addslashes($e->getMessage());
                echo "<script>alert('Error Edit Kategori: {$errMsg}');</script>";
            }
        }
    }

    // 4. LOGIKA HAPUS KATEGORI
    if ($action === 'delete_kategori') {
        $id_kategori = trim($_POST['id_kategori'] ?? '');
        try {
            $database->getReference('kategori/' . $id_kategori)->remove();
            echo "<script>alert('Kategori berhasil dihapus!');</script>";
        } catch (Exception $e) {
            $errMsg = addslashes($e->getMessage());
            echo "<script>alert('Error Hapus Kategori: {$errMsg}');</script>";
        }
    }

    // 5. LOGIKA ADD & EDIT BARANG
    if ($action === 'add' || $action === 'edit') {
        $kode = trim($_POST['kode'] ?? '');
        $nama = trim($_POST['nama'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $stok_total = (int)($_POST['stok_total'] ?? 0);
        $stok_rusak = (int)($_POST['stok_rusak'] ?? 0);
        $stok_dipinjam = (int)($_POST['stok_dipinjam'] ?? 0);
        
        $stok_tersedia = $stok_total - $stok_rusak - $stok_dipinjam;
        
        if ($stok_rusak + $stok_dipinjam > $stok_total) {
            echo "<script>alert('Gagal! Jumlah barang rusak + dipinjam melebihi Stok Total fisik.');</script>";
        } else {
            $dataBaru = [
                'nama' => $nama,
                'kategori' => $kategori,
                'stok_total' => $stok_total,
                'stok_rusak' => $stok_rusak,
                'stok_dipinjam' => $stok_dipinjam,
                'stok_tersedia' => $stok_tersedia
            ];
            
            try {
                if ($action === 'add') {
                    $database->getReference('barang/' . $kode)->set($dataBaru);
                    echo "<script>alert('Barang baru berhasil ditambahkan!');</script>";
                } else if ($action === 'edit') {
                    $database->getReference('barang/' . $kode)->update($dataBaru);
                    echo "<script>alert('Data barang berhasil diperbarui!');</script>";
                }
            } catch (Exception $e) {
                $errMsg = addslashes($e->getMessage());
                echo "<script>alert('Error Firebase: {$errMsg}');</script>";
            }
        }
    }
}

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
    <title>List Product & Kategori</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/addproduct.css">
</head>
<body>

    <div id="sidebar-container"></div>

    <main class="main-content">
        <div id="topbar-container"></div>
        <section class="content-body">

            <!-- SHAPE 1: AREA MANAJEMEN KATEGORI -->
            <div class="table-container" style="margin-bottom: 20px;">
                <h2 class="table-title">Manajemen Kategori</h2>
                
                <form method="POST" action="" style="display: flex; gap: 15px; align-items: flex-end; margin-bottom: 15px; flex-wrap: wrap;">
                    <input type="hidden" name="action" value="add_kategori">
                    <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                        <label>ID Kategori</label>
                        <input type="text" name="id_kategori" class="form-control" placeholder="Contoh: audio_01" required>
                    </div>
                    <div class="form-group" style="flex: 2; min-width: 250px; margin-bottom: 0;">
                        <label>Nama Kategori</label>
                        <input type="text" name="nama_kategori" class="form-control" placeholder="Contoh: Peralatan Audio" required>
                    </div>
                    <button type="submit" class="btn-submit" style="height: 40px;">Tambah Kategori</button>
                </form>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 25%;">ID Kategori</th>
                                <th style="width: 55%;">Nama Kategori</th>
                                <th style="width: 20%; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dataKategoriRaw)): ?>
                                <tr><td colspan="3" style="text-align: center;">Belum ada kategori.</td></tr>
                            <?php else: ?>
                                <?php foreach($dataKategoriRaw as $idKat => $kat): ?>
                                <tr>
                                    <td style="font-family: monospace; color: var(--primary-color);"><?php echo htmlspecialchars($idKat); ?></td>
                                    <td><?php echo htmlspecialchars($kat['nama'] ?? $idKat); ?></td>
                                    <td style="text-align: center; white-space: nowrap;">
                                        <button type="button" class="btn-primary btn-edit-kat" 
                                            data-id="<?php echo htmlspecialchars($idKat); ?>" 
                                            data-nama="<?php echo htmlspecialchars($kat['nama'] ?? $idKat); ?>" 
                                            style="padding: 6px 12px; font-size: 13px; margin-right: 5px;" title="Edit Kategori">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </button>
                                        <form method="POST" action="" onsubmit="return confirm('Yakin ingin menghapus kategoriini?');" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_kategori">
                                            <input type="hidden" name="id_kategori" value="<?php echo htmlspecialchars($idKat); ?>">
                                            <button type="submit" class="btn-danger" style="padding: 6px 10px; font-size: 13px;" title="Hapus Kategori">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- SHAPE 2: Area Form (Create Barang) -->
            <div class="table-container" style="margin-bottom: 20px;">
                <h2 class="table-title">Tambah Barang Baru</h2>
                <form id="form-add" class="form-grid" method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label>Kode Barang (ID)</label>
                        <input type="text" id="add_kode" name="kode" class="form-control" required placeholder="Contoh: mic_05">
                    </div>
                    <div class="form-group">
                        <label>Nama Barang</label>
                        <input type="text" id="add_nama" name="nama" class="form-control" required placeholder="Contoh: Shure SM58">
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <select id="add_kategori" name="kategori" class="form-control" required>
                            <option value="">Pilih kategori...</option>
                            <?php foreach($dataKategoriRaw as $idKat => $kat): ?>
                                <option value="<?php echo htmlspecialchars($idKat); ?>"><?php echo htmlspecialchars($kat['nama'] ?? $idKat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Stok Total</label>
                            <input type="number" id="add_stok_total" name="stok_total" class="form-control" required min="0" value="0">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Stok Rusak</label>
                            <input type="number" id="add_stok_rusak" name="stok_rusak" class="form-control" required min="0" value="0">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Tambah Barang</button>
                    </div>
                </form>
            </div>

            <!-- SHAPE 3: Area Tabel (Read & Edit Barang) -->
            <div class="table-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                    <h2 class="table-title" style="margin-bottom: 0;">Daftar Semua Barang</h2>
                    
                    <div style="display: flex; gap: 10px;">
                        <select id="filter-kategori" class="form-control" style="width: auto; padding: 6px 12px;">
                            <option value="all">Semua Kategori</option>
                        </select>
                        <select id="filter-kondisi" class="form-control" style="width: auto; padding: 6px 12px;">
                            <option value="all">Semua Kondisi</option>
                            <option value="baik">Baik</option>
                            <option value="rusak">Rusak</option>
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

    <!-- POPUP UNTUK EDIT KATEGORI -->
    <div id="editKategoriModal" class="modal">
        <div class="modal-content table-container">
            <span class="close-modal-kat">&times;</span>
            <h2 class="table-title">Edit Kategori</h2>
            <form method="POST" action="" class="form-grid">
                <input type="hidden" name="action" value="edit_kategori">
                <div class="form-group">
                    <label>ID Kategori (Terkunci)</label>
                    <input type="text" id="edit_kat_id" name="id_kategori" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label>Nama Kategori Baru</label>
                    <input type="text" id="edit_kat_nama" name="nama_kategori" class="form-control" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit">Update Kategori</button>
                </div>
            </form>
        </div>
    </div>

    <!-- POPUP UNTUK EDIT BARANG -->
    <div id="editModal" class="modal">
        <div class="modal-content table-container">
            <span class="close-modal">&times;</span>
            <h2 class="table-title" id="edit-title">Edit Barang</h2>
            <form id="form-edit" class="form-grid" method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_stok_dipinjam" name="stok_dipinjam">
                <div class="form-group">
                    <label>Kode Barang (ID - Terkunci)</label>
                    <input type="text" id="edit_kode" name="kode" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label>Nama Barang</label>
                    <input type="text" id="edit_nama" name="nama" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <select id="edit_kategori" name="kategori" class="form-control" required>
                    </select>
                </div>
                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Stok Total</label>
                        <input type="number" id="edit_stok_total" name="stok_total" class="form-control" required min="0">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Stok Rusak</label>
                        <input type="number" id="edit_stok_rusak" name="stok_rusak" class="form-control" required min="0">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit">Update Data</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Form Tersembunyi untuk Delete Barang -->
    <form id="form-delete" method="POST" action="" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" id="delete_kode" name="kode">
    </form>

    <script src="assets/js/layout.js"></script>
    <script>
        const allBarangData = <?php echo empty($jsDataBarang) ? '{}' : $jsDataBarang; ?>;
        const masterKategori = <?php echo empty($jsMasterKategori) ? '{}' : $jsMasterKategori; ?>;
    </script>
    <script src="assets/js/addproduct.js"></script>
</body>
</html>