<?php
/**
 * Generates the widget that is needed for the display of assigned events.
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @package Verowa Connect
 * @subpackage Events
 */

/**
 * Register 'VerowaEventlistWidget' widget.
 *
 * @return void
 */
function register_verowa_eventlist_widget() {
	register_widget( 'VerowaEventlistWidget' );
}

add_action( 'widgets_init', 'register_verowa_eventlist_widget' );

/**
 * Generates the widget that is needed for the display of assigned events.
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @package Verowa Connect
 * @subpackage Events
 */
class VerowaEventlistWidget extends WP_Widget {

	/**
	* Constructor for the VerowaEventlistWidget class.
	*
	* @since 1.0.0
	*/
	function __construct() {
		parent::__construct(
			'verowa_Eventlist_Widget',
			__( 'Verowa: Show Events', 'verowa-connect' ),
			array(
				'description' => __( 'This widget shows Events from Verowa.', 'verowa-connect' ),
			)
		);
	}


	/**
	 * Constructor for the VerowaEventlistWidget class.
	 *
	 * @since 1.0.0
	 */
	public function widget( $args, $instance ) {
		// Widget is not displayed if shortcode is used or it does not contain an event.
		global $post, $wpdb, $wp_query;

		$str_eventlist_shortcode = '';
		$int_eventlist_template_id = intval( get_post_meta( $post->ID, 'verowa_eventlist_template', true ) );

		if ( 0 === $int_eventlist_template_id ) {
			$int_eventlist_template_id = intval( get_option( 'verowa_default_eventlist_template' ) );
		}

		$curr_language_code      = verowa_wpml_get_current_language();
		$obj_template            = verowa_get_single_template( $int_eventlist_template_id, $curr_language_code, 'widget' );


		// We only show the event list's if a template with type "widget" ist selected.
		if ( ! empty( $obj_template ) ) {
			$int_list_id         = get_post_meta( $post->ID, '_verowa_list_assign', true );
			$int_layer_id        = get_post_meta( $post->ID, '_verowa_layer_assign', true );
			$int_target_group_id = get_post_meta( $post->ID, '_verowa_target_group_assign', true );
			$int_max             = get_post_meta( $post->ID, '_max_events', true );
			$int_max_days        = get_post_meta( $post->ID, '_max_events_days', true );
			$str_list_title      = get_post_meta( $post->ID, 'verowa_list_title', true );
			$str_extra_vars      = '';

			// Process max events and max days.
			if ( 0 !== $int_max ) {
				$str_extra_vars .= ' pro-seite=' . $int_max . ' max=' . $int_max . ' ';

				if ( 0 !== $int_max_days ) {
					$str_extra_vars .= 'max_days=' . $int_max_days . ' ';
				}
			}

			if ( '' !== $str_list_title ) {
				$str_extra_vars .= 'title="' . $str_list_title . '" ';
			}

			if ( $int_list_id > 0 && ! $wp_query->is_search() ) {
				$str_eventlist_shortcode .= '[verowa_event_list id=' . $int_list_id . ' ' . $str_extra_vars . ' ' .
					'template_id=' . $int_eventlist_template_id . ']';
			}

			if ( $int_layer_id > 0 && ! $wp_query->is_search() ) {
				$str_eventlist_shortcode .= '[verowa_event_list layer_id=' . $int_layer_id . ' ' . $str_extra_vars . ' ' .
				'template_id=' . $int_eventlist_template_id . ']';
			}

			if ( $int_target_group_id > 0 && ! $wp_query->is_search() ) {
				$str_eventlist_shortcode .= '[verowa_event_list target_group=' . $int_target_group_id . ' ' .
					$str_extra_vars . ' template_id=' . $int_eventlist_template_id . ']';
			}
		}

		if ( '' !== $str_eventlist_shortcode ) {
			echo $args['before_widget'];

			if ( ! empty( $instance['title'] ) ) {
				echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
			}

			echo do_shortcode( $str_eventlist_shortcode );

			echo $args['after_widget'];
		}
	}

	/**
	 * Back-end widget form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $instance The widget instance.
	 */
	public function form( $instance ) {
		$title = '';
		if ( ! empty( $instance['title'] ) ) {
			$title = $instance['title'];
		}

		echo '<p><label for="' . $this->get_field_id( 'title' ) . '">' .
			esc_html( __( 'Title', 'verowa-connect' ) ) . ':</label>' .
			'<input class="widefat" id="' . esc_attr( $this->get_field_id( 'title' ) ) . '" ' .
			'name="' . esc_attr( $this->get_field_name( 'title' ) ) . '" type="text" value="' . esc_attr( $title ) . '"></p>';
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @since 1.0.0
	 *
	 * @param array $new_instance The new widget instance.
	 * @param array $old_instance The old widget instance.
	 * @return array The sanitized widget instance.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();

		if ( ! empty( $new_instance['title'] ) ) {
			$instance['title'] = strip_tags( $new_instance['title'] );
		} else {
			$instance['title'] = '';
		}

		return $instance;
	}
}
