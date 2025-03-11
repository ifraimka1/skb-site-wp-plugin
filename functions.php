<?php

// Отключает стили темы TwentyTwentyFour в Wordpress
add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('wp-block-site-logo');
    wp_dequeue_style('wp-block-site-title');
    wp_dequeue_style('wp-block-group');
    wp_dequeue_style('wp-block-page-list');
    wp_dequeue_style('wp-block-navigation');
    wp_dequeue_style('wp-block-template-part');
    wp_dequeue_style('wp-block-heading');
    wp_dequeue_style('wp-block-spacer');
    wp_dequeue_style('wp-block-paragraph');
    wp_dequeue_style('wp-block-button');
    wp_dequeue_style('twentytwentyfour-button-style-outline');
    wp_dequeue_style('wp-block-buttons');
    wp_dequeue_style('wp-block-image');
    wp_dequeue_style('wp-block-pattern');
    wp_dequeue_style('wp-block-column');
    wp_dequeue_style('wp-block-columns');
    wp_dequeue_style('wp-block-list-item');
    wp_dequeue_style('wp-block-list');
    wp_dequeue_style('wp-block-separator');
    wp_dequeue_style('wp-block-post-title');
    wp_dequeue_style('wp-block-post-date');
    wp_dequeue_style('wp-block-post-author-name');
    wp_dequeue_style('wp-block-post-terms');
    wp_dequeue_style('wp-block-post-template');
    wp_dequeue_style('wp-block-query-pagination-previous');
    wp_dequeue_style('wp-block-query-pagination-numbers');
    wp_dequeue_style('wp-block-query-pagination-next');
    wp_dequeue_style('wp-block-query-pagination');
    wp_dequeue_style('wp-block-query-no-results');
    wp_dequeue_style('wp-block-site-tagline');
    wp_dequeue_style('wp-block-navigation-link');
    wp_dequeue_style('block-style-variation-styles');
    //wp_dequeue_style('wp-emoji-styles');
    wp_dequeue_style('global-styles');
    wp_dequeue_style('core-block-supports');
    wp_dequeue_style('wp-block-template-skip-link');
    wp_dequeue_style('wp-mail-smtp-admin-bar');
}, 999);

//add_action( 'wp_print_scripts', 'true_inspect_script_style' );
 
function true_inspect_script_style() {
 
	global $wp_scripts, $wp_styles;
 
	// не запускаем для неадминистраторов
	if( ! current_user_can( 'administrator' ) ) {
		return;
	}
 
	// не запускаем в админке, иначе мы в неё уже не попадём
	if( is_admin() ) {
		return;
	}

    $wall = require("includes/vk.php");
 
	// погнали
	wp_die(
	 	'
		<h1>Scripts</h1>
		<p>' . join( '<br>', $wp_scripts->queue ) . '</p>
		<h1>Styles</h1>
		<p>' . join( '<br>', $wp_styles->queue ) . '</p>
        <h1>Wall</h1>
        <pre><p>' . print_r($wall) . '</p></pre>
		'
	);
}