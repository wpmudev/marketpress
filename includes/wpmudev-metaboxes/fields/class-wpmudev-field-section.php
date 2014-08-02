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
	 *		@type string $title The title of the section.
	 *		@type string $subtitle The subtitle of the section.
	 * }	 
	 */
	public function on_creation( $args ) {
		$this->args = array_replace_recursive(array(
			'title' => __('Section Title', 'wpmudev_metaboxes'),
			'subtitle' => '',
		), $args);
	}

	/**
	 * Displays the field
	 *
	 * @since 1.0
	 * @access public
	 * @param int $post_id
	 */
	public function display( $post_id ) {
		$class = 'wpmudev-field-section-wrap';
		$atts = '';
		
		foreach ( $this->args['custom'] as $key => $att ) {
			if ( strpos($key, 'data-conditional') !== false ) {
				$atts .= ' ' . $key . '="' . esc_attr($att) . '"';
			}
		}
		
		if ( strlen($atts) > 0 ) {
			$class .= ' wpmudev-field-has-conditional';
		} ?>
		<div class="<?php echo $class; ?>"<?php echo $atts; ?>>
			<h2 class="wpmudev-section-title"><?php echo $this->args['title']; ?></h2>
			<?php
			if ( ! empty($this->args['subtitle']) ) : ?>
			<p><?php echo $this->args['subtitle']; ?></p>
			<?php
			endif; ?>
		</div>
		<?php
	}
}