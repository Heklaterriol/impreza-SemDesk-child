<?php
/**
 * The template for single post of CPT sd_cpt_event
 *
 * @package SeminardeskPlugin
 */

use Inc\Utils\TemplateUtils as Utils;

get_header();
?>
<main id="site-content" role="main">
	<?php
	if (have_posts()) {
		while (have_posts()) {
			the_post();
			?>
			<header class="entry-header has-text-align-center">
				<div class="entry-header-inner section-inner medium">
					<?php
					Utils::get_value_by_language( $post->sd_data['title'], 'DE', '<h1 class="archive-title">', '</h1>', true);
					echo Utils::get_value_by_language($post->sd_data['subtitle']);
					$img_url = Utils::get_value_by_language($post->sd_data['headerPictureUrl']) ?? null;
					echo Utils::get_img_remote( $img_url, '300', '', 'remote image failed' );
					?>
				</div>
			</header>
			<div class="post-meta-wrapper post-meta-single post-meta-single-top">
				<p>
					<?php
					// TODO: for backwards compatibility - perhaps remove at a later?
					$facilitators = Utils::get_facilitators($post->sd_data['facilitators']);
					if ($facilitators) {
						?><strong>Facilitator - Event level: </strong><?php
						echo $facilitators;
					}
					?>
				</p>
				<p>
					<?php
					echo Utils::get_value_by_language($post->sd_data['description']);
					?>
				</p>
				<?php
					// get list of all dates for this event
					$status_lib = array(
						'available'		=> 'Booking Available',
						'fully_booked'	=> 'Fully Booked',
						'limited'		=> 'Limited Booking',
						'wait_list'		=> 'Waiting List',
						'canceled'		=> 'Canceled',
					);
					$booking_list = Utils::get_event_dates_list( $post->sd_event_id, $status_lib );
					$booking_url = esc_url( Utils::get_value_by_language( $post->sd_data['bookingPageUrl'] ?? null ) );
					if ( $booking_list ){
						?>
						<h4>List of available dates:</h4>
						<?php
						echo $booking_list;
						if ( !empty($booking_url) && $post->sd_data['registrationAvailable'] === true ) {
							?>
							<br><p><button class="sd-modal-booking-btn">Booking</button></p>
							<?php
						}
					} else {
						?>
						<h4>No dates for this event available :(</h4>
						<?php
					}
					?>
			</div>
			<!-- BEGIN modal content -->
			<div class="sd-modal">
				<div class="sd-modal-content">
					<span class="sd-modal-close-btn">&times;</span>
					<h4 class="sd-modal-title">Booking</h4>
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
