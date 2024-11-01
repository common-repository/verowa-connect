<?php
/**
 * Shortcode to display the feedback to the user
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @package Verowa Connect
 * @subpackage General
 */

add_action( 'init', 'verowa_deprecated_shortcode_aliases' );

/**
 * Add deprecated Shortcodes
 *
 * @return void
 */
function verowa_deprecated_shortcode_aliases() {
	add_shortcode( 'verowa_subscription_overview', 'verowa_subscription_overview' );
}


/**
 * Alias for verowa_event_list.
 *
 * @param array $atts Shortcode attributes.
 */
function verowa_subscription_overview( $atts ) {

	$atts = shortcode_atts(
		array(
			'id'          => 0,
			'list'        => 0,
			'max_count'   => 0,
			'max_days'    => 0,
			'template_id' => 0,
			'handle_full' => 'disable', // How to deal with the fully booked events? "link|disable|hide".
		),
		$atts,
		'verowa_subscription_overview'
	);

	$str_handle_full = 'disable' === $atts['handle_full'] ? 'none' : $atts['handle_full'];
	$list_id         = $atts['list'] > 0 ? $atts['list'] : $atts['id'];

	$str_shortcode = '[verowa_event_list title=" " list_id=' . $list_id . ' handle_full="' . $str_handle_full . '" ';
	if ( intval( $atts['template_id'] ) > 0 ) {
		$str_shortcode .= 'template_id=' . intval( $atts['template_id'] ) . ' ';
	}

	if ( intval( $atts['max_count'] ) > 0 ) {
		$str_shortcode .= 'max_events=' . intval( $atts['max_count'] ) . ' ';
	}

	if ( intval( $atts['max_days'] ) > 0 ) {
		$str_shortcode .= 'max_days=' . intval( $atts['max_days'] ) . ' ';
	}

	$str_shortcode .= ']';

	return do_shortcode( $str_shortcode );
}

/**
 * Alias for verowa_event_list
 *
 * @param array $atts Shortcode attributes.
 *
 * @return bool|string
 */
function verowa_event_liste( $atts ) {
	// Where no values are set, defaults are used.
	// If the number is set and "pro-seite" is also set to true,
	// there are multiple pages with the number set.

	$default_pro_page = 10;
	$default_max       = $default_pro_page;

	$atts = shortcode_atts(
		array(
			'id'           => 0,
			'layer_id'     => '',
			'target_group' => '',
			'pro-seite'    => $default_pro_page,
			'title'        => '',
			'max'          => $default_max,
			'max_days'     => 365,
			'template_id'  => 0,
		),
		$atts,
		'verowa_event_liste'
	);

	ob_start();

	// Pass on all attributes.
	echo do_shortcode(
		'[verowa_event_list id=' . $atts['id'] . ' layer_id="' . $atts['layer_id'] . '" target_group="' .
		$atts['target_group'] . '" pro-seite=' . $atts['pro-seite'] . ' title="' . $atts['title'] . '" max=' .
		$atts['max'] . ' max_days=' . $atts['max_days'] . ' template_id=' . $atts['template_id'] . ']'
	);

	return ob_get_clean();
}
