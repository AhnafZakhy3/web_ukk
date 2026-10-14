<?php
// login boy
declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

if (is_logged_in()) {
    header('Location: kasir.php');
    exit;
}

$errorMessage = '';
$usernameVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $usernameVal = $identifier;

    if ($identifier === '' || $password === '') {
        $errorMessage = 'Mohon masukkan nama pengguna dan kata sandi Anda.';
    } else {
        try {
            $db = get_db();
            $stmt = $db->prepare("SELECT * FROM users WHERE (username = :u1 OR email = :u2) LIMIT 1");
            $stmt->execute([
                ':u1' => $identifier,
                ':u2' => $identifier,
            ]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'aktif') {
                    $errorMessage = 'Akun Anda berstatus nonaktif. Silakan hubungi Administrator.';
                } else {
                    login_user($user);
                    $_SESSION['flash_success'] = 'Selamat datang kembali, ' . $user['nama_lengkap'] . '!';
                    header('Location: kasir.php');
                    exit;
                }
            } else {
                $errorMessage = 'Nama pengguna atau kata sandi tidak sesuai.';
            }
        } catch (Exception $e) {
            $errorMessage = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Login Kasir & Admin';
$toko = get_toko_info();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= htmlspecialchars($toko['nama_toko']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/output.css">
    <script>
        (function() {
            const savedTheme = localStorage.getItem('nusapos_theme');
            if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-surface font-body-md text-on-surface antialiased min-h-screen flex items-center justify-center p-space-md sm:p-space-lg relative">

<div class="fixed top-4 right-4 z-50">
    <button type="button" onclick="toggleTheme()" title="Ganti Mode Gelap / Terang" aria-label="Ganti Mode Gelap / Terang" class="w-10 h-10 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface transition-all flex items-center justify-center border border-outline-variant/40 shadow-md hover:scale-105 active:scale-95 cursor-pointer">
        <span class="material-symbols-outlined theme-toggle-icon text-[20px]">dark_mode</span>
    </button>
</div>

<div class="relative w-full max-w-5xl bg-surface-container-lowest rounded-2xl shadow-xl overflow-hidden grid grid-cols-1 lg:grid-cols-12 min-h-[600px] border border-outline-variant/30">
    
    <div class="lg:col-span-5 bg-gradient-to-br from-primary via-primary-container to-secondary p-space-xl text-on-primary flex flex-col justify-between relative overflow-hidden">
        <div class="relative z-10 space-y-space-lg">
            <div class="flex items-center gap-space-md">
                <div class="w-12 h-12 rounded-xl bg-surface-container-lowest shadow-md flex items-center justify-center text-primary-container shrink-0">
                    <span class="material-symbols-outlined text-[28px]">point_of_sale</span>
                </div>
                <div>
                    <div class="flex items-baseline tracking-tight leading-none">
                        <span class="font-headline-lg text-headline-lg font-bold text-surface-container-lowest">Nusa</span>
                        <span class="font-headline-lg text-headline-lg font-bold text-primary-fixed">POS</span>
                    </div>
                    <span class="font-label-sm text-label-sm uppercase tracking-wider text-primary-fixed-dim">Point of Sale Enterprise</span>
                </div>
            </div>

            <div class="space-y-space-sm pt-4">
                <div class="inline-flex items-center gap-space-xs bg-surface-container-lowest/15 backdrop-blur-sm px-space-md py-1 rounded-full text-surface-container-lowest">
                    <span class="w-2 h-2 rounded-full bg-tertiary-fixed animate-ping"></span>
                    <span class="font-label-sm text-label-sm">Sistem Terintegrasi Online</span>
                </div>
                <h1 class="font-headline-xl text-headline-xl font-bold tracking-tight text-surface-container-lowest leading-tight">
                    Presisi Transaksi & Manajemen Ritel Cepat.
                </h1>
                <p class="font-body-md text-body-md text-primary-fixed-dim leading-relaxed">
                    Aplikasi kasir modern dengan pemrosesan order cepat, pemotongan stok otomatis, dan laporan akuntansi real-time.
                </p>
            </div>
        </div>

        <div class="relative z-10 pt-space-xl border-t border-primary-fixed/20 flex items-center justify-between text-body-sm text-primary-fixed-dim">
            <span><?= htmlspecialchars($toko['cabang']) ?></span>
            <span class="font-mono">v1.0 • UKK</span>
        </div>
    </div>

    <div class="lg:col-span-7 p-space-xl sm:p-12 flex flex-col justify-between bg-surface-container-lowest">
        <div>
            <div class="space-y-1 mb-8">
                <h2 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight">Selamat Datang di NusaPOS</h2>
                <p class="font-body-md text-body-md text-on-surface-variant">Silakan masukkan nama pengguna atau email untuk memulai shift kasir.</p>
            </div>

            <?php if ($errorMessage !== ''): ?>
            <div class="mb-6 p-4 rounded-xl bg-error-container text-on-error-container border border-error/20 flex items-start gap-3">
                <span class="material-symbols-outlined text-[22px] text-error shrink-0">error</span>
                <div class="flex-1 text-body-sm">
                    <p class="font-semibold text-error">Gagal Masuk</p>
                    <p><?= htmlspecialchars($errorMessage) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="space-y-5">
                <div>
                    <label for="username" class="block font-label-md text-label-md text-on-surface font-semibold mb-1.5">
                        Nama Pengguna atau Email
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-outline text-[20px]">person</span>
                        <input type="text" id="username" name="username" required value="<?= htmlspecialchars($usernameVal) ?>"
                            placeholder="Contoh: budisantoso atau sitikasir1"
                            class="w-full pl-11 pr-4 py-2.5 rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-body-md focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-primary border border-outline-variant/40 transition-all">
                    </div>
                </div>

                <div>
                    <label for="password" class="block font-label-md text-label-md text-on-surface font-semibold mb-1.5">
                        Kata Sandi
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-outline text-[20px]">lock</span>
                        <input type="password" id="password" name="password" required
                            placeholder="Masukkan kata sandi akun"
                            class="w-full pl-11 pr-11 py-2.5 rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-body-md focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-primary border border-outline-variant/40 transition-all">
                        <button type="button" id="btnTogglePassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-outline hover:text-on-surface focus:outline-none">
                            <span class="material-symbols-outlined text-[20px]" id="iconTogglePassword">visibility</span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded text-primary focus:ring-primary border-outline-variant cursor-pointer">
                        <span class="font-body-sm text-body-sm text-on-surface-variant">Ingat Saya</span>
                    </label>
                    <span class="text-body-sm text-outline">Terminal Kasir Resmi</span>
                </div>

                <button type="submit" id="btnSubmit" class="w-full py-3 px-space-md rounded-xl bg-primary hover:bg-secondary text-on-primary font-headline-sm text-headline-sm shadow-md transition-all active:scale-[0.99] flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">login</span>
                    <span>Masuk ke Sistem</span>
                </button>
            </form>

        </div>

        <div class="mt-8 pt-4 text-center">
            <p class="font-body-sm text-body-sm text-outline">
                &copy; <?= date('Y') ?> <?= htmlspecialchars($toko['nama_toko']) ?>. Hak Cipta Dilindungi.
            </p>
        </div>
    </div>
</div>

<script>
    const btnToggle = document.getElementById('btnTogglePassword');
    const pwdInput = document.getElementById('password');
    const pwdIcon = document.getElementById('iconTogglePassword');

    btnToggle.addEventListener('click', () => {
        if (pwdInput.type === 'password') {
            pwdInput.type = 'text';
            pwdIcon.textContent = 'visibility_off';
        } else {
            pwdInput.type = 'password';
            pwdIcon.textContent = 'visibility';
        }
    });

    function fillAccount(username, password) {
        document.getElementById('username').value = username;
        document.getElementById('password').value = password;
    }
</script>
<script src="public/js/app.js"></script>
</body>
</html>
