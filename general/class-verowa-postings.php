<?php
/**
 * ...
 *
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @version 1.0
 * @package VEROWA CONNECT
 * @subpackage Forms
 */

namespace Picture_Planet_GmbH\Verowa_Connect {

	/**
	 * Handles operations related to Verowa postings.
	 */
	class Verowa_Postings {

		/**
		 * Retrieves postings from the database.
		 *
		 * @param string $str_cols The columns to select. Defaults to '*'.
		 * @param array|string $mixed_restrictions The restrictions for the WHERE clause. Can be an array or a string.
		 * @param string $str_order_by The ORDER BY clause. Defaults to '`ver_post_id` ASC'.
		 * @param int $int_limit The LIMIT clause. Defaults to 0 (no limit).
		 * @param int $int_offset The OFFSET clause. Defaults to 0.
		 *
		 * @return array|object|null An array of posting objects, a single posting object, or null if no postings are found.
		 */
		public static function find_postings( $str_cols = '*' , $mixed_restrictions = '',
			$str_order_by = '`ver_post_id` ASC', $int_limit = 0, $int_offset = 0) {
			global $wpdb;

			$str_where = is_array( $mixed_restrictions ) ? implode( ' AND ', $mixed_restrictions ) : $mixed_restrictions;
			if ( strlen( $str_where ) > 0 ) {
				$str_where = ' WHERE ' . $str_where;
			}
			
			$str_limit = ( $int_limit > 0 ) ? ' LIMIT ' . $int_limit : '';

			$arr_postings_data  = $wpdb->get_results(
				'SELECT ' . $str_cols . ' FROM `' . $wpdb->prefix . 'verowa_postings` ' .
				 $str_where . ' ORDER BY ' . $str_order_by . $str_limit, OBJECT_K
			);

			return $arr_postings_data;
		}


		/**
		 * Deactivates the cron job for postings importer.
		 */
		public function deactivate_cron_job() {
			// Unschedule cron job on plugin deactivation
			wp_clear_scheduled_hook( 'verowa_connect_postings_importer' );
		}

		/**
         * Schedules the cron job for postings importer.
         */
		public function schedule_cron_job() {
			$timestamp = wp_next_scheduled( 'verowa_connect_postings_importer' );
			
			if ( ! $timestamp ) {
				$obj_curr_date     = current_datetime();
				$str_instance      = get_option('verowa_instance', true );
				$int_minute        = ( hexdec(hash ( 'crc32', $str_instance ) ) % 59) + 1;
				$obj_date_next_run = \DateTime::createFromFormat(
					'Y-m-d H:i:s',
					$obj_curr_date->format( 'Y-m-d H' ) . ':' . str_pad( $int_minute, 2, '0', STR_PAD_LEFT ) . ':00'
				);

				if ( false !== $obj_date_next_run ) {
					$interval = 'twicedaily';
					// Schedule cron job if not already scheduled
					wp_schedule_event( $obj_date_next_run->getTimestamp(), $interval, 
						'verowa_connect_postings_importer' );
				}
			}
		}


		/**
         * Initializes actions, filters and shortcodes related to postings.
         */
		public function init() {
			add_action(
				'rest_api_init',
				function () {
					register_rest_route(
						'verowa/v1',
						'/update_posting/',
						array(
							'methods'             => 'GET',
							'callback'            => array( $this, 'force_update_postings' ),
							'permission_callback' => 'verowa_api_permission_callback',
						)
					);
				}
			);

			add_action(
				'rest_api_init',
				function () {
					register_rest_route(
						'verowa/v1',
						'/get_default_news_tempaltes/',
						array(
							'methods'             => 'GET',
							'callback'            => array( $this, 'get_default_news_tempaltes' ),
							'permission_callback' => 'verowa_api_permission_callback',
						)
					);
				}
			);

			add_action( 'verowa_connect_postings_importer', array( $this, 'verowa_connect_postings_importer' ) );
			add_filter( 'wp_kses_allowed_html', array($this, 'allow_iframes_for_post_type'), 10, 2 );

			add_shortcode( 'verowa_posting_list_home', array( $this, 'shortcode_verowa_posting_list_home') );
			add_shortcode( 'verowa_posting_list', array( $this, 'shortcode_posting_list') );
		}


		/**
         * Allows iframes for the 'post' post type.
         *
         * @param array $tags The allowed HTML tags.
         * @param string $context The context where the tags are used.
         *
         * @return array The modified allowed HTML tags.
         */
		public function allow_iframes_for_post_type( $tags, $context ) {

			if ($context == 'post') {
				$tags['iframe'] = array(
					'src'             => true,
					'height'          => true,
					'width'           => true,
					'frameborder'     => true,
					'allowfullscreen' => true,
					// Add any other allowed iframe attributes here
				);
			}
			return $tags;
		}


		/**
         * Adds default templates for posting details and posting list if they don't exist.
         */
		public static function add_templates() {
			global $wpdb;

			$query     = 'SELECT count(`template_id`) as `count` FROM `' . $wpdb->prefix . 'verowa_templates` ' .
				'WHERE `type` IN ("postingdetails","postinglist");';
			$int_count = intval( $wpdb->get_var( $query ) ?? 0 );

			if ( 0 === $int_count ) {
				$wpdb->insert(
					$wpdb->prefix . 'verowa_templates',
					array(
						'template_name' => 'News Liste',
						'info_text'     => '',
						'type'          => 'postinglist',
						'display_where' => 'content',
						'header'        => '',
						'entry'         => '<div class="roster-entry">postinglist' . PHP_EOL .
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

				add_option( 'verowa_default_postinglist_template', $wpdb->insert_id );

				$wpdb->insert(
					$wpdb->prefix . 'verowa_templates',
					array(
						'template_name' => 'News Details',
						'info_text'     => '',
						'type'          => 'postingdetails',
						'display_where' => 'content',
						'header'        => '',
						'entry'         => 'News <h3 class="verowa-roster-headline">Hausdienst</h3>' . PHP_EOL .
							'<div class="verowa-single-roster-entry">' . PHP_EOL .
							'	{BLOCKS_HTML}[[?IMAGE_URL:<div>' . PHP_EOL .
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
				add_option( 'verowa_default_postingdetails_template', $wpdb->insert_id );
			}
		}


		/**
         * Inserts a new post based on the Verowa posting data.
         *
         * @param array $arr_ver_posting The Verowa posting data.
         *
         * @return int|WP_Error The ID of the inserted post or a WP_Error object on failure.
         */
		private function insert_post( $arr_ver_posting ) {
			global $wpdb;
			
			try {
				$str_json_content = wp_json_encode( $arr_ver_posting, JSON_UNESCAPED_UNICODE );

				$int_template_id = get_option( 'verowa_default_postingdetails_template', 0 );
				$arr_template = verowa_get_single_template( $int_template_id );
				$obj_template = $arr_template['de'];

				$str_content = $this->assembling_html ( $arr_ver_posting, $obj_template );

				$wpdb->query( 'START TRANSACTION' );

				$arr_post = array(
					'post_title'   => $arr_ver_posting['title'],
					'post_name'    => $arr_ver_posting['post_id'],
					'post_type'    => 'verowa_posting',
					'post_excerpt' => $arr_ver_posting['lead'],
					'post_content' => '<!-- wp:html -->' . $str_content . '<!-- /wp:html -->',
					'post_status'  => 'publish',
					'meta_input'   => array(
						'event_id' => $arr_ver_posting['event_id'],
					),
				);

				// Insert the post into the database.
				$int_post_id = verowa_general_insert_custom_post(
					$arr_post,
					'verowa_postings',
					'ver_post_id'
				);

				if ( ! is_wp_error( $int_post_id ) ) {
					$wpdb->insert(
						$wpdb->prefix . 'verowa_postings',
						array(
							'ver_post_id'        => intval( $arr_ver_posting['post_id'] ),
							'event_id'           => intval( $arr_ver_posting['event_id'] ),
							'post_id'            => intval( $int_post_id ),
							'content'            => $str_json_content,
							'position'           => intval( $arr_ver_posting['position'] ),
							'publ_datetime_from' => $arr_ver_posting['publ_datetime_from'],
							'publ_datetime_to'   => $arr_ver_posting['publ_datetime_to'],
							'ver_last_mod_on'    => $arr_ver_posting['last_mod_datetime'],
							'deprecated'         => 0,
						),
						array( '%d', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%d' )
					);
				} elseif ( is_wp_error( $int_post_id ) ) {
					throw new \Exception( $int_post_id->get_error_message() );
				}

				$wpdb->query( 'COMMIT' );
				
			} catch ( \Exception $exception ) {
				$obj_debug = new \Verowa_Connect_Debugger();
				$obj_debug->write_to_file( 'Fehler einfügen eines posting: ' . $exception->getMessage() );
				// In the event of an error, the data is deleted.
				$wpdb->query( 'ROLLBACK' );
			}
			
			return $int_post_id;
		}


		/**
         * Updates an existing post based on the Verowa posting data.
         *
         * @param array $arr_ver_posting The Verowa posting data.
         *
         * @return int|false The number of rows updated or false on failure.
         */
		private function update_post( $arr_ver_posting ) {
			global $wpdb;

			$wpdb->query( 'START TRANSACTION' );

			$str_update_exception = 'Update not possible ' . $arr_ver_posting['title'] . '(' . $arr_ver_posting['event_id'] . ')';

			try {
				$int_template_id = get_option( 'verowa_default_postingdetails_template', 0 );
				$arr_templates   = verowa_get_single_template( $int_template_id );
				$obj_template    = $arr_templates['de'];
				$str_json_content = wp_json_encode( $arr_ver_posting, JSON_UNESCAPED_UNICODE );

				$query           = 'SELECT `post_id` FROM `' . $wpdb->prefix . 'verowa_postings` ' .
					'WHERE `ver_post_id` = ' . $arr_ver_posting['post_id'] . ';';
				$current_post_id = $wpdb->get_var( $query );

				// Error during query.
				if ( null === $current_post_id ) {
					throw new \Exception( '$current_post_id ist null ' );
				}

				//$str_search_content = do_shortcode( $str_content_html );
				//$str_search_content = preg_replace( '#<[^>]+>#', ' ', $str_search_content );
				$str_content = $this->assembling_html ( $arr_ver_posting, $obj_template );

				if ( 0 === $current_post_id ) {
					$str_post_name = $arr_ver_posting['post_id'];
					
					$post = array(
						'post_title'   => $arr_ver_posting['title'],
						'post_name'    => $str_post_name,
						'post_content' => '<!-- wp:html -->' . $str_content . '<!-- /wp:html -->',
						'post_excerpt' => $arr_ver_posting['lead'],
						'post_type'    => 'verowa_posting',
						'post_status'  => 'publish',
					);

					// Insert the post into the database.
					$int_post_id = verowa_general_insert_custom_post( $post, 'verowa_posting', 'ver_post_id' );

				} else {
					$arr_post = array(
						'ID'           => intval( $current_post_id ?? 0 ),
						'post_title'   => wp_strip_all_tags( $arr_ver_posting['title'] ),
						'post_content' => '<!-- wp:html -->' . $str_content . '<!-- /wp:html -->',
						'post_excerpt' =>  $arr_ver_posting['lead'],
					);

					$int_post_id = verowa_general_update_custom_post( $arr_post, '');
				}
				
				if ( $int_post_id > 0 ) {

					$arr_posting_data = array(
						'event_id'           => intval( $arr_ver_posting['event_id'] ),
						'content'            => $str_json_content,
						'position'           => intval( $arr_ver_posting['position'] ),
						'publ_datetime_from' => $arr_ver_posting['publ_datetime_from'],
						'publ_datetime_to'   => $arr_ver_posting['publ_datetime_to'],
						'ver_last_mod_on'    => $arr_ver_posting['last_mod_datetime'],
						'deprecated'         => 0,
						'deprecated_content' => 0,
					);

					$ret_update = $wpdb->update(
						$wpdb->prefix . 'verowa_postings',
						$arr_posting_data,
						array(
							'ver_post_id' => $arr_ver_posting['post_id'],
						),
						array( '%d', '%s', '%d', '%s', '%s', '%s', '%d', '%d' ),
						array( '%d' )
					);

					if ( false === $ret_update ) {
						// jumps directly into the catch block.
						throw new \Exception( $str_update_exception );
					}
					
				}
				$wpdb->query( 'COMMIT' );
				
			} catch ( \Exception $exception ) {
				$obj_debug = new \Verowa_Connect_Debugger();
				$obj_debug->write_to_file( 'Fehler Update posting: ' . $exception->getMessage() );
				// In the event of an error, the changes are reversed.
				$wpdb->query( 'ROLLBACK' );
				$ret_update = false;
			}

			return $ret_update;
		}


		/**
         * Assembles the HTML content for a posting based on the template.
         *
         * @param array $arr_ver_posting The Verowa posting data.
         * @param object $obj_template The template object.
         *
         * @return string The assembled HTML content.
         */
		public function assembling_html( $arr_ver_posting, $obj_template ) {
			
			if (isset($arr_ver_posting['content']) === true) {
				$arr_content = json_decode ($arr_ver_posting['content'], true);
				if (is_array( $arr_content ) === true)
				{
					$arr_ver_posting = array_merge( $arr_ver_posting, $arr_content );
				}
			}

			$str_siteurl        = verowa_get_base_url();
			$str_language_code = verowa_wpml_get_current_language();
			$wpml_is_configured = verowa_wpml_is_configured();
			$str_blocks         = $this->render_blocks( $arr_ver_posting['blocks'] );

			$arr_placeholders = array();
			$arr_placeholders['TITLE'] = $arr_ver_posting['title'];

			$i_posting_id = $arr_ver_posting['ver_post_id'] ?? $arr_ver_posting['post_id'];
			$arr_placeholders['POSTING_ID'] = $i_posting_id;

			$i_event_id = intval ($arr_ver_posting['event_id'] ?? 0);
			$arr_placeholders['EVENT_ID'] = $arr_ver_posting['event_id'];
			if($i_event_id > 0) {
				$arr_placeholders['IS_EVENT_POSTING'] = 1;
				if ( true === $wpml_is_configured ) {
					$arr_placeholders['POSTING_DETAILS_URL'] = verowa_wpml_get_custom_post_url ( $i_event_id, 
						$str_language_code, 'veranstaltung', 'verowa_event' );
				} else {
					$arr_placeholders['POSTING_DETAILS_URL'] = '/veranstaltung/' . $i_event_id . '/';
				}
			} else {
				$arr_placeholders['IS_EVENT_POSTING'] = 0;
				if ( true === $wpml_is_configured ) {
					$arr_placeholders['POSTING_DETAILS_URL'] = verowa_wpml_get_custom_post_url ( $i_posting_id, 
						$str_language_code, 'posting', 'verowa_posting' );
				} else {
					$arr_placeholders['POSTING_DETAILS_URL'] = '/posting/' . $i_posting_id . '/';
				}
			}
			$arr_placeholders['LEAD'] = $arr_ver_posting['lead'];
			$arr_placeholders['IMAGE_URL'] = $arr_ver_posting['image_url'];
			$arr_placeholders['BLOCKS_HTML'] = $str_blocks;
			
			$str_output = verowa_parse_template( $obj_template->entry, $arr_placeholders ) .
				$obj_template->separator;
			
			return $str_output;
		}


		 /**
         * Renders the blocks for a posting.
         *
         * @param array $arr_blocks The blocks data.
         *
         * @return string The rendered blocks HTML.
         */
		private function render_blocks( $arr_blocks ) {
			$str_content = '';
			$arr_block_templates = get_option( 'verowa_news_block_templates', [] );
			
			array_walk_recursive($arr_block_templates, function( & $value) {
				if ( true === is_string( $value ) ) {
					$value = stripslashes( $value );
				}
			});

			foreach ( $arr_blocks as $arr_single_block )
			{
				$str_type = $arr_single_block['type'] ?? '';
				
				$arr_plc = [];
				switch ( $str_type ) {
					case 'text':
						if ( trim( $arr_single_block['text'] ) != '' ) {
							$arr_plc['TEXT'] = trim( $arr_single_block['text'] );
							$str_content .= verowa_parse_template( $arr_block_templates[ $str_type ] ?? '', $arr_plc );
						}
						break;

					case 'image':
						$arr_plc['CSS_CLASSES'] = trim( $arr_single_block['css_classes'] );
						$arr_plc['CAPTION'] = trim( $arr_single_block['caption'] );
						$arr_plc['SOURCE_TEXT'] = trim( $arr_single_block['source_text'] );
						$arr_plc['SOURCE_URL'] = trim( $arr_single_block['source_url'] );
						$arr_plc['URL'] = trim( $arr_single_block['image_url'] );
						$str_content .= verowa_parse_template( $arr_block_templates[ $str_type ] ?? '', $arr_plc );
						break;

					case 'document':
						$files = $arr_single_block['block_files'] ?? [];
						if ( count($files) > 0 ) {
							$arr_plc['FILE_LIST'] = verowa_get_file_list( $files, false );
							$str_content .= verowa_parse_template( $arr_block_templates[ $str_type ] ?? '', $arr_plc );
						}
						break;

					case 'movie':
						$arr_plc['EMBED_ID'] = trim( $arr_single_block['embed_id'] );
						$str_content .= verowa_parse_template( $arr_block_templates[ $str_type ] ?? '', $arr_plc );
						break;

					case 'map':
						$arr_plc['CITY']   = urlencode( trim( $arr_single_block['city'] ) );
						$arr_plc['STREET'] = urlencode( trim( $arr_single_block['street'] ) );
						$str_content .= verowa_parse_template( $arr_block_templates[ $str_type ] ?? '', $arr_plc );
						break;

					case 'gallery':
						 $arr_block_templates[ $str_type ]['header'] ?? '';
						$arr_imges = $arr_single_block['gallery_images'] ?? [];
						foreach ($arr_imges as $arr_single_image)
						{
							$arr_plc['CAPTION'] = $arr_single_image['caption'];
							$arr_plc['SOURCE_TEXT'] = $arr_single_image['source_text'];
							$arr_plc['SOURCE_URL'] = $arr_single_image['source_url'];
							$arr_plc['THUMBNAIL_URL'] = $arr_single_image['thumbnail_url'];
							$arr_plc['URL'] = $arr_single_image['image_url'];
							$str_content .= verowa_parse_template( $arr_block_templates[ $str_type ]['entry'] ?? '', $arr_plc );
						}
						$str_content .= $arr_block_templates[ $str_type ]['footer'] ?? '';

					case 'raw_html':
						if ( trim( $arr_single_block['text'] ) != '' ) {
							$str_content .= trim( $arr_single_block['text'] );
						}
						break;
				}
			}

			return $str_content;
		}


		/**
         * Imports postings from the Verowa-API.
         *
         * @param int $ver_post_id The ID of a specific Verowa post to import (optional).
         *
         * @return void
         */
		public function verowa_connect_postings_importer()
		{
			$this->import_postings();
		}


		/**
		 * Imports postings from the Verowa API.
		 *
		 * @param int $ver_post_id The ID of a specific Verowa post to import (optional).
		 *
		 * @return void
		 */
		private function import_postings( $ver_post_id = 0 ) {
			global $wpdb;

			$arr_module_infos = verowa_get_module_infos();
			if (false == $arr_module_infos['postings']['enabled'])
			{
				return -1;
			}

			add_filter( 'force_filtered_html_on_import' , '__return_false' );
			$arr_postings = verowa_api_call( 'getpostings', 'website/' . $ver_post_id, true );
			
			
			if ($arr_postings['code'] === 204 && $ver_post_id > 0 || // Single post is requested, but not found
				$arr_postings['code'] === 200 && is_array( $arr_postings['data'] )) { // All postings should be imported
				$this->set_postings_deprecated( $ver_post_id );
			}

			if ( $arr_postings['code'] === 200 && is_array( $arr_postings['data'] ) ) {
				$arr_existing_post = Verowa_Postings::find_postings( '`ver_post_id`, `ver_last_mod_on`' );
				$arr_existing_post_ids = array_column( $arr_existing_post, 'ver_post_id');
				
				foreach ( $arr_postings['data'] as $arr_single_posting ) {
					$int_post_id = $arr_single_posting['post_id'];
					if ( false === in_array( $int_post_id, $arr_existing_post_ids ) ) {
						$this->insert_post( $arr_single_posting );
					} else {
						if ($arr_existing_post[$int_post_id]->ver_last_mod_on != $arr_single_posting['last_mod_datetime'])
						{
							$this->update_post( $arr_single_posting );
						}
						else
						{
							$wpdb->update(
								$wpdb->prefix . 'verowa_postings',
								array(
									'deprecated' => 0,
								),
								array(
									'ver_post_id' => $int_post_id,
								),
								array( '%d' ),
								array( '%d' )
							);
						}
					}
				}
			}
			
			// In any case the deprecated posts will be deleted.
			verowa_general_delete_deprecated( $wpdb->prefix . 'verowa_postings' );
		}


		/**
		 * Retrieves the posting list for the homepage.
		 *
		 * @param array $atts The shortcode attributes.
		 *
		 * @return string The rendered posting list.
		 */
		public function shortcode_verowa_posting_list_home( $atts ) {
			$atts = shortcode_atts(
				array(
					'template_id' => get_option( 'verowa_default_postinglist_template', 0 ),
				),
				$atts,
				'verowa_posting_list_home'
			);
			return $this->get_posting_list( true, $atts['template_id'] );
		}


		 /**
         * Retrieves the posting list.
         *
         * @param bool $is_home Whether to retrieve the posting list for the homepage.
         * @param int $int_template_id The ID of the template to use.
         *
         * @return string The rendered posting list.
         */
		public function shortcode_posting_list( $atts ) {
			$atts = shortcode_atts(
				array(
					'template_id' => get_option( 'verowa_default_postinglist_template', 0 ),
				),
				$atts,
				'verowa_posting_list'
			);
			return $this->get_posting_list ( false, $atts['template_id'] );
		}


		/**
		 * Retrieves the posting list.
		 *
		 * @param bool $is_home Whether to retrieve the posting list for the homepage.
		 * @param int $int_template_id The ID of the template to use.
		 *
		 * @return string The rendered posting list.
		 */
		private function get_posting_list( $is_home, $int_template_id ) {
			$obj_curr_date = new \DateTime( 'now', wp_timezone() );
			$arr_restrictions = array(
				'`publ_datetime_from` <= "' . $obj_curr_date->format( 'Y-m-d H:i:s' ) . '"',
				'`publ_datetime_to` >= "' . $obj_curr_date->format( 'Y-m-d H:i:s' ) . '"',
			);
			
			$arr_postings_raw = Verowa_Postings::find_postings( '*', $arr_restrictions, 'publ_datetime_from ASC' );
			$arr_postings_raw = array_values( $arr_postings_raw );
			$arr_ordered_postings = [];

			// ** Determine the position (this is unfortunately a bit tricky)

			// We go through the positions for the homepage one by one; where a position has been fixed,
			// we take it into account

			// NOTE: The position is also assigned if the post is not yet displayed due to the date;
			// because as soon as the time is reached, the order is automatically correct.
			if ( true === $is_home ) {
				$arr_fixed_pos = array();
				$arr_module_infos = get_option('verowa_module_infos', []);
				$nb_tiles_on_homepage = $arr_module_infos['postings']['channel_options']['nb_on_homepage'] ?? 6;

				for ( $i = 1; $i <= $nb_tiles_on_homepage; $i++ ) {
					// Is there a post that claims this position and has not yet been used?
					for ( $j = 0; $j < count( $arr_postings_raw ); $j++ ) {
						if (
							$arr_postings_raw[ $j ]->position == $i &&
							intval( $arr_postings_raw[ $j ]->order ?? 0 ) === 0 ) {
							$i_curr_pos = $i;

							// If the position is already taken, the post moves to the next free one
							while ( $arr_fixed_pos[ $i_curr_pos ] ?? 0 > 0 ) {
								++$i_curr_pos;
							}

							$arr_postings_raw[ $j ]->order = $i_curr_pos;
							$arr_fixed_pos[ $i_curr_pos ] = $arr_postings_raw[ $j ]->post_id;
						}
					}
				}

				$i_curr_pos = 1;

				// We simply add all posts that have not yet been ordered one after the other
				for ($j = 0; $j < count($arr_postings_raw); $j++) {
					if (intval($arr_postings_raw[$j]->order ?? 0) == 0) {
						while (in_array($i_curr_pos, array_column($arr_postings_raw, 'order'))) {
							$i_curr_pos++;
						}

						$arr_postings_raw[$j]->order = $i_curr_pos++;
					}
				}
				$arr_ordered_postings = $arr_postings_raw;
				$arr_order = array_column ($arr_postings_raw, 'order');
				array_multisort( $arr_order, SORT_ASC, $arr_ordered_postings );
			} else{
				$arr_ordered_postings = $arr_postings_raw;
			}

			$arr_template = verowa_get_single_template( $int_template_id );
			$obj_template = $arr_template['de'];
			$str_content = $obj_template->header;
			
			foreach ( $arr_ordered_postings as $obj_single_posting ) {
				$str_content .= $this->assembling_html( (array)$obj_single_posting, $obj_template );
			}

			$str_content .= $obj_template->footer;

			return $str_content;
		}


		/**
         * Forces an update of postings.
         *
         * @return int 0
         */
		public function force_update_postings() {
			$ver_post_id = strval( $_GET['post_id'] ?? '' );
			$this->import_postings( $ver_post_id );
			return 0;
		}


		/**
		 * Retrieves the default news templates.
		 * 
		 * @return string[] The default news templates.
		 */
		public function get_default_news_tempaltes() {
			return array(
					'text' => '<div class="verowa-absatz">' . PHP_EOL .
						'	{TEXT}' . PHP_EOL .
						'</div>',
					'image' => '<div class="{CSS_CLASSES}">' . PHP_EOL .
						'	<img src="{URL}" alt="{CAPTION}" />' . PHP_EOL .
						'	<a href="{SOURCE_URL}">{SOURCE_TEXT}</a>' . PHP_EOL .
						'</div>',
					'document' => '<div>' . PHP_EOL .
						'{FILE_LIST}' . PHP_EOL .
						'</div>',
					'movie' => '<iframe width="560" height="315" src="https://www.youtube.com/embed/{EMBED_ID}" ' . PHP_EOL .
						'title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope;" allowfullscreen></iframe>',
					'map' => '<iframe width="600" height="450" frameborder="0" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" ' . PHP_EOL .
						'src="https://www.google.com/maps?q={STREET}, {CITY}&ie=UTF8&iwloc=&output=embed"></iframe>',
			);
		}


		/**
         * Sets the default news templates.
         */
		public function set_default_news_tempaltes() {
			$arr_nb_templates = $this->get_default_news_tempaltes();
			add_option( 'verowa_news_block_templates', $arr_nb_templates );
		}


		/**
		 * Retrieves the file extension from a URL.
		 *
		 * @param string $url The URL to extract the file extension from.
		 *
		 * @return string|null The file extension or null if no extension is found.
		 */
		private function get_file_extension_from_url_regex( $url ) {
			$pattern = '#(.+)?\.(\w+)(\?.+)?#';
			preg_match($pattern, $url, $matches);
  
			if (isset($matches[0])) {
				return $matches[2]; // Remove leading dot
			} else {
				return null; // No extension found
			}
		}


		/**
         * Sets postings as deprecated.
         *
         * @param int $post_id The ID of a specific post to deprecate (optional).
         */
		private function set_postings_deprecated( $post_id = 0 ) {
			global $wpdb;

			$str_where = ( $post_id > 0 ) ? '`ver_post_id` = ' . $post_id : '`ver_post_id` > 0';

			$wpdb->query(
				'UPDATE `' . $wpdb->prefix . 'verowa_postings` SET `deprecated` = 1 WHERE ' . $str_where
			);
		}
	}
}