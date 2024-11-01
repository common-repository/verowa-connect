<?php
/**
 * Generates the widget needed to display event filters.
 * However, the logic of the display is set in verowa-event-filter.php.
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @package Verowa Connect
 * @subpackage Events
*/

/**
 * register "VerowaEventFilter" widget
 *
 * @return void
 */
function register_verowa_event_filter_widget() {
	register_widget( 'VerowaEventFilter' );
}

add_action( 'widgets_init', 'register_verowa_event_filter_widget' );

/**
 * ...
 */
class VerowaEventFilter extends WP_Widget {

	/**
	 *
	 */
	public function __construct() {
		parent::__construct(
			'verowa_event_filter_widget',
			__(
				'Verowa: Event filter',
				'verowa-connect'
			),
			array(
				'description' => __( 'A widget to filter Verowa events.', 'verowa-connect' ),
			)
		);
	}

	/**
	 * Outputs the content of the widget.
	 *
	 * @param array $args Display arguments including 'before_title', 'after_title', 'before_widget', and 'after_widget'.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance ) {
		global $post;

		if ( has_shortcode( $post->post_content, 'verowa_agenda' ) ) {
			echo do_shortcode( '[verowa_event_filter titel="' . $instance['title'] . '"]' );
		}
	}

	/**
	 * Outputs the settings update form.
	 *
	 * @param array $instance Current settings.
	 *
	 * @return void
	 */
	public function form( $instance ) {
		if ( ! empty( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			$title = __( 'Choose event', 'verowa-connect' );
		}

		echo '<p><label for="' . esc_attr( $this->get_field_id( 'title' ) ) . '">' .
			esc_html( __( 'Title', 'verowa-connect' ) ) . ':</label> ' .
			'<input class="widefat" id="' . esc_attr( $this->get_field_id( 'title' ) ) . '" ' .
			'name="' . esc_attr( $this->get_field_name( 'title' ) ) . '" type="text" value="' . esc_attr( $title ) . '"></p>';
	}

	/**
	 * Updates a particular instance of a widget.
	 *
	 * @param mixed $new_instance New settings for this instance as input by the user via WP_Widget::form().
	 * @param mixed $dummy Old settings for this instance.
	 *
	 * @return string[]
	 */
	public function update( $new_instance, $dummy ) {
		$instance          = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';

		return $instance;
	}
}
