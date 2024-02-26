<?php

function generate_meta_titles()
{
     $geolocations = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1));

     foreach ($geolocations as $geolocation) {
          $geolocation_id = $geolocation->ID;

          $archive_title_trimmed = get_the_title($geolocation_id);

          $seo_gd_place_list = get_post_meta($geolocation_id, 'seo_gd_place_list', false);
          $num_of_seo_gd_places = count($seo_gd_place_list);

          $seo_num_of_units_available = get_post_meta($geolocation_id, 'seo_num_of_units_available', true);
          $seo_num_of_units_available_intval = intval($seo_num_of_units_available);

          $lowest_price = get_post_meta($geolocation_id, 'lowest price', true);
          $lowest_price_floatval = floatval($lowest_price);

          global $statistics_data_fields;
          global $meta_title_candidates;

          set_meta_title_field($geolocation_id, $num_of_seo_gd_places, $seo_num_of_units_available_intval, $lowest_price_floatval, $archive_title_trimmed, $statistics_data_fields, $meta_title_candidates);
     }
     trigger_error("Meta titles updated", E_USER_NOTICE);
}

function set_meta_title_field($geolocation_id, $num_of_seo_gd_places, $seo_num_of_units_available, $lowest_price, $archive_title_trimmed, $statistics_data_fields, $meta_title_candidates)
{
     $current_meta_title = get_post_meta($geolocation_id, 'meta_title', true);
     $new_meta_title  = $meta_title_candidates[0]; //set basic title

     //set the better meta titles if the data is available
     if ($seo_num_of_units_available >= 20 && $lowest_price >= 30) {
          $new_meta_title = $meta_title_candidates[1];
     } else if ($num_of_seo_gd_places >= 3) {
          $new_meta_title = $meta_title_candidates[2];
     }

     $new_meta_title = replace_variable_placeholders($new_meta_title, $statistics_data_fields, $geolocation_id, $num_of_seo_gd_places, $seo_num_of_units_available, $archive_title_trimmed);

     if ($current_meta_title != $new_meta_title) {
          update_post_meta($geolocation_id, 'meta_title', $new_meta_title);
          trigger_error("Meta title updated for " . $archive_title_trimmed . ". New meta title:  " . $new_meta_title, E_USER_NOTICE);
     }
}
