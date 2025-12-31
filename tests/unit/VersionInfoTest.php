<?php
/**
 * Tests para información de versión
 *
 * @package JSON_Version_Manager
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests para la información de versión en el admin
 */
class VersionInfoTest extends TestCase {

	/**
	 * Manager instance
	 *
	 * @var JSON_Version_Manager
	 */
	private $manager;

	/**
	 * Setup
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->manager = new JSON_Version_Manager();
	}

	/**
	 * Cleanup
	 */
	protected function tearDown(): void {
		// Limpiar archivo de test si existe
		$test_file = JVM_PLUGIN_DIR . 'mcp-metadata.json';
		if ( file_exists( $test_file ) ) {
			unlink( $test_file );
		}

		parent::tearDown();
	}

	/**
	 * Test: Obtener versión actual del JSON
	 */
	public function test_get_current_version() {
		$test_data = array(
			'version'             => '1.2.0',
			'adapter_version'     => '1.2.0',
			'min_adapter_version' => '1.1.0',
		);

		$json_file = JVM_PLUGIN_DIR . 'mcp-metadata.json';
		file_put_contents( $json_file, wp_json_encode( $test_data, JSON_PRETTY_PRINT ) );

		$reflection = new ReflectionClass( $this->manager );
		$method     = $reflection->getMethod( 'get_json_data' );
		$method->setAccessible( true );

		$data = $method->invoke( $this->manager );

		$this->assertEquals( '1.2.0', $data['version'] );
		$this->assertEquals( '1.2.0', $data['adapter_version'] );
		$this->assertEquals( '1.1.0', $data['min_adapter_version'] );

		// Limpiar
		if ( file_exists( $json_file ) ) {
			unlink( $json_file );
		}
	}

	/**
	 * Test: Verificar que el archivo se guarda en la carpeta del plugin
	 */
	public function test_json_saved_in_plugin_directory() {
		$json_file = JVM_PLUGIN_DIR . 'mcp-metadata.json';
		$test_data = array( 'version' => '1.0.0' );

		file_put_contents( $json_file, wp_json_encode( $test_data ) );

		$this->assertFileExists( $json_file );
		$this->assertStringContainsString( 'json_version_plugin', $json_file );

		// Limpiar
		if ( file_exists( $json_file ) ) {
			unlink( $json_file );
		}
	}

	/**
	 * Test: Verificar información de archivo
	 */
	public function test_file_info() {
		$json_file = JVM_PLUGIN_DIR . 'mcp-metadata.json';
		$test_data = array( 'version' => '1.0.0' );

		file_put_contents( $json_file, wp_json_encode( $test_data ) );

		$file_exists = file_exists( $json_file );
		$file_size   = filesize( $json_file );
		$file_date   = filemtime( $json_file );

		$this->assertTrue( $file_exists );
		$this->assertGreaterThan( 0, $file_size );
		$this->assertIsInt( $file_date );

		// Limpiar
		if ( file_exists( $json_file ) ) {
			unlink( $json_file );
		}
	}

	/**
	 * Test: URL del JSON apunta a la carpeta del plugin
	 */
	public function test_json_url_in_plugin_directory() {
		$json_url = JVM_JSON_URL;

		$this->assertStringContainsString( 'json_version_plugin', $json_url );
		$this->assertStringContainsString( 'mcp-metadata.json', $json_url );
		$this->assertStringStartsWith( 'http', $json_url );
	}

	/**
	 * Test: Ruta del archivo está en la carpeta del plugin
	 */
	public function test_json_file_path_in_plugin_directory() {
		$json_file = JVM_JSON_FILE;

		$this->assertStringContainsString( 'json_version_plugin', $json_file );
		$this->assertStringEndsWith( 'mcp-metadata.json', $json_file );
	}
}

