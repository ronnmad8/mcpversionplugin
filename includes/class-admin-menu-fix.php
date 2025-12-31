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
 */
function jvm_ensure_admin_menu() {
	// Solo ejecutar en admin
	if ( ! is_admin() ) {
		return;
	}

	// Verificar si el menú ya existe
	global $submenu;
	
	// Buscar si nuestro menú ya está registrado
	$menu_exists = false;
	if ( isset( $submenu['tools.php'] ) ) {
		foreach ( $submenu['tools.php'] as $item ) {
			if ( isset( $item[2] ) && $item[2] === 'json-version-manager' ) {
				$menu_exists = true;
				break;
			}
		}
	}

	// Si no existe y la clase está disponible, añadirlo manualmente
	if ( ! $menu_exists && class_exists( 'JSON_Version_Manager' ) ) {
		$manager = new JSON_Version_Manager();
		add_management_page(
			__( 'JSON Version Manager', 'json-version-manager' ),
			__( 'JSON Versiones', 'json-version-manager' ),
			'manage_options',
			'json-version-manager',
			array( $manager, 'render_admin_page' )
		);
	}
}
// Usar prioridad muy alta para asegurar que se ejecuta después de todo
add_action( 'admin_menu', 'jvm_ensure_admin_menu', 999 );

