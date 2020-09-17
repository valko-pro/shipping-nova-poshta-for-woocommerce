<?php
/**
 * Admin area in WooCommerce order list
 *
 * @package   Shipping-Nova-Poshta-For-Woocommerce
 * @author    Maksym Denysenko
 * @link      https://github.com/wppunk/shipping-nova-poshta-for-woocommerce
 * @copyright Copyright (c) 2020
 * @license   GPL-2.0+
 * @wordpress-plugin
 */

namespace Nova_Poshta\Admin;

/**
 * Class Admin
 *
 * @package Nova_Poshta\Admin
 */
class Admin_Woocommerce_Order_List {

	/**
	 * Add hooks
	 */
	public function hooks() {
		add_filter( 'manage_edit-shop_order_columns', [ $this, 'register_columns' ] );
		add_action( 'manage_shop_order_posts_custom_column', [ $this, 'internet_document_column' ] );
	}

	/**
	 * Register custom columns.
	 *
	 * @param array $columns List of columns.
	 *
	 * @return mixed
	 */
	public function register_columns( $columns ) {
		$keys = array_values( array_keys( $columns ) );
		$key  = array_search( 'order_total', $keys, true );
		$key  = $key ? $key : 6;

		return array_slice( $columns, 0, $key, true ) + [ 'internet_document' => esc_html__( 'Invoice', 'shipping-nova-poshta-for-woocommerce' ) ] + array_slice( $columns, $key, count( $columns ) - 1, true );
	}

	/**
	 * Fill internet document column.
	 *
	 * @param string $column Column name.
	 */
	public function internet_document_column( $column ) {
		global $post;

		if ( 'internet_document' !== $column ) {
			return;
		}
		$order           = wc_get_order( $post->ID );
		$shipping_method = $order->get_shipping_methods();
		if ( ! $shipping_method ) {
			return;
		}
		$shipping_method = array_shift( $shipping_method );
		echo esc_html( $shipping_method->get_meta( 'internet_document', true ) );
	}
}
