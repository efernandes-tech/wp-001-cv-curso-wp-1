<?php

// get the CSS from animate.css
$css = file_get_contents($this->thisplugindir . '/includes/animation/animate.css');

// Minify the css loading dynamically (for purpose of MT instant preview when scrolling through animations)
$path = dirname(__FILE__) . '/min-css-js';
require_once $path . '/minify/src/Minify.php';
require_once $path . '/minify/src/CSS.php';
require_once $path . '/minify/src/JS.php';
require_once $path . '/minify/src/Exception.php';
require_once $path . '/path-converter/src/Converter.php';
use MatthiasMullie\Minify;
$minifier = new Minify\CSS($css);
$this->write_file($this->thisplugindir . 'css/animate.min.css', $minifier->minify());

// pattern to get animation name and keyframe code
$pattern = '/(@-webkit-keyframes\s([^{]+)\s.+)\.\2\s/s';

// get data into an array
preg_match_all(
	$pattern,
	$css,
	$matches,
	PREG_PATTERN_ORDER
);

// create custom array from animation data
$animations = array();
foreach ($matches[2] as $i => $name){

	$animations[$name] = array(
		//'group' => '', // maybe later
		'code' => $matches[1][$i]
	);

}

// animation names with categories
$animation_categories = array(

	__('Attention Seekers', 'microthemer') => array(
		'bounce', 'flash', 'pulse', 'rubberBand', 'shake', 'swing', 'tada', 'wobble', 'jello',
	),

	__('Bouncing Entrances', 'microthemer') => array(
	'bounceIn', 'bounceInDown', 'bounceInLeft', 'bounceInRight', 'bounceInUp',
	),

	__('Bouncing Exits', 'microthemer') => array(
		'bounceOut', 'bounceOutDown', 'bounceOutLeft', 'bounceOutRight', 'bounceOutUp'
	),

	__('Fading Entrances', 'microthemer') => array(
		'fadeIn', 'fadeInDown', 'fadeInDownBig', 'fadeInLeft', 'fadeInLeftBig', 'fadeInRight',
		'fadeInRightBig', 'fadeInUp', 'fadeInUpBig'
	),

	__('Fading Exits', 'microthemer') => array(
		'fadeOut', 'fadeOutDown', 'fadeOutDownBig', 'fadeOutLeft', 'fadeOutLeftBig', 'fadeOutRight',
	'fadeOutRightBig', 'fadeOutUp', 'fadeOutUpBig'
	),

	__('Flippers', 'microthemer') => array(
		'flip', 'flipInX', 'flipInY', 'flipOutX', 'flipOutY'
	),

	__('Lightspeed', 'microthemer') => array(
		'lightSpeedIn', 'lightSpeedOut'
	),

	__('Rotating Entrances', 'microthemer') => array(
		'rotateIn', 'rotateInDownLeft', 'rotateInDownRight', 'rotateInUpLeft', 'rotateInUpRight',
	),

	__('Rotating Exits', 'microthemer') => array(
		'rotateOut', 'rotateOutDownLeft', 'rotateOutDownRight', 'rotateOutUpLeft', 'rotateOutUpRight',
	),

	__('Sliding Entrances', 'microthemer') => array(
		'slideInUp', 'slideInDown', 'slideInLeft', 'slideInRight',
	),

	__('Sliding Exits', 'microthemer') => array(
		'slideOutUp', 'slideOutDown', 'slideOutLeft', 'slideOutRight',
	),

	__('Zoom Entrances', 'microthemer') => array(
		'zoomIn', 'zoomInDown', 'zoomInLeft', 'zoomInRight', 'zoomInUp',
	),

	__('Zoom Exits', 'microthemer') => array(
		'zoomOut', 'zoomOutDown', 'zoomOutLeft', 'zoomOutRight', 'zoomOutUp',
	),

	__('Specials', 'microthemer') => array(
		'hinge', 'jackInTheBox', 'rollIn', 'rollOut',
	),
);


// format array as valid PHP code (string)
$animation_data = '<?php $animations = ' . var_export($animations, true) . '; ' . "\n\n";

// include category-based list if animations
$animation_data.= ' $animation_names = ' . var_export($this->to_autocomplete_arr($animation_categories), true) . ';';

// write to php file
$write_file = fopen($this->thisplugindir . '/includes/animation/animation-code.inc.php', 'w');

// if write is unsuccessful for some reason
if (false === fwrite($write_file, $animation_data)) {
	echo 'Animation write error';
}

fclose($write_file);
