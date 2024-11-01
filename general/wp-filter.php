<?php
/**
 * Filter for general functions
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since Version 2.8.5
 * @package Verowa Connect
 * @subpackage General
 */

add_filter( 'the_content', 'verowa_apend_js_to_content', 8 );
add_filter( 'wpml_tm_dashboard_documents', 'verowa_exclude_post_type' );


/**
 * Add JS related to specific verowa event or person to the content
 *
 * @param string $content html post content.
 *
 * @return string the modified post content.
 */
function verowa_apend_js_to_content( $content ) {
	global $post;
	if ( 'verowa_event' === get_post_type() || 'verowa_person' === get_post_type() 
		|| 'verowa_posting' === get_post_type() ) {
		$content .= get_post_meta( $post->ID, VEROWA_POST_RELATED_SCRIPT_META_KEY, true );
	}
	return $content;
}


function verowa_exclude_post_type( $docs ) {
	foreach ((array) $docs as $key => $doc) {
		if ( isset( $doc->translation_element_type )
			&& in_array(
				$doc->translation_element_type,
				array( 'post_verowa_event', 'post_verowa_person', 'post_verowa_posting' )
			) ) {
			unset( $docs[ $key ] );
		}
	}
	
	return $docs;
}


$wp_version = isset( $wp_version ) === true ? $wp_version : get_bloginfo( 'version' );
if ( version_compare( $wp_version, '6.0.0', '>=' ) ) {
	// UNDONE: (AM) sprachversionen prüfen
	add_action( 'wp_insert_post_data', 'verowa_check_verowa_pages_on_update', 10, 4 );
	/**
	 * Checks whether a verowa permalink was edited when a page is updated. In this case,
	 * it will be renamed to its correct name.
	 *
	 * @param array   $data The array of post data to be updated.
	 * @param array   $arr_post The original array of post data before the update.
	 * @param array   $arr_unsanitized_post The unsanitized array of post data before the update.
	 * @param boolean $update Whether the post is being updated or created.
	 *
	 * @return array The updated array of post data.
	 */
	function verowa_check_verowa_pages_on_update( $data, $arr_post, $arr_unsanitized_post, $update ) {
		// Check is only necessary for updates, the pages are created by the Plug-in.
		if ( true === $update && false === verowa_wpml_is_configured()) {
			$arr_verowa_pages = array(
				// UNDONE: Prüfen, gewisse Seiten könne im VC konfiguriert werden. Evtl. BS CWe
				array(
					'expected_post_name' => 'subscription-form',
					'shortcode' => 'verowa_subscription_form',
				),
				array(
					'expected_post_name' => 'verowa-subscription-confirmation',
					'shortcode' => 'verowa_subscription_confirmation',
				),
				array(
					'expected_post_name' => 'anmeldung-validieren',
					'shortcode' => 'verowa_subscription_validation',
				),
				array(
					'expected_post_name' => 'verowa_renting_response',
					'shortcode' => 'reservationsanfrage-response',
				),
				array(
					'expected_post_name' => 'reservationsanfrage-validieren',
					'shortcode' => 'verowa_renting_validate',
				),
			);

			foreach ( $arr_verowa_pages as $arr_single_page ) {
				// If the content contains a shortcode of a Verowa Connect page, it is checked whether the post_name is incorrect.
				if ( false !== stripos( $data['post_content'], '[' . $arr_single_page['shortcode'] . ']' ) &&
					$data['post_name'] != $arr_single_page['expected_post_name'] ) {
					// Set correct post name for the plug-in page.
					$data['post_name'] = $arr_single_page['expected_post_name'];
					break; // A maximum of one page can be found.
				}
			}
		}

		return $data;
	}
}