# WASLA - Plateforme Agri-Logistique de Souss-Massa

## Transcription vocale Darija en direct

Le microphone du dashboard utilise désormais un service FastAPI et Gemini Live.
Consultez [le guide de configuration et de lancement](transcription/README.md).
Une page autonome est disponible sur `http://localhost:8001` après lancement du service.

Sous Windows, lancez `.\start-voice.ps1` et `.\start-dashboard.ps1` dans deux
terminaux, puis ouvrez `http://localhost:8000`. Le port **8000 appartient à PHP**,
le port **8001 à FastAPI** : ne lancez pas PHP sur 8001. Le service vocal local
est joignable directement sur `http://127.0.0.1:8001/healthz`.

WASLA (Moroccan Arabic for "Connection") is a clean-architecture, zero-dependency native PHP & MySQL platform built to optimize logistics, consolidate agricultural crop shipments, and minimize environmental impact in the Souss-Massa agricultural heartland of Morocco.

---

## 🚀 Fonctionnalités Clés
1. **Moteur Darija NLU (Gemini 1.5 Flash):** Un analyseur intelligent capable de comprendre le dialecte marocain (Darija) écrit ou parlé par les agriculteurs, et de le structurer instantanément au format JSON (Produit, Quantité, Qualité, Commune).
2. **Collecte des Offres de Récolte:** Suivi dynamique des récoltes prêtes à être expédiées dans les 6 communes clés.
3. **Optimisation Spatiale (Agrégation des Lots):** Algorithme transactionnel qui regroupe intelligemment les petites offres locales d'une même commune en lots logistiques (Cible: 5 à 10 Tonnes) pour remplir efficacement des camions, évitant ainsi les trajets retour à vide.
4. **Tableau de Bord Cartographique:** Vue interactive s'appuyant sur **Leaflet.js** et des calculs d'itinéraires automatiques pour visualiser les flux de transport et les gains écologiques (Co₂ évité).

---

## 📁 Structure du Projet
Le projet adopte une architecture moderne, fluide et modulaire :
```
wasla/
├── .env.example              # Exemple de variables d'environnement
├── SPEC.md                   # Spécification détaillée des fonctionnalités
├── README.md                 # Documentation d'accueil et d'installation
├── config/
│   └── db.php                # Connexion singleton PDO sécurisée & chargeur d'environnement
├── database/
│   └── schema.sql            # Schéma MySQL (InnoDB) + Données de graine (Souss-Massa)
├── api/
│   ├── process_nlu.php       # Point d'accès de traitement Darija NLU (Gemini API / Fallback local)
│   ├── get_offers.php        # Récupération des offres de récolte actives
│   └── aggregate_lots.php    # Moteur algorithmique d'agrégation de lots de transport
├── public/
│   └── index.php             # Dashboard UI Shell (HTML5, Tailwind CSS, Leaflet Map)
└── tasks/
    ├── plan.md               # Plan d'implémentation
    └── todo.md               # Suivi d'avancement des tâches du projet
```

---

## 🛠️ Installation et Démarrage Rapide

### 1. Prérequis
- Un serveur web local supportant **PHP 8.1+** (e.g., PHP CLI standard, XAMPP, Laragon, etc.)
- Un serveur de base de données **MySQL 8.0+**

### 2. Base de Données
Créez une base de données MySQL nommée `wasla` et importez le fichier de structure situé dans `database/schema.sql` :
```bash
mysql -u root -p -e "CREATE DATABASE wasla;"
mysql -u root -p wasla < database/schema.sql
```

### 3. Fichier d'Environnement
Dupliquez le fichier d'exemple et configurez vos informations de connexion MySQL ainsi que votre clé d'API Gemini (optionnelle pour le mode simulation locale) :
```bash
cp .env.example .env
```
Éditez le fichier `.env` :
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=wasla
DB_USER=your_user
DB_PASS=your_password

# Clé API Gemini (Obtenez-la sur Google AI Studio)
GEMINI_API_KEY=votre_cle_gemini
```

### 4. Lancement du Serveur Web Local
Lancez le serveur PHP natif directement à la racine du projet :
```bash
php -S localhost:8000 -t public/ public/router.php
```
Ouvrez ensuite votre navigateur sur [http://localhost:8000](http://localhost:8000).

---

## 🧪 Utilisation et Mode Offline Intelligent
- **Mode Connecté (Gemini API):** Si la clé `GEMINI_API_KEY` est configurée dans votre fichier `.env`, les requêtes vocales ou textuelles complexes en Darija seront envoyées directement au modèle **Gemini 1.5 Flash** pour une extraction à haute fidélité.
- **Mode Offline (Simulation Locale):** Si aucune clé API n'est définie, le système bascule de manière transparente sur un moteur lexical marocain local intégré au script `process_nlu.php` capable d'analyser les raccourcis Darija les plus courants (e.g. *Khizou, Maticha, Limoun, Massa, Belfaa*...) de manière 100% autonome et fluide !

## 🌍 Coordonnées des 6 Communes de Souss-Massa incluses :
Le projet intègre nativement et cartographie les coordonnées géographiques précises des pôles agricoles suivants :
- **Tiznit :** `29.6975, -9.7317`
- **Inezgane :** `30.3658, -9.5381`
- **Taroudant :** `30.4703, -8.8770`
- **Oulad Teima :** `30.3958, -9.2084`
- **Belfaa :** `30.0414, -9.5639`
- **Massa :** `30.0078, -9.6381`
