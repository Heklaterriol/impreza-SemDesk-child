<?php
/**
 * SeminarDesk for WordPress
 *
 * @link https://www.seminardesk.com/wordpress-plugin
 * @package SeminardeskPlugin
 */

/**
 * Plugin Name: SeminarDesk for WordPress
 * Plugin URI: https://www.seminardesk.com/wordpress-plugin
 * Description: Allows you to connect SeminarDesk to your WordPress site in order to create posts for events, dates and facilitators.
 * Version: 1.5.0
 * Requires at least: 5.2
 * Tested up to: 6.4
 * Requires PHP: 7.3
 * Author: SeminarDesk – Danker, Smaluhn & Tammen GbR
 * Author URI: https://www.seminardesk.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: seminardesk
 * Domain Path: /languages
 * Bitbucket Plugin URI: https://bitbucket.org/seminardesk/seminardesk-wordpress
 */

/**
 * Copyright 2020 by SeminarDesk and the contributors
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

// security check if plugin tricked by WordPress
defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

use Inc\Utils\AdminUtils;

// init composer autoload
if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
	require_once dirname( __FILE__ ) . '/vendor/autoload.php';
}

/**
 * global constant variables to define environment for the SeminarDesk plugin
 * 
 * @param array SD_ENV, SeminarDesk environment
 * @var string $['name'] folder name
 * @var string $['php'] php file name
 * @var string $['base'] base name 
 * @var string $['path'] filesystem directory path
 * @var string $['file'] filesystem directory path and filename
 * @var string $['url'] url directory path
 */
define( 'SD_ENV', array(
	'name'	=> basename( plugin_dir_path( __FILE__ ) ),
	'php'	=> basename( __FILE__),
	'dirname'	=> dirname( __FILE__ ),
	'base'	=> plugin_basename( __FILE__ ),
	'path'	=> plugin_dir_path( __FILE__ ),
	'file'	=> __FILE__,
	'url'	=> plugin_dir_url( __FILE__ ),
) );

/**
 * constant variables to define admin page
 */

define( 'SD_ADMIN', array(
	'page'						=> 'seminardesk_plugin',
	'position'				=> 65, // below Plugins
	'group_settings'	=> 'seminardesk_plugin_settings',
) );

/**
 * constant variables to define options
 */
define( 'SD_OPTION', array(
	'public_events' => 'seminardesk_public_events',
	'public_dates' => 'seminardesk_public_dates',
	'public_facilitators' => 'seminardesk_public_facilitators',
	'public_labels' => 'seminardesk_public_labels',
	'slugs' => 'seminardesk_slugs',
	'rest' => 'seminardesk_rest',
	'debug' => 'seminardesk_debug',
	'delete' => 'seminardesk_delete',
) );

define( 'SD_OPTION_DEFAULT', array(
	'public_events' => true,
	'public_dates' => true,
	'public_facilitators' => true,
	'public_labels' => true,
	'rest' => false,
	'debug' => false,
	'delete' => false,
) );

define( 'SD_OPTION_VALUE', array(
	'public_events' => (bool) get_option( SD_OPTION['public_events'], SD_OPTION_DEFAULT['public_events'] ),
	'public_dates' => (bool) get_option( SD_OPTION['public_dates'], SD_OPTION_DEFAULT['public_dates'] ),
	'public_facilitators' => (bool) get_option( SD_OPTION['public_facilitators'], SD_OPTION_DEFAULT['public_facilitators'] ),
	'public_labels' => (bool) get_option( SD_OPTION['public_labels'], SD_OPTION_DEFAULT['public_labels'] ),
	'rest' => (bool) get_option( SD_OPTION['rest'], SD_OPTION_DEFAULT['rest'] ),
	'debug' => (bool) get_option( SD_OPTION['debug'], SD_OPTION_DEFAULT['debug'] ),
	'delete' => (bool) get_option( SD_OPTION['delete'], SD_OPTION_DEFAULT['delete'] ),
) );

/**
 * constant variables to define custom post type
 */
define( 'SD_CPT', array(
	'sd_cpt_event' => array( // don't rename this key
		'name'					=> _x('Event', 'post type singular name', 'seminardesk'),
		'names'					=> _x('Events', 'post type general name', 'seminardesk'),
		'title'					=> _x('CPT Events', 'post type setting title', 'seminardesk'),
		'public'				=> SD_OPTION_VALUE['public_events'],
		// 'exclude_from_search'	=> false, //  Default is the opposite value of $public.
		'has_archive'			=> SD_OPTION_VALUE['public_events'],
		'menu_position'			=> 1, // position in submenu
		'taxonomies'			=> array( 
			'sd_txn_facilitators',
			'sd_txn_labels',
		),
		'hierarchical'			=> false,
		'slug_default'			=> 'events',
		'slug_option_key'		=> 'sd_slugs_cpt_events',
	),
	'sd_cpt_date'				=> array( // don't rename this key
		'name'					=> _x('Date', 'post type singular name', 'seminardesk'),
		'names'					=> _x('Dates', 'post type general name', 'seminardesk'),
		'title'					=> _x('CPT Dates', 'post type setting title', 'seminardesk'),
		'public'				=> false,
		// 'exclude_from_search'	=> true, //  Default is the opposite value of $public.
		'has_archive'			=> false,
		'menu_position'			=> 2,
		'taxonomies'			=> array( 
			'sd_txn_dates',
			'sd_txn_labels',
			'sd_txn_facilitators',
		),
		'hierarchical'			=> false,
		'slug_default'			=> 'dates',
		'slug_option_key'		=> 'sd_slugs_cpt_dates',
	),
	'sd_cpt_facilitator' => array( // don't rename this key
		'name'					=> _x('Facilitator', 'post type singular name', 'seminardesk'),
		'names'					=> _x('Facilitators', 'post type general name','seminardesk'),
		'title'					=> _x('CPT Facilitators', 'post type setting title', 'seminardesk'),
		'public'				=> SD_OPTION_VALUE['public_facilitators'],
		// 'exclude_from_search'	=> false, //  Default is the opposite value of $public.
		'has_archive'			=> SD_OPTION_VALUE['public_facilitators'],
		'menu_position'			=> 3,
		'taxonomies'			=> array( 
			'sd_txn_labels', 
		),
		'hierarchical'			=> false,
		'slug_default'			=> 'facilitators',
		'slug_option_key'		=> 'sd_slugs_cpt_facilitator',
	),
	'sd_cpt_label' => array( // don't rename this key
		'name'					=> _x('Label', 'post type singular name', 'seminardesk'),
		'names'					=> _x('Labels', 'post type general name', 'seminardesk'),
		'title'					=> _x('CPT Labels', 'post type setting title', 'seminardesk'),
		'public'				=> SD_OPTION_VALUE['public_labels'],
		// 'exclude_from_search'	=> false, //  Default is the opposite value of $public.
		'has_archive'			=> SD_OPTION_VALUE['public_labels'],
		'menu_position'			=> 4,
		'taxonomies'			=> array( 
			'sd_txn_labels', 
		),
		'hierarchical'			=> false,
		'slug_default'			=> 'labels',
		'slug_option_key'		=> 'sd_slugs_cpt_label',
	),
) );

/**
 * Constant variables define custom taxonomies
 * 
 * Note: WP user for Webhooks needs capability to modify taxonomies
 */
define( 'SD_TXN', array(
	'sd_txn_dates' => array( // don't rename this key
		'name'				=> _x('Date', 'taxonomy singular name', 'seminardesk'),
		'names'				=> _x('Dates', 'taxonomy general name', 'seminardesk'),
		'title'				=> _x('TXN Dates', 'taxonomy setting title', 'seminardesk'),
		'public'			=> SD_OPTION_VALUE['public_dates'],
		'hierarchical'		=> false,
		'menu_position'		=> 5,
		'object_types'		=> array( 
			'sd_cpt_date' 
		),
		'slug_overloading'	=> false,
		'slug_default'		=> 'schedule',
		'slug_option_key'	=> 'sd_slug_txn_dates',
	),
	'sd_txn_facilitators' => array( // don't rename this key
		'name'				=> _x('Facilitator', 'taxonomy singular name', 'seminardesk'),
		'names'				=> _x('Facilitators', 'taxonomy general name', 'seminardesk'),
		'title'				=> _x('TXN Facilitator', 'taxonomy setting title', 'seminardesk'),
		'public'			=> false,
		'hierarchical'		=> false,
		'menu_position'		=> 6,
		'object_types'		=> array(
			'sd_cpt_event',
			'sd_cpt_date'
		),
		'slug_overloading' 	=> false,
		'slug_default'		=> 'sd_txn_facilitators',
		'slug_option_key'	=> 'sd_slug_txn_facilitators',
	),
	'sd_txn_labels' => array( // don't rename this key
		'name'				=> _x('Label', 'taxonomy singular name', 'seminardesk'),
		'names'				=> _x('Labels', 'taxonomy general name', 'seminardesk'),
		'title'				=> _x('TXN Labels', 'taxonomy setting title','seminardesk'),
		'public'			=> SD_OPTION_VALUE['public_labels'],
		'show_in_nav_menus'	=> false,
		'hierarchical'		=> true,
		'menu_position'		=> 7,
		'object_types'		=> array(
			'sd_cpt_facilitator',
			'sd_cpt_event',
			'sd_cpt_dates', 
		),
		'slug_overloading'	=> true, // slug overloading with sd_cpt_label
		'slug_default'		=> 'labels',
		'slug_option_key'	=> 'sd_slugs_cpt_label', 
	),
) );

/**
 * Constant variables define SeminarDesk's static terms for custom taxonomies
 */
define( 'SD_TXN_TERM', array(
	'upcoming' => array( // don't rename this key
		'title'				=> _x('Upcoming Events Dates', 'term title', 'seminardesk'),
		'taxonomy'			=> 'sd_txn_dates',
		'slug_default'		=> 'upcoming',
		'slug_option_key'	=> 'sd_slug_txn_dates_upcoming',
		'public'			=> SD_OPTION_VALUE['public_dates'],
	),
	'past' => array( // don't rename this key
		'title'				=> _x('Past Events Dates', 'term title', 'seminardesk'),
		'taxonomy'			=> 'sd_txn_dates',
		'slug_default'		=> 'past',
		'slug_option_key'	=> 'sd_slug_txn_dates_past',
		'public'			=> SD_OPTION_VALUE['public_dates'],
	),
) );

// activation hook for plugin
register_activation_hook( SD_ENV['file'], array( 'Inc\Base\Activate', 'activate' ) );

// deactivation hook for plugin
register_deactivation_hook( SD_ENV['file'], array( 'Inc\Base\Deactivate', 'deactivate' ) );

// uninstall hook for plugin
register_uninstall_hook( SD_ENV['file'], array( 'Inc\Base\Uninstall', 'uninstall' ) );

// register services utilizing the init class
if ( class_exists( 'Inc\\Base\\Init' ) ) {
	$services = new Inc\Base\Init();
	$services->register_services();
	// alternative method to register services
	// Inc\Base\Init::register_services();
}
