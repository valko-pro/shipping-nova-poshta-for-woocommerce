<?php
/**
 * Admin user tests
 *
 * @package   Woo-Nova-Poshta
 */

namespace Nova_Poshta\Admin;

use Mockery;
use Nova_Poshta\Core\Main;
use Nova_Poshta\Tests\Test_Case;
use tad\FunctionMocker\FunctionMocker;
use WP_Mock;

/**
 * Class Test_Notice
 *
 * @package Nova_Poshta\Admin
 */
class Test_User extends Test_Case {

	/**
	 * Test fields
	 */
	public function test_fields() {
		$city_id    = 'city-id';
		$city       = 'City';
		$warehouses = [
			'warehouse-id-1' => 'Warehouse',
			'warehouse-id-2' => 'Warehouse 2',
		];
		$api        = Mockery::mock( 'Nova_Poshta\Core\API' );
		$api
			->shouldReceive( 'cities' )
			->once()
			->andReturn( [ $city_id => $city ] );
		$api
			->shouldReceive( 'warehouses' )
			->once()
			->withArgs( [ $city_id ] )
			->andReturn( $warehouses );
		WP_Mock::userFunction(
			'wp_nonce_field',
			[
				'args'  => [
					Main::PLUGIN_SLUG . '-shipping',
					'woo_nova_poshta_nonce',
					false,
				],
				'times' => 1,
			]
		);

		WP_Mock::userFunction( 'woocommerce_form_field', [ 'times' => 2 ] );
		$user = new User( $api );

		$user->fields();
	}

	/**
	 * Test don't save
	 */
	public function test_dont_save_on_checkout_for_not_auth_users() {
		WP_Mock::userFunction( 'get_current_user_id' )->
		once();

		$api  = Mockery::mock( 'Nova_Poshta\Core\API' );
		$user = new User( $api );

		$user->checkout();
	}

	/**
	 * Test on not valid nonce
	 */
	public function test_not_valid_nonce_on_checkout_for_not_valid_nonce() {
		WP_Mock::userFunction( 'get_current_user_id' )->
		once()->
		andReturn( 1 );

		$api  = Mockery::mock( 'Nova_Poshta\Core\API' );
		$user = new User( $api );

		$user->checkout();
	}

	/**
	 * Test with empty city or warehouse in request
	 */
	public function test_not_valid_checkout_with_empty_city_or_warehouse() {
		WP_Mock::userFunction( 'get_current_user_id' )->
		once()->
		andReturn( 1 );
		WP_Mock::userFunction( 'wp_verify_nonce' )->
		once()->
		andReturn( true );

		$api  = Mockery::mock( 'Nova_Poshta\Core\API' );
		$user = new User( $api );

		$user->checkout();
	}

	/**
	 * Test valid checkout update user meta fields
	 */
	public function test_valid_checkout() {
		global $city_id, $warehouse_id;
		$user_id      = 1;
		$city_id      = 2;
		$warehouse_id = 3;
		WP_Mock::userFunction( 'get_current_user_id' )->
		once()->
		andReturn( $user_id );
		WP_Mock::userFunction( 'wp_verify_nonce' )->
		once()->
		andReturn( true );

		$filter_input = FunctionMocker::replace(
			'filter_input',
			function () {
				global $city_id, $warehouse_id;
				static $i = 0;

				$answers = [ 'nonce', $city_id, $warehouse_id ];

				return $answers[ $i ++ ];
			}
		);
		WP_Mock::userFunction( 'update_user_meta' )->
		once()->
		withArgs(
			[
				$user_id,
				'woo_nova_poshta_city',
				$city_id,
			]
		);
		WP_Mock::userFunction( 'update_user_meta' )->
		once()->
		withArgs(
			[
				$user_id,
				'woo_nova_poshta_warehouse',
				$warehouse_id,
			]
		);

		$api  = Mockery::mock( 'Nova_Poshta\Core\API' );
		$user = new User( $api );

		$user->checkout();

		$filter_input->wasCalledWithOnce( [ INPUT_POST, 'woo_nova_poshta_city', FILTER_SANITIZE_STRING ] );
		$filter_input->wasCalledWithOnce( [ INPUT_POST, 'woo_nova_poshta_warehouse', FILTER_SANITIZE_STRING ] );
	}

	/**
	 * Test city filter for not auth user
	 */
	public function test_city_id_not_auth_user() {
		$city_id = 'city-id';

		$api  = Mockery::mock( 'Nova_Poshta\Core\API' );
		$user = new User( $api );

		$this->assertSame( $city_id, $user->city( $city_id ) );
	}

	/**
	 * Test city filter for auth user
	 */
	public function test_city_id_auth_user() {
		$user_id      = 1;
		$city_id      = 'city-id';
		$user_city_id = 'user-city_id';
		WP_Mock::userFunction( 'get_current_user_id' )->
		once()->
		andReturn( $user_id );
		WP_Mock::userFunction( 'get_user_meta' )->
		withArgs( [ $user_id, 'woo_nova_poshta_city', true ] )->
		once()->
		andReturn( $user_city_id );

		$api  = Mockery::mock( 'Nova_Poshta\Core\API' );
		$user = new User( $api );

		$this->assertSame( $user_city_id, $user->city( $city_id ) );
	}

	/**
	 * Test warehouse filter for not auth user
	 */
	public function test_warehouse_id_not_auth_user() {
		$warehouse_id = 'warehouse-id';

		$api  = Mockery::mock( 'Nova_Poshta\Core\API' );
		$user = new User( $api );

		$this->assertSame( $warehouse_id, $user->warehouse( $warehouse_id ) );
	}

	/**
	 * Test warehouse filter for auth user
	 */
	public function test_warehouse_id_auth_user() {
		$user_id           = 1;
		$warehouse_id      = 'warehouse-id';
		$user_warehouse_id = 'warehouse-city_id';
		WP_Mock::userFunction( 'get_current_user_id' )->
		once()->
		andReturn( $user_id );
		WP_Mock::userFunction( 'get_user_meta' )->
		withArgs( [ $user_id, 'woo_nova_poshta_warehouse', true ] )->
		once()->
		andReturn( $user_warehouse_id );

		$api  = Mockery::mock( 'Nova_Poshta\Core\API' );
		$user = new User( $api );

		$this->assertSame( $user_warehouse_id, $user->warehouse( $warehouse_id ) );
	}

}