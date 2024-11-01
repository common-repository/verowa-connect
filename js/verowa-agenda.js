/* Dynamic Agenda JS,
 */

// infinite-scroll
var g_max_loadings = 50; // Maximum number of reloading events
var g_current_batch = 1; // Number of newly loaded entries, The first batch is loaded via PHP.
var g_agenda_is_loading = false;
var g_has_further_results = true;
var g_filter_has_changed = false; // Set to True if the filter has changed during loading.
// Set to false after the first results have been loaded.
// Without this flag, results were loaded twice if less than one screen was displayed.
var g_lock_scroll_load = true;

/*
 *  This prevents a click on the calendar or the details from closing the agenda entry.
 */
function event_handler_detail_link_clickt(e) {
	e.stopPropagation();
}

jQuery(document).ready(function ($) {

	jQuery(document).on('click touch', ".event_list_item a:not(.verowa_excl_stop_propagation)", function (e) {
		e.stopPropagation();
	});

	// toggle for details
	jQuery(document).on('click touch', '.event_list_wrapper .event_list_item', verowa_toggle_event_list_handler);

	jQuery("#vc-agenda-search-wrapper button").click(function (e) {
			e.preventDefault();
			e.stopPropagation();

			if (!g_agenda_is_loading) {
				load_agenda_events(true);
				g_agenda_is_loading = true;
			} else {
				g_filter_has_changed = true;
			}
		});

		jQuery("#vc-agenda-search-input").keyup(function (e) {
			let key_code = e.keyCode || e.which;
			if (key_code == 13) {
				if (!g_agenda_is_loading) {
					load_agenda_events(true);
					g_agenda_is_loading = true;
				} else {
					g_filter_has_changed = true;
				}
			}
		});

	// we need this, that the toogle is not triggered in this moment
	// Event anstelle von e als Parameter kann zu Fehler führen
	jQuery('.details-button').on('click touch', function (e) {
		e.stopPropagation();
	});

	// This is the original function of Isotope.
	// This one has no more "Combinations" ****
	jQuery(document).on('click', '#verowa_event_filters .list_filter a', function () {

		var $this = jQuery(this);
		// don't proceed if already selected
		if ($this.hasClass('selected')) {
			return;
		}

		var $optionSet = $this.parents('.option-set');
		// change selected class
		$optionSet.find('.selected').removeClass('selected');
		$this.addClass('selected');

		if (!g_agenda_is_loading) {
			load_agenda_events(true);
			g_agenda_is_loading = true;
		} else {
			g_filter_has_changed = true;
		}

		return false;
	});

	// on change of datepicker we need to the same as when we change a filter
	jQuery('#verowa_connect_datepicker').change(function () {
		var $this = jQuery(this);

		var selector = '';
		var vlist_ids = '%20';

		if (!g_agenda_is_loading) {
			load_agenda_events(true);
			g_agenda_is_loading = true;
		} else {
			g_filter_has_changed = true;
		}

		return false;
	});
});


// Datepicker Selector
var dateToday = new Date();

jQuery(function () {
	// German strings in here, because we never know what jQuery UI language is set
	jQuery("#verowa_connect_datepicker").datepicker({
		dateFormat: "dd.mm.yy",
		monthNames: ["Januar", "Februar", "März", "April",
			"Mai", "Juni", "Juli", "August", "September",
			"Oktober", "November", "Dezember"
		],
		minDate: dateToday,
		firstDay: 1, // Start with Monday
		dayNamesMin: ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"]
	});

	// Also calendar icon triggers datepicker
	jQuery(".dashicons-calendar-alt").on("click", function () {
		// When you click on the calendar, the date picker also goes to
		jQuery("#verowa_connect_datepicker").focus();

	});

});




function datepicker_to_technical_date() {
	var filterDate = '';
	if (document.getElementById("verowa_connect_datepicker") != null) {
		var filterDateRaw = document.getElementById("verowa_connect_datepicker").value;
		// this is because we need 20181012 format for the date
		var filterDateArr = filterDateRaw.split(".");
		filterDateArr = filterDateArr.reverse();
		filterDate = filterDateArr.join('');
	}

	return filterDate;
}


// infinite-scroll
function load_agenda_events(reload) {

	jQuery("#infinite-scroll-loader").show();

	if (reload) {
		g_current_batch = 1;
		jQuery('.event_list_dynamic_agenda').html("");
		g_has_further_results = true;
	}

	var date_from_datepicker = datepicker_to_technical_date();

	var obj_data = {
		date: date_from_datepicker,
		arr_list_ids: [],
		search_string: jQuery("#vc-agenda-search-input").val() ?? '',
		wpml_language_code: jQuery("#wpml-language-code").val(),
	};

	let verowa_atts_list_id = parseInt(jQuery("#verowa-atts-list-id").val());
	if (!isNaN(verowa_atts_list_id) && 0 !== verowa_atts_list_id) {
		obj_data.arr_list_ids.push(verowa_atts_list_id);
	}

	jQuery('#verowa_event_filters .list_filter a.selected').each(function (index, ele) {
		if (jQuery(ele).attr("data-filter-value") != "")
		{
			obj_data.arr_list_ids.push(parseInt(jQuery(ele).attr("data-filter-value").split('-')[1]));
		}
	});

	jQuery.ajax({
		url: verowa_L10n_agenda.BASE_URL + '/wp-json/verowa/v1/agenda_event/' + g_current_batch + '/',
		type: 'GET',
		data: obj_data,
		contentType: "application/json; charset=utf-8",
		dataType: "JSON",
		success: function (data) {
			g_lock_scroll_load = false;
			g_current_batch++;
			jQuery(".event_list_dynamic_agenda").append(data.content);
			g_agenda_is_loading = false;
			jQuery("#infinite-scroll-loader").hide();

			// No results?
			if (jQuery(".event_list_dynamic_agenda > div").length == 0) {
				jQuery('#no-events-box').show();
			} else {
				jQuery('#no-events-box').hide();
			}
			g_has_further_results = data.has_further_results;

			if (g_has_further_results){
				if (g_filter_has_changed) {
					g_filter_has_changed = false;
					load_agenda_events(true);
				}
			}
		},
		error: function (request, status, error) {
			g_agenda_is_loading = false;
			g_has_further_results = true;
			if (g_filter_has_changed) {
				g_filter_has_changed = false;
				load_agenda_events(true);
			}
		}
	});
}


function bind_infinite_scroll() {

	window.addEventListener('scroll', () => {
		const {
			scrollTop,
			scrollHeight,
			clientHeight
		} = document.documentElement;

		if (!g_lock_scroll_load && scrollTop + clientHeight >= scrollHeight - (2 * clientHeight)
			&& g_current_batch < g_max_loadings && g_has_further_results) {
			if (!g_agenda_is_loading && g_has_further_results) {
				load_agenda_events(false);
				g_agenda_is_loading = true;
			}
		}
	}, {
		passive: true
	});
}


function scroll_to_event(event_id)
{
	if (jQuery(".event-" + event_id).length >= 1 && event_id !== 0 && event_id != null)
	{
		var int_event_offset_top = jQuery(".event-" + event_id).offset().top;
		var int_sticky_navigation = jQuery(".inside-navigation").height() || 0;
		var int_martin_top = 30;

		var scoll_to_px = int_event_offset_top - int_sticky_navigation - int_martin_top;

		jQuery("html, body").animate({
			scrollTop: scoll_to_px
		}, 0);
	}
}


function verowa_agenda_filter_reset()
{
	var obj_date_now = new Date();
	var str_day = '0' + obj_date_now.getDate();
	if (obj_date_now.getDate() > 9)
	{
		str_day = obj_date_now.getDate();
	}

	var int_month = obj_date_now.getMonth() + 1;
	var str_month = '0' + int_month;
	if (int_month > 9) {
		str_month = int_month;
	}

	var str_date_now = str_day + '.' + str_month + '.' + obj_date_now.getFullYear();
	jQuery('#verowa_connect_datepicker').val(str_date_now);
	jQuery("#verowa_event_filters .filter-button.selected").removeClass("selected");
	jQuery("#verowa_event_filters .no-filter:not(.selected)").addClass("selected");

	jQuery('#vc-agenda-search-input').val("");

	load_agenda_events(true);
}


function supports_session_storage() {
	try {
		if ('sessionStorage' in window && window['sessionStorage'] !== null) {
			sessionStorage.setItem("testitem", true);
			sessionStorage.removeItem("testitem");
			return true;
		}
	} catch (e) {
		return false;
	}
}




function verowa_toggle_event_list_handler(e) {
	e.preventDefault();
	var event_id = jQuery(this).attr('data-id');

	if (jQuery(this).find('.toggle_button').hasClass('open')) {
		jQuery('.event-' + event_id + ' .short_text').toggle();
		jQuery('.event-' + event_id + ' .event_button_list').toggle();
		jQuery('.event-' + event_id + ' .event_content').html('');
		jQuery(this).find('.toggle_button.open').removeClass('open');
		jQuery(this).find('.toggle_button').css({ 'transform': 'rotate(0deg)' });
	} else {
		jQuery(this).find('.toggle_button').addClass('open');
		jQuery(this).find('.toggle_button').css({ 'transform': 'rotate(180deg)' });
		jQuery.getJSON({
			url: verowa_L10n_agenda.BASE_URL + "/wp-json/verowa/v1/event/" + event_id,
			data: '',
			success: function (data) {
				jQuery('.event-' + event_id + ' .short_text').toggle();
				jQuery('.event-' + event_id + ' .event_button_list').toggle();

				var expanded_event_html = '<div class="first"></div>';

				if (data['short_desc'] != 'undefined') {
					expanded_event_html += '<p class="short_desc">' + data['short_desc'] + '</p>';
				}

				// Event Meta
				var event_meta = '';

				if (data['catering'] != null && data['catering'].toString() != '') {
					event_meta += ', <span class="catering">' + data['catering'] + '</span>';
				}

				//
				if (data['childcare_text'] != null && data['childcare_text'].toString() != '') {
					event_meta += ', <span class="childcare_text">' + data['childcare_text'] + '</span>';
				}

				//
				if (data['baptism_offer_text'] != null && data['baptism_offer_text'].toString() == 'ja') {
					event_meta += ', <span class="baptism_offer_text">mit Taufe</span>';
				}

				if (event_meta != '') {
					expanded_event_html += '<p class="event_meta">';
					expanded_event_html += event_meta.replace(', ', '');
					expanded_event_html += '</p>';
				}

				// Organizer String
				var organizers = '';

				if (data['organizer'] != null && data['organizer'].toString() !== '') {
					organizers += ', ' + data['organizer']['name'];
				}

				if ('coorganizers' in data) {  
					data['coorganizers'].forEach(function (coorganizer, index) {
						organizers += ', ' + coorganizer['name'];
					});
				}

				if (data['further_coorganizer'] != null && data['further_coorganizer'].toString() !== '') {
					organizers += ', ' + data['further_coorganizer'];
				}

				if (organizers != '') {
					expanded_event_html += '<p class="organizers">';
					expanded_event_html += organizers.replace(', ', '');
					expanded_event_html += '</p>';
				}

				jQuery('.event-' + event_id + ' .event_content').html(expanded_event_html);
			}
		});
	}

}
