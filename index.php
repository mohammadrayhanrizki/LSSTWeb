<?php
/** @var mysqli $conn */
require 'init.php';
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Mengambil total member dari database
$query_member = mysqli_query($conn, "SELECT COUNT(id) as total FROM users");
$data_member = mysqli_fetch_assoc($query_member);
$total_member = $data_member['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Los Santos Street Team - Community Portal</title>
    
    <!-- Link CSS Sendiri -->
    <link rel="stylesheet" href="./style/style.css">
    
    <!-- Tailwind via CDN (Hanya untuk layouting Grid/Margin) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- FontAwesome (Untuk icon klasik) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="antialiased">

    <!-- Header Panel -->
    <header class="bg-jgrp-header text-white">
        <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <!-- Logo teks sederhana -->
                <h1 class="text-2xl font-bold tracking-widest uppercase">Los Santos Street Team</h1>
            </div>
            <nav class="text-sm font-semibold space-x-6">
                <a href="index.php" class="hover:text-gray-300"><i class="fa fa-home"></i> Home</a>
                <a href="login.php" class="hover:text-gray-300"><i class="fa fa-sign-in"></i> Sign In</a>
            </nav>
        </div>
    </header>

    <!-- Main Container -->
    <div class="max-w-6xl mx-auto px-4 mt-6">
        
        <!-- Breadcrumb ala Forum -->
        <div class="text-sm text-gray-500 mb-4 pb-2 border-b border-gray-300">
            <i class="fa fa-home"></i> <span>Home</span> <i class="fa fa-angle-right mx-2"></i> <span>Community Portal</span>
        </div>

        <!-- Grid Layout: Main Content (Kiri) & Sidebar (Kanan) -->
        <div class="flex flex-col md:flex-row gap-6">
            
            <!-- Main Content Area -->
            <div class="w-full md:w-3/4">
                
                <!-- Welcome Panel -->
                <div class="ipsBox">
                    <div class="ipsBox_header">
                        <span>Welcome to Los Santos Street Team</span>
                    </div>
                    <div class="p-6 text-sm leading-relaxed text-gray-700">
                        <p class="mb-4">
                            Selamat datang di portal informasi dan pusat manajemen komunitas <strong>LSST</strong>. Kami adalah kelompok otomotif berbasis roleplay yang berfokus pada balap jalanan, modifikasi kendaraan, dan persaudaraan.
                        </p>
                        <p class="mb-6">
                            Website ini difungsikan sebagai wadah transparansi internal kami, khususnya untuk memantau perputaran uang kas faksi secara *real-time*. Seluruh anggota kru terdaftar memiliki hak untuk melihat laporan keuangan tanpa terkecuali.
                        </p>
                        <hr class="my-4 border-gray-200">
                        <div class="flex gap-3">
                            <a href="register.php" class="ipsButton_primary"><i class="fa fa-user-plus"></i> Gabung Kru</a>
                            <a href="login.php" class="ipsButton_secondary"><i class="fa fa-lock"></i> Akses Kas</a>
                        </div>
                    </div>
                </div>

                <!-- Latest Activity Panel (Mockup) -->
                <div class="ipsBox">
                    <div class="ipsBox_header">
                        <span><i class="fa fa-newspaper-o"></i> Pengumuman Terbaru</span>
                    </div>
                    <div class="p-4 text-sm">
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3 border-b border-gray-100 pb-2">
                                <i class="fa fa-bullhorn text-[#377453] mt-1"></i>
                                <div>
                                    <a href="#" class="font-bold text-[#1e412e] hover:underline">Sistem Website Kas LSST Diluncurkan!</a>
                                    <p class="text-xs text-gray-500 mt-1">Oleh <strong>Management</strong> · 23 Juni 2026</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3 border-b border-gray-100 pb-2">
                                <i class="fa fa-calendar text-[#377453] mt-1"></i>
                                <div>
                                    <a href="#" class="font-bold text-[#1e412e] hover:underline">Jadwal Car Meet Mingguan - Idlewood</a>
                                    <p class="text-xs text-gray-500 mt-1">Oleh <strong>Management</strong> · 20 Juni 2026</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>

            <!-- Sidebar Area -->
            <div class="w-full md:w-1/4">
                
                <!-- Status Widget -->
                <div class="ipsBox">
                    <div class="ipsBox_header">
                        <span>Informasi</span>
                    </div>
                    <div class="p-4 text-sm">
                        <div class="mb-3">
                            <span class="block text-gray-500 text-xs uppercase font-bold">Status Server</span>
                            <span class="text-green-600 font-bold"><i class="fa fa-check-circle"></i> JGRP Online</span>
                        </div>
                        <div class="mb-3">
                            <span class="block text-gray-500 text-xs uppercase font-bold">Total Member Web</span>
                            <span class="text-gray-800 font-bold"><?= number_format($total_member, 0, ',', '.'); ?> Anggota</span>
                        </div>
                    </div>
                </div>

                <!-- Discord Widget -->
                <div class="ipsBox border-l-4 border-[#5865F2]">
                    <div class="p-4 text-sm text-center">
                        <i class="fa fa-discord fa-3x text-[#5865F2] mb-2"></i>
                        <p class="font-bold text-gray-700 mb-3">Join Discord LSST</p>
                        <a href="https://bit.ly/LSSTWeb" target="_blank" class="text-[#5865F2] hover:underline text-xs">Klik di sini untuk bergabung</a>
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