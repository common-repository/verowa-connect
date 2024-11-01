<?php
/**
 * Replacement for "PHP session" to store user session data in the DB
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since 2.9.0
 * @package Verowa Connect
 * @subpackage Functions
 */

/**
 * Save user data in the DB and returns the key
 *
 * @param array  $arr_user_data Data array to store in the Database.
 * @param string $str_key Key which is stored in verowa-connect-session.
 *
 * @return string
 */
function verowa_save_user_data( $arr_user_data, $str_key = '' ) {
	global $wpdb;

	if ( '' === $str_key ) {
		// Create a unique key.
		do {
			$str_key       = verowa_create_id( 20 );
			$int_found_key = intval(
				$wpdb->get_var(
					$wpdb->prepare(
						'SELECT COUNT(`session_key`) FROM `' . $wpdb->prefix . 'verowa_temp_userdata`' .
						' WHERE `session_key` = %s',
						$str_key
					)
				)
			);
		} while ( $int_found_key > 0 );
	}

	$ret_insert = $wpdb->insert(
		$wpdb->prefix . 'verowa_temp_userdata',
		array(
			'session_key' => $str_key,
			'user_data'   => wp_json_encode( $arr_user_data ),
		),
		array( '%s', '%s' )
	);

	// Delete old data in DB.
	$current_date = new DateTime();
	$current_date->modify( '-7 day' );

	$wpdb->query(
		'DELETE FROM `' . $wpdb->prefix . 'verowa_temp_userdata` WHERE `created_when` < "' .
		$current_date->format( 'Y-m-d H:i:s' ) . '";'
	);

	return $str_key;
}




/**
 * Get user data from the DB
 *
 * @param string $str_key Key which is stored in verowa-connect-session.
 *
 * @return array
 */
function verowa_get_user_data( $str_key ) {
	global $wpdb;
	$wpdb->escape_by_ref( $str_key );

	$str_json = $wpdb->get_var(
		$wpdb->prepare(
			'SELECT `user_data` FROM `' . $wpdb->prefix . 'verowa_temp_userdata`' .
			' WHERE `session_key` = %s',
			$str_key
		)
	);
	$arr_ret  = null !== $str_json ? json_decode( $str_json, true ) : array();

	return $arr_ret;
}



/**
 * Returns the number of updated lines or false in case of error
 *
 * @param string $str_key Key which is stored in verowa-connect-session.
 * @param array $arr_user_data Data array for update in the Database.
 * @return bool|int|string
 */
function verowa_update_user_data( $str_key, $arr_user_data ) {
	global $wpdb;
	// Check whether update is possible, if not then an insert is made.
	$query = 'SELECT COUNT(*) as `count` FROM `' . $wpdb->prefix . 'verowa_temp_userdata` WHERE session_key = "' .
		$str_key . '";';
	$int_count = $wpdb->get_var( $query );
	if ( null != $int_count ) {
		$int_count = intval( $int_count );
	} else {
		$int_count = 0;
	}

	if( $int_count > 0 ) {
		$current_date = new DateTime();
		return $wpdb->update(
			$wpdb->prefix . 'verowa_temp_userdata',
			array(
				'user_data'    => wp_json_encode( $arr_user_data ),
				'created_when' => $current_date->format( 'Y-m-d H:i:s' ),
			),
			array( 'session_key' => $str_key )
		);
	} else {
		return verowa_save_user_data( $arr_user_data, $str_key );
	}

}



/**
 * Returns the number of deleted lines or false in case of error
 *
 * @param string $str_key Key which is stored in verowa-connect-session.
 *
 * @return bool|int
 */
function verowa_delete_user_data( $str_key ) {
	global $wpdb;
	$result = false;

	if ( strlen( $str_key ?? '' ) > 2 ) {
		$result = $wpdb->delete(
			$wpdb->prefix . 'verowa_temp_userdata',
			array(
				'session_key' => $str_key,
			)
		);
	}

	return $result;
}



/**
 * Return the session key sotred in the Cookie verowa-connect-session
 *
 * @return string
 */
function verowa_get_key_from_cookie() {
	$str_key = sanitize_text_field( wp_unslash( $_COOKIE['verowa-connect-session'] ?? '' ) );
	return $str_key;
}




/**
 * Save the session key in the cookie verowa-connect-session
 *
 * @param string $str_key Key which is stored in verowa-connect-session.
 *
 * @return bool
 */
function verowa_set_connect_cookie( $str_key ) {
	$str_name = 'verowa-connect-session';

	// CWe/MPf: The cookie expires at the end of the session to avoid filter errors.
	$expire_time = 0;
	$path = '/';
	$str_server_name = sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ?? '' ) );
	$is_set_cookie = false;
	if (!headers_sent()) {
		$is_set_cookie = setcookie( $str_name, $str_key, $expire_time, $path, $str_server_name, true );
	}
	return $is_set_cookie;
}

add_action( 'verowa_delete_user_data', 'verowa_delete_user_data_callback' );
/**
 * If a key is set, the temporary user data in the DB is deleted.
 */
function verowa_delete_user_data_callback() {
	global $post;
	$str_key = isset( $_GET['key'] ) ? strval( $_GET['key'] ) : '';

	// delete user data from db.
	if ( '' !== $str_key ) {
		verowa_delete_user_data( $str_key );
	}
}
