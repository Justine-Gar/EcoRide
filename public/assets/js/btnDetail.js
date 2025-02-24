document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.card').forEach(card => {
    const toggleButton = card.querySelector('.toggleDetailButton');
    const detailsContent = card.querySelector('.detailsContent');
    const creditFooter = card.querySelector('.card-footer span');

    toggleButton.addEventListener('click', (e) => {
      e.stopPropagation();
      
      // Gestion de la classe active
      document.querySelectorAll('.card').forEach(c => {
        c.classList.remove('active');
      });

      if (!detailsContent.classList.contains('visible')) {
        card.classList.add('active');
      }
      
      // Toggle pour cette carte
      detailsContent.classList.toggle('visible');
      toggleButton.textContent = detailsContent.classList.contains('visible') ? '- Détails' : '+ Détails';
      creditFooter.style.visibility = detailsContent.classList.contains('visible') ? 'hidden' : 'visible';
      
      // Ferme les autres cartes
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