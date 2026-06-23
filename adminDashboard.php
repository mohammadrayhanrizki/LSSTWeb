<?php
/** @var mysqli $conn */
require 'init.php';

// Proteksi Halaman: Jika belum login atau BUKAN Admin, tendang ke dashboard
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data user yang sedang login
$stmt = $conn->prepare("SELECT ic_name, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$role = $user['role'];
$ic_name = htmlspecialchars($user['ic_name']);

if ($role !== 'Admin') {
    header("Location: dashboard.php");
    exit;
}

// Proses Ubah Role User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_role'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) die("Request tidak valid.");

    $target_user_id = (int)$_POST['target_user_id'];
    $new_role = $_POST['new_role'];
    
    // Pastikan tidak bisa mengubah diri sendiri (mencegah lockout)
    if ($target_user_id === $user_id) {
        $_SESSION['swal_error'] = "Anda tidak bisa mengubah peran Anda sendiri!";
    } elseif (in_array($new_role, ['Member', 'Treasurer', 'Admin'], true)) {
        $update = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $update->bind_param("si", $new_role, $target_user_id);
        $update->execute();
        
        $_SESSION['swal_success'] = "Peran user berhasil diperbarui!";
    }
    header("Location: admin_dashboard.php");
    exit;
}

// Ambil Data Semua User
$query_users = mysqli_query($conn, "SELECT id, email, ic_name, ooc_name, role, created_at FROM users ORDER BY created_at DESC");

// Ambil Data Audit Log
$query_logs = mysqli_query($conn, "
    SELECT l.*, u.ic_name as deleter_name 
    FROM admin_logs l 
    JOIN users u ON l.deleted_by = u.id 
    ORDER BY l.deleted_at DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Los Santos Street Team</title>
    <link rel="stylesheet" href="./style/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="antialiased">

    <header class="bg-jgrp-header text-white">
        <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold tracking-widest uppercase truncate">LSST - Admin</h1>
            <nav class="text-sm font-semibold space-x-4 flex items-center">
                <a href="dashboard.php" class="text-gray-300 hover:text-white mr-2"><i class="fa fa-arrow-left"></i> Kembali ke Kas</a>
                <span class="text-yellow-400 mr-4 hidden md:inline"><i class="fa fa-user-secret"></i> <?= $ic_name; ?> (Admin)</span>
                <a href="logout.php" class="bg-red-700 hover:bg-red-800 text-white px-3 py-1.5 rounded transition"><i class="fa fa-sign-out"></i> Logout</a>
            </nav>
        </div>
    </header>

    <div class="max-w-6xl mx-auto px-4 mt-6">
        
        <div class="flex justify-between items-end mb-6 pb-2 border-b border-gray-300">
            <div class="text-sm text-gray-500">
                <a href="index.php" class="hover:underline"><i class="fa fa-home"></i> Home</a> 
                <i class="fa fa-angle-right mx-2"></i> <span>Panel Admin</span>
            </div>
        </div>

        <div class="flex flex-col gap-8">
            
            <!-- Manajemen User -->
            <div class="w-full">
                <div class="ipsBox">
                    <div class="ipsBox_header bg-[#1e412e]">
                        <span><i class="fa fa-users"></i> Manajemen Pengguna (Role)</span>
                    </div>
                    <div class="overflow-x-auto p-4">
                        <table class="w-full text-left border-collapse text-sm min-w-max">
                            <thead>
                                <tr class="bg-gray-100 border-b-2 border-gray-200 text-gray-600">
                                    <th class="p-3">Bergabung</th>
                                    <th class="p-3">Karakter IC</th>
                                    <th class="p-3">OOC Name</th>
                                    <th class="p-3">Email</th>
                                    <th class="p-3">Role Saat Ini</th>
                                    <th class="p-3 text-center">Aksi Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($query_users) > 0): ?>
                                    <?php while ($r = mysqli_fetch_assoc($query_users)): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                                            <td class="p-3 text-gray-500 text-xs whitespace-nowrap">
                                                <?= date('d/m/Y', strtotime($r['created_at'])); ?>
                                            </td>
                                            <td class="p-3 font-semibold text-gray-700">
                                                <?= htmlspecialchars($r['ic_name']); ?>
                                            </td>
                                            <td class="p-3 text-gray-600"><?= htmlspecialchars($r['ooc_name']); ?></td>
                                            <td class="p-3 text-gray-500 text-xs"><?= htmlspecialchars($r['email']); ?></td>
                                            <td class="p-3 font-bold <?= $r['role'] == 'Admin' ? 'text-yellow-600' : ($r['role'] == 'Treasurer' ? 'text-blue-600' : 'text-gray-500'); ?>">
                                                <?= htmlspecialchars($r['role']); ?>
                                            </td>
                                            <td class="p-3 text-center">
                                                <?php if ($r['id'] !== $user_id): ?>
                                                <form action="" method="POST" class="inline-flex items-center gap-2">
                                                    <?= csrf_field(); ?>
                                                    <input type="hidden" name="target_user_id" value="<?= $r['id']; ?>">
                                                    <select name="new_role" class="border border-gray-300 p-1 rounded text-xs focus:outline-none">
                                                        <option value="Member" <?= $r['role'] == 'Member' ? 'selected' : ''; ?>>Member</option>
                                                        <option value="Treasurer" <?= $r['role'] == 'Treasurer' ? 'selected' : ''; ?>>Treasurer</option>
                                                        <option value="Admin" <?= $r['role'] == 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                                    </select>
                                                    <button type="submit" name="update_role" class="bg-[#377453] hover:bg-[#1e412e] text-white px-2 py-1 rounded text-xs transition" title="Update Role">
                                                        <i class="fa fa-check"></i>
                                                    </button>
                                                </form>
                                                <?php else: ?>
                                                <span class="text-xs text-gray-400 italic">Anda sendiri</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Audit Log Transaksi Terhapus -->
            <div class="w-full mb-8">
                <div class="ipsBox">
                    <div class="ipsBox_header bg-red-800">
                        <span><i class="fa fa-history"></i> Audit Log: Penghapusan Kas</span>
                    </div>
                    <div class="overflow-x-auto p-4">
                        <table class="w-full text-left border-collapse text-sm min-w-max">
                            <thead>
                                <tr class="bg-gray-100 border-b-2 border-gray-200 text-gray-600">
                                    <th class="p-3">Waktu Dihapus</th>
                                    <th class="p-3 text-red-700">Dihapus Oleh</th>
                                    <th class="p-3">Data Asli (Tipe)</th>
                                    <th class="p-3">Data Asli (Keterangan)</th>
                                    <th class="p-3 text-right">Nominal Asli</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($query_logs) > 0): ?>
                                    <?php while ($log = mysqli_fetch_assoc($query_logs)): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                                            <td class="p-3 text-gray-500 text-xs whitespace-nowrap">
                                                <?= date('d/m/Y H:i', strtotime($log['deleted_at'])); ?>
                                            </td>
                                            <td class="p-3 font-semibold text-red-600">
                                                <i class="fa fa-user-circle-o"></i> <?= htmlspecialchars($log['deleter_name']); ?>
                                            </td>
                                            <td class="p-3 font-bold <?= $log['original_type'] == 'INCOME' ? 'text-green-600' : 'text-red-600'; ?>">
                                                <?= $log['original_type']; ?>
                                            </td>
                                            <td class="p-3 text-gray-500 italic">
                                                "<?= htmlspecialchars($log['original_description']); ?>"
                                            </td>
                                            <td class="p-3 text-right font-bold text-gray-600">
                                                $<?= number_format($log['original_amount'], 0, ',', '.'); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="p-6 text-center text-gray-500">Belum ada aktivitas penghapusan transaksi kas.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Notifikasi Sukses
        <?php if (isset($_SESSION['swal_success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= $_SESSION['swal_success']; ?>',
                confirmButtonColor: '#377453'
            });
            <?php unset($_SESSION['swal_success']); ?>
        <?php endif; ?>

        // Notifikasi Error
        <?php if (isset($_SESSION['swal_error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Akses Ditolak!',
                text: '<?= $_SESSION['swal_error']; ?>',
                confirmButtonColor: '#d33'
            });
            <?php unset($_SESSION['swal_error']); ?>
        <?php endif; ?>
    </script>
</body>
</html>
