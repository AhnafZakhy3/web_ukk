<?php
/**
 * NusaPOS - Laporan Transaksi & Riwayat Penjualan
 */

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

$user = require_login();
$activeMenu = 'transaksi';
$pageTitle = 'Laporan Transaksi & Riwayat Penjualan';
$toko = get_toko_info();

$db = get_db();

// 1. Filter Parameters
$startDate   = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate     = $_GET['end_date'] ?? date('Y-m-d');
$searchNo    = trim($_GET['no_invoice'] ?? '');
$filterUser  = (int)($_GET['user_id'] ?? 0);
$filterMetode = strtolower(trim($_GET['metode'] ?? 'all'));

// 2. Query Transaksi dengan Filter
$sql = "
    SELECT t.*, u.nama_lengkap as nama_kasir,
           (SELECT COUNT(*) FROM detail_transaksi dt WHERE dt.transaksi_id = t.id) as item_count,
           (SELECT SUM(dt.jumlah) FROM detail_transaksi dt WHERE dt.transaksi_id = t.id) as total_qty
    FROM transaksi t
    JOIN users u ON t.user_id = u.id
    WHERE DATE(t.created_at) >= :start_date AND DATE(t.created_at) <= :end_date
";
$params = [
    ':start_date' => $startDate,
    ':end_date'   => $endDate,
];

if ($searchNo !== '') {
    $sql .= " AND t.no_invoice LIKE :invoice";
    $params[':invoice'] = "%{$searchNo}%";
}

if ($filterUser > 0) {
    $sql .= " AND t.user_id = :uid";
    $params[':uid'] = $filterUser;
}

if ($filterMetode !== 'all' && in_array($filterMetode, ['tunai', 'qris', 'debit', 'transfer'], true)) {
    $sql .= " AND t.metode_pembayaran = :metode";
    $params[':metode'] = $filterMetode;
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Eager load detail transaksi untuk menghindari N+1 query problem
$trxItemsMap = [];
if (!empty($transactions)) {
    $trxIds = array_column($transactions, 'id');
    $placeholders = implode(',', array_fill(0, count($trxIds), '?'));
    $stmtItems = $db->prepare("SELECT * FROM detail_transaksi WHERE transaksi_id IN ($placeholders)");
    $stmtItems->execute($trxIds);
    while ($item = $stmtItems->fetch()) {
        $trxItemsMap[$item['transaksi_id']][] = $item;
    }
}

// 3. Kalkulasi Ringkasan Metrik
$totalOmzet = 0.0;
$totalTrx = count($transactions);
$totalItemTerjual = 0;

foreach ($transactions as $t) {
    $totalOmzet += (float)$t['total_akhir'];
    $totalItemTerjual += (int)($t['total_qty'] ?? 0);
}

$aov = ($totalTrx > 0) ? ($totalOmzet / $totalTrx) : 0.0;

// 4. Data Tren Penjualan 7 Hari Terakhir untuk Grafik SVG
$stmtTrend = $db->query("
    SELECT DATE(created_at) as tgl, SUM(total_akhir) as omzet, COUNT(*) as jml
    FROM transaksi
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at) ASC
");
$rawTrend = $stmtTrend->fetchAll();
$trendMap = [];
foreach ($rawTrend as $r) {
    $trendMap[$r['tgl']] = (float)$r['omzet'];
}

$chartDays = [];
$maxChartVal = 100000;
for ($i = 6; $i >= 0; $i--) {
    $dayDate = date('Y-m-d', strtotime("-{$i} days"));
    $dayLabel = date('d M', strtotime($dayDate));
    $val = $trendMap[$dayDate] ?? 0.0;
    if ($val > $maxChartVal) $maxChartVal = $val;
    $chartDays[] = [
        'date' => $dayDate,
        'label' => $dayLabel,
        'val' => $val,
    ];
}

// 5. Daftar Kasir untuk Dropdown Filter
$cashiers = $db->query("SELECT id, nama_lengkap FROM users ORDER BY nama_lengkap ASC")->fetchAll();

require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';
require_once __DIR__ . '/templates/topbar.php';
?>

<div class="app-layout-content pl-0 md:pl-64 pt-16 min-h-screen bg-surface">
    <main class="p-space-md lg:p-space-lg space-y-space-lg">
        
        <!-- Header & Action -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-space-md">
            <div>
                <h1 class="font-headline-lg text-2xl text-on-surface font-bold tracking-tight">Laporan Transaksi & Riwayat Penjualan</h1>
                <p class="font-body-md text-sm text-on-surface-variant">Pantau omzet berkala, volume penjualan kasir, dan detail nota struk ritel.</p>
            </div>
            
            <a href="api/export.php?type=transaksi" class="inline-flex items-center gap-2 px-space-md py-2.5 rounded-xl bg-surface-container-low hover:bg-surface-container text-on-surface font-label-md text-xs border border-outline-variant/30 shadow-sm transition-all">
                <span class="material-symbols-outlined text-[18px]">download</span>
                <span>Export Laporan (CSV)</span>
            </a>
        </div>

        <!-- 4 Bento Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-space-md">
            <!-- Total Omzet -->
            <div class="p-space-md bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 flex items-start justify-between">
                <div>
                    <span class="font-label-sm text-xs uppercase tracking-wider text-outline font-semibold">Total Omzet Penjualan</span>
                    <div class="font-currency-display text-2xl text-primary font-bold mt-1"><?= format_rupiah($totalOmzet) ?></div>
                    <p class="text-xs text-on-surface-variant mt-2 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px] text-tertiary">trending_up</span>
                        Rentang tanggal terpilih
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-primary-fixed text-on-primary-fixed flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">payments</span>
                </div>
            </div>

            <!-- Total Transaksi -->
            <div class="p-space-md bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 flex items-start justify-between">
                <div>
                    <span class="font-label-sm text-xs uppercase tracking-wider text-outline font-semibold">Total Struk Transaksi</span>
                    <div class="font-currency-display text-2xl text-on-surface font-bold mt-1"><?= number_format($totalTrx, 0, ',', '.') ?> <span class="text-xs font-normal text-outline">Nota</span></div>
                    <p class="text-xs text-tertiary mt-2 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">check_circle</span>
                        Berhasil diproses
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-secondary-fixed text-on-secondary-fixed flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">receipt</span>
                </div>
            </div>

            <!-- Produk Terjual -->
            <div class="p-space-md bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 flex items-start justify-between">
                <div>
                    <span class="font-label-sm text-xs uppercase tracking-wider text-outline font-semibold">Total Item Terjual</span>
                    <div class="font-currency-display text-2xl text-tertiary font-bold mt-1"><?= number_format($totalItemTerjual, 0, ',', '.') ?> <span class="text-xs font-normal text-outline">Pcs</span></div>
                    <p class="text-xs text-on-surface-variant mt-2 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">shopping_bag</span>
                        Volume produk keluar
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-on-tertiary-container/30 text-tertiary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">inventory</span>
                </div>
            </div>

            <!-- Rata-rata Nilai Transaksi (AOV) -->
            <div class="p-space-md bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 flex items-start justify-between">
                <div>
                    <span class="font-label-sm text-xs uppercase tracking-wider text-outline font-semibold">Rata-rata Transaksi (AOV)</span>
                    <div class="font-currency-display text-2xl text-on-surface font-bold mt-1"><?= format_rupiah($aov) ?></div>
                    <p class="text-xs text-on-surface-variant mt-2 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px] text-primary">analytics</span>
                        Basket size belanja
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-surface-container text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">shopping_basket</span>
                </div>
            </div>
        </div>

        <!-- Grafik Visual Tren Penjualan 7 Hari Terakhir (SVG Modern) -->
        <div class="bg-surface-container-lowest p-space-lg rounded-2xl shadow-sm border border-outline-variant/30 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-headline-md text-base text-on-surface font-bold">Tren Penjualan 7 Hari Terakhir</h3>
                    <p class="text-xs text-on-surface-variant">Grafik omzet harian otomatis diperbarui dari database lokal.</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-primary-fixed text-on-primary-fixed">
                    Omzet Tertinggi: <?= format_rupiah($maxChartVal) ?>
                </span>
            </div>

            <!-- SVG Bar Chart -->
            <div class="h-44 w-full flex items-end justify-between gap-3 pt-6 px-2">
                <?php foreach ($chartDays as $cDay): 
                    $pct = ($maxChartVal > 0) ? round(($cDay['val'] / $maxChartVal) * 100) : 0;
                    $barHeight = max(8, $pct);
                ?>
                <div class="flex-1 flex flex-col items-center gap-2 group h-full justify-end">
                    <div class="text-[10px] font-mono font-bold text-primary opacity-0 group-hover:opacity-100 transition-opacity">
                        <?= format_rupiah($cDay['val']) ?>
                    </div>
                    <div class="w-full max-w-[48px] rounded-t-xl bg-gradient-to-t from-primary to-primary-container group-hover:from-secondary group-hover:to-secondary-container transition-all shadow-sm"
                        style="height: <?= $barHeight ?>%;">
                    </div>
                    <span class="text-[11px] font-semibold text-on-surface-variant mt-1"><?= $cDay['label'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-surface-container-lowest p-space-md rounded-2xl shadow-sm border border-outline-variant/30">
            <form method="GET" action="transaksi.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                <!-- Tanggal Mulai -->
                <div>
                    <label class="block text-[11px] font-semibold text-outline mb-1">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>"
                        class="w-full h-10 px-3 rounded-xl bg-surface-container-low text-xs border border-outline-variant/30">
                </div>

                <!-- Tanggal Akhir -->
                <div>
                    <label class="block text-[11px] font-semibold text-outline mb-1">Tanggal Akhir</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>"
                        class="w-full h-10 px-3 rounded-xl bg-surface-container-low text-xs border border-outline-variant/30">
                </div>

                <!-- No Invoice -->
                <div>
                    <label class="block text-[11px] font-semibold text-outline mb-1">Cari No. Faktur</label>
                    <input type="text" name="no_invoice" value="<?= htmlspecialchars($searchNo) ?>" placeholder="TRX-..."
                        class="w-full h-10 px-3 rounded-xl bg-surface-container-low font-mono text-xs border border-outline-variant/30">
                </div>

                <!-- Kasir -->
                <div>
                    <label class="block text-[11px] font-semibold text-outline mb-1">Pilihan Kasir</label>
                    <select name="user_id" class="w-full h-10 px-3 rounded-xl bg-surface-container-low text-xs border border-outline-variant/30">
                        <option value="0">Semua Kasir</option>
                        <?php foreach ($cashiers as $cs): ?>
                        <option value="<?= $cs['id'] ?>" <?= ($filterUser === (int)$cs['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cs['nama_lengkap']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Metode Pembayaran -->
                <div>
                    <label class="block text-[11px] font-semibold text-outline mb-1">Metode Bayar</label>
                    <select name="metode" class="w-full h-10 px-3 rounded-xl bg-surface-container-low text-xs border border-outline-variant/30">
                        <option value="all">Semua Metode</option>
                        <option value="tunai" <?= ($filterMetode === 'tunai') ? 'selected' : '' ?>>Tunai</option>
                        <option value="qris" <?= ($filterMetode === 'qris') ? 'selected' : '' ?>>QRIS</option>
                        <option value="debit" <?= ($filterMetode === 'debit') ? 'selected' : '' ?>>Debit</option>
                        <option value="transfer" <?= ($filterMetode === 'transfer') ? 'selected' : '' ?>>Transfer</option>
                    </select>
                </div>

                <!-- Tombol Action -->
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 h-10 bg-primary hover:bg-secondary text-on-primary rounded-xl font-label-md text-xs shadow-sm flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">filter_alt</span>
                        <span>Filter</span>
                    </button>
                    <a href="transaksi.php" class="h-10 px-3 bg-surface-container-low hover:bg-surface-container text-on-surface-variant rounded-xl flex items-center justify-center" title="Reset">
                        <span class="material-symbols-outlined text-[16px]">restart_alt</span>
                    </a>
                </div>
            </form>
        </div>

        <!-- Tabel Riwayat Transaksi -->
        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/30 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low text-outline font-label-sm text-xs uppercase tracking-wider border-b border-outline-variant/30">
                            <th class="py-3.5 px-4 font-semibold">No. Faktur (Struk)</th>
                            <th class="py-3.5 px-4 font-semibold">Waktu Transaksi</th>
                            <th class="py-3.5 px-4 font-semibold">Kasir</th>
                            <th class="py-3.5 px-4 font-semibold">Pelanggan</th>
                            <th class="py-3.5 px-4 font-semibold text-center">Item</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Total Akhir</th>
                            <th class="py-3.5 px-4 font-semibold text-center">Metode Bayar</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20 text-xs">
                        <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="8" class="py-12 text-center text-outline">
                                <span class="material-symbols-outlined text-[42px] mb-2">receipt_long</span>
                                <p class="font-headline-sm text-sm font-semibold text-on-surface-variant">Tidak ada transaksi ditemukan.</p>
                                <p class="text-xs">Coba sesuaikan rentang tanggal atau kriteria filter.</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($transactions as $tr): 
                            // Gunakan detail items yang telah dieager-load
                            $tItems = $trxItemsMap[$tr['id']] ?? [];

                            $tPayload = htmlspecialchars(json_encode([
                                'id' => $tr['id'],
                                'no_invoice' => $tr['no_invoice'],
                                'tanggal' => date('d/m/Y H:i', strtotime($tr['created_at'])),
                                'kasir' => $tr['nama_kasir'],
                                'pelanggan' => $tr['pelanggan'],
                                'subtotal' => (float)$tr['subtotal'],
                                'pajak_ppn' => (float)$tr['pajak_ppn'],
                                'diskon' => (float)$tr['diskon'],
                                'total_akhir' => (float)$tr['total_akhir'],
                                'metode_pembayaran' => $tr['metode_pembayaran'],
                                'nominal_bayar' => (float)$tr['nominal_bayar'],
                                'kembalian' => (float)$tr['kembalian'],
                                'items' => $tItems
                            ]), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr class="hover:bg-surface-container-low/60 transition-colors">
                            <td class="py-3 px-4 font-mono font-bold text-primary">
                                <?= htmlspecialchars($tr['no_invoice']) ?>
                            </td>
                            <td class="py-3 px-4 text-on-surface">
                                <?= date('d M Y, H:i', strtotime($tr['created_at'])) ?>
                            </td>
                            <td class="py-3 px-4 font-semibold text-on-surface">
                                <?= htmlspecialchars($tr['nama_kasir']) ?>
                            </td>
                            <td class="py-3 px-4 text-on-surface-variant">
                                <?= htmlspecialchars($tr['pelanggan']) ?>
                            </td>
                            <td class="py-3 px-4 text-center font-bold">
                                <?= $tr['total_qty'] ?: 1 ?> Unit
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-sm text-on-surface">
                                <?= format_rupiah($tr['total_akhir']) ?>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <?php if ($tr['metode_pembayaran'] === 'tunai'): ?>
                                    <span class="px-2.5 py-0.5 rounded-full font-bold bg-tertiary-fixed text-on-tertiary-fixed-variant text-[11px]">TUNAI</span>
                                <?php elseif ($tr['metode_pembayaran'] === 'qris'): ?>
                                    <span class="px-2.5 py-0.5 rounded-full font-bold bg-primary-fixed text-on-primary-fixed text-[11px]">QRIS</span>
                                <?php elseif ($tr['metode_pembayaran'] === 'debit'): ?>
                                    <span class="px-2.5 py-0.5 rounded-full font-bold bg-secondary-fixed text-on-secondary-fixed text-[11px]">DEBIT BCA</span>
                                <?php else: ?>
                                    <span class="px-2.5 py-0.5 rounded-full font-bold bg-surface-container text-on-surface text-[11px]">TRANSFER</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <button type="button" onclick='openDetailTrx(<?= $tPayload ?>)' class="px-3 py-1.5 rounded-lg bg-surface-container-low hover:bg-surface-container text-primary font-semibold text-xs transition-colors inline-flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                                    <span>Detail Struk</span>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- ======================================================== -->
<!-- MODAL DETAIL TRANSAKSI & CETAK STRUK                      -->
<!-- ======================================================== -->
<div id="modal-detail-trx" class="fixed inset-0 z-50 bg-inverse-surface/65 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-surface-container-lowest w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden border border-outline-variant/30 flex flex-col max-h-[90vh]">
        <div class="p-4 bg-surface-container-low border-b border-outline-variant/30 flex items-center justify-between">
            <div>
                <h3 class="font-headline-md text-base text-on-surface font-bold">Rincian Struk Pembayaran</h3>
                <p class="text-xs font-mono text-primary" id="dt-invoice">-</p>
            </div>
            <button type="button" onclick="closeModal('modal-detail-trx')" class="text-outline hover:text-on-surface p-1 rounded-lg">
                <span class="material-symbols-outlined text-[22px]">close</span>
            </button>
        </div>

        <div class="p-space-lg overflow-y-auto space-y-4">
            <!-- Metadata -->
            <div class="grid grid-cols-2 gap-2 text-xs bg-surface-container-low p-3 rounded-xl">
                <div>Kasir: <strong id="dt-kasir" class="text-on-surface">-</strong></div>
                <div>Waktu: <strong id="dt-time" class="text-on-surface">-</strong></div>
                <div>Pelanggan: <strong id="dt-pelanggan" class="text-on-surface">-</strong></div>
                <div>Metode: <strong id="dt-metode" class="text-primary uppercase">-</strong></div>
            </div>

            <!-- Items Table -->
            <div class="space-y-1.5 border-t border-b border-outline-variant/30 py-3 text-xs" id="dt-items">
                <!-- Dinamis -->
            </div>

            <!-- Math Summary -->
            <div class="space-y-1 text-xs">
                <div class="flex justify-between text-on-surface-variant">
                    <span>Subtotal</span>
                    <span id="dt-subtotal" class="font-mono font-semibold">Rp0</span>
                </div>
                <div class="flex justify-between text-on-surface-variant">
                    <span>PPN (11%)</span>
                    <span id="dt-ppn" class="font-mono font-semibold">Rp0</span>
                </div>
                <div class="flex justify-between text-tertiary">
                    <span>Diskon</span>
                    <span id="dt-diskon" class="font-mono font-semibold">-Rp0</span>
                </div>
                <div class="flex justify-between text-on-surface font-bold text-sm pt-2 border-t border-outline-variant/30">
                    <span>TOTAL AKHIR</span>
                    <span id="dt-total" class="font-currency-display text-base text-primary font-bold">Rp0</span>
                </div>
                <div class="flex justify-between text-on-surface-variant">
                    <span>Nominal Bayar</span>
                    <span id="dt-paid" class="font-mono font-semibold">Rp0</span>
                </div>
                <div class="flex justify-between text-tertiary font-bold">
                    <span>Kembalian</span>
                    <span id="dt-change" class="font-mono font-bold">Rp0</span>
                </div>
            </div>
        </div>

        <div class="p-4 bg-surface-container-low border-t border-outline-variant/30 flex items-center justify-end gap-2">
            <button type="button" onclick="closeModal('modal-detail-trx')" class="px-4 py-2 rounded-xl bg-surface-container text-on-surface text-xs font-semibold">Tutup</button>
            <button type="button" onclick="printReceiptFromDetail()" class="px-5 py-2 rounded-xl bg-primary hover:bg-secondary text-on-primary text-xs font-bold shadow-sm flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px]">print</span>
                <span>Cetak Ulang Struk</span>
            </button>
        </div>
    </div>
</div>

<script>
    let activeTrxDetail = null;

    function openDetailTrx(trx) {
        activeTrxDetail = trx;
        document.getElementById('dt-invoice').textContent = trx.no_invoice;
        document.getElementById('dt-kasir').textContent = trx.kasir;
        document.getElementById('dt-time').textContent = trx.tanggal;
        document.getElementById('dt-pelanggan').textContent = trx.pelanggan;
        document.getElementById('dt-metode').textContent = trx.metode_pembayaran;

        let itemsHtml = '';
        trx.items.forEach(item => {
            itemsHtml += `
                <div class="flex justify-between items-center py-1">
                    <div>
                        <p class="font-semibold text-on-surface">${item.nama_produk}</p>
                        <p class="text-outline text-[11px]">${item.jumlah} x ${formatRupiah(item.harga_satuan)}</p>
                    </div>
                    <span class="font-mono font-semibold text-on-surface">${formatRupiah(item.subtotal)}</span>
                </div>
            `;
        });
        document.getElementById('dt-items').innerHTML = itemsHtml;

        document.getElementById('dt-subtotal').textContent = formatRupiah(trx.subtotal);
        document.getElementById('dt-ppn').textContent = formatRupiah(trx.pajak_ppn);
        document.getElementById('dt-diskon').textContent = '-' + formatRupiah(trx.diskon);
        document.getElementById('dt-total').textContent = formatRupiah(trx.total_akhir);
        document.getElementById('dt-paid').textContent = formatRupiah(trx.nominal_bayar);
        document.getElementById('dt-change').textContent = formatRupiah(trx.kembalian);

        openModal('modal-detail-trx');
    }

    function printReceiptFromDetail() {
        if (!activeTrxDetail) return;
        window.print();
    }
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
