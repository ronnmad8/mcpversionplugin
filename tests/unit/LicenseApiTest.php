<?php
/**
 * Tests para License API
 *
 * @package JSON_Version_Manager
 */

require_once dirname( __DIR__ ) . '/bootstrap-simple.php';

// Mock de funciones REST API
if ( ! function_exists( 'register_rest_route' ) ) {
	function register_rest_route( $namespace, $route, $args = array() ) {
		global $registered_routes;
		if ( ! isset( $registered_routes ) ) {
			$registered_routes = array();
		}
		$registered_routes[ $namespace . $route ] = $args;
	}
}

if ( ! function_exists( 'rest_ensure_response' ) ) {
	function rest_ensure_response( $data ) {
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		return new WP_REST_Response( $data );
	}
}

if ( ! function_exists( 'rest_url' ) ) {
	function rest_url( $path = '' ) {
		return 'https://example.com/wp-json/' . ltrim( $path, '/' );
	}
}

// Mock de WP_REST_Request
if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {
		private $params = array();

		public function __construct( $params = array() ) {
			$this->params = $params;
		}

		public function get_param( $key, $default = null ) {
			return isset( $this->params[ $key ] ) ? $this->params[ $key ] : $default;
		}
	}
}

// Mock de WP_REST_Response
if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {
		private $data;
		private $status;

		public function __construct( $data = null, $status = 200 ) {
			$this->data = $data;
			$this->status = $status;
		}

		public function get_data() {
			return $this->data;
		}

		public function get_status() {
			return $this->status;
		}
	}
}

// Mock de WP_Error
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $code;
		private $message;
		private $data;

		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code = $code;
			$this->message = $message;
			$this->data = $data;
		}

		public function get_error_code() {
			return $this->code;
		}

		public function get_error_message() {
			return $this->message;
		}

		public function get_error_data() {
			return $this->data;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

// Cargar la clase de API de licencias
require_once dirname( dirname( __DIR__ ) ) . '/includes/class-license-api.php';

/**
 * Test class para License API
 */
class LicenseApiTest extends PHPUnit\Framework\TestCase {

	/**
	 * Setup antes de cada test
	 */
	protected function setUp(): void {
		parent::setUp();
		
		// Limpiar opciones antes de cada test
		global $wp_options;
		$wp_options = array();
		
		// Limpiar rutas registradas
		global $registered_routes;
		$registered_routes = array();
	}

	/**
	 * Test: Registrar rutas REST API
	 */
	public function test_register_routes() {
		global $registered_routes;
		$registered_routes = array();
		
		$api = new JVM_License_API();
		
		// Simular rest_api_init llamando directamente al método
		$api->register_routes();
		
		$this->assertArrayHasKey( 'jvm/v1/verify', $registered_routes );
		$this->assertEquals( 'POST', $registered_routes['jvm/v1/verify']['methods'] );
	}

	/**
	 * Test: Verificar licencia sin clave
	 */
	public function test_verify_license_missing_key() {
		$api = new JVM_License_API();
		$request = new WP_REST_Request( array() );
		
		$result = $api->verify_license( $request );
		
		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertEquals( 'missing_license_key', $result->get_error_code() );
	}

	/**
	 * Test: Verificar licencia con formato inválido
	 */
	public function test_verify_license_invalid_format() {
		$api = new JVM_License_API();
		$request = new WP_REST_Request( array( 'license_key' => '123' ) );
		
		$result = $api->verify_license( $request );
		
		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertEquals( 'invalid_format', $result->get_error_code() );
	}

	/**
	 * Test: Verificar licencia no configurada (modo producción)
	 */
	public function test_verify_license_no_licenses_configured_production() {
		// Este test requiere que WP_DEBUG no esté definido
		// Como en el bootstrap se puede definir, lo marcamos como skipped
		// o lo probamos de otra manera
		$this->markTestSkipped( 'Requiere entorno sin WP_DEBUG para probar modo producción' );
	}

	/**
	 * Test: Verificar licencia no configurada (modo desarrollo)
	 */
	public function test_verify_license_no_licenses_configured_development() {
		// Definir WP_DEBUG para modo desarrollo
		if ( ! defined( 'WP_DEBUG' ) ) {
			define( 'WP_DEBUG', true );
		}
		
		$api = new JVM_License_API();
		$request = new WP_REST_Request( array( 
			'license_key' => 'ANY-LICENSE-KEY-12345',
			'site_url' => 'https://example.com'
		) );
		
		$result = $api->verify_license( $request );
		
		// En modo desarrollo debería aceptar cualquier licencia
		$this->assertNotInstanceOf( 'WP_Error', $result );
		$data = $result->get_data();
		$this->assertEquals( 'valid', $data['license'] );
		$this->assertEquals( 'Development Mode', $data['customer'] );
	}

	/**
	 * Test: Verificar licencia válida
	 */
	public function test_verify_license_valid() {
		// Configurar una licencia válida
		$valid_license = array(
			'key' => 'TEST-LICENSE-KEY-12345',
			'customer' => 'Test Customer',
			'expires' => strtotime( '+1 year' ),
			'max_activations' => 2,
		);
		update_option( 'jvm_valid_licenses', array( $valid_license ) );
		
		$api = new JVM_License_API();
		$request = new WP_REST_Request( array( 
			'license_key' => 'TEST-LICENSE-KEY-12345',
			'site_url' => 'https://example.com',
			'plugin' => 'mcp-stream-wp'
		) );
		
		$result = $api->verify_license( $request );
		
		$this->assertNotInstanceOf( 'WP_Error', $result );
		$data = $result->get_data();
		$this->assertEquals( 'valid', $data['license'] );
		$this->assertEquals( 'Test Customer', $data['customer'] );
		$this->assertEquals( 'https://example.com', $data['site_url'] );
		$this->assertEquals( 1, $data['activations'] ); // Primera activación
	}

	/**
	 * Test: Verificar licencia inválida
	 */
	public function test_verify_license_invalid() {
		// Configurar una licencia válida diferente
		$valid_license = array(
			'key' => 'VALID-LICENSE-KEY-12345',
			'customer' => 'Test Customer',
			'expires' => strtotime( '+1 year' ),
			'max_activations' => 1,
		);
		update_option( 'jvm_valid_licenses', array( $valid_license ) );
		
		$api = new JVM_License_API();
		$request = new WP_REST_Request( array( 
			'license_key' => 'INVALID-LICENSE-KEY-12345',
			'site_url' => 'https://example.com'
		) );
		
		$result = $api->verify_license( $request );
		
		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertEquals( 'invalid_license', $result->get_error_code() );
	}

	/**
	 * Test: Verificar licencia expirada
	 */
	public function test_verify_license_expired() {
		// Configurar una licencia expirada
		$expired_license = array(
			'key' => 'EXPIRED-LICENSE-KEY-12345',
			'customer' => 'Test Customer',
			'expires' => strtotime( '-1 day' ), // Expirada ayer
			'max_activations' => 1,
		);
		update_option( 'jvm_valid_licenses', array( $expired_license ) );
		
		$api = new JVM_License_API();
		$request = new WP_REST_Request( array( 
			'license_key' => 'EXPIRED-LICENSE-KEY-12345',
			'site_url' => 'https://example.com'
		) );
		
		$result = $api->verify_license( $request );
		
		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertEquals( 'expired_license', $result->get_error_code() );
	}

	/**
	 * Test: Verificar límite de activaciones
	 */
	public function test_verify_license_activation_limit() {
		// Configurar una licencia con límite de 1 activación
		$limited_license = array(
			'key' => 'LIMITED-LICENSE-KEY-12345',
			'customer' => 'Test Customer',
			'expires' => strtotime( '+1 year' ),
			'max_activations' => 1,
		);
		update_option( 'jvm_valid_licenses', array( $limited_license ) );
		
		// Activar en un sitio
		$activations = array(
			'LIMITED-LICENSE-KEY-12345' => array( 'https://site1.com' )
		);
		update_option( 'jvm_license_activations', $activations );
		
		$api = new JVM_License_API();
		$request = new WP_REST_Request( array( 
			'license_key' => 'LIMITED-LICENSE-KEY-12345',
			'site_url' => 'https://site2.com' // Diferente sitio
		) );
		
		$result = $api->verify_license( $request );
		
		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertEquals( 'activation_limit', $result->get_error_code() );
	}

	/**
	 * Test: Verificar que se registra la activación
	 */
	public function test_verify_license_registers_activation() {
		// Configurar una licencia válida
		$valid_license = array(
			'key' => 'REGISTER-LICENSE-KEY-12345',
			'customer' => 'Test Customer',
			'expires' => strtotime( '+1 year' ),
			'max_activations' => 2,
		);
		update_option( 'jvm_valid_licenses', array( $valid_license ) );
		
		$api = new JVM_License_API();
		$request = new WP_REST_Request( array( 
			'license_key' => 'REGISTER-LICENSE-KEY-12345',
			'site_url' => 'https://newsite.com',
			'plugin' => 'mcp-stream-wp'
		) );
		
		$result = $api->verify_license( $request );
		
		// Verificar que la activación se registró
		$activations = get_option( 'jvm_license_activations', array() );
		$this->assertArrayHasKey( 'REGISTER-LICENSE-KEY-12345', $activations );
		$this->assertContains( 'https://newsite.com', $activations['REGISTER-LICENSE-KEY-12345'] );
	}

	/**
	 * Test: Verificar que no se duplican activaciones
	 */
	public function test_verify_license_no_duplicate_activations() {
		// Configurar una licencia válida
		$valid_license = array(
			'key' => 'NO-DUPLICATE-LICENSE-KEY-12345',
			'customer' => 'Test Customer',
			'expires' => strtotime( '+1 year' ),
			'max_activations' => 2,
		);
		update_option( 'jvm_valid_licenses', array( $valid_license ) );
		
		// Activar en un sitio
		$activations = array(
			'NO-DUPLICATE-LICENSE-KEY-12345' => array( 'https://existingsite.com' )
		);
		update_option( 'jvm_license_activations', $activations );
		
		$api = new JVM_License_API();
		$request = new WP_REST_Request( array( 
			'license_key' => 'NO-DUPLICATE-LICENSE-KEY-12345',
			'site_url' => 'https://existingsite.com', // Mismo sitio
			'plugin' => 'mcp-stream-wp'
		) );
		
		$result = $api->verify_license( $request );
		
		// Verificar que no se duplicó
		$activations = get_option( 'jvm_license_activations', array() );
		$this->assertCount( 1, $activations['NO-DUPLICATE-LICENSE-KEY-12345'] );
	}

	/**
	 * Test: Verificar licencia sin expiración
	 */
	public function test_verify_license_no_expiration() {
		// Configurar una licencia sin expiración
		$no_expire_license = array(
			'key' => 'NO-EXPIRE-LICENSE-KEY-12345',
			'customer' => 'Test Customer',
			'expires' => '', // Sin expiración
			'max_activations' => 1,
		);
		update_option( 'jvm_valid_licenses', array( $no_expire_license ) );
		
		$api = new JVM_License_API();
		$request = new WP_REST_Request( array( 
			'license_key' => 'NO-EXPIRE-LICENSE-KEY-12345',
			'site_url' => 'https://example.com'
		) );
		
		$result = $api->verify_license( $request );
		
		$this->assertNotInstanceOf( 'WP_Error', $result );
		$data = $result->get_data();
		$this->assertEquals( 'valid', $data['license'] );
		// Debería tener una fecha de expiración por defecto (1 año)
		$this->assertGreaterThan( time(), $data['expires'] );
	}
}

