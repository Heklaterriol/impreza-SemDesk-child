<?php 
/**
 * @package SeminardeskPlugin
 */

namespace Inc\Utils;

// exit if accessed directly
defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

use WP_REST_Response;
use Inc\Utils\TemplateUtils;
use Inc\Utils\AdminUtils;
use Inc\Utils\WebhookUtils as Utils;

/**
 * Handles webhook actions
 */
class WebhookHandler
{
	/**
	 * Processes webhook request and its notifications sent from SeminarDesk
	 * 
	 * Note: notification order in webhook batch... labelGroup.create, facilitator.create, event.create, eventDate.create
	 * 
	 * @param array $request_json Webhook request
	 * @return WP_REST_Response 
	 */
	public function batch_request( $request_json )
	{
		$response_notifications = array();
		$notifications = $request_json['notifications'];	 
		foreach ( $notifications as $notification ){
			switch ( $notification['action'] ){
				case 'event.create':
					$response = $this->create_event($notification);
					array_push( $response_notifications, $response );
					break;
				case 'event.update':
					$response = $this->update_event($notification);
					array_push( $response_notifications, $response );
					break;
				case 'event.delete':
					$response = $this->delete_event($notification);
					array_push( $response_notifications, $response );
					break;
				case 'eventDate.create':
					$response = $this->create_event_date($notification);
					array_push( $response_notifications, $response );
					break;
				case 'eventDate.update':
					$response = $this->update_event_date($notification);
					array_push( $response_notifications, $response );
					break;
				case 'eventDate.delete':
					$response = $this->delete_event_date($notification);
					array_push( $response_notifications, $response );
					break;
				case 'facilitator.create':
					$response =$this->create_facilitator($notification);
					array_push( $response_notifications, $response );
					break;
				case 'facilitator.update':
					$response = $this->update_facilitator($notification);
					array_push( $response_notifications, $response );
					break;
				case 'facilitator.delete':
					$response = $this->delete_facilitator($notification);
					array_push( $response_notifications, $response );
					break;
				case 'labelGroup.create':
					$response =$this->create_label($notification);
					array_push( $response_notifications, $response );
					break;
				case 'labelGroup.update':
					$response = $this->update_label($notification);
					array_push( $response_notifications, $response );
					break;
				case 'labelGroup.delete':
					$response = $this->delete_label($notification);
					array_push( $response_notifications, $response );
					break;
				case 'all.delete':
					$response = $this->delete_all($notification);
					array_push( $response_notifications, $response );
					break;
				case 'plugin.update':
					$response = $this->update_plugin( $notification );
					array_push( $response_notifications, $response );
					break;
				default:
					$response = array(
						'status'	=> 400,
						'message'	=> 'action ' . $notification['action'] . ' not supported',
						'action'	=> $notification['action'],
						'id'		=> $notification['payload']['id'],
					);
					array_push( $response_notifications, $response );
			}
		}

		return new WP_REST_Response( [
			'message'		=> 'webhook response',
			'requestId'		=> $request_json['id'],
			'attempt'		=> $request_json['attempt'],
			'notifications'	=> $response_notifications,
		], 200);
	}


	/**
	 * Creates or updates event via webhook from SeminarDesk
	 *
	 * @param array $notification Notification part of the Webhook request
	 * @return array Webhook response
	 */
	public function put_event( $notification )
	{
		$payload = (array)$notification['payload']; // payload of the request in JSON
		$sd_webhook = $notification;
		unset($sd_webhook['payload']);
		
		// checks if event_id exists and sets corresponding post_id
		$query = Utils::get_query_by_meta( 'sd_cpt_event', 'sd_event_id', $payload['id'] );
		$event_wp_id = $query->post->ID ?? 0;
		$event_count = $query->post_count;
		$event_title = wp_strip_all_tags( TemplateUtils::get_value_by_language( $payload['title'] ) );
		$txn_input = array();
		
		/**
		 * Event Settings
		 */
		// set post_status private on event setting 'detailpageAvailable'
		$detailpageAvailable = $payload['detailpageAvailable'] ?? true;
		if (  $detailpageAvailable === false){
			$post_status = 'private'; // not visible to users who are not logged in. Note: Append string 'Private: ' in front of the post title
			// $post_status = 'pending'; // post is pending review, but is visible for everyone
		} else {
			$post_status = 'publish'; // viewable by everyone
		}
		// update event setting previewAvailable for all corresponding event dates, if they already exist
		$post_previewAvailable = $query->post->sd_data['previewAvailable'] ?? null;
		$payload_previewAvailable = $payload['previewAvailable'] ?? true;
		if ( !empty($event_wp_id) && $post_previewAvailable !== $payload_previewAvailable )
		{
			// query corresponding event dates
			$query_dates = Utils::get_query_by_meta( 'sd_cpt_date', 'sd_event_id', $query->post->sd_event_id );
			foreach ( $query_dates->posts as $post_date ){
				$post_date->sd_preview_available = $payload_previewAvailable;
				update_post_meta( $post_date->ID, 'sd_preview_available', $post_date->sd_preview_available );
			}
		}

		/**
		 * sd_txn_facilitators
		 */
		// TODO: for backwards compatibility - perhaps remove facilitator for event at a later?
		$facilitators = $payload['facilitators'] ?? null;
		if ( !empty($facilitators) ){
			foreach ( $facilitators as $facilitator ){
				Utils::add_term_tax_input( $txn_input, $facilitator['id'], 'sd_txn_facilitators' );
			}
		}else{
			$txn_input['sd_txn_facilitators'] = array();
		}

		/**
		 * sd_txn_labels
		 */
		$labels = $payload['labels'] ?? null;
		if ( !empty( $labels ) ){
			foreach( $labels as $label ){
				$label_term_name = 'l_id_' . $label['id'];
				Utils::add_term_tax_input( $txn_input, $label_term_name, 'sd_txn_labels' );
			}
		}else{
			$txn_input['sd_txn_labels'] = array();
		}
		
		/**
		 * sd_cpt_events
		 */
		// define metadata of the event
		$meta_input = array(
			'sd_event_id'	=> $payload['id'],
			'sd_data'		=> $payload,
			'sd_webhook'	=> $sd_webhook,
		);
		// set attributes of the new event
		$event_slug = Utils::unique_post_slug( $event_title, $event_wp_id );
		$event_attr = array(
			'post_type'			=> 'sd_cpt_event',
			'post_title'		=> $event_title,
			'post_name'			=> $event_slug,
			'post_author'		=> get_current_user_id(),
			'post_status'		=> $post_status,
			'meta_input'		=> $meta_input,
			'tax_input'			=> $txn_input,
		);
		
		// create new post or update post with existing post id
		if ( !empty($event_wp_id) ) {
			$event_attr['ID'] = $event_wp_id;
			$message = 'Event updated';
			$event_wp_id = wp_update_post( wp_slash($event_attr), true );

		} else {
			$message = 'Event created';
			$event_wp_id = wp_insert_post(wp_slash($event_attr), true);
		}

		// set featured image
		$picture_url = TemplateUtils::get_value_by_language($payload['headerPictureUrl']);
		$featured_image = Utils::featured_image_to_post( $event_wp_id, $picture_url );

		// add custom action hook
		do_action( 'wpsd_webhook_put_event', $notification );

		// return error if $post_id is of type WP_Error
		if (is_wp_error($event_wp_id)){
			return $event_wp_id;
		}

		return array(
			'status'		=> 200,
			'message'		=> $message,
			'action'		=> $notification['action'],
			'eventId'		=> $payload['id'],
			'eventWpId'		=> $event_wp_id,
			'eventWpCount'	=> $event_count,
		);
	}

	/**
	 * Creates event via webhook from SeminarDesk
	 * 
	 * Note: Incase event id already exists, it will update existing event
	 *
	 * @param array $notification Notification part of the Webhook request
	 * @return array Webhook response
	 */
	public function create_event($notification)
	{
		$response = $this->put_event($notification);
		// add custom action hook
		do_action( 'wpsd_webhook_create_event', $notification );
		return $response;
	}

	/**
	 * Updates event via webhook from SeminarDesk
	 *
	 * Note: Incase event id doesn't exists, it will create the event
	 * 
	 * @param array $notification Notification part of the Webhook request
	 * @return array Webhook response
	 */
	public function update_event($notification)
	{
		$response = $this->put_event($notification);
		// add custom action hook
		do_action( 'wpsd_webhook_update_event', $notification );
		return $response;
	}

	/**
	 * Deletes event via webhook from SeminarDesk
	 * 
	 * @param array $notification Notification part of the Webhook request
	 * @return array Webhook response
	 */
	public function delete_event($notification) 
	{
		$payload = (array)$notification['payload'];

		$event_deleted = Utils::delete_post_by_meta( 'sd_cpt_event', 'sd_event_id', $payload['id'] );
		
		if ( empty($event_deleted) ){
			return array(
				'status'	=> 404,
				'message'	=> 'Nothing to delete. Event ID ' . $payload['id'] . ' does not exists',
				'action'	=> $notification['action'],
				'eventId'	=> $payload['id'],
			);
		}
		
		// add custom action hook
		do_action( 'wpsd_webhook_delete_event', $notification );

		return array(
			'status'	=> 200,
			'message'	=> 'Event deleted',
			'action'	=> $notification['action'],
			'eventId'	=> $payload['id'],
			'eventWpId'	=> $event_deleted->ID,
		);
	}

	/**
	 * Creates or updates event date via webhook from SeminarDesk
	 *
	 * @param array $notification Notification part of the Webhook request
	 * @return array Webhook response
	 */
	public function put_event_date( $notification )
	{
		$date_payload = (array)$notification['payload'];
		$sd_webhook = $notification;
		unset($sd_webhook['payload']);

		// check if with event date associated event exists and get its WordPress ID
		$event_query = Utils::get_query_by_meta( 'sd_cpt_event', 'sd_event_id', $date_payload['eventId']);
		$event_wp_id = $event_query->post->ID ?? 0;
		$event_payload = $event_query->post->sd_data;
		$event_preview = $event_payload['previewAvailable'] ?? false;

		if (!isset($event_wp_id)){
			return array(
				'status'		=> 404,
				'message'		=> 'Event date not created. Associated event with the ID ' . $date_payload['eventId'] . ' does not exist',
				'action'		=> $notification['action'],
				'eventDateId'	=> $date_payload['id'],
				'eventId'		=> $date_payload['eventId'],
			);
		}
		$event_count = $event_query->post_count;

		// check if event date exists and sets corresponding date_post_id
		$date_query = Utils::get_query_by_meta( 'sd_cpt_date', 'sd_date_id', $date_payload['id']);
		$date_wp_id = $date_query->post->ID ?? 0;
		$date_count = $date_query->post_count;
		$date_title = wp_strip_all_tags( TemplateUtils::get_value_by_language( $date_payload['title'] ) );

		/**
		 * sd_txn_dates
		 */
		$year = wp_date('Y', $date_payload['beginDate']/1000);
		$month = wp_date('m', $date_payload['beginDate']/1000);
		// get term ID for date year and create if term doesn't exist incl. months as its children terms
		$term_year = term_exists( (string) $year, 'sd_txn_dates'); 
		if (!isset($term_year)){
			$term_year = wp_insert_term($year, 'sd_txn_dates', array(
				'description'	=> __('Dates of', 'seminardesk') . ' ' . $year,
				'slug'			=> $year,
			));
			for ($m = 1; $m <= 12; $m++){
				$m_padded = sprintf('%02s', $m);
				wp_insert_term($m_padded . '/' . $year, 'sd_txn_dates', array(
					// 'alias_of'	=> $year,
					'description'	=> __('Dates of', 'seminardesk') . ' ' . $m_padded . '/' . $year,
					'parent'		=> $term_year['term_id'],
					'slug'			=> $year . '-' . $m_padded,
				));
			}
		}
		// add txn_dates terms (year and months) to tax_input array
		$term_month = term_exists( (string) $year . '-' . (string) $month, 'sd_txn_dates');
		$txn_dates_terms = array(
			$term_year['term_id'], 
			$term_month['term_id'],
		);
		$txn_input = array(
			'sd_txn_dates' => $txn_dates_terms,
		);

		/**
		 * sd_txn_facilitators
		 */
		// get facilitators ids from date
		$facilitators = $date_payload['facilitators'] ?? null;
		if ( !empty($facilitators) ){
			foreach ( $facilitators as $facilitator ){
				Utils::add_term_tax_input( $txn_input, $facilitator['id'], 'sd_txn_facilitators' );
			}
		}else{
			$txn_input['sd_txn_facilitators'] = array();
		}

		/**
		 * sd_txn_labels
		 */
		// define array of taxonomy terms keyed by sd_txn_labels for the sd_cpt_date
		$labels = $date_payload['labels'] ?? null;
		if ( !empty( $labels ) ){
			foreach( $labels as $label ){
				$label_term_name = 'l_id_' . $label['id'];
				Utils::add_term_tax_input( $txn_input, $label_term_name, 'sd_txn_labels' );
			}
		}else{
			$txn_input['sd_txn_labels'] = array();
		}
		
		$meta_input = array(
			'sd_date_id'		=> $date_payload['id'],
			'sd_date_begin'	 	=> $date_payload['beginDate'],
			'sd_date_end'		=> $date_payload['endDate'],
			'sd_event_id'		=> $date_payload['eventId'],
			'wp_event_id'		=> $event_wp_id,
			'sd_preview_available'	=> $event_preview ?? false,
			'sd_data'			=> $date_payload,
			'sd_webhook'		=> $sd_webhook,
		);
		$date_slug = Utils::unique_post_slug( $date_title, $date_wp_id );
		// TODO: add post_parent sd_cpt_event
		$date_attr = array(
			'post_type'		=> 'sd_cpt_date',
			'post_title'	=> $date_title,
			'post_name'		=> $date_slug,
			'post_author'	=> get_current_user_id(),
			'post_status'	=> 'publish',
			// 'post_parent'	=> $event_wp_id,
			'meta_input'	=> $meta_input,
			'tax_input'		=> $txn_input,
		);
		
		// create new post or update post with existing post id
		if ( !empty($date_wp_id) ) {
			$date_attr['ID'] = $date_wp_id;
			$message = 'Event Date updated';
			$date_wp_id = wp_update_post( wp_slash($date_attr), true );
		} else {
			$message = 'Event Date created';
			$date_wp_id = wp_insert_post( wp_slash($date_attr), true );
		}

		// add custom action hook
		do_action( 'wpsd_webhook_put_date', $notification );
		
		// return error if $date_post_id is of type WP_Error
		if (is_wp_error($date_wp_id)){
			return $date_wp_id;
		}

		return array(
			'status'			=> 200,
			'message'			=> $message,
			'action'			=> $notification['action'],
			'eventDateId'		=> $date_payload['id'],
			'eventDateWpId'		=> $date_wp_id,
			'EventDateWpCount'	=> $date_count,
			'eventId'			=> $date_payload['eventId'],
			'eventWpId'			=> $event_wp_id,
			'eventWpCount'		=> $event_count,
		);
	}

	/**
	 * Creates event date via webhook from SeminarDesk
	 * 
	 * Note: Incase event date id already exists, it will update existing event date
	 *
	 * @param array $notification Notification part of the Webhook request
	 * @return array Webhook response
	 */
	public function create_event_date( $notification )
	{
		$response = $this->put_event_date( $notification );
		// add custom action hook
		do_action( 'wpsd_webhook_create_date', $notification );
		return $response;
	}

	/**
	 * Updates event date via webhook from SeminarDesk
	 * 
	 * Note: Incase event date id doesn't exists, it will create the event date
	 *
	 * @param array $notification Notification part of the Webhook request
	 * @return array Webhook response
	 */
	public function update_event_date( $notification )
	{
		$response = $this->put_event_date( $notification );
		// add custom action hook
		do_action( 'wpsd_webhook_update_date', $notification );
		return $response;
	}

	/**
	 * Deletes event date via webhook from SeminarDesk
	 *
	 * @param array $notification Notification part of the Webhook request
	 * @return array Webhook response
	 */
	public function delete_event_date($notification)
	{
		$payload = (array)$notification['payload'];

		$date_deleted = Utils::delete_post_by_meta('sd_cpt_date', 'sd_date_id', $payload['id']);

		if ( empty($date_deleted) ){
			return array(
				'status'		=> 404,
				'message'		=> 'Nothing to delete. Event date ID ' . $payload['id'] . ' does not exists',
				'action'		=> $notification['action'],
				'eventDateId'	=> $payload['id'],
			);
		}

		// add custom action hook
		do_action( 'wpsd_webhook_delete_date', $notification );
		
		return array(
			'status'		=> 200,
			'message'		=> 'Event date deleted',
			'action'		=> $notification['action'],
			'eventDateId'	=> $payload['id'],
			'eventDateWpId'	=> $date_deleted->ID,
		);
	}
		
	/**
	 * Creates or updates facilitator via webhook from SeminarDesk
	 *
	 * @param array $notification Notification part of the Webhook request
	 * @return array Webhook response
	 */
	public function put_facilitator( $notification )
	{
		$facilitator_payload = (array)$notification['payload'];
		$sd_webhook = $notification;
		unset($sd_webhook['payload']);
		$facilitator_title = wp_strip_all_tags( $facilitator_payload['name'] );

		/**
		 * sd_cpt_facilitators
		 */
		$query = Utils::get_query_by_meta( 'sd_cpt_facilitator', 'sd_facilitator_id', $facilitator_payload['id'] );
		$facilitator_wp_id = $query->post->ID ?? 0;
		
		/**
		 * sd_txn_labels
		 */
		$txn_input = array();
		$labels = $facilitator_payload['labels'] ?? null;
		if ( !empty( $labels ) ){
			foreach( $labels as $label ){
				$label_term_name = 'l_id_' . $label['id'];
				Utils::add_term_tax_input( $txn_input, $label_term_name, 'sd_txn_labels' );
			}
		}else{
			$txn_input['sd_txn_labels'] = array();
		}
		
		// define metadata of the new sd_cpt_facilitator
		$meta_input = array(
			'sd_facilitator_id'	=> $facilitator_payload['id'],
			'sd_last_name'		=> $facilitator_payload['lastName'],
			'sd_data'			=> $facilitator_payload,
			'sd_webhook'		=> $sd_webhook,
		);
		// define attributes of the new facilitator using $payload of the 
		$slug = Utils::unique_post_slug( $facilitator_title, $facilitator_wp_id );
		$facilitator_attr = array(
			'post_type'		=> 'sd_cpt_facilitator',
			'post_title'	=> $facilitator_title,
			'post_name'		=> $slug,
			'post_author'	=> get_current_user_id(),
			'post_status'	=> 'publish',
			'meta_input'	=> $meta_input,
			'tax_input'		=> $txn_input,
		);
		// create new post or update post with existing post id
		if ( !empty($facilitator_wp_id) ) {
			$facilitator_attr['ID'] = $facilitator_wp_id;
			$message = 'Facilitator updated';
			$facilitator_wp_id = wp_update_post( wp_slash($facilitator_attr), true );
		} else {
			$message = 'Facilitator created';
			$facilitator_wp_id = wp_insert_post(wp_slash($facilitator_attr), true);
		}

		// set featured image
		$picture_url = $facilitator_payload['pictureUrl'];
		$featured_image = Utils::featured_image_to_post( $facilitator_wp_id, $picture_url );

		// return error if $post_id is of type WP_Error
		if (is_wp_error($facilitator_wp_id)){
			return $facilitator_wp_id;
		}

		/**
		 * sd_txn_facilitators
		 */
		// insert/update term for sd_cpt_facilitator, is parent of event term
		// set ID as name to be queryable
		$term_ids = Utils::set_term( 'sd_txn_facilitators', $facilitator_payload['id'], $facilitator_title, $slug );
		// return error if $term_ids is of type WP_Error
		if (is_wp_error($term_ids)){
			return $term_ids;
		}

		// add custom action hook
		do_action( 'wpsd_webhook_put_facilitator', $notification );

		return array(
			'status'			=> 200,
			'message'			=> $message,
			'action'			=> $notification['action'],
			'facilitatorId'		=> $facilitator_payload['id'],
			'facilitatorWpId'	=> $facilitator_wp_id,
		);
	}

	/**
	 * Creates facilitator via webhook from SeminarDesk
	 * 
	 * Note: Incase facilitator id already exists, it will update existing facilitator
	 *
	 * @param array $notification Notification part of the Webhook request
	 * @return array Webhook response
	 */
	public function create_facilitator($notification)
	{
		$response = $this->put_facilitator( $notification );
		// add custom action hook
		do_action( 'wpsd_webhook_create_facilitator', $notification );
		return $response;
	}

	/**
	 * Updates facilitator via webhook from SeminarDesk
	 * 
	 * Note: Incase facilitator id doesn't exists, it will create the facilitator
	 *
	 * @param array $notification Notification part of the Webhook request
	 * @return array Webhook response
	 */
	public function update_facilitator($notification)
	{
		$response = $this->put_facilitator( $notification );
		// add custom action hook
		do_action( 'wpsd_webhook_update_facilitator', $notification );
		return $response;
	}

	/**
	 * Deletes facilitator via webhook from SeminarDesk
	 *
	 * @param array $notification Notification part of the Webhook request
	 * @return array Webhook response
	 */
	public function delete_facilitator($notification)
	{
		$payload = (array)$notification['payload'];

		/**
		 * sd_cpt_facilitator
		 */
		$facilitator_deleted = Utils::delete_post_by_meta('sd_cpt_facilitator', 'sd_facilitator_id', $payload['id']);
		if ( empty($facilitator_deleted) ){
			return array(
				'status'		=> 404,
				'message'		=> 'Nothing to delete. Facilitator ID ' . $payload['id'] . ' does not exists',
				'action'		=> $notification['action'],
				'eventDateId'	=> $payload['id'],
			);
		}

		/**
		 * sd_txn_facilitators
		 */
		$term = get_term_by( 'name', $payload['id'], 'sd_txn_facilitators', ARRAY_A );
		$term_deleted = wp_delete_term( $term['term_id'], 'sd_txn_facilitators' );

		if ( $term_deleted === false ){
			return array(
				'status'		=> 404,
				'message'		=> 'Cannot delete term. Facilitator ID ' . $payload['id'] . ' does not exists',
				'action'		=> $notification['action'],
				'facilitatorId'	=> $payload['id'],
			);
		} elseif ( $term_deleted === 0 ){
			return array(
				'status'		=> 404,
				'message'		=> 'Attempt to delete default term',
				'action'		=> $notification['action'],
				'facilitatorId'	=> $payload['id'],
			);
		} elseif ( ( is_wp_error( $term_deleted ) ) ){
			return array(
				'status'		=> 404,
				'message'		=> 'Not deleted. Term corresponding with Facilitator ID ' . $payload['id'] . ' does not exists',
				'action'		=> $notification['action'],
				'facilitatorId'	=> $payload['id'],
			);
		}

		// add custom action hook
		do_action( 'wpsd_webhook_delete_facilitator', $notification );
		
		return array(
			'status'			=> 200,
			'message'			=> 'Facilitator deleted',
			'action'			=> $notification['action'],
			'facilitatorId'		=> $payload['id'],
			'facilitatorWpId'	=> $facilitator_deleted->ID,
		);
	}

	/**
	 * Creates or updates label group and its labels via webhook from SeminarDesk
	 *
	 * @param array $notification Notification part of the Webhook request
	 * @return array Webhook response
	 */
	public function put_label( $notification )
	{
		$lg_payload = (array)$notification['payload']; // payload of the request in JSON
		$sd_webhook = $notification;
		unset($sd_webhook['payload']);
		$label_title = wp_strip_all_tags( $lg_payload['name'] );

		/**
		 * sd_txn_labels
		 */
		// labelGroup term and its meta
		$lg_term_name = 'lg_id_' . $lg_payload['id'];
		$lg_term_ids = Utils::set_term( 'sd_txn_labels', $lg_term_name, $label_title, $label_title ); // Note: set ID as name to be queryable
		// return error if $term_ids is of type WP_Error
		if (is_wp_error($lg_term_ids)){
			return $lg_term_ids;
		}
		$lg_term_id = $lg_term_ids['term_id'];
		// add sd data as meta to term 
		$lg_term_meta_id = Utils::set_term_meta( $lg_term_ids, 'sd_data', $lg_payload );
		if (is_wp_error($lg_term_meta_id)){
			return $lg_term_meta_id;
		}
		// set sd_id as queryable meta data
		$tec_term_meta_id = WebhookUtils::set_term_meta( $lg_term_ids, 'sd_id', $lg_payload['id'] );
		if (is_wp_error($tec_term_meta_id)){
			return $tec_term_meta_id;
		}
		
		/**
		 * sd_cpt_label
		 */
		// set labelGroup post
		$lg_query = Utils::get_query_by_meta( 'sd_cpt_label', 'sd_labelGroup_id', $lg_payload['id']);
		$lg_wp_id = $lg_query->post->ID ?? 0;
		$lg_count = $lg_query->post_count;
		// define metadata of the event
		$lg_meta_input = array(
			'sd_labelGroup_id'	=> $lg_payload['id'],
			'sd_data'			=> $lg_payload,
			'sd_webhook'		=> $sd_webhook,
		);
		// set attributes of the new event
		$lg_attr = array(
			'post_type'		=> 'sd_cpt_label',
			'post_title'	=> $label_title,
			'post_name'		=> $label_title,
			'post_author'	=> get_current_user_id(),
			'post_status'	=> 'publish',
			'meta_input'	=> $lg_meta_input,
			// 'tax_input'	=> $txn_input,
		);
		// create new post or update post with existing post id
		if ( !empty($lg_wp_id) ) {
			$lg_attr['ID'] = $lg_wp_id;
			$message = 'Label Group updated';
			$lg_wp_id = wp_update_post( wp_slash($lg_attr), true );
		} else {
			$message = 'Label Group created';
			$lg_wp_id = wp_insert_post( wp_slash($lg_attr), true );
		}

		// set featured image
		$picture_url = TemplateUtils::get_value_by_language($lg_payload['pictureUrl']);
		$featured_image = Utils::featured_image_to_post( $lg_wp_id, $picture_url );

		// return error if $post_id is of type WP_Error
		if (is_wp_error($lg_wp_id)){
			return $lg_wp_id;
		}

		$txn_input = array();
		$labelGroup = $lg_payload ?? null;
		if ( !empty( $labelGroup ) ){
			Utils::add_term_tax_input( $txn_input, $lg_term_name, 'sd_txn_labels' );
		}else{
			$txn_input['sd_txn_labels'] = array();
		}
		
		// set label posts and term object
		$labels_payload = $lg_payload['labels'];

		// delete label terms and posts if not included in the labelGroup anymore
		$label_posts = get_posts( array(
			'post_type'		=> 'sd_cpt_label',
			'post_parent'	=> $lg_wp_id,
		) );
		foreach ( $label_posts as $label_post ){
			Utils::exclude_label_post( $label_post, $labels_payload );
		}
		$label_terms = get_terms( array(
			'taxonomy'		=> 'sd_txn_labels',
			'parent'		=> $lg_term_id,
			'hide_empty'	=> false,
		) );
		foreach ( $label_terms as $label_term ){
			Utils::exclude_label_term( $label_term, $labels_payload );
		}
		
		foreach ( $labels_payload as $label_payload ) {
			$label_query = Utils::get_query_by_meta( 'sd_cpt_label', 'sd_label_id', $label_payload['id']);
			$label_post_id = $label_query->post->ID ?? 0;
			$label_count = $label_query->post_count;
			// define metadata of the event
			$label_meta_input = array(
				'sd_label_id'	=> $label_payload['id'],
				'sd_data'		=> $label_payload,
				'sd_webhook'	=> $sd_webhook,
			);
			// set attributes of the new event
			$label_slug = Utils::unique_post_slug( $label_payload['name'], $label_post_id );
			$label_attr = array(
				'post_type'	 	=> 'sd_cpt_label',
				'post_title'	=> $label_payload['name'],
				'post_name'		=> $label_slug,
				'post_author'	=> get_current_user_id(),
				'post_status'	=> 'publish',
				'post_parent'	=> $lg_wp_id,
				'meta_input'	=> $label_meta_input,
				'tax_input'		=> $txn_input,
			);
			// create or update post with existing post id
			if ( !empty($label_post_id) ) {
				$label_attr['ID'] = $label_post_id;
				$label_post_id = wp_update_post( wp_slash($label_attr), true );
			} else {
				$label_post_id = wp_insert_post( wp_slash($label_attr), true );
			}
			// return error if $post_id is of type WP_Error
			if (is_wp_error($label_post_id)){
				return $label_post_id;
			}
			// label terms and their meta 
			$label_term_name = 'l_id_' .  $label_payload['id'];
			$label_term_description = $label_payload['name'];
			// Note: labels have duplicated 'name', results in slug of 'name' . '_' . 'parent slug' (e.g. label01 -> label01-label-group-02). This is default behavior of WordPress for terms
			$label_ids = Utils::set_term( 'sd_txn_labels', $label_term_name, $label_term_description, $label_slug, $lg_term_id );
			if (is_wp_error($label_ids)){
				return $label_ids;
			}
			$label_meta_id = Utils::set_term_meta( $label_ids, 'sd_data', $label_payload );
			if (is_wp_error($label_meta_id)){
				return $label_meta_id;
			}
		}

		// add custom action hook
		do_action( 'wpsd_webhook_put_label', $notification );

		return array(
			'status'			=> 200,
			'message'			=> $message,
			'action'			=> $notification['action'],
			'labelGroupId'		=> $lg_payload['id'],
			'labelGroupWpId'	=> $lg_wp_id,
			'labelCount'		=> $label_count,
		);
	}

/**
	 * Creates label group and its labels via webhook from SeminarDesk
	 * 
	 * Note: Incase facilitator id already exists, it will update existing facilitator
	 *
	 * @param array $notification Notification part of the Webhook request
	 * @return array Webhook response
	 */
	public function create_label($notification)
	{
		$response = $this->put_label( $notification );
		// add custom action hook
		do_action( 'wpsd_webhook_create_label', $notification );
		return $response;
	}

	/**
	 * Updates label group and its labels via webhook from SeminarDesk
	 * 
	 * Note: Incase id doesn't exists, it will create the label group/label
	 *
	 * @param array $notification
	 * @return array Webhook response
	 */
	public function update_label($notification)
	{
		$response = $this->put_label( $notification );
		// add custom action hook
		do_action( 'wpsd_webhook_update_label', $notification );
		return $response;
	}

	/**
	 * Deletes label group and its labels via webhook from SeminarDesk
	 *
	 * @param array $notification Notification part of the Webhook request
	 * @return array Webhook response
	 */
	public function delete_label($notification)
	{
		$payload = (array)$notification['payload'];
		$lg_query = Utils::get_query_by_meta('sd_cpt_label', 'sd_labelGroup_id', $payload['id'] );
		$lg_post_id = $lg_query->post->ID;

		/**
		 * sd_cpt_label
		 */
		// delete all labels of the labelGroup
		$label_posts = get_posts( array(
			'post_type' => 'sd_cpt_label',
			'post_parent'   => $lg_post_id,
		) );
		foreach( $label_posts as $label_post ){
			$label_deleted = wp_delete_post( $label_post->ID );
			$label_wp_ids[] = $label_deleted->ID;
		}
		// delete labelGroup
		$lg_deleted = wp_delete_post( $lg_post_id );
		if ( empty($lg_deleted) ){
			return array(
				'status'		=> 404,
				'message'		=> 'Nothing to delete. LabelGroup ID ' . $payload['id'] . ' does not exists',
				'action'		=> $notification['action'],
				'labelGroupId'	=> $payload['id'],
			);
		}

		/**
		 * sd_txn_labels
		 */
		// get lg term and all label terms
		$lg_term_name = 'lg_id_' . $payload['id'];
		$lg_term = get_term_by( 'name', $lg_term_name, 'sd_txn_labels' );
		$label_terms = get_terms( array(
			'taxonomy'		=> 'sd_txn_labels',
			'parent'		=> $lg_term->term_id,
			'hide_empty'	=> false,
		) );
		// delete labelGroup
		$term_deleted = wp_delete_term( $lg_term->term_id, 'sd_txn_labels' );
		if ( $term_deleted === false ){
			return array(
				'status'		=> 404,
				'message'		=> 'Cannot delete term. Label Group ID ' . $payload['id'] . ' does not exists',
				'action'		=> $notification['action'],
				'labelGroupId'	=> $payload['id'],
			);
		} elseif ( $term_deleted === 0 ){
			return array(
				'status'		=> 404,
				'message'		=> 'Attempt to delete default term',
				'action'		=> $notification['action'],
				'labelGroupId'	=> $payload['id'],
			);
		} elseif ( ( is_wp_error( $term_deleted ) ) ){
			return array(
				'status'		=> 404,
				'message'		=> 'Cannot delete term. Label Group ID ' . $payload['id'] . ' does not exists',
				'action'		=> $notification['action'],
				'labelGroupId'	=> $payload['id'],
			);
		}
		// delete all labels of the labelGroup
		foreach( $label_terms as $label_term ){
			$term_sd_data = get_term_meta( $label_term->term_id, 'sd_data', true );
			$term_deleted = wp_delete_term( $label_term->term_id, 'sd_txn_labels' );
			if ( $term_deleted === false ){
				return array(
					'status'	=> 404,
					'message'	=> 'Cannot delete term. Label ID ' . $term_sd_data['id'] . ' does not exists',
					'action'	=> $notification['action'],
					'labelId'	=> $term_sd_data['id'],
				);
			} elseif ( $term_deleted === 0 ){
				return array(
					'status'	=> 404,
					'message'	=> 'Attempt to delete default term',
					'action'	=> $notification['action'],
					'labelId'	=> $term_sd_data['id'],
				);
			} elseif ( ( is_wp_error( $term_deleted ) ) ){
				return array(
					'status'	=> 404,
					'message'	=> 'Cannot delete term. Label Group ID ' . $term_sd_data['id'] . ' does not exists',
					'action'	=> $notification['action'],
					'labelId'	=> $term_sd_data['id'],
				);
			}
			$label_ids[] = $term_sd_data['id'];
		}

		// add custom action hook
		do_action( 'wpsd_webhook_delete_label', $notification );

		return array(
			'status'			=> 200,
			'message'			=> 'Label Group deleted',
			'action'			=> $notification['action'],
			'groupLabelId'		=> $payload['id'],
			'groupLabelWpId'	=> $lg_deleted->ID,
			'labelIds'			=> $label_ids,
			'labelWpIds'		=> $label_wp_ids,
		);
	}

	/**
	 * deletes all SeminarDesk entries (cpts, txns and their terms) from the WordPress database via webhook
	 *
	 * @param array $notification Notification part of the Webhook request
	 * @return array Webhook response
	 */
	public function delete_all($notification)
	{
		$delete_all = SD_OPTION_VALUE['delete'];
		$debug = SD_OPTION_VALUE['debug'];
		if ( $delete_all !== false && $debug !== false ) {
			// Get SeminarDesk's posts and terms and delete them
			AdminUtils::delete_all_sd_objects();
			return array(
				'status'	=> 200,
				'message'	=> 'Plugin cleared - all cpt, txn, term deleted from the data base',
				'action'	=> $notification['action'],
			);
		}

		// add custom action hook
		do_action( 'wpsd_webhook_delete_all', $notification );

		return array(
			'status'	=> 405,
			'message'	=> 'Plugin not cleared - Not Allowed. Debug and Deleted All needs to be enabled at the admin page of the SD plugin',
			'action'	=> $notification['action'],
		);
	}
	/**
	 * updates database or other resources via webhook from SeminarDesk.
	 * 
	 * Note: add a case (if necessary) after updating the SeminarDesk plugin to maintain compatibility 
	 * 
	 * @param mixed $notification Notification part of the Webhook request
	 * @return array Webhook response
	 */
	public function update_plugin( $notification )
	{
		$payload = (array)$notification['payload'];
		switch ( $payload['update'] ){
			case 'sd_preview_available':
				global $wpdb;
				$wpdb->query( "UPDATE `wp_postmeta` SET `meta_key` = 'sd_preview_available' WHERE `meta_key` = 'preview_available'" );
				return array(
					'status'	=> 200,
					'message'	=> 'Plugin Update - Custom field preview_available renamed to sd_preview_available',
					'action'	=> $notification['action'],
				);
				break;
			default:
				return array(
					'status'	=> 404,
					'message'	=> 'Not Found - ' . $payload['update'] . '. This payload to update the SeminarDesk plugin is not defined',
					'action'	=> $notification['action'],
				);
		}
	}
}