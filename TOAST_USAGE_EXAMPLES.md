<!-- 
  Exemple d'utilisation des notifications toast
  Ajoutez ce code n'importe où dans votre composant Vue
-->

<script setup>
import { useToast } from '@/Composables/useToast';

const { toast } = useToast();

// Exemples d'utilisation :

// 1. Toast de succès simple
toast.success('Opération réussie !');

// 2. Toast de succès avec titre
toast.success('Les données ont été sauvegardées', 'Succès');

// 3. Toast d'erreur
toast.error('Une erreur est survenue');

// 4. Toast d'avertissement
toast.warning('Attention, cette action est irréversible');

// 5. Toast d'information
toast.info('Nouvelle mise à jour disponible');

// 6. Toast personnalisé avec durée
toast.custom({
    type: 'success',
    title: 'Téléchargement terminé',
    message: 'Votre fichier a été téléchargé avec succès',
    duration: 10000 // 10 secondes
});

// 7. Toast qui ne se ferme pas automatiquement
toast.custom({
    type: 'warning',
    title: 'Action requise',
    message: 'Veuillez confirmer votre email',
    duration: 0 // Ne se ferme pas automatiquement
});
</script>

<!-- Dans un gestionnaire d'événement -->
<template>
  <button @click="toast.success('Bouton cliqué !')">
    Test Toast
  </button>
</template>
