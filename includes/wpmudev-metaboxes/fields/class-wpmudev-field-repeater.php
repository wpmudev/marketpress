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
		$this->args = array_replace_recursive(array(
			'layout' => 'table',
			'add_row_label' => __('Add Row', 'wpmudev_metaboxes'),
		), $args);
	}
	
	/**
	 * Saves the field to the database.
	 *
	 * @since 1.0
	 * @access public
	 * @action save_post
	 * @param int $post_id
	 * @param string $meta_key
	 * @param $value (optional)
	 */
	public function save_value( $post_id, $meta_key = null, $value = null ) {
		if ( is_null($value) ) {
			$post_key = $this->get_post_key();
			$value = $this->get_post_value($post_key, null);
		}
		
		
		if ( is_null($meta_key) ) {
			$meta_key = $this->get_meta_key();
		}
		
		/**
		 * Modify the value before it's saved to the database. Return null to bypass internal saving mechanisms.
		 *
		 * @since 1.0
		 * @param mixed $value The field's value
		 * @param mixed $post_id The current post id or option name
		 * @param object $this Refers to the current field object
		 */
		$value = apply_filters('wpmudev_field/save_value', $this->sanitize_for_db($value, $post_id), $post_id, $this);
		$value = apply_filters('wpmudev_field/save_value/' . $this->args['name'], $value, $post_id, $this);
		
		if ( is_null($value) ) {
			return;
		}
		
		//! TODO: This is a post - save to database
	}

	/**
	 * Gets an appropriate meta key for a given subfield
	 *
	 * @since 1.0
	 * @access public
	 * @param WPMUDEV_Field $field
	 * @return string
	 */
	public function get_subfield_meta_key( $field ) {
		$meta_key_parts = explode('[', $field->args['name']);
		return rtrim($meta_key_parts[1], ']');
	}

	/**
	 * Gets the field value from the database.
	 *
	 * @since 1.0
	 * @access public
	 * @param mixed $post_id
	 * @param bool $raw Whether or not to get the raw/unformatted value as saved in the db
	 * @return mixed
	 */
	public function get_value( $post_id, $raw = false ) {	
		$value = null;
		
		/**
		 * Filter the returned value before any internal code is run
		 *
		 * @since 1.0
		 * @param mixed $value The return value
		 * @param mixed $post_id The current post id or option name
		 * @param bool $raw Whether or not to get the raw/unformatted value as saved in the db
		 * @param object $this Refers to the current field object		 
		 */
		$value = apply_filters('wpmudev_field/before_get_value', $value, $post_id, $raw, $this);
		$value = apply_filters('wpmudev_field/before_get_value/' . $this->args['name'], $value, $post_id, $raw, $this);

		if ( ! is_null($value) ) {
			$this->_value = $value;
		}
		
		if ( ! is_null($this->_value) ) {
			return ($raw) ? $this->_value : $this->format_value($this->_value, $post_id);
		}

		$value = array();
		
		if ( is_numeric($post_id) ) {
			foreach ( $this->subfields as $subfield ) {
				$meta_key = $this->args['original_name'] . '_' . $subfield->args['original_name'];
			}
		} else {
			$options = get_option($post_id);
			$key = $this->get_post_key();
			$value = $this->array_search($options, $key);
		}
		
		/**
		 * Modify the returned value.
		 *
		 * @since 1.0
		 * @param mixed $value The return value
		 * @param mixed $post_id The current post id or option name
		 * @param bool $raw Whether or not to get the raw/unformatted value as saved in the db
		 * @param object $this Refers to the current field object		 
		 */
		$value = apply_filters('wpmudev_field/get_value', $value, $post_id, $raw, $this);
		$value = apply_filters('wpmudev_field/get_value/' . $this->args['name'], $value, $post_id, $raw, $this);
		
		if ( is_null($value) ) {
			$value = $this->args['default_value'];
		}
		
		return $value;
	}
	
	
	/**
	 * Sorts/groups the repeater field's subfields for easier workability
	 *
	 * @since 1.0
	 * @access public
	 * @param array $unsorted The unsorted subfield $_POST data
	 * @return array
	 */
	public function sort_subfields( $unsorted ) {
		$new = $existing = array();
		
		foreach ( $unsorted as $input_name => $array ) {
			foreach ( $array as $type => $array2 ) {
				switch ( $type ) {
					case 'new' :
						$fields = array();
						foreach ( $array2 as $index => $value ) {
							$subfield = $this->subfields[$index];
							$new[$index][$input_name] = $value;
						}
					break;
				
					case 'existing' :
						$index = 0;
						foreach ( $array2 as $variation_id => $value ) {
							$subfield = $this->subfields[$index];
							$existing[$variation_id][$input_name] = $value;
							$index ++;
						}
					break;
					
					// Repeater fields will have $input_name as $type key
					default :
						$input_name2 = $type;
						foreach ( $array2 as $type2 => $array3 ) {
							switch ( $type2 ) {
								case 'new' :
									foreach ( $array3 as $index => $value ) {
										$subfield = $this->subfields[$index];
										$new[$index][$input_name][$input_name2] = $value;
									}
								break;
							
								case 'existing' :
									$index = 0;
									foreach ( $array3 as $variation_id => $value ) {
										$subfield = $this->subfields[$index];
										$existing[$variation_id][$input_name][$input_name2] = $value;
										$index ++;
									}
								break;
							}
						}
					break;
				}
			}
		}
		
		return array('new' => $new, 'existing' => $existing);
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
		$class = 'wpmudev-subfield-group-wrap';
		$atts = '';
		
		if ( empty($data) ) {
			$data[] = array(); // So we always show at least one row
		}
		
		foreach ( $this->args['custom'] as $key => $att ) {
			if ( strpos($key, 'data-conditional') !== false ) {
				$atts .= ' ' . $key . '="' . esc_attr($att) . '"';
			}
		}
		
		if ( strlen($atts) > 0 ) {
			$class .= ' wpmudev-field-has-conditional';
		}
		
		$this->before_field(); ?>
		<div class="<?php echo $class; ?>"<?php echo $atts; ?>>
		<?php		
		if ( $this->args['layout'] == 'table' ) : ?>
			<table class="wpmudev-subfields widefat">
				<thead>
					<tr>
						<th style="width:15px">&nbsp;</th>
			<?php
			foreach ( $this->subfields as $index => $subfield ) : ?>
						<th><?php echo $subfield->args['label']['text']; ?><small class="wpmudev-subfield-desc"><?php echo $subfield->args['desc']; ?></small></th>
			<?php
			endforeach; ?>		
			
						<th style="width:15px">&nbsp;</th>
					</tr>
				</thead>
		<?php
		else : ?>
			<div class="wpmudev-subfields">
		<?php
		endif;
		
		foreach ( $data as $outer_index => $row ) :
			foreach ( $this->subfields as $index => $subfield ) :
				if ( isset($data[$outer_index][$subfield->args['original_name']]) ) {
					$id = ( isset($data[$outer_index]['ID']) ) ? $data[$outer_index]['ID'] : $outer_index;
					$subfield->set_value($data[$outer_index][$subfield->args['original_name']]);
					$subfield->set_subfield_id($id);
				} else {
					$subfield->set_value('');
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
										<?php
						if ( ! empty($subfield->args['label']['text']) ) : ?>
										<label class="wpmudev-subfield-label <?php echo $subfield->args['label']['class']; ?>"><?php echo $subfield->args['label']['text'] . (( ! empty($subfield->args['desc']) ) ? '<span class="wpmudev-metabox-tooltip dashicons dashicons-editor-help"><span>' . $subfield->args['desc'] . '</span></span>' : ''); ?></label>
										<?php
						endif; ?>
									<div class="wpmudev-subfield-input"><?php $subfield->display($post_id); ?></div>
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
		<div class="wpmudev-repeater-field-actions clearfix"><input class="button-primary wpmudev-repeater-field-add alignright" type="button" value="<?php echo $this->args['add_row_label']; ?>" /></div>
		<script type="text/javascript">
		// Clone the subfields BEFORE any events/plugins are able to modify the markup, bind events, etc
		jQuery('.wpmudev-subfields').not('[data-ready-for-cloning]').each(function(){
			var $this = jQuery(this);
			$this.data('toClone', $this.find('.wpmudev-subfield-group:first')).attr('data-ready-for-cloning', 1);
		});
		</script>
		<?php
		$this->after_field();
	}
	
	/**
	 * Enqueues necessary field scripts
	 *
	 * @since 1.0
	 * @access public
	 */
	public function enqueue_scripts() {
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script('jquery-ui-core');
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
			$(document).trigger('wpmudev_repeater_field/start_sort', [ ui.item ]);
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
			$(document).trigger('wpmudev_repeater_field/stop_sort', [ ui.item ]);
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
		if ( confirm('<?php _e('Are you sure you want to delete this?', 'wpmudev_metaboxes'); ?>') ) {
			var $this = $(this),
					$subfieldGroup = $this.closest('.wpmudev-subfield-group'),
					$links = $('.wpmudev-subfield-delete-group-link'),
					$siblings = $subfieldGroup.siblings(),
					subfieldGroupCount = $subfieldGroup.closest('.wpmudev-subfields').find('.wpmudev-subfield-group').length,
					didOne = false;
			
			$subfieldGroup.find('.wpmudev-subfield-inner').hide();
			
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
			$(document).trigger('wpmudev_repeater_field/after_delete_field_group', [ $subfieldGroup ]);

			$subfieldGroup.remove();
			$siblings.each(function(i){
				$(this).find('.wpmudev-subfield-group-index').find('span').html(i + 1);
			});
		}
	});
	
	$('.wpmudev-repeater-field-add').click(function(){
		/**
		 * Triggered right before a row is added.
		 *
		 * @since 1.0
		 */
		$(document).trigger('wpmudev_repeater_field/before_add_field_group');

		var $btn = $(this),
				$subfields = $btn.closest('.wpmudev-field').find('.wpmudev-subfields'),
				$clonedRow = $subfields.data('toClone').clone(),
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
		
		$clonedRow.find('.wpmudev-subfield-inner').show();
		
		if ( didOne ) {
			return;
		}
		
		didOne = true;
		
		// Change the id of each field
		$clonedRow.find('[id]').each(function(){
			var $this = $(this),
					oldId = $this.attr('id');
					
			$this.attr('id', '').uniqueId();
			$clonedRow.find('label[for="' + oldId + '"]').attr('for', $this.attr('id'));
		});
		
		/**
		 * Triggered when a row is added.
		 *
		 * @since 1.0
		 * @param object group
		 */
		$(document).trigger('wpmudev_repeater_field/after_add_field_group', [ $clonedRow ]);
		
		$('.wpmudev-subfields').sortable('refresh'); //so the new row is recognized by the sortable plugin
		$('.wpmudev-subfield-delete-group-link').show();
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
		$class = 'WPMUDEV_Field_' . ucfirst($type);
		
		if ( ! class_exists($class) ) {
			return false;	
		}
		
		//subfields don't support validation (yet) so make sure these arguments are set accordingly
		$args['validation'] = array();
		$args['custom_validation_message'] = '';
		
		if ( isset($args['name']) ) {
			// Some fields (e.g. section) don't use the name argument
			$args['original_name'] = $args['name'];
			$args['name'] = $this->args['name'] . '[' . $args['name'] . '][new][]'; //repeater fields should be an array
		}
		
		$args['echo'] = false;
		$field = new $class($args);
		$field->is_subfield = true;
		$this->subfields[] = $field;
		
		return $field;
	}
}