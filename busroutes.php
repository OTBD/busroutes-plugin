<?php
/**
 * Plugin Name: Bus Routes
 * Description: A googlemaps feature that creates a list of bus routes.
 * Version: 1.0
 * Author: Owen Dawson
 */

 function my_acf_google_map_api( $api ){
    $apiKey = get_field('my_map_plugin_api_key','option');

    $api['key'] = $apiKey;
    return $api;
}
add_filter('acf/fields/google_map/api', 'my_acf_google_map_api');


 function enqueue_map_plugin_scripts() {
    // Get the API key from the ACF field
    $apiKey = get_field('my_map_plugin_api_key','option');

    // Enqueue your custom script and pass the API key to it
    wp_enqueue_script('busroutes', plugin_dir_url(__FILE__) . 'app.js', array(), '1.0', true);
    wp_script_add_data('busroutes', 'type', 'module');

    // Register the Google Maps script with the API key from the ACF field
    wp_register_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . $apiKey . '&callback=initMap', array(), null, true);

    // Add a filter to add the 'async' attribute
    add_filter('script_loader_tag', function($tag, $handle) {
        
        if ('google-maps' !== $handle)
            return $tag;
            return str_replace( ' src', ' async="async" src', $tag );
        }, 10, 2);
        

    // Enqueue the Google Maps script
    wp_enqueue_script('google-maps');

    function get_bus_route_stops($route_name) {
        $stops = array();
    
        // check if the repeater field has rows of data
        if( have_rows($route_name, 'options') ):
    
            // loop through the rows of data
            while ( have_rows($route_name, 'options') ) : the_row();
    
                // get the sub field values
                $lat = get_sub_field('lat');
                $lng = get_sub_field('lng');
    
                // add the coordinates to the array
                $stops[] = array(
                    'lat' => floatval($lat),
                    'lng' => floatval($lng)
                );
    
            endwhile;
    
        endif;
    
        return $stops;
    }
    
    $bus_route_1_stops = get_bus_route_stops('bus_route_1_stops');
    $bus_route_2_stops = get_bus_route_stops('bus_route_2_stops');

    function get_location($field_name) {
        $location = get_field($field_name, 'option');
        return array(
            'lat' => floatval($location['lat']),
            'lng' => floatval($location['lng'])
        );
    }

    
    $bsr1start = get_location('bus_route_1_start');
    $bsr1finish = get_location('bus_route_1_finish');
    $bsr2start = get_location('bus_route_2_start');
    $bsr2finish = get_location('bus_route_2_finish');
    $map_center = get_location('map_center');
    $zoom_level = get_field('zoom_level', 'option');

    wp_localize_script('busroutes', 'mapPosCenter', $map_center);
    wp_localize_script('busroutes', 'bsr1start', $bsr1start);
    wp_localize_script('busroutes', 'bsr1finish', $bsr1finish);
    wp_localize_script('busroutes', 'bsr2start', $bsr2start);
    wp_localize_script('busroutes', 'bsr2finish', $bsr2finish);
    wp_localize_script('busroutes', 'busRoute1Stops', $bus_route_1_stops);
    wp_localize_script('busroutes', 'busRoute2Stops', $bus_route_2_stops);
    wp_localize_script('busroutes', 'mapData', array('zoomLevel' => $zoom_level));


}
add_action('wp_enqueue_scripts', 'enqueue_map_plugin_scripts');




function map_block_shortcode($atts = [], $content = null) {
    // normalize attribute keys, lowercase
    $atts = array_change_key_case((array)$atts, CASE_LOWER);

    // override default attributes with user attributes
    $wporg_atts = shortcode_atts([
        'block-id' => 'map-block',
    ], $atts);

    ob_start(); // Start output buffering

    echo '<div class="flex flex-wrap md:flex-nowrap m-auto items-center bg-primary">';
    echo '    <div id="map" class="h-screen w-full md:w-1/2"></div>';
    echo '    <div id="locationsearch" class="w-full md:w-1/2 px-10 text-center py-7 text-white flex flex-col items-center justify-center">';
    echo '        <h2 class="text-3xl my-5 text-white">Find your closest bus stop</h2>';
    echo '        <p>The School is easily accessible using both public or private transport links.</p>';
    echo '        <form id="location-form" class="flex flex-wrap justify-center text-primary">';
    echo '            <input type="text" class="p-4 w-full mb-5" id="user-location" placeholder="Enter your location">';
    echo '            <button class="px-10 py-5 bg-white" type="submit">Find closest bus stop</button>';
    echo '        </form>';
    echo '    </div>';
    echo '</div>';

    return ob_get_clean(); // Return the buffered output
}

add_shortcode('map_block', 'map_block_shortcode');

function busroutes_options_page() {

    if( function_exists('acf_add_options_page') ) {
        acf_add_options_page(array(
            'page_title' 	=> 'Bus Routes Settings',
            'menu_title'	=> 'Bus Routes',
            'menu_slug' 	=> 'busroutes-settings',
            'capability'	=> 'edit_posts',
            'redirect'		=> false
        ));
    }
}
add_action('admin_menu', 'busroutes_options_page');

if(function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group( array(
        'key' => 'group_65c93c7450f1a',
        'title' => 'Plugin',
        'fields' => array(
            array(
                'key' => 'field_65c93c744441a',
                'label' => 'Settings',
                'name' => '',
                'aria-label' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'placement' => 'top',
                'endpoint' => 0,
            ),
            array(
                'key' => 'field_65c93c8c4441b',
                'label' => 'Api Key',
                'name' => 'my_map_plugin_api_key',
                'aria-label' => '',
                'type' => 'text',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'maxlength' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
            ),
            array(
                'key' => 'field_65c93ca14441c',
                'label' => 'Map Center',
                'name' => 'map_center',
                'aria-label' => '',
                'type' => 'google_map',
                'instructions' => 'This will create the centre location for your map.',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '80',
                    'class' => '',
                    'id' => '',
                ),
                'center_lat' => '',
                'center_lng' => '',
                'zoom' => '',
                'height' => '',
            ),
            array(
                'key' => 'field_65c93cd24441d',
                'label' => 'Zoom Level',
                'name' => 'zoom_level',
                'aria-label' => '',
                'type' => 'number',
                'instructions' => '0 - 19',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '20',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => 10,
                'min' => '',
                'max' => '',
                'placeholder' => '',
                'step' => '',
                'prepend' => '',
                'append' => '',
            ),
            array(
                'key' => 'field_65c93d29139d8',
                'label' => 'Bus Route 1',
                'name' => '',
                'aria-label' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'placement' => 'top',
                'endpoint' => 0,
            ),
            array(
                'key' => 'field_65c93dd283e07',
                'label' => 'Bus Route 1 Start',
                'name' => 'bus_route_1_start',
                'aria-label' => '',
                'type' => 'google_map',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'center_lat' => '',
                'center_lng' => '',
                'zoom' => '',
                'height' => '',
            ),
            array(
                'key' => 'field_65c93d34139d9',
                'label' => 'Bus Route 1 Stops',
                'name' => 'bus_route_1_stops',
                'aria-label' => '',
                'type' => 'repeater',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'layout' => 'table',
                'pagination' => 0,
                'min' => 0,
                'max' => 0,
                'collapsed' => '',
                'button_label' => 'Add Row',
                'rows_per_page' => 20,
                'sub_fields' => array(
                    array(
                        'key' => 'field_65c93db0c801c',
                        'label' => 'Lat',
                        'name' => 'lat',
                        'aria-label' => '',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '50',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'maxlength' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'parent_repeater' => 'field_65c93d34139d9',
                    ),
                    array(
                        'key' => 'field_65c93dbec801d',
                        'label' => 'Lng',
                        'name' => 'lng',
                        'aria-label' => '',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '50',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'maxlength' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'parent_repeater' => 'field_65c93d34139d9',
                    ),
                ),
            ),
            array(
                'key' => 'field_65c93de683e08',
                'label' => 'Bus Route 1 Finish',
                'name' => 'bus_route_1_finish',
                'aria-label' => '',
                'type' => 'google_map',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'center_lat' => '',
                'center_lng' => '',
                'zoom' => '',
                'height' => '',
            ),
            array(
                'key' => 'field_65c93fbbabf02',
                'label' => 'Bus Route 2',
                'name' => '',
                'aria-label' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'placement' => 'top',
                'endpoint' => 0,
            ),
            array(
                'key' => 'field_65c93fc9abf03',
                'label' => 'Bus Route 2 Start',
                'name' => 'bus_route_2_start',
                'aria-label' => '',
                'type' => 'google_map',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'center_lat' => '',
                'center_lng' => '',
                'zoom' => '',
                'height' => '',
            ),
            array(
                'key' => 'field_65c93fd7abf04',
                'label' => 'Bus Route 2 Stops',
                'name' => 'bus_route_2_stops',
                'aria-label' => '',
                'type' => 'repeater',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'layout' => 'table',
                'pagination' => 0,
                'min' => 0,
                'max' => 0,
                'collapsed' => '',
                'button_label' => 'Add Row',
                'rows_per_page' => 20,
                'sub_fields' => array(
                    array(
                        'key' => 'field_65c93fd7abf05',
                        'label' => 'Lat',
                        'name' => 'lat',
                        'aria-label' => '',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '50',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'maxlength' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'parent_repeater' => 'field_65c93fd7abf04',
                    ),
                    array(
                        'key' => 'field_65c93fd7abf06',
                        'label' => 'Lng',
                        'name' => 'lng',
                        'aria-label' => '',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '50',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'maxlength' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'parent_repeater' => 'field_65c93fd7abf04',
                    ),
                ),
            ),
            array(
                'key' => 'field_65c93fe6abf07',
                'label' => 'Bus Route 2 Finish',
                'name' => 'bus_route_2_finish',
                'aria-label' => '',
                'type' => 'google_map',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'center_lat' => '',
                'center_lng' => '',
                'zoom' => '',
                'height' => '',
            ),
        ),
    
        'location' => array(
            array(
                array(
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'busroutes-settings',
                ),
            ),
        ),
    ));
}