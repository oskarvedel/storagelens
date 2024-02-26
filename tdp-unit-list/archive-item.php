<?php


function generate_archive_item_html_for_all_gd_places()
{
    generate_default_unit_list_for_all_gd_places();

    global $tags;
    global $featured_header;

    $gd_places = get_posts(array(
        'post_type' => 'gd_place',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ));
    foreach ($gd_places as $gd_place) {

        $gd_place_id = $gd_place->ID;


        if ($gd_place_id == 3020) {
            // xdebug_break();
        }

        $featured = get_post_meta($gd_place_id, 'featured', true);

        $post_info = geodir_get_post_info($gd_place_id);

        $post_tags = wp_get_post_terms($gd_place_id, 'gd_place_tags'); // Get the GeoDirectory tags of the post

        // construct an array with the tags if they are "1" in the post_info object
        $tag_array = array();
        foreach ($post_tags as $post_tag) {
            if (isset($post_tag->slug)) {
                //check if the slug exists in the tags array
                $tag = $post_tag->slug;
                $priority = $tags[$post_tag->slug];
                //capitalize the first letter of the tag
                $tag_name = ucfirst($post_tag->name);
                $tag_array[$tag_name] = $priority;
            }
        }
        if (count($tag_array) > 3) {
            // xdebug_break();
        }

        // Sort the array by priority
        asort($tag_array);

        //construct the archive item html

        //construct the header
        if ($featured) {
            $archive_item_html = '<a href="' . get_permalink($gd_place_id) . '"><div class="archive-item featured">' . $featured_header . '<div class="main-info-row"><div class="image-column">';
        } else {
            $archive_item_html = '<a href="' . get_permalink($gd_place_id) . '"><div class="archive-item"><div class="main-info-row"><div class="image-column">';
        }

        $archive_item_html = str_replace("[partner_name]", $post_info->post_title, $archive_item_html);

        //construct the image column
        $image_url = get_the_post_thumbnail_url($gd_place_id, array(175, 125));

        if ($image_url) {
            $archive_item_html .=
                '<img src="' . $image_url . '" alt="">';
        }
        $archive_item_html .= '</div>'; // close image-column

        //construct the title column
        $archive_item_html .= '<div class="title-column">';

        $archive_item_html .= '<h2 class="truncate-text">' . $post_info->post_title . '</h2>';

        $address = $post_info->street . ", " . $post_info->zip . " " . $post_info->city;

        if ($address) {
            $archive_item_html .= '<p class="address truncate-text">' . $address . '</p>';
        }

        $archive_item_html .= '</div>'; // close title-column


        //construct the tag column
        $archive_item_html .= generate_tag_html($tag_array);

        $archive_item_html .= '</div></a>'; // close main-info-row

        //add the unit list
        $default_archive_page_unit_list = get_post_meta($gd_place_id, 'default_archive_page_unit_list', true);

        if ($default_archive_page_unit_list) {
            $archive_item_html .= '<br/>' . $default_archive_page_unit_list;
        }


        update_post_meta($gd_place_id, 'archive_item_html', $archive_item_html);
    }

    trigger_error("Archive item html updated for all gd_places", E_USER_NOTICE);
}

function generate_tag_html($tag_array)
{
    // xdebug_break();

    //cut the tag array to 3
    $tag_array = array_slice($tag_array, 0, 3);

    $tag_html = '<div class="tag-column">';
    foreach ($tag_array as $tag => $priority) {
        $tag_html .= '<div class="tag">' . $tag . '</div>';
    }
    $tag_html .= '</div>';
    return $tag_html;
}

$tags = array(
    'adgang-doegnet-rundt' => 7,
    'alarm' => 20,
    'alarm-i-hvert-depotrum'  => 2,
    'boxit' => 26,
    'brandsikring' => 19,
    'dag-til-dag-opsigelse' => 4,
    'direkte-adgang-med-bil' => 6,
    'flytteservice' => 24,
    'forsikring-inklusiv' => 23,
    'godt-indeklima' => 16,
    'gratis-trailer'  => 3,
    'gulvvarme' => 1,
    'indendoers-aflaesning' => 5,
    'ingen-binding' => 18,
    'intet-depositum' => 12,
    'medlem-af-dssa' => 25,
    'online-booking' => 10,
    'opvarmet' => 13,
    'palleloefter' => 14,
    'personlig-betjening' => 21,
    'rent-toert-og-lyst' => 11,
    'saekkevogn' => 22,
    'studierabat' => 8,
    'stueplan' => 15,
    'tilbyder-forsikring' => 12,
    'videoovervaagning' => 9,
);


$featured_header = '<div class="featured-header">
<svg width="18" height="18" viewBox="0 0 22 18" xmlns="http://www.w3.org/2000/svg" class="svg thumbs-up">
   <g fill-rule="evenodd">
      <path d="M19.939 9.339L16.304 16H9V7.887l2.98-5.316c.26-.465.817-.681 1.326-.516v6.538h6.184a.507.507 0 01.449.746zM2 16h5V9H2v7zm19.645-8.148a2.487 2.487 0 00-2.145-1.19h-4.38V.969l-.366-.29c-1.341-1.058-3.308-.856-4.393.453a3.054 3.054 0 00-.301.435L7.156 6.663H0V18h17.401l4.294-7.74a2.365 2.365 0 00-.05-2.408z"></path>
      <path d="M5 15a1 1 0 100-2 1 1 0 000 2"></path>
   </g>
</svg>
<span class="featured-description-title truncate-text">Pålidelig partner</span><span class="featured-description truncate-text">: Dine ting står sikker hos [partner_name].</span>
</div>
';
