<?php
/**
 * Shortcodes.
 *
 * @package HelloIVY mod
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
		<div id="widget-upcoming">
			<section class="splide" aria-labelledby="upcoming-heading">
				<div class="splide__track">
					<div class="splide__list">
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
                
                $event = get_post( $date->wp_event_id );
                
                // Get title and subtitles
                $event_title = $event->sd_data['title'][0]['value'];
                $event_subtitle = $event->sd_data['subtitle'][0]['value'];
                $date_title = $date->post_title;
                
                // Get venue
                $venue = $date->sd_data['venue'];
                $venue_address = implode(', ', array_filter([
                  $venue['name'], 
//                  $venue['street1'], 
//                  $venue['street2'], 
//                  $venue['zip'], 
                  $venue['city'], 
                  $venue['country']
                ]));
                
                // Get status
                $booking_status = $date->sd_data['bookingPageStatus'];
                $booking_status_label = IVY_STRINGS['status'][$booking_status]??ucwords($booking_status);
                ?>
                <div class="splide__slide">
                  <a class="box" href="<?php echo get_permalink( $date->wp_event_id ); ?>">
                   <!--  <div class="header-image">
                      <?php
                      $img_url = Utils::get_value_by_language( $event->sd_data['teaserPictureUrl']) ?: Utils::get_value_by_language($event->sd_data['headerPictureUrl'] );
                      Utils::get_img_remote( $img_url, '', '', $alt = __('remote image', 'vajrayogini'), '', '', true );
                      $teaser = Utils::get_value_by_language( $event->sd_data['teaser'] );
//                      $date_categories = get_the_terms($date, 'sd_txn_labels');
                      ?>
                    </div>
                    -->
                    <div class="content">
                      <h3 class="title"><?= wp_strip_all_tags($event_title); ?></h3>
                      <?php if ($event_subtitle && $event_title !== $event_subtitle) : ?>
                        <h4 class="subtitle">
                          <?= wp_strip_all_tags($event_subtitle); ?>
                        </h4>
                      <?php endif; ?>
                      <?php if ($date_title && $date_title !== $event_title && $date_title !== $event_subtitle) : ?>
                        <h5 class="post-title">
                          <?= wp_strip_all_tags($date_title); ?>
                        </h5>
                      <?php endif; ?>
                      <?php
                      // Facilitators
                      $facilitator_posts = ivy_get_facilitators($date->sd_data['facilitators']);
                      if  ( !empty( $facilitator_posts ) ) {
                        $facilitators = [];
                        foreach ( $facilitator_posts as $facilitator_post ){
                          $facilitators[$facilitator_post->ID] = $facilitator_post->post_title;
                        }
                        ?>
                        <div class="facilitators">
                          <?= implode(', ', $facilitators); ?>
                        </div>
                        <?php
                      }
                      ?>
                      <div class="details">
                     <!--   <div class="teaser">
                          <?= $teaser; ?>
                        </div>
                        -->
                        <hr class="divider-separator">
                        <div class="detail-information">
                          <div class="float-left">
                            <div  class="date">
                              <i class="fas fa-calendar-day"></i>
                              <span>
                                <?php
                                //date start to end
                                $date_begin = $date->sd_date_begin/1000;
                                $date_end = $date->sd_date_end/1000;
                                    $format_begin_time = wp_date( 'i', $date_begin ) != '00' ? 'G\hi' : 'G\h';
                                    $format_end_time = wp_date( 'i', $date_end ) != '00' ? 'G\hi' : 'G\h';
                                // single day event of today
                                  if( wp_date('Y-m-d', $date_begin) == wp_date('Y-m-d', $date_end) && wp_date('Y-m-d') == wp_date('Y-m-d', $date_end) ){
                                  echo '<div>' . ucfirst( IVY_STRINGS['today'] ) . '</div>';
                                  $format_begin = wp_date( 'i', $date_begin ) != '00' ? 'G\hi' : 'G\h';
                                  echo ', <div>' . wp_date( $format_begin_time, $date_begin ) . ' – ' . wp_date( $format_end_time, $date_end ) . '</div>';
                                }
                                // begins today
                                elseif( wp_date('Y-m-d') == wp_date('Y-m-d', $date_begin) ){
                                  echo '<div>' . ucfirst( IVY_STRINGS['today'] ) . '</div> – <div>' . ucfirst(wp_date( 'D, d. F', $date_end )) . '</div>';
                                }
                                 // ends today
                                elseif( wp_date('Y-m-d') == wp_date('Y-m-d', $date_end) ){
                                  echo '<div>' . ucfirst(wp_date( 'D, d. F', $date_begin )) . '</div> – <div>' . ucfirst( IVY_STRINGS['today'] ) . '</div>';
                                }
                                // single day event
                                elseif( wp_date('Y-m-d', $date_begin) == wp_date('Y-m-d', $date_end) ){
                                  echo '<div>' . ucfirst(wp_date( 'D, d. F', $date_end )) . '</div>, <div>' . wp_date( $format_begin_time, $date_begin ) . ' – ' . wp_date( $format_end_time, $date_end ) . '/div>';
                                }
                                else {

                                  if( wp_date('Y', $date_begin) !== wp_date('Y', $date_end) ) {
                                    echo '<div>' . ucfirst(wp_date( 'D, d. F Y', $date_begin )) . '</div> – <div>' . ucfirst(wp_date( 'D, d. F Y', $date_end )) . '</div>';
                                  }
                                  elseif( wp_date('m', $date_begin) !== wp_date('m', $date_end) && wp_date('Y', $date_begin) == wp_date('Y', $date_end) ) {
                                    echo '<div>' . ucfirst(wp_date( 'D, d. F', $date_begin )) . '</div> – <div>' . ucfirst(wp_date( 'D, d. F Y', $date_end )) . '</div>';
                                  }
                                  else {
                                    echo '<div>' . ucfirst(wp_date( 'D, d.', $date_begin )) . '</div> – <div>' . ucfirst(wp_date( 'D, d. F Y', $date_end )) . '</div>';
                                  }
                                }
                                ?>
                              </span>
                            </div>
                            <div class="location">
                              <?php if ($venue_address) : ?>
                              <i class="fas fa-map-marker-alt"></i>
                              <span><?= $venue_address ?></span>
                              <?php endif; ?>
                            </div>
                            <div class="status">
                              <span><?= $booking_status_label ?></span>
                            </div>
                          </div>
                          <div class="float-right">
                            <?php echo $svg_arrow; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </a>
                </div>
                <?php
              }
            }
					?>
				</div>
			</section>
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