<?php
/**
 * @package SeminardeskPlugin
 */

namespace Inc\Controllers;

// exit if accessed directly
defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

/**
 * Handles settings link
 */
class SettingsLinksController
{
	/**
	 * Code that runs to register the controller
	 *
	 * @return void
	 */
	public function register() {
		// add settings link to the plugin menu
		add_filter( "plugin_action_links_" . SD_ENV['base'], array( $this, 'create_settings_link'));
	 }

	/**
	 * Adds custom settings link to plugin menu.
	 *
	 * @param array  $links
	 * @return array $links list of links
	 */
	public function create_settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=seminardesk_plugin">Settings</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
}