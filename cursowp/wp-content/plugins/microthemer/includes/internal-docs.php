<?php

// Stop direct call
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('Please do not call this page directly.');
}

// get property options
include 'property-options.inc.php';
?>

<div id="tvr" class='wrap tvr-wrap tvr-docs'>
	<div id='tvr-docs'>

		<!-- Google Search -->
		<div id="g-search">
			<div class="support-header-wrap">
				<ul id="external-links">
					<li class="doc-item external">
						<a target="_blank" class="video" href="<?php echo $this->demo_video; ?>">Demo<br />Video</a>
					</li>
					<li class="doc-item external">
						<a target="_blank" href="http://themeover.com/support/">Online<br />Docs</a>
					</li>
					<li class="doc-item external">
						<a target="_blank" href="http://themeover.com/forum/">Support<br />Forum</a>
					</li>
				</ul>
				<script>
					(function() {
						var cx = '000071969491634751241:fbvdr_lfrxg';
						var gcse = document.createElement('script');
						gcse.type = 'text/javascript';
						gcse.async = true;
						gcse.src = (document.location.protocol == 'https:' ? 'https:' : 'http:') +
						'//www.google.com/cse/cse.js?cx=' + cx;
						// when
						gcse.onreadystatechange = gcse.onload = function() {

						};
						var s = document.getElementsByTagName('script')[0];
						s.parentNode.insertBefore(gcse, s);
					})();
				</script>
				<gcse:searchbox enableAutoComplete="true"></gcse:searchbox>
			</div>
		</div>

		<div id="docs-container">

			<div id="docs-content">

				<div id="g-results">
					<gcse:searchresults linkTarget="_blank"></gcse:searchresults>
				</div>

				<?php

				$prop_group = $property = false;

				// support index page
				if (isset($_GET['docs_index'])) {
					?>
					<div id="docs-index" class='ref-details docs-box'>
						<h2>Getting Started Tips</h2>

						<h4>Basic Usage</h4>

						<ol class="basic-usage">
							<li>Click the <span class="docs-target-button">Target</span> button.</li>
							<li>Click anything on the page that you want to style.</li>
							<li>Use the <span class="docs-plus-button">+</span> option or click the <span class="docs-create-button tvr-button">CREATE SELECTOR</span> button.</li>
							<li>Apply new styles using the toolbar (colors, fonts, spacing etc).</li>
							<li>(Optional) Under the <b>General</b> menu, you can enable <i>Draft mode</i>. Only you can see the changes you make in Microthemer when <i>Draft mode</i> is enabled. When your style changes are ready to go live, turn off <i>Draft mode</i> to publish them.</li>
						</ol>

						<h4>Getting started video</h4>

						<p>If you're totally new to Microthemer, we strongly recommend watching this getting started video, which demonstrates how to use the program:</p>

						<a target="_blank" href="<?php echo $this->demo_video; ?>">
							<img class="img-to-video" src="<?php echo $this->thispluginurl; ?>images/video-thumbnail.jpg?v=2" />
						</a>

                        <h4>Targeting video</h4>

                        <p>The targeting video is also highly recommended if you want to leverage the true power of Microthemer's targeting options.</p>

                        <a target="_blank" href="<?php echo $this->targeting_video; ?>">
                            <img class="img-to-video" src="<?php echo $this->thispluginurl; ?>images/targeting-video-thumbnail.gif?v=2" />
                        </a>

						<h4>Keyboard Shortcuts</h4>

						<?php
						// keyboard shortcuts
						$shortcuts = array(
							array(
								'action' => 'Toggle page builder (Elementor, Beaver Builder, or Oxygen)',
								'win' => 'Ctrl+Alt+B',
								'mac' => 'Command+Alt+B',
							),
							array(
								'action' => 'Toggle code view mode',
								'win' => 'Ctrl+Alt+C',
								'mac' => 'Command+Alt+C',
							),
							array(
								'action' => 'Detach site preview in separate window',
								'win' => 'Ctrl+Alt+D',
								'mac' => 'Command+Alt+D',
							),
							array(
								'win' => 'Ctrl+Alt+G',
								'mac' => 'Command+Alt+G',
								'action' => 'View the CSS Microthemer generates'
							),
							array(
								'win' => 'Ctrl+Alt+H',
								'mac' => 'Command+Alt+H',
								'action' => 'Highlight the current selector (press and hold)'
							),
							array(
								'action' => 'Jump between code editor and UI property field',
								'win' => 'Ctrl+Alt+J',
								'mac' => 'Command+Alt+J',
							),
							array(
								'action' => 'Toggle CSS property text labels',
								'win' => 'Ctrl+Alt+L',
								'mac' => 'Command+Alt+L',
							),
							array(
								'action' => 'Toggle page navigator menu',
								'win' => 'Ctrl+Alt+N',
								'mac' => 'Command+Alt+N',
							),
							array(
								'action' => 'If Sass is enabled, recompiles all MT selectors',
								'win' => 'Ctrl+Alt+P',
								'mac' => 'Command+Alt+P',
							),
							array(
								'win' => 'Ctrl+S',
								'mac' => 'Command+S',
								'action' => 'Save settings. This is only needed when typing code in the JavaScript code editor. GUI settings and CSS code edits auto-save.'
							),
							array(
								'action' => 'Toggle targeting mode',
								'win' => 'Ctrl+Alt+T',
								'mac' => 'Command+Alt+T',
							),
							array(
								'win' => 'Ctrl+Alt+,',
								'mac' => 'Command+Alt+, (comma)',
								'action' => 'Navigate to previous selector'
							),
							array(
								'win' => 'Ctrl+Alt+.',
								'mac' => 'Command+Alt+. (full stop)',
								'action' => 'Navigate to next selector'
							),

						)
						?>

						<table class="prop-vals">
							<thead>
							<tr class="heading">
								<th class="value">Windows / Mac</th>
								<!--<th>Mac</th>-->
								<th>Action</th>
							</tr>
							</thead>
							<tbody>

							<?php
							foreach ($shortcuts as $ks_key => $ks_arr){
								$tr_class = $ks_key&1 ? 'odd' : 'even';
								?>
								<tr class="<?php //echo $tr_class; ?>">
									<td><?php echo $ks_arr['win']; ?></td>
									<!--<td><?php /*echo $ks_arr['mac']; */?></td>-->
									<td><?php echo $ks_arr['action']; ?></td>
								</tr>
								<?php
							}
							?>

							</tbody>
						</table>


						<h4>Understand CSS & Responsive Design</h4>

						<p>If you're interested in learning a bit of HTML, CSS, and responsive design we recommend the following <a target="_blank" href="http://themeover.com/html-css-responsive-design-wordpress-microthemer/">zero to hero responsive tutorial</a>. It contains a lot of information, which may be intimidating at first. But it's split into sections, which you can work through slowly. It teaches everything we've noticed novices struggle with during our 5 years of providing forum support. Reading it has the potential to save you time and frustration, and empower you to do more.</p>

						<h4>CSS Reference</h4>

						<p>When you want to find out what a particular style option (CSS Property) does, the CSS reference
							on the left provides a brief description of each property and links to relevant online references
							and tutorials.</p>

						<h4>Friendly Support Forum</h4>

						<p>Finally, if you get stuck, someone is waiting to help out in our <a target="_blank" href="http://themeover.com/forum/">friendly support forum</a>.</p>

					</div>

					<?php
				}

				// CSS Reference
				if (isset($_GET['prop_group']) or isset($_GET['prop'])) {

					// single property
					$prop_group = htmlentities($_GET['prop_group']);
					$property = htmlentities($_GET['prop']);
					$pg_arr = $propertyOptions[$prop_group];
					$p_arr = $propertyOptions[$prop_group][$property];
					?>
					<div id='<?php echo $property; ?>' class='ref-details docs-box'>
						<h3 class="main-title">
							<span class="<?php if (!empty($p_arr['field-class'])) { echo $p_arr['field-class']; }?>">
								<span class="option-icon-<?php echo $property; ?> option-icon no-click"></span>
							</span>
							<span class="t-text">
								<?php echo $p_arr['label']; ?>
							</span>

						</h3>
						<div class="inner-box">
							<p><?php echo $p_arr['ref_desc']; ?></p>
							<?php
							if (!empty($p_arr['ref_values']) and is_array($p_arr['ref_values'])){
								?>
								<table class="prop-vals">
									<thead>
									<tr class='heading'>
										<th class="value">Value</th>
										<th>Description</th>
									</tr>
									</thead>
									<tbody>
									<?php
									$i = 1;
									foreach ($p_arr['ref_values'] as $value => $desc) {
										?>
										<tr <?php if ($i&1) { echo 'class="odd"'; } ?>>
											<td class="value"><?php echo $value; ?></td><td><?php echo $desc; ?></td>
										</tr>
										<?php
										++$i;
									}
									?>
									</tbody>
								</table>
								<?php
							}
							?>


						</div>
					</div>
					<div id="tutorials-refs" class="docs-box">

						<?php
						$ref_map = array(
							'can_i_use' => array(
								'name' => 'Can I Use',
								'icon' => ''
							),
							'css_tricks' => array(
								'name' => 'CSS Tricks',
								'icon' => ''
							),
							'mozilla' => array(
								'name' => 'Mozilla',
								'icon' => ''
							),
							'quackit' => array(
								'name' => 'Quackit',
								'icon' => ''
							),
							'w3s' => array(
								'name' => 'W3Schools',
								'icon' => ''
							)
						);
						// css ref
						if (!empty($p_arr['ref_links']) and is_array($p_arr['ref_links'])){
							echo '<h3>'.$p_arr['label'].' References</h3>
							<ul>';
							foreach ($p_arr['ref_links'] as $key => $url){
								echo '
								<li>
									<a target="_blank" href="'.$url.'">'
										.$ref_map[$key]['name'].': ' . str_replace('_', '-', $property ). '
									</a>
								</li>';
							}
							echo '</ul>';
						}
						// tutorials
						if (!empty($p_arr['tutorials']) and is_array($p_arr['tutorials'])){
							// are the tutorials specific to the property or the property group?
							if (!empty($p_arr['group_tutorials'])){
								$scope = $this->property_option_groups[$prop_group];
								//$scope = $p_arr['pg_label'];
							} elseif (!empty($p_arr['subgroup_tutorials'])){
								$keys = array_keys($pg_arr);
								$scope = $pg_arr[$keys[0]]['sub_label'];
							} elseif (!empty($p_arr['related_tutorials'])){
								$scope = $p_arr['related_tutorials'];
							} else {
								$scope = $p_arr['label'];
							}
							echo '<h3>'.$scope.' Tutorials</h3>
							<ul>';
							foreach ($p_arr['tutorials'] as $key => $arr){
								echo '
								<li>
									<a target="_blank" href="'.$arr['url'].'">'
									.$arr['title'].'
									</a>
								</li>';
							}
							echo '</ul>';
						}
						?>
					</div>
				<?php

				}
				?>

			</div><!-- end content -->
		</div><!-- end container -->

		<?php

		// side menu
		$this->docs_menu($propertyOptions, $prop_group, $property);

		?>



	</div><!-- end tvr-docs -->
</div><!-- end wrap -->

