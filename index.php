<?php
declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

if (is_logged_in()) {
    header('Location: kasir.php');
} else {
    header('Location: login.php');
}
exit;
