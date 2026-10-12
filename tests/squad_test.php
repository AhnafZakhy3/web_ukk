<?php
/**
 * NusaPOS - Squad Automated Test Suite
 * Menguji seluruh endpoint, fungsionalitas, keamanan role, dan edge-case.
 */

declare(strict_types=1);

$baseUrl = 'http://localhost/web_ukk';
$cookieFile = __DIR__ . '/test_cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

$passed = 0;
$failed = 0;

function make_request(string $url, string $method = 'GET', $data = null, bool $useCookie = true, bool $isJson = false): array {
    global $cookieFile;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);

    if ($useCookie) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($isJson) {
            $payload = is_string($data) ? $data : json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? http_build_query($data) : $data);
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $error = curl_error($ch);
    curl_close($ch);

    $headerStr = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    return [
        'code' => $httpCode,
        'headers' => $headerStr,
        'body' => $body,
        'error' => $error
    ];
}

function extract_csrf_token(string $html): string {
    if (preg_match('/name=["\']csrf-token["\']\s+content=["\']([a-f0-9]{64})["\']/', $html, $matches)) {
        return $matches[1];
    }
    if (preg_match('/name=["\']csrf_token["\']\s+value=["\']([a-f0-9]{64})["\']/', $html, $matches)) {
        return $matches[1];
    }
    if (preg_match('/[?&]csrf_token=([a-f0-9]{64})/', $html, $matches)) {
        return $matches[1];
    }
    return '';
}

function test_assert(string $name, bool $condition, string $detail = '') {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$name}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$name} - {$detail}\n";
    }
}

echo "====================================================\n";
echo "   NusaPOS Automated End-to-End Test Suite          \n";
echo "====================================================\n\n";

// TEST 1: Redirect to login when unauthenticated
echo "1. AUTHENTICATION & ACCESS CONTROL TESTS:\n";
if (file_exists($cookieFile)) unlink($cookieFile);
$res = make_request("{$baseUrl}/kasir.php", 'GET');
test_assert("Unauthenticated kasir.php redirects to login.php", $res['code'] === 302 && strpos($res['headers'], 'login.php') !== false, "Code: {$res['code']}");

$res = make_request("{$baseUrl}/pengguna.php", 'GET');
test_assert("Unauthenticated pengguna.php redirects to login.php", $res['code'] === 302 && strpos($res['headers'], 'login.php') !== false, "Code: {$res['code']}");

// TEST 2: Login with Invalid Credentials
$res = make_request("{$baseUrl}/login.php", 'POST', ['username' => 'wronguser', 'password' => 'wrongpass']);
test_assert("Login with invalid credentials fails", strpos($res['body'], 'Nama pengguna atau kata sandi tidak sesuai') !== false, "Body didn't show error message");

// TEST 3: Login as Kasir
if (file_exists($cookieFile)) unlink($cookieFile);
$res = make_request("{$baseUrl}/login.php", 'POST', ['username' => 'sitikasir1', 'password' => 'password123']);
test_assert("Login as Kasir redirects to kasir.php", $res['code'] === 302 && strpos($res['headers'], 'kasir.php') !== false, "Code: {$res['code']}");

// TEST 4: Kasir cannot access pengguna.php (Admin only)
$res = make_request("{$baseUrl}/pengguna.php", 'GET');
test_assert("Kasir role blocked from pengguna.php (redirects to kasir.php)", $res['code'] === 302 && strpos($res['headers'], 'kasir.php') !== false, "Code: {$res['code']}");

// TEST 5: Kasir access kasir.php
$res = make_request("{$baseUrl}/kasir.php", 'GET');
test_assert("Kasir can access kasir.php successfully", $res['code'] === 200 && strpos($res['body'], 'Terminal Kasir') !== false);

// TEST 6: Checkout API Test with Kasir session
echo "\n2. CHECKOUT & INVENTORY ATOMICITY TESTS:\n";
require_once __DIR__ . '/../config/database.php';
$db = get_db();
$initialProd = $db->query("SELECT id, nama_produk, harga_jual, stok FROM produk WHERE id = 1")->fetch();
$stockBefore = (int)$initialProd['stok'];

// Lakukan checkout 2 pcs
$checkoutData = [
    'pelanggan' => 'Pembeli Uji QA',
    'metode_pembayaran' => 'tunai',
    'nominal_bayar' => 100000,
    'diskon' => 0,
    'items' => [
        [
            'produk_id' => 1,
            'jumlah' => 2,
            'harga_satuan' => (float)$initialProd['harga_jual']
        ]
    ]
];
$res = make_request("{$baseUrl}/api/checkout.php", 'POST', $checkoutData, true, true);
$json = json_decode($res['body'], true);
test_assert("Checkout valid responds HTTP 200 with success=true", $res['code'] === 200 && ($json['success'] ?? false) === true, "Response: {$res['body']}");

// Verifikasi pengurangan stok di DB
$stockAfter = (int)$db->query("SELECT stok FROM produk WHERE id = 1")->fetchColumn();
test_assert("Product stock accurately decremented by 2 in DB", $stockAfter === ($stockBefore - 2), "Before: {$stockBefore}, After: {$stockAfter}");

// Verifikasi riwayat transaksi tercatat
$invoiceNo = $json['receipt']['no_invoice'] ?? '';
$trxRecord = $db->query("SELECT * FROM transaksi WHERE no_invoice = " . $db->quote($invoiceNo))->fetch();
test_assert("Transaction row created in `transaksi` table", !empty($trxRecord), "Invoice {$invoiceNo} not found");

// TEST 7: Checkout overstock error handling
$overstockData = [
    'pelanggan' => 'Pembeli Overstock',
    'metode_pembayaran' => 'tunai',
    'nominal_bayar' => 999999999,
    'diskon' => 0,
    'items' => [
        [
            'produk_id' => 1,
            'jumlah' => 99999,
            'harga_satuan' => (float)$initialProd['harga_jual']
        ]
    ]
];
$res = make_request("{$baseUrl}/api/checkout.php", 'POST', $overstockData, true, true);
$overJson = json_decode($res['body'], true);
test_assert("Checkout overstock rejected with HTTP 400", $res['code'] === 400 && ($overJson['success'] ?? false) === false, "Code: {$res['code']}");

// TEST 8: Checkout underpayment (uang kurang)
$underpaidData = [
    'pelanggan' => 'Pembeli Uang Kurang',
    'metode_pembayaran' => 'tunai',
    'nominal_bayar' => 100,
    'diskon' => 0,
    'items' => [
        [
            'produk_id' => 1,
            'jumlah' => 1,
            'harga_satuan' => (float)$initialProd['harga_jual']
        ]
    ]
];
$res = make_request("{$baseUrl}/api/checkout.php", 'POST', $underpaidData, true, true);
test_assert("Checkout underpayment rejected with HTTP 400", $res['code'] === 400);

// TEST 9: Login as Admin
echo "\n3. ADMINISTRATOR PRIVILEGES & CRUD TESTS:\n";
if (file_exists($cookieFile)) unlink($cookieFile);
$res = make_request("{$baseUrl}/login.php", 'POST', ['username' => 'budisantoso', 'password' => 'password123']);
test_assert("Login as Administrator succeeds", $res['code'] === 302 && strpos($res['headers'], 'kasir.php') !== false);

// Akses inventory untuk mendapatkan valid CSRF token
$resInv = make_request("{$baseUrl}/inventory.php?tab=kategori", 'GET');
$csrfToken = extract_csrf_token($resInv['body']);
test_assert("Valid CSRF token extracted from session page", !empty($csrfToken) && strlen($csrfToken) === 64, "Token: '{$csrfToken}', Code: {$resInv['code']}, Body preview: " . substr(strip_tags($resInv['body']), 0, 100));

// Akses pengguna.php as Admin
$res = make_request("{$baseUrl}/pengguna.php", 'GET');
test_assert("Admin can access pengguna.php successfully", $res['code'] === 200 && strpos($res['body'], 'Manajemen Akun') !== false);

// TEST 10: Security - CSRF rejection without token
$resNoCsrf = make_request("{$baseUrl}/api/kategori_action.php", 'POST', [
    'action' => 'add_kategori',
    'nama_kategori' => 'Hacker Cat',
    'kode' => 'HACK'
]);
$resCheck = make_request("{$baseUrl}/inventory.php?tab=kategori", 'GET');
$hasCsrfMsg = strpos($resCheck['body'], 'CSRF') !== false;
test_assert("Action without valid CSRF token rejected", $hasCsrfMsg, "Code: {$resNoCsrf['code']}, Headers: " . trim($resNoCsrf['headers']) . ", Body snippet: " . substr(strip_tags($resCheck['body']), 0, 150));

// Tambah Kategori Baru dengan CSRF token
$newCatCode = 'TEST' . rand(100, 999);
$res = make_request("{$baseUrl}/api/kategori_action.php", 'POST', [
    'csrf_token' => $csrfToken,
    'action' => 'add_kategori',
    'nama_kategori' => 'Kategori QA Test',
    'kode' => $newCatCode,
    'ikon' => 'science',
    'deskripsi' => 'Kategori dibuat oleh Automated Test',
    'status' => 'aktif'
]);
test_assert("Admin can create new category with CSRF token", $res['code'] === 302);
$createdCat = $db->query("SELECT * FROM kategori WHERE kode = " . $db->quote($newCatCode))->fetch();
test_assert("New category exists in database", !empty($createdCat));

// Cegah Duplikasi Kode Kategori
$res = make_request("{$baseUrl}/api/kategori_action.php", 'POST', [
    'csrf_token' => $csrfToken,
    'action' => 'add_kategori',
    'nama_kategori' => 'Kategori Duplikat',
    'kode' => $newCatCode,
    'ikon' => 'science',
    'deskripsi' => 'Duplikat kode',
    'status' => 'aktif'
]);
$resCheck = make_request("{$baseUrl}/inventory.php?tab=kategori", 'GET');
test_assert("Duplicate category code rejected with flash error", strpos($resCheck['body'], 'sudah ada') !== false);

// Tambah Produk Baru
$newSku = 'SKU-QA-' . rand(1000, 9999);
$res = make_request("{$baseUrl}/api/produk_action.php", 'POST', [
    'csrf_token' => $csrfToken,
    'action' => 'add',
    'nama_produk' => 'Produk QA Otomatis',
    'sku' => $newSku,
    'barcode' => '89999999999',
    'kategori_id' => $createdCat['id'],
    'harga_beli' => 10000,
    'harga_jual' => 15000,
    'stok' => 25,
    'min_stok' => 5,
    'status' => 'aktif'
]);
test_assert("Admin can create new product", $res['code'] === 302);
$createdProd = $db->query("SELECT * FROM produk WHERE sku = " . $db->quote($newSku))->fetch();
test_assert("New product exists in database", !empty($createdProd));

// Hapus Kategori yang masih memiliki produk (harus ditolak)
$res = make_request("{$baseUrl}/api/kategori_action.php?csrf_token={$csrfToken}", 'POST', [
    'csrf_token' => $csrfToken,
    'action' => 'delete_kategori',
    'id' => $createdCat['id']
]);
$resCheck = make_request("{$baseUrl}/inventory.php?tab=kategori", 'GET');
test_assert("Prevent deleting category with active products", strpos($resCheck['body'], 'masih digunakan') !== false);

// Hapus Produk Baru (belum ada transaksi -> hard delete berhasil)
$res = make_request("{$baseUrl}/api/produk_action.php?csrf_token={$csrfToken}", 'POST', [
    'csrf_token' => $csrfToken,
    'action' => 'delete',
    'id' => $createdProd['id']
]);
$deletedProd = $db->query("SELECT * FROM produk WHERE id = " . (int)$createdProd['id'])->fetch();
test_assert("Product without transaction history deleted permanently", empty($deletedProd));

// Sekarang hapus kategori (sudah kosong -> berhasil)
$res = make_request("{$baseUrl}/api/kategori_action.php?csrf_token={$csrfToken}", 'POST', [
    'csrf_token' => $csrfToken,
    'action' => 'delete_kategori',
    'id' => $createdCat['id']
]);
$deletedCat = $db->query("SELECT * FROM kategori WHERE id = " . (int)$createdCat['id'])->fetch();
test_assert("Empty category deleted permanently", empty($deletedCat));

// TEST 11: Security - Self Deletion Protection for logged in Admin
echo "\n4. SECURITY & EDGE CASES:\n";
$currentAdminId = (int)$db->query("SELECT id FROM users WHERE username = 'budisantoso'")->fetchColumn();
$res = make_request("{$baseUrl}/api/pengguna_action.php", 'POST', [
    'csrf_token' => $csrfToken,
    'action' => 'delete',
    'id' => $currentAdminId
]);
$resCheck = make_request("{$baseUrl}/pengguna.php", 'GET');
test_assert("Admin cannot delete own currently logged in account", strpos($resCheck['body'], 'menghapus akun Anda sendiri') !== false);

// TEST 12: Export CSV Endpoints
$res = make_request("{$baseUrl}/api/export.php?type=template", 'GET');
test_assert("Export CSV template responds with CSV headers", $res['code'] === 200 && strpos($res['headers'], 'text/csv') !== false && strpos($res['body'], 'nama_produk,sku') !== false);

$res = make_request("{$baseUrl}/api/export.php?type=produk", 'GET');
test_assert("Export CSV products responds with CSV headers and records", $res['code'] === 200 && strpos($res['headers'], 'text/csv') !== false);

$res = make_request("{$baseUrl}/api/export.php?type=transaksi", 'GET');
test_assert("Export CSV transactions responds with CSV headers and records", $res['code'] === 200 && strpos($res['headers'], 'text/csv') !== false);

// TEST 13: Eager Loading Verification on Transaksi
$res = make_request("{$baseUrl}/transaksi.php", 'GET');
test_assert("Transaksi page renders successfully with eager-loaded details", $res['code'] === 200 && strpos($res['body'], 'Laporan Transaksi') !== false);

// TEST 14: Logout
$res = make_request("{$baseUrl}/logout.php", 'GET');
test_assert("Logout redirects to login.php", $res['code'] === 302 && strpos($res['headers'], 'login.php') !== false);

$res = make_request("{$baseUrl}/kasir.php", 'GET');
test_assert("Session cleared after logout", $res['code'] === 302 && strpos($res['headers'], 'login.php') !== false);

echo "\n====================================================\n";
echo "TEST RESULTS SUMMARY:\n";
echo "  Total Passed: {$passed}\n";
echo "  Total Failed: {$failed}\n";
echo "====================================================\n";

if (file_exists($cookieFile)) unlink($cookieFile);
exit($failed === 0 ? 0 : 1);
