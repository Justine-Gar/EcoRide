// === FONCTIONS UTILITAIRES ===

//Affiche un message d'erreur
function showError(message) {
    const errorDiv = document.getElementById('loginError');
    errorDiv.textContent = message;
    errorDiv.classList.remove('d-none');
}
//Masque le message erreur
function hideError() {
    const errorDiv = document.getElementById('loginError');
    errorDiv.classList.add('d-none');
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
        hideError();
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

            //console.log('Status de la réponse:', response.status);
            
            // == ETAPE 2 : Analyse de la repônse
            const responseText = await response.text();
            //console.log('Réponse brute:', responseText);
            
            // == ETAPE 3: Conversion de la réponse en objet JSON
            let data;
            try {
                data = JSON.parse(responseText);
                //console.log('Données parsées:', data);
            } catch (e) {
                //console.error('Erreur de parsing JSON:', e);
                showError('Erreur de format de réponse');
                return;
            }

            // == ETAPE 4 : Traitement de la reponse
            if (data.success) {
                //si connexion ok, redirection
                //console.log('Redirection vers:', data.redirect);
                window.location.href = data.redirect;
            } else {
                //si echec, afficher message erreur
                showError(data.message || 'Erreur de connexion');
            }
        } catch (error) {
            //Gestion des erreurs
            //console.error('Erreur complète:', error);
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
        hideError();
    });
});