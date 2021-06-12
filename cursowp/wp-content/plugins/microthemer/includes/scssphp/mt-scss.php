<?php
// Include scss script here as 'use' can't be used in function
//$path = dirname(__FILE__);

// normalise windows paths to forward slashes //
function tvr_normalize_path( $path ) {
	$path = str_replace( '\\', '/', $path );
	$path = preg_replace( '|(?<=.)/+|', '/', $path );
	if ( ':' === substr( $path, 1, 1 ) ) {
		$path = ucfirst( $path );
	}
	return $path;
}

// http://leafo.github.io/scssphp/docs/
include 'scssphp-oct-2018/scss.inc.php';
use Leafo\ScssPhp;
$scss = new Leafo\ScssPhp\Compiler();
$scss->setFormatter('Leafo\ScssPhp\Formatter\Expanded');

// set the default path start point as /wp-content/micro-themes/ so css/scss @imports work the same
//$path = tvr_normalize_path($path) ."/../../../../micro-themes/";


$path = $this->micro_root_dir;

// for debugging
//echo '$path ' . $path;
//echo 'scandir($path) <pre>' . print_r(scandir($path), true) . '</pre>';

$scss->setImportPaths($path);
