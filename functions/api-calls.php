<?php
/**
 * Provide a generic function to perform API calls
 * For Verowa persons and events details, it has a specific function
 *
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @package Verowa Connect
 * @subpackage Functions
 */

/**
 * These functions read the data from API.Verowa.ch.
 * It now only covers one API call.
 * "Pure REST" and "SOAP as REST".
 *
 * Rückgabewert: Returns the HTTP status code and its message with the received data.
 *               [code, message, data]
 *
 * @param mixed $method Name of the API method.
 * @param mixed $id Id off the Record.
 * @param mixed $soap_is_on Whether switched on or off.
 *
 * @return array
 */
function verowa_api_call( $method, $id, $soap_is_on = false ) {
	$api_url  = 'https://api.verowa.ch/';
	$instance = get_option( 'verowa_instance', true );
	$api_key  = get_option( 'verowa_api_key', true );

	// Changes the URL structure depending on whether SOAP functions are on.
	if ( true === $soap_is_on ) {
		$the_verowa_api_query = $api_url . $method . '/' . $instance . '/' . $api_key . '/' . $id;
	} else {
		$desired_id           = ( '' === $id ) ? '' : $id . '/';
		$the_verowa_api_query = $api_url . $method . '/' . $instance . '/' . $desired_id . $api_key;
	}

	$arg          = array( 'timeout' => 15 ); // In seconds.
	$obj_response = wp_remote_get( $the_verowa_api_query, $arg );

	if ( ! is_wp_error( $obj_response ) ) {
		// Fallback code 404 (not found).
		$int_code    = isset( $obj_response['response']['code'] ) ? intval( $obj_response['response']['code'] ) : 404;
		$str_message = isset( $obj_response['response']['message'] ) ? $obj_response['response']['message'] : '';
		$json        = wp_remote_retrieve_body( $obj_response );
		$data        = json_decode( $json, true ) ?? array();
	} else {
		$int_code    = '500';
		$str_message = _x(
			'Verowa API: internal server error (500)',
			'An error occurred when retrieving the Verowa API, which is not displayed to the user.',
			'verowa-connect'
		);
		$data        = '';
		// TODO: reduzieren der Mails bei einem API Ausfall auf 1 pro Stunde
		verowa_send_mail(
			VEROWA_REPORTING_MAIL,
			'Error verowa_api_call',
			$the_verowa_api_query . '<br />' .
			// phpcs:ignore
			print_r( $obj_response, true ),
			true
		);
	}

	return array(
		'code'    => $int_code,
		'message' => $str_message,
		'data'    => $data,
	);
}




/**
 * Returns the HTTP status code and its message with the received data.
 *
 * @param array $arr_api_event_ids The details are collected for these IDs.
 *
 * @return array [code, message, data]
 */
function verowa_get_eventdetails( $arr_api_event_ids ) {
	$verowa_api_key = get_option( 'verowa_api_key', false );
	$verowa_member  = get_option( 'verowa_instance', false );
	$arr_ret        = array();

	$arr_api_event_infos = wp_remote_post(
		'https://api.verowa.ch/geteventdetails',
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
			'body'        => wp_json_encode( $arr_api_event_ids, true ),
			'cookies'     => array(),
		)
	);

	if ( ! is_wp_error( $arr_api_event_infos ) ) {
		// Fallback code 404 (not found).
		$int_code    = isset( $arr_api_event_infos['response']['code'] ) ?
			intval( $arr_api_event_infos['response']['code'] ) : 404;
		$str_message = isset( $arr_api_event_infos['response']['message'] ) ?
			$arr_api_event_infos['response']['message'] : '';

		$json    = wp_remote_retrieve_body( $arr_api_event_infos );
		$arr_ret = json_decode( $json, true );
	} else {
		$int_code    = '500';
		$str_message = _x(
			'Verowa API: internal server error (500)',
			'An error occurred when retrieving the Verowa API, which is not displayed to the user.',
			'verowa-connect'
		);
		// phpcs:ignore
		verowa_send_mail( VEROWA_REPORTING_MAIL, 'Error verowa_get_eventdetails', print_r( $arr_api_event_infos, true ), true );
	}

	return array(
		'code'    => $int_code,
		'message' => $str_message,
		'data'    => $arr_ret,
	);
}




/**
 * Returns the HTTP status code and its message with the received data.
 *
 * @param array $arr_person_ids The details are collected for these IDs.
 *
 * @return array
 */
function verowa_get_persondetails( $arr_person_ids ) {
	$verowa_api_key = get_option( 'verowa_api_key', false );
	$verowa_member  = get_option( 'verowa_instance', false );
	$arr_ret        = array();

	$arr_api_person_infos = wp_remote_post(
		'https://api.verowa.ch/postpersondetails',
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
			'body'        => wp_json_encode( $arr_person_ids, true ),
			'cookies'     => array(),
		)
	);

	if ( ! is_wp_error( $arr_api_person_infos ) ) {
		// Fallback code 404 (not found).
		$int_code    = isset( $arr_api_person_infos['response']['code'] ) ?
			intval( $arr_api_person_infos['response']['code'] ) : 404;
		$str_message = isset( $arr_api_person_infos['response']['message'] ) ?
			$arr_api_person_infos['response']['message'] : '';

		$json    = wp_remote_retrieve_body( $arr_api_person_infos );
		$arr_ret = json_decode( $json, true );
	} else {
		$int_code    = '500';
		$str_message = _x(
			'Verowa API: internal server error (500)',
			'An error occurred when retrieving the Verowa API, which is not displayed to the user.',
			'verowa-connect'
		);
		// phpcs:ignore
		verowa_send_mail( VEROWA_REPORTING_MAIL, 'Error verowa_get_persondetails', print_r( $arr_api_person_infos, true ), true );
	}

	return array(
		'code'    => $int_code,
		'message' => $str_message,
		'data'    => $arr_ret,
	);
}




/**
 * Get all layers in an array from the API
 *
 * @return mixed
 */
function verowa_get_layers_array() {
	$arr_ret_api_call = verowa_api_call( 'getlayers', '', true );
	return $arr_ret_api_call['data'];
}




/**
 * Get the current module infos from verowa.
 * 
 * @return array
 */
function verowa_get_module_infos() {
	$arr_ret   = [];

	$from_cache = wp_cache_get( 'verowa-module-infos', 'verowa-connect' );
	if ( false === $from_cache ) {
		$arr_module_infos = verowa_api_call( 'get_module_infos', '' );
		$int_code         = intval( $arr_module_infos['code'] ?? 0 );
		if ( 200 === $int_code || 204 === $int_code ) {
			$arr_ret = $arr_module_infos['data'];
			wp_cache_set( 'verowa-module-infos', $arr_ret, 'verowa-connect', 600);
		}
	} else {
		$arr_ret = $from_cache;
	}
	
	return $arr_ret;
}