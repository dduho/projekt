# Guide HMR (Hot Module Replacement) - PRISM

## 🔥 HMR Activé !

Le Hot Module Replacement est maintenant configuré pour le développement frontend.

## 🚀 Comment utiliser

### Démarrage du serveur de développement

```bash
npm run dev
```

Le serveur Vite démarrera sur `http://localhost:5173`

### Mode développement (avec HMR)

Quand `npm run dev` tourne :
- ✅ Les changements CSS/JS sont appliqués **instantanément** sans recharger la page
- ✅ L'état de l'application est préservé
- ✅ Les modifications sont visibles en temps réel
- ✅ Meilleure expérience de développement

**Important** : Gardez le terminal `npm run dev` ouvert pendant le développement !

### Mode production (sans HMR)

```bash
npm run build
```

Utiliser uniquement pour :
- Déploiement en production
- Test des assets compilés
- Vérification des tailles de bundle

## ⚙️ Configuration

Le fichier `vite.config.js` a été mis à jour avec :

```javascript
server: {
    host: '0.0.0.0',
    port: 5173,
    hmr: {
        host: 'localhost',
        protocol: 'ws',
    },
    watch: {
        usePolling: true,  // Important pour Docker/Windows
    },
}
```

## 🎨 Système de thèmes dynamique

Les couleurs de texte s'adaptent maintenant automatiquement à chaque thème :

```javascript
const themes = {
    orangeBlue: {
        gradient: { ... },
        textColor: '#ffffff',
        textSecondary: 'rgba(255, 255, 255, 0.9)',
        textMuted: 'rgba(255, 255, 255, 0.7)'
    },
    // ... autres thèmes
}
```

Chaque thème définit :
- **textColor** : Couleur principale du texte
- **textSecondary** : Texte secondaire (80-90% opacité)
- **textMuted** : Texte discret (60-70% opacité)

Ces variables CSS sont mises à jour dynamiquement :
- `--text-primary`
- `--text-secondary`
- `--text-muted`

## 🐛 Résolution de problèmes

### Le HMR ne fonctionne pas

1. Vérifier que `npm run dev` tourne :
   ```bash
   # Vérifier les processus Node
   Get-Process node
   ```

2. Vérifier le port 5173 :
   ```bash
   # Windows
   netstat -ano | findstr :5173
   ```

3. Redémarrer Vite :
   ```bash
   # Ctrl+C pour arrêter
   npm run dev
   ```

### Les changements ne s'affichent pas

1. **Hard refresh** : `Ctrl + Shift + R`
2. Vider le cache du navigateur
3. Vérifier la console pour les erreurs WebSocket

### Erreur "Cannot connect to HMR"

- Vérifier que le port 5173 n'est pas bloqué par un firewall
- S'assurer que `localhost:5173` est accessible
- Essayer `127.0.0.1:5173` si `localhost` ne fonctionne pas

## 📝 Workflow recommandé

### Développement frontend

```bash
# Terminal 1 - Serveur Vite (HMR)
npm run dev

# Terminal 2 - Containers Docker
docker-compose up -d

# Terminal 3 - Commandes Laravel
docker-compose exec app php artisan ...
```

### Avant commit/déploiement

```bash
# Compiler les assets pour production
npm run build

# Tester l'application
npm run preview
```

## 🎯 Performances

Avec HMR activé :
- ⚡ Rechargement < 100ms pour les composants Vue
- ⚡ Rechargement < 50ms pour les fichiers CSS
- ⚡ Pas de perte d'état pendant le développement
- ⚡ Feedback visuel instantané

Sans HMR (build + refresh manuel) :
- 🐌 6-8 secondes de build
- 🐌 2-3 secondes de rechargement navigateur
- 🐌 Perte de l'état de l'application

## 💡 Astuces

1. **Split screen** : Éditeur + Navigateur côte à côte pour voir les changements en temps réel

2. **Console du navigateur** : Garder ouverte pour voir les logs HMR
   ```
   [vite] hot updated: /resources/js/Components/...
   ```

3. **Extensions navigateur** : Installer Vue Devtools pour debug

4. **Watch mode** : Ne pas arrêter `npm run dev` entre les sessions

5. **Port personnalisé** : Modifier dans `vite.config.js` si 5173 est occupé
