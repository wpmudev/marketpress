<?php

$mp_settings_metaboxes['coupons'] = new MP_Settings_Metabox_Coupon_Settings();

class MP_Settings_Metabox_Coupon_Settings extends MP_Settings_Metabox {
	/**
	 * Initialize class variables
	 *
	 * @since 3.0.0
	 */
	
	function init_vars() {
		$this->title = __('Coupons', 'mp');
		$this->tab = 'coupons';
	}
	
	/**
	 * Constructor function
	 *
	 * @since 3.0.0
	 */
	
	function on_creation() {
		add_action('admin_footer', array(&$this, 'coupon_form'));
		add_action('wp_ajax_mp_save_coupon', array(&$this, 'save_coupon'));
		add_action('wp_ajax_mp_get_coupon', array(&$this, 'get_coupon'));
		add_action('wp_ajax_mp_delete_coupon', array(&$this, 'delete_coupon'));
	}
	
	/**
	 * Delete a coupon(ajax)
	 *
	 * @since 3.0.0
	 */
	
	function delete_coupon() {
		check_ajax_referer('mp_delete_coupon', 'mp_delete_coupon_nonce');
		$coupons = get_option('mp_coupons');
		$coupon = mp_arr_get_value($_POST['coupon_id'], $coupons);
		
		if ( $coupon ) {
			unset($coupons[$_POST['coupon_id']]);
			update_option('mp_coupons', $coupons);
			wp_send_json_success();
		}
		
		wp_send_json_error();
	}
	
	/**
	 * Get a coupon (ajax)
	 *
	 * @since 3.0.0
	 */
	
	function get_coupon() {
		$coupon_id = mp_get_post_value('coupon_id');
		$json = array();
		$coupons = get_option('mp_coupons');
		$coupon = mp_arr_get_value($coupon_id, $coupons);
		
		if ( empty($coupon_id) || !$coupon ) {
			$json['message'] = __('An invalid coupon code was provided', 'mp');
			wp_send_json_error($json);
		}
		
		$json['coupon_code'] = $coupon_id;
		
		foreach ( $coupon as $key => $val ) {
			switch ( $key ) {
				case 'applies_to' :
					$json[$key] = ( empty($val) ) ? 'all' : $val;
				break;
				
				case 'end' :
					$json[$key] =  ( empty($val) ) ? '' : $val;
				break;
				
				default :
					$json[$key] = $val;
				break;
			}
		}
		
		wp_send_json_success($json);
	}
	
	/**
	 * Saves the coupon (ajax)
	 *
	 * @since 3.0.0
	 */
	
	function save_coupon() {
		check_ajax_referer('mp_save_coupon', 'mp_save_coupon_nonce');

		$coupons = get_option('mp_coupons');
		$json = array(
			'errors' => array(),
			'message' => __('Coupon succesfully saved.', 'mp'),
		);
		
		if ( !is_array($coupons) )
			$coupons = array();
				
		//add or update a coupon
		$new_coupon_code = preg_replace('/[^A-Z0-9_-]/', '', strtoupper(mp_get_post_value('coupon_code')));
		if ( empty($new_coupon_code) )
			$json['errors']['coupon_code'][] = __('Please enter a valid Coupon Code', 'mp');
		elseif ( mp_arr_get_value($new_coupon_code, $coupons) && mp_get_post_value('old_coupon_code', '') == '' )
			$json['errors']['coupon_code'][] = __('The Coupon Code entered already exists', 'mp');

		$coupons[$new_coupon_code]['discount'] = round((float) mp_get_post_value('discount', 2));
		if ( $coupons[$new_coupon_code]['discount'] <= 0 )
			$json['errors']['discount_type'][] = __('Please enter a valid Discount Amount', 'mp');

		$coupons[$new_coupon_code]['discount_type'] = mp_get_post_value('discount_type');
		if ( $coupons[$new_coupon_code]['discount_type'] != 'amt' && $coupons[$new_coupon_code]['discount_type'] != 'pct' )
			$json['errors']['discount_type'][] = __('Please choose a valid Discount Type', 'mp');

		$coupons[$new_coupon_code]['start'] = strtotime(mp_get_post_value('start'));
		if ( $coupons[$new_coupon_code]['start'] === false )
			$json['errors']['start'][] = __('Please enter a valid Start Date', 'mp');

		$coupons[$new_coupon_code]['end'] = strtotime(mp_get_post_value('end'));
		if ( $coupons[$new_coupon_code]['end'] && $coupons[$new_coupon_code]['end'] < $coupons[$new_coupon_code]['start'] )
			$json['errors']['end'][] = __('Please enter a valid End Date not earlier than the Start Date', 'mp');

		$coupons[$new_coupon_code]['uses'] = mp_get_post_value('uses');
		if ( !empty($coupons[$new_coupon_code]['uses']) && preg_match('/[^0-9]/', $coupons[$new_coupon_code]['uses']) )
			$json['errors']['uses'][] = __('Uses must be a numeric value', 'mp');
		else
			$coupons[$new_coupon_code]['uses'] = (float) $coupons[$new_coupon_code]['uses'];

		// applies to
		$applies_to = mp_get_post_value('applies_to');
		switch( $applies_to ) {
			case 'all':
				$coupons[$new_coupon_code]['applies_to'] = '';
			break;
			 
			case 'category';
			 	$coupons[$new_coupon_code]['applies_to']['type'] = $applies_to;
			 	$coupons[$new_coupon_code]['applies_to']['id'] = mp_get_post_value('coupon_category');
			 	
			 	if( empty($coupons[$new_coupon_code]['applies_to']['id']) ) {
					$json['errors']['coupon_category'][] = __('Please choose a Product Category to apply the coupon to', 'mp');
				}
			break;
			 
			case 'product':
			 	$coupons[$new_coupon_code]['applies_to']['type'] = $applies_to;
			 	$coupons[$new_coupon_code]['applies_to']['id'] = mp_get_post_value('coupon_product');
			 	
				if( empty($coupons[$new_coupon_code]['applies_to']['id']) ) {
					$json['errors']['coupon_product'][] = __('Please choose an Individual Product to apply the coupon to', 'mp');
				}
			break;
		}
	
		if ( count($json['errors']) == 0 ) {
			update_option('mp_coupons', $coupons);
			wp_send_json_success($json); 
		} else {
			wp_send_json_error($json);
		}
		
		exit;
	}
	
	/**
	 * Enqueue scripts/styles
	 *
	 * @since 3.0.0
	 */
	
	function enqueue_scripts() {
		add_thickbox();
	}
	
	/**
	 * Output add/edit coupon form for use with thickbox
	 *
	 * @since 3.0.0
	 */
	
	function coupon_form() {
		if ( !$this->is_active() )
			return;
		
		//setup defaults
		$new_coupon_code = preg_replace('/[^A-Z0-9_-]/', '', strtoupper(mp_get_post_value('coupon_code')));
		
		if (isset($coupons[$new_coupon_code]))
			$discount = (isset($coupons[$new_coupon_code]['discount']) && isset($coupons[$new_coupon_code]['discount_type']) && $coupons[$new_coupon_code]['discount_type'] == 'amt') ? round($coupons[$new_coupon_code]['discount'], 2) : $coupons[$new_coupon_code]['discount'];
		else
			$discount = '';
			
		$discount_type = isset($coupons[$new_coupon_code]['discount_type']) ? $coupons[$new_coupon_code]['discount_type'] : '';
		$start = !empty($coupons[$new_coupon_code]['start']) ? date('Y-m-d', $coupons[$new_coupon_code]['start']) : date('Y-m-d');
		$end = !empty($coupons[$new_coupon_code]['end']) ? date('Y-m-d', $coupons[$new_coupon_code]['end']) : '';
		$uses = isset($coupons[$new_coupon_code]['uses']) ? $coupons[$new_coupon_code]['uses'] : '';
		//
		$applies_to = ( isset($coupons[$new_coupon_code]['applies_to']['type'] ) ) ? $coupons[$new_coupon_code]['applies_to']['type'] : 'all';
		$applies_to_id	= ( isset( $coupons[$new_coupon_code]['applies_to']['id'] ) ) ? $coupons[$new_coupon_code]['applies_to']['id'] : '';	
		
		//get all the product categories
		$product_cats = get_terms( array( 'product_category'), array( 'hide_empty' => false ) );
				
		//get products
		$products = new WP_Query( array( 'post_type' => 'product', 'posts_per_page' => 500, 'update_post_term_cache' => false, 'update_post_meta_cache' => false ) );
		?>
		<div id="mp-coupon-form-holder" style="display:none">
			<form id="mp-coupon-form" method="post" action="<?php echo admin_url('admin-ajax.php?action=mp_save_coupon'); ?>">
				<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<?php _e('Coupon Code', 'mp') ?><br />
						<small style="font-weight: normal;"><?php _e('Letters and Numbers only', 'mp') ?></small>
					</th>
					<td>
						<input data-default="" value="<?php echo $new_coupon_code ?>" name="coupon_code" type="text" style="text-transform: uppercase;" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Discount', 'mp') ?></th>
					<td>
						<input data-default="" value="<?php echo $discount; ?>" size="3" name="discount" type="text" />
						<select data-default="amt" name="discount_type" style="vertical-align:top">
						 <option value="amt"<?php selected($discount_type, 'amt') ?>><?php echo mp_format_currency(); ?></option>
						 <option value="pct"<?php selected($discount_type, 'pct') ?>>%</option>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Start Date', 'mp') ?></th>
					<td>
						<input data-default="<?php echo date('Y-m-d'); ?>" value="<?php echo $start; ?>" class="pickdate" size="11" name="start" type="text" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<?php _e('Expire Date', 'mp') ?><br />
						<small style="font-weight: normal;"><?php _e('No end if blank', 'mp') ?></small>
					</th>
					<td>
						<input data-default="" value="<?php echo $end; ?>" class="pickdate" size="11" name="end" type="text" />
					</td>
				</tr>
				<tr valign="top">
					<th>
						<?php _e('Allowed Uses', 'mp') ?><br />
						<small style="font-weight: normal;"><?php _e('Unlimited if blank', 'mp') ?></small>
					</th>
					<td>
						<input data-default="" value="<?php echo $uses; ?>" size="4" name="uses" type="text" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e('Applies To', 'mp'); ?></th>
					<td>
					<select data-default="all" id="applies_to" name="applies_to">
						<option value="all" <?php selected($applies_to, 'all');?>><?php _e('All Products','mp');?></option>
						<option value="category" <?php selected($applies_to, 'category');?><?php echo ( !isset($cat_select) ) ? ' disabled' : ''; ?>><?php _e('Product Category','mp');?></option>
						<option value="product" <?php selected($applies_to, 'product');?><?php echo ( !is_wp_error($products) && $products->have_posts() ) ? '' : ' disabled'; ?>><?php _e('Product','mp');?></option>
					</select>
					</td>
				</tr>
				<tr id="coupon-category-row" style="<?php echo ( $applies_to == 'category' ) ? '' : 'display:none'; ?>" valign="top">
					<th scope="row"><?php _e('Product Category', 'mp'); ?></th>
					<td>
						<?php
						if ( is_array($product_cats) && count($product_cats) > 0 ) :
						?>
						<select class="mp-chosen-select" name="coupon_category" id="coupon_category" style="<?php echo ( $applies_to == 'category' ) ? '' : 'display:none'; ?>" multiple>
							<?php
							foreach( $product_cats as $cat ) {
								echo '<option value="' . $cat->term_id . '" ' . selected($applies_to_id, $cat->term_id, false) . '	>' . $cat->name . '</option>';
							}
							?>
						</select>
						<?php
						endif;
						?>
					</td>
				</tr>
				<tr id="coupon-product-row" style="<?php echo ( $applies_to == 'product' ) ? '' : 'display:none'; ?>" valign="top">
					<th scope="row"><?php _e('Product', 'mp'); ?></th>
					<td>
						<?php
						if ( !is_wp_error($products) ) :
							if ( $products->found_posts > 500) :
						?>
						<label id="coupon_product"><?php _e('Post ID','mp');?>
							<input type="text" value="<?php echo ( $applies_to == 'product' ) ? $applies_to_id : ''; ?>" name="coupon_product" />
						</label>
						<?php
							else :
						?>
							<select class="mp-chosen-select" name="coupon_product" id="coupon_product" style="width:350px" multiple>
								<?php
								foreach ( $products->posts as $product ) {
									echo '<option value="' . $product->ID . '" '. selected($applies_to_id, $product->ID, false) . '	 >' . $product->post_title . '</option>';
								}
								?>
							</select>
						<?php
							endif;
						endif;
						?>
					</td>
				</tr>
				</table>
				
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Save Coupon', 'mp'); ?>" />
				</p>
				
				<input type="hidden" name="old_coupon_code" value="" />
				<?php wp_nonce_field('mp_save_coupon', 'mp_save_coupon_nonce'); ?>
			</form>
			<div class="mp-coupon-loading" style="position:absolute;bottom:15px;left:15px;"><img src="<?php echo mp_plugin_url('ui/images/ajax-loader.gif'); ?>" alt="" /> &nbsp; Loading...</div>
		</div>
		<?php
	}
	
	/**
	 * Save metabox settings
	 *
	 * @since 3.0.0
	 * @param array $settings
	 * @return array
	 */
			
	function save_settings( $settings ) {
		//delete checked coupons
		if ( mp_get_post_value('coupon_delete') ) {
			check_admin_referer('bulk-product_page_store-settings');

			$coupons = get_option('mp_coupons', array());
				//loop through and delete
				foreach ($_POST['coupon_delete'] as $del_code) {}
					unset($coupons[$del_code]);

				update_option('mp_coupons', $coupons);
				
				//display message confirmation
				$this->updated = '<div class="updated fade"><p>'.__('Coupon(s) succesfully deleted.', 'mp').'</p></div>';
		}
				
		return $settings;
	}
	
	/**
	 * Print metabox javascript
	 *
	 * @since 3.0.0
	 */
	
	function print_scripts() {
		
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$.datepicker.setDefaults($.datepicker.regional['<?php echo mp()->language; ?>']);
				$('.pickdate').datepicker({
				 	"dateFormat" : "yy-mm-dd",
				 	"changeMonth" : true,
				 	"changeYear" : true,
				 	"minDate" : 0,
				 	"firstDay" : <?php echo (get_option('start_of_week') == '0') ? 7 : get_option('start_of_week'); ?>
				});
				
				$('body').on('submit', '#mp-coupon-form', function(evt){
					evt.preventDefault();
					
					var $this = $(this);
					
					$this.find('.mp-error').remove();
					
					$.post($this.attr('action'), $this.serialize()).done(function(req){
						if ( req.success ) {
							window.location.href = '<?php echo add_query_arg('message', 1); ?>';
							return;
						}
						
						for ( i in req.data.errors ) {
							for ( x = 0; x < req.data.errors[i].length; x++ ) {
								$('[name="' + i + '"]').after('<span class="mp-error">' + req.data.errors[i][x] + '</span>');
							}
						}
					});
				});
				
				$('body').on('change', '#applies_to', function(){
					var type = 'coupon_' + $(this).val();
					switch ( type ) {
						case 'coupon_all':
							$('#coupon-category-row').hide();
							$('#coupon-product-row').hide();
						break;
						
						case 'coupon_category':
							$('#coupon-category-row').show();
							$('#coupon-product-row').hide();
						break;
						
						case 'coupon_product':
							$('#coupon-category-row').hide();
							$('#coupon-product-row').show();
						break;
					}
				});
				
				$('.mp-add-coupon-link').click(function(evt){
					evt.preventDefault();
					resetCouponForm();
					$('.mp-coupon-loading').hide();
				});
				
				$('.mp-delete-coupon-link').click(function(evt){
					evt.preventDefault();
					
					if ( confirm('<?php _e('Are you sure you want to delete this coupon? This action can not be undone!', 'mp'); ?>') ) {
						var $this = $(this),
								data = [
									{
										"name" : "action",
										"value" : "mp_delete_coupon"
									},
									{
										"name" : "coupon_id",
										"value" : $this.attr('data-coupon-id')
									},
									{
										"name" : "mp_delete_coupon_nonce",
										"value" : "<?php echo wp_create_nonce('mp_delete_coupon'); ?>"
									}
								];
						
						$.post(ajaxurl, $.param(data)).done(function(res){
							if ( !res.success ) {
								alert('<?php _e('An error occurred whilie deleting the coupon. Please try again.', 'mp'); ?>');
								return;
							}
							
							window.location.href = '<?php echo add_query_arg('message', 2); ?>';
						});
					}
				});
				
				$('.mp-edit-coupon-link').click(function(evt){
					evt.preventDefault();
					
					resetCouponForm();
					$('.mp-coupon-loading').show();
					
					var $this = $(this),
							$form = $('#mp-coupon-form'),
							data = [
								{
									"name" : "coupon_id",
									"value" : $this.attr('data-coupon-id')
								},
								{
									"name" : "mp_get_coupon_nonce",
									"value" : "<?php echo wp_create_nonce('mp_get_coupon'); ?>"
								},
								{
									"name" : "action",
									"value" : "mp_get_coupon"
								}
							];
					
					$.post(ajaxurl, $.param(data)).done(function(req){
						$('.mp-coupon-loading').hide();
						
						if ( !req.success ) {
							alert(req.message);
							return;
						}
						
						for ( i in req.data ) {
							switch ( i ) {
								case 'start' :
								case 'end' :
									$('[name="' + i + '"]').datepicker('setDate', req.data[i]);
								break;
								
								default :
									$('[name="' + i + '"]').val(req.data[i]);
								break;
							}
						}
						
						$('[name="old_coupon_code"]').val(req.data['coupon_code']);
					});
				});
				
				resetCouponForm();
				
				function resetCouponForm() {
					$('#mp-coupon-form').find('[name]').each(function(){
						var $this = $(this);
						
						if ( $this.attr('data-default') != undefined )
							$this.val($this.attr('data-default'));
					});
					
					$('.mp-coupon-loading').show();
				}
			});
		</script>		
		<?php
	}
	
	/**
	 * Display metabox
	 *
	 * @since 3.0.0
	 */
	
	function display_inside() {
		//if editing a coupon
		$new_coupon_code = mp_arr_get_value('code', $_GET, null);
		
		switch ( mp_arr_get_value('message', $_GET) ) {
			case 1 :
				echo '<div class="updated fade"><p>' . __('Coupon saved successfully', 'mp') . '</p></div>';
			break;
			
			case 2 :
				echo '<div class="updated fade"><p>' . __('Coupon deleted successfully', 'mp') . '</p></div>';
			break;
		}		
		?>
		<p><?php _e('You can create, delete, or update coupon codes for your store here.', 'mp') ?></p>
		<p><a class="button thickbox mp-add-coupon-link" title="<?php _e('Add Coupon Form', 'mp'); ?>" href="#TB_inline&width=800&height=600&inlineId=mp-coupon-form-holder"><?php _e('Add Coupon', 'mp'); ?></a></p>
		
		<?php
		include_once mp_plugin_dir('includes/admin/class-mp-coupons-list-table.php');
		$mp_coupons_list_table = new MP_Coupons_List_Table();
		$mp_coupons_list_table->prepare_items();
		$mp_coupons_list_table->display();		
	}
}