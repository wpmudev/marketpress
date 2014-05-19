jQuery.validator.addMethod('alphanumeric', function(value, element){
	return this.optional(element) || new RegExp('[a-z0-9]{' + value.length + '}', 'ig').test(value);
}, WPMUDEV_Metaboxes.alphanumeric_error_msg);
jQuery.validator.addClassRules('alphanumeric', { "alphanumeric" : true });

jQuery.validator.addMethod('custom', function(value, element, params){
	return this.optional(element) || new RegExp(params + '{' + value.length + '}', 'ig').test(value);
}, WPMUDEV_Metaboxes.discount_error_msg);

(function($){
	jQuery(document).ready(function($){
		initConditionals();
		initValidation();
	});
	
	var initConditionals = function(){
		$('input[data-conditional-name][data-conditional-value][data-conditional-action]').each(function(){
			var $this = $(this),
					conditionalAction = $this.attr('data-conditional-action'),
					conditionalValue = $this.attr('data-conditional-value'),
					conditionalName = $this.attr('data-conditional-name'),
					$container = $this.closest('.wpmudev-field'),
					$conditionalInput = $('input[name="' + conditionalName + '"]');
					
			if ( ! $conditionalInput.is(':radio') && ! $conditionalInput.is(':checkbox') && ! $conditionalInput.is('select') ) {
				//conditional logic only works for radios, checkboxes and select dropdowns
				return;
			}
			
			if ( $conditionalInput.is('select') ) {
				var selected = $conditionalInput.val();
			} else {
				var selected = $conditionalInput.filter(':checked').val();
			}
			
			if ( conditionalAction == 'show' ) {
				if ( selected != conditionalValue ) {
					$container.hide();
				}
				
				$conditionalInput.change(function(){
					var $this = $(this);
					
					if ( $this.is('select') ) {
						var selected = $this.val();	
					} else {
						var selected = $this.filter(':checked').val();
					}
				
					if ( selected == conditionalValue ) {
						$container.slideDown(500);
					} else {
						$container.slideUp(500);
					}
				});
			}

			if ( conditionalAction == 'hide' ) {
				if ( selected != conditionalValue ) {
					$container.show();
				}		
				
				$conditionalInput.change(function(){
					var $this = $(this);
					
					if ( $this.is('select') ) {
						var selected = $this.val();	
					} else {
						var selected = $this.filter(':checked').val();
					}
					
					if ( selected == conditionalValue ) {
						$container.slideUp(500);
					} else {
						$container.slideDown(500);
					}
				});		
			}
		});
	};
	
	var initValidation = function(){
		$('[data-custom-validation]').each(function(){
			var $this = $(this);
			$this.rules('add', {
				"custom" : $this.attr('data-custom-validation')
			});
		});
		
		var $form = $("form#post");
		$form.find('#publish').click(function(e){
			e.preventDefault();
			
			$form.validate();
			
			if ( $form.valid() ) {
				$form.submit();
			}
		});
	}

}(jQuery));