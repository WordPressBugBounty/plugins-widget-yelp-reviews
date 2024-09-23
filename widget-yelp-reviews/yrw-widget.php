<?php
if (!defined('ABSPATH')) exit;

/**
 * Yelp Reviews Widget
 *
 * @description: The Yelp Reviews Widget
 * @since      : 1.0
 */

class Yelp_Reviews_Widget extends WP_Widget {

    public static $widget_fields = array(
        'title'                => '',
        'business_id'          => '',
        'dark_theme'           => '',
        'view_mode'            => 'list',
        'pagination'           => '',
        'text_size'            => '',
        'read_on_yelp'         => false,
        'refresh_reviews'      => true,
        'max_width'            => '',
        'max_height'           => '',
        'centered'             => false,
        'open_link'            => true,
        'nofollow_link'        => true,
    );

    public function __construct() {
        parent::__construct(
            'yrw_widget', // Base ID
            'Yelp Reviews Widget', // Name
            array(
                'classname'   => 'yelp-reviews-widget',
                'description' => yrw_i('Display Yelp Business Reviews on your website.', 'yrw')
            )
        );

        add_action('admin_enqueue_scripts', array($this, 'yrw_widget_scripts'));

        wp_register_script('wpac_time_js', plugins_url('/static/js/wpac-time.js', __FILE__), array(), YRW_VERSION);
        wp_enqueue_script('wpac_time_js', plugins_url('/static/js/wpac-time.js', __FILE__));

        wp_register_style('yrw_widget_css', plugins_url('/static/css/yrw-widget.css', __FILE__), array(), YRW_VERSION);
        wp_enqueue_style('yrw_widget_css', plugins_url('/static/css/yrw-widget.css', __FILE__));
    }

    function yrw_widget_scripts($hook) {
        if ($hook == 'widgets.php' || $hook == 'settings_page_yrw' || ($hook == 'post.php' && defined('SITEORIGIN_PANELS_VERSION'))) {

            wp_register_style('rplg_wp_css', plugins_url('/static/css/rplg-wp.css', __FILE__));
            wp_enqueue_style('rplg_wp_css', plugins_url('/static/css/rplg-wp.css', __FILE__));

            wp_enqueue_script('jquery');

            wp_register_script('yrw_wpac_js', plugins_url('/static/js/wpac.js', __FILE__), array(), YRW_VERSION);
            wp_enqueue_script('yrw_wpac_js', plugins_url('/static/js/wpac.js', __FILE__));

            wp_register_script('yrw_finder_js', plugins_url('/static/js/yrw-finder.js', __FILE__), array(), YRW_VERSION);
            wp_localize_script('yrw_finder_js', 'finderVars', array(
                'YELP_AVATAR' => YRW_AVATAR,
                'handlerUrl' => admin_url('options-general.php?page=yrw'),
                'actionPrefix' => 'yrw'
            ));
            wp_enqueue_script('yrw_finder_js', plugins_url('/static/js/yrw-finder.js', __FILE__));
        }
    }

    function widget($args, $instance) {
        global $wpdb;

        if (yrw_enabled()) {
            extract($args);
            foreach (self::$widget_fields as $variable => $value) {
                ${$variable} = !isset($instance[$variable]) ? self::$widget_fields[$variable] : esc_attr($instance[$variable]);
            }

            echo $before_widget;
            if ($title) { ?><h2 class="yrw-widget-title widget-title"><?php echo $title; ?></h2><?php }
            if ($business_id) {
                include(dirname(__FILE__) . '/yrw-reviews.php');
            } else {
                ?>
                <div class="yrw-error" style="padding:10px;color:#B94A48;background-color:#F2DEDE;border-color:#EED3D7;">
                    <?php echo yrw_i('Please first find and save your Yelp Business.'); ?>
                </div>
                <?php
            }
            echo $after_widget;
        }
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        foreach (self::$widget_fields as $field => $value) {
            $instance[$field] = isset($new_instance[$field]) ? strip_tags(stripslashes($new_instance[$field])) : '';
        }
        return $instance;
    }

    function form($instance) {
        global $wp_version;
        foreach (self::$widget_fields as $field => $value) {
            ${$field} = !isset($instance[$field]) ? $value : esc_attr($instance[$field]);
        }

        wp_nonce_field('yrw_wpnonce', 'yrw_nonce');

        $yrw_api_key = get_option('yrw_api_key');
        if ($yrw_api_key) {
            ?>
            <div id="<?php echo $this->id; ?>">
                <?php
                if (!$business_id) {
                    include(dirname(__FILE__) . '/yrw-finder.php');
                } else { ?>
                    <script type="text/javascript">
                        jQuery('.yrw-tooltip').remove();
                    </script> <?php
                }
                include(dirname(__FILE__) . '/yrw-options.php'); ?>
                <br>
            </div>
            <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" onload="(function(el) { var t = setInterval(function () {if (window.yrw_sidebar_init){yrw_sidebar_init({el: el});clearInterval(t);}}, 200); })(this.parentNode);" style="display:none">
            <?php
        } else {
            ?>
            <h4 class="text-left">First of all, please create and save the Yelp API Key on <a href="<?php echo admin_url('options-general.php?page=yrw&yrw_tab=setting'); ?>">the setting page</a> of the plugin</h4>
            <?php
        }
        ?>
        <script type="text/javascript">
            function yrw_load_js(src, cb) {
                var script = document.createElement('script');
                script.type = 'text/javascript';
                script.src = src;
                script.async = 'true';
                if (cb) {
                    script.addEventListener('load', function (e) { cb(null, e); }, false);
                }
                document.getElementsByTagName('head')[0].appendChild(script);
            }

            function yrw_load_css(href) {
                var link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = href;
                document.getElementsByTagName('head')[0].appendChild(link);
            }

            if (!window.yrw_sidebar_init) {
                yrw_load_css('<?php echo plugins_url('/static/css/rplg-wp.css?ver=' . YRW_VERSION, __FILE__); ?>');
                yrw_load_js('<?php echo plugins_url('/static/js/wpac.js?ver=' . YRW_VERSION, __FILE__); ?>', function() {
                    window.finderVars = {
                        YELP_AVATAR : '<?php echo YRW_AVATAR; ?>',
                        handlerUrl    : '<?php echo admin_url('options-general.php?page=yrw'); ?>',
                        actionPrefix  : 'yrw'
                    };
                    yrw_load_js('<?php echo plugins_url('/static/js/yrw-finder.js?ver=' . YRW_VERSION, __FILE__); ?>');
                });
            }
        </script>
        <?php
    }
}
?>