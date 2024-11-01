<?php
/**
 * Functions to display people from Verowa.
 * Mainly auxiliary functions for the WP structure
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since 2.9.0
 * @package Verowa Connect
 * @subpackage Person
 */

// If the person is called via the GET parameter.
// Function is actually deprecated because Pretty Permalinks are now available.

add_action( 'init', 'retrieve_person_get' );

/**
 * Retrieve and redirect to the person's page.
 *
 * This function is a REST API callback that handles GET requests to the "retrieve_person" endpoint.
 * It checks if the "person" parameter is set in the GET request and redirects the user to a specific URL
 * with the "person" parameter included.
 *
 * @return void
 */
function retrieve_person_get() {
	if ( isset( $_GET['person'] ) ) {
		$location = get_bloginfo( 'url' ) . '/person/' . $_GET['person'] . '/';
		wp_redirect( $location, 301 );
		exit;
	}
}



/**
 * Compile the content of the person page for display.
 * This function compiles and generates content for the person page based on the provided person data.
 *
 * @param array  $arr_person_data An array containing person data.
 * @param string $str_comp_tag    Optional. A tag to identify the specific person data entry to use for images.
 *
 * @return array An associative array of compiled content for each language. The keys represent language codes,
 *               and the values contain the compiled content.
 */
function show_a_person_from_verowa_detail( $arr_person_data, $str_comp_tag = 'pr' ) {

	$str_title             = '';
	$arr_placeholders      = array();
	$arr_person_ml_content = array();

	$int_persondetails_default_template = get_option( 'verowa_default_persondetails_template', 0 );

	if ( null !== $arr_person_data && count( $arr_person_data ) > 0 ) {

		if ( count( $arr_person_data['images'] ?? array() ) > 0 && key_exists( $str_comp_tag, $arr_person_data['images'] ) ) {
			$arr_placeholders['IMAGE_URL'] = $arr_person_data['images'][ $str_comp_tag ]['url'];
		}

		$arr_placeholders['IMAGE_SOURCE_TEXT'] = $arr_person_data['image_source_text'] ?? '';
		$arr_placeholders['IMAGE_SOURCE_URL']  = $arr_person_data['image_source_url'] ?? '';

		$arr_title = array();
		if ( '' !== $arr_person_data['firstname'] ) {
			$arr_title [] = $arr_person_data['firstname'];
		}

		// if there is the first name AND the surname, separate with a space.
		if ( '' != $arr_person_data['lastname'] ) {
			$arr_title [] = $arr_person_data['lastname'];
		}

		$str_title                     = implode( ' ', $arr_title );
		$arr_placeholders['PERSON_ID'] = $arr_person_data['person_id'] ?? '';

		/**
		 * User PERSON_NAME.
		 *
		 * @deprecated VC 1.6.0
		*/
		$arr_placeholders['NAME']        = $str_title;
		$arr_placeholders['PERSON_NAME'] = $str_title;

		if ( '' != $arr_person_data['profession'] ) {
			$arr_placeholders['PROFESSION'] = $arr_person_data['profession'];
		}

		if ( '' !== $arr_person_data['short_desc'] ) {
			$arr_placeholders['SHORT_DESC'] = $arr_person_data['short_desc'];
		}

		$arr_placeholders['DESC_TASKS'] = $arr_person_data['desc_tasks'] ?? '';
		$arr_placeholders['DESC_PERSONAL']   = $arr_person_data['desc_personal'] ?? '';

		$arr_placeholders['HAS_PRIVATE_ADDRESS'] = false;
		if ( '' !== ( $arr_person_data['private_street'] ?? '' ) ||
			'' !== ( $arr_person_data['private_postcode'] ?? '' ) ||
			'' !== ( $arr_person_data['private_city']?? '' ) ) {

			$arr_placeholders['HAS_PRIVATE_ADDRESS'] = true;

			if ( '' !== $arr_person_data['private_street'] ) {
				$arr_placeholders['PRIVATE_STREET'] = $arr_person_data['private_street'];
			}

			if ( '' !== $arr_person_data['private_postcode'] || '' !== $arr_person_data['private_city'] ) {
				$str_private_zipcode = '';

				if ( '' !== $arr_person_data['private_postcode'] ) {
					$str_private_zipcode = $arr_person_data['private_postcode'] . ' ';
				}

				$arr_placeholders['PRIVATE_ZIP_CITY'] = $str_private_zipcode . $arr_person_data['private_city'];
			}
		}

		$arr_placeholders['HAS_BUSINESS_ADDRESS'] = false;

		if ( '' !== ( $arr_person_data['business_street'] ?? '' ) || '' !== ( $arr_person_data['business_postcode'] ?? '') ||
			'' !== ($arr_person_data['business_city'] ?? '') ) {

			$arr_placeholders['HAS_BUSINESS_ADDRESS'] = true;

			if ( '' !== ( $arr_person_data['business_street'] ?? '' ) ) {
				$arr_placeholders['BUSINESS_STREET'] = $arr_person_data['business_street'];
			}

			if ( '' !== ($arr_person_data['business_postcode'] ?? '') || '' !== ($arr_person_data['business_city'] ?? '') ) {
				$str_business_zipcode = '';

				if ( '' !== ( $arr_person_data['business_postcode'] ?? '' ) ) {
					$str_business_zipcode = $arr_person_data['business_postcode'] . ' ';
				}

				$arr_placeholders['BUSINESS_ZIP_CITY'] = $str_business_zipcode . ($arr_person_data['business_city'] ?? '');
			}
		}

		if ( '' !== $arr_person_data['email'] ) {
			$arr_placeholders['EMAIL'] = $arr_person_data['email'];
		}

		$arr_placeholders['PERSONAL_URL'] = '';
		if ( key_exists( 'pers_website', $arr_person_data ) && '' !== $arr_person_data['pers_website'] ) {
			if ( false === strpos( $arr_person_data['pers_website'], 'http' ) ) {
				$arr_person_data['pers_website'] = 'https://' . $arr_person_data['pers_website'];
			}
			$arr_placeholders['PERSONAL_URL'] = $arr_person_data['pers_website'];
		}

		$arr_placeholders['BUSINESS_PHONE']  = verowa_add_tel_link( $arr_person_data['business_phone'] ?? '');
		$arr_placeholders['BUSINESS_MOBILE'] = verowa_add_tel_link( $arr_person_data['business_mobile'] ?? '');
		$arr_placeholders['PRIVATE_PHONE']   = verowa_add_tel_link( $arr_person_data['private_phone'] ?? '');
		$arr_placeholders['PRIVATE_MOBILE']  = verowa_add_tel_link( $arr_person_data['private_mobile'] ?? '');

		$arr_placeholders['BUSINESS_PHONE_NUMBER']  = $arr_person_data['business_phone'] ?? '';
		$arr_placeholders['BUSINESS_MOBILE_NUMBER'] = $arr_person_data['business_mobile'] ?? '';
		$arr_placeholders['PRIVATE_PHONE_NUMBER']   = $arr_person_data['private_phone'] ?? '';
		$arr_placeholders['PRIVATE_MOBILE_NUMBER']  = $arr_person_data['private_mobile'] ?? '';

		$arr_lang_templates = verowa_get_single_template( $int_persondetails_default_template );
		$verowa_wpml_mapping    = verowa_wpml_get_mapping();
		$str_default_lang_code  = verowa_wpml_get_default_language_code();

		foreach ($verowa_wpml_mapping as $str_verowa_lang => $arr_wp_language_codes)
		{
			foreach ($arr_wp_language_codes as $str_wp_language_code)
			{
				$obj_single_template = $arr_lang_templates[ $str_wp_language_code ] ?? $arr_lang_templates[ $str_default_lang_code ];
				$str_parsed_template = $obj_single_template->header .
					verowa_parse_template ($obj_single_template->entry, $arr_placeholders) .
					$obj_single_template->footer;

				$str_head = verowa_parse_template ($obj_single_template->head, $arr_placeholders);
				$arr_person_ml_content[$str_wp_language_code] = array(
					'head' => $str_head,
					'html' => $str_parsed_template,
				);
			}
		}
	}
	return $arr_person_ml_content;
}
