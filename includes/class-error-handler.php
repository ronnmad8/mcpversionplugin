<?php
/**
 * Error Handler Class
 * Maneja errores de forma segura sin romper la web
 *
 * @package JSON_Version_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase para manejo de errores
 */
class JVM_Error_Handler {

	/**
	 * Initialize error handler
	 */
	public static function init() {
		// Registrar shutdown handler para capturar errores fatales
		register_shutdown_function( array( __CLASS__, 'shutdown_handler' ) );
		
		// Prevenir que errores de nuestro plugin rompan la web
		set_error_handler( array( __CLASS__, 'error_handler' ), E_ALL );
	}

	/**
	 * Error handler
	 *
	 * @param int    $errno Error number.
	 * @param string $errstr Error string.
	 * @param string $errfile Error file.
	 * @param int    $errline Error line.
	 * @return bool
	 */
	public static function error_handler( $errno, $errstr, $errfile, $errline ) {
		// Solo manejar errores de nuestro plugin
		if ( strpos( $errfile, 'json_version_plugin' ) === false ) {
			return false; // Dejar que otros plugins manejen sus errores
		}

		// Log del error si WP_DEBUG está activo
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'JSON Version Manager Error [%s]: %s in %s on line %s',
				$errno,
				$errstr,
				$errfile,
				$errline
			) );
		}

		// No mostrar errores en producción
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return true; // Suprimir el error
		}

		return false; // Dejar que PHP maneje el error normalmente en debug
	}

	/**
	 * Shutdown handler para errores fatales
	 */
	public static function shutdown_handler() {
		$error = error_get_last();
		
		if ( $error && strpos( $error['file'], 'json_version_plugin' ) !== false ) {
			// Es un error de nuestro plugin
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf(
					'JSON Version Manager Fatal Error: %s in %s on line %s',
					$error['message'],
					$error['file'],
					$error['line']
				) );
			}

			// Si estamos en admin y es un error fatal, mostrar mensaje amigable
			if ( is_admin() && in_array( $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ), true ) ) {
				// No hacer nada que pueda romper más la página
				// Solo loguear
			}
		}
	}

	/**
	 * Safe JSON encode
	 *
	 * @param mixed $data Data to encode.
	 * @param int   $options Options.
	 * @return string|false
	 */
	public static function safe_json_encode( $data, $options = 0 ) {
		try {
			return wp_json_encode( $data, $options );
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'JSON Version Manager: JSON encode error - ' . $e->getMessage() );
			}
			return false;
		}
	}

	/**
	 * Safe file operations
	 *
	 * @param string $file File path.
	 * @param string $content Content to write.
	 * @return bool|int
	 */
	public static function safe_file_put_contents( $file, $content ) {
		try {
			// Verificar que el archivo está en nuestro directorio
			if ( strpos( $file, 'json_version_plugin' ) === false ) {
				return false;
			}

			return @file_put_contents( $file, $content, LOCK_EX );
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'JSON Version Manager: File write error - ' . $e->getMessage() );
			}
			return false;
		}
	}
}

