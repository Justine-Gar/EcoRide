document.addEventListener('DOMContentLoaded', function () {
  // Sélectionner tous les boutons de détails
  const detailButtons = document.querySelectorAll('.load-carpool-details');

  // Ajouter un événement de clic à chaque bouton
  detailButtons.forEach(button => {
    button.addEventListener('click', function () {
      const carpoolId = this.getAttribute('data-carpool-id');
      const contentDiv = document.getElementById('carpoolDetailContent');

      // Afficher le spinner de chargement
      contentDiv.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div></div>';

      // Charger les détails via AJAX
      fetch(`/covoiturage/${carpoolId}/details-modal`)
        .then(response => {
          if (!response.ok) {
            throw new Error('Erreur réseau');
          }
          return response.text();
        })
        .then(html => {
          // Insérer le HTML dans la modal
          contentDiv.innerHTML = html;

          // Exécuter les scripts dans le contenu chargé
          const scripts = contentDiv.querySelectorAll('script');
          scripts.forEach(script => {
            const newScript = document.createElement('script');
            newScript.textContent = script.textContent;
            document.body.appendChild(newScript);
            document.body.removeChild(newScript);
          });
        })
        .catch(error => {
          contentDiv.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des détails</div>';
          console.error('Erreur:', error);
        });
    });
  });
});