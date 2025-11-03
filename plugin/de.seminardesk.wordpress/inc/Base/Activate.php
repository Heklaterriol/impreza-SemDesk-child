<?php
/**
 * @package SeminardeskPlugin
 */

namespace Inc\Base;

// exit if accessed directly
defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

use Inc\Controllers\CptController;
use Inc\Controllers\TaxonomyController;

/**
 * Callbacks for the plugin activation
 */
final class Activate
{
	/**
	 * code that runs during plugin activation
	 *
	 * @return void
	 */
	public static function activate() 
	{
		// create CPTs, Taxonomies, check Terms and rewrite rules/permalinks for slugs
		$cpt_ctrl = new CptController();
		$cpt_ctrl->create_cpts();
		$txn_ctrl = new TaxonomyController();
		$txn_ctrl->create_taxonomies();
		$txn_ctrl->custom_rewrite_rules();

		flush_rewrite_rules();
	}
}