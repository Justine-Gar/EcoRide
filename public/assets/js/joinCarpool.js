function joinCarpool(carpoolId) {
  fetch(`/covoiturage/${carpoolId}/join`, {
      method: 'POST',
      headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/json'
      }
  })
  .then(response => response.json())
  .then(data => {
    if (data.auth_required) {
      //Met à jour le message de la modal
      const loginModalBody = document.querySelector('#loginModal .modal-body');
      if (loginModalBody) {
          //Créer / mettre à jour le message d'erreur
          let errorMessage = loginModalBody.querySelector('.auth-error-message');
          if (!errorMessage) {
              errorMessage = document.createElement('div');
              errorMessage.className = 'alert alert-warning mb-3 auth-error-message';
              loginModalBody.insertBefore(errorMessage, loginModalBody.firstChild);
          }
          errorMessage.textContent = 'Vous devez être connecté pour participer à ce covoiturage.';
      }
      
      const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
      loginModal.show();
        
    } else if (data.success) {
      setTimeout(() => {
        displayErrorMessage('Vous avez rejoint le covoiturage avec succès !')
        window.location.href = '/profile';
      }, 4000);
    } else {
        // Autres erreurs
        displayErrorMessage(data.message);
    }
  })
  .catch(error => {
      console.error('Erreur:', error);
      displayErrorMessage('Une erreur est survenue');
  });
}

function displayErrorMessage(message) {
  const alertResult = document.querySelector('#alert_results_search');

  if (alertResult) {
    let alertContainer = alertResult.querySelector('.alert');

    if (!alertContainer) {
      alertContainer = document.createElement('div');
      alertContainer.className = 'alert mb-4';
      alertResult.insertBefore(alertContainer, alertResult.firstChild);
    }
  }
  const alertParagraph = alertContainer.querySelector('p.mb-0') || document.createElement('p');
  alertParagraph.className = 'mb-0';
  alertParagraph.textContent = message;
    
  if (!alertContainer.contains(alertParagraph)) {
    alertContainer.appendChild(alertParagraph);
  }
    
    // Faire défiler jusqu'au message
  alertContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });

}

document.addEventListener('DOMContentLoaded', function() {
  const joinButtons = document.querySelectorAll('.join-carpool-btn');
  joinButtons.forEach(button => {
      button.addEventListener('click', function(e) {
          e.preventDefault();
          const carpoolId = this.dataset.carpoolId;
          joinCarpool(carpoolId);
      });
  });
});