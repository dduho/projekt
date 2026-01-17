**CAHIER DES CHARGES**

Plateforme de Gestion de Projets

**MOOV PROJECT MANAGER**

| **Client** | MOOV MONEY TOGO |
| --- | --- |
| **Version** | 1.0 |
| **Date** | Janvier 2026 |
| **Statut** | Document Initial |
| **Classification** | Confidentiel |

_Stack Technologique: Laravel 11 + Vue.js 3 + Design Glassmorphism_

# **TABLE DES MATIÈRES**

# **1\. INTRODUCTION ET CONTEXTE**

## **1.1 Présentation du Projet**

Ce cahier des charges définit les spécifications complètes pour le développement d'une plateforme web moderne de gestion de projets destinée à MOOV MONEY TOGO. Cette application permettra de centraliser, suivre et piloter l'ensemble du portefeuille de projets de l'entreprise, actuellement composé de 65 projets actifs.

## **1.2 Objectifs Stratégiques**

- Centraliser toutes les informations projets dans une interface unique et intuitive
- Offrir une visibilité en temps réel sur l'état d'avancement du portefeuille
- Faciliter la prise de décision grâce à des tableaux de bord dynamiques
- Améliorer la collaboration entre les équipes projet
- Assurer la traçabilité complète des changements et décisions
- Automatiser les rapports et notifications

## **1.3 Périmètre Fonctionnel**

La plateforme intégrera les modules suivants, correspondant aux feuilles du fichier Excel source:

- Dashboard - Tableau de bord avec KPIs et indicateurs visuels
- Project Register - Registre complet des 65 projets avec leurs métadonnées
- Status Tracking - Matrice de suivi des statuts et phases de développement
- Risk & Issues Log - Registre des risques et problèmes identifiés
- Change Log - Journal des modifications et demandes de changement
- Governance & Process - Documentation des processus de gouvernance

# **2\. ARCHITECTURE TECHNIQUE**

## **2.1 Stack Technologique**

### **2.1.1 Backend - Laravel 11**

- PHP 8.2+ avec Laravel 11.x (dernière version LTS)
- Base de données: MySQL 8.0 ou PostgreSQL 15
- Cache: Redis pour les sessions et le cache applicatif
- Queue: Laravel Horizon pour les jobs asynchrones
- API: Laravel Sanctum pour l'authentification API
- Websockets: Laravel Reverb pour les notifications temps réel

### **2.1.2 Frontend - Vue.js 3**

- Vue.js 3.4+ avec Composition API
- Inertia.js pour le rendu hybride SPA/SSR
- Pinia pour la gestion d'état
- TailwindCSS 3.4 pour le styling
- Chart.js / ApexCharts pour les graphiques
- Vite pour le bundling et HMR

### **2.1.3 Infrastructure**

- Serveur: Nginx avec PHP-FPM
- Containerisation: Docker avec Docker Compose
- CI/CD: GitHub Actions ou GitLab CI

## **2.2 Architecture Applicative**

L'application suivra une architecture en couches respectant les principes SOLID et les bonnes pratiques Laravel:

┌─────────────────────────────────────────────────────────────┐

│ PRESENTATION LAYER │

│ Vue.js Components + Inertia.js + TailwindCSS Glass │

├─────────────────────────────────────────────────────────────┤

│ APPLICATION LAYER │

│ Controllers + Form Requests + Resources + Policies │

├─────────────────────────────────────────────────────────────┤

│ DOMAIN LAYER │

│ Services + Actions + DTOs + Events + Listeners │

├─────────────────────────────────────────────────────────────┤

│ INFRASTRUCTURE LAYER │

│ Models + Repositories + Migrations + Seeders │

└─────────────────────────────────────────────────────────────┘

# **3\. DESIGN UI/UX - GLASSMORPHISM**

## **3.1 Principes du Design Glass**

Le design Glassmorphism (ou Glass UI) est caractérisé par des effets de transparence et de flou qui donnent l'impression de surfaces en verre. Ce style moderne et élégant sera appliqué de manière cohérente sur toute l'application.

### **3.1.1 Caractéristiques Visuelles**

- Fond semi-transparent avec backdrop-filter: blur()
- Bordures subtiles avec des dégradés légers
- Ombres douces et diffuses
- Arrière-plan avec gradient coloré ou image
- Coins arrondis pour une apparence moderne

### **3.1.2 Palette de Couleurs**

| **Couleur** | **Code Hex** | **Utilisation** |
| --- | --- | --- |
| Primary Blue | #1E3A5F | En-têtes, boutons principaux, liens |
| Success Green | #10B981 | Statut Green, validations, succès |
| Warning Amber | #F59E0B | Statut Amber, alertes modérées |
| Danger Red | #EF4444 | Statut Red, erreurs, blocages |
| Glass White | rgba(255,255,255,0.25) | Fond des cartes Glass |
| Glass Border | rgba(255,255,255,0.18) | Bordures des éléments Glass |
| Background Gradient | #667eea → #764ba2 | Arrière-plan principal |

### **3.1.3 Classes CSS TailwindCSS Glass**

/\* Composant Glass Card de base \*/

.glass-card {

@apply bg-white/25 backdrop-blur-xl rounded-2xl;

@apply border border-white/18 shadow-xl;

@apply hover:bg-white/30 transition-all duration-300;

}

/\* Configuration TailwindCSS \*/

backdropBlur: { xs: '2px', sm: '4px', md: '12px', lg: '16px', xl: '24px' }

## **3.2 Composants UI Principaux**

### **3.2.1 Navigation**

- Sidebar Glass fixe à gauche avec menu hiérarchique
- Header Glass avec recherche globale et notifications
- Breadcrumb pour la navigation contextuelle

### **3.2.2 Cards et Conteneurs**

- GlassCard - Conteneur principal avec effet verre
- StatCard - Carte KPI avec icône et variation
- ProjectCard - Carte projet avec statut RAG
- Modal Glass - Fenêtres modales transparentes

### **3.2.3 Tableaux et Listes**

- DataTable Glass avec tri, filtres et pagination
- ListView pour les listes d'éléments
- Timeline pour l'historique des activités

# **4\. MODÈLE DE DONNÉES**

## **4.1 Schéma de Base de Données**

Le modèle de données est conçu pour refléter fidèlement la structure du fichier Excel source tout en permettant une extensibilité future.

### **4.1.1 Table: projects**

| **Colonne** | **Type** | **Nullable** | **Description** |
| --- | --- | --- | --- |
| id  | bigint unsigned | Non | Clé primaire auto-incrémentée |
| project_code | varchar(20) | Non | Code unique (MOOV-001) |
| name | varchar(255) | Non | Nom du projet |
| description | text | Oui | Description détaillée |
| category_id | bigint unsigned | Non | FK vers categories |
| business_area | varchar(100) | Oui | Domaine métier |
| priority | enum | Non | High, Medium, Low |
| frs_status | enum | Non | Draft, Review, Signoff |
| dev_status | enum | Non | Not Started, In Development, Deployed |
| current_progress | varchar(100) | Oui | État d'avancement actuel |
| blockers | text | Oui | Éléments bloquants |
| owner_id | bigint unsigned | Oui | FK vers users |
| planned_release | varchar(50) | Oui | Version prévue |
| target_date | date | Oui | Date cible |
| submission_date | date | Oui | Date de soumission |
| rag_status | enum | Non | Green, Amber, Red |
| completion_percent | integer | Non | Pourcentage 0-100 |
| created_at | timestamp | Non | Date de création |
| updated_at | timestamp | Non | Date de modification |

### **4.1.2 Table: project_phases**

| **Colonne** | **Type** | **Nullable** | **Description** |
| --- | --- | --- | --- |
| id  | bigint unsigned | Non | Clé primaire |
| project_id | bigint unsigned | Non | FK vers projects |
| phase | enum | Non | FRS, Development, Testing, UAT, Deployment |
| status | enum | Non | Pending, In Progress, Completed, Blocked |
| completed_at | timestamp | Oui | Date de complétion |
| remarks | text | Oui | Remarques sur la phase |

### **4.1.3 Table: risks**

| **Colonne** | **Type** | **Nullable** | **Description** |
| --- | --- | --- | --- |
| id  | bigint unsigned | Non | Clé primaire |
| risk_code | varchar(20) | Non | Code unique (RISK-001) |
| project_id | bigint unsigned | Non | FK vers projects |
| type | enum | Non | Risk, Issue |
| description | text | Non | Description du risque/problème |
| impact | enum | Non | Low, Medium, High, Critical |
| probability | enum | Non | Low, Medium, High |
| risk_score | enum | Non | Low, Medium, High, Critical |
| mitigation_plan | text | Oui | Plan de mitigation |
| owner_id | bigint unsigned | Oui | FK vers users |
| status | enum | Non | Open, In Progress, Mitigated, Closed |

### **4.1.4 Table: change_requests**

| **Colonne** | **Type** | **Nullable** | **Description** |
| --- | --- | --- | --- |
| id  | bigint unsigned | Non | Clé primaire |
| change_code | varchar(20) | Non | Code unique (CHG-001) |
| project_id | bigint unsigned | Non | FK vers projects |
| change_type | enum | Non | Scope, Schedule, Budget, Resource |
| description | text | Non | Description du changement |
| requested_by_id | bigint unsigned | Non | FK vers users |
| approved_by_id | bigint unsigned | Oui | FK vers users |
| status | enum | Non | Pending, Under Review, Approved, Rejected |
| requested_at | timestamp | Non | Date de demande |
| resolved_at | timestamp | Oui | Date de résolution |

### **4.1.5 Tables Complémentaires**

- users - Utilisateurs avec rôles (Admin, PM, Viewer)
- categories - Catégories de projets (Payment Services, Integration, etc.)
- activity_logs - Journal d'activité avec polymorphisme
- comments - Commentaires sur projets/risques/changements
- attachments - Pièces jointes avec stockage S3/local
- notifications - Notifications utilisateur

# **5\. SPÉCIFICATIONS FONCTIONNELLES DÉTAILLÉES**

## **5.1 Module Dashboard**

Le tableau de bord constitue la page d'accueil principale et offre une vue synthétique de l'état du portefeuille projets.

### **5.1.1 KPIs Principaux**

- Total Projects: Nombre total de projets (65 actuellement)
- Deployed/Live: Projets en production avec pourcentage
- In Progress: Projets en cours de développement
- Awaiting Action: Projets en attente nécessitant une intervention
- FRS Signed Off: Projets avec spécifications validées
- Critical Risks: Nombre de risques critiques ouverts

### **5.1.2 Graphiques et Visualisations**

- Donut Chart: Répartition par statut RAG (Green/Amber/Red)
- Bar Chart: Distribution par catégorie
- Line Chart: Évolution mensuelle des déploiements
- Heatmap: Matrice des phases par projet
- Progress Bars: Avancement global du portefeuille

### **5.1.3 Widgets Interactifs**

- Recent Activity: Timeline des 10 dernières activités
- Critical Projects: Liste des projets Red nécessitant attention
- Upcoming Deadlines: Échéances des 30 prochains jours
- Quick Actions: Boutons d'accès rapide aux actions fréquentes

## **5.2 Module Project Register**

Ce module centralise toutes les informations des projets et permet leur gestion complète.

### **5.2.1 Liste des Projets**

- DataTable avec pagination, tri multi-colonnes et recherche globale
- Filtres avancés: Catégorie, Priorité, Statut RAG, Owner, Date range
- Export: CSV, Excel, PDF
- Vue Kanban alternative avec drag & drop
- Colonnes personnalisables par utilisateur

### **5.2.2 Fiche Projet Détaillée**

- Header avec statut RAG, priorité et actions rapides
- Onglet Overview: Informations générales et description
- Onglet Phases: Timeline interactive des phases avec statuts
- Onglet Risks: Risques et issues liés au projet
- Onglet Changes: Historique des demandes de changement
- Onglet Activity: Journal d'activité complet
- Onglet Documents: Pièces jointes et liens

### **5.2.3 Actions CRUD**

- Création: Formulaire multi-étapes avec validation
- Modification: Édition inline ou formulaire complet
- Suppression: Soft delete avec confirmation
- Duplication: Clone de projet avec reset des statuts
- Archivage: Déplacement vers archive avec historique préservé

## **5.3 Module Status Tracking**

Matrice de suivi avancée permettant de visualiser l'état de chaque projet à travers ses différentes phases.

### **5.3.1 Matrice de Phases**

- Vue matricielle: Projets en lignes, Phases en colonnes
- Indicateurs visuels: ✓ Complété, ⏳ En cours, ⏸ En attente, ❌ Bloqué
- Code couleur: Vert (OK), Jaune (Attention), Rouge (Critique)
- Tooltip au survol: Détails de la phase et remarques

### **5.3.2 Timeline Gantt**

- Vue chronologique des projets avec dates cibles
- Dépendances entre projets visualisées
- Milestones et jalons clés
- Zoom temporel: Jour, Semaine, Mois, Trimestre

## **5.4 Module Risk & Issues**

Gestion complète des risques et problèmes identifiés sur les projets.

### **5.4.1 Registre des Risques**

- Liste avec filtres par type, impact, probabilité, statut
- Matrice de risques interactive (Impact x Probabilité)
- Calcul automatique du score de risque
- Alertes pour risques critiques

### **5.4.2 Workflow de Gestion**

- Création avec assignation automatique au PM
- États: Open → In Progress → Mitigated → Closed
- Plan de mitigation avec suivi des actions
- Notifications aux parties prenantes

## **5.5 Module Change Log**

Journal des demandes de changement avec workflow d'approbation.

### **5.5.1 Gestion des Changements**

- Types: Scope, Schedule, Budget, Resource
- Workflow: Pending → Under Review → Approved/Rejected
- Impact analysis obligatoire
- Approbation multi-niveaux configurable

## **5.6 Module Governance**

Documentation et référentiel des processus de gestion de projet.

### **5.6.1 Contenu**

- Phases du cycle de vie projet avec livrables attendus
- Framework de reporting (fréquence, format)
- Matrice RACI des responsabilités
- Templates et modèles téléchargeables
- Wiki collaboratif éditable

# **6\. SPÉCIFICATIONS API REST**

## **6.1 Architecture API**

L'API suivra les conventions RESTful avec versioning et documentation OpenAPI/Swagger.

### **6.1.1 Conventions**

- Base URL: /api/v1/
- Format: JSON avec pagination curseur/offset
- Auth: Bearer Token (Laravel Sanctum)
- Rate Limiting: 60 req/min par utilisateur

### **6.1.2 Endpoints Projets**

| **Méthode** | **Endpoint** | **Description** |
| --- | --- | --- |
| **GET** | /projects | Liste paginée avec filtres |
| **GET** | /projects/{id} | Détail d'un projet |
| **POST** | /projects | Créer un projet |
| **PUT** | /projects/{id} | Modifier un projet |
| **DELETE** | /projects/{id} | Supprimer (soft delete) |
| **GET** | /projects/{id}/phases | Phases du projet |
| **PUT** | /projects/{id}/phases/{phase} | Mettre à jour une phase |
| **GET** | /projects/{id}/risks | Risques du projet |
| **GET** | /projects/{id}/changes | Changements du projet |
| **GET** | /projects/{id}/activity | Activité du projet |
| **POST** | /projects/{id}/comments | Ajouter un commentaire |
| **POST** | /projects/import | Import Excel/CSV |

### **6.1.3 Endpoints Risques**

| **Méthode** | **Endpoint** | **Description** |
| --- | --- | --- |
| **GET** | /risks | Liste des risques |
| **GET** | /risks/{id} | Détail d'un risque |
| **POST** | /risks | Créer un risque |
| **PUT** | /risks/{id} | Modifier un risque |
| **PUT** | /risks/{id}/status | Changer le statut |
| **GET** | /risks/matrix | Matrice des risques |

### **6.1.4 Endpoints Dashboard & Stats**

| **Méthode** | **Endpoint** | **Description** |
| --- | --- | --- |
| **GET** | /dashboard/kpis | KPIs du tableau de bord |
| **GET** | /dashboard/charts/rag | Distribution RAG |
| **GET** | /dashboard/charts/categories | Répartition par catégorie |
| **GET** | /dashboard/charts/timeline | Évolution temporelle |
| **GET** | /dashboard/activity | Activité récente |
| **GET** | /reports/portfolio | Rapport portefeuille |
| **GET** | /reports/export | Export global |

# **7\. SÉCURITÉ ET AUTHENTIFICATION**

## **7.1 Authentification**

- Laravel Sanctum pour l'authentification SPA/API
- Support SSO via SAML2/OAuth2 (optionnel)
- 2FA optionnel via TOTP (Google Authenticator)
- Sessions sécurisées avec expiration configurable
- Password policy: min 12 caractères, complexité requise

## **7.2 Autorisation et Rôles**

| **Rôle** | **Permissions** |
| --- | --- |
| **Admin** | Accès total: CRUD tous modules, gestion utilisateurs, configuration système, import/export |
| **Project Manager** | CRUD projets assignés, gestion risques/changements, rapports, commentaires |
| **Team Member** | Lecture tous projets, modification phases assignées, ajout commentaires |
| **Viewer** | Lecture seule sur tous les modules, export personnel, dashboard |

## **7.3 Sécurité Applicative**

- Protection CSRF sur tous les formulaires
- Validation et sanitization de toutes les entrées
- Protection XSS via échappement automatique
- Rate limiting sur les endpoints sensibles
- Audit log de toutes les actions critiques
- Headers de sécurité: CSP, HSTS, X-Frame-Options

# **8\. IMPORT DES DONNÉES EXCEL**

## **8.1 Processus d'Import**

Le système permettra l'import initial des données depuis le fichier Excel source, ainsi que des imports ultérieurs pour mise à jour.

### **8.1.1 Mapping des Feuilles**

| **Feuille Excel** | **Table(s) Cible** | **Traitement** |
| --- | --- | --- |
| PROJECT REGISTER | projects, categories | Création/Upsert par code |
| STATUS TRACKING | projects, project_phases | Mise à jour phases |
| RISK & ISSUES LOG | risks | Création risques liés |
| CHANGE LOG | change_requests | Création changements |
| GOVERNANCE | governance_docs | Import documentation |

### **8.1.2 Fonctionnalités d'Import**

- Validation préalable avec rapport d'erreurs
- Mode preview avant import définitif
- Import incrémental (upsert sur code projet)
- Rollback en cas d'erreur
- Historique des imports avec utilisateur et date
- Notification de fin d'import

### **8.1.3 Package Recommandé**

composer require maatwebsite/excel

Utilisation de Laravel Excel pour le parsing et la validation des fichiers Excel.

# **9\. SYSTÈME DE NOTIFICATIONS**

## **9.1 Types de Notifications**

- In-App: Bell icon avec badge count et dropdown
- Email: Digest quotidien ou notifications instantanées
- Websocket: Temps réel via Laravel Reverb

## **9.2 Événements Déclencheurs**

- Changement de statut RAG d'un projet
- Nouveau risque critique créé
- Demande de changement en attente d'approbation
- Échéance approchante (J-7, J-3, J-1)
- Commentaire/mention sur un projet
- Import de données terminé

## **9.3 Préférences Utilisateur**

Chaque utilisateur pourra configurer ses préférences de notification par type d'événement et canal de communication.

# **10\. PLANNING DE DÉVELOPPEMENT**

## **10.1 Phases du Projet**

| **Phase** | **Activités** | **Durée** | **Livrables** |
| --- | --- | --- | --- |
| **1** | Setup & Architecture | 1 semaine | Env Docker, CI/CD, DB |
| **2** | Backend Core | 2 semaines | Models, Migrations, API |
| **3** | Frontend Base | 2 semaines | Layout Glass, Components |
| **4** | Module Dashboard | 1 semaine | KPIs, Charts, Widgets |
| **5** | Module Projects | 2 semaines | CRUD, Filtres, Export |
| **6** | Module Status | 1 semaine | Matrice, Timeline |
| **7** | Module Risks | 1 semaine | CRUD, Matrice risques |
| **8** | Module Changes | 1 semaine | Workflow approbation |
| **9** | Import Excel | 1 semaine | Parsing, Validation |
| **10** | Notifications | 1 semaine | Events, Websockets |
| **11** | Tests & QA | 2 semaines | Unit, Feature, E2E |
| **12** | Deploy & Doc | 1 semaine | Production, Formation |

**Durée totale estimée:** 16 semaines (4 mois)

## **10.2 Équipe Recommandée**

- 1 Tech Lead / Architecte (full-time)
- 2 Développeurs Backend Laravel (full-time)
- 1 Développeur Frontend Vue.js (full-time)
- 1 UI/UX Designer (mi-temps phases 2-3)
- 1 QA Engineer (phase 11)

# **11\. LIVRABLES ATTENDUS**

## **11.1 Code Source**

- Repository Git avec historique complet et branches organisées
- Code documenté avec PHPDoc et JSDoc
- Tests automatisés (couverture > 80%)
- Fichiers de configuration Docker

## **11.2 Documentation**

- README.md avec instructions d'installation
- Documentation API (Swagger/OpenAPI)
- Guide utilisateur avec captures d'écran
- Guide d'administration
- Documentation technique d'architecture

## **11.3 Environnements**

- Environnement de développement local (Docker)
- Environnement de staging/recette
- Environnement de production
- Scripts de déploiement automatisé

## **11.4 Formation**

- Session de formation utilisateurs (2h)
- Session de formation administrateurs (2h)
- Vidéos tutorielles des fonctionnalités principales

# **12\. ANNEXES**

## **12.1 Structure des Dossiers Laravel**

moov-project-manager/

├── app/

│ ├── Actions/ # Actions métier

│ ├── DTOs/ # Data Transfer Objects

│ ├── Events/ # Événements applicatifs

│ ├── Http/

│ │ ├── Controllers/ # Controllers API & Web

│ │ ├── Requests/ # Form Requests validation

│ │ └── Resources/ # API Resources

│ ├── Listeners/ # Event Listeners

│ ├── Models/ # Eloquent Models

│ ├── Notifications/ # Notification classes

│ ├── Policies/ # Authorization Policies

│ └── Services/ # Business Services

├── database/

│ ├── migrations/ # Migrations DB

│ └── seeders/ # Seeders & import

├── resources/

│ └── js/

│ ├── Components/ # Vue Components

│ ├── Layouts/ # Page Layouts

│ ├── Pages/ # Inertia Pages

│ └── Stores/ # Pinia Stores

├── routes/

│ ├── api.php # API Routes

│ └── web.php # Web Routes

└── tests/ # Tests PHPUnit & Pest

## **12.2 Composants Vue.js Glass**

resources/js/Components/

├── Glass/

│ ├── GlassCard.vue

│ ├── GlassButton.vue

│ ├── GlassInput.vue

│ ├── GlassSelect.vue

│ ├── GlassModal.vue

│ └── GlassTable.vue

├── Charts/

│ ├── DonutChart.vue

│ ├── BarChart.vue

│ └── LineChart.vue

├── Project/

│ ├── ProjectCard.vue

│ ├── ProjectForm.vue

│ └── PhaseTimeline.vue

└── Dashboard/

├── KpiCard.vue

├── ActivityFeed.vue

└── CriticalProjects.vue

## **12.3 Variables d'Environnement**

\# .env.example

APP_NAME='Moov Project Manager'

APP_ENV=production

APP_DEBUG=false

APP_URL=<https://projects.moovmoney.tg>

DB_CONNECTION=mysql

DB_HOST=127.0.0.1

DB_PORT=3306

DB_DATABASE=moov_projects

REDIS_HOST=127.0.0.1

QUEUE_CONNECTION=redis

BROADCAST_DRIVER=reverb

MAIL_MAILER=smtp

MAIL_FROM_ADDRESS=<noreply@moovmoney.tg>

_- Fin du Cahier des Charges -_