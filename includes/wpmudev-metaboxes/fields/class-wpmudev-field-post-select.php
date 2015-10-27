<?php

add_action( 'wp_ajax_wpmudev_field_post_select_search_posts', array( 'WPMUDEV_Field_Post_Select', 'search_posts' ) );

class WPMUDEV_Field_Post_Select extends WPMUDEV_Field {

	/**
	 * Runs on parent construct
	 *
	 * @since 1.0
	 * @access public
	 * @param array $args {
	 * 		An array of arguments.
	 *
	 * 		@type array $query @see WP_Query
	 * 		@type bool $multiple True, if selection of multiple posts is allowed.
	 * 		@type string $placeholder The text that shows up in the field before any posts are selected
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = array_replace_recursive( array(
			'query'			 => array(),
			'multiple'		 => false,
			'placeholder'	 => __( 'Select Posts', 'mp' )
		), $args );

		$this->args[ 'class' ] .= ' wpmudev-post-select';
		$this->args[ 'custom' ][ 'data-placeholder' ]	 = $this->args[ 'placeholder' ];
		$this->args[ 'custom' ][ 'data-multiple' ]		 = (int) $this->args[ 'multiple' ];
		$this->args[ 'custom' ][ 'data-query' ]			 = http_build_query( $this->args[ 'query' ] );
	}

	/**
	 * Formats the field value for display.
	 *
	 * @since 1.0
	 * @access public
	 * @param mixed $value
	 * @param mixed $post_id
	 */
	public function format_value( $value, $post_id ) {
		if( ! is_array( $value ) ) {
			$values = explode( ',', $value );
		} else {
			$values = $value;
		}
		
		return parent::format_value( $values, $post_id );
	}

	/**
	 * Searches posts
	 *
	 * @since 1.0
	 * @access public
	 * @action wp_ajax_wpmudev_search_posts
	 */
	public static function search_posts() {
		add_filter( 'posts_search', array( __CLASS__, 'search_by_title_only' ), 500, 2 );

		parse_str( $_GET[ 'query' ], $args );

		$args = array_replace_recursive( array(
			'posts_per_page' => get_option( 'posts_per_page' ),
		), $args );

		$query	 = new WP_Query( array_replace_recursive( array(
			'paged'					 => mp_arr_get_value( 'page', $_GET ),
			'posts_per_page'		 => $args[ 'posts_per_page' ],
			's'						 => mp_arr_get_value( 'search_term', $_GET ),
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'orderby'				 => 'title',
			'order'					 => 'ASC',
			'post_status'			 => array( 'publish' ),
		), $args ) );
		$data	 = array( 'posts' => array(), 'posts_per_page' => $args[ 'posts_per_page' ], 'total' => $query->found_posts );

		while ( $query->have_posts() ) : $query->the_post();
			$data[ 'posts' ][] = array( 'id' => get_the_ID(), 'text' => get_the_title() );
		endwhile;

		wp_send_json( $data );
	}

	/**
	 * Search by title only
	 *
	 * @since 3.0
	 * @access public
	 * @filter posts_search
	 * @param string $search
	 * @param WP_Query $wp_query
	 * @return string
	 */
	public static function search_by_title_only( $search, &$wp_query ) {
		global $wpdb;

		if ( empty( $search ) ) {
			return $search; // skip processing - no search term in query
		}

		$q			 = $wp_query->query_vars;
		$n			 = !empty( $q[ 'exact' ] ) ? '' : '%';
		$search		 = '';
		$searchand	 = '';

		foreach ( (array) $q[ 'search_terms' ] as $term ) {
			$term		 = esc_sql( $wpdb->esc_like( $term ) );
			$search .= "{$searchand}($wpdb->posts.post_title LIKE '{$n}{$term}{$n}')";
			$searchand	 = ' AND ';
		}

		if ( !empty( $search ) ) {
			$search = " AND ({$search}) ";
			if ( !is_user_logged_in() ) {
				$search .= " AND ($wpdb->posts.post_password = '') ";
			}
		}

		return $search;
	}

	/**
	 * Prints scripts
	 *
	 * @since 3.0
	 * @access public
	 */
	public function print_scripts() {
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				$( 'input.wpmudev-post-select' ).each( function() {
					var $this = $( this ),
						multiple = Boolean( parseInt( $this.attr( 'data-multiple' ) ) ),
						args = {
							"multiple": multiple,
							"placeholder": $this.attr( 'data-placeholder' ),
							"initSelection": function( element, callback ) {
								if ( multiple ) {
									var data = [ ];
									$( element.attr( 'data-select2-value' ).split( '||' ) ).each( function() {
										var val = this.split( '->' );
										data.push( { "id": val[0], "text": val[1] } );
									} );
								} else {
									var val = $this.attr( 'data-select2-value' ).split( '->' );
									var data = { "id": val[0], "text": val[1] };
								}

								callback( data );
							},
							"ajax": {
								"url": ajaxurl,
								"dataType": "json",
								"data": function( term, page ) {
									return {
										"search_term": term,
										"page": page,
										"query": $this.attr( 'data-query' ),
										"action": "wpmudev_field_post_select_search_posts"
									}
								},
								"results": function( data, page ) {
									var more = ( page * data.post_per_page ) < data.total;
									return {
										"results": data.posts,
										"more": more
									}
								}
							}
						};

					if ( multiple ) {
						args.width = '100%';
					} else {
						args.dropdownAutoWidth = true;
					}

					$this.mp_select2( args );
				} );

				$( document ).on( 'wpmudev_repeater_field/before_add_field_group', function() {
					$( '.wpmudev-post-select' ).mp_select2( 'destroy' );
					$( '[id^="s2id_"]' ).remove(); // Remove select2 autogenerated elements. For some reason there is a bug in the destroy method.
				} );

			} );
		</script>
		<?php

		parent::print_scripts();
	}

	/**
	 * Displays the field
	 *
	 * @since 1.0
	 * @access public
	 * @param int $post_id
	 */
	public function display( $post_id ) {
		$value	 = $this->get_value( $post_id );
		$data	 = array();
		$ids	 = is_array( $value ) ? $value : explode( ',', $value );

		foreach ( $ids as $id ) {
			$data[] = $id . '->' . get_the_title( $id );
		}

		$this->args[ 'custom' ][ 'data-select2-value' ] = implode( '||', $data );
		$this->before_field();
		echo '<input type="hidden" ' . $this->parse_atts() . ' value="' . implode( ',', $ids ) . '" />';
		$this->after_field();
	}

	/**
	 * Enqueues the field's scripts
	 *
	 * @since 1.0
	 * @access public
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'wpmudev-field-select2', WPMUDEV_Metabox::class_url( 'ui/select2/select2.min.js' ), array( 'jquery' ), WPMUDEV_METABOX_VERSION );
	}

	/**
	 * Enqueues the field's styles
	 *
	 * @since 1.0
	 * @access public
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'wpmudev-field-select2', WPMUDEV_Metabox::class_url( 'ui/select2/select2.css' ), array(), WPMUDEV_METABOX_VERSION );
	}

}