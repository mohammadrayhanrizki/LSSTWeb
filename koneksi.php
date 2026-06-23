<?php
// Sesuaikan dengan pengaturan lokalmu (XAMPP biasanya root dan password kosong)
$host = "localhost";
$user = "root";
$pass = ""; 
$db   = "db_lsst_kas";

// Melakukan koneksi ke MySQL
$conn = mysqli_connect($host, $user, $pass, $db);

// Cek apakah koneksi berhasil
if (!$conn) {
    error_log("DB Connection Failed: " . mysqli_connect_error());
    die("Terjadi kesalahan sistem. Silakan coba lagi nanti.");
}

// Hapus tanda miring ganda di bawah ini jika kamu ingin mengetes koneksi
// echo "Koneksi ke database db_lsst_kas berhasil!";
?>  