<?php
/**
 * Widget for displaying persons from the Verowa database.
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @package Verowa Connect
 * @subpackage Person
 */

/**
 * Generates the widget that is needed for the display of assigned persons.
 * However, the logic of the display is set in verowa-show-persons-shortcode.php.
 */
function register_verowa_persons_widget() {
	register_widget( 'Verowa_Persons_Widget' );
}

add_action( 'widgets_init', 'register_verowa_persons_widget' );

/**
 *  Widget for displaying persons from the Verowa database.
 */
class Verowa_Persons_Widget extends WP_Widget {

	/**
	 * Initialize the class and call the parent constructor.
	 */
	function __construct() {
		parent::__construct(
			'verowa_Persons_widget',
			__( 'Verowa: Show persons', 'verowa-connect' ),
			array(
				'description' => __(
					'This widget shows persons from the Verowa database.',
					'verowa-connect'
				),
			)
		);
	}



	/**
	 * Outputs the content of the widget.
	 *
	 * @param array $args Display arguments including 'before_title', 'after_title', 'before_widget', and 'after_widget'.
	 * @param array $instance The settings for the particular instance of the widget.
	 *
	 * @return void
	 */
	public function widget( $args, $instance ) {
		// Widget is not displayed if shortcode is used or it does not contain a person.
		global $post;

		$int_personlist_template = array();
		$curr_language_code = verowa_wpml_get_current_language();

		$all_persons_function_content = verowa_personen();
		$is_empty = strip_tags( $all_persons_function_content );

		// Get the template id and the template for the person list.
		$int_personlist_template = intval( get_post_meta( $post->ID, 'verowa_personlist_template', true ) );

		if ( 0 === $int_personlist_template ) {
			$int_personlist_template = intval( get_option( 'verowa_default_personlist_template' ) );
		}

		$obj_personlist_template = verowa_get_single_template( $int_personlist_template, $curr_language_code, 'widget' );

		// We look to see if a template with "widget" has been selected.
		if ( true === empty( $obj_personlist_template ) || '' == trim( $is_empty ) ) {
			$all_persons_function_content = '';
		}

		if ( '' !== $all_persons_function_content ) {
			echo $args['before_widget'];

			if ( ! empty( $instance['title'] ) ) {
				echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
			}
			echo $all_persons_function_content;

			echo $args['after_widget'];
		}
	}

	/**
	 * Outputs the settings update form.
	 *
	 * @param mixed $instance $instance Current settings.
	 *
	 * @return void
	 */
	public function form( $instance ) {
		$title = '';
		if ( ! empty( $instance['title'] ) ) {
			$title = $instance['title'];
		}

		echo '<p><label for="' . esc_attr( $this->get_field_id( 'title' ) ) . '">' .
			esc_html( __( 'Title', 'verowa-connect' ) ) . ':</label>' .
			'<input class="widefat" id="' . esc_attr( $this->get_field_id( 'title' ) ) . '" ' .
			'name="' . esc_attr( $this->get_field_name( 'title' ) ?? '' ) . '" type="text" value="' . esc_attr( $title ) . '"></p>';
	}

	/**
	 *
	 *
	 * @param array $new_instance
	 * @param array $dummy
	 * @return array
	 */
	public function update( $new_instance, $dummy ) {
		$instance = array();

		if ( ! empty( $new_instance['title'] ) ) {
			$instance['title'] = strip_tags( $new_instance['title'] );
		} else {
			$instance['title'] = '';
		}

		return $instance;
	}
}