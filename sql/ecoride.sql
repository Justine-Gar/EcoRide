-- Création de toutes les tables
CREATE TABLE `roles` (
    id_role INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name_role VARCHAR(50) NOT NULL
);

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

CREATE TABLE `user_roles` (
    id_user INT NOT NULL,
    id_role INT NOT NULL
);

CREATE TABLE `preference_types` (
    id_preference_types INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    is_systeme BOOLEAN NOT NULL
);

CREATE TABLE `user_preferences` (
    id_user_preferences INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    id_user INT NOT NULL,
    id_preference_type INT NOT NULL,
    choose_value VARCHAR(50) NOT NULL
);

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

CREATE TABLE `carpool_users` (
    id_carpool INT NOT NULL,
    id_user INT NOT NULL 
);

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
('Passager'),

INSERT INTO `users` (`id_user`, `name`, `firstname`, `email`, `password`, `phone_number`, `profil_picture`, `credits`) VALUES
(1, 'Admin', 'System', 'admin@ecoride.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0600000000', NULL, 100),
(2, 'Support', 'Team', 'staff@ecoride.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0600000001', NULL, 100),
(3, 'Dupont', 'Jean', 'jean.dupont@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0611111111', NULL, 45),
(4, 'Martin', 'Sophie', 'sophie.martin@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0622222218', 'telechargement-67b569ed96a36.bmp', 40),
(5, 'Garcia', 'Lucas', 'lucas.garcia@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0633333333', NULL, 20),

INSERT INTO `user_roles` (`id_user`, `ìd_role`) VALUES
(1,1),
(2,2),
(3,3),
(4,4),
(5,4);
