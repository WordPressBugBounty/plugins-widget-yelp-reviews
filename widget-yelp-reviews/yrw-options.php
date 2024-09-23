<?php if (isset($business_id)) { ?>
<div class="form-group">
    <input type="text" id="<?php echo $this->get_field_id('business_id'); ?>" name="<?php echo $this->get_field_name('business_id'); ?>" value="<?php echo $business_id; ?>" class="yrw-business-id" placeholder="<?php echo yrw_i('Business ID'); ?>" readonly />
</div>
<?php } ?>

<?php if (isset($title)) { ?>
<div class="form-group">
    <input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $title; ?>" placeholder="<?php echo yrw_i('Widget title'); ?>" />
</div>
<?php } ?>

<div class="form-group">
    <label><?php echo yrw_i('Pagination'); ?></label>
    <input type="text" id="<?php echo $this->get_field_id('pagination'); ?>" name="<?php echo $this->get_field_name('pagination'); ?>" value="<?php echo $pagination; ?>"/>
</div>

<div class="form-group">
    <label><?php echo yrw_i('Characters before \'read more\' link'); ?></label>
    <input type="text" id="<?php echo $this->get_field_id('text_size'); ?>" name="<?php echo $this->get_field_name('text_size'); ?>" value="<?php echo $text_size; ?>"/>
</div>

<div class="form-group">
    <label for="<?php echo $this->get_field_id('max_width'); ?>"><?php echo yrw_i('Widget width'); ?></label>
    <input id="<?php echo $this->get_field_id('max_width'); ?>" name="<?php echo $this->get_field_name('max_width'); ?>" value="<?php echo $max_width; ?>" type="text" />
</div>

<div class="form-group">
    <label for="<?php echo $this->get_field_id('max_height'); ?>"><?php echo yrw_i('Widget height'); ?></label>
    <input id="<?php echo $this->get_field_id('max_height'); ?>" name="<?php echo $this->get_field_name('max_height'); ?>" value="<?php echo $max_height; ?>" type="text" />
</div>

<div class="form-group">
    <label>
        <input id="<?php echo $this->get_field_id('refresh_reviews'); ?>" name="<?php echo $this->get_field_name('refresh_reviews'); ?>" type="checkbox" value="1" <?php checked('1', $refresh_reviews); ?>/>
        <?php echo yrw_i('Refresh reviews'); ?>
        <span class="rplg-quest rplg-toggle" title="Click to help">?</span>
        <div style="display:none">
            The plugin uses the Yelp API to get your reviews. <b>The API only returns the 3 most helpful reviews (it's a limitation of Yelp, not the plugin)</b>. This option calls the Yelp API once a day to check for a new reviews and if there are, adds to the plugin.<br><br>Also if you see the new reviews on Yelp, but after some time it's not added to the plugin, it means that Yelp does not include these reviews to the API and the plugin can't get this.
        </div>
    </label>
</div>

<div class="form-group">
    <label>
        <input id="<?php echo $this->get_field_id('read_on_yelp'); ?>" name="<?php echo $this->get_field_name('read_on_yelp'); ?>" type="checkbox" value="1" <?php checked('1', $read_on_yelp); ?>/>
        <?php echo yrw_i('Add \'read more\' link to Yelp after ellipsis'); ?>
    </label>
</div>

<div class="form-group">
    <label>
        <input id="<?php echo $this->get_field_id('centered'); ?>" name="<?php echo $this->get_field_name('centered'); ?>" type="checkbox" value="1" <?php checked('1', $centered); ?>/>
        <?php echo yrw_i('Place by center (only if Width is set)'); ?>
    </label>
</div>

<div class="form-group">
    <label>
        <input id="<?php echo $this->get_field_id('dark_theme'); ?>" name="<?php echo $this->get_field_name('dark_theme'); ?>" type="checkbox" value="1" <?php checked('1', $dark_theme); ?>/>
        <?php echo yrw_i('Dark background'); ?>
    </label>
</div>

<div class="form-group">
    <label>
        <input id="<?php echo $this->get_field_id('open_link'); ?>" name="<?php echo $this->get_field_name('open_link'); ?>" type="checkbox" value="1" <?php checked('1', $open_link); ?>/>
        <?php echo yrw_i('Open links in new Window'); ?>
    </label>
</div>

<div class="form-group">
    <label>
        <input id="<?php echo $this->get_field_id('nofollow_link'); ?>" name="<?php echo $this->get_field_name('nofollow_link'); ?>" type="checkbox" value="1" <?php checked('1', $nofollow_link); ?>/>
        <?php echo yrw_i('Use no follow links'); ?>
    </label>
</div>

<div class="form-group">
    <div class="rplg-pro">
        <?php echo yrw_i('Try more features in the Business version: '); ?>
        <a href="https://richplugins.com/business-reviews-bundle-wordpress-plugin" target="_blank">
            <?php echo yrw_i('Upgrade to Business'); ?>
        </a>
    </div>
</div>