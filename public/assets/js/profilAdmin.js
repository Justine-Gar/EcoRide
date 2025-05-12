document.addEventListener('DOMContentLoaded', function () {
    // Récupération du formulaire d'ajout d'employé
    const addEmployeeForm = document.getElementById('addEmployeeForm');

    // Si le formulaire existe, on ajoute un gestionnaire d'événement
    if (addEmployeeForm) {
        addEmployeeForm.addEventListener('submit', function (e) {
            // Empêche le comportement par défaut (rechargement de la page)
            e.preventDefault();

            // Récupération du bouton de soumission pour le désactiver pendant la requête
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Traitement...';

            // Création d'un objet FormData à partir du formulaire
            const formData = new FormData(this);

            // Récupération du token CSRF si présent dans la page
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            // Configuration de la requête fetch
            const options = {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };

            // Ajout du token CSRF aux headers si disponible
            if (csrfToken) {
                options.headers['X-CSRF-TOKEN'] = csrfToken;
            }

            // Envoi de la requête AJAX
            fetch(addEmployeeForm.action, options)
                .then(response => {
                    // Vérification du statut de la réponse
                    if (!response.ok) {
                        throw new Error('Erreur réseau');
                    }
                    return response.json();
                })
                .then(data => {
                    // Traitement des données reçues
                    if (data.success) {
                        // Création d'une alerte de succès
                        createAlert('success', data.message || 'Employé ajouté avec succès !');

                        // Réinitialisation du formulaire
                        addEmployeeForm.reset();

                        // Fermeture de la modal après un court délai
                        setTimeout(() => {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('addEmployeeModal'));
                            if (modal) {
                                modal.hide();
                            }

                            // Rechargement de la page pour afficher le nouvel employé
                            window.location.reload();
                        }, 1500);
                    } else {
                        // Création d'une alerte d'erreur
                        createAlert('danger', data.message || 'Une erreur est survenue');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    createAlert('danger', 'Une erreur est survenue lors de la communication avec le serveur');
                })
                .finally(() => {
                    // Réactivation du bouton de soumission
                    submitButton.disabled = false;
                    submitButton.textContent = 'Ajouter';
                });
        });
    }

    // Fonction pour créer une alerte dans la modal
    function createAlert(type, message) {
        // Suppression des alertes existantes
        const existingAlerts = document.querySelectorAll('#addEmployeeModal .alert');
        existingAlerts.forEach(alert => alert.remove());

        // Création de la nouvelle alerte
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} mt-3 mb-0`;
        alertDiv.textContent = message;

        // Ajout de l'alerte au début du formulaire
        const form = document.getElementById('addEmployeeForm');
        form.parentNode.insertBefore(alertDiv, form);

        // Disparition automatique après 5 secondes
        setTimeout(() => {
            alertDiv.classList.add('fade');
            setTimeout(() => alertDiv.remove(), 500);
        }, 5000);
    }
});

document.addEventListener('DOMContentLoaded', function () {
    // Sélectionner les boutons d'édition et de suppression
    const updateButtons = document.querySelectorAll('.update-employee');
    const deleteButtons = document.querySelectorAll('.delete-employee');

    // Référencer les modales et formulaires
    const updateModal = new bootstrap.Modal(document.getElementById('updateEmployeeModal'));
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteEmployeeModal'));
    const updateForm = document.getElementById('updateEmployeeForm');
    const deleteForm = document.getElementById('deleteEmployeeForm');

    // Fonction pour afficher des alertes
    function showAlert(container, type, message) {
        // Supprimer les alertes existantes
        const existingAlerts = container.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());

        // Créer la nouvelle alerte
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} mt-3`;
        alertDiv.textContent = message;

        // Ajouter l'alerte au conteneur
        container.insertBefore(alertDiv, container.firstChild);

        // Faire disparaître l'alerte après 5 secondes
        setTimeout(() => {
            alertDiv.classList.add('fade');
            setTimeout(() => alertDiv.remove(), 500);
        }, 5000);
    }

    // Gestion des clics sur les boutons d'édition
    updateButtons.forEach(button => {
        button.addEventListener('click', function () {
            const employeeId = this.dataset.id;

            // Récupérer les données de l'employé via AJAX
            fetch(`/admin/gestion-employes/${employeeId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                console.log('Statut de la réponse:', response.status);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    console.log("Données reçues:", data);
                    // Remplir le formulaire avec les données de l'employé
                    document.getElementById('update_employee_id').value = data.employee.id;
                    document.getElementById('update_employee_name').value = data.employee.name;
                    document.getElementById('update_employee_firstname').value = data.employee.firstname;
                    document.getElementById('update_employee_email').value = data.employee.email;
                    document.getElementById('update_employee_phone').value = data.employee.phone_number;
                    document.getElementById('update_employee_password').value = ''; // Vider le champ mot de passe

                    // Mettre à jour l'action du formulaire
                    updateForm.action = `/admin/gestion-employes/update/${employeeId}`;

                    // Afficher la modale
                    updateModal.show();
                } else {
                    // Afficher un message d'erreur
                    alert(data.message || 'Erreur lors de la récupération des données');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la récupération des données');
            });
        });
    });

    // Gestion des clics sur les boutons de suppression
    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
            const employeeId = this.dataset.id;
            const employeeName = this.closest('tr').querySelector('td:nth-child(2)').textContent;

            // Mettre à jour le texte de confirmation
            document.getElementById('delete_employee_name').textContent = employeeName;

            // Mettre à jour l'action du formulaire
            deleteForm.action = `/admin/gestion-employes/delete/${employeeId}`;

            // Afficher la modale de confirmation
            deleteModal.show();
        });
    });

    // Gestion de la soumission du formulaire de mise à jour
    if (updateForm) {
        updateForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');

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
                        showAlert(this.parentNode, 'success', data.message);

                        // Fermer la modale après un délai
                        setTimeout(() => {
                            updateModal.hide();
                            // Recharger la page pour voir les modifications
                            window.location.reload();
                        }, 1500);
                    } else {
                        // Afficher le message d'erreur
                        showAlert(this.parentNode, 'danger', data.message);

                        // Réactiver le bouton
                        submitButton.disabled = false;
                        submitButton.textContent = 'Mettre à jour';
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showAlert(this.parentNode, 'danger', 'Une erreur est survenue');

                    // Réactiver le bouton
                    submitButton.disabled = false;
                    submitButton.textContent = 'Mettre à jour';
                });
        });
    }

    // Gestion de la soumission du formulaire de suppression
    if (deleteForm) {
        deleteForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');

            // Désactiver le bouton pendant la soumission
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
                        // Afficher le message de succès
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success';
                        alertDiv.textContent = data.message;
                        this.parentNode.parentNode.querySelector('.modal-body').appendChild(alertDiv);

                        // Désactiver les boutons
                        this.querySelector('button[type="button"]').disabled = true;

                        // Fermer la modale après un délai
                        setTimeout(() => {
                            deleteModal.hide();
                            // Recharger la page pour mettre à jour la liste
                            window.location.reload();
                        }, 1500);
                    } else {
                        // Afficher le message d'erreur
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-danger';
                        alertDiv.textContent = data.message;
                        this.parentNode.parentNode.querySelector('.modal-body').appendChild(alertDiv);

                        // Réactiver le bouton
                        submitButton.disabled = false;
                        submitButton.textContent = 'Supprimer';
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger';
                    alertDiv.textContent = 'Une erreur est survenue';
                    this.parentNode.parentNode.querySelector('.modal-body').appendChild(alertDiv);

                    // Réactiver le bouton
                    submitButton.disabled = false;
                    submitButton.textContent = 'Supprimer';
                });
        });
    }

});

document.addEventListener('DOMContentLoaded', function () {
    // Pour la modale d'ajout
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
    
    // Pour la modale de mise à jour
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