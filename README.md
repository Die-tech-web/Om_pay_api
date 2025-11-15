# OM Pay - API de Paiement Mobile

[![Laravel](https://img.shields.io/badge/Laravel-10.10-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15-blue.svg)](https://postgresql.org)
[![Docker](https://img.shields.io/badge/Docker-Ready-blue.svg)](https://docker.com)

OM Pay est une API REST complète pour un système de paiement mobile développée avec Laravel 10. Elle offre des fonctionnalités avancées de gestion de comptes, transactions financières, authentification OAuth2, génération de QR codes, et documentation automatique Swagger.

## 📋 Table des Matières

- [Fonctionnalités](#-fonctionnalités)
- [Architecture](#-architecture)
- [Technologies Utilisées](#-technologies-utilisées)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Utilisation de l'API](#-utilisation-de-lapi)
- [Modèles de Données](#-modèles-de-données)
- [Services](#-services)
- [Événements et Observateurs](#-événements-et-observateurs)
- [Tests](#-tests)
- [Déploiement](#-déploiement)
- [Documentation API](#-documentation-api)
- [Sécurité](#-sécurité)
- [Contribuer](#-contribuer)

## 🚀 Fonctionnalités

### ✅ Authentification et Autorisation
- Authentification OAuth2 avec Laravel Passport
- Gestion des tokens d'accès et de rafraîchissement
- Inscription et connexion des utilisateurs
- Envoi automatique d'emails de bienvenue

### ✅ Gestion des Comptes
- Création automatique de comptes lors de l'inscription
- Génération de numéros de compte uniques
- Calcul automatique des soldes
- Génération de QR codes pour chaque compte
- Gestion des statuts de compte (actif, bloqué, fermé)

### ✅ Transactions Financières
- **Dépôt** : Créditer un compte
- **Retrait** : Débiter un compte
- **Transfert** : Transfert entre comptes clients
- **Paiement** : Paiement vers marchands
- Calcul automatique des frais de transaction
- Génération de reçus PDF
- Validation des soldes avant transactions

### ✅ API Endpoints
- `GET /api/mon-compte` - Informations du compte utilisateur
- `GET /api/comptes/{numero_compte}/solde` - Solde d'un compte spécifique
- `GET /api/comptes/{numero_compte}/transactions` - Toutes les transactions d'un compte
- `GET /api/comptes/{numero_compte}/mes-transactions` - Transactions sortantes uniquement
- `POST /api/comptes/{numero_compte}/transactions` - Effectuer une transaction
- `GET /api/mes-transactions` - Transactions de l'utilisateur connecté
- `POST /api/mes-transactions` - Nouvelle transaction
- `GET /api/mes-transactions/{reference}` - Détails d'une transaction

### ✅ Gestion des Marchands
- Base de données de marchands avec codes uniques
- Validation des paiements marchands
- Gestion des types de commerce

### ✅ Documentation Automatique
- Documentation Swagger/OpenAPI générée automatiquement
- Interface interactive pour tester les endpoints
- Exemples de requêtes et réponses
- Validation des schémas

## 🏗️ Architecture

### Structure MVC
```
app/
├── Http/Controllers/          # Contrôleurs API
│   ├── AuthController.php
│   ├── CompteController.php
│   ├── TransactionController.php
│   └── MarchandController.php
├── Models/                    # Modèles Eloquent
│   ├── User.php
│   ├── Compte.php
│   ├── Transaction.php
│   └── Marchand.php
├── Services/                  # Logique métier
│   ├── AuthService.php
│   ├── CompteService.php
│   ├── TransactionService.php
│   └── EmailService.php
├── Events/                    # Événements
│   └── TransactionCreated.php
├── Observers/                 # Observateurs de modèles
│   └── TransactionObserver.php
├── Http/Requests/             # Validation des requêtes
│   ├── CreateTransactionRequest.php
│   └── CreateCompteTransactionRequest.php
└── Providers/                 # Service Providers
    ├── AppServiceProvider.php
    ├── AuthServiceProvider.php
    └── EventServiceProvider.php
```

### Architecture des Services
- **AuthService** : Gestion de l'authentification et création de comptes
- **CompteService** : Logique métier des comptes (solde, QR codes)
- **TransactionService** : Traitement des transactions financières
- **EmailService** : Envoi d'emails avec système de retry

## 🛠️ Technologies Utilisées

### Backend
- **Laravel 10.10** - Framework PHP
- **PHP 8.1+** - Langage de programmation
- **PostgreSQL 15** - Base de données

### Authentification & Sécurité
- **Laravel Passport** - OAuth2 Server
- **Laravel Sanctum** - API Token Authentication

### APIs & Documentation
- **L5-Swagger** - Génération automatique de documentation OpenAPI
- **Guzzle HTTP** - Client HTTP pour les requêtes externes

### Fonctionnalités Spéciales
- **SimpleSoftwareIO QR Code** - Génération de QR codes
- **DomPDF** - Génération de reçus PDF
- **UUID** - Identifiants uniques pour les entités

### DevOps & Déploiement
- **Docker & Docker Compose** - Conteneurisation
- **PostgreSQL Alpine** - Base de données optimisée
- **Laravel Sail** - Environnement de développement

### Tests & Qualité
- **PHPUnit** - Framework de tests
- **Laravel Pint** - Formatage du code
- **Mockery** - Mocking pour les tests

## 📦 Installation

### Prérequis
- Docker & Docker Compose
- Git

### Installation Automatique
```bash
# Cloner le repository
git clone <repository-url>
cd om-pay-backend

# Lancer l'application avec Docker
docker-compose up -d

# Vérifier que les conteneurs sont démarrés
docker-compose ps
```

### Installation Manuelle (sans Docker)
```bash
# Cloner et installer les dépendances
git clone <repository-url>
cd om-pay-backend
composer install

# Configuration
cp .env.example .env
php artisan key:generate

# Configuration de la base de données PostgreSQL
# Modifier .env avec vos paramètres DB

# Migrations et seeders
php artisan migrate
php artisan db:seed

# Générer les clés Passport
php artisan passport:keys

# Démarrer le serveur
php artisan serve
```

## ⚙️ Configuration

### Variables d'Environnement (.env)
```env
# Application
APP_NAME="OM Pay API"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8000

# Base de données PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=postgres_db
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=password

# Mail Configuration (Gmail recommandé)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre-email@gmail.com
MAIL_PASSWORD=votre-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=votre-email@gmail.com
MAIL_FROM_NAME="OM Pay"

# Passport
PASSPORT_PERSONAL_ACCESS_CLIENT_ID=1
PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=secret

# QR Code
QR_CODE_SIZE=300
QR_CODE_MARGIN=4
```

### Configuration Gmail
1. Activer la vérification en 2 étapes
2. Générer un "App Password"
3. Utiliser l'App Password dans MAIL_PASSWORD

## 🔌 Utilisation de l'API

### Authentification
```bash
# Inscription
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "nom": "Diop",
    "prenom": "Amadou",
    "telephone": "+221771234567",
    "email": "amadou.diop@email.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'

# Connexion
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "amadou.diop@email.com",
    "password": "password123"
  }'
```

### Transactions
```bash
# Effectuer un transfert
curl -X POST http://localhost:8000/api/comptes/OMABC123/transactions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "transfert",
    "numero_telephone": "+221771234567",
    "montant_transaction": 50000
  }'

# Consulter le solde
curl -X GET http://localhost:8000/api/comptes/OMABC123/solde \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 📊 Modèles de Données

### User
```php
protected $fillable = [
    'nom', 'prenom', 'telephone', 'email', 'password'
];
```
- Relations : `hasMany(Compte::class)`

### Compte
```php
protected $fillable = [
    'id_client', 'numero_compte', 'code_pin', 'type',
    'date_creation', 'statut', 'metadata', 'code_qr'
];
```
- Relations : `belongsTo(User::class)`, `hasMany(Transaction::class)`
- Attribut calculé : `getSoldeAttribute()` - Calcule le solde dynamiquement

### Transaction
```php
protected $fillable = [
    'compte_id', 'type', 'montant', 'libelle', 'description',
    'numero_destinataire', 'code_marchand', 'reference',
    'date_transaction', 'statut'
];
```
- Types : `depot`, `retrait`, `transfert`, `paiement`, `frais`
- Relations : `belongsTo(Compte::class)`

### Marchand
```php
protected $fillable = [
    'nom', 'code_marchand', 'adresse', 'telephone',
    'email', 'type_commerce', 'actif'
];
```
- Scope : `actifs()` - Filtre les marchands actifs

## 🔧 Services

### AuthService
- `register(array $data)` : Inscription + création de compte automatique
- `login(array $data)` : Authentification OAuth2
- `logout(User $user)` : Révocation des tokens

### CompteService
- `createCompte(User $user, array $data)` : Création de compte avec QR code
- `getUserComptes(User $user)` : Comptes de l'utilisateur
- `getCompteWithTransactions(Compte $compte)` : Compte avec transactions

### TransactionService
- `createTransaction(Compte $compte, array $data)` : Création de transaction
- `getTransactions(Compte $compte, array $filters)` : Liste paginée avec formatage
- `getTransactionByReference(Compte $compte, string $reference)` : Transaction par référence

### EmailService
- `sendWelcomeEmail(User $user, string $codePin)` : Email de bienvenue avec retry
- `testEmailConfiguration()` : Test de configuration email

## 🎯 Événements et Observateurs

### TransactionObserver
Observateur automatique sur le modèle Transaction qui :
- Traite les transactions lors de la création (`created`)
- Met à jour les soldes selon le type de transaction
- Crée les transactions destinataires pour les transferts
- Génère les reçus PDF
- Calcule et applique les frais

### Événements
- **TransactionCreated** : Déclenché lors de la création d'une transaction
- **Registered** : Utilisateur inscrit (Laravel natif)

## 🧪 Tests

### Tests Unitaires
```bash
# Exécuter tous les tests
php artisan test

# Tests spécifiques
php artisan test tests/Unit/TransactionServiceTest.php
php artisan test tests/Unit/TransactionServiceComprehensiveTest.php
```

### Couverture des Tests
- **TransactionServiceTest** : Tests des calculs de solde et transferts
- **TransactionServiceComprehensiveTest** : Tests complets des transactions
- **PaginationFormatTest** : Tests du format de pagination personnalisé

### Fonctionnalités Testées
- ✅ Dépôts et retraits
- ✅ Transferts avec vérification des soldes
- ✅ Calcul automatique des frais
- ✅ Formatage des montants
- ✅ Pagination personnalisée
- ✅ Gestion des erreurs

## 🚀 Déploiement

### Avec Docker (Production)
```bash
# Build et déploiement
docker-compose -f docker-compose.yml up -d --build

# Logs
docker-compose logs -f app

# Migration en production
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan db:seed --force
```

### Variables d'Environnement Production
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.com

# Base de données PostgreSQL externe
DB_HOST=votre-host-postgresql
DB_DATABASE=votre-db-prod
DB_USERNAME=votre-user
DB_PASSWORD=votre-password

# Configuration email production
MAIL_MAILER=smtp
MAIL_HOST=votre-smtp-host
```

### Commandes de Maintenance
```bash
# Générer les clés Passport
php artisan passport:keys

# Clear les caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Générer la documentation Swagger
php scripts/generate_swagger_docs.php
```

## 📚 Documentation API

### Accès à Swagger
- **URL** : `http://localhost:8000/api/documentation`
- **Interface interactive** pour tester les endpoints
- **Génération automatique** via L5-Swagger

### Endpoints Documentés
- ✅ Authentification (register, login, logout)
- ✅ Gestion des comptes
- ✅ Transactions financières
- ✅ Gestion des marchands

### Génération de la Documentation
```bash
# Régénérer automatiquement
php scripts/generate_swagger_docs.php

# Fichiers générés
storage/api-docs/api-docs.json
storage/api-docs/api-docs.yaml
```

## 🔒 Sécurité

### Authentification
- **OAuth2** avec Laravel Passport
- **Tokens JWT** avec expiration
- **Refresh tokens** pour renouvellement

### Validation des Données
- **Form Requests** pour validation côté serveur
- **Règles de validation** strictes (formats téléphone, montants)
- **Sanitisation** automatique des entrées

### Sécurité des Transactions
- **Vérification des soldes** avant débit
- **Transactions atomiques** (rollback en cas d'erreur)
- **Logs d'audit** des opérations sensibles
- **Génération de références uniques**

### Protection des Données
- **Hashage des mots de passe** (bcrypt)
- **Chiffrement des données sensibles**
- **Rate limiting** sur les endpoints
- **CORS configuré** pour les origines autorisées

## 🤝 Contribuer

### Prérequis pour les Contributeurs
```bash
# Installation des dépendances de développement
composer install
npm install

# Configuration
cp .env.example .env
php artisan key:generate
php artisan passport:keys

# Base de données de test
php artisan migrate
php artisan db:seed
```

### Standards de Code
```bash
# Formatage du code
./vendor/bin/pint

# Tests
php artisan test

# Analyse statique (si configuré)
./vendor/bin/phpstan analyse
```

### Processus de Contribution
1. Fork le repository
2. Créer une branche feature (`git checkout -b feature/nouvelle-fonctionnalite`)
3. Commiter les changements (`git commit -m 'Ajout de nouvelle fonctionnalité'`)
4. Push vers la branche (`git push origin feature/nouvelle-fonctionnalite`)
5. Créer une Pull Request

## 📄 Licence

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 👥 Équipe

- **Développeur Principal** : Dié NIANG
- **Technologies** : Laravel, PostgreSQL, Docker
- **Version** : 1.0.0

## 📞 Support

Pour toute question ou problème :
- 📧 Email : support@ompay.com
- 📚 Documentation : [https://ompay.com/docs](https://ompay.com/docs)
- 🐛 Issues : [GitHub Issues](https://github.com/votre-repo/issues)

---

**OM Pay** - Solution de paiement mobile moderne et sécurisée pour l'Afrique. 🇸🇳
