<?php
/**
 * Bootstrap simple para tests unitarios
 * Mock de funciones de WordPress
 */

// Definir constantes de WordPress
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/../../' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}

// Mock de funciones de WordPress
if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return dirname( $file ) . '/';
	}
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ) {
		return 'https://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		// Mock - no hace nada
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		// Mock - no hace nada
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( $hook, ...$args ) {
		global $wp_actions;
		if ( ! isset( $wp_actions ) ) {
			$wp_actions = array();
		}
		$wp_actions[ $hook ] = $args;
	}
}

if ( ! function_exists( 'register_activation_hook' ) ) {
	function register_activation_hook( $file, $callback ) {
		// Mock - no hace nada
	}
}

if ( ! function_exists( 'add_management_page' ) ) {
	function add_management_page( $page_title, $menu_title, $capability, $menu_slug, $function ) {
		// Mock - no hace nada
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability ) {
		return true; // Por defecto permitir en tests
	}
}

if ( ! function_exists( 'wp_die' ) ) {
	function wp_die( $message = '', $title = '', $args = array() ) {
		throw new Exception( $message );
	}
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( $nonce, $action = -1 ) {
		return $nonce === 'valid_nonce';
	}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action = -1 ) {
		return 'valid_nonce';
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		return filter_var( $url, FILTER_SANITIZE_URL );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return filter_var( $url, FILTER_SANITIZE_URL );
	}
}

if ( ! function_exists( 'esc_js' ) ) {
	function esc_js( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $data ) {
		return $data; // Simplificado para tests
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( 'wp_safe_redirect' ) ) {
	function wp_safe_redirect( $location, $status = 302 ) {
		// Mock - no hace nada
		exit;
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( $key, $value = null, $url = null ) {
		if ( $url === null ) {
			$url = 'http://example.com';
		}
		$parsed = parse_url( $url );
		$query  = isset( $parsed['query'] ) ? $parsed['query'] : '';
		parse_str( $query, $params );
		if ( is_array( $key ) ) {
			$params = array_merge( $params, $key );
		} else {
			$params[ $key ] = $value;
		}
		$query_string = http_build_query( $params );
		return $parsed['scheme'] . '://' . $parsed['host'] . ( isset( $parsed['path'] ) ? $parsed['path'] : '' ) . ( $query_string ? '?' . $query_string : '' );
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '' ) {
		return 'http://example.com/wp-admin/' . $path;
	}
}

if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '' ) {
		return 'http://example.com/' . $path;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value ) {
		global $wp_options;
		if ( ! isset( $wp_options ) ) {
			$wp_options = array();
		}
		$wp_options[ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		global $wp_options;
		if ( ! isset( $wp_options ) ) {
			$wp_options = array();
		}
		return isset( $wp_options[ $option ] ) ? $wp_options[ $option ] : $default;
	}
}

if ( ! function_exists( 'wp_editor' ) ) {
	function wp_editor( $content, $editor_id, $settings = array() ) {
		echo '<textarea id="' . esc_attr( $editor_id ) . '" name="' . esc_attr( $editor_id ) . '">' . esc_textarea( $content ) . '</textarea>';
	}
}

if ( ! function_exists( 'esc_textarea' ) ) {
	function esc_textarea( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'submit_button' ) ) {
	function submit_button( $text = 'Save Changes', $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null ) {
		echo '<button type="submit" name="' . esc_attr( $name ) . '" class="button button-' . esc_attr( $type ) . '">' . esc_html( $text ) . '</button>';
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( '_e' ) ) {
	function _e( $text, $domain = 'default' ) {
		echo $text;
	}
}

if ( ! function_exists( 'date_i18n' ) ) {
	function date_i18n( $format, $timestamp = null ) {
		if ( $timestamp === null ) {
			$timestamp = time();
		}
		return date( $format, $timestamp );
	}
}

if ( ! function_exists( 'wp_mkdir_p' ) ) {
	function wp_mkdir_p( $target ) {
		$target = rtrim( $target, '/\\' );
		if ( empty( $target ) ) {
			$target = '/';
		}
		if ( file_exists( $target ) ) {
			return @is_dir( $target );
		}
		$target_parent = dirname( $target );
		while ( '.' !== $target_parent && ! is_dir( $target_parent ) ) {
			$target_parent = dirname( $target_parent );
		}
		if ( $stat = @stat( $target_parent ) ) {
			$dir_perms = $stat['mode'] & 0007777;
		} else {
			$dir_perms = 0755;
		}
		if ( @mkdir( $target, $dir_perms, true ) ) {
			if ( $dir_perms !== ( $stat['mode'] & 0007777 ) ) {
				@chmod( $target, $dir_perms );
			}
			return true;
		}
		return false;
	}
}

if ( ! function_exists( 'size_format' ) ) {
	function size_format( $bytes, $decimals = 0 ) {
		$quant = array(
			'TB' => 1024 * 1024 * 1024 * 1024,
			'GB' => 1024 * 1024 * 1024,
			'MB' => 1024 * 1024,
			'KB' => 1024,
			'B'  => 1,
		);
		foreach ( $quant as $unit => $mag ) {
			if ( doubleval( $bytes ) >= $mag ) {
				return number_format_i18n( $bytes / $mag, $decimals ) . ' ' . $unit;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'number_format_i18n' ) ) {
	function number_format_i18n( $number, $decimals = 0 ) {
		return number_format( $number, $decimals, '.', ',' );
	}
}

// Definir constantes del plugin solo si no están definidas
// Nota: Estas constantes pueden estar definidas en json-version-manager.php
// pero las definimos aquí para los tests si no existen
if ( ! defined( 'JVM_VERSION' ) ) {
	define( 'JVM_VERSION', '1.0.3' );
}
if ( ! defined( 'JVM_PLUGIN_DIR' ) ) {
	define( 'JVM_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'JVM_PLUGIN_URL' ) ) {
	define( 'JVM_PLUGIN_URL', 'https://example.com/wp-content/plugins/json_version_plugin/' );
}
if ( ! defined( 'JVM_JSON_FILE' ) ) {
	define( 'JVM_JSON_FILE', JVM_PLUGIN_DIR . 'mcp-metadata.json' );
}
if ( ! defined( 'JVM_JSON_URL' ) ) {
	define( 'JVM_JSON_URL', JVM_PLUGIN_URL . 'mcp-metadata.json' );
}

// Cargar el plugin
require_once dirname( __DIR__ ) . '/includes/class-error-handler.php';
require_once dirname( __DIR__ ) . '/includes/class-json-version-manager.php';
require_once dirname( __DIR__ ) . '/json-version-manager.php';

