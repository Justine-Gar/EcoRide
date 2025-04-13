function activateOption(option) {
  const container = document.querySelector('.switch-container');
  
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
  
}