<?php

function remove_scraper_data($supplier_name)
{
    remove_unit_links($supplier_name);
    remove_unit_types($supplier_name);
    remove_depotrum_custom_fields($supplier_name);
}

function import_scraper_data($supplier_name)
{
    trigger_error('scraper started', E_USER_NOTICE);
    // Get the user ID for the supplier
    $user = get_user_by('login', $supplier_name);
    $user_id = $user ? $user->ID : 0; // If the user doesn't exist, use 0
    if ($user_id == 0) {
        trigger_error('User ' . $supplier_name .  ' not found', E_USER_WARNING);
        return;
    }

    if ($supplier_name == "nettolager") {
        $file = plugins_url('/nettolager.json', __FILE__);
        $contextOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ];

        $context = stream_context_create($contextOptions);
        $json = file_get_contents($file, false, $context);

        //open the file and serialize the json data
        $data = json_decode($json, true);
        unset($json);

        // xdebug_break();
        //serialize the data
        $sanitized_data = sanitize_nettolager_data($data);
        unset($data);
    } else {
        trigger_error('waking the render service', E_USER_NOTICE);

        //set the timeout to 5 seconds
        add_filter('http_request_timeout', function () {
            return 5;
        });
        //wake the render service
        $url = 'https://boxdepotet-unit-scraper.onrender.com/screenshot/https://www.dr.dk';

        wp_remote_get($url);
        //sleep for 1 min while the service spins up
        trigger_error('sleeping for 30 seconds to let render spin up', E_USER_NOTICE);
        sleep(30);
        trigger_error('sleep over, calling render scrape function', E_USER_NOTICE);
        //set the timeout to 20 minutes
        add_filter('http_request_timeout', function () {
            return 1200;
        });

        trigger_error('calling  ' . $supplier_name . ' scraper', E_USER_NOTICE);
        $url = 'https://boxdepotet-unit-scraper.onrender.com/scrape/' . $supplier_name;

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            if ('http_request_failed' === $response->get_error_code()) {
                $error_message = 'The HTTP request timed out.';
            }
            trigger_error('Error getting data: ' . $error_message, E_USER_WARNING);
            return;
        }

        //check if the response body is empty
        if (empty($response['body'])) {
            trigger_error('render ' . $supplier_name . ' response data is empty, scheduling a new call in 30 sec', E_USER_WARNING);

            //schedule a new run of the scraper in 5 minutes
            $timestamp = wp_next_scheduled('scraper');
            if ($timestamp == false) {
                wp_schedule_single_event(time() + 30, 'run_scraper_action', array($supplier_name));
            }
            return;
        }

        //if the response body is contains the word "error" then log the error and return
        if (strpos($response['body'], 'error') !== false) {
            trigger_error('render ' . $supplier_name . ' response data contains error, scheduling a new call in 30 sec', E_USER_WARNING);

            $timestamp = wp_next_scheduled('scraper');
            if ($timestamp == false) {
                wp_schedule_single_event(time() + 30, 'run_scraper_action', array($supplier_name));
            }
            return;
        }

        //open the file and serialize the json data
        $json = $response['body'];

        //open the file and serialize the json data
        $data = json_decode($json, true);
        unset($json);

        //check if there is any data
        if (empty($data)) {
            trigger_error('render ' . $supplier_name . ' response data is empty, scheduling a new call in 1 mins', E_USER_WARNING);
            $timestamp = wp_next_scheduled('scraper');
            if ($timestamp == false) {
                wp_schedule_single_event(time() + 60, 'run_scraper_action', array($supplier_name));
            }
            return;
            return;
        }

        //log the data to console
        // trigger_error('boxdepotet data: ' . print_r($data, true), E_USER_NOTICE);
        //serialize the data

        // xdebug_break();

        if ($supplier_name == "boxdepotet") {
            $sanitized_data = sanitize_boxdepotet_data($data);
        } else if ($supplier_name == "nettolager") {
            $sanitized_data = sanitize_nettolager_data($data);
        } else if ($supplier_name == "pelican") {
            $sanitized_data = sanitize_pelican_data($data);
        } else if ($supplier_name == "cityselfstorage") {
            $sanitized_data = sanitize_cityselfstorage_data($data);
        } else if ($supplier_name == "shurgard") {
            // $sanitized_data = sanitize_shurgard_data($data);
        }
        unset($data);
    }

    remove_unit_links($supplier_name);
    //get the ids and urls of the locations

    $locations_urls = get_all_locations_ids_and_partner_department_urls($supplier_name);

    //get the unique units
    $unique_units = get_unique_units($sanitized_data, $supplier_name);

    //get any existing unit types
    $existing_unit_types = get_unit_types($supplier_name);

    //create the unit types
    $new_unit_types = create_unit_types($unique_units, $user_id, $existing_unit_types, $supplier_name);
    trigger_error('Created ' . count($new_unit_types) . ' ' .  $supplier_name . ' unit types', E_USER_NOTICE);

    unset($unique_units);

    //set the unit_types to the existing unit types + the new unit types
    $unit_types = $existing_unit_types + $new_unit_types;
    //make sure each unit type in the array has a unique id
    $unit_types = array_unique($unit_types);

    //create the unit links
    create_unit_links($sanitized_data, $locations_urls, $unit_types, $user_id, $supplier_name);
    trigger_error('imported ' .  $supplier_name . ' scraper data', E_USER_NOTICE);
    return;
}

function remove_depotrum_custom_fields($supplier_name)
{
    $args = array(
        'post_type' => 'gd_place',
        'author_name' => $supplier_name,
        'posts_per_page' => -1,
        'fields' => 'ids'
    );

    $ids = get_posts($args);

    foreach ($ids as $id) {
        delete_post_meta($id, 'depotrum');
    }
}

function create_unit_links($sanitized_data, $locations_urls, $unit_types, $user_id, $supplier_name)
{
    $batch_size = 10; // Adjust this to a suitable size

    // Split the data into batches
    $batches = array_chunk($sanitized_data, $batch_size);

    // Use WordPress's built-in object caching, if available, to store titles
    $cached_titles = [];

    $gd_places_not_found = 0;

    $unit_links_created = 0;

    // Loop through the batches
    foreach ($batches as $batch_index => $batch) {
        // Loop through the data in the current batch
        foreach ($batch as $item) {
            // Get the gd_place id
            $gd_place_id = array_search($item['url'], $locations_urls);
            if (!$gd_place_id) {
                trigger_error('gd_place not found for ' . $supplier_name . ' url: ' . $item['url'], E_USER_WARNING);
                $gd_places_not_found++;
                continue;
            }

            // Check if the title is already cached
            if (!isset($cached_titles[$gd_place_id])) {
                $cached_titles[$gd_place_id] = get_the_title($gd_place_id);
            }
            $title = $cached_titles[$gd_place_id];

            // Create the unit links
            foreach ($item['singleLocationsUnitData'] as $unitData) {
                $unit_type_id = array_search(get_unit_type_name($unitData, $supplier_name), $unit_types);
                if (isset($unitData['m3'])) {
                    $unit_title = $title . ' link: ' . $unitData['m2'] . ' m2 / ' . $unitData['m3'] . ' m3';
                } else {
                    $unit_title = $title . ' link: ' . $unitData['m2'] . ' m2';
                }
                $unit_link_id = wp_insert_post(array(
                    'post_title' => $unit_title,
                    'post_type' => 'unit_link',
                    'post_status' => 'publish',
                    'post_author' => $user_id
                ));

                // Set the price and availability
                update_post_meta($unit_link_id, 'price', $unitData['price']);
                if ($unitData['available'] == 0) {
                    update_post_meta($unit_link_id, 'available', '0');
                } else {
                    update_post_meta($unit_link_id, 'available', '1');
                    if ($unitData['available']) {
                        update_post_meta($unit_link_id, 'available_date', $unitData['available']);
                    }
                }

                //set the bookUrl if the array key exists
                if (isset($unitData['bookUrl'])) {
                    update_post_meta($unit_link_id, 'booking_link', $unitData['bookUrl']);
                }

                //set the supplier_unit_id
                if (isset($unitData['supplier_unit_id'])) {
                    update_post_meta($unit_link_id, 'supplier_unit_id', $unitData['supplier_unit_id']);
                }

                if (isset($unitData['introPrice'])) {
                    update_post_meta($unit_link_id, 'intro_price', $unitData['introPrice']);
                }

                if (isset($unitData['introPeriod'])) {
                    update_post_meta($unit_link_id, 'intro_period', $unitData['introPeriod']);
                }


                // Add the unit type and gd_place to the unit link
                update_post_meta($unit_link_id, 'rel_type', $unit_type_id);
                update_post_meta($unit_link_id, 'rel_lokation', $gd_place_id);

                $unit_links_created++;
            }

            // Log how many unit links were created for the gd_place
            trigger_error('Created ' . count($item['singleLocationsUnitData']) .  $supplier_name . ' unit links for gd_place: ' . $title, E_USER_NOTICE);
        }
        // Free memory after processing each batch
        unset($batch);
    }

    // Free memory by unsetting the cached titles
    unset($cached_titles);

    trigger_error('num of gd_places not found: ' . $gd_places_not_found, E_USER_NOTICE);
    trigger_error('Created ' . $unit_links_created . ' ' .  $supplier_name . ' unit links', E_USER_NOTICE);
}


function create_unit_types($unique_units, $user_id, $existing_unit_types, $supplier_name)
{
    $new_unit_types = array(); // Array to store the new unit types

    foreach ($unique_units as $unit) {
        // Check if the unit type already exists
        $unitName = get_unit_type_name($unit, $supplier_name);
        $existing_unit_type_id = array_search($unitName, $existing_unit_types);
        if ($existing_unit_type_id) {
            continue; // Skip creating the unit type if it already exists
        }

        // Get the unit type name
        $unit_type_name = get_unit_type_name($unit, $supplier_name);
        $unit_type_id = wp_insert_post(array(
            'post_title' => $unit_type_name,
            'post_type' => 'unit_type',
            'post_status' => 'publish',
            'post_author' => $user_id
        ));

        // Set the m2 and m3 sizes
        update_post_meta($unit_type_id, 'm2', $unit['m2']);
        if (isset($unit['m3'])) {
            update_post_meta($unit_type_id, 'm3', $unit['m3']);
        }

        // Set the unit_type
        update_post_meta($unit_type_id, 'unit_type', 'indoor');

        $new_unit_types[$unit_type_id] = $unit_type_name; // Set the name as the value of the returned array

        // trigger_error('Created unit type: ' . $unit_type_name, E_USER_NOTICE);
    }

    return $new_unit_types; // Return the array of new unit types with names as values
}

function get_unit_type_name($unit, $supplier_name)
{
    if ($supplier_name == "nettolager") {
        return $supplier_name . ' type: ' . $unit['m2'] . ' m2 / ' . $unit['m3'] . ' m3';
    } else if ($supplier_name == "boxdepotet") {
        return  $supplier_name . ' type: ' .  $unit['m2'] . ' m2 / ' . $unit['m3'] . ' m3';
    } else if ($supplier_name == "pelican") {
        return  $supplier_name . ' type: ' . $unit['m2'] . ' m2';
    } else if ($supplier_name == "cityselfstorage") {
        return  $supplier_name . ' type: ' . $unit['m2'] . ' m2 / ' . $unit['m3'] . ' m3';
    }
}

function get_unique_units($data)
{
    $uniqueUnits = array();
    $seen = array();

    foreach ($data as $item) {
        foreach ($item['singleLocationsUnitData'] as $unitData) {

            if (isset($unitData['m3'])) {
                $key = $unitData['m2'] . '-' . $unitData['m3'];
            } else {
                $key = $unitData['m2'];
            }
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueUnits[] = $unitData;
            }
        }
    }

    return $uniqueUnits;
}

function sanitize_nettolager_data($data)
{
    return array_map(function ($item) {
        $sanitizedData = array_map(function ($unitData) {
            // Remove all non-numeric characters except comma
            $price = preg_replace("/[^0-9,]/", "", $unitData['price']);
            // Replace comma with dot
            // $price = str_replace(",", ".", $price);
            // Convert to float
            // $price = floatval($price);

            return array(
                'm2' => str_replace(" m2", "", $unitData['m2']),
                'm3' => str_replace(" m3", "", $unitData['m3']),
                'available' => intval(preg_replace("/[^0-9]/", "", $unitData['available'])) == 0 ? 0 : 1,
                'price' => $price
            );
        }, $item['unitData']);

        return array(
            'url' => $item['url'],
            'singleLocationsUnitData' => $sanitizedData
        );
    }, $data);
}

function sanitize_boxdepotet_data($data)
{
    return array_map(function ($item) {
        $sanitizedData = array_map(function ($unitData) {
            return array(
                'm2' => str_replace(" m2", "", $unitData['squareMeters']),
                'm3' => str_replace(" m3", "", $unitData['cubicMeters']),
                'available' => intval(preg_replace("/[^0-9]/", "", $unitData['availability'])),
                'price' => floatval(preg_replace("/[^0-9\.]/", "", $unitData['price'])),
                'bookUrl' => $unitData['bookUrl'],
                'supplier_unit_id' => $unitData['roomNumber']
            );
        }, $item['roomData']);

        return array(
            'url' => $item['url'],
            'singleLocationsUnitData' => $sanitizedData
        );
    }, $data);
}

function sanitize_pelican_data($data)
{
    return array_map(function ($location) {
        $sanitizedData = array_map(function ($unit) {
            return array(
                'm2' => str_replace(" m2", "", $unit['m2size']),
                'price' => floatval(preg_replace("/[^0-9\.]/", "", $unit['price'])),
                'available' => $unit['available'],
                'bookUrl' => $unit['link'],
                'introPrice' => str_replace("-", "", $unit['introPrice']),
                'introPeriod' => $unit['introPeriod']
            );
        }, $location['unitList']);

        return array(
            'url' => $location['locationDetails']['name'],
            'singleLocationsUnitData' => $sanitizedData
        );
    }, $data);
}

function sanitize_cityselfstorage_data($data)
{
    return array_map(function ($location) {
        $sanitizedData = array_map(function ($unit) {
            return array(
                'm2' => floatval(str_replace(" m2", "", $unit['size'])),
                'm3' => floatval(str_replace(" m³", "", $unit['volume'])),
                'price' => floatval(preg_replace("/[^0-9\.]/", "", $unit['price'])),
                'available' => "1",
                'bookUrl' => $unit['link'],
                'introPrice' => str_replace("-", "", $unit['introPrice']),
                'introPeriod' => $unit['introPeriod']
            );
        }, $location['unitList']);

        return array(
            'url' => $location['locationUrl'],
            'singleLocationsUnitData' => $sanitizedData
        );
    }, $data);
}

function get_all_locations_ids_and_partner_department_urls($supplier_name)
{
    $args = array(
        'post_type' => 'gd_place',
        'author_name' => $supplier_name,
        'posts_per_page' => -1,
        'fields' => 'ids'
    );

    $ids = get_posts($args);

    $posts = array();
    foreach ($ids as $id) {
        if ($supplier_name === 'pelican') {
            $posts[$id] = get_the_title($id);
        } else {
            $partner_department_url = get_post_meta($id, 'partner_department_url', true);
            $posts[$id] = $partner_department_url;
        }
    }

    trigger_error('locations: ' . print_r($posts, true), E_USER_NOTICE);
    return $posts;
}



function get_unit_types($supplier_name)
{
    $args = array(
        'post_type' => 'unit_type',
        'author_name' => $supplier_name,
        'posts_per_page' => -1,
        'fields' => 'ids'
    );

    $unit_types_ids = get_posts($args);

    //for each unit type, set the key as the unit type name
    $unit_types = array();
    foreach ($unit_types_ids as $id) {
        $unit_types[$id] = get_the_title($id);
    }
    return $unit_types;
}

function get_unit_links($supplier_name)
{
    $args = array(
        'post_type' => 'unit_link',
        'author_name' => $supplier_name,
        'posts_per_page' => -1,
        'fields' => 'ids'
    );

    $unit_links_ids = get_posts($args);

    return $unit_links_ids;
}

function remove_unit_types($supplier_name)
{
    $unit_types_ids = get_unit_types($supplier_name);
    foreach ($unit_types_ids as $unit_type_id => $unit_type_name) {
        wp_delete_post($unit_type_id, true);
    }
    trigger_error($supplier_name . ' unit types removed, deleted ' . count($unit_types_ids) . ' unit types', E_USER_NOTICE);
}

function remove_unit_links($supplier_name)
{
    $unit_links_ids = get_unit_links($supplier_name);
    foreach ($unit_links_ids as $unit_link_id) {
        wp_delete_post($unit_link_id, true);
    }
    trigger_error($supplier_name . ' unit links removed, deleted ' . count($unit_links_ids) . ' unit links', E_USER_NOTICE);
}
