<?php
/**
 * NusaPOS - Terminal Kasir (Point of Sale)
 */

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

$user = require_login();
$activeMenu = 'kasir';
$pageTitle = 'Terminal Kasir';
$toko = get_toko_info();

$db = get_db();

// 1. Ambil Kategori Aktif
$categories = $db->query("
    SELECT k.*, COUNT(p.id) as total_produk 
    FROM kategori k 
    LEFT JOIN produk p ON k.id = p.kategori_id AND p.status = 'aktif'
    WHERE k.status = 'aktif'
    GROUP BY k.id 
    ORDER BY k.nama_kategori ASC
")->fetchAll();

// 2. Ambil Semua Produk Aktif
$products = $db->query("
    SELECT p.*, k.nama_kategori, k.kode as kode_kategori, s.singkatan as satuan_nama
    FROM produk p
    JOIN kategori k ON p.kategori_id = k.id
    LEFT JOIN satuan s ON p.satuan_id = s.id
    WHERE p.status = 'aktif'
    ORDER BY p.nama_produk ASC
")->fetchAll();

require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';
require_once __DIR__ . '/templates/topbar.php';
?>

<div class="app-layout-content pl-0 md:pl-64 pt-16 min-h-screen lg:h-screen lg:overflow-hidden bg-surface">
    <main class="p-space-md lg:p-space-lg lg:h-[calc(100vh-4rem)] lg:overflow-hidden flex flex-col">
        <!-- Dual Column POS Layout -->
        <div class="grid grid-cols-12 gap-space-md lg:gap-space-lg items-stretch lg:h-full lg:min-h-0 flex-1">
            
            <!-- Kolom Kiri: Katalog Produk & Pencarian (Grid 7 ~ 8 Kolom) -->
            <section class="col-span-12 lg:col-span-7 xl:col-span-8 flex flex-col gap-space-md lg:h-full lg:min-h-0">
                
                <!-- Search & Filter Bar -->
                <div class="bg-surface-container-lowest p-space-md rounded-2xl shadow-sm border border-outline-variant/30 space-y-space-md shrink-0">
                    <div class="flex items-center gap-space-sm">
                        <div class="relative flex-1">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                            <input type="text" id="search-input" placeholder="Cari nama produk, SKU, atau barcode..."
                                class="w-full pl-11 pr-4 py-2.5 rounded-xl bg-surface-container-low text-on-surface placeholder:text-outline font-body-md focus:outline-none focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary border border-outline-variant/30 transition-all">
                        </div>
                        <button type="button" onclick="showToast('fitur ini blom jadi nanti ajah bikinnya', 'info');" class="flex items-center gap-2 bg-primary text-on-primary px-4 py-2.5 rounded-xl font-label-lg shadow-sm hover:bg-secondary transition-all active:scale-[0.98] shrink-0 cursor-pointer">
                            <span class="material-symbols-outlined text-[20px]">barcode_scanner</span>
                            <span class="hidden sm:inline">Scan Barcode</span>
                        </button>
                    </div>

                    <!-- Category Filter Tabs -->
                    <div class="flex items-center gap-2 overflow-x-auto pb-1" id="category-tabs">
                        <button type="button" onclick="filterCategory('semua', this)" class="cat-tab px-3.5 py-1.5 rounded-full font-label-md text-label-md whitespace-nowrap transition-colors bg-primary-container text-on-primary font-semibold shadow-sm cursor-pointer">
                            Semua (<?= count($products) ?>)
                        </button>
                        <?php foreach ($categories as $cat): ?>
                        <button type="button" onclick="filterCategory('<?= strtolower($cat['kode']) ?>', this)" class="cat-tab px-3.5 py-1.5 rounded-full font-label-md text-label-md whitespace-nowrap transition-colors bg-surface-container-low text-on-surface-variant hover:bg-surface-container border border-outline-variant/30 cursor-pointer">
                            <?= htmlspecialchars($cat['nama_kategori']) ?> (<?= $cat['total_produk'] ?>)
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Product Catalog Grid Container (Scrollable independently) -->
                <div class="flex-1 lg:overflow-y-auto lg:min-h-0 overscroll-contain pr-1">
                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-space-md" id="catalog-grid">
                        <?php foreach ($products as $prod): 
                            $prodJson = htmlspecialchars(json_encode([
                                'id' => (int)$prod['id'],
                                'sku' => $prod['sku'],
                                'barcode' => $prod['barcode'],
                                'nama_produk' => $prod['nama_produk'],
                                'harga_jual' => (float)$prod['harga_jual'],
                                'stok' => (int)$prod['stok'],
                            ]), ENT_QUOTES, 'UTF-8');
                            $isOutOfStock = ((int)$prod['stok'] <= 0);
                            $isLowStock = ((int)$prod['stok'] > 0 && (int)$prod['stok'] <= (int)$prod['min_stok']);
                        ?>
                        <div class="product-card group bg-surface-container-lowest p-space-sm rounded-xl shadow-sm hover:shadow-md border border-outline-variant/30 transition-all flex flex-col justify-between"
                            data-id="<?= $prod['id'] ?>"
                            data-category="<?= strtolower($prod['kode_kategori']) ?>"
                            data-name="<?= htmlspecialchars($prod['nama_produk']) ?>"
                            data-sku="<?= htmlspecialchars($prod['sku']) ?>"
                            data-barcode="<?= htmlspecialchars($prod['barcode'] ?? '') ?>"
                            data-stok="<?= $prod['stok'] ?>"
                            data-json='<?= $prodJson ?>'>
                            <div>
                                <!-- Gambar Produk -->
                                <div class="relative w-full h-32 rounded-lg bg-surface-container-low overflow-hidden mb-space-sm flex items-center justify-center">
                                    <?php if (!empty($prod['gambar'])): ?>
                                        <img src="<?= htmlspecialchars($prod['gambar']) ?>" alt="<?= htmlspecialchars($prod['nama_produk']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    <?php else: ?>
                                        <span class="material-symbols-outlined text-[42px] text-outline/40">shopping_bag</span>
                                    <?php endif; ?>
                                    <span class="absolute top-2 left-2 bg-surface-container-lowest/90 backdrop-blur-sm px-2 py-0.5 rounded font-label-sm text-xs text-on-surface-variant font-semibold">
                                        <?= htmlspecialchars($prod['nama_kategori']) ?>
                                    </span>
                                </div>

                                <!-- SKU & Stock Badge -->
                                <div class="flex items-center justify-between gap-1 mb-1">
                                    <span class="font-label-sm text-xs text-outline font-mono truncate"><?= htmlspecialchars($prod['sku']) ?></span>
                                    <?php if ($isOutOfStock): ?>
                                        <span class="stock-badge bg-error-container text-error px-2 py-0.5 rounded-full font-label-sm text-xs font-semibold">Habis</span>
                                    <?php elseif ($isLowStock): ?>
                                        <span class="stock-badge bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full font-label-sm text-xs font-semibold">Sisa: <?= $prod['stok'] ?></span>
                                    <?php else: ?>
                                        <span class="stock-badge bg-tertiary-fixed text-on-tertiary-fixed-variant px-2 py-0.5 rounded-full font-label-sm text-xs font-semibold">Stok: <?= $prod['stok'] ?></span>
                                    <?php endif; ?>
                                </div>

                                <!-- Title -->
                                <h3 class="font-headline-sm text-sm text-on-surface line-clamp-2 leading-snug font-semibold" title="<?= htmlspecialchars($prod['nama_produk']) ?>">
                                    <?= htmlspecialchars($prod['nama_produk']) ?>
                                </h3>
                            </div>

                            <!-- Price & Add Button -->
                            <div class="mt-space-md pt-space-xs flex items-center justify-between border-t border-outline-variant/20">
                                <div>
                                    <span class="font-label-sm text-[11px] text-outline block">Harga</span>
                                    <span class="font-currency-table text-sm text-primary font-bold">
                                        <?= format_rupiah($prod['harga_jual']) ?>
                                    </span>
                                </div>
                                <button type="button" 
                                    onclick='addToCart(JSON.parse(this.closest(".product-card").getAttribute("data-json")))' 
                                    <?= $isOutOfStock ? 'disabled' : '' ?>
                                    title="<?= $isOutOfStock ? 'Stok Habis' : 'Tambah ke Keranjang' ?>" 
                                    class="btn-add-cart w-9 h-9 rounded-lg <?= $isOutOfStock ? 'bg-surface-container text-outline cursor-not-allowed' : 'bg-primary-container/10 hover:bg-primary-container text-primary hover:text-on-primary active:scale-95' ?> transition-all flex items-center justify-center shadow-sm">
                                    <span class="material-symbols-outlined text-[20px]"><?= $isOutOfStock ? 'block' : 'add_shopping_cart' ?></span>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Empty search state -->
                    <div id="catalog-not-found" class="hidden p-12 text-center bg-surface-container-lowest rounded-2xl border border-outline-variant/30 space-y-3">
                        <span class="material-symbols-outlined text-[48px] text-outline">search_off</span>
                        <h4 class="font-headline-md text-on-surface font-bold">Produk Tidak Ditemukan</h4>
                        <p class="font-body-md text-on-surface-variant max-w-sm mx-auto">Tidak ada produk yang cocok dengan pencarian atau barcode yang dimasukkan.</p>
                    </div>
                </div>
            </section>

            <!-- Kolom Kanan: Keranjang Belanja & Checkout (Grid 4 ~ 5 Kolom) -->
            <section class="col-span-12 lg:col-span-5 xl:col-span-4 lg:h-full lg:min-h-0 flex flex-col">
                <div class="bg-surface-container-lowest rounded-2xl shadow-md border border-outline-variant/30 p-space-md flex flex-col lg:h-full lg:max-h-full min-h-0 justify-between">
                    
                    <!-- Cart Header -->
                    <div class="shrink-0">
                        <div class="pb-space-sm border-b border-outline-variant/30">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-[24px]">shopping_cart</span>
                                    <span class="font-headline-md text-lg text-on-surface font-bold">Keranjang Belanja</span>
                                </div>
                                <button type="button" onclick="confirmClearCart()" title="Kosongkan Keranjang" class="text-error hover:bg-error-container/40 px-2 py-1 rounded-lg transition-colors flex items-center gap-1 font-label-sm text-xs">
                                    <span class="material-symbols-outlined text-[16px]">delete_sweep</span>
                                    <span>Kosongkan</span>
                                </button>
                            </div>

                            <div class="flex items-center justify-between text-on-surface-variant font-label-sm text-xs">
                                <span class="font-mono font-semibold text-primary" id="cart-invoice-preview">#TRX-<?= date('Ymd') ?>-BARU</span>
                                <span class="font-semibold text-outline" id="cart-items-count">0 Item</span>
                            </div>

                            <!-- Pelanggan input -->
                            <div class="mt-2.5">
                                <input type="text" id="customer-name-input" value="Pelanggan Umum (Walk-In)" placeholder="Nama Pelanggan (opsional)"
                                    class="w-full px-3 py-1.5 rounded-lg bg-surface-container-low text-xs text-on-surface font-body-sm border border-outline-variant/30 focus:outline-none focus:ring-1 focus:ring-primary">
                            </div>
                        </div>
                    </div>

                    <!-- Cart Item List (Scrollable Area) -->
                    <div class="flex-1 overflow-y-auto py-space-sm space-y-space-sm pr-1 overscroll-contain min-h-0" id="cart-item-list">
                        <!-- Diisi secara dinamis oleh pos.js -->
                    </div>

                    <!-- Cart Empty State -->
                    <div id="cart-empty-state" class="flex-1 flex flex-col items-center justify-center p-6 text-center text-outline space-y-2">
                        <div class="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center text-outline/60 mb-2">
                            <span class="material-symbols-outlined text-[32px]">remove_shopping_cart</span>
                        </div>
                        <h4 class="font-headline-sm text-sm font-semibold text-on-surface-variant">Keranjang Masih Kosong</h4>
                        <p class="font-body-sm text-xs max-w-xs text-outline">Pilih produk dari katalog atau pindai barcode untuk menambahkan transaksi belanja.</p>
                    </div>

                    <!-- Cart Footer & Calculation -->
                    <div class="shrink-0 pt-space-sm border-t border-outline-variant/30 space-y-3">
                        <div class="space-y-1.5 font-body-sm text-xs">
                            <div class="flex justify-between text-on-surface-variant">
                                <span>Subtotal Belanja</span>
                                <span class="font-mono font-semibold" id="cart-subtotal">Rp0</span>
                            </div>
                            <div class="flex justify-between text-on-surface-variant">
                                <span>PPN (11%)</span>
                                <span class="font-mono font-semibold" id="cart-ppn">Rp0</span>
                            </div>
                            <div class="flex justify-between text-tertiary">
                                <span>Diskon Transaksi</span>
                                <span class="font-mono font-semibold" id="cart-discount">-Rp0</span>
                            </div>
                            <div class="flex justify-between text-on-surface font-headline-md text-base pt-2 border-t border-outline-variant/30 font-bold">
                                <span>Total Tagihan</span>
                                <span class="text-primary font-currency-display text-xl" id="cart-total">Rp0</span>
                            </div>
                        </div>

                        <!-- Tombol Bayar -->
                        <button type="button" id="btn-checkout" onclick="openPaymentModal()" disabled
                            class="w-full py-3 px-space-md rounded-xl bg-primary hover:bg-secondary disabled:bg-surface-container disabled:text-outline disabled:cursor-not-allowed text-on-primary font-headline-sm text-base shadow-md transition-all active:scale-[0.99] flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">payments</span>
                            <span>Bayar Sekarang</span>
                        </button>
                    </div>

                </div>
            </section>

        </div>
    </main>
</div>

<!-- ======================================================== -->
<!-- MODAL PEMBAYARAN (PAYMENT DIALOG)                        -->
<!-- ======================================================== -->
<div id="payment-modal" class="fixed inset-0 z-50 bg-inverse-surface/65 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-surface-container-lowest w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden border border-outline-variant/30 flex flex-col max-h-[90vh]">
        <!-- Header Modal -->
        <div class="p-space-lg bg-surface-container-low border-b border-outline-variant/30 flex items-center justify-between">
            <div class="flex items-center gap-space-sm">
                <div class="w-10 h-10 rounded-xl bg-primary text-on-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">point_of_sale</span>
                </div>
                <div>
                    <h3 class="font-headline-md text-lg text-on-surface font-bold">Pilih Pembayaran</h3>
                    <p class="font-body-sm text-xs text-on-surface-variant">Selesaikan pembayaran transaksi kasir ritel</p>
                </div>
            </div>
            <button type="button" onclick="closePaymentModal()" class="text-outline hover:text-on-surface p-1 rounded-lg">
                <span class="material-symbols-outlined text-[24px]">close</span>
            </button>
        </div>

        <!-- Body Modal -->
        <div class="p-space-lg overflow-y-auto space-y-space-md">
            <!-- Ringkasan Tagihan Box -->
            <div class="p-space-md rounded-xl bg-surface-container-low border border-outline-variant/30 flex items-center justify-between">
                <div>
                    <span class="font-label-sm text-xs text-outline uppercase tracking-wider block font-semibold">Total Tagihan Final</span>
                    <div class="font-currency-display text-2xl text-primary font-bold" id="modal-pay-total">Rp0</div>
                </div>
                <div class="text-right text-xs space-y-0.5 text-on-surface-variant">
                    <div>Subtotal: <span class="font-semibold font-mono" id="modal-pay-subtotal">Rp0</span></div>
                    <div>PPN (11%): <span class="font-semibold font-mono" id="modal-pay-ppn">Rp0</span></div>
                    <div>Diskon: <span class="font-semibold font-mono text-tertiary" id="modal-pay-discount">-Rp0</span></div>
                </div>
            </div>

            <!-- Metode Pembayaran Cards -->
            <div>
                <span class="font-label-md text-sm text-on-surface font-semibold block mb-2">Metode Pembayaran</span>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <!-- Tunai -->
                    <button type="button" onclick="selectPaymentMethod('tunai')" data-method="tunai" class="pay-method-card p-3 rounded-xl border border-outline-variant/40 bg-surface-container-lowest hover:bg-surface-container-low transition-all flex flex-col items-center justify-center gap-1.5 cursor-pointer">
                        <span class="material-symbols-outlined text-[28px] text-tertiary">payments</span>
                        <span class="font-label-md text-xs font-bold text-on-surface">Uang Tunai</span>
                        <span class="text-[10px] text-outline">Cash / Nota</span>
                    </button>
                    <!-- QRIS -->
                    <button type="button" onclick="selectPaymentMethod('qris')" data-method="qris" class="pay-method-card p-3 rounded-xl border border-outline-variant/40 bg-surface-container-lowest hover:bg-surface-container-low transition-all flex flex-col items-center justify-center gap-1.5 cursor-pointer">
                        <span class="material-symbols-outlined text-[28px] text-primary">qr_code_scanner</span>
                        <span class="font-label-md text-xs font-bold text-on-surface">QRIS Dinamis</span>
                        <span class="text-[10px] text-outline">Gopay / OVO / Dana</span>
                    </button>
                    <!-- Debit BCA -->
                    <button type="button" onclick="selectPaymentMethod('debit')" data-method="debit" class="pay-method-card p-3 rounded-xl border border-outline-variant/40 bg-surface-container-lowest hover:bg-surface-container-low transition-all flex flex-col items-center justify-center gap-1.5 cursor-pointer">
                        <span class="material-symbols-outlined text-[28px] text-secondary">credit_card</span>
                        <span class="font-label-md text-xs font-bold text-on-surface">Kartu Debit</span>
                        <span class="text-[10px] text-outline">BCA / Mandiri / BRI</span>
                    </button>
                    <!-- Transfer Bank -->
                    <button type="button" onclick="selectPaymentMethod('transfer')" data-method="transfer" class="pay-method-card p-3 rounded-xl border border-outline-variant/40 bg-surface-container-lowest hover:bg-surface-container-low transition-all flex flex-col items-center justify-center gap-1.5 cursor-pointer">
                        <span class="material-symbols-outlined text-[28px] text-on-surface-variant">account_balance</span>
                        <span class="font-label-md text-xs font-bold text-on-surface">Transfer Bank</span>
                        <span class="text-[10px] text-outline">Virtual Account</span>
                    </button>
                </div>
            </div>

            <!-- Konten Spesifik Metode: TUNAI -->
            <div id="cash-payment-section" class="space-y-space-sm p-space-md rounded-xl bg-surface-container-low border border-outline-variant/30">
                <label for="cash-tendered-input" class="font-label-md text-xs text-on-surface font-semibold block">Nominal Uang Diterima (Rp)</label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 font-bold text-on-surface-variant text-sm">Rp</span>
                    <input type="number" id="cash-tendered-input" oninput="calculateChange()" step="1000" min="0"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-surface-container-lowest text-on-surface font-mono font-bold text-lg border border-outline-variant/40 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Tombol Denominasi Cepat -->
                <div class="flex items-center gap-2 flex-wrap pt-1">
                    <span class="text-xs text-outline font-semibold">Uang Cepat:</span>
                    <button type="button" onclick="setQuickCash('pas')" class="px-2.5 py-1 rounded-lg bg-surface-container-lowest hover:bg-surface-container text-xs font-semibold text-primary border border-outline-variant/40">Uang Pas</button>
                    <button type="button" onclick="setQuickCash(20000)" class="px-2.5 py-1 rounded-lg bg-surface-container-lowest hover:bg-surface-container text-xs font-semibold text-on-surface border border-outline-variant/40">Rp20.000</button>
                    <button type="button" onclick="setQuickCash(50000)" class="px-2.5 py-1 rounded-lg bg-surface-container-lowest hover:bg-surface-container text-xs font-semibold text-on-surface border border-outline-variant/40">Rp50.000</button>
                    <button type="button" onclick="setQuickCash(100000)" class="px-2.5 py-1 rounded-lg bg-surface-container-lowest hover:bg-surface-container text-xs font-semibold text-on-surface border border-outline-variant/40">Rp100.000</button>
                </div>

                <!-- Feedback Kembalian -->
                <div class="pt-3 border-t border-outline-variant/30 flex items-center justify-between">
                    <span class="font-label-md text-sm text-on-surface font-semibold">Kembalian</span>
                    <div id="cash-change-display" class="font-currency-display text-xl text-tertiary font-bold">Rp0</div>
                </div>
                <div id="cash-underpaid-alert" class="hidden text-xs text-error font-semibold flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">warning</span>
                    <span>Uang yang diterima kurang dari total tagihan!</span>
                </div>
            </div>

            <!-- Konten Spesifik Metode: QRIS -->
            <div id="qris-payment-section" class="hidden p-space-md rounded-xl bg-surface-container-low border border-outline-variant/30 flex flex-col items-center justify-center gap-2 text-center py-6">
                <div class="w-36 h-36 bg-surface-container-lowest p-2 rounded-xl shadow-inner border border-outline-variant/40 flex items-center justify-center">
                    <svg class="w-full h-full text-on-surface" viewBox="0 0 100 100">
                        <path d="M10,10 h30 v30 h-30 z M15,15 v20 h20 v-20 z M22,22 h6 v6 h-6 z M60,10 h30 v30 h-30 z M65,15 v20 h20 v-20 z M72,22 h6 v6 h-6 z M10,60 h30 v30 h-30 z M15,65 v20 h20 v-20 z M22,72 h6 v6 h-6 z M50,15 h5 v10 h-5 z M50,35 h8 v5 h-8 z M60,50 h10 v8 h-10 z M80,50 h10 v15 h-10 z M50,65 h12 v6 h-12 z M70,70 h8 v8 h-8 z M85,80 h5 v10 h-5 z M60,85 h15 v5 h-15 z M50,85 h5 v5 h-5 z" fill="currentColor"></path>
                    </svg>
                </div>
                <span class="font-label-sm text-xs text-on-surface-variant font-semibold">Tunjukkan QRIS ke Pelanggan • Nominal Otomatis Terkunci</span>
            </div>

            <!-- Konten Spesifik Metode: DEBIT / TRANSFER -->
            <div id="card-payment-section" class="hidden p-space-md rounded-xl bg-surface-container-low border border-outline-variant/30 text-center py-6 space-y-2">
                <span class="material-symbols-outlined text-[36px] text-primary">credit_card</span>
                <p class="font-headline-sm text-sm font-semibold text-on-surface">Gunakan Mesin EDC Kasir</p>
                <p class="font-body-sm text-xs text-on-surface-variant">Gesek atau masukkan kartu debit pelanggan pada mesin EDC, lalu tekan tombol konfirmasi setelah struk EDC keluar.</p>
            </div>
        </div>

        <!-- Footer Modal -->
        <div class="p-space-lg bg-surface-container-low border-t border-outline-variant/30 flex items-center justify-end gap-space-sm">
            <button type="button" onclick="closePaymentModal()" class="px-5 py-2.5 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md transition-colors">
                Batal
            </button>
            <button type="button" id="btn-confirm-payment" onclick="confirmPayment()" class="px-6 py-2.5 rounded-xl bg-primary hover:bg-secondary text-on-primary font-headline-sm text-sm shadow-md transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">check_circle</span>
                <span>Konfirmasi Pembayaran</span>
            </button>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL SUKSES & CETAK STRUK THERMAL 80MM                  -->
<!-- ======================================================== -->
<div id="success-modal" class="fixed inset-0 z-50 bg-inverse-surface/65 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-surface-container-lowest w-full max-w-4xl rounded-2xl shadow-2xl overflow-hidden grid grid-cols-1 md:grid-cols-12 max-h-[90vh] border border-outline-variant/30">
        
        <!-- Sisi Kiri: Status & Rincian -->
        <div class="md:col-span-6 p-space-xl flex flex-col justify-between bg-surface-container-low">
            <div>
                <div class="w-14 h-14 rounded-2xl bg-tertiary-fixed text-on-tertiary-fixed-variant flex items-center justify-center mb-space-md shadow-sm">
                    <span class="material-symbols-outlined text-[32px]">check_circle</span>
                </div>
                <span class="font-label-sm text-xs text-tertiary font-bold uppercase tracking-wider block">Pembayaran Berhasil Diterima</span>
                <h2 class="font-headline-lg text-xl text-on-surface font-bold mt-1">Transaksi Berhasil Disimpan</h2>
                <p class="font-body-md text-xs text-on-surface-variant mt-1.5">
                    Struk penjualan siap dicetak ke printer thermal atau disimpan ke sistem pembukuan.
                </p>

                <!-- Rincian Nilai -->
                <div class="mt-space-md p-space-md rounded-xl bg-surface-container-lowest border border-outline-variant/30 space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-outline">No. Faktur</span>
                        <span class="font-mono font-bold text-on-surface" id="receipt-invoice-no">-</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-outline">Waktu Transaksi</span>
                        <span class="text-on-surface" id="receipt-time">-</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-outline">Metode Bayar</span>
                        <span class="font-semibold text-primary" id="receipt-method">-</span>
                    </div>
                    <div class="flex justify-between pt-1 border-t border-outline-variant/20">
                        <span class="text-outline">Total Tagihan</span>
                        <span class="font-bold text-on-surface font-mono" id="receipt-total">-</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-outline">Uang Diterima</span>
                        <span class="text-on-surface font-mono" id="receipt-paid">-</span>
                    </div>
                    <div class="flex justify-between pt-1 border-t border-outline-variant/30">
                        <span class="font-bold text-tertiary">Kembalian</span>
                        <span class="font-bold text-tertiary font-mono" id="receipt-change">-</span>
                    </div>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="mt-space-lg space-y-2">
                <button type="button" onclick="printReceipt()" class="w-full py-3 px-space-md rounded-xl bg-primary hover:bg-secondary text-on-primary font-headline-sm text-sm flex items-center justify-center gap-2 shadow-md transition-all active:scale-[0.99]">
                    <span class="material-symbols-outlined text-[20px]">print</span>
                    <span>Cetak Struk Thermal</span>
                </button>
                <button type="button" onclick="resetOrder()" class="w-full py-2.5 px-space-md rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-lg text-sm flex items-center justify-center gap-2 transition-all">
                    <span class="material-symbols-outlined text-[20px]">add_shopping_cart</span>
                    <span>Transaksi Baru (Esc)</span>
                </button>
            </div>
        </div>

        <!-- Sisi Kanan: Pratinjau Slip Struk 80mm Realistis -->
        <div class="md:col-span-6 p-space-lg bg-surface-container-highest flex flex-col items-center justify-center overflow-y-auto max-h-[85vh]">
            <span class="font-label-sm text-xs text-outline mb-2 font-semibold">Pratinjau Kertas Struk 80mm</span>
            
            <div id="thermal-print-area" class="w-full max-w-[320px] bg-white text-neutral-900 p-5 rounded-sm shadow-xl font-mono text-xs leading-relaxed border-t-4 border-primary">
                <!-- Header Toko -->
                <div class="text-center pb-2.5 border-b border-dashed border-neutral-300">
                    <div class="font-bold text-sm tracking-tight text-neutral-950 uppercase" id="slip-toko">-</div>
                    <div class="text-[10px] text-neutral-600" id="slip-cabang">-</div>
                    <div class="text-[10px] text-neutral-600" id="slip-alamat">-</div>
                </div>

                <!-- Info Faktur -->
                <div class="py-2 text-[10px] border-b border-dashed border-neutral-300 text-neutral-600 space-y-0.5">
                    <div class="flex justify-between">
                        <span>No: <strong id="slip-invoice">-</strong></span>
                        <span>Pos: #01</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Tgl: <span id="slip-date">-</span></span>
                        <span>Kasir: <span id="slip-kasir">-</span></span>
                    </div>
                </div>

                <!-- Daftar Item -->
                <div class="py-2 border-b border-dashed border-neutral-300 space-y-1.5" id="slip-items">
                    <!-- Dinamis -->
                </div>

                <!-- Kalkulasi -->
                <div class="py-2 space-y-1 border-b border-dashed border-neutral-300 text-[11px]">
                    <div class="flex justify-between text-neutral-600">
                        <span>Subtotal</span>
                        <span id="slip-subtotal">0</span>
                    </div>
                    <div class="flex justify-between text-neutral-600">
                        <span>PPN (11%)</span>
                        <span id="slip-ppn">0</span>
                    </div>
                    <div class="flex justify-between text-neutral-600">
                        <span>Diskon</span>
                        <span id="slip-diskon">0</span>
                    </div>
                    <div class="flex justify-between font-bold text-neutral-950 text-xs pt-1 border-t border-neutral-300">
                        <span>TOTAL</span>
                        <span id="slip-total">0</span>
                    </div>
                    <div class="flex justify-between text-neutral-700 pt-1">
                        <span>BAYAR</span>
                        <span id="slip-paid">0</span>
                    </div>
                    <div class="flex justify-between font-bold text-neutral-900">
                        <span>KEMBALIAN</span>
                        <span id="slip-change">0</span>
                    </div>
                </div>

                <!-- Footer Struk -->
                <div class="text-center pt-2 text-[10px] text-neutral-500">
                    <p><?= htmlspecialchars($toko['printer_footer']) ?></p>
                </div>
            </div>
        </div>

    </div>
</div>

<?php 
$extraScripts = '<script src="public/js/pos.js"></script>';
require_once __DIR__ . '/templates/footer.php'; 
?>
