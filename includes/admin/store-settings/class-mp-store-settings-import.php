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

/**
 * Export to file if all the security checks pass
 */
if ( ! empty( $_POST['mp-store-export'] ) ) { // Input var okay.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	check_admin_referer( 'mp-store-export' );

	MP_Store_Settings_Import::download_export();
	die();
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
	 * @return  string
	 */
	private static function get_settings() {
		global $wpdb;

		$result = $wpdb->get_results( "SELECT option_value FROM {$wpdb->options} WHERE option_name = 'mp_settings'", OBJECT );
		$options = array_pop( $result );

		return $options->option_value;
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

		?><form method="post">
		<p>
			<?php _e( 'Use the text below to import to a new installation.', 'mp' ); ?>
		</p>
		<textarea title="mp-store-settings-text" cols="100" rows="10"><?php echo $this->get_settings(); ?></textarea><br>
		<?php

		?>

			<?php wp_nonce_field( 'mp-store-export' ) ?>
			<input type="submit" class="button" name="mp-store-export" id="mp-store-export" value="<?php _e( 'Export to file', 'mp' ); ?>">
		</form><?php
	}


}

MP_Store_Settings_Import::get_instance();
