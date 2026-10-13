document.addEventListener('DOMContentLoaded', function() {
    const cartKey = "keranjang_" + userEmail;
    let cart = JSON.parse(localStorage.getItem(cartKey)) || {};
    
    const tableBody = document.getElementById('cart-table-body');
    const cartDataInput = document.getElementById('cart_data_input');
    const formCheckout = document.getElementById('form-checkout');
    const btnSubmit = document.getElementById('btn-submit-checkout');
    
    // 1. Set minimal tanggal kembali ke waktu saat ini
    const dateInput = document.getElementById('tanggal_kembali');
    if (dateInput) {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        dateInput.min = now.toISOString().slice(0, 16);
    }

    // 2. Jika transaksi sukses diproses oleh PHP, bersihkan keranjang & arahkan ke katalog
    if (typeof isSuccess !== 'undefined' && isSuccess) {
        localStorage.removeItem(cartKey);
        alert("Transaksi Berhasil! Silakan cek menu Riwayat Transaksi.");
        window.location.href = "product.php";
        return;
    }

    // 3. Render Tabel Keranjang
    function renderCart() {
        if (!tableBody) return;
        tableBody.innerHTML = '';
        let hasItems = false;
        
        for (let idBarang in cart) {
            hasItems = true;
            const item = cart[idBarang];
            
            const row = `
                <tr>
                    <td style="font-family: monospace; color: var(--primary-color);">${idBarang}</td>
                    <td style="font-weight: 500;">${item.nama}</td>
                    <td style="text-align: center; font-weight: bold;">${item.qty}</td>
                    <td style="text-align: center;">
                        <button type="button" class="btn-danger" onclick="removeItem('${idBarang}')" style="padding: 6px 10px; font-size: 13px;" title="Hapus dari keranjang">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </td>
                </tr>
            `;
            tableBody.innerHTML += row;
        }
        
        if (!hasItems) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="4" style="text-align: center; padding: 25px; color: var(--text-muted);">
                        Keranjang masih kosong.<br>
                        <a href="product.php" style="color: var(--primary-color); font-weight: bold; text-decoration: none; display: inline-block; margin-top: 8px;">
                            <i class="fa-solid fa-arrow-left"></i> Pilih Barang di Katalog
                        </a>
                    </td>
                </tr>
            `;
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.style.opacity = '0.5';
                btnSubmit.style.cursor = 'not-allowed';
            }
        } else {
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.style.opacity = '1';
                btnSubmit.style.cursor = 'pointer';
            }
        }
    }
    
    // 4. Hapus Barang dari Keranjang
    window.removeItem = function(id) {
        if (confirm('Hapus barang ini dari daftar pinjam?')) {
            delete cart[id];
            localStorage.setItem(cartKey, JSON.stringify(cart));
            renderCart();
        }
    };
    
    // 5. Masukkan JSON Keranjang ke Input Hidden saat Submit
    if (formCheckout) {
        formCheckout.addEventListener('submit', function(e) {
            if (Object.keys(cart).length === 0) {
                e.preventDefault();
                alert("Keranjang kosong!");
                return;
            }
            cartDataInput.value = JSON.stringify(cart);
        });
    }

    renderCart();
});