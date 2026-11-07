<?php
spl_autoload_register(
	static function ( $class ) {
		$prefix = 'FileWarden\\';
		if ( str_starts_with( $class, $prefix ) ) {
			$file = dirname( __DIR__ ) . '/includes/' . substr( $class, strlen( $prefix ) ) . '.php';
			if ( is_file( $file ) ) {
				require_once $file;
			}
		}
	}
);
