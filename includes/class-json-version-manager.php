<?php
/**
 * JSON Version Manager Class
 * Gestiona el archivo JSON de versiones
 *
 * @package JSON_Version_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase principal del plugin
 */
class JSON_Version_Manager {

	/**
	 * Option name
	 */
	const OPTION_NAME = 'jvm_json_data';

	/**
	 * Initialize
	 */
	public function init() {
		// NO registrar el menú aquí - se registra en el archivo principal para evitar duplicados
		// El menú se registra una sola vez en json-version-manager.php
		add_action( 'admin_init', array( $this, 'register_settings' ), 10 );
		add_action( 'admin_post_jvm_save_json', array( $this, 'save_json' ) );
		add_action( 'admin_post_jvm_preview_json', array( $this, 'preview_json' ) );
		add_action( 'admin_post_jvm_save_license', array( $this, 'save_license' ) );
		add_action( 'admin_post_jvm_delete_license', array( $this, 'delete_license' ) );
		
		// Usar prioridad alta para template_redirect para que se ejecute antes que otros plugins
		// pero solo si no hay conflictos
		add_action( 'template_redirect', array( $this, 'serve_json' ), 1 );
		
		// Prevenir conflictos con Elementor y otros page builders
		add_action( 'elementor/loaded', array( $this, 'prevent_elementor_conflicts' ), 1 );
	}
	
	/**
	 * Prevent conflicts with Elementor
	 */
	public function prevent_elementor_conflicts() {
		// Si Elementor está activo, asegurar que nuestro template_redirect no interfiera
		// Solo servir JSON si no es una request de Elementor
		if ( isset( $_GET['elementor-preview'] ) || isset( $_GET['elementor_library'] ) ) {
			remove_action( 'template_redirect', array( $this, 'serve_json' ), 1 );
		}
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		// Verificaciones básicas
		if ( ! function_exists( 'is_admin' ) || ! is_admin() ) {
			return;
		}

		if ( ! function_exists( 'add_management_page' ) ) {
			return;
		}

		// Verificar si ya existe para evitar duplicados
		global $submenu;
		$menu_exists = false;
		
		if ( isset( $submenu['tools.php'] ) && is_array( $submenu['tools.php'] ) ) {
			foreach ( $submenu['tools.php'] as $item ) {
				if ( isset( $item[2] ) && $item[2] === 'json-version-manager' ) {
					$menu_exists = true;
					return; // Ya existe, no hacer nada
				}
			}
		}

		// Añadir página en el menú de Herramientas
		$hook = add_management_page(
			'JSON Version Manager',
			'JSON Versiones',
			'manage_options',
			'json-version-manager',
			array( $this, 'render_admin_page' )
		);

		// Si no se añadió en Herramientas, intentar en el menú principal como fallback
		if ( ! $hook && function_exists( 'add_menu_page' ) ) {
			add_menu_page(
				'JSON Version Manager',
				'JSON Versiones',
				'manage_options',
				'json-version-manager',
				array( $this, 'render_admin_page' ),
				'dashicons-update',
				30
			);
		}
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		// No necesitamos registrar settings, usamos admin_post
	}

	/**
	 * Render admin page
	 */
	public function render_admin_page() {
		// Verificar permisos
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'json-version-manager' ) );
		}

		// Cargar datos actuales con manejo de errores
		try {
			$json_data = $this->get_json_data();
		} catch ( Exception $e ) {
			// Si hay error, usar datos por defecto
			$json_data = $this->get_default_json_data();
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'JSON Version Manager: Error al cargar datos - ' . $e->getMessage() );
			}
		}

		// Mostrar mensajes
		if ( isset( $_GET['saved'] ) && $_GET['saved'] === '1' ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'JSON guardado correctamente.', 'json-version-manager' ); ?></p>
			</div>
			<?php
		}

		if ( isset( $_GET['license_saved'] ) && $_GET['license_saved'] === '1' ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Licencia guardada correctamente.', 'json-version-manager' ); ?></p>
			</div>
			<?php
		}

		if ( isset( $_GET['license_deleted'] ) && $_GET['license_deleted'] === '1' ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Licencia eliminada correctamente.', 'json-version-manager' ); ?></p>
			</div>
			<?php
		}

		if ( isset( $_GET['error'] ) ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo esc_html( urldecode( $_GET['error'] ) ); ?></p>
			</div>
			<?php
		}

		// Obtener información de versión actual
		$current_version = $json_data['version'] ?? '1.0.0';
		$adapter_version = $json_data['adapter_version'] ?? '1.0.0';
		$min_adapter_version = $json_data['min_adapter_version'] ?? '1.0.0';
		$last_updated = $json_data['last_updated'] ?? date( 'Y-m-d' );
		$json_file_exists = file_exists( JVM_JSON_FILE );
		$json_file_size = $json_file_exists ? filesize( JVM_JSON_FILE ) : 0;
		$json_file_date = $json_file_exists ? date( 'Y-m-d H:i:s', filemtime( JVM_JSON_FILE ) ) : '';

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'JSON Version Manager', 'json-version-manager' ); ?></h1>
			
			<div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px; border-left: 4px solid #2271b1;">
				<h2 style="margin-top: 0; font-size: 18px;">
					<?php esc_html_e( 'Versión Actual en el JSON', 'json-version-manager' ); ?>
				</h2>
				<div style="display: flex; align-items: center; gap: 20px; margin: 15px 0;">
					<div>
						<span style="color: #666; font-size: 13px;"><?php esc_html_e( 'Versión actual:', 'json-version-manager' ); ?></span>
						<strong style="color: #2271b1; font-size: 24px; font-weight: bold; display: block; margin-top: 5px;">
							<?php echo esc_html( $current_version ); ?>
						</strong>
					</div>
					<div style="flex: 1;">
						<span style="color: #666; font-size: 12px;">
							<?php esc_html_e( 'Esta versión se está sirviendo en:', 'json-version-manager' ); ?>
						</span><br>
						<code style="background: #f0f0f1; padding: 5px 10px; border-radius: 3px; font-size: 11px; margin-top: 5px; display: inline-block;">
							<?php echo esc_url( JVM_JSON_URL ); ?>
						</code>
					</div>
					<?php if ( $json_file_exists ) : ?>
						<div style="text-align: right;">
							<span style="color: green; font-weight: bold;">✓ <?php esc_html_e( 'Activo', 'json-version-manager' ); ?></span>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px;">
				<h2 style="margin-top: 0; font-size: 18px;"><?php esc_html_e( 'Editar Versión', 'json-version-manager' ); ?></h2>
				<p class="description" style="margin-bottom: 15px;">
					<?php esc_html_e( 'Actualiza la versión que se servirá en el archivo JSON público.', 'json-version-manager' ); ?>
				</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'jvm_save_json', 'jvm_nonce' ); ?>
				<input type="hidden" name="action" value="jvm_save_json">

				<table class="form-table" role="presentation" style="max-width: 800px;">
					<!-- Campos ocultos para mantener compatibilidad -->
					<input type="hidden" id="name" name="name" value="<?php echo esc_attr( $json_data['name'] ?? 'MCP Stream WordPress' ); ?>">
					<input type="hidden" id="slug" name="slug" value="<?php echo esc_attr( $json_data['slug'] ?? 'mcp-stream-wp' ); ?>">

					<tr style="background: #f0f6fc; border-left: 4px solid #2271b1;">
						<th scope="row" style="padding: 12px; width: 200px;">
							<label for="version" style="font-size: 14px; font-weight: bold;">
								<?php esc_html_e( 'Versión del Plugin', 'json-version-manager' ); ?>
							</label>
						</th>
						<td style="padding: 12px;">
							<div style="display: flex; align-items: center; gap: 15px;">
								<div>
									<span style="color: #666; font-size: 12px; display: block; margin-bottom: 5px;">
										<?php esc_html_e( 'Actual:', 'json-version-manager' ); ?> 
										<strong style="color: #2271b1;"><?php echo esc_html( $current_version ); ?></strong>
									</span>
									<input 
										type="text" 
										id="version" 
										name="version" 
										value="<?php echo esc_attr( $json_data['version'] ?? '1.0.0' ); ?>" 
										class="regular-text" 
										required
										style="font-size: 16px; font-weight: bold; padding: 10px; border: 2px solid #2271b1; width: 150px;"
										placeholder="1.0.0"
									>
								</div>
								<div style="flex: 1;">
									<p class="description" style="margin: 0; font-size: 12px;">
										<?php esc_html_e( 'Formato: X.Y.Z', 'json-version-manager' ); ?> (ej: 1.0.0, 1.1.0)
										<br>
										<span style="color: #d63638; font-weight: bold;">
											⚠️ <?php esc_html_e( 'Se servirá en el JSON público', 'json-version-manager' ); ?>
										</span>
									</p>
								</div>
							</div>
						</td>
					</tr>

					<tr>
						<th scope="row" style="padding: 12px;">
							<label for="adapter_version">
								<?php esc_html_e( 'Versión Adaptador', 'json-version-manager' ); ?>
							</label>
						</th>
						<td style="padding: 12px;">
							<div style="display: flex; align-items: center; gap: 10px;">
								<span style="color: #666; font-size: 12px;">
									<?php esc_html_e( 'Actual:', 'json-version-manager' ); ?> <?php echo esc_html( $adapter_version ); ?>
								</span>
								<input type="text" id="adapter_version" name="adapter_version" value="<?php echo esc_attr( $json_data['adapter_version'] ?? '1.0.0' ); ?>" class="small-text" required style="width: 100px;">
							</div>
						</td>
					</tr>

					<tr>
						<th scope="row" style="padding: 12px;">
							<label for="min_adapter_version">
								<?php esc_html_e( 'Versión Mínima', 'json-version-manager' ); ?>
							</label>
						</th>
						<td style="padding: 12px;">
							<div style="display: flex; align-items: center; gap: 10px;">
								<span style="color: #666; font-size: 12px;">
									<?php esc_html_e( 'Actual:', 'json-version-manager' ); ?> <?php echo esc_html( $min_adapter_version ); ?>
								</span>
								<input type="text" id="min_adapter_version" name="min_adapter_version" value="<?php echo esc_attr( $json_data['min_adapter_version'] ?? '1.0.0' ); ?>" class="small-text" required style="width: 100px;">
								<span style="color: #d63638; font-size: 11px;">
									⚠️ <?php esc_html_e( 'Fuerza actualización', 'json-version-manager' ); ?>
								</span>
							</div>
						</td>
					</tr>

					<!-- Campos ocultos para mantener compatibilidad -->
					<input type="hidden" id="download_url" name="download_url" value="<?php echo esc_attr( $json_data['download_url'] ?? 'https://renekay.com/api/mcp-adapter-download.php' ); ?>">
					<input type="hidden" id="requires_php" name="requires_php" value="<?php echo esc_attr( $json_data['requires_php'] ?? '8.0' ); ?>">
					<input type="hidden" id="requires_wordpress" name="requires_wordpress" value="<?php echo esc_attr( $json_data['requires_wordpress'] ?? '6.4' ); ?>">
					<input type="hidden" id="tested_up_to" name="tested_up_to" value="<?php echo esc_attr( $json_data['tested_up_to'] ?? '6.4' ); ?>">
					<input type="hidden" id="changelog" name="changelog" value="<?php echo esc_attr( $json_data['sections']['changelog'] ?? '' ); ?>">
					<input type="hidden" id="php_files_hash" name="php_files_hash" value="<?php echo esc_attr( $json_data['php_files_hash'] ?? '' ); ?>">
				</table>

				<p class="submit">
					<?php submit_button( __( 'Guardar Versión', 'json-version-manager' ), 'primary', 'submit', false ); ?>
					<span style="margin-left: 10px; color: #666; font-size: 12px;">
						<?php esc_html_e( 'Los cambios se aplicarán inmediatamente al JSON público.', 'json-version-manager' ); ?>
					</span>
				</p>
			</form>
			</div>

			<?php $this->render_license_section(); ?>

			<div style="background: #fff; border: 1px solid #ccd0d4; padding: 15px; margin-top: 20px; font-size: 12px;">
				<details style="cursor: pointer;">
					<summary style="font-weight: bold; margin-bottom: 10px;">
						<?php esc_html_e( 'Ver más opciones y detalles', 'json-version-manager' ); ?>
					</summary>
					<div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
						<p>
							<strong><?php esc_html_e( 'URL del JSON:', 'json-version-manager' ); ?></strong>
							<code style="background: #f0f0f1; padding: 3px 8px; border-radius: 3px; font-size: 11px; margin-left: 10px;">
								<?php echo esc_url( JVM_JSON_URL ); ?>
							</code>
							<button type="button" class="button button-small" onclick="navigator.clipboard.writeText('<?php echo esc_js( JVM_JSON_URL ); ?>'); alert('<?php esc_html_e( 'URL copiada', 'json-version-manager' ); ?>');" style="margin-left: 10px;">
								<?php esc_html_e( 'Copiar', 'json-version-manager' ); ?>
							</button>
						</p>
						<?php if ( $json_file_exists ) : ?>
							<p style="margin-top: 10px;">
								<a href="<?php echo esc_url( JVM_JSON_URL ); ?>" target="_blank" class="button button-small">
									<?php esc_html_e( 'Ver JSON en el navegador', 'json-version-manager' ); ?>
								</a>
							</p>
						<?php endif; ?>
					</div>
				</details>
			</div>

			<div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px;">
				<h2><?php esc_html_e( 'Vista Previa del JSON', 'json-version-manager' ); ?></h2>
				<pre style="background: #f0f0f1; padding: 15px; border-radius: 3px; overflow-x: auto;"><code id="json-preview"><?php echo esc_html( wp_json_encode( $json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); ?></code></pre>
				<button type="button" class="button" onclick="document.getElementById('json-preview').textContent = JSON.stringify(<?php echo wp_json_encode( $json_data ); ?>, null, 2);">
					<?php esc_html_e( 'Actualizar Vista Previa', 'json-version-manager' ); ?>
				</button>
				<a href="<?php echo esc_url( JVM_JSON_URL ); ?>" target="_blank" class="button">
					<?php esc_html_e( 'Ver JSON Público', 'json-version-manager' ); ?>
				</a>
			</div>
		</div>

		<script>
		// Actualizar vista previa al cambiar campos
		document.addEventListener('DOMContentLoaded', function() {
			const form = document.querySelector('form');
			const preview = document.getElementById('json-preview');
			
			form.addEventListener('input', function() {
				const formData = new FormData(form);
				const jsonData = {
					name: formData.get('name'),
					slug: formData.get('slug'),
					version: formData.get('version'),
					adapter_version: formData.get('adapter_version'),
					min_adapter_version: formData.get('min_adapter_version'),
					download_url: formData.get('download_url'),
					requires_php: formData.get('requires_php'),
					requires_wordpress: formData.get('requires_wordpress'),
					tested_up_to: formData.get('tested_up_to'),
					last_updated: '<?php echo esc_js( date( 'Y-m-d' ) ); ?>',
					sections: {
						changelog: formData.get('changelog')
					},
					php_files_hash: formData.get('php_files_hash')
				};
				
				preview.textContent = JSON.stringify(jsonData, null, 2);
			});
		});
		</script>
		<?php
	}

	/**
	 * Get JSON data
	 *
	 * @return array
	 */
	private function get_json_data() {
		try {
			if ( file_exists( JVM_JSON_FILE ) && is_readable( JVM_JSON_FILE ) ) {
				$content = @file_get_contents( JVM_JSON_FILE );
				
				if ( false === $content ) {
					// Error al leer el archivo
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'JSON Version Manager: No se pudo leer el archivo JSON' );
					}
					return $this->get_default_json_data();
				}
				
				$data = json_decode( $content, true );
				
				if ( json_last_error() === JSON_ERROR_NONE && is_array( $data ) ) {
					return $data;
				}
				
				// JSON inválido
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'JSON Version Manager: JSON inválido - ' . json_last_error_msg() );
				}
			}
		} catch ( Exception $e ) {
			// Error al procesar
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'JSON Version Manager Error: ' . $e->getMessage() );
			}
		}

		// Retornar datos por defecto
		return $this->get_default_json_data();
	}
	
	/**
	 * Get default JSON data
	 *
	 * @return array
	 */
	private function get_default_json_data() {
		return array(
			'name'                => 'MCP Stream WordPress',
			'slug'                => 'mcp-stream-wp',
			'version'             => '1.0.0',
			'adapter_version'     => '1.0.0',
			'min_adapter_version' => '1.0.0',
			'download_url'        => 'https://renekay.com/api/mcp-adapter-download.php',
			'requires_php'        => '8.0',
			'requires_wordpress'  => '6.4',
			'last_updated'        => date( 'Y-m-d' ),
			'sections'            => array(
				'changelog' => '<h4>1.0.0</h4><ul><li>Versión inicial</li></ul>',
			),
			'php_files_hash'      => '',
			'tested_up_to'        => '6.4',
		);
	}

	/**
	 * Save JSON
	 */
	public function save_json() {
		// Verificar permisos
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'  => 'json-version-manager',
						'error' => urlencode( __( 'No tienes permisos para realizar esta acción.', 'json-version-manager' ) ),
					),
					admin_url( 'tools.php' )
				)
			);
			exit;
		}

		// Verificar nonce
		if ( ! isset( $_POST['jvm_nonce'] ) || ! wp_verify_nonce( $_POST['jvm_nonce'], 'jvm_save_json' ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'  => 'json-version-manager',
						'error' => urlencode( __( 'Error de seguridad. Por favor, intenta de nuevo.', 'json-version-manager' ) ),
					),
					admin_url( 'tools.php' )
				)
			);
			exit;
		}

		// Recoger datos del formulario con validación y manejo de errores
		try {
			$json_data = array(
				'name'                => sanitize_text_field( $_POST['name'] ?? '' ),
				'slug'                => sanitize_text_field( $_POST['slug'] ?? '' ),
				'version'             => sanitize_text_field( $_POST['version'] ?? '1.0.0' ),
				'adapter_version'     => sanitize_text_field( $_POST['adapter_version'] ?? '1.0.0' ),
				'min_adapter_version' => sanitize_text_field( $_POST['min_adapter_version'] ?? '1.0.0' ),
				'download_url'        => esc_url_raw( $_POST['download_url'] ?? '' ),
				'requires_php'        => sanitize_text_field( $_POST['requires_php'] ?? '8.0' ),
				'requires_wordpress'  => sanitize_text_field( $_POST['requires_wordpress'] ?? '6.4' ),
				'last_updated'        => date( 'Y-m-d' ),
				'sections'            => array(
					'changelog' => wp_kses_post( $_POST['changelog'] ?? '' ),
				),
				'php_files_hash'      => sanitize_text_field( $_POST['php_files_hash'] ?? '' ),
				'tested_up_to'        => sanitize_text_field( $_POST['tested_up_to'] ?? '6.4' ),
			);

			// Validar campos requeridos
			if ( empty( $json_data['name'] ) || empty( $json_data['slug'] ) || empty( $json_data['version'] ) ) {
				throw new Exception( __( 'Por favor, completa todos los campos requeridos.', 'json-version-manager' ) );
			}

			// Validar formato de versión
			if ( ! preg_match( '/^\d+\.\d+\.\d+$/', $json_data['version'] ) ) {
				throw new Exception( __( 'El formato de versión debe ser X.Y.Z (ej: 1.0.0)', 'json-version-manager' ) );
			}

			// Convertir a JSON con manejo de errores
			$json_string = wp_json_encode( $json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			
			if ( false === $json_string ) {
				throw new Exception( __( 'Error al generar el JSON. Verifica los datos.', 'json-version-manager' ) );
			}

			// Asegurar que el directorio existe
			$json_dir = dirname( JVM_JSON_FILE );
			if ( ! file_exists( $json_dir ) ) {
				$mkdir_result = wp_mkdir_p( $json_dir );
				if ( ! $mkdir_result ) {
					throw new Exception( __( 'No se pudo crear el directorio. Verifica permisos.', 'json-version-manager' ) );
				}
			}

			// Verificar permisos de escritura antes de guardar
			if ( file_exists( JVM_JSON_FILE ) && ! is_writable( JVM_JSON_FILE ) ) {
				throw new Exception( __( 'El archivo existe pero no tiene permisos de escritura.', 'json-version-manager' ) );
			}

			if ( ! is_writable( $json_dir ) ) {
				throw new Exception( __( 'El directorio no tiene permisos de escritura.', 'json-version-manager' ) );
			}

			// Guardar en archivo (en la carpeta del plugin) con manejo de errores
			$result = @file_put_contents( JVM_JSON_FILE, $json_string, LOCK_EX );

			if ( false === $result ) {
				$error = error_get_last();
				$error_msg = $error ? $error['message'] : __( 'Error desconocido al guardar el archivo.', 'json-version-manager' );
				throw new Exception( sprintf( __( 'Error al guardar el archivo: %s', 'json-version-manager' ), $error_msg ) );
			}

			// Verificar que el archivo se guardó correctamente
			if ( ! file_exists( JVM_JSON_FILE ) ) {
				throw new Exception( __( 'El archivo no se creó correctamente.', 'json-version-manager' ) );
			}

			// Validar que el JSON guardado es válido
			$saved_content = file_get_contents( JVM_JSON_FILE );
			$saved_data = json_decode( $saved_content, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				throw new Exception( __( 'El JSON guardado no es válido.', 'json-version-manager' ) );
			}

			// Guardar también en opciones de WordPress (backup) con manejo de errores
			$option_result = update_option( self::OPTION_NAME, $json_data );
			if ( false === $option_result ) {
				// No es crítico, solo loguear
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'JSON Version Manager: No se pudo guardar en opciones de WordPress' );
				}
			}

		} catch ( Exception $e ) {
			// Log del error si WP_DEBUG está activo
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'JSON Version Manager Error: ' . $e->getMessage() );
			}

			// Redirigir con mensaje de error
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'  => 'json-version-manager',
						'error' => urlencode( $e->getMessage() ),
					),
					admin_url( 'tools.php' )
				)
			);
			exit;
		}

		// Redirigir con mensaje de éxito
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'  => 'json-version-manager',
					'saved' => '1',
				),
				admin_url( 'tools.php' )
			)
		);
		exit;
	}

	/**
	 * Serve JSON file
	 */
	public function serve_json() {
		// Solo servir si la URL coincide con el archivo JSON del plugin
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		
		// Verificar si es la URL del archivo JSON del plugin
		$plugin_json_path = str_replace( home_url(), '', JVM_JSON_URL );
		if ( strpos( $request_uri, $plugin_json_path ) === false && strpos( $request_uri, '/mcp-metadata.json' ) === false ) {
			return;
		}

		// Evitar conflictos con otros plugins (como Elementor)
		// Solo procesar si no hay otros plugins procesando la request
		if ( did_action( 'template_redirect' ) > 1 ) {
			// Otro plugin ya procesó la request, no interferir
			return;
		}

		try {
			// Cargar datos con manejo de errores
			$json_data = $this->get_json_data();

			if ( empty( $json_data ) ) {
				// Si no hay datos, retornar JSON vacío en lugar de error
				$json_data = array(
					'error' => 'No data available',
					'version' => '0.0.0',
				);
			}

			// Headers con prevención de errores
			if ( ! headers_sent() ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				header( 'Cache-Control: public, max-age=3600' ); // Cache de 1 hora
				header( 'Access-Control-Allow-Origin: *' ); // Permitir CORS
				header( 'X-Content-Type-Options: nosniff' ); // Seguridad
			}

			// Output JSON con manejo de errores
			$json_output = wp_json_encode( $json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			
			if ( false === $json_output ) {
				// Si falla la codificación, enviar error JSON válido
				$json_output = wp_json_encode( array(
					'error' => 'JSON encoding failed',
					'version' => '0.0.0',
				) );
			}

			echo $json_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;

		} catch ( Exception $e ) {
			// En caso de error, enviar JSON de error válido
			if ( ! headers_sent() ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				header( 'HTTP/1.1 500 Internal Server Error' );
			}

			$error_json = wp_json_encode( array(
				'error' => 'Internal server error',
				'message' => defined( 'WP_DEBUG' ) && WP_DEBUG ? $e->getMessage() : 'An error occurred',
				'version' => '0.0.0',
			) );

			echo $error_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			
			// Log del error
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'JSON Version Manager Serve Error: ' . $e->getMessage() );
			}
			
			exit;
		}
	}

	/**
	 * Preview JSON
	 */
	public function preview_json() {
		// Verificar permisos
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos para realizar esta acción.', 'json-version-manager' ) );
		}

		$json_data = $this->get_json_data();
		header( 'Content-Type: application/json; charset=utf-8' );
		echo wp_json_encode( $json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		exit;
	}

	/**
	 * Render license management section
	 */
	private function render_license_section() {
		$valid_licenses = get_option( 'jvm_valid_licenses', array() );
		$activations = get_option( 'jvm_license_activations', array() );
		$api_url = rest_url( 'jvm/v1/verify' );
		?>
		<div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px;">
			<h2 style="margin-top: 0; font-size: 18px;"><?php esc_html_e( 'Gestión de Licencias', 'json-version-manager' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Gestiona las licencias válidas para el plugin MCP Stream WordPress. El endpoint de verificación está disponible en:', 'json-version-manager' ); ?>
			</p>
			<p>
				<code style="background: #f0f0f1; padding: 5px 10px; border-radius: 3px; font-size: 11px;">
					<?php echo esc_url( $api_url ); ?>
				</code>
				<button type="button" class="button button-small" onclick="navigator.clipboard.writeText('<?php echo esc_js( $api_url ); ?>'); alert('<?php esc_html_e( 'URL copiada', 'json-version-manager' ); ?>');" style="margin-left: 10px;">
					<?php esc_html_e( 'Copiar', 'json-version-manager' ); ?>
				</button>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="jvm-license-form">
				<?php wp_nonce_field( 'jvm_save_license', 'jvm_license_nonce' ); ?>
				<input type="hidden" name="action" value="jvm_save_license">

				<table class="form-table" style="margin-top: 15px;">
					<tr>
						<th scope="row" style="padding: 12px;">
							<label for="license_key"><?php esc_html_e( 'Clave de Licencia', 'json-version-manager' ); ?></label>
						</th>
						<td style="padding: 12px;">
							<input type="text" id="license_key" name="license_key" class="regular-text" required placeholder="LICENSE-XXXX-XXXX-XXXX">
							<button type="button" id="generate-license-btn" class="button" style="margin-left: 10px;">
								<?php esc_html_e( 'Generar', 'json-version-manager' ); ?>
							</button>
							<p class="description" style="margin-top: 5px;">
								<?php esc_html_e( 'Haz clic en "Generar" para crear una nueva clave de licencia automáticamente.', 'json-version-manager' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row" style="padding: 12px;">
							<label for="customer_name"><?php esc_html_e( 'Cliente', 'json-version-manager' ); ?></label>
						</th>
						<td style="padding: 12px;">
							<input type="text" id="customer_name" name="customer_name" class="regular-text" placeholder="<?php esc_attr_e( 'Nombre del cliente', 'json-version-manager' ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row" style="padding: 12px;">
							<label for="expires"><?php esc_html_e( 'Expira', 'json-version-manager' ); ?></label>
						</th>
						<td style="padding: 12px;">
							<input type="date" id="expires" name="expires" class="regular-text">
							<p class="description"><?php esc_html_e( 'Dejar vacío para licencia sin expiración', 'json-version-manager' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row" style="padding: 12px;">
							<label for="max_activations"><?php esc_html_e( 'Máx. Activaciones', 'json-version-manager' ); ?></label>
						</th>
						<td style="padding: 12px;">
							<input type="number" id="max_activations" name="max_activations" class="small-text" value="1" min="1">
							<p class="description"><?php esc_html_e( 'Número máximo de sitios que pueden activar esta licencia', 'json-version-manager' ); ?></p>
						</td>
					</tr>
				</table>

				<p class="submit">
					<?php submit_button( __( 'Añadir Licencia', 'json-version-manager' ), 'secondary', 'submit', false ); ?>
				</p>
			</form>

			<?php if ( ! empty( $valid_licenses ) ) : ?>
				<hr style="margin: 20px 0;">
				<h3><?php esc_html_e( 'Licencias Válidas', 'json-version-manager' ); ?></h3>
				<table class="widefat" style="margin-top: 10px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Clave', 'json-version-manager' ); ?></th>
							<th><?php esc_html_e( 'Cliente', 'json-version-manager' ); ?></th>
							<th><?php esc_html_e( 'Expira', 'json-version-manager' ); ?></th>
							<th><?php esc_html_e( 'Activaciones', 'json-version-manager' ); ?></th>
							<th><?php esc_html_e( 'Acciones', 'json-version-manager' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $valid_licenses as $index => $license ) : ?>
							<tr>
								<td>
									<code style="background: #f0f0f1; padding: 4px 8px; border-radius: 3px; font-size: 12px; display: inline-block; margin-right: 8px;">
										<?php echo esc_html( $license['key'] ); ?>
									</code>
									<button type="button" class="button button-small copy-license-btn" 
											data-license-key="<?php echo esc_attr( $license['key'] ); ?>"
											style="margin-left: 5px;">
										<?php esc_html_e( 'Copiar', 'json-version-manager' ); ?>
									</button>
								</td>
								<td><?php echo esc_html( $license['customer'] ?? '-' ); ?></td>
								<td>
									<?php
									if ( isset( $license['expires'] ) && ! empty( $license['expires'] ) ) {
										$expires = is_numeric( $license['expires'] ) 
											? date( 'Y-m-d', $license['expires'] ) 
											: $license['expires'];
										echo esc_html( $expires );
									} else {
										echo '<span style="color: #666;">' . esc_html__( 'Sin expiración', 'json-version-manager' ) . '</span>';
									}
									?>
								</td>
								<td>
									<?php
									$license_key = $license['key'];
									$activation_count = isset( $activations[ $license_key ] ) ? count( $activations[ $license_key ] ) : 0;
									$max_activations = isset( $license['max_activations'] ) ? intval( $license['max_activations'] ) : 1;
									echo esc_html( $activation_count . ' / ' . $max_activations );
									?>
								</td>
								<td>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
										<?php wp_nonce_field( 'jvm_delete_license_' . $index, 'jvm_delete_nonce' ); ?>
										<input type="hidden" name="action" value="jvm_delete_license">
										<input type="hidden" name="license_index" value="<?php echo esc_attr( $index ); ?>">
										<button type="submit" class="button button-small" onclick="return confirm('<?php esc_attr_e( '¿Estás seguro de eliminar esta licencia?', 'json-version-manager' ); ?>');">
											<?php esc_html_e( 'Eliminar', 'json-version-manager' ); ?>
										</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p style="color: #666; font-style: italic; margin-top: 15px;">
					<?php esc_html_e( 'No hay licencias configuradas. Añade una licencia arriba.', 'json-version-manager' ); ?>
				</p>
			<?php endif; ?>
		</div>
		
		<script type="text/javascript">
		(function() {
			'use strict';
			
			/**
			 * Genera una clave de licencia con formato LICENSE-XXXX-XXXX-XXXX
			 * @returns {string} Clave de licencia generada
			 */
			function generateLicenseKey() {
				const chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
				let key = 'LICENSE-';
				
				// Generar 3 grupos de 4 caracteres cada uno
				for (let i = 0; i < 3; i++) {
					if (i > 0) {
						key += '-';
					}
					for (let j = 0; j < 4; j++) {
						key += chars.charAt(Math.floor(Math.random() * chars.length));
					}
				}
				
				return key;
			}
			
			/**
			 * Copia texto al portapapeles
			 * @param {string} text Texto a copiar
			 * @returns {Promise} Promise que se resuelve cuando se copia
			 */
			function copyToClipboard(text) {
				if (navigator.clipboard && navigator.clipboard.writeText) {
					return navigator.clipboard.writeText(text).then(function() {
						return true;
					}).catch(function(err) {
						console.error('Error al copiar:', err);
						return false;
					});
				} else {
					// Fallback para navegadores antiguos
					const textArea = document.createElement('textarea');
					textArea.value = text;
					textArea.style.position = 'fixed';
					textArea.style.left = '-999999px';
					textArea.style.top = '-999999px';
					document.body.appendChild(textArea);
					textArea.focus();
					textArea.select();
					try {
						const successful = document.execCommand('copy');
						document.body.removeChild(textArea);
						return Promise.resolve(successful);
					} catch (err) {
						document.body.removeChild(textArea);
						return Promise.resolve(false);
					}
				}
			}
			
			// Añadir event listener cuando el DOM esté listo
			document.addEventListener('DOMContentLoaded', function() {
				// Generador de licencias
				const generateBtn = document.getElementById('generate-license-btn');
				const licenseInput = document.getElementById('license_key');
				
				if (generateBtn && licenseInput) {
					generateBtn.addEventListener('click', function(e) {
						e.preventDefault();
						licenseInput.value = generateLicenseKey();
					});
				}
				
				// Botones de copiar licencia
				const copyButtons = document.querySelectorAll('.copy-license-btn');
				copyButtons.forEach(function(btn) {
					btn.addEventListener('click', function(e) {
						e.preventDefault();
						const licenseKey = this.getAttribute('data-license-key');
						if (licenseKey) {
							copyToClipboard(licenseKey).then(function(success) {
								if (success) {
									const originalText = btn.textContent;
									btn.textContent = '<?php esc_html_e( '¡Copiado!', 'json-version-manager' ); ?>';
									btn.style.backgroundColor = '#46b450';
									btn.style.color = '#fff';
									setTimeout(function() {
										btn.textContent = originalText;
										btn.style.backgroundColor = '';
										btn.style.color = '';
									}, 2000);
								} else {
									alert('<?php esc_attr_e( 'Error al copiar. Por favor, copia manualmente.', 'json-version-manager' ); ?>');
								}
							});
						}
					});
				});
			});
		})();
		</script>
		<?php
	}

	/**
	 * Save license
	 */
	public function save_license() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos para realizar esta acción.', 'json-version-manager' ) );
		}

		if ( ! isset( $_POST['jvm_license_nonce'] ) || ! wp_verify_nonce( $_POST['jvm_license_nonce'], 'jvm_save_license' ) ) {
			wp_die( esc_html__( 'Error de seguridad. Por favor, intenta de nuevo.', 'json-version-manager' ) );
		}

		$license_key = sanitize_text_field( $_POST['license_key'] ?? '' );
		$customer = sanitize_text_field( $_POST['customer_name'] ?? '' );
		$expires = sanitize_text_field( $_POST['expires'] ?? '' );
		$max_activations = intval( $_POST['max_activations'] ?? 1 );

		if ( empty( $license_key ) ) {
			wp_safe_redirect( add_query_arg( 'page', 'json-version-manager', admin_url( 'tools.php' ) ) . '&error=' . urlencode( __( 'La clave de licencia es requerida.', 'json-version-manager' ) ) );
			exit;
		}

		$valid_licenses = get_option( 'jvm_valid_licenses', array() );

		// Verificar si ya existe
		foreach ( $valid_licenses as $license ) {
			if ( isset( $license['key'] ) && $license['key'] === $license_key ) {
				wp_safe_redirect( add_query_arg( 'page', 'json-version-manager', admin_url( 'tools.php' ) ) . '&error=' . urlencode( __( 'Esta licencia ya existe.', 'json-version-manager' ) ) );
				exit;
			}
		}

		// Añadir nueva licencia
		$new_license = array(
			'key'            => $license_key,
			'customer'       => $customer,
			'expires'        => ! empty( $expires ) ? strtotime( $expires ) : '',
			'max_activations' => $max_activations,
		);

		$valid_licenses[] = $new_license;
		update_option( 'jvm_valid_licenses', $valid_licenses );

		wp_safe_redirect( add_query_arg( 'page', 'json-version-manager', admin_url( 'tools.php' ) ) . '&license_saved=1' );
		exit;
	}

	/**
	 * Delete license
	 */
	public function delete_license() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos para realizar esta acción.', 'json-version-manager' ) );
		}

		$index = intval( $_POST['license_index'] ?? -1 );
		if ( $index < 0 || ! isset( $_POST['jvm_delete_nonce'] ) || ! wp_verify_nonce( $_POST['jvm_delete_nonce'], 'jvm_delete_license_' . $index ) ) {
			wp_die( esc_html__( 'Error de seguridad. Por favor, intenta de nuevo.', 'json-version-manager' ) );
		}

		$valid_licenses = get_option( 'jvm_valid_licenses', array() );
		if ( isset( $valid_licenses[ $index ] ) ) {
			unset( $valid_licenses[ $index ] );
			$valid_licenses = array_values( $valid_licenses ); // Reindexar
			update_option( 'jvm_valid_licenses', $valid_licenses );
		}

		wp_safe_redirect( add_query_arg( 'page', 'json-version-manager', admin_url( 'tools.php' ) ) . '&license_deleted=1' );
		exit;
	}
}

