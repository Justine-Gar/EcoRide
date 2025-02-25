// Fonctions utilitaires
//affiche msg erreur
function showError(message) {
    const errorDiv = document.getElementById('loginError');
    errorDiv.textContent = message;
    errorDiv.classList.remove('d-none');
}
//masque msg erreur
function hideError() {
    const errorDiv = document.getElementById('loginError');
    errorDiv.classList.add('d-none');
}
//valide format email
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}
//valide longeure minimal mdp
function isValidPassword(password) {
    return password.length >= 8;
}

// Gestionnaire principal
document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('loginForm');
    const submitButton = loginForm.querySelector('.btn-connexion');
    // Gère la soumission du formulaire
    async function handleSubmit(e) {
        e.preventDefault();
        
        hideError();
        submitButton.disabled = true;
        submitButton.textContent = 'Connexion en cours...';

        const formData = new FormData(loginForm);
        const email = formData.get('_username');
        const password = formData.get('_password');
        const csrf = formData.get('_csrf_token');
        
        // Vérification des données
        console.log('Données du formulaire :', {
            email: email,
            password: password ? '[PRÉSENT]' : '[MANQUANT]',
            csrf: csrf
        });

        try {
            
            const response = await fetch('/login', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            console.log('Status de la réponse:', response.status);
            
            // Log de la réponse complète
            const responseText = await response.text();
            console.log('Réponse brute:', responseText);
            
            // Essai de parsing JSON
            let data;
            try {
                data = JSON.parse(responseText);
                console.log('Données parsées:', data);
            } catch (e) {
                console.error('Erreur de parsing JSON:', e);
                showError('Erreur de format de réponse');
                return;
            }

            if (data.success) {
                console.log('Redirection vers:', data.redirect);
                window.location.href = data.redirect;
            } else {
                showError(data.message || 'Erreur de connexion');
            }
        } catch (error) {
            console.error('Erreur complète:', error);
            showError('Une erreur est survenue. Veuillez réessayer.');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Se connecter';
        }
    }
    
    loginForm.addEventListener('submit', handleSubmit);
    
    // Réinitialisation du formulaire à la fermeture de la modal
    const loginModal = document.getElementById('loginModal');
    loginModal.addEventListener('hidden.bs.modal', function () {
        loginForm.reset();
        hideError();
    });
});