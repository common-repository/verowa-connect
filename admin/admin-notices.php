<?php
/**
 * For adding different admin notices in the backend
 *
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @package Verowa Connect
 * @subpackage Backend
 */

/**
 * If the verowa_instance or verowa_api_key is not set, it generate an admin notices.
 */
function verowa_api_member_error() {

	$ret = verowa_check_member_api_key();

	if ( false === $ret->validity ) {
		$str_class = 'notice notice-error';
		printf(
			'<div class="%1$s"><p>%2$s</p></div>',
			esc_attr( $str_class ),
			wp_kses(
				VEROWA_APIKEY_MEMBER_ERROR,
				array(
					'a' => array(
						'href'   => array(),
						'title'  => array(),
						'target' => array(),
					),
				)
			)
		);
	}
}
add_action( 'admin_notices', 'verowa_api_member_error' );
