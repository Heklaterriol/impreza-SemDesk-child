<?php
/**
 * Search form.
 * 
 * @package HelloIVY
 */

defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );
?>

<form class="elementor-search-form ivy-agenda-search-form" role="search" action="#content" method="get">
	<div class="elementor-search-form__container">
		<input id="ivy-search-input" value="<?php echo !empty( $_GET['search'] ) ? $_GET['search'] : null;?>" placeholder="Suchen ..." class="elementor-search-form__input" type="search" name="search" title="Rechercher" data-com.bitwarden.browser.user-edited="yes">
		<input type="hidden" name="lang" value="de">
		<!-- <button class="elementor-search-form__submit" type="submit" title="Suchen " aria-label="Suchen " onclick="onclickSearch(this)">
			<i aria-hidden="true" class="fas fa-search"></i>
			<span class="elementor-screen-only">Suchen </span> -->
		</button>
	</div>
</form>