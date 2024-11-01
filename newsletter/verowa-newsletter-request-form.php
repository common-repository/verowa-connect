<?php
/**
 * This is the small newsletter form that can be integrated using [verowa_newsletter_request_form slug="newsletter"].
 * It's important to use the slug from the large form (without trailing and ending slashes).
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since 3.0.0
 * @package Verowa Connect
 * @subpackage Newsletter
 */


/**
 * Generates a small newsletter form for integration using a shortcode.
 *
 * @param mixed $atts Attributes for customizing the form. Default attribute includes:
 *                    - 'slug': Slug for the form (Default: 'newsletter')
 * @return bool|string Returns either a string representation of the newsletter form or a boolean value based on the success of the operation.
 */
function verowa_newsletter_request_form( $atts ) {

	$atts = shortcode_atts( array(
		'slug' => 'newsletter',
	), $atts, 'verowa_newsletter_request_form' );

	ob_start();

	echo '<div class="verowa-newsletter small-form">';

	if (! isset ($_GET['email']) && ! isset ($_GET['key']))
	{

		echo '<form action="/' . $atts['slug'] . '/" method="GET" >' .
			'<div class="row">' .
			'<input type="email" placeholder="' . __( 'Your Email Address', 'verowa-connect' ) . '" required=required name="email" />' .
			'</div>' .
			'<div class="row">' .
			'<input type="submit" value="' . _x( 'Subscribe', 'Text of a button', 'verowa-connect' ) . '" />' .
			'</form>';

	}
	else
	{
		echo __ ('You are currently on the newsletter form and cannot fill anything out here.', 'verowa-connect');
	}
	echo '</div>';
	return ob_get_clean();
}

?>