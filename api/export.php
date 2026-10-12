<?php
/**
 * NusaPOS - Export & Import CSV Handler
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

$user = require_login();
$db = get_db();

$type   = $_GET['type'] ?? '';
$action = $_GET['action'] ?? '';

// 1. Unduh Template CSV Import Produk
if ($type === 'template') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=template_import_produk_nusapos.csv');

    $output = fopen('php://output', 'w');
    // Header
    fputcsv($output, ['nama_produk', 'sku', 'barcode', 'kategori_kode', 'harga_beli', 'harga_jual', 'stok', 'min_stok']);
    // Contoh Baris
    fputcsv($output, ['Kopi Susu Gula Aren 250ml', 'SKU-MNM-099', '899123456789', 'MINUMAN', '10000', '15000', '50', '5']);
    fputcsv($output, ['Biskuit Cokelat Renyah 100g', 'SKU-MKN-099', '899987654321', 'MAKANAN', '5000', '8000', '30', '5']);
    fclose($output);
    exit;
}

// 2. Export Master Data Produk ke CSV
if ($type === 'produk') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=katalog_produk_nusapos_' . date('Ymd_His') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Nama Produk', 'SKU', 'Barcode', 'Kategori', 'Harga Beli', 'Harga Jual', 'Stok', 'Min Stok', 'Status']);

    $stmt = $db->query("
        SELECT p.id, p.nama_produk, p.sku, p.barcode, k.nama_kategori, p.harga_beli, p.harga_jual, p.stok, p.min_stok, p.status
        FROM produk p
        JOIN kategori k ON p.kategori_id = k.id
        ORDER BY p.id ASC
    ");

    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['id'],
            $row['nama_produk'],
            $row['sku'],
            $row['barcode'] ?: '-',
            $row['nama_kategori'],
            $row['harga_beli'],
            $row['harga_jual'],
            $row['stok'],
            $row['min_stok'],
            $row['status']
        ]);
    }

    fclose($output);
    exit;
}

// 3. Export Riwayat Transaksi ke CSV
if ($type === 'transaksi') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan_transaksi_nusapos_' . date('Ymd_His') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['No Invoice', 'Tanggal', 'Kasir', 'Pelanggan', 'Subtotal', 'PPN (11%)', 'Diskon', 'Total Akhir', 'Metode Bayar', 'Nominal Bayar', 'Kembalian']);

    $stmt = $db->query("
        SELECT t.*, u.nama_lengkap as nama_kasir
        FROM transaksi t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC
    ");

    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['no_invoice'],
            $row['created_at'],
            $row['nama_kasir'],
            $row['pelanggan'],
            $row['subtotal'],
            $row['pajak_ppn'],
            $row['diskon'],
            $row['total_akhir'],
            strtoupper($row['metode_pembayaran']),
            $row['nominal_bayar'],
            $row['kembalian']
        ]);
    }

    fclose($output);
    exit;
}

// 4. Proses Import CSV Produk
if ($action === 'import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['flash_error'] = 'Gagal mengunggah berkas CSV.';
        header('Location: ../inventory.php?tab=produk');
        exit;
    }

    $tmpPath = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($tmpPath, 'r');
    if (!$handle) {
        $_SESSION['flash_error'] = 'Tidak dapat membaca file CSV.';
        header('Location: ../inventory.php?tab=produk');
        exit;
    }

    $header = fgetcsv($handle);
    $insertedCount = 0;
    $skippedCount = 0;

    // Cache kategori
    $catMap = [];
    $cats = $db->query("SELECT id, kode FROM kategori")->fetchAll();
    foreach ($cats as $c) {
        $catMap[strtoupper($c['kode'])] = (int)$c['id'];
    }
    $defaultCatId = !empty($cats) ? (int)$cats[0]['id'] : 1;

    $stmtInsert = $db->prepare("
        INSERT INTO produk (nama_produk, sku, barcode, kategori_id, harga_beli, harga_jual, stok, min_stok, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'aktif')
    ");

    $stmtCheck = $db->prepare("SELECT id FROM produk WHERE sku = ?");

    $db->beginTransaction();
    try {
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2 || trim($row[0]) === '') continue;

            $nama      = trim($row[0]);
            $sku       = trim($row[1] ?? 'SKU-' . rand(100, 999));
            $barcode   = trim($row[2] ?? '') ?: null;
            $catKode   = strtoupper(trim($row[3] ?? ''));
            $hargaBeli = (float)($row[4] ?? 0);
            $hargaJual = (float)($row[5] ?? 0);
            $stok      = (int)($row[6] ?? 0);
            $minStok   = (int)($row[7] ?? 5);

            $kategoriId = $catMap[$catKode] ?? $defaultCatId;

            // Skip jika SKU sudah ada
            $stmtCheck->execute([$sku]);
            if ($stmtCheck->fetch()) {
                $skippedCount++;
                continue;
            }

            try {
                $stmtInsert->execute([$nama, $sku, $barcode, $kategoriId, $hargaBeli, $hargaJual, $stok, $minStok]);
                $insertedCount++;
            } catch (Exception $e) {
                $skippedCount++;
            }
        }
        $db->commit();
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        fclose($handle);
        $_SESSION['flash_error'] = 'Gagal memproses import data CSV: ' . $e->getMessage();
        header('Location: ../inventory.php?tab=produk');
        exit;
    }

    fclose($handle);

    $_SESSION['flash_success'] = "Import selesai: {$insertedCount} produk berhasil ditambahkan, {$skippedCount} dilewati.";
    header('Location: ../inventory.php?tab=produk');
    exit;
}

header('Location: ../inventory.php');
exit;
