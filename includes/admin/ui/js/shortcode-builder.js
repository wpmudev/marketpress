(function($){
	$(document).ready(function($){
		initShortCodeBuilder();
		initColorbox();
		initSelect2();
		initProductSearchField();
		initToolTips();
	});

	var initToolTips = function(){
		$('.mp-tooltip').click(function(){
			var $this = $(this),
					$button = $this.find('.mp-tooltip-button');

			if ( $button.length == 0 ) {
				$this.children('span').append('<a class="mp-tooltip-button" href="#">x</a>');
			}

			$this.children('span').css({
				"display" : "block",
				"margin-top" : -($this.children('span').outerHeight() / 2)
			});
		});

		$('.mp-tooltip').on('click', '.mp-tooltip-button', function(e){
			e.preventDefault();
			e.stopPropagation();
			$(this).parent().fadeOut(250);
		});
	}

 	var initShortCodeBuilder = function() {
		var $form = $('#mp-shortcode-builder-form');

		$form.find('[name="shortcode"]').change(function(){
			var $table = $('#' + $(this).val().replace(/_/g, '-') + '-shortcode');

			if ( $table.length == 0 ) {
				$form.find('.form-table').hide();
				$.colorbox.resize();
				return; // bail
			}

			$table.show().siblings('.form-table').hide();
			$.colorbox.resize({
				"height" : "80%"
			});
			refreshChosenFields();
		});

		$form.submit(function(e){
			e.preventDefault();

			var shortcode = '[' + $form.find('[name="shortcode"]').val();
			var atts = '';

			$form.find('.form-table').filter(':visible').find(':input').filter('[name]').each(function(){
				var $this = $(this);

				if ( ($.trim($this.val()).length == 0 || ($this.attr('data-default') !== undefined && $this.attr('data-default') == $.trim($this.val()))) && !($this.is(':radio') || $this.is(':checkbox')) ) {
					return; // Don't include empty fields or fields that are default values
				}

				if ( $this.is(':radio') || $this.is(':checkbox') ) {
					if ( $this.is(':checked') ) {
						atts += ' ' + $this.attr('name') + '="' + $this.val() + '"';
					} else {
						if( $this.val() === "1" ){
							atts += ' ' + $this.attr('name') + '="0"';
						}	
					}
				} else {
					atts += ' ' + $this.attr('name') + '="' + $this.val() + '"';
				}
			});

			shortcode += atts + ']';

			window.send_to_editor(shortcode);
			$.colorbox.close();
		});
	};

	var refreshChosenFields = function() {
		$('.mp-chosen-select').trigger('chosen:updated');
	};

	var initColorbox = function() {
		$('body').on('click', '.mp-shortcode-builder-button', function(){
			var $this = $(this);

			$.colorbox({
				"width" : 800,
				"maxWidth" : "80%",
				"height" : "80%",
				"inline" : true,
				"href" : "#mp-shortcode-builder-form",
				"opacity" : 0.7
			});
		});
	};

	var initSelect2 = function() {
		$('.mp-chosen-select').mp_select2({
			"width" : "100%"
		});
	};

	var initProductSearchField = function() {
		$('input.mp-select-product').each(function(){
			var $this = $(this);

			$this.mp_select2({
				"multiple" : false,
				"placeholder" : MP_ShortCode_Builder.select_product,
				"width" : "100%",
				"ajax" : {
					"url" : ajaxurl,
					"dataType" : "json",
					"data" : function(term, page){
						return {
							"search_term" : term,
							"page" : page,
							"action" : "mp_shortcode_builder_search_products"
						}
					},
					"results" : function(data, page){
						var more = (page * data.post_per_page) < data.total;
						return {
							"results" : data.posts,
							"more" : more
						}
					}
				}
			})
		});
	};
	
}(jQuery));
