<?php
/**
 * Verowa Encode Link Callback Function
 *
 * Beschreibung: Diese Funktion generiert einen URL-kodierten Link mit den bereitgestellten Attributen und Linktext.
 * Kodierung: UTF-8
 *
 * @author © Picture-Planet GmbH
 * @package Verowa Connect
 * @subpackage General
 *
 * @param array $atts Ein Array von Attributen, die an die Funktion übergeben werden, um den Link zu konfigurieren.
 *
 * @return string Der generierte URL-kodierte Link oder ein leerer String, wenn die erforderlichen Attribute fehlen.
 */
function verowa_encode_link_callback( $atts = array() ) {

	$atts = shortcode_atts(
		array(
			'url' => '',
			'link_text' => '',
			'title' => '',
			'target' => '',
			'css_class' => '',
		),
		$atts,
		'verowa_encode_link'
	);

	$str_link = '';

	if ( strlen( $atts['url'] ) > 0 & strlen( $atts["link_text"] ) > 0 ) {
		$arr_url_parst = explode( '?', $atts['url'] );

		parse_str( $arr_url_parst[1] ?? '', $arr_ret );
		$str_encoded_url = '';
		foreach ( $arr_ret as $k => $v ) {
			$str_encoded_url .= '&' . $k . "=" . urlencode( $v );
		}
		$str_encoded_url = $arr_url_parst[0] . '?' . substr( $str_encoded_url, 1 );

		$url_atts = array();
		$url_atts[] = 'href="' . $str_encoded_url . '"';

		$attr_mappping = array(
			'target' => 'target',
			'class' => 'css_class',
			'title' => 'title',
		);

		foreach ( $attr_mappping as $str_url_attr_name => $str_atts_name ) {
			if ( strlen( $atts[ $str_atts_name ] ) > 0 ) {
				$url_atts[] = $str_url_attr_name . '="' . $atts[ $str_atts_name ] . '"';
			}
		}

		$str_link .= '<a ' . implode( ' ', $url_atts );
		$str_link .= ' >' . $atts["link_text"] . '</a>';
	}

	return $str_link;
}