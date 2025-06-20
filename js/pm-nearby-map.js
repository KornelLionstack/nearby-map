
let customLocations = [];

function slugifyType(type) {
    return type
        .toLowerCase()
        // Remove accents so "Kávézó" becomes "kavezo".
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .trim()
        .replace(/[\s-]+/g, '_')
        .replace(/[^a-z0-9_]/g, '')
        .replace(/^_+|_+$/g, '');
}

document.addEventListener('DOMContentLoaded', function () {
    if (typeof google !== 'undefined' && google.maps) {
        initCustomNearbyMap();
    }
});

function initCustomNearbyMap() {
    let mapElement = document.getElementById('map');
    if (!mapElement) {
        // Progress Map plugin uses containers like
        // <div class="cspm_map_container"><div id="codespacing_progress_map_x"></div></div>
        mapElement = document.querySelector('.cspm_map_container div[id^="codespacing_progress_map_"]');
    }
    if (!mapElement) return;

    const map = new google.maps.Map(mapElement, {
        center: { lat: 47.4979, lng: 19.0402 }, // Budapest default
        zoom: 13
    });

    // Lekérjük a WP adminban megadott helyeket
    const ajaxUrl = ( typeof cspm_nearby_map !== 'undefined' && cspm_nearby_map.ajax_url ) ? cspm_nearby_map.ajax_url : '/wp-admin/admin-ajax.php';

    fetch(ajaxUrl + '?action=get_custom_nearby_locations')
        .then(response => response.json())
        .then(data => {
            customLocations = Array.isArray(data)
                ? data.map(p => Object.assign({}, p, { slug: slugifyType(p.type || '') }))
                : [];
            renderCustomTypeFilter(customLocations);

            customLocations.forEach(place => {
                if (!place.lat || !place.lng) return;
                new google.maps.Marker({
                    position: { lat: parseFloat(place.lat), lng: parseFloat(place.lng) },
                    map: map,
                    title: place.name || 'Hely',
                    icon: getIconByType(place.slug)
                });
            });
        })
        .catch(error => console.error('Hiba a helyek betöltésekor:', error));
}

// Egyszerű példa ikon választásra a type slug alapján
function getIconByType(slug) {
    if (typeof cspm_nearby_map !== 'undefined' && cspm_nearby_map.place_markers_file_url) {
        return cspm_nearby_map.place_markers_file_url + slug + '.png';
    }
    return null;
}



// === CUSTOM TYPE FILTER (ONLY FROM JSON) ===

function getCustomTypesFromJSON(locations) {
    const types = new Map();
    locations.forEach(loc => {
        if (loc.slug && loc.type && !types.has(loc.slug)) {
            types.set(loc.slug, loc.type);
        }
    });
    return types;
}

// Feltételezzük, hogy `customLocations` globálisan elérhető (betöltve már)
function renderCustomTypeFilter(locations) {
    const container = document.querySelector('#cspm-filter'); // vagy más ID osztály, ha más a HTML
    if (!container) return;

    const types = getCustomTypesFromJSON(locations);

    let html = '<select id="custom-type-selector"><option value="">– Összes típus –</option>';
    types.forEach((label, slug) => {
        html += `<option value="${slug}">${label}</option>`;
    });
    html += '</select>';

    container.innerHTML = html;

    document.querySelector('#custom-type-selector').addEventListener('change', function () {
        const selected = this.value;
        showFilteredLocations(selected);
    });
}

function showFilteredLocations(typeSlug) {
    const mapContainer = document.querySelector('#cspm-map');
    if (!mapContainer || typeof customLocations === "undefined") return;

    const markers = typeSlug ? customLocations.filter(loc => loc.slug === typeSlug) : customLocations;

    // töröljük az előzőket (feltételezzük, hogy a `cspm_markers` globális)
    cspm_clearAllMarkers();

    markers.forEach(loc => {
        cspm_addMarker(loc.lat, loc.lng, loc.name, loc.slug);
    });
}
