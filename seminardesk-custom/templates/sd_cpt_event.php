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
require_once( get_stylesheet_directory() . '/inc/utils.php' );

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

<main id="page-content site-content event-details" class="l-main" itemprop="mainContentOfPage" role="main">
<?php
// loop
if (have_posts()) {
  while (have_posts()) {
    the_post();
    // loop parameters
    ?>
			<?php $img_url = Utils::get_value_by_language($post->sd_data['headerPictureUrl']) ?? null; ?>
			<section class="l-section wpb_row height_small full_height valign_top parallax_fixed" id="kopf" <?php if ($img_url != null) { echo 'style="background-image: url(' . $img_url . ')!important';} ?>">
				<div class="l-section-h i-cf">
					<div class="g-cols vc_row via_grid cols_1 laptops-cols_inherit tablets-cols_inherit mobiles-cols_1 valign_top type_default stacking_default">
						<div class="wpb_column vc_column_container us_custom_4c3b89c1 stretched has-link">
							<div class="vc_column-inner">
								<div class="w-separator size_huge"></div>
								<div class="w-iconbox us_custom_1fe0742c iconpos_top style_default color_custom align_center no_text no_title">
                  <div class="w-iconbox-icon" style="font-size: 2rem;"><i class="fas fa-chevron-double-down"></i></div>
                  <div class="w-iconbox-meta"></div>
                </div>
              </div>
              <a href="#inhalt" class="vc_column-link smooth-scroll" aria-label="Link"></a>
						</div>
					</div>
				</div>
			</section>
			<section class="l-section wpb_row us_custom_5e1b6e42 height_auto with_shape" id="inhalt">
        <div class="l-section-shape type_tilt pos_top" style="height: 2vmin;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 8" preserveAspectRatio="none" width="100%" height="100%">
            <path fill="currentColor" d="M64 7.9 L64 10 L0 10 L0 0 Z"></path>
          </svg>
        </div>
        <div class="l-section-h i-cf">
          <div class="g-cols vc_row via_grid cols_1 laptops-cols_inherit tablets-cols_inherit mobiles-cols_1 valign_top type_default stacking_default" style="--gap: 0rem;">
            <div class="wpb_column vc_column_container">
              <div class="vc_column-inner">
                <div class="g-cols wpb_row us_custom_59bf33fe minheight600 via_grid cols_1 laptops-cols_inherit tablets-cols_inherit mobiles-cols_1 valign_top type_default stacking_default" style="--gap: 0%;">
                  <div class="wpb_column vc_column_container us_custom_5cd26a65">
                    <div class="vc_column-inner">
                      <div class="w-post-elm post_content us_custom_5cd26a65" itemprop="text">
                        <section class="l-section wpb_row height_small">
                          <div class="l-section-h i-cf">
                            <div class="g-cols vc_row via_grid cols_1 laptops-cols_inherit tablets-cols_inherit mobiles-cols_1 valign_top type_default stacking_default">
                              <div class="wpb_column vc_column_container">
                                <div class="vc_column-inner">
                                  <div class="title-container">
                                    <?php
                                    // get list of all dates for this event
                                    $status_lib = [
                                      'available'		=> 'Anmeldung',
                                      'fully_booked'	=> 'Ausgebucht',
                                      'limited'		=> 'besondere Anmeldebdingungen',
                                      'wait_list'		=> 'Warteliste',
                                      'canceled'		=> 'Abgesagt',
                                      'hidden_on_list'		=> 'Ausgeblendet',
                                    ];
//                                    $booking_list = Utils::get_event_dates_list( $post->sd_event_id, $status_lib );
                                    $booking_list = ivy_get_event_dates_list( $post->sd_event_id, $status_lib );
                                    $booking_url = esc_url( Utils::get_value_by_language( $post->sd_data['bookingPageUrl'] ?? null ) );   

                                    Utils::get_value_by_language( $post->sd_data['title'], 'DE', '<div class="float-left"><h1 class="w-post-elm post_title entry-title color_link_inherit">', '</h1></div>', true);
                                    if ( !empty($booking_url) && $post->sd_data['registrationAvailable'] === true ) {
                                      ?>
                                      <div class="float-right"><button class="sd-modal-booking-btn sd-booking-btn-top w-btn us-btn-style_4">Anmeldung</button></div>
                                      <?php
                                    } else { ?>
                                    <div class="float-right"><a href="#registration-area" class="w-btn us-btn-style_4">Anmeldung</a></div>
                                    <?php
                                    } ?>
                                  </div>
                                  <div class="wpb_text_column">
                                    <div class="wpb_wrapper">
                                      <?= Utils::get_value_by_language($post->sd_data['subtitle'], 'DE', '<h2>', '</h2>', false); ?>
                                      <?php foreach( $booking_list as $date ) : ?>
                                        <div class="sd-available-dates">
                                          <div class="grid-item-left">
                                              <strong>Wann:</strong> <?= $date['date'] ?><br />
                                                <strong>Kursleitung:</strong> <?= $date['facilitators'] ?>
                                            </div>
                                            <div class="grid-item-right">
                                              <strong>Wo:</strong> <?= $date['venue'] ?><br />
                                              <strong>Preis:</strong> <?= $date['price'] ?>
                                            </div>
                                        </div>
                                      <?php endforeach; ?>
                                      <div class="sd-description">
                                        <?= Utils::get_value_by_language($post->sd_data['description']); ?>
                                      </div>
                                        <?php
                                        $facilitators = ivy_get_facilitators($post->sd_data['facilitators']);
                                        if ( !empty( $facilitators ) ) : ?>
                                      <div class="sd-facilitators">
                                        <h2>Referent*innen</h2>
                                          <?php foreach ( $facilitators as $facilitator ) : ?>
                                            <div class="sd-facilitator">
                                              <div class="sd-facilitator-name">
                                                <h3><?= $facilitator->sd_data['name']; ?></h3>
                                              </div>
                                              <div class="sd-facilitator-picture">
                                                <img src="<?= $facilitator->sd_data['pictureUrl'] ?>" alt="<?= $facilitator->sd_data['name'] ?>">
                                              </div>
                                              <div class="sd-facilitator-about">
                                                <?= Utils::get_value_by_language($facilitator->sd_data['about']); ?>
                                              </div>
                                            </div>
                                          <?php endforeach; ?>
                                      </div>
                                        <?php endif; ?>
                                      <div class="sd-registration">
                                        <?php if (!empty($booking_url) && $post->sd_data['registrationAvailable'] === true ) : ?>
                                          <div id="registration-area"><button class="sd-modal-booking-btn sd-booking-btn-100 w-btn us-btn-style_4">Anmeldung</button></div>
                                        <?php else : ?>
                                          <div id="registration-area"><a class="sd-booking-btn-100 w-btn us-btn-style_4">Anmeldung direkt über die Seminarleitung</a></div>
                                        <?php endif; ?>
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
