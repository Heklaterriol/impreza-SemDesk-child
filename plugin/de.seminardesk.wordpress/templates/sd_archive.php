<?php
/**
 * The template for cpt archives
 * 
 * @package SeminardeskPlugin
 */

use Inc\Utils\TemplateUtils as Utils;

get_header();

$archive_title = get_the_archive_title();
$archive_subtitle = get_the_archive_description();
?>

<main id="site-content" role="main">
	<header class="archive-header has-text-align-center header-footer-group">
		<div class="archive-header-inner section-inner medium">
			<h1 class="archive-title"><?php echo $archive_title ?></h1>
		</div>
	</header>

	<?php
	if (have_posts()) {
		while (have_posts()) {
			the_post();
			$post_type = get_post_type();
			$post_parent = wp_get_post_parent_id( $post );
			?>
			<div class="entry-header-inner section-inner small">
				<?php
				if ( get_post_status() === 'publish' ){
					?>
						<a href="<?php echo get_permalink(); ?>">
							<?php
							if( get_post_type() !== 'sd_cpt_facilitator'){
								echo Utils::get_value_by_language( $post->sd_data['title'] ?? null, 'DE', '<p><h4>', '</h4></p>', false);
							}else{
								echo '<p><h4>' . $post->sd_data['name'] . '</h4></p>';
							}
							?>
						</a> 
					<?php
				} else {
					Utils::get_value_by_language( $post->sd_data['title'], 'DE', '<p><h4>', '</h4></p>', true);
				} 
				?>
			</div>
			<?php
		}?>
		<div class="has-text-align-center">
			<br><p><?php echo get_posts_nav_link();?></p>
		</div>
		<?php
	} else {
		?>
		<div class="entry-header-inner section-inner small has-text-align-center">
			<h5>Sorry, no posts available in this archive.</h5>
			<br>
		</div>
		<?php
	}
?>

</main><!-- #site-content -->

<?php
get_footer();