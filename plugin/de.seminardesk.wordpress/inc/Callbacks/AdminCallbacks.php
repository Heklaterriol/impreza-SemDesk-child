<?php 
/**
 * @package SeminardeskPlugin
 */
namespace Inc\Callbacks;

// exit if accessed directly
defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

/**
 * Callbacks for admin page
 */
class AdminCallbacks
{
	/**
	 * Calls the general admin page for SeminarDesk.
	 *
	 * @return void
	 */
	public function adminGeneral()
	{
		return require_once( SD_ENV['path'] . '/admin/admin.php' );
	}
}