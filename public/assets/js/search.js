
document.addEventListener('DOMContentLoaded', function () {
  // Initialisation des composants d'interface
  initDetailButtons();

  // Création et configuration de la carte si l'élément existe
  if (document.getElementById('map')) {
    initMap();
  }
});

/**
 * Initialise la carte et configure toutes les fonctionnalités liées
 */
function initMap() {
  // Création de la carte centrée sur la France
  const map = L.map('map').setView([46.603354, 1.888334], 6);
  
  // Stockage global de la carte pour y accéder dans d'autres fonctions
  window.map = map;
  window.markers = [];
  
  // Ajout du fond de carte OpenStreetMap
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 18
  }).addTo(map);
  
  // Force le redimensionnement de la carte (nécessaire lorsqu'elle est dans un conteneur caché)
  setTimeout(() => {
    map.invalidateSize();
  }, 100);
  
  // Variables pour stocker les marqueurs et les itinéraires
  let departureMark = null;
  let arrivalMark = null;
  let routingControl = null;
  
  // Stockage global des marqueurs de départ/arrivée
  window.departureMark = departureMark;
  window.arrivalMark = arrivalMark;
  window.routingControl = routingControl;
  
  // Configuration du formulaire de recherche
  setupSearchForm(map, window.markers, departureMark, arrivalMark, routingControl);
  
  // Configuration des filtres
  setupFilters();
  
  // Affiche les covoiturages existants sur la carte
  if (typeof window.carpools !== 'undefined' && window.carpools && window.carpools.length > 0) {
    displayCarpoolsOnMap(window.carpools, map, window.markers);
  }
  
  // Si un covoiturage spécifique est sélectionné, l'affiche sur la carte
  if (typeof window.selectedCarpool !== 'undefined' && window.selectedCarpool) {
    showSelectedCarpoolOnMap(window.selectedCarpool, map, window.markers);
  }
}

/**
 * Configure le formulaire de recherche et ses événements
 */
function setupSearchForm(map, markers, departureMark, arrivalMark, routingControl) {
  // Récupère les éléments du formulaire
  const departureInput = document.querySelector('input[name="depart"]');
  const arrivalInput = document.querySelector('input[name="arrivee"]');
  const dateInput = document.querySelector('input[name="date"]');
  const searchForm = document.querySelector('.search-bar');
  const searchButton = document.querySelector('.btn-search');
  
  // Configure l'autocomplete pour les champs d'adresse
  if (departureInput) initAddressAutocomplete(departureInput, map, 'depart', departureMark, arrivalMark);
  if (arrivalInput) initAddressAutocomplete(arrivalInput, map, 'arrivee', departureMark, arrivalMark);
  
  // Intercepte la soumission du formulaire pour utiliser AJAX
  if (searchForm) {
    searchForm.addEventListener('submit', function(event) {
      // Vérifie si on est sur la page de covoiturage
      const isCovoituragePage = window.location.pathname.includes('/covoiturage');
      
      // Si on n'est pas sur la page covoiturage, comportement normal (redirection)
      if (!isCovoituragePage) {
        return true;
      }
      
      // Empêche le comportement par défaut (rechargement de la page)
      event.preventDefault();
      
      // Récupère les valeurs du formulaire
      const depart = departureInput ? departureInput.value : '';
      const arrivee = arrivalInput ? arrivalInput.value : '';
      const date = dateInput ? dateInput.value : '';
      
      // Construit l'URL pour la requête AJAX
      const searchParams = new URLSearchParams({
        depart: depart,
        arrivee: arrivee,
        date: date
      });
      
      // Affiche un indicateur de chargement
      const loadingDiv = createLoadingOverlay();
      document.body.appendChild(loadingDiv);
      
      // Effectue la requête AJAX
      fetch(`/covoiturage/search?${searchParams.toString()}`)
        .then(response => {
          if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
          }
          return response.text();
        })
        .then(html => {
          // Supprime l'indicateur de chargement
          if (document.body.contains(loadingDiv)) {
            document.body.removeChild(loadingDiv);
          }
          
          // Injecte le HTML dans la section des résultats
          const resultsContainer = document.querySelector('.resultats');
          if (resultsContainer) {
            resultsContainer.innerHTML = html;
            // Réinitialise les boutons de détails
            if (typeof initDetailButtons === 'function') {
              initDetailButtons();
            }
          }
          
          // Met à jour l'URL du navigateur (permet de partager le lien)
          const newUrl = window.location.pathname + '?' + searchParams.toString();
          window.history.pushState({ path: newUrl }, '', newUrl);
          
          // Met à jour la carte avec les résultats de recherche
          fetch(`/covoiturage/search?${searchParams.toString()}`)
            .then(response => {
              if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
              }
              try {
                return response.json();
              } catch (error) {
                // Si ce n'est pas du JSON, on extrait du DOM
                return extractCarpoolsFromDOM();
              }
            })
            .catch(error => {
              console.warn('Extraction des données depuis le DOM:', error);
              return extractCarpoolsFromDOM();
            })
            .then(data => {
              if (data && data.carpools && data.carpools.length > 0) {
                // Efface les anciens marqueurs
                clearMarkers(map, markers);
                
                // Affiche les covoiturages sur la carte
                displayCarpoolsOnMap(data.carpools, map, markers);
              }
            });
        })
        .catch(error => {
          console.error('Erreur lors de la recherche:', error);
          // Supprime l'indicateur de chargement s'il existe encore
          if (document.body.contains(loadingDiv)) {
            document.body.removeChild(loadingDiv);
          }
          
          // En cas d'erreur, soumet le formulaire normalement
          searchForm.submit();
        });
    });
  }
  
  // Intercepte les clics sur les liens de détails des covoiturages
  document.addEventListener('click', function(event) {
    const linkTarget = event.target.closest('a[href^="?id="]');
    
    if (!linkTarget) {
      return;
    }
    
    event.preventDefault();
    const href = linkTarget.getAttribute('href');
    const id = href.replace('?id=', '');
    
    // Met à jour l'URL
    const newUrl = window.location.pathname + '?id=' + id;
    window.history.pushState({ path: newUrl }, '', newUrl);
    
    // Affiche un indicateur de chargement
    const loadingDiv = createLoadingOverlay();
    document.body.appendChild(loadingDiv);
    
    // Charge les détails du covoiturage
    fetch(`/covoiturage/${id}/details-modal`)
      .then(response => {
        if (!response.ok) {
          throw new Error(`Erreur HTTP: ${response.status}`);
        }
        return response.text();
      })
      .then(html => {
        // Supprime l'indicateur de chargement
        if (document.body.contains(loadingDiv)) {
          document.body.removeChild(loadingDiv);
        }
        
        // Met à jour la section des détails
        const resultsContainer = document.querySelector('.resultats');
        if (resultsContainer) {
          resultsContainer.innerHTML = html;
          // Réinitialise les boutons de détails
          if (typeof initDetailButtons === 'function') {
            initDetailButtons();
          }
        }
        
        // Essaie de charger les données pour la carte si disponibles
        try {
          // Extrait les données du covoiturage du DOM nouvellement inséré
          const carpoolElement = document.querySelector('[data-carpool-json]');
          if (carpoolElement) {
            const carpool = JSON.parse(carpoolElement.dataset.carpoolJson);
            if (carpool) {
              showSelectedCarpoolOnMap(carpool, map, markers);
            }
          }
        } catch (error) {
          console.warn('Impossible de mettre à jour la carte:', error);
        }
      })
      .catch(error => {
        console.error('Erreur lors du chargement des détails:', error);
        // Supprime l'indicateur de chargement s'il existe encore
        if (document.body.contains(loadingDiv)) {
          document.body.removeChild(loadingDiv);
        }
        // En cas d'erreur, utilise le comportement par défaut
        window.location.href = href;
      });
  });
}

/**
 * Extrait les données des covoiturages depuis le DOM
 */
function extractCarpoolsFromDOM() {
  const carpoolElements = document.querySelectorAll('[data-carpool-json]');
  const carpools = Array.from(carpoolElements).map(el => {
    try {
      return JSON.parse(el.dataset.carpoolJson);
    } catch (e) {
      console.error('Erreur de parsing JSON:', e);
      return null;
    }
  }).filter(Boolean);
  
  return { carpools: carpools };
}

/**
 * Initialise l'autocomplétion des adresses
 */
function initAddressAutocomplete(input, map, type, departureMark, arrivalMark) {
  // Crée des champs cachés pour stocker lat/lng
  const latInput = document.createElement('input');
  latInput.type = 'hidden';
  latInput.name = input.name + '_lat';
  input.parentNode.appendChild(latInput);
  
  const lngInput = document.createElement('input');
  lngInput.type = 'hidden';
  lngInput.name = input.name + '_lng';
  input.parentNode.appendChild(lngInput);
  
  // Gère la saisie dans le champ
  input.addEventListener('input', debounce(function() {
    if (input.value.length < 3) {
      // Supprime la liste de suggestions si elle existe
      const existingSuggestions = document.getElementById(`${input.name}_suggestions`);
      if (existingSuggestions) existingSuggestions.remove();
      return;
    }
    
    // Effectue la recherche d'adresses
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(input.value)}&limit=5&countrycodes=fr`)
      .then(response => response.json())
      .then(data => {
        showAddressSuggestions(data, input, latInput, lngInput, map, type, departureMark, arrivalMark);
      })
      .catch(error => console.error('Erreur lors de la recherche d\'adresses:', error));
  }, 500));
}

/**
 * Affiche les suggestions d'adresses dans une liste déroulante
 */
function showAddressSuggestions(addresses, input, latInput, lngInput, map, type, departureMark, arrivalMark) {
  // Supprime la liste de suggestions existante
  const existingSuggestions = document.getElementById(`${input.name}_suggestions`);
  if (existingSuggestions) existingSuggestions.remove();
  
  // Crée la liste de suggestions
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
    // Ajoute chaque adresse à la liste
    addresses.forEach(address => {
      const li = document.createElement('li');
      li.textContent = address.display_name;
      li.style.padding = '8px 12px';
      li.style.cursor = 'pointer';
      li.style.borderBottom = '1px solid #eee';
      
      // Effets de survol
      li.addEventListener('mouseover', function() {
        this.style.backgroundColor = '#f0f0f0';
      });
      li.addEventListener('mouseout', function() {
        this.style.backgroundColor = 'white';
      });
      
      // Gère le clic sur une suggestion
      li.addEventListener('click', function() {
        // Extrait juste le nom de la ville
        const cityParts = address.display_name.split(',');
        const cityName = cityParts[0].trim();
        input.value = cityName;
        
        latInput.value = address.lat;
        lngInput.value = address.lon;
        
        // Ajoute un marqueur sur la carte
        addMarkerForAddress(map, address, type);
        
        // Supprime la liste de suggestions
        suggestionsList.remove();
      });
      
      suggestionsList.appendChild(li);
    });
  }
  
  // Ajoute la liste au DOM
  input.parentNode.appendChild(suggestionsList);
}

/**
 * Ajoute un marqueur sur la carte pour l'adresse sélectionnée
 */
function addMarkerForAddress(map, address, type) {
  const lat = parseFloat(address.lat);
  const lng = parseFloat(address.lon);
  
  // Supprime l'ancien marqueur si nécessaire
  if (type === 'depart' && window.departureMark) {
    map.removeLayer(window.departureMark);
  } else if (type === 'arrivee' && window.arrivalMark) {
    map.removeLayer(window.arrivalMark);
  }
  
  // Crée le nouveau marqueur
  let marker;
  
  if (type === 'depart') {
    marker = L.marker([lat, lng], {
      icon: L.divIcon({
        className: 'departure-marker',
        html: '<i class="fas fa-map-marker-alt" style="color: #007bff; font-size: 24px;"></i>',
        iconSize: [20, 20],
        iconAnchor: [10, 20]
      })
    }).addTo(map).bindPopup('Point de départ: ' + address.display_name);
    
    window.departureMark = marker;
  } else if (type === 'arrivee') {
    marker = L.marker([lat, lng], {
      icon: L.divIcon({
        className: 'arrival-marker',
        html: '<i class="fas fa-flag-checkered" style="color: #28a745; font-size: 24px;"></i>',
        iconSize: [20, 20],
        iconAnchor: [10, 20]
      })
    }).addTo(map).bindPopup('Destination: ' + address.display_name);
    
    window.arrivalMark = marker;
  }
  
  // Si les deux points sont définis, calcule l'itinéraire
  if (window.departureMark && window.arrivalMark) {
    // Supprime l'itinéraire existant
    if (window.routingControl) {
      map.removeLayer(window.routingControl);
    }
    
    // Calcule le nouvel itinéraire
    calculateRoute(map, window.departureMark.getLatLng(), window.arrivalMark.getLatLng());
  }
  
  // Centre la carte sur le marqueur
  map.setView([lat, lng], 10);
}

/**
 * Calcule et affiche un itinéraire entre deux points
 */
function calculateRoute(map, start, end) {
  // Pour l'itinéraire, utilise une simple ligne droite avec des points intermédiaires
  const waypoints = generateIntermediateWaypoints(start, end);
  
  // Supprime l'ancien itinéraire
  if (window.routeLine) {
    map.removeLayer(window.routeLine);
  }
  
  // Crée la ligne de l'itinéraire
  window.routeLine = L.polyline(waypoints, {
    color: '#FF4500',
    weight: 4,
    opacity: 0.7,
    smoothFactor: 2
  }).addTo(map);
  
  // Calcule la distance approximative
  const distance = calculateRouteDistance(waypoints);
  
  // Estime la durée (vitesse moyenne ~80 km/h)
  const durationSeconds = (distance / 80) * 3600;
  const durationFormatted = formatDuration(durationSeconds);
  
  // Ajoute les infos à l'itinéraire
  window.routeLine.bindPopup(`
    <strong>Votre itinéraire</strong><br>
    Distance estimée: ${distance.toFixed(1)} km<br>
    Durée estimée: ${durationFormatted}
  `);
  
  // Ajuste la vue
  map.fitBounds(L.latLngBounds([start, end]), { padding: [50, 50] });
}

/**
 * Fonctions utilitaires
 */

// Limite les appels API (debounce)
function debounce(func, wait) {
  let timeout;
  return function () {
    const context = this;
    const args = arguments;
    clearTimeout(timeout);
    timeout = setTimeout(() => func.apply(context, args), wait);
  };
}

// Efface tous les marqueurs de la carte
function clearMarkers(map, markers) {
  markers.forEach(marker => {
    if (map.hasLayer(marker)) {
      map.removeLayer(marker);
    }
  });
  markers.length = 0;
}

// Crée un overlay de chargement
function createLoadingOverlay() {
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
  return loadingDiv;
}

// Génère des points intermédiaires pour un itinéraire
function generateIntermediateWaypoints(start, end) {
  const waypoints = [start];
  
  // Calcule la distance directe
  const distance = calculateHaversineDistance(
    start.lat, start.lng,
    end.lat, end.lng
  );
  
  // Détermine le nombre de points intermédiaires
  const numPoints = Math.max(2, Math.ceil(distance / 100));
  
  // Génère des points intermédiaires avec variation
  for (let i = 1; i < numPoints; i++) {
    const fraction = i / numPoints;
    
    // Interpolation linéaire entre les points
    const lat = start.lat + (end.lat - start.lat) * fraction;
    const lng = start.lng + (end.lng - start.lng) * fraction;
    
    // Ajoute une variation pour simuler des routes réelles
    const variation = Math.sin(fraction * Math.PI) * 0.15;
    const randomLat = lat + (Math.random() - 0.5) * variation;
    const randomLng = lng + (Math.random() - 0.5) * variation;
    
    waypoints.push(L.latLng(randomLat, randomLng));
  }
  
  waypoints.push(end);
  return waypoints;
}

// Calcule la distance entre deux points (Haversine)
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

// Convertit les degrés en radians
function toRad(degrees) {
  return degrees * Math.PI / 180;
}

// Calcule la distance d'un itinéraire complet
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
  
  // Ajoute 20% pour les détours réels
  return totalDistance * 1.2;
}

// Formate une durée en secondes en texte
function formatDuration(seconds) {
  const hours = Math.floor(seconds / 3600);
  const minutes = Math.round((seconds % 3600) / 60);
  
  if (hours > 0) {
    return `${hours}h ${minutes}min`;
  } else {
    return `${minutes} minutes`;
  }
}

// Formate une date
function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
}

// Formate un horaire
function formatTime(timeString) {
  if (!timeString) return '';
  
  // Si c'est juste une heure HH:MM:SS
  if (timeString.includes(':')) {
    const parts = timeString.split(':');
    return `${parts[0]}h${parts[1]}`;
  }
  
  // Si c'est une date complète
  const date = new Date(timeString);
  return date.toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit'
  });
}

/**
 * Affiche les covoiturages sur la carte
 */
function displayCarpoolsOnMap(carpools, map, markers) {
  const bounds = L.latLngBounds();
  let hasBounds = false;
  
  // Pour chaque covoiturage
  carpools.forEach(carpool => {
    // Vérifie que les coordonnées sont définies
    if (carpool.latStart && carpool.lngStart) {
      const startPoint = [parseFloat(carpool.latStart), parseFloat(carpool.lngStart)];
      
      // Étend les limites de la carte
      bounds.extend(startPoint);
      hasBounds = true;
      
      // Marqueur de départ
      const startMarker = L.marker(startPoint, {
        icon: L.divIcon({
          className: 'departure-marker',
          html: '<i class="fas fa-map-marker-alt" style="color: #007bff; font-size: 24px;"></i>',
          iconSize: [20, 20],
          iconAnchor: [10, 20]
        })
      }).addTo(map);
      
      // Popup du marqueur
      startMarker.bindPopup(`
        <strong>Départ: ${carpool.locationStart}</strong><br>
        Date: ${formatDate(carpool.dateStart)}<br>
        Heure: ${formatTime(carpool.hourStart)}<br>
        <a href="?id=${carpool.idCarpool}" class="btn btn-sm btn-primary mt-2">Voir détails</a>
      `);
      
      markers.push(startMarker);
      
      // Si les coordonnées d'arrivée sont aussi définies
      if (carpool.latReach && carpool.lngReach) {
        const endPoint = [parseFloat(carpool.latReach), parseFloat(carpool.lngReach)];
        
        // Étend les limites
        bounds.extend(endPoint);
        
        // Marqueur d'arrivée
        const endMarker = L.marker(endPoint, {
          icon: L.divIcon({
            className: 'arrival-marker',
            html: '<i class="fas fa-flag-checkered" style="color: #28a745; font-size: 24px;"></i>',
            iconSize: [20, 20],
            iconAnchor: [10, 20]
          })
        }).addTo(map);
        
        // Popup du marqueur
        endMarker.bindPopup(`
          <strong>Arrivée: ${carpool.locationReach}</strong><br>
          <a href="?id=${carpool.idCarpool}" class="btn btn-sm btn-primary mt-2">Voir détails</a>
        `);
        
        markers.push(endMarker);
        
        // Crée l'itinéraire
        const waypoints = generateIntermediateWaypoints(
          L.latLng(startPoint[0], startPoint[1]),
          L.latLng(endPoint[0], endPoint[1])
        );
        
        const routeLine = L.polyline(waypoints, {
          color: '#3388ff',
          weight: 3,
          opacity: 0.7,
          smoothFactor: 2
        }).addTo(map);
        
        // Calcule la distance et la durée
        const distance = calculateRouteDistance(waypoints);
        const durationSeconds = (distance / 80) * 3600;
        const durationFormatted = formatDuration(durationSeconds);
        
        // Popup de l'itinéraire
        routeLine.bindPopup(`
          <strong>Trajet: ${carpool.locationStart} → ${carpool.locationReach}</strong><br>
          Distance estimée: ${distance.toFixed(1)} km<br>
          Durée estimée: ${durationFormatted}<br>
          <a href="?id=${carpool.idCarpool}" class="btn btn-sm btn-primary mt-2">Voir détails</a>
        `);
        
        markers.push(routeLine);
      }
    }
  });
  
  // Ajuste la vue si des points ont été ajoutés
  if (hasBounds) {
    map.fitBounds(bounds, { padding: [50, 50] });
  }
}

/**
 * Affiche un covoiturage sélectionné sur la carte
 */
function showSelectedCarpoolOnMap(carpool, map, markers) {
  // Nettoie les marqueurs existants
  clearMarkers(map, markers);
  
  // Vérifie que toutes les coordonnées nécessaires sont présentes
  if (carpool.latStart && carpool.lngStart && carpool.latReach && carpool.lngReach) {
    const startPoint = [parseFloat(carpool.latStart), parseFloat(carpool.lngStart)];
    const endPoint = [parseFloat(carpool.latReach), parseFloat(carpool.lngReach)];
    
    // Crée les marqueurs et l'itinéraire
    const departureMarker = L.marker(startPoint, {
      icon: L.divIcon({
        className: 'departure-marker',
        html: '<i class="fas fa-map-marker-alt" style="color: #007bff; font-size: 24px;"></i>',
        iconSize: [20, 20],
        iconAnchor: [10, 20]
      })
    }).addTo(map).bindPopup(`Départ: ${carpool.locationStart}`);
    
    const arrivalMarker = L.marker(endPoint, {
      icon: L.divIcon({
        className: 'arrival-marker',
        html: '<i class="fas fa-flag-checkered" style="color: #28a745; font-size: 24px;"></i>',
        iconSize: [20, 20],
        iconAnchor: [10, 20]
      })
    }).addTo(map).bindPopup(`Arrivée: ${carpool.locationReach}`);
    
    // Crée l'itinéraire
    const waypoints = generateIntermediateWaypoints(
      L.latLng(startPoint[0], startPoint[1]),
      L.latLng(endPoint[0], endPoint[1])
    );
    
    const carpoolRoute = L.polyline(waypoints, {
      color: '#3388ff',
      weight: 4,
      opacity: 0.8,
      smoothFactor: 2
    }).addTo(map);
    
    // Calcule la distance et la durée
    const distance = calculateRouteDistance(waypoints);
    const durationSeconds = (distance / 80) * 3600;
    const durationFormatted = formatDuration(durationSeconds);
    
    // Popup de l'itinéraire
    carpoolRoute.bindPopup(`
      <strong>Trajet: ${carpool.locationStart} → ${carpool.locationReach}</strong><br>
      Distance estimée: ${distance.toFixed(1)} km<br>
      Durée estimée: ${durationFormatted}
    `).openPopup();
    
    // Stocke les marqueurs
    markers.push(departureMarker, arrivalMarker, carpoolRoute);
    
    // Ajuste la vue pour voir l'itinéraire complet
    map.fitBounds(L.latLngBounds([startPoint, endPoint]), { padding: [50, 50] });
  }
}

/**
 * Configure les filtres de recherche
 */
function setupFilters() {
  // Récupère les éléments DOM
  const creditRange = document.getElementById('credit-range');
  const creditDisplay = document.getElementById('credit-value');
  const filterButton = document.querySelector('.btn-modal-filtre');
  
  // Mise à jour de l'affichage des crédits
  if (creditRange && creditDisplay) {
    creditRange.addEventListener('input', function() {
      creditDisplay.textContent = `${this.value} crédits`;
    });
  }
  
  // Applique les filtres lorsqu'on clique sur le bouton
  if (filterButton) {
    filterButton.addEventListener('click', function() {
      // Récupère les valeurs des filtres
      const vehicleType = document.querySelector('input[name="vehicleType"]:checked').id;
      const passengerCount = document.getElementById('passager-count').value;
      const maxCredits = creditRange.value;
      const driverRating = parseFloat(document.getElementById('driver-rating').value);
      
      // Récupère les paramètres de recherche actuels
      const urlParams = new URLSearchParams(window.location.search);
      const depart = urlParams.get('depart') || '';
      const arrivee = urlParams.get('arrivee') || '';
      const date = urlParams.get('date') || '';
      
      // Construit les paramètres de la requête
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
      
      // Affiche l'indicateur de chargement
      const loadingDiv = createLoadingOverlay();
      document.body.appendChild(loadingDiv);
      
      // Effectue la requête AJAX
      fetch(`/covoiturage/filter?${filterParams.toString()}`)
        .then(response => {
          if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
          }
          return response.text();
        })
        .then(html => {
          // Supprime l'indicateur de chargement
          document.body.removeChild(loadingDiv);
          
          // Met à jour le contenu des résultats
          const resultsContainer = document.querySelector('.resultats');
          if (resultsContainer) {
            resultsContainer.innerHTML = html;
            
            // Réinitialise les boutons de détails
            if (typeof initDetailButtons === 'function') {
              initDetailButtons();
            }
          }
          
          // Met à jour l'URL
          const newUrl = window.location.pathname + '?' + filterParams.toString();
          window.history.pushState({ path: newUrl }, '', newUrl);
          
          // Sauvegarde les filtres dans sessionStorage
          sessionStorage.setItem('ecoride_filters', JSON.stringify({
            vehicleType,
            passengerCount: parseInt(passengerCount, 10),
            maxCredits,
            driverRating
          }));
          
          // Ferme la modale
          const modal = bootstrap.Modal.getInstance(document.getElementById('filterModal'));
          if (modal) {
            modal.hide();
          }
          
          // Met à jour la carte
          updateMapWithFilterResults(filterParams);
        })
        .catch(error => {
          console.error('Erreur lors du filtrage:', error);
          document.body.removeChild(loadingDiv);
          alert('Une erreur est survenue lors du filtrage. Veuillez réessayer.');
        });
    });
  }
  
  // Restaure les filtres depuis sessionStorage au chargement
  restoreFiltersFromSession();
}

/**
 * Restaure les filtres depuis sessionStorage
 */
function restoreFiltersFromSession() {
  const savedFilters = sessionStorage.getItem('ecoride_filters');
  if (savedFilters) {
    try {
      const filters = JSON.parse(savedFilters);
      
      // Restaure les valeurs dans la modale
      if (filters.vehicleType && document.getElementById(filters.vehicleType)) {
        document.getElementById(filters.vehicleType).checked = true;
      }
      
      if (filters.passengerCount) {
        const passengerInput = document.getElementById('passager-count');
        if (passengerInput) passengerInput.value = filters.passengerCount;
      }
      
      const creditRange = document.getElementById('credit-range');
      const creditDisplay = document.getElementById('credit-value');
      if (filters.maxCredits && creditRange) {
        creditRange.value = filters.maxCredits;
        if (creditDisplay) creditDisplay.textContent = `${filters.maxCredits} crédits`;
      }
      
      if (filters.driverRating) {
        const ratingInput = document.getElementById('driver-rating');
        if (ratingInput) ratingInput.value = filters.driverRating;
      }
    } catch (error) {
      console.error('Erreur lors de la restauration des filtres:', error);
      sessionStorage.removeItem('ecoride_filters');
    }
  }
}