<?php
// Include minify script here as 'use' can't be used in function
$path = dirname(__FILE__);
require_once $path . '/minify/src/Minify.php';
require_once $path . '/minify/src/CSS.php';
require_once $path . '/minify/src/JS.php';
require_once $path . '/minify/src/Exception.php';
require_once $path . '/path-converter/src/Converter.php';
use MatthiasMullie\Minify;
$minifier = new Minify\CSS($sty['data']);
$css_files[] = array(
	'name' => 'min.'.$targetFile,
	'data' => $minifier->minify()
);

