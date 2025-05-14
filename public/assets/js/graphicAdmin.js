document.addEventListener('DOMContentLoaded', function () {
  // Graphique des covoiturages par jour
  fetchCarpoolsData();

  // Graphique des crédits par jour
  fetchCreditsData();

  // Fonction pour récupérer et afficher les données des covoiturages
  function fetchCarpoolsData() {
    fetch('/admin/carpools-data')
      .then(response => response.json())
      .then(data => {
        const ctx = document.getElementById('carpoolChart').getContext('2d');
        new Chart(ctx, {
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
      })
      .catch(error => console.error('Erreur lors de la récupération des données de covoiturages:', error));
  }
  //Fonction pour régupérer et afficher les données des crédits
  function fetchCreditsData() {
    fetch('/admin/credits-data')
      .then(response => response.json())
      .then(data => {
        const ctx = document.getElementById('creditsChart').getContext('2d');
        new Chart(ctx, {
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
      })
      .catch(error => console.error('Erreur lors de la récupération des données de crédits:', error));
  }

  // Mise à jour des statistiques
  function updateStats(id, value) {
    const element = document.getElementById(id);
    if (element) {
      element.textContent = value;
    }
  }
  
}); 
