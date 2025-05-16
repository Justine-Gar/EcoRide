USE ecoride;

-- Insert des rôles --
INSERT INTO `roles` (`name_role`) VALUES 
('Administrateur'),
('Staff'),
('Conducteur'),
('Passager');

--  Création d'un administrateur par défaut (mdp: password) --
--  Création d'un staff par défaut (mdp: password)  --
--  Création des utilisateurs par defaut (mdp: password)  --
-- Note: Le hash devrait être généré par Symfony en production  --
INSERT INTO `users` (`name`, `firstname`, `email`, `password`, `phone_number`, `profil_picture`, `credits`, `rating`) VALUES
('Admin', 'System', 'admin@ecoride.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0600000000', NULL, 100, NULL),
('Baptiste', 'Novel', 'staff@ecoride.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0600000001', NULL, 100, NULL),
('Dupont', 'Jean', 'jean.dupont@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0611111111', NULL, 45, 4.5),
('Martin', 'Sophie', 'sophie.martin@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0622222218', NULL, 40, 4.5),
('Garcia', 'Lucas', 'lucas.garcia@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0633333333', NULL, 20, 4),
('Dubois', 'Marie', 'marie.dubois@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0644444444', NULL, 20, 4.5),
('Karpin', 'Robert', 'robet.karpin@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0633333333', NULL, 20, 4),
('douk', 'madi', 'madi.douk@gmail.com', '$2y$13$orlX9EQuZNOvHxBBCFknQ.yUEs35KXGvavn4KTA8/WnQAxiUW1u6i', '0100000001', 'images-67d854e8bfe37.jpg', 20, 0),
('Bambina', 'Laura', 'laura.bambina@gmail.com', '$2y$13$fcWRrYJ9b3UweUdBFo4hbeuSoKK2Xz5AkL4MkbQVCBGv8nVotarR2', '0000007887', NULL, 24, 0);


--  Attribution des rôle  s--
INSERT INTO `user_roles` (`id_user`, `id_role`) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 3), 
(5, 3),
(6, 4),
(7, 4),
(8, 4),
(9, 4);

-- Insert des préférences systemes  --
INSERT INTO `preferences_types` (`name`, `is_systeme`, `id_user`) VALUES
('non_fumeur', TRUE, NULL),
('animaux de compagnie', TRUE, NULL),
('discussion', TRUE, NULL),
('silence', TRUE, NULL);



-- Insert de cars --
INSERT INTO `cars` (`id_user`, `marque`, `modele`, `color`, `energie`, `nbr_places`, `license_plate`, `first_registration`) VALUES
(3, 'Renault', 'Clio', 'Rouge', 'essence', 4, 'AB-123-CD', '2020-03-15'),
(3, 'Telsa', 'Model 3', 'Blanc', 'electrique', 5, 'IJ-789-KL', '2021-11-22'),
(8, 'Peugeot', '308', 'gris', 'diesel', 4, 'EF-456-GH', '2019-07-10'),
(4, 'Toyota', 'Prius', 'Blanc', 'hybride', 4, 'HI-789-JK', '2021-11-05'),
(5, 'Volkswagen', 'Golf', 'Noir', 'diesel', 5, 'TU-678-VW', '2021-05-18'),
(5, 'Hyundai', 'Kona', 'Noir', 'electrique', 5, 'XY-901-ZA', '2022-03-10'),
(7, 'Peugeot', 'teste', 'teste', 'essence', 3, 'kj-0505-et', '2025-05-06');

--Insert user preferences --
INSERT INTO `user_preferences` (`id_user`, `id_preference_types`, `choose_value`) VALUES
(5, 5, 'Rock'),
(8, 1, 'oui');

-- Création de carpools --
INSERT INTO `carpools` (`id_user`, `date_start`, `location_start`, `hour_start`, `date_reach`, `location_reach`, `hour_reach`, `statut`, `credits`, `nbr_places`, `preferences`, `lat_start`, `lng_start`, `lat_reach`, `lng_reach`) VALUES
(3, '2025-03-18', 'Paris', '07:00:00', '2025-03-18', 'Nantes', '10:30:00', 'terminé', 22, 3, NULL, 48.86, 2.35, 47.22, -1.55),
(3, '2025-02-08', 'Paris', '07:15:00', '2025-02-08', 'Lille', '09:45:00', 'annulé', 18, 4, NULL, 48.86, 2.35, 50.63, 3.07),
(3, '2025-01-10', 'Paris', '14:30:00', '2025-01-10', 'Orléans', '16:15:00', 'terminé', 12, 3, NULL, 48.86, 2.35, 47.90, 1.90),
(4, '2025-04-14', 'Paris', '18:00:00', '2025-04-14', 'Nantes', '20:00:00', 'annulé', 10, 4, NULL, 48.85, 2.35, 47.22, -1.55),
(4, '2025-04-21', 'Paris', '17:33:00', '2025-04-21', 'Lyon', '19:33:00', 'terminé', 20, 4, NULL, 48.85, 2.35, 45.76, 4.83),
(4, '2025-04-21', 'Paris', '10:00:00', '2025-04-21', 'Nantes', '12:00:00', 'terminé', 20, 4, NULL, 48.85, 2.35, 47.22, -1.55),
(5, '2025-04-14', 'Paris', '21:00:00', '2025-04-14', 'Lyon', '23:00:00', 'terminé', 20, 4, NULL, 48.85, 2.35, 45.76, 4.83),
(5, '2025-04-14', 'Paris', '21:00:00', '2025-04-14', 'Lyon', '23:00:00', 'terminé', 20, 4, NULL, 48.85, 2.35, 45.76, 4.83),
(5, '2025-04-29', 'Paris', '17:00:00', '2025-04-29', 'Nantes', '19:00:00', 'terminé', 15, 4, NULL, 48.86, 2.32, 47.22, -1.55),
(5, '2025-03-18', 'Paris', '17:30:00', '2025-03-18', 'Nantes', '21:00:00', 'annulé', 24, 4, NULL, 48.86, 2.35, 47.22, -1.55),
(7, '2025-02-15', 'Paris', '9:30:00', '2025-02-15', 'Nantes', '10:00:00', 'terminé', 24, 4, NULL, 48.86, 2.35, 47.22, -1.55),
(8, '2025-03-21', 'Paris', '21:00:00', '2025-03-21', 'Lyon', '23:00:00', 'terminé', 20, 4, NULL, 48.85, 2.35, 45.76, 4.83),
(3, '2025-05-21', 'Paris', '21:00:00', '2025-05-21', 'Blois', '23:00:00', 'attente', 10, 4, NULL, 0.00, 0.00, 0.00, 0.00),
(4, '2025-05-15', 'Paris', '17:00:00', '2025-05-15', 'Blois', '19:00:00', 'attente', 5, 4, NULL, 0.00, 0.00, 0.00, 0.00),
(5, '2025-05-15', 'Paris', '15:00:00', '2025-05-15', 'Blois', '17:00:00', 'attente', 8, 4, NULL, 0.00, 0.00, 0.00, 0.00);

--
-- Création de carpools avec passager --
INSERT INTO `carpool_users` (`id_carpool`, `id_user`) VALUES
(13, 6),
(14, 7),
(14, 8),
(15, 9);

-- Création d'avis pour les utilisataires --
INSERT INTO `reviews` (`id_user`, `id_sender`, `id_recipient`, `id_carpool`, `comment`, `note`, `statut`) VALUES
(3, 6, 3, 1, 'Voyage très agréable, conducteur ponctuel et sympathique. La voiture était propre et confortable.', 4.5, 'publié'),
(3, 7, 3, 3, 'Conducteur professionnel et aimable. Trajet très confortable, je recommande vivement !', 5.0, 'publié'),
(4, 8, 4, 5, 'Super trajet ! Sophie est une conductrice prudente et la conversation était très intéressante.', 5.0, 'publié'),
(4, 7, 4, 6, 'Bonne ambiance et conduite sécuritaire. Le trajet est passé très vite !', 4.5, 'publié'),
(5, 9, 5, 7, 'Trajet correct mais la voiture n\'était pas très propre. Le conducteur était néanmoins agréable.', 3.5, 'publié'),
(5, 6, 5, 8, 'Un peu de retard au départ mais bonne communication. Conduite prudente.', 4.0, 'publié'),
(5, 8, 5, 9, 'Plus de 30 minutes de retard sans prévenir et aucune excuse, de plus la voiture était dan sun état pas possible.', 2.0, 'signalé'),
(5, 6, 5, 9, 'Voiture dans un état déplorable, déchets partout et odeur de cigarette très forte.', 2.0, 'signalé'),
(8, 4, 8, 12, 'Conducteur très irrespectueux qui a tenu des propos déplacés pendant tout le trajet.', 1.0, 'danger'),
(7, 4, 7, 11, 'Bon conducteur !', 4.0, 'attente'),
(7, 9, 7, 11, 'Monsieur Dupont a été un bon et très prudents conducteur , très avenant de notre bien être.', 4.5, 'attente');
