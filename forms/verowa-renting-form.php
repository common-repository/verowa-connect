<?php
/**
 * Shortcode to display the renting from
 *
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since 2.2.0
 * @package Verowa Connect
 * @subpackage Forms
 */

use Picture_Planet_GmbH\Verowa_Connect\Verowa_Formfields_Rendering;


/**
 * Rendering of the renting Form
 *
 * @param array $atts Shortcode attributes.
 *
 * @return bool|string
 */
function verowa_room_renting_form( $atts ) {
	$atts = shortcode_atts(
		array(
			'form_id' => 0,
		),
		$atts,
		'verowa_renting_form'
	);

	// Standard data from Verowa Connect.
	$verowa_api_key = get_option( 'verowa_api_key', false );
	$verowa_member  = get_option( 'verowa_instance', false );

	$ret_check = verowa_check_member_api_key();
	ob_start();

	if ( $ret_check->validity ) {
		$verowa_api_url = 'https://api.verowa.ch';
		$int_form_id    = $atts['form_id'];
		$plugin_data    = verowa_connect_get_plugin_data();

		$str_get_url = $verowa_api_url . '/get_renting_form_fields/' . $verowa_member . '/' . $verowa_api_key . '/' .
			$int_form_id . '/?v=' . $plugin_data['Version'];

		// Get form fields.
		$response = wp_remote_get( $str_get_url );
		$body     = json_decode( $response['body'], true );

		$arr_renting_formfields = $body['arr_form_fields'];
		$str_key                = isset( $_GET['key'] ) ? strval( $_GET['key'] ) : '';
		$arr_user_data          = verowa_get_user_data( $str_key );

		if ( 200 === intval( $body['http_status'] ?? 0 ) ) {
			echo '<div>';
			echo '<form action="" method="POST">';
			echo '<input type="hidden" name="form_id" value="' . esc_attr( $int_form_id ) . '" />';

			if ( count( $arr_renting_formfields ) > 0 ) {
				echo '<div class="verowa-renting-formfields renting-formfields" style="padding: 15px 0px;">';
				$obj_rf_wrapper = new Verowa_Related_Field_Wrapper();

				foreach ( $arr_renting_formfields as $arr_single_formfield ) {

					$obj_formfields = new Verowa_Formfields_Rendering( $arr_single_formfield );
					// Handling off error is in the class.
					if ( key_exists( 'arr_errors', $arr_user_data ) ) {
						$obj_formfields->error_handler( $arr_user_data['arr_errors'] );
					}

					if ( isset( $arr_user_data['arr_post'] ) ) {
						$obj_formfields->set_field_value( $arr_user_data['arr_post'] );
					}

					$obj_rf_wrapper->show_wrapper( $arr_single_formfield );

					$obj_formfields->show_formfield_html();
				}

				echo '</div>';
			}

			$obj_rf_wrapper->close_open_wrapper();
			$obj_rf_wrapper->print_js();
		}

		echo '<div class="verowa_renting_form_submit vc_renting_form_submit" >' .
			'<i>' . __(
				'Fields marked with an asterisk (*) are mandatory.',
				'verowa-connect'
			) . '</i>';
		echo '<div class="verowa-submit-wrapper"><input type="submit" id="verowa_renting_form_submit" name="verowa_renting_form_submit" value="senden" /></div></div>';

		echo '</form>';
		echo '</div>';

		
	} else {
		// api key or member not set.
		echo $ret_check->content;
	}

	return ob_get_clean();
}
