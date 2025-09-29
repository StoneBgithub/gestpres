-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 30 sep. 2025 à 01:13
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestion_presence`
--

-- --------------------------------------------------------

--
-- Structure de la table `absence`
--

CREATE TABLE `absence` (
  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `id_type_absence` int(11) NOT NULL,
  `justificatif` varchar(255) DEFAULT NULL,
  `id_statut` int(11) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `validation` int(11) DEFAULT NULL,
  `date_autorisation` datetime DEFAULT NULL,
  `motif_rejet` text DEFAULT NULL COMMENT 'Motif du rejet de la demande d''absence'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `absence`
--

INSERT INTO `absence` (`id`, `agent_id`, `date_debut`, `date_fin`, `id_type_absence`, `justificatif`, `id_statut`, `description`, `validation`, `date_autorisation`, `motif_rejet`) VALUES
(2, 129, '2025-07-11', '2025-07-18', 2, 'image.pdf', 1, 'appendicite', 12, NULL, NULL),
(5, 149, '2025-07-14', '2025-07-20', 2, 'FXHCGH', 1, 'Compliqué', 12, NULL, NULL),
(6, 130, '2025-07-14', '2025-08-14', 5, 'dossier.pdf', 1, 'voyage d\'affaire', 10, NULL, NULL),
(14, 216, '2025-09-08', '2025-09-19', 4, NULL, 1, NULL, NULL, NULL, NULL),
(15, 216, '2025-09-08', '2025-09-19', 4, NULL, 1, NULL, NULL, NULL, NULL),
(19, 214, '2025-09-22', '2025-10-22', 5, NULL, 1, '', 10, '2025-09-26 00:01:54', NULL),
(24, 136, '2025-09-25', '2025-10-25', 3, NULL, 1, '', 10, '2025-09-25 23:58:21', NULL),
(26, 178, '2025-09-30', '2025-10-30', 3, NULL, 2, '', 10, '2025-09-26 00:16:33', NULL),
(27, 140, '2025-09-29', '2025-10-29', 3, 'justificatifs/68daa3ecf40b4_8dcad6d3-5213-4db6-86d7-63f170714768__1_.jpeg', 1, '', 10, '2025-09-29 17:34:22', NULL),
(28, 150, '2025-09-29', '2025-10-29', 2, NULL, 2, '', 10, '2025-09-29 17:58:31', 'toute les justificatifs ne sont pas présentes'),
(29, 214, '2025-09-29', '2025-10-29', 3, NULL, 1, '', 10, '2025-09-29 18:54:29', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `agent`
--

CREATE TABLE `agent` (
  `id` int(11) NOT NULL,
  `matricule` varchar(10) DEFAULT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telephone` varchar(20) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `bureau_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `agent`
--

INSERT INTO `agent` (`id`, `matricule`, `nom`, `prenom`, `email`, `telephone`, `photo`, `bureau_id`) VALUES
(38, '38O471S', 'OBISSI', 'Dan', 'dan@gmail.com', '064586471', NULL, 9),
(125, '125E631D', 'EBONDO MALAKA', 'Listete Ornelia', 'lisetteebo@gmail.com', '064594242', NULL, 10),
(126, '126M043S', 'MIME MASSAMBA NÉE MPANZOU', 'Mary Juliette', 'maryse@gmail.com', '0661296043', NULL, 20),
(127, '127E955S', 'EBONDO NGOYA', 'Dominique Nouchika', NULL, '0641013955', NULL, 5),
(128, '128M574S', 'MAMPOUYA FUADIANIMU', 'Amalthée Gabriella', NULL, '0649500574', NULL, 5),
(129, '129O933S', 'OSSEY', 'Geneviève', NULL, '0662180933', NULL, 5),
(130, '130T574S', 'TSIAKAKA MPEKANI', 'Destin Raice', NULL, '0684060574', NULL, 5),
(131, '131O574S', 'OSSETE', 'Martial', NULL, '0684060574', NULL, 5),
(132, '132Y631S', 'YOKA ABIA', 'Fabrice', NULL, '066935631', NULL, 5),
(133, '', 'MBEMB', 'Cele Hulson P.', 'celze@gmail.com', '065256210', NULL, 1),
(134, '134M688E', 'MASSAMBA', 'Cassild Nhyven', NULL, '066364688', NULL, 2),
(136, '136M688E', 'MBEMBA MAYENGA', 'Manassé Jodel', NULL, '066364688', NULL, 2),
(137, '137N723E', 'AGNIELE NKOUNKOU', 'Sage Dieu-Mercy', NULL, '0684752723', NULL, 4),
(138, '138B723E', 'APOKO', 'Gladys Muriel', NULL, '0684752723', NULL, 4),
(139, '139B019E', 'BOULANGA-LOSSINGO', 'Darstel Déchadron', NULL, '0686243019', NULL, 4),
(140, '140O919E', 'OBILANGUNDA-AHOUE', 'Ornelle Martine', NULL, '0686753919', NULL, 4),
(141, '141N098E', 'NKOUA EPALA', 'Clive Sorel', NULL, '069515098', NULL, 4),
(142, '142M423E', 'MVUOMO ALEOYO', 'Dave Chancel', NULL, '0695000423', NULL, 4),
(143, '143M767E', 'MPINDINA', 'Léon Das', NULL, '0689933767', NULL, 12),
(144, '144B925E', 'BOUSSALA AKIM KIENGA', 'Laude Reande', NULL, '064242925', NULL, 12),
(145, '145N925E', 'NGATSENGO', 'Noelys Boris', NULL, '064242925', NULL, 12),
(146, '146T436E', 'TOUA', 'Jolyns Junior', NULL, '064771436', NULL, 12),
(147, '147O436E', 'OBA OKELLY', 'Tino', NULL, '064771436', NULL, 12),
(148, '148B925E', 'BALOUNGA', 'Prosper', 'comptecode2@gmail.com', '066639925', NULL, 19),
(149, '149B533E', 'BANSIMBA FOUIMA', 'Christin Eldrid', NULL, '0656330533', NULL, 8),
(150, '150D677E', 'DZON', 'Prince Junior', NULL, '064938677', NULL, 8),
(151, '151N591E', 'NKOUNKOU BANZOUZI', 'Jéhaline', NULL, '068302591', NULL, 8),
(152, '152B893E', 'BILAYE', 'Stancy Christ', NULL, '069215893', NULL, 8),
(153, '153K421E', 'KONGO BOUDIMBOU', 'Boris', NULL, '068245421', NULL, 8),
(154, '154L433E', 'LOUFOUAMOU', 'Victorin Michel', NULL, '0678133433', NULL, 8),
(155, '155O410E', 'OYELE HONGOTO', 'Odilon', NULL, '068007410', NULL, 8),
(156, '156M376E', 'MALONGA MATEMBE', 'Price Gilclard', NULL, '068658376', NULL, 8),
(157, '157N131E', 'NGAMBOMI ASSOLENGUE ITOUA', 'Tessia Maryse', NULL, '0695328131', NULL, 8),
(158, '158M950E', 'MFOULA', 'Jessica Patricia', NULL, '0690327950', NULL, 8),
(159, '159B454E', 'BASSANGUI', 'Coureil Patrick', NULL, '068602454', NULL, 8),
(160, '160K773E', 'KAPI', 'Evartiste Alfred', NULL, '069414773', NULL, 8),
(161, '161E650E', 'ENGA ANGALI', 'Pamela Blanvy', NULL, '0680228650', NULL, 14),
(162, '162M851E', 'MASSAMBA', 'Thérésia', NULL, '0670251851', NULL, 14),
(163, '163M985E', 'MAMPOUYA', 'Princess Pamela', NULL, '068059985', NULL, 14),
(164, '164B850E', 'BOUNGA', 'Huguès Cendres Valérie', NULL, '0648379850', NULL, 14),
(165, '165I089E', 'ITOUMA', 'Shadé Lesly Emeline', NULL, '068403089', NULL, 14),
(166, '166M842E', 'MPIA', 'Catherine', NULL, '068263842', NULL, 14),
(167, '167E885E', 'ENDEKE', 'Christie', NULL, '068206885', NULL, 14),
(168, '168N558E', 'NKOUNKOU', 'Laude Sagesse', NULL, '069075558', NULL, 14),
(169, '169N244E', 'NGOLO POMENO', 'Julienne Eldaa', NULL, '069535244', NULL, 14),
(170, '170O902E', 'ODOU', 'Ostavi Véronique', NULL, '069879902', NULL, 14),
(171, '171P405E', 'PAKOU', 'Marthelia Chancy Durelle', NULL, '0670262405', NULL, 14),
(172, '172P100E', 'PEYA MOYOU', 'Nelvy', NULL, '0679372100', NULL, 14),
(173, '173N552S', 'NKOUNKOU WAKOULOU', 'André Mozart', NULL, '064475552', NULL, 18),
(174, '174T080S', 'TONDO BALONGA', 'Laurent Stevy', NULL, '065773080', NULL, 3),
(175, '175L060S', 'LIBAMA GHANKIMA', 'Athanadore', NULL, '064365060', NULL, 3),
(176, '176O060S', 'OBAMI MENON BEY', 'Laud', NULL, '064365060', NULL, 3),
(177, '177B285S', 'BINDIHO', 'Carole Marina', NULL, '067863285', NULL, 3),
(178, '178B398S', 'BINDING', 'Béatrice', NULL, '068229398', NULL, 3),
(179, '179N977S', 'NZALANI NILEMYO', 'Rondelieve Félicité C.', NULL, '068959977', NULL, 3),
(180, '180N119S', 'NDEBEKE BIAYENDA', 'Emile', NULL, '068681119', NULL, 3),
(181, '181A386S', 'AWHO', 'Junelle Céline Lisa', NULL, '064642386', NULL, 9),
(182, '182B828S', 'BALECKITA', 'Christ Bertrand Maurice', NULL, '061777828', NULL, 9),
(183, '183B890S', 'BOBILA BOUIKEKA', 'Léonid', NULL, '0646050890', NULL, 9),
(184, '184B875S', 'BOUNG', 'Chrisse Wangil', NULL, '0686680875', NULL, 9),
(185, '185B875S', 'BOUMPOUTOU WISANA', 'Orphée Epiphane', NULL, '0646480875', NULL, 9),
(186, '186E662S', 'ECKASSA NGOUA', 'Yitter Almich', NULL, '0682554662', NULL, 9),
(187, '187G248S', 'GABIO', 'Ruth Préfina', NULL, '0685022248', NULL, 9),
(188, '188M233S', 'MBOUNGO', 'Fresnel Gerald', NULL, '0687795233', NULL, 9),
(189, '189M230S', 'MBOCHI', 'Proverbue Bénédicte', NULL, '0687795230', NULL, 9),
(190, '190M815S', 'MORLENDE', 'Flanick Jovial', NULL, '065785815', NULL, 9),
(191, '191N191S', 'NZONZA ASSIONGO', 'Ange', NULL, '0675131191', NULL, 9),
(192, '192S716S', 'SAMBA OSSOKO', 'Sayo Osée', NULL, '069712716', NULL, 9),
(193, '193M716S', 'MOUSSENGO', 'Claude Céleste', NULL, '069712716', NULL, 9),
(194, '194B716I', 'BOUYA', 'Diane Brina', NULL, '069712716', NULL, 16),
(195, '195E716I', 'EBOMOUA', 'Judicaëlle Marine Jacqueline', NULL, '069712716', NULL, 16),
(196, '196E716I', 'EKIDIZO', 'Léon Dieuveil Chrisostome', NULL, '069712716', NULL, 16),
(197, '197E716I', 'ELONGO IWANGA', 'Faustin Pavels', NULL, '069712716', NULL, 16),
(198, '198E977I', 'ENGAMBE-ITOUA-DIMI', 'Gentiane Solandre', NULL, '068959977', NULL, 16),
(199, '199K977I', 'KHAM-NSAMBAK-NGALA', 'Gisèle Sandra', NULL, '068959977', NULL, 16),
(200, '200M977I', 'MAMPOUYA', 'Paule Sandrine', NULL, '068959977', NULL, 16),
(201, '201M977I', 'MOBILOBARA ECKOSSI NGALA', 'Gloire Prestige', NULL, '068959977', NULL, 16),
(202, '202M977I', 'MOUSSIAKI', 'Bienaimée Yomar', NULL, '068959977', NULL, 16),
(203, '203N977I', 'NGAKOSSO', 'Romary Jourdan', NULL, '068959977', NULL, 16),
(204, '204S977I', 'SABOKA', 'Christ Goldrick', NULL, '068959977', NULL, 16),
(205, '205N977I', 'NGLABABAY', 'Joliane Darceline', NULL, '068959977', NULL, 16),
(206, '206N977I', 'NGAMBOMI ESSEA', 'Danièle Rebecca', NULL, '068959977', NULL, 16),
(207, '207O977I', 'OKANA GUEM', 'Ruth Lammanne', NULL, '068959977', NULL, 16),
(208, '208O977I', 'OLIEKOU', 'Bruno Clevi', NULL, '068959977', NULL, 16),
(209, '209P977I', 'POUCKOUA ONDELE', 'Isaac', NULL, '068959977', NULL, 16),
(213, '148', 'ABANZ', 'Dayana', 'dayana@gmail.com', '065256218', 'photos/68b15d79a981e_PHOTO-2025-08-20-17-39-59.jpg', 2),
(214, '146', 'APIPI BOUYA', 'Pasteur', 'apipi@gmail.com', '065301549', NULL, 2),
(215, '200M210E', 'pascal', 'lissouba', 'rosy.ikama.yeekola@gmail.com', '069530795', NULL, 2),
(216, '216NR795E', 'NGOMA IKAMA', 'Rosy Perine', 'rosy.ikama.yeekola@gmail.com', '069530795', NULL, 2),
(218, '218N955S', 'NGOMA IKAMA', 'Nanouh Sabrina', 'sabrina@gmail.com', '064669955', NULL, 5);

-- --------------------------------------------------------

--
-- Structure de la table `bureau`
--

CREATE TABLE `bureau` (
  `id` int(11) NOT NULL,
  `libele` varchar(100) NOT NULL,
  `service_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `bureau`
--

INSERT INTO `bureau` (`id`, `libele`, `service_id`) VALUES
(1, 'Etude des projets informatiques', 1),
(2, 'Développement informatiques', 1),
(3, 'Réseaux et télécommunications', 2),
(4, 'Etude et bases de données', 1),
(5, 'Secrétariat', 4),
(8, 'Gestion électronique de l\'information', 3),
(9, 'Systèmes et sécurité', 2),
(10, 'DIRECTRICE DES SYSTEMES D\'INFORMATION', 9),
(11, 'Maintenance et gestion du parc informatique', 3),
(12, 'Instance d’affectation', 1),
(13, 'Instance d’affectation', 2),
(14, 'Instance d’affectation', 3),
(15, 'Instance d’affectation', 4),
(16, 'Instance d’affectation globale', 8),
(18, 'Chef de Service', 2),
(19, 'Chef de Service', 3),
(20, 'Chef de Service', 4);

-- --------------------------------------------------------

--
-- Structure de la table `journal_actions`
--

CREATE TABLE `journal_actions` (
  `id` int(11) NOT NULL,
  `ag_id` int(11) NOT NULL,
  `action_type` enum('ajouter','modifier','supprimer','telecharger','generer','connexion','deconnexion') NOT NULL,
  `donnees` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`donnees`)),
  `date_action` datetime DEFAULT current_timestamp(),
  `est_vue` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `journal_actions`
--

INSERT INTO `journal_actions` (`id`, `ag_id`, `action_type`, `donnees`, `date_action`, `est_vue`) VALUES
(55, 11, 'ajouter', '{\"nom\":\"pascal\",\"prenom\":\"lissouba\",\"matricule\":\"200M210E\",\"email\":\"rosy.ikama.yeekola@gmail.com\",\"telephone\":\"069530795\",\"bureau_id\":\"2\"}', '2025-06-19 18:06:38', 1),
(56, 11, 'modifier', '{\"nom\":\"MBEMBA\",\"prenom\":\"Cele Hulson P.\",\"matricule\":\"133M210E\",\"email\":\"celze@gmail.com\",\"telephone\":\"065256210\",\"bureau_id\":\"1\"}', '2025-06-19 20:14:04', 1),
(57, 12, 'modifier', '{\"nom\":\"APIPI BOUYA\",\"prenom\":\"Pasteur\",\"matricule\":\"146\",\"email\":\"apipi@gmail.com\",\"telephone\":\"065301549\",\"bureau_id\":\"2\"}', '2025-06-19 21:10:47', 1),
(58, 12, 'ajouter', '{\"nom\":\"NGOMA IKAMA\",\"prenom\":\"Rosy Perine\",\"matricule\":\"216NR795E\",\"email\":\"rosy.ikama.yeekola@gmail.com\",\"telephone\":\"069530795\",\"bureau_id\":\"2\"}', '2025-06-19 21:41:09', 1),
(59, 12, 'supprimer', '{\"nom\":\"SAMBA\",\"prenom\":\"Jacky Landry\",\"matricule\":\"135S688E\",\"email\":\"grafanachallenge@gmail.com\",\"telephone\":\"066364688\",\"bureau_id\":\"2\"}', '2025-07-09 17:36:53', 1),
(61, 13, 'modifier', '{\"nom\":\"MBEMB\",\"prenom\":\"Cele Hulson P.\",\"matricule\":\"\",\"email\":\"celze@gmail.com\",\"telephone\":\"065256210\",\"bureau_id\":\"1\"}', '2025-07-15 00:00:23', 1),
(62, 12, 'modifier', '{\"nom\":\"MBEMBA\",\"prenom\":\"Cele Hulson P.\",\"matricule\":\"\",\"bureau\":\"Etude des projets informatiques\",\"changes\":{\"nom\":{\"old\":\"MBEMB\",\"new\":\"MBEMBA\"}}}', '2025-07-15 13:20:41', 1),
(67, 13, 'modifier', '{\"nom\":\"MBEMB\",\"prenom\":\"Cele Hulson P.\",\"matricule\":\"\",\"bureau\":\"Etude des projets informatiques\",\"changes\":{\"nom\":{\"old\":\"MBEMBA\",\"new\":\"MBEMB\"}}}', '2025-07-20 22:52:52', 1),
(68, 13, 'ajouter', '{\"nom\":\"NGOMA IKAMA\",\"prenom\":\"Nanouh Sabrina\",\"matricule\":\"217N955S\",\"email\":\"sabrina@gmail.com\",\"telephone\":\"064669955\",\"bureau\":\"Secrétariat\"}', '2025-07-22 17:08:29', 1),
(69, 13, 'ajouter', '{\"nom\":\"NGOMA IKAMA\",\"prenom\":\"Nanouh Sabrina\",\"matricule\":\"218N955S\",\"email\":\"sabrina@gmail.com\",\"telephone\":\"064669955\",\"bureau\":\"Secrétariat\"}', '2025-07-22 17:23:50', 1),
(70, 13, 'supprimer', '{\"nom\":\"NGOMA IKAMA\",\"prenom\":\"Nanouh Sabrina\",\"matricule\":\"217N955S\",\"email\":\"sabrina@gmail.com\",\"telephone\":\"064669955\",\"bureau\":\"Secrétariat\"}', '2025-07-22 17:24:40', 1),
(71, 13, '', '{\"agent_id\":\"215\"}', '2025-08-26 17:06:13', 1),
(72, 13, '', '{\"agent_id\":\"215\",\"email\":\"rosy.ikama.yeekola@gmail.com\",\"nom\":\"pascal\",\"prenom\":\"lissouba\",\"statut\":\"activé\",\"role_id\":8,\"role\":\"admnistrateur\",\"etat\":\"déconnecté\"}', '2025-08-26 17:17:31', 1),
(73, 13, '', '{\"agent_id\":\"127\"}', '2025-08-26 17:19:19', 1),
(74, 13, '', '{\"agent_id\":\"218\"}', '2025-08-26 17:19:25', 1),
(75, 13, '', '{\"agent_id\":\"214\"}', '2025-08-26 17:19:31', 1),
(76, 11, 'supprimer', '{\"agent_id\":\"OSSETE Martial\",\" date_debut\":\"2025-07-11\",\" date_fin\":\"2025-07-25\",\"id_type_absence\":null,\"justificatif\":\"image.jpg\",\"id_statut\":null,\"description\":\"gchjk\"}', '2025-08-26 18:30:07', 1),
(77, 11, 'supprimer', '{\"agent_id\":\"EBONDO NGOYA Dominique Nouchika\",\" date_debut\":\"2025-07-11\",\" date_fin\":\"2025-07-17\",\"id_type_absence\":null,\"justificatif\":\"hdj\",\"id_statut\":null,\"description\":\"xsbj,kx\"}', '2025-08-26 18:30:26', 1),
(78, 11, 'modifier', '{\"nom\":\"ABANZ\",\"prenom\":\"Dayana\",\"matricule\":\"148\",\"bureau\":\"Développement informatiques\",\"changes\":{\"photo\":{\"old\":\"Aucune\",\"new\":\"photos\\/68b15d79a981e_PHOTO-2025-08-20-17-39-59.jpg\"}}}', '2025-08-29 09:57:45', 1),
(79, 11, 'supprimer', '{\"agent_id\":\"BOULANGA-LOSSINGO Darstel Déchadron\",\" date_debut\":\"2025-07-25\",\" date_fin\":\"2025-08-25\",\"id_type_absence\":null,\"justificatif\":null,\"id_statut\":null,\"description\":\"\"}', '2025-08-31 20:56:55', 1),
(80, 11, 'supprimer', '{\"agent_id\":\"OSSETE Martial\",\" date_debut\":\"2025-09-08\",\" date_fin\":\"2025-09-12\",\"id_type_absence\":null,\"justificatif\":null,\"id_statut\":null,\"description\":null}', '2025-09-07 02:07:58', 1),
(81, 11, 'supprimer', '{\"agent_id\":\"OSSETE Martial\",\" date_debut\":\"2025-09-08\",\" date_fin\":\"2025-09-12\",\"id_type_absence\":null,\"justificatif\":null,\"id_statut\":null,\"description\":\"\"}', '2025-09-07 18:16:46', 1),
(82, 13, '', '{\"agent_id\":\"128\"}', '2025-09-07 18:25:14', 1);

-- --------------------------------------------------------

--
-- Structure de la table `login`
--

CREATE TABLE `login` (
  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `derniere_connexion` timestamp NOT NULL DEFAULT current_timestamp(),
  `statut` enum('activé','désactivé') DEFAULT 'activé',
  `role_id` int(11) NOT NULL,
  `etat` enum('connecté','déconnecté') DEFAULT 'déconnecté'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `login`
--

INSERT INTO `login` (`id`, `agent_id`, `mot_de_passe`, `date_creation`, `derniere_connexion`, `statut`, `role_id`, `etat`) VALUES
(10, 125, '123456', '2025-05-05 10:48:47', '2025-07-17 06:55:32', 'activé', 6, 'connecté'),
(11, 126, '123456', '2025-05-05 10:49:09', '2025-05-05 10:49:09', 'activé', 7, 'déconnecté'),
(12, 148, '123456', '2025-05-05 10:49:39', '2025-07-17 07:01:57', 'activé', 5, 'connecté'),
(13, 216, '123456@admin', '2025-06-19 19:52:33', '2025-06-19 19:52:33', 'activé', 8, 'déconnecté'),
(14, 133, '$2y$10$H84uZ/uIPfqFCEA/koxdluySH767jJPM4fKA0h.8lFd8hN8htATOS', '2025-07-10 01:22:23', '2025-07-10 01:22:23', 'activé', 5, 'déconnecté'),
(26, 213, '$2y$10$ymOAzVZsLJhauPXJbAztN.Lr7mjnKxploylu5rvUiGf7hR.j62q46', '2025-07-20 20:14:23', '2025-07-20 20:14:23', 'activé', 8, 'déconnecté'),
(42, 215, '$2y$10$sZ5TqSRoBHnttIKpLpcAmeODrt7fYTiGn77EnsRuM17w3mqHCkU0.', '2025-08-26 15:17:31', '2025-08-26 15:17:31', 'activé', 8, 'déconnecté');

-- --------------------------------------------------------

--
-- Structure de la table `permission`
--

CREATE TABLE `permission` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `presence`
--

CREATE TABLE `presence` (
  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `heure` time NOT NULL,
  `type` enum('arrivée','depart') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `presence`
--

INSERT INTO `presence` (`id`, `agent_id`, `date`, `heure`, `type`) VALUES
(37, 148, '2025-04-16', '08:12:40', 'arrivée'),
(38, 137, '2025-04-16', '13:46:02', 'arrivée'),
(39, 137, '2025-04-17', '12:59:17', 'arrivée'),
(40, 137, '2025-04-17', '12:59:47', 'depart'),
(51, 137, '2025-04-23', '09:34:21', 'arrivée'),
(57, 148, '2025-04-23', '10:02:34', 'arrivée'),
(58, 148, '2025-04-23', '10:02:49', 'depart'),
(59, 148, '2025-04-24', '09:56:34', 'arrivée'),
(60, 148, '2025-04-24', '15:56:50', 'depart'),
(61, 148, '2025-04-25', '01:58:29', 'arrivée'),
(62, 148, '2025-04-25', '01:58:47', 'depart'),
(63, 125, '2025-05-14', '10:43:05', 'arrivée'),
(64, 125, '2025-05-14', '10:44:12', 'depart'),
(65, 125, '2025-05-16', '12:39:26', 'arrivée'),
(66, 38, '2025-06-03', '08:34:00', 'arrivée'),
(67, 38, '2025-06-03', '08:34:00', 'arrivée'),
(68, 38, '2025-06-03', '08:34:00', 'arrivée'),
(69, 38, '2025-06-02', '13:37:00', 'arrivée'),
(70, 38, '2025-06-02', '14:38:00', 'depart'),
(71, 133, '2025-07-14', '23:58:00', 'arrivée'),
(72, 214, '2025-09-29', '08:50:00', 'arrivée'),
(73, 214, '2025-09-29', '14:30:00', 'depart');

-- --------------------------------------------------------

--
-- Structure de la table `role`
--

CREATE TABLE `role` (
  `id` int(11) NOT NULL,
  `libelle` enum('secretaire','directrice','chef de service','admnistrateur') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `role`
--

INSERT INTO `role` (`id`, `libelle`) VALUES
(7, 'secretaire'),
(6, 'directrice'),
(5, 'chef de service'),
(8, 'admnistrateur');

-- --------------------------------------------------------

--
-- Structure de la table `role_permission`
--

CREATE TABLE `role_permission` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `service`
--

CREATE TABLE `service` (
  `id` int(11) NOT NULL,
  `libele` varchar(100) NOT NULL,
  `chef_service_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `service`
--

INSERT INTO `service` (`id`, `libele`, `chef_service_id`) VALUES
(1, 'Etude et développement', NULL),
(2, 'Système et réseau', 173),
(3, 'Exploitation', 148),
(4, 'Secrétariat de direction', 126),
(8, 'Instance d’affectation globale', NULL),
(9, 'Direction Générale', 125);

-- --------------------------------------------------------

--
-- Structure de la table `statut_absence`
--

CREATE TABLE `statut_absence` (
  `id` int(11) NOT NULL,
  `libelle` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `statut_absence`
--

INSERT INTO `statut_absence` (`id`, `libelle`) VALUES
(1, 'autoriser'),
(2, 'rejeter'),
(3, 'en attente');

-- --------------------------------------------------------

--
-- Structure de la table `type_absence`
--

CREATE TABLE `type_absence` (
  `id` int(11) NOT NULL,
  `libelle` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `type_absence`
--

INSERT INTO `type_absence` (`id`, `libelle`) VALUES
(1, 'congé annuel'),
(2, 'maladie'),
(3, 'grossesse'),
(4, 'formation'),
(5, 'mission');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `absence`
--
ALTER TABLE `absence`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agent_id` (`agent_id`),
  ADD KEY `id_type_absence` (`id_type_absence`),
  ADD KEY `id_statut` (`id_statut`),
  ADD KEY `fk_autorise_par` (`validation`);

--
-- Index pour la table `agent`
--
ALTER TABLE `agent`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matricule` (`matricule`),
  ADD KEY `bureau_id` (`bureau_id`);

--
-- Index pour la table `bureau`
--
ALTER TABLE `bureau`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`);

--
-- Index pour la table `journal_actions`
--
ALTER TABLE `journal_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `journal_actions_ibfk_1` (`ag_id`);

--
-- Index pour la table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `agent_id` (`agent_id`),
  ADD KEY `login_ibfk_2` (`role_id`);

--
-- Index pour la table `permission`
--
ALTER TABLE `permission`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `presence`
--
ALTER TABLE `presence`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agent_id` (`agent_id`);

--
-- Index pour la table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `libelle` (`libelle`);

--
-- Index pour la table `role_permission`
--
ALTER TABLE `role_permission`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Index pour la table `service`
--
ALTER TABLE `service`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_chef_service` (`chef_service_id`);

--
-- Index pour la table `statut_absence`
--
ALTER TABLE `statut_absence`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `type_absence`
--
ALTER TABLE `type_absence`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `absence`
--
ALTER TABLE `absence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT pour la table `agent`
--
ALTER TABLE `agent`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=219;

--
-- AUTO_INCREMENT pour la table `bureau`
--
ALTER TABLE `bureau`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `journal_actions`
--
ALTER TABLE `journal_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT pour la table `login`
--
ALTER TABLE `login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT pour la table `permission`
--
ALTER TABLE `permission`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `presence`
--
ALTER TABLE `presence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT pour la table `role`
--
ALTER TABLE `role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `role_permission`
--
ALTER TABLE `role_permission`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `service`
--
ALTER TABLE `service`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `statut_absence`
--
ALTER TABLE `statut_absence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `type_absence`
--
ALTER TABLE `type_absence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `absence`
--
ALTER TABLE `absence`
  ADD CONSTRAINT `absence_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `agent` (`id`),
  ADD CONSTRAINT `absence_ibfk_3` FOREIGN KEY (`id_type_absence`) REFERENCES `type_absence` (`id`),
  ADD CONSTRAINT `absence_ibfk_4` FOREIGN KEY (`id_statut`) REFERENCES `statut_absence` (`id`),
  ADD CONSTRAINT `fk_autorise_par` FOREIGN KEY (`validation`) REFERENCES `login` (`id`);

--
-- Contraintes pour la table `agent`
--
ALTER TABLE `agent`
  ADD CONSTRAINT `agent_ibfk_1` FOREIGN KEY (`bureau_id`) REFERENCES `bureau` (`id`);

--
-- Contraintes pour la table `bureau`
--
ALTER TABLE `bureau`
  ADD CONSTRAINT `bureau_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `service` (`id`);

--
-- Contraintes pour la table `journal_actions`
--
ALTER TABLE `journal_actions`
  ADD CONSTRAINT `journal_actions_ibfk_1` FOREIGN KEY (`ag_id`) REFERENCES `login` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `login`
--
ALTER TABLE `login`
  ADD CONSTRAINT `login_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `agent` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `login_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `presence`
--
ALTER TABLE `presence`
  ADD CONSTRAINT `presence_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `agent` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `role_permission`
--
ALTER TABLE `role_permission`
  ADD CONSTRAINT `role_permission_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`),
  ADD CONSTRAINT `role_permission_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permission` (`id`);

--
-- Contraintes pour la table `service`
--
ALTER TABLE `service`
  ADD CONSTRAINT `fk_chef_service` FOREIGN KEY (`chef_service_id`) REFERENCES `agent` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
