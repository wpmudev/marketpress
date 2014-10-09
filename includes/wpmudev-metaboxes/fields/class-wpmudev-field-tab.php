<?php

class WPMUDEV_Field_Tab extends WPMUDEV_Field {
	/**
	 * Runs on creation of parent
	 *
	 * @since 1.0
	 * @access public
	 * 
	 * @param array $args {
	 *		An array of arguments. Optional.
	 *
	 *		@type string $slug The tab slug.
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = array_replace_recursive(array(
			'slug' => '',
		), $args);
	}

	/**
	 * Saves the field to the database.
	 *
	 * @since 1.0
	 * @access public
	 * @action save_post
	 * @param int $post_id
	 * @param string $meta_key The meta key to use when storing the field value. Defaults to null.
	 * @param mixed $value The value of the field. Defaults to null.
	 * @param bool $force Whether to bypass the is_subfield check. Subfields normally don't run their own save routine. Defaults to false.
	 */
	public function save_value( $post_id, $meta_key = null, $value = null, $force = false ) {
		// Don't save to db
	}

	/**
	 * Display the field
	 *
	 * @since 1.0
	 * @access public
	 * @param int/string $post_id
	 */
	public function display( $post_id ) {
		?>
<input type="hidden" <?php echo $this->parse_atts(); ?> value="" />
<a class="wpmudev-field-tab-anchor" name="<?php echo $this->args['slug']; ?>"></a>
		<?php
	}
}