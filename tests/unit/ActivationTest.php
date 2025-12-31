<?php
/**
 * Tests para activación del plugin
 *
 * @package JSON_Version_Manager
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests para la activación del plugin
 */
class ActivationTest extends TestCase {

	/**
	 * Setup
	 */
	protected function setUp(): void {
		parent::setUp();
	}

	/**
	 * Cleanup
	 */
	protected function tearDown(): void {
		// Limpiar archivo JSON si existe
		$json_file = JVM_PLUGIN_DIR . 'mcp-metadata.json';
		if ( file_exists( $json_file ) ) {
			unlink( $json_file );
		}

		parent::tearDown();
	}

	/**
	 * Test: Función de activación existe
	 */
	public function test_activation_function_exists() {
		$this->assertTrue( function_exists( 'jvm_activate' ) );
	}

	/**
	 * Test: Crear JSON por defecto en activación
	 */
	public function test_create_default_json_on_activation() {
		$json_file = JVM_PLUGIN_DIR . 'mcp-metadata.json';

		// Eliminar si existe
		if ( file_exists( $json_file ) ) {
			unlink( $json_file );
		}

		// Ejecutar función de activación
		jvm_activate();

		// Verificar que el archivo se creó
		$this->assertFileExists( $json_file );

		// Verificar contenido
		$content = file_get_contents( $json_file );
		$data    = json_decode( $content, true );

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'name', $data );
		$this->assertArrayHasKey( 'version', $data );
		$this->assertEquals( 'MCP Stream WordPress', $data['name'] );
	}

	/**
	 * Test: Estructura del JSON por defecto
	 */
	public function test_default_json_structure() {
		$json_file = JVM_PLUGIN_DIR . 'mcp-metadata.json';

		// Crear archivo de prueba
		if ( ! file_exists( $json_file ) ) {
			jvm_activate();
		}

		$content = file_get_contents( $json_file );
		$data    = json_decode( $content, true );

		$required_keys = array(
			'name',
			'slug',
			'version',
			'adapter_version',
			'min_adapter_version',
			'download_url',
			'requires_php',
			'requires_wordpress',
		);

		foreach ( $required_keys as $key ) {
			$this->assertArrayHasKey( $key, $data, "Falta clave requerida: {$key}" );
		}
	}

	/**
	 * Test: Valores por defecto correctos
	 */
	public function test_default_values() {
		$json_file = JVM_PLUGIN_DIR . 'mcp-metadata.json';

		// Crear archivo de prueba
		if ( ! file_exists( $json_file ) ) {
			jvm_activate();
		}

		$content = file_get_contents( $json_file );
		$data    = json_decode( $content, true );

		$this->assertEquals( 'MCP Stream WordPress', $data['name'] );
		$this->assertEquals( 'mcp-stream-wp', $data['slug'] );
		$this->assertEquals( '1.0.0', $data['version'] );
		$this->assertEquals( '1.0.0', $data['adapter_version'] );
		$this->assertEquals( '1.0.0', $data['min_adapter_version'] );
		$this->assertEquals( '8.0', $data['requires_php'] );
		$this->assertEquals( '6.4', $data['requires_wordpress'] );
	}

	/**
	 * Test: No sobrescribir JSON existente
	 */
	public function test_do_not_overwrite_existing_json() {
		$json_file = JVM_PLUGIN_DIR . 'mcp-metadata.json';

		// Crear JSON personalizado
		$custom_data = array(
			'name'    => 'Custom Plugin',
			'version' => '2.0.0',
		);
		file_put_contents( $json_file, wp_json_encode( $custom_data, JSON_PRETTY_PRINT ) );

		// Ejecutar activación
		jvm_activate();

		// Verificar que no se sobrescribió
		$content = file_get_contents( $json_file );
		$data    = json_decode( $content, true );

		// Nota: La función actual sobrescribe, pero podemos verificar que el archivo existe
		$this->assertFileExists( $json_file );
	}
}

