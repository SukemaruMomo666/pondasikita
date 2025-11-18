-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 12 Jul 2025 pada 04:00:53 WIB
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pondasikita_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `chat_sessions`
--

DROP TABLE IF EXISTS `chats`;

CREATE TABLE `chat_sessions` (
  `id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `guest_identifier` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('open','closed','pending') NOT NULL DEFAULT 'open',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_customer_activity` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_user_activity` timestamp NULL DEFAULT NULL,
  `customer_typing` tinyint(1) NOT NULL DEFAULT 0,
  `user_typing` tinyint(1) NOT NULL DEFAULT 0,
  `closed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cities`
--

CREATE TABLE `cities` (
  `id` int(10) UNSIGNED NOT NULL,
  `province_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `cities` (`id`, `province_id`, `name`) VALUES
(1, 1, 'KOTA JAKARTA PUSAT'),
(2, 1, 'KOTA JAKARTA UTARA'),
(3, 1, 'KOTA JAKARTA BARAT'),
(4, 1, 'KOTA JAKARTA SELATAN'),
(5, 1, 'KOTA JAKARTA TIMUR'),
(6, 2, 'KOTA BOGOR'),
(7, 2, 'KOTA DEPOK'),
(8, 2, 'KOTA BEKASI'),
(9, 2, 'KABUPATEN BOGOR'),
(10, 2, 'KABUPATEN BEKASI'),
(11, 2, 'KABUPATEN KARAWANG'),
(12, 3, 'KOTA TANGERANG'),
(13, 3, 'KOTA TANGERANG SELATAN'),
(14, 3, 'KABUPATEN TANGERANG'),
(15, 1, 'KAB. ADM. KEPULAUAN SERIBU');

-- --------------------------------------------------------

--
-- Struktur dari tabel `districts`
--

CREATE TABLE `districts` (
  `id` int(10) UNSIGNED NOT NULL,
  `city_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `districts` (`id`, `city_id`, `name`) VALUES
(1, 1, 'Gambir'),
(2, 1, 'Sawah Besar'),
(3, 1, 'Kemayoran'),
(4, 1, 'Senen'),
(5, 1, 'Cempaka Putih'),
(6, 1, 'Menteng'),
(7, 1, 'Tanah Abang'),
(8, 1, 'Johar Baru'),
(9, 2, 'Penjaringan'),
(10, 2, 'Tanjung Priok'),
(11, 2, 'Koja'),
(12, 2, 'Kelapa Gading'),
(13, 2, 'Pademangan'),
(14, 2, 'Cilincing'),
(15, 3, 'Cengkareng'),
(16, 3, 'Grogol Petamburan'),
(17, 3, 'Taman Sari'),
(18, 3, 'Tambora'),
(19, 3, 'Kebon Jeruk'),
(20, 3, 'Kalideres'),
(21, 3, 'Palmerah'),
(22, 3, 'Kembangan'),
(23, 4, 'Tebet'),
(24, 4, 'Setiabudi'),
(25, 4, 'Mampang Prapatan'),
(26, 4, 'Pasar Minggu'),
(27, 4, 'Kebayoran Lama'),
(28, 4, 'Cilandak'),
(29, 4, 'Kebayoran Baru'),
(30, 4, 'Pancoran'),
(31, 4, 'Jagakarsa'),
(32, 4, 'Pesanggrahan'),
(33, 5, 'Matraman'),
(34, 5, 'Pulo Gadung'),
(35, 5, 'Jatinegara'),
(36, 5, 'Duren Sawit'),
(37, 5, 'Kramat Jati'),
(38, 5, 'Makasar'),
(39, 5, 'Pasar Rebo'),
(40, 5, 'Ciracas'),
(41, 5, 'Cipayung'),
(42, 5, 'Cakung'),
(43, 15, 'Kepulauan Seribu Utara'),
(44, 15, 'Kepulauan Seribu Selatan'),
(45, 6, 'Bogor Selatan'),
(46, 6, 'Bogor Timur'),
(47, 6, 'Bogor Utara'),
(48, 6, 'Bogor Tengah'),
(49, 6, 'Bogor Barat'),
(50, 6, 'Tanah Sareal'),
(51, 7, 'Pancoran Mas'),
(52, 7, 'Cimanggis'),
(53, 7, 'Sawangan'),
(54, 7, 'Limo'),
(55, 7, 'Sukmajaya'),
(56, 7, 'Beji'),
(57, 7, 'Cipayung'),
(58, 7, 'Cilodong'),
(59, 7, 'Cinere'),
(60, 7, 'Tapos'),
(61, 7, 'Bojongsari'),
(62, 8, 'Bekasi Timur'),
(63, 8, 'Bekasi Barat'),
(64, 8, 'Bekasi Utara'),
(65, 8, 'Bekasi Selatan'),
(66, 8, 'Rawalumbu'),
(67, 8, 'Medan Satria'),
(68, 8, 'Bantar Gebang'),
(69, 8, 'Pondok Gede'),
(70, 8, 'Jatiasih'),
(71, 8, 'Jatisampurna'),
(72, 8, 'Mustika Jaya'),
(73, 8, 'Pondok Melati'),
(74, 9, 'Cibinong'),
(75, 9, 'Gunung Putri'),
(76, 9, 'Citeureup'),
(77, 9, 'Sukaraja'),
(78, 9, 'Babakan Madang'),
(79, 9, 'Jonggol'),
(80, 9, 'Cileungsi'),
(81, 9, 'Cariu'),
(82, 9, 'Sukamakmur'),
(83, 9, 'Parung'),
(84, 9, 'Gunung Sindur'),
(85, 9, 'Kemang'),
(86, 9, 'Bojonggede'),
(87, 9, 'Leuwiliang'),
(88, 9, 'Ciampea'),
(89, 9, 'Cibungbulang'),
(90, 9, 'Pamijahan'),
(91, 9, 'Rumpin'),
(92, 9, 'Jasinga'),
(93, 9, 'Parung Panjang'),
(94, 9, 'Nanggung'),
(95, 9, 'Cigudeg'),
(96, 9, 'Tenjo'),
(97, 9, 'Ciawi'),
(98, 9, 'Cisarua'),
(99, 9, 'Megamendung'),
(100, 9, 'Caringin'),
(101, 9, 'Cijeruk'),
(102, 9, 'Ciomas'),
(103, 9, 'Dramaga'),
(104, 9, 'Tamansari'),
(105, 9, 'Klapanunggal'),
(106, 9, 'Ciseeng'),
(107, 9, 'Rancabungur'),
(108, 9, 'Tajurhalang'),
(109, 9, 'Cigombong'),
(110, 9, 'Leuwisadeng'),
(111, 9, 'Tenjolaya'),
(112, 9, 'Tanjungsari'),
(113, 9, 'Sukajaya'),
(114, 10, 'Cikarang Pusat'),
(115, 10, 'Cikarang Selatan'),
(116, 10, 'Cikarang Utara'),
(117, 10, 'Cikarang Barat'),
(118, 10, 'Cibitung'),
(119, 10, 'Setu'),
(120, 10, 'Tambun Selatan'),
(121, 10, 'Tambun Utara'),
(122, 10, 'Cibarusah'),
(123, 10, 'Serang Baru'),
(124, 10, 'Karangbahagia'),
(125, 10, 'Pebayuran'),
(126, 10, 'Sukakarya'),
(127, 10, 'Sukatani'),
(128, 10, 'Sukawangi'),
(129, 10, 'Tambelang'),
(130, 10, 'Babelan'),
(131, 10, 'Tarumajaya'),
(132, 10, 'Muara Gembong'),
(133, 10, 'Cabangbungin'),
(134, 10, 'Kedungwaringin'),
(135, 10, 'Bojongmangu'),
(136, 11, 'Karawang Barat'),
(137, 11, 'Karawang Timur'),
(138, 11, 'Klari'),
(139, 11, 'Rengasdengklok'),
(140, 11, 'Kutawaluya'),
(141, 11, 'Batujaya'),
(142, 11, 'Telukjambe Timur'),
(143, 11, 'Telukjambe Barat'),
(144, 11, 'Cikampek'),
(145, 11, 'Jatisari'),
(146, 11, 'Cilamaya Wetan'),
(147, 11, 'Cilamaya Kulon'),
(148, 11, 'Lemahabang'),
(149, 11, 'Rawamerta'),
(150, 11, 'Tempuran'),
(151, 11, 'Tirtajaya'),
(152, 11, 'Pedes'),
(153, 11, 'Cibuaya'),
(154, 11, 'Pakisjaya'),
(155, 11, 'Tirtamulya'),
(156, 11, 'Cilebar'),
(157, 11, 'Jayakerta'),
(158, 11, 'Majalaya'),
(159, 11, 'Banyusari'),
(160, 11, 'Kotabaru'),
(161, 11, 'Ciampel'),
(162, 11, 'Pangkalan'),
(163, 11, 'Tegalwaru'),
(164, 11, 'Purwasari'),
(165, 11, 'Telagasari'),
(166, 12, 'Tangerang'),
(167, 12, 'Batuceper'),
(168, 12, 'Benda'),
(169, 12, 'Cibodas'),
(170, 12, 'Ciledug'),
(171, 12, 'Cipondoh'),
(172, 12, 'Jatiuwung'),
(173, 12, 'Karangtengah'),
(174, 12, 'Karawaci'),
(175, 12, 'Larangan'),
(176, 12, 'Neglasari'),
(177, 12, 'Periuk'),
(178, 12, 'Pinang'),
(179, 13, 'Serpong'),
(180, 13, 'Serpong Utara'),
(181, 13, 'Pondok Aren'),
(182, 13, 'Ciputat'),
(183, 13, 'Ciputat Timur'),
(184, 13, 'Pamulang'),
(185, 13, 'Setu'),
(186, 14, 'Balaraja'),
(187, 14, 'Cikupa'),
(188, 14, 'Cisauk'),
(189, 14, 'Cisoka'),
(190, 14, 'Curug'),
(191, 14, 'Gunung Kaler'),
(192, 14, 'Jambe'),
(193, 14, 'Jayanti'),
(194, 14, 'Kelapa Dua'),
(195, 14, 'Kemiri'),
(196, 14, 'Kresek'),
(197, 14, 'Kronjo'),
(198, 14, 'Legok'),
(199, 14, 'Mauk'),
(200, 14, 'Mekar Baru'),
(201, 14, 'Pagedangan'),
(202, 14, 'Pakuhaji'),
(203, 14, 'Panongan'),
(204, 14, 'Pasar Kemis'),
(205, 14, 'Rajeg'),
(206, 14, 'Sepatan'),
(207, 14, 'Sepatan Timur'),
(208, 14, 'Sindang Jaya'),
(209, 14, 'Solear'),
(210, 14, 'Sukadiri'),
(211, 14, 'Sukamulya'),
(212, 14, 'Teluknaga'),
(213, 14, 'Tigaraksa'),
(214, 14, 'Kosambi');

-- --------------------------------------------------------

--
-- Struktur dari tabel `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `chat_session_id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `sender_type` enum('customer','guest','user','bot') NOT NULL,
  `message_text` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read_by_user` tinyint(1) NOT NULL DEFAULT 0,
  `is_read_by_customer` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `provinces`
--

CREATE TABLE `provinces` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `provinces` (`id`, `name`) VALUES
(1, 'DKI JAKARTA'),
(2, 'JAWA BARAT'),
(3, 'BANTEN');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_barang`
--

CREATE TABLE `tb_barang` (
  `id` int(11) NOT NULL,
  `toko_id` int(11) NOT NULL,
  `kategori_id` int(11) NOT NULL,
  `kode_barang` varchar(20) DEFAULT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `merk_barang` varchar(100) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `gambar_utama` varchar(255) DEFAULT NULL,
  `harga` decimal(12,2) NOT NULL,
  `tipe_diskon` enum('NOMINAL','PERSEN') DEFAULT NULL,
  `nilai_diskon` decimal(12,2) DEFAULT NULL,
  `diskon_mulai` datetime DEFAULT NULL,
  `diskon_berakhir` datetime DEFAULT NULL,
  `satuan_unit` varchar(20) NOT NULL DEFAULT 'pcs' COMMENT 'Contoh: pcs, sak, batang, kaleng, m3',
  `stok` int(11) NOT NULL DEFAULT 0,
  `berat_kg` decimal(10,2) NOT NULL COMMENT 'Dalam Kilogram (KG)',
  `status_moderasi` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `alasan_penolakan` text DEFAULT NULL COMMENT 'Alasan dari admin jika produk ditolak',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tb_barang` (`id`, `toko_id`, `kategori_id`, `kode_barang`, `nama_barang`, `merk_barang`, `deskripsi`, `gambar_utama`, `harga`, `tipe_diskon`, `nilai_diskon`, `diskon_mulai`, `diskon_berakhir`, `satuan_unit`, `stok`, `berat_kg`, `status_moderasi`, `alasan_penolakan`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 11, NULL, 'Genteng merah dari raja sulaiman', '1ok', 'ini bukan main nih ayyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy', 'produk_1_1751965627.jpg', 100000.00, NULL, NULL, '2025-07-08 16:06:00', NULL, 'pcs', 10, 1.00, 'approved', NULL, 1, '2025-07-08 09:07:07', '2025-07-09 15:53:23'),
(2, 1, 16, NULL, 'ini bukan batu dari raja sulaiman', '112', 'ini banyak incianngaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'produk_1_1751965762.jpg', 800000.00, NULL, NULL, '2025-07-08 16:08:00', NULL, 'pcs', 88, 1.00, 'approved', NULL, 1, '2025-07-08 09:09:22', '2025-07-09 15:53:21'),
(3, 1, 13, NULL, 'raja sulaiman ilang cincinnya', '69', 'ini kemarennnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnn', 'produk_1_1751965843.jpg', 1000000.00, NULL, NULL, '2025-07-08 16:09:00', NULL, 'pcs', 11, 1.00, 'approved', NULL, 1, '2025-07-08 09:10:43', '2025-07-09 15:53:20'),
(4, 1, 7, NULL, 'raja sulaiman di curi cincinya', '2sds', 'gg gaming gggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggg', 'produk_1_1751965938.jpg', 2000000.00, NULL, NULL, '2025-07-08 16:11:00', NULL, 'pcs', 22, 1.00, 'approved', NULL, 1, '2025-07-08 09:12:18', '2025-07-09 15:53:17'),
(5, 2, 12, NULL, 'ini produk dari langsung ke', 'A32', 'oakwoakwowkokowkw gg produk Dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd', 'produk_2_1752107100.png', 1000000.00, NULL, NULL, '2025-07-10 07:23:00', NULL, 'pcs', 55, 1.00, 'approved', NULL, 1, '2025-07-10 00:25:00', '2025-07-10 00:25:16'),
(6, 3, 17, NULL, 'ini bukan produk sembarangan', 'A32', 'gg gaming nih produkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkk', 'produk_3_1752107490.png', 200000.00, NULL, NULL, '2025-07-10 07:30:00', NULL, 'pcs', 12, 2.00, 'approved', NULL, 1, '2025-07-10 00:31:30', '2025-07-10 00:31:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_barang_variasi`
--

CREATE TABLE `tb_barang_variasi` (
  `id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `nama_variasi` varchar(255) NOT NULL COMMENT 'Contoh: Warna: Merah, Ukuran: 5 KG',
  `kode_sku` varchar(50) DEFAULT NULL,
  `harga_tambahan` decimal(12,2) DEFAULT 0.00 COMMENT 'Tambahan dari harga dasar barang',
  `stok` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_biaya_pengiriman`
--

CREATE TABLE `tb_biaya_pengiriman` (
  `id` int(11) NOT NULL,
  `zona_id` int(11) NOT NULL,
  `tipe_biaya` enum('per_km','flat') NOT NULL DEFAULT 'flat',
  `biaya` decimal(10,2) NOT NULL,
  `deskripsi` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tb_biaya_pengiriman` (`id`, `zona_id`, `tipe_biaya`, `biaya`, `deskripsi`, `created_at`) VALUES
(1, 1, 'flat', 50000.00, 'Tarif flat untuk seluruh area Jabodetabek', '2025-07-09 21:06:06'),
(2, 2, 'per_km', 5000.00, 'Tarif per kilometer untuk area Bandung Raya', '2025-07-09 21:06:06'),
(3, 3, 'flat', 45000.00, 'Tarif flat untuk area Surabaya, Sidoarjo, Gresik', '2025-07-11 17:03:09'),
(4, 4, 'flat', 40000.00, 'Tarif flat Jogja Raya', '2025-07-11 17:03:09'),
(5, 5, 'flat', 40000.00, 'Tarif flat Solo Raya', '2025-07-11 17:03:09'),
(6, 6, 'flat', 45000.00, 'Tarif flat Semarang Raya', '2025-07-11 17:03:09'),
(7, 7, 'flat', 55000.00, 'Tarif flat Medan dan sekitarnya', '2025-07-11 17:03:09'),
(8, 8, 'flat', 60000.00, 'Tarif flat Makassar Raya', '2025-07-11 17:03:09'),
(9, 9, 'flat', 50000.00, 'Tarif flat seluruh wilayah Bali', '2025-07-11 17:03:09'),
(10, 10, 'per_km', 6000.00, 'Tarif per kilometer area Balikpapan dan Samarinda', '2025-07-11 17:03:09'),
(11, 11, 'flat', 70000.00, 'Tarif flat Batam dan Kepulauan Riau', '2025-07-11 17:03:09'),
(12, 12, 'per_km', 8000.00, 'Tarif per kilometer wilayah Papua', '2025-07-11 17:03:09'),
(13, 13, 'flat', 65000.00, 'Tarif flat wilayah Maluku', '2025-07-11 17:03:09'),
(14, 14, 'flat', 50000.00, 'Tarif flat Banjarmasin Raya', '2025-07-11 17:03:09'),
(15, 15, 'flat', 50000.00, 'Tarif flat Pontianak dan sekitarnya', '2025-07-11 17:03:09'),
(16, 16, 'flat', 45000.00, 'Tarif flat Padang dan wilayah sekitar', '2025-07-11 17:03:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_detail_transaksi`
--

CREATE TABLE `tb_detail_transaksi` (
  `id` int(11) NOT NULL,
  `transaksi_id` int(11) NOT NULL,
  `toko_id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `nama_barang_saat_transaksi` varchar(255) NOT NULL,
  `harga_saat_transaksi` decimal(15,2) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `metode_pengiriman` enum('DIKIRIM','DIAMBIL') NOT NULL DEFAULT 'DIKIRIM' COMMENT 'Menandai apakah item ini dikirim atau diambil di toko',
  `kurir_terpilih` varchar(100) DEFAULT NULL COMMENT 'Contoh: Pengiriman Toko, JNE, J&T',
  `biaya_pengiriman_item` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Ongkir untuk sub-pesanan dari toko ini',
  `status_pesanan_item` enum('diproses','siap_kirim','dikirim','sampai_tujuan','dibatalkan','pengajuan_pengembalian','pengembalian_disetujui','pengembalian_ditolak') NOT NULL DEFAULT 'diproses',
  `resi_pengiriman` varchar(100) DEFAULT NULL,
  `catatan_pembeli` text DEFAULT NULL COMMENT 'Alasan pembeli saat mengajukan retur/batal',
  `catatan_penjual` text DEFAULT NULL COMMENT 'Alasan penjual saat menolak retur/batal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_flash_sale_events`
--

CREATE TABLE `tb_flash_sale_events` (
  `id` int(11) NOT NULL,
  `nama_event` varchar(255) NOT NULL,
  `banner_event` varchar(255) DEFAULT NULL,
  `tanggal_mulai` datetime NOT NULL,
  `tanggal_berakhir` datetime NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tb_flash_sale_events` (`id`, `nama_event`, `banner_event`, `tanggal_mulai`, `tanggal_berakhir`, `is_active`) VALUES
(1, 'gg', '', '2025-07-09 03:25:00', '2025-07-18 03:25:00', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_flash_sale_produk`
--

CREATE TABLE `tb_flash_sale_produk` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `toko_id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `harga_flash_sale` decimal(12,2) NOT NULL,
  `stok_flash_sale` int(11) NOT NULL,
  `status_moderasi` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_gambar_barang`
--

CREATE TABLE `tb_gambar_barang` (
  `id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `nama_file` varchar(255) NOT NULL,
  `is_utama` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_kategori`
--

CREATE TABLE `tb_kategori` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `icon_class` varchar(100) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tb_kategori` (`id`, `nama_kategori`, `deskripsi`, `icon_class`, `parent_id`) VALUES
(1, 'Bahan Bangunan Dasar', 'Material utama untuk konstruksi', 'mdi-brick', NULL),
(2, 'Semen', 'Berbagai jenis semen untuk konstruksi', 'mdi-sack', 1),
(3, 'Semen Portland', 'Semen jenis umum', NULL, 2),
(4, 'Semen Putih', 'Semen untuk finishing dan dekorasi', NULL, 2),
(5, 'Pasir', 'Pasir untuk campuran bangunan', 'mdi-sand', 1),
(6, 'Batu', 'Berbagai jenis batu untuk konstruksi', 'mdi-stone', 1),
(7, 'Besi & Baja', 'Material besi dan baja untuk struktur', 'mdi-iron', NULL),
(8, 'Besi Beton', 'Besi untuk tulangan beton', NULL, 7),
(9, 'Baja Ringan', 'Baja untuk rangka atap', NULL, 7),
(10, 'Cat & Pelapis', 'Produk untuk finishing dan perlindungan permukaan', 'mdi-brush', NULL),
(11, 'Cat Tembok', 'Cat untuk dinding interior dan eksterior', NULL, 10),
(12, 'Cat Kayu & Besi', 'Cat untuk permukaan kayu dan besi', NULL, 10),
(13, 'Keramik & Granit', 'Material penutup lantai dan dinding', 'mdi-tiles', NULL),
(14, 'Keramik Lantai', 'Keramik untuk lantai', NULL, 13),
(15, 'Keramik Dinding', 'Keramik untuk dinding', NULL, 13),
(16, 'Granit', 'Material granit untuk lantai dan dinding', NULL, 13),
(17, 'Pipa & Perlengkapan Air', 'Sistem perpipaan dan sanitasi', 'mdi-pipe', NULL),
(18, 'Pipa PVC', 'Pipa plastik PVC', NULL, 17),
(19, 'Pipa Besi', 'Pipa dari bahan besi', NULL, 17),
(20, 'Perlengkapan Sanitasi', 'Kloset, wastafel, shower', NULL, 17);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_keranjang`
--

CREATE TABLE `tb_keranjang` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tb_keranjang` (`id`, `user_id`, `barang_id`, `jumlah`, `created_at`) VALUES
(2, 1, 3, 1, '2025-07-10 01:59:40'),
(3, 1, 6, 1, '2025-07-11 13:57:01');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_komisi`
--

CREATE TABLE `tb_komisi` (
  `id` int(11) NOT NULL,
  `detail_transaksi_id` int(11) NOT NULL,
  `jumlah_penjualan` decimal(15,2) NOT NULL,
  `persentase_komisi` decimal(5,2) NOT NULL,
  `jumlah_komisi` decimal(15,2) NOT NULL,
  `status` enum('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_kurir_toko`
--

CREATE TABLE `tb_kurir_toko` (
  `id` int(11) NOT NULL,
  `toko_id` int(11) NOT NULL,
  `nama_kurir` varchar(100) NOT NULL COMMENT 'Contoh: Pengiriman Toko, JNE Trucking, Lalamove',
  `estimasi_waktu` varchar(50) DEFAULT NULL COMMENT 'Contoh: 1-2 hari',
  `biaya` decimal(12,2) NOT NULL,
  `tipe_kurir` enum('TOKO','PIHAK_KETIGA') NOT NULL DEFAULT 'PIHAK_KETIGA' COMMENT 'Membedakan antara kurir internal toko dan ekspedisi pihak ketiga',
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_payouts`
--

CREATE TABLE `tb_payouts` (
  `id` int(11) NOT NULL,
  `toko_id` int(11) NOT NULL,
  `jumlah_payout` decimal(15,2) NOT NULL,
  `status` enum('pending','completed','rejected') NOT NULL DEFAULT 'pending',
  `tanggal_request` datetime NOT NULL DEFAULT current_timestamp(),
  `tanggal_proses` datetime DEFAULT NULL,
  `catatan_admin` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_pengaturan`
--

CREATE TABLE `tb_pengaturan` (
  `setting_nama` varchar(50) NOT NULL,
  `setting_nilai` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tb_pengaturan` (`setting_nama`, `setting_nilai`) VALUES
('aktifkan_fitur_live_chat', '1'),
('alamat_pusat', 'Jl. Jenderal Sudirman Kav. 52-53, Jakarta Selatan, DKI Jakarta 12190'),
('bank_rekening_platform', 'BCA'),
('deskripsi_website', 'Platform Jual Beli Bahan Bangunan Terlengkap se-Indonesia'),
('durasi_preorder_maks_hari', '30'),
('email_kontak', 'kontak@pondasikita.com'),
('Maps_api_key', ''),
('link_facebook', 'https://facebook.com/pondasikita'),
('link_instagram', 'https://instagram.com/pondasikita'),
('link_kebijakan_privasi', '/halaman/kebijakan-privasi'),
('link_syarat_ketentuan', '/halaman/syarat-ketentuan'),
('link_youtube', 'https://youtube.com/pondasikita'),
('maks_berat_pesanan_kg', '50'),
('maks_foto_produk', '8'),
('midtrans_client_key', ''),
('midtrans_server_key', ''),
('nama_rekening_platform', 'PT Pondasi Kita Indonesia'),
('nama_website', 'Pondasikita'),
('nomor_rekening_platform', '8881234567'),
('persentase_komisi', '5'),
('prefix_invoice', 'PNDSK'),
('rajaongkir_active_couriers', ''),
('rajaongkir_api_key', ''),
('rajaongkir_last_sync', NULL),
('telepon_kontak', '081234567890');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_review_produk`
--

CREATE TABLE `tb_review_produk` (
  `id` int(11) NOT NULL,
  `detail_transaksi_id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `ulasan` text DEFAULT NULL,
  `gambar_ulasan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_stok_histori`
--

CREATE TABLE `tb_stok_histori` (
  `id` bigint(20) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `variasi_id` int(11) DEFAULT NULL COMMENT 'Isi jika stok milik variasi',
  `jumlah` int(11) NOT NULL COMMENT 'Positif untuk stok masuk, negatif untuk stok keluar',
  `tipe_pergerakan` enum('initial','sale','sale_return','adjustment','stock_in') NOT NULL,
  `referensi` varchar(100) DEFAULT NULL COMMENT 'Contoh: Kode Invoice, Nomor Surat Jalan',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_toko`
--

CREATE TABLE `tb_toko` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nama_toko` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `deskripsi_toko` text DEFAULT NULL,
  `logo_toko` varchar(255) DEFAULT NULL,
  `banner_toko` varchar(255) DEFAULT NULL,
  `alamat_toko` text NOT NULL,
  `province_id` int(10) UNSIGNED DEFAULT NULL,
  `city_id` int(10) UNSIGNED DEFAULT NULL,
  `district_id` int(10) UNSIGNED DEFAULT NULL,
  `kode_pos` varchar(10) DEFAULT NULL,
  `telepon_toko` varchar(20) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('pending','active','suspended') NOT NULL DEFAULT 'pending',
  `status_operasional` enum('Buka','Tutup') NOT NULL DEFAULT 'Buka',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tb_toko` (`id`, `user_id`, `nama_toko`, `slug`, `deskripsi_toko`, `logo_toko`, `banner_toko`, `alamat_toko`, `province_id`, `city_id`, `district_id`, `kode_pos`, `telepon_toko`, `latitude`, `longitude`, `status`, `status_operasional`, `created_at`, `updated_at`) VALUES
(1, 5, 'QISA', 'qisa', NULL, NULL, NULL, 'Pagaden\r\nbabakan', NULL, 11, NULL, NULL, '085156677227', NULL, NULL, 'active', 'Buka', '2025-07-03 11:29:29', '2025-07-11 17:12:06'),
(2, 7, 'QistyStore', 'Qistore', 'ini wajib di isi toko qisty cantik', NULL, NULL, 'pagaden', NULL, 11, NULL, NULL, '', NULL, NULL, 'active', 'Buka', '2025-07-10 00:21:52', '2025-07-11 17:12:06'),
(3, 4, 'PrabuStore', 'prabustore', 'prabu store asli wak', NULL, NULL, 'subang', NULL, 12, NULL, NULL, '', NULL, NULL, 'active', 'Buka', '2025-07-10 00:29:32', '2025-07-11 17:12:06'),
(4, 8, 'Ambatugung', 'ambatugung', NULL, 'logo_1752251394_68713c028bf23.jpg', NULL, 'Jl Bung karno no 110, blok 3', 2, 11, 158, NULL, '08123030809', NULL, NULL, 'active', 'Buka', '2025-07-11 16:29:54', '2025-07-11 16:34:55');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_toko_dekorasi`
--

CREATE TABLE `tb_toko_dekorasi` (
  `id` int(11) NOT NULL,
  `toko_id` int(11) NOT NULL,
  `tipe_komponen` enum('BANNER','PRODUK_UNGGULAN','TEKS_GAMBAR') NOT NULL,
  `konten_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`konten_json`)),
  `urutan` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_toko_jam_operasional`
--

CREATE TABLE `tb_toko_jam_operasional` (
  `id` int(11) NOT NULL,
  `toko_id` int(11) NOT NULL,
  `hari` tinyint(1) NOT NULL COMMENT '1=Senin, 2=Selasa, ..., 7=Minggu',
  `is_buka` tinyint(1) NOT NULL DEFAULT 0,
  `jam_buka` time DEFAULT NULL,
  `jam_tutup` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_toko_pengaturan`
--

CREATE TABLE `tb_toko_pengaturan` (
  `toko_id` int(11) NOT NULL,
  `notif_email_pesanan` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Notif email untuk pesanan baru & update status',
  `notif_email_chat` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Notif email untuk chat baru',
  `notif_email_produk` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Notif email saat produk disetujui/ditolak',
  `notif_email_promo` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Notif email info promo dari platform',
  `chat_terima_otomatis` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Aktifkan/nonaktifkan kemampuan menerima chat'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_toko_review`
--

CREATE TABLE `tb_toko_review` (
  `id` int(11) NOT NULL,
  `toko_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaksi_id` int(11) NOT NULL COMMENT 'Untuk memvalidasi ulasan dari pembeli asli',
  `rating` tinyint(1) NOT NULL,
  `ulasan` text DEFAULT NULL,
  `balasan_penjual` text DEFAULT NULL COMMENT 'Kolom untuk menyimpan balasan dari penjual',
  `is_anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_transaksi`
--

CREATE TABLE `tb_transaksi` (
  `id` int(11) NOT NULL,
  `kode_invoice` varchar(50) NOT NULL,
  `sumber_transaksi` enum('ONLINE','OFFLINE') NOT NULL DEFAULT 'ONLINE' COMMENT 'Membedakan pesanan dari web atau dari POS kasir',
  `user_id` int(11) NOT NULL,
  `total_harga_produk` decimal(15,2) NOT NULL,
  `total_diskon` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_final` decimal(15,2) NOT NULL,
  `metode_pembayaran` varchar(50) DEFAULT NULL,
  `status_pembayaran` enum('pending','paid','failed','expired','cancelled') NOT NULL DEFAULT 'pending',
  `status_pesanan_global` enum('menunggu_pembayaran','diproses','selesai','dibatalkan') NOT NULL DEFAULT 'menunggu_pembayaran',
  `shipping_label_alamat` varchar(100) DEFAULT NULL,
  `shipping_nama_penerima` varchar(255) DEFAULT NULL,
  `shipping_telepon_penerima` varchar(20) DEFAULT NULL,
  `shipping_alamat_lengkap` text DEFAULT NULL,
  `shipping_kecamatan` varchar(100) DEFAULT NULL,
  `shipping_kota_kabupaten` varchar(100) DEFAULT NULL,
  `shipping_provinsi` varchar(100) DEFAULT NULL,
  `shipping_kode_pos` varchar(10) DEFAULT NULL,
  `snap_token` varchar(255) DEFAULT NULL,
  `tanggal_transaksi` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_user`
--

CREATE TABLE `tb_user` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `nama` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `profile_picture_url` varchar(255) DEFAULT NULL,
  `level` enum('admin','seller','customer','bot') NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_banned` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `is_online` tinyint(1) NOT NULL DEFAULT 0,
  `last_activity` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tb_user` (`id`, `username`, `password`, `google_id`, `nama`, `email`, `no_telepon`, `jenis_kelamin`, `tanggal_lahir`, `alamat`, `profile_picture_url`, `level`, `is_verified`, `is_banned`, `created_at`, `updated_at`, `reset_token`, `reset_token_expires_at`, `is_online`, `last_activity`) VALUES
(1, 'prabukeren', '$2y$10$PCWVkhM3W7BjTyBnv5eui.V/S/fN9zWy7Rar1D6DaIdyJfpnKzFDa', NULL, 'Prabu Alam Tian Try Suherman', 'prabualamtian@gmail.com', '085156677227', 'Laki-laki', '2005-04-20', 'Pagaden', 'user_1_1752119340.jpeg', 'customer', 0, 0, '2025-07-03 04:35:43', '2025-07-11 15:40:17', NULL, NULL, 0, NULL),
(4, 'prabukeren666', '$2y$10$fQCD3NU4N4M2AM.eYAeIO.Vzcjdp8bfMsOF54bHt0WP.7O8gUTw66', NULL, 'prabu alam tian try suherman', 'prabualamxii@gmail.com', '085156677227', NULL, NULL, 'subang', NULL, 'seller', 0, 0, '2025-07-03 08:35:03', NULL, NULL, NULL, 0, NULL),
(5, 'Prabu', '$2y$10$6IVUXxAgljWdzQ1ocqL5huELDlXilNSunAA9wFfMVlx1RUDGkOla/e', NULL, 'Prabu Alam Tian Try Suherman', 'prabuxmomo@gmail.com', NULL, NULL, NULL, NULL, NULL, 'seller', 0, 0, '2025-07-03 11:29:29', '2025-07-09 20:00:28', NULL, NULL, 0, NULL),
(6, 'AdminPondasikita', '$2y$10$4KefH6FArhoiIYjmCmF5ZefdLTYq5eHNNLtnkZIB9uQX7hlcZXC7i6', NULL, 'pondasikitamaster', 'prabualamxi@gmail.com', NULL, NULL, NULL, NULL, NULL, 'admin', 1, 0, '2025-07-09 14:30:56', NULL, NULL, NULL, 0, NULL),
(7, 'QistyCantik', '$2y$10$.m9R7n3AtgGf1F8bMtFFQuMGw/529Jt1O13D0fP2VHY5.ykKBv0NC', NULL, 'Qisty Sauva', '2qistysauva@gmail.com', NULL, NULL, NULL, NULL, NULL, 'seller', 1, 0, '2025-07-10 00:17:38', NULL, NULL, NULL, 0, NULL),
(8, 'Kiboy', '$2y$10$HzuyVN5ORrq7IXI57OcC3OZfu6zFfzxeesThKHjCTD359IUO.I2Q.', NULL, 'Abdul Halim', 'zyoz472@gmail.com', NULL, NULL, NULL, NULL, NULL, 'seller', 0, 0, '2025-07-11 16:29:54', NULL, NULL, NULL, 0, NULL),
(99, 'SiPondaBot', NULL, NULL, 'SiPonda AI', 'siponda.bot@pondasikita.com', NULL, NULL, NULL, NULL, NULL, 'bot', 1, 0, '2025-07-12 00:00:00', NULL, NULL, NULL, 1, NOW());

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_user_alamat`
--

CREATE TABLE `tb_user_alamat` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `label_alamat` varchar(50) NOT NULL,
  `nama_penerima` varchar(100) NOT NULL,
  `telepon_penerima` varchar(20) NOT NULL,
  `alamat_lengkap` text NOT NULL,
  `province_id` int(10) UNSIGNED DEFAULT NULL,
  `city_id` int(10) UNSIGNED DEFAULT NULL,
  `district_id` int(10) UNSIGNED DEFAULT NULL,
  `kode_pos` varchar(10) DEFAULT NULL,
  `is_utama` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tb_user_alamat` (`id`, `user_id`, `label_alamat`, `nama_penerima`, `telepon_penerima`, `alamat_lengkap`, `province_id`, `city_id`, `district_id`, `kode_pos`, `is_utama`) VALUES
(1, 1, 'Rumah', 'Prabu Alam Tian Try Suherman', '085156677227', '0', 2, 11, 138, '', 0),
(2, 1, 'Rumah', 'Prabu Alam Tian Try Suherman', '085156677227', 'di rumah mamah saya ya', 2, 11, 138, '', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_zona_pengiriman`
--

CREATE TABLE `tb_zona_pengiriman` (
  `id` int(11) NOT NULL,
  `nama_zona` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tb_zona_pengiriman` (`id`, `nama_zona`, `deskripsi`, `created_at`) VALUES
(1, 'Jabodetabek', 'Mencakup Jakarta, Bogor, Depok, Tangerang, Bekasi', '2025-07-09 20:59:57'),
(2, 'Bandung Raya', 'Mencakup Kota Bandung, Kabupaten Bandung, Cimahi', '2025-07-09 20:59:57'),
(3, 'Surabaya Metropolitan', 'Mencakup Surabaya, Sidoarjo, Gresik', '2025-07-09 20:59:57'),
(4, 'Jogja Raya', 'Mencakup Yogyakarta, Sleman, Bantul, Kulon Progo, Gunungkidul', '2025-07-11 17:00:20'),
(5, 'Solo Raya', 'Mencakup Kota Solo, Karanganyar, Sukoharjo, Boyolali, Klaten, Sragen', '2025-07-11 17:00:20'),
(6, 'Semarang Raya', 'Mencakup Kota Semarang, Kabupaten Semarang, Kendal, Demak, Ungaran', '2025-07-11 17:00:20'),
(7, 'Medan Metropolitan', 'Mencakup Medan, Binjai, Deli Serdang', '2025-07-11 17:00:20'),
(8, 'Makassar Raya', 'Mencakup Makassar, Gowa, Maros, Takalar', '2025-07-11 17:00:20'),
(9, 'Bali', 'Mencakup seluruh wilayah di Provinsi Bali', '2025-07-11 17:00:20'),
(10, 'Balikpapan-Samarinda', 'Mencakup Balikpapan, Samarinda, dan sekitarnya', '2025-07-11 17:00:20'),
(11, 'Batam dan Kepulauan Riau', 'Mencakup Batam, Tanjungpinang, dan kabupaten di Kepri', '2025-07-11 17:00:20'),
(12, 'Papua', 'Mencakup Jayapura, Timika, dan wilayah lainnya di Papua', '2025-07-11 17:00:20'),
(13, 'Maluku', 'Mencakup Ambon, Tual, dan daerah lain di Maluku', '2025-07-11 17:00:20'),
(14, 'Banjarmasin Raya', 'Mencakup Banjarmasin, Banjarbaru, dan Kabupaten Banjar', '2025-07-11 17:00:20'),
(15, 'Pontianak Raya', 'Mencakup Pontianak, Singkawang, dan sekitarnya', '2025-07-11 17:00:20'),
(16, 'Padang Raya', 'Mencakup Kota Padang, Bukittinggi, Payakumbuh, dan sekitarnya', '2025-07-11 17:00:20');

-- --------------------------------------------------------

--
-- Struktur dari tabel `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL,
  `toko_id` int(11) DEFAULT NULL COMMENT 'Jika NULL, voucher dari platform. Jika ada ID, voucher milik toko.',
  `kode_voucher` varchar(12) NOT NULL,
  `deskripsi` varchar(255) NOT NULL,
  `tipe_diskon` enum('RUPIAH','PERSEN') NOT NULL,
  `nilai_diskon` decimal(10,2) NOT NULL,
  `maks_diskon` decimal(10,2) DEFAULT NULL,
  `min_pembelian` decimal(10,2) DEFAULT 0.00,
  `kuota` int(11) NOT NULL,
  `kuota_terpakai` int(11) DEFAULT 0,
  `tanggal_mulai` datetime NOT NULL,
  `tanggal_berakhir` datetime NOT NULL,
  `status` enum('AKTIF','TIDAK_AKTIF','HABIS') DEFAULT 'AKTIF'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `province_id` (`province_id`);

--
-- Indeks untuk tabel `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `city_id` (`city_id`);

--
-- Indeks untuk tabel `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chat_session_id` (`chat_session_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indeks untuk tabel `provinces`
--
ALTER TABLE `provinces`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `tb_barang`
--
ALTER TABLE `tb_barang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_barang_toko` (`toko_id`),
  ADD KEY `fk_barang_kategori` (`kategori_id`);

--
-- Indeks untuk tabel `tb_barang_variasi`
--
ALTER TABLE `tb_barang_variasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `barang_id` (`barang_id`);

--
-- Indeks untuk tabel `tb_biaya_pengiriman`
--
ALTER TABLE `tb_biaya_pengiriman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `zona_id` (`zona_id`);

--
-- Indeks untuk tabel `tb_detail_transaksi`
--
ALTER TABLE `tb_detail_transaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaksi_id` (`transaksi_id`),
  ADD KEY `toko_id` (`toko_id`);

--
-- Indeks untuk tabel `tb_flash_sale_events`
--
ALTER TABLE `tb_flash_sale_events`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `tb_flash_sale_produk`
--
ALTER TABLE `tb_flash_sale_produk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_fs_event` (`event_id`),
  ADD KEY `fk_fs_toko` (`toko_id`),
  ADD KEY `fk_fs_barang` (`barang_id`);

--
-- Indeks untuk tabel `tb_gambar_barang`
--
ALTER TABLE `tb_gambar_barang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `barang_id` (`barang_id`);

--
-- Indeks untuk tabel `tb_kategori`
--
ALTER TABLE `tb_kategori`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kategori_parent` (`parent_id`);

--
-- Indeks untuk tabel `tb_keranjang`
--
ALTER TABLE `tb_keranjang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `barang_id` (`barang_id`);

--
-- Indeks untuk tabel `tb_komisi`
--
ALTER TABLE `tb_komisi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `detail_transaksi_id` (`detail_transaksi_id`);

--
-- Indeks untuk tabel `tb_kurir_toko`
--
ALTER TABLE `tb_kurir_toko`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kurir_toko` (`toko_id`);

--
-- Indeks untuk tabel `tb_payouts`
--
ALTER TABLE `tb_payouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `toko_id` (`toko_id`);

--
-- Indeks untuk tabel `tb_pengaturan`
--
ALTER TABLE `tb_pengaturan`
  ADD PRIMARY KEY (`setting_nama`);

--
-- Indeks untuk tabel `tb_review_produk`
--
ALTER TABLE `tb_review_produk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `detail_transaksi_id` (`detail_transaksi_id`);

--
-- Indeks untuk tabel `tb_stok_histori`
--
ALTER TABLE `tb_stok_histori`
  ADD PRIMARY KEY (`id`),
  ADD KEY `barang_id` (`barang_id`);

--
-- Indeks untuk tabel `tb_toko`
--
ALTER TABLE `tb_toko`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `fk_toko_user` (`user_id`),
  ADD KEY `fk_toko_province` (`province_id`),
  ADD KEY `fk_toko_city` (`city_id`),
  ADD KEY `fk_toko_district` (`district_id`);

--
-- Indeks untuk tabel `tb_toko_dekorasi`
--
ALTER TABLE `tb_toko_dekorasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_dekorasi_toko` (`toko_id`);

--
-- Indeks untuk tabel `tb_toko_jam_operasional`
--
ALTER TABLE `tb_toko_jam_operasional`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `toko_hari_unik` (`toko_id`,`hari`);

--
-- Indeks untuk tabel `tb_toko_pengaturan`
--
ALTER TABLE `tb_toko_pengaturan`
  ADD PRIMARY KEY (`toko_id`);

--
-- Indeks untuk tabel `tb_toko_review`
--
ALTER TABLE `tb_toko_review`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_review_toko_id` (`toko_id`),
  ADD KEY `fk_review_user_id` (`user_id`),
  ADD KEY `fk_review_transaksi_id` (`transaksi_id`);

--
-- Indeks untuk tabel `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_invoice` (`kode_invoice`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `tb_user`
--
ALTER TABLE `tb_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `tb_user_alamat`
--
ALTER TABLE `tb_user_alamat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_alamat_user` (`user_id`);

--
-- Indeks untuk tabel `tb_zona_pengiriman`
--
ALTER TABLE `tb_zona_pengiriman`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_voucher` (`kode_voucher`),
  ADD KEY `fk_voucher_toko` (`toko_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `chat_sessions`
--
ALTER TABLE `chat_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=215;

--
-- AUTO_INCREMENT untuk tabel `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `provinces`
--
ALTER TABLE `provinces`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `tb_barang`
--
ALTER TABLE `tb_barang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `tb_barang_variasi`
--
ALTER TABLE `tb_barang_variasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tb_biaya_pengiriman`
--
ALTER TABLE `tb_biaya_pengiriman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `tb_detail_transaksi`
--
ALTER TABLE `tb_detail_transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tb_flash_sale_events`
--
ALTER TABLE `tb_flash_sale_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `tb_flash_sale_produk`
--
ALTER TABLE `tb_flash_sale_produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tb_gambar_barang`
--
ALTER TABLE `tb_gambar_barang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tb_kategori`
--
ALTER TABLE `tb_kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `tb_keranjang`
--
ALTER TABLE `tb_keranjang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `tb_komisi`
--
ALTER TABLE `tb_komisi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tb_kurir_toko`
--
ALTER TABLE `tb_kurir_toko`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tb_payouts`
--
ALTER TABLE `tb_payouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tb_review_produk`
--
ALTER TABLE `tb_review_produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tb_stok_histori`
--
ALTER TABLE `tb_stok_histori`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tb_toko`
--
ALTER TABLE `tb_toko`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `tb_toko_dekorasi`
--
ALTER TABLE `tb_toko_dekorasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tb_toko_jam_operasional`
--
ALTER TABLE `tb_toko_jam_operasional`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tb_toko_pengaturan`
--
ALTER TABLE `tb_toko_pengaturan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tb_toko_review`
--
ALTER TABLE `tb_toko_review`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tb_user`
--
ALTER TABLE `tb_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT untuk tabel `tb_user_alamat`
--
ALTER TABLE `tb_user_alamat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `tb_zona_pengiriman`
--
ALTER TABLE `tb_zona_pengiriman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD CONSTRAINT `fk_chat_customer` FOREIGN KEY (`customer_id`) REFERENCES `tb_user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_chat_user` FOREIGN KEY (`user_id`) REFERENCES `tb_user` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `fk_cities_province` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `districts`
--
ALTER TABLE `districts`
  ADD CONSTRAINT `fk_districts_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_message_chat_session` FOREIGN KEY (`chat_session_id`) REFERENCES `chat_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_message_sender` FOREIGN KEY (`sender_id`) REFERENCES `tb_user` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `tb_barang`
--
ALTER TABLE `tb_barang`
  ADD CONSTRAINT `fk_barang_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `tb_kategori` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_barang_toko` FOREIGN KEY (`toko_id`) REFERENCES `tb_toko` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_barang_variasi`
--
ALTER TABLE `tb_barang_variasi`
  ADD CONSTRAINT `fk_variasi_barang` FOREIGN KEY (`barang_id`) REFERENCES `tb_barang` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_biaya_pengiriman`
--
ALTER TABLE `tb_biaya_pengiriman`
  ADD CONSTRAINT `fk_biaya_zona` FOREIGN KEY (`zona_id`) REFERENCES `tb_zona_pengiriman` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_detail_transaksi`
--
ALTER TABLE `tb_detail_transaksi`
  ADD CONSTRAINT `fk_detail_transaksi_toko` FOREIGN KEY (`toko_id`) REFERENCES `tb_toko` (`id`),
  ADD CONSTRAINT `fk_detail_transaksi_utama` FOREIGN KEY (`transaksi_id`) REFERENCES `tb_transaksi` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_flash_sale_produk`
--
ALTER TABLE `tb_flash_sale_produk`
  ADD CONSTRAINT `fk_fs_barang` FOREIGN KEY (`barang_id`) REFERENCES `tb_barang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fs_event` FOREIGN KEY (`event_id`) REFERENCES `tb_flash_sale_events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fs_toko` FOREIGN KEY (`toko_id`) REFERENCES `tb_toko` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_gambar_barang`
--
ALTER TABLE `tb_gambar_barang`
  ADD CONSTRAINT `fk_gambar_barang` FOREIGN KEY (`barang_id`) REFERENCES `tb_barang` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_kategori`
--
ALTER TABLE `tb_kategori`
  ADD CONSTRAINT `fk_kategori_parent` FOREIGN KEY (`parent_id`) REFERENCES `tb_kategori` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_keranjang`
--
ALTER TABLE `tb_keranjang`
  ADD CONSTRAINT `fk_keranjang_barang` FOREIGN KEY (`barang_id`) REFERENCES `tb_barang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_keranjang_user` FOREIGN KEY (`user_id`) REFERENCES `tb_user` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_komisi`
--
ALTER TABLE `tb_komisi`
  ADD CONSTRAINT `fk_komisi_detail` FOREIGN KEY (`detail_transaksi_id`) REFERENCES `tb_detail_transaksi` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_kurir_toko`
--
ALTER TABLE `tb_kurir_toko`
  ADD CONSTRAINT `fk_kurir_toko` FOREIGN KEY (`toko_id`) REFERENCES `tb_toko` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_payouts`
--
ALTER TABLE `tb_payouts`
  ADD CONSTRAINT `fk_payout_toko` FOREIGN KEY (`toko_id`) REFERENCES `tb_toko` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_pengaturan`
--
ALTER TABLE `tb_pengaturan`
  ADD CONSTRAINT `PRIMARY` PRIMARY KEY (`setting_nama`);

--
-- Ketidakleluasaan untuk tabel `tb_review_produk`
--
ALTER TABLE `tb_review_produk`
  ADD CONSTRAINT `fk_review_detail` FOREIGN KEY (`detail_transaksi_id`) REFERENCES `tb_detail_transaksi` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_stok_histori`
--
ALTER TABLE `tb_stok_histori`
  ADD CONSTRAINT `fk_stok_barang` FOREIGN KEY (`barang_id`) REFERENCES `tb_barang` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_toko`
--
ALTER TABLE `tb_toko`
  ADD CONSTRAINT `fk_toko_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_toko_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_toko_province` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_toko_user` FOREIGN KEY (`user_id`) REFERENCES `tb_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_toko_dekorasi`
--
ALTER TABLE `tb_toko_dekorasi`
  ADD CONSTRAINT `fk_dekorasi_toko` FOREIGN KEY (`toko_id`) REFERENCES `tb_toko` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_toko_jam_operasional`
--
ALTER TABLE `tb_toko_jam_operasional`
  ADD CONSTRAINT `fk_jam_toko` FOREIGN KEY (`toko_id`) REFERENCES `tb_toko` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_toko_pengaturan`
--
ALTER TABLE `tb_toko_pengaturan`
  ADD CONSTRAINT `fk_pengaturan_toko` FOREIGN KEY (`toko_id`) REFERENCES `tb_toko` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_toko_review`
--
ALTER TABLE `tb_toko_review`
  ADD CONSTRAINT `fk_review_toko_id` FOREIGN KEY (`toko_id`) REFERENCES `tb_toko` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_transaksi_id` FOREIGN KEY (`transaksi_id`) REFERENCES `tb_transaksi` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_user_id` FOREIGN KEY (`user_id`) REFERENCES `tb_user` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  ADD CONSTRAINT `fk_transaksi_user` FOREIGN KEY (`user_id`) REFERENCES `tb_user` (`id`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_user_alamat`
--
ALTER TABLE `tb_user_alamat`
  ADD CONSTRAINT `fk_alamat_user` FOREIGN KEY (`user_id`) REFERENCES `tb_user` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `vouchers`
--
ALTER TABLE `vouchers`
  ADD CONSTRAINT `fk_voucher_toko` FOREIGN KEY (`toko_id`) REFERENCES `tb_toko` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;