<?php
/**
 * Adds a WP cronjob which instantiates and executes the update controller.
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since 2.0.0
 * @package Verowa Connect
 * @subpackage General
 */

/**
 * Activates Verowa data hooks during plugin activation.
 * If the scheduled event 'verowa_connect_importer' is not already set, it schedules the importer.
 *
 * @return void
 */
function activate_verowa_data_hooks() {
	if ( ! wp_next_scheduled( 'verowa_connect_importer' ) ) {
		verowa_schedule_importer();
	}

	
	$arr_module_infos = verowa_get_module_infos();
	if ($arr_module_infos['postings']['enabled'])
	{
		$obj_postings = new Picture_Planet_GmbH\Verowa_Connect\Verowa_Postings();
		$obj_postings->schedule_cron_job ();
	}
}




/**
 * Deactivates Verowa data hooks during plugin deactivation.
 * It clears the scheduled event 'verowa_connect_importer'.
 *
 * @return void
 */
function deactivate_verowa_data_hooks() {
	wp_clear_scheduled_hook( 'verowa_connect_importer' );

	$obj_postings = new Picture_Planet_GmbH\Verowa_Connect\Verowa_Postings();
	$obj_postings->deactivate_cron_job();
}

add_action( 'verowa_connect_importer', 'verowa_connect_importer_handler' );





/**
 * Activate configuration also use this function
 *
 * @return bool
 */
function verowa_connect_importer_handler() {
	global $wpdb;
	$str_log = 'Start VC Importer: ' . wp_date( 'H:i:s' ) . PHP_EOL;
	$start   = microtime( true );

	$obj_update_controller = new Verowa_Update_Controller();
	$ret_check             = verowa_check_member_api_key();
	if ( $ret_check->validity ) {
		$str_log .= 'Vor Initialisierung: ' . wp_date( 'H:i:s' ) . PHP_EOL;
		$obj_update_controller->init( 'all' );
		$str_log .= 'Nach Initialisierung: ' . wp_date( 'H:i:s v' ) . PHP_EOL;

		$obj_update_controller->update_agenda_filters();
		$str_log .= 'Nach Agenda Filter Update: ' . wp_date( 'H:i:s v' ) . PHP_EOL;

		$obj_update_controller->update_targetgroups();
		$str_log .= 'Nach Zielgruppen Update: ' . wp_date( 'H:i:s v' ) . PHP_EOL;

		$ret_log = verowa_save_log( 'verowa_last_update_log', $str_log );

		// The persons must be read in before the events, as persons who are only displayed
		// at the events or are on services are only added if they are not yet in the DB.
		$obj_update_controller->update_verowa_persons_in_db();
		$str_log .= 'Nach Personen Update: ' . wp_date( 'H:i:s v' ) . PHP_EOL;

		verowa_update_log( $ret_log->insert_id, $str_log );

		$obj_update_controller->update_verowa_events_in_db();
		$str_log .= 'Nach Veranstaltung Update: ' . wp_date( 'H:i:s v' ) . PHP_EOL;

		$obj_update_controller->update_roster_duty();
		$str_log .= 'Nach Dienstplan Update: ' . wp_date( 'H:i:s v' ) . PHP_EOL;

		verowa_update_log( $ret_log->insert_id, $str_log );
		$obj_update_controller->checks_after_update();

		$time_elapsed_secs = ( microtime( true ) - $start );
		verowa_save_log( 'verowa_update_time_elapsed', $time_elapsed_secs . ' sec' );

		$str_log .= 'Ende: ' . wp_date( 'H:i:s v' ) . PHP_EOL;
		verowa_update_log( $ret_log->insert_id, $str_log );
	}
	return $ret_check->validity;
}
