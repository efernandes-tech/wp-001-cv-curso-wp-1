<?php
	//css helper for option icons (dev tool)

	$scss_data = '/*--------------------------------------------------------------
Options Icons
--------------------------------------------------------------*/

/** The following option icon CSS is programatically generated at the top of tvr-microthemer-ui.php
 After refreshing the ui, phpStorm then needs to update styles.css
 **/
';
	$prev_key = '';
	// set css corrections for icon-width here so CSS can be copied and pasted without updating styles.scss separately
	$extra_scss = array(
		'icon-size-0b' => array(
			'x_adjust' => .4,
			'props' => 'margin-left:-6px;'
		),
		'icon-size-0a' => array(
			'x_adjust' => .4,
			'props' => 'margin-left:-4px;'
		),
		'icon-size-2' => array(
			'x_adjust' => .25,
			'props' => 'width:30px !important;'
		),
		'icon-size-3' => array(
			'x_adjust' => .2,
			'props' => 'width:32px !important;'
		),
		'icon-size-4' => array(
			'x_adjust' => .15,
			'props' => 'width:36px !important;'
		)
	);
	foreach ($this->propertyoptions as $key => $arr){
		foreach ($arr as $prop => $value) {
			if (empty($value['icon'])) continue;
			// most icons need .4 added to x pos value to align, some need slightly different
			$x_adjust = .4;
			foreach ($extra_scss as $k => $v){
				//echo 'bobby' . $k;
				if ( !empty($value['field-class']) and strpos($value['field-class'], $k) !== false ){
					$x_adjust = $v['x_adjust'];
				}
			}

			// icon position / sprite type
			$icon_pos = explode(',', $value['icon']);

			// sprite type, which also affects adjustment
			if ( !empty($icon_pos[2]) ){
				$sprite_type = trim($icon_pos[2]);
				// sprite B is smaller so x-adjustment can be reduced proportionally
				if ($sprite_type == 'B'){
					$x_adjust = round( $x_adjust / 2, 2 );
				}
			} else {
				$sprite_type = 'A';
			}

			$x = $icon_pos[0] + $x_adjust;
			if (!empty($icon_pos[1])) {
				$y = $icon_pos[1];
			} else {
				$y = 11;
			}

			// new group
			if ($prev_key != $key) {
				$group = "// $key
";
			} else {
				$group = "";
			}
			$scss_data.= "{$group}.option-icon-{$prop} { @include state-sprite({$x}, {$y}, {$sprite_type}); }
";
			$prev_key = $key;
		}
	}
	// do width/margin adjustments
	$scss_data.= '
// width/margin adjustments
';
	foreach ($extra_scss as $k => $v) {
		$scss_data.= ".option-icon { .{$k} & { " . $v['props'] . " } }
";
	}

	// write to scss file
	$write_file = fopen($this->thisplugindir . '/css/scss/option-icons.scss', 'w');
	// if write is unsuccessful for some reason
	if (false === fwrite($write_file, $scss_data)) {
		echo 'SCSS write error';
	}
	fclose($write_file);
