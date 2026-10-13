<?php
/**
 * NusaPOS - Data Purge Utility
 * Mengosongkan data operasional (transaksi, produk, kategori)
 * dengan tetap mempertahankan akun pengguna (users), profil toko (pengaturan), dan satuan unit.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

echo "=== NusaPOS: Mengosongkan Data Operasional ===\n";

try {
    $db = get_db();

    // Nonaktifkan pemeriksaan foreign key sementara untuk truncate bersih
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // 1. Kosongkan tabel transaksi & detail transaksi
    $db->exec("TRUNCATE TABLE detail_transaksi;");
    $db->exec("TRUNCATE TABLE transaksi;");
    echo "[OK] Tabel transaksi & detail_transaksi berhasil dikosongkan.\n";

    // 2. Kosongkan tabel produk
    $db->exec("TRUNCATE TABLE produk;");
    echo "[OK] Tabel produk berhasil dikosongkan.\n";

    // 3. Kosongkan tabel kategori
    $db->exec("TRUNCATE TABLE kategori;");
    echo "[OK] Tabel kategori berhasil dikosongkan.\n";

    // Aktifkan kembali pemeriksaan foreign key
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // 4. Bersihkan file gambar hasil upload (kecuali .gitkeep)
    $uploadDir = __DIR__ . '/public/uploads/produk/';
    if (is_dir($uploadDir)) {
        $files = glob($uploadDir . '*');
        $deletedFiles = 0;
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== '.gitkeep') {
                unlink($file);
                $deletedFiles++;
            }
        }
        echo "[OK] Direktori unggahan gambar dibersihkan ({$deletedFiles} file dihapus).\n";
    }

    echo "-------------------------------------------\n";
    echo "STATUS DATABASE SAAT INI:\n";
    echo "- Users: " . $db->query("SELECT COUNT(*) FROM users")->fetchColumn() . " akun (TETAP DIPERTAHANKAN)\n";
    echo "- Pengaturan Toko: " . $db->query("SELECT COUNT(*) FROM pengaturan")->fetchColumn() . " baris (TETAP DIPERTAHANKAN)\n";
    echo "- Satuan Unit: " . $db->query("SELECT COUNT(*) FROM satuan")->fetchColumn() . " unit (TETAP DIPERTAHANKAN)\n";
    echo "- Kategori: " . $db->query("SELECT COUNT(*) FROM kategori")->fetchColumn() . " kategori (BERSIH 0)\n";
    echo "- Produk: " . $db->query("SELECT COUNT(*) FROM produk")->fetchColumn() . " produk (BERSIH 0)\n";
    echo "- Transaksi: " . $db->query("SELECT COUNT(*) FROM transaksi")->fetchColumn() . " transaksi (BERSIH 0)\n";
    echo "===========================================\n";
    echo "Database NusaPOS sekarang SIAP DIGUNAKAN SECARA BERSIH!\n";

} catch (Exception $e) {
    echo "[ERROR] Gagal mengosongkan database: " . $e->getMessage() . "\n";
    exit(1);
}
