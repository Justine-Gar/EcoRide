// Ce script gère le formulaire d'édition du profil et les alertes associées

document.addEventListener('DOMContentLoaded', function () {
    // === PARTIE 1: GESTION DU FORMULAIRE DE PROFIL ===

    // Récupération du formulaire de modification du profil
    const form = document.getElementById('profile-edit-form');
    const modalElement = document.getElementById('editProfileModal');

    // Si le formulaire existe, ajoute un gestionnaire d'événement
    if (form) {
        // ÉTAPE 1: Capture la soumission du formulaire
        form.addEventListener('submit', function (e) {
            console.log('Formulaire soumis');

            // ÉTAPE 2: Log des données pour débogage
            console.log('Données du formulaire:', {
                'firstname': this.querySelector('[name="user_profile[firstname]"]')?.value,
                'email': this.querySelector('[name="user_profile[email]"]')?.value,
                'file': this.querySelector('[name="user_profile[profilePicture]"]')?.files[0]
            });

            // Note: Le traitement réel se fait côté serveur via la soumission standard
            // du formulaire (pas d'appel fetch ici contrairement aux autres scripts)
        });
    } else {
        // Avertissement si le formulaire n'est pas trouvé
        console.warn('Formulaire non trouvé');
    }

    // === PARTIE 2: GESTION DES ALERTES ===

    // ÉTAPE 3: Fermeture automatique des alertes après 5 secondes
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            // Utilise l'API Bootstrap pour fermer l'alerte en douceur
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000); // 5000 ms = 5 secondes
    });

    // === PARTIE 3: GESTION DE LA MODAL ===

    // ÉTAPE 4: Ferme automatiquement la modal si une alerte de succès est présente
    const successMessages = document.querySelectorAll('.alert-success');
    if (successMessages.length > 0) {
        // Récupère l'instance Bootstrap de la modal
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            // Ferme la modal car l'opération est réussie
            modal.hide();
        }
    }
})