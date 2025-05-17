# Projet EcoRide :green_car:

___

## Description
EcoRide est une application web de covoiturage avec système de géolocalisation et de réservation. 
Elle permet aux utilisateurs de proposer des trajets ou de trouver des covoiturages existants, facilitant ainsi une mobilité plus écologique et économique.

##### **Objectif du projet:**
- Developper une plateforme intuitive et conviviale pour facilité le covoiturage
- Réduire l'empreinte carbone en favorisant le partage de trajets
- Créer une communoté basé sur la confiance grâce à un système d'évalusation
- Fournir une interface de recherche pour trouvé facilement des trajets
- Mettre en place un système de crédits pour la gestion des paiements


___


## Technologie utilisées

##### **Frontend**
- Bootstrap 5 (Framework CSS)
- Twig (Moteur de templates)
- Javascript
- Leaflet (Bibliothèque JS pour carte intéractive)

##### **Backend**
- PHP 8.2
- Symfony 6.4 (Framework PHP)
- Doctrine ORM (ORM pour l'accès au données)
- Nginx (Serveur web)

##### **Base de données**
- MySQL 8.0 5 (Système de gestion de base de donnée relationnelle)

##### **Service ecternes**
- OpenStreetMap (Données cartographique)
- Nominatim (Service géocodage)

## Prérequis techniques

##### **Envirronnement de developpement**
- Docker (Conteneurisation)
- Docker Compose (Orchestration des conteneurs)
- Git (Gestion de versions)

##### **Dépendances**
- Composer (Gestion de dépendances PHP)


___


## Installation
1. Cloner le projet
```bash
  git clone [URL_DU_REPO]
  cd Projet_EcoRide
```
2. Lancer Docker
```bash
  # Construire les images
  docker-compose build
  # Démarrer les conteneurs
  docker-compose up -d
```
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

  Note importante: Le fichier ecoride_tables.sql contient déjà l'instruction CREATE DATABASE ecoride;, donc il n'est pas nécessaire d'exécuter la commande Symfony doctrine:database:create.
```


___


## Guide d'utilisation

##### **Accès à l'application**
-L'application est accessible à l'adresse : http://localhost:8080

##### **Fonctionnalités principal**
- Inscription/Connexion
- Recherche de trajets
- Projosition de trajet
- Gestion de profil
- Système de paiement
- Système d'avis

##### **Structure du projet**
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

##### **API et Services**
- **API de Geolocalisation**: Utilisateion d'OpenStretMap
- **Service d'authentification**: Gestion des utilisateurs et des rôles
- **Service de notification**: Alertes pour les réservation et annulations


___


## Développement futurs
Voici les amélioration prévue pour les prochaines versions du projet:

**Teste automatisés**
- Teste unitaire avec PHPUnit
- Mise en place de tests fonctionnels pour les principales fonctionnalités

**Autres amélioration prévues**
- Ajustement de la fonctionnalité de recherche de covoiturage
- Webpack Bundler ajouter pour les script js
- Amélioration de l'expérience User en notification 


___

*Projet développé dans le cadre d'un examen de fin d'étude*