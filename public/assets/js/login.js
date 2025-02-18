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
document.addEventListener('DOMContentLoaded', function() {
  const loginForm = document.getElementById('loginForm');

  // Gère la soumission du formulaire
  async function handleSubmit(e) {
      e.preventDefault();
      hideError();

      // Récupère et nettoie les valeurs
      const email = document.querySelector('input[name="_username"]').value.trim();
      const password = document.querySelector('input[name="_password"]').value.trim();
      const csrfToken = document.querySelector('input[name="_csrf_token"]').value;

      // Validation des champs
      if (!email || !password) {
          showError('Veuillez remplir tous les champs.');
          return;
      }

      if (!isValidEmail(email)) {
          showError('Veuillez entrer une adresse email valide.');
          return;
      }

      if (!isValidPassword(password)) {
          showError('Le mot de passe doit contenir au moins 6 caractères.');
          return;
      }

      // Gère le bouton de soumission
      const submitButton = document.querySelector('.btn-connexion');
      submitButton.disabled = true;
      submitButton.textContent = 'Connexion en cours...';

      try {
          const response = await fetch('/login', {
              method: 'POST',
              body: FormData,
              credentials: 'same-origin'
              
          });

          const data = await response.json();
          
              
              if (data.success) {
                  // Redirection en cas de succès
                  window.location.pathname = data.redirect;
              } else {
                  // Affichage du message d'erreur du serveur
                  showError(data.message || 'Échec de la connexion. Veuillez réessayer.');
              }
          
      } catch (error) {
          console.error('Erreur de connexion:', error);
          showError('Impossible de se connecter au serveur. Veuillez réessayer plus tard.');
      } finally {
          // Restauration du bouton
          submitButton.disabled = false;
          submitButton.textContent = 'Se connecter';
      }
  }

  // Attache le gestionnaire au formulaire
  loginForm.addEventListener('submit', handleSubmit);

  // Réinitialise le formulaire quand la modal est fermée
  const loginModal = document.getElementById('loginModal');
  loginModal.addEventListener('hidden.bs.modal', function () {
      loginForm.reset();
      hideError();
  });
});