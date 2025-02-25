// Fonctions utilitaires
function showError(message) {
  const errorDiv = document.getElementById('regiterError');
  errorDiv.textContent = message;
  errorDiv.classList.remove('d-none');
}

function hideError() {
  const errorDiv = document.getElementById('registerError');
  errorDiv.classList.add('d-none');
}

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPassword(password) {
  return password.length >= 8;
}

// Gestionnaire principal
document.addEventListener('DOMContentLoaded', function () {

  //recupere le formulaire d'inscription
  const registerForm = document.getElementById('registerForm');
  if(!registerForm) return;

  const submitButton =registerForm.querySelector('[button type="submit"]');

  //async qui gere la soumission du formulaire
  async function HandlSubmit(e) {
    
    e.preventDefault();
  }
})