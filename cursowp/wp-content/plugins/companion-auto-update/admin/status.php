<?php

	// Update the database
	if( isset( $_GET['run'] ) && $_GET['run'] == 'db_update' ) {
		cau_manual_update();
		echo '<div id="message" class="updated"><p><b>'.__( 'Database update completed' ).'</b></p></div>';
	}
	
	if( isset( $_GET['run'] ) && $_GET['run'] == 'db_info_update' ) {
		cau_savePluginInformation();
		echo '<div id="message" class="updated"><p><b>'.__( 'Database information update completed' ).'</b></p></div>';
	}

	if( isset( $_GET['ignore_report'] ) ) {

		$report_to_ignore 	= sanitize_text_field( $_GET['ignore_report'] );
		$allowedValues 		= array( 'seo', 'cron' );

		if( !in_array( $report_to_ignore, $allowedValues ) ) {
			wp_die( 'Trying to cheat eh?' );
		} else {
			global $wpdb;
			$table_name = $wpdb->prefix . "auto_updates"; 
			$wpdb->query( $wpdb->prepare( "UPDATE {$table_name} SET onoroff = %s WHERE name = 'ignore_$report_to_ignore'", 'yes' ) );
			$__ignored = __( 'This report will now be ignored', 'companion-auto-update' );
			echo "<div id='message' class='updated'><p><b>$__ignored</b></p></div>";
		}

	}


	
	// Variables
	$dateFormat 	= get_option( 'date_format' );
	$dateFormat 	.= ' '.get_option( 'time_format' );
	global $wpdb;
	$table_name 	= $wpdb->prefix . "auto_updates"; 
	$schedules 		= wp_get_schedules();
	$interval_names = cau_wp_get_schedules();

?>

<div class="cau_status_page">

	<table class="cau_status_list widefat striped">

		<thead>
			<tr>
				<th class="cau_status_name"><strong><?php _e( 'Auto Updater', 'companion-auto-update' ); ?></strong></th>
				<th class="cau_status_active_state"><strong><?php _e( 'Status', 'companion-auto-update' ); ?></strong></th>
				<th class="cau_status_interval"><strong><?php _e( 'Interval', 'companion-auto-update' ); ?></strong></th>
				<th class="cau_status_next"><strong><?php _e( 'Next', 'companion-auto-update' ); ?></strong></th>
			</tr>
		</thead>

		<tbody id="the-list">
			<?php 

			$auto_updaters = array(
				'plugins' 	=> __( 'Plugins', 'companion-auto-update' ),
				'themes' 	=> __( 'Themes', 'companion-auto-update' ),
				'minor' 	=> __( 'Core (Minor)', 'companion-auto-update' ),
				'major' 	=> __( 'Core (Major)', 'companion-auto-update' )
			);

			$eventNames = array(
				'plugins' 	=> 'wp_update_plugins',
				'themes' 	=> 'wp_update_themes',
				'minor' 	=> 'wp_version_check',
				'major' 	=> 'wp_version_check'
			);

			foreach ( $auto_updaters as $key => $value ) {

				if( cau_get_db_value( $key ) == 'on' ) {
					$__status  		= 'enabled';
					$__icon  		= 'yes-alt';
					$__text 		= __( 'Enabled', 'companion-auto-update' );
					$__interval 	= $interval_names[wp_get_schedule( $eventNames[$key] )];
					$__next 		= date_i18n( $dateFormat, wp_next_scheduled( $eventNames[$key] ) );
				} else {
					$__status  		= 'disabled';
					$__icon  		= 'marker';
					$__text 		= __( 'Disabled', 'companion-auto-update' );
					$__interval 	= '&dash;';
					$__next 		= '&dash;';
				}

				$__nxt 	= __( 'Next', 'companion-auto-update' );

				echo "<tr>
					<td class='cau_status_name'>$value</td>
					<td class='cau_status_active_state'><span class='cau_$__status'><span class='dashicons dashicons-$__icon'></span> $__text</span></td>
					<td class='cau_status_interval'>$__interval</td>
					<td class='cau_status_next'><span class='cau_mobile_prefix'>$__nxt: </span>$__next</td>
				</tr>";
			} 

			?>
		</tbody>

	</table>

	<table class="cau_status_list widefat striped">

		<thead>
			<tr>
				<th class="cau_status_name"><strong><?php _e( 'Email Notifications', 'companion-auto-update' ); ?></strong></th>
				<th class="cau_status_active_state"><strong><?php _e( 'Status', 'companion-auto-update' ); ?></strong></th>
				<th class="cau_status_interval"><strong><?php _e( 'Interval', 'companion-auto-update' ); ?></strong></th>
				<th class="cau_status_next"><strong><?php _e( 'Next', 'companion-auto-update' ); ?></strong></th>
			</tr>
		</thead>

		<tbody id="the-list">
		<?php

		$other_events = array(
			'send' 			=> __( 'Update available', 'companion-auto-update' ),
			'sendupdate' 	=> __( 'Successful update', 'companion-auto-update' ),
			'wpemails' 		=> __( 'Core notifications', 'companion-auto-update' )
		);

		$other_eventNames = array(
			'send' 			=> 'cau_set_schedule_mail',
			'sendupdate' 	=> 'cau_set_schedule_mail',
			'wpemails' 		=> 'cau_set_schedule_mail',
		);

		foreach ( $other_events as $key => $value ) {

			if( cau_get_db_value( $key ) == 'on' ) {
				$__status  		= 'enabled';
				$__icon  		= 'yes-alt';
				$__text 		= __( 'Enabled', 'companion-auto-update' );
				$__interval 	= $interval_names[wp_get_schedule( $other_eventNames[$key] )];
				$__next 		= date_i18n( $dateFormat, wp_next_scheduled( $other_eventNames[$key] ) );
			} else {
				$__status  		= 'warning';
				$__icon  		= 'marker';
				$__text 		= __( 'Disabled', 'companion-auto-update' );
				$__interval 	= '&dash;';
				$__next 		= '&dash;';
			}

			$__nxt 	= __( 'Next', 'companion-auto-update' );

			echo "<tr>
				<td class='cau_status_name'>$value</td>
				<td class='cau_status_active_state'><span class='cau_$__status'><span class='dashicons dashicons-$__icon'></span> $__text</span></td>
				<td class='cau_status_interval'>$__interval</td>
				<td class='cau_status_next'><span class='cau_mobile_prefix'>$__nxt: </span>$__next</td>
			</tr>";
		} 

		?>
		</tbody>

	</table>

	<table class="cau_status_list widefat striped cau_status_warnings">

		<thead>
			<tr>
				<th class="cau_plugin_issue_name" colspan="4"><strong><?php _e( 'Status' ); ?></strong></th>
			</tr>
		</thead>

		<tbody id="the-list">

			<tr>
				<td><?php _e( 'Auto updates', 'companion-auto-update' ); ?></td>
				<?php if ( checkAutomaticUpdaterDisabled() ) { ?>
					<td class="cau_status_active_state"><span class='cau_disabled'><span class="dashicons dashicons-no"></span> <?php _e( 'All automatic updates are disabled', 'companion-auto-update' ); ?></span></td>
					<td>
						<form method="POST">
							<?php wp_nonce_field( 'cau_fixit' ); ?>
							<button type="submit" name="fixit" class="button button-primary"><?php _e( 'Fix it', 'companion-auto-update' ); ?></button>
							<a href="https://codeermeneer.nl/documentation/known-issues-fixes/#updates_disabled" target="_blank" class="button"><?php _e( 'How to fix this', 'companion-auto-update' ); ?></a>
						</form>
					</td>
				<?php } else { ?>
					<td class="cau_status_active_state"><span class='cau_enabled'><span class="dashicons dashicons-yes-alt"></span> <?php _e( 'No issues detected', 'companion-auto-update' ); ?></span></td>
					<td></td>
				<?php } ?>
				<td></td>
			</tr>
			
			<tr>
				<td><?php _e( 'Connection with WordPress.org', 'companion-auto-update' ); ?></td>
				<?php if( wp_http_supports( array( 'ssl' ) ) == '1' ) {
					$__text		= __( 'No issues detected', 'companion-auto-update' );
					echo "<td colspan='3' class='cau_status_active_state'><span class='cau_enabled'><span class='dashicons dashicons-yes-alt'></span> $__text</span></td>";
				} else {
					$__text		= __( 'Disabled', 'companion-auto-update' );
					echo "<td colspan='3' class='cau_status_active_state'><span class='cau_disabled'><span class='dashicons dashicons-no'></span> $__text</span></td>";
				} ?>
			</tr>
			
			<tr <?php if( cau_get_db_value( 'ignore_seo' ) == 'yes' ) { echo "class='report_hidden'"; } ?> >
				<td><?php _e( 'Search Engine Visibility', 'companion-auto-update' ); ?></td>
				<?php if( get_option( 'blog_public' ) == 0 ) { ?>
					<td colspan="2" class="cau_status_active_state">
						<span class='cau_warning'><span class="dashicons dashicons-warning"></span></span>
						<?php _e( 'You’ve chosen to discourage Search Engines from indexing your site. Auto-updating works best on sites with more traffic, consider enabling indexing for your site.', 'companion-auto-update' ); ?>
					</td>
					<td>
						<a href="<?php echo admin_url( 'options-reading.php' ); ?>" class="button"><?php _e( 'Fix it', 'companion-auto-update' ); ?></a>
						<a href="<?php echo cau_url( 'status' ); ?>&ignore_report=seo" class="button button-alt"><?php _e( 'Ignore this report', 'companion-auto-update' ); ?></a>
					</td>
				<?php } else { ?>
					<td colspan="3" class="cau_status_active_state"><span class='cau_enabled'><span class="dashicons dashicons-yes-alt"></span> <?php _e( 'No issues detected', 'companion-auto-update' ); ?></span></td>
				<?php } ?>
			</tr>
			
			<tr <?php if( cau_get_db_value( 'ignore_cron' ) == 'yes' ) { echo "class='report_hidden'"; } ?> >
				<td><?php _e( 'Cronjobs', 'companion-auto-update' ); ?></td>
				<?php if( checkCronjobsDisabled() ) { ?>
					<td class="cau_status_active_state"><span class='cau_warning'><span class="dashicons dashicons-warning"></span> <?php _e( 'Disabled', 'companion-auto-update' ); ?></span></td>
					<td><code>DISABLE_WP_CRON true</code></td>
					<td>
						<a href="https://codeermeneer.nl/contact/" class="button"><?php _e( 'Contact for support', 'companion-auto-update' ); ?></a>
						<a href="<?php echo cau_url( 'status' ); ?>&ignore_report=cron" class="button button-alt"><?php _e( 'Ignore this report', 'companion-auto-update' ); ?></a>
					</td>
				<?php } else { ?>
					<td colspan="3" class="cau_status_active_state"><span class='cau_enabled'><span class="dashicons dashicons-yes-alt"></span> <?php _e( 'No issues detected', 'companion-auto-update' ); ?></span></td>
				<?php } ?>
			</tr>

			<tr>
				<td>wp_version_check</td>
				<?php if ( !has_filter( 'wp_version_check', 'wp_version_check' ) ) { ?>
					<td colspan="2" class="cau_status_active_state"><span class='cau_disabled'><span class="dashicons dashicons-no"></span> <?php _e( 'A plugin has prevented updates by disabling wp_version_check', 'companion-auto-update' ); ?></span></td>
					<td><a href="https://codeermeneer.nl/contact/" class="button"><?php _e( 'Contact for support', 'companion-auto-update' ); ?></a></td>
				<?php } else { ?>
					<td colspan="3" class="cau_status_active_state"><span class='cau_enabled'><span class="dashicons dashicons-yes-alt"></span> <?php _e( 'No issues detected' , 'companion-auto-update' ); ?></span></td>
				<?php } ?>
			</tr>

			<tr>
				<td>VCS</td>
				<td colspan="3" class="cau_status_active_state"><span class='cau_<?php echo cau_test_is_vcs_checkout( ABSPATH )['status']; ?>'><span class="dashicons dashicons-<?php echo cau_test_is_vcs_checkout( ABSPATH )['icon']; ?>"></span> <?php echo cau_test_is_vcs_checkout( ABSPATH )['description']; ?></span></td>
			</tr>

		</tbody>

	</table>

	<table class="autoupdate cau_status_list widefat striped cau_status_warnings">

		<thead>
			<tr>
				<th colspan="4"><strong><?php _e( 'Systeminfo', 'companion-auto-update' ); ?></strong></th>
			</tr>
		</thead>

		<tbody id="the-list">
			<tr>
				<td>WordPress</td>
				<td><?php echo get_bloginfo( 'version' ); ?></td>
				<td></td>
				<td></td>
			</tr>
			<tr>
				<td>PHP</td>
				<td><?php echo phpversion(); ?></td>
				<td></td>
				<td></td>
			</tr>
			<tr <?php if( cau_incorrectDatabaseVersion() ) { echo "class='inactive'"; } ?>>
				<td>Database</td>
				<td><?php echo get_option( "cau_db_version" ); ?> <code>(Latest: <?php echo cau_db_version(); ?>)</code></td>
				<td></td>
				<td></td>
			</tr>
			<tr>
				<td class="cau_status_name"><?php _e( 'Timezone' ); ?></td>
				<td class="cau_status_active_state"><?php echo cau_get_proper_timezone(); ?> (GMT <?php echo get_option('gmt_offset'); ?>) - <?php echo date_default_timezone_get(); ?></td>
				<td></td>
				<td></td>
			</tr>
		</tbody>

	</table>
	
	<?php 
	// If has incomptable plugins
	if( cau_incompatiblePlugins() ) { ?>

		<table class="cau_status_list no_column_width widefat striped cau_status_warnings">

			<thead>
				<tr>
					<th class="cau_plugin_issue_name" colspan="4"><strong><?php _e( 'Possible plugin issues', 'companion-auto-update' ); ?></strong></th>
				</tr>
			</thead>

			<tbody id="the-list">
				<?php
				foreach ( cau_incompatiblePluginlist() as $key => $value ) {
					if( is_plugin_active( $key ) ) {
						echo '<tr>
							<td class="cau_plugin_issue_name"><strong>'.$key.'</strong></td>
							<td colspan="2" class="cau_plugin_issue_explain">'.$value.'</td>
							<td class="cau_plugin_issue_fixit"><a href="https://codeermeneer.nl/documentation/known-issues-fixes/#plugins" target="_blank" class="button">'.__( 'How to fix this', 'companion-auto-update' ).'</a></td>
						</tr>';
					}
				}
				?>
			</tbody>

		</table>

	<?php } ?>

	<table class="autoupdate cau_status_list widefat striped cau_status_warnings">

		<thead>
			<tr>
				<th><strong><?php _e( 'Advanced info', 'companion-auto-update' ); ?></strong> &dash; <?php _e( 'For when you need our help fixing an issue.', 'companion-auto-update' ); ?></th>
			</tr>
		</thead>
		<tbody id="the-list">
			<tr>
				<td>
					<div class='button button-primary toggle_advanced_button'><?php _e( 'Toggle', 'companion-auto-update' ); ?></div>
				
					<div class='toggle_advanced_content' style='display: none;'>
						<?php 
						global $wpdb;
						$autoupdates 	= $wpdb->prefix."auto_updates"; 
						$cau_configs 	= $wpdb->get_results( "SELECT * FROM $autoupdates" ); 
						array_push( $cau_configs, "WordPress: ".get_bloginfo( 'version' ) );
						array_push( $cau_configs, "PHP: ".phpversion() );
						array_push( $cau_configs, "DB: ".get_option( "cau_db_version" ).' / '.cau_db_version() );

						echo "<textarea style='width: 100%; height: 750px;'>";
						print_r( $cau_configs );
						echo "</textarea>";
						?>
					</div>
				</td>
			</tr>
		</tbody>
	</table>

	<script>jQuery( '.toggle_advanced_button' ).click( function() { jQuery( '.toggle_advanced_content' ).toggle(); });</script>

</div>

<?php 

// Remove the line
if( isset( $_POST['fixit'] ) ) {
	check_admin_referer( 'cau_fixit' );
	cau_removeErrorLine();
}

// Get wp-config location
function cau_configFile() {

	// Config file
	if ( file_exists( ABSPATH . 'wp-config.php') ) {
		$conFile = ABSPATH . 'wp-config.php';
	} else {
		$conFile = dirname(ABSPATH) . '/wp-config.php';
	}

	return $conFile;

}

// Change the AUTOMATIC_UPDATER_DISABLED line
function cau_removeErrorLine() {

	// Config file
	$conFile = cau_configFile();

	// Lines to check and replace
	$revLine 		= "define('AUTOMATIC_UPDATER_DISABLED', false);"; // We could just remove the line, but replacing it will be safer
	$posibleLines 	= array( "define( 'AUTOMATIC_UPDATER_DISABLED', true );", "define( 'AUTOMATIC_UPDATER_DISABLED', minor );" ); // The two base options
	foreach ( $posibleLines as $value ) array_push( $posibleLines, strtolower( $value ) ); // Support lowercase variants
	foreach ( $posibleLines as $value ) array_push( $posibleLines, str_replace( ' ', '', $value ) ); // For variants without spaces

	$melding 	= __( "We couldn't fix the error for you. Please contact us for further support", 'companion-auto-update' ).'.';
	$meldingS 	= 'error';

	// Check for each string if it exists
	foreach ( $posibleLines as $key => $string ) {

		if( strpos( file_get_contents( $conFile ), $string ) !== false) {
	        $contents = file_get_contents( $conFile );
			$contents = str_replace( $string, $revLine, $contents );
			file_put_contents( $conFile, $contents );
			$melding 	= __( "We've fixed the error for you", 'companion-auto-update' ).' :)';
			$meldingS 	= 'updated';
	    }

	}
	
	echo "<div id='message' class='$meldingS'><p><strong>$melding</strong></p></div>";

}

?>