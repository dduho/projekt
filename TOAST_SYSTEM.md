# Système de Notifications Toast

## 🎯 Fonctionnement Global

Le système de notifications toast est **entièrement automatique** pour toutes les actions backend.

### ✅ Notifications Automatiques

Toutes les requêtes Inertia (POST, PUT, PATCH, DELETE) affichent automatiquement un toast :

**Depuis le Backend (Laravel) :**
```php
// Dans vos contrôleurs
return redirect()->back()->with('success', 'Opération réussie !');
return redirect()->back()->with('error', 'Une erreur est survenue');
return redirect()->back()->with('warning', 'Attention !');
return redirect()->back()->with('info', 'Information');
```

**Les messages sont traduits automatiquement** selon la locale active grâce au middleware `HandleInertiaRequests`.

### 🎨 Notifications Manuelles (Frontend)

Pour des cas spécifiques où vous voulez afficher un toast sans requête backend :

```javascript
import { useToast } from '@/Composables/useToast';

const { toast } = useToast();

// Notifications simples
toast.success('Opération réussie !');
toast.error('Une erreur est survenue');
toast.warning('Attention !');
toast.info('Information');

// Notification personnalisée
toast.custom({
    type: 'success',
    title: 'Titre personnalisé',
    message: 'Message détaillé',
    duration: 10000 // 10 secondes (0 = ne se ferme pas)
});
```

## 🔧 Configuration Actuelle

### Intercepteur Global (app.js)
- ✅ Écoute tous les événements Inertia `success`
- ✅ Écoute tous les événements Inertia `error`
- ✅ Traduction automatique des messages
- ✅ Affichage automatique des toasts

### Types de Messages Supportés
- `success` : Vert - Opérations réussies
- `error` : Rouge - Erreurs
- `warning` : Orange - Avertissements
- `info` : Bleu - Informations

### Features
- ✨ Design glass morphism moderne
- ✨ Animations fluides (slide-in, bounce)
- ✨ Barre de progression animée
- ✨ Pause automatique au survol
- ✨ Durée configurable
- ✨ Fermeture manuelle possible
- ✨ Traduction automatique

## 📝 Ajout de Nouvelles Traductions

Les messages du backend sont dans les fichiers :
- `lang/fr.json` (Français)
- `lang/en.json` (English)

Ajoutez simplement la traduction du message exact :

```json
{
    "Votre message backend": "Your backend message",
    "Opération réussie": "Operation successful"
}
```

## 🚫 Ne PAS faire

❌ **Ne pas** ajouter manuellement `toast.success()` ou `toast.error()` dans les callbacks `onSuccess`/`onError` des requêtes Inertia
❌ **Ne pas** dupliquer les notifications - elles sont automatiques !

✅ **À faire** : Utiliser `onSuccess` uniquement pour les actions UI locales (fermer un modal, réinitialiser un formulaire, etc.)

## Exemple Correct

```javascript
const updateOwner = () => {
  router.put(route('projects.update', props.project.id), {
    owner: ownerText.value || null,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      // Actions UI locales uniquement
      editingOwner.value = false;
      // Le toast s'affiche automatiquement !
    }
  })
}
```

## 🎯 Actions Couvertes Automatiquement

- ✅ Création de projets, risques, demandes de changement
- ✅ Mise à jour de tous les champs
- ✅ Suppression d'éléments
- ✅ Commentaires (ajout, modification, suppression)
- ✅ Phases de projet
- ✅ Gestion des utilisateurs
- ✅ Gestion des catégories
- ✅ Profil et mot de passe
- ✅ Notifications
- ✅ Imports Excel
- ✅ Analyse ML des risques
