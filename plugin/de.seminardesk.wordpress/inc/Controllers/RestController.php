<?php
/**
 * @package SeminardeskPlugin
 */

namespace Inc\Controllers;

// exit if accessed directly
defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

use WP_REST_Controller;
use WP_REST_Server;
use Inc\Callbacks\RestCallbacks;

/**
 * Handles the custom REST API endpoint of the SeminarDesk-WordPress plugin
 * 
 * @link: https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
 */
class RestController extends WP_REST_Controller
{

	/**
	 * base names to build a route
	 *
	 * @var string
	 */
	protected $base_webhook, $base_cpt_event, $base_cpt_date, $base_cpt_facilitator;
	
	function __construct()
	{
		$this->register();
	}

	/**
	 * Code that runs to register the controller
	 * 
	 * @return void 
	 */
	public function register()
	{
		$this->namespace = 'seminardesk/v1';
		$this->base_webhook = 'webhooks';
		$this->base_cpt_event = 'cpt_events';
		$this->base_cpt_date = 'cpt_dates';
		$this->base_cpt_facilitator = 'cpt_facilitators';
		$this->base_cpt_label = 'cpt_labels';

		// add custom REST API route for SeminarDesk
		add_action('rest_api_init', array($this, 'create_routes'));
	}

	/**
	 * Registers custom namespace, its route and methods
	 * 
	 * @return void
	 */
	public function create_routes()
	{
		$rest = new RestCallbacks;

		// Webhook route registration for HTTP POSTs from SeminarDesk
		register_rest_route($this->namespace, '/' . $this->base_webhook, array(
			array(
				'methods'				=> WP_REST_Server::CREATABLE,
				'callback'				=> array($rest, 'create_webhooks'),
				'permission_callback'	=> array($rest, 'post_check_permissions'),
				// 'args'	=> $this->get_endpoint_args_for_item_schema( true ),
			),
		));

		// enable/disable GET methods
		if( SD_OPTION_VALUE['rest'] == true ){ 

			// CPT Event route registration to get all events
			register_rest_route($this->namespace, '/' . $this->base_cpt_event, array(
				array(
					'methods'				=> WP_REST_Server::READABLE,
					'callback'				=> array($rest, 'get_cpt_events'),
					'permission_callback'	=> array($rest, 'get_check_permissions'),
					// 'args'	=> array(),
				),
			));
	
			// CPT Event route registration to get specific event
			register_rest_route($this->namespace, '/' . $this->base_cpt_event . '/(?P<event_id>[a-z0-9]+)', array(
				array(
					'methods'				=> WP_REST_Server::READABLE,
					'callback'				=> array($rest, 'get_cpt_event'),
					'permission_callback'	=> array($rest, 'get_check_permissions'),
					'args'					=> $this->get_endpoint_args_for_item_schema( true ),
				),
			));
	
			// CPT Event date route registration to get all event dates
			register_rest_route($this->namespace, '/' . $this->base_cpt_date, array(
				array(
					'methods'				=> WP_REST_Server::READABLE,
					'callback'				=> array($rest, 'get_cpt_dates'),
					'permission_callback'	=> array($rest, 'get_check_permissions'),
					// 'args'	=> array(),
				),
			));
	
			// CPT Event date route registration to get specific event date
			register_rest_route($this->namespace, '/' . $this->base_cpt_date . '/(?P<date_id>[0-9]+)', array(
				array(
					'methods'				=> WP_REST_Server::READABLE,
					'callback'				=> array($rest, 'get_cpt_date'),
					'permission_callback'	=> array($rest, 'get_check_permissions'),
					// 'args'	=> $this->get_endpoint_args_for_item_schema( true ),
				),
			));
	
			// CPT Facilitator route registration to get all facilitators
			register_rest_route($this->namespace, '/' . $this->base_cpt_facilitator, array(
				array(
					'methods'				=> WP_REST_Server::READABLE,
					'callback'				=> array($rest, 'get_cpt_facilitators'),
					'permission_callback'	=> array($rest, 'get_check_permissions'),
					// 'args'	=> array(),
				),
			));
		
			// CPT Facilitator route registration to get specific facilitator
			register_rest_route($this->namespace, '/' . $this->base_cpt_facilitator . '/(?P<facilitator_id>[0-9]+)', array(
				array(
					'methods'				=> WP_REST_Server::READABLE,
					'callback'				=> array($rest, 'get_cpt_facilitator'),
					'permission_callback'	=> array($rest, 'get_check_permissions'),
					// 'args'	=> $this->get_endpoint_args_for_item_schema( true ),
				),
			));
	
			// CPT label route registration to get all labels
			register_rest_route($this->namespace, '/' . $this->base_cpt_label, array(
				array(
					'methods'				=> WP_REST_Server::READABLE,
					'callback'				=> array($rest, 'get_cpt_labels'),
					'permission_callback'	=> array($rest, 'get_check_permissions'),
					// 'args'	=> array(),
				),
			));
		
			// CPT label route registration to get specific label group
			register_rest_route($this->namespace, '/' . $this->base_cpt_label . '/(?P<label_id>[0-9]+)', array(
				array(
					'methods'				=> WP_REST_Server::READABLE,
					'callback'				=> array($rest, 'get_cpt_label'),
					'permission_callback'	=> array($rest, 'get_check_permissions'),
					// 'args'	=> $this->get_endpoint_args_for_item_schema( true ),
				),
			));

		}
	}
}
