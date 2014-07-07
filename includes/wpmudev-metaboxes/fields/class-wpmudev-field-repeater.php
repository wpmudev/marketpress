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
	 *		@type string $layout How to display the repeater subfields - either "table" or "rows".
	 *		@type string $add_row_label The label for the add row button.
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
	 */
	public function display( $post_id ) {
		$data = $this->get_value($post_id);
		$atts = '';
		
		if ( empty($data) ) {
			$data[] = array(); // So we always show at least one row
		}
		
		foreach ( $this->args['custom'] as $key => $att ) {
			if ( strpos($key, 'data-conditional') !== false ) {
				$atts .= $key . '="' . esc_attr($att) . '" ';
			}
		} ?>
		
		<div class="wpmudev-subfield-group-wrap" <?php echo trim($atts); ?>>
		<?php		
		if ( $this->args['layout'] == 'table' ) : ?>
			<table class="wpmudev-subfields widefat">
				<thead>
					<th style="width:15px">&nbsp;</th>
			<?php
			foreach ( $this->subfields as $index => $subfield ) : ?>
					<th><?php echo $subfield->args['label']['text']; ?><small class="wpmudev-subfield-desc"><?php echo $subfield->args['desc']; ?></small></th>
			<?php
			endforeach; ?>		
			
					<th style="width:15px">&nbsp;</th>
				</thead>
		<?php
		else : ?>
			<div class="wpmudev-subfields">
		<?php
		endif;
		
		foreach ( $data as $outer_index => $row ) :
			foreach ( $this->subfields as $index => $subfield ) :
				if ( isset($data[$outer_index][$subfield->args['original_name']]) ) {
					$subfield->set_value($data[$outer_index][$subfield->args['original_name']]);
					$subfield->set_subfield_id($data[$outer_index]['ID']);
				}
				
				switch ( $this->args['layout'] ) :
					case 'rows' :
						if ( $index == 0 ) : ?>
				<div class="wpmudev-subfield-group">
					<div class="wpmudev-subfield-group-index"><span><?php echo ($outer_index + 1); ?></span></div>
						<div class="wpmudev-subfield-group-fields">
						<?php
						endif; ?>
							<div class="wpmudev-subfield">
								<div class="wpmudev-subfield-inner clearfix">
									<div class="wpmudev-subfield-input"><?php echo $subfield->display($post_id, false); ?></div>
										<label for="<?php echo $subfield->get_id(); ?>" class="wpmudev-subfield-label '<?php echo $subfield->args['label']['class']; ?>"><?php echo $subfield->args['label']['text'] . (( ! empty($subfield->args['desc']) ) ? '<span class="wpmudev-metabox-tooltip dashicons dashicons-editor-help"><span>' . $subfield->args['desc'] . '</span></span>' : ''); ?></label>
									</div>
								</div>
						<?php
						if ( $index == (count($this->subfields) - 1) ) : ?>
							</div>
							<div class="wpmudev-subfield-delete-group-link-wrap"><a class="wpmudev-subfield-delete-group-link" href="javascript:;"></a></div>
						<?php
						endif;
					break;
				
					default :
						if ( $index == 0 ) : ?>
				<tr class="wpmudev-subfield-group"><td class="wpmudev-subfield-group-index"><span><?php echo ($outer_index + 1); ?></span></td>
					<?php
						endif; ?>
					<td class="wpmudev-subfield">
						<div class="wpmudev-subfield-inner"><?php $subfield->display($post_id); ?></div>
					</td>
					<?php
						if ( $index == (count($this->subfields) - 1) ) : ?>
					<td class="wpmudev-subfield-delete-group"><a class="wpmudev-subfield-delete-group-link" href="javascript:;"></a></td>
					<?php
						endif;
					break;				
				endswitch;
			endforeach;
		
			if ( $this->args['layout'] == 'table' ) : ?>
				</tr>
			<?php
			else : ?>
				</div>
			<?php
			endif;
		endforeach;

		if ( $this->args['layout'] == 'table' ) : ?>
			</table>
		<?php
		else : ?>
			</div>
		<?php
		endif; ?>
		</div>
		<div class="wpmudev-repeater-field-actions clearfix"><input class="button wpmudev-repeater-field-add alignright" type="button" value="<?php echo $this->args['add_row_label']; ?>" /></div>
		<?php
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
		parent::print_scripts();
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
		"start" : function(e, ui) {
			/**
			 * Triggered when sorting begis
			 *
			 * @since 1.0
			 * @param object
			 */
			$(document).trigger('wpmudev_repeater_field_start_sort', [ ui.item ]);
		},
		"stop" : function(e, ui) {
			ui.item.siblings().andSelf().each(function(i){
				$(this).find('.wpmudev-subfield-group-index').find('span').html(i + 1);
			});
			
			/**
			 * Triggered when sorting stops
			 *
			 * @since 1.0
			 * @param object
			 */			
			$(document).trigger('wpmudev_repeater_field_stop_sort', [ ui.item ]);
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
		if ( confirm('<?php _e('Are you sure you want to delete this field group?', 'wpmudev_metaboxes'); ?>') ) {
			var $this = $(this),
					$subfieldGroup = $this.closest('.wpmudev-subfield-group'),
					$links = $('.wpmudev-subfield-delete-group-link'),
					$siblings = $subfieldGroup.siblings(),
					subfieldGroupCount = $subfieldGroup.closest('.wpmudev-subfields').find('.wpmudev-subfield-group').length,
					didOne = false;
			
			$subfieldGroup.find('.wpmudev-subfield-inner').fadeOut(250, function(){
				if ( didOne ) {
					return;
				}
				
				didOne = true;
				
				if ( subfieldGroupCount == 2 ) {
					$links.hide();
				} else {
					$links.show();
				}
				
				/**
				 * Triggered when a row is deleted
				 *
				 * @since 1.0
				 * @param object
				 */
				$(document).trigger('wpmudev_repeater_field_after_delete_field_group', [ $subfieldGroup ]);

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
				$clonedRow = $subfields.find('.wpmudev-subfield-group:last').clone(),
				didOne = false;
				
		$clonedRow.find('[name]').val('');
		$clonedRow.find('.wpmudev-subfield-inner').css('display', 'none');
		$clonedRow.appendTo($subfields);
		$clonedRow.find('.wpmudev-subfield-group-index').find('span').html($clonedRow.index() + 1);
		$clonedRow.find('[name]').each(function(){
			var $this = $(this),
					name = $this.attr('name').replace('existing', 'new'),
					nameParts = name.split('['),
					newName = nameParts[0];
			
			for ( i = 1; i < (nameParts.length - 1); i++ ) {
				var namePart = nameParts[i].replace(']', '');
				newName += '[' + namePart + ']';
			}
			
			$this.attr('name', newName + '[]');
		});
		
		$clonedRow.find('.wpmudev-subfield-inner').css('display', 'none').fadeIn(250, function(){
			if ( didOne ) {
				return;
			}
			
			didOne = true;
			
			/**
			 * Triggered when a row is added.
			 *
			 * @since 1.0
			 * @param object group
			 */
			$(document).trigger('wpmudev_repeater_field_after_add_field_group', [ $clonedRow ]);
			
			$('.wpmudev-subfields').sortable('refresh'); //so the new row is recognized by the sortable plugin
			$('.wpmudev-subfield-delete-group-link').show();
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
	 * @return WPMUDEV_Field
	 */
	public function add_sub_field( $type, $args ) {
		$class = apply_filters('wpmudev_field_repeater_add_sub_field', 'WPMUDEV_Field_' . ucfirst($type), $type, $args);
		
		if ( ! class_exists($class) ) {
			return false;	
		}
		
		//subfields don't support validation (yet) so make sure these arguments are set accordingly
		$args['validation'] = array();
		$args['custom_validation_message'] = '';
		
		$args['echo'] = false;
		$args['original_name'] = $args['name'];
		$args['name'] = $this->args['name'] . '[' . $args['name'] . '][new][]'; //repeater fields should be an array
		$field = new $class($args);
		$field->is_subfield = true;
		$this->subfields[] = $field;
		
		return $field;
	}
}