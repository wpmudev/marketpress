<?php

class WPMUDEV_Field_Repeater extends WPMUDEV_Field {
	/**
	 * Stores reference to the repeater's subfields
	 *
	 * @since 1.0
	 * @access public
	 * @var array
	 */
	var $subfields = array();
	
	/**
	 * Runs on creation of parent
	 *
	 * @since 1.0
	 * @access public
	 * 
	 * @param array $args {
	 *		An array of arguments. Optional.
	 *
	 *		@type string $layout (table/rows)
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = wp_parse_args($args, array(
			'layout' => 'table',
			'add_row_label' => __('Add Row', 'wpmudev_metaboxes'),
		));
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
		$html = '<div class="wpmudev-subfield-group-wrap">';
		
		if ( $this->args['layout'] == 'table' ) {
			$html .= '<table class="wpmudev-subfields widefat">';
				$html .= '<thead>';
					$html .= '<th style="width:15px">&nbsp;</th>';
				
					foreach ( $this->subfields as $index => $subfield ) {
						$html .= '<th>' . $subfield->args['label']['text'] . '<small class="wpmudev-subfield-desc">' . $subfield->args['desc'] . '</small></th>';
					}				
			
					$html .= '<th style="width:15px">&nbsp;</th>';
				$html .= '</thead>';
				$html .= '<tr class="wpmudev-subfield-group">';
				
		} else {
			$html .= '<div class="wpmudev-subfields">';
				$html .= '<div class="wpmudev-subfield-group">';
		}
		
		foreach ( $this->subfields as $index => $subfield ) {
			switch ( $this->args['layout'] ) {
				case 'rows' :
					if ( $index == 0 ) {
						$html .= '<div class="wpmudev-subfield-group-index"><span>' . ($index + 1) . '</span></div>';
							$html .= '<div class="wpmudev-subfield-group-fields">';
					}
					
					$html .= '<div class="wpmudev-subfield">';
						$html .= '<div class="wpmudev-subfield-inner">';
							$html .= $subfield->display($post_id, false);
							$html .= '<label for="' . $subfield->get_id() . '" class="wpmudev-subfield-label ' . $subfield->args['label']['class'] . '">' . $subfield->args['label']['text'] . '</label>';
						$html .= '</div>';
					$html .= '</div>';
					
					if ( $index == (count($this->subfields) - 1) ) {
							$html .= '</div>';
							$html .= '<div class="wpmudev-subfield-delete-group-link-wrap"><a class="wpmudev-subfield-delete-group-link" href="javascript:;"></a></div>';
					}
				break;
			
				default :
					$html .= ( $index == 0 ) ? '<td class="wpmudev-subfield-group-index"><span>' . ($index + 1) . '</span></td>' : '';
					$html .= '<td class="wpmudev-subfield">';
						$html .= '<div class="wpmudev-subfield-inner">';
							$html .= $subfield->display($post_id, false);
						$html .= '</div>';
					$html .= '</td>';
					$html .= ( $index == (count($this->subfields) - 1) ) ? '<td class="wpmudev-subfield-delete-group"><a class="wpmudev-subfield-delete-group-link" href="javascript:;"></a></td>' : '';
				break;				
			}
		}
		
		if ( $this->args['layout'] == 'table' ) {
				$html .= '</tr>';
			$html .= '</table>';
		} else {
				$html .= '</div>';
			$html .= '</div>';
		}
		
			$html .= '</div>';
		$html .= '<div class="wpmudev-repeater-field-actions clearfix"><input class="button wpmudev-repeater-field-add alignright" type="button" value="' . $this->args['add_row_label'] . '" /></div>';

		/**
		 * Modify the display HTML before return/output.
		 *
		 * @since 1.0
		 * @param string $html The current display HTML.
		 * @param object $this The current field object.
		 */
		$html = apply_filters('wpmudev_field_repeater_display', $html, $this);
		
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}
	
	/**
	 * Enqueues necessary field scripts
	 *
	 * @since 1.0
	 * @access public
	 */
	public function enqueue_scripts() {
		wp_enqueue_script('jquery-ui-sortable');
	}
	
	/**
	 * Prints necessary field scripts
	 *
	 * @since 1.0
	 * @access public
	 */
	public function print_scripts() {
		?>
<script type="text/javascript">
jQuery(document).ready(function($){
	$('.wpmudev-subfields').sortable({
		"axis" : "y",
		"forceHelperSize" : true,
		"forcePlaceholderSize" : true,
		"items" : ".wpmudev-subfield-group",
		"handle" : ".wpmudev-subfield-group-index",
		"placeholder" : "wpmudev-repeater-field-placeholder",
		"stop" : function(e, ui) {
			ui.item.siblings().andSelf().each(function(i){
				$(this).find('.wpmudev-subfield-group-index').find('span').html(i + 1);
			});
		},
		"helper" : function(e, ui){
			ui.children().each(function() {
		  	$(this).width($(this).width());
		  });
		  
		  return ui;
		}
	});
	
	var $links = $('.wpmudev-subfield-delete-group-link');
	if ( $links.length == 1 ) {
		$links.hide();
	}
	
	$('.wpmudev-subfields').on('click', '.wpmudev-subfield-delete-group-link', function(event){
		if ( confirm('<?php _e('Are you sure you want to delete this row?', 'wpmudev_metaboxes'); ?>') ) {
			var $this = $(this),
					$subfieldGroup = $this.closest('.wpmudev-subfield-group'),
					$links = $('.wpmudev-subfield-delete-group-link'),
					$siblings = $subfieldGroup.siblings(),
					subfieldGroupCount = $subfieldGroup.closest('.wpmudev-subfields').find('.wpmudev-subfield-group').length;
			
			$subfieldGroup.find('.wpmudev-subfield-inner').fadeOut(250, function(){
				if ( subfieldGroupCount == 2 ) {
					$links.hide();
				} else {
					$links.show();
				}
				
				$subfieldGroup.remove();
				$siblings.each(function(i){
					$(this).find('.wpmudev-subfield-group-index').find('span').html(i + 1);
				});
			});
		}
	});
	
	$('.wpmudev-repeater-field-add').click(function(){
		var $btn = $(this),
				$subfields = $btn.closest('.wpmudev-field').find('.wpmudev-subfields'),
				$clonedRow = $subfields.find('.wpmudev-subfield-group:last').clone();
				
		$clonedRow.find('input,select,textarea').val('');
		$clonedRow.find('.wpmudev-subfield-inner').css('display', 'none');
		$clonedRow.appendTo($subfields);
		$clonedRow.find('.wpmudev-subfield-group-index').find('span').html($clonedRow.index() + 1);
		$clonedRow.find('.wpmudev-subfield-inner').css('display', 'none').fadeIn(250, function(){
			$('.wpmudev-subfield-delete-group-link').show();
			$('.wpmudev-subfields').sortable('refresh'); //so the new row is recognized by the sortable plugin
		});
	});
});
</script>
		<?php
	}
	
	/**
	 * Adds a sub field to the repeater
	 *
	 * @since 1.0
	 * @access public
	 */
	public function add_sub_field( $type, $args ) {
		$class = apply_filters('wpmudev_field_repeater_add_sub_field', 'WPMUDEV_Field_' . ucfirst($type), $type, $args);
		
		if ( ! class_exists($class) ) {
			return false;	
		}
		
		$args['echo'] = false;
		$args['name'] = '[' . $this->args['name'] . '][' . $args['name'] . '][]'; //repeater fields should be an array
		$this->subfields[] = new $class($args);
	}
}