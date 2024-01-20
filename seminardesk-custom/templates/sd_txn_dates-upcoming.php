<?php
/**
 * Custom template for agenda page, taxonomy sd_txn_dates with upcoming event dates.
 * 
 * @package HelloIVY
 */

defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

/**
 * includes here...
 */

use Inc\Utils\TemplateUtils as Utils;

/**
 * Hooks here ...
 */

/**
 * parameters here...
 */

$term_set = '';
$http_response_sd_api = wp_safe_remote_get(
	'https://institutvajrayogini.seminardesk.com/api/tenant',
	array(
		'timeout'	=> 10,
		'headers'	=> array(
				'Content-Type'	=> 'application/json; charset=utf-8',
		)
	),
);

/**
 * site header here...
 */

/**
 * Add meta og:image to the header via yoast hook
 * @param mixed $object 
 * @return void 
 */
function ivy_wpseo_add_images( $object ) {
	global $http_response_sd_api;
	if( !is_wp_error( $http_response_sd_api ) ){
		$http_json = json_decode( wp_remote_retrieve_body($http_response_sd_api), true );
		if ( !empty( $http_json ) ){
			// $image = 'https://sot.datenablage.info/wp-content/uploads/2023/12/bg_home.jpg';
			$img_url = Utils::get_value_by_language( $http_json['info']['headerImageUrl'], IVY_STRINGS['lang'] );
			$object->add_image( $img_url );
		}
	}
}
add_action( 'wpseo_add_opengraph_images', 'ivy_wpseo_add_images' );

get_header();

/**
 * main code here...
 */

?>
<main id="site-content" role="main">
	<header class="archive-header header-footer-group">
		<div class="archive-header-inner section-inner medium">
				<!-- header title -->
				<div class="elementor-element elementor-element-f314c53 soulignage_partiel_bleu elementor-widget elementor-widget-heading" data-id="f314c53" data-element_type="widget" data-widget_type="heading.default">
					<div class="elementor-widget-container">
						<style>/*! elementor - v3.6.4 - 13-04-2022 */
							.elementor-heading-title{padding:0;margin:0;line-height:1}.elementor-widget-heading .elementor-heading-title[class*=elementor-size-]>a{color:inherit;font-size:inherit;line-height:inherit}.elementor-widget-heading .elementor-heading-title.elementor-size-small{font-size:15px}.elementor-widget-heading .elementor-heading-title.elementor-size-medium{font-size:19px}.elementor-widget-heading .elementor-heading-title.elementor-size-large{font-size:29px}.elementor-widget-heading .elementor-heading-title.elementor-size-xl{font-size:39px}.elementor-widget-heading .elementor-heading-title.elementor-size-xxl{font-size:59px}
						</style>
						<h2 class="elementor-heading-title elementor-size-default">Agenda</h2>
					</div>
				</div>
				<?php
				/** 
				 * archive header starts here...
				 */
				if( !is_wp_error( $http_response_sd_api ) ){
					$http_json = json_decode( wp_remote_retrieve_body($http_response_sd_api), true );
					if ( !empty( $http_json ) ){
						$header_img_url = Utils::get_value_by_language( $http_json['info']['headerImageUrl'], IVY_STRINGS['lang'] );
						Utils::get_img_remote( $header_img_url, '500', '', $alt = __('remote image', 'vajrayogini'), '<div class="header-image">', '</div>', true);
						$html_search = array(
							'<p><br /></p>',
							'<br />',
							'<p></p>'
						);
						echo ivy_strip_html_tags(
							ivy_strip_html_styles(
								Utils::get_value_by_language( $http_json['info']['headerText'], IVY_STRINGS['lang'], '<div class="header-text">', '</div>' ),
							),
							$html_search
						);
					}
				}
				?>
		</div><!-- .archive-header-inner -->
	</header><!-- .archive-header -->
	<div class="agenda-content">

		<?php
			get_sidebar( 'agenda' );
		?>
		<div class="page-content">
			<div id="content">
				<?php
				if ( !empty($_GET['level']) ) {
					$date_posts = ivy_get_dates_upcoming_by_level();
				}elseif ( !empty($_GET['category']) ) {
					$date_posts = ivy_get_dates_upcoming_by_category();
				}elseif ( !empty($_GET['date']) ) {
					$date_posts = ivy_get_dates_upcoming_by_month();
				}elseif ( !empty($_GET['search']) ) {
					$date_posts = ivy_get_dates_upcoming_by_search();
				}
				else {
					$date_posts = ivy_get_dates_upcoming_all();
				}
	
				// remove dates from array which shouldn't be listed in upcoming events
				if ( !empty( $date_posts ) ){
					$i = 0;
					$dates_filtered = $date_posts;
					foreach ( $date_posts as $date ){
						if ( $date->sd_data['bookingPageStatus'] === 'hidden_on_list' || $date->sd_data['bookingPageStatus'] === 'hidden' ) {
							// $is_remove = true;
							unset ( $dates_filtered[$i] );
						}
						$i++;
					}
					$date_posts = $dates_filtered;
					// echo count( $dates ) > 1 ? 'Dates disponibles :' : 'Date disponible :';
					$date_count = 0;
					$ongoing = true;
					$first = true;
					$has_month_container = false;
					// $wp_timestamp_this_month = strtotime( wp_date( 'Y-m' ) );
					$wp_timestamp_now = strtotime('now');
					$month_set = null;
					foreach ( $date_posts as $date_post ){
						$event_post = get_post( $date_post->wp_event_id );
						$event_sd_data = $event_post->sd_data;
						if( $event_sd_data['previewAvailable'] ){
							$date_sd_data = $date_post->sd_data;
							$date_sd_af = $date_sd_data['additionalFields'];
							$date_sd_attendance_type = $date_sd_data['attendanceType'];
							$event_wp_id = $event_post->ID;
							$event_sd_id = $event_post->sd_event_id;
							$event_sd_af = $event_sd_data['additionalFields'];
							$event_wp_permalink = get_permalink( $event_wp_id );
							$event_post_status = $event_post->post_status;
							$date_sd_begin = $date_post->sd_date_begin/1000;
							$date_sd_end = $date_post->sd_date_end/1000;
							if( $date_sd_begin >= $wp_timestamp_now && $month_set !== wp_date( 'm-Y' , $date_sd_begin ) ){
								$month_set = wp_date( 'm-Y' , $date_sd_begin );
								$has_month_container = true;
								if ( $first === true ){
									echo '<div class="sd-month-container ' . wp_date( 'm-Y', $date_sd_begin ) . '">';
									$first = false;
								}else{
									echo '</div>';
									echo '<div class="sd-month-container ' . wp_date( 'm-Y', $date_sd_begin ) . '">';
								}
								echo '<span class="month divider-separator"><h1 class="month-title">' . wp_date( 'F Y',$date_sd_begin ) . '</h1></span>';
							}elseif( $date_sd_begin < $wp_timestamp_now && $month_set !== '00-00' ){
								$month_set = '00-00';
								if ( $first === true ){
									echo '<div class="sd-month-container 00-00">';
									$first = false;
								}else{
									echo '</div>';
									echo '<div class="sd-month-container 00-00">';
								}
								echo '<span class="month 00-00 divider-separator"><h1 class="month-title">' . IVY_STRINGS['current'] . '</h1></span>';
							}
	
							?>
							<div class="date-entry <?php echo ivy_get_filter_tags( $date_sd_attendance_type );?>">
								<div class="date-container">
									<a class="contents" href="<?php echo $event_wp_permalink;?>">
										<div class="date-start">
											<div class="date-box">
												<?php
												echo '<p class="day">' . wp_date( 'd', $date_sd_begin ) . '</p><div class="month">' . wp_date( 'M', $date_sd_begin ) . '</div>';
												?>
											</div>
										</div>
										<div class="date-title">
											<?php
											echo '<h4>' . $date_post->post_title . '</h4>';
											$date_sd_subtitle = wp_strip_all_tags( $date_sd_af['DE_Date_Subtitle'], true );
											echo !empty( $date_sd_subtitle ) ? '<p class="date-subtitle">' . $date_sd_subtitle . '</p>' : null;
											?>
										</div>
										<div class="date-duration">
											<div class="box wrap
											<?php 
											echo $date_sd_data['bookingPageStatus'] === 'canceled' || $date_sd_data['bookingPageStatus'] === 'fully_booked' ? ' ivy-red' : null;
											echo $date_sd_data['bookingPageStatus'] === 'canceled' ? ' ivy-canceled' : null;
											echo  $date_sd_data['bookingPageStatus'] === 'fully_booked' ? ' ivy-full' : null;
											?>">
												<?php
												ivy_the_date_duration( $date_sd_begin, $date_sd_end );
												?>
											</div>
											<?php
											
											?>
										</div>
										<div class="date-access">
											<div class="attendance-type">
												<?php
												// onsite / online
												ivy_get_the_date_attendanceType( $date_sd_data );
												?>
											</div>
											<?php
											// date level - label
											ivy_get_the_date_levels( $date_post->ID );
											// date status - additional field
											$close_onsite_reg = $date_sd_af['Onsite_reg_fully booked_and_Online_reg_still_open']; // CF to close onsite registration, but keep online registration open
											switch ( $date_sd_data['bookingPageStatus'] )
											{
												case 'canceled':
													$status_html = '<div class="canceled">annulé</div>';
													break;
												case 'fully_booked':
													if( $date_sd_af['Online_Required_Registration'] === true || $date_sd_af['Onsite_Required_Registration'] === true ){
														$status_html =  '<div class="fully_booked">complet</div>';
													}
													break;
												default:
													if ( $close_onsite_reg === true ){
														if( $date_sd_af['Online_Required_Registration'] === true || $date_sd_af['Onsite_Required_Registration'] === true ){
															$status_html =  '<div class="fully_booked">complet</div>';
														}
														break;
													}
													$status_html = null;
													break;
											}
											echo !empty($status_html) ? '<div class="status">' . $status_html . '</div>' : null;
											?>
										</div>
										<div class="date-button">
											<?php
												if ( !empty( $event_wp_permalink ) ) {
													?>
													<div class="button">
														<span>
															<i class="fas fa-plus"></i>
																D'INFOS
														</span>
													</div>
													<?php
												}
												?>
										</div>
									</a>
								</div>
								<div class="divider-dates">
									<span class="divider-separator"></span>
								</div>
							</div>
							<?php
							$date_count++;
						}
					}
					echo $has_month_container === true ? '</div>' : null;
					?>
					<h4 class="no-result">
						<?php echo IVY_STRINGS['noresult']; ?>
					</h4>
					<?php
				}else {
					// no current & upcoming event dates
					?>
						<h4 class="no-result">
							<?php echo IVY_STRINGS['noresult']; ?>
						</h4>
					<?php
				}
				wp_reset_query();
				wp_reset_postdata();
			?>
			</div>
		</div>
	</div>
</main><!-- #site-content -->
<?php

/**
 * footer here ...
 */

get_footer();