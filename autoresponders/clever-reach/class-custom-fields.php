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
 * Class Custom_Fields
 * @package Thrive\ThirdPartyAutoResponderDemo\AutoResponders
 */
class Custom_Fields {
	/**
	 * @var API
	 */
	private $api;

	/**
	 * @var string
	 */
	private $key;

	/**
	 * @var array
	 */
	private $all_custom_fields;

	public static $default_custom_fields = [
		[ 'id' => 'name', 'placeholder' => 'Name' ],
		[ 'id' => 'phone', 'placeholder' => 'Phone' ],
		[ 'id' => 'mapping_text', 'placeholder' => 'Text' ],
		[ 'id' => 'mapping_url', 'placeholder' => 'URL' ],
		[ 'id' => 'mapping_hidden', 'placeholder' => 'Hidden' ],
		[ 'id' => 'mapping_radio', 'placeholder' => 'Radio' ],
		[ 'id' => 'mapping_select', 'placeholder' => 'Dropdown' ],
		[ 'id' => 'mapping_checkbox', 'placeholder' => 'Checkbox' ],
		[ 'id' => 'mapping_textarea', 'placeholder' => 'Textarea' ],
	];

	public function __construct( $api, $key ) {
		$this->api = $api;
		$this->key = $key;
	}

	/**
	 * @return \string[][]
	 */
	public static function get_custom_field_types() {
		return static::$default_custom_fields;
	}

	/**
	 * Get all the custom fields, or get them specifically for a mailing list.
	 *
	 * @return array
	 */
	public function get_custom_fields() {
		return $this->get_all_custom_fields();
	}

	/**
	 * Get all the custom fields.
	 *
	 * @return array|mixed
	 */
	public function get_all_custom_fields() {
		if ( empty( $this->all_custom_fields ) ) {
			$this->all_custom_fields = [];

			$lists = $this->api->get_lists();

			/* only continue if mailing lists exist ( they're not required for the next step, but there's no point in doing it if there are no lists ) */
			if ( is_array( $lists ) ) {
				$this->all_custom_fields = [];

				foreach ( $this->api->get( '/attributes' ) as $custom_field ) {
					if ( ! empty( $custom_field->type ) && $custom_field->type === 'text' ) {
						$this->all_custom_fields[] = $this->normalize_custom_field( $custom_field );
					}
				}
			}
		}

		return $this->all_custom_fields;
	}

	/**
	 * @param $field
	 *
	 * @return array
	 */
	protected function normalize_custom_field( $field ) {
		$field = (array) $field;

		$input_name = empty( $field['name'] ) ? '' : $field['name'];

		return [
			'id'    => isset( $field['id'] ) ? $field['id'] : '', /* unique identifier */
			'name'  => $input_name, /* will be displayed as 'name="received_name_field"' for an input */
			'type'  => empty( $field['type'] ) ? '' : $field['type'], /* type, for e.g. [url, text] */
			'label' => empty( $field['preview_value'] ) ? $input_name : $field['preview_value'], /* label to display for users */
		];
	}

	/**
	 * Get the mapped and parsed custom fields array based on form parameters
	 *
	 * @param $submitted_data
	 *
	 * @return array
	 */
	public function parse_custom_fields( $submitted_data ) {
		$parsed_form_data     = static::parse_form_data( $submitted_data );
		$parsed_custom_fields = [];

		foreach ( static::get_mapped_field_ids() as $mapped_field_name ) {
			/**
			 * Extract an array with all the custom fields keys from the form data
			 * {ex: [mapping_url_0, .. mapping_url_n] / [mapping_text_0, .. mapping_text_n]}
			 */
			$custom_fields = preg_grep( "#^{$mapped_field_name}#i", array_keys( $parsed_form_data ) );

			if ( empty( $custom_fields ) || ! is_array( $custom_fields ) ) {
				continue;
			}

			foreach ( $custom_fields as $custom_field_key ) {
				/* remove the '[]' from checkbox mapping */
				$parsed_custom_field_key = str_replace( '[]', '', $custom_field_key );

				if ( empty( $parsed_form_data[ $custom_field_key ][ $this->key ] ) || empty( $submitted_data[ $parsed_custom_field_key ] ) ) {
					continue;
				}

				$custom_field_id   = $parsed_form_data[ $custom_field_key ][ $this->key ];
				$custom_field_name = $this->get_custom_field_name_by_id( $custom_field_id );

				$custom_field_value = $submitted_data[ $parsed_custom_field_key ];

				if ( is_array( $custom_field_value ) ) {
					$custom_field_value = implode( ', ', $custom_field_value );
				}

				$custom_field_value = sanitize_text_field( $custom_field_value );

				if ( ! empty( $custom_field_name ) ) {
					$parsed_custom_fields[ $custom_field_name ] = $custom_field_value;
				}
			}
		}

		return $parsed_custom_fields;
	}

	/**
	 * @param $submitted_data
	 *
	 * @return array|bool|mixed|string
	 */
	public static function parse_form_data( $submitted_data ) {
		/* make sure the format is a serialized array with base64 encoding */
		if ( empty( $submitted_data['tve_mapping'] ) || ! Utils::is_base64_encoded( $submitted_data['tve_mapping'] ) || ! is_serialized( base64_decode( $submitted_data['tve_mapping'] ) ) ) {
			return [];
		}

		$form_data = Utils::safe_unserialize( base64_decode( $submitted_data['tve_mapping'] ) );

		return is_array( $form_data ) ? $form_data : [];
	}

	/**
	 * @param $custom_field_id
	 *
	 * @return mixed|string
	 */
	public function get_custom_field_name_by_id( $custom_field_id ) {
		$custom_fields = $this->get_all_custom_fields();

		$custom_field_index = array_search( $custom_field_id, array_column( $custom_fields, 'id' ), true );

		return $custom_field_index === false ? '' : $custom_fields[ $custom_field_index ]['name'];
	}

	/**
	 * Build the custom field mapping for automations
	 *
	 * @param $automation_data
	 *
	 * @return array
	 */
	public function build_automation_fields( $automation_data ) {
		$mapped_data = [];

		foreach ( $automation_data['api_fields'] as $custom_field ) {
			$custom_field_name  = $this->get_custom_field_name_by_id( $custom_field['key'] );
			$custom_field_value = sanitize_text_field( $custom_field['value'] );

			if ( ! empty( $custom_field_name ) && ! empty( $custom_field_value ) ) {
				$mapped_data[ $custom_field_name ] = $custom_field_value;
			}
		}

		return $mapped_data;
	}

	/**
	 * @return array
	 */
	public static function get_mapped_field_ids() {
		return array_column( static::get_custom_field_types(), 'id' );
	}
}
