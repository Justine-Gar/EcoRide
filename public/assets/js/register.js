// Fonctions utilitaires
function showError(message) {
  const errorDiv = document.getElementById('registerError');
  errorDiv.textContent = message;
  errorDiv.classList.remove('d-none');
}

function hideError() {
  const errorDiv = document.getElementById('registerError');
  errorDiv.classList.add('d-none');
}

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPassword(password) {
  return password.length >= 8;
}

// Gestionnaire principal
document.addEventListener('DOMContentLoaded', function () {

  //recupere le formulaire d'inscription
  const registerForm = document.getElementById('registerForm');
  if(!registerForm) return;

  const submitButton = registerForm.querySelector('button[type="submit"]');

  //async qui gere la soumission du formulaire
  async function handleSubmit(e) {
    
    e.preventDefault();

    //requipere les donnée
    hideError();
    submitButton.disabled = true;
    submitButton.textContent = 'Inscription en cours...';
    //await fetch /register post json 
    // Récupère les valeurs des champs du formulaire
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
    
    // Validation des données côté client avant envoi
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
    
    // Prépare l'objet qui sera envoyé au serveur
    const formData = {
        firstname,
        name,
        email,
        phone_number,
        password,
        confirm_password,
        terms,
        role
    };
    
    // Affiche les données pour le débogage (sans les mots de passe)
    console.log('Données du formulaire :', {
        ...formData,
        password: '[PRÉSENT]',
        confirm_password: '[PRÉSENT]'
    });

    try {
      // Envoi des données au serveur via fetch API
      const response = await fetch('/register', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify(formData)
      });

      console.log('Status de la réponse:', response.status);
      
      // Récupère la réponse brute et tente de la convertir en JSON
      const responseText = await response.text();
      console.log('Réponse brute:', responseText);
      
      let data;
      try {
          data = JSON.parse(responseText);
          console.log('Données parsées:', data);
      } catch (e) {
          console.error('Erreur de parsing JSON:', e);
          showError('Erreur de format de réponse');
          submitButton.disabled = false;
          submitButton.textContent = 'S\'inscrire';
          return;
      }

      if (data.success) {
         // Ferme la modal d'inscription
        const registerModal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
        registerModal.hide();
        
        // Réinitialise le formulaire d'inscription
        registerForm.reset();
        
        // Ouvre la modal de connexion
        const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
        
        // Prépare le message de succès pour la modal de connexion
        const loginMessageElement = document.createElement('div');
        loginMessageElement.className = 'alert alert-success mb-3';
        loginMessageElement.textContent = 'Inscription réussie ! Vous pouvez maintenant vous connecter avec vos identifiants.';
        
        // Trouve l'élément modal-body de la modal de connexion
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
        loginModal.show();
        
        // Optionnel: Focus sur le champ email pour faciliter la connexion
        setTimeout(() => {
            const emailField = document.getElementById('login-email');
            if (emailField) {
                emailField.focus();
                // Pré-remplir l'email avec celui de l'inscription
                emailField.value = email;
            }
        }, 500);
      } else {
          // Si l'inscription a échoué, affiche l'erreur
          showError(data.error || 'Une erreur est survenue lors de l\'inscription');
      }
    } catch (error) {
        // Gestion des erreurs lors de la requête
        console.error('Erreur complète:', error);
        showError('Une erreur est survenue. Veuillez réessayer.');
    } finally {
        // Réactive le bouton quelle que soit l'issue
        submitButton.disabled = false;
        submitButton.textContent = 'S\'inscrire';
    }
  }

  // Ajoute l'écouteur d'événement pour la soumission du formulaire
  registerForm.addEventListener('submit', handleSubmit);
    
  // Gère la réinitialisation du formulaire lorsque la modal est fermée
  const registerModal = document.getElementById('registerModal');
  if (registerModal) {
      registerModal.addEventListener('hidden.bs.modal', function () {
          registerForm.reset();
          hideError();
      });
  }
});