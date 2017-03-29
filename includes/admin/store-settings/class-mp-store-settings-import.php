<?php
/**
 * Class MP_Store_Settings_Import
 *
 * @since   3.2.3
 * @package MarketPress
 */

if ( ! class_exists( 'MP_Store_Settings_Import' ) ) {
	return;
}

// Load WordPress export API.
require_once( ABSPATH . 'wp-admin/includes/export.php' );

/**
 * Process import/export form actions
 */
if ( ! empty( $_POST['mp-store-exporter'] ) ) { // Input var okay.
	if ( ! current_user_can( 'export' ) ) {
		return;
	}

	check_admin_referer( 'mp-store-export' );

	// Export settings to a file if all the security checks pass.
	if ( ! empty( $_POST['mp-store-export'] ) ) { // Input var okay.
		MP_Store_Settings_Import::download_export();
		die();
	}

	// Export products to a file if all the security checks pass.
	if ( ! empty( $_POST['mp-store-export-products'] ) ) { // Input var okay.
		$args['content'] = 'product';
		export_wp( $args );
		die();
	}

	// Import settings from a file.
	if ( ! empty( $_POST['mp-store-import'] ) ) { // Input var okay.
		// TODO: add warning message that data will be replaced.
		if ( ! empty( $_POST['mp-store-settings-text'] ) ) { // Input var okay.
			global $wpdb;
			$settings = base64_decode( $_POST['mp-store-settings-text'] );

			$wpdb->query( $wpdb->prepare( "
				UPDATE $wpdb->options
				SET option_value = %s
				WHERE option_name = 'mp_settings'
			", $settings ) );
		}
	}
}

class MP_Store_Settings_Import {
	/**
	 * Refers to a single instance of the class
	 *
	 * @since   3.2.3
	 * @access  private
	 * @var     object
	 */
	private static $_instance = null;

	/**
	 * Constructor function
	 *
	 * @since   3.2.3
	 * @access  private
	 */
	private function __construct() {

	}

	/**
	 * Gets the single instance of the class
	 *
	 * @since   3.2.3
	 * @access  public
	 * @return  object
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new MP_Store_Settings_Import();
		}
		return self::$_instance;
	}

	/**
	 * Gets the settings of the plugin
	 *
	 * Location settings, tax settings, currency settings, digital settings, download settings, miscellaneous settings
	 * and advanced settings.
	 *
	 * @since   3.2.3
	 * @access  private
	 * @param   string $option_name Where to find the plugin settings. Default 'mp_settings'.
	 * @return  string
	 */
	private static function get_settings( $option_name = 'mp_settings' ) {
		global $wpdb;

		$result = $wpdb->get_results( $wpdb->prepare( "
			SELECT option_value
			FROM $wpdb->options
			WHERE option_name = %s
		", $option_name ) );
		$settings = array_pop( $result );

		return $settings->option_value;
	}

	/**
	 * Check to see if the WordPress importer is installed
	 *
	 * First check if the WordPress importer is installed and activated. If not activated - we activate it and run it.
	 * If not install, we redirect to installation.
	 *
	 * @since   3.2.3
	 * @access  private
	 * @param   string $importer Slug for the importer. Default 'wordpress-importer'.
	 * @param   string $importer_name Name of the importer. Default 'WordPress'.
	 * @return  string $action Link for running the importer.
	 */
	private static function get_importer( $importer = 'wordpress-importer', $importer_name = 'WordPress' ) {
		if ( file_exists( WP_PLUGIN_DIR . '/' . $importer ) ) {
			// Looks like an importer is installed, but not active.
			$plugins = get_plugins( '/' . $importer );
			if ( ! empty( $plugins ) ) {
				$keys = array_keys( $plugins );
				$plugin_file = $importer . '/' . $keys[0];
				$url = wp_nonce_url( add_query_arg( array(
					'action' => 'activate',
					'plugin' => $plugin_file,
					'from'   => 'import',
				), admin_url( 'plugins.php' ) ), 'activate-plugin_' . $plugin_file );
				$action = sprintf(
					'<a href="%s" class="button-primary" aria-label="%s">%s</a>',
					esc_url( $url ),
					/* translators: %s: Importer name */
					esc_attr( sprintf( __( 'Run %s' ), $importer_name ) ),
					__( 'Run Importer' )
				);

				return $action;
			}
		}

		if ( empty( $action ) ) {
			if ( is_main_site() ) {
				$url = wp_nonce_url( add_query_arg( array(
					'action' => 'install-plugin',
					'plugin' => $importer,
					'from'   => 'import',
				), self_admin_url( 'update.php' ) ), 'install-plugin_' . $importer );
				$action = sprintf(
					'<a href="%1$s" class="install-now button-primary" data-slug="%2$s" data-name="%3$s" aria-label="%4$s">%5$s</a>',
					esc_url( $url ),
					esc_attr( $importer ),
					esc_attr( $importer_name ),
					/* translators: %s: Importer name */
					esc_attr( sprintf( __( 'Install %s' ), $importer_name ) ),
					__( 'Install Now' )
				);
			} else {
				$action = sprintf(
					/* translators: URL to wp-admin/import.php */
					__( 'This importer is not installed. Please install importers from <a href="%s">the main site</a>.' ),
					get_admin_url( get_current_network_id(), 'import.php' )
				);
			}
		}

		return $action;
	}

	/**
	 * Download export file
	 *
	 * @since   3.2.3
	 * @access  public
	 */
	public static function download_export() {
		$sitename = sanitize_key( get_bloginfo( 'name' ) );
		if ( ! empty( $sitename ) ) {
			$sitename .= '.';
		}
		$date = date( 'Y-m-d' );
		$wp_filename = $sitename . 'marketpress.' . $date . '.xml';
		/**
		 * WordPress filter
		 *
		 * Filters the export filename.
		 *
		 * @since 4.4.0
		 *
		 * @param string $wp_filename The name of the file for download.
		 * @param string $sitename    The site name.
		 * @param string $date        Today's date, formatted.
		 */
		$filename = apply_filters( 'export_wp_filename', $wp_filename, $sitename, $date );

		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );

		echo self::get_settings();
	}

	/**
	 * Display import/export page
	 *
	 * @since   3.2.3
	 * @access  public
	 */
	public function display_settings() {
		//var_dump( get_option( 'mp_settings' ) );
		//$option = unserialize( $this->get_settings() );
		//update_option( 'mp_settings', $option );

		$a = $this->get_settings();
		$a = base64_encode( $a );
		//var_dump( $a );

		//var_dump( $a );
		//$content = str_replace(' ', '&nbsp;', $this->get_settings());
		//$a = nl2br( $content ;
		//$a = base64_encode( nl2br( $content ) );
		?>
		<form method="post" id="mp-export-form">
			<?php wp_nonce_field( 'mp-store-export' ) ?>
			<input type="hidden" name="mp-store-exporter" value="export-action">

			<h2><?php esc_html_e( 'Import / Export Settings', 'mp' ); ?></h2>
			<p>
				<?php esc_html_e( 'Use the text below to export to a new installation. Or paste in the new configuration to import.', 'mp' ); ?>
			</p>
			<!--<textarea title="mp-store-settings-text" name="mp-store-settings-text" cols="100" rows="10"><?php //echo esc_textarea( $this->get_settings() ); ?></textarea><br>-->
			<textarea title="mp-store-settings-text" name="mp-store-settings-text" cols="100" rows="10"><?php echo esc_textarea($a); ?></textarea><br>

			<input type="submit" class="button button-primary" name="mp-store-import" id="mp-store-import" value="<?php esc_attr_e( 'Import configuration', 'mp' ); ?>">
			<input type="submit" class="button" name="mp-store-export" id="mp-store-export" value="<?php esc_attr_e( 'Export settings to file', 'mp' ); ?>">
			<h2><?php esc_html_e( 'Import / Export Products', 'mp' ); ?></h2>
			<p>
				<?php esc_html_e( 'The import process uses the WordPress importer plugin.', 'mp' ); ?>
			</p>
			<?php echo $this::get_importer(); ?>
			<input type="submit" class="button" name="mp-store-export-products" id="mp-store-export-products" value="<?php esc_attr_e( 'Export products to file', 'mp' ); ?>">
		</form>
		<?php
	}


}

MP_Store_Settings_Import::get_instance();
