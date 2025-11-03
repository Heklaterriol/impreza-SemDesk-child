<?php
/**
 * @package SeminardeskPlugin
 */

namespace Inc\Utils;

// exit if accessed directly
defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

use WP_Query;
use WP_Error;
use WP_Term;
use WP_Post;

/**
 * Collection of utilities for webhooks
 */
class WebhookUtils
{

	/**
	 * computes a unique post name (aka slug) for a post by using indexing
	 * 
	 * @param string $post_name 
	 * @param int $post_id Optional
	 * @return string unique post name 
	 */
	public static function unique_post_slug( string $post_name, int $post_id = 0 )
	{
		global $wpdb;
		$post_name = sanitize_title($post_name);
		if($wpdb->get_row("SELECT post_name FROM $wpdb->posts WHERE post_name = '" . $post_name . "' AND ID != $post_id", 'ARRAY_A')) {
			$unique = false;
		} else {
			return $post_name;
		}
		$i = 0;
		while ( $unique === false ){
			$i++;
			$post_name_indexed = $post_name . '-' . $i;
			if($wpdb->get_row("SELECT post_name FROM $wpdb->posts WHERE post_name = '" . $post_name_indexed . "' AND ID != $post_id", 'ARRAY_A')) {
				$unique = false;
			} else {
				// $unique = true;
				return $post_name_indexed;
			}
		}
	
	}
	/**
	 * computes a unique slug for a term by using indexing
	 * 
	 * @param string $slug 
	 * @param int $term_id Optional
	 * @return string unique term
	 */
	public static function unique_term_slug( string $slug , $term_id = 0)
	{
		global $wpdb;
		$slug = sanitize_title($slug);
		if($wpdb->get_row("SELECT slug FROM $wpdb->terms WHERE slug = '" . $slug . "' AND term_id != $term_id", 'ARRAY_A')) {
			$unique = false;
		} else {
			return $slug;
		}
		$i = 0;
		while ( $unique === false ){
			$i++;
			$slug_indexed = $slug . '-' . $i;
			if($wpdb->get_row("SELECT slug FROM $wpdb->terms WHERE slug = '" . $slug_indexed . "' AND term_id != $term_id", 'ARRAY_A')) {
				$unique = false;
			} else {
				// $unique = true;
				return $slug_indexed;
			}
		}
	}

	/**
	 * adds a taxonomy term to an array of terms 
	 * 
	 * Note: used for tax_input of wp_insert/update_post
	 * 
	 * @param string $term The term to check. Accepts term ID, slug, or name.
	 * @param array $terms The tax_input array of an post insert/update.
	 * @param string $taxonomy (Optional) The taxonomy name to use. Default value: ''
	 * @param int|null $parent (Optional) ID of parent term under which to confine the exists search. Default value: null
	 * @return array The array with terms 
	 */
	public static function add_post_term( string $term, array $terms, string $taxonomy = '', int $parent = null ){
		$term_ids = term_exists( (string) $term, $taxonomy, $parent);
		if ( isset( $term_ids ) ){
			array_push( $terms, $term_ids['term_id'] );
		}

		return $terms;
	}

	/**
	 * adds a taxonomy term to tax_input array
	 * 
	 * @param array $tax_input Array of taxonomy terms keyed by their taxonomy name.
	 * @param $term The term to check. Accepts term ID, slug, or name.
	 * @param string $taxonomy The taxonomy name to use.
	 * @param mixed $parent (Optional) ID of parent term under which to confine the exists search. Default value: null
	 * @return void 
	 */
	public static function add_term_tax_input( array &$tax_input, $term, string $taxonomy, int $parent = null )
	{
		$term_ids = term_exists( (string) $term, $taxonomy, $parent);
		if ( isset( $term_ids ) ){
			if ( empty( $tax_input[$taxonomy] ) ){
				$tax_input[$taxonomy] = array();
			}
			array_push( $tax_input[$taxonomy], $term_ids['term_id'] );
		}
	}

	/**
	 * inserts or updates a term of a taxonomy 
	 * 
	 * Note: term name is payload['id'] for the term to be queried by it's SeminarDesk ID
	 * 
	 * @param string $taxonomy The taxonomy of the term.
	 * @param array $name The name of the term. Needs to be unique for the hole taxonomy 
	 * @param array $description The description of the term
	 * @param string $slug The slug of the term. Needs to be unique for the hole WordPress
	 * @param int $parent (Optional) The id of the parent term. Default value: 0
	 * @return array|WP_Error An array containing the term_id and term_taxonomy_id, WP_Error otherwise.
	 */
	public static function set_term( string $taxonomy, string $name, string $description, string $slug, int $parent = 0 )
	{
		$term = get_term_by('name', $name, $taxonomy);
		if ( empty( $term ) ){
			$slug = self::unique_term_slug( $slug );
			$term_ids = wp_insert_term( $name, $taxonomy, array(
				'name'				=> $name,
				'description'	=> $description,
				'slug'			=> $slug,
				'parent'		=> $parent,
				) );
		}else {
			$slug = self::unique_term_slug( $slug, $term->term_id);
			$term_ids = wp_update_term( $term->term_id, $taxonomy, array(
				'name'				=> $name,
				'description'	=> $description,
				'slug'			=> $slug,
				'parent'		=> $parent,
			) );
		}

		return $term_ids;
	}

	/**
	 * Deletes label term, if label not included in the payload of labelGroup anymore
	 * 
	 * @param WP_Term $label_term
	 * @param array $labels_payload 
	 * @return bool True on deletion, false if label exists.
	 */
	public static function exclude_label_term( WP_Term $label_term, array $labels_payload )
	{
		foreach ( $labels_payload as $label_payload ){
			if ( (int) $label_term->name === $label_payload['id'] ){
				return false;
			}
		}
		wp_delete_term( $label_term->term_id, 'sd_txn_labels' );
		return true;
	}

	/**
	 * Deletes label term post, if label not included in the payload of labelGroup anymore
	 * 
	 * @param WP_Post $label_post
	 * @param array $labels_payload 
	 * @return bool True on deletion, false if label exists.
	 */
	public static function exclude_label_post( WP_Post $label_post, array $labels_payload )
	{
		foreach ( $labels_payload as $label_payload ){
			if ( (int) $label_post->sd_label_id === $label_payload['id'] ){
				return false;
			}
		}
		$post_query = self::get_query_by_meta( 'sd_cpt_label', 'sd_label_id', $label_post->sd_label_id);
		wp_delete_post( $post_query->post->ID );
		return true;
	}

	/**
	 * sets unique meta data for a term
	 * 
	 * @param array $term_ids An array containing the term_id and term_taxonomy_id.
	 * @param string $meta_key Metadata name.
	 * @param mixed $meta_value Metadata value. Must be serializable if non-scalar.
	 * @return int|bool|WP_Error Meta ID on success, false on failure. WP_Error when term_id is ambiguous between taxonomies.
	 */
	public static function set_term_meta( array $term_ids, string $meta_key, $meta_value ){
		// set term meta
		$meta_data = get_term_meta( $term_ids['term_id'], $meta_key );
		if( !empty( $meta_data ) ) {
			$term_meta_id = update_term_meta( $term_ids['term_id'], $meta_key, $meta_value );
		}else {
			$term_meta_id = add_term_meta( $term_ids['term_id'], $meta_key, $meta_value, true);
		}
		return $term_meta_id;
	}

	/**
	 * Retrieves query data by given meta key and its requested value.
	 *
	 * @param string $post_type
	 * @param string $meta_key
	 * @param string $meta_value
	 * @return WP_Query
	 */
	public static function get_query_by_meta( $post_type, $meta_key, $meta_value )
	{
		$query = new WP_Query(
			array(
				'post_type'		=> $post_type,
				'post_status'	=> 'any',
				'meta_query'	=> array(
					array(
						'key'			=> $meta_key,
						'value'		=> $meta_value,
						'compare'	=> '=',
						'type'		=> 'CHAR',
					),
				),
			),
		);

		return $query;
	}

	/**
	 * deletes post of a meta data query permanently ... no trash.
	 * 
	 * Note: Metadata needs to define a unique post ... multiple posts use WebhookUtils::delete_posts_by_meta
	 * 
	 * @param int   $post_type
	 * @param string $meta_key
	 * @param string $meta_value
	 * @return WP_post|false|null deleted WP_post if successfully deleted
	 */
	public static function delete_post_by_meta( $post_type, $meta_key, $meta_value )
	{
		$query = self::get_query_by_meta( $post_type, $meta_key, $meta_value );
		if ( $query->post_count === 1 ){
			$post_id = $query->post->ID ?? 0;
			$post_deleted = wp_delete_post( $post_id );
			return $post_deleted;
		} else {
			return null;
		}
	}

	/**
	 * deletes posts of a meta data query permanently ... no trash
	 * 
	 * @param int   $post_type
	 * @param string $meta_key
	 * @param string $meta_value
	 * @return bool|null true if all posts successfully deleted
	 */
	public static function delete_posts_by_meta( $post_type, $meta_key, $meta_value )
	{
		$query = self::get_query_by_meta( $post_type, $meta_key, $meta_value );
		foreach( $query->posts as $query_post ){
			$post_id = $query_post->ID ?? 0;
			$post_deleted = wp_delete_post( $post_id );
			if( empty( $post_deleted ) ){
				return $post_deleted;
			}
		}
		return true;
	}

	/**
	 * Sets or deletes featured image for CPT
	 * 
	 * @link: http://xylusthemes.com/
	 * @since    1.4.0
	 * @param int $post_id post id.
	 * @param int $image_url Image URL
	 * @return void
	 */
	public static function featured_image_to_post( $post_id, $image_url = '' ) {

		if ( empty( $image_url ) ) {
			delete_post_thumbnail( $post_id );
			return;
		}
		$post = get_post( $post_id );
		if( empty ( $post ) ){
			return;
		}

		require_once(ABSPATH . 'wp-admin/includes/media.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/image.php');

		$post_title = $post->post_title;
		if(strpos($image_url, "https://drive.google.com/") === 0 ){
			$ical_image_id = explode('/', str_replace('https://drive.google.com/', '', $image_url))[2];
			if(!empty($ical_image_id)){
				$image_url = 'https://drive.google.com/uc?export=download&id='.$ical_image_id;
			}
		}
		if ( ! empty( $image_url ) ) {
			$without_ext = false;
			// Set variables for storage, fix file filename for query strings.
			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png|webp)\b/i', $image_url, $matches );
			if ( ! $matches ) {
				if(strpos($image_url, "https://cdn.evbuc.com") === 0 || strpos($image_url, "https://img.evbuc.com") === 0 || strpos($image_url, "https://drive.google.com") === 0 ){
					$without_ext = true;
				}else{
					return new WP_Error( 'image_sideload_failed', __( 'Invalid image URL' ) );
				}
			}

			$args = array(
				'post_type'   => 'attachment',
				'post_status' => 'any',
				'fields'      => 'ids',
				'meta_query'  => array( // @codingStandardsIgnoreLine.
					array(
						'value' => $image_url,
						'key'   => '_wpsd_attachment_source',
					),
				),
			);
			$id = 0;
			$ids = get_posts( $args ); // @codingStandardsIgnoreLine.
			if ( $ids ) {
				$id = current( $ids );
			}
			if( $id && $id > 0 ){
				set_post_thumbnail( $post_id, $id );
				return $id;
			}

			$file_array = array();
			$file_array['name'] = $post->ID . '_image';
			if($without_ext === true){
				$file_array['name'] .= '.jpg';
			}else{
				$file_array['name'] .=  '_'.basename( $matches[0] );
			}
			
			if( has_post_thumbnail( $post_id ) ){
				$attachment_id = get_post_thumbnail_id( $post_id );
				$attach_filename = basename( get_attached_file( $attachment_id ) );
				if( $attach_filename == $file_array['name'] ){
					return $attachment_id;
				}
			}

			// Download file to temp location.
			$file_array['tmp_name'] = download_url( $image_url );

			// If error storing temporarily, return the error.
			if ( is_wp_error( $file_array['tmp_name'] ) ) {
				return $file_array['tmp_name'];
			}

			// Do the validation and storage stuff.
			$att_id = media_handle_sideload( $file_array, $post_id, $post_title );

			// If error storing permanently, unlink.
			if ( is_wp_error( $att_id ) ) {
				@unlink( $file_array['tmp_name'] );
				return $att_id;
			}

			if ($att_id) {
				set_post_thumbnail($post_id, $att_id);
			}

			// Save attachment source for future reference.
			update_post_meta( $att_id, '_wpsd_attachment_source', $image_url );

			return $att_id;
		}
	}
}