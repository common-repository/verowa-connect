<?php
/**
 * This shortcode displays specially specified Verowa lists
 * NEW: Attributes number=number and page-split=true (or false) added.
 * NEW 2018, the shortcode can be used with the parameter layer_id= (comma separated). can be used
 *
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since 2.9.0
 * @package Verowa Connect
 * @subpackage Events
 */

/**
 * "verowa_event_list" is the correct shortcode to use.
 * The other two are redirected to it and should be removed in the future.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return bool|string
 */
function verowa_event_list( $atts ) {
	global $post;

	// Where no values are set, defaults are used.

	// If the number is set and "Split pages" is also set to True,
	// there are several pages with the set number.

	// The default values.
	$max_max                = 1000; // Maximum number of events allowed to be displayed.

	$atts = shortcode_atts(
		array(
			'id'           => '',
			'list_id'      => '',
			'filter'       => '',
			'layer_id'     => '',
			'target_group' => '',
			'group_id'     => '',
			'title'        => '', // With spaces the title is not displayed.
			'max'          => 10,
			'max_events'   => 10,
			'max_days'     => 365,
			'template_id'  => 0,
			'handle_full'  => '',
		),
		$atts,
		'verowa_event_list'
	);

	$hierarchical_layer_ids = json_decode( get_option( 'verowa_hierarchical_layers_tree', '' ), true );

	// add sub layer ids.
	$list_id          = '' !== $atts['list_id'] ? $atts['list_id'] : $atts['id'];
	$filter_list_id   = $atts['filter'] ?? '';
	$group_id         = '' !== $atts['group_id'] ? $atts['group_id'] : $atts['target_group'];
	$atts['layer_id'] = verowa_get_sub_layer_ids( $atts['layer_id'], $hierarchical_layer_ids );

	// if no template id is given we get it from the post meta for this page.
	if ( 0 === intval( $atts['template_id'] ) ) {
		$atts['template_id'] = intval( get_post_meta( $post->ID, 'verowa_eventlist_template', true ) );

		// if there is no template id in the post meta either we get the default from the options.
		if ( 0 === $atts['template_id'] ) {
			$atts['template_id'] = intval( get_option( 'verowa_default_eventlist_template' ) );
		}
	}

	$curr_language_code = verowa_wpml_get_current_language();
	if ( $atts['template_id'] > 0 ) {
		$obj_template = verowa_get_single_template( $atts['template_id'], $curr_language_code );
	}

	// Set title. If it is empty, we take "Upcoming events".
	$str_tf = verowa_tf( 'Upcoming events', __( 'Upcoming events', 'verowa-connect' ), $curr_language_code );
	$str_title = strlen( $atts['title'] ) > 0 ? $atts['title'] : $str_tf;

	$int_max = ( 10 !== intval( $atts['max_events'] ) ) ? intval( $atts['max_events'] ) : intval( $atts['max'] );

	ob_start();

	// Error message only if nothing is defined (whether the specification is valid at all,
	// is tested by the Verowa-Fn; we pass the value unchanged).
	if ( empty( $list_id ) && empty( $atts['layer_id'] ) && empty( $group_id ) ) {
		echo '<mark>' . esc_html( __( 'You need to enter a list ID or a layer ID for this list.', 'verowa-connect' ) ) .
			'</mark>';
	} elseif ( $int_max > $max_max ) {
		echo '<mark>' .
			esc_html(
				// translators: %s: Maximal count of event to show.
				sprintf(
					__(
						'You can show %s events max.',
						'verowa-connect'
					),
					$max_max
				)
			) .
			'</mark>';
	} else {

		$template_id = intval( $atts['template_id'] );
		// If no template id is given we get it from the post meta for this page.
		if ( 0 === $template_id ) {
			$template_id = 0;
			if ( null != $post ) {
				$template_id = intval( get_post_meta( $post->ID, 'verowa_eventlist_template', true ) );
			}

			// If there is no template id in the post meta either we get the default from the options.
			if ( 0 === $template_id ) {
				$template_id = intval( get_option( 'verowa_default_eventlist_template' ) );
			}
			$obj_template = verowa_get_single_template( $template_id, $curr_language_code );
		}

		$str_date_from_format = true == $obj_template->display_entire_day ? 'Y-m-d 00:00:00' : 'Y-m-d H:i:s';

		$arr_events = verowa_events_db_get_multiple(
			'',
			$list_id,
			$filter_list_id,
			$group_id,
			$atts['layer_id'],
			$int_max,
			0,
			'now',
			$str_date_from_format
		);

		$number_of_all_entries = count( $arr_events );

		if ( $number_of_all_entries > 0 ) {

			if ( ! empty( $obj_template ) ) {
				echo $obj_template->header;
			}

			// if the title contains only spaces, nothing is output.
			if ( strlen( trim( $str_title ) ) > 0 ) {
				echo '<h3>' . $str_title . '</h3>';
			}
		}

		foreach ( $arr_events as $arr_single_event ) {
			$show_event          = true;
			$str_subscribe_state = $arr_single_event['subs_state'] ?? 'none';

			$today = strtotime('today');

			if ( intval($atts['max_days']) !== 0 && key_exists('datetime_from', $arr_single_event ) &&
				strtotime( $arr_single_event['datetime_from'] ) > strtotime( '+' . $atts['max_days'].' days', $today ) ) {
				$show_event = false;
			}

			switch ( $atts['handle_full'] ) {
				case 'none':
					break;

				case 'link':
					break;

				case 'hide':
					if ( 'booked_up' !== $str_subscribe_state ) {
						$show_event = true;
					}
					break;
			}


			if ( '' !== $atts['handle_full'] &&
				'deadline_expired' === $str_subscribe_state ) {
				$show_event = false;
			}

			if ( $show_event ) {
				echo do_shortcode(
					verowa_event_show_single(
						$arr_single_event,
						array(
							$curr_language_code => $obj_template,
						),
						$curr_language_code,
						$atts['handle_full']
					)
				);
			}
		}

		if ( $number_of_all_entries > 0 ) {

			if ( ! empty( $obj_template ) ) {
				echo $obj_template->footer;
			}
		}

		if ( 0 === $number_of_all_entries && '' !== $atts['handle_full'] ) {
			$ret_option  = get_option( 'verowa_subscriptions_settings' );
			$arr_options = $ret_option != false ? json_decode( $ret_option, true ) : array();
			if ( 'on' === ( $arr_options['only_today'] ?? 'off' ) ) {
				$str_tf = verowa_tf( 
					'There are no public events with registration taking place today.', 
					__(
						'There are no public events with registration taking place today.',
						'verowa-connect'
					), 
					$curr_language_code
				);
				echo '<strong>' . esc_html( $str_tf ) . '</strong><br/><br/>';
			} else {
				$str_tf = verowa_tf( 
					'There will be no public events with registration in the next few days.', 
					__(
						'There will be no public events with registration in the next few days.',
						'verowa-connect'
					), 
					$curr_language_code
				);
				echo '<strong>' . esc_html( $str_tf ) . '</strong><br/><br/>';
			}
		}
	}

	$str_content = ob_get_clean();
	return $str_content;
}
