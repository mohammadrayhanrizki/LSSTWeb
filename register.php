<?php
/** @var mysqli $conn */
require 'init.php';

// Jika pengguna kebetulan sudah login, langsung tendang ke dashboard
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error_msg = '';

// Proses form registrasi saat tombol ditekan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    // CSRF Protection
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        die("Request tidak valid. Silakan muat ulang halaman.");
    }

    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $ic_name = trim($_POST['ic_name']);
    $ooc_name = trim($_POST['ooc_name']);

    // Cek apakah email sudah terdaftar sebelumnya
    $cek_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $cek_email->bind_param("s", $email);
    $cek_email->execute();
    $cek_result = $cek_email->get_result();

    if ($cek_result->num_rows > 0) {
        $error_msg = "Email tersebut sudah digunakan! Silakan gunakan email lain.";
    } else {
        // Keamanan: Enkripsi password menggunakan BCRYPT
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Masukkan data ke database (Role otomatis 'Member' dari bawaan tabel SQL)
        $insert = $conn->prepare("INSERT INTO users (email, password, ic_name, ooc_name) VALUES (?, ?, ?, ?)");
        $insert->bind_param("ssss", $email, $hashed_password, $ic_name, $ooc_name);
        
        if ($insert->execute()) {
            // Jika sukses, arahkan ke halaman login dengan pesan sukses
            header("Location: login.php?registered=1");
            exit;
        } else {
            $error_msg = "Terjadi kesalahan sistem saat mendaftar. Coba lagi nanti.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Los Santos Street Team</title>
    
    <link rel="stylesheet" href="./style/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="antialiased flex flex-col min-h-screen">

    <header class="bg-jgrp-header text-white">
        <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold tracking-widest uppercase">Los Santos Street Team</h1>
            <nav class="text-sm font-semibold space-x-6">
                <a href="index.php" class="hover:text-gray-300"><i class="fa fa-home"></i> Home</a>
                <a href="login.php" class="hover:text-gray-300"><i class="fa fa-sign-in"></i> Sign In</a>
            </nav>
        </div>
    </header>

    <div class="flex-grow flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md ipsBox my-auto">
            <div class="ipsBox_header">
                <span><i class="fa fa-user-plus"></i> Create New Account</span>
            </div>
            <div class="p-6">
                
                <?php if($error_msg): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 mb-5 text-sm" role="alert">
                        <i class="fa fa-exclamation-triangle"></i> <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" class="space-y-4">
                    <?= csrf_field(); ?>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
                        <input type="email" name="email" required placeholder="you@example.com" class="w-full border border-gray-300 p-2.5 rounded text-sm focus:outline-none focus:border-[#377453]">
                    </div>
                    
                    <div class="border-t border-gray-200 pt-4 mt-2">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 block">Informasi Karakter</span>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">In-Character Name (IC)</label>
                        <input type="text" name="ic_name" required placeholder="Contoh: Dominic Toretto" class="w-full border border-gray-300 p-2.5 rounded text-sm focus:outline-none focus:border-[#377453]">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Out-of-Character Name (OOC / Discord)</label>
                        <input type="text" name="ooc_name" required placeholder="Nama asli atau alias" class="w-full border border-gray-300 p-2.5 rounded text-sm focus:outline-none focus:border-[#377453]">
                    </div>
                    
                    <div class="border-t border-gray-200 pt-4 mt-2"></div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" required minlength="6" placeholder="Minimal 6 karakter" class="w-full border border-gray-300 p-2.5 rounded text-sm focus:outline-none focus:border-[#377453]">
                    </div>

                    <button type="submit" name="register" class="ipsButton_primary w-full py-2.5 mt-4"><i class="fa fa-user-plus"></i> Gabung Kru</button>
                </form>

                <div class="mt-6 pt-4 border-t border-gray-100 text-center text-sm text-gray-500">
                    Sudah punya akun? <br>
                    <a href="login.php" class="text-[#377453] hover:underline font-bold mt-1 inline-block">Login sekarang</a>
                </div>
            </div>
        </div>
    </div>

    <footer class="py-6 text-center text-xs text-gray-400 border-t border-gray-200 bg-white">
        &copy; <?php echo date("Y"); ?> Los Santos Street Team.<br>
        Powered by PHP Native & MySQL.
    </footer>

</body>
</html>