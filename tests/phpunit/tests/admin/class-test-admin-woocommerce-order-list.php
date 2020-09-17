<?php
/**
 * Admin area tests
 *
 * @package   Shipping-Nova-Poshta-For-Woocommerce
 */

namespace Nova_Poshta\Admin;

use Mockery;
use stdClass;
use Nova_Poshta\Tests\Test_Case;

use function Brain\Monkey\Functions\when;
use function Brain\Monkey\Functions\expect;

/**
 * Class Test_Admin
 *
 * @package Nova_Poshta\Admin
 */
class Test_Admin_Woocommerce_Order_List extends Test_Case {

	/**
	 * Test adding hooks
	 */
	public function test_hooks() {
		$admin_woocommerce_order_list = new Admin_Woocommerce_Order_List();

		$admin_woocommerce_order_list->hooks();

		$this->assertTrue( has_filter( 'manage_edit-shop_order_columns', [ $admin_woocommerce_order_list, 'register_columns' ] ) );
		$this->assertTrue( has_action( 'manage_shop_order_posts_custom_column', [ $admin_woocommerce_order_list, 'internet_document_column' ] ) );
	}

	/**
	 * Test register columns.
	 */
	public function test_register_columns() {
		when( 'esc_html__' )->returnArg();
		$admin_woocommerce_order_list = new Admin_Woocommerce_Order_List();

		$this->assertSame(
			[
				'key1'              => 'Key2',
				'internet_document' => 'Invoice',
				'order_total'       => 'Order total',
				'key3'              => 'Key3',
			],
			$admin_woocommerce_order_list->register_columns(
				[
					'key1'        => 'Key2',
					'order_total' => 'Order total',
					'key3'        => 'Key3',
				]
			)
		);
	}

	/**
	 * Test skip not internet document column.
	 */
	public function test_NOT_internet_document_column() {
		$admin_woocommerce_order_list = new Admin_Woocommerce_Order_List();
		$admin_woocommerce_order_list->internet_document_column( 'other_key' );
	}

	/**
	 * Test skip orders without shipping methods.
	 *
	 * @throws \Brain\Monkey\Expectation\Exception\ExpectationArgsRequired Invalid arguments.
	 */
	public function test_NOT_shipping_methods_in_internet_document_column() {
		global $post;
		$post_id                      = 7;
		$post                         = new stdClass(); //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post->ID                     = $post_id;
		$admin_woocommerce_order_list = new Admin_Woocommerce_Order_List();
		$order                        = Mockery::mock( 'WC_Order' );
		$order
			->shouldReceive( 'get_shipping_methods' )
			->withNoArgs()
			->once()
			->andReturn( null );
		expect( 'wc_get_order' )
			->with( $post_id )
			->once()
			->andReturn( $order );

		$admin_woocommerce_order_list->internet_document_column( 'internet_document' );
	}

	/**
	 * Test internet document column.
	 *
	 * @throws \Brain\Monkey\Expectation\Exception\ExpectationArgsRequired Invalid arguments.
	 */
	public function test_internet_document_column() {
		when( 'esc_html' )->returnArg();
		global $post;
		$internet_document            = '1234 5678 9012 3456';
		$post_id                      = 7;
		$post                         = new stdClass(); //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post->ID                     = $post_id;
		$admin_woocommerce_order_list = new Admin_Woocommerce_Order_List();
		$shipping_method              = Mockery::mock( 'WC_Shipping_Method' );
		$shipping_method
			->shouldReceive( 'get_meta' )
			->with( 'internet_document', true )
			->once()
			->andReturn( $internet_document );
		$order = Mockery::mock( 'WC_Order' );
		$order
			->shouldReceive( 'get_shipping_methods' )
			->withNoArgs()
			->once()
			->andReturn(
				[
					$shipping_method,
				]
			);
		expect( 'wc_get_order' )
			->with( $post_id )
			->once()
			->andReturn( $order );
		ob_start();

		$admin_woocommerce_order_list->internet_document_column( 'internet_document' );

		$this->assertSame( $internet_document, ob_get_clean() );
	}

}
