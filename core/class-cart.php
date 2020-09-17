<?php
/**
 * Cart
 *
 * @package   Shipping-Nova-Poshta-For-Woocommerce
 * @author    Maksym Denysenko
 * @link      https://github.com/wppunk/shipping-nova-poshta-for-woocommerce
 * @copyright Copyright (c) 2020
 * @license   GPL-2.0+
 * @wordpress-plugin
 */

namespace Nova_Poshta\Core;

/**
 * Class AJAX
 *
 * @package Nova_Poshta\Core
 */
class Cart {

	/**
	 * Plugin settings.
	 *
	 * @var \Nova_Poshta\Core\Settings
	 */
	private $settings;

	/**
	 * Cart constructor.
	 *
	 * @param \Nova_Poshta\Core\Settings $settings Settings.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Add hooks
	 */
	public function hooks() {
		add_filter( 'woocommerce_cart_get_total', [ $this, 'cart_total' ] );
	}

	/**
	 * Update cart total.
	 *
	 * @param float $total Total.
	 *
	 * @return float
	 */
	public function cart_total( $total ) {
		if ( ! $this->settings->exclude_shipping_from_total() ) {
			return $total;
		}

		return $total - WC()->cart->get_shipping_total();
	}

}
