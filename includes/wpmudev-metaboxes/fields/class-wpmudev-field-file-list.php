<?php
/** 
 * File field that allows you to add a new row by clicking a plus button
 *
 * @author Paul Kevin
 */

/**
 * File List Class
 * Allows you to add a new row for multiple file uploads for an item
 *
 * @class WPMUDEV_Field_File_List
 */
class WPMUDEV_Field_File_List extends WPMUDEV_Field {

	/**
	 * Runs on creation of parent
	 *
	 * @since 1.0
	 * @access public
	 * @param array $args {
	 *		Any array of arguments. Optional.
	 *
	 *		@type string $title The text that shows up in the header of the media lightbox. Optional.
	 *		@type string $button_label The label of the send-to-editor button. Optional.
	 * }
	 */
	public function on_creation( $args ) {
        $this->args = array_replace_recursive(array(
			'title'         => __( 'Select the file that you would like to use.', 'wpmudev_metaboxes' ),
			'button_label'  => __( 'Select File', 'wpmudev_metaboxes' ),
		), $args);
		
		$this->args[ 'custom' ][ 'data-media-title' ]           = $this->args[ 'title' ];
		$this->args[ 'custom' ][ 'data-media-button-label' ]    = $this->args[ 'button_label' ];
		$this->args[ 'style' ] .= ' width:60%;';
	}

	/**
	 * Enqueues necessary field javascript.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function enqueue_scripts() {
		wp_enqueue_script('media-upload');
		
		// 3.5 media gallery
		if ( function_exists('wp_enqueue_media') && ! did_action('wp_enqueue_media') ) {
            wp_enqueue_media();
		}
	}

	/**
	 * Print necessary field javascript.
	 *
	 * @since 3.2.4
	 * @access public
	 */
	public function print_scripts() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($){
            /*
            * Show the media library popup
            */
            $('.wpmudev-fields').on('click', '.wpmudev-field-file-select', function(e){
                e.preventDefault();
                
                var $this = $(this),
                    $input = $this.siblings('input'),
                    frame = wp.media({
                            "title"     : $input.attr('data-media-title'),
                            "multiple"  : false,
                            "button"    : { "text" : $input.attr('data-media-button-label') }
                    });
                
                /*
                * Send image data back to the calling field
                */
                frame.on('select', function(){
                    var selection = frame.state().get('selection');
                    
                    selection.each(function(attachment){
                        $input.val(attachment.attributes.url);
                    });
                });
                
                /*
                * Set the selected image
                */
                frame.on('open', function(){
                    var selection = frame.state().get('selection'),
                    id = $input.val();
            
                    if ( id.length ) {
                        var attachment = wp.media.attachment(id);
                        attachment.fetch();
                        selection.add(attachment ? [attachment] : []);
                    }
                });
                
                frame.open();
            });
            
            //Add field button
            $('.wpmudev-fields .add-file').click(function(e) {
                e.preventDefault();
                var newInput = $(".wpmudev-fields .file-list .file:first").clone();
                newInput.find("input").val('');
                newInput.find("a.add-file").remove();
                newInput.append('<a href="#" class="button mp_file_action remove-file"><span class="dashicons dashicons-minus"></span></a>')
                $('.wpmudev-fields .file-list').append(newInput);
            });

            //Remove field button
            $(document).on('click','.wpmudev-fields .remove-file',function(e) {
                e.preventDefault();
                $(this).parent().remove();
            });
        });
        </script>
        <?php
        parent::print_scripts();
	}


	/**
	 * Displays the field.
	 *
	 * @since 3.2.4
	 * @access public
	 * @param int $post_id
	 */
	public function display( $post_id ) {

		$values = $this->get_value($post_id);
		$this->before_field(); 

		//Set the name to be an array
		$this->args[ 'name' ] = $this->args[ 'name' ] . '[]'; ?>
		<div class="file-list">
            <?php

            if ( is_array( $values ) ){

                $index = 0;
                foreach( $values as $key => $value ){

                    //Button Classes
                    //Add button classes by default
                    $classes = array( 'add' , 'plus' );
                    if ( $index > 0 ){
                        //Remove button classes for the other items
                        $classes = array( 'remove' , 'minus' );
                    }
                    
                    ?>
                    <div class="file">
                        <input type="text" <?php echo $this->parse_atts(); ?> value="<?php echo $value; ?>" /> <a class="button wpmudev-field-file-select" href="#"><?php _e('Select File', 'wpmudev_metaboxes'); ?></a><a href="#" class="button mp_file_action <?php echo $classes[0]; ?>-file"><span class="dashicons dashicons-<?php echo $classes[1]; ?>"></span></a>
                    </div>
                    <?php
                    $index++;
                }
            } else {
            ?>
            <div class="file">
		        <input type="text" <?php echo $this->parse_atts(); ?> value="<?php echo $values; ?>" /> <a class="button wpmudev-field-file-select" href="#"><?php _e('Select File', 'wpmudev_metaboxes'); ?></a><a href="#" class="button mp_file_action add-file"><span class="dashicons dashicons-plus"></span></a>
            </div>
            <?php } ?>
		</div>
		<?php
		$this->after_field();
	}
    
}
?>