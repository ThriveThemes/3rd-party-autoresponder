<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package 3rd-party-autoresponder
 */

namespace Thrive\ThirdPartyAutoResponderDemo;

use Thrive\ThirdPartyAutoResponderDemo\AutoResponders\Autoresponder;

defined( 'THRIVE_THIRD_PARTY_PLUGIN_URL' ) || define( 'THRIVE_THIRD_PARTY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Class Main
 * @package Thrive\ThirdPartyAutoresponderDemo
 */
class Main {
	public static $registered_autoresponders = [];

	public static function init() {
		static::includes();

		static::register_autoresponder( 'clever-reach', 'Thrive\ThirdPartyAutoResponderDemo\AutoResponders\CleverReach\Main' );

		static::add_hooks();
	}

	public static function add_hooks() {
		if ( empty( static::$registered_autoresponders ) ) {
			return;
		}

		add_filter( 'tvd_api_available_connections', [ __CLASS__, 'add_api_to_connection_list' ], 10, 3 );
		add_filter( 'tvd_third_party_autoresponders', [ __CLASS__, 'add_api_to_thrive_dashboard_list' ], 10, 2 );
	}

	public static function includes() {
		require_once __DIR__ . '/autoresponders/class-autoresponder.php';
	}

	/**
	 * @param $key
	 * @param $class
	 */
	public static function register_autoresponder( $key, $class ) {
		require_once __DIR__ . '/autoresponders/' . $key . '/class-main.php';

		static::$registered_autoresponders[ $key ] = new $class();
	}

	/**
	 * Hook that adds the autoresponder to the list of available APIs that gets retrieved by Thrive Architect and Thrive Automator.
	 *
	 * @param $autoresponders
	 * @param $only_connected
	 * @param $include_all - a flag that is set to true when all the connections ( including third party APIs ) must be shown
	 *
	 * @return mixed
	 */
	public static function add_api_to_connection_list( $autoresponders, $only_connected, $api_filter ) {
		$include_3rd_party_apis = ! empty( $api_filter['include_3rd_party_apis'] );

		if ( ( $include_3rd_party_apis || $only_connected ) && static::should_include_autoresponders( $api_filter ) ) {
			foreach ( static::$registered_autoresponders as $autoresponder_key => $autoresponder_instance ) {
				/* @var Autoresponder $autoresponder_data */
				if ( $include_3rd_party_apis || $autoresponder_instance->is_connected() ) {
					$autoresponders[ $autoresponder_key ] = $autoresponder_instance;
				}
			}
		}

		return $autoresponders;
	}

	/**
	 * Hook that adds the card of this API to the Thrive Dashboard API Connection page.
	 *
	 * @param array $autoresponders
	 * @param bool  $localize
	 *
	 * @return mixed
	 */
	public static function add_api_to_thrive_dashboard_list( $autoresponders, $localize ) {
		foreach ( static::$registered_autoresponders as $key => $autoresponder_instance ) {
			if ( $localize ) {
				$autoresponders[] = $autoresponder_instance->localize();
			} else {
				$autoresponders[ $key ] = $autoresponder_instance;
			}
		}

		return $autoresponders;
	}

	/**
	 * @param array $api_filter
	 *
	 * @return bool
	 */
	public static function should_include_autoresponders( $api_filter ) {
		$type = 'autoresponder';

		if ( empty( $api_filter['include_types'] ) ) {
			$should_include_api = ! in_array( $type, $api_filter['exclude_types'], true );
		} else {
			$should_include_api = in_array( $type, $api_filter['include_types'], true );
		}

		return $should_include_api;
	}
}
