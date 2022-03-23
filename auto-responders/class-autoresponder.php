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

	abstract static function get_link_to_controls_page();

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
	public function get_api_data( $params = array() ) {
		$api_editor_data = [
			'lists' => static::get_lists(),
		];

		if ( $this->has_custom_fields() ) {
			$api_editor_data['custom_fields']     = $this->get_custom_fields();
			$api_editor_data['api_custom_fields'] = $this->get_api_custom_fields( $params );
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
	 * A forms integration example will be implemented in the next release.
	 *
	 * @return bool
	 */
	public function has_forms() {
		return false;
	}

	/**
	 * Specifies the features used by this autoresponder inside Thrive Automator.
	 * By default only the mailing list is enabled, in order to add tags and custom fields, extend this.
	 * @return \string[][]
	 */
	public function get_automator_add_autoresponder_mapping_fields() {
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
}
