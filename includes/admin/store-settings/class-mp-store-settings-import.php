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
		add_action( 'init', array( $this, 'process_form' ) );

		// Process variation terms
		add_filter( 'wp_import_terms', array( $this, 'process_taxonomies' ) );

		// Process posts that are marked as duplicates
		add_filter( 'wp_import_existing_post', array( $this, 'process_duplicates' ), 10, 2 );

		// Process attributes for variable products
		add_filter( 'wp_import_post_meta', array( $this, 'process_products' ), 10, 2 );
	}

	/**
	 * Hook into the import process to create missing taxonomies.
	 *
	 * Here we filter through all terms and see if WordPress has a registered taxonomy for the term. If there is
	 * a taxonomy (usually there is one for tags and categories) - we pass it on to WordPress importer. If no
	 * texonomy is found, that means it is an attribute for a variable product and we need to add it manually.
	 *
	 * @since  3.2.4
	 * @param  array $terms  Array of WP_Term objects.
	 * @return array $terms  Array of WP_Term objects.
	 */
	public function process_taxonomies( $terms ) {

		foreach ( $terms as $term_key => $term ) {

			if ( ! taxonomy_exists( $term['term_taxonomy'] ) ) {
				// We will process this manually.
				unset( $terms[ $term_key ] );

				// Add taxonomy to wp_mp_product_attributes
				$taxonomy = MP_Products_Screen::maybe_create_attribute( $term['term_taxonomy'], $term['term_taxonomy'] );

				// Add term to wp_terms
				if ( taxonomy_exists( $taxonomy ) && ! term_exists( $term['term_name'], $taxonomy ) ) {
					// TODO: do not make duplicates
					wp_insert_term( $term['term_name'], $taxonomy );
				}
			} // End if().
		} // End foreach().

		// Everything else can be parsed by WordPress importer.
		return $terms;

	}

	/**
	 * Process duplicate entries.
	 *
	 * Market Press, when creating variations will create the posts in the database with the same name and data.
	 * During import these posts will be treated as duplicates and not be imported. We need to manually add them
	 * to the database.
	 *
	 * @since  3.2.4
	 * @param  int   $post_exists  Post ID, or 0 if post did not exist.
	 * @param  array $post         The post array to be inserted.
	 * @return int   $post_exists  Post ID, or 0 if post did not exist.
	 */
	public function process_duplicates( $post_exists, $post ) {

		if ( null === get_post( $post['post_id'] ) && 'mp_product_variation' === $post['post_type'] ) {
			//var_dump( 'duplicate post: ' . $post_exists );
			//var_dump( $post );
			$post_exists = 0;
		}

		return $post_exists;
	}

	/**
	 * Process attributes for variable products.
	 *
	 * @since  3.2.4
	 * @param  array  $metakeys  Post meta data.
	 * @param  int    $post_id   Post ID.
	 * @return array
	 */
	public function process_products( $metakeys, $post_id ) {
		// TODO: value can consist of several keys
		foreach ( $metakeys as $meta ) {
			if ( 'name' == $meta['key'] ) {

				$taxonomies = get_taxonomies();
				foreach ( $taxonomies as $tax_type_key => $taxonomy ) {
					// If term object is returned, break out of loop. (Returns false if there's no object)
					if ( $term_object = get_term_by( 'name', $meta['value'] , $taxonomy ) ) {
						wp_set_object_terms( $post_id, $term_object->term_id, $term_object->taxonomy );
						break;
					}
				}
			}
		}

		return $metakeys;

	}

	/**
	 * Gets the single instance of the class
	 *
	 * @since   3.2.3
	 * @return  object
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new MP_Store_Settings_Import();
		}
		return self::$_instance;
	}

	/**
	 * Process import/export form actions
	 *
	 * @since   3.2.3
	 */
	public static function process_form() {
		if ( ! empty( $_POST['mp-store-exporter'] ) ) { // Input var okay.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			check_admin_referer( 'mp-store-export' );

			// Export settings to a file if all the security checks pass.
			if ( ! empty( $_POST['mp-store-export'] ) ) { // Input var okay.
				self::prepare_heder();
				echo self::get_settings();
				die();
			}

			// Export products to a file if all the security checks pass.
			if ( ! empty( $_POST['mp-store-export-products'] ) ) { // Input var okay.
				self::export_products();
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
					esc_attr( sprintf( __( 'Run %s', 'mp' ), $importer_name ) ),
					__( 'Run Importer', 'mp' )
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
					esc_attr( sprintf( __( 'Install %s', 'mp' ), $importer_name ) ),
					__( 'Install Now', 'mp' )
				);
			} else {
				$action = sprintf(
					/* translators: URL to wp-admin/import.php */
					__( 'This importer is not installed. Please install importers from <a href="%s">the main site</a>.', 'mp' ),
					get_admin_url( get_current_network_id(), 'import.php' )
				);
			}
		}

		return $action;
	}

	/**
	 * Prepare header for export.
	 *
	 * @since   3.2.3
	 * @access  private
	 */
	private static function prepare_heder() {
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
	}

	/**
	 * Display import/export page
	 *
	 * @since   3.2.3
	 */
	public function display_settings() {
		$options = base64_encode( $this->get_settings() );
		?>
		<form method="post" id="mp-export-form">
			<?php wp_nonce_field( 'mp-store-export' ) ?>
			<input type="hidden" name="mp-store-exporter" value="export-action">

			<h2><?php esc_html_e( 'Import / Export Settings', 'mp' ); ?></h2>
			<p>
				<?php esc_html_e( 'Use the text below to export to a new installation. Or paste in the new configuration to import.', 'mp' ); ?>
			</p>
			<textarea title="mp-store-settings-text" name="mp-store-settings-text" cols="100" rows="10"><?php echo esc_textarea( $options ); ?></textarea><br>

			<input type="submit" class="button button-primary" name="mp-store-import" id="mp-store-import" value="<?php esc_attr_e( 'Import configuration', 'mp' ); ?>">
			<input type="submit" class="button" name="mp-store-export" id="mp-store-export" value="<?php esc_attr_e( 'Export settings to file', 'mp' ); ?>">
			<h2><?php esc_html_e( 'Import / Export Products', 'mp' ); ?></h2>
			<p>
				<?php esc_html_e( 'The import process uses the WordPress importer plugin.', 'mp' ); ?>
			</p>
			<?php echo self::get_importer(); ?>
			<input type="submit" class="button" name="mp-store-export-products" id="mp-store-export-products" value="<?php esc_attr_e( 'Export products to file', 'mp' ); ?>">
		</form>
		<?php
	}

	/**
	 * Generates the WXR export file for download.
	 *
	 * @since 2.1.0
	 * @access private
	 *
	 * @global wpdb    $wpdb WordPress database abstraction object.
	 * @global WP_Post $post Global `$post`.
	 */
	private static function export_products() {
		global $wpdb, $post;

		$args = array(
			'author'     => false,
			'category'   => false,
			'start_date' => false,
			'end_date'   => false,
			'status'     => false,
		);

		/**
		 * Fires at the beginning of an export, before any headers are sent.
		 *
		 * @since 2.3.0
		 *
		 * @param array $args An array of export arguments.
		 */
		do_action( 'export_wp', $args );

		self::prepare_heder();

		$where = $wpdb->prepare( "{$wpdb->posts}.post_type = %s", 'product' );
		$where .= " OR {$wpdb->posts}.post_type = 'mp_product_variation'";

		// Grab a snapshot of post IDs, just in case it changes during the export.
		$post_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE $where" );

		/*
		 * Get the requested terms ready, empty unless posts filtered by category
		 * or all content.
		 */
		$terms = array();

		$custom_taxonomies = get_taxonomies( array(
			'_builtin' => false,
		) );
		$custom_terms = (array) get_terms( $custom_taxonomies, array(
			'get' => 'all',
		) );

		// Put terms in order with no child going before its parent.
		while ( $t = array_shift( $custom_terms ) ) {
			if ( 0 == $t->parent || isset( $terms[ $t->parent ] ) ) {
				$terms[ $t->term_id ] = $t;
			} else {
				$custom_terms[] = $t;
			}
		}

		unset( $custom_taxonomies, $custom_terms );

/**
 * Wrap given string in XML CDATA tag.
 *
 * @since 2.1.0
 *
 * @param string $str String to wrap in XML CDATA tag.
 * @return string
 */
function wxr_cdata( $str ) {
	if ( ! seems_utf8( $str ) ) {
		$str = utf8_encode( $str );
	}
	// $str = ent2ncr(esc_html($str));
	$str = '<![CDATA[' . str_replace( ']]>', ']]]]><![CDATA[>', $str ) . ']]>';

	return $str;
}

/**
 * Return the URL of the site
 *
 * @since 2.5.0
 *
 * @return string Site URL.
 */
function wxr_site_url() {
	// Multisite: the base URL.
	if ( is_multisite() )
		return network_home_url();
	// WordPress (single site): the blog URL.
	else
		return get_bloginfo_rss( 'url' );
}

/**
 * Output a term_name XML tag from a given term object
 *
 * @since 2.9.0
 *
 * @param object $term Term Object
 */
function wxr_term_name( $term ) {
	if ( empty( $term->name ) )
		return;

	echo '<wp:term_name>' . wxr_cdata( $term->name ) . "</wp:term_name>\n";
}

/**
 * Output a term_description XML tag from a given term object
 *
 * @since 2.9.0
 *
 * @param object $term Term Object
 */
function wxr_term_description( $term ) {
	if ( empty( $term->description ) )
		return;

	echo "\t\t<wp:term_description>" . wxr_cdata( $term->description ) . "</wp:term_description>\n";
}

/**
 * Output term meta XML tags for a given term object.
 *
 * @since 4.6.0
 *
 * @param WP_Term $term Term object.
 */
function wxr_term_meta( $term ) {
	global $wpdb;

	$termmeta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->termmeta WHERE term_id = %d", $term->term_id ) );

	foreach ( $termmeta as $meta ) {
		/**
		 * Filters whether to selectively skip term meta used for WXR exports.
		 *
		 * Returning a truthy value to the filter will skip the current meta
		 * object from being exported.
		 *
		 * @since 4.6.0
		 *
		 * @param bool   $skip     Whether to skip the current piece of term meta. Default false.
		 * @param string $meta_key Current meta key.
		 * @param object $meta     Current meta object.
		 */
		if ( ! apply_filters( 'wxr_export_skip_termmeta', false, $meta->meta_key, $meta ) ) {
			printf( "\t\t<wp:termmeta>\n\t\t\t<wp:meta_key>%s</wp:meta_key>\n\t\t\t<wp:meta_value>%s</wp:meta_value>\n\t\t</wp:termmeta>\n", wxr_cdata( $meta->meta_key ), wxr_cdata( $meta->meta_value ) );
		}
	}
}

/**
 * Output list of authors with posts
 *
 * @since 3.1.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param array $post_ids Array of post IDs to filter the query by. Optional.
 */
function wxr_authors_list( array $post_ids = null ) {
	global $wpdb;

	if ( !empty( $post_ids ) ) {
		$post_ids = array_map( 'absint', $post_ids );
		$and = 'AND ID IN ( ' . implode( ', ', $post_ids ) . ')';
	} else {
		$and = '';
	}

	$authors = array();
	$results = $wpdb->get_results( "SELECT DISTINCT post_author FROM $wpdb->posts WHERE post_status != 'auto-draft' $and" );
	foreach ( (array) $results as $result )
		$authors[] = get_userdata( $result->post_author );

	$authors = array_filter( $authors );

	foreach ( $authors as $author ) {
		echo "\t<wp:author>";
		echo '<wp:author_id>' . intval( $author->ID ) . '</wp:author_id>';
		echo '<wp:author_login>' . wxr_cdata( $author->user_login ) . '</wp:author_login>';
		echo '<wp:author_email>' . wxr_cdata( $author->user_email ) . '</wp:author_email>';
		echo '<wp:author_display_name>' . wxr_cdata( $author->display_name ) . '</wp:author_display_name>';
		echo '<wp:author_first_name>' . wxr_cdata( $author->first_name ) . '</wp:author_first_name>';
		echo '<wp:author_last_name>' . wxr_cdata( $author->last_name ) . '</wp:author_last_name>';
		echo "</wp:author>\n";
	}
}

/**
 * Output list of market press product attributes in XML tag format
 *
 * @since 3.2.4
 */
/*
function wxr_product_attributes() {
	global $wpdb;

	$attributes = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'mp_product_attributes' );

	foreach ( $attributes as $attr ) {
		echo "\t<mp:attributes>";
		echo '<mp:attribute_id>' . intval( $attr->attribute_id ) . '</mp:attribute_id>';
		echo '<mp:attribute_name>' . wxr_cdata( $attr->attribute_name ) . '</mp:attribute_name>';
		echo '<mp:attribute_terms_sort_by>' . wxr_cdata( $attr->attribute_terms_sort_by ) . '</mp:attribute_terms_sort_by>';
		echo '<mp:attribute_terms_sort_order>' . wxr_cdata( $attr->attribute_terms_sort_order ) . '</mp:attribute_terms_sort_order>';
		echo "</mp:attributes>\n";
	}
}
*/

/**
 * Output list of taxonomy terms, in XML tag format, associated with a post
 *
 * @since 2.3.0
 */
function wxr_post_taxonomy() {
	$post = get_post();

	$taxonomies = get_object_taxonomies( $post->post_type );
	if ( empty( $taxonomies ) )
		return;
	$terms = wp_get_object_terms( $post->ID, $taxonomies );

	foreach ( (array) $terms as $term ) {
		echo "\t\t<category domain=\"{$term->taxonomy}\" nicename=\"{$term->slug}\">" . wxr_cdata( $term->name ) . "</category>\n";
	}
}

/**
 *
 * @param bool   $return_me
 * @param string $meta_key
 * @return bool
 */
function wxr_filter_postmeta( $return_me, $meta_key ) {
	if ( '_edit_lock' == $meta_key )
		$return_me = true;
	return $return_me;
}
add_filter( 'wxr_export_skip_postmeta', 'wxr_filter_postmeta', 10, 2 );

echo '<?xml version="1.0" encoding="' . get_bloginfo('charset') . "\" ?>\n"; ?>
<?php the_generator( 'export' ); ?>
<rss version="2.0"
	 xmlns:excerpt="http://wordpress.org/export/<?php echo WXR_VERSION; ?>/excerpt/"
	 xmlns:content="http://purl.org/rss/1.0/modules/content/"
	 xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	 xmlns:dc="http://purl.org/dc/elements/1.1/"
	 xmlns:wp="http://wordpress.org/export/<?php echo WXR_VERSION; ?>/"
>

	<channel>
		<title><?php bloginfo_rss( 'name' ); ?></title>
		<link><?php bloginfo_rss( 'url' ); ?></link>
		<description><?php bloginfo_rss( 'description' ); ?></description>
		<pubDate><?php echo date( 'D, d M Y H:i:s +0000' ); ?></pubDate>
		<language><?php bloginfo_rss( 'language' ); ?></language>
		<wp:wxr_version><?php echo WXR_VERSION; ?></wp:wxr_version>
		<wp:base_site_url><?php echo wxr_site_url(); ?></wp:base_site_url>
		<wp:base_blog_url><?php bloginfo_rss( 'url' ); ?></wp:base_blog_url>

		<?php wxr_authors_list( $post_ids ); ?>

		<?php foreach ( $terms as $t ) : ?>
			<wp:term>
				<wp:term_id><?php echo wxr_cdata( $t->term_id ); ?></wp:term_id>
				<wp:term_taxonomy><?php echo wxr_cdata( $t->taxonomy ); ?></wp:term_taxonomy>
				<wp:term_slug><?php echo wxr_cdata( $t->slug ); ?></wp:term_slug>
				<wp:term_parent><?php echo wxr_cdata( $t->parent ? $terms[$t->parent]->slug : '' ); ?></wp:term_parent>
				<?php wxr_term_name( $t );
				wxr_term_description( $t );
				wxr_term_meta( $t ); ?>
			</wp:term>
		<?php endforeach; ?>

		<?php
		/** This action is documented in wp-includes/feed-rss2.php */
		do_action( 'rss2_head' );
		?>

		<?php if ( $post_ids ) {
			/**
			 * @global WP_Query $wp_query
			 */
			global $wp_query;

			// Fake being in the loop.
			$wp_query->in_the_loop = true;

			// Fetch 20 posts at a time rather than loading the entire table into memory.
			while ( $next_posts = array_splice( $post_ids, 0, 20 ) ) {
				$where = 'WHERE ID IN (' . join( ',', $next_posts ) . ')';
				$posts = $wpdb->get_results( "SELECT * FROM {$wpdb->posts} $where" );

				// Begin Loop.
				foreach ( $posts as $post ) {
					setup_postdata( $post );
					$is_sticky = is_sticky( $post->ID ) ? 1 : 0;
					?>
					<item>
						<title><?php
							/** This filter is documented in wp-includes/feed.php */
							echo apply_filters( 'the_title_rss', $post->post_title );
							?></title>
						<link><?php the_permalink_rss() ?></link>
						<pubDate><?php echo mysql2date( 'D, d M Y H:i:s +0000', get_post_time( 'Y-m-d H:i:s', true ), false ); ?></pubDate>
						<dc:creator><?php echo wxr_cdata( get_the_author_meta( 'login' ) ); ?></dc:creator>
						<guid isPermaLink="false"><?php the_guid(); ?></guid>
						<description></description>
						<content:encoded><?php
							/**
							 * Filters the post content used for WXR exports.
							 *
							 * @since 2.5.0
							 *
							 * @param string $post_content Content of the current post.
							 */
							echo wxr_cdata( apply_filters( 'the_content_export', $post->post_content ) );
							?></content:encoded>
						<excerpt:encoded><?php
							/**
							 * Filters the post excerpt used for WXR exports.
							 *
							 * @since 2.6.0
							 *
							 * @param string $post_excerpt Excerpt for the current post.
							 */
							echo wxr_cdata( apply_filters( 'the_excerpt_export', $post->post_excerpt ) );
							?></excerpt:encoded>
						<wp:post_id><?php echo intval( $post->ID ); ?></wp:post_id>
						<wp:post_date><?php echo wxr_cdata( $post->post_date ); ?></wp:post_date>
						<wp:post_date_gmt><?php echo wxr_cdata( $post->post_date_gmt ); ?></wp:post_date_gmt>
						<wp:comment_status><?php echo wxr_cdata( $post->comment_status ); ?></wp:comment_status>
						<wp:ping_status><?php echo wxr_cdata( $post->ping_status ); ?></wp:ping_status>
						<wp:post_name><?php echo wxr_cdata( $post->post_name ); ?></wp:post_name>
						<wp:status><?php echo wxr_cdata( $post->post_status ); ?></wp:status>
						<wp:post_parent><?php echo intval( $post->post_parent ); ?></wp:post_parent>
						<wp:menu_order><?php echo intval( $post->menu_order ); ?></wp:menu_order>
						<wp:post_type><?php echo wxr_cdata( $post->post_type ); ?></wp:post_type>
						<wp:post_password><?php echo wxr_cdata( $post->post_password ); ?></wp:post_password>
						<wp:is_sticky><?php echo intval( $is_sticky ); ?></wp:is_sticky>
						<?php	if ( $post->post_type == 'attachment' ) : ?>
							<wp:attachment_url><?php echo wxr_cdata( wp_get_attachment_url( $post->ID ) ); ?></wp:attachment_url>
						<?php 	endif; ?>
						<?php 	wxr_post_taxonomy(); ?>
						<?php	$postmeta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE post_id = %d", $post->ID ) );
						foreach ( $postmeta as $meta ) :
							/**
							 * Filters whether to selectively skip post meta used for WXR exports.
							 *
							 * Returning a truthy value to the filter will skip the current meta
							 * object from being exported.
							 *
							 * @since 3.3.0
							 *
							 * @param bool   $skip     Whether to skip the current post meta. Default false.
							 * @param string $meta_key Current meta key.
							 * @param object $meta     Current meta object.
							 */
							if ( apply_filters( 'wxr_export_skip_postmeta', false, $meta->meta_key, $meta ) )
								continue;
							?>
							<wp:postmeta>
								<wp:meta_key><?php echo wxr_cdata( $meta->meta_key ); ?></wp:meta_key>
								<wp:meta_value><?php echo wxr_cdata( $meta->meta_value ); ?></wp:meta_value>
							</wp:postmeta>
						<?php	endforeach; ?>
					</item>
					<?php
				}
			}
		} ?>
	</channel>
</rss>
<?php
	}


}

MP_Store_Settings_Import::get_instance();
