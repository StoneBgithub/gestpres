-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : lun. 28 avr. 2025 à 11:02
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
-- Structure de la table `absence_justifiee`
--

CREATE TABLE `absence_justifiee` (
  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(126, '126M043S', 'MIME MASSAMBA NÉE MPANZOU', 'Mary Juliette', NULL, '0661296043', NULL, 20),
(127, '127E955S', 'EBONDO NGOYA', 'Dominique Nouchika', NULL, '0641013955', NULL, 5),
(128, '128M574S', 'MAMPOUYA FUADIANIMU', 'Amalthée Gabriella', NULL, '0649500574', NULL, 5),
(129, '129O933S', 'OSSEY', 'Geneviève', NULL, '0662180933', NULL, 5),
(130, '130T574S', 'TSIAKAKA MPEKANI', 'Destin Raice', NULL, '0684060574', NULL, 5),
(131, '131O574S', 'OSSETE', 'Martial', NULL, '0684060574', NULL, 5),
(132, '132Y631S', 'YOKA ABIA', 'Fabrice', NULL, '066935631', NULL, 5),
(133, '133M210E', 'MBEMBA', 'Celze Hulson P.', NULL, '065256210', NULL, 1),
(134, '134M688E', 'MASSAMBA', 'Cassild Nhyven', NULL, '066364688', NULL, 2),
(135, '135S688E', 'SAMBA', 'Jacky Landry', NULL, '066364688', NULL, 2),
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
(209, '209P977I', 'POUCKOUA ONDELE', 'Isaac', NULL, '068959977', NULL, 16);

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
-- Structure de la table `login`
--

CREATE TABLE `login` (
  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('admin','viewer') NOT NULL DEFAULT 'viewer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `login`
--

INSERT INTO `login` (`id`, `agent_id`, `mot_de_passe`, `role`) VALUES
(4, 38, '12345', 'admin'),
(5, 173, '123456', 'viewer'),
(6, 148, '123456', 'admin'),
(7, 126, '123456', 'viewer'),
(8, 125, '123456', 'admin');

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
(62, 148, '2025-04-25', '01:58:47', 'depart');

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

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `absence_justifiee`
--
ALTER TABLE `absence_justifiee`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agent_id` (`agent_id`);

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
-- Index pour la table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `agent_id` (`agent_id`);

--
-- Index pour la table `presence`
--
ALTER TABLE `presence`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agent_id` (`agent_id`);

--
-- Index pour la table `service`
--
ALTER TABLE `service`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_chef_service` (`chef_service_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `absence_justifiee`
--
ALTER TABLE `absence_justifiee`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `agent`
--
ALTER TABLE `agent`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=210;

--
-- AUTO_INCREMENT pour la table `bureau`
--
ALTER TABLE `bureau`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `login`
--
ALTER TABLE `login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `presence`
--
ALTER TABLE `presence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT pour la table `service`
--
ALTER TABLE `service`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `absence_justifiee`
--
ALTER TABLE `absence_justifiee`
  ADD CONSTRAINT `absence_justifiee_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `agent` (`id`) ON DELETE CASCADE;

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
-- Contraintes pour la table `login`
--
ALTER TABLE `login`
  ADD CONSTRAINT `login_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `agent` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `presence`
--
ALTER TABLE `presence`
  ADD CONSTRAINT `presence_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `agent` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `service`
--
ALTER TABLE `service`
  ADD CONSTRAINT `fk_chef_service` FOREIGN KEY (`chef_service_id`) REFERENCES `agent` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
