<?php
/**
 * Project:         VEROWA CONNECT
 * File:            general/class-verowa-backend-settings.php
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since 3.0.0
 * @package Verowa Connect
 * @subpackage  Backend
 */
class Verowa_Backend_Settings {
	// Default templates.
	private $arr_option_mapping = array(
		'verowa_default_personlist_template'    => 'verowa_select_template_personlist',
		'verowa_default_persondetails_template' => 'verowa_select_template_persondetails',
		'verowa_default_eventlist_template'     => 'verowa_select_template_eventlist',
		'verowa_default_eventdetails_template'  => 'verowa_select_template_eventdetails',
		'verowa_default_rosterlist_template'    => 'verowa_select_template_rosterlist',
		'verowa_default_firstroster_template'   => 'verowa_select_template_firstroster',
	);

	private $arr_template_dropdown_args = array(
		'show_empty_option' => true,
	);

	public $str_tab;
	public $arr_module_infos;

	public function __construct( $str_tab ) {
		$this->str_tab          = $str_tab;
	}

	public function load_verowa_module_infos() {
		$this->arr_module_infos = verowa_get_module_infos();
	}

	public function render_navigation() {

		$arr_nav = array(
			array( 
				'text'      => esc_html( __( 'General', 'verowa-connect' ) ),
				'slug'      => '',
				'css_class' => (null === $this->str_tab ? 'nav-tab tab-active' : 'nav-tab'),
			),
			array( 
				'text'      => esc_html( __( 'Events', 'verowa-connect' ) ),
				'slug'      => '&tab=events',
				'css_class' => ('events' === $this->str_tab ? 'nav-tab tab-active' : 'nav-tab'),
			),
			array( 
				'text' => esc_html( __( 'Persons', 'verowa-connect' ) ),
				'slug' => '&tab=persons',
				'css_class' => ('persons' === $this->str_tab ? 'nav-tab tab-active' : 'nav-tab'),
			),
			array( 
				'text' => esc_html( __( 'Rosters', 'verowa-connect' ) ),
				'slug' => '&tab=rosters',
				'css_class' => ('rosters' === $this->str_tab ? 'nav-tab tab-active' : 'nav-tab'),
			),
			array( 
				'text'      => esc_html( __( 'Agenda filter', 'verowa-connect' ) ),
				'slug'      => '&tab=agenda',
				'css_class' => ('agenda' === $this->str_tab ? 'nav-tab tab-active' : 'nav-tab'),
			),
			array( 
				'text' => esc_html( __( 'Texte', 'verowa-connect' ) ),
				'slug' => '&tab=vertexte',
				'css_class' => ('vertexte' === $this->str_tab ? 'nav-tab tab-active' : 'nav-tab'),
			),
		);

		if ( true === $this->arr_module_infos['postings']['enabled'] ) {
			$arr_nav[] = array(
				'text'      => 'News',
				'slug'      => '&tab=news',
				'css_class' => ('news' === $this->str_tab ? 'nav-tab tab-active' : 'nav-tab'),
			);
		}

		if ( true === verowa_wpml_is_configured() ) {
			$arr_nav[] = array(
				'text'      => 'WPML',
				'slug'      => '&tab=wpml',
				'css_class' => ('wpml' === $this->str_tab ? 'nav-tab tab-active' : 'nav-tab'),
			);
		}
		
		if ( true === $this->arr_module_infos['subscriptions']['enabled'] ) {
			$arr_nav[] = array(
				'text'      => 'Anmeldungen',
				'slug'      => '&tab=subs',
				'css_class' => ('subs' === $this->str_tab ? 'nav-tab tab-active' : 'nav-tab'),
			);
		}

		//$arr_nav[] = array(
		//	'text' => 'Tools',
		//	'slug' => '&tab=tools',
		//	'css_class' => ('tools' === $this->str_tab ? 'nav-tab tab-active' : 'nav-tab'),
		//);

		echo '<nav class="nav-tab-wrapper">';

		foreach ( $arr_nav as $obj_single_nav ) {
			echo ' <a href="?page=verowa-connect-settings' . $obj_single_nav['slug'] . '" ' .
				'class="' . $obj_single_nav['css_class'] . '">' . $obj_single_nav['text'] . '</a>';
		}

		echo '</nav>';
	}


	public function render_tab_content() {
		switch ( $this->str_tab ) {
			case 'agenda':
				$this->agenda_tab();
				break;

			case 'events':
				$this->event_tab();
				break;

			case 'persons':
				$this->person_tab();
				break;

			case 'rosters':
				$this->roster_tab();
				break;

			case 'vertexte':
				$this->vertexte_tab();
				break;

			case 'wpml':
				$this->wpml_tab();
				break;

			case 'news':
				$this->news_tab();
				break;

			case 'subs':
				$this->subscriptions_tab();
				break;

			case 'tools':
				$this->tool_tab();
				break;

			default:
				$this->general_tab ();
				break;
		}
	}


	private function general_tab() {
		// Option block "General".
		$instance = get_option( 'verowa_instance', true );
		$api_key  = get_option( 'verowa_api_key', true );

		$str_content = '<p><table>' .
			'<tr><td>' . esc_html( __ ( 'Parish ID', 'verowa-connect' ) ) . '</td>' .
			'<td><input type="text" name="verowa_instance" style="width:400px;" value="' . esc_attr ($instance) .
			'" /></td></tr>' .
			'<tr><td>' . esc_html( __ ( 'API key', 'verowa-connect' ) ) . '</td>' .
			'<td><input type="text" name="verowa_api_key" style="width:400px;" value="' . esc_attr ($api_key) .
			'" /></td></tr>' .
			'</table></p>';

		$this->add_html_form ($str_content);
	}


	private function event_tab() {
		$bool_events_exclude_from_search_engines = get_option( 'verowa_events_exclude_from_search_engines', false );
		$int_eventlist_default_template     = get_option( 'verowa_default_eventlist_template', 0 );
		$int_eventdetails_default_template  = get_option( 'verowa_default_eventdetails_template', 0 );

		// $str_content = '<div class="verowa-setting-section" >';

		$str_helptext = verowa_get_info_rollover(
			__(
				'The events won’t show in lists and searches, but their details pages remain accessible directly via link, e.g. from newsletters or flyers.',
				'verowa-connect'
			)
		);

		$int_keep_days = intval( get_option( 'verowa_keep_outdated_events_days', 14 ) );
		$str_content = '<p class="verowa-keep-outdated-events" >' .
			esc_html( __( 'Number of days to keep outdated events', 'verowa-connect' ) ) .
			' <input name="verowa_keep_outdated_events_days" type="number" value="' . esc_attr( $int_keep_days ) . '" ' .
			'min="0" max="400" step="0.1" size="3" /> ' .
			wp_kses(
				$str_helptext,
				array(
					'i' => array(
						'title' => array(),
						'class' => array(),
					),
				)
			) . '</p>';

		// Display the drop-down for the default person list template.
		$str_content .= '<b>' . esc_html( __( 'Default list template', 'verowa-connect' ) ) . '</b><br />';
		$arr_ddl = verowa_show_template_dropdown(
			'eventlist',
			$int_eventlist_default_template,
			$this->arr_template_dropdown_args
		);

		$str_content .= wp_kses( $arr_ddl['content'], $arr_ddl['kses_allowed'] );
		$str_content .= '<br /><br />';

		// Display the drop-down for the default person list template.
		$str_content .= '<b>' . esc_html( __( 'Default detail template', 'verowa-connect' ) ) . '</b><br />';
		$arr_ddl = verowa_show_template_dropdown(
			'eventdetails',
			$int_eventdetails_default_template,
			$this->arr_template_dropdown_args
		);

		$str_content .= wp_kses( $arr_ddl['content'], $arr_ddl['kses_allowed'] );

		$arr_cb = verowa_checkbox_html(
			array(
				'name'     => 'verowa_events_exclude_from_search_engines',
				'text'     => __( 'Exclude from search engines', 'verowa-connect' ),
				'helptext' => __(
					'Search engines like Google may not index the Verowa events',
					'verowa-connect'
				),
				'value'    => $bool_events_exclude_from_search_engines,
			)
		);

		$str_content .= '<p>' . wp_kses( $arr_cb['content'], $arr_cb['kses_allowed'] ) . '</p>';
		$this->add_html_form ($str_content);
	}


	private function agenda_tab() {
		// Get the data again cleanly from the DB (for safety).
		$how_many_verowa_dropdowns = get_option( 'how_many_verowa_dropdowns', true );
		$wp_verowa_listengruppe    = get_option( 'verowa_wordpress_listengruppe', true );
		$show_full_text_search     = get_option( 'verowa_show_full_text_search', true );

		// Agenda filters in the corresponding languages.
		$str_content = '<div class="tbl-verowa-ddls" ><div>' .
			'<label>' . esc_html( __( 'Number of dropdown menus', 'verowa-connect' ) ) . ':</label> ' .
			'<input type="number" name="how_many_verowa_dropdowns" value="' . esc_attr( $how_many_verowa_dropdowns ) . '" />' .
			'</div><div>' .
			'<label>' . esc_html( __( 'ID of group with WordPress lists', 'verowa-connect' ) ) . ':</label> ' .
			'<input type="number" name="verowa_wp_listengruppen_id" value="' . esc_attr( $wp_verowa_listengruppe ) . '" /></div>' .
			'<input type="hidden" name="verowa_show_full_text_search" value="off" />';

		$arr_cb = verowa_checkbox_html(
			array(
				'name' => 'verowa_show_full_text_search',
				'text' => __( 'Agenda: enable full text search', 'verowa-connect' ),
				'value' => ( $show_full_text_search ? 'on' : '' ),
			)
		);

		$str_content .= wp_kses( $arr_cb['content'], $arr_cb['kses_allowed'] ) .
			'<input type="submit" value="' . esc_attr( __( 'Save Options', 'verowa-connect' ) ) . '" class="button-primary" style="margin-top: 15px; margin-bottom: 15px;" >' .
			'</div><div class="verowa-filter-group">';

		$arr_listen_in_gruppe = json_decode( get_option( 'verowa_agenda_filter', '[]' ), true );
		$int_anzahl_zaehler   = 1;
		$float_max_width = 100 / $how_many_verowa_dropdowns;
		$str_ml_dropdowns_titel = get_option ('verowa_agenda_ml_dropdowns_titel', '');
		$arr_ml_dropdowns_titel = $str_ml_dropdowns_titel != '' ? json_decode ($str_ml_dropdowns_titel, true) : array();

		while ( $int_anzahl_zaehler <= $how_many_verowa_dropdowns ) {
			$str_content .= '<div class="verowa-single-filter" style="display:inline-block; max-width:' . $float_max_width . '%;">' .
				'<b>' . esc_html (__ ('Dropdown menu', 'verowa-connect')) . ' ' . esc_attr ($int_anzahl_zaehler) . '</b> <br />';
		
			if( true === verowa_wpml_is_configured() ) {
				$arr_languages = verowa_wpml_get_active_languages();

				foreach ($arr_languages as $arr_single_language) {
					$str_content .= '<div style="margin-bottom:15px;"><span style="width:75px;display: inline-block;">' . esc_html (__ ('Title', 'verowa-connect')) . ' ' . strtoupper($arr_single_language['code']) . ': </span><input type="text" name="dropdown_menu_' .
						esc_attr ($int_anzahl_zaehler) .
						'_title_' . $arr_single_language['code'] . '" ' . 
						'value="' . esc_attr ($arr_ml_dropdowns_titel[ $int_anzahl_zaehler ][ $arr_single_language['code'] ] ?? $int_anzahl_zaehler) . '" />' .
						'</div>';
				}

			} else {
				$str_content .= esc_html (__ ('Title', 'verowa-connect')) . ': <input type="text" name="dropdown_menu_' .
					esc_attr ($int_anzahl_zaehler) .
					'_title" value="' . esc_attr (get_option ('verowa_dropdown_' . $int_anzahl_zaehler . '_title', true)) . '" />' .
					'<br />';
			}

			$str_content .= '<ul class="verowa_agenda_dropdown_sort">';

			$str_inhalt_aktuelle_liste  = get_option( 'verowa_dropdown_' . $int_anzahl_zaehler, true );
			$arr_inhalt_aktuelle_listen = array_unique( explode( ', ', $str_inhalt_aktuelle_liste ) );

			foreach ( $arr_inhalt_aktuelle_listen as $group_id ) {
				if ( is_array( $arr_listen_in_gruppe ) ) {

					$group_key = array_search( $group_id, array_column( $arr_listen_in_gruppe, 'list_id' ) );

					$str_content .= '<li><span class="dashicons dashicons-move"></span><input type="checkbox" name="dropdown_menu_' .
					esc_html( $int_anzahl_zaehler ) . '[]" checked=checked value="' .
					esc_html( $arr_listen_in_gruppe[ $group_key ]['list_id'] ) . '" />' .
					esc_html( $arr_listen_in_gruppe[ $group_key ]['name'] ) . ' <small>(' . esc_html( __( 'ID', 'verowa-connect' ) ) . ': ' .
					esc_html( $arr_listen_in_gruppe[ $group_key ]['list_id'] ) . ')</small></li>';
				}
			}

			if ( is_array( $arr_listen_in_gruppe ) ) {
				foreach ( $arr_listen_in_gruppe as $arr_einzelne_liste ) {
					// Set checkbox if list is present in array.
					if ( ! in_array( strval( $arr_einzelne_liste['list_id'] ), $arr_inhalt_aktuelle_listen ) ) {
						$str_content .= '<li><span class="dashicons dashicons-move"></span>' .
							'<input type="checkbox" name="dropdown_menu_' . esc_attr( $int_anzahl_zaehler ) . '[]" ' .
							' value="' . esc_attr( $arr_einzelne_liste['list_id'] ) . '" />' .
							esc_html( $arr_einzelne_liste['name'] ) .
							' <small>(' . esc_html( __( 'ID', 'verowa-connect' ) ) . ': ' .
							esc_html( $arr_einzelne_liste['list_id'] ) . ')</small></li>';
					}
				}
			}

			$str_content .=  '</li></div>';
			$int_anzahl_zaehler++;
		} // while ( $int_anzahl_zaehler <= $how_many_verowa_dropdowns )

		$str_content .= '</div><script>jQuery( function() {
			jQuery( ".verowa_agenda_dropdown_sort" ).sortable();
			jQuery( ".verowa_agenda_dropdown_sort" ).disableSelection();
			} );
			</script>';
		$this->add_html_form ($str_content);
	}


	private function person_tab() {
		$persons_without_detail_page              = get_option( 'verowa_persons_without_detail_page', false );
		$bool_persons_exclude_from_search_engines = get_option( 'verowa_persons_exclude_from_search_engines', false );
		$int_personlist_default_template    = get_option( 'verowa_default_personlist_template', 0 );
		$int_persondetails_default_template = get_option( 'verowa_default_persondetails_template', 0 );

		// Display the drop-down for the default person list Template.
		$str_content = '<p style="margin-top: 0;"><b>' . esc_html( __( 'Default list template', 'verowa-connect' ) ) . '</b><br />';
		$arr_ddl = verowa_show_template_dropdown( 'personlist', $int_personlist_default_template, $this->arr_template_dropdown_args );
		$str_content .= wp_kses( $arr_ddl['content'], $arr_ddl['kses_allowed'] );
		$str_content .= '</p>';

		// Display the drop-down for the default person list Template.
		$str_content .= '<p><b>' . esc_html( __( 'Default detail template', 'verowa-connect' ) ) . '</b></br>';
		$arr_ddl = verowa_show_template_dropdown( 'persondetails', $int_persondetails_default_template, $this->arr_template_dropdown_args );
		$str_content .= wp_kses( $arr_ddl['content'], $arr_ddl['kses_allowed'] );
		$str_content .= '</p>';

		$persons_without_detail_page = get_option( 'verowa_persons_without_detail_page', false ) == true ? 'on' : '';

		$str_content .= '<b>' . esc_html( _x( 'Settings for detail pages', 'Persons', 'verowa-connect' ) ) . '</strong></b><br />';

		$arr_cb = verowa_checkbox_html(
			array(
				'name'  => 'verowa_persons_without_detail_page',
				'text'  => __(
					'WITHOUT detail pages (the persons are not displayed in the search result!)',
					'verowa-connect'
				),
				'value' => $persons_without_detail_page,
			)
		);
		$str_content .= wp_kses( $arr_cb['content'], $arr_cb['kses_allowed'] );

		$arr_cb = verowa_checkbox_html(
			array(
				'name'     => 'verowa_persons_exclude_from_search_engines',
				'text'     => esc_html( __( 'Exclude from search engines', 'verowa-connect' ) ),
				'helptext' => esc_html(
					__(
						'Search engines like Google will not index the Verona person details pages. If names are displayed on event pages, they can still be indexed there.',
						'verowa-connect'
					),
				),
				'value'    => $bool_persons_exclude_from_search_engines,
			)
		);
		$str_content .= '<p style="margin-top: 0;">' . wp_kses( $arr_cb['content'], $arr_cb['kses_allowed'] ) . '</p>';
		$this->add_html_form ($str_content);
	}

	private function roster_tab() {
		$int_rosterlist_default_template  = get_option( 'verowa_default_rosterlist_template', 0 );
		$int_firstroster_default_template = get_option( 'verowa_default_firstroster_template', 0 );

		$this->arr_template_dropdown_args['ddl_name'] = 'verowa_select_template_rosterlist';
		$str_content = '<p style="margin-top: 0;"><b>' . esc_html( __( 'Default list template', 'verowa-connect' ) ) . '</b></br>';
		$arr_ddl = verowa_show_template_dropdown( 'roster', $int_rosterlist_default_template, $this->arr_template_dropdown_args );
		$str_content .= wp_kses( $arr_ddl['content'], $arr_ddl['kses_allowed'] );
		$str_content .= '</p>';

		$this->arr_template_dropdown_args['ddl_name'] = 'verowa_select_template_firstroster';
		$str_content .= '<p><b>' . esc_html( __( 'Default first entry template', 'verowa-connect' ) ) . '</b></br>';
		$arr_ddl = verowa_show_template_dropdown( 'roster', $int_firstroster_default_template, $this->arr_template_dropdown_args );
		$str_content .= wp_kses( $arr_ddl['content'], $arr_ddl['kses_allowed'] );
		$str_content .= '</p>';

		$this->add_html_form( $str_content );
	}

	/**
	 * 
	 */
	private function vertexte_tab() {
		$arr_string_translations = array(
			'general' => array(
				'title' => esc_html( __( 'General', 'verowa-connect' ) ),
				'strings' => array(
					'Back', // BTN
					'e-mail', // obfuscate
					'file', // e.g. "PDF file"
					'MB', // abbrev. Megabytes
				),
			),
			'events' => array(
				'title'   => esc_html( __( 'Events', 'verowa-connect' ) ),
				'strings' => array(
					'All', // 'Button label on agenda'
					'Details',
					'Details + Subscription', // BTN
					'Enter a search term', // Agenda
					'No registration necessary.',
					array(
						'text' => 'Reset filter', 
						'info'=> 'Link text to reset the agenda filter',
					),
					// Event List
					'Subscription until',
					'There are no public events with registration taking place today.',
					'There will be no public events with registration in the next few days.',
					'This event doesn’t exist or it is already over.',
					'The registration deadline expired on %s.',
					'to the registration form',
					'Show from', // Agenda Datums Filter
					'Subscription', // Button
					'Upcoming events', // Wenn Title Attr bei Liste fehlt.
					'via e-mail',
				),
			),
			'rosters' => array(
				'title'   =>  esc_html( __( 'Rosters', 'verowa-connect' ) ),
				'strings' => array(
					'Service weeks',
				),
			),
			'forms_renting'=> array(
				'title'   => __('Renting', 'verowa-connect' ),
				'strings' => array(
					'Billing',
					'Contact',
					'Different billing address',
				),
			),
			'forms_subs'=> array(
				'title'   => __( 'Subscription', 'verowa-connect' ),
				'strings' => array(
					'back to the form',
					'enter more persons',
					'free seats:',
					'free seats: unlimited',
					'Only spontaneous visits possible',
					'Registration deadline expired',
					'reservable seats',
					'Send',
					'This event does not exist or no longer exists.',
					'The registration form is temporarily unavailable. Please try again later or contact the secretariat.',
					'There are no public events taking place today.',
					'There will be no public events in the next few days.',
				)
			),
		);

		if ( true == verowa_wpml_is_configured() ) {
			$arr_languages = verowa_wpml_get_active_languages();
		} else {
			$arr_languages = array(
				'de' => array(
					'code' => 'de',
					'native_name' => 'Deutsch',
				),
			);
		}

		$arr_av_translations = array();
		foreach ( $arr_languages as $str_lang_code => $arr_single_lang )
		{
			$arr_av_translations[ $str_lang_code ] = json_decode (get_option ('verowa_translations_' . $str_lang_code, '[]'), true);
		}

		$str_content = '';

		foreach ( $arr_string_translations as $arr_single_sesction ) {
			$str_content .= '<section class="verowa-setting-section" ><h2>' . $arr_single_sesction['title'] . '</h2><table style="margin-top: 1rem;">' .
				'<thead><tr>' .
				'<th style="text-align: left;" >Text</th>';
	
			foreach ( $arr_languages as $str_lang_code => $arr_single_lang ) {
				$str_content .= '<th class="verowa-lang-title-' . $arr_single_lang['code'] . '">' . ( $arr_single_lang['native_name'] ?? '' ) . '</th>';
			}

			$str_content .= '<tr>';
			foreach ($arr_single_sesction['strings'] as $mixed_single_string) {
				if ( true === is_array( $mixed_single_string ) ) {
					$str_lbl = $mixed_single_string['text'] .' ' . verowa_get_info_rollover( $mixed_single_string['info'] );
					$str_text = $mixed_single_string['text'];
				} else {
					$str_lbl = $mixed_single_string;
					$str_text = $mixed_single_string;
				}

				$str_content .= '<tr>' .
					'<td class="verowa-wpml-string-en" data-en="' . $str_text . '">' . $str_lbl . '</td>';
					foreach ($arr_languages as $str_lang_code => $arr_single_lang) {
						$str_translation = trim( $arr_av_translations[$str_lang_code][$str_text] ?? '' );
						$str_insert_btn_style = strlen( $str_translation ) > 0 ? 
							' style="display:none;" ' :' style="display:block;" ';
						$str_edit_btn_style = strlen( $str_translation ) > 0 ? 
							' style="display:block;" ' :' style="display:none;" ';
						$str_content .= '<td>' .
							'<span data-langcode="' . $str_lang_code . '" data-text="' . $str_translation . '" ' . 
							'class="dashicons dashicons-insert"' . $str_insert_btn_style . 'role="button"></span>' .
							'<span title="' . $str_translation . '" data-langcode="' . $str_lang_code . '" data-text="' . $str_translation . '"' .
								'class="dashicons dashicons-edit"' . $str_edit_btn_style . 'role="button" ></span>';
							'</td>';
					}

				$str_content .= '</tr>';
			}
			$str_content .= '</table></section>';
		}

		$str_content .=
			'<div id="verowa-wpml-translation-popup">' .
			'	<input type="hidden" name="lang_code" />' .
			'	<div class="verowa-wpml-translation-modal">' .
			'	<span class="close-button">&times;</span>' .
			'	<div class="row">' .
			'	<div class="column">' .
			'		<h2>' . esc_html( __( 'Original: English','verowa-connect' ) ) . '</h2>' .
			'		<textarea readonly class="original_string"></textarea>' .
			'	</div>' .
			'	<div class="column">' .
			'		<h2>Translation to: <span class="verowa-translation-lang">&nbsp;</span></h2>' .
			'		<textarea class="translated_string"></textarea>' .
			'	</div>' .
			'	</div>' .
			'	<div class="row" style="justify-content: end;">' . 
			'		<button class="verowa-save-button button button-primary">' . _x( 'Save',  'Text of a button', 'verowa-connect' ) . '</button>' .
			'	</div>' .
			'	</div>' .
			'</div>';
		echo $str_content;
	}

	private function subscriptions_tab()
	{
		$str_content = '';
		$ret_option  = get_option( 'verowa_subscriptions_settings' );
		$arr_options = false !== $ret_option ? json_decode( $ret_option, true ) : array();
			
		$str_recaptcha_key_public = $arr_options['recaptcha_key_public'] ?? '';
		$str_recaptcha_key_secret = $arr_options['recaptcha_key_secret'] ?? '';
		$str_event_detail_link = $arr_options['event_detail_link'] ?? '';

		$str_content .= '<input type="hidden" value="off" name="only_today" />' . PHP_EOL .
			'<input type="hidden" value="off" name="hide_bookable_seats" />' . PHP_EOL .
			'<input type="hidden" value="off" name="hide_free_seats" />' . PHP_EOL .
			'<input type="hidden" value="off" name="hide_numbers_when_infinite" />' . PHP_EOL .
			'<input type="hidden" value="off" name="show_event_link" />' . PHP_EOL .
			'<table cellpadding = "3" cellspacing = "0">' . PHP_EOL .
			'	<tr>' . PHP_EOL .
			'		<td>reCAPTCHA Public Key</td>' . PHP_EOL .
			'		<td>' . PHP_EOL .
			'			<input type="text" name="recaptcha_key_public" ' .
			'				value="' . esc_attr( $str_recaptcha_key_public ) . '" size="50" />' . PHP_EOL .
			'		</td>' . PHP_EOL .
			'	</tr>' . PHP_EOL .
			'	<tr>' . PHP_EOL .
			'		<td>reCAPTCHA Secret Key</td>' . PHP_EOL .
			'		<td>' . PHP_EOL .
			'		<input type="text" name="recaptcha_key_secret" ' .
			'		value="' . esc_attr( $str_recaptcha_key_secret ) . '" size="50" />' . PHP_EOL .
			'		</td>' . PHP_EOL .
			'	</tr>' . PHP_EOL .
			'	<tr>' . PHP_EOL .
			'		<td>' . PHP_EOL .
			'			<input type="checkbox" name="only_today" ' .  
			esc_html( verowa_set_cb_checked( 'only_today', $arr_options ) ) . ' />' . PHP_EOL .
				esc_html(
					_x(
						'Show only events of the current day',
						'Checkbox on the admin page',
						'verowa-connect'
					)
				) . PHP_EOL .
			'		</td>' . PHP_EOL .
			'	</tr>' . PHP_EOL .
			'	<tr>' . PHP_EOL .
			'		<td>' . PHP_EOL .
			'			<input type="checkbox" name="hide_free_seats"' . verowa_set_cb_checked( 'hide_free_seats', $arr_options ) . ' />' . PHP_EOL .
						 _x( 'Hide number of free places', 'Checkbox on the admin page', 'verowa-connect' ) . PHP_EOL .
			'		</td>' . PHP_EOL .
			'	</tr>' . PHP_EOL .
			'	<tr>' . PHP_EOL .
			'		<td>' . PHP_EOL .
			'			<input type="checkbox" name="hide_bookable_seats" ' .  verowa_set_cb_checked( 'hide_bookable_seats', $arr_options ) . '/>' . PHP_EOL .
						_x( 'Hide number of reservable seats', 'Checkbox on the admin page', 'verowa-connect' ) . 
			'		</td>' . PHP_EOL .
			'	</tr>' . PHP_EOL .
			'	<tr>' . PHP_EOL .
			'		<td>' . PHP_EOL .
			'			<input type="checkbox" name="hide_numbers_when_infinite" ' .  verowa_set_cb_checked( 'hide_numbers_when_infinite', $arr_options ) . '/>' . PHP_EOL .
			'			' .  _x( 'Hide number of seats if unlimited', 'Checkbox on the admin page', 'verowa-connect' ) . PHP_EOL .
			'		</td>' . PHP_EOL .
			'	</tr>' . PHP_EOL .
			'	<tr>' . PHP_EOL .
			'		<td>' . PHP_EOL .
			'			<input type="checkbox" name="show_event_link" ' . verowa_set_cb_checked( 'show_event_link', $arr_options ) . ' />' . PHP_EOL .
			'			' . _x( 'Show detailed links in the form', 'Checkbox on the admin page', 'verowa-connect' ) .
			'		</td>' . PHP_EOL .
			'	</tr>' . PHP_EOL .
			'	<tr>' . PHP_EOL .
			'		<td>' .
			// UNDONE: Übersetzung
			'			' . __( '', 'verowa-connect' ) . ' max. Plätze pro Anmeldung' . PHP_EOL .
			'		</td>' . PHP_EOL .
			'		<td>' . PHP_EOL .
			'			<select name="max_seats">' . PHP_EOL;
			
			for ( $i = 1; $i <= 6; $i++ ) {
				$selected_max_seats = verowa_set_dd_selected( 'max_seats', $arr_options, $i );
				$str_content .= '<option value="' . $i . '" ' . $selected_max_seats . '>' . $i . '</option>';
			}

		$str_content .= '</select>' . PHP_EOL .
			'		</td>' . PHP_EOL .
			'	</tr>' . PHP_EOL .
			'	<tr>' . PHP_EOL .
			'	<td>' . PHP_EOL .
			'		<h3>' .  __( 'Validation page', 'verowa-connect' ) . '</h3>' . PHP_EOL .
			'	</td>' . PHP_EOL .
			'	</tr>' . PHP_EOL .
			'	<tr>' . PHP_EOL .
			'		<td style="vertical-align: top;">' . PHP_EOL .
			'		</td>' . PHP_EOL .
			'		<td>' . PHP_EOL .
			'			<input type="text" name="event_detail_link" value="' . $str_event_detail_link . '" size="50" /><br />' . PHP_EOL .
			'			<p>' . __( 'For example', 'verowa-connect' ) . ': https://www.meinegemeinde.ch/veranstaltung/{%event_id%}/</p>' . PHP_EOL .
			'		</td>' . PHP_EOL .
			'	</tr>' . PHP_EOL .
			'</table>';
				
		$this->add_html_form( $str_content );
	}

	private function wpml_tab() {
		$str_content = '';
		$arr_ver_languages = $this->arr_module_infos['general']['content_additional_languages'] ?? [];
		$arr_ver_languages = array_merge ( array ( array(
			'id' => 'de',
			'name' => 'Deutsch',
			)), $arr_ver_languages );
		$str_wpml_mapping = get_option ('verowa_wpml_mapping', '');
		$arr_wpml_mapping = ( '' !== $str_wpml_mapping ) ? json_decode( $str_wpml_mapping, true ) : [];
		
		$arr_wpml_languages = verowa_wpml_get_active_languages();

		$str_content .= '<h2>WPML</h2><p style="width: 33%;min-width: 320px;">' .
			'Dieses Mapping kann verwendet werden, wenn der Sprachcode in WPML von dem in Verowa abweicht oder wenn mehrere WP-Sprachen aus einer Verowa gefüllt werden.' .
			'</p>';
		$str_content .= '<table id="verowa-wpml-mapping">' .
			'<thead><tr><th>WP</th><th>VER</th></tr></thead>';

		foreach ( $arr_wpml_languages as $arr_single_language ) {
			$key_mapping = array_search( $arr_single_language['code'], array_column( $arr_wpml_mapping, 'code' ) );
			$str_value = '';
			foreach ( $arr_wpml_mapping as $arr_single_lang ) {
				if ( true === in_array( $arr_single_language['code'], $arr_single_lang['wp_language_code'] ) ) {
					$str_value = $arr_single_lang['input_lang'];
					break;
				}
			}

			$lang_ddl = verowa_wpml_get_language_ddl( $arr_ver_languages, 'ver_language_for_' .$arr_single_language['code'], $str_value);
			$str_content .= '<tr>' .
			'	<td>' . $arr_single_language['native_name'] . ' - ' . $arr_single_language['code'] . '</td>' . 
			'	<td>' . wp_kses( $lang_ddl['content'], $lang_ddl['kses_allowed'] );
			if ( false !== $key_mapping ) {
				$str_content .= $arr_wpml_mapping[$key_mapping]['wp_language_code'];
			}
			$str_content .= '</td></tr>';
		}
		$str_content .= '</table>';
		$this->add_html_form( $str_content );
	}


	private function news_tab() {
		$arr_news_blocks = $this->arr_module_infos['postings']['block_types'] ?? [];
		$arr_block_templates = get_option('verowa_news_block_templates', []);
		$arr_news_blocks['text']['placeholders'] = array(
			'current' => array(
				'default' => array(
					'pcl' => array(
						array(
							'name' => 'TEXT',
							'helptext' => '',
						),
					),
				),
			),
			'deprecated' => array(),
		);

		$arr_news_blocks['image']['placeholders'] = array(
			'current' => array(
				'default' => array(
					'pcl' => array(
						array(
							'name' => 'CSS_CLASSES',
							'helptext' => 'Es werden nur die CSS Klassen ausgegeben. z.B. «verowa_postings aling_right text_warp»',
						),
						array(
							'name' => 'URL',
							'helptext' => '',
						),
						array(
							'name' => 'CAPTION',
							'helptext' => 'Legende',
						),
						array(
							'name' => 'SOURCE_TEXT',
							'helptext' => 'Quelle',
						),
						array(
							'name' => 'SOURCE_URL',
							'helptext' => 'Link zur Quelle',
						),
					),
				),
			),
			'deprecated' => array(),
		);

		$arr_news_blocks['document']['placeholders'] = array(
			'current' => array(
				'default' => array(
					'pcl' => array(
						array(
							'name' => 'FILE_LIST',
							'helptext' => '',
						),
					),
				),
			),
			'deprecated' => array(),
		);


		$arr_news_blocks['movie']['placeholders'] = array(
			'current' => array(
				'default' => array(
					'pcl' => array(
						array(
							'name' => 'EMBED_ID',
							'helptext' => 'ID des eingefügtem Video'
						),
					),
				),
			),
			'deprecated' => array(),
		);

		$arr_news_blocks['map']['placeholders'] = array(
			'current' => array(
				'default' => array(
					'pcl' => array(
						array(
							'name' => 'STREET',
							'helptext' => 'Ist für den URL encodiert',
						),
						array(
							'name' => 'CITY',
							'helptext' => 'Ist für den URL encodiert',
						),
					),
				),
			),
			'deprecated' => array(),
		);

		$arr_news_blocks['gallery']['placeholders'] = array(
			'current' => array(
				'default' => array(
					'pcl' => array(
						array(
							'name' => 'URL',
							'helptext' => '',
						),
						array(
							'name' => 'THUMBNAIL_URL',
							'helptext' => 'URL zum Vorschaubild',
						),
						array(
							'name' => 'CAPTION',
							'helptext' => 'Legende',
						),
						array(
							'name' => 'SOURCE_TEXT',
							'helptext' => 'Quelle',
						),
						array(
							'name' => 'SOURCE_URL',
							'helptext' => 'Link zur Quelle',
						),
					),
				),
			),
			'deprecated' => array(),
		);

		$str_content = '<div class="news-settings-wrapper"><h2>' . __( 'Template for content blocks', 'verowa-connect' ) . '</h2>';
		foreach ( $arr_news_blocks as $str_block_type => $arr_news_block )
		{
			if ( 'raw_html' === $str_block_type )
			{
				continue;
			}

			$str_content .= '<div class="mb-4"><h3>' . $arr_news_block['name'] . '</h3>';

				$arr_template_parts = ('gallery' === $str_block_type) ? array( 
					'header' => esc_html( __( 'Header', 'verowa-connect' ) ), 
					'entry'  => esc_html( _x( 'Image', 'Subtitle for the news gallery template.', 'verowa-connect' ) ), 
					'footer' => esc_html( __( 'Footer', 'verowa-connect' ) ),) : array('');
				
			foreach ( $arr_template_parts as $str_part_type => $str_title ) {
				if ( 'gallery' === $str_block_type ) {
					$str_name = 'verowa_news_block_templates[' . $str_block_type . '][' . $str_part_type .']';
					$str_id ='verowa_news_block_template_' . $str_block_type . '_' . $str_part_type;
					$str_value = stripslashes( $arr_block_templates[ $str_block_type ][ $str_part_type ] ?? '' );
				} else {
					$str_name = 'verowa_news_block_templates[' . $str_block_type . ']';
					$str_id = 'verowa_news_block_template_' . $str_block_type;
					$str_value = stripslashes( $arr_block_templates[ $str_block_type ] ?? '' );
				}

				$b_show_info = 'gallery' === $str_block_type && 'entry' === $str_part_type;
				if ( true === $b_show_info )
					$str_content .= '<div class="verowa-news-gallery-title" >';

				if ( strlen( $str_title ) > 0 ) 
					$str_content .= '<h4>' . $str_title . '</h4>';

				if ( true === $b_show_info )
					$str_content .= verowa_get_info_rollover( 
						__( 'Single image template repeated for each image.', 'verowa-connect' ) 
						) . '</div>';

				$str_content .= '<textarea name="' . $str_name . '" id="' . $str_id . '">' . $str_value . '</textarea>';

				if ( true === in_array( $str_part_type, array( 'entry', '' ) ) ) {
					if ( isset( $arr_news_blocks[ $str_block_type ]['placeholders']['current'] ) ) {
						$str_content .= '<div class="verowa_current_placeholder" >' .
							 Picture_Planet_GmbH\Verowa_Connect\VEROWA_TEMPLATE::redner_placeholder_html( $arr_news_blocks[$str_block_type]['placeholders']['current'] ) .
							'</div>';
					}
				}
			}

			$str_content .= '</div>'; 
		}
		$str_content .= '</div>';
		$this->add_html_form( $str_content );
	}


	private function tool_tab() {
		global $wp_version;
		$custom_posts_count = verowa_get_custom_posts_count();

		echo '<p>WP-Version:' . esc_html( $wp_version ) . '</p>' .
			'<ul>';

		foreach ( $custom_posts_count as $key => $val ) {
			echo '<li><b>' . esc_attr( $key ) . '</b>: ' . esc_html( $val ) . '</li>';
		}

		echo '</ul>' .
			'<form method="post">' .
			wp_nonce_field( 'verowa_admin_page', '_wpnonce', true, false ) .
			'<p><label><input type="radio" name="flush_action" value="event" /></label> Event</p>' .
			'<p><label><input type="radio" name="flush_action" value="person" /></label> Person</p>' .
			'<p><label><input type="radio" name="flush_action" value="cache" /></label> Cache</p>' .
			'<p><input type="submit" name="btn_save" value="execute" /></p>' .
			'</form>';
	}


	private function add_html_form( $str_content ) {
		echo '<form action="" method="POST">' .
			'<input type="hidden" name="tab" value="' . $this->str_tab . '" />' .
			$str_content;
		wp_nonce_field( 'verowa_admin_page' );
		echo '<input type="submit" value="' . esc_attr( __( 'Save Options', 'verowa-connect' ) ) . '" ' .
			'class="button button-primary" />' .
			'</form>';
	}


	public function update_options( $str_tab ) {
		global $wpdb;
		if (check_admin_referer ('verowa_admin_page')) {
			switch ( $str_tab ) {
				case 'agenda':
					$how_many_verowa_dropdowns_before = get_option( 'how_many_verowa_dropdowns', false );

					// Intercept if something goes wrong and set initial to 3.
					if ( false === $how_many_verowa_dropdowns_before ) {
						$how_many_verowa_dropdowns_before = 3;
						update_option( 'how_many_verowa_dropdowns', '3', true );
					}

					if ( isset( $_POST['verowa_wp_listengruppen_id'] ) ) {
						update_option( 'verowa_wordpress_listengruppe', $_POST['verowa_wp_listengruppen_id'], true );
					}

					$int_anzahl_zaehler = 1;
					$arr_dropdowns_ml_titel = array();

					// Sets the options as an array in the WP database from all the menus entered by the client.
					// we save all options of the previously existing menus, even if there are less later on
					// (It is safer to keep all options in case something is deleted by mistake).
					while ( isset( $_POST['how_many_verowa_dropdowns'] ) && $int_anzahl_zaehler <= $how_many_verowa_dropdowns_before ) {
						$str_gewaehlte_listen_ids = implode( ', ', $_POST[ 'dropdown_menu_' . $int_anzahl_zaehler ] );
						echo $_POST['dropdown_menu_' .
							$int_anzahl_zaehler . '_title'] ?? '';
						update_option( 'verowa_dropdown_' . $int_anzahl_zaehler, $str_gewaehlte_listen_ids, true );
						
						if( true === verowa_wpml_is_configured() ) {
							$arr_dropdowns_ml_titel[ $int_anzahl_zaehler ] = array();
							$arr_languages = verowa_wpml_get_active_languages();
							foreach ( $arr_languages as $arr_single_language ) {
								$str_ddl_title = trim( $_POST[ 'dropdown_menu_' .
									$int_anzahl_zaehler . '_title_' . $arr_single_language['code'] ] );
								$arr_dropdowns_ml_titel[ $int_anzahl_zaehler ][ $arr_single_language['code'] ] = $str_ddl_title;
							}
						} else {
							update_option(
								'verowa_dropdown_' . $int_anzahl_zaehler . '_title',
								$_POST[ 'dropdown_menu_' .
								$int_anzahl_zaehler . '_title' ],
								true
							);
						}
						$int_anzahl_zaehler++;
					}

					if ( count( $arr_dropdowns_ml_titel ) > 0 ) {
						update_option( 'verowa_agenda_ml_dropdowns_titel', wp_json_encode( $arr_dropdowns_ml_titel, JSON_UNESCAPED_UNICODE ) );
					}

					// Save the new number of drop down menus for the agenda.
					if ( isset( $_POST['how_many_verowa_dropdowns'] ) ) {
						update_option( 'how_many_verowa_dropdowns', $_POST['how_many_verowa_dropdowns'], true );
					}

					if ( isset( $_POST['verowa_show_full_text_search'] ) ) {
						$show_full_text_search = 'on' === htmlspecialchars( $_POST['verowa_show_full_text_search'] ) ? true : false;
						update_option( 'verowa_show_full_text_search', $show_full_text_search );
					}
					break;

				case 'events':
					update_option( 'verowa_keep_outdated_events_days', intval( $_POST['verowa_keep_outdated_events_days'] ?? 0 ) );

					update_option(
						'verowa_events_exclude_from_search_engines',
						isset( $_POST['verowa_events_exclude_from_search_engines'] ) ? 'on' : false
					);
					break;

				case 'persons':
					update_option(
						'verowa_persons_without_detail_page',
						isset( $_POST['verowa_persons_without_detail_page'] ) ? 'on' : false
					);

					update_option(
						'verowa_persons_exclude_from_search_engines',
						isset( $_POST['verowa_persons_exclude_from_search_engines'] ) ? 'on' : false
					);

					$db_without_detail_page   = get_option( 'verowa_persons_without_detail_page', false );
					$curr_without_detail_page = isset( $_POST['verowa_persons_without_detail_page'] ) ? 'on' : false;

					if ( $db_without_detail_page !== $curr_without_detail_page ) {
						if ( 'on' === $curr_without_detail_page ) {
							verowa_person_db_flush_posts();
						} else {
							$arr_persons = verowa_persons_get_multiple( null, 'FULL' );
							if ( is_array( $arr_persons ) ) {
								foreach ( $arr_persons as $arr_single_person ) {
									verowa_person_add_wp_post( $arr_single_person['person_id'], $arr_single_person );
								}
							}
						}
					}
					break;

				case 'rosters':
					// The default templates are stored outside of the case.
					break;

				case 'news':
					if ( isset( $_POST['verowa_news_block_templates'] ) ) {
						update_option( 'verowa_news_block_templates', $_POST['verowa_news_block_templates'] );
						verowa_general_set_deprecated_content( 'verowa_postings' );
					}
					break;

				case 'tools':
					if ( isset( $_POST['flush_action'] ) ) {
						switch ( $_POST['flush_action'] ) {
							case 'event':
								$arr_events = $wpdb->get_results( 'SELECT * FROM `' . $wpdb->prefix . 'posts` WHERE `post_type` = "verowa_event";' );
								if ( $arr_events ) {
									foreach ( $arr_events as $obj_single_event ) {
										wp_delete_post( $obj_single_event->ID, true );
									}
								}
								$wpdb->query( 'DELETE FROM `' . $wpdb->prefix . 'verowa_events` WHERE `event_id` > 0;' );
								$wpdb->query( 'DELETE FROM `' . $wpdb->prefix . 'icl_translations` WHERE `element_type` = "post_verowa_event";' );
								break;

							case 'person':
								$arr_persons = $wpdb->get_results( 'SELECT * FROM `' . $wpdb->prefix . 'posts` WHERE `post_type` = "verowa_person";' );
								$wpdb->query( 'DELETE FROM `' . $wpdb->prefix . 'icl_translations` WHERE `element_type` = "post_verowa_person";' );
								if ( $arr_persons ) {
									foreach ( $arr_persons as $obj_single_person ) {
										wp_delete_post( $obj_single_person->ID, true );
									}
								}
								$wpdb->query( 'DELETE FROM `' . $wpdb->prefix . 'verowa_person` WHERE `person_id` > 0;' );
								break;

							case 'cache':
								// add flush lsc cache.
								echo ( true === wp_cache_flush() ) ?
								'<p>Cache wurde gelöscht</p>' : '<p>Fehler beim löschen des Cache</p>';
								break;
						}
					}
					break;

				case 'subs':
					$ret_add     = add_option( 'verowa_subscriptions_settings', wp_json_encode( $_POST ) );

					if ( ! $ret_add ) {
						update_option( 'verowa_subscriptions_settings', wp_json_encode( $_POST ) );
					}
					break;

				case 'wpml':
					if ( true == verowa_wpml_is_configured() ) {
						$arr_wpml_mapping = array();
		
						$arr_wpml_languages = verowa_wpml_get_active_languages();
						foreach ( $arr_wpml_languages as $arr_single_language ) {
							$str_ver_lang = trim( $_POST['ver_language_for_' . $arr_single_language['code']] );
							if ( $str_ver_lang !== '' ) {
								$int_mapping_length = count( $arr_wpml_mapping );
								$bool_added = false;
								for ( $i = 0; $i < $int_mapping_length; $i++ ) {
									if ( $arr_wpml_mapping[ $i ]['input_lang'] == $str_ver_lang ) {
										$arr_wpml_mapping[ $i ]['wp_language_code'][] = $arr_single_language['code'];
										$bool_added = true;
									}
								}

								if ( false === $bool_added ) {
									$arr_wpml_mapping[] = array(
										'input_lang'       => $str_ver_lang,
										'wp_language_code' => array( $arr_single_language['code'] ),
									);
								}
							}
						}
					}

					$is_updated = update_option( 'verowa_wpml_mapping', wp_json_encode( $arr_wpml_mapping, JSON_UNESCAPED_UNICODE ) );
					echo '<div id="setting-error-settings_updated" ' .
						'class="notice notice-success settings-error is-dismissible"><p>' .
						__( 'Mapping saved.', 'verowa-connect' ) . '</p></div>';
					break;

				default:
					if ( isset( $_POST['verowa_api_key'] ) ) {
						// Option block "General".
						$instance = sanitize_key( $_POST['verowa_instance'] ?? '' );
						update_option( 'verowa_instance', $instance );

						$api_key = sanitize_text_field( wp_unslash( $_POST['verowa_api_key'] ?? '' ) );
						update_option( 'verowa_api_key', $api_key );

					}
					break;
			}

			if ( true == in_array( $str_tab, array( 'events', 'persons', 'rosters' ) ) ) {
				foreach ( $this->arr_option_mapping as $option_name => $ddl_name ) {
					if ( true === isset( $_POST[ $ddl_name ] ) ) {
						update_option(
							$option_name,
							intval( $_POST[ $ddl_name ] ?? 0 )
						);
					}
				}
			}
		}
	}


	public function register_rest_routes() {
		add_action(
			'rest_api_init',
			function () {
				register_rest_route( 'verowa/v1', '/update_translation', array (
					'methods' => 'POST',
					'callback' => array ( $this, 'update_translation_callback' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					}
				) );
			}
		);
	}


	public function update_translation_callback( \WP_REST_Request $request ) {
		$original_string = $request->get_param( 'original_string' );
		$translated_string = $request->get_param( 'translated_string' );
		$str_lang_code = $request->get_param( 'lang_code' );

		$arr_av_translations = json_decode( get_option ('verowa_translations_' . $str_lang_code, '[]'), true );
		$translated_string = trim( $translated_string );
		if ( empty( $translated_string ) ) {
			unset( $arr_av_translations[ $original_string ] );
			update_option( 'verowa_translations_' . $str_lang_code,
				wp_json_encode( $arr_av_translations, JSON_UNESCAPED_UNICODE ) );
		} else {
			$arr_av_translations[ $original_string ] = $translated_string;
			update_option( 'verowa_translations_' . $str_lang_code,
				wp_json_encode( $arr_av_translations, JSON_UNESCAPED_UNICODE ) );
		}

		return rest_ensure_response( array(
			'success' => true,
			'message' => __( 'Translation successfully updated!', 'verowa-connect' ),
			'original_string' =>  $original_string,
			'translated_string' =>  $translated_string,
			'lang_code' => $str_lang_code,
		) );
	}
}