-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 20, 2026 at 01:08 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `first_project`
--

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`id`, `name`, `description`) VALUES
(1, 'informatique', 'cette cat??gorie contient tous les necessaires pour informatique'),
(2, 'acessoires', 'tous les n??cessaires pour accessoires'),
(4, 'categorie', 'bonne cat');

-- --------------------------------------------------------

--
-- Table structure for table `comment`
--

CREATE TABLE `comment` (
  `id` int(11) NOT NULL,
  `content` longtext NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `post_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comment`
--

INSERT INTO `comment` (`id`, `content`, `image`, `user_id`, `created_at`, `post_id`, `parent_id`) VALUES
(3, 'hellooooo\n', NULL, 5, '2026-02-13 11:47:54', 2, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `comment_reaction`
--

CREATE TABLE `comment_reaction` (
  `id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contract`
--

CREATE TABLE `contract` (
  `id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `content` longtext DEFAULT NULL,
  `request_id` int(11) NOT NULL,
  `sponsor_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conversation`
--

CREATE TABLE `conversation` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `conversation`
--

INSERT INTO `conversation` (`id`, `buyer_id`, `seller_id`, `product_id`, `created_at`) VALUES
(1, 4, 3, 6, '2026-02-14 22:44:14'),
(2, 9, 3, 6, '2026-02-14 22:46:37'),
(3, 4, 9, 7, '2026-02-14 23:00:05');

-- --------------------------------------------------------

--
-- Table structure for table `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20260201185516', '2026-02-12 23:26:04', 102),
('DoctrineMigrations\\Version20260205143810', '2026-02-12 23:26:04', 7),
('DoctrineMigrations\\Version20260208194359', '2026-02-12 23:26:04', 7),
('DoctrineMigrations\\Version20260208201147', '2026-02-12 23:26:05', 46),
('DoctrineMigrations\\Version20260208205931', '2026-02-12 23:26:05', 66),
('DoctrineMigrations\\Version20260212150000', '2026-02-12 23:26:05', 62),
('DoctrineMigrations\\Version20260212160000', '2026-02-12 23:39:41', 12);

-- --------------------------------------------------------

--
-- Table structure for table `document`
--

CREATE TABLE `document` (
  `id` int(11) NOT NULL,
  `message` longtext NOT NULL,
  `motivation` longtext NOT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `client_id` int(11) NOT NULL,
  `offer_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

CREATE TABLE `message` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `content` longtext NOT NULL,
  `is_read` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `audio_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `message`
--

INSERT INTO `message` (`id`, `conversation_id`, `sender_id`, `content`, `is_read`, `created_at`, `audio_path`) VALUES
(1, 1, 4, 'bonjouur ', 0, '2026-02-14 22:44:25', NULL),
(2, 1, 4, 'bonjouur ', 0, '2026-02-14 22:44:28', NULL),
(3, 3, 4, 'helloooo', 1, '2026-02-14 23:00:16', NULL),
(4, 3, 9, 'helloooo', 1, '2026-02-14 23:01:44', NULL),
(5, 3, 9, 'helloooo', 1, '2026-02-14 23:01:48', NULL),
(6, 3, 9, '[Message Vocal]', 1, '2026-02-14 23:09:11', 'uploads/audio/6990f2870b53d.webm'),
(7, 2, 9, '[Message Vocal]', 0, '2026-02-14 23:35:43', 'uploads/audio/6990f8bf5e7c3.webm'),
(8, 3, 9, '[Message Vocal]', 1, '2026-02-15 13:22:10', 'uploads/audio/6991ba724116b.webm');

-- --------------------------------------------------------

--
-- Table structure for table `messenger_messages`
--

CREATE TABLE `messenger_messages` (
  `id` bigint(20) NOT NULL,
  `body` longtext NOT NULL,
  `headers` longtext NOT NULL,
  `queue_name` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL,
  `available_at` datetime NOT NULL,
  `delivered_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `is_read` tinyint(4) NOT NULL,
  `created_at` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `actor_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification`
--

INSERT INTO `notification` (`id`, `type`, `is_read`, `created_at`, `user_id`, `actor_id`, `post_id`) VALUES
(1, 'comment', 0, '2026-02-13 11:44:05', 3, 5, 2),
(2, 'like', 0, '2026-02-13 11:45:40', 3, 5, 2),
(3, 'comment', 0, '2026-02-13 11:47:54', 3, 5, 2);

-- --------------------------------------------------------

--
-- Table structure for table `order`
--

CREATE TABLE `order` (
  `id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `order_date` datetime NOT NULL,
  `status` varchar(50) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `stripe_session_id` varchar(255) DEFAULT NULL,
  `payment_intent_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`id`, `quantity`, `order_date`, `status`, `product_id`, `user_id`, `stripe_session_id`, `payment_intent_id`) VALUES
(2, 2, '2026-02-13 01:16:10', 'pending', 2, 4, NULL, NULL),
(3, 1, '2026-02-13 05:23:32', 'pending', 2, 4, NULL, NULL),
(4, 1, '2026-02-13 11:33:41', 'pending', 3, 5, NULL, NULL),
(5, 1, '2026-02-13 11:33:41', 'pending', 2, 5, NULL, NULL),
(7, 1, '2026-02-13 23:32:14', 'pending', 5, 3, NULL, NULL),
(9, 3, '2026-02-13 23:55:45', 'returned', 3, 3, NULL, NULL),
(10, 3, '2026-02-14 00:01:48', 'pending', 5, 3, NULL, NULL),
(11, 1, '2026-02-14 00:03:24', 'pending', 5, 3, NULL, NULL),
(12, 1, '2026-02-14 00:27:00', 'pending', 5, 3, NULL, NULL),
(13, 1, '2026-02-14 12:44:39', 'pending', 3, 3, NULL, NULL),
(14, 1, '2026-02-15 13:23:11', 'pending', 1, 9, NULL, NULL),
(15, 1, '2026-02-15 15:16:07', 'pending', 5, 4, NULL, NULL),
(16, 1, '2026-02-16 11:26:45', 'delivered', 3, 9, NULL, NULL),
(17, 1, '2026-02-17 14:41:10', 'pending', 6, 9, NULL, NULL),
(18, 1, '2026-02-20 09:24:50', 'pending', 7, 3, NULL, NULL),
(19, 1, '2026-02-20 09:25:52', 'paid', 6, 3, NULL, NULL),
(20, 1, '2026-02-20 12:51:04', 'pending', 7, 11, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `post`
--

CREATE TABLE `post` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `up_votes` int(11) NOT NULL,
  `down_votes` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post`
--

INSERT INTO `post` (`id`, `title`, `content`, `image`, `type`, `up_votes`, `down_votes`, `created_at`, `user_id`) VALUES
(2, 'statut', 'helloooozdzdzzd', NULL, 'announcement', 0, 0, '2026-02-13 11:43:22', 3);

-- --------------------------------------------------------

--
-- Table structure for table `post_reaction`
--

CREATE TABLE `post_reaction` (
  `id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` double NOT NULL,
  `description` longtext DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `seller_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`id`, `name`, `price`, `description`, `image`, `stock`, `created_at`, `category_id`, `seller_id`) VALUES
(1, 'Pro Controller', 69, 'Manette pro', 'assets\\images\\product\\product-2.jpg', 8, '2026-02-12 23:43:40', 1, NULL),
(2, 'manette', 300, 'manette ps3', 'assets/images/product/OIP.png', 3, '2026-02-13 01:00:48', 1, NULL),
(3, 'Nintendo', 100, 'bonne etat', 'assets/images/product/nintendo.jpg', 1, '2026-02-13 05:29:58', 2, NULL),
(5, 'souris', 60, 'smooth', 'assets/images/product/souris.jpg', 0, '2026-02-13 23:22:11', 2, NULL),
(6, 'cable', 30, 'bon', 'assets/images/product/cable.jpg', 6, '2026-02-14 22:42:58', 1, 3),
(7, 'telephone', 500, 'hellooo', 'assets/images/product/tel.jpg', 5, '2026-02-14 22:53:17', 1, 9);

-- --------------------------------------------------------

--
-- Table structure for table `return_request`
--

CREATE TABLE `return_request` (
  `id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `reason` longtext NOT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `admin_comment` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `return_request`
--

INSERT INTO `return_request` (`id`, `order_item_id`, `reason`, `status`, `created_at`, `refund_amount`, `admin_comment`) VALUES
(1, 9, 'pas aim??', 'approved', '2026-02-14 00:40:21', 299.77, 'okkkk'),
(2, 14, 'pas bien ', 'rejected', '2026-02-16 12:59:30', NULL, 'non');

-- --------------------------------------------------------

--
-- Table structure for table `sponsor`
--

CREATE TABLE `sponsor` (
  `id` int(11) NOT NULL,
  `nom_societe` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `amount` double NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `dossier` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `sponsor_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sponsor`
--

INSERT INTO `sponsor` (`id`, `nom_societe`, `description`, `amount`, `target_type`, `dossier`, `created_at`, `sponsor_id`) VALUES
(1, 'guesmi islem', 'jbjbjbjbbj', 50, 'client', NULL, '2026-02-13 03:52:59', 5),
(2, 'fff', 'ffdcscscscscs', 50, 'tournament', NULL, '2026-02-13 10:54:52', 3),
(3, 'isleeeem', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 50, 'client', NULL, '2026-02-13 11:59:22', 5);

-- --------------------------------------------------------

--
-- Table structure for table `tournament`
--

CREATE TABLE `tournament` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` date NOT NULL,
  `max_players` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tournament_participation`
--

CREATE TABLE `tournament_participation` (
  `id` int(11) NOT NULL,
  `registration_date` datetime NOT NULL,
  `status` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `bio` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `role` varchar(20) NOT NULL,
  `nom` varchar(255) DEFAULT NULL,
  `datenaissance` date DEFAULT NULL,
  `prenom` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `email`, `password`, `bio`, `created_at`, `telephone`, `role`, `nom`, `datenaissance`, `prenom`, `image`) VALUES
(2, ' islem', 'moujib@gmail.com', '$2y$13$FZLO9LVZSoxa9r8VXIMyy.vCs.1g1aO9RvPiM2iUEc3sLbtqgCn3O', NULL, '2026-02-13 00:35:24', '90 78 54 31 12', 'admin', '', NULL, '', NULL),
(3, ' eya', 'eya@gmail.com', '$2y$13$0oNzhQ2g2dNFE2T2LfgZSO1ePEfsz5fK37NYEZ4VZaCv8K3Mx4cC2', NULL, '2026-02-13 00:37:11', '', 'admin', '', NULL, '', NULL),
(4, 'beya', 'beya@gmail.com', '$2y$13$hDVw93VeYpwwTtCNgG.yauduLw69o.1we2jxrk37d/ATlbpV0aQKm', 'hello', '2026-02-13 01:14:54', '90785431', 'client', 'mlika', NULL, 'beya', NULL),
(5, 'islem', 'islem@gmail.com', '$2y$13$MRRtS/epf.UfUAALwTh8TulLKO5DHWWe8E/e.yrA1ikkyujifDspS', 'hello', '2026-02-13 03:39:17', '1234567899', 'sponsor', 'guesmi', NULL, 'islem', NULL),
(6, 'mahdi', 'mahdi@gmail.com', '$2y$13$NxYHdsOR.cVmp.B/725qduWDR5ygHysPIBW.XkHHXNa8LxzQAwexS', 'hii', '2026-02-13 10:19:58', '1234567899', 'client', 'heni', NULL, 'heni', NULL),
(8, 'talbi aziz', 'aziz@gmail.com', '$2y$13$UHrDwx7O/qw0NpTkUmMoku/fOvRLT0hu00jUDx5gYR8AjuPwANXB2', NULL, '2026-02-13 10:28:36', '', 'client', 'aziz', NULL, 'talbi', NULL),
(9, 'rezgui hela', 'hela@gmail.com', '$2y$13$2I9DWrH4Nsk6FINdaJju8O8z/FoG/FhRcagFrAfpRcFbG5yYMeZuK', NULL, '2026-02-13 10:38:43', '', 'client', 'hela', NULL, 'rezgui', NULL),
(11, 'boulifi aziz', 'aziz.boulifi@gmail.com', '$2y$13$sqWz.lr0KZ97H0ycCAf1QO/lzpqoAJSP63rhh1rb0x/z4SAyEGSvm', NULL, '2026-02-20 12:49:17', '1111111115', 'client', 'aziz', '2003-05-26', 'boulifi', 'QmV2n5ye5TyNo4AAfvopbSBzLjHfrBqfyZivPPDLqirbV4-69984a91ecb08.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `comment`
--
ALTER TABLE `comment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_9474526CA76ED395` (`user_id`),
  ADD KEY `IDX_9474526C4B89032C` (`post_id`),
  ADD KEY `IDX_9474526C727ACA70` (`parent_id`);

--
-- Indexes for table `comment_reaction`
--
ALTER TABLE `comment_reaction`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_B99364F1A76ED395` (`user_id`),
  ADD KEY `IDX_B99364F1F8697D13` (`comment_id`);

--
-- Indexes for table `contract`
--
ALTER TABLE `contract`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_E98F2859427EB8A5` (`request_id`),
  ADD KEY `IDX_E98F285912F7FB51` (`sponsor_id`),
  ADD KEY `IDX_E98F285919EB6921` (`client_id`);

--
-- Indexes for table `conversation`
--
ALTER TABLE `conversation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_8A8E26196C755722` (`buyer_id`),
  ADD KEY `IDX_8A8E26198DE820D9` (`seller_id`),
  ADD KEY `IDX_8A8E26194584665A` (`product_id`);

--
-- Indexes for table `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- Indexes for table `document`
--
ALTER TABLE `document`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_D8698A7619EB6921` (`client_id`),
  ADD KEY `IDX_D8698A7653C674EE` (`offer_id`);

--
-- Indexes for table `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_B6BD307F9AC0396` (`conversation_id`),
  ADD KEY `IDX_B6BD307FF624B39D` (`sender_id`);

--
-- Indexes for table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750` (`queue_name`,`available_at`,`delivered_at`,`id`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_BF5476CAA76ED395` (`user_id`),
  ADD KEY `IDX_BF5476CA10DAF24A` (`actor_id`),
  ADD KEY `IDX_BF5476CA4B89032C` (`post_id`);

--
-- Indexes for table `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_ORDER_PRODUCT_ID` (`product_id`),
  ADD KEY `IDX_ORDER_USER_ID` (`user_id`);

--
-- Indexes for table `post`
--
ALTER TABLE `post`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_5A8A6C8DA76ED395` (`user_id`);

--
-- Indexes for table `post_reaction`
--
ALTER TABLE `post_reaction`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_1B3A8E56A76ED395` (`user_id`),
  ADD KEY `IDX_1B3A8E564B89032C` (`post_id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_D34A04AD12469DE2` (`category_id`),
  ADD KEY `IDX_D34A04AD8DE820D9` (`seller_id`);

--
-- Indexes for table `return_request`
--
ALTER TABLE `return_request`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_C2E06A26E415FB15` (`order_item_id`);

--
-- Indexes for table `sponsor`
--
ALTER TABLE `sponsor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_818CC9D412F7FB51` (`sponsor_id`);

--
-- Indexes for table `tournament`
--
ALTER TABLE `tournament`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tournament_participation`
--
ALTER TABLE `tournament_participation`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `comment`
--
ALTER TABLE `comment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `comment_reaction`
--
ALTER TABLE `comment_reaction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contract`
--
ALTER TABLE `contract`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conversation`
--
ALTER TABLE `conversation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `document`
--
ALTER TABLE `document`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message`
--
ALTER TABLE `message`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `order`
--
ALTER TABLE `order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `post`
--
ALTER TABLE `post`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `post_reaction`
--
ALTER TABLE `post_reaction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `return_request`
--
ALTER TABLE `return_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sponsor`
--
ALTER TABLE `sponsor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tournament`
--
ALTER TABLE `tournament`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tournament_participation`
--
ALTER TABLE `tournament_participation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `FK_9474526C4B89032C` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`),
  ADD CONSTRAINT `FK_9474526C727ACA70` FOREIGN KEY (`parent_id`) REFERENCES `comment` (`id`),
  ADD CONSTRAINT `FK_9474526CA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `comment_reaction`
--
ALTER TABLE `comment_reaction`
  ADD CONSTRAINT `FK_B99364F1A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_B99364F1F8697D13` FOREIGN KEY (`comment_id`) REFERENCES `comment` (`id`);

--
-- Constraints for table `contract`
--
ALTER TABLE `contract`
  ADD CONSTRAINT `FK_E98F285912F7FB51` FOREIGN KEY (`sponsor_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_E98F285919EB6921` FOREIGN KEY (`client_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_E98F2859427EB8A5` FOREIGN KEY (`request_id`) REFERENCES `document` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `conversation`
--
ALTER TABLE `conversation`
  ADD CONSTRAINT `FK_8A8E26194584665A` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`),
  ADD CONSTRAINT `FK_8A8E26196C755722` FOREIGN KEY (`buyer_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_8A8E26198DE820D9` FOREIGN KEY (`seller_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `document`
--
ALTER TABLE `document`
  ADD CONSTRAINT `FK_D8698A7619EB6921` FOREIGN KEY (`client_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_D8698A7653C674EE` FOREIGN KEY (`offer_id`) REFERENCES `sponsor` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `message`
--
ALTER TABLE `message`
  ADD CONSTRAINT `FK_B6BD307F9AC0396` FOREIGN KEY (`conversation_id`) REFERENCES `conversation` (`id`),
  ADD CONSTRAINT `FK_B6BD307FF624B39D` FOREIGN KEY (`sender_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `FK_BF5476CA10DAF24A` FOREIGN KEY (`actor_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_BF5476CA4B89032C` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`),
  ADD CONSTRAINT `FK_BF5476CAA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `FK_ORDER_PRODUCT` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`),
  ADD CONSTRAINT `FK_ORDER_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `FK_5A8A6C8DA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `post_reaction`
--
ALTER TABLE `post_reaction`
  ADD CONSTRAINT `FK_1B3A8E564B89032C` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`),
  ADD CONSTRAINT `FK_1B3A8E56A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `FK_D34A04AD12469DE2` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`),
  ADD CONSTRAINT `FK_D34A04AD8DE820D9` FOREIGN KEY (`seller_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `return_request`
--
ALTER TABLE `return_request`
  ADD CONSTRAINT `FK_C2E06A26E415FB15` FOREIGN KEY (`order_item_id`) REFERENCES `order` (`id`);

--
-- Constraints for table `sponsor`
--
ALTER TABLE `sponsor`
  ADD CONSTRAINT `FK_818CC9D412F7FB51` FOREIGN KEY (`sponsor_id`) REFERENCES `user` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
