jQuery(document).ready(function($) {
  var formfield;

  //open thickbox when button is clicked
  $('#mp_upload_button').click(function() {
    formfield = $('#mp_file');
		tb_show('Upload A Product File', 'media-upload.php?TB_iframe=true');
		return false;
	});

	// user inserts file into post. only run custom if user started process using the above process
	// window.send_to_editor(html) is how wp would normally handle the received data
	window.original_send_to_editor = window.send_to_editor;
	window.send_to_editor = function(html){
		if (formfield) {
			fileurl = $(html).attr('href');
			$(formfield).val(fileurl);
      formfield = false;
			tb_remove();
		} else {
			window.original_send_to_editor(html);
		}
	};

  //remove formfield whenever thickbox is closed
  $('a.thickbox, #TB_overlay, #TB_imageOff, #TB_closeWindowButton, #TB_TopCloseWindowButton').click(function(){
    formfield = false;
  });

  //checkbox toggle inventory field
  $('#mp_track_inventory').change(function(event){
		if(this.checked) {
      $('#mp_inventory_label').show();
		} else {
      $('#mp_inventory_label').hide();
    }
	});

	//setup del link html on load
	var var_del_html = $('.variation:last .mp_var_remove').html();
  $('.variation .mp_var_remove_last a').remove();
  
	//add new variation
  $('#mp_add_vars').click(function() {

    var var_html = '<tr class="variation">' + $('.variation:last').html() + '</tr>';
    $('.variation:last .mp_var_remove a').remove();
		$('.variation:last').after(var_html);
		
		//add back in remove link if missing
		if ($('.variation:last .mp_var_remove').html() == '')
      $('.variation:last .mp_var_remove').html(var_del_html);

		//remove variation
   	reg_remove_variation();
		return false;
	});

	function reg_remove_variation() {
		//remove variation
	  $('.mp_var_remove a').click(function() {
	    $('.variation:last').remove();
	    //add back in remove link if missing
			if ($('.variation').size() > 2 && $('.variation:last .mp_var_remove').html() == '')
	      $('.variation:last .mp_var_remove').html(var_del_html);
	    reg_remove_variation();
			return false;
		});
	}
	reg_remove_variation();
});