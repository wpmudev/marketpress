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
			<h3><?php _e( 'Welcome to MarketPress - Quick Setup', 'mp' ); ?></h3>
			<p><?php _e( 'MarketPress adds a full online store to your website. It\'s really easy to get gogin, and only takes a few minutes to setup!', 'mp' ); ?></p>
			<p><?php _e( 'Once you\'ve completed this quick setup wizard you\'ll have a fully working online store - exciting! Start by creating the default store pages for the online store such as the cart, checkout and store pages.', 'mp' ); ?></p>

			<a href="" class="button-primary"><?php _e( 'Install store pages', 'mp' ); ?></a>

			<p><a class="mp_skip_step" href="<?php echo admin_url( add_query_arg( array( 'page' => 'store-setup-wizard', 'quick_setup_step' => '2' ), 'admin.php' ) ); ?>"><?php _e( 'Skip this step, I\'ll do this manually', 'mp' ); ?></a></p>

			<?php
		} else if ( $quick_setup_step == '2' ) {//Second step with tabs and settings
			?>
			<h3><?php _e( 'Welcome to MarketPress - Quick Setup', 'mp' ); ?></h3>
			<p><?php _e( 'Choose where you want to sell your stuff and what currency. Easy!', 'mp' ); ?></p>

			<div id="mp-quick-setup-tabs">
				<ul>
					<li><a href="#tabs-1"><?php _e( 'Locations', 'mp' ); ?></a></li>
					<li><a href="#tabs-2"><?php _e( 'Currency & Tax', 'mp' ); ?></a></li>
					<li><a href="#tabs-3"><?php _e( 'Metric System', 'mp' ); ?></a></li>
				</ul>
				<div id="tabs-1" class="mp-quick-setup-tab">
					<div class="mp-tab-content">
						<div class="mp-tab-locations mp-quick-settings-one-third">
							<p class="p-title"><?php _e( 'Locations', 'mp' ); ?></p>
							<p><?php _e( 'Where is your online store based?', 'mp' ); ?></p>
						</div>
						<div class="mp-tab-countries mp-quick-settings-two-thirds">
							<p class="p-title">&nbsp;</p>
							<p><?php _e( 'And, which countries do you want to sell to?', 'mp' ); ?></p>
						</div>
						<div class="clearfix"></div>
					</div>
					<div class="mp-tab-navigation">
						<a href="" class="button-secondary"><?php _e( 'Next', 'mp' ); ?></a>
					</div>
				</div>
				<div id="tabs-2" class="mp-quick-setup-tab">
					<div class="mp-tab-content">
						<div class="mp-tab-currency">
							<p class="p-title"><?php _e( 'Currency', 'mp' ); ?></p>
							<p><?php _e( 'What currency do you want to sell with?', 'mp' ); ?></p>
						</div>
						<div class="mp-tab-tax">
							<p class="p-title"><?php _e( 'Tax', 'mp' ); ?></p>
							<p><?php _e( 'Do you want to apply tax for your products? You can customize this for each product and vatiation', 'mp' ); ?></p>
						</div>
						<div class="clearfix"></div>
					</div>
					<div class="mp-tab-navigation">
						<a href="" class="button-secondary"><?php _e( 'Next', 'mp' ); ?></a>
					</div>
				</div>
				<div id="tabs-3" class="mp-quick-setup-tab">
					<div class="mp-tab-content">
						<div class="mp-tab-measurement-system">
							<p><?php _e( 'And what metric system do you want to use?', 'mp' ); ?></p>
						</div>

					</div>
					<div class="mp-tab-navigation">
						<input class="button-primary" type="submit" name="submit_settings" value="<?php _e( 'Finish Setup', 'mp' ); ?>" />
						<!--<a href="<?php echo admin_url( add_query_arg( array( 'page' => 'store-setup-wizard', 'quick_setup_step' => '3' ), 'admin.php' ) ); ?>" class="button-primary"><?php _e( 'Finish Setup', 'mp' ); ?></a>-->
					</div>
				</div>
			</div>

			<?php
		} else {//Final Step
			?>
			<h3><?php _e( 'Markethoo! Your online store is up and running!', 'mp' ); ?></h3>
			<p><?php _e( 'That\'s all your new store needs to work! However every store needs products...Get started adding products bellow, or jump straight into configuring your stores settings further.', 'mp' ); ?></p>

			<div class="mp-quick-settings-one-half">
				<?php _e( 'Add your first product for sale and get familliar with adding products.', 'mp' ); ?>
				<a href="" class="button-primary add-product"><?php _e( 'Add Product', 'mp' ); ?></a>
			</div>

			<div class="mp-quick-settings-one-half">
				<?php _e( 'Configure shipping rates, emails and your store\'s appearance. ', 'mp' ); ?>
				<a href="" class="button-primary configure-store"><?php _e( 'Configure Store', 'mp' ); ?></a>
			</div>
			<?php
		}
		$this->after_field();
	}

}
