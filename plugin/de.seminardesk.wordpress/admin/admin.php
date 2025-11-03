<?php
/**
 * defines admin page of the seminardesk plugin  
 * 
 * @package SeminardeskPlugin
 */

 ?>

<div class="wrap">
	<h1>SeminarDesk Plugin</h1>
	<?php settings_errors(); ?>

	<ul class="nav nav-tabs">
		<li class="active"><a href="#tab-1">Settings</a></li>
		<li><a href="#tab-2">Update</a></li>
		<li><a href="#tab-3">About</a></li>
	</ul>

	<div class="tab-content">

		<div id="tab-1" class="tab-pane active">
			<form method="post" action="options.php">
				<?php
				settings_fields(SD_ADMIN['group_settings']);
				do_settings_sections(SD_ADMIN['page']);
				submit_button(__('Save Settings', 'seminardesk'));
				?>
			</form>
		</div>

		<div id="tab-2" class="tab-pane">
			<h3>Manual Update</h3>
			<p class="regular-text">Automatic Update of this Plugin from the WordPress Dashboard is not available yet. Plugin updates are provided by SeminarDesk in a .zip format and need to be manually installed via SFTP or FTP.</p>
		</div>

		<div id="tab-3" class="tab-pane">
			<h3>Contacts</h3>
			<div  class='regular-text'>
				<p>
						<span style="font-size: 1.1em; font-weight:600;">SeminarDesk</span> - Danker, Smaluhn & Tammer GbR
						<br /> 
						Borsteler Weg 26 D
						<br />
						31595 Steyerberg
				</p>
				<p>
					<b>Gesellschafter</b>: Christoph Danker (geb. Mühlich), Simon Smaluhn und Jan Tammen
				</p>
				<p>
					<b>Telefon</b>: <a href="tel:+495764942806">+49 5764 942806</a><br />
					<b>Web</b>: <a href="https://www.seminardesk.de/" target="_blank">https://www.seminardesk.de</a><br />
					<b>E-Mail</b>: info [at] seminardesk.de
				</p>
			</div>
		</div>

	</div>
</div>