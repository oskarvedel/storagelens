<?php


require_once dirname(__FILE__) . '/import-scraper-data.php';

function add_remove_nettolager_data_button($links)
{
    $remove_link = '<a href="' . esc_url(admin_url('admin-post.php?action=remove_nettolager_data')) . '">Remove Nettolager Data</a>';
    array_unshift($links, $remove_link);
    return $links;
}
add_filter('plugin_action_links_tdp-scraper-import/tdp-scraper-import-plugin.php', 'add_remove_nettolager_data_button');

function handle_remove_nettolager_data()
{
    remove_scraper_data("nettolager");
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('admin_post_remove_nettolager_data', 'handle_remove_nettolager_data');

function add_import_nettolager_data_button($links)
{
    $import_link = '<a href="' . esc_url(admin_url('admin-post.php?action=import_nettolager_data')) . '">Import Nettolager Data</a>';
    array_unshift($links, $import_link);
    return $links;
}
add_filter('plugin_action_links_tdp-scraper-import/tdp-scraper-import-plugin.php', 'add_import_nettolager_data_button');

function handle_import_nettolager_data()
{
    import_scraper_data("nettolager");
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('admin_post_import_nettolager_data', 'handle_import_nettolager_data');

//add button to remove boxdepotet data
function add_remove_boxdepotet_data_button($links)
{
    $remove_link = '<a href="' . esc_url(admin_url('admin-post.php?action=remove_boxdepotet_data')) . '">Remove Boxdepotet Data</a>';
    array_unshift($links, $remove_link);
    return $links;
}
add_filter('plugin_action_links_tdp-scraper-import/tdp-scraper-import-plugin.php', 'add_remove_boxdepotet_data_button');

function handle_remove_boxdepotet_data()
{
    remove_scraper_data("boxdepotet");
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('admin_post_remove_boxdepotet_data', 'handle_remove_boxdepotet_data');


//add button to import boxdepotet data
function add_import_boxdepotet_data_button($links)
{
    $import_link = '<a href="' . esc_url(admin_url('admin-post.php?action=import_boxdepotet_data')) . '">Fetch and import Boxdepotet Data</a>';
    array_unshift($links, $import_link);
    return $links;
}
add_filter('plugin_action_links_tdp-scraper-import/tdp-scraper-import-plugin.php', 'add_import_boxdepotet_data_button');

function handle_import_boxdepotet_data()
{
    import_scraper_data("boxdepotet");
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('admin_post_import_boxdepotet_data', 'handle_import_boxdepotet_data');

function run_scraper($scraper_name)
{
    trigger_error('run_scraper job called', E_USER_NOTICE);
    import_scraper_data($scraper_name);
}
add_action('run_scraper_action', 'run_scraper');
