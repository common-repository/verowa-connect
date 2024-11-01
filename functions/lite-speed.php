<?php
/**
 * Collection of functions to ensure Connect functionality with LiteSpeed
 *
 * Project:         VEROWA CONNECT
 * File:            functions/lite_speed.php
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since 2.9.1
 * @package Verowa Connect
 * @subpackage Functions
 */

add_action( 'litespeed_tag_finalize', 'verowa_set_tag' );
add_action( 'litespeed_control_finalize', 'verowa_control_finalize' );

/**
 * If LiteSpeed is active, true is returned, otherwise false.
 *
 * @return bool
 */
function verowa_litespeed_is_active() {
	$is_active         = false;
	$arr_active_plugin = get_option( 'active_plugins', array() );

	if ( in_array( 'litespeed-cache/litespeed-cache.php', $arr_active_plugin, true ) ) {
		$is_active = true;
	}

	return $is_active;
}




/**
 * Adds specific URLs to the list of excluded URLs from caching in LiteSpeed Cache plugin.
 *
 * If LiteSpeed Cache is active, the function retrieves the current list of excluded URLs from the LiteSpeed Cache
 * configuration, and then adds each URL specified in the constant VEROWA_NOT_CACHE_URIS to the list, if it is not
 * already present. The function updates the LiteSpeed Cache configuration to include the newly added URLs.
 */
function verowa_litespeed_add_config() {
	if ( verowa_litespeed_is_active() ) {
		$arr_curr_cache_exc = json_decode( get_option( 'litespeed.conf.cache-exc' ), true );
		foreach ( VEROWA_NOT_CACHE_URIS as $str_url ) {
			if ( ! in_array( $str_url, $arr_curr_cache_exc, true ) ) {
				$arr_curr_cache_exc[] = $str_url;
			}
		}

		update_option( 'litespeed.conf.cache-exc', wp_json_encode( $arr_curr_cache_exc ) );
	}
}



/**
 * Every existing tag could be purge with this function
 *
 * @param array $arr_tags List of tags witch are purged.
 */
function verowa_litespeed_purge_tags( $arr_tags ) {
	if ( verowa_litespeed_is_active() ) {
		foreach ( $arr_tags as $str_single_tag ) {
			do_action( 'litespeed_purge', $str_single_tag );
		}
	}
}




/**
 * Set any tag for "shortcodes" or assign lists oder persons
 *
 * @return void
 */
function verowa_set_tag() {
	$post_id = get_the_ID();
	if ( false === $post_id ) {
		return;
	}

	$str_content = get_the_content();
	$arr_matches = array();
	preg_match_all(
		'/' . get_shortcode_regex() . '/',
		$str_content,
		$arr_matches,
		PREG_SET_ORDER
	);

	if ( count( $arr_matches ) > 0 ) {
		$arr_verowa_shortcodes = VEROWA_SHORTCODES;

		foreach ( $arr_matches as $arr_single_match ) {
			// index 2 of $arr_single_match is always the shortcode name
			// VEROWA_SHORTCODES is defined in the presets.
			$int_key = array_search( $arr_single_match[2], $arr_verowa_shortcodes );
			if ( false !== $int_key ) {
				do_action( 'litespeed_tag_add', $arr_verowa_shortcodes[ $int_key ] );

				// Only add one tag for the same shortcode.
				unset( $arr_verowa_shortcodes[ $int_key ] );
			}
		}
	}

	$int_list_id         = intval( get_post_meta( $post_id, '_verowa_list_assign', true ) );
	$int_layer_id        = intval( get_post_meta( $post_id, '_verowa_layer_assign', true ) );
	$int_target_group_id = intval( get_post_meta( $post_id, '_verowa_target_group_assign', true ) );
	if ( $int_list_id > 0 || $int_layer_id > 0 || $int_target_group_id > 0 ) {
		do_action( 'litespeed_tag_add', 'verowa_agenda' );
		do_action( 'litespeed_tag_add', 'verowa_event_liste_dynamic' );
		do_action( 'litespeed_tag_add', 'verowa_event_filter' );
		do_action( 'litespeed_tag_add', 'verowa_event_list' );
		do_action( 'litespeed_tag_add', 'verowa_event_liste' );
	}

	$single_persons = intval( get_post_meta( $post_id, '_person_singles', true ) );
	if ( $single_persons > 0 ) {
		do_action( 'litespeed_tag_add', 'verowa_person' );
		do_action( 'litespeed_tag_add', 'verowa_personen' );
	}

}




/**
 * Checks the content of the current post for specific shortcodes and triggers corresponding actions.
 *
 * The function scans the content of the current post to find specific shortcodes, and if any of those shortcodes
 * are found, it triggers corresponding actions using `do_action()` with appropriate debug info.
 *
 * The function doesn't return anything, as it is used as a hook or action callback.
 */
function verowa_control_finalize() {
	// Get the content of the current post.
	$str_content = get_the_content();

	// Check if the content contains the shortcode 'verowa_roster_entries'.
	if ( false !== stripos( $str_content, 'verowa_roster_entries' ) ) {
		// Second parameter is only used as debug info.
		do_action( 'litespeed_control_set_nocache', 'roster entries request over the API' );
	}

	if ( false !== stripos( $str_content, 'verowa-first-roster-entry' ) ) {
		// Second parameter is only used as debug info.
		do_action( 'litespeed_control_set_nocache', 'roster entries request over the API' );
	}

	if ( false !== stripos( $str_content, 'verowa_renting_form' ) ) {
		// Second parameter is only used as debug info.
		do_action( 'litespeed_control_set_nocache', 'renting over the API' );
	}
}
