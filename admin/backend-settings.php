<?php
/**
 * List to display all Verowa templates
 *
 * Project:         VEROWA CONNECT
 * File:            general/verowa_templates_list.php
 * Encoding:        UTF-8 (ẅ)
 *
 * @author © Picture-Planet GmbH
 * @since 2.8.0
 * @package Verowa Connect
 * @subpackage  Backend
 */

/**
 * Creates the HTML for the settings page.
 *
 * @return void
 */
function verowa_render_settings() {
	global $wpdb;
	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Get the active tab from the $_GET param.
	$default_tab = null;
	$tab         = isset( $_GET['tab'] ) ? $_GET['tab'] : $default_tab;

	$obj_backend_settings = new Verowa_Backend_Settings( $tab );
	$obj_backend_settings->load_verowa_module_infos();
	$str_request_method = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) );
	
	?>
	<!-- Our admin page content should all be inside .wrap -->
	<div class="wrap verowa-options-wrapper">
		<div class="verowa-options-header" >
			<?php 
				echo '<img src="' . esc_url (plugins_url ('../images/verowa.png', __FILE__)) . '" style="height:56px;" />' .
					'<h1>' .  esc_html( get_admin_page_title() ) . '</h1>';
			?>
			<!-- Here are our tabs -->
			<?php $obj_backend_settings->render_navigation() ?>
		</div>
		<div class="tab-content">
		<?php
			if ($str_request_method == 'POST') {
				$obj_backend_settings->update_options($tab);
			}
			$obj_backend_settings->render_tab_content();
		?>
		</div>
	</div>
	<!-- Erstelle das Javascript für den code mirror text editor-->
	<?php
	if ( 'news' === $tab ) {
		echo '<script>
			jQuery(document).ready(function($) {';
		$arr_news_types = $obj_backend_settings->arr_module_infos['postings']['block_types'] ?? [];
		foreach ( $arr_news_types as $key => $value ) {
			if ('gallery' === $key)
			{
				echo 'wp.codeEditor.initialize($("#verowa_news_block_template_' . $key . '_header"), cm_settings);';
				echo 'wp.codeEditor.initialize($("#verowa_news_block_template_' . $key . '_entry"), cm_settings);';
				echo 'wp.codeEditor.initialize($("#verowa_news_block_template_' . $key . '_footer"), cm_settings);';
			} else {
				echo 'wp.codeEditor.initialize($("#verowa_news_block_template_' . $key . '"), cm_settings);';
			}
		}

		echo '});
			</script>';
	}
}
