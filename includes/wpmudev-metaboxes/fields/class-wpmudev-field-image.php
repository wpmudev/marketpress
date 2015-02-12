<?php

class WPMUDEV_Field_Image extends WPMUDEV_Field {

	/**
	 * Runs on creation of parent
	 *
	 * @since 1.0
	 * @access public
	 * 
	 * @param array $args {
	 * 		An array of arguments. Optional.
	 *
	 * 		@type string $preview_size The preview size of the image in wp-admin.
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = array_replace_recursive( array(
			'preview_size' => 'thumbnail',
		), $args );
	}

	/**
	 * Enqueues necessary field javascript.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'media-upload' );

		// 3.5 media gallery
		if ( function_exists( 'wp_enqueue_media' ) && !did_action( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}
	}

	/**
	 * Print necessary field javascript.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function print_scripts() {
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				var buttonHtml = '<a class="button wpmudev-image-field-select" href="javascript:;"><?php _e( 'Select Image', 'wpmudev_metaboxes' ); ?></a>';

				/*
				 * When adding a new group to the repeater field reset the image preview back to a button
				 */
				$( document ).on( 'wpmudev_repeater_field/after_add_field_group', function( e, $group ) {
					$group.find( '.wpmudev-image-field-preview' ).replaceWith( buttonHtml )
				} );

				/*
				 * Delete image
				 */
				$( '.wpmudev-fields' ).on( 'click', '.wpmudev-image-field-delete', function( e ) {
					e.preventDefault();

					var $this = $( this ),
						$parent = $this.parent();

					$parent.siblings( ':hidden' ).val( '' );
					$parent.replaceWith( buttonHtml );
				} );

				/*
				 * Show the media library popup
				 */
				$( '.wpmudev-fields' ).on( 'click', '.wpmudev-image-field-select', function( e ) {
					e.preventDefault();

					var $this = $( this ),
						$input = ( $this.hasClass( 'wpmudev-image-field-edit' ) ) ? $this.parent().siblings( ':hidden' ) : $this.siblings( ':hidden' ),
						frame = wp.media( {
							"title": "<?php _e( 'Select the image that you would like to use for this variation.', 'wpmudev_metaboxes' ); ?>",
							"multiple": false,
							"library": { "type": "image" },
							"button": { "text": "<?php _e( 'Select Image', 'wpmudev_metaboxes' ); ?>" }
						} );

					/*
					 * Send image data back to the calling field
					 */
					frame.on( 'select', function() {
						var selection = frame.state().get( 'selection' );

						selection.each( function( attachment ) {
							var url = attachment.attributes.sizes.hasOwnProperty( '<?php echo $this->args[ 'preview_size' ]; ?>' ) ? attachment.attributes.sizes['<?php echo $this->args[ 'preview_size' ]; ?>'].url : attachment.attributes.sizes.thumbnail.url,
								html = '<div class="wpmudev-image-field-preview"><a class="wpmudev-image-field-edit wpmudev-image-field-select dashicons dashicons-edit" href="#"></a><a class="wpmudev-image-field-delete dashicons dashicons-trash" href="#"></a><img src="' + url + '" alt="" /></div>';

							if ( $this.hasClass( 'wpmudev-image-field-edit' ) ) {
								$this.parent().replaceWith( html );
							} else {
								$this.replaceWith( html );
							}

							$input.val( attachment.id );
						} );
					} );

					/*
					 * Set the selected image
					 */
					frame.on( 'open', function() {
						var selection = frame.state().get( 'selection' ),
							id = $input.val();

						if ( id.length ) {
							var attachment = wp.media.attachment( id );
							attachment.fetch();
							selection.add( attachment ? [ attachment ] : [ ] );
						}
					} );

					frame.open();
				} );
			} );
		</script>
		<?php
	}

	/**
	 * Displays the field.
	 *
	 * @since 1.0
	 * @access public
	 * @param int $post_id
	 */
	public function display( $post_id ) {
		$value = $this->get_value( $post_id );

		$this->before_field();

		if ( $value ) :
			$img_url = wp_get_attachment_image_src( $value, $this->args[ 'preview_size' ] );
			?>
			<div class="wpmudev-image-field-preview"><a class="wpmudev-image-field-edit wpmudev-image-field-select dashicons dashicons-edit" href="#"></a><a class="wpmudev-image-field-delete dashicons dashicons-trash" href="#"></a><img src="<?php echo $img_url[ 0 ]; ?>" alt="" /></div>
			<?php else :
			?>
			<a class="button wpmudev-image-field-select" href="javascript:;"><?php _e( 'Select Image', 'wpmudev_metaboxes' ); ?></a>
		<?php endif; ?>

		<input type="hidden" <?php echo $this->parse_atts(); ?> value="<?php echo $value; ?>" />
		<?php
		$this->after_field();
	}

}
