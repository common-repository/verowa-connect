<?php
/**
 * List to display all Verowa templates
 *
 * Project:         VEROWA CONNECT
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since 2.8.0
 * @package Verowa Connect
 * @subpackage  Backend
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Creates an overview list for the Verowa templates.
 */
class Verowa_Templates_List extends WP_List_Table {
	/**
	 * Array with alle selected template IDs.
	 *
	 * @var int[]
	 */
	private $arr_used_templates = array();

	/**
	 * All templates group by default language template.
	 *
	 * @var array
	 */
	private $arr_lang_templates;

	/**
	 * Load data to display the template list in Verowa backend.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Template', 'verowa-connect' ), // singular name of the listed records.
				'plural'   => __( 'Templates', 'verowa-connect' ), // plural name of the listed records.
				'ajax'     => false, // should this table support ajax?
			)
		);

		$this->_column_headers = array(
			$this->get_columns(),          // columns.
			array(),                       // hidden.
			$this->get_sortable_columns(), // sortable .
		);

		$this->arr_used_templates[] = get_option( 'verowa_default_persondetails_template', 0 );
		$this->arr_used_templates[] = get_option( 'verowa_default_personlist_template', 0 );
		$this->arr_used_templates[] = get_option( 'verowa_default_eventdetails_template', 0 );
		$this->arr_used_templates[] = get_option( 'verowa_default_eventlist_template', 0 );
		$this->arr_used_templates[] = get_option( 'verowa_default_postingdetails_template', 0 );
		$this->arr_used_templates[] = get_option( 'verowa_default_postinglist_template', 0 );
		$this->arr_used_templates[] = get_option( 'verowa_default_rosterlist_template', 0 );
		$this->arr_used_templates[] = get_option( 'verowa_default_firstroster_template', 0 );

		if ( true === verowa_wpml_is_configured() ) {
			$this->arr_lang_templates = self::get_templates_group_by_default();
		}
	}


	/**
	 * Returns the corresponding translated templates per default template.
	 *
	 * @return array
	 * source_language_code = null then default language or template_id of the first array
	 * [
	 *   template_id => [
	 *    'roh' => template_id
	 *    'fr' => template_id
	 *   ]
	 *   ...
	 * ]
	 */
	public static function get_templates_group_by_default() {

		$arr_custom_elements_language = verowa_wpml_get_custom_element_language(
			array( 'str_element_type' => 'record_verowa_template' )
		);
		$arr_lang_templates           = array();

		/**
		 * All translation group ids for default templates.
		*/
		$arr_trids = array();

		// Set default language templates.
		foreach ( $arr_custom_elements_language as $key => $obj_wpml_language ) {
			if ( null === $obj_wpml_language->source_language_code ) {
				$arr_lang_templates[ $obj_wpml_language->element_id ] = array();
				$arr_trids[ $obj_wpml_language->trid ]                = $obj_wpml_language->element_id;
				unset( $arr_custom_elements_language[ $key ] );
			}
		}

		// Assign remaining language templates.
		foreach ( $arr_custom_elements_language as $obj_wpml_language ) {
			$template_id = $arr_trids[ $obj_wpml_language->trid ] ?? 0;
			if ( 0 !== $template_id ) {
				$arr_lang_templates[ $template_id ][ $obj_wpml_language->language_code ] = $obj_wpml_language->element_id;
			}
		}

		return $arr_lang_templates;
	}




	/**
	 * Get the desired templates from the DB.
	 *
	 * @param int $per_page Number of templates per page.
	 * @param int $page_number Current page number to display.
	 *
	 * @return array|null|object
	 */
	public static function get_templates( $per_page = 10, $page_number = 1 ) {
		global $wpdb;

		// %i only supported on WP 6.2.0+
		$query = 'SELECT * FROM `%1$s` WHERE `deprecated` = 0';
		if ( true === verowa_wpml_is_configured() ) {
			$arr_default_template_ids = array_keys( self::get_templates_group_by_default() );
			if ( count( $arr_default_template_ids ) > 0 ) {
				$query .= ' AND `template_id` in (' . implode( ',', $arr_default_template_ids ) . ')';
			} else {
				$query .= ' AND `template_id` = -1';
			}
		}

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$query .= ' ORDER BY `%2$s`%3$s' .
				' LIMIT %4$d' .
				' OFFSET %5$d';

			$stmt = $wpdb->prepare(
				$query, // phpcs:ignore.
				$wpdb->prefix . 'verowa_templates',
				esc_sql( $_REQUEST['orderby'] ),
				( ! empty( $_REQUEST['order'] ) ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC',
				$per_page,
				( $page_number - 1 ) * $per_page
			);
		} else {
			$query .= ' LIMIT %2$d' .
				' OFFSET %3$d';
			$stmt   = $wpdb->prepare(
				$query, // phpcs:ignore.
				$wpdb->prefix . 'verowa_templates',
				$per_page,
				( $page_number - 1 ) * $per_page
			);
		}

		$result = $wpdb->get_results(
			$stmt, // phpcs:ignore.
			'ARRAY_A'
		);

		return $result;
	}




	/**
	 * Deletes the corresponding template.
	 *
	 * @param int $id Id of the Verowa template greater the 0.
	 */
	public static function delete_template( $id ) {
		global $wpdb;

		$wpdb->delete(
			$wpdb->prefix . 'verowa_templates',
			array( 'template_id' => $id ),
			array( '%d' )
		);
	}




	/**
	 * Returns the number of templates.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;
		$int_count = 0;
		if ( true === verowa_wpml_is_configured() ) {
			// UNDONE: Implement for WPML.
			$int_count = 0;
		} else {
			$query     = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'verowa_templates WHERE `deprecated` = 0';
			$int_count = $wpdb->get_var( $query ) ?? -1;
		}
		return $int_count;
	}

	/**
	 * Text displayed when no template data is available.
	 */
	public function no_items() {
		esc_html_e( 'No templates avaliable.', 'verowa-connect' );
	}

	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data.
	 *
	 * @return string
	 */
	public function column_name( $item ) {

		// Create a nonce.
		$delete_nonce = wp_create_nonce( 'verowa_delete_template' );

		$title = '<strong>' . $item['name'] . '</strong>';

		$actions = array(
			'delete' => sprintf(
				'<a href="?page=%s&action=%s&template=%s&_wpnonce=%s">Delete</a>',
				esc_attr( $_REQUEST['page'] ),
				'delete',
				absint( $item['template_id'] ),
				$delete_nonce
			),
		);

		return $title . $this->row_actions( $actions );
	}

	/**
	 * Render a column when no column specific method exists.
	 *
	 * @param array  $item Represent the current name.
	 * @param string $column_name Name of the current column.
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		$str_return        = '';
		$arr_language_code = array();
		$arr_text_columns  = array(
			'template_id',
			'template_name',
			'info_text',
			'type',
			'display_where',
		);

		$is_wpml_configured = verowa_wpml_is_configured();
		if ( true === $is_wpml_configured ) {
			$arr_languages     = verowa_wpml_get_active_languages( false );
			$arr_language_code = array_column( $arr_languages, 'code' );
		}

		// Display of columns depending on name.
		if ( true === in_array( $column_name, $arr_text_columns, true ) ) {
			$str_return = stripcslashes( $item[ $column_name ] );
		} elseif ( 'edit' === $column_name ) {
			$arr_actions = array();
			// edit.
			$arr_actions[] = '<a href="?page=verowa-options-templates&t=' . $item['template_id'] . '">' .
				__( 'edit', 'verowa-connect' ) . '</a>';

			// duplicate.
			$arr_actions[] = '<a href="?page=verowa-options-templates&t=' . $item['template_id'] . '&d=1">' .
				__( 'duplicate', 'verowa-connect' ) . '</a>';

			// delete?
			if ( in_array( $item['template_id'], $this->arr_used_templates, true ) ) {
				$arr_actions[] = '<span class="verowa-disable-delete" ' .
					'title="' . __( 'Template is used as default template', 'verowa-connect' ) . '" >' .
					__( 'delete', 'verowa-connect' ) . '</a>';
			} else {
				$arr_actions[] = '<a class="verowa-delete-action" ' .
					'href="?page=verowa-options-templates&del=' . $item['template_id'] . '" >' .
					__( 'delete', 'verowa-connect' ) . '</a>';
			}

			$str_return = implode( '</a>&nbsp;|&nbsp;', $arr_actions );
		} elseif ( true === in_array( $column_name, $arr_language_code, true ) ) {
			$lang_template_id = intval( $this->arr_lang_templates[ $item['template_id'] ][ $column_name ] ?? 0 );
			// TODO: P2 Tooltip anpassen.
			if ( $lang_template_id > 0 ) {
				$str_edit_url = '?page=verowa-options-templates&t=' . $lang_template_id . '&lang=' . $column_name;
				$str_icon     = '<i class="otgs-ico-edit js-otgs-popover-tooltip verowa-edit-ml-template" ' .
					'title="Edit the %s translation" tabindex="0" ></i>';
				// Create a nonce.
				$delete_nonce    = wp_create_nonce( 'verowa_delete_template' );
				$str_delete_url  = sprintf(
					'?page=%s&del=%s&lang=%s&_wpnonce=%s',
					esc_attr( $_REQUEST['page'] ),
					absint( $lang_template_id ),
					$column_name,
					$delete_nonce
				);
				$str_confirm_mgs = 'Soll das Sprachtemplate für «' . $item['template_name'] . '» gelöscht werden?';
				$str_delete_link = '|<a class="verowa-delelete-ml-template" onclick="return confirm(\'' . $str_confirm_mgs . '\')"' .
					'href="' . $str_delete_url . '"><i class="otgs-ico-delete" ' .
					'title="" tabindex="0" ></i></a>';
			} else {
				$str_edit_url    = '?page=verowa-options-templates&t=' . $item['template_id'] . '&d=1&lang=' . $column_name;
				$str_icon        = '<i class="otgs-ico-add js-otgs-popover-tooltip" ' .
					'title="Add translation" tabindex="0" ></i>';
				$str_delete_link = '';
			}
			$str_return = '<a href="' . $str_edit_url . '">' . $str_icon . '</a>' . $str_delete_link;
		} else {
			$str_return = print_r( $item, true ); // Show the whole array for troubleshooting purposes.
		}

		return $str_return;
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />',
			$item['template_id']
		);
	}

	/**
	 * Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			// 'cb' => '<input type="checkbox" />',
			'template_id'   => 'ID',
			'template_name' => __( 'Name', 'verowa-connect' ),
			'info_text'     => __( 'Info Text', 'verowa-connect' ),
			'type'          => __( 'Type', 'verowa-connect' ),
			'display_where' => __( 'Display where', 'verowa-connect' ),
		);

		$is_wpml_configured = verowa_wpml_is_configured();
		if ( true === $is_wpml_configured ) {
			$arr_languages = verowa_wpml_get_active_languages( false );
			foreach ( $arr_languages as $language_code => $arr_single_lang ) {
				$columns[ $language_code ] = $arr_single_lang['translated_name'] ?? $arr_single_lang['native_name'];
			}
		}

		$columns['edit'] = '';
		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'template_name' => array( 'template_name', true ),
			'type'          => array( 'type', true ),
			'separator'     => array( 'separator', false ),
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			// 'bulk-delete' => 'Delete'.
		);

		return $actions;
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'templates_per_page', 15 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args(
			array(
				'total_items' => $total_items, // We have to calculate the total number of items.
				'per_page'    => $per_page, // We have to determine how many items to show on a page.
			)
		);

		$this->items = self::get_templates( $per_page, $current_page );
	}


	/**
	 * Disabled for Templates.
	 */
	public function process_bulk_action() {
		// ...
	}
}
