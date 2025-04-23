// Initialisation de la carte avec Leaflet
document.addEventListener('DOMContentLoaded', function () {
  initDetailButtons();
  
  // Je vérifie si l'élément map existe sur la page
  if (document.getElementById('map')) {
    // J'initialise la carte centrée sur Paris
    const map = L.map('map').setView([48.8566, 2.3522], 12);

    // J'ajoute la couche OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 18
    }).addTo(map);

    // Je force la mise à jour de la carte et réinitialise les gestionnaires d'événements
    setTimeout(() => {
      map.invalidateSize();
      map.dragging.enable();
      map.touchZoom.enable();
      map.doubleClickZoom.enable();
      map.scrollWheelZoom.enable();
      map.boxZoom.enable();
      map.keyboard.enable();
    }, 100);

    // Je déclare les variables pour stocker les marqueurs et les itinéraires
    let markers = [];
    let departureMark = null;
    let arrivalMark = null;
    let searchedRoute = null; // Pour conserver l'itinéraire recherché

    // Je récupère les éléments du formulaire
    const departureInput = document.querySelector('input[name="depart"]');
    const arrivalInput = document.querySelector('input[name="arrivee"]');
    const dateInput = document.querySelector('input[name="date"]');
    const searchForm = document.querySelector('.search-bar');
    const searchButton = document.querySelector('.btn-search');

    // J'intercepte la soumission du formulaire pour utiliser AJAX
    if (searchForm) {
      searchForm.addEventListener('submit', function (event) {
        // Je vérifie si je suis sur la page de covoiturage ou sur une autre page
        const isCovoituragePage = window.location.pathname.includes('/covoiturage');
        // Si je ne suis pas sur la page de covoiturage, je laisse le comportement par défaut (redirection)
        if (!isCovoituragePage) {
          return true;
        }
        // J'empêche le comportement par défaut (rechargement de la page)
        event.preventDefault();
        
        // Je récupère les valeurs du formulaire
        const depart = departureInput ? departureInput.value : '';
        const arrivee = arrivalInput ? arrivalInput.value : '';
        const date = dateInput ? dateInput.value : '';

        // Je construis l'URL pour la requête AJAX
        const searchParams = new URLSearchParams({
          depart: depart,
          arrivee: arrivee,
          date: date
        });

        // Je crée et affiche un indicateur de chargement
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'loading-overlay';
        loadingDiv.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div>';
        loadingDiv.style.position = 'fixed';
        loadingDiv.style.top = '0';
        loadingDiv.style.left = '0';
        loadingDiv.style.width = '100%';
        loadingDiv.style.height = '100%';
        loadingDiv.style.backgroundColor = 'rgba(255,255,255,0.7)';
        loadingDiv.style.display = 'flex';
        loadingDiv.style.justifyContent = 'center';
        loadingDiv.style.alignItems = 'center';
        loadingDiv.style.zIndex = '9999';
        document.body.appendChild(loadingDiv);

        // Je fais la requête AJAX pour le HTML des résultats
        fetch(`/covoiturage/search?${searchParams.toString()}`)
          .then(response => response.text())
          .then(html => {
            // Je supprime l'indicateur de chargement
            document.body.removeChild(loadingDiv);

            // J'injecte le HTML dans la section des résultats
            const resultsContainer = document.querySelector('.resultats');
            if (resultsContainer) {
              resultsContainer.innerHTML = html;
              // J'initialise les boutons de détails apres la mise a jour du contenu
              if (window.initDetailButtons) {
                window.initDetailButtons();
              }
            }

            // Je mets à jour l'URL du navigateur pour permettre les liens partagés
            const newUrl = window.location.pathname + '?' + searchParams.toString();
            window.history.pushState({ path: newUrl }, '', newUrl);

            // Je charge les données de carte séparément
            fetchMapData(searchParams);
          })
          .catch(error => {
            console.error('Erreur lors de la recherche:', error);
            document.body.removeChild(loadingDiv);

            // En cas d'erreur, je soumets le formulaire normalement
            searchForm.submit();
          });
      });
    }

    // J'initialise les champs d'adresse avec l'autocomplete
    if (departureInput) initAddressAutocomplete(departureInput);
    if (arrivalInput) initAddressAutocomplete(arrivalInput);

    // Ma fonction pour initialiser l'autocomplete d'adresse
    function initAddressAutocomplete(input) {
      // Je crée un élément caché pour stocker la latitude
      const latInput = document.createElement('input');
      latInput.type = 'hidden';
      latInput.name = input.name + '_lat';
      input.parentNode.appendChild(latInput);

      // Je crée un élément caché pour stocker la longitude
      const lngInput = document.createElement('input');
      lngInput.type = 'hidden';
      lngInput.name = input.name + '_lng';
      input.parentNode.appendChild(lngInput);

      // J'écoute les événements de saisie
      input.addEventListener('input', debounce(function () {
        if (input.value.length < 3) {
          // Je supprime la liste de suggestions si elle existe
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

    // Ma fonction pour afficher les suggestions d'adresse
    function showAddressSuggestions(addresses, input, latInput, lngInput) {
      // Je supprime la liste de suggestions si elle existe déjà
      const existingSuggestions = document.getElementById(`${input.name}_suggestions`);
      if (existingSuggestions) existingSuggestions.remove();

      // Je crée la liste de suggestions
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
        // J'ajoute chaque adresse à la liste
        addresses.forEach(address => {
          const li = document.createElement('li');
          li.textContent = address.display_name;
          li.style.padding = '8px 12px';
          li.style.cursor = 'pointer';
          li.style.borderBottom = '1px solid #eee';

          // J'ajoute des effets de survol
          li.addEventListener('mouseover', function () {
            this.style.backgroundColor = '#f0f0f0';
          });
          li.addEventListener('mouseout', function () {
            this.style.backgroundColor = 'white';
          });

          // Je gère le clic sur une suggestion
          li.addEventListener('click', function () {
            // J'extrais juste le nom de la ville
            const cityParts = address.display_name.split(',');
            const cityName = cityParts[0].trim();
            input.value = cityName; // J'utilise uniquement le nom de la ville

            latInput.value = address.lat;
            lngInput.value = address.lon;

            // J'ajoute un marqueur sur la carte
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

            // Si les deux points sont définis, je calcule l'itinéraire
            if (departureMark && arrivalMark) {
              // Si un itinéraire existe déjà, je le supprime
              if (searchedRoute) {
                map.removeLayer(searchedRoute);
              }

              calculateRouteForPoints(departureMark.getLatLng(), arrivalMark.getLatLng());
            }

            // Je supprime la liste de suggestions
            suggestionsList.remove();
          });

          suggestionsList.appendChild(li);
        });
      }

      // J'ajoute la liste au DOM
      input.parentNode.appendChild(suggestionsList);
    }

    // Ma fonction pour calculer l'itinéraire entre deux points
    function calculateRouteForPoints(start, end) {
      // Je supprime l'ancienne route si elle existe
      if (window.routeLine) map.removeLayer(window.routeLine);

      // Je calcule la distance à vol d'oiseau
      const distance = calculateHaversineDistance(
        start.lat, start.lng,
        end.lat, end.lng
      );

      // Pour les trajets courts, j'utilise OSRM pour un itinéraire précis
      if (distance <= 250) {
        const url = `https://router.project-osrm.org/route/v1/driving/${start.lng},${start.lat};${end.lng},${end.lat}?overview=full&geometries=polyline`;

        fetch(url)
          .then(response => response.json())
          .then(data => {
            if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
              // J'ajoute le décodeur polyline si nécessaire
              if (!L.Polyline.fromEncoded) {
                L.Polyline.fromEncoded = function (encoded) {
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

              // Je décode la géométrie polyline
              const routeCoordinates = L.Polyline.fromEncoded(data.routes[0].geometry);

              // Je crée la ligne de l'itinéraire
              window.routeLine = L.polyline(routeCoordinates, {
                color: '#FF4500', // Orange-rouge pour l'itinéraire recherché
                weight: 4,
                opacity: 0.7
              }).addTo(map);

              // Je stocke l'itinéraire recherché
              searchedRoute = window.routeLine;

              // Je calcule la distance et le temps
              const distanceKm = (data.routes[0].distance / 1000).toFixed(1);
              const durationFormatted = formatDuration(data.routes[0].duration);

              // J'ajoute un popup avec les informations
              window.routeLine.bindPopup(`
                <strong>Votre itinéraire</strong><br>
                Distance: ${distanceKm} km<br>
                Durée estimée: ${durationFormatted}
              `);

              // J'ajuste la vue pour voir tout l'itinéraire
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
        // Pour les trajets longs, j'utilise une méthode alternative
        createFallbackRoute(start, end);
      }
    }

    // Ma fonction pour créer un itinéraire alternatif
    function createFallbackRoute(start, end) {
      const waypoints = generateIntermediateWaypoints(start, end);

      window.routeLine = L.polyline(waypoints, {
        color: '#FF4500', // Orange-rouge pour l'itinéraire recherché
        weight: 4,
        opacity: 0.7,
        smoothFactor: 2
      }).addTo(map);

      // Je stocke l'itinéraire recherché
      searchedRoute = window.routeLine;

      // Je calcule la distance approximative
      const distanceEstimated = calculateRouteDistance(waypoints);

      // J'estime la durée (vitesse moyenne ~80 km/h)
      const durationSeconds = (distanceEstimated / 80) * 3600;
      const durationFormatted = formatDuration(durationSeconds);

      window.routeLine.bindPopup(`
        <strong>Votre itinéraire</strong><br>
        Distance estimée: ${distanceEstimated.toFixed(1)} km<br>
        Durée estimée: ${durationFormatted}
      `);

      // J'ajuste la vue
      map.fitBounds(window.routeLine.getBounds(), { padding: [50, 50] });
    }

    // Ma fonction pour limiter les appels API (debounce)
    function debounce(func, wait) {
      let timeout;
      return function () {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
      };
    }

    // Ma fonction pour calculer la distance à vol d'oiseau
    function calculateHaversineDistance(lat1, lon1, lat2, lon2) {
      const R = 6371; // Rayon de la Terre en km
      const dLat = toRad(lat2 - lat1);
      const dLon = toRad(lon2 - lon1);
      const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      const distance = R * c;
      return distance;
    }

    // Ma fonction pour convertir les degrés en radians
    function toRad(degrees) {
      return degrees * Math.PI / 180;
    }

    // Ma fonction pour formater la durée
    function formatDuration(seconds) {
      const hours = Math.floor(seconds / 3600);
      const minutes = Math.round((seconds % 3600) / 60);

      if (hours > 0) {
        return `${hours}h ${minutes}min`;
      } else {
        return `${minutes} minutes`;
      }
    }

    // Ma fonction pour générer des points intermédiaires
    function generateIntermediateWaypoints(start, end) {

      const waypoints = [start];

      // Je calcule la distance directe
      const distance = calculateHaversineDistance(
        start.lat, start.lng,
        end.lat, end.lng
      );

      // Je détermine le nombre de points intermédiaires basé sur la distance
      const numPoints = Math.max(2, Math.ceil(distance / 100));

      // Je génère des points intermédiaires avec un peu de variation
      for (let i = 1; i < numPoints; i++) {
        const fraction = i / numPoints;

        // Interpolation linéaire entre les points avec une légère variation
        const lat = start.lat + (end.lat - start.lat) * fraction;
        const lng = start.lng + (end.lng - start.lng) * fraction;

        // J'ajoute une variation pour simuler des routes réelles
        const variation = Math.sin(fraction * Math.PI) * 0.15;
        const randomLat = lat + (Math.random() - 0.5) * variation;
        const randomLng = lng + (Math.random() - 0.5) * variation;

        waypoints.push(L.latLng(randomLat, randomLng));
      }

      waypoints.push(end);
      return waypoints;
    }

    // Ma fonction pour calculer la distance d'un itinéraire
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

      // J'ajoute 20% pour tenir compte des détours réels des routes
      return totalDistance * 1.2;
    }

    // Ma fonction pour récupérer les données pour la carte
    function fetchMapData(searchParams) {
      fetch(`/covoiturage/search?${searchParams.toString()}`)
        .then(response => response.json())
        .then(data => {
          // J'efface les anciens marqueurs de résultats
          markers.forEach(marker => map.removeLayer(marker));
          markers = [];

          // J'affiche les covoiturages sur la carte
          if (data.carpools && data.carpools.length > 0) {
            displayCarpoolsOnMap(data.carpools);
          }
        })
        .catch(error => {
          console.error('Erreur lors du chargement des données de carte:', error);
        });
    }

    // Ma fonction pour afficher les covoiturages sur la carte
    function displayCarpoolsOnMap(carpools) {
      const bounds = [];

      carpools.forEach(carpool => {
        if (carpool.latStart && carpool.lngStart) {
          const startPoint = [carpool.latStart, carpool.lngStart];
          bounds.push(startPoint);

          // J'ajoute un marqueur pour le départ
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
            Heure: ${new Date(carpool.dateStart + 'T' + carpool.hourStart).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}<br>
            <a href="?id=${carpool.idCarpool}" class="btn btn-sm btn-primary mt-2">Voir détails</a>
          `);

          markers.push(startMarker);
        }

        if (carpool.latStart && carpool.lngStart && carpool.latReach && carpool.lngReach) {
          const endPoint = [carpool.latReach, carpool.lngReach];
          bounds.push(endPoint);

          // J'ajoute un marqueur pour l'arrivée
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

          // Je crée l'itinéraire
          const start = L.latLng(carpool.latStart, carpool.lngStart);
          const end = L.latLng(carpool.latReach, carpool.lngReach);
          const waypoints = generateIntermediateWaypoints(start, end);

          const routeLine = L.polyline(waypoints, {
            color: '#3388ff', // Bleu pour les itinéraires des covoiturages
            weight: 3,
            opacity: 0.7,
            smoothFactor: 2
          }).addTo(map);

          // Je calcule la distance approximative et la durée
          const distance = calculateRouteDistance(waypoints);
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

      // J'ajuste la vue pour inclure tous les points et l'itinéraire recherché
      if (bounds.length > 0) {
        const boundsGroup = L.latLngBounds(bounds);

        // J'inclus les points de l'itinéraire recherché
        if (departureMark) boundsGroup.extend(departureMark.getLatLng());
        if (arrivalMark) boundsGroup.extend(arrivalMark.getLatLng());

        map.fitBounds(boundsGroup, { padding: [50, 50] });
      }
    }

    // J'intercepte les clics sur les liens de détails
    document.addEventListener('click', function (event) {
      const linkTarget = event.target.closest('a[href^="?id="]');

      if (!linkTarget) {
        return;
      }

      event.preventDefault();
      const href = linkTarget.getAttribute('href');
      const id = href.replace('?id=', '');

      // Je mets à jour l'URL
      const newUrl = window.location.pathname + '?id=' + id;
      window.history.pushState({ path: newUrl }, '', newUrl);

      // J'affiche un indicateur de chargement
      const loadingDiv = document.createElement('div');
      loadingDiv.className = 'loading-overlay';
      loadingDiv.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div>';
      loadingDiv.style.position = 'fixed';
      loadingDiv.style.top = '0';
      loadingDiv.style.left = '0';
      loadingDiv.style.width = '100%';
      loadingDiv.style.height = '100%';
      loadingDiv.style.backgroundColor = 'rgba(255,255,255,0.7)';
      loadingDiv.style.display = 'flex';
      loadingDiv.style.justifyContent = 'center';
      loadingDiv.style.alignItems = 'center';
      loadingDiv.style.zIndex = '9999';
      document.body.appendChild(loadingDiv);

      // Je charge les détails du covoiturage
      fetch(`/covoiturage/details/${id}`)
        .then(response => response.text())
        .then(html => {
          // Je supprime l'indicateur de chargement
          document.body.removeChild(loadingDiv);

          // Je mets à jour la partie des résultats
          const resultsContainer = document.querySelector('.resultats');
          if (resultsContainer) {
            resultsContainer.innerHTML = html;
            // J'initialise les boutons de détails après mise à jour du contenu
            if (window.initDetailButtons) {
              window.initDetailButtons();
            }
          }

          // Je charge les détails pour la carte
          fetch(`/covoiturage/details-json/${id}`)
            .then(response => response.json())
            .then(data => {
              showSelectedCarpoolOnMap(data.carpool);
            })
            .catch(error => {
              console.error('Erreur lors du chargement des détails pour la carte:', error);
            });
        })
        .catch(error => {
          console.error('Erreur lors du chargement des détails:', error);
          document.body.removeChild(loadingDiv);
          window.location.href = href; // En cas d'erreur, je fais le comportement par défaut
        });
      
    });

    // Ma fonction pour afficher un covoiturage sélectionné sur la carte
    function showSelectedCarpoolOnMap(carpool) {
      // Je supprime les marqueurs de résultats tout en conservant l'itinéraire recherché
      markers.forEach(marker => map.removeLayer(marker));
      markers = [];

      // J'affiche le covoiturage sélectionné sur la carte
      if (carpool.latStart && carpool.lngStart && carpool.latReach && carpool.lngReach) {
        // J'ajoute un marqueur pour le départ
        const departureMarker = L.marker([carpool.latStart, carpool.lngStart], {
          icon: L.divIcon({
            className: 'departure-marker',
            html: '<i class="fas fa-map-marker-alt" style="color: #007bff; font-size: 24px;"></i>',
            iconSize: [20, 20],
            iconAnchor: [10, 20]
          })
        }).addTo(map).bindPopup(`Départ: ${carpool.locationStart}`);

        // J'ajoute un marqueur pour l'arrivée
        const arrivalMarker = L.marker([carpool.latReach, carpool.lngReach], {
          icon: L.divIcon({
            className: 'arrival-marker',
            html: '<i class="fas fa-flag-checkered" style="color: #28a745; font-size: 24px;"></i>',
            iconSize: [20, 20],
            iconAnchor: [10, 20]
          })
        }).addTo(map).bindPopup(`Arrivée: ${carpool.locationReach}`);

        // Je crée l'itinéraire
        const start = L.latLng(carpool.latStart, carpool.lngStart);
        const end = L.latLng(carpool.latReach, carpool.lngReach);
        const waypoints = generateIntermediateWaypoints(start, end);

        const carpoolRoute = L.polyline(waypoints, {
          color: '#3388ff',
          weight: 4,
          opacity: 0.8,
          smoothFactor: 2
        }).addTo(map);

        // Je calcule la distance et la durée
        const distance = calculateRouteDistance(waypoints);
        const durationSeconds = (distance / 80) * 3600;
        const durationFormatted = formatDuration(durationSeconds);

        carpoolRoute.bindPopup(`
          <strong>Trajet: ${carpool.locationStart} → ${carpool.locationReach}</strong><br>
          Distance estimée: ${distance.toFixed(1)} km<br>
          Durée estimée: ${durationFormatted}
        `).openPopup();

        // J'ajoute les éléments à la liste des marqueurs
        markers.push(departureMarker, arrivalMarker, carpoolRoute);

        // J'ajuste la vue pour voir l'itinéraire complet
        // J'ajuste la vue pour voir l'itinéraire complet et l'itinéraire recherché
        const bounds = L.latLngBounds([
          [carpool.latStart, carpool.lngStart],
          [carpool.latReach, carpool.lngReach]
        ]);

        // Si l'itinéraire recherché existe, je l'inclus dans la vue
        if (searchedRoute) {
          try {
            const searchedBounds = searchedRoute.getBounds();
            bounds.extend(searchedBounds);
          } catch (error) {
            console.warn('Impossible d\'étendre les limites avec l\'itinéraire recherché', error);
          }
        }

        map.fitBounds(bounds, { padding: [50, 50] });
      }
    }

    // Je configure le type du bouton de recherche
    if (searchButton) {
      searchButton.type = 'submit';
    }

    // J'affiche les covoiturages sur la carte s'ils sont disponibles
    if (typeof carpools !== 'undefined' && carpools && carpools.length > 0) {
      displayCarpoolsOnMap(carpools);
    }

    // Si un covoiturage spécifique est sélectionné, je l'affiche sur la carte
    if (typeof selectedCarpool !== 'undefined' && selectedCarpool) {
      if (selectedCarpool.latStart && selectedCarpool.lngStart && selectedCarpool.latReach && selectedCarpool.lngReach) {
        // Je supprime les marqueurs et lignes existants, mais pas l'itinéraire recherché
        markers.forEach(marker => map.removeLayer(marker));
        markers = [];

        // J'affiche le covoiturage sélectionné sur la carte
        showSelectedCarpoolOnMap(selectedCarpool);
      }
    }

    // Je masque la liste de suggestions lorsqu'on clique ailleurs sur la page
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

// Gestion des filtres
document.addEventListener('DOMContentLoaded', function() {
  // Vérifier si un paramètre filtered=true est présent dans l'URL
  const urlParams = new URLSearchParams(window.location.search);
  if (!urlParams.get('filtered')) {
      // Si nous ne sommes pas sur une page filtrée, effacer le storage
      sessionStorage.removeItem('ecoride_filters');
  }

  // Éléments DOM de la modale de filtrage
  const creditRange = document.getElementById('credit-range');
  const creditDisplay = document.getElementById('credit-value');
  
  const filterButton = document.querySelector('.btn-modal-filtre');
  
  // Mise à jour de l'affichage des crédits
  if (creditRange && creditDisplay) {
      creditRange.addEventListener('input', function() {
          creditDisplay.textContent = `${this.value} crédits`;
      });
  }

  
  // Application des filtres
  if (filterButton) {
      filterButton.addEventListener('click', function() {
          // Récupération des valeurs de filtres
          const vehicleType = document.querySelector('input[name="vehicleType"]:checked').id;
          const passengerCount = document.querySelector('#passager-count').value;
          const maxCredits = creditRange.value;
          const driverRating = parseFloat(document.querySelector('#driver-rating').value);
          
          // Récupération des paramètres de recherche actuels (ville départ, arrivée, date)
          const urlParams = new URLSearchParams(window.location.search);
          const depart = urlParams.get('depart') || '';
          const arrivee = urlParams.get('arrivee') || '';
          const date = urlParams.get('date') || '';
          
          // Construction des paramètres de la requête
          const filterParams = new URLSearchParams({
              depart: depart,
              arrivee: arrivee,
              date: date,
              vehicleType: vehicleType,
              passengerCount: passengerCount,
              maxCredits: maxCredits,
              driverRating: driverRating,
              filtered: 'true'
          });
          
          // Affichage d'un indicateur de chargement
          const loadingDiv = document.createElement('div');
          loadingDiv.className = 'loading-overlay';
          loadingDiv.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div>';
          loadingDiv.style.position = 'fixed';
          loadingDiv.style.top = '0';
          loadingDiv.style.left = '0';
          loadingDiv.style.width = '100%';
          loadingDiv.style.height = '100%';
          loadingDiv.style.backgroundColor = 'rgba(255,255,255,0.7)';
          loadingDiv.style.display = 'flex';
          loadingDiv.style.justifyContent = 'center';
          loadingDiv.style.alignItems = 'center';
          loadingDiv.style.zIndex = '9999';
          document.body.appendChild(loadingDiv);
          
          // Requête AJAX pour appliquer les filtres
          fetch(`/covoiturage/filter?${filterParams.toString()}`)
              .then(response => response.text())
              .then(html => {
                  // Suppression de l'indicateur de chargement
                  document.body.removeChild(loadingDiv);
                  
                  // Mise à jour du contenu des résultats
                  const resultsContainer = document.querySelector('.resultats');
                  if (resultsContainer) {
                      resultsContainer.innerHTML = html;
                      
                      // Initialisation des boutons de détails après mise à jour du contenu
                      if (window.initDetailButtons) {
                          window.initDetailButtons();
                      }
                  }
                  
                  // Mise à jour de l'URL pour permettre le partage et l'historique
                  const newUrl = window.location.pathname + '?' + filterParams.toString();
                  window.history.pushState({ path: newUrl }, '', newUrl);
                  
                  // Charger les données pour la carte
                  if (typeof fetchMapData === 'function') {
                      fetchMapData(filterParams);
                  }
                  
                  // Sauvegarder les filtres dans sessionStorage
                  sessionStorage.setItem('ecoride_filters', JSON.stringify({
                      vehicleType,
                      passengerCount: parseInt(passengerCount, 10),
                      maxCredits,
                      driverRating
                  }));
                  
                  // Fermer la modale
                  const modal = bootstrap.Modal.getInstance(document.getElementById('filterModal'));
                  if (modal) {
                      modal.hide();
                  }
              })
              .catch(error => {
                  console.error('Erreur lors du filtrage:', error);
                  document.body.removeChild(loadingDiv);
                  alert('Une erreur est survenue lors du filtrage. Veuillez réessayer.');
              });
      });
  }
  
  // Initialisation du bouton reset pour les filtres
  const resetButton = document.createElement('button');
  resetButton.className = 'btn btn-outline-secondary me-2';
  resetButton.textContent = 'Réinitialiser';
  
  const buttonContainer = document.querySelector('.filter-menu .modal-body .text-center');
  if (buttonContainer) {
      // Insérer le bouton de réinitialisation avant le bouton de filtrage
      buttonContainer.insertBefore(resetButton, buttonContainer.firstChild);
      
      resetButton.addEventListener('click', function() {
          // Réinitialiser les filtres
          document.getElementById('allVehicles').checked = true;
          
          const passengerInput = document.querySelector('.filter-section input[type="number"][max="5"]');
          if (passengerInput) passengerInput.value = 1;
          
          if (creditRange) {
              creditRange.value = 20;
              creditDisplay.textContent = '20 crédits';
          }
          
          const ratingInput = document.querySelector('.filter-section input[type="number"][max="5"]:last-of-type');
          if (ratingInput) ratingInput.value = 5;
          
          // Supprimer les filtres enregistrés
          sessionStorage.removeItem('ecoride_filters');
      });
  }
  
  // Restaurer les filtres depuis sessionStorage au chargement
  const savedFilters = sessionStorage.getItem('ecoride_filters');
  if (savedFilters) {
      try {
          const filters = JSON.parse(savedFilters);
          
          // Restaurer les valeurs dans la modale
          if (filters.vehicleType && document.getElementById(filters.vehicleType)) {
              document.getElementById(filters.vehicleType).checked = true;
          }
          
          if (filters.passengerCount) {
            const passengerInput = document.querySelector('#passager-count');
            if (passengerInput) {
              passengerInput.value = filters.passengerCount;
            }
          }
          
          if (filters.maxCredits && creditRange) {
              creditRange.value = filters.maxCredits;
              creditDisplay.textContent = `${filters.maxCredits} crédits`;
          }
          
          if (filters.driverRating) {
              const ratingInput = document.querySelector('.filter-section input[type="number"][max="5"]:last-of-type');
              if (ratingInput) ratingInput.value = filters.driverRating;
          }
      } catch (error) {
          console.error('Erreur lors de la restauration des filtres:', error);
          sessionStorage.removeItem('ecoride_filters');
      }
  }
});
