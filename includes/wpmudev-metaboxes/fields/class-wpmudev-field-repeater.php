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
	 *		@type bool $sortable Whether the fields should be sortable or not. Defaults to true.
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = array_replace_recursive(array(
			'layout' => 'table',
			'add_row_label' => __('Add Row', 'wpmudev_metaboxes'),
			'sortable' => true,
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
	 * @param string $meta_key
	 * @param bool $raw Whether or not to get the raw/unformatted value as saved in the db
	 * @return mixed
	 */
	public function get_value( $post_id, $meta_key = null, $raw = false ) {
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
		$sorted = $name_keys = array();
		
		// Get the base field name for the repeater field
		$this_name_parts = explode( '[', $this->args['name'] );
		$this_name_parts = array_map( create_function( '$val', 'return rtrim( $val, "]" );' ), $this_name_parts );
		$this_name_key = implode( '->', $this_name_parts );
		
		// Loop through the fields and setup the appropriate name keys (e.g. $key1->$key2->$key3)
		foreach ( $unsorted as $idx => $fields ) {
			foreach ( $this->subfields as $index => $field ) {
				$name_parts = explode( '[', $field->args['name_base'] );
				$name_parts = array_map( create_function( '$val', 'return rtrim( $val, "]" );' ), $name_parts );
				$name_key = implode( '->', $name_parts );
				$name_key = str_replace( $this_name_key, $idx, $name_key );
				
				if ( $field instanceof WPMUDEV_Field_Complex ) {
					$subfields = $fields[ $field->args['original_name'] ];
					foreach ( $subfields as $name => $subfield ) {
						$name_keys[] = $name_key . '->' . $name;
					}
				} else {
					$name_keys[] = $name_key;
				}
			}
		}

		/* Loop through the name keys and get the type (either existing or new), id and value
		and then add to the $sorted array */
		foreach ( $name_keys as $name_key ) {
			$keys = explode( '->', $name_key );
			$array = mp_arr_get_value( $name_key, $unsorted );
			if(!is_array($array)){
				//in case $array is not an array, we move next
				continue;
			}
			$type = key( $array );
			$array = current( $array );
			$id = key( $array );
			$val = current( $array );
			$idx = array_shift( $keys );
			
			if ( 'existing' == $type ) {
				$id = '_' . ltrim( $id, '_' );
			}
			
			mp_push_to_array( $sorted, $idx . '->' . $id . '->' . implode( '->', $keys ), $val );
		}
		
		return $sorted;
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
			$data = array(''); // So we always show at least one row
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
			<table class="wpmudev-subfields widefat<?php echo ( ! $this->args['sortable'] ) ? ' no-sorting' : ''; ?>">
				<thead>
					<tr>
						<th style="width:15px">&nbsp;</th>
			<?php
			foreach ( $this->subfields as $index => $subfield ) : ?>
						<th><label class="wpmudev-subfield-label <?php echo $subfield->args['label']['class']; ?>"><?php echo $subfield->args['label']['text'] . (( ! empty( $subfield->args['custom']['data-rule-required'] ) ) ? '<span class="required">*</span>' : '') . (( ! empty($subfield->args['desc']) ) ? '<span class="wpmudev-metabox-tooltip dashicons dashicons-editor-help"><span>' . $subfield->args['desc'] . '</span></span>' : ''); ?></label></th>
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
				$subfield->set_order($outer_index);

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
							<div class="wpmudev-subfield <?php echo str_replace('_', '-', strtolower(get_class($subfield))); ?>">
								<div class="wpmudev-subfield-inner clearfix">
										<?php
						if ( ! empty($subfield->args['label']['text']) ) : ?>
										<label class="wpmudev-subfield-label <?php echo $subfield->args['label']['class']; ?>"><?php echo $subfield->args['label']['text'] . (( ! empty( $subfield->args['custom']['data-rule-required'] ) ) ? '<span class="required">*</span>' : '') . (( ! empty($subfield->args['desc']) ) ? '<span class="wpmudev-metabox-tooltip dashicons dashicons-editor-help"><span>' . $subfield->args['desc'] . '</span></span>' : ''); ?></label>
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
	var updateOrdering = function( $elms ){
		$elms.each(function(i){
			var $this = $(this);
			$this.find('.wpmudev-subfield-group-index').find('span').html(i + 1);
			$this.find('input,textarea,select').filter('[name]').each(function(){
				var $this = $(this),
						name = $this.attr('name'),
						newName = name.replace(/\[[\d*]\]/, '[' + i + ']');
						
				$this.attr('name', newName);
			});
		});
	};
	
	$('.wpmudev-subfields').not( '.no-sorting' ).sortable({
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
			updateOrdering(ui.item.siblings().andSelf());
			
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
				var $this = $(this);
		  	$this.width($this.width());
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
			updateOrdering($siblings);
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
								
		$clonedRow.find('.wpmudev-subfield-inner').css('display', 'none');
		$clonedRow.appendTo($subfields);
		$clonedRow.find('[name]').each(function(){
			var $this = $(this),
					name = $this.attr('name').replace('existing', 'new'),
					nameParts = name.split('['),
					newName = nameParts[0];
			
			for ( i = 1; i < (nameParts.length - 1); i++ ) {
				var namePart = nameParts[i].replace(']', '');
				newName += '[' + namePart + ']';
			}
			
			// Reset input value
			if ( $this.attr('data-default-value') !== undefined ) {
				$this.val($this.attr('data-default-value'));
			} else {
				$this.val('');
			}
			
			$this.attr('name', newName + '[' + $clonedRow.index() + ']');
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
		
		updateOrdering($clonedRow.siblings().andSelf());
		
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
		//$args['validation'] = array();
		//$args['custom_validation_message'] = '';
		
		if ( isset($args['name']) ) {
			// Some fields (e.g. section) don't use the name argument
			$args['original_name'] = $args['name'];
			$args['name_base'] = $this->args['name'] . '[' . $args['name'] . ']';
			$args['name'] = $args['name_base'] . '[new][]'; //repeater fields should be an array
			
		}
		
		$args['echo'] = false;
		$field = new $class($args);
		$field->is_subfield = true;
		$this->subfields[] = $field;
		
		return $field;
	}
}