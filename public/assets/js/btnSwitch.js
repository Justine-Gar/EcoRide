function activateOption(option) {
  const container = document.querySelector('.switch-container');

  // Déterminer quel rôle utiliser (première lettre en majuscule)
  const roleName = option === 'conducteur' ? 'Conducteur' : 'Passager';

  fetch(`/profile/switch-role/${roleName}`, {
    method: 'GET',
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
    .then(response => {
      //console.log('Réponse du serveur:', response.status);
      return response.json();
    })
    .then(data => {
      //console.log('Données reçues:', data);

      if (data.success) {
        // Changement visuel seulement si le changement de rôle a réussi
        document.getElementById('role-display').textContent = 'Role actuel : ' + option.charAt(0).toUpperCase() + option.slice(1);

        const btnRecherche = document.getElementById('btn-recherche');
        const btnTrajet = document.getElementById('btn-trajet');

        if (option === 'conducteur') {
          container.classList.remove('passager-active');
          container.classList.add('conducteur-active');
          document.querySelector('.option-conducteur').classList.add('option-active');
          document.querySelector('.option-passager').classList.remove('option-active');

          btnTrajet.style.display = 'inline-block';
          btnRecherche.style.display = 'none';
        } else {
          container.classList.remove('conducteur-active');
          container.classList.add('passager-active');
          document.querySelector('.option-passager').classList.add('option-active');
          document.querySelector('.option-conducteur').classList.remove('option-active');

          btnTrajet.style.display = 'none';
          btnRecherche.style.display = 'inline-block';
        }
        // Facultatif: afficher un message de réussite
        const flashContainer = document.querySelector('.flash-messages');
        if (flashContainer) {
          const successMessage = document.createElement('div');
          successMessage.className = 'alert alert-success';
          successMessage.textContent = `Vous êtes maintenant en mode ${option}`;
          flashContainer.appendChild(successMessage);

          // Faire disparaître le message après quelques secondes
          setTimeout(() => {
            successMessage.remove();
          }, 3000);
        }
      } else {
        console.error('Erreur:', data.error || 'Erreur non spécifiée');
        const flashContainer = document.querySelector('.flash-messages');
        if (flashContainer) {
          const errorMessage = document.createElement('div');
          errorMessage.className = 'alert alert-danger';
          errorMessage.textContent = `Erreur: ${data.error}`;
          flashContainer.appendChild(errorMessage);
        }
      }
    })
    .catch(error => {
      console.error('Erreur de fetch:', error);
    });
}


