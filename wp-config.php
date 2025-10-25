<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'bkgt_se_db_1' );

/** Database username */
define( 'DB_USER', 'dbaadmin@b383837' );

/** Database password */
define( 'DB_PASSWORD', 'Anna1Martin2' );

/** Database hostname */
define( 'DB_HOST', 'mysql513.loopia.se' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '8Xz!9pL$5mN&2qR*7tY@4wE#6uI^1oP' );
define( 'SECURE_AUTH_KEY',  '3sD%8fG*2hJ!5kL@7zX$9cV^4bN&1mP' );
define( 'LOGGED_IN_KEY',    '6tR@9uI!2wE$4yU^7pO*1aS#3dF%5gH' );
define( 'NONCE_KEY',        '4jK&8lZ!1xC$6vB@3nM^9qW*2eR%7tY' );
define( 'AUTH_SALT',        '5hG@2jK!8lZ$1xC^4vB&9nM*3qW%6eR' );
define( 'SECURE_AUTH_SALT', '7uY!9iO$2pA^5sD*8fG#1hJ&4kL@3zX' );
define( 'LOGGED_IN_SALT',   '1cV@6bN!3mP$8qW^2eR*9tY#4uI&7pO' );
define( 'NONCE_SALT',       '9aS$3dF!5gH@7jK^1lZ*4xC&8vB%2nM' );

/**#@-*/

/**
 * WordPress database table prefix.
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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );

/* Add any custom values between this line and the "stop editing" line. */


/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';