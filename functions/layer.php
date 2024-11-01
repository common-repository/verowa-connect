<?php
/**
 * Functions for use verowa layers
 * Encoding: UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @package Verowa Connect
 * @subpackage Functions
 */

// UNDONE: Layer Funktionen prüfen und überarbeiten


/**
 * Determines the child layer IDs
 *
 * @param int $parent_layer_id
 *
 * @return array
 */
function verowa_layer_get_children( $parent_layer_id ) {
	$layers_array = verowa_get_layers_array();
	$result       = array();

	foreach ( $layers_array as $layer ) {
		if ( $layer['parent_id'] == $parent_layer_id ) {
			$result[] = $layer;
		}
	}

	return $result;
}




/**
 * A shortcode to show sub targets
 *
 * @return bool|string
 */
function show_verowa_sub_targets() {
	global $post;

	ob_start();

	$sub_targets = get_post_meta( $post->ID, 'verowa_sub_targets', true );

	if ( ! empty( $sub_targets ) ) {
		echo '<div class="verowa-sub-targets">';

		foreach ( $sub_targets as $sub_target ) {
			$sub_post = get_post( $sub_target );

			if ( 'publish' === $sub_post->post_status ) {
				$title   = $sub_post->post_title;
				$excerpt = $sub_post->post_excerpt;
				$link    = $sub_post->guid;
				$image   = get_the_post_thumbnail_url( $sub_target, 'thumbnail' ); // WordPress 5 needs post-thumbnail as size.

				echo '<a href="' . $link . '" >';
				echo '<div class="sub-target">';
				echo '<div class="wrapper">';
				echo '<div class="sub-target-title">' . $title . '</div>';
				echo '<div class="sub-target-excerpt">' . $excerpt . '</div>';
				echo '</div>';
				echo '<div class="sub-target-image-container">';
				if ( '' != $image ) {
					echo '<img class="sub-target-image" src="' . $image . '" />';
				}

				echo '</div>';
				echo '</div></a>';
			}
		}

		echo '</div>';
	}

	return ob_get_clean();
}



/**
 * Functions create Verowa layer drop-down.
 *
 * @param int $post_id ID of a Post.
 */
function verowa_layers_dropdown( $post_id ) {

	$hierarchical_array_for_dropdown = verowa_get_hierarchical_layers_tree();

	$int_layer_id = get_post_meta( $post_id, '_verowa_layer_assign', true );

	echo '<select name="bereiche" class="verowa-ddl-list-settings">';
	echo '<option value=""';
	if ( '' === $int_layer_id ) {
		echo '  selected=selected';
	}
	echo '></option>';
	verowa_dropdown_options( $hierarchical_array_for_dropdown, $int_layer_id );
	echo '</select>';
}




/**
 *
 * @param array $hierarchical_layer_array
 * @param mixed $selected
 */
function verowa_dropdown_options( $hierarchical_layer_array, $selected ) {

	foreach ( $hierarchical_layer_array as $layer ) {
		$space_before = str_repeat( '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $layer['node_level'] ) . ' ';
		if ( key_exists( 'children', $layer ) && count( $layer['children'] ) > 0 && ! empty( $layer['children'] ) ) {
			echo '<option value="' . $layer['layer_id'] . '"';

			if ( $selected == $layer['layer_id'] ) {
				echo ' selected=selected';
			}

			echo '>';
			echo $space_before . $layer['name'] . ' (Nr. ' . $layer['layer_id'] . ')' . '</option>';
			verowa_dropdown_options( $layer['children'], $selected );
		} else {
			echo '<option value="' . $layer['layer_id'] . '"';

			if ( $selected == $layer['layer_id'] ) {
				echo ' selected=selected';
			}
			echo '>' . $space_before . $layer['name'] . ' (Nr. ' . $layer['layer_id'] . ')' . '</option>';
		}
	}
}




/**
 * Builds a hierarchical tree structure from the given elements array based on parent-child relationships.
 *
 * @param array $elements   An array containing the elements to build the tree from.
 * @param int   $parent_id  The parent ID to start building the tree from (defaults to 0).
 * @param int   $node_level The level of the current node in the hierarchy (defaults to 0).
 *
 * @return array            Returns a hierarchical tree structure as an array.
 */
function verowa_get_hierarchical_layers_tree( $elements = array(), $parent_id = 0, $node_level = 0 ) {

	if ( is_array( $elements ) && 0 == count( $elements ) ) {
		$elements = verowa_get_layers_array();

		if ( is_array( $elements ) ) {
			// We reorder the array.
			usort(
				$elements,
				function( $a, $b ) {
					return $a['parent_id'] <=> $b['parent_id'];
				}
			);
		}
	}

	$branch         = array();
	$sub_node_level = $node_level + 1;
	if ( is_array( $elements ) ) {
		foreach ( $elements as &$element ) {
			if ( $element['parent_id'] == $parent_id ) {
				// The node level is used for the correct display in the drop down.
				$children = verowa_get_hierarchical_layers_tree( $elements, $element['layer_id'], $sub_node_level );

				$element['node_level'] = $node_level;
				if ( $children ) {
					$element['children'] = $children;
				}

				$branch[ $element['layer_id'] ] = $element;
				unset( $element );
			}
		}
	}

	return $branch;
}




/**
 * Retrieves the sub-layer IDs for a given set of layer IDs in a hierarchical tree structure.
 *
 * @param string $str_layer_ids A comma-separated string of layer IDs.
 * @param array  $hierarchical_layer_ids The hierarchical tree structure containing layer IDs and their children.
 *
 * @return string A comma-separated string of all the layer IDs and their sub-layer IDs.
 */
function verowa_get_sub_layer_ids( $str_layer_ids, $hierarchical_layer_ids ) {
	$arr_layer_ids = explode( ',', $str_layer_ids );

	$str_ret_layer_ids = $str_layer_ids;

	foreach ( $arr_layer_ids as $int_layer_id ) {

		$arr_tree_sub_layer_ids = verowa_get_arr_sub_layer_id( $int_layer_id, $hierarchical_layer_ids );
		$str_ids                = implode( ', ', verowa_add_sub_layer_ids( $arr_tree_sub_layer_ids ) );
		if ( '' !== $str_ids ) {
			$str_ret_layer_ids .= ', ' . $str_ids;
		}
	}

	return $str_ret_layer_ids;
}




/**
 * Searches for a specific layer ID in a hierarchical tree structure and returns its children IDs.
 *
 * @param int   $int_layer_id The layer ID to search for in the hierarchical tree.
 * @param array $hierarchical_layer_ids The hierarchical tree structure containing layer IDs and their children.
 *
 * @return array An array containing the children layer IDs of the given layer ID.
 */
function verowa_get_arr_sub_layer_id( $int_layer_id, $hierarchical_layer_ids ) {
	$arr_ret = array();

	// Layer ID is a main group.
	if ( key_exists( $int_layer_id, $hierarchical_layer_ids ) ) {
		$arr_ret = $hierarchical_layer_ids[ $int_layer_id ];
	} else {
		// Search in child notes.
		foreach ( $hierarchical_layer_ids as $single_arr_sub_tree ) {
			// If something has been found, there is no need to search any further.
			if ( 0 !== count( $arr_ret ) ) {
				break;
			}

			if ( key_exists( 'children', $single_arr_sub_tree ) ) {
				$arr_ret = verowa_get_arr_sub_layer_id( $int_layer_id, $single_arr_sub_tree['children'] );
			}
		}
	}

	return $arr_ret;
}



/**
 * Recursively adds sub-layer IDs to an array for a given node in the hierarchical tree structure.
 *
 * @param array $arr_tree_sub_layer_ids An array representing a node in the hierarchical tree structure.
 *
 * @return array An array containing all the sub-layer IDs for the given node.
 */
function verowa_add_sub_layer_ids( $arr_tree_sub_layer_ids ) {
	$arr_ret = array();

	if ( key_exists( 'children', $arr_tree_sub_layer_ids ) ) {
		foreach ( $arr_tree_sub_layer_ids['children'] as $single_arr_sub_tree ) {
			$arr_ret [] = $single_arr_sub_tree['layer_id'];
			$arr_sub    = verowa_add_sub_layer_ids( $single_arr_sub_tree );
			if ( count( $arr_sub ) > 0 ) {
				$arr_ret = array_merge( $arr_ret, $arr_sub );
			}
		}
	}

	return $arr_ret;
}
