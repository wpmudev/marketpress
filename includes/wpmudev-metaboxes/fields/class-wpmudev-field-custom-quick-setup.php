<?php

class WPMUDEV_Field_Quick_Setup extends WPMUDEV_Field {

	/**
	 * Runs on construct of parent
	 *
	 * @since 1.0
	 * @access public
	 * @param array $args {
	 * 		Array of arguments. Optional.
	 *
	 * 		@type string $after_field Text show after the input field.
	 * 		@type string $before_field Text show before the input field.
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = array_replace_recursive( array(
			'before_field'	 => '',
			'after_field'	 => '',
		), $args );
	}

	/**
	 * Displays the field
	 *
	 * @since 1.0
	 * @access public
	 * @param int $post_id
	 */
	public function display( $post_id ) {
		$this->before_field();

		$quick_setup_step = mp_get_get_value( 'quick_setup_step' );

		if ( empty( $quick_setup_step ) ) {//First Step / Page installation
			?>
			<div class="mp_quick_setup_step mp_quick_setup_step-1">
				<div class="mp_content">
					<img class="mp_quick_setup_image-welcome mp_image" src="<?php echo mp_plugin_url( 'includes/admin/ui/images/mp_quick_setup-welcome.png' ); ?>" alt="<?php _e('MarketPress - Quick Setup', 'mp'); ?>" height="158" width="158">
					<h3 class="mp_title"><?php _e( 'Welcome to MarketPress - Quick Setup', 'mp' ); ?></h3>
					<p><?php _e( 'MarketPress adds a full online store to your website, with heaps of configuration options and addons <br>to suit your needs. It\'s really easy to get going, and only takes a few minutes to setup!', 'mp' ); ?></p>
					<!--<p><?php // _e( 'MarketPress adds a full online store to your website. It\'s really easy to get gogin, and only takes a few minutes to setup!', 'mp' );        ?></p>-->
					<p><?php _e( 'Once you\'ve completed this quick setup wizard you\'ll have a fully working online store - exciting! Start <br>by creating the <strong>default store pages</strong> for the online store such as the cart, checkout and store pages.', 'mp' ); ?></p>
				</div><!-- end mp_content -->

				<?php if ( get_option( 'mp_needs_pages', 1 ) == 1 && current_user_can( 'manage_options' ) ) { ?>
					<div class="mp_callout">
						<a href="<?php echo admin_url( add_query_arg( array( 'page' => 'store-setup-wizard', 'install_mp_pages' => 'true' ), 'admin.php' ) ); ?>" class="button-primary mp_button"><?php _e( 'Install store pages', 'mp' ); ?></a>
					</div><!-- end mp_callout -->

					<div class="mp_skip_step">
						<a class="mp_link mp_link-skip-step" href="<?php echo admin_url( add_query_arg( array( 'page' => 'store-setup-wizard', 'quick_setup_step' => 'skip' ), 'admin.php' ) ); ?>"><?php _e( 'Skip this step, I\'ll do this manually', 'mp' ); ?></a>
					</div><!-- end mp_skip_step -->
				<?php } else {
					?>
					<div class="mp_callout">
						<a href="<?php echo admin_url( add_query_arg( array( 'page' => 'store-setup-wizard', 'quick_setup_step' => '2' ), 'admin.php' ) ); ?>" class="button-primary mp_button"><?php _e( 'Continue', 'mp' ); ?></a>
					</div>
				<?php }
				?>
			</div><!-- end mp_quick_setup_content_step-1 -->

			<?php
		} else if ( $quick_setup_step == '2' ) {//Second step with tabs and settings
			?>
			<div class="mp_quick_setup_step mp_quick_setup_step-2 mp_quick_setup_step-has-tabs">
				<div class="mp_content">
					<h3 class="mp_title"><?php _e( 'Welcome to MarketPress - Quick Setup', 'mp' ); ?></h3>
					<p><?php _e( 'Choose where you want to sell your stuff and what currency. Easy!', 'mp' ); ?></p>
				</div><!-- end mp_content -->

				<div class="mp_content-tabs">
					<div id="mp-quick-setup-tabs" class="mp_quick_setup_tabs">

						<ul class="mp_tabs_labels">
							<li class="mp_tab_label"><a class="mp_tab_label_link" href="#mp-quick-setup-tab-locations"><span class="dashicons-before dashicons-admin-site mp_icon mp_icon-inline mp_icon-rounded"></span> <?php _e( 'Locations', 'mp' ); ?></a></li>
							<li class="mp_tab_label"><a class="mp_tab_label_link" href="#mp-quick-setup-tab-currency-and-tax"><span class="dashicons-before dashicons-tag mp_icon mp_icon-inline mp_icon-rounded"></span> <?php _e( 'Currency & Tax', 'mp' ); ?></a></li>
							<li class="mp_tab_label"><a class="mp_tab_label_link" href="#mp-quick-setup-tab-metric-system"><span class="dashicons-before dashicons-cart mp_icon mp_icon-inline mp_icon-rounded"></span> <?php _e( 'Metric System', 'mp' ); ?></a></li>
							<li class="mp_tab_label"><a class="mp_tab_label_link" href="#mp-quick-setup-tab-payment-gateway"><span class="dashicons-before dashicons-feedback mp_icon mp_icon-inline mp_icon-rounded"></span> <?php _e( 'Payment Gateway', 'mp' ); ?></a></li>
						</ul><!-- end mp_tabs_labels -->

						<div class="mp_tabs_content">

							<div id="mp-quick-setup-tab-locations" class="mp_tab">
								<div class="mp_tab_content">
									<div id="mp-content-locations" class="mp_tab_content_locations mp_content_col mp_content_col-one-third">
										<p class="mp_tab_content_label"><strong><?php _e( 'Locations', 'mp' ); ?></strong></p>
										<p><?php _e( 'Where is your online store based?', 'mp' ); ?></p>
									</div><!-- end mp_tab_content_locations -->
									<div id="mp-content-countries" class="mp_tab_content_countries mp_content_col mp_content_col-two-thirds">
										<p class="mp_tab_content_label">&nbsp;</p>
										<p><?php _e( 'And, which countries do you want to sell to?', 'mp' ); ?></p>
									</div><!-- end mp_tab_content_countries -->
								</div><!-- end mp_tab_content -->
								<div class="mp_tab_navigation">
									<a href="#" class="button-secondary mp_button mp_button_tab_nav-next"><?php _e( 'Next', 'mp' ); ?></a>
								</div><!-- end mp_tab_navigation -->
							</div><!-- end mp-quick-setup-tab-locations -->

							<div id="mp-quick-setup-tab-currency-and-tax" class="mp_tab">
								<div class="mp_tab_content">
									<div id="mp-content-currency" class="mp_tab_content_currency mp_content_col mp_content_col-one-half">
										<p class="mp_tab_content_label"><?php _e( 'Currency', 'mp' ); ?></p>
										<p><?php _e( 'What currency do you want to sell with?', 'mp' ); ?></p>
									</div><!-- end mp_tab_content_currency -->
									<div id="mp-content-tax" class="mp_tab_content_tax mp_content_col mp_content_col-one-half">
										<p class="mp_tab_content_label"><?php _e( 'Tax', 'mp' ); ?></p>
										<p><?php _e( 'Do you want to apply tax for your products? <em>You can customize this for each product and variation</em>.', 'mp' ); ?></p>
									</div><!-- end mp_tab_content_tax -->
								</div><!-- end mp_tab_content -->
								<div class="mp_tab_navigation">
									<a href="#" class="button-secondary mp_button mp_button_tab_nav-prev"><?php _e( 'Back', 'mp' ); ?></a>
									<a href="#" class="button-secondary mp_button mp_button_tab_nav-next"><?php _e( 'Next', 'mp' ); ?></a>
								</div><!-- end mp_tab_navigation -->
							</div><!-- end mp-quick-setup-tab-currency-and-tax -->

							<div id="mp-quick-setup-tab-metric-system" class="mp_tab">
								<div class="mp_tab_content">

									<div class="mp_tab_content_block">
										<div id="mp-content-metric-system" class="mp_tab_content_metric_system">
											<p class="mp_tab_content_label"><?php _e( 'Metric System', 'mp' ); ?></p>
											<p><?php _e( 'And what metric system do you want to use?', 'mp' ); ?></p>
										</div><!-- end mp-content-metric-system -->
									</div><!-- end mp_tab_content_block -->
									
									<hr class="mp_tab_sep" />

									<div class="mp_tab_content_block">
										<div id="mp-content-shipping" class="mp_tab_content_shipping">
											<?php do_action( 'mp_wizard_shipping_section' ) ?>
											<div id="mp-content-shipping-details" class="mp_tab_content_details mp_tab_content_details-shipping">
												<?php do_action( 'mp_wizard_shipping_rule_section' ) ?>
												<?php do_action( '_mp_wizard_shipping_rule_section' ) ?>
											</div><!-- end mp-content-shipping-details -->
										</div><!-- end mp-content-shipping -->
									</div><!-- end mp_tab_content_block -->

								</div><!-- end mp_tab_content -->
								<div class="mp_tab_navigation">
									<a href="#" class="button-secondary mp_button mp_button_tab_nav-prev"><?php _e( 'Back', 'mp' ); ?></a>
									<a href="#" class="button-secondary mp_button mp_button_tab_nav-next"><?php _e( 'Next', 'mp' ); ?></a>
									<!--<a href="<?php echo admin_url( add_query_arg( array( 'page' => 'store-setup-wizard', 'quick_setup_step' => '3' ), 'admin.php' ) ); ?>" class="button-primary"><?php _e( 'Finish Setup', 'mp' ); ?></a>-->
								</div><!-- end mp_tab_navigation -->
							</div><!-- mp-quick-setup-tab-metric-system -->
							

							<div id="mp-quick-setup-tab-payment-gateway" class="mp_tab">
								<div class="mp_tab_content">
									<div id="mp-content-payment-gateway" class="mp_tab_content_payment_gateway">
										<?php do_action( 'mp_wizard_payment_gateway_section' ) ?>
										<div class="mp_tab_content_details mp_tab_content_details-payment-gateway">
											<?php do_action( 'mp_wizard_payment_gateway_details' ) ?>
										</div><!-- end mp_tab_content_details-payment-gateway -->
									</div><!-- end mp_tab_content_payment_gateway -->
								</div><!-- end mp_tab_content -->
								<div class="mp_tab_navigation">
									<a href="#" class="button-secondary mp_button mp_button_tab_nav-prev"><?php _e( 'Back', 'mp' ); ?></a>
									<input class="button-primary mp_button mp_button_tab_nav-finish" type="submit" name="submit_settings" value="<?php _e( 'Finish Setup', 'mp' ); ?>">
								</div>
								<!-- end mp_tab_navigation -->
							</div>
							<!-- end payment gateway tab section -->
						</div><!-- end mp_tabs_content -->

					</div><!-- end mp_quick_setup_tabs -->
				</div><!-- end mp_content-tabs -->

			</div><!-- end mp_quick_setup_content_step-2 -->
			<?php
		} else {//Final Step

			// If Skip
			if( $quick_setup_step == 'skip' ) {
				update_option( 'mp_needs_quick_setup', 'skip' );
			} else {
				update_option( 'mp_needs_quick_setup', 0 );
			}
			?>
			<div class="mp_quick_setup_step mp_quick_setup_step-3">
				<div class="mp_content">
					<h3 class="mp_title"><?php _e( 'Woohoo! Your online store is up and running.', 'mp' ); ?></h3>
					<p><?php _e( 'Your store is almost ready for its first customers, but first it needs some products. Get started by adding products below, or jump straight into further configuring your store’s settings.', 'mp' ); ?></p>
				</div><!-- end mp_content -->
				<div class="mp_callout">
					<div class="mp_content_col mp_content_col-one-half">
						<span class="dashicons dashicons-welcome-write-blog mp_icon mp_icon-stack mp_icon-gray mp_icon-big"></span>
						<p><?php _e( '<strong>Add your first product</strong> for sale and get familiar with adding products.', 'mp' ); ?></p>
						<a href="<?php echo admin_url( 'post-new.php?post_type=product' ); ?>" class="button-primary mp_button mp_button-add-product"><?php _e( 'Add Product', 'mp' ); ?></a>
					</div>

					<div class="mp_content_col mp_content_col-one-half">
						<span class="dashicons dashicons-admin-settings mp_icon mp_icon-stack mp_icon-gray mp_icon-big"></span>
						<p><?php _e( '<strong>Configure</strong> shipping rates, emails and your store\'s appearance. ', 'mp' ); ?></p>
						<a href="<?php echo admin_url( 'admin.php?page=store-settings' ); ?>" class="button-primary mp_button mp_button-configure-store"><?php _e( 'Configure Store', 'mp' ); ?></a>
					</div>
				</div><!-- end mp_callout -->
			</div><!-- end mp_quick_setup_content_step-3 -->
			<?php
		}
		$this->after_field();
	}

}
