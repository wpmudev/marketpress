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
	 * Display the field
	 *
	 * @since 1.0
	 * @access public
	 * @param int/string $post_id
	 */
	public function display( $post_id ) {
		echo '<a class="wpmudev-field-tab-anchor" name="' . $this->args['slug'] . '"></a>';
	}
}