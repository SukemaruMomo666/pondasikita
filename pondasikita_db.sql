-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 18, 2025 at 06:55 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

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
-- Table structure for table `chats`
--

CREATE TABLE `chats` (
  `id` int NOT NULL,
  `customer_id` int NOT NULL,
  `admin_id` int DEFAULT NULL,
  `toko_id` int DEFAULT NULL,
  `status` enum('open','closed','pending_admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'open',
  `start_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` int UNSIGNED NOT NULL,
  `province_id` int UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cities`
--

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
(15, 1, 'KAB. ADM. KEPULAUAN SERIBU'),
(21, 2, 'KABUPATEN SUBANG');

-- --------------------------------------------------------

--
-- Table structure for table `districts`
--

CREATE TABLE `districts` (
  `id` int UNSIGNED NOT NULL,
  `city_id` int UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `districts`
--

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
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int NOT NULL,
  `chat_id` int NOT NULL,
  `sender_id` int NOT NULL,
  `message_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `provinces`
--

CREATE TABLE `provinces` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `provinces`
--

INSERT INTO `provinces` (`id`, `name`) VALUES
(1, 'DKI JAKARTA'),
(2, 'JAWA BARAT'),
(3, 'BANTEN');

-- --------------------------------------------------------

--
-- Table structure for table `tb_barang`
--

CREATE TABLE `tb_barang` (
  `id` int NOT NULL,
  `toko_id` int NOT NULL,
  `kategori_id` int NOT NULL,
  `kode_barang` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_barang` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `merk_barang` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deskripsi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `gambar_utama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `harga` decimal(12,2) NOT NULL,
  `tipe_diskon` enum('NOMINAL','PERSEN') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nilai_diskon` decimal(12,2) DEFAULT NULL,
  `diskon_mulai` datetime DEFAULT NULL,
  `diskon_berakhir` datetime DEFAULT NULL,
  `satuan_unit` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pcs' COMMENT 'Contoh: pcs, sak, batang, kaleng, m3',
  `stok` int NOT NULL DEFAULT '0',
  `stok_di_pesan` int NOT NULL DEFAULT '0',
  `berat_kg` decimal(10,2) NOT NULL COMMENT 'Dalam Kilogram (KG)',
  `status_moderasi` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `alasan_penolakan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Alasan dari admin jika produk ditolak',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_barang`
--

INSERT INTO `tb_barang` (`id`, `toko_id`, `kategori_id`, `kode_barang`, `nama_barang`, `merk_barang`, `deskripsi`, `gambar_utama`, `harga`, `tipe_diskon`, `nilai_diskon`, `diskon_mulai`, `diskon_berakhir`, `satuan_unit`, `stok`, `stok_di_pesan`, `berat_kg`, `status_moderasi`, `alasan_penolakan`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 11, NULL, 'Genteng merah dari raja sulaiman', '1ok', 'ini bukan main nih ayyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy', 'produk_1_1751965627.jpg', 100000.00, NULL, NULL, '2025-07-08 16:06:00', NULL, 'pcs', 10, 0, 1.00, 'approved', NULL, 1, '2025-07-08 02:07:07', '2025-07-09 08:53:23'),
(2, 1, 16, NULL, 'ini bukan batu dari raja sulaiman', '112', 'ini banyak incianngaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'produk_1_1751965762.jpg', 800000.00, NULL, NULL, '2025-07-08 16:08:00', NULL, 'pcs', 88, 0, 1.00, 'approved', NULL, 1, '2025-07-08 02:09:22', '2025-07-11 19:46:31'),
(3, 1, 13, NULL, 'raja sulaiman ilang cincinnya', '69', 'ini kemarennnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnn', 'produk_1_1751965843.jpg', 1000000.00, NULL, NULL, '2025-07-08 16:09:00', NULL, 'pcs', 11, 0, 1.00, 'approved', NULL, 1, '2025-07-08 02:10:43', '2025-07-11 21:19:03'),
(4, 1, 7, NULL, 'raja sulaiman di curi cincinya', '2sds', 'gg gaming gggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggggg', 'produk_1_1751965938.jpg', 2000000.00, NULL, NULL, '2025-07-08 16:11:00', NULL, 'pcs', 22, 0, 1.00, 'approved', NULL, 1, '2025-07-08 02:12:18', '2025-07-09 08:53:17'),
(5, 2, 12, NULL, 'ini produk dari langsung ke', 'A32', 'oakwoakwowkokowkw gg produk Dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd', 'produk_2_1752107100.png', 1000000.00, NULL, NULL, '2025-07-10 07:23:00', NULL, 'pcs', 55, 0, 1.00, 'approved', NULL, 1, '2025-07-09 17:25:00', '2025-07-11 13:33:16'),
(6, 3, 17, NULL, 'ini bukan produk sembarangan', 'A32', 'gg gaming nih produkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkk', 'produk_3_1752107490.png', 200000.00, NULL, NULL, '2025-07-10 07:30:00', NULL, 'pcs', 12, 0, 2.00, 'approved', NULL, 1, '2025-07-09 17:31:30', '2025-07-09 17:31:40');

-- --------------------------------------------------------

--
-- Table structure for table `tb_barang_variasi`
--

CREATE TABLE `tb_barang_variasi` (
  `id` int NOT NULL,
  `barang_id` int NOT NULL,
  `nama_variasi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Contoh: Warna: Merah, Ukuran: 5 KG',
  `kode_sku` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `harga_tambahan` decimal(12,2) DEFAULT '0.00' COMMENT 'Tambahan dari harga dasar barang',
  `stok` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_biaya_pengiriman`
--

CREATE TABLE `tb_biaya_pengiriman` (
  `id` int NOT NULL,
  `zona_id` int NOT NULL,
  `tipe_biaya` enum('per_km','flat') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'flat',
  `biaya` decimal(10,2) NOT NULL,
  `deskripsi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_biaya_pengiriman`
--

INSERT INTO `tb_biaya_pengiriman` (`id`, `zona_id`, `tipe_biaya`, `biaya`, `deskripsi`, `created_at`) VALUES
(1, 1, 'flat', 50000.00, 'Tarif flat untuk seluruh area Jabodetabek', '2025-07-09 14:06:06'),
(2, 2, 'per_km', 5000.00, 'Tarif per kilometer untuk area Bandung Raya', '2025-07-09 14:06:06'),
(3, 3, 'flat', 45000.00, 'Tarif flat untuk area Surabaya, Sidoarjo, Gresik', '2025-07-11 10:03:09'),
(4, 4, 'flat', 40000.00, 'Tarif flat Jogja Raya', '2025-07-11 10:03:09'),
(5, 5, 'flat', 40000.00, 'Tarif flat Solo Raya', '2025-07-11 10:03:09'),
(6, 6, 'flat', 45000.00, 'Tarif flat Semarang Raya', '2025-07-11 10:03:09'),
(7, 7, 'flat', 55000.00, 'Tarif flat Medan dan sekitarnya', '2025-07-11 10:03:09'),
(8, 8, 'flat', 60000.00, 'Tarif flat Makassar Raya', '2025-07-11 10:03:09'),
(9, 9, 'flat', 50000.00, 'Tarif flat seluruh wilayah Bali', '2025-07-11 10:03:09'),
(10, 10, 'per_km', 6000.00, 'Tarif per kilometer area Balikpapan dan Samarinda', '2025-07-11 10:03:09'),
(11, 11, 'flat', 70000.00, 'Tarif flat Batam dan Kepulauan Riau', '2025-07-11 10:03:09'),
(12, 12, 'per_km', 8000.00, 'Tarif per kilometer wilayah Papua', '2025-07-11 10:03:09'),
(13, 13, 'flat', 65000.00, 'Tarif flat wilayah Maluku', '2025-07-11 10:03:09'),
(14, 14, 'flat', 50000.00, 'Tarif flat Banjarmasin Raya', '2025-07-11 10:03:09'),
(15, 15, 'flat', 50000.00, 'Tarif flat Pontianak dan sekitarnya', '2025-07-11 10:03:09'),
(16, 16, 'flat', 45000.00, 'Tarif flat Padang dan wilayah sekitar', '2025-07-11 10:03:09');

-- --------------------------------------------------------

--
-- Table structure for table `tb_detail_transaksi`
--

CREATE TABLE `tb_detail_transaksi` (
  `id` int NOT NULL,
  `transaksi_id` int NOT NULL,
  `toko_id` int NOT NULL,
  `barang_id` int NOT NULL,
  `nama_barang_saat_transaksi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `harga_saat_transaksi` decimal(15,2) NOT NULL,
  `jumlah` int NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `metode_pengiriman` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'DIKIRIM',
  `kurir_terpilih` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Contoh: Pengiriman Toko, JNE, J&T',
  `biaya_pengiriman_item` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Ongkir untuk sub-pesanan dari toko ini',
  `status_pesanan_item` enum('diproses','siap_kirim','dikirim','sampai_tujuan','dibatalkan','pengajuan_pengembalian','pengembalian_disetujui','pengembalian_ditolak') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'diproses',
  `resi_pengiriman` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catatan_pembeli` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Alasan pembeli saat mengajukan retur/batal',
  `catatan_penjual` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Alasan penjual saat menolak retur/batal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_detail_transaksi`
--

INSERT INTO `tb_detail_transaksi` (`id`, `transaksi_id`, `toko_id`, `barang_id`, `nama_barang_saat_transaksi`, `harga_saat_transaksi`, `jumlah`, `subtotal`, `metode_pengiriman`, `kurir_terpilih`, `biaya_pengiriman_item`, `status_pesanan_item`, `resi_pengiriman`, `catatan_pembeli`, `catatan_penjual`) VALUES
(2, 10, 2, 5, 'ini produk dari langsung ke', 1000000.00, 1, 1000000.00, '0', NULL, 15000.00, 'diproses', NULL, NULL, NULL),
(3, 11, 1, 2, 'ini bukan batu dari raja sulaiman', 800000.00, 1, 800000.00, '0', NULL, 15000.00, 'diproses', NULL, NULL, NULL),
(4, 12, 1, 3, 'raja sulaiman ilang cincinnya', 1000000.00, 1, 1000000.00, '0', NULL, 15000.00, 'diproses', NULL, NULL, NULL),
(5, 13, 1, 3, 'raja sulaiman ilang cincinnya', 1000000.00, 1, 1000000.00, '0', NULL, 15000.00, 'diproses', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_flash_sale_events`
--

CREATE TABLE `tb_flash_sale_events` (
  `id` int NOT NULL,
  `nama_event` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `banner_event` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tanggal_mulai` datetime NOT NULL,
  `tanggal_berakhir` datetime NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_flash_sale_events`
--

INSERT INTO `tb_flash_sale_events` (`id`, `nama_event`, `banner_event`, `tanggal_mulai`, `tanggal_berakhir`, `is_active`) VALUES
(1, 'gg', '', '2025-07-09 03:25:00', '2025-07-18 03:25:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tb_flash_sale_produk`
--

CREATE TABLE `tb_flash_sale_produk` (
  `id` int NOT NULL,
  `event_id` int NOT NULL,
  `toko_id` int NOT NULL,
  `barang_id` int NOT NULL,
  `harga_flash_sale` decimal(12,2) NOT NULL,
  `stok_flash_sale` int NOT NULL,
  `status_moderasi` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_gambar_barang`
--

CREATE TABLE `tb_gambar_barang` (
  `id` int NOT NULL,
  `barang_id` int NOT NULL,
  `nama_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_utama` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_kategori`
--

CREATE TABLE `tb_kategori` (
  `id` int NOT NULL,
  `nama_kategori` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `icon_class` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `parent_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_kategori`
--

INSERT INTO `tb_kategori` (`id`, `nama_kategori`, `deskripsi`, `icon_class`, `parent_id`) VALUES
(1, 'Bahan Bangunan Dasar', 'Material utama untuk konstruksi', 'fas fa-cube', NULL),
(2, 'Semen', 'Berbagai jenis semen untuk konstruksi', 'fas fa-cube', 1),
(3, 'Semen Portland', 'Semen jenis umum', 'fas fa-cube', 2),
(4, 'Semen Putih', 'Semen untuk finishing dan dekorasi', 'fas fa-cube', 2),
(5, 'Pasir', 'Pasir untuk campuran bangunan', 'fas fa-cube', 1),
(6, 'Batu', 'Berbagai jenis batu untuk konstruksi', 'fas fa-cube', 1),
(7, 'Besi & Baja', 'Material besi dan baja untuk struktur', 'fas fa-cube', NULL),
(8, 'Besi Beton', 'Besi untuk tulangan beton', 'fas fa-cube', 7),
(9, 'Baja Ringan', 'Baja untuk rangka atap', 'fas fa-cube', 7),
(10, 'Cat & Pelapis', 'Produk untuk finishing dan perlindungan permukaan', 'fas fa-cube', NULL),
(11, 'Cat Tembok', 'Cat untuk dinding interior dan eksterior', 'fas fa-cube', 10),
(12, 'Cat Kayu & Besi', 'Cat untuk permukaan kayu dan besi', 'fas fa-cube', 10),
(13, 'Keramik & Granit', 'Material penutup lantai dan dinding', 'fas fa-cube', NULL),
(14, 'Keramik Lantai', 'Keramik untuk lantai', 'fas fa-cube', 13),
(15, 'Keramik Dinding', 'Keramik untuk dinding', 'fas fa-cube', 13),
(16, 'Granit', 'Material granit untuk lantai dan dinding', 'fas fa-cube', 13),
(17, 'Pipa & Perlengkapan Air', 'Sistem perpipaan dan sanitasi', 'fas fa-cube', NULL),
(18, 'Pipa PVC', 'Pipa plastik PVC', 'fas fa-cube', 17),
(19, 'Pipa Besi', 'Pipa dari bahan besi', 'fas fa-cube', 17),
(20, 'Perlengkapan Sanitasi', 'Kloset, wastafel, shower', 'fas fa-cube', 17);

-- --------------------------------------------------------

--
-- Table structure for table `tb_keranjang`
--

CREATE TABLE `tb_keranjang` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `barang_id` int NOT NULL,
  `jumlah` int NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_keranjang`
--

INSERT INTO `tb_keranjang` (`id`, `user_id`, `barang_id`, `jumlah`, `created_at`) VALUES
(10, 1, 2, 1, '2025-07-11 21:18:24');

-- --------------------------------------------------------

--
-- Table structure for table `tb_komisi`
--

CREATE TABLE `tb_komisi` (
  `id` int NOT NULL,
  `detail_transaksi_id` int NOT NULL,
  `jumlah_penjualan` decimal(15,2) NOT NULL,
  `persentase_komisi` decimal(5,2) NOT NULL,
  `jumlah_komisi` decimal(15,2) NOT NULL,
  `status` enum('unpaid','paid') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_kurir_toko`
--

CREATE TABLE `tb_kurir_toko` (
  `id` int NOT NULL,
  `toko_id` int NOT NULL,
  `nama_kurir` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Contoh: Pengiriman Toko, JNE Trucking, Lalamove',
  `estimasi_waktu` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Contoh: 1-2 hari',
  `biaya` decimal(12,2) NOT NULL,
  `tipe_kurir` enum('TOKO','PIHAK_KETIGA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PIHAK_KETIGA' COMMENT 'Membedakan antara kurir internal toko dan ekspedisi pihak ketiga',
  `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_payouts`
--

CREATE TABLE `tb_payouts` (
  `id` int NOT NULL,
  `toko_id` int NOT NULL,
  `jumlah_payout` decimal(15,2) NOT NULL,
  `status` enum('pending','completed','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `tanggal_request` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tanggal_proses` datetime DEFAULT NULL,
  `catatan_admin` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_pengaturan`
--

CREATE TABLE `tb_pengaturan` (
  `setting_nama` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `setting_nilai` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_pengaturan`
--

INSERT INTO `tb_pengaturan` (`setting_nama`, `setting_nilai`) VALUES
('aktifkan_fitur_live_chat', '1'),
('alamat_pusat', 'Jl. Jenderal Sudirman Kav. 52-53, Jakarta Selatan, DKI Jakarta 12190'),
('bank_rekening_platform', 'BCA'),
('deskripsi_website', 'Platform Jual Beli Bahan Bangunan Terlengkap se-Indonesia'),
('durasi_preorder_maks_hari', '30'),
('email_kontak', 'kontak@pondasikita.com'),
('google_maps_api_key', ''),
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
-- Table structure for table `tb_review_produk`
--

CREATE TABLE `tb_review_produk` (
  `id` int NOT NULL,
  `detail_transaksi_id` int NOT NULL,
  `barang_id` int NOT NULL,
  `user_id` int NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `ulasan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `gambar_ulasan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_stok_histori`
--

CREATE TABLE `tb_stok_histori` (
  `id` bigint NOT NULL,
  `barang_id` int NOT NULL,
  `variasi_id` int DEFAULT NULL COMMENT 'Isi jika stok milik variasi',
  `jumlah` int NOT NULL COMMENT 'Positif untuk stok masuk, negatif untuk stok keluar',
  `tipe_pergerakan` enum('initial','sale','sale_return','adjustment','stock_in') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `referensi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Contoh: Kode Invoice, Nomor Surat Jalan',
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_toko`
--

CREATE TABLE `tb_toko` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `nama_toko` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi_toko` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `logo_toko` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `banner_toko` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alamat_toko` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `province_id` int UNSIGNED DEFAULT NULL,
  `city_id` int UNSIGNED DEFAULT NULL,
  `district_id` int UNSIGNED DEFAULT NULL,
  `kode_pos` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telepon_toko` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('pending','active','suspended') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `status_operasional` enum('Buka','Tutup') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Buka',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_toko`
--

INSERT INTO `tb_toko` (`id`, `user_id`, `nama_toko`, `slug`, `deskripsi_toko`, `logo_toko`, `banner_toko`, `alamat_toko`, `province_id`, `city_id`, `district_id`, `kode_pos`, `telepon_toko`, `latitude`, `longitude`, `status`, `status_operasional`, `created_at`, `updated_at`) VALUES
(1, 5, 'QISA', 'qisa', NULL, NULL, NULL, 'Pagaden\r\nbabakan', NULL, 11, NULL, NULL, '085156677227', NULL, NULL, 'active', 'Buka', '2025-07-03 04:29:29', '2025-07-11 10:12:06'),
(2, 7, 'QistyStore', 'Qistore', 'ini wajib di isi toko qisty cantik', NULL, NULL, 'pagaden', NULL, 11, NULL, NULL, '', NULL, NULL, 'active', 'Buka', '2025-07-09 17:21:52', '2025-07-11 10:12:06'),
(3, 4, 'PrabuStore', 'prabustore', 'prabu store asli wak', NULL, NULL, 'subang', NULL, 12, NULL, NULL, '', NULL, NULL, 'active', 'Buka', '2025-07-09 17:29:32', '2025-07-11 10:12:06'),
(4, 8, 'Ambatugung', 'ambatugung', NULL, 'logo_1752251394_68713c028bf23.jpg', NULL, 'Jl Bung karno no 110, blok 3', 2, 11, 158, NULL, '08123030809', NULL, NULL, 'active', 'Buka', '2025-07-11 09:29:54', '2025-07-11 09:34:55'),
(45, 31, 'Subang Bangunan Center', 'subang-bangunan', 'Toko bahan bangunan terpercaya di Subang', NULL, NULL, 'Jl. Raya Cagak No. 10', 2, 21, 158, '41212', '081200000001', NULL, NULL, 'active', 'Buka', '2025-07-11 11:41:50', '2025-07-11 11:41:50'),
(46, 33, 'Bekasi Toko Material', 'bekasi-material', 'Bahan bangunan lengkap dan murah di Bekasi', NULL, NULL, 'Jl. Raya Bekasi No. 88', 2, 8, 78, '17113', '081200000003', NULL, NULL, 'active', 'Buka', '2025-07-11 11:41:50', '2025-07-11 11:41:50'),
(47, 34, 'Bangunan Depok Indah', 'depok-bangunan', 'Toko bahan bangunan dan renovasi rumah', NULL, NULL, 'Jl. Margonda No. 21', 2, 7, 99, '16431', '081200000004', NULL, NULL, 'active', 'Buka', '2025-07-11 11:41:50', '2025-07-11 11:41:50'),
(48, 36, 'Karawang Bangunan Mandiri', 'karawang-bangunan', 'Bahan bangunan, cat, dan keramik tersedia lengkap', NULL, NULL, 'Jl. Ahmad Yani No. 80', 2, 11, 161, '41311', '081200000006', NULL, NULL, 'active', 'Buka', '2025-07-11 11:41:50', '2025-07-11 11:41:50'),
(49, 37, 'Toko Bangunan Bogor Raya', 'bogor-raya', 'Pusat bahan bangunan Bogor', NULL, NULL, 'Jl. Pajajaran No. 12', 2, 6, 162, '16143', '081200000007', NULL, NULL, 'active', 'Buka', '2025-07-11 11:41:50', '2025-07-11 11:41:50'),
(50, 32, 'Jakarta Pusat Material', 'jakarta-pusat-material', 'Distributor material bangunan di Jakarta Pusat', NULL, NULL, 'Jl. Sudirman No. 1', 1, 1, 1, '10270', '081200000011', NULL, NULL, 'active', 'Buka', '2025-07-11 11:41:50', '2025-07-11 11:41:50'),
(51, 35, 'Tangerang Bangun Jaya', 'tangerang-bangun', 'Solusi kebutuhan bangunan di Tangerang', NULL, NULL, 'Jl. Raya Serpong No. 1', 3, 12, 100, '15325', '081200000012', NULL, NULL, 'active', 'Buka', '2025-07-11 11:41:50', '2025-07-11 11:41:50'),
(52, 41, 'Toko Bangunan Jaya Pusat', 'toko-bangunan-jayapusat', 'Pusat material bangunan di Jakarta Pusat', NULL, NULL, 'Jl. Thamrin No. 10', 1, 1, 1, '10310', '081211110001', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(53, 42, 'Sumber Material Jakpus', 'sumber-material-jakpus', 'Menyediakan bahan konstruksi lengkap', NULL, NULL, 'Jl. Kebon Sirih No. 5', 1, 1, 1, '10340', '081211110002', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(54, 43, 'Mitra Baja Jakarta Pusat', 'mitra-baja-jakpus', 'Solusi besi dan baja terbaik', NULL, NULL, 'Jl. Menteng Raya No. 20', 1, 1, 1, '10350', '081211110003', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(55, 44, 'Gudang Semen Jakpus', 'gudang-semen-jakpus', 'Pemasok semen dan pasir kualitas prima', NULL, NULL, 'Jl. Wahid Hasyim No. 15', 1, 1, 1, '10340', '081211110004', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(56, 45, 'Material Utara Gemilang', 'material-utara-gemilang', 'Kebutuhan bangunan untuk Jakarta Utara', NULL, NULL, 'Jl. Yos Sudarso No. 30', 1, 2, 1, '14350', '081211110005', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(57, 46, 'Toko Kusen Agung Jakarta Utara', 'toko-kusen-agung-jakut', 'Spesialis kusen dan pintu', NULL, NULL, 'Jl. Danau Sunter Utara No. 5', 1, 2, 1, '14350', '081211110006', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(58, 47, 'Pipa Baja Sukses Jakut', 'pipa-baja-sukses-jakut', 'Distributor pipa PVC dan besi', NULL, NULL, 'Jl. Pluit Raya No. 10', 1, 2, 1, '14450', '081211110007', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(59, 48, 'Keramik Indah Jakarta Utara', 'keramik-indah-jakut', 'Pilihan keramik dan granit terlengkap', NULL, NULL, 'Jl. Boulevard Raya No. 25', 1, 2, 1, '14240', '081211110008', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(60, 49, 'Pondasi Jaya Jakarta Barat', 'pondasi-jaya-jakbar', 'Toko bahan bangunan terlengkap', NULL, NULL, 'Jl. Daan Mogot No. 50', 1, 3, 1, '11510', '081211110009', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(61, 50, 'Cat Warna Prima Jakarta Barat', 'cat-warna-prima-jakbar', 'Pusat cat tembok dan aksesoris', NULL, NULL, 'Jl. Panjang No. 10', 1, 3, 1, '11530', '081211110010', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(62, 51, 'Kayu Modern Jakbar', 'kayu-modern-jakbar', 'Supplier kayu dan triplek berkualitas', NULL, NULL, 'Jl. Meruya Ilir No. 15', 1, 3, 1, '11620', '081211110011', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(63, 52, 'Alat Bangun Barokah Jakbar', 'alat-bangun-barokah-jakbar', 'Menjual perkakas dan alat berat', NULL, NULL, 'Jl. Tomang Raya No. 20', 1, 3, 1, '11440', '081211110012', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(64, 53, 'Renovasi Rumah Jakarta Selatan', 'renovasi-rumah-jaksel', 'Solusi renovasi dan material bangunan', NULL, NULL, 'Jl. Fatmawati No. 8', 1, 4, 1, '12430', '081211110013', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(65, 54, 'Toko Baja Ringan Jaksel', 'toko-baja-ringan-jaksel', 'Spesialis baja ringan dan atap', NULL, NULL, 'Jl. TB Simatupang No. 12', 1, 4, 1, '12520', '081211110014', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(66, 55, 'Granit Elegan Jakarta Selatan', 'granit-elegan-jaksel', 'Pilihan granit dan marmer mewah', NULL, NULL, 'Jl. Pangeran Antasari No. 25', 1, 4, 1, '12150', '081211110015', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(67, 56, 'Pasir Beton Sejahtera Jaksel', 'pasir-beton-sejahtera-jaksel', 'Penyedia pasir dan kerikil proyek', NULL, NULL, 'Jl. Warung Buncit Raya No. 7', 1, 4, 1, '12740', '081211110016', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(68, 57, 'Global Material Jakarta Timur', 'global-material-jaktim', 'Toko material lengkap di Jaktim', NULL, NULL, 'Jl. Raya Bogor KM 20', 1, 5, 1, '13740', '081211110017', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(69, 58, 'Pusat Gipsum Jakarta Timur', 'pusat-gipsum-jaktim', 'Supplier plafon dan partisi gypsum', NULL, NULL, 'Jl. Kalimalang No. 10', 1, 5, 1, '13450', '081211110018', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(70, 59, 'Batu Alam Timur', 'batu-alam-timur', 'Koleksi batu alam dan ornamen taman', NULL, NULL, 'Jl. Pondok Gede Raya No. 15', 1, 5, 1, '13810', '081211110019', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(71, 60, 'Kawat Berduri Jaktim', 'kawat-berduri-jaktim', 'Jual kawat dan material pagar', NULL, NULL, 'Jl. Raya Ciracas No. 5', 1, 5, 1, '13740', '081211110020', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(72, 61, 'Toko Bangunan Raya Bogor', 'toko-bangunan-raya-bogor', 'Pusat bahan bangunan di Kota Bogor', NULL, NULL, 'Jl. Pajajaran No. 12', 2, 6, 162, '16143', '081211110021', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(73, 62, 'Bogor Mandiri Konstruksi', 'bogor-mandiri-konstruksi', 'Menyediakan besi dan beton instan', NULL, NULL, 'Jl. Ahmad Yani No. 5', 2, 6, 162, '16124', '081211110022', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(74, 63, 'Cahaya Bangun Bogor', 'cahaya-bangun-bogor', 'Toko perlengkapan rumah dan renovasi', NULL, NULL, 'Jl. Dadali No. 18', 2, 6, 162, '16161', '081211110023', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(75, 64, 'Pusat Keramik Bogor', 'pusat-keramik-bogor', 'Aneka keramik lantai dan dinding', NULL, NULL, 'Jl. Sholeh Iskandar No. 3', 2, 6, 162, '16167', '081211110024', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(76, 65, 'Bangun Sejahtera Cibinong', 'bangun-sejahtera-cibinong', 'Material bangunan terbaik Kab. Bogor', NULL, NULL, 'Jl. Raya Jakarta-Bogor Km.45', 2, 9, 1, '16912', '081211110025', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(77, 66, 'Toko Besi Jati Bogor', 'toko-besi-jati-bogor', 'Penjual besi beton dan hollow', NULL, NULL, 'Jl. Raya Bojong Gede No. 20', 2, 9, 1, '16922', '081211110026', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(78, 67, 'Pasir Koral Kab Bogor', 'pasir-koral-kab-bogor', 'Supplier pasir, split, dan batu kali', NULL, NULL, 'Jl. Raya Puncak Km. 80', 2, 9, 1, '16750', '081211110027', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(79, 68, 'Cat Exterior Bogor', 'cat-exterior-bogor', 'Jual cat anti bocor dan waterproofing', NULL, NULL, 'Jl. Raya Transyogi No. 10', 2, 9, 1, '16968', '081211110028', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(80, 69, 'Depok Bangunan Indah', 'depok-bangunan-indah', 'Toko bahan bangunan dan renovasi rumah', NULL, NULL, 'Jl. Margonda No. 21', 2, 7, 99, '16431', '081211110029', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(81, 70, 'Depok Baja Perkasa', 'depok-baja-perkasa', 'Penyedia besi dan baja berkualitas', NULL, NULL, 'Jl. Raya Sawangan No. 50', 2, 7, 99, '16511', '081211110030', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(82, 71, 'Material Depok Abadi', 'material-depok-abadi', 'Lengkap untuk konstruksi dan renovasi', NULL, NULL, 'Jl. Tole Iskandar No. 30', 2, 7, 99, '16411', '081211110031', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(83, 72, 'Depok Genteng Makmur', 'depok-genteng-makmur', 'Pilihan genteng dan atap terbaik', NULL, NULL, 'Jl. Raya Cinere No. 10', 2, 7, 99, '16514', '081211110032', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(84, 73, 'Tangerang Material Jaya', 'tangerang-material-jaya', 'Toko bahan bangunan di pusat Tangerang', NULL, NULL, 'Jl. Jend. Sudirman No. 5', 3, 12, 1, '15111', '081211110033', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(85, 74, 'Semen Beton Tangerang', 'semen-beton-tangerang', 'Supplier semen dan ready mix', NULL, NULL, 'Jl. MH. Thamrin No. 15', 3, 12, 1, '15143', '081211110034', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(86, 75, 'Besi Hollow Tangerang Kota', 'besi-hollow-tangerang-kota', 'Jual besi hollow dan kanal C', NULL, NULL, 'Jl. Imam Bonjol No. 20', 3, 12, 1, '15119', '081211110035', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(87, 76, 'Alat Perkakas Tangerang', 'alat-perkakas-tangerang', 'Menyediakan perkakas dan alat teknik', NULL, NULL, 'Jl. Gajah Mada No. 8', 3, 12, 1, '15118', '081211110036', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(88, 77, 'Tangsel Bangun Sejahtera', 'tangsel-bangun-sejahtera', 'Kebutuhan material di Tangerang Selatan', NULL, NULL, 'Jl. Raya Serpong No. 50', 3, 13, 1, '15320', '081211110037', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(89, 78, 'Modern Material BSD', 'modern-material-bsd', 'Toko bangunan modern dan lengkap', NULL, NULL, 'Jl. BSD Grand Boulevard No. 1', 3, 13, 1, '15339', '081211110038', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(90, 79, 'Ciputat Jaya Konstruksi', 'ciputat-jaya-konstruksi', 'Menyediakan bahan bangunan di Ciputat', NULL, NULL, 'Jl. Ir. H. Juanda No. 10', 3, 13, 1, '15412', '081211110039', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(91, 80, 'Material Bintaro Utama', 'material-bintaro-utama', 'Solusi proyek dan renovasi Bintaro', NULL, NULL, 'Jl. Bintaro Raya No. 25', 3, 13, 1, '15229', '081211110040', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(92, 81, 'Kabupaten Tangerang Material', 'kabupaten-tangerang-material', 'Pusat bahan bangunan Kab. Tangerang', NULL, NULL, 'Jl. Raya Serang Km. 20', 3, 14, 1, '15610', '081211110041', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(93, 82, 'Cikupa Bangun Bersama', 'cikupa-bangun-bersama', 'Menyediakan material untuk industri', NULL, NULL, 'Jl. Raya Cikupa No. 5', 3, 14, 1, '15710', '081211110042', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(94, 83, 'Maja Jaya Konstruksi', 'maja-jaya-konstruksi', 'Distributor material untuk perumahan baru', NULL, NULL, 'Jl. Raya Maja No. 1', 3, 14, 1, '15760', '081211110043', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(95, 84, 'Tigaraksa Baja Perkasa', 'tigaraksa-baja-perkasa', 'Penyedia besi dan atap di Tigaraksa', NULL, NULL, 'Jl. Aria Jaya Santika No. 10', 3, 14, 1, '15720', '081211110044', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(96, 85, 'Bekasi Toko Material Raya', 'bekasi-toko-material-raya', 'Bahan bangunan lengkap dan murah di Bekasi', NULL, NULL, 'Jl. Raya Bekasi No. 88', 2, 8, 78, '17113', '081211110045', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(97, 86, 'Bekasi Baja Ringan Kuat', 'bekasi-baja-ringan-kuat', 'Solusi atap dan rangka baja ringan', NULL, NULL, 'Jl. Cut Meutia No. 15', 2, 8, 78, '17116', '081211110046', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(98, 87, 'Toko Pipa Bekasi', 'toko-pipa-bekasi', 'Menyediakan berbagai jenis pipa', NULL, NULL, 'Jl. Ahmad Yani No. 5', 2, 8, 78, '17141', '081211110047', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(99, 88, 'Bekasi Keramik Megah', 'bekasi-keramik-megah', 'Pilihan keramik dan granit terbaru', NULL, NULL, 'Jl. Boulevard Raya No. 10', 2, 8, 78, '17148', '081211110048', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(100, 89, 'Kabupaten Bekasi Material', 'kabupaten-bekasi-material', 'Pusat bahan bangunan Kab. Bekasi', NULL, NULL, 'Jl. Raya Cikarang-Cibarusah Km 10', 2, 10, 1, '17530', '081211110049', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(101, 90, 'Cikarang Bangun Sejahtera', 'cikarang-bangun-sejahtera', 'Distributor material industri Cikarang', NULL, NULL, 'Jl. Jababeka Raya No. 1', 2, 10, 1, '17530', '081211110050', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(102, 91, 'Tambun Jaya Konstruksi', 'tambun-jaya-konstruksi', 'Material dan alat berat di Tambun', NULL, NULL, 'Jl. Sultan Hasanudin No. 20', 2, 10, 1, '17510', '081211110051', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(103, 92, 'Setu Material Lengkap', 'setu-material-lengkap', 'Menyediakan material bangunan di Setu', NULL, NULL, 'Jl. Raya Setu No. 5', 2, 10, 1, '17320', '081211110052', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(104, 93, 'Karawang Bangunan Mandiri', 'karawang-bangunan-mandiri', 'Bahan bangunan, cat, dan keramik tersedia lengkap', NULL, NULL, 'Jl. Ahmad Yani No. 80', 2, 11, 161, '41311', '081211110053', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(105, 94, 'Material Terbaik Karawang', 'material-terbaik-karawang', 'Pusat material konstruksi Karawang', NULL, NULL, 'Jl. Raya Klari No. 15', 2, 11, 161, '41371', '081211110054', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(106, 95, 'Karawang Baja Sukses', 'karawang-baja-sukses', 'Supplier besi beton dan baja ringan', NULL, NULL, 'Jl. Interchange Karawang Barat No. 5', 2, 11, 161, '41361', '081211110055', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(107, 96, 'Toko Cat Karawang Prima', 'toko-cat-karawang-prima', 'Pilihan cat interior dan eksterior', NULL, NULL, 'Jl. Tuparev No. 25', 2, 11, 161, '41312', '081211110056', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(108, 97, 'Subang Bangunan Center', 'subang-bangunan-center', 'Toko bahan bangunan terpercaya di Subang', NULL, NULL, 'Jl. Raya Cagak No. 10', 2, 21, 158, '41212', '081211110057', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(109, 98, 'Subang Material Utama', 'subang-material-utama', 'Kebutuhan konstruksi di Subang', NULL, NULL, 'Jl. Otto Iskandardinata No. 5', 2, 21, 158, '41211', '081211110058', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(110, 99, 'Baja Ringan Subang Jaya', 'baja-ringan-subang-jaya', 'Spesialis pemasangan baja ringan', NULL, NULL, 'Jl. Raya Purwadadi No. 1', 2, 21, 158, '41261', '081211110059', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39'),
(111, 100, 'Toko Pipa Subang', 'toko-pipa-subang', 'Menyediakan pipa PVC dan aksesorisnya', NULL, NULL, 'Jl. Raya Kalijati No. 20', 2, 21, 158, '41271', '081211110060', NULL, NULL, 'active', 'Buka', '2025-07-11 11:54:39', '2025-07-11 11:54:39');

-- --------------------------------------------------------

--
-- Table structure for table `tb_toko_dekorasi`
--

CREATE TABLE `tb_toko_dekorasi` (
  `id` int NOT NULL,
  `toko_id` int NOT NULL,
  `tipe_komponen` enum('BANNER','PRODUK_UNGGULAN','TEKS_GAMBAR') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `konten_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `urutan` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_toko_jam_operasional`
--

CREATE TABLE `tb_toko_jam_operasional` (
  `id` int NOT NULL,
  `toko_id` int NOT NULL,
  `hari` tinyint(1) NOT NULL COMMENT '1=Senin, 2=Selasa, ..., 7=Minggu',
  `is_buka` tinyint(1) NOT NULL DEFAULT '0',
  `jam_buka` time DEFAULT NULL,
  `jam_tutup` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_toko_pengaturan`
--

CREATE TABLE `tb_toko_pengaturan` (
  `toko_id` int NOT NULL,
  `notif_email_pesanan` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Notif email untuk pesanan baru & update status',
  `notif_email_chat` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Notif email untuk chat baru',
  `notif_email_produk` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Notif email saat produk disetujui/ditolak',
  `notif_email_promo` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Notif email info promo dari platform',
  `chat_terima_otomatis` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Aktifkan/nonaktifkan kemampuan menerima chat'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_toko_review`
--

CREATE TABLE `tb_toko_review` (
  `id` int NOT NULL,
  `toko_id` int NOT NULL,
  `user_id` int NOT NULL,
  `transaksi_id` int NOT NULL COMMENT 'Untuk memvalidasi ulasan dari pembeli asli',
  `rating` tinyint(1) NOT NULL,
  `ulasan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `balasan_penjual` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Kolom untuk menyimpan balasan dari penjual',
  `is_anonymous` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_transaksi`
--

CREATE TABLE `tb_transaksi` (
  `id` int NOT NULL,
  `kode_invoice` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `sumber_transaksi` enum('ONLINE','OFFLINE') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ONLINE' COMMENT 'Membedakan pesanan dari web atau dari POS kasir',
  `user_id` int NOT NULL,
  `total_harga_produk` decimal(15,2) NOT NULL,
  `total_diskon` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_final` decimal(15,2) NOT NULL,
  `metode_pembayaran` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_pembayaran` enum('pending','paid','failed','expired','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `status_pesanan_global` enum('menunggu_pembayaran','diproses','selesai','dibatalkan') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'menunggu_pembayaran',
  `payment_deadline` datetime DEFAULT NULL,
  `shipping_label_alamat` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shipping_nama_penerima` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shipping_telepon_penerima` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shipping_alamat_lengkap` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `shipping_kecamatan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shipping_kota_kabupaten` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shipping_provinsi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shipping_kode_pos` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `voucher_digunakan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `biaya_pengiriman` decimal(12,2) DEFAULT '0.00',
  `tipe_pengambilan` enum('pengiriman','ambil_di_toko') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pengiriman',
  `snap_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tanggal_transaksi` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_transaksi`
--

INSERT INTO `tb_transaksi` (`id`, `kode_invoice`, `sumber_transaksi`, `user_id`, `total_harga_produk`, `total_diskon`, `total_final`, `metode_pembayaran`, `status_pembayaran`, `status_pesanan_global`, `payment_deadline`, `shipping_label_alamat`, `shipping_nama_penerima`, `shipping_telepon_penerima`, `shipping_alamat_lengkap`, `shipping_kecamatan`, `shipping_kota_kabupaten`, `shipping_provinsi`, `shipping_kode_pos`, `catatan`, `voucher_digunakan`, `biaya_pengiriman`, `tipe_pengambilan`, `snap_token`, `tanggal_transaksi`) VALUES
(10, 'TRX-20250711201954-8505', 'ONLINE', 1, 1000000.00, 0.00, 1015000.00, 'Midtrans', 'cancelled', 'dibatalkan', '2025-07-11 21:19:54', 'Rumah', 'Prabu Alam Tian Try Suherman', '085156677227', 'di rumah mamah saya ya', 'Klari', 'KABUPATEN KARAWANG', 'JAWA BARAT', '', '', '', 15000.00, 'pengiriman', NULL, '2025-07-12 03:19:54'),
(11, 'TRX-20250712024619-8231', 'ONLINE', 1, 800000.00, 0.00, 815000.00, 'Midtrans', 'cancelled', 'dibatalkan', '2025-07-12 03:46:19', 'Rumah', 'Prabu Alam Tian Try Suherman', '085156677227', '111', 'Klari', 'KABUPATEN KARAWANG', 'JAWA BARAT', '41132', '', '', 15000.00, 'pengiriman', NULL, '2025-07-12 09:46:19'),
(12, 'TRX-20250712032623-7878', 'ONLINE', 1, 1000000.00, 0.00, 1015000.00, 'Midtrans', 'cancelled', 'dibatalkan', '2025-07-12 04:26:23', 'Rumah', 'Prabu Alam Tian Try Suherman', '085156677227', '111', 'Klari', 'KABUPATEN KARAWANG', 'JAWA BARAT', '41132', '', '', 15000.00, 'pengiriman', NULL, '2025-07-12 10:26:23'),
(13, 'TRX-20250712041753-3989', 'ONLINE', 1, 1000000.00, 0.00, 1015000.00, 'Midtrans', 'expired', 'dibatalkan', '2025-07-12 05:17:53', 'Rumah', 'Prabu Alam Tian Try Suherman', '085156677227', '111', 'Pancoran', 'KOTA JAKARTA SELATAN', 'DKI JAKARTA', '41132', '', '', 15000.00, 'pengiriman', NULL, '2025-07-12 11:17:53');

-- --------------------------------------------------------

--
-- Table structure for table `tb_user`
--

CREATE TABLE `tb_user` (
  `id` int NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `google_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `no_telepon` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `profile_picture_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('online','offline','typing') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'offline',
  `last_activity_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `level` enum('admin','seller','customer','bot') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `is_banned` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `reset_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `status_online` enum('online','offline') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'offline'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_user`
--

INSERT INTO `tb_user` (`id`, `username`, `password`, `google_id`, `nama`, `email`, `no_telepon`, `jenis_kelamin`, `tanggal_lahir`, `alamat`, `profile_picture_url`, `status`, `last_activity_at`, `level`, `is_verified`, `is_banned`, `created_at`, `updated_at`, `reset_token`, `reset_token_expires_at`, `status_online`) VALUES
(1, 'prabukeren', '$2y$10$PCWVkhM3W7BjTyBnv5eui.V/S/fN9zWy7Rar1D6DaIdyJfpnKzFDa', NULL, 'Prabu Alam Tian Try Suherman', 'prabualamtian@gmail.com', '085156677227', 'Laki-laki', '2005-04-20', 'Pagaden', 'user_1_1752269905.jpg', 'offline', '2025-07-11 20:44:00', 'customer', 0, 0, '2025-07-02 21:35:43', '2025-07-11 14:38:25', NULL, NULL, 'offline'),
(4, 'prabukeren666', '$2y$10$fQCD3NU4N4M2AM.eYAeIO.Vzcjdp8bfMsOF54bHt0WP.7O8gUTw66', NULL, 'prabu alam tian try suherman', 'prabualamxii@gmail.com', '085156677227', NULL, NULL, 'subang', NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-03 01:35:03', '2025-07-11 16:02:53', NULL, NULL, 'offline'),
(5, 'Prabu', '$2y$10$6IVUXxAgljWdzQ1ocqL5huELDlXiINSuAA9wFfMVlx1RUDGkOla/e', NULL, 'Prabu Alam Tian Try Suherman', 'prabuxmomo@gmail.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-03 04:29:29', '2025-07-09 13:00:28', NULL, NULL, 'offline'),
(6, 'AdminPondasikita', '$2y$10$4KefH6FArhoiIYjmCmF5ZefdLTYq5eHNNLtnkZIB9uQX7hljXC7i6', NULL, 'pondasikitamaster', 'prabualamxi@gmail.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'admin', 1, 0, '2025-07-09 07:30:56', NULL, NULL, NULL, 'offline'),
(7, 'QistyCantik', '$2y$10$.m9R7n3AtgGf1F8bMtFFQuMGw/529Jt1O13D0fP2VHY5.ykKBv0NC', NULL, 'Qisty Sauva', '2qistysauva@gmail.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 1, 0, '2025-07-09 17:17:38', NULL, NULL, NULL, 'offline'),
(8, 'Kiboy', '$2y$10$HzuyVN5ORrq7IXI57OcC3OZfu6zFfzxeesThKHjCTD359IUO.I2Q.', NULL, 'Abdul Halim', 'zyoz472@gmail.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 09:29:54', NULL, NULL, NULL, 'offline'),
(31, 'user_subang_seller', 'some_hashed_password', NULL, 'Subang Seller', 'subang.seller@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:28:35', '2025-07-11 11:28:35', NULL, NULL, 'offline'),
(32, 'user_bandung_seller', 'some_hashed_password', NULL, 'Bandung Seller', 'bandung.seller@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:28:35', '2025-07-11 11:28:35', NULL, NULL, 'offline'),
(33, 'user_bekasi_seller', 'some_hashed_password', NULL, 'Bekasi Seller', 'bekasi.seller@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:28:35', '2025-07-11 11:28:35', NULL, NULL, 'offline'),
(34, 'user_depok_seller', 'some_hashed_password', NULL, 'Depok Seller', 'depok.seller@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:28:35', '2025-07-11 11:28:35', NULL, NULL, 'offline'),
(35, 'user_cirebon_seller', 'some_hashed_password', NULL, 'Cirebon Seller', 'cirebon.seller@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:28:35', '2025-07-11 11:28:35', NULL, NULL, 'offline'),
(36, 'user_karawang_seller', 'some_hashed_password', NULL, 'Karawang Seller', 'karawang.seller@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:28:35', '2025-07-11 11:28:35', NULL, NULL, 'offline'),
(37, 'user_bogor_seller', 'some_hashed_password', NULL, 'Bogor Seller', 'bogor.seller@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:28:35', '2025-07-11 11:28:35', NULL, NULL, 'offline'),
(38, 'user_purwakarta_seller', 'some_hashed_password', NULL, 'Purwakarta Seller', 'purwakarta.seller@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:28:35', '2025-07-11 11:28:35', NULL, NULL, 'offline'),
(39, 'user_sukabumi_seller', 'some_hashed_password', NULL, 'Sukabumi Seller', 'sukabumi.seller@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:28:35', '2025-07-11 11:28:35', NULL, NULL, 'offline'),
(40, 'user_indramayu_seller', 'some_hashed_password', NULL, 'Indramayu Seller', 'indramayu.seller@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:28:35', '2025-07-11 11:28:35', NULL, NULL, 'offline'),
(41, 'seller_jakpus_toko1', '$2y$10$somehashedpassword', NULL, 'Seller Jakpus 1', 'jakpus1@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(42, 'seller_jakpus_toko2', '$2y$10$somehashedpassword', NULL, 'Seller Jakpus 2', 'jakpus2@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(43, 'seller_jakpus_toko3', '$2y$10$somehashedpassword', NULL, 'Seller Jakpus 3', 'jakpus3@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(44, 'seller_jakpus_toko4', '$2y$10$somehashedpassword', NULL, 'Seller Jakpus 4', 'jakpus4@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(45, 'seller_jakut_toko1', '$2y$10$somehashedpassword', NULL, 'Seller Jakut 1', 'jakut1@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(46, 'seller_jakut_toko2', '$2y$10$somehashedpassword', NULL, 'Seller Jakut 2', 'jakut2@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(47, 'seller_jakut_toko3', '$2y$10$somehashedpassword', NULL, 'Seller Jakut 3', 'jakut3@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(48, 'seller_jakut_toko4', '$2y$10$somehashedpassword', NULL, 'Seller Jakut 4', 'jakut4@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(49, 'seller_jakbar_toko1', '$2y$10$somehashedpassword', NULL, 'Seller Jakbar 1', 'jakbar1@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(50, 'seller_jakbar_toko2', '$2y$10$somehashedpassword', NULL, 'Seller Jakbar 2', 'jakbar2@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(51, 'seller_jakbar_toko3', '$2y$10$somehashedpassword', NULL, 'Seller Jakbar 3', 'jakbar3@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(52, 'seller_jakbar_toko4', '$2y$10$somehashedpassword', NULL, 'Seller Jakbar 4', 'jakbar4@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(53, 'seller_jaksel_toko1', '$2y$10$somehashedpassword', NULL, 'Seller Jaksel 1', 'jaksel1@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(54, 'seller_jaksel_toko2', '$2y$10$somehashedpassword', NULL, 'Seller Jaksel 2', 'jaksel2@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(55, 'seller_jaksel_toko3', '$2y$10$somehashedpassword', NULL, 'Seller Jaksel 3', 'jaksel3@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(56, 'seller_jaksel_toko4', '$2y$10$somehashedpassword', NULL, 'Seller Jaksel 4', 'jaksel4@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(57, 'seller_jaktim_toko1', '$2y$10$somehashedpassword', NULL, 'Seller Jaktim 1', 'jaktim1@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(58, 'seller_jaktim_toko2', '$2y$10$somehashedpassword', NULL, 'Seller Jaktim 2', 'jaktim2@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(59, 'seller_jaktim_toko3', '$2y$10$somehashedpassword', NULL, 'Seller Jaktim 3', 'jaktim3@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(60, 'seller_jaktim_toko4', '$2y$10$somehashedpassword', NULL, 'Seller Jaktim 4', 'jaktim4@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(61, 'seller_kotabogor_toko1', '$2y$10$somehashedpassword', NULL, 'Seller Kota Bogor 1', 'kotabogor1@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(62, 'seller_kotabogor_toko2', '$2y$10$somehashedpassword', NULL, 'Seller Kota Bogor 2', 'kotabogor2@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(63, 'seller_kotabogor_toko3', '$2y$10$somehashedpassword', NULL, 'Seller Kota Bogor 3', 'kotabogor3@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(64, 'seller_kotabogor_toko4', '$2y$10$somehashedpassword', NULL, 'Seller Kota Bogor 4', 'kotabogor4@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(65, 'seller_kabubogor_toko1', '$2y$10$somehashedpassword', NULL, 'Seller Kab Bogor 1', 'kabubogor1@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(66, 'seller_kabubogor_toko2', '$2y$10$somehashedpassword', NULL, 'Seller Kab Bogor 2', 'kabubogor2@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(67, 'seller_kabubogor_toko3', '$2y$10$somehashedpassword', NULL, 'Seller Kab Bogor 3', 'kabubogor3@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(68, 'seller_kabubogor_toko4', '$2y$10$somehashedpassword', NULL, 'Seller Kab Bogor 4', 'kabubogor4@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(69, 'seller_depok_toko1', '$2y$10$somehashedpassword', NULL, 'Seller Depok 1', 'depok1@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(70, 'seller_depok_toko2', '$2y$10$somehashedpassword', NULL, 'Seller Depok 2', 'depok2@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(71, 'seller_depok_toko3', '$2y$10$somehashedpassword', NULL, 'Seller Depok 3', 'depok3@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(72, 'seller_depok_toko4', '$2y$10$somehashedpassword', NULL, 'Seller Depok 4', 'depok4@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(73, 'seller_kotatang_toko1', '$2y$10$somehashedpassword', NULL, 'Seller Kota Tang. 1', 'kotatang1@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(74, 'seller_kotatang_toko2', '$2y$10$somehashedpassword', NULL, 'Seller Kota Tang. 2', 'kotatang2@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(75, 'seller_kotatang_toko3', '$2y$10$somehashedpassword', NULL, 'Seller Kota Tang. 3', 'kotatang3@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(76, 'seller_kotatang_toko4', '$2y$10$somehashedpassword', NULL, 'Seller Kota Tang. 4', 'kotatang4@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(77, 'seller_tangsel_toko1', '$2y$10$somehashedpassword', NULL, 'Seller Tangsel 1', 'tangsel1@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(78, 'seller_tangsel_toko2', '$2y$10$somehashedpassword', NULL, 'Seller Tangsel 2', 'tangsel2@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(79, 'seller_tangsel_toko3', '$2y$10$somehashedpassword', NULL, 'Seller Tangsel 3', 'tangsel3@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(80, 'seller_tangsel_toko4', '$2y$10$somehashedpassword', NULL, 'Seller Tangsel 4', 'tangsel4@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(81, 'seller_kabutang_toko1', '$2y$10$somehashedpassword', NULL, 'Seller Kab Tang. 1', 'kabutang1@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(82, 'seller_kabutang_toko2', '$2y$10$somehashedpassword', NULL, 'Seller Kab Tang. 2', 'kabutang2@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(83, 'seller_kabutang_toko3', '$2y$10$somehashedpassword', NULL, 'Seller Kab Tang. 3', 'kabutang3@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(84, 'seller_kabutang_toko4', '$2y$10$somehashedpassword', NULL, 'Seller Kab Tang. 4', 'kabutang4@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(85, 'seller_kotabks_toko1', '$2y$10$somehashedpassword', NULL, 'Seller Kota Bks 1', 'kotabks1@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(86, 'seller_kotabks_toko2', '$2y$10$somehashedpassword', NULL, 'Seller Kota Bks 2', 'kotabks2@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(87, 'seller_kotabks_toko3', '$2y$10$somehashedpassword', NULL, 'Seller Kota Bks 3', 'kotabks3@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(88, 'seller_kotabks_toko4', '$2y$10$somehashedpassword', NULL, 'Seller Kota Bks 4', 'kotabks4@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(89, 'seller_kabubks_toko1', '$2y$10$somehashedpassword', NULL, 'Seller Kab Bks 1', 'kabubks1@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(90, 'seller_kabubks_toko2', '$2y$10$somehashedpassword', NULL, 'Seller Kab Bks 2', 'kabubks2@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(91, 'seller_kabubks_toko3', '$2y$10$somehashedpassword', NULL, 'Seller Kab Bks 3', 'kabubks3@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(92, 'seller_kabubks_toko4', '$2y$10$somehashedpassword', NULL, 'Seller Kab Bks 4', 'kabubks4@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(93, 'seller_karawang_toko1', '$2y$10$somehashedpassword', NULL, 'Seller Karawang 1', 'karawang1@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(94, 'seller_karawang_toko2', '$2y$10$somehashedpassword', NULL, 'Seller Karawang 2', 'karawang2@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(95, 'seller_karawang_toko3', '$2y$10$somehashedpassword', NULL, 'Seller Karawang 3', 'karawang3@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(96, 'seller_karawang_toko4', '$2y$10$somehashedpassword', NULL, 'Seller Karawang 4', 'karawang4@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(97, 'seller_subang_toko1', '$2y$10$somehashedpassword', NULL, 'Seller Subang 1', 'subang1@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(98, 'seller_subang_toko2', '$2y$10$somehashedpassword', NULL, 'Seller Subang 2', 'subang2@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(99, 'seller_subang_toko3', '$2y$10$somehashedpassword', NULL, 'Seller Subang 3', 'subang3@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(100, 'seller_subang_toko4', '$2y$10$somehashedpassword', NULL, 'Seller Subang 4', 'subang4@example.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'seller', 0, 0, '2025-07-11 11:54:23', '2025-07-11 11:54:23', NULL, NULL, 'offline'),
(101, 'XX', '$2y$10$W5t3G1Jcqv6vZucbDlJ3w.9ndreTYQd3TuGTJ1KSzfWsc/f1ZL9LS', NULL, 'Mr kiki', 'kiki@gamail.com', NULL, NULL, NULL, NULL, NULL, 'offline', '2025-07-11 20:44:00', 'customer', 0, 0, '2025-07-11 17:38:26', NULL, NULL, NULL, 'offline');

-- --------------------------------------------------------

--
-- Table structure for table `tb_user_alamat`
--

CREATE TABLE `tb_user_alamat` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `label_alamat` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nama_penerima` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `telepon_penerima` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `alamat_lengkap` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `province_id` int UNSIGNED DEFAULT NULL,
  `city_id` int UNSIGNED DEFAULT NULL,
  `district_id` int UNSIGNED DEFAULT NULL,
  `kode_pos` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_utama` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_user_alamat`
--

INSERT INTO `tb_user_alamat` (`id`, `user_id`, `label_alamat`, `nama_penerima`, `telepon_penerima`, `alamat_lengkap`, `province_id`, `city_id`, `district_id`, `kode_pos`, `is_utama`) VALUES
(2, 1, 'Rumah', 'Prabu Alam Tian Try Suherman', '085156677227', '111', 1, 4, 30, '41132', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tb_zona_pengiriman`
--

CREATE TABLE `tb_zona_pengiriman` (
  `id` int NOT NULL,
  `nama_zona` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_zona_pengiriman`
--

INSERT INTO `tb_zona_pengiriman` (`id`, `nama_zona`, `deskripsi`, `created_at`) VALUES
(1, 'Jabodetabek', 'Mencakup Jakarta, Bogor, Depok, Tangerang, Bekasi', '2025-07-09 13:59:57'),
(2, 'Bandung Raya', 'Mencakup Kota Bandung, Kabupaten Bandung, Cimahi', '2025-07-09 13:59:57'),
(3, 'Surabaya Metropolitan', 'Mencakup Surabaya, Sidoarjo, Gresik', '2025-07-09 13:59:57'),
(4, 'Jogja Raya', 'Mencakup Yogyakarta, Sleman, Bantul, Kulon Progo, Gunungkidul', '2025-07-11 10:00:20'),
(5, 'Solo Raya', 'Mencakup Kota Solo, Karanganyar, Sukoharjo, Boyolali, Klaten, Sragen', '2025-07-11 10:00:20'),
(6, 'Semarang Raya', 'Mencakup Kota Semarang, Kabupaten Semarang, Kendal, Demak, Ungaran', '2025-07-11 10:00:20'),
(7, 'Medan Metropolitan', 'Mencakup Medan, Binjai, Deli Serdang', '2025-07-11 10:00:20'),
(8, 'Makassar Raya', 'Mencakup Makassar, Gowa, Maros, Takalar', '2025-07-11 10:00:20'),
(9, 'Bali', 'Mencakup seluruh wilayah di Provinsi Bali', '2025-07-11 10:00:20'),
(10, 'Balikpapan-Samarinda', 'Mencakup Balikpapan, Samarinda, dan sekitarnya', '2025-07-11 10:00:20'),
(11, 'Batam dan Kepulauan Riau', 'Mencakup Batam, Tanjungpinang, dan kabupaten di Kepri', '2025-07-11 10:00:20'),
(12, 'Papua', 'Mencakup Jayapura, Timika, dan wilayah lainnya di Papua', '2025-07-11 10:00:20'),
(13, 'Maluku', 'Mencakup Ambon, Tual, dan daerah lain di Maluku', '2025-07-11 10:00:20'),
(14, 'Banjarmasin Raya', 'Mencakup Banjarmasin, Banjarbaru, dan Kabupaten Banjar', '2025-07-11 10:00:20'),
(15, 'Pontianak Raya', 'Mencakup Pontianak, Singkawang, dan sekitarnya', '2025-07-11 10:00:20'),
(16, 'Padang Raya', 'Mencakup Kota Padang, Bukittinggi, Payakumbuh, dan sekitarnya', '2025-07-11 10:00:20');

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int NOT NULL,
  `toko_id` int DEFAULT NULL COMMENT 'Jika NULL, voucher dari platform. Jika ada ID, voucher milik toko.',
  `kode_voucher` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipe_diskon` enum('RUPIAH','PERSEN') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nilai_diskon` decimal(10,2) NOT NULL,
  `maks_diskon` decimal(10,2) DEFAULT NULL,
  `min_pembelian` decimal(10,2) DEFAULT '0.00',
  `kuota` int NOT NULL,
  `kuota_terpakai` int DEFAULT '0',
  `tanggal_mulai` datetime NOT NULL,
  `tanggal_berakhir` datetime NOT NULL,
  `status` enum('AKTIF','TIDAK_AKTIF','HABIS') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'AKTIF'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chats`
--
ALTER TABLE `chats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `toko_id` (`toko_id`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `province_id` (`province_id`);

--
-- Indexes for table `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `city_id` (`city_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chat_id` (`chat_id`);

--
-- Indexes for table `provinces`
--
ALTER TABLE `provinces`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tb_barang`
--
ALTER TABLE `tb_barang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_barang_toko` (`toko_id`),
  ADD KEY `fk_barang_kategori` (`kategori_id`);

--
-- Indexes for table `tb_barang_variasi`
--
ALTER TABLE `tb_barang_variasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `barang_id` (`barang_id`);

--
-- Indexes for table `tb_biaya_pengiriman`
--
ALTER TABLE `tb_biaya_pengiriman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `zona_id` (`zona_id`);

--
-- Indexes for table `tb_detail_transaksi`
--
ALTER TABLE `tb_detail_transaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaksi_id` (`transaksi_id`),
  ADD KEY `toko_id` (`toko_id`);

--
-- Indexes for table `tb_flash_sale_events`
--
ALTER TABLE `tb_flash_sale_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tb_flash_sale_produk`
--
ALTER TABLE `tb_flash_sale_produk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_fs_event` (`event_id`),
  ADD KEY `fk_fs_toko` (`toko_id`),
  ADD KEY `fk_fs_barang` (`barang_id`);

--
-- Indexes for table `tb_gambar_barang`
--
ALTER TABLE `tb_gambar_barang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `barang_id` (`barang_id`);

--
-- Indexes for table `tb_kategori`
--
ALTER TABLE `tb_kategori`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kategori_parent` (`parent_id`);

--
-- Indexes for table `tb_keranjang`
--
ALTER TABLE `tb_keranjang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `barang_id` (`barang_id`);

--
-- Indexes for table `tb_komisi`
--
ALTER TABLE `tb_komisi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `detail_transaksi_id` (`detail_transaksi_id`);

--
-- Indexes for table `tb_kurir_toko`
--
ALTER TABLE `tb_kurir_toko`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kurir_toko` (`toko_id`);

--
-- Indexes for table `tb_payouts`
--
ALTER TABLE `tb_payouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `toko_id` (`toko_id`);

--
-- Indexes for table `tb_pengaturan`
--
ALTER TABLE `tb_pengaturan`
  ADD PRIMARY KEY (`setting_nama`);

--
-- Indexes for table `tb_review_produk`
--
ALTER TABLE `tb_review_produk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `detail_transaksi_id` (`detail_transaksi_id`);

--
-- Indexes for table `tb_stok_histori`
--
ALTER TABLE `tb_stok_histori`
  ADD PRIMARY KEY (`id`),
  ADD KEY `barang_id` (`barang_id`);

--
-- Indexes for table `tb_toko`
--
ALTER TABLE `tb_toko`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `fk_toko_user` (`user_id`),
  ADD KEY `fk_toko_province` (`province_id`),
  ADD KEY `fk_toko_city` (`city_id`),
  ADD KEY `fk_toko_district` (`district_id`);

--
-- Indexes for table `tb_toko_dekorasi`
--
ALTER TABLE `tb_toko_dekorasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_dekorasi_toko` (`toko_id`);

--
-- Indexes for table `tb_toko_jam_operasional`
--
ALTER TABLE `tb_toko_jam_operasional`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `toko_hari_unik` (`toko_id`,`hari`);

--
-- Indexes for table `tb_toko_pengaturan`
--
ALTER TABLE `tb_toko_pengaturan`
  ADD PRIMARY KEY (`toko_id`);

--
-- Indexes for table `tb_toko_review`
--
ALTER TABLE `tb_toko_review`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_review_toko_id` (`toko_id`),
  ADD KEY `fk_review_user_id` (`user_id`),
  ADD KEY `fk_review_transaksi_id` (`transaksi_id`);

--
-- Indexes for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_invoice` (`kode_invoice`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tb_user`
--
ALTER TABLE `tb_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `tb_user_alamat`
--
ALTER TABLE `tb_user_alamat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_alamat_user` (`user_id`);

--
-- Indexes for table `tb_zona_pengiriman`
--
ALTER TABLE `tb_zona_pengiriman`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_voucher` (`kode_voucher`),
  ADD KEY `fk_voucher_toko` (`toko_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chats`
--
ALTER TABLE `chats`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=215;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `provinces`
--
ALTER TABLE `provinces`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tb_barang`
--
ALTER TABLE `tb_barang`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tb_barang_variasi`
--
ALTER TABLE `tb_barang_variasi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_biaya_pengiriman`
--
ALTER TABLE `tb_biaya_pengiriman`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `tb_detail_transaksi`
--
ALTER TABLE `tb_detail_transaksi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tb_flash_sale_events`
--
ALTER TABLE `tb_flash_sale_events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_flash_sale_produk`
--
ALTER TABLE `tb_flash_sale_produk`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_gambar_barang`
--
ALTER TABLE `tb_gambar_barang`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_kategori`
--
ALTER TABLE `tb_kategori`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `tb_keranjang`
--
ALTER TABLE `tb_keranjang`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tb_komisi`
--
ALTER TABLE `tb_komisi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_kurir_toko`
--
ALTER TABLE `tb_kurir_toko`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_payouts`
--
ALTER TABLE `tb_payouts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_review_produk`
--
ALTER TABLE `tb_review_produk`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_stok_histori`
--
ALTER TABLE `tb_stok_histori`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_toko`
--
ALTER TABLE `tb_toko`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT for table `tb_toko_dekorasi`
--
ALTER TABLE `tb_toko_dekorasi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_toko_jam_operasional`
--
ALTER TABLE `tb_toko_jam_operasional`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_toko_review`
--
ALTER TABLE `tb_toko_review`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tb_user`
--
ALTER TABLE `tb_user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT for table `tb_user_alamat`
--
ALTER TABLE `tb_user_alamat`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tb_zona_pengiriman`
--
ALTER TABLE `tb_zona_pengiriman`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chats`
--
ALTER TABLE `chats`
  ADD CONSTRAINT `fk_chat_customer` FOREIGN KEY (`customer_id`) REFERENCES `tb_user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_chat_toko` FOREIGN KEY (`toko_id`) REFERENCES `tb_toko` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `fk_cities_province` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `districts`
--
ALTER TABLE `districts`
  ADD CONSTRAINT `fk_districts_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_message_chat` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_barang`
--
ALTER TABLE `tb_barang`
  ADD CONSTRAINT `fk_barang_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `tb_kategori` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_barang_toko` FOREIGN KEY (`toko_id`) REFERENCES `tb_toko` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_barang_variasi`
--
ALTER TABLE `tb_barang_variasi`
  ADD CONSTRAINT `fk_variasi_barang` FOREIGN KEY (`barang_id`) REFERENCES `tb_barang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_biaya_pengiriman`
--
ALTER TABLE `tb_biaya_pengiriman`
  ADD CONSTRAINT `fk_biaya_zona` FOREIGN KEY (`zona_id`) REFERENCES `tb_zona_pengiriman` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_detail_transaksi`
--
ALTER TABLE `tb_detail_transaksi`
  ADD CONSTRAINT `fk_detail_transaksi_toko` FOREIGN KEY (`toko_id`) REFERENCES `tb_toko` (`id`),
  ADD CONSTRAINT `fk_detail_transaksi_utama` FOREIGN KEY (`transaksi_id`) REFERENCES `tb_transaksi` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_flash_sale_produk`
--
ALTER TABLE `tb_flash_sale_produk`
  ADD CONSTRAINT `fk_fs_barang` FOREIGN KEY (`barang_id`) REFERENCES `tb_barang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fs_event` FOREIGN KEY (`event_id`) REFERENCES `tb_flash_sale_events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fs_toko` FOREIGN KEY (`toko_id`) REFERENCES `tb_toko` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_gambar_barang`
--
ALTER TABLE `tb_gambar_barang`
  ADD CONSTRAINT `fk_gambar_barang` FOREIGN KEY (`barang_id`) REFERENCES `tb_barang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_kategori`
--
ALTER TABLE `tb_kategori`
  ADD CONSTRAINT `fk_kategori_parent` FOREIGN KEY (`parent_id`) REFERENCES `tb_kategori` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_keranjang`
--
ALTER TABLE `tb_keranjang`
  ADD CONSTRAINT `fk_keranjang_barang` FOREIGN KEY (`barang_id`) REFERENCES `tb_barang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_keranjang_user` FOREIGN KEY (`user_id`) REFERENCES `tb_user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_komisi`
--
ALTER TABLE `tb_komisi`
  ADD CONSTRAINT `fk_komisi_detail` FOREIGN KEY (`detail_transaksi_id`) REFERENCES `tb_detail_transaksi` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_kurir_toko`
--
ALTER TABLE `tb_kurir_toko`
  ADD CONSTRAINT `fk_kurir_toko` FOREIGN KEY (`toko_id`) REFERENCES `tb_toko` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_payouts`
--
ALTER TABLE `tb_payouts`
  ADD CONSTRAINT `fk_payout_toko` FOREIGN KEY (`toko_id`) REFERENCES `tb_toko` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_review_produk`
--
ALTER TABLE `tb_review_produk`
  ADD CONSTRAINT `fk_review_detail` FOREIGN KEY (`detail_transaksi_id`) REFERENCES `tb_detail_transaksi` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_toko`
--
ALTER TABLE `tb_toko`
  ADD CONSTRAINT `fk_toko_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_toko_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_toko_province` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_toko_user` FOREIGN KEY (`user_id`) REFERENCES `tb_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_toko_dekorasi`
--
ALTER TABLE `tb_toko_dekorasi`
  ADD CONSTRAINT `fk_dekorasi_toko` FOREIGN KEY (`toko_id`) REFERENCES `tb_toko` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_toko_jam_operasional`
--
ALTER TABLE `tb_toko_jam_operasional`
  ADD CONSTRAINT `fk_jam_toko` FOREIGN KEY (`toko_id`) REFERENCES `tb_toko` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_toko_pengaturan`
--
ALTER TABLE `tb_toko_pengaturan`
  ADD CONSTRAINT `fk_pengaturan_toko` FOREIGN KEY (`toko_id`) REFERENCES `tb_toko` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_toko_review`
--
ALTER TABLE `tb_toko_review`
  ADD CONSTRAINT `fk_review_toko_id` FOREIGN KEY (`toko_id`) REFERENCES `tb_toko` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_transaksi_id` FOREIGN KEY (`transaksi_id`) REFERENCES `tb_transaksi` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_user_id` FOREIGN KEY (`user_id`) REFERENCES `tb_user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  ADD CONSTRAINT `fk_transaksi_user` FOREIGN KEY (`user_id`) REFERENCES `tb_user` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `tb_user_alamat`
--
ALTER TABLE `tb_user_alamat`
  ADD CONSTRAINT `fk_alamat_user` FOREIGN KEY (`user_id`) REFERENCES `tb_user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD CONSTRAINT `fk_voucher_toko` FOREIGN KEY (`toko_id`) REFERENCES `tb_toko` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
