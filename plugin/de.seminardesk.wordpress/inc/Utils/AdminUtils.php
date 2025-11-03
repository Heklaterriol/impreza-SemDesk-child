<?php
/**
 * @package SeminardeskPlugin
 */

namespace Inc\Utils;

// exit if accessed directly
defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

/**
 * Collection of utilities for admin page
 */
class AdminUtils
{
	/**
	 * Gets (serialized) option or use default, if option is empty or does not exist
	 * 
	 * @param string $option serialized or not serialized option from the DB
	 * @param string $default default value if option is not set
	 * @param string $key used to get value of a serialized option
	 * @return string 
	 */
	public static function get_option_or_default( $option, $default = '', $key = null ) {
		if ( !empty(get_option( $option )) ){
			if ( !empty($key) ) {
				$value = !empty(get_option( $option )[$key]) ? get_option( $option )[$key] : $default;
			} else {
				$value = get_option( $option );
			} 
		} else {
			$value = $default;
		}
		return $value;
	}

	/**
	 * Deletes SeminarDesk's custom posts, terms and options from the DB
	 * @return void 
	 */
	public static function delete_all_sd_objects( )
	{
		// Get SeminarDesk's posts by ID and delete them from the DB
		foreach ( SD_CPT as $key => $value ){
			$custom_post_ids = get_posts( array (
				'fields'		=> 'ids', // return an array of ids instead of objects
				'post_type'		=> $key,
				'post_status'	=> 'any',
				'numberposts'	=> -1,
			));
			foreach ( $custom_post_ids as $custom_post_id){
				wp_delete_post( $custom_post_id, true);
			}
			$custom_post_ids = get_posts( array (
				'fields'		=> 'ids',
				'post_type'		=> $key,
				'post_status'	=> 'trash',
				'numberposts'	=> -1,
			));
			foreach ( $custom_post_ids as $custom_post_id){
				wp_delete_post( $custom_post_id, true);
			}
		}
		// Get SeminarDesk's terms by ID of the txn and delete them from the DB
		foreach ( SD_TXN as $key => $value){
			$terms_ids = get_terms( array(
				'fields'		=> 'ids',
				'taxonomy'		=> $key,
				'hide_empty'	=> false,
			) );
			foreach ( $terms_ids as $term_id ){
				wp_delete_term( $term_id, $key );
			}
		}
	}

	/**
	 * Deletes SeminarDesk's options from the DB
	 * @return void 
	 */
	public static function delete_all_sd_options( )
	{
		// Get SeminarDesk's options and delete them from the DB
		foreach( SD_OPTION as $key => $value ){
			delete_option( $value );
		}
	}
}