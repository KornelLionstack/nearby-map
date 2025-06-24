document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.proximity_place_nearby_map_453');

    buttons.forEach(button => {
        button.addEventListener('click', function () {
            const typeLabel = this.dataset.proximityName;
            const mapId = this.dataset.mapId;
            const slug = slugify(typeLabel);

            fetch(ajaxurl + '?action=get_custom_nearby_locations')
                .then(response => response.json())
                .then(locations => {
                    const filtered = locations.filter(loc => slugify(loc.type) === slug);
                    if (filtered.length === 0) {
                        console.warn(`Nincs találat a '${slug}' slug-hoz.`);
                    }
                    displayMarkersOnMap(filtered, mapId);
                })
                .catch(err => console.error('Helyek betöltésekor hiba történt:', err));
        });
    });

    function slugify(text) {
        const accentsMap = new Map([
            ['á','a'],['é','e'],['í','i'],['ó','o'],['ö','o'],['ő','o'],['ú','u'],['ü','u'],['ű','u'],
            ['Á','a'],['É','e'],['Í','i'],['Ó','o'],['Ö','o'],['Ő','o'],['Ú','u'],['Ü','u'],['Ű','u']
        ]);
        let slug = text.toLowerCase().split('').map(char => accentsMap.get(char) || char).join('');
        return slug.replace(/[\s\-]+/g, '_').replace(/[^a-z0-9_]/g, '').trim();
    }

    function displayMarkersOnMap(locations, mapId) {
        const mapElement = document.getElementById(mapId);
        if (!mapElement) return console.error(`Nem található a térkép elem: ${mapId}`);

        const lat = parseFloat(mapElement.dataset.lat || 47.475);
        const lng = parseFloat(mapElement.dataset.lng || 19.04);
        const zoom = parseInt(mapElement.dataset.zoom || 13);

        const map = new google.maps.Map(mapElement, {
            center: { lat, lng },
            zoom: zoom,
            mapTypeId: 'roadmap'
        });

        locations.forEach(loc => {
            new google.maps.Marker({
                position: { lat: loc.lat, lng: loc.lng },
                map: map,
                title: loc.name
            });
        });
    }
});
