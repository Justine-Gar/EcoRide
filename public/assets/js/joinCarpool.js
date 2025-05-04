//Fonctions d'affichage des messages
function displayErrorMessage(message) {
  const alertContainer = document.querySelector('#alert_results_search');
  if (!alertContainer) return;

  const existingAlerts = alertContainer.querySelectorAll('.alert');
  existingAlerts.forEach(alert => alert.remove());

  const errorDiv = document.createElement('div');
  errorDiv.className = 'alert alert-warning mb-4';

  const alertParagraph = document.createElement('p');
  alertParagraph.className = 'mb-0';
  alertParagraph.textContent = message;

  errorDiv.appendChild(alertParagraph);
  alertContainer.insertBefore(errorDiv, alertContainer.firstChild);
  scrollWithOffset(errorDiv, 130)

  setTimeout(() => errorDiv.remove(), 10000);
}

function displaySuccessMessage(message) {
  const alertContainer = document.querySelector('#alert_results_search');
  if (!alertContainer) return;

  const existingAlerts = alertContainer.querySelectorAll('.alert');
  existingAlerts.forEach(alert => alert.remove());

  const successDiv = document.createElement('div');
  successDiv.className = 'alert alert-success mb-4';

  const alertParagraph = document.createElement('p');
  alertParagraph.className = 'mb-0';
  alertParagraph.textContent = message;

  successDiv.appendChild(alertParagraph);
  alertContainer.insertBefore(successDiv, alertContainer.firstChild);
  scrollWithOffset(successDiv, 130)

  setTimeout(() => successDiv.remove(), 10000);
}

function hideMessages() {
  const alertContainer = document.querySelector('#alert_results_search');
  if (!alertContainer) return;

  const alerts = alertContainer.querySelectorAll('.alert');
  alerts.forEach(alert => alert.remove());
}

function scrollWithOffset(element, offset = 100) {
  // Récupérer la position de l'élément par rapport au haut de la page
  const elementPosition = element.getBoundingClientRect().top;
  // Récupérer la position actuelle du scroll
  const offsetPosition = elementPosition + window.pageYOffset - offset;
  
  // Faire défiler la page en douceur
  window.scrollTo({
    top: offsetPosition,
    behavior: 'smooth'
  });
}

function displayMessage(message, type = 'warning') {
  if (type === 'success') {
    displaySuccessMessage(message);
  } else {
    displayErrorMessage(message);
  }
}

function joinCarpool(carpoolId) {
  displayMessage('Traitement en cours...', 'warning');

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

  const options = {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Content-Type': 'application/json'
    }
  };

  if (csrfToken) {
    options.headers['X-CSRF-TOKEN'] = csrfToken;
  }

  fetch(`/covoiturage/${carpoolId}/join`, options)
    .then(response => response.json())
    .then(data => {
      hideMessages();

      if (data.auth_required) {
        
        const loginModal = document.getElementById('loginModal');
        if (loginModal) {
          const loginModalBody = loginModal.querySelector('.modal-body');
          if (loginModalBody) {
            let errorMessage = loginModalBody.querySelector('.auth-error-message');
            if (!errorMessage) {
              errorMessage = document.createElement('div');
              errorMessage.className = 'alert alert-warning mb-3 auth-error-message';
              loginModalBody.insertBefore(errorMessage, loginModalBody.firstChild);
            }
            errorMessage.textContent = 'Vous devez être connecté pour participer à ce covoiturage.';
          }

          // Afficher la modal avec Bootstrap
          const modal = new bootstrap.Modal(loginModal);
          modal.show();
        } else {
          
          window.location.href = '/login';
        }
      } else if (data.success) {
        // Participation réussie
        displaySuccessMessage(data.message || 'Vous avez rejoint le covoiturage avec succès !');

        // Redirection vers le profil après un délai
        setTimeout(() => {
          window.location.href = '/profile';
        }, 5000);
      } else {
        // Erreur avec message
        displayErrorMessage(data.message || 'Une erreur est survenue lors de l\'inscription au covoiturage.');
      }
    })
    .catch(error => {
      console.error('Erreur:', error);
      hideMessages();
      displayErrorMessage('Une erreur de communication est survenue');
    });
}

document.addEventListener('DOMContentLoaded', function () {
  // Initialiser les boutons de participation
  const joinButtons = document.querySelectorAll('.join-carpool-btn');
  joinButtons.forEach(button => {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      const carpoolId = this.dataset.carpoolId;
      joinCarpool(carpoolId);
    });
  });

  // Initialiser les boutons de détails
  const detailButtons = document.querySelectorAll('.toggleDetailButton');
  detailButtons.forEach(button => {
    button.addEventListener('click', function () {
      const card = this.closest('.card-body');
      const detailSection = card.querySelector('.detailsContent');

      if (detailSection) {
        detailSection.classList.toggle('active');
        this.textContent = detailSection.classList.contains('active') ? '- Détails' : '+ Détails';
      }
    });
  });
});