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
 * Class Main
 * @package Thrive\ThirdPartyAutoResponderDemo\AutoResponders\CleverReach
 */
class Main extends \Thrive\ThirdPartyAutoResponderDemo\AutoResponders\Autoresponder {
	private $access_token;
	private $api_instance;

	const API_KEY = 'clever-reach';

	/**
	 * @return string
	 */
	public function get_title() {
		return 'CleverReach';
	}

	public function get_key() {
		return static::API_KEY;
	}

	public function __construct() {
		static::includes();

		Hooks::init();

		$credentials        = Authentication::read_credentials();
		$this->access_token = empty( $credentials['access_token'] ) ? '' : $credentials['access_token'];

		if ( ! empty( $_POST ) && current_user_can( 'manage_options' ) && static::is_plugin_page() ) {
			/* this handles connect / disconnect / test connection on the plugin page */
			$this->on_form_action( $_POST );
		}
	}

	public static function is_plugin_page() {
		return Utils::get_current_screen() === 'thrive_third_party_autoresponder_section';
	}

	public static function includes() {
		require_once __DIR__ . '/class-hooks.php';
		require_once __DIR__ . '/class-authentication.php';
		require_once __DIR__ . '/class-api.php';
		require_once __DIR__ . '/class-custom-fields.php';
		require_once __DIR__ . '/class-utils.php';
	}

	/**
	 * @return API
	 */
	public function get_api_instance() {
		if ( empty( $this->api_instance ) ) {
			try {
				$this->api_instance = new API( $this->access_token );
			} catch ( \Exception $e ) {
				Utils::log_error( 'Error while instantiating the API! Error message: ' . $e->getMessage() );
			}
		}

		return $this->api_instance;
	}

	/**
	 * When the 'Connect' button is pressed, save the new credentials and re-generate the access token.
	 * If 'Disconnect' is pressed, clear the credentials and the token.
	 * If 'Test connection' is pressed, run a test API call and print the result.
	 *
	 * @param array $post_data
	 */
	public function on_form_action( $post_data ) {
		switch ( $post_data['action'] ) {
			case 'connect':
				Authentication::connect( $post_data );
				break;
			case 'disconnect':
				Authentication::disconnect();
				break;
			case 'test connection':
				Utils::print_message( 'The connection is ' . ( $this->test_connection() ? '' : 'not ' ) . 'working!' );
				break;
			default:
				break;
		}
	}

	/**
	 * @param string $list_identifier - the ID of the mailing list
	 * @param array  $data            - an array of what we want to send as subscriber data
	 * @param bool   $is_update
	 *
	 * @return boolean
	 */
	public function add_subscriber( $list_identifier, $data, $is_update = false ) {
		if ( ! $this->is_connected() ) {
			return false;
		}

		$success = false;

		try {
			$api  = $this->get_api_instance();
			$data = $this->process_subscriber_data( $data );
			$api->add_subscriber( $list_identifier, $data, $is_update ? 'put' : 'post' );
			$success = true;
		} catch ( \Exception $e ) {
			Utils::log_error( 'Error while adding/updating the subscriber! Error message: ' . $e->getMessage() );
		}

		return $success;
	}

	/**
	 * @param array $data
	 *
	 * @return mixed
	 */
	public function process_subscriber_data( $data ) {
		$tag_key = $this->get_tags_key();

		/* include tags if they are present in the data */
		if ( isset( $data[ $tag_key ] ) ) {
			$tags = explode( ',', $data[ $tag_key ] );
			/* there are some cases where the tags string also has spaces after the ',', so we apply trim() on each element in order to uniformize them */
			$tags         = array_map( 'trim', $tags );
			$data['tags'] = $tags;
		}

		if ( ! empty( $data['tve_mapping'] ) ) {
			/**
			 * When the request is coming from a Thrive Architect form, if it contains the 'tve_mapping' field it means that there are encoded custom fields inside.
			 * In that case, the custom field helper class parses the data and returns the custom fields that must be added here.
			 */
			$custom_fields = $this->get_custom_field_instance()->parse_custom_fields( $data );

			if ( ! empty( $custom_fields ) ) {
				$data['global_attributes'] = $custom_fields;
			}
		} else if ( ! empty( $data['automator_custom_fields'] ) ) {
			/**
			 * If the request contains custom fields data from automator, we can add it directly since it's sent in the proper format.
			 * The contents are processed beforehand inside the 'build_automation_custom_fields' function.
			 */
			$data['global_attributes'] = $data['automator_custom_fields'];
			unset( $data['automator_custom_fields'] );
		}

		if ( $this->has_forms() ) {
			$form_key = $this->get_forms_key();

			if ( ! empty( $data[ $form_key ] ) ) {
				if ( empty( $data['global_attributes'] ) ) {
					$data['global_attributes'] = [];
				}

				$data['global_attributes']['form_id'] = sanitize_text_field( $data[ $form_key ] );
				unset( $data[ $form_key ] );
			}
		}

		if ( $this->has_optin() ) {
			$opt_in_key = $this->get_optin_key();

			/**
			 * If the opt-in is set to 'double', we send the 'registered' field without the 'activated' field being set.
			 * This lets the API know that we added a user that is not activated yet. ( if we don't set the fields at all, the user is activated by default )
			 * For more details, see the 'Implementation Notes' from this section of the Rest Explorer: https://rest.cleverreach.com/explorer/v3/#!/groups-v3/create__post
			 */
			if ( ! empty( $data[ $opt_in_key ] ) && $data[ $opt_in_key ] === 'd' ) {
				$data['registered'] = time();
			}
		}

		return $data;
	}

	/**
	 * @return bool
	 */
	public function is_connected() {
		return ! empty( $this->access_token );
	}

	/**
	 * @return bool
	 */
	public function test_connection() {
		if ( ! $this->is_connected() ) {
			return false;
		}

		$is_connected = true;

		try {
			$lists = $this->get_lists( true );

			/* false is only returned if the request fails */
			if ( $lists === false ) {
				$is_connected = false;
			}
		} catch ( \Exception $e ) {
			Utils::print_message( $e->getMessage() );

			$is_connected = false;
		}

		return $is_connected;
	}

	/**
	 * @param bool $is_testing_connection
	 *
	 * @return array|mixed|null
	 * @throws \Exception
	 */
	public function get_lists( $is_testing_connection = false ) {
		if ( ! $this->is_connected() ) {
			return [];
		}

		$lists = [];

		try {
			$api   = $this->get_api_instance();
			$lists = $api->get_lists();
		} catch ( \Exception $e ) {
			$message = $e->getMessage();

			Utils::log_error( 'Error while fetching the mailing lists! Error message: ' . $message );

			if ( $is_testing_connection ) {
				Utils::print_message( $message );
				$lists = false;
			}
		}

		return $lists;
	}

	/**
	 * Since custom fields are enabled, this is set to true.
	 *
	 * @return bool
	 */
	public function has_custom_fields() {
		return true;
	}

	/**
	 * Since the implementation covers the clever-reach global custom fields, this function returns all of them.
	 *
	 * @return array
	 */
	public function get_custom_fields_by_list() {
		return $this->get_api_custom_fields();
	}

	/**
	 * Returns all the types of custom field mappings
	 *
	 * @return \string[][]
	 */
	public function get_custom_fields() {
		return Custom_Fields::get_custom_field_types();
	}

	/**
	 * Retrieves all the used custom fields. Currently it returns all the inter-group (global) ones.
	 *
	 * @param array $params  which may contain `list_id`
	 * @param bool  $force
	 * @param bool  $get_all whether to get lists with their custom fields
	 *
	 * @return array
	 */
	public function get_api_custom_fields( $params = [], $force = false, $get_all = true ) {
		$custom_fields = [];

		try {
			$custom_fields = $this->get_custom_field_instance()->get_custom_fields();
		} catch ( \Exception $e ) {
			Utils::log_error( 'Error while fetching the custom fields! Error message: ' . $e->getMessage() );
		}

		return $custom_fields;
	}

	/**
	 * Builds custom fields mapping for automations.
	 * Called from Thrive Automator when the custom fields are processed.
	 *
	 * @param $automation_data
	 *
	 * @return array
	 */
	public function build_automation_custom_fields( $automation_data ) {
		return $this->get_custom_field_instance()->build_automation_fields( $automation_data );
	}

	/**
	 * @return Custom_Fields
	 */
	public function get_custom_field_instance() {
		$api = $this->get_api_instance();

		return new Custom_Fields( $api, $this->get_key() );
	}

	/**
	 * Enables the tag feature inside Thrive Architect & Automator.
	 * @return bool
	 */
	public function has_tags() {
		return true;
	}

	/**
	 * API-unique tag identifier.
	 *
	 * @return string
	 */
	public function get_tags_key() {
		return $this->get_key() . '_tags';
	}

	/**
	 * Enables the mailing list, forms, opt-in type and tag features inside Thrive Automator.
	 * Check the parent method for an explanation of the config structure.
	 *
	 * @return \string[][]
	 */
	public function get_automator_add_autoresponder_mapping_fields() {
		return [ 'autoresponder' => [ 'mailing_list' => [ 'form_list' ], 'api_fields' => [], 'optin' => [], 'tag_input' => [] ] ];
	}

	/**
	 * Get field mappings specific to an API with tags. Has to be set like this in order to enable tags inside Automator.
	 * @return string[][]
	 */
	public function get_automator_tag_autoresponder_mapping_fields() {
		return [ 'autoresponder' => [ 'mailing_list', 'tag_input' ] ];
	}

	/**
	 * @return bool
	 */
	public function has_forms() {
		return true;
	}

	/**
	 * @return string
	 */
	public function get_forms_key() {
		return $this->get_key() . '_form';
	}

	/**
	 * @return array
	 */
	public function get_forms() {
		$forms = [];

		try {
			$api = $this->get_api_instance();

			/* since the clever-reach forms depend on the mailing list, they are structured according to the list IDs */
			foreach ( $this->get_lists() as $list ) {
				$forms[ $list->id ][] = [
					'id'   => 0,
					'name' => 'none',
				];
			}

			foreach ( $api->get_forms() as $form ) {
				$list_id = $form->customer_tables_id;

				if ( empty( $forms[ $list_id ] ) ) {
					$forms[ $list_id ] = [];
				}

				$forms[ $list_id ][ $form->id ] = [
					'id'   => $form->id,
					'name' => $form->name,
				];
			}
		} catch ( \Exception $e ) {
			Utils::log_error( 'Error while fetching the forms! Error message: ' . $e->getMessage() );
		}

		return $forms;
	}

	/**
	 * @return bool
	 */
	public function has_optin() {
		return true;
	}

	/**
	 * @return string
	 */
	public function get_optin_key() {
		return $this->get_key() . '_optin';
	}

	/**
	 * This is called from Thrive Automator when the 'Tag user' automation is triggered.
	 * In this case, we want to add the received tags to the received subscriber and mailing list.
	 * This is only done if the subscriber already exists.
	 *
	 * @param string $email
	 * @param string $tags
	 * @param array  $extra
	 *
	 * @return bool
	 */
	public function update_tags( $email, $tags = '', $extra = [] ) {
		$list_identifier = empty( $extra['list_identifier'] ) ? null : $extra['list_identifier'];

		$args = [
			'email'               => $email,
			$this->get_tags_key() => $tags,
		];

		$subscriber_exists = false;

		try {
			$api = $this->get_api_instance();

			if ( ! empty( $api->get_subscriber_by_email( $list_identifier, $email ) ) ) {
				$subscriber_exists = true;
			}
		} catch ( \Exception $e ) {
			Utils::log_error( 'Error while fetching the subscriber! Error message: ' . $e->getMessage() );
		}

		return $subscriber_exists ? $this->add_subscriber( $list_identifier, $args, true ) : false;
	}

	/**
	 * This is called from Thrive Quiz Builder and it is used to add an array of tags to already existing ones.
	 * Only used if has_tags() is enabled.
	 *
	 * @param array|string $tags
	 * @param array        $data
	 *
	 * @return array
	 */
	public function push_tags( $tags, $data = [] ) {
		if ( empty( $tags ) || ! $this->has_tags() ) {
			return $data;
		}

		if ( is_array( $tags ) ) {
			$tags = implode( ',', $tags );
		} else if ( ! is_string( $tags ) ) {
			$tags = '';
		}

		$tag_key = $this->get_tags_key();

		if ( empty( $data[ $tag_key ] ) ) {
			$tag_data = $tags;
		} else {
			$tag_data = $data[ $tag_key ] . ( empty( $tags ) ? '' : ',' . $tags );
		}

		$data[ $tag_key ] = trim( $tag_data );

		return $data;
	}

	public static function get_thumbnail() {
		return static::get_assets_url() . 'images/clever_reach.png';
	}

	public static function get_assets_url() {
		return THRIVE_THIRD_PARTY_PLUGIN_URL . 'autoresponders/clever-reach/assets/';
	}

	public static function get_link_to_controls_page() {
		return get_admin_url() . 'admin.php?page=thrive_third_party_autoresponder_section';
	}
}
