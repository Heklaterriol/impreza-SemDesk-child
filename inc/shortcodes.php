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

const SD_CATEGORIES_PARENT_LABEL_ID = 1;

/**
 * Shortcode function for agenda widget
 * e.g. [sd-widget-agenda show_filters=1 category=44 limit=3]
 * @param array $atts - Optional - ['show_filters', 'category´, 'show_current', 'limit']
 * @return string - Rendered seminar list as HTML
 */
function sd_widget_agenda( $atts ) {
	$atts = shortcode_atts ([
      'show_filters' => 0, // Show category filter for visitor? boolean, default: false
      'show_current' => 1, // Show running seminars? boolean, default: true
      'category' => 0, // Filter by category (SeminarDesk Label ID)? integer, 0 (=show all)
      'limit' => 0, // Limit number of items in list? integer, default: 0 (=no limit)
	], $atts);
  $category = $atts['category'] ?: $_GET['category'] ?? 0;
  
	// enable output buffering
	ob_start();

	/**
	 * BEGIN of template code ...
	 */

	$svg_arrow = '<div class="arrow"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" width="40" height="40" focusable="false"><path d="m15.5 0.932-4.3 4.38 14.5 14.6-14.5 14.5 4.3 4.4 14.6-14.6 4.4-4.3-4.4-4.4-14.6-14.6z"></path></svg></div>'
	?>
	<div id="shortcode-agenda">
		<div class="spacer"></div>
    <?php if ($atts['show_filters']) : ?>
      <form action="">
				<label for="categories">Kategorie</label>
				<select name="category" id="select-sd-category" onchange="onchangeSelect(this)">
					<?php
          // Get and list categories
          $categories = get_term_by( 'name', 'lg_id_'.SD_CATEGORIES_PARENT_LABEL_ID, 'sd_txn_labels' );
					$child_categories_id = get_term_children( $categories->term_id, 'sd_txn_labels' );
					echo '<option value="all">Kategorie (Alle)</option>';
					foreach ( $child_categories_id as $child_category_id ){
						$term_category = get_term( $child_category_id );
						echo '<option value="' . $term_category->slug . '">' . ucfirst( $term_category->description ) . '</option>';
					}
					?>
				</select>
			</form>

    <?php endif; ?>
		<!--<div id="widget-current">
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
		</div>-->
		<div id="widget-upcoming">
			<section class="splide" aria-labelledby="upcoming-heading">
				<h2 id="upcoming-heading"><?php echo IVY_STRINGS['upcoming'] ?></h2>
				<div class="splide__track">
					<ul class="splide__list">
						<?php
            // Get dates
            $dates_upcoming = ivy_get_dates_upcoming_all();
            
            $i = 0;
            $dates_filtered = $dates_upcoming;
            foreach ( $dates_upcoming as $date ){
              if ( 
                $date->sd_data['bookingPageStatus'] === 'hidden_on_list' || 
                $date->sd_data['bookingPageStatus'] === 'hidden' || 
                empty($date->sd_preview_available) || 
                $category && !sd_date_has_category($date, $category)

                ) {
                  // $is_remove = true;
                  unset ( $dates_filtered[$i] );
							}
							$i++;
						}
						$dates_upcoming = $dates_filtered;
            $count = 0;
						foreach( $dates_upcoming as $date){
              $wp_timestamp_today = strtotime(wp_date('Y-m-d'));
  						if (
                (
                  // Category filter?
                  !$category || 
                  sd_date_has_category($date, $category)
                ) &&
                (
                  // Date filter: Don't show past dates (date_begin < today)
                  $wp_timestamp_today <= $date->sd_date_begin/1000 ||
                  // Show current dates? (date_end < today)
                  ($atts['show_current'] && $wp_timestamp_today <= $date->sd_date_end/1000)
                ) && 
                (
                  // Limit items?
                  ($atts['limit'] == 0 || $count < $atts['limit'])
                )
              ) {
                $count++;
                ?>
                <li class="splide__slide">
                  <a class="box" href="<?php echo get_permalink( $date->wp_event_id ); ?>">
                    <div class="header-image">
                      <?php
                      $event = get_post( $date->wp_event_id );
                      $img_url = Utils::get_value_by_language( $event->sd_data['teaserPictureUrl']) ?: Utils::get_value_by_language($event->sd_data['headerPictureUrl'] );
                      Utils::get_img_remote( $img_url, '', '', $alt = __('remote image', 'vajrayogini'), '', '', true );
                      $teaser = Utils::get_value_by_language( $event->sd_data['teaser'] );
                      $date_categories = get_the_terms($date, 'sd_txn_labels');
                      ?>
                    </div>
                    <div class="content">
                      <h2 class="title">
                        <?= $date->post_title; ?>
                      </h2>
                      <?php
                      if( isset($date->sd_data['additionalFields']['FR_Date_Subtitle']) ){
                        ?>
                        <h2 class="subtitle">
                          <?= wp_strip_all_tags($date->sd_data['additionalFields']['FR_Date_Subtitle']); ?>
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
                                echo '<div><i> von ' . wp_date( $format_begin, $date_begin ) . ' bis ' . wp_date( $format_end, $date_end ) . '</i></div>';
                              }elseif( wp_date('Y-m-d') == wp_date('Y-m-d', $date_begin) ){
                                echo '<div>' . ucfirst( IVY_STRINGS['today'] ) . '</div>';
                                echo '<div><i> bis </i> ' . ucfirst(wp_date( 'D. d F', $date_end )) . '</div>';
                              }elseif( wp_date('Y-m-d', $date_begin) == wp_date('Y-m-d', $date_end) ){
                                echo '<div>' . ucfirst(wp_date( 'D. d F', $date_end )) . '</div>';
                                $format_begin = wp_date( 'i', $date_begin ) != '00' ? 'G\hi' : 'G\h';
                                $format_end = wp_date( 'i', $date_end ) != '00' ? 'G\hi' : 'G\h';
                                echo '<div><i> von ' . wp_date( $format_begin, $date_begin ) . ' bis ' . wp_date( $format_end, $date_end ) . '</i></div>';
                              }else{
                                echo '<div><i> von</i> ' . ucfirst(wp_date( 'D. d F', $date_begin )) . '</div>';
                                echo '<div><i> bis</i> ' . ucfirst(wp_date( 'D. d F', $date_end )) . '</div>';
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
add_shortcode( 'sd-widget-agenda', 'sd_widget_agenda' );
?>