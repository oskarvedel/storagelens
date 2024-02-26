<?php

function generate_top_seo_texts()
{
     $geolocations = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1));

     foreach ($geolocations as $geolocation) {
          $geolocation_id = $geolocation->ID;

          $archive_title_trimmed = get_the_title($geolocation_id);

          global $top_seo_text;
          $output = "<span class='top-seo-text'>";
          $output .= $top_seo_text;

          $output = str_replace("[location]", $archive_title_trimmed, $output);

          $output .= "</span>";

          $old_top_seo_text = get_post_meta($geolocation_id, 'top_seo_text', true);

          if ($old_top_seo_text != $output) {
               update_post_meta($geolocation_id, 'top_seo_text', $output);
               trigger_error("SEO text updated for " . $archive_title_trimmed, E_USER_NOTICE);
          }
     }
     trigger_error("Top SEO texts updated", E_USER_NOTICE);
}
