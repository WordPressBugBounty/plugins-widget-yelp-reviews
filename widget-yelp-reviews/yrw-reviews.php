<?php
if (!defined('ABSPATH')) exit;

wp_register_script('rplg_js', plugins_url('/static/js/rplg.js', __FILE__));
wp_enqueue_script('rplg_js', plugins_url('/static/js/rplg.js', __FILE__));

include_once(dirname(__FILE__) . '/yrw-reviews-helper.php');

$business = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "yrw_yelp_business WHERE business_id = %s", $business_id));
if (!$business) {
    ?>
    <div class="yrw-error" style="padding:10px;color:#B94A48;background-color:#F2DEDE;border-color:#EED3D7;">
        <?php echo yrw_i('Business not found by BusinessID: ') . $business_id; ?>
    </div>
    <?php
    return;
}

$reviews = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "yrw_yelp_review WHERE yelp_business_id = %d ORDER BY time DESC", $business->id));

$rating = number_format((float)$business->rating, 1, '.', '');

if (is_numeric($max_width)) {
    $max_width = $max_width . 'px';
}
if (is_numeric($max_height)) {
    $max_height = $max_height . 'px';
}

$style = '';
if (isset($max_width) && strlen($max_width) > 0) {
    $style .= 'width:' . $max_width . '!important;';
}
if (isset($max_height) && strlen($max_height) > 0) {
    $style .= 'height:' . $max_height . '!important;overflow-y:auto!important;';
}
if ($centered) {
    $style .= 'margin:0 auto!important;';
}

if ($refresh_reviews) {
    $schedule_step = 60 * 60 * 24;
    $args = array($business_id);
    $schedule_cache_key = 'yrw_refresh_reviews_' . join('_', $args);
    if (get_transient($schedule_cache_key) === false) {
        wp_schedule_single_event(time() + $schedule_step, 'yrw_refresh_reviews', array($args));
        set_transient($schedule_cache_key, $schedule_cache_key, $schedule_step + 60 * 10);
    }
}
?>

<div class="wp-yrw wpac"<?php if ($style) { ?> style="<?php echo $style;?>"<?php } ?>>
    <div class="wp-yelp-list<?php if ($dark_theme) { ?> wp-dark<?php } ?>">
        <div class="wp-yelp-place">
            <?php yrw_page($business, $rating, $open_link, $nofollow_link); ?>
        </div>
        <div class="wp-yelp-content-inner">
            <?php yrw_page_reviews($reviews, $text_size, $pagination, $read_on_yelp, $open_link, $nofollow_link); ?>
        </div>
    </div>
</div>