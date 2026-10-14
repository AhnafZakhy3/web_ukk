<?php
// kode buat kategori sama satuan

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

$user = require_login();
$db = get_db();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

if (!validate_csrf($csrfToken)) {
    $_SESSION['flash_error'] = 'Validasi keamanan CSRF gagal atau sesi Anda kedaluwarsa. Silakan coba kembali.';
    header('Location: ../inventory.php?tab=kategori');
    exit;
}

try {
    if ($action === 'add_kategori') {
        $nama = trim($_POST['nama_kategori'] ?? '');
        $kode = strtoupper(trim($_POST['kode'] ?? ''));
        $ikon = trim($_POST['ikon'] ?? 'category');
        $desk = trim($_POST['deskripsi'] ?? '');
        $status = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';

        if ($nama === '' || $kode === '') {
            throw new Exception("Nama kategori dan kode departemen wajib diisi.");
        }

        // Cek duplikat kode
        $stmtCheck = $db->prepare("SELECT id FROM kategori WHERE kode = ?");
        $stmtCheck->execute([$kode]);
        if ($stmtCheck->fetch()) {
            throw new Exception("Kode kategori '{$kode}' sudah ada.");
        }

        $stmt = $db->prepare("INSERT INTO kategori (nama_kategori, kode, ikon, deskripsi, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nama, $kode, $ikon, $desk, $status]);

        $_SESSION['flash_success'] = "Kategori '{$nama}' berhasil ditambahkan.";
        header('Location: ../inventory.php?tab=kategori');
        exit;

    } elseif ($action === 'edit_kategori') {
        $id   = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama_kategori'] ?? '');
        $kode = strtoupper(trim($_POST['kode'] ?? ''));
        $ikon = trim($_POST['ikon'] ?? 'category');
        $desk = trim($_POST['deskripsi'] ?? '');
        $status = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';

        if ($id <= 0 || $nama === '' || $kode === '') {
            throw new Exception("Data kategori tidak lengkap.");
        }

        // buat ngecek duplikat
        $stmtCheck = $db->prepare("SELECT id FROM kategori WHERE kode = ? AND id != ?");
        $stmtCheck->execute([$kode, $id]);
        if ($stmtCheck->fetch()) {
            throw new Exception("Kode kategori '{$kode}' sudah digunakan kategori lain.");
        }

        $stmt = $db->prepare("UPDATE kategori SET nama_kategori = ?, kode = ?, ikon = ?, deskripsi = ?, status = ? WHERE id = ?");
        $stmt->execute([$nama, $kode, $ikon, $desk, $status, $id]);

        $_SESSION['flash_success'] = "Kategori '{$nama}' berhasil diperbarui.";
        header('Location: ../inventory.php?tab=kategori');
        exit;

    } elseif ($action === 'delete_kategori') {
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) throw new Exception("ID kategori tidak valid.");

        // ngecek produk yang terikatr ke kategori
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM produk WHERE kategori_id = ?");
        $stmtCheck->execute([$id]);
        $prodCount = (int)$stmtCheck->fetchColumn();

        if ($prodCount > 0) {
            throw new Exception("Kategori tidak dapat dihapus karena masih digunakan oleh {$prodCount} produk. Pindahkan atau hapus produk terlebih dahulu.");
        }

        $stmt = $db->prepare("DELETE FROM kategori WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['flash_success'] = "Kategori berhasil dihapus.";
        header('Location: ../inventory.php?tab=kategori');
        exit;

    } elseif ($action === 'add_satuan') {
        $nama = trim($_POST['nama_satuan'] ?? '');
        $singkatan = trim($_POST['singkatan'] ?? '');

        if ($nama === '' || $singkatan === '') {
            throw new Exception("Nama satuan dan singkatan wajib diisi.");
        }

        $stmt = $db->prepare("INSERT INTO satuan (nama_satuan, singkatan) VALUES (?, ?)");
        $stmt->execute([$nama, $singkatan]);

        $_SESSION['flash_success'] = "Satuan '{$singkatan}' berhasil ditambahkan.";
        header('Location: ../inventory.php?tab=kategori');
        exit;

    } elseif ($action === 'delete_satuan') {
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) throw new Exception("ID satuan tidak valid.");

        $db->prepare("UPDATE produk SET satuan_id = NULL WHERE satuan_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM satuan WHERE id = ?")->execute([$id]);

        $_SESSION['flash_success'] = "Satuan unit berhasil dihapus.";
        header('Location: ../inventory.php?tab=kategori');
        exit;

    } else {
        throw new Exception("Aksi tidak valid.");
    }
} catch (Exception $e) {
    $_SESSION['flash_error'] = $e->getMessage();
    header('Location: ../inventory.php?tab=kategori');
    exit;
}
