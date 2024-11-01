<?php
/**
 * Class to Store a single Verowa template.
 *
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since 2.13.0
 * @package Verowa Connect
 * @subpackage TEMPLATE
 */

namespace Picture_Planet_GmbH\Verowa_Connect;

/**
 * Represent an single verowa template record.
 */
class VEROWA_TEMPLATE {

	/**
	 * Id of a Verowa template
	 *
	 * @var int
	 */
	public $template_id;

	/**
	 * Id of a sub template
	 *
	 * @var int
	 */
	public $nested_template_id;

	/**
	 * Template name has no technical use
	 *
	 * @var string
	 */
	public $template_name;

	/**
	 * Description of the template for the administrator
	 *
	 * @var string
	 */
	public $info_text;

	/**
	 * Indicates whether to display events for the entire day. Only available for event list types.
	 *
	 * @var bool $display_entire_day
	 */
	public $display_entire_day;

	/**
	 * Specifies where the Template can be used
	 *
	 * @var string
	 */
	public $type;

	/**
	 * Available is empty string, content or widget
	 *
	 * @var string
	 */
	public $display_where;

	/**
	 * Template part to add meta tags
	 * 
	 * @var string
	 */
	public $head;

	/**
	 * Header HTML of a Template
	 *
	 * @var string
	 */
	public $header;

	/**
	 * Content HTML of a Template
	 *
	 * @var string
	 */
	public $entry;

	/**
	 * Will be rendered between two entry
	 *
	 * @var string
	 */
	public $separator;

	/**
	 * Footer HTML of a Template
	 *
	 * @var string
	 */
	public $footer;

	/**
	 * Query to Create the database table
	 *
	 * @return string
	 */
	public static function get_create_table_query() {
		global $wpdb;
		$str_charset = $wpdb->get_charset_collate();
		
		return 'CREATE TABLE `' . $wpdb->prefix . 'verowa_templates` (
			`template_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`nested_template_id` INT UNSIGNED NULL DEFAULT 0,
			`display_entire_day` TINYINT NOT NULL DEFAULT 0,
			`template_name` VARCHAR(100) NOT NULL,
			`info_text` TEXT NOT NULL COMMENT "Infos zum template",
			`type` VARCHAR(80) NOT NULL,
			`display_where` VARCHAR(50) NOT NULL,
			`head` TEXT NOT NULL,
			`header` TEXT NOT NULL,
			`entry` TEXT NOT NULL,
			`separator` TEXT NOT NULL,
			`footer` TEXT NOT NULL,
			`modified_when` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			`created_when` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
			`deprecated` TINYINT NOT NULL DEFAULT 0,
			PRIMARY KEY (template_id),
			UNIQUE KEY `template_id_UNIQUE` (template_id ASC)) ' . $str_charset . ';';
	}



	/**
	 * Return the placeholder HTML.
	 * 
	 * @param array $arr_placeholders 
	 * @return string
	 */
	public static function redner_placeholder_html( $arr_placeholders ) {
		$str_placeholder_html = '';
		foreach ( $arr_placeholders ?? array() as $arr_pcl_group ) {
			$str_title             = isset( $arr_pcl_group['title'] ) ?
				'<span class="verowa-pcl-title">' . $arr_pcl_group['title'] . '</span>' : '';
			$str_placeholder_html .= '<div class="verowa-pcl-group">' . $str_title . '<div>';
			foreach ( $arr_pcl_group['pcl'] as $single_placeholder ) {
				if ( false === is_array( $single_placeholder ) ) {
					$str_placeholder_html .= '<span class="verowa-pcl-btn">' . $single_placeholder . '</span>';
				} elseif ( 0 === strlen( $single_placeholder['helptext'] ?? '' ) ) {
					$str_placeholder_html .= '<span class="verowa-pcl-btn">' . $single_placeholder['name'] . '</span>';
				} else {
					$str_placeholder_html .= '<span class="verowa-pcl-btn" title="' .
					$single_placeholder['helptext'] . '">' . $single_placeholder['name'] .
						'<i class="dashicons dashicons-info"></i></span>';
				}
			}
			$str_placeholder_html .= '</div></div>';
		}
		return $str_placeholder_html;
	}
	/**
	 * Initialized the class. If no parameter is set default value are set.
	 *
	 * @param array $arr_default_template Template from the default language.
	 * @param array $arr_language_template Only necessary if is not the default language template.
	 */
	public function __construct( $arr_default_template = null, $arr_language_template = null ) {
		// Initialize a new template.
		if ( null === $arr_default_template && null === $arr_language_template ) {
			$this->template_id        = 0;
			$this->nested_template_id = 0;
			$this->display_entire_day = 0;
			$this->template_name      = '';
			$this->info_text          = '';
			$this->type               = 'eventlist';
			$this->display_where      = '';
			$this->head             = '';
			$this->header             = '';
			$this->entry              = '';
			$this->separator          = '';
			$this->footer             = '';
		} else {
			// Default template laden.
			if ( null === $arr_language_template ) {
				$this->template_id        = $arr_default_template['template_id'] ?? 0;
				$this->nested_template_id = intval( $arr_default_template['nested_template_id'] ?? 0 );
				$this->display_entire_day = intval( $arr_default_template['display_entire_day'] ?? 0 );
				$this->template_name      = $arr_default_template['template_name'] ?? '';
				$this->info_text          = $arr_default_template['info_text'] ?? '';
				$this->type               = $arr_default_template['type'] ?? 'eventlist';
				$this->display_where      = $arr_default_template['display_where'] ?? '';
				$this->head             = $arr_default_template['head'] ?? '';
				$this->header             = $arr_default_template['header'] ?? '';
				$this->entry              = $arr_default_template['entry'] ?? '';
				$this->separator          = $arr_default_template['separator'] ?? '';
				$this->footer             = $arr_default_template['footer'] ?? '';
			} else {
				// language template ergänzt mit default template.
				$this->template_id        = $arr_language_template['template_id'] ?? 0;
				$this->nested_template_id = intval( $arr_default_template['nested_template_id'] ?? 0 );
				$this->display_entire_day = intval( $arr_default_template['display_entire_day'] ?? 0 );
				$this->template_name      = $arr_language_template['template_name'] ?? '';
				$this->info_text          = $arr_default_template['info_text'] ?? '';
				$this->type               = $arr_default_template['type'] ?? 'eventlist';
				$this->display_where      = $arr_default_template['display_where'] ?? '';
				$this->header             = $arr_language_template['header'] ?? '';
				$this->entry              = $arr_language_template['entry'] ?? '';
				$this->separator          = $arr_language_template['separator'] ?? '';
				$this->footer             = $arr_language_template['footer'] ?? '';
			}
		}
	}


	/**
	 * Try to get the values from the POST array and sanitize it.
	 *
	 * @param array $arr_post $_POST could be used.
	 *
	 * @return void
	 */
	public function get_data_from_post( $arr_post ) {
		$this->template_id        = intval( $arr_post['template_id'] ?? 0 );
		$this->nested_template_id = intval( $arr_post['verowa_select_template_rosterunit'] ?? 0 );
		$this->display_entire_day = $arr_post['display_entire_day'] ?? 'off' === 'on' ? 1 : 0;
		$this->template_name      = sanitize_text_field( $arr_post['template_name'] );
		$this->info_text          = sanitize_text_field( $arr_post['info_text'] ?? '' );
		$this->type               = sanitize_text_field( $arr_post['type'] ?? '' );
		$this->display_where      = sanitize_text_field( $arr_post['display_where'] ?? '' );
		$this->head               = stripcslashes( $arr_post['verowa_head'] );
		$this->header             = stripcslashes( $arr_post['verowa_header'] );
		$this->entry              = stripcslashes( $arr_post['verowa_entry'] );
		$this->separator          = stripcslashes( $arr_post['verowa_separator'] );
		$this->footer             = stripcslashes( $arr_post['verowa_footer'] );
	}


	/**
	 * Return the entire object as JSON string.
	 *
	 * @return string
	 */
	public function to_json() {
		return wp_json_encode( $this );
	}


	/**
	 * Return the object as Array for insert or update purpose.
	 *
	 * @return array;
	 */
	public function to_array() {
		$arr_ret = array();

		$arr_ret['nested_template_id'] = $this->nested_template_id;
		$arr_ret['display_entire_day'] = $this->display_entire_day;
		$arr_ret['template_name']      = $this->template_name;
		$arr_ret['info_text']          = $this->info_text;
		$arr_ret['type']               = $this->type;
		$arr_ret['display_where']      = $this->display_where;
		$arr_ret['head']               = $this->head;
		$arr_ret['header']             = $this->header;
		$arr_ret['entry']              = $this->entry;
		$arr_ret['separator']          = $this->separator;
		$arr_ret['footer']             = $this->footer;
		return $arr_ret;
	}


	/**
	 * Return format array for e.g. wpdb::insert or wpdb::update
	 *
	 * @return array
	 */
	public function get_format_array() {
		return array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );
	}
}
