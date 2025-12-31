<?php
/**
 * Debug script para verificar el menú
 * Ejecutar desde: wp-admin/admin.php?page=json-version-manager-debug
 */

if ( ! defined( 'ABSPATH' ) ) {
	require_once '../../../wp-load.php';
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'No tienes permisos' );
}

echo '<h1>Debug - JSON Version Manager Menu</h1>';

// Verificar clase
echo '<h2>Clase JSON_Version_Manager:</h2>';
echo class_exists( 'JSON_Version_Manager' ) ? '✅ Existe' : '❌ No existe';
echo '<br>';

// Verificar hooks
echo '<h2>Hooks registrados:</h2>';
global $wp_filter;
if ( isset( $wp_filter['admin_menu'] ) ) {
	$admin_menu_hooks = $wp_filter['admin_menu']->callbacks;
	foreach ( $admin_menu_hooks as $priority => $callbacks ) {
		foreach ( $callbacks as $callback ) {
			if ( is_array( $callback['function'] ) && is_object( $callback['function'][0] ) ) {
				$class = get_class( $callback['function'][0] );
				if ( strpos( $class, 'JSON_Version' ) !== false || strpos( $class, 'JVM' ) !== false ) {
					echo "Prioridad: {$priority}, Función: {$class}::{$callback['function'][1]}<br>";
				}
			} elseif ( is_string( $callback['function'] ) && ( strpos( $callback['function'], 'jvm' ) !== false || strpos( $callback['function'], 'JVM' ) !== false ) ) {
				echo "Prioridad: {$priority}, Función: {$callback['function']}<br>";
			}
		}
	}
}

// Verificar menú
echo '<h2>Menú en tools.php:</h2>';
global $submenu;
if ( isset( $submenu['tools.php'] ) ) {
	echo '<pre>';
	print_r( $submenu['tools.php'] );
	echo '</pre>';
} else {
	echo '❌ No hay submenús en tools.php';
}

// Intentar añadir manualmente
echo '<h2>Intentar añadir manualmente:</h2>';
if ( class_exists( 'JSON_Version_Manager' ) ) {
	$manager = new JSON_Version_Manager();
	$hook = add_management_page(
		'JSON Version Manager Debug',
		'JSON Versiones Debug',
		'manage_options',
		'json-version-manager-debug',
		function() {
			echo '<h1>Debug Page</h1><p>Si ves esto, el menú funciona.</p>';
		}
	);
	echo $hook ? "✅ Menú añadido: {$hook}" : '❌ No se pudo añadir';
}

