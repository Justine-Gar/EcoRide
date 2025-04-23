// === FONCTIONS UTILITAIRES ===

//Affiche un message d'erreur
function showError(message) {
    // Supprime tout message existant
    const existingAlert = document.querySelector('#registerModal .alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // Crée la nouvelle alerte d'erreur
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger mb-3';
    errorDiv.textContent = message;
    
    // Trouve le modal-body et insère l'alerte au début
    const registerModalBody = document.querySelector('#registerModal .modal-body');
    if (registerModalBody) {
        registerModalBody.insertBefore(errorDiv, registerModalBody.firstChild);
    }
}

// Affiche un message de succès
function showSuccess(message) {
    // Supprime tout message existant
    const existingAlert = document.querySelector('#registerModal .alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // Crée la nouvelle alerte de succès
    const successDiv = document.createElement('div');
    successDiv.className = 'alert alert-success mb-3';
    successDiv.textContent = message;
    
    // Trouve le modal-body et insère l'alerte au début
    const registerModalBody = document.querySelector('#registerModal .modal-body');
    if (registerModalBody) {
        registerModalBody.insertBefore(successDiv, registerModalBody.firstChild);
    }
}

//Masque le message erreur
function hideMessages() {
    const alertDiv = document.querySelector('#registerModal .alert');
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
    //Recupere le formulaire d'inscription
    const registerForm = document.getElementById('registerForm');
    if (!registerForm) return; //si pas de formulaire on sort

    const submitButton = registerForm.querySelector('button[type="submit"]');

    //Fonction qui gère la soumission du formulaire
    async function handleSubmit(e) {
        //Empeche le rechargement de la page
        e.preventDefault();

        //Réinitialise l'interface (modal)
        hideMessages();
        submitButton.disabled = true;
        submitButton.textContent = 'Inscription en cours...';

        // == Etape 1 : Récupere les donnée du formulaire
        const firstname = document.getElementById('firstname').value;
        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const phone_number = document.getElementById('phone_number').value;
        const password = document.getElementById('register-password').value;
        const confirm_password = document.getElementById('confirm-password').value;
        const terms = document.getElementById('terms').checked;

        // Récupère le rôle sélectionné
        const rolePassager = document.getElementById('role-passager');
        const roleConducteur = document.getElementById('role-conducteur');
        const role = roleConducteur.checked ? 'Conducteur' : 'Passager';

        // == Etape 2 : Validation des données avant envoi
        if (!firstname || !name || !email || !phone_number || !password || !confirm_password) {
            showError('Tous les champs sont obligatoires');
            submitButton.disabled = false;
            submitButton.textContent = 'S\'inscrire';
            return;
        }

        if (!isValidEmail(email)) {
            showError('Veuillez saisir un email valide');
            submitButton.disabled = false;
            submitButton.textContent = 'S\'inscrire';
            return;
        }

        if (password !== confirm_password) {
            showError('Les mots de passe ne correspondent pas');
            submitButton.disabled = false;
            submitButton.textContent = 'S\'inscrire';
            return;
        }

        if (!terms) {
            showError('Vous devez accepter les conditions d\'utilisation');
            submitButton.disabled = false;
            submitButton.textContent = 'S\'inscrire';
            return;
        }

        // Prépare l'objet à envoyer au serveur
        const formData = {
            firstname, name, email, phone_number, password,
            confirm_password, terms, role
        };

        try {
            // == Etape 3 : Envoie des données au serveur
            const response = await fetch('/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(formData)
            });

            // == Etape 4 : Récuperer et analyser la réponse
            const responseText = await response.text();

            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                showError('Erreur de format de réponse');
                submitButton.disabled = false;
                submitButton.textContent = 'S\'inscrire';
                return;
            }

            // == Etape 5 :  Traitement de la réponse
            if (data.success) {
                // Affiche message de succès
                showSuccess('Inscription réussie ! Vous allez être redirigé vers la page de connexion.');
                // Attendre un court délai pour que l'utilisateur voie le message
                setTimeout(() => {
                    // Ferme la modal d'inscription
                    const registerModal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
                    registerModal.hide();
                    
                    // Réinitialise le formulaire d'inscription
                    registerForm.reset();
                    
                    // Prépare le message pour la modal de connexion
                    const loginMessageElement = document.createElement('div');
                    loginMessageElement.className = 'alert alert-success mb-3';
                    loginMessageElement.textContent = 'Inscription réussie ! Vous pouvez maintenant vous connecter avec vos identifiants.';
                    
                    // Trouve modal-body de la modal de connexion
                    const loginModalBody = document.querySelector('#loginModal .modal-body');
                    
                    // Vérifie si un message existe déjà et le supprime
                    const existingMessage = loginModalBody.querySelector('.alert');
                    if (existingMessage) {
                        existingMessage.remove();
                    }
                    
                    // Insère le nouveau message au début de la modal
                    if (loginModalBody) {
                        loginModalBody.insertBefore(loginMessageElement, loginModalBody.firstChild);
                    }
                    
                    // Affiche la modal de connexion
                    const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                    loginModal.show();
                }, 4000); // 4 secondes
            } else {
                //Affiche erreur si inscription a échoué
                showError(data.error || 'Une erreur est survenue lors de l\'inscription');
            }
        } catch (error) {
            //Gestion des erreurs
            //console.error('Erreur complète:', error);
            showError('Une erreur est survenue. Veuillez réessayer.');
        } finally {
            //Réactive le bouton dans tous les cas
            submitButton.disabled = false;
            submitButton.textContent = 'S\'inscrire';
        }
    }

    //Ajoute l'écouteur d'événement pour la soumission du formulaire
    registerForm.addEventListener('submit', handleSubmit);

    //Réinitialise le formulaire à la fermeture de la modal
    const registerModal = document.getElementById('registerModal');
    if (registerModal) {
        registerModal.addEventListener('hidden.bs.modal', function () {
            registerForm.reset();
            hideMessages();
        });
    }
});