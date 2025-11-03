<?php 
/**
 * @package SeminardeskPlugin
 */
namespace Inc\Callbacks;

// exit if accessed directly
defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

use Inc\Controllers\CptController;
use Inc\Controllers\TaxonomyController;
use Inc\Utils\AdminUtils;
use Inc\Controllers\AdminController;

/**
 * Callbacks to manage the settings
 */
class ManagerCallbacks
{
	/**
	 * outputs html markup a text field
	 * 
	 * @param array $args 
	 * @return void 
	 */
	public function text_field( $args )
	{
		$name = $args['option']  . '[' . $args['key'] . ']';
		$value = AdminUtils::get_option_or_default( $args['option'], '',  $args['key']);
		echo '<input type="text" class="' . $args['class'] . '" name="' . $name . '" value="' . $value . '" placeholder="' . $args['placeholder'] . '">';
	}

	/**
	 * outputs html markup a slug field
	 * 
	 * @param array $args 
	 * @return void 
	 */
	public function slug_field( $args )
	{
		$this->text_field( $args );
		
		// add view link for slugs
		switch ( $args['type'] ){
			case 'cpt':
				$view_link = get_post_type_archive_link( $args['name'] );
				break;
			case 'txn':
				// $view_link = null;
				break;
			case 'term':
				$view_link = get_term_link( get_term_by('name', $args['name'], $args['taxonomy'] ) ) ;
				break;
			default:
				// $view_link = null;

		}
		if ( isset( $view_link ) ){
			$view_title = 'URL for this slug: ' . $view_link;
			echo '<a class="view-link" href="' . $view_link . '"  title="' . $view_title . '">view</a>';
		}
	}

	/**
	 * outputs html markup a checkbox field
	 * 
	 * @param mixed $args 
	 * @return void 
	 */
	public function checkbox_field( $args )
	{
		// $default = ( isset( $args['default'] ) ? $args['default'] : false );
		// $checkbox = get_option( $args['option'], $default );
		$checkbox = $args['value'];
		echo '<input type="checkbox" name="' . $args['option'] . '" value="1" class="' . $args['class'] . '" ' . ($checkbox ? 'checked' : '') . '>';
	}

	/**
	 * Sanitizes array of slugs
	 * 
	 * @param $inputs 
	 * @return array 
	 */
	public function sanitize_slugs( $inputs )
	{
		$inputs_sanitized = array();
		if ( isset( $inputs ) ){
			foreach ( $inputs as $input => $input_value ){
				$input_value = sanitize_title( $input_value );
				// $input_value = sanitize_text_field( $input_value );
				$inputs_sanitized[$input] = $input_value;
			}
		}
		return $inputs_sanitized;
	}

	/**
	 * creates CPTs and TXNs with new slug and rewrite rules 
	 * 
	 * @param mixed $value_old 
	 * @param mixed $value_new 
	 * @return void 
	 */
	public function rewrite_slugs( $value_old, $value_new )
	{
		// remove rewrite rule for old slug sd_txn_dates
		global $wp_rewrite;
		if ( is_array( $value_old ) && empty($value_old) )
		{
			if ( isset($value_old['sd_slug_txn_dates']) && $value_old['sd_slug_txn_dates'] === '' ){
				$rule = '^' . SD_TXN['sd_txn_dates']['slug_default'] . '/?$';
				// unset($wp_rewrite->extra_rules_top['^schedule/?$']);
				unset($wp_rewrite->extra_rules_top[$rule]);
			} else {
				$rule = '^' .  $value_old['sd_slug_txn_dates'] . '/?$';
				unset($wp_rewrite->extra_rules_top[$rule]);
			}
		}
		
		$cpt_ctrl = new CptController();
		$cpt_ctrl->create_cpts();

		$txn_ctrl = new TaxonomyController();
		$txn_ctrl->create_taxonomies();
		$txn_ctrl->update_terms_slug();
		$txn_ctrl->custom_rewrite_rules();

		flush_rewrite_rules();
	}

	/**
	 * Sanitizes checkbox
	 * 
	 * @param mixed $input 
	 * @return bool 
	 */
	public function sanitize_checkbox( $input )
	{
		// return ( isset($input) ? true : false );
		return filter_var($input, FILTER_VALIDATE_BOOLEAN);

	}

	/**
	 * outputs text for public section
	 * 
	 * @return void 
	 */
	public function admin_section_rest()
	{
		_e('Enable/Disable GET methods of the REST API for SeminarDesk in WordPress.', 'seminardesk');
		$url_namespace = home_url() . '/wp-json/seminardesk/v1'
		?>
			<a href=<?php echo $url_namespace;?> target="_blank"><?php _e('Open REST API for SeminarDesk', 'seminardesk'); ?></a>
			<?php
			if( SD_OPTION_VALUE['rest'] == true ){
				?>
				<ul class="bullet-points">
					<li><a href="<?php echo $url_namespace . '/cpt_events' ?>" target="_blank">GET Events</a></li>
					<li><a href="<?php echo $url_namespace . '/cpt_dates' ?>" target="_blank">GET Dates</a></li>
					<li><a href="<?php echo $url_namespace . '/cpt_facilitators' ?>" target="_blank">GET Facilitators</a></li>
					<li><a href="<?php echo $url_namespace . '/cpt_labels' ?>" target="_blank">GET Labels</a></li>
				</ul>
				<?php
			}
	}

	/**
	 * outputs text for slugs section
	 * 
	 * @return void 
	 */
	public function admin_section_slugs()
	{
		_e('Make SeminarDesk template pages public in the WordPress frontend via slugs. The different items can be enabled/disabled and customize.', 'seminardesk');
	}

	/**
	 * outputs text for debug section
	 * 
	 * @return void 
	 */
	public function admin_section_debug()
	{
		_e('Allows managing SeminarDesk CPTs, TXNs and Terms in the admin page. Use with caution.', 'seminardesk');
	}

	/**
	 * outputs text for uninstall section
	 * 
	 * @return void 
	 */
	public function admin_section_uninstall()
	{
		_e('Manage the settings for WP uninstaller of the SeminarDesk plugin.', 'seminardesk');
	}
}