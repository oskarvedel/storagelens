<?php

function gd_location_metadata_shortcode_func($atts)
{
     // xdebug_break();
     $a = shortcode_atts(array(
          'field' => '',
     ), $atts);

     // if (!isset($atts[0])) {
     //      return;
     // }
     $geolocation_id = extract_geolocation_id_via_url_seo_text();

     $field = esc_attr($a['field']);

     global $post;
     // $post_id = (NULL === $post_id) ? $post->ID : $post_id;
     $meta_field = get_post_meta($geolocation_id, $field, true);
     return  $meta_field;
}

add_shortcode('gd_location_statistics_field_shortcode', 'gd_location_metadata_shortcode_func');
