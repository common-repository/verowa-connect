jQuery(document).ready(function () {
	let str_value = jQuery('input[type=radio][name=verowa_select_list_settings]:checked').val();
	if (null != str_value ) {
		verowa_list_option(str_value);
	}

	jQuery('input[type=radio][name=verowa_select_list_settings]').change(function () {
		verowa_list_option( this.value );
	});

	jQuery(".verowa-ddl-list-settings").change(function () {
		jQuery(this).closest('tr').find('input[type=radio]').prop('checked', true);
		verowa_list_option(jQuery(this).closest('tr').find('input[type=radio]').val());
	});

	jQuery("#template_edit_form select[name=type]").on("change", function () {
		let selected_val = jQuery("#template_edit_form select[name=type]").val();
		if (selected_val == "persondetails" || selected_val == "eventdetails") {
			jQuery("#verowa_head_wrapper").show();
		} else {
			jQuery("#verowa_head_wrapper").hide();
		}
	});

	jQuery(".verowa-options-wrapper *[role=button]").on("click", function (event) {
		event.preventDefault();
		event.stopPropagation();
		let x = event.pageX - jQuery('.verowa-options-wrapper .tab-content').offset().left;
		let y = event.pageY - jQuery('.verowa-options-wrapper .tab-content').offset().top + 15;
		let original_string = jQuery(this).closest('tr').find('*[data-en]').data('en');
		let lang_code = jQuery(this).data('langcode');
		let lang_title = jQuery(this).closest("table").find(".verowa-lang-title-" + lang_code).text();
		let str_translation = jQuery(this).data("text");

		jQuery("#verowa-wpml-translation-popup .verowa-translation-lang").text(lang_title);
		jQuery("#verowa-wpml-translation-popup .original_string").val(original_string);
		jQuery("#verowa-wpml-translation-popup .translated_string").val(str_translation);
		jQuery("#verowa-wpml-translation-popup *[name=lang_code]").val(lang_code);
		jQuery("#verowa-wpml-translation-popup").css("top", y + "px");
		jQuery("#verowa-wpml-translation-popup").show();
	});

	jQuery("#verowa-wpml-translation-popup .close-button").on("click", function (event) {
		event.preventDefault();
		event.stopPropagation();
		jQuery("#verowa-wpml-translation-popup").hide();
	});

	jQuery("#verowa-wpml-translation-popup .verowa-save-button").on("click", function (event) {
		event.preventDefault();
		event.stopPropagation();
		let original = jQuery("#verowa-wpml-translation-popup .original_string").val();
		let translated = jQuery("#verowa-wpml-translation-popup .translated_string").val();
		let lang_code = jQuery("#verowa-wpml-translation-popup *[name=lang_code]").val();
		
		$obj_data = {
			original_string: original,
			translated_string: translated,
			lang_code: lang_code,
		};

		jQuery.ajax({
			url: '/wp-json/verowa/v1/update_translation', // REST-API Endpunkt
			method: 'POST',
			data: $obj_data,
			xhrFields: {
				withCredentials: true
			},
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
			},
			success: function (response) {
				if (true == response.success) {
					let $insert_btn = jQuery('td[data-en="' + response.original_string + '"]').closest('tr')
						.find('*[data-langcode=' + response.lang_code + '].dashicons-insert');
					let $edit_btn = jQuery('td[data-en="' + response.original_string + '"]').closest('tr')
						.find('*[data-langcode=' + response.lang_code + '].dashicons-edit');
					$edit_btn.data('text', response.translated_string);

					if ('' == response.translated_string) {
						$insert_btn.show();
						$edit_btn.hide();
					} else { 
						$insert_btn.hide();
						$edit_btn.show();
					}

					jQuery("#verowa-wpml-translation-popup").hide();
				}
				console.log(response.message); // Ausgabe der Erfolgsmeldung

			},
			error: function (error) {
				console.error('Fehler:', error);
			}
		});
	});
});



function verowa_list_option( str_value ) {
	jQuery('td.verowa-list-option').closest('tr').find('select').prop('disabled', true);
	jQuery('td.verowa-list-option input[type=radio][value=' + str_value + ']')
		.closest('tr').find('select').prop('disabled', false);
	jQuery(".verowa-ddl-list-settings:disabled").val('');
}