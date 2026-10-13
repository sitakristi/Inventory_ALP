const tableBody = document.getElementById('data-peminjaman');

function renderTable() {
    if (!tableBody) return;
    tableBody.innerHTML = '';
    
    if (!dataHistory || dataHistory.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="6" style="text-align: center;">Belum ada data transaksi.</td></tr>';
        return;
    }

    dataHistory.forEach((data) => {
        const id = data.id_transaksi;
        // Menggunakan class badge dari style.css (otomatis support Dark/Light mode)
        const badgeClass = data.status === 'Sedang Dipinjam' ? 'bg-warning' : 'bg-success';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="font-family: monospace; color: var(--primary-color);">${id}</td>
            <td>
                <div style="font-weight: bold;">${data.nama_peminjam || '-'}</div>
                <div style="font-size: 12px; color: var(--text-muted);">${data.email || '-'}</div>
            </td>
            <td>${data.rincian || '-'}</td>
            <td>${data.tanggal_pinjam || '-'}</td>
            <td style="text-align: center;">
                <span class="badge ${badgeClass}">${data.status}</span>
            </td>
            <td style="text-align: center; white-space: nowrap;">
                <button type="button" class="btn-primary" onclick="bukaModalEdit('${encodeURIComponent(JSON.stringify(data))}', '${id}')" style="padding: 6px 12px; font-size: 13px; margin-right: 5px;">
                    <i class="fa-solid fa-pen-to-square"></i> Edit
                </button>
                
                <form action="proses_history.php" method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus transaksi ini?');">
                    <input type="hidden" name="action" value="delete_single">
                    <input type="hidden" name="id_transaksi" value="${id}">
                    <button type="submit" class="btn-danger" style="padding: 6px 10px; font-size: 13px;" title="Hapus">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </form>
            </td>
        `;
        tableBody.appendChild(tr);
    });
}

// Jalankan render tabel saat file dimuat
renderTable();

// Fungsi Membuka Modal Edit
window.bukaModalEdit = function(encodedData, id) {
    const data = JSON.parse(decodeURIComponent(encodedData));
    
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = data.nama_peminjam || '';
    
    if (document.getElementById('edit_rincian')) {
        document.getElementById('edit_rincian').value = data.rincian || '-';
    }
    if (document.getElementById('edit_tgl_pinjam')) {
        document.getElementById('edit_tgl_pinjam').value = data.tgl_pinjam_raw || '';
    }
    if (document.getElementById('edit_tgl_kembali')) {
        document.getElementById('edit_tgl_kembali').value = data.tgl_kembali_raw || '';
    }
    if (document.getElementById('edit_status')) {
        document.getElementById('edit_status').value = data.status || 'Sedang Dipinjam';
    }
    
    document.getElementById('modalEdit').style.display = 'flex'; 
};

// Fungsi Menutup Modal Edit
window.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
};