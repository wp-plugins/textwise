<script>
jQuery(function(){
	jQuery('input[name=textwise_logo_place]').change(textwise_opt_logo);

});

function textwise_opt_logo() {
	var opt = jQuery(this).val();
	if (opt == 'none') {
		jQuery('#use_logo').slideUp('fast');
	} else {
		jQuery('#use_logo').slideDown('fast');
	}
}
</script>

<div class="wrap">
	<h2>Textwise Plugin Settings</h2>
	<form method="post" action="options.php">
<?php	wp_nonce_field('update-options') ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">API Token</th>
				<td>
					<input type="text" name="textwise_api_token" value="<?php echo get_option('textwise_api_token'); ?>" />
					<p><em>This plugin uses the TextWise SemanticHacker API.</em> Registration for the SemanticHacker API is necessary for the plugin to function properly. Once registered, you'll be sent the token string to use here.</p>
					<p><em>Registration is easy and free &#151; to register go to <a target="_blank" href="http://www.semantichacker.com/user/register">www.semantichacker.com/user/register</a>.</em></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Auto-update</th>
				<td>Automatically scan for suggestions <select name="textwise_autoupdate">
					<option value="0" <?php echo ( get_option('textwise_autoupdate') == '0') ? 'selected' : ''; ?>>never</option>
					<option value="60" <?php echo ( get_option('textwise_autoupdate') == '60') ? 'selected' : ''; ?>>every minute</option>
					<option value="300" <?php echo ( get_option('textwise_autoupdate') == '300') ? 'selected' : ''; ?>>every 5 minutes</option>
					</select>

				</td>
			<tr valign="top">
				<th scope="row">Select TextWise Content Suggestions</th>
				<td>Choose how you would like TextWise help you enhance your blog and post content.<br />
					<input type="checkbox" id="textwise_category_enable" name="textwise_category_enable" value="1" <?php echo ( get_option('textwise_category_enable') == '1') ? 'checked' : ''; ?> /> <label for="textwise_category_enable">Categories*</label><br />
					<input type="checkbox" id="textwise_tag_enable" name="textwise_tag_enable" value="1" <?php echo ( get_option('textwise_tag_enable') == '1') ? 'checked' : ''; ?> /> <label for="textwise_tag_enable">Tags*</label><br />
					<input type="checkbox" id="textwise_contentlink_enable" name="textwise_contentlink_enable" value="1" <?php echo ( get_option('textwise_contentlink_enable') == '1') ? 'checked' : ''; ?> /> <label for="textwise_contentlink_enable">Content Links*</label><br />
					<input type="checkbox" id="textwise_video_enable" name="textwise_video_enable" value="1" <?php echo ( get_option('textwise_video_enable') == '1') ? 'checked' : ''; ?> /> <label for="textwise_video_enable">Videos*</label><br />
					<input type="checkbox" id="textwise_image_enable" name="textwise_image_enable" value="1" <?php echo ( get_option('textwise_image_enable') == '1') ? 'checked' : ''; ?> /> <label for="textwise_image_enable">Images*</label><br />
					<input type="checkbox" id="textwise_rss_enable" name="textwise_rss_enable" value="1" <?php echo ( get_option('textwise_rss_enable') == '1') ? 'checked' : ''; ?> /> <label for="textwise_rss_enable">Blogs &amp; News</label><br />
					<input type="checkbox" id="textwise_wiki_enable" name="textwise_wiki_enable" value="1" <?php echo ( get_option('textwise_wiki_enable') == '1') ? 'checked' : ''; ?> /> <label for="textwise_wiki_enable">Wikipedia</label><br />
					<input type="checkbox" id="textwise_product_enable" name="textwise_product_enable" value="1" <?php echo ( get_option('textwise_product_enable') == '1') ? 'checked' : ''; ?> /> <label for="textwise_product_enable">Products</label><br />
					<br />
					* Deselecting these items will not automatically remove previously selected suggestions from your posts.
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Select Logo Option</th>
				<td>Choose how you would like to have the TextWise logo displayed on your blog.<br /><br />
					Logo placement for the Similar News and Blog Articles, Wikipedia Articles and Product sections:<br />
					<label for="textwise_logo_place_bottomleft"><input type="radio" id="textwise_logo_place_bottomleft" name="textwise_logo_place" value="bottomleft" <?php echo ( get_option('textwise_logo_place') == 'bottomleft') ? 'checked' : ''?> />
						Bottom LEFT of all the sections combined</label><br />
					<label for="textwise_logo_place_bottomright"><input type="radio" id="textwise_logo_place_bottomright" name="textwise_logo_place" value="bottomright" <?php echo ( get_option('textwise_logo_place') == 'bottomright') ? 'checked' : ''?> />
						Bottom RIGHT of all the sections combined</label><br />
					<label for="textwise_logo_place_section"><input type="radio" id="textwise_logo_place_section" name="textwise_logo_place" value="section" <?php echo ( get_option('textwise_logo_place') == 'section') ? 'checked' : ''?> />
						In each individual section</label><br />
					<label for="textwise_logo_place_none"><input type="radio" id="textwise_logo_place_none" name="textwise_logo_place" value="none" <?php echo ( get_option('textwise_logo_place') == 'none') ? 'checked' : ''?> />
						None</label><br />
					<br />
					<div id="use_logo">
						Which variation of the logo should be used?<br />
						<label for="logo-1"><input id="logo-1" type="radio" name="textwise_use_logo" value="1" <?php echo ( get_option('textwise_use_logo') == '1') ? 'checked' : ''; ?> />
							Powered by <img src="<?=get_bloginfo('wpurl')?>/wp-content/plugins/textwise/img/textwise_logo.gif" alt="standard logo" />
							<em>(transparent image for use with themes with light backgrounds)</em>
							</label><br />
						<label for="logo-2"><input id="logo-2" type="radio" name="textwise_use_logo" value="2" <?php echo ( get_option('textwise_use_logo') == '2') ? 'checked' : ''; ?>/>
							Powered by <img src="<?=get_bloginfo('wpurl')?>/wp-content/plugins/textwise/img/textwise_logo_bw.gif" alt="grayscale for light backgrounds" />
							<em>(transparent image for use with themes with light backgrounds)</em>
							</label><br />
						<label for="logo-3"><input id="logo-3" type="radio" name="textwise_use_logo" value="3" <?php echo ( get_option('textwise_use_logo') == '3') ? 'checked' : ''; ?>/>
							Powered by <img style="padding: 2px; background: black;" src="<?=get_bloginfo('wpurl')?>/wp-content/plugins/textwise/img/textwise_logo_i.gif" alt="color for dark backgrounds" />
							<em>(transparent image for use with themes with dark backgrounds)</em>
							</label><br />
						<label for="logo-4"><input id="logo-4" type="radio" name="textwise_use_logo" value="4" <?php echo ( get_option('textwise_use_logo') == '4') ? 'checked' : ''; ?>/>
							Powered by <img style="padding: 2px; background: black;" src="<?=get_bloginfo('wpurl')?>/wp-content/plugins/textwise/img/textwise_logo_bwi.gif" alt="grayscale for dark backgrounds" />
							<em>(transparent image for use with themes with dark backgrounds)</em>
							</label>
					</div>
					<br />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Related Link formatting</th>
				<td>Some themes (such as WordPress' default) justify lists which affects the spacing of our results in the article output. How should this be handled?<br />
					<label for="textwise_list_css_noforce"><input type="radio" id="textwise_list_css_noforce" name="textwise_list_css" value="0" <?php echo ( get_option('textwise_list_css') != '1') ? 'checked' : ''; ?> /> Follow the theme's stylesheet</label><br />
					<label for="textwise_list_css_force"><input type="radio" id="textwise_list_css_force" name="textwise_list_css" value="1" <?php echo ( get_option('textwise_list_css') == '1') ? 'checked' : ''; ?> /> Force list items to be left justified</label>
				</td>
			</tr>
			<tr valign="top" id="conflicts">
				<th scope="row">Plugin Conflict Warning</th>
				<td><label for="textwise_conflict_warning"><input type="checkbox" id="textwise_conflict_warning" name="textwise_conflict_warning" value="1" <?php echo ( get_option('textwise_conflict_warning') == '1') ? 'checked' : ''; ?>> Disable warning</label><br />
				Some other Dashboard plugins may cause the Textwise plugin to behave erratically. When this is the case, a warning will be displayed.
				</td>
			</tr>
<?php if ( ($GLOBALS['wp_version'] < '2.8.6') ) { ?>
			<tr valign="top" id="versionwarning">
				<th scope="row">Plugin Version Warning</th>
				<td><label for="textwise_version_warning"><input type="checkbox" id="textwise_version_warning" name="textwise_version_warning" value="1" <?php echo ( get_option('textwise_version_warning') == '1') ? 'checked' : ''; ?>> Disable warning</label><br />
				This plugin has not been tested with your version of WordPress. Use at your own risk.  See <a href="http://wordpress.org/extend/plugins/textwise/">our plugin page</a> for more information.
				</td>
			</tr>
<? } ?>
			<tr valign="top">
				<th scope="row">Amazon Referral ID</th>
				<td><input type="text" name="textwise_amazon_ref" value="<?php echo get_option('textwise_amazon_ref'); ?>"></td>
			</tr>
<?php if ( !($GLOBALS['wp_version'] < '2.7') ) { ?>
			<tr valign="top">
				<th scope="row">Reset control positions</th>
				<td><label for="textwise_box_position"><input type="checkbox" id="textwise_box_position" name="textwise_box_position" value="1" /> Move TextWise controls closer to the text editor</label></td>
			</tr>
<? } ?>
		</table>
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="page_options" value="textwise_api_token,textwise_category_enable,textwise_tag_enable,textwise_contentlink_enable,textwise_video_enable,textwise_image_enable,textwise_rss_enable,textwise_wiki_enable,textwise_product_enable,textwise_logo_place,textwise_use_logo,textwise_autoupdate,textwise_conflict_warning,textwise_amazon_ref,textwise_box_position,textwise_list_css,textwise_version_warning" />
		<p class="submit">
			<input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" />
		</p>
	</form>
</div>
