<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package 3rd-party-autoresponder
 */

namespace Thrive\ThirdPartyAutoResponderDemo\AutoResponders\CleverReach;

/**
 * Class API
 * Taken from https://rest.cleverreach.com/howto/examples/rest_client.zip and modified ( deleted unused stuff )
 *
 * @package Thrive\ThirdPartyAutoResponderDemo\AutoResponders\CleverReach
 */
class API {
	public  $header   = false;
	public  $error    = false;
	public  $rest_url = 'https://rest.cleverreach.com/v3';
	private $access_token;

	/**
	 * API constructor
	 *
	 * @param $access_token
	 */
	public function __construct( $access_token ) {
		$this->access_token = $access_token;
	}

	public function get_subscriber_by_email( $list_id, $email ) {
		return $this->get( '/groups/' . $list_id . '/receivers/' . $email );
	}

	/**
	 * @param $list_id
	 * @param $args
	 * @param $mode - post or put
	 *
	 * @return mixed
	 */
	public function add_subscriber( $list_id, $args, $mode = 'post' ) {
		return $this->post( '/groups/' . $list_id . '/receivers', $args, $mode );
	}

	public function get_lists() {
		return $this->get( '/groups' );
	}

	/**
	 * @param      $path
	 * @param bool $data
	 *
	 * @return mixed|null
	 * @throws \Exception
	 */
	public function get( $path, $data = false ) {
		$url = sprintf( "%s?%s", $this->rest_url . $path, ( $data ? http_build_query( $data ) : "" ) );

		$curl = curl_init( $url );
		$this->setupCurl( $curl );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		$curl_response = curl_exec( $curl );
		$headers       = curl_getinfo( $curl );
		curl_close( $curl );

		return $this->returnResult( $curl_response, $headers );
	}

	/**
	 * @param $path
	 * @param $data
	 * @param $mode
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function post( $path, $data, $mode = 'post' ) {
		$curl_post_data = $data;

		/* when updating, we have to include the email in the path in order to specify which receiver we're updating */
		if ( $mode === 'put' ) {
			$path .= '/' . $data['email'];
		}

		$curl = curl_init( $this->rest_url . $path );
		$this->setupCurl( $curl );

		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );

		if ( $mode === 'post' ) {
			curl_setopt( $curl, CURLOPT_POST, true );
		} else {
			curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'PUT' );
		}

		curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode( $curl_post_data ) );

		$curl_response = curl_exec( $curl );
		$headers       = curl_getinfo( $curl );
		curl_close( $curl );

		return $this->returnResult( $curl_response, $headers );
	}

	/**
	 * @param $curl
	 */
	private function setupCurl( &$curl ) {
		$header = array();

		$header['content'] = 'Content-Type: application/json';
		$header['token']   = 'Authorization: Bearer ' . $this->access_token;

		/* workaround for curl on localhost */
		if ( Utils::is_local_environment() ) {
			$curl = Utils::curl_local_setup( $curl );
		}

		curl_setopt( $curl, CURLOPT_HTTPHEADER, $header );
	}

	/**
	 * @param      $in
	 * @param bool $header
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	private function returnResult( $in, $header = false ) {
		$this->header = $header;

		if ( isset( $header["http_code"] ) ) {
			if ( $header["http_code"] < 200 || $header["http_code"] >= 300 ) {
				$this->error = $in;
				$message     = var_export( $in, true );
				if ( $tmp = json_decode( $in ) ) {
					if ( isset( $tmp->error->message ) ) {
						$message = $tmp->error->message;
					}
				}
				throw new \Exception( '' . $header["http_code"] . ';' . $message );

				$in = null;
			}
		}

		return json_decode( $in );
	}
}
