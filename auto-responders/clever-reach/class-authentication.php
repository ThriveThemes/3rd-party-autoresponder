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

class Authentication {
	public static $default_credentials = [ 'client_id' => '', 'client_secret' => '', 'access_token' => '' ];

	const OPTION_KEY = 'thrive_clever_reach_settings';

	/**
	 * @param $post_data
	 */
	public static function connect( $post_data ) {
		$new_credentials = [
			'client_id'     => empty( $post_data['client_id'] ) ? '' : sanitize_text_field( $post_data['client_id'] ),
			'client_secret' => empty( $post_data['client_secret'] ) ? '' : sanitize_text_field( $post_data['client_secret'] ),
		];

		$new_access_token = static::generate_access_token( $new_credentials );

		if ( ! empty( $new_access_token ) ) {
			static::update_credentials( array_merge( $new_credentials, [ 'access_token' => $new_access_token ] ) );
		}
	}

	public static function disconnect() {
		static::update_credentials( static::$default_credentials );
	}

	public static function read_credentials() {
		return get_option( static::OPTION_KEY, static::$default_credentials );
	}

	/**
	 * @param $new_credentials
	 */
	public static function update_credentials( $new_credentials ) {
		update_option( static::OPTION_KEY, $new_credentials );
	}

	public static function generate_access_token( $credentials ) {
		$access_token = '';

		if ( empty( $credentials['client_id'] ) || empty( $credentials['client_secret'] ) ) {
			Utils::print_message( 'Token generation failed - empty "ID" or "secret" field' );

			return $access_token;
		}

		$token_url = 'https://rest.cleverreach.com/oauth/token.php';

		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $token_url );
		curl_setopt( $curl, CURLOPT_USERPWD, $credentials['client_id'] . ":" . $credentials['client_secret'] );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, array( 'grant_type' => 'client_credentials' ) );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );

		/* workaround for curl on localhost */
		if ( Utils::is_local_environment() ) {
			$curl = Utils::curl_local_setup( $curl );
		}

		$result = curl_exec( $curl );
		curl_close( $curl );

		$result = json_decode( $result );

		if ( $result && property_exists( $result, 'access_token' ) ) {
			$access_token = $result->access_token;
		} else {
			Utils::print_message( 'Token generation failed - error message: ' . empty( $result ) ? '' : $result->error );
		}

		return $access_token;
	}
}
