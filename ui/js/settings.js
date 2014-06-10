
jQuery(document).ready(function($){
	//Sticky navigation
	$('.mp-tabs').sticky({
		"topSpacing" : 45
	});
	
	//Inline-help tooltips
	setInterval(function(){
		$('.mp-help-icon').not('[data-hasqtip]').each(function(){
			var $this = $(this);
			
			$this.qtip({
				"show" : {
					"event" : "click",
					"solo" : true
				},
				"hide" : {
					"event" : "click",
					"fixed" : true
				},
				"position" : {
					"my" : "left center",
					"at" : "right center",
					"adjust" : {
						"x" : -10
					}
				},
				"style" : "qtip-shadow",
				"content" : {
					"button" : true,
					"text" : $this.next('.mp-help-text')
				},
			});
		});
	},25);
});