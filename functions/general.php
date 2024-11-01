<?php
/**
 * Function collection for general purpose
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since VC 2.9.0
 * @package Verowa Connect
 * @subpackage Functions
 */

/**
 * Goes through all pages and saves the entered list ids in the option _verowa_list_assign
 */
function verowa_update_list_id_option() {
	$args = array(
		'numberposts' => -1, // ex posts_per_page.
		'post_status' => 'publish', // published.
		'post_type'   => array( 'page', 'post' ), // ex page.
	);

	$posts               = get_posts( $args );
	$arr_list_ids        = array();
	$arr_content_snipets = array();

	// Go through all posts. We look to see if the shortcode has been added to the content
	// and whether a list has been selected.
	foreach ( $posts as $post ) {

		$int_list_id = intval( get_post_meta( $post->ID, '_verowa_list_assign', true ) );

		// We check if a list has been selected for the page.
		if ( 0 !== $int_list_id ) {
			$arr_list_ids[] = $int_list_id;
		}

		$arr_content_snipets[] = $post->post_content;
	}

	// get list ids in widgets (ab WP 5.0).
	$arr_widgets = get_option( 'widget_block', array() );
	foreach ( $arr_widgets as $arr_single_widget ) {
		if ( is_array( $arr_single_widget ) && key_exists( 'content', $arr_single_widget ) ) {
			$arr_content_snipets[] = $arr_single_widget['content'];
		}
	}

	// As of WP 5.6.6, widget are saved differently.
	$arr_widgets = get_option( 'widget_text', array() );
	foreach ( $arr_widgets as $arr_single_widget ) {
		if ( is_array( $arr_single_widget ) && key_exists( 'text', $arr_single_widget ) ) {
			$arr_content_snipets[] = $arr_single_widget['text'];
		}
	}

	foreach ( $arr_content_snipets as $str_single_snipet ) {
		$arr_new_list = verowa_extract_list_ids( $str_single_snipet );
		$arr_list_ids = array_merge( $arr_list_ids, $arr_new_list );
	}

	// We also get all list ids from the agenda filter drop downs.
	$int_count_agenda_dropdowns = intval( get_option( 'how_many_verowa_dropdowns', false ) );

	for ( $i = 1; $i <= $int_count_agenda_dropdowns; $i++ ) {
		$str_agenda_list_ids = get_option( 'verowa_dropdown_' . $i, '' );

		if ( '' !== $str_agenda_list_ids ) {
			$arr_agenda_list_ids = array_map( 'intval', explode( ', ', $str_agenda_list_ids ) );

			$arr_list_ids = array_merge( $arr_list_ids, $arr_agenda_list_ids );
		}
	}

	$arr_list_ids = array_unique( $arr_list_ids );
	sort( $arr_list_ids );

	if ( count( $arr_list_ids ) > 0 ) {
		update_option( 'verowa_list_ids', wp_json_encode( $arr_list_ids ) );
	}
}



/**
 * Collects the roster ids from the WP content
 *
 * @return void
 */
function verowa_update_roster_ids_option() {
	$args = array(
		'numberposts' => -1, // ex posts_per_page.
		'post_status' => 'publish', // published.
		'post_type'   => array( 'page', 'post' ), // ex page.
	);

	$posts               = get_posts( $args );
	$arr_roster_ids      = array();
	$arr_content_snipets = array();

	// Go through all posts. We look to see if the shortcode has been added to the content
	// and whether a list has been selected.
	foreach ( $posts as $post ) {
		$arr_content_snipets[] = $post->post_content;
	}

	// get list ids in widgets (ab WP 5.0).
	$arr_widgets = get_option( 'widget_block', array() );
	foreach ( $arr_widgets as $arr_single_widget ) {
		if ( is_array( $arr_single_widget ) && key_exists( 'content', $arr_single_widget ) ) {
			$arr_content_snipets[] = $arr_single_widget['content'];
		}
	}

	// As of WP 5.6.6, widget are saved differently.
	$arr_widgets = get_option( 'widget_text', array() );
	foreach ( $arr_widgets as $arr_single_widget ) {
		if ( is_array( $arr_single_widget ) && key_exists( 'text', $arr_single_widget ) ) {
			$arr_content_snipets[] = $arr_single_widget['text'];
		}
	}

	foreach ( $arr_content_snipets as $str_single_snipet ) {
		$arr_new_list   = verowa_extract_roster_ids( $str_single_snipet ) ?? array();
		$arr_roster_ids = array_merge( $arr_roster_ids, $arr_new_list );
	}

	$arr_roster_ids = array_unique( $arr_roster_ids );
	sort( $arr_roster_ids );

	if ( count( $arr_roster_ids ) > 0 ) {
		update_option( 'verowa_roster_ids', wp_json_encode( $arr_roster_ids ) );
	}
}




/**
 * Function to extract list ids from shortcode e.g. [verowa_event_liste title="" id="837" max="4" template_id=5]
 *
 * @param string $str_content Content of a WP post content.
 * @return int[]
 */
function verowa_extract_list_ids( $str_content ) {

	$arr_list_ids = array();
	$arr_matches  = array();
	preg_match_all( VEROWA_PATTERN_LIST_IDS, $str_content, $arr_matches );

	// The shortcode attributes are stored in $arr_matches[3].
	foreach ( $arr_matches[3] as $str_attributes ) {
		// Separate all attributes.
		$arr_shortcode_attributes = explode( ' ', trim( $str_attributes ) );

		// Separate the value from the shortcode.
		foreach ( $arr_shortcode_attributes as $single_attribute ) {
			$arr_seperated_atts = explode( '=', $single_attribute );

			if ( 'id' === $arr_seperated_atts[0] ||
				'list' === $arr_seperated_atts[0] ||
				'list_id' === $arr_seperated_atts[0] ) {
				$arr_id_matches = array();
				preg_match( '/\d+/', $arr_seperated_atts[1], $arr_id_matches );
				if ( count( $arr_id_matches ) > 0 ) {
					$arr_list_ids[] = intval( $arr_id_matches[0] );
				}
			}
		}
	}
	return $arr_list_ids;
}




/**
 * Function to extract roster ids from shortcode e.g. [verowa_event_liste title="" id="837" max="4" template_id=5]
 *
 * @param string $str_content Content of a WP post content.
 * @return int[]
 */
function verowa_extract_roster_ids( $str_content ) {

	$arr_ids     = array();
	$arr_matches = array();
	$str_pattern = '/' . get_shortcode_regex( array( 'verowa_roster_entries', 'verowa-first-roster-entry' ) ) . '/s';
	preg_match_all( $str_pattern, $str_content, $arr_matches );

	// The shortcode attributes are stored in $arr_matches[3].
	foreach ( $arr_matches[3] as $str_attributes ) {
		// Separate all attributes.
		$arr_shortcode_attributes = explode( ' ', trim( $str_attributes ) );

		// Separate the value from the shortcode.
		foreach ( $arr_shortcode_attributes as $single_attribute ) {
			$arr_seperated_atts = explode( '=', $single_attribute );

			if ( 'id' === $arr_seperated_atts[0] ) {
				$arr_id_matches = array();
				preg_match( '/\d+/', $arr_seperated_atts[1], $arr_id_matches );
				if ( count( $arr_id_matches ) > 0 ) {
					$arr_ids[] = intval( $arr_id_matches[0] );
				}
			}
		}
	}
	return $arr_ids;
}




/**
 * Put the target_groups, layer_ids and list_ids into a ;1;2;3; format for saving in the DB.
 *
 * @param array  $arr_event Verowa event data.
 * @param string $str_list_ids Verowa lists the IDs to which the event is assigned.
 * @return string[]
 */
function verowa_prepare_layer_list_target_group( $arr_event, $str_list_ids ) {
	$arr_ret = array(
		'target_group_ids' => '',
		'layer_ids'        => '',
		'list_ids'         => '',
	);

	$arr_target_group_ids = array();
	$arr_layer_ids        = array();

	// Put all target_group ids in the format ;1;2;3; (if target_groups are specified at the event).
	if ( is_array( $arr_event['target_groups'] ) && count( $arr_event['target_groups'] ) > 0 ) {
		foreach ( $arr_event['target_groups'] as $single_target_group ) {
			$arr_target_group_ids[] = $single_target_group['id'];
		}

		$arr_ret['target_group_ids'] = ';' . implode( ';', $arr_target_group_ids ) . ';';
	}

	// Put all layer ids in the format ;1;2;3; (if layers are specified at the event).
	if ( is_array( $arr_event['layers'] ) && count( $arr_event['layers'] ) > 0 ) {
		foreach ( $arr_event['layers'] as $single_layer ) {
			$arr_layer_ids[] = $single_layer['id'];
		}

		$arr_ret['layer_ids'] = ';' . implode( ';', $arr_layer_ids ) . ';';
	}

	$arr_ret['list_ids'] = '' !== $str_list_ids ? ';' . str_replace( ',', ';', $str_list_ids ) . ';' : '';

	return $arr_ret;
}




/**
 * Assembles ids for the query where condition
 *
 * @param string $field_name e.g "list_ids".
 * @param string $str_ids e.g. "23;23".
 * @param string $str_condition 'OR' or 'AND'.
 *
 * @return string
 */
function verowa_helper_combine_query_for_ids( $field_name, $str_ids, $str_condition = 'OR' ) {
	$arr_restrictions = array();
	foreach ( explode( ',', $str_ids ) as $single_id ) {
		$arr_restrictions[] = '`' . $field_name . '` LIKE "%;' . trim( $single_id ) . ';%"';
	}

	return '(' . implode( ' ' . $str_condition . ' ', $arr_restrictions ) . ')';
}




/**
 * Deletes all deprecated Verowa events or persons with the corresponding posts
 *
 * @param string $str_table_name Name of the table.
 */
function verowa_general_delete_deprecated( $str_table_name ) {
	global $wpdb;
	$arr_allowed_tables = array( 
		$wpdb->prefix . 'verowa_person', 
		$wpdb->prefix . 'verowa_events', 
		$wpdb->prefix . 'verowa_postings' 
	);

	if ( true === in_array( $str_table_name, $arr_allowed_tables, true ) ) {
		switch ( $str_table_name ) {
			case $wpdb->prefix . 'verowa_person';
				$post_type = 'verowa_person';
				break;

			case $wpdb->prefix . 'verowa_events';
				$post_type = 'verowa_event';
				break;
			
			case $wpdb->prefix . 'verowa_postings';
				$post_type = 'verowa_posting';
				break;

			default:
				$post_type = '';
				break;
		}

		try {
			$wpdb->query( 'START TRANSACTION' );

			$query = 'SELECT `post_id` FROM `' . $str_table_name . '` ' .
				'WHERE `deprecated` = 1;';

			$arr_post_ids = $wpdb->get_results( $query, ARRAY_A );

			if ( is_array( $arr_post_ids ) && count( $arr_post_ids ) > 0 ) {
				foreach ( $arr_post_ids as $arr_row ) {
					if ( true === verowa_wpml_is_configured() ) {
						$trid = apply_filters ( 'wpml_element_trid', null, $arr_row['post_id'], 'post_' . $post_type );
						$arr_translations = apply_filters( 'wpml_get_element_translations', null, $trid, 'post_' . $post_type );
						foreach ( $arr_translations as $obj_single_translation ) {
							$int_post_id = intval( $obj_single_translation->element_id ?? 0 );
							// delete related Posts.
							verowa_delete_post( $int_post_id , $post_type );
						}
					} else {
						// delete related Posts.
						verowa_delete_post( $arr_row['post_id'], $post_type );
					}
				}
			}

			$wpdb->delete(
				$str_table_name,
				array(
					'deprecated' => 1, // value in column to target for deletion.
				),
				array(
					'%d', // format of value being targeted for deletion.
				)
			);
			$wpdb->query( 'COMMIT' );
		} catch ( Exception $exception ) {
			$obj_debug = new Verowa_Connect_Debugger();
			$obj_debug->write_to_file( 'Fehler "general_delete_deprecated" : ' . $exception->getMessage() );
			$wpdb->query( 'ROLLBACK' );
		}
	}
}




/**
 * Set content of verowa post deprecated. Force content update on request.
 *
 * @param string $str_table_name Name of the table.
 */
function verowa_general_set_deprecated_content( $str_table_name ) {
	global $wpdb;
	$wpdb->query(
		'UPDATE `' . $wpdb->prefix . $str_table_name . '` SET `deprecated_content` = 1' .
		' WHERE `deprecated_content` = 0;'
	);
}




/**
 * Wrapper function for wp_insert_post
 *
 * @param array  $arr_post post data.
 * @param string $str_verowa_table_name verowa_events or verowa_person ohne prefix.
 * @param string $str_table_id_key Name of the PK field.
 * @param string $str_script Script which insert in the post metadata.
 * @param string $str_head E.g. meta tags
 *
 * @return int|WP_Error post_id
 */
function verowa_general_insert_custom_post( $arr_post, $str_verowa_table_name, $str_table_id_key, 
	$str_script = '', $str_head = '' ) {
	global $wpdb;
	$int_post_id = null;

	// Update only if the post type is available.
	if ( key_exists( 'post_type', $arr_post ) ) {

		// the post_name is the ID of the event.
		$str_post_name = $arr_post['post_name'] ?? '';
		$post_id       = 0;

		// It checks whether it already has a post with the corresponding name.
		// If so, this is updated to prevent duplicate entries.
		if ( ! empty( $str_post_name ) ) {
			$query   = 'SELECT `ID` FROM `' . $wpdb->prefix . 'posts` WHERE `post_name` = "' .
				$arr_post['post_name'] . '" AND `post_type` = "' . $arr_post['post_type'] . '" ;';
			$post_id = $wpdb->get_results( $query, ARRAY_A )[0]['ID'] ?? 0;
		}

		if ( 0 === $post_id || empty( $post_id ) ) {
			/**
			 * To ensures that the insert works.
			 *
			 * @var int $int_post_id
			 */
			$int_post_id = wp_insert_post( $arr_post, true, true );
			if ( ! is_wp_error( $int_post_id ) ) {
				add_post_meta( $int_post_id, VEROWA_POST_RELATED_SCRIPT_META_KEY, $str_script );
				add_post_meta( $int_post_id, 'VEROWA_HTML_HEAD', $str_head );
			}
		} else {
			$arr_post['ID'] = $post_id;
			/**
			 * To ensures that the update works.
			 *
			 * @var int $int_post_id
			 */
			$int_post_id = wp_update_post( $arr_post, true, true );
			if ( ! is_wp_error( $int_post_id ) ) {
				update_post_meta( $int_post_id, VEROWA_POST_RELATED_SCRIPT_META_KEY, $str_script );
				add_post_meta( $int_post_id, 'VEROWA_HTML_HEAD', $str_head );
			}
		}

		if ( ! is_wp_error( $int_post_id ) ) {
			$bool_do_update = true;

			// Ensure that the ID is only updated for the default language
			if ( true === verowa_wpml_is_configured() ) {
				$str_default_language_code   = verowa_wpml_get_default_language_code();
				if ( false === strpos( '-' . $str_default_language_code, $str_post_name ) ) $bool_do_update = false;
			}

			if ( true === $bool_do_update ) { 
				// Ensures that the verowa_event is correctly linked to the post.
				$wpdb->update(
					$wpdb->prefix . $str_verowa_table_name,
					array(
						'post_id' => intval( $int_post_id ),
					),
					array(
						$str_table_id_key => intval( $str_post_name ),
					),
					array( '%d' ),
					array( '%d' )
				);
			}
		}
	}
	return $int_post_id;
}




/**
 * Update a verowa custom post
 * In the case of an error it throw an exception
 *
 * @param array  $arr_post Array with the post data.
 * @param string $str_script Script which updates the post metadata.
 * @return int post_id
 * @throws Exception If wp_update_post faild an exception will be thrown.
 */
function verowa_general_update_custom_post( $arr_post, $str_script = '', $str_head = '' ) {
	$ret_update_post = '';
	$int_post_id     = $arr_post['ID'] ?? 0;
	if ( $int_post_id > 0 ) {
		$ret_update_post = wp_update_post( $arr_post, true, true );

		if ( is_wp_error( $ret_update_post ) ) {
			// jumps directly into the catch block.
			throw new Exception( $int_post_id . ' update faild: ' . $ret_update_post->get_error_message() );
		}

		if ( '' !== $str_script ) {
			update_post_meta( $int_post_id, VEROWA_POST_RELATED_SCRIPT_META_KEY, $str_script );
		}

		if ( '' !== $str_head ) {
			update_post_meta( $int_post_id, 'VEROWA_HTML_HEAD', $str_head );
		}
	}

	return $ret_update_post;
}




/**
 * Delete a verowa post
 *
 * @param int    $post_id WP post ID.
 * @param string $str_type a verowa type e.g. "verowa_event".
 *
 * @return bool|null|WP_Post
 */
function verowa_delete_post( $post_id, $str_type ) {
	global $wpdb;
	$ret     = null;
	$post_id = intval( $post_id ?? 0 );

	if ( $post_id > 0 ) {
		$query         = 'SELECT `post_type` FROM `' . $wpdb->posts . '` WHERE `ID` = ' . $post_id;
		$str_post_type = $wpdb->get_var( $query ) ?? '';
		if ( $str_post_type === $str_type ) {
			$ret = wp_delete_post( $post_id, true );
		}
	}
	return $ret;
}




/**
 * Convert date8 to date10ch
 * e.g. 12052021 to 12.05.2021
 *
 * @param mixed $date8 e.g. 12082032.
 * @return string
 */
function verowa_date8_to_date10ch( $date8 ) {
	return substr( $date8, 6, 2 ) . '.' . substr( $date8, 4, 2 ) . '.' . substr( $date8, 0, 4 );
}





/**
 * konvertiert die CET-Zeitstempel zu UTC: z.B.
 * "2020-06-12 08:30:00" (der normale Wert aus Verowa) wird zu "2020-06-12T06:30:00+02:00"
 * @param mixed $datetime_cet e.g. 2020-06-12 08:30:00
 * @return string
 */
function pp_convert_cet_to_utc( $datetime_cet, $str_mode = 'url' ) {
	if ( $str_mode === "url" ) {
		return gmdate( 'Ymd\THis', strtotime( $datetime_cet ) ) . '+02:00';
	} else {
		return gmdate( 'Y-m-d\TH:i:s', strtotime( $datetime_cet ) ) . '+02:00';
	}
}





/**
 * Get metadata for the verowa connect
 *
 * @return array
 */
function verowa_connect_get_plugin_data() {
	if ( ! function_exists( 'get_plugin_data' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$str_dir = dirname( __DIR__ );
	return get_plugin_data( $str_dir . '/verowa-connect.php' );
}




/**
 * Return stdClass with validity and in case of an error also a content
 *
 * @return stdClass
 */
function verowa_check_member_api_key() {
	$ret            = new stdClass();
	$ret->validity  = true;
	$verowa_api_key = get_option( 'verowa_api_key', '' );
	$verowa_member  = get_option( 'verowa_instance', '' );

	if ( '1' === strval( $verowa_api_key ) || '1' === strval( $verowa_member ) ||
		true === empty( $verowa_api_key ) || true === empty( $verowa_member ) ) {
		$ret->validity = false;
		$ret->content  = '<div class="verowa_connect_error" >' . VEROWA_APIKEY_MEMBER_ERROR . '<div>';
	}

	return $ret;
}




/**
 * Composes ID from any number of letters and numbers
 *
 * @param int    $int_length Number of characters that the ID should consist of.
 * @param string $str_allowed_chars all characters that may be used for the ID.
 * @return string
 */
function verowa_create_id( $int_length,
	$str_allowed_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
	) {
	$str_id = '';
	for ( $i = 0; $i < $int_length; $i++ ) {
		$str_id .= $str_allowed_chars[ wp_rand( 0, strlen( $str_allowed_chars ) - 1 ) ];
	}

	return $str_id;
}




/**
 * Wrap for the PHP function mail
 *
 * @param string $str_to Receiver, or receivers of the mail.
 * @param string $str_subject Subject of the email to be sent.
 * @param string $str_message Message to be sent.
 * @param bool   $bool_send_always If true, the mail will be sent even if VEROWA_DEBUG is set to false.
 *
 * @return bool
 */
function verowa_send_mail( $str_to, $str_subject, $str_message, $bool_send_always = false ) {
	$ret = false;

	if ( VEROWA_DEBUG || $bool_send_always ) {
		$ret = mail( $str_to, $str_subject, $str_message );
	}
	return $ret;
}




/**
 * If a help text is available, the help icon is displayed.
 * If there is a rollover, the help text is displayed as a ToolTip
 *
 * @param string $str_helptext Text is shown in tool tip.
 * @return string html
 */
function verowa_get_info_rollover( $str_helptext ) {
	$str_ret = '';
	if ( strlen( $str_helptext ) > 0 ) {
		$str_ret = '<i class="dashicons dashicons-info" title="' . htmlspecialchars( $str_helptext ) . '"></i>';
	}

	return $str_ret;
}


/**
 * Earlier VC version created duplicate posts. This function cleans the database.
 */
function verowa_delete_duplicate_custom_post() {
	global $wpdb;

	$query = 'SELECT `ID` FROM `' . $wpdb->prefix . 'posts`' .
		' WHERE `post_type` IN ("verowa_event", "verowa_person") AND `post_name`LIKE "%-2"';

	$arr_post_ids = $wpdb->get_results( $query, ARRAY_A );
	foreach ( $arr_post_ids as $post_id ) {
		if ( ( $post_id ?? 0 ) > 0 ) {
			wp_delete_post( intval( $post_id ), true );
		}
	}
}




/**
 * Auxiliary function to mark the checkboxes as checked
 *
 * @param array $arr_args [is_disabled, helptext, value, name, text].
 * @return array
 */
function verowa_checkbox_html( $arr_args ) {
	$is_disabled           = $arr_args['is_disabled'] ?? false;
	$str_label_class       = $is_disabled ? ' class="verowa_input_disabled" ' : '';
	$str_checkbox_disabled = $is_disabled ? ' disabled' : '';
	$str_checked           = 'on' === $arr_args['value'] || 1 == $arr_args['value'] ? ' checked' : '';

	return array(
		'content'      => '<label' . $str_label_class . '><input type="checkbox" ' .
			'name="' . $arr_args['name'] . '" style="margin-right: 10px"' . $str_checked .
			$str_checkbox_disabled . ' />' . $arr_args['text'] . '</label> ' .
			verowa_get_info_rollover( $arr_args['helptext'] ?? '' ) . '<br />',
		'kses_allowed' => array(
			'label' => array(
				'class' => array(),
			),
			'input' => array(
				'type'     => array(),
				'name'     => array(),
				'style'    => array(),
				'disabled' => array(),
				'checked' => array(),
			),
			'br'    => array(),
			'i'     => array(
				'title' => array(),
				'class' => array(),
			),
		),
	);
}




/**
 * Auxiliary function to mark the radio-button as checked
 *
 * @param array $arr_args [id, helptext, attr_value, name, text].
 * @return string
 */
function verowa_radio_html( $arr_args ) {

	$str_name = $arr_args['name'] ?? '';
	if ( $str_name > '' ) {
		$str_text       = $arr_args['text'] ?? '';
		$str_attr_value = $arr_args['attr_value'] ?? '';
		$str_id         = $arr_args['id'] ?? '';
		$str_value      = $arr_args['value'];
		$str_checked    = $str_attr_value === $str_value ? ' checked' : '';

		$str_ret = '<label for="' . $str_id . '" >' .
			'<input type="radio" id="' . $str_id . '" name="' . $str_name . '" ' .
			'value="' . $str_attr_value . '"' . $str_checked . ' />' .
			$str_text . verowa_get_info_rollover( $arr_args['helptext'] ?? '' ) .
			'</label>';
	} else {
		$str_ret = '<!-- Radio buttons require a name -->';
	}
	return $str_ret;
}




/**
 * Extracts the display values for drop down, radio, multiple_choice
 * Mode value_only is used for validation
 * Mode label_and_value is used for rendering the option in the html control
 *
 * @param string $str_options contain the options on several lines.
 * @param string $mode value_only or label_and_value.
 *
 * @return string[]
 */
function verowa_slice_formfield_options( $str_options, $mode ) {
	$is_first_line = true;
	$str_options   = str_replace( '\r\n', PHP_EOL, $str_options );

	// PREG_SPLIT_NO_EMPTY could not be uesed, because the first line may be empty.
	$pattern               = '/\R/';
	$arr_temp_options      = preg_split( $pattern, $str_options );
	$arr_formfield_options = array();

	foreach ( $arr_temp_options as $str_single_options ) {
		// Only the first line may be empty
		// Others are skipped.
		if ( ! $is_first_line && '' === trim( $str_single_options ) ) {
			continue;
		}

		if ( 'value_only' === $mode ) {
			$arr_formfield_options[] = trim( explode( '||', $str_single_options )[0] );
		} else {
			// Ex: Yes||Yes, I do
			// The value becomes Yes
			// For the label "Yes, I do" without a separator, the value and label are the same.
			$arr_options_pair        = explode( '||', $str_single_options );
			$value                   = trim( $arr_options_pair[0] );
			$label                   = trim( key_exists( 1, $arr_options_pair ) ? $arr_options_pair[1] : $arr_options_pair[0] );
			$arr_formfield_options[] = array( $value, $label );
		}

		if ( $is_first_line ) {
			$is_first_line = false;
		}
	}

	return $arr_formfield_options;
}




/**
 * The passed nodes are compiled into a tree
 * Each node required id, parent id and a children array
 *
 * @param array  $arr_pgroups Array of person groups.
 * @param string $str_id id name.
 * @param string $str_parent parent key name.
 * @return array return the tree.
 */
function verowa_get_tree( $arr_pgroups, $str_id, $str_parent ) {
	$arr_tree         = array();
	$k                = 0;
	$int_pgroup_count = count( $arr_pgroups ?? array() );

	do {
		foreach ( $arr_pgroups as $str_key => $arr_node ) {
			// If the node does not have a parent node, it is added to the root node.
			if ( 0 === intval( $arr_node[ $str_parent ] ) ) {
				$arr_tree[ intval( $arr_node[ $str_id ] ) ] = $arr_node;
				unset( $arr_pgroups[ $str_key ] );
			} else {
				// The parent node must be determined, in the first pass it could be that the node does not exist.
				$are_added = verowa_add_node_to_tree( $arr_tree, $arr_pgroups[ $str_key ], $str_id, $str_parent );
				if ( $are_added ) {
					unset( $arr_pgroups[ $str_key ] );
				}
			}
		}
		$k++;
	} while ( $int_pgroup_count > 0 && $k <= 10 );

	// All groups that are still in $arr_pgroups are added to a general group
	if ( count ($arr_pgroups) > 0)
	$arr_tree[] = [
		'disabled' => true,
		'name' => str_repeat( '&ndash;', 30 ),
	];

	$arr_tree = array_merge( $arr_tree, $arr_pgroups );

	return $arr_tree;
}




/**
 * Add a node to its parent node
 *
 * @param array  $arr_tree Tree to add the node.
 * @param array  $arr_node Node add to the tree.
 * @param string $str_id Node ID.
 * @param string $str_parent Parent Node ID.
 * @return bool
 */
function verowa_add_node_to_tree( &$arr_tree, $arr_node, $str_id, $str_parent ) {
	// If the parent node is in the first node level, it can be added directly.
	if ( key_exists( intval( $arr_node[ $str_parent ] ), $arr_tree ) ) {
		$arr_tree[ intval( $arr_node[ $str_parent ] ) ]['children'][ intval( $arr_node[ $str_id ] ) ] = $arr_node;
		return true;
	} else {
		// Parent node must be searched for in the tree.
		$are_added = false;
		foreach ( $arr_tree as $ele ) {
			$are_added = verowa_add_node_to_tree( $ele['children'], $arr_node, $str_id, $str_parent );
			if ( $are_added ) {
				$arr_tree[ intval( $ele[ $str_id ] ) ]['children'] = $ele['children'];
				break;
			}
		}

		return $are_added;
	}
}




/**
 * Add verowa import data cron job.
 *
 * @return void
 */
function verowa_schedule_importer() {
	$obj_curr_date     = current_datetime();
	$str_instance      = get_option( 'verowa_instance', true );
	$int_minute        = ( hexdec( hash( 'crc32', $str_instance ) ) % 59 ) + 1;
	$obj_date_next_run = DateTime::createFromFormat(
		'Y-m-d H:i:s',
		$obj_curr_date->format( 'Y-m-d H' ) . ':' . str_pad( $int_minute, 2, '0', STR_PAD_LEFT ) . ':00'
	);

	if ( false !== $obj_date_next_run ) {
		wp_schedule_event( $obj_date_next_run->getTimestamp(), 'hourly', 'verowa_connect_importer' );
	}
}




/**
 * Add a Link with the tel protocol and optimize the number for the link:
 * If $str_tel_nr is null oder an empty string an empty string will return
 *
 * @param string $str_tel_nr single phone number.
 *
 * @return string e.g. <a href="tel:0791112233">079 111 22 33</a>
 */
function verowa_add_tel_link( string $str_tel_nr ) {
	$str_tel_with_link = '';
	if ( strlen( $str_tel_nr ?? '' ) > 0 ) {
		$str_link_tel_nr   = str_replace( array( ' ', '-' ), '', $str_tel_nr );
		$str_tel_with_link = '<a href="tel:' . $str_link_tel_nr . '">' . $str_tel_nr . '</a>';
	}
	return $str_tel_with_link;
}




/**
 * Saves the module info from the specified member in the wp option verowa_module_infos
 *
 * @return bool false is returned in the event of an error
 */
function verowaset_set_module_infos() {
	$arr_module_infos = verowa_api_call( 'get_module_infos', '' );
	$int_code         = intval( $arr_module_infos['code'] ?? 0 );
	if ( 200 === $int_code || 204 === $int_code ) {
		return update_option( 'verowa_module_infos', $arr_module_infos['data'] );
	} else {
		return false;
	}
}

/**
 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
 * Non-scalar values are ignored.
 *
 * @param string|array $var Data to sanitize.
 * @return string|array
 */
function verowa_clean( $var ) {
	if ( is_array( $var ) ) {
		return array_map( 'verowa_clean', $var );
	} else {
		return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
	}
}

add_filter( 'the_content', 'verowa_add_back_button', 999 );

/**
 * This filter automatically adds the back button on all pages
 *
 * @param string $content HTML post content.
 *
 * @return string the modified post content.
 */
function verowa_add_back_button( $content ) {
	global $post;

	// die Seite muss für den Button "qualifiziert" sein.
	if ( is_single() ) {
		$str_link_test = verowa_tf( 'Back', __( 'Back', 'verowa-connect' ));
		$content .= '<a class="back-link button" onclick="verowa_do_history_back(); return false;" href="#" style="display:none;" >' .
			$str_link_test . '</a>';
	}

	return $content;
}




/**
 * Get the base URL for the WordPress website.
 *
 * This function retrieves the base URL for the website, considering the permalink structure
 * and multisite configurations.
 *
 * @return string The base URL of the website.
 */
function verowa_get_base_url() {
	$index_php_prefix = '';
	$blog_prefix = '';
	$permalink_structure = get_option( 'permalink_structure' );

	/*
	 * In a subdirectory configuration of multisite, the `/blog` prefix is used by
	 * default on the main site to avoid collisions with other sites created on that
	 * network. If the `permalink_structure` option has been changed to remove this
	 * base prefix, WordPress core can no longer account for the possible collision.
	 */
	if ( is_multisite() && ! is_subdomain_install() && is_main_site() &&
		preg_match( "/^\/blog\/(.*)/i", trim( $permalink_structure ) ) > 0 ) {
		$blog_prefix = '/blog';
	}
	$url_base = home_url( $blog_prefix . $index_php_prefix );
	return $url_base;
}




function verowa_download_image_and_add_attachment( $image_url, $wp_post_id )
{

	if ( trim( $image_url ) == "" || $wp_post_id == 0 ) {
		return false;
	}

	// Initialize cURL session
	$ch = curl_init($image_url);

	// Set cURL options
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		
// UNDONE: Weite Einbauen, wenn die Funktion lokal getestet wird
// curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

	// Download the image file
	$imageData = curl_exec( $ch );

	// Check for download errors
	if ( curl_errno( $ch ) ) {
		//echo "Error downloading image: " . curl_error($ch);
		//exit;
	}

	// Close cURL session
	curl_close( $ch );

	// Get image type based on url
	$pattern = '#(.+)?\.(\w+)(\?.+)?#';
	preg_match($pattern, $image_url, $matches);
  
	if (isset($matches[0])) {
		$imageType = $matches[2]; // Remove leading dot
	} else {
		$imageType = null; // No extension found
	}

	if ( $imageType !== false && $imageType !== null ) {
		// Generate a unique temporary filename
		$tempFilename = uniqid( 'posting-' ) . '.' . $imageType;

		// Open the temporary file for writing
		$tempFile = fopen( $tempFilename, 'wb' );

		// Write the downloaded image data to the temporary file
		fwrite( $tempFile, $imageData );

		// Close the temporary file
		fclose( $tempFile );

		// Use wp_upload_bits to upload the temporary file
		$upload = wp_upload_bits( $tempFilename, null, file_get_contents( $tempFilename ) );

		// Check for upload errors
		if ( $upload['error'] === false ) {
			// File uploaded successfully, proceed with attachment creation
			$attachment_id = wp_insert_attachment( array(
			'post_mime_type' => $upload['type'],
			'post_title' => preg_replace( '/\.[^.]+$/', '', $tempFilename ), // Remove file extension from title
			'post_parent'    => $wp_post_id, // <-- Verknüpft den Anhang mit dem Beitrag
			'post_content' => '',
			'post_status' => 'inherit',
			'meta_input'   => array(
				'verowa_image_url' => $image_url,
			),
			), $upload['file'] );
		} else {
			// Handle upload error
			echo "Error uploading image: " . $upload['error'];
		}

		set_post_thumbnail( $wp_post_id, $attachment_id );

		// Delete the temporary file after uploading
		unlink( $tempFilename );
	}
}


/**
 * Übersetzt einen Text in die angegebene oder ermittelte Sprache.
 *
 * @param string $text Der zu übersetzende Text.
 * @param string $str_translated_string Die Standardübersetzung, falls keine passende Übersetzung gefunden wird.
 * @param string|null $str_lang_code (Optional) Der Sprachcode (z.B. 'de', 'en'). Wenn nicht angegeben, wird der aktuelle Sprachcode über `verowa_wpml_get_current_language()` ermittelt.
 *
 * @return string Der übersetzte Text oder die Standardübersetzung (ggf. mit Fallback-Hinweis).
 */
function verowa_tf( $text, $str_translated_string, $str_lang_code = null) {
	$str_lang_code = $str_lang_code ?? verowa_wpml_get_current_language();
	$arr_av_translation = json_decode( get_option('verowa_translations_' . $str_lang_code, '[]'), true);
	if ( true === key_exists($text, $arr_av_translation) ) {
		$str_ret = $arr_av_translation[$text] ?? $str_translated_string . ' - (fallback)';
	} else {
		$str_ret = $str_translated_string;
	}
	return $str_ret;
}


/**
 * Stellt aus dem files array eine Liste von Dateien zusammen.
 * 
 * @param array $files 
 * @param boolean $b_with_file_size 
 * @param string $str_language_code
 * 
 * @return string HTML-Liste von Dateien
 */
function verowa_get_file_list ($files, $b_with_file_size, $str_language_code = 'de') {
	$files_content = '';
	if ( true === is_array( $files ) ) {
		foreach ( $files as $file ) {
			$str_url = $file['url'] ?? $file['file_url'] ?? '';
			$str_desc = trim( $file['desc'] ?? $file['description'] ?? '' );
			$files_content .= '<a href="' . $str_url . '" target="_blank" >' .
				(( '' != $str_desc ) ? $str_desc : $file['file_name']) .
				'</a>';

			if ( true === $b_with_file_size ) {
				// File Type and Size (Size wegen Verowa-Fehler bisher nicht da)
				// translators: context = e.g. "PDF file"; MB = abbrev. Megabytes.
				$files_content .= ' (' . $file['file_type'] . '&ndash;' .
					verowa_tf( 'file', __( 'file', 'verowa-connect' ), $str_language_code ) . ', ' .
					number_format( ( $file['filesize_kb'] / 1024 ), 1, ',', '\'' ) . ' ' .
					verowa_tf( 'MB', _x( 'MB', 'abbrev. Megabytes', 'verowa-connect' ), $str_language_code ) . ')';
			}
		}
	}

	return $files_content;
}



/**
 * Returns the HTML for an inline error message.
 * 
 * @param int $post_id 
 * @param boolean $b_with_file_size 
 * @param string $str_language_code
 * 
 * @return string HTML
 */
function verowa_get_inline_error( $str_error_msg ) {
	return '<p class="verowa-inline-error-msg pp_inline_error_msg">' . $str_error_msg . '</p>';
}




/**
 * Set selected for dropdown-menu
 *
 * @param string $field_name
 * @param array  $arr_options
 * @param string $value
 * @return string
 */
function verowa_set_dd_selected( $field_name, $arr_options, $value ) {
	$ret = '';

	if ( is_array( $arr_options ) && key_exists( $field_name, $arr_options ) && intval( $arr_options[ $field_name ] ) == $value ) {
		$ret = 'selected';
	}

	return $ret;
}




/**
 * Set checked for checkboxes
 *
 * @param string $field_name
 * @param array  $arr_options
 * @return string
 */
function verowa_set_cb_checked( $field_name, $arr_options ) {
	$ret = '';

	if ( key_exists( $field_name, $arr_options ) && 'on' === $arr_options[ $field_name ] ) {
		$ret = 'checked';
	}

	return $ret;
}
