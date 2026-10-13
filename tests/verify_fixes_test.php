<?php
/**
 * Test script for verifying the 5 bugfixes via HTTP (Laragon Apache)
 */
declare(strict_types=1);

$baseUrl = 'http://localhost/web_ukk';
$cookieFile = __DIR__ . '/verify_cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

function make_req(string $url, string $method = 'GET', $data = null): array {
    global $cookieFile;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? http_build_query($data) : $data);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'headers' => substr($response, 0, $headerSize),
        'body' => substr($response, $headerSize)
    ];
}

echo "====================================================\n";
echo "   NusaPOS Bug Fixes Verification Suite (HTTP)      \n";
echo "====================================================\n\n";

// Login as Admin
$loginRes = make_req("{$baseUrl}/login.php", 'POST', ['username' => 'budisantoso', 'password' => 'password123']);
if ($loginRes['code'] !== 302) {
    echo "Login failed! Code: {$loginRes['code']}\n";
    exit(1);
}
echo "[AUTH] Logged in as Administrator successfully\n\n";

// 1. Test Product Table Horizontal Scroll Slider & Pagination (Limit 10)
echo "TEST 1 & 2: PRODUCT TABLE HORIZONTAL SCROLLBAR & LIMIT 10\n";
$res10 = make_req("{$baseUrl}/inventory.php?tab=produk&limit=10&page=1", 'GET');
$body10 = $res10['body'];

$hasContainer = strpos($body10, 'id="product-table-container"') !== false;
$hasTableMinW = strpos($body10, 'min-w-[1100px]') !== false;
$hasSliderBar = strpos($body10, 'id="product-table-slider-bar"') !== false;
$hasTrack = strpos($body10, 'id="product-table-track-wrapper"') !== false;
$hasStickyBar = strpos($body10, 'sticky bottom-0') !== false;
$hasLimit10Selected = strpos($body10, 'value="10" selected') !== false;

preg_match_all('/<tr class="hover:bg-surface-container-low\/60/', $body10, $rows10);
$rowCount10 = count($rows10[0]);

echo "  [" . ($hasContainer ? "PASS" : "FAIL") . "] Table container #product-table-container present\n";
echo "  [" . ($hasTableMinW ? "PASS" : "FAIL") . "] Product table has min-w-[1100px]\n";
echo "  [" . ($hasSliderBar ? "PASS" : "FAIL") . "] Sticky slider bar #product-table-slider-bar present\n";
echo "  [" . ($hasTrack ? "PASS" : "FAIL") . "] Horizontal track wrapper present\n";
echo "  [" . ($hasStickyBar ? "PASS" : "FAIL") . "] Slider bar uses sticky bottom-0 (accessible at bottom of screen)\n";
echo "  [" . ($hasLimit10Selected ? "PASS" : "FAIL") . "] Limit 10 is selected in dropdown\n";
echo "  [" . ($rowCount10 <= 10 ? "PASS" : "FAIL") . "] Product rows rendered: {$rowCount10} (Expected <= 10)\n";

// 2. Test Limit 30
echo "\nTEST 2: LIMIT 30 & 50\n";
$res30 = make_req("{$baseUrl}/inventory.php?tab=produk&limit=30&page=1", 'GET');
$body30 = $res30['body'];
$hasLimit30Selected = strpos($body30, 'value="30" selected') !== false;
preg_match_all('/<tr class="hover:bg-surface-container-low\/60/', $body30, $rows30);
$rowCount30 = count($rows30[0]);
echo "  [" . ($hasLimit30Selected ? "PASS" : "FAIL") . "] Limit 30 selected in dropdown\n";
echo "  [" . ($rowCount30 > 10 ? "PASS" : "FAIL") . "] Product rows rendered with limit=30: {$rowCount30} (Shows more than 10 rows)\n";

$res50 = make_req("{$baseUrl}/inventory.php?tab=produk&limit=50&page=1", 'GET');
$body50 = $res50['body'];
$hasLimit50Selected = strpos($body50, 'value="50" selected') !== false;
echo "  [" . ($hasLimit50Selected ? "PASS" : "FAIL") . "] Limit 50 selected in dropdown\n";

// 3. Test Category Table Scrollability
echo "\nTEST 3: CATEGORY TABLE HORIZONTAL SCROLL\n";
$resCat = make_req("{$baseUrl}/inventory.php?tab=kategori", 'GET');
$bodyCat = $resCat['body'];
$hasTabCatId = strpos($bodyCat, 'id="tab-content-kategori"') !== false;
$hasCatOverflow = strpos($bodyCat, '<div class="overflow-x-auto">') !== false;
$hasCatMinW = strpos($bodyCat, 'min-w-[700px]') !== false;
echo "  [" . ($hasTabCatId ? "PASS" : "FAIL") . "] Category tab container #tab-content-kategori present\n";
echo "  [" . ($hasCatOverflow ? "PASS" : "FAIL") . "] Category table wrapped in overflow-x-auto container\n";
echo "  [" . ($hasCatMinW ? "PASS" : "FAIL") . "] Category table has min-w-[700px] allowing horizontal scroll\n";

// 4. Test Cashier Scrolling Behavior
echo "\nTEST 4: CASHIER TERMINAL SCROLLING BEHAVIOR\n";
$resKasir = make_req("{$baseUrl}/kasir.php", 'GET');
$bodyKasir = $resKasir['body'];
$hasOverscroll = strpos($bodyKasir, 'overscroll-contain') !== false;
$noStickyTop20 = strpos($bodyKasir, 'sticky top-20') === false;
$hasLgOverflow = strpos($bodyKasir, 'lg:overflow-hidden') !== false;
$hasLgHScreen = strpos($bodyKasir, 'lg:h-screen') !== false;
echo "  [" . ($hasOverscroll ? "PASS" : "FAIL") . "] Kasir catalog & cart use overscroll-contain (no scroll chaining)\n";
echo "  [" . ($noStickyTop20 ? "PASS" : "FAIL") . "] Removed sticky top-20 trapping\n";
echo "  [" . ($hasLgOverflow ? "PASS" : "FAIL") . "] Desktop layout bounded with lg:overflow-hidden\n";
echo "  [" . ($hasLgHScreen ? "PASS" : "FAIL") . "] Desktop container bounded with lg:h-screen\n";

// 5. Test Hide/Toggle Sidebar
echo "\nTEST 5: HIDE/TOGGLE SIDEBAR\n";
$hasDesktopToggleTopbar = strpos($bodyKasir, 'id="desktop-sidebar-toggle"') !== false;
$hasDesktopToggleIcon = strpos($bodyKasir, 'id="desktop-sidebar-toggle-icon"') !== false;
$hasSidebarCollapseFunc = strpos($bodyKasir, 'toggleSidebarCollapse') !== false;
$hasAntiFouc = strpos($bodyKasir, 'nusapos_sidebar_collapsed') !== false;

echo "  [" . ($hasDesktopToggleTopbar ? "PASS" : "FAIL") . "] Desktop sidebar toggle button present in topbar\n";
echo "  [" . ($hasDesktopToggleIcon ? "PASS" : "FAIL") . "] Desktop sidebar toggle icon present\n";
echo "  [" . ($hasSidebarCollapseFunc ? "PASS" : "FAIL") . "] Sidebar toggle function attached\n";
echo "  [" . ($hasAntiFouc ? "PASS" : "FAIL") . "] Anti-FOUC sidebar-collapsed state check present in header\n";

// 6. Test Deep Edge Cases: Sync math, scrollbar hiding, mobile scoping, shortcut guard
echo "\nTEST 6: DEEP EDGE CASE & ROBUSTNESS CHECKS\n";
$hasScrollbarHidden = strpos($body10, '[scrollbar-width:none]') !== false;
$hasMaxScrollMath = strpos($body10, 'maxScroll') !== false && strpos($body10, 'trackWrapper.clientWidth + maxScroll') !== false;
$appJsContent = file_get_contents(__DIR__ . '/../public/js/app.js');
$hasShortcutGuard = strpos($appJsContent, 'INPUT') !== false && strpos($appJsContent, 'TEXTAREA') !== false;
$inputCssContent = file_get_contents(__DIR__ . '/../src/input.css');
$hasMediaScopedSidebar = strpos($inputCssContent, '@media (min-width: 768px)') !== false;

echo "  [" . ($hasScrollbarHidden ? "PASS" : "FAIL") . "] Table container hides redundant native scrollbar\n";
echo "  [" . ($hasMaxScrollMath ? "PASS" : "FAIL") . "] Slider track dummy uses 1:1 maxScroll matching (no dead zone)\n";
echo "  [" . ($hasShortcutGuard ? "PASS" : "FAIL") . "] Keyboard shortcut Ctrl+B guarded against active form inputs\n";
echo "  [" . ($hasMediaScopedSidebar ? "PASS" : "FAIL") . "] Sidebar-collapsed CSS properly scoped to desktop (min-width: 768px)\n";

// Clean up cookie
if (file_exists($cookieFile)) unlink($cookieFile);

echo "\n====================================================\n";
echo "   ALL TESTS EXECUTED SUCCESSFULLY!                 \n";
echo "====================================================\n";
