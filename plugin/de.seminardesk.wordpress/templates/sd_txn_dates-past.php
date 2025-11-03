<?php
/**
 * The template for taxonomy sd_txn_dates
 * 
 * @package SeminardeskPlugin
 */

use Inc\Utils\TemplateUtils as Utils;

get_header();
?>
<main id="site-content" role="main">
	<header class="archive-header has-text-align-center header-footer-group">
				<h1 class="archive-title">Past Event Dates</h1>
		</div><!-- .archive-header-inner -->
	</header><!-- .archive-header -->
	
	<?php
	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			$post_event = get_post( $post->wp_event_id );
			$post_event_status = $post_event->post_status;
			?>
			<div class="entry-header-inner section-inner small">
				<?php
				if ( $post_event_status === 'publish' ){
					?>
					<a href="<?php echo get_permalink($post_event); ?>">
					<?php 
					Utils::get_value_by_language( $post_event->sd_data['title'], 'DE', '<h4>', '</h4>', true); 
					?>
					</a>
					<?php
				} else {
					Utils::get_value_by_language( $post_event->sd_data['title'], 'DE', '<h4>', '</h4>', true);
				}
				Utils::get_date_span( $post->sd_data['beginDate'], $post->sd_data['endDate'], null, null, '<p><strong>Date: </strong>', '</p>', true);
				Utils::get_facilitators( $post_event->sd_data['facilitators'], '<p><strong>Facilitator - Event level: </strong>', '</p>', true ); // TODO: for backwards compatibility - perhaps remove at a later?
				Utils::get_facilitators( $post->sd_data['facilitators'], '<p><strong>Facilitator - Date level: </strong>', '</p>', true );
				echo Utils::get_value_by_language( $post->sd_data['priceInfo'], 'DE', '<p><strong>Price: </strong>', '</p>' );
				Utils::get_venue( $post->sd_data['venue'], '<p><strong>Venue: </strong>', '</p>', true);
				Utils::get_img_remote( Utils::get_value_by_language( $post_event->sd_data['teaserPictureUrl'] ?? null ), '300', '', $alt = "remote image load failed", '<p>', '</p>', true );
				Utils::get_value_by_language( $post_event->sd_data['teaser'], 'DE',  '<p>', '</p>', true );
				if ( $post_event_status === 'publish' ){
					?>
					<a href="<?php echo get_permalink($post->wp_event_id); ?>">
						More ...
					</a>
					<?php
				}
				?>
			</div>
			<?php
		}?>
		<div class="has-text-align-center">
			<br><p>
				<?php
				echo paginate_links( array(
					'base' => add_query_arg('page', '%#%'),
					'format' => '?page=%#%',
				) );
				?>
			</p>
		</div>
		<?php
	} else {
		?>
		<div class="entry-header-inner section-inner small has-text-align-center">
			<h5>
				<strong>Sorry, no past event dates available.</strong>
			</h5>
			<br>
		</div>
		<?php

	} 
	wp_reset_query();
	?>

</main><!-- #site-content -->

<?php
get_footer();

