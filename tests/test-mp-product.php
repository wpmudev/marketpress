<?php

class MP_Product_Tests extends WP_UnitTestCase {

	function test_in_stock() {
		$product	 = $this->factory->post->create_and_get( array( 'post_type' => 'product' ) );
		$variation	 = $this->factory->post->create_and_get( array( 'post_type' => MP_Product::get_variations_post_type(), 'post_parent' => $product->ID ) );

		add_post_meta( $variation->ID, '_inventory', 'WPMUDEV_Field_Text' );
		add_post_meta( $variation->ID, 'inventory', 1 );

		$variation = new MP_Product( $variation );
		$this->expectOutputString( '1' );
		echo $variation->in_stock( 2 );
	}

}
