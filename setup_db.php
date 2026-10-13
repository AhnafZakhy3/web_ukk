<?php
/**
 * NusaPOS - Database Setup Runner
 * Menjalankan migrasi DDL dan seeder data awal secara otomatis.
 */

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbName = 'nusapos_db';

echo "=== Memulai Inisialisasi Database NusaPOS ===\n";

try {
    // 1. Hubungkan ke MySQL Server
    $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "[OK] Terhubung ke MySQL server ({$host})\n";

    // 2. Bersihkan dan buat database dari awal
    $pdo->exec("DROP DATABASE IF EXISTS `{$dbName}`;");
    $pdo->exec("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `{$dbName}`;");
    echo "[OK] Database `{$dbName}` dibuat ulang secara bersih\n";

    // 3. Jalankan schema.sql
    $schemaFile = __DIR__ . '/database/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("File schema.sql tidak ditemukan di: {$schemaFile}");
    }
    $schemaSql = file_get_contents($schemaFile);
    $pdo->exec($schemaSql);
    echo "[OK] Skema tabel berhasil dibuat / diverifikasi\n";

    // 4. Jalankan seed data (Mendukung flag --clean untuk mengosongkan katalog & transaksi dummy)
    $isClean = in_array('--clean', $argv ?? []);

    if ($isClean) {
        $defaultPassword = password_hash('password123', PASSWORD_BCRYPT);
        $initSql = "
            INSERT INTO pengaturan (id, nama_toko, cabang, alamat, telepon, persen_ppn, printer_footer)
            VALUES (1, 'NusaPOS Retail Mart', 'Cabang Menteng - Jakarta Pusat', 'Jl. HOS Cokroaminoto No. 42, Menteng, Jakarta Pusat', '(021) 3928-1920', 11.00, 'Terima kasih telah berbelanja di NusaPOS!')
            ON DUPLICATE KEY UPDATE nama_toko=VALUES(nama_toko);

            INSERT INTO users (id, username, email, password, nama_lengkap, role, cabang, status)
            VALUES 
            (1, 'budisantoso', 'budi.santoso@nusapos.id', '{$defaultPassword}', 'Budi Santoso', 'admin', 'Cabang Menteng - Jakarta Pusat', 'aktif'),
            (2, 'sitikasir1', 'siti.rahma@nusapos.id', '{$defaultPassword}', 'Siti Rahmawati', 'kasir', 'Cabang Menteng - Jakarta Pusat', 'aktif')
            ON DUPLICATE KEY UPDATE username=VALUES(username);

            INSERT INTO satuan (id, nama_satuan, singkatan)
            VALUES
            (1, 'Pieces', 'Pcs'), (2, 'Botol', 'Btl'), (3, 'Pack', 'Pck'),
            (4, 'Dus / Karton', 'Dus'), (5, 'Cup', 'Cup'), (6, 'Kilogram', 'Kg'), (7, 'Liter', 'Ltr')
            ON DUPLICATE KEY UPDATE nama_satuan=VALUES(nama_satuan);
        ";
        $pdo->exec($initSql);
        echo "[OK] Mode Bersih (--clean): Hanya akun pengguna, pengaturan toko, dan satuan unit yang dibuat.\n";
    } else {
        $seedFile = __DIR__ . '/database/seed.sql';
        if (!file_exists($seedFile)) {
            throw new Exception("File seed.sql tidak ditemukan di: {$seedFile}");
        }
        $seedSql = file_get_contents($seedFile);
        $pdo->exec($seedSql);
        echo "[OK] Data awal (seed lengkap dengan data contoh) berhasil diisikan\n";
    }

    // 5. Verifikasi ringkas
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $prodCount = $pdo->query("SELECT COUNT(*) FROM produk")->fetchColumn();
    $catCount = $pdo->query("SELECT COUNT(*) FROM kategori")->fetchColumn();
    $trxCount = $pdo->query("SELECT COUNT(*) FROM transaksi")->fetchColumn();

    echo "-------------------------------------------\n";
    echo "STATUS DATABASE:\n";
    echo "- Users: {$userCount} akun\n";
    echo "- Kategori: {$catCount} kategori\n";
    echo "- Produk: {$prodCount} item\n";
    echo "- Transaksi Awal: {$trxCount} nota\n";
    echo "===========================================\n";
    echo "Inisialisasi database NusaPOS SELESAI DENGAN SUKSES!\n";

} catch (PDOException $e) {
    echo "[ERROR PDO] " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
