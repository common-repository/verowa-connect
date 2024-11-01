<?php
/**
 * Contains functions that can either be used for the individual person ad
 * or for a group of people as a shortcode that can be set in the post
 * and can be displayed via the widget.
 * The show_a_person_from_verowa function is for smaller boxes where not all details are listed.
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @package Verowa Connect
 * @subpackage Person
 */

/**
 * Individual person that can be called up anywhere with [verowa_person id=129].
 * The function is a wrapper and is named exactly like the shortcode that is created above.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return bool|string
 */
function verowa_person( $atts ) {
	global $post;
	ob_start();

	$int_selected_template = intval( $atts['template_id'] ?? 0 );
	$str_comp_tag          = strlen( $atts['comp_tag'] ?? '' ) > 0 ? $atts['comp_tag'] : 'pr';

	if ( 0 === $int_selected_template ) {
		$int_selected_template = null !== $post ?
			intval( get_post_meta( $post->ID, 'verowa_default_personlist_template', true ) ) : 0;
	}

	$default_template_id = intval( get_option( 'verowa_default_personlist_template' ) );
	if ( 0 === $int_selected_template ) {
		$int_selected_template = $default_template_id;
	}

	$curr_language_code = verowa_wpml_get_current_language();
	$obj_template       = verowa_get_single_template( $int_selected_template, $curr_language_code );
	// If no template was loaded and the ID does not correspond to the default template,
	// then the default template is loaded.
	if ( empty( $obj_template ) && $int_selected_template !== $default_template_id ) {
		$obj_template = verowa_get_single_template( $default_template_id, $curr_language_code );
	}

	$arr_single_person     = verowa_person_db_get_content( $atts['id'] ?? 0 );


	echo $obj_template->header;

	// UNDONE: Template ID durch Template Array ersetzten.
	show_a_person_from_verowa( $arr_single_person, $str_comp_tag, $int_selected_template );
	echo $obj_template->separator;

	echo $obj_template->footer;

	return ob_get_clean();
}




/**
 * Displays all selected persons, incl. headings and groups if applicable
 *
 * @param array $atts hortcode attributes.
 * @return bool|string
 */
function verowa_personen( $atts = array() ) {
	global $post;
	ob_start();

	$atts = shortcode_atts(
		array(
			'id'          => get_post_meta( $post->ID, '_person_singles', true ),
			'comp_tag'    => 'pr',
			'template_id' => 0,
		),
		$atts,
		'verowa_personen'
	);

	$arr_all_person_ids = $atts['id'] ?? 0;
	$str_comp_tag       = $atts['comp_tag'] ?? 'pr';

	// Get the Template.
	$template_id = 0 !== intval( $atts['template_id'] ) ? intval( $atts['template_id'] ) :
		intval( get_post_meta( $post->ID, 'verowa_personlist_template', true ) );

	if ( 0 === $template_id ) {
		$default_template_id = intval( get_option( 'verowa_default_personlist_template' ) );
		$template_id = $default_template_id;
	}
	$curr_language_code = verowa_wpml_get_current_language();

	$obj_template       = verowa_get_single_template( $template_id, $curr_language_code );

	// If no template was loaded and the ID does not correspond to the default template,
	// then the default template is loaded.
	if ( empty( $obj_template ) && $template_id !== $default_template_id ) {
		$obj_template = verowa_get_single_template( $default_template_id );
	}

	// If the person IDs come back as strings (p1,p24,p12), we put them into an array of numbers.
	if ( is_string( $arr_all_person_ids ) ) {
		$arr_all_person_ids          = explode( ',', $arr_all_person_ids );
		$arr_all_person_ids_polished = array();

		foreach ( $arr_all_person_ids as $single_person ) {
			$single_person = trim( $single_person );

			// If there is a P at the beginning, it is omitted (because [person] = default).
			if ( 'p' === strtolower( substr( $single_person, 0, 1 ) ) ) {
				$single_person = substr( $single_person, 1 );
			}

			$arr_all_person_ids_polished[] = $single_person;
		}

		$arr_all_person_ids = $arr_all_person_ids_polished;
	}

	// $arr_all_person_ids can contain person IDs, group IDs AND headings.
	if ( is_array( $arr_all_person_ids ) && count( $arr_all_person_ids ) > 0 ) {
		$arr_person_ids = array();

		foreach ( $arr_all_person_ids as $single_person ) {
			if ( is_numeric( $single_person ) ) {
				$arr_person_ids[] = $single_person;
			}
		}

		// We only fetch the individual persons. Groups are fetched separately below.
		$arr_single_members          = verowa_persons_get_multiple( $arr_person_ids );
		$arr_single_members_polished = array();

		// New array, Key = Person-ID.
		if ( is_array( $arr_single_members ) && count( $arr_single_members ) > 0 ) {
			foreach ( $arr_single_members as $single_member ) {
				$arr_single_members_polished[ $single_member['person_id'] ] = $single_member;
			}
		}

		// UNDONE: Erweitern für WPML
		echo $obj_template->header;

		$is_first = true;
		// we continue with $arr_all_person_ids, because here the order is correct.
		// ATTENTION: $single_person can be an ID, a group ID ('group-123') or a heading !
		foreach ( $arr_all_person_ids as $single_person ) {
			// it is a person id => ^(0|[1-9][0-9]*)$.
			if ( is_numeric( $single_person ) && key_exists( $single_person, $arr_single_members_polished ) ) {
				echo ( false === $is_first ) ? $obj_template->separator : '';
				show_a_person_from_verowa( $arr_single_members_polished[ $single_person ], $str_comp_tag, $template_id );
			} elseif ( false !== strpos( $single_person, 'group-' ) ) { // it is a person group.
				$group_id = str_replace( 'group-', '', $single_person );

				$arr_group_members = verowa_persons_get_for_group( intval( $group_id ) );
				$is_g_first        = false;
				foreach ( $arr_group_members as $single_person ) {
					show_a_person_from_verowa( $single_person, $str_comp_tag, $template_id );
					echo ( false === $is_g_first ) ? $obj_template->separator : '';
					$is_g_first = true;
				}
			}

			// check if it is a headline.
			elseif ( 0 === preg_match( '/^\\d+$/', $single_person ) ) {
				echo '<div class="verowa-persons-headline persons_headline"><h3>' . $single_person . '</h3></div>';
			}

			$is_first = false;
		}

		echo $obj_template->footer;
	}

	return ob_get_clean();
}




/**
 * Output of the individual person, as variable the associated person ID.
 * Used by the widget and the shortcodes verowa_person and verowa_personen.
 *
 * @param  array  $arr_group_member The person data array or the content array containing the person data.
 * @param  string $str_comp_tag The component tag to select the specific image for the person.
 * @param  int    $int_personlist_template The template ID for the person list. (Optional).
 * @return void This function echoes the parsed output of the person data template.
 */
function show_a_person_from_verowa( $arr_group_member, $str_comp_tag = 'pr', $int_personlist_template = 0 ) {
	global $post;

	// UNDONE: Umschreiben von Template-ID auf ref zu Template
	$arr_placeholders = array();

	if ( isset( $arr_group_member['content'] ) ) {
		$arr_person = json_decode( $arr_group_member['content'], true );
	} else {
		$arr_person = $arr_group_member;
	}

	$curr_language_code  = verowa_wpml_get_current_language();
	$default_template_id = intval( get_option( 'verowa_default_personlist_template' ) );

	if ( 0 === $int_personlist_template ) {
		$int_personlist_template = null !== $post ?
			intval( get_post_meta( $post->ID, 'verowa_personlist_template', true ) ) : 0;

		if ( 0 === $int_personlist_template ) {
			$int_personlist_template = $default_template_id;
		}
	}

	$obj_template = verowa_get_single_template( $int_personlist_template, $curr_language_code );

	// If no template was loaded and the ID does not correspond to the default template,
	// then the default template is loaded.
	if ( empty( $obj_template ) && $int_personlist_template !== $default_template_id ) {
		$obj_template = verowa_get_single_template( $default_template_id );
	}

	// Certain special entries override the defaults from the plug-in settings.
	if ( null !== $post && $post->ID > 0 &&
		get_post_meta( $post->ID, 'verowa_has_person_options_on_post_level', false ) ) {

		// Only with detail pages, the setting of the post has effect.
		$persons_without_detail_page = get_option( 'verowa_persons_without_detail_page', false );
		if ( 'on' !== $persons_without_detail_page ) {
			$persons_have_detail_link = get_post_meta( $post->ID, 'verowa_persons_have_detail_link', true );
		} else {
			$persons_have_detail_link = false;
		}

		$person_group_function = get_post_meta( $post->ID, 'verowa_person_show_group_function', true );
		$person_profession     = get_post_meta( $post->ID, 'verowa_person_show_profession', true );
		$person_address        = get_post_meta( $post->ID, 'verowa_person_show_address', true );
		$person_email          = get_post_meta( $post->ID, 'verowa_person_show_person_email', true );
		$person_phone          = get_post_meta( $post->ID, 'verowa_person_show_person_phone', true );
		$person_short_desc     = get_post_meta( $post->ID, 'verowa_person_short_desc', true );
	} else {
		// Otherwise we simply adopt the ones from the plugin.
		$persons_without_detail_page = get_option( 'verowa_persons_without_detail_page', false );
		$persons_have_detail_link    = 'on' != $persons_without_detail_page ? 'on' : false;

		// If the person is displayed via the shortcode in an event detail it has no post meta data.
		// The options are set to on so that the display depends on the placeholders of the templates.
		$person_group_function = 'on';
		$person_profession     = 'on';
		$person_address        = 'on';
		$person_email          = 'on';
		$person_phone          = 'on';
		$person_short_desc     = 'on';
	}

	$arr_single_person = $arr_person;
	$str_language_code = verowa_wpml_get_current_language();

	if ( is_array( $arr_single_person ) && count( $arr_single_person ) > 0 ) {
		$str_siteurl        = verowa_get_base_url();
		$wpml_is_configured = verowa_wpml_is_configured();
		if ( true === $wpml_is_configured ) {
			$str_person_details_url = verowa_wpml_get_custom_post_url ($arr_single_person['person_id'], 
				$str_language_code, 'person', 'verowa_person');
		} else {
			$str_person_details_url = $str_siteurl . '/person/' . $arr_single_person['person_id'] . '/';
		}
		
		$arr_placeholders['PERSON_DETAILS_URL'] = $str_person_details_url;

		if ( 'on' === $persons_have_detail_link ) {
			// UNDONE: URL auf WPML anpassen.

			$str_link_pre  = '<a href="' . $str_person_details_url . '/">';
			$str_link_post = '</a>';
		} else {
			$str_link_pre  = '';
			$str_link_post = '';
		}

		$arr_placeholders['PERSON_ID'] = $arr_single_person['person_id'] ?? '';

		if ( count( $arr_single_person['images'] ?? array() ) > 0 &&
			key_exists( $str_comp_tag, $arr_single_person['images'] ) ) {
			$arr_placeholders['IMAGE_URL'] = $str_link_pre .
				'<img src="' . $arr_single_person['images'][ $str_comp_tag ]['url'] . '" />' . $str_link_post;
		}

		$str_person_name = trim( $arr_single_person['firstname'] . ' ' . $arr_single_person['lastname'] );

		$arr_placeholders['PERSON_NAME']            = $str_link_pre . $str_person_name . $str_link_post;
		$arr_placeholders['PERSON_NAME_NOT_LINKED'] = $str_person_name;

		if ( key_exists( 'function_in_group', $arr_single_person ) &&
			strlen( $arr_single_person['function_in_group'] ) > 0 &&
			'on' === $person_group_function ) {
			$arr_placeholders['FUNCTION_IN_GROUP'] = $arr_single_person['function_in_group'];
		}

		if ( key_exists( 'short_desc', $arr_single_person ) &&
			strlen( $arr_single_person['short_desc'] ) > 0 &&
			'on' === $person_short_desc ) {
			$arr_placeholders['SHORT_DESC'] = $arr_single_person['short_desc'];
		}

		$arr_placeholders['DESC_TASKS'] = $arr_single_person['desc_tasks'] ?? '';
		$arr_placeholders['DESC_PERSONAL'] = $arr_single_person['desc_personal'] ?? '';

		if ( strlen( $arr_single_person['profession'] ) > 0 && 'on' === $person_profession ) {
			$arr_placeholders['PROFESSION'] = $arr_single_person['profession'];
		}

		if ( ( $arr_single_person['postcode'] ?? 0 ) > 0 && 'on' === $person_address ) {
			$arr_placeholders['ADDRESS']  = $arr_single_person['address'];
			$arr_placeholders['ZIP_CITY'] = $arr_single_person['postcode'] . ' ' . $arr_single_person['city'];
		}

		if ( strlen( $arr_single_person['email'] ) > 0 && 'on' === $person_email ) {
			$arr_placeholders['EMAIL'] = $arr_single_person['email'];
		}

		$arr_placeholders['PERSONAL_URL'] = '';
		if ( key_exists( 'pers_website', $arr_single_person ) && '' !== $arr_single_person['pers_website'] ) {
			if ( false === strpos( $arr_single_person['pers_website'], 'http' )  ) {
				$arr_single_person['pers_website'] = 'https://' . $arr_single_person['pers_website'];
			}

			$arr_placeholders['PERSONAL_URL'] = $arr_single_person['pers_website'];
		}

		if ( strlen( $arr_single_person['phone'] ) > 0 && 'on' === $person_phone ) {
			$arr_placeholders['PHONE'] = $arr_single_person['phone'];
		}

		$arr_placeholders['BUSINESS_PHONE']  = verowa_add_tel_link ($arr_single_person['business_phone'] ?? '');
		$arr_placeholders['BUSINESS_MOBILE'] = verowa_add_tel_link ($arr_single_person['business_mobile'] ?? '');
		$arr_placeholders['PRIVATE_PHONE']   = verowa_add_tel_link ($arr_single_person['private_phone'] ?? '');
		$arr_placeholders['PRIVATE_MOBILE']  = verowa_add_tel_link ($arr_single_person['private_mobile'] ?? '');

		$arr_placeholders['BUSINESS_PHONE_NUMBER']  = $arr_single_person['business_phone'] ?? '';
		$arr_placeholders['BUSINESS_MOBILE_NUMBER'] = $arr_single_person['business_mobile'] ?? '';
		$arr_placeholders['PRIVATE_PHONE_NUMBER']   = $arr_single_person['private_phone'] ?? '';
		$arr_placeholders['PRIVATE_MOBILE_NUMBER']  = $arr_single_person['private_mobile'] ?? '';

		$arr_placeholders['IMAGE_SOURCE_TEXT'] = $arr_single_person['image_source_text'] ?? '';
		$arr_placeholders['IMAGE_SOURCE_URL']  = $arr_single_person['image_source_url'] ?? '';

		// UNDONE: Erweitern für WPML
		if ( is_array( $obj_template ) && isset( $obj_template['de'] ) ) {
			$obj_template = $obj_template['de'];
		}
		
		if ( is_array( $obj_template ) ) {
			$obj_template = (object)$obj_template;
		}

		$str_output = verowa_parse_template( $obj_template->entry, $arr_placeholders );

		echo do_shortcode( $str_output );
	}
}
