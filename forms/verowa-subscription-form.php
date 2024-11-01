<?php
/**
 * Shortcode display a subscription form.
 *
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since 2.7.0
 * @package Verowa Connect
 * @subpackage Forms
 */

use Picture_Planet_GmbH\Verowa_Connect\Verowa_Formfields_Rendering;

/**
 *
 * @param array $atts Shortcode attributes.
 *
 * @return bool|string
 */
function verowa_subscription_form( $atts ) {

	$atts = shortcode_atts(
		array(
			'event_id'        => 0,
			'show_event_info' => true,
		),
		$atts,
		'verowa_subscription_form'
	);

	$event_id                = intval( $_GET['subs_event_id'] ?? $atts['event_id'] ?? 0 );
	$atts['show_event_info'] = filter_var( $atts['show_event_info'], FILTER_VALIDATE_BOOLEAN );

	/**
	 * Verify that the request for geteventdetails was successful.
	 *
	 * @var int $int_request_status_code
	 */
	$int_request_status_code = 0;

	if ( 0 !== $event_id ) {
		$arr_ret_api_call = verowa_api_call( 'geteventdetails', $event_id, true );

		$arr_event_details       = $arr_ret_api_call['data'][0] ?? array();
		$int_request_status_code = $arr_ret_api_call['code'];

		if ( 200 === $int_request_status_code || 204 === $int_request_status_code ) {
			$ret_option  = get_option( 'verowa_subscriptions_settings' );
			$arr_options = false !== $ret_option ? json_decode( $ret_option, true ) : array();

			// This allows you to simply insert the reCaptcha codes in various places.
			$is_recaptcha_enable = false;
			if ( array_key_exists( 'recaptcha_key_public', $arr_options ) &&
				strlen( $arr_options['recaptcha_key_public'] ) > 0 &&
				array_key_exists( 'recaptcha_key_secret', $arr_options ) &&
				strlen( $arr_options['recaptcha_key_secret'] ) > 0 ) {
				$is_recaptcha_enable = true;
			}

			ob_start();
			$str_key       = isset( $_GET['key'] ) ? strval( $_GET['key'] ) : '';
			$arr_user_data = verowa_get_user_data( $str_key );

			if ( $is_recaptcha_enable ) {
					echo '<script src="https://www.google.com/recaptcha/api.js?render=' .
						esc_url( $arr_options['recaptcha_key_public'] ) . '"></script>
				<script>
					grecaptcha.ready(function() {
						grecaptcha.execute(\'' . esc_url( $arr_options['recaptcha_key_public'] ) .
					'\', {action: \'login\'}).then(function(token) {
							document.getElementById(\'recaptcha_token\').value = token;
						});
					});
				</script>';
			}

			$has_subscription = false;
			if ( isset( $arr_event_details['subs_type'] ) ) {
				if ( 'none' !== $arr_event_details['subs_type'] ) {
					$has_subscription = true;
				} else {
					if ( $arr_event_details['subs_person_id'] > 1 ||
						8 === strlen( $arr_event_details['subs_date'] ) ) {
						$has_subscription = true;
					}
				}
			}

			if ( $has_subscription ) {
				verowa_subscriptions_print_form(
					$arr_event_details,
					$arr_options,
					$is_recaptcha_enable,
					$arr_user_data,
					$atts['show_event_info'],
					$event_id,
				);
			} else {
				$str_tf = verowa_tf( 
					'This event does not exist or no longer exists.', 
					__(
					'This event does not exist or no longer exists.',
					'verowa-connect'
				));
				echo '<span class="verowa-subs-no-event" >' .
					esc_html(
						$str_tf
					) . '</span>';
			}
		} else {
			$str_tf = verowa_tf( 
				'The registration form is temporarily unavailable. Please try again later or contact the secretariat.', 
				__(
				'The registration form is temporarily unavailable. Please try again later or contact the secretariat.',
				'verowa-connect'
			));
			echo '<span class="verowa-api-error" >' .
				esc_html(
					$str_tf
				) . '</span>';
		}
	}
	return ob_get_clean();
}

/**
 * Display the subscription form for an event.
 *
 * @param array  $arr_event_details     Event details.
 * @param array  $arr_options           Options array.
 * @param bool   $is_recaptcha_enable   Whether reCAPTCHA is enabled.
 * @param array  $arr_user_data         User data.
 * @param bool   $show_event_info       Whether to show event info.
 * @param int    $event_id              ID of the event to display.
 *
 * @return void
 */
function verowa_subscriptions_print_form( $arr_event_details, $arr_options, $is_recaptcha_enable,
	$arr_user_data, $show_event_info, $event_id = 0 ) {
	global $l10n, $l10n_unloaded, $wp_textdomain_registry;

	$arr_weekdays = array(
		0 => __( 'Sun', 'verowa-connect' ) . '.',
		1 => __( 'Mon', 'verowa-connect' ) . '.',
		2 => __( 'Tue', 'verowa-connect' ) . '.',
		3 => __( 'Wed', 'verowa-connect' ) . '.',
		4 => __( 'Thu', 'verowa-connect' ) . '.',
		5 => __( 'Fri', 'verowa-connect' ) . '.',
		6 => __( 'Sat', 'verowa-connect' ) . '.',
	);

	$arr_month_kurz = array(
		'01' => 'Jan.',
		'02' => 'Feb.',
		'03' => 'März',
		'04' => 'April',
		'05' => 'Mai',
		'06' => 'Juni',
		'07' => 'Juli',
		'08' => 'Aug.',
		'09' => 'Sept.',
		'10' => 'Okt.',
		'11' => 'Nov.',
		'12' => 'Dez.',
	);

	$str_key = $_GET['k'] ?? '';
	$subs_id = intval( $_GET['si'] ?? 0 );

	$str_subscribe_date = '';

	echo '<div class="verowa-contact-tracing big-form">' . PHP_EOL .
	'<form action="" class="verowa-subscription-form" method="POST">' . PHP_EOL;

	if ( '' !== $str_key ) {
		echo '<input type="hidden" name="k" value="' . esc_attr( $str_key ) . '" />' . PHP_EOL;
	}

	if ( '' !== $subs_id ) {
		echo '<input type="hidden" name="subs_id" value="' . esc_attr( $subs_id ) . '" />' . PHP_EOL;
	}

	if ( strlen( $arr_event_details['subs_date'] ?? '' ) >= 6 ) {
		$str_subscribe_date = date_i18n( 'j', strtotime( $arr_event_details['subs_date'] ) ) . '. ' .
			$arr_month_kurz[ substr( $arr_event_details['subs_date'], 4, 2 ) ];
	}

	$arr_event_content =
		verowa_subscription_show_events(
			array(
				0 => $arr_event_details,
			),
			$arr_options,
			$event_id,
			$arr_month_kurz
		);

	$str_subscribe_time = substr( $arr_event_details['subs_time'], 0, 2 ) . '.' .
		substr( $arr_event_details['subs_time'], 2, 2 );

	$str_anmeldefrist_abgelaufen = '<strong>Die Anmeldefrist ist am ' . $str_subscribe_date;

	if ( ! empty( $arr_event_details['subs_time'] ) ) {
		$str_anmeldefrist_abgelaufen .= ', ' . $str_subscribe_time . ' Uhr';
	} else {
		$arr_event_details['subs_time'] = '2359';
	}

	$str_anmeldefrist_abgelaufen .= ' abgelaufen.</strong><br/><br/>' .
		'<a href="javascript:history.back();">' . verowa_tf( 'Back', __( 'Back', 'verowa-connect' )) . '</a>';

	// Check registration deadline (if registration deadline is set).
	switch ( $arr_event_details['subs_state'] ?? 'none' ) {
		case 'no_subs_form':
		case 'booked_up':
			$arr_event_content['is_show_form'] = false;
			break;

		case 'deadline_expired':
			$arr_event_content['is_show_form'] = false;
			$show_event_info = false;
			echo $str_anmeldefrist_abgelaufen;
			break;

		case 'subs_link':
		case 'waiting_list':
			$arr_event_content['is_show_form'] = true;
			break;
	}

	// Event Infos ausgeben.
	if ( true === $show_event_info ) {
		echo $arr_event_content['content'];
	}

	if ( false === $show_event_info ) {
		echo '<input type="hidden" name="event_id" value="' . esc_attr( $event_id ) . '" />';
	}

	// Fields all go into this array.
	$arr_formfields     = array();
	$str_subs_form_text = isset( $arr_event_details['subs_form']['subs_form_text'] ) ?
		'<p class="verowa-subs-form-text">' . $arr_event_details['subs_form']['subs_form_text'] . '</p>' : '';

	if ( $arr_event_content['is_show_form'] ) {
		$arr_formfields = $arr_event_details['subs_form']['formfields'] ?? array();
		echo '<div class="verowa-form ct-form">' .
			'<div class="verowa-block ct_block">';

		echo $str_subs_form_text;
		$obj_rf_wrapper = new Verowa_Related_Field_Wrapper();

		// Output of the form fields.
		foreach ( $arr_formfields as $arr_single_formfield ) {

			$field_id                               = $arr_single_formfield['field_id'];
			$str_error_msg                          = key_exists( 'arr_errors', $arr_user_data ) &&
				key_exists( $field_id, $arr_user_data['arr_errors'] ) ? $arr_user_data['arr_errors'][ $field_id ] : '';
			$arr_single_formfield['str_error_msg'] = $str_error_msg;

			$obj_formfields = new Verowa_Formfields_Rendering( $arr_single_formfield );
			if ( isset( $arr_user_data['arr_post'] ) ) {
				$obj_formfields->set_field_value( $arr_user_data['arr_post'] );
			}

			// If another person is entered in the registration form,
			// then the values from the post must be taken over directly.
			if ( isset( $_POST['subscription_back_button'] ) ) {
				$obj_formfields->set_field_value( $_POST );
			}

			$obj_rf_wrapper->show_wrapper( $arr_single_formfield );
			$obj_formfields->show_formfield_html();
		}

		$obj_rf_wrapper->close_open_wrapper();

		echo '<div><i>' . esc_html(
			__(
				'Fields marked with an asterisk (*) are mandatory.',
				'verowa-connect'
			)
		) .
			'</i></div>';

		echo '</div>'; // ct_block.

		echo '<div class="verowa-block nl_block">';
		echo '<div class="row">';
		echo '<div class="column">';
		// is in use.
		echo '<input type="hidden" name="subs_template_id" value="' .
			esc_attr( $arr_event_details['subscribe_person_id'] ) . '" /> ';
		$str_tf = verowa_tf( 'Send', __( 'Send', 'verowa-connect' ) );
		echo '<div class="verowa-submit-wrapper"><input id="verowa_subs_form_submit" type="submit" name="submit_button" value="' .
			esc_attr( $str_tf ) . '" /></div> ';

		echo '</div>';
		echo '</div>'; // end Row.
		echo '</div>';
		echo '</div>'; // end .nl_subscribe.
		wp_nonce_field( 'send_infos', 'send_infos' );
		if ( $is_recaptcha_enable ) {
			echo '<input type="hidden" name="recaptcha_response" id="recaptchaResponse" />';
			echo ' <input type="hidden" name="recaptcha_token" id="recaptcha_token" />';
		}

		echo '</form>';
		echo '</div>';

		$obj_rf_wrapper->print_js();
	} // end if Show Form

}
