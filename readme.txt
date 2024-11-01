=== Verowa Connect ===
Contributors: pictureplanet, michaelpfister
Tags: Verowa, church, events, swiss, Switzerland
Requires at least: 5.8
Tested up to: 6.6.2
Stable tag: 3.0.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Include your Verowa data seamlessly into your WordPress project! Events and persons are fetched on the fly and thus always up to date. Flexible layouts via customizable templates and various shortcode options. Verowa Connect can even handle subscription forms and complex forms for room reservations.

== Description ==

Verowa is a swiss management software for churches and parishes. Verowa connects your team, manages your rooms and equipment, helps to plan events like church services or meetings and organizes your ads, office weeks and much more. Verowa keeps you informed of subsequent changes and also keeps your webpage content up to date with this plugin.

This WordPress plugin is connecting with the [Verowa API](https://api.verowa.ch). To use it on your WordPress project you need to obtain an instance of Verowa and an API key. Please visit the [Verowa website](https://www.verowa.ch) (in german) and contact us to get started.

= Subscriptions =

With Verowa Connect visitors of your website can subscribe to certain events. The number of seats can be limited and Verowa can provide a waiting list automatically if needed. Subscription forms may contain a large variety of input fields. The visitor’s data will be sent directly to Verowa where you can view and edit each individual subscription. (These options require the Verowa subscription module.)

= Room Reservations =

Verowa Connect can display complex room reservation forms, so your visitors can apply for rooms, devices and services. Verowa automatically sends confirmation mails and shows the data in a neat overview for you to check. Once approved one click changes the form data to a Verowa event. (These options require the Verowa room reservation module.)

== Installation ==

1. Install the plugin from the plugin repository and activate it.
1. For the plugin to be able to fetch your Verowa data you need to set the id of your Verowa instance (e.g. "my-parish") and the API key in the Verowa options panel (e.g. "6ba4ceea235de07b258044a0cb3804d2").
1. If you have Automatic Updates enabled you can sit back an relax. Verowa Connect will update itself when a new version is available.
1. You have to create a new page with the shortcode `[verowa_agenda]` in order to show the agenda in your web project.
1. You can show a list of specific events with the shortcode `[verowa_event_list]`. With the attributes `id`, `layer_id` or `target_group` you can limit the events shown to specific events or lists. With the attribute `max` you can set the max amount of events shown. The attribute `max_days` limits how far in advance the events are listed. For further information on these and other shortcodes and attributes please visit the [Verowa Connect documentation](https://www.verowa-connect.ch) (in german).
1. After installing you need to make sure you have a default template for the persondetails, personlists, eventdetails and eventlists selected. You can select them under Settings > Verowa Options.

= Subscription Installation =

1. For the registration process to work properly you need to set up three pages. On the first page you add the shortcode `[verowa_subscription_form]` to display a list of events.
On the second page you add the shortcode `[verowa_subscription_confirmation]` to instruct your subscribers to click on the confirmation link after they have sent the form.
On the third page you add the shortcode `[verowa_subscription_validation]`. This page shows a confirmation message to the users after they have validated their subscription through the confirmation link.
1. Enter the reCaptcha public / secret key under Settings > Verowa Subscriptions. Here you can also configure the info displayed in the form.


== Frequently Asked Questions ==

= How can I style the event list according to my wishes? =
The Verowa templates are very powerful and act like a serial letter: Just enter your desired HTML code and include the provided placeholders like `TITLE`, `LOCATION` or `DATE_FROM_LONG`. For the complete list of placeholders visit the [Verowa Connect documentation](https://www.verowa-connect.ch) (in german).

= Does Verowa Connect run without a Verowa license? =
No. Consider this plugin as a connection between WordPress and Verowa. It stores the data in a couple of custom tables in the WP database to run even if the Verowa server is down. However all data is imported from Verowa or the webforms and cannot be typed in manually in some backend.

= Subscriptions: Can I filter the listed events? =
Yes. The easiest way to show only certain events is to make a list in Verowa and add the list’s ID as an attribute to your shortcode (e.g. `[verowa_subscriptions_form id=34]`).

= I have a problem, who should I contact? =
Call us directly at +41 52 551 04 40.
For specific questions about the WordPress plugin please write an e-mail to <support@verowa.ch>.

== Screenshots ==

1. The Verowa options panel
1. Description settings-panel.JPG: This image shows the settings-panel under Settings > Verowa Subscriptions. Here you can customise the form.


== Changelog ==

= 3.0.1 =
* Added: Templates for the news module.
* Added: Open Graph Meta Tags for event details
* Improved: Subscriptions settings have been integrated into the settings page.
* Improved: Wrapper HTML-div, such as for the event list, are now moved into the corresponding templates.
* Improved: Lesser separate API calls for better performance.

= 3.0.0 =

* Added: The formerly separate Verowa newsletter plugin is now included.
* Added: WPML support for events and templates.
* Added: Placeholder `FILE_LIST` for event list.
* Added: Placeholders `ICAL_EXPORT_URL`, `IMAGE_CAPTION`, `IMAGE_SOURCE_TEXT`, `IMAGE_SOURCE_URL` for event list and event details templates.
* Added: Placeholders `DESC_PERSONAL`, `IMAGE_SOURCE_TEXT`, `IMAGE_SOURCE_URL` for person details templates.
* Added: Header section for templates intended for meta tags.
* Improved: All settings were brought together on one page.
* Bugfix: Dropdown menu of person groups and subgroups in page form.

= 2.14.0 =

* Added: The attribute `filter` for the shortcode `[verowa_event_list]` to restrict the results according to list IDs.
* Added: Support for related fields in room reservation and subscription forms.
* Added: Ability to add additional CSS classes to fields of room reservation and subscription forms.
* Added: Placeholders `BUSINESS_PHONE_NUMBER`, `BUSINESS_MOBILE_NUMBER`, `PRIVATE_PHONE_NUMBER` and `PRIVATE_MOBILE_NUMBER` for single person pages.

= 2.13.2 =

* Bugfix: Display errors for events or people if widget and content templates are active at the same time.
* Bugfix: Layer selections for events are displayed again.

= 2.13.1 =

* Added: Shortcodes `[verowa_urlencode]` and `[verowa_encode_link]`.
* Added: Placeholders `DATETIME_FROM_UTC` and `DATETIME_TO_UTC` for event templates.
* Added: Agenda JS scripts for multi-site installations.
* Bugfix: Toggle function of event list.
* Bugfix: Template table extension.

= 2.13.0 =

* Added: Nested templates to display rosters.
* Added: Event placeholder `LIST_IDS` for if-statements in templates.
* Added: Template option to display all events of the current day.
* Added: If saving a subscription or room rental fails, an error message is displayed to the user.
* Bugfix: The error message above the subscription form is no longer displayed twice.

= 2.12.2 =

* Improved: Agenda fulltext search.
* Bugfix: Display agenda datetime on mobile devices.

= 2.12.1 =

* Bugfix: Reading the agenda filter during the plugin update caused an error.

= 2.12.0 =

* Added: Event placeholders to display the Datetime.
* Added: GET parameters for agenda: `vcat`, `vq` and `vdate`.
* Improved: The shortcode `[verowa_roster_entries]` no longer displays a title.
* Improved: Lesser API calls for better performance.
* Improved: Templates are loaded via WP cache.

= 2.11.5 =

* Improved: Lesser API calls when multiple verowa events are updated at once.

= 2.11.4 =

* Bugfix: Insert query for the person groups.

= 2.11.3 =

* Added: Update post content when the template is updated.
* Improved: Lesser API calls for better performance.

= 2.11.2 =

* Bugfix: Public events without list IDs are assigned correctly to their target groups and layer IDs.

= 2.11.1 =
* Added: Placeholder `PERSON_ID` for person details.
* Added: `[verowa_event_list ...]`: Shows notification when `handle_full` is set and no events have been received.
* Bugfix: Import persons related with events.

= 2.11.0 =

* Added: New shortcode `[verowa_image]` to display a person’s image.
* Improved: Each Verowa template supports shortcodes.
* Change: Shortcode `[verowa_agenda_dynamic]` replaced by `[verowa_agenda]`.
* Change: Shortcode `[verowa_event_liste_dynamic]` was obsolete and has been deleted. Use `[verowa_agenda]` instead.

= 2.10.0 =

* Added: Roster list and first roster entry support templates.
* Added: Javascript validation for subscription Form.
* Change: Shortcode `[verowa_print_subscriptions_form]` deleted.
* Change: Renamed the shortcode `[verowa_subscriptions_form]` to `[verowa_subscription_form]`.
* Change: Renamed the shortcode `[verowa_subscriptions_response]` to `[verowa_subscription_confirmation]`.
* Change: Renamed the shortcode `[verowa_subscriptions_validation_anmeldung]` to `[verowa_subscription_validation]`.
* Change: Renamed the shortcode `[verowa_print_subscriptions_form]` to `[verowa_subscription_overview]`.

= 2.9.2 =

* Added: Javascript validation for renting form.
* Added: Multiple selections in the forms for subscriptions and room reservations support the options "min" and "max" selected items.

= 2.9.1 =

* Added: WP 6.0.0 and PHP 8.1.4 support.
* Added: LiteSpeed support.
* Change: Placeholder `LOCATION_WITH_ROOM` needs a label e.g "Location".
* Change: Placeholder `CALENDAR_EXPORT` has been removed, replace it with e.g. "Calendar export".
* Change: Placeholder `HAS_PRIVATE_ADDRESS` returns 1 instead of "Private:" for more easier use.
* Change: Placeholder `HAS_BUSINESS_ADDRESS` returns 1 instead of "Business:" for more easier use.
* Refactoring: Renting forms

= 2.9.0 =

* Added: Full text search in agenda.
* Added: Placeholders for additional text fields (`ADD_TEXT_1..4`).
* Added: Detail pages of past Verowa events remain accessible via direct link for several days to support outdated newsletter links.
* Added: Verowa events and persons can be excluded for search engine indexes.
* Added: Verowa events have the meta tag "unavailable after" for robots.
* Improved: Deprecated Verowa options removed.
* Bugfix: Delete the temporary user data for the subscription form and the room reservation form

= 2.8.11 =

* Improved: New DB update logic after plugin update.
* Improved: The agenda’s output buffer got removed to prevent memory overflow.
* Bugfix: The date-time picker in the agenda shows the icons again.
* Refactoring of person functions.

= 2.8.10 =

* Bugfix: Phone number validation.
* Bugfix: Update event and person posts after changing the corresponding template.

= 2.8.8 / 2.8.9 =

* Bugfix

= 2.8.7 =

* Improved: When a subscription form cannot be fetched via the API it is loaded from the database cache.
* Bugfix: Display room names.

= 2.8.6 =

* Bugfix: Navigation error on new custom post type.
* Refactoring

= 2.8.5 =

* Added: PHP 8 support
* Added: Verowa events and persons are also stored as custom post type `verowa_event` and `verowa_person`.
* Improved: Form error messages are now displayed below the affected input field.
* Improved: Use of $_SESSION removed.
* Change: Personal details and event details pages must be deleted.
* Refactoring

= 2.8.4 =

* Bugfix: Subscription add person.
* Bugfix: Add further persons.
* Bugfix: Shortcode `[verowa_event_liste_dynamic title]`.

= 2.8.2 / 2.8.3  =

* Bugfix

= 2.8.1 =

* Added: Update list event mapping on post save.
* Bugfixes: Several warnings and notices.

= 2.8.0 =

* Improved: Lesser calls to the Verowa API for better performance.
* Added: Template placeholder for events: `LOCATION`.
* Bugfixes: Several warnings and notices.
* Refactoring

= 2.7.2 =

* Bugfix: Plugin Activation Error: "Headers Already Sent".

= 2.7.1 =

* Added: New template placeholder for persons: `PERSONAL_URL`.
* Added: Extracted Verowa IDs in widget are stored in widget_text.
* Change: The template placeholder `SERVICE_4_PERSONS` requires a label.

= 2.7.0 =

* Improved: Event IDs used in widget are also included when querying the event.
* Change: Subscription buttons in the agenda are displayed without the subscription date.
* Change: Subscription option: new delimiter `||` instead of `;`.
* Bugfix: Subscription from.
* Refactoring

= 2.6.3 =

* Added: With Flexy Breadcrumb, the breadcrumbs are displayed accordingly in the person and event details.
* Added: The improved agenda supports the GET parameter `cat` for categories.
* Bugfix: Subscription form radio button default values and drop-down lists.

= 2.6.2 =

* Added: Verowa templates can now be duplicated.
* Added: New "html" field type for renting forms.
* Bugfix: Renting forms.

= 2.6.1 =

* Added: Verowa Template Editor
* Improved: On activation the plugin creates the generic detail pages for persons and events automatically.
* Bugfix: Sublayer ids

= 2.5.0 =

* Added: Live updates: Verowa event changes can be pushed to Verowa Connect and are immediately updated in the WPDB. Requires Verowa to be configured accordingly.

= 2.4.1 / 2.4.2 =

* Bugfix

= 2.4.0 =

* Improved: Lesser requests to the Verowa API for better performance.
* Bugfix: Event and person updates.

= 2.3.2-4 =

* Bugfix: Display persons and events.
* Minor bugfixes and refactoring.

= 2.3.0 =

* Improved: The agenda filters are now persistent and the scroll position is maintained.
* Bugfix: Error messages for checkboxes in renting forms can be configured.
* Bugfix: Backlinks.
* Added: New placeholders for events: `ORGANIZER_NAME`, `COORGANIZER_NAMES`.

= 2.2.1 =

* Added: New input fields, e.g. date and time, for subscription and renting forms.
* Improved: The subscription and renting forms use the same input control class.
* Refactoring

= 2.2.0 =

* Added: The former separate plugin "Verowa Subscriptions" has been integrated to Verowa Connect.
* Improved: renting forms.
* Refactoring

= 2.1.0 =

* Added: New HTML templates for event details, person details, event lists and person lists. After updating you need to select four default templates in the options for the shortcodes to work.
* Added: New event widget, similar to the person widget.
* Improved: Events, persons and person groups are now stored in the WPDB. They are automatically updated hourly via a cron job.

= 2.0.5 =

* Added: Event and person details are now displayed through pages with shortcodes: `[verowa_generate_post_event]`, `[verowa_generate_post_person]`.
* Added: New rules are automatically added to the htaccess file upon plugin update.
* Added: List title, max amount of events and max days in advance can be adjusted while editing a page.
* Added: Shortcode to display the event lists: `[verowa_event_list]`. The deprecated shortcodes are automatically directed to the new one.
* Refactoring

= 2.0.4 =

* Added: Buttons now lead directly to the subscription form in the agenda.
* Improved: Support for smaller person pictures in pages, events and group-pages.
* Bugfix: Preselected list for sorting the agenda works again.

= 2.0.3 =

* Bugfix: Slashes in captions no longer cause trouble.

= 2.0.2 =

* Change: Person’s HTML structure improved.

= 2.0.1 =

* Added: Runs now on PHP 7.4.

= 2.0.0 =

* Added: New shortcode: `verowa-first-roster-entry`.
* Change: Code for localization refactored.
* Change: Roster functionality refactored.

= 1.9.5 =

* Added: an option to show/hide the event organizer in event lists and detail pages.
* Bugfixes and broad refactoring.

= 1.9.1 =

* Change: This version merges several forks that had hard coded values and altered features. It brings the plugin back to an equal code base.
* Change: The plugin folder’s name has changed from "verowa-connect-plugin" to "verowa-connect".