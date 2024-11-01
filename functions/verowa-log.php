<?php
/**
 * Log event related to the plugin.
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since
 * @package Verowa Connect
 * @subpackage Functions
 */

/**
 * Saves the log entries in the DB and deletes entries that are older than 180 days.
 *
 * @param  string $str_change_type Text that specifies the type of change.
 * @param  string $json_log_content Old content of a Template as JSON or content to Log.
 * 
 * @return object
 */
function verowa_save_log( $str_change_type, $json_log_content ) {
	global $wpdb;
	$obj_ret = new stdClass();

	$obj_curr_user = wp_get_current_user();
	$user_id       = $obj_curr_user->ID ?? 0;
	$str_user_name = $obj_curr_user->user_nicename ?? '';

	$obj_ret->number_of_rows_inserted = $wpdb->insert(
		$wpdb->prefix . 'verowa_log',
		array(
			'modifed_by_user_id' => $user_id,
			'user_display_name'  => $str_user_name,
			'change_type'        => $str_change_type,
			'content'            => $json_log_content,
		),
		array( '%d', '%s', '%s', '%s' )
	); // db call ok.

	if ( false !== $obj_ret->number_of_rows_inserted ) {
		$obj_ret->validity  = true;
		$obj_ret->insert_id = $wpdb->insert_id;
	} else {
		$obj_ret->validity = false;
	}

	// Delete old data in DB.
	$current_date = new DateTime();
	$current_date->modify( '-180 day' );

	$wpdb->query(
		$wpdb->prepare( 'DELETE FROM `%1sverowa_log` WHERE `created_when` < \'%2$s;\'', $wpdb->prefix, $current_date->format( 'Y-m-d H:i:s' ) )
	); // db call ok; no-cache ok.

	return $obj_ret;
}




/**
 * Update log entries
 *
 * @param int    $history_id Id of the entry to update.
 * @param string $json_log_content Old content of a Template as JSON or content to Log.
 * @return int|false Return the value which $wpdb->update returns.
 */
function verowa_update_log( $history_id, $json_log_content ) {
	global $wpdb;

	$ret_update = $wpdb->update(
		$wpdb->prefix . 'verowa_log',
		array(
			'content' => $json_log_content,
		),
		array(
			'history_id' => intval( $history_id ),
		),
		array( '%s' ),
		array( '%d' )
	); // db call ok.

	return $ret_update;
}
