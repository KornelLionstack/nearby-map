
document.addEventListener('DOMContentLoaded', function () {
    if (typeof google !== 'undefined' && google.maps) {
        initCustomNearbyMap();
    }
});

function initCustomNearbyMap() {
    const mapElement = document.getElementById('map');
    if (!mapElement) return;

    const map = new google.maps.Map(mapElement, {
        center: { lat: 47.4979, lng: 19.0402 }, // Budapest default
        zoom: 13
    });

    // Lekérjük a WP adminban megadott helyeket
    fetch('/wp-admin/admin-ajax.php?action=get_custom_nearby_locations')
        .then(response => response.json())
        .then(data => {
            data.forEach(place => {
                if (!place.lat || !place.lng) return;
                new google.maps.Marker({
                    position: { lat: parseFloat(place.lat), lng: parseFloat(place.lng) },
                    map: map,
                    title: place.name || 'Hely',
                    icon: getIconByType(place.type)
                });
            });
        })
        .catch(error => console.error('Hiba a helyek betöltésekor:', error));
}

// Egyszerű példa ikon választásra type szerint
function getIconByType(type) {
    const icons = {
        'bowling': 'https://maps.google.com/mapfiles/kml/shapes/sports.png',
        'gym': 'https://maps.google.com/mapfiles/kml/shapes/library_maps.png',
        'default': 'https://maps.google.com/mapfiles/kml/shapes/info-i_maps.png'
    };
    return icons[type] || icons['default'];
}



// === CUSTOM TYPE FILTER (ONLY FROM JSON) ===

function getCustomTypesFromJSON(locations) {
    const types = new Set();
    locations.forEach(loc => {
        if (loc.type) types.add(loc.type);
    });
    return Array.from(types);
}

// Feltételezzük, hogy `customLocations` globálisan elérhető (betöltve már)
function renderCustomTypeFilter(locations) {
    const container = document.querySelector('#cspm-filter'); // vagy más ID osztály, ha más a HTML
    if (!container) return;

    const types = getCustomTypesFromJSON(locations);

    let html = '<select id="custom-type-selector"><option value="">– Összes típus –</option>';
    types.forEach(type => {
        html += `<option value="${type}">${type}</option>`;
    });
    html += '</select>';

    container.innerHTML = html;

    document.querySelector('#custom-type-selector').addEventListener('change', function () {
        const selected = this.value;
        showFilteredLocations(selected);
    });
}

function showFilteredLocations(type) {
    const mapContainer = document.querySelector('#cspm-map');
    if (!mapContainer || typeof customLocations === "undefined") return;

    const markers = type ? customLocations.filter(loc => loc.type === type) : customLocations;

    // töröljük az előzőket (feltételezzük, hogy a `cspm_markers` globális)
    cspm_clearAllMarkers();

    markers.forEach(loc => {
        cspm_addMarker(loc.lat, loc.lng, loc.name, loc.type);
    });
}
