<?php
/** @var mysqli $conn */
require 'init.php';

// Jika pengguna kebetulan sudah login, langsung tendang ke dashboard
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error_msg = '';

// Proses form login saat tombol ditekan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    // CSRF Protection
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        die("Request tidak valid. Silakan muat ulang halaman.");
    }

    $email = $_POST['email'];
    $password = $_POST['password'];

    // Cari user berdasarkan email
    $stmt = $conn->prepare("SELECT id, password, ic_name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verifikasi kecocokan password yang diketik dengan hash di database
        if (password_verify($password, $user['password'])) {
            // Regenerasi session ID untuk mencegah session fixation
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            
            // Arahkan ke dashboard
            header("Location: dashboard.php");
            exit;
        } else {
            $error_msg = "Password yang kamu masukkan salah!";
        }
    } else {
        $error_msg = "Akun dengan email tersebut tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Los Santos Street Team</title>
    
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
                <a href="login.php" class="text-gray-300"><i class="fa fa-sign-in"></i> Sign In</a>
            </nav>
        </div>
    </header>

    <div class="flex-grow flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md ipsBox">
            <div class="ipsBox_header">
                <span><i class="fa fa-lock"></i> Existing User Sign In</span>
            </div>
            <div class="p-6">
                
                <?php if($error_msg): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 mb-5 text-sm" role="alert">
                        <i class="fa fa-exclamation-triangle"></i> <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <?php if(isset($_GET['registered'])): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-3 mb-5 text-sm" role="alert">
                        <i class="fa fa-check-circle"></i> Registrasi berhasil! Silakan Sign In dengan akun barumu.
                    </div>
                <?php endif; ?>

                <form action="" method="POST" class="space-y-5">
                    <?= csrf_field(); ?>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
                        <input type="email" name="email" required placeholder="Email kamu" class="w-full border border-gray-300 p-2.5 rounded text-sm focus:outline-none focus:border-[#377453]">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" required placeholder="••••••••" class="w-full border border-gray-300 p-2.5 rounded text-sm focus:outline-none focus:border-[#377453]">
                    </div>
                    <button type="submit" name="login" class="ipsButton_primary w-full py-2.5 mt-2"><i class="fa fa-sign-in"></i> Sign In</button>
                </form>

                <div class="mt-6 pt-4 border-t border-gray-100 text-center text-sm text-gray-500">
                    Belum memiliki akun LSST? <br>
                    <a href="register.php" class="text-[#377453] hover:underline font-bold mt-1 inline-block">Register sekarang</a>
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