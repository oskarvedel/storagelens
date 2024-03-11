<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function schedule_tdp_daily_functions()
{
    if (!wp_next_scheduled('run_tdp_daily_functions')) {
        $time = strtotime('01:00:00'); // Set the time you want the function to run
        if ($time < time()) {
            $time += 24 * 60 * 60; // Add 24 hours if the time has already passed today
        }
        wp_schedule_event($time, 'daily', 'run_tdp_daily_functions');
    }
}
add_action('wp', 'schedule_tdp_daily_functions');

function tdp_daily_functions()
{
    set_time_limit(5000);
    trigger_error('RUNNING TDP DAILY FUNCTIONS', E_USER_WARNING);
    update_statistics_data_for_all_gd_places();
    sleep(60);
    update_statistics_data_for_all_geolocations();
    sleep(60);
    consolidate_geolocations();
    sleep(60);
    import_scraper_data("boxdepotet");
    sleep(60);
    // import_scraper_data("nettolager");
    sleep(60);
    // import_scraper_data("pelican");
    // sleep(60);
    generate_seo_texts();
    sleep(30);
    generate_top_seo_texts();
    sleep(30);
    generate_missing_static_map_images();
    sleep(30);
    generate_nearby_locations_lists();
    sleep(30);
    generate_meta_titles();
    sleep(30);
    generate_meta_descriptions();
    sleep(30);
    generate_archive_item_html_for_all_gd_places();
    sleep(30);
    // generate_missing_chatgpt_geolocation_descriptions(50);
    sleep(30);
    // generate_missing_chatgpt_geolocation_short_descriptions(50);
    trigger_error('FINISHED RUNNING TDP DAILY FUNCTIONS', E_USER_WARNING);
}
add_action('run_tdp_daily_functions', 'tdp_daily_functions');

//add a admin plugin button to run the daily functions
function add_run_tdp_daily_functions_button($links)
{
    $consolidate_link = '<a href="' . esc_url(admin_url('admin-post.php?action=run_tdp_daily_functions')) . '">Run TDP Daily Functions</a>';
    array_unshift($links, $consolidate_link);
    return $links;
}
add_filter('plugin_action_links_tdp-control/tdp-control-plugin.php', 'add_run_tdp_daily_functions_button');

function handle_run_tdp_daily_functions()
{
    run_tdp_daily_functions();
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('admin_post_run_tdp_daily_functions', 'handle_run_tdp_daily_functions');


// Add a new cron schedule for every 6 hours
function add_every_six_hours_schedule($schedules)
{
    $schedules['every_six_hours'] = array(
        'interval' => 6 * 60 * 60, // 6 hours in seconds
        'display'  => __('Every 6 hours'),
    );
    return $schedules;
}
add_filter('cron_schedules', 'add_every_six_hours_schedule');

// Schedule an event that runs 4 times per day
function schedule_tdp_4_times_per_day_functions()
{
    if (!wp_next_scheduled('run_tdp_4_times_per_day_functions')) {
        $time = strtotime('01:00:00'); // Set the time you want the function to run
        if ($time < time()) {
            $time += 6 * 60 * 60; // Add 6 hours if the time has already passed
        }
        wp_schedule_event($time, 'every_six_hours', 'run_tdp_4_times_per_day_functions');
    }
}
add_action('wp', 'schedule_tdp_4_times_per_day_functions');


function tdp_4_times_per_day_functions()
{
    set_time_limit(5000);
    trigger_error('RUNNING TDP 4 TIMES PER DAY FUNCTIONS', E_USER_WARNING);
    import_scraper_data("boxdepotet");
    sleep(60);
    generate_archive_item_html_for_all_gd_places();
    sleep(30);
    consolidate_geolocations();
    sleep(30);
    generate_nearby_locations_lists();
    trigger_error('FINISHED RUNNING TDP 4 TIMES PER DAY FUNCTIONS', E_USER_WARNING);
}

add_action('run_tdp_4_times_per_day_functions', 'tdp_4_times_per_day_functions');

//add a admin plugin button to run the 4 times per day functions
function add_run_tdp_4_times_per_day_functions_button($links)
{
    $consolidate_link = '<a href="' . esc_url(admin_url('admin-post.php?action=run_tdp_4_times_per_day_functions')) . '">Run TDP 4 Times Per Day Functions</a>';
    array_unshift($links, $consolidate_link);
    return $links;
}
add_filter('plugin_action_links_tdp-control/tdp-control-plugin.php', 'add_run_tdp_4_times_per_day_functions_button');

function handle_run_tdp_4_times_per_day_functions()
{
    tdp_4_times_per_day_functions();
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('admin_post_run_tdp_4_times_per_day_functions', 'handle_run_tdp_4_times_per_day_functions');


// Add a new cron schedule for every 10 minutes
function add_every_ten_minutes_schedule($schedules)
{
    $schedules['every_ten_minutes'] = array(
        'interval' => 10 * 60, // 10 minutes in seconds
        'display'  => __('Every 10 minutes'),
    );
    return $schedules;
}
add_filter('cron_schedules', 'add_every_ten_minutes_schedule');

// Schedule an event that runs every 10 minutes
function schedule_tdp_every_ten_minutes_functions()
{
    if (!wp_next_scheduled('run_tdp_every_ten_minutes_functions')) {
        $time = time(); // Set the current time
        wp_schedule_event($time, 'every_ten_minutes', 'run_tdp_every_ten_minutes_functions');
    }
}
add_action('wp', 'schedule_tdp_every_ten_minutes_functions');


function tdp_every_ten_minutes_functions()
{
    // trigger_error('RUNNING TDP EVERY TEN MINUTES FUNCTIONS', E_USER_WARNING);
    send_missing_supplier_booking_emails();
    send_missing_admin_booking_emails();
}

add_action('run_tdp_every_ten_minutes_functions', 'tdp_every_ten_minutes_functions');
