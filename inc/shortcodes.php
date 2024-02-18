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

// Where to find all labels that represent event categories in SeminarDesk
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
          <!--<label for="categories">Kategorie</label>-->
          <select name="category" id="select-sd-category" onchange="onChangeSelect(this)">
            <?php
            // Get and list categories
            $categories = get_term_by( 'name', 'lg_id_'.SD_CATEGORIES_PARENT_LABEL_ID, 'sd_txn_labels' );
            $child_categories_id = get_term_children( $categories->term_id, 'sd_txn_labels' );
            echo '<option value="all">Kategorie (Alle)</option>';
            foreach ( $child_categories_id as $child_category_id ){
                $term_category = get_term( $child_category_id );
                echo '<option value="' . $term_category->slug . '"' . (($term_category->slug === $category)?' selected':'') . '>' . ucfirst( $term_category->description ) . '</option>';
            }
            ?>
          </select>
        </form>
        <div class="w-separator size_small"></div>
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
                        $venue['city'], 
                        $venue['country']
                      ]));

                      // Get status
                      $booking_status = $date->sd_data['bookingPageStatus'];
                      $booking_status_label = IVY_STRINGS['status'][$booking_status]??ucwords($booking_status);
                      ?>
                      <div class="splide__slide">
                        <a class="box" href="<?php echo get_permalink( $date->wp_event_id ); ?>">
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
              <?php if ($count === 0) : ?>
                <div class="sd-no-events">Zu dieser Kategorie sind aktuell keine Seminare verfügbar.</div>
              <?php endif; ?>
            </div>
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



/** ###############################################
 * shortcode sd-widget-agenda-flex
 ##################################################  */



function sd_widget_agenda_flex( $atts ) {
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
     * BEGIN of template code
     */

    ?>
    <div class="sd-component sd-events">
    <div class="sd-eventlist">
    <?php if ($atts['show_filters']) : ?>
        <form action="">
            <!--<label for="categories">Kategorie</label>-->
            <select name="category" id="select-sd-category" onchange="onChangeSelect(this)">
                <?php
                // Get and list categories
                $categories = get_term_by( 'name', 'lg_id_'.SD_CATEGORIES_PARENT_LABEL_ID, 'sd_txn_labels' );
                $child_categories_id = get_term_children( $categories->term_id, 'sd_txn_labels' );
                echo '<option value="all">Kategorie (Alle)</option>';
                foreach ( $child_categories_id as $child_category_id ){
                    $term_category = get_term( $child_category_id );
                    echo '<option value="' . $term_category->slug . '"' . (($term_category->slug === $category)?' selected':'') . '>' . ucfirst( $term_category->description ) . '</option>';
                }
                ?>
            </select>
        </form>
        <div class="w-separator size_small"></div>
    <?php endif; ?>
                    
    <?php

    // ################## Get dates ##################
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
          // ################## $is_remove = true; ##################
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
          // ################## Category filter? ##################
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
          // ################## Limit items? ##################
          ($atts['limit'] == 0 || $count < $atts['limit'])
        )
      ) {
        $count++;

        $event = get_post( $date->wp_event_id );

        // Get title and subtitles
        $event_title = $event->sd_data['title'][0]['value'];
        $event_subtitle = $event->sd_data['subtitle'][0]['value'];
        $date_title = $date->post_title;

        $event_main_title = !empty($date->post_title) ? $date->post_title : $event->sd_data['title'][0]['value'];        

        // ##################Get venue ##################
        $venue = $date->sd_data['venue'];
        $venue_address = implode(', ', array_filter([
          $venue['name'], 
//                  $venue['street1'], 
//                  $venue['street2'], 
//                  $venue['zip'], 
          $venue['city'], 
          $venue['country']
        ]));

        // ################## Get status ##################
        $booking_status = $date->sd_data['bookingPageStatus'];
        $booking_status_label = IVY_STRINGS['status'][$booking_status]??ucwords($booking_status);

        // ################## Get facilitators ##################
        $facilitator_posts = ivy_get_facilitators($date->sd_data['facilitators']);
        unset($eventfacilitators);
        if ( !empty( $facilitator_posts ) ) {
            $facilitators = [];
            foreach ( $facilitator_posts as $facilitator_post ) {
                $facilitators[$facilitator_post->ID] = $facilitator_post->post_title;
            }
            $eventfacilitators = implode(', ', $facilitators);
            }

        // ################## Get teaser text ################## 
        // $teaser = Utils::get_value_by_language( $event->sd_data['teaser'] );

        // ################## Get date categories ################## 
        // $date_categories = get_the_terms($date, 'sd_txn_labels');

        ?>
            <div class="sd-event">
                <a 
                  href="<?php echo get_permalink( $date->wp_event_id ); ?>" 
                  itemprop="url" 
                  target="_parent" 
                  class="box<?php if (!empty( $eventfacilitators ) ) { echo ' has-facilitator'; } ?><?php if (!empty( $venue['name'] ) ) { echo ' has-location'; } ?>"
                >
               
                <?php
                // ################## Get event teaser image, fallback if not set ################## 
                if (!empty(Utils::get_value_by_language( $event->sd_data['teaserPictureUrl']))) {
                    $img_url = Utils::get_value_by_language( $event->sd_data['teaserPictureUrl']) . '?1';
                }
                else {
                	$img_url = '/wp-content/themes/Impreza-child/assets/seminar-image-default.jpg?4';
                }
                ?>
            
                 <div class="sd-event-image" style="background-image: url(<?php echo $img_url; ?>);" /></div>
                <!--  ################## Get date/time ##################  -->
                
                <div class="sd-event-date">
                       <time itemprop="startDate" datetime="<?= wp_date('Y-m-d\TG:i:sO', $date_begin) ?>" content="<?= wp_date('Y-m-d\TG:i:sO', $date_begin) ?>">
                <?php
                // ################## dates & times ################## 
                    $date_begin = $date->sd_date_begin/1000;
                    $date_end = $date->sd_date_end/1000;
                    $format_begin_time = wp_date( 'i', $date_begin ) != '00' ? 'G\hi' : 'G\h';
                    $format_end_time = wp_date( 'i', $date_end ) != '00' ? 'G\hi' : 'G\h';
                        
                               
                    // single day event of today
                    if( wp_date('Y-m-d', $date_begin) == wp_date('Y-m-d', $date_end) ){
                        echo '<span class="sd-event-begindate"' . ucfirst(wp_date( 'j.n.', $date_end )) . '</span><span class="date-separator"></span>
                        <span class="sd-event-enddate">' . $format_begin_time . '</span>';
                    }
                    else {
                           if ( wp_date('Y', $date_begin) !== wp_date('Y', $date_end) ) {
                            echo '<span class="sd-event-begindate">' . ucfirst(wp_date( 'j.n.Y', $date_begin )) . '</span><span class="date-separator"> - </span>
                            <span class="sd-event-enddate"><span>' . ucfirst(wp_date( 'j.n.Y', $date_end )) . '</span></span>';
                        }
                        elseif ( wp_date('m', $date_begin) !== wp_date('m', $date_end) && wp_date('Y', $date_begin) == wp_date('Y', $date_end) )
                          {
                            echo '<span class="sd-event-begindate">' . ucfirst(wp_date( 'j.n.', $date_begin )) . '</span><span class="date-separator"> - </span>
                            <span class="sd-event-enddate wrap">' . ucfirst(wp_date( 'j.n.', $date_end )) . '<span>' . ucfirst(wp_date( 'Y', $date_end )) . '</span></span>';
                        }
                        else {
                            echo '<span class="sd-event-begindate">' . ucfirst(wp_date( 'j.', $date_begin )) . '</span><span class="date-separator"> - </span>
                            <span class="sd-event-enddate wrap">' . ucfirst(wp_date( 'j.n.', $date_end )) . '<span>' . ucfirst(wp_date( 'Y', $date_end )) . '</span></span>';
                        }
                    }    
                ?>
            </time>
            <time itemprop="endDate" datetime="<?= wp_date('Y-m-d\TG:i:sO', $date_end) ?>"></time>
        </div>
         <div class="sd-event-title">
            <h4 itemprop="name"><?= wp_strip_all_tags($event_main_title); ?></h4>
            <p class="subtitle"><?php if ($event_subtitle && $event_main_title !== $event_subtitle) : echo wp_strip_all_tags($event_subtitle);
            endif; ?>
            </p>
        </div>
    
        <div class="sd-event-facilitators" itemprop="organizer">
            <?php if (!empty( $eventfacilitators)) { echo $eventfacilitators; }; ?>
        </div>
        <div class="sd-event-categories">
            <!-- <!-- hide categories for now -->
        </div>
        <div class="sd-event-registration">
            <?= $booking_status_label ?>
            <!-- show status for future events only -->
        </div>
        <!-- <div class="sd-event-external">
            Gastveranstaltung
        </div>
        -->
        <?php if (!empty($venue['name'])) { ?>
            <div class="sd-event-location">
                <?php echo $venue['name'] . (!empty($venue['city']) ? ', ' . $venue['city'] : ''); ?>
            </div>
        <?php } ?>
        
        <div class="sd-event-location hidden" itemprop="location" itemscope="" itemtype="https://schema.org/Place">
            <span itemprop="name"><?= $venue['name'] ?></span>
            <div class="address" itemprop="address" itemscope="" itemtype="https://schema.org/PostalAddress">
                <span itemprop="streetAddress"><?= $venue['street1'] ?>, <?= $venue['street2'] ?></span><br />
                <span itemprop="postalCode"><?= $venue['zip'] ?></span> <span itemprop="addressLocality"><?= $venue['city'] ?></span>, <span itemprop="addressCountry"><?= $venue['country'] ?></span>
            </div>
        </div>     
                </a>
            </div>
            <?php
            }
        }
        ?>
        <?php if ($count === 0) : ?>
            <div class="sd-no-events">Zu dieser Kategorie sind aktuell keine Seminare verfügbar.</div>
        <?php endif; ?>
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
add_shortcode( 'sd-widget-agenda-flex', 'sd_widget_agenda_flex' );



/** ###############################################
 * shortcode sd-widget-agenda-mini
 ##################################################  */

function sd_widget_agenda_mini( $atts ) {
    $atts = shortcode_atts ([
      'category' => 0, // Filter by category (SeminarDesk Label ID)? integer, 0 (=show all)
      'limit' => 0, // Limit number of items in list? integer, default: 0 (=no limit)
    ], $atts);
  $category = $atts['category'] ?: $_GET['category'] ?? 0;
  
    // enable output buffering
    ob_start();

    /**
     * BEGIN of template code ...
     */

    ?>
    <div id="shortcode-agenda-mini">
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
                  $wp_timestamp_today <= $date->sd_date_begin/1000
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
                
                // Get venue
                $venue = $date->sd_data['venue'];
                $venue_address = implode(', ', array_filter([
                  $venue['name'], 
//                  $venue['street1'], 
//                  $venue['street2'], 
//                  $venue['zip'], 
                  $venue['city']
                ]));
                
                // Get status
                $booking_status = $date->sd_data['bookingPageStatus'];
                $booking_status_label = IVY_STRINGS['status'][$booking_status]??ucwords($booking_status);
                ?>
                <div class="sd-event">
                  <a class="box" href="<?php echo get_permalink( $date->wp_event_id ); ?>">
                  
                <div class="content">
                  
                    <div class="sd-event-date ">
                    <?php
                                //date start to end
                                $date_begin = $date->sd_date_begin/1000;
                                $date_end = $date->sd_date_end/1000;
                                    $format_begin_time = wp_date( 'i', $date_begin ) != '00' ? 'G\hi' : 'G\h';
                                    $format_end_time = wp_date( 'i', $date_end ) != '00' ? 'G\hi' : 'G\h';
                                // single day event of today
                                  if( wp_date('Y-m-d', $date_begin) == wp_date('Y-m-d', $date_end) && wp_date('Y-m-d') == wp_date('Y-m-d', $date_end) ){
                                  echo '<span>' . ucfirst( IVY_STRINGS['today'] ) . '</span>';
                                  $format_begin = wp_date( 'i', $date_begin ) != '00' ? 'G\hi' : 'G\h';
                                  echo ', <span>' . wp_date( $format_begin_time, $date_begin ) . ' – ' . wp_date( $format_end_time, $date_end ) . '</span>';
                                }
                                // begins today
                                elseif( wp_date('Y-m-d') == wp_date('Y-m-d', $date_begin) ){
                                  echo '<span>' . ucfirst( IVY_STRINGS['today'] ) . '</span> – <span>' . ucfirst(wp_date( 'D, d. F', $date_end )) . '</span>';
                                }
                                 // ends today
                                elseif( wp_date('Y-m-d') == wp_date('Y-m-d', $date_end) ){
                                  echo '<span>' . ucfirst(wp_date( 'd.m.', $date_begin )) . '</span> – <span>' . ucfirst( IVY_STRINGS['today'] ) . '</span>';
                                }
                                // single day event
                                elseif( wp_date('Y-m-d', $date_begin) == wp_date('Y-m-d', $date_end) ){
                                  echo '<span>' . ucfirst(wp_date( 'd.m.', $date_end )) . '</span>, <span>' . wp_date( $format_begin_time, $date_begin ) . ' – ' . wp_date( $format_end_time, $date_end ) . '</span>';
                                }
                                else {

                                  if( wp_date('Y', $date_begin) !== wp_date('Y', $date_end) ) {
                                    echo '<span>' . ucfirst(wp_date( 'd.m.Y', $date_begin )) . '</span> – <span>' . ucfirst(wp_date( 'D, d. F Y', $date_end )) . '</span>';
                                  }
                                  elseif( wp_date('m', $date_begin) !== wp_date('m', $date_end) && wp_date('Y', $date_begin) == wp_date('Y', $date_end) ) {
                                    echo '<span>' . ucfirst(wp_date( 'd.m.', $date_begin )) . '</span> – <span>' . ucfirst(wp_date( 'D, d. F Y', $date_end )) . '</span>';
                                  }
                                  else {
                                    echo '<span>' . ucfirst(wp_date( 'd.', $date_begin )) . '</span> – <span>' . ucfirst(wp_date( 'd.m.Y', $date_end )) . '</span>';
                                  }
                                }
                                ?>
                    <br />
                    <span class="title"><strong><?= wp_strip_all_tags($event_title); ?></strong></span>
                    <?php if ($event_subtitle && $event_title !== $event_subtitle) : ?>
                       <span class="subtitle"> - <?= wp_strip_all_tags($event_subtitle); ?></span>
                    <?php endif; ?>
                    <br />
                    <?php if ($venue_address) : ?>
                      <span><?= $venue_address ?></span>
                    <?php endif; ?>
                </div>
                </div>
                </a>
                <?php
              }
            }
                    ?>
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
add_shortcode( 'sd-widget-agenda-mini', 'sd_widget_agenda_mini' );