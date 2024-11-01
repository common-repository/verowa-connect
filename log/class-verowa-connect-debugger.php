<?php
/**
 * Debug function for verowa connect.
 *
 * Project:  VEROWA CONNECT
 * File:     log/class-verowa-connect-debugger.php
 * Encoding: UTF-8 (ẅ)
 *
 * @package verowa_connect
 */

/**
 * Debug FN
 *
 * @author © Picture-Planet GmbH
 * @since Version 2.8.5
 */
class Verowa_Connect_Debugger {
	/**
	 * Write text to the connect log file
	 *
	 * @param string $str_text Text is written to the file.
	 *
	 * @return void
	 */
	public function write_to_file( $str_text ) {
		global $wp_filesystem;

		if ( VEROWA_DEBUG ) {
			$str_dir      = dirname( __DIR__, 1 );
			$date_now     = new DateTime();
			$str_log_file = $str_dir . '\log\log_' . $date_now->format( 'Y_m_d' ) . '.txt';
			$wp_filesystem->put_contents( $str_log_file, $str_text );
		}
	}
}
