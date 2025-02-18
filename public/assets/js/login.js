// Fonctions utilitaires
function showError(message) {
    const errorDiv = document.getElementById('loginError');
    errorDiv.textContent = message;
    errorDiv.classList.remove('d-none');
}

function hideError() {
    const errorDiv = document.getElementById('loginError');
    errorDiv.classList.add('d-none');
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPassword(password) {
    return password.length >= 10;
}

// Gestionnaire principal
document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('loginForm');
    const submitButton = loginForm.querySelector('.btn-connexion');
    // Gère la soumission du formulaire
    async function handleSubmit(e) {
        e.preventDefault();
        
        const errorDiv = document.getElementById('loginError');
        errorDiv.classList.add('d-none');
        
        submitButton.disabled = true;
        submitButton.textContent = 'Connexion en cours...';
        
        try {
            const formData = new FormData(loginForm);
            
            const response = await fetch('/login', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                errorDiv.textContent = data.message;
                errorDiv.classList.remove('d-none');
            }
        } catch (error) {
            console.error('Erreur:', error);
            errorDiv.textContent = 'Une erreur est survenue. Veuillez réessayer.';
            errorDiv.classList.remove('d-none');
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
        const errorDiv = document.getElementById('loginError');
        errorDiv.classList.add('d-none');
    });
});