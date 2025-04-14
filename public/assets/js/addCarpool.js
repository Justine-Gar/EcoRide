document.addEventListener('DOMContentLoaded', function () {
    // Initialisation de la carte
    let mapPreview = L.map('mapPreview').setView([48.8566, 2.3522], 12); // Vue centrée sur Paris

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(mapPreview);

    // Variables pour stocker les marqueurs
    let departMarker = null;
    let arriveeMarker = null;
    let routingControl = null;

    // Géolocalisation pour le lieu de départ
    const departInput = document.getElementById('location_start');
    departInput.addEventListener('blur', function () {
        if (departInput.value.trim() !== '') {
            geolocate(departInput.value, 'depart');
        }
    });

    // Géolocalisation pour le lieu d'arrivée
    const arriveeInput = document.getElementById('location_reach');
    arriveeInput.addEventListener('blur', function () {
        if (arriveeInput.value.trim() !== '') {
            geolocate(arriveeInput.value, 'arrivee');
        }
    });

    // Fonction de géolocalisation via Nominatim (OpenStreetMap)
    function geolocate(address, type) {
        const apiUrl = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`;

        fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    const result = data[0];
                    const lat = parseFloat(result.lat);
                    const lng = parseFloat(result.lon);

                    if (type === 'depart') {
                        // Mise à jour des champs cachés
                        document.getElementById('lat_start').value = lat;
                        document.getElementById('lng_start').value = lng;

                        // Ajout/mise à jour du marqueur de départ
                        if (departMarker) {
                            departMarker.setLatLng([lat, lng]);
                        } else {
                            departMarker = L.marker([lat, lng], {
                                icon: L.divIcon({
                                    html: '<i class="fas fa-map-marker-alt fa-2x" style="color: #28a745;"></i>',
                                    iconSize: [20, 20],
                                    className: 'map-marker'
                                })
                            }).addTo(mapPreview);
                        }

                        mapPreview.setView([lat, lng], 10);
                    } else if (type === 'arrivee') {
                        // Mise à jour des champs cachés
                        document.getElementById('lat_reach').value = lat;
                        document.getElementById('lng_reach').value = lng;

                        // Ajout/mise à jour du marqueur d'arrivée
                        if (arriveeMarker) {
                            arriveeMarker.setLatLng([lat, lng]);
                        } else {
                            arriveeMarker = L.marker([lat, lng], {
                                icon: L.divIcon({
                                    html: '<i class="fas fa-flag-checkered fa-2x" style="color: #dc3545;"></i>',
                                    iconSize: [20, 20],
                                    className: 'map-marker'
                                })
                            }).addTo(mapPreview);
                        }
                    }

                    // Si les deux marqueurs sont présents, tracer l'itinéraire
                    if (departMarker && arriveeMarker) {
                        calculateRoute();
                        updateTripSummary();
                    }
                }
            })
            .catch(error => {
                console.error('Erreur de géolocalisation:', error);
            });
    }

    // Fonction pour calculer l'itinéraire entre les deux points
    function calculateRoute() {
        const startLatLng = departMarker.getLatLng();
        const endLatLng = arriveeMarker.getLatLng();

        // Si un itinéraire est déjà affiché, le supprimer
        if (routingControl) {
            mapPreview.removeControl(routingControl);
        }

        // Ajout du contrôle d'itinéraire (nécessite le plugin Leaflet Routing Machine)
        routingControl = L.Routing.control({
            waypoints: [
                L.latLng(startLatLng.lat, startLatLng.lng),
                L.latLng(endLatLng.lat, endLatLng.lng)
            ],
            lineOptions: {
                styles: [{ color: '#6FA1EC', weight: 4 }]
            },
            show: false,
            addWaypoints: false,
            routeWhileDragging: false,
            draggableWaypoints: false,
            fitSelectedRoutes: true,
            showAlternatives: false
        }).addTo(mapPreview);

        // Ajuster la vue pour montrer tout l'itinéraire
        const bounds = L.latLngBounds([startLatLng, endLatLng]);
        mapPreview.fitBounds(bounds, { padding: [50, 50] });
    }

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
        document.querySelectorAll('input[name="user_preferences[]"]:checked').forEach(checkbox => {
            preferences.push(checkbox.value);
        });
        if (preferences.length > 0) {
            formData.append('commentaire', 'Préférences: ' + preferences.join(', '));
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

    // Réinitialisation de la carte à l'ouverture de la modal
    document.getElementById('createTripModal').addEventListener('show.bs.modal', function () {
        setTimeout(() => {
            mapPreview.invalidateSize();
            // Réinitialiser la vue de la carte
            mapPreview.setView([46.603354, 1.888334], 6);
        }, 100);
    });
});