<?php
/**
 * Tests para JSON_Version_Manager
 *
 * @package JSON_Version_Manager
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests para la clase JSON_Version_Manager
 */
class JsonVersionManagerTest extends TestCase {

	/**
	 * Ruta temporal para el archivo JSON
	 *
	 * @var string
	 */
	private $temp_json_file;

	/**
	 * Instancia del manager
	 *
	 * @var JSON_Version_Manager
	 */
	private $manager;

	/**
	 * Setup antes de cada test
	 */
	protected function setUp(): void {
		parent::setUp();

		// Crear archivo temporal
		$this->temp_json_file = sys_get_temp_dir() . '/mcp-metadata-test-' . uniqid() . '.json';
		
		// Redefinir constante para el test
		if ( defined( 'JVM_JSON_FILE' ) ) {
			// Ya está definida, usar ruta temporal
			$this->temp_json_file = JVM_PLUGIN_DIR . 'mcp-metadata-test.json';
		}

		// Limpiar archivo si existe
		if ( file_exists( $this->temp_json_file ) ) {
			unlink( $this->temp_json_file );
		}

		// Crear instancia
		$this->manager = new JSON_Version_Manager();
	}

	/**
	 * Cleanup después de cada test
	 */
	protected function tearDown(): void {
		// Limpiar archivo temporal
		if ( file_exists( $this->temp_json_file ) ) {
			unlink( $this->temp_json_file );
		}

		// Limpiar opciones
		global $wp_options;
		if ( isset( $wp_options ) ) {
			$wp_options = array();
		}

		parent::tearDown();
	}

	/**
	 * Test: La clase existe
	 */
	public function test_class_exists() {
		$this->assertTrue( class_exists( 'JSON_Version_Manager' ) );
	}

	/**
	 * Test: Inicialización
	 */
	public function test_init() {
		$this->assertTrue( method_exists( $this->manager, 'init' ) );
		$this->manager->init();
		$this->assertTrue( true ); // Si no lanza excepción, está bien
	}

	/**
	 * Test: Obtener datos JSON por defecto
	 */
	public function test_get_default_json_data() {
		$reflection = new ReflectionClass( $this->manager );
		$method     = $reflection->getMethod( 'get_json_data' );
		$method->setAccessible( true );

		$data = $method->invoke( $this->manager );

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'name', $data );
		$this->assertArrayHasKey( 'version', $data );
		$this->assertArrayHasKey( 'adapter_version', $data );
		$this->assertEquals( 'MCP Stream WordPress', $data['name'] );
		$this->assertEquals( '1.0.0', $data['version'] );
	}

	/**
	 * Test: Obtener datos JSON desde archivo existente
	 */
	public function test_get_json_data_from_file() {
		// Crear archivo JSON de prueba en la ubicación esperada
		$test_data = array(
			'name'                => 'Test Plugin',
			'slug'                => 'test-plugin',
			'version'             => '2.0.0',
			'adapter_version'     => '2.0.0',
			'min_adapter_version' => '2.0.0',
			'download_url'        => 'https://example.com/download',
			'requires_php'        => '8.0',
			'requires_wordpress'  => '6.4',
		);

		$json_file = JVM_JSON_FILE;
		file_put_contents( $json_file, wp_json_encode( $test_data, JSON_PRETTY_PRINT ) );

		// Obtener datos usando reflexión
		$reflection = new ReflectionClass( $this->manager );
		$method     = $reflection->getMethod( 'get_json_data' );
		$method->setAccessible( true );

		$data = $method->invoke( $this->manager );

		// Verificar que lee del archivo
		$this->assertEquals( 'Test Plugin', $data['name'] );
		$this->assertEquals( '2.0.0', $data['version'] );

		// Limpiar
		if ( file_exists( $json_file ) ) {
			unlink( $json_file );
		}
	}

	/**
	 * Test: Validar estructura de datos por defecto
	 */
	public function test_default_data_structure() {
		$reflection = new ReflectionClass( $this->manager );
		$method     = $reflection->getMethod( 'get_json_data' );
		$method->setAccessible( true );

		$data = $method->invoke( $this->manager );

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
			$this->assertArrayHasKey( $key, $data, "Falta la clave requerida: {$key}" );
		}
	}

	/**
	 * Test: Métodos públicos existen
	 */
	public function test_public_methods_exist() {
		$this->assertTrue( method_exists( $this->manager, 'add_admin_menu' ) );
		$this->assertTrue( method_exists( $this->manager, 'register_settings' ) );
		$this->assertTrue( method_exists( $this->manager, 'render_admin_page' ) );
		$this->assertTrue( method_exists( $this->manager, 'save_json' ) );
		$this->assertTrue( method_exists( $this->manager, 'serve_json' ) );
	}

	/**
	 * Test: Constantes definidas
	 */
	public function test_constants_defined() {
		$this->assertTrue( defined( 'JVM_VERSION' ) );
		$this->assertTrue( defined( 'JVM_PLUGIN_DIR' ) );
		$this->assertTrue( defined( 'JVM_PLUGIN_URL' ) );
		$this->assertTrue( defined( 'JVM_JSON_FILE' ) );
		$this->assertTrue( defined( 'JVM_JSON_URL' ) );
	}

	/**
	 * Test: Option name constante
	 */
	public function test_option_name_constant() {
		$reflection = new ReflectionClass( $this->manager );
		$constant   = $reflection->getConstant( 'OPTION_NAME' );
		$this->assertEquals( 'jvm_json_data', $constant );
	}
}

