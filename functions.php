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

/**
 * Helper: parse custom nearby locations and extract unique place types.
 *
 * @return array Array of slug => label pairs for each custom type.
 */
function cspmnm_get_json_types() {
    $types = array();
    $json  = wp_unslash( get_option('custom_nearby_locations', '' ) );

    if ( ! empty( $json ) ) {
        $locations = json_decode( $json, true );
        if ( is_array( $locations ) ) {
            foreach ( $locations as $location ) {
                if ( isset( $location['type'] ) && $location['type'] !== '' ) {
                    $slug  = sanitize_title( $location['type'] );
                    if ( ! isset( $types[ $slug ] ) ) {
                        $types[ $slug ] = ucwords( str_replace( array( '_', '-' ), ' ', $location['type'] ) );
                    }
                }
            }
        }
    }

    return $types;
}
