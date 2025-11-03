<?php
/**
 * @package SeminardeskPlugin
 */

namespace Inc\Controllers;

// exit if accessed directly
defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

use Inc\API\SettingsAPI;
use Inc\Callbacks\AdminCallbacks;
use Inc\Callbacks\ManagerCallbacks;

/**
 * Handles admin pages and sub pages
 */
class AdminController
{

	public $settings;
	
	public $callbacks;
	public $callbacks_mngr;

	public $pages = array();
	public $subpages = array();

	/**
	 * Code that runs to register the controller
	 *
	 * @return void
	 */
	public function register() 
	{
		$this->settings = new SettingsAPI(); 
		$this->callbacks = new AdminCallbacks();
		$this->callbacks_mngr = new ManagerCallbacks();
		
		$this->set_admin_pages();
		$this->set_admin_subpages();
		
		$this->set_settings();
		$this->set_sections();
		$this->set_fields();
		
		// generate admin section for seminardesk plugin
		$this->settings->add_pages( $this->pages )->with_sub_page( 'General' )->add_sub_pages( $this->subpages )->register();

		add_action( 'admin_enqueue_scripts', array ( $this, 'enqueue_assets' ) );

		// add entry to admin submenu of seminardesk
		add_filter( 'parent_file', array( $this, 'set_submenu' ) );

		// rewrite rule for custom slug of CPTs and TXNs, if add or update option
		add_action( 'add_option_' . SD_OPTION['slugs'], array( $this->callbacks_mngr, 'rewrite_slugs' ), 10, 2 );
		add_action( 'update_option_' . SD_OPTION['slugs'], array( $this->callbacks_mngr, 'rewrite_slugs' ), 10, 2 );
		// rewrite rule for custom slug of CPTs and TXNs, when switching theme
		add_action('after_switch_theme', array( $this->callbacks_mngr, 'rewrite_slugs' ), 10, 2 );
	}
	
	/**
	 * enqueues assets of the admin page
	 * 
	 * @return void 
	 */
	public function enqueue_assets()
	{
		if( is_admin() ){
			// wp_enqueue_script( 'sd-admin-script', SD_ENV['url'] . 'admin/sd-admin-script.js' );
			wp_enqueue_script( "sd-admin-script", SD_ENV['url'] . 'admin/sd-admin-script.js', array(), filemtime( SD_ENV['path'] . 'admin/sd-admin-script.js' ), true );
			// wp_enqueue_style( 'sd-admin-style', SD_ENV['url'] . 'admin/sd-admin-style.css' );
			wp_enqueue_style( 'sd-admin-style', SD_ENV['url'] . 'admin/sd-admin-style.css', array(), filemtime( SD_ENV['path'] . 'admin/sd-admin-style.css' ) );
		}
	}

	/**
	 * sets current menu to seminardesk_plugin to correctly highlight submenu items with custom parent menu/page.
	 * 
	 * @param string $parent_file 
	 * @return string 
	 */
	public function set_submenu( $parent_file )
	{
		// global $current_screen, $pagenow; // debugging
		// $screen = get_current_screen(); // debugging
		global $submenu_file;
		if (strpos($submenu_file, 'sd_') !== false) {
			$parent_file = SD_ADMIN['page'];
		}
		return $parent_file;
	}

	/**
	 * Adds SeminarDesk to the admin pages.
	 *
	 * @return void
	 */
	public function set_admin_pages() {
		// sd logo used as menu icon
		$sd_icon = 'data:image/svg+xml;base64,' . base64_encode(
			'<svg version="1.0" xmlns="http://www.w3.org/2000/svg"
			width="300.000000pt" height="300.000000pt" viewBox="0 0 300.000000 300.000000"
			preserveAspectRatio="xMidYMid meet">
		
			<g transform="translate(-120.000000,450.000000) scale(0.200000,-0.200000)"
			fill="black" stroke="none">
			<path d="M1768 1768 c-3 -151 -3 -153 -20 -121 -22 40 -71 57 -163 57 -159 0
			-275 -114 -292 -286 l-6 -65 -23 24 c-28 28 -85 53 -176 77 -36 10 -75 26 -87
			37 -38 36 -21 79 31 79 22 0 33 -8 46 -30 l17 -30 103 0 c102 0 103 0 97 23
			-38 126 -164 193 -318 168 -142 -24 -207 -85 -207 -196 0 -57 11 -84 50 -122
			33 -32 66 -47 186 -82 80 -23 97 -37 92 -77 -6 -56 -113 -47 -140 10 -11 25
			-13 26 -115 26 l-103 0 6 -27 c34 -142 212 -216 399 -168 80 21 132 57 154
			106 l18 40 18 -28 c60 -95 120 -127 235 -127 99 0 146 17 175 67 l20 32 3 -42
			3 -43 105 0 104 0 0 425 0 425 -105 0 -104 0 -3 -152z m-68 -223 c48 -25 65
			-68 65 -165 -1 -129 -36 -180 -126 -180 -54 0 -88 22 -113 74 -21 45 -21 153
			1 207 28 70 106 99 173 64z"/>
			<path d="M2085 1276 c-55 -24 -81 -80 -64 -139 27 -97 160 -115 212 -28 34 57
			10 138 -48 165 -40 19 -60 20 -100 2z"/>
			</g>
			</svg>'
		);
		// add SeminarDesk to the Admin pages 
		$this->pages = array(
			array(
				'page_title' 	=> 'SeminarDesk Plugin', 
				'menu_title' 	=> 'SeminarDesk', 
				'capability' 	=> 'manage_options', 
				'menu_slug' 	=> SD_ADMIN['page'], 
				'callback'		=> array( $this->callbacks, 'adminGeneral' ), 
				'icon_url'		=> $sd_icon, // 'dashicons-calendar', 
				'position' 		=> SD_ADMIN['position'],
			)
		);
	}

	/**
	 * controls the subpages of admin page
	 * 
	 * @return void 
	 */
	public function set_admin_subpages()
	{
		if ( SD_OPTION_VALUE['debug'] !== false ){
			$this->subpages = array(
			// placeholder for custom admin subpages
			);

			foreach ( SD_TXN as $txn => $value ) 
			{
				$this->subpages[] = array(
					'parent_slug'	=> SD_ADMIN['page'], 
					'page_title'	=> $value['title'], 
					'menu_title'	=> $value['title'], 
					'capability'	=> 'manage_options', 
					'menu_slug'		=> 'edit-tags.php?taxonomy=' . $txn, 
					'callback'		=> null,
					'position'		=> $value['menu_position'],
				);
			}
		}
	}
	
	/**
	 * controls the settings of the admin page
	 * 
	 * @return void 
	 */
	public function set_settings()
	{
		$args = array(
			// set slug settings
			array(
				'option_group'	=> SD_ADMIN['group_settings'],
				'option_name'	=> SD_OPTION['slugs'],
				'callback'		=> array( $this->callbacks_mngr, 'sanitize_slugs' ),
			),
			// set slug public settings
			array(
				'option_group'	=> SD_ADMIN['group_settings'],
				'option_name'	=> SD_OPTION['public_events'],
				'callback'		=> array( $this->callbacks_mngr, 'sanitize_checkbox' ),
				'default' => SD_OPTION_DEFAULT['public_events'],
			),
			array(
				'option_group'	=> SD_ADMIN['group_settings'],
				'option_name'	=> SD_OPTION['public_dates'],
				'callback'		=> array( $this->callbacks_mngr, 'sanitize_checkbox' ),
				'default' => SD_OPTION_DEFAULT['public_dates'],
			),
			array(
				'option_group'	=> SD_ADMIN['group_settings'],
				'option_name'	=> SD_OPTION['public_facilitators'],
				'callback'		=> array( $this->callbacks_mngr, 'sanitize_checkbox' ),
				'default' => SD_OPTION_DEFAULT['public_facilitators'],
			),
			array(
				'option_group'	=> SD_ADMIN['group_settings'],
				'option_name'	=> SD_OPTION['public_labels'],
				'callback'		=> array( $this->callbacks_mngr, 'sanitize_checkbox' ),
				'default' => SD_OPTION_DEFAULT['public_labels'],
			),
			// set rest settings
			array(
				'option_group'	=> SD_ADMIN['group_settings'],
				'option_name'	=> SD_OPTION['rest'],
				'callback'		=> array( $this->callbacks_mngr, 'sanitize_checkbox' ),
				'default' => SD_OPTION_DEFAULT['rest'],
			),
			// set debug settings
			array(
				'option_group' 	=> SD_ADMIN['group_settings'],
				'option_name' 	=> SD_OPTION['debug'],
				'callback' 		=> array( $this->callbacks_mngr, 'sanitize_checkbox' ),
				'default' => SD_OPTION_DEFAULT['debug'],
			),
			// set uninstall settings
			array(
				'option_group' 	=> SD_ADMIN['group_settings'],
				'option_name' 	=> SD_OPTION['delete'],
				'callback' 		=> array( $this->callbacks_mngr, 'sanitize_checkbox' ),
				'default' => SD_OPTION_DEFAULT['delete'],
			),
		);

		$this->settings->set_settings( $args );
	}

	/**
	 * controls the sections of the admin page
	 * 
	 * @return void 
	 */
	public function set_sections()
	{
		$args = array(
			// slug section
			array(
				'id'		=> 'sd_admin_slugs',
				'title'		=> __('Slugs', 'seminardesk'),
				'callback'	=> array( $this->callbacks_mngr, 'admin_section_slugs' ),
				'page'		=> SD_ADMIN['page'],
			),
			// rest section
			array(
				'id'		=> 'sd_admin_rest',
				'title'		=> __('REST API', 'seminardesk'),
				'callback'	=> array( $this->callbacks_mngr, 'admin_section_rest' ),
				'page'		=> SD_ADMIN['page'],
			),
			// developer section
			array(
				'id'		=> 'sd_admin_debug',
				'title'		=> __('Developer', 'seminardesk'),
				'callback' 	=> array( $this->callbacks_mngr, 'admin_section_debug' ),
				'page'		=> SD_ADMIN['page'],
			),
			// uninstall section
			array(
				'id'		=> 'sd_admin_uninstall',
				'title'		=> __('Uninstall', 'seminardesk'),
				'callback'	=> array( $this->callbacks_mngr, 'admin_section_uninstall' ),
				'page'		=> SD_ADMIN['page'],
			),
		);

		$this->settings->set_sections( $args );
	}

	/**
	 * controls the fields of the admin page
	 * 
	 * @return void 
	 */
	public function set_fields()
	{
		$args = array();

		/**
		 * slug settings
		 */
		// cpt slugs
		foreach ( SD_CPT as $cpt => $value ){
			// add checkbox to enable/disable REST API for each CPT
			if( $value['name'] !== 'Date'){ // don't add checkbox for Date CPT
					$option_name = 'public_' . strtolower($value['names']);
					$args[] = array(
						'id'		=> SD_OPTION[$option_name],
						'title'		=> $value['title'] . ':',
						'callback'	=> array( $this->callbacks_mngr, 'checkbox_field' ),
						'page'		=> SD_ADMIN['page'],
						'section'	=> 'sd_admin_slugs',
						'args'		=> array(
							'option'	=> SD_OPTION[$option_name],
							'value'	=> SD_OPTION_VALUE[$option_name],
							'class'		=> 'ui-toggle sd-cpts'
						),
					);
				// add slug field for each CPT
				if( $value['public'] === true ){
					$args[] = array(
						'id'		=> $value['slug_option_key'],
						// 'title'		=> $cpt_value['title'] . ':',
						'callback'	=> array( $this->callbacks_mngr, 'slug_field' ),
						'page'		=> SD_ADMIN['page'],
						'section'	=> 'sd_admin_slugs',
						'args'		=> array(
							'option'		=> SD_OPTION['slugs'],
							'key'			=> $value['slug_option_key'],
							'class'			=> 'regular-text',
							'placeholder'	=> $value['slug_default'],
							'type'			=> 'cpt',
							'name'			=> $cpt
						)
					);
				}
			}
		}
		// txn slugs
		foreach ( SD_TXN as $txn => $value ){
			if ( $value['slug_overloading'] !== true && $value['name'] !== 'Facilitator' ){
				// add checkbox to enable/disable REST API for each TXN
				$option_name = 'public_' . strtolower($value['names']);
				$args[] = array(
					'id'		=> SD_OPTION[$option_name],
					'title'		=> $value['title'] . ':',
					'callback'	=> array( $this->callbacks_mngr, 'checkbox_field' ),
					'page'		=> SD_ADMIN['page'],
					'section'	=> 'sd_admin_slugs',
					'args'		=> array(
						'option'	=> SD_OPTION[$option_name],
						'value'	=> SD_OPTION_VALUE[$option_name],
						'class'		=> 'ui-toggle sd-txns'
					),
				);
				// add slug field for each TXN
				if( $value['public'] === true ){
					$args[] = array(
						'id'		=> $value['slug_option_key'],
						// 'title'		=> $value['title'] . ':',
						'callback'	=> array( $this->callbacks_mngr, 'slug_field' ),
						'page'		=> SD_ADMIN['page'],
						'section'	=> 'sd_admin_slugs',
						'args'		=> array(
							'option'		=> SD_OPTION['slugs'],
							'key'			=> $value['slug_option_key'],
							'class'			=> 'regular-text',
							'placeholder'	=> $value['slug_default'],
							'type'			=> 'txn',
							'name'			=> $txn
						)
					);
				}
			}
		}
		// term slugs
		foreach ( SD_TXN_TERM as $term => $value ){
			if ( SD_OPTION_VALUE['public_dates'] === true ){
				// add slug field for each Term
				$args[] = array(
					'id'			=> $value['slug_option_key'],
					'title'			=> $value['title'] . ':',
					'callback'		=> array( $this->callbacks_mngr, 'slug_field' ),
					'page'			=> SD_ADMIN['page'],
					'section'		=> 'sd_admin_slugs',
					'args'			=> array(
						'option'		=> SD_OPTION['slugs'],
						'key'			=> $value['slug_option_key'],
						'class'			=> 'regular-text ui-toggle sd-terms',
						'placeholder'	=> $value['slug_default'],
						'type'			=> 'term',
						'name'			=> $term,
						'taxonomy'		=> $value['taxonomy'],
					)
				);
			}
		}

		// rest settings
		$args[] = array(
			'id'		=> SD_OPTION['rest'],
			'title'		=> __('REST API:', 'seminardesk'),
			'callback'	=> array( $this->callbacks_mngr, 'checkbox_field' ),
			'page'		=> SD_ADMIN['page'],
			'section'	=> 'sd_admin_rest',
			'args'		=> array(
				'option'	=> SD_OPTION['rest'],
				'value'	=> SD_OPTION_VALUE['rest'],
				'class'		=> 'ui-toggle'
			),
		);

		/**
		 * developer settings
		 */
		$args[] = array(
			'id'		=> SD_OPTION['debug'],
			'title'		=> __('Debug:', 'seminardesk'),
			'callback'	=> array( $this->callbacks_mngr, 'checkbox_field' ),
			'page'		=> SD_ADMIN['page'],
			'section'	=> 'sd_admin_debug',
			'args'		=> array(
				'option'	=> SD_OPTION['debug'],
				'value'	=> SD_OPTION_VALUE['debug'],
				'class'		=> 'ui-toggle'
			),
		);

		/**
		 * uninstall settings
		 */
		$args[] = array(
			'id'		=> SD_OPTION['delete'],
			'title'		=> __('Delete all:', 'seminardesk'),
			'callback'	=> array( $this->callbacks_mngr, 'checkbox_field' ),
			'page'		=> SD_ADMIN['page'],
			'section'	=> 'sd_admin_uninstall',
			'args'		=> array(
				'option'	=> SD_OPTION['delete'],
				'value'	=> SD_OPTION_VALUE['delete'],
				'class'		=> 'ui-toggle'
			),
		);

		$this->settings->set_fields( $args );
	}
}