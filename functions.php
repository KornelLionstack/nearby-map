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
/**
 * Convert a place type label into a slug that matches
 * the icon file naming convention.
 *
 * @param string $type Raw type label from JSON.
 * @return string Sanitized slug using underscores as separators.
 */
function cspmnm_slugify_type( $type ) {
    $type = strtolower( $type );

    // Remove accents to match the JavaScript slugify implementation
    if ( function_exists( 'remove_accents' ) ) {
        $type = remove_accents( $type );
    }

    $type = preg_replace( '/[\s-]+/', '_', $type );
    $type = preg_replace( '/[^a-z0-9_]/', '', $type );
    return trim( $type, '_' );
}

function cspmnm_get_json_types() {
    $types = array();
    $json  = wp_unslash( get_option('custom_nearby_locations', '' ) );

    if ( ! empty( $json ) ) {
        $locations = json_decode( $json, true );
        if ( is_array( $locations ) ) {
            foreach ( $locations as $location ) {
                if ( isset( $location['type'] ) && $location['type'] !== '' ) {
                    $slug  = cspmnm_slugify_type( $location['type'] );
                    if ( ! isset( $types[ $slug ] ) ) {
                        $types[ $slug ] = ucwords( str_replace( array( '_', '-' ), ' ', $location['type'] ) );
                    }
                }
            }
        }
    }

    return $types;
}

// Filter Progress Map image paths to use this plugin's icons
add_filter('csnm_img_file', 'cspmnm_custom_img_file');
add_filter('csnm_place_markers_file', 'cspmnm_custom_marker_path');

function cspmnm_custom_img_file( $default_url ) {
    return plugin_dir_url( __FILE__ ) . 'img/';
}

function cspmnm_custom_marker_path( $default_url ) {
    return plugin_dir_url( __FILE__ ) . 'img/place_types_markers/';
}

// Load Google Maps API asynchronously for better performance
add_filter( 'script_loader_tag', 'cspmnm_defer_google_maps', 10, 2 );
function cspmnm_defer_google_maps( $tag, $handle ) {
    if ( strpos( $tag, 'maps.googleapis.com/maps/api/js' ) !== false ) {
        $tag = str_replace( '<script ', '<script defer ', $tag );
    }
    return $tag;
}
