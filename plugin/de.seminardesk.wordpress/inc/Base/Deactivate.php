<?php
/**
 * @package SeminardeskPlugin
 */

namespace Inc\Base;

// exit if accessed directly
defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

/**
 * Callbacks for the plugin deactivation
 */
class Deactivate
{
	/**
	 * code that runs during plugin deactivation
	 *
	 * @return void
	 */
	public static function deactivate() 
	{
		flush_rewrite_rules();
	}
}