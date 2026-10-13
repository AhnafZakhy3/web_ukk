<?php
/**
 * NusaPOS - Inventory (Katalog Produk & Kategori)
 */

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

$user = require_login();
$tab = $_GET['tab'] ?? 'produk';
$activeMenu = ($tab === 'kategori') ? 'inventory-kategori' : 'inventory-produk';
$pageTitle = ($tab === 'kategori') ? 'Manajemen Kategori & Satuan' : 'Inventory Katalog Produk';

$db = get_db();

// 1. Data Statistik Produk
$statTotal = (int)$db->query("SELECT COUNT(*) FROM produk")->fetchColumn();
$statAktif = (int)$db->query("SELECT COUNT(*) FROM produk WHERE status = 'aktif'")->fetchColumn();
$statMenipis = (int)$db->query("SELECT COUNT(*) FROM produk WHERE stok > 0 AND stok <= min_stok AND status = 'aktif'")->fetchColumn();
$statHabis = (int)$db->query("SELECT COUNT(*) FROM produk WHERE stok <= 0 AND status = 'aktif'")->fetchColumn();

// 2. Data Kategori & Satuan
$categories = $db->query("
    SELECT k.*, COUNT(p.id) as total_produk 
    FROM kategori k 
    LEFT JOIN produk p ON k.id = p.kategori_id 
    GROUP BY k.id 
    ORDER BY k.nama_kategori ASC
")->fetchAll();

$units = $db->query("SELECT * FROM satuan ORDER BY nama_satuan ASC")->fetchAll();

// 3. Query Produk dengan Filter & Pagination
$filterCat = $_GET['kategori'] ?? 'all';
$filterStock = $_GET['stok'] ?? 'all';
$filterActive = $_GET['status'] ?? 'all';
$searchQuery = trim($_GET['q'] ?? '');

$baseWhere = " WHERE 1=1";
$params = [];

if ($filterCat !== 'all' && $filterCat !== '') {
    $baseWhere .= " AND (k.kode = :cat OR k.id = :cat_id)";
    $params[':cat'] = $filterCat;
    $params[':cat_id'] = $filterCat;
}

if ($filterStock === 'tersedia') {
    $baseWhere .= " AND p.stok > p.min_stok";
} elseif ($filterStock === 'menipis') {
    $baseWhere .= " AND p.stok > 0 AND p.stok <= p.min_stok";
} elseif ($filterStock === 'habis') {
    $baseWhere .= " AND p.stok <= 0";
}

if ($filterActive === 'aktif') {
    $baseWhere .= " AND p.status = 'aktif'";
} elseif ($filterActive === 'nonaktif') {
    $baseWhere .= " AND p.status = 'nonaktif'";
}

if ($searchQuery !== '') {
    $baseWhere .= " AND (p.nama_produk LIKE :q1 OR p.sku LIKE :q2 OR p.barcode LIKE :q3)";
    $params[':q1'] = "%{$searchQuery}%";
    $params[':q2'] = "%{$searchQuery}%";
    $params[':q3'] = "%{$searchQuery}%";
}

// 4. Hitung Total Data untuk Pagination
$countSql = "
    SELECT COUNT(*) 
    FROM produk p
    JOIN kategori k ON p.kategori_id = k.id
    LEFT JOIN satuan s ON p.satuan_id = s.id
    {$baseWhere}
";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalFilteredProducts = (int)$countStmt->fetchColumn();

// Filter per-page limit (10, 30, 50 item)
$allowedLimits = [10, 30, 50];
$rawLimit = (int)($_GET['limit'] ?? 10);
$limit = in_array($rawLimit, $allowedLimits, true) ? $rawLimit : 10;

$totalPages = max(1, (int)ceil($totalFilteredProducts / $limit));
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) {
    $page = 1;
} elseif ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $limit;

$sql = "
    SELECT p.*, k.nama_kategori, k.kode as kode_kategori, s.singkatan as satuan_nama
    FROM produk p
    JOIN kategori k ON p.kategori_id = k.id
    LEFT JOIN satuan s ON p.satuan_id = s.id
    {$baseWhere}
    ORDER BY p.id DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $db->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$startItem = ($totalFilteredProducts > 0) ? ($offset + 1) : 0;
$endItem = min($offset + $limit, $totalFilteredProducts);

$paginationQuery = $_GET;
$paginationQuery['tab'] = 'produk';
$paginationQuery['limit'] = $limit;
unset($paginationQuery['page']);
$basePaginationUrl = 'inventory.php?' . http_build_query($paginationQuery);

require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';
require_once __DIR__ . '/templates/topbar.php';
?>

<div class="app-layout-content pl-0 md:pl-64 pt-16 min-h-screen bg-surface">
    <main class="p-space-md lg:p-space-lg space-y-space-lg">
        
        <!-- Header & Nav Tabs Subhalaman -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-space-md border-b border-outline-variant/30 pb-4">
            <div>
                <h1 class="font-headline-lg text-2xl text-on-surface font-bold tracking-tight">Inventory</h1>
                <p class="font-body-md text-sm text-on-surface-variant">Kelola katalog produk, harga jual/beli, kategori departemen, dan stok fisik.</p>
            </div>
            
            <!-- Subnav Tabs -->
            <div class="flex items-center gap-2 bg-surface-container-low p-1.5 rounded-xl border border-outline-variant/30">
                <a href="inventory.php?tab=produk" class="px-4 py-2 rounded-lg font-label-md text-sm transition-all flex items-center gap-2 <?= ($tab === 'produk') ? 'bg-surface-container-lowest text-primary font-bold shadow-sm' : 'text-on-surface-variant hover:text-on-surface' ?>">
                    <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                    <span>Katalog Produk</span>
                </a>
                <a href="inventory.php?tab=kategori" class="px-4 py-2 rounded-lg font-label-md text-sm transition-all flex items-center gap-2 <?= ($tab === 'kategori') ? 'bg-surface-container-lowest text-primary font-bold shadow-sm' : 'text-on-surface-variant hover:text-on-surface' ?>">
                    <span class="material-symbols-outlined text-[18px]">category</span>
                    <span>Kategori & Unit</span>
                </a>
            </div>
        </div>

        <?php if ($tab === 'produk'): ?>
        <!-- ======================================================== -->
        <!-- TAB 1: KATALOG PRODUK                                    -->
        <!-- ======================================================== -->

        <!-- 4 Bento Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-space-md">
            <!-- Card 1: Total Produk -->
            <div class="p-space-md bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 flex items-start justify-between">
                <div>
                    <span class="font-label-sm text-xs uppercase tracking-wider text-outline font-semibold">Total Master Produk</span>
                    <div class="font-headline-xl text-2xl text-on-surface font-bold mt-1"><?= number_format($statTotal, 0, ',', '.') ?> Item</div>
                    <p class="text-xs text-on-surface-variant mt-2 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px] text-primary">inventory</span>
                        Tercatat dalam sistem
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-primary-fixed text-on-primary-fixed flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">format_list_bulleted</span>
                </div>
            </div>

            <!-- Card 2: Produk Aktif -->
            <div class="p-space-md bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 flex items-start justify-between">
                <div>
                    <span class="font-label-sm text-xs uppercase tracking-wider text-outline font-semibold">Produk Siap Jual</span>
                    <div class="font-headline-xl text-2xl text-tertiary font-bold mt-1"><?= number_format($statAktif, 0, ',', '.') ?> Aktif</div>
                    <p class="text-xs text-tertiary mt-2 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">check_circle</span>
                        Tayang di Terminal Kasir
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-on-tertiary-container/30 text-tertiary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">storefront</span>
                </div>
            </div>

            <!-- Card 3: Stok Menipis -->
            <div class="p-space-md bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 flex items-start justify-between">
                <div>
                    <span class="font-label-sm text-xs uppercase tracking-wider text-outline font-semibold">Peringatan Stok Menipis</span>
                    <div class="font-headline-xl text-2xl text-amber-600 font-bold mt-1"><?= number_format($statMenipis, 0, ',', '.') ?> SKU</div>
                    <p class="text-xs text-amber-700 mt-2 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">warning</span>
                        Perlu re-order ke supplier
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">production_quantity_limits</span>
                </div>
            </div>

            <!-- Card 4: Stok Habis -->
            <div class="p-space-md bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 flex items-start justify-between">
                <div>
                    <span class="font-label-sm text-xs uppercase tracking-wider text-outline font-semibold">Stok Kosong / Habis</span>
                    <div class="font-headline-xl text-2xl text-error font-bold mt-1"><?= number_format($statHabis, 0, ',', '.') ?> SKU</div>
                    <p class="text-xs text-error mt-2 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">block</span>
                        Tidak dapat ditransaksikan
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-error-container text-error flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">remove_shopping_cart</span>
                </div>
            </div>
        </div>

        <!-- Filter Bar & Control Tools -->
        <div class="bg-surface-container-lowest p-space-md rounded-2xl shadow-sm border border-outline-variant/30 space-y-3">
            <form method="GET" action="inventory.php" class="flex flex-col lg:flex-row items-stretch lg:items-center gap-3">
                <input type="hidden" name="tab" value="produk">

                <!-- Search Field -->
                <div class="relative flex-1">
                    <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                    <input type="text" name="q" id="product-search-input" value="<?= htmlspecialchars($searchQuery) ?>"
                        placeholder="Cari berdasarkan Nama Produk, SKU, atau Barcode... (F1)"
                        class="w-full h-11 pl-11 pr-24 rounded-xl bg-surface-container-low text-on-surface placeholder:text-outline font-body-md focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-primary border border-outline-variant/30 transition-all">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 font-label-sm text-xs bg-surface-container px-2 py-0.5 rounded text-outline font-mono">F1</span>
                </div>

                <!-- Filters -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <!-- Kategori -->
                    <select name="kategori" class="h-11 px-3 rounded-xl bg-surface-container-low text-on-surface font-label-md text-xs border border-outline-variant/30 focus:outline-none focus:ring-2 focus:ring-primary cursor-pointer">
                        <option value="all">Semua Kategori</option>
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['kode'] ?>" <?= ($filterCat === $c['kode']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nama_kategori']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Status Stok -->
                    <select name="stok" class="h-11 px-3 rounded-xl bg-surface-container-low text-on-surface font-label-md text-xs border border-outline-variant/30 focus:outline-none focus:ring-2 focus:ring-primary cursor-pointer">
                        <option value="all">Semua Stok</option>
                        <option value="tersedia" <?= ($filterStock === 'tersedia') ? 'selected' : '' ?>>Tersedia</option>
                        <option value="menipis" <?= ($filterStock === 'menipis') ? 'selected' : '' ?>>Stok Menipis</option>
                        <option value="habis" <?= ($filterStock === 'habis') ? 'selected' : '' ?>>Stok Habis</option>
                    </select>

                    <!-- Status Keaktifan -->
                    <select name="status" class="h-11 px-3 rounded-xl bg-surface-container-low text-on-surface font-label-md text-xs border border-outline-variant/30 focus:outline-none focus:ring-2 focus:ring-primary cursor-pointer">
                        <option value="all">Semua Status</option>
                        <option value="aktif" <?= ($filterActive === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= ($filterActive === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                    </select>

                    <!-- Filter Limit Baris -->
                    <select name="limit" class="h-11 px-3 rounded-xl bg-surface-container-low text-on-surface font-label-md text-xs border border-outline-variant/30 focus:outline-none focus:ring-2 focus:ring-primary cursor-pointer">
                        <option value="10" <?= ($limit === 10) ? 'selected' : '' ?>>10 / halaman</option>
                        <option value="30" <?= ($limit === 30) ? 'selected' : '' ?>>30 / halaman</option>
                        <option value="50" <?= ($limit === 50) ? 'selected' : '' ?>>50 / halaman</option>
                    </select>
                </div>

                <!-- Submit & Reset -->
                <div class="flex items-center gap-2">
                    <button type="submit" class="h-11 px-4 rounded-xl bg-primary text-on-primary font-label-md text-xs hover:bg-secondary transition-all flex items-center justify-center gap-1.5 shadow-sm">
                        <span class="material-symbols-outlined text-[18px]">filter_alt</span>
                        <span>Filter</span>
                    </button>
                    <a href="inventory.php?tab=produk" class="h-11 px-3 rounded-xl bg-surface-container-low hover:bg-surface-container text-on-surface-variant font-label-md text-xs transition-all flex items-center justify-center" title="Reset Filter">
                        <span class="material-symbols-outlined text-[18px]">restart_alt</span>
                    </a>
                </div>
            </form>

            <!-- Action Buttons: Tambah, Import, Export -->
            <div class="pt-3 border-t border-outline-variant/30 flex flex-wrap items-center justify-between gap-space-sm">
                <!-- Quick Category Chips -->
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-xs text-outline font-semibold">Kategori Cepat:</span>
                    <a href="inventory.php?tab=produk&kategori=all&limit=<?= $limit ?>" class="px-2.5 py-1 rounded-full text-xs <?= ($filterCat === 'all') ? 'bg-primary text-on-primary font-bold' : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high' ?>">Semua</a>
                    <?php foreach ($categories as $c): ?>
                    <a href="inventory.php?tab=produk&kategori=<?= $c['kode'] ?>&limit=<?= $limit ?>" class="px-2.5 py-1 rounded-full text-xs <?= ($filterCat === $c['kode']) ? 'bg-primary text-on-primary font-bold' : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high' ?>">
                        <?= htmlspecialchars($c['nama_kategori']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Action Button Group -->
                <div class="flex items-center gap-2">
                    <button type="button" onclick="openModal('modal-import')" class="px-3.5 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container text-on-surface font-label-md text-xs border border-outline-variant/30 transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">upload_file</span>
                        <span>Import</span>
                    </button>
                    <a href="api/export.php?type=produk" class="px-3.5 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container text-on-surface font-label-md text-xs border border-outline-variant/30 transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">download</span>
                        <span>Export CSV</span>
                    </a>
                    <button type="button" onclick="openAddProductModal()" class="px-4 py-2 rounded-xl bg-primary hover:bg-secondary text-on-primary font-label-lg text-xs shadow-sm transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">add_circle</span>
                        <span>+ Tambah Produk</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Master Data Product Table -->
        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 flex flex-col relative" id="product-table-card">
            
            <!-- Batch Action Toolbar -->
            <form id="form-batch" method="POST" action="api/produk_action.php">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" id="batch-action-type" value="batch_delete">
                
                <div id="batch-bar" class="hidden px-5 py-3 bg-secondary-fixed text-on-secondary-fixed flex items-center justify-between border-b border-outline-variant/30">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[20px]">check_box</span>
                        <span class="font-label-md text-sm font-bold"><span id="selected-count">0</span> Produk Terpilih</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="submitBatch('batch_status')" class="px-3 py-1.5 rounded-lg bg-surface-container-lowest text-on-surface font-label-sm text-xs shadow-sm hover:bg-surface-container">Ubah Status</button>
                        <button type="button" onclick="submitBatch('batch_delete')" class="px-3 py-1.5 rounded-lg bg-error text-on-error font-label-sm text-xs shadow-sm hover:bg-on-error-container">Hapus Terpilih</button>
                    </div>
                </div>

                <div class="overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" id="product-table-container">
                    <table class="w-full text-left border-collapse min-w-[1100px]">
                        <thead>
                            <tr class="bg-surface-container-low text-outline font-label-sm text-xs uppercase tracking-wider border-b border-outline-variant/30">
                                <th class="py-3.5 px-4 w-12 text-center">
                                    <input type="checkbox" id="check-all" class="w-4 h-4 rounded text-primary focus:ring-primary cursor-pointer">
                                </th>
                                <th class="py-3.5 px-4 font-semibold">Produk & Gambar</th>
                                <th class="py-3.5 px-4 font-semibold">SKU & Barcode</th>
                                <th class="py-3.5 px-4 font-semibold">Kategori</th>
                                <th class="py-3.5 px-4 font-semibold text-right">Harga Beli</th>
                                <th class="py-3.5 px-4 font-semibold text-right">Harga Jual</th>
                                <th class="py-3.5 px-4 font-semibold text-center">Margin</th>
                                <th class="py-3.5 px-4 font-semibold text-center">Stok</th>
                                <th class="py-3.5 px-4 font-semibold text-center">Status Stok</th>
                                <th class="py-3.5 px-4 font-semibold text-center">Status</th>
                                <th class="py-3.5 px-4 font-semibold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20">
                            <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="11" class="py-12 text-center text-outline">
                                    <span class="material-symbols-outlined text-[42px] mb-2">inventory_2</span>
                                    <p class="font-headline-sm text-sm font-semibold text-on-surface-variant">Tidak ada produk ditemukan.</p>
                                    <p class="text-xs">Coba sesuaikan kata kunci pencarian atau filter kategori.</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($products as $p): 
                                $buy = (float)$p['harga_beli'];
                                $sell = (float)$p['harga_jual'];
                                $margin = ($sell > 0) ? round((($sell - $buy) / $sell) * 100, 1) : 0;
                                $stok = (int)$p['stok'];
                                $minStok = (int)$p['min_stok'];
                                $pDataJson = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr class="hover:bg-surface-container-low/60 transition-colors">
                                <td class="py-3 px-4 text-center">
                                    <input type="checkbox" name="ids[]" value="<?= $p['id'] ?>" class="row-checkbox w-4 h-4 rounded text-primary focus:ring-primary cursor-pointer">
                                </td>
                                <!-- Produk & Gambar -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-lg bg-surface-container-low flex-shrink-0 overflow-hidden border border-outline-variant/30 flex items-center justify-center">
                                            <?php if (!empty($p['gambar'])): ?>
                                                <img src="<?= htmlspecialchars($p['gambar']) ?>" alt="<?= htmlspecialchars($p['nama_produk']) ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <span class="material-symbols-outlined text-[24px] text-outline/50">shopping_bag</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex flex-col min-w-0">
                                            <span class="font-label-lg text-sm text-on-surface font-semibold truncate max-w-[200px]" title="<?= htmlspecialchars($p['nama_produk']) ?>">
                                                <?= htmlspecialchars($p['nama_produk']) ?>
                                            </span>
                                            <span class="text-xs text-on-surface-variant">
                                                Satuan: <?= htmlspecialchars($p['satuan_nama'] ?: 'Pcs') ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <!-- SKU & Barcode -->
                                <td class="py-3 px-4">
                                    <div class="flex flex-col">
                                        <span class="font-label-md text-xs text-primary font-bold font-mono"><?= htmlspecialchars($p['sku']) ?></span>
                                        <span class="text-xs text-outline font-mono flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[13px]">barcode</span>
                                            <?= htmlspecialchars($p['barcode'] ?: '-') ?>
                                        </span>
                                    </div>
                                </td>
                                <!-- Kategori -->
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-surface-container-high text-on-surface-variant font-label-sm text-xs font-semibold">
                                        <?= htmlspecialchars($p['nama_kategori']) ?>
                                    </span>
                                </td>
                                <!-- Harga Beli -->
                                <td class="py-3 px-4 text-right font-mono text-xs text-on-surface-variant">
                                    <?= format_rupiah($buy) ?>
                                </td>
                                <!-- Harga Jual -->
                                <td class="py-3 px-4 text-right font-mono text-xs font-bold text-on-surface">
                                    <?= format_rupiah($sell) ?>
                                </td>
                                <!-- Margin -->
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold font-mono <?= ($margin >= 20) ? 'bg-on-tertiary-container/30 text-tertiary' : 'bg-surface-container text-on-surface-variant' ?>">
                                        +<?= $margin ?>%
                                    </span>
                                </td>
                                <!-- Stok Fisik -->
                                <td class="py-3 px-4 text-center font-mono font-bold text-sm text-on-surface">
                                    <?= $stok ?>
                                </td>
                                <!-- Status Stok -->
                                <td class="py-3 px-4 text-center">
                                    <?php if ($stok <= 0): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-error-container text-error text-xs font-semibold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-error"></span> Habis
                                        </span>
                                    <?php elseif ($stok <= $minStok): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs font-semibold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span> Menipis
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-on-tertiary-container/30 text-tertiary text-xs font-semibold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-tertiary"></span> Tersedia
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <!-- Status Aktif -->
                                <td class="py-3 px-4 text-center">
                                    <a href="api/produk_action.php?action=toggle_status&id=<?= $p['id'] ?>&csrf_token=<?= csrf_token() ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold <?= ($p['status'] === 'aktif') ? 'bg-tertiary-fixed text-on-tertiary-fixed-variant' : 'bg-surface-container text-outline' ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?= ($p['status'] === 'aktif') ? 'bg-tertiary' : 'bg-outline' ?>"></span>
                                        <?= ucfirst($p['status']) ?>
                                    </a>
                                </td>
                                <!-- Aksi -->
                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" onclick='openEditProductModal(<?= $pDataJson ?>)' class="p-1.5 rounded-lg text-outline hover:text-primary hover:bg-surface-container transition-colors" title="Ubah Produk">
                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                        </button>
                                        <a href="api/produk_action.php?action=delete&id=<?= $p['id'] ?>&csrf_token=<?= csrf_token() ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?');" class="p-1.5 rounded-lg text-outline hover:text-error hover:bg-error-container transition-colors" title="Hapus Produk">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <!-- Sticky Horizontal Scrollbar Bar at bottom of screen -->
            <div id="product-table-slider-bar" class="sticky bottom-0 z-20 w-full bg-surface-container-lowest/95 backdrop-blur-md border-t border-outline-variant/30 px-4 py-2 flex items-center justify-between gap-3 shadow-md">
                <div class="flex items-center gap-1.5 shrink-0">
                    <span class="text-xs text-outline font-semibold flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px] text-primary">swap_horiz</span>
                        <span class="hidden sm:inline">Slider:</span>
                    </span>
                    <button type="button" onclick="scrollProductTable(-200)" title="Geser Tabel ke Kiri" class="w-7 h-7 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface flex items-center justify-center transition-all cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                    </button>
                    <button type="button" onclick="scrollProductTable(200)" title="Geser Tabel ke Kanan" class="w-7 h-7 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface flex items-center justify-center transition-all cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                    </button>
                </div>

                <!-- Synchronized Horizontal Scroll Track -->
                <div id="product-table-track-wrapper" class="flex-1 overflow-x-auto py-1 table-scrollbar-thin">
                    <div id="product-table-track-dummy" style="height: 1px;"></div>
                </div>

                <div class="flex items-center gap-1.5 shrink-0 text-xs">
                    <button type="button" onclick="scrollProductTableTo('start')" class="px-2 py-1 rounded bg-surface-container hover:bg-surface-container-high text-on-surface-variant hover:text-on-surface transition-colors cursor-pointer">
                        Awal
                    </button>
                    <button type="button" onclick="scrollProductTableTo('end')" class="px-2 py-1 rounded bg-surface-container hover:bg-surface-container-high text-primary font-semibold transition-colors cursor-pointer">
                        Aksi &gt;
                    </button>
                </div>
            </div>

            <!-- Table Pagination & Footer Toolbar -->
            <div class="px-5 py-3.5 bg-surface-container-low/50 border-t border-outline-variant/30 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs rounded-b-2xl">
                <!-- Left: Info Data & Per-page Limit selector -->
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-on-surface-variant">
                        Menampilkan <span class="font-bold font-mono text-on-surface"><?= $startItem ?> - <?= $endItem ?></span> dari <span class="font-bold font-mono text-on-surface"><?= $totalFilteredProducts ?></span> produk
                    </span>
                    <div class="flex items-center gap-1.5 border-l border-outline-variant/40 pl-3">
                        <label for="limit-select" class="text-outline font-semibold">Tampilkan:</label>
                        <select id="limit-select" onchange="changeLimit(this.value)" class="h-8 px-2 rounded-lg bg-surface-container text-on-surface font-semibold border border-outline-variant/40 focus:ring-1 focus:ring-primary cursor-pointer">
                            <option value="10" <?= ($limit === 10) ? 'selected' : '' ?>>10 baris</option>
                            <option value="30" <?= ($limit === 30) ? 'selected' : '' ?>>30 baris</option>
                            <option value="50" <?= ($limit === 50) ? 'selected' : '' ?>>50 baris</option>
                        </select>
                    </div>
                </div>

                <!-- Right: Pagination Buttons -->
                <div class="flex items-center gap-1">
                    <!-- Prev Button -->
                    <?php if ($page > 1): ?>
                    <a href="<?= $basePaginationUrl ?>&page=<?= $page - 1 ?>" class="px-2.5 py-1.5 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                        <span class="hidden sm:inline">Sebelumnya</span>
                    </a>
                    <?php else: ?>
                    <span class="px-2.5 py-1.5 rounded-lg bg-surface-container/50 text-outline/50 cursor-not-allowed flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                        <span class="hidden sm:inline">Sebelumnya</span>
                    </span>
                    <?php endif; ?>

                    <!-- Page Numbers -->
                    <div class="flex items-center gap-1">
                        <?php
                        $maxLinks = 5;
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $startPage + $maxLinks - 1);
                        if ($endPage - $startPage < $maxLinks - 1) {
                            $startPage = max(1, $endPage - $maxLinks + 1);
                        }
                        if ($startPage > 1) {
                            echo '<a href="' . $basePaginationUrl . '&page=1" class="w-8 h-8 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface flex items-center justify-center font-bold font-mono">1</a>';
                            if ($startPage > 2) {
                                echo '<span class="text-outline px-1">...</span>';
                            }
                        }
                        for ($i = $startPage; $i <= $endPage; $i++) {
                            if ($i === $page) {
                                echo '<span class="w-8 h-8 rounded-lg bg-primary text-on-primary font-bold font-mono flex items-center justify-center shadow-xs">' . $i . '</span>';
                            } else {
                                echo '<a href="' . $basePaginationUrl . '&page=' . $i . '" class="w-8 h-8 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface flex items-center justify-center font-bold font-mono">' . $i . '</a>';
                            }
                        }
                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<span class="text-outline px-1">...</span>';
                            }
                            echo '<a href="' . $basePaginationUrl . '&page=' . $totalPages . '" class="w-8 h-8 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface flex items-center justify-center font-bold font-mono">' . $totalPages . '</a>';
                        }
                        ?>
                    </div>

                    <!-- Next Button -->
                    <?php if ($page < $totalPages): ?>
                    <a href="<?= $basePaginationUrl ?>&page=<?= $page + 1 ?>" class="px-2.5 py-1.5 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold transition-colors flex items-center gap-1">
                        <span class="hidden sm:inline">Berikutnya</span>
                        <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                    </a>
                    <?php else: ?>
                    <span class="px-2.5 py-1.5 rounded-lg bg-surface-container/50 text-outline/50 cursor-not-allowed flex items-center gap-1">
                        <span class="hidden sm:inline">Berikutnya</span>
                        <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- ======================================================== -->
        <!-- TAB 2: KELOLA KATEGORI & SATUAN                          -->
        <!-- ======================================================== -->

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-start">
            
            <!-- Kolom Kiri: Tabel Kategori (8 Kolom) -->
            <div class="lg:col-span-8 space-y-space-md">
                <div class="bg-surface-container-lowest p-space-md rounded-2xl shadow-sm border border-outline-variant/30 flex items-center justify-between">
                    <div>
                        <h2 class="font-headline-md text-lg text-on-surface font-bold">Departemen & Kategori Produk</h2>
                        <p class="text-xs text-on-surface-variant">Kelompokkan produk untuk memudahkan navigasi kasir.</p>
                    </div>
                    <button type="button" onclick="openAddCategoryModal()" class="px-4 py-2 rounded-xl bg-primary hover:bg-secondary text-on-primary font-label-md text-xs shadow-sm transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        <span>Tambah Kategori</span>
                    </button>
                </div>

                <div id="tab-content-kategori" class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[700px]">
                            <thead>
                                <tr class="bg-surface-container-low text-outline font-label-sm text-xs uppercase tracking-wider border-b border-outline-variant/30">
                                    <th class="py-3 px-4 whitespace-nowrap">Ikon & Kategori</th>
                                    <th class="py-3 px-4 whitespace-nowrap">Kode</th>
                                    <th class="py-3 px-4 min-w-[200px]">Deskripsi</th>
                                    <th class="py-3 px-4 text-center whitespace-nowrap">Jumlah Produk</th>
                                    <th class="py-3 px-4 text-center whitespace-nowrap">Status</th>
                                    <th class="py-3 px-4 text-right whitespace-nowrap">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/20 text-xs">
                                <?php foreach ($categories as $cat): 
                                    $catJson = htmlspecialchars(json_encode($cat), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr class="hover:bg-surface-container-low/60 transition-colors">
                                    <td class="py-3 px-4 font-semibold text-on-surface flex items-center gap-2.5 whitespace-nowrap">
                                        <div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
                                            <span class="material-symbols-outlined text-[18px]"><?= htmlspecialchars($cat['ikon']) ?></span>
                                        </div>
                                        <span><?= htmlspecialchars($cat['nama_kategori']) ?></span>
                                    </td>
                                    <td class="py-3 px-4 font-mono font-bold text-primary whitespace-nowrap">
                                        <?= htmlspecialchars($cat['kode']) ?>
                                    </td>
                                    <td class="py-3 px-4 text-on-surface-variant max-w-xs truncate">
                                        <?= htmlspecialchars($cat['deskripsi'] ?: '-') ?>
                                    </td>
                                    <td class="py-3 px-4 text-center font-bold font-mono whitespace-nowrap">
                                        <?= $cat['total_produk'] ?> Item
                                    </td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= ($cat['status'] === 'aktif') ? 'bg-tertiary-fixed text-on-tertiary-fixed-variant' : 'bg-surface-container text-outline' ?>">
                                            <?= strtoupper($cat['status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" onclick='openEditCategoryModal(<?= $catJson ?>)' class="p-1 rounded-lg text-outline hover:text-primary hover:bg-surface-container">
                                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                            </button>
                                            <a href="api/kategori_action.php?action=delete_kategori&id=<?= $cat['id'] ?>&csrf_token=<?= csrf_token() ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini?');" class="p-1 rounded-lg text-outline hover:text-error hover:bg-error-container">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Satuan Unit & Pengaturan (4 Kolom) -->
            <div class="lg:col-span-4 space-y-space-md">
                <div class="bg-surface-container-lowest p-space-md rounded-2xl shadow-sm border border-outline-variant/30 space-y-4">
                    <div class="border-b border-outline-variant/30 pb-3">
                        <h3 class="font-headline-md text-base text-on-surface font-bold">Satuan Unit Kemasan</h3>
                        <p class="text-xs text-on-surface-variant">Unit acuan produk (Pcs, Botol, Pack, Kg).</p>
                    </div>

                    <!-- Form Tambah Satuan -->
                    <form method="POST" action="api/kategori_action.php" class="space-y-3">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="add_satuan">
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1">Nama Satuan</label>
                            <input type="text" name="nama_satuan" required placeholder="Contoh: Pieces, Box" class="w-full px-3 py-2 rounded-xl bg-surface-container-low text-xs border border-outline-variant/30 focus:outline-none focus:ring-1 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1">Singkatan / Simbol</label>
                            <input type="text" name="singkatan" required placeholder="Contoh: Pcs, Box, Kg" class="w-full px-3 py-2 rounded-xl bg-surface-container-low text-xs border border-outline-variant/30 focus:outline-none focus:ring-1 focus:ring-primary">
                        </div>
                        <button type="submit" class="w-full py-2 bg-primary hover:bg-secondary text-on-primary rounded-xl text-xs font-bold transition-all shadow-sm">
                            + Tambah Satuan Baru
                        </button>
                    </form>

                    <!-- Daftar Satuan -->
                    <div class="pt-3 border-t border-outline-variant/30 space-y-1.5">
                        <span class="text-xs text-outline font-semibold block mb-1">Daftar Satuan Aktif:</span>
                        <?php foreach ($units as $u): ?>
                        <div class="flex items-center justify-between p-2 rounded-lg bg-surface-container-low text-xs">
                             <span class="font-semibold text-on-surface"><?= htmlspecialchars($u['nama_satuan']) ?> (<?= htmlspecialchars($u['singkatan']) ?>)</span>
                            <a href="api/kategori_action.php?action=delete_satuan&id=<?= $u['id'] ?>&csrf_token=<?= csrf_token() ?>" onclick="return confirm('Hapus satuan ini?');" class="text-outline hover:text-error">
                                <span class="material-symbols-outlined text-[16px]">delete</span>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>

        <?php endif; ?>

    </main>
</div>

<!-- ======================================================== -->
<!-- MODAL TAMBAH / EDIT PRODUK                               -->
<!-- ======================================================== -->
<div id="modal-product" class="fixed inset-0 z-50 bg-inverse-surface/65 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-surface-container-lowest w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden border border-outline-variant/30 flex flex-col max-h-[90vh]">
        <div class="p-space-lg bg-surface-container-low border-b border-outline-variant/30 flex items-center justify-between">
            <h3 class="font-headline-md text-lg text-on-surface font-bold" id="modal-prod-title">Tambah Produk Baru</h3>
            <button type="button" onclick="closeModal('modal-product')" class="text-outline hover:text-on-surface p-1 rounded-lg">
                <span class="material-symbols-outlined text-[24px]">close</span>
            </button>
        </div>

        <form method="POST" action="api/produk_action.php" enctype="multipart/form-data" class="p-space-lg overflow-y-auto space-y-4">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" id="prod-form-action" value="add">
            <input type="hidden" name="id" id="prod-form-id" value="">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Nama Produk -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-on-surface mb-1">Nama Produk *</label>
                    <input type="text" name="nama_produk" id="input-prod-name" required placeholder="Contoh: Kopi Susu Aren Botol 250ml"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-surface-container-low text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- SKU -->
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label class="text-xs font-semibold text-on-surface">Kode SKU *</label>
                        <button type="button" onclick="generateSku()" class="text-[11px] text-primary hover:underline font-semibold">Auto-Generate</button>
                    </div>
                    <input type="text" name="sku" id="input-prod-sku" required placeholder="SKU-MNM-001"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-surface-container-low font-mono text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Barcode -->
                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">Barcode (Opsional)</label>
                    <input type="text" name="barcode" id="input-prod-barcode" placeholder="899276100142"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-surface-container-low font-mono text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Kategori -->
                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">Kategori *</label>
                    <select name="kategori_id" id="input-prod-cat" required class="w-full px-3.5 py-2.5 rounded-xl bg-surface-container-low text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary">
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Satuan -->
                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">Satuan Unit</label>
                    <select name="satuan_id" id="input-prod-unit" class="w-full px-3.5 py-2.5 rounded-xl bg-surface-container-low text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">Pilih Satuan</option>
                        <?php foreach ($units as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama_satuan']) ?> (<?= htmlspecialchars($u['singkatan']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Harga Beli -->
                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">Harga Beli / Modal (Rp) *</label>
                    <input type="number" name="harga_beli" id="input-prod-buy" required step="100" min="0" value="0" oninput="calcMargin()"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-surface-container-low font-mono text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Harga Jual -->
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label class="text-xs font-semibold text-on-surface">Harga Jual Kasir (Rp) *</label>
                        <span id="margin-preview" class="text-[11px] font-bold text-tertiary">Margin: 0%</span>
                    </div>
                    <input type="number" name="harga_jual" id="input-prod-sell" required step="100" min="0" value="0" oninput="calcMargin()"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-surface-container-low font-mono text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Stok Awal -->
                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">Stok Fisik Awal *</label>
                    <input type="number" name="stok" id="input-prod-stock" required min="0" value="10"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-surface-container-low font-mono text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Minimum Stok Alert -->
                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1">Batas Minimum Peringatan Stok</label>
                    <input type="number" name="min_stok" id="input-prod-min-stock" min="1" value="5"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-surface-container-low font-mono text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Foto / Gambar Produk (Upload File & URL) -->
                <div class="sm:col-span-2 space-y-2">
                    <label class="block text-xs font-semibold text-on-surface">Foto / Gambar Produk (Opsional)</label>
                    
                    <div class="flex flex-col sm:flex-row gap-4 items-start p-3.5 rounded-xl bg-surface-container-low border border-outline-variant/40">
                        <!-- Preview Box -->
                        <div class="w-24 h-24 rounded-xl bg-surface-container-high border border-outline-variant/40 flex items-center justify-center overflow-hidden shrink-0 relative">
                            <img id="prod-img-preview" src="" alt="Pratinjau" class="w-full h-full object-cover hidden">
                            <div id="prod-img-placeholder" class="flex flex-col items-center justify-center text-on-surface-variant/60">
                                <span class="material-symbols-outlined text-[30px]">image</span>
                                <span class="text-[10px] mt-0.5">Tanpa Foto</span>
                            </div>
                            <button type="button" id="btn-remove-img" onclick="clearProductImage()" title="Hapus Gambar" class="absolute top-1 right-1 w-6 h-6 rounded-full bg-error text-on-error flex items-center justify-center hidden opacity-90 hover:opacity-100 transition-opacity shadow-sm">
                                <span class="material-symbols-outlined text-[14px]">close</span>
                            </button>
                        </div>

                        <!-- Upload & URL Controls -->
                        <div class="flex-1 space-y-2.5 w-full">
                            <!-- File Upload Input -->
                            <div>
                                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-surface-container hover:bg-surface-container-highest text-on-surface text-xs font-semibold cursor-pointer border border-outline-variant/50 transition-colors shadow-xs">
                                    <span class="material-symbols-outlined text-[18px] text-primary">upload_file</span>
                                    <span>Pilih Berkas Gambar</span>
                                    <input type="file" name="gambar_file" id="input-prod-file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" onchange="previewSelectedFile(this)">
                                </label>
                                <span id="file-chosen-text" class="text-[11px] text-on-surface-variant ml-2 truncate inline-block max-w-[200px] align-middle">Belum ada file dipilih</span>
                                <p class="text-[10px] text-on-surface-variant/70 mt-1">Mendukung format JPG, PNG, WEBP, atau GIF (Maks. 3 MB)</p>
                            </div>

                            <!-- Or URL Fallback Input -->
                            <div class="pt-2 border-t border-outline-variant/30">
                                <label for="input-prod-img" class="block text-[11px] text-on-surface-variant mb-1">Atau tempel URL gambar eksternal:</label>
                                <input type="text" name="gambar" id="input-prod-img" placeholder="https://..." oninput="previewUrlImage(this.value)"
                                    class="w-full px-3 py-1.5 rounded-lg bg-surface-container-lowest text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary placeholder:text-outline">
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="hapus_gambar" id="input-hapus-gambar" value="0">
                </div>

                <!-- Status Produk -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-on-surface mb-1">Status Keaktifan</label>
                    <select name="status" id="input-prod-status" class="w-full px-3.5 py-2.5 rounded-xl bg-surface-container-low text-xs border border-outline-variant/40">
                        <option value="aktif">Aktif (Dapat ditransaksikan)</option>
                        <option value="nonaktif">Nonaktif (Diarsipkan)</option>
                    </select>
                </div>
            </div>

            <div class="pt-4 border-t border-outline-variant/30 flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('modal-product')" class="px-4 py-2 rounded-xl bg-surface-container text-on-surface text-xs font-semibold">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-primary hover:bg-secondary text-on-primary text-xs font-bold shadow-sm">Simpan Produk</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL TAMBAH / EDIT KATEGORI                             -->
<!-- ======================================================== -->
<div id="modal-category" class="fixed inset-0 z-50 bg-inverse-surface/65 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-surface-container-lowest w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border border-outline-variant/30 flex flex-col">
        <div class="p-space-lg bg-surface-container-low border-b border-outline-variant/30 flex items-center justify-between">
            <h3 class="font-headline-md text-base text-on-surface font-bold" id="modal-cat-title">Tambah Kategori</h3>
            <button type="button" onclick="closeModal('modal-category')" class="text-outline hover:text-on-surface p-1 rounded-lg">
                <span class="material-symbols-outlined text-[22px]">close</span>
            </button>
        </div>

        <form method="POST" action="api/kategori_action.php" class="p-space-lg space-y-4">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" id="cat-form-action" value="add_kategori">
            <input type="hidden" name="id" id="cat-form-id" value="">

            <div>
                <label class="block text-xs font-semibold text-on-surface mb-1">Nama Kategori *</label>
                <input type="text" name="nama_kategori" id="input-cat-name" required placeholder="Contoh: Makanan Dingin" class="w-full px-3.5 py-2.5 rounded-xl bg-surface-container-low text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary">
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface mb-1">Kode Departemen (Huruf Kapital) *</label>
                <input type="text" name="kode" id="input-cat-code" required placeholder="MAKANAN" class="w-full px-3.5 py-2.5 rounded-xl bg-surface-container-low font-mono uppercase text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary">
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface mb-1">Ikon Material</label>
                <input type="text" name="ikon" id="input-cat-icon" value="category" placeholder="local_bar, bakery_dining, rice_bowl" class="w-full px-3.5 py-2.5 rounded-xl bg-surface-container-low font-mono text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary">
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface mb-1">Deskripsi Kategori</label>
                <textarea name="deskripsi" id="input-cat-desc" rows="2" placeholder="Keterangan singkat kelompok produk" class="w-full px-3.5 py-2 rounded-xl bg-surface-container-low text-xs border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface mb-1">Status</label>
                <select name="status" id="input-cat-status" class="w-full px-3.5 py-2 rounded-xl bg-surface-container-low text-xs border border-outline-variant/40">
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
            </div>

            <div class="pt-3 border-t border-outline-variant/30 flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('modal-category')" class="px-4 py-2 rounded-xl bg-surface-container text-on-surface text-xs font-semibold">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-primary hover:bg-secondary text-on-primary text-xs font-bold shadow-sm">Simpan Kategori</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL IMPORT PRODUK (CSV)                                 -->
<!-- ======================================================== -->
<div id="modal-import" class="fixed inset-0 z-50 bg-inverse-surface/65 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-surface-container-lowest w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden border border-outline-variant/30 flex flex-col">
        <div class="p-space-lg bg-surface-container-low border-b border-outline-variant/30 flex items-center justify-between">
            <h3 class="font-headline-md text-base text-on-surface font-bold">Import Data Produk</h3>
            <button type="button" onclick="closeModal('modal-import')" class="text-outline hover:text-on-surface p-1 rounded-lg">
                <span class="material-symbols-outlined text-[22px]">close</span>
            </button>
        </div>

        <form method="POST" action="api/export.php?action=import" enctype="multipart/form-data" class="p-space-lg space-y-4">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <p class="text-xs text-on-surface-variant leading-relaxed">
                Unggah berkas spreadsheet format CSV untuk menambahkan produk secara massal. Pastikan header sesuai dengan template.
            </p>

            <div class="p-6 border-2 border-dashed border-outline-variant rounded-2xl text-center space-y-2 bg-surface-container-low">
                <span class="material-symbols-outlined text-[36px] text-primary">cloud_upload</span>
                <p class="text-xs font-semibold text-on-surface">Pilih Berkas CSV</p>
                <input type="file" name="csv_file" accept=".csv" required class="text-xs text-outline mx-auto block">
            </div>

            <div class="flex items-center justify-between text-xs pt-2">
                <a href="api/export.php?type=template" class="text-primary font-semibold hover:underline flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">file_download</span>
                    <span>Unduh Template CSV</span>
                </a>
                <span class="text-outline">Maks. 2MB</span>
            </div>

            <div class="pt-3 border-t border-outline-variant/30 flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('modal-import')" class="px-4 py-2 rounded-xl bg-surface-container text-on-surface text-xs font-semibold">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-primary hover:bg-secondary text-on-primary text-xs font-bold shadow-sm">Mulai Import</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Margin preview calculator
    function calcMargin() {
        const buy = parseFloat(document.getElementById('input-prod-buy').value) || 0;
        const sell = parseFloat(document.getElementById('input-prod-sell').value) || 0;
        const preview = document.getElementById('margin-preview');
        if (sell > 0) {
            const margin = (((sell - buy) / sell) * 100).toFixed(1);
            preview.textContent = `Margin: ${margin}%`;
        } else {
            preview.textContent = 'Margin: 0%';
        }
    }

    // SKU auto generator
    function generateSku() {
        const catSelect = document.getElementById('input-prod-cat');
        const catText = catSelect.options[catSelect.selectedIndex].text.substring(0, 3).toUpperCase();
        const rand = Math.floor(100 + Math.random() * 900);
        document.getElementById('input-prod-sku').value = `SKU-${catText}-${rand}`;
    }

    // Image Preview & Upload Helpers
    function previewSelectedFile(input) {
        const preview = document.getElementById('prod-img-preview');
        const placeholder = document.getElementById('prod-img-placeholder');
        const removeBtn = document.getElementById('btn-remove-img');
        const chosenText = document.getElementById('file-chosen-text');
        const hapusInput = document.getElementById('input-hapus-gambar');

        if (input.files && input.files[0]) {
            const file = input.files[0];
            chosenText.textContent = file.name;
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                placeholder.classList.add('hidden');
                removeBtn.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
            hapusInput.value = '0';
        }
    }

    function previewUrlImage(url) {
        const preview = document.getElementById('prod-img-preview');
        const placeholder = document.getElementById('prod-img-placeholder');
        const removeBtn = document.getElementById('btn-remove-img');
        const hapusInput = document.getElementById('input-hapus-gambar');

        const trimmed = (url || '').trim();
        if (trimmed !== '') {
            preview.src = trimmed;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
            removeBtn.classList.remove('hidden');
            hapusInput.value = '0';
        } else {
            const fileInput = document.getElementById('input-prod-file');
            if (!fileInput.files || fileInput.files.length === 0) {
                clearProductImage();
            }
        }
    }

    function clearProductImage() {
        const preview = document.getElementById('prod-img-preview');
        const placeholder = document.getElementById('prod-img-placeholder');
        const removeBtn = document.getElementById('btn-remove-img');
        const fileInput = document.getElementById('input-prod-file');
        const urlInput = document.getElementById('input-prod-img');
        const chosenText = document.getElementById('file-chosen-text');
        const hapusInput = document.getElementById('input-hapus-gambar');

        fileInput.value = '';
        urlInput.value = '';
        preview.src = '';
        preview.classList.add('hidden');
        placeholder.classList.remove('hidden');
        removeBtn.classList.add('hidden');
        chosenText.textContent = 'Belum ada file dipilih';
        hapusInput.value = '1';
    }

    // Modal Add Product
    function openAddProductModal() {
        document.getElementById('modal-prod-title').textContent = 'Tambah Produk Baru';
        document.getElementById('prod-form-action').value = 'add';
        document.getElementById('prod-form-id').value = '';
        document.getElementById('input-prod-name').value = '';
        document.getElementById('input-prod-sku').value = '';
        document.getElementById('input-prod-barcode').value = '';
        document.getElementById('input-prod-buy').value = '0';
        document.getElementById('input-prod-sell').value = '0';
        document.getElementById('input-prod-stock').value = '10';
        document.getElementById('input-prod-min-stock').value = '5';
        clearProductImage();
        document.getElementById('input-hapus-gambar').value = '0';
        calcMargin();
        openModal('modal-product');
    }

    // Modal Edit Product
    function openEditProductModal(prod) {
        document.getElementById('modal-prod-title').textContent = 'Edit Informasi Produk';
        document.getElementById('prod-form-action').value = 'edit';
        document.getElementById('prod-form-id').value = prod.id;
        document.getElementById('input-prod-name').value = prod.nama_produk;
        document.getElementById('input-prod-sku').value = prod.sku;
        document.getElementById('input-prod-barcode').value = prod.barcode || '';
        document.getElementById('input-prod-cat').value = prod.kategori_id;
        document.getElementById('input-prod-unit').value = prod.satuan_id || '';
        document.getElementById('input-prod-buy').value = prod.harga_beli;
        document.getElementById('input-prod-sell').value = prod.harga_jual;
        document.getElementById('input-prod-stock').value = prod.stok;
        document.getElementById('input-prod-min-stock').value = prod.min_stok;
        document.getElementById('input-prod-status').value = prod.status;
        document.getElementById('input-hapus-gambar').value = '0';
        
        // Reset file upload
        document.getElementById('input-prod-file').value = '';
        
        const preview = document.getElementById('prod-img-preview');
        const placeholder = document.getElementById('prod-img-placeholder');
        const removeBtn = document.getElementById('btn-remove-img');
        const chosenText = document.getElementById('file-chosen-text');
        const urlInput = document.getElementById('input-prod-img');

        if (prod.gambar) {
            preview.src = prod.gambar;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
            removeBtn.classList.remove('hidden');
            chosenText.textContent = prod.gambar.startsWith('public/uploads') ? 'Foto lokal tersimpan' : 'URL gambar aktif';
            urlInput.value = prod.gambar.startsWith('http') ? prod.gambar : '';
        } else {
            preview.src = '';
            preview.classList.add('hidden');
            placeholder.classList.remove('hidden');
            removeBtn.classList.add('hidden');
            chosenText.textContent = 'Belum ada file dipilih';
            urlInput.value = '';
        }

        calcMargin();
        openModal('modal-product');
    }

    // Category Modals
    function openAddCategoryModal() {
        document.getElementById('modal-cat-title').textContent = 'Tambah Kategori Baru';
        document.getElementById('cat-form-action').value = 'add_kategori';
        document.getElementById('cat-form-id').value = '';
        document.getElementById('input-cat-name').value = '';
        document.getElementById('input-cat-code').value = '';
        document.getElementById('input-cat-icon').value = 'category';
        document.getElementById('input-cat-desc').value = '';
        openModal('modal-category');
    }

    function openEditCategoryModal(cat) {
        document.getElementById('modal-cat-title').textContent = 'Edit Kategori';
        document.getElementById('cat-form-action').value = 'edit_kategori';
        document.getElementById('cat-form-id').value = cat.id;
        document.getElementById('input-cat-name').value = cat.nama_kategori;
        document.getElementById('input-cat-code').value = cat.kode;
        document.getElementById('input-cat-icon').value = cat.ikon;
        document.getElementById('input-cat-desc').value = cat.deskripsi || '';
        document.getElementById('input-cat-status').value = cat.status;
        openModal('modal-category');
    }

    // Batch Actions logic
    const checkAll = document.getElementById('check-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const batchBar = document.getElementById('batch-bar');
    const selectedCount = document.getElementById('selected-count');

    function updateBatch() {
        const checked = document.querySelectorAll('.row-checkbox:checked').length;
        if (selectedCount) selectedCount.textContent = checked;
        if (batchBar) {
            if (checked > 0) batchBar.classList.remove('hidden');
            else batchBar.classList.add('hidden');
        }
    }

    if (checkAll) {
        checkAll.addEventListener('change', (e) => {
            rowCheckboxes.forEach(cb => cb.checked = e.target.checked);
            updateBatch();
        });
    }

    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            updateBatch();
            if (checkAll) checkAll.checked = (rowCheckboxes.length === document.querySelectorAll('.row-checkbox:checked').length);
        });
    });

    function submitBatch(actionType) {
        if (!confirm('Proses aksi massal pada produk terpilih?')) return;
        document.getElementById('batch-action-type').value = actionType;
        document.getElementById('form-batch').submit();
    }

    // Filter Limit Change Handler
    function changeLimit(newLimit) {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', 'produk');
        url.searchParams.set('limit', newLimit);
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    }

    // Product Table Horizontal Scroll Slider & Controls
    function scrollProductTable(delta) {
        const el = document.getElementById('product-table-container');
        if (el) el.scrollBy({ left: delta, behavior: 'smooth' });
    }

    function scrollProductTableTo(pos) {
        const el = document.getElementById('product-table-container');
        if (el) el.scrollTo({ left: pos === 'start' ? 0 : el.scrollWidth, behavior: 'smooth' });
    }

    function initTableScrollSlider() {
        const tableContainer = document.getElementById('product-table-container');
        const trackWrapper = document.getElementById('product-table-track-wrapper');
        const trackDummy = document.getElementById('product-table-track-dummy');
        const sliderBar = document.getElementById('product-table-slider-bar');
        if (!tableContainer || !trackWrapper || !trackDummy) return;

        function updateSliderDimensions() {
            const scrollWidth = tableContainer.scrollWidth;
            const clientWidth = tableContainer.clientWidth;
            const maxScroll = Math.max(0, scrollWidth - clientWidth);
            // 1:1 exact pixel synchronization between track dummy and table scroll range
            trackDummy.style.width = (trackWrapper.clientWidth + maxScroll) + 'px';
            if (sliderBar) {
                if (maxScroll > 0) {
                    sliderBar.classList.remove('hidden');
                    sliderBar.classList.add('flex');
                } else {
                    sliderBar.classList.add('hidden');
                    sliderBar.classList.remove('flex');
                }
            }
        }

        let isSyncing = false;
        tableContainer.addEventListener('scroll', () => {
            if (isSyncing) return;
            isSyncing = true;
            trackWrapper.scrollLeft = tableContainer.scrollLeft;
            requestAnimationFrame(() => { isSyncing = false; });
        });

        trackWrapper.addEventListener('scroll', () => {
            if (isSyncing) return;
            isSyncing = true;
            tableContainer.scrollLeft = trackWrapper.scrollLeft;
            requestAnimationFrame(() => { isSyncing = false; });
        });

        if (sliderBar) {
            sliderBar.addEventListener('wheel', (e) => {
                if (e.deltaY !== 0 && e.deltaX === 0) {
                    e.preventDefault();
                    tableContainer.scrollLeft += e.deltaY;
                }
            }, { passive: false });
        }

        updateSliderDimensions();
        window.addEventListener('resize', updateSliderDimensions);
    }

    document.addEventListener('DOMContentLoaded', initTableScrollSlider);
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
