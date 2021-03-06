<?php
/**
 * Shipping cost tests
 *
 * @package   Shipping-Nova-Poshta-For-Woocommerce
 */

namespace Nova_Poshta\Core;

use Exception;
use Mockery;
use Nova_Poshta\Tests\Test_Case;
use function Brain\Monkey\Functions\expect;

/**
 * Class Test_API
 *
 * @package Nova_Poshta\Core
 */
class Test_Shipping_Cost extends Test_Case {

	/**
	 * Shipping cost disabled
	 *
	 * @throws Exception Invalid DateTime.
	 */
	public function test_shipping_cost_disabled() {
		$api      = Mockery::mock( 'Nova_Poshta\Core\API' );
		$settings = Mockery::mock( 'Nova_Poshta\Core\Settings' );
		$settings
			->shouldReceive( 'is_shipping_cost_enable' )
			->once()
			->andReturn( false );
		$calculator = Mockery::mock( 'Nova_Poshta\Core\Calculator' );

		$shipping_cost = new Shipping_Cost( $api, $settings, $calculator );
		$shipping_cost->calculate( 'city-id', [] );
	}

	/**
	 * Shipping cost with default product weight and dimensions
	 *
	 * @throws Exception Invalid DateTime.
	 */
	public function test_shipping_cost_with_default_weight_and_dimensions() {
		$city_id            = 'city-id';
		$cost               = 48;
		$product1_weight    = 10;
		$product2_weight    = 20;
		$product1_dimension = 15;
		$product2_dimension = 25;
		expect( 'get_option' )
			->with( 'woocommerce_weight_unit' )
			->twice()
			->andReturn( 'weight_unit' );
		expect( 'get_option' )
			->with( 'woocommerce_dimension_unit' )
			->times( 6 )
			->andReturn( 'dimension_unit' );
		expect( 'wc_get_weight' )
			->with( $product1_weight, 'kg', 'weight_unit' )
			->once()
			->andReturn( $product1_weight );
		expect( 'wc_get_weight' )
			->with( $product2_weight, 'kg', 'weight_unit' )
			->once()
			->andReturn( $product2_weight );
		expect( 'wc_get_dimension' )
			->with( $product1_dimension, 'm', 'dimension_unit' )
			->times( 3 )
			->andReturn( $product1_dimension );
		expect( 'wc_get_dimension' )
			->with( $product2_dimension, 'm', 'dimension_unit' )
			->times( 3 )
			->andReturn( $product2_dimension );
		$api = Mockery::mock( 'Nova_Poshta\Core\API' );
		$api
			->shouldReceive( 'shipping_cost' )
			->with(
				$city_id,
				$product1_weight + $product2_weight,
				( $product1_dimension ^ 3 ) + ( $product2_dimension ^ 3 )
			)
			->once()
			->andReturn( $cost );
		$settings = Mockery::mock( 'Nova_Poshta\Core\Settings' );
		$settings
			->shouldReceive( 'is_shipping_cost_enable' )
			->once()
			->andReturn( true );
		$calculator = Mockery::mock( 'Nova_Poshta\Core\Calculator' );
		$calculator
			->shouldReceive( 'result' )
			->with( '(' . $product1_dimension . ') * (' . $product1_dimension . ') * (' . $product1_dimension . ')', 1 )
			->once()
			->andReturn( $product1_dimension ^ 3 );
		$calculator
			->shouldReceive( 'result' )
			->with( '(' . $product2_dimension . ') * (' . $product2_dimension . ') * (' . $product2_dimension . ')', 1 )
			->once()
			->andReturn( $product2_dimension ^ 3 );
		$product1 = Mockery::mock( 'WC_Product' );
		$product1
			->shouldReceive( 'get_weight' )
			->once()
			->andReturn( $product1_weight );
		$product1
			->shouldReceive( 'get_width', 'get_length', 'get_height' )
			->once()
			->andReturn( $product1_dimension );
		$product2 = Mockery::mock( 'WC_Product' );
		$product2
			->shouldReceive( 'get_weight' )
			->once()
			->andReturn( $product2_weight );
		$product2
			->shouldReceive( 'get_width', 'get_length', 'get_height' )
			->once()
			->andReturn( $product2_dimension );
		$shipping_cost = new Shipping_Cost( $api, $settings, $calculator );

		$shipping_cost->calculate(
			$city_id,
			[
				[
					'quantity' => 1,
					'data'     => $product1,
				],
				[
					'quantity' => 1,
					'data'     => $product2,
				],
			]
		);
	}

	/**
	 * Shipping cost with default parent product weight and dimensions. Need for Variable Products
	 *
	 * @throws Exception Invalid DateTime.
	 */
	public function test_shipping_cost_with_default_weight_and_dimensions_for_parent_product() {
		$city_id                   = 'city-id';
		$cost                      = 48;
		$product1_weight           = false;
		$product1_dimension        = false;
		$product1_parent_id        = 11;
		$product1_parent_weight    = 10;
		$product1_parent_dimension = 15;
		expect( 'get_option' )
			->with( 'woocommerce_weight_unit' )
			->twice()
			->andReturn( 'weight_unit' );
		expect( 'get_option' )
			->with( 'woocommerce_dimension_unit' )
			->times( 2 )
			->andReturn( 'dimension_unit' );
		expect( 'wc_get_weight' )
			->with( $product1_parent_weight, 'kg', 'weight_unit' )
			->once()
			->andReturn( $product1_parent_weight );
		expect( 'wc_get_dimension' )
			->with( $product1_parent_dimension, 'm', 'dimension_unit' )
			->times( 3 )
			->andReturn( $product1_parent_dimension );
		$api = Mockery::mock( 'Nova_Poshta\Core\API' );
		$api
			->shouldReceive( 'shipping_cost' )
			->with(
				$city_id,
				$product1_parent_weight,
				$product1_parent_dimension ^ 3
			)
			->once()
			->andReturn( $cost );
		$settings = Mockery::mock( 'Nova_Poshta\Core\Settings' );
		$settings
			->shouldReceive( 'is_shipping_cost_enable' )
			->once()
			->andReturn( true );
		$calculator = Mockery::mock( 'Nova_Poshta\Core\Calculator' );
		$calculator
			->shouldReceive( 'result' )
			->with( '(' . $product1_parent_dimension . ') * (' . $product1_parent_dimension . ') * (' . $product1_parent_dimension . ')', 1 )
			->once()
			->andReturn( $product1_parent_dimension ^ 3 );
		$product1 = Mockery::mock( 'WC_Product' );
		$product1
			->shouldReceive( 'get_weight' )
			->once()
			->andReturn( $product1_weight );
		$product1
			->shouldReceive( 'get_width', 'get_length', 'get_height' )
			->once()
			->andReturn( $product1_dimension );
		$product1
			->shouldReceive( 'get_parent_id' )
			->times( 8 )
			->andReturn( $product1_parent_id );
		$product1_parent = Mockery::mock( 'WC_Product' );
		$product1_parent
			->shouldReceive( 'get_weight' )
			->once()
			->andReturn( $product1_parent_weight );
		$product1_parent
			->shouldReceive( 'get_width', 'get_length', 'get_height' )
			->once()
			->andReturn( $product1_parent_dimension );
		expect( 'wc_get_product' )
			->with( $product1_parent_id )
			->times( 4 )
			->andReturn( $product1_parent );

		$shipping_cost = new Shipping_Cost( $api, $settings, $calculator );

		$shipping_cost->calculate(
			$city_id,
			[
				[
					'quantity' => 1,
					'data'     => $product1,
				],
			]
		);
	}

	/**
	 * Shipping cost with product formulas
	 *
	 * @throws Exception Invalid DateTime.
	 */
	public function test_shipping_cost_with_product_formula() {
		$city_id              = 'city-id';
		$cost                 = 48;
		$product1_category_id = 11;
		$product2_category_id = 22;
		$weight               = 10;
		$weight_formula       = '10 + [qty]';
		$dimension            = 15;
		$dimension_formula    = '15 + [qty]';
		$api                  = Mockery::mock( 'Nova_Poshta\Core\API' );
		$api
			->shouldReceive( 'shipping_cost' )
			->with(
				$city_id,
				$weight + $weight,
				( $dimension ^ 3 ) + ( $dimension ^ 3 )
			)
			->once()
			->andReturn( $cost );
		$settings = Mockery::mock( 'Nova_Poshta\Core\Settings' );
		$settings
			->shouldReceive( 'is_shipping_cost_enable' )
			->once()
			->andReturn( true );
		$calculator = Mockery::mock( 'Nova_Poshta\Core\Calculator' );
		$calculator
			->shouldReceive( 'result' )
			->with( $weight_formula, 1 )
			->twice()
			->andReturn( $weight );
		$calculator
			->shouldReceive( 'result' )
			->with( $dimension_formula, 1 )
			->times( 6 )
			->andReturn( $dimension );
		$calculator
			->shouldReceive( 'result' )
			->with( '(' . $dimension . ') * (' . $dimension . ') * (' . $dimension . ')', 1 )
			->twice()
			->andReturn( $dimension ^ 3 );
		$product1 = Mockery::mock( 'WC_Product' );
		$product1
			->shouldReceive( 'get_weight' )
			->andReturn( false )
			->once();
		$product1
			->shouldReceive( 'get_width', 'get_length', 'get_height' )
			->andReturn( false )
			->once();
		$product1
			->shouldReceive( 'get_parent_id' )
			->times( 8 )
			->andReturn( false );
		$product1
			->shouldReceive( 'get_meta' )
			->with( 'weight_formula', true )
			->once()
			->andReturn( $weight_formula );
		$product1
			->shouldReceive( 'get_meta' )
			->with(
				Mockery::anyOf(
					'height_formula',
					'length_formula',
					'width_formula'
				),
				true
			)
			->times( 3 )
			->andReturn( $dimension_formula );
		$product2 = Mockery::mock( 'WC_Product' );
		$product2
			->shouldReceive( 'get_weight' )
			->andReturn( false )
			->once();
		$product2
			->shouldReceive( 'get_width', 'get_length', 'get_height' )
			->andReturn( false )
			->once();
		$product2
			->shouldReceive( 'get_parent_id' )
			->times( 8 )
			->andReturn( false );
		$product2
			->shouldReceive( 'get_meta' )
			->with( 'weight_formula', true )
			->once()
			->andReturn( $weight_formula );
		$product2
			->shouldReceive( 'get_meta' )
			->with(
				Mockery::anyOf(
					'height_formula',
					'length_formula',
					'width_formula'
				),
				true
			)
			->times( 3 )
			->andReturn( $dimension_formula );

		$shipping_cost = new Shipping_Cost( $api, $settings, $calculator );

		$shipping_cost->calculate(
			$city_id,
			[
				[
					'quantity' => 1,
					'data'     => $product1,
				],
				[
					'quantity' => 1,
					'data'     => $product2,
				],
			]
		);
	}

	/**
	 * Shipping cost with product category formulas
	 *
	 * @throws Exception Invalid DateTime.
	 */
	public function test_shipping_cost_with_product_category_formula() {
		$city_id              = 'city-id';
		$cost                 = 48;
		$product1_category_id = 11;
		$product2_category_id = 22;
		$weight               = 10;
		$weight_formula       = '10 + [qty]';
		$dimension            = 15;
		$dimension_formula    = '15 + [qty]';
		$api                  = Mockery::mock( 'Nova_Poshta\Core\API' );
		$api
			->shouldReceive( 'shipping_cost' )
			->with(
				$city_id,
				$weight + $weight,
				( $dimension ^ 3 ) + ( $dimension ^ 3 )
			)
			->once()
			->andReturn( $cost );
		$settings = Mockery::mock( 'Nova_Poshta\Core\Settings' );
		$settings
			->shouldReceive( 'is_shipping_cost_enable' )
			->once()
			->andReturn( true );
		$calculator = Mockery::mock( 'Nova_Poshta\Core\Calculator' );
		$calculator
			->shouldReceive( 'result' )
			->with( $weight_formula, 1 )
			->twice()
			->andReturn( $weight );
		$calculator
			->shouldReceive( 'result' )
			->with( $dimension_formula, 1 )
			->times( 6 )
			->andReturn( $dimension );
		$calculator
			->shouldReceive( 'result' )
			->with( '(' . $dimension . ') * (' . $dimension . ') * (' . $dimension . ')', 1 )
			->twice()
			->andReturn( $dimension ^ 3 );
		$product1 = Mockery::mock( 'WC_Product' );
		$product1
			->shouldReceive( 'get_weight' )
			->andReturn( false )
			->once();
		$product1
			->shouldReceive( 'get_width', 'get_length', 'get_height' )
			->andReturn( false )
			->once();
		$product1
			->shouldReceive( 'get_parent_id' )
			->times( 8 )
			->andReturn( false );
		$product1
			->shouldReceive( 'get_meta' )
			->with( 'weight_formula', true )
			->once()
			->andReturn( false );
		$product1
			->shouldReceive( 'get_meta' )
			->with( 'width_formula', true )
			->once()
			->andReturn( false );
		$product1
			->shouldReceive( 'get_meta' )
			->with( 'height_formula', true )
			->once()
			->andReturn( false );
		$product1
			->shouldReceive( 'get_meta' )
			->with( 'length_formula', true )
			->once()
			->andReturn( false );
		$product1
			->shouldReceive( 'get_category_ids' )
			->times( 8 )
			->andReturn( [ $product1_category_id ] );
		$product2 = Mockery::mock( 'WC_Product' );
		$product2
			->shouldReceive( 'get_weight' )
			->andReturn( false )
			->once();
		$product2
			->shouldReceive( 'get_width', 'get_length', 'get_height' )
			->andReturn( false )
			->once();
		$product2
			->shouldReceive( 'get_parent_id' )
			->times( 8 )
			->andReturn( false );
		$product2
			->shouldReceive( 'get_meta' )
			->with( 'weight_formula', true )
			->once()
			->andReturn( false );
		$product2
			->shouldReceive( 'get_meta' )
			->with( 'width_formula', true )
			->once()
			->andReturn( false );
		$product2
			->shouldReceive( 'get_meta' )
			->with( 'height_formula', true )
			->once()
			->andReturn( false );
		$product2
			->shouldReceive( 'get_meta' )
			->with( 'length_formula', true )
			->once()
			->andReturn( false );
		$product2
			->shouldReceive( 'get_category_ids' )
			->times( 8 )
			->andReturn( [ $product2_category_id ] );
		expect( 'get_term_meta' )
			->with( 'weight_formula', true )
			->twice()
			->andReturn( $weight_formula );
		expect( 'get_term_meta' )
			->with(
				Mockery::anyOf(
					'height_formula',
					'length_formula',
					'width_formula'
				),
				true
			)
			->times( 6 )
			->andReturn( $dimension_formula );

		$shipping_cost = new Shipping_Cost( $api, $settings, $calculator );

		$shipping_cost->calculate(
			$city_id,
			[
				[
					'quantity' => 1,
					'data'     => $product1,
				],
				[
					'quantity' => 1,
					'data'     => $product2,
				],
			]
		);
	}

	/**
	 * Shipping cost with settings formulas
	 *
	 * @throws Exception Invalid DateTime.
	 */
	public function test_shipping_cost_with_default_settings_formula() {
		$city_id                   = 'city-id';
		$cost                      = 48;
		$default_weight            = 10;
		$default_weight_formula    = '10 + [qty]';
		$default_dimension         = 15;
		$default_dimension_formula = '15 + [qty]';
		$api                       = Mockery::mock( 'Nova_Poshta\Core\API' );
		$api
			->shouldReceive( 'shipping_cost' )
			->with(
				$city_id,
				$default_weight + $default_weight,
				( $default_dimension ^ 3 ) + ( $default_dimension ^ 3 )
			)
			->once()
			->andReturn( $cost );
		$settings = Mockery::mock( 'Nova_Poshta\Core\Settings' );
		$settings
			->shouldReceive( 'is_shipping_cost_enable' )
			->once()
			->andReturn( true );
		$settings
			->shouldReceive( 'default_weight_formula' )
			->twice()
			->andReturn( $default_weight_formula );
		$settings
			->shouldReceive( 'default_width_formula', 'default_height_formula', 'default_length_formula' )
			->twice()
			->andReturn( $default_dimension_formula );
		$calculator = Mockery::mock( 'Nova_Poshta\Core\Calculator' );
		$calculator
			->shouldReceive( 'result' )
			->with( $default_weight_formula, 1 )
			->twice()
			->andReturn( $default_weight );
		$calculator
			->shouldReceive( 'result' )
			->with( $default_dimension_formula, 1 )
			->times( 6 )
			->andReturn( $default_dimension );
		$calculator
			->shouldReceive( 'result' )
			->with( '(' . $default_dimension . ') * (' . $default_dimension . ') * (' . $default_dimension . ')', 1 )
			->twice()
			->andReturn( $default_dimension ^ 3 );
		$product1 = Mockery::mock( 'WC_Product' );
		$product1
			->shouldReceive( 'get_weight' )
			->andReturn( false )
			->once();
		$product1
			->shouldReceive( 'get_width', 'get_length', 'get_height' )
			->andReturn( false )
			->once();
		$product1
			->shouldReceive( 'get_parent_id' )
			->times( 8 )
			->andReturn( false );
		$product1
			->shouldReceive( 'get_meta' )
			->with( 'weight_formula', true )
			->once()
			->andReturn( false );
		$product1
			->shouldReceive( 'get_meta' )
			->with( 'width_formula', true )
			->once()
			->andReturn( false );
		$product1
			->shouldReceive( 'get_meta' )
			->with( 'height_formula', true )
			->once()
			->andReturn( false );
		$product1
			->shouldReceive( 'get_meta' )
			->with( 'length_formula', true )
			->once()
			->andReturn( false );
		$product1
			->shouldReceive( 'get_category_ids' )
			->times( 4 )
			->andReturn( false );
		$product2 = Mockery::mock( 'WC_Product' );
		$product2
			->shouldReceive( 'get_weight' )
			->andReturn( false )
			->once();
		$product2
			->shouldReceive( 'get_width', 'get_length', 'get_height' )
			->andReturn( false )
			->once();
		$product2
			->shouldReceive( 'get_parent_id' )
			->times( 8 )
			->andReturn( false );
		$product2
			->shouldReceive( 'get_meta' )
			->with( 'weight_formula', true )
			->once()
			->andReturn( false );
		$product2
			->shouldReceive( 'get_meta' )
			->with( 'width_formula', true )
			->once()
			->andReturn( false );
		$product2
			->shouldReceive( 'get_meta' )
			->with( 'height_formula', true )
			->once()
			->andReturn( false );
		$product2
			->shouldReceive( 'get_meta' )
			->with( 'length_formula', true )
			->once()
			->andReturn( false );
		$product2
			->shouldReceive( 'get_category_ids' )
			->times( 4 )
			->andReturn( false );
		$shipping_cost = new Shipping_Cost( $api, $settings, $calculator );

		$shipping_cost->calculate(
			$city_id,
			[
				[
					'quantity' => 1,
					'data'     => $product1,
				],
				[
					'quantity' => 1,
					'data'     => $product2,
				],
			]
		);
	}

}
