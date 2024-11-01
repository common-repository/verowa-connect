<?php
/**
 * Function collection for the verowa roster duties
 *
 * Project:         VEROWA CONNECT
 * File:            functions/roster.php.php
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since 2.10.0
 * @package Verowa Connect
 * @subpackage Functions
 */

/**
 * Return current roster duty.
 * Past duties are not returned.
 *
 * @param int $roster_id Id of a roster duty record.
 * @param int $int_max_days Max days form current date are displayed.
 * @param int $int_max Maximum number of roster tasks are return.
 * @param int $int_offset Offset, to implement e.g. an pagination.
 *
 * @return array|null|object
 */
function verowa_roster_duty_db_find( $roster_id, $int_max_days = 0, $int_max = 0, $int_offset = 0 ) {
	global $wpdb;

	$str_query = 'SELECT * FROM `' . $wpdb->prefix . 'verowa_roster_duties`';
	$arr_where = array();

	if ( $roster_id ) {
		$arr_where[] = '`roster_id` = ' . $roster_id;
	}

	if ( $int_max_days > 0 ) {
		$obj_max_days = new DateTime( current_time( 'Y-m-d 23:59:59' ) );
		$obj_max_days->modify( '+' . $int_max_days . ' days' );
		$arr_where[] = 'datetime_from < "' . $obj_max_days->format( 'Y-m-d H:i:s' ) . '"';
	}

	$arr_where[] = '`datetime_to` > "' . current_time( 'Y-m-d 00:00:00' ) . '"';

	$str_query .= ' WHERE ' . implode( ' AND ', $arr_where ) . ' ';
	$str_limit  = $int_max > 0 ? ' LIMIT ' . $int_max : '';
	$str_limit .= $int_offset > 0 ? ' OFFSET ' . $int_offset : '';

	$arr_roster_duty = $wpdb->get_results( $str_query . $str_limit, ARRAY_A );
	return $arr_roster_duty;
}




/**
 * Insert a duty into the DB
 *
 * @param bool  $roster_id Id of a roster duty record.
 * @param array $arr_roster_duty Roster duty data.
 */
function verowa_roster_duty_db_insert( $roster_id, $arr_roster_duty ) {
	global $wpdb;
	$obj_ret = new stdClass();

	try {
		if ( isset( $arr_roster_duty['content'] ) ) {
			$str_content = wp_json_encode( $arr_roster_duty['content'] ?? array(), JSON_UNESCAPED_UNICODE );

			// Hash everything. With the hash it is easy to check whether something has been changed.
			$str_hash = verowa_roster_duty_generate_hash( $roster_id, $arr_roster_duty );

			$obj_ret->content  = $wpdb->insert(
				$wpdb->prefix . 'verowa_roster_duties',
				array(
					'roster_id'     => intval( $roster_id ),
					'datetime_from' => $arr_roster_duty['datetime_from'],
					'datetime_to'   => $arr_roster_duty['datetime_to'],
					'content'       => $str_content,
					'hash'          => $str_hash,
					'deprecated'    => 0,
				),
				array( '%d', '%s', '%s', '%s', '%s', '%d' )
			);
			$obj_ret->validity = true;

		}
	} catch ( Exception $exception ) {
		$obj_debug = new Verowa_Connect_Debugger();
		$obj_debug->write_to_file( 'Fehler insert roster duties: ' . $exception->getMessage() );
		$obj_ret->content  = $exception->getMessage();
		$obj_ret->validity = false;
	}

	return $obj_ret;
}




/**
 * Update a roster_duty in the DB
 *
 * @param int    $roster_id Id of a roster duty record.
 * @param array  $arr_roster_duty Data array.
 * @param string $str_hash MD5 hash.
 *
 * @return stdClass
 */
function verowa_roster_duty_db_update( $roster_id, $arr_roster_duty, $str_hash ) {
	global $wpdb;
	$obj_ret = new stdClass();

	try {

		$ret_update = $wpdb->update(
			$wpdb->prefix . 'verowa_roster_duties',
			array(
				'content'    => wp_json_encode( $arr_roster_duty['content'] ?? array(), JSON_UNESCAPED_UNICODE ),
				'hash'       => $str_hash,
				'deprecated' => 0,
			),
			array(
				'roster_id'     => $roster_id,
				'datetime_from' => $arr_roster_duty['datetime_from'],
				'datetime_to'   => $arr_roster_duty['datetime_to'],
			),
			array( '%s', '%s', '%d' ),
			array( '%d', '%s', '%s' )
		);

		if ( false === $ret_update ) {
			// Jump into the catch block.
			throw new Exception( $wpdb->last_error . '\n' . $wpdb->last_query );
		}
		$obj_ret->validity = true;
		$obj_ret->content  = $ret_update;
	} catch ( Exception $exception ) {
		$obj_debug = new Verowa_Connect_Debugger();
		$obj_debug->write_to_file( 'Fehler Update roster duties: ' . $exception->getMessage() );
		$obj_ret->content  = $exception->getMessage();
		$obj_ret->validity = false;
	}

	return $obj_ret;
}




/**
 * Delete a verowa event and its related WP_Post
 *
 * @param int    $roster_id Id of a roster duty record.
 * @param string $datetime_from Start date time  from roster.
 * @param string $datetime_to End date time from roster.
 */
function verowa_roster_duty_db_remove( $roster_id, $datetime_from, $datetime_to ) {
	global $wpdb;
	$obj_ret = new stdClass();

	try {

		$obj_ret->content  = $wpdb->delete(
			$wpdb->prefix . 'verowa_roster_duties',
			array(
				'roster_id'     => $roster_id,
				'datetime_from' => $datetime_from,
				'datetime_to'   => $datetime_to,
			),
			array( '%d', '%s', '%s' ),
		);
		$obj_ret->validity = true;
	} catch ( Exception $exception ) {
		$obj_debug = new Verowa_Connect_Debugger();
		$obj_debug->write_to_file( 'Delete roster duties: ' . $exception->getMessage() );
		$obj_ret->content  = $exception->getMessage();
		$obj_ret->validity = false;
	}

	return $obj_ret;
}




/**
 * Generate an MD5 hash from the roster data.
 *
 * @param int   $roster_id       The ID of the roster.
 * @param array $arr_roster_duty An associative array containing roster duty information.
 *                              The array must have a 'content' key containing an array of data.
 *                              The data in the 'content' array will be encoded to JSON.
 * @return string                The MD5 hash generated from the roster data.
 */
function verowa_roster_duty_generate_hash( $roster_id, $arr_roster_duty ) {
	$str_content = wp_json_encode( $arr_roster_duty['content'][0] ?? array(), JSON_UNESCAPED_UNICODE );
	return md5(
		strval( $roster_id ) . ';df=' . $arr_roster_duty['datetime_from'] .
		';dt=' . $arr_roster_duty['datetime_to'] . ';cn=' . $str_content
	);
}




/**
 * Adds 2 default templates for the roster.
 *
 * @return void
 */
function verowa_roster_add_duty_templates() {
	global $wpdb;

	$query     = 'SELECT count(`template_id`) as `count` FROM `' . $wpdb->prefix . 'verowa_templates` ' .
		'WHERE `type` = "roster";';
	$int_count = $wpdb->get_var( $query ) ?? 0;

	if ( 0 === $int_count ) {
		$wpdb->insert(
			$wpdb->prefix . 'verowa_templates',
			array(
				'template_name' => 'Dienstplan Liste',
				'info_text'     => '',
				'type'          => 'roster',
				'display_where' => 'content',
				'header'        => '',
				'entry'         => '<div class="roster-entry">' . PHP_EOL .
					'	<span class="roster-date">{DATE_FROM_SHORT}</span>' . PHP_EOL .
					'	<span class="roster-person">{TEXT}</span>' . PHP_EOL .
					'	<span class="add-roster-data">' . PHP_EOL .
					'		{EMAIL}' . PHP_EOL .
					'		[[?PHONE:<span class="phone">{PHONE}</span>]]' . PHP_EOL .
					'</span>' . PHP_EOL .
					'</div>',
				'separator'     => '',
				'footer'        => '',
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		add_option( 'verowa_default_rosterlist_template', $wpdb->insert_id );

		$wpdb->insert(
			$wpdb->prefix . 'verowa_templates',
			array(
				'template_name' => 'Dienstplan erster Eintrag',
				'info_text'     => '',
				'type'          => 'roster',
				'display_where' => 'content',
				'header'        => '',
				'entry'         => '<h3 class="verowa-roster-headline">Hausdienst</h3>' . PHP_EOL .
					'<div class="verowa-single-roster-entry">' . PHP_EOL .
					'	[[?IMAGE_URL:<div>' . PHP_EOL .
					'		<img width="145" height="145" src="{IMAGE_URL}" title="{TEXT}" />' . PHP_EOL .
					'	</div>]]' . PHP_EOL .
					'	<div class="single-roster-entry">' . PHP_EOL .
					'		<span class="roster-person">{TEXT}</span>' . PHP_EOL .
					'		<span class="add-roster-data">' . PHP_EOL .
					'			<span class="phone">{PHONE}</span>' . PHP_EOL .
					'			{EMAIL}' . PHP_EOL .
					'		</span>' . PHP_EOL .
					'	</div>' . PHP_EOL .
					'</div>' . PHP_EOL,
				'separator'     => '',
				'footer'        => '',
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		add_option( 'verowa_default_firstroster_template', $wpdb->insert_id );
	}
}
