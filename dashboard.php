<?php
/** @var mysqli $conn */
require 'init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$stmt = $conn->prepare("SELECT ic_name, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$role = $user['role'];
$ic_name = htmlspecialchars($user['ic_name']);

// ==========================================
// 1. FITUR EXPORT EXCEL (Khusus Treasurer)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['export_csv']) && in_array($role, ['Treasurer', 'Admin'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        die("Request tidak valid.");
    }

    // Ubah ekstensi menjadi .xls untuk trik HTML to Excel
    $filename = "Laporan_Kas_LSST_" . date('Ymd_His') . ".xls";
    
    // Set header khusus Excel
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    // Render tabel HTML untuk dibaca oleh Excel (agar ada garis rapi)
    ?>
    <table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; font-family: Arial, sans-serif;">
        <!-- Kop Laporan -->
        <tr>
            <th colspan="9" style="font-size:16px; text-align:center; background-color:#f0f0f0;">LAPORAN ARUS KAS - LOS SANTOS STREET TEAM</th>
        </tr>
        <tr>
            <td colspan="9" style="text-align:center; background-color:#f0f0f0;">Tanggal Cetak: <?= date('d/m/Y H:i:s'); ?></td>
        </tr>
        <tr><td colspan="9" style="border:none;"></td></tr>
        
        <!-- Header Kolom (Berwarna dan Tebal) -->
        <tr>
            <th style="background-color:#1e412e; color:white; width: 40px;">No.</th>
            <th style="background-color:#1e412e; color:white; width: 80px;">Tanggal</th>
            <th style="background-color:#1e412e; color:white; width: 60px;">Jam</th>
            <th style="background-color:#1e412e; color:white; width: 150px;">Pencatat (IC)</th>
            <th style="background-color:#1e412e; color:white; width: 120px;">Jenis Transaksi</th>
            <th style="background-color:#1e412e; color:white; width: 300px;">Keterangan</th>
            <th style="background-color:#1e412e; color:white; width: 120px;">Pemasukan ($)</th>
            <th style="background-color:#1e412e; color:white; width: 120px;">Pengeluaran ($)</th>
            <th style="background-color:#1e412e; color:white; width: 150px;">Nominal Bersih ($)</th>
        </tr>
        
        <?php
        $query_export = mysqli_query($conn, "
            SELECT k.id, k.created_at, u.ic_name, k.type, k.amount, k.description 
            FROM kas_transactions k 
            JOIN users u ON k.user_id = u.id 
            ORDER BY k.created_at ASC
        ");
        
        $no = 1;
        $total_masuk = 0;
        $total_keluar = 0;
        
        while ($row = mysqli_fetch_assoc($query_export)) {
            $tgl = date('d/m/Y', strtotime($row['created_at']));
            $jam = date('H:i', strtotime($row['created_at']));
            $jenis = $row['type'] == 'INCOME' ? 'Pemasukan' : 'Pengeluaran';
            
            $pemasukan = $row['type'] == 'INCOME' ? $row['amount'] : 0;
            $pengeluaran = $row['type'] == 'EXPENSE' ? $row['amount'] : 0;
            $nominal_bersih = $row['type'] == 'INCOME' ? $row['amount'] : -$row['amount'];
            
            // Format warna baris zebra
            $bg = ($no % 2 == 0) ? '#f9f9f9' : '#ffffff';
            ?>
            <tr style="background-color: <?= $bg; ?>;">
                <td style="text-align:center;"><?= $no++; ?></td>
                <td style="text-align:center;"><?= $tgl; ?></td>
                <td style="text-align:center;"><?= $jam; ?></td>
                <td><?= htmlspecialchars($row['ic_name']); ?></td>
                <td style="text-align:center; color: <?= $row['type'] == 'INCOME' ? '#377453' : '#d33'; ?>; font-weight:bold;"><?= $jenis; ?></td>
                <td><?= htmlspecialchars($row['description']); ?></td>
                <td style="text-align:right;"><?= $pemasukan; ?></td>
                <td style="text-align:right;"><?= $pengeluaran; ?></td>
                <td style="text-align:right; font-weight:bold; color: <?= $nominal_bersih < 0 ? '#d33' : '#377453'; ?>;"><?= $nominal_bersih; ?></td>
            </tr>
            <?php
            $total_masuk += $pemasukan;
            $total_keluar += $pengeluaran;
        }
        $saldo_akhir = $total_masuk - $total_keluar;
        ?>
        
        <!-- Summary Rows -->
        <tr><td colspan="9" style="border:none;"></td></tr>
        <tr style="background-color:#edf0f4;">
            <td colspan="5" style="border:none;"></td>
            <td style="text-align:right; font-weight:bold;">TOTAL PEMASUKAN:</td>
            <td style="text-align:right; font-weight:bold; color:#377453;"><?= $total_masuk; ?></td>
            <td colspan="2" style="border:none;"></td>
        </tr>
        <tr style="background-color:#edf0f4;">
            <td colspan="5" style="border:none;"></td>
            <td style="text-align:right; font-weight:bold;">TOTAL PENGELUARAN:</td>
            <td style="border:none;"></td>
            <td style="text-align:right; font-weight:bold; color:#d33;"><?= $total_keluar; ?></td>
            <td style="border:none;"></td>
        </tr>
        <tr style="background-color:#e2e8f0;">
            <td colspan="5" style="border:none;"></td>
            <td style="text-align:right; font-weight:bold; font-size:14px;">SALDO AKHIR:</td>
            <td colspan="2" style="border:none;"></td>
            <td style="text-align:right; font-weight:bold; font-size:14px; color: <?= $saldo_akhir < 0 ? '#d33' : '#1e412e'; ?>;"><?= $saldo_akhir; ?></td>
        </tr>
    </table>
    <?php
    exit;
}

// ==========================================
// 2. PROSES INPUT KAS
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_kas']) && in_array($role, ['Treasurer', 'Admin'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) die("Request tidak valid.");

    $type = $_POST['type'];
    if (!in_array($type, ['INCOME', 'EXPENSE'], true)) die("Tipe transaksi tidak valid.");

    $amount = (int) preg_replace('/\D/', '', $_POST['amount']);
    $description = trim($_POST['description']);

    if ($amount > 0 && !empty($description)) {
        $insert = $conn->prepare("INSERT INTO kas_transactions (user_id, type, amount, description) VALUES (?, ?, ?, ?)");
        $insert->bind_param("isis", $user_id, $type, $amount, $description);
        $insert->execute();
        
        // Catat ke activity_logs
        $action_detail = "Menambahkan kas " . ($type == 'INCOME' ? 'Pemasukan' : 'Pengeluaran') . " sebesar $" . number_format($amount, 0, ',', '.') . " (" . $description . ")";
        $log = $conn->prepare("INSERT INTO activity_logs (user_id, action_type, action_detail) VALUES (?, 'CREATE_KAS', ?)");
        $log->bind_param("is", $user_id, $action_detail);
        $log->execute();
        
        // Kirim sinyal sukses untuk SweetAlert
        $_SESSION['swal_success'] = "Transaksi kas berhasil dicatat!";
        header("Location: dashboard.php");
        exit;
    }
}

// ==========================================
// 3. PROSES HAPUS KAS
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_kas']) && in_array($role, ['Treasurer', 'Admin'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) die("Request tidak valid.");

    $kas_id = (int)$_POST['kas_id'];
    if ($kas_id > 0) {
        // Ambil data asli sebelum dihapus
        $cek_data = $conn->prepare("SELECT type, amount, description FROM kas_transactions WHERE id = ?");
        $cek_data->bind_param("i", $kas_id);
        $cek_data->execute();
        $res_data = $cek_data->get_result();
        
        if ($res_data->num_rows > 0) {
            $data_asli = $res_data->fetch_assoc();
            
            // Catat ke activity_logs
            $action_detail = "Menghapus kas " . ($data_asli['type'] == 'INCOME' ? 'Pemasukan' : 'Pengeluaran') . " sebesar $" . number_format($data_asli['amount'], 0, ',', '.') . " (" . $data_asli['description'] . ")";
            $log = $conn->prepare("INSERT INTO activity_logs (user_id, action_type, action_detail) VALUES (?, 'DELETE_KAS', ?)");
            $log->bind_param("is", $user_id, $action_detail);
            $log->execute();
            
            // Hapus dari kas_transactions
            $hapus = $conn->prepare("DELETE FROM kas_transactions WHERE id = ?");
            $hapus->bind_param("i", $kas_id);
            $hapus->execute();
            
            // Kirim sinyal sukses untuk SweetAlert
            $_SESSION['swal_deleted'] = "Transaksi berhasil dihapus dan dicatat di Activity Log.";
        }
        header("Location: dashboard.php");
        exit;
    }
}

// Kalkulasi Statistik Kas
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

// Ambil Data Riwayat
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
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="antialiased">

    <header class="bg-jgrp-header text-white">
        <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-xl md:text-2xl font-bold tracking-widest uppercase truncate">
                <span class="md:hidden">LSST</span>
                <span class="hidden md:inline">LOS SANTOS STREET TEAM</span>
            </h1>
            <nav class="text-sm font-semibold space-x-3 md:space-x-4 flex items-center">
                <span class="text-gray-300 mr-4 hidden md:inline"><i class="fa fa-user"></i> <?= $ic_name; ?> (<?= htmlspecialchars($role); ?>)</span>
                <a href="logout.php" class="bg-red-700 hover:bg-red-800 text-white px-3 py-1.5 rounded transition"><i class="fa fa-sign-out"></i> Logout</a>
            </nav>
        </div>
    </header>

    <!-- Tab Menu Khusus Aplikasi -->
    <div class="bg-white border-b border-gray-200 shadow-sm mb-6">
        <div class="max-w-6xl mx-auto px-4 flex gap-2 overflow-x-auto whitespace-nowrap scrollbar-hide">
            <a href="dashboard.php" class="lsst-tab active"><i class="fa fa-money"></i> Arus Kas</a>
            <?php if ($role === 'Admin'): ?>
            <a href="adminDashboard.php" class="lsst-tab"><i class="fa fa-shield"></i> Panel Administrator</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4">
        
        <div class="flex justify-between items-end mb-6 pb-2 border-b border-gray-300">
            <div class="text-sm text-gray-500">
                <a href="index.php" class="hover:underline"><i class="fa fa-home"></i> Home</a> 
                <i class="fa fa-angle-right mx-2"></i> <span>Dashboard Kas</span>
            </div>
        </div>

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
                    <h3 class="text-3xl font-extrabold <?= $saldo_akhir < 0 ? 'text-red-600' : 'text-[#377453]'; ?> mt-1">
                        <?= $saldo_akhir < 0 ? '-' : ''; ?>$<?= number_format(abs($saldo_akhir), 0, ',', '.'); ?>
                    </h3>
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
                        <?php if (in_array($role, ['Treasurer', 'Admin'])): ?>
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
                                <p class="text-xs mt-2">Sebagai Member, kamu hanya memiliki akses untuk melihat riwayat arus kas.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-2/3">
                <div class="ipsBox">
                    <div class="ipsBox_header bg-[#1e412e] text-white flex justify-between items-center">
                        <span><i class="fa fa-list-alt"></i> Riwayat Transaksi</span>
                        <?php if (in_array($role, ['Treasurer', 'Admin'])): ?>
                        <form action="" method="POST" class="m-0 p-0">
                            <?= csrf_field(); ?>
                            <button type="submit" name="export_csv" class="bg-yellow-500 hover:bg-yellow-400 text-[#1e412e] px-3 py-1 rounded text-xs font-bold transition shadow" title="Unduh Laporan Format Excel">
                                <i class="fa fa-download"></i> Ekspor XLS
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <div class="overflow-x-auto p-4">
                        <table class="w-full text-left border-collapse text-sm min-w-max">
                            <thead>
                                <tr class="bg-gray-100 border-b-2 border-gray-200 text-gray-600">
                                    <th class="p-3">Waktu</th>
                                    <th class="p-3">Pencatat</th>
                                    <th class="p-3">Keterangan</th>
                                    <th class="p-3 text-right">Nominal</th>
                                    <?php if (in_array($role, ['Treasurer', 'Admin'])): ?>
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
                                            
                                            <?php if (in_array($role, ['Treasurer', 'Admin'])): ?>
                                            <td class="p-3 text-center">
                                                <form action="" method="POST" id="delete-form-<?= $row['id']; ?>">
                                                    <?= csrf_field(); ?>
                                                    <input type="hidden" name="kas_id" value="<?= $row['id']; ?>">
                                                    <input type="hidden" name="delete_kas" value="1">
                                                    <button type="button" onclick="confirmDelete(<?= $row['id']; ?>)" class="text-red-500 hover:text-red-700 transition" title="Hapus Transaksi">
                                                        <i class="fa fa-trash text-lg"></i>
                                                    </button>
                                                </form>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?= in_array($role, ['Treasurer', 'Admin']) ? '5' : '4'; ?>" class="p-6 text-center text-gray-500">Belum ada riwayat transaksi kas tercatat.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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

    <script>
        // 1. Pop-up Konfirmasi Hapus Keren
        function confirmDelete(id) {
            Swal.fire({
                title: 'Hapus Transaksi?',
                text: "Data kas ini akan dihapus secara permanen dan memengaruhi saldo!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#8a9499',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Jika user klik 'Ya', submit form PHP-nya
                    document.getElementById('delete-form-' + id).submit();
                }
            })
        }

        // 2. Notifikasi Sukses Input Data
        <?php if (isset($_SESSION['swal_success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= $_SESSION['swal_success']; ?>',
                confirmButtonColor: '#377453'
            });
            <?php unset($_SESSION['swal_success']); // Hapus session agar tidak muncul terus ?>
        <?php endif; ?>

        // 3. Notifikasi Sukses Hapus Data
        <?php if (isset($_SESSION['swal_deleted'])): ?>
            Swal.fire({
                icon: 'info',
                title: 'Dihapus',
                text: '<?= $_SESSION['swal_deleted']; ?>',
                confirmButtonColor: '#1e412e'
            });
            <?php unset($_SESSION['swal_deleted']); ?>
        <?php endif; ?>
    </script>
</body>
</html>