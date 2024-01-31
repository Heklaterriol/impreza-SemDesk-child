<?php
/**
 * Collection of utility functions.
 *
 * @package HelloIVY
 */

defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

// includes
use Inc\Utils\TemplateUtils as Utils;
use Inc\Controllers\TemplateController;

/**
 * Register widget area
 * @return void 
 */
function ivy_register_custom_sidebar() {
	register_sidebar(
		array (
			'name' => 'Sidebar Agenda',
			'id' => 'agenda-sidebar',
			'description' => 'Agenda Sidebar',
			'before_widget' => '<div id="%1$s" class="agenda-content %2$s">',
			'after_widget' => "</div>",
			'before_title' => '<h2 class="widget-title">',
			'after_title' => '</h2>',
		)
	);
}

/**
 * Overrides templates
 * 
 * @param string $template 
 * @return mixed 
 */
function ivy_override_template( string $template ){
	// for agenda page
	if ( is_page( 'agenda' )){
		// echo 'agenda';
		$templates = array(
			'sd_txn_dates-upcoming',
			'sd_txn',
		);
		return TemplateController::set_template_enqueue_assets( $templates);
	}
	return $template;
}

/**
	 * Resets to the initial WP query stored in $wp_the_query
	 * 
	 * Note : the initial query is stored in another global named $wp_the_query
	 * 
	 * @return void 
	 */
function ivy_reset_query()
{
	global $wp_query, $wp_the_query;
	switch ( current_filter() ) {
		case 'get_footer':
			$wp_query = $wp_the_query;
		break;
	}
}

/**
 * overrides the current query with a custom query 
 * @param string $template path
 * @return string template path
 */
function ivy_override_query( string $template ){
	global $sd_query;
	if ( is_page('agenda' )) {
		$queried_object = get_queried_object();
		$timestamp_today = strtotime(wp_date('Y-m-d')); // current time
		// $timestamp_today = strtotime('2022-03-27'); // debugging
		$paged = ( get_query_var( 'page' ) ) ? absint( get_query_var( 'page' ) ) : 1;
		$sd_query = new WP_Query( array(
			'post_type'		=> 'sd_cpt_date',
			'post_status'	=> 'publish',
			// 'posts_per_page'   => 5, // debugging
			'paged'			=> $paged,
			'meta_key'		=> 'sd_date_begin',
			'orderby'		=> 'meta_value_num',
			'order'			=> 'ASC',
			'term_object'	=> $queried_object, // custom var
			'meta_query'	=> array(
				'relation'	=> 'AND',
				array(
					'key'		=> 'sd_date_begin',
					'value'		=> $timestamp_today*1000, //in ms
					'type'		=> 'numeric',
					'compare'	=> '>=',
				),
				array(
					'key'	=> 'sd_preview_available',
					'value'	=> true,
				),
			),
		) );
		// when loop is finished
		add_action( 'get_footer', 'ivy_reset_query', 100 );
	}
	return $template;
}

/**
 * Get array of facilitators with their properties (e.g. name, link, img, ID ...)
 * @param array $sd_ids (Required) The facilitator ids 
 * @return array|null Array with facilitators and their properties
 */
function ivy_get_facilitators( array $sd_ids ){
	$response = null;
	$ids = array();
	foreach ($sd_ids as $sd_id){
		$ids[] = $sd_id['id'];
	}
	if ( !empty( $ids) ){
		$wp_query_facilitators = new WP_Query(
			array(
				'post_type'		=> 'sd_cpt_facilitator',
				'post_status'	=> 'publish',
				'posts_per_page'	=> -1, // all dates
				'meta_key'		=> 'sd_facilitator_id',
				'meta_query'	=> array(
					'key'		=> 'sd_facilitator_id',
					'value'		=> $ids,
					'compare'	=> 'IN',
				),
			)
		);
		$facilitators_wp_posts = $wp_query_facilitators->get_posts();
		if ( $wp_query_facilitators->have_posts() ){
			$response = $facilitators_wp_posts;
		}
	}
	wp_reset_query();
	wp_reset_postdata();
	
	return $response;
}

/**
 * **** Copied & modified from /wp-content/plugins/de.seminardesk.wordpress/inc/Utils/ **** 
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
 * @return array List of dates with list of fields ['title', 'date', 'facilitators', 'price', 'status_msg', 'venue]
 * 
 * Note: 
 * 	- PHP date format parameter string https://www.php.net/manual/en/datetime.format.php
 */
function ivy_get_event_dates_list( $event_id, $status_lib = null, $number_dates = -1 , $date_begin_format = '', $date_end_format = '', $before = '', $after = '' , $echo = false )
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
  $dates_html = array();
  if ( $custom_query->have_posts() ){
    foreach ( $date_posts as $date_post) {
      $date = Utils::get_date_span( $date_post->sd_date_begin, $date_post->sd_date_end, $date_begin_format, $date_end_format );
      // rtrim() or wp_strip_all_tags...
      $title = wp_strip_all_tags($date_post->post_title);
      $price = wp_strip_all_tags(Utils::get_value_by_language($date_post->sd_data['priceInfo']));
      $status = $date_post->sd_data['bookingPageStatus'];
      if ( empty( $status_lib ) ){
        $status_lib = array(
          'available'		=> 'available',
          'fully_booked'	=> 'fully_booked',
          'limited'		=> 'limited',
          'wait_list'		=> 'wait_list',
        );
      }

      $facilitators = Utils::get_facilitators($date_post->sd_data['facilitators']);
      $status_msg = $status_lib[$status];
      $venue_props = $date_post->sd_data['venue'];
      // Remove 1st element (id) from array (fixing bug from seminardesk plugin)
      if (array_key_exists('id', $venue_props)) { unset($venue_props['id']); }
      $venue = Utils::get_venue($venue_props);

      $date_props = [
          'title' => $title, 
          'date' => $date, 
          'facilitators' => $facilitators, 
          'price' => $price, 
          'status_msg' => $status_msg, 
          'venue' => $venue, 
      ];
      array_push($dates, $date_props);
      
      $date_html = '<li>' . $title . implode(', ', $date_props) . '</li>';
      array_push($dates_html, $date_html);
    }
  }

  if ( $echo && !empty($dates_html) ){
    echo $before . '<ol>' . implode('', $dates_html) . '</ol>' . $after;
  }
  return $dates;
}

/**
 *  Get Array of current dates
 * @return WP_Post[]|int[]|null 
 */
function ivy_get_dates_current(){
	$response = null;
	$wp_timestamp_today = strtotime(wp_date('Y-m-d'));
	// $wp_timestamp_today = strtotime('2022-06-03'); // for debugging
	$wp_query_dates = new WP_Query(
		array(
			'post_type'			=> 'sd_cpt_date',
			'post_status'		=> 'publish',
			'posts_per_page'	=> -1, // all dates
			'meta_key'	  		=> 'sd_date_begin',
			'orderby'			=> 'meta_value_num',
			'order'		 		=> 'ASC',
			'meta_query'		=> array(
				'relation' => 'And',
				'condition_start' => array(
					'key'			=> 'sd_date_begin',
					'value'		=> $wp_timestamp_today*1000, //in ms
					'type'		=> 'NUMERIC',
					'compare'	=> '<=',
				),
				'condition_end'	=> array(
					'key'			=> 'sd_date_end',
					'value'		=> $wp_timestamp_today*1000, //in ms
					'type'		=> 'NUMERIC',
					'compare'	=> '>=',
				),
			),
		)
	);
	if ( $wp_query_dates->have_posts() ){
		$date_wp_posts = $wp_query_dates->get_posts();
		$response = $date_wp_posts;
	}
	wp_reset_query();
	wp_reset_postdata();
	return $response;
}

/**
 * Get Array of current and upcoming dates of an selected event with their properties
 * @param string $sd_event_id SeminarDesk ID of the event
 * @return null|array Array with facilitators and their properties
 */
function ivy_get_dates_upcoming_by_id( string $sd_event_id ){
	$response = null;
	$wp_timestamp_today = strtotime(wp_date('Y-m-d'));
	// $wp_timestamp_today = strtotime('2021-08-05'); // for debugging
	$wp_query_dates = new WP_Query(
		array(
			'post_type'			=> 'sd_cpt_date',
			'post_status'		=> 'publish',
			'posts_per_page'	=> -1, // all dates
			'meta_key'	  		=> 'sd_date_begin',
			'orderby'			=> 'meta_value_num',
			'order'		 		=> 'ASC',
			'meta_query'		=> array(
				'relation'		=> 'AND',
				'condition1'	=> array(
					'key'		=> 'sd_event_id',
					'value'		=> $sd_event_id,
					'type'		=> 'CHAR',
					'compare'	=> '=',
				),
				'condition2'	=> array(
					'relation' => 'OR',
					'condition_start' => array(
						'key'		=> 'sd_date_begin',
						'value'		=> $wp_timestamp_today*1000, //in ms
						'type'		=> 'NUMERIC',
						'compare'	=> '>=',
					),
					'condition_end'	=> array(
						'key'		=> 'sd_date_end',
						'value'		=> $wp_timestamp_today*1000, //in ms
						'type'		=> 'NUMERIC',
						'compare'	=> '>=',
					),
				),
			),
		)
	);
	if ( $wp_query_dates->have_posts() ){
		$date_wp_posts = $wp_query_dates->get_posts();
		$response = $date_wp_posts;
	}
	wp_reset_query();
	wp_reset_postdata();
	return $response;
}

/**
 * Get Array of all current and upcoming dates with their properties
 * @return null|array Array with facilitators and their properties
 */
function ivy_get_dates_upcoming_all(){
	$response = null;
	$wp_timestamp_today = strtotime(wp_date('Y-m-d'));
	// $wp_timestamp_today = strtotime('2022-04-19'); // for debugging

	$wp_query_dates = new WP_Query(
		array(
			'post_type'				=> 'sd_cpt_date',
			'post_status'			=> 'publish',
			// 'posts_per_page'	=> 10,
			// 'no_found_rows'	=> true, // make your query bail after it found the the set posts per page - source: https://wordpress.stackexchange.com/a/181553
			'posts_per_page'	=> -1, // all dates
			'meta_key'				=> 'sd_date_begin',
			'orderby'					=> 'meta_value_num',
			'order'						=> 'ASC',
			'meta_query'			=> array(
				'relation'				=> 'OR',
				'condition_start'	=> array(
					'key'			=> 'sd_date_begin',
					'value'		=> $wp_timestamp_today*1000, //in ms
					'type'		=> 'NUMERIC',
					'compare'	=> '>=',
				),
				'condition_end'		=> array(
					'key'			=> 'sd_date_end',
					'value'		=> $wp_timestamp_today*1000, //in ms
					'type'		=> 'NUMERIC',
					'compare'	=> '>=',
				),
			),
		),
	);

	$date_wp_posts = $wp_query_dates->get_posts();
	if ( $wp_query_dates->have_posts() ){
		$response = $date_wp_posts;
	}
	wp_reset_query();
	wp_reset_postdata();
	return $response;
}

/**
 * Get Array of all current and upcoming dates depending on search string
 * @return null|array Array with facilitators and their properties
 */
function ivy_get_dates_upcoming_by_search(){
	$response = null;
	$wp_timestamp_today = strtotime(wp_date('Y-m-d'));
	// $wp_timestamp_today = strtotime('2022-04-19'); // for debugging

	$wp_query_dates = new WP_Query(
		array(
			's'								=> !empty($_REQUEST[ 'search' ]) ? $_REQUEST[ 'search' ] : null, // search parameter
			'post_type'				=> 'sd_cpt_date',
			'post_status'			=> 'publish',
			'posts_per_page'	=> -1, // all dates
			'meta_key'				=> 'sd_date_begin',
			'orderby'					=> 'meta_value_num',
			'order'						=> 'ASC',
			'meta_query'			=> array(
				'relation'				=> 'OR',
				'condition_start'	=> array(
					'key'			=> 'sd_date_begin',
					'value'		=> $wp_timestamp_today*1000, //in ms
					'type'		=> 'NUMERIC',
					'compare'	=> '>=',
				),
				'condition_end'		=> array(
					'key'			=> 'sd_date_end',
					'value'		=> $wp_timestamp_today*1000, //in ms
					'type'		=> 'NUMERIC',
					'compare'	=> '>=',
				),
			),
		),
	);

	$dates_wp_posts = $wp_query_dates->get_posts();
	if ( $wp_query_dates->have_posts() ){
		$response = $dates_wp_posts;
	}
	wp_reset_query();
	wp_reset_postdata();
	return $response;
}

/**
 * Get Array of all current and upcoming dates with their properties depending on selected month
 * @return null|array Array with facilitators and their properties
 */
function ivy_get_dates_upcoming_by_month(){
	$response = null;

	$month_selected = isset($_GET['date']) ? $_GET['date'] : '';
	$wp_timestamp_today = strtotime(wp_date('Y-m-d'));
	// $wp_timestamp_today = strtotime('2022-04-30'); // debugging 
	if ( $month_selected === 'en ce moment' ){
		$wp_query_dates = new WP_Query(
			array(
				'post_type'				=> 'sd_cpt_date',
				'post_status'			=> 'publish',
				'posts_per_page'	=> -1, // all dates
				'meta_key'				=> 'sd_date_begin',
				'orderby'					=> 'meta_value_num',
				'order'						=> 'ASC',
				'meta_query'			=> array(
					'relation'				=> 'AND',
					'condition_start'	=> array(
						'key'			=> 'sd_date_begin',
						'value'		=> $wp_timestamp_today*1000, //in ms
						'type'		=> 'NUMERIC',
						'compare'	=> '<=',
					),
					'condition_end'		=> array(
						'key'			=> 'sd_date_end',
						'value'		=> $wp_timestamp_today*1000, //in ms
						'type'		=> 'NUMERIC',
						'compare'	=> '>=',
					),
				),
			),
		);
	}else {
		// convert french date into timestamp
		$month_fr = explode(' ',trim($month_selected))[0]; //get just the month from the string
		$year = explode(' ',trim($month_selected))[1]; //get just the year from the string
		// $month_num = null;
		switch ( $month_fr ) {
			case 'janvier':
				$month_num = 1;
				break;
			case 'février':
				$month_num = 2;
				break;
			case 'mars':
				$month_num = 3;
				break;
			case 'avril':
				$month_num = 4;
				break;
			case 'mai':
				$month_num = 5;
				break;
			case 'juin':
				$month_num = 6;
				break;
			case 'juillet':
				$month_num = 7;
				break;
			case 'août':
				$month_num = 8;
				break;
			case 'septembre':
				$month_num = 9;
				break;
			case 'octobre':
				$month_num = 10;
				break;
			case 'novembre':
				$month_num = 11;
				break;
			case 'décembre':
				$month_num = 12;
				break;
		}
		// get timestamps ... source: https://stackoverflow.com/a/4702714
		$first_minute_time = mktime(0, 0, 0, $month_num, 1, (int)$year);
		$last_minute_time = mktime(23, 59, 59, $month_num, date('t', $first_minute_time), (int)$year);
		
		$month_selected_time = wp_date( 'm-Y', $first_minute_time ) === wp_date( 'm-Y', $wp_timestamp_today ) ? $wp_timestamp_today : $first_minute_time;

		$wp_query_dates = new WP_Query(
			array(
				'post_type'				=> 'sd_cpt_date',
				'post_status'			=> 'publish',
				'posts_per_page'	=> -1, // all dates
				'meta_key'				=> 'sd_date_begin',
				'orderby'					=> 'meta_value_num',
				'order'						=> 'ASC',
				'meta_query'			=> array(
					'relation'				=> 'AND',
					'condition_start'	=> array(
						'key'			=> 'sd_date_begin',
						// 'value'		=> $first_minute*1000, //in ms
						'value'		=> $month_selected_time*1000, //in ms
						'type'		=> 'NUMERIC',
						'compare'	=> '>=',
					),
					'condition_end'		=> array(
						'key'			=> 'sd_date_begin',
						'value'		=> $last_minute_time*1000, //in ms
						'type'		=> 'NUMERIC',
						'compare'	=> '<=',
					),
				),
			),
		);
	}

	$date_wp_posts = $wp_query_dates->get_posts();
	if ( $wp_query_dates->have_posts() ){
		$response = $date_wp_posts;
	}
	wp_reset_query();
	wp_reset_postdata();
	return $response;
}

/**
 * Get Array of all current and upcoming dates with their properties depending on selected level
 * @return null|array Array with facilitators and their properties
 */
function ivy_get_dates_upcoming_by_level(){
	$response = null;
	$wp_timestamp_today = strtotime(wp_date('Y-m-d'));
	// $wp_timestamp_today = strtotime('2022-04-19'); // for debugging

	// needs to query events to gather sd event ids which have a certain label, since dates don't hold this information (missing sd webhook feature)
	// query events for labels of the group "level"
	$wp_query_events = new WP_Query(
		array(
			'post_type'				=> 'sd_cpt_event',
			'post_status'			=> 'publish',
			'posts_per_page'	=> -1, // all events
			'tax_query'	=> array(
				array(
					'taxonomy'	=> 'sd_txn_labels',
					'field'			=> 'slug',
					'terms'			=> !empty( $_GET['level'] ) ? $_GET['level'] : '',
				),
			),
		)
	);
	$event_sd_ids = array();
	foreach( $wp_query_events->posts as $event_wp_post ){
		array_push( $event_sd_ids, $event_wp_post->sd_event_id );
	}
	
	if( !empty( $event_sd_ids ) ){
		$wp_query_dates = new WP_Query(
			array(
				's'								=> !empty($_REQUEST[ 'search' ]) ? $_REQUEST[ 'search' ] : null, // search parameter
				'post_type'				=> 'sd_cpt_date',
				'post_status'			=> 'publish',
				'posts_per_page'	=> -1, // all dates
				'meta_key'				=> 'sd_date_begin',
				'orderby'					=> 'meta_value_num',
				'order'						=> 'ASC',
				'meta_query'			=> array(
					'relation' => 'AND',
					array(
						'key' => 'sd_event_id',
						'value' => $event_sd_ids,
						'compare' => 'IN',
					),
					array(
						'relation'				=> 'OR',
						'condition_start'	=> array(
							'key'			=> 'sd_date_begin',
							'value'		=> $wp_timestamp_today*1000, //in ms
							'type'		=> 'NUMERIC',
							'compare'	=> '>=',
						),
						'condition_end'		=> array(
							'key'			=> 'sd_date_end',
							'value'		=> $wp_timestamp_today*1000, //in ms
							'type'		=> 'NUMERIC',
							'compare'	=> '>=',
						),
					),
				),
			),
		);
		$response = $wp_query_dates->get_posts();
	}
	wp_reset_query();
	wp_reset_postdata();
	return $response;
}

/**
 * 
 * @param Object $date
 * @param integer $category
 * @return boolean
 */
function sd_date_has_category($date, $category){
  $date_categories = get_the_terms($date, 'sd_txn_labels');
  foreach ($date_categories as $date_category) {
    if (in_array($category, [
        $date_category->term_id, 
        $date_category->slug, 
        $date_category->description
    ])) {
      return true;
    }
  }
  return false;
}

/**
 * Get Array of all current and upcoming dates with their properties depending on selected category
 * @param int $category_id (optional)
 * @return null|array Array with facilitators and their properties
 */
function ivy_get_dates_upcoming_by_category($category_id = 0){
	$response = null;
	$wp_timestamp_today = strtotime(wp_date('Y-m-d'));
	// $wp_timestamp_today = strtotime('2022-04-19'); // for debugging

	// needs to query events to gather sd event ids which have a certain label, since dates don't hold this information (missing sd webhook feature)
	// query events for labels of the group "category"
	$wp_query_events = new WP_Query(
		array(
			'post_type'				=> 'sd_cpt_event',
			'post_status'			=> 'publish',
			'posts_per_page'	=> -1, // all events
			'tax_query'	=> array(
				array(
					'taxonomy'	=> 'sd_txn_labels',
					'field'			=> 'slug',
					'terms'			=> !empty( $_GET['category'] ) ? $_GET['category'] : $category_id,
				),
			),
		)
	);
	$event_sd_ids = array();
	foreach( $wp_query_events->posts as $event_wp_post ){
		array_push( $event_sd_ids, $event_wp_post->sd_event_id );
	}
	
	$request_search = isset($_REQUEST[ 'search' ]) ? $_REQUEST[ 'search' ] : '';
	if ( !empty( $event_sd_ids ) ){
		$wp_query_dates = new WP_Query(
			array(
				's'								=> !empty($request_search) ? $request_search : null, // search parameter
				'post_type'				=> 'sd_cpt_date',
				'post_status'			=> 'publish',
				'posts_per_page'	=> -1, // all dates
				'meta_key'				=> 'sd_date_begin',
				'orderby'					=> 'meta_value_num',
				'order'						=> 'ASC',
				'meta_query'			=> array(
					'relation' => 'AND',
					array(
						'key' => 'sd_event_id',
						'value' => $event_sd_ids,
						'compare' => 'IN',
					),
					array(
						'relation'				=> 'OR',
						'condition_start'	=> array(
							'key'			=> 'sd_date_begin',
							'value'		=> $wp_timestamp_today*1000, //in ms
							'type'		=> 'NUMERIC',
							'compare'	=> '>=',
						),
						'condition_end'		=> array(
							'key'			=> 'sd_date_end',
							'value'		=> $wp_timestamp_today*1000, //in ms
							'type'		=> 'NUMERIC',
							'compare'	=> '>=',
						),
					),
				),
			),
		);
		$response = $wp_query_dates->get_posts();
	}
	wp_reset_query();
	wp_reset_postdata();
	return $response;
}

/**
 * get SD label and its properties by ID from a collection of SD labels
 * 
 * Source: https://stackoverflow.com/questions/5517255/remove-style-attribute-from-html-tags
 * 
 * @param array $labels_sd collection of labels from SeminarDesk
 * @param string $label_sd_id 
 * @return array 
 */
function ivy_get_label( array $labels_sd, int $label_sd_id ){
	$label_sd = array_filter( $labels_sd, function ( $search_item ) use ( $label_sd_id ){
			return ( $search_item['id'] === $label_sd_id ? true : false ); 
		}
	);
	return $label_sd;
}

/**
 * Remove style from HTML tags
 * 
 * @param string $html_input original html code
 * @return string|string[]|null stripped html code
 */
function ivy_strip_html_styles( string $html_input ){
	$html_output = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $html_input);
	return $html_output;
}

/**
 * Remove html tags from a string
 * 
 * @param string $html_input original html code
 * @param array $search html tags to remove
 * @param array $replace Optional: string to replace html tags from $search
 * @return string stripped html code
 */
function ivy_strip_html_tags( string $html_input, array $search, string $replace = '' ){
	$html_output = str_replace( 
		$search,
		$replace, 
		$html_input 
	);
	return $html_output;
}

/**
 * echo the formatted start and end (from-till) of a date
 * 
 * @param int $date_sd_from 
 * @param int $date_sd_till 
 * @return void 
 */
function ivy_the_date_duration( int $date_sd_from, int $date_sd_till ){
	$month_no_abb = array( 5, 6, 8 );
	// same day?
	if ( wp_date( 'd-m-Y', $date_sd_from ) !== wp_date( 'd-m-Y', $date_sd_till ) ){
		// from
		if ( in_array( wp_date( 'm', $date_sd_from ), $month_no_abb ) ){
			$format_date_start = wp_date( 'i', $date_sd_from ) != '00' ? 'l j M - G\Hi' : 'l j M - G\H';
		}else{
			$format_date_start = wp_date( 'i', $date_sd_from ) != '00' ? 'l j M. - G\Hi' : 'l j M. - G\H';
		}
		// till
		if ( in_array( wp_date( 'm', $date_sd_till ), $month_no_abb ) ){
			$format_date_end = wp_date( 'i', $date_sd_till ) != '00' ? 'l j M Y - G\Hi' : 'l j M Y - G\H';
		}else{
			$format_date_end = wp_date( 'i', $date_sd_till ) != '00' ? 'l j M. Y - G\Hi' : 'l j M. Y - G\H';
		}
	}else{
		// from
		if ( in_array( wp_date( 'm', $date_sd_from ), $month_no_abb ) ){
			$format_date_start = wp_date( 'i', $date_sd_from ) != '00' ? 'l j M Y - G\Hi' : 'l j M Y - G\H';
		}else{
			$format_date_start = wp_date( 'i', $date_sd_from ) != '00' ? 'l j M. Y - G\Hi' : 'l j M. Y - G\H';
		}
		// till
		$format_date_end = wp_date( 'i', $date_sd_till ) != '00' ? 'G\Hi' : 'G\H';
	}
	echo '<div class="start">' . wp_date( $format_date_start, $date_sd_from ) . '</div>';
	echo '<div class="arrow"><i class="fas fa-arrow-right"></i></div>';
	echo '<div class="end">' . wp_date( $format_date_end, $date_sd_till ) . '</div>';
}

/**
 * echo the attendanceType of the date
 * @param mixed $date_sd_data 
 * @return void 
 */
function ivy_get_the_date_attendanceType( $date_sd_data ){
	$date_sd_af = $date_sd_data['additionalFields'];
	$date_sd_attendance_type = $date_sd_data['attendanceType'];
	// online date fully booked with registration required or canceled?
	if( $date_sd_af['Online_Required_Registration'] === true && $date_sd_data['bookingPageStatus'] === 'fully_booked' && ( $date_sd_attendance_type === 'ONLINE' || $date_sd_attendance_type === 'SELECTABLE' ) ){
		$access_online_html = '<div class="ivy-red ivy-full">' . IVY_STRINGS['online'] . '</div>';
	}elseif(  ( $date_sd_attendance_type === 'ONLINE' || $date_sd_attendance_type === 'SELECTABLE' ) && $date_sd_data['bookingPageStatus'] === 'canceled' ){
		$access_online_html = '<div class="ivy-red ivy-canceled">' . IVY_STRINGS['online'] . '</div>';
	}elseif( $date_sd_attendance_type === 'ONLINE' || $date_sd_attendance_type === 'SELECTABLE' ){
		$access_online_html = IVY_STRINGS['online'];
	}else{
		$access_online_html = null;
	}
	// onsite date fully booked with registration required or canceled?
	$close_onsite_reg = $date_sd_af['Onsite_reg_fully booked_and_Online_reg_still_open']; // CF to close onsite registration, but keep online registration open
	if( $date_sd_af['Onsite_Required_Registration'] === true && ( $date_sd_data['bookingPageStatus'] === 'fully_booked' || $close_onsite_reg === true ) && ( $date_sd_attendance_type === 'ON_SITE' || $date_sd_attendance_type === 'SELECTABLE' ) ){
		$access_onsite_html = '<div class="ivy-red ivy-full">' . IVY_STRINGS['onsite'] . '</div>';
	}elseif(  ( $date_sd_attendance_type === 'ON_SITE' || $date_sd_attendance_type === 'SELECTABLE' ) && $date_sd_data['bookingPageStatus'] === 'canceled' ){
		$access_onsite_html = '<div class="ivy-red ivy-canceled">' . IVY_STRINGS['onsite'] . '</div>';
	}elseif( $date_sd_attendance_type === 'ON_SITE' || $date_sd_attendance_type === 'SELECTABLE' ){
		$access_onsite_html = IVY_STRINGS['onsite'];
	}else{
		$access_onsite_html = null;
	}
	//attendance type
	echo !empty($access_online_html) ? $access_online_html : null;
	echo $date_sd_attendance_type === 'SELECTABLE' ? '<div class="separator">|</div>' : null;
	echo !empty($access_onsite_html) ? $access_onsite_html : null;
}

/**
 * echo the levels of the date
 * @param int $date_post_id 
 * @param string $before 
 * @param string $after 
 * @return void 
 */
function ivy_get_the_date_levels( int $date_post_id, $before = '', $after = '' ){
	$term_level_parent = get_term_by( 'name', 'lg_id_5', 'sd_txn_labels');
	$levels_args = array( 'parent' => $term_level_parent->term_id );
	$terms_level = wp_get_post_terms( $date_post_id, 'sd_txn_labels', $levels_args );
	if( !empty( $terms_level ) ){
		
		?>
		<div class="date-level">
			<?php
			echo $before;
			foreach ( $terms_level as $term_level ){
				$term_level = get_term( $term_level->term_id );
				echo '<div>' . ucfirst( $term_level->description ) . '</div>';
			}
			echo $after;
			?>
		</div>
		<?php
	}
}

/**
 * get filter tags for html class attribute
 * 
 * @param string $date_sd_attendance_type 
 * @return string 
 */
function ivy_get_filter_tags( string $date_sd_attendance_type ) {
	$response = null;
	$classes = array();
	$classes[] = $date_sd_attendance_type === 'ONLINE' || $date_sd_attendance_type === 'SELECTABLE' ? 'sd-date-online' : null;
	$classes[] =  $date_sd_attendance_type === 'ON_SITE' || $date_sd_attendance_type === 'SELECTABLE' ? 'sd-date-onsite' : null;
	$i = 0;
	foreach ( $classes as $class ){
		if ( !empty( $class ) ){
			$response .= $i === 0 ? $class : ' ' . $class;
		}
		$i++;
	}
	return $response;
}