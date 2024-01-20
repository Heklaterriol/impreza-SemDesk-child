<?php
/**
 * Custom sidebar for agenda page, taxonomy sd_txn_dates with upcoming event dates.
 * 
 * @package HelloIVY
 */

defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

/**
 * includes here...
 */

/**
 * parameters here...
 */
$wp_timestamp_this_month = strtotime(wp_date('Y-m-d'));

?>

<div class="sidebar-content" id="sidebar-agenda">
	<!-- <h2>Agenda Sidebar</h2> -->
	<?php 
	// load search form
	// get_search_form(); //default search form
	require( get_stylesheet_directory() . '/sidebar-agenda-searchform.php' ); // custom search form
	/**
	 * Filters
	 */
	$filter_terms = array(
		'level'			=>	get_term_by( 'name', 'lg_id_5', 'sd_txn_labels'),
		'category'	=>	get_term_by( 'name', 'lg_id_13', 'sd_txn_labels'),
		'access'		=>	get_term_by( 'name', 'lg_id_12', 'sd_txn_labels'),
	);
	?>
	<h3 class="sidebar-headline">Filter</h3>
	<!-- Filter - Date -->
	<div class="sd-filters">
		<div class="lg-details">
			<form action="">
				<label for="dates">Daten</label>
				<select name="date" id="select-sd-date" onchange="onchangeSelect(this)">
					<?php
					$date_posts = ivy_get_dates_upcoming_all();
					if ( !empty( $date_posts ) ){
						$wp_timestamp_now = strtotime('now');
						$date_sd_begin_first = $date_posts[0]->sd_date_begin/1000;
						if ( $date_date_first < $wp_timestamp_now ){
							$date_months = array( IVY_STRINGS['current'] );
						}else{
							$date_months = array();
						}
						foreach ( $date_posts as $date_post ){
							$event_post = get_post( $date_post->wp_event_id );
							$sd_event_data = $event_post->sd_data;
							if( $sd_event_data['previewAvailable'] ){
								$date_sd_begin = $date_post->sd_date_begin/1000;
								if( wp_date( 'F Y', $date_sd_begin ) != end( $date_months ) && $date_sd_begin > $wp_timestamp_now ){
									array_push( $date_months, wp_date( 'F Y', $date_sd_begin ) );
								}
							}
						}
					}
					echo '<option value="all">Alle Termine</option>';
					foreach( $date_months as $date_month){
						echo '<option value="' . $date_month . '">' . ucfirst( $date_month ) . '</option>';
					}
					?>
				</select>
			</form>
		</div>
		<!-- Filter - Level -->
		<div class="lg-details">
			<form action="">
				<label for="levels">Level</label>
				<select name="level" id="select-sd-level" onchange="onchangeSelect(this)">
					<?php
					$labels_level_id = get_term_children( $filter_terms['level']->term_id, 'sd_txn_labels' );
					echo '<option for="all" value="all">alle Levels</option>';
					foreach ( $labels_level_id as $label_level_id ){
						$term_level = get_term( $label_level_id );
						echo '<option value="' . $term_level->slug . '">' . ucfirst( $term_level->description ) . '</option>';
					}
					?>
				</select>
			</form>
		</div>
		<!-- Filter - Category -->
		<div class="lg-details">
			<form action="">
				<label for="categories">Kategorie</label>
				<select name="category" id="select-sd-category" onchange="onchangeSelect(this)">
					<?php
					$labels_category_id = get_term_children( $filter_terms['category']->term_id, 'sd_txn_labels' );
					echo '<option value="all">alle Kategorien</option>';
					foreach ( $labels_category_id as $label_category_id ){
						$term_category = get_term( $label_category_id);
						echo '<option value="' . $term_category->slug . '">' . ucfirst( $term_category->description ) . '</option>';
					}
					?>
				</select>
			</form>
		</div>
	</div>
	<!-- Filter - Online -->
	<div class="filter-attendance-type">
		<div class="cbox-online">
			<label>Online</label>
			<input type="checkbox" id="cbox-sd-online" name="cbox-sd-online" value="yes" onclick="onclickCbox()" checked>
		</div>
		<!-- Filter - Onsite -->
		<div class="cbox-onsite">
			<label class=container >live</label>
			<input type="checkbox" id="cbox-sd-onsite" name="onsite" value="yes" onclick="onclickCbox()" checked>
		</div>
	</div>
	<?php
	// reset button for filters
		global $wp;
		?>
		<div class="filter-reset">
			<button class="filter-button" onclick="window.location.href='<?php echo home_url( $wp->request ) . '#content'; ?>';">Réinitialiser les filtres</button>
		</div>
		<?php
	?>
</div>