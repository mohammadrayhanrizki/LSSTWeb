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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['login_user']) || isset($_POST['login_admin']))) {
    // CSRF Protection
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        die("Request tidak valid. Silakan muat ulang halaman.");
    }

    $email = $_POST['email'];
    $password = $_POST['password'];
    $is_admin_attempt = isset($_POST['login_admin']);

    // Cari user berdasarkan email
    $stmt = $conn->prepare("SELECT id, password, ic_name, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verifikasi kecocokan password yang diketik dengan hash di database
        if (password_verify($password, $user['password'])) {
            // Proteksi: Jika mencoba login di form Admin tapi rolenya bukan Admin
            if ($is_admin_attempt && $user['role'] !== 'Admin') {
                $error_msg = "Akses ditolak! Anda bukan Administrator.";
            } else {
                // Regenerasi session ID untuk mencegah session fixation
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                
                // Arahkan ke dashboard yang sesuai
                if ($user['role'] === 'Admin') {
                    header("Location: adminDashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit;
            }
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
            <a href="index.php" class="hover:text-gray-300 transition block">
                <h1 class="text-xl md:text-2xl font-bold tracking-widest uppercase truncate">
                    <span class="md:hidden">LSST</span>
                    <span class="hidden md:inline">LOS SANTOS STREET TEAM</span>
                </h1>
            </a>
            <nav class="text-sm font-semibold space-x-3 md:space-x-6">
                <a href="index.php" class="hover:text-gray-300"><i class="fa fa-home"></i><span class="hidden sm:inline"> Home</span></a>
                <a href="login.php" class="text-gray-300"><i class="fa fa-sign-in"></i><span class="hidden sm:inline"> Sign In</span></a>
            </nav>
        </div>
    </header>

    <div class="flex-grow flex items-center justify-center px-4 py-6 md:py-12">
        <div class="w-full max-w-4xl">
            
            <!-- Tombol Kembali -->
            <a href="index.php" class="inline-block mb-4 text-gray-500 hover:text-[#1e412e] transition font-semibold text-sm">
                <i class="fa fa-arrow-left mr-1"></i> Kembali ke Beranda
            </a>
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

            <!-- Grid 2 Kolom untuk User dan Admin -->
            <div class="flex flex-col md:flex-row gap-8">
                
                <!-- KOLOM KIRI: USER LOGIN -->
                <div class="w-full md:w-1/2 ipsBox">
                    <div class="ipsBox_header">
                        <span><i class="fa fa-lock"></i> Portal Anggota (Member/Treasurer)</span>
                    </div>
                    <div class="p-6">
                        <form action="" method="POST" class="space-y-5">
                            <?= csrf_field(); ?>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
                                <input type="email" name="email" required placeholder="Email anggota" class="w-full border border-gray-300 p-2.5 rounded text-sm focus:outline-none focus:border-[#377453]">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                                <input type="password" name="password" required placeholder="••••••••" class="w-full border border-gray-300 p-2.5 rounded text-sm focus:outline-none focus:border-[#377453]">
                            </div>
                            <button type="submit" name="login_user" class="ipsButton_primary w-full py-2.5 mt-2"><i class="fa fa-sign-in"></i> Sign In Anggota</button>
                        </form>
                        <div class="mt-6 pt-4 border-t border-gray-100 text-center text-sm text-gray-500">
                            Belum memiliki akun LSST? <br>
                            <a href="register.php" class="text-[#377453] hover:underline font-bold mt-1 inline-block">Register sekarang</a>
                        </div>
                    </div>
                </div>

                <!-- KOLOM KANAN: ADMIN LOGIN -->
                <div class="w-full md:w-1/2 ipsBox border-t-4 border-yellow-500">
                    <div class="ipsBox_header bg-[#1e412e]">
                        <span class="text-yellow-400"><i class="fa fa-shield"></i> Portal Administrator</span>
                    </div>
                    <div class="p-6 bg-gray-50 h-full">
                        <p class="text-xs text-gray-500 mb-5 text-center">Zona khusus untuk management server. Segala bentuk percobaan intrusi ilegal akan dicatat.</p>
                        <form action="" method="POST" class="space-y-5">
                            <?= csrf_field(); ?>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Email Admin</label>
                                <input type="email" name="email" required placeholder="admin@lsst.com" class="w-full border border-gray-300 p-2.5 rounded text-sm focus:outline-none focus:border-yellow-500 bg-white">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Password Admin</label>
                                <input type="password" name="password" required placeholder="••••••••" class="w-full border border-gray-300 p-2.5 rounded text-sm focus:outline-none focus:border-yellow-500 bg-white">
                            </div>
                            <button type="submit" name="login_admin" class="w-full py-2.5 mt-2 bg-yellow-500 hover:bg-yellow-600 text-white font-bold rounded transition shadow"><i class="fa fa-user-secret"></i> Sign In Admin</button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-8 py-6 text-center text-xs text-gray-400 border-t border-gray-200">
        &copy; <?php echo date("Y"); ?> Los Santos Street Team.<br>
        Powered by PHP Native & MySQL.
    </footer>

</body>
</html>