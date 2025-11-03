<?php
/**
 * @package SeminardeskPlugin
 */

namespace Inc\Base;

// exit if accessed directly
defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

use Inc\Utils\AdminUtils as Utils;
use Inc\Utils\AdminUtils;

/**
 * Callbacks for the plugin uninstall
 */
class Uninstall
{
	/**
	 * code that runs during plugin uninstall
	 *
	 * @return void
	 */
	public function uninstall() 
	{
		// Clear all SeminarDesk entries form database
		$delete_all = SD_OPTION_VALUE['delete'];
		if ( $delete_all == true ) {
			AdminUtils::delete_all_sd_objects();
			AdminUtils::delete_all_sd_options();
		}
		flush_rewrite_rules();
	}
}