<?php
/**
 * Get the event details from the DB, if it is a subscription we will get the
 * actual data from the API and return it as JSON.
 *
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @package Verowa Connect
 * @subpackage Events
 */

/**
 * Return the requested event details as JSON
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string
 */
function verowa_event_details_json( $atts ) {

	$atts = shortcode_atts(
		array(
			'id' => 0,
		),
		$atts,
		'verowa_event_details_json'
	);

	$int_id  = intval( $atts['id'] );
	$str_ret = '';

	if ( $int_id > 0 ) {
		$arr_event_details = verowa_event_db_get_content( $atts['id'] );

		$subs_person_id = intval( $arr_event_details['subs_person_id'] ?? 0 );

		// Is it a subscription, we get the current date from the API.
		if ( $subs_person_id > 0 ) {
			$arr_ret_api_call        = verowa_api_call( 'geteventdetails', $int_id, true );
			$arr_event_details       = $arr_ret_api_call['data'][0];
			$int_request_status_code = $arr_ret_api_call['code'];
		}

		// Remove old fields.
		unset( $arr_event_details['subscription_module_active'] );
		unset( $arr_event_details['subscribe_date'] );
		unset( $arr_event_details['subscription_text'] );
		unset( $arr_event_details['subscribe_person_id'] );
		unset( $arr_event_details['subscription_type'] );
		unset( $arr_event_details['subscribe_form'] );
		unset( $arr_event_details['subscribe_time'] );

		$str_ret = wp_json_encode( $arr_event_details );
	}

	return $str_ret;
}
