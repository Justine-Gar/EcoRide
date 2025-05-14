document.addEventListener('DOMContentLoaded', function () {


    // Variables pour stocker les marqueurs
    let departCoords = null;
    let arriveeCoords = null;

    // Géolocalisation pour le lieu de départ
    const departInput = document.getElementById('location_start');
    departInput.addEventListener('blur', function () {
        if (departInput.value.trim() !== '') {
            // Simuler des coordonnées (pour garder la compatibilité avec le backend)
            document.getElementById('lat_start').value = "0";
            document.getElementById('lng_start').value = "0";
            updateTripSummary();
        }
    });

    // Géolocalisation pour le lieu d'arrivée
    const arriveeInput = document.getElementById('location_reach');
    arriveeInput.addEventListener('blur', function () {
        if (arriveeInput.value.trim() !== '') {
            // Simuler des coordonnées (pour garder la compatibilité avec le backend)
            document.getElementById('lat_reach').value = "0";
            document.getElementById('lng_reach').value = "0";
            updateTripSummary();
        }
    });

    // Fonction pour mettre à jour le résumé du trajet
    function updateTripSummary() {
        const departValue = document.getElementById('location_start').value;
        const arriveeValue = document.getElementById('location_reach').value;
        const dateValue = document.getElementById('date_start').value;
        const timeValue = document.getElementById('hour_start').value;
        const placesValue = document.getElementById('nbr_places').value;
        const creditsValue = document.getElementById('credits').value;

        if (departValue && arriveeValue && dateValue && timeValue && placesValue && creditsValue) {
            // Formatage de la date
            const dateObj = new Date(dateValue + 'T' + timeValue);
            const dateFormatted = dateObj.toLocaleDateString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
            const timeFormatted = dateObj.toLocaleTimeString('fr-FR', {
                hour: '2-digit',
                minute: '2-digit'
            });

            // Mise à jour du résumé
            document.getElementById('summaryText').innerHTML = `
                <strong>De:</strong> ${departValue} <br>
                <strong>À:</strong> ${arriveeValue} <br>
                <strong>Le:</strong> ${dateFormatted} à ${timeFormatted} <br>
                <strong>Places disponibles:</strong> ${placesValue}
            `;

            document.getElementById('summaryPrice').textContent = `Prix: ${creditsValue} crédits par personne`;

            // Afficher le résumé
            document.getElementById('tripSummary').style.display = 'block';
        }
    }

    // Événements pour mettre à jour le résumé en temps réel
    document.getElementById('date_start').addEventListener('change', updateTripSummary);
    document.getElementById('hour_start').addEventListener('change', updateTripSummary);
    document.getElementById('nbr_places').addEventListener('change', updateTripSummary);
    document.getElementById('credits').addEventListener('change', updateTripSummary);

    // Gestion de la soumission du formulaire
    document.getElementById('createCarpoolForm').addEventListener('submit', function (e) {
        e.preventDefault();

        // Vérification des champs requis
        const requiredFields = [
            'location_start', 'location_reach', 'date_start', 'hour_start',
            'car_id', 'nbr_places', 'credits'
        ];

        let isValid = true;
        requiredFields.forEach(field => {
            if (!document.getElementById(field).value) {
                isValid = false;
                document.getElementById(field).classList.add('is-invalid');
            } else {
                document.getElementById(field).classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            alert('Veuillez remplir tous les champs obligatoires');
            return;
        }

        // Préparation des données pour correspondre à votre route
        const formData = new FormData();
        formData.append('location_start', document.getElementById('location_start').value);
        formData.append('lat_start', document.getElementById('lat_start').value);
        formData.append('lng_start', document.getElementById('lng_start').value);
        formData.append('location_reach', document.getElementById('location_reach').value);
        formData.append('lat_reach', document.getElementById('lat_reach').value);
        formData.append('lng_reach', document.getElementById('lng_reach').value);

        formData.append('date_start', document.getElementById('date_start').value);
        formData.append('hour_start', document.getElementById('hour_start').value);

        formData.append('car_id', document.getElementById('car_id').value);
        formData.append('nbr_places', document.getElementById('nbr_places').value);
        formData.append('credits', document.getElementById('credits').value);

        // Ajout du token CSRF
        const token = document.querySelector('input[name="_csrf_token"]').value;
        formData.append('_csrf_token', token);

        // Préférences (optionnel)
        const preferences = [];
        document.querySelectorAll('input[name="preferences[]"]:checked').forEach(checkbox => {
            preferences.push(checkbox.value);
        });

        if (preferences.length > 0) {
            // Ajouter les préférences au formulaire
            preferences.forEach((preference, index) => {
                formData.append(`preferences[${index}]`, preference);
            });
        }

        // Envoi des données
        fetch('/profile/new-carpool', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Fermer la modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('createTripModal'));
                    if (modal) {
                        modal.hide();
                    }

                    // Redirection ou rafraîchissement
                    setTimeout(() => {
                        window.location.href = data.redirect || '/profile';
                    }, 2000);
                } else {
                    // Notification d'erreur précise
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur détaillée:', error);
                alert('Erreur: ' + error.message);
            });
    });
});