<?php
/**
 * NusaPOS - API Produk Action (CRUD, Toggle Status, Batch)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

$user = require_login();
$db = get_db();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

if (!validate_csrf($csrfToken)) {
    $_SESSION['flash_error'] = 'Validasi keamanan CSRF gagal atau sesi Anda kedaluwarsa. Silakan coba kembali.';
    header('Location: ../inventory.php?tab=produk');
    exit;
}

try {
    if ($action === 'add') {
        $namaProduk = trim($_POST['nama_produk'] ?? '');
        $sku        = trim($_POST['sku'] ?? '');
        $barcode    = trim($_POST['barcode'] ?? '') ?: null;
        $kategoriId = (int)($_POST['kategori_id'] ?? 0);
        $satuanId   = (int)($_POST['satuan_id'] ?? 0) ?: null;
        $hargaBeli  = (float)($_POST['harga_beli'] ?? 0);
        $hargaJual  = (float)($_POST['harga_jual'] ?? 0);
        $stok       = (int)($_POST['stok'] ?? 0);
        $minStok    = (int)($_POST['min_stok'] ?? 5);
        $gambar     = trim($_POST['gambar'] ?? '') ?: null;
        $status     = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';

        if ($namaProduk === '' || $sku === '' || $kategoriId <= 0) {
            throw new Exception("Nama produk, SKU, dan Kategori wajib diisi.");
        }

        // Cek duplikat SKU
        $stmtCheck = $db->prepare("SELECT id FROM produk WHERE sku = ?");
        $stmtCheck->execute([$sku]);
        if ($stmtCheck->fetch()) {
            throw new Exception("Kode SKU '{$sku}' sudah digunakan oleh produk lain.");
        }

        $stmt = $db->prepare("
            INSERT INTO produk (
                kategori_id, satuan_id, sku, barcode, nama_produk, 
                harga_beli, harga_jual, stok, min_stok, gambar, status
            ) VALUES (
                :kategori_id, :satuan_id, :sku, :barcode, :nama_produk,
                :harga_beli, :harga_jual, :stok, :min_stok, :gambar, :status
            )
        ");
        $stmt->execute([
            ':kategori_id' => $kategoriId,
            ':satuan_id'   => $satuanId,
            ':sku'         => $sku,
            ':barcode'     => $barcode,
            ':nama_produk' => $namaProduk,
            ':harga_beli'  => $hargaBeli,
            ':harga_jual'  => $hargaJual,
            ':stok'        => $stok,
            ':min_stok'    => $minStok,
            ':gambar'      => $gambar,
            ':status'      => $status,
        ]);

        $_SESSION['flash_success'] = "Produk '{$namaProduk}' berhasil ditambahkan ke katalog.";
        header('Location: ../inventory.php?tab=produk');
        exit;

    } elseif ($action === 'edit') {
        $id         = (int)($_POST['id'] ?? 0);
        $namaProduk = trim($_POST['nama_produk'] ?? '');
        $sku        = trim($_POST['sku'] ?? '');
        $barcode    = trim($_POST['barcode'] ?? '') ?: null;
        $kategoriId = (int)($_POST['kategori_id'] ?? 0);
        $satuanId   = (int)($_POST['satuan_id'] ?? 0) ?: null;
        $hargaBeli  = (float)($_POST['harga_beli'] ?? 0);
        $hargaJual  = (float)($_POST['harga_jual'] ?? 0);
        $stok       = (int)($_POST['stok'] ?? 0);
        $minStok    = (int)($_POST['min_stok'] ?? 5);
        $gambar     = trim($_POST['gambar'] ?? '') ?: null;
        $status     = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';

        if ($id <= 0 || $namaProduk === '' || $sku === '' || $kategoriId <= 0) {
            throw new Exception("Data produk tidak lengkap untuk diperbarui.");
        }

        // Cek duplikat SKU selain produk ini
        $stmtCheck = $db->prepare("SELECT id FROM produk WHERE sku = ? AND id != ?");
        $stmtCheck->execute([$sku, $id]);
        if ($stmtCheck->fetch()) {
            throw new Exception("Kode SKU '{$sku}' sudah digunakan oleh produk lain.");
        }

        $stmt = $db->prepare("
            UPDATE produk SET 
                kategori_id = :kategori_id,
                satuan_id = :satuan_id,
                sku = :sku,
                barcode = :barcode,
                nama_produk = :nama_produk,
                harga_beli = :harga_beli,
                harga_jual = :harga_jual,
                stok = :stok,
                min_stok = :min_stok,
                gambar = :gambar,
                status = :status
            WHERE id = :id
        ");
        $stmt->execute([
            ':kategori_id' => $kategoriId,
            ':satuan_id'   => $satuanId,
            ':sku'         => $sku,
            ':barcode'     => $barcode,
            ':nama_produk' => $namaProduk,
            ':harga_beli'  => $hargaBeli,
            ':harga_jual'  => $hargaJual,
            ':stok'        => $stok,
            ':min_stok'    => $minStok,
            ':gambar'      => $gambar,
            ':status'      => $status,
            ':id'          => $id,
        ]);

        $_SESSION['flash_success'] = "Informasi produk '{$namaProduk}' berhasil diperbarui.";
        header('Location: ../inventory.php?tab=produk');
        exit;

    } elseif ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) throw new Exception("ID produk tidak valid.");

        $stmt = $db->prepare("SELECT status, nama_produk FROM produk WHERE id = ?");
        $stmt->execute([$id]);
        $prod = $stmt->fetch();
        if (!$prod) throw new Exception("Produk tidak ditemukan.");

        $newStatus = ($prod['status'] === 'aktif') ? 'nonaktif' : 'aktif';
        $stmtUpdate = $db->prepare("UPDATE produk SET status = ? WHERE id = ?");
        $stmtUpdate->execute([$newStatus, $id]);

        $_SESSION['flash_success'] = "Status produk '{$prod['nama_produk']}' diubah menjadi " . ucfirst($newStatus) . ".";
        header('Location: ../inventory.php?tab=produk');
        exit;

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) throw new Exception("ID produk tidak valid.");

        // Cek apakah produk memiliki riwayat transaksi
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM detail_transaksi WHERE produk_id = ?");
        $stmtCheck->execute([$id]);
        $trxCount = (int)$stmtCheck->fetchColumn();

        if ($trxCount > 0) {
            // Jika ada riwayat, ubah status ke nonaktif agar data laporan akuntansi tetap konsisten
            $db->prepare("UPDATE produk SET status = 'nonaktif' WHERE id = ?")->execute([$id]);
            $_SESSION['flash_success'] = "Produk telah dinonaktifkan dari katalog karena memiliki riwayat transaksi.";
        } else {
            $db->prepare("DELETE FROM produk WHERE id = ?")->execute([$id]);
            $_SESSION['flash_success'] = "Produk berhasil dihapus dari sistem.";
        }

        header('Location: ../inventory.php?tab=produk');
        exit;

    } elseif ($action === 'batch_delete') {
        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids) || empty($ids)) {
            throw new Exception("Tidak ada produk terpilih.");
        }

        $intIds = array_map('intval', $ids);
        foreach ($intIds as $pId) {
            $stmtCheck = $db->prepare("SELECT COUNT(*) FROM detail_transaksi WHERE produk_id = ?");
            $stmtCheck->execute([$pId]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                $db->prepare("UPDATE produk SET status = 'nonaktif' WHERE id = ?")->execute([$pId]);
            } else {
                $db->prepare("DELETE FROM produk WHERE id = ?")->execute([$pId]);
            }
        }

        $_SESSION['flash_success'] = count($intIds) . " produk terpilih berhasil diproses.";
        header('Location: ../inventory.php?tab=produk');
        exit;

    } else {
        throw new Exception("Aksi tidak dikenal.");
    }
} catch (Exception $e) {
    $_SESSION['flash_error'] = $e->getMessage();
    header('Location: ../inventory.php?tab=produk');
    exit;
}
