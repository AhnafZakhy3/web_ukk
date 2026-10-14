<?php

//   api endpoint checkout cransaksi 


declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/auth.php';

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesi login telah berakhir. Silakan login kembali.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode request tidak diizinkan.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data) || empty($data['items'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Data keranjang transaksi kosong atau tidak valid.']);
    exit;
}

$items = $data['items'];
$pelanggan = trim((string)($data['pelanggan'] ?? 'Pelanggan Umum (Walk-In)'));
$metodeBayar = strtolower(trim((string)($data['metode_pembayaran'] ?? 'tunai')));
$diskon = max(0, (float)($data['diskon'] ?? 0));
$nominalBayar = (float)($data['nominal_bayar'] ?? 0);

if (!in_array($metodeBayar, ['tunai', 'qris', 'debit', 'transfer'], true)) {
    $metodeBayar = 'tunai';
}

$db = get_db();

try {

    $db->beginTransaction();
    // buat validasi produk sama stok
    $productIds = array_map(fn($item) => (int)$item['produk_id'], $items);
    if (empty($productIds)) {
        throw new Exception("Tidak ada produk dalam keranjang.");
    }

    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = $db->prepare("SELECT id, nama_produk, harga_jual, stok FROM produk WHERE id IN ($placeholders) FOR UPDATE");
    $stmt->execute($productIds);
    $productsDb = [];
    while ($row = $stmt->fetch()) {
        $productsDb[$row['id']] = $row;
    }

    $subtotal = 0.0;
    $verifiedItems = [];
    $updatedStocks = [];

    foreach ($items as $item) {
        $pId = (int)$item['produk_id'];
        $qty = (int)$item['jumlah'];

        if (!isset($productsDb[$pId])) {
            throw new Exception("Produk ID #{$pId} tidak ditemukan di sistem.");
        }

        $prod = $productsDb[$pId];
        if ($qty <= 0) {
            throw new Exception("Kuantitas untuk '{$prod['nama_produk']}' tidak valid.");
        }

        if ($qty > $prod['stok']) {
            throw new Exception("Stok untuk '{$prod['nama_produk']}' tidak mencukupi (Tersedia: {$prod['stok']}, Diminta: {$qty}).");
        }

        $hargaSatuan = (float)$prod['harga_jual'];
        $lineSubtotal = $hargaSatuan * $qty;
        $subtotal += $lineSubtotal;

        $verifiedItems[] = [
            'produk_id'    => $pId,
            'nama_produk'  => $prod['nama_produk'],
            'harga_satuan' => $hargaSatuan,
            'jumlah'       => $qty,
            'subtotal'     => $lineSubtotal,
            'stok_baru'    => $prod['stok'] - $qty
        ];
    }

    $ppn = round($subtotal * 0.11);
    $totalAkhir = max(0, $subtotal + $ppn - $diskon);

    if ($metodeBayar === 'tunai' && $nominalBayar < $totalAkhir) {
        throw new Exception("Nominal uang yang diterima kurang dari total tagihan.");
    }

    $kembalian = ($metodeBayar === 'tunai') ? max(0, $nominalBayar - $totalAkhir) : 0.0;

    // mbuat no invoice struk
    $today = date('Ymd');
    $stmtSeq = $db->prepare("SELECT COUNT(*) FROM transaksi WHERE no_invoice LIKE ?");
    $stmtSeq->execute(["TRX-{$today}-%"]);
    $seq = (int)$stmtSeq->fetchColumn() + 1;
    $noInvoice = sprintf("TRX-%s-%04d", $today, $seq);

    $stmtInsertTrx = $db->prepare("
        INSERT INTO transaksi (
            no_invoice, user_id, pelanggan, subtotal, pajak_ppn, diskon, 
            total_akhir, metode_pembayaran, nominal_bayar, kembalian, catatan
        ) VALUES (
            :no_invoice, :user_id, :pelanggan, :subtotal, :pajak_ppn, :diskon,
            :total_akhir, :metode_pembayaran, :nominal_bayar, :kembalian, :catatan
        )
    ");

    $stmtInsertTrx->execute([
        ':no_invoice'        => $noInvoice,
        ':user_id'           => $user['id'],
        ':pelanggan'         => $pelanggan,
        ':subtotal'          => $subtotal,
        ':pajak_ppn'         => $ppn,
        ':diskon'            => $diskon,
        ':total_akhir'       => $totalAkhir,
        ':metode_pembayaran' => $metodeBayar,
        ':nominal_bayar'     => $nominalBayar,
        ':kembalian'         => $kembalian,
        ':catatan'           => 'Transaksi kasir berhasil',
    ]);
    $transaksiId = (int)$db->lastInsertId();

    $stmtInsertDetail = $db->prepare("
        INSERT INTO detail_transaksi (transaksi_id, produk_id, nama_produk, harga_satuan, jumlah, subtotal)
        VALUES (:transaksi_id, :produk_id, :nama_produk, :harga_satuan, :jumlah, :subtotal)
    ");

    $stmtDeductStock = $db->prepare("
        UPDATE produk SET stok = stok - ? WHERE id = ? AND stok >= ?
    ");

    foreach ($verifiedItems as $vItem) {
        $stmtInsertDetail->execute([
            ':transaksi_id' => $transaksiId,
            ':produk_id'    => $vItem['produk_id'],
            ':nama_produk'  => $vItem['nama_produk'],
            ':harga_satuan' => $vItem['harga_satuan'],
            ':jumlah'       => $vItem['jumlah'],
            ':subtotal'     => $vItem['subtotal'],
        ]);

        $stmtDeductStock->execute([
            $vItem['jumlah'],
            $vItem['produk_id'],
            $vItem['jumlah'],
        ]);

        $updatedStocks[] = [
            'id'   => $vItem['produk_id'],
            'stok' => $vItem['stok_baru']
        ];
    }

    $db->commit();

    $toko = get_toko_info();

    echo json_encode([
        'success' => true,
        'message' => 'Transaksi berhasil diproses.',
        'updated_stocks' => $updatedStocks,
        'receipt' => [
            'no_invoice'        => $noInvoice,
            'tanggal'           => date('d/m/Y H:i'),
            'nama_toko'         => $toko['nama_toko'],
            'cabang'            => $toko['cabang'],
            'alamat'            => $toko['alamat'],
            'kasir'             => $user['nama_lengkap'],
            'pelanggan'         => $pelanggan,
            'metode_pembayaran' => $metodeBayar,
            'subtotal'          => $subtotal,
            'pajak_ppn'         => $ppn,
            'diskon'            => $diskon,
            'total_akhir'       => $totalAkhir,
            'nominal_bayar'     => $nominalBayar,
            'kembalian'         => $kembalian,
            'items'             => $verifiedItems,
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
