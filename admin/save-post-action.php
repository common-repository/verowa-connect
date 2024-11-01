<?php
/**
 * Action on save post
 *
 * Project:         VEROWA CONNECT
 * File:            admin/save_post_action.php
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @package Verowa Connect
 * @subpackage  Backend
 */

add_action( 'save_post', 'verowa_save_post_action', 10, 2 );

/**
 * Update of the WP option "verowa_list_ids" on change
 *
 * @param int     $post_id ID of WP post.
 * @param WP_Post $post A single WP post.
 */
function verowa_save_post_action( $post_id, $post ) {

	if ( 'revision' !== $post->post_type ) {
		$arr_verowa_list_ids = json_decode( get_option( 'verowa_list_ids' ), true );
		$arr_new_list_ids    = verowa_extract_list_ids( $post->post_content );
		$bool_update         = false;

		// If an id is not found, the update is executed.
		if ( true === is_array( $arr_new_list_ids ) && count( $arr_new_list_ids ) > 0 ) {
			foreach ( $arr_new_list_ids as $single_id ) {
				if ( false === in_array( $single_id, $arr_verowa_list_ids, true ) ) {
					$bool_update = true;
					break;
				}
			}
		}

		if ( $bool_update ) {
			verowa_update_list_id_option();
			$obj_update = new Verowa_Update_Controller();
			$obj_update->init( 'list_map' );
			$obj_update->update_verowa_event_list_mapping();
		}

		verowa_update_roster_ids_option();
	}
}
