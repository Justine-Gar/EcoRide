CREATE DATABASE `ecoride`;

use ecoride;

--  Table roles --
CREATE TABLE `roles` (
  id_role INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  name_role VARCHAR(50) NOT NULL
);

--  Table utilisateurs  --
CREATE TABLE `users` (
  id_user INT NOT NULL PRIMARY KEY AUTO_INCREMENT ,
  name VARCHAR(50) NOT NULL,
  firstname VARCHAR(50) NOT NULL,
  email VARCHAR(180) NOT NULL,
  password VARCHAR(255) NOT NULL,
  phone_number VARCHAR(20) NOT NULL,
  profil_picture VARCHAR(255) DEFAULT NULL,
  credits INT DEFAULT 100,
  rating DECIMAL(3, 1) DEFAULT NULL
);

--  Table liaison user_role --
CREATE TABLE `user_roles` (
  id_user INT NOT NULL,
  id_role INT NOT NULL,
  PRIMARY KEY (id_user, id_role)
);

--  Table voitures  --
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

--  Table covoiturages  --
CREATE TABLE `carpools` (
  id_carpool INT NOT NULL PRIMARY KEY AUTO_INCREMENT ,
  id_user INT NOT NULL,
  date_start DATE NOT NULL,
  hour_start TIME NOT NULL,
  location_start VARCHAR(255) NOT NULL,
  date_reach DATE NOT NULL,
  hour_reach TIME NOT NULL,
  location_reach VARCHAR(255) NOT NULL,
  nbr_places INT NOT NULL,
  credits INT NOT NULL,
  statut VARCHAR(20) NOT NULL DEFAULT 'waiting',
  preferences TEXT,
  lat_start DECIMAL(10,8) DEFAULT NULL,
  lng_start DECIMAL(11,8) DEFAULT NULL,
  lat_reach DECIMAL(10,8) DEFAULT NULL,
  lng_reach DECIMAL(11,8) DEFAULT NULL
);

-- Table liaison carpool_users  --
CREATE TABLE `carpool_users` (
  id_carpool INT NOT NULL,
  id_user INT NOT NULL,
  PRIMARY KEY (id_carpool, id_user)
);

--  Table avis  --
CREATE TABLE `reviews` (
  id_review INT NOT NULL PRIMARY KEY AUTO_INCREMENT,  
  id_user INT NOT NULL,
  id_sender INT NOT NULL,
  id_recipient INT NOT NULL,
  id_carpool INT NOT NULL,
  comment TEXT NOT NULL,
  note DECIMAL(3,1) NOT NULL,
  statut VARCHAR(20) NOT NULL DEFAULT 'attente'
);

--  Table préférences_types --
CREATE TABLE `preferences_types` (
  id_preference_types INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL,
  is_systeme BOOLEAN NOT NULL DEFAULT FALSE,
  id_user INT NULL
);

--  Table user_preferences  --
CREATE TABLE `user_preferences` (
  id_user_preference INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  id_user INT NOT NULL,
  id_preference_types INT NOT NULL,
  choose_value VARCHAR(50) NOT NULL
);