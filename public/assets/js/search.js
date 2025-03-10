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
            const cityParts = address.display_name.split(',');
            const cityName = cityParts[0].trim();
            input.value = cityName; // Utiliser uniquement le nom de la ville
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

            // Vue de la carte pour inclure les deux marqueurs
            if (departureMark && arrivalMark) {
              calculateRouteForPoints(departureMark.getLatLng(), arrivalMark.getLatLng());
            }

            // Fonction pour calculer l'itinéraire entre deux points
            function calculateRouteForPoints(start, end) {
              // Supprimer l'ancienne route si elle existe
              if (window.routeLine) map.removeLayer(window.routeLine);
              
              // Calculer la distance à vol d'oiseau
              const distance = calculateHaversineDistance(
                start.lat, start.lng,
                end.lat, end.lng
              );
              
              //console.log(`Distance à vol d'oiseau: ${distance.toFixed(1)} km`);
              
              // Pour les trajets courts, utiliser OSRM pour un itinéraire précis
              if (distance <= 250) {
                const url = `https://router.project-osrm.org/route/v1/driving/${start.lng},${start.lat};${end.lng},${end.lat}?overview=full&geometries=polyline`;
                
                fetch(url)
                  .then(response => response.json())
                  .then(data => {
                    if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                      // Ajouter le décodeur polyline si nécessaire
                      if (!L.Polyline.fromEncoded) {
                        L.Polyline.fromEncoded = function(encoded) {
                          var points = [];
                          var index = 0, len = encoded.length;
                          var lat = 0, lng = 0;
                          
                          while (index < len) {
                            var b, shift = 0, result = 0;
                            do {
                              b = encoded.charAt(index++).charCodeAt(0) - 63;
                              result |= (b & 0x1f) << shift;
                              shift += 5;
                            } while (b >= 0x20);
                            var dlat = ((result & 1) != 0 ? ~(result >> 1) : (result >> 1));
                            lat += dlat;
                            
                            shift = 0;
                            result = 0;
                            do {
                              b = encoded.charAt(index++).charCodeAt(0) - 63;
                              result |= (b & 0x1f) << shift;
                              shift += 5;
                            } while (b >= 0x20);
                            var dlng = ((result & 1) != 0 ? ~(result >> 1) : (result >> 1));
                            lng += dlng;
                            
                            points.push(L.latLng(lat * 1e-5, lng * 1e-5));
                          }
                          
                          return points;
                        };
                      }
                      
                      // Décoder la géométrie polyline
                      const routeCoordinates = L.Polyline.fromEncoded(data.routes[0].geometry);

                      // Créer la ligne de l'itinéraire
                      window.routeLine = L.polyline(routeCoordinates, {
                        color: '#3388ff',
                        weight: 3,
                        opacity: 0.7
                      }).addTo(map);

                      // Calculer la distance et le temps
                      const distanceKm = (data.routes[0].distance / 1000).toFixed(1);
                      const durationFormatted = formatDuration(data.routes[0].duration);

                      // Ajouter un popup avec les informations
                      window.routeLine.bindPopup(`
                        <strong>Itinéraire routier</strong><br>
                        Distance: ${distanceKm} km<br>
                        Durée estimée: ${durationFormatted}
                      `);

                      // Ajuster la vue pour voir tout l'itinéraire
                      map.fitBounds(window.routeLine.getBounds(), { padding: [50, 50] });
                    } else {
                      createFallbackRoute(start, end);
                    }
                  })
                  .catch(error => {
                    console.error('Erreur:', error);
                    createFallbackRoute(start, end);
                  });
              } else {
                // Pour les trajets longs, utiliser une méthode alternative
                createFallbackRoute(start, end);
              }
            }

            // Fonction pour créer un itinéraire alternatif lorsque OSRM échoue ou pour les longues distances
            function createFallbackRoute(start, end) {
              const waypoints = generateIntermediateWaypoints(start, end);
              
              window.routeLine = L.polyline(waypoints, {
                color: '#3388ff',
                weight: 3,
                opacity: 0.7,
                smoothFactor: 2
              }).addTo(map);
              
              // Calculer la distance approximative
              const distanceEstimated = calculateRouteDistance(waypoints);
              
              // Estimer la durée (vitesse moyenne ~80 km/h)
              const durationSeconds = (distanceEstimated / 80) * 3600;
              const durationFormatted = formatDuration(durationSeconds);
              
              window.routeLine.bindPopup(`
                <strong>Itinéraire estimé</strong><br>
                Distance estimée: ${distanceEstimated.toFixed(1)} km<br>
                Durée estimée: ${durationFormatted}
              `);
              
              // Ajuster la vue
              map.fitBounds(window.routeLine.getBounds(), { padding: [50, 50] });
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
    // Ajouter ces fonctions de calcul de distance et conversion
    function calculateHaversineDistance(lat1, lon1, lat2, lon2) {
      const R = 6371; // Rayon de la Terre en km
      const dLat = toRad(lat2 - lat1);
      const dLon = toRad(lon2 - lon1);
      const a = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * 
        Math.sin(dLon/2) * Math.sin(dLon/2);
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
      const distance = R * c;
      return distance;
    }

    function toRad(degrees) {
      return degrees * Math.PI / 180;
    }

    // Fonction pour formater la durée
    function formatDuration(seconds) {
      const hours = Math.floor(seconds / 3600);
      const minutes = Math.round((seconds % 3600) / 60);
      
      if (hours > 0) {
        return `${hours}h ${minutes}min`;
      } else {
        return `${minutes} minutes`;
      }
    }

    // Fonction pour générer des points intermédiaires (pour les itinéraires réalistes)
    function generateIntermediateWaypoints(start, end) {
      // start et end sont des objets LatLng de Leaflet
      const waypoints = [start];
      
      // Calculer la distance directe
      const distance = calculateHaversineDistance(
        start.lat, start.lng,
        end.lat, end.lng
      );
      
      // Déterminer le nombre de points intermédiaires basé sur la distance
      const numPoints = Math.max(2, Math.ceil(distance / 100));
      
      // Générer des points intermédiaires avec un peu de variation
      for (let i = 1; i < numPoints; i++) {
        const fraction = i / numPoints;
        
        // Interpolation linéaire entre les points avec une légère variation
        const lat = start.lat + (end.lat - start.lat) * fraction;
        const lng = start.lng + (end.lng - start.lng) * fraction;
        
        // Ajouter une variation pour simuler des routes réelles
        const variation = Math.sin(fraction * Math.PI) * 0.15;
        const randomLat = lat + (Math.random() - 0.5) * variation;
        const randomLng = lng + (Math.random() - 0.5) * variation;
        
        waypoints.push(L.latLng(randomLat, randomLng));
      }
      
      waypoints.push(end);
      return waypoints;
    }

    // Fonction pour calculer la distance d'un itinéraire avec plusieurs points
    function calculateRouteDistance(waypoints) {
      let totalDistance = 0;
      
      for (let i = 0; i < waypoints.length - 1; i++) {
        const p1 = waypoints[i];
        const p2 = waypoints[i + 1];
        
        totalDistance += calculateHaversineDistance(
          p1.lat, p1.lng, 
          p2.lat, p2.lng
        );
      }
      
      // Ajouter 20% pour tenir compte des détours réels des routes
      return totalDistance * 1.2;
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
          
          // Ajouter un marqueur pour l'arrivée
          const endMarker = L.marker(endPoint, {
            icon: L.divIcon({
              className: 'arrival-marker',
              html: '<i class="fas fa-flag-checkered" style="color: #28a745; font-size: 24px;"></i>',
              iconSize: [20, 20],
              iconAnchor: [10, 20]
            })
          }).addTo(map);
          
          endMarker.bindPopup(`
            <strong>Arrivée: ${carpool.locationReach}</strong><br>
            <a href="?id=${carpool.idCarpool}" class="btn btn-sm btn-primary mt-2">Voir détails</a>
          `);
          
          markers.push(endMarker);
          
          // Calculer la distance à vol d'oiseau
          const distanceKm = calculateHaversineDistance(
            carpool.latStart, carpool.lngStart,
            carpool.latReach, carpool.lngReach
          );
          
          // Créer l'itinéraire
          const start = L.latLng(carpool.latStart, carpool.lngStart);
          const end = L.latLng(carpool.latReach, carpool.lngReach);
          const waypoints = generateIntermediateWaypoints(start, end);
          
          const routeLine = L.polyline(waypoints, {
            color: '#3388ff',
            weight: 3,
            opacity: 0.7,
            smoothFactor: 2
          }).addTo(map);
          
          // Calculer la distance approximative
          const distance = calculateRouteDistance(waypoints);
          
          // Estimer la durée (vitesse moyenne ~80 km/h)
          const durationSeconds = (distance / 80) * 3600;
          const durationFormatted = formatDuration(durationSeconds);
          
          routeLine.bindPopup(`
            <strong>Trajet: ${carpool.locationStart} → ${carpool.locationReach}</strong><br>
            Distance estimée: ${distance.toFixed(1)} km<br>
            Durée estimée: ${durationFormatted}<br>
            <a href="?id=${carpool.idCarpool}" class="btn btn-sm btn-primary mt-2">Voir détails</a>
          `);
          
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
        // Calculer la distance à vol d'oiseau
        const distance = calculateHaversineDistance(
          selectedCarpool.latStart, selectedCarpool.lngStart,
          selectedCarpool.latReach, selectedCarpool.lngReach
        );

        // Créer l'itinéraire
        const start = L.latLng(selectedCarpool.latStart, selectedCarpool.lngStart);
        const end = L.latLng(selectedCarpool.latReach, selectedCarpool.lngReach);
        const waypoints = generateIntermediateWaypoints(start, end);

        window.routeLine = L.polyline(waypoints, {
          color: '#3388ff',
          weight: 4,
          opacity: 0.8,
          smoothFactor: 2
        }).addTo(map);

        // Calculer la distance approximative
        const routeDistance = calculateRouteDistance(waypoints);

        // Estimer la durée (vitesse moyenne ~80 km/h)
        const durationSeconds = (routeDistance / 80) * 3600;
        const durationFormatted = formatDuration(durationSeconds);

        window.routeLine.bindPopup(`
          <strong>Trajet: ${selectedCarpool.locationStart} → ${selectedCarpool.locationReach}</strong><br>
          Distance estimée: ${routeDistance.toFixed(1)} km<br>
          Durée estimée: ${durationFormatted}
        `).openPopup();

        // Ajuster la vue pour voir tout l'itinéraire
        map.fitBounds(window.routeLine.getBounds(), { padding: [50, 50] });
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