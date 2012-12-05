<?php
/*
Plugin Name: TextWise Similarity Search
Plugin URI: http://textwise.com/tools/wordpress-plugin-0
Description: SemanticHacker API integration for WordPress
Version: 1.3.2
Author: TextWise, LLC
Author URI: http://www.textwise.com/

	Copyright 2008-2012  TextWise, LLC.  ( email : admin@semantichacker.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of

    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('TEXTWISE_API_URL', 'http://api.semantichacker.com/');
define('TEXTWISE_AMAZON_REF', 'textwiseco23-20');

//Load CSS and code for article output
add_action('wp_enqueue_scripts', 'textwise_frontend_style');
add_filter('the_content', 'textwise_content_filter');

//Actions to include on every admin page
add_action('admin_init', 'textwise_admin_init');
add_action('admin_init', 'textwise_token_update_check');
add_action('admin_menu', 'textwise_admin_page');
add_action('admin_notices', 'textwise_check_token');
add_action('admin_notices', 'textwise_check_detectconflict');
add_action('admin_notices', 'textwise_check_versions');
add_action('admin_notices', 'textwise_dependency_warning');

//Supported in 2.7+
add_filter('plugin_action_links_textwise/textwise.php', 'textwise_plugin_links' );

//Load on editor pages only, if token exists and is valid, and any service is enabled.
if ( is_admin()
		&& textwise_check_dependencies()
		&& get_option('textwise_api_token')
		&& get_option('textwise_api_token_invalid') == 'n'
		&& textwise_services_enabled() ) {
	add_action('add_meta_boxes_post', 'textwise_init_editor');
	add_action('save_post', 'textwise_savepost');

}

//AJAX Callbacks
add_action('wp_ajax_textwise_content_update', 'textwise_ajax_content_update');
add_action('wp_ajax_textwise_nonce', 'textwise_ajax_nonce');
add_action('wp_ajax_textwise_categoryinfo', 'textwise_ajax_categoryinfo');

function textwise_services_enabled() {
	$service_settings = array(
		'tag'            => get_option('textwise_tag_enable'),
		'contentlink'    => get_option('textwise_contentlink_enable'),
		'category'       => get_option('textwise_category_enable'),
		'video'          => get_option('textwise_video_enable'),
		'image'          => get_option('textwise_image_enable'),
		'rss'            => get_option('textwise_rss_enable'),
		'wiki'           => get_option('textwise_wiki_enable'),
		'product'        => get_option('textwise_product_enable')
	);

	$enabled = array_filter( $service_settings );
	return !empty( $enabled );
}

//Hook call function definitions

function textwise_admin_init() {
	//Set defaults
	//Won't affect existing settings
	add_option('textwise_api_token', '');
	add_option('textwise_api_token_invalid', 'c');	//States: n=Valid, y=Invalid, c=Check on next run
	add_option('textwise_amazon_ref', '');
	add_option('textwise_tag_enable', '1');
	add_option('textwise_contentlink_enable', '1');
	add_option('textwise_category_enable', '1');
	add_option('textwise_video_enable', '1');
	add_option('textwise_image_enable', '1');
	add_option('textwise_rss_enable', '1');
	add_option('textwise_wiki_enable', '1');
	add_option('textwise_product_enable', '1');
	add_option('textwise_autoupdate', '60');
	add_option('textwise_logo_place', 'section');
	add_option('textwise_use_logo', '1');
	add_option('textwise_conflict_warning', '0');
	add_option('textwise_box_position', '0');
	add_option('textwise_list_css', '0');
	add_option('textwise_version_warning', '0');

	//For use in 2.7+
	if (function_exists('register_setting')) {
		register_setting('textwise', 'textwise_api_token');
		register_setting('textwise', 'textwise_api_token_invalid');
		register_setting('textwise', 'textwise_amazon_ref');
		register_setting('textwise', 'textwise_tag_enable');
		register_setting('textwise', 'textwise_contentlink_enable');
		register_setting('textwise', 'textwise_category_enable');
		register_setting('textwise', 'textwise_video_enable');
		register_setting('textwise', 'textwise_image_enable');
		register_setting('textwise', 'textwise_rss_enable');
		register_setting('textwise', 'textwise_wiki_enable');
		register_setting('textwise', 'textwise_product_enable');
		register_setting('textwise', 'textwise_autoupdate');
		register_setting('textwise', 'textwise_logo_place');
		register_setting('textwise', 'textwise_use_logo');
		register_setting('textwise', 'textwise_conflict_warning');
		register_setting('textwise', 'textwise_box_position');
		register_setting('textwise', 'textwise_list_css');
		register_setting('textwise', 'textwise_version_warning');
	}
	if ( function_exists('add_meta_box') ) {
		$force = (get_option('textwise_box_position') == '1') ? true : false;
		textwise_metabox_position($force);
	}

}

function textwise_token_update_check() {
	//Token check
	if ( isset( $_POST['textwise_api_token'] ) && $_POST['textwise_api_token'] != get_option('textwise_api_token') ) {
		update_option('textwise_api_token_invalid', 'c');
	}
}

// Add a link to this plugin's settings page (2.7+)
function textwise_plugin_links($links) {
	$infoUrl = get_bloginfo('wpurl'). '/wp-admin/options-general.php?page=textwise/textwise-settings.php';
	$settings_link = '<a href="'.$infoUrl.'">Settings</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

//
function textwise_check_token() {
	if (get_option('textwise_api_token_invalid') == 'c') { textwise_verify_token(); }

	$infoUrl = get_bloginfo('wpurl'). '/wp-admin/options-general.php?page=textwise/textwise-settings.php';
	$message = '';
	if (!get_option('textwise_api_token')) {
		$message = 'API Token missing. Learn about this and other plugin options <a href="'.$infoUrl.'">here</a>.';
	} elseif (get_option('textwise_api_token_invalid') === 'y') {
		$message = 'API Token is invalid. Please correct it in the <a href="'.$infoUrl.'">settings</a>.';
	} elseif (get_option('textwise_api_token_invalid') === 'c') {
		// If it's still set to 'c', assume it's because of a connection error?
		$message = 'API Token could not be verified due to connectivity issues.';
	}

	if ($message != '') {
		echo '<div class="updated"><p><b>TextWise:</b> '.$message.'</p></div>';
	}
}

function textwise_verify_token() {
	if ( !textwise_check_dependencies() ) { return; }

	$token = get_option('textwise_api_token');
	if ($token == '') {
		update_option('textwise_api_token_invalid', 'y');
	} else {
		//Make request to TextWise API for Concept
		$api = textwise_create_api();
		$api_req['c'] = 'wp';
		$api_req['content'] = '';
		$result = $api->concept($api_req);

		//Code 102: Invalid Token
		if (isset( $result['message']['code'] ) && $result['message']['code'] == '102') {
			$invalid = 'y';
		} else if ( is_array($result) && isset($result['error']) ) {
			$invalid = 'c';
		} else {
			$invalid = 'n';
		}

		update_option('textwise_api_token_invalid', $invalid);
	}
}

function textwise_check_detectconflict() {
	//Add list of conflicting plugins here.
	$conflicting_plugins = array('tagaroo', 'zemanta');
	$infoUrl = get_bloginfo('wpurl'). '/wp-admin/options-general.php?page=textwise/textwise-settings.php#conflicts';

	$detected_plugins = array();
	$deactivate_links = array();
	$active_plugins = get_option('active_plugins');
	foreach ($active_plugins as $plugin) {
		if (in_array(basename($plugin, '.php'), $conflicting_plugins)) {
			if( $data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin) ) {
				$plugin_name = $data['Name'];
			} else {
				$plugin_name = basename($plugin, '.php');
			}
			$detected_plugins[] = $plugin;
			$deactivate_links[] = '<a href="' . wp_nonce_url('plugins.php?action=deactivate&amp;plugin=' . $plugin, 'deactivate-plugin_' . $plugin) . '" title="' . __('Deactivate this plugin') . '" class="delete">' . $plugin_name . '</a>';
		}
	}
	if (get_option('textwise_conflict_warning') != 1 && count($detected_plugins)) {
		echo '<div class="updated"><p><b>Textwise:</b> Conflicting plugins detected: '. implode($deactivate_links, ', '). '. <em><a href="'.$infoUrl.'">Disable this warning</a></em></p></div>';
	}
}

function textwise_check_versions() {
	global $wp_version;
	$infoUrl = get_bloginfo('wpurl'). '/wp-admin/options-general.php?page=textwise/textwise-settings.php#versionwarning';
	if ($wp_version < '3.0' && get_option('textwise_version_warning') != 1 )
		echo '<div class="updated"><p><b>Textwise:</b> This version of the Textwise Semantic Plugin has not been tested with your version of WordPress. If you experience any problems, please downgrade to plugin version 1.0.4. <em><a href="'.$infoUrl.'">Disable this warning</a></em></p></div>';
}

//Make sure PHP requirements available
function textwise_check_dependencies() {
	$fopen = ini_get('allow_url_fopen');
	$curl = function_exists('curl_init');
	return ($fopen || $curl);
}

function textwise_dependency_warning() {
	if ( !textwise_check_dependencies() ) {
		echo '<div class="updated"><p><b>Textwise:</b> Your web server configuration prevents communication with the TextWise API. <a target="_blank" href="http://www.wordpress.org/extend/plugins/textwise/installation">Learn More</a></p></div>';
	}
}


//Add link to plugin settings page
function textwise_admin_page() {
	add_options_page('TextWise Plugin', 'TextWise', 'manage_options', dirname(__FILE__).'/textwise-settings.php');
}

//Only runs on post.php & post-new.php (see load-* action)
function textwise_init_editor() {
	global $wp_version;

	wp_enqueue_script('textwise', WP_PLUGIN_URL . '/textwise/textwise.js');
	wp_enqueue_style('textwise', WP_PLUGIN_URL . '/textwise/textwise.css');
	add_action('admin_print_scripts', 'textwise_dataobject');
	add_action('admin_footer', 'textwise_admin_footer');

	//Re-enable relevant code for media plugin - affects YouTube video
	//Different solution for WP 3.2
	if ( $wp_version >= '3.1' && $wp_version < '3.2' ) {
		add_filter( 'mce_external_plugins', 'textwise_tinymce_plugins' );
	}

	$priority = 'high';

	add_meta_box('textwise_metabox_update', 'TextWise Similarity Search', 'textwise_metabox_update', 'post', 'side', 'high');

	if (get_option('textwise_contentlink_enable') == '1') {
		add_meta_box('textwise_metabox_contentlinks', 'Content Link Suggestions', 'textwise_metabox_contentlinks', 'post', 'normal', 'high');
	}

	if (get_option('textwise_image_enable') == '1') {
		add_meta_box('textwise_metabox_images', 'Image Suggestions', 'textwise_metabox_images', 'post', 'normal', $priority);
	}

	if (get_option('textwise_video_enable') == '1') {
		add_meta_box('textwise_metabox_videos', 'Video Suggestions', 'textwise_metabox_videos', 'post', 'normal', $priority);
	}

	if (get_option('textwise_rss_enable') == '1' || get_option('textwise_wiki_enable') == '1' || get_option('textwise_product_enable') == '1') {
		add_meta_box('textwise_metabox_links', 'Relevant Content Suggestions', 'textwise_metabox_links', 'post', 'normal', $priority);
	}
}

function textwise_tinymce_plugins($plugins) {
	$plugins['tw_media'] = WP_PLUGIN_URL . '/textwise/tinymce/mediaplugin.js';
	return $plugins;
}

function textwise_metabox_update() {
	$status = WP_PLUGIN_URL . '/textwise/img/updatestatus.gif';
	$label = WP_PLUGIN_URL . '/textwise/img/updatebutton.png';
	$html = '<a id="textwise_update_button"><span id="textwise_update_status"><img align="top" src="%s" alt="" /></span></a>';
	echo sprintf( $html, $status, $label );
}

//Set metabox positions if unset.
function textwise_metabox_position($force = false) {
	global $current_user, $table_prefix, $wp_version;
	//tagsdiv,categorydiv
	$mb_normal = array('textwise_metabox_contentlinks', 'textwise_metabox_images', 'textwise_metabox_videos', 'textwise_metabox_links');
	$mb_side = array('textwise_metabox_update');

	$metaboxorder = get_user_meta($current_user->ID, 'meta-box-order_post', true);

	if ($metaboxorder) {
		$usermeta_normal = explode(',', $metaboxorder['normal']);
		$usermeta_side = explode(',', $metaboxorder['side']);

		//Remove textwise metabox keys from lists..
		if ($force) {
			$usermeta_normal = array_diff($usermeta_normal, $mb_normal, $mb_side, array(''));
			$usermeta_side = array_diff($usermeta_side, $mb_normal, $mb_side, array(''));
		}

		$updatesetting = false;
		foreach (array_reverse($mb_normal) as $mb) {
			if ( !in_array($mb, $usermeta_normal) ) {
				array_unshift($usermeta_normal, $mb);
				$updatesetting = true;
			}
		}
		foreach (array_reverse($mb_side) as $mb) {
			if ( !in_array($mb, $usermeta_side) ) {
				array_unshift($usermeta_side, $mb);
				$updatesetting = true;
			}
		}
		if ($updatesetting) {
			$metaboxorder['normal'] = implode(',', $usermeta_normal);
			$metaboxorder['side'] = implode(',', $usermeta_side);

			update_user_meta( $current_user->ID, 'meta-box-order_post', $metaboxorder );
			update_option('textwise_box_position', '0');
		}

	}

}

function textwise_metabox_contentlinks() {
	echo '<div id="textwise_contentlinks_container"></div>';
}

function textwise_metabox_videos() {
	global $post_ID;
	$post_meta = get_post_custom($post_ID);
	$video_list = isset($post_meta['_tw_video_list']) ? $post_meta['_tw_video_list'][0] : '';
?>
<p>Click a video to insert it into the Post</p>
<div style="overflow: auto; position: relative; width: 100%;">
	<table id="textwise_video_table"><tr></tr></table>
</div>
<textarea id="textwise_video_input" name="textwise_video_input" style="display: none;" ><?php echo trim(stripslashes($video_list)); ?></textarea>
<?php
}

function textwise_metabox_images() {
	global $post_ID;
	$post_meta = get_post_custom($post_ID);
	$image_list = isset($post_meta['_tw_image_list']) ? $post_meta['_tw_image_list'][0] : '';
?>
<p>Click an image to insert it into the Post</p>
<div style="overflow: auto; position: relative; width: 100%;">
	<table id="textwise_image_table"><tr></tr></table>
</div>
<textarea id="textwise_image_input" name="textwise_image_input" style="display: none;" ><?php echo trim(stripslashes($image_list)); ?></textarea>
<?php
}

function textwise_metabox_links() {
	global $post_ID;
	$post_meta = get_post_custom($post_ID);
	$rss_list = isset($post_meta['_tw_rss_list']) ? $post_meta['_tw_rss_list'][0] : '';
	$wiki_list = isset($post_meta['_tw_wiki_list']) ? $post_meta['_tw_wiki_list'][0] : '';
	$product_list = isset($post_meta['_tw_product_list']) ? $post_meta['_tw_product_list'][0] : '';

?>
<div style="position: relative; width: 100%;">
<?php	if (get_option('textwise_rss_enable') == '1') { ?>
	<h4 class="textwise_powered_by"><img src="<?php bloginfo('wpurl') ?>/wp-content/plugins/textwise/img/textwise_logo.png" alt="textwise Blog &amp; News Suggestions" />
		<span class="textwise_powered_by_section">Blog &amp; News</span> Suggestions
	</h4>
	<div id="textwise_related_blogs"></div>
	<textarea id="textwise_rss_input" name="textwise_rss_input" style="display: none;"><?php echo trim(stripslashes($rss_list));?></textarea>
<?php	}
	if (get_option('textwise_wiki_enable') == '1') { ?>
	<h4 class="textwise_powered_by"><img src="<?php bloginfo('wpurl') ?>/wp-content/plugins/textwise/img/textwise_logo.png" alt="TextWise Wikipedia Suggestions" />
		<span class="textwise_powered_by_section">Wikipedia</span> Suggestions
	</h4>
	<div id="textwise_related_wiki"></div>
	<textarea id="textwise_wiki_input" name="textwise_wiki_input" style="display: none;" ><?php echo trim(stripslashes($wiki_list));?></textarea>

<?php	}
	if (get_option('textwise_product_enable') == '1') { ?>
	<h4 class="textwise_powered_by"><img src="<?php bloginfo('wpurl') ?>/wp-content/plugins/textwise/img/textwise_logo.png" alt="TextWise Product Suggestions" />
		<span class="textwise_powered_by_section">Product</span> Suggestions
	</h4>
	<div style="overflow: auto; position: relative; width: 100%;">
		<table id="textwise_product_table"><tr></tr></table>
	</div>
	<textarea id="textwise_product_input" name="textwise_product_input" style="display: none;"><?php echo trim(stripslashes($product_list));?></textarea>
<?php	} ?>
</div>
<?php
}

//Used in 2.6 and earlier only
function textwise_postboxes() {
	if (get_option('textwise_image_enable') == '1') {?>
<div id="textwise_metabox_images" class="postbox closed">
	<h3><span class="hi">Images Suggestions</span></h3>
	<div class="inside"><?php textwise_metabox_images(); ?></div>
</div>
<?php }
	if (get_option('textwise_video_enable') == '1') {?>
<div id="textwise_metabox_videos" class="postbox closed">
	<h3><span>Video Suggestions</span></h3>
	<div class="inside"><?php textwise_metabox_videos(); ?></div>
</div>
<?php }
	if (get_option('textwise_rss_enable') == '1' || get_option('textwise_wiki_enable') == '1' || get_option('textwise_product_enable') == '1') { ?>
<div id="textwise_metabox_links" class="postbox closed">
	<h3><span>Relevant Content Suggestions<em></span></h3>
	<div class="inside"><?php textwise_metabox_links(); ?></div>
</div>
<?php }
}


function textwise_admin_footer() {
	echo '<div id="textwise_bubble"></div>';
}

function textwise_ajax_nonce() {
	$data = wp_create_nonce('textwise_nonce');
	$wpAjax = new WP_Ajax_Response( array(
		'what' => 'textwise_nonce',
		'data' => $id ? $data : '',
		'supplemental' => array()
	) );
	$wpAjax->send();
}

//Create a API proxy object configured with the proper URL and Token
function textwise_create_api() {
	if ( textwise_check_dependencies() ) {
		include_once(ABSPATH . '/wp-content/plugins/textwise/TextWise_API.php');
		$api_config['baseUrl'] = TEXTWISE_API_URL;
		$api_config['token'] = get_option('textwise_api_token');
		return new TextWise_API($api_config);
	}
}

function textwise_ajax_connection_error( $wpAjax, $what, $id, $error ) {
	$wpAjax->add( array(
		'what'         => $what,
		'id'           => $id,
		'data'         => '',
		'supplemental' => array( 'error' => $error )
	) );
	$wpAjax->send();
	exit();
}

//Respond to AJAX call with data from the API
//Calls are only made to enabled sections (see get_option() calls)
function textwise_ajax_content_update() {
	//check_ajax_referer( ..nonce.. );

	//$_POST has sent request info
	//Extract current content to make API call
	$id = isset( $_POST['post_ID'] ) ? $_POST['post_ID'] : '';
	$api_req['c'] = 'wp';
	$api_req['content'] = isset( $_POST['content'] ) ? stripslashes( $_POST['content'] ) : '';
	$api_req['showLabels'] = 'true';
	$api_req['filter'] = 'html';

	//Make request to TextWise API
	$api = textwise_create_api();
	$wpAjax = new WP_Ajax_Response();

	//Concepts needed for Tags and Content Links..
	if (get_option('textwise_tag_enable') == '1' || get_option('textwise_contentlink_enable')) {
		$api_req['includePositions'] = 'true';
		$result = $api->concept($api_req);

		//Detect connection error ASAP.
		if ( isset( $result['error'] ) )
			textwise_ajax_connection_error( $wpAjax, 'tags', $id, $result['error'] );
	}

	if ( is_array($result['concepts']) ) {
		$concept_results = $result['concepts'];
	} else {
		$concept_results = array();
	}
	if ( isset( $result['message'] ) && is_array($result['message']) ) {
		$sup = array('error' => $result['message']['string']);
	}

	//Tags
	if ( get_option('textwise_tag_enable') == '1') {
		$arrConcepts = array();
		$tagString = "{label: '%s', weight: %s}";
		$tagWeight = '';
		foreach ($concept_results as $c) {
			if ($tagWeight == '') { $tagWeight = 1.0 / $c['weight']; }
			$adjustedWeight = $tagWeight * $c['weight'];
			$arrConcepts[] = sprintf($tagString, textwise_esc_json($c['label']), textwise_esc_json($adjustedWeight));
		}
		$data = implode(',', $arrConcepts);

		$wpAjax->add(array(
			'what' => 'tags',
			'id' => $id,
			'data' => $id ? $data : '',
			'supplemental' => isset($sup) ? $sup : array()
			));
	}

	//Content Links
	//Reuse call to Tags but add surfaceForm data
	if ( get_option('textwise_contentlink_enable') == '1') {
		$filter = $api->filter($api_req);
		$arrContentLinks = array();

		foreach ($concept_results as $c) {
			$arrContentLinks[] = textwise_esc_json(substr($filter['filteredText'], $c['positions'][0]['start'], $c['positions'][0]['end'] - $c['positions'][0]['start']));
		}
		$data = "'".implode("','", $arrContentLinks)."'";

		$wpAjax->add(array(
			'what' => 'contentlinks',
			'id' => $id,
			'data' => $id ? $data : '',
			'supplemental' => isset($result['error']) ? $result : array()
			));

	}

	//Categories
	if (get_option('textwise_category_enable') == '1') {
		$result = $api->category($api_req);

		//Detect connection error ASAP.
		if ( isset( $result['error'] ) )
			textwise_ajax_connection_error( $wpAjax, 'categories', $id, $result['error'] );


		$cat_results = null;
		if ( isset($result['categories']) )
			$cat_results = $result['categories'];
		else
			$cat_results = array();
		$arrCategories = array();
		foreach ($cat_results as $c) {
			$c['label'] = str_replace('_', ' ', $c['label']);
			$arrCategories[] = textwise_esc_json($c['label']);
		}
		$data = "'".implode($arrCategories, "','")."'";

		$wpAjax->add(array(
			'what' => 'categories',
			'id' => $id,
			'data' => $id ? $data : '',
			'supplemental' => isset($result['error']) ? $result : array()
			));
	}

	//Videos
	if (get_option('textwise_video_enable') == '1') {
		$api_req['indexId'] = 'youtube';
		$api_req['fields'] = 'title,landingPageUrl,enclosureUrl,thumbnailUrl';
		$result = $api->match($api_req);

		//Detect connection error ASAP.
		if ( isset( $result['error'] ) )
			textwise_ajax_connection_error( $wpAjax, 'videos', $id, $result['error'] );

		$vidString = "{title: '%s', thumbnailUrl: '%s', enclosureUrl: '%s', landingPageUrl: '%s', videoId: '%s'}";
		if( isset($result['matches']) ) {
			$vid_results = $result['matches'];
		} else {
			$vid_results = array();
		}
		$arrVideos = array();
		foreach ($vid_results as $m) {
			if ( isset( $m['thumnailUrl'] ) && $m['thumnailUrl'] != '') {
				$vidThumb = $m['thumbnailUrl'];
			} else {
				$vidUrl = parse_url($m['landingPageUrl']);
				parse_str($vidUrl['query'], $vidArgs);
				$vidThumb = 'http://img.youtube.com/vi/'.$vidArgs['v'].'/2.jpg';
			}
			$arrVideos[] = sprintf($vidString,
				textwise_esc_json($m['title']),
				textwise_esc_json($vidThumb),
				textwise_esc_json($m['enclosureUrl']),
				textwise_esc_json($m['landingPageUrl']),
				textwise_esc_json($vidArgs['v'])
				);
		}
		$data = implode($arrVideos, ",");

		$wpAjax->add(array(
			'what' => 'videos',
			'id' => $id,
			'data' => $id ? $data : '',
			'supplemental' => isset($result['error']) ? $result : array()
			));
	}

	//Images
	if (get_option('textwise_image_enable') == '1') {
		$api_req['indexId'] = 'images';
		$api_req['fields'] = 'title,thumbnailUrl,imageUrl,source,landingPageUrl';
		$result = $api->match($api_req);

		//Detect connection error ASAP.
		if ( isset( $result['error'] ) )
			textwise_ajax_connection_error( $wpAjax, 'images', $id, $result['error'] );


		$imgString = "{title:'%s', thumbnailUrl:'%s', landingPageUrl:'%s', imageUrl:'%s', source:'%s'}";
		$img_results = null;
		if(isset($result['matches']))
			$img_results = $result['matches'];
		else
			$img_results = array();
		$arrImages = array();
		foreach ($img_results as $m) {
			$arrImages[] = sprintf($imgString,
				textwise_esc_json($m['title']),
				textwise_esc_json($m['thumbnailUrl']),
				textwise_esc_json($m['landingPageUrl']),
				textwise_esc_json($m['imageUrl']),
				textwise_esc_json($m['source'])
				);
		}
		$data = implode($arrImages, ",");

		$wpAjax->add(array(
			'what' => 'images',
			'id' => $id,
			'data' => $id ? $data : '',
			'supplemental' => isset($result['error']) ? $result : array()
			));
	}

	$api_req['nMatches'] = '5';
	//Blogs & News
	if (get_option('textwise_rss_enable') == '1') {
		$api_req['indexId'] = 'rsscombined';
		$api_req['fields'] = 'title,description,landingPageUrl,channelTitle,channelLink';
		$result = $api->match($api_req);

		//Detect connection error ASAP.
		if ( isset( $result['error'] ) )
			textwise_ajax_connection_error( $wpAjax, 'rss', $id, $result['error'] );

		$rssString = "{title:'%s', description:'%s', landingPageUrl:'%s', channelTitle: '%s', channelLink: '%s'}";
		$bnews_results = null;
		if(isset($result['matches']))
			$bnews_results = $result['matches'];
		else
			$bnews_results = array();
		$arrRss = array();
		foreach ($bnews_results as $m) {
			$arrRss[] = sprintf($rssString,
				textwise_esc_json($m['title']),
				textwise_esc_json(substr($m['description'],0,200)),
				textwise_esc_json($m['landingPageUrl']),
				textwise_esc_json($m['channelTitle']),
				textwise_esc_json($m['channelLink'])
				);
		}
		$data = implode($arrRss, ",");

		$wpAjax->add(array(
			'what' => 'rss',
			'id' => $id,
			'data' => $id ? $data : '',
			'supplemental' => isset($result['error']) ? $result : array()
			));
	}

	//Wiki
	if (get_option('textwise_wiki_enable') == '1') {
		$api_req['indexId'] = 'wikipedia';
		$api_req['fields'] = 'title,description,landingPageUrl,channelTitle,channelLink';
		$result = $api->match($api_req);

		//Detect connection error ASAP.
		if ( isset( $result['error'] ) )
			textwise_ajax_connection_error( $wpAjax, 'wiki', $id, $result['error'] );

		$wikiString = "{title:'%s', description:'%s', landingPageUrl:'%s', channelTitle: '%s', channelLink: '%s'}";
		$wiki_results = null;
		if(isset($result['matches']))
			$wiki_results = $result['matches'];
		else
			$wiki_results = array();
		$arrWiki = array();
		foreach ($wiki_results as $m) {
			$arrWiki[] = sprintf($wikiString,
				textwise_esc_json($m['title']),
				textwise_esc_json(substr($m['description'],0,200)),
				textwise_esc_json($m['landingPageUrl']),
				textwise_esc_json($m['channelTitle']),
				textwise_esc_json($m['channelLink'])
				);
		}
		$data = implode($arrWiki, ",");

		$wpAjax->add(array(
			'what' => 'wiki',
			'id' => $id,
			'data' => $id ? $data : '',
			'supplemental' => isset($result['error']) ? $result : array()
			));
	}

	//Products -> Amazon
	if (get_option('textwise_product_enable') == '1') {
		$api_req['nMatches'] = '10';
		$api_req['indexId'] = 'amazon';
		$api_req['fields'] = 'title,description,landingPageUrl,imageUrl';
		$result = $api->match($api_req);

		//Detect connection error ASAP.
		if ( isset( $result['error'] ) )
			textwise_ajax_connection_error( $wpAjax, 'products', $id, $result['error'] );

		$prod_results = null;
		if ( isset($result['matches']) ) {
			$prod_results = $result['matches'];
		} else {
			$prod_results = array();
		}
		$prodString = "{title:'%s', description:'%s', landingPageUrl:'%s', imageUrl:'%s'}";
		$uniqueMatches = array();
		$arrProducts = array();
		$dupFields = array('title' => 40, 'description' => 140);
		foreach ($prod_results as $m) {
			$m['description'] = substr($m['description'],0,140);
			if (!textwise_objDup($uniqueMatches, $m, $dupFields)) {
				$uniqueMatches[] = $m;
			}
			if (count($uniqueMatches) >= 5) { break; }
		}

		foreach ($uniqueMatches as $m) {
			$arrProducts[] = sprintf($prodString,
				textwise_esc_json($m['title']),
				textwise_esc_json($m['description']),
				textwise_esc_json($m['landingPageUrl']),
				textwise_esc_json($m['imageUrl'])
			);
		}
		$data = implode($arrProducts, ",");

		$wpAjax->add(array(
			'what' => 'products',
			'id' => $id,
			'data' => $id ? $data : '',
			'supplemental' => isset($result['error']) ? $result : array()
			));
	}

	//Send combined results back to client
	$wpAjax->send();
}

//Escape text value for use in JSON string
function textwise_esc_json($text) {
	$trans['"'] = '\"';
	$trans["'"] = "\'";
	$trans['\\'] = '\\\\';
	$trans['/'] = '\/';
	$trans["\n"] = ' ';	//Strip carraige returns
	$trans["\r"] = '';
	$trans["\t"] = '\\t';
	return strtr($text, $trans);
}

//Convert serialized value into an object array for use in output formatting
//When making changes, see matching calls in textwise.js
//Current format:
// Record separator: \r\n\r\n
// Column separator: \n
// Key/Val separator: \t
function textwise_deserialize_input($content) {
	$arrValidCols = array('title', 'description', 'landingPageUrl', 'imageUrl', 'channelTitle', 'channelLink');
	$result = array();
	$records = explode("\r\n\r\n", $content);
	foreach ($records as $record) {
		$rows = explode("\n", $record);
		$rowdata = array();
		foreach ($rows as $row) {
			$cols = explode("\t", $row);
			if (in_array($cols[0], $arrValidCols)) {
				$rowdata[$cols[0]] = $cols[1];
			}
		}
		if (count($rowdata) > 0) {
			$result[] = $rowdata;
		}
	}
	return $result;
}

//Serialize object array for storage as string in WP database. See textwise_deserialize_input for details
function textwise_serialize_input($dataobj) {
	$result = '';
	foreach ($dataobj as $obj) {
		foreach ($obj as $key => $val) {
			$result .= $key."\t".$val."\n";
		}
		$result .= "\n";
	}
	return $result;
}

//Add Textwise Suggestions to article text at display time
function textwise_content_filter($content = '') {
	$logoPlace = get_option('textwise_logo_place') ? get_option('textwise_logo_place') : 'section';
	switch (get_option('textwise_use_logo')) {
		case '1':
			$logoSrc = get_bloginfo('wpurl').'/wp-content/plugins/textwise/img/textwise_logo.png';
			break;
		case '2':
			$logoSrc = get_bloginfo('wpurl').'/wp-content/plugins/textwise/img/textwise_logo_bw.png';
			break;
		case '3':
			$logoSrc = get_bloginfo('wpurl').'/wp-content/plugins/textwise/img/textwise_logo_i.png';
			break;
		case '4':
			$logoSrc = get_bloginfo('wpurl').'/wp-content/plugins/textwise/img/textwise_logo_bwi.png';
			break;
		default:
			$logoSrc = get_bloginfo('wpurl').'/wp-content/plugins/textwise/img/textwise_logo.png';
	}
	$logoTag = 'Powered by <a href="http://www.textwise.com/" target="_blank"><img border="0" src="%s" alt="TextWise" align="top" /></a>';
	$logoHtml = $logoSrc != '' ? sprintf($logoTag, $logoSrc) : '';

	if ($logoPlace == 'section') {
		$logoSubHead = ': '.$logoHtml;
	} else if ($logoPlace == 'bottomleft') {
		$logoHtml = '<div class="bottomleft">'.$logoHtml.'</div>';
	} else if ($logoPlace == 'bottomright') {
		$logoHtml = '<div class="bottomright">'.$logoHtml.'</div>';
	}

	$post_meta = get_post_custom();
	//Is it enabled, are there results to display, for each section?
	if (get_option('textwise_rss_enable') == 1 && isset($post_meta['_tw_rss_list'])) {
		$objRss = textwise_deserialize_input(trim(stripslashes($post_meta['_tw_rss_list'][0])));
		$rss_enable = (count($objRss) > 0);
	} else {
		$rss_enable = false;
	}

	if (get_option('textwise_wiki_enable') == 1 && isset($post_meta['_tw_wiki_list'])) {
		$objWiki = textwise_deserialize_input(trim(stripslashes($post_meta['_tw_wiki_list'][0])));
		$wiki_enable = (count($objWiki) > 0);
	} else {
		$wiki_enable = false;
	}

	if (get_option('textwise_product_enable') == 1 && isset($post_meta['_tw_product_list'])) {
		$objProduct = textwise_deserialize_input(trim(stripslashes($post_meta['_tw_product_list'][0])));
		$product_enable = (count($objProduct) > 0);
	} else {
		$product_enable = false;
	}

	$strRss = '<li><a href="%s">%s</a> :: <em><a href="%s">%s</a></em></li>';
	$strWiki = '<li><a href="%s">%s</a></li>';
	$strProduct = '<li><a href="%s">%s</a></li>';
	$strProduct = <<<_EOF_
	<tr class="tw_itemrow">
		<td class="tw_imagecell"><img src="%s" width="120" alt="%s" /></td>
		<td>
			<span class="title%s"><a href="%s">%s</a></span>
			<span class="source">:: Amazon</span>
			<span class="description small"><small>%s</small></span>
		</td>
	</tr>
_EOF_;

	if ( get_option('textwise_list_css') == 1 ) {
		$ulTag = '<ul class="forceleft">';
		$forceLeft = ' forceleft';
	} else {
		$ulTag = '<ul>';
		$forceLeft = '';
	}
	//If any  info is turned on and selected, build the snippet
	$links = '';
	if ($rss_enable || $wiki_enable || $product_enable) {
		$links = '<div id="textwise_suggestions">';
		if ($rss_enable) {
			$links .= "<h4 id='twBlogs'>Similar Blog & News Articles$logoSubHead</h4>";
			$links .= $ulTag;
			foreach ($objRss as $obj) {
				$links .= sprintf($strRss, $obj['landingPageUrl'], $obj['title'], $obj['channelLink'], $obj['channelTitle']);
			}
			$links .= '</ul>';
		}

		if ($wiki_enable) {
			$links .= "<h4 id='twWiki'>Similar Wikipedia Articles$logoSubHead</h4>";
			$links .= $ulTag;
			foreach ($objWiki as $obj) {
				$links .= sprintf($strWiki, $obj['landingPageUrl'], $obj['title']);
			}
			$links .= '</ul>';
		}

		if ($product_enable) {
			$links .= "<h4 id='twProducts'>Similar Products$logoSubHead</h4><table class=\"tw_products\" border=\"0\">";
			foreach ($objProduct as $obj) {
				$links .= sprintf($strProduct, $obj['imageUrl'], $obj['title'], $forceLeft, textwise_amazon_ref($obj['landingPageUrl']), $obj['title'], $obj['description']);
			}
			$links .= '</table>';
		}

		if ($logoPlace == 'bottomleft' || $logoPlace == 'bottomright') { $links .= $logoHtml; }

		$links .= '</div>';
	}
	return $content . $links;
}


//Capture selections from an article as it's being saved by the editor and store with article's metadata
function textwise_savepost($post_ID) {
	$post_type = !empty( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : 'post';
	if ( $post_type != 'post' )
		return;

	update_post_meta($post_ID, '_tw_tag_list',		!empty( $_POST['textwise_tag_input'] )     ? $_POST['textwise_tag_input']     : '' );
	update_post_meta($post_ID, '_tw_cat_list',		!empty( $_POST['textwise_cat_input'] )     ? $_POST['textwise_cat_input']     : '' );
	update_post_meta($post_ID, '_tw_video_list',	!empty( $_POST['textwise_video_input'] )   ? $_POST['textwise_video_input']   : '' );
	update_post_meta($post_ID, '_tw_image_list',	!empty( $_POST['textwise_image_input'] )   ? $_POST['textwise_image_input']   : '' );
	update_post_meta($post_ID, '_tw_rss_list',		!empty( $_POST['textwise_rss_input'] )     ? $_POST['textwise_rss_input']     : '' );
	update_post_meta($post_ID, '_tw_wiki_list',		!empty( $_POST['textwise_wiki_input'] )    ? $_POST['textwise_wiki_input']    : '' );
	update_post_meta($post_ID, '_tw_product_list',	!empty( $_POST['textwise_product_input'] ) ? $_POST['textwise_product_input'] : '' );
}

//Create JS settings object with server-side data..
function textwise_dataobject() {
	global $post_ID, $wp_version;
	$autoupdate         = get_option('textwise_autoupdate') ? (int) get_option('textwise_autoupdate') : 0;
	$tag_enable         = (get_option('textwise_tag_enable') == 1) ? 1 : 0;
	$contentlink_enable = (get_option('textwise_contentlink_enable') == 1) ? 1 : 0;
	$cat_enable         = (get_option('textwise_category_enable') == 1) ? 1 : 0;
	$video_enable       = (get_option('textwise_video_enable') == 1) ? 1 : 0;
	$image_enable       = (get_option('textwise_image_enable') == 1) ? 1 : 0;
	$rss_enable         = (get_option('textwise_rss_enable') == 1) ? 1 : 0;
	$wiki_enable        = (get_option('textwise_wiki_enable') == 1) ? 1 : 0;
	$product_enable     = (get_option('textwise_product_enable') == 1) ? 1 : 0;

	$post_meta = get_post_custom($post_ID);
	$tag_list = isset($post_meta['_tw_tag_list']) ? implode($post_meta['_tw_tag_list'], ',') : '';
	$cat_list = isset($post_meta['_tw_cat_list']) ? implode($post_meta['_tw_cat_list'], ',') : '';
	$output = <<<_EOF_
<script>
var textwise_dataobject = {
	"wpVersion" : '$wp_version',
	"autoupdate" : $autoupdate,
	"tag_enable" : $tag_enable,
	"contentlink_enable" : $contentlink_enable,
	"cat_enable" : $cat_enable,
	"image_enable" : $image_enable,
	"video_enable" : $video_enable,
	"rss_enable" : $rss_enable,
	"wiki_enable" : $wiki_enable,
	"product_enable" : $product_enable,
	"tag_list" : '$tag_list',
	"cat_list" : '$cat_list',
	"lastUpdateSuccess" : false
};
</script>
_EOF_;

	echo $output;
}

//Replace tag value with Amazon ref code if provided
function textwise_amazon_ref($link) {
	$tag = get_option('textwise_amazon_ref');
	$tag = $tag ? $tag : TEXTWISE_AMAZON_REF;	//Replace with default
	$link = preg_replace('/([\?&])tag=([^&#]*)/', '\1tag='.urlencode($tag), $link);
	return $link;
}

//Check objects for matching properties
function textwise_objDup($objArray, $matchTest, $arrFields) {
	foreach ($objArray as $obj) {
		$matched = 0;
		foreach ($arrFields as $f => $len) {
			if (substr($obj[$f], 0, $len) == substr($matchTest[$f], 0, $len)) { $matched++; }
		}
		if ($matched == count($arrFields)) { return true; }
	}

	return false;
}

//Custom CSS used when viewing output
//Use the filter to provide an alternate stylesheet URL or return an empty string to disable it.
function textwise_frontend_style() {
	$style_path =  apply_filters( 'textwise_frontend_style_path', plugins_url( '/textwise_post.css', __FILE__ ) );
	if ( !empty( $style_path ) )
		wp_enqueue_style('textwise_post', $style_path );
}

?>
