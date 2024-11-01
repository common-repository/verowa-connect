<?php
/**
 * Internal settings
 *
 * Project:         VEROWA CONNECT
 * File:            includes/presets.php
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since Version 2.2.0
 * @package Verowa Connect
 */

// **************************************************************
// Do not change those settings! This could corrupt the plugin.
// **************************************************************

// Debug emails are sent and log files are created.
// The log files are stored in the "log" folder in the root directory of the plugin.
define( 'VEROWA_DEBUG', false );

define( 'VEROWA_REPORTING_MAIL', 'reporting@verowa.ch' );
define( 'VEROWA_CONNECT_DB_VERSION', '10' );

// With this pattern we fetch the lists shortcodes with the attributes from the pages.
// From there we then fetch the lists ids.
// verowa_event_list and verowa_event_list_dynamic are obsolete and will be deleted in the future.
define(
	'VEROWA_PATTERN_LIST_IDS',
	'/' . get_shortcode_regex(
		array(
			'verowa_event_list',
			'verowa_event_liste',
			'verowa_event_liste_dynamic',
			'verowa_subscription_overview',
			'verowa_agenda',
		)
	) . '/s'
);

$option_url = get_option( 'siteurl', '' ) . '/wp-admin/options-general.php?page=verowa-options';

/*
* translators: %1$s: link to the verowa options
* translators: %2$s: link to  VEROWA website'
*/
$str_verowa_error = __(
	'Please enter a parish ID and an API key under "Settings ><a href="%1$s">Verowa options</a>". You will receive both from <a href="%2$s" target="_blank" >Verowa support</a>.',
	'verowa-connect'
);

define(
	'VEROWA_APIKEY_MEMBER_ERROR',
	__( 'The connection to Verowa still needs some settings!', 'verowa-connect' ) . PHP_EOL .
	sprintf(
		$str_verowa_error,
		$option_url,
		'https://verowa.ch'
	)
);

define( 'VEROWA_POST_RELATED_SCRIPT_META_KEY', 'verowa_related_script' );

// Cache plug-in support.
define(
	'VEROWA_NOT_CACHE_URIS',
	array(
		'/wp-json/verowa/v1/update/777',
		'/wp-json/verowa/v1/plugin_info',
		'^/wp-json/verowa/v1/update_event',
		'^/wp-json/verowa/v1/update_person',
		'^/verowa-subscription-confirmation/',
		'^/subscription-form/',
		'^/anmeldung-validieren',
		'^/reservationsanfrage-response',
		'^/reservationsanfrage-validieren',
	)
);

define(
	'VEROWA_SHORTCODES',
	array(
		'verowa_person',
		'verowa_personen',
		'verowa_renting_form',
		'verowa_subscriptions_form',
		'verowa_print_subscriptions_form',
		'verowa_agenda',
		'verowa_event_liste_dynamic',
		'verowa_event_filter',
		'verowa_event_list',
		'verowa_event_liste',
		'verowa_roster_entries',
		'verowa-first-roster-entry',
	)
);

// General error messages.

// This error message is displayed when the user tries to manipulate the form.
define(
	'VEROWA_TECHNICAL_ERROR',
	_x(
		'Technical error in form',
		'This error message is displayed when the user tries to manipulate the form',
		'verowa-connect'
	)
);
