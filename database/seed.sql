-- ========================================================
-- NusaPOS Web Application - Seed Initial Data
-- ========================================================

USE nusapos_db;

-- 1. Pengaturan Toko
INSERT INTO pengaturan (id, nama_toko, cabang, alamat, telepon, persen_ppn, printer_footer)
VALUES (1, 'NusaPOS Retail Mart', 'Cabang Menteng - Jakarta Pusat', 'Jl. HOS Cokroaminoto No. 42, Menteng, Jakarta Pusat', '(021) 3928-1920', 11.00, 'Terima kasih telah berbelanja di NusaPOS! Simpan struk ini sebagai bukti pembayaran yang sah.')
ON DUPLICATE KEY UPDATE nama_toko=VALUES(nama_toko);

-- 2. Akun Pengguna (Password default: password123)
INSERT INTO users (id, username, email, password, nama_lengkap, role, cabang, status, avatar)
VALUES 
(1, 'budisantoso', 'budi.santoso@nusapos.id', '$2y$10$tDN3izBjU2Ezih05IijRwOjHUEMUCBRLA0JBFMLC4EbhsjmZitDG2', 'Budi Santoso', 'admin', 'Cabang Menteng - Jakarta Pusat', 'aktif', 'https://lh3.googleusercontent.com/aida-public/AB6AXuA1V7mwpjaYRQV655rnHXGfoDTck6B0zmlIs-s1loFKH0HfsOTifK6kDLIgFg-PYbFp8H_-BzRcMuVcmHMtxMRLWHUMUdnDsLH8s9DOjCmFoxpkqeeuBld_FjvXf9W3pJ9Wa0TtdZbTUIzXh4GZCB4nJfaDCMOJn6__cfP5bRE8lsKwh1ZCZpm8ToZSP82nGijOa2WfSsU-usn7nuYFv2FGryi3OyZvjk13u9xSfBrig8vO812_OF_P'),
(2, 'sitikasir1', 'siti.rahma@nusapos.id', '$2y$10$tDN3izBjU2Ezih05IijRwOjHUEMUCBRLA0JBFMLC4EbhsjmZitDG2', 'Siti Rahmawati', 'kasir', 'Cabang Menteng - Jakarta Pusat', 'aktif', 'https://lh3.googleusercontent.com/aida-public/AB6AXuC_TfKmSNv4gK6C2G-Fx-TMHmOvfJw-NeXLduHaYECL2b2iUnY_FTYO_Q0bzeQu6zDuPluH7kESi2Xrv1ZaVcWtUoI710yk75-3Faxv63VxX-IXbB37y1dnK2FfSBkZCAhfI4uwrh1JWw8y4u7cJq7mqa_nP7wZ0J74925rasHwMwJBs8LA2KsNyiTUSSh9yOg0IOeR9ClM1QYxHbS5qjmrUfd06UkAxc0VzM09c1lre2AK6aHPsLhb'),
(3, 'andrirahmat', 'andri.r@nusapos.id', '$2y$10$tDN3izBjU2Ezih05IijRwOjHUEMUCBRLA0JBFMLC4EbhsjmZitDG2', 'Andri Rahmat', 'kasir', 'Cabang Menteng - Jakarta Pusat', 'aktif', NULL),
(4, 'dewilestari', 'dewi.l@nusapos.id', '$2y$10$tDN3izBjU2Ezih05IijRwOjHUEMUCBRLA0JBFMLC4EbhsjmZitDG2', 'Dewi Lestari', 'kasir', 'Cabang Menteng - Jakarta Pusat', 'nonaktif', NULL)
ON DUPLICATE KEY UPDATE username=VALUES(username);

-- 3. Satuan Unit
INSERT INTO satuan (id, nama_satuan, singkatan)
VALUES
(1, 'Pieces', 'Pcs'),
(2, 'Botol', 'Btl'),
(3, 'Pack', 'Pck'),
(4, 'Dus / Karton', 'Dus'),
(5, 'Cup', 'Cup'),
(6, 'Kilogram', 'Kg'),
(7, 'Liter', 'Ltr')
ON DUPLICATE KEY UPDATE nama_satuan=VALUES(nama_satuan);

-- 4. Kategori Produk
INSERT INTO kategori (id, nama_kategori, kode, ikon, deskripsi, status)
VALUES
(1, 'Minuman Dingin', 'MINUMAN', 'local_bar', 'Kategori produk minuman segar, dingin, jus, dan kopi botol', 'aktif'),
(2, 'Makanan Ringan', 'MAKANAN', 'bakery_dining', 'Roti, biskuit, camilan gurih, dan mi instan', 'aktif'),
(3, 'Sembako', 'SEMBAKO', 'rice_bowl', 'Beras, minyak goreng, gula, telur, dan kebutuhan pokok dapur', 'aktif'),
(4, 'Kebutuhan Rumah', 'RUMAH', 'cleaning_services', 'Deterjen, sabun mandi, tisu, dan perlengkapan kebersihan rumah', 'aktif'),
(5, 'Rokok & Tembakau', 'TEMBAKAU', 'smoking_rooms', 'Rokok filter, kretek, dan produk tembakau berizin', 'aktif'),
(6, 'Kesehatan & Obat', 'KESEHATAN', 'medication', 'Vitamin, suplemen kesehatan, obat bebas, dan plester', 'aktif')
ON DUPLICATE KEY UPDATE nama_kategori=VALUES(nama_kategori);

-- 5. Katalog Produk (Mengacu pada contoh di implement.md & Google Stitch)
INSERT INTO produk (id, kategori_id, satuan_id, sku, barcode, nama_produk, harga_beli, harga_jual, stok, min_stok, gambar, status)
VALUES
(1, 1, 5, 'SKU-MNM-001', '899276100142', 'Kopi Susu Aren 250ml', 12000.00, 18000.00, 34, 5, 'https://lh3.googleusercontent.com/aida-public/AB6AXuD_ARi0wOuk0FQRONRsokV5X3jPS179oDhA4ENmNO-I32TmqRiG6pjMFM-1-OGGYMdOI7OJboDe1GicRksTQ33ZBWqhzX7TTYYljgMvLmheYzZ8hjW_CEYC4agvQE1LeC-zHcVA7xQQsTya9nx5EwlvLaBqPJJSp38VI6CVqpcJTA-UxXRDGZOtjlha1_qgcq8fUvN1P4bTqmOC-yHR86ValmwXnHgAkeGdVOc7q8CRFpndgBuNyN40', 'aktif'),
(2, 2, 3, 'SKU-MKN-014', '899317550211', 'Roti Cokelat Keju Spesial', 8500.00, 12500.00, 15, 5, 'https://lh3.googleusercontent.com/aida-public/AB6AXuCNtwbvuSWHxyBavZvPDBUkqZxKyqGdCTupK-8A129h7cVrpJTjeiRH-ro6PggzzvXvRjT0MPfgPKagtPO0RjKskmWKfmBbmgyuKjH-3E7yBk0rceeyun5OuhEenVw2SRUviIGO9Innf72MImKVbhtTk_qwEJZuUcyEOpi8TSepmTa8vVhZDjfjP8E0AKn8vfvxhxTfqBSxbbTFmYv6PdtqEVxwEhNrLmkcsG7_7HreRkU0TwMdnbm4', 'aktif'),
(3, 1, 2, 'SKU-MNM-004', '899100110452', 'Teh Botol Melati 450ml', 4500.00, 6500.00, 48, 10, 'https://lh3.googleusercontent.com/aida-public/AB6AXuDCm4W4m4-s85iYv_Gg9r-QoR4iUjUa0U4oD0KkLm7nOpQrStUvWxYz0123456789', 'aktif'),
(4, 1, 2, 'SKU-MNM-008', '899886610011', 'Air Mineral Pegunungan 600ml', 2500.00, 4000.00, 85, 20, NULL, 'aktif'),
(5, 2, 3, 'SKU-MKN-022', '899277512034', 'Mi Instan Goreng Spesial 85g', 2800.00, 3500.00, 120, 24, NULL, 'aktif'),
(6, 2, 3, 'SKU-MKN-031', '899100234123', 'Biskuit Cokelat Sandwich 120g', 6000.00, 9000.00, 22, 5, NULL, 'aktif'),
(7, 3, 6, 'SKU-SBK-001', '899212300112', 'Beras Pandan Wangi Premium 5kg', 68000.00, 78500.00, 18, 5, NULL, 'aktif'),
(8, 3, 7, 'SKU-SBK-002', '899456700234', 'Minyak Goreng Sawit 2L Pouch', 31000.00, 36000.00, 26, 6, NULL, 'aktif'),
(9, 3, 6, 'SKU-SBK-003', '899789100345', 'Gula Pasir Kristal Putih 1kg', 14500.00, 17500.00, 40, 10, NULL, 'aktif'),
(10, 4, 1, 'SKU-RMH-001', '899600100456', 'Sabun Cuci Piring Jeruk Nipis 750ml', 11000.00, 14500.00, 19, 5, NULL, 'aktif'),
(11, 4, 3, 'SKU-RMH-002', '899600200567', 'Tisu Wajah Lembut 250 Sheets', 9000.00, 13000.00, 31, 8, NULL, 'aktif'),
(12, 1, 3, 'SKU-MNM-012', '899800100678', 'Susu UHT Cokelat 200ml', 4200.00, 5500.00, 0, 10, NULL, 'aktif'),
(13, 6, 1, 'SKU-KSH-001', '899900100789', 'Minyak Kayu Putih 60ml', 18000.00, 23500.00, 4, 5, NULL, 'aktif')
ON DUPLICATE KEY UPDATE sku=VALUES(sku);

-- 6. Transaksi Awal Contoh (Agar laporan penjualan langsung terisi data realistis)
INSERT INTO transaksi (id, no_invoice, user_id, pelanggan, subtotal, pajak_ppn, diskon, total_akhir, metode_pembayaran, nominal_bayar, kembalian, catatan, created_at)
VALUES
(1, 'TRX-20261012-0001', 2, 'Pelanggan Umum (Walk-In)', 36000.00, 3960.00, 0.00, 39960.00, 'tunai', 50000.00, 10040.00, 'Transaksi kasir shift pagi', NOW() - INTERVAL 3 HOUR),
(2, 'TRX-20261012-0002', 2, 'Ibu Ratna', 68000.00, 7480.00, 5000.00, 70480.00, 'qris', 70480.00, 0.00, 'Member diskon promo belanja', NOW() - INTERVAL 2 HOUR),
(3, 'TRX-20261012-0003', 2, 'Pak Handoko', 114500.00, 12595.00, 0.00, 127095.00, 'debit', 127095.00, 0.00, 'Pembayaran EDC BCA', NOW() - INTERVAL 1 HOUR)
ON DUPLICATE KEY UPDATE no_invoice=VALUES(no_invoice);

-- Detail Transaksi
INSERT INTO detail_transaksi (transaksi_id, produk_id, nama_produk, harga_satuan, jumlah, subtotal)
VALUES
(1, 1, 'Kopi Susu Aren 250ml', 18000.00, 2, 36000.00),
(2, 1, 'Kopi Susu Aren 250ml', 18000.00, 2, 36000.00),
(2, 2, 'Roti Cokelat Keju Spesial', 12500.00, 1, 12500.00),
(2, 3, 'Teh Botol Melati 450ml', 6500.00, 3, 19500.00),
(3, 7, 'Beras Pandan Wangi Premium 5kg', 78500.00, 1, 78500.00),
(3, 8, 'Minyak Goreng Sawit 2L Pouch', 36000.00, 1, 36000.00);
