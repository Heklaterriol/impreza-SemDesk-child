<?php
/**
 * Shortcodes.
 *
 * @package HelloIVY
 */

defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

/**
 * includes here...
 */
use Inc\Utils\TemplateUtils as Utils;

/**
 * Shortcode function for agenda widget
 * e.g. [ivy-widget-agenda category="31" show_current="true"]
 * @param mixed $atts - ['category´] (category_id), ['show_current'] (boolean, default: false)
 * @return string|false 
 */
function ivy_widget_agenda( $atts ) {
	$atts = shortcode_atts ( array(
	), $atts);

	// enable output buffering
	ob_start();

	/**
	 * BEGIN of template code ...
	 */

	$svg_arrow = '<div class="arrow"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" width="40" height="40" focusable="false"><path d="m15.5 0.932-4.3 4.38 14.5 14.6-14.5 14.5 4.3 4.4 14.6-14.6 4.4-4.3-4.4-4.4-14.6-14.6z"></path></svg></div>'
	?>
	<div id="shortcode-agenda">
		<div class="spacer"></div>
		<div id="widget-current">
			<section class="splide" aria-labelledby="current-heading">
				<h2 id="current-heading"><?php echo IVY_STRINGS['current'] ?></h2>
				<div class="splide__track">
					<ul class="splide__list">
						<?php
						$dates_current = ivy_get_dates_current();
						foreach( $dates_current as $date ){
							if ( 
								$date->sd_data['bookingPageStatus'] !== 'canceled' &&
								$date->sd_data['bookingPageStatus'] !== 'hidden_on_list' &&
								$date->sd_data['bookingPageStatus'] !== 'hidden' &&
								!empty($date->sd_preview_available)
							){
								$wp_today = wp_date('Y-m-d');
								$wp_date_begin = wp_date( 'Y-m-d', $date->sd_date_begin/1000 );
								$wp_date_end = wp_date( 'Y-m-d', $date->sd_date_end/1000 );
								if( $wp_date_begin == $wp_date_end && $wp_date_end == $wp_today ){ // don't show current day event dates
									echo null;
								}else{
								?>
								<li class="splide__slide">
									<a class="box" href="<?php echo get_permalink( $date->wp_event_id ); ?>">
										<div class="date">
											<div class="float-left">
												<h2 class="title">
													<?php echo ucfirst($date->post_title); ?>
												</h2>
												<?php
												if( isset($date->sd_data['additionalFields']['FR_Date_Subtitle']) ){
												?>
													<h2 class="subtitle">
														<?php
														echo wp_strip_all_tags($date->sd_data['additionalFields']['FR_Date_Subtitle']);
														?>
													</h2>
													<?php
												}
												?>
												<p class="date-end">
													Jusqu'au 
													<?php
													if( $wp_today == $wp_date_end ){
														echo 'aujourd\'hui';
													}else{
														echo wp_date( 'd F', $date->sd_date_end/1000 );
													}
													?>
												</p>
											</div>
											<div class="float-right">
												<?php echo $svg_arrow; ?>
											</div>
										</div>
									</a>
								</li>
								<?php
								}
							}
						}
						?>
					</ul>
				</div>
			</section>
		</div>
		<div id="widget-upcoming">
			<section class="splide" aria-labelledby="upcoming-heading">
				<h2 id="upcoming-heading"><?php echo IVY_STRINGS['upcoming'] ?></h2>
				<div class="splide__track">
					<ul class="splide__list">
						<?php
            // Get dates (all or by category)
            if (isset($attr['category']) && $attr['category'] > 0) {
              $dates_upcoming = ivy_get_dates_upcoming_by_category($attr['category']);
            }
            else {
              $dates_upcoming = ivy_get_dates_upcoming_all();
            }
            $i = 0;
            $dates_filtered = $dates_upcoming;
            foreach ( $dates_upcoming as $date ){
              if ( 
                $date->sd_data['bookingPageStatus'] === 'hidden_on_list' || 
                $date->sd_data['bookingPageStatus'] === 'hidden' || 
                empty($date->sd_preview_available)
                ) {
                  // $is_remove = true;
                  unset ( $dates_filtered[$i] );
							}
							$i++;
						}
						$dates_upcoming = $dates_filtered;
						foreach( $dates_upcoming as $date){
              $wp_timestamp_today = strtotime(wp_date('Y-m-d'));
              $show_current = (isset($attr['show_current']) && $attr['show_current'] == true);
  						if( 
                $wp_timestamp_today <= $date->sd_date_begin/1000 || // don't show current dates
                ($show_current && $wp_timestamp_today <= $date->sd_date_end/1000)
                ) { // don't show past dates
                ?>
                <li class="splide__slide">
                  <a class="box" href="<?php echo get_permalink( $date->wp_event_id ); ?>">
                    <div class="header-image">
                      <?php
                      $event = get_post( $date->wp_event_id );
                      $img_url = Utils::get_value_by_language($event->sd_data['teaserPictureUrl']) ?: Utils::get_value_by_language($event->sd_data['headerPictureUrl']);
                      Utils::get_img_remote( $img_url, '', '', $alt = __('remote image', 'vajrayogini'), '', '', true);
                      $teaser = Utils::get_value_by_language($event->sd_data['teaser']);
//                      $labels = get_post_meta( $event->ID );
                      $labels = get_post_meta(433);
//                      $labels = get_post_meta( $date->wp_event_id , 'labels', false );
//                      $labels = get_post_meta( $date->wp_event_id , 'labels', true );
                      ?>
                    </div>
                    <div class="content">
                      <h2 class="title">
                        <?php echo $date->post_title; ?>
                      </h2>
                      <textarea style="display: none;" cols="40" rows="4"><?= json_encode($date->wp_event_id); ?></textarea>
                      <textarea style="display: none;" cols="40" rows="4"><?= json_encode($event); ?></textarea>
                      <textarea style="display: none;" cols="40" rows="4"><?= json_encode($labels); ?></textarea>
                      <textarea style="display: none;" cols="40" rows="4"><?= json_encode($labels['sd_event_id']); ?></textarea>
                      <textarea style="display: none;" cols="40" rows="4"><?= json_encode($labels['labels']); ?></textarea>
                      <?php
                      if( isset($date->sd_data['additionalFields']['FR_Date_Subtitle']) ){
                        ?>
                        <h2 class="subtitle">
                          <?php
                          echo wp_strip_all_tags($date->sd_data['additionalFields']['FR_Date_Subtitle']);
                          ?>
                        </h2>
                      <?php
                      }
                      ?>
                      <div class="details">
                        <div class="teaser">
                          <?= $teaser; ?>
                        </div>
                        <div class="type">
                          <?php
                          ivy_get_the_date_attendanceType( $date->sd_data );
                          ?>
                        </div>
                        <hr class="divider-separator">
                        <div class="duration">
                          <div class="float-left">
                            <i class="fas fa-calendar-day"></i>
                            <span>
                              <?php
                              //date start to end
                              $date_begin = $date->sd_date_begin/1000;
                              $date_end = $date->sd_date_end/1000;
                              if( wp_date('Y-m-d', $date_begin) == wp_date('Y-m-d', $date_end) && wp_date('Y-m-d') == wp_date('Y-m-d', $date_end) ){ //ends today
                                echo '<div>' . ucfirst( IVY_STRINGS['today'] ) . '</div>';
                                $format_begin = wp_date( 'i', $date_begin ) != '00' ? 'G\hi' : 'G\h';
                                $format_end = wp_date( 'i', $date_end ) != '00' ? 'G\hi' : 'G\h';
                                echo '<div><i>von ' . wp_date( $format_begin, $date_begin ) . ' à ' . wp_date( $format_end, $date_end ) . '</i></div>';
                              }elseif( wp_date('Y-m-d') == wp_date('Y-m-d', $date_begin) ){
                                echo '<div>' . ucfirst( IVY_STRINGS['today'] ) . '</div>';
                                echo '<div><i>bis</i> ' . ucfirst(wp_date( 'D. d F', $date_end )) . '</div>';
                              }elseif( wp_date('Y-m-d', $date_begin) == wp_date('Y-m-d', $date_end) ){
                                echo '<div>' . ucfirst(wp_date( 'D. d F', $date_end )) . '</div>';
                                $format_begin = wp_date( 'i', $date_begin ) != '00' ? 'G\hi' : 'G\h';
                                $format_end = wp_date( 'i', $date_end ) != '00' ? 'G\hi' : 'G\h';
                                echo '<div><i>von ' . wp_date( $format_begin, $date_begin ) . ' à ' . wp_date( $format_end, $date_end ) . '</i></div>';
                              }else{
                                echo '<div><i>von</i> ' . ucfirst(wp_date( 'D. d F', $date_begin )) . '</div>';
                                echo '<div><i>bis</i> ' . ucfirst(wp_date( 'D. d F', $date_end )) . '</div>';
                              }
                              ?>
                            </span>
                          </div>
                          <div class="float-right">
                            <?php echo $svg_arrow; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </a>
                </li>
                <?php
              }
            }
					?>
				</ul>
			</section>
			<div class="spacer"></div>
		</div>
	</div>

	<?php
	/**
	 * End of template code ...
	 */

	// disable output buffer
	$return = ob_get_contents();
	ob_end_clean();

	return $return;
}

/**
 * register custom shortcodes in WordPress
 */
add_shortcode( 'ivy-widget-agenda', 'ivy_widget_agenda');
?>