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
 * Class Hooks
 * @package Thrive\ThirdPartyAutoResponderDemo\AutoResponders\CleverReach
 */
class Hooks {
	public static function init() {
		add_action( 'thrive_third_party_autoresponder_page_template', [ __CLASS__, 'include_settings_view' ] );

		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_styles' ] );

		/* integrate with TCB editor - add a HTML template and functionality for Lead Generation API Connections */
		add_action( 'tcb_editor_enqueue_scripts', [ __CLASS__, 'enqueue_architect_scripts' ] );

		add_filter( 'tcb_lead_generation_apis_with_tag_support', [ __CLASS__, 'tcb_apis_with_tags' ] );
	}

	public static function include_settings_view() {
		$credentials = Authentication::read_credentials();

		require_once __DIR__ . '/views/dashboard-settings.php';
	}

	public static function enqueue_styles() {
		if ( Utils::get_current_screen() === 'thrive_third_party_autoresponder_section' ) {
			wp_enqueue_style( 'thrive-third-party-autoresponder-css', Main::get_assets_url() . 'css/styles.css' );
		}
	}

	/**
	 * Enqueue an additional script inside Thrive Architect in order to add some custom hooks which integrate Clever-Reach with the Lead Generation element API Connections.
	 */
	public static function enqueue_architect_scripts() {
		wp_enqueue_script( 'thrive-architect-api-integration', Main::get_assets_url() . 'js/editor.js', [ 'tve_editor' ] );

		$localized_data = [
			'api_logo' => Main::get_assets_url() . 'images/clever_reach_no_text.png',
		];

		wp_localize_script( 'thrive-architect-api-integration', 'thrive_third_party_api_localized_data', $localized_data );
	}

	/**
	 * Add Clever-Reach to the list of supported APIs with tags. Required inside TCB.
	 *
	 * @param $apis
	 *
	 * @return mixed
	 */
	public static function tcb_apis_with_tags( $apis ) {
		$apis[] = 'clever-reach';

		return $apis;
	}
}
