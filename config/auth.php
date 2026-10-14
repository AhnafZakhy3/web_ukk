<?php
// buat sesi sama login

declare(strict_types=1);

require_once __DIR__ . '/database.php';
// sesi
function init_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

init_session();

function current_user(): ?array {
    return $_SESSION['nusapos_user'] ?? null;
}

function is_logged_in(): bool {
    return current_user() !== null;
}

function is_admin(): bool {
    $u = current_user();
    return $u !== null && ($u['role'] === 'admin');
}

function require_login(): array {
    $user = current_user();
    if (!$user) {
        header('Location: login.php');
        exit;
    }
    return $user;
}

function require_admin(): array {
    $user = require_login();
    if ($user['role'] !== 'admin') {
        $_SESSION['flash_error'] = 'Akses ditolak: Anda tidak memiliki izin Administrator.';
        header('Location: kasir.php');
        exit;
    }
    return $user;
}

function login_user(array $user): void {
    $_SESSION['nusapos_user'] = [
        'id'           => $user['id'],
        'username'     => $user['username'],
        'email'        => $user['email'],
        'nama_lengkap' => $user['nama_lengkap'],
        'role'         => $user['role'],
        'cabang'       => $user['cabang'] ?? 'Cabang Menteng - Jakarta Pusat',
        'avatar'       => $user['avatar'] ?? null,
    ];

    try {
        $db = get_db();
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
    } catch (Exception $e) {
    }
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf(?string $token): bool {
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function format_rupiah(float|int|string $amount): string {
    return 'Rp' . number_format((float)$amount, 0, ',', '.');
}

function get_toko_info(): array {
    static $info = null;
    if ($info === null) {
        try {
            $db = get_db();
            $info = $db->query("SELECT * FROM pengaturan WHERE id = 1")->fetch() ?: [
                'nama_toko' => 'NusaPOS Retail Mart',
                'cabang' => 'Cabang Menteng - Jakarta Pusat',
                'alamat' => 'Jl. HOS Cokroaminoto No. 42',
                'telepon' => '(021) 3928-1920',
                'persen_ppn' => 11.00,
                'printer_footer' => 'Terima kasih atas kunjungan Anda!',
            ];
        } catch (Exception $e) {
            $info = [
                'nama_toko' => 'NusaPOS Retail Mart',
                'cabang' => 'Cabang Menteng - Jakarta Pusat',
                'alamat' => 'Jl. HOS Cokroaminoto No. 42',
                'telepon' => '(021) 3928-1920',
                'persen_ppn' => 11.00,
                'printer_footer' => 'Terima kasih atas kunjungan Anda!',
            ];
        }
    }
    return $info;
}
