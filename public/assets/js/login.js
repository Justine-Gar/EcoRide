// === FONCTIONS UTILITAIRES ===

//Affiche un message d'erreur
function showError(message) {
    // Supprime tout message existant
    const existingAlert = document.querySelector('#loginModal .alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // Crée la nouvelle alerte d'erreur
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger mb-3';
    errorDiv.textContent = message;
    
    // Trouve le modal-body et insère l'alerte au début
    const loginModalBody = document.querySelector('#loginModal .modal-body');
    if (loginModalBody) {
        loginModalBody.insertBefore(errorDiv, loginModalBody.firstChild);
    }
}

// Affiche un message de succès
function showSuccess(message) {
    // Supprime tout message existant
    const existingAlert = document.querySelector('#loginModal .alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // Crée la nouvelle alerte de succès
    const successDiv = document.createElement('div');
    successDiv.className = 'alert alert-success mb-3';
    successDiv.textContent = message;
    
    // Trouve le modal-body et insère l'alerte au début
    const loginModalBody = document.querySelector('#loginModal .modal-body');
    if (loginModalBody) {
        loginModalBody.insertBefore(successDiv, loginModalBody.firstChild);
    }
}

//Masque le message erreur
function hideMessages() {
    const alertDiv = document.querySelector('#loginModal .alert');
    if (alertDiv) {
        alertDiv.remove();
    }
}

//Vérifie si email a un format valide
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}
//Vérifie si le mdp a au moin 8 caractères + 
function isValidPassword(password) {
    return password.length >= 8;
}

// === CODE PRINCIPAL ===
document.addEventListener('DOMContentLoaded', function () {
    //Recupere le formulaire de connexion
    const loginForm = document.getElementById('loginForm');
    const submitButton = loginForm.querySelector('.btn-connexion');

    //Fonction qui gère la soumission du formulaire
    async function handleSubmit(e) {
        //Empeche le rechargement de la page
        e.preventDefault();
        
        //Reinitialise l'interface (modal)
        hideMessages();
        submitButton.disabled = true;
        submitButton.textContent = 'Connexion en cours...';

        //Recupere les données du formulaire
        const formData = new FormData(loginForm);
        const email = formData.get('_username');
        const password = formData.get('_password');
        const csrf = formData.get('_csrf_token');
        
        //Affiche les données pour le débogage (sans les mots de passe)
        /*console.log('Données du formulaire :', {
            email: email,
            password: password ? '[PRÉSENT]' : '[MANQUANT]',
            csrf: csrf
        });*/

        try {
            // == ETAPE 1: Envoi des données au serveur
            const response = await fetch('/login', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            // == ETAPE 2 : Analyse de la repônse
            const responseText = await response.text();
            
            // == ETAPE 3: Conversion de la réponse en objet JSON
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                showError('Erreur de format de réponse');
                return;
            }

            // == ETAPE 4 : Traitement de la reponse
            if (data.success) {
                //Message de succès
                showSuccess('Connexion réussie ! Redirection en cours...');
                //si connexion ok, redirection
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 4000);

            } else {
                //si echec, afficher message erreur
                showError(data.message || 'Erreur de connexion');
            }
        } catch (error) {
            //Gestion des erreurs
            showError('Une erreur est survenue. Veuillez réessayer.');
        } finally {
            //Reactive le bouton dans tous els cas
            submitButton.disabled = false;
            submitButton.textContent = 'Se connecter';
        }
    }
    
    //Ajoute l'écouteur d'évènement pour la soumission du formulaire
    loginForm.addEventListener('submit', handleSubmit);
    
    //Réinitialise le formulaire à la fermeture de la modal
    const loginModal = document.getElementById('loginModal');
    loginModal.addEventListener('hidden.bs.modal', function () {
        loginForm.reset();
        hideMessages();
    });
});