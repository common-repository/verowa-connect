<?php
/**
 * Displays the Verowa event filter, incl. date picker and new as parameter list = (id)
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @package Verowa Connect
 * @subpackage Events
 */


/**
 *
 * @param array $atts
 *
 * @return bool|string
 */
function verowa_event_filter( $atts ) {
	$atts = shortcode_atts(
		array(
			'titel' => __( 'Verowa filter', 'verowa-connect' ),
			'liste' => 0,
		),
		$atts,
		'verowa_event_filter'
	);

	ob_start();

	echo '<div class="verowa-filter-box" >';
	echo '<b>' . esc_html( $atts['titel'] ) . '</b>';

	// Change if dynamic by an option.
	$dropdown_count = 1;

	$how_many_dropdowns = get_option( 'how_many_verowa_dropdowns', false );
	$dropdowns          = $how_many_dropdowns;

	$arr_lists_in_group = json_decode( get_option( 'verowa_agenda_filter', '[]' ), true );

	echo '<form method="GET">';
	$arr_listen_aus_get_parameter = $_GET['list_targets'];

	if ( 0 != $atts['liste'] ) {
		$original_listen_array        = $arr_listen_aus_get_parameter;
		$arr_listen_aus_get_parameter = array(
			$atts['liste'],
		);

		foreach ( $original_listen_array as $org_wert ) {
			array_push( $arr_listen_aus_get_parameter, $org_wert );
		}
	}

	$angezeigte_liste = array();
	foreach ( $arr_lists_in_group as $angezeigte_listen ) {
		$angezeigte_liste[ $angezeigte_listen['list_id'] ] = $angezeigte_listen['name'];
	}
	$str_tf = verowa_tf( 'Show from', __( 'Show from', 'verowa-connect' ) );
	echo '<table style="border:0;"><tr><td colspan="2">' .
		esc_html( $str_tf ) . ':</td></tr>';
	echo '<tr>';

	if ( isset( $_GET['verowa_datum'] ) ) {
		$current_day = $_GET['verowa_datum'];
	} else {
		$current_day = current_time( 'd.m.y' );
	}

	echo '<td><span class="dashicons dashicons-calendar-alt" ></span></td>' .
		'<td><input type="text" style="width:100%;" name="verowa_datum" value ="' . esc_attr( $current_day ) . '"' .
		' id="verowa_connect_datepicker" onchange="this.form.submit()" /></td></tr>';
	echo '<tr>';

	while ( $dropdown_count <= $dropdowns ) {
		echo '<td><label style="margin-right:10px;" for="list_targets[]">' .
			get_option( 'verowa_dropdown_' . $dropdown_count . '_title', true ) . '<label></td>';
		echo '<td><select style="width:100%;" onchange="this.form.submit()" name="list_targets[]">';
		$options = explode( ', ', get_option( 'verowa_dropdown_' . $dropdown_count, true ) );
		echo '<option value=""></option>';

		foreach ( $options as $option ) {
			$selected = ( in_array( $option, $arr_listen_aus_get_parameter ) ) ? ' selected' : '';
			echo '<option value="' . $option . '" ' . $selected . '>' . $angezeigte_liste[ $option ] . '</option>';
		}

		echo '</select></td></tr>';
		$dropdown_count++;
	}

	echo '</table></form></div>';

	return ob_get_clean();
}
