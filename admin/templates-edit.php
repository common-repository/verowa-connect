<?php
/**
 * Edit Verowa templates for events and persons
 *
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since 2.8.0
 * @package Verowa Connect
 * @subpackage  Backend
 */

use Picture_Planet_GmbH\Verowa_Connect;
use Picture_Planet_GmbH\Verowa_Connect\VEROWA_TEMPLATE;
// UNDONE: Default Lang. Template kann nicht mehr gelöscht werden, wenn es Übersetzungen hat.
/**
 * Render the
 *
 * @return void
 */
function verowa_templates_configuration_page() {
	global $wpdb;

	// If content is added, it is displayed below the title.
	$str_info             = '';
	$str_default_language = verowa_wpml_get_default_language_code();
	$arr_error = array();

	// phpcs:ignore
	$str_language_code = esc_attr( wp_unslash( $_GET['lang'] ?? $str_default_language ) );
	$obj_template      = new VEROWA_TEMPLATE();

	// phpcs:ignore
	$str_type       = $_REQUEST['type'] ?? null;

	// settings for template list.
	$option = 'per_page';
	$args   = array(
		'label'   => 'Templates',
		'default' => 10,
		'option'  => 'Templates_per_page',
	);

	add_screen_option( $option, $args );

	// phpcs:ignore
	if ( isset( $_REQUEST['btn_save_template'] ) ) {
		$str_template_name = esc_attr( wp_unslash( $_POST['template_name'] ?? '' ) );
		if ($str_template_name === '') {
			$arr_error['template_name'] = __( 'Must not be empty.', 'verowa-connect' );
		} else {
			$i_template_id = intval( $_REQUEST['template_id'] ?? 0 );
			$str_template_name = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT `template_name` FROM `' . $wpdb->prefix . 'verowa_templates` ' .
					'WHERE `template_name` = %s AND `template_id` <> %d',
					$str_template_name,
					$i_template_id
				)
			);

			if ( $str_template_name !== null ) {
				$arr_error['template_name'] = __( 'Name must be unique.', 'verowa-connect' );
			}
		}

		if ( count( $arr_error ) > 0 ) {
			$str_info = '<p class="verowa_connect_error_box">' . 
				esc_html( __( 'The form contains errors.', 'verowa-connect' ) ) . '</p>';
		}

		if ( check_admin_referer( 'verowa_insert_update_template' ) && count( $arr_error ) === 0 ) {
			// phpcs:ignore
			if ( 0 === intval( $_REQUEST['template_id'] ) ) {
				verowa_insert_template_in_db( $_POST );
			} else {
				verowa_update_template_in_db( $_POST );
			}

			switch ( $str_type ) {
				case 'personlist':
					do_action(
						'verowa_purge_shortcode_cache',
						array(
							'verowa_person',
							'verowa_personen',
						)
					);
					break;

				case 'persondetails':
					verowa_general_set_deprecated_content( 'verowa_person' );
					verowa_general_set_deprecated_content( 'verowa_events' );
					break;

				case 'eventlist':
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
					break;

				case 'eventdetails':
					verowa_general_set_deprecated_content( 'verowa_events' );
					break;

				case 'postingdetails':
					verowa_general_set_deprecated_content( 'verowa_postings' );
					break;

				case 'roster':
					break;

			}
		} else {
			$obj_template = new VEROWA_TEMPLATE();
			$obj_template->get_data_from_post( $_POST );
		}
	} else {
		$template_id = intval( $_GET['t'] ?? 0 );
	}

	// Load template data.
	if ( ! empty( $template_id ) && $template_id > 0 ) {
		$is_duplicate = intval( $_GET['d'] ?? 0 ) > 0 ? true : false;
		$obj_template = verowa_get_single_template( $template_id, $str_language_code );

		if ( true === $is_duplicate ) {
			if ($obj_template == null) {
				$obj_template = verowa_get_single_template( $template_id, $str_default_language );
			}
			// Force insert on save.
			$obj_template->template_id = 0;
		}

		$str_template_type = $str_type ?? $obj_template->type ?? '';

		if ( '' !== $str_template_type ) {
			$arr_pcl_entry  = verowa_get_pcl_html( $str_template_type, 'entry', $obj_template->entry );
			$arr_pcl_footer = verowa_get_pcl_html( $str_template_type, 'footer', $obj_template->footer );
			$arr_pcl_header = verowa_get_pcl_html( $str_template_type, 'header', $obj_template->header );
			// The same placeholders can be used for the head as for the entry.
			$arr_pcl_head = verowa_get_pcl_html( $str_template_type, 'entry', $obj_template->header );
		}
	}

	if ( isset( $_GET['n'] ) || ( ! empty( $template_id ) && $template_id > 0) || count( $arr_error ) > 0 ) {
		$show_edit_form = true;
		$show_overview  = false;
	} else {
		$show_edit_form = false;
		$show_overview  = true;
	}

	// Delete template!
	if ( isset( $_GET['del'] ) ) {
		$int_template_id = intval( $_GET['del'] );
		$wpdb->update(
			$wpdb->prefix . 'verowa_templates',
			array(
				'deprecated' => 1,
			),
			array(
				'template_id' => $int_template_id,
			),
			array( '%d' ),
			array( '%d' )
		);
		if ( isset( $_GET['lang'] ) ) { 
			$arr_args = array(
				'element_id'   => $int_template_id,
				'element_type' => 'record_verowa_template',
			);
			verowa_wpml_delete_custom_element_language( $arr_args );
		} 
		$str_info = '<p class="verowa-info">' . esc_html( __( 'The template has been deleted', 'verowa-connect' ) ) . '</p>';
	}
	?>

<div class="wrap">
	<h2><?php echo esc_html( __( 'Configure display', 'verowa-connect' ) ); ?></h2>
	<?php
	if ( strlen( $str_info ) > 0 ) {
		echo wp_kses(
			$str_info,
			array(
				'p' => array(
					'class' => array(),
				),
			),
		);
	}
	?>
	<?php
	if ( $show_overview ) {
		$obj_templates = new Verowa_Templates_List();
		?>
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<a href="?page=verowa-options-templates&n=1">
					<?php echo esc_html( _x( 'add new', 'In Template Editor', 'verowa-connect' ) ); ?>
				</a>
				<div class="meta-box-sortables ui-sortable verowa-template-overview">
					<form method="post">
						<?php
							$obj_templates->prepare_items();
							$obj_templates->display();
						?>
					</form>
				</div>
			</div>
		</div>
		<br class="clear" />
	</div>
		<?php
	}

	if ( $show_edit_form ) {
		$int_template_id = intval( $_GET['t'] ?? 0 );
		$int_trid        = verowa_wpml_get_translations_trid( $int_template_id );

		// Flags to deactivate input fields that can only be edited in the main template.
		/**
		 * Disable egg. textarea or text input fields.
		 *
		 * @var string
		*/
		$str_disable = $str_default_language !== $str_language_code ? ' disabled ' : '';

		/**
		 * Class to add for a label.
		 *
		 * @var string
		*/
		$str_class_disable = $str_default_language !== $str_language_code ? ' class="verowa-disabled-label" ' : '';

		/**
		 * Flag to disable DDL-Menü.
		 *
		 * @var boolean
		 */
		$is_disable = $str_default_language !== $str_language_code ? true : false;
		?>
	<form id="template_edit_form" action="?page=verowa-options-templates" method="post" class="postbox verowa_form">
		<?php
			echo wp_kses(
				wp_nonce_field( 'verowa_insert_update_template' ),
				array(
					'input' => array(
						'type'  => array(),
						'id'    => array(),
						'name'  => array(),
						'value' => array(),
					),
				)
			);
		?>
		<input type="hidden" name="template_id" value="<?php echo esc_attr( $obj_template->template_id ); ?>" />
		<input type="hidden" name="trid" value="<?php echo esc_attr( $int_trid ); ?>" />
		<input type="hidden" name="language_code" value="<?php echo esc_attr( $str_language_code ); ?>" />
		<?php
		if ( $str_language_code !== $str_default_language ) {
			echo '<input type="hidden" name="source_language_code" value="' .
				esc_attr( verowa_wpml_get_default_language_code() ) . '" />';
		}

		if ( isset( $arr_error['template_name'] ) ) {
			$str_name_input = '<div><input type="text" name="template_name" class="verowa_input_has_error" value="' . 
			esc_attr( stripcslashes( $obj_template->template_name ) ) . '" />' .
			'<p class="verowa_inline_error_msg">' . $arr_error['template_name'] . '</p></div>';
		} else {
			$str_name_input = '<input type="text" name="template_name" value="' .
				esc_attr( stripcslashes( $obj_template->template_name ) ) . '" />';
		}
		echo '<div class="row">' .
			'<label>' . esc_attr( __( 'Name', 'verowa-connect' ) ) . ' *</label>' . $str_name_input .
			'</div>';

		if ( 'eventlist' === $obj_template->type ) {
			$arr_cb = verowa_checkbox_html(
				array(
					'name'  => 'display_entire_day',
					'text'  => __( 'Show entire day', 'verowa-connect' ),
					'value' => $obj_template->display_entire_day,
					'helptext' => 'Show events from the current day even if they have already passed',
				)
			);
			echo '<div class="row"><div>' . wp_kses( $arr_cb['content'], $arr_cb['kses_allowed'] ) . '</div></div>';
		}

		if ( 'roster' === $obj_template->type ) {
			$ddl_args = array(
				'show_empty_option' => true,
			);
			$arr_ddl  = verowa_show_template_dropdown( 'rosterunit', $obj_template->nested_template_id, $ddl_args );
			echo '<div class="row">' .
				'<label>' . esc_attr( __( 'Roster unit template', 'verowa-connect' ) ) . '</label>' .
				wp_kses( $arr_ddl['content'], $arr_ddl['kses_allowed'] ) .
				'</div>';
		}
		?>
		<div class="row">
			<?php
			echo wp_kses(
				'<label' . $str_class_disable . '>' .
				__( 'Template info', 'verowa-connect' ) .
				'</label>',
				array(
					'label' => array(
						'class' => array(),
					),
				)
			);
			?>
			<textarea name="info_text" <?php echo esc_html( $str_disable ); ?>><?php echo esc_html( stripcslashes( $obj_template->info_text ) ); ?></textarea>
		</div>
		<div class="row">
			<?php
				echo wp_kses(
					'<label' . $str_class_disable . '>' .
					__( 'Type', 'verowa-connect' ) .
					'</label>',
					array(
						'label' => array(
							'class' => array(),
						),
					)
				);

				$arr_types = array(
					'eventdetails'  => 'Eventdetails',
					'eventlist'     => 'Eventlist',
					'postingdetails'  => 'Newsdetails',
					'postinglist'     => 'Newslist',
					'personlist'    => 'Personlist',
					'persondetails' => 'Persondetails',
					'roster'        => _x( 'Roster', 'Option value for template selection', 'verowa-connect' ),
					'rosterunit'    => _x( 'Roster unit', 'Option value for template selection', 'verowa-connect' ),
				);

				if ( true === $is_disable ) {
					echo '<input name="type" type="hidden" value="' . $obj_template->type . '" />';
				}

				verowa_show_dropdown( 'type', $arr_types, $obj_template->type, $is_disable );

				?>
		</div>
		<div class="row">
		<?php
			echo wp_kses(
				'<label' . $str_class_disable . '>' .
				__( 'Display restriction', 'verowa-connect' ) .
				'</label>',
				array(
					'label' => array(
						'class' => array(),
					),
				)
			);

			$arr_display_where = array(
				''        => 'keine',
				'content' => 'Inhalt',
				'widget'  => 'Widget',
			);
			verowa_show_dropdown( 'display_where', $arr_display_where, $obj_template->display_where, $is_disable );
			?>
		</div>
		<?php
			$str_wrapper_styles = '';
			if ( in_array($obj_template->type, ['eventdetails', 'persondetails'], true) !== true ) {
				$str_wrapper_styles = ' style="display: none;" ';
			}
			
		if ($obj_template->template_id > 0 || true === $is_duplicate)
		{
			echo '<div class="row" id="verowa_head_wrapper"' . $str_wrapper_styles . '>
				<label>' . esc_html (__ ('Head', 'verowa-connect')) . '</label>';
		
			echo wp_kses(
				$arr_pcl_head['deprecated_pcl'] ?? '',
				array(
					'span' => array(
						'class' => array(),
					),
					'div'  => array(
						'id'    => array(),
						'class' => array(),
					),
					'p'    => array(
						'class' => array(),
					),
				)
			);
		
			echo '<textarea id="verowa_head" name="verowa_head">' . $obj_template->head . '</textarea>';

			echo wp_kses(
				$arr_pcl_head['current_pcl'] ?? '',
				array(
					'span' => array(
						'class' => array(),
					),
					'div' => array(
						'id' => array(),
						'class' => array(),
					),
					'p' => array(
						'class' => array(),
					),
					'a' => array(
						'href' => array(),
					),
				)
			);
			echo '</div>';

			?>
			<div class="row">
				<label><?php echo esc_html(__ ('Header', 'verowa-connect')); ?></label>
				<?php
				echo wp_kses(
					$arr_pcl_header['deprecated_pcl'] ?? '',
					array(
						'span' => array(
							'class' => array(),
						),
						'div' => array(
							'id' => array(),
							'class' => array(),
						),
						'p' => array(
							'class' => array(),
						),
					)
				);
				?>
				<textarea id="verowa_header" name="verowa_header"><?php echo wp_kses_post ($obj_template->header); ?></textarea>
				<?php
				echo wp_kses(
					$arr_pcl_header['current_pcl'] ?? '',
					array(
						'span' => array(
							'class' => array(),
						),
						'div' => array(
							'id' => array(),
							'class' => array(),
						),
						'p' => array(
							'class' => array(),
						),
						'a' => array(
							'href' => array(),
						),
					)
				);
				?>
			</div>
			<div class="row">
				<label><?php echo esc_html(__ ('Entry', 'verowa-connect')); ?></label>
				<?php
				echo wp_kses (
					$arr_pcl_entry['deprecated_pcl'] ?? '',
					array(
						'span' => array(
							'class' => array(),
						),
						'div' => array(
							'id' => array(),
							'class' => array(),
						),
						'p' => array(
							'class' => array(),
						),
					)
				);
				?>
				<textarea id="verowa_entry" name="verowa_entry"><?php echo $obj_template->entry; ?></textarea>
				<?php
				echo wp_kses(
					$arr_pcl_entry['current_pcl'] ?? '',
					array(
						'span' => array(
							'class' => array(),
						),
						'div' => array(
							'id' => array(),
							'class' => array(),
						),
						'p' => array(
							'class' => array(),
						),
						'a' => array(
							'href' => array(),
						),
					)
				);
				?>
			</div>
			<div class="row">
				<label><?php echo esc_html (__ ('Separator', 'verowa-connect')); ?></label>
				<textarea id="verowa_separator" name="verowa_separator"><?php echo wp_kses_post ($obj_template->separator); ?></textarea>
			</div>
			<div class="row">
				<?php
				echo wp_kses (
					$arr_pcl_footer['deprecated_pcl'] ?? '',
					array(
						'span' => array(
							'class' => array(),
						),
						'div' => array(
							'id' => array(),
							'class' => array(),
						),
						'p' => array(
							'class' => array(),
						),
					)
				);
				?>
				<label><?php echo esc_html(__ ('Footer', 'verowa-connect')); ?></label>
				<textarea id="verowa_footer" name="verowa_footer"><?php echo wp_kses_post ($obj_template->footer); ?></textarea>
				<?php
				echo wp_kses(
					$arr_pcl_footer['current_pcl'] ?? '',
					array(
						'span' => array(
							'class' => array(),
						),
						'div' => array(
							'id' => array(),
							'class' => array(),
						),
						'p' => array(
							'class' => array(),
						),
						'a' => array(
							'href' => array(),
						),
					)
				);
				?>
				<?php } // if for input fields that are only available when editing ?>
		</div>
		<div class="row">
			<input type="button" class="button button-secondary button-large" id="btn_abort_template"
				onclick="location.href = '?page=verowa-options-templates';" value="abbrechen" />
			<input type="submit" class="button button-primary button-large" id="btn_save_template" name="btn_save_template" value="speichern" />
		</div>
	</form>
		<?php
	}
	?>
</div>
<script type="text/javascript">
	// Load code mirror.
	var editor_entry;

	jQuery(document).ready(function ($) {
		if ($('#verowa_head').length > 0) {
			wp.codeEditor.initialize($('#verowa_head'), cm_settings);
		}
		wp.codeEditor.initialize($('#verowa_header'), cm_settings);
		editor_entry = wp.codeEditor.initialize($('#verowa_entry'), cm_settings);
		wp.codeEditor.initialize($('#verowa_separator'), cm_settings);
		wp.codeEditor.initialize($('#verowa_footer'), cm_settings);
	});

</script>
	<?php
}




/**
 * Return the HTML for the placeholder description.
 *
 * @param string $str_template_type Type off the Verowa templates.
 * @param string $str_template_part Template part 'entry', 'header' OR 'footer'.
 * @param
 *
 * @return array [current_pcl, deprecated_pcl]
 */
function verowa_get_pcl_html( $str_template_type, $str_template_part, $str_template_content ) {
	$str_doc_url                     = '';
	$str_deprecated_placeholder_html = '';

	switch ( $str_template_type ) {
		case 'personlist':
			$str_doc_url = 'https://verowa-connect.ch/platzhalter-fuer-personen#personenliste';
			break;

		case 'persondetails':
			$str_doc_url = 'https://verowa-connect.ch/platzhalter-fuer-personen#personendetail';
			break;

		case 'eventlist':
			$str_doc_url = 'https://verowa-connect.ch/platzhalter-fuer-veranstaltungen#veranstaltungsliste';
			break;

		case 'eventdetails':
			$str_doc_url = 'https://verowa-connect.ch/platzhalter-fuer-veranstaltungen#veranstaltungsdetails';
			break;

		case 'roster':
			$str_doc_url = 'https://verowa-connect.ch/platzhalter-fur-dienstplane/';
			break;
	}

	$str_placeholder_html = '<div id="verowa_current_placeholder" >';
	if ( strlen( trim( $str_doc_url ) ) ) {
		'<p><a href="' . $str_doc_url . '" target="_blank" >' .
		esc_html(
			_x(
				'Placeholder instructions',
				'Link text in the backend to the placeholder instructions',
				'verowa-connect'
			)
		) .
		'</a></p>';
	}
	$arr_placeholders = verowa_get_placeholder_desc( $str_template_type, $str_template_part );

	$str_placeholder_html .= VEROWA_TEMPLATE::redner_placeholder_html( $arr_placeholders['current'] ?? '' );

	$str_placeholder_html .= '</div>';

	$arr_found_deprecated_placeholder = array();
	foreach ( $arr_placeholders['deprecated'] ?? array() as $single_placeholder ) {
		$str_placeholder_name = is_array( $single_placeholder ) ? ( $single_placeholder['old'] ?? '' ) : $single_placeholder;
		if ( false !== strpos( $str_template_content, '{' . $str_placeholder_name . '}' ) ) {
			$arr_found_deprecated_placeholder [] = $single_placeholder;
		}
	}

	if ( count( $arr_found_deprecated_placeholder ?? array() ) > 0 ) {
		$str_deprecated_placeholder_html = '<div id="verowa_deprecated_placeholder" >' .
			'<span class="label" >' . __( 'The following placeholders are deprecated', 'verowa-connect' ) . '</span>';
		foreach ( $arr_found_deprecated_placeholder as $single_placeholder ) {
			if ( is_array( $single_placeholder ) ) {
				$str_pcl_old = $single_placeholder['old'] ?? '';
				$str_pcl_new = $single_placeholder['new'] ?? '';

				switch ( $single_placeholder['type'] ) {
					case 'changed':
						$str_plc_info_text = sprintf(
							esc_html_x(
								/*
								* translators: %1$s: Old Placeholder
								* translators: %2$s: New Placeholder
								*/
								'Replace %1$s with %2$s',
								'In Template Editor',
								'verowa-connect'
							),
							$str_pcl_old,
							$str_pcl_new
						);
						break;
				}
			} else {
				$str_plc_info_text = $single_placeholder;
			}
			$str_deprecated_placeholder_html .= '<p class="placeholders">' . $str_plc_info_text . '</p>';
		}
		$str_deprecated_placeholder_html .= '</div>';
	}

	return array(
		'current_pcl'    => $str_placeholder_html,
		'deprecated_pcl' => $str_deprecated_placeholder_html,
	);
}
