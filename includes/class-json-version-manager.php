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
		// Asegurar que el menú se añade correctamente
		// Usar prioridad estándar para admin_menu
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 10 );
		add_action( 'admin_init', array( $this, 'register_settings' ), 10 );
		add_action( 'admin_post_jvm_save_json', array( $this, 'save_json' ) );
		add_action( 'admin_post_jvm_preview_json', array( $this, 'preview_json' ) );
		
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
		// Solo ejecutar en admin
		if ( ! is_admin() ) {
			return;
		}

		// Añadir página en el menú de Herramientas
		$hook = add_management_page(
			__( 'JSON Version Manager', 'json-version-manager' ),
			__( 'JSON Versiones', 'json-version-manager' ),
			'manage_options',
			'json-version-manager',
			array( $this, 'render_admin_page' )
		);

		// Si no se añadió en Herramientas, intentar en el menú principal como fallback
		if ( ! $hook ) {
			add_menu_page(
				__( 'JSON Version Manager', 'json-version-manager' ),
				__( 'JSON Versiones', 'json-version-manager' ),
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
			<p class="description">
				<?php esc_html_e( 'Gestiona el archivo JSON de versiones para MCP Stream WordPress.', 'json-version-manager' ); ?>
			</p>

			<div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px; border-left: 4px solid #2271b1;">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'Versión Actual en el JSON', 'json-version-manager' ); ?></h2>
				<p class="description" style="margin-bottom: 15px;">
					<?php esc_html_e( 'Esta es la versión que actualmente está sirviendo el archivo JSON. Puedes editarla en el formulario de abajo.', 'json-version-manager' ); ?>
				</p>
				<table class="widefat" style="margin-top: 10px;">
					<tbody>
						<tr style="background: #f0f6fc;">
							<td style="width: 250px; font-weight: bold; padding: 15px;">
								<?php esc_html_e( 'Versión del Plugin (actual):', 'json-version-manager' ); ?>
							</td>
							<td style="padding: 15px;">
								<strong style="color: #2271b1; font-size: 20px; font-weight: bold;">
									<?php echo esc_html( $current_version ); ?>
								</strong>
								<span style="margin-left: 10px; color: #666; font-size: 12px;">
									(<?php esc_html_e( 'Esta versión se está sirviendo actualmente', 'json-version-manager' ); ?>)
								</span>
							</td>
						</tr>
						<tr>
							<td style="font-weight: bold; padding: 15px;"><?php esc_html_e( 'Versión del Adaptador:', 'json-version-manager' ); ?></td>
							<td style="padding: 15px;">
								<strong><?php echo esc_html( $adapter_version ); ?></strong>
							</td>
						</tr>
						<tr>
							<td style="font-weight: bold; padding: 15px;"><?php esc_html_e( 'Versión Mínima Requerida:', 'json-version-manager' ); ?></td>
							<td style="padding: 15px;">
								<strong><?php echo esc_html( $min_adapter_version ); ?></strong>
								<span style="margin-left: 10px; color: #d63638; font-size: 12px;">
									<?php esc_html_e( '(Fuerza actualización si se aumenta)', 'json-version-manager' ); ?>
								</span>
							</td>
						</tr>
						<tr>
							<td style="font-weight: bold; padding: 15px;"><?php esc_html_e( 'Última Actualización:', 'json-version-manager' ); ?></td>
							<td style="padding: 15px;"><?php echo esc_html( $last_updated ); ?></td>
						</tr>
						<?php if ( $json_file_exists ) : ?>
							<tr>
								<td style="font-weight: bold; padding: 15px;"><?php esc_html_e( 'Estado del Archivo JSON:', 'json-version-manager' ); ?></td>
								<td style="padding: 15px;">
									<span style="color: green; font-weight: bold;">✓ <?php esc_html_e( 'Activo y sirviendo', 'json-version-manager' ); ?></span>
									<span style="margin-left: 20px; color: #666;">
										<?php echo esc_html( size_format( $json_file_size ) ); ?> | 
										<?php echo esc_html( $json_file_date ); ?>
									</span>
								</td>
							</tr>
						<?php else : ?>
							<tr>
								<td style="font-weight: bold; padding: 15px;"><?php esc_html_e( 'Estado del Archivo JSON:', 'json-version-manager' ); ?></td>
								<td style="padding: 15px;">
									<span style="color: red; font-weight: bold;">✗ <?php esc_html_e( 'No existe - Se creará al guardar', 'json-version-manager' ); ?></span>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px;">
				<h2><?php esc_html_e( 'Ubicación del Archivo JSON', 'json-version-manager' ); ?></h2>
				<p>
					<strong><?php esc_html_e( 'Ruta del archivo:', 'json-version-manager' ); ?></strong><br>
					<code style="background: #f0f0f1; padding: 5px 10px; border-radius: 3px; display: inline-block; margin-top: 5px;">
						<?php echo esc_html( JVM_JSON_FILE ); ?>
					</code>
				</p>
				<p>
					<strong><?php esc_html_e( 'URL pública del JSON:', 'json-version-manager' ); ?></strong><br>
					<code style="background: #f0f0f1; padding: 5px 10px; border-radius: 3px; display: inline-block; margin-top: 5px;">
						<?php echo esc_url( JVM_JSON_URL ); ?>
					</code>
					<button type="button" class="button button-small" onclick="navigator.clipboard.writeText('<?php echo esc_js( JVM_JSON_URL ); ?>'); alert('<?php esc_html_e( 'URL copiada al portapapeles', 'json-version-manager' ); ?>');">
						<?php esc_html_e( 'Copiar URL', 'json-version-manager' ); ?>
					</button>
				</p>
				<p class="description">
					<?php esc_html_e( 'El archivo JSON se guarda en la carpeta del plugin y es accesible públicamente desde la URL mostrada arriba.', 'json-version-manager' ); ?>
				</p>
				<?php if ( $json_file_exists ) : ?>
					<p>
						<a href="<?php echo esc_url( JVM_JSON_URL ); ?>" target="_blank" class="button button-secondary">
							<?php esc_html_e( 'Ver JSON en el navegador', 'json-version-manager' ); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>

			<div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px;">
				<h2><?php esc_html_e( 'Editar Versiones del JSON', 'json-version-manager' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Modifica las versiones que se servirán en el archivo JSON. Los cambios se aplicarán inmediatamente después de guardar.', 'json-version-manager' ); ?>
				</p>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 20px;">
				<?php wp_nonce_field( 'jvm_save_json', 'jvm_nonce' ); ?>
				<input type="hidden" name="action" value="jvm_save_json">

				<table class="form-table" role="presentation" style="background: #fff; border: 1px solid #ccd0d4;">
					<tr>
						<th scope="row">
							<label for="name"><?php esc_html_e( 'Nombre del Plugin', 'json-version-manager' ); ?></label>
						</th>
						<td>
							<input type="text" id="name" name="name" value="<?php echo esc_attr( $json_data['name'] ?? '' ); ?>" class="regular-text" required>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="slug"><?php esc_html_e( 'Slug', 'json-version-manager' ); ?></label>
						</th>
						<td>
							<input type="text" id="slug" name="slug" value="<?php echo esc_attr( $json_data['slug'] ?? '' ); ?>" class="regular-text" required>
						</td>
					</tr>

					<tr style="background: #f0f6fc; border-left: 4px solid #2271b1;">
						<th scope="row" style="padding: 15px;">
							<label for="version" style="font-size: 14px; font-weight: bold;">
								<?php esc_html_e( 'Versión del Plugin (Actualizar)', 'json-version-manager' ); ?>
							</label>
							<p class="description" style="margin-top: 5px; font-weight: normal;">
								<?php esc_html_e( 'Versión actual:', 'json-version-manager' ); ?>
								<strong style="color: #2271b1;"><?php echo esc_html( $current_version ); ?></strong>
							</p>
						</th>
						<td style="padding: 15px;">
							<input 
								type="text" 
								id="version" 
								name="version" 
								value="<?php echo esc_attr( $json_data['version'] ?? '1.0.0' ); ?>" 
								class="regular-text" 
								required
								style="font-size: 16px; font-weight: bold; padding: 8px; border: 2px solid #2271b1;"
								placeholder="1.0.0"
							>
							<p class="description" style="margin-top: 8px;">
								<strong><?php esc_html_e( 'Formato:', 'json-version-manager' ); ?></strong> X.Y.Z (ej: 1.0.0, 1.1.0, 2.0.0)
								<br>
								<span style="color: #d63638;">
									<?php esc_html_e( '⚠️ Esta versión será la que se sirva en el JSON público.', 'json-version-manager' ); ?>
								</span>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="adapter_version">
								<?php esc_html_e( 'Versión del Adaptador', 'json-version-manager' ); ?>
								<span style="color: #666; font-weight: normal; font-size: 12px;">
									(<?php esc_html_e( 'Actual:', 'json-version-manager' ); ?> <?php echo esc_html( $adapter_version ); ?>)
								</span>
							</label>
						</th>
						<td>
							<input type="text" id="adapter_version" name="adapter_version" value="<?php echo esc_attr( $json_data['adapter_version'] ?? '1.0.0' ); ?>" class="regular-text" required>
							<p class="description">
								<?php esc_html_e( 'Versión del adaptador STDIO que se distribuye.', 'json-version-manager' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="min_adapter_version">
								<?php esc_html_e( 'Versión Mínima del Adaptador', 'json-version-manager' ); ?>
								<span style="color: #666; font-weight: normal; font-size: 12px;">
									(<?php esc_html_e( 'Actual:', 'json-version-manager' ); ?> <?php echo esc_html( $min_adapter_version ); ?>)
								</span>
							</label>
						</th>
						<td>
							<input type="text" id="min_adapter_version" name="min_adapter_version" value="<?php echo esc_attr( $json_data['min_adapter_version'] ?? '1.0.0' ); ?>" class="regular-text" required>
							<p class="description">
								<strong style="color: #d63638;"><?php esc_html_e( '⚠️ Importante:', 'json-version-manager' ); ?></strong>
								<?php esc_html_e( 'Versión mínima requerida. Si la aumentas, fuerzas actualización del adaptador en todos los clientes.', 'json-version-manager' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="download_url"><?php esc_html_e( 'URL de Descarga', 'json-version-manager' ); ?></label>
						</th>
						<td>
							<input type="url" id="download_url" name="download_url" value="<?php echo esc_attr( $json_data['download_url'] ?? '' ); ?>" class="regular-text" required>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="requires_php"><?php esc_html_e( 'PHP Mínimo', 'json-version-manager' ); ?></label>
						</th>
						<td>
							<input type="text" id="requires_php" name="requires_php" value="<?php echo esc_attr( $json_data['requires_php'] ?? '8.0' ); ?>" class="small-text" required>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="requires_wordpress"><?php esc_html_e( 'WordPress Mínimo', 'json-version-manager' ); ?></label>
						</th>
						<td>
							<input type="text" id="requires_wordpress" name="requires_wordpress" value="<?php echo esc_attr( $json_data['requires_wordpress'] ?? '6.4' ); ?>" class="small-text" required>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="tested_up_to"><?php esc_html_e( 'Probado hasta', 'json-version-manager' ); ?></label>
						</th>
						<td>
							<input type="text" id="tested_up_to" name="tested_up_to" value="<?php echo esc_attr( $json_data['tested_up_to'] ?? '6.4' ); ?>" class="small-text">
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="changelog"><?php esc_html_e( 'Changelog', 'json-version-manager' ); ?></label>
						</th>
						<td>
							<?php
							$changelog = $json_data['sections']['changelog'] ?? '';
							wp_editor(
								$changelog,
								'changelog',
								array(
									'textarea_name' => 'changelog',
									'textarea_rows' => 10,
									'media_buttons' => false,
									'teeny'         => true,
								)
							);
							?>
							<p class="description"><?php esc_html_e( 'HTML permitido. Se mostrará en la página de actualizaciones de WordPress.', 'json-version-manager' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="php_files_hash"><?php esc_html_e( 'Hash SHA256 (Opcional)', 'json-version-manager' ); ?></label>
						</th>
						<td>
							<input type="text" id="php_files_hash" name="php_files_hash" value="<?php echo esc_attr( $json_data['php_files_hash'] ?? '' ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Hash SHA256 del archivo para verificación de integridad.', 'json-version-manager' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Guardar JSON', 'json-version-manager' ) ); ?>
			</form>

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
}

