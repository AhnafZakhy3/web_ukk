<?php
// logout wak
declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

logout_user();
$_SESSION['flash_success'] = 'Anda telah berhasil keluar dari sistem.';
header('Location: login.php');
exit;
