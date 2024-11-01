<?php
/**
 * Filter for person and event details
 * - the_title:     remove the post title
 * - body_class:    add a class to the body tag
 * - sep_fb_event_listing_shortcode:    support flexy-breadcrump
 * - pre_get_posts: filter to exclude old verowa events from search
 *
 * Action
 * - wp_head:       to add robots Metatags
 * - parse_request: update custom post if its content is deprecated
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since Verowa 2.2.0
 * @package Verowa Connect
 * @subpackage General
 */

add_filter( 'the_title', 'verowa_post_title', 10, 2 );
add_filter( 'body_class', 'verowa_person_body_class' );
add_filter( 'body_class', 'verowa_event_body_class' );
add_filter( 'pre_get_posts', 'verowa_events_exclude_from_search' );
add_filter( 'template_include', 'verowa_load_custom_template' );

add_action( 'wp_head', 'verowa_hook_add_metatags' );
add_action( 'parse_request', 'verowa_parse_request_update_custom_post_if_deprecated' );
add_action( 'template_redirect', 'verowa_custom_post_redirect' );

/**
 * Remove the post title for Verowa event and person post types on the front-end.
 *
 * @param string $str_title The original post title.
 * @param int    $id        The ID of the post.
 *
 * @return mixed The modified post title.
 */
function verowa_post_title( $str_title, $id = NULL ) {
	global $wp;
	if ( null !== $id ) {
		$obj_post = get_post( $id );

		if ( false === key_exists( 's', $wp->query_vars ?? array() )
			&& ! is_admin()
			&& isset( $obj_post )
			&&  true === in_array( $obj_post->post_type, array( 'verowa_event', 'verowa_person', 'verowa_posting' ), true ) ) {
			$str_title = '';
		}
	}

	return $str_title;
}




/**
 * The class "verowa_event_body" is added to the body tag
 *
 * @param array $classes existing classes of the html body tag.
 *
 * @return array
 */
function verowa_event_body_class( $classes ) {
	global $post;

	if ( null !== $post && 'verowa_event' === $post->post_type ) {
		$classes[] = 'verowa_event_body';
	}

	return $classes;
}


/**
 * The class "verowa_person_body" is added to the body-tag
 *
 * @param array $classes existing classes of the html body tag.
 *
 * @return mixed
 */
function verowa_person_body_class( $classes ) {
	global $post;

	if ( null != $post && 'verowa_person' === $post->post_type ) {
		$classes[] = 'verowa_person_body';
	}

	return $classes;
}




/**
 * Filter to exclude past verowa events from WP search
 *
 * @param WP_Query $query WP_Query object.
 *
 * @return mixed
 */
function verowa_events_exclude_from_search( $query ) {
	global $wpdb;

	if ( $query->is_search ) {
		$str_query = 'SELECT `post_id` FROM `' . $wpdb->prefix . 'verowa_events` ' .
			'WHERE `datetime_to` <= "' . wp_date( 'Y-m-d H:i:s' ) . '";';
		$arr_post_ids = array_column( $wpdb->get_results( $str_query, ARRAY_A ), 'post_id' );
		$query->set( 'post__not_in', $arr_post_ids );

		if ( 'verowa_event' == ( $query->query['post_type'] ?? '' ) ) {
			$query->set( 'meta_key', 'verowa_event_datetime_from' );
			$query->set( 'order', 'asc' );
			$query->set( 'orderby', 'verowa_event_datetime_from' );
		}
	}

	return $query;
}




function verowa_load_custom_template( $original_template ) {
	global $wp_query;
	$str_query_name = $wp_query->query['category_name'] ?? '';
	if ( 'verowa' == $str_query_name ) {
		$str_template_url = dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'includes' .
			DIRECTORY_SEPARATOR . 'verowa-page-template.php';
		return $str_template_url;
	} else {
		return $original_template;
	}
}




/**
 * Remove the Link around the titel.
 *
 * @param string $title Title of the current post.
 *
 * @return string
 */
function verowa_unlink_post_title( $title ) {
	return '<h2 class="entry-title" itemprop="headline">' . wp_filter_nohtml_kses( $title ) . '</h2>';
}

$plugin_list = get_option( 'active_plugins' );
$str_plugin_id = 'flexy-breadcrumb/flexy-breadcrumb.php';

if ( in_array( $str_plugin_id, $plugin_list, true ) ) {

	// Check if plugin exists.
	add_filter( 'sep_fb_event_listing_shortcode', 'sep_fb_event_listing_shortcode' );

	/**
	 * As the event and person details are generated from the DB.
	 * The breadcrumb must be created.
	 *
	 * @param string $html_breadcrumb HTML of default bread crumb.
	 *
	 * @return string
	 */
	function sep_fb_event_listing_shortcode( $html_breadcrumb ) {
		global $post;

		// Only the page name is checked and not the shortcode,
		// as only the name can be used to determine whether it is an event or a person.
		if ( null !== $post && 'verowa_event' === $post->post_type ) {
			$arr_current_event = verowa_event_db_get_content( $post->post_name );
			$str_replacement = '';
			if ( null !== $arr_current_event ) {
				$str_replacement = '<span itemprop="name" title="">' . $arr_current_event['title'] . '</span>';
			}
			$html_breadcrumb = str_replace(
				'<span itemprop="name" title=""></span>',
				$str_replacement,
				$html_breadcrumb
			);
		}

		if ( null != $post && 'verowa_person' === $post->post_type ) {
			$arr_person = verowa_person_db_get_content( $post->post_name );
			if ( key_exists( 'content', $arr_person ) ) {
				$arr_current_person = json_decode( $arr_person['content'], true );
				$str_title = $arr_current_person['firstname'];

				// if there is the first name AND the surname, separate with a space.
				if ( '' != $str_title && '' != $arr_current_person['lastname'] ) {
					$str_title .= ' ';
				}

				$str_title .= $arr_current_person['lastname'];

				$str_replacement = '<span itemprop="name" title="">' . $str_title . '</span>';
				$html_breadcrumb = str_replace(
					'<span itemprop="name" title=""></span>',
					$str_replacement,
					$html_breadcrumb
				);
			}
		}

		return $html_breadcrumb;
	}
}




/**
 * Adds meta tags to the head section of the HTML page for Verowa event and person post types.
 *
 * @return void
 */
function verowa_hook_add_metatags() {
	global $post, $wp_query;
	$show_noindex_tag           = false;
	$show_unavailable_after_tag = false;
	$str_datetime_end           = '';
	$str_siteurl                = verowa_get_base_url();

	if ( null !== $post && 'verowa_event' === $post->post_type ) {
		$show_noindex_tag = 'on' === get_option( 'verowa_events_exclude_from_search_engines', false ) ? true : false;
		
		echo get_post_meta( $post->ID, 'VEROWA_HTML_HEAD', true );
	
		$int_event_id = intval( $post->post_name );
		$arr_event = verowa_events_db_get_multiple( strval( $int_event_id ) )[0] ?? [];
		
		if ( is_array( $arr_event ) && count( $arr_event ) > 0 ) {
			try {
				$int_datetime_to = strtotime( $arr_event['datetime_to'] );
				// The date is formatted according to ISO 8601. Do not change!
				$str_datetime_end = date( 'Y-m-d\\TH:i:sO', $int_datetime_to );
				$show_unavailable_after_tag = true;
			} catch (Exception $exception) {
				// In case of an error, the tag is not displayed.
				$show_unavailable_after_tag = false;
				$str_datetime_end = '';
			}
		}

		$arr_event_content = $arr_event['content'] ?? [];
		$str_description = $arr_event_content['short_desc'] ?? $arr_event_content['long_desc'] ?? '';
		echo '<!-- VC --><meta property="og:url"       content="' . $str_siteurl . $_SERVER['REQUEST_URI'] . '" />' . PHP_EOL .
			'<meta property="og:type"        content="article" />' . PHP_EOL .
			'<meta property="og:title"       content="' . ($arr_event_content['title'] ?? '') . '" />' . PHP_EOL .
			'<meta property="og:description" content="' . $str_description . '" />' . PHP_EOL .
			'<meta property="og:image"       content="' . ($arr_event_content['image_url'] ?? '') . '" />' . PHP_EOL;
	}

	if ( null != $post && 'verowa_person' == $post->post_type ) {
		$show_noindex_tag = get_option( 'verowa_persons_exclude_from_search_engines', false ) == 'on' ? true : false;
	}

	if ( true === $show_noindex_tag ) {
		echo PHP_EOL . '<meta name="robots" content="noindex, nofollow" /><!-- VC -->' . PHP_EOL;
	}

	if ( true === $show_unavailable_after_tag && strlen( $str_datetime_end ) > 0 ) {
		echo '<meta name="robots" content="unavailable_after: ' . $str_datetime_end . '">' . PHP_EOL;
	}

}




/**
 * If the content of the custom post is out of date, it will be updated via this function shortly before it is displayed.
 *
 * @param WP $query WP object.
 */
function verowa_parse_request_update_custom_post_if_deprecated( $query ) {
	global $wpdb, $wp;

	if ( true === is_array( $query->query_vars ) ) {
		if (
			key_exists( 'post_type', $query->query_vars ?? array() ) &&
			'verowa_event' === $query->query_vars['post_type'] ?? '' ) {

			// z.B. {even_id}-{language_code}.
			$arr_verowa_event = explode( '-', $query->query_vars['verowa_event'] ?? '' );
			$int_event_id = intval( $arr_verowa_event[0] ?? 0 );
			if ( $int_event_id > 0 ) {
				$str_query = 'SELECT * FROM `' . $wpdb->prefix . 'verowa_events` ' .
					'WHERE `event_id` = ' . $int_event_id;
				$arr_ret = $wpdb->get_results( $str_query, ARRAY_A )[0] ?? array();
				$int_post_id = $arr_ret['post_id'] ?? 0;
				$is_deprecated = filter_var( $arr_ret['deprecated_content'] ?? false, FILTER_VALIDATE_BOOLEAN );

				if ( true === $is_deprecated && $int_post_id > 0 ) {
					$arr_translations            = verowa_wpml_get_translations( $int_post_id );
					$arr_event                   = json_decode( $arr_ret['content'], true );
					$int_eventdetail_template_id = get_option( 'verowa_default_eventdetails_template', 0 );
					$arr_templates               = verowa_get_single_template( $int_eventdetail_template_id );
					$arr_ret_new_content         = verowa_event_get_single_content( 0, $arr_templates, $arr_event );

					foreach ( $arr_ret_new_content as $str_lang_code => $arr_new_content ) {
						$arr_post = array(
							'ID' => intval( $arr_translations[ $str_lang_code ]->element_id ?? 0 ),
							'post_title' => wp_strip_all_tags( $arr_translations[ $str_lang_code ]->post_title ?? '' ),
							'post_content' => $arr_new_content['html'],
						);

						verowa_general_update_custom_post( $arr_post, $arr_new_content['script'], 
							$arr_new_content['head'] );
					}

					$wpdb->update(
						$wpdb->prefix . 'verowa_events',
						array(
							'deprecated_content' => 0,
						),
						array(
							'event_id' => $int_event_id,
						),
						array( '%d' ),
						array( '%d' )
					);
				}
			}
		}


		if ( key_exists( 'post_type', $query->query_vars ?? array() ) && 'verowa_person' === $query->query_vars['post_type'] ) {
			$int_person_id = intval( $query->query_vars['verowa_person'] ?? 0 );
			if ( $int_person_id > 0 ) {
				$str_query = 'SELECT * FROM `' . $wpdb->prefix . 'verowa_person` ' .
					'WHERE `person_id` = ' . $int_person_id;
				$arr_ret = $wpdb->get_results( $str_query, ARRAY_A )[0] ?? array();
				$int_post_id = $arr_ret['post_id'] ?? 0;
				$is_deprecated = filter_var( $arr_ret['deprecated_content'] ?? false, FILTER_VALIDATE_BOOLEAN );
				if ( true === $is_deprecated && $int_post_id > 0 ) {
					$arr_person = json_decode( $arr_ret['content'], true );
					$arr_new_content = show_a_person_from_verowa_detail( $arr_person );

					$arr_title = array();
					if ( '' != ( $arr_person['firstname'] ?? '' ) ) {
						$arr_title[] = $arr_person['firstname'];
					}

					// If there is the first name AND the surname, separate with a space.
					if ( '' != ( $arr_person['lastname'] ?? '' ) ) {
						$arr_title[] = $arr_person['lastname'];
					}

					$str_title = implode( ' ', $arr_title );


					$curr_language_code = verowa_wpml_get_current_language();
					$str_content = $arr_new_content[ $curr_language_code ]['html'];

					$arr_post = array(
						'ID' => intval( $int_post_id ?? 0 ),
						'post_title' => $str_title,
						'post_content' => $str_content,
					);

					verowa_general_update_custom_post( $arr_post, '', $arr_new_content[ $curr_language_code ]['head'] );
					$wpdb->update(
						$wpdb->prefix . 'verowa_person',
						array(
							'deprecated_content' => 0,
						),
						array(
							'person_id' => $int_person_id,
						),
						array( '%d' ),
						array( '%d' )
					);
				}
			}
		}


		// TODO: Umschreiben für ML
		if ( true === is_array( $query->query_vars ) ) {
			if (
				key_exists( 'post_type', $query->query_vars ?? array() ) &&
				'verowa_posting' === $query->query_vars['post_type'] ?? '' ) {

				// z.B. {ver_post_id}-{language_code}.
				$arr_verowa_posting = explode( '-', $query->query_vars['verowa_posting'] ?? '' );
				$int_posting_id = intval( $arr_verowa_posting[0] ?? 0 );
				if ( $int_posting_id > 0 ) {
					$str_query = 'SELECT * FROM `' . $wpdb->prefix . 'verowa_postings` ' .
						'WHERE `ver_post_id` = ' . $int_posting_id;
					$arr_ret = $wpdb->get_results( $str_query, ARRAY_A )[0] ?? array();
					$int_post_id = $arr_ret['post_id'] ?? 0;
					$is_deprecated = filter_var( $arr_ret['deprecated_content'] ?? false, FILTER_VALIDATE_BOOLEAN );

					if ( true === $is_deprecated && $int_post_id > 0 ) {
						$arr_event = json_decode( $arr_ret['content'], true );

						$int_postingdetails_template_id = get_option( 'verowa_default_postingdetails_template', 0 );
						$arr_templates = verowa_get_single_template( $int_postingdetails_template_id );
						$obj_postings = new Picture_Planet_GmbH\Verowa_Connect\Verowa_Postings();
						$str_new_content = $obj_postings->assembling_html( $arr_ret, $arr_templates['de'] );

						$arr_post = array(
							'ID' => $int_post_id,
							'post_content' => $str_new_content,
						);

						verowa_general_update_custom_post( $arr_post );

						$wpdb->update(
							$wpdb->prefix . 'verowa_postings',
							array(
								'deprecated_content' => 0,
							),
							array(
								'ver_post_id' => $int_posting_id,
							),
							array( '%d' ),
							array( '%d' )
						);
					}
				} // $int_posting_id > 0
			}
		}
	}
}



/**
 * ...
 */
function verowa_custom_post_redirect() {
	global $wp;
	$arr_matches = [];
	$pattern = '/^anmelden\/(\d+)/';
	preg_match ($pattern, $wp->request, $arr_matches);
	
	if (preg_match( $pattern, $wp->request, $arr_matches)) 
	{
		wp_redirect( '/veranstaltung/' + $arr_matches[1] );
		die;
	}
}