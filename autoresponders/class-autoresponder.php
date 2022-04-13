<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package 3rd-party-autoresponder
 */

namespace Thrive\ThirdPartyAutoResponderDemo\AutoResponders;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Autoresponder
 * @package Thrive\ThirdPartyAutoResponderDemo\AutoResponders
 */
abstract class Autoresponder {
	/**
	 * @return boolean
	 */
	abstract function is_connected();

	abstract function test_connection();

	abstract function add_subscriber( $list_identifier, $args );

	/**
	 * @return array
	 */
	abstract function get_lists();

	/**
	 * @return array
	 */
	public function localize() {
		return [
			'key'           => static::get_key(),
			'title'         => static::get_title(),
			'is_connected'  => $this->is_connected(),
			'controls_link' => static::get_link_to_controls_page(),
			'thumbnail'     => static::get_thumbnail(),
		];
	}

	/**
	 * Localizes data needed in the Thrive Architect editor
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function get_api_data( $params = [] ) {
		$api_editor_data = [
			'lists' => static::get_lists(),
		];

		if ( $this->has_custom_fields() ) {
			$api_editor_data['custom_fields']     = $this->get_custom_fields();
			$api_editor_data['api_custom_fields'] = $this->get_api_custom_fields( $params );
		}

		/* if forms are enabled, Thrive Architect requires the list of forms in the editor */
		if ( $this->has_forms() ) {
			$api_editor_data['extra_settings'] = [
				'forms' => $this->get_forms()
			];
		}

		return $api_editor_data;
	}

	/**
	 * False by default.
	 *
	 * In order to implement tags:
	 * - set this to true;
	 * - implement get_tags_key();
	 * - process the 'tags' field inside add_subscriber() and add it to the request;
	 * - override get_automator_add_autoresponder_mapping_fields() to also contain the 'tag_input' key ( for Thrive Automator );
	 * - implement get_automator_tag_autoresponder_mapping_fields() ( for Thrive Automator );
	 * - implement update_tags() ( for Thrive Automator );
	 * - implement push_tags() ( for Thrive Quiz Builder );
	 * - if needed, adapt autoresponders\clever-reach\assets\js\editor.js to suit your API - used by Thrive Architect
	 *
	 * A working example can be found in the clever-reach folder.
	 *
	 * @return bool
	 */
	public function has_tags() {
		return false;
	}

	/**
	 * False by default.
	 *
	 * In order to implement custom fields:
	 * - set this to true;
	 * - implement get_custom_fields() - this localizes custom field mapping inside Thrive Architect
	 * - implement get_api_custom_fields() - this retrieves all the existing custom fields from the API
	 * - override get_automator_add_autoresponder_mapping_fields() to also contain the 'api_fields' key;
	 * - implement build_automation_custom_fields() - this builds the custom field mapping for Automator;
	 * - implement get_custom_fields_by_list() ( Automator );
	 * - process ( and parse ) the custom fields data inside add_subscriber before adding it to the request
	 *
	 * A working example can be found in the clever-reach folder, and the helpers inside class-custom-fields can be re-used.
	 */
	public function has_custom_fields() {
		return false;
	}

	/**
	 * False by default.
	 *
	 * In order to implement forms:
	 * - set this to true;
	 * - implement get_forms_key() - used by both Thrive Automator and Thrive Architect
	 * - implement get_forms() - used by both Thrive Automator and Thrive Architect
	 * - handle the form data inside the add_subscriber() function
	 * - if needed, adapt autoresponders\clever-reach\assets\js\editor.js to suit your API - used by Thrive Architect
	 *
	 * A working example can be found in the clever-reach folder.
	 * @return bool
	 */
	public function has_forms() {
		return false;
	}

	/**
	 * False by default.
	 *
	 * In order to implement the opt-in selector:
	 * - set this to true;
	 * - implement get_optin_key() - used by both Thrive Automator and Thrive Architect
	 * - handle the opt-in data inside the add_subscriber() function
	 *
	 * A working example can be found in the clever-reach folder.
	 * @return bool
	 */
	public function has_optin() {
		return false;
	}

	/**
	 * Specifies the features used by this autoresponder inside Thrive Automator.
	 * By default only the mailing list is enabled, in order to add tags, forms, opt-in type and custom fields, extend this.
	 * @return \string[][]
	 */
	public function get_automator_add_autoresponder_mapping_fields() {
		/**
		 * Some usage examples for this:
		 *
		 * A basic configuration only for mailing lists is "[ 'autoresponder' => [ 'mailing_list' ] ]".
		 * If the custom fields rely on the mailing list, they are added like this: "[ 'autoresponder' => [ 'mailing_list' => [ 'api_fields' ] ] ]"
		 * If the custom fields don't rely on the mailing list ( global custom fields ), the config is: "[ 'autoresponder' => [ 'mailing_list', 'api_fields' ] ]"
		 *
		 * Config for mailing list, custom fields (global), tags: "[ 'autoresponder' => [ 'mailing_list', 'api_fields', 'tag_input' ] ]"
		 *
		 * Config for mailing list, tags, and forms that depend on the mailing lists:
		 * "[ 'autoresponder' => [ 'mailing_list' => [ 'form_list' ], 'api_fields' => [], 'tag_input' => [] ] ]"
		 * ^ If one of the keys has a corresponding array, empty arrays must be added to the other keys in order to respect the structure.
		 */
		return [ 'autoresponder' => [ 'mailing_list' ] ];
	}

	/**
	 * Extra data to localize inside Thrive Architect.
	 * Nothing is needed by default, so we return an empty array.
	 *
	 * @return array
	 */
	public function get_data_for_setup() {
		return [];
	}

	/**
	 * Thumbnail shown in the Thrive Dashboard API connections tab.
	 *
	 * @return string
	 */
	public static function get_thumbnail() {
		return '';
	}

	/**
	 * @return string
	 */
	public static function get_link_to_controls_page() {
		return 'link-to-your-plugin-page-here';
	}

	/**
	 * @return string
	 */
	public static function get_type() {
		return 'autoresponder';
	}

	/**
	 * This is the email shortcode tag from clever-reach
	 *
	 * @return string
	 */
	public function get_email_merge_tag() {
		return '{EMAIL}';
	}
}
