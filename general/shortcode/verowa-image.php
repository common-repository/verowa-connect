<?php
/**
 * Shortcode display Person Image with specific comp tag
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @package Verowa Connect
 * @subpackage General
 */

/**
 * Renders the image associated with a Verowa person using the provided shortcode attributes.
 *
 * @param array  $atts     Shortcode attributes.
 * @param string $content  Shortcode content.
 *
 * @return bool|string     Returns the rendered image HTML or false if the image is not found.
 */
function verowa_image_rendering( $atts, $content ) {
	global $wpdb;

	ob_start();

	$atts = shortcode_atts(
		array(
			'id' => 0,
			'comp_tag' => 'pr',
		),
		$atts,
		'verowa_image'
	);

	$content = trim( $content );
	// is $content empty add a default content.
	if ( 0 === strlen( $content ) ) {
		$content = '<img class="verowa_person" src="IMAGE_URL" alt="" />';
	}

	$int_id = intval( $atts['id'] );
	if ( $int_id > 0 ) {
		$query = 'SELECT `content` FROM `' . $wpdb->prefix . 'verowa_person` ' .
			'WHERE `person_id` = ' . $int_id . ';';
		$str_content = $wpdb->get_var( $query ) ?? '{}';
		$arr_content = json_decode($str_content, true);
		if ( key_exists( 'images', $arr_content ) &&
			key_exists( $atts['comp_tag'], $arr_content['images'] ?? []) ) {
			$content = str_replace( 'IMAGE_URL', $arr_content['images'][$atts['comp_tag']]['url'], $content );
		} else {
			$content = '';
		}
	} else {
		$content = '';
	}

	echo $content;
	return ob_get_clean();
}
