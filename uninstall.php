<?php

//Safety checks:
//Cannot execute by direct path, only frop WP
if( !defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
	die('Cannot run directly');

//Cannot execute unless you have proper privs
if ( ! current_user_can('delete_plugins') )
	wp_die(__('You do not have sufficient permissions to delete plugins for this blog.'));

//All clear:

//Delete options
delete_option('textwise_amazon_ref');
delete_option('textwise_api_token');
delete_option('textwise_autoupdate');
delete_option('textwise_box_position');
delete_option('textwise_category_enable');
delete_option('textwise_conflict_warning');
delete_option('textwise_contentlink_enable');
delete_option('textwise_image_enable');
delete_option('textwise_list_css');
delete_option('textwise_logo_place');
delete_option('textwise_product_enable');
delete_option('textwise_rss_enable');
delete_option('textwise_tag_enable');
delete_option('textwise_use_logo');
delete_option('textwise_video_enable');
delete_option('textwise_wiki_enable');

//Delete post meta
$metakeys[] = '_tw_tag_list';
$metakeys[] = '_tw_cat_list';
$metakeys[] = '_tw_video_list';
$metakeys[] = '_tw_image_list';
$metakeys[] = '_tw_rss_list';
$metakeys[] = '_tw_wiki_list';
$metakeys[] = '_tw_product_list';

$allposts = get_posts('numberposts=-1&post_type=post&post_status=');

foreach( $allposts as $postinfo) {
	foreach( $metakeys as $meta) {
		delete_post_meta($postinfo->ID, $meta);
	}
}


?>
