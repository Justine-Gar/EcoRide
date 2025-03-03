-- Création de toutes les tables

--Table des roles
CREATE TABLE `roles` (
    id_role INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name_role VARCHAR(50) NOT NULL
);
--Tbale des utilisateurs
CREATE TABLE `users` (
    id_user INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    firstname VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone_number VARCHAR(50) NULL,
    profil_picture TEXT NULL,
    credits INT NOT NULL
);

--Table de Liaison role et user
CREATE TABLE `user_roles` (
    id_user INT NOT NULL,
    id_role INT NOT NULL
);

--Table pour les préférence créer par le systeme
CREATE TABLE `preference_types` (
    id_preference_types INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    is_systeme BOOLEAN NOT NULL
);

--Table pour les Préférence créer par utilisateur
CREATE TABLE `user_preferences` (
    id_user_preferences INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    id_user INT NOT NULL,
    id_preference_type INT NOT NULL,
    choose_value VARCHAR(50) NOT NULL
);

--Table pour les voiture des utilisateurs
CREATE TABLE `cars` (
    id_cars INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    marque VARCHAR(50) NOT NULL,
    modele VARCHAR(50) NOT NULL,
    color VARCHAR(50) NOT NULL,
    energie VARCHAR(50) NOT NULL,
    nbr_places INT NOT NULL,
    license_plate VARCHAR(50) NOT NULL,
    first_registration DATE NOT NULL,
    id_user INT NOT NULL
);

--Table pour les trajet de covoiturage
CREATE TABLE `carpools` (
    id_carpool INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    id_user INT NOT NULL,
    date_start DATE NOT NULL,
    location_start VARCHAR(50) NOT NULL,
    hour_start TIME NOT NULL,
    date_reach DATE NOT NULL,
    location_reach VARCHAR(50) NOT NULL,
    hour_reach TIME NOT NULL,
    statut VARCHAR(50) NOT NULL,
    credits INT NOT NULL,
    nbr_places INT NOT NULL,
    lat_start DECIMAL(15, 2) NULL,
    lng_start DECIMAL(15, 2) NULL,
    lat_reach DECIMAL(15, 2) NULL,
    lng_reach DECIMAL(15, 2) NULL
);

--Table Liaison pour la participation covoit-users
CREATE TABLE `carpool_users` (
    id_carpool INT NOT NULL,
    id_user INT NOT NULL 
);

--Table pour les avis
CREATE TABLE `reviews` (
    id_review INT NOT NULL PRIMARY KEY AUTO_INCREMENT,  
    comment VARCHAR(250) NOT NULL,
    note DECIMAL(5, 1) NOT NULL,
    statut VARCHAR(50) NOT NULL,
    id_sender INT NOT NULL,
    id_recipient INT NOT NULL
);

-- Création des clés étrangères
ALTER TABLE `user_roles`
  ADD CONSTRAINT fk_user_roles_user FOREIGN KEY (id_user) REFERENCES users (id_user),
  ADD CONSTRAINT fk_user_roles_role FOREIGN KEY (id_role) REFERENCES roles (id_role);

ALTER TABLE `user_preferences`
  ADD CONSTRAINT fk_user_preferences_user FOREIGN KEY (id_user) REFERENCES users (id_user),
  ADD CONSTRAINT fk_user_preferences_type FOREIGN KEY (id_preference_type) REFERENCES preference_types (id_preference_types);

ALTER TABLE `cars`
  ADD CONSTRAINT fk_cars_user FOREIGN KEY (id_user) REFERENCES users (id_user);

ALTER TABLE `carpools`
  ADD CONSTRAINT fk_carpools_user FOREIGN KEY (id_user) REFERENCES users (id_user);

ALTER TABLE `carpool_users`
  ADD CONSTRAINT fk_carpool_users_carpool FOREIGN KEY (id_carpool) REFERENCES carpools (id_carpool),
  ADD CONSTRAINT fk_carpool_users_user FOREIGN KEY (id_user) REFERENCES users (id_user);

ALTER TABLE `reviews`
  ADD CONSTRAINT fk_sender_user FOREIGN KEY (id_sender) REFERENCES users (id_user),
  ADD CONSTRAINT fk_recipient_user FOREIGN KEY (id_recipient) REFERENCES users (id_user);


-- Insertion des données de base

INSERT INTO `roles` (`name_role`) VALUES 
('Administrateur'),
('Staff'),
('Conducteur'),
('Passager');

INSERT INTO `users` (`name`, `firstname`, `email`, `password`, `phone_number`, `profil_picture`, `credits`) VALUES
('Admin', 'System', 'admin@ecoride.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0600000000', NULL, 100),
('Support', 'Team', 'staff@ecoride.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0600000001', NULL, 100),
('Dupont', 'Jean', 'jean.dupont@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0611111111', NULL, 45),
('Martin', 'Sophie', 'sophie.martin@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0622222218', 'telechargement-67b569ed96a36.bmp', 40),
('Garcia', 'Lucas', 'lucas.garcia@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0633333333', NULL, 20),
('Dubois', 'Marie', 'marie.dubois@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0644444444', NULL, 20),
('Karpin', 'Robert', 'robet.karpin@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0633333333', NULL, 20);

INSERT INTO `user_roles` (`id_user`, `id_role`) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 3), 
(5, 3),
(6, 4),
(7, 4);

INSERT INTO `carpools` (`id_user`, `date_start`, `location_start`, `hour_start`, `date_reach`, `location_reach`, `hour_reach`, `statut`, `credits`, `nbr_places`, `lat_start`, `lng_start`, `lat_reach`, `lng_reach`) VALUES

(3, '2023-02-28', 'Paris', '07:00:00', '2023-02-28', 'Nantes', '10:30:00', 'actif', 22, 3, 48.86, 2.35, 47.22, -1.55),
(4, '2023-02-28', 'Paris', '12:15:00', '2023-02-28', 'Nantes', '15:45:00', 'actif', 22, 2, 48.86, 2.35, 47.22, -1.55),
(5, '2023-02-28', 'Paris', '17:30:00', '2023-02-28', 'Nantes', '21:00:00', 'actif', 24, 4, 48.86, 2.35, 47.22, -1.55),

(3, '2023-02-15', 'Paris', '08:00:00', '2023-02-15', 'Lyon', '12:30:00', 'actif', 25, 3, 48.86, 2.35, 45.76, 4.83),
(3, '2023-02-20', 'Lyon', '16:00:00', '2023-02-20', 'Paris', '20:30:00', 'annulé', 25, 3, 45.76, 4.83, 48.86, 2.35),
(3, '2023-02-28', 'Paris', '07:15:00', '2023-02-28', 'Lille', '09:45:00', 'terminé', 18, 4, 48.86, 2.35, 50.63, 3.07),
(3, '2023-01-10', 'Paris', '14:30:00', '2023-01-10', 'Orléans', '16:15:00', 'terminé', 12, 3, 48.86, 2.35, 47.90, 1.90),
(3, '2023-01-25', 'Paris', '09:00:00', '2023-01-25', 'Reims', '10:45:00', 'terminé', 15, 2, 48.86, 2.35, 49.26, 4.03),

(4, '2023-02-16', 'Marseille', '10:00:00', '2023-02-16', 'Nice', '12:15:00', 'actif', 15, 2, 43.30, 5.37, 43.70, 7.25),
(4, '2023-03-01', 'Marseille', '07:00:00', '2023-03-01', 'Montpellier', '09:30:00', 'terminé', 12, 3, 43.30, 5.37, 43.61, 3.87),
(4, '2023-01-12', 'Marseille', '11:30:00', '2023-01-12', 'Toulon', '12:45:00', 'terminé', 10, 4, 43.30, 5.37, 43.12, 5.93),
(4, '2023-01-28', 'Marseille', '15:00:00', '2023-01-28', 'Aix-en-Provence', '15:45:00', 'terminé', 8, 3, 43.30, 5.37, 43.53, 5.45),

(5, '2023-02-17', 'Bordeaux', '14:00:00', '2023-02-17', 'Toulouse', '17:30:00', 'terminé', 20, 2, 44.84, -0.58, 43.60, 1.44),
(5, '2023-01-15', 'Bordeaux', '10:00:00', '2023-01-15', 'Arcachon', '11:15:00', 'terminé', 12, 3, 44.84, -0.58, 44.66, -1.17),
(5, '2023-01-30', 'Bordeaux', '17:30:00', '2023-01-30', 'Bergerac', '19:00:00', 'terminé', 14, 2, 44.84, -0.58, 44.85, 0.48),

(6, '2023-01-18', 'Strasbourg', '13:00:00', '2023-01-18', 'Colmar', '14:00:00', 'terminé', 8, 4, 48.58, 7.75, 48.08, 7.36),
(7, '2023-01-29', 'Strasbourg', '08:30:00', '2023-01-29', 'Metz', '10:15:00', 'terminé', 14, 3, 48.58, 7.75, 49.12, 6.17);

INSERT INTO `carpool_users` (`id_carpool`, `id_user`) VALUES
--Pour le covoit(Paris → Nantes, créé par Dupont)
(1, 4),  --Martin participe au covoiturage 1
(1, 5),  --Garcia participe au covoiturage 1
--Pour le covoit (Paris → Nantes, créé par Martin)
(2, 3),  --Dupont participe au covoiturage 2
(2, 6),  --Dubois participe au covoiturage 2
-- Pour le covoit (Paris → Lyon, créé par Dupont)
(4, 5),  --Garcia participe au covoiturage 4
(4, 6),  --Dubois participe au covoiturage 4
-- Pour le covoit(Marseille → Nice, créé par Martin)
(9, 3),  --Dupont participe au covoiturage 9
(9, 5);  --Garcia participe au covoiturage 9