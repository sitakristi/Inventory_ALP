<?php
session_start();

// Hapus semua variabel sesi
session_unset();

// Hapus sesi
session_destroy();

// Kembalikan pengguna ke halaman login
header("Location: index.php");
exit();
?>