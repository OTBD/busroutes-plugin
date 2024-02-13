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

    $routes = [];
    if (have_rows('routes','options')) {
        while (have_rows('routes','options')) {
            the_row();

            $start = get_sub_field('start');
            $finish = get_sub_field('finish');
            $color = get_sub_field('color');
            $icon = get_sub_field('icon');
            $icon_start = get_sub_field('icon_start');
            $icon_finish = get_sub_field('icon_finish');

            $stops = [];
            if (have_rows('stops')) {
                while (have_rows('stops')) {
                    the_row();
                    $stops[] = get_sub_field('stop');
                }
            }

            $routes[] = [
                'start' => $start,
                'finish' => $finish,
                'color' => $color,
                'icon' => $icon,
                'icon_start' => $icon_start,
                'icon_finish' => $icon_finish,
                'stops' => $stops
            ];
        }
    }

    function get_location($field_name) {
        $location = get_field($field_name, 'option');
        return array(
            'lat' => floatval($location['lat']),
            'lng' => floatval($location['lng'])
        );
    }

    // Assuming 'my-script' is the handle for your script
    wp_localize_script('busroutes', 'routesData', $routes);


    $map_center = get_location('map_center');
    $zoom_level = get_field('zoom_level', 'option');

    wp_localize_script('busroutes', 'mapPosCenter', $map_center);
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
                'key' => 'field_65ca7f3673adb',
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
                'key' => 'field_65ca7f2873ada',
                'label' => 'API Key',
                'name' => 'api_key',
                'aria-label' => '',
                'type' => 'text',
                'instructions' => 'Get the api key from google dev tools',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '80',
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
                'key' => 'field_65ca7f3f73adc',
                'label' => 'Zoom Level',
                'name' => 'zoom_level',
                'aria-label' => '',
                'type' => 'number',
                'instructions' => '0-19',
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
                'key' => 'field_65ca7f88907a2',
                'label' => 'Map Center',
                'name' => 'map_center',
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
                'key' => 'field_65ca7f9a245bd',
                'label' => 'Bus Routes',
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
                'key' => 'field_65ca7fa4245be',
                'label' => 'Routes',
                'name' => 'routes',
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
                'layout' => 'block',
                'pagination' => 0,
                'min' => 0,
                'max' => 0,
                'collapsed' => '',
                'button_label' => 'Add Row',
                'rows_per_page' => 20,
                'sub_fields' => array(
                    array(
                        'key' => 'field_65ca7fb7245bf',
                        'label' => 'Start',
                        'name' => 'start',
                        'aria-label' => '',
                        'type' => 'google_map',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '50',
                            'class' => '',
                            'id' => '',
                        ),
                        'center_lat' => '',
                        'center_lng' => '',
                        'zoom' => '',
                        'height' => '',
                        'parent_repeater' => 'field_65ca7fa4245be',
                    ),
                    array(
                        'key' => 'field_65ca7fc5245c0',
                        'label' => 'Finish',
                        'name' => 'finish',
                        'aria-label' => '',
                        'type' => 'google_map',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '50',
                            'class' => '',
                            'id' => '',
                        ),
                        'center_lat' => '',
                        'center_lng' => '',
                        'zoom' => '',
                        'height' => '',
                        'parent_repeater' => 'field_65ca7fa4245be',
                    ),
                    array(
                        'key' => 'field_65ca7fe1245c1',
                        'label' => 'Stops',
                        'name' => 'stops',
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
                                'key' => 'field_65ca7ff9245c2',
                                'label' => 'Stop',
                                'name' => 'stop',
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
                                'parent_repeater' => 'field_65ca7fe1245c1',
                            ),
                        ),
                        'parent_repeater' => 'field_65ca7fa4245be',
                    ),
                    array(
                        'key' => 'field_65ca8008245c3',
                        'label' => 'Color',
                        'name' => 'color',
                        'aria-label' => '',
                        'type' => 'color_picker',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '50',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'enable_opacity' => 0,
                        'return_format' => 'string',
                        'parent_repeater' => 'field_65ca7fa4245be',
                    ),
                    array(
                        'key' => 'field_65cbd39c62511',
                        'label' => 'Icon Start',
                        'name' => 'icon_start',
                        'aria-label' => '',
                        'type' => 'image',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '16.66',
                            'class' => '',
                            'id' => '',
                        ),
                        'return_format' => 'array',
                        'library' => 'all',
                        'min_width' => '',
                        'min_height' => '',
                        'min_size' => '',
                        'max_width' => '',
                        'max_height' => '',
                        'max_size' => '',
                        'mime_types' => '',
                        'preview_size' => 'medium',
                        'parent_repeater' => 'field_65ca7fa4245be',
                    ),
                    array(
                        'key' => 'field_65ca8018245c4',
                        'label' => 'Icon',
                        'name' => 'icon',
                        'aria-label' => '',
                        'type' => 'image',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '16.66',
                            'class' => '',
                            'id' => '',
                        ),
                        'return_format' => 'array',
                        'library' => 'all',
                        'min_width' => '',
                        'min_height' => '',
                        'min_size' => '',
                        'max_width' => '',
                        'max_height' => '',
                        'max_size' => '',
                        'mime_types' => '',
                        'preview_size' => 'medium',
                        'parent_repeater' => 'field_65ca7fa4245be',
                    ),
                    array(
                        'key' => 'field_65cbd3a762512',
                        'label' => 'Icon Finish',
                        'name' => 'icon_finish',
                        'aria-label' => '',
                        'type' => 'image',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '16.66',
                            'class' => '',
                            'id' => '',
                        ),
                        'return_format' => 'array',
                        'library' => 'all',
                        'min_width' => '',
                        'min_height' => '',
                        'min_size' => '',
                        'max_width' => '',
                        'max_height' => '',
                        'max_size' => '',
                        'mime_types' => '',
                        'preview_size' => 'medium',
                        'parent_repeater' => 'field_65ca7fa4245be',
                    ),
                ),
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