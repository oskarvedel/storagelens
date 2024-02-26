<?php


require_once dirname(__FILE__) . '/statistics-calcs-per-geolocation.php';
require_once dirname(__FILE__) . '/statistics-calcs-per-gd-place.php';

//add a button to update statistics data for all gd_places the plugin settings page
function add_update_statistics_data_for_all_gd_places_button($links)
{
    $consolidate_link = '<a href="' . esc_url(admin_url('admin-post.php?action=update_statistics_data_for_all_gd_places')) . '">Run statistics for gd_places</a>';
    array_unshift($links, $consolidate_link);
    return $links;
}
add_filter('plugin_action_links_tdp-statistics/tdp-statistics-plugin.php', 'add_update_statistics_data_for_all_gd_places_button');

function handle_update_statistics_data_for_all_gd_places()
{
    update_statistics_data_for_all_gd_places();
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('admin_post_update_statistics_data_for_all_gd_places', 'handle_update_statistics_data_for_all_gd_places');

//add a button to update statistics data for all geolocations the plugin settings page
function add_update_statistics_data_for_all_geolocations_button($links)
{
    $consolidate_link = '<a href="' . esc_url(admin_url('admin-post.php?action=update_statistics_data_for_all_geolocations')) . '">Run statistics for geolocations</a>';
    array_unshift($links, $consolidate_link);
    return $links;
}
add_filter('plugin_action_links_tdp-statistics/tdp-statistics-plugin.php', 'add_update_statistics_data_for_all_geolocations_button');

function handle_update_statistics_data_for_all_geolocations()
{
    update_statistics_data_for_all_geolocations();
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('admin_post_update_statistics_data_for_all_geolocations', 'handle_update_statistics_data_for_all_geolocations');


function add_update_all_statistics_button($links)
{
    $consolidate_link = '<a href="' . esc_url(admin_url('admin-post.php?action=update_all_statistics')) . '">Run ALL statistics</a>';
    array_unshift($links, $consolidate_link);
    return $links;
}
add_filter('plugin_action_links_tdp-statistics/tdp-statistics-plugin.php', 'add_update_all_statistics_button');

function handle_update_all_statistics()
{
    update_statistics_data_for_all_gd_places();
    update_statistics_data_for_all_geolocations();
    trigger_error("updated ALL statistics", E_USER_NOTICE);
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('admin_post_update_all_statistics', 'handle_update_all_statistics');
