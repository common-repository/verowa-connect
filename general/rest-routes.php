<?php
/**
 * Implements all wp-json extensions for "Verowa Connect".
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @package Verowa Connect
 * @subpackage General
 */

/**
 * Dieser Rest-Wrapper liefert uns den Inhalt für die Event-Details in der Event-Liste.
 * Für die Listendarstellung wird zuerst per REST-Query mal die grobe Liste abgeholt; erst danach, wenn die
 * Seite dargestellt ist, holen wir per JS zu jedem einzelnen Event auch noch die Details aus Verowa.
 * Wir stellen den Request per jQuery nicht direkt an die Verowa-API, weil das den API Key verraten würde.
 */

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'verowa/v1',
			'/event/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => 'verowa_event_id_rest_wrapper',
				'permission_callback' => 'verowa_api_permission_callback',
			)
		);
	}
);




/**
 * Callback function for the REST wrapper
 *
 * @param array $data [id => event_id ].
 * @return mixed
 */
function verowa_event_id_rest_wrapper( $data ) {
	$event_id       = intval( $data['id'] ?? 0 );
	$arr_event_data = verowa_event_db_get_content( $event_id );

	return $arr_event_data;
}




add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'verowa/v1',
			'/agenda_event/(?P<current_batch>[-a-zA-Z0-9_]+)/',
			array(
				'methods'             => 'GET',
				'callback'            => 'verowa_get_agenda_events',
				'permission_callback' => 'verowa_api_permission_callback',
			)
		);
	}
);




/**
 * Get agenda events based on the provided filter data.
 *
 * @param array $data An array containing filter data for the agenda events.
 *                   The array may contain the following keys:
 *                   - 'current_batch': The current batch number of events to retrieve.
 *
 * @return stdClass A stdClass object containing the results of the query.
 *                 The object may contain the following properties:
 *                 - 'has_further_results': A boolean indicating whether there are further results.
 *                 - 'content': A string containing the HTML content of the retrieved events.
 */
function verowa_get_agenda_events( $data ) {
	global $post;

	$str_content       = '';
	$result            = new stdClass();
	$str_session_key   = verowa_get_key_from_cookie();
	$str_date8         = sanitize_text_field( trim( wp_unslash( $_GET['date'] ?? '' ) ) );
	$arr_list_ids      = $_GET['arr_list_ids'] ?? array();
	$arr_list_ids      = array_map( 'sanitize_text_field', $arr_list_ids );
	$str_search_string = sanitize_text_field( wp_unslash( $_GET['search_string'] ?? '' ) );
	$str_language_code = sanitize_text_field( wp_unslash( $_GET['wpml_language_code'] ?? '' ) );
	$format_in         = 'Y-m-d H:i:s';
	$obj_date_filter   = 'now';
	$arr_user_data     = verowa_get_user_data( $str_session_key );

	$arr_user_data['verowa_agenda_current_batch']           = $data['current_batch'];
	$arr_user_data['verowa_agenda_filter']                  = array();
	$arr_user_data['verowa_agenda_filter']['search_string'] = $str_search_string;
	$arr_user_data['verowa_agenda_filter']['arr_list_ids']  = $arr_list_ids;

	if ( strlen( $str_date8 ) > 0 ) {
		$arr_user_data['verowa_agenda_filter']['date8_displays_from'] = $str_date8;

		$str_date_filter = substr( $str_date8, 0, 4 ) . '-' . substr( $str_date8, 4, 2 ) . '-' .
			substr( $str_date8, 6, 2 ) . ' 00:00:00';
		$obj_date_filter = DateTime::createFromFormat( $format_in, $str_date_filter, wp_timezone() );
	}

	verowa_update_user_data( $str_session_key, $arr_user_data );

	// check if the date is proper set.
	$obj_date_filter = false === $obj_date_filter ? 'now' : $obj_date_filter;

	$batch_size = intval (get_option( 'verowa_agenda_batch_size', 50 ));
	$int_offset = ( intval( $data['current_batch'] ) * $batch_size ) - $batch_size;

	$arr_events = verowa_events_db_get_agenda(
		'',
		implode( ',', $arr_list_ids ),
		$str_search_string,
		$batch_size,
		$int_offset,
		$obj_date_filter
	);

	$result->has_further_results = count( $arr_events ?? [] ) === $batch_size ? true : false;

	$template_id = 0;
	if ( null !== $post ) {
		$template_id = intval( get_post_meta( $post->ID, 'verowa_eventlist_template', true ) );
	}

	// If there is no template id in the post meta either we get the default from the options.
	if ( 0 === $template_id ) {
		$template_id = intval( get_option( 'verowa_default_eventlist_template' ) );
	}
	$arr_templates = verowa_get_single_template( $template_id );

	foreach ( $arr_events as $arr_single_event ) {
		$str_content .= do_shortcode(
			verowa_event_show_single(
				$arr_single_event,
				$arr_templates,
				$str_language_code
			)
		);
	}

	$result->content = $str_content;
	return $result;
}


add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'verowa/v1',
			'/plugin_info',
			array(
				'methods'             => 'GET',
				'callback'            => 'verowa_return_plugin_info',
				'permission_callback' => 'verowa_api_permission_callback',
			)
		);
	}
);




/**
 * Return an array containing information about the Verowa Connect plugin.
 *
 * @return array
 */
function verowa_return_plugin_info() {
	global $wpdb;

	$plugin_data = verowa_connect_get_plugin_data();
	$arr_counts  = verowa_get_custom_posts_count();

	// -1 indicates that an error has occurred
	$count_deprecated_person_groups = $wpdb->get_var(
		'SELECT count(*) as `count` ' .
		'FROM `' . $wpdb->prefix . 'verowa_person_groups` WHERE `deprecated` = 1;'
	) ?? -1;

	return array(
		'version'                   => $plugin_data['Version'],
		'member'                    => get_option( 'verowa_instance', true ),
		'culture'                   => get_locale(),
		'verowa_connect_db_version' => get_option( 'verowa_connect_db_version', -1 ),
		'verowa_list_ids'           => get_option( 'verowa_list_ids', 'leer' ),
		'verowa_roster_ids'         => get_option( 'verowa_roster_ids', 'leer' ),
		'last_update_checks'        => gmdate( DateTime::RFC1036, get_option( 'verowa_connect_last_update_checks', 'leer' ) ),
		'count_event_posts'         => $arr_counts['int_count_event_posts'],
		'verowa_events'             => $arr_counts['int_verowa_events'],
		'count_person_posts'        => $arr_counts['int_count_person_posts'],
		'verowa_persons'            => $arr_counts['int_verowa_persons'],
		'deprecated_person_groups'  => $count_deprecated_person_groups,
	);
}




add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'verowa/v1',
			'/update_event',
			array(
				'methods'             => 'GET',
				'callback'            => 'force_update_single_event',
				'permission_callback' => 'verowa_api_permission_callback',
			)
		);
	}
);




/**
 * Force the update of a single event.
 * 
 * @return int
 */
function force_update_single_event() {
	$obj_update = new Verowa_Update_Controller();
	$obj_update->init( 'single_event', $_GET['id'] ?? 0 );
	$obj_update->update_verowa_events_in_db( $_GET['id'] ?? 0 );

	return 0; // 0 = OK.
}



add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'verowa/v1',
			'/update_person',
			array(
				'methods'             => 'GET',
				'callback'            => 'force_update_single_person',
				'permission_callback' => 'verowa_api_permission_callback',
			)
		);
	}
);


/**
 * Force the update of a single person.
 * 
 * @return int
 */
function force_update_single_person() {
	$obj_update = new Verowa_Update_Controller();
	$obj_update->init( 'single_person', $_GET['id'] ?? 0 );
	$obj_update->update_verowa_persons_in_db( $_GET['id'] ?? 0 );

	return 0; // 0 = OK
}




add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'verowa/v1',
			'/save_renting_request',
			array(
				'methods'             => 'POST',
				'callback'            => 'verowa_save_renting_request',
				'permission_callback' => 'verowa_api_permission_callback',
			)
		);
	}
);


/**
 * Send request to the VEROWA API and return the response to the JavaScript
 *
 * @param WP_REST_Request $obj_request
 *
 * @return array
 */
function verowa_save_renting_request( $obj_request ) {
	$verowa_api_key = get_option( 'verowa_api_key', false );
	$verowa_member  = get_option( 'verowa_instance', false );

	// Prepare API-URLs.
	$str_send_url                       = 'https://api.verowa.ch/save_renting_request';
	$arr_renting_data                   = json_decode( $obj_request->get_body(), true );
	$plugin_data                        = verowa_connect_get_plugin_data();
	$arr_renting_data['plugin_version'] = $plugin_data['Version'];

	$response = wp_remote_post(
		$str_send_url,
		array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(
				'member' => $verowa_member,
				'apikey' => $verowa_api_key,
			),
			'body'        => json_encode( $arr_renting_data, true ),
			'cookies'     => array(),
		)
	);

	if ( is_wp_error( $response ) ) {
		$error_message = $response->get_error_message();
		verowa_save_log( 'save_renting_request', $error_message );
		throw new Exception( $response->get_error_message() );
	} else {
		$arr_body                          = json_decode( $response['body'], true );
		$str_key                           = verowa_save_user_data( $arr_body );
		$arr_body['response_url'] = ( true === isset( $arr_body['response_url'] ) && 
			'' != trim( $arr_body['response_url'] ) ) ?
			$arr_body['response_url'] . '?key=' . $str_key : '/reservationsanfrage-response?key=' . $str_key;
	}
	return $arr_body;
}




add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'verowa/v1',
			'/save_subs_request',
			array(
				'methods'             => 'POST',
				'callback'            => 'verowa_save_sub_request',
				'permission_callback' => 'verowa_api_permission_callback',
			)
		);
	}
);




/**
 * Send request to the VEROWA API and return the response to the JavaScript
 *
 * @param WP_REST_Request $obj_request
 *
 * @return array
 */
function verowa_save_sub_request( $obj_request ) {
	$verowa_api_key = get_option( 'verowa_api_key', false );
	$verowa_member  = get_option( 'verowa_instance', false );

	// Prepare API-URLs.
	$str_send_url  = 'https://api.verowa.ch/subscribetoevent';
	$arr_subs_data = json_decode( $obj_request->get_body(), true );
	if ( $arr_subs_data ) {
		$plugin_data                     = verowa_connect_get_plugin_data();
		$arr_subs_data['plugin_version'] = $plugin_data['Version'];

		$response = wp_remote_post(
			$str_send_url,
			array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => array(
					'member' => $verowa_member,
					'apikey' => $verowa_api_key,
				),
				'body'        => json_encode( $arr_subs_data, true ),
				'cookies'     => array(),
			)
		);

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			verowa_save_log( 'save_subs_request', $error_message );
			throw new Exception( $response->get_error_message() );
		} else {
			$arr_body = json_decode( $response['body'], true );
			if ( isset( $arr_body['redirect_url'] ) ) {
				$str_key                   = verowa_save_user_data( $arr_body );
				$arr_body['redirect_url'] .= '?key=' . $str_key;
			}
		}
	}

	return $arr_body ?? array();
}




add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'verowa/v1',
			'/resend_subscription_mail',
			array(
				'methods'             => 'POST',
				'callback'            => 'verowa_resend_subscription_mail',
				'permission_callback' => 'verowa_api_permission_callback',
			)
		);
	}
);


/**
 * Send request to the VEROWA API and return the response to the JavaScript
 *
 * @param WP_REST_Request $obj_request
 *
 * @return array
 */
function verowa_resend_subscription_mail( $obj_request ) {
	$verowa_api_key = get_option( 'verowa_api_key', false );
	$verowa_member  = get_option( 'verowa_instance', false );

	// Prepare API-URLs.
	$str_send_url  = 'https://api.verowa.ch/resend_subscription_mail';
	$arr_subs_data = json_decode( $obj_request->get_body(), true );
	if ( $arr_subs_data ) {
		$plugin_data                     = verowa_connect_get_plugin_data();
		$arr_subs_data['plugin_version'] = $plugin_data['Version'];

		$response = wp_remote_post(
			$str_send_url,
			array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '2.0',
				'blocking'    => true,
				'headers'     => array(
					'member' => $verowa_member,
					'apikey' => $verowa_api_key,
				),
				'body'        => wp_json_encode( $arr_subs_data, true ),
				'cookies'     => array(),
			)
		);

		$arr_body = json_decode( $response['body'], true );
	}

	return $arr_body ?? array();
}




/*
 * Callback function is located in general/update_cron.php.
 */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'verowa/v1',
			'/update/(?P<slug>[a-zA-Z0-9-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => 'verowa_connect_importer_handler',
				'permission_callback' => 'verowa_api_permission_callback',
			)
		);
	}
);


$obj_backend = new Verowa_Backend_Settings ('');
$obj_backend->register_rest_routes();


/**
 *  WP 5.5.0, the permission_callback must be specified in register_rest_route.
 *
 * @param array $atts
 *
 * @return  bool
 */
function verowa_api_permission_callback( $atts ) {
	return true;
}
