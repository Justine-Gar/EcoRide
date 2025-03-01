//Script qui gère l'affichage des détails pour chaque carte de trajet

document.addEventListener('DOMContentLoaded', () => {
  //Sélectionne tooutes les caryes sur la page
  document.querySelectorAll('.card').forEach(card => {
    //pour chaque carte, recupère les éléments importants
    const toggleButton = card.querySelector('.toggleDetailButton');
    const detailsContent = card.querySelector('.detailsContent');
    const creditFooter = card.querySelector('.card-footer span');

    //ajoute un ecouteur d'évènement sur le bouton de détails
    toggleButton.addEventListener('click', (e) => {
      e.stopPropagation(); //Empeche la propagation du clic
      
      // == ETAPE 1 : Désactive tout les autres cartes
      document.querySelectorAll('.card').forEach(c => {
        c.classList.remove('active');
      });

      // == ETAPE 2 : Active cette carte si on affiche les détails
      if (!detailsContent.classList.contains('visible')) {
        card.classList.add('active');
      }
      
      // == ETAPE 3: Affiche et masque les détails pour cette carte
      detailsContent.classList.toggle('visible');

      // == ETAPE 4 : Change le texte du bouton selon son état
      toggleButton.textContent = detailsContent.classList.contains('visible') 
                                ? '- Détails' : '+ Détails';

      // == ETAPE 5 : Affiche le crédit en bas de la carte
      creditFooter.style.visibility = detailsContent.classList.contains('visible') 
                                    ? 'hidden' : 'visible';
      
      // == ETAPE 6 : Ferme les détails des toute les cartes
      document.querySelectorAll('.card').forEach(otherCard => {
        if (otherCard !== card) {
          const otherDetails = otherCard.querySelector('.detailsContent');
          const otherButton = otherCard.querySelector('.toggleDetailButton');
          const otherCredit = otherCard.querySelector('.card-footer span');
          
          otherDetails.classList.remove('visible');
          otherButton.textContent = '+ Détails';
          otherCredit.style.visibility = 'visible';
        }
      });
    });
  });
});