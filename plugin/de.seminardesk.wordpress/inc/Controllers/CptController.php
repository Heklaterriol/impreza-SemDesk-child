<?php
/**
 * @package SeminardeskPlugin
 */

namespace Inc\Controllers;

// exit if accessed directly
defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

use Inc\Utils\AdminUtils;

/**
 * Handles custom post types
 */
class CptController
{
	/**
	 * Code that runs to register the controller
	 *
	 * @return void
	 */
	public function register()
	{
		add_action( 'init', array( $this, 'create_cpts' ) );
		add_filter( 'document_title_parts', array( $this, 'modify_page_title') );
		add_filter( 'post_type_link', array( $this, 'override_permalink'), 10, 2 );
	}

	/**
	 * registers custom post types for the SeminarDesk plugin
	 * 
	 * @return void 
	 */
	public function create_cpts( )
	{
		foreach (SD_CPT as $key => $value){
			$name = ucfirst($value['name']);
			$names = ucfirst($value['names']);
			$title = ucfirst($value['title']);
			$name_lower = strtolower($value['name']);
			$names_lower = strtolower($value['names']);
			$show_ui = SD_OPTION_VALUE['debug'];
			$slug = AdminUtils::get_option_or_default( SD_OPTION['slugs'] , $value['slug_default'], $value['slug_option_key'] );

			/**
			 * array to configure labels for the CPT 
			 */
			$labels = array(
				'name'					=> $names,
				'singular_name'			=> $name,
				'name_admin_bar'		=> $name,
				'add_new'				=> __( 'Add New', 'seminardesk' ),
				'add_new_item'			=> __( 'Add New', 'seminardesk' ) . ' ' . $name,
				'new_item'				=> __( 'New', 'seminardesk' ) . ' ' . $name,
				'edit_item'				=> __( 'Edit', 'seminardesk' ) . ' ' . $name,
				'view_item'				=> __( 'View', 'seminardesk' ) . ' ' . $name,
				'view_items'			=> __( 'View', 'seminardesk' ) . ' ' . $names,
				// 'all_items'	=> __( 'All', 'seminardesk' ) . ' ' . $names,
				'all_items'				=> $title,
				'search_items'			=> __( 'Search', 'seminardesk' )  . ' ' . $names,
				'parent_item_colon'		=> __( 'Parent', 'seminardesk' ) . ' ' . $names . ':',
				'not_found'				=> __( 'No found' , 'seminardesk' ) . ' ' . $names_lower,
				'not_found_in_trash'	=> __( 'No found in Trash', 'seminardesk' ) . ' ' . $names_lower,
				'parent_item_colon'		=> __( 'Parent', 'seminardesk' ) . ' ' . $name,
				'archives'				=> $name . ' ' . __( 'Archives', 'seminardesk' ),
				'attributes'			=> $name . ' ' . __( 'Attributes', 'seminardesk' ),
				'insert_into_item'		=> __( 'Insert into', 'seminardesk' ) . ' ' . $name_lower,
				'uploaded_to_this_item'	=> __( 'Uploaded to this', 'seminardesk' ) . ' ' . $name_lower,
			);

			/**
			 * array to set rewrite rules for the CPT (sub CPT option)
			 */
			$rewrite = array(
				'slug'	=> $slug,
			);

			/**
			 * array to registers supported features for the CPT (sub CPT option)
			 */
			$supports = array(
				'title', 
				'editor', 
				'author', 
				'thumbnail', // enable featured image
				'excerpt', 
				'custom-fields', // enable support of Meta API
				'page-attributes', // template and parent, hierarchical must be true for parent option
				//'post-formats',
			);

			/**
			 * array to configure CPT options
			 */
			$cptOptions =  array(
				'labels'				=> $labels,
				'description'			=> $name . ' ' . __( 'post type for SeminarDesk.', 'seminardesk' ),
				'has_archive'			=> $value['has_archive'],
				'show_in_rest'			=> false,
				'public'				=> $value['public'],
				// 'exclude_from_search'	=> $value['exclude_from_search'], //  Default is the opposite value of $public.
				'show_ui'				=> $show_ui,
				'show_in_menu'			=> SD_ADMIN['page'], // add post type to the seminardesk menu
				'menu_position'			=> $value['menu_position'],
				'supports'				=> $supports,
				'rewrite'				=> $rewrite,
				'taxonomies'			=> $value['taxonomies'],
				'hierarchical'			=> $value['hierarchical'],
			);

			register_post_type( $key, $cptOptions ); 

			// for debugging custom post type features... expensive operation. should usually only be called when activate and deactivate the plugin
			// flush_rewrite_rules();
		}
	}

	/**
	 * modifies the page title
	 * 
	 * @param array $title_parts the components of the page title.
	 * @return array 
	 */
	public function modify_page_title( $title_parts )
	{
		$post_type = get_query_var( 'post_type' );
		if ( get_query_var( 'post_type' ) === 'sd_cpt_date' ){
			$term_object = get_query_var( 'term_object' );
			$title_parts['title'] = ucfirst(str_replace( array( '-', '_'), ' ', $term_object->slug));
		}
		if ( is_tax() ){
			$queried_object = get_queried_object();
			if ( $queried_object->taxonomy === 'sd_txn_labels'){
				$title_parts['title'] = ucfirst($queried_object->description);
			}
		}
		return $title_parts;
	}

	/**
	 * Rewrites the permalink of a post
	 * 
	 * @param string $post_link The default post's permalink.
	 * @param WP_Post $post The post in question.
	 * @return string The new post's permalink
	 */
	public function override_permalink( $post_link, $post )
	{
		// rewrites permalink of event date to point to its event
		if ( $post->post_type === 'sd_cpt_date' ){
			$custom_link = get_permalink( $post->wp_event_id );
			return $custom_link;
		}

		return $post_link;
	}
}