<?php
/**
 * Verowa URL Encode Callback Function
 *
 * Beschreibung: Diese Funktion nimmt den bereitgestellten Inhalt und führt die URL-Kodierung (URL encoding) auf ihn aus.
 *
 * Kodierung: UTF-8
 *
 * @author [Dein Name oder Organisation]
 * @package [Name des Pakets, falls zutreffend]
 * @subpackage [Name des Unterpakets, falls zutreffend]
 *
 * @param array  $attr     Ein Array von Attributen, die möglicherweise an die Funktion übergeben werden (in der Regel nicht verwendet).
 * @param string $str_url  Der Inhalt, der URL-kodiert werden soll.
 *
 * @return string          Der URL-kodierte Inhalt.
 */
function verowa_urlencode_callback( $attr = array(), $str_url = null ) {
	$arr_url_parst = explode( '?', $str_url );

	parse_str( $arr_url_parst[1] ?? '', $arr_ret );
	$str_encoded_url = '';
	foreach ( $arr_ret as $k => $v ) {
		$str_encoded_url .= '&' . $k .  "=". urlencode( $v );
	}

	return $arr_url_parst[0] . '?' . substr( $str_encoded_url, 1 );
}