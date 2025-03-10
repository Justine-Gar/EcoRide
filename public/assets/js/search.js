// Initialisation de la carte avec Leaflet
document.addEventListener('DOMContentLoaded', function () {
  // Vérifier si l'élément map existe sur la page
  if (document.getElementById('map')) {
    // Initialiser la carte centrée sur la France
    const map = L.map('map').setView([46.603354, 1.888334], 6);

    // Ajouter la couche OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 18
    }).addTo(map);

    // Variables pour stocker les marqueurs
    let markers = [];
    let departureMark = null;
    let arrivalMark = null;

    // Récupérer les éléments du formulaire selon votre structure HTML
    const departureInput = document.querySelector('input[name="depart"]');
    const arrivalInput = document.querySelector('input[name="arrivee"]');
    const dateInput = document.querySelector('input[name="date"]');
    const searchForm = document.querySelector('.search-bar');
    const searchButton = document.querySelector('.btn-search');

    // Initialisation des champs d'adresse avec autocomplete
    if (departureInput) initAddressAutocomplete(departureInput);
    if (arrivalInput) initAddressAutocomplete(arrivalInput);

    // Fonction pour initialiser l'autocomplete d'adresse
    function initAddressAutocomplete(input) {
      // Créer un élément caché pour stocker la latitude
      const latInput = document.createElement('input');
      latInput.type = 'hidden';
      latInput.name = input.name + '_lat';
      input.parentNode.appendChild(latInput);

      // Créer un élément caché pour stocker la longitude
      const lngInput = document.createElement('input');
      lngInput.type = 'hidden';
      lngInput.name = input.name + '_lng';
      input.parentNode.appendChild(lngInput);

      // Écouter les événements de saisie
      input.addEventListener('input', debounce(function () {
        if (input.value.length < 3) {
          // Supprimer la liste de suggestions si elle existe
          const existingSuggestions = document.getElementById(`${input.name}_suggestions`);
          if (existingSuggestions) existingSuggestions.remove();
          return;
        }

        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(input.value)}&limit=5&countrycodes=fr`)
          .then(response => response.json())
          .then(data => {
            showAddressSuggestions(data, input, latInput, lngInput);
          })
          .catch(error => console.error('Erreur:', error));
      }, 500));
    }

    // Afficher les suggestions d'adresse
    function showAddressSuggestions(addresses, input, latInput, lngInput) {
      // Supprimer la liste de suggestions si elle existe déjà
      const existingSuggestions = document.getElementById(`${input.name}_suggestions`);
      if (existingSuggestions) existingSuggestions.remove();

      // Créer la liste de suggestions
      const suggestionsList = document.createElement('ul');
      suggestionsList.id = `${input.name}_suggestions`;
      suggestionsList.className = 'address-suggestions';
      suggestionsList.style.position = 'absolute';
      suggestionsList.style.zIndex = '1000';
      suggestionsList.style.backgroundColor = 'white';
      suggestionsList.style.width = `${input.offsetWidth}px`;
      suggestionsList.style.maxHeight = '200px';
      suggestionsList.style.overflowY = 'auto';
      suggestionsList.style.border = '1px solid #ccc';
      suggestionsList.style.borderRadius = '4px';
      suggestionsList.style.listStyle = 'none';
      suggestionsList.style.padding = '0';
      suggestionsList.style.margin = '0';
      suggestionsList.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';

      // Si aucun résultat
      if (addresses.length === 0) {
        const li = document.createElement('li');
        li.textContent = 'Aucun résultat trouvé';
        li.style.padding = '8px 12px';
        suggestionsList.appendChild(li);
      } else {
        // Ajouter chaque adresse à la liste
        addresses.forEach(address => {
          const li = document.createElement('li');
          li.textContent = address.display_name;
          li.style.padding = '8px 12px';
          li.style.cursor = 'pointer';
          li.style.borderBottom = '1px solid #eee';

          // Hover effect
          li.addEventListener('mouseover', function () {
            this.style.backgroundColor = '#f0f0f0';
          });
          li.addEventListener('mouseout', function () {
            this.style.backgroundColor = 'white';
          });

          // Click event
          li.addEventListener('click', function () {
            input.value = address.display_name;
            latInput.value = address.lat;
            lngInput.value = address.lon;

            // Ajouter un marqueur sur la carte
            if (input.name === 'depart') {
              if (departureMark) map.removeLayer(departureMark);
              departureMark = L.marker([address.lat, address.lon], {
                icon: L.divIcon({
                  className: 'departure-marker',
                  html: '<i class="fas fa-map-marker-alt" style="color: #007bff; font-size: 24px;"></i>',
                  iconSize: [20, 20],
                  iconAnchor: [10, 20]
                })
              }).addTo(map).bindPopup('Point de départ: ' + address.display_name);
            } else if (input.name === 'arrivee') {
              if (arrivalMark) map.removeLayer(arrivalMark);
              arrivalMark = L.marker([address.lat, address.lon], {
                icon: L.divIcon({
                  className: 'arrival-marker',
                  html: '<i class="fas fa-flag-checkered" style="color: #28a745; font-size: 24px;"></i>',
                  iconSize: [20, 20],
                  iconAnchor: [10, 20]
                })
              }).addTo(map).bindPopup('Destination: ' + address.display_name);
            }

            // Ajuster la vue de la carte pour inclure les deux marqueurs
            if (departureMark && arrivalMark) {
              const bounds = L.latLngBounds(
                [departureMark.getLatLng(), arrivalMark.getLatLng()]
              );
              map.fitBounds(bounds, { padding: [50, 50] });

              // Tracer une ligne entre les deux points
              if (window.routeLine) map.removeLayer(window.routeLine);
              window.routeLine = L.polyline([
                departureMark.getLatLng(),
                arrivalMark.getLatLng()
              ], {
                color: '#3388ff',
                weight: 3,
                opacity: 0.7,
                dashArray: '5, 10'
              }).addTo(map);
            }

            // Supprimer la liste de suggestions
            suggestionsList.remove();
          });

          suggestionsList.appendChild(li);
        });
      }

      // Ajouter la liste au DOM
      input.parentNode.appendChild(suggestionsList);

    }

    // Fonction de debounce pour limiter les appels API
    function debounce(func, wait) {
      let timeout;
      return function () {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
      };
    }

    // Fixer le type du bouton de recherche
    if (searchButton) {
      searchButton.type = 'submit';
    }

    // Afficher les carpools sur la carte s'ils sont disponibles
    if (typeof carpools !== 'undefined' && carpools && carpools.length > 0) {
      const bounds = [];

      carpools.forEach(carpool => {
        if (carpool.latStart && carpool.lngStart) {
          const startPoint = [carpool.latStart, carpool.lngStart];
          bounds.push(startPoint);

          // Ajouter un marqueur pour le départ
          const startMarker = L.marker(startPoint, {
            icon: L.divIcon({
              className: 'departure-marker',
              html: '<i class="fas fa-map-marker-alt" style="color: #007bff; font-size: 24px;"></i>',
              iconSize: [20, 20],
              iconAnchor: [10, 20]
            })
          }).addTo(map);

          startMarker.bindPopup(`
            <strong>Départ: ${carpool.locationStart}</strong><br>
            Date: ${new Date(carpool.dateStart).toLocaleDateString('fr-FR')}<br>
            Heure: ${new Date(carpool.hourStart).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}<br>
            <a href="?id=${carpool.idCarpool}" class="btn btn-sm btn-primary mt-2">Voir détails</a>
          `);

          markers.push(startMarker);
        }

        // Si les coordonnées d'arrivée sont disponibles, ajouter un marqueur et tracer une ligne
        if (carpool.latStart && carpool.lngStart && carpool.latReach && carpool.lngReach) {
          const endPoint = [carpool.latReach, carpool.lngReach];
          bounds.push(endPoint);

          // Tracer la route entre départ et arrivée
          const routeLine = L.polyline([
            [carpool.latStart, carpool.lngStart],
            [carpool.latReach, carpool.lngReach]
          ], {
            color: '#3388ff',
            weight: 3,
            opacity: 0.7,
            dashArray: '5, 10'
          }).addTo(map);

          markers.push(routeLine);
        }
      });

      // Ajuster la vue de la carte pour inclure tous les points
      if (bounds.length > 0) {
        const boundsGroup = L.latLngBounds(bounds);
        map.fitBounds(boundsGroup, { padding: [50, 50] });
      }
    }

    // Si un covoiturage spécifique est sélectionné, l'afficher sur la carte
    if (typeof selectedCarpool !== 'undefined' && selectedCarpool) {
      if (selectedCarpool.latStart && selectedCarpool.lngStart && selectedCarpool.latReach && selectedCarpool.lngReach) {
        // Supprimer les marqueurs et lignes existants
        markers.forEach(marker => map.removeLayer(marker));
        if (departureMark) map.removeLayer(departureMark);
        if (arrivalMark) map.removeLayer(arrivalMark);
        if (window.routeLine) map.removeLayer(window.routeLine);

        // Ajouter les nouveaux marqueurs
        departureMark = L.marker([selectedCarpool.latStart, selectedCarpool.lngStart], {
          icon: L.divIcon({
            className: 'departure-marker',
            html: '<i class="fas fa-map-marker-alt" style="color: #007bff; font-size: 24px;"></i>',
            iconSize: [20, 20],
            iconAnchor: [10, 20]
          })
        }).addTo(map).bindPopup(`Départ: ${selectedCarpool.locationStart}`);

        arrivalMark = L.marker([selectedCarpool.latReach, selectedCarpool.lngReach], {
          icon: L.divIcon({
            className: 'arrival-marker',
            html: '<i class="fas fa-flag-checkered" style="color: #28a745; font-size: 24px;"></i>',
            iconSize: [20, 20],
            iconAnchor: [10, 20]
          })
        }).addTo(map).bindPopup(`Arrivée: ${selectedCarpool.locationReach}`);

        // Tracer une ligne entre départ et arrivée
        window.routeLine = L.polyline([
          [selectedCarpool.latStart, selectedCarpool.lngStart],
          [selectedCarpool.latReach, selectedCarpool.lngReach]
        ], {
          color: '#3388ff',
          weight: 4,
          opacity: 0.8
        }).addTo(map);

        // Ajuster la vue pour voir tout le trajet
        const bounds = L.latLngBounds(
          [[selectedCarpool.latStart, selectedCarpool.lngStart],
          [selectedCarpool.latReach, selectedCarpool.lngReach]]
        );
        map.fitBounds(bounds, { padding: [50, 50] });
      }
    }

    // Masquer la liste de suggestions lorsqu'on clique ailleurs sur la page
    document.addEventListener('click', function (e) {
      const suggestionLists = document.querySelectorAll('[id$="_suggestions"]');
      suggestionLists.forEach(list => {
        if (e.target !== list && !list.contains(e.target)) {
          list.remove();
        }
      });
    });
  }
});