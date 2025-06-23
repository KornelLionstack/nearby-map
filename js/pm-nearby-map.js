
// Ensure globals provided by the Progress Map plugin exist so that
// the script doesn't raise "nearby_map is not defined" errors when the
// extension runs on its own.
window.nearby_map = window.nearby_map || {};
window.nearby_map_object = window.nearby_map_object || {};
window.origin = window.origin || {};

let customLocations = [];

let customMap;
let customMarkers = [];

function addCustomMarker(lat, lng, title, slug) {
    if (!customMap) return;
    const marker = new google.maps.Marker({
        position: { lat: parseFloat(lat), lng: parseFloat(lng) },
        map: customMap,
        title: title || "",
        icon: getIconByType(slug)
    });
    customMarkers.push(marker);
    return marker;
}

function clearCustomMarkers() {
    customMarkers.forEach(m => m.setMap(null));
    customMarkers = [];
}

function loadNearbyLocations(typeSlug) {
    const ajaxUrl = ( typeof cspm_nearby_map !== 'undefined' && cspm_nearby_map.ajax_url )
        ? cspm_nearby_map.ajax_url
        : '/wp-admin/admin-ajax.php';

    fetch(ajaxUrl + '?action=get_custom_nearby_locations')
        .then(response => response.json())
        .then(locations => {
            const filtered = typeSlug
                ? locations.filter(place => slugifyType(place.type) === typeSlug)
                : locations;

            clearCustomMarkers();

            filtered.forEach(place => {
                if (!place.lat || !place.lng) return;
                new google.maps.Marker({
                    position: { lat: place.lat, lng: place.lng },
                    map: customMap,
                    title: place.name
                });
            });
        })
        .catch(err => {
            console.error('Helyek betöltése sikertelen:', err);
        });
}

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


function onReady(callback) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback);
    } else {
        callback();
    }
}

onReady(function () {
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
    customMap = map;

    // Lekérjük a WP adminban megadott helyeket
    const ajaxUrl = ( typeof cspm_nearby_map !== 'undefined' && cspm_nearby_map.ajax_url ) ? cspm_nearby_map.ajax_url : '/wp-admin/admin-ajax.php';

    fetch(ajaxUrl + '?action=get_custom_nearby_locations')
        .then(response => response.json())
        .then(data => {
            console.log('Loaded locations from JSON:', data);
            customLocations = Array.isArray(data)
                ? data.map(p => Object.assign({}, p, { slug: slugifyType(p.type || '') }))
                : [];
            renderCustomTypeFilter(customLocations);
            bindCategoryHover();

            customLocations.forEach(place => {
                if (!place.lat || !place.lng) return;
                addCustomMarker(place.lat, place.lng, place.name || "Hely", place.slug);
            });
        })
        .catch(error => console.error('Hiba a helyek betöltésekor:', error));
}

// Egyszerű példa ikon választásra a type slug alapján
const typeAliases = {
    bowling: 'bowling_alley',
    bevasarlas_es_kiskereskedelem: 'shopping_mall',
    kozlekedes: 'bus_station',
    egeszsegugyi_letesitmenyek: 'hospital',
    oktatasi_intezmenyek: 'school',
    jszakai_let__klub: 'night_club',
    sport_es_rekreacio: 'stadium',
    ttermek: 'restaurant',
    kulturalis_helyszinek: 'museum',
    kavezok: 'cafe'
};

function getIconByType(slug) {
    if (typeof cspm_nearby_map !== 'undefined' && cspm_nearby_map.place_markers_file_url) {
        const mapped = typeAliases[slug] || slug;
        return cspm_nearby_map.place_markers_file_url + mapped + '.png';
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
        loadNearbyLocations(selected);
    });
}

function showFilteredLocations(typeSlug) {

    const markers = typeSlug ? customLocations.filter(loc => loc.slug === typeSlug) : customLocations;

    // töröljük az előzőket (feltételezzük, hogy a `cspm_markers` globális)
    clearCustomMarkers();

    markers.forEach(loc => {
        addCustomMarker(loc.lat, loc.lng, loc.name, loc.slug);
    });
}

function bindCategoryHover() {
    const cats = document.querySelectorAll('.cspm_nearby_cat_holder');
    cats.forEach(cat => {
        const slug = slugifyType(cat.getAttribute('id') || cat.dataset.proximityName || '');
        cat.addEventListener('mouseenter', () => {
            loadNearbyLocations(slug);
        });
        cat.addEventListener('mouseleave', () => {
            loadNearbyLocations('');
        });
        cat.addEventListener('click', () => {
            loadNearbyLocations(slug);
        });
    });
}
