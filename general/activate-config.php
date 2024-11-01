<?php
/**
 * Performs required updates after activating the plug-in
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since 2.2.0
 * @package Verowa Connect
 * @subpackage General
 */

use Picture_Planet_GmbH\Verowa_Connect;
use Picture_Planet_GmbH\Verowa_Connect\VEROWA_TEMPLATE;
/**
 * This function is executed as soon as the Verowa Connect Plugin is activated.
 * This function creates an Agenda page on installation (if it does not exist).
 * and sets default values (Options).
 */
function verowa_plugin_activate( $network_wide ) {
	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$is_info_updated = verowaset_set_module_infos();
	

	if ( is_multisite() && $network_wide ) {
		// Get all blogs in the network and activate plugin on each one
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );
			// add required tables for verowa connect.
			verowa_update_wp_database();

			$templates_in_db_query = 'SELECT COUNT(*) FROM `' . $wpdb->prefix . 'verowa_templates`;';

			$count_templates_in_db = intval( $wpdb->get_var( $templates_in_db_query ) ?? -1 );

			if ( 0 === $count_templates_in_db ) {
				verowa_add_default_templates_to_db();

				// set default options for templates.
				add_option( 'verowa_default_persondetails_template', 1 );
				add_option( 'verowa_default_personlist_template', 2 );
				add_option( 'verowa_default_eventdetails_template', 3 );
				add_option( 'verowa_default_eventlist_template', 4 );
			}

			Picture_Planet_GmbH\Verowa_Connect\Verowa_Postings::add_templates();

			verowa_create_subscriptions_pages();
			activate_verowa_data_hooks();
			verowa_wpml_init_templates();
			restore_current_blog();
		}
	} else {
		// add required tables for verowa connect.
		verowa_update_wp_database();

		$templates_in_db_query = 'SELECT COUNT(*) FROM `' . $wpdb->prefix . 'verowa_templates`;';

		$count_templates_in_db = intval( $wpdb->get_var( $templates_in_db_query ) ?? -1 );

		if ( 0 === $count_templates_in_db ) {
			verowa_add_default_templates_to_db();

			// set default options for templates.
			add_option( 'verowa_default_persondetails_template', 1 );
			add_option( 'verowa_default_personlist_template', 2 );
			add_option( 'verowa_default_eventdetails_template', 3 );
			add_option( 'verowa_default_eventlist_template', 4 );
		}

		Picture_Planet_GmbH\Verowa_Connect\Verowa_Postings::add_templates();

		verowa_create_subscriptions_pages();
		activate_verowa_data_hooks();
		verowa_wpml_init_templates();
	}
}


/**
 * Will be executed when the plugin is deactivated.
 */
function verowa_plugin_deactivate() {
	deactivate_verowa_data_hooks();
}


/**
 * The function is also called from the verowa_update_controller.php.
 */
function verowa_update_wp_database() {
	global $wpdb;

	$str_charset                = $wpdb->get_charset_collate();
	$str_events_tablename       = $wpdb->prefix . 'verowa_events';
	$str_persons_tablename      = $wpdb->prefix . 'verowa_person';
	$str_persongroups_tablename = $wpdb->prefix . 'verowa_person_groups';

	// Default number for dropdowns is 2.
	if ( false === get_option( 'how_many_verowa_dropdowns', false ) ) {
		update_option( 'how_many_verowa_dropdowns', '2' );
	}
	// ****************************************************************************************************************
	// The VEROWA_CONNECT_DB_VERSION must be incremented so that the changes are made to the DB.
	// ****************************************************************************************************************

	$create_verowa_translations = 'CREATE TABLE `' . $wpdb->prefix . 'verowa_translations` (
		`translation_id` bigint(20) NOT NULL AUTO_INCREMENT,
		`element_type` varchar(60) NOT NULL DEFAULT "",
		`element_id` bigint(20) DEFAULT NULL,
		`trid` bigint(20) NOT NULL,
		`language_code` varchar(7) NOT NULL,
		`source_language_code` varchar(7),
		PRIMARY KEY (translation_id),
		UNIQUE KEY `translation_id_UNIQUE` (translation_id ASC)) ' . $str_charset . ';';

	$create_event_table_query = 'CREATE TABLE `' . $str_events_tablename . '` (
		`event_id` INT UNSIGNED NOT NULL DEFAULT 0,
		`post_id` BIGINT(20) UNSIGNED NOT NULL,
		`datetime_from` DATETIME NOT NULL,
		`datetime_to` DATETIME NOT NULL,
		`list_ids` TEXT NOT NULL COMMENT ";a;b;c;",
		`layer_ids` TEXT NOT NULL COMMENT ";a;b;c;",
		`target_groups` TEXT NOT NULL COMMENT ";a;b;c;",
		`with_subscription` TINYINT UNSIGNED NULL DEFAULT 0,
		`content` TEXT NOT NULL COMMENT "JSON",
		`search_content` TEXT NOT NULL COMMENT "Text without HTML-Tags",
		`hash` VARCHAR(32) NOT NULL,
		`modified_when` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		`created_when` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
		`deprecated_content` TINYINT NOT NULL DEFAULT 0 COMMENT "Post content is deprecated after template update",
		`deprecated` TINYINT NOT NULL DEFAULT 0,
		PRIMARY  KEY (event_id))
		COMMENT = "Verowa Events werden hier gespeichert" ' . $str_charset . ';';

	$create_persons_table_query = 'CREATE TABLE `' . $str_persons_tablename . '` (
		`person_id` INT UNSIGNED NOT NULL,
		`post_id` BIGINT(20) UNSIGNED NOT NULL,
		`content` TEXT NOT NULL COMMENT "JSON",
		`hash` VARCHAR(32) NOT NULL,
		`web_visibility` ENUM("FULL","EVENTS") NULL COMMENT "Sichtbarkeit auf Homepage (auch externen); EVENTS = nur Name bei Mitwirkung zeigen",
		`modified_when` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		`created_when` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
		`deprecated_content` TINYINT NOT NULL DEFAULT 0 COMMENT "Post content is deprecated after template update",
		`deprecated` TINYINT NOT NULL DEFAULT 0,
		PRIMARY  KEY (person_id)) ' . $str_charset . ';';

	$create_persongroups_table_query = 'CREATE TABLE `' . $str_persongroups_tablename . '` (
		`pgroup_id` INT UNSIGNED NOT NULL,
		`parent_id` INT UNSIGNED NULL,
		`content` TEXT NOT NULL COMMENT "JSON",
		`person_ids` TEXT NOT NULL COMMENT ";a;b;c;",
		`functions_in_group` TEXT NOT NULL COMMENT "JSON with all the group functions of the members",
		`hash` VARCHAR(32) NOT NULL,
		`modified_when` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		`created_when` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
		`deprecated` TINYINT NOT NULL DEFAULT 0,
		PRIMARY  KEY (pgroup_id))
		COMMENT = "Personengruppen werden hier gespeichert" ' . $str_charset . ';';

	$create_verowa_temp_userdata_table_query = 'CREATE TABLE `' . $wpdb->prefix . 'verowa_temp_userdata` (
		`session_key` VARCHAR(30) NOT NULL,
		`user_data` LONGTEXT NOT NULL COMMENT "JSON Object",
		`created_when` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY  KEY (session_key));';

	$create_postings_table_query = 'CREATE TABLE `' . $wpdb->prefix . 'verowa_postings` (
		`ver_post_id` INT UNSIGNED NOT NULL,
		`event_id` INT UNSIGNED NOT NULL,
		`post_id` BIGINT(20) UNSIGNED NOT NULL,
		`content` TEXT NOT NULL COMMENT "JSON",
		`position` INT UNSIGNED NOT NULL,
		`publ_datetime_from` DATETIME NOT NULL,
		`publ_datetime_to` DATETIME NOT NULL,
		`ver_last_mod_on` DATETIME NOT NULL,
		`modified_when` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		`created_when` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
		`deprecated` TINYINT NOT NULL DEFAULT 0,
		`deprecated_content` TINYINT NOT NULL DEFAULT 0 COMMENT "Post content is deprecated after template update",
		PRIMARY KEY (ver_post_id)) ' . $str_charset . ';';

	$create_rosters_table_query = 'CREATE TABLE `' . $wpdb->prefix . 'verowa_roster_duties` (
		`roster_id` INT UNSIGNED NOT NULL,
		`datetime_from` DATETIME NOT NULL,
		`datetime_to` DATETIME NOT NULL,
		`content` TEXT NOT NULL COMMENT "JSON",
		`hash` VARCHAR(32) NOT NULL,
		`modified_when` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		`created_when` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
		`deprecated` TINYINT NOT NULL DEFAULT 0,
		PRIMARY  KEY (roster_id, datetime_from, datetime_to)) ' . $str_charset . ';';

	$create_verowa_log_query = 'CREATE TABLE `' . $wpdb->prefix . 'verowa_log` (
		`history_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
		`modifed_by_user_id` INT UNSIGNED NULL,
		`user_display_name` VARCHAR(250) NOT NULL,
		`change_type` VARCHAR(250) NOT NULL,
		`content` LONGTEXT NOT NULL COMMENT "JSON Object",
		`modified_when` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		`created_when` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY  KEY (history_id));';

	// dbDelta() checks if the tables already exist and only adds them if not.
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$arr_delta = dbDelta(
		array(
			VEROWA_TEMPLATE::get_create_table_query(),
			$create_verowa_translations,
			$create_event_table_query,
			$create_persons_table_query,
			$create_postings_table_query,
			$create_persongroups_table_query,
			$create_verowa_temp_userdata_table_query,
			$create_rosters_table_query,
			$create_verowa_log_query,
		)
	);

	update_option( 'verowa_connect_db_version', VEROWA_CONNECT_DB_VERSION );

}


/**
 * Add Default Templates to DB
 */
function verowa_add_default_templates_to_db() {
	global $wpdb;
	$arr_data_formats = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' );
	$wpdb->insert(
		$wpdb->prefix . 'verowa_templates',
		array(
			'template_id'   => 1,
			'template_name' => __( 'Persondetails', 'verowa-connect' ),
			'info_text'     => '',
			'type'          => 'persondetails',
			'display_where' => '',
			'header'        => '',
			'entry'         => '<div class="person">' . PHP_EOL .
			'<h1 class="verowa-person-title">{NAME}</h1>' . PHP_EOL .
			'[[?IMAGE_URL:<div class="person_image"><img src="{IMAGE_URL}" alt="" /></div>]]' . PHP_EOL .
			'<div class="person_description">' . PHP_EOL .
			'	[[?PROFESSION:<span class="person_profession">{PROFESSION}</span>]]' . PHP_EOL .
			'	[[?SHORT_DESC:<span class="person_excerpt">{SHORT_DESC}</span>]]' . PHP_EOL .
			'	[[?DESC_TASKS:<h3 class="aufgabenbereiche">' . __( 'Tasks', 'verowa-connect' ) . PHP_EOL .
			'	</h3><span class="person_tasks">{DESC_TASKS}</span>]]' . PHP_EOL .
			'	<h3 class="kontakt">' . __( 'Contact', 'verowa-connect' ) . '</h3>' . PHP_EOL .
			'	<span class="person_meta"> ' . PHP_EOL .
			'	[[?HAS_PRIVATE_ADDRESS % 1:<span class="address-type">Privat</span>' . PHP_EOL .
			'	[[?PRIVATE_STREET:<span class="address">{PRIVATE_STREET}</span>]]' . PHP_EOL .
			'	[[?PRIVATE_ZIP_CITY:<span class="postcode-town">{PRIVATE_ZIP_CITY}</span>]]' . PHP_EOL . ']]' . PHP_EOL .
			'	[[?HAS_BUSINESS_ADDRESS % 1:<span class="address-type">Geschäftlich</span>' . PHP_EOL .
			'	[[?BUSINESS_STREET:<span class="address">{BUSINESS_STREET}</span>]]' . PHP_EOL .
			'	[[?BUSINESS_ZIP_CITY:<span class="postcode-town">{BUSINESS_ZIP_CITY}</span>]]' . PHP_EOL . ']]' . PHP_EOL .
			'	[[?EMAIL:<span class="email"><a href="mailto:{EMAIL}">E-Mail</a></span>]]' . PHP_EOL .
			'	[[?BUSINESS_PHONE:<span class="phone business">{BUSINESS_PHONE}</span>]]' . PHP_EOL .
			'	[[?PRIVATE_PHONE:<span class="phone private">{PRIVATE_PHONE}</span>]]' . PHP_EOL .
			'	</span>' . PHP_EOL .
			'	[[?DESC_PERSONAL:<span class="person_description">{DESC_PERSONAL}</span>]]' . PHP_EOL .
			'</div>' . PHP_EOL .
			'</div>',
			'separator'     => '',
			'footer'        => '',
		),
		$arr_data_formats
	);

	$wpdb->insert(
		$wpdb->prefix . 'verowa_templates',
		array(
			'template_id'   => 2,
			'template_name' => __( 'Personlist', 'verowa-connect' ),
			'info_text'     => '',
			'type'          => 'personlist',
			'display_where' => 'content',
			'header'        => '<div class="person-container single-persons">',
			'entry'         => '<div class="person">' . PHP_EOL .
			'[[?IMAGE_URL:<div class="person_image">{IMAGE_URL}</div>]]' . PHP_EOL .
			'<div class="person_description">' . PHP_EOL .
			'<h4 class="name">{PERSON_NAME}</h4>' . PHP_EOL .
			'[[?FUNCTION_IN_GROUP:<p class="group_function">{FUNCTION_IN_GROUP}</p>]]' . PHP_EOL .
			'[[?SHORT_DESC:<p class="short_desc">{SHORT_DESC}</p>]]' . PHP_EOL .
			'[[?PROFESSION:<p class="profession">{PROFESSION}</p>]]' . PHP_EOL .
			'<div class="person_meta">' . PHP_EOL .
			'<ul>' . PHP_EOL .
			'[[?ADDRESS:<li class="address">{ADDRESS}</li>]]' . PHP_EOL .
			'[[?ZIP_CITY:<li class="postcode-town">{ZIP_CITY}</li>]]' . PHP_EOL .
			'[[?EMAIL:<li class="email"><a href="mailto:{EMAIL}">E-Mail</a></li>]]' . PHP_EOL .
			'[[?PHONE:<li class="phone">{PHONE}</li>]]' . PHP_EOL .
			'</ul>' . PHP_EOL .
			'</div>' . PHP_EOL .
			'</div>' . PHP_EOL .
			'</div>',
			'separator'     => '',
			'footer'        => '</div>',
		),
		$arr_data_formats
	);

	$wpdb->insert(
		$wpdb->prefix . 'verowa_templates',
		array(
			'template_id'   => 3,
			'template_name' => __( 'Eventdetails', 'verowa-connect' ),
			'info_text'     => '',
			'type'          => 'eventdetails',
			'display_where' => '',
			'header'        => '<div class="event_list_wrapper">',
			'entry'         => '[[?IMAGE_URL:<div class="image verowa-event-picture"><img src="{IMAGE_URL}" alt="" /></div>]]' . PHP_EOL .
			'<h1 class="verowa-event-title">{TITLE}</h1>' . PHP_EOL .
			'[[?TOPIC:<h2>{TOPIC}</h2>]]' . PHP_EOL .
			'<p class="datum">{DATETIME_FROM_LONG}</p>' . PHP_EOL .
			'[[?LOCATION_WITH_ROOM:<p class="town">{LOCATION_WITH_ROOM}</p>]]' . PHP_EOL .
			'[[?WITH_SACRAMENT:<p class="with_sacrament">{WITH_SACRAMENT}</p>]]' . PHP_EOL .
			'[[?SHORT_DESC:<div class="short_description">{SHORT_DESC}</div>]]' . PHP_EOL .
			'[[?LONG_DESC:<div class="long_description">{LONG_DESC}</div>]]' . PHP_EOL .
			'[[?FILE_LIST:<p class="files">{FILE_LIST}</p>]]' . PHP_EOL .
			'<div style="margin-bottom: 10px;" class="verowa-event-detail-meta-data">' . PHP_EOL .
			'[[?COORGANIZERS:<p class="coorganizer">{COORGANIZERS}</p>]]' . PHP_EOL .
			'[[?SUBSCRIPTION_INFO:<div class="subscription">{SUBSCRIPTION_INFO}</div>]]' . PHP_EOL .
			'' . PHP_EOL .
			'[[?SERVICE_4_PERSONS:<p class="organists">Musik: {SERVICE_4_PERSONS}</p>]]' . PHP_EOL .
			'[[?COLLECTION:<p class="collection">{COLLECTION}</p>]]' . PHP_EOL .
			'[[?BAPTISM_OFFER:<p class="baptism_offer">{BAPTISM_OFFER}</p>]]' . PHP_EOL .
			'[[?CHILDCARE:<p class="childcare">{CHILDCARE}</p>]]' . PHP_EOL .
			'[[?CATERING:<p class="catering"><b>' . __( 'with', 'verowa-connect' ) . ' {CATERING}</b></p>]]' . PHP_EOL .
			'</div>' . PHP_EOL .
			'' . PHP_EOL .
			'[[?ORGANIZER_ID:<h3>Kontakt</h3>' . PHP_EOL .
			'<div class="person-container single-persons mt-0" style="display: inline-block !important;">' .
			'	[verowa_person id="{ORGANIZER_ID}" comp_tag="th"]</div>' . PHP_EOL .
			'|| [[?ORGANIZER_NAME:<div class="pers-kontakt"><h3>Kontakt</h3><p>{ORGANIZER_NAME}</p></div>]]' . PHP_EOL .
			']]',
			'separator'     => '',
			'footer'        => '</div>',
		),
		$arr_data_formats
	);

	$wpdb->insert(
		$wpdb->prefix . 'verowa_templates',
		array(
			'template_id'   => 4,
			'template_name' => __( 'Eventlist', 'verowa-connect' ),
			'info_text'     => '',
			'type'          => 'eventlist',
			'display_where' => 'content',
			'header'        => '<div class="event_list_wrapper">',
			'entry'         => '<div class="event_list_item element-item event-{EVENT_ID} {CATEGORIES}" data-date="{DATE_FROM_8}" ' . PHP_EOL .
			'data-id="{EVENT_ID}"> ' . PHP_EOL .
			'<div class="event_date">{DATE_FROM_LONG}</div> ' . PHP_EOL .
			'<div class="event_container"> ' . PHP_EOL .
			'<div class="event_short_content"> ' . PHP_EOL .
			'{TIME_MIXED_LONG} ' . PHP_EOL .
			'<span class="event_title">{TITLE}</span> ' . PHP_EOL .
			'[[?TOPIC:<span class="event_topic">{TOPIC}</span>]] ' . PHP_EOL .
			'[[?LOCATION_WITH_ROOM:<span class="event_location">{LOCATION_WITH_ROOM}</span>]] ' . PHP_EOL .
			'</div> ' . PHP_EOL .
			'[[?IMAGE_URL:<div class="event_image"><img src="{IMAGE_URL}" alt="" /></div>]] ' . PHP_EOL .
			'<div class="event_toggle"><div class="toggle_button" ><i class="fas fa-angle-down"></i></div></div> ' . PHP_EOL .
			'<div class="event_content collapse event{EVENT_ID}" ></div> ' . PHP_EOL .
			'<div class="event_button_list"><div class="buttons"> ' . PHP_EOL .
			'<p class="ical"><a href="{ICAL_EXPORT_URL}">' . PHP_EOL .
			'	<i class="far fa-calendar-alt"></i>&nbsp;Kalender-Export</a></p><span class="details-buttons"> ' . PHP_EOL .
			'[[?SUBS_BUTTON:{SUBS_BUTTON}]] ' . PHP_EOL .
			'<a href="/veranstaltung/{EVENT_ID}/"><button class="detail">{DETAILS_BUTTON_TEXT}</button></a></span></div></div> ' . PHP_EOL .
			'</div> ' . PHP_EOL .
			'</div>',
			'separator'     => '',
			'footer'        => '</div>',
		),
		$arr_data_formats
	);

	$wpdb->insert(
		$wpdb->prefix . 'verowa_templates',
		array(
			'template_name' => 'Eventlist expand',
			'info_text'     => '',
			'type'          => 'eventlist',
			'display_where' => 'content',
			'header'        => '',
			'entry'         => '<div class="event_list_item element-item event-{EVENT_ID} {CATEGORIES}" data-date="{DATE_FROM_8}" data-id="{EVENT_ID}">' . PHP_EOL .
			'<div class="event_date"><a href="/veranstaltung/{EVENT_ID}/">{DATE_FROM_LONG}</a></div>' . PHP_EOL .
			'<div class="event_container">' . PHP_EOL .
			'<a href="/veranstaltung/{EVENT_ID}/"><div class="event_short_content">{TIME_MIXED_LONG}' . PHP_EOL .
			'<span class="event_title">{TITLE}</span>' . PHP_EOL .
			'[[?TOPIC:<span class="event_topic">{TOPIC}</span>]]' . PHP_EOL .
			'[[?LOCATION:<span class="event_location">{LOCATION}</span>]]' . PHP_EOL .
			'</div></a>' . PHP_EOL .
			'[[?IMAGE_URL:<div class="event_image"><img src="{IMAGE_URL}" alt="" /></div>]]' . PHP_EOL .
			'<a href="/veranstaltung/{EVENT_ID}/"><div>' . PHP_EOL .
			'[[?COORGANIZER_NAMES:<p style="margin-bottom: 0;">Mitwirkende: {COORGANIZER_NAMES}</p>]]' . PHP_EOL .
			'</div></a></div>' . PHP_EOL .
			'<div class="event_button_list_new"><div class="buttons"><span class="details-buttons">' . PHP_EOL .
			'[[?SUBS_BUTTON:{SUBS_BUTTON}]]</span></div></div></div>',
			'separator'     => '',
			'footer'        => '',
		),
		$arr_data_formats
	);

	$wpdb->insert(
		$wpdb->prefix . 'verowa_templates',
		array(
			'template_name' => __( 'Eventlist in Widget', 'verowa-connect' ),
			'info_text'     => '',
			'type'          => 'eventlist',
			'display_where' => 'widget',
			'header'        => '',
			'entry'         => '<div class="event_list_item">' . PHP_EOL .
			'<div class="event_date_text">{DATETIME_LONG}</div>' . PHP_EOL .
			'<a href="/veranstaltung/{EVENT_ID}/">' . PHP_EOL .
			'<div class="event_title">{TITLE}</div>' . PHP_EOL .
			'[[?LOCATION_WITH_ROOM:<div class="event_location">{LOCATION_WITH_ROOM}</div>]]' . PHP_EOL .
			'</a>' . PHP_EOL .
			'</div>',
			'separator'     => '',
			'footer'        => '',
		),
		$arr_data_formats
	);

	verowa_roster_add_duty_templates();
}
