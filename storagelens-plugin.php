<?php

/**
 * Plugin Name: storagelens
 * Version: 4
 */

require_once dirname(__FILE__) . '/tdp-bookings/tdp-bookings-module.php';
require_once dirname(__FILE__) . '/tdp-configs-module.php';
require_once dirname(__FILE__) . '/tdp-control-module.php';
require_once dirname(__FILE__) . '/tdp-scraper-import/tdp-scraper-import-module.php';
require_once dirname(__FILE__) . '/tdp-seo-text/tdp-seo-text-module.php';
require_once dirname(__FILE__) . '/tdp-consolidations/tdp-consolidations-module.php';
require_once dirname(__FILE__) . '/tdp-statistics/tdp-statistics-module.php';
require_once dirname(__FILE__) . '/tdp-unit-list/tdp-unit-list-module.php';
require_once dirname(__FILE__) . '/geolocation-list/geolocation-list-module.php';
require_once dirname(__FILE__) . '/common/tdp-common.php';
require_once dirname(__FILE__) . '/common/geolocation_seo_articles.php';
require_once dirname(__FILE__) . '/common/github_updater.php';


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
        __('StorageLens', 'textdomain'), // Page title
        __('StorageLens', 'textdomain'),     // Menu title
        'manage_options',                      // Capability
        'storagelens-plugin',                       // Menu slug
        'tdp_menu_page',                 // Function to display the page
        'dashicons-admin-generic',             // Icon URL
        6                                      // Position
    );
}

if (is_admin()) { // note the use of is_admin() to double check that this is happening in the admin
    $config = array(
        'slug' => plugin_basename(__FILE__), // this is the slug of your plugin
        'proper_folder_name' => 'storagelens', // this is the name of the folder your plugin lives in
        'api_url' => 'https://api.github.com/repos/oskarvedel/storagelens', // the GitHub API url of your GitHub repo
        'raw_url' => 'https://raw.github.com/oskarvedel/storagelens/main', // the GitHub raw url of your GitHub repo
        'github_url' => 'https://github.com/oskarvedel/storagelens', // the GitHub url of your GitHub repo
        'zip_url' => 'https://github.com/oskarvedel/storagelens/zipball/main', // the zip url of the GitHub repo
        'sslverify' => true, // whether WP should check the validity of the SSL cert when getting an update, see https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/2 and https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/4 for details
        'requires' => '3.0', // which version of WordPress does your plugin require?
        'tested' => '3.3', // which version of WordPress is your plugin tested up to?
        'readme' => 'README.md', // which file to use as the readme for the version number
        'access_token' => '', // Access private repositories by authorizing under Plugins > GitHub Updates when this example plugin is installed
    );
    new WP_GitHub_Updater($config);
}

function tdp_menu_page()
{
    $plugin_data = get_plugin_data(__FILE__);
    $plugin_version = $plugin_data['Version'];
    // Output the HTML for the admin page or include a file that does.
?>
    <div class="tdp-admin-wrap">
        <h1><?php _e('TDP  Controls', 'textdomain'); ?> - Version <?php echo $plugin_version; ?></h1>


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
            <!-- Generate 50 missing ChatGPT descriptions-->
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
            <!-- Generate geolocations list -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="generate_geolocation_list">
                <input type="submit" value="<?php _e('Generate geolocations list', 'textdomain'); ?>" class="button">
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

        <!--add scraper import section -->
        <div class="tdp-section">
            <h2><?php _e('Scraper Import', 'textdomain'); ?></h2>
            <!-- Import scraper data for boxdepotet -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="import_boxdepotet_scraper_data">
                <input type="submit" value="<?php _e('Import scraper data for boxdepotet', 'textdomain'); ?>" class="button">
            </form>
            <!-- Import scraper data for nettolager -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="import_nettolager_scraper_data">
                <input type="submit" value="<?php _e('Import scraper data for nettolager', 'textdomain'); ?>" class="button">
            </form>
            <!-- Import scraper data for pelican -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="import_pelican_scraper_data">
                <input type="submit" value="<?php _e('Import scraper data for pelican', 'textdomain'); ?>" class="button">
            </form>
            <!-- Import scraper data for City Self Storage -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="import_cityselfstorage_scraper_data">
                <input type="submit" value="<?php _e('Import scraper data for City Self Storage', 'textdomain'); ?>" class="button">
            </form>
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="remove_boxdepotet_scraper_data">
                <input type="submit" value="<?php _e('Remove boxdepotet data', 'textdomain'); ?>" class="button">
            </form>
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="remove_nettolager_scraper_data">
                <input type="submit" value="<?php _e('Remove nettolager data', 'textdomain'); ?>" class="button">
            </form>
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="remove_pelican_scraper_data">
                <input type="submit" value="<?php _e('Remove pelican data', 'textdomain'); ?>" class="button">
            </form>
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="remove_cityselfstorage_scraper_data">
                <input type="submit" value="<?php _e('Remove cityselfstorage data', 'textdomain'); ?>" class="button">
            </form>
        </div>
        <div class="tdp-section">
            <!-- Import article titles -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="import_article_titles">
                <input type="submit" value="<?php _e('Import article titles', 'textdomain'); ?>" class="button">
            </form>
            <!--  write_articles_for_geolocation -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="write_articles_for_geolocation">
                <input type="text" name="geolocation_id" placeholder="Enter geolocation ID">
                <input type="submit" value="<?php _e('Write articles for geolocation', 'textdomain'); ?>" class="button">
            </form>
        </div>
        <div class="tdp-section">
            <!-- generate chatgpt article -->
            <form method="post" action="">
                <?php wp_nonce_field('tdp_action_nonce'); ?>
                <input type="hidden" name="tdp_action" value="generate chatgpt article">
                <textarea name="prompt" placeholder="Enter your text here" required rows="10" cols="50"></textarea>
                <input type="text" name="geolocation" placeholder="Enter geolocation">
                <input type="submit" value="<?php _e('Generate chatgpt article', 'textdomain'); ?>" class="button">
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
            case 'generate_geolocation_list':
                generate_geolocation_list();
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
            case 'import_boxdepotet_scraper_data':
                import_scraper_data("boxdepotet");
                break;
            case 'import_nettolager_scraper_data':
                import_scraper_data("nettolager");
                break;
            case 'import_pelican_scraper_data':
                import_scraper_data("pelican");
                break;
            case 'import_cityselfstorage_scraper_data':
                import_scraper_data("cityselfstorage");
                break;
            case 'remove_boxdepotet_scraper_data':
                remove_scraper_data("boxdepotet");
                break;
            case 'remove_nettolager_scraper_data':
                remove_scraper_data("nettolager");
                break;
            case 'remove_pelican_scraper_data':
                remove_scraper_data("pelican");
                break;
            case 'remove_cityselfstorage_scraper_data':
                remove_scraper_data("cityselfstorage");
                break;
            case 'generate chatgpt article':
                $prompt = $_POST['prompt'];
                $geolocation = $_POST['geolocation'];
                generate_chatgpt_seo_article($prompt, $geolocation);
            case 'import_article_titles':
                import_article_titles();
                break;
            case 'write_articles_for_geolocation':
                $geolocation_id = $_POST['geolocation_id'];
                write_articles_for_geolocation($geolocation_id);
                break;
            default:
        }
    }
}
