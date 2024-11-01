<?php
/**
 * Function collection for the verowa persons
 *
 * Project:         VEROWA CONNECT
 * File:            functions\person.php
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since Version 2.2.0
 * @package Verowa Connect
 * @subpackage Functions
 */

/**
 * Inserts a person into the database.
 *
 * @param array  $arr_person       Array containing person data.
 * @param string $str_web_visibility   Web visibility of the person.
 * @param string $str_hash         Hash value associated with the person.
 *
 * @return void
 */
function verowa_person_db_insert( $arr_person, $str_web_visibility, $str_hash ) {
	global $wpdb;
	$bool_persons_without_detail_page = get_option( 'verowa_persons_without_detail_page', false ) === 'on' ?
		true : false;
	
	// $wpdb->query( 'START TRANSACTION' );
	$str_tablename = $wpdb->prefix . 'verowa_person';

	try {
		$int_post_id = 0;
		$str_title   = verowa_person_get_title( $arr_person );

		$str_exception = 'Insert not possible ' . $str_title . '(' . $arr_person['person_id'] . ')';

		$arr_person_ml_html        = show_a_person_from_verowa_detail( $arr_person );
		$str_default_language_code = verowa_wpml_get_default_language_code();

		if ( false === $bool_persons_without_detail_page ) {
			$trid = null;

			foreach ( $arr_person_ml_html as $str_language_code => $arr_person_html ) {
				if ( true === verowa_wpml_is_configured() ) {
					$str_post_name = $arr_person['person_id'] . '-' . $str_language_code;
				} else {
					$str_post_name = $arr_person['person_id'];
				}

				$post = array(
					'post_title'   => wp_strip_all_tags( $str_title ),
					'post_name'    => $str_post_name,
					'post_content' => $arr_person_html['html'],
					'post_excerpt' => $arr_person['short_desc'],
					'post_type'    => 'verowa_person',
					'post_status'  => 'publish',
				);

				// Insert the post into the database.
				$int_post_id = verowa_general_insert_custom_post(
					$post,
					'verowa_person',
					'person_id',
					'',
					$arr_person_html['html']
				);

				if ( ! is_wp_error( $int_post_id ) ) {

					$str_source_language_code = $str_language_code !== $str_default_language_code ?
						$str_default_language_code : null;

					if (null == $trid) {
						$get_language_args = array( 'element_id' => $int_post_id, 'element_type' => 'post_verowa_person' );
						$original_post_language_info = apply_filters( 'wpml_element_language_details', null, $get_language_args );
						$trid = $original_post_language_info->trid;
					} else {
						$set_language_args = array(
							'element_id' => $int_post_id,
							'element_type' => 'post_verowa_person',
							'trid' => $trid,
							'language_code' => $str_language_code,
							'source_language_code' => $str_source_language_code,
						);
						do_action( 'wpml_set_element_language_details', $set_language_args );
					}
				}

				if ( is_wp_error( $int_post_id ) ) {
					throw new Exception( $str_exception );
				}
			}
		}

		$str_content = json_encode( $arr_person, JSON_UNESCAPED_UNICODE );

		$wpdb->insert(
			$str_tablename,
			array(
				'person_id'          => $arr_person['person_id'],
				'post_id'            => $int_post_id,
				'content'            => $str_content,
				'hash'               => $str_hash,
				'web_visibility'     => $str_web_visibility,
				'deprecated_content' => 0,
				'deprecated'         => 0,
			),
			array( '%d', '%d', '%s', '%s', '%s', '%d', '%d' )
		);

		// $wpdb->query( 'COMMIT' );
	} catch ( Exception $exception ) {
		$obj_debug = new Verowa_Connect_Debugger();
		$obj_debug->write_to_file( 'Fehler Update Person: ' . $exception->getMessage() );
		// In the event of an error, the modifications are reversed
		// $wpdb->query( 'ROLLBACK' );
	}
}




/**
 * Insert a person into the database without creating a corresponding WordPress post.
 *
 * @param array  $arr_person       Array containing person data.
 * @param string $str_web_visibility   Web visibility of the person.
 * @param string $str_hash         Hash value associated with the person.
 *
 * @return void
 */
function verowa_person_db_insert_without_post( $arr_person, $str_web_visibility, $str_hash ) {
	global $wpdb;

	$str_content = json_encode( $arr_person );
	try {
		$wpdb->replace(
			$wpdb->prefix . 'verowa_person',
			array(
				'person_id'          => $arr_person['person_id'],
				'post_id'            => 0,
				'content'            => $str_content,
				'hash'               => $str_hash,
				'deprecated'         => 0,
				'deprecated_content' => 0,
				'web_visibility'     => $str_web_visibility,
			),
			array( '%d', '%d', '%s', '%s', '%d', '%d', '%s' )
		);
	} catch ( Exception $exception ) {
		$obj_debug = new Verowa_Connect_Debugger();
		$obj_debug->write_to_file( 'Fehler Insert Person: ' . $exception->getMessage() );
	}
}





/**
 * Update a person in the database without a corresponding WordPress post.
 *
 * @param array  $arr_person       Array containing person data.
 * @param string $str_web_visibility   Web visibility of the person.
 * @param string $str_hash         Hash value associated with the person.
 *
 * @return void
 */
function verowa_person_db_update_without_post( $arr_person, $str_web_visibility, $str_hash ) {
	global $wpdb;

	$str_content = wp_json_encode( $arr_person );
	try {
		$wpdb->update(
			$wpdb->prefix . 'verowa_person',
			array(
				'post_id'            => 0,
				'content'            => $str_content,
				'hash'               => $str_hash,
				'deprecated'         => 0,
				'deprecated_content' => 0,
				'web_visibility'     => $str_web_visibility,
			),
			array(
				'person_id' => $arr_person['person_id'],
			),
			array( '%d', '%s', '%s', '%d', '%d', '%s' ),
			array( '%d' )
		);
	} catch ( Exception $exception ) {
		$obj_debug = new Verowa_Connect_Debugger();
		$obj_debug->write_to_file( 'Fehler Update Person: ' . $exception->getMessage() );
	}

}





/**
 * Insert a person group into the database.
 *
 * @param array  $arr_person_group          Array containing person group data.
 * @param string $str_person_ids            Comma-separated string of person IDs belonging to the group.
 * @param string $str_members_group_functions   Comma-separated string of functions in the group.
 *
 * @return void
 */
function verowa_person_group_db_insert( $arr_person_group, $str_person_ids, $str_members_group_functions ) {
	global $wpdb;

	$arr_content = array(
		'name'          => $arr_person_group['name'] ?? '',
		'public_target' => $arr_person_group['public_target'] ?? '',
		'short_name'    => $arr_person_group['short_name'] ?? '',
	);
	$str_content = json_encode( $arr_content );

	// Hash everything. With the hash it is easy to check whether something has been changed.
	$str_hash = verowa_person_group_generate_hash(
		$arr_person_group['group_id'],
		$str_person_ids,
		$str_members_group_functions,
		$str_content
	);

	$wpdb->insert(
		$wpdb->prefix . 'verowa_person_groups',
		array(
			'pgroup_id'          => intval( $arr_person_group['group_id'] ),
			'parent_id'          => intval( $arr_person_group['parent_id'] ),
			'content'            => $str_content,
			'person_ids'         => $str_person_ids,
			'functions_in_group' => $str_members_group_functions,
			'hash'               => $str_hash,
			'deprecated'         => 0,
		),
		array( '%d', '%d', '%s', '%s', '%s', '%s', '%d' )
	);
}



/**
 * Updates a person in the DB.
 *
 * @param array  $arr_person          Array containing person data to be updated.
 * @param string $str_web_visibility  Web visibility status for the person.
 * @param string $str_hash            Hash value to check for changes in person data.
 *
 * @return void
 */
function verowa_person_db_update( $arr_person, $str_web_visibility, $str_hash ) {
	global $wpdb;
	$bool_persons_without_detail_page = get_option( 'verowa_persons_without_detail_page', false ) == 'on' ?
		true : false;
	
	$str_person_tablename      = $wpdb->prefix . 'verowa_person';
	$str_default_language_code = verowa_wpml_get_default_language_code();

	try {
		$str_title   = verowa_person_get_title( $arr_person );
		$int_post_id = 0;

		$str_update_exception = 'Update not possible ' . $str_title . '(' . $arr_person['person_id'] . ')';
		$arr_person_ml_html   = show_a_person_from_verowa_detail( $arr_person );

		$query           = 'SELECT `post_id` FROM `' . $str_person_tablename . '` ' .
			'WHERE person_id = ' . $arr_person['person_id'] . ';';
		$current_post_id = $wpdb->get_var( $query );

		if ( null === $current_post_id ) {
			throw new Exception( '$current_post_id ist null (person.php)' );
		}

		$current_post_id = intval( $current_post_id );

		// Create post only if the detail page is needed.
		if ( false === $bool_persons_without_detail_page ) {
			$trid = null;
			if ( 0 === $current_post_id ) {
				// New posts object.
				foreach ( $arr_person_ml_html as $str_language_code => $arr_single_person ) {
					if ( true === verowa_wpml_is_configured() ) {
						$str_post_name = $arr_person['person_id'] . '-' . $str_language_code;
					} else {
						$str_post_name = $arr_person['person_id'];
					}

					$post = array(
						'post_title'   => wp_strip_all_tags( $str_title ),
						'post_name'    => $str_post_name,
						'post_content' => $arr_single_person['html'],
						'post_excerpt' => $arr_person['short_desc'],
						'post_type'    => 'verowa_person',
						'post_status'  => 'publish',
					);

					$int_post_id              = verowa_general_insert_custom_post( $post, 'verowa_person', 'person_id',
						'', $arr_single_person['head'] ?? '' );

					
					if ( ! is_wp_error( $int_post_id ) ) {
						$str_source_language_code = $str_language_code !== $str_default_language_code ?
							$str_default_language_code : null;

						if (null == $trid) {
							$get_language_args = array( 'element_id' => $int_post_id, 'element_type' => 'post_verowa_person' );
							$original_post_language_info = apply_filters( 'wpml_element_language_details', null, $get_language_args );
							$trid = $original_post_language_info->trid;
						} else {
							$set_language_args = array(
								'element_id' => $int_post_id,
								'element_type' => 'post_verowa_person',
								'trid' => $trid,
								'language_code' => $str_language_code,
								'source_language_code' => $str_source_language_code,
							);
							do_action( 'wpml_set_element_language_details', $set_language_args );
						}
					}
				}
			} else {
				if ( true === verowa_wpml_is_configured() ) {
					$get_language_args = array( 'element_id' => $current_post_id, 'element_type' => 'post_verowa_person' );
					$original_post_language_info = apply_filters( 'wpml_element_language_details', null, $get_language_args );
					$trid = $original_post_language_info->trid;
					$arr_translations = apply_filters( 'wpml_get_element_translations', null, $trid, 'post_verowa_person' );
					foreach ( $arr_person_ml_html as $str_language_code => $arr_single_person ) {
						$int_element_id = intval( $arr_translations[ $str_language_code ]->element_id ?? 0 );

						if ( $int_element_id > 0 ) {
							// Update existing post object.
							$arr_post = array(
								'ID'           => $int_element_id,
								'post_title'   => wp_strip_all_tags( $str_title ),
								'post_content' => $arr_person_ml_html[ $str_language_code ]['html'],
								'post_excerpt' => $arr_person['short_desc'],
							);

							// this function call must be nested in a try catch block.
							$int_post_id = verowa_general_update_custom_post( $arr_post, '',
								$arr_person_ml_html[ $str_language_code ]['head']);
						} else {
							if ( true === verowa_wpml_is_configured() ) {
								$str_post_name = $arr_person['person_id'] . '-' . $str_language_code;
							} else {
								$str_post_name = $arr_person['person_id'];
							}

							$post = array(
								'post_title'   => wp_strip_all_tags( $str_title ),
								'post_name'    => $str_post_name,
								'post_content' => $arr_person_ml_html[ $str_language_code ]['html'],
								'post_excerpt' => $arr_person['short_desc'],
								'post_type'    => 'verowa_person',
								'post_status'  => 'publish',
							);

							$int_post_id = verowa_general_insert_custom_post(
								$post,
								'verowa_person',
								'person_id',
								'',
								$arr_person_ml_html[ $str_language_code ]['html']
							);

							if ( ! is_wp_error( $int_post_id ) ) {

								$str_source_language_code = $str_language_code !== $str_default_language_code ?
									$str_default_language_code : null;

								$set_language_args = array(
									'element_id'           => $int_post_id,
									'element_type'         => 'post_verowa_person',
									'trid'                 => $trid,
									'language_code'        => $str_language_code,
									'source_language_code' => $str_source_language_code,
								);
								do_action( 'wpml_set_element_language_details', $set_language_args );
							}
						}
					}
				} else {
					$arr_post = array(
						'ID'           => intval( $current_post_id ),
						'post_title'   => wp_strip_all_tags( $str_title ),
						'post_content' => $arr_person_ml_html['de']['html'],
						'post_excerpt' => $arr_person['short_desc'],
					);
					// this function call must be nested in a try catch block.
					$int_post_id = verowa_general_update_custom_post( $arr_post, '',$arr_person_ml_html['de']['head'] );
				}
			}
		}

		$ret_update = $wpdb->update(
			$str_person_tablename,
			array(
				'post_id'        => intval( $int_post_id ),
				'content'        => json_encode( $arr_person, JSON_UNESCAPED_UNICODE ),
				'hash'           => $str_hash,
				'web_visibility' => $str_web_visibility,
				'deprecated'     => 0,
			),
			array(
				'person_id' => $arr_person['person_id'],
			),
			array( '%d', '%s', '%s', '%s', '%d' ),
			array( '%d' )
		);

		if ( false === $ret_update ) {
			// jumps directly into the catch block.
			throw new Exception( $str_update_exception );
		}
	} catch ( Exception $exception ) {
		// $obj_debug->write_to_file( 'Fehler Update persons: ' . $exception->getMessage() );
		// In the event of an error, the modifications are reversed
		// $wpdb->query ( 'ROLLBACK' );
	}
}




/**
 * Updates a group of persons in the DB.
 *
 * @param array  $arr_person_group            Array containing person group data to be updated.
 * @param string $str_hash                    Hash value to check for changes in group data.
 * @param string $str_person_ids              Comma-separated string of person IDs belonging to the group.
 * @param string $str_members_group_functions Comma-separated string of functions in the group.
 *
 * @return void
 */
function verowa_person_group_db_update( $arr_person_group, $str_hash,
	$str_person_ids, $str_members_group_functions ) {
	global $wpdb;

	$arr_content = array(
		'name'          => $arr_person_group['name'] ?? '',
		'public_target' => $arr_person_group['public_target'] ?? '',
		'short_name'    => $arr_person_group['short_name'] ?? '',
	);
	$str_content = json_encode( $arr_content );

	$wpdb->update(
		$wpdb->prefix . 'verowa_person_groups',
		array(
			'person_ids'         => $str_person_ids,
			'parent_id'          => intval( $arr_person_group['parent_id'] ),
			'content'            => $str_content,
			'functions_in_group' => $str_members_group_functions,
			'hash'               => $str_hash,
			'deprecated'         => 0,
		),
		array(
			'pgroup_id' => $arr_person_group['group_id'],
		),
		array( '%s', '%d', '%s', '%s', '%s', '%d' ),
		array( '%d' )
	);
}




/**
 * Update all posts which related witch a verowa person
 */
function verowa_person_db_update_posts_content() {
	$arr_persons = verowa_persons_get_multiple( null, 'FULL' );
	foreach ( $arr_persons as $arr_single_person ) {
		$arr_html = show_a_person_from_verowa_detail( $arr_single_person );

		if ( intval( $arr_single_person['post_id'] ?? 0 ) > 0 ) {
			$arr_post = array(
				'ID'           => intval( $arr_single_person['post_id'] ),
				'post_content' => $arr_html['de']['html'],
			);

			try {
				// This function call must be nested in a try catch block.
				verowa_general_update_custom_post( $arr_post, '', $arr_html['de']['head']);
			} catch ( Exception $exception ) {
				$obj_debug = new Verowa_Connect_Debugger();
				$obj_debug->write_to_file( 'Fehler event update: ' . $exception->getMessage() );
			}
		}
	}
}




/**
 * Deletes the corresponding person in the DB with the associated post.
 *
 * @param int $person_id The ID of the person to be removed.
 *
 * @return void
 */
function verowa_person_db_remove( $person_id ) {
	global $wpdb;

	if ( intval( $person_id ) > 0 ) {
		$str_person_tablename = $wpdb->prefix . 'verowa_person';
		$query                = 'SELECT `post_id` FROM `' . $str_person_tablename . '` ' .
				'WHERE `person_id` = ' . $person_id . ';';

		$int_post_id = $wpdb->get_var( $query );

		if ( null != $int_post_id ) {
			verowa_delete_post( $int_post_id, 'verowa_person' );

			$wpdb->delete(
				$str_person_tablename,
				array(
					'person_id' => $person_id, // value in column to target for deletion.
				),
				array( '%d' ) // format of value being targeted for deletion.
			);
		}
	}
}




/**
 * Delete all person posts and remove the related post_in in 'verowa_person' table.
 */
function verowa_person_db_flush_posts() {
	global $wpdb;

	$str_person_tablename = $wpdb->prefix . 'verowa_person';
	$query                = 'SELECT `person_id`, `post_id` FROM `' . $str_person_tablename . '`;';
	$arr_person           = $wpdb->get_results( $query, ARRAY_A );

	foreach ( $arr_person as $arr_single_pers_ids ) {
		try {
			$int_post_id = intval( $arr_single_pers_ids['post_id'] );
			verowa_delete_post( $int_post_id, 'verowa_person' );

			// Remove post id from verowa person
			$int_person_id = intval( $arr_single_pers_ids['person_id'] );
			$wpdb->update(
				$str_person_tablename,
				array(
					'post_id' => 0,
				),
				array(
					'person_id' => $int_person_id,
				),
				array( '%d' ),
				array( '%d' )
			);
		} catch ( Exception $exception ) {
			echo $exception->getMessage();
			$obj_debug = new Verowa_Connect_Debugger();
			$obj_debug->write_to_file( 'Fehler Delete persons: ' . $exception->getMessage() );
		}
	} // end foreach

	// If there are still some posts, they will be deleted.
	$all_person = get_posts(
		array(
			'post_type'   => 'verowa_person',
			'numberposts' => -1,
		)
	);

	foreach ( $all_person as $single_post ) {
		verowa_delete_post( $single_post->ID, 'verowa_person' );
	}
}




/**
 * Generates the MD5 hash for a person from the person info.
 *
 * @param int   $int_person_id  The ID of the person.
 * @param array $arr_content    Array containing person content data.
 *
 * @return string               The MD5 hash value representing the person.
 */
function verowa_person_generate_hash( $int_person_id, $arr_content ) {
	return md5( $int_person_id . json_encode( $arr_content ?? array() ) );
}



/**
 * Generates the MD5 hash for a group of people from the group info.
 *
 * @param int    $int_group_id               The ID of the group.
 * @param string $str_person_ids             Comma-separated string of person IDs in the group.
 * @param string $str_members_group_functions Comma-separated string of group functions for the members.
 * @param string $str_content                JSON-encoded string containing group content data.
 *
 * @return string                           The MD5 hash value representing the group of people.
 */
function verowa_person_group_generate_hash( $int_group_id, $str_person_ids, $str_members_group_functions, $str_content ) {
	return md5( $int_group_id . $str_person_ids . $str_members_group_functions . $str_content );
}




/**
 * If $str_person_id is an empty string all person are set to deprecated.
 *
 * @param string $str_person_id z.B. '32,322,21'.
 *
 * @return mixed
 */
function verowa_persons_set_deprecated( $str_person_ids = '' ) {
	global $wpdb;

	$query = 'UPDATE `' . $wpdb->prefix . 'verowa_person` SET `deprecated` = 1';
	if ( '' != $str_person_ids ) {
		$wpdb->escape_by_ref( $str_person_ids );
		$query .= ' WHERE `person_id` IN (' . $str_person_ids . ')';
	}
	$query .= ';';

	$wpdb->query( $query );
}




/**
 * Set a person group to deprecated
 *
 * @param int $int_group_id ID of the group, which set to deprecated.
 * @return void
 */
function verowa_person_group_set_deprecated( $int_group_id ) {
	global $wpdb;

	$wpdb->update(
		$wpdb->prefix . 'verowa_person_groups',
		array(
			'deprecated' => 1,
		),
		array(
			'pgroup_id' => $int_group_id,
		),
		array( '%d' ),
		array( '%d' )
	);
}




/**
 * Deletes deprecated person groups from the database.
 */
function verowa_person_groups_delete_deprecated() {
	global $wpdb;

	$wpdb->delete(
		$wpdb->prefix . 'verowa_person_groups',
		array(
			'deprecated' => 1, // value in column to target for deletion
		),
		array(
			'%d', // format of value being targeted for deletion
		)
	);
}




/**
 * Get the person content from the database based on the person ID.
 *
 * @param int $int_person_id The ID of the person.
 * @return mixed             The content of the person as an associative array.
 */
function verowa_person_db_get_content( $int_person_id ) {
	global $wpdb;

	$arr_person_data = array();
	if ( intval( $int_person_id ?? 0 ) > 0 ) {
		$str_get_person_query = 'SELECT `content` FROM `' . $wpdb->prefix . 'verowa_person` WHERE `person_id` = ' .
			$int_person_id . ';';

		$arr_person_data = $wpdb->get_results( $str_get_person_query, ARRAY_A );
	}
	return key_exists( 0, $arr_person_data ) ? $arr_person_data[0] : array();
}




/**
 * Get multiple persons from the database based on person IDs and web visibility.
 *
 * @param array  $arr_person_ids    An array of person IDs.
 * @param string $str_web_visibility The web visibility status.
 * @return array                    An associative array of persons.
 */
function verowa_persons_get_multiple( $arr_person_ids = null, $str_web_visibility = '' ) {
	global $wpdb;
	$arr_persons = array();

	$str_query = 'SELECT * FROM `' . $wpdb->prefix . 'verowa_person`';
	$arr_where = array();

	if ( null !== $arr_person_ids &&
		is_array( $arr_person_ids ) &&
		0 !== count( $arr_person_ids ) ) {
		$arr_where[] = '`person_id` IN (' .
		implode( ', ', $arr_person_ids ) . ')';
	}

	if ( '' != $str_web_visibility ) {
		$arr_where[] = '`web_visibility` = "' . $str_web_visibility . '"';
	}

	if ( count( $arr_where ) > 0 ) {
		$str_query .= ' WHERE ' . implode( ' AND ', $arr_where ) . ' ';
	}

	$arr_persons_raw = $wpdb->get_results( $str_query, ARRAY_A );
	$arr_persons     = array();

	foreach ( $arr_persons_raw as $arr_single_person ) {
		$arr_persons[ $arr_single_person['person_id'] ] = json_decode( $arr_single_person['content'], true );
	}

	return $arr_persons;
}




/**
 * Get all the persons belonging to a group from the database.
 *
 * @param mixed $int_group_id The ID of the group.
 * @return array              An associative array of persons belonging to the group.
 */
function verowa_persons_get_for_group( $int_group_id ) {
	global $wpdb;

	$arr_persongroup_infos = array();
	$arr_person_info       = array();

	$query = 'SELECT `person_ids`, `functions_in_group` FROM ' .
		'`' . $wpdb->prefix . 'verowa_person_groups` WHERE `pgroup_id` = ' . $int_group_id . ' AND `deprecated` = 0;';

	$arr_persongroup_infos = $wpdb->get_results( $query, ARRAY_A );

	$arr_group_functions = is_array( $arr_persongroup_infos ) && count( $arr_persongroup_infos ) > 0 ?
		json_decode( $arr_persongroup_infos[0]['functions_in_group'], true ) : array();

	$arr_person_ids = array();
	// We remove the semicolon at the beginning and end of the string
	if ( count( $arr_persongroup_infos ) > 0 ) {
		$arr_person_ids = explode(
			';',
			substr(
				substr(
					$arr_persongroup_infos[0]['person_ids'],
					1,
					strlen( $arr_persongroup_infos[0]['person_ids'] ) - 1
				),
				0,
				-1
			)
		);

		if ( is_array( $arr_person_ids ) && '' !== ( $arr_person_ids[0] ?? '' ) ) {
			foreach ( $arr_person_ids as $person_id ) {
				$arr_person                    = verowa_person_db_get_content( $person_id );
				$arr_person_info[ $person_id ] = json_decode( $arr_person['content'] ?? '', true );

				if ( is_array( $arr_group_functions ) && count( $arr_group_functions ) > 0 ) {
					$arr_person_info[ $person_id ]['function_in_group'] =
						key_exists( $person_id, $arr_group_functions ) && strlen( $arr_group_functions[ $person_id ] ) > 0 ?
						$arr_group_functions[ $person_id ] : '';
				}
			}
		}
	}

	return $arr_person_info;
}




/**
 * Check if a verowa_person has no related post and add it if missing.
 *
 * @param int   $person_id  The ID of the person.
 * @param array $arr_person An associative array containing person information.
 */
function verowa_person_add_wp_post( $person_id, $arr_person ) {
	global $wpdb;
	// TODO: Erweitern für WPML
	$str_table_name  = $wpdb->prefix . 'verowa_person';
	$query           = 'SELECT `post_id` FROM `' . $str_table_name . '` ' .
		'WHERE person_id = ' . $person_id . ';';
	$current_post_id = $wpdb->get_var( $query );

	if ( $current_post_id != null && intval( $current_post_id ) == 0 ) {

		$arr_content_html = show_a_person_from_verowa_detail( $arr_person );
		$str_title        = verowa_person_get_title( $arr_person );

		$post = array(
			'post_title'   => wp_strip_all_tags( $str_title ),
			'post_name'    => $person_id,
			'post_content' => $arr_content_html['de']['html'],
			'post_excerpt' => $arr_person['short_desc'],
			'post_type'    => 'verowa_person',
			'post_status'  => 'publish',
		);

		$int_post_id = verowa_general_insert_custom_post( $post, 'verowa_person', 'person_id',
			'', $arr_content_html['de']['head']);

		if ( null != $int_post_id && ! is_wp_error( $int_post_id ) ) {

			$wpdb->update(
				$str_table_name,
				array(
					'post_id' => intval( $int_post_id ),
				),
				array(
					'person_id' => $person_id,
				),
				array( '%d' ),
				array( '%d' )
			);
		}
	}
}




/**
 * Get person groups from the database.
 *
 * @return array An associative array representing the person groups as a tree structure.
 */
function verowa_person_group_db_get() {
	global $wpdb;

	$arr_restrictions   = array();
	$arr_restrictions[] = '`deprecated` = 0';

	$query       = 'SELECT * FROM ' .
		'`' . $wpdb->prefix . 'verowa_person_groups`';
	$query      .= ' WHERE ' . implode( 'AND', $arr_restrictions ) . ';';
	$arr_pgroups = $wpdb->get_results( $query, ARRAY_A ) ?? array();

	for ( $i = 0; $i < count( $arr_pgroups ); $i++ ) {
		$arr_pgroups[ $i ] = array_merge( json_decode( $arr_pgroups[ $i ]['content'], true ), $arr_pgroups[ $i ] );
		unset( $arr_pgroups[ $i ]['content'] );
		$arr_pgroups[ $i ]['children'] = array();
	}

	$arr_p_name = array_column( $arr_pgroups, 'name' );
	array_multisort( $arr_pgroups, $arr_p_name );

	$arr_groups_tree = verowa_get_tree( $arr_pgroups, 'pgroup_id', 'parent_id' );
	return $arr_groups_tree;
}




/**
 * Compiles the person's title and returns it as a string.
 *
 * @param array $arr_person An associative array containing person information.
 * @return string           The compiled title of the person.
 */
function verowa_person_get_title( $arr_person ) {
	$arr_title = array();
	if ( '' != $arr_person['firstname'] ) {
		$arr_title[] = $arr_person['firstname'];
	}

	// falls es den Vor- UND den Nachnamen gibt, mit Leerstelle trennen
	if ( '' != $arr_person['lastname'] ) {
		$arr_title[] = $arr_person['lastname'];
	}
	return implode( ' ', $arr_title );
}
