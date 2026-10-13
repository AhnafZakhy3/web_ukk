<?php
/**
 * NusaPOS - Layout Sidebar
 */
declare(strict_types=1);

$user = current_user();
$activeMenu = $activeMenu ?? '';
$toko = get_toko_info();
?>
<!-- Mobile Sidebar Backdrop Overlay -->
<div id="sidebar-backdrop" onclick="toggleSidebar(false)" class="fixed inset-0 bg-inverse-surface/40 backdrop-blur-xs z-40 hidden md:hidden"></div>

<aside id="app-sidebar" class="fixed left-0 top-0 h-screen w-64 bg-surface-container-lowest border-r border-outline-variant/40 z-50 flex flex-col justify-between shadow-[0_1px_8px_rgba(0,0,0,0.04)] -translate-x-full md:translate-x-0 transition-transform duration-300">
    <div class="flex flex-col">
        <!-- Brand Header -->
        <div class="p-space-lg border-b border-outline-variant/30 flex items-center justify-between gap-space-sm">
            <div class="flex items-center gap-space-sm min-w-0">
                <div class="w-10 h-10 rounded-xl bg-primary-container flex items-center justify-center text-on-primary shadow-sm shrink-0">
                    <span class="material-symbols-outlined text-[24px]">point_of_sale</span>
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="font-headline-sm text-headline-sm text-primary tracking-tight font-bold truncate">NusaPOS</span>
                    <span class="font-label-sm text-label-sm text-on-surface-variant truncate flex items-center gap-1">
                        <span class="material-symbols-outlined text-[13px] text-tertiary">store</span>
                        <?= htmlspecialchars($toko['cabang']) ?>
                    </span>
                </div>
            </div>
            <!-- Desktop collapse button inside sidebar -->
            <button type="button" onclick="toggleSidebarCollapse()" title="Sembunyikan Sidebar (Ctrl+B)" aria-label="Sembunyikan Sidebar" class="hidden md:flex p-1.5 rounded-lg text-outline hover:text-on-surface hover:bg-surface-container transition-colors cursor-pointer shrink-0">
                <span class="material-symbols-outlined text-[20px]">first_page</span>
            </button>
        </div>

        <!-- Section Label -->
        <div class="px-space-md py-space-sm">
            <span class="font-label-sm text-label-sm uppercase tracking-wider text-outline px-space-sm font-semibold">Navigasi Operasional</span>
        </div>

        <!-- Nav Links -->
        <nav class="px-space-sm space-y-1">
            <!-- 1. Terminal Kasir -->
            <a href="kasir.php" class="flex items-center gap-space-sm px-space-md py-2.5 rounded-lg transition-all <?= ($activeMenu === 'kasir') ? 'bg-primary-container text-on-primary font-semibold shadow-sm' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface font-label-lg' ?>">
                <span class="material-symbols-outlined text-[20px]">point_of_sale</span>
                <span>Terminal Kasir</span>
            </a>

            <!-- 2. Inventory (Dropdown/Subnav) -->
            <div class="space-y-1">
                <a href="inventory.php" class="flex items-center justify-between px-space-md py-2.5 rounded-lg transition-all <?= (str_starts_with($activeMenu, 'inventory')) ? 'bg-surface-container text-primary font-semibold' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface font-label-lg' ?>">
                    <div class="flex items-center gap-space-sm">
                        <span class="material-symbols-outlined text-[20px]">inventory_2</span>
                        <span>Inventory</span>
                    </div>
                    <span class="material-symbols-outlined text-[16px] text-outline">expand_more</span>
                </a>
                <div class="pl-8 pr-space-xs space-y-1">
                    <a href="inventory.php?tab=produk" class="flex items-center gap-2 py-1.5 px-space-sm rounded-md transition-colors font-body-sm <?= ($activeMenu === 'inventory-produk') ? 'text-primary font-semibold bg-surface-container-high/60' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface' ?>">
                        <span class="w-1.5 h-1.5 rounded-full <?= ($activeMenu === 'inventory-produk') ? 'bg-primary' : 'bg-outline' ?>"></span>
                        <span>Katalog Produk</span>
                    </a>
                    <a href="inventory.php?tab=kategori" class="flex items-center gap-2 py-1.5 px-space-sm rounded-md transition-colors font-body-sm <?= ($activeMenu === 'inventory-kategori') ? 'text-primary font-semibold bg-surface-container-high/60' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface' ?>">
                        <span class="w-1.5 h-1.5 rounded-full <?= ($activeMenu === 'inventory-kategori') ? 'bg-primary' : 'bg-outline' ?>"></span>
                        <span>Kategori & Unit</span>
                    </a>
                </div>
            </div>

            <!-- 3. Manajemen Akun (Khusus Admin atau ditampilkan status) -->
            <?php if (is_admin()): ?>
            <a href="pengguna.php" class="flex items-center gap-space-sm px-space-md py-2.5 rounded-lg transition-all <?= ($activeMenu === 'pengguna') ? 'bg-primary-container text-on-primary font-semibold shadow-sm' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface font-label-lg' ?>">
                <span class="material-symbols-outlined text-[20px]">group</span>
                <span>Manajemen Akun</span>
            </a>
            <?php else: ?>
            <div class="flex items-center justify-between px-space-md py-2.5 rounded-lg text-outline/60 cursor-not-allowed font-label-lg" title="Akses khusus Administrator">
                <div class="flex items-center gap-space-sm">
                    <span class="material-symbols-outlined text-[20px]">lock</span>
                    <span>Manajemen Akun</span>
                </div>
                <span class="text-[10px] uppercase font-bold bg-surface-container px-1.5 py-0.5 rounded">Admin</span>
            </div>
            <?php endif; ?>

            <!-- 4. Laporan Transaksi -->
            <a href="transaksi.php" class="flex items-center gap-space-sm px-space-md py-2.5 rounded-lg transition-all <?= ($activeMenu === 'transaksi') ? 'bg-primary-container text-on-primary font-semibold shadow-sm' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface font-label-lg' ?>">
                <span class="material-symbols-outlined text-[20px]">bar_chart</span>
                <span>Laporan Transaksi</span>
            </a>
        </nav>
    </div>

    <!-- Bottom: Mode Gelap/Terang & Keluar Sistem -->
    <div class="p-space-md border-t border-outline-variant/30 space-y-2">
        <button type="button" onclick="toggleTheme()" class="w-full flex items-center justify-between px-space-md py-2.5 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface transition-all font-label-md cursor-pointer border border-outline-variant/30">
            <div class="flex items-center gap-space-sm">
                <span class="material-symbols-outlined theme-toggle-icon text-[20px] text-primary">dark_mode</span>
                <span class="theme-toggle-text">Mode Gelap</span>
            </div>
            <span class="text-xs px-2 py-0.5 rounded bg-surface-container-highest text-on-surface-variant font-semibold theme-status-text">Terang</span>
        </button>

        <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem NusaPOS?');" class="w-full flex items-center justify-between px-space-md py-2.5 rounded-lg text-error hover:bg-error-container hover:text-on-error-container transition-all font-label-md">
            <div class="flex items-center gap-space-sm">
                <span class="material-symbols-outlined text-[20px]">logout</span>
                <span>Keluar Sistem</span>
            </div>
            <span class="material-symbols-outlined text-[16px]">chevron_right</span>
        </a>
    </div>
</aside>
