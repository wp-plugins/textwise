<?php
/*
Plugin Name: TextWise Similarity Search
Plugin URI: http://textwise.com/tools/wordpress-plugin-0
Description: SemanticHacker API integration for WordPress
Version: 1.1.4
Author: TextWise, LLC
Author URI: http://www.textwise.com/

	Copyright 2008-2010  TextWise, LLC.  ( email : admin@semantichacker.com )

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
add_action('wp_head', 'textwise_global_header');
add_filter('the_content', 'textwise_content_filter');

//Actions to include on every admin page
add_action('admin_menu', 'textwise_admin_page');
add_action('admin_notices', 'textwise_check_token');
add_action('admin_notices', 'textwise_check_detectconflict');
add_action('admin_notices', 'textwise_check_versions');
add_action('admin_notices', 'textwise_dependency_warning');

//Supported in 2.7+
add_filter('plugin_action_links_textwise/textwise.php', 'textwise_plugin_links' );

//Load when in admin section
add_action('admin_init', 'textwise_admin_init');

//Load on editor pages only, if token exists and is valid, and any service is enabled.
if ( textwise_check_dependencies() && get_option('textwise_api_token') && get_option('textwise_api_token_invalid') == 'n' &&
		( get_option('textwise_tag_enable') == '1' ||
			get_option('textwise_contentlink_enable') == '1' ||
			get_option('textwise_category_enable') == '1' ||
			get_option('textwise_video_enable') == '1' ||
			get_option('textwise_image_enable') == '1' ||
			get_option('textwise_rss_enable') == '1' ||
			get_option('textwise_wiki_enable') == '1' ||
			get_option('textwise_product_enable') == '1' )
	) {
	add_action('load-post.php', 'textwise_init_editor');
	add_action('load-post-new.php', 'textwise_init_editor');
}

//AJAX Callbacks
add_action('wp_ajax_textwise_chk_sig', 'textwise_ajax_chk_sig');
add_action('wp_ajax_textwise_content_update', 'textwise_ajax_content_update');
add_action('wp_ajax_textwise_nonce', 'textwise_ajax_nonce');
add_action('wp_ajax_textwise_categoryinfo', 'textwise_ajax_categoryinfo');

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

if ($_POST['textwise_api_token'] && $_POST['textwise_api_token'] != get_option('textwise_api_token') ) {
	update_option('textwise_api_token_invalid', 'c');
}

function textwise_verify_token() {
	if ( !textwise_check_dependencies() ) { return; }

	$token = get_option('textwise_api_token');
	if ($token == '') {
		update_option('textwise_api_token_invalid', 'y');
	} else {
		//Make request to TextWise API for Signature
		$api = textwise_create_api();
		$api_req['c'] = 'wp';
		$api_req['content'] = '';
		$result = $api->signature($api_req);

		//Code 102: Invalid Token
		if (is_array($result['message']) && $result['message']['code'] == '102') {
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
//	In case we need to compare plugin version, too.
//	$plugin = get_plugin_data(WP_PLUGIN_DIR . '/textwise/textwise.php');
//	$plugin_version = $plugin['Version'];
	$infoUrl = get_bloginfo('wpurl'). '/wp-admin/options-general.php?page=textwise/textwise-settings.php#versionwarning';
	if ($wp_version < '2.8.6' && get_option('textwise_version_warning') != 1 ) {
		echo '<div class="updated"><p><b>Textwise:</b> This version of the Textwise Semantic Plugin has not been tested with your version of WordPress. If you experience any problems, please downgrade to plugin version 1.0.4. <em><a href="'.$infoUrl.'">Disable this warning</a></em></p></div>';

	}
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
	add_options_page('TextWise Plugin', 'TextWise', 8, dirname(__FILE__).'/textwise-settings.php');
}

//Only runs on post.php & post-new.php (see load-* action)
function textwise_init_editor() {
	global $wp_version;
	wp_enqueue_script('textwise', '/wp-content/plugins/textwise/textwise.js');
	wp_enqueue_style('textwise', '/wp-content/plugins/textwise/textwise.css');
	add_action('admin_print_scripts', 'textwise_dataobject');
	add_action('save_post', 'textwise_savepost');
	add_action('admin_footer', 'textwise_admin_footer');

	//Necessary for 2.7+ but introduced somewhere between 2.6.1 - 2.6.4
	if (function_exists('add_meta_box')) {
		if ($wp_version < '2.7') {
			$priority = 'low';
		} else {
			$priority = 'high';
			//Add a special box for the update button in 2.7
			add_meta_box('textwise_metabox_update', 'TextWise Similarity Search', 'textwise_metabox_update', 'post', 'side', 'high');
		}
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
	} else {
		//For older versions, add postboxes manually
		add_action('edit_form_advanced', 'textwise_postboxes');
	}
}

function textwise_metabox_update() {
	echo '';
}

//Set metabox positions if unset.
function textwise_metabox_position($force = false) {
	global $current_user, $table_prefix, $wp_version;
	//tagsdiv,categorydiv
	$mb_normal = array('textwise_metabox_contentlinks', 'textwise_metabox_images', 'textwise_metabox_videos', 'textwise_metabox_links');
	$mb_side = array('textwise_metabox_update');

	//Metaboxes were partially implemented, and the tag and category interfaces were normally under the editor
	if ($wp_version < '2.7') {
		$mb_normal = array_merge(array('tagdiv', 'categorydiv'), $mb_normal);
	}

	if ($wp_version < '3.0') {
		$metaboxorder = get_usermeta($current_user->ID, $table_prefix . 'metaboxorder_post');
	} else {
		$metaboxorder = get_user_meta($current_user->ID, 'meta-box-order_post', true);
	}

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

			if ($wp_version < '3.0') {
				update_user_option( $current_user->ID, "metaboxorder_post", $metaboxorder );
			} else {
				update_user_meta( $current_user->ID, 'meta-box-order_post', $metaboxorder );
			}
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
	<h4><img src="<?=get_bloginfo('wpurl')?>/wp-content/plugins/textwise/img/head_suggestions_blog-news.gif" alt="textwise Blog &amp; News Suggestions" /></h4>
	<div id="textwise_related_blogs"></div>
	<textarea id="textwise_rss_input" name="textwise_rss_input" style="display: none;"><?php echo trim(stripslashes($rss_list));?></textarea>
<?php	}
	if (get_option('textwise_wiki_enable') == '1') { ?>
	<h4><img src="<?=get_bloginfo('wpurl')?>/wp-content/plugins/textwise/img/head_suggestions_wiki.gif" alt="textwise Wiki Suggestions" /></h4>
	<div id="textwise_related_wiki"></div>
	<textarea id="textwise_wiki_input" name="textwise_wiki_input" style="display: none;" ><?php echo trim(stripslashes($wiki_list));?></textarea>

<?php	}
	if (get_option('textwise_product_enable') == '1') { ?>
	<h4><img src="<?=get_bloginfo('wpurl')?>/wp-content/plugins/textwise/img/head_suggestions_product.gif" alt="textwise Product Suggestions" /></h4>
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
	echo '<div style="display:none;" id="textwisesignonce"></div>';
	echo '<div id="textwise_bubble"></div>';
}

if ( !function_exists('wp_nonce_field') ) {
	function textwise_nonce_field($action = -1) { return; }
	$textwise_nonce = -1;
} else {
	function textwise_nonce_field($action = -1) { return wp_nonce_field($action); }
	$textwise_nonce = 'textwise-update';
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

//Check Signature of current editor text against last signature.
//Responses:
//	"nochange" .. signature is the same
//  "update" .. signature is different
function textwise_ajax_chk_sig() {
	//check_ajax_referer('textwisechksignonce');

	//Extract current content to make API call
	$id = $_POST['post_ID'];
	$api_req['c'] = 'wp';
	$api_req['content'] = stripslashes($_POST['content']);
	$api_req['showLabels'] = 'true';
	$api_req['extractor'] = 'html';
	$api_req['filter'] = 'html';

	//Make request to TextWise API for Signature
	$api = textwise_create_api();
	$result = $api->signature($api_req);

	//Determine whether the signature has changed enough to merit updating the other content
	$sig_new = textwise_create_sig_str($result['dimensions']);
	$sig_old = get_post_meta($id, '_tw_signature', true);

	//Only update if new signature is different and not empty (has significant words)
	if ($sig_new == $sig_old || $sig_new == '') {
		$data = 'nochange';
	} else {
		$data = 'update';
		update_post_meta($id, '_tw_signature', $sig_new);
	}

	//Build AJAX response
	$wpAjax = new WP_Ajax_Response( array(
		'what' => 'textwise_chk_sig',
		'id' => $id,
		'data' => $id ? $data : '',
		'supplemental' => array()
	) );
	$wpAjax->send();
}

//Respond to AJAX call with data from the API
//Calls are only made to enabled sections (see get_option() calls)
function textwise_ajax_content_update() {
	//check_ajax_referer( ..nonce.. );

	//$_POST has sent request info
	//Extract current content to make API call
	$id = $_POST['post_ID'];
	$api_req['c'] = 'wp';
	$api_req['content'] = stripslashes($_POST['content']);
	$api_req['showLabels'] = 'true';
	$api_req['filter'] = 'html';

	//Make request to TextWise API for Signature
	$api = textwise_create_api();
	$wpAjax = new WP_Ajax_Response();

	//Concepts needed for Tags and Content Links..
	if (get_option('textwise_tag_enable') == '1' || get_option('textwise_contentlink_enable')) {
		$api_req['includePositions'] = 'true';
		$result = $api->concept($api_req);
	}

	if ( is_array($result['concepts']) ) {
		$concept_results = $result['concepts'];
	} else {
		$concept_results = array();
	}
	if ( is_array($result['message']) ) {
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
			$arrContentLinks[] = textwise_esc_json(substr($filter['filteredText'], $c['positions'][0]['start'], $c['positions'][0]['end'] - $c['positions'][0]['start']+1));
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
		$cat_results = null;
		if ( isset($result['categories']) )
			$cat_results = $result['categories'];
		else
			$cat_results = array();
		$arrCategories = array();
		foreach ($cat_results as $c) {
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
		$vidString = "{title: '%s', thumbnailUrl: '%s', enclosureUrl: '%s', landingPageUrl: '%s', videoId: '%s'}";
		if( isset($result['matches']) ) {
			$vid_results = $result['matches'];
		} else {
			$vid_results = array();
		}
		$arrVideos = array();
		foreach ($vid_results as $m) {
			if ($m['thumnailUrl'] != '') {
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

//Generate Signature string which will be compared in the textwise_ajax_chk_sig() callback
//Currently just a comma-separated list of key:val pairs of dimension index & weights
function textwise_create_sig_str($dimensions) {
	$result = array();
	$maxDimensions = 10;	//How many dimensions should use in the sig string?
	for ($i=0; $i<count($dimensions) && $i<$maxDimensions; $i++) {
		$d = $dimensions[$i];
		$result[] = $d['index'].':'.$d['weight'];
	}
	return implode($result, ",");
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
			$logoSrc = get_bloginfo('wpurl').'/wp-content/plugins/textwise/img/textwise_logo.gif';
			break;
		case '2':
			$logoSrc = get_bloginfo('wpurl').'/wp-content/plugins/textwise/img/textwise_logo_bw.gif';
			break;
		case '3':
			$logoSrc = get_bloginfo('wpurl').'/wp-content/plugins/textwise/img/textwise_logo_i.gif';
			break;
		case '4':
			$logoSrc = get_bloginfo('wpurl').'/wp-content/plugins/textwise/img/textwise_logo_bwi.gif';
			break;
		default:
			$logoSrc = get_bloginfo('wpurl').'/wp-content/plugins/textwise/img/textwise_logo.gif';
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

	$post_meta = get_post_custom($post_ID);
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
	update_post_meta($post_ID, '_tw_tag_list',		$_POST['textwise_tag_input']);
	update_post_meta($post_ID, '_tw_cat_list',		$_POST['textwise_cat_input']);
	update_post_meta($post_ID, '_tw_video_list',	$_POST['textwise_video_input']);
	update_post_meta($post_ID, '_tw_image_list',	$_POST['textwise_image_input']);
	update_post_meta($post_ID, '_tw_rss_list',		$_POST['textwise_rss_input']);
	update_post_meta($post_ID, '_tw_wiki_list',		$_POST['textwise_wiki_input']);
	update_post_meta($post_ID, '_tw_product_list',	$_POST['textwise_product_input']);
}

//Create JS settings object with server-side data..
function textwise_dataobject() {
	global $post_ID, $wp_version;
	$autoupdate = get_option('textwise_autoupdate') ? 0+get_option('textwise_autoupdate') : 0;
	$tag_enable = (get_option('textwise_tag_enable') == 1) ? 1 : 0;
	$contentlink_enable = (get_option('textwise_contentlink_enable') == 1) ? 1 : 0;
	$cat_enable = (get_option('textwise_category_enable') == 1) ? 1 : 0;
	$video_enable = (get_option('textwise_video_enable') == 1) ? 1 : 0;
	$image_enable = (get_option('textwise_image_enable') == 1) ? 1 : 0;
	$rss_enable = (get_option('textwise_rss_enable') == 1) ? 1 : 0;
	$wiki_enable = (get_option('textwise_wiki_enable') == 1) ? 1 : 0;
	$product_enable = (get_option('textwise_product_enable') == 1) ? 1 : 0;

	$post_meta = get_post_custom($post_ID);
	$tag_list = isset($post_meta['_tw_tag_list']) ? implode($post_meta['_tw_tag_list'], ',') : '';
	$cat_list = isset($post_meta['_tw_cat_list']) ? implode($post_meta['_tw_cat_list'], ',') : '';
//	$rss_list = isset($post_meta['_tw_rss_list']) ? implode(explode($post_meta['_tw_rss_list'], "\n"), ',') : '';
//	$wiki_list = isset($post_meta['_tw_wiki_list']) ? implode(explode($post_meta['_tw_wiki_list'], "\n"), ',') : '';
//	$product_list = isset($post_meta['_tw_product_list']) ? implode(explode($post_meta['_tw_product_list'], "\n"), ',') : '';
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
function textwise_global_header() {
	echo '<link rel="stylesheet" href="'.get_bloginfo('wpurl').'/wp-content/plugins/textwise/textwise_post.css" type="text/css" media="screen" />';
}

?>
