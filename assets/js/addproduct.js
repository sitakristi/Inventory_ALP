document.addEventListener('DOMContentLoaded', function() {
    const tabelBarang = document.getElementById('tabel-semua-barang');
    const filterKategori = document.getElementById('filter-kategori');
    const filterKondisi = document.getElementById('filter-kondisi');
    const modalEdit = document.getElementById('editModal');
    const btnCloseModal = document.querySelector('.close-modal');
    const editKategori = document.getElementById('edit_kategori');
    
    const modalEditKat = document.getElementById('editKategoriModal');
    const btnCloseModalKat = document.querySelector('.close-modal-kat');

    // 1. Isi Dropdown Kategori untuk Filter & Modal Edit
    if (masterKategori) {
        for (const idKat in masterKategori) {
            const namaKat = masterKategori[idKat].nama || idKat;
            if (filterKategori) filterKategori.innerHTML += `<option value="${idKat}">${namaKat}</option>`;
            if (editKategori) editKategori.innerHTML += `<option value="${idKat}">${namaKat}</option>`;
        }
    }

    // 2. Event Buka & Tutup Modal Edit Kategori
    document.querySelectorAll('.btn-edit-kat').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_kat_id').value = this.dataset.id;
            document.getElementById('edit_kat_nama').value = this.dataset.nama;
            modalEditKat.classList.add('show');
        });
    });

    if (btnCloseModalKat) {
        btnCloseModalKat.addEventListener('click', () => modalEditKat.classList.remove('show'));
    }

    // 3. Render Tabel Barang
    function renderTabelBarang() {
        if (!tabelBarang) return;
        tabelBarang.innerHTML = '';
        let hasData = false;

        const valKategori = filterKategori ? filterKategori.value : 'all';
        const valKondisi = filterKondisi ? filterKondisi.value : 'all';

        if (allBarangData) {
            for (const idBarang in allBarangData) {
                const item = allBarangData[idBarang];
                
                const total = Number(item.stok_total) || 0;
                const rusak = Number(item.stok_rusak) || 0;
                const dipinjam = Number(item.stok_dipinjam) || 0;
                const tersedia = Number(item.stok_tersedia) || 0;
                
                const matchKategori = (valKategori === 'all' || item.kategori === valKategori);
                
                let matchKondisi = true;
                if (valKondisi === 'rusak') matchKondisi = (rusak > 0); 
                else if (valKondisi === 'baik') matchKondisi = (total - rusak > 0); 

                if (matchKategori && matchKondisi) {
                    hasData = true;
                    let namaKategoriText = item.kategori; 
                    if (masterKategori && masterKategori[item.kategori]) {
                        namaKategoriText = masterKategori[item.kategori].nama;
                    }
                    
                    const row = `
                        <tr>
                            <td style="font-family: monospace; color: var(--primary-color);">${idBarang}</td>
                            <td style="font-weight: 500;">${item.nama}</td>
                            <td>${namaKategoriText}</td> 
                            <td>
                                <span style="display:block; font-weight:bold; color:var(--primary-color);">Tersedia: ${tersedia}</span>
                                <span style="font-size:12px; color:var(--text-muted);">
                                    Total: ${total} | Rusak: ${rusak} | Dipinjam: ${dipinjam}
                                </span>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <button type="button" class="btn-primary btn-edit-item" 
                                    data-id="${idBarang}" 
                                    data-nama="${item.nama}" 
                                    data-kategori="${item.kategori}" 
                                    data-total="${total}" 
                                    data-rusak="${rusak}"
                                    data-dipinjam="${dipinjam}"
                                    style="padding: 6px 12px; font-size: 13px; margin-right: 5px;">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>

                                <button type="button" class="btn-danger btn-delete-item" 
                                    data-id="${idBarang}" 
                                    data-nama="${item.nama}" 
                                    style="padding: 6px 10px; font-size: 13px;"
                                    title="Hapus Barang">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    tabelBarang.innerHTML += row;
                }
            }
        }

        if (!hasData) {
            tabelBarang.innerHTML = '<tr><td colspan="5" style="text-align: center;">Tidak ada data yang sesuai.</td></tr>';
        } else {
            // Pasang event listener untuk tombol Edit Barang
            document.querySelectorAll('.btn-edit-item').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('edit_kode').value = this.dataset.id;
                    document.getElementById('edit_nama').value = this.dataset.nama;
                    document.getElementById('edit_kategori').value = this.dataset.kategori;
                    document.getElementById('edit_stok_total').value = this.dataset.total;
                    document.getElementById('edit_stok_rusak').value = this.dataset.rusak;
                    document.getElementById('edit_stok_dipinjam').value = this.dataset.dipinjam;
                    
                    document.getElementById('edit-title').innerText = "Edit: " + this.dataset.nama;
                    modalEdit.classList.add('show'); 
                });
            });

            // Pasang event listener untuk tombol Hapus Barang
            document.querySelectorAll('.btn-delete-item').forEach(btn => {
                btn.addEventListener('click', function() {
                    const idYangDihapus = this.dataset.id;
                    const namaYangDihapus = this.dataset.nama;
                    
                    if (confirm(`Yakin ingin menghapus barang "${namaYangDihapus}" (ID: ${idYangDihapus}) secara permanen?`)) {
                        document.getElementById('delete_kode').value = idYangDihapus;
                        document.getElementById('form-delete').submit();
                    }
                });
            });
        }
    }

    renderTabelBarang();

    if (filterKategori) filterKategori.addEventListener('change', renderTabelBarang);
    if (filterKondisi) filterKondisi.addEventListener('change', renderTabelBarang);
    if (btnCloseModal) btnCloseModal.addEventListener('click', () => modalEdit.classList.remove('show'));
    
    window.addEventListener('click', (e) => {
        if (e.target === modalEdit) modalEdit.classList.remove('show');
        if (e.target === modalEditKat) modalEditKat.classList.remove('show');
    });
});