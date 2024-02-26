<?php

require_once(dirname(__FILE__) . '/general-consolidations.php');
require_once(dirname(__FILE__) . '/geodir-consolidations.php');
require_once(dirname(__FILE__) . '/seo-consolidations.php');


function consolidate_geolocations()
{
    geodir_consolidations();
    general_consolidations();
    seo_consolidations();
    trigger_error("ALL consolidations done", E_USER_NOTICE);
}

function email_admin($body, $subject)
{
    $to = get_option('admin_email');
    $subject = $subject;
    $headers = array(
        'From: system@tjekdepot.dk <system@tjekdepot.dk>',
        'Content-Type: text/html; charset=UTF-8',
    );

    wp_mail($to, $subject, $body, $headers);
}

function add_geodir_consolidations_button($links)
{
    $geodir_link = '<a href="' . admin_url('admin-post.php?action=geodir_consolidations') . '">Run geodir geolocation consolidations</a>';
    array_unshift($links, $geodir_link);
    return $links;
}
add_filter('plugin_action_links_tdp-consolidations/tdp-consolidations-plugin.php', 'add_geodir_consolidations_button');

function handle_geodir_consolidations()
{
    set_time_limit(5000);
    geodir_consolidations();
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('admin_post_geodir_consolidations', 'handle_geodir_consolidations');

function add_general_consolidations_button($links)
{
    $general_link = '<a href="' . admin_url('admin-post.php?action=general_consolidations') . '">Run general geolocation consolidations</a>';
    array_unshift($links, $general_link);
    return $links;
}
add_filter('plugin_action_links_tdp-consolidations/tdp-consolidations-plugin.php', 'add_general_consolidations_button');

function handle_general_consolidations()
{
    set_time_limit(5000);
    general_consolidations();
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('admin_post_general_consolidations', 'handle_general_consolidations');

//add a button to the plugin settings page to run seo consolidations
function add_seo_consolidations_button($links)
{
    $seo_link = '<a href="' . admin_url('admin-post.php?action=seo_consolidations') . '">Run SEO geolocation consolidations</a>';
    array_unshift($links, $seo_link);
    return $links;
}
add_filter('plugin_action_links_tdp-consolidations/tdp-consolidations-plugin.php', 'add_seo_consolidations_button');

function handle_seo_consolidations()
{
    set_time_limit(5000);
    seo_consolidations();
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('admin_post_seo_consolidations', 'handle_seo_consolidations');


//add a button to the plugin settings page to consolidate geolocations
function add_consolidate_button($links)
{
    $consolidate_link = '<a href="' . esc_url(admin_url('admin-post.php?action=consolidate_geolocations')) . '">Run ALL geolocation consolidations</a>';
    array_unshift($links, $consolidate_link);
    return $links;
}
add_filter('plugin_action_links_tdp-consolidations/tdp-consolidations-plugin.php', 'add_consolidate_button');

function handle_consolidate_geolocations()
{
    set_time_limit(5000);
    consolidate_geolocations();
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('admin_post_consolidate_geolocations', 'handle_consolidate_geolocations');