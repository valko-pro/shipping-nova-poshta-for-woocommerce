<?php
/**
 * Database
 *
 * @package   Woo-Nova-Poshta
 * @author    Maksym Denysenko
 * @link      https://github.com/mdenisenko/woo-nova-poshta
 * @copyright Copyright (c) 2020
 * @license   GPL-2.0+
 * @wordpress-plugin
 */

namespace Nova_Poshta\Core;

/**
 * Class DB
 *
 * @package Nova_Poshta\Core
 */
class DB {

	/**
	 * Table name for cities
	 *
	 * @var string
	 */
	private $cities_table;
	/**
	 * Table name for warehouses
	 *
	 * @var string
	 */
	private $warehouses_table;

	/**
	 * DB constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->cities_table     = $wpdb->prefix . 'np_cities';
		$this->warehouses_table = $wpdb->prefix . 'np_warehouses';
	}

	/**
	 * Add hooks
	 */
	public function hooks() {
		register_activation_hook(
			plugin_dir_path( __DIR__ ) . dirname( plugin_basename( __DIR__ ) ) . '.php',
			[ $this, 'create' ]
		);
	}

	/**
	 * Create tables
	 */
	public function create() {
		global $wpdb;
		$cities_sql = 'CREATE TABLE ' . $this->cities_table . '
			(
		        city_id            VARCHAR(36)  NOT NULL UNIQUE,
		        description        VARCHAR(100) NOT NULL
	        ) ' . $wpdb->get_charset_collate();

		$warehouses_sql = 'CREATE TABLE ' . $this->warehouses_table . '
			(
		        `warehouse_id`       VARCHAR(36)  NOT NULL UNIQUE,
		        `city_id`            VARCHAR(36)  NOT NULL,
		        `description`        VARCHAR(100) NOT NULL,
		        `order`              INT(4)       UNSIGNED NOT NULL,
                  
                CONSTRAINT `city_id` FOREIGN KEY( `city_id` ) REFERENCES ' . $this->cities_table . ' ( `city_id` )
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
	        ) ' . $wpdb->get_charset_collate();

		$this->maybe_create_table( $this->cities_table, $cities_sql );
		$this->maybe_create_table( $this->warehouses_table, $warehouses_sql );
	}

	/**
	 * Maybe create table
	 *
	 * @param string $table_name Table name.
	 * @param string $create_ddl Table create SQL.
	 */
	private function maybe_create_table( string $table_name, string $create_ddl ) {
		if ( ! function_exists( 'maybe_create_table' ) ) {
			// @codeCoverageIgnoreStart
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			// @codeCoverageIgnoreEnd
		}
		maybe_create_table( $table_name, $create_ddl );
	}

	/**
	 * List of the cities
	 *
	 * @param string $search Search string.
	 * @param int    $limit  Limit cities in result.
	 *
	 * @return array
	 */
	public function cities( string $search, int $limit ): array {
		global $wpdb;
		$sql = 'SELECT * FROM ' . $this->cities_table;
		if ( $search ) {
			$sql .= $wpdb->remove_placeholder_escape(
				$wpdb->prepare( ' WHERE description LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' )
			);
		}
		$sql .= ' ORDER BY LENGTH(`description`), `description`';
		if ( $limit ) {
			$sql .= $wpdb->prepare( ' LIMIT %d', $limit );
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$cities = $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		return wp_list_pluck( $cities, 'description', 'city_id' );
	}

	/**
	 * Update cities
	 *
	 * @param array $cities List of the cities.
	 */
	public function update_cities( array $cities ): void {
		global $wpdb;
		$sql = 'INSERT INTO ' . $this->cities_table . ' (`city_id`, `description`, `area`) VALUES ';
		foreach ( $cities as $city ) {
			if ( ! isset( $city['DescriptionRu'] ) ) {
				continue;
			}
			$sql .= $wpdb->prepare(
				'(%s, %s, %s),',
				$city['Ref'],
				$city['DescriptionRu'],
				$city['Area']
			);
		}
		$sql = rtrim( $sql, ',' );

		$sql .= ' ON DUPLICATE KEY UPDATE `description`=VALUES(`description`), `area`=VALUES(`area`)';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $sql );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get city name
	 *
	 * @param string $city_id City ID.
	 *
	 * @return string
	 */
	public function city( string $city_id ): string {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_var(
			$wpdb->prepare( 'SELECT `description` FROM ' . $this->cities_table . ' WHERE city_id = %s', $city_id )
		) ?: '';
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get city area.
	 *
	 * @param string $city_id City ID.
	 *
	 * @return string
	 */
	public function area( string $city_id ): string {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_var(
			$wpdb->prepare( 'SELECT `area` FROM ' . $this->cities_table . ' WHERE city_id = %s', $city_id )
		) ?: '';
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get list of the warehouses.
	 *
	 * @param string $city_id City ID.
	 *
	 * @return array
	 */
	public function warehouses( string $city_id ): array {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$sql = $wpdb->prepare(
			'SELECT warehouse_id, description FROM ' . $this->warehouses_table .
			' WHERE city_id = %s  ORDER BY LENGTH(`order`), `order`',
			$city_id
		);

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		$warehouses = $wpdb->get_results( $sql );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		return wp_list_pluck( $warehouses, 'description', 'warehouse_id' );
	}

	/**
	 * Update warehouses
	 *
	 * @param array $warehouses List of the warehouses.
	 */
	public function update_warehouses( array $warehouses ): void {
		global $wpdb;
		$sql = 'INSERT INTO ' . $this->warehouses_table . ' (`warehouse_id`,`city_id`, `description`, `order`) VALUES ';
		foreach ( $warehouses as $key => $warehouse ) {
			$sql .= $wpdb->prepare(
				'(%s, %s, %s, %d),',
				$warehouse['Ref'],
				$warehouse['CityRef'],
				$warehouse['DescriptionRu'],
				$key
			);
		}
		$sql = rtrim( $sql, ',' );

		$sql .= ' ON DUPLICATE KEY UPDATE `city_id`=VALUES(`city_id`), `description`=VALUES(`description`), `order`=VALUES(`order`)';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $sql );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get warehouse description
	 *
	 * @param string $warehouse_id Warehouse ID.
	 *
	 * @return string
	 */
	public function warehouse( string $warehouse_id ): string {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_var(
			$wpdb->prepare(
				'SELECT `description` FROM ' . $this->warehouses_table . ' WHERE warehouse_id = %s',
				$warehouse_id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}

}
