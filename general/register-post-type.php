<?php
/**
 * Register custom post type "verowa_event" and "verowa_person"
 *
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since 2.8.5
 * @package Verowa Connect
 * @subpackage General
 */

// Register and configure verowa_event and verowa_person.

add_action( 'init', 'verowa_create_custom_post_type' );
add_filter( 'manage_edit-verowa_event_columns', 'verowa_add_event_table_columns' );
add_action( 'manage_verowa_event_posts_custom_column', 'verowa_event_output_table_columns_data', 10, 2 );
add_filter( 'bulk_actions-edit-verowa_event', 'verowa_remove_bulk_actions_for_custom_posts' );
add_filter( 'bulk_actions-edit-verowa_person', 'verowa_remove_bulk_actions_for_custom_posts' );
add_filter( 'bulk_actions-edit-verowa_posting', 'verowa_remove_bulk_actions_for_custom_posts' );

/**
 * Verowa Connect hook to register the custom post types
 *
 * @return void
 */
function verowa_create_custom_post_type() {
	register_verowa_event();
	register_verowa_person();
	register_verowa_posting();
}




/**
 * Registers the custom post type "verowa_event" for Verowa Veranstaltungen (Events).
 *
 * @return void
 */
function register_verowa_event() {
	$args = array();

	/**
	 * The $labels describes how the post type appears.
	 */
	$labels = array(
		'name'          => __( 'Events', 'verowa-connect' ), // Plural name.
		'singular_name' => __( 'Event', 'verowa-connect' ),   // Singular name.
	);

	/*
	 * The $supports parameter describes what the post type supports
	 */
	$supports = array(
		'editor',       // Post content.
		'excerpt',      // Allows short description.
		'thumbnail',    // Allows feature images.
		'page-attributes',
		'trackbacks',   // Supports trackbacks.
		'custom-fields', // Supports by custom fields.
	);

	/*
	 * The $args parameter holds important parameters for the custom post type
	 */
	$args = array(
		'labels'              => $labels,
		'description'         => 'Verowa Veranstaltungen', // Description.
		'supports'            => $supports,
		'taxonomies'          => array(), // Allowed taxonomies.
		'hierarchical'        => false, // Allows hierarchical categorization, if set to false, the Custom Post Type will behave like Post, else it will behave like Page.
		'public'              => true,  // Makes the post type public.
		'show_ui'             => true,  // Displays an interface for this post type.
		'show_in_menu'        => 'verowa/main_options_menu.php',  // Displays in the Admin Menu (the left panel).
		'show_in_nav_menus'   => true,  // Displays in Appearance -> Menus.
		'show_in_admin_bar'   => false,  // Displays in the black admin bar.
		'menu_position'       => 40,     // The position number in the left menu.
		'menu_icon'           => true,  // The URL for the icon used for this post type.
		'can_export'          => true,  // Allows content export using Tools -> Export.
		'has_archive'         => false,  // Enables post type archive (by month, date, or year).
		'exclude_from_search' => false, // Excludes posts of this type in the front-end search result page if set to true, include them if set to false.
		'publicly_queryable'  => true,  // Allows queries to be performed on the front-end part if set to true.
		'capabilities'        => array(
			'create_posts'       => false,
			'read_post'          => true,
			'delete_others_ypts' => true,
			'delete_post'        => true,
		),
		'rewrite'             => array(
			'slug'       => 'veranstaltung',
			'with_front' => true,
		),
	);

	register_post_type( 'verowa_event', $args );
}




/**
 * Add the event ID to the verowa events overview
 *
 * @param array $array All columns to display in the Overview.
 *
 * @return array
 */
function verowa_add_event_table_columns( $array ) {
	unset( $array['author'] );

	$array['event_id'] = 'Event-ID';

	return $array;
}




/**
 * Outputs data for custom columns in the table view of Verowa event post type.
 *
 * @param string $column_name The name of the column being displayed.
 * @param int    $post_id     The ID of the post being displayed.
 *
 * @return void
 */
function verowa_event_output_table_columns_data( $column_name, $post_id ) {
	global $wpdb;

	$obj_post = get_post( $post_id );
	if ( null !== $obj_post ) {
		$str_output = '';
		switch ( $obj_post->post_type ) {
			case 'verowa_event':
				switch ( $column_name ) {
					case 'event_id':
						$str_table_name = $wpdb->prefix . 'verowa_events';
						echo $wpdb->get_var(
							'SELECT `' . $column_name . '` FROM `' . $str_table_name . '` ' .
							'WHERE `post_id` = ' . $post_id
						);
						break;
				}

				break;

			case 'verowa_person':
				break;
		}

		echo $str_output;
	}
}


/**
 * Register "verowa_person" post Type
 */
function register_verowa_person() {
	$args = array();

	/*
	 * The $labels describes how the post type appears.
	 */
	$labels = array(
		'name'          => __( 'Persons', 'verowa-connect' ), // Plural name.
		'singular_name' => __( 'Person', 'verowa-connect' ),   // Singular name.
	);

	/*
	 * The $supports parameter describes what the post type supports
	 */
	$supports = array(
		'title',        // Post title.
		'editor',       // Post content.
		'excerpt',      // Allows short description.
		'page-attributes',
		'thumbnail',    // Allows feature images.
		'trackbacks',   // Supports trackbacks.
		'custom-fields', // Supports by custom fields.
	);

	/*
	 * The $args parameter holds important parameters for the custom post type.
	 */
	$args = array(
		'labels'              => $labels,
		'description'         => 'Verowa Veranstaltungen', // Description.
		'supports'            => $supports,
		'taxonomies'          => array(), // Allowed taxonomies.
		'hierarchical'        => false, // Allows hierarchical categorization, if set to false, the Custom Post Type will behave like Post, else it will behave like Page.
		'public'              => true,  // Makes the post type public.
		'show_ui'             => true,  // Displays an interface for this post type.
		'show_in_menu'        => 'verowa/main_options_menu.php',  // Displays in the Admin Menu (the left panel).
		'show_in_nav_menus'   => true,  // Displays in Appearance -> Menus.
		'show_in_admin_bar'   => false,  // Displays in the black admin bar.
		'menu_position'       => 30,     // The position number in the left menu.
		'menu_icon'           => true,  // The URL for the icon used for this post type.
		'can_export'          => true,  // Allows content export using Tools -> Export.
		'has_archive'         => false,  // Enables post type archive (by month, date, or year).
		'exclude_from_search' => false, // Excludes posts of this type in the front-end search result page if set to true, include them if set to false.
		'publicly_queryable'  => true,  // Allows queries to be performed on the front-end part if set to true.
		'capabilities'        => array(
			'create_posts'       => false,
			'read_post'          => true,
			'delete_others_ypts' => true,
			'delete_post'        => true,
		),
		// 'capability_type'     => 'post', // Allows read, edit, delete like “Post”.
		'rewrite'             => array(
			'slug'       => 'person',
			'with_front' => true,
		),
	);

	register_post_type( 'verowa_person', $args );
}

function register_verowa_posting() {
		
	// Whether to include the post type in the REST API. Set this to true for the post type to be available in the block editor.
	$args = array();

	/*
	 * The $labels describes how the post type appears.
	 */
	$labels = array(
		'name'          => __( 'Postings', 'verowa-connect' ), // Plural name.
		'singular_name' => __( 'Posting', 'verowa-connect' ),   // Singular name.
	);

	/*
	 * The $supports parameter describes what the post type supports
	 */
	$supports = array(
		'title',        // Post title.
		'editor',       // Post content.
		'excerpt',      // Allows short description.
		'page-attributes',
		'thumbnail',    // Allows feature images.
		'trackbacks',   // Supports trackbacks.
		'custom-fields', // Supports by custom fields.
	);

	/*
	 * The $args parameter holds important parameters for the custom post type.
	 */
	$args = array(
		'labels'              => $labels,
		'description'         => 'Verowa Beiträge', // Description.
		'supports'            => $supports,
		'taxonomies'          => array(), // Allowed taxonomies.
		'show_in_rest'        => true,
		'hierarchical'        => false, // Allows hierarchical categorization, if set to false, the Custom Post Type will behave like Post, else it will behave like Page.
		'public'              => true,  // Makes the post type public.
		'show_ui'             => true,  // Displays an interface for this post type.
		'show_in_menu'        => 'verowa/main_options_menu.php',  // Displays in the Admin Menu (the left panel).
		'show_in_nav_menus'   => true,  // Displays in Appearance -> Menus.
		'show_in_admin_bar'   => false,  // Displays in the black admin bar.
		'menu_position'       => 30,     // The position number in the left menu.
		'menu_icon'           => true,  // The URL for the icon used for this post type.
		'can_export'          => true,  // Allows content export using Tools -> Export.
		'has_archive'         => false,  // Enables post type archive (by month, date, or year).
		'exclude_from_search' => false, // Excludes posts of this type in the front-end search result page if set to true, include them if set to false.
		'publicly_queryable'  => true,  // Allows queries to be performed on the front-end part if set to true.
		'capabilities'        => array(
			'create_posts'       => false,
			'read_post'          => true,
			'delete_others_ypts' => true,
			'delete_post'        => true,
		),
		'rewrite'             => array(
			'slug'       => 'posting',
			'with_front' => true,
		),
	);

	register_post_type( 'verowa_posting', $args );
}


function verowa_remove_bulk_actions_for_custom_posts($actions) {
    if (isset($actions['edit'])) {
        unset($actions['edit']); 
    }
    if (isset($actions['trash'])) {
        unset($actions['trash']); 
    }
    return $actions;
}
