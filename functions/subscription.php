<?php
/**
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since 2.4.1
 * @package Verowa Connect
 * @subpackage Functions
 */



/**
 * Display a list of all public events with subscriptions.
 *
 * @param array $arr_events      An array containing information about events with subscriptions.
 * @param array $arr_options     An array of options for displaying the events.
 * @param int   $event_id        The ID of the current event.
 * @param array $arr_month_kurz  An array of abbreviated month names.
 * @return array                 An array containing the HTML content to display the events and a boolean
 *                               indicating whether to show the subscription form.
 */
function verowa_subscription_show_events( $arr_events, $arr_options, $event_id, $arr_month_kurz ) {
	$is_show_form = true;

	// Displayed to the user, can contain an even list, a single event or an info.
	$str_content = '<div class="verowa-subscription-form-div nl-subscrib">';

	// Events sind im body drin.
	$str_today10      = date_i18n( 'Y-m-d' );
	$radiofield_count = 0;
	$str_event_line   = '';

	$arr_events_today = is_array( $arr_events ) ? $arr_events : array();

	if ( 0 === count( $arr_events_today ) ) {
		if ( 'on' === $arr_options['only_today'] ) {
			$str_tf = verowa_tf( 'There are no public events taking place today.', __(
				'There are no public events taking place today.',
				'verowa-connect'
			) );
			$str_content .= '<strong>' . $str_tf . '</strong>';
			$is_show_form = false;
		} else {
			$str_tf = verowa_tf( 'There will be no public events in the next few days.', __(
				'There will be no public events in the next few days.',
				'verowa-connect'
			) );
			$str_content .= '<strong>' . $str_tf . '</strong>';
			$is_show_form = false;
		}
	} else {
		$str_only_today     = $arr_options['only_today'] ?? 'off';
		$events_today_exist = key_exists( 'date_from', $arr_events_today ) &&
			substr( $arr_events_today['date_from'], 0, 10 ) == $str_today10 ? true : false;
		if ( 'on' === $str_only_today && false === $events_today_exist ) {
			$str_tf = verowa_tf( 'There are no public events taking place today.', __( 'There are no public events taking place today.', 'verowa-connect' ) );
			$str_content .= '<strong>' . esc_html(
				$str_tf
			) . '</strong>';
			$is_show_form = false;
		}
	}

	if ( $is_show_form ) {
		$str_content .= '<div class="verowa-event-wrapper ct-events">';

		$str_curr_event_date = '';

		foreach ( $arr_events_today as $single_event ) {
			$arr_template_options = $single_event['subs_form']['template_options'] ?? array();
			$is_ignore_seats      = filter_var( $arr_template_options['ignore_seats'] ?? false, FILTER_VALIDATE_BOOLEAN );

			$str_topic = strlen( $single_event['topic'] ) === 0 ? '' : '<p class="verowa-event-topic">' . $single_event['topic'] . '</p>';

			if ( isset( $arr_options['event_detail_link'] ) && '' !== $arr_options['event_detail_link']
				&& 'on' == $arr_options['show_event_link'] ) {
				$str_event_link = str_replace(
					'{%event_id%}',
					$single_event['event_id'],
					$arr_options['event_detail_link']
				);
				$str_content   .= '<a href="' . $str_event_link . '"><h2>' . $single_event['title'] . '</h2></a>' .
					$str_topic . '<p class="verowa-subs-date" >' . $single_event['date_text'] . '</p>';
			} else {
				$str_content .= '<h2>' . $single_event['title'] . '</h2>' . $str_topic .
				'<p class="verowa-subs-date" >' . $single_event['date_text'] . '</p>';
			}

			$str_field_id = 'event_' . ( $radiofield_count++ );

			$str_new_event_date = date_i18n( 'j. M. Y', strtotime( $single_event['date_from'] ) );

			$str_event_date_from8 = substr( $single_event['date_from'], 0, 4 ) .
				substr( $single_event['date_from'], 5, 2 ) . substr( $single_event['date_from'], 8, 2 );

			$str_date_today8 = date_i18n( 'Ymd' );

			$bool_anmeldung_date = false;
			$str_anmelde_schluss = '';

			// Output registration deadline if set.
			if ( '0' != $single_event['subscribe_date'] && '' != $single_event['subscribe_date'] ) {
				$bool_anmeldung_date = true;
				$str_tf = verowa_tf( 'Subscription', __( 'Subscription', 'verowa-connect' ) );
				$str_event_line      = $str_tf . ' ';
				$str_anmelde_schluss = '<span style=" white-space: nowrap;">' .
					__( 'until', 'verowa-connect' ) . ' ';
				$str_subs_time       = '';
				$str_subs_date       = date_i18n( 'j', strtotime( $single_event['subscribe_date'] ) ) . '. ' .
					$arr_month_kurz[ substr( $single_event['subscribe_date'], 4, 2 ) ];

				if ( '' != $single_event['subscribe_time'] ) {
					$str_subs_time = intval( substr( $single_event['subscribe_time'], 0, 2 ) ) . '.' .
						substr( $single_event['subscribe_time'], 2, 2 ) . ' ' . __( 'o’clock', 'verowa-connect' );

					if ( $single_event['subscribe_date'] == $str_date_today8 ) {
						$str_anmelde_schluss .= $str_subs_time . '</span>';
					} else {
						$str_anmelde_schluss .= $str_subs_date . ', ' . $str_subs_time . '</span>';
					}
				} else {
					// If the date is equal to the event's start date, we only display the time.
					if ( $single_event['subscribe_date'] == $str_event_date_from8 ) {
						$str_anmelde_schluss = '';
					} else {
						$str_anmelde_schluss .= $str_subs_date . '</span>';
					}
				}
			}

			if ( false === $is_ignore_seats ) {
				$int_seats_total = ( $single_event['seats_online'] ?? 0 ) + ( $single_event['seats_entrance'] ?? 0 );

				$str_seats = '';

				$str_hide_bookable_seats = $arr_options['hide_bookable_seats'] ?? 'off';
				$str_hide_free_seats     = $arr_options['hide_free_seats'] ?? 'off';
				$str_seats_online        = intval( $arr_options['seats_online'] ?? -1 );

				if ( 'on' !== $str_hide_bookable_seats && -1 != $str_seats_online ) {
					if ( 'on' !== $str_hide_free_seats ) {
						$str_seats = ', reservierbar';
					} else {
						$str_seats = verowa_tf( 'reservable seats', __( 'reservable seats', 'verowa-connect' ) );
					}

					$str_seats .= ': ' . $single_event['seats_online'];
				}

				if ( 'on' !== ( $arr_options['hide_free_seats'] ?? 'off' ) ) {
					if ( $int_seats_total > 0 ) {
						$str_tf = verowa_tf( 'free seats:', __( 'free seats:', 'verowa-connect' ) );
						$str_seats = $str_tf . ' ' . $int_seats_total . $str_seats;
					} elseif ( 0 === $int_seats_total ) {
						$str_tf = verowa_tf( 'free seats:', __( 'free seats:', 'verowa-connect' ) );
						$str_seats = $str_tf . ' 0' . $str_seats;
					} elseif ( 'on' !== ( $arr_options['hide_numbers_when_infinite'] ?? 'off' ) ) {
						$str_tf = verowa_tf( 'free seats: unlimited', __( 'free seats: unlimited', 'verowa-connect' ) );
						$str_seats = $str_tf;
					}
				}
			}

			if ( 'on' === ( $arr_options['only_today'] ?? 'off' ) ) {
				if ( substr( $single_event['date_from'], 0, 10 ) == $str_today10 ) {
					if ( $str_new_event_date != $str_curr_event_date ) {
						$str_content        .= ' ' . $str_new_event_date . '';
						$str_curr_event_date = $str_new_event_date;
					}

					$str_content .= verowa_subscriptions_show_days(
						$str_field_id,
						$str_event_line,
						$str_seats,
						$single_event,
						$str_anmelde_schluss,
						$bool_anmeldung_date
					);
				}
			} else {
				if ( $str_new_event_date != $str_curr_event_date ) {
					$str_curr_event_date = $str_new_event_date;
				}

				$str_content .= verowa_subscriptions_show_days(
					$str_field_id,
					$str_event_line,
					$str_seats,
					$single_event,
					$str_anmelde_schluss,
					$bool_anmeldung_date
				);
			}
		} // foreach

		$str_content .= '</div>';
	}

	return array(
		'content'      => $str_content,
		'is_show_form' => $is_show_form,
	);
}


/**
 * Helper function to generate HTML content for displaying subscription details for an event.
 *
 * @param  string $str_field_id        The field ID for the event.
 * @param  string $str_event_line      A line of text related to the event.
 * @param  string $str_seats           Information about available seats.
 * @param  array  $arr_event           An array containing information about the event.
 * @param  string $str_anmelde_schluss A string representing the registration deadline.
 * @param  bool   $bool_anmeldung_date  A boolean indicating whether the registration date exists.
 * @return string                      The generated HTML content for displaying subscription details.
 */
function verowa_subscriptions_show_days( $str_field_id, $str_event_line, $str_seats, $arr_event,
	$str_anmelde_schluss, $bool_anmeldung_date ) {
	$snd_subs_block = '';
	$str_subs_state = $arr_event['subs_state'] ?? 'none';

	$str_ret = '<div class="verowa-block ct_block">';

	$str_time_now = date_i18n( 'Hi' );
	$str_date_now = date_i18n( 'Ymd' );

	$bool_subs_time_elapsed = false;
	$bool_allow_waitinglist = filter_var(
		$arr_event['subs_form']['template_options']['allow_waitinglist'] ??
		false,
		FILTER_VALIDATE_BOOLEAN
	);

	if ( ! empty( $arr_event['subscribe_date'] ) && ! empty( $arr_event['subscribe_time'] ) ) {
		if ( intval( $arr_event['subscribe_date'] ) < intval( $str_date_now ) ) {
			$bool_subs_time_elapsed = true;
		} elseif ( intval( $arr_event['subscribe_date'] ) == intval( $str_date_now ) &&
			intval( $arr_event['subscribe_time'] ) < intval( $str_time_now ) ) {

			$bool_subs_time_elapsed = true;
		}
	} elseif ( ! empty( $arr_event['subscribe_date'] ) ) {
		if ( intval( $arr_event['subscribe_date'] ) < intval( $str_date_now ) ) {
			$bool_subs_time_elapsed = true;
		}
	}

	$str_ret .= '<div class="row">';
	$str_ret .= '<input type="hidden" name="event_id" id="' . $str_field_id . '"' .
			' value="' . $arr_event['event_id'] . '" />';
	$str_ret .= $str_event_line;
	$str_ret .= $str_anmelde_schluss;

	if ( 'no_subs_form' === $str_subs_state ) {
		$str_text = $arr_event['subs_form_no_subs_form_text'] ?? '';
		if ( '' !== $str_text ) {
			$snd_subs_block = '<div class="subs_block mt-1 subs-no-form-text"><strong>' . $str_text . '</strong></div>';
		}
	}

	if ( 0 == $arr_event['seats_entrance'] && 0 == $arr_event['seats_online'] ) {
		if ( $bool_allow_waitinglist ) {
			$str_text = $arr_event['subs_form']['template_options']['subs_form_waitinglist_text'] ?? '';
			if ( '' != $str_text ) {
				$snd_subs_block = '<div class="subs_block mt-1 subs-waitinglist-text"><strong>' . $str_text . '</strong></div>';
			}
		} else {
			$str_text = $arr_event['subs_form']['template_options']['subs_form_booked_up_text'] ?? '';
			if ( '' != $str_text ) {
				$snd_subs_block = '<div class="subs_block mt-1 subs-booked-up-text"><strong>' . $str_text . '</strong></div>';
			}
		}
	} elseif ( true == $bool_subs_time_elapsed ) {
		$str_tf = verowa_tf( 'Registration deadline expired', __( 'Registration deadline expired', 'verowa-connect' ) );
		$str_ret .= ' (' . $str_tf . ')';
	} elseif ( strlen( $str_seats ) > 0 ) {
		$str_ret .= ' (' . $str_seats . ')';
	}

	if ( ( ( strlen( $arr_event['subscribe_date'] ) > 0 && true == $bool_subs_time_elapsed ) ||
		0 == $arr_event['seats_online'] )
		&& $arr_event['seats_entrance'] > 0 ) {

		$str_ret .= ' – ' . verowa_tf( 'Only spontaneous visits possible', __( 'Only spontaneous visits possible', 'verowa-connect' ) );
	}

	$str_ret .= '</div></div>';

	return $str_ret . $snd_subs_block;
}




/**
 * Generates the HTML content for a subscription form to go back and enter more persons.
 *
 * @param  array $arr_user_data The user data from the subscription form.
 * @param  array $arr_form_data The form data.
 * @return string               The HTML content for the subscription form to go back.
 */
function verowa_subscriptions_backform( $arr_user_data, $arr_form_data ) {
	$str_return             = '';
	$arr_formfields_allowed = array();
	$str_rollover           = '';
	$str_disabled           = '';
	$str_class              = 'subscription-back-button-enabled';

	// Array with all fields which are to be filled again.
	$arr_formfields_allowed[0]['subs_form']['formfields'] =
		verowa_subscriptions_get_formfields_for_back( $arr_user_data['subs_formfields'] );

	$arr_cf_refined = $arr_formfields_allowed[0]['subs_form']['formfields'] ?? '';

	$str_return .= '<form action="https://' . ( $_SERVER['HTTP_HOST'] ?? '' ) . '/subscription-form/?subs_event_id=' .
		$arr_form_data['event_id'] . '"  method="POST">';

	foreach ( $arr_cf_refined as $single_field ) {
		if ( true === key_exists( $single_field['field_name'] ?? '', $arr_form_data ) &&
			! is_array( $arr_form_data[ $single_field['field_name'] ?? '' ] ) ) {
			$str_return .= '<input type="hidden" name="' . $single_field['field_name'] . '" ' .
				'value="' . $arr_form_data[ $single_field['field_name'] ] . '"/>';
		} else {
			if ( key_exists( $single_field['field_name'], $arr_form_data ) ) {
				foreach ( $arr_form_data[ $single_field['field_name'] ] as $single_checkbox ) {
					$str_return .= '<input type="hidden" name="' . $single_field['field_name'] . '[]" ' .
						'value="' . $single_checkbox . '"/>';
				}
			}
		}
	}

	if ( strlen( $str_return ) > 0 && count( $arr_cf_refined ) > 0 ) {
		if ( false === $arr_user_data['back_to_form'] ) {
			$str_rollover = $arr_user_data['response'];

			$str_class    = 'subscription-back-button-disabled';
			$str_disabled = 'disabled';
		}

		// A check is made for the name of the button.
		$str_tf = verowa_tf( 'enter more persons', __( 'enter more persons', 'verowa-connect' ) );
		$str_return .= '<br /><span title="' . $str_rollover . '"><button name="subscription_back_button" class="' .
			$str_class . '" ' . $str_disabled . ' id="subscription-back-button">' .
			esc_html( $str_tf ) . '</button></span>';
	}

	$str_return .= '</form>';
	return $str_return;
}




/**
 * Get the form fields for the subscription form to go back and enter more persons.
 *
 * @param  array $arr_formfields The array of form fields.
 * @return array                 An array containing the form fields allowed for going back.
 */
function verowa_subscriptions_get_formfields_for_back( $arr_formfields ) {
	$arr_formfields_allowed = array();

	foreach ( $arr_formfields as $single_formfield ) {
		if ( true == is_array( $single_formfield ) && false !== strpos( $single_formfield['options'], '==' ) ) {
			$arr_formfields_allowed[] = $single_formfield;
		}
	}

	return $arr_formfields_allowed;
}




/**
 * Separate the subscription label short title.
 *
 * @param  string $str_label The subscription label.
 * @return array            An array containing the separated short title pieces.
 */
function verowa_separate_subs_label_shorttitle( $str_label ) {
	$arr_title_pieces = array();

	// If it has a short title, it always ends with "}}". Let's remove that.
	$str_label = substr( $str_label, 0, -2 );

	$arr_title_pieces = explode( '{{', $str_label );

	return $arr_title_pieces;
}




/**
 * Create subscription-related WordPress pages if they don't exist.
 */
function verowa_create_subscriptions_pages() {
	// UNDONE: Add ML Support
	$arr_verowa_module_infos = get_option( 'verowa_module_infos', array() );
	$is_enabled              = $arr_verowa_module_infos['subscriptions']['enabled'] ?? false;

	if ( $is_enabled ) {
		$wp_post_subscription_form = get_page_by_path( 'subscription-form' );
		if ( null === $wp_post_subscription_form ) {
			$subscription_form_post = array(
				'post_title'     => 'Anmeldung',
				'post_name'      => 'subscription-form',
				'post_type'      => 'page',
				'post_content'   => '<!-- wp:shortcode -->[verowa_subscription_form]<!-- /wp:shortcode -->',
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'post_category'  => array(),
			);

			wp_insert_post( $subscription_form_post );
		}

		$wp_post = get_page_by_path( 'verowa-subscription-confirmation' );
		if ( null === $wp_post ) {
			$subscription_form_post = array(
				'post_title'     => 'Anmeldeformular Antwort',
				'post_name'      => 'verowa-subscription-confirmation',
				'post_type'      => 'page',
				'post_content'   => '<!-- wp:shortcode -->[verowa_subscription_confirmation]<!-- /wp:shortcode -->',
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'post_category'  => array(),
			);

			wp_insert_post( $subscription_form_post );
		}

		$wp_post = get_page_by_path( 'anmeldung-validieren' ) ?? array();
		if ( null === $wp_post ) {

			$subscription_form_post = array(
				'post_title'     => 'Anmeldung bestätigt',
				'post_name'      => 'anmeldung-validieren',
				'post_type'      => 'page',
				'post_content'   => '<!-- wp:shortcode -->[verowa_subscription_validation]<!-- /wp:shortcode -->',
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'post_category'  => array(),
			);

			wp_insert_post( $subscription_form_post );
		}
	}
}
