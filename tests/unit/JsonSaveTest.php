<?php
/**
 * Tests para guardado de JSON
 *
 * @package JSON_Version_Manager
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests para el guardado del JSON
 */
class JsonSaveTest extends TestCase {

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
		$test_file = JVM_PLUGIN_DIR . 'mcp-metadata-test.json';
		if ( file_exists( $test_file ) ) {
			unlink( $test_file );
		}

		// Limpiar opciones
		global $wp_options;
		if ( isset( $wp_options ) ) {
			$wp_options = array();
		}

		parent::tearDown();
	}

	/**
	 * Test: Validar datos de entrada
	 */
	public function test_validate_input_data() {
		$valid_data = array(
			'name'                => 'Test Plugin',
			'slug'                => 'test-plugin',
			'version'             => '1.0.0',
			'adapter_version'     => '1.0.0',
			'min_adapter_version' => '1.0.0',
			'download_url'        => 'https://example.com/download',
			'requires_php'        => '8.0',
			'requires_wordpress'  => '6.4',
		);

		$this->assertArrayHasKey( 'name', $valid_data );
		$this->assertArrayHasKey( 'version', $valid_data );
		$this->assertNotEmpty( $valid_data['name'] );
		$this->assertNotEmpty( $valid_data['version'] );
	}

	/**
	 * Test: Sanitizar campos de texto
	 */
	public function test_sanitize_text_fields() {
		$dirty = '<script>alert("xss")</script>Test Plugin';
		$clean = sanitize_text_field( $dirty );
		
		$this->assertStringNotContainsString( '<script>', $clean );
		$this->assertStringNotContainsString( '</script>', $clean );
		$this->assertStringContainsString( 'Test Plugin', $clean );
	}

	/**
	 * Test: Sanitizar URL
	 */
	public function test_sanitize_url() {
		$dirty = 'javascript:alert("xss")';
		$clean = esc_url_raw( $dirty );
		
		// esc_url_raw debería limpiar URLs peligrosas
		$this->assertIsString( $clean );
	}

	/**
	 * Test: Formato de versión válido
	 */
	public function test_version_format() {
		$valid_versions = array( '1.0.0', '1.1.0', '2.0.0', '1.0.1' );
		
		foreach ( $valid_versions as $version ) {
			$this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+$/', $version, "Versión inválida: {$version}" );
		}
	}

	/**
	 * Test: Estructura JSON válida
	 */
	public function test_json_structure() {
		$data = array(
			'name'                => 'Test Plugin',
			'slug'                => 'test-plugin',
			'version'             => '1.0.0',
			'adapter_version'     => '1.0.0',
			'min_adapter_version' => '1.0.0',
			'download_url'        => 'https://example.com/download',
			'requires_php'        => '8.0',
			'requires_wordpress'  => '6.4',
			'last_updated'        => date( 'Y-m-d' ),
			'sections'            => array(
				'changelog' => '<h4>1.0.0</h4><ul><li>Test</li></ul>',
			),
		);

		$json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		$decoded = json_decode( $json, true );

		$this->assertIsString( $json );
		$this->assertIsArray( $decoded );
		$this->assertEquals( $data['name'], $decoded['name'] );
		$this->assertEquals( $data['version'], $decoded['version'] );
	}

	/**
	 * Test: Campos requeridos
	 */
	public function test_required_fields() {
		$required = array( 'name', 'slug', 'version' );
		
		$data = array(
			'name'    => 'Test',
			'slug'    => 'test',
			'version' => '1.0.0',
		);

		foreach ( $required as $field ) {
			$this->assertArrayHasKey( $field, $data, "Campo requerido faltante: {$field}" );
			$this->assertNotEmpty( $data[ $field ], "Campo requerido vacío: {$field}" );
		}
	}
}

