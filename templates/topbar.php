<?php
/**
 * NusaPOS - Layout Topbar
 */
declare(strict_types=1);

$user = current_user() ?? [
    'nama_lengkap' => 'Operator',
    'role' => 'kasir',
    'avatar' => null,
];
$avatarUrl = $user['avatar'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($user['nama_lengkap']) . '&background=2563EB&color=fff&size=128';
?>
<header class="app-topbar fixed top-0 left-0 md:left-64 right-0 h-16 bg-surface-container-lowest/95 backdrop-blur-md border-b border-outline-variant/40 z-40 px-4 md:px-space-lg flex items-center justify-between shadow-[0_1px_4px_rgba(0,0,0,0.02)]">
    <!-- Left: Hamburger & Status -->
    <div class="flex items-center gap-space-sm lg:gap-space-md">
        <!-- Hamburger button mobile -->
        <button type="button" onclick="toggleSidebar(true)" aria-label="Buka menu navigasi" class="md:hidden p-2 rounded-xl text-on-surface hover:bg-surface-container transition-colors flex items-center justify-center">
            <span class="material-symbols-outlined text-[24px]">menu</span>
        </button>
        <div class="hidden xl:flex items-center gap-2 bg-surface-container-low px-space-md py-1.5 rounded-lg border border-outline-variant/30">
            <span class="material-symbols-outlined text-[18px] text-outline">keyboard</span>
            <span class="font-label-sm text-label-sm text-on-surface-variant">
                <span class="font-semibold text-primary">F1:</span> Cari Produk &nbsp;|&nbsp; 
                <span class="font-semibold text-primary">F2:</span> Bayar &nbsp;|&nbsp; 
                <span class="font-semibold text-primary">F9:</span> Cetak Struk
            </span>
        </div>
        <div class="flex items-center gap-2 px-space-sm py-1 bg-on-tertiary-container/30 border border-tertiary-fixed rounded-full">
            <span class="w-2 h-2 rounded-full bg-tertiary animate-pulse"></span>
            <span class="font-label-sm text-label-sm text-tertiary font-semibold">Online • Mesin Kasir #01</span>
        </div>
    </div>

    <!-- Right: Profile & Quick Actions -->
    <div class="flex items-center gap-space-md">
        <!-- Date display -->
        <div class="hidden md:flex items-center gap-1.5 text-on-surface-variant font-label-sm">
            <span class="material-symbols-outlined text-[16px]">calendar_today</span>
            <span><?= date('d M Y') ?></span>
        </div>

        <!-- Theme Toggle Button -->
        <button type="button" onclick="toggleTheme()" title="Ganti Mode Gelap / Terang" aria-label="Ganti Mode Gelap / Terang" class="w-9 h-9 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface transition-all flex items-center justify-center border border-outline-variant/30 hover:scale-105 active:scale-95 shadow-xs cursor-pointer">
            <span class="material-symbols-outlined theme-toggle-icon text-[20px] transition-transform duration-300">dark_mode</span>
        </button>

        <div class="h-6 w-px bg-outline-variant/50 hidden md:block"></div>

        <!-- User Profile Pill -->
        <div class="relative group">
            <div class="flex items-center gap-space-sm pl-space-xs cursor-pointer">
                <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Profil" class="w-9 h-9 rounded-full object-cover ring-2 ring-primary/20 shadow-sm">
                <div class="hidden md:flex flex-col text-left">
                    <span class="font-label-md text-label-md text-on-surface font-semibold truncate max-w-[140px]"><?= htmlspecialchars($user['nama_lengkap']) ?></span>
                    <span class="font-label-sm text-label-sm text-primary font-medium capitalize"><?= $user['role'] === 'admin' ? 'Administrator' : 'Kasir Utama' ?></span>
                </div>
                <span class="material-symbols-outlined text-[18px] text-on-surface-variant group-hover:text-on-surface transition-transform group-hover:rotate-180">arrow_drop_down</span>
            </div>

            <!-- Profile Dropdown Menu -->
            <div class="absolute right-0 top-full mt-2 w-56 bg-surface-container-lowest rounded-xl shadow-xl border border-outline-variant/40 py-2 hidden group-hover:block hover:block z-50">
                <div class="px-4 py-2 border-b border-outline-variant/30">
                    <p class="font-label-sm text-on-surface-variant">Masuk sebagai:</p>
                    <p class="font-label-md text-on-surface font-bold truncate"><?= htmlspecialchars($user['nama_lengkap']) ?></p>
                    <span class="inline-block mt-1 font-label-sm text-xs px-2 py-0.5 rounded-full <?= $user['role'] === 'admin' ? 'bg-primary-fixed text-on-primary-fixed' : 'bg-surface-container text-on-surface' ?>">
                        <?= strtoupper($user['role']) ?>
                    </span>
                </div>
                
                <!-- Quick Toggle inside menu -->
                <button type="button" onclick="toggleTheme()" class="w-full flex items-center justify-between px-4 py-2.5 text-on-surface hover:bg-surface-container text-body-sm transition-colors text-left cursor-pointer border-b border-outline-variant/20">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined theme-toggle-icon text-[18px] text-primary">dark_mode</span>
                        <span>Mode Tampilan</span>
                    </span>
                    <span class="text-xs px-2 py-0.5 rounded bg-surface-container-high text-on-surface-variant font-medium theme-status-text">Terang</span>
                </button>

                <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?');" class="flex items-center gap-2 px-4 py-2.5 text-error hover:bg-error-container text-body-sm transition-colors">
                    <span class="material-symbols-outlined text-[18px]">logout</span>
                    <span>Keluar Sistem</span>
                </a>
            </div>
        </div>
    </div>
</header>
