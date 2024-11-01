<?php
/**
 * Handle the WP-DB update. When the plug-in is activate or the update cronjob is running
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since Version 2.2.0
 * @package Verowa Connect
 * @subpackage General
 */

/**
 * Update Verowa data for the webpage.
 *
 * @version 1.0
 */
class Verowa_Update_Controller {
	/**
	 * Key is the event id and value all related list ids.
	 *
	 * @var array
	 */
	private $arr_list_ids_for_event = array();

	/**
	 * Contains all list IDs used on the website.
	 *
	 * @var array
	 */
	private $arr_list_ids;

	/**
	 * Array to assign ranges to events.
	 *
	 * @var array
	 */
	private $wordpress_liste;

	/**
	 * Result of the API call "getlistsbygroup".
	 *
	 * @var array
	 */
	private $arr_verowa_groups;

	/**
	 * Table Name.
	 *
	 * @var string
	 */
	private $str_events_tablename;

	/**
	 * Initializes member variables.
	 */
	public function __construct() {
		global $wpdb;
		$this->str_events_tablename = $wpdb->prefix . 'verowa_events';
		verowa_delete_duplicate_custom_post();
		set_time_limit( 600 );
	}

	/**
	 * Loads the required information for an appropriate update according to the mode.
	 *
	 * all:             Only when complete vc data are updated.
	 * single_event:    When a single event is updated.
	 * list_map:        List mapping of all events are updated.
	 *
	 * @param string  $str_mode all, single_event, or list_map.
	 * @param integer $event_id In case of an immediate update the ID is greater than 0.
	 */
	public function init( $str_mode, $event_id = 0 ) {
		global $wpdb;

		switch ( $str_mode ) {
			case 'all':
				/**** Here below we enter all events, persons and groups of persons into the tables */
				// First we have to update or create the option which tracks all listen ids.
				// (if we are doing this for the first time).

				verowa_update_list_id_option();
				verowa_update_roster_ids_option();

				$arr_module_infos = verowa_get_module_infos();
				update_option( 'verowa_module_infos', $arr_module_infos );

				// Update LayerIds.
				$hierarchical_array_for_dropdown = verowa_get_hierarchical_layers_tree();
				if ( is_array( $hierarchical_array_for_dropdown ) && count( $hierarchical_array_for_dropdown ) > 0 ) {
					update_option(
						'verowa_hierarchical_layers_tree',
						wp_json_encode( $hierarchical_array_for_dropdown ),
						false
					);
				}

				// Array to assign ranges to events.
				$this->wordpress_liste = get_option( 'verowa_wordpress_listengruppe', true );

				// Only required for updating the agenda filter.
				$arr_ret_api_call        = verowa_api_call( 'getlistsbygroup', $this->wordpress_liste . '-e', true );
				$this->arr_verowa_groups = $arr_ret_api_call['data'];
				$this->arr_list_ids      = json_decode( get_option( 'verowa_list_ids' ), true );

				if ( is_array( $this->arr_list_ids ) && count( $this->arr_list_ids ) > 0) {
					$str_list_ids = implode( ',', $this->arr_list_ids );
					$arr_ret_api_call = verowa_api_call( 'geteventslistmap', $str_list_ids . '/0', true );
					$this->arr_list_ids_for_event = is_array( $arr_ret_api_call['data'] ) ? $arr_ret_api_call['data'] : array();
				}

				if ( true === verowa_wpml_is_configured() ) {
					// Bereinigt im Fehlerfall die Tabelleder Übersetzungen
					$query = 'DELETE FROM `' . $wpdb->prefix . 'icl_translations` WHERE `element_type` LIKE "post_verowa_%" AND ' .
						'`element_id` NOT IN (SELECT `ID` FROM `' . $wpdb->prefix . 'posts`);';
					$wpdb->query( $query );
				}
				break;

			case 'single_event':
				$this->arr_list_ids = json_decode( get_option( 'verowa_list_ids' ), true );
				if ( is_array( $this->arr_list_ids ) && count( $this->arr_list_ids ) > 0) {
					$str_list_ids = implode ( ',', $this->arr_list_ids );
					$arr_ret_api_call = verowa_api_call( 'geteventslistmap', $str_list_ids . '/' . $event_id, true );
					$this->arr_list_ids_for_event = is_array( $arr_ret_api_call['data'] ) ? $arr_ret_api_call['data'] : array();
				}
				break;

			case 'list_map':
				$this->arr_list_ids = json_decode( get_option( 'verowa_list_ids' ), true );
				if (is_array( $this->arr_list_ids ) && count( $this->arr_list_ids ) > 0 ) {
					$arr_ret_api_call = verowa_api_call (
						'geteventslistmap',
						implode (',', $this->arr_list_ids) . '/0',
						true
					);
					$this->arr_list_ids_for_event = is_array( $arr_ret_api_call['data'] ) ? 
						$arr_ret_api_call['data'] : array();
				}
				break; 
		}

	}




	/**
	 * Updates the list for the Agenda Filter.
	 */
	public function update_agenda_filters() {
		$filter_array = array();

		$dropdown_count = 1;
		$nb_dropdowns   = get_option( 'how_many_verowa_dropdowns', false );

		if ( null !== $this->arr_verowa_groups ) {
			while ( $dropdown_count <= $nb_dropdowns ) {
				$filter_options = explode( ', ', get_option( 'verowa_dropdown_' . $dropdown_count, true ) );

				foreach ( $filter_options as $group_id ) {
					$group_key      = array_search( $group_id, array_column( $this->arr_verowa_groups, 'list_id' ) );
					$filter_array[] = array(
						'list_id'   => $this->arr_verowa_groups[ $group_key ]['list_id'] ?? '',
						'event_ids' => explode( ',', $this->arr_verowa_groups[ $group_key ]['event_ids'] ?? '' ),
					);
				}

				$dropdown_count++;
			}
		}

		// Call String für Events.
		$verowa_call_string = '%20/0/1000'; // First only 1000 events from the API.
		$arr_ret_api_call   = verowa_api_call( 'geteventsbylist', $verowa_call_string, true );
		$arr_api_events     = $arr_ret_api_call['data'];
		$arr_events         = $arr_api_events['events'] ?? array();

		foreach ( $arr_events as $value ) {
			$temp_events_cat = '';

			foreach ( $filter_array as $filter ) {
				$temp_cat = array_search( $value['event_id'], $filter['event_ids'], true );
				if ( false !== $temp_cat ) {
					$temp_events_cat .= ' .events-' . $filter['list_id'];
				}
			}

			$value['event_cats'] = $temp_events_cat;
		}

		if ( function_exists( 'update_option' ) ) {
			add_option( 'verowa_agenda_filter', '' );
		}

		if ( ! empty( $this->arr_verowa_groups ) ) {
			update_option( 'verowa_agenda_filter', wp_json_encode( $this->arr_verowa_groups ), false );
		}
	}




	/**
	 * Updates the available target groups from verowa
	 */
	public function update_targetgroups() {
		$arr_ret_api_call = verowa_api_call( 'gettargetgroups', '', false );
		$target_groups    = $arr_ret_api_call['data'];

		if ( is_array( $target_groups ) && count( $target_groups ) > 0 ) {
			update_option( 'verowa_targetgroups', $target_groups );
		}
	}




	/**
	 * Decides whether all stored events from the Verowa API should be updated or only a single event.
	 *
	 * @param mixed $update_event_id Only this event is queried.
	 */
	public function update_verowa_events_in_db( $update_event_id = 0 ) {
		if ( $update_event_id > 0 ) {
			$this->immediate_update_single_verowa_event( $update_event_id );
		} else {
			$this->update_all_verowa_events();
		}

		do_action(
			'verowa_purge_shortcode_cache',
			array(
				'verowa_agenda',
				'verowa_event_liste_dynamic',
				'verowa_event_filter',
				'verowa_event_list',
				'verowa_event_liste',
				'verowa_subscriptions_form',
			)
		);
	}




	/**
	 * Update a single verowa event in DB.
	 *
	 * @param mixed $update_event_id ID for an immediate update.
	 */
	private function immediate_update_single_verowa_event( $update_event_id ) {
		global $wpdb;

		if ( $update_event_id > 0 ) {

			$query           = 'SELECT `person_id`, `hash` FROM `' . $wpdb->prefix . 'verowa_person` WHERE `web_visibility` = "EVENTS"';
			$arr_person_hash = $wpdb->get_results( $query, OBJECT_K );

			$query               = 'SELECT `person_id` FROM `' . $wpdb->prefix . 'verowa_person` WHERE `web_visibility` = "FULL";';
			$arr_person_full_ids = array_keys( $wpdb->get_results( $query, OBJECT_K ) );

			$query                = 'SELECT `event_id`, `hash` FROM `' . $this->str_events_tablename . '`' .
				' WHERE `event_id` = ' . $update_event_id . ';';
			$arr_obj_event_hashes = $wpdb->get_results( $query, OBJECT_K );
			$arr_ret_api_call     = verowa_get_eventdetails( array( $update_event_id ) );
			$arr_api_event_infos  = $arr_ret_api_call['data'];
			$status_code          = intval( $arr_ret_api_call['code'] );

			if ( 200 === $status_code || 204 === $status_code ) {
				if ( 0 === count( $arr_api_event_infos ?? array() ) ) {
					verowa_event_db_remove( $update_event_id );
				} else {
					verowa_wpml_map_lang( $single_event );
					$this->update_or_insert_single_verowa_event( $arr_api_event_infos[0], $arr_obj_event_hashes );
					$this->add_person_if_related_to_event( $arr_api_event_infos[0], $arr_person_full_ids, $arr_person_hash );
				} // else count of $arr_api_event_infos == 0.
			}
		}
	}


	/**
	 * Updating the stored events from the Verowa API.
	 */
	private function update_all_verowa_events() {
		global $wpdb;
		$str_api_message = '';

		// First we get the IDs and the info for the list pictures (because in the call for the event DETAILS we have
		// we only have the info for the detail picture, not for the list picture);
		// actually, there is still more info here, but we don't need it, because it is loaded again with the detail call
		// loaded again further down.
		$arr_ret_api_call  = verowa_api_call( 'geteventids', '', true );
		$status_code       = intval( $arr_ret_api_call['code'] );
		$str_api_message   = $arr_ret_api_call['message'];
		$arr_api_event_ids = $arr_ret_api_call['data'];

		if ( 200 === $status_code || 204 === $status_code ) {
			$arr_ret_api_call         = verowa_get_eventdetails( $arr_api_event_ids );
			$arr_api_event_infos      = $arr_ret_api_call['data'];
			$status_code_eventdetails = intval( $arr_ret_api_call['code'] );

			if ( 200 === $status_code_eventdetails || 204 === $status_code_eventdetails ) {
				verowa_events_db_set_to_deprecated();
				// Set person related with event, deprecated.
				$wpdb->update(
					$wpdb->prefix . 'verowa_person',
					array(
						'deprecated' => 1,
					),
					array(
						'web_visibility' => 'EVENTS',
					),
					array( '%d' ),
					array( '%s' ),
				);

				$query           = 'SELECT `person_id`, `hash` FROM `' . $wpdb->prefix . 'verowa_person` WHERE `web_visibility` = "EVENTS"';
				$arr_person_hash = $wpdb->get_results( $query, OBJECT_K );

				if ( is_array( $arr_api_event_ids ) && count( $arr_api_event_ids ) > 0 ) {
					$get_event_by_hash_query = 'SELECT `event_id`, `hash` FROM `' . $this->str_events_tablename . '`;';

					$arr_obj_event_hashes = $wpdb->get_results( $get_event_by_hash_query, OBJECT_K );

					$query               = 'SELECT `person_id` FROM `' . $wpdb->prefix . 'verowa_person` WHERE `web_visibility` = "FULL";';
					$arr_person_full_ids = array_keys( $wpdb->get_results( $query, OBJECT_K ) );

					// Get the service labels for the Template editor.
					$str_first_key     = array_key_first( $arr_api_event_infos );
					$arr_first_event   = $arr_api_event_infos[ $str_first_key ] ?? array();
					$arr_service_label = array();
					for ( $i = 1; $i <= 8; $i++ ) {
						$arr_service_label[ $i ] = $arr_first_event[ 'service' . $i . '_label' ] ?? '';
					}
					update_option( 'verowa_event_service_label', $arr_service_label );

					foreach ( $arr_api_event_infos as $single_event ) {
						// Change verowa language to spesifice WP language.
						verowa_wpml_map_lang( $single_event );
						$this->update_or_insert_single_verowa_event( $single_event, $arr_obj_event_hashes );
						$this->add_person_if_related_to_event( $single_event, $arr_person_full_ids, $arr_person_hash );
					}
				}

				// All deprecated events are deleted.
				// In case the API call worked but did not contain any events
				// all events are deleted.
				verowa_general_delete_deprecated( $this->str_events_tablename );

				$wpdb->delete(
					$wpdb->prefix . 'verowa_person',
					array(
						'deprecated' => 1,
					),
					array( '%d' )
				);
			}
		} else {
			if ( VEROWA_DEBUG ) {
				$str_mail  = get_site_url();
				$str_mail .= $str_api_message;
				verowa_send_mail( VEROWA_REPORTING_MAIL, 'WP Update cron', $str_mail );
			}
		}

		// Update all event with deprecated_content.
		$query      = 'SELECT `post_id`, `content` FROM `' . $wpdb->prefix . 'verowa_events` ' .
			'WHERE `deprecated_content` = 1';
		$arr_events = $wpdb->get_results( $query );

		$int_eventdetail_template_id = get_option( 'verowa_default_eventdetails_template', 0 );
		$arr_templates               = verowa_get_single_template( $int_eventdetail_template_id );

		foreach ( $arr_events as $single_dc_event ) {
			$arr_event       = json_decode( $single_dc_event->content ?? '', true );
			$arr_new_content = verowa_event_get_single_content( 0, $arr_templates, $arr_event );

			if ( true === verowa_wpml_is_configured() ) {
				$trid = apply_filters( 'wpml_element_trid', null, $single_dc_event->post_id, 'post_verowa_event' );
				$arr_translations = apply_filters( 'wpml_get_element_translations', null, $trid, 'post_verowa_event' );
				foreach ( $arr_new_content as $wp_language_code => $arr_single_content ) {
					$int_post_id = intval( $arr_translations[ $wp_language_code ]->element_id ?? 0 );
					if ( $int_post_id > 0 ) {
						$arr_post = array(
							'ID' => $int_post_id,
							'post_content' => $arr_single_content['html'],
						);
						verowa_general_update_custom_post( $arr_post, $arr_single_content['script'], $arr_single_content['head'] );
					}
				}
			}
			else
			{
				$arr_post = array(
					'ID'           => intval( $single_dc_event->post_id ),
					'post_content' => $arr_new_content['de']['html'],
				);
				verowa_general_update_custom_post( $arr_post, $arr_new_content['de']['script'], $arr_new_content['de']['head'] );
			}
		}

		$wpdb->update(
			$wpdb->prefix . 'verowa_events',
			array(
				'deprecated_content' => 0,
			),
			array(
				'deprecated_content' => 1,
			),
			array( '%d' ),
			array( '%d' ),
		);
	}




	/**
	 * Update or insert includes the associated post
	 *
	 * @param array $single_event Single event for insertion or update.
	 * @param array $arr_obj_event_hashes Reference for the event hash.
	 */
	private function update_or_insert_single_verowa_event( $single_event, &$arr_obj_event_hashes ) {
		global $wpdb;
		$arr_event = array();

		$has_subscription = false;
		if ( isset( $single_event['subscription_type'] ) ) {
			if ( 'none' !== $single_event['subscription_type'] ) {
				$has_subscription = true;
			} else {
				if ( $single_event['subscribe_person_id'] > 1 ||
					8 === strlen( $single_event['subscribe_date'] ) ) {
					$has_subscription = true;
				}
			}
		}

		// Get list ids for events, could be that no assignment is available.
		$str_list_ids = $this->arr_list_ids_for_event[ $single_event['event_id'] ] ?? '';

		// Rewrite list ids, layer ids and target groups into the format ;1;2;3;.
		$arr_ids = verowa_prepare_layer_list_target_group( $single_event, $str_list_ids );
		$single_event['list_ids'] = $arr_ids['list_ids'] ?? '';
		$arr_event[ $single_event['event_id'] ] = $single_event;

		// Set the image displayed on the detail page.
		$arr_event[ $single_event['event_id'] ]['detail_image']        = $single_event['image_url'];
		$arr_event[ $single_event['event_id'] ]['detail_image_width']  = $single_event['image_width'];
		$arr_event[ $single_event['event_id'] ]['detail_image_height'] = $single_event['image_height'];

		// If there is already an entry, the hash is recalculated and compared.
		if ( key_exists( $single_event['event_id'], $arr_obj_event_hashes ) ) {
			$str_hash = verowa_event_generate_hash(
				$arr_event[ $single_event['event_id'] ],
				$arr_ids,
				$has_subscription,
				wp_json_encode( $arr_event[ $single_event['event_id'] ] )
			);

			// If the hashes do not match, there are changes and we update the DB.
			if ( $arr_obj_event_hashes[ $single_event['event_id'] ]->hash !== $str_hash ) {
				verowa_event_db_update(
					$arr_event[ $single_event['event_id'] ],
					$has_subscription,
					$str_hash,
					$arr_ids
				);
			} else {
				// check if verowa_event has no related post and add it if missing.
				if ( true === verowa_wpml_is_configured() ){
					verowa_event_wpml_add_wp_post(
						$single_event['event_id'],
						$arr_event[ $single_event['event_id'] ]
					);
				} else {
					verowa_event_add_wp_post(
						$single_event['event_id'],
						$arr_event[ $single_event['event_id'] ]
					);
				}

				$query = 'UPDATE `' . $this->str_events_tablename . '` SET `deprecated` = 0 ' .
					'WHERE `event_id` = ' . $single_event['event_id'] . ';';
				$wpdb->query( $query );
			}
		} else {
			verowa_event_db_insert(
				$arr_event[ $single_event['event_id'] ],
				$has_subscription,
				$arr_ids
			);
		}
	}




	/**
	 * Updated only the mapping between the events and lists.
	 */
	public function update_verowa_event_list_mapping() {
		global $wpdb;

		foreach ( $this->arr_list_ids_for_event as $key => $single_mapping ) {
			$wpdb->update(
				$this->str_events_tablename,
				array(
					'list_ids' => ';' . str_replace( ',', ';', $single_mapping ) . ';',
				),
				array( 'event_id' => $key )
			);
		}

		do_action(
			'verowa_purge_shortcode_cache',
			array(
				'verowa_agenda',
				'verowa_event_liste_dynamic',
				'verowa_event_filter',
				'verowa_event_list',
				'verowa_event_liste',
			)
		);
	}




	/**
	 * If a person is related to an event and is not included in the DB, they are added.
	 *
	 * @param array $arr_single_event Event data for inserting people that only relate to the event.
	 * @param mixed $arr_person_ids IDs from persons web_visibility = "FULL".
	 * @param mixed $arr_person_hash Hashed from the Person in the DB.
	 */
	public function add_person_if_related_to_event( $arr_single_event, $arr_person_ids, $arr_person_hash ) {
		global $wpdb;

		for ( $i = 1; $i <= 8; $i++ ) {
			if ( key_exists( 'service' . $i, $arr_single_event ) && count( $arr_single_event[ 'service' . $i ] ) ) {
				foreach ( $arr_single_event[ 'service' . $i ] as $arr_single_person ) {
					// Only insert or update, if the persons web_visibility is "EVENTS".
					if ( false === array_search( intval( $arr_single_person['person_id'] ), $arr_person_ids ) ) {
						$str_hash = verowa_person_generate_hash( $arr_single_person['person_id'], $arr_single_person );
						if ( true === key_exists( $arr_single_person['person_id'], $arr_person_hash ) ) {
							// not check hash, update of deprecated is anyway necessary.
							verowa_person_db_update_without_post( $arr_single_person, 'EVENTS', $str_hash );
						} else {
							verowa_person_db_insert_without_post( $arr_single_person, 'EVENTS', $str_hash );
						}
					}
				}
			}
		}
	}




	/**
	 * Persons are updated via the Verowa API.
	 */
	public function update_verowa_persons_in_db( $int_person_id = 0 )
	{
		global $wpdb;
		$arr_ret_api_call = verowa_api_call ('getpersonsbyid', $int_person_id, true);
		$arr_all_persons = $arr_ret_api_call['data'];

		$bool_without_detail_page = 'on' === get_option ('verowa_persons_without_detail_page', false) ?
			true : false;

		if (is_array ($arr_all_persons) &&
			(200 === $arr_ret_api_call['code'] || 204 === $arr_ret_api_call['code'])) {
			if (count( $arr_all_persons ) > 0) {
				$arr_data = $this->get_person_data ($arr_all_persons);
				$arr_persons_hash = $arr_data['persons_hash'];
				$arr_persons = $arr_data['person_data'];
				$person_ids_in_db = $arr_data['person_ids_in_db'];
				foreach ($arr_all_persons as $single_person)
				{
					$arr_person_data = key_exists( $single_person['person_id'], $arr_persons ) ?
						$arr_persons[$single_person['person_id']] : array();
					if (count ($arr_person_data) > 0)
					{
						// merge the two info-arrays together.
						$arr_all_person_data = array_merge ($single_person, $arr_person_data);
					}
					else
					{
						$arr_all_person_data = $single_person;
					}

					// Set the image displayed in the list.
					$arr_all_person_data['images'] = $arr_person_data['images'] ?? array();
					$arr_all_person_data['web_visibility'] = 'FULL';
					$this->update_or_insert_single_verowa_person ($arr_all_person_data, $person_ids_in_db, $arr_persons_hash);
				}
				// All deprecated person are deleted.
				verowa_general_delete_deprecated ($wpdb->prefix . 'verowa_person');
				$this->update_person_groups ();
			}
			$this->release_persons_after_update ($bool_without_detail_page, VEROWA_DEBUG && 0 === count ($arr_all_persons));
		}
	}




	private function get_person_data( & $arr_all_persons ) {
		global $wpdb;
		$obj_person_ids_in_db = $wpdb->get_results(
			'SELECT GROUP_CONCAT(person_id SEPARATOR ",") AS `person_ids`' .
			' FROM `' . $wpdb->prefix . 'verowa_person`;'
		);
		$arr_person_ids_in_db = explode( ',', $obj_person_ids_in_db[0]->person_ids );

		// We note down all person_ids to compare them with those in the DB afterwards.
		$arr_person_ids_from_verowa = array_column( $arr_all_persons, 'person_id' );
		$arr_ret_api_call           = verowa_get_persondetails( $arr_person_ids_from_verowa );
		$all_required_person_data   = $arr_ret_api_call['data'];

		verowa_persons_set_deprecated();

		$query            = 'SELECT `person_id`, `hash`, `deprecated` FROM `' . $wpdb->prefix . 'verowa_person`' .
					' WHERE `person_id` IN (' . implode( ', ', $arr_person_ids_from_verowa ) . ');';
		$arr_persons_hash = $wpdb->get_results( $query, OBJECT_K );

		return array(
			'persons_hash' => $arr_persons_hash,
			'person_data' => $all_required_person_data,
			'person_ids_in_db' => $arr_person_ids_in_db
		);
	}




	/**
	 * A person is updated or inserted.
	 * If the function is called up, the personal data has always changed.
	 *
	 * 
	 */
		public function update_or_insert_single_verowa_person( & $arr_all_person_data, & $arr_person_ids_in_db, & $arr_persons_hash ) {
			global $wpdb;
			$bool_without_detail_page = 'on' === get_option( 'verowa_persons_without_detail_page', false ) ?
				true : false;
			
			$str_person_hash = verowa_person_generate_hash( $arr_all_person_data['person_id'], $arr_all_person_data );
			// If this person already exists we update it.
			if ( in_array( $arr_all_person_data['person_id'], $arr_person_ids_in_db ) ) {
				$is_deprecated = filter_var( $arr_persons_hash[ $arr_all_person_data['person_id'] ]->deprecated ?? false, FILTER_VALIDATE_BOOLEAN );
				// deprecated entries will also be updated until the new update function is available.
				if ( $arr_persons_hash[ $arr_all_person_data['person_id'] ]->hash !== $str_person_hash
					|| true === $is_deprecated ) {
					verowa_person_db_update( $arr_all_person_data, 'FULL', $str_person_hash );
				} elseif ( false === $bool_without_detail_page ) {
					verowa_person_add_wp_post( $arr_all_person_data['person_id'], $arr_all_person_data );
					$wpdb->update(
						$wpdb->prefix . 'verowa_person',
						array(
							'deprecated' => 0,
						),
						array(
							'person_id' => $arr_all_person_data['person_id'],
						),
						array( '%d' ),
						array( '%d' ),
					);
				} else {
					// prevent delete post.
					verowa_person_add_wp_post( $arr_all_person_data['person_id'], $arr_all_person_data );
					$wpdb->update(
						$wpdb->prefix . 'verowa_person',
						array(
							'deprecated' => 0,
						),
						array(
							'person_id' => $arr_all_person_data['person_id'],
						),
						array( '%d' ),
						array( '%d' )
					);
				}
			} else {
				verowa_person_db_insert( $arr_all_person_data, 'FULL', $str_person_hash );
			}
		}

		


		private function release_persons_after_update( $bool_without_detail_page, $bool_send_error_mail ){
			if ( $bool_send_error_mail ) {
				$str_mail  = 'Es wurden keine Personen von der API übertragen<br />';
				$str_mail .= get_site_url();
				verowa_send_mail( VEROWA_REPORTING_MAIL, 'WP Update cron', $str_mail );
			}

			if ( true === $bool_without_detail_page ) {
				// flush persons posts in DB.
				verowa_person_db_flush_posts();
			}

			do_action(
				'verowa_purge_shortcode_cache',
				array(
					'verowa_person',
					'verowa_personen',
					'verowa_event_list',
				)
			);
		}




	private function update_person_groups() {
		global $wpdb;
		$arr_persongroup_ids_from_verowa = array();
		$arr_deprecated_persongroup_ids  = array();

		$arr_ret_api_call = verowa_api_call( 'getpersongroups', '' );
		$arr_groups       = $arr_ret_api_call['data'] ?? array();
		$arr_group_ids    = array_column( $arr_groups, 'group_id' );

		$arr_ret_persons_by_groups =
			verowa_api_call( 'getpersonsbygroups', implode( ',', $arr_group_ids ), true ) ?? array();
		if ( ( 200 === $arr_ret_api_call['code'] || 204 === $arr_ret_api_call['code'] ) &&
			( 200 === $arr_ret_persons_by_groups['code'] || 204 === $arr_ret_persons_by_groups['code'] ) ) {
			$arr_persons_by_groups      = $arr_ret_persons_by_groups['data'];
			$obj_persongroups_ids_in_db = $wpdb->get_results(
				'SELECT GROUP_CONCAT(pgroup_id SEPARATOR ",") AS `pgroup_ids`' .
				' FROM `' . $wpdb->prefix . 'verowa_person_groups`;'
			);

			$arr_persongroups_ids_in_db = explode( ',', $obj_persongroups_ids_in_db[0]->pgroup_ids );

			foreach ( $arr_groups as $single_group ) {
				$arr_group_members           = $arr_persons_by_groups[ $single_group['group_id'] ];
				$arr_person_ids              = array();
				$arr_members_group_functions = array();

				// We note down all group_ids to compare them with the ones in the DB.
				$arr_persongroup_ids_from_verowa[] = $single_group['group_id'];

				foreach ( $arr_group_members as $single_member ) {
					$arr_person_ids[] = $single_member['person_id'];

					// We also store the group function for each member in an array. [person_id] => group_function.
					if ( strlen( $single_member['function_in_group'] ) > 0 ) {
						$arr_members_group_functions[ $single_member['person_id'] ] = $single_member['function_in_group'];
					}
				}

				$str_person_ids = ';' . implode( ';', $arr_person_ids ) . ';';
				$arr_content    = array(
					'name'          => $single_group['name'] ?? '',
					'public_target' => $single_group['public_target'] ?? '',
					'short_name'    => $single_group['short_name'] ?? '',
				);
				$str_content    = wp_json_encode( $arr_content ) . 'pid=' . ( $single_group['parent_id'] ?? 0 );

				// Hash everything. With the hash you can easily check if something has been changed.
				$str_person_group_hash = verowa_person_group_generate_hash(
					$single_group['group_id'],
					$str_person_ids,
					wp_json_encode( $arr_members_group_functions ),
					$str_content
				);

				if ( in_array( $single_group['group_id'], $arr_persongroups_ids_in_db ) ) {
					$get_persongroup_hash = 'SELECT `hash` FROM `' . $wpdb->prefix . 'verowa_person_groups`' .
						' WHERE `pgroup_id` = ' . $single_group['group_id'] . ';';
					$arr_persongroup_hash = $wpdb->get_results( $get_persongroup_hash, ARRAY_A );

					// We only update the db if something changed (if the hashes are not the same anymore).
					if ( $arr_persongroup_hash[0]['hash'] !== $str_person_group_hash ) {
						verowa_person_group_db_update(
							$single_group,
							$str_person_group_hash,
							$str_person_ids,
							wp_json_encode( $arr_members_group_functions )
						);
					}
				} else {
					verowa_person_group_db_insert(
						$single_group,
						$str_person_ids,
						wp_json_encode( $arr_members_group_functions )
					);
				}
			}

			// All person groups that exist in the DB but not in the new call are set to deprecated.
			$arr_deprecated_persongroup_ids = array_diff(
				$arr_persongroups_ids_in_db,
				$arr_persongroup_ids_from_verowa
			);

			if ( count( $arr_deprecated_persongroup_ids ) > 0 ) {
				foreach ( $arr_deprecated_persongroup_ids as $single_pgroup_id ) {
					verowa_person_group_set_deprecated( intval( $single_pgroup_id ) );
				}
			}
			verowa_person_groups_delete_deprecated();
		}
	}




	/**
	 * Update the roster duty from Verowa.
	 *
	 * @return void
	 */
	public function update_roster_duty() {
		global $wpdb;

		// get records in DB and assemble keys.
		$arr_roster_duty = verowa_roster_duty_db_find( 0 );
		$arr_duty_keys   = array();
		foreach ( $arr_roster_duty as $arr_single_duty ) {
			$arr_duty_keys[] = $arr_single_duty['roster_id'] . '_' . $arr_single_duty['datetime_from'] . '_' . $arr_single_duty['datetime_to'];
		}

		// set all deprecated.
		$wpdb->update(
			$wpdb->prefix . 'verowa_roster_duties',
			array(
				'deprecated' => 1,
			),
			array(
				'deprecated' => 0,
			),
			array( '%d' ),
			array( '%d' )
		);

		// insert or update duty.
		$has_api_error  = false;
		$arr_roster_ids = json_decode( get_option( 'verowa_roster_ids', '[]' ), true );

		foreach ( $arr_roster_ids as $single_id ) {
			$arr_ret_api_call  = verowa_api_call( 'getrosterentries', $single_id, true );
			$arr_roster_duties = $arr_ret_api_call['data'];
			if ( is_array( $arr_roster_duties ) &&
				( 200 === $arr_ret_api_call['code'] || 204 === $arr_ret_api_call['code'] ) ) {
				foreach ( $arr_roster_duties as $arr_single_duty ) {
					$str_key = $single_id . '_' . $arr_single_duty['datetime_from'] . '_' . $arr_single_duty['datetime_to'];
					if ( in_array( $str_key, $arr_duty_keys ) ) {
						$str_hash = verowa_roster_duty_generate_hash( $single_id, $arr_single_duty );
						verowa_roster_duty_db_update( $single_id, $arr_single_duty, $str_hash );
					} else {
						verowa_roster_duty_db_insert( $single_id, $arr_single_duty );
					}
				}
			} else {
				// If an API error occurs, it is aborted.
				$has_api_error = true;
				break;
			}
		}

		// delete deprecated.
		if ( false === $has_api_error ) {
			$wpdb->delete(
				$wpdb->prefix . 'verowa_roster_duties',
				array(
					'deprecated' => 1,
				),
				array( '%d' ),
			);
		}

		do_action(
			'verowa_purge_shortcode_cache',
			array(
				'verowa_roster_entries',
				'verowa-first-roster-entry',
			)
		);
	}



	/**
	 * Checks the Verowa events and people
	 *
	 * @return void
	 */
	public function checks_after_update() {
		global $wpdb;
		$str_mail           = '';
		$last_update_checks = get_option( 'verowa_connect_last_update_checks', 0 );
		// only check one a day.
		$str_check_time = time() - ( 24 * 60 * 60 );
		if ( $last_update_checks < $str_check_time ) {
			$arr_counts = verowa_get_custom_posts_count();

			// Check whether there are the same.
			// number of posts with the type 'verowa_event' and records in the table "verowa_events".
			if ( $arr_counts['int_count_event_posts'] !== $arr_counts['int_verowa_events'] ) {
				$str_mail .= 'Anzahl Posts: ' . $arr_counts['int_count_event_posts'] . ' / ' .
					'Anzahl Records in verowa_events ' . $arr_counts['int_verowa_events'] . PHP_EOL;
			}

			// Check whether there are the same
			// number of posts with the type 'verowa_person' and records in the table "verowa_persons".
			$persons_without_detail_page = get_option( 'verowa_persons_without_detail_page', false );
			if ( false === $persons_without_detail_page &&
				$arr_counts['int_count_person_posts'] !== $arr_counts['int_verowa_persons'] ) {
				$str_mail .= 'Anzahl Posts: ' . $arr_counts['int_count_person_posts'] . ' / ' .
					'Anzahl Records in verowa_persons ' . $arr_counts['int_verowa_persons'] . PHP_EOL;
			}

			// Check two event pictures, if available.
			$int_to_check     = 2;
			$arr_test_records = $wpdb->get_results(
				'SELECT `content` FROM ' .
				'`' . $wpdb->prefix . 'verowa_events` ORDER BY `event_id` DESC  LIMIT 50;',
				ARRAY_A
			);

			if ( ! is_wp_error( $arr_test_records ) ) {
				foreach ( $arr_test_records as $single_arr_test ) {
					$arr_person    = json_decode( $single_arr_test['content'], true );
					$str_image_url = trim( $arr_person['image_url'] ?? '' );
					if ( $int_to_check > 0 && '' !== $str_image_url ) {
						$int_to_check--;
						$obj_response = wp_remote_get( $str_image_url );
						if ( is_wp_error( $obj_response ) ) {
							$int_code = 500;
						} else {
							$int_code = intval( $obj_response['response']['code'] ?? 404 );
						}

						if ( 200 !== $int_code ) {
							$str_mail = $int_code . ': ' . $str_image_url . PHP_EOL;
						}
					}
				}
			}

			// Check two person pictures, if available.
			$int_to_check     = 2;
			$arr_test_records = $wpdb->get_results(
				'SELECT `content` FROM ' .
				'`' . $wpdb->prefix . 'verowa_person` ORDER BY `person_id` DESC LIMIT 50;',
				ARRAY_A
			);

			if ( ! is_wp_error( $arr_test_records ) ) {
				foreach ( $arr_test_records as $single_arr_test ) {
					$arr_person = json_decode( $single_arr_test['content'], true );
					$arr_images = $arr_person['images'] ?? array();
					if ( $int_to_check > 0 && count( $arr_images ) > 0 ) {
						reset( $arr_images );
						$str_url = trim( current( $arr_images )['url'] ?? '' );
						if ( '' !== $str_url ) {
							$int_to_check--;
							$obj_response = wp_remote_get( $str_url );
							$int_code = is_wp_error( $obj_response ) ? 500 : intval( $obj_response['response']['code'] ?? 404 );

							if ( 200 !== $int_code ) {
								$str_mail = $int_code . ': ' . $str_image_url . PHP_EOL;
							}
						}
					}
				}
			}

			if ( '' !== $str_mail ) {
				$str_mail = get_option( 'siteurl', '' ) . PHP_EOL . $str_mail;
				// TODO: Umschreiben auf verowa_send_mail
				mail( VEROWA_REPORTING_MAIL, 'Test nach Update', $str_mail );
			}

			// if there are deprecated person groups left, we remove the flag.
			$wpdb->update(
				$wpdb->prefix . 'verowa_person_groups',
				array(
					'deprecated' => 0,
				),
				array(
					'deprecated' => 1,
				),
				array( '%d' ),
				array( '%d' )
			);

			$query = 'DELETE FROM `' . $wpdb->posts . '` ' .
				'WHERE `post_type` = "verowa_event" AND ID NOT IN (' .
				'SELECT `post_id` From `' . $wpdb->prefix . 'verowa_events`)';
			$wpdb->query( $query );

			$query = 'DELETE FROM `' . $wpdb->posts . '` ' .
				'WHERE `post_type` = "verowa_person" AND ID NOT IN (' .
				'SELECT `post_id` From `' . $wpdb->prefix . 'verowa_person`)';

			$wpdb->query( $query );

			update_option( 'verowa_connect_last_update_checks', time() );
		}
	}

	/**
	 * Do not use for validation purposes.
	 */
	// public function __destruct() {}
}




/**
 *  If the version in the DB is not the same as in the code the DB will be updated.
 */
function verowa_check_db_version() {
	global $wpdb;

	$curr_db_version = get_option( 'verowa_connect_db_version' );
	if ( VEROWA_CONNECT_DB_VERSION !== $curr_db_version ) {
		// Ensures that the required tables are present.
		verowa_update_wp_database();

		// If no version number is yet stored in the DB, it is set to zero.
		$int_db_version = false === $curr_db_version ? 0 : intval( $curr_db_version );
		switch ( $int_db_version ) {

			case 0: // VC Version 2.8.11.
				add_option( 'verowa_persons_without_detail_page', get_option( 'verowa_person_has_detail_page' ) );
				delete_option( 'verowa_person_has_detail_page' );

				// Update postmeta
				// verowa_person_has_detail_page => verowa_persons_have_detail_link.
				$query        = 'SELECT * FROM `' . $wpdb->prefix . 'postmeta` WHERE `meta_key` = "verowa_person_has_detail_page";';
				$arr_postmeta = $wpdb->get_results( $query, ARRAY_A );
				foreach ( $arr_postmeta as $arr_single_postmeta ) {
					$persons_have_detail_link = 'on' === $arr_single_postmeta['meta_value'] ? '' : 'on';
					add_post_meta(
						$arr_single_postmeta['post_id'],
						'verowa_persons_have_detail_link',
						$persons_have_detail_link,
						true
					);
				}

				// no break, so that the DB runs through all required updates.
			case 1: // VC Version 2.9.0.
				add_option( 'verowa_show_full_text_search', true );

				// Earlier connect versions set all person groups to deprecate.
				// Set deprecated to 0 as it correctEarlier connect versions set all person groups to deprecate.
				// Set deprecated to 0 as it correct.
				$wpdb->update(
					$wpdb->prefix . 'verowa_person_groups',
					array(
						'deprecated' => 0,
					),
					array(
						'deprecated' => 1,
					),
					array( '%d' ),
					array( '%d' )
				);

				// no break, so that the DB get all required updates.
			case 2: // VC Version 2.10.0.
				global $wpdb;

				$wp_post = get_page_by_path( 'contact-tracing-response' );
				if ( null !== $wp_post ) {
					$wp_post->post_name    = 'verowa-subscription-confirmation';
					$wp_post->post_content = str_replace( 'verowa_subscriptions_response', 'verowa_subscription_confirmation', $wp_post->post_content );
					wp_update_post( $wp_post, true, false );
				}

				$wp_post = get_page_by_path( 'subscription-form' );
				if ( null !== $wp_post ) {
					$wp_post->post_content = str_replace( 'verowa_print_subscriptions_form', 'verowa_subscription_form', $wp_post->post_content );
					wp_update_post( $wp_post, true, false );
				}

				$wp_post = get_page_by_path( 'anmeldung-validieren' );
				if ( null !== $wp_post ) {
					$wp_post->post_content = str_replace( 'verowa_subscriptions_validation_anmeldung', 'verowa_subscription_validation', $wp_post->post_content );
					wp_update_post( $wp_post, true, false );
				}

				$query = 'UPDATE `' . $wpdb->posts . '` SET `post_content` = REPLACE( `post_content`, "verowa_subscriptions_form", "verowa_subscription_form" ) ' .
					'WHERE `post_content` LIKE "%verowa_subscriptions_form%";';
				$wpdb->query( $query );

				wp_clear_scheduled_hook( 'sync_verowa_data_from_api' );
				if ( ! wp_next_scheduled( 'verowa_connect_importer' ) ) {
					verowa_schedule_importer();
				}

				verowa_roster_add_duty_templates();
				// no break, so that the DB get all required updates.

			case 3: // VC 2.11.0.
				$query = 'UPDATE `' . $wpdb->posts . '` SET ' .
					'`post_content` = REPLACE( `post_content`, "verowa_event_liste_dynamic", "verowa_event_list" ) ' .
					'WHERE `post_content` LIKE "%verowa_event_liste_dynamic%";';
				$wpdb->query( $query );

				$query = 'UPDATE `' . $wpdb->posts . '` SET ' .
					'`post_content` = REPLACE( `post_content`, "verowa_dynamic_agenda", "verowa_agenda" ) ' .
					'WHERE `post_content` LIKE "%verowa_dynamic_agenda%";';
				$wpdb->query( $query );
				// no break, so that the DB get all required updates.

			case 4:
				// VC 2.11.3 (empty).
				// no break, so that the DB get all required updates.

			case 5: // VC 2.11.4.
				$wpdb->query( 'UPDATE `' . $wpdb->prefix . 'verowa_person_groups` SET `hash` = \'\' WHERE `pgroup_id` > 0;' );
				// no break, so that the DB get all required updates.

			case 6: // VC 2.12.1.
				delete_option( 'verowa_dynamic_agenda_events' );
				delete_option( 'verowa_dynamic_agenda_filter_array' );

				$filter = get_option( 'verowa_dynamic_agenda_filter', array() );
				add_option( 'verowa_agenda_filter', $filter );

				delete_option( 'verowa_dynamic_agenda_filter' );

				// set new time stamp for next run.
				wp_clear_scheduled_hook( 'verowa_connect_importer' );
				if ( ! wp_next_scheduled( 'verowa_connect_importer' ) ) {
					verowa_schedule_importer();
				}
				// no break, so that the DB get all required updates.

			case 7: // VC 2.12.0
				$wpdb->query( 'UPDATE `' . $wpdb->prefix . 'verowa_events` SET `hash` = \'\' WHERE `event_id` > 0;' );
				$str_query   = 'SELECT table_schema, table_name, index_name, column_name ' .
					'FROM information_schema.statistics ' .
					'WHERE table_schema = "' . $wpdb->dbname . '" ' .
					'AND table_name = "' . $wpdb->prefix . 'verowa_events" AND index_name = "search_content"';
				$arr_indexes = $wpdb->get_results( $str_query );
				if ( 0 === count( $arr_indexes ) ) {
					$wpdb->query( 'ALTER TABLE `' . $wpdb->prefix . 'verowa_events` ADD FULLTEXT KEY `search_content` (`search_content`);' );
				}
				verowa_connect_importer_handler();

			case 8: // VC 3.0.0 
				$wpdb->query( 'UPDATE `' . $wpdb->prefix . 'verowa_templates` SET `entry` = REPLACE(`entry`, "{CATERING}", "mit {CATERING}") 
					WHERE `type` = "eventdetails" AND `entry` like "%{CATERING}%" AND `entry` NOT LIKE "%' . __( 'with', 'verowa-connect' ) . ' {CATERING}%";' );
				// no break, so that the DB get all required updates.

			case 9: // VC 3.0.1
				require_once dirname( dirname( __FILE__ ) ) . '/admin/class-verowa-templates-list.php';

				$arr_template_updates = array(
						'eventlist' => array(
							'header' => '<div class="event_list_wrapper">', // über Header
							'footer' => '</div>', // unter footer
						),
						'personlist' => array(
							'header' => '<div class="person-container single-persons">',
							'footer' => '</div>',
						),
						'roster' => array(
							'header' => '<div class="verowa-roster-entries">',
							'footer' => '</div>',
						),
					);

				if ( true === verowa_wpml_is_configured() ) {
					// Reading the template IDs from the DB
					$arr_lang_templates = Verowa_Templates_List::get_templates_group_by_default();
					$arr_template_types = $wpdb->get_results( 'SELECT `template_id`, `type` ' .
						'FROM `' . $wpdb->prefix . 'verowa_templates`;', OBJECT_K );
					$arr_ids_to_update = [];

					// For the update, group the template IDs in an array according to template type
					foreach ( $arr_lang_templates as $i_template_id => $arr_lang_templates ) {
						$str_type = $arr_template_types[$i_template_id]->type;
						if ( 'eventlist' === $str_type || 'personlist' === $str_type || 'roster' === $str_type ) {
							$arr_ids_to_update[$str_type] = array_merge( $arr_ids_to_update[ $str_type ] ?? [], 
								[ $i_template_id ], $arr_lang_templates ?? []);
						}
					}

					foreach ( $arr_template_updates as $str_template_type => $arr_single_template ) {
						$str_query = 'UPDATE `' . $wpdb->prefix . 'verowa_templates` ' . 
							'SET `header` = CONCAT("' . addslashes( $arr_single_template['header'] ) . '\n", `header`), ' .
							'`footer` = CONCAT(`footer`, "' . addslashes( $arr_single_template['footer'] ) . '\n") ' .
							'WHERE `template_id` IN (' . implode( ', ', $arr_ids_to_update[$str_template_type] ?? [] ) . ');';
						$wpdb->query( $str_query );
					}

				} else {
					foreach ( $arr_template_updates as $str_template_type => $arr_single_template ) {
						$str_query = 'UPDATE `' . $wpdb->prefix . 'verowa_templates` ' . 
							'SET `header` = CONCAT("' . addslashes( $arr_single_template['header'] ) . '\n", `header`), ' .
							'`footer` = CONCAT(`footer`, "' . addslashes( $arr_single_template['footer'] ) . '\n") ' .
							'WHERE `type` = "' . $str_template_type. '";';
						$wpdb->query( $str_query );
					}
				}

				// no break, so that the DB get all required updates.
		}
	}

	verowa_cache_exclude_uris();
}

add_action( 'plugins_loaded', 'verowa_check_db_version' );
