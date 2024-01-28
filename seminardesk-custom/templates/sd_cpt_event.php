<?php
/**
 * The template for single post of CPT sd_cpt_event
 *
 * @package SeminardeskPlugin
 */

defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

/**
 * includes here...
 */
use Inc\Utils\TemplateUtils as Utils;

/**
 * parameters here...
 */
$modal_id = 0;
global $post;
$event_wp_id = $post->ID;
$event_sd_id = $post->sd_event_id;
$event_sd_data = $post->sd_data;
$event_sd_af = $event_sd_data['additionalFields'];

/**
 * site header here...
 */

/**
 * Add meta og:image to the header via yoast hook
 * @param mixed $object 
 * @return void 
 */
function ivy_wpseo_add_images( $object ) {
	global $post;
	$img_url = Utils::get_value_by_language($post->sd_data['headerPictureUrl']);
	// $img_size = getimagesize($img_url);
	$img = array( 
		'url' => $img_url, 
		// 'width' => $img_size[0], 
		// 'height' => $img_size[1], 
		// 'type' => $img_size['mime'] // Poor value/performance trade-off; especially as we ping Facebook on post publish, at which point they determine and cache this information themselves.
	);
	$object->add_image( $img );
}
add_action( 'wpseo_add_opengraph_images', 'ivy_wpseo_add_images' );

/**
 * Add meta og:description to the header via yoast hook
 * @param mixed $desc 
 * @return string 
 */
function ivy_wpseo_change_desc( $desc ) {
	global $event_sd_af;
	
	$desc = wp_strip_all_tags( $event_sd_af['FR_Event_Short_Description'] );
	$desc_trimmed = mb_strimwidth( $desc, 0, 160, "..." );
	return $desc_trimmed;
}
add_filter( 'wpseo_opengraph_desc', 'ivy_wpseo_change_desc' );

get_header();

/**
 * main code here...
 */

?>
	<?php
	// loop
	if (have_posts()) {
		while (have_posts()) {
			the_post();
			// loop parameters
			?>
			<main id="page-content site-content" class="l-main" itemprop="mainContentOfPage" role="main">
			<?php $img_url = Utils::get_value_by_language($post->sd_data['headerPictureUrl']) ?? null; ?>
			<section class="l-section wpb_row height_small full_height valign_bottom parallax_fixed" id="kopf" <?php if ($img_url != null) { echo 'style="background-image: url(' . $img_url . ')!important"';} ?>"><div class="l-section-h i-cf"><div class="g-cols vc_row via_grid cols_1 laptops-cols_inherit tablets-cols_inherit mobiles-cols_1 valign_top type_default stacking_default"><div class="wpb_column vc_column_container"><div class="vc_column-inner"><div class="w-separator size_huge"></div></div></div></div></div></section>
			
			<section class="l-section wpb_row us_custom_c77e16e1 height_auto with_shape">
    <div class="l-section-shape type_tilt pos_top" style="height: 2vmin;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 8" preserveAspectRatio="none" width="100%" height="100%">
            <path fill="currentColor" d="M64 7.9 L64 10 L0 10 L0 0 Z"></path>
        </svg>
    </div>
    <div class="l-section-h i-cf">
        <div class="g-cols vc_row via_grid cols_1 laptops-cols_inherit tablets-cols_inherit mobiles-cols_1 valign_top type_default stacking_default" style="--gap: 0rem;">
            <div class="wpb_column vc_column_container">
                <div class="vc_column-inner">
                    <div class="g-cols wpb_row us_custom_ac3998c6 via_grid cols_1 laptops-cols_inherit tablets-cols_inherit mobiles-cols_1 valign_top type_default stacking_default" style="--gap: 0%;">
                        <div class="wpb_column vc_column_container us_custom_5cd26a65">
                            <div class="vc_column-inner">
                                <div class="w-post-elm post_content us_custom_5cd26a65" itemprop="text">
                                    <section class="l-section wpb_row height_small full_height valign_top parallax_fixed">
                                        <div class="l-section-h i-cf">
                                            <div class="g-cols vc_row via_grid cols_1 laptops-cols_inherit tablets-cols_inherit mobiles-cols_1 valign_top type_default stacking_default">
                                                <div class="wpb_column vc_column_container">
                                                    <div class="vc_column-inner">
                                                    <div class="title-container">
                                                        <?php
                                             
					$booking_list = Utils::get_event_dates_list( $post->sd_event_id, $status_lib );
					$booking_url = esc_url( Utils::get_value_by_language( $post->sd_data['bookingPageUrl'] ?? null ) );   
					
					Utils::get_value_by_language( $post->sd_data['title'], 'DE', '<div class="float-left"><h1 class="w-post-elm post_title entry-title color_link_inherit">', '</h1>', true);
					echo Utils::get_value_by_language($post->sd_data['subtitle'], 'DE', '<h2>', '</h2></div>', false);
					
					if ( !empty($booking_url) && $post->sd_data['registrationAvailable'] === true ) {
						?>
						<div class="float-right"><button class="sd-modal-booking-btn sd-booking-btn-top w-btn us-btn-style_4">Anmeldung</button></div>
						<?php
						}         
					?>      </div><hr />
                                                    <div class="wpb_text_column">
                                                            <div class="wpb_wrapper">
                                                                
					<?php
					// TODO: for backwards compatibility - perhaps remove at a later?
					$facilitators = Utils::get_facilitators($post->sd_data['facilitators']);
					if ($facilitators) {
						?><p><strong>Facilitator - Event level: </strong></p><?php
						echo $facilitators;
					}
					?>
				
				<div class="sd-description">
					<?php
					echo Utils::get_value_by_language($post->sd_data['description']);
					?>
				</div>
				<?php
					// get list of all dates for this event
					$status_lib = array(
						'available'		=> 'Anmeldung',
						'fully_booked'	=> 'Ausgebucht',
						'limited'		=> 'besondere Anmeldebdingungen',
						'wait_list'		=> 'Warteliste',
						'canceled'		=> 'Abgesagt',
					);
					if ( $booking_list ){
						?>
						<div class="sd-available-dates">
						<h4>Verfügbare Termine:</h4>
						<?php
						echo $booking_list;
						if ( !empty($booking_url) && $post->sd_data['registrationAvailable'] === true ) {
							?>
							<br><p><button class="sd-modal-booking-btn sd-booking-btn-100 w-btn us-btn-style_4">Anmeldung</button></p>
							<?php
						}
					} else {
						?>
						<h4>Keine Daten verfügbar für diese Veranstaltung :(</h4>
						<?php
					}
					?>
                                                              </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
			<!-- BEGIN modal content -->
			<div class="sd-modal">
				<div class="sd-modal-content">
					<span class="sd-modal-close-btn">&times;</span>
					<iframe class="sd-modal-booking" src="<?php echo $booking_url ?>/embed" title="Seminardesk Booking"></iframe>
				</div>
			</div>
			<!-- END modal content -->
			<?php
		}
	} else {
		?>
		<div class="entry-header-inner section-inner small has-text-align-center">
			<h5><strong>Sorry, event does not exist.</strong></h5>
			<br>
		</div>
		<?php
	}
	wp_reset_query();
	?>

</main><!-- #site-content -->

<?php
get_footer();
