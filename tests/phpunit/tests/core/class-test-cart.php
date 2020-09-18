<?php
/**
 * Cart tests
 *
 * @package   Shipping-Nova-Poshta-For-Woocommerce
 */

namespace Nova_Poshta\Core;

use Mockery;
use Nova_Poshta\Tests\Test_Case;

use function Brain\Monkey\Functions\expect;

/**
 * Class Test_Cart
 *
 * @package Nova_Poshta\Core
 */
class Test_Cart extends Test_Case {

	/**
	 * Test adding hooks
	 */
	public function test_hooks() {
		$cart = new Cart( Mockery::mock( 'Nova_Poshta\Core\Settings' ) );

		$cart->hooks();

		$this->assertTrue(
			has_filter( 'woocommerce_cart_get_total', [ $cart, 'cart_total' ] )
		);
	}

	/**
	 * Test left total by default.
	 */
	public function test_left_total_by_default() {
		$total    = 1000.11;
		$settings = Mockery::mock( 'Nova_Poshta\Core\Settings' );
		$settings
			->shouldReceive( 'exclude_shipping_from_total' )
			->withNoArgs()
			->once()
			->andReturn( false );
		$cart = new Cart( $settings );

		$this->assertSame(
			$total,
			$cart->cart_total( $total )
		);
	}

	/**
	 * Remove shipping cost from the total.
	 *
	 * @throws \Brain\Monkey\Expectation\Exception\ExpectationArgsRequired Invalid arguments.
	 */
	public function test_cart_total() {
		$total          = 1000.11;
		$shipping_total = 77;
		$settings       = Mockery::mock( 'Nova_Poshta\Core\Settings' );
		$settings
			->shouldReceive( 'exclude_shipping_from_total' )
			->withNoArgs()
			->once()
			->andReturn( true );
		$cart = Mockery::mock( 'WC_Cart' );
		$cart
			->shouldReceive( 'get_shipping_total' )
			->withNoArgs()
			->once()
			->andReturn( $shipping_total );
		expect( 'WC' )
			->withNoArgs()
			->once()
			->andReturn(
				(object) [
					'cart' => $cart,
				]
			);
		$cart = new Cart( $settings );

		$this->assertSame(
			$total - $shipping_total,
			$cart->cart_total( $total )
		);
	}

}
