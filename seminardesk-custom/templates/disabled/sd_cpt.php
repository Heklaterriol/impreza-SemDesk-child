<?php
/**
 * The fallback template for custom post type defined by seminardesk
 * used when specific single post template for CPT doesn't exist.
 * 
 * @package SeminardeskPlugin
 */

get_header();
?>

<main id="site-content" role="main">
	<header class="archive-header has-text-align-center header-footer-group">
		<div class="archive-header-inner section-inner medium">
			<h1 class="archive-title">CPT Template - Fallback</h1>
		</div>
	</header>

	<div class="entry-header-inner section-inner small">
		<br><p>Placeholder for template</p>
	</div>
</main>

<?php
get_footer();