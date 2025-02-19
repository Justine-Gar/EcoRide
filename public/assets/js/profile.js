document.addEventListener('DOMContentLoaded', function() {
  // Récupération des éléments
  const form = document.getElementById('profile-edit-form');
  const modalElement = document.getElementById('editProfileModal');
  
  // Gestion du formulaire
  if (form) {
      form.addEventListener('submit', function(e) {
          console.log('Formulaire soumis');
          console.log('Données du formulaire:', {
              'firstname': this.querySelector('[name="user_profile[firstname]"]')?.value,
              'email': this.querySelector('[name="user_profile[email]"]')?.value,
              'file': this.querySelector('[name="user_profile[profilePicture]"]')?.files[0]
          });
      });
  } else {
      console.warn('Formulaire non trouvé');
  }

  // Gestion automatique des alertes
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(alert => {
      setTimeout(() => {
          const bsAlert = new bootstrap.Alert(alert);
          bsAlert.close();
      }, 5000);
  });

  // Fermeture de la modal en cas de succès
  const successMessages = document.querySelectorAll('.alert-success');
  if (successMessages.length > 0) {
      const modal = bootstrap.Modal.getInstance(modalElement);
      if (modal) {
          modal.hide();
      }
  }
});