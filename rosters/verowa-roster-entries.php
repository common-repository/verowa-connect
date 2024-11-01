<?php
/**
 * Adds two shortcodes:
 * - verowa_roster_entries
 * - verowa-first-roster-entry
 *
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @package Verowa Connect
 * @subpackage Rosters
 */

use Picture_Planet_GmbH\Verowa_Connect\VEROWA_TEMPLATE;

/**
 * Shortcode callback function to display roster entries.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return bool|string
 */
function verowa_roster_entries( $atts ) {
	$curr_language_code = verowa_wpml_get_current_language();
	$str_tf = verowa_tf( 'Service weeks', __( 'Service weeks', 'verowa-connect' ) );
	$atts               = shortcode_atts(
		array(
			'id'          => 0,
			'max'         => 0,
			'max-entries' => 0,
			'max_days'    => 365,
			'title'       => $str_tf . ' ' . wp_date( 'Y' ), // With spaces the title is not displayed.
			'template_id' => 0,
		),
		$atts,
		'verowa_roster_entries'
	);

	if ( intval( $atts['id'] ) > 0 ) {

		$id          = $atts['id'];
		$max_entries = intval( $atts['max-entries'] ) > 0 ? intval( $atts['max-entries'] ) : intval( $atts['max'] );

		ob_start();
		$roster_array = verowa_roster_duty_db_find( $id, intval( $atts['max_days'] ), $max_entries );
		if ( strlen( trim( $atts['title'] ) ) > 0 ) {
			echo '<h3 class="verowa-roster-headline">' . esc_html( $atts['title'] ) . '</h3>';
		}
		
		if ( is_array( $roster_array ) ) {
			$default_template_id = get_option( 'verowa_default_rosterlist_template', 0 );
			$template_id         = $atts['template_id'] > 0 ?
				$atts['template_id'] : $default_template_id;
			$obj_template        = verowa_get_single_template( $template_id, $curr_language_code );

			// If no template was loaded and the ID does not correspond to the default template,
			// then the default template is loaded.
			if ( empty( $obj_template ) && $template_id !== $default_template_id ) {
				$obj_template = verowa_get_single_template( $default_template_id, $curr_language_code );
			}

			if ( null != $obj_template )
			{
				echo $obj_template->header;

				foreach ($roster_array as $single_roster)
				{
					verowa_show_single_roster ($single_roster, $obj_template);
				}

				echo $obj_template->footer;
			}

		}
	}
	return ob_get_clean();
}




/**
 * Shortcode callback function to display the first Roster entry.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return bool|string
 */
function verowa_first_roster_entry( $atts ) {

	$atts = shortcode_atts(
		array(
			'id'          => 0,
			'template_id' => 0,
		),
		$atts,
		'verowa-first-roster-entry'
	);

	$id = intval( $atts['id'] );
	ob_start();
	if ( $id > 0 ) {
		$default_template_id = get_option( 'verowa_default_firstroster_template', 0 );
		$curr_language_code  = verowa_wpml_get_current_language();
		$template_id         = $atts['template_id'] > 0 ?
			$atts['template_id'] : $default_template_id;
		$obj_template        = verowa_get_single_template( $template_id, $curr_language_code );
		// If no template was loaded and the ID does not correspond to the default template,
		// then the default template is loaded.
		if ( empty( $obj_template ) && $template_id !== $default_template_id ) {
			$obj_template = verowa_get_single_template( $default_template_id, $curr_language_code );
		}

		echo wp_kses_post( $obj_template->header );

		$roster_array = verowa_roster_duty_db_find( $id, 0, 1 );

		if ( is_array( $roster_array ) && count( $roster_array ) > 0 ) {
			$single_roster = $roster_array[0] ?? array();
			verowa_show_single_roster( $single_roster, $obj_template );
		}

		echo wp_kses_post( $obj_template->footer );
	}
	return ob_get_clean();
}




/**
 * Single roster entry is output
 *
 * @param array           $single_roster Array single record from the DB.
 * @param VEROWA_TEMPLATE $obj_template Reference to a Verowa Template object.
 */
function verowa_show_single_roster( $single_roster, &$obj_template ) {
	$arr_content = json_decode( $single_roster['content'], true );
	if ( count( $arr_content ) > 0 ) {
		$arr_placeholder = array();
		$time_from       = strtotime( $single_roster['datetime_from'] );
		$time_to         = strtotime( $single_roster['datetime_to'] );

		$arr_placeholder['DATE_FROM_SHORT'] = date_i18n( 'j. M. Y', $time_from );
		$arr_placeholder['DATE_TO_SHORT']   = date_i18n( 'j. M. Y', $time_to );

		if ( false === strpos( $obj_template->entry, 'UNIT_ENTRIES' ) ) {
			$arr_content = $arr_content[0];
			$str_type    = $arr_content['type'] ?? '';

			switch ( $str_type ) {
				case 'person':
					$arr_placeholder['Person_ID'] = $arr_content['id'];
					break;
			}
			$arr_placeholder['IS_PERSON']   = 'person' === $str_type ? true : false;
			$arr_placeholder['TEXT']        = $arr_content['text'];
			$arr_placeholder['SHORTCUT']    = $arr_content['shortcut'];
			$arr_placeholder['OUTPUT_NAME'] = $arr_content['output_name'];
			$arr_placeholder['PROFESSION']  = $arr_content['profession'];
			$arr_placeholder['EMAIL']       = verowa_email_obfuscate( $arr_content['email'] );

			$arr_placeholder['PHONE']     = $arr_content['phone'];
			$arr_placeholder['IMAGE_URL'] = $arr_content['img_url'];
			$arr_placeholder['UNIT']      = $arr_content['unit'];
			$arr_placeholder['TYPE']      = $str_type;
		} else {
			$str_unit_entries = '';
			if ( $obj_template->nested_template_id > 0 ) {
				$curr_language_code  = verowa_wpml_get_current_language();
				$obj_nested_template = verowa_get_single_template( $obj_template->nested_template_id, $curr_language_code );
				foreach ( $arr_content as $single_content ) {

					$arr_nested_pcl = array();
					$str_type       = $single_content['type'] ?? '';
					switch ( $str_type ) {
						case 'person':
							$arr_nested_pcl['Person_ID'] = $single_content['id'];
							break;
					}

					$arr_nested_pcl['IS_PERSON']   = 'person' === $str_type ? true : false;
					$arr_nested_pcl['TEXT']        = $single_content['text'];
					$arr_nested_pcl['SHORTCUT']    = $single_content['shortcut'];
					$arr_nested_pcl['OUTPUT_NAME'] = $single_content['output_name'];
					$arr_nested_pcl['PROFESSION']  = $single_content['profession'];
					$arr_nested_pcl['EMAIL']       = verowa_email_obfuscate( $single_content['email'] );

					$arr_nested_pcl['PHONE']     = $single_content['phone'];
					$arr_nested_pcl['IMAGE_URL'] = $single_content['img_url'];
					$arr_nested_pcl['UNIT']      = $single_content['unit'];
					$arr_nested_pcl['TYPE']      = $str_type;

					$str_unit_entries .= $obj_nested_template->header .
						verowa_parse_template( $obj_nested_template->entry, $arr_nested_pcl ) .
						$obj_nested_template->footer;
				}
			}
			$arr_placeholder['UNIT_ENTRIES'] = $str_unit_entries;
		}

		echo do_shortcode( verowa_parse_template( $obj_template->entry, $arr_placeholder ) );
			$obj_template->separator;
	}
}




/**
 * Encrypt mail before send to browser
 *
 * @param string $str_email Mail which should be encrypted.
 * @return string
 */
function verowa_email_obfuscate( $str_email ) {
	$email_parts = explode( '@', $str_email );
	$str_ret     = '';
	if ( 2 === count( $email_parts ) ) {
		$str_tf = verowa_tf( 'e-mail', __( 'e-mail', 'verowa-connect' ) );
		$str_ret = '<span class="email"><script>var affenschwanz="@"; document.write("' .
		'<a href=\"mailto:' . $email_parts[0] . '"+affenschwanz+"' . $email_parts[1] . '\">' .
		$str_tf . '</a>");</script></span>';
	}
	return $str_ret;
}
