<?php
/**
 * Function collection for WPML
 *
 * Project:  VEROWA CONNECT
 * File:     functions/event.php
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since 2.13.0
 * @package Verowa Connect
 * @subpackage Functions
 */

// TODO: P2 Prüfen, was gemacht werden muss, wenn die Default Lang. gewechselt wird.
// TODO: P2 Default Lang. gewechselt: Für Templates ohne Übersetzung in der Default Lang. Müssen Templates angelegt werden.

/**
 * Adds an entry to the table "verowa_translations" for the default templates.
 * This gives the templates a translation group ID which is necessary for the other language template.
 *
 * @return void
 */
function verowa_wpml_init_templates() {
	global $wpdb;

	if ( true === verowa_wpml_is_configured() ) {

		$str_default_language = verowa_wpml_get_default_language_code();

		// Templates without an entry in "verowa_translations" receive an entry in the default language.
		// All other languages are generated when the translation is created.
		$query         = 'SELECT `template_id` FROM `' . $wpdb->prefix . 'verowa_templates` ';

		$arr_restrictions = array(
			'`deprecated` = 0',
		);

		$arr_restrictions[] = '`template_id` NOT IN (SELECT `element_id` FROM `' . $wpdb->prefix . 'verowa_translations`);';
		$query .= 'WHERE ' . implode( ' AND ', $arr_restrictions);
		$arr_templates = $wpdb->get_results( $query );

		if ( ! is_wp_error( $arr_templates ) && count( $arr_templates ) > 0 ) {
			$arr_template_ids = array();
			foreach ( $arr_templates as $obj_single_template ) {
				$arr_args_set_templates = array(
					'element_type'  => 'record_verowa_template',
					'element_id'    => intval( $obj_single_template->template_id ),
					'trid'          => intval( $obj_single_template->template_id ),
					'language_code' => 'de',
				);

				// Entering the corresponding templates.
				verowa_wpml_set_custom_element_language( $arr_args_set_templates );
				$arr_template_ids[] = intval( $obj_single_template->template_id );
			}
		}
	}
}




/**
 * Is a copy of wpml_set_element_language_details because the templates are not posts and
 * the relationship of the languages cannot be stored in the table "icl_languages".
 *
 * @param array $args [element_type, element_id, trid, language_code, source_language_code].
 *
 * @return void
 */
function verowa_wpml_set_custom_element_language( $args ) {
	global $wpdb;
	if ( true === verowa_wpml_is_configured() ) {
		$query              = 'SELECT * FROM `' . $wpdb->prefix . 'verowa_translations` ' .
			'WHERE `element_id` = ' . $args['element_id'] .
			' AND `element_type` = "' . $args['element_type'] . '"';
		$int_translation_id = intval( $wpdb->get_var( $query ) ?? 0 );

		if ( 0 === $int_translation_id ) {
			$wpdb->insert(
				$wpdb->prefix . 'verowa_translations',
				$args,
				array( '%s', '%d', '%d', '%s', '%s' )
			);
		}
	}
}




function verowa_wpml_delete_custom_element_language( $args ) {
	global $wpdb;
	if ( true === verowa_wpml_is_configured() ) {
		$query = 'DELETE FROM `' . $wpdb->prefix . 'verowa_translations` ' .
			'WHERE `element_id` = ' . $args['element_id'] .
			' AND `element_type` = "' . $args['element_type'] . '"';
		$wpdb->query( $query );
	}
}




/**
 * Returns all elements of a translation group ID with the same element type.
 *
 * @param  array $arrg [str_element_type, trid].
 * @return array
 */
function verowa_wpml_get_custom_element_language( $arrg ) {
	 global $wpdb;
	$query = 'SELECT * FROM `' . $wpdb->prefix . 'verowa_translations`';

	$arr_restrictions = array();
	if ( '' !== ( $arrg['str_element_type'] ?? '' ) ) {
		$arr_restrictions[] = '`element_type` = "' . $arrg['str_element_type'] . '"';
	}

	$trid = intval( $arrg['trid'] ?? 0 );
	if ( 0 !== $trid ) {
		$arr_restrictions[] = '`trid` = "' . $trid . '"';
	}

	if ( count( $arr_restrictions ) > 0 ) {
		$query .= ' WHERE ' . implode( ' AND ', $arr_restrictions );
	}
	$query .= ';';

	$arr_result = $wpdb->get_results( $query );

	return false === is_wp_error( $arr_result ) ? $arr_result : array();
}




/**
 * TODO: Erweitern für Personen
 * Get all translation of the given post. 
 * Works allso without WPML.
 *
 * @param  mixed $int_post_id
 *
 * @return array
 */
function verowa_wpml_get_translations( $int_post_id ) {
	$arr_translations = array();
	if ( true === verowa_wpml_is_configured() ) {
		$trid             = apply_filters( 'wpml_element_trid', null, $int_post_id, 'post_verowa_event' );
		$arr_translations = apply_filters( 'wpml_get_element_translations', null, $trid, 'post_verowa_event' );
	} else {
		$obj                    = new stdClass();
		$obj->element_id        = $int_post_id;
		$arr_translations['de'] = $obj;
	}
	return $arr_translations;
}

/**
 * Return the translation group ID of the given element.
 *
 * @param  int    $element_id In the case of record_verowa_template, this is the template id.
 * @param  string $el_type Only needed if it is not a record_verowa_template.
 *
 * @return int
 */
function verowa_wpml_get_translations_trid( $element_id, $el_type = 'record_verowa_template' ) {
	global $wpdb;
	// TODO: P2 add WP-Cache
	$trid = 0;
	if ( true === verowa_wpml_is_configured() ) {
		$trid = intval(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT `trid` FROM {$wpdb->prefix}verowa_translations WHERE element_id=%d AND element_type=%s",
					array( $element_id, $el_type )
				)
			) ?? 0
		);

		// If no ID was found, the next higher ID was determined.
		if ( 0 === $trid ) {
			$ret_trid = $wpdb->get_var( "SELECT MAX(trid) as `max_id` FROM `{$wpdb->prefix}verowa_translations`;" );
			$trid     = intval( $ret_trid ?? 0 ) + 1;
		}
	}
	return $trid;
}




/**
 * Extracts the required languages from the multilingual array of Verowa
 *
 * @param  array  $arr_event Reference event array to be sent from the API.
 * @param  string $str_language_code Specify the language to be returned.
 *
 * @return array
 */
function verowa_wpml_get_language_strings_for_events( &$arr_event, $str_language_code ) {
	$arr_lang = array();
	if ( 'de' === $str_language_code || false === key_exists ($str_language_code, $arr_event['multilang'] ?? []) ) {
		$arr_lang['title']               = $arr_event['title'];
		$arr_lang['short_desc']          = $arr_event['short_desc'];
		$arr_lang['long_desc']           = $arr_event['long_desc'];
		$arr_lang['topic']               = $arr_event['topic'];
		$arr_lang['further_coorganizer'] = $arr_event['further_coorganizer'];
		$arr_lang['catering']            = $arr_event['catering'];
		for ( $i = 0; $i < 4; $i++ ) {
			$arr_lang[ 'additional_text' . $i ] = $arr_event[ 'additional_text' . $i ] ?? '';
		}
	} else {
		$arr_lang['title']               = $arr_event['multilang'][ $str_language_code ]['title'] ?? $arr_event['title'];
		$arr_lang['short_desc']          = $arr_event['multilang'][ $str_language_code ]['short_text'] ?? '';
		$arr_lang['long_desc']           = $arr_event['multilang'][ $str_language_code ]['long_desc'] ?? '';
		$arr_lang['topic']               = $arr_event['multilang'][ $str_language_code ]['topic'] ?? '';
		$arr_lang['further_coorganizer'] = $arr_event['multilang'][ $str_language_code ]['further_coorganizer'] ?? '';
		$arr_lang['catering']            = $arr_event['multilang'][ $str_language_code ]['catering_comment'] ?? '';
		for ( $i = 0; $i < 4; $i++ ) {
			$arr_lang[ 'additional_text' . $i ] = $arr_event['multilang'][ $str_language_code ][ 'additional_text' . $i ] ?? '';
		}
	}

	$arr_lang['post_excerpt'] = strlen( trim( $arr_lang['short_desc'] ) ) > 0 ?
		$arr_lang['short_desc'] : $arr_lang['long_desc'];

	return $arr_lang;
}





/**
 * Returns all configured languages of WPML.
 *
 * @param  mixed $with_default_lang default ist true.
 *
 * @return array
 */
function verowa_wpml_get_active_languages( $with_default_lang = true ) {
	$arr_languages = apply_filters( 'wpml_active_languages', null, 'skip_missing=0&orderby=code' ) ?? array();
	// Removes the default language from the return array.
	$str_default_lang = verowa_wpml_get_default_language_code();
	if ( strlen( $str_default_lang ) > 0 ) {
		$arr_default_lang_info = $arr_languages[ $str_default_lang ];
		unset( $arr_languages[ $str_default_lang ] );
		// Fügt es als erste Sprache wieder ein.
		if ( true === $with_default_lang ) {
			$arr_languages = array_merge( array( $str_default_lang => $arr_default_lang_info ), $arr_languages );
		}
	}
	return $arr_languages;
}




/**
 * Retrieves a mapping from Verowa language codes to WordPress language codes.
 *
 * This function first checks if WPML is configured. If so, it retrieves the active WPML languages
 * and adds any missing mappings from the `verowa_wpml_mapping` option. Otherwise, it defaults to a mapping for the German ('de') language.
 *
 * The mapping is returned as an associative array, where the keys are Verowa language codes and the values are arrays of corresponding WordPress language codes.
 *
 * @return array The mapping from Verowa language codes to WordPress language codes.
 */
function verowa_wpml_get_mapping() {
	$verowa_wpml_mapping = get_option( 'verowa_wpml_mapping', '[]');
	$arr_ret = array();

	if ( true === verowa_wpml_is_configured() ) {
		$arr_mapping = json_decode ($verowa_wpml_mapping, true);

		$arr_languages = verowa_wpml_get_active_languages();
		foreach ($arr_languages as $arr_single_language)
		{
			$bool_in_array = false;
			foreach ($arr_mapping as $sub_array) {
				if ( in_array( $arr_single_language['language_code'], $sub_array['wp_language_code'] ) ) {
					$bool_in_array = true;
					break; // Schleife beenden, da der Wert gefunden wurde
				}
			}
			if ( false === $bool_in_array )
			{
				$str_lang_code = $arr_single_language['language_code'];
				$arr_mapping[ $str_lang_code ] = array(
					'input_lang'       => $str_lang_code,
					'wp_language_code' => array( $str_lang_code ),
				);
			}

		}

		// Sicherzustellen, das de als erste Sprache eingefügt wird
		if (isset( $arr_mapping['de'] )) {
			$de_value = $arr_mapping['de']; 
			unset( $arr_mapping['de'] );
			$arr_mapping = array_merge ( array( 'de' => $de_value ) + $arr_mapping );
		}
		
		if ( is_array( $arr_mapping ) ) {
			foreach ( $arr_mapping as $single_mapping ) {
				if ( isset( $single_mapping['input_lang'] ) ) {
					$arr_ret[ $single_mapping['input_lang'] ] = $single_mapping['wp_language_code'];
				}
			}
		}
	} else {
		$arr_ret['de'] =  array( 'de' );
	}

	



	return $arr_ret;
}




/**
 * Map the verowa languages to the WP in the multilang array from the Verowa API
 *
 * @param  mixed $arr_event Reference event array to be sent from the API.
 * @return void
 */
function verowa_wpml_map_lang( &$arr_event ) {
	if ( verowa_wpml_is_configured() && true === isset( $arr_event['multilang'] ) ) {
		$arr_temp_multilang = $arr_event['multilang'];
		// This way we can fill the array with the correct keys.
		$arr_event['multilang'] = array();

		$arr_mapping = verowa_wpml_get_mapping();
		foreach ( $arr_temp_multilang as $str_ver_language_code => $val ) {
			if ( true === isset( $arr_mapping[ $str_ver_language_code ] ) ) {
				foreach ( $arr_mapping[ $str_ver_language_code ] as $wpml_language_code ) {
					$arr_event['multilang'][ $wpml_language_code ] = $val;
				}
			} else {
				$arr_event['multilang'][ $str_ver_language_code ] = $val;
			}
		}
	}
}




/**
 * Wrapper for WPML function setup_complete
 *
 * @return boolean
 */
function verowa_wpml_is_configured() {
	$is_wpml_configured = apply_filters( 'wpml_setting', false, 'setup_complete' ) ?? false;
	return filter_var( $is_wpml_configured, FILTER_VALIDATE_BOOLEAN );
}





/**
 * Wrapper for WPML function wpml_default_language
 * Returns the default language code, if wpml is not activated its return is always 'de'.
 *
 * @return string
 */
function verowa_wpml_get_default_language_code() {
	if ( true === verowa_wpml_is_configured() ) {
		$str_default_lang = apply_filters( 'wpml_default_language', null ) ?? '';
	} else {
		$str_default_lang = 'de';
	}
	return $str_default_lang;
}

function verowa_wpml_get_default_language_locale() {
	if ( true === verowa_wpml_is_configured() ) {
		$str_default_lang = apply_filters( 'wpml_default_language', null ) ?? '';
		$languages = apply_filters( 'wpml_active_languages', NULL, array( 'skip_missing' => 0 ) );
		$str_default_locale = $languages[$str_default_lang]['default_locale'] ?? 'de_CH';
	} else {
		$str_default_locale = 'de_CH';
	}
	return $str_default_locale;
}

/**
 * Wrapper for WPML filter wpml_current_language
 *
 * @return string
 */
function verowa_wpml_get_current_language() {
	if ( true === verowa_wpml_is_configured() ) {
		$str_current_language = apply_filters( 'wpml_current_language', null ) ?? 'de';
	} else {
		$str_current_language = 'de';
	}
	return $str_current_language;
}




/**
 * Wrapper function for "wpml_permalink" filter.
 * Test in ppg_playground_overview.php
 *
 * @param string $str_permalink WP-Permalink is required.
 * @param string $language_code Target language.
 *
 * @return string
 */
function verowa_wpml_get_translated_permalink( $str_permalink, $language_code ) {
	if ( true === verowa_wpml_is_configured() ) {
		$str_ret = apply_filters(
			'wpml_permalink',
			$str_permalink,
			$language_code,
			true
		);
	} else {
		$str_ret = $str_permalink;
	}
	return $str_ret;
}




/**
 * Generates a custom post URL considering WPML configuration.
 * 
 * If WPML is active, the translated slug for the given language is used.
 * Otherwise, the base site URL is used along with the untranslated slug.
 *
 * @param int $ver_id The ID of the custom post (e.g., event_id, person_id).
 * @param string $str_language_code The language code (e.g., 'en', 'de').
 * @param string $cpt_slug The untranslated slug of the custom post type.
 * @param string $str_post_type The name of the custom post type.
 * 
 * @return string The generated URL for the custom post.
 */
function verowa_wpml_get_custom_post_url( $ver_id, $str_language_code, $cpt_slug, $str_post_type ) {
	$str_custom_post_url = '';
	$str_siteurl = verowa_get_base_url();
	if (true === verowa_wpml_is_configured()) {
		$str_cpt_slug = apply_filters( 'wpml_get_translated_slug', $cpt_slug, $str_post_type, $str_language_code );
		$str_custom_post_url = $str_siteurl .  $str_cpt_slug .'/' . $ver_id . '-' . $str_language_code;
	}
	return $str_custom_post_url;
}




function verowa_wpml_get_language_ddl( $arr_languages, $str_ddl_name, $str_value = '' )
{
	$str_return = '';

	/**
	 * Default is true because the option did not exist earlier
	 *
	 * @var boolean $is_show_empty_option
	 */
	$is_show_empty_option = true;

	$str_select_class = '';
	$str_template_del_options = '';
	$arr_lang_ids = array_column( $arr_languages, 'id' );

	if ( ! empty( $str_value ) &&
		false === in_array( $str_value, $arr_lang_ids, true )
	) {
		// Template has been deleted.
		$str_select_class .= 'class="verowa-select-error-first-child" ';
		// $str_template_del_options = verowa_get_deleted_template_option( $int_selected_template );
	}

	$str_return .= '<select ' . $str_select_class . 'name="' . $str_ddl_name . '">';
	$str_return .= $str_template_del_options;

	if ( $is_show_empty_option ) {
		$str_return .= '<option value=""></option>';
	}

	foreach ( $arr_languages as $arr_single_language ) {
		$str_selected = $arr_single_language['id'] === $str_value ? 'selected' : '';

		$str_return .= '<option value="' . $arr_single_language['id'] . '" ' . $str_selected . '>' .
			esc_attr( stripcslashes( $arr_single_language['name'] ) ) . '</option>';
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