<?php

/**
 * @author: Hoang Ngo
 */
class Test_MP_Tax extends WP_UnitTestCase {

	/**
	 * Case we need to test, expected product price return in right amount, tax will be INCLUSIVE to product price
	 * 1. Tax Disable
	 * 2. Tax Enable
	 * 3. Tax enable, product price exclusive with tax(display)
	 * 4. Tax enable, product price inclusive with tax(display)
	 *
	 * Table Test
	 * Table having 3 row, 2 normal and one compound.
	 * Table having 2 row, all normal
	 * Table having 2 row, one normal, 1 compound
	 */
	function test_tax_product_inclusive() {
		//we need to init a test data for tax
		$data = $this->generate_tax_table( 1 );
		//update some data
		mp_update_setting( 'tax->tables_data', $data );
		mp_update_setting( 'tax->tax_calculate_based', 'store_address' );
		mp_update_setting( 'base_country', 'US' );
		mp_update_setting( 'base_province', 'IN' );
		mp_update_setting( 'base_zip', '47150' );
		mp_update_setting( 'tax->set_price_with_tax', 'inclusive' );

		$price = 11.67;
		//tax is disable
		mp_update_setting( 'tax->tax_enable', 0 );
		$tax = mp_tax()->product_price_with_tax( $price );
		$this->assertEquals( $price, $tax );
		mp_update_setting( 'tax->tax_enable', 1 );
		mp_update_setting( 'tax->show_price_with_tax', 'exclusive' );
		$tax = mp_tax()->product_price_with_tax( $price );
		//tax inclusive the price, and we will show the price without tax
		/**
		 * this having compound, so we need to find the tax without compound first 5%
		 * 11.67/(1.05) = 11.11428571428571
		 * now we got 13% and 10% = 23% = 0.23
		 * 11.11428571428571/1.23 = 9.036 = 9.04
		 */
		$this->assertEquals( 9.04, $tax );
		//tax inclsuive with the price, and we will show the price with tax
		mp_update_setting( 'tax->show_price_with_tax', 'inclusive' );
		$tax = mp_tax()->product_price_with_tax( $price );
		$this->assertEquals( $price, $tax );

		//====================== CASE 2 ===========================
		$data = $this->generate_tax_table( 2 );
		mp_update_setting( 'tax->tables_data', $data );
		mp_update_setting( 'tax->show_price_with_tax', 'exclusive' );
		$tax = mp_tax()->product_price_with_tax( $price );
		//tax inclusive the price, and we will show the price without tax
		/**
		 * this doesn't having compound, the tax sum is 19% = 0.19
		 * 11.67/(1.19) = 9.8
		 */
		$this->assertEquals( 9.8, $tax );
		//====================== CASE 3 ===========================
		$data = $this->generate_tax_table( 3 );
		mp_update_setting( 'tax->tables_data', $data );
		mp_update_setting( 'tax->show_price_with_tax', 'exclusive' );
		$tax = mp_tax()->product_price_with_tax( $price );
		//tax inclusive the price, and we will show the price without tax
		/**
		 * this having 8% compound, and 16% VAT
		 * 11.67/(1.08) = 10.81
		 * 10.81/(1.16)= 9.32
		 */
		$this->assertEquals( 9.32, $tax );
		//====================== CASE 4 ===========================
		$data = $this->generate_tax_table( 4 );
		mp_update_setting( 'tax->tables_data', $data );
		mp_update_setting( 'tax->show_price_with_tax', 'exclusive' );
		$tax = mp_tax()->product_price_with_tax( $price );
		//tax inclusive the price, and we will show the price without tax
		/**
		 * this having 33%
		 * 11.67/(1.33) = 8.77
		 */
		$this->assertEquals( 8.77, $tax );
	}

	/**
	 * Case we need to test, expected product price return in right amount, tax will be INCLUSIVE to product price
	 * 1. Tax Disable
	 * 2. Tax Enable
	 * 3. Tax enable, product price exclusive with tax(display)
	 * 4. Tax enable, product price inclusive with tax(display)
	 *
	 * Table Test
	 * Table having 3 row, 2 normal and one compound.
	 * Table having 2 row, all normal
	 * Table having 2 row, one normal, 1 compound
	 */
	function tax_product_exclusive() {
		//we need to init a test data for tax
	}

	function tax_cart_inclusive() {

	}

	function tax_cart_exclusive() {

	}

	/**
	 * Generate tax data
	 */
	private function generate_tax_table( $case ) {
		switch ( $case ) {
			case 1: {
				$data = array(
					'standard' => array(
						array(
							'country_code'   => 'US',
							'state_code'     => 'IN',
							'city'           => '*',
							'zip'            => '*',
							'rate'           => '10',
							'display_name'   => 'VAT',
							'priority'       => '1',
							'compound'       => '0',
							'apply_shipping' => '0',
						),
						array(
							'country_code'   => 'US',
							'state_code'     => '*',
							'city'           => '*',
							'zip'            => '*',
							'rate'           => '13',
							'display_name'   => 'State',
							'priority'       => '2',
							'compound'       => '0',
							'apply_shipping' => '0',
						),
						array(
							'country_code'   => 'US',
							'state_code'     => '*',
							'city'           => '*',
							'zip'            => '*',
							'rate'           => '5',
							'display_name'   => 'Compound',
							'priority'       => '3',
							'compound'       => '1',
							'apply_shipping' => '0',
						),
					),

				);
				break;
			}
			case 2: {
				$data = array(
					'standard' => array(
						array(
							'country_code'   => 'US',
							'state_code'     => 'IN',
							'city'           => '*',
							'zip'            => '*',
							'rate'           => '12',
							'display_name'   => 'VAT',
							'priority'       => '1',
							'compound'       => '0',
							'apply_shipping' => '0',
						),
						array(
							'country_code'   => 'US',
							'state_code'     => '*',
							'city'           => '*',
							'zip'            => '*',
							'rate'           => '7',
							'display_name'   => 'State',
							'priority'       => '2',
							'compound'       => '0',
							'apply_shipping' => '0',
						),
					),
				);
				break;
			}
			case 3: {
				$data = array(
					'standard' => array(
						array(
							'country_code'   => 'US',
							'state_code'     => 'IN',
							'city'           => '*',
							'zip'            => '*',
							'rate'           => '16',
							'display_name'   => 'VAT',
							'priority'       => '1',
							'compound'       => '0',
							'apply_shipping' => '0',
						),
						array(
							'country_code'   => 'US',
							'state_code'     => '*',
							'city'           => '*',
							'zip'            => '*',
							'rate'           => '8',
							'display_name'   => 'Compound',
							'priority'       => '3',
							'compound'       => '1',
							'apply_shipping' => '0',
						),
					),

				);
				break;
			}
			case 4: {
				$data = array(
					'standard' => array(
						array(
							'country_code'   => 'US',
							'state_code'     => 'IN',
							'city'           => '*',
							'zip'            => '*',
							'rate'           => '33',
							'display_name'   => 'VAT',
							'priority'       => '1',
							'compound'       => '0',
							'apply_shipping' => '0',
						),
					),

				);
				break;
			}
		}

		return $data;
	}
}
