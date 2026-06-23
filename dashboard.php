<?php
session_start();
require 'koneksi.php';

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
    $type = $_POST['type']; // INCOME atau EXPENSE
    $amount = preg_replace('/\D/', '', $_POST['amount']); // Bersihkan input, ambil angkanya saja
    $description = htmlspecialchars($_POST['description']);

    if (!empty($type) && !empty($amount) && !empty($description)) {
        $insert = $conn->prepare("INSERT INTO kas_transactions (user_id, type, amount, description) VALUES (?, ?, ?, ?)");
        $insert->bind_param("isis", $user_id, $type, $amount, $description);
        $insert->execute();
        
        // Refresh halaman agar form bersih dan saldo terupdate otomatis
        header("Location: dashboard.php?sukses=1");
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
                <span class="text-gray-