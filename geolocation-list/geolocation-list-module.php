<?php

function geolocation_list_scripts()
{
    // xdebug_break();
    $url = plugins_url('/js/geolocation-list.js', __FILE__);

    wp_enqueue_script('geolocation-list-js', plugins_url('/js/geolocation-list.js', __FILE__), array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'geolocation_list_scripts');

function generate_geolocation_list()
{
    xdebug_break();
    $overview_locations = get_posts(array('post_type' => 'overview_locations', 'posts_per_page' => -1));
    update_overview_locations();
    generate_geolocation_list_html($locations);
}

function update_overview_locations()
{
    $overview_locations = get_posts(array('post_type' => 'overview_locations', 'posts_per_page' => -1));
    $geolocations_array = generate_geolocation_array();
    foreach ($geolocations_array as $geolocation) {
        $geolocation_overview_location = $geolocation['overview_location'];

        $geolocation_seo_num_of_units_available = $geolocation['seo_num_of_units_available'];
        foreach ($overview_locations as $key => $overview_location) {
            if ($overview_location['name'] == $geolocation_overview_location) {
                $overview_locations[$key]['seo_num_of_units_available'] = $geolocation_seo_num_of_units_available;
            }
        }
    }
}

$overview_locations = array(
    array("path" => "storkobenhavn", "name" => "Storkøbenhavn", "seo_num_of_units_available" => 0),
    array("path" => "ostjylland", "name" => "Østjylland", "seo_num_of_units_available" => 0),
    array("path" => "sydjylland", "name" => "Sydjylland", "seo_num_of_units_available" => 0),
    array("path" => "fyn", "name" => "Fyn", "seo_num_of_units_available" => 0),
    array("path" => "midt-og-vestsjaelland", "name" => "Midt- og Vestsjælland", "seo_num_of_units_available" => 0),
    array("path" => "nordjylland", "name" => "Nordjylland", "seo_num_of_units_available" => 0),
    array("path" => "nordsjaelland", "name" => "Nordsjælland", "seo_num_of_units_available" => 0),
    array("path" => "vestjylland", "name" => "Vestjylland", "seo_num_of_units_available" => 0),
    array("path" => "sydsjaelland", "name" => "Sydsjælland", "seo_num_of_units_available" => 0),
    array("path" => "bornholm", "name" => "Bornholm", "seo_num_of_units_available" => 0)
);

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
    return $geolocations_array;
}
//generate div with a list of all geolocations, save to a file to be retrieved by a shortcode
function generate_geolocation_list_html($locations)
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
                    foreach ($geolocations_array as $geolocation) {
                    ?>
                        <div class="ep-location-navigator__location-link" data-path="<?php echo $geolocation['id']; ?>" data-name="<?php echo $geolocation['name']; ?>" onclick="toggleList('<?php echo $geolocation['id']; ?>')">
                            <p><?php echo $geolocation['name']; ?> (<?php echo $geolocation['seo_num_of_units_available']; ?>)</p>
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
