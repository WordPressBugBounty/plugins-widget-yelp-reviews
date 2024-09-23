<?php

if (!current_user_can('manage_options')) {
    die('The account you\'re logged in to doesn\'t have permission to access this page.');
}

function yrw_has_valid_nonce() {
    $nonce_actions = array('yrw_reset', 'yrw_settings', 'yrw_active', 'yrw_advance');
    $nonce_form_prefix = 'yrw-form_nonce_';
    $nonce_action_prefix = 'yrw-wpnonce_';
    foreach ($nonce_actions as $key => $value) {
        if (isset($_POST[$nonce_form_prefix.$value])) {
            check_admin_referer($nonce_action_prefix.$value, $nonce_form_prefix.$value);
            return true;
        }
    }
    return false;
}

function yrw_debug() {
    global $wpdb;
    $businesses = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "yrw_yelp_business");
    $businesses_error = $wpdb->last_error;
    $reviews = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "yrw_yelp_review");
    $reviews_error = $wpdb->last_error; ?>

DB Businesses: <?php echo print_r($businesses); ?>

DB Businesses error: <?php echo $businesses_error; ?>

DB Reviews: <?php echo print_r($reviews); ?>

DB Reviews error: <?php echo $reviews_error;
}

if (!empty($_POST)) {
    $nonce_result_check = yrw_has_valid_nonce();
    if ($nonce_result_check === false) {
        die('Unable to save changes. Make sure you are accessing this page from the Wordpress dashboard.');
    }
}

// Reset
if (isset($_POST['reset_all'])) {
    yrw_reset(isset($_POST['reset_db']));
    unset($_POST);
?>
<div class="wrap">
    <h3><?php echo yrw_i('Yelp Reviews Widget Reset'); ?></h3>
    <form method="POST" action="?page=yrw">
        <?php wp_nonce_field('yrw-wpnonce_yrw_reset', 'yrw-form_nonce_yrw_reset'); ?>
        <p><?php echo yrw_i('Yelp Reviews Widget has been reset successfully.') ?></p>
        <ul style="list-style: circle;padding-left:20px;">
            <li><?php echo yrw_i('Local settings for the plugin were removed.') ?></li>
        </ul>
        <p>
            <?php echo yrw_i('If you wish to reinstall, you can do that now.') ?>
            <a href="?page=yrw">&nbsp;<?php echo yrw_i('Reinstall') ?></a>
        </p>
    </form>
</div>
<?php
die();
}

// Post fields that require verification.
$valid_fields = array(
    'yrw_active' => array(
        'key_name' => 'yrw_active',
        'values' => array('Disable', 'Enable')
    ));

// Check POST fields and remove bad input.
foreach ($valid_fields as $key) {

    if (isset($_POST[$key['key_name']]) ) {

        // SANITIZE first
        $_POST[$key['key_name']] = trim(sanitize_text_field($_POST[$key['key_name']]));

        // Validate
        if (isset($key['regexp']) && $key['regexp']) {
            if (!preg_match($key['regexp'], $_POST[$key['key_name']])) {
                unset($_POST[$key['key_name']]);
            }

        } else if (isset($key['type']) && $key['type'] == 'int') {
            if (!intval($_POST[$key['key_name']])) {
                unset($_POST[$key['key_name']]);
            }

        } else {
            $valid = false;
            $vals = $key['values'];
            foreach ($vals as $val) {
                if ($_POST[$key['key_name']] == $val) {
                    $valid = true;
                }
            }
            if (!$valid) {
                unset($_POST[$key['key_name']]);
            }
        }
    }
}

if (isset($_POST['yrw_active']) && isset($_GET['yrw_active'])) {
    update_option('yrw_active', ($_GET['yrw_active'] == '1' ? '1' : '0'));
}

if (isset($_POST['yrw_setting'])) {
    $yrw_api_key = trim(sanitize_text_field($_POST['yrw_api_key']));
    update_option('yrw_api_key', $yrw_api_key);
    if (strlen($yrw_api_key) > 0) {
        $yelp_test_url = yrw_api_url('benjamin-steakhouse-new-york-2');
        $yelp_response = rplg_json_urlopen($yelp_test_url, null, array(
            'Authorization: Bearer ' . $yrw_api_key
        ));
        if (isset($yelp_response->error) && strlen($yelp_response->error->description) > 0) {
            $yelp_api_key_error = $yelp_response->error->description;
        }
    }
}

if (isset($_POST['create_db'])) {
    yrw_install_db();
}

if (isset($_POST['install'])) {
    yrw_reset(true);
    yrw_install();
}

wp_register_style('rplg_setting_css', plugins_url('/static/css/rplg-setting.css', __FILE__));
wp_enqueue_style('rplg_setting_css', plugins_url('/static/css/rplg-setting.css', __FILE__));

wp_enqueue_script('jquery');

$tab         = isset($_GET['yrw_tab']) && strlen($_GET['yrw_tab']) > 0 ? sanitize_text_field($_GET['yrw_tab']) : 'about';
$yrw_enabled = get_option('yrw_active') == '1';
$yrw_api_key = get_option('yrw_api_key');
?>

<span class="rplg-version"><?php echo yrw_i('Free Version: %s', esc_html(YRW_VERSION)); ?></span>

<div class="rplg-setting">

    <div class="rplg-page-title">
        <img src="<?php echo YRW_PLUGIN_URL . '/static/img/yelp-logo.png'; ?>" alt="Yelp" style="height:45px"> Reviews Widget
    </div>

    <div class="rplg-settings-workspace">

        <div data-nav-tabs="">
            <div class="nav-tab-wrapper">
                <a href="#about"     class="nav-tab<?php if ($tab == 'about')     { ?> nav-tab-active<?php } ?>"><?php echo yrw_i('About'); ?></a>
                <a href="#setting"   class="nav-tab<?php if ($tab == 'setting')   { ?> nav-tab-active<?php } ?>"><?php echo yrw_i('Settings'); ?></a>
                <a href="#shortcode" class="nav-tab<?php if ($tab == 'shortcode') { ?> nav-tab-active<?php } ?>"><?php echo yrw_i('Shortcode'); ?></a>
                <a href="#support"   class="nav-tab<?php if ($tab == 'support')   { ?> nav-tab-active<?php } ?>"><?php echo yrw_i('Support'); ?></a>
                <a href="#advance"   class="nav-tab<?php if ($tab == 'advance')   { ?> nav-tab-active<?php } ?>"><?php echo yrw_i('Advance'); ?></a>
            </div>

            <div id="about" class="tab-content" style="display:<?php echo $tab == 'about' ? 'block' : 'none'?>;">
                <h3>Yelp Reviews Widget for WordPress</h3>
                <div class="rplg-flex-row">
                    <div class="rplg-flex-col">
                        <span>Yelp Reviews plugin is an easy and fast way to integrate Yelp business reviews right into your WordPress website. This plugin works instantly and keep Yelp places and reviews into a WordPress database thus it has no depend on external services.</span>
                        <p>Please see Introduction Video to understand how it works. Also you can find most common answers and solutions for most common questions and issues in next tabs.</p>
                        <div class="rplg-alert rplg-alert-success">
                            <strong>Try more features in the Business version</strong>: Merge Google, Facebook and Yelp reviews, Beautiful themes (Slider, Grid, Trust Badges), Shortcode support, Rich Snippets, Rating filter, Any sorting, Include/Exclude words filter, Hide/Show any elements, Priority support and many others.
                            <a class="button-primary button" href="https://richplugins.com/business-reviews-bundle-wordpress-plugin" target="_blank" style="margin-left:10px">Upgrade to Business</a>
                        </div>
                        <br>
                        <div class="rplg-socials">
                            <div id="fb-root"></div>
                            <script>(function(d, s, id) {
                              var js, fjs = d.getElementsByTagName(s)[0];
                              if (d.getElementById(id)) return;
                              js = d.createElement(s); js.id = id;
                              js.src = "//connect.facebook.net/en_EN/sdk.js#xfbml=1&version=v2.6&appId=1501100486852897";
                              fjs.parentNode.insertBefore(js, fjs);
                            }(document, 'script', 'facebook-jssdk'));</script>
                            <div class="fb-like" data-href="https://richplugins.com/" data-layout="button_count" data-action="like" data-show-faces="true" data-share="false"></div>
                            <a href="https://twitter.com/richplugins?ref_src=twsrc%5Etfw" class="twitter-follow-button" data-show-count="false">Follow @richplugins</a><script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
                            <div class="g-plusone" data-size="medium" data-annotation="inline" data-width="200" data-href="https://plus.google.com/101080686931597182099"></div>
                            <script type="text/javascript">
                                window.___gcfg = { lang: 'en-US' };
                                (function () {
                                    var po = document.createElement('script');
                                    po.type = 'text/javascript';
                                    po.async = true;
                                    po.src = 'https://apis.google.com/js/plusone.js';
                                    var s = document.getElementsByTagName('script')[0];
                                    s.parentNode.insertBefore(po, s);
                                })();
                            </script>
                        </div>
                    </div>
                    <div class="rplg-flex-col">
                        <iframe width="100%" height="315" src="https://www.youtube.com/embed/nVyxAHmYQkU" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                </div>
            </div>

            <div id="setting" class="tab-content" style="display:<?php echo $tab == 'setting' ? 'block' : 'none'?>;">
                <h3>General Settings</h3>
                <form method="post" action="?page=yrw&amp;yrw_tab=setting&amp;yrw_active=<?php echo (string)((int)($yrw_enabled != true)); ?>">
                    <div class="rplg-field">
                        <div class="rplg-field-label">
                            <label>The plugin is currently <b><?php echo $yrw_enabled ? 'enabled' : 'disabled' ?></b></label>
                        </div>
                        <div class="wp-review-field-option">
                            <?php wp_nonce_field('yrw-wpnonce_yrw_active', 'yrw-form_nonce_yrw_active'); ?>
                            <input type="submit" name="yrw_active" class="button" value="<?php echo $yrw_enabled ? yrw_i('Disable') : yrw_i('Enable'); ?>" />
                        </div>
                    </div>
                </form>
                <form method="POST" action="?page=yrw&amp;yrw_tab=setting" enctype="multipart/form-data">
                    <?php wp_nonce_field('yrw-wpnonce_yrw_settings', 'yrw-form_nonce_yrw_settings'); ?>
                    <div class="rplg-field">
                        <div class="rplg-field-label">
                            <label>Yelp API Key</label>
                        </div>
                        <div class="wp-review-field-option">
                            <input type="text" id="yrw_api_key" name="yrw_api_key" class="regular-text" value="<?php echo esc_attr($yrw_api_key); ?>">
                            <?php if (isset($yelp_api_key_error)) {?>
                            <div class="rplg-alert rplg-alert-dismissible rplg-alert-danger">
                                API key is wrong.<br>
                                Please get the correct key by instruction below ↓
                            </div>
                            <?php } ?>
                            <div style="padding-top:15px">
                                <input type="submit" value="Save" name="yrw_setting" class="button" />
                            </div>
                        </div>
                    </div>
                    <div class="rplg-field">
                        <div class="rplg-field-label">
                            <label>Instruction: how to create Yelp API key</label>
                        </div>
                        <div class="wp-review-field-option">
                            <p>1. If you do not have a <b>free Yelp account (not a business)</b>, please <a href="https://www.yelp.com/signup" target="_blank">Sign Up Here</a></p>
                            <p>2. Under the free Yelp account, go to the <a href="https://www.yelp.com/developers/v3/manage_app" target="_blank">Yelp developers</a> page and create new app</p>
                            <p>3. Copy <b>API Key</b> to this setting and <b>Save</b></p>
                            <p><b>Video instruction</b></p>
                            <iframe src="//www.youtube.com/embed/GFhGN36Wf7Q?rel=0" allowfullscreen=""></iframe>
                        </div>
                    </div>
                </form>
            </div>

            <div id="shortcode" class="tab-content" style="display:<?php echo $tab == 'shortcode' ? 'block' : 'none'?>;">
                <h3>Shortcode</h3>
                <div class="rplg-flex-row">
                    <div class="rplg-flex-col3">
                        <div class="widget-content">
                            <?php $yrw_widget = new Yelp_Reviews_Widget; $yrw_widget->form(array()); ?>
                        </div>
                    </div>
                    <div class="rplg-flex-col6">
                        <div class="shortcode-content">
                            <textarea id="rplg_shortcode" style="display:block;width:100%;height:200px;padding:10px" onclick="window.rplg_shortcode.select();document.execCommand('copy');window.rplg_shortcode_msg.innerHTML='Shortcode copied, please paste it to the page content';" readonly>Connect Yelp business to show the shortcode</textarea>
                            <p id="rplg_shortcode_msg"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div id="support" class="tab-content" style="display:<?php echo $tab == 'support' ? 'block' : 'none'?>;">
                <h3>Most Common Questions</h3>
                <div class="rplg-flex-row">
                    <div class="rplg-flex-col">
                        <div class="rplg-support-question">
                            <h3>How I can connect my Yelp business?</h3>
                            <p>You just need to find your business page on Yelp, for instance https://yelp.com/biz/benjamin-steakhouse-new-york-2 and copy & paste this link to the <b>Link to Business</b> field and connect.</p>
                        </div>
                    </div>
                    <div class="rplg-flex-col">
                        <div class="rplg-support-question">
                            <h3>I can't connect my business.</h3>
                            <p>Please check that your business has at least one review for use in the plugin, if so, check the Yelp API key that it is correctly fetched by instruction: <a href="<?php echo admin_url('options-general.php?page=yrw&yrw_tab=setting'); ?>">how to create Yelp API key</a></p>
                        </div>
                    </div>
                </div>
                <div class="rplg-flex-row">
                    <div class="rplg-flex-col">
                        <div class="rplg-support-question">
                            <h3>Why I see only 3 reviews even in this Business version?</h3>
                            <p>The plugin uses the Yelp API to get your reviews. The API only returns the 3 most helpful reviews. Unfortunately, it is a limitation of Yelp, not specifically the plugin.</p>
                        </div>
                    </div>
                    <div class="rplg-flex-col">
                        <div class="rplg-support-question">
                            <h3>Reviews are trimmed with ellipsis (...), why?</h3>
                            <p>The plugin uses the Yelp API to get your reviews. The API only returns the 3 most helpful reviews with trimmed ended. Unfortunately, it is a limitation of Yelp, not the plugin.</p>
                        </div>
                    </div>
                </div>
                <div class="rplg-flex-row">
                    <div class="rplg-flex-col">
                        <div class="rplg-support-question">
                            <h3>If you need support</h3>
                            <p>You can contact us directly by email <a href="mailto:support@richplugins.com">support@richplugins.com</a> and would be great and save us a lot of time if each request to the support will contain the following data:</p>
                            <ul>
                                <li><b>1.</b> Clear and understandable description of the issue;</li>
                                <li><b>2.</b> Direct links to your reviews on: Google map;</li>
                                <li><b>3.</b> Link to the page of your site where the plugin installed;</li>
                                <li><b>4.</b> Better if you attach a screenshot(s) (or screencast) how you determine the issue;</li>
                                <li><b>5. The most important:</b> please always copy & paste the DEBUG INFORMATION from the <b>Advance</b> tab.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="rplg-flex-col">
                        <div class="rplg-support-question">
                        </div>
                    </div>
                </div>
            </div>

            <div id="advance" class="tab-content" style="display:<?php echo $tab == 'advance' ? 'block' : 'none'?>;">
                <h3>Advance Options</h3>
                <form method="post" action="?page=yrw&amp;yrw_tab=advance">
                    <?php wp_nonce_field('yrw-wpnonce_yrw_advance', 'yrw-form_nonce_yrw_advance'); ?>
                    <div class="rplg-field">
                        <div class="rplg-field-label">
                            <label>Re-create the database tables of the plugin (service option)</label>
                        </div>
                        <div class="wp-review-field-option">
                            <input type="submit" value="Re-create Database" name="create_db" onclick="return confirm('Are you sure you want to re-create database tables?')" class="button" />
                        </div>
                    </div>
                    <div class="rplg-field">
                        <div class="rplg-field-label">
                            <label><b>Please be careful</b>: this removes all settings, reviews and install the plugin from scratch</label>
                        </div>
                        <div class="wp-review-field-option">
                            <input type="submit" value="Install from scratch" name="install" onclick="return confirm('It will delete all current reviews, are you sure you want to install the plugin from scratch?')" class="button" />
                        </div>
                    </div>
                    <div class="rplg-field">
                        <div class="rplg-field-label">
                            <label><b>Please be careful</b>: this removes all plugin-specific settings (and reviews if 'Remove all reviews' checkbox is set)</label>
                        </div>
                        <div class="wp-review-field-option">
                            <input type="submit" value="Delete the plugin" name="reset_all" onclick="return confirm('Are you sure you want to reset all plugin data' + (window.reset_db.checked ? ' including reviews' : '') + '?')" class="button" />
                            <br><br>
                            <label>
                                <input type="checkbox" id="reset_db" name="reset_db"> Remove all reviews
                            </label>
                        </div>
                    </div>
                    <div id="debug_info" class="rplg-field">
                        <div class="rplg-field-label">
                            <label>DEBUG INFORMATION</label>
                        </div>
                        <div class="wp-review-field-option">
                            <input type="button" value="Copy Debug Information" name="reset_all" onclick="window.rplg_debug_info.select();document.execCommand('copy');window.rplg_debug_msg.innerHTML='Debug Information copied, please paste it to your email to support';" class="button" />
                            <textarea id="rplg_debug_info" style="display:block;width:30em;height:100px;margin-top:10px" onclick="window.rplg_debug_info.select();document.execCommand('copy');window.rplg_debug_msg.innerHTML='Debug Information copied, please paste it to your email to support';" readonly><?php rplg_debug(YRW_VERSION, yrw_options(), 'widget_yrw_widget'); yrw_debug(); ?></textarea>
                            <p id="rplg_debug_msg"></p>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>

</div>
<script type="text/javascript">
jQuery(document).ready(function($) {
    $('a.nav-tab').on('click', function(e)  {
        var $this = $(this), activeId = $this.attr('href');
        $(activeId).show().siblings('.tab-content').hide();
        $this.addClass('nav-tab-active').siblings().removeClass('nav-tab-active');
        e.preventDefault();
    });

    var el = document.body.querySelector('.widget-content'),
        elms = '.widget-content input[type="text"][name],' +
               '.widget-content input[type="hidden"][name],' +
               '.widget-content input[type="checkbox"][name]';

    $(elms).change(function() {
        if (!this.getAttribute('name')) return;
        if (!el.querySelector('.yrw-business-id').value) return;

        var args = '',
            ctrls = el.querySelectorAll(elms);
        for (var i = 0; i < ctrls.length; i++) {
            var ctrl = ctrls[i],
                match = ctrl.getAttribute('name').match(/\[\]\[(.*?)\]/);
            if (match && match.length > 1) {
                var name = match[1];
                if (ctrl.type == 'checkbox') {
                    if (ctrl.checked) args += ' ' + name + '=true';
                } else {
                    if (ctrl.value) args += ' ' + name + '=' + '"' + ctrl.value + '"';
                }
            }
        }
        window.rplg_shortcode.value = '[yrw' + args + ']';
    });
});
</script>