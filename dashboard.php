<?php
/** @var mysqli $conn */
require 'init.php';

// Proteksi Halaman: Jika belum login, tendang ke halaman login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data user yang sedang login (Nama IC dan Role)
$stmt = $conn->prepare("SELECT ic_name, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$role = $user['role'];
$ic_name = htmlspecialchars($user['ic_name']);

// Proses Input Kas (HANYA dieksekusi jika yang login adalah Treasurer)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_kas']) && $role === 'Treasurer') {
    // CSRF Protection
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        die("Request tidak valid. Silakan muat ulang halaman.");
    }

    // Whitelist: hanya INCOME dan EXPENSE yang diizinkan
    $type = $_POST['type'];
    $allowed_types = ['INCOME', 'EXPENSE'];
    if (!in_array($type, $allowed_types, true)) {
        die("Tipe transaksi tidak valid.");
    }

    $amount = (int) preg_replace('/\D/', '', $_POST['amount']);
    $description = trim($_POST['description']);

    if ($amount > 0 && !empty($description)) {
        $insert = $conn->prepare("INSERT INTO kas_transactions (user_id, type, amount, description) VALUES (?, ?, ?, ?)");
        $insert->bind_param("isis", $user_id, $type, $amount, $description);
        $insert->execute();
        
        // Refresh halaman agar form bersih dan saldo terupdate otomatis
        header("Location: dashboard.php?sukses=1");
        exit;
    }
}

// Proses Hapus Kas (HANYA dieksekusi jika yang login adalah Treasurer)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_kas']) && $role === 'Treasurer') {
    // CSRF Protection
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        die("Request tidak valid. Silakan muat ulang halaman.");
    }

    $kas_id = (int)$_POST['kas_id'];
    
    if ($kas_id > 0) {
        $hapus = $conn->prepare("DELETE FROM kas_transactions WHERE id = ?");
        $hapus->bind_param("i", $kas_id);
        $hapus->execute();
        
        // Refresh halaman setelah dihapus
        header("Location: dashboard.php?dihapus=1");
        exit;
    }
}

// Kalkulasi Statistik Kas secara otomatis dari database
$query_stats = mysqli_query($conn, "
    SELECT 
        SUM(CASE WHEN type = 'INCOME' THEN amount ELSE 0 END) as total_masuk,
        SUM(CASE WHEN type = 'EXPENSE' THEN amount ELSE 0 END) as total_keluar
    FROM kas_transactions
");
$stats = mysqli_fetch_assoc($query_stats);
$total_masuk = $stats['total_masuk'] ?? 0;
$total_keluar = $stats['total_keluar'] ?? 0;
$saldo_akhir = $total_masuk - $total_keluar;

// Ambil Data Riwayat Kas (Di-join dengan tabel users untuk menampilkan nama pencatat)
$query_history = mysqli_query($conn, "
    SELECT k.*, u.ic_name 
    FROM kas_transactions k 
    JOIN users u ON k.user_id = u.id 
    ORDER BY k.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kas - Los Santos Street Team</title>
    
    <link rel="stylesheet" href="./style/style.css">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="antialiased">

    <header class="bg-jgrp-header text-white">
        <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold tracking-widest uppercase">Los Santos Street Team</h1>
            <nav class="text-sm font-semibold space-x-4 flex items-center">
                <span class="text-gray-300 mr-4"><i class="fa fa-user"></i> Halo, <?= $ic_name; ?> (<?= htmlspecialchars($role); ?>)</span>
                <a href="logout.php" class="bg-red-700 hover:bg-red-800 text-white px-3 py-1.5 rounded transition"><i class="fa fa-sign-out"></i> Logout</a>
            </nav>
        </div>
    </header>

    <div class="max-w-6xl mx-auto px-4 mt-6">
        
        <div class="text-sm text-gray-500 mb-6 pb-2 border-b border-gray-300">
            <a href="index.php" class="hover:underline"><i class="fa fa-home"></i> Home</a> 
            <i class="fa fa-angle-right mx-2"></i> <span>Dashboard Kas</span>
        </div>

        <?php if(isset($_GET['sukses'])): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 text-sm shadow-sm" role="alert">
            <p><i class="fa fa-check-circle"></i> Transaksi kas berhasil dicatat dan saldo telah diperbarui!</p>
        </div>
        <?php endif; ?>

        <?php if(isset($_GET['dihapus'])): ?>
        <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 text-sm shadow-sm" role="alert">
            <p><i class="fa fa-info-circle"></i> Transaksi kas berhasil dihapus dari riwayat.</p>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="ipsBox mb-0 border-t-4 border-green-500">
                <div class="p-4">
                    <span class="text-gray-500 text-xs font-bold uppercase">Total Pemasukan</span>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1">$<?= number_format($total_masuk, 0, ',', '.'); ?></h3>
                </div>
            </div>
            <div class="ipsBox mb-0 border-t-4 border-red-500">
                <div class="p-4">
                    <span class="text-gray-500 text-xs font-bold uppercase">Total Pengeluaran</span>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1">$<?= number_format($total_keluar, 0, ',', '.'); ?></h3>
                </div>
            </div>
            <div class="ipsBox mb-0 border-t-4 border-[#1e412e] bg-[#edf0f4]">
                <div class="p-4">
                    <span class="text-[#1e412e] text-xs font-bold uppercase">Saldo Kas Saat Ini</span>
                    <h3 class="text-3xl font-extrabold text-[#377453] mt-1">$<?= number_format($saldo_akhir, 0, ',', '.'); ?></h3>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-6">
            
            <div class="w-full md:w-1/3">
                <div class="ipsBox">
                    <div class="ipsBox_header">
                        <span><i class="fa fa-plus-circle"></i> Catat Transaksi</span>
                    </div>
                    <div class="p-4">
                        
                        <?php if ($role === 'Treasurer'): ?>
                            <form action="" method="POST" class="space-y-4">
                                <?= csrf_field(); ?>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Tipe Transaksi</label>
                                    <select name="type" required class="w-full border border-gray-300 p-2 rounded text-sm focus:outline-none focus:border-[#377453]">
                                        <option value="INCOME">Pemasukan (Income)</option>
                                        <option value="EXPENSE">Pengeluaran (Expense)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nominal ($)</label>
                                    <input type="number" name="amount" required placeholder="Contoh: 500" class="w-full border border-gray-300 p-2 rounded text-sm focus:outline-none focus:border-[#377453]">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi / Catatan</label>
                                    <textarea name="description" required rows="3" placeholder="Uang kas mingguan..." class="w-full border border-gray-300 p-2 rounded text-sm focus:outline-none focus:border-[#377453]"></textarea>
                                </div>
                                <button type="submit" name="submit_kas" class="ipsButton_primary w-full"><i class="fa fa-save"></i> Simpan Transaksi</button>
                            </form>
                        <?php else: ?>
                            <div class="text-center py-6 text-gray-500">
                                <i class="fa fa-eye fa-3x mb-3 text-gray-300"></i>
                                <h4 class="font-bold text-gray-700">Mode Transparansi</h4>
                                <p class="text-xs mt-2">Sebagai Member, kamu hanya memiliki akses untuk melihat riwayat arus kas. Hubungi Management atau Treasurer jika ada selisih data.</p>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <div class="w-full md:w-2/3">
                <div class="ipsBox">
                    <div class="ipsBox_header">
                        <span><i class="fa fa-list-alt"></i> Riwayat Arus Kas</span>
                    </div>
                    <div class="overflow-x-auto p-4">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="bg-gray-100 border-b-2 border-gray-200 text-gray-600">
                                    <th class="p-3">Waktu</th>
                                    <th class="p-3">Pencatat</th>
                                    <th class="p-3">Keterangan</th>
                                    <th class="p-3 text-right">Nominal</th>
                                    <?php if ($role === 'Treasurer'): ?>
                                    <th class="p-3 text-center">Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($query_history) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($query_history)): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                                            <td class="p-3 text-gray-500 text-xs whitespace-nowrap">
                                                <?= date('d/m/Y H:i', strtotime($row['created_at'])); ?>
                                            </td>
                                            <td class="p-3 font-semibold text-gray-700">
                                                <i class="fa fa-user-circle-o text-gray-400"></i> <?= htmlspecialchars($row['ic_name']); ?>
                                            </td>
                                            <td class="p-3 text-gray-600">
                                                <?= htmlspecialchars($row['description']); ?>
                                            </td>
                                            <td class="p-3 text-right font-bold <?= $row['TYPE'] == 'INCOME' ? 'text-green-600' : 'text-red-600'; ?>">
                                                <?= $row['TYPE'] == 'INCOME' ? '+' : '-'; ?> $<?= number_format($row['amount'], 0, ',', '.'); ?>
                                            </td>
                                            
                                            <?php if ($role === 'Treasurer'): ?>
                                            <td class="p-3 text-center">
                                                <form action="" method="POST" onsubmit="return confirm('Yakin ingin menghapus transaksi ini?');">
                                                    <?= csrf_field(); ?>
                                                    <input type="hidden" name="kas_id" value="<?= $row['id']; ?>">
                                                    <button type="submit" name="delete_kas" class="text-red-500 hover:text-red-700 transition" title="Hapus Transaksi">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                            <?php endif; ?>

                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?= $role === 'Treasurer' ? '5' : '4'; ?>" class="p-6 text-center text-gray-500">Belum ada riwayat transaksi kas tercatat.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>
</html>