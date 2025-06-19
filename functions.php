<?php


// JSON helyek kilistázása AJAX-on keresztül
add_action('wp_ajax_get_custom_nearby_locations', 'return_custom_nearby_locations');
add_action('wp_ajax_nopriv_get_custom_nearby_locations', 'return_custom_nearby_locations');

function return_custom_nearby_locations() {
    $locations = get_option('custom_nearby_locations', '[]');
    header('Content-Type: application/json');
    echo $locations;
    wp_die();
}
