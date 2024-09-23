<?php
if (!defined('ABSPATH')) exit;

function yrw_page($business, $rating, $open_link, $nofollow_link) {
    ?>
    <div class="wp-yelp-left">
        <img src="<?php echo $business->photo; ?>" alt="<?php echo $business->name; ?>" width="50" height="50" title="<?php echo $business->name; ?>">
    </div>
    <div class="wp-yelp-right">
        <div class="wp-yelp-name">
            <?php echo yrw_anchor($business->url, '', '<span>' . $business->name . '</span>', $open_link, $nofollow_link); ?>
        </div>
        <div>
            <span class="wp-yelp-rating"><?php echo $rating; ?></span>
            <span class="wp-yelp-stars"><?php yrw_stars($rating); ?></span>
        </div>
        <div class="wp-yelp-powered">Based on <?php echo $business->review_count; ?> Reviews</div>
        <div class="wp-yelp-logo">
            <?php echo yrw_anchor($business->url, '', '<img src="' . YRW_PLUGIN_URL . '/static/img/yelp-logo.png" alt="Yelp logo" width="60" height="31" title="Yelp logo">', $open_link, $nofollow_link); ?>
        </div>
    </div>
    <?php
}

function yrw_page_reviews($reviews, $text_size, $pagination, $read_on_yelp, $open_link, $nofollow_link) {
    ?>
    <div class="wp-yelp-reviews">
    <?php
    $hr = false;
    if (count($reviews) > 0) {
        $i = 0;
        foreach ($reviews as $review) {
            if ($pagination > 0 && $pagination <= $i++) {
                $hr = true;
            }
        ?>
        <div class="wp-yelp-review<?php if ($hr) { ?> wp-yelp-hide<?php } ?>">
            <div class="wp-yelp-left">
                <?php
                if (strlen($review->author_img) > 0) {
                    $author_img = str_replace('o.jpg', 'ms.jpg', $review->author_img);
                } else {
                    $author_img = YRW_AVATAR;
                }
                ?>
                <img src="<?php echo $author_img; ?>" alt="<?php echo $review->author_name; ?>" width="50" height="50" title="<?php echo $review->author_name; ?>" onerror="if(this.src!='<?php echo YRW_AVATAR; ?>')this.src='<?php echo YRW_AVATAR; ?>';">
            </div>
            <div class="wp-yelp-right">
                <?php yrw_anchor($review->url, 'wp-yelp-name', $review->author_name, $open_link, $nofollow_link); ?>
                <div class="wp-yelp-time" data-time="<?php echo $review->time; ?>"><?php echo $review->time; ?></div>
                <div class="wp-yelp-feedback">
                    <span class="wp-yelp-stars"><?php echo yrw_stars($review->rating); ?></span>
                    <?php
                    if (isset($review->text)) {
                        $review_text = $review->text;
                        if ($read_on_yelp) {
                            $review_text .= " <a class=\"wp-yelp-link\" href=\"" . $review->url . "\" target=\"_blank\" rel=\"noopener\">" . yrw_i('read more') . "</a>";
                        }
                    ?>
                    <span class="wp-yelp-text"><?php echo yrw_trim_text($review_text, $text_size); ?></span>
                    <?php } ?>
                </div>
            </div>
        </div>
        <?php
        }
    }
    ?>
    </div>
    <?php if ($pagination > 0 && $hr) { ?>
    <a class="wp-yelp-url" href="#" onclick="return rplg_next_reviews.call(this, 'yelp', <?php echo $pagination; ?>);">
        <?php echo yrw_i('Next Reviews'); ?>
    </a>
    <?php }
}

function yrw_stars($rating) {
    ?><span class="wp-stars"><svg class="yrw-rating yrw-rating-<?php echo $rating * 10; ?>" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 865 145" width="865" height="145"><defs><linearGradient id="yrw-rating-gradient-0" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" stop-color="#CCCCCC"/><stop offset="100%" stop-color="#CCCCCC"/></linearGradient><linearGradient id="yrw-rating-gradient-1" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" stop-color="#F2BD79"/><stop offset="100%" stop-color="#F2BD79"/></linearGradient><linearGradient id="yrw-rating-gradient-2" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" stop-color="#FEC011"/><stop offset="100%" stop-color="#FEC011"/></linearGradient><linearGradient id="yrw-rating-gradient-3" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" stop-color="#FF9242"/><stop offset="100%" stop-color="#FF9242"/></linearGradient><linearGradient id="yrw-rating-gradient-4" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" stop-color="#F15C4F"/><stop offset="100%" stop-color="#F15C4F"/></linearGradient><linearGradient id="yrw-rating-gradient-5" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" stop-color="#D32323"/><stop offset="100%" stop-color="#D32323"/></linearGradient></defs><path class="yrw-stars-1f" d="M110.6 0h-76.9c-18.6 0-33.7 15.1-33.7 33.7v76.9c0 18.6 15.1 33.7 33.7 33.7h76.9c18.6 0 33.7-15.1 33.7-33.7v-76.9c0-18.6-15.1-33.7-33.7-33.7z"/><path class="yrw-stars-0h" d="M33.3,0.3C14.7,0.3-0.4,15.4-0.4,34V111c0,18.6,15.1,33.7,33.7,33.7h38.3V0.3H33.3z"/><path class="yrw-stars-2f" d="M290.6 0h-76.9c-18.6 0-33.7 15.1-33.7 33.7v76.9c0 18.6 15.1 33.7 33.7 33.7h76.9c18.6 0 33.7-15.1 33.7-33.7v-76.9c0-18.6-15.1-33.7-33.7-33.7z"/><path class="yrw-stars-1h" d="M214,0.3c-18.6,0-33.7,15.1-33.7,33.7v77c0,18.6,15.1,33.7,33.7,33.7h38.3V0.3H214z"/><path class="yrw-stars-3f" d="M470.4 0h-76.9c-18.6 0-33.7 15.1-33.7 33.7v76.9c0 18.6 15.1 33.7 33.7 33.7h76.9c18.6 0 33.7-15.1 33.7-33.7v-76.9c.1-18.6-15.1-33.7-33.7-33.7z"/><path class="yrw-stars-2h" d="M393.9,0.6c-18.6,0-33.7,15.1-33.7,33.7v77c0,18.6,15.1,33.7,33.7,33.7h38.3V0.6H393.9z"/><path class="yrw-stars-4f" d="M650.6 0h-76.9c-18.6 0-33.7 15.1-33.7 33.7v76.9c0 18.6 15.1 33.7 33.7 33.7h76.9c18.6 0 33.7-15.1 33.7-33.7v-76.9c0-18.6-15.1-33.7-33.7-33.7z"/><path class="yrw-stars-3h" d="M573.9 0c-18.6 0-33.7 15.1-33.7 33.7v77c0 18.6 15.1 33.7 33.7 33.7h38.3v-144.4h-38.3z"/><path class="yrw-stars-5f" d="M830.6 0h-76.9c-18.6 0-33.7 15.1-33.7 33.7v76.9c0 18.6 15.1 33.7 33.7 33.7h76.9c18.6 0 33.7-15.1 33.7-33.7v-76.9c0-18.6-15.1-33.7-33.7-33.7z"/><path class="yrw-stars-4h" d="M753.8 0c-18.6 0-33.7 15.1-33.7 33.7v77c0 18.6 15.1 33.7 33.7 33.7h38.3v-144.4h-38.3z"/><path class="yrw-stars" fill="#FFF" stroke="#FFF" stroke-width="2" stroke-linejoin="round" d="M72 19.3l13.6 35.4 37.9 2-29.5 23.9 9.8 36.6-31.8-20.6-31.8 20.6 9.8-36.6-29.5-23.9 37.9-2zm180.2 0l13.6 35.4 37.8 2-29.4 23.9 9.8 36.6-31.8-20.6-31.9 20.6 9.8-36.6-29.4-23.9 37.8-2zm179.8 0l13.6 35.4 37.9 2-29.5 23.9 9.8 36.6-31.8-20.6-31.8 20.6 9.8-36.6-29.5-23.9 37.9-2zm180.2 0l13.6 35.4 37.8 2-29.4 23.9 9.8 36.6-31.8-20.6-31.9 20.6 9.8-36.6-29.4-23.9 37.8-2zm180 0l13.6 35.4 37.8 2-29.4 23.9 9.8 36.6-31.8-20.6-31.9 20.6 9.8-36.6-29.4-23.9 37.8-2z"/></svg></span><?php
}

function yrw_rstrpos($haystack, $needle, $offset) {
    $size = strlen ($haystack);
    $pos = strpos (strrev($haystack), $needle, $size - $offset);

    if ($pos === false)
        return false;

    return $size - $pos;
}

function yrw_trim_text($text, $size) {
    if ($size > 0 && strlen($text) > $size) {
        $visible_text = $text;
        $invisible_text = '';
        $idx = yrw_rstrpos($text, ' ', $size);
        if ($idx < 1) {
            $idx = $size;
        }
        if ($idx > 0) {
            $visible_text = substr($text, 0, $idx - 1);
            $invisible_text = substr($text, $idx - 1, strlen($text));
        }
        echo $visible_text;
        if (strlen($invisible_text) > 0) {
            ?><span>... </span><span class="wp-more"><?php echo $invisible_text; ?></span><span class="wp-more-toggle"><?php echo yrw_i('read more'); ?></span><?php
        }
    } else {
        echo $text;
    }
}

function yrw_anchor($url, $class, $text, $open_link, $nofollow_link) {
    echo '<a href="' . $url . '"' . ($class ? ' class="' . $class . '"' : '') . ($open_link ? ' target="_blank"' : '') . ' rel="' . ($nofollow_link ? 'nofollow ' : '') . 'noopener">' . $text . '</a>';
}
?>