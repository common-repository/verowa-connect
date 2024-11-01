<?php
/**
 * Contains the page "Verowa options"
 *
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @package Verowa Connect
 * @subpackage Backend
 */




/**
 * New administration page
 *
 * @return void
 */
function verowa_register_admin_menu() {
	$user = wp_get_current_user();
	if ( in_array( 'administrator', (array) $user->roles, true ) ) {
		add_menu_page(
			'Verowa',
			'Verowa',
			'manage_options',
			'verowa/main_options_menu.php',
			'verowa_main_options_menu',
			plugins_url( 'verowa-connect/images/icon.png' ),
			80
		);

		add_submenu_page(
			'verowa/main_options_menu.php',
			__( 'Verowa templates', 'verowa-connect' ),
			__( 'Verowa templates', 'verowa-connect' ),
			'manage_options',
			'verowa-options-templates',
			'verowa_templates_configuration_page'
		);

		add_submenu_page(
			'verowa/main_options_menu.php',
			__( 'Verowa Connect Settings', 'verowa-connect' ),
			__( 'Settings', 'verowa-connect' ),
			'manage_options',
			'verowa-connect-settings',
			'verowa_render_settings'
		);
	}
}

add_action( 'admin_menu', 'verowa_register_admin_menu' );

/**
 * The Verowa menu does not have its own page, so the callback function is empty.
 *
 * @return void
 */
function verowa_main_options_menu() {  }
