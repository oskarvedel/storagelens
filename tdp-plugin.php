<?php

/**
 * Plugin Name: tdp plugin
 * Version: 1.0
 */

require_once dirname(__FILE__) . '/tdp-bookings/tdp-bookings-module.php';
require_once dirname(__FILE__) . '/tdp-configs-module.php';
require_once dirname(__FILE__) . '/tdp-control-module.php';
require_once dirname(__FILE__) . '/tdp-scraper-import/tdp-scraper-import-module.php';
require_once dirname(__FILE__) . '/tdp-seo-text/tdp-seo-text-module.php';
require_once dirname(__FILE__) . '/tdp-consolidations/tdp-consolidations-module.php';
require_once dirname(__FILE__) . '/tdp-statistics/tdp-statistics-module.php';
require_once dirname(__FILE__) . '/tdp-unit-list/tdp-unit-list-module.php';


// Define the activation function
function tdp_plugin_activation_function()
{
    // call the function that adds the rewrite rule
    custom_rewrite_booking_confirmation();
    // flush rewrite rules to ensure the new rule is saved to the database
    flush_rewrite_rules();
}
// Define the deactivation function
function tdp_plugin_deactivation_function()
{
    // flush rules on deactivation as well
    flush_rewrite_rules();
}

add_action('admin_menu', 'register_tdp_menu_page');

// Hook the activation and deactivation functions
register_activation_hook(__FILE__, 'tdp_plugin_activation_function');
register_deactivation_hook(__FILE__, 'tdp_plugin_deactivation_function');

function register_tdp_menu_page()
{
    add_menu_page(
        __('TDP plugin', 'textdomain'), // Page title
        __('TDP plugin', 'textdomain'),     // Menu title
        'manage_options',                      // Capability
        'tdp-plugin',                       // Menu slug
        'tdp_menu_page',                 // Function to display the page
        'dashicons-admin-generic',             // Icon URL
        6                                      // Position
    );
}

function tdp_menu_page()
{
    // Output the HTML for the admin page or include a file that does.
?>
    <div class="tdp-admin-wrap">
        <h1><?php _e('TDP  Controls', 'textdomain'); ?></h1>

        <div class="tdp-section">
            <h2><?php _e('Data Generation', 'textdomain'); ?></h2>
            <!-- Add a form to handle the post request -->
            <form method="post" action="">
                <!-- Add security fields for nonce -->
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <!-- Add a hidden field to check which control was pressed -->
                <input type="hidden" name="tdp_action" value="generate_archive_item_html_for_all_gd_places">
                <input type="submit" value="<?php _e('generate_archive_item_html_for_all_gd_places', 'textdomain'); ?>" class="button">
            </form>
            <!-- Generate 50 missing ChatGPT short descriptions -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="generate_missing_chatgpt_geolocation_short_descriptions">
                <input type="submit" value="<?php _e('Generate 50 missing ChatGPT short descriptions', 'textdomain'); ?>" class="button">
            </form>
            <!-- Generate 50 missing ChatGPT descriptions -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="generate_missing_chatgpt_geolocation_descriptions">
                <input type="submit" value="<?php _e('Generate 50 missing ChatGPT descriptions', 'textdomain'); ?>" class="button">
            </form>
            <!-- Generate meta descriptions -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="generate_meta_descriptions">
                <input type="submit" value="<?php _e('Generate meta descriptions', 'textdomain'); ?>" class="button">
            </form>
            <!-- Generate meta titles -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="generate_meta_titles">
                <input type="submit" value="<?php _e('Generate meta titles', 'textdomain'); ?>" class="button">
            </form>
            <!-- Generate nearby locations lists -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="generate_nearby_locations_lists">
                <input type="submit" value="<?php _e('Generate nearby locations lists', 'textdomain'); ?>" class="button">
            </form>
            <!-- Generate missing static map images -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="generate_missing_static_map_images">
                <input type="submit" value="<?php _e('Generate missing static map images', 'textdomain'); ?>" class="button">
            </form>
            <!-- Generate top SEO texts -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="generate_top_seo_texts">
                <input type="submit" value="<?php _e('Generate top SEO texts', 'textdomain'); ?>" class="button">
            </form>
            <!-- Generate SEO texts -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="generate_seo_texts">
                <input type="submit" value="<?php _e('Generate SEO texts', 'textdomain'); ?>" class="button">
            </form>
        </div>

        <div class="tdp-section">
            <h2><?php _e('Scheduled functions', 'textdomain'); ?></h2>
            <!-- run daily functions -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="run_daily_functions">
                <input type="submit" value="<?php _e('Run Daily Functions', 'textdomain'); ?>" class="button">
            </form>

            <!-- run 4 times per day functions -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="run_4_times_per_day_functions">
                <input type="submit" value="<?php _e('Run 4 Times Per Day Functions', 'textdomain'); ?>" class="button">
            </form>
        </div>

        <div class="tdp-section">
            <h2><?php _e('Statistics', 'textdomain'); ?></h2>
            <!-- run all statistics -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="run_all_statistics">
                <input type="submit" value="<?php _e('Run All Statistics', 'textdomain'); ?>" class="button">
            </form>

            <!-- run statistics for gd_places -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="update_statistics_data_for_all_gd_places">
                <input type="submit" value="<?php _e('Run statistics for gd_places', 'textdomain'); ?>" class="button">
            </form>

            <!-- run statistics for geolocations -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="update_statistics_data_for_all_geolocations">
                <input type="submit" value="<?php _e('Run statistics for geolocations', 'textdomain'); ?>" class="button">
            </form>
        </div>

        <div class="tdp-section">
            <h2><?php _e('Consolidations', 'textdomain'); ?></h2>
            <!-- run all geolocation consolidations -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="run_all_geolocation_consolidations">
                <input type="submit" value="<?php _e('Run All Geolocation Consolidations', 'textdomain'); ?>" class="button">
            </form>
            <!--run seo geolocation consolidations -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="run_seo_geolocation_consolidations">
                <input type="submit" value="<?php _e('Run SEO Geolocation Consolidations', 'textdomain'); ?>" class="button">
            </form>
            <!--run all gd place consolidations -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="run_general_consolidations">
                <input type="submit" value="<?php _e('Run General Geolocation Consolidations', 'textdomain'); ?>" class="button">
            </form>
        </div>
    </div>

    <!-- style the admin page -->
    <style>
        .tdp-admin-wrap {
            max-width: 800px;
            margin: 0 auto;
        }

        .tdp-section {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #e5e5e5;
            border-radius: 3px;
        }

        .tdp-section h2 {
            margin-top: 0;
        }

        .tdp-form {
            margin-bottom: 10px;
        }

        .tdp-form input[type="submit"] {
            margin-top: 5px;
        }
    </style>
<?php

}

add_action('admin_init', 'tdp_plugin_handle_post');

function tdp_plugin_handle_post()
{
    // xdebug_break();
    if (isset($_POST['tdp_action'])) {
        check_admin_referer('tdp_action_nonce');

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Run the corresponding function based on the action
        $action = $_POST['tdp_action'];
        switch ($action) {
            case 'generate_archive_item_html_for_all_gd_places':
                generate_archive_item_html_for_all_gd_places();
                break;
            case 'run_daily_functions':
                tdp_daily_functions();
                break;
            case 'run_4_times_per_day_functions':
                tdp_4_times_per_day_functions();
                break;
            case 'run_all_statistics':
                update_statistics_data_for_all_gd_places();
                update_statistics_data_for_all_geolocations();
                trigger_error("updated ALL statistics", E_USER_NOTICE);
                break;
            case 'generate_missing_chatgpt_geolocation_short_descriptions':
                generate_missing_chatgpt_geolocation_short_descriptions(50);
                break;
            case 'generate_missing_chatgpt_geolocation_descriptions':
                generate_missing_chatgpt_geolocation_descriptions(50);
                break;
            case 'generate_meta_descriptions':
                generate_meta_descriptions();
                break;
            case 'generate_meta_titles':
                generate_meta_titles();
                break;
            case 'generate_nearby_locations_lists':
                generate_nearby_locations_lists();
                break;
            case 'generate_missing_static_map_images':
                generate_missing_static_map_images();
                break;
            case 'generate_top_seo_texts':
                generate_top_seo_texts();
                break;
            case 'generate_seo_texts':
                generate_seo_texts();
                break;
            case 'run_all_geolocation_consolidations':
                seo_consolidations();
                break;
            case 'run_seo_geolocation_consolidations':
                seo_consolidations();
                break;
            case 'run_general_consolidations':
                general_consolidations();
                break;
            case 'update_statistics_data_for_all_gd_places':
                update_statistics_data_for_all_gd_places();
                break;
            case 'update_statistics_data_for_all_geolocations':
                update_statistics_data_for_all_geolocations();
                break;
            default:
        }
    }
}
