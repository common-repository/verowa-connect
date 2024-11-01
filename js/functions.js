
jQuery(document).ready(function () {
	jQuery("#diffrent_contact").change(function () {
		if ( this.checked ) {
			jQuery('#rentin-persons-billing-title').show();
			jQuery('#renting-persons').show();
			jQuery('#renting-persons-billing').show();
		} else {
			jQuery('#rentin-persons-billing-title').hide();
			jQuery('#renting-persons-billing').hide();
		}
	});

	jQuery('.has_related_fields').change(function () {
		jQuery('.' + jQuery(this).data('relatedclass')).hide();
		jQuery('.' + jQuery(this).data('relatedclass') + '[data-affecteditems=' + jQuery(this).val() + ']').show();
	});

	if (history.length > 1) {
		jQuery(".back-link:eq(0)").show();
	}

	if ( 0 != jQuery('#verowa_renting_form_submit').length ) {
		jQuery('#verowa_renting_form_submit').click(function (event) {
			event.preventDefault();
			event.stopPropagation();
			let arr_form_data = jQuery(this).closest("form").serializeArray();
			let obj_form_data = verowa_assemble_form_data(arr_form_data);

			verowa_add_loader_animation(jQuery(this), 'after');

			jQuery.ajax({
				method: 'POST',
				url: verowa_L10n_functions.BASE_URL + '/wp-json/verowa/v1/save_renting_request',
				data: JSON.stringify(obj_form_data),
				contentType: "application/json; charset=utf-8",
				dataType: 'json',
				success: function (data) {
					if (data.arr_errors.fields != null && data.arr_errors.general)
					{
						let arr_field_ids = Object.keys(data.arr_errors.fields);
						let arr_general_keys = Object.keys(data.arr_errors.general);
						if ( arr_field_ids.length > 0 || arr_general_keys.length > 0 ) {
							verowa_show_api_errors( data, '.renting-formfields' );
							verowa_remove_loader();
						} else {
							window.location.href = data.response_url;
						}
					}
				},
				error: function (error) {
					let str_error = '<div class="verowa_connect_error_box"><ul>' +
						'<li>' + verowa_L10n_functions.api_error_save_renting + '</li>' +
						'</ul></div>';
					if (0 == jQuery(".verowa_connect_error_box").length) {
						jQuery('.renting-formfields').prepend(str_error);
					} else {
						jQuery('.renting-formfields .verowa_connect_error_box').replaceWith(str_error);
					}

					let int_scroll_top = jQuery('.verowa_connect_error_box').offset().top - 200;
					if (int_scroll_top < 0) {
						int_scroll_top = 0;
					}
					window.scrollTo(0, int_scroll_top);
					verowa_remove_loader ();
				}
			});
		});
	}

	if ( 0 != jQuery('#verowa_subs_form_submit').length ) {
		jQuery('#verowa_subs_form_submit').click(function (event) {
			event.preventDefault();
			event.stopPropagation();

			jQuery(this).attr("disabled", "disabled");
			let arr_form_data = jQuery(this).closest("form").serializeArray();
			let obj_form_data = verowa_assemble_form_data(arr_form_data);

			let int_seats_count = 0;
			for (let single_field of arr_form_data) {
				// If it has several seats fields, we add them together
				if (-1 !== single_field.name.indexOf('nb_seats_')) {
					let single_seats = parseInt(single_field.value);
					if (false == isNaN(single_seats) && single_seats != null) {
						int_seats_count += single_seats;
					}
				}
			}

			if (int_seats_count > 0) {
				obj_form_data.nb_seats = int_seats_count;
			}

			verowa_add_loader_animation(jQuery(this), 'after');

			jQuery.ajax({
				method: 'POST',
				url: verowa_L10n_functions.BASE_URL + '/wp-json/verowa/v1/save_subs_request',
				data: JSON.stringify(obj_form_data),
				contentType: "application/json; charset=utf-8",
				dataType: 'json',
				success: function (data) {
					let arr_field_ids = Object.keys(data?.arr_errors?.fields || []);
					let arr_general_keys = Object.keys(data?.arr_errors?.general || []);
					let is_remove_loader = false;

					if (typeof data.message.trim != 'undefined' && 0 === data.message.trim().length) {
						if (arr_field_ids.length > 0 || arr_general_keys.length > 0) {
							verowa_show_api_errors(data, '.verowa-subscription-form:eq(0)');
							is_remove_loader = true;
						} else {
							window.location.href = data.redirect_url;
						}
					} else if ('error' == data.subs_state) {
						verowa_add_error_box(['message'], data, '.verowa-subscription-form:eq(0)');
						is_remove_loader = true;
					} else if ('ok' == data.subs_state) {
						window.location.href = data.redirect_url;
					}

					if (is_remove_loader) {
						jQuery('#verowa_subs_form_submit').prop("disabled", false);
						verowa_remove_loader();
					}
				},
				error: function (error) {
					console.log(verowa_L10n_functions);
					let str_error = '<div class="verowa_connect_error_box"><ul>' +
						'<li>' + verowa_L10n_functions.api_error_save_renting + '</li>' +
						'</ul></div>';
					if (0 == jQuery(".verowa_connect_error_box").length) {
						jQuery('.verowa-subscription-form:eq(0)').prepend(str_error);
					} else {
						jQuery('.verowa-subscription-form .verowa_connect_error_box').replaceWith(str_error);
					}

					let int_scroll_top = jQuery('.verowa_connect_error_box').offset().top - 200;
					if (int_scroll_top < 0) {
						int_scroll_top = 0;
					}
					window.scrollTo(0, int_scroll_top);
					jQuery('#verowa_subs_form_submit').prop("disabled", false);
					verowa_remove_loader ();
				}
			});
		});
	}

	if (0 != jQuery('#verowa_renting_form_submit').length || 0 != jQuery('#verowa_subs_form_submit').length ) {
		jQuery(':checkbox').change(function (e) {
			jQuery(this).closest('div').find('.pp_inline_error_msg').remove();
			jQuery(this).closest('div.multiple-choice-block').find('.pp_inline_error_msg').remove();
			if (0 == jQuery('.pp_inline_error_msg').length) {
				jQuery('.verowa_connect_error_box').remove();
			}
		});

		jQuery('.renting-formfields input:not(:checkbox), .renting-formfields select, .renting-formfields textarea,' +
			' .verowa-subscription-form input:not(:checkbox), .verowa-subscription-form select, .verowa-subscription-form textarea').on('keyup change', function (e) {
			jQuery(this).next('.pp_inline_error_msg').remove();
			jQuery(this).removeClass('pp_input_has_error');
			jQuery(this).closest('div.verowa-input-radio').find('.pp_inline_error_msg').remove();
			if (0 == jQuery('.pp_inline_error_msg').length) {
				jQuery('.verowa_connect_error_box').remove();
			}
		});
	}

	jQuery('.verowa-renting-formfields input[type=number]').keyup(function (e) {
		let maxlength = parseInt(jQuery(this).attr('maxlength'));
		jQuery(this).val( jQuery(this).val().substring( 0, maxlength ) );
	});

	if (0 != jQuery('#verowa-resend-subs-form-submit').length) {
		jQuery('#verowa-resend-subs-form-submit').click(function (event) {
			event.preventDefault();
			event.stopPropagation();
			let obj_post_var = {};
			let $divWrapper = jQuery(this).closest("div");

			obj_post_var.event_id = $divWrapper.find("input[name=event_id]").val();
			obj_post_var.subs_id = $divWrapper.find("input[name=subs_id]").val();
			obj_post_var.email = $divWrapper.find("input[name=email]").val();

			jQuery.ajax({
				method: 'POST',
				url: verowa_L10n_functions.BASE_URL + '/wp-json/verowa/v1/resend_subscription_mail',
				data: JSON.stringify(obj_post_var),
				contentType: "application/json; charset=utf-8",
				dataType: 'json',
				success: function (data) {
					if (true === data.mail_sent) {
						$divWrapper.addClass("verowa-resend-success");
						$divWrapper.html(data.message);
					} else {
						$divWrapper.addClass("verowa-resend-error");
						$divWrapper.html(data.message);
					}
				},
				error: function (err) {
					$divWrapper.addClass("verowa-resend-error");
					$divWrapper.html(err.message);

				},
			});

		});
	}
});




function verowa_do_history_back () {
	// window.history.pushState({ prevUrl: window.location.href }, null, "/agenda/");
	window.history.back();
	return false;
}




function verowa_show_api_errors (data, form_selector) {
	let arr_field_ids = Object.keys(data.arr_errors?.fields);
	let arr_general_keys = Object.keys(data.arr_errors?.general);

	if ( arr_general_keys.length > 0 ) {
		verowa_add_error_box(arr_general_keys, data.arr_errors.general, form_selector);
	} else if (jQuery(".verowa_connect_error_box").length > 0) {
		jQuery(".verowa_connect_error_box").remove();
	}

	if (arr_field_ids.length > 0) {
		for (let single_field_id of arr_field_ids) {
			let value = data.arr_errors.fields[single_field_id];
			let is_value_objejct = typeof value === 'object' && value !== null;

			// dann ist es eine Gruppe
			if (is_value_objejct) {
				let arr_keys = Object.keys(value);
				for (let single_key of arr_keys) {
					let str_value = value[single_key];
					let str_html = '<p class="pp_inline_error_msg verowa-inline-error-msg" >' +
						str_value + '</p>';
					verowa_add_error_message(single_key, str_html);
				}
			} else {
				let str_html = '<p class="pp_inline_error_msg verowa-inline-error-msg" >' +
					value + '</p>';
				verowa_add_error_message(single_field_id, str_html);
			}
		}
	}
}




function verowa_add_error_box( arr_keys, arr_errors, form_selector ) {
	let str_error = '<div class="verowa_connect_error_box"><ul>';
	for (let str_error_key of arr_keys) {
		str_error += '<li>' + arr_errors[str_error_key] + '</li>';
	}
	str_error += '</ul></div>';
	if (0 == jQuery(".verowa_connect_error_box").length) {
		jQuery(form_selector).prepend(str_error);
	} else {
		jQuery(form_selector + ' .verowa_connect_error_box').replaceWith(str_error);
	}

	let int_scroll_top = jQuery('.verowa_connect_error_box').offset().top - 200;
	if (int_scroll_top < 0) {
		int_scroll_top = 0;
	}
	window.scrollTo(0, int_scroll_top);
}




/**
 *
 * @param {any} arr_form_data
 */
function verowa_assemble_form_data( arr_form_data ) {
	let obj_form_data = {};
	for (let single_form_data of arr_form_data) {
		let str_name = single_form_data.name;
		// multiple choice
		if (str_name.indexOf('[]') != -1) {
			str_name = str_name.replace('[]', '');
			// durch das hidden field ist der erste eintrag ein string.
			if (!obj_form_data.hasOwnProperty(str_name) ||
				'function' !== typeof obj_form_data[str_name].push) {
				obj_form_data[str_name] = [];
			}
			obj_form_data[str_name].push(single_form_data.value);
		} else if (str_name.indexOf('[') != -1 || str_name.indexOf(']') != -1) {
			// e.g. contact, date and time from/to
			let arr_keys = str_name.substring(0, str_name.length - 1).split('[');
			if (!obj_form_data.hasOwnProperty(arr_keys[0])) {
				obj_form_data[arr_keys[0]] = {};
			}

			obj_form_data[arr_keys[0]][arr_keys[1]] = single_form_data.value;
		} else {
			// default
			obj_form_data[str_name] = single_form_data.value;
		}
	}
	return obj_form_data;
}




function verowa_add_error_message (field_id, html) {
	if (0 == jQuery('div[data-id=' + field_id + '] .pp_inline_error_msg').length) {
		jQuery('div[data-id=' + field_id + '] input').addClass('pp_input_has_error');
		jQuery('div[data-id=' + field_id + ']').append(html);
	} else {
		jQuery('div[data-id=' + field_id + '] .pp_inline_error_msg').replaceWith(html);
	}
}




/**
 * Add the loading animation of a form
 * @param {object} $ele jQuery object.
 * @param {string} mode String to define the way the loader is added. "after"
 */
function verowa_add_loader_animation( $ele, mode ) {
	let str_loader = '<img src="' + verowa_L10n_functions.BASE_URL + '/wp-content/plugins/verowa-connect/images/ajax-loader.gif" class="verowa-ajax-loader" />';
	switch (mode) {
		case 'after':
			$ele.after(str_loader);
			break;

		default:
			console.log("verowa_add_loader: mode not available")
			break;
	}
}




/**
 * Removes the loading animation of a form
 */
function verowa_remove_loader() {
	jQuery(".verowa-submit-wrapper .verowa-ajax-loader").fadeOut(300, function() {
		jQuery(this).remove();
	});
}




function verowa_clear_form_fields(container) {
	container.find(':input').each(function () {
		var elementType = this.type;
		if (elementType === 'text' || elementType === 'password' || elementType === 'textarea') {
			jQuery(this).val('');
		} else if (elementType === 'radio' || elementType === 'checkbox') {
			this.checked = false;
		} else if (elementType === 'select-one') {
			jQuery(this).val('');
		}
	});
}