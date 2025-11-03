<?php
/**
 * @package SeminardeskPlugin
 */

namespace Inc\Utils;

// exit if accessed directly
defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

use WP_Query;

/**
 * Collection of utilities for templates
 */
class TemplateUtils
{
	/**
	 * Retrieves or displays value by parsing a l10n array form SeminarDesk's payload
	 *
	 * @param array $array l10n formatted array from the payload provided by SeminarDesk (e.g. ['name'], ['title'] ...)
	 * @param string $lang_tag (Optional) select language by tag (e.g 'DE', 'EN' ...). Default value = 'DE'
	 * @param string $before (Optional) custom html markup before value (e.g. '<div class= "**custom-class*">'). Default value = ''
	 * @param string $after (Optional) custom html markup after value (e.g. '</div>'). Default value = ''
	 * @param boolean $echo (Optional) Whether to echo the date or return it. Default value: false
	 * @return string HTML markup of localized value or empty string
	 */
	public static function get_value_by_language( $array, $lang_tag = 'DE', $before = '', $after = '', $echo = false )
	{
		if ( !empty($array) ){
			$key = array_search($lang_tag, array_column($array, 'language') ) ?? false;
			// on failure get default language or first entry of the array
			if ( $key === false){
				$lang_default = 'DE';
				$key = array_search($lang_default, array_column($array, 'language'));
				if ( $key === false ){
					$key = '0';
				}
			}
	
			$response = !empty($array[$key]['value']) ? $before . $array[$key]['value'] . $after : '';
		} else {
			$response = '';
		}
		
		if ( $echo ){
			echo $response;
		}

		return (string)$response;
	}

	/**
	 * Retrieves or displays HTML markup for remote image url
	 *
	 * @param string $url (Required) image url
	 * @param string $width (Optional) image width. Default value ''
	 * @param string $height (Optional) image height. Default value ''
	 * @param string $alt (Optional) alternative text. Default value: ''
	 * @param string $before (Optional) HTML markup prepend to remote image (e.g. '<div class= "**custom-class*">'). Default value: '' 
	 * @param string $after (Optional) HTML markup append to remote image (e.g. '</div>'). Default value: ''
	 * @param boolean $echo (Optional) Whether to echo the date or return it. Default value: false
	 * @return string|null HTML markup of remote image or null
	 */
	public static function get_img_remote( $url, $width = '', $height = '', $alt = "remote image failed", $before = '', $after = '', $echo = false )
	{
		if ( is_string( $url ) && !empty( $url ) ){
			$url = esc_url( $url );
			$response = $before . '<img src="' . $url . '" alt="' . $alt . '" width="' . $width . '" height="' . $height . '"/>' . $after;
		}else{
			$response = null;
		}
		if ( $echo ){
			echo $response;
		}
		return $response;
	}

	/**
	 * Retrieves or displays the date ($begin_date) with optional end date ($date_begin . '-' . $end_date) in localized format.
	 * 
	 * @param integer $begin_date (Required) The date
	 * @param integer $end_date (Optional) End date. Default value: 0 
	 * @param string $begin_format (Optional) PHP date format for $begin_date. Default format: 'D. d.m.Y H:i'.
	 * @param string $end_format (Optional) PHP date format for $end_date. Default format: 'D. d.m.Y H:i'.
	 * @param string $before (Optional) HTML markup prepend to the date (e.g. '<div class= "**custom-class*">'). Default value: ''
	 * @param string $after (Optional) HTML markup append to the date (e.g. '</div>'). Default value: ''
	 * @param boolean $echo (Optional) Whether to echo the date or return it. Default value: false
	 * @return string|null HTML markup of date in localized format
	 * 
	 * Note: PHP date format parameter string https://www.php.net/manual/en/datetime.format.php
	 * 
	 */
	public static function get_date_span( $begin_date, $end_date = 0, $begin_format = '', $end_format ='',  $before = '', $after = '', $echo = false )
	{
		$begin_format = !empty($begin_format) ? $begin_format : 'D. d.m.Y H:i';
		$end_format = !empty($end_format) ? $end_format : 'D. d.m.Y H:i';
		$end_date = !empty($end_date) ? $end_date : 0;

		$date_formatted = array(
			'begin' => wp_date( $begin_format, $begin_date/1000),
			'end'  => wp_date( $end_format, $end_date/1000),
		);

		if ( !empty($date_formatted['begin']) && !empty($date_formatted['end']) ){
			$response = $before . $date_formatted['begin'] . ' – ' . $date_formatted['end'] . $after;
		}elseif ( !empty( $date_formatted['begin'] ) || $begin_date === $end_date ) {
			$response = $before . $date_formatted['begin'] . $after;
		}else{
			$response = null;
		}
		
		if ( $echo ){
			echo $response;
		}
		return $response;
	}

	/**
	 * **** exemplary **** 
	 * Retrieves or displays list of facilitators in html markup including permalink to facilitator details
	 * 
	 * @param array $facilitators (Required) The facilitators ids 
	 * @param string $before (Optional) HTML markup prepend to list of facilitators (e.g. '<div class= "**custom-class*">'). Default value: ''
	 * @param string $after (Optional) HTML markup append to list of facilitators (e.g. '</div>'). Default value: ''
	 * @param bool $echo (Optional) Whether to echo the date or return it. Default value: false
	 * @return string|null HTML markup of facilitators list
	 */
	public static function get_facilitators( array $facilitators, $before = '', $after = '' , $echo = false )
	{
		$facilitators_html = array();
		$ids = array();
		foreach ($facilitators as $facilitator){
			$ids[] = $facilitator['id'];
		}
		if ( !empty( $ids) ){
			$custom_query = new WP_Query(
				array(
					'post_type'		=> 'sd_cpt_facilitator',
					'post_status'	=> 'publish',
					'meta_key'		=> 'sd_facilitator_id',
					'meta_query'	=> array(
						'key'		=> 'sd_facilitator_id',
						'value'		=> $ids,
						'type'		=> 'numeric',
						'compare'	=> 'IN',
					),
				)
			);
			// loop to get facilitator name and link, create html markup to push in array
			if ( $custom_query->have_posts() ){
				while ($custom_query->have_posts()){
					$custom_query->the_post();
					$facilitator_html = '<a href="' . get_permalink($custom_query->post->ID) . '">' . get_the_title($custom_query->post) . '</a>';
						array_push($facilitators_html, $facilitator_html);
				}
			}
		}
		
		// wp_reset_query();
		wp_reset_postdata();

		if ( !empty($facilitators_html) ){
			// sort array of received facilitator names ascending
			sort($facilitators_html);
			$response = $before . implode(" | ",$facilitators_html) . $after;
		}else{
			$response = null;
		}
		if ( $echo ){
			echo $response;
		}
		return $response;
	}

	/**
	 * **** exemplary **** 
	 * Retrieves or displays a html markup list of defined number upcoming event dates for an event
	 * 
	 * @param int $event_id (Required) WordPress ID of the Event.
	 * @param array $status_lib (Optional) define custom strings for event date ['bookingPageStatus']. Default value: array('available' => 'available', 'fully_booked => 'fully_booked', 'limited' => 'limited', 'wait_list' => 'wait_list')
	 * @param int $number_dates (Optional) number of upcoming event dates showing in the list. Default value: -1 // all event dates
	 * @param string $date_begin_format (Optional) PHP date format for begin of event date. Default format: 'D. d.m.Y H:i'
	 * @param string $date_end_format (Optional) PHP date format end of event date. Default format: 'D. d.m.Y H:i'
	 * @param string $before  (Optional) HTML markup prepend to the event dates list (e.g. '<div class= "**custom-class*">'). Default value: ''
	 * @param string $after (Optional) HTML markup append to event dates list (e.g. '</div>'). Default value: ''
	 * @param boolean $echo (Optional) Whether to echo the date or return it. Default value: false
	 * @return string|null HTML markup of event date list
	 * 
	 * Note: 
	 * 	- PHP date format parameter string https://www.php.net/manual/en/datetime.format.php
	 */
	public static function get_event_dates_list( $event_id, $status_lib = null, $number_dates = -1 , $date_begin_format = '', $date_end_format = '', $before = '', $after = '' , $echo = false )
	{
		$timestamp_today = strtotime(wp_date('Y-m-d'));
		// $timestamp_today = strtotime('2020-04-01'); // for debugging
		$custom_query = new WP_Query(
			array(
				'post_type'			=> 'sd_cpt_date',
				'post_status'		=> 'publish',
				'posts_per_page'	=> $number_dates,
				'meta_key'	  		=> 'sd_date_begin',
				'orderby'			=> 'meta_value_num',
				'order'		 		=> 'ASC',
				'meta_query'		=> array(
					'relation'		=> 'AND',
					'condition1'	=> array(
						'key'		=> 'sd_date_begin',
						'value'		=> $timestamp_today*1000, //in ms
						'type'		=> 'NUMERIC',
						'compare'	=> '>=',
					),
					'condition2'	=> array(
						'key'		=> 'sd_event_id',
						'value'		=> $event_id,
						'type'		=> 'CHAR',
						'compare'	=> '=',
					),
				),
			)
		);
		$date_posts = $custom_query->get_posts();
		$dates = array();
		if ( $custom_query->have_posts() ){
			foreach ( $date_posts as $date_post) {
				$date = self::get_date_span( $date_post->sd_date_begin, $date_post->sd_date_end, $date_begin_format, $date_end_format );
				// rtrim() or wp_strip_all_tags...
				$title = wp_strip_all_tags($date_post->post_title) . ': ';
				$price = wp_strip_all_tags(self::get_value_by_language($date_post->sd_data['priceInfo']));
				$status = $date_post->sd_data['bookingPageStatus'];
				if ( empty( $status_lib ) ){
					$status_lib = array(
						'available'		=> 'available',
						'fully_booked'	=> 'fully_booked',
						'limited'		=> 'limited',
						'wait_list'		=> 'wait_list',
					);
				}

				$facilitators = self::get_facilitators($date_post->sd_data['facilitators']);
				$status_msg = $status_lib[$status];
				$venue_props = $date_post->sd_data['venue'];
				$venue = self::get_venue($venue_props);

				$date_props = array();
				array_push($date_props, $date, $facilitators, $price, $status_msg, $venue);
				$date_props = array_filter($date_props); // remove all empty values from array
				$date_html = '<li>' . $title . implode(', ', $date_props) . '</li>';
				array_push($dates, $date_html);
			}
		}

		if ( !empty($dates) ){
			$response = $before . '<ol>' . implode('', $dates) . '</ol>' . $after;
		}else{
			$response = null;
		}
		if ( $echo ){
			echo $response;
		}
		return $response;
	}

	/**
	 * **** exemplary **** 
	 * Retrieves or displays venue information form SeminarDesk's payload ['venue']
	 * 
	 * @param array $venue_props array with venue information provided by SeminarDesk ['venue']
	 * @param string $before (Optional) custom html markup before venue information (e.g. '<div class= "**custom-class*">'). Default value = ''
	 * @param string $after (Optional) custom html markup after venue information (e.g. '</div>'). Default value = ''
	 * @param bool $echo (Optional) Whether to echo the date or return it. Default value: false
	 * @return string|null HTML markup of venue information
	 */
	public static function get_venue( $venue_props, $before = '', $after = '' , $echo = false )
	{
		if ( !empty($venue_props) ){
			$venue_link = esc_url($venue_props['weblink']);
			$venue_info = array_slice($venue_props, 0, 5);
			$venue_info = array_filter( $venue_info) ; // remove all empty values from array
			if (!empty($venue_link)){
				$venue = '<a href="' . $venue_link . '">' . implode(', ', $venue_info) . '</a>';
			} elseif (!empty($venue_props)) {
				$venue = implode(', ', $venue_info);
			}
			else {
				$venue = '';
			}
			$response = $before .$venue . $after;
		}else{
			$response = null;
		}

		if ( $echo ){
			echo $response;
		}
		return $response;
	}
}
