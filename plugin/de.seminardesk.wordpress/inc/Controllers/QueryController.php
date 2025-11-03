<?php
/**
 * @package SeminardeskPlugin
 */

namespace Inc\Controllers;

// exit if accessed directly
defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

use WP_Query;

/**
 * Handles modification and overwrites of queries
 * 
 * Note: 
 * - serialized meta data (custom field sd_data) cannot and should not be queried! https://wordpress.stackexchange.com/questions/16709/meta-query-with-meta-values-as-serialize-arrays
 */
class QueryController
{
	/**
	 * Code that runs to register the controller
	 *
	 * @return void
	 */
	public function register()
	{
		add_action( 'pre_get_posts', array( $this, 'modify_query' ) );
		add_filter( 'template_include', array( $this, 'overwrite_query' ), 20 );
	}

	/**
	 * modifies the query
	 * 
	 * @param WP_Query $query 
	 * @return void 
	 */
	public function modify_query( $query ) {

		/**
		 * sd_txn_dates
		 */
		if ( $query->is_tax( 'sd_txn_dates' ) && $query->is_main_query() ) {
			$query->set( 'meta_key', 'sd_date_begin' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'ASC' );
			$query->set( 'meta_query', array(
				'key'	=> 'sd_preview_available',
				'value'	=> true,
			) );
			// $query->set( 'posts_per_page', 5 ); // debugging
		}

		/**
		 * sd_txn_labels
		 */
		if ( $query->is_tax( 'sd_txn_labels' ) && $query->is_main_query() ) {
			$query->set( 'post_status', 'any');
			$query->set( 'orderby', 'title' );
			$query->set( 'order', 'ASC' );
			// rewrite tax_query to exclude children
			$tax_query = $query->tax_query->queries[0];
			$tax_query['include_children'] = false;
			$query->tax_query->queries[0] = $tax_query;
			$query->query_vars['tax_query'] = $query->tax_query->queries;
		}

		/**
		 * sd_archive
		 */
		if ( $query->is_archive() && !$query->is_tax() && $query->is_main_query() ){
			$post_type = $query->query['post_type'] ?? null;
			// $query->set( 'post_status', 'any');
			switch ($post_type){
				case 'sd_cpt_facilitator':
					$query->set( 'meta_key', 'sd_last_name' );
					$query->set( 'orderby', array( 
						'meta_value'	=> 'ASC',
						'title'		 	=> 'ASC', 
					) );
					break;
				case 'sd_cpt_label':
					$query->set( 'post_parent', 0 );
					// break; // commented out ... continue with default case
				default:
					$query->set( 'orderby', 'title' );
					$query->set( 'order', 'ASC' );
			} 
		}
	}


	/**
	 * overwrites the current query by a custom query 
	 * 
	 * @param string $template template path
	 * @return string template path
	 */
	public function overwrite_query( string $template )
	{
		global $wp_query;

		/**
		 * sd_archive
		 */
		if ( is_archive() && is_main_query() ){
			if ( get_query_var('post_type') === 'sd_cpt_event' ){
				// retrieve upcoming event date posts
				$timestamp_today = strtotime(wp_date('Y-m-d')); // current time
				// $timestamp_today = strtotime('2022-09-01'); // debugging
				$date_posts = get_posts(array(
					'post_type'			=> 'sd_cpt_date',
					'post_status'		=> 'publish',
					'posts_per_page'	=> -1,
					'meta_key'			=> 'sd_date_begin',
					'orderby'			=> array(
						'meta_value'	=> 'ASC',
					),
					'meta_query'		=> array(
						array(
							'key'		=> 'sd_date_begin',
							'value'		=> $timestamp_today*1000, //in ms
							'type'		=> 'numeric',
							'compare'	=> '>=',
						),
					),
					'cache_results' => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				));
				// retrieve corresponding event post ids of event ordered date
				$wp_events = array();
				foreach ( $date_posts as $date_post ) {
					$wp_events[] =  (int)$date_post->wp_event_id;
				}
				// exclude duplicated event post ids
				$wp_events = array_unique( $wp_events );
				// retrieve event post ids without upcoming event date ordered by title
				$wp_events_no_upcoming = get_posts(array(
					'post_type'			=> 'sd_cpt_event',
					'post_status'		=> 'any',
					'posts_per_page'	=> -1,
					'post__not_in' => $wp_events,
					'orderby'			=> 'title',
					'fields'			=> 'ids',
					'cache_results' => false, // Turned off by default since 'fields' was passed.
					'update_post_meta_cache' => false, // Turned off by default since 'fields' was passed.
					'update_post_term_cache' => false, // Turned off by default since 'fields' was passed.
				));
				// combine custom lists of event post ids
				$wp_events = array_merge( $wp_events, $wp_events_no_upcoming );
				// overwrite $wp_query with custom ordered event query
				$wp_query = new WP_Query(array(
					'post_type'			=> 'sd_cpt_event',
					'post_status'		=> 'any',
					'posts_per_page'	=> -1,
					'post__in' 			=> $wp_events,
					'orderby'			=> 'post__in',
				));
			}
		}

		/**
		 * sd_txn_dates
		 */
		if ( is_tax( 'sd_txn_dates' ) && is_main_query() ) {
			$queried_object = get_queried_object();
			switch ( $queried_object->name ){
				case 'upcoming':
					$timestamp_today = strtotime(wp_date('Y-m-d')); // current time
					// $timestamp_today = strtotime('2022-09-01'); // debugging
					$paged = ( get_query_var( 'page' ) ) ? absint( get_query_var( 'page' ) ) : 1;
					$wp_query = new WP_Query( array(
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
					add_action( 'get_footer', array( $this, 'reset_query' ), 100 );
					break;
				case 'past':
					$timestamp_today = strtotime(wp_date('Y-m-d')); // current time
					// $timestamp_today = strtotime('2022-12-01'); // debugging
					$paged = ( get_query_var( 'page' ) ) ? absint( get_query_var( 'page' ) ) : 1;
					$wp_query = new WP_Query( array(
						'post_type'		=> 'sd_cpt_date',
						'post_status'	=> 'publish',
						// 'posts_per_page'   => 5, // debugging
						'paged'			=> $paged,
						'meta_key'		=> 'sd_date_begin',
						'orderby'		=> 'meta_value_num',
						'order'			=> 'DESC',
						'term_object'	=> $queried_object, // custom var
						'meta_query'	=> array(
							'relation'	=> 'AND',
							array(
								'key'		=> 'sd_date_begin',
								'value'		=> $timestamp_today*1000, //in ms
								'type'		=> 'numeric',
								'compare'	=> '<',
							),
							array(
								'key'	=> 'sd_preview_available',
								'value'	=> true,
							),
						),
					) );
					// when loop is finished
					add_action( 'get_footer', array( $this, 'reset_query' ), 100 );
					break;
				default:
					break;
			}
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
	public function reset_query()
	{
		global $wp_query, $wp_the_query;
		switch ( current_filter() ) {
			case 'get_footer':
				$wp_query = $wp_the_query;
			break;
		}
	}
}