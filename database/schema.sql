CREATE DATABASE IF NOT EXISTS nusapos_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nusapos_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'kasir') NOT NULL DEFAULT 'kasir',
    cabang VARCHAR(100) NOT NULL DEFAULT 'Cabang Menteng - Jakarta Pusat',
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    avatar TEXT NULL,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    kode VARCHAR(50) NOT NULL UNIQUE,
    ikon VARCHAR(50) NOT NULL DEFAULT 'category',
    deskripsi TEXT NULL,
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS satuan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_satuan VARCHAR(50) NOT NULL,
    singkatan VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS produk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori_id INT NOT NULL,
    satuan_id INT NULL,
    sku VARCHAR(50) NOT NULL UNIQUE,
    barcode VARCHAR(50) NULL,
    nama_produk VARCHAR(150) NOT NULL,
    harga_beli DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    harga_jual DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    stok INT NOT NULL DEFAULT 0,
    min_stok INT NOT NULL DEFAULT 5,
    gambar TEXT NULL,
    deskripsi TEXT NULL,
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kategori (kategori_id),
    INDEX idx_barcode (barcode),
    INDEX idx_sku (sku),
    CONSTRAINT fk_produk_kategori FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE RESTRICT,
    CONSTRAINT fk_produk_satuan FOREIGN KEY (satuan_id) REFERENCES satuan(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_invoice VARCHAR(50) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    pelanggan VARCHAR(100) NOT NULL DEFAULT 'Pelanggan Umum (Walk-In)',
    subtotal DECIMAL(12,2) NOT NULL,
    pajak_ppn DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    diskon DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_akhir DECIMAL(12,2) NOT NULL,
    metode_pembayaran ENUM('tunai', 'qris', 'debit', 'transfer') NOT NULL DEFAULT 'tunai',
    nominal_bayar DECIMAL(12,2) NOT NULL,
    kembalian DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    catatan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_invoice (no_invoice),
    CONSTRAINT fk_transaksi_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS detail_transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_id INT NOT NULL,
    produk_id INT NOT NULL,
    nama_produk VARCHAR(150) NOT NULL,
    harga_satuan DECIMAL(12,2) NOT NULL,
    jumlah INT NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    INDEX idx_transaksi (transaksi_id),
    INDEX idx_produk (produk_id),
    CONSTRAINT fk_detail_transaksi FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE CASCADE,
    CONSTRAINT fk_detail_produk FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pengaturan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_toko VARCHAR(100) NOT NULL DEFAULT 'NusaPOS Retail Mart',
    cabang VARCHAR(100) NOT NULL DEFAULT 'Cabang Menteng - Jakarta Pusat',
    alamat TEXT NULL,
    telepon VARCHAR(50) NULL,
    persen_ppn DECIMAL(5,2) NOT NULL DEFAULT 11.00,
    printer_footer TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- bismilah optimal