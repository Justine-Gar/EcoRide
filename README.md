# Projet EcoRide

## Description
Creation d'une application web de covoiturage avec système de géolocalisation et de réservation.

## Installation

### Prérequis
- Docker Desktop
- Docker Compose
- Git

### Installation
1. Cloner le projet
  ```bash
  git clone [URL_DU_REPO]
  cd Projet_EcoRide

2. Lancer Docker
  ```bash
  # Construire les images
  docker-compose build
  # Démarrer les conteneurs
  docker-compose up -d

3. Installer les dépendances
  ```bash
  # Installation des dépendances Symfony
  docker-compose exec php composer install

4. Créer la base de données
  ```bash
  # Importer la base de données existante
  docker-compose exec -T database mysql -uroot -proot ecoride < sql/ecoride.sql
  # Créer la base de données
  docker-compose exec php php bin/console doctrine:database:create

### Usage
- Le site est accessible sur : http://localhost:8080
- L'application permet de :
    Consulter les trajets disponibles
    Créer un compte utilisateur
    Proposer un trajet
    Rechercher un trajet via la carte interactive

### Technologie utilisées
- Symfony 6.4 (Framework PHP)
- Docker (Conteneurisation)
- MySQL 8.0 (Base de données)
- OpenStreetMap (Cartographie)
- Leaflet (Bibliothèque JavaScript pour cartes interactives)
- Bootstrap 5 (Framework CSS)

#### Auteur
Justine Garandel