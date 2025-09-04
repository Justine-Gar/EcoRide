<div align="center">
  <h1>🌱 Projet EcoRide :car:</h1>
</div>

## Description
EcoRide est une application web de covoiturage avec système de géolocalisation et de réservation. 
Elle permet aux utilisateurs de proposer des trajets ou de trouver des covoiturages existants, facilitant ainsi une mobilité plus écologique et économique.

#####  **Objectif du projet :**
- Developper une plateforme intuitive et conviviale pour facilité le covoiturage
- Réduire l'empreinte carbone en favorisant le partage de trajets
- Créer une communoté basé sur la confiance grâce à un système d'évalusation
- Fournir une interface de recherche pour trouvé facilement des trajets
- Mettre en place un système de crédits pour la gestion des paiements

___


## Prérequis système

### Logiciels requis
- **Docker** : version 20.10.0 ou supérieure
  - Vérifiez avec `docker --version`
  - [Instructions d'installation](https://docs.docker.com/get-docker/)

- **Docker Compose** : version 2.0.0 ou supérieure (inclus avec Docker Desktop pour Windows/Mac)
  - Vérifiez avec `docker-compose --version`
  - [Instructions d'installation](https://docs.docker.com/compose/install/)

- **Git** : version 2.25.0 ou supérieure
  - Vérifiez avec `git --version`
  - [Instructions d'installation](https://git-scm.com/downloads)

### Configuration réseau
- Les ports suivants doivent être disponibles sur votre machine :
  - 8080 : Interface web de l'application
  - 3306 : Base de données MySQL
  - 9000 : PHP-FPM

### Accès aux services externes
- Connexion Internet requise pour :
  - Téléchargement des dépendances Composer
  - Accès aux tuiles et API d'OpenStreetMap

> [!NOTE]
> <ins>Note</ins> : Si certains de ces ports sont déjà utilisés sur votre système, vous devrez modifier le fichier `docker-compose.yml` avant de démarrer les conteneurs.


___


## Technologie utilisées

#### ***Frontend***
- Bootstrap 5 (Framework CSS)
- Twig (Moteur de templates)
- Javascript
- Leaflet (Bibliothèque JS pour carte intéractive)

#### ***Backend***
- PHP 8.2
- Symfony 6.4 (Framework PHP)
- Doctrine ORM (ORM pour l'accès au données)
- Nginx (Serveur web)

#### ***Base de données***
- MySQL 8.0 5 (Système de gestion de base de donnée relationnelle)

#### ***Service externes***
- OpenStreetMap (Données cartographique)

___


## Installation
1. Cloner le projet
```bash
  git clone https://github.com/Justine-Gar/EcoRide.git
  cd Projet_EcoRide
```
2. Lancer Docker
```bash
  # Construire les images
  docker-compose build
  # Démarrer les conteneurs
  docker-compose up -d
```

<details>
  Voici un extrait du fichier docker-compose.yml pour référence :<summary>docker-compose.yml</summary>
  
    version: '3.8'

    services:
      # Serveur web NGINX
      nginx:
        image: nginx:1.21-alpine
        container_name: ecoride_nginx
        ports:
          - "8080:80"
        volumes:
          - ./public:/var/www/html/public:ro
          - ./docker/nginx/conf.d:/etc/nginx/conf.d
        depends_on:
          - php
        networks:
          - ecoride_network

      # Service PHP pour Symfony
      php:
        build:
          context: ./docker/php
        container_name: ecoride_php
        volumes:
          - .:/var/www/html
        environment:
          - APP_ENV=dev
          - DATABASE_URL=mysql://root:root@database:3306/ecoride
        depends_on:
          - database
        networks:
          - ecoride_network

      # Base de données MySQL
      database:
        image: mysql:8.0
        container_name: ecoride_database
        ports:
          - "3306:3306"
        environment:
          - MYSQL_ROOT_PASSWORD=root
          - MYSQL_DATABASE=ecoride
        volumes:
          - ecoride_database_data:/var/lib/mysql
        networks:
          - ecoride_network

    # [Suite du fichier omis pour brevité]
    
</details>

3. Installer les dépendances
```bash
  # Installation des dépendances Symfony
  docker-compose exec php composer install
```
4. Installer la base de données
```bash
  #Importer les fichiers SQL dans cet ordre précis
  # 1. Création de la base de données et des tables
  docker-compose exec -T database mysql -uroot -proot < sql/ecoride_tables.sql

  # 2. Ajout des contraintes (clés étrangères)
  docker-compose exec -T database mysql -uroot -proot < sql/ecoride_constraints.sql

  # 3. Insertion des données initiales
  docker-compose exec -T database mysql -uroot -proot < sql/ecoride_insert.sql
```
> [!NOTE]
> <ins>Note importante</ins>: Le fichier ecoride_tables.sql contient déjà l'instruction CREATE DATABASE ecoride;, donc il n'est pas nécessaire d'exécuter la commande Symfony doctrine:database:create.

5. Vérifier l'installation
```bash
  #Vérifier que tous les conteneurs sont en cours d'exécution
  docker-compose ps
  #Vérifier que le site est accessible
  curl http://localhost:8080
```
___


## Guide d'utilisation

#### ***Accès à l'application***
- L'application est accessible à l'adresse : [http://localhost:8080](http://localhost:8080)

#### ***Fonctionnalités principal***
- **Inscription/Connexion** : Créez votre compte ou connectez-vous
- **Recherche de trajets** : Utilisez la recherche pour trouver des trajets
- **Proposition de trajet** : Proposez vos propres trajets en tant que conducteur
- **Gestion de profil** : Gérez vos informations personnelles et vos préférences 
- **Système de paiement** : Gestion des crédits pour les trajets
- **Système d'avis** : Evaluez les conducteurs après votre trajet

#### ***API et Services***
- <ins>API de Geolocalisation</ins>: Utilisateion d'OpenStretMap
- <ins>Service d'authentification</ins>: Gestion des utilisateurs et des rôles
- <ins>Service de notification</ins>: Alertes pour les réservation et annulations

#### ***Structure du projet***
```bash
Projet_EcoRide/
├── config/               # Configuration Symfony
├── docker/               # Configuration Docker
├── public/               # Fichiers publics (index.php)
│    └── asset/           # Fichiers source (JS, CSS, images)
│         ├── js/         # Scripts JavaScript
│         └── css/        # Fichiers CSS
├── src/                  # Code source PHP
│    ├── Controller/      # Contrôleurs de l'application
│    ├── Repository/      # Repositories pour l'accès aux données
│    ├── Entity/          # Formulaires
│    └── Form/            # Entités Doctrine
├── sql/                  # Scripts SQL pour l'initialisation de la base de données
├── templates/            # Templates Twig
├── docker-compose.yml    # Configuration Docker Compose
└── README.mdr            # Documentation du projet
```

___


## Développement futurs
Voici les amélioration prévue pour les prochaines versions du projet:

🧪**Teste automatisés**
- Teste unitaire avec PHPUnit
- Mise en place de tests fonctionnels pour les principales fonctionnalités

🚀**Autres amélioration prévues**
- Ajustement de la fonctionnalité de recherche de covoiturage
- [x] Ajout d'une base de données "non relationnelle" : NoSQL
- Webpack Bundler ajouter pour les script js
- Amélioration de l'expérience User en notification 


___

<div align="center">
  *Projet développé dans le cadre d'un examen de fin d'étude*
</div>
