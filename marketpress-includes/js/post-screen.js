jQuery(document).ready(function($) {
  var formfield;

  //open thickbox when button is clicked
  jQuery('#mp_upload_button').click(function() {
    formfield = jQuery('#mp_file');
		tb_show('Upload A Product File', 'media-upload.php?TB_iframe=true');
		return false;
	});

	// user inserts file into post. only run custom if user started process using the above process
	// window.send_to_editor(html) is how wp would normally handle the received data
	window.original_send_to_editor = window.send_to_editor;
	window.send_to_editor = function(html){
		if (formfield) {
			fileurl = jQuery(html).attr('href');
			jQuery(formfield).val(fileurl);
      formfield = false;
			tb_remove();
		} else {
			window.original_send_to_editor(html);
		}
	};

  //remove formfield whenever thickbox is closed
  jQuery('a.thickbox, #TB_overlay, #TB_imageOff, #TB_closeWindowButton, #TB_TopCloseWindowButton').click(function(){
    formfield = false;
  });

  //checkbox toggle inventory field
  jQuery('#mp_track_inventory').change(function(event){
		if(this.checked) {
      jQuery('#mp_inventory_label').show();
		} else {
      jQuery('#mp_inventory_label').hide();
    }
	});
});