jQuery.validator.addMethod('alphanumeric', function(value, element){
	return this.optional(element) || new RegExp('[a-z0-9]{' + value.length + '}', 'ig').test(value);
}, WPMUDEV_Metaboxes_Validation_Messages.alphanumeric_error_msg);
jQuery.validator.addClassRules('alphanumeric', { "alphanumeric" : true });

(function($){
	window.onload = function() {
		/* initializing conditional logic here instead of document.ready() to prevent
		issues with wysiwyg editor not getting proper height */
		initConditionals();
	}
	
	$(document).on('wpmudev_repeater_field_after_add_field_group', function(e){
		initConditionals();
	});
	
	jQuery(document).ready(function($){
		initValidation();
	});
	
	var testConditionals = function(conditionals, action){
		var numValids = 0;
		
		$.each(conditionals, function(i, conditional){
			var $input = $('[name="' + conditional.name + '"]');
			
			if ( ! $input.is(':radio') && ! $input.is(':checkbox') && ! $input.is('select') ) {
				// Conditional logic only works for radios, checkboxes and select dropdowns
				return;
			}
			
			if ( $input.is('select') ) {
				var selected = $input.val();
			} else {
				var selected = ( $input.filter(':checked').length == 0 ) ? '-1' : $input.filter(':checked').val();
			}
			
			if ( action == 'show' && $.inArray(selected, conditional.value) >= 0 ) {
				numValids ++;
			}
			
			if ( action == 'hide' && $.inArray(selected, conditional.value) < 0 ) {
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
	
	var initConditionals = function(){
		$('.wpmudev-field-has-conditional').each(function(){
			var $this = $(this),
					$container = ( $this.closest('.wpmudev-subfield').length ) ? $this.closest('.wpmudev-subfield') : $this.closest('.wpmudev-field'),
					operator = $this.attr('data-conditional-operator').toUpperCase(),
					action = $this.attr('data-conditional-action').toLowerCase(),
					numValids = 0,
					conditionals = parseConditionals(this);
			
			if ( action == 'show' ) {
				if ( operator == 'AND' ) {
					if ( testConditionals(conditionals, 'show') != conditionals.length ) {
						$container.hide();
					}
				} else {
					if ( testConditionals(conditionals, 'show') == 0 ) {
						$container.hide();
					}
				}
				
				$.each(conditionals, function(i, conditional){
					var $input = $('[name="' + conditional.name + '"]');
					
					$input.change(function(){
						var $this = $(this);
						
						if ( $this.is('select') ) {
							var selected = $this.val();	
						} else {
							var selected = $this.filter(':checked').val();
						}
					
						if ( operator == 'AND' ) {
							if ( testConditionals(conditionals, 'show') != conditionals.length ) {
								$container.slideUp(500);
							} else {
								$container.slideDown(500);
							}
						} else {
							if ( testConditionals(conditionals, 'show') == 0 ) {
								$container.slideUp(500);
							} else {
								$container.slideDown(500);
							}
						}
					});
				});
			}	
				
			if ( action == 'hide' ) {
				if ( operator == 'AND' ) {
					if ( testConditionals(conditionals, 'hide') != conditionals.length ) {
						$container.show();
					}
				} else {
					if ( testConditionals(conditionals, 'hide') == 0 ) {
						$container.show();
					}
				}

				$.each(conditionals, function(i, conditional){
					var $input = $('[name="' + conditional.name + '"]');
				
					$input.change(function(){
						var $this = $(this);
						
						if ( $this.is('select') ) {
							var selected = $this.val();	
						} else {
							var selected = $this.filter(':checked').val();
						}
					
						if ( operator == 'AND' ) {
							if ( testConditionals(conditionals, 'hide') != conditionals.length ) {
								$container.slideDown(500);
							} else {
								$container.slideUp(500);
							}
						} else {
							if ( testConditionals(conditionals, 'hide') == 0 ) {
								$container.slideDown(500);
							} else {
								$container.slideUp(500);
							}
						}
					});
				});
			}	
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