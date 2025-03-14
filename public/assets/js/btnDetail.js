//Script qui gère l'affichage des détails pour chaque carte de trajet

function initDetailButtons() {
  document.querySelectorAll('.card').forEach(card => {
    const toggleButton = card.querySelector('.toggleDetailButton');
    if (!toggleButton) return; // Sécurité si le bouton n'existe pas
    
    const detailsContent = card.querySelector('.detailsContent');
    const creditFooter = card.querySelector('.card-footer span');

    // Ajouter l'écouteur d'événement (sans supprimer les anciens, car on vérifiera si la carte est déjà initialisée)
    if (!toggleButton.hasAttribute('data-initialized')) {
      toggleButton.addEventListener('click', (e) => {
        e.stopPropagation();
        
        // ETAPE 1 : Désactive tout les autres cartes
        document.querySelectorAll('.card').forEach(c => {
          c.classList.remove('active');
        });

        // ETAPE 2 : Active cette carte si on affiche les détails
        if (!detailsContent.classList.contains('visible')) {
          card.classList.add('active');
        }
        
        // ETAPE 3: Affiche et masque les détails pour cette carte
        detailsContent.classList.toggle('visible');

        // ETAPE 4 : Change le texte du bouton selon son état
        toggleButton.textContent = detailsContent.classList.contains('visible') 
                                  ? '- Détails' : '+ Détails';

        // ETAPE 5 : Affiche le crédit en bas de la carte
        if (creditFooter) {
          creditFooter.style.visibility = detailsContent.classList.contains('visible') 
                                      ? 'hidden' : 'visible';
        }
        
        // ETAPE 6 : Ferme les détails des toutes les autres cartes
        document.querySelectorAll('.card').forEach(otherCard => {
          if (otherCard !== card) {
            const otherDetails = otherCard.querySelector('.detailsContent');
            const otherButton = otherCard.querySelector('.toggleDetailButton');
            const otherCredit = otherCard.querySelector('.card-footer span');
            
            if (otherDetails) otherDetails.classList.remove('visible');
            if (otherButton) otherButton.textContent = '+ Détails';
            if (otherCredit) otherCredit.style.visibility = 'visible';
          }
        });
      });
      
      // Marquer le bouton comme initialisé
      toggleButton.setAttribute('data-initialized', 'true');
    }
  });
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', initDetailButtons);

// Exposer la fonction pour une utilisation externe
window.initDetailButtons = initDetailButtons;