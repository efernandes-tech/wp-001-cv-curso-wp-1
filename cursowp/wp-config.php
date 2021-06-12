<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wp001cv' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost:3307' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '1=(lp@DkzjdQ57$$;dPruKZXVwjV;j1K]01UF{[(JO?nvC)x*f<:Fg`.[`#S4xo(' );
define( 'SECURE_AUTH_KEY',  '.~&@/qr)jzN)WdDtzRK`_O-L{|iLr`6}DLw@csK+H$mZtBxw{S8fYI-y;)tQ}e#+' );
define( 'LOGGED_IN_KEY',    '*rS>$4qzlB]E&ReYQi=!K,Y)Wp-Ap;wruY>CGg6N#.o0:vPRC>G)5cGY1EW`43A^' );
define( 'NONCE_KEY',        '(:7Ia8_7ybP?Xad(RHL?:C([Mh@aB{+eA1Xl0gkRn`:^}Og{}*0i#&zt*Q[%2fN#' );
define( 'AUTH_SALT',        'SnkjmQZ5oPa&#}Y)i|E:25qg/rn)>Yg>roQr<cR;x77nJZJB_yV/r>j|_@|;$DR=' );
define( 'SECURE_AUTH_SALT', 'p7>)7MO;0%JH43*LgFKO;gEwqV]Q9J.1pY6AL2=A>R;/!Nd9wW)f)pO+}hmJ9[P|' );
define( 'LOGGED_IN_SALT',   'xaq]w$3(o,?%r*0h:%-eivX~nR9J_KU$Leg>590TOUL=%{6Ru!9Qxp5[nqU,>h-R' );
define( 'NONCE_SALT',       ')q*^c<b1il=VRC;65g$KLx!/d42|-Z>Re5^e(Bp7:9bOSKW6(=#,M%3x@o387RL~' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
