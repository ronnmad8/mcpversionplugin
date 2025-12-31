<?php
/**
 * Tests para manejo de archivo JSON
 *
 * @package JSON_Version_Manager
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests para el archivo JSON
 */
class JsonFileTest extends TestCase {

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
		// Limpiar archivos de test
		$test_files = array(
			JVM_PLUGIN_DIR . 'mcp-metadata-test.json',
			JVM_PLUGIN_DIR . 'mcp-metadata.json',
		);

		foreach ( $test_files as $file ) {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}

		parent::tearDown();
	}

	/**
	 * Test: Crear archivo JSON
	 */
	public function test_create_json_file() {
		$test_file = JVM_PLUGIN_DIR . 'mcp-metadata-test.json';
		$data      = array(
			'name'    => 'Test',
			'version' => '1.0.0',
		);

		$json = wp_json_encode( $data, JSON_PRETTY_PRINT );
		$result = file_put_contents( $test_file, $json );

		$this->assertNotFalse( $result );
		$this->assertFileExists( $test_file );
		$this->assertGreaterThan( 0, filesize( $test_file ) );
	}

	/**
	 * Test: Leer archivo JSON
	 */
	public function test_read_json_file() {
		$test_file = JVM_PLUGIN_DIR . 'mcp-metadata-test.json';
		$data      = array(
			'name'    => 'Test Plugin',
			'version' => '1.0.0',
		);

		file_put_contents( $test_file, wp_json_encode( $data, JSON_PRETTY_PRINT ) );

		$content = file_get_contents( $test_file );
		$decoded = json_decode( $content, true );

		$this->assertIsString( $content );
		$this->assertIsArray( $decoded );
		$this->assertEquals( 'Test Plugin', $decoded['name'] );
		$this->assertEquals( '1.0.0', $decoded['version'] );
	}

	/**
	 * Test: Validar JSON válido
	 */
	public function test_valid_json() {
		$valid_json = '{"name":"Test","version":"1.0.0"}';
		$decoded    = json_decode( $valid_json, true );

		$this->assertIsArray( $decoded );
		$this->assertEquals( JSON_ERROR_NONE, json_last_error() );
	}

	/**
	 * Test: Detectar JSON inválido
	 */
	public function test_invalid_json() {
		$invalid_json = '{"name":"Test","version":}';
		$decoded      = json_decode( $invalid_json, true );

		$this->assertNull( $decoded );
		$this->assertNotEquals( JSON_ERROR_NONE, json_last_error() );
	}

	/**
	 * Test: Formato JSON con pretty print
	 */
	public function test_json_pretty_print() {
		$data = array(
			'name'    => 'Test',
			'version' => '1.0.0',
		);

		$json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		$this->assertStringContainsString( "\n", $json );
		$this->assertStringContainsString( '"name"', $json );
		$this->assertStringContainsString( '"version"', $json );
	}

	/**
	 * Test: Permisos de archivo
	 */
	public function test_file_permissions() {
		$test_file = JVM_PLUGIN_DIR . 'mcp-metadata-test.json';
		$data      = array( 'test' => 'data' );

		file_put_contents( $test_file, wp_json_encode( $data ) );

		if ( file_exists( $test_file ) ) {
			$perms = fileperms( $test_file );
			$this->assertIsInt( $perms );
		}
	}

	/**
	 * Test: Actualizar archivo JSON
	 */
	public function test_update_json_file() {
		$test_file = JVM_PLUGIN_DIR . 'mcp-metadata-test.json';

		// Crear archivo inicial
		$data1 = array( 'version' => '1.0.0' );
		file_put_contents( $test_file, wp_json_encode( $data1 ) );

		// Actualizar
		$data2 = array( 'version' => '1.1.0' );
		file_put_contents( $test_file, wp_json_encode( $data2 ) );

		// Verificar
		$content = file_get_contents( $test_file );
		$decoded = json_decode( $content, true );

		$this->assertEquals( '1.1.0', $decoded['version'] );
	}
}

