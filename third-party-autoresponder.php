<?php
/*
Plugin Name: CleverReach for Thrive Themes
Plugin URI: http://www.thrivethemes.com
Description: Features an example of how to add and integrate a 3rd party autoresponder with Thrive Architect and Thrive Automator.
Author: <a href="http://www.thrivethemes.com">Thrive Themes</a>
Version: 0.1
*/

add_action( 'thrive_dashboard_loaded', function () {
	require_once __DIR__ . '/class-main.php';

	Thrive\ThirdPartyAutoResponderDemo\Main::init();
} );

add_action( 'admin_menu', function () {
	add_menu_page(
		'Thrive 3rd Party Autoresponder',
		'Thrive 3rd Party Autoresponder',
		'manage_options',
		'thrive_third_party_autoresponder_section',
		'thrive_third_party_autoresponder_section',
		'',
		30
	);
} );

/**
 * Adds the framework-view for the plugin. Further HTML can be added by hooking into the 'thrive_third_party_autoresponder_page_template' action.
 */
function thrive_third_party_autoresponder_section() {
	require_once __DIR__ . '/views/third-party-autoresponder-page.php';
}
