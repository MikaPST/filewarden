<?php
/**
 * Plugin Name: FileWarden
 * Description: Private document management for WordPress (pre-alpha foundation).
 * Version: 0.1.9-dev
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * Author: MikaPST
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: filewarden
 * Domain Path: /languages
 *
 * @package FileWarden
 */

defined( 'ABSPATH' ) || exit;

define( 'FILEWARDEN_VERSION', '0.1.9-dev' );
define( 'FILEWARDEN_PATH', plugin_dir_path( __FILE__ ) );

spl_autoload_register(
	static function ( $class_name ) {
		$prefix = 'FileWarden\\';
		if ( str_starts_with( $class_name, $prefix ) ) {
			$name = substr( $class_name, strlen( $prefix ) );
			if ( preg_match( '/^[A-Za-z]+$/D', $name ) ) {
				$file = FILEWARDEN_PATH . 'includes/' . $name . '.php';
				if ( is_file( $file ) ) {
					require_once $file;
				}
			}
		}
	}
);

register_activation_hook( __FILE__, array( FileWarden\Installer::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( FileWarden\Plugin::class, 'deactivate' ) );
add_action( 'plugins_loaded', array( FileWarden\Plugin::class, 'boot' ) );
