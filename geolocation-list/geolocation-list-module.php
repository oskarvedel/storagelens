<?php

function geolocation_list_scripts()
{
    $url = plugins_url('/js/geolocation-list.js', __FILE__);

    wp_enqueue_script('geolocation-list-js', plugins_url('/js/geolocation-list.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('geolocation-list-js', 'theme', array('uri' => get_template_directory_uri()));
}
add_action('wp_enqueue_scripts', 'geolocation_list_scripts');

function generate_geolocation_list()
{
    xdebug_break();
    $geolocations_array = generate_geolocation_array();
    generate_frontend_geolocation_arrays($geolocations_array);
    update_overview_locations($geolocations_array);
    $overview_locations = get_posts(array('post_type' => 'overview_location', 'posts_per_page' => -1));
    generate_geolocation_list_html($overview_locations);
}

function update_overview_locations($geolocations_array)
{
    $overview_locations = get_posts(array('post_type' => 'overview_location', 'posts_per_page' => -1));

    foreach ($overview_locations as $overview_location) {
        $seo_num_of_units_available_old = get_post_meta($overview_location->ID, 'seo_num_of_units_available', true);
        $seo_num_of_units_available_new = 0;
        $seo_num_of_gd_places_old = get_post_meta($overview_location->ID, 'seo_num_of_gd_places', true);
        $seo_num_of_gd_places_new = 0;
        foreach ($geolocations_array as $geolocation) {
            if ($overview_location->ID == $geolocation['overview_location']) {
                $seo_num_of_units_available_new += $geolocation['seo_num_of_units_available'];
                $seo_num_of_gd_places_new = $seo_num_of_gd_places_new + count(get_post_meta($geolocation['id'], 'seo_gd_place_list', false));
            }
        }
        if ($seo_num_of_units_available_old != $seo_num_of_units_available_new) {
            trigger_error("SEO num of units available for overview location " . get_the_title($overview_location->ID) . " has changed from " . $seo_num_of_units_available_old . " to " . $seo_num_of_units_available_new, E_USER_NOTICE);
            update_post_meta($overview_location->ID, 'seo_num_of_units_available', $seo_num_of_units_available_new);
        }
        if ($seo_num_of_gd_places_old != $seo_num_of_gd_places_new) {
            trigger_error("SEO num of gd places for overview location " . get_the_title($overview_location->ID) . " has changed from " . $seo_num_of_gd_places_old . " to " . $seo_num_of_gd_places_new, E_USER_NOTICE);
            update_post_meta($overview_location->ID, 'seo_num_of_gd_places', $seo_num_of_gd_places_new);
        }
    }
}

function generate_geolocation_array()
{
    //get all geolocations
    $geolocations = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1));
    $geolocations_array = array();
    foreach ($geolocations as $geolocation) {
        $geolocation_id = $geolocation->ID;
        $geolocations_array[] = array(
            "id" => $geolocation_id,
            "name" => get_the_title($geolocation_id),
            "seo_num_of_units_available" => get_post_meta($geolocation_id, 'seo_num_of_units_available', true),
            "overview_location" => get_post_meta($geolocation_id, 'overview_location', true)
        );

        if (empty($geolocations_array[count($geolocations_array) - 1]['seo_num_of_units_available'])) {
            $geolocations_array[count($geolocations_array) - 1]['seo_num_of_units_available'] = 0;
        }
    }
    //save the array to a file
    file_put_contents(get_template_directory() . '/geolocations-array.json', json_encode($geolocations_array));
    return $geolocations_array;
}

function generate_frontend_geolocation_arrays($geolocations_array)
{
    //go through the overview locations and create an array for each overview location
    $overview_locations = get_posts(array('post_type' => 'overview_location', 'posts_per_page' => -1));
    $frontend_geolocations_array = array();
    foreach ($overview_locations as $overview_location) {
        $frontend_geolocations_array[$overview_location->ID] = array();
        foreach ($geolocations_array as $geolocation) {
            if ($overview_location->ID == $geolocation['overview_location']) {
                $frontend_geolocations_array[$overview_location->ID][] = array(
                    'name' => $geolocation['name'],
                    'seo_num_of_units_available' => $geolocation['seo_num_of_units_available']
                );
            }
        }
    }
    //save the arrays to a file
    file_put_contents(get_template_directory() . '/frontend-geolocations-array.json', json_encode($frontend_geolocations_array));
}
//generate div with a list of all geolocations, save to a file to be retrieved by a shortcode
function generate_geolocation_list_html($overview_locations)
{
    ob_start();
?>
    <div class="ep-location-navigator">
        <div class="ep-location-navigator__container">
            <div class="ep-location-navigator__header">
                <h2>Geolokationer</h2>
            </div>
            <div class="ep-location-navigator__content">
                <div class="ep-location-navigator__location-list">
                    <?php
                    foreach ($overview_locations as $overview_location) {
                        $seo_num_of_units_available = get_post_meta($overview_location->ID, 'seo_num_of_units_available', true);
                    ?>
                        <div class="ep-location-navigator__location-link" data-path="<?php echo $overview_location->ID; ?>" data-name="<?php echo $overview_location->post_title; ?>" onclick="toggleList('<?php echo $overview_location->ID; ?>')">
                            <p><?php echo $overview_location->post_title; ?> (<?php echo $seo_num_of_units_available ?> ledige depotrum)</p>
                        </div>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
<?php
    $geolocation_list = ob_get_clean();
    file_put_contents(get_template_directory() . '/geolocation-list.html', $geolocation_list);
}

//shortcode to output the geolocation list
function geolocation_list_shortcode_func($atts)
{
    $geolocation_list = file_get_contents(get_template_directory() . '/geolocation-list.html');
    return $geolocation_list;
}
add_shortcode('geolocation_list_shortcode', 'geolocation_list_shortcode_func');
