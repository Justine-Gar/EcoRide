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
    const registerForm = document.getElementById('registerForm');
    if (!registerForm) {
        console.log('Formulaire d\'inscription non trouvé');
        return;
    }

    const submitButton = registerForm.querySelector('button[type="submit"]');

    registerForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log('Début de l\'inscription...');

        // Désactive le bouton
        submitButton.disabled = true;
        submitButton.textContent = 'Inscription en cours...';

        // Récupère les données
        const formData = {
            firstname: document.getElementById('firstname').value,
            name: document.getElementById('name').value,
            email: document.getElementById('email').value,
            phone_number: document.getElementById('phone_number').value,
            password: document.getElementById('register-password').value,
            confirm_password: document.getElementById('confirm-password').value,
            terms: document.getElementById('terms').checked,
            role: document.getElementById('role-conducteur').checked ? 'Conducteur' : 'Passager'
        };

        console.log('Données à envoyer:', formData);

        try {
            console.log('Envoi de la requête...');
            
            const response = await fetch('/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(formData)
            });

            console.log('Réponse reçue, status:', response.status);
            
            const responseText = await response.text();
            console.log('Réponse brute:', responseText);

            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('Erreur parsing JSON:', e);
                throw new Error('Réponse invalide du serveur');
            }

            if (data.success) {
                console.log('Inscription réussie !');
                alert('Inscription réussie ! Vous pouvez maintenant vous connecter.');
                
                // Réinitialise le formulaire
                registerForm.reset();
            } else {
                console.error('Erreur d\'inscription:', data.error);
                alert('Erreur: ' + (data.error || 'Inscription échouée'));
            }
        } catch (error) {
            console.error('Erreur réseau:', error);
            alert('Erreur de connexion: ' + error.message);
        } finally {
            // Réactive le bouton
            submitButton.disabled = false;
            submitButton.textContent = 'S\'inscrire';
        }
    });
});