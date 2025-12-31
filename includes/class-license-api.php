<?php
/**
 * License API Class
 * Gestiona el endpoint de verificación de licencias
 *
 * @package JSON_Version_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase para API de licencias
 */
class JVM_License_API {

	/**
	 * Initialize
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		register_rest_route(
			'jvm/v1',
			'/verify',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'verify_license' ),
				'permission_callback' => '__return_true', // Público, pero validamos internamente
				'args'                => array(
					'license_key' => array(
						'required' => true,
						'type'     => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'site_url' => array(
						'required' => false,
						'type'     => 'string',
						'sanitize_callback' => 'esc_url_raw',
					),
					'plugin' => array(
						'required' => false,
						'type'     => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Verify license
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function verify_license( $request ) {
		$license_key = $request->get_param( 'license_key' );
		$site_url    = $request->get_param( 'site_url' ) ?: '';
		$plugin      = $request->get_param( 'plugin' ) ?: 'mcp-stream-wp';

		// Validar que se proporcionó la licencia
		if ( empty( $license_key ) ) {
			return new WP_Error(
				'missing_license_key',
				'License key is required',
				array( 'status' => 400 )
			);
		}

		// Validar formato básico
		if ( strlen( $license_key ) < 10 ) {
			return new WP_Error(
				'invalid_format',
				'Invalid license key format',
				array( 'status' => 400 )
			);
		}

		// Verificar licencia
		$result = $this->check_license( $license_key, $site_url, $plugin );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Devolver respuesta exitosa
		return rest_ensure_response( $result );
	}

	/**
	 * Check license validity
	 *
	 * @param string $license_key License key.
	 * @param string $site_url Site URL.
	 * @param string $plugin Plugin name.
	 * @return array|WP_Error
	 */
	private function check_license( $license_key, $site_url, $plugin ) {
		// Obtener licencias válidas desde opciones (configurables desde admin)
		$valid_licenses = get_option( 'jvm_valid_licenses', array() );

		// Si no hay licencias configuradas, permitir cualquier licencia (modo desarrollo)
		if ( empty( $valid_licenses ) ) {
			// En producción, esto debería devolver error
			if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
				return new WP_Error(
					'no_licenses_configured',
					'License system not configured. Please configure valid licenses in the admin panel.',
					array( 'status' => 503 )
				);
			}

			// Modo desarrollo: aceptar cualquier licencia
			return array(
				'license'     => 'valid',
				'expires'     => strtotime( '+1 year' ),
				'customer'    => 'Development Mode',
				'activations' => 999,
				'site_url'    => $site_url,
			);
		}

		// Buscar la licencia en la lista de válidas
		$license_data = null;
		foreach ( $valid_licenses as $license ) {
			if ( isset( $license['key'] ) && $license['key'] === $license_key ) {
				$license_data = $license;
				break;
			}
		}

		// Si no se encuentra la licencia
		if ( ! $license_data ) {
			return new WP_Error(
				'invalid_license',
				'Invalid license key',
				array( 'status' => 403 )
			);
		}

		// Verificar expiración
		if ( isset( $license_data['expires'] ) && ! empty( $license_data['expires'] ) ) {
			$expires = is_numeric( $license_data['expires'] ) 
				? $license_data['expires'] 
				: strtotime( $license_data['expires'] );
			
			if ( $expires < time() ) {
				return new WP_Error(
					'expired_license',
					'License has expired',
					array( 'status' => 403 )
				);
			}
		}

		// Verificar límite de activaciones
		$activations = $this->get_activation_count( $license_key );
		$max_activations = isset( $license_data['max_activations'] ) 
			? intval( $license_data['max_activations'] ) 
			: 1;

		if ( $activations >= $max_activations && ! $this->is_site_activated( $license_key, $site_url ) ) {
			return new WP_Error(
				'activation_limit',
				'License activation limit reached',
				array( 'status' => 403 )
			);
		}

		// Registrar activación si es nueva
		if ( ! empty( $site_url ) && ! $this->is_site_activated( $license_key, $site_url ) ) {
			$this->register_activation( $license_key, $site_url );
		}

		// Devolver datos de licencia válida
		return array(
			'license'     => 'valid',
			'expires'     => isset( $license_data['expires'] ) && ! empty( $license_data['expires'] )
				? ( is_numeric( $license_data['expires'] ) ? $license_data['expires'] : strtotime( $license_data['expires'] ) )
				: strtotime( '+1 year' ),
			'customer'    => isset( $license_data['customer'] ) ? $license_data['customer'] : 'Unknown',
			'activations' => $this->get_activation_count( $license_key ),
			'site_url'    => $site_url,
		);
	}

	/**
	 * Get activation count for a license
	 *
	 * @param string $license_key License key.
	 * @return int
	 */
	private function get_activation_count( $license_key ) {
		$activations = get_option( 'jvm_license_activations', array() );
		if ( ! isset( $activations[ $license_key ] ) ) {
			return 0;
		}
		return count( $activations[ $license_key ] );
	}

	/**
	 * Check if site is already activated
	 *
	 * @param string $license_key License key.
	 * @param string $site_url Site URL.
	 * @return bool
	 */
	private function is_site_activated( $license_key, $site_url ) {
		$activations = get_option( 'jvm_license_activations', array() );
		if ( ! isset( $activations[ $license_key ] ) ) {
			return false;
		}
		return in_array( $site_url, $activations[ $license_key ], true );
	}

	/**
	 * Register activation
	 *
	 * @param string $license_key License key.
	 * @param string $site_url Site URL.
	 */
	private function register_activation( $license_key, $site_url ) {
		$activations = get_option( 'jvm_license_activations', array() );
		if ( ! isset( $activations[ $license_key ] ) ) {
			$activations[ $license_key ] = array();
		}
		if ( ! in_array( $site_url, $activations[ $license_key ], true ) ) {
			$activations[ $license_key ][] = $site_url;
			update_option( 'jvm_license_activations', $activations );
		}
	}
}

