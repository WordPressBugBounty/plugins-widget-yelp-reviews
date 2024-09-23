<?php
/*
Plugin Name: Widget for Yelp Reviews
Plugin URI: https://richplugins.com
Description: Instantly Yelp rating and reviews on your website to increase user confidence and SEO.
Author: RichPlugins <support@richplugins.com>
Version: 1.7.8
Author URI: https://richplugins.com
*/

if (!defined('ABSPATH')) exit;

require(ABSPATH . 'wp-includes/version.php');

include_once(dirname(__FILE__) . '/api/urlopen.php');
include_once(dirname(__FILE__) . '/helper/debug.php');

define('YRW_VERSION',            '1.7.8');
define('YRW_API',                'https://api.yelp.com/v3/businesses');
define('YRW_PLUGIN_URL',         plugins_url(basename(plugin_dir_path(__FILE__ )), basename(__FILE__)));
define('YRW_AVATAR',             YRW_PLUGIN_URL . '/static/img/yelp-avatar.png');

function yrw_options() {
    return array(
        'yrw_version',
        'yrw_active',
        'yrw_api_key',
        'yrw_language',
        'yrw_activation_time',
        'yrw_rev_notice_hide',
        'rplg_rev_notice_show',
    );
}

/*-------------------------------- Widget --------------------------------*/
function yrw_init_widget() {
    if (!class_exists('Yelp_Reviews_Widget')) {
        require 'yrw-widget.php';
    }
}
add_action('widgets_init', 'yrw_init_widget');

function yrw_register_widget() {
    return register_widget("Yelp_Reviews_Widget");
}
add_action('widgets_init', 'yrw_register_widget');

/*-------------------------------- Menu --------------------------------*/
function yrw_setting_menu() {
     add_submenu_page(
         'options-general.php',
         'Yelp Reviews Widget',
         'Yelp Reviews Widget',
         'moderate_comments',
         'yrw',
         'yrw_setting'
     );
}
add_action('admin_menu', 'yrw_setting_menu', 10);

function yrw_setting() {
    include_once(dirname(__FILE__) . '/yrw-setting.php');
}

/*-------------------------------- Links --------------------------------*/
function yrw_plugin_action_links($links, $file) {
    $plugin_file = basename(__FILE__);
    if (basename($file) == $plugin_file) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=yrw') . '">'.yrw_i('Settings') . '</a>';
        array_unshift($links, $settings_link);
    }
    return $links;
}
add_filter('plugin_action_links', 'yrw_plugin_action_links', 10, 2);

/*-------------------------------- Row Meta --------------------------------*/
function yrw_plugin_row_meta($input, $file) {
    if ($file != plugin_basename( __FILE__ )) {
        return $input;
    }

    $links = array(
        //'<a href="' . esc_url('https://richplugins.com') . '" target="_blank">' . yrw_i('View Documentation') . '</a>',
        '<a href="' . esc_url('https://richplugins.com/business-reviews-bundle-wordpress-plugin') . '" target="_blank">' . yrw_i('Upgrade to Business') . ' &raquo;</a>',
    );
    $input = array_merge($input, $links);
    return $input;
}
add_filter('plugin_row_meta', 'yrw_plugin_row_meta', 10, 2);

/*-------------------------------- Database --------------------------------*/
function yrw_activation($network_wide = false) {
    $now = time();
    update_option('yrw_activation_time', $now);

    add_option('yrw_is_multisite', $network_wide);
    if (yrw_does_need_update()) {
        yrw_install();
    }
}
register_activation_hook(__FILE__, 'yrw_activation');

function yrw_install() {

    $version = (string)get_option('yrw_version');
    if (!$version) {
        $version = '0';
    }

    $network_wide = get_option('yrw_is_multisite');

    if ($network_wide) {
        $site_ids = get_sites(array(
            'fields'     => 'ids',
            'network_id' => get_current_network_id()
        ));
        foreach($site_ids as $site_id) {
            switch_to_blog($site_id);
            yrw_install_single_site($version);
            restore_current_blog();
        }
    } else {
        yrw_install_single_site($version);
    }
}

function yrw_install_single_site($version) {
    yrw_install_db();

    if (version_compare($version, YRW_VERSION, '=')) {
        return;
    }

    add_option('yrw_active', '1');
    add_option('yrw_api_key', '');
    update_option('yrw_version', YRW_VERSION);
}

function yrw_install_db() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "yrw_yelp_business (".
           "id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,".
           "business_id VARCHAR(100) NOT NULL,".
           "name VARCHAR(255) NOT NULL,".
           "photo VARCHAR(255),".
           "address VARCHAR(255),".
           "rating DOUBLE PRECISION,".
           "url VARCHAR(255),".
           "website VARCHAR(255),".
           "review_count INTEGER NOT NULL,".
           "PRIMARY KEY (`id`),".
           "UNIQUE INDEX yrw_business_id (`business_id`)".
           ") " . $charset_collate . ";";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta($sql);

    $sql = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "yrw_yelp_review (".
           "id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,".
           "yelp_business_id BIGINT(20) UNSIGNED NOT NULL,".
           "hash VARCHAR(40) NOT NULL,".
           "rating INTEGER NOT NULL,".
           "text VARCHAR(10000),".
           "url VARCHAR(255),".
           "time VARCHAR(20) NOT NULL,".
           "author_name VARCHAR(255),".
           "author_img VARCHAR(255),".
           "PRIMARY KEY (`id`),".
           "UNIQUE INDEX yrw_yelp_review_hash (`hash`),".
           "INDEX yrw_yelp_business_id (`yelp_business_id`)".
           ") " . $charset_collate . ";";

    dbDelta($sql);
}

function yrw_reset($reset_db) {
    global $wpdb;

    if (function_exists('is_multisite') && is_multisite()) {
        $current_blog_id = get_current_blog_id();
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            yrw_reset_data($reset_db);
        }
        switch_to_blog($current_blog_id);
    } else {
        yrw_reset_data($reset_db);
    }
}

function yrw_reset_data($reset_db) {
    global $wpdb;

    foreach (yrw_options() as $opt) {
        delete_option($opt);
    }
    if ($reset_db) {
        $wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "yrw_yelp_business;");
        $wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "yrw_yelp_review;");
    }
}

/*-------------------------------- Shortcode --------------------------------*/
function yrw_shortcode($atts) {
    global $wpdb;

    if (!yrw_enabled()) return '';
    if (!class_exists('Yelp_Reviews_Widget')) return '';

    $shortcode_atts = array();
    foreach (Yelp_Reviews_Widget::$widget_fields as $field => $value) {
        $shortcode_atts[$field] = isset($atts[$field]) ? strip_tags(stripslashes($atts[$field])) : '';
    }

    foreach ($shortcode_atts as $variable => $value) {
        ${$variable} = esc_attr($shortcode_atts[$variable]);
    }

    ob_start();
    if (empty($business_id)) {
        ?>
        <div class="yrw-error" style="padding:10px;color:#b94a48;background-color:#f2dede;border-color:#eed3d7;max-width:200px;">
            <?php echo yrw_i('<b>Google Reviews Business</b>: required attribute business_id is not defined'); ?>
        </div>
        <?php
    } else {
        include(dirname(__FILE__) . '/yrw-reviews.php');
    }
    return preg_replace('/[\n\r]/', '', ob_get_clean());
}
add_shortcode("yrw", "yrw_shortcode");

/*-------------------------------- Request --------------------------------*/
function yrw_request_handler() {
    global $wpdb;

    if (!empty($_GET['cf_action'])) {

        switch ($_GET['cf_action']) {
            case 'yrw_api_key':
                if (current_user_can('manage_options')) {
                    if (isset($_POST['yrw_wpnonce']) === false) {
                        $error = yrw_i('Unable to call request. Make sure you are accessing this page from the Wordpress dashboard.');
                        $response = compact('error');
                    } else {
                        check_admin_referer('yrw_wpnonce', 'yrw_wpnonce');

                        // Validate, sanitize, escape
                        update_option('yrw_api_key', trim(sanitize_text_field($_POST['app_key'])));
                        $status = 'success';

                        $response = compact('status');
                    }
                    header('Content-type: text/javascript');
                    echo json_encode($response);
                    die();
                }
            break;
            case 'yrw_search':
                if (current_user_can('manage_options')) {
                    if (isset($_GET['yrw_wpnonce']) === false) {
                        $error = yrw_i('Unable to call request. Make sure you are accessing this page from the Wordpress dashboard.');
                        $response = compact('error');
                    } else {
                        check_admin_referer('yrw_wpnonce', 'yrw_wpnonce');
                        $term = trim(sanitize_text_field($_GET['term']));
                        $location = trim(sanitize_text_field($_GET['location']));
                        $api_url = YRW_API . '/search?term=' . $term . '&location=' . $location;
                        $api_key = get_option('yrw_api_key');
                        $response = rplg_json_urlopen($api_url, null, array(
                            'Authorization: Bearer ' . $api_key
                        ));
                    }
                    header('Content-type: text/javascript');
                    echo json_encode($response);
                    die();
                }
            break;
            case 'yrw_reviews':
                if (current_user_can('manage_options')) {
                    if (isset($_GET['yrw_wpnonce']) === false) {
                        $error = yrw_i('Unable to call request. Make sure you are accessing this page from the Wordpress dashboard.');
                        $response = compact('error');
                    } else {
                        check_admin_referer('yrw_wpnonce', 'yrw_wpnonce');
                        $business_id = trim(sanitize_text_field($_GET['business_id']));
                        $api_key = get_option('yrw_api_key');
                        $response = rplg_json_urlopen(yrw_api_url($business_id), null, array(
                            'Authorization: Bearer ' . $api_key
                        ));
                    }
                    header('Content-type: text/javascript');
                    echo json_encode($response);
                    die();
                }
            break;
            case 'yrw_save':
                if (current_user_can('manage_options')) {
                    if (isset($_POST['yrw_wpnonce']) === false) {
                        $error = yrw_i('Unable to call request. Make sure you are accessing this page from the Wordpress dashboard.');
                        $response = compact('error');
                    } else {
                        check_admin_referer('yrw_wpnonce', 'yrw_wpnonce');
                        $api_key = get_option('yrw_api_key');

                        // Validate, sanitize, escape
                        $business_id = trim(sanitize_text_field($_POST['business_id']));

                        $business = rplg_json_urlopen(YRW_API . '/' . $business_id, null, array(
                            'Authorization: Bearer ' . $api_key
                        ));
                        $reviews = rplg_json_urlopen(yrw_api_url($business_id), null, array(
                            'Authorization: Bearer ' . $api_key
                        ));
                        yrw_save_reviews($business, $reviews);
                        $response = 'success';

                    }
                    header('Content-type: text/javascript');
                    echo json_encode($business);
                    die();
                }
            break;
        }
    }
}
add_action('init', 'yrw_request_handler');

function yrw_save_reviews($business, $reviews) {
    global $wpdb;

    $yelp_business_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . $wpdb->prefix . "yrw_yelp_business WHERE business_id = %s", $business->id));
    if ($yelp_business_id) {
        $wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "yrw_yelp_business SET rating = %s, review_count = %s WHERE ID = %s", $business->rating, $business->review_count, $yelp_business_id));
    } else {
        $address = implode(", ", array($business->location->address1, $business->location->city, $business->location->state, $business->location->zip_code));
        $wpdb->query($wpdb->prepare("INSERT INTO " . $wpdb->prefix . "yrw_yelp_business (business_id, name, photo, address, rating, url, review_count) VALUES (%s, %s, %s, %s, %s, %s, %s)", $business->id, $business->name, $business->image_url, $address, $business->rating, $business->url, $business->review_count));
        $yelp_business_id = $wpdb->insert_id;
    }

    if ($reviews && $reviews->reviews) {
        foreach ($reviews->reviews as $review) {
            $hash = sha1($business->id . $review->time_created);
            $yelp_review_hash = $wpdb->get_var($wpdb->prepare("SELECT hash FROM " . $wpdb->prefix . "yrw_yelp_review WHERE hash = %s", $hash));
            if (!$yelp_review_hash) {
                $wpdb->query($wpdb->prepare("INSERT INTO " . $wpdb->prefix . "yrw_yelp_review (yelp_business_id, hash, rating, text, url, time, author_name, author_img) VALUES (%d, %s, %s, %s, %s, %s, %s, %s)", $yelp_business_id, $hash, $review->rating, $review->text, $review->url, $review->time_created, $review->user->name, $review->user->image_url));
            }
        }
    }
}

/*-------------------------------- Refresh Reviews --------------------------------*/
function yrw_refresh_reviews($args) {
    $api_key = get_option('yrw_api_key');
    if (!$api_key || strlen($api_key) < 1) {
        return;
    }

    $business_id = $args[0];

    $business = rplg_json_urlopen(YRW_API . '/' . $business_id, null, array(
        'Authorization: Bearer ' . $api_key
    ));
    $reviews = rplg_json_urlopen(yrw_api_url($business_id), null, array(
        'Authorization: Bearer ' . $api_key
    ));
    yrw_save_reviews($business, $reviews);

    delete_transient('yrw_refresh_reviews_' . join('_', $args));
}
add_action('yrw_refresh_reviews', 'yrw_refresh_reviews');

/*-------------------------------- Init language --------------------------------*/
function yrw_lang_init() {
    $plugin_dir = basename(dirname(__FILE__));
    load_plugin_textdomain('yrw', false, basename( dirname( __FILE__ ) ) . '/languages');
}
add_action('plugins_loaded', 'yrw_lang_init');

/*-------------------------------- Leave review --------------------------------*/
function yrw_admin_notice() {
    if (!is_admin()) return;

    $activation_time = get_option('yrw_activation_time');

    if ($activation_time == '') {
        $activation_time = time() - 86400*2;
        update_option('yrw_activation_time', $activation_time);
    }

    $rev_notice = isset($_GET['yrw_rev_notice']) ? sanitize_text_field($_GET['yrw_rev_notice']) : '';
    if ($rev_notice == 'later') {
        $activation_time = time() - 86400*2;
        update_option('yrw_activation_time', $activation_time);
        update_option('yrw_rev_notice_hide', 'later');
    } else if ($rev_notice == 'never') {
        update_option('yrw_rev_notice_hide', 'never');
    }

    $rev_notice_hide = get_option('yrw_rev_notice_hide');
    $rev_notice_show = get_option('rplg_rev_notice_show');

    if ($rev_notice_show == '' || $rev_notice_show == 'yrw') {

        if ($rev_notice_hide != 'never' && $activation_time < (time() - 86400*3)) {
            update_option('rplg_rev_notice_show', 'yrw');
            $class = 'notice notice-info is-dismissible';
            $url = remove_query_arg(array('taction', 'tid', 'sortby', 'sortdir', 'opt'));
            $url_later = esc_url(add_query_arg('yrw_rev_notice', 'later', $url));
            $url_never = esc_url(add_query_arg('yrw_rev_notice', 'never', $url));

            $notice = '<p style="font-weight:normal;">' .
                          'Hello, I noticed you have been using my <b>Yelp Reviews Widget</b> plugin for a while now – that’s awesome!<br>' .
                          'Could you please do me a BIG favor and give it a 5-star rating on WordPress?<br><br>' .
                          '-- Thanks! Daniel K.' .
                      '</p>' .
                      '<p>' .
                          '<a href="https://wordpress.org/support/plugin/widget-yelp-reviews/reviews/#new-post" style="text-decoration:none;" target="_blank">' .
                              '<button class="button button-primary" style="margin-right:5px;">OK, you deserve it</button>' .
                          '</a>' .
                          '<a href="' . $url_later . '" style="text-decoration:none;">' .
                              '<button class="button button-secondary">Not now, maybe later</button>' .
                          '</a>' .
                          '<a href="' . $url_never . '" style="text-decoration:none;">' .
                              '<button class="button button-secondary" style="float:right;">Do not remind me again</button>' .
                          '</a>' .
                      '</p>' .
                      '<p style="color:#999;font-size:12px;">' .
                          'By the way, if you have been thinking about upgrading to the ' .
                          '<a href="https://richplugins.com/business-reviews-bundle-wordpress-plugin" target="_blank">Business</a> ' .
                          'version, here is a 25% off onboard coupon ->  <b>business25off</b>' .
                      '</p>';

            printf('<div class="%1$s" style="position:fixed;top:50px;right:20px;padding-right:30px;z-index:1;margin-left:20px">%2$s</div>', esc_attr($class), $notice);
        } else {
            update_option('rplg_rev_notice_show', '');
        }

    }
}
add_action('admin_notices', 'yrw_admin_notice');

/*-------------------------------- Helpers --------------------------------*/
function yrw_enabled() {
    global $id, $post;

    $active = get_option('yrw_active');
    if (empty($active) || $active === '0') { return false; }
    return true;
}

function yrw_api_url($business_id, $reviews_lang = '') {
    $url = YRW_API . '/' . $business_id . '/reviews';

    $yrw_language = strlen($reviews_lang) > 0 ? $reviews_lang : get_option('yrw_language');
    if (strlen($yrw_language) > 0) {
        $url = $url . '?locale=' . $yrw_language;
    }
    return $url;
}


function yrw_does_need_update() {
    $version = (string)get_option('yrw_version');
    if (empty($version)) {
        $version = '0';
    }
    if (version_compare($version, '1.0', '<')) {
        return true;
    }
    return false;
}

function yrw_i($text, $params=null) {
    if (!is_array($params)) {
        $params = func_get_args();
        $params = array_slice($params, 1);
    }
    return vsprintf(__($text, 'yrw'), $params);
}

?>