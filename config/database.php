<?php
// file koneksi database

declare(strict_types=1);

function get_db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $host = '127.0.0.1';
        $db   = 'nusapos_db';
        $user = 'root';
        $pass = '';
        $port = 3306;
        $charset = 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            die("Gagal terhubung ke database MySQL NusaPOS: " . htmlspecialchars($e->getMessage()));
        }
    }

    return $pdo;
}
