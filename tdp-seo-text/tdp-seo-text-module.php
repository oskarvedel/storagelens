<?php

require_once dirname(__FILE__) . '/generate/generate-seo-texts.php';
require_once dirname(__FILE__) . '/generate/generate-top-seo-texts.php';
require_once dirname(__FILE__) . '/generate/generate-missing-static-map-images.php';
require_once dirname(__FILE__) . '/generate/generate-nearby-locations-lists.php';
require_once dirname(__FILE__) . '/generate/generate-meta-titles.php';
require_once dirname(__FILE__) . '/generate/generate-meta-descriptions.php';
require_once dirname(__FILE__) . '/generate/generate-chatgpt-descriptions.php';
require_once dirname(__FILE__) . '/generate/generate-chatgpt-short-descriptions.php';


require_once dirname(__FILE__) . '/gd_location-metadata-shortcode.php';

require_once dirname(__FILE__) . '/texts/description-title.php';
require_once dirname(__FILE__) . '/texts/basic-text.php';
require_once dirname(__FILE__) . '/texts/first-paragraph.php';
require_once dirname(__FILE__) . '/texts/second-paragraph.php';
require_once dirname(__FILE__) . '/texts/meta-title.php';
require_once dirname(__FILE__) . '/texts/meta-description.php';
require_once dirname(__FILE__) . '/texts/top-seo-text.php';

function set_lokation_archive_meta_titles()
{
    if (geodir_is_page('post_type')) {
        $geolocation_id = extract_geolocation_id_via_url();
        $meta_title = get_post_meta($geolocation_id, 'meta_title', true);
        if (!empty($meta_title)) {
            return $meta_title;
        } else {
            return 'Alle opbevaringsrum og depotrum i Danmark';
        }
    }
}
add_filter('pre_get_document_title', 'set_lokation_archive_meta_titles', 21);

function set_lokation_archive_meta_descriptions()
{
    if (geodir_is_page('post_type')) {
        $geolocation_id = extract_geolocation_id_via_url();
        $meta_description = get_post_meta($geolocation_id, 'meta_description', true);
        if (!empty($meta_description)) {
            echo '<meta name="description" content="' . esc_attr($meta_description) . '">';
        } else {
            echo '<meta name="description" content="Alle opbevaringsrum og depotrum i Danmark ✓ Søg og book opbevaring og depotrum ✓ Stort udvalg af opbevaringsrum ✓ Sammenlign priser ➡ Find den bedste løsning.">';
        }
    }
}
add_action('wp_head', 'set_lokation_archive_meta_descriptions');

function top_seo_text_func()
{
    $id = extract_geolocation_id_via_url();
    $top_seo_text = get_post_meta($id, 'top_seo_text', true);
    echo $top_seo_text;
}

add_shortcode('gd_location_top_seo_text_shortcode', 'top_seo_text_func');

function seo_text_func()
{
    $id = extract_geolocation_id_via_url();
    $seo_text = get_post_meta($id, 'seo_text', true);
    echo $seo_text;
}

add_shortcode('gd_location_seo_text_shortcode', 'seo_text_func');

function nearby_locations_list_func()
{
    $id = extract_geolocation_id_via_url();
    $nearby_locations_list = get_post_meta($id, 'nearby_locations_list', true);
    echo $nearby_locations_list;
}

add_shortcode('gd_location_nearby_locations_list_shortcode', 'nearby_locations_list_func');

function add_generate_maps_button($links)
{
    $consolidate_link = '<a href="' . esc_url(admin_url('admin-post.php?action=generate_maps')) . '">Generate missing static maps</a>';
    array_unshift($links, $consolidate_link);
    return $links;
}
add_filter('plugin_action_links_tdp-seo-text/tdp-seo-text-plugin.php', 'add_generate_maps_button');


function add_generate_seo_texts_button($links)
{
    $consolidate_link = '<a href="' . esc_url(admin_url('admin-post.php?action=generate_seo_texts')) . '">Generate SEO texts</a>';
    array_unshift($links, $consolidate_link);
    return $links;
}
add_filter('plugin_action_links_tdp-seo-text/tdp-seo-text-plugin.php', 'add_generate_seo_texts_button');

function handle_generate_seo_texts()
{
    generate_seo_texts();
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('admin_post_generate_seo_texts', 'handle_generate_seo_texts');

function add_generate_top_seo_texts_button($links)
{
    $consolidate_link = '<a href="' . esc_url(admin_url('admin-post.php?action=generate_top_seo_texts')) . '">Generate top SEO texts</a>';
    array_unshift($links, $consolidate_link);
    return $links;
}
add_filter('plugin_action_links_tdp-seo-text/tdp-seo-text-plugin.php', 'add_generate_top_seo_texts_button');

function handle_generate_top_seo_texts()
{
    generate_top_seo_texts();
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('admin_post_generate_top_seo_texts', 'handle_generate_top_seo_texts');

add_action('admin_post_generate_meta_titles', 'handle_generate_meta_titles');

function add_generate_nearby_locations_lists_button($links)
{
    $consolidate_link = '<a href="' . esc_url(admin_url('admin-post.php?action=generate_nearby_locations_lists')) . '">Generate nearby locations lists</a>';
    array_unshift($links, $consolidate_link);
    return $links;
}

add_filter('plugin_action_links_tdp-seo-text/tdp-seo-text-plugin.php', 'add_generate_nearby_locations_lists_button');

function handle_generate_nearby_locations_lists()
{
    generate_nearby_locations_lists();
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('admin_post_generate_nearby_locations_lists', 'handle_generate_nearby_locations_lists');

function add_generate_meta_titles_button($links)
{
    $consolidate_link = '<a href="' . esc_url(admin_url('admin-post.php?action=generate_meta_titles')) . '">Generate meta titles</a>';
    array_unshift($links, $consolidate_link);
    return $links;
}

add_filter('plugin_action_links_tdp-seo-text/tdp-seo-text-plugin.php', 'add_generate_meta_titles_button');

function handle_generate_meta_titles()
{
    generate_meta_titles();
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}

function add_generate_meta_descriptions_button($links)
{
    $consolidate_link = '<a href="' . esc_url(admin_url('admin-post.php?action=generate_meta_descriptions')) . '">Generate meta descriptions</a>';
    array_unshift($links, $consolidate_link);
    return $links;
}

add_filter('plugin_action_links_tdp-seo-text/tdp-seo-text-plugin.php', 'add_generate_meta_descriptions_button');

function handle_generate_meta_descriptions()
{
    generate_meta_descriptions();
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('admin_post_generate_meta_descriptions', 'handle_generate_meta_descriptions');

function add_generate_chatgpt_descriptions_button($links)
{
    $consolidate_link = '<a href="' . esc_url(admin_url('admin-post.php?action=generate_chatgpt_descriptions')) . '">Generate 50 missing ChatGPT descriptions</a>';
    array_unshift($links, $consolidate_link);
    return $links;
}

add_filter('plugin_action_links_tdp-seo-text/tdp-seo-text-plugin.php', 'add_generate_chatgpt_descriptions_button');

function handle_generate_chatgpt_descriptions()
{
    set_time_limit(5000);
    generate_missing_chatgpt_geolocation_descriptions(50);
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}

add_action('admin_post_generate_chatgpt_descriptions', 'handle_generate_chatgpt_descriptions');

function add_generate_chatgpt_short_descriptions_button($links)
{
    $consolidate_link = '<a href="' . esc_url(admin_url('admin-post.php?action=generate_chatgpt_short_descriptions')) . '">Generate 50 missing ChatGPT short descriptions</a>';
    array_unshift($links, $consolidate_link);
    return $links;
}

add_filter('plugin_action_links_tdp-seo-text/tdp-seo-text-plugin.php', 'add_generate_chatgpt_short_descriptions_button');

function handle_generate_chatgpt_short_descriptions()
{
    set_time_limit(5000);
    generate_missing_chatgpt_geolocation_short_descriptions(50);
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}

add_action('admin_post_generate_chatgpt_short_descriptions', 'handle_generate_chatgpt_short_descriptions');
