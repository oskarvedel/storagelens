<?php

function geodir_consolidations()
{
    global $wpdb;
    $geodir_post_locations = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}geodir_post_locations", OBJECT);
    $geolocations = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1));

    if (empty($geodir_post_locations)) {
        trigger_error("consolidate_geolocations:No geodir_post_locations found", E_USER_WARNING);
        return;
    }

    if (empty($geolocations)) {
        trigger_error("consolidate_geolocations: No geolocations found", E_USER_WARNING);
        return;
    }

    $geodir_post_locations_ids = array_map(function ($item) {
        return $item->location_id;
    }, $geodir_post_locations);

    $geolocations_gd_location_ids = array_map(function ($item) {
        return $item->gd_location_id;
    }, $geolocations);

    //create_missing_geolocations($geodir_post_locations_ids, $geodir_post_neighbourhoods_ids, $geolocations_gd_location_ids, $geodir_post_locations, $geodir_post_neighbourhoods);
    //titles_match_check($geodir_post_locations, $geodir_post_neighbourhoods, $geolocations);
    // set_geodir_neighbourhoods($geodir_post_locations, $geodir_post_neighbourhoods, $geolocations);
    // set_geodir_parent_locations($geodir_post_neighbourhoods, $geodir_post_locations, $geodir_post_neighbourhoods_ids, $geolocations);
    //update_gd_places_for_all_geolocations($geolocations, $geodir_post_locations, $geodir_post_neighbourhoods);
    trigger_error("geodir consolidations done", E_USER_NOTICE);
}

function update_gd_places_for_all_geolocations($geolocations, $geodir_post_locations, $geodir_post_neighbourhoods)
{
    global $wpdb;
    $geodir_gd_place_detail_table = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}geodir_gd_place_detail", OBJECT);

    $all_gd_places = get_posts(array('post_type' => 'gd_place', 'posts_per_page' => -1));


    $filtered_geodir_gd_place_detail_table = [];
    foreach ($all_gd_places as $gd_place) {
        foreach ($geodir_gd_place_detail_table as $gd_place_detail) {
            if ($gd_place_detail->post_id == $gd_place->ID) {
                $filtered_geodir_gd_place_detail_table[] = $gd_place_detail;
            }
        }
    }

    foreach ($geolocations as $geolocation) {
        // trigger_error("compiling list of gd_places for : " . $geolocation->post_title, E_USER_WARNING);
        //find gd_places with matching city or neighbourhood
        $gd_places_matching_city_or_neighbourhood = array();
        foreach ($filtered_geodir_gd_place_detail_table as $gd_place_detail) {
            //echo $gd_place_detail->gd_location_slug;
            if ($gd_place_detail->city == $geolocation->post_title) {
                //echo "city match" . ($gd_place_detail->neighbourhood);
                $gd_places_matching_city_or_neighbourhood[] = $gd_place_detail->post_id;
            } else if ($geolocation->gd_location_slug == $gd_place_detail->neighbourhood) {
                //echo "hood match" . ($gd_place_detail->neighbourhood);
                $gd_places_matching_city_or_neighbourhood[] = $gd_place_detail->post_id;
            }
        }

        // trigger_error("compiled list of gd_places for : " . $geolocation->post_title, E_USER_WARNING);
        if (empty($gd_places_matching_city_or_neighbourhood)) {
            //trigger_error("No gd_places found for geolocation: " . $geolocation->post_title, E_USER_NOTICE);
            $gd_places_matching_city_or_neighbourhood = array();
        }

        $current_gd_place_list = get_post_meta($geolocation->ID, 'gd_place_list', false);

        if (empty($current_gd_place_list)) {
            $current_gd_place_list = array();
        }

        if (empty($gd_places_matching_city_or_neighbourhood) || empty($current_gd_place_list)) {
            continue;
        }

        $emailoutput = "";
        $emailoutput = update_gd_place_list_for_single_geolocation($current_gd_place_list, $gd_places_matching_city_or_neighbourhood, $geolocation, $emailoutput);
        // trigger_error("updated list of gd_places for : " . $geolocation->post_title, E_USER_WARNING);
        if ($emailoutput != "") {
            email_admin($emailoutput, 'gd_place list(s) updated for geolocation(s)');
        }
    }
}

function update_gd_place_list_for_single_geolocation($current_gd_place_list, $new_gd_place_list, $geolocation, $emailoutput)
{
    $new_gd_place_list = $current_gd_place_list + $new_gd_place_list;
    $new_gd_place_list = array_unique($new_gd_place_list);
    update_post_meta($geolocation->ID, 'gd_place_list', $new_gd_place_list);
    $gd_place_names = array();
    $message = "updating gd_place list for geolocation: " . $geolocation->post_title . "\n";
    foreach ($new_gd_place_list as $gd_place_id) {
        $gd_place = get_post($gd_place_id);
        $gd_place_names[] = $gd_place->post_title;
        $message .= $gd_place->post_title . "\n";
    }

    update_post_meta($geolocation->ID, 'gd_place_names', $gd_place_names);
    return $emailoutput;
}

function titles_match_check($geodir_post_locations, $geolocations)
{
    $emailoutput = "";
    //check if geolocation post title matches geodir_post_location city
    foreach ($geolocations as $geolocation) {
        $titles = array_column($geolocations, 'post_title');
        //var_dump($geolocation->gd_location_id);
        $geodir_post_location = $geodir_post_locations[array_search($geolocation->gd_location_id, array_column($geodir_post_locations, 'location_id'))]->city;
        if ($geodir_post_location !== $geolocation->post_title) {
            $message = "Geolocation title: " . $geolocation->post_title . " does not match name of associated gd_location: " . $geodir_post_location . "\r\n";
            //trigger_error($message, E_USER_WARNING); FIX
            $emailoutput .= $message;
        }
    }

    if ($emailoutput != "") {
        email_admin($emailoutput, 'Mismatching geolocation title(s) found');
    }
}

function create_missing_geolocations($geodir_post_locations_ids, $geodir_post_neighbourhoods_ids, $geolocations_gd_location_ids, $geodir_post_locations, $geodir_post_neighbourhoods)
{
    $emailoutput = "";

    foreach ($geodir_post_locations_ids as $id) {
        if (!in_array($id, $geolocations_gd_location_ids)) {
            $missing_geodir_post_location_title = $geodir_post_locations[array_search($id, array_column($geodir_post_locations, 'location_id'))]->city;
            $missing_geodir_post_location_slug = $geodir_post_locations[array_search($id, array_column($geodir_post_locations, 'location_id'))]->city_slug;
            $missing_geodir_post_location_latitude = $geodir_post_locations[array_search($id, array_column($geodir_post_locations, 'location_id'))]->latitude;
            $missing_geodir_post_location_longitude = $geodir_post_locations[array_search($id, array_column($geodir_post_locations, 'location_id'))]->longitude;
            $message = "geodir_post_location id: " . $id . " and name: " .  $missing_geodir_post_location_title   . " not found in geolocations_gd_location_ids. Creating new geolocation.\r\n";
            trigger_error($message, E_USER_WARNING);
            $emailoutput .= $message;
            $new_post = wp_insert_post(array(
                'post_title' => $missing_geodir_post_location_title,
                'post_type' => 'geolocations',
                'post_status' => 'publish',
            ));
            update_post_meta($new_post, 'gd_location_id', $id);
            update_post_meta($new_post, 'gd_location_slug', $missing_geodir_post_location_slug);
            update_post_meta($new_post, 'latitude', $missing_geodir_post_location_latitude);
            update_post_meta($new_post, 'longitude', $missing_geodir_post_location_longitude);
            update_post_meta($new_post, 'gd_place_list', []);
        }
    }

    foreach ($geodir_post_neighbourhoods_ids as $id) {
        if (!in_array($id, $geolocations_gd_location_ids)) {
            $missing_geodir_post_hood_title = $geodir_post_neighbourhoods[array_search($id, array_column($geodir_post_neighbourhoods, 'hood_id'))]->hood_name;
            $missing_geodir_post_hood_slug = $geodir_post_neighbourhoods[array_search($id, array_column($geodir_post_neighbourhoods, 'hood_id'))]->hood_slug;
            $missing_geodir_post_hood_latitude = $geodir_post_neighbourhoods[array_search($id, array_column($geodir_post_neighbourhoods, 'hood_id'))]->hood_latitude;
            $missing_geodir_post_hood_longitude = $geodir_post_neighbourhoods[array_search($id, array_column($geodir_post_neighbourhoods, 'hood_id'))]->hood_longitude;
            $missing_geodir_post_hood_parent_location_id = $geodir_post_neighbourhoods[array_search($id, array_column($geodir_post_neighbourhoods, 'hood_id'))]->hood_location_id;
            $missing_geodir_post_hood_parent_location = $geodir_post_locations[array_search($missing_geodir_post_hood_parent_location_id, array_column($geodir_post_locations, 'location_id'))]->city;
            $message = "geodir_hood_location id: " . $id . " and name: " .  $missing_geodir_post_hood_title   . " not found in geolocations_gd_location_ids. Creating new geolocation.\r\n";
            trigger_error($message, E_USER_WARNING);
            $emailoutput .= $message;
            $new_post = wp_insert_post(array(
                'post_title' => $missing_geodir_post_hood_title,
                'post_type' => 'geolocations',
                'post_status' => 'publish',
            ));
            update_post_meta($new_post, 'gd_location_id', $id);
            update_post_meta($new_post, 'gd_location_slug', $missing_geodir_post_hood_slug);
            update_post_meta($new_post, 'latitude', $missing_geodir_post_hood_latitude);
            update_post_meta($new_post, 'longitude', $missing_geodir_post_hood_longitude);
            update_post_meta($new_post, 'parent_location', $missing_geodir_post_hood_parent_location);
            update_post_meta($new_post, 'gd_place_list', []);
        }
    }

    if ($emailoutput != "") {
        email_admin($emailoutput, 'Geolocation(s) created');
    }
}

function set_geodir_neighbourhoods()
{
    $geolocations = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1));
    foreach ($geolocations as $geolocation) {
        $geodir_neighbourhoods = find_neighbourhoods_for_geolocation($geolocation);
        array_unique($geodir_neighbourhoods);
        $current_geodir_neighbourhoods = get_post_meta($geolocation->ID, 'geodir_neighbourhoods', false);

        if ($geodir_neighbourhoods != $current_geodir_neighbourhoods) {
            $message = "updating geodir_neighbourhoods for geolocation: " . $geolocation->post_title . "\n";
            trigger_error($message, E_USER_WARNING);
        }
        $geodir_neighbourhoods = $geodir_neighbourhoods + $current_geodir_neighbourhoods;
        $geodir_neighbourhoods = array_unique($geodir_neighbourhoods);
        update_post_meta($geolocation->ID, 'geodir_neighbourhoods', $geodir_neighbourhoods);
    }
}

function find_neighbourhoods_for_geolocation($geolocation)
{
    global $wpdb;
    $geodir_post_neighbourhoods = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}geodir_post_neighbourhood", OBJECT);
    $gd_location_id = get_post_meta($geolocation->ID, 'gd_location_id', true);
    $geodir_neighbourhoods = [];
    foreach ($geodir_post_neighbourhoods as $neighbourhood) {
        if ($neighbourhood->hood_location_id == $gd_location_id) {
            $geodir_neighbourhoods[] = find_geolocation_that_matches_with_neighbourhood($neighbourhood->hood_id);
        }
    }
    return $geodir_neighbourhoods;
}

function find_geolocation_that_matches_with_neighbourhood($hood_id)
{
    $geolocations = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1));
    foreach ($geolocations as $geolocation) {
        $gd_location_id = get_post_meta($geolocation->ID, 'gd_location_id', true);
        if ($gd_location_id == $hood_id) {
            return $geolocation->ID;
        }
    }
}

function set_geodir_parent_locations($geodir_post_neighbourhoods, $geodir_post_locations, $geodir_post_neighbourhoods_ids, $geolocations)
{
    $emailoutput = "";
    $counter1 = 0;
    $counter2 = 0;
    foreach ($geolocations as $geolocation) {
        $geolocation_name = $geolocation->post_title;
        $geolocation_id = $geolocation->ID;
        $gd_location_id = get_post_meta($geolocation->ID, 'gd_location_id', true);
        if (in_array($gd_location_id, $geodir_post_neighbourhoods_ids)) {
            $counter1++;
            $current_parent_geolocation_id = get_post_meta($geolocation->ID, 'parent_location', true);
            $current_parent_location_name = get_the_title($current_parent_geolocation_id);

            $correct_parent_gd_location_id = $geodir_post_neighbourhoods[array_search($gd_location_id, array_column($geodir_post_neighbourhoods, 'hood_id'))]->hood_location_id;
            $correct_parent_geolocation_id = "";
            foreach ($geolocations as $geolocation) {
                if ($geolocation->gd_location_id == $correct_parent_gd_location_id) {
                    $correct_parent_geolocation_id = $geolocation->ID;
                    continue;
                }
            }

            if (!$correct_parent_geolocation_id) {
                return;
            }

            if (intval($current_parent_geolocation_id) != $correct_parent_geolocation_id) {
                $counter2++;
                $hood_title = $geolocation_name;
                update_post_meta($geolocation_id, 'parent_location', $correct_parent_geolocation_id);
            }
            unset($current_parent_geolocation_id, $current_parent_location_name, $correct_parent_gd_location_id, $correct_parent_geolocation_id);
        }
    }
}
