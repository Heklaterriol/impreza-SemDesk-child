<?php

error_reporting(E_ALL);

/**
 * Custom template for event detail page, single post of CPT sd_cpt_event.
 * 
 * @package HelloIVY
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
	
	$desc = wp_strip_all_tags( $event_sd_af['DE_Event_Short_Description'] );
	$desc_trimmed = mb_strimwidth( $desc, 0, 160, "..." );
	return $desc_trimmed;
}
add_filter( 'wpseo_opengraph_desc', 'ivy_wpseo_change_desc' );

get_header();

/**
 * main code here...
 */

?>
<main id="site-content" role="main">
	<?php
	// loop
	if (have_posts()) {
		while (have_posts()) {
			the_post();
			// loop parameters
			?>
			<header class="entry-header">
				<div class="entry-header-inner section-inner medium">
				<!-- <p>custom IVY event detail page</p> -->
					<?php 
					$header_img_url = Utils::get_value_by_language($post->sd_data['headerPictureUrl']);
					Utils::get_img_remote( $header_img_url, '500', '', $alt = __('remote image', 'vajrayogini'), '', '', true);
					?>
					<span class="divider-separator">
						<?php
						Utils::get_value_by_language( $post->sd_data['title'], '', '<h1 class="archive-title">', '</h1>', true);
						?>
					</span>
				</div>
			</header>
			<div class="post-meta-wrapper post-meta-single post-meta-single-top">
				<?php
				/**
				 * Event short description
				 */
				// echo Utils::get_value_by_language($post->sd_data['description']);
				echo '<div class="event-short-description">' . ivy_strip_html_styles( $event_sd_af['DE_Event_Short_Description'] ) . '</div>';
				/**
				 * Course detail page button
				 */
				if ( !empty( $event_sd_af['DE_Course_Page_Button_URL'] ) ) {
					$url_course_page = $event_sd_af['DE_Course_Page_Button_URL'];
					$text_course_page = $event_sd_af['DE_Course_Page_Button_Text'];
					?>
					<div class="course-page">
						<div class="button">
							<a href="<?php echo $url_course_page;?>">
								<span>
									<i class="fas fa-plus"></i>
									<a href="<?php echo $url_course_page;?>">
										<?php echo $text_course_page;?>
									</a>
								</span>
							</a>
						</div>
					</div>
					<?php
				}
				/**
				 * Facilitators
				 */
				$facilitator_posts = ivy_get_facilitators($post->sd_data['facilitators']);
				if  ( !empty( $facilitator_posts ) ) {
					?>
					<div class="facilitators-entry">
						<h3>
							<?php 
							echo count( $facilitator_posts ) > 1 ? 'Intervenants :' : 'Intervenant :';
							?>
						</h3>
						<div class="facilitators-container">
							<?php
							foreach ( $facilitator_posts as $facilitator_post ){
								?>
								<div class="facilitator-col">
									<?php
									Utils::get_img_remote( esc_url( $facilitator_post->sd_data['pictureUrl'] ), '', '', $alt = __('remote image', 'vajrayogini'), '', '', true);
									?><div class="flex-row-break"></div><?php
									echo '<a>' . wp_strip_all_tags( $facilitator_post->post_title ) . '</a>';
									?>
								</div>
								<?php
							}
							?>
						</div>
					</div>
					<?php
				}
				/**
				 * upcoming event dates
				 */
				$date_posts = ivy_get_dates_upcoming_by_id($event_sd_id);
				
				// remove dates from array which shouldn't be listed in upcoming events
				$i = 0;
				$dates_filtered = $date_posts;
				foreach ( $date_posts as $date ){
					$key = $date->sd_data['bookingPageStatus'];
					if ( $date->sd_data['bookingPageStatus'] === 'hidden_on_list' || $date->sd_data['bookingPageStatus'] === 'hidden' ) {
						// $is_remove = true;
						unset ( $dates_filtered[$i] );
					}
					$i++;
				}
				$date_posts = $dates_filtered;
				// has dates ... 
				if ( !empty( $date_posts ) ){
					// has upcoming event dates
					?>
						<h3>
							<?php 
							echo count( $date_posts ) > 1 ? 'Dates disponibles :' : 'Date disponible :';
							?>
						</h3>
						<div class="dates-entry">
							<?php
							foreach ( $date_posts as $date_post ){
								$status = $date_post->sd_data['bookingPageStatus'];
								$date_sd_id = $date_post->sd_date_id;
								$date_sd_af = $date_post->sd_data['additionalFields']
								?>
								<div class="date-container">
									<div class="date-title">
										<?php
										echo '<h3>' . wp_strip_all_tags($date_post->post_title, true) . '</h3>';
										$date_sd_subtitle = wp_strip_all_tags( $date_sd_af['DE_Date_Subtitle'], true );
										echo !empty( $date_sd_subtitle ) ? '<p class="date-subtitle">' . $date_sd_subtitle . '</p>' : null;
										?>
									</div>
									<div class="col date-duration">
										<div class="wrap">
											<?php
											$date_sd_from = $date_post->sd_date_begin/1000;
											$date_sd_till = $date_post->sd_date_end/1000;
											ivy_the_date_duration( $date_sd_from, $date_sd_till );
											?>
										</div>
									</div>
									<div class="col">
										<div class="wrap">
											<div class="date-access">
												<?php
												//attendance type
												$date_sd_attendance_type = $date_post->sd_data['attendanceType'];
												echo $date_sd_attendance_type === 'ONLINE' || $date_sd_attendance_type === 'SELECTABLE' ? IVY_STRINGS['online']: null;
												echo ( $date_sd_attendance_type === 'ONLINE' && $date_sd_attendance_type === 'ON_SITE' ) || $date_sd_attendance_type === 'SELECTABLE' ? ' | ' : null;
												echo $date_sd_attendance_type === 'ON_SITE' || $date_sd_attendance_type === 'SELECTABLE' ? IVY_STRINGS['onsite']: null;
												?>
											</div>
											<?php
											// date level - label
											ivy_get_the_date_levels( $date_post->ID );
											?>
										</div>
									</div>
									<div class="col">
										<?php
										// date price infos
										// TODO adjust SD settings
										// $price_info = wp_strip_all_tags( Utils::get_value_by_language($date->sd_data['priceInfo'], IVY_STRINGS['lang'], '', '', false ) );
										$price_info = Utils::get_value_by_language($date_post->sd_data['priceInfo'], IVY_STRINGS['lang'], '', '', false );
										$out = array( 'Europlus', 'Euro', 'plus', '/', 'Hébergement', 'Repas');
										$in = array( '€ | Plus ', '€ ', 'Plus ',' et ', 'hébergement', 'repas');
										echo str_replace($out, $in, $price_info);
										?>
									</div>
									<div class="col">
										<div class="wrap">
											<?php
											// event date status
											if ( $status != 'available' && $status !='fully_booked' && $status !='hidden_on_list' && !empty( $status ) ) {
												$out = array( 'limited', 'fully_booked', 'wait_list', 'available', 'canceled');
												$in = array( 'Seulement quelques places disponibles', 'Complet', 'Liste d\'attente', '', 'Annulé');
												echo '<p class="date-status status-' . $status . '">' . str_replace($out, $in, $status). '</p>';
											}
											// flexible attendance
											$is_flex = $date_post->sd_data['allowFlexibleAttendance'];
											echo $is_flex === true ? '<p class="date-flexible">Participation flexible</p>' : null;
											// registration_start
											$reg_start = $date_post->sd_data['registrationStart'];
											$format_reg_start = wp_date( 'i', $reg_start/1000 ) != '00' ? 'j/n/Y G\Hi' : 'j/n/Y G\H';
											$timestamp_today = strtotime(wp_date('Y-m-d')); // current time
											echo !empty($reg_start) && $reg_start/1000 > $timestamp_today ? '<p class="date-status status-limited">L’inscription commence le ' . wp_date( $format_reg_start, $reg_start/1000) . '</p>' : null;
											// registration end
											$reg_end = $date_post->sd_data['registrationEnd'];
											$format_reg_end = wp_date( 'i', $reg_start/1000 ) != '00' ? 'j/n/Y G\Hi' : 'j/n/Y G\H';
											echo !empty($reg_end) ? '<p class="date-status status-limited">L\'inscription termine le ' . wp_date( $format_reg_end ,$reg_end/1000) . '</p>' : null;
											?>
										</div>
									</div>
									<?php
									/**
									 * Details Accordion
									 */
									if ( !empty( wp_strip_all_tags( $date_sd_af['DE_Date_Schedule'] ) ) || !empty( wp_strip_all_tags( $date_sd_af['DE_Date_Description'] ) ) ){
										?>
										<div class="event-details">
											<a class="accordion">
												Détails et horaires
												<i class="fa-toggle fas fa-angle-right"></i>
												<i class="fa-toggle fas fa-angle-down"></i>
											</a>
											<div class="box">
												<div class="text-box">
													<?php
													echo '<p class="schedule">' . ivy_strip_html_styles( $date_sd_af['DE_Date_Schedule'] ) . '</p>';
													echo '<p class="description">' . ivy_strip_html_styles( $date_sd_af['DE_Date_Description'] ) . '</p>';
													?>
												</div>
												<button class="read-more"><span><i class="icon fas fa-plus"></i><i class="text">Lire la suite...</i></span></button>
											</div>
											</div>
										<?php
									}
									/**
									 * Registration detail boxes
									 */
									?>
									<div class="col date-registrations">
										<?php
										/**
										 * Box - Online registration required?
										 */
										if ( $date_sd_attendance_type === 'ONLINE' || $date_sd_attendance_type === 'SELECTABLE' ) {
											$booking_page_url = Utils::get_value_by_language( $event_sd_data['bookingPageUrl'] );
											$booking_page_url = str_replace( 'https://booking.seminardesk.de/', 'https://booking.seminardesk.de/de/', $booking_page_url );
											if ( $date_sd_af['Online_Required_Registration'] === true && !empty( $booking_page_url ) && $status !== 'fully_booked' && $status !== 'canceled' ){
												// with registration option
												/**
												 * Booking Modal
												 */
												$modal_id++; // modal counter
												?>
												<div class="box">
													<h4>Participation en ligne</h4>
													<div class="registration-yes">
														<button class="modal-button" type="button" href="#modal-booking-<?php echo $modal_id?>">Inscription</button>
													</div>
												</div>
													<!-- BEGIN modal content -->
													<div id="modal-booking-<?php echo $modal_id?>" class="modal">
														<div class="modal-content">
															<div class="modal-header">
																<span class="close">×</span>
																<h2>Inscription</h2>
															</div>
															<div class="modal-body">
																<iframe class="iframe-sd-booking" src="" data-src="<?php echo $booking_page_url; ?>/embed?eventDateId=<?php echo $date_sd_id; ?>&attendanceType=ONLINE&hideAttendanceTypeSelection=true" title="SeminarDesk Inscription"></iframe>
															</div>
															<!-- <div class="modal-footer">
																<h3>Modal Footer</h3>
															</div> -->
														</div>
													</div>
													<!-- END modal content -->
												<?php
											}elseif ( $date_sd_af['Online_Required_Registration'] === true && !empty( $booking_page_url ) && $status === 'fully_booked' ){
												// fully booked
												?>
												<div class="box">
													<h4>Participation en ligne</h4>
														<div class="registration-full">
															<a class="button" href="#full">Complet</a>
														</div>
												</div>
												<?php
											}elseif( $status === 'canceled'  ){
												// status canceled
												?>
												<div class="box">
													<h4>Participation sur place</h4>
														<div class="registration-canceled">
															<a class="button" href="#canceled">Annulé</a>
														</div>
												</div>
												<?php
											}
											else{
												// without registration option
												?>
												<div class="box">
													<h4>Participation en ligne</h4>
													<p class="no-registartion">Sans inscription</p>
													<div class="registration-col">
														<?php
														// Webcast 1
														if (  !empty( $date_sd_af['DE_URL_Webcast_1'] ) ){
															?>
															<div class="webcast">
																<a href="<?php echo $date_sd_af['DE_URL_Webcast_1']; ?>">
																	<img src="<?php echo $date_sd_af['Image_Webcast_1']; ?>" title="Logo-Webcast-1" alt="Logo de webcast 1" class="ivy-animation-grow lazyloaded" data-src="<?php echo $date_sd_af['Image_Webcast_1']; ?>" loading="lazy"><noscript><img src="<?php echo $date_sd_af['Image_Webcast_1']; ?>" title="Logo-Webcast-1" alt="Logo de webcast 1" class="ivy-animation-grow" data-eio="l" /></noscript>
																</a>
															</div>
															<?php
														}
														// Webcast 2
														if (  !empty( $date_sd_af['DE_URL_Webcast_2'] ) ){
															?>
															<div class="webcast">
																<a href="<?php echo $date_sd_af['DE_URL_Webcast_2']; ?>">
																	<img src="<?php echo $date_sd_af['Image_Webcast_2']; ?>" title="Logo-Webcast-2" alt="Logo de webcast 2" class="ivy-animation-grow lazyloaded" data-src="<?php echo $date_sd_af['Image_Webcast_2']; ?>" loading="lazy"><noscript><img src="<?php echo $date_sd_af['Image_Webcast_2']; ?>" title="Logo-Webcast-2" alt="Logo de webcast 2" class="ivy-animation-grow" data-eio="l" /></noscript>
																</a>
															</div>
														<?php
														}
														// Webcast 3
														if (  !empty( $date_sd_af['DE_URL_Webcast_3'] ) ){
															?>
															<div class="webcast">
																<a href="<?php echo $date_sd_af['DE_URL_Webcast_3']; ?>">
																	<img src="<?php echo $date_sd_af['Image_Webcast_3']; ?>" title="Logo-Webcast-3" alt="Logo de webcast 3" class="ivy-animation-grow lazyloaded" data-src="<?php echo $date_sd_af['Image_Webcast_3']; ?>" loading="lazy"><noscript><img src="<?php echo $date_sd_af['Image_Webcast_3']; ?>" title="Logo-Webcast-3" alt="Logo de webcast 3" class="ivy-animation-grow" data-eio="l" /></noscript>
																</a>
															</div>
														<?php
														}
														?>
													</div>
													<div class="registration-col">
														<?php
														// Documents button
														if ( !empty( $date_sd_af['DE_URL_Documents_Webcast'] ) ){
															?>
															<div class="registration-no">
																	<a class="button" href="<?php echo $date_sd_af['DE_URL_Documents_Webcast'] ?>">
																		<div aria-hidden="true" class="fas fa-download"></div>
																		Documents
																	</a>
															</div>
															<?php
														}
														// Dons button
														if ( !empty( $date_sd_af['DE_URL_Donations_Webcast'] ) ){
															?>
															<div class="registration-no">
																<a class="button" href="<?php echo $date_sd_af['DE_URL_Donations_Webcast'] ?>">
																	<div aria-hidden="true" class="fas fa-hand-holding-heart"></div>
																	Dons
																</a>
															</div>
															<?php
														}
														?>
													</div>
												</div>
												<?php
											}
										}
										/**
										 * Box - On-site registration required?
										 */
										if ( $date_sd_attendance_type === 'ON_SITE' || $date_sd_attendance_type === 'SELECTABLE' ){
											$booking_page_url =  Utils::get_value_by_language( $event_sd_data['bookingPageUrl'] );
											$booking_page_url = str_replace( 'https://booking.seminardesk.de/', 'https://booking.seminardesk.de/fr/', $booking_page_url );
											$close_onsite_reg = $date_sd_af['Onsite_reg_fully booked_and_Online_reg_still_open']; // CF to close onsite registration, but keep online registration open
											// count( $date_posts ) > 1 ? 'Dates disponibles :' : 'Date disponible :';
											if ( $date_sd_af['Onsite_Required_Registration'] === true && !empty( $booking_page_url ) && $status !== 'fully_booked' && empty($close_onsite_reg) && $status !== 'canceled' ){
												// with registration option
												/**
												 * Booking Modal
												 */
												$modal_id++; // modal counter
												?>
												<div class="box">
													<h4>Participation sur place</h4>
														<div class="registration-yes">
															<button class="modal-button" type="button" href="#modal-id-<?php echo $modal_id?>">Inscription</button>
														</div>
												</div>
												<!-- BEGIN modal content -->
												<div id="modal-id-<?php echo $modal_id?>" class="modal">
													<div class="modal-content">
														<div class="modal-header">
															<span class="close">×</span>
															<h2>Inscription</h2>
														</div>
														<div class="modal-body">
															<iframe class="iframe-sd-booking" src="" data-src="<?php echo $booking_page_url; ?>/embed?eventDateId=<?php echo $date_sd_id; ?>&attendanceType=ON_SITE&hideAttendanceTypeSelection=true" title="SeminarDesk Inscription"></iframe>
														</div>
														<!-- <div class="modal-footer">
															<h3>Modal Footer</h3>
														</div> -->
													</div>
												</div>
												<!-- END modal content -->
												<?php
											}elseif ( $date_sd_af['Onsite_Required_Registration'] === true && !empty( $booking_page_url ) && ( $status === 'fully_booked' || $close_onsite_reg === true ) ){
												// fully booked
												?>
												<div class="box">
													<h4>Participation sur place</h4>
														<div class="registration-full">
															<a class="button" href="#full">Complet</a>
														</div>
												</div>
												<?php
											}elseif( $status === 'canceled' ){
												?>
												<div class="box">
													<h4>Participation sur place</h4>
														<div class="registration-canceled">
															<a class="button" href="#canceled">Annulé</a>
														</div>
												</div>
												<?php
											}else{
												// without registration option
												if ( !empty( $date_sd_af['DE_Onsite_Not_Required_Registration_Text'] ) ){
													?>
													<div class="box">
														<h4>Participation sur place</h4>
														<a class="no-reg"><?php echo $date_sd_af['DE_Onsite_Not_Required_Registration_Text'] ?></a>
													</div>
													<?php
												}
											}
										}
									?>
									</div>
								</div>
								<?php
							}
							?>
						</div>
					<?php
				}else {
				// no current & upcoming event dates
				?>
				<div class="entry-header-inner section-inner small has-text-align-center">
					<h4>
						<?php
						echo '<h4 class="no-date">' . 'Désolé, il n’y a pour l’instant aucune dates à venir pour cet évènement.' . '</h4>';
						?>
					</h4>
					<br>
				</div>
				<?php
				}
			}
	}
	wp_reset_query();
	wp_reset_postdata();
	?>
	<!-- return to agenda page button -->
	<div class="agenda-page">
		<div class="button">
			<a href="<?php echo home_url(); ?>/pratiquer/agenda/">RETOUR À L'AGENDA</a>
			<a href="<?php echo home_url(); ?>/pratiquer/agenda/">
				<span>
					<!-- <i class="fas fa-plus"></i> -->
					<i class="fas fa-angle-right"></i>
				</span>
			</a>
		</div>
	</div>

</main><!-- #site-content -->
<?php

/**
 * footer here ...
 */

get_footer();