<?php

class WPMUDEV_Field_Tab_Labels extends WPMUDEV_Field {
	/**
	 * Runs on parent construct
	 *
	 * @since 1.0
	 * @access public
	 * @param array $args {
	 * 		An array of arguments.
	 *
	 *		@type array $tabs {
	 *			An array of arguments for the tabs
	 *
	 *			@type bool $active Whether the tab should be in active state or not.
	 *			@type string $label The label of the tab.
	 *			@type string $slug The target slug to use for the tab - used for the href attribute of the tab link.
	 *		}
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = array_replace_recursive(array(
			'tabs' => array(),
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
	 * Print necessary field scripts
	 *
	 * @since 1.0
	 * @access public
	 */
	public function print_scripts() {
		parent::print_scripts();
		?>
<script type="text/javascript">
(function($){
	var createTabGroups = function( $elms ){
		$elms.each(function(){
			var $this = $(this),
					$anchor = $this.find('.wpmudev-field-tab-anchor'),
					slug = $anchor.attr('name');
			
			$this.nextUntil('.wpmudev-field-tab').andSelf().wrapAll('<div class="wpmudev-field-tab-wrap" data-slug="' + slug + '"></div>');
			$anchor.remove();
		});
	};
	
	var initTabGroupListeners = function() {
		$('.wpmudev-field').on('click', '.wpmudev-field-tab-label-link', function(e){
			e.preventDefault();
			
			var $this = $(this),
					$target = $this.closest('.wpmudev-field-tab-labels').siblings('.wpmudev-field-tab-wrap[data-slug="' + $this.attr('href').substr(1) + '"]');
			
			$this.parent().addClass('active').siblings().removeClass('active');
			$target.removeClass( 'inactive' ).siblings('.wpmudev-field-tab-wrap').addClass( 'inactive' );
		});
		$('.wpmudev-field-tab-label').filter('.active').find('.wpmudev-field-tab-label-link').trigger('click');
	}

	$(document).ready(function(){
		createTabGroups($('.wpmudev-field-tab'));
		initTabGroupListeners();
	});
	
	$(document).on('wpmudev_repeater_field/after_add_field_group', function(e, $group){
		createTabGroups($group.find('.wpmudev-field-tab'));
	});
}(jQuery));
</script>
		<?php
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
<ul class="wpmudev-field-tab-labels-holder clearfix">
		<?php
		foreach ( $this->args['tabs'] as $tab ) :
			$tab = array_replace_recursive(array(
				'active' => false,
				'label' => '',
				'slug' => '',
			), $tab);
			
			$classes = array('wpmudev-field-tab-label');
			if ( $tab['active'] ) {
				$classes[] = 'active';
			}
		?>
	<li class="<?php echo implode(' ', $classes); ?>"><a class="wpmudev-field-tab-label-link" href="#<?php echo $tab['slug']; ?>"><?php echo $tab['label']; ?></a></li>
		<?php
		endforeach; ?>
</ul>
		<?php
	}
}