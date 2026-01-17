# 🚀 Guide de Démarrage Rapide - PRISM

## ✅ État Actuel du Projet

### Backend (Complété ✓)
- ✅ Laravel 11 configuré
- ✅ Docker compose avec tous les services
- ✅ Migrations de base de données créées
- ✅ Models avec relations
- ✅ Services métier (ProjectService, RiskService, DashboardService)
- ✅ Controllers API complets
- ✅ Form Requests et Resources

### Frontend (Phase 3 Complétée ✓)
- ✅ Vue 3 + Inertia.js configuré
- ✅ TailwindCSS avec thème Glass
- ✅ Composants UI réutilisables
- ✅ Layouts (AppLayout, GuestLayout)
- ✅ Stores Pinia (auth, project, notification)
- ✅ Pages Login et Dashboard
- ✅ Routes Inertia configurées

## 📋 Commandes de Démarrage

### 1. Démarrer les services Docker

```powershell
# Démarrer tous les conteneurs
docker-compose up -d

# Vérifier que tous les services sont actifs
docker-compose ps
```

### 2. Installation des dépendances

```powershell
# Installer les dépendances PHP
docker-compose exec app composer install

# Installer les dépendances NPM
docker-compose exec app npm install
```

### 3. Configuration de l'environnement

```powershell
# Copier le fichier .env (si pas déjà fait)
Copy-Item .env.example .env

# Générer la clé d'application
docker-compose exec app php artisan key:generate

# Créer le lien de stockage
docker-compose exec app php artisan storage:link
```

### 4. Base de données

```powershell
# Exécuter les migrations
docker-compose exec app php artisan migrate

# Seed avec données de test (optionnel)
docker-compose exec app php artisan db:seed
```

### 5. Compiler les assets frontend

```powershell
# Build pour production
docker-compose exec app npm run build

# OU pour le développement avec hot reload
docker-compose exec app npm run dev
```

### 6. Démarrer les workers

```powershell
# Dans un nouveau terminal - Horizon pour les queues
docker-compose exec app php artisan horizon

# Dans un autre terminal - Reverb pour WebSockets (si besoin)
docker-compose exec app php artisan reverb:start
```

## 🌐 Accéder à l'application

### URLs
- **Application**: http://localhost:8080
- **Mailpit** (emails de test): http://localhost:8025
- **Horizon** (queues): http://localhost:8080/horizon

### Utilisateur par défaut
Si vous avez seedé la base de données:
- **Email**: admin@moovmoney.tg
- **Password**: password

## 🛠️ Commandes Utiles

### Docker
```powershell
# Voir les logs
docker-compose logs -f app

# Arrêter tous les services
docker-compose down

# Redémarrer un service
docker-compose restart app

# Accéder au shell du conteneur
docker-compose exec app bash
```

### Laravel
```powershell
# Nettoyer les caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Mettre en cache pour production
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Lancer les tests
docker-compose exec app php artisan test
```

### NPM
```powershell
# Build production
docker-compose exec app npm run build

# Dev avec hot reload
docker-compose exec app npm run dev

# Linter
docker-compose exec app npm run lint
```

## 📁 Structure des Fichiers Importants

```
projekt/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/    # Controllers API
│   │   ├── Middleware/         # HandleInertiaRequests
│   │   └── Requests/           # Form validation
│   ├── Models/                 # Models Eloquent
│   └── Services/               # Logique métier
├── resources/
│   ├── css/
│   │   └── app.css            # Styles Glass + Tailwind
│   ├── js/
│   │   ├── Components/        # Composants Vue
│   │   ├── Layouts/           # Layouts Inertia
│   │   ├── Pages/             # Pages Inertia
│   │   ├── stores/            # Stores Pinia
│   │   └── app.js             # Entry point
│   └── views/
│       └── app.blade.php      # Template Inertia
├── routes/
│   ├── web.php                # Routes Inertia
│   └── api.php                # Routes API REST
├── docker-compose.yml         # Configuration Docker
├── tailwind.config.js         # Config Tailwind
└── vite.config.js             # Config Vite
```

## 🐛 Dépannage

### Le frontend ne se charge pas
```powershell
# Vérifier que Vite est démarré
docker-compose exec app npm run dev

# Vérifier les logs Nginx
docker-compose logs nginx
```

### Erreur 500
```powershell
# Vérifier les logs Laravel
docker-compose exec app tail -f storage/logs/laravel.log

# Vérifier les permissions
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Les migrations échouent
```powershell
# Vérifier que PostgreSQL est prêt
docker-compose exec postgres pg_isready

# Réinitialiser la base
docker-compose exec app php artisan migrate:fresh --seed
```

### NPM install échoue
```powershell
# Nettoyer le cache npm
docker-compose exec app npm cache clean --force
docker-compose exec app rm -rf node_modules package-lock.json
docker-compose exec app npm install
```

## 📚 Prochaines Étapes

1. ✅ **Phase 3 Complétée** - Frontend Base
2. 🔄 **Phase 4 En Cours** - Dashboard Testing
3. ⏳ **Phase 5 Prochaine** - Projects CRUD

Consultez [FRONTEND_PROGRESS.md](./FRONTEND_PROGRESS.md) pour plus de détails.

## 💡 Ressources

- [Documentation Laravel](https://laravel.com/docs)
- [Documentation Vue 3](https://vuejs.org/)
- [Documentation Inertia.js](https://inertiajs.com/)
- [Documentation TailwindCSS](https://tailwindcss.com/)
- [Cahier des charges](./CAHIER_DE_CHARGES_DEVELOPPEMENT.md)

---

**Besoin d'aide?** Consultez les logs avec `docker-compose logs -f`
