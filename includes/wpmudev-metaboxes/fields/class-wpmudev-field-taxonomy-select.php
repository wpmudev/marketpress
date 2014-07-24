<?php

class WPMUDEV_Field_Taxonomy_Select extends WPMUDEV_Field {
	/**
	 * Runs on parent construct
	 *
	 * @since 1.0
	 * @access public
	 */
	public function on_creation( $args ) {
		$this->args = array_replace_recursive(array(
			'multiple' => false,
			'placeholder' => __('Select Posts', 'mp'),
			'taxonomy' => '',
		), $args);
		
		$this->args['class'] .= ' wpmudev-taxonomy-select';			
	}

	/**
	 * Prints scripts
	 *
	 * @since 3.0
	 * @access public
	 */	
	public function print_scripts() {
		$terms = get_terms((array) $this->args['taxonomy'], array('hide_empty' => false));
		$data = array();
		
		if ( ! is_array($terms) ) {
			return false;
		}
		
		foreach ( $terms as $term ) {
			$data[] = array('id' => $term->term_id, 'text' => $term->name);
		}
	?>
<script type="text/javascript">
jQuery(document).ready(function($){
	$('input.wpmudev-taxonomy-select').each(function(){
		var $this = $(this);
		
		$this.select2({
			"multiple" : <?php echo $this->args['multiple']; ?>,
			"placeholder" : "<?php echo $this->args['placeholder']; ?>",
			"initSelection" : function(element, callback){
				var data = [];
				
				$(element.attr('data-select2-value').split('||')).each(function(){
					var val = this.split('->');
					data.push({ "id" : val[0], "text" : val[1] });
				});
				
				callback(data);
			},			
			"data" : <?php echo json_encode($data); ?>,
			"width" : "100%"
		}) 
	});
});
</script>
	<?php		
	}
		
	/**
	 * Displays the field
	 *
	 * @since 1.0
	 * @access public
	 * @param int $post_id
	 * @param bool $echo
	 */
	public function display( $post_id, $echo = true ) {
		$value = $this->get_value($post_id);
		$data = array();
		$ids = explode(',', $value);
		
		foreach ( $ids as $id ) {
			$term = get_term($id, $this->args['taxonomy']);
			$data[] =$id . '->' . $term->name;
		}
		
		$this->args['custom']['data-select2-value'] = implode('||', $data); ?>
		<input type="hidden" <?php echo $this->parse_atts(); ?> value="<?php echo esc_attr($value); ?>" />
		<?php
	}
	
	/**
	 * Enqueues the field's scripts
	 *
	 * @since 1.0
	 * @access public
	 */
	public function enqueue_scripts() {
		wp_enqueue_script('jquery');
		wp_enqueue_script('wpmudev-field-select2', WPMUDEV_Metabox::class_url('ui/select2/select2.min.js'), array('jquery'), '3.4.8');
	}
	
	/**
	 * Enqueues the field's styles
	 *
	 * @since 1.0
	 * @access public
	 */
	public function enqueue_styles() {
		wp_enqueue_style('wpmudev-field-select2',  WPMUDEV_Metabox::class_url('ui/select2/select2.css'), array(), '3.4.8');
	}
}