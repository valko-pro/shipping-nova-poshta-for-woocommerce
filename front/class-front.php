<?php
/**
 * Front area
 *
 * @package   Shipping-Nova-Poshta-For-Woocommerce
 * @author    Maksym Denysenko
 * @link      https://github.com/wppunk/shipping-nova-poshta-for-woocommerce
 * @copyright Copyright (c) 2020
 * @license   GPL-2.0+
 * @wordpress-plugin
 */

namespace Nova_Poshta\Front;

use Nova_Poshta\Core\Language;
use Nova_Poshta\Core\Main;

/**
 * Class Admin
 *
 * @package Nova_Poshta\Front
 */
class Front {

	/**
	 * Language
	 *
	 * @var Language
	 */
	private $language;

	/**
	 * Front constructor.
	 *
	 * @param Language $language Language.
	 */
	public function __construct( Language $language ) {
		$this->language = $language;
	}

	/**
	 * Add hooks
	 */
	public function hooks() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Enqueue styles
	 */
	public function enqueue_styles() {
		if ( ! is_checkout() ) {
			return;
		}
		wp_enqueue_style( 'np-select2', plugin_dir_url( __FILE__ ) . 'assets/css/select2.min.css', [], Main::VERSION, 'all' );
		wp_enqueue_style( Main::PLUGIN_SLUG, plugin_dir_url( __FILE__ ) . 'assets/css/main.css', [ 'np-select2' ], Main::VERSION, 'all' );
	}

	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts() {
		if ( ! is_checkout() ) {
			return;
		}
		wp_enqueue_script(
			'np-select2',
			plugin_dir_url( __FILE__ ) . 'assets/js/select2.min.js',
			[ 'jquery' ],
			Main::VERSION,
			true
		);
		wp_enqueue_script(
			'select2-i18n-' . $this->language->get_current_language(),
			plugin_dir_url( __FILE__ ) . 'assets/js/i18n/' . $this->language->get_current_language() . '.js',
			[ 'jquery', 'np-select2' ],
			Main::VERSION,
			true
		);
		wp_enqueue_script(
			Main::PLUGIN_SLUG,
			plugin_dir_url( __FILE__ ) . 'assets/js/main.js',
			[
				'jquery',
				'np-select2',
			],
			Main::VERSION,
			true
		);
		wp_localize_script(
			Main::PLUGIN_SLUG,
			'shipping_nova_poshta_for_woocommerce',
			[
				'url'      => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( Main::PLUGIN_SLUG ),
				'language' => $this->language->get_current_language(),
			]
		);
	}

}
