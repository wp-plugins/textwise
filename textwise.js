/******************************
* (c) 2008-2012 TextWise, LLC *
******************************/

//Case insensitive 'contains' matches

jQuery.extend(jQuery.expr[':'], {
	contains : function(a, i, m) {
		return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase())>=0;
	}
});

var textwise_settings = {
	pluginDir: '../wp-content/plugins/textwise',
	postContentLast: '',
	lastUpdateStatus: 'none',
	ajaxComplete: true,
	offTop: 100,
	offLeft: 30,
	lastHover: false,
	tagchecklist: '#tagchecklist',
	taginput: '#tags-input',
	catlist: '#categories-all'
};

jQuery(document).ready(textwise_init);

//Initialization
function textwise_init() {
	//Only enable feature if the HTML element is available and able to be used.

	//Handle WP core r10222 of /wp-admin/edit-form-advanced.php changing tagchecklist from id to class
	if (!jQuery(textwise_settings.tagchecklist).length) {
		textwise_settings.tagchecklist = '.tagchecklist';
		textwise_settings.taginput = '#tax-input\\[post_tag\\]';
	}
	//Handle change for WP 3.0 taxonomy structure
	if (!jQuery(textwise_settings.taginput).length) {
		textwise_settings.taginput = '#tax-input-post_tag';
	}
	//Category container change for WP 3.0
	if (!jQuery(textwise_settings.catlist).length) {
		textwise_settings.catlist = '#category-all';
	}

	//Enable feature only if right elements are in place.
	textwise_dataobject.tag_enable = (textwise_dataobject.tag_enable == 1) && jQuery(textwise_settings.tagchecklist).length ? 1 : 0;
	textwise_dataobject.cat_enable = (textwise_dataobject.cat_enable == 1) && jQuery(textwise_settings.catlist).length ? 1 : 0;
	textwise_dataobject.contentlink_enable = (textwise_dataobject.contentlink_enable == 1) && jQuery('#postdivrich').length ? 1 : 0;

	//Redefine function from WP core to be compatible with 2.9+
	if (typeof new_tag_remove_tag != 'function') { new_tag_remove_tag = function(el){ tagBox.parseTags(this); } }
	if (typeof tag_update_quickclicks != 'function') { tag_update_quickclicks = function(el){ tagBox.quickClicks(document.getElementById('post_tag'));} }

	//Add content to existing elements.
	var helpImage = function(id){ return '<img id="'+id+'" class="textwise_help" src="'+textwise_settings.pluginDir+'/img/help.gif" alt="(?)" />';};
	//Tags
	if (textwise_dataobject.tag_enable == 1) {
		jQuery(textwise_settings.tagchecklist).after('<div><p><em class="textwise_powered_by">'
			+'<span class="textwise_powered_by_section">Concept Tag Suggestions</span><br />'
			+'Powered By <img src="'+textwise_settings.pluginDir+'/img/textwise_logo.png" alt="powered by TextWise" /></em> '
			+helpImage('textwise_tag_help')+'</p>'
			+'<ul id="textwise_tags"><li><em>Start entering content and click Update Textwise Suggestions to begin</em></li></ul></div>'
			+'<input id="textwise_tag_input" name="textwise_tag_input" style="display:none;"><br class="clearleft" />');
		jQuery('#textwise_tag_input').val(textwise_dataobject.tag_list);
	}
	//Categories
	if (textwise_dataobject.cat_enable == 1) {
		jQuery(textwise_settings.catlist).after('<div><p><em class="textwise_powered_by">'
			+'<span class="textwise_powered_by_section">Category Suggestions</span><br />'
			+'Powered By <img src="'+textwise_settings.pluginDir+'/img/textwise_logo.png" alt="powered by TextWise" /></em> '
			+helpImage('textwise_cat_help')+'</p>'
			+'<ul id="textwise_cats"> </ul></div>'
			+'<input id="textwise_cat_input" name="textwise_cat_input" style="display:none;"><br class="clearleft" />');
		jQuery('#textwise_cat_input').val(textwise_dataobject.cat_list);
	}

	//Content links
	if (textwise_dataobject.contentlink_enable == 1) {
		//Check for metabox in 2.7+ or append to postdivrich
		if (jQuery('#textwise_contentlinks_container').length) {
			jQuery('#textwise_contentlinks_container').append('<ul id="textwise_contentlinks"></li></ul><br class="clearleft"/>');
		} else {
			jQuery('#postdivrich').append('<div><p><em><img src="'+textwise_settings.pluginDir+'/img/head_suggestions_link.png" alt="TextWise Content Link Suggestions" /></em>'+helpImage('textwise_contentlink_help')+'</p><ul id="textwise_contentlinks"></li></ul></div><br class="clearleft"/>');
		}
	}

	//Image and video tag enhancement
	var titleBar;
	var tagLine = ' <em>- powered by TextWise Similarity Search</em>';

	if (textwise_dataobject.contentlink_enable == 1 && (titleBar = jQuery('#textwise_metabox_contentlinks h3'))) {
		titleBar = (titleBar.find('span').length) ? titleBar.find('span') : titleBar;
		titleBar.append(tagLine +' '+helpImage('textwise_contentlink_help'));
	}

	if (textwise_dataobject.image_enable == 1 && (titleBar = jQuery('#textwise_metabox_images h3'))) {
		titleBar = (titleBar.find('span').length) ? titleBar.find('span') : titleBar;
		titleBar.append(tagLine +' '+helpImage('textwise_image_help'));
	}
	if (textwise_dataobject.video_enable == 1 && (titleBar = jQuery('#textwise_metabox_videos h3'))) {
		titleBar = (titleBar.find('span').length) ? titleBar.find('span') : titleBar;
		titleBar.append(tagLine +' '+helpImage('textwise_video_help'));
	}
	if ( (textwise_dataobject.rss_enable == 1 || textwise_dataobject.wiki_enable == 1 || textwise_dataobject.product_enable == 1)
		&& (titleBar = jQuery('#textwise_metabox_links h3'))) {
		titleBar = (titleBar.find('span').length) ? titleBar.find('span') : titleBar;
		titleBar.append(tagLine +' '+helpImage('textwise_link_help'));
	}

	//Update button

	jQuery('#textwise_update_button').click(textwise_checkContent)
		.mousedown(function(){jQuery('#textwise_update_label').attr('src', textwise_settings.pluginDir+'/img/updatebutton_down.png')})
		.mouseup(function(){jQuery('#textwise_update_label').attr('src', textwise_settings.pluginDir+'/img/updatebutton.png')});

	jQuery('.textwise_help').click(textwise_help_bubble).mouseout(function(){jQuery('#textwise_bubble').hide();});

	//Always check content when loading page if not completely new
	setTimeout(textwise_contentUpdate, 2000);

	if (textwise_dataobject.autoupdate > 0) {
		setInterval(textwise_checkContent, textwise_dataobject.autoupdate * 1000);
	}

	function nodeChangeWatcher() {
		var ed;
		if (typeof tinyMCE != "undefined" && (ed = tinyMCE.getInstanceById('content')) && typeof ed != "undefined") {
			//Bind to editor's DOM Node Change event to sync with interface
			function delayedEventHandler(callback){
				var timer=setTimeout(function(){},1);
				return function delayEvent(){
					var that=this;
					var args=arguments;
					clearTimeout(timer);
					timer=setTimeout(function(){callback.apply(that,args)},1)
				}
			}
			var edDoc = ed.getDoc();
			if(document.implementation.hasFeature("Events","2.0")) {
				edDoc.addEventListener("DOMNodeInserted",delayedEventHandler(textwise_nodeChanged),false)
				//edDoc.addEventListener("DOMNodeInserted",textwise_nodeChanged,false)
				edDoc.addEventListener("DOMNodeRemoved",delayedEventHandler(textwise_nodeRemoved),false)
			} else {
				edDoc.onactivate=delayedEventHandler(function() {
					textwise_nodeChanged.call(this);
					textwise_nodeRemoved.call(this);
				});

				edDoc.onkeyup=function(win) {
					return function() {
						var e=win.event;
						if(e.keyCode==8||e.keyCode==46) {
							textwise_nodeRemoved.call(win.document)
						}
					}
				}(ed.getWin());
			}
		} else {	//Wait until rich editor it ready
			setTimeout(nodeChangeWatcher, 1000);
		}
	}
	nodeChangeWatcher();
}

/** Content processing and AJAX calls **/

//Why is this returning tinymce when it's not true??
function textwise_getEditor() {
	if (textwise_dataobject.wpVersion < '2.7') {
		return wpTinyMCEConfig.defaultEditor;
	} else {
		return tinyMCE && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden() ? 'tinymce' : 'html';
	}
}

// Force editor to process rich text content into HTML or grab cleaned HTML content
// Returns post title and content
function textwise_getContent() {
	var rich = (textwise_getEditor() == 'tinymce');
	var post_title = jQuery('#title').val();
	var post_content = '';

	if ( rich && tinyMCE.activeEditor ) {
		var ed = tinyMCE.activeEditor;
		if ( 'mce_fullscreen' == ed.id )
			tinyMCE.get('content').setContent(ed.getContent({format : 'raw'}), {format : 'raw'});
		tinyMCE.get('content').save();
		post_content = ed.getContent({format:'raw'});
	} else {
		post_content = jQuery('#content').val();
	}

	//Strip caption shortcodes as added by wpimagedit plugin
	post_content = post_content.replace(/\[(?:wp_)?caption([^\]]+)\]([\s\S]+?)\[\/(?:wp_)?caption\][\s\u00a0]*/g, '');

	//Strip default captions. Include user-defined captions in semantic search
	post_content = post_content.replace(/<dd .*?wp-caption-dd.*?>.*?<\/dd>/gi, '');
	var result = post_title+' '+post_content;
	if (result == ' ' || result == ' <p><br data-mce-bogus="1"></p>') { result = ''; }
	return result;
}

function textwise_getIDom() {
	var rich = (textwise_getEditor() == 'tinymce');
	var i_dom;
	if ( rich && tinyMCE.activeEditor ) {
		var ed = tinyMCE.activeEditor;
		i_dom = jQuery(ed.getBody());
	} else {
		i_dom = jQuery('<div>'+jQuery('#content').val()+'</div>');
	}
	return i_dom;
}


function textwise_checkContent() {
/*
 - Check to see if content has changed
 - If content is different make call to other services
 - Populate areas
*/
	if ( !textwise_settings.ajaxComplete ) {return;}
	textwise_updateStatus('on');

	var doCheck = true;

	// No update while thickbox is open (media buttons)
	if ( jQuery("#TB_window").css('display') == 'block' )
		doCheck = false;

	// FROM WP AUTOSAVE START //
	// (bool) is rich editor enabled and active
	var rich = (textwise_getEditor() == 'tinymce');
	/* Gotta do this up here so we can check the length when tinyMCE is in use */

	// Don't run while the TinyMCE spellcheck is on.  Why?  Who knows.
	if ( rich && tinyMCE.activeEditor.plugins.spellchecker && tinyMCE.activeEditor.plugins.spellchecker.active ) {
		doCheck = false;
	}
	// FROM WP AUTOSAVE END //

	var post_content = textwise_getContent();

	// Nothing to save or no change.
	if( post_content == '' ) {
		doCheck = false;
	} else if ( post_content == textwise_settings.postContentLast && textwise_settings.lastUpdateStatus == 'ok') {
		doCheck = false;
	}

	if ( doCheck ) {
		textwise_settings.postContentLast = post_content;
		textwise_contentUpdate();
	} else {
		//Turn off activity indicator in a sec
		setTimeout(function(){textwise_updateStatus('off');}, 1000);
	}
}

function textwise_contentUpdate() {
	if ( !textwise_settings.ajaxComplete ) { return; }

	textwise_updateStatus('on');
	var post_content = textwise_getContent();
	if (post_content == '') {
		textwise_updateStatus('off');
		return;
	}

	textwise_settings.ajaxComplete = false;
	textwise_settings.lastUpdateStatus = 'inprogress';
	var post_data = {
		action: "textwise_content_update",
		post_ID:  jQuery("#post_ID").val() || 0,
		content: post_content
	};
	jQuery.ajax({
		data: post_data,
		type: 'POST',
		url: './admin-ajax.php',
		success: textwise_contentUpdateCallback
	});
}

function textwise_contentUpdateCallback(response) {
	var res = wpAjax.parseAjaxResponse(response);

	var results = {};
	if ( res && res.responses && res.responses.length ) {
		for (i=0; i<res.responses.length; i++) {
			var resi = res.responses[i];
			results[resi.what] = eval("Array("+resi.data+")"); //Convert JSON Response into Array
			if (res.responses[i].supplemental.error) {
				alert('Response error: '+res.responses[i].supplemental.error);
				textwise_settings.lastUpdateStatus = 'error';
				textwise_updateStatus('off');
				textwise_settings.ajaxComplete = true;
				return;
			}
		}

		for (i in results) {
			switch (i) {
				case 'tags':
					if (textwise_dataobject.tag_enable != 1) break;
					var ulTag = jQuery('#textwise_tags');
					textwise_sync_tag_list();
					var tw_taglist = jQuery('#textwise_tag_input').val().split(',');
					jQuery.each(results[i], function(j, tag){
						if (jQuery.inArray(tag.label, tw_taglist) == -1) {
							ulTag.append('<li><a class="textwise_tag_link">'+textwise_html_esc(tag.label)+'</a></li>');
						}
						var tagItem = ulTag.find('a.textwise_tag_link:contains('+tag.label+')');
						if (tagItem.length) {
							tagItem.data('twdata', tag.weight);
						}
					});
					jQuery('#textwise_tags a').click(textwise_tag_toggle)
						.hover(textwise_tag_hover, function(){jQuery('#textwise_smallpreview').remove();});
					break;
				case 'contentlinks':
					if (textwise_dataobject.contentlink_enable != 1) break;
					var ulLinks = jQuery('#textwise_contentlinks');
					textwise_sync_contentlink_list();
					var tw_links = [];
					ulLinks.find('a.textwise_tag_link').each(function(i, tag){
						tw_links.push(jQuery(tag).text().toLowerCase());
					});
					var bodyText = textwise_getIDom().text();
					jQuery.each(results[i], function(i, tag) {
						if (jQuery.inArray(tag.toLowerCase(), tw_links) == -1
							&& bodyText.toLowerCase().indexOf(tag.toLowerCase()) != -1 ) {
							ulLinks.append('<li><a class="textwise_tag_link">'+textwise_html_esc(tag)+'</a></li>');
						}
					});
					jQuery('#textwise_contentlinks a').click(textwise_link_toggle);
					jQuery('#textwise_metabox_contentlinks').removeClass('closed');
					break;
				case 'categories':
					if (textwise_dataobject.cat_enable != 1) break;
					var ulCat = jQuery('#textwise_cats');
					var cur_catlist = [];
					textwise_sync_cat_list();
					var tw_catlist = jQuery('#textwise_cat_input').val().split(',');
					jQuery.each(tw_catlist, function (key,val) {
						trimVal = jQuery.trim(val);
						var wpcat = textwise_wpcat_find(trimVal);
						if (wpcat && wpcat.attr('checked')) {
							cur_catlist.push(trimVal);
						}
					});
					jQuery.each(results[i], function(i, catLine){
						jQuery.each(catLine.split('/'), function(j, cat){
							if (cat != 'Top' && jQuery.inArray(cat, cur_catlist) == -1) {
								cur_catlist.push(cat);
								ulCat.append('<li><a class="term">'+textwise_html_esc(cat)+'</a></li>');
							}
						});
					});
					jQuery('#textwise_cats a').click(textwise_cat_toggle);
					jQuery('#categorychecklist input').click(textwise_wpcat_toggle);
					break;
				case 'videos':
					if (textwise_dataobject.video_enable != 1) break;
					var tableVid = jQuery('#textwise_video_table tr');
					textwise_sync_video_list();
					var videoObjs = textwise_deserialize_metablock(jQuery('#textwise_video_input').val());
					var videolist = [];
					jQuery.each(videoObjs, function(j, objVid){
						videolist.push(objVid.enclosureUrl);
					});
					jQuery.each(results[i], function(j, objVid){
						if (jQuery.inArray(objVid.enclosureUrl, videolist) == -1) {
							tableVid.append(textwise_html_video(objVid, false));
						}
					});
					jQuery('#textwise_video_table .tw_thumbcell').click(textwise_video_toggle)
						.hover(textwise_video_hover, textwise_hoverend)
						.mousemove(textwise_preview_track);
					jQuery('#textwise_metabox_videos').removeClass('closed');
					break;
				case 'images':
					if (textwise_dataobject.image_enable != 1) break;
					textwise_sync_image_list();
					var imageObjs = textwise_deserialize_metablock(jQuery('#textwise_image_input').val());
					var imagelist = textwise_get_links(imageObjs);
					var tableImg = jQuery('#textwise_image_table tr');
					jQuery.each(results[i], function(i, objImg) {
						if (jQuery.inArray(objImg.landingPageUrl, imagelist) == -1) {
							tableImg.append(textwise_html_image(objImg, false));
						}
					});
					jQuery('#textwise_image_table .tw_thumbcell').click(textwise_image_toggle)
						.hover(textwise_image_hover, textwise_hoverend)
						.mousemove(textwise_preview_track);

					jQuery('#textwise_metabox_images').removeClass('closed');
					break;
				case 'rss':
					if (textwise_dataobject.rss_enable != 1) break;
					textwise_sync_rss_list();
					var linkObjs = textwise_deserialize_metablock(jQuery('#textwise_rss_input').val());
					var linklist = textwise_get_links(linkObjs);
					var c = linklist.length;
					var divRss = jQuery('#textwise_related_blogs')
					jQuery.each(results[i], function(i, objLink){
						if (jQuery.inArray(objLink.landingPageUrl, linklist) == -1) {
							divRss.append(textwise_html_rss(objLink, false, (c%2 == 0)));
							c++;
						}
					});
					jQuery('#textwise_related_blogs .item').click(textwise_rss_toggle);
					jQuery('#textwise_related_blogs .expando').click(function(){
						jQuery(this).toggleClass('open').parent().parent().find('.details').slideToggle('fast')
					});
					jQuery('#textwise_metabox_links').removeClass('closed');

					break;
				case 'wiki':
					if (textwise_dataobject.wiki_enable != 1) break;
					textwise_sync_wiki_list();
					var linkObjs = textwise_deserialize_metablock(jQuery('#textwise_wiki_input').val());
					var linklist = textwise_get_links(linkObjs);
					var c = linklist.length;
					var divWiki = jQuery('#textwise_related_wiki')
					jQuery.each(results[i], function(i, objLink){
						if (jQuery.inArray(objLink.landingPageUrl, linklist) == -1) {
							divWiki.append(textwise_html_wiki(objLink, false, (c%2 == 0)));
							c++;
						}
					});
					jQuery('#textwise_related_wiki .item').click(textwise_wiki_toggle);
					jQuery('#textwise_related_wiki .expando').click(function(){
						jQuery(this).toggleClass('open').parent().parent().find('.details').slideToggle('fast')
					});
					jQuery('#textwise_metabox_links').removeClass('closed');
					break;
				case 'products':
					if (textwise_dataobject.product_enable != 1) break;
					textwise_sync_product_list();
					var linkObjs = textwise_deserialize_metablock(jQuery('#textwise_product_input').val());
					var linklist = textwise_get_links(linkObjs);
					var c = linklist.length;
					var tabProd = jQuery('#textwise_product_table tr');
					jQuery.each(results[i], function(i, objLink){
						if (jQuery.inArray(objLink.landingPageUrl, linklist) == -1) {
							tabProd.append(textwise_html_product(objLink, false));
						}
					});
					jQuery('.tw_prodcell').click(textwise_product_toggle);
					jQuery('#textwise_metabox_links').removeClass('closed');
					break;
			}
		}
		textwise_settings.lastUpdateStatus = 'ok';
	} else if (response == '') {
		//Do nothing. They are probably leaving the page.
	} else {
		//Some problem with processing response..
		alert('Response error: '+response);
		textwise_settings.lastUpdateStatus = 'error';
	}
	textwise_updateStatus('off');
	textwise_settings.ajaxComplete = true;

}

function textwise_updateStatus(status) {
	switch (status) {
		case 'on':
			jQuery('#textwise_update_status img').attr('src', textwise_settings.pluginDir+'/img/updatestatus_wait.gif');
			break;
		case 'off':
			jQuery('#textwise_update_status img').attr('src', textwise_settings.pluginDir+'/img/updatestatus.gif');
			break;
		case 'error':
			jQuery('#textwise_update_status img').attr('src', textwise_settings.pluginDir+'/img/updatestatus.gif');
			break;
		default:
			jQuery('#textwise_update_status img').attr('src', textwise_settings.pluginDir+'/img/updatestatus.gif');
	}
}


/** Content links **/

function textwise_sync_contentlink_list() {
	var rich = (textwise_getEditor() == 'tinymce');
	if ( rich && tinyMCE.activeEditor ) {
		var ed = tinyMCE.activeEditor;
		i_dom = jQuery(ed.getBody());
	} else {
		i_dom = jQuery('<div>'+jQuery('#content').val()+'</div>');
	}

	var tw_links = i_dom.find('a.tw_contentlink');

	var ulLinks = jQuery('#textwise_contentlinks');
	ulLinks.empty();
	jQuery.each(tw_links, function(i, link) {
		var tag = jQuery(link).text();
		if (tag != '') {
			ulLinks.append('<li><a class="textwise_tag_link selected">'+textwise_html_esc(tag)+'</a></li>');
		}
	});
}

function textwise_link_toggle() {
	var term = jQuery(this).text();
	var re_term_esc = new RegExp('([\\\\\\|\\(\\)\\[\\{\\^\\$\\*\\+\\?\\.])', 'g');
	var esc_term = term.replace(re_term_esc, '\\$1');
	var re_term = new RegExp('(\\b|[^\\w])('+esc_term+')($|\\b|[^\\w])', 'i');
	var newTag = '$1<a class="tw_contentlink" href="http://en.wikipedia.org/w/index.php?search=$2&go=Go">$2</a>$3';
	var rich = (textwise_getEditor() == 'tinymce');
	var i_dom = textwise_getIDom();
	var tw_links = [];

	var tw_links = i_dom.find('a.tw_contentlink:contains('+term+')');
	if (tw_links.length) {
		tw_links.replaceWith(tw_links.text());
		jQuery(this).removeClass('selected');
		if( ! rich ) { jQuery('#content').val(jQuery(i_dom).html()); }
	} else {
		var items = jQuery(':contains('+term+'):first', i_dom);
		if(items.length) {
			var oldcontent = items.html();
			var newcontent;
			var bound = textwise_locate_boundaries(oldcontent);
			for (i=0; i<bound.length; i += 2) {
				var start = bound[i];
				var end = bound[i+1];
				//var len = end - start;
				newcontent = oldcontent.substring(0, start)
					+ oldcontent.substring(start, end+1).replace(re_term, newTag)
					+ oldcontent.substring(end+1, oldcontent.length);
				if (newcontent != oldcontent) { break; }
			}

			items.html(newcontent);
			jQuery(this).addClass('selected');
			if( ! rich ) { jQuery('#content').val(jQuery(i_dom).html()); }
		} else if( ! rich ) {
			var content = jQuery('#content').val();
			var newcontent = content.replace(re_term, newTag);
			if(newcontent != content) {
				jQuery(this).addClass('selected');
				jQuery('#content').val(newcontent);
			}
		}
	}

	if ( rich && tinyMCE.activeEditor ) { tinyMCE.activeEditor.getDoc().ignoreDOM = false; }

}

/** Tags **/

function textwise_tag_toggle() {
	var taginput = jQuery(this).text();
	var wp_taglist = jQuery(textwise_settings.taginput).val();
	var tw_taglist = jQuery('#textwise_tag_input').val();

	if (jQuery(this).hasClass('selected')) {
		//tag_remove
		var current_tags = wp_taglist.split(',');
		var new_tags = [];
		jQuery.each( current_tags, function( key, val ) {
			trimVal = jQuery.trim(val);
			if ( trimVal != '' && trimVal != taginput ) {
				new_tags.push( trimVal );
			}
		});

		var tw_tags = tw_taglist.split(',');
		var tw_new_tags = [];
		jQuery.each( tw_tags, function( key, val ) {
			trimVal = jQuery.trim(val);
			if ( trimVal != '' && trimVal != taginput ) {
				tw_new_tags.push( trimVal );
			}
		});

		jQuery(textwise_settings.taginput).val(new_tags.join(','));	//Wordpress' tag list
		jQuery('#textwise_tag_input').val(tw_new_tags.join(','));	//Textwise tag list
		tag_update_quickclicks();	//From WP codebase
		jQuery('#newtag').focus();
		jQuery(this).removeClass('selected');
	} else {
		//tag_add
		wp_taglist = wp_taglist + ',' + taginput;
		//Tag string cleanup from WP code, post.js: tag_flush_to_text()
		wp_taglist = wp_taglist.replace( /\s+,+\s*/g, ',' ).replace( /,+/g, ',' ).replace( /,+\s+,+/g, ',' ).replace( /,+\s*$/g, '' ).replace( /^\s*,+/g, '' );
		jQuery(textwise_settings.taginput).val( wp_taglist );

		tw_taglist = tw_taglist + ',' + taginput;
		tw_taglist = tw_taglist.replace( /\s+,+\s*/g, ',' ).replace( /,+/g, ',' ).replace( /,+\s+,+/g, ',' ).replace( /,+\s*$/g, '' ).replace( /^\s*,+/g, '' );
		jQuery('#textwise_tag_input').val(tw_taglist);
		tag_update_quickclicks();	//From WP codebase
		jQuery(this).addClass('selected');
	}
	textwise_wptag_rebind();
}

function textwise_tag_hover(e) {
	var weight = jQuery(this).data('twdata');
	if (!jQuery('#textwise_smallpreview').length) {
		jQuery("body").append('<div id="textwise_smallpreview"></div>');
	}
	var preview = jQuery('#textwise_smallpreview');

	if (jQuery(this).hasClass('selected')) { return; }

	var img = jQuery('<img width="45" height="29" alt="" src="" />');
	var iw = 1;
	if (weight > 0.0) { iw = 1; }
	if (weight > 0.4) { iw = 2; }
	if (weight > 0.6) { iw = 3; }
	if (weight > 0.8) { iw = 4; }

	img.attr('src', textwise_settings.pluginDir+'/img/bars'+iw+'.png');
//	img.attr('alt', iw);
	preview.append(img);
	var objOffset = jQuery(this).offset();
	preview.css("top",(objOffset.top - 39) + "px")
		.css("left",(objOffset.left + 15) + "px");
	setTimeout('jQuery("#textwise_smallpreview").fadeIn("fast")', 100);
}

//Sync meta value with current tag selections
//Rebuild tag list and input
function textwise_sync_tag_list() {
	var wp_tags = jQuery(textwise_settings.taginput).val().split(',');
	wp_tags = jQuery.map( wp_tags, jQuery.trim );
	var tw_tags = jQuery('#textwise_tag_input').val().split(',');
	var common_tags = [];
	jQuery.each( tw_tags, function( key, val ) {
		trimVal = jQuery.trim(val);
		if ( trimVal != '' && jQuery.inArray(trimVal, wp_tags) != -1 && jQuery.inArray(trimVal, common_tags) == -1) {
			common_tags.push( trimVal );
		}
	});

	var ulTag = jQuery('#textwise_tags');
	ulTag.empty();
	jQuery.each(common_tags, function(i, tag){
		ulTag.append('<li><a class="textwise_tag_link selected">'+tag+'</a></li>');
	});

	//Assign Wordpress tag buttons events to sync list
	textwise_wptag_rebind();

}

function textwise_wptag_remove() {
	var id = jQuery( this ).attr( 'id' );
	var num = id.substr( 10 );
	if (isNaN(num)) {
		num = id.split('-check-num-')[1];	//For 2.9+
	}
	var current_tags = jQuery( textwise_settings.taginput ).val().split(',');
	var tw_taglist = jQuery('#textwise_tag_input').val();
	var tw_tags = tw_taglist.split(',');
	var newtags = [];
	jQuery.each(tw_tags, function(i, obj){
		if (current_tags[num] != obj) {
			newtags.push(obj);
		}
	});

	jQuery('#textwise_tag_input').val(newtags.join(','));
	jQuery('#textwise_tags .textwise_tag_link.selected:contains('+current_tags[num]+')').removeClass('selected');
	setTimeout(textwise_wptag_rebind, 100);
}

function textwise_wptag_rebind() {
	jQuery(textwise_settings.tagchecklist+' a.ntdelbutton').unbind().click(textwise_wptag_remove).click(new_tag_remove_tag);
}
/** Categories **/

function textwise_sync_cat_list() {
	var tw_cats = jQuery('#textwise_cat_input').val().split(',');
	var common_cats = [];
	jQuery.each(tw_cats, function (key,val) {
		trimVal = jQuery.trim(val);
		var wpcat = textwise_wpcat_find(trimVal);
		if (wpcat && wpcat.attr('checked') && jQuery.inArray(trimVal, common_cats) == -1 ) {
			common_cats.push(trimVal);
		}
	});

	var ulCat = jQuery('#textwise_cats');
	ulCat.empty();
	jQuery.each(common_cats, function(i, cat){
		ulCat.append('<li><a class="term selected">'+cat+'</a></li>');
	});
}

function textwise_cat_toggle() {
	var catinput = jQuery(this).text();
	catinput = jQuery.trim(catinput);
	var tw_catlist = jQuery('#textwise_cat_input').val().split(',');
	var wpcatinput = textwise_wpcat_find(catinput);
	var newcats = [];
	jQuery.each(tw_catlist, function(i, cat){
		var trimCat = jQuery.trim(cat);
		trimCat = trimCat.toLowerCase();
		if (trimCat != '' && trimCat != catinput && jQuery.inArray(cat, newcats) == -1) {
			newcats.push(cat);
		}
	});

	if (jQuery(this).hasClass('selected')) {
		jQuery(this).removeClass('selected');
		if (wpcatinput) { wpcatinput.attr('checked', false); }
	} else {
		jQuery(this).addClass('selected');
		if (wpcatinput) {
			wpcatinput.attr('checked', true);
		} else {
			//Add it

			//WP 2.9.2 and earlier
			var newcatfield = '#newcat';
			var newcatbutton = '#category-add-sumbit';

			//WP 3.0
			if ( !jQuery(newcatfield).length ) {
				newcatfield = '#newcategory';
				newcatbutton = '#category-add-submit';
			}
			jQuery(newcatfield).focus();
			jQuery(newcatfield).val(catinput);
			jQuery(newcatbutton).click();
			jQuery(newcatfield).blur();

			function newcat_click() {
				var wpnewcat = textwise_wpcat_find(catinput);
				if (wpnewcat) {
					jQuery(wpnewcat).click(textwise_wpcat_toggle);
				} else {
					setTimeout(newcat_click, 500);
				}
			}
			setTimeout(newcat_click, 500);
		}

		//Add to tracked list
		newcats.push(catinput);
	}
	jQuery('#textwise_cat_input').val(newcats.join(','));
}

//Watch changes on the Wordpress category list and sync with matching results from API
function textwise_wpcat_toggle() {
	var tw_cats = jQuery('#textwise_cats a.term');
	var th = jQuery(this);
	var catname = jQuery.trim(th.parent().text());
	tw_cats.each(function(){
		if (jQuery(this).text().toLowerCase() == catname.toLowerCase()) { jQuery(this).click(); }
	});
}

function textwise_wpcat_find(catname) {
	var wpcatbox = jQuery('#categorychecklist');
	var wpcatinput = false;
	wpcatbox.find('label').each(function(){
		var th = jQuery(this);
		var name = jQuery.trim(th.text());
		if (catname.toLowerCase() == name.toLowerCase()) {
			wpcatinput = th.find('input');
		}
	});
	return wpcatinput;
}

/** Images **/

function textwise_sync_image_list() {
	var tableImg = jQuery('#textwise_image_table tr');
	tableImg.empty();
	var editor_images;
	var rich = (textwise_getEditor() == 'tinymce');
	if ( rich && tinyMCE.activeEditor ) {
		var ed = tinyMCE.activeEditor;
		//textwise_getContent();
		editor_images = jQuery(ed.getBody()).find('img.tw_selimg');
	} else {
		editor_images = jQuery(jQuery('#content').val()).find('img.tw_selimg');
	}
	var imagelist = [];
	jQuery.each(editor_images, function(i, objImg){
		imagelist.push(jQuery(objImg).attr('src'));
	});
	var image_meta = textwise_deserialize_metablock(jQuery('#textwise_image_input').val());
	var image_meta_final = [];
	jQuery.each(image_meta, function(i, objImg){
		if (jQuery.inArray(objImg.imageUrl, imagelist) != -1) {
			tableImg.append(textwise_html_image(objImg, true));
			image_meta_final.push(objImg);
		}
	});
	if (image_meta_final.length) {
		jQuery('#textwise_metabox_images').removeClass('closed');
	}
	jQuery('#textwise_image_input').val(textwise_serialize_metablock(image_meta_final));
}

function textwise_image_toggle(e) {
	var maxWidth = 200;	//Maximum width to insert give an inserted image.
	var htmlTag = '';

	if (jQuery(e.target).is('a')) { return; }
	var imageObj = jQuery(this).data('twdata');
	var imageData = textwise_deserialize_metablock(jQuery('#textwise_image_input').val());
	var rich = (textwise_getEditor() == 'tinymce');

	//Get the DOM from either editor
	var i_dom = textwise_getIDom();

	//Find the image based on the selection
	var editor_images = [];
	i_dom.find('img.tw_selimg').each(function(i, obj){
		if (jQuery(obj).attr('src') == imageObj.imageUrl) {
			editor_images.push(obj);
		}
	});


	if (editor_images.length) {
	//Remove from content
		jQuery.each(editor_images, function(i, obj){
			var cObj = textwise_tinymce_special_node(obj);
			jQuery(cObj).remove();
		});
		if ( !rich ) { jQuery('#content').val(jQuery(i_dom).html()); }
		var oldImageData = imageData;
		imageData = [];
		jQuery.each(oldImageData, function(i, obj){
			if (imageObj.imageUrl != obj.imageUrl) {
				imageData.push(obj);
			}
		});
		jQuery(this).removeClass('selected');
	} else {
	//Add to content
		//Render to check src processing
		imageObj.imageUrl = textwise_real_image_url(imageObj.imageUrl);

		if ( rich ) {
			htmlTag = textwise_imageInsert(imageObj, true);
			tinyMCE.activeEditor.focus(false);
			var el = tinyMCE.activeEditor.selection.getNode();
			var el2 = textwise_tinymce_special_node(el);
			//If selected node is a special node, insert in it, otherwise insert outside of it
			el = !el2 ? el : jQuery(el2).parent();
			if (jQuery(el).is('a')) { el = jQuery(el).parent(); }
			jQuery(el).prepend(htmlTag);

			textwise_getContent();
		} else {
			htmlTag = textwise_imageInsert(imageObj);
			edInsertContent(edCanvas, htmlTag);
		}
		imageData.push(imageObj);
		jQuery(this).addClass('selected');
	}

	if (rich && tinyMCE.activeEditor ) {
		tinyMCE.activeEditor.getDoc().ignoreDOM = false;
	}

	jQuery('#textwise_image_input').val(textwise_serialize_metablock(imageData));
	textwise_hoverend();

}

function textwise_imageInsert(imageObj, withCaption) {
	withCaption = typeof(withCaption) != 'undefined' ? withCaption : true;

	var htmlTag = '';
	var caption = 'Source: ' + imageObj.source;
	var imageWidth = 200;
	var imageLoader = new Image();
	imageLoader.src = imageObj.thumbnailUrl;
	var imageAspect = imageLoader.height / imageLoader.width;
	var imageHeight = isNaN(parseInt(imageWidth * imageAspect)) ? '' : parseInt(imageWidth * imageAspect);
	htmlTag = '<img class="tw_selimg" title="'+textwise_html_esc(imageObj.title)
		+'" src="'+textwise_html_esc(imageObj.imageUrl)
		+'" mce_src="'+textwise_html_esc(imageObj.imageUrl)+'" alt="'+textwise_html_esc(caption)
		+'" width="'+imageWidth+'" height="'+imageHeight+'" />';

	if (withCaption) {
		htmlTag = '<div class="mceTemp"><dl id="" class="wp-caption alignnone" style="width: 210px;"><dt class="wp-caption-dt">'+htmlTag+'</dt><dd class="wp-caption-dd">'+caption+'</dd></dl></div>';
	}
	return htmlTag;
}

//Let the browser load the image and normalize the URL string.
function textwise_real_image_url(url) {
	var imageloader = new Image();
	imageloader.src = url
	return imageloader.src;
}

function textwise_image_hover(e) {
	var imgObj = jQuery('.thumbimg img', this);
		//@todo: Broken in IE and image load issues

	var imgLoader = new Image();
	imgLoader.src = imgObj.attr('src');
	var imgHeight = imgLoader.height;
	var imgWidth = imgLoader.width;

	var mediaSource = jQuery('.media_source', this).text();
	var imgPane = textwise_get_preview();
	imgPane.html('<div id="textwise_preview_captions"><span class="title"><nobr>'+imgObj.attr('alt')+'</nobr></span><span class="mediasource">'+mediaSource+'</span></div><br class="clear" />');

	var previewImage = jQuery('<img id="textwise_preview_image" />');
	previewImage.attr('src', imgObj.attr('src'));
	imgPane.append(previewImage);

	function finish_hover() {
		if (imgHeight > 0 && imgWidth > 0) {
			jQuery('#textwise_preview_captions').append('<span class="picsize">&nbsp;'+imgWidth+' x '+imgHeight+' pixels</span>');
		}

		//Size image to friendly max..
		var tWidth = 290;	//target width
		var tHeight = 200;	//target height
		var tAspect = tWidth / tHeight;
		var sAspect = imgWidth / imgHeight;
		if ( sAspect > tAspect) {
			previewImage.width(tWidth);
		} else {
			previewImage.height(tHeight);
		}

		imgPane.css("top",(e.pageY - textwise_settings.offTop) + "px")
			.css("left",(e.pageX + textwise_settings.offLeft) + "px");
		imgPane.fadeIn("fast")
	}
	textwise_settings.lastHover = setTimeout(finish_hover, 500);
}



/** Videos **/

function textwise_video_source(objVid) {
	var vidSrc = null;
	//Pre 3.2 media placeholder puts video info in the title attribute
	var vidSrcData = jQuery(objVid).attr('title');
	if ( vidSrcData ) {
		vidSrc = eval("({"+vidSrcData+"})");
	} else {
		//Newer versions use something more complex..
		vidSrcData = jQuery(objVid).attr('data-mce-json');
		vidSrc = eval("("+vidSrcData+")").params;
	}

	return vidSrc;
}
function textwise_sync_video_list() {
	var tableVid = jQuery('#textwise_video_table tr');
	tableVid.empty();

	var rich = (textwise_getEditor() == 'tinymce');

	var videolist = [];
	if ( rich && tinyMCE.activeEditor ) {
		var ed = tinyMCE.activeEditor;
		//TinyMCE encloses the video in an image tag
		var editor_videos = jQuery(ed.getBody()).find('span.tw_selvid img.mceItemFlash');
		jQuery.each(editor_videos, function(i, objVid){
			var vidSrc = textwise_video_source(objVid);
			videolist.push(vidSrc.src);
		});
	} else {
		var editor_videos = jQuery(jQuery('#content').val()).find('span.tw_selvid object');
		jQuery.each(editor_videos, function(i, objVid){
			videolist.push(jQuery(objVid).find('param[name="src"]').attr('value'));
		});

	}

	var video_meta = textwise_deserialize_metablock(jQuery('#textwise_video_input').val());
	jQuery.each(video_meta, function(i, objVid){
		if (jQuery.inArray(objVid.enclosureUrl, videolist) != -1) {
			tableVid.append(textwise_html_video(objVid, true));
		} else {
			delete video_meta[i];
		}
	});
	if (video_meta.length) {
		jQuery('#textwise_metabox_videos').removeClass('closed');
	}
	jQuery('#textwise_video_input').val(textwise_serialize_metablock(video_meta));

}

function textwise_video_toggle(e) {
	var htmlTag = '';
	var vidW = 300;
	var vidH = 225;
	if (jQuery(e.target).is('a')) { return; }
	var videoObj = jQuery(this).data('twdata');
	var videoData = textwise_deserialize_metablock(jQuery('#textwise_video_input').val());
	var rich = (textwise_getEditor() == 'tinymce');
	var i_dom;

	//Get the DOM from either editor
	if ( rich && tinyMCE.activeEditor ) {
		tinyMCE.activeEditor.getDoc().ignoreDOM = true;
		var ed = tinyMCE.activeEditor;
		textwise_getContent();	//Refresh/re-render
		i_dom = jQuery(ed.getBody());
	} else {
		i_dom = jQuery('<div>'+jQuery('#content').val()+'</div>');
	}

	//Find inserted videos in the DOM, put into array
	var editor_videos = [];
	i_dom.find('span.tw_selvid img').each(function(i, obj){
		var tagObj = {};
		try {	//If an image wanders into a span for TW videos, the title attrib won't evaluate as JSON properly
			tagObj = textwise_video_source(obj);
		} catch(err) { }
		if (tagObj.src == videoObj.enclosureUrl) {
			editor_videos.push(obj);
		}
	});

	//If videos found
	if (editor_videos.length) {
	//Remove from content
		jQuery.each(editor_videos, function(i, obj){
			var cObj = textwise_tinymce_special_node(obj);
			jQuery(cObj).remove();
		});
		if ( !rich ) { jQuery('#content').val(jQuery(i_dom).html()); }
		var oldVideoData = videoData;
		videoData = [];
		jQuery.each(oldVideoData, function(i, obj){
			if (videoObj.enclosureUrl != obj.enclosureUrl) {
				videoData.push(obj);
			}
		});
		jQuery(this).removeClass('selected');
	} else {
	//Add to content
		var vidSrc = videoObj.enclosureUrl;
		var vidTitle = videoObj.title;
		//New html tag
		htmlTag = '<span class="tw_selvid"><object width="'+vidW+'" height="'+vidH+'" classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0">'
			+'<param name="src" value="'+textwise_html_esc(vidSrc)+'" />'
			+'<embed width="'+vidW+'" height="'+vidH+'" type="application/x-shockwave-flash" src="'+textwise_html_esc(vidSrc)+'" />'
			+'</object></span>';
		if ( rich && tinyMCE.activeEditor ) {
			tinyMCE.activeEditor.focus(false);
			var el = tinyMCE.activeEditor.selection.getNode();
			var el2 = textwise_tinymce_special_node(el);
			el = !el2 ? el : jQuery(el2).parent();
			if (jQuery(el).is('a')) { el = jQuery(el).parent(); }
			jQuery(el).prepend(htmlTag);
			tinyMCE.activeEditor.execCommand('mceCleanup');
		} else {
			edInsertContent(edCanvas, htmlTag);
		}

		videoData.push(videoObj);
		jQuery(this).addClass('selected');
	}

	if ( rich && tinyMCE.activeEditor ) {
		tinyMCE.activeEditor.getDoc().ignoreDOM = false;
	}

	jQuery('#textwise_video_input').val(textwise_serialize_metablock(videoData));
	textwise_hoverend();

}

function textwise_video_hover(e) {
	var vidObj = jQuery('a.enclosure_link', this);
	var vidTitle = vidObj.attr('title');
	var vidId = vidObj.attr('id');
	var vidUrl = vidObj.attr('href');
	var vidThumbBase = 'http://img.youtube.com/vi/'+vidId+'/';
	var mediaSource = 'YouTube';
	var vidPane = textwise_get_preview();
	vidPane.empty();
	vidPane.append('<div id="textwise_preview_captions"><span class="title"><nobr>'+vidObj.attr('title')+'</nobr></span><span class="mediasource">'+mediaSource+'</span></div><br class="clear" />');
	var previewImage = jQuery('<img id="textwise_preview_image" width="290" />');

	previewImage.attr('src', vidThumbBase+'0.jpg');

	vidPane.append(previewImage);

	vidPane.css("top",(e.pageY - textwise_settings.offTop) + "px")
		.css("left",(e.pageX + textwise_settings.offLeft) + "px")
	textwise_settings.lastHover = setTimeout('jQuery("#textwise_preview").fadeIn("fast")', 500);
}


/** Links and Products **/

function textwise_sync_wiki_list() {
	var divWiki = jQuery('#textwise_related_wiki');
	if (divWiki.length) {
		divWiki.empty();
		var c = 0;
		var linklist = textwise_deserialize_metablock(jQuery('#textwise_wiki_input').val());
		jQuery.each(linklist, function(i, objLink){
			divWiki.append(textwise_html_wiki(objLink, true, (c%2 == 0)));
			c++;
		});
		if (linklist.length) {
			jQuery('#textwise_metabox_links').removeClass('closed');
		}
	}
}
function textwise_sync_rss_list() {
	var divRss = jQuery('#textwise_related_blogs');
	if (divRss.length) {
		divRss.empty();
		var c = 0;
		var linklist = textwise_deserialize_metablock(jQuery('#textwise_rss_input').val());
		for (var i=0; i<linklist.length; i++) {
			var objLink = linklist[i];
			divRss.append(textwise_html_rss(linklist[i], true, (c%2 == 0)));
			c++;
		}
		if (linklist.length) {
			jQuery('#textwise_metabox_links').removeClass('closed');
		}
	}
}


function textwise_sync_product_list() {
	var tabProd = jQuery('#textwise_product_table tr');
	if (tabProd.length) {
		tabProd.empty();
		var linklist = textwise_deserialize_metablock(jQuery('#textwise_product_input').val());
		for (var i=0; i<linklist.length; i++) {
			var objLink = linklist[i];
			tabProd.append(textwise_html_product(linklist[i], true));
		}
		if (linklist.length) {
			jQuery('#textwise_metabox_links').removeClass('closed');
		}
	}
}


function textwise_product_toggle(e) {
	if (!jQuery(e.target).is('a')) {
		var linklist = textwise_deserialize_metablock(jQuery('#textwise_product_input').val());
		var itemObj = jQuery(this).data('twdata');
		var itemLink = itemObj.landingPageUrl;

		if (jQuery(this).hasClass('selected')) {
			var newlist = [];
			jQuery.each(linklist, function(key, obj) {
				if (obj.landingPageUrl != itemLink) {
					newlist.push(obj);
				}
			});
			jQuery('#textwise_product_input').val(textwise_serialize_metablock(newlist));
			jQuery(this).removeClass('selected');
		} else {
			linklist.push(itemObj);
			jQuery('#textwise_product_input').val(textwise_serialize_metablock(linklist));
			jQuery(this).addClass('selected');
		}
	}
}

function textwise_rss_toggle(e) {
	if (!jQuery(e.target).is('a')) {
		var linklist = textwise_deserialize_metablock(jQuery('#textwise_rss_input').val());
		var itemObj = jQuery(this).data('twdata');
		var itemLink = itemObj.landingPageUrl;

		if (jQuery(this).hasClass('selected')) {
			var newlist = [];
			jQuery.each(linklist, function(key, obj) {
				if (obj.landingPageUrl != itemLink) {
					newlist.push(obj);
				}
			});
			jQuery('#textwise_rss_input').val(textwise_serialize_metablock(newlist));
			jQuery(this).removeClass('selected');
		} else {
			linklist.push(itemObj);
			jQuery('#textwise_rss_input').val(textwise_serialize_metablock(linklist));
			jQuery(this).addClass('selected');
		}
	}
}

function textwise_wiki_toggle(e) {
	if (!jQuery(e.target).is('a')) {
		var linklist = textwise_deserialize_metablock(jQuery('#textwise_wiki_input').val());
		var itemObj = jQuery(this).data('twdata');
		var itemLink = itemObj.landingPageUrl;

		if (jQuery(this).hasClass('selected')) {
			var newlist = [];
			jQuery.each(linklist, function(key, obj) {
				if (obj.landingPageUrl != itemLink) {
					newlist.push(obj);
				}
			});
			jQuery('#textwise_wiki_input').val(textwise_serialize_metablock(newlist));
			jQuery(this).removeClass('selected');
		} else {
			linklist.push(itemObj);
			jQuery('#textwise_wiki_input').val(textwise_serialize_metablock(linklist));
			jQuery(this).addClass('selected');
		}
	}
}



/** Misc Helpers **/

function textwise_tinymce_special_node(node) {
	//Find nearest parent node patching selector
	//From jQuery code for use with older versions
	var getClosest = function(selector, cur){
		while (cur && cur.ownerDocument) {
			if (jQuery(cur).is(selector)) {
				return cur;
			}
			cur = cur.parentNode;
		}
		return false;
	};

	var p;

	//If inside of a special container, work with that container instead of the selected node
	// Images
	if (p = getClosest('div.mceTemp', node)) { return p; }
	// YouTube videos
	if (p = getClosest('span.tw_selvid', node)) { return p; }
	//return node;
	return false;
}

function textwise_get_preview() {
	if (!jQuery('#textwise_preview').length) {
		jQuery("body").append('<div id="textwise_preview"></div>');
	}
	return jQuery('#textwise_preview');
}


function textwise_hoverend() {
	jQuery(this).unbind('click', textwise_preview_track);
	if (textwise_settings.lastHover) { clearTimeout(textwise_settings.lastHover); }
	jQuery("#textwise_preview").remove();
}


function textwise_preview_track(e) {
	jQuery("#textwise_preview")
		.css("top",(e.pageY - textwise_settings.offTop) + "px")
		.css("left",(e.pageX + textwise_settings.offLeft) + "px");
}

function textwise_html_esc(str){
	var result;
	try {
	    result = str.replace(/&/g, "&amp;").replace(/</g,"&lt;").replace(/>/g, "&gt;").replace(/'/g, "&#039;").replace(/"/g, "&quot;");
	} catch(err) {
		result = '';
	}
	return result;
}

function textwise_deserialize_metablock(content) {
	var result = [];
	var trimmedContent = jQuery.trim(content);
	if (trimmedContent == '') return result;	//continue
	var records = trimmedContent.split("\n\n");
	jQuery.each(records, function (i, record){
		rows = record.split("\n");
		rowdata = {};
		jQuery.each(rows, function (j, row) {
			cols = row.split("\t");
			if (cols[0] != '') { rowdata[cols[0]] = cols[1]; }
		});

		if (rowdata.title) {
			result.push(rowdata);
		}
	});
	return result;
}

function textwise_serialize_metablock(dataobjs) {
	var result = '';
	jQuery.each(dataobjs, function (id, obj) {
		if (obj == undefined) return true;	//continue
		jQuery.each(obj, function (key, val) {
			result += jQuery.trim(key)+"\t"+jQuery.trim(val)+"\n";
		});
		result += "\n";
	});
	return result;
}

//Get array of landingPageUrls from objects
function textwise_get_links(objs) {
	var cur_links = [];
	jQuery.each(objs, function(i, objLink){
		cur_links.push(objLink.landingPageUrl);
	});
	return cur_links;
}

function textwise_locate_boundaries(html) {
	var bounds = new Array();
	var ci = 0;
	var si = 0;
	var ei = 0;
	do {
		si = html.indexOf('<', ei);
		if(si == -1) {
			bounds.push(ci);
			bounds.push(html.length - 1);
			break;
		}
		bounds.push(ci);
		if(si > ci) {
			bounds.push(si - 1);
		} else {
			bounds.pop();
		}
		ei = html.indexOf('>', si);
		if(ei == -1) {
			bounds.push(html.length - 1);
			break;
		} else if(ei == html.length -1) {
			break;
		} else {
			ci = ei + 1;
		}
	} while(true);

	return bounds;
}


/* HTML fragments */
function textwise_html_video(objVid, selected) {
	var vid = jQuery('<td><div class="tw_thumbcell"><a id="'+objVid.videoId+'" class="enclosure_link" href="'+objVid.enclosureUrl+'" title="'+textwise_html_esc(objVid.title)+'"></a><div class="thumbbox"><div class="thumbimg"><img src="'+objVid.thumbnailUrl+'" alt="'+textwise_html_esc(objVid.title)+'"></a></div><div class="sel">&nbsp;</div></div><span class="caption"><a class="preview_link" target="_blank" href="'+objVid.landingPageUrl+'">Preview Video</a><br /><span class="media_source">YouTube</span></span></div></td>');
	vid.find('.tw_thumbcell').data('twdata', objVid);
	if (selected) { vid.find('.tw_thumbcell').addClass('selected'); }
	return vid;
}

function textwise_html_image(objImg, selected) {
	var img = jQuery('<td><div class="tw_thumbcell"><div class="thumbbox clipped"><div class="thumbimg"><img src="'
		+textwise_html_esc(objImg.thumbnailUrl)+'" alt="'+textwise_html_esc(objImg.title)
		+'"></a></div><div class="sel">&nbsp;</div></div><span class="caption"><a class="preview_link" target="_blank" href="'
		+objImg.landingPageUrl+'">Preview Image</a><br /><span class="media_source">'+textwise_html_esc(objImg.source)
		+'</span></span></div></td>');
	img.find('.tw_thumbcell').data('twdata', objImg);
	if (selected) {img.find('.tw_thumbcell').addClass('selected'); }
	return img;
}

function textwise_html_rss(objLink, selected, altRow) {
	var rss = jQuery('<div class="item"></div>');
	rss.data('twdata', objLink);
	if (altRow)		{ rss.addClass('alt'); }
	if (selected)	{ rss.addClass('selected'); }
	rss.append('<div class="title"><a class="expando">&nbsp;&nbsp;&nbsp;</a> '+textwise_html_esc(objLink.title)
		+'</div><div class="details"><div class="description">'+textwise_html_esc(objLink.description)
		+'</div><div class="sourcelink"><a class="landingpage" href="'+objLink.landingPageUrl
		+'" target="_blank">Read Article</a> on <a target="_blank" class="extlink" href="'
		+textwise_html_esc(objLink.channelLink)+'">'+textwise_html_esc(objLink.channelTitle)+'</a></div></div>');
	return rss;
}

function textwise_html_wiki(objLink, selected, altRow) {
	var wiki = jQuery('<div class="item"></div>');
	wiki.data('twdata', objLink);
	if (altRow)		{ wiki.addClass('alt'); }
	if (selected)	{ wiki.addClass('selected'); }
	wiki.append('<div class="title"><a class="expando">&nbsp;&nbsp;&nbsp;</a> '+textwise_html_esc(objLink.title)
		+'</div><div class="details"><div class="description">'+textwise_html_esc(objLink.description)
		+'</div><div class="sourcelink"><a target="_blank" class="extlink" href="'+textwise_html_esc(objLink.landingPageUrl)
		+'">Read Article</a></div></div>');
	return wiki;
}

function textwise_html_product(objLink, selected) {
	if (objLink.imageUrl == '') {
		objLink.imageUrl = textwise_settings.pluginDir+'/img/product_missing.gif';	//Product Image Missing
	}
	var prod = jQuery('<td><div class="tw_prodcell"><div class="thumbbox"><div class="thumbimg"><img src="'+objLink.imageUrl
		+'" alt="'+textwise_html_esc(objLink.title)+'"></div><div class="sel">&nbsp;</div></div><div class="captiontitle" title="'
		+textwise_html_esc(objLink.title)+'">'+textwise_html_esc(objLink.title)+'</div><div class="captiondesc">'
		+textwise_html_esc(objLink.description)+'</div><div class="captionlink">View Item on <a class="link" target="_blank" href="'
		+textwise_html_esc(objLink.landingPageUrl)+'">Amazon</a></div></div></td>');
	prod.find('.tw_prodcell').data('twdata', objLink);
	if (selected) { prod.find('.tw_prodcell').addClass('selected'); }
	return prod;
}


/** Help bubbles **/
function textwise_help_bubble() {
	var helpStrings = {};
	helpStrings['textwise_tag_help'] = "Concept tags are significant-term tags which are derived from a semantic analysis of your original text.  Move your mouse over one of the suggestions to see its similarity strength to your post.  They can be selected to add to your post's tags.";
	helpStrings['textwise_cat_help'] = "The Semantic Hacker API goes beyond inaccurate, labor-intensive keyword or folksonomy categorization systems. These categories are useful for content categorization and site navigation. Only the best matches are then shown for you to include in your post.";
	helpStrings['textwise_video_help'] = "The text of your post is used as input to a Similarity Search API call where it is semantically matched against videos found in our content indexes. Only the best matches are then shown for you to include in your post.";
	helpStrings['textwise_image_help'] = "The text of your post is used as input to a Similarity Search API call where it is semantically matched against images in our content indexes. Only the best matches are then shown for you to include in your post.";
	helpStrings['textwise_link_help'] = "The text of your post is used as input to a Similarity Search  API call where it is semantically matched against blogs, news, Wikipedia articles and Amazon products found in our content indexes. Only the best matches are then shown for you to include in your post.";
	helpStrings['textwise_contentlink_help'] = "The words or phrases in this section appear in the text of your post. Using the power of Semantic Gist, they have been selected as relevant to the post. Click on the words or phrases below to link that text in your post to the corresponding Wikipedia article on that topic.<br /><br />Wordpress also provides the ability to edit the content links if the suggestions are not exactly what you want for your post.";

	var objBubble = jQuery('#textwise_bubble');
	var objWindow = jQuery(window);
	var targetPos = jQuery(this).offset();
	var bubblePos = {};
	if (targetPos.left + objBubble.width() + 20 < objWindow.width()) {
		bubblePos.left = targetPos.left + 20;
	} else {
		bubblePos.left = targetPos.left - objBubble.width() - 20;
	}
	if (targetPos.top + objBubble.height() < objWindow.height()) {
		bubblePos.top = targetPos.top;
	} else {
		bubblePos.top = targetPos.top - objBubble.height() - 20;
	}

	var bubbleId = jQuery(this).attr('id');
	var bubbleText = helpStrings[bubbleId] ? helpStrings[bubbleId] : bubbleId;
	objBubble.html(bubbleText);

	jQuery('#textwise_bubble').css('top', bubblePos.top).css('left', bubblePos.left).fadeIn();
	return false;	//Stop metabox hide/show propogation
}

/** DOM Change watchers **/
function textwise_nodeChanged(e) {
	var doc = tinyMCE.activeEditor.getDoc();
	if (doc.ignoreDOM) { return; }

	doc.ignoreDOM = true;

	jQuery('#textwise_contentlinks a.selected').each(function(i, objLink){
		var linktext = jQuery(objLink).text();
		if (!jQuery(doc).find('a.tw_contentlink:contains('+linktext+')').length) {
			jQuery(objLink).removeClass('selected');
		}
	});

	//Cleanup unlinked contentlinks
	var unlinked = jQuery(doc).find('span.tw_contentlink');
	if (unlinked.length) {
		unlinked.replaceWith(unlinked.text());
	}

	var edImages = [];
	var apiImages = jQuery('#textwise_image_table .tw_thumbcell');


	//Dragged images
	jQuery('img', doc).each(function(i, edImg){
		apiImages.each(function(j, obj){
			var imageObj = jQuery(obj).data('twdata');
			if (edImg.src == imageObj.thumbnailUrl) {
				if ( !(jQuery(edImg).hasClass('tw_selimg') || jQuery(edImg).parents('.tw_selimg').length) ) {
					htmlTag = textwise_imageInsert(imageObj);
					jQuery(edImg).replaceWith(htmlTag);
					tinyMCE.execCommand('mceRepaint');
					var imageData = textwise_deserialize_metablock(jQuery('#textwise_image_input').val());
					imageData.push(imageObj);
					jQuery('#textwise_image_input').val(textwise_serialize_metablock(imageData));

				}

			}
		});
	});

	jQuery(doc).find('img.tw_selimg').each(function(i, objImg){
		edImages.push(jQuery(objImg).attr('src'));
	});


	jQuery('#textwise_image_table .tw_thumbcell').each(function(i, objImg){
		var imageData = jQuery(objImg).data('twdata');
		var imageSrc = imageData.imageUrl;
		if (jQuery.inArray(imageSrc, edImages) != -1) {
			jQuery(objImg).addClass('selected');
		} else {
			jQuery(objImg).removeClass('selected');
		}
	});

	var edVideos = [];
	jQuery(doc).find('span.tw_selvid img.mceItemFlash').each(function(i, objVid){
		var embd = jQuery(objVid).attr('src');
		if (embd != 'undefined') {
			//embd = eval('({'+jQuery(objVid).attr('title')+'})');
			embd = textwise_video_source(objVid);
			edVideos.push(embd.src);
		}
	});

	jQuery('#textwise_video_table .tw_thumbcell').each(function(i, objVid){
		var videoData = jQuery(objVid).data('twdata');
		var videoSrc = videoData.enclosureUrl;
		if (jQuery.inArray(videoSrc, edVideos) != -1) {
			jQuery(objVid).addClass('selected');
		} else {
			jQuery(objVid).removeClass('selected');
		}

	});
	doc.ignoreDOM = false;
}

function textwise_nodeRemoved(e) {
	var doc = tinyMCE.activeEditor.getDoc();
	if (doc.ignoreDOM) { return; }
	doc.ignoreDOM = true;

	jQuery('#textwise_contentlinks a.selected').each(function(i, objLink){
		var linktext = jQuery(objLink).text();
		if (!jQuery(doc).find('a.tw_contentlink:contains('+linktext+')').length) {
			jQuery(objLink).removeClass('selected');
		}
	});

	//Cleanup unlinked contentlinks
	var unlinked = jQuery(doc).find('span.tw_contentlink');
	if (unlinked.length) {
		unlinked.replaceWith(unlinked.text());
	}

	var edImages = [];

	jQuery(doc).find('img.tw_selimg').each(function(i, objImg){
		edImages.push(jQuery(objImg).attr('src'));
	});


	jQuery('#textwise_image_table .tw_thumbcell').each(function(i, objImg){
		var imageData = jQuery(objImg).data('twdata');
		var imageSrc = imageData.imageUrl;
		if (jQuery.inArray(imageSrc, edImages) != -1) {
			jQuery(objImg).addClass('selected');
		} else {
			jQuery(objImg).removeClass('selected');
		}
	});

	var edVideos = [];
	jQuery(doc).find('span.tw_selvid img.mceItemFlash').each(function(i, objVid){
		var embd = jQuery(objVid).attr('src');;
		if (embd != 'undefined') {
			embd = textwise_video_source(objVid);
			edVideos.push(embd.src);
		}
	});

	jQuery('#textwise_video_table .tw_thumbcell').each(function(i, objVid){
		var videoData = jQuery(objVid).data('twdata');
		var videoSrc = videoData.enclosureUrl;
		if (jQuery.inArray(videoSrc, edVideos) != -1) {
			jQuery(objVid).addClass('selected');
		} else {
			jQuery(objVid).removeClass('selected');
		}

	});

	doc.ignoreDOM = false;

}
