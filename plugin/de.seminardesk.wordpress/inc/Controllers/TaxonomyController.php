<?php
/**
 * @package SeminardeskPlugin
 */

namespace Inc\Controllers;

// exit if accessed directly
defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

use Inc\Utils\AdminUtils;

/**
 * Handles the taxonomies
 * 
 * Note:
 * - Show/Edit Taxonomy http://localhost/wpsdp/wp-admin/edit-tags.php?taxonomy=sd_txn_dates
 */

class TaxonomyController
{
	/**
	 * Code that runs to register the controller
	 *
	 * @return void
	 */
	public function register()
	{
		add_action( 'init', array($this, 'create_taxonomies') );
		add_action( 'init', array($this, 'custom_rewrite_rules') );
	}

	/**
	 * registers custom taxonomies for the SeminarDesk plugin
	 * 
	 * @return void
	 */
	public function create_taxonomies()
	{
		foreach (SD_TXN as $txn => $txn_value){
			$name = ucfirst($txn_value['name']);
			$names = ucfirst($txn_value['names']);
			$name_lower = strtolower($txn_value['name']);
			$names_lower = strtolower($txn_value['names']);
			$show_ui = SD_OPTION_VALUE['debug'];
			$slug = AdminUtils::get_option_or_default( SD_OPTION['slugs'], $txn_value['slug_default'], $txn_value['slug_option_key'] );

			// Add new taxonomy, make it hierarchical (like categories)
			$labels = array(
				'name'				=> $names,
				'singular_name'		=> $name,
				'search_items'		=> __( 'Search', 'seminardesk' ) . ' ' . $names,
				'all_items'			=> __( 'All', 'seminardesk' ) . ' ' .  $names,
				'parent_item'		=> __( 'Parent', 'seminardesk' ) . ' '  . $name,
				'parent_item_colon'	=> __( 'Parent', 'seminardesk' ) . ' ' . $name . ':',
				'edit_item'			=> __( 'Edit', 'seminardesk' ) . ' ' . $name,
				'update_item'		=> __( 'Update', 'seminardesk' ) . ' ' . $name,
				'add_new_item'		=> __( 'Add New', 'seminardesk' ) . ' ' . $name,
				'new_item_name'		=> __( 'New Name', 'seminardesk' ) . ' ' . $name,
				'menu_name'			=> $names,
				'back_to_items'		=> $names,
				'not_found'			=> $names,
			);
		
			$args = array(
				'labels'			=> $labels,
				'public'			=> $txn_value['public'],
				'show_ui'			=> $show_ui,
				'show_in_nav_menus'	=> $txn_value['show_in_nav_menus'] ?? $txn_value['public'],
				'show_admin_column'	=> true,
				'query_var'			=> true,
				'show_in_rest'		=> false,
				'hierarchical'		=> true,
				'rewrite'			=> array( 
					'slug'			=> $slug, 
					'hierarchical'	=> $txn_value['hierarchical'],
					// 'with_front'	> true, 
				),
			);

			register_taxonomy( $txn, $txn_value['object_types'], $args );

			// create SeminarDesk's static terms for each taxonomy
			foreach (SD_TXN_TERM as $term => $term_value){
				if ( $term_value['taxonomy'] === $txn ){
					$term_slug = AdminUtils::get_option_or_default( SD_OPTION['slugs'], SD_TXN_TERM[$term]['slug_default'], SD_TXN_TERM[$term]['slug_option_key'] );
					$term_ids = $this->check_term_exists( $term, $txn, $term_value['title'], $term_slug);
				}
			}

			// for debugging taxonomy features... expensive operation. should usually only be called when activate and deactivate the plugin
			// flush_rewrite_rules();
		}

	}

	/**
	 * Gets term id and create term if doesn't exist
	 * 
	 * @param string $term 
	 * @param string $txn 
	 * @param string $description 
	 * @param string $slug 
	 * @return array|WP_Error 
	 */
	public function check_term_exists( $term, $txn, $description, $slug )
	{
		$term_ids = term_exists( (string) $term, $txn );
		if ( !isset( $term_ids ) ) {
			$term_ids = wp_insert_term( $term, $txn, array(
				'description'	=> $description,
				'slug'			=> $slug,
			));
		}
		return $term_ids;
	}

	/**
	 * Adds custom rewrite rules for SeminarDesk's taxonomies
	 * 
	 * @return void 
	 */
	public function custom_rewrite_rules(){

		// global $wp_rewrite; // debugging
		
		// make link of sd_txn_dates point to term 'upcoming'
		$slug_txn_dates = AdminUtils::get_option_or_default( SD_OPTION['slugs'], SD_TXN['sd_txn_dates']['slug_default'], SD_TXN['sd_txn_dates']['slug_option_key'] );
		$slug_txn_dates_upcoming = AdminUtils::get_option_or_default( SD_OPTION['slugs'], SD_TXN_TERM['upcoming']['slug_default'], SD_TXN_TERM['upcoming']['slug_option_key'] );
		add_rewrite_rule( '^' .$slug_txn_dates . '/?$', 'index.php?sd_txn_dates=' . $slug_txn_dates_upcoming, 'top');
	}

	/**
	 * Updates slug of the terms
	 * 
	 * @return void 
	 */
	public function update_terms_slug()
	{
		foreach  (SD_TXN_TERM as $key => $value ) {
			$term = get_term_by('name', $key, $value['taxonomy'], ARRAY_A);
			$slug = AdminUtils::get_option_or_default( SD_OPTION['slugs'], $value['slug_default'],  $value['slug_option_key']);
			wp_update_term( $term['term_id'], $term['taxonomy'],  array( 'slug' => $slug ) );
		}
	}
}