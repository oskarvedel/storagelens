<?php

date_default_timezone_set('Europe/Copenhagen');

// Remove dashicons in frontend for unauthenticated users
add_action('wp_enqueue_scripts', 'bs_dequeue_dashicons');
function bs_dequeue_dashicons()
{
    if (!is_user_logged_in()) {
        wp_deregister_style('dashicons');
    }
}

function modify_archive_query($query)
{
    // Check if we are on the front end and if the main query is being modified
    if (!is_admin() && $query->is_main_query()) {
        // Target a specific archive page, e.g., a custom post type archive
        if ($query->is_post_type_archive('gd_place')) {
            trigger_error('modify_archive_query: gd_place archive', E_USER_NOTICE);
            // Extract the geolocation ID from the URL
            $geolocation_id = extract_geolocation_id_via_url_configs();
            global $wp;
            $current_url = add_query_arg(array(), $wp->request);
            // $special_location = get_post_meta($current_geolocation_id, 'special_location', true);
            if ($current_url == "lokation") {
                $geolocation_id = 29783; //set geolocation id to Denmark
            }
            // Assume this function returns an array of post IDs
            $archive_gd_place_list = get_post_meta($geolocation_id, 'archive_gd_place_list', false);

            // Get and log the post titles for each ID in the list
            $post_titles = array_map(function ($post_id) {
                return get_the_title($post_id);
            }, $archive_gd_place_list);

            trigger_error('modify_archive_query: post titles: ' . print_r($post_titles, true), E_USER_NOTICE);

            // Set the post__in parameter for the main query
            if (!empty($archive_gd_place_list)) {
                $query->set('post__in', array_values($archive_gd_place_list));
                $query->set('orderby', 'post__in');
            } else {
                $query->set('post__in', array(0));
            }
        }
    }
}

// add_action('pre_get_posts', 'modify_archive_query', 1);

//create custom query for "lokation" page
add_action('elementor/query/lokation_page_query', function ($query) {

    // Check if we are on the front end and if the main query is being modified
    if (!is_admin()) {
        // xdebug_break();
        // Target a specific archive page, e.g., a custom post type archive

        // trigger_error('lokation_page_query_test: gd_place archive', E_USER_NOTICE);
        // Extract the geolocation ID from the URL
        $geolocation_id = extract_geolocation_id_via_url_configs();
        global $wp;
        $current_url = add_query_arg(array(), $wp->request);
        // $special_location = get_post_meta($current_geolocation_id, 'special_location', true);
        if ($current_url == "lokation") {
            $geolocation_id = 29783; //set geolocation id to Denmark
        }
        // Assume this function returns an array of post IDs
        $archive_gd_place_list = get_post_meta($geolocation_id, 'archive_gd_place_list', false);

        // // Get and log the post titles for each ID in the list
        // $post_titles = array_map(function ($post_id) {
        //     return get_the_title($post_id);
        // }, $archive_gd_place_list);

        // trigger_error('modify_archive_query: post titles: ' . print_r($post_titles, true), E_USER_NOTICE);

        // Set the post__in parameter for the main query
        if (!empty($archive_gd_place_list)) {
            $query->set('post_type', 'gd_place');
            $query->set('post__in', array_values($archive_gd_place_list));
            $query->set('orderby', 'post__in');
            $query->set('posts_per_page', 8);
        } else {
            $query->set('post__in', array(0));
        }
    }
});

//create custom query for "opmagasinering" page
add_action('elementor/query/depotrum_page_query', function ($query) {
    $geolocation_id = 17921; //set geolocation id to Denmark (tjekdepot.local)
    $geolocation_id = 29783; //set geolocation id to Denmark (tjekdepot.dk)

    $gd_place_list_combined = get_post_meta($geolocation_id, 'archive_gd_place_list', false);

    $query->set('post_type', 'gd_place');
    $query->set('post__in', $gd_place_list_combined);
    $query->set('orderby', 'post__in');
    $query->set('posts_per_page', 6);
});

function handle_javascript_error()
{
    $_POST['error_message'] = $_POST['error_message'];

    if (!isset($_POST['error_message'])) {
        // Handle the case where the first name is not set (redirect or display an error message)
        trigger_error('Javascript Error Handler: Error message is not set.', E_USER_ERROR);
    } else if ($_POST['error_message'] == "no_booking_link") {
        trigger_error('Javascript Error Handler: No booking link found, unit_id: ' . $_POST['unit_id'], E_USER_ERROR);
    } else if ($_POST['error_message'] == "undefined_booking_error") {
        trigger_error('Javascript Error Handler: Undefined booking error, unit_id: ' . $_POST['unit_id'], E_USER_ERROR);
    }
    exit();
}

add_action('wp_ajax_nopriv_javascript_error_action', 'handle_javascript_error');
add_action('wp_ajax_javascript_error_action', 'handle_javascript_error');

function my_custom_wp_mail_from($email)
{
    return 'system@tjekdepot.dk';
}
add_filter('wp_mail_from', 'my_custom_wp_mail_from');

// Optionally, force the "From" name for all outgoing WordPress emails
function my_custom_wp_mail_from_name($name)
{
    return 'tjekdepot.dk'; // Change this to the name you want to appear
}
add_filter('wp_mail_from_name', 'my_custom_wp_mail_from_name');
