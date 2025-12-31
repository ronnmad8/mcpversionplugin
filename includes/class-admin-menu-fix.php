<?php
/**
 * Admin Menu Fix
 * Asegura que el menú se muestre correctamente
 *
 * @package JSON_Version_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Función para verificar y forzar el menú
 * Se ejecuta con múltiples prioridades para asegurar que funciona
 */
function jvm_ensure_admin_menu() {
	// Solo ejecutar en admin
	if ( ! is_admin() || ! function_exists( 'is_admin' ) ) {
		return;
	}

	if ( ! class_exists( 'JSON_Version_Manager' ) ) {
		return;
	}

	// Verificar si el menú ya existe
	global $submenu;
	
	// Buscar si nuestro menú ya está registrado
	$menu_exists = false;
	if ( isset( $submenu['tools.php'] ) && is_array( $submenu['tools.php'] ) ) {
		foreach ( $submenu['tools.php'] as $item ) {
			if ( isset( $item[2] ) && $item[2] === 'json-version-manager' ) {
				$menu_exists = true;
				break;
			}
		}
	}

	// Si no existe, añadirlo manualmente
	if ( ! $menu_exists ) {
		$manager = new JSON_Version_Manager();
		$hook = add_management_page(
			__( 'JSON Version Manager', 'json-version-manager' ),
			__( 'JSON Versiones', 'json-version-manager' ),
			'manage_options',
			'json-version-manager',
			array( $manager, 'render_admin_page' )
		);

		// Si falla add_management_page, intentar como menú principal
		if ( ! $hook ) {
			add_menu_page(
				__( 'JSON Version Manager', 'json-version-manager' ),
				__( 'JSON Versiones', 'json-version-manager' ),
				'manage_options',
				'json-version-manager',
				array( $manager, 'render_admin_page' ),
				'dashicons-update',
				30
			);
		}
	}
}
// Usar múltiples prioridades para asegurar que se ejecuta
add_action( 'admin_menu', 'jvm_ensure_admin_menu', 5 );
add_action( 'admin_menu', 'jvm_ensure_admin_menu', 15 );
add_action( 'admin_menu', 'jvm_ensure_admin_menu', 999 );

