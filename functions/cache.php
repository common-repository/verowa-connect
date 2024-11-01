<?php
/**
 * Collection of functions to provide PHP cache usage
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since 2.9.1
 * @package Verowa Connect
 * @subpackage Functions
 */

/**
 * Execute the initialize call to add the verowa connect cache hooks.
 */
function verowa_cache_bind_hooks() {
	add_action( 'verowa_purge_shortcode_cache', 'verowa_purge_shortcode_cache' );
}




/**
 *  Standard functions to exclude pages for a caching plug-in
 */
function verowa_cache_exclude_uris() {
	// Include also the exclude of dynamic pages.
	verowa_litespeed_add_config();
}




/**
 * If any cache should purge, this function will be called.
 * Currently only lite speed is implemented.
 *
 * @param array $arr_shortcodes List of shortcodes to purge.
 *
 * @return void
 */
function verowa_purge_shortcode_cache( $arr_shortcodes ) {
	verowa_litespeed_purge_tags( $arr_shortcodes );
}
