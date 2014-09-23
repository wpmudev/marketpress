jQuery.validator.addMethod('alphanumeric', function(value, element){
	return this.optional(element) || new RegExp('[a-z0-9]{' + value.length + '}', 'ig').test(value);
}, WPMUDEV_Metaboxes_Validation_Messages.alphanumeric_error_msg);
jQuery.validator.addClassRules('alphanumeric', { "alphanumeric" : true });

(function($){
	$(document).ready(function(){
		// Preload working indicator
		$('body').append('<img class="wpmudev-metabox-working-indicator" style="display:none" src="' + WPMUDEV_Metaboxes.spinner_url + '" alt="" />');
	});

	$.fn.isWorking = function( isLoading ){
		var $spinner = $('.wpmudev-metabox-working-indicator');
		
		if ( isLoading ) {
			return this.each(function(){
				var $this = $(this);
				$spinner.insertAfter($this);
				$spinner.show();
			});
		} else {
			$spinner.hide();
		}
	};
}(jQuery));

(function($){
	window.onload = function() {
		/* initializing conditional logic here instead of document.ready() to prevent
		issues with wysiwyg editor not getting proper height */
		initConditionals();
		$(':checkbox, :radio, select').change(initConditionals);
	}
	
	$(document).on('wpmudev_repeater_field/after_add_field_group', function(e){
		initConditionals();
	});
	
	jQuery(document).ready(function($){
		initValidation();
		initRowShading();
		initToolTips();
		initPostboxAccordions();
	});
	
	var initPostboxAccordions = function(){
		$('.wpmudev-postbox').find('.hndle, .handlediv').click(function(){
			var $this = $(this),
					$postbox = $this.closest('.wpmudev-postbox');
			
			$postbox.toggleClass('closed');
			$(document).trigger('postbox-toggled', $postbox);
			
			$.post(ajaxurl, {
				"action" : "wpmudev_metabox_save_state",
				"closed" : $postbox.hasClass('closed'),
				"id" : $postbox.attr('id')
			});
		});
	}
	
	var initToolTips = function(){
		$('.wpmudev-field').on('click', '.wpmudev-metabox-tooltip', function(){
			var $this = $(this),
					$button = $this.find('.wpmudev-metabox-tooltip-button');
			
			if ( $button.length == 0 ) {
				$this.children('span').append('<a class="wpmudev-metabox-tooltip-button" href="#">x</a>');
			}
			
			$this.children('span').fadeIn(250);
		});
		
		$('.wpmudev-field').on('click', '.wpmudev-metabox-tooltip-button', function(e){
			e.preventDefault();
			e.stopPropagation();
			$(this).parent().fadeOut(250);
		});
	}
	
	var initRowShading = function(){
		$('.wpmudev-postbox').each(function(){
			var $rows = $(this).find('.wpmudev-field:visible');
			$rows.filter(':odd').addClass('shaded');
			$rows.filter(':even').removeClass('shaded');
		});
		
		$('.wpmudev-field-section').each(function(){
			var $this = $(this),
					shaded = $this.hasClass('shaded') ? true : false;
			
			if ( shaded ) {
				$this.nextUntil('.wpmudev-field-section').addClass('shaded');
			} else {
				$this.nextUntil('.wpmudev-field-section').removeClass('shaded');
			}
		})
	}
	
	var testConditionals = function(conditionals){
		var numValids = 0;
		
		$.each(conditionals, function(i, conditional){
			var $input = $('[name="' + conditional.name + '"]');
			
			if ( ! $input.is(':radio') && ! $input.is(':checkbox') && ! $input.is('select') ) {
				// Conditional logic only works for radios, checkboxes and select dropdowns
				return;
			}
			
			var val = getInputValue($input);
			
			if ( $.inArray(val, conditional.value) >= 0 ) {
				numValids ++;
			}
		});
		
		return numValids;
	};
	
	var parseConditionals = function(elm){
		var conditionals = [];
		$.each(elm.attributes, function(i, attrib){
			if ( attrib.name.indexOf('data-conditional-name') >= 0 ) {
				var index = attrib.name.replace('data-conditional-name-', '');
				
				if ( conditionals[index] === undefined ) {
					conditionals[index] = {};
				}
				
				conditionals[index]['name'] = attrib.value;
			}
			
			if ( attrib.name.indexOf('data-conditional-value') >= 0  ) {
				var index = attrib.name.replace('data-conditional-value-', '');
				
				if ( conditionals[index] === undefined ) {
					conditionals[index] = {};
				}
				
				conditionals[index]['value'] = attrib.value.split('||');
			}
		});
		
		return conditionals;
	};
	
	var getInputValue = function($input) {
		if ( $input.is('select') ) {
			var val = $input.val();
		}
		
		if ( $input.is(':checkbox') ) {
			var val = ( $input.prop('checked') ) ? $input.val() : "-1";
		}
		
		if ( $input.is(':radio') ) {
			var val = $input.filter(':checked').val();
		}
		
		return val;			
	}
	
	var initConditionals = function(){
		$('.wpmudev-field-has-conditional, .wpmudev-metabox-has-conditional').each(function(){
			var $this = $(this),
					operator = $this.attr('data-conditional-operator'),
					action = $this.attr('data-conditional-action'),
					numValids = 0;
			
			if ( operator === undefined || action === undefined ) {
				// Skip elements that don't have conditional attributes defined
				return;
			}
			
			operator = operator.toUpperCase();
			action = action.toLowerCase();
			
			var conditionals = parseConditionals(this);
			
			if ( $this.hasClass('wpmudev-metabox-has-conditional') ) {
				$container = $this;
			} else {
				$container = ( $this.closest('.wpmudev-subfield').length ) ? $this.closest('.wpmudev-subfield') : $this.closest('.wpmudev-field')
			}
			
			if ( action == 'show' ) {
				if ( operator == 'AND' ) {
					if ( testConditionals(conditionals) != conditionals.length ) {
						$container.hide().next('p.submit').hide();
					} else {
						$container.fadeIn(500).next('p.submit').fadeIn(500)
					}
				} else {
					if ( testConditionals(conditionals) == 0 ) {
						$container.hide().next('p.submit').hide();
					} else {
						$container.fadeIn(500).next('p.submit').fadeIn(500)
					}
				}
			}
			
			if ( action == 'hide' ) {
				if ( operator == 'AND' ) {
					if ( testConditionals(conditionals) == conditionals.length ) {
						$container.hide().next('p.submit').hide();
					} else {
						$container.fadeIn(500).next('p.submit').fadeIn(500)
					}
				} else {
					if ( testConditionals(conditionals) > 0 ) {
						$container.hide().next('p.submit').hide();
					} else {
						$container.fadeIn(500).next('p.submit').fadeIn(500)
					}
				}
			}
				
			initRowShading();
		});
	};

	var initValidation = function(){
		var $form = $("form#post, form#mp-main-form");

		//initialize the form validation		
		$form.validate({
			"errorPlacement" : function(error, element){
				error.appendTo(element.parent());
			},
			"wrapper" : "div"
		});
		
		$form.find('#publish').click(function(e){
			e.preventDefault();
			
			if ( $form.valid() ) {
				$form.submit();
			}
		});
		
		$('[data-custom-validation]').each(function(){
			var $this = $(this),
					nameParts = $this.attr('name').split('['), //take into account array fields
					ruleName = nameParts[0],
					rules = {};
			
			rules[ruleName] = $this.attr('data-custom-validation');
			
			$.validator.addMethod(ruleName, function(value, element, params){
				return this.optional(element) || new RegExp(params + '{' + value.length + '}', 'ig').test(value);
			}, WPMUDEV_Metaboxes_Validation_Messages[ruleName]);

			$this.rules('add', rules);
		});		
	}

}(jQuery));