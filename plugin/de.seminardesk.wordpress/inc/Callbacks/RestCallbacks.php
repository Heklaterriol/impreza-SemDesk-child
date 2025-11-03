<?php 
/**
 * @package SeminardeskPlugin
 */
namespace Inc\Callbacks;

// exit if accessed directly
defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

use WP_Error;
use WP_REST_Response;
use Inc\Utils\WebhookHandler;
use WP_Query;
use Inc\Utils\AdminUtils;

/**
 * Callbacks for the custom REST API endpoint of the SeminarDesk-WordPress plugin
 */
class RestCallbacks{

	/**
	 * Sets permission for POST request to interact with endpoint
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function post_check_permissions($request)
	{
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Sets permission for GET request to interact with endpoint
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_check_permissions($request)
	{
		// return current_user_can( 'edit_posts' );
		return true;
	}

	/**
	 * Gets specific post of a custom post type via meta
	 *
	 * @param string $post_type
	 * @param string $meta_key
	 * @param string $meta_value
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_custom_post($post_type, $meta_key, $meta_value)
	{ 
		$args = array(
			'numberposts'	=> -1,
			'post_type'		=> $post_type,
			'post_status'	=> 'publish',
			'meta_key'		=> $meta_key,
			'meta_value'	=> $meta_value,
		);
		$custom_query = new WP_Query( $args );
 
		if ( empty( $custom_query->post ) ) {
			return new WP_Error('no_post', 'Requested ID ' .$meta_value . ' does not exist', array('status' => 404));
		}

		$response = $this->get_custom_post_attr($custom_query->post);
		return rest_ensure_response($response);
	}
	
	/**
	* Gets all posts of a custom post type
	*
	* @param WP_REST_Request $request
	* @return WP_REST_Response|WP_Error
	*/
	public function get_custom_posts($post_type)
	{
		$args = array(
			'numberposts'	=> -1, // all events
			'post_type'		=> $post_type,
			'post_status'	=> 'any',
		);
		$posts = get_posts($args);

		if (empty($posts)) {
			return new WP_Error('no_post', 'No post available', array('status' => 404));
		}

		$response = array();

		foreach ( $posts as $current ) {
			// get event attributes and add to $response of the endpoint
			$post_attr = $this->get_custom_post_attr( $current );
			array_push( $response, $post_attr );
		}
		return rest_ensure_response( $response );
	}

	/**
	 * gets post attributes of corresponding custom post type
	 * 
	 * @param WP_Post $post
	 * @return Array|Null post attributes
	 */
	public function get_custom_post_attr($post)
	{
		switch ($post->post_type) {
			case 'sd_cpt_event':
				if ( SD_OPTION_VALUE['debug'] !== false ){
					$event_attr = array(
						'wp_event_id'	=> $post->ID,
						'sd_event_id'	=> $post->sd_event_id,
						'title'			=> $post->post_title,
						'slug'			=> $post->post_name,
						'link'			=> get_post_permalink($post->ID),
						'status'		=> $post->post_status,
						'author'		=> get_the_author_meta( 'display_name', $post->post_author),
						'sd_data'		=> $post->sd_data,
						'sd_webhook'	=> $post->sd_webhook, // get metadata 'json_dump'
					);
				}else{
					$event_attr = array(
						'wp_event_id'	=> $post->ID,
						'sd_event_id'	=> $post->sd_event_id,
						'title'			=> $post->post_title,
						'slug'			=> $post->post_name,
						'link'			=> get_post_permalink($post->ID),
						'status'		=> $post->post_status,
						'author'		=> get_the_author_meta( 'display_name', $post->post_author),
						'sd_webhook'	=> $post->sd_webhook, // get metadata 'json_dump'
					);
				}
				break;
			case 'sd_cpt_date':
				if ( SD_OPTION_VALUE['debug'] !== false ){
					$event_attr = array(
						'wp_date_id'		=> $post->ID,
						'sd_date_id'		=> $post->sd_date_id,
						'sd_date_begin'		=> $post->sd_date_begin,
						'sd_date_end'		=> $post->sd_date_end,
						'wp_event_id'		=> $post->wp_event_id,
						'sd_event_id'		=> $post->sd_event_id,
						'sd_preview_available'	=> $post->sd_preview_available,
						'title'				=> $post->post_title,
						'slug'				=> $post->post_name,
						'link'				=> get_post_permalink($post->ID),
						'status'			=> $post->post_status,
						'sd_data'			=> $post->sd_data,
						'sd_webhook'		=> $post->sd_webhook,
					);
				}else{
					$event_attr = array(
						'wp_date_id'		=> $post->ID,
						'sd_date_id'		=> $post->sd_date_id,
						'sd_date_begin'		=> $post->sd_date_begin,
						'sd_date_end'		=> $post->sd_date_end,
						'wp_event_id'		=> $post->wp_event_id,
						'sd_event_id'		=> $post->sd_event_id,
						'sd_preview_available'	=> $post->sd_preview_available,
						'title'				=> $post->post_title,
						'slug'				=> $post->post_name,
						'link'				=> get_post_permalink($post->ID),
						'status'			=> $post->post_status,
						'sd_webhook'		=> $post->sd_webhook,
					);
				}
				break;
			case 'sd_cpt_facilitator':
				if ( SD_OPTION_VALUE['debug'] !== false ){
					$event_attr = array(
						'wp_facilitator_id'	=> $post->ID,
						'sd_facilitator_id'	=> $post->sd_facilitator_id,
						'sd_last_name'		=> $post->sd_last_name,
						'title'				=> $post->post_title,
						'slug'				=> $post->post_name,
						'link'				=> get_post_permalink($post->ID),
						'status'			=> $post->post_status,
						'sd_data'			=> $post->sd_data,
						'sd_webhook'		=> $post->sd_webhook,
					);
				}else{
					$event_attr = array(
						'wp_facilitator_id'	=> $post->ID,
						'sd_facilitator_id'	=> $post->sd_facilitator_id,
						'sd_last_name'		=> $post->sd_last_name,
						'title'				=> $post->post_title,
						'slug'				=> $post->post_name,
						'link'				=> get_post_permalink($post->ID),
						'status'			=> $post->post_status,
						'sd_webhook'		=> $post->sd_webhook,
					);
				}
				break;
			case 'sd_cpt_label':
				
				if ( !empty($post->sd_labelGroup_id) ) {
					if ( SD_OPTION_VALUE['debug'] !== false ){
						$event_attr = array(
							'wp_labelGroup_id'	=> $post->ID,
							'sd_labelGroup_id'	=> $post->sd_labelGroup_id,
							'title'				=> $post->post_title,
							'slug'				=> $post->post_name,
							'link'				=> get_post_permalink($post->ID),
							'status'			=> $post->post_status,
							'sd_data'			=> $post->sd_data,
							'sd_webhook'		=> $post->sd_webhook,
						);
					}else{
						$event_attr = array(
							'wp_labelGroup_id'	=> $post->ID,
							'sd_labelGroup_id'	=> $post->sd_labelGroup_id,
							'title'				=> $post->post_title,
							'slug'				=> $post->post_name,
							'link'				=> get_post_permalink($post->ID),
							'status'			=> $post->post_status,
							'sd_webhook'		=> $post->sd_webhook,
						);
					}
				} else {
					if ( SD_OPTION_VALUE['debug'] !== false ){
						$event_attr = array(
							'wp_label_id'	=> $post->ID,
							'sd_label_id'	=> $post->sd_label_id,
							'title'			=> $post->post_title,
							'slug'			=> $post->post_name,
							'link'			=> get_post_permalink($post->ID),
							'status'		=> $post->post_status,
							'sd_data'		=> $post->sd_data,
							'sd_webhook'	=> $post->sd_webhook,
						);
					}else{
						$event_attr = array(
							'wp_label_id'	=> $post->ID,
							'sd_label_id'	=> $post->sd_label_id,
							'title'			=> $post->post_title,
							'slug'			=> $post->post_name,
							'link'			=> get_post_permalink($post->ID),
							'status'		=> $post->post_status,
							'sd_webhook'	=> $post->sd_webhook,
						);
					}
				}
				break;
			default:
				$event_attr = null;
		}
		// get response for a single post
		return $event_attr;
	}

	/**
	 * Processes HTTP POSTs from SeminarDesk
	 *
	 * @param WP_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_webhooks($request)
	{
		$request_json = (array)$request->get_json_params(); // complete JSON data of the request#
		if ( !empty( $request_json['notifications'] ) ){
			$webhook = new WebhookHandler;
			$response = $webhook->batch_request($request_json);
		} else{
			$response = new WP_Error('not_supported', 'notifications of the request is empty', array('status' => 400));
		}
		return rest_ensure_response($response);
	}

	/**
	* Gets list of all events
	*
	* @param WP_REST_Request $request
	* @return WP_REST_Response|WP_Error
	*/
	public function get_cpt_events($request)
	{
		$response = $this->get_custom_posts('sd_cpt_event');
		return rest_ensure_response( $response );
	}

	/**
	* Gets list of all event dates
	*
	* @param WP_REST_Request $request
	* @return WP_REST_Response|WP_Error
	*/
	public function get_cpt_dates($request)
	{
		$response = $this->get_custom_posts('sd_cpt_date');
		return rest_ensure_response( $response );
	}

	/**
	* Gets list of all facilitators
	*
	* @param WP_REST_Request $request
	* @return WP_REST_Response|WP_Error
	*/
	public function get_cpt_facilitators($request)
	{
		$response = $this->get_custom_posts('sd_cpt_facilitator');
		return rest_ensure_response( $response );
	}

	/**
	* Gets list of all labels
	*
	* @param WP_REST_Request $request
	* @return WP_REST_Response|WP_Error
	*/
	public function get_cpt_labels($request)
	{
		$response = $this->get_custom_posts('sd_cpt_label');
		return rest_ensure_response( $response );
	}

	/**
	 * Gets specific event via event id
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_cpt_event($request)
	{
		$response = $this->get_custom_post('sd_cpt_event', 'sd_event_id', $request['event_id']);
		return rest_ensure_response($response);
	}

	/**
	 * Gets specific event date via event date id
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_cpt_date($request)
	{
		$response = $this->get_custom_post('sd_cpt_date', 'sd_date_id', $request['date_id']);
		return rest_ensure_response($response);
	}

	/**
	 * Gets specific facilitator via facilitator id
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_cpt_facilitator($request)
	{
		$response = $this->get_custom_post('sd_cpt_facilitator', 'sd_facilitator_id', $request['facilitator_id']);
		return rest_ensure_response($response);
	}

	/**
	 * Gets specific label via label id
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_cpt_label($request)
	{
		$response = $this->get_custom_post('sd_cpt_label', 'sd_label_id', $request['label_id']);
		if ( is_wp_error ( $response ) ){
			$response = $this->get_custom_post('sd_cpt_label', 'sd_labelGroup_id', $request['label_id'], );
		}
		return rest_ensure_response($response);
	}
}