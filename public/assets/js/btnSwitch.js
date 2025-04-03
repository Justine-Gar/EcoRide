function activateOption(option) {
  const container = document.querySelector('.switch-container');
  
  document.getElementById('role-display').textContent = 'Role actuel : ' + option.charAt(0).toUpperCase() + option.slice(1);
  
  if (option === 'conducteur') {
      container.classList.remove('passager-active');
      container.classList.add('conducteur-active');
      document.querySelector('.option-conducteur').classList.add('option-active');
      document.querySelector('.option-passager').classList.remove('option-active');
  } else {
      container.classList.remove('conducteur-active');
      container.classList.add('passager-active');
      document.querySelector('.option-passager').classList.add('option-active');
      document.querySelector('.option-conducteur').classList.remove('option-active');
  }
}