<?php
/**
 * @package SeminardeskPlugin
 */

namespace Inc\Base;

// exit if accessed directly
defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

use Inc\Controllers;

// thread class es as service
/**
 * inits controller classes as services
 */
final class Init
{
	/**
	 * Stores all the service classes inside an array
	 *
	 * @return array    full list of classes as a service
	 */
	public static function get_services() 
	{
		return array(
			new \Inc\Controllers\BasicAuthController(),
			new \Inc\Controllers\AdminController(),
			new \Inc\Controllers\SettingsLinksController(),
			new \Inc\Controllers\RestController(),
			new \Inc\Controllers\TemplateController(),
			new \Inc\Controllers\TaxonomyController(), // Note: for slug overloading to work the TaxonomyController needs to be initialized before CptController
			new \Inc\Controllers\CptController(),
			new \Inc\Controllers\QueryController(),
		);
	}

	/**
	 * Loops through the classes, initialize them and call the register() method if it exists
	 *
	 * @return void
	 */
	public static function register_services() 
	{
		foreach (self::get_services() as $service) {
			if ( method_exists( $service, 'register') ) {
				$service->register();
			}
		}
	}
 }