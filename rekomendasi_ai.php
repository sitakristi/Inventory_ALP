<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Hanya user yang sudah login
if (!isset($_SESSION['user_email'])) {
    http_response_code(401);
    echo json_encode(["rekomendasi" => "Sesi habis, silakan login kembali."]);
    exit;
}
session_write_close();

include 'firebase_config.php';

// ================== KONFIGURASI ==================
$apiKey = getenv('GEMINI_API_KEY') ?: '';
if ($apiKey === '' && file_exists(__DIR__ . '/config_ai.php')) {
    $apiKey = require __DIR__ . '/config_ai.php';
}
// Model Gemini
$model = getenv('GEMINI_MODEL') ?: 'gemini-3.5-flash';

$input = json_decode(file_get_contents('php://input'), true);
$namaAcara = trim($input['nama_acara'] ?? '');
$namaAcara = mb_substr($namaAcara !== '' ? $namaAcara : 'Kegiatan Umum', 0, 200);

// ---------- Ambil stok dari Firebase ----------
try {
    $dataBarangRaw = $database->getReference('barang')->getValue() ?? [];
    $dataKategori  = $database->getReference('kategori')->getValue() ?? [];
} catch (\Exception $e) {
    echo json_encode(["rekomendasi" => "Gagal membaca data barang dari database."]);
    exit;
}

$katalog = [];
foreach ($dataBarangRaw as $id => $item) {
    if (!is_array($item)) continue;
    $tersedia = (int)($item['stok_tersedia'] ?? 0);
    if ($tersedia <= 0) continue;
    $namaKat = $dataKategori[$item['kategori'] ?? '']['nama'] ?? ($item['kategori'] ?? '-');
    $katalog[] = "- " . ($item['nama'] ?? $id) . " (kategori: $namaKat, stok tersedia: $tersedia)";
}

if (empty($katalog)) {
    echo json_encode(["rekomendasi" => "Saat ini tidak ada barang yang tersedia untuk dipinjam."]);
    exit;
}
$daftarBarang = implode("\n", $katalog);

// Instruksi dipertegas agar AI tidak memakai simbol markdown bintang (*)
$prompt = "Kamu adalah asisten inventaris kampus. User ingin mengadakan acara: \"$namaAcara\".\n\n"
    . "Daftar barang yang tersedia saat ini:\n$daftarBarang\n\n"
    . "Tugas: rekomendasikan barang dari daftar di atas yang sebaiknya dipinjam beserta jumlah yang masuk akal.\n"
    . "Aturan SANGAT PENTING: JANGAN PERNAH menggunakan simbol bintang (*), garis bawah, atau format markdown bold/italic apa pun di seluruh teks jawaban. Gunakan teks biasa murni.\n"
    . "Hanya pilih barang yang ada di daftar, jumlah tidak boleh melebihi stok tersedia. "
    . "Jawab singkat dalam bahasa Indonesia dengan format paragraf atau poin angka biasa (tanpa tabel).\n"
    . "Jika informasi acara kurang jelas, beri perkiraan umum dan sebutkan asumsinya. "
    . "Pengguna harus meminjam sendiri lewat menu Checkout.";

$url  = "https://generativelanguage.googleapis.com/v1beta/models/" . rawurlencode($model) . ":generateContent";
$data = ["contents" => [["parts" => [["text" => $prompt]]]]];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS     => json_encode($data),
]);

$response  = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    $teks = "Error koneksi: " . $curlError;
} else {
    $result = json_decode($response, true);
    if (isset($result['error'])) {
        $teks = "Error API: " . ($result['error']['message'] ?? 'Kesalahan API Key.');
    } else {
        $teks = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if ($teks === null) {
            $alasan = $result['promptFeedback']['blockReason'] ?? ($result['candidates'][0]['finishReason'] ?? 'tidak diketahui');
            $teks = "AI tidak memberi jawaban (alasan: $alasan). Coba ulangi dengan kalimat lain.";
        } else {
            // BACKUP PENGAMAN: Membersihkan sisa tanda bintang (*) secara otomatis via PHP
            $teks = str_replace(['**', '*'], '', $teks);
        }
    }
}

echo json_encode(["rekomendasi" => $teks], JSON_UNESCAPED_UNICODE);