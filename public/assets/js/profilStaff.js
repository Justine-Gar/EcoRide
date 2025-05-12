document.addEventListener('DOMContentLoaded', function() {
  // Vérifier si une section active est stockée
  const activeSection = sessionStorage.getItem('activeSection');
  
  if (activeSection) {
    loadSection(activeSection);
  } else {
    // Comportement par défaut : charger les avis
    loadSection('reviews');
  }
  
  // Stocker la section active lorsque l'utilisateur change d'onglet
  document.querySelectorAll('.btn-toggle-section').forEach(btn => {
    btn.addEventListener('click', function() {
      const section = this.id.replace('btn-', '');
      sessionStorage.setItem('activeSection', section);
    });
  });

  setupAlertTimers();
});

// Fonction pour afficher un message du serveur avant le rechargement
function showServerMessage(data) {
  // Si le serveur n'a pas envoyé de message, ne rien faire
  if (!data || !data.flashMessage) {
    window.location.reload();
    return;
  }
  
  // Supprimer les alertes existantes pour éviter la duplication
  const existingAlerts = document.querySelectorAll('.alert');
  existingAlerts.forEach(alert => alert.remove());
  
  // Créer l'élément d'alerte avec le message du serveur
  const alertDiv = document.createElement('div');
  alertDiv.className = `alert alert-${data.flashType || 'success'} alert-dismissible fade show`;
  alertDiv.setAttribute('role', 'alert');
  alertDiv.innerHTML = `
    ${data.flashMessage}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  `;
  
  // Insérer le message juste avant le conteneur dynamique
  const dynamicContent = document.querySelector('#dynamic-content');
  dynamicContent.parentNode.insertBefore(alertDiv, dynamicContent);
  
  // Faire défiler vers le haut de la page
  window.scrollTo({ top: 0, behavior: 'smooth' });
  
  // Ajouter un flag dans sessionStorage pour supprimer le message flash après rechargement
  sessionStorage.setItem('suppressNextFlash', 'true');
  
  // Recharger la page après un délai
  setTimeout(() => {
    window.location.reload();
  }, 1500);
}

// Fonction pour configurer les timers sur tous les messages d'alerte
function setupAlertTimers() {
  // Vérifier si nous devons supprimer les messages flash après un rechargement
  if (sessionStorage.getItem('suppressNextFlash') === 'true') {
    // Supprimer les messages flash générés par Symfony
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => alert.remove());
    
    // Supprimer l'indicateur pour que les messages s'affichent normalement la prochaine fois
    sessionStorage.removeItem('suppressNextFlash');
    return;
  }
  
  // Pour chaque alerte, configurer un timer de 5 secondes
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(alert => {
    setTimeout(() => {
      const bsAlert = new bootstrap.Alert(alert);
      bsAlert.close();
    }, 5000);
  });
}

//Evenement pour les détails des signalements
document.addEventListener('click', function(e) {
  const button = e.target.closest('.view-report-detail');
  if (!button) return;

  e.preventDefault();

  const reportId = button.dataset.reportId;
  const modalContent = document.getElementById('reportModalContent');

  // Afficher un spinner de chargement
  modalContent.innerHTML = `
  <div class="text-center">
    <div class="spinner-border" role="status">
      <span class="visually-hidden">Chargement...</span>
    </div>
  </div>`;

  // Charger les détails du signalement via AJAX
  fetch(`/staff/reports/${reportId}/details`)
    .then(response => {
      if (!response.ok) {
        throw new Error('Erreur réseau');
      }
      return response.text();
    })
    .then(html => {
      modalContent.innerHTML = html;
    })
    .catch(error => {
      console.error('Erreur:', error);
      modalContent.innerHTML = `
      <div class="alert alert-danger">
        Erreur lors du chargement des détails du signalement.
      </div>`;
    });
});

//Evenement pour les details des avis
document.addEventListener('click', function(e) {
  const button = e.target.closest('.view-review-detail');
  if (!button) return;
  
  e.preventDefault();
  
  const reviewId = button.dataset.reviewId;
  const modalContent = document.getElementById('reviewModalContent');
  
  // Afficher un spinner de chargement
  modalContent.innerHTML = `
  <div class="text-center">
    <div class="spinner-border" role="status">
      <span class="visually-hidden">Chargement...</span>
    </div>
  </div>`;

  // Charger les détails du signalement via AJAX
  fetch(`/staff/reviews/${reviewId}/details`)
    .then(response => {
      if (!response.ok) {
        throw new Error('Erreur réseau');
      }
      return response.text();
    })
    .then(html => {
      modalContent.innerHTML = html;
    })
    .catch(error => {
      console.error('Erreur:', error);
      modalContent.innerHTML = `
        <div class="alert alert-danger">
          Erreur lors du chargement des détails de l'avis.
        </div>`;
    });
});

// Evenement pour approuver un avis
document.addEventListener('click', function(e) {
  const button = e.target.closest('.approve-review');
  if (!button) return;
  
  e.preventDefault();
  
  const reviewId = button.dataset.reviewId;
  
  // Sauvegarder la section active dans sessionStorage
  sessionStorage.setItem('activeSection', 'reviews');
  
  // Afficher un spinner sur le bouton
  const originalButtonContent = button.innerHTML;
  button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
  button.disabled = true;
  
  fetch(`/staff/reviews/${reviewId}/approve`, {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Content-Type': 'application/json'
    }
  })
  .then(response => {
    if (!response.ok) throw new Error('Erreur réseau');
    return response.json();
  })
  .then(data => {
    if (data.success) {
      // Afficher le message du serveur avant de recharger la page
      showServerMessage({
        flashMessage: data.flashMessage || "L'avis a été approuvé avec succès.",
        flashType: "success"
      });
    } else {
      // Restaurer le bouton en cas d'erreur
      button.innerHTML = originalButtonContent;
      button.disabled = false;
      
      // Si le serveur a renvoyé un message d'erreur, l'afficher
      if (data.message) {
        showServerMessage({
          flashMessage: data.message,
          flashType: "danger"
        });
      }
    }
  })
  .catch(error => {
    console.error('Erreur:', error);
    
    // Restaurer le bouton en cas d'erreur
    button.innerHTML = originalButtonContent;
    button.disabled = false;
    
    // Recharger la page en cas d'erreur réseau
    window.location.reload();
  });
});

// Evenement pour rejeter un avis
document.addEventListener('click', function(e) {
  const button = e.target.closest('.reject-review');
  if (!button) return;
  
  e.preventDefault();
  
  const reviewId = button.dataset.reviewId;
  
  // Sauvegarder la section active dans sessionStorage
  sessionStorage.setItem('activeSection', 'reviews');
  
  // Afficher un spinner sur le bouton
  const originalButtonContent = button.innerHTML;
  button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
  button.disabled = true;
  
  fetch(`/staff/reviews/${reviewId}/reject`, {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Content-Type': 'application/json'
    }
  })
  .then(response => {
    if (!response.ok) throw new Error('Erreur réseau');
    return response.json();
  })
  .then(data => {
    if (data.success) {
      // Afficher le message du serveur avant de recharger la page
      showServerMessage({
        flashMessage: data.flashMessage || "L'avis a été rejeté.",
        flashType: "warning"
      });
    } else {
      // Restaurer le bouton en cas d'erreur
      button.innerHTML = originalButtonContent;
      button.disabled = false;
      
      // Si le serveur a renvoyé un message d'erreur, l'afficher
      if (data.message) {
        showServerMessage({
          flashMessage: data.message,
          flashType: "danger"
        });
      }
    }
  })
  .catch(error => {
    console.error('Erreur:', error);
    
    // Restaurer le bouton en cas d'erreur
    button.innerHTML = originalButtonContent;
    button.disabled = false;
    
    // Recharger la page en cas d'erreur réseau
    window.location.reload();
  });
});

// Evenement pour resoudre le signalement
document.addEventListener('click', function(e) {
  const button = e.target.closest('.resolve-report');
  if (!button) return;
  
  e.preventDefault();
  
  const reportId = button.dataset.reportId;
  const row = button.closest('tr');
  
  // Sauvegarder la section active dans sessionStorage
  sessionStorage.setItem('activeSection', 'reports');
  
  // Afficher un spinner sur le bouton
  const originalButtonContent = button.innerHTML;
  button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
  button.disabled = true;
  
  fetch(`/staff/reports/${reportId}/resolve`, {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Content-Type': 'application/json' 
    }
  })
  .then(response => {
    if (!response.ok) throw new Error('Erreur réseau');
    return response.json();
  })
  .then(data => {
    if (data.success) {
      // Mettre à jour visuellement la ligne dans le tableau
      if (row) {
        // Mettre à jour le statut à "publié"
        const statusCell = row.querySelector('td:nth-child(5)');
        if (statusCell) {
          statusCell.innerHTML = '<span class="badge bg-success">Publié</span>';
        }
        
        // Désactiver les boutons d'action
        const actionButtons = row.querySelectorAll('.btn-group .btn');
        actionButtons.forEach(btn => {
          btn.disabled = true;
          btn.classList.add('disabled');
        });
      }
      
      // Afficher le message du serveur avant de recharger la page
      showServerMessage({
        flashMessage: data.flashMessage || "Le signalement a été résolu avec succès.",
        flashType: "success"
      });
      
    } else {
      // Restaurer le bouton en cas d'erreur
      button.innerHTML = originalButtonContent;
      button.disabled = false;
      
      // Si le serveur a renvoyé un message d'erreur, l'afficher
      if (data.message) {
        showServerMessage({
          flashMessage: data.message,
          flashType: "danger"
        });
      }
    }
  })
  .catch(error => {
    console.error('Erreur:', error);
    
    // Restaurer le bouton en cas d'erreur
    button.innerHTML = originalButtonContent;
    button.disabled = false;
    
    // Recharger la page en cas d'erreur réseau
    window.location.reload();
  });
});

//Evenement pour signaler le danger a l'admin
document.addEventListener('click', function(e) {
  const button = e.target.closest('.danger-report');
  if (!button) return;

  e.preventDefault();

  const reportId = button.dataset.reportId;
  const row = button.closest('tr');

  sessionStorage.setItem('activeSection', 'reports');
  const originalButtonContent = button.innerHTML;
  button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
  button.disabled = true;

  fetch(`/staff/reports/${reportId}/danger`, {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Content-Type': 'application/json' 
    },
    body: JSON.stringify({})
  })
  .then(response => {
    if (!response.ok) throw new Error('Erreur réseau');
    return response.json();
  })
  .then(data => {
    if (data.success) {
      // Mettre à jour visuellement la ligne dans le tableau
      if (row) {
        // Mettre à jour le statut à "publié"
        const statusCell = row.querySelector('td:nth-child(5)');
        if (statusCell) {
          statusCell.innerHTML = '<span class="badge bg-danger">Danger</span>';
        }
        
        // Désactiver les boutons d'action
        const actionButtons = row.querySelectorAll('.btn-group .btn');
        actionButtons.forEach(btn => {
          btn.disabled = true;
          btn.classList.add('disabled');
        });
      }
      
      // Afficher le message du serveur avant de recharger la page
      showServerMessage({
        flashMessage: data.flashMessage || "L'utilisateur à été signalé à l'administration.",
        flashType: "success"
      });
      
    } else {
      // Restaurer le bouton en cas d'erreur
      button.innerHTML = originalButtonContent;
      button.disabled = false;
      
      // Si le serveur a renvoyé un message d'erreur, l'afficher
      if (data.message) {
        showServerMessage({
          flashMessage: data.message,
          flashType: "danger"
        });
      }
    }
  })
  .catch(error => {
    console.error('Erreur:', error);
    
    // Restaurer le bouton en cas d'erreur
    button.innerHTML = originalButtonContent;
    button.disabled = false;
    
    // Recharger la page en cas d'erreur réseau
    window.location.reload();
  });
});
//Fonction pour changer les sections
async function loadSection(section) {
  const container = document.getElementById('dynamic-content');
  container.innerHTML = ` 
    <div class="text-center my-4"> 
      <div class="spinner-border text-primary" role="status"> 
        <span class="visually-hidden">Chargement...</span> 
      </div> 
    </div>`;

  // Gestion des boutons actifs 
  const buttons = document.querySelectorAll('.btn-toggle-section');
  buttons.forEach(btn => btn.classList.remove('active'));
  document.getElementById(`btn-${section}`).classList.add('active');

  let url = '';

  if (section === 'reviews') {
    url = '/staff/reviews';
  } else if (section === 'reports') {
    url = '/staff/reports';
  }

  try {
    const response = await fetch(url);
    if (!response.ok) throw new Error("Erreur de chargement");

    const html = await response.text();
    container.innerHTML = html;

  } catch (error) {
    container.innerHTML = ` 
      <div class="alert alert-danger mt-3">Erreur lors du chargement du contenu.</div> `;
    console.error(error);
  }
}