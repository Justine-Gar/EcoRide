document.addEventListener('DOMContentLoaded', function () {
 // Chargement des graphiques uniquement
  fetchCarpoolsData();
  fetchCreditsData();
  addRefreshButton();


  
// === FONCTIONS PRINCIPALES ===
  // Fonction pour récupérer et afficher les données des covoiturages
  function fetchCarpoolsData() {

    showLoadingSpinner('carpoolChart');

    fetch('/admin/carpools-data')
      .then(response => response.json())
      .then(data => {

        hideLoadingSpinner('carpoolChart');
        const ctx = document.getElementById('carpoolChart').getContext('2d');

        // Détruire le graphique existant s'il existe
        if (window.carpoolChartInstance) {
          window.carpoolChartInstance.destroy();
        }

        window.carpoolChartInstance = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: data.labels,
            datasets: [{
              label: 'Covoiturages créés',
              data: data.datasets[0].data,
              backgroundColor: 'rgba(63, 123, 106, 0.6)',
              borderColor: 'rgba(63, 123, 106, 1)',
              borderWidth: 1,
              borderRadius: 4,
              hoverBackgroundColor: 'rgba(63, 123, 106, 0.8)'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'top',
                labels: {
                  boxWidth: 12,
                  usePointStyle: true,
                  pointStyle: 'circle'
                }
              },
              tooltip: {
                callbacks: {
                  label: function (context) {
                    const value = context.parsed.y;
                    return value === 0 ? 'Aucun covoiturage' : value + ' covoiturage(s)';
                  }
                }
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  stepSize: 1,
                  precision: 0
                },
                grid: {
                  drawBorder: false,
                  color: 'rgba(0, 0, 0, 0.05)'
                }
              },
              x: {
                grid: {
                  display: false,
                  drawBorder: false
                }
              }
            }
          }
        });

        // Mettre à jour les statistiques totales (si vous avez des éléments pour les afficher)
        updateStats('totalCarpools', data.stats.total);
        updateStats('avgCarpools', data.stats.average);

        // Afficher la date de mise à jour
        updateLastRefreshTime();
      })
      .catch(error => {
        console.error('Erreur lors de la récupération des données de covoiturages:', error);
        hideLoadingSpinner('carpoolChart');
        showErrorMessage('carpoolChart');
      });
  }
  //Fonction pour régupérer et afficher les données des crédits
  function fetchCreditsData() {

    showLoadingSpinner('creditsChart');

    fetch('/admin/credits-data')
      .then(response => response.json())
      .then(data => {

        hideLoadingSpinner('creditsChart');
        const ctx = document.getElementById('creditsChart').getContext('2d');
        window.creditsChartInstance = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: data.labels,
            datasets: [{
              label: 'Crédits reçus',
              data: data.datasets[0].data,
              backgroundColor: 'rgba(49, 58, 80, 0.6)',
              borderColor: 'rgba(49, 58, 80, 1)',
              borderWidth: 1,
              borderRadius: 4,
              hoverBackgroundColor: 'rgba(49, 58, 80, 0.8)'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'top',
                labels: {
                  boxWidth: 12,
                  usePointStyle: true,
                  pointStyle: 'circle'
                }
              },
              tooltip: {
                callbacks: {
                  label: function (context) {
                    const value = context.parsed.y;
                    return value === 0 ? 'Aucun crédit' : value + ' crédits';
                  }
                }
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  stepSize: 4
                },
                grid: {
                  drawBorder: false,
                  color: 'rgba(0, 0, 0, 0.05)'
                }
              },
              x: {
                grid: {
                  display: false,
                  drawBorder: false
                }
              }
            }
          }
        });

        // Mettre à jour les statistiques totales (si vous avez des éléments pour les afficher)
        updateStats('totalCredits', data.stats.total);
        updateStats('avgCredits', data.stats.average);

        // Afficher la date de mise à jour
        updateLastRefreshTime();
      })
      .catch(error => {
        console.error('Erreur lors de la récupération des données de crédits:', error);
        hideLoadingSpinner('carpoolChart');
        showErrorMessage('carpoolChart');
      });
  }



// === FONCTIONS UTILITAIRES ===

  // Mise à jour des statistiques
  function updateStats(id, value) {
    const element = document.getElementById(id);
    if (element) {
      element.textContent = value;
    }
  }
  
  function addRefreshButton() {
    const dashboardTitle = document.querySelector('.dashboard-title');
    if (!dashboardTitle) return;
    
    // Vérifier si le bouton existe déjà
    if (document.getElementById('refreshCharts')) return;
    
    // Créer le conteneur pour la dernière mise à jour et le bouton
    const refreshContainer = document.createElement('div');
    refreshContainer.className = 'refresh-container d-flex align-items-center';
    
    // Créer un élément pour afficher la dernière mise à jour
    const lastRefreshEl = document.createElement('small');
    lastRefreshEl.id = 'lastRefreshTime';
    lastRefreshEl.className = 'text-muted me-2';
    lastRefreshEl.textContent = 'Dernière mise à jour: ' + getCurrentTime();
    
    // Créer le bouton de rafraîchissement
    const refreshBtn = document.createElement('button');
    refreshBtn.id = 'refreshCharts';
    refreshBtn.className = 'btn btn-sm btn-outline-secondary ms-2';
    refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Rafraîchir';
    refreshBtn.addEventListener('click', function() {
      fetchCarpoolsData();
      fetchCreditsData();
      
      // Animation de rotation pour l'icône
      const icon = this.querySelector('i');
      icon.classList.add('fa-spin');
      setTimeout(() => {
        icon.classList.remove('fa-spin');
      }, 1000);
    });
    
    // Ajouter les éléments au conteneur
    refreshContainer.appendChild(lastRefreshEl);
    refreshContainer.appendChild(refreshBtn);
    
    // Ajouter le conteneur après le titre du tableau de bord
    dashboardTitle.parentNode.insertBefore(refreshContainer, dashboardTitle.nextSibling);
  }
  
  // Afficher l'heure actuelle formatée
  function getCurrentTime() {
    const now = new Date();
    return now.toLocaleTimeString('fr-FR', { 
      hour: '2-digit', 
      minute: '2-digit',
      second: '2-digit'
    });
  }
  
  // Mettre à jour l'affichage de la dernière mise à jour
  function updateLastRefreshTime() {
    const lastRefreshEl = document.getElementById('lastRefreshTime');
    if (lastRefreshEl) {
      lastRefreshEl.textContent = 'Dernière mise à jour: ' + getCurrentTime();
    }
  }
  
  // Afficher un spinner de chargement
  function showLoadingSpinner(chartId) {
    const chartCanvas = document.getElementById(chartId);
    if (!chartCanvas) return;
    
    const parent = chartCanvas.parentElement;
    
    // Créer le spinner s'il n'existe pas déjà
    if (!parent.querySelector('.chart-loading-spinner')) {
      const spinnerContainer = document.createElement('div');
      spinnerContainer.className = 'chart-loading-spinner position-absolute top-0 left-0 w-100 h-100 d-flex justify-content-center align-items-center bg-white bg-opacity-75';
      spinnerContainer.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div>';
      parent.style.position = 'relative';
      parent.appendChild(spinnerContainer);
    }
  }
  
  // Masquer le spinner de chargement
  function hideLoadingSpinner(chartId) {
    const chartCanvas = document.getElementById(chartId);
    if (!chartCanvas) return;
    
    const parent = chartCanvas.parentElement;
    const spinner = parent.querySelector('.chart-loading-spinner');
    if (spinner) {
      spinner.remove();
    }
  }
  
  // Afficher un message d'erreur
  function showErrorMessage(chartId) {
    const chartCanvas = document.getElementById(chartId);
    if (!chartCanvas) return;
    
    const parent = chartCanvas.parentElement;
    
    // Créer le message d'erreur
    if (!parent.querySelector('.chart-error-message')) {
      const errorContainer = document.createElement('div');
      errorContainer.className = 'chart-error-message alert alert-danger mt-2';
      errorContainer.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Erreur lors du chargement des données. <button class="btn btn-sm btn-outline-danger ms-2">Réessayer</button>';
      
      // Ajouter un gestionnaire de clic pour le bouton de réessai
      const retryButton = errorContainer.querySelector('button');
      retryButton.addEventListener('click', function() {
        errorContainer.remove();
        if (chartId === 'carpoolChart') {
          fetchCarpoolsData();
        } else if (chartId === 'creditsChart') {
          fetchCreditsData();
        }
      });
      
      parent.appendChild(errorContainer);
    }
  }

}); 
