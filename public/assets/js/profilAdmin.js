document.addEventListener('DOMContentLoaded', function () {
    // === FONCTIONS UTILITAIRES ===

    //Affiche un message d'erreur
    function showModalError(message, modalId) {
        //supprime tout le smessage existant
        const existingAlert = document.querySelector(`#${modalId} .alert`);
        if (existingAlert) {
            existingAlert.remove();
        }
    
        //crée l'alerte danger
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger mb-3';
        errorDiv.textContent = message;
    
        //insère l'alerte au debut 
        const modalBody = document.querySelector(`#${modalId} .modal-body`);
        if (modalBody) {
            modalBody.insertBefore(errorDiv, modalBody.firstChild);
        }
    
        //timer sur l'alerte
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.classList.add('fade');
                setTimeout(() => {
                    if (errorDiv.parentNode) {
                        errorDiv.remove();
                    }
                }, 300);
            }
        }, 10000);
        
        return errorDiv;
    }
    
    //Affiche un message de succes
    function showModalSuccess(message, modalId) {
        const existingAlert = document.querySelector(`#${modalId} .alert`);
        if (existingAlert) {
            existingAlert.remove();
        }
    
        const successDiv = document.createElement('div');
        successDiv.className = 'alert alert-success mb-3';
        successDiv.textContent = message;
    
        const modalBody = document.querySelector(`#${modalId} .modal-body`);
        if (modalBody) {
            modalBody.insertBefore(successDiv, modalBody.firstChild);
        }
    
        setTimeout(() => {
            if (successDiv.parentNode) {
                successDiv.classList.add('fade');
                setTimeout(() => {
                    if (successDiv.parentNode) {
                        successDiv.remove();
                    }
                }, 300);
            }
        }, 5000);
        
        return successDiv;
    }
    
    //Masque tout les message alerte d'une modale
    function hideModalMessages(modalId) {
        const alertDivs = document.querySelectorAll(`#${modalId} .alert`);
        alertDivs.forEach(alertDiv => {
            alertDiv.classList.add('fade');
            setTimeout(() => alertDiv.remove(), 300);
        });
    }


    // ======== MODAL AJOUT EMPLOYÉ ========
    
    //Recupére le formu d'ajout employé
    const addEmployeeForm = document.getElementById('addEmployeeForm');
    //si form existe = evenement
    if (addEmployeeForm) {
        addEmployeeForm.addEventListener('submit', function (e) {
            // Empêche le rechargement de page
            e.preventDefault();

            // Récupéredu bouton pour le désactiver pendant la requête
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Traitement...';

            // Création d'un objet FormData
            const formData = new FormData(this);

            // Récupére le token CSRF si présent
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            // Config de la requête
            const options = {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };

            // Ajoute le token CSRF aux headers 
            if (csrfToken) {
                options.headers['X-CSRF-TOKEN'] = csrfToken;
            }

            // Envoi de la requête 
            fetch(addEmployeeForm.action, options)
                .then(response => {
                    // Vérifie du statut de la réponse
                    if (!response.ok) {
                        throw new Error('Erreur réseau');
                    }
                    return response.json();
                })
                .then(data => {
                    // Traitement des données reçues
                    if (data.success) {
                        // Créeation de l'alerte de succès
                        showModalSuccess(data.message || 'Employé ajouté avec succès !', 'addEmployeeModal');

                        // Réinit du form
                        addEmployeeForm.reset();

                        // Fermeture de la modal avec timer
                        setTimeout(() => {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('addEmployeeModal'));
                            if (modal) {
                                modal.hide();
                            }

                            // Rechargement de la page
                            window.location.reload();
                        }, 1500);
                    } else {
                        // Création d'une alerte d'erreur
                        showModalError(data.message || 'Une erreur est survenue', 'addEmployeeModal');
                    }
                })
                .catch(error => {
                    //console.error('Erreur:', error);
                    showModalError('Une erreur est survenue lors de la communication avec le serveur', 'addEmployeeModal');
                })
                .finally(() => {
                    // Réactivation du bouton de soumission
                    submitButton.disabled = false;
                    submitButton.textContent = 'Ajouter';
                });
        });
    }


    // ======== MODAL UPDATE EMPLOYÉ ========

    //Le bouton update
    const updateButtons = document.querySelectorAll('.update-employee');
    //modale et form
    const updateModal = new bootstrap.Modal(document.getElementById('updateEmployeeModal'));
    const updateForm = document.getElementById('updateEmployeeForm');

    //click bouton update
    updateButtons.forEach(button => {
        button.addEventListener('click', function () {
            const employeeId = this.dataset.id;

            const updateForm = document.getElementById('updateEmployeeForm');
            const updateSpinner = document.getElementById('updateSpinner');

            // Nettoyer les messages précédents
            hideModalMessages('updateEmployeeModal');
            
            // Afficher le spinner et masquer le formulaire
            updateSpinner.style.display = 'block';
            updateForm.style.display = 'none';

            // Afficher la modale
            updateModal.show();

            // Récupérer les données de l'employé via AJAX
            fetch(`/admin/gestion-employes/${employeeId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remplir le formulaire avec les données de l'employé
                    document.getElementById('update_employee_id').value = data.employee.id;
                    document.getElementById('update_employee_name').value = data.employee.name;
                    document.getElementById('update_employee_firstname').value = data.employee.firstname;
                    document.getElementById('update_employee_email').value = data.employee.email;
                    document.getElementById('update_employee_phone').value = data.employee.phone_number;
                    document.getElementById('update_employee_password').value = ''; // Vider le champ mot de passe

                    // Mettre à jour l'action du formulaire
                    updateForm.action = `/admin/gestion-employes/update/${employeeId}`;

                    // Masquer le spinner et afficher le formulaire
                    updateSpinner.style.display = 'none';
                    updateForm.style.display = 'block';
                } else {
                    // Masquer le spinner
                    updateSpinner.style.display = 'none';
                    
                    // Afficher l'erreur et ajouter un bouton pour réessayer
                    const errorAlert = showModalError(data.message || 'Erreur lors de la récupération des données', 'updateEmployeeModal');
                    
                    // Ajouter un bouton pour réessayer
                    const retryBtn = document.createElement('button');
                    retryBtn.className = 'btn btn-primary mt-2';
                    retryBtn.textContent = 'Réessayer';
                    retryBtn.onclick = () => {
                        // Supprimer l'alerte
                        if (errorAlert.parentNode) {
                            errorAlert.remove();
                            retryBtn.remove();
                        }
                        
                        // Réafficher le spinner
                        updateSpinner.style.display = 'block';
                        
                        // Réessayer après un court délai
                        setTimeout(() => button.click(), 300);
                    };
                    
                    // Ajouter le bouton après l'alerte
                    errorAlert.appendChild(document.createElement('br'));
                    errorAlert.appendChild(retryBtn);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                
                // Masquer le spinner
                updateSpinner.style.display = 'none';
                
                // Afficher l'erreur
                const errorAlert = showModalError('Une erreur est survenue lors de la récupération des données', 'updateEmployeeModal');
                
                // Ajouter un bouton pour réessayer
                const retryBtn = document.createElement('button');
                retryBtn.className = 'btn btn-primary mt-2';
                retryBtn.textContent = 'Réessayer';
                retryBtn.onclick = () => {
                    // Supprimer l'alerte et le bouton
                    if (errorAlert.parentNode) {
                        errorAlert.remove();
                        retryBtn.remove();
                    }
                    
                    // Réessayer après un court délai
                    setTimeout(() => button.click(), 300);
                };
                
                // Ajouter le bouton après l'alerte
                errorAlert.appendChild(document.createElement('br'));
                errorAlert.appendChild(retryBtn);
            });
        });
    });

    //envoie du form update
    if (updateForm) {
        updateForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');

            // Nettoyer les messages précédents
            hideModalMessages('updateEmployeeModal');

            // Désactiver le bouton pendant la soumission
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Traitement...';

            fetch(this.action, {
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
                    showModalSuccess(data.message || 'Employé mis à jour avec succès !', 'updateEmployeeModal');

                    // Fermer la modale après un délai
                    setTimeout(() => {
                        updateModal.hide();
                        // Recharger la page pour voir les modifications
                        window.location.reload();
                    }, 1500);
                } else {
                    // Afficher le message d'erreur
                    showModalError(data.message || 'Une erreur est survenue', 'updateEmployeeModal');

                    // Réactiver le bouton
                    submitButton.disabled = false;
                    submitButton.textContent = 'Mettre à jour';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showModalError('Une erreur est survenue lors de la communication avec le serveur', 'updateEmployeeModal');

                // Réactiver le bouton
                submitButton.disabled = false;
                submitButton.textContent = 'Mettre à jour';
            });
        });
    }


    // ======== MODAL SUPPRESSION EMPLOYÉ ========

    //Le bouton delete
    const deleteButtons = document.querySelectorAll('.delete-employee');
    //modal et form
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteEmployeeModal'));
    const deleteForm = document.getElementById('deleteEmployeeForm');

    //click bouton delete
    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
            const employeeId = this.dataset.id;
            const employeeName = this.closest('tr').querySelector('td:nth-child(2)').textContent;

            //Clean msg
            hideModalMessages('deleteEmployeeModal');

            //Update ltexte de confirmation
            document.getElementById('delete_employee_name').textContent = employeeName;

            // Update form
            deleteForm.action = `/admin/gestion-employes/delete/${employeeId}`;

            // Afficher la modale
            deleteModal.show();
        });
    });

    //envoie form delete
    if (deleteForm) {
        deleteForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');

            hideModalMessages('deleteEmployeeModal');

            //Désactiver le bouton 
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Suppression...';

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    //Afficher le message de succès
                    showModalSuccess(data.message || 'Employé supprimé avec succès !', 'deleteEmployeeModal');

                    //Désactive les boutons
                    this.querySelector('button[type="button"]').disabled = true;

                    //Fermer la modale
                    setTimeout(() => {
                        deleteModal.hide();
                        //Recharger la page
                        window.location.reload();
                    }, 1500);
                } else {
                    //Affiche le message d'erreur
                    showModalError(data.message || 'Une erreur est survenue', 'deleteEmployeeModal');

                    //Réactiv le bouton
                    submitButton.disabled = false;
                    submitButton.textContent = 'Supprimer';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showModalError('Une erreur est survenue lors de la communication avec le serveur', 'deleteEmployeeModal');

                //Réactiv le bouton
                submitButton.disabled = false;
                submitButton.textContent = 'Supprimer';
            });
        });
    }


    // ======== GESTION DES MOTS DE PASSE ========

    //Modal Add
    const toggleAddPassword = document.getElementById('toggleAddPassword');
    const addPassword = document.getElementById('add_employee_password');

    if (toggleAddPassword && addPassword) {
        toggleAddPassword.addEventListener('click', function () {
            const type = addPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            addPassword.setAttribute('type', type);

            // Changer l'icône
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }
    
    //Modal update
    const toggleUpdatePassword = document.getElementById('toggleUpdatePassword');
    const updatePassword = document.getElementById('update_employee_password');

    if (toggleUpdatePassword && updatePassword) {
        toggleUpdatePassword.addEventListener('click', function () {
            const type = updatePassword.getAttribute('type') === 'password' ? 'text' : 'password';
            updatePassword.setAttribute('type', type);

            // Changer l'icône
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }
});