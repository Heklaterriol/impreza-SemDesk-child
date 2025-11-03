<?php
/**
 * @package SeminardeskPlugin
 */

namespace Inc\Controllers;

use Inc\Utils\AdminUtils;

// exit if accessed directly
defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

class TemplateController
{
	/**
	 * Code that runs to register the controller
	 * 
	 * @return void 
	 */
	public function register()
	{
		add_filter( 'template_include', array( $this, 'override_template'));
	}

	/**
	 * sets template file and enqueue its assets
	 * 
	 * @param array $templates list of template names sorted by priority
	 * @return string template path or empty string if not exists
	 */
	public static function set_template_enqueue_assets( $templates)
	{
		// list of possible locations of template and assets sorted by priority
		$locations = array (
			'sd-custom'	=> array(
				'dir'		=> dirname( SD_ENV['path'], 1 ) . '/' . 'seminardesk-custom/',
				'url'		=> WP_CONTENT_URL . '/plugins/' . 'seminardesk-custom/',
				// 'dir'	=> dirname( SD_ENV['path'], 1 ) . '/' . SD_ENV['name'] . '-custom/',
				// 'url'	=> WP_CONTENT_URL . '/plugins/' . SD_ENV['name'] . '-custom/',
				'template'	=> 'templates/',
				'assets'	=> 'assets/',
			),
			'theme'	=> array(
				'dir'		=> get_stylesheet_directory() . '/'. 'seminardesk-custom/',
				'url'		=> get_stylesheet_directory_uri() . '/' . 'seminardesk-custom/',
				'template'	=> 'templates/',
				'assets'	=> 'assets/',
			),
			'sd'	=> array(
				'dir'		=> SD_ENV['path'],
				'url'		=> SD_ENV['url'],
				'template'	=> 'templates/',
				'assets'	=> 'assets/',
			),
		);

		// check for template and its assets
		// return complete path of template and enqueue assets (scripts, styles)
		foreach ( $locations as $location ){
			if ( file_exists( $location['dir'] ) ){
				foreach ( $templates as $template ){
					$template_path = $location['dir'] . $location['template'] . $template . '.php';
					$asset_dir = $location['dir'] . $location['template'] . $location['assets'];
					if ( file_exists ($template_path ) ){
						if ( file_exists( $asset_dir ) ){
							$asset_url = $location['url'] . $location['template'] . $location['assets'];
							$style_file = $template . '.css';
							$script_file = $template . '.js';
							if ( file_exists( $asset_dir . $style_file ) ){
								
								wp_register_style( $style_file, $asset_url . $style_file, array(), filemtime( $asset_dir . $style_file ), 'all' );
								wp_enqueue_style( $style_file );
							}
							if ( file_exists( $asset_dir . $script_file ) ){
								wp_register_script( $script_file, $asset_url . $script_file, array(), filemtime( $asset_dir . $script_file ), true );
								wp_enqueue_script( $script_file );
								
							}
						}
						// Note: The result of the function file_exists() is cached. Cache can make your file_exists() behave unexpectedly, when create or delete custom template files. Use clearstatcache() to clear the cache.
						clearstatcache();
						return $template_path;
					}
				}
			}
		}
		// Note: The result of the function file_exists() is cached. Cache can make your file_exists() behave unexpectedly, when create or delete custom template files. Use clearstatcache() to clear the cache.
		clearstatcache();
		return '';
	}

	/**
	 * overrides templates to use SeminarDesk's template hierarchy
	 * 
	 * @param string $template current template path
	 * @return string new template path
	 */
	public function override_template( string $template )
	{
		// for taxonomies
		if ( is_tax() ){
			$queried_object = (array) get_queried_object();
			// only for SeminarDesk's taxonomies
			if ( strpos($queried_object['taxonomy'], 'sd_txn' ) !== false ){
				// overwrite taxonomy template for SeminarDesk's static terms (e.g. past, upcoming) or use default
				foreach ( SD_TXN_TERM as $term => $term_value ){
					if ( $term === $queried_object['name'] ){
						$templates = array( 
							$queried_object['taxonomy'] .  '-' . $queried_object['name'],
							'sd_txn', // fallback template
						);
						return $this->set_template_enqueue_assets( $templates );
					}
				}
				// template for all other terms of SeminarDesk's taxonomies
				$templates = array( 
					get_query_var( 'taxonomy' ),
					'sd_txn', // fallback template
				);
				return $this->set_template_enqueue_assets( $templates );
			}
		}
		
		// for custom post types and archive
		if ( is_single() === true ) {
			$post_type = get_post_type();
			// only for SeminarDesk's custom post types
			if ( strpos($post_type, 'sd_cpt' ) !== false)
			{
				$templates = array(
					$post_type,
					'sd_cpt' // fallback template
				);
				return $this->set_template_enqueue_assets( $templates );
			}
		}

		// for archive
		if ( is_archive() === true ){
			$post_type = get_post_type();
			// only for SeminarDesk's custom post types
			if ( strpos($post_type, 'sd_cpt' ) !== false)
			{
				$templates = array(
					'sd_archive',
					'sd_cpt' // fallback template
				);
				return $this->set_template_enqueue_assets( $templates );
			}
		}

		return $template;
	}
}