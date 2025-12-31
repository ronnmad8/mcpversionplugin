<?php
/**
 * Plugin Name:       JSON Version Manager
 * Plugin URI:        https://renekay.com
 * Description:       Gestiona el archivo JSON de versiones para MCP Stream WordPress desde el admin de WordPress
 * Version:           1.0.3
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            RENEKAY
 * Author URI:        https://renekay.com
 * License:           GPL-2.0-or-later
 * License URI:       https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain:       json-version-manager
 * Domain Path:       /languages
 *
 * @package JSON_Version_Manager
 */

// Si este archivo es llamado directamente, abortar.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Definir constantes del plugin (solo si no están definidas para evitar warnings en tests)
if ( ! defined( 'JVM_VERSION' ) ) {
	define( 'JVM_VERSION', '1.0.3' );
}
if ( ! defined( 'JVM_PLUGIN_DIR' ) ) {
	define( 'JVM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'JVM_PLUGIN_URL' ) ) {
	define( 'JVM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'JVM_JSON_FILE' ) ) {
	define( 'JVM_JSON_FILE', JVM_PLUGIN_DIR . 'mcp-metadata.json' );
}
if ( ! defined( 'JVM_JSON_URL' ) ) {
	define( 'JVM_JSON_URL', JVM_PLUGIN_URL . 'mcp-metadata.json' );
}

// Cargar las clases
require_once JVM_PLUGIN_DIR . 'includes/class-error-handler.php';
require_once JVM_PLUGIN_DIR . 'includes/class-json-version-manager.php';
require_once JVM_PLUGIN_DIR . 'includes/class-admin-menu-fix.php';
require_once JVM_PLUGIN_DIR . 'includes/class-license-api.php';

// Inicializar error handler primero
JVM_Error_Handler::init();

// Inicializar API de licencias
if ( class_exists( 'JVM_License_API' ) ) {
	new JVM_License_API();
}

// Inicializar el plugin inmediatamente
function jvm_init() {
	try {
		// Verificar que la clase existe
		if ( ! class_exists( 'JSON_Version_Manager' ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'JSON Version Manager: Clase no encontrada' );
			}
			return;
		}

		$manager = new JSON_Version_Manager();
		$manager->init();
	} catch ( Exception $e ) {
		// Log del error pero no romper la web
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'JSON Version Manager Init Error: ' . $e->getMessage() );
		}
	}
}

// Inicializar en múltiples hooks para asegurar que se ejecuta
add_action( 'plugins_loaded', 'jvm_init', 5 );
add_action( 'init', 'jvm_init', 5 );

// Variable global para evitar duplicados - inicializada como estática
if ( ! isset( $GLOBALS['jvm_menu_registered'] ) ) {
	$GLOBALS['jvm_menu_registered'] = false;
}

// Añadir menú UNA SOLA VEZ - SOLUCIÓN ÚNICA
function jvm_add_admin_menu_once() {
	// Usar GLOBALS para asegurar persistencia entre llamadas
	if ( ! isset( $GLOBALS['jvm_menu_registered'] ) ) {
		$GLOBALS['jvm_menu_registered'] = false;
	}
	
	// Si ya se registró, no hacer nada
	if ( $GLOBALS['jvm_menu_registered'] ) {
		return;
	}

	// Verificaciones básicas
	if ( ! function_exists( 'is_admin' ) || ! is_admin() ) {
		return;
	}

	if ( ! function_exists( 'add_management_page' ) ) {
		return;
	}

	if ( ! class_exists( 'JSON_Version_Manager' ) ) {
		return;
	}

	// Verificar si ya existe en tools
	global $submenu;
	$exists_in_tools = false;
	if ( isset( $submenu['tools.php'] ) && is_array( $submenu['tools.php'] ) ) {
		foreach ( $submenu['tools.php'] as $item ) {
			if ( isset( $item[2] ) && $item[2] === 'json-version-manager' ) {
				$exists_in_tools = true;
				$GLOBALS['jvm_menu_registered'] = true; // Ya existe, marcar como registrado
				return;
			}
		}
	}

	// Verificar si ya existe como menú principal
	global $menu;
	$exists_in_main = false;
	if ( isset( $menu ) && is_array( $menu ) ) {
		foreach ( $menu as $item ) {
			if ( isset( $item[2] ) && $item[2] === 'json-version-manager' ) {
				$exists_in_main = true;
				$GLOBALS['jvm_menu_registered'] = true; // Ya existe, marcar como registrado
				return;
			}
		}
	}

	// Si no existe en ningún lado, añadirlo UNA SOLA VEZ
	if ( ! $exists_in_tools && ! $exists_in_main ) {
		$manager = new JSON_Version_Manager();
		$hook = add_management_page(
			'JSON Version Manager',
			'JSON Versiones',
			'manage_options',
			'json-version-manager',
			array( $manager, 'render_admin_page' )
		);

		// Si falla add_management_page, usar add_menu_page como fallback
		if ( ! $hook && function_exists( 'add_menu_page' ) ) {
			add_menu_page(
				'JSON Version Manager',
				'JSON Versiones',
				'manage_options',
				'json-version-manager',
				array( $manager, 'render_admin_page' ),
				'dashicons-update',
				30
			);
		}

		// Marcar como registrado
		$GLOBALS['jvm_menu_registered'] = true;
	}
}

// Registrar UNA SOLA VEZ en admin_menu con prioridad estándar
add_action( 'admin_menu', 'jvm_add_admin_menu_once', 10 );

// Hook de activación
register_activation_hook( __FILE__, 'jvm_activate' );
function jvm_activate() {
	// Crear archivo JSON inicial si no existe
	if ( ! file_exists( JVM_JSON_FILE ) ) {
		$default_json = array(
			'name'                => 'MCP Stream WordPress',
			'slug'                => 'mcp-stream-wp',
			'version'             => '1.0.0',
			'adapter_version'     => '1.0.0',
			'min_adapter_version' => '1.0.0',
			'download_url'        => 'https://renekay.com/api/mcp-adapter-download.php',
			'requires_php'        => '8.0',
			'requires_wordpress'  => '6.4',
			'last_updated'        => date( 'Y-m-d' ),
			'sections'            => array(
				'changelog' => '<h4>1.0.0</h4><ul><li>Versión inicial</li></ul>',
			),
			'php_files_hash'     => '',
			'tested_up_to'       => '6.4',
		);

		file_put_contents(
			JVM_JSON_FILE,
			wp_json_encode( $default_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		);
	}
}

