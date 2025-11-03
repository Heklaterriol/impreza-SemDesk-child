<?php
/**
 * The template for taxonomy sd_txn_dates by term year or month.
 * 
 * @package SeminardeskPlugin
 */

use Inc\Utils\TemplateUtils as Utils;

get_header();
?>

<main id="site-content" role="main">

	<?php
	$txn = get_taxonomy(get_query_var( 'taxonomy' ));
	$title = ucfirst($txn->rewrite['slug']) . ': '. get_queried_object()->name;
	?>
	
	<header class="archive-header has-text-align-center header-footer-group">

		<div class="archive-header-inner section-inner medium">

			<?php if ( $title ) { ?>
				<h1 class="archive-title"><?php echo $title; ?></h1>
			<?php } ?>

		</div><!-- .archive-header-inner -->

	</header><!-- .archive-header -->

	<?php
	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			$post_event = get_post( $post->wp_event_id );
			$post_event_status = $post_event->post_status;
			?>
			<div class="sd-event">
				<div class="entry-header-inner section-inner small">
					<div class="sd-event-title">
						<?php 
						if ( $post_event_status === 'publish' ){
							?>
							<a href="<?php echo get_permalink($post_event); ?>">
							<?php 
							Utils::get_value_by_language( $post_event->sd_data['title'], 'DE', '<h3>', '</h3>', true); 
							?>
						</a>
						<?php
						} else {
							Utils::get_value_by_language( $post_event->sd_data['title'], 'DE', '<h3>', '</h3>', true);
						}
						?>
					</div>
					<div class="sd-event-container">
						<div class="sd-event-props">
							<?php
							Utils::get_date_span( $post->sd_date_begin, $post->sd_date_begin, null, null, '<div class="sd-event-date"><strong>Date: </strong>', '</div>', true);
							Utils::get_facilitators($post_event->sd_data['facilitators'], '<div class="sd-event-facilitators"><strong>Facilitator - Event level: </strong>', '</div>', true); // TODO: for backwards compatibility - perhaps remove at a later?
							Utils::get_facilitators($post->sd_data['facilitators'], '<div class="sd-event-facilitators"><strong>Facilitator - Date level: </strong>', '</div>', true);
							Utils::get_value_by_language($post->sd_data['priceInfo'], 'DE', '<div class="sd-event-price"><strong>Price: </strong>', '</div>', true );
							Utils::get_venue($post->sd_data['venue'], '<div class="sd-event-venue"><strong>Venue: </strong>', '</div>', true);
							?>
						</div>
						<div class=sd-event-image>
							<?php
							Utils::get_img_remote(  Utils::get_value_by_language($post_event->sd_data['teaserPictureUrl'] ?? null), '300', '', 'remote image failed', '', '', true);
							?>
						</div>
						<div class=sd-event-teaser>
							<?php 
							echo Utils::get_value_by_language($post_event->sd_data['teaser']);
							if ( $post_event_status === 'publish' ){
								?>
								<div class="sd-event-more-link">
									<a class="button" href="<?php echo get_permalink($post_event); ?>">More</a>
								</div> 
								<?php
							}
							?>
						</div>
					</div>
					
				</div>
			</div>
		<?php
		}?>
		<div class="has-text-align-center">
			<br><p><?php echo paginate_links();?></p>
		</div>
		<?php
	} else {
		?>
		<div class="entry-header-inner section-inner small has-text-align-center">
			<h5>Sorry, no events for this date.</h5>
			<br>
		</div>
		<?php
	}
	?>

</main><!-- #site-content -->

<?php
get_footer();