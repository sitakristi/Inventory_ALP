document.addEventListener('DOMContentLoaded', function() {
    const cartKey = "keranjang_" + userEmail;
    let cart = JSON.parse(localStorage.getItem(cartKey)) || {};
    
    const tabelKatalog = document.getElementById('tabel-katalog');
    const filterKategori = document.getElementById('filter-kategori');
    const cartCountEl = document.getElementById('cart-count');

    // 1. Isi Dropdown Kategori
    if (masterKategori && filterKategori) {
        for (const idKat in masterKategori) {
            const namaKat = masterKategori[idKat].nama || idKat;
            filterKategori.innerHTML += `<option value="${idKat}">${namaKat}</option>`;
        }
    }

    // 2. Fungsi Update Angka di Ikon Keranjang
    function updateCartBadge() {
        if (!cartCountEl) return;
        let totalItems = 0;
        for (let id in cart) {
            totalItems += cart[id].qty;
        }
        cartCountEl.innerText = totalItems;
    }

    // 3. Render Tabel Barang
    function renderKatalog() {
        if (!tabelKatalog) return;
        tabelKatalog.innerHTML = '';
        let hasData = false;
        const valKategori = filterKategori ? filterKategori.value : 'all';

        if (allBarangData) {
            for (const idBarang in allBarangData) {
                const item = allBarangData[idBarang];
                const tersedia = Number(item.stok_tersedia) || 0;
                const matchKategori = (valKategori === 'all' || item.kategori === valKategori);
                
                // Hanya tampilkan jika stok tersedia > 0 dan sesuai filter
                if (tersedia > 0 && matchKategori) {
                    hasData = true;
                    let namaKategoriText = item.kategori;
                    if (masterKategori && masterKategori[item.kategori]) {
                        namaKategoriText = masterKategori[item.kategori].nama;
                    }

                    // Amankan nama barang jika mengandung tanda kutip
                    const safeNama = (item.nama || '').replace(/'/g, "\\'");

                    const row = `
                        <tr>
                            <td style="font-weight: 500;">${item.nama}</td>
                            <td>${namaKategoriText}</td>
                            <td style="text-align: center;">
                                <span class="badge bg-success">${tersedia} Unit</span>
                            </td>
                            <td style="text-align: center;">
                                <input type="number" id="qty_${idBarang}" class="form-control" value="1" min="1" max="${tersedia}" style="width: 75px; padding: 6px; text-align: center; margin: 0 auto;">
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-primary" onclick="addToCart('${idBarang}', '${safeNama}', ${tersedia})" style="padding: 6px 12px; font-size: 13px;">
                                    <i class="fa-solid fa-plus"></i> Tambah
                                </button>
                            </td>
                        </tr>
                    `;
                    tabelKatalog.innerHTML += row;
                }
            }
        }

        if (!hasData) {
            tabelKatalog.innerHTML = '<tr><td colspan="5" style="text-align: center;">Tidak ada barang yang tersedia di kategori ini.</td></tr>';
        }
    }

    // 4. Fungsi Menambah ke Keranjang (Global Scope)
    window.addToCart = function(id, nama, maxTersedia) {
        const qtyInput = document.getElementById(`qty_${id}`);
        const qty = parseInt(qtyInput.value);
        
        if (isNaN(qty) || qty < 1) {
            alert("Jumlah harus minimal 1.");
            return;
        }

        let currentQtyInCart = cart[id] ? cart[id].qty : 0;
        
        if (currentQtyInCart + qty > maxTersedia) {
            alert(`Gagal! Total "${nama}" di keranjang Anda melebihi stok tersedia (${maxTersedia}).`);
            return;
        }
        
        if (cart[id]) {
            cart[id].qty += qty;
            cart[id].max = maxTersedia;
        } else {
            cart[id] = { nama: nama, qty: qty, max: maxTersedia };
        }
        
        localStorage.setItem(cartKey, JSON.stringify(cart));
        updateCartBadge();
        
        qtyInput.value = 1;
        
        // Efek animasi pada badge keranjang
        if (cartCountEl) {
            cartCountEl.style.transform = 'scale(1.5)';
            setTimeout(() => cartCountEl.style.transform = 'scale(1)', 200);
        }
    };

    // Panggilan Awal
    renderKatalog();
    updateCartBadge();

    if (filterKategori) {
        filterKategori.addEventListener('change', renderKatalog);
    }
});