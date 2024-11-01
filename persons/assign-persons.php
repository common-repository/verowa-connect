<?php
/**
 * Adds a so-called custom meta box that is needed for the person assignment.
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @package Verowa Connect
 * @subpackage Person
 */

add_action( 'add_meta_boxes', 'verowa_person_assign_add_meta_box' );
add_action( 'save_post', 'verowa_person_assign_save_meta_box_data' );
add_filter( 'the_content', 'add_verowa_persons_if_selected', 7 );

/**
 * Register the meta box to assign persons to post and page screens.
 * The meta box will be displayed on the edit screen for posts and pages.
 *
 * @return void
 */
function verowa_person_assign_add_meta_box() {
	$screens = array(
		'post',
		'page',
	);

	foreach ( $screens as $screen ) {
		add_meta_box(
			'verowa_person_assign_sectionid',
			__( 'Assign persons', 'verowa-connect' ),
			'verowa_person_assign_meta_box_callback',
			$screen
		);
	}
}



/**
 * Callback of add_meta_box "Assign persons"
 *
 * @param WP_POST $post A post object.
 */
function verowa_person_assign_meta_box_callback( $post ) {
	global $post, $wpdb;
	wp_nonce_field( 'verowa_person_assign_meta_box', 'verowa_person_assign_meta_box_nonce' );
	echo '<div class="row"><div class="col col-md-6">';

	$arr_group_data        = verowa_person_group_db_get();
	$verowa_group_dropdown = verowa_group_dropdown( $arr_group_data );

	$arr_all_persons        = verowa_persons_get_multiple( null, 'FULL' );
	$verowa_person_dropdown = verowa_person_dropdown( $arr_all_persons );
	$int_selected_template  = intval( get_post_meta( $post->ID, 'verowa_personlist_template', true ) );

	echo '<span id="plusperson">' .
		'<span style="vertical-align:middle;" class="dashicons dashicons-plus-alt" ></span> ' .
		esc_html( __( 'Add person', 'verowa-connect' ) ) . '</span><br />';

	echo '<span id="plusgroup">' .
		'<span style="vertical-align:middle;" class="dashicons dashicons-plus-alt" ></span> ' .
		esc_html( __( 'Add group', 'verowa-connect' ) ) . '</span><br />';

	echo '<span id="plustext">' .
		'<span style="vertical-align:middle;" class="dashicons dashicons-plus-alt" ></span> ' .
		esc_html( __( 'Add caption', 'verowa-connect' ) ) . '</span>';

	?>
	<script>
		var plusbutton = document.getElementById('plusperson');
		var plustext = document.getElementById('plustext');

		jQuery("#plusgroup, #plusperson").on('click', function() {
		var text =  jQuery('select[name="personeneinzeln"] option:selected' ).text();

		if (jQuery(this).attr("id") == 'plusperson') {
			jQuery(".personen").append("<li id='person_single'><span class='dashicons dashicons-move'>" +
				"</span><span class='dashicons dashicons-admin-users'>" +
				"</span><?php echo $verowa_person_dropdown; ?><a href='#' id='kill'> " +
				"<span class='dashicons dashicons-dismiss'></span></a></li>");
		} else {
			jQuery(".personen").append("<li id='person_single'><span class='dashicons dashicons-move'>" +
				"</span><span class='dashicons dashicons-groups'>" +
				"</span><?php echo $verowa_group_dropdown; ?><a href='#' id='kill'>" +
				" <span class='dashicons dashicons-dismiss'></span></a></li>");
		} });

		plustext.addEventListener('click', function() {
			var value = jQuery('select[name="personeneinzeln"]').val();
			var text =  jQuery('select[name="personeneinzeln"] option:selected' ).text();
			jQuery(".personen").append("<li id='person_single'><span class='dashicons dashicons-move'>" +
				"</span><span class='dashicons dashicons-format-aside'></span>" +
				"<input type='text' name='personeneinzeln[]' /></a><a href='#' id='kill'>" +
				"<span class='dashicons dashicons-dismiss'></span></a></li>");
		}, false);

		jQuery( function() {
			jQuery(document).on('click touch', "#kill", function() {
				jQuery(this).parent().remove();
			});
			jQuery( ".personen" ).sortable();
			jQuery( ".personen" ).disableSelection();
		} );

	</script>
	<style>
	.personen a, #plustext, #plusperson {
		text-decoration: none;
	}

	.col {
		display: inline-block;
		width: 49%;
		vertical-align: top;
	}

	.row {
		display: flex;
		width: 100%;
		vertical-align: top;
	}

	.verowa-personlist-template, .verowa-personlist-options {
		width: 50%;
	}

	</style>
	<?php
	echo '<p><strong>Shortcode: ' .
	'[verowa_personen id="group-7" comp_tag="th"]</strong>' .
	'<a class="verowa-manual-link" href="https://verowa-connect.ch/dokumentation/konfiguration/shortcodes/#verowa_personen" target="_blank">' .
	esc_html( __( 'Manual', 'verowa-connect' ) ) . '</a></p>';
	echo '<ul class="personen">';

	$arr_alle_einzelnen_person = get_post_meta( $post->ID, '_person_singles', true );

	if ( is_array( $arr_alle_einzelnen_person ) ) {
		foreach ( $arr_alle_einzelnen_person as $str_einzelne_person ) {

			if ( '' !== $str_einzelne_person && is_numeric( $str_einzelne_person ) ) {
				echo '<li id="person_single"><span class="dashicons dashicons-move"></span>' .
				'<span class="dashicons dashicons-admin-users"></span>' .
				stripslashes( verowa_person_dropdown( $arr_all_persons, $str_einzelne_person ) ) .
				'<a href="#" id="kill"> <span class="dashicons dashicons-dismiss"></span></a></li>';
			} elseif ( false !== strpos( $str_einzelne_person, 'group-' ) ) {
				echo '<li id="person_single"><span class="dashicons dashicons-move"></span>' .
				'<span class="dashicons dashicons-groups"></span>' .
				stripslashes( verowa_group_dropdown( $arr_group_data, str_replace( 'group-', '', $str_einzelne_person ) ) ) .
				'<a href="#" id="kill"> <span class="dashicons dashicons-dismiss"></span></a></li>';
			} else {
				echo '</span><li id="person_single"><span class="dashicons dashicons-move"></span>' .
				'<span class="dashicons dashicons-format-aside"></span>' .
				'<input type="text" name="personeneinzeln[]" value="' . $str_einzelne_person . '" /></a>' .
				'<a href="#" id="kill"><span class="dashicons dashicons-dismiss"></span></a></li>';
			}
		}
	}

	echo '</ul></div>';
	$arr_ddl = verowa_show_template_dropdown( 'personlist', $int_selected_template );
	echo '<div class="col col-md-6" style="display: flex; flex-direction: row;"><div class="verowa-personlist-template"><h4>' .
		esc_html( __( 'Select Persons Template', 'verowa-connect' ) ) . '</h4>' .
		wp_kses( $arr_ddl['content'], $arr_ddl['kses_allowed'] ) . '</div>';
	echo '<div class="verowa-personlist-options">';

	$obj_current_screen = get_current_screen();
	$str_acction        = isset( $obj_current_screen->action ) ? $obj_current_screen->action : '';
	if ( 'add' === $str_acction ) {
		$persons_have_detail_link = get_option( 'verowa_persons_without_detail_page', false ) == 'on' ?
			'' : 'on';
		$person_group_function    = '';
		$person_profession        = '';
		$person_address           = '';
		$person_email             = '';
		$person_phone             = '';
		$person_short_desc        = '';
	} else {
		$persons_have_detail_link = get_post_meta( $post->ID, 'verowa_persons_have_detail_link', true );
		$person_profession        = get_post_meta( $post->ID, 'verowa_person_show_profession', true );
		$person_address           = get_post_meta( $post->ID, 'verowa_person_show_address', true );
		$person_email             = get_post_meta( $post->ID, 'verowa_person_show_person_email', true );
		$person_phone             = get_post_meta( $post->ID, 'verowa_person_show_person_phone', true );
		$person_short_desc        = get_post_meta( $post->ID, 'verowa_person_short_desc', true );
		$person_group_function    = get_post_meta( $post->ID, 'verowa_person_show_group_function', true );
	}

	echo '<b>' . esc_html( __( 'Display options', 'verowa-connect' ) ) . '</strong></b><br />';

	// Option "Without detail pages" is selected, its override post_meta "persons_have_detail_link".
	$persons_have_detail_link_disabled = false;
	$str_helptext                      = '';

	if ( 'on' === get_option( 'verowa_persons_without_detail_page', false ) ) {
		$persons_have_detail_link          = false;
		$persons_have_detail_link_disabled = true;
		$str_helptext                      = esc_html(
			__(
				'This checkbox is deactivated because the option "Without detail pages" is selected for the persons in the Verowa options.',
				'verowa-connect'
			)
		);
	}

	$arr_cb = verowa_checkbox_html(
		array(
			'name'        => 'verowa_persons_have_detail_link',
			'text'        => __( 'Link to the detail page', 'verowa-connect' ),
			'value'       => $persons_have_detail_link,
			'is_disabled' => $persons_have_detail_link_disabled,
			'helptext'    => $str_helptext,
		)
	);
	echo wp_kses( $arr_cb['content'], $arr_cb['kses_allowed'] );

	$arr_cb = verowa_checkbox_html(
		array(
			'name'  => 'verowa_person_group_function',
			'text'  => __( 'Show group function', 'verowa-connect' ),
			'value' => $person_group_function,
		)
	);
	echo wp_kses( $arr_cb['content'], $arr_cb['kses_allowed'] );

	$arr_cb = verowa_checkbox_html(
		array(
			'name'  => 'verowa_person_short_desc',
			'text'  => __( 'Show short text', 'verowa-connect' ),
			'value' => $person_short_desc,
		)
	);
	echo wp_kses( $arr_cb['content'], $arr_cb['kses_allowed'] );

	$arr_cb = verowa_checkbox_html(
		array(
			'name'  => 'verowa_person_profession',
			'text'  => __( 'Show profession', 'verowa-connect' ),
			'value' => $person_profession,
		)
	);
	echo wp_kses( $arr_cb['content'], $arr_cb['kses_allowed'] );

	$arr_cb = verowa_checkbox_html(
		array(
			'name'  => 'verowa_person_address',
			'text'  => __( 'Show address', 'verowa-connect' ),
			'value' => $person_address,
		)
	);
	echo wp_kses( $arr_cb['content'], $arr_cb['kses_allowed'] );

	$arr_cb = verowa_checkbox_html(
		array(
			'name'  => 'verowa_person_email',
			'text'  => __( 'Show e-mail', 'verowa-connect' ),
			'value' => $person_email,
		)
	);
	echo wp_kses( $arr_cb['content'], $arr_cb['kses_allowed'] );

	$arr_cb = verowa_checkbox_html(
		array(
			'name'  => 'verowa_person_phone',
			'text'  => __( 'Show phone number', 'verowa-connect' ),
			'value' => $person_phone,
		)
	);
	echo wp_kses( $arr_cb['content'], $arr_cb['kses_allowed'] );

	echo '</div></div>';
}




/**
 * Die Daten der oben erzeugten Metabox werden abgespeichert.
 * Da das zu 50% aus WP-Eigenem Code besteht, werden die Original-Kommentare behalten.
 *
 * @param int $post_id The ID of the post being saved.
 */
function verowa_person_assign_save_meta_box_data( $post_id ) {
	// Verify alle nonce in the current request.
	if ( ! isset( $_POST['verowa_person_assign_meta_box_nonce'] ) ||
		! wp_verify_nonce( $_POST['verowa_person_assign_meta_box_nonce'], 'verowa_person_assign_meta_box' ) ||
		defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || ! current_user_can( 'edit_page', $post_id ) ) {

		return;
	}

	// Sanitize user input.
	$verowa_person_assign     = sanitize_text_field( $_POST['personen'] ?? '' );
	$verowa_personen_group    = sanitize_text_field( $_POST['persongroup'] ?? '' );
	$verowa_personen_subgroup = sanitize_text_field( $_POST['include_subgroups'] ?? '' );
	$pers_groups_title        = sanitize_text_field( $_POST['pers_group_title'] ?? '' );

	// Update the meta field in the database.
	if ( '' != $verowa_person_assign ) {
		update_post_meta( $post_id, '_verowa_person_assign', $verowa_person_assign );
	}
	if ( '' != $verowa_personen_group ) {
		update_post_meta( $post_id, '_verowa_person_group', $verowa_personen_group );
	}
	if ( '' != $verowa_personen_subgroup ) {
		update_post_meta( $post_id, '_verowa_person_subgroup', $verowa_personen_subgroup );
	}
	if ( '' != $pers_groups_title ) {
		update_post_meta( $post_id, '_verowa_person_group_title', $pers_groups_title );
	}

	update_post_meta(
		$post_id,
		'verowa_persons_have_detail_link',
		isset( $_POST['verowa_persons_have_detail_link'] ) ? 'on' : false
	);

	update_post_meta(
		$post_id,
		'verowa_person_show_group_function',
		isset( $_POST['verowa_person_group_function'] ) ? 'on' : false
	);

	update_post_meta(
		$post_id,
		'verowa_person_show_profession',
		isset( $_POST['verowa_person_profession'] ) ? 'on' : false
	);

	update_post_meta(
		$post_id,
		'verowa_person_show_address',
		isset( $_POST['verowa_person_address'] ) ? 'on' : false
	);

	update_post_meta(
		$post_id,
		'verowa_person_show_person_email',
		isset( $_POST['verowa_person_email'] ) ? 'on' : false
	);

	update_post_meta(
		$post_id,
		'verowa_person_show_person_phone',
		isset( $_POST['verowa_person_phone'] ) ? 'on' : false
	);

	update_post_meta(
		$post_id,
		'verowa_person_short_desc',
		isset( $_POST['verowa_person_short_desc'] ) ? 'on' : false
	);

	update_post_meta( $post_id, 'verowa_has_person_options_on_post_level', true );

	$str_personeneinzeln = key_exists( 'personeneinzeln', $_POST ) ? $_POST['personeneinzeln'] : '';
	update_post_meta( $post_id, '_person_singles', $str_personeneinzeln );

	$int_verowa_select_template_personlist = key_exists( 'verowa_select_template_personlist', $_POST ) ? $_POST['verowa_select_template_personlist'] : '';
	update_post_meta( $post_id, 'verowa_personlist_template', intval( $int_verowa_select_template_personlist ) );

	do_action(
		'verowa_purge_shortcode_cache',
		array(
			'verowa_person',
			'verowa_personen',
			'verowa_event_list',
		)
	);
}




/**
 * Returns the HTML for a dropdown menu containing options for selecting persons.
 *
 * @param mixed $arr_all_persons An array containing person data with firstname, lastname, and person_id.
 * @param mixed $selected The value of the selected option. (Optional)
 *
 * @return string The HTML for the dropdown menu containing person options.
 */
function verowa_person_dropdown( $arr_all_persons, $selected = '' ) {
	$arr_firstname = array_column( $arr_all_persons, 'firstname' );
	$arr_name      = array_column( $arr_all_persons, 'lastname' );
	$arr_all_ids   = array_column( $arr_all_persons, 'person_id' );
	$str_class     = '';

	// phpcs: ignore @codingStandardsIgnoreLine no strict comparison is needed.
	if ( '' !== $selected && false === in_array( $selected, $arr_all_ids ) ) {
		$str_class = 'class="verowa-select-error-first-child" ';
	}

	$html = '<select id="personselect" ' . $str_class . 'name="personeneinzeln[]">';
	// phpcs: ignore @codingStandardsIgnoreLine no strict comparison is needed.
	if ( '' !== $selected && false === in_array( intval( $selected ), $arr_all_ids ) ) {
		$html .= '<option value="' . $selected . '">' .
				esc_html(
					_x(
						'Deleted: Person',
						'Prefix for option to assign a person. A person id follows.',
						'verowa-connect'
					)
				) . ' ' . $selected . '</option>';
	}

	array_multisort(
		$arr_name,
		SORT_ASC,
		$arr_firstname,
		SORT_ASC,
		$arr_all_persons
	);

	if ( is_array( $arr_all_persons ) ) {
		foreach ( $arr_all_persons as $arr_single_person ) {
			$html .= '<option value="' . $arr_single_person['person_id'] . '" ';

			if ( $selected == $arr_single_person['person_id'] ) {
				$html .= ' selected=selected ';
			}

			$html .= '>' . $arr_single_person['lastname'];

			if ( '' !== $arr_single_person['lastname'] && '' !== $arr_single_person['firstname'] ) {
				$html .= ' ';
			}

			$html .= $arr_single_person['firstname'] . '</option>';
		}
	}

	$html .= '</select>';

	return addslashes( $html );
}




/**
 * Returns the HTML for a drop down menu
 *
 * @param array  $arr_group_data An array containing the person group data with children.
 * @param string $selected The value of the selected option. (Optional).
 *
 * @return string The HTML for the drop down menu containing person group options.
 */
function verowa_group_dropdown( $arr_group_data, $selected = '' ) {
	$html = '<select id="personselect" name="personeneinzeln[]">';

	if ( $arr_group_data ) {
		$html .= verowa_get_group_dropdown_option( $arr_group_data, $selected );
	}
	$html .= '</select>';

	return addslashes( $html );
}




/**
 * Returns the HTML for all person group options in a drop down select element.
 *
 * @param array  $arr_group_data $arr_group_data An array containing the person group data with children.
 * @param int    $selected The ID of the selected group (if any).
 * @param string $str_text_indent The text indentation for subgroups. (Optional).
 *
 * @return string The HTML options for the person group drop down select element.
 */
function verowa_get_group_dropdown_option( $arr_group_data, $selected, $str_text_indent = '' ) {
	$html = '';
	foreach ( $arr_group_data as $arr_group_single_data ) {
		$str_disabled = ($arr_group_single_data['disabled'] ?? false) == true ? ' disabled="disabled"' : '';
		$html .= '<option' . $str_disabled . ' value="group-' . $arr_group_single_data['pgroup_id'] . '"';

		if ( $selected == $arr_group_single_data['pgroup_id'] ) {
			$html .= ' selected ';
		}

		$html .= '>' . $str_text_indent . $arr_group_single_data['name'] . '</option>';
		if ( count( $arr_group_single_data['children'] ?? array() ) > 0 ) {
			$html .= verowa_get_group_dropdown_option(
				$arr_group_single_data['children'],
				$selected,
				$str_text_indent . '&nbsp;&nbsp;&nbsp;&nbsp;'
			);
		}
	}

	return $html;
}




/**
 * Add the selected persons to the page content
 *
 * @param string $content html post content.
 *
 * @return string the modified post content.
 */
function add_verowa_persons_if_selected( $content ) {
	global $post, $wp_query, $wpdb;

	$curr_language_code    = verowa_wpml_get_current_language();
	$int_selected_template = intval( get_post_meta( $post->ID, 'verowa_personlist_template', true ) );

	if ( 0 === $int_selected_template )  {
		$int_selected_template = intval( get_option( 'verowa_default_personlist_template' ) );
	}

	$obj_template        = verowa_get_single_template( $int_selected_template, $curr_language_code, 'content' );
	$single_persons      = get_post_meta( $post->ID, '_person_singles', true );
	$int_list_id         = get_post_meta( $post->ID, '_verowa_list_assign', true );
	$int_layer_id        = get_post_meta( $post->ID, '_verowa_layer_assign', true );
	$int_target_group_id = get_post_meta( $post->ID, '_verowa_target_group_assign', true );

	if ( ( is_array( $single_persons ) && count( $single_persons ) > 0 ||
		$int_list_id > 0 ||
		$int_layer_id > 0 ||
		$int_target_group_id > 0 ) && ! $wp_query->is_search() ) {
		$content .= '<div class="verowa-persons-events-container persons-events-container" >';
	}

	if ( ! empty( $obj_template ) ) {
		if ( is_array( $single_persons ) && count( $single_persons ) > 0 && ! $wp_query->is_search() ) {
			$content .= '[verowa_personen comp_tag="th"]';
		}
	}

	return $content;
}
