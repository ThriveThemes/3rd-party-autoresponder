<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package 3rd-party-autoresponder
 */

namespace Thrive\ThirdPartyAutoResponderDemo\AutoResponders\CleverReach;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Utils
 * @package Thrive\ThirdPartyAutoResponderDemo\AutoResponders\CleverReach
 */
class Utils {
	/**
	 * @param $data
	 *
	 * @return bool
	 */
	public static function is_base64_encoded( $data ) {
		return $data === base64_encode( base64_decode( $data, true ) );
	}

	/**
	 * @param string $data
	 *
	 * @return mixed
	 */
	public static function safe_unserialize( $data ) {
		if ( ! is_serialized( $data ) ) {
			return $data;
		}

		if ( version_compare( '7.0', PHP_VERSION, '<=' ) ) {
			return unserialize( $data, array( 'allowed_classes' => false ) );
		}

		/* on php <= 5.6, we need to check if the serialized string contains an object instance */
		if ( ! is_string( $data ) ) {
			return false;
		}

		if ( preg_match( '#(^|;)o:\d+:"[a-z0-9\\\_]+":\d+:#i', $data, $m ) ) {
			return false;
		}

		return unserialize( $data );
	}

	/**
	 * @param $message
	 */
	public static function print_message( $message ) {
		add_action( 'thrive_third_party_autoresponder_page_template', function () use ( $message ) {
			echo '<br>' . $message;
		}, 11 );
	}


	/**
	 * In order to test this in a local environment, please define THIRD_PARTY_LOCAL_ENVIRONMENT in your wp-config.php.
	 * @return bool
	 */
	public static function is_local_environment() {
		return ! is_ssl() &&
		       defined( 'THIRD_PARTY_LOCAL_ENVIRONMENT' ) && THIRD_PARTY_LOCAL_ENVIRONMENT;
	}

	/**
	 * Adapts curl to ensure that it works when being tested locally.
	 * Only works if THIRD_PARTY_LOCAL_ENVIRONMENT is set and is_ssl() is false.
	 * Don't call this on live sites :)
	 *
	 * @param $curl
	 *
	 * @return mixed
	 */
	public static function curl_local_setup( $curl ) {
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, false );

		return $curl;
	}

	/**
	 * @return string
	 */
	public static function get_current_screen() {
		return empty( $_GET['page'] ) ? '' : $_GET['page'];
	}

	/**
	 * @param $message
	 */
	public static function log_error( $message ) {
		$timestamp = date( 'm/d/Y h:i:s a' );

		file_put_contents( __DIR__ . '/error_log.txt', $timestamp . ': ' . $message . PHP_EOL, FILE_APPEND );
	}
}
