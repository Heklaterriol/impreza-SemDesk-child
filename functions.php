<?php

/**
 * Theme functions and definitions.
 * 
 * @package HelloIVY
 */

defined( 'ABSPATH') or die('not allowed to access this file' );

/**
 * includes here
 */
require_once( get_stylesheet_directory() . '/inc/shortcodes.php' );
require_once( get_stylesheet_directory() . '/inc/utils.php' );

/**
 * constant variables here ...
 */

define(
	'IVY_STRINGS',
	array(
		'lang'			=> 'de',
		'online'		=> 'online',
		'onsite'		=> 'vor Ort',
		'current'		=> 'aktuell',
		'upcoming'	=> 'kommende',
		'today'			=> 'heute',
		'noresult'	=> 'Kein Resultat',
    'status'    => [
      'available' => 'Plätze frei',
      'canceled' => 'Abgesagt',
      'fully_booked' => 'Ausgebucht',
      'limited' => 'Fast ausgebucht',
      'wait_list' => 'Warteliste',
    ],
	)
);

/**
 * enqueues here...
 */

/**
 * Load hello child theme css and scripts
 * @return void 
 */
function ivy_hello_enqueue_scripts()
{
	// child theme style.css
	wp_enqueue_style( 'ivy-hello-child-style', get_stylesheet_uri(), array( 'ht-theme-style', 'ht-kb' ), filemtime( get_stylesheet_directory() . '/style.css' ), 'all');
	// custom registers
	$shortcode_agenda_css_src = '/assets/shortcode-agenda.css';
	$shortcode_agenda_js_src = '/assets/shortcode-agenda.js';
//	$shortcode_agenda_filter_js_src = '/assets/shortcode-agenda-filter.js';
	wp_register_style( 'ivy-shortcode-agenda-css', get_stylesheet_directory_uri() . $shortcode_agenda_css_src, array(), filemtime( get_stylesheet_directory() . $shortcode_agenda_css_src ), 'all' );
	wp_register_script( 'ivy-shortcode-agenda-js', get_stylesheet_directory_uri() . $shortcode_agenda_js_src, array(), filemtime( get_stylesheet_directory() . $shortcode_agenda_js_src ), true );
//	wp_register_script( 'ivy-shortcode-agenda-filter-js', get_stylesheet_directory_uri() . $shortcode_agenda_filter_js_src, array(), filemtime( get_stylesheet_directory() . $shortcode_agenda_filter_js_src ), true );
	wp_register_style( 'ivy-sidebar-agenda.css', get_stylesheet_directory_uri() . '/assets/sidebar-agenda.css', array(), filemtime(get_stylesheet_directory() . '/assets/sidebar-agenda.css' ) );
	wp_register_script( 'ivy-sidebar-agenda.js', get_stylesheet_directory_uri() . '/assets/sidebar-agenda.js', array(), filemtime(get_stylesheet_directory() . '/assets/sidebar-agenda.js' ), true );

	global $post;
	if( has_shortcode( $post->post_content, 'sd-widget-agenda' ) || has_shortcode( $post->post_content, 'sd-widget-agenda-mini' ) || has_shortcode( $post->post_content, 'sd-widget-agenda-flex' ) ){ // enqueue for shortcode [sd-widget-agenda]
		wp_enqueue_style( 'ivy-shortcode-agenda-css' );
		wp_enqueue_script( 'ivy-shortcode-agenda-js' );
	}
}
add_action( 'wp_enqueue_scripts', 'ivy_hello_enqueue_scripts', 20 );

// enable support shortcodes in widgets
add_filter( 'widget_text', 'shortcode_unautop' );
add_filter( 'widget_text', 'do_shortcode' );
add_action( 'widgets_init', 'ivy_register_custom_sidebar' );

// override for agenda page
$lang_code = defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : '';
if( $lang_code === IVY_STRINGS['lang'] ) {
	add_filter('template_include', 'ivy_override_template', 20);
	add_filter('template_include', 'ivy_override_query', 20);
}

/**
 * Start - Optimization part
 */

/**
 * Disable the emoji library from WordPress
 * @return void 
 */
function hello_sd_disable_emojis()
{
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'tiny_mce_plugins', 'hello_sd_disable_emojis_tinymce' );
	add_filter( 'wp_resource_hints', 'hello_sd_disable_emojis_remove_dns_prefetch', 10, 2 );
}
add_action( 'init', 'hello_sd_disable_emojis' );

/**
 * Filter function used to remove the tinymce emoji plugin.
 * 
 * @param array $plugins 
 * @return array Difference between the two arrays
 */
function hello_sd_disable_emojis_tinymce( $plugins )
{
	if (is_array( $plugins )) {
		return array_diff($plugins, array( 'wpemoji' ));
	} else {
		return array();
	}
}

/**
 * Remove emoji CDN hostname from DNS pre-fetching hints.
 *
 * @param array $urls URLs to print for resource hints.
 * @param string $relation_type The relation type the URLs are printed for.
 * @return array Difference between the two arrays.
 */
function hello_sd_disable_emojis_remove_dns_prefetch( $urls, $relation_type )
{
	if ( 'dns-prefetch' == $relation_type ) {
		/** This filter is documented in wp-includes/formatting.php */
		$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );
		$urls = array_diff( $urls, array( $emoji_svg_url ) );
	}
	return $urls;
}

/**
 * Disable xmlrpc.php
 */
add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * Start - Custom functions by alexis
 */

function hello_ivy_disable_page_title( $return )
{
	return false;
}
add_filter( 'hello_elementor_page_title', 'hello_ivy_disable_page_title' );


// if (function_exists( "register_sidebar" )) {
// 	register_sidebar();
// }
function hello_ivy_js_before_do_header()
{
	echo '<div class="site-container">';
}
add_action( 'elementor/theme/before_do_header', 'hello_ivy_js_before_do_header' );

function hello_ivy_js_after_do_footer()
{
	echo '</div><!-- .site-container -->';
}
add_action(  'elementor/theme/after_do_footer', 'hello_ivy_js_after_do_footer' );
/**
 * End - Custom functions 	by alexis
 */
