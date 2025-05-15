//Evenement addReviews
document.addEventListener('DOMContentLoaded', function() {
    // Écouter les clics sur les boutons d'avis
    document.addEventListener('click', function(e) {
        // Vérifier si c'est un bouton d'avis (classe show-review-modal)
        if (e.target && e.target.classList.contains('show-review-modal')) {
            const carpoolId = e.target.dataset.carpoolId;
            //console.log('Ouverture de la modal pour le covoiturage:', carpoolId);
            
            // Mettre à jour l'action du formulaire avec l'ID du covoiturage
            const form = document.getElementById('review-form');
            if (form) {
                form.action = form.action.replace(/\/\d+$/, `/${carpoolId}`);
                //console.log('Action du formulaire mise à jour:', form.action);
                
                // Réinitialiser le formulaire
                form.reset();
            }
        }
    });
    
    // Gérer la soumission du formulaire
    const reviewForm = document.getElementById('review-form');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Extraire l'ID du covoiturage de l'URL du formulaire
            const carpoolId = this.action.match(/\/(\d+)$/)[1];
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            
            // Désactiver le bouton pendant la soumission
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Envoi...';
            }
            
            // Utiliser des backticks pour l'interpolation de chaîne
            fetch(`/review/submit/${carpoolId}`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Afficher le message de succès
                    const modal = document.getElementById('reviewModalContent');
                    modal.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    
                    // Fermer la modal après un délai
                    setTimeout(() => {
                        const bsModal = bootstrap.Modal.getInstance(document.getElementById('addReviewCarpoolModal'));
                        bsModal.hide();
                        
                        // Optionnel : recharger la page pour mettre à jour l'interface
                        window.location.reload();
                    }, 5000);
                } else {
                    // Réactiver le bouton en cas d'erreur
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = 'Envoyer';
                    }
                    
                    // Afficher le message d'erreur
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                
                // Réactiver le bouton
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = 'Envoyer';
                }
                
                const errorMessage = document.createElement('div');
                errorMessage.className = 'alert alert-danger';
                errorMessage.textContent = error.message || 'Une erreur est survenue lors de l\'envoi du formulaire.';
                
                const modal = document.getElementById('reviewModalContent');
                modal.innerHTML = '';
                modal.appendChild(errorMessage);
            });
        });
    }
});

//Evenement addReport
document.addEventListener('DOMContentLoaded', function() {
    // Écouter les clics sur les boutons d'avis
    document.addEventListener('click', function(e) {
        // Vérifier si c'est un bouton de signalement
        if (e.target && e.target.classList.contains('show-report-modal')) {
            const carpoolId = e.target.dataset.carpoolId;
            //console.log('Ouverture de la modal pour le covoiturage:', carpoolId);
            
            // Mettre à jour l'action du formulaire avec l'ID du covoiturage
            const form = document.getElementById('report-form');
            if (form) {
                form.action = form.action.replace(/\/\d+$/, `/${carpoolId}`);
                //console.log('Action du formulaire mise à jour:', form.action);
                
                // Réinitialiser le formulaire
                form.reset();
            }
        }
    });
    
    // Gérer la soumission du formulaire
    const reportForm = document.getElementById('report-form');
    if (reportForm) {
        reportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Extraire l'ID du covoiturage de l'URL du formulaire
            const carpoolId = this.action.match(/\/(\d+)$/)[1];
            const formData = new FormData(this);
            formData.append('carpool_id', carpoolId);
            const submitButton = this.querySelector('button[type="submit"]');
            
            // Désactiver le bouton pendant la soumission
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Envoi...';
            }
            
            // Utiliser des backticks pour l'interpolation de chaîne
            fetch(`/review/report/${carpoolId}`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Afficher le message de succès
                    const modal = document.getElementById('reportModalContent');
                    modal.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    
                    // Fermer la modal après un délai
                    setTimeout(() => {
                        const bsModal = bootstrap.Modal.getInstance(document.getElementById('reportCarpoolModal'));
                        bsModal.hide();
                        
                        // Optionnel : recharger la page pour mettre à jour l'interface
                        window.location.reload();
                    }, 5000);
                } else {
                    // Réactiver le bouton en cas d'erreur
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = 'Envoyer';
                    }
                    
                    // Afficher le message d'erreur
                    alert(data.message || 'Une erreur est survenue');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                
                // Réactiver le bouton
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = 'Envoyer';
                }
                
                const errorMessage = document.createElement('div');
                errorMessage.className = 'alert alert-danger';
                errorMessage.textContent = error.message || 'Une erreur est survenue lors de l\'envoi du formulaire.';
                
                const modal = document.getElementById('reportModalContent');
                modal.innerHTML = '';
                modal.appendChild(errorMessage);
            });
        });
    }
});