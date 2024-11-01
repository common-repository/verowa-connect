<?php
/**
 * Plugin Name: Verowa Connect
 * Plugin URI: https://www.verowa.ch
 * Description: Include your Verowa data seamlessly into your WordPress project!
 * Author: Picture-Planet GmbH
 * Version: 3.0.1
 * Requires at least: 5.2
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: verowa-connect
 * Domain Path: /languages
 */

require 'includes/presets.php';
require 'functions/general.php';
require 'log/class-verowa-connect-debugger.php';
require 'general/shortcode/verowa-image.php';
require 'general/shortcode/verowa-urlencode.php';
require 'general/shortcode/verowa-encode-link.php';
require 'general/shortcode/deprecated-shortcode-aliases.php';
require 'admin/class-verowa-backend-settings.php';

// Models.
require 'models/class-verowa-template.php';
require 'functions/api-calls.php';

// Standard pages and auxiliary pages.
require 'general/activate-config.php';
require 'general/class-verowa-postings.php';
require 'general/class-verowa-update-controller.php';
require 'general/custom-post-action-filters.php';
require 'general/register-post-type.php';
require 'general/rest-routes.php';
require 'general/update-cron.php';
require 'general/wp-filter.php';

// Functions collection.
require 'functions/cache.php';
require 'functions/event.php';
require 'functions/form.php';
require 'functions/layer.php';
require 'functions/lite-speed.php';
require 'functions/person.php';
require 'functions/roster.php';
require 'functions/subscription.php';
require 'functions/user-data.php';
require 'functions/verowa-template.php';
require 'functions/verowa-log.php';
require 'functions/wpml.php';

if (true === is_admin ())
{
	require 'admin/admin-notices.php';
	require 'admin/admin-pages.php';
	require 'admin/backend-settings.php';
	require 'admin/class-verowa-templates-list.php';
	require 'admin/save-post-action.php';
	require 'admin/templates-edit.php';
}


// Event module.
require 'events/assign-list.php';
require 'events/event-filter.php';
require 'events/event-list-widget.php';
require 'events/event-filter-widget.php';
require 'events/shortcode/verowa-agenda.php';
require 'events/shortcode/verowa-event-list.php';
require 'events/shortcode/verowa-event-details-json.php';

// Newsletter module.
require 'newsletter/verowa-newsletter-request-form.php';
require 'newsletter/verowa-newsletter-options-form.php';

// Person module.
require 'persons/assign-persons.php';
require 'persons/show-persons-shortcode.php';
require 'persons/show-persons-widget.php';
require 'persons/single-person-page.php';

require 'forms/class-verowa-formfields-rendering.php';
require 'forms/class-verowa-related-field-wrapper.php';
require 'forms/verowa-renting-form.php';
require 'forms/verowa-subscription-form.php';

// Rosters.
require 'rosters/verowa-roster-entries.php';

// UNDONE: AM => WP liefert den Sprachcode über am Form, VER setzt den Detail-URL zusammen

// Unless already done.
add_post_type_support( 'page', 'excerpt' );

add_action( 'wp_enqueue_scripts', 'add_verowa_styles', 30 );
add_action( 'admin_enqueue_scripts', 'add_verowa_admin_scripts' );


$obj_posting = new Picture_Planet_GmbH\Verowa_Connect\Verowa_Postings();
$obj_posting->init();

/**
 * Adds the styles and scripts for Verowa-Connect
 *
 * @return void
 */
function add_verowa_styles() {

	/**
	 * Later configurable via backend.
	 */
	$min_extension = '.min';
	$arr_args = array (
			'in_footer' => false,
		);

	$plugin_data    = verowa_connect_get_plugin_data();
	$str_plugin_ver = $plugin_data['Version'] ?? substr( uniqid(), 0, 6 );

	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_style(
		'verowa-datepicker-style',
		plugins_url(
			'css/jquery-ui' . $min_extension . '.css',
			__FILE__
		),
		array(),
		$str_plugin_ver
	);

	wp_enqueue_style( 'dashicons' );
	wp_enqueue_script(
		'script-handle',
		plugins_url(
			'js/functions' . $min_extension . '.js',
			__FILE__
		),
		array( 'jquery', 'jquery-ui-datepicker' ),
		$str_plugin_ver,
		$arr_args,
	);

	$str_base_url = verowa_get_base_url();
	$str_base_url = str_replace( '/blog', '', $str_base_url );

	wp_localize_script(
		'script-handle',
		'verowa_L10n_functions',
		array(
			'api_error_save_renting' => esc_html__( 'An error occurred while submitting the data, please try again later.', 'verowa-connect' ),
			'BASE_URL' => $str_base_url,
		)
	);

	// We include the components for the agenda everywhere so that they are already loaded when you access them.
	wp_enqueue_style(
		'verowa-styles',
		plugins_url( 'css/verowa-connect.css', __FILE__ ),
		array(),
		$str_plugin_ver
	);

	wp_enqueue_style(
		'verowa-agenda-css',
		plugins_url( 'css/verowa-agenda' . $min_extension . '.css', __FILE__ ),
		array(),
		$str_plugin_ver
	);

	wp_enqueue_script(
		'verowa-agenda',
		plugins_url( 'js/verowa-agenda' . $min_extension . '.js', __FILE__ ),
		array( 'jquery', 'jquery-ui-datepicker' ),
		$str_plugin_ver,
		$arr_args
	);
	
	wp_localize_script(
		'verowa-agenda',
		'verowa_L10n_agenda',
		array(
			'BASE_URL' => $str_base_url,
		)
	);
}




/**
 * Add admin scripts
 *
 * @return void
 */
function add_verowa_admin_scripts() {
	$str_page = $_REQUEST['page'] ?? '';
	
	if ( 'verowa-options-templates' === $str_page || 'verowa-connect-settings' === $str_page ) {
		$cm_settings['codeEditor'] = wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
		wp_localize_script( 'jquery', 'cm_settings', $cm_settings );
		wp_enqueue_script( 'wp-theme-plugin-editor' );
		wp_enqueue_style( 'wp-codemirror' );
	}

	wp_enqueue_style( 'verowa-admin-styles', plugins_url( 'css/backend-style.css', __FILE__ ), array(), true );

	wp_enqueue_script( 'verowa_admin_script', plugins_url( 'js/verowa-admin-script.js', __FILE__ ), array(), true );
	wp_localize_script( 'verowa_admin_script', 'wpApiSettings', array(
		'root' => esc_url_raw( rest_url() ),
		'nonce' => wp_create_nonce( 'wp_rest' )
	) );
}



/**
 * Initializes Session cookie.
 */
add_action(
	'init',
	function() {
		// In the admin area, it's not necessary.
		if ( false === is_admin() ) {
			// Must be done as soon as possible.
			$str_session_key = verowa_get_key_from_cookie();
			if ( '' === $str_session_key ) {
				// no cookie -.- set one!
				$str_session_key = verowa_save_user_data( array( 'verowa_agenda_filter' => array() ) );


				/**
				 * For future testing purpose
				 * If the error "Header already sent" occurs, this indicates a problem before the init action
				 *
				 * @var bool
				 */
				$cookie_is_set = verowa_set_connect_cookie( $str_session_key );
			}
			
			if ( false === wp_next_scheduled( 'verowa_connect_importer' ) ) {
				verowa_schedule_importer();
			}
		}
	},
	1
);


/**
 * Enter verowa_id for the details in the query_vars because
 * you can't access normal $_GET parameters in WP
 */
add_filter(
	'query_vars',
	function( $vars ) {
		$vars[] = 'verowa_id';
		return $vars;
	}
);

// Functions that are executed during the first activation.
register_activation_hook( __FILE__, 'verowa_plugin_activate' );
register_deactivation_hook( __FILE__, 'verowa_plugin_deactivate' );

add_action( 'init', 'verowaconnect_plugin_init' );


/**
 * Initializes the plugin and the shortcodes
 *
 * @return void
 */
function verowaconnect_plugin_init() {
	global $obj_posting;

	load_plugin_textdomain( 'verowa-connect' );
	verowa_cache_bind_hooks();

	add_shortcode( 'verowa_image', 'verowa_image_rendering' );
	add_shortcode( 'verowa_urlencode', 'verowa_urlencode_callback' );
	add_shortcode( 'verowa_encode_link', 'verowa_encode_link_callback' );

	add_shortcode( 'verowa_renting_form', 'verowa_room_renting_form' );
	add_shortcode( 'verowa_renting_validate', 'verowa_renting_validate' );
	add_shortcode( 'verowa_renting_response', 'print_verowa_renting_response' );
	add_shortcode( 'verowa_sub_targets', 'show_verowa_sub_targets' );

	add_shortcode( 'verowa_agenda', 'verowa_agenda' );
	add_shortcode( 'verowa_event_filter', 'verowa_event_filter' );
	add_shortcode( 'verowa_event_list', 'verowa_event_list' );
	add_shortcode( 'verowa_event_liste', 'verowa_event_liste' );
	add_shortcode( 'verowa_event_details_json', 'verowa_event_details_json' );

	add_shortcode( 'verowa_newsletter_request_form', 'verowa_newsletter_request_form' );
	add_shortcode( 'verowa_newsletter_options_form', 'verowa_newsletter_options_form' );

	add_shortcode( 'verowa_person', 'verowa_person' );
	add_shortcode( 'verowa_personen', 'verowa_personen' );

	add_shortcode( 'verowa_subscription_form', 'verowa_subscription_form' );
	add_shortcode( 'verowa_subscription_confirmation', 'verowa_subscription_confirmation' );
	add_shortcode( 'verowa_subscription_validation', 'verowa_subscription_validation' );

	add_shortcode( 'verowa_roster_entries', 'verowa_roster_entries' );
	add_shortcode( 'verowa-first-roster-entry', 'verowa_first_roster_entry' );
}

add_action( 'the_post', 'verowa_remove_wpautop', 10, 1 );

/**
 * Filter to remove wpautop from Verowa events and persons.
 *
 * @param WP_Post $post WordPress Post.
 * @return void
 */
function verowa_remove_wpautop( $post ) {
	if ( 'verowa_person' === $post->post_type || 'verowa_event' === $post->post_type ) {
		remove_filter( 'the_content', 'wpautop' );
	}
}
