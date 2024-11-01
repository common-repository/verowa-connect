<?php
/**
 * Adds meta boxes to select per post list IDs, target groups and layer IDs from which events are to be displayed.
 *
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @package Verowa Connect
 * @subpackage Events
 */


add_action( 'add_meta_boxes', 'verowa_list_assign_add_meta_box' );
add_action( 'save_post', 'verowa_list_assign_save_meta_box_data' );
add_filter( 'the_content', 'add_verowa_list_if_selected', 8 );

/**
 *
 */
function verowa_list_assign_add_meta_box() {
	$screens = array(
		'post',
		'page',
	);

	foreach ( $screens as $screen ) {
		add_meta_box(
			'verowa_list_assign_sectionid',
			__(
				'Event list mapping',
				'verowa-connect'
			),
			'verowa_list_assign_meta_box_callback',
			$screen
		);
	}
}




/**
 * Creates the meta box HTML
 *
 * @param WP_Post $post
 */
function verowa_list_assign_meta_box_callback( $post ) {
	// Add a nonce field so we can check for it later.
	wp_nonce_field( 'verowa_list_assign_meta_box', 'verowa_list_assign_meta_box_nonce' );

	$arr_group_data = json_decode( get_option( 'verowa_agenda_filter', '[]' ), true );

	$int_list_id         = get_post_meta( $post->ID, '_verowa_list_assign', true );
	$int_layer_id        = get_post_meta( $post->ID, '_verowa_layer_assign', true );
	$target_groups       = get_option( 'verowa_targetgroups' );
	$int_target_group_id = get_post_meta( $post->ID, '_verowa_target_group_assign', true );

	$max_events                      = get_post_meta( $post->ID, '_max_events', true );
	$max_events_days                 = get_post_meta( $post->ID, '_max_events_days', true );
	$str_list_title                  = get_post_meta( $post->ID, 'verowa_list_title', true );
	$int_eventlist_selected_template = get_post_meta( $post->ID, 'verowa_eventlist_template', true );

	$str_select_list_settings = $_POST['verowa_select_list_settings'] ?? '';
	if ( '' == $str_select_list_settings ) {
		if ( $int_list_id > 0 ) {
			$str_select_list_settings = 'lists';
		} elseif ( $int_layer_id > 0 ) {
			$str_select_list_settings = 'layers';
		} elseif ( $int_target_group_id > 0 ) {
			$str_select_list_settings = 'target_groups';
		}
	}

	echo '<table><tr><td class="verowa-list-option" >';
	echo verowa_radio_html(
		array(
			'id'         => 'verowa_list_select',
			'attr_value' => 'lists',
			'value'      => $str_select_list_settings,
			'name'       => 'verowa_select_list_settings',
			'text'       => __( 'List', 'verowa-connect' ),
		)
	) . ':</td>' .
		'<td>';

	$str_ddl_list = '<select id="pg" name="listen" class="verowa-ddl-list-settings" ><option value="0"> </option>';
	if ( is_array( $arr_group_data ) ) {
		foreach ( $arr_group_data as $arr_group_single_data ) {
			$str_ddl_list .= '<option value="' . $arr_group_single_data['list_id'] . '"';

			if ( $int_list_id == $arr_group_single_data['list_id'] ) {
				$str_ddl_list .= ' selected';
			}

			$str_ddl_list .= '>' . $arr_group_single_data['name'] . ' (Nr. ' . $arr_group_single_data['list_id'] . ')</option>';
		}
	}
	$str_ddl_list .= '</select></td>' .
		'</tr><tr>';

	echo $str_ddl_list;

	if ( function_exists( 'verowa_layers_dropdown' ) ) {
		echo '<td class="verowa-list-option" >' . verowa_radio_html(
			array(
				'id'         => 'verowa_layers_select',
				'attr_value' => 'layers',
				'value'      => $str_select_list_settings,
				'name'       => 'verowa_select_list_settings',
				'text'       => __( 'Layer', 'verowa-connect' ),
			)
		) . ':' . '</td>' .
		'<td>';

		echo verowa_layers_dropdown( $post->ID ) .
		'</td>';
	}

	echo '</tr>';

	// New section for direct selection of a target group.
	echo '<tr><td class="verowa-list-option" >' . verowa_radio_html(
		array(
			'id'         => 'verowa_target_groups_select',
			'attr_value' => 'target_groups',
			'value'      => $str_select_list_settings,
			'name'       => 'verowa_select_list_settings',
			'text'       => __( 'Target groups / regions', 'verowa-connect' ),
		)
	) . ':</td>' .
	'<td>';

	$str_ddl_target_groups = '<select name="target_groups" class="verowa-ddl-list-settings" ><option value="0"> </option>';
	if (is_array ($target_groups) === true) {
		foreach ( $target_groups as $target_group ) {
			$str_ddl_target_groups .= '<option value="' . $target_group['group_id'] . '"';

			if ( $int_target_group_id == $target_group['group_id'] ) {
				$str_ddl_target_groups .= ' selected';
			}

			$str_ddl_target_groups .= '>' . $target_group['longname'] . ' (Nr. ' . $target_group['group_id'] . ')</option>';
		}
	}
	$str_ddl_target_groups .= '</select>' .
		'</td></tr>';

	echo $str_ddl_target_groups;

	// Max. days in advance
	echo '<tr><td>' . esc_html( __( 'max. Number', 'verowa-connect' ) ) . '</td>' .
		'<td><input style="width: 20%;" type="number" value="' . esc_attr( $max_events ) . '" name="max_events" />&nbsp;' . esc_html( __( '0 = all', 'verowa-connect' ) ) . '</td></tr>';

	// Max. number.
	echo '<tr><td>' . __( 'max. days in advance', 'verowa-connect' ) . '</td>' .
		'<td><input style="width: 20%;" type="number" value="' . esc_attr( $max_events_days ) . '" name="max_events_days" />&nbsp;' . esc_html( __( '0 = no matter', 'verowa-connect' ) ) . '</td></tr>';

	// Heading above the list.
	echo '<tr><td>' . esc_html( __( 'Caption', 'verowa-connect' ) ) . '</td>' .
		'<td><input style="width: 70%;" type="text" name="verowa_list_title" value="' . esc_attr( $str_list_title ) . '" /></td></tr>';

	$arr_ddl = verowa_show_template_dropdown( 'eventlist', $int_eventlist_selected_template );
	// List template.
	echo '<tr><td>' . esc_html( __( 'Template', 'verowa-connect' ) ) . '</td>' .
		'<td>' . wp_kses( $arr_ddl['content'], $arr_ddl['kses_allowed'] ) . '</td></tr>';

	echo '<tr><td colspan="2"><strong>Shortcode: ' .
		'[verowa_event_list title="' .
		esc_html(
			_x(
				'Current events',
				'Sample title for shortcode Attribute',
				'verowa-connect'
			)
		) .
		'" id="197" max="2" template_id=5 ]</strong>' .
		'<a class="verowa-manual-link" href="https://verowa-connect.ch/dokumentation/konfiguration/shortcodes/#verowa_event_list" target="_blank">' .
		esc_html( __( 'Manual', 'verowa-connect' ) ) . '<a><td>';

	echo '</table>';
}



/**
 * The data of the metabox created above is saved.
 *
 * @param int $post_id The ID of the post being saved.
 */

function verowa_list_assign_save_meta_box_data( $post_id ) {
	/*
	* We need to verify this came from our screen and with proper authorization,
	* because the save_post action can be triggered at other times.
	*/

	// Check if our nonce is set.
	if ( ! isset( $_POST['verowa_list_assign_meta_box_nonce'] ) ) {
		return;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST['verowa_list_assign_meta_box_nonce'], 'verowa_list_assign_meta_box' ) ) {
		return;
	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check the user's permissions.
	if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}
	} else {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	/* OK, it's safe for us to save the data now. */

	// Sanitize user input.
	$verowa_list_assign  = sanitize_text_field( $_POST['listen'] ?? '' );
	$verowa_layer_assign = sanitize_text_field( $_POST['bereiche'] ?? '' );
	$verowa_tg_assign    = sanitize_text_field( $_POST['target_groups'] ?? '' );

	// Make sure that it is set.
	switch ( $_POST['verowa_select_list_settings'] ?? '' ) {
		case 'lists':
			// Only when a list is selected update the mapping.
			if ( '' !== $verowa_list_assign ) {
				$current_list_assign = get_post_meta( $post_id, '_verowa_list_assign', true );
				if ( $verowa_list_assign != $current_list_assign ) {
					verowa_update_list_id_option();
					$obj_update = new Verowa_Update_Controller();
					$obj_update->init( 'list_map' );
					$obj_update->update_verowa_event_list_mapping();
				}
			}
			break;
	}

	$max_events            = intval( $_POST['max_events'] );
	$max_events_days       = intval( $_POST['max_events_days'] );
	$str_verowa_list_title = sanitize_text_field( $_POST['verowa_list_title'] );

	// Update the meta field in the database.
	update_post_meta( $post_id, '_verowa_list_assign', $verowa_list_assign );
	update_post_meta( $post_id, '_verowa_layer_assign', $verowa_layer_assign );
	update_post_meta( $post_id, '_verowa_target_group_assign', $verowa_tg_assign );

	update_post_meta( $post_id, '_max_events', $max_events );
	update_post_meta( $post_id, '_max_events_days', $max_events_days );
	update_post_meta( $post_id, 'verowa_list_title', $str_verowa_list_title );

	// Event list templates
	if ( isset( $_POST['verowa_select_template_eventlist'] ) ) {
		update_post_meta( $post_id, 'verowa_eventlist_template', intval( $_POST['verowa_select_template_eventlist'] ) );
	}

	do_action(
		'verowa_purge_shortcode_cache',
		array(
			'verowa_agenda',
			'verowa_event_liste_dynamic',
			'verowa_event_filter',
			'verowa_event_list',
			'verowa_event_liste',
			'verowa_subscriptions_form',
		)
	);
}


/**
 * Callback function for content filter to add the list
 *
 * @param string $content
 *
 * @return string
 */
function add_verowa_list_if_selected( $content ) {
	global $post, $wp_query;

	$single_persons      = get_post_meta( $post->ID, '_person_singles', true );
	$int_list_id         = get_post_meta( $post->ID, '_verowa_list_assign', true );
	$int_layer_id        = get_post_meta( $post->ID, '_verowa_layer_assign', true );
	$int_target_group_id = get_post_meta( $post->ID, '_verowa_target_group_assign', true );

	$int_max        = get_post_meta( $post->ID, '_max_events', true );
	$int_max_days   = get_post_meta( $post->ID, '_max_events_days', true );
	$str_list_title = get_post_meta( $post->ID, 'verowa_list_title', true );
	$str_extra_vars = '';

	$int_eventlist_template = intval( get_post_meta( $post->ID, 'verowa_eventlist_template', true ) );

	if ( 0 === $int_eventlist_template ) {
		$int_eventlist_template = intval( get_option( 'verowa_default_eventlist_template' ) );
	}

	$curr_language_code      = verowa_wpml_get_current_language();
	$obj_template            = verowa_get_single_template( $int_eventlist_template, $curr_language_code, 'content' );

	if ( ! empty( $obj_template ) ) {
		// Process max events and max days.
		if ( 0 !== $int_max ) {
			$str_extra_vars .= ' pro-seite=' . $int_max . ' max=' . $int_max . ' ';

			if ( 0 !== $int_max_days ) {
				$str_extra_vars .= 'max_days=' . $int_max_days . ' ';
			}
		}

		if ( '' !== $str_list_title ) {
			$str_extra_vars .= 'title="' . $str_list_title . '" ';
		}

		$bool_show_wrapper = false;

		if ( $int_list_id > 0 && ! $wp_query->is_search() ) {
			$content          .= '[verowa_event_list id=' . $int_list_id . ' ' . $str_extra_vars . ']';
			$bool_show_wrapper = true;
		}

		if ( $int_layer_id > 0 && ! $wp_query->is_search() ) {
			$content          .= '[verowa_event_list layer_id=' . $int_layer_id . ' ' . $str_extra_vars . ']';
			$bool_show_wrapper = true;
		}

		if ( $int_target_group_id > 0 && ! $wp_query->is_search() ) {
			$content          .= '[verowa_event_list target_group=' . $int_target_group_id . ' ' . $str_extra_vars . ']';
			$bool_show_wrapper = true;
		}

		if ( is_array( $single_persons ) && count( $single_persons ) > 0 ) {
			$bool_show_wrapper = true;
		}

		if ( $bool_show_wrapper ) {
			$content .= '</div>';
		}
	}

	return $content;
}
