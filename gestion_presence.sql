-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 08 avr. 2025 à 10:08
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

--
-- Déchargement des données de la table `absence_justifiee`
--

INSERT INTO `absence_justifiee` (`id`, `agent_id`, `date_debut`, `date_fin`, `description`) VALUES
(1, 3, '2025-04-02', '2025-04-03', 'Congé maladie'),
(2, 5, '2025-04-03', '2025-04-05', 'Formation professionnelle'),
(3, 7, '2025-04-01', '2025-04-04', 'Congé familial'),
(4, 9, '2025-04-05', '2025-04-10', 'Congé annuel'),
(5, 10, '2025-04-02', '2025-04-02', 'Rendez-vous médical');

-- --------------------------------------------------------

--
-- Structure de la table `agent`
--

CREATE TABLE `agent` (
  `id` int(11) NOT NULL,
  `matricule` varchar(10) NOT NULL,
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
(1, 'RH001', 'Dupont', 'Jean', 'jean.dupont@exemple.com', '0612345678', 'photos/photo.jpg', 1),
(2, 'RH002', 'Martin', 'Marie', 'marie.martin@exemple.com', '0623456789', 'photos/photo.jpg', 1),
(3, 'RH003', 'Durand', 'Pierre', 'pierre.durand@exemple.com', '0634567890', 'photos/photo.jpg', 2),
(4, 'CP001', 'Petit', 'Sophie', 'sophie.petit@exemple.com', '0645678901', 'photos/photo.jpg', 3),
(5, 'IT001', 'Leroy', 'Thomas', 'thomas.leroy@exemple.com', '0656789012', 'photos/photo.jpg', 4),
(6, 'IT002', 'Moreau', 'Julie', 'julie.moreau@exemple.com', '0667890123', 'photos/photo.jpg', 5),
(7, 'MK001', 'Dubois', 'Lucas', 'lucas.dubois@exemple.com', '0678901234', 'photos/photo.jpg', 6),
(8, 'DR001', 'Robert', 'Emma', 'emma.robert@exemple.com', '0689012345', 'photos/photo.jpg', 7),
(9, 'IT003', 'Richard', 'Paul', NULL, '0690123456', 'photos/photo.jpg', 4),
(10, 'IT004', 'Simon', 'Laura', 'laura.simon@exemple.com', '0701234567', 'photos/photo.jpg', 5);

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
(1, 'Bureau RH 1', 1),
(2, 'Bureau RH 2', 1),
(3, 'Bureau Comptabilité', 2),
(4, 'Bureau Informatique A', 3),
(5, 'Bureau Informatique B', 3),
(6, 'Bureau Marketing', 4),
(7, 'Bureau Direction', 5);

-- --------------------------------------------------------

--
-- Structure de la table `login`
--

CREATE TABLE `login` (
  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `login`
--

INSERT INTO `login` (`id`, `agent_id`, `mot_de_passe`) VALUES
(1, 1, 'password123'),
(2, 5, 'secure456'),
(3, 8, 'admin789');

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
(1, 1, '2025-04-01', '08:30:00', 'arrivée'),
(2, 1, '2025-04-01', '17:15:00', 'depart'),
(3, 2, '2025-04-01', '09:00:00', 'arrivée'),
(4, 2, '2025-04-01', '18:00:00', 'depart'),
(5, 3, '2025-04-01', '08:45:00', 'arrivée'),
(6, 3, '2025-04-01', '16:45:00', 'depart'),
(7, 4, '2025-04-01', '08:15:00', 'arrivée'),
(8, 4, '2025-04-01', '17:00:00', 'depart'),
(9, 5, '2025-04-01', '09:30:00', 'arrivée'),
(10, 5, '2025-04-01', '18:30:00', 'depart'),
(11, 1, '2025-04-02', '08:25:00', 'arrivée'),
(12, 1, '2025-04-02', '17:10:00', 'depart'),
(13, 2, '2025-04-02', '08:55:00', 'arrivée'),
(14, 2, '2025-04-02', '17:50:00', 'depart'),
(15, 4, '2025-04-02', '08:20:00', 'arrivée'),
(16, 4, '2025-04-02', '17:05:00', 'depart'),
(17, 6, '2025-04-02', '08:00:00', 'arrivée'),
(18, 6, '2025-04-02', '16:30:00', 'depart'),
(19, 8, '2025-04-02', '09:15:00', 'arrivée'),
(20, 8, '2025-04-02', '18:15:00', 'depart'),
(21, 4, '2025-04-06', '16:00:50', 'arrivée'),
(22, 1, '2025-04-06', '16:52:27', 'arrivée'),
(23, 1, '2025-04-06', '16:56:05', 'depart'),
(24, 4, '2025-04-06', '16:57:18', 'depart'),
(25, 4, '2025-04-07', '09:26:46', 'arrivée'),
(26, 4, '2025-04-07', '09:27:42', 'depart'),
(27, 3, '2025-04-07', '09:48:44', 'arrivée'),
(28, 3, '2025-04-07', '09:49:17', 'depart'),
(29, 2, '2025-04-07', '09:57:49', 'arrivée'),
(30, 2, '2025-04-07', '09:58:36', 'depart'),
(31, 5, '2025-04-07', '12:41:57', 'arrivée'),
(32, 5, '2025-04-07', '12:42:14', 'depart'),
(33, 7, '2025-04-07', '14:28:07', 'arrivée'),
(34, 7, '2025-04-07', '14:28:36', 'depart');

-- --------------------------------------------------------

--
-- Structure de la table `service`
--

CREATE TABLE `service` (
  `id` int(11) NOT NULL,
  `libele` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `service`
--

INSERT INTO `service` (`id`, `libele`) VALUES
(1, 'Ressources Humaines'),
(2, 'Comptabilité'),
(3, 'Informatique'),
(4, 'Marketing'),
(5, 'Direction');

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
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `bureau`
--
ALTER TABLE `bureau`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `login`
--
ALTER TABLE `login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `presence`
--
ALTER TABLE `presence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT pour la table `service`
--
ALTER TABLE `service`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `absence_justifiee`
--
ALTER TABLE `absence_justifiee`
  ADD CONSTRAINT `absence_justifiee_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `agent` (`id`);

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
  ADD CONSTRAINT `login_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `agent` (`id`);

--
-- Contraintes pour la table `presence`
--
ALTER TABLE `presence`
  ADD CONSTRAINT `presence_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `agent` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
