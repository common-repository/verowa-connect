<?php
/**
 * Creates the HTML code of a single form field.
 *
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @version 1.0
 * @package VEROWA CONNECT
 * @subpackage Forms
 */

namespace Picture_Planet_GmbH\Verowa_Connect;

/**
 * Class to render a single form field
 */
class Verowa_Formfields_Rendering {
	/**
	 * Type of the form control. Does not always correspond to the input control type.
	 *
	 * @var string
	 */
	private $str_input_type;

	/**
	 * Require if the field is nested in another form field. Only use for type fieldprop.
	 *
	 * @var string
	 */
	private $str_parent_type;


	/**
	 * It will be set as input value if no value is given in.
	 *
	 * @var array
	 */
	private $arr_default_value;

	/**
	 * The option are used for e.g radio or checkboxes.
	 *
	 * @var string
	 */
	private $str_options;

	/**
	 * If the form field is required it add '&nbsp;*'
	 *
	 * @var string
	 */
	private $str_required;

	/**
	 * Store the helptext ist one ist define.
	 *
	 * @var string
	 */
	private $str_helptext_html;

	/**
	 * Store option of ddl oder multiple choice.
	 *
	 * @var array
	 */
	private $arr_all_options;

	/**
	 * An array containing the options of the form field.
	 *
	 * @var array
	 */
	private $arr_formfield_options;

	/**
	 * An array that stores the value of the form field.
	 *
	 * @var mixed
	 */
	private $field_value;

	/**
	 * The placeholder text to be displayed in the form field.
	 *
	 * @var array
	 */
	private $arr_single_formfield;

	/**
	 * The placeholder text to be displayed in the form field.
	 *
	 * @var string
	 */
	private $str_placeholder;

	/**
	 * The default value to be pre-filled in the form field.
	 *
	 * @var string
	 */
	private $str_default_value;

	/**
	 * A string representing the HTML markup for the input group.
	 *
	 * @var string
	 */
	private $str_input_group;

	/**
	 * An array that stores the validation errors associated with the form field.
	 *
	 * @var array
	 */
	private $arr_errors;

	/**
	 * The values of the field 'config_json' will be stored here as an array.
	 *
	 * @var array
	 */
	private $arr_config;

	/**
	 * List of Errors
	 *
	 * @var string
	 */
	private $str_error_html;

	/**
	 * A string representing the HTML markup for displaying the error message(s).
	 *
	 * @var array
	 */
	private $arr_input_css_classes;

	/**
	 * Field name insert in the name attribute.
	 *
	 * @var string
	 */
	public $str_field_name;

	/**
	 * Id of the form field.
	 *
	 * @var string
	 */
	public $str_field_id;

	/**
	 * Part of the input name attribute e.g. field_name[sub_field_name]
	 *
	 * @var string
	 */
	public $str_sub_field_name;

	/**
	 * Prepares the form field specifications for all field types.
	 *
	 * @param array  $arr_single_formfield subscription or renting form field.
	 * @param string $str_input_group add do the str_field_name: e.g. group_name[field_name].
	 * @param string $str_parent_type Require if the field is nested in another form field. Only use for type fieldprop.
	 */
	public function __construct( $arr_single_formfield, $str_input_group = '', $str_parent_type = '' ) {

		$this->arr_default_value = array();
		$this->str_parent_type = $str_parent_type;
		$this->arr_input_css_classes = array();
		$this->str_default_value = '';
		$this->arr_single_formfield = $arr_single_formfield;
		$this->str_placeholder = '';
		$this->str_input_group = $str_input_group;
		$this->str_input_type = $arr_single_formfield['type'] ?? '';

		$str_config_json = trim( $arr_single_formfield['config_json'] ?? '' );
		if ( strlen( $str_config_json ) > 0 &&
			'html' != $this->str_parent_type &&
			'fieldprop' != $this->str_parent_type ) {
			$ret_config_json = json_decode( $str_config_json, true );
			$this->arr_config = is_array( $ret_config_json ) ? $ret_config_json : array();
		} else {
			$this->arr_config = array();
		}

		// == take away if they exist
		if ( key_exists( 'options', $arr_single_formfield ) ) {
			$this->str_options = false !== strpos( $arr_single_formfield['options'] ?? '', '==' ) ?
				substr( $arr_single_formfield['options'], 0, -2 ) : $arr_single_formfield['options'];
		}
		$this->str_options = str_replace( 'int_remarks;', '', $this->str_options ?? '' );

		// Remove validation options.
		if ( 'multiple_choice' === $this->str_input_type ) {
			$this->str_options = preg_replace( '/(\[\[)(.*)(\]\])/s', '', $this->str_options );
		}

		// {ff132:"Remarks: %value%"}500 => Cutting off the display specification
		// Note that it cannot be reduced to {} since there may also be values enclosed in these brackets following the option.
		$this->str_options = preg_replace( '/\{ff\d+:"[^"]+"}/', '', $this->str_options );

		$str_key_required = array_key_exists( 'is_required', $arr_single_formfield ) ? 'is_required' : 'required';
		$this->str_required = 1 === intval( $arr_single_formfield[ $str_key_required ] ) ? '&nbsp;*' : '';

		// type fieldprop, the field names must be extended so that they are unique.
		if ( 'fieldprop' === $str_parent_type ) {
			$this->str_field_name = 'field_ff_' . $this->arr_single_formfield['field_id'];
		} else {
			// The field name has dependencies on the registration form and cannot yet be removed.
			$this->str_field_name = key_exists( 'field_name', $this->arr_single_formfield ) ?
				$this->arr_single_formfield['field_name'] : 'field_' . $this->arr_single_formfield['field_id'];
		}

		$this->str_field_id = 'field_' . $this->arr_single_formfield['field_id'];

		if ( '' !== $str_input_group ) {
			$this->str_sub_field_name = $this->str_field_name;
			$this->str_field_name = $str_input_group . '[' . $this->str_field_name . ']';
		}

		$this->str_helptext_html = '';
		if ( key_exists( 'helptext', $this->arr_single_formfield ) && '' != $this->arr_single_formfield['helptext'] ) {
			if ( false !== strpos( $this->arr_single_formfield['helptext'], '}' ) &&
				'{' !== $this->arr_single_formfield['helptext'][0] ) {
				$str_helptext_html = preg_split(
					'/[{}]/',
					$this->arr_single_formfield['helptext'],
					-1,
					PREG_SPLIT_NO_EMPTY
				)[0];

				$this->str_helptext_html = verowa_get_info_rollover( $str_helptext_html );
			} elseif ( '{' !== $this->arr_single_formfield['helptext'][0] ) {
				$this->str_helptext_html = verowa_get_info_rollover( $this->arr_single_formfield['helptext'] );
			}
		}

		// Read out default value /(.*)({.*})(.*)/ .
		if ( key_exists( 'default_value', $arr_single_formfield ) ) {
			$this->str_default_value = $arr_single_formfield['default_value'];
			if ( false !== strpos( $this->str_default_value ?? '', '}' ) &&
				false !== strpos( $this->str_default_value ?? '', '{' ) ) {
				$this->str_default_value =
					preg_split( '/[{}]/', $this->arr_single_formfield['default_value'], -1, PREG_SPLIT_NO_EMPTY )[0];
			}
		} elseif ( false !== strpos( $this->str_options ?? '', '{' ) && false !== strpos( $this->str_options, '}' ) ) {
			$this->str_options = substr( $this->str_options, 0, -1 );

			$this->arr_all_options = explode( '{', $this->str_options );

			$this->str_options = $this->arr_all_options[0];
			$this->str_default_value = $this->arr_all_options[1];
		}

		// If it has a value in the field, it will be set later.
		$this->field_value = $this->str_default_value;

		// Separate label and short title in the form we take the whole title.
		if ( key_exists( 'label', $arr_single_formfield ) &&
			false !== strpos( $arr_single_formfield['label'] ?? '', '{{' ) &&
			false !== strpos( $arr_single_formfield['label'] ?? '', '}}' ) ) {
			$arr_title_pieces = verowa_separate_subs_label_shorttitle( $arr_single_formfield['label'] );

			$arr_single_formfield['label'] = $arr_title_pieces[0];
		}

		$this->str_error_html = '';
		if ( key_exists( 'str_error_msg', $arr_single_formfield ) && $arr_single_formfield['str_error_msg'] ) {
			$this->str_error_html = verowa_get_inline_error( $arr_single_formfield['str_error_msg'] );
		}
	}

	/**
	 * Inject post array and set field value
	 *
	 * @param array $arr_post Post Value.
	 */
	public function set_field_value( &$arr_post ) {
		switch ( $this->str_input_type ) {
			case 'contact':
				$this->field_value = array();
				$str_contact = 'arr_renting_persons_contact';
				$str_billing = 'arr_renting_persons_billing';

				$this->field_value['billing_is_different'] = $arr_post['billing_is_different'] ?? 'off';
				$this->field_value[ $str_contact ] = $arr_post[ $str_contact ] ?? array();
				$this->field_value[ $str_billing ] = $arr_post[ $str_billing ] ?? array();
				break;
			default:
				if ( '' === $this->str_parent_type ) {
					// Set field value if it exists.
					if ( '' != $this->str_input_group ) {
						$this->field_value =
							$arr_post[ $this->str_input_group ][ $this->str_sub_field_name ] ?? $this->str_default_value;
					} else {
						$this->field_value = $arr_post[ $this->str_field_name ] ?? $this->str_default_value;
					}
				} elseif ( 'fieldprop' === $this->str_parent_type ) {
					$this->field_value = $arr_post[ $this->str_sub_field_name ] ?? $this->str_default_value;
				}
				break;
		}
	}


	/**
	 * Fill the HTML error string in $this->str_error_html.
	 *
	 * @param array $arr_errors Array of form errors.
	 */
	public function error_handler( $arr_errors ) {
		switch ( $this->str_input_type ) {
			case 'contact':
			case 'fieldprop':
				$this->arr_errors = $arr_errors ?? array();
				break;

			default:
				$field_id = $this->arr_single_formfield['field_id'];
				if ( key_exists( $field_id, $arr_errors ?? array() ) ) {
					$this->str_error_html = '<p class="verowa_inline_error_msg pp_inline_error_msg">' .
						$arr_errors[ $field_id ] . '</p>';
				}
		}
	}

	/**
	 * Echo the form field
	 *
	 * @return void
	 */
	public function show_formfield_html() {
		echo $this->get_formfield_html( true );
	}

	/**
	 * Returns a HTML string with the input controls depending on the type.
	 *
	 * @param bool $show_html If true then the HTML will echo through wp_kses_html.
	 *
	 * @return string
	 */
	private function get_formfield_html( $show_html = false ) {
		$str_html = '';
		// UNDONE: P3 Neu über wp_kses_html ausgeben, die *_input Funktionen solle auch die Spezi. für wp_kses_html
		// zurückgeben.

		// Which type has the field?
		switch ( $this->str_input_type ) {
			case 'checkbox':
				$str_html = $this->get_checkbox_input();
				break;
			case 'contact':
				$str_html = $this->get_contact_input();
				break;
			case 'date':
				$str_html = $this->get_date_input();
				break;
			case 'datetime':
				$str_html = $this->get_datetime_input();
				break;
			case 'dropdown':
				$str_html = $this->get_dropdown_input();
				break;
			case 'email':
				$str_html = $this->get_general_input( 'ct-input', 'email' );
				break;
			case 'fieldprop':
				$str_html = $this->get_fieldprop_input();
				break;
			case 'hidden':
				$str_html = $this->get_hidden_input();
				break;
			case 'radio':
				$str_html = $this->get_radio_input();
				break;
			case 'seats':
			case 'number':
				$str_html = $this->get_number_input();
				break;
			case 'text':
				$str_html = $this->get_text_input();
				break;
			case 'tel':
			case 'phone':
				$this->str_placeholder = 'placeholder="079 123 45 67"';
				$str_html = $this->get_general_input( 'verowa-input verowa-input-phone ct-input ct-input-phone', 'tel' );
				break;
			case 'time':
				$str_html = $this->get_general_input( 'verowa-input verowa-input-time ct-input ct-input-time', 'time' );
				break;
			case 'multiple_choice':
				$str_html = $this->get_multiple_choice();
				break;
			case 'title':
				$str_html = '<div class="verowa-title ct-title">' .
					str_replace( '[%title%]', $this->arr_single_formfield['label'], $this->str_options ) .
					'</div>';
				break;
			case 'html':
				$str_html = $this->arr_single_formfield['custom'] ?? $this->str_options;
				break;
		}

		return $str_html;
	}



	/**
	 * Generate text input or textarea if max length more then 100
	 *
	 * @return string
	 */
	private function get_text_input() {

		$str_html = '<div class="verowa-input ct-input" data-id="' . $this->str_field_id . '" >' .
			'<label for="' . $this->str_field_name . '">' .
			$this->arr_single_formfield['label'] . $this->str_required . '&nbsp;'
			. $this->str_helptext_html . '</label>';

		if ( '' !== $this->str_error_html ) {
			$this->arr_input_css_classes = array( 'verowa_input_has_error', 'pp_input_has_error' );
		}

		if ( key_exists( 'additional_css_classes', $this->arr_config ) ) {
			$this->arr_input_css_classes[] = trim( $this->arr_config['additional_css_classes'] );
		}

		$str_input_css_classes = implode( ' ', $this->arr_input_css_classes );
		$str_input_class = strlen( $str_input_css_classes ) ? ' class="' . $str_input_css_classes . '" ' : ' ';
		// $str_html .= $this->str_options;
		if ( intval( $this->str_options ) > 100 ) {
			// Not all Browser supports minlength.
			$str_html .= '<textarea' . $str_input_class . 'name="' . $this->str_field_name .
				'" id="' . $this->str_field_name . '" ' . $this->str_placeholder . '>' .
				$this->field_value . '</textarea>';
		} else {
			$str_html .= '<input type="text"' . $str_input_class . 'name="' . $this->str_field_name .
				'" id="' . $this->str_field_name . '" ' . $this->str_placeholder . ' value="' .
				$this->field_value . '"/>';
		}

		$str_html .= $this->str_error_html . '</div>';

		return $str_html;
	}




	/**
	 *
	 *
	 * @return string HTML
	 */
	private function get_number_input() {
		$str_min_max = $this->get_max_min_for_input();

		if ( '' != $this->str_error_html ) {
			$this->arr_input_css_classes = array( 'verowa_input_has_error', 'pp_input_has_error' );
		}

		if ( key_exists( 'additional_css_classes', $this->arr_config ) ) {
			$this->arr_input_css_classes[] = trim( $this->arr_config['additional_css_classes'] );
		}

		$str_input_css_classes = implode( ' ', $this->arr_input_css_classes );
		$str_input_class = strlen( $str_input_css_classes ) ? ' class="' . $str_input_css_classes . '" ' : ' ';

		$str_html = '<div class="verowa-input ct-input" data-id="' . $this->str_field_id . '" >' .
			'<label for="' . $this->str_field_name . '">' .
			$this->arr_single_formfield['label'] . $this->str_required . '&nbsp;' .
			$this->str_helptext_html . '</label>' .
			'<input type="number" name="' . $this->str_field_name . '"' .
			' id="' . $this->str_field_name . '" value="' . $this->field_value . '" ' .
			$str_min_max . $str_input_class . '/>' . $this->str_error_html . '</div>';

		return $str_html;
	}



	/**
	 *
	 * @return string
	 */
	private function get_dropdown_input() {

		if ( '' !== $this->str_error_html ) {
			$this->arr_input_css_classes = array( 'verowa_input_has_error', 'pp_input_has_error' );
		}

		if ( key_exists( 'additional_css_classes', $this->arr_config ) ) {
			$this->arr_input_css_classes[] = trim( $this->arr_config['additional_css_classes'] );
		}

		$str_input_css_classes = implode( ' ', $this->arr_input_css_classes );
		$str_input_class = strlen( $str_input_css_classes ) ? ' class="' . $str_input_css_classes . '" ' : ' ';

		$str_html = '<div data-id="' . $this->str_field_id . '" ><label for="' . $this->str_field_name . '">' .
			$this->arr_single_formfield['label'] . $this->str_required . '&nbsp;';
		$str_html .= $this->str_helptext_html . '</label>';
		$str_html .= '<select id="' . $this->str_field_name . '" name="' .
			$this->str_field_name . '"' . $str_input_class . '>';

		$this->arr_formfield_options = verowa_slice_formfield_options( $this->str_options, 'label_and_value' );

		$str_selected = '';

		$int_count = count( $this->arr_formfield_options );
		for ( $i = 0; $i < $int_count; $i++ ) {
			$str_selected = $this->field_value == $this->arr_formfield_options[ $i ][0] ? 'selected' : '';

			$str_html .= '<option value="' . $this->arr_formfield_options[ $i ][0] . '" ' . $str_selected . ' >' .
				$this->arr_formfield_options[ $i ][1] . '</option>';
		}

		$str_html .= '</select></div>';
		return $str_html;
	}


	/**
	 * Return the HTML from the give form field
	 *
	 * @return string
	 */
	private function get_fieldprop_input() {
		$str_html = $this->arr_single_formfield['custom'];
		$str_options = explode( ';', $this->str_options );
		$field_id = $this->arr_single_formfield['field_id'];
		$arr_errors = true === key_exists( $field_id, $this->arr_errors ?? array() ) ? $this->arr_errors[ $field_id ] : array();
		foreach ( $str_options as $int_ff_id ) {
			if ( 'fieldprop' != $this->arr_single_formfield[ $int_ff_id ]['type'] ) {
				$obj_formfields = new Verowa_Formfields_Rendering(
					$this->arr_single_formfield[ $int_ff_id ],
					$this->str_field_name,
					'fieldprop'
				);

				if ( isset( $this->field_value ) ) {
					$obj_formfields->set_field_value( $this->field_value );
				}

				$obj_formfields->error_handler( $arr_errors );

				$str_field_html = $obj_formfields->get_formfield_html();
			}

			// Replaces the placeholder in the template with the formfield HTML.
			$str_html = str_replace( '[[' . $int_ff_id . ']]', $str_field_html, $str_html );
		}
		return $str_html;
	}



	/**
	 *
	 *
	 * @return string
	 */
	private function get_checkbox_input() {
		$str_checked = $this->field_value == 'on' ? 'checked' : '';

		if ( '' != $this->str_error_html ) {
			$this->arr_input_css_classes = array( 'verowa_input_has_error', 'pp_input_has_error' );
		}

		if ( key_exists( 'additional_css_classes', $this->arr_config ) ) {
			$this->arr_input_css_classes[] = trim( $this->arr_config['additional_css_classes'] );
		}

		$str_input_css_classes = implode( ' ', $this->arr_input_css_classes );
		$str_input_class = strlen( $str_input_css_classes ) ? ' ' . $str_input_css_classes : '';

		return '<div class="ct-input verowa-single-checkbox' . $str_input_class .
			'" data-id="' . $this->str_field_id . '" >' .
			'<label for="' . $this->str_field_name . '">' .
			'<input type="hidden" name="' . $this->str_field_name . '" value="off" />' .
			'<input type="checkbox" name="' . $this->str_field_name . '" id="'
			. $this->str_field_name . '" value="on" ' . $str_checked . ' />' .
			trim( $this->arr_single_formfield['label'] . $this->str_required ) . '&nbsp;' . $this->str_helptext_html .
			'</label>' . $this->str_error_html . '</div>';
	}



	/**
	 * Generat radio buttons fields
	 *
	 * @return string
	 */
	private function get_radio_input() {
		if ( '' !== $this->str_error_html ) {
			$this->arr_input_css_classes = array( 'verowa_input_has_error', 'pp_input_has_error' );
		}

		if ( key_exists( 'additional_css_classes', $this->arr_config ) ) {
			$this->arr_input_css_classes[] = trim( $this->arr_config['additional_css_classes'] );
		}

		$str_input_css_classes = implode( ' ', $this->arr_input_css_classes );
		$str_input_class = strlen( $str_input_css_classes ) ? ' ' . $str_input_css_classes : '';

		$str_html = '<div class="ct-input verowa-input-radio' . $str_input_class . '" data-id="' .
			$this->str_field_id . '" >';
		$int_field_id = $this->arr_single_formfield['field_id'];

		$this->arr_formfield_options = verowa_slice_formfield_options( $this->str_options, 'label_and_value' );

		$str_html .= '<label for="' . $this->str_field_name . '">' .
			$this->arr_single_formfield['label'] . $this->str_required . '&nbsp;' .
			$this->str_helptext_html . '</label>' .
			'<div class="verowa-input-radio-container subs-input-radio-container">' .
			'<input type="hidden" value="" name="' . $this->str_field_name . '" />';

		$str_selected = '';
		$int_count_formfield_options = count( $this->arr_formfield_options );
		for ( $i = 0; $i < $int_count_formfield_options; $i++ ) {

			$str_selected = '';
			if ( $this->field_value == $this->arr_formfield_options[ $i ][0] ) {
				$str_selected = 'checked';
			}

			$str_html .= '<div class="verowa-radio-input subs-input-radio"><label for="cf_rb_id_' . $int_field_id
				. '_' . $i . '">';

			$str_html .= '<input type="radio" name="' . $this->str_field_name .
				'" id="cf_rb_id_' . $int_field_id . '_' . $i . '" ' .
				'value="' . $this->arr_formfield_options[ $i ][0] . '" ' . $str_selected . ' />&nbsp;' .
				$this->arr_formfield_options[ $i ][1] . '<br />' .
				'</label></div>';
		}

		$str_html .= $this->str_error_html . '</div></div>';

		return $str_html;
	}




	/**
	 *
	 * @return string
	 */
	private function get_date_input() {
		$str_min_max = $this->get_max_min_for_input();
		if ( '' !== $this->str_error_html ) {
			$this->arr_input_css_classes = array( 'verowa_input_has_error', 'pp_input_has_error' );
		}

		if ( key_exists( 'additional_css_classes', $this->arr_config ) ) {
			$this->arr_input_css_classes[] = trim( $this->arr_config['additional_css_classes'] );
		}

		$str_input_css_classes = implode( ' ', $this->arr_input_css_classes );
		$str_input_class = strlen( $str_input_css_classes ) ? ' class="' . $str_input_css_classes . '" ' : ' ';

		$str_html = '<div class="verowa-input ct-input" data-id="' . $this->str_field_id . '">' .
			'<label for="' . $this->str_field_name . '">' .
			$this->arr_single_formfield['label'] . $this->str_required . '&nbsp;' .
			$this->str_helptext_html . '</label><input type="date" name="' . $this->str_field_name . '"' .
			$str_input_class . 'id="' . $this->str_field_name . '" value="' . $this->field_value . '" ' .
			$str_min_max . ' />' . $this->str_error_html . '</div>';

		return $str_html;
	}




	/**
	 *
	 *
	 * @return string
	 */
	private function get_datetime_input() {
		$str_div_class = '';
		$str_affected = '';
		if ( '' !== $this->str_error_html ) {
			$this->arr_input_css_classes = array( 'verowa_input_has_error', 'pp_input_has_error' );
		}

		if ( key_exists( 'additional_css_classes', $this->arr_config ) ) {
			$this->arr_input_css_classes[] = trim( $this->arr_config['additional_css_classes'] );
		}

		$str_input_css_classes = implode( ' ', $this->arr_input_css_classes );
		$str_input_class = strlen( $str_input_css_classes ) ? ' class="' . $str_input_css_classes . '" ' : ' ';

		if ( key_exists( 'related_fields', $this->arr_single_formfield ) ) {
			$str_div_class = '' != $this->arr_single_formfield['related_fields'] ? 'ff_renting_' .
				$this->arr_single_formfield['related_fields'] : '';
		}

		if ( key_exists( 'affected_items', $this->arr_single_formfield ) ) {
			$str_affected = '' !== $this->arr_single_formfield['affected_items'] ? 'data-affecteditems="' .
				$this->arr_single_formfield['affected_items'] . '"' : '';
		}

		$str_arr_field_name = $this->str_field_name;
		$str_date = null != $this->field_value && key_exists( $this->str_field_name . '_date', $this->field_value ) ?
			$this->field_value[ $this->str_field_name . '_date' ] : '';
		$str_time_from = null != $this->field_value && key_exists( $this->str_field_name . '_from', $this->field_value ) ?
			$this->field_value[ $this->str_field_name . '_from' ] : '';
		$str_time_to = null != $this->field_value && key_exists( $this->str_field_name . '_to', $this->field_value ) ?
			$this->field_value[ $this->str_field_name . '_to' ] : '';

		$str_html = '<div class="' . $str_div_class . '" ' . $str_affected . 'data-id="' . $this->str_field_id . '" >' .
			'<label for="' . $this->str_field_name . '_' . $this->arr_single_formfield['field_id'] . '">' .
			$this->arr_single_formfield['label'] . '&nbsp;' . $this->str_required .
			'<input type="date" name="' . $str_arr_field_name . '[' . $this->str_field_name . '_date]"' .
			$str_input_class . 'id="' . $this->str_field_name .
			'_' . $this->arr_single_formfield['field_id'] . '" value="' . $str_date . '" /></label>' .
			'&nbsp;von:&nbsp;<input type="time" name="' . $str_arr_field_name .
			'[' . $this->str_field_name . '_from]" value="' . $str_time_from . '" />&nbsp;bis:&nbsp;<input type="time" name="' .
			$str_arr_field_name . '[' . $this->str_field_name . '_to]" value="' . $str_time_to . '" />' . $this->str_error_html . '</div>';

		return $str_html;
	}



	/**
	 *
	 * @return string
	 */
	private function get_contact_input() {

		$str_checked = key_exists( 'billing_is_different', $this->field_value ?? array() ) &&
			'on' === $this->field_value['billing_is_different'] ? 'checked' : '';
		$has_billing = 'no_billing' === $this->arr_single_formfield['options'] ? false : true;
		$str_html = '<div class="vc_renting_form_address_wrapper" >';

		if ( strlen( trim( $this->arr_single_formfield['label'] ?? '' ) ) > 0 ) {
			$str_html .= '<h3>' . $this->arr_single_formfield['label'] . '</h3>';
		}

		if ( true === $has_billing ) {
			$str_tf = verowa_tf( 'Different billing address', __( 'Different billing address', 'verowa-connect' ) );
			$str_html .= '<div><label for="diffrent_contact"><input type="hidden" name="billing_is_different" value="off"/>' .
				'<input type="checkbox" name="billing_is_different" id="diffrent_contact" value="on" ' . $str_checked .
				' />&nbsp;' . esc_html( $str_tf ) . '</label></div>';
		}

		$str_html .= '<div class="vc_renting_form_contact_wrapper">';

		if ( key_exists( 'renting_persons_contact', $this->arr_single_formfield ) &&
			count( $this->arr_single_formfield['renting_persons_contact'] ) > 0 ) {
			$str_title_style = 'checked' === $str_checked ? '' : 'display: none;';

			$str_html .= '<div id="renting-persons">';
			$str_tf = verowa_tf( 'Contact', __( 'Contact', 'verowa-connect' ) );
			$str_html .= '<h4 style="' . $str_title_style . '" id="rentin-persons-billing-title">' .
				esc_html( $str_tf ) . '</h4>';

			foreach ( $this->arr_single_formfield['renting_persons_contact'] as $renting_form_field ) {
				// Prevents a recursive call.
				if ( 'contact' !== $renting_form_field['type'] ) {
					$obj_formfields = new Verowa_Formfields_Rendering( $renting_form_field, 'arr_renting_persons_contact' );
					if ( true === isset( $this->field_value ) ) {
						$obj_formfields->set_field_value( $this->field_value );
					}

					if ( key_exists( 'renting_persons_contact', $this->arr_errors ?? array() ) ) {
						$obj_formfields->error_handler( $this->arr_errors['renting_persons_contact'] );
					}

					$str_html .= $obj_formfields->get_formfield_html();
				}
			}
			$str_html .= '</div>';
		}

		if ( $has_billing && key_exists( 'renting_persons_billing', $this->arr_single_formfield ) &&
			count( $this->arr_single_formfield['renting_persons_billing'] ) > 0 ) {
			$str_display_style = 'checked' === $str_checked ? '' : 'display: none;';

			$str_html .= '<div id="renting-persons-billing" style="' . $str_display_style . ' ">';
			$str_tf = verowa_tf( 'Billing', __( 'Billing', 'verowa-connect' ) );
			$str_html .= '<h4>' . esc_html( $str_tf ) . '</h4>';

			foreach ( $this->arr_single_formfield['renting_persons_billing'] as $renting_form_field ) {
				// Prevents a recursive call.
				if ( 'contact' !== $renting_form_field['type'] ) {
					$obj_formfields = new Verowa_Formfields_Rendering( $renting_form_field, 'arr_renting_persons_billing' );
					if ( isset( $this->field_value ) ) {
						$obj_formfields->set_field_value( $this->field_value );
					}
					if ( key_exists( 'renting_persons_billing', $this->arr_errors ?? array() ) ) {
						$obj_formfields->error_handler( $this->arr_errors['renting_persons_billing'] );
					}
					$str_html .= $obj_formfields->get_formfield_html();
				}
			}

			$str_html .= '</div>';
		}

		$str_html .= '</div></div>';

		return $str_html;
	}



	/**
	 *
	 * @return string
	 */
	private function get_multiple_choice() {
		$str_div_class = '';
		if ( '' != $this->str_error_html ) {
			$this->arr_input_css_classes = array( 'verowa_input_has_error', 'pp_input_has_error' );
		}

		if ( key_exists( 'additional_css_classes', $this->arr_config ?? []) ) {
			$this->arr_input_css_classes[] = trim( $this->arr_config['additional_css_classes'] );
		}

		$str_input_css_classes = implode( ' ', $this->arr_input_css_classes );
		$str_input_class = strlen( $str_input_css_classes ) ? ' ' . $str_input_css_classes : '';

		$arr_cb_options = verowa_slice_formfield_options( $this->str_options, 'label_and_value' );
		if ( key_exists( 'related_fields', $this->arr_single_formfield ) ) {
			$str_div_class = $this->arr_single_formfield['related_fields'] != '' ?
				' ff_renting_' . $this->arr_single_formfield['related_fields'] : '';
		}

		$str_affected = '';
		if ( key_exists( 'affected_items', $this->arr_single_formfield ) ) {
			$str_affected = $this->arr_single_formfield['affected_items'] != '' ?
				' data-affecteditems="' . $this->arr_single_formfield['affected_items'] . '" ' : '';
		}

		$str_html = '<div class="multiple-choice-block' . $str_div_class . '" ' .
			'data-id="' . $this->str_field_id . '"' . $str_affected . '>' .
			'<input type="hidden" name="' . $this->str_field_name . '" value="" />' .
			'<h4>' . trim(
			$this->arr_single_formfield['label'] . ' ' .
			$this->str_required . ' ' . $this->str_helptext_html
		) . '</h4>' . $this->str_error_html;

		$counter = 0;
		$str_checked = '';
		$arr_default_values = array();

		if ( $this->str_default_value != '' ) {
			$default_value = preg_split( '/[{}]/', $this->str_default_value, -1, PREG_SPLIT_NO_EMPTY );
			$arr_default_values = explode( ';', $default_value[0] );
		}

		foreach ( $arr_cb_options as $cb_option ) {
			$counter++;

			if ( $counter % 2 == 0 ) {
				$str_class = 'rf-option-right';
			} else {
				$str_class = 'rf-option-left';
			}

			if ( is_array( $this->field_value ) ) {
				$str_checked = in_array( $cb_option[0], $this->field_value ) ? 'checked' : '';
			} elseif ( count( $arr_default_values ) > 0 ) {
				$str_checked = in_array( $cb_option[0], $arr_default_values ) ? 'checked' : '';
			}

			// replace $display_name
			$field_name = 'verowa_field_' . $this->arr_single_formfield['field_id'] . '_' . $counter;
			$str_html .= '<div class="' . $str_class . $str_input_class . '"><label for="' . $field_name . '">' .
				'<input type="checkbox" name="' . $this->str_field_name . '[]" value="' .
				$cb_option[0] . '" id="' . $field_name . '" ' . $str_checked .
				' />&nbsp;' . $cb_option[1] . '</label></div>';

		}

		$str_html .= '</div>';
		return $str_html;
	}



	/**
	 *
	 * @return string
	 */
	private function get_hidden_input() {
		return '<input type="hidden" name="' . $this->str_field_name . '" value="' .
			$this->str_default_value . '"/>';
	}



	/**
	 * Can be used for the output of any html input type
	 *
	 * @param string $str_wrapper_class add as CSS class in the wrapper div tag
	 * @param string $str_type html input type
	 *
	 * @return string
	 */
	private function get_general_input( $str_wrapper_class, $str_type ) {
		if ( '' != $this->str_error_html ) {
			$this->arr_input_css_classes = array( 'verowa_input_has_error', 'pp_input_has_error' );
		}

		if ( key_exists( 'additional_css_classes', $this->arr_config ) ) {
			$this->arr_input_css_classes[] = trim( $this->arr_config['additional_css_classes'] );
		}
		$str_input_css_classes = implode( ' ', $this->arr_input_css_classes );
		$str_input_class = strlen( $str_input_css_classes ) ? ' class="' . $str_input_css_classes . '" ' : ' ';
		$str_min_max = $this->get_max_min_for_input();
		$str_disabled = ( $this->arr_single_formfield['is_input_disabled'] ?? false ) ? ' disabled ' : '';

		$str_html = '<div class="' . $str_wrapper_class . '" data-id="' . $this->str_field_id . '" ><label for="' . $this->str_field_name . '">' .
			$this->arr_single_formfield['label'] . $this->str_required . '&nbsp;' .
			$this->str_helptext_html . '</label><input type="' . $str_type . '" name="' . $this->str_field_name .
			'" id="' . $this->str_field_name . '" value="' . $this->field_value . '" ' .
			$str_min_max . $str_input_class . $str_disabled . '/>' . $this->str_error_html . '</div>';
		return $str_html;
	}

	/**
	 *  attribute : supportet types
	 *  max / min:  date, month, week, time, datetime-local, number, and range
	 *  minlength:  text, search, url, tel, email, and password
	 *  maxlength:  text, search, url, tel, email, textarea, and password
	 */
	private function get_max_min_for_input() {
		$str_min_max = '';
		$arr_min_max = array();

		if ( '' != $this->str_options ) {
			$arr_formfield_options = explode( ';', $this->str_options );
			if ( 2 == count( $arr_formfield_options ) ) {
				$int_min = $arr_formfield_options[0];
				$int_max = $arr_formfield_options[1];
			} else {
				$int_min = null;
				$int_max = $arr_formfield_options[0];
			}

			switch ( $this->str_input_type ) {
				// Attr. "min" and "max"
				case 'date':
				case 'month':
				case 'week':
				case 'time':
				case 'datetime-local':
				case 'number':
				case 'range':
				case 'seats':
					if ( null != $int_min ) {
						$arr_min_max[] = 'min="' . $int_min . '"';
					}

					$arr_min_max[] = ' max="' . $int_max . '"';

					// Limit the maximum number of digits.
					if ( 'number' == $this->str_input_type ) {
						$int_min_length = strlen( $int_min );
						$int_max_length = strlen( $int_max );
						$int_length = $int_min_length > $int_max_length ? $int_min_length : $int_max_length;
						$arr_min_max[] = ' maxlength="' . $int_length . '"';
					}
					break;

				// Attr. "minlength" and "maxlength".
				case 'text':
				case 'search':
				case 'url':
				case 'tel':
				case 'email':
				case 'password':
					if ( null != $int_min ) {
						$arr_min_max[] = 'minlength="' . $int_min . '"';
					}

					$arr_min_max[] = ' maxlength="' . $int_max . '"';
					break;

				// maxlength
				case 'textarea':
					$arr_min_max[] = '" maxlength="' . $int_max;
					break;
			}

			$str_min_max = implode( ' ', $arr_min_max );
		}
		return $str_min_max;
	}
}