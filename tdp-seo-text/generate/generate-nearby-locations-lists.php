<?php

function generate_nearby_locations_lists()
{
  $geolocations = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1));
  foreach ($geolocations as $geolocation) {
    $geolocation_id = $geolocation->ID;

    $neighbourhoods = get_post_meta($geolocation_id, 'geodir_neighbourhoods', true);

    if (empty($neighbourhoods[0])) {
      $neighbourhoods = array();
    }

    //if string, convert to array with the string as the first value
    if (is_string($neighbourhoods)) {
      $neighbourhoods = array($neighbourhoods);
    }

    $first_10_geolocations_within_20_km_with_seo_gd_place_list_sorted_by_distance = get_post_meta($geolocation_id, 'first_10_geolocations_within_20_km_with_seo_gd_place_list_sorted_by_distance', true);

    if (empty($first_10_geolocations_within_20_km_with_seo_gd_place_list_sorted_by_distance[0])) {
      $first_10_geolocations_within_20_km_with_seo_gd_place_list_sorted_by_distance = array();
    }

    //if string, convert to array with the string as the first value
    if (is_string($first_10_geolocations_within_20_km_with_seo_gd_place_list_sorted_by_distance)) {
      $first_10_geolocations_within_20_km_with_seo_gd_place_list_sorted_by_distance = array($first_10_geolocations_within_20_km_with_seo_gd_place_list_sorted_by_distance);
    }

    $all_nearby_locations_ids = array_merge($neighbourhoods, $first_10_geolocations_within_20_km_with_seo_gd_place_list_sorted_by_distance);
    $all_nearby_locations_bulky = array();
    foreach ($all_nearby_locations_ids as $location_id) {
      $bulky_nearby_location = array($location_id, get_the_title($location_id), get_post_field('post_name', $location_id)); // replace 0, 1, 2 with the indexes of the values you want
      $all_nearby_locations_bulky[] = $bulky_nearby_location;
    }

    //remove duplicates
    $all_nearby_locations_bulky = array_map('unserialize', array_unique(array_map('serialize', $all_nearby_locations_bulky)));

    $parent_location_id = get_post_meta($geolocation_id, 'parent_location', true);
    if (!empty($parent_location_id)) {
      foreach ($all_nearby_locations_bulky as $key => $nearby_location) {
        if ($nearby_location[0] == $parent_location_id) {
          unset($all_nearby_locations_bulky[$key]);
        }
      }
    }

    $all_nearby_locations_bulky = array_slice($all_nearby_locations_bulky, 0, 10);

    if (empty($all_nearby_locations_bulky)) {
      update_post_meta($geolocation_id, 'nearby_locations_list', "");
      continue;
    }
    $output = '<nav><div class="scrollable-list"><div>';


    foreach ($all_nearby_locations_bulky as $nearby_geolocation) {
      $post_title = $nearby_geolocation[1];
      $slug = $nearby_geolocation[2];
      $gd_location_slug = get_post_meta($nearby_geolocation[0], 'gd_location_slug', true); //use when migrating from geodir
      if (empty($gd_location_slug)) {
        $gd_location_slug = $slug;
      }
      $link = "https://www.tjekdepot.dk/lokation/$gd_location_slug/";
      $output .= '<a href="' . $link . '">' . $post_title . '</a>';
    }

    $output .= '</div></div></nav>';

    $current_nearby_locations_list = get_post_meta($geolocation_id, 'nearby_locations_list', true);

    if ($current_nearby_locations_list == $output) {
      continue;
    } else {
      trigger_error("updated nearby location lists for " . $geolocation->post_title, E_USER_NOTICE);
      update_post_meta($geolocation_id, 'nearby_locations_list', $output);
    }
  }
  trigger_error("nearby location lists updated", E_USER_NOTICE);
}
