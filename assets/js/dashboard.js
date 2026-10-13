const tabelBarangRusak = document.getElementById('data-barang-rusak');
const selectLimit = document.getElementById('limit-barang-rusak');
const tabelPeminjaman = document.getElementById('data-peminjaman');

let teksRekomendasiTerakhir = "";

// 1. FUNGSI PANGGIL AI REKOMENDASI
function mintaRekomendasiAI() {
    const namaAcara = document.getElementById('inputNamaAcara').value;
    const boxHasil = document.getElementById('boxHasilAi');
    const containerTombol = document.getElementById('containerTombolCheckout');

    if (!namaAcara.trim()) {
        alert('Mohon masukkan nama acara terlebih dahulu!');
        return;
    }

    boxHasil.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> AI sedang menganalisis ketersediaan stok di database...';
    containerTombol.style.display = 'none';

    fetch('rekomendasi_ai.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nama_acara: namaAcara })
    })
    .then(response => response.json())
    .then(data => {
        teksRekomendasiTerakhir = data.rekomendasi || 'Tidak ada rekomendasi yang ditemukan.';
        boxHasil.innerText = teksRekomendasiTerakhir;
        containerTombol.style.display = 'block';
    })
    .catch(error => {
        boxHasil.innerText = 'Terjadi kesalahan saat menghubungi server AI.';
        containerTombol.style.display = 'none';
    });
}

// 2. FUNGSI KIRIM REKOMENDASI KE CHECKOUT
function kirimRekomendasiKeCheckout() {
    if (!currentUserEmail) {
        alert("Sesi email tidak ditemukan.");
        return;
    }

    let cart = {};
    let itemDitemukanCount = 0;

    for (let idBarang in katalogBarangFirebase) {
        const item = katalogBarangFirebase[idBarang];
        const namaBarangDb = item.nama ? item.nama.toLowerCase() : "";

        if (namaBarangDb && teksRekomendasiTerakhir.toLowerCase().includes(namaBarangDb)) {
            cart[idBarang] = {
                nama: item.nama,
                qty: 1
            };
            itemDitemukanCount++;
        }
    }

    if (itemDitemukanCount === 0) {
        alert("Sistem belum dapat mencocokkan otomatis barang dari teks AI. Silakan pilih manual di menu Checkout.");
        window.location.href = "checkout.php";
        return;
    }

    const cartKey = "keranjang_" + currentUserEmail;
    localStorage.setItem(cartKey, JSON.stringify(cart));

    alert("Berhasil! " + itemDitemukanCount + " barang rekomendasi telah dimasukkan ke keranjang checkout.");
    window.location.href = "checkout.php";
}

// 3. FUNGSI RENDER TABEL BARANG RUSAK
function renderTabelRusak() {
    if (!tabelBarangRusak) return;
    tabelBarangRusak.innerHTML = '';
    
    if (!dataBarangRusakAll || dataBarangRusakAll.length === 0) {
        tabelBarangRusak.innerHTML = '<tr><td colspan="4" style="text-align: center;">Semua barang dalam kondisi baik.</td></tr>';
        return;
    }

    let limitValue = selectLimit ? selectLimit.value : '5';
    let dataToShow = dataBarangRusakAll;

    if (limitValue !== 'all') {
        const limitNumber = parseInt(limitValue);
        dataToShow = dataBarangRusakAll.slice(0, limitNumber);
    }

    dataToShow.forEach(item => {
        let namaKat = item.kategori;
        if (masterKategori[item.kategori] && masterKategori[item.kategori].nama) {
            namaKat = masterKategori[item.kategori].nama;
        }

        const row = `
            <tr>
                <td>${item.id}</td>
                <td>${item.nama}</td>
                <td>${namaKat}</td>
                <td><span class="badge bg-danger">${item.stok_rusak} Unit</span></td>
            </tr>
        `;
        tabelBarangRusak.innerHTML += row;
    });

    if (limitValue !== 'all' && dataBarangRusakAll.length > parseInt(limitValue)) {
        const sisaData = dataBarangRusakAll.length - parseInt(limitValue);
        tabelBarangRusak.innerHTML += `
            <tr>
                <td colspan="4" style="text-align: center; font-size: 12px; color: var(--text-muted); font-style: italic; padding-top: 10px;">
                    + ${sisaData} barang rusak lainnya disembunyikan.
                </td>
            </tr>`;
    }
}

// 4. FUNGSI RENDER TABEL PEMINJAMAN AKTIF
function renderTabelPeminjaman() {
    if (!tabelPeminjaman) return;
    tabelPeminjaman.innerHTML = '';
    
    if (!dataPeminjamanAll || dataPeminjamanAll.length === 0) {
        tabelPeminjaman.innerHTML = '<tr><td colspan="6" style="text-align: center;">Belum ada transaksi peminjaman aktif.</td></tr>';
        return;
    }
    
    const dataToShow = dataPeminjamanAll.slice(0, 5);

    dataToShow.forEach(trx => {
        const statusLabel = `<span class="badge bg-warning">${trx.status}</span>`;

        const row = `
            <tr>
                <td style="font-family: monospace; color: var(--primary-color);">${trx.id_transaksi}</td>
                <td>
                    <div style="font-weight: bold;">${trx.nama_peminjam}</div>
                    <div style="font-size: 12px; color: var(--text-muted);">${trx.email}</div>
                </td>
                <td style="font-size: 14px;">${trx.rincian}</td>
                <td style="font-size: 14px;">${trx.tanggal_pinjam}</td>
                <td style="font-size: 14px; color: var(--danger-text); font-weight: bold;">${trx.tanggal_kembali}</td>
                <td style="text-align: center;">${statusLabel}</td>
            </tr>
        `;
        tabelPeminjaman.innerHTML += row;
    });

    if (dataPeminjamanAll.length > 5) {
        const sisaData = dataPeminjamanAll.length - 5;
        tabelPeminjaman.innerHTML += `
            <tr>
                <td colspan="6" style="text-align: center; font-size: 12px; color: var(--text-muted); font-style: italic; padding-top: 10px;">
                    + ${sisaData} transaksi aktif lainnya disembunyikan. Silakan cek menu Riwayat Transaksi untuk selengkapnya.
                </td>
            </tr>`;
    }
}

// Jalankan render saat halaman pertama kali dimuat
renderTabelRusak();
renderTabelPeminjaman();

if (selectLimit) {
    selectLimit.addEventListener('change', renderTabelRusak);
}