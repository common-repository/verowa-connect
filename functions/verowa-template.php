<?php
/**
 * Collection of functions to work with Verowa templates
 *
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since 2.13.0
 * @package Verowa Connect
 * @subpackage TEMPLATE
 */

use Picture_Planet_GmbH\Verowa_Connect\VEROWA_TEMPLATE;

/**
 *
 *
 * @param string $str_display_where widget or content.
 *
 * @return stdClass[]
 */
function verowa_get_templates( $arr_restriction ) {
	global $wpdb;
	// if ( true === verowa_wpml_is_configured() ) {

	// } else {

	// }
	$arr_restriction[] = '`deprecated` = 0';

	$query = 'SELECT * FROM `' . $wpdb->prefix . 'verowa_templates`';
	$query .= ' WHERE ' . implode( ' AND ', $arr_restriction );
	$query .= ';';

	$arr_template_data = $wpdb->get_results( $query, OBJECT_K ); // db call ok.
	return $arr_template_data;

}




/**
 * Get all templates from the specific translation group
 * The translation group is determined over the template id
 * If the language code is set only the requested template object will be returned.
 * If $str_display_where is set, the requested template only return if ist fit the requirements.
 *
 * @param int    $int_template_id ID of a specific template.
 * @param string $str_language_code
 * @param string $str_display_where widget or content.
 *
 * @return VEROWA_TEMPLATE[]|VEROWA_TEMPLATE
 */
function verowa_get_single_template( $int_template_id, $str_language_code = 'all', $str_display_where = '' ) {
	$mixed_return = null;
	if ( false === verowa_wpml_is_configured() ) {
		$mixed_return = verowa_get_single_template_single_lang( $int_template_id, $str_display_where );
		if ( 'all' === $str_language_code ) {
			$mixed_return = array( 'de' => $mixed_return );
		}
	} else {
		$mixed_return = verowa_get_single_template_wpml( $int_template_id, $str_language_code, $str_display_where );
	}
	return $mixed_return;
}




/**
 * Ohne WPML
 *
 * @param int    $int_template_id ID of a specific template.
 * @param string $str_display_where widget or content.
 *
 * @return VEROWA_TEMPLATE
 */
function verowa_get_single_template_single_lang( $int_template_id, $str_display_where = '' ) {
	global $wpdb;

	$int_template_id = intval( $int_template_id );

	if ( intval( $int_template_id ) > 0 ) {
		$str_cache_key = 'verowa_single_template_2130' . $int_template_id;
		$arr_template_data = wp_cache_get( $str_cache_key );

		if ( false === $arr_template_data ) {
			$arr_template_data = array();

			$query = 'SELECT * FROM `' . $wpdb->prefix . 'verowa_templates`';

			// Add no further restriction, this query fills the cache
			$arr_restriction = array();
			$arr_restriction[] = '`template_id` = "' . $int_template_id . '"';
			$arr_restriction[] = '`deprecated` = 0';

			$query .= ' WHERE ' . implode( ' AND ', $arr_restriction );
			$query .= ';';

			$arr_template_data = $wpdb->get_results( $query, ARRAY_A ); // db call ok.

			if ( '' === $wpdb->last_error ) {
				wp_cache_set( $str_cache_key, $arr_template_data );
			}
		}

		$obj_templates = new VEROWA_TEMPLATE( $arr_template_data[0] ?? [] );

		if ( '' !== $str_display_where ) {
			$obj_templates = $obj_templates->display_where === $str_display_where ? $obj_templates : null;
		} else {
			$obj_templates;
		}
	} else {
		$obj_templates = null;
	}
	return $obj_templates;
}




/**
 *
 * @param int    $int_template_id ID of a specific template.
 * @param string $str_display_where widget or content.
 *
 * @return VEROWA_TEMPLATE[]|VEROWA_TEMPLATE
 */
function verowa_get_single_template_wpml( $int_template_id, $str_language_code = 'all', $str_display_where = '' ) {
	global $wpdb;
	$mixed_return = null;
	$arr_templates = array();

	$int_template_id = intval( $int_template_id );

	if ( intval( $int_template_id ) > 0 ) {
		$trid = verowa_wpml_get_translations_trid( $int_template_id );
		if ( $trid > 0 ) {
			$arr_elements = verowa_wpml_get_custom_element_language( array( 'trid' => $trid ) );
			if ( count( $arr_elements ?? array() ) > 0 ) {
				$str_cache_key = 'verowa_single_template_' . $trid;
				$arr_element_id = array_column( $arr_elements, 'element_id' );
				$arr_template_data = wp_cache_get( $str_cache_key );

				if ( false === $arr_template_data ) {
					$arr_template_data = array();

					$query = 'SELECT * FROM `' . $wpdb->prefix . 'verowa_templates`';
					$arr_restriction = array();
					$arr_restriction[] = '`template_id` in (' . implode( ', ', $arr_element_id ) . ')';
					$arr_restriction[] = '`deprecated` = 0';

					$query .= ' WHERE ' . implode( ' AND ', $arr_restriction );
					$query .= ';';

					$arr_template_data = $wpdb->get_results( $query, OBJECT_K ); // db call ok.

					if ( '' === $wpdb->last_error ) {
						$arr_template_data;
						wp_cache_set( $str_cache_key, $arr_template_data );
					}
				}

				$arr_default_template = array();
				// Get the default template.
				foreach ( $arr_elements as $obj_single_ele ) {
					if ( null === $obj_single_ele->source_language_code ) {
						$arr_default_template = (array) ( $arr_template_data[ $obj_single_ele->element_id ] ?? array() );
						break;
					}
				}

				// Assemble the template collection.
				foreach ( $arr_elements as $obj_single_ele ) {
					$str_lang = $obj_single_ele->language_code;
					if ( null !== $obj_single_ele->source_language_code ) {
						$arr_lang_templ_data = (array) ( $arr_template_data[ $obj_single_ele->element_id ] ?? array() );
						if ( count( $arr_lang_templ_data ) > 0 ) {
							$arr_templates[ $str_lang ] = new VEROWA_TEMPLATE( $arr_default_template, $arr_lang_templ_data );
						}
					} else {
						$arr_templates[ $str_lang ] = new VEROWA_TEMPLATE( $arr_default_template );
					}
				}
			}
		}

		if ( 'all' === $str_language_code ) {
			if ( '' === $str_display_where ) {
				$mixed_return = $arr_templates;
			} else {
				$mixed_return = $arr_default_template['display_where'] === $str_display_where ?
					$arr_templates : null;
			}
		} else {
			if ( '' === $str_display_where ) {
				$mixed_return = $arr_templates[ $str_language_code ] ?? new VEROWA_TEMPLATE( $arr_default_template );
			} else {
				$arr_temp_tpl = $arr_templates[ $str_language_code ] ?? new VEROWA_TEMPLATE( $arr_default_template );
				$str_tpl_display_where = is_object($arr_temp_tpl) ? $arr_temp_tpl->display_where : $arr_temp_tpl['display_where'];
				$mixed_return = $str_tpl_display_where === $str_display_where ? $arr_temp_tpl : null;
			}
		}
	}
	return $mixed_return;
}




/**
 * Parses the template and fills in the values
 *
 * HINT: This function is still designed for insane speed.
 * If this becomes an issue, the following concept can be implemented:
 * 1. Pre-process a function that searches for all placeholders using regex in the template (within the IFs and the {Texts}!) and stores them in an array. (This might incur an initial overhead, but it saves us 50 times later. Moreover, it ensures our code remains safe from excessive placeholder variations, as we only spend time on additional placeholders when they are effectively used. Nice!)
 * 2. Then, in the foreach loop, only populate the placeholders that are actually used by the template.
 * (Since we're not adding unnecessary items to the placeholder array,
 * we won't need to perform unnecessary str_replace operations later!)
 *
 * @param string $str_code Html that contains Placeholders.
 * @param array  $arr_placeholders The data which replace the Placeholders in str_code.
 * @return mixed.
 */
function verowa_parse_template( $str_code, $arr_placeholders ) {
	$str_cond_beginning = '[[?';
	$str_cond_ending = ']]';
	$str_else_separator = '||';
	$str_cond_separator = ':';

	// examples in templates:
	// "<h1>{TITLE}</h1>"
	// "Contact: {ORGANIZER_NAME}"
	// "[[?COORGANIZER_NAMES:<p class="coorgs">Contributors: {COORGANIZER_NAMES}</p>]]"
	// "Art: [[?LAYER_IDS % ;1;:Church service||Event]]".
	if ( strlen( $str_code ) > 0 ) {

		// HINT: Whenever we need special conditions (e.g. whether something is > 1) we just prepare another key
		// (e.g. HAS_MULTIPLE_COORGANIZERS).

		// LATER: Tests should also be negatible with ! and combinable with AND or OR.

		// the conditions are processed only if there is the same amount of opening and closing brackets.
		$count_cond_beginnings = substr_count( $str_code, $str_cond_beginning );
		$count_cond_endings = substr_count( $str_code, $str_cond_ending );

		if ( $count_cond_beginnings === $count_cond_endings ) {
			while ( $count_cond_beginnings > 0 ) {
				// we always start with the innermost condition; so we search for the first closing brackets and
				// the corresponding opening brackets––they are the closest ones on the left-hand side.
				$pos_first_cond_ending = strpos( $str_code, $str_cond_ending );

				$pos_matching_cond_beginning = strrpos(
					substr( $str_code, 0, $pos_first_cond_ending ),
					$str_cond_beginning
				);

				// now we want a substring with the whole condition but without the brackets, e.g.
				// "LOCATION:<div class="event_location">{LOCATION}</div>".
				$str_curr_condition = substr(
					$str_code,
					$pos_matching_cond_beginning + strlen( $str_cond_beginning ),
					$pos_first_cond_ending - $pos_matching_cond_beginning - strlen( $str_cond_beginning )
				);

				// this string we divide at the position of the separator in the key (left) and the content (right)
				// (if there is no separator, we flush the whole part).
				$pos_first_cond_separator = strpos( $str_curr_condition, $str_cond_separator );
				$str_content = '';

				if ( false !== $pos_first_cond_separator ) {
					$str_key = trim( substr( $str_curr_condition, 0, $pos_first_cond_separator ) );

					$condition_is_true = false;

					// do we find a "="? (e.g. "LOCATION=Ref. Kirche Brugg");
					// if so, we try to match the value with the value of the corresponding placeholder.
					if ( strpos( $str_key, '=' ) > 0 ) {
						$arr_key_parts = explode( '=', $str_key );
						$arr_key_parts[0] = trim( $arr_key_parts[0] );
						if ( array_key_exists( $arr_key_parts[0], $arr_placeholders ) &&
							trim( $arr_key_parts[1] === $arr_placeholders[ $arr_key_parts[0] ] )
						) {
							$condition_is_true = true;
						}
					} elseif ( strpos( $str_key, '%' ) > 0 ) {
						// else we look if we find a "%" (e.g. "LAYER_IDS % ;6;");
						// if so, we try to find the value as part of the correspondig placeholder value
						// (this can be an array or a string).
						$arr_key_parts = explode( '%', $str_key );
						$arr_key_parts[0] = trim( $arr_key_parts[0] );
						$arr_key_parts[1] = trim( $arr_key_parts[1] );
						if ( array_key_exists( $arr_key_parts[0], $arr_placeholders ) ) {
							if ( is_array( $arr_placeholders[ $arr_key_parts[0] ] ) ) {
								// for e.g. PROFESSION % Pfr.||Pfrin.
								if ( false !== strpos ($arr_key_parts[1], '||') ) {
									$arr_or_vals = explode ('||', $arr_key_parts[1]);
									foreach ( $arr_or_vals as $str_single_val) {
										$condition_is_true = (true === in_array(trim( $str_single_val ), $arr_placeholders[$arr_key_parts[0]], true));
										if (true === $condition_is_true) {
											break;
										}
									}
								} else {
									$condition_is_true = (true === in_array( $arr_key_parts[1], $arr_placeholders[$arr_key_parts[0]], true) );
								}
							} else {
								// for e.g. PROFESSION % Pfr.||Pfrin.
								if ( false !== strpos( $arr_key_parts[1], '||' ) ) {
									$arr_or_vals = explode ( '||', $arr_key_parts[1] );
									foreach ( $arr_or_vals as $str_single_val ) {
										$condition_is_true = (false !== strpos ($arr_placeholders[$arr_key_parts[0]], $str_single_val));
										if ( true === $condition_is_true ) {
											break;
										}
									}
								} else {
									$condition_is_true = ( false !== strpos ($arr_placeholders[$arr_key_parts[0]], $arr_key_parts[1]) );
								}
							}
						}
					} // otherwise we just need to check if the value is not empty.
					elseif ( strlen( $arr_placeholders[ $str_key ] ?? '' ) > 0 ) {
						$condition_is_true = true;
					}

					// now we look, if the content has an alternative content in case the condition fails.
					$str_content_raw = substr(
						$str_curr_condition,
						$pos_first_cond_separator + strlen( $str_cond_separator )
					);

					$pos_else_separator = strpos( $str_content_raw, $str_else_separator );

					if ( false === $pos_else_separator ) {
						$str_content_true = $str_content_raw;
						$str_content_false = '';
					} else {
						$str_content_true = substr( $str_content_raw, 0, $pos_else_separator );
						$str_content_false = substr(
							$str_content_raw,
							$pos_else_separator + strlen( $str_else_separator )
						);
					}

					// If the condition matches we take its content; otherwise we take the alternative.
					$str_content = true === $condition_is_true ? $str_content_true : $str_content_false;
				}

				// then we concat the string again.
				$str_code = substr( $str_code, 0, $pos_matching_cond_beginning ) . $str_content .
					substr( $str_code, $pos_first_cond_ending + strlen( $str_cond_ending ) );

				// Now there’s one condition less.
				$count_cond_beginnings--;
			}
		} else {
			return __( 'Error in template: unequal number of beginnings and ends of conditions.', 'verowa-connect' );
		}

		// ** Replace placeholder, e.g. "{LOCATION}".
		$arr_search = array();
		$arr_replace = array();

		foreach ( $arr_placeholders as $key => $value ) {
			$arr_search[] = '{' . $key . '}';
			$arr_replace[] = $value;
		}

		$str_code = str_replace( $arr_search, $arr_replace, $str_code );
	}

	return $str_code;
}




/**
 * Insert template in DB
 *
 * @param array $arr_post $_POST array parsed to the function.
 *
 * @return void
 */
function verowa_insert_template_in_db( $arr_post ) {
	global $wpdb;
	$obj_template = new VEROWA_TEMPLATE();
	$obj_template->get_data_from_post( $arr_post );

	// db call ok; no-cache ok.
	$ret_insert = $wpdb->insert(
		$wpdb->prefix . 'verowa_templates',
		$obj_template->to_array(),
		$obj_template->get_format_array()
	);

	if ( false !== $ret_insert ) {
		$int_template_id = $wpdb->insert_id;
		// If not set, then it is the default languages and must be zero!
		$source_language_code = isset( $arr_post['source_language_code'] ) ? sanitize_text_field( $arr_post['source_language_code'] ) : null;
		if ( true === verowa_wpml_is_configured() ) {
			$arr_verowa_translations = array(
				'element_type' => 'record_verowa_template',
				'element_id' => $int_template_id,
				'trid' => intval( $arr_post['trid'] ),
				'language_code' => sanitize_text_field( $arr_post['language_code'] ?? 0 ),
				'source_language_code' => $source_language_code,
			);
			verowa_wpml_set_custom_element_language( $arr_verowa_translations );
		}
		// TODO: P2 Cache für Templates wieder einführen
	}
}




/**
 * Update template in DB
 *
 * @param array $arr_template $_POST array parsed to the function.
 *
 * @return void
 */
function verowa_update_template_in_db( $arr_template ) {
	global $wpdb;
	$obj_template = new VEROWA_TEMPLATE();
	$obj_template->get_data_from_post( $arr_template );
	$language_code = $arr_template['language_code'] ?? 'de';
	$arr_old_template = verowa_get_single_template( $obj_template->template_id, $language_code );

	// TODO: P2 Cache freigeben beim löschen des Templates.
	wp_cache_delete( 'verowa_single_template_2130' . $obj_template->template_id );
	verowa_save_log( 'update_template', wp_json_encode( $arr_old_template ) );

	$wpdb->update(
		$wpdb->prefix . 'verowa_templates',
		$obj_template->to_array(),
		array(
			'template_id' => $obj_template->template_id,
		),
		$obj_template->get_format_array(),
		array( '%d' )
	);
}




/**
 * Return an unsorted list option with a deleted flag in the Text.
 *
 * @param int $int_template_id ID of an specific template.
 * @return string
 */
function verowa_get_deleted_template_option( $int_template_id ) {
	global $wpdb;
	$str_return = '';
	// UNDONE: prüfen, ob die Warnung mit WPML noch funktioniert

	if ( intval( $int_template_id ) > 0 ) {
		$str_return = '<option value="' . $int_template_id . '">';
		$arr_template_data = array();
		$arr_template_data = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM `' . $wpdb->prefix . 'verowa_templates` WHERE `template_id` = %d;',
				$int_template_id
			),
			ARRAY_A
		);

		$arr_template = count( $arr_template_data ) > 0 ? $arr_template_data[0] : array();
		if ( count( $arr_template ) > 0 ) {
			$str_return .= _x( 'Deleted', 'Prefix for option name. A template name follows.', 'verowa-connect' ) . ': ' . ( esc_attr( stripcslashes( $arr_template['template_name'] ) ) );
		} else {
			$str_return .= _x( 'Deleted', 'Prefix for option name. A template id follows.', 'verowa-connect' ) . ': ' . __( 'Template', 'verowa-connect' ) . ' ' . $int_template_id;
		}
		$str_return .= '</option>';
	}

	return $str_return;

}




/**
 * Auxiliary function to avoid dependency on the subscription and rental forms.
 *
 * @param string $str_name Insert in the name attribute of the select tag.
 * @param array  $arr_options The given option in the tag.
 * @param string $str_ff_value The Value witch is selected.
 * @param int    $is_disabled Determinate if the input control is disabled.
 *
 * @return void
 */
function verowa_show_dropdown( $str_name, $arr_options, $str_ff_value, $is_disabled = false ) {
	$str_display = true === $is_disabled ? ' disabled ' : '';
	echo '<select name="' . esc_attr( $str_name ) . '"' . esc_html( $str_display ) . '>';

	foreach ( $arr_options as $str_value => $str_display ) {
		$str_selected = $str_value === $str_ff_value ? ' selected ' : '';
		echo '<option value="' . esc_attr( $str_value ) . '" ' . esc_html( $str_selected ) . '>' .
			esc_html( $str_display ) . '</option>';
	}

	echo '</select>';
}




/**
 * Print the drop-down to select a template
 *
 * @param string $str_template_type .
 * @param int    $int_selected_template .
 * @param array  $args show_empty_option (bool)
 *                     ddl_name (string).
 *
 * @return array
 */
function verowa_show_template_dropdown( $str_template_type, $int_selected_template, $args = array() ) {
	$str_return = '';
	$str_ddl_name = key_exists( 'ddl_name', $args ) ? $args['ddl_name'] : 'verowa_select_template_' . $str_template_type;

	/**
	 * Default is true because the option did not exist earlier
	 *
	 * @var boolean $is_show_empty_option
	 */
	$is_show_empty_option = filter_var( $args['show_empty_option'] ?? true, FILTER_VALIDATE_BOOLEAN );
	$arr_templates = verowa_get_templates( array( '`type` = "' . $str_template_type . '"' ) );
	$arr_template_ids = array_keys( $arr_templates );
	$str_select_class = '';
	$str_template_del_options = '';
	$arr_template_ids = array_map( 'intval', $arr_template_ids );
	$int_selected_template = intval( $int_selected_template );
	if ( 0 !== $int_selected_template &&
		false === in_array( $int_selected_template, $arr_template_ids, true )
	) {
		// Template has been deleted.
		$str_select_class .= 'class="verowa-select-error-first-child" ';
		$str_template_del_options = verowa_get_deleted_template_option( $int_selected_template );
	}

	$str_return .= '<select ' . $str_select_class . 'name="' . $str_ddl_name . '">';
	$str_return .= $str_template_del_options;

	if ( $is_show_empty_option ) {
		$str_return .= '<option value=""></option>';
	}

	foreach ( $arr_templates as $single_template ) {
		$str_selected = intval( $single_template->template_id ) === $int_selected_template ? 'selected' : '';

		$str_return .= '<option value="' . $single_template->template_id . '" ' . $str_selected . '>' .
			esc_attr( stripcslashes( $single_template->template_name ) ) . '</option>';
	}

	$str_return .= '</select>';

	return array(
		'content' => $str_return,
		'kses_allowed' => array(
			'label' => array(
				'class' => array(),
			),
			'select' => array(
				'name' => array(),
				'style' => array(),
				'disabled' => array(),
			),
			'option' => array(
				'value' => array(),
				'selected' => array(),
			),
			'i' => array(
				'title' => array(),
				'class' => array(),
			),
		),
	);
}




/**
 * Returns the current and deprecated placeholders of the specified template type.
 *
 * @param string $str_template_type Define witch placeholder are return.
 * @param string $str_template_part Could set to 'entry', 'header' OR 'footer'.
 *
 * @return array
 */
function verowa_get_placeholder_desc( $str_template_type, $str_template_part ) {
	switch ( $str_template_type ) {
		case 'eventlist':
			switch ( $str_template_part ) {
				case 'entry':
					$arr_placeholder = array(
						'current' => array(
							'date_time' => array(
								'title' => __( 'Date/Time', 'verowa-connect' ),
								'pcl' => array(
									'DATE_FROM_8',
									'DATETIME_LONG',
									'DATE_FROM_LONG',
									'TIME_MIXED_LONG',
									'WEEKDAY',
									'DATETIME_FROM_LONG',
									'DATETIME_FROM_LONGMONTH',
									'WEEKDAY_SHORT',
									'DAY_FROM_NB',
									'MONTH_FROM_NB',
									'MONTH_FROM_NAME',
									'YEAR_FROM',
									'HOUR_FROM',
									'HOUR_FROM_2',
									'MINUTE_FROM',
									'DAY_TO_NB',
									'MONTH_TO_NB',
									'YEAR_TO',
									'HOUR_TO',
									'HOUR_TO_2',
									'MINUTE_TO',
								),
							),
							'text' => array(
								'title' => __( 'Text', 'verowa-connect' ),
								'pcl' => array(
									'IMAGE_CAPTION',
									'IMAGE_SOURCE_TEXT',
									'IMAGE_SOURCE_URL',
									'TITLE',
									'TOPIC',
									'SHORT_DESC',
								),
							),
							'city' => array(
								'title' => __( 'Location', 'verowa-connect' ),
								'pcl' => array(
									'LOCATION',
									'LOCATION_WITH_ROOM',
								),
							),
							'persons' => array(
								'title' => __( 'Persons', 'verowa-connect' ),
								'pcl' => array(
									'ORGANIZER_NAME',
									'COORGANIZER_NAMES',
									'COORGANIZERS_WITH_ORGANIZER',
								),
							),
							'structure' => array(
								'title' => __( 'Structure', 'verowa-connect' ),
								'pcl' => array(
									'CATEGORIES',
									'EVENT_ID',
									'LAYER_IDS',
									'LIST_IDS',
									'SUBS_STATE',
									'TARGET_GROUP_IDS',
									array(
										'name' => 'EVENT_DETAILS_URL',
										'helptext' => '',
									),
								),
							),
							'others' => array(
								'title' => __( 'Misc.', 'verowa-connect' ),
								'pcl' => array(
									'DETAILS_BUTTON_TEXT',
									'ICAL_EXPORT_URL',
									'IMAGE_URL',
									'FILE_LIST',
									'SUBS_BUTTON',
								),
							),
						),
						'deprecated' => array(
							array(
								'old' => 'EVENT_TITLE',
								'new' => 'TITLE',
								'type' => 'changed',
							),
							array(
								'old' => 'EVENT-CATS',
								'new' => 'CATEGORIES',
								'type' => 'changed',
							),
							array(
								'old' => 'EVENT_CATS',
								'new' => 'CATEGORIES',
								'type' => 'changed',
							),
							array(
								'old' => 'DATE_FROM',
								'new' => 'DATE_FROM_8',
								'type' => 'changed',
							),
							array(
								'old' => 'EVENT_DATE',
								'new' => 'DATE_FROM_LONG',
								'type' => 'changed',
							),
							array(
								'old' => 'EVENT_TIME',
								'new' => 'TIME_MIXED_LONG',
								'type' => 'changed',
							),
							array(
								'old' => 'LOCATIONS',
								'new' => 'LOCATION',
								'type' => 'changed',
							),
							array(
								'old' => 'SUPSCRIPTION_BUTTON',
								'new' => 'SUBS_BUTTON',
								'type' => 'changed',
							),
							array(
								'old' => 'SUPS_BUTTON',
								'new' => 'SUBS_BUTTON',
								'type' => 'changed',
							),
						),
					);
					break;

				case 'header':
					$arr_placeholder = array();
					break;

				case 'footer':
					$arr_placeholder = array();
					break;

			}
			break;

		case 'eventdetails':
			switch ( $str_template_part ) {
				case 'entry':
					$arr_placeholder = array(
						'current' => array(
							'date_time' => array(
								'title' => __( 'Date/Time', 'verowa-connect' ),
								'pcl' => array(
									'DATE_FROM_LONG',
									'DATETIME_FROM_LONG',
									'DATETIME_FROM_LONGMONTH',
									'DATETIME_FROM_LONG_WITH_TO_TIME',
									'TIME_MIXED_LONG',
									'WEEKDAY_SHORT',
									'DAY_FROM_NB',
									'MONTH_FROM_NB',
									'MONTH_FROM_NAME',
									'YEAR_FROM',
									'HOUR_FROM',
									'HOUR_FROM_2',
									'MINUTE_FROM',
									'DAY_TO_NB',
									'MONTH_TO_NB',
									'YEAR_TO',
									'HOUR_TO',
									'HOUR_TO_2',
									'MINUTE_TO',
								),
							),
							'text' => array(
								'title' => __( 'Text', 'verowa-connect' ),
								'pcl' => array(
									'ADD_TEXT_1',
									'ADD_TEXT_2',
									'ADD_TEXT_3',
									'ADD_TEXT_4',
									'IMAGE_CAPTION',
									'IMAGE_SOURCE_TEXT',
									'IMAGE_SOURCE_URL',
									'LONG_DESC',
									'SHORT_DESC',
									'SUBS_INFO',
									'TITLE',
									'TOPIC',
								),
							),
							'city' => array(
								'title' => __( 'Location', 'verowa-connect' ),
								'pcl' => array(
									'LOCATION',
									'LOCATION_LINKED',
									'LOCATION_WITH_ROOM',
									'LOCATION_WITH_ROOM_LINKED',
								),
							),
							'persons' => array(
								'title' => __( 'Persons', 'verowa-connect' ),
								'pcl' => array(
									'ORGANIZER_ID',
									'ORGANIZER_NAME',
									'COORGANIZERS',
									'COORGANIZERS_WITH_ORGANIZER',
								),
							),
							'structure' => array(
								'title' => __( 'Structure', 'verowa-connect' ),
								'pcl' => array(
									'EVENT_ID',
									'LIST_IDS',
									'SUBS_STATE',
									'TARGET_GROUP_IDS',
								),
							),
							'others' => array(
								'title' => __( 'Misc.', 'verowa-connect' ),
								'pcl' => array(
									'BAPTISM_OFFER',
									'CATERING',
									'COLLECTION',
									'CHILDCARE',
									'FILE_LIST',
									'ICAL_EXPORT_URL',
									'IMAGE_URL',
									'LAYER_IDS',
									'WITH_SACRAMENT',
								),
							),
						),
						'deprecated' => array(
							array(
								'old' => 'EVENT_TITLE',
								'new' => 'TITLE',
								'type' => 'changed',
							),
							array(
								'old' => 'EVENT_DATETIME',
								'new' => 'DATETIME_FROM_LONG',
								'type' => 'changed',
							),
							array(
								'old' => 'LOCATIONS',
								'new' => 'LOCATION',
								'type' => 'changed',
							),
							array(
								'old' => 'FILES',
								'new' => 'FILE_LIST',
								'type' => 'changed',
							),
							array(
								'old' => 'COORGANIZER',
								'new' => 'COORGANIZER_NAMES',
								'type' => 'changed',
							),
							array(
								'old' => 'SUBSCRIPTION',
								'new' => 'SUBS_INFO',
								'type' => 'changed',
							),
							array(
								'old' => 'SUBSCRIPTION_INFO',
								'new' => 'SUBS_INFO',
								'type' => 'changed',
							),
							array(
								'old' => 'ORGANIST',
								'new' => 'SERVICE_1**8_PERSONS',
								'type' => 'changed',
							),
							array(
								'old' => 'SERVICE_1_LABEL',
								'new' => 'Text',
								'type' => 'changed',
							),
							array(
								'old' => 'SERVICE_2_LABEL',
								'new' => 'Text',
								'type' => 'changed',
							),
							array(
								'old' => 'SERVICE_3_LABEL',
								'new' => 'Text',
								'type' => 'changed',
							),
							array(
								'old' => 'SERVICE_4_LABEL',
								'new' => 'Text',
								'type' => 'changed',
							),
							array(
								'old' => 'SERVICE_5_LABEL',
								'new' => 'Text',
								'type' => 'changed',
							),
							array(
								'old' => 'SERVICE_6_LABEL',
								'new' => 'Text',
								'type' => 'changed',
							),
							array(
								'old' => 'SERVICE_7_LABEL',
								'new' => 'Text',
								'type' => 'changed',
							),
							array(
								'old' => 'SERVICE_8_LABEL',
								'new' => 'Text',
								'type' => 'changed',
							),
							array(
								'old' => 'EVENT_PERSON',
								'new' => '[[?ORGANIZER_ID:[verowa_person id="{ORGANIZER_ID}" comp_tag="th"]||{ORGANIZER_NAME}]]',
								'type' => 'changed',
							),
							array(
								'old' => 'ORGANIZER_DETAILED',
								'new' => '[[?ORGANIZER_ID:[verowa_person id="{ORGANIZER_ID}" comp_tag="th"]||{ORGANIZER_NAME}]]',
								'type' => 'changed',
							),
							array(
								'old' => 'ORGANIZER',
								'new' => 'ORGANIZER_NAME',
								'type' => 'changed',
							),
						),
					);

					$arr_service_label = get_option( 'verowa_event_service_label', false );
					if ( true === is_array( $arr_service_label ) ) {
						foreach ( $arr_service_label as $int_id => $str_lbl ) {
							$arr_placeholder['eventdetails']['current']['persons']['pcl'][] = array(
								'name' => 'SERVICE_' . $int_id . '_PERSONS',
								'helptext' => $str_lbl,
							);
						}
					}
					break;

				case 'header':
					$arr_placeholder = array();
					break;

				case 'footer':
					$arr_placeholder = array();
					break;

			}
			break;

		case 'personlist':
			switch ( $str_template_part ) {
				case 'entry':
					$arr_placeholder = array(
						'current' => array(
							'default' => array(
								'pcl' => array(
									'PERSON_ID',
									'IMAGE_SOURCE_TEXT',
									'IMAGE_SOURCE_URL',
									'IMAGE_URL',
									'PERSON_DETAILS_URL',
									'PERSON_NAME',
									'FUNCTION_IN_GROUP',
									'DESC_TASKS',
									'DESC_PERSONAL',
									'SHORT_DESC',
									'PROFESSION',
									'ADDRESS',
									'ZIP_CITY',
									'EMAIL',
									'PERSONAL_URL',
									'PHONE',
									'BUSINESS_PHONE',
									'BUSINESS_MOBILE',
									'PRIVATE_PHONE',
									'PRIVATE_MOBILE',
									'SERVICE_1**8_PERSON_IDS',
								),
							),
						),
						'deprecated' => array(),
					);
					break;

				case 'header':
					$arr_placeholder = array();
					break;

				case 'footer':
					$arr_placeholder = array();
					break;

			}
			break;

		case 'persondetails':
			switch ( $str_template_part ) {
				case 'entry':
					$arr_placeholder = array(
						'current' => array(
							'default' => array(
								'pcl' => array(
									'PERSON_ID',
									'PERSON_NAME',
									'PROFESSION',
									'DESC_TASKS',
									'DESC_PERSONAL',
									'SHORT_DESC',
									'HAS_PRIVATE_ADDRESS',
									'PRIVATE_STREET',
									'PRIVATE_ZIP_CITY',
									'HAS_BUSINESS_ADDRESS',
									'BUSINESS_STREET',
									'BUSINESS_ZIP_CITY',
									'EMAIL',
									'PERSONAL_URL',
									'BUSINESS_PHONE',
									'BUSINESS_MOBILE',
									'PRIVATE_PHONE',
									'PRIVATE_MOBILE',
									'IMAGE_SOURCE_TEXT',
									'IMAGE_SOURCE_URL',
									'IMAGE_URL',
								),
							),
						),
						'deprecated' => array(
							array(
								'old' => 'NAME',
								'new' => 'PERSON_NAME',
								'type' => 'changed',
							),
							array(
								'old' => 'MOBILE_PHONE',
								'new' => 'BUSINESS_MOBILE, PRIVATE_MOBILE',
								'type' => 'changed',
							),
						),
					);
					break;

				case 'header':
					$arr_placeholder = array();
					break;

				case 'footer':
					$arr_placeholder = array();
					break;

			}
			break;

		case 'roster':
			switch ( $str_template_part ) {
				case 'entry':
					$arr_placeholder = array(
						'current' => array(
							'default' => array(
								'pcl' => array(
									'DATE_FROM_SHORT',
									'DATE_TO_SHORT',
									array(
										'name' => 'UNIT_ENTRIES',
										'helptext' => 'Bei Verwendung des Platzhalter kann man nur DATE_FROM_SHORT und DATE_TO_SHORT verwenden.',
									),
									'IS_PERSON',
									'TEXT',
									'SHORTCUT',
									'OUTPUT_NAME',
									'PROFESSION',
									'EMAIL',
									'PHONE',
									'UNIT',
									'TYPE',
								),
							),
						),
						'deprecated' => array(),
					);
					break;

				case 'header':
					$arr_placeholder = array();
					break;

				case 'footer':
					$arr_placeholder = array();
					break;
			}
			break;

		case 'postingdetails':
			switch ( $str_template_part ) {
				case 'entry':
					$arr_placeholder = array(
						'current' => array(
							'default' => array(
								'pcl' => array(
									'POSTING_ID',
									'EVENT_ID',
									'TITLE',
									'LEAD',
									'IMAGE_URL',
									'BLOCKS_HTML',
								),
							),
						),
						'deprecated' => array(),
					);
					break;

				case 'header':
					$arr_placeholder = array();
					break;

				case 'footer':
					$arr_placeholder = array();
					break;
			}
			break;

		case 'postinglist':
			switch ( $str_template_part ) {
				case 'entry':
					$arr_placeholder = array(
						'current' => array(
							'default' => array(
								'pcl' => array(
									'POSTING_ID',
									'EVENT_ID',
									'IS_EVENT_POSTING',
									'TITLE',
									'LEAD',
									'IMAGE_URL',
									'POSTING_DETAILS_URL',
								),
							),
						),
						'deprecated' => array(),
					);
					break;

				case 'header':
					$arr_placeholder = array();
					break;

				case 'footer':
					$arr_placeholder = array();
					break;
			}
			break;

		default:
			$arr_placeholder = array();
			break;
	}
	return $arr_placeholder;
}