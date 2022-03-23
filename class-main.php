<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package 3rd-party-autoresponder
 */

namespace Thrive\ThirdPartyAutoResponderDemo;

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

		add_filter( 'tvd_api_available_connections', [ __CLASS__, 'add_api_to_connection_list' ], 10, 2 );
		add_filter( 'tvd_third_party_autoresponders', [ __CLASS__, 'add_api_to_thrive_dashboard_list' ] );
	}

	public static function includes() {
		require_once __DIR__ . '/auto-responders/class-autoresponder.php';
	}

	/**
	 * @param $key
	 * @param $class
	 */
	public static function register_autoresponder( $key, $class ) {
		require_once __DIR__ . '/auto-responders/' . $key . '/class-main.php';

		static::$registered_autoresponders[ $key ] = new $class();
	}

	/**
	 * Hook that adds the autoresponder to the list of available APIs that gets retrieved by Thrive Architect and Thrive Automator.
	 *
	 * @param $autoresponders
	 * @param $only_connected
	 *
	 * @return mixed
	 */
	public static function add_api_to_connection_list( $autoresponders, $only_connected ) {
		if ( $only_connected ) {
			foreach ( static::$registered_autoresponders as $autoresponder_key => $instance ) {
				if ( $instance->is_connected() ) {
					$autoresponders[ $autoresponder_key ] = $instance;
				}
			}
		}

		return $autoresponders;
	}

	/**
	 * Hook that adds the card of this API to the Thrive Dashboard API Connection page.
	 *
	 * @param $autoresponders
	 *
	 * @return mixed
	 */
	public static function add_api_to_thrive_dashboard_list( $autoresponders ) {
		foreach ( static::$registered_autoresponders as $autoresponder_instance ) {
			$autoresponders[] = $autoresponder_instance->localize();
		}

		return $autoresponders;
	}
}
