<?php
/**
 * Function collection for the verowa events
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @package Verowa Connect
 * @subpackage Functions
 */

/**
 * The composition of the output for the individual event detail.
 * Javascript code could not proper save in post content. So it has to be separated.
 *
 * @param int   $id id of the given event.
 * @param array $arr_templates includes all required templates.
 * @param array $arr_event_data if the parameter is empty, the event data is fetched from the DB.
 * @return array [ html, script ]
 */
function verowa_event_get_single_content( $id, $arr_templates, $arr_event_data = array() ) {
	if ( true === is_array( $arr_event_data ) && count( $arr_event_data ) > 0 ) {
		$id = $arr_event_data['event_id'];
	} elseif ( 0 === $id ) {
		$arr_event_data = verowa_event_db_get_content( $id );
	}

	if ( true === verowa_wpml_is_configured() ) {
		$verowa_wpml_mapping    = verowa_wpml_get_mapping();
		$str_domain             = 'verowa-connect';
		$arr_active_languages   = verowa_wpml_get_active_languages( true );
		$str_default_lang_code  = verowa_wpml_get_default_language_code();

		foreach ( $verowa_wpml_mapping as $arr_wp_language_codes ) {
			foreach ( $arr_wp_language_codes as $str_wp_language_code ) {
				$arr_lang = verowa_wpml_get_language_strings_for_events( $arr_event_data, $str_wp_language_code );
				$str_wp_locale = $arr_active_languages[ $str_wp_language_code ]['default_locale'];
				$str_mofile    = $str_domain . '-' . $str_wp_locale . '.mo';
				load_textdomain( $str_domain, WP_LANG_DIR . '/plugins/' . $str_mofile, $str_wp_locale );
				$obj_single_template = $arr_templates[ $str_wp_language_code ] ?? $arr_templates[ $str_default_lang_code ];
				if ( empty( $arr_event_data ) ) {
					$arr_event_content[ $str_wp_language_code ] = esc_html(
						verowa_tf( 
							'This event doesn’t exist or it is already over.', 
							__( 'This event doesn’t exist or it is already over.', 'verowa-connect' ), 
							$str_wp_language_code 
						)
					);
				} else {
					$arr_event_content[ $str_wp_language_code ] = verowa_event_assembling_html( $id, $arr_event_data, $obj_single_template, $arr_lang, $str_wp_language_code );
				}
			} // foreach WP locale.
		} // foreach Verowa languages.
		// return $arr_lang_event_content;
	} else {
		$obj_single_template     = $arr_templates['de'];
		$arr_lang                = verowa_wpml_get_language_strings_for_events( $arr_event_data, 'de' );
		$arr_event_content['de'] = verowa_event_assembling_html( $id, $arr_event_data, $obj_single_template, $arr_lang );
	}

	return $arr_event_content;
}




/**
 * Fills the placeholders and creates the HTML and JavaScript for the creation of a "verowa_event".
 *
 * @param string          $id event-id.
 * @param array           $arr_event_data  All data for the corresponding event.
 * @param \Picture_Planet_GmbH\Verowa_Connect\VEROWA_TEMPLATE $obj_single_template Verowa template.
 * @param array           $arr_lang Array with all translated texts for the corresponding language.
 * @param string          $str_wp_language_code Only needed with WPML.
 *
 * @return array [html, script]
 */
function verowa_event_assembling_html( $id, $arr_event_data, &$obj_single_template, $arr_lang, 
	$str_wp_language_code = 'de' ) {
	global $wpdb;

	$arr_placeholders = array();
	$str_siteurl      = verowa_get_base_url();

	$image = $arr_event_data['detail_image'];
	if ( '' !== $image ) {
		$arr_placeholders['IMAGE_URL'] = $image;
	}

	$arr_placeholders['EVENT_ID'] = $id;

	// If the layer_ids key is missing, the IDs are loaded as fall back.
	if ( false === key_exists( 'layer_ids', $arr_event_data ?? [] ) ) {
		$arr_ids                            = verowa_prepare_layer_list_target_group( $arr_event_data, '' );
		$arr_event_data['layer_ids']        = $arr_ids['layer_ids'] ?? '';
		$arr_event_data['target_group_ids'] = $arr_ids['target_group_ids'] ?? '';
	}

	$arr_placeholders['LAYER_IDS']        = $arr_event_data['layer_ids'];
	$arr_placeholders['TARGET_GROUP_IDS'] = $arr_event_data['target_group_ids'];

	$arr_placeholders['TITLE'] = $arr_lang['title'];
	/** Use TITLE @deprecated v 2.4.0 */
	$arr_placeholders['EVENT_TITLE'] = $arr_lang['title'];

	$arr_placeholders['TOPIC'] = $arr_lang['topic'] ?? '';

	$technical_date_from = new DateTime( $arr_event_data['date_from'] );
	$technical_date_to   = new DateTime( $arr_event_data['date_to'] );

	// If to-time is turned on, show Verowa date_text,
	// because it's already nicely pre-formatted; otherwise, we assemble the date ourselves.

	/**
	 * Use DATE_FROM_LONG
	 *
	 * @deprecated 2.4.0
	 */
	$arr_placeholders['EVENT_DATETIME'] = date_i18n( 'l, j. F Y, G.i', $technical_date_from->format( 'U' ) ) .
		'&nbsp;' . __( 'o’clock', 'verowa-connect' );

	$arr_placeholders['DATETIME_FROM_LONG_WITH_TO_TIME'] = $arr_event_data['date_text'];
	$arr_placeholders['DATETIME_FROM_LONG']              = date_i18n( 'l, j. F Y, G.i', $technical_date_from->format( 'U' ) ) .
		'&nbsp;' . __( 'o’clock', 'verowa-connect' );
	$arr_placeholders['DATE_FROM_LONG']                  = date_i18n( 'l, j. F Y', $technical_date_from->format( 'U' ) );

	// if a room is available, we display it.
	if ( '' !== $arr_event_data['rooms'] ) {
		$arr_first_room = $arr_event_data['rooms'][0] ?? array();

		// Check if it is an external URL, otherwise put the blog address in $site.
		if ( '' !== ( $arr_first_room['location_url_is_external'] ?? '' ) ) {
			$url_domain           = get_bloginfo( 'url' );
			$str_target_attribute = '';
		} else {
			$url_domain           = ''; // if external, the domain is already included in the location URL.
			$str_target_attribute = ' target="_blank" ';
		}

		$str_location_url = $arr_first_room['location_url'] ?? '';
		// TODO für WPML erweitern.
		if ( '' !== $str_location_url ) {
			$loc_link_part1 = '<a href="' . $url_domain . $str_location_url . '"' . $str_target_attribute . '>';
			$loc_link_part2 = '</a>';
		} else {
			$loc_link_part1 = '';
			$loc_link_part2 = '';
		}

		$str_location = verowa_event_show_single_location( $arr_first_room );

		$strlocation_name                    = $arr_first_room['location_name'] ?? '';
		$arr_placeholders['LOCATION']        = $strlocation_name;
		$arr_placeholders['LOCATION_LINKED'] = $loc_link_part1 . $strlocation_name .
			$loc_link_part2;

		/** Use ROOM_NAME @deprecated v 2.4.0 */
		$arr_placeholders['LOCATIONS'] = $str_location;

		$arr_placeholders['ROOM_NAME']                 = $arr_first_room['name'] ?? '';
		$arr_placeholders['LOCATION_WITH_ROOM']        = $str_location;
		$arr_placeholders['LOCATION_WITH_ROOM_LINKED'] = $loc_link_part1 . $str_location . $loc_link_part2;
		$arr_placeholders['LOCATION_WITH_ADDRESS']     = verowa_event_get_single_location_with_address( $arr_first_room );
	}

	if ( $arr_event_data['with_sacrament'] > 0 ) {
		$arr_placeholders['WITH_SACRAMENT'] = __( 'With sacrament', 'verowa-connect' );
	}

	$arr_placeholders['LONG_DESC']  = $arr_lang['long_desc'];

	if ( ! empty( $arr_event_data['further_coorganizer'] ) || ! empty( $arr_event_data['coorganizers'] )
		|| ! empty( $arr_event_data['organizer'] ) ) {

		$arr_coorganizer_names = [];
		if ( ! empty( $arr_event_data['organizer'] ) ) {
			$arr_coorganizer_names[] = $arr_event_data['organizer']['name'];
		}

		$str_coorganizers = '';
		if ( ! empty( $arr_event_data['coorganizers'] ) ) {
			foreach ( $arr_event_data['coorganizers'] as $coorganizer ) {
				$str_coorganizers .= ($coorganizer['name'] ?? '') . ', ';
				$arr_coorganizer_names[] = $coorganizer['name'];
			}
		}

		if ( ! empty( $arr_lang['further_coorganizer'] ) ) {
			$str_coorganizers .= $arr_lang['further_coorganizer'] . ', ';
			$arr_coorganizer_names[] = $arr_lang['further_coorganizer'];
		}

		// coorganizer inkl. organizer
		$arr_coorganizer_names = array_unique( $arr_coorganizer_names );
		$str_contributors = implode( ', ', $arr_coorganizer_names );
		$arr_placeholders['CONTRIBUTORS']                = $str_contributors;
		$arr_placeholders['COORGANIZERS_WITH_ORGANIZER'] = $str_contributors;

		$arr_placeholders['COORGANIZERS'] = substr( $str_coorganizers, 0, -2 ); // WITHOUT the organiser.
		/**
		 * Use COORGANIZERS
		 *
		 * @deprecated v 2.4.0
		 * */
		$arr_placeholders['COORGANIZER'] = $arr_placeholders['COORGANIZERS'];

	}

	// Subscription.
	$str_subs_time_date   = '';
	$str_subs_date        = new DateTime();
	$str_date_i18n_format = '';
	$str_datetime_now     = date_i18n( 'Ymd' ) . date_i18n( 'His' );

	// Subscription deadline.
	if ( ! empty( $arr_event_data['subs_date'] ) && ! empty( $arr_event_data['subs_time'] ) ) {
		$str_date_i18n_format = 'l, j. F Y, G.i';
		$str_clock            = '&nbsp;' . __( 'o’clock', 'verowa-connect' );
		$str_subs_date        = new DateTime(
			substr( $arr_event_data['subs_date'], 0, 4 ) . '-' .
			substr( $arr_event_data['subs_date'], 4, 2 ) . '-' .
			substr( $arr_event_data['subs_date'], 6, 2 ) . ' ' .
			substr( $arr_event_data['subs_time'], 0, 2 ) . ':' .
			substr( $arr_event_data['subs_time'], 2, 2 ) . ':00'
		);
	} elseif ( ! empty( $arr_event_data['subs_date'] ) ) {

		// If the login time is empty it is not displayed.
		$str_date_i18n_format = 'l, j. F Y';
		$str_clock            = '';
		$str_subs_date        = new DateTime(
			substr( $arr_event_data['subs_date'], 0, 4 ) . '-' .
			substr( $arr_event_data['subs_date'], 4, 2 ) . '-' .
			substr( $arr_event_data['subs_date'], 6, 2 ) . ' 23:59:59'
		);
	}

	if ( ! empty( $arr_event_data['subs_time'] ) || ! empty( $arr_event_data['subs_date'] ) ) {
		$str_subs_time_date = verowa_tf( 'Subscription until', __( 'Subscription until', 'verowa-connect' ), $str_wp_language_code ) . ' ' .
			date_i18n( $str_date_i18n_format, $str_subs_date->format( 'U' ) ) . $str_clock;
	}

	// If the subscribe date is empty the event start date is set.
	if ( empty( $arr_event_data['subs_date'] ) ) {
		$arr_event_data['subs_date'] = $technical_date_from->format( 'Ymd' );
	}

	// If the subscribe time is empty it is set to 23:59.
	if ( empty( $arr_event_data['subs_time'] ) ) {
		if ( $arr_event_data['subs_date'] === $technical_date_from->format( 'Ymd' ) ) {
			$arr_event_data['subs_time'] = $technical_date_from->format( 'Hi' );
		} else {
			$arr_event_data['subs_time'] = '2359';
		}
	}

	$str_subscription_text = '';

	$is_subs_module_active = filter_var( $arr_event_data['subs_module_active'], FILTER_VALIDATE_BOOLEAN ) ?? false;
	// If the subscription module is not activated, we only output the deadline and the subscription person (if available).
	if ( false === $is_subs_module_active ) {
		// We only give out something if there is a subscription date.
		if ( ! empty( $arr_event_data['subs_date'] ) ) {
			if ( $arr_event_data['subs_date'] . $arr_event_data['subs_time'] . '59' > $str_datetime_now ) {
				$str_subscription_text       .= '<div><p class="subscription-event-detail-datetime">' .
					$str_subs_time_date . '</p></div>';
					$is_subs_deadline_expired = false;
			} else {
				$str_tf = verowa_tf( 
					'The registration deadline expired on %s.', 
					__( 'The registration deadline expired on %s.', 'verowa-connect' ),
					$str_wp_language_code 
				);
				$str_subscription_text .= '<p class="subscription-event-detail-deadline-over">' .
					/*
					* translators: %s: Date of subscription deadline.
					*/
					sprintf(
						$str_tf,
						date_i18n( $str_date_i18n_format, $str_subs_date->format( 'U' ) )
					) . '</p>';
				$is_subs_deadline_expired = true;
			}
		}

		// If a registration person is specified. Otherwise you have to register via the contact person of the event.
		// 04.03.2023/CWe: If the registration deadline has expired, the mail should no longer be displayed.
		if ( ! empty( $arr_event_data['subscribe_person'] ) && false === $is_subs_deadline_expired ) {
			$str_tf = verowa_tf( 
				'via e-mail',
				__( 'via e-mail', 'verowa-connect' ),
				$str_wp_language_code
			);
			$str_subscription_text .= ' ' . $str_tf . ' ' .
				_x( 'to', 'personal', 'verowa-connect' ) .
				' <a href="mailto:' . $arr_event_data['subscribe_person']['email'] . '">' .
				$arr_event_data['subscribe_person']['name'] . '</a>';
		}
	} elseif ( isset( $arr_event_data['subs_form'] ) &&
		0 !== intval( $arr_event_data['subs_person_id'] ) ||
		! empty( $arr_event_data['subs_date'] ) ) {
		$arr_template_options = $arr_event_data['subs_form']['template_options'] ?? array();
		// Output link only if "without subscription" is not selected (subs_person_id == 1 ).
		if ( 1 !== intval( $arr_event_data['subs_person_id'] ) ) {
			// Output link to subscription form only if registration is "not" over.
			if ( isset( $arr_event_data['subs_date'] ) &&
				$arr_event_data['subs_date'] . $arr_event_data['subs_time'] . '59' > $str_datetime_now ) {

				switch ( $arr_event_data['subs_state'] ?? 'none' ) {
					case 'waiting_list':
						// no fallback is required, the api set a default value.
						if ( isset( $arr_template_options['subs_detail_button_text'] ) ) {
							// TODO: (AM) Umschreiben auf ml, da der Link verschieden ist.
							$str_subscription_text .= '<a class="subscription-button" ' .
								'href="' . $str_siteurl .
								'/subscription-form?subs_event_id=' . $id . '">' .
								$arr_template_options['subs_detail_button_text'] . '</a>';
						}
						break;

					case 'booked_up':
						if ( isset( $arr_template_options['subs_detail_booked_up_text'] ) ) {
							$str_subscription_text .=
								'<span class="verowa-subs-booked-up-detail" >' . $arr_template_options['subs_detail_booked_up_text'] . '</span>';
						}
						break;

					case 'subs_link':
						$str_tf = verowa_tf( 'to the registration form', __('to the registration form', 'verowa-connect'), $str_wp_language_code );
						$str_subs_btn_text      = key_exists( 'subs_detail_button_text', $arr_template_options ?? array() ) ?
							$arr_template_options['subs_detail_button_text'] : $str_tf;
						$str_subscription_url   = verowa_wpml_get_translated_permalink(
							$str_siteurl . '/subscription-form?subs_event_id=' . $id,
							$str_wp_language_code
						);
						$str_subscription_text .= '<a class="subscription-button" ' .
							'href="' . $str_subscription_url . '">' .
							$str_subs_btn_text . ' </a>';
						break;
				}
			} else {
				if ( isset( $arr_template_options['subs_detail_deadline_expired_text'] ) ) {
					$str_subscription_text .=
						'<span class="verowa-subs-deadline-expired-detail" >' .
						$arr_template_options['subs_detail_deadline_expired_text'] . '</span>';
				}
			}

			// Show deadline only when a subscription link is displayed.
			if ( in_array( $arr_event_data['subs_state'] ?? 'none', array( 'subs_link', 'waiting_list' ), true ) ) {
				// Check whether the AM plug-in is installed.
				if ( empty( $arr_event_data['subs_date'] ) ||
					$arr_event_data['subs_date'] . $arr_event_data['subs_time'] . '59' >= $str_datetime_now ) {
					$str_subscription_text .= '<p class="subscription-event-detail-datetime">' .
						$str_subs_time_date . '</p>';
				} else {
					$str_subscription_text .= '<p class="subscription-event-detail-deadline-over">' .
						sprintf(
							__( 'The registration deadline expired on %s', 'verowa-connect' ),
							date_i18n( $str_date_i18n_format, $str_subs_date->format( 'U' ) )
						) . '</p>';
				}
			}
		} else {
			$str_subscription_text .= '<div style="margin: 30px 0px 30px;"><p>' .
				verowa_tf( 'No registration necessary.', __( 'No registration necessary.', 'verowa-connect' ), $str_wp_language_code ) . '</p></div>';
		}
	}

	if ( strlen( $str_subscription_text ) > 0 ) {
		/**
		 * Has been replaced by SUBS_INFO.
		 *
		 * @deprecated v 2.4.0
		 */
		$arr_placeholders['SUBSCRIPTION'] = $str_subscription_text;

		/**
		 * Has been replaced by SUBS_INFO.
		 *
		 * @deprecated v 2.11.2
		 */
		$arr_placeholders['SUBSCRIPTION_INFO'] = $str_subscription_text;
		$arr_placeholders['SUBS_INFO']         = $str_subscription_text;
	} else {
		$arr_placeholders['SUBSCRIPTION_INFO'] = '';
		$arr_placeholders['SUBS_INFO']         = '';
	}

	$arr_placeholders['SUBS_FORM']  = '[verowa_subscription_form event_id=' . $id . ' show_event_info=0]';
	$arr_placeholders['SUBS_STATE'] = $arr_event_data['subs_state'] ?? 'none';

	if ( ! empty( $arr_event_data['organists'] ) ) {
		$str_organists = __( 'Music', 'verowa-connect' ) . ': ';

		foreach ( $arr_event_data['organists'] as $organists ) {
			$str_organists .= $organists['name'] . ', ';
		}

		$arr_placeholders['ORGANIST'] = substr( $str_organists, 0, -2 ); /** @deprecated v 2.4.0 */
	}

	if ( ! empty( $arr_event_data['collection'] ) ) {
		if ( strlen( $arr_event_data['collection']['url'] ) > 0 ) {
			$collection_url = $arr_event_data['collection']['url'];

			if ( false === strpos( $collection_url, '//' ) ) {
				$collection_url = 'https://' . $collection_url;
			}

			$str_link_pre  = '<a href="' . $collection_url . '" target="_blank">';
			$str_link_post = '</a>';
		} else {
			$str_link_pre  = '';
			$str_link_post = '';
		}

		$arr_placeholders['COLLECTION'] = $str_link_pre .
			$arr_event_data['collection']['project'] . $str_link_post;

	} else {
		$arr_placeholders['COLLECTION'] = '';
	}

	$arr_placeholders['BAPTISM_OFFER'] = $arr_event_data['baptism_offer_text'] ?? '';

	if ( ! empty( $arr_event_data['childcare_text'] ) ) {
		$str_childcare = $arr_event_data['childcare_text'];

		if ( ! empty( $arr_event_data['childcare_person']['name'] ) ) {
			$str_childcare .= ': ' . $arr_event_data['childcare_person']['name'];
		}

		$arr_placeholders['CHILDCARE'] = $str_childcare;
	}

	$arr_placeholders['CATERING'] = ( ! empty( $arr_event_data['catering'] ) ) ? $arr_event_data['catering'] : '';

	$arr_placeholders['ORGANIZER_NAME'] = '';
	$arr_placeholders['ORGANIZER']      = '';

	$arr_placeholders['ORGANIZER_ID'] = '';

	// Contact person or pastoral worker (can also be switched off).
	if ( key_exists( 'person_id', $arr_event_data['organizer'] ?? array() ) &&
		is_numeric( $arr_event_data['organizer']['person_id'] ) &&
		$arr_event_data['organizer']['person_id'] > 0 ) {

		$int_person_id                    = intval( $arr_event_data['organizer']['person_id'] ?? 0 );

		// Check whether a person ID is available.
		if ( $int_person_id > 0 ) {
			// ORGANIZER_ID is only set if the person is publicly visible.
			$query           = 'SELECT `person_id` FROM `' . $wpdb->prefix .
				'verowa_person` WHERE `person_id` = "' . $int_person_id . '";';
			$person_id_in_db = $wpdb->get_var( $query ) ?? 0;
			if ( $person_id_in_db > 0 ) {
				$arr_placeholders['ORGANIZER_ID'] = $person_id_in_db;
			}
		}

		$arr_placeholders['ORGANIZER_NAME'] = $arr_event_data['organizer']['name'] ?? '';

		/**
		 * New us the person shortcode
		 *
		 * @deprecated v 2.9.0
		 * */
		$arr_placeholders['ORGANIZER'] = $arr_event_data['organizer']['name'];
	}

	for ( $i = 1; $i <= 4; $i++ ) {
		$arr_placeholders[ 'ADD_TEXT_' . $i ] = nl2br( $arr_lang[ 'additional_text' . $i ] ?? $arr_event_data[ 'additional_text' . $i ] ?? '' );
	}
	$str_post_url = verowa_wpml_get_custom_post_url ($arr_event_data['event_id'], 
		$str_wp_language_code, 'veranstaltung', 'verowa_event');

	// Micro format is a default.
	$str_javascript = '<script type="application/ld+json">
		{
		"@type": "h-event",
		"p-name": "' . $arr_event_data['title'] . '",
		"location": "' . ( $arr_event_data['rooms'][0]['location_name'] ?? '' ) . '",
		"dt-start": "' . $technical_date_from->format( 'Y-m-d h:m' ) . '",
		"dt-end": "' . $technical_date_to->format( 'Y-m-d h:m' ) . '",
		"u-url": "' . $str_post_url . '",
		"p-summary": "' . $arr_event_data['short_desc'] . '"
		}
		</script>';

	$str_javascript .= '<script>jQuery("li:contains(\'' . __( 'Agenda', 'verowa-connect' ) . '\')").' .
		'addClass("current_page_item current-menu-item");</script>';

	// fake genesis breadcrumbs.
	global $breadcrumb_title;
	$breadcrumb_title = $arr_event_data['title'];
	remove_action( 'genesis_before_loop', 'genesis_do_breadcrumbs' );
	add_action( 'genesis_before_loop', 'verowa_fake_breadcrumbs' );

	$arr_shared_placeholder = verowa_event_shared_placeholders( $arr_event_data, $str_wp_language_code );
	$arr_placeholders       = array_merge( $arr_placeholders, $arr_shared_placeholder );

	$str_output = $obj_single_template->header .
		verowa_parse_template( $obj_single_template->entry, $arr_placeholders ) .
		$obj_single_template->footer;

	$str_head = verowa_parse_template ($obj_single_template->head, $arr_placeholders);

	return array(
		'head'   => $str_head,
		'html'   => $str_output,
		'script' => $str_javascript,
	);
}



/**
 * Display a single event in the list.
 *
 * @param array  $arr_event Event data.
 * @param array  $arr_template Contains all language templates (VEROWA_Templates).
 * @param string $str_language_code If WPML is not configured, default language code will be 'de'.
 * @param string $str_handle_full Define how the subscription link is displayed.
 *
 * @return string Rendered HTML content for the event.
 */
function verowa_event_show_single( $arr_event, $arr_template, $str_language_code = 'de', $str_handle_full = '' ) {
	global $post;

	$event_cats                   = '';
	$arr_placeholders             = array();
	$arr_content                  = $arr_event['content'];
	$str_default_lang_code        = verowa_wpml_get_default_language_code();
	$arr_placeholders['EVENT_ID'] = $arr_content['event_id'];
	$wpml_is_configured           = verowa_wpml_is_configured();
	$str_siteurl                  = verowa_get_base_url();

	if ( true === $wpml_is_configured ) {
		$arr_placeholders['EVENT_DETAILS_URL'] = verowa_wpml_get_custom_post_url ($arr_event['event_id'], 
			$str_language_code, 'veranstaltung', 'verowa_event');
	} else {
		$arr_placeholders['EVENT_DETAILS_URL'] = $str_siteurl . '/veranstaltung/' . $arr_content['event_id'] . '/';
	}

	$obj_template = $arr_template[ $str_language_code ] ?? $arr_template[ $str_default_lang_code ];
	$arr_lang     = verowa_wpml_get_language_strings_for_events( $arr_content, $str_language_code );
	$str_title    = $arr_lang['title'];

	if ( isset( $arr_content['event_cats'] ) ) {
		$event_cats = str_replace( '.', '', $arr_content['event_cats'] );
	}

	// Area IDs (we need these to change the texts in the template depending on the layer).
	$arr_placeholders['LAYER_IDS']        = $arr_event['layer_ids']; // z.B. ";36;5;"
	$arr_placeholders['TARGET_GROUP_IDS'] = $arr_event['target_group_ids'] ?? $arr_event['target_groups'];

	$arr_coorganizer_names = array();
	if ( count( $arr_content['coorganizers'] ) > 0 ) {
		foreach ( $arr_content['coorganizers'] as $arr_single_coorg ) {
			$arr_coorganizer_names[] = $arr_single_coorg['name'];
		}
	}

	// Coorganizers: Names as a string in an array.
	$arr_coorganizer_names = array();
	if ( count( $arr_content['coorganizers'] ) > 0 ) {
		foreach ( $arr_content['coorganizers'] as $arr_single_coorg ) {
			$arr_coorganizer_names[] = $arr_single_coorg['name'];
		}
	}

	/** Block of deprecated placeholders @deprecated v 2.4.0 */
	$arr_placeholders['EVENT-CATS']  = $event_cats;
	$arr_placeholders['DATE_FROM']   = gmdate( 'Ymd', strtotime( $arr_content['date_from'] ) );
	$arr_placeholders['EVENT_DATE']  = verowa_event_show_single_date( $arr_content );
	$arr_placeholders['EVENT_TIME']  = verowa_event_show_single_time( $arr_content, true );
	$arr_placeholders['EVENT_TITLE'] = $str_title;
	// end deprecated.

	$arr_placeholders['CATEGORIES']      = $event_cats;
	$arr_placeholders['DATE_FROM_8']     = date( 'Ymd', strtotime( $arr_content['date_from'] ) );
	$arr_placeholders['DATE_FROM_LONG']  = verowa_event_show_single_date( $arr_content );
	$arr_placeholders['TIME_FROM_LONG']  = verowa_event_show_single_time( $arr_content, false );
	$arr_placeholders['TIME_MIXED_LONG'] = verowa_event_show_single_time( $arr_content, true );
	$arr_placeholders['TITLE']           = $str_title;

	$str_organizer                      = $arr_content['organizer']['name'] ?? '';
	$arr_placeholders['ORGANIZER_NAME'] = $str_organizer;

	$str_coorganizers                                = implode( ', ', $arr_coorganizer_names );

	// coorganizer inkl. organizer
	array_unshift( $arr_coorganizer_names, $str_organizer );
	$arr_coorganizer_names = array_unique( $arr_coorganizer_names );
	$str_contributors = implode( ', ', $arr_coorganizer_names );
	$arr_placeholders['CONTRIBUTORS']                = $str_contributors;
	$arr_placeholders['COORGANIZERS_WITH_ORGANIZER'] = $str_contributors;

	$arr_placeholders['COORGANIZER_NAMES'] = $str_coorganizers;

	// It is checked whether the event is multi-day.
	$verowa_date_from = new DateTime( $arr_content['date_from'] );
	$verowa_date_to   = new DateTime( $arr_content['date_to'] );
	if ( ( $verowa_date_to->format( 'U' ) - $verowa_date_from->format( 'U' ) ) > 86400 ) {
		$date_format_string_from = 'D, j. F';
		$date_format_string_to   = 'D, j. F Y';

		if ( date_i18n( 'FY', $verowa_date_from->format( 'U' ) ) == date_i18n( 'FY', $verowa_date_to->format( 'U' ) ) ) {
			$date_format_string_from = 'D, j.';
		}

		$arr_placeholders['DATETIME_LONG'] = date_i18n( $date_format_string_from, $verowa_date_from->format( 'U' ) ) .
			', ' . _x( 'to', 'temporal', 'verowa-connect' ) . ' ' .
			date_i18n( $date_format_string_to, $verowa_date_to->format( 'U' ) );
	} else {
		$arr_placeholders['DATETIME_LONG'] = $arr_content['date_text'];
		$arr_placeholders['DATE_LONG']     = date_i18n( 'D', $verowa_date_from->format( 'U' ) ) . ', ' .
			date_i18n( 'j.', $verowa_date_from->format( 'U' ) ) . ' ' .
			date_i18n( 'F Y', $verowa_date_from->format( 'U' ) ) . ', ' .
			date_i18n( 'G.i', $verowa_date_from->format( 'U' ) ) . '&nbsp;' .
			__( 'o’clock', 'verowa-connect' );
	}

	/** @deprecated EVENT_DATETIME_TEXT */
	$arr_placeholders['EVENT_DATETIME_TEXT'] = $arr_placeholders['DATETIME_LONG'];
	$arr_placeholders['TOPIC']               = $arr_content['topic'];

	if ( ! empty( $arr_content['rooms'] ) ) {
		$arr_placeholders['LOCATION']              = $arr_content['rooms'][0]['location_name'] ?? '';
		$arr_placeholders['ROOM_NAME']             = $arr_content['rooms'][0]['name'] ?? '';
		$arr_placeholders['LOCATION_WITH_ROOM']    = verowa_event_show_single_location( $arr_content['rooms'][0] );
		$arr_placeholders['LOCATION_WITH_ADDRESS'] =
			verowa_event_get_single_location_with_address( $arr_content['rooms'][0] );
		$arr_placeholders['LOCATIONS']             = verowa_event_show_single_location( $arr_content['rooms'][0] ); /** @deprecated */
	}

	if ( '' != ( $arr_content['list_image_url'] ?? '' ) ) {
		$arr_placeholders['IMAGE_URL'] = $arr_content['list_image_url'];
	} else {
		$arr_placeholders['IMAGE_URL'] = '';
	}

	$str_datetime_now = date_i18n( 'Ymd' ) . date_i18n( 'His' );

	if ( empty( $arr_content['subs_time'] ) ) {
		$arr_content['subs_time'] = '2359';
	}

	// Set registration button when a form has been selected.
	$arr_placeholders['SUPSCRIPTION_BUTTON'] = ''; /** Use SUBS_BUTTON @deprecated v 2.12.0 */
	$arr_placeholders['SUPS_BUTTON']         = ''; /** Use SUBS_BUTTON @deprecated v 2.12.0 */
	$arr_placeholders['SUBS_BUTTON']         = '';

	if ( key_exists( 'subs_date', $arr_content ) && strlen( $arr_content['subs_date'] ) < 8 ) {
		$arr_content['subs_date'] = 0;
	}

	$str_subs_button    = '';
	$int_subs_person_id = intval( $arr_content['subs_person_id'] ?? 0 );
	if ( ( 0 != $arr_content['subs_date'] ||
		( key_exists( 'subs_module_active', $arr_content ) &&
		key_exists( 'subs_person_id', $arr_content ) &&
		true == $arr_content['subs_module_active'] ) ) &&
		$int_subs_person_id > 1 ) {

		$arr_template_options = $arr_content['subs_form']['template_options'] ?? array();
		switch ( $arr_content['subs_state'] ?? 'none' ) {
			case 'waiting_list':
				$str_subs_button = '<a href="' . $str_siteurl . '/subscription-form?subs_event_id=' .
					$arr_content['event_id'] . '">' .
					'<button class="subscription subscription-waiting-list">' .
					( $arr_template_options['subs_list_button_text'] ?? '' ) . '</button></a>';
				break;

			case 'booked_up':
				$str_subs_button = '<span class="verowa-subs-booked-up-list" >' .
					( $arr_template_options['subs_list_button_text'] ?? '' ) . '</span>';
				break;

			case 'deadline_expired':
				$str_tf = verowa_tf( 'Subscription', __( 'Subscription', 'verowa-connect' ), $str_language_code );
				$str_subs_btn_text = $arr_template_options['subs_list_button_text'] ?? $str_tf;

				$str_subs_button = '<button class="subscription disabled"' .
					' title="' . $arr_template_options['subs_list_deadline_expired_button_title'] . '" disabled>' . $str_subs_btn_text . '</button>';
				break;

			case 'subs_link':
				$str_tf = verowa_tf( 'Subscription', __( 'Subscription', 'verowa-connect' ), $str_language_code );
				$str_subs_btn_text = $arr_template_options['subs_list_button_text'] ?? $str_tf;
				$str_subs_button   = '<a href="' . $str_siteurl . '/subscription-form?subs_event_id=' .
					$arr_content['event_id'] . '">' .
					'<button class="subscription">' . $str_subs_btn_text . '</button></a>';
				break;

			case 'no_subs_form':
				$str_subs_button = '<span class="verowa-subs-no-subs-form-list" >' .
					$arr_content['subs_list_no_subs_form_text'] . '</span>';
				break;
		}
	}
	$arr_placeholders['SUPSCRIPTION_BUTTON'] = $str_subs_button; /** Use SUBS_BUTTON @deprecated v 2.11.2 */
	$arr_placeholders['SUPS_BUTTON']         = $str_subs_button; /** Use SUBS_BUTTON @deprecated v 2.12.0 */
	$arr_placeholders['SUBS_BUTTON']         = $str_subs_button;

	$arr_placeholders['SUBS_STATE'] = $arr_content['subs_state'] ?? 'none';

	// Change detail button if no form is selected but it still has a login time.
	if ( 0 !== intval( $arr_content['subs_date'] ) && 0 !== $int_subs_person_id
		&& $arr_content['subs_date'] . $arr_content['subs_time'] . '59' > $str_datetime_now ) {
			$str_tf = verowa_tf( 'Details + Subscription', __( 'Details + Subscription', 'verowa-connect' ), $str_language_code );
		$arr_placeholders['DETAILS_BUTTON_TEXT'] = $str_tf;
	} else {
		$str_tf = verowa_tf( 'Details', __( 'Details', 'verowa-connect' ), $str_language_code );
		$arr_placeholders['DETAILS_BUTTON_TEXT'] = $str_tf;
	}

	$arr_shared_placeholder = verowa_event_shared_placeholders( $arr_content );
	$arr_placeholders       = array_merge( $arr_placeholders, $arr_shared_placeholder );
	$str_output             = verowa_parse_template( $obj_template->entry, $arr_placeholders ) .
		$obj_template->separator;

	return $str_output;
}




/**
 * Generates placeholders for event details and event lists.
 *
 * @param array $arr_event_data The event content.
 * @return array Placeholders
 */
function verowa_event_shared_placeholders( $arr_event_data, $str_language_code = 'de' ) {
	$arr_placeholders = array();

	$technical_date_from = new DateTime( $arr_event_data['date_from'] );
	$technical_date_to   = new DateTime( $arr_event_data['date_to'] );
	$str_month = date_i18n( 'F', $technical_date_from->format( 'U' ) );
	
	$arr_placeholders['WEEKDAY']           = strtolower( date( 'D', strtotime( $arr_event_data['date_from'] ) ) ); // z.B. "sat"

	// (e.g. "Wed").
	$arr_placeholders['WEEKDAY_SHORT'] = date_i18n( 'D', $technical_date_from->format( 'U' ) );
	// (e.g. "3" or "13", without leading zero).
	$arr_placeholders['DAY_FROM_NB'] = date_i18n( 'j', $technical_date_from->format( 'U' ) );
	// (e.g. "3" or "10", without leading zero).
	$arr_placeholders['MONTH_FROM_NB'] = date_i18n( 'n', $technical_date_from->format( 'U' ) );
	// e.g. "April"
	$arr_placeholders['MONTH_FROM_NAME'] = $str_month;

	// (e.g. "2023").
	$arr_placeholders['YEAR_FROM'] = date_i18n( 'Y', $technical_date_from->format( 'U' ) );
	// (e.g. "7" or "16", without leading zero).
	$arr_placeholders['HOUR_FROM'] = date_i18n( 'G', $technical_date_from->format( 'U' ) );
	// (e.g. "7" or "16", without leading zero).
	$arr_placeholders['HOUR_FROM_2'] = date_i18n( 'H', $technical_date_from->format( 'U' ) );
	// (e.g. "07" or "16", with leading zero).
	$arr_placeholders['MINUTE_FROM'] = date_i18n( 'i', $technical_date_from->format( 'U' ) );

	// (e.g. "00" or "45", with leading zero).
	$arr_placeholders['DAY_TO_NB'] = date_i18n( 'j', $technical_date_to->format( 'U' ) );
	// (e.g. "3" or "13", without leading zero).
	$arr_placeholders['MONTH_TO_NB'] = date_i18n( 'n', $technical_date_to->format( 'U' ) );
	// (e.g. "2023").
	$arr_placeholders['YEAR_TO'] = date_i18n( 'Y', $technical_date_to->format( 'U' ) );
	// (e.g. "7" or "16", without leading zero).
	$arr_placeholders['HOUR_TO'] = date_i18n( 'G', $technical_date_to->format( 'U' ) );
	// (e.g. "07" or "16", with leading zero).
	$arr_placeholders['HOUR_TO_2'] = date_i18n( 'H', $technical_date_to->format( 'U' ) );
	// (e.g. "00" or "45", with leading zero).
	$arr_placeholders['MINUTE_TO'] = date_i18n( 'i', $technical_date_to->format( 'U' ) );

	// Service 1 - 8.
	for ( $i = 1; $i <= 8; $i++ ) {
		$str_service      = '';
		$str_ids          = '';
		$str_service_name = 'service' . $i;
		if ( key_exists( $str_service_name, $arr_event_data ) && count( $arr_event_data[ $str_service_name ] ) ) {
			$arr_person_names = array_column( $arr_event_data[ $str_service_name ], 'name' );
			$str_service      = implode( ', ', $arr_person_names );

			$arr_person_ids = array_column( $arr_event_data[ $str_service_name ], 'person_id' );
			$str_ids        = implode( ', ', $arr_person_ids );
		}

		$arr_placeholders[ 'SERVICE_' . $i . '_LABEL' ]       = $arr_event_data[ 'service' . $i . '_label' ] ?? '';
		$arr_placeholders[ 'SERVICE_' . $i . '_PERSONS' ]     = $str_service;
		$arr_placeholders[ 'SERVICE_' . $i . '_PERSONS_IDS' ] = $str_ids;
	}

	$arr_placeholders['LIST_IDS'] = $arr_event_data['list_ids'] ?? '';
	$arr_placeholders['SHORT_DESC'] = $arr_event_data['short_desc'];

	// Di, 03. September 2023, 16:00 Uhr
	$arr_placeholders['DATETIME_FROM_LONGMONTH'] = date_i18n( 'D, d. F Y, H:i', $technical_date_from->format( 'U' ) ) .
		'&nbsp;' . __( 'o’clock', 'verowa-connect' );

	$str_datetie_from_ect = date_i18n( 'Y-m-d H:i:s', $technical_date_from->format( 'U' ) );
	$str_datetie_to_ect = date_i18n( 'Y-m-d H:i:s', $technical_date_to->format( 'U' ) );
	$arr_placeholders['DATETIME_FROM_UTC'] = pp_convert_cet_to_utc($str_datetie_from_ect);
	$arr_placeholders['DATETIME_TO_UTC'] = pp_convert_cet_to_utc($str_datetie_to_ect);

	// Checks whether flyers are available.
	$arr_placeholders['FILE_LIST'] = verowa_get_file_list( $arr_event_data['files'] ?? [], false, $str_language_code );

	$verowa_member  = get_option( 'verowa_instance', '' );
	$arr_placeholders['ICAL_EXPORT_URL'] = 'https://verowa.ch/' . $verowa_member .  '/export/ical/e' . ($arr_event_data['event_id'] ?? 0);

	$arr_placeholders['LANGUAGE_CODE'] = $str_language_code;

	// new placeholders for the image.
	$arr_placeholders['IMAGE_CAPTION'] = $arr_event_data['image_caption'];
	$arr_placeholders['IMAGE_SOURCE_TEXT'] = $arr_event_data['image_source_text'];
	$arr_placeholders['IMAGE_SOURCE_URL'] = $arr_event_data['image_source_url'];

	return $arr_placeholders;
}




/**
 * Get date HTML for e.g. placeholder DATE_FROM_LONG
 *
 * @param array $event all event data.
 * @return string
 */
function verowa_event_show_single_date( $event ) {
	$verowa_date_from    = new DateTime( $event['date_from'] );
	$verowa_date_to      = new DateTime( $event['date_to'] );
	$verowa_date_from_ts = $verowa_date_from->format( 'U' );
	$verowa_date_to_ts   = $verowa_date_to->format( 'U' );

	$arr_month_kurz = array(
		'01' => __( 'Jan.', 'verowa-connect' ),
		'02' => __( 'Feb.', 'verowa-connect' ),
		'03' => __( 'March', 'verowa-connect' ),
		'04' => __( 'April', 'verowa-connect' ),
		'05' => __( 'May', 'verowa-connect' ),
		'06' => __( 'June', 'verowa-connect' ),
		'07' => __( 'July', 'verowa-connect' ),
		'08' => __( 'Aug.', 'verowa-connect' ),
		'09' => __( 'Sept.', 'verowa-connect' ),
		'10' => __( 'Oct.', 'verowa-connect' ),
		'11' => __( 'Nov.', 'verowa-connect' ),
		'12' => __( 'Dec.', 'verowa-connect' ),
	);

	$str_date_from_year = $verowa_date_from->format( 'Y' );
	$str_year_now       = date_i18n( 'Y' );
	$str_month          = date_i18n( 'F', $verowa_date_from_ts );
	$str_year           = date_i18n( 'Y', $verowa_date_from_ts );

	$is_multiday_event    = date_i18n( 'Ymd', $verowa_date_from_ts ) != date_i18n( 'Ymd', $verowa_date_to->format( 'U' ) );
	$str_month_year_class = $is_multiday_event ? 'verowa-multiday-event verowa-month month' : 'verowa-month month';
	$str_month_year_html  = '<span class="' . $str_month_year_class . '">' . $str_month . '</span>';

	// If the year is not the current one (Can only be in the future), then we spend it.
	if ( $str_date_from_year != $str_year_now ) {
		$str_month            = $arr_month_kurz[ date_i18n( 'm', $verowa_date_from_ts ) ];
		$str_year             = date_i18n( 'y', $verowa_date_from_ts );
		$str_month_year_class = $is_multiday_event ?
			'verowa-multiday-event verowa-month month verowa-year year' : 'verowa-month month verowa-year';
		// Example: January 2022 -> Jan. 2022.
		$str_month_year_html = '<span class="' . $str_month_year_class . '">' . $str_month . ' ' . $str_year . '</span>';
	}

	// The calendar day is only displayed if the event does not last several days.
	if ( ! $is_multiday_event ) {
		$date_string = '<span class="weekday">' . date_i18n( 'l', $verowa_date_from_ts ) . '</span>' .
			'<span class="day">' . date_i18n( 'j', $verowa_date_from_ts ) . '</span>' .
			$str_month_year_html;
	} else {
		$date_string = '<span class="verowa-date-prefix verowa-multiday-event" >' .
			_x( 'from', 'event list e.g. from 13. April', 'verowa-connect' ) . '</span>' .
			'<span class="verowa-day day verowa-multiday-event">' . date_i18n( 'j', $verowa_date_from_ts ) . '</span>' .
			$str_month_year_html;
	}

	return $date_string;
}




/**
 * Get event date and time
 *
 * @param array $event       All event data.
 * @param bool  $show_to_time If true, the end time of the event will be displayed.
 *
 * @return string
 */
function verowa_event_show_single_time( $event, $show_to_time ) {
	$verowa_date_from    = new DateTime( $event['date_from'] );
	$verowa_date_to      = new DateTime( $event['date_to'] );
	$verowa_date_from_ts = $verowa_date_from->format( 'U' );
	$verowa_date_to_ts   = $verowa_date_to->format( 'U' );

	$time_string = '<span class="time">';

	// if it is a one-day event, we can spend the time directly.
	if ( date_i18n( 'Ymd', $verowa_date_from_ts ) == date_i18n( 'Ymd', $verowa_date_to_ts ) ) {
		$time_string .= date_i18n( 'G.i', $verowa_date_from_ts );

		if ( true === $show_to_time ) {
			$time_string .= '&ndash;' . date_i18n( 'G.i', $verowa_date_to_ts );
		}

		$time_string .= '&nbsp;' . __( 'o’clock', 'verowa-connect' );
	} else {
		// For events lasting several days, we use a different format (3 variants):
		// from/to in the same month: "9-13 September 2019".
		if ( date_i18n( 'M Y', $verowa_date_from_ts ) === date_i18n( 'M Y', $verowa_date_to_ts ) ) {
			$time_string .= date_i18n( 'j.', $verowa_date_from_ts ) . '&ndash;' .
				date_i18n( 'j. F Y', $verowa_date_to_ts );

		} elseif ( date_i18n( 'Y', $verowa_date_from_ts ) === date_i18n( 'Y', $verowa_date_to_ts ) ) {
			// if it is over 2 months in the same year: "27 September to 18 October 2019".
			$time_string .= date_i18n( 'j. F', $verowa_date_from_ts ) . ' ' .
				_x( 'to', 'temporal', 'verowa-connect' ) . ' ' .
				date_i18n( 'j. F Y', $verowa_date_to_ts );
		} else {
			// if it is over a year limit: "30 December 2019 to 3 January 2020".
			$time_string .= date_i18n( 'j. F Y', $verowa_date_from_ts ) . ' ' .
				_x( 'to', 'temporal', 'verowa-connect' ) . ' ' .
				date_i18n( 'j. F Y', $verowa_date_to_ts );
		}
	}

	$time_string .= '</span>';

	return $time_string;
}



/**
 * Return a string with the event location
 *
 * @param array $arr_event_loc A single room.
 *
 * @return string
 */
function verowa_event_show_single_location( $arr_event_loc ) {
	$arr_loc_parts = array();

	if ( strlen( $arr_event_loc['location_name'] ?? '' ) > 0 ) {
		$arr_loc_parts[] = $arr_event_loc['location_name'];
	}

	if ( strlen( $arr_event_loc['name'] ?? '' ) > 0 && ! in_array( $arr_event_loc['name'], $arr_loc_parts ) ) {
		$arr_loc_parts[] = $arr_event_loc['name'];
	}

	return implode( ', ', $arr_loc_parts );
}



/**
 * Assembling an address block
 *
 * @param array $arr_event_loc A single room.
 * @return string
 */
function verowa_event_get_single_location_with_address( $arr_event_loc ) {
	$arr_loc_parts = array();

	if ( strlen( $arr_event_loc['location_name'] ?? '' ) > 0 ) {
		$arr_loc_parts[] = $arr_event_loc['location_name'];
	}

	if ( strlen( trim( $arr_event_loc['location_address'] ?? '' ) ) > 0 ) {
		$arr_loc_parts[] = $arr_event_loc['location_address'];
	}

	if ( strlen( trim( ( $arr_event_loc['location_postcode'] ?? '' ) . ' ' . ( $arr_event_loc['location_city'] ?? '' ) ) ) > 0 ) {
		$arr_loc_parts[] = trim( ( $arr_event_loc['location_postcode'] ?? '' ) . ' ' . ( $arr_event_loc['location_city'] ?? '' ) );
	}

	return implode( ', ', $arr_loc_parts );
}




/**
 * Insert an event into the DB
 *
 * @param array $arr_event Array of Event Data.
 * @param bool  $bool_has_subscription Is true if the event has a registration.
 * @param array $arr_ids Array with list, layer and target_groups IDs.
 * 
 * @throws Exception If there is an error during the insert process.
 *
 */
function verowa_event_db_insert( $arr_event, $bool_has_subscription, $arr_ids ) {
	global $wpdb;

	try {
		$str_content = wp_json_encode( $arr_event, JSON_UNESCAPED_UNICODE );

		// Hash everything. With the hash it is easy to check whether something has been changed.
		$str_hash                    = verowa_event_generate_hash( $arr_event, $arr_ids, $bool_has_subscription, $str_content );
		$int_eventdetail_template_id = get_option( 'verowa_default_eventdetails_template', 0 );
		$arr_templates               = verowa_get_single_template( $int_eventdetail_template_id );
		$arr_event_contents          = verowa_event_get_single_content( 0, $arr_templates, $arr_event );
		$str_default_language_code   = verowa_wpml_get_default_language_code();
		// Die ID muss pro Sprache eindeutig sein.
		$trid = null;

		foreach ( $arr_event_contents as $str_language_code => $arr_single_event_content ) {
			$wpdb->query( 'START TRANSACTION' );
			$str_content_html   = $arr_single_event_content['html'];
			$str_search_content = do_shortcode( $str_content_html );
			$str_search_content = preg_replace( '#<[^>]+>#', ' ', $str_search_content );

			$str_post_excerpt = strlen( trim( $arr_event['short_desc'] ) ) > 0 ?
				$arr_event['short_desc'] : $arr_event['long_desc'];

			if ( true === verowa_wpml_is_configured() ) {
				$str_post_name = $arr_event['event_id'] . '-' . $str_language_code;
			} else {
				$str_post_name = $arr_event['event_id'];
			}

			$arr_post = array(
				'post_title'   => wp_strip_all_tags( $arr_event['title'] ),
				'post_name'    => $str_post_name,
				'post_content' => $str_content_html,
				'post_excerpt' => $str_post_excerpt,
				'post_type'    => 'verowa_event',
				'post_status'  => 'publish',
			);

			// Insert the post into the database.
			$int_post_id = verowa_general_insert_custom_post(
				$arr_post,
				'verowa_events',
				'event_id',
				$arr_single_event_content['script'],
				$arr_single_event_content['head'] ?? ''
			);

			if ( ! is_wp_error( $int_post_id ) && true === verowa_wpml_is_configured() ) {
				$str_source_language_code = ( $str_language_code !== $str_default_language_code ) ?
					$str_default_language_code : null;

				if (null == $trid) {
					$get_language_args = array('element_id' => $int_post_id, 'element_type' => 'post_verowa_event' );
					$original_post_language_info = apply_filters( 'wpml_element_language_details', null, $get_language_args );
					$trid = $original_post_language_info->trid;
				} else {
					$set_language_args = array(
						'element_id' => $int_post_id,
						'element_type' => 'post_verowa_event',
						'trid' => $trid,
						'language_code' => $str_language_code,
						'source_language_code' => $str_source_language_code,
					);
					do_action( 'wpml_set_element_language_details', $set_language_args );
				}
			}

			add_post_meta( $int_post_id, 'verowa_event_datetime_from', $arr_event['date_from'] );

			if ( $str_language_code === $str_default_language_code ) {
				if ( ! is_wp_error( $int_post_id ) ) {
					$wpdb->insert(
						$wpdb->prefix . 'verowa_events',
						array(
							'event_id'           => intval( $arr_event['event_id'] ),
							'post_id'            => intval( $int_post_id ),
							'datetime_from'      => $arr_event['date_from'],
							'datetime_to'        => $arr_event['date_to'],
							'list_ids'           => $arr_ids['list_ids'] ?? '',
							'layer_ids'          => $arr_ids['layer_ids'] ?? '',
							'target_groups'      => $arr_ids['target_group_ids'] ?? '',
							'with_subscription'  => $bool_has_subscription,
							'content'            => $str_content,
							'search_content'     => $str_search_content,
							'hash'               => strval( $str_hash ),
							'deprecated_content' => 0,
							'deprecated'         => 0,
						),
						array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d' )
					);
				}
			} elseif ( is_wp_error( $int_post_id ) ) {
				throw new Exception( $int_post_id->get_error_message() );
			}

			$wpdb->query( 'COMMIT' );
		}
	} catch ( Exception $exception ) {
		$obj_debug = new Verowa_Connect_Debugger();
		$str_exception_msg = 'Fehler einfügen eines Events: ' . $exception->getMessage();
		$obj_debug->write_to_file( $str_exception_msg );
		// In the event of an error, the data is deleted.
		$wpdb->query( 'ROLLBACK' );
		throw new Exception( $str_exception_msg );
	}
}




/**
 * Updates an event in the DB
 *
 * @param array  $arr_event Array containing event details.
 * @param bool   $bool_has_subscription Flag indicating if the event has a subscription.
 * @param string $str_hash Hash value for the event.
 * @param array  $arr_ids Array containing list, layer, and target group IDs.
 *
 * @throws Exception If there is an error during the update process.
 */
function verowa_event_db_update( $arr_event, $bool_has_subscription, $str_hash, $arr_ids ) {
	global $wpdb;

	$wpdb->query( 'START TRANSACTION' );

	$str_update_exception = 'Update not possible ' . $arr_event['title'] . '(' . $arr_event['event_id'] . ')';
	$str_events_tablename = $wpdb->prefix . 'verowa_events';

	try {
		$int_eventdetail_template_id = get_option( 'verowa_default_eventdetails_template', 0 );
		$arr_templates               = verowa_get_single_template( $int_eventdetail_template_id );
		$arr_event_contents          = verowa_event_get_single_content( 0, $arr_templates, $arr_event );
		$str_default_language_code   = verowa_wpml_get_default_language_code();

		$query           = 'SELECT `post_id` FROM `' . $str_events_tablename . '` ' .
			'WHERE event_id = ' . $arr_event['event_id'] . ';';
		$current_post_id = $wpdb->get_var( $query );
		// Error during query.
		if ( null === $current_post_id ) {
			throw new Exception( '$current_post_id ist null (event.php)' );
		}
		
		if ( true === verowa_wpml_is_configured() ) {
			$get_language_args = array( 'element_id' => $current_post_id, 'element_type' => 'post_verowa_event' );
			$original_post_language_info = apply_filters( 'wpml_element_language_details', null, $get_language_args );
			$trid = $original_post_language_info->trid;
			$arr_translations = apply_filters( 'wpml_get_element_translations', null, $trid, 'post_verowa_event' );
		}
		
		foreach ( $arr_event_contents as $str_language_code => $arr_single_event_content ) {
			$post_id            = intval( $arr_translations[ $str_language_code ]->element_id ?? $current_post_id );
			$str_content_html   = $arr_single_event_content['html'];
			$str_search_content = do_shortcode( $str_content_html );
			$str_search_content = preg_replace( '#<[^>]+>#', ' ', $str_search_content );
			$arr_lang           = verowa_wpml_get_language_strings_for_events( $arr_event, $str_language_code );

			if ( 0 === $post_id ) {
				if ( true === verowa_wpml_is_configured() ) {
					$str_post_name = $arr_event['event_id'] . '-' . $str_language_code;
				} else {
					$str_post_name = $arr_event['event_id'];
				}

				$post = array(
					'post_title'   => wp_strip_all_tags( $arr_lang['title'] ),
					'post_name'    => $str_post_name,
					'post_content' => $str_content_html,
					'post_excerpt' => $arr_lang['post_excerpt'],
					'post_type'    => 'verowa_event',
					'post_status'  => 'publish',
				);

				// Insert the post into the database.
				$int_post_id = verowa_general_insert_custom_post( $post, 'verowa_events', 'event_id', 
					$arr_single_event_content['script'], $arr_single_event_content['head'] ?? '' );

			} else {
				$arr_post = array(
					'ID'           => intval( $post_id ?? 0 ),
					'post_title'   => wp_strip_all_tags( $arr_lang['title'] ),
					'post_content' => $str_content_html,
					'post_excerpt' => $arr_lang['post_excerpt'],
				);

				$int_post_id = verowa_general_update_custom_post( $arr_post, $arr_single_event_content['script'],
					$arr_single_event_content['head'] );
			}

			if ( $str_language_code === $str_default_language_code ) {
				if ( $int_post_id > 0 ) {
					update_post_meta( $int_post_id, 'verowa_event_datetime_from', $arr_event['date_from'] ?? '' );

					$arr_event_data = array(
						'post_id'            => intval( $int_post_id ),
						'datetime_from'      => $arr_event['date_from'],
						'datetime_to'        => $arr_event['date_to'],
						'list_ids'           => $arr_ids['list_ids'] ?? '',
						'layer_ids'          => $arr_ids['layer_ids'] ?? '',
						'target_groups'      => $arr_ids['target_group_ids'] ?? '',
						'with_subscription'  => $bool_has_subscription,
						'content'            => wp_json_encode( $arr_event, JSON_UNESCAPED_UNICODE ),
						'search_content'     => $str_search_content,
						'hash'               => $str_hash,
						'deprecated_content' => 0,
						'deprecated'         => 0,
					);

					$ret_update = $wpdb->update(
						$str_events_tablename,
						$arr_event_data,
						array(
							'event_id' => $arr_event['event_id'],
						),
						array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d' ),
						array( '%d' )
					);

					if ( false === $ret_update ) {
						// jumps directly into the catch block.
						throw new Exception( $str_update_exception );
					}
				}
			}
			$wpdb->query( 'COMMIT' );
		}
	} catch ( Exception $exception ) {
		$obj_debug = new Verowa_Connect_Debugger();
		$str_exception_msg = 'Fehler Update events: ' . $exception->getMessage();
		$obj_debug->write_to_file( $str_exception_msg );
		// In the event of an error, the changes are reversed.
		$wpdb->query( 'ROLLBACK' );
		throw new Exception( $str_exception_msg );
	}
}




/**
 * Delete a verowa event and its related WP_Post
 *
 * @param int $event_id The ID of the event to be deleted.
 *
 * @throws Exception If there is an error during the deletion process.
 */
function verowa_event_db_remove( $event_id ) {
	global $wpdb;

	if ( intval( $event_id ) > 0 ) {
		$str_events_tablename = $wpdb->prefix . 'verowa_events';
		$query                = 'SELECT `post_id` FROM `' . $str_events_tablename . '` ' .
			'WHERE event_id = ' . $event_id . ';';
		$int_post_id          = $wpdb->get_var( $query );

		$arr_translations = verowa_wpml_get_translations( $int_post_id );

		foreach ( $arr_translations as $obj_element_translation ) {
			if ( null !== $obj_element_translation->element_id ) {
				try {
					$wpdb->query( 'START TRANSACTION' );
					$ret_delete_post = verowa_delete_post( $obj_element_translation->element_id, 'verowa_event' );
					$ret_wp_delete   = $wpdb->delete(
						$str_events_tablename,
						array(
							'event_id' => $event_id, // value in column to target for deletion.
						),
						array( '%d' ), // format of value being targeted for deletion.
					);

					// Post or verowa_event cannot be deleted.
					if ( false === $ret_delete_post || null === $ret_delete_post ||
						false === $ret_wp_delete ) {
						// jumps directly into the catch block.
						throw new Exception( 'Post or verowa_event could not be deleted' );
					}

					$wpdb->query( 'COMMIT' );
				} catch ( Exception $exception ) {
					$wpdb->query( 'ROLLBACK' );
				}
			}
		}
	}
}




/**
 * Check if verowa_event has no related post and add it if missing.
 *
 * @param int   $event_id The ID of the event.
 * @param array $arr_event Event data to check.
 *
 * @return void
 */
function verowa_event_wpml_add_wp_post( $event_id, $arr_event ) {
	global $wpdb;

	$int_event_id = intval( $arr_event['event_id'] ?? 0 );
	if ( $int_event_id > 0 ) {
		$query           = 'SELECT `post_id` FROM `' . $wpdb->prefix . 'verowa_events` ' .
			'WHERE event_id = ' . $int_event_id . ';';
		$current_post_id = $wpdb->get_var( $query );
		if ($current_post_id > 0)
		{
			$ret_posts = $wpdb->get_results(
				'SELECT `ID`, `post_name` FROM ' . $wpdb->posts . ' Where `post_name` like "%' .
				$arr_event['event_id'] . '%";', ARRAY_A
			);
			$arr_post_ids = false === is_wp_error ($ret_posts) ? array_column ($ret_posts, 'ID') : array();
			$arr_post_name = false === is_wp_error ($ret_posts) ? array_column ($ret_posts, 'post_name') : array();
			$trid = apply_filters( 'wpml_element_trid', null, $current_post_id, 'post_verowa_event' );
			// Gibt ein array mit allen Übersetzungen zurück. Key ist der Sprachcode
			$arr_element_translations = apply_filters( 'wpml_get_element_translations', array(), $trid, 'post_verowa_event');

			$int_eventdetail_template_id = get_option( 'verowa_default_eventdetails_template', 0 );
			$arr_templates               = verowa_get_single_template( $int_eventdetail_template_id );
			$arr_event_content           = verowa_event_get_single_content( 0, $arr_templates, $arr_event );
			$str_default_language_code   = verowa_wpml_get_default_language_code();

			foreach ( $arr_event_content as $str_language_code => $single_content ) {
				$str_post_name = $arr_event['event_id'] . '-' . $str_language_code;
				//  Post is available, then we can jump to the next one
				if ( true === in_array( $str_post_name, $arr_post_name ) ) continue;

				$arr_lang         = verowa_wpml_get_language_strings_for_events( $arr_event, $str_language_code );
				$str_content_html = $single_content['html'];

				// Create post object.
				$post = array(
					'post_title'   => wp_strip_all_tags( $arr_lang['title'] ),
					'post_name'    => $str_post_name,
					'post_content' => $str_content_html,
					'post_excerpt' => $arr_lang['post_excerpt'],
					'post_type'    => 'verowa_event',
					'post_status'  => 'publish',
				);

				// only if the record in verowa_event has a post ID,
				// but no post with the ID exists. If the import_id is set.
				// Only works with the default language
				if ( $str_language_code == $str_default_language_code ) {
					if ( false === in_array( $current_post_id, $arr_post_ids ) ) {
						$post['import_id'] = $current_post_id;
					}
				} elseif ( true === isset( $arr_element_translations[ $str_language_code ] ) ) {
					$temp_post_id = $arr_element_translations[ $str_language_code ]->element_id ?? 0;
					if ( false === in_array( $temp_post_id, $arr_post_ids ) ) {
						$post['import_id'] = $temp_post_id;
					}
				}

				// Insert the post into the database.
				$int_post_id = verowa_general_insert_custom_post( $post, 'verowa_events', 'event_id', 
					$single_content['script'], $single_content['head'] ?? '' );
				if ( false === is_wp_error( $int_post_id ) ) {
					update_post_meta( $int_post_id, 'verowa_event_datetime_from', $arr_event['date_from'] ?? '' );
					$set_language_args = array(
						'element_id'           => $int_post_id,
						'element_type'         => 'post_verowa_event',
						'trid'                 => $trid,
						'language_code'        => $str_language_code,
						'source_language_code' => ( $str_language_code !== $str_default_language_code
							? $str_default_language_code : null ),
					);
					do_action( 'wpml_set_element_language_details', $set_language_args );
				}
			} // foreach
		} // Post ID
	} // Event_id
}




/**
 * Check if verowa_event has no related post and add it if missing.
 *
 * @param int   $event_id The ID of the event.
 * @param array $arr_event Event data to check.
 *
 * @return void
 */
function verowa_event_add_wp_post( $event_id, $arr_event ) {
	global $wpdb;
	$str_table_name  = $wpdb->prefix . 'verowa_events';
	$query           = 'SELECT `post_id` FROM `' . $str_table_name . '` ' .
			'WHERE event_id = ' . $arr_event['event_id'] . ';';
	$current_post_id = $wpdb->get_var( $query );

	// Check if the post still exists
	// if post_id is null we had to insert a post.
	$post_id       = null;
	$set_import_id = false;
	if ( $current_post_id > 0 ) {
		$post_id = $wpdb->get_var(
			'SELECT `ID` FROM ' . $wpdb->posts . ' Where `post_name` = ' .
			$arr_event['event_id']
		);

		if ( null === $post_id ) {
			$set_import_id = true;
		}
	}

	$int_eventdetail_template_id = get_option( 'verowa_default_eventdetails_template', 0 );
	$arr_templates               = verowa_get_single_template( $int_eventdetail_template_id );
	$arr_event_content           = verowa_event_get_single_content( 0, $arr_templates, $arr_event );

	foreach ( $arr_event_content as $str_language_code => $single_content ) {
		$arr_lang         = verowa_wpml_get_language_strings_for_events( $arr_event, $str_language_code );
		$str_content_html = $single_content['html'];
		$str_post_name = $arr_event['event_id'];
		
		// Create post object.
		$post = array(
			'post_title'   => wp_strip_all_tags( $arr_lang['title'] ),
			'post_name'    => $str_post_name,
			'post_content' => $str_content_html,
			'post_excerpt' => $arr_lang['post_excerpt'],
			'post_type'    => 'verowa_event',
			'post_status'  => 'publish',
		);

		// only if the record in verowa_event has a post ID,
		// but no post with the ID exists. If the import_id is set.
		if ( $set_import_id ) {
			$post['import_id'] = $current_post_id;
		}

		update_post_meta( $current_post_id, 'verowa_event_datetime_from', $arr_event['date_from'] ?? '' );

		// Insert the post into the database.
		verowa_general_insert_custom_post( $post, 'verowa_events', 'event_id', $single_content['script'] );
	}
}




/**
 * Retrieves events from the database based on the provided parameters.
 *
 * @param array    $arr_list_id List IDs to filter the events by.
 * @param int      $layer_id Layer ID to filter the events by.
 * @param DateTime $obj_date_from Optional. Starting date to filter the events from.
 * @param DateTime $obj_date_to Optional. Ending date to filter the events to.
 * @param string   $order_by Optional. Specifies the sorting order of the events. Default is 'datetime_from ASC'.
 * @param int      $int_limit Optional. Specifies the sorting order of the events. Default is 'datetime_from ASC'.
 * @param int      $int_offset Optional. Number of events to skip. Default is 0 (no offset).
 *
 * @return array[]  An array of event data retrieved from the database.
 */
function verowa_event_get_from_db( $arr_list_id, $layer_id, $obj_date_from = '', $obj_date_to = '',
	$order_by = 'datetime_from ASC', $int_limit = 0, $int_offset = 0 ) {
	global $wpdb;

	$arr_ret              = array();
	$str_events_tablename = $wpdb->prefix . 'verowa_events';
	$query                = 'SELECT * FROM `' . $str_events_tablename . '`';

	// Setup filter.
	$arr_filter = array();
	if ( $obj_date_from ) {
		$arr_filter [] = '`datetime_from` >= "' . $obj_date_from->format( 'Y-m-d' ) . ' 00:00:00"';
	}

	if ( '' !== $obj_date_to ) {
		$arr_filter [] = '`datetime_to` <= "' . $obj_date_to->format( 'Y-m-d' ) . ' 23:59:59"';
	}

	foreach ( $arr_list_id as $single_list_id ) {
		$arr_filter [] = '`list_ids` like "%;' . $single_list_id . ';%"';
	}

	if ( 0 !== count( $arr_filter ) ) {
		$query .= ' WHERE ';
		$query .= implode( ' AND ', $arr_filter );
	}

	$query .= ' ORDER BY ' . $order_by;

	if ( $int_limit > 0 ) {
		$query .= ' LIMIT ' . $int_limit . ' OFFSET ' . $int_offset;
	}

	$query .= ';';

	$result = $wpdb->get_results( $query );

	foreach ( $result as $obj_event ) {
		$arr_event = array(
			'event_id'          => $obj_event->event_id,
			'datetime_from'     => $obj_event->datetime_from,
			'datetime_to'       => $obj_event->datetime_to,
			'list_ids'          => $obj_event->list_ids,
			'layer_ids'         => $obj_event->layer_ids,
			'target_groups'     => $obj_event->target_groups,
			'with_subscription' => $obj_event->with_subscription,
		);

		$arr_content = json_decode( $obj_event->content, JSON_OBJECT_AS_ARRAY );

		if ( count( $arr_content['rooms'] ) > 0 ) {
			$arr_content['room'] = $arr_content['rooms'][0];
		}

		$arr_ret[] = array_merge( $arr_event, $arr_content );
	}

	return $arr_ret;
}




/**
 * Generates the MD5 hash for an event based on the event information.
 *
 * @param array  $arr_event Generates the MD5 hash for an event based on the event information.
 * @param array  $arr_ids An array containing IDs related to the event.
 * @param bool   $bool_has_subscription Indicates if the event has a subscription.
 * @param string $str_content $str_content Additional content related to the event.
 *
 * @return string The MD5 hash generated for the event.
 */
function verowa_event_generate_hash( $arr_event, $arr_ids, $bool_has_subscription, $str_content ) {
	$str_for_hash = strval ($arr_event['event_id']) .
		';df=' . $arr_event['date_from'] . ';dt=' . $arr_event['date_to'] .
		';li=' . ($arr_ids['list_ids'] ?? '') . ';ly=' . ($arr_ids['layer_ids'] ?? '') . ';tg=' . ($arr_ids['target_group_ids'] ?? '') .
		';sc=' . strval ($bool_has_subscription) . ';cn=' . $str_content;

	return md5( $str_for_hash );
}




/**
 * Set all events to deprecated
 *
 * @return void
 */
function verowa_events_db_set_to_deprecated() {
	global $wpdb;

	$str_date_format = 'Y-m-d H:i:s';

	// Calculate offset from server date time to WordPress date time
	// This ensures that the correct offset is always used.
	// Otherwise, events are not kept because they were deleted for the programme,
	// before they were over.
	$obj_curr_date  = new DateTime();
	$server_start   = $obj_curr_date->format( 'U' );
	$ms_time_offset = wp_date( 'U' ) - $server_start;

	// Tolerance in keeping the events.
	$ms_tolerance = 180;

	// Days from the Verowa Options settings.
	$int_keep_days         = get_option( 'verowa_keep_outdated_events_days', 14 );
	$datetime_to_keep_from = wp_date( $str_date_format, $server_start - $ms_time_offset - ( $int_keep_days * 86400 ) );
	$datetime_to_keep_to   = wp_date( $str_date_format, $server_start - $ms_time_offset + $ms_tolerance );

	$wpdb->query(
		$wpdb->prepare(
			'UPDATE `' . $wpdb->prefix . 'verowa_events` SET `deprecated` = 1' .
			' WHERE `datetime_to` <= %s' .
			' OR `datetime_from` >= %s;',
			$datetime_to_keep_from,
			$datetime_to_keep_to
		)
	);

	if ( VEROWA_DEBUG ) {
		echo esc_html( $wpdb->last_query );
	}
}




/**
 * Retrieve the event content from the database based on the event ID.
 *
 * @param int $int_event_id The ID of the event.
 * @return array|null The event content as an associative array, or null if not found.
 */
function verowa_event_db_get_content( $int_event_id ) {
	global $wpdb;

	$arr_event_data = array();

	$arr_event_data = $wpdb->get_results(
		$wpdb->prepare( 'SELECT `content` FROM `' .$wpdb->prefix . 'verowa_events` WHERE `event_id` = %d', $int_event_id ),
		ARRAY_A
	);

	return count( $arr_event_data ) > 0 ? json_decode( $arr_event_data[0]['content'], true ) : null;
}

/**
 * Retrieve the events from the database based on various parameters.
 *
 * @param string          $event_ids A comma-separated list of event IDs. e.g. '15,4,23'.
 * @param string          $list_ids A comma-separated list of event IDs. e.g.'15,4,23'.
 * @param string          $str_search_string The search string to filter events.
 * @param int             $max_events The maximum number of events to retrieve.
 * @param int             $int_offset The offset for pagination.
 * @param string|DateTime $obj_date The date to retrieve events from.
 * @return array|null|object An array of events as associative arrays, or null if not found.
 */
function verowa_events_db_get_agenda( $event_ids = '', $list_ids = '', $str_search_string = '',
	$max_events = 0, $int_offset = 0, $obj_date = 'now' ) {
	global $wpdb;

	$arr_event_data   = array();
	$arr_restrictions = array();

	// CWe,MPf: Prevent display event in the past.
	$obj_date_now       = new DateTime( 'now', wp_timezone() );
	$arr_restrictions[] = '`datetime_to` >= "' . $obj_date_now->format( 'Y-m-d H:i:s' ) . '"';

	if ( 'now' === $obj_date ) {
		$obj_date = new DateTime();
	}

	$arr_restrictions[] = '`datetime_to` >= "' . $obj_date->format( 'Y-m-d H:i:s' ) . '"';

	if ( strlen( $event_ids ) > 0 ) {
		$arr_restrictions[] = '`event_id` IN (' . $event_ids . ')';
	}

	if ( strlen( $list_ids ) > 0 ) {
		$arr_restrictions[] = verowa_helper_combine_query_for_ids( 'list_ids', $list_ids, 'AND' );
	}

	if ( '' !== $str_search_string ) {
		$arr_restrictions[] = '`search_content` like "%' . $str_search_string . '%"';
	}

	$str_restrictions = count( $arr_restrictions ) === 0 ? '1' : implode( ' AND ', $arr_restrictions );
	$str_limit        = $max_events > 0 ? ' LIMIT ' . $max_events : '';
	$str_limit       .= $int_offset > 0 ? ' OFFSET ' . $int_offset : '';

	$str_get_event_query = 'SELECT * FROM `' . $wpdb->prefix . 'verowa_events` ' .
		'WHERE ' . $str_restrictions . ' ORDER BY `datetime_from` ASC' . $str_limit;

	$arr_event_data   = $wpdb->get_results( $str_get_event_query, ARRAY_A );
	$int_count_events = count( $arr_event_data ?? array() );
	if ( $int_count_events > 0 ) {
		for ( $i = 0; $i < $int_count_events; $i++ ) {
			$arr_event_data[ $i ]['content'] = json_decode( $arr_event_data[ $i ]['content'], true );
		}
	}

	return $arr_event_data;
}




/**
 * Get the events from the DB (Do not use for agenda)
 *
 * Ids must be separated by commas ("15,4,23").
 * Restrictions can be combined, e.g. layers and target grous.
 * If multiple list, tgroup or layer ids are given, the events must match only one of them.
 *
 * The event’s content will be parsed to a nested array, e.g. ['content']['long_desc'].
 *
 * @param string          $event_ids Ids must be separated by commas ("15,4,23").
 * @param string          $list_ids Ids must be separated by commas ("15,4,23").
 * @param string          $filter_list_ids Ids must be separated by commas ("15,4,23"). It is a and condtion.
 * @param string          $tgoup_ids Ids must be separated by commas ("15,4,23").
 * @param string          $layer_ids Ids must be separated by commas ("15,4,23").
 * @param int             $max_events The maximum number of events to retrieve.
 * @param int             $int_offset The offset for pagination.
 * @param DateTime|string $obj_date string only 'now' supported.
 *
 * @return array|null
 */
function verowa_events_db_get_multiple( $event_ids = '', $list_ids = '', $filter_list_ids = '', $tgoup_ids = '', $layer_ids = '',
	$max_events = 0, $int_offset = 0, $obj_date = 'now', $date_from_format = 'Y-m-d H:i:s' ) {
	global $wpdb;

	$arr_event_data   = array();
	$arr_restrictions = array();

	if ( 'now' === $obj_date ) {
		$obj_date = new DateTime( 'now', wp_timezone() );
	}

	$arr_restrictions[] = '`datetime_to` >= "' . $obj_date->format( $date_from_format ) . '"';

	if ( strlen( $event_ids ) > 0 ) {
		$arr_restrictions[] = '`event_id` IN (' . $event_ids . ')';
	}

	if ( strlen( $list_ids ) > 0 ) {
		$arr_restrictions[] = verowa_helper_combine_query_for_ids( 'list_ids', $list_ids );
	}

	if ( strlen( $filter_list_ids ) > 0 ) {
		$arr_restrictions[] = verowa_helper_combine_query_for_ids( 'list_ids', $filter_list_ids, 'AND' );
	}

	if ( strlen( $tgoup_ids ) > 0 ) {
		$arr_restrictions[] = verowa_helper_combine_query_for_ids( 'target_groups', $tgoup_ids );
	}

	if ( strlen( $layer_ids ) > 0 ) {
		$arr_restrictions[] = verowa_helper_combine_query_for_ids( 'layer_ids', $layer_ids );
	}

	$str_restrictions = 0 == count( $arr_restrictions ) ? '1' : implode( ' AND ', $arr_restrictions );
	$str_limit        = $max_events > 0 ? ' LIMIT ' . $max_events : '';
	$str_limit       .= $int_offset > 0 ? ' OFFSET ' . $int_offset : '';

	$str_get_event_query = 'SELECT * FROM `' . $wpdb->prefix . 'verowa_events` ' .
		'WHERE ' . $str_restrictions . ' ORDER BY `datetime_from` ASC' . $str_limit;

	$arr_event_data  = $wpdb->get_results( $str_get_event_query, ARRAY_A );
	$int_count_event = count( $arr_event_data ?? array() );
	if ( $int_count_event > 0 ) {
		for ( $i = 0; $i < $int_count_event; $i++ ) {
			$arr_event_data[ $i ]['content'] = json_decode( $arr_event_data[ $i ]['content'], true );
		}
	}

	return $arr_event_data;
}




/**
 * Return classes for the Weekend
 *
 * @param array $event Event data.
 *
 * @return string
 */
function get_weekend_classes( $event ) {
	$verowa_date_from = new DateTime( $event['date_from'] );
	$int_weekday      = date( 'N', $verowa_date_from->format( 'U' ) );
	$str_classes      = '';

	if ( 6 === $int_weekday ) {
		$str_classes = ' weekend weekend-bg we-saturday';
	}

	if ( 7 === $int_weekday ) {
		$str_classes = ' weekend weekend-bg we-sunday';
	}

	return $str_classes;
}




/**
 * Action handler for genesis_before_loop.
 */
function verowa_fake_breadcrumbs() {
	global $breadcrumb_title;

	echo '<div class="breadcrumb" itemprop="breadcrumb" itemscope="" ' .
	'itemtype="http://schema.org/BreadcrumbList">' . esc_html( __( 'Current page', 'verowa-connect' ) ) . ': ' .
	'<span class="breadcrumb-link-wrap" itemprop="itemListElement" itemscope="" ' .
	'itemtype="http://schema.org/ListItem"><a href="' . esc_url( get_bloginfo( 'url' ) ) . '" itemprop="item">' .
	'<span itemprop="name">' . esc_html( __( 'Frontpage', 'verowa-connect' ) ) . '</span></a></span> ' .
	'<span aria-label="breadcrumb separator">/</span> ' .
	'<span class="breadcrumb-link-wrap" itemprop="itemListElement" itemscope="" ' .
	'itemtype="http://schema.org/ListItem"><a href="' . esc_url( get_bloginfo( 'url' ) ) . '/agenda" itemprop="item">' .
	'<span itemprop="name">' . esc_html( __( 'Agenda', 'verowa-connect' ) ) . '</span></a></span> ' .
	'<span aria-label="breadcrumb separator">/</span> ' .
	esc_html( $breadcrumb_title ) . '</div>';
}




/**
 *
 * Values in array
 * int_count_event_posts, int_verowa_events, int_count_person_posts, int_verowa_persons
 *
 * @return array<int>
 */
function verowa_get_custom_posts_count() {
	global $wpdb;

	$query = 'SELECT COUNT(`ID`) AS `anzahl` FROM `' . $wpdb->posts . '`' .
			'WHERE `post_type` = "verowa_event";';

	$int_count_event_posts = intval( $wpdb->get_var( $query ) ?? 0 );

	$query             = 'SELECT COUNT(`event_id`) AS `anzahl` FROM `' . $wpdb->prefix . 'verowa_events`;';
	$int_verowa_events = intval( $wpdb->get_var( $query ) ?? 0 );

	$query                  = 'SELECT COUNT(`ID`) AS `anzahl` FROM `' . $wpdb->posts . '`' .
			'WHERE `post_type` = "verowa_person";';
	$int_count_person_posts = intval( $wpdb->get_var( $query ) ?? 0 );

	$query              = 'SELECT COUNT(`person_id`) AS `anzahl` FROM `' . $wpdb->prefix .
		'verowa_person` WHERE `web_visibility` = "FULL";';
	$int_verowa_persons = intval( $wpdb->get_var( $query ) ?? 0 );

	return array(
		'int_count_event_posts'  => $int_count_event_posts,
		'int_verowa_events'      => $int_verowa_events,
		'int_count_person_posts' => $int_count_person_posts,
		'int_verowa_persons'     => $int_verowa_persons,
	);
}
