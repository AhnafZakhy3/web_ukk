<?php
/**
 * NusaPOS - API Pengguna & Otoritas Action
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

// Proteksi Keamanan: Hanya Role Administrator yang dapat memodifikasi akun
$currentUser = require_admin();
$db = get_db();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

if (!validate_csrf($csrfToken)) {
    $_SESSION['flash_error'] = 'Validasi keamanan CSRF gagal atau sesi Anda kedaluwarsa. Silakan coba kembali.';
    header('Location: ../pengguna.php');
    exit;
}

try {
    if ($action === 'add') {
        $namaLengkap = trim($_POST['nama_lengkap'] ?? '');
        $username    = trim($_POST['username'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $password    = $_POST['password'] ?? '';
        $role        = ($_POST['role'] ?? 'kasir') === 'admin' ? 'admin' : 'kasir';
        $status      = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';
        $cabang      = trim($_POST['cabang'] ?? 'Cabang Menteng - Jakarta Pusat');
        $avatar      = trim($_POST['avatar'] ?? '') ?: null;

        if ($namaLengkap === '' || $username === '' || $email === '' || $password === '') {
            throw new Exception("Seluruh kolom wajib diisi.");
        }

        // Cek duplikasi username / email
        $stmtCheck = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmtCheck->execute([$username, $email]);
        if ($stmtCheck->fetch()) {
            throw new Exception("Nama pengguna atau email sudah terdaftar dalam sistem.");
        }

        $hashPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("
            INSERT INTO users (username, email, password, nama_lengkap, role, cabang, status, avatar)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$username, $email, $hashPassword, $namaLengkap, $role, $cabang, $status, $avatar]);

        $_SESSION['flash_success'] = "Akun pengguna '{$namaLengkap}' berhasil dibuat.";
        header('Location: ../pengguna.php');
        exit;

    } elseif ($action === 'edit') {
        $id          = (int)($_POST['id'] ?? 0);
        $namaLengkap = trim($_POST['nama_lengkap'] ?? '');
        $username    = trim($_POST['username'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $role        = ($_POST['role'] ?? 'kasir') === 'admin' ? 'admin' : 'kasir';
        $status      = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';
        $cabang      = trim($_POST['cabang'] ?? 'Cabang Menteng - Jakarta Pusat');
        $avatar      = trim($_POST['avatar'] ?? '') ?: null;

        if ($id <= 0 || $namaLengkap === '' || $username === '' || $email === '') {
            throw new Exception("Data pengguna tidak lengkap.");
        }

        // Cek jika mengubah role atau status admin terakhir
        if ($role === 'kasir' || $status === 'nonaktif') {
            $stmtCountAdmin = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'aktif' AND id != ?");
            $stmtCountAdmin->execute([$id]);
            if ((int)$stmtCountAdmin->fetchColumn() === 0) {
                throw new Exception("Tindakan Ditolak: Anda tidak dapat mengubah peran atau menonaktifkan satu-satunya Administrator aktif.");
            }
        }

        // Cek duplikasi username / email pengguna lain
        $stmtCheck = $db->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmtCheck->execute([$username, $email, $id]);
        if ($stmtCheck->fetch()) {
            throw new Exception("Nama pengguna atau email sudah digunakan oleh akun lain.");
        }

        $stmt = $db->prepare("
            UPDATE users SET 
                username = ?, email = ?, nama_lengkap = ?, 
                role = ?, cabang = ?, status = ?, avatar = ?
            WHERE id = ?
        ");
        $stmt->execute([$username, $email, $namaLengkap, $role, $cabang, $status, $avatar, $id]);

        $_SESSION['flash_success'] = "Data akun '{$namaLengkap}' berhasil diperbarui.";
        header('Location: ../pengguna.php');
        exit;

    } elseif ($action === 'reset_password') {
        $id       = (int)($_POST['id'] ?? 0);
        $password = $_POST['new_password'] ?? 'password123';

        if ($id <= 0 || trim($password) === '') {
            throw new Exception("ID akun dan kata sandi baru wajib valid.");
        }

        $hashPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashPassword, $id]);

        $_SESSION['flash_success'] = "Kata sandi akun berhasil diatur ulang menjadi '{$password}'.";
        header('Location: ../pengguna.php');
        exit;

    } elseif ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) throw new Exception("ID akun tidak valid.");

        $stmt = $db->prepare("SELECT role, status, nama_lengkap FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $u = $stmt->fetch();
        if (!$u) throw new Exception("Pengguna tidak ditemukan.");

        $newStatus = ($u['status'] === 'aktif') ? 'nonaktif' : 'aktif';

        if ($u['role'] === 'admin' && $newStatus === 'nonaktif') {
            $stmtCountAdmin = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'aktif' AND id != ?");
            $stmtCountAdmin->execute([$id]);
            if ((int)$stmtCountAdmin->fetchColumn() === 0) {
                throw new Exception("Tindakan Ditolak: Anda tidak dapat menonaktifkan Administrator terakhir.");
            }
        }

        $db->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
        $_SESSION['flash_success'] = "Status akun '{$u['nama_lengkap']}' diubah menjadi " . ucfirst($newStatus) . ".";
        header('Location: ../pengguna.php');
        exit;

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) throw new Exception("ID akun tidak valid.");

        if ($id === (int)$currentUser['id']) {
            throw new Exception("Tindakan Ditolak: Anda tidak dapat menghapus akun Anda sendiri saat sedang masuk.");
        }

        $stmt = $db->prepare("SELECT role, nama_lengkap FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $u = $stmt->fetch();
        if (!$u) throw new Exception("Pengguna tidak ditemukan.");

        if ($u['role'] === 'admin') {
            $stmtCountAdmin = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'aktif' AND id != ?");
            $stmtCountAdmin->execute([$id]);
            if ((int)$stmtCountAdmin->fetchColumn() === 0) {
                throw new Exception("Tindakan Ditolak: Anda tidak dapat menghapus Administrator terakhir.");
            }
        }

        // Cek riwayat transaksi
        $stmtCheckTrx = $db->prepare("SELECT COUNT(*) FROM transaksi WHERE user_id = ?");
        $stmtCheckTrx->execute([$id]);
        if ((int)$stmtCheckTrx->fetchColumn() > 0) {
            $db->prepare("UPDATE users SET status = 'nonaktif' WHERE id = ?")->execute([$id]);
            $_SESSION['flash_success'] = "Akun '{$u['nama_lengkap']}' telah dinonaktifkan karena memiliki riwayat transaksi tercatat.";
        } else {
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            $_SESSION['flash_success'] = "Akun '{$u['nama_lengkap']}' berhasil dihapus permanen.";
        }

        header('Location: ../pengguna.php');
        exit;

    } else {
        throw new Exception("Aksi tidak dikenal.");
    }
} catch (Exception $e) {
    $_SESSION['flash_error'] = $e->getMessage();
    header('Location: ../pengguna.php');
    exit;
}
