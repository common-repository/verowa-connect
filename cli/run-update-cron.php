<?php
if( php_sapi_name() !== 'cli' ) {
    die("Meant to be run from command line");
}

function find_wordpress_base_path() {
    $dir = dirname( __FILE__ );
    do {
        //it is possible to check for other files here
        if( file_exists( $dir."/wp-config.php" ) ) {
            return $dir;
        }
    } while( $dir = realpath( "$dir/.." ) );
    return null;
}

define( 'BASE_PATH', find_wordpress_base_path()."/" );
define( 'WP_USE_THEMES', false );
global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;
require ( BASE_PATH . 'wp-load.php' );

$arr_module_infos = verowa_get_module_infos();
if ($arr_module_infos['postings']['enabled'])
{
	$obj_postings = new Picture_Planet_GmbH\Verowa_Connect\Verowa_Postings();
	$obj_postings->verowa_connect_postings_importer();
}

$str_log = 'Start VC Importer: ' . wp_date( 'H:i:s' ) . PHP_EOL;
$start   = microtime( true );
echo 'Start: ' . wp_date( 'H:i:s v' ) . PHP_EOL;
$obj_update_controller = new Verowa_Update_Controller();
$ret_check             = verowa_check_member_api_key();
if ( $ret_check->validity ) {
	$str_log .= 'Vor Initialisierung: ' . wp_date( 'H:i:s' ) . PHP_EOL;
	$obj_update_controller->init( 'all' );
	echo 'init: ' . wp_date( 'H:i:s v' ) . PHP_EOL;
	$str_log .= 'Nach Initialisierung: ' . wp_date( 'H:i:s v' ) . PHP_EOL;

	$obj_update_controller->update_agenda_filters();
	$str_log .= 'Nach Agenda Filter Update: ' . wp_date( 'H:i:s v' ) . PHP_EOL;
	echo 'agenda: ' . wp_date( 'H:i:s v' ) . PHP_EOL;

	$obj_update_controller->update_targetgroups();
	$str_log .= 'Nach Zielgruppen Update: ' . wp_date( 'H:i:s v' ) . PHP_EOL;
	echo 'Zielgruppen: ' .  wp_date( 'H:i:s v' ) . PHP_EOL;

	$ret_log = verowa_save_log( 'verowa_last_update_log', $str_log );

	// The persons must be read in before the events, as persons who are only displayed
	// at the events or are on services are only added if they are not yet in the DB.
	$obj_update_controller->update_verowa_persons_in_db();
	$str_log .= 'Nach Personen Update: ' . wp_date( 'H:i:s v' ) . PHP_EOL;
	echo 'Personen: ' . wp_date( 'H:i:s v' ) . PHP_EOL;

	verowa_update_log( $ret_log->insert_id, $str_log );

	$obj_update_controller->update_verowa_events_in_db();
	$str_log .= 'Nach Veranstaltung Update: ' . wp_date( 'H:i:s v' ) . PHP_EOL;
	echo 'Veranstaltung: ' . wp_date( 'H:i:s v' ) . PHP_EOL;

	$obj_update_controller->update_roster_duty();
	$str_log .= 'Nach Dienstplan Update: ' . wp_date( 'H:i:s v' ) . PHP_EOL;
	echo 'Dienstplan: ' . wp_date( 'H:i:s v' ) . PHP_EOL;

	verowa_update_log( $ret_log->insert_id, $str_log );
	$obj_update_controller->checks_after_update();

	$time_elapsed_secs = ( microtime( true ) - $start );
	verowa_save_log( 'verowa_update_time_elapsed', $time_elapsed_secs . ' sec' );
	echo 'verowa_save_log: ' . wp_date( 'H:i:s v' ) . PHP_EOL;

	$str_log .= 'Ende: ' . wp_date( 'H:i:s v' ) . PHP_EOL;
	verowa_update_log( $ret_log->insert_id, $str_log );
	echo 'ende: ' . wp_date( 'H:i:s v' ) . PHP_EOL;
}

