document.addEventListener('DOMContentLoaded', function() {
    const tabelBarang = document.getElementById('tabel-semua-barang');
    const filterKategori = document.getElementById('filter-kategori');
    const filterKondisi = document.getElementById('filter-kondisi');
    const modalEdit = document.getElementById('editModal');
    const btnCloseModal = document.querySelector('.close-modal');

    // 1. Isi Dropdown Filter Kategori
    if (masterKategori && filterKategori) {
        for (const idKat in masterKategori) {
            const namaKat = masterKategori[idKat].nama || idKat;
            filterKategori.innerHTML += `<option value="${idKat}">${namaKat}</option>`;
        }
    }

    // 2. Fungsi Render Tabel
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
                                    Total: ${total} | <strong style="color: var(--danger-text);">Rusak: ${rusak}</strong> | Dipinjam: ${dipinjam}
                                </span>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <button type="button" class="btn-primary btn-edit-rusak" 
                                    data-id="${idBarang}" 
                                    data-nama="${item.nama}" 
                                    data-total="${total}" 
                                    data-rusak="${rusak}"
                                    data-dipinjam="${dipinjam}"
                                    style="padding: 6px 12px; font-size: 13px;">
                                    <i class="fa-solid fa-pen-to-square"></i> Ubah Stok Rusak
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
            // Pasang event listener pada tombol Ubah Stok Rusak
            document.querySelectorAll('.btn-edit-rusak').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('edit_kode').value = this.dataset.id;
                    document.getElementById('edit_nama').value = this.dataset.nama;
                    document.getElementById('edit_stok_rusak').value = this.dataset.rusak;
                    
                    document.getElementById('edit_stok_total').value = this.dataset.total;
                    document.getElementById('edit_stok_dipinjam').value = this.dataset.dipinjam;
                    
                    document.getElementById('edit-title').innerText = "Update Rusak: " + this.dataset.nama;
                    modalEdit.classList.add('show'); 
                });
            });
        }
    }

    renderTabelBarang();

    // Event Listener UI
    if (filterKategori) filterKategori.addEventListener('change', renderTabelBarang);
    if (filterKondisi) filterKondisi.addEventListener('change', renderTabelBarang);
    if (btnCloseModal) btnCloseModal.addEventListener('click', () => modalEdit.classList.remove('show'));
    
    window.addEventListener('click', (e) => {
        if (e.target === modalEdit) modalEdit.classList.remove('show');
    });
});