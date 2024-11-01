<?php
/**
 * Function collection
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since VC 2.14.0
 * @package Verowa Connect
 * @subpackage Functions
 */

/**
 * Shortcode to display the confirmation message after submitting the rental form
 * @return bool|string
 */
function print_verowa_renting_response() {
	// phpcs:ignore
	$str_key = isset( $_GET['key'] ) ? strval( $_GET['key'] ) : '';
	$arr_user_data = verowa_get_user_data( $str_key );
	$str_message = $arr_user_data['message'] ?? '';
	$str_message = '<p>' . $str_message . '</p>';

	return $str_message;
}




/**
 *
 * Shortcode for the verification of a renting request
 * @return bool|string
 */
function verowa_renting_validate() {

	ob_start();

	$verowa_api_key = get_option( 'verowa_api_key', false );
	$verowa_member = get_option( 'verowa_instance', false );

	// First it is checked whether the option was found and then whether a value was entered.
	if ( false === empty( $verowa_api_key ) && false === empty( $verowa_member ) ) {
		// Standard data from verowa connect.
		$verowa_api_url = 'https://api.verowa.ch';
		$send_form_method = 'validate_renting_request';

		$str_send_url = $verowa_api_url . '/' . $send_form_method;

		if ( $_GET['rk'] && $_GET['ri'] ) {
			$request_id = isset( $_GET['ri'] ) ? intval( $_GET['ri'] ) : 0;
			$request_key = isset( $_GET['rk'] ) ? sanitize_text_field( $_GET['rk'] ) : '';

			$validate_response = wp_remote_post(
				$str_send_url,
				array(
					'method' => 'POST',
					'timeout' => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking' => true,
					'headers' => array(
						'member' => $verowa_member,
						'apikey' => $verowa_api_key,
					),
					'body' => wp_json_encode(
						array(
							'request_id' => $request_id,
							'request_key' => $request_key,
						),
						true
					),
					'cookies' => array(),
				)
			);

			$validate_body = json_decode( $validate_response['body'], true );

			// Check the validate_response code
			// for later testing purpose.
			$validate_response_code = wp_remote_retrieve_response_code( $validate_response );
			$validate_response_message = wp_remote_retrieve_response_message( $validate_response );

			echo $validate_body['message'];

		}
	}
	return ob_get_clean();
}




/**
 * Shortcode to display the feedback to the user
 *
 * @return bool|string
 */
function verowa_subscription_confirmation() {
	ob_start();

	$str_key        = strval( $_GET['key'] ?? '' );
	$arr_user_data  = verowa_get_user_data( $str_key );
	$str_response   = $arr_user_data['response'] ?? $arr_user_data['message'] ?? '';
	$str_subs_state = $arr_user_data['subs_state'] ?? '';

	echo '<p>' . $str_response . '</p>';

	if ( 'refine' ==  $str_subs_state ) {
		$str_tf = verowa_tf( 'back to the form', __( 'back to the form', 'verowa-connect' ) );
		echo '<p><br /><a href="javascript:history.back();">' .
			esc_html( $str_tf ) .
			'</a></p>';
	}

	if ( ! empty( $arr_user_data['subs_formfields'] ) ) {
		$str_back_button = verowa_subscriptions_backform( $arr_user_data, $arr_user_data['subs_data'] );
		echo $str_back_button;
	}

	$event_id = intval( $arr_user_data['subs_data']['event_id'] ?? 0 );
	if ( $event_id > 0 ) {
		$obj_update = new Verowa_Update_Controller();
		$obj_update->init( 'single_event', $event_id );
		$obj_update->update_verowa_events_in_db( $event_id );
	}
	return ob_get_clean();
}




/**
 * Shortcode handler to validate a subscription an show the result to the user.
 *
 * @return bool|string
 */
function verowa_subscription_validation() {
	ob_start();

	$ret_api_check  = verowa_check_member_api_key();
	$verowa_api_key = get_option( 'verowa_api_key', '' );
	$verowa_member  = get_option( 'verowa_instance', '' );

	// First it is checked whether the option was found and then whether a value was entered.
	if ( true === $ret_api_check->validity ) {
		$verowa_api_url   = 'https://api.verowa.ch';
		$send_form_method = 'validate_event_subscription';
		$str_send_url     = $verowa_api_url . '/' . $send_form_method;

		if ( ( $_GET['k'] ?? false ) && ( $_GET['subs'] ?? false ) ) {
			$subs_key    = sanitize_text_field( $_GET['k'] ?? '' );
			$subs_id     = sanitize_text_field( $_GET['subs'] ?? '' );
			$plugin_data = verowa_connect_get_plugin_data();

			$validate_response = wp_remote_post(
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
					'body'        => json_encode(
						array(
							'subs_key'       => $subs_key,
							'subs_id'        => $subs_id,
							'plugin_version' => $plugin_data['Version'] ?? '0.0.0',
						),
						true
					),
					'cookies'     => array(),
				)
			);

			$validate_body = json_decode( $validate_response['body'], true );

			// Check the validate_response code.
			$validate_response_code    = wp_remote_retrieve_response_code( $validate_response );
			$validate_response_message = wp_remote_retrieve_response_message( $validate_response );

			if ( 200 !== intval( $validate_response_code ) ) {
				if ( ! empty( $validate_response_message ) ) {
					echo $validate_response_message;
				} else {
					echo esc_html( __( 'An unhandled error occurred.', 'verowa-connect' ) );
				}
			} else {
				echo $validate_body['message'] . '</br>';
				$int_event_id = $validate_body['event_id'] ?? 0;
				// Update event in DB.
				if ( $int_event_id > 0 ) {
					$obj_update = new Verowa_Update_Controller();
					$obj_update->init( 'single_event', $int_event_id );
					$obj_update->update_verowa_events_in_db( $int_event_id );
				}
			}
		} else {
			if ( isset( $_GET['key'] ) ) {
				$str_key = strval( $_GET['key'] ?? '' );
				$arr_user_data = verowa_get_user_data( $str_key );
				$str_response = $arr_user_data['response'] ?? $arr_user_data['message'] ?? '';
				$str_subs_state = $arr_user_data['subs_state'] ?? '';

				echo '<p>' . $str_response . '</p>';

				if ( 'refine' === $str_subs_state ) {
					$str_tf = verowa_tf( 'back to the form', __( 'back to the form', 'verowa-connect' ) );
					echo '<p><br /><a href="javascript:history.back();">' .
						esc_html( $str_tf ) .
						'</a></p>';
				}
			} else {
				echo '<span class="verowa-connect-error" >' .
					'Die Anmeldung kann nicht validiert werden, da die Anmelde-ID oder deren Schlüssen fehlt.' .
					'</span>';
			}
		}

		$event_id = intval( $validate_body['event_id'] ?? 0 );
		if ( $event_id ) {
			$obj_update = new Verowa_Update_Controller();
			$obj_update->init( 'single_event', $event_id );
			$obj_update->update_verowa_events_in_db( $event_id );
		}
	} else {
		echo $ret_api_check->content;
	}

	return ob_get_clean();
}