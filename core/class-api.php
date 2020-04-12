<?php
/**
 * API for Nova Poshta
 *
 * @package   Woo-Nova-Poshta
 * @author    Maksym Denysenko
 * @link      https://github.com/mdenisenko/woo-nova-poshta
 * @copyright Copyright (c) 2020
 * @license   GPL-2.0+
 * @wordpress-plugin
 */

namespace Nova_Poshta\Core;

use DateTime;
use Exception;
use LisDev\Delivery\NovaPoshtaApi2;

/**
 * Class API
 *
 * @package Nova_Poshta\Core
 */
class API {

	/**
	 * Plugin settings
	 *
	 * @var array
	 */
	private $settings;
	/**
	 * API for Nova Poshta
	 *
	 * @var NovaPoshtaApi2
	 */
	private $np;
	/**
	 * Database
	 *
	 * @var DB
	 */
	private $db;

	/**
	 * API constructor.
	 *
	 * @param DB       $db       Database.
	 * @param Settings $settings Plugin settings.
	 */
	public function __construct( DB $db, Settings $settings ) {
		$this->settings = $settings;
		$this->np       = new NovaPoshtaApi2( '' );
		$this->db       = $db;
	}

	/**
	 * List of the cities
	 *
	 * @param string $search Search string.
	 * @param int    $limit  Limit cities in result.
	 *
	 * @return array
	 */
	public function cities( string $search = '', int $limit = 10 ): array {
		if ( ! get_transient( Main::PLUGIN_SLUG . '-cities' ) ) {
			$request = $this->np->getCities( 0 );
			if ( $request['success'] ) {
				$this->db->update_cities( $request['data'] );
				set_transient( Main::PLUGIN_SLUG . '-cities', 1, DAY_IN_SECONDS );
			}
			unset( $request );
		}

		return $this->db->cities( $search, $limit );
	}

	/**
	 * City name
	 *
	 * @param string $city_id City ID.
	 *
	 * @return string
	 */
	public function city( string $city_id ): string {
		return $this->db->city( $city_id );
	}

	/**
	 * City area
	 *
	 * @param string $city_id City ID.
	 *
	 * @return string
	 */
	public function area( string $city_id ): string {
		return $this->db->area( $city_id );
	}

	/**
	 * Warehouse full description
	 *
	 * @param string $warehouse_id Warehouse ID.
	 *
	 * @return string
	 */
	public function warehouse( string $warehouse_id ): string {
		return $this->db->warehouse( $warehouse_id );
	}

	/**
	 * List of warehouses
	 *
	 * @param string $city_id Warehouse ID.
	 *
	 * @return array
	 */
	public function warehouses( string $city_id ): array {
		if ( ! get_transient( Main::PLUGIN_SLUG . '-warehouse-' . $city_id ) ) {
			$request = $this->np->getWarehouses( $city_id );
			if ( $request['success'] ) {
				$this->db->update_warehouses( $request['data'] );
				set_transient( Main::PLUGIN_SLUG . '-warehouse-' . $city_id, 1, DAY_IN_SECONDS );
			}
			unset( $request );
		}

		return $this->db->warehouses( $city_id );
	}

	/**
	 * Create internet document
	 *
	 * @param string $first_name   Customer first name.
	 * @param string $last_name    Customer last name.
	 * @param string $phone        Customer phone.
	 * @param string $city_id      Customer city ID.
	 * @param string $warehouse_id Customer warehouse ID.
	 * @param float  $price        Order price.
	 * @param int    $count        Order items count.
	 * @param float  $redelivery   Cash on delivery price.
	 *
	 * @return string
	 * @throws Exception Invalid DateTime.
	 */
	public function internet_document(
		string $first_name, string $last_name, string $phone,
		string $city_id, string $warehouse_id, float $price,
		int $count, float $redelivery = 0
	): string {
		if ( empty( $this->settings->api_key() ) ) {
			return '';
		}
		$this->np->setKey( $this->settings->api_key() );
		$admin_phone        = $this->settings->phone() ?? '';
		$admin_city_id      = $this->settings->city_id() ?? '';
		$admin_warehouse_id = $this->settings->warehouse_id() ?? '';
		if ( ! $admin_phone || ! $admin_city_id || ! $admin_warehouse_id ) {
			return '';
		}
		$sender    = [
			'ContactSender' => $admin_phone,
			'CitySender'    => $admin_city_id,
			'SenderAddress' => $admin_warehouse_id,
		];
		$recipient = [
			'FirstName'        => $first_name,
			'LastName'         => $last_name,
			'Phone'            => preg_replace( '/[^0-9]/', '', $phone ),
			'Region'           => $this->area( $city_id ),
			'City'             => $city_id,
			'CityRecipient'    => $city_id,
			'RecipientAddress' => $warehouse_id,
		];
		$date      = new DateTime( '', new \DateTimeZone( 'Europe/Kiev' ) );
		$info      = [
			'ServiceType'   => 'WarehouseWarehouse',
			'PaymentMethod' => 'Cash',
			'PayerType'     => 'Recipient',
			'Cost'          => $price,
			'SeatsAmount'   => '1',
			'Description'   => 'Взуття', // TODO: Field with deliver.
			'Weight'        => ( $count * .5 ) - .01, // TODO: Calculate weight.
			'DateTime'      => $date->format( 'd.m.Y' ),
		];
		if ( $redelivery ) {
			$info['BackwardDeliveryData'] = [
				[
					'PayerType'        => 'Recipient',
					'CargoType'        => 'Money',
					'RedeliveryString' => $redelivery,
				],
			];
		}
		$internet_document = $this->np->newInternetDocument( $sender, $recipient, $info );
//		var_dump( $internet_document );
//		die();
		// TODO: Return WP_Error.

		return $internet_document['success'] ? $internet_document['data'][0]['IntDocNumber'] : '';
	}

	/**
	 * Validate api key
	 *
	 * @param string $api_key API key.
	 *
	 * @return bool
	 */
	public function validate( string $api_key ): bool {
		$this->np->setKey( $api_key );
		$sender = $this->np->getCounterparties( 'Sender', 1 );

		return ! empty( $sender['success'] );
	}

}
