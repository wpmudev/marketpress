<?php

class WPMUDEV_Field_Section extends WPMUDEV_Field {
	/**
	 * Runs on parent construct
	 *
	 * @since 1.0
	 * @access public
	 * @param array $args {
	 *		An array of arguments. Optional.
	 *
	 *		@type bool $title The title of the section.
	 * }	 
	 */
	public function on_creation( $args ) {
		$this->args = wp_parse_args($args, array(
			'title' => __('Section Title', 'wpmudev_metaboxes'),
		));
	}

	/**
	 * Displays the field
	 *
	 * @since 1.0
	 * @access public
	 * @param int $post_id
	 */
	public function display( $post_id ) {
		?>
		<h2 class="wpmudev-section-title"><?php echo $this->args['title']; ?></h2>
		<?php
		if ( ! empty($this->args['desc']) ) : ?>
		<p><?php echo $this->args['desc']; ?></p>
		<?php
		endif;
	}
}