<?php

function generate_meta_descriptions()
{

     $geolocations = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1));

     foreach ($geolocations as $geolocation) {
          $geolocation_id = $geolocation->ID;

          $archive_title_trimmed = get_the_title($geolocation_id);

          $seo_gd_place_list = get_post_meta($geolocation_id, 'seo_gd_place_list', false);

          $num_of_seo_gd_places = count($seo_gd_place_list);

          global $statistics_data_fields;
          global $meta_description_candidates;

          set_meta_description_field($geolocation_id, $num_of_seo_gd_places, $archive_title_trimmed, $statistics_data_fields, $meta_description_candidates);
     }
     trigger_error("Meta descriptions updated", E_USER_NOTICE);
}

function set_meta_description_field($geolocation_id, $num_of_seo_gd_places, $archive_title_trimmed, $statistics_data_fields, $meta_description_candidates)
{
     $current_meta_description = get_post_meta($geolocation_id, 'meta_description', true);
     $lowest_price = get_post_meta($geolocation_id, 'lowest price', true);
     $lowest_price_floatval = floatval($lowest_price);
     $highest_price = get_post_meta($geolocation_id, 'highest price', true);
     $highest_price_floatval = floatval($highest_price);
     $average_price = get_post_meta($geolocation_id, 'average price', true);
     $average_price_floatval = floatval($average_price);
     $seo_num_of_units_available = get_post_meta($geolocation_id, 'seo_num_of_units_available', true);
     $seo_num_of_units_available_intval = intval($seo_num_of_units_available);

     $new_meta_description  = $meta_description_candidates[0]; //set basic description

     if ($num_of_seo_gd_places >= 3) {
          $new_meta_description = $meta_description_candidates[1];
     }

     if ($num_of_seo_gd_places >= 3 && $seo_num_of_units_available_intval >= 20) {
          $new_meta_description = $meta_description_candidates[2];
     }

     $new_meta_description = replace_variable_placeholders($new_meta_description, $statistics_data_fields, $geolocation_id, $num_of_seo_gd_places, $seo_num_of_units_available_intval, $archive_title_trimmed);

     if ($current_meta_description != $new_meta_description) {
          update_post_meta($geolocation_id, 'meta_description', $new_meta_description);
          trigger_error("Meta description updated for " . $archive_title_trimmed . ". New meta description:  " . $new_meta_description, E_USER_NOTICE);
     }
}
