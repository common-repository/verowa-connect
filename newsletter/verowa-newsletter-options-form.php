<?php
/**
 * Here's the expanded newsletter form that can be integrated using [verowa_newsletter_big_form].
 * 
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since 3.0.0
 * @package Verowa Connect
 * @subpackage Newsletter
 */

/**
 * This function generates a large newsletter form that 
 * can be integrated using the shortcode [verowa_newsletter_options_form].
 *
 * @param mixed $atts Attributes for customizing the form. Default attributes include:
 *                    - 'fields': Field labels section (Default: 'Allgemeine Angaben:')
 *                    - 'target_lists': Interests section (Default: 'Ich bin interessiert an:')
 *                    - 'interval': Frequency of newsletter selection section (Default: 'So oft möchte ich den Newsletter erhalten:')
 *
 * @return bool|string Returns either a string representation of the newsletter form or a boolean value based on the success of the operation.
 */
function verowa_newsletter_options_form( $atts ) {
	$atts = shortcode_atts( array(
        'fields'       => __( 'General Information:', 'verowa-connect' ),
        'target_lists' => __( 'I am interested in:', 'verowa-connect' ),
        'interval'     => __( 'How often would I like to receive the newsletter:', 'verowa-connect' ),
	), $atts, 'verowa_newsletter_options_form' );

	// Standard data also from Verowa Connect
	$verowa_api_key = get_option( 'verowa_api_key', false );
	$verowa_member = get_option( 'verowa_instance', false );
	$verowa_api_url = 'https://api.verowa.ch';
	$get_form_method = 'getnlsubscriberfrom';
	$send_form_method = 'updatenlsubscriber';
	$postfix = '';

	 // Postfix, what are we actually trying...
	if ( isset( $_GET['email'] ) ) {
		$postfix = sanitize_email( $_GET['email'] );
	}

	if ( isset( $_GET['key'] ) ) {
		$postfix = sanitize_text_field( $_GET['key'] );
	}

	ob_start();

	if ( strlen( $postfix ) == 0 ) {
        echo '<p class="mb-5">' . __( 'Thank you for your interest in our offers.', 'verowa-connect' ) . 
			'<br/>' . __( 'Please enter your email address first.', 'verowa-connect' ) . '</p>'; 
		echo do_shortcode( '[verowa_newsletter_small_form]' );
	} else {

		 // Prepare API URLs
		$subscription_get_url = $verowa_api_url . '/' . $get_form_method . '/' . $verowa_member . '/' . $verowa_api_key . '/' . $postfix;
		$subscription_send_url = $verowa_api_url . '/' . $send_form_method;

		// Changes to the form
		if ( isset( $_POST['change_subscription'] ) && wp_verify_nonce( $_POST['change_subscription'], 'change_subscription' ) ) {
			$arr_subscription_data = array();
			foreach ( $_POST as $key => $value ) {
				$arr_subscription_data[ $key ] = $value;
			}

			$response = wp_remote_post( $subscription_send_url, array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array( 'member' => $verowa_member, 'apikey' => $verowa_api_key ),
				'body' => json_encode( $arr_subscription_data, true ),
				'cookies' => array()
			) );
			$body = json_decode( $response['body'], true );

			// Check the response code
			$response_code = wp_remote_retrieve_response_code( $response );
			$response_message = wp_remote_retrieve_response_message( $response );


			if ( 200 != $response_code && ! empty( $response_message ) ) {
				echo $response_message;
			} else if ( 200 != $response_code ) {
				echo __( 'An unknown error has occurred.', 'verowa-connect' );
			} else if ( is_numeric( $body ) ) {
				if ( $arr_subscription_data['nl_subs_id'] == 0 ) {
					echo __( 'Your registration has been added. Thank you for your interest.', 'verowa-connect' );
				} else {
					echo __( 'Your newsletter registration has been changed.', 'verowa-connect' ); 
				}
			} else if ( isset( $body['message'] ) ) {
				echo $body['message'];
			} else {
				echo __( 'Your newsletter registration was not changed, the API is not operational.', 'verowa-connect' );
			}
		} else {
			$response = wp_remote_get( $subscription_get_url );
			$body = json_decode( $response['body'], true );

			// Check the response code
			$response_code = wp_remote_retrieve_response_code( $response );
			$response_message = wp_remote_retrieve_response_message( $response );

			if ( 200 != $response_code && ! empty( $response_message ) ) {
				echo $response_message;
			} else {
				if ( 200 != $response_code ) {
					echo __( 'An unknown error has occurred.', 'verowa-connect' );
				} else {
					if ( isset( $body['message'] ) ) {
						echo $body['message'];
					} else {

						// Now building the form if nothing from the previous actions executed.
						echo '<div class="verowa-newsletter big-form">';
						echo '<form action="" method="POST">';
						echo '<div class="nl-subscribe">';
						echo '<input type="hidden" name="nl_subs_id" value="' . $body['nl_subs_id'] . '" />';

						echo '<h4>' . $atts['fields'] . '</h4>';

						echo '<div class="nl_block verowa-default-block">';
						foreach ( $body['nl_fields'] as $field ) {
							echo '<div class="row">';
							echo '<div class="column"><label for="' . $field['input_name'] . '">' . $field['label'] . ':</label></div>';
							echo '<div class="column">';

							if ( isset( $_GET['email'] ) && $field['input_name'] == 'email' ) {
								$field_value = 'value="' . $postfix . '" readonly';
							} else {
								$field_value = 'value="' . $field['value'] . '"';
							}
							echo '<input type="text" name="' . $field['input_name'] . '" ' . $field_value . '  ';
							if ( $field['required'] == 'required' )
								echo ' required="required" ';
							echo ' /></div>';
							echo '</div>'; // end row
						}

						echo '</div>';
						echo '<h4>' . $atts['target_lists'] . '</h4>';
						echo '<div class="nl_target_lists">';
						foreach ( $body['nl_target_lists'] as $field ) {
							echo '<div class="row">';
							echo '<div class="column"><input type="checkbox" name="' . $field['input_name'] . '" value="' . $field['value'] . '" ';
							if ( $field['checked'] == 'checked' )
								echo ' checked="checked" ';
							echo ' />';
							echo ' <label for="' . $field['input_name'] . '">' . $field['public_name'] . '</label></div>';
							echo '</div>'; // end row
						}

						echo '</div>';
						echo '<div class="nl_block intervall">';
						echo '<div class="row"><div class="column">';
						echo '<label for="nl_interval"></label>' . $atts['interval'] . '</div><div class="column">';
						echo '<select name="nl_interval">';
						foreach ( $body['nl_interval'] as $field ) {
							echo '<option value="' . $field['value'] . '" ';
							if ( $field['selected'] == 'selected' ) {
								echo ' selected="selected" ';
							}
							echo ' >';
							echo $field['public_name'];
							echo '</option>';
						}

						echo '</select></div></div></div>';
						echo '<div class="nl_block">';
						echo '<div class="row">';
						echo '<div class="column">';
						if ( $body['nl_subs_id'] == 0 ) {
							echo '<input type="submit" name="change_button" value="' . __('Subscribe', 'verowa-connect') . '" /> ';
						} else {
							echo '<input type="submit" name="change_button" value="' . __('Change', 'verowa-connect') . '" /> ';
							echo '<input type="submit" name="delete_button" value="' . __('Delete', 'verowa-connect') . '" /> ';
						}
						echo '</div>';
						echo '</div>'; // end Row
						echo '</div>'; // end .nl_subscribe
						wp_nonce_field( 'change_subscription', 'change_subscription' );
						echo '</form>';
						echo '</div>';
					}
				}
			}
		}
	}

	return ob_get_clean();
}

?>