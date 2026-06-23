<?php
/** @var mysqli $conn */
require 'init.php';

// Mengambil total member dari database
$query_member = mysqli_query($conn, "SELECT COUNT(id) as total FROM users");
$data_member = mysqli_fetch_assoc($query_member);
$total_member = $data_member['total'] ?? 0;

// Mengambil member yang sedang online (aktivitas 5 menit terakhir)
$query_online = mysqli_query($conn, "SELECT COUNT(id) as total_online FROM users WHERE last_activity >= NOW() - INTERVAL 5 MINUTE");
$data_online = mysqli_fetch_assoc($query_online);
$total_online = $data_online['total_online'] ?? 0;
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
                <a href="index.php" class="hover:text-gray-300 transition block">
                    <h1 class="text-xl md:text-2xl font-bold tracking-widest uppercase truncate">
                        <span class="md:hidden">LSST</span>
                        <span class="hidden md:inline">LOS SANTOS STREET TEAM</span>
                    </h1>
                </a>
            </div>
            <nav class="text-sm font-semibold space-x-3 md:space-x-6">
                <a href="index.php" class="hover:text-gray-300"><i class="fa fa-home"></i><span class="hidden sm:inline"> Home</span></a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="logout.php" class="hover:text-red-400 text-red-500"><i class="fa fa-sign-out"></i><span class="hidden sm:inline"> Logout</span></a>
                <?php else: ?>
                    <a href="login.php" class="hover:text-gray-300"><i class="fa fa-sign-in"></i><span class="hidden sm:inline"> Sign In</span></a>
                <?php endif; ?>
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
                            <a href="dashboard.php" class="ipsButton_secondary"><i class="fa fa-book"></i> Akses Kas</a>
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
                        <div class="mb-3">
                            <span class="block text-gray-500 text-xs uppercase font-bold">Sedang Online</span>
                            <span class="text-[#377453] font-bold"><i class="fa fa-circle text-xs text-green-500 shadow-green-500/50 drop-shadow-md"></i> <?= number_format($total_online, 0, ',', '.'); ?> Member</span>
                        </div>
                    </div>
                </div>

                <!-- Discord Widget -->
                <div class="ipsBox border-l-4 border-[#5865F2]">
                    <div class="p-4 text-sm text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-3 text-[#5865F2]" fill="currentColor" viewBox="0 0 127.14 96.36"><path d="M107.7,8.07A105.15,105.15,0,0,0,81.47,0a72.06,72.06,0,0,0-3.36,6.83A97.68,97.68,0,0,0,49,6.83,72.37,72.37,0,0,0,45.64,0,105.89,105.89,0,0,0,19.39,8.09C2.79,32.65-1.71,56.6.54,80.21h0A105.73,105.73,0,0,0,32.71,96.36,77.7,77.7,0,0,0,39.6,85.25a68.42,68.42,0,0,1-10.85-5.18c.91-.66,1.8-1.34,2.66-2a75.57,75.57,0,0,0,64.32,0c.87.71,1.76,1.39,2.66,2a67.55,67.55,0,0,1-10.87,5.19,77,77,0,0,0,6.89,11.1A105.25,105.25,0,0,0,126.6,80.22h0C129.24,52.84,122.09,29.11,107.7,8.07ZM42.45,65.69C36.18,65.69,31,60,31,53s5-12.74,11.43-12.74S54,46,53.89,53,48.84,65.69,42.45,65.69Zm42.24,0C78.41,65.69,73.31,60,73.31,53s5-12.74,11.43-12.74S96.3,46,96.19,53,91.08,65.69,84.69,65.69Z"/></svg>
                        <p class="font-bold text-gray-700 mb-3">Join Discord LSST</p>
                        <a href="https://bit.ly/LSSTWeb" target="_blank" class="inline-flex items-center gap-1 bg-[#5865F2] hover:bg-[#4752C4] text-white px-4 py-2 rounded-md font-semibold transition text-xs shadow-md">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 127.14 96.36"><path d="M107.7,8.07A105.15,105.15,0,0,0,81.47,0a72.06,72.06,0,0,0-3.36,6.83A97.68,97.68,0,0,0,49,6.83,72.37,72.37,0,0,0,45.64,0,105.89,105.89,0,0,0,19.39,8.09C2.79,32.65-1.71,56.6.54,80.21h0A105.73,105.73,0,0,0,32.71,96.36,77.7,77.7,0,0,0,39.6,85.25a68.42,68.42,0,0,1-10.85-5.18c.91-.66,1.8-1.34,2.66-2a75.57,75.57,0,0,0,64.32,0c.87.71,1.76,1.39,2.66,2a67.55,67.55,0,0,1-10.87,5.19,77,77,0,0,0,6.89,11.1A105.25,105.25,0,0,0,126.6,80.22h0C129.24,52.84,122.09,29.11,107.7,8.07ZM42.45,65.69C36.18,65.69,31,60,31,53s5-12.74,11.43-12.74S54,46,53.89,53,48.84,65.69,42.45,65.69Zm42.24,0C78.41,65.69,73.31,60,73.31,53s5-12.74,11.43-12.74S96.3,46,96.19,53,91.08,65.69,84.69,65.69Z"/></svg>
                            Klik di sini untuk bergabung
                        </a>
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