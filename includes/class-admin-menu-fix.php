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
 * NOTA: Esta función ya no se usa - el menú se registra en json-version-manager.php
 * Se mantiene por compatibilidad pero no se ejecuta para evitar duplicados
 */
function jvm_ensure_admin_menu() {
	// Esta función ya no se ejecuta - el menú se registra en json-version-manager.php
	// para evitar duplicados
	return;
}
// NO registrar esta función - el menú se registra en json-version-manager.php
// add_action( 'admin_menu', 'jvm_ensure_admin_menu', 5 );
// add_action( 'admin_menu', 'jvm_ensure_admin_menu', 15 );
// add_action( 'admin_menu', 'jvm_ensure_admin_menu', 999 );

