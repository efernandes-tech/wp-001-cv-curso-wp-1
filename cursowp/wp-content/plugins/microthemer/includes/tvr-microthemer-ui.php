<?php

// Stop direct call
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('Please do not call this page directly.');
}

/*global $media_queries_list_above, $media_queries_list;
$this->show_me = '<pre>$media_queries_list: '.print_r($this->oxygen_mqs, true). '</pre>' .
                 '<pre>$media_queries_list_above: '.print_r($media_queries_list_above, true). '</pre>';*/

$debug_unlock = false;
if ($debug_unlock){
	$this->show_me.= '<pre>'.print_r($this->preferences['subscription'], true). '</pre>';
	$this->show_me.= '<pre>'.print_r($this->preferences['subscription_checks'], true). '</pre>';
	$this->show_me.= 'buyer_email: ' . $this->preferences['buyer_email'] . '<br />';
	$this->show_me.= 'retro_sub_check_done: ' . $this->preferences['retro_sub_check_done'] . '<br />';
}

// is edge mode active?
if ($this->edge_mode['available'] and !empty($this->preferences['edge_mode'])){
	$this->edge_mode['active'] = true;
}

// dev tool - refresh option-icon css after updating icon-size-x in property options file
$dev_tasks = false;
if ($dev_tasks and TVR_DEV_MODE){

    // update the option icons - after adding new properties
	include $this->thisplugindir . 'includes/regenerate-option-icons.inc.php';

	// update the animation array - if new version of animate.css
	include $this->thisplugindir . 'includes/regenerate-animations.inc.php';
}

// set interface classes
$ui_class = '';
$this->preferences['admin_bar_preview'] ? $ui_class.= 'show-admin-bar' : $ui_class.= 'do-not-show-admin-bar';
//$this->preferences['auto_capitalize'] ? $ui_class.= ' tvr-caps' : false;
$this->preferences['dark_editor'] ? $ui_class.= ' dark-editor' : false;

$this->preferences['buyer_validated'] ? $ui_class.= ' plugin-unlocked' : $ui_class.= ' free-trial-mode';



$this->preferences['show_interface'] ? $ui_class.= ' show_interface' : false;
($this->preferences['css_important'] != 1) ? $ui_class.= ' manual-css-important' : false;
$this->preferences['show_code_editor'] ? $ui_class.= ' show_code_editor' : false;
$this->preferences['show_text_labels'] ? $ui_class.= ' show_text_labels' : false;
$this->preferences['show_rulers'] ? $ui_class.= ' show_rulers' : false;
$this->preferences['draft_mode'] ? $ui_class.= ' draft_mode' : false;
$this->preferences['dock_wizard_right'] ? $ui_class.= ' dock_wizard_right' : false;
$this->preferences['hover_inspect'] ? $ui_class.= ' hover_inspect' : false;
$this->preferences['selname_code_synced'] ? $ui_class.= ' selname_code_synced' : false;
$this->preferences['wizard_expanded'] ? $ui_class.= ' wizard_expanded' : false;
$this->preferences['code_manual_resize'] ? $ui_class.= ' code_manual_resize' : false;
$this->preferences['show_extra_actions'] ? $ui_class.= ' show_extra_actions' : false;
!$this->preferences['allow_scss'] ? $ui_class.= ' instant_editing_enabled' : false;
$this->preferences['dock_editor_left'] ? $ui_class.= ' dock_editor_left' : false;
$this->preferences['dock_options_left'] ? $ui_class.= ' dock_options_left' : false;
$this->preferences['detach_preview'] ? $ui_class.= ' detach_preview' : false;
!empty($this->preferences['server_scss']) ? $ui_class.= ' server_scss' : false;
!empty($this->preferences['show_sampled_values']) ? $ui_class.= ' show_sampled_values' : false;
!empty($this->preferences['show_sampled_variables']) ? $ui_class.= ' show_sampled_variables' : false;
!empty($this->preferences['tape_measure_slider']) ? $ui_class.= ' tape_measure_slider' : false;


// signal if 3rd party plugins are active
$no_integrations_available = true;
foreach ($this->integrations as $intKey => $val){
	if (!empty($val)){
		$ui_class.= ' ' . $intKey.'_active';
		$no_integrations_available = false;
	}
}

// signal that some integrations are available for e.g. launch builder checkbox
if ($no_integrations_available){
	$ui_class.= ' no_integrations_available';
} else {
	$ui_class.= ' integrations_available';
}

// page specific class is added if at least one option is on
foreach ($this->css_filters as $key => $arr){
	foreach ($arr['items'] as $i => $val){
		if (!empty($this->preferences[$key][$i])){
			$ui_class.= ' '.$key;
			break;
		}
	}
}

// edge mode interface classes
if ($this->edge_mode['active']){
	if (is_array($this->edge_mode['config'])){
		foreach ($this->edge_mode['config'] as $key => $value){
			$ui_class.= ' '.$key.'-'.$value;
		}
	}
}

?>

<div id="tvr" class='wrap tvr-wrap <?php echo $ui_class; ?>'>
<!-- <div id='tvr-ui'>-->

		<?php
		// make css file in use available to JS
		$css_min = $this->preferences['minify_css'] ? 'min.': '';
		$css_stub = $this->preferences['draft_mode'] ? 'draft' : 'active';
		?>

		<?php
		// root ui toggle for showing/hiding extra action icons in folders and selectors menu
		echo $this->extra_actions_icon('show_extra_actions');

		// root toggle for showing variables
        $sampledTypes = array('variables', 'values');
        foreach ($sampledTypes as $sType){
            $sKey = 'show_sampled_'.$sType;
	        echo $this->ui_toggle(
		        $sKey,
		        esc_attr__('Expand variables', 'microthemer'),
		        esc_attr__('Collapse variables', 'microthemer'),
		        !empty($this->preferences[$sKey]),
		        'sampled-'.$sType.'-toggle tvr-icon',
		        $sKey
	        );
        }


		/*echo $this->ui_toggle(
			'show_extra_actions',
			esc_attr__('Show more actions', 'microthemer'),
			esc_attr__('Show less actions', 'microthemer'),
			$this->preferences['show_extra_actions'],
			'extra-actions-toggle tvr-icon',
			'show_extra_actions'
		);
		*/
		?>

        <!--<input type="text" id="tvr-width-input" class="property-input combobox" name="non_section[tvr_width_input]" value="" />-->

        <span id="inputWidthCalc"></span>

		<span id="ui-nonce"><?php echo wp_create_nonce('tvr_microthemer_ui_load_styles'); ?></span>
		<span id="fonts-api" rel="<?php echo $this->thispluginurl.'includes/fonts-api.php'; ?>"></span>
		<span id="ui-url" rel="<?php echo 'admin.php?page=' . $this->microthemeruipage; ?>"></span>
		<span id="css-min" rel="<?php echo $css_min; ?>"></span>
		<span id="css-stub" rel="<?php echo $css_stub; ?>"></span>
		<span id="admin-url" rel="<?php echo $this->wp_blog_admin_url; ?>"></span>

		<span id="micro-url" rel="<?php echo $this->micro_root_url; ?>"></span>
		<span id="user-browser" rel="<?php echo $this->check_browser(); ?>"></span>
		<span id="clean-ui-url" rel="<?php echo isset($_GET['_wpnonce']) ? 1 : 0; ?>"></span>

		<span id="wpAjaxUrl" rel="<?php echo $this->wp_ajax_url; ?>"></span>
		<span id="wpMediaFakePostID"></span>

		<span id='site-url' rel="<?php echo $this->site_url; ?>"></span>
		<span id='home-url' rel="<?php echo $this->home_url; ?>"></span>
		<span id="active-styles-url" rel="<?php echo $this->micro_root_url . 'active-styles.css' ?>"></span>


		<span id='all_devices_default_width' rel='<?php echo $this->preferences['all_devices_default_width']; ?>'></span>

		<span id='plugin-url' rel='<?php echo $this->thispluginurl; ?>'></span>
		<span id='docs-url' rel='<?php echo 'admin.php?page=' . $this->docspage; ?>'></span>
		<span id='tooltip_delay' rel='<?php echo $this->preferences['tooltip_delay']; ?>'></span>
		<?php
		// edge mode settings
		if ($this->edge_mode['active']){
			?>
			<span id='edge-mode' rel='1'></span>
			<?php
			if (is_array($this->edge_mode['config'])){
				foreach ($this->edge_mode['config'] as $key => $value){
					echo '<span id="'.$key.'" rel="'.$value.'"></span>';
				}
			}
		}
		?>
		<span id='plugin-trial' rel='<?php echo $this->preferences['buyer_validated']; ?>'></span>

		<form method="post" name="tvr_microthemer_ui_serialised" id="tvr_microthemer_ui_serialised" autocomplete="off">
			<textarea id="tvr-serialised-data" name="tvr_serialized_data"></textarea>
		</form>
		<?php

		// classes that affect display of things in the ui
		$main_class = '';

		// log ie notice
		//$this->ie_notice();

		/*** Build Visual View ***/


		?>
		<form method="post" name="tvr_microthemer_ui_save" id="tvr_microthemer_ui_save" autocomplete="off">
		<?php wp_nonce_field('tvr_microthemer_ui_save');?>
		<input type="hidden" name="action" value="tvr_microthemer_ui_save" />
		<!--<textarea id="user-action" name="tvr_mcth[non_section][meta][user_action]"></textarea>-->


		<div id="visual-view" class="<?php echo $main_class; ?>">

			<div id="v-top-controls">

				<div id='hand-css-area'>
					<div id="custom-code-toolbar">
						<?php

						// hover inspect toggle
						echo $this->hover_inspect_button();

						// code view toggle
						echo $this->code_view_icon();

						?>
						<!--<div class="heading">
							<?php /*esc_html_e('Enter your own CSS or JavaScript code', 'microthemer'); */?>
						</div>-->
					</div>

					<?php
					// root toggle for manual resize of editor
					echo $this->ui_toggle(
						'code_manual_resize',
						esc_attr__('Make editor height drag resizable', 'microthemer'),
						esc_attr__('Auto-resize editor height', 'microthemer'),
						$this->preferences['code_manual_resize'],
						'editor-resize-icon tvr-icon',
						'code_manual_resize' // id
					);
					?>

					<div id="code-editors-wrap" class="tvr-editor-area">
						<div id="css-tab-areas" class="query-tabs css-code-tabs">
						<span class="edit-code-tabs show-dialog"
							title="<?php esc_attr_e('Edit custom code tabs', 'microthemer'); ?>" rel="edit-code-tabs">
						</span>

							<?php
                            //print_r($this->custom_code_flat);

							// save the configuration of the css tab
							$css_focus = !empty($this->preferences['css_focus'])
                                ? $this->preferences['css_focus']
                                : 'all-browsers';

							foreach ($this->custom_code_flat as $key => $arr) {

							    //if ($this->preferences['hide_ie_tabs'] || )

                                if ($arr['tab-key'] === 'all-browsers'){
	                                $arr['label'] = $this->preferences['allow_scss'] ? 'SCSS' : 'CSS';
                                }

								if ($key == 'hand_coded_css' or
                                    $key == 'js' or
                                    empty($this->preferences['hide_ie_tabs'])){
									echo '<span class="css-tab mt-tab css-tab-'.$arr['tab-key'].' show" rel="'.$arr['tab-key'].'">'.$arr['label'].'</span>';
								}


							}

							?>
							<!--<input class="css-focus" type="hidden"
								name="tvr_mcth[non_section][css_focus]"
								value="<?php /*echo $css_focus; */?>" />-->
						</div>
						<div id="tvr-inner-code" class="tvr-inner-code">
							<?php
							foreach ($this->custom_code_flat as $key => $arr) {

							    $code = '';
							    $include_editor = false;

								if ($key == 'hand_coded_css' or $key == 'js'){
									$opt_arr = $this->options['non_section'];
									$name = 'tvr_mcth[non_section]';
									$include_editor = true;
								} else {
									$opt_arr = !empty( $this->options['non_section']['ie_css'])
                                        ? $this->options['non_section']['ie_css']
                                        : array();
									$name = 'tvr_mcth[non_section][ie_css]';
									$include_editor = empty($this->preferences['hide_ie_tabs']);
								}

								if (!$include_editor){
								    continue;
                                }

								if (!empty($opt_arr[$key])){
									$code = htmlentities($opt_arr[$key], ENT_QUOTES, 'UTF-8');
								}

								$name.= '['.$key.']';

								if ($arr['tab-key'] == $css_focus){
									$show_c = 'show';
								} else {
									$show_c = '';
								}

								?>
								<div rel="<?php echo $arr['tab-key']; ?>"
									 class="mt-full-code mt-full-code-<?php echo $arr['tab-key']; ?> hidden
									 <?php echo $show_c; ?>" data-code-type="<?php echo $arr['type']; ?>">

                                    <div class="css-code-wrap">
										<textarea id='css-<?php echo $arr['tab-key']; ?>' class="hand-css-textarea"
                                                  data-mode="<?php echo $arr['type']; ?>"
											 name="<?php echo $name; ?>" autocomplete="off"><?php echo $code; ?></textarea>
									</div>

								</div>

							<?php
							}
							?>
						</div>
					</div>
				</div>


				<div id="advanced-wizard">

					<div id="selector-naming">

						<?php
						// alias for hover inspect button, which will vary in size, so easier to have as inline-block element here
						echo $this->hover_inspect_button(false, esc_html__('Cancel', 'microthemer'));
						?>

						<div class="heading dialog-header naming-header">
							<span class="dialog-icon"></span>
							<span class="text"><?php esc_html_e('Element targeting', 'microthemer'); ?> <!--<a href="#" target="_blank">(learn how)</a>--></span>

						</div>

						<div class="refine-target-controls">
							<span class="tvr-prev-sibling refine-button disabled"
								  title="<?php esc_attr_e("Previous element", 'microthemer'); ?>">&lArr;</span>
							<div class="updown-wrap">
							<span class="tvr-parent refine-button disabled"
								  title="<?php esc_attr_e("Parent element", 'microthemer'); ?>">&uArr;</span>
							<span class="tvr-child refine-button disabled"
								  title="<?php esc_attr_e("Child element", 'microthemer'); ?>">&dArr;</span>
							</div>
									<span class="tvr-next-sibling refine-button disabled"
										  title="<?php esc_attr_e("Next element", 'microthemer'); ?>">&rArr;</span>
						</div>

						<div class="naming-fields">

							<div class="quick-wrap wiz-name">
								<label class="wizard-name-label" title="<?php esc_attr_e("Microthemer will auto-generate a label that simply 'describes' the element(s) you target. Change the label as you please.", 'microthemer'); ?>"><?php esc_html_e('Label', 'microthemer'); ?></label>
								<label class="wizard-code-label" title="<?php esc_attr_e("The selector label is synced with CSS code. \nEnter/edit CSS code.", 'microthemer'); ?>"><?php esc_html_e('Code', 'microthemer'); ?></label>
								<span class="tvr-input-wrap wizard-name-wrap" >
									<input id='wizard-name' type='text' class='wizard-name wizard-input' name='wizard_name' value='' />
									<?php echo $this->ui_toggle(
										'selname_code_synced',
										esc_attr__('Sync selector label with code', 'microthemer'),
										esc_attr__('Unsync selector label with code', 'microthemer'),
										$this->preferences['selname_code_synced'],
										'code-chained-icon tvr-icon selname-code-sync',
										'selname_code_synced',
										array('dataAtts' => array(
											'fhtml' => 1 // for quick options font. Note: $.data() is case insensitive
										))

									); ?>

									<div id="css-filters">
										<?php
										echo $this->icon_control(
											false,
											'show_css_filters',
											false, // never show on page load
											'show_css_filters',
											'show_css_filters'
										);

										// css filter favourites
										echo '
									    <ul id="fav-filters" class="css-filter-list">'.$this->fav_css_filters.'</ul>';
										?>
									</div>
								</span>
								<span class="num-els-icon-wrap">

								</span>



							</div>

							<div class="quick-wrap wiz-folder">
								<label title="<?php esc_attr_e("Place your selector into a folder. \nJust to keep your styles organised", 'microthemer'); ?>"><?php esc_html_e('Folder', 'microthemer'); ?></label>
									<span class="tvr-input-wrap wizard-folder-wrap" >
										<input type="text" class="combobox wizard-folder has-arrows wizard-input"
											   id="wizard_folder" name="wizard_folder" rel="cur_folders" value=""
											   data-ph-title="<?php esc_attr_e("Enter or select a folder", 'microthemer'); ?>" />
										<span class="combo-arrow"></span>
									</span>
							</div>



						</div>

						<div id="create-sel-wrap" class="create-sel-wrap">

							<div class='wizard-add tvr-button'>
								<span class="mt-full-label wizard-add"><?php esc_html_e('Create selector', 'microthemer'); ?></span>
								<span class="creating-code-sel"></span>
							</div>
							<?php /*<span id="update-sel-info" class="tvr-icon info-icon" title="<?php esc_attr_e("Update selector:
	NOTE: current selector will be renamed/reorganised if you change the Name or Folder fields above.", 'microthemer'); ?>"></span>*/ ?>
							<div id="wizard-update-cur" class='wizard-update-cur tvr-button tvr-blue'>
								<span class="mt-full-label wizard-update-cur">
									<?php esc_html_e('Update selector', 'microthemer'); ?>
								</span>
							</div>

						</div>

						<?php
						echo $this->ui_toggle(
							'wizard_expanded',
							esc_attr__('Show advanced targeting options', 'microthemer'),
							esc_attr__('Hide advanced targeting options', 'microthemer'),
							$this->preferences['wizard_expanded'],
							'wizard-expand-toggle',
							false,
							array(
								'text' => 'conditional',
								'dataAtts' => array(
									'text-pos' => esc_attr__('Show advanced', 'microthemer'),
									'text-neg' => esc_attr__('Hide advanced', 'microthemer'),
								),
							)
						);
						?>

						<span class="cancel-wizard cancel tvr-icon close-icon"
							  title="<?php esc_attr_e("Close the selector wizard", 'microthemer'); ?>"></span>

						<?php
						echo $this->ui_toggle(
							'dock_wizard_right',
							esc_attr__('Dock to right', 'microthemer'),
							esc_attr__('Dock to top', 'microthemer'),
							$this->preferences['dock_wizard_right'],
							'dock-toggle'
						);
						?>



					</div>

					<div id="adv-tabs" class="query-tabs">
						<?php
						// save the configuration of the css tab
						if (empty($this->preferences['adv_wizard_tab'])){
							$adv_wizard_focus = 'refine-targeting';
						} else {
							$adv_wizard_focus = $this->preferences['adv_wizard_tab'];
						}
						$tab_headings = array(
							'html-inspector' => esc_html__('HTML', 'microthemer'),
							'css-inspector' => esc_html__('Styles', 'microthemer'),
							'css-computed' => esc_html__('Computed', 'microthemer'),
							'refine-targeting' => esc_html__('Targeting', 'microthemer'),
						);
						foreach ($tab_headings as $key => $value) {
							if ($key == $adv_wizard_focus){
								$active_c = 'active';
							} else {
								$active_c = '';
							}
							echo '<span class="adv-tab mt-tab adv-tab-'.$key.' show '.$active_c.'" rel="'.$key.'">'.$tab_headings[$key].'</span>';
						}
						// this is redundant (preferences store focus) but kept for consistency with other tab remembering
						?>
						<!--<input class="adv-wizard-focus" type="hidden"
							   name="tvr_mcth[non_section][adv_wizard_focus]"
							   value="<?php /*echo $adv_wizard_focus; */?>" />-->
					</div>

					<div class="wizard-panes">

						<div class="adv-area-html-inspector adv-area hidden <?php
						if ($adv_wizard_focus == 'html-inspector') {
							echo 'show';
						}?>">

							<div class="heading">
								<span class="text"><?php esc_html_e('Page HTML', 'microthemer'); ?></span>
							</div>


							<div id="nav-bread">

								<div id="dom-bread" class="drag-port">
									<div class="drag-containment">
										<div id="full-breadcrumbs" class="drag-content"></div>
									</div>
								</div>

							</div>


							<div id="html-preview" class="wizard-inner scrollable-area">

								<div class="css-code-wrap">
									<textarea name="inspector_html" class="dont-serialize"></textarea>
									<pre id="wizard_inspector_html" class="wizard_inspector_html"
										 data-mode="html"></pre>
								</div>

								<?php // it would ne nice if mirror could work without this. ace.edit() needs work ?>
								<div class="css-code-wrap mirror">
									<textarea name="inspector_html_mirror" class="dont-serialize"></textarea>
									<pre id="wizard_inspector_html_mirror" class="wizard_inspector_html"
										 data-mode="html"></pre>
								</div>


								<?php
								/*
								?>
								echo $this->ui_toggle(
									'ace_full_page_html',
									esc_attr__('Show full page HTML', 'microthemer'),
									esc_attr__('Show reduced HTML', 'microthemer'),
									$this->preferences['ace_full_page_html'],
									'full-page-html'
								);
								*/
								?>

							</div>



						</div>


						<div class="adv-area-css-inspector adv-area hidden <?php
						if ($adv_wizard_focus == 'css-inspector') {
							echo 'show';
						}
						?>">

							<div id="actual-styles" class="actual-styles wizard-inner scrollable-area"></div>

						</div>


						<div class="adv-area-css-computed adv-area hidden <?php
						if ($adv_wizard_focus == 'css-computed') {
							echo 'show';
						}
						?>">

							<div class="css-inner-wrap wizard-inner scrollable-area">
								<div id="html-computed-css">

									<?php
									$i = 1;
									foreach ($this->property_option_groups as $property_group => $pg_label) {
										?>
										<ul id="comp-<?php echo $property_group; ?>"
											class="accordion-menu property-menu <?php if ($i&1) { echo 'odd'; } ?>">
											<li class="css-group-heading accordion-heading">
												<span class="menu-arrow accordion-menu-arrow tvr-icon" title="<?php esc_attr_e("Open/close group", 'microthemer'); ?>"></span>
												<span class="text-for-group"><?php echo $pg_label; ?></span>
											</li>
											<?php
											++$i;
											?>
										</ul>
									<?php
									}
									?>
								</div>
							</div>

						</div>


						<div id="refine-targeting-pane" class="adv-area-refine-targeting adv-area hidden <?php
						if ($adv_wizard_focus == 'refine-targeting') {
							echo 'show';
						}
						?>">

							<div class="heading">

                                <span class="text"><?php esc_html_e('Targeting suggestions', 'microthemer'); ?></span>

                                <?php
                                $specificity_high = !empty($this->preferences['specificity_preference']) ? ' on' : '';
                                ?>
                                <div class="ui-toggle uit-par specificity-preference<?php echo $specificity_high; ?>" data-aspect="specificity_preference">
                                    <span class="specificity-label"
                                          title=" <?php esc_attr_e('Sort by specificity', 'microthemer'); ?>">
                                        <?php esc_html_e('Specificity', 'microthemer'); ?>:
                                    </span>
                                    <span class="specificity-low ui-toggle"
                                          title=" <?php esc_attr_e('favour classes', 'microthemer'); ?>">
                                        <?php esc_html_e('low', 'microthemer'); ?>
                                    </span>
                                    <span class="specificity-high ui-toggle"
                                          title=" <?php esc_attr_e('favour ids', 'microthemer'); ?>">
                                        <?php esc_html_e('high', 'microthemer'); ?>
                                    </span>
                                </div>
							</div>


							<?php /*<span id="n-els" title="<?php esc_attr_e('Number of elements a selector targets',
								'microthemer'); ?>">N</span> */ ?>

							<div class="wizard-inner scrollable-area">

								<ul id="code-suggestions"></ul>

								<div id="targets-nothing" class="hidden">

									<div id="nothing-invalid" class="hidden">
										<div class="targets-nothing-heading">
											<?php echo esc_html__('Invalid selector', 'microthemer'); ?>:
										</div>
										<ul id="targets-nothing-invalid"></ul>
									</div>


									<div id="nothing-valid" class="hidden">
										<div class="targets-nothing-heading">
											<?php echo esc_html__('Selectors that target zero elements', 'microthemer'); ?>:
										</div>
										<ul id="targets-nothing-sels"></ul>
									</div>

								</div>

							</div>
						</div>


					</div>

				</div>


				<div id="right-stuff-wrap">

					<div id="status-board" class="tvr-popdown-wrap">

						<div id="status-short"></div>

						<div id="full-logs" class="tvr-popdown scrollable-area">
							<div id="tvr-dismiss">
								<span class="link dismiss-status"><?php esc_html_e('dismiss', 'microthemer'); ?></span>
								<span class="tvr-icon close-icon dismiss-status"></span>
							</div>
							<div class="heading ensure-selectable"><?php esc_html_e('Microthemer Notifications', 'microthemer'); ?></div>
							<?php
							echo $this->display_log();
							?>

							<div id="script-feedback"></div>
						</div>
					</div>

				</div>

				<?php
				/*echo $this->ui_toggle(
					'show_code_editor',
					esc_attr__('Show code editor view', 'microthemer'),
					esc_attr__('Show GUI view', 'microthemer'),
					$this->preferences['show_code_editor'],
					'toggle-full-code-editor',
					'code-editor-alias'
				)*/
				?>

				<?php
                echo $this->show_me;
				?>


			</div>

            <div id="z-control">



                <div id="tvr-nav" class="tvr-nav">

					<?php
					// hover inspect toggle
					echo $this->hover_inspect_button('hover-inspect-toggle');

					// code view toggle
					echo $this->code_view_icon();
					?>

                    <div id="quick-nav" class="quick-nav">
							<span id="vb-focus-prev" class="scroll-buttons tvr-icon"
                                  title="<?php esc_attr_e("Previous selector (Ctrl+Alt+,)", 'microthemer'); ?>"></span>
                        <span id="vb-focus-next" class="scroll-buttons tvr-icon"
                              title="<?php esc_attr_e("Next selector (Ctrl+Alt+.)", 'microthemer'); ?>"></span>
						<?php
						// for quick debugging
						//$this->show_me = $this->json_format_ua('icon', 'item', 'val');

						?>
                    </div>

                    <div id="tvr-main-menu" class="tvr-main-menu-wrap">
                        <div class="mt-opt-divider"></div>
                        <span id="main-menu-tip-trigger" class="main-menu-tip-trigger">
								<span class="menu-arrow main-menu-tip-trigger tvr-icon"></span>
								<span class="main-menu-text main-menu-tip-trigger"
                                      title="<?php esc_attr_e("Manage existing selectors", 'microthemer'); ?>">
									<?php esc_html_e("Selectors", 'microthemer'); ?>
								</span>
							</span>
                        <div id="main-menu-popdown" class="main-menu-popdown">

                            <div class="scrollable-area menu-scrollable">
                                <ul id='tvr-menu'>
									<?php
									foreach ( $this->options as $section_name => $array) {
										// if non_section continue
										if ($section_name == 'non_section') {
											continue;
										}
										// section menu item (trigger function for menu selectors too)
										echo $this->menu_section_html($section_name, $array);
										++$this->total_sections;
									}
									?>
                                </ul>
                            </div>
                            <!-- keep track of total sections & selectors -->
                            <div id="ui-totals-count">

                                <span id="section-count-state" class='section-count-state' rel='<?php echo $this->total_selectors; ?>'></span>

                                <span id="total-sec-count"><?php echo $this->total_sections; ?></span>
                                <span class="total-folders"><?php esc_html_e('Folders', 'microthemer'); ?>&nbsp;&nbsp;</span>


                                <span id="total-sel-count"><?php echo $this->total_selectors; ?></span>
                                <span><?php esc_html_e('Selectors', 'microthemer'); ?></span>

                                <span id="new-section-add" class='new-section-add'
                                      title="<?php esc_attr_e('Create a new folder', 'microthemer'); ?>">
												<?php esc_html_e('New folder', 'microthemer'); ?>
									</span>

                            </div>
                        </div>
                    </div>

                    <span id="current-selector"></span>

                    <div id="starter-message" class="hidden">
                        <span rel="program-docs" data-docs-index="1" class="link item-program-docs show-dialog" title="<?php esc_html_e('Quick tips on how to use Microthemer', 'microthemer'); ?>"><?php esc_html_e('Getting started tips', 'microthemer'); ?></span>
                    </div>

                </div>

                <div id="responsive-bar">
		            <?php echo $this->global_media_query_tabs(); ?>
                </div>

                <div id="css-group-icons">
		            <?php echo $this->css_group_icons(); ?>
                </div>



            </div>

            <ul id='tvr-options'>
				<?php
				foreach ( $this->initial_options_html as $key => $html) {
					echo $html;
				}
				?>

				<?php
				include $this->thisplugindir . 'includes/grid-control.inc.php';
				echo $grid_control;
				?>

            </ul>



            <div id="inline-editor">

                <div class="tvr-editor-area">
                    <div class="css-code-wrap">
                        <pre id="adaptable-editor" class="custom-css-pre"></pre>
                    </div>
                </div>

                <div class="editor-actions">
                    <span class="quick-opts-wrap tvr-fade-in">
                        <span class="css-icon mt-icon"></span>
                        <span class="quick-opts">
                            <div class="quick-opts-inner">
                                <?php echo $this->manual_resize_icon('inline-code'); ?>
                            </div>
                        </span>
                    </span>
                </div>

                <span class="gui-shortcut" title="Ctrl + Alt + J">J</span>

            </div>

            <?php
            $page_context = $this->microthemeruipage;
            include $this->thisplugindir . 'includes/tvr-microthemer-preview-wrap.php';
            ?>

			<div id="v-left-controls-wrap">

				<div id="v-left-controls-old">
					<?php // echo $this->display_left_menu_icons(); ?>
				</div>

				<div id="v-left-controls">
					<?php echo $this->system_menu(); ?>
					<div class="notify-draft" title="<?php esc_attr_e("To publish your changes, simply turn draft mode off", 'microthemer'); ?>">
						<span class="draft-mode tvr-icon nd-icon"></span>
						<span class="nd-text"><?php esc_html_e('Draft mode: ON', 'microthemer'); ?></span>
					</div>
					<?php
					if (!$this->preferences['buyer_validated']){
						?>
						<div class="cta-wrap">
							<a class="cta-button buy-cta tvr-button red-button" href="https://themeover.com" target="_blank"
							   title="<?php esc_attr_e('Purchase a license to use the full program', 'microthemer'); ?>">
								<span class="tvr-icon"></span>
								<span class="cta-label"><?php esc_html_e('Buy', 'microthemer'); ?></span>
							</a>
							<span class="cta-button unlock-cta tvr-button show-dialog"
								  title="<?php esc_attr_e("If you have purchased Microthemer you can enter your email address to unlock the full program. If you have not yet purchased Microthemer, you cannot unlock the full version.", 'microthemer'); ?>" rel="unlock-microthemer">
								<span class="tvr-icon show-dialog" rel="unlock-microthemer"></span>
								<span class="cta-label show-dialog" rel="unlock-microthemer"><?php esc_html_e("Unlock", 'microthemer'); ?></span>
							</span>
						</div>
					<?php
					}

					if ($this->is_capped_version()){
						?>
                        <div class="cta-wrap">

                            <span class="cta-button unlock-cta tvr-button show-dialog"
                                  title="<?php esc_attr_e("Renew your subscription to get the latest version of Microthemer.", 'microthemer'); ?>" rel="unlock-microthemer">
								<span id="mt-renew-prompt" class="cta-label show-dialog" rel="unlock-microthemer"><?php esc_html_e("Enable Updates", 'microthemer'); ?></span>
							</span>
                        </div>
						<?php
					}
					?>
				</div>


			</div>

            <div id="style-components"></div>

            <div id="mt-slider-set">

                <span class="mtss-buttons">
                    <span class="mtss-button mtss-decr-button"></span>
                    <span class="mtss-button mtss-incr-button"></span>
                </span>

                <div class="mtss-slider-port drag-port">

                    <div class="mtss-slider-containment drag-containment">
                        <div id="mtss-dragbar" class="mtss-drag-content drag-content">
                            <div class="mousedown-fix"></div>

		                    <?php
		                    /*for ($x = 0; $x < 360; $x+= 10) {
			                    echo '<span class="mtss-block">'.$x.'</span>';
		                    }*/
		                    ?>
                        </div>
                    </div>

                    <div class="mtss-marker">
                        <div class="mtss-marker-top"></div>
                        <div class="mtss-marker-bottom"></div>
                    </div>

                </div>

                <span class="mtss-unit-menu">

                    <span class="mtss-current-unit"></span>

                    <!--<span class="mtss-px-equiv">
                        (<span class="mtss-px-value">20</span><span class="mtss-px">px</span>)
                    </span>-->

                    <span class="mtss-numeric">
                        <span class="mtss-numeric-value"></span>
                        <span class="mtss-px-equiv">
                            = <span class="mtss-px-value">20</span><span class="mtss-px">px</span>
                        </span>
                    </span>

                    <div class="mtss-units">
                        <?php
                        $html = '';
                        //$units = array_merge(array(''))
                        foreach ($this->css_units as $unit_cat => $cat_units){
                            $first_unit_key = array_keys($cat_units)[0];
	                        $html.= '
	                        <ul class="unit-cat unit-cat-'.$cat_units[$first_unit_key]['type'].'">
	                            <li class="unit-cat-heading">'.$unit_cat.'</li>';
                                foreach ($cat_units as $unit => $unit_data){
                                    $html.= '<li class="mt-unit-item" data-unit="'.$unit.'"
                                    title="'.$unit_data['desc'].'">'.$unit.'</li>';
                                }
	                        $html.= '
                            </ul>';
                        }
                        echo $html;
                        ?>
                    </div>

                </span>



            </div>

		</div>

		<?php

		// mt logo, interface collapse
		echo $this->ui_toggle(
			'show_interface',
			esc_attr__('Show Microthemer toolbar', 'microthemer')."\n",
			esc_attr__('Hide Microthemer toolbar', 'microthemer')."\n",
			$this->preferences['show_interface'],
			'm-logo',
			'm-logo'
		);
		?>

		<?php
		/*// store the active media queries so they can be shared with design packs
		if (is_array($this->preferences['m_queries'])){
			foreach ($this->preferences['m_queries'] as $key => $m_query) {
				echo '
			<input type="hidden" name="tvr_mcth[non_section][active_queries]['.$key.'][label]" value="'.esc_attr($m_query['label']).'" />
			<input type="hidden" name="tvr_mcth[non_section][active_queries]['.$key.'][query]" value="'.esc_attr($m_query['query']).'" />';
			}
		}

		// store the active js deps so they can also be shared with design packs
		if (!empty($this->preferences['enq_js']) and is_array($this->preferences['enq_js'])){
			foreach ($this->preferences['enq_js'] as $key => $arr) {
				if (empty($arr['disabled'])){
					echo '
					<input type="hidden" name="tvr_mcth[non_section][active_enq_js]['.$key.'][display_name]"
					value="'.esc_attr($arr['display_name']).'" />';
				}
			}
		}

		// store the active JS events
        $active_events = !empty($this->options['non_section']['active_events'])
            ? $this->options['non_section']['active_events'] : '';
		echo '<textarea id="mt_active_events" name="tvr_mcth[non_section][active_events]">'
                . esc_html($active_events).
             '</textarea>';*/


		// send the time settings were last saved to ensure another tabs hasn't saved settings more recently
        /*$last_save_time = !empty($this->options['non_section']['last_save_time']) ?
            $this->options['non_section']['last_save_time'] : '';
		echo '<input id="send-last-save-time" type="hidden" name="tvr_mcth[non_section][last_save_time]" 
		value="'.esc_attr($last_save_time).'" />';*/
		?>

		</form>


		<!-- </div>end tvr-ui -->
	<?php

	// output dynamic JS here as it changes on page load
	echo '<script type="text/javascript">';

	// auto-show unlock dialog
	echo 'var launchMTUnlock = ' . (isset($_GET['launch_unlock']) ? 1 : 0) . ";\n\n";

	include $this->thisplugindir . '/includes/js-dynamic.php';

	echo '</script>';


	if (!$this->optimisation_test){
		?>
		<div id="dialogs">

			<!-- Unlock Microthemer -->
			<form name='tvr_validate_form' method="post"
				autocomplete="off" action="admin.php?page=<?php echo $this->microthemeruipage;?>" >
				<?php
				if ($this->preferences['buyer_validated']){
					$title = esc_html__('Microthemer Has Been Successfully Unlocked', 'microthemer');
					if ($this->is_capped_version()){
						$title = esc_html__('Renew your subscription to get the latest version', 'microthemer');
					}
				} else {
					$title = esc_html__('Enter your PayPal email to unlock Microthemer', 'microthemer');
				}
				echo $this->start_dialog('unlock-microthemer', $title, 'small-dialog'); ?>
				<div class="content-main">
					<?php
					if ($this->preferences['buyer_validated'] and !$this->is_capped_version()){
						$class = '';
						if (!empty($this->preferences['license_type'])){
							echo '<p>' . esc_html__('License Type: ', 'microthemer') . '<b>'.$this->preferences['license_type'].'</b></p>';
						}
						?>
						<p><span class="link reveal-unlock"><?php esc_html_e('Validate software using a different email address', 'microthemer'); ?></span>
						</p>
					<?php
					} else {
						$class = 'show';
					}
					?>
					<div id='tvr_validate_form' class='hidden <?php echo $class; ?>'>
						<?php wp_nonce_field('tvr_validate_form'); ?>
						<?php
						if (!$this->preferences['buyer_validated'] or $this->is_capped_version()){
							$attempted_email = esc_attr($this->preferences['buyer_email']);
						} else {
							$attempted_email = '';
						}

						// instructions to renew subscription then unlock again.
						if ($this->is_capped_version()){
						    ?>
                            <div class="renew-instructions">
                                <div class="heading">Step 1</div>
                                <p>Renew your subscription via <a href="https://themeover.com/my-account/" target="_blank">My Downloads</a>.</p>
                                <p>Once you've completed the checkout process, move onto step 2.</p>
                                <br />
                                <div class="heading">Step 2</div>
                            </div>
                            <?php
                        }

						?>
						<ul class="form-field-list">
							<li>
								<label class="text-label" title="<?php esc_attr_e("Enter email or unlock code shown in 'My Downloads'", 'microthemer'); ?>">
                                    <?php esc_html_e('Enter email or unlock code shown in ', 'microthemer'); ?>
                                    <a href="https://themeover.com/my-account/" target="_blank">
                                        <?php esc_html_e('My Downloads', 'microthemer'); ?>
                                    </a>
                                </label>
								<input type='text' autocomplete="off" name='tvr_preferences[buyer_email]'
									value='<?php echo $attempted_email; ?>' />
							</li>
						</ul>

						<?php echo $this->dialog_button('Validate', 'input', 'ui-validate'); ?>



						<div class="explain">
							<div class="heading link explain-link"><?php esc_html_e('About this feature', 'microthemer'); ?></div>

							<div class="full-about">
								<p><?php echo wp_kses(
									__('<b>Note:</b> Themeover will record your domain name when you submit your email address for license verification purposes.', 'microthemer'),
									array( 'b' => array() )
								) ; ?></p>
								<p><?php echo wp_kses(
									sprintf(
										__('<b>Note:</b> if you have any problems with the validator <a %s>send Themeover a quick email</a> and we"ll get you unlocked ASAP.', 'microthemer'),
										'href="https://themeover.com/support/pre-sales-enquiries/" target="_blank"'
									),
									array( 'a' => array( 'href' => array(), 'target' => array() ), 'b' => array() )
								); ?></p>
							</div>
						</div>


					</div>

				</div>
				<?php
				if (!$this->preferences['buyer_validated']){
					echo $this->end_dialog(esc_html__('Validate', 'microthemer'), 'input', 'ui-validate');
				} else {
					echo $this->end_dialog(esc_html_x('Close', 'verb', 'microthemer'), 'span', 'close-dialog');
				}
				?>
			</form>

			<?php
			// this is a separate include because it needs to have separate page for changing gzip
			$page_context = $this->microthemeruipage;
			include $this->thisplugindir . 'includes/tvr-microthemer-preferences.php';
			?>

			<!-- Edit Media Queries -->
			<form id="edit-media-queries-form" name='tvr_media_queries_form' method="post" autocomplete="off"
				action="admin.php?page=<?php echo $this->microthemeruipage;?>" >
				<input type="hidden" name="tvr_media_queries_submit" value="1" />
				<?php echo $this->start_dialog('edit-media-queries', esc_html__('Edit Media Queries (For Designing Responsively)', 'microthemer'), 'small-dialog'); ?>

				<div class="content-main">

					<ul class="form-field-list">
						<?php

						// yes no options
						$yes_no = array(
							'initial_scale' => array(
								'label' => __('Set device viewport zoom level to "1"', 'microthemer'),
								'explain' => __('Set this to yes if you\'re using media queries to make your site look good on mobile devices. Otherwise mobile phones etc will continue to scale your site down automatically as if you hadn\'t specified any media queries. If you set leave this set to "No" it will not override any viewport settings in your theme, Microthemer just won\'t add a viewport tag at all.', 'microthemer')
							)

						);
						// text options
						$text_input = array(
							'all_devices_default_width' => array(
								'label' => __('Default screen width for "All Devices" tab', 'microthemer'),
								'explain' => __('Leave this blank to let the frontend preview fill the full width of your screen when you\'re on the "All Devices" tab. However, if you\'re designing "mobile first" you can set this to "480px" (for example) and then use min-width media queries to apply styles that will only have an effect on larger screens.', 'microthemer')
							),
						);

						// mq set combo
						$media_query_sets = array(
							'load_mq_set' => array(
								'combobox' => 'mq_sets',
								'label' => __('Select a media query set', 'microthemer'),
								'explain' => __('Microthemer lets you choose from a list of media query "sets". If you are trying to make a non-responsive site look good on mobiles, you may want to use the default "Desktop-first device MQs" set. If you designing mobile first, you may want to try an alternative set.', 'microthemer')
							)
						);

						// overwrite options
						$overwrite = array(
							'overwrite_existing_mqs' => array(
								//'default' => 'yes',
								'label' => __('Overwrite your existing media queries?', 'microthemer'),
								'explain' => __('You can overwrite your current media queries by choosing "Yes". However, if you would like to merge the selected media query set with your existing media queries please choose "No".', 'microthemer')
							)
						);

						$this->output_radio_input_lis($yes_no);

						$this->output_text_combo_lis($text_input);
						?>
						<li><span class="reveal-hidden-form-opts link reveal-mq-sets" rel="mq-set-opts"><?php esc_html_e('Load an alternative media query set', 'microthemer'); ?></span></li>
						<?php

						$this->output_text_combo_lis($media_query_sets, 'hidden mq-set-opts');

						$this->output_radio_input_lis($overwrite, 'hidden mq-set-opts');


						?>
					</ul>

					<div class="heading"><?php esc_html_e('Media Queries', 'microthemer'); ?></div>


					<?php echo $this->dyn_menu(
						$this->preferences['m_queries'], // data
						$this->mq_structure, // structure
						array('controls' => 1) // config
					); ?>

					<div class="explain">
						<div class="heading link explain-link"><?php esc_html_e('About this feature', 'microthemer'); ?></div>

						<div class="full-about">

							<p><?php esc_html_e('If you\'re not using media queries in Microthemer to make your site look good on mobile devices you don\'t need to set the viewport zoom level to 1. You will be passing judgement over to the devices (e.g. an iPhone) to display your site by automatically scaling it down. But if you are using media queries you NEED to set this setting to "Yes" in order for things to work as expected on mobile devices (otherwise mobile devices will just show a proportionally reduced version of the full-size site).', 'microthemer'); ?></p>
							<p><?php echo wp_kses (
								sprintf(
									__('You may want to read <a %s>this tutorial which gives a bit of background on the viewport meta tag</a>.', 'microthemer'),
									'target="_blank" href="http://www.paulund.co.uk/understanding-the-viewport-meta-tag"'
								),
								array( 'a' => array( 'href' => array(), 'target' => array() ) )
							); ?></p>
							<p><?php esc_html_e('Feel free to rename the media queries and change the media query code. You can also reorder the media queries by dragging and dropping them. This will determine the order in which the media queries are written to the stylesheet and the order that they are displayed in the Microthemer interface.', 'microthemer'); ?></p>
							<p><?php esc_html_e('TIP: to reset the default media queries simply delete all media query boxes and then save your settings', 'microthemer'); ?></p>
						</div>
					</div>

				</div>

				<?php echo $this->end_dialog(esc_html__('Update Media Queries', 'microthemer'), 'span', 'update-media-queries'); ?>
			</form>

			<!-- Enqueue JS libraries -->
			<form id="mt-enqueue-js" name='mt_enqueue_js' method="post" autocomplete="off"
				  action="admin.php?page=<?php echo $this->microthemeruipage;?>" >

				<input type="hidden" name="mt_enqueue_js_submit" value="1" />
				<?php echo $this->start_dialog(
					'mt-enqueue-js',
					esc_html__('Enqueue WordPress JavaScript Libraries', 'microthemer'),
					'small-dialog'
				); ?>

				<div class="content-main">

					<p><?php echo esc_html__('If you want to write custom JavaScript code that depends on jQuery or any other JS library, you can enqueue it here. The dropdown menu below only includes the most popular script handles.', 'microthemer'); ?>
					 <a href="https://developer.wordpress.org/reference/functions/wp_register_script/" target="_blank">
						 <?php echo esc_html__('View more WP script handles online.', 'microthemer'); ?>
					 </a>

					</p>

					<?php echo $this->dyn_menu(
						$this->preferences['enq_js'], // data
						$this->enq_js_structure, // structure
						array('controls' => 1) // config
					); ?>

				</div>

				<?php echo $this->end_dialog(esc_html__('Update JS Libraries', 'microthemer'), 'span', 'update-enqjs'); ?>

			</form>

			<!-- Edit Custom Code Tabs -->
			<form id="edit-code-tabs" name='tvr_code_tabs_form' method="post" autocomplete="off"
				action="admin.php?page=<?php echo $this->microthemeruipage;?>" >
				<?php wp_nonce_field('tvr_code_tabs_form'); ?>
				<?php echo $this->start_dialog('edit-code-tabs', esc_html__('Manage Custom Code Editors', 'microthemer'), 'small-dialog'); ?>

				<div class="content-main">
					<ul id="code-list">
						<?php
						$i = 0;
						/*
						if (is_array($this->preferences['code_tabs'])){
							foreach ($this->preferences['code_tabs'] as $key => $value) {
								if (is_array($value)){
									foreach ($value as $key => $v2) {
										echo '<li>This is just a teaser: <b>'.$v2.'</b></li>';
									}
								} else {
									echo '<li>This is just a teaser: <b>'.$value.'</b></li>';
								}
								++$i;
							}
						}
						*/
						?>
					</ul>
					<p>You will be able to create new code editors and specify the language (CSS, SCSS, JavaScript)
					as well as browser targeting.</p>
				</div>
				<?php
				echo $this->end_dialog(esc_html__('Update Custom Code Tabs', 'microthemer'), 'input', 'update-custom-code-tabs');
				?>
			</form>

			<!-- manage custom code row template
			...
			-->


			<!-- Display (Potentially) External CSS file -->
			<?php echo $this->start_dialog(
				'inspect-stylesheet',
				esc_html__('Inspect CSS Stylesheet', 'microthemer'),
				'medium-dialog'
			); ?>
			<div class="content-main">
				<div class="css-code-wrap">
					<textarea name="inspect_stylesheet" class="dont-serialize"></textarea>
					<pre id="inspect_stylesheet_preview" class="inspect_stylesheet_preview" data-mode="css"></pre>
				</div>
			</div>
			<?php echo $this->end_dialog(esc_html_x('Close', 'verb', 'microthemer'), 'span', 'close-dialog'); ?>



			<!-- Import dialog -->
			<?php
				$tabs = array(
					esc_html__('MT Design Pack', 'microthemer'),
					esc_html__('CSS Stylesheet', 'microthemer'),
				);
			?>
			<form method="post" id="microthemer_ui_settings_import" autocomplete="off">
				<input type="hidden" name="import_pack_or_css" value="1" />
				<?php echo $this->start_dialog('import-from-pack', esc_html__('Import settings from a design pack or CSS Stylesheet', 'microthemer'), 'medium-dialog', $tabs); ?>

				<div class="content-main dialog-tab-fields">

					<?php
					foreach ($tabs as $i => $name){
						$show = $i == 0 ? 'show' : '';
						// design pack import
						?>
						<div class="dialog-tab-field dialog-tab-field-<?php echo $i; ?> hidden <?php echo $show; ?>">
						<?php
						if ($i == 0){
							?>

							<p><?php esc_html_e('Select a design pack to import', 'microthemer'); ?></p>
							<p class="combobox-wrap tvr-input-wrap">
								<input type="text" class="combobox has-arrows" id="import_from_pack_name" name="import_from_pack_name" rel="directories"
									   value="" />
								<span class="combo-arrow"></span>
							</p>
							<p class="enter-name-explain"><?php esc_html_e('Choose to overwrite or merge the imported settings with your current settings', 'microthemer'); ?></p>

							<ul id="overwrite-merge" class="checkboxes fake-radio-parent">
								<li><input name="tvr_import_method" type="radio" value="<?php esc_attr_e('Overwrite', 'microthemer'); ?>" id='ui-import-overwrite'
										   class="radio ui-import-method" />
									<span class="fake-radio"></span>
									<span class="ef-label"><?php esc_html_e('Overwrite', 'microthemer'); ?></span>
								</li>
								<li><input name="tvr_import_method" type="radio" value="<?php esc_attr_e('Merge', 'microthemer'); ?>" id='ui-import-merge'
										   class="radio ui-import-method" />
									<span class="fake-radio"></span>
									<span class="ef-label"><?php esc_html_e('Merge', 'microthemer'); ?></span>
								</li>
							</ul>
							<?php /*
				<p class="button-wrap"><?php echo $this->dialog_button(__('Import', 'microthemer'), 'span', 'ui-import'); ?></p>*/
							?>
							<div class="explain">
								<div class="heading link explain-link"><?php esc_html_e('About this feature', 'microthemer'); ?></div>
								<div class="full-about">
									<p><?php echo wp_kses(
											sprintf(
												__('Microthemer can be used to restyle any WordPress theme or plugin without the need for pre-configuration. That\'s thanks to the handy "Double-click to edit" feature. But just because you <i>can</i> do everything yourself doesn\'t mean <i>have</i> to. That\'s where importable design packs come in. A design pack contains folders, selectors, hand-coded CSS, and background images that someone else has created while working with Microthemer. Of course it may not be someone else, you can create design packs too using the "<span %s>Export</span>" feature!', 'microthemer'),
												'class="link show-dialog" rel="export-to-pack"'
											),
											array( 'i' => array(), 'span' => array() )
										); ?> </p>
									<p><?php printf(
											esc_html__('Note: you can install other people\'s design packs via the "%s" window.', 'microthemer'),
											'<span class="link show-dialog" rel="manage-design-packs">' . __('Manage Design Packs', 'microthemer') . '</span>'
										); ?></p>
									<p><b><?php esc_html_e('You may want to make use of this feature for the following reasons:', 'microthemer'); ?></b></p>
									<ul>
										<li><?php printf(
												esc_html__('You\'ve downloaded and installed a design pack that you found on %s for restyling a theme, contact form, or any other WordPress content you can think of. Importing it will load the folders and hand-coded CSS contained within the design pack into the Microthemer UI.', 'microthemer'),
												'<a target="_blank" href="http://themeover.com/">themeover.com</a>'
											); ?></li>
										<li><?php esc_html_e('You previously exported your own work as a design pack and now you would like to reload it back into the Microthemer UI.', 'microthemer'); ?></li>
									</ul>
								</div>
							</div>
							<br /><br /><br /><br />
							<?php
						}
						// css stylesheet import
						else {
							// textarea for posting
							?>
							<textarea id="stylesheet_import_json" name="stylesheet_import_json"></textarea>
							<textarea id="get_remote_images" name="get_remote_images"></textarea>
							<?php

							// combobox for previously entered stylesheets and suggest theme/MT stylesheets.
							$default_sheet = !empty($this->preferences['viewed_import_stylesheets'][0])
								? $this->preferences['viewed_import_stylesheets'][0] : '';
							?>
							<p>
							</p>

							<div class="stylesheet-to-import">
								<span class="combobox-wrap tvr-input-wrap">
								<input id="stylesheet_to_import" type="text" name="stylesheet_to_import" class="combobox has-arrows"
									   rel="viewed_import_stylesheets" value="<?php echo $default_sheet; ?>" title="<?php echo
								esc_attr__('Enter or select a CSS stylesheet URL', 'microthemer'); ?>" />

								<span class="combo-arrow"></span>

								<span class="refresh-icon refresh-stylesheet-list tvr-icon" title="<?php echo
									esc_attr__('Get stylesheets affecting the current page', 'microthemer'); ?>"></span>


							</span>

							<span class="tvr-button view-import-stylesheet" title="<?php echo
							esc_attr__('Load stylesheet contents into the editor below', 'microthemer'); ?>">
									<?php echo esc_html__('Load Stylesheet', 'microthemer'); ?>
								</span>
							</div>


							<div id="imp-css-preview" class="imp-css-preview">

								<p class="imp-editor-extra">

									<span class="how-to-css-import" title="<?php echo esc_html__('You can paste arbitrary CSS into the editor below. Or load the contents of a stylesheet using the option above. NOTE: use the \'Only import selected text\' option if you just want to import CSS code that you have highlighted with your mouse.', 'microthemer'); ?>">

										<span class="tvr-icon info-icon"></span>
										<span> Help</span>
									</span>

									<span class="only-import-selected-text">
										<?php
										$checked = '';
										$on = '';
										if (!empty($this->preferences['css_imp_only_selected'])){
											$checked = 'checked="checked"';
											$on = 'on';
										}
										?>
										<input type="checkbox" name="tvr_preferences[css_imp_only_selected]"
											<?php echo $checked; ?> value="1" />
										<span class="fake-checkbox toggle-import-selected-text <?php echo $on; ?>"></span>
										<span class="ef-label import-selected-label">
											<?php esc_html_e('Only import selected text', 'microthemer'); ?>
										</span>
									</span>

								</p>
								<div class="css-code-wrap">
									<textarea name="css_to_import" class="dont-serialize"></textarea>
								<pre id="preview-import-css-0" class="preview-import-css preview-import-css-0"
									 data-mode="css"></pre>
								</div>

								<div class="heading"><?php echo esc_html__('Stylesheet Import Options', 'microthemer'); ?></div>
								<ul id="user-import-css-opts" class="form-field-list">
									<?php

									// yes no options
									$yes_no = array(
										'css_imp_mqs' => array(
											'label' => __('Import media queries', 'microthemer'),
											'explain' => __('Media queries will be imported (recommended).', 'microthemer')
										),
										'css_imp_sels' => array(
											'label' => __('Import selectors', 'microthemer'),
											'explain' => __('CSS Selectors will be imported. You can set this to "No" if you just want to import your theme\'s media queries.', 'microthemer')
										),
										'css_imp_styles' => array(
											'label' => __('Import styles', 'microthemer'),
											'explain' => __('CSS properties and values will be added to the imported selectors. The above "Import Selectors" option must be set to "Yes" for this option to work.', 'microthemer')
										),
										'css_imp_friendly' => array(
											'label' => __('Give selectors friendly names', 'microthemer'),
											'explain' => __('When using the selector wizard, Microthemer can give selectors more human readable names. This option mimics that behaviour.', 'microthemer')
										),
										'css_imp_adjust_paths' => array(
											'label' => __('Make relative URLs absolute', 'microthemer'),
											'explain' => __('This will ensure @import and url() file paths are valid even though the location of the stylesheet will change. An URL to the original source of the CSS must be provided above the editor for this to work (even if you have not used the "LOAD STYLESHEET" button).', 'microthemer')
										),

										'css_imp_copy_remote' => array(
											'label' => __('Copy images to WP media library', 'microthemer'),
											'explain' => __('Microthemer will copy any images referenced in the stylesheet to your WordPress media library. Image file paths will be automatically adjusted.',
												'microthemer')

										/* 'label' => __('Copy remote images to WP media library', 'microthemer'),
										'explain' => __('Microthemer will copy any remote images referenced in the stylesheet to your WordPress media library. Image file paths will be automatically adjusted. Local images (on this domain) will not be copied.',
											'microthemer') */
										),
										/*'css_imp_always_cus_code' => array(
											'label' => __('Always add styles to GUI selector code field', 'microthemer'),
											'explain' => __('Always add imported styles to a GUI selector\'s custom code editor - even when a dedicated GUI field exists for the CSS property. This normally only happens when no GUI field exists.', 'microthemer')
										),*/
									);

									if (!empty($this->preferences['allow_scss'])){
										$yes_no['css_imp_always_cus_code'] = array(
											'label' => __('Always add styles to GUI selector code field', 'microthemer'),
											'explain' => __('Always add imported styles to a GUI selector\'s custom code editor - even when a dedicated GUI field exists for the CSS property. This normally only happens when no GUI field exists.', 'microthemer')
										);
                                    }

									// text options
									$text_input = array(
										'css_imp_max' => array(
											'label' => __('Max @import rules to follow', 'microthemer'),
											'explain' => __('Instead of adjusting @import file paths, Microthemer can follow these paths and combine CSS code it finds there with the initial stylesheet. Thus doing a deep import of the CSS into Microthemer\'s GUI interface.', 'microthemer')
										),
									);

									$this->output_radio_input_lis($yes_no);

									// $this->output_text_combo_lis($text_input); // add this feature later

									?>
								</ul>

								<p>
									<span class="tvr-button view-import-stats">
										<?php echo esc_html__('Review Before Importing', 'microthemer'); ?>
									</span>
								</p>

								<div id="import-stats" class="hidden">
									<div class="heading">Import Stats</div>
									<p>Preview the data before importing. For long lists of styles or selectors, type in the fields to filter down the results.</p>
									<br />
									<?php
									$stats = array(
										/*'errors' => array(
											'desc' => esc_html__('Import errors', 'microthemer'),
											'type' => 'combo'
										),*/
										'media' => array(
											'desc' => esc_html__('GUI media queries', 'microthemer'),
											'type' => 'combo'
										),
										'folders' => array(
											'desc' => esc_html__('GUI folders', 'microthemer'),
											'type' => 'combo'
										),
										'selectors' => array(
											'desc' => esc_html__('GUI selectors', 'microthemer'),
											'type' => 'combo'
										),
										'declarations' => array(
											'desc' => esc_html__('GUI field styles', 'microthemer'),
											'type' => 'combo'
										),
										'gui_custom' => array(
											'desc' => esc_html__('GUI code editor styles', 'microthemer'),
											'type' => 'combo'
										),
										'remote_images' => array(
											'desc' => esc_html__('Images to be copied', 'microthemer'),
											'type' => 'combo'
										),
										'full_custom' => array(
											'desc' => esc_html__('CSS code that must be added to the full code editor', 'microthemer'),
											'type' => 'ace'
										),
									);

									//
									foreach ($stats as $key => $arr){

										if ($arr['type'] == 'ace'){
											?>
											<div class="ace-stats-wrap">
												<p><?php echo $arr['desc'] ?></p>
												<div class="css-code-wrap">
													<textarea name="stats_<?php echo $key; ?>" class="dont-serialize"></textarea>
													<pre id="stats-<?php echo $key; ?>" class="stats-<?php echo $key; ?>"
														 data-mode="css"></pre>
												</div>
											</div>
											<?php
										} else {
											?>
											<div id="stats-<?php echo $key; ?>" class="stats-wrap">

												<label class="stat-label"><?php echo $arr['desc'] ?> <span class="stat-count"></span></label>
												<div class="tvr-input-wrap">

													<input type="text" name="stats_<?php echo $key; ?>" rel="<?php echo $key; ?>"
													 class="combobox has-arrows stats-<?php echo $key; ?>"
													title="<?php echo $arr['desc'] ?>" />
													<span class="combo-arrow"></span>

												</div>
											</div>

											<?php
										}
									}
									?>
								</div>
							</div>

							<?php

							//echo '<pre>'.print_r($this->preferences, true). '</pre>';

						}
						?>
						</div>
					<?php
					}
					?>


				</div>
				<?php echo $this->end_dialog(esc_html_x('Import', 'verb', 'microthemer'), 'span', 'ui-import'); ?>
			</form>



			<!-- Export dialog -->
			<form method="post" id="microthemer_ui_settings_export" action="#" autocomplete="off">
			<?php echo $this->start_dialog('export-to-pack', esc_html__('Export your work as a design pack', 'microthemer'), 'small-dialog'); ?>

			<div class="content-main export-form">
				<input type='hidden' id='only_export_selected' name='only_export_selected' value='1' />
				<input type='hidden' id='export_to_pack' name='export_to_pack' value='0' />
				<input type='hidden' id='new_pack' name='new_pack' value='0' />

				<p class="enter-name-explain"><?php esc_html_e('Enter a new name or export to an existing design pack. Uncheck any folders or custom CSS you don\'t want included in the export.', 'microthemer'); ?></p>
				<p class="combobox-wrap tvr-input-wrap">
					<input type="text" class="combobox has-arrows" id="export_pack_name" name="export_pack_name" rel="directories"
						value="<?php //echo $this->readable_name($this->preferences['theme_in_focus']); ?>" autocomplete="off" />
					<span class="combo-arrow"></span>

				</p>


				<div class="heading"><?php esc_html_e('Folders', 'microthemer'); ?></div>
				<ul id="toggle-checked-folders" class="checkboxes">
					<li><input type="checkbox" name="toggle_checked_folders" />
						<span class="fake-checkbox toggle-checked-folders"></span>
						<span class="ef-label check-all-label"><?php esc_html_e('Check All', 'microthemer'); ?></span>
					</li>
				</ul>
				<ul id="available-folders" class="checkboxes"></ul>

				<div class="heading"><?php esc_html_e('Custom CSS', 'microthemer'); ?></div>
				<ul id="custom-css" class="checkboxes">
					<?php
					foreach ($this->custom_code_flat as $key => $arr) {
						$name = ($key == 'hand_coded_css' or $key == 'js') ?
							'export_sections' : 'export_sections[ie_css]';
						?>
						<li>
							<input type="checkbox" name="<?php echo $name; ?>[<?php echo $key; ?>]" />
							<span class="fake-checkbox custom-css-<?php echo $arr['tab-key']; ?>"></span>
							<span class="code-icon tvr-icon"></span>
							<span class="ef-label"><?php echo $arr['label']; ?></span>
						</li>
						<?php
					}
					?>
				</ul>
				<?php /*
				<p class="button-wrap"><?php echo $this->dialog_button('Export', 'span', 'export-dialog-button'); ?></p>
 */ ?>

				<div class="explain">
					<div class="heading link explain-link"><?php esc_html_e('About this feature', 'microthemer'); ?></div>

					<div class="full-about">
						<p><?php echo wp_kses(
							sprintf(
								__('Microthemer gives you the flexibility to export your current work to a design pack for later use (you can <span %1$s>import</span> it back). Microthemer will create a directory on your server in %2$s which will be used to store your settings and background images. Your folders, selectors, and hand-coded css settings are saved to a configuration file in this directory called config.json.', 'microthemer'),
								'class="link show-dialog" rel="import-from-pack"',
								'<code>/wp-content/micro-themes/</code>'
								),
							array( 'span' => array() )
						); ?></p>
						<p><b><?php esc_html_e('You may want to make use of this feature for the following reasons:', 'microthemer'); ?></b></p>
						<ul>
							<li><?php printf(
								esc_html__('To make extra sure that your work is backed up (even though there is an automatic revision restore feature). After exporting your work to a design pack you can also download it as a zip package for extra reassurance. You can do this from the "%s" window.', 'microthemer'),
								'<span class="link show-dialog" rel="manage-design-packs">' . esc_html__('Manage Design Packs', 'microthemer') . '</span>'
							); ?></li>
							<li><?php esc_html_e('To save your current work but then start a fresh (using the "reset" option in the left-hand menu)', 'microthemer'); ?></li>
							<li><?php esc_html_e('To save one aspect of your design for reuse in other projects (e.g. styling for a menu). You can do this by organising the styles you plan to reuse into a folder and then export only that folder to a design pack by unchecking the other folders before clicking the "Export" button.', 'microthemer'); ?></li>
							<li><?php printf(
								esc_html__('To submit a design pack for sale or free download on %s', 'microthemer'),
								'<a target="_blank" href="http://themeover.com/">themeover.com</a>'
							); ?></li>
						</ul>
					</div>

				</div>

			</div>
			<?php echo $this->end_dialog(esc_html_x('Export', 'verb', 'microthemer'),
				'span', 'export-dialog-button', esc_attr__('Export settings')); ?>
			</form>


			<!-- View CSS -->
			<?php
			// get user config for scss/draft/minify
			$input_ext = $this->preferences['allow_scss'] ? 'scss': 'css';
			$input_file_stub = $this->preferences['draft_mode'] ? 'draft' : 'active';
			$min_stub = $this->preferences['minify_css'] ? 'min.': '';

			// all possible code view tabs (orig way wasn'ty easily maintainable)
			$jsf = $input_file_stub.'-scripts.js';
			$all_pos_tabs = array(
				'scss' => array(
					'do' => $this->preferences['allow_scss'],
					'ext' => 'scss',
					'file' => $input_file_stub.'-styles.scss'
				),
				'css' => array(
					'do' => 1,
					'ext' => 'css',
					'file' => $input_file_stub.'-styles.css'
				),
				'css_min' => array(
					'do' => $this->preferences['minify_css'],
					'ext' => 'css',
					'file' => 'min.'.$input_file_stub.'-styles.css',
					'minified' => 1
				),
				'js' => array(
					'do' => 1,
					'ext' => 'js',
					'file' => $jsf,
					'file_exists' => file_exists($this->micro_root_dir . $jsf)
				),
				'js_min'=> array(
					'do' => !empty($this->preferences['minify_js']),
					'ext' => 'js',
					'file' => 'min'.$jsf,
					'file_exists' => file_exists($this->micro_root_dir . 'min'.$jsf),
					'minified' => 1
				),

				// client-side SCSS needs to show code used in optimised compile
                // sometimes this will be the contents of an import file
				'scss_compile'=> array(
					'do' => $this->client_scss(), // this will be hidden with CSS when not needed
					'ext' => 'scss',
					'file' => 'stdin',
				),
				'other' => array(
					'do' => 0 // for showing external js files and ie if errors arise
				),
			);

			// set up tabs (new)
			$tabs = array();
			foreach ($all_pos_tabs as $key => $arr){
				if ($arr['do']){
					$name = strtoupper($arr['ext']);

					if ($key == 'scss_compile'){
						$name = 'Previous SCSS Compile';
                    }

					$name.= !empty($arr['minified']) ? ' ('.esc_html__('Min', 'microthemer').')' : '';
					$tabs[] = $name;
				}
			}

			// begin dialog
			echo $this->start_dialog('display-css-code', esc_html__('View the CSS code Microthemer generates', 'microthemer'), 'medium-dialog', $tabs); ?>

			<div class="content-main dialog-tab-fields">

				<div id="view-css-areas">
					<?php
					$i = -1;
					foreach ($all_pos_tabs as $k => $arr){
						if (!$arr['do']) continue;
						++$i;
						$show = (!empty($this->preferences['generated_css_focus']) && $i === $this->preferences['generated_css_focus']) ||
						        (empty($this->preferences['generated_css_focus']) && $i === 0)
                            ? 'show'
                            : '';

						?>
						<div class="dialog-tab-field dialog-tab-field-<?php echo $i; ?> tab-field-<?php echo $k; ?> hidden <?php echo $show; ?>" rel="<?php echo $i; ?>">


                            <div class="view-file">

	                            <?php
	                            if ($k === 'scss_compile'){
		                            ?>
                                    <div id="scss-error-notes">
                                        <p><b>NOTE: </b> for maximum performance Microthemer selectively
                                            compiles SCSS code (previous compile shown below).
                                            To compile everything use <b>Ctrl + Alt + P</b></p>
                                    </div>
                                    <div id="scss-import-error-notes" class="hidden">
                                        <p>The SCSS error occurred in an @import file: <b><span id="scss-error-file">file here</span></b> (please fix this error and then refresh Microthemer)</p>
                                    </div>
		                            <?php
	                            }

	                            else {
		                            ?>
                                    <a href="<?php echo $this->micro_root_url . $arr['file']; ?>" target="_blank">
			                            <?php echo $arr['file']; ?></a>
                                    <span>(<?php echo esc_html_e('not editable here', 'microthemer'); ?>)</span>
		                            <?php
	                            }
	                            ?>

                            </div>



							<div class="css-code-wrap">
								<textarea class="gen-css-holder dont-serialize"></textarea>
								<?php
								$min_class = !empty($arr['minified']) ? 'min' : '';
								$mode = $arr['ext'] != 'js' ? $arr['ext'] : 'javascript';
								?>
								<pre id="generated-css-<?php echo $i; ?>"
									 class="generated-css generated-css-<?php echo $k; ?> <?php echo $min_class; ?>"
									 data-mode="<?php echo $mode; ?>"></pre>
							</div>
						</div>
						<?php
					}
					?>
				</div>

				<?php /*
                <div class="explain">
					<div class="heading link explain-link"><?php esc_html_e('About this feature', 'microthemer'); ?></div>

					<div class="full-about">
						<p><?php esc_html_e('What you see above is the CSS code Microthemer is currently generating. This can sometimes be useful for debugging issues if you know CSS. Or if you want to reuse the code Microthemer generates elsewhere.', 'microthemer'); ?></p>
						<p><?php echo wp_kses(
							sprintf(
								__('<b>Did you know</b> - it\'s possible to disable or completely uninstall Microthemer and still use the customisations. You just need to paste a small piece of code in your theme\'s functions.php file. See this <a %s>forum post</a> for further information.', 'microthemer'),
								'target="_blank" href="http://themeover.com/forum/topic/microthemer-customizations-when-deactived/"'
							),
							array( 'a' => array( 'href' => array(), 'target' => array() ), 'b' => array() )
						); ?></p>
						<p><?php echo wp_kses(
							sprintf(
								__('<b>Also note</b>, Microthemer adds the "!important" declaration to all CSS styles by default. If you\'re up to speed on %1$s you may want to disable this behaviour on the <span %2$s>preferences page</span>. If so, you will still be able to apply "!important" declarations on a per style basis by clicking the faint "i"s that will appear to the right of all style option fields.', 'microthemer'),
								'<a target="_blank" href="http://themeover.com/beginners-guide-to-understanding-css-specificity/">' . esc_html__('CSS specificity', 'microthemer') . '</a>',
								'class="link show-dialog" rel="display-preferences"'
							),
							array( 'b' => array(), 'span' => array() )
						); ?></p>
					</div>

				</div>
                */
                ?>
			</div>
			<?php echo $this->end_dialog(esc_html_x('Close', 'verb', 'microthemer'), 'span', 'close-dialog'); ?>

			<!-- Restore Settings -->
			<?php echo $this->start_dialog('display-revisions', esc_html__('Restore settings from a previous save point', 'microthemer'), 'small-dialog'); ?>

			<div class="content-main">
				<div id='revisions'>
					<div id='revision-area'></div>
				</div>
				<span id="view-revisions-trigger" rel="display-revisions"></span>
				<div class="explain">
				<div class="heading link explain-link"><?php esc_html_e('About this feature', 'microthemer'); ?></div>
					<div class="full-about">
					<p><?php esc_html_e('Click the "restore" link in the right hand column of the table to restore your workspace settings to a previous save point.', 'microthemer'); ?></p>
					</div>
				</div>
			</div>
			<?php echo $this->end_dialog(esc_html_x('Close', 'verb', 'microthemer'), 'span', 'close-dialog'); ?>

			<!-- Spread the word -->
			<?php echo $this->start_dialog('display-share', esc_html__('Show off your new discovery', 'microthemer'), 'small-dialog'); ?>
			<div class="content-main">
				<div class="explain">
					<div class="heading link explain-link"><?php esc_html_e('About this feature', 'microthemer'); ?></div>
					<div class="full-about">
						<p><?php esc_html_e('cash back feature - coupon code to give new customer, affiliate commission for existing', 'microthemer'); ?></p>
						<p><?php esc_html_e('For now, just a simply share widget.', 'microthemer'); ?></p>
					</div>
				</div>
			</div>
			<?php echo $this->end_dialog(esc_html_x('Close', 'verb', 'microthemer'), 'span', 'close-dialog'); ?>

		</div>

		<!-- Manage Design Packs -->
		<?php echo $this->start_dialog('manage-design-packs', esc_html__('Install & Manage Design Packs', 'microthemer')); ?>
		<iframe id="manage_iframe" class="microthemer-iframe" frameborder="0" name="manage_iframe"
				rel="<?php echo 'admin.php?page='.$this->microthemespage; ?>"
				src="<?php echo $this->thispluginurl; ?>includes/place-holder2.html"
				data-frame-loaded="0"></iframe>
		<?php echo $this->end_dialog(esc_html_x('Close', 'verb', 'microthemer'), 'span', 'close-dialog'); ?>

        <!-- Google Fonts -->
		<?php echo $this->start_dialog('google-fonts', esc_html__('Google Fonts Library', 'microthemer')); ?>
        <iframe id="google_fonts_iframe" class="microthemer-iframe" frameborder="0" name="google_fonts_iframe"
                rel="<?php echo 'admin.php?page='.$this->fontspage; ?>"
                src="<?php echo $this->thispluginurl; ?>includes/place-holder2.html"
                data-frame-loaded="0"></iframe>
		<?php echo $this->end_dialog(esc_html_x('Close', 'verb', 'microthemer'), 'span', 'close-dialog'); ?>

		<!-- Program Docs -->
		<?php echo $this->start_dialog('program-docs', esc_html__('Help Centre', 'microthemer')); ?>
		<iframe id="docs_iframe" class="microthemer-iframe" frameborder="0" name="docs_iframe"
				rel="<?php echo 'admin.php?page=' . $this->docspage; ?>"
				src="<?php echo $this->thispluginurl; ?>includes/place-holder2.html"
				data-frame-loaded="0"></iframe>
		<?php echo $this->end_dialog(esc_html_x('Close', 'verb', 'microthemer'), 'span', 'close-dialog'); ?>

		<!-- Integration -->
		<?php
		echo $this->start_dialog('integration', esc_html__('Integration with 3rd party software', 'microthemer'), 'small-dialog');
		?>
		<div class="content-main">
			<div class="heading"><?php esc_html_e('WPTouch Mobile Plugin', 'microthemer'); ?></div>
			<p><?php /*echo wp_kses(
				sprintf(
					__('Microthemer can be used to style the mobile-only theme that WPTouch presents to mobile devices. In order to load the mobile theme in Microthemer\'s preview window, simply enable WPTouch mode using the toggle in the left toolbar. This toggle will only appear if Microthemer detects that you have installed and activated WPTouch. There is a <a %1$s>free</a> and <a %2$s>premium version</a> of WPTouch.', 'microthemer'),
					'target="_blank" href="<?php echo $this->wp_blog_admin_url; ?>plugin-install.php?tab=search&type=term&s=wptouch+mobile+plugin"',
					'target="_blank" href="http://www.wptouch.com/"'
				),
					array( 'a' => array( 'href' => array(), 'target' => array() ) )
				);
 */?></p>
			<div class="explain">
			<div class="heading link explain-link"><?php esc_html_e('About this feature', 'microthemer'); ?></div>
				<div class="full-about">
					<p><?php esc_html_e('When possible, we\'ll add little features to make it easier to use Microthemer with complementary products.', 'microthemer'); ?></p>
				</div>
			</div>
		</div>
		<?php echo $this->end_dialog(esc_html_x('Close', 'verb', 'microthemer'), 'span', 'close-dialog'); ?>
		<?php
	}
	?>



	<!-- error report form -->
	<form id="error-report-form" name="error_report" method="post">
		<textarea name="tvr_php_error"></textarea>
		<textarea name="tvr_serialised_data"></textarea>
		<textarea name="tvr_browser_info"></textarea>
	</form>

	<!-- color picker mini and large palettes -->
	<div id="mt-picker-palette">
		<ul class="mt-picker-palette palette-list">
		<li class="view-full-palette">

			<?php
			echo $this->ui_toggle(
				'full_color_palette',
				esc_attr__('More colors', 'microthemer'),
				esc_attr__('Less colors', 'microthemer'),
				false, // never on initially
				'full-palette-toggle',
				false,
				array(
					'text' => '...',
					'dataAtts' => array(
						'no-save' => 1
					)
				)
			)
			?>

			<div class="full-palette-popup">
				<?php
				$palettes = array(
					// not using this right now
					'recent' => array(
						'title' => esc_html__('Recent colors', 'microthemer'),
						'icons_buttons' => array(
							'clear-icon tvr-icon' => array(
								'title' => esc_html__('Clear recent colors', 'microthemer')
							),
						)
					),
					'saved' => array(
						'title' => esc_html__('Saved colors', 'microthemer'),
						'icons_buttons' => array(
							'palette-button mt-save-color show' => array(
								'text' => esc_html__('Save', 'microthemer'),
								'title' => esc_html__('Add to saved colors', 'microthemer')
							),
							'palette-button tvr-secondary mt-remove-color' => array(
								'text' => esc_html__('Remove', 'microthemer'),
								'title' => esc_html__('Remove from saved colors', 'microthemer')
							)
						)
					),
					'site' => array(
						'title' => esc_html__('Site colors', 'microthemer'),
						'icons_buttons' => array(
							'refresh-icon' => array(
								'title' => esc_html__('Resample colors affecting the current page', 'microthemer')
							)
						)
					)
				);
				foreach ($palettes as $key => $arr){
					?>
					<div class="<?php echo $key; ?>-colors-wrap p-colors-wrap">
						<div class="palette-heading">
							<span class="palette-heading-text"><?php echo $arr['title']; ?></span>
							<?php
							// output buttons/icons
							if (!empty($arr['icons_buttons'])){
								foreach ($arr['icons_buttons'] as $class => $array){
									$text = !empty($array['text']) ? $array['text'] : '';
									$title = !empty($array['title']) ? 'title="'.$array['title'].'"' : '';
									echo '<span class="'.$class.'" '.$title.'>'.$text.'</span>';
								}
							}
							?>
						</div>
						<ul class="palette-list full-palette <?php echo $key; ?>-full-palette"></ul>
					</div>
					<?php
				}
				?>
				<span class="tvr-icon close-icon close-palette"></span>

			</div>
		</li>
	</ul>
	</div>

	<!-- html templates -->
	<form action='#' name='dummy' id="html-templates">
		<?php
		if (!$this->optimisation_test){

			// warning template
			?>
			<!--<div id="notice-template" class="tvr-message tvr-template-notice tvr-warning">
				<span class="mt-notice-icon"></span>
				<span class="mt-notice-text"></span>
			</div>-->
			<?php

			// ajax loaders
			//$this->hidden_ajax_loaders();

			// template for displaying save error and error report option
			/*$short = __('Error saving settings', 'microthemer');
			$long =
				'<p>' . sprintf(
					esc_html__('Please %s. The error report sends us information about your current Microthemer settings, server and browser information, and your WP admin email address. We use this information purely for replicating your issue and then contacting you with a solution.', 'microthemer'),
					'<span id="email-error" class="link">' . __('click this link to email an error report to Themeover', 'microthemer') . '</span>'
				) . '</p>
				<p>' . wp_kses(
					__('<b>Note:</b> reloading the page is normally a quick fix for now. However, unsaved changes will need to be redone.', 'microthemer'),
					array( 'b' => array() )
				). '</p>';
			echo $this->display_log_item('error', array('short'=> $short, 'long'=> $long), 0, 'id="log-item-template"');*/

			// dynamic menu items
			//echo $this->dyn_item($this->enq_js_structure, 'item', array('display_name' => 'item')); // enq_js
			//echo $this->dyn_item($this->mq_structure, 'item', array('label' => 'item')); // mq
			// mqs
			// custom code

            // define template for menu section
			//echo $this->menu_section_html('selector_section', 'section_label');

			// define template for menu selector
			//echo $this->menu_selector_html('selector_section', 'selector_css', array('selector_code', 'selector_label'), 1);

			// define template for section
			//echo $this->section_html('selector_section', array());

			// define template for selector
			//echo $this->single_selector_html('selector_section', 'selector_css', '', true);

            // define property group templates
			/*foreach ($this->propertyoptions as $property_group_name => $property_group_array) {

			    // we want root keys only for $property_group_array, to match propertyOptions format
			    $array_keys = array_keys($property_group_array);
			    $property_group_array_root = array();
			    foreach($array_keys as $prop_slug){
				    $property_group_array_root[$prop_slug] = '';
                }

			    echo $this->single_option_fields(
					'selector_section',
					'selector_css',
					array(),
				    $property_group_array_root,
					$property_group_name,
					'',
					true);
			}*/
		}
		?>

	</form>
	<!-- end html templates -->


</div><!-- end #tvr -->
<?php
// output current settings to file (before any save), also useful for output custom debug stuff
if ($this->debug_current){
	$debug_file = $this->micro_root_dir . $this->preferences['theme_in_focus'] . '/debug-current.txt';
	$write_file = @fopen($debug_file, 'w');
	$data = '';
	$data.= esc_html__('Custom debug output', 'microthemer') . "\n\n";
	//$data.= $this->debug_custom;
	//$data.= print_r($this->debug_custom, true);
	$data.= "\n\n" . esc_html__('The existing options', 'microthemer') . "\n\n";
	$data.= print_r($this->options, true);
	fwrite($write_file, $data);
	fclose($write_file);
}
