<?php
/**
 * Agenda shortcode to display an agenda of Verowa events
 *
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @package Verowa Connect
 * @subpackage Events
 */

/**
 * Shortcode to display an Agenda with events from Verowa.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return bool|string
 */
function verowa_agenda( $atts ) {
	global $wp_query;

	$atts = shortcode_atts(
		array(
			'list_id' => 0,
		),
		$atts,
		'verowa_agenda'
	);

	$str_ret                  = '';
	$arr_list_ids             = array();
	$arr_verowa_agenda_filter = array();
	$arr_user_data            = array();
	$str_search_string        = '';
	$str_session_key          = verowa_get_key_from_cookie();
	$int_return_event_id      = intval( $_GET['id'] ?? 0 );
	$curr_language_code       = verowa_wpml_get_current_language();

	if ( '' !== $str_session_key ) {
		// User comes back from the agenda.
		$arr_user_data            = verowa_get_user_data( $str_session_key );
		$arr_verowa_agenda_filter = $arr_user_data['verowa_agenda_filter'] ?? array();
	}

	$str_search_string = $_GET['qv'] ?? $arr_verowa_agenda_filter['search_string'] ?? '';

	// 0 to reset the cat filter
	// -1 to retrieve the filter value from the session.
	$int_list_id = intval( $_GET['vcat'] ?? $_GET['cat'] ?? -1 );
	if ( -1 === $int_list_id ) {
		$arr_list_ids = $arr_verowa_agenda_filter['arr_list_ids'] ?? array();
	} else {
		$arr_list_ids = array( $int_list_id );
	}

	// date is deprecated new vdate should be used.
	$str_date8            = $_GET['vdate'] ?? $_GET['date'] ?? $arr_verowa_agenda_filter['date8_displays_from'] ?? '';
	$date10_displays_from = 8 === strlen( $str_date8 ) ? verowa_date8_to_date10ch( $str_date8 ) : current_time( 'd.m.Y' );

	// We get it all from our cache because this process with the filter takes too long.
	$str_verowa_agenda_filter = get_option( 'verowa_agenda_filter', '' );
	$arr_verowa_groups        = strlen( $str_verowa_agenda_filter ) > 0 ? json_decode( $str_verowa_agenda_filter, true ) : array();
	$str_ret                 .= '<br /><input type="hidden" id="verowa-atts-list-id" value="' . $atts['list_id'] . '"/>';
	$str_ret                 .= '<input type="hidden" id="wpml-language-code" value="' . $curr_language_code . '"/>';
	$str_ret                 .= '<section id="verowa_event_filters" class="verowa-filter">';
	if ( get_option( 'verowa_show_full_text_search', true ) ) {
		$str_search_pcl = verowa_tf( 
			'Enter a search term',
			_x( 'Enter a search term', 'Placeholder in search textbox.', 'verowa-connect' ),
			$curr_language_code 
		);

		$str_ret .= '<div id="vc-agenda-search-wrapper" class="row">' .
			'<input type="search" id="vc-agenda-search-input" value="' . trim( $str_search_string ) . '" class="verowa-is-search-input is-search-input" ' .
			'placeholder="' . esc_attr( $str_search_pcl ) . '">' .
			'<button type="submit" class="is-search-submit"><span class="verowa-is-search-input is-search-icon">' .
			'<svg focusable="false" aria-label="Search" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24px">' .
			// path should be one one code line.
			'<path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"></path></svg>' .
			'</span></button></div>';
	}
	$dropdown_count = 1;
	$nb_dropdowns   = get_option( 'how_many_verowa_dropdowns', false );
	if ( true === verowa_wpml_is_configured() )
	{
		$str_ml_dropdowns_titel = get_option ('verowa_agenda_ml_dropdowns_titel', '');
		$arr_ml_dropdowns_titel = $str_ml_dropdowns_titel != '' ? json_decode( $str_ml_dropdowns_titel, true ) : array();
		$curr_language_code = verowa_wpml_get_current_language();
	}
	while ( $dropdown_count <= $nb_dropdowns ) {
		$str_html             = '<div class="row">';
		if ( true === verowa_wpml_is_configured() ) {
			$filter_name = $arr_ml_dropdowns_titel[ $dropdown_count ][ $curr_language_code ] ?? '-';
		} else {
			$filter_name = get_option( 'verowa_dropdown_' . $dropdown_count . '_title', true );
		}
		$str_html            .= '<div class="column"><label>' . $filter_name . '</label></div>' .
			'<ul class="column list_filter option-set" data-filter-group="' . $filter_name . '">' .
			'<li>[[ALL_BUTTON]]</li>';
		$filter_options       = explode( ', ', get_option( 'verowa_dropdown_' . $dropdown_count, true ) );
		$has_selected_buttons = false;
		foreach ( $filter_options as $group_id ) {
			$group_key    = array_search( $group_id, array_column( $arr_verowa_groups, 'list_id' ) );
			$str_selected = '';
			if ( in_array( $arr_verowa_groups[ $group_key ]['list_id'] ?? array(), $arr_list_ids ) ) {
				$str_selected        .= ' selected';
				$has_selected_buttons = true;
			}

			$str_html .= '<li><a class="filter-button' . $str_selected . '" href="#filter-' . $filter_name .
				'-' . preg_replace( '/[^a-zA-Z0-9]+/', '', $arr_verowa_groups[ $group_key ]['name'] ?? '' ) . '"' .
				' data-filter-value=".events-' . ( $arr_verowa_groups[ $group_key ]['list_id'] ?? 0 ) . '">' .
				( $arr_verowa_groups[ $group_key ]['name'] ?? '' ) . '</a></li>';
		}

		// If a filter was selected, the all button should not be selected.
		$str_selected_all = 'selected ';
		if ( true === $has_selected_buttons ) {
			$str_selected_all     = '';
			$has_selected_buttons = false;
		}

		$str_all_test = verowa_tf( 'All', _x( 'All', 'Button label on agenda', 'verowa-connect' ) );
		$str_all_button = '<a class="filter-button ' . $str_selected_all . 'no-filter" data-filter-value="">' .
			$str_all_test . '</a>';
		$str_html       = str_replace( '[[ALL_BUTTON]]', $str_all_button, $str_html );

		$str_html .= '</ul></div>';
		$str_ret  .= $str_html;
		$dropdown_count++;
	}

	// Date picker from the past.
	$str_tf = verowa_tf( 'Show from', __( 'Show from', 'verowa-connect' ), $curr_language_code );
	$str_tf_filter = verowa_tf( 
		'Reset filter', 
		_x( 'Reset filter', 'Link text to reset the agenda filter', 'verowa-connect' ), 
		$curr_language_code 
	);

	$str_ret .= '<div class="row date-row">' .
		'<div class="date_filter column">' .
		'<label for="verowa_datum">' . $str_tf . '</label>' .
		'</div>' .
		'<div class="date_filter_picker column">' .
		'<input type="text" name="verowa_datum" value="' . $date10_displays_from . '"' .
		' id="verowa_connect_datepicker" />' .
		'</div>' .
		'<div class="verowa_agenda_filter_reset_wrapper" >' .
		'<a href="javascript:verowa_agenda_filter_reset();" id="verowa-agenda-filter-reset" >' .
		$str_tf_filter . '</a>' .
		'</div></div>' .
		'</section>';

	// Special case for preselected categories.
	$str_ret .= '<input type="hidden" id="dynamic-agenda-cat" value="' . ( $_GET['cat'] ?? 0 ) . '"/>';
	$str_ret .= '<div class="event_list_wrapper grid event_list_dynamic_agenda"></div>';

	// Prepare message if there are no hits.
	$str_ret .= '<div class="event_list_item" style="display:none;"	 id="no-events-box">' .
		'<i>' .
		__(
			'Unfortunately no events were found for your criteria. Please change your settings.',
			'verowa-connect'
		) . '</i>' .
		'</div>';

	$str_ret .= '<div class="event_list_item" style="display:none;text-align: center;" id="infinite-scroll-loader">' .
	'<img src="' . site_url() . '/wp-content/plugins/verowa-connect/images/ajax-loader.gif" />' .
	'</div>';

	$str_ret .= '<script>
		var g_current_agenda_offset = 0;
		var g_supports_session_storage = null;

		jQuery(document).ready(function() {
			g_supports_session_storage = supports_session_storage();
			bind_infinite_scroll();
			load_agenda_events(false);
			g_agenda_is_loading = true;
			scroll_to_event(' . $int_return_event_id . ');
		});

		</script>';

	return $str_ret;
}
