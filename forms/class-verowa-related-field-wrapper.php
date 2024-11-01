<?php
/**
 * This class is used to generate a wrapper element for related form fields and provide JavaScript code for interacting with these fields.
 *
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @version 1.0
 * @package VEROWA CONNECT
 * @subpackage Forms
 */

/**
 * Class Verowa_Related_Field_Wrapper
 */
class Verowa_Related_Field_Wrapper {

	public $prev_related_field_id = 0;
	public $bool_open_rf_wrapper = false;

	public function __construct() {}

	/**
	 * Displays the wrapper for a related form field.
	 *
	 * @param array $arr_single_formfield An array containing information about the form field.
	 */
	public function show_wrapper ( &$arr_single_formfield ) {
		$arr_related_fields = explode( ':', $arr_single_formfield['related_fields'] );
		$int_related_field_id = intval( $arr_related_fields[0] ?? 0 );
		$str_related_value = $arr_related_fields[1] ?? '';

		if ( $this->prev_related_field_id !== $int_related_field_id ) {
			if ( true === $this->bool_open_rf_wrapper ) {
				echo '</div>';
				$this->bool_open_rf_wrapper = false;
			}

			if ( $int_related_field_id > 0 ) {
				echo '<div class="verowa-related-field-wrapper" style="display:none;" data-relatedfield="' . $int_related_field_id . '" ';
				$this->bool_open_rf_wrapper = true;
				if ( is_string( $str_related_value ) && strlen( $str_related_value ) > 0 ) {
					echo 'data-relatedvalue="' . trim( $str_related_value ) . '" ';
				}

				echo '>';
			}
		}
		$this->prev_related_field_id = $int_related_field_id;
	}

	public function get_wrapper( &$arr_single_formfield )
	{
		ob_start ();
		$this->show_wrapper ( $arr_single_formfield );
		return ob_get_clean ();
	}

	/**
	 * Closes open wrapper elements for related form fields.
	 */
	public function close_open_wrapper() {
		// If a related field wrapper still opened, the div must be closed here.
		if ( true === $this->bool_open_rf_wrapper ) {
			echo '</div>';
		}
	}

	/**
	 * Generates JavaScript code for interacting with the related form fields.
	 */
	public function print_js() {
?>
		<script>
			function verowa_rel_mc_handler($ele, field_id) {
				let cb_id = $ele.attr("id");
				let str_label = jQuery("*[for=" + cb_id + "]").text();
				if ($ele[0].checked == true) {
					jQuery("div[data-relatedfield=" + field_id + "][data-relatedvalue='" + str_label.trim() + "']").stop().slideDown(300);
				} else {
					let $div_wrapper = jQuery("div[data-relatedfield=" + field_id + "][data-relatedvalue='" + str_label.trim() + "']");
					$div_wrapper.stop().slideUp(300);
					verowa_clear_form_fields($div_wrapper);
				}
			}

			function verowa_rel_rb_handler($ele, field_id) {
				let rb_name = $ele.attr("name");
				let selected_rb_id = jQuery('input[name=' + rb_name + ']:checked').attr('id');
				let str_label = jQuery("*[for=" + selected_rb_id + "]").text();

				let $div_wrapper = jQuery("div[data-relatedfield=" + field_id + "]:not([data-relatedvalue='" + str_label.trim() + "'])");
				$div_wrapper.stop().slideUp(300);
				verowa_clear_form_fields($div_wrapper);

				jQuery("div[data-relatedfield=" + field_id + "][data-relatedvalue='" + str_label.trim() + "']").stop().slideDown(300);
			}

			jQuery("div[data-relatedfield]").each(function (event) {
				let field_id = jQuery(this).data("relatedfield");

				// Type: Checkbox
				let cb_selector = "*[name=field_" + field_id + "][type=checkbox], *[name=customfield_" + field_id + "][type=checkbox]";
				jQuery(cb_selector).click(function (event) {
					if (jQuery(this)[0].checked == true) {
						jQuery("div[data-relatedfield=" + field_id + "]").stop().slideDown(300);
					} else {
						let $div_wrapper = jQuery("div[data-relatedfield=" + field_id + "]");
						$div_wrapper.stop().slideUp(300);
						verowa_clear_form_fields($div_wrapper);
					}
				});

				let $cb = jQuery(cb_selector);

				if ($cb.length > 0 && $cb[0].checked == true) {
					jQuery("div[data-relatedfield=" + field_id + "]").stop().slideDown(300);
				} else {
					let $div_wrapper = jQuery("div[data-relatedfield=" + field_id + "]");
					$div_wrapper.stop().slideUp(300);
					verowa_clear_form_fields($div_wrapper);
				}


				let mc_selector = "*[data-id=field_" + field_id + "].multiple-choice-block *[type=checkbox], *[data-id=customfield_" + field_id + "].multiple-choice-block *[type=checkbox]";
				jQuery(mc_selector).click(function (event) {
					verowa_rel_mc_handler(jQuery(this), field_id);
				});

				// on load prüfen
				jQuery(mc_selector).each(function () {
					verowa_rel_mc_handler(jQuery(this), field_id);
				});

				let rb_selector = "*[name=field_" + field_id + "][type=radio], *[name=customfield_" + field_id + "][type=radio]";
				jQuery(rb_selector).click(function (event) {
					verowa_rel_rb_handler(jQuery(this), field_id);
				});

				// on load rb prüfen
				jQuery(rb_selector).each(function () {
					verowa_rel_rb_handler(jQuery(this), field_id);
				});

				// DDL-Menü
				let ddl_selector = "select[name=field_" + field_id + "], select[name=customfield_" + field_id + "]";
				jQuery(ddl_selector).change(function (event) {
					let str_value = jQuery(this).val();
					let $div_wrapper = jQuery("div[data-relatedfield=" + field_id + "]:not([data-relatedvalue='" + str_value + "'])");
					$div_wrapper.stop().slideUp(300);
					verowa_clear_form_fields($div_wrapper);

					jQuery("div[data-relatedfield=" + field_id + "][data-relatedvalue='" + str_value + "']").stop().slideDown(300);
				});
			});
		</script>

		<?php
	}
}