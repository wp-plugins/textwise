=== TextWise Similarity Search ===
Contributors: textwise
Tags: semantic, admin, Post, posts, tags, tagging, links, photo, photos, images, video, youtube, amazon, articles
Requires at least: 3.1
Tested up to: 3.4.2
Stable tag: 1.3.3

Use the power of TextWise Similarity Search technology to find related images, videos, and other content to your blog post as you edit.

== Description ==

Create better posts with our Similarity Search plugin for Wordpress. The plugin automatically suggests related content as you write.

* Generate related categories and concept tags
* Enhance your post with images and videos from Wikipedia, Flickr, YouTube, and more
* Add related links to Wikipedia and thousands of news and blog websites
* Link to related products available from Amazon using your Affiliate ID

= Features =

* **Content link suggestions** link terms from your post to Wikipedia
* **Tag suggestions** describe your post using your most important words and phrases
* **Category suggestions** link your posts by subject
* **Image suggestions** illustrate your post with photos and graphics
* **Video suggestions** add multimedia content from YouTube
* **Related Blogs &amp; News suggestions** link to the best articles on your topic
* **Related Wikipedia Article suggestions** provide further research about your subject
* **Related Amazon suggestions** show readers relevant products available for purchase

= Configuration &amp; Customization =

The plugin provides a number of customization options to fit your needs.

* Control which types of content you want suggested and how often they are updated
* Customize the appearance of our suggestions to match your theme
* Use your own Amazon Affiliate ID to profit from your product suggestions

Once the plugin is installed and activated, look for the TextWise section under Settings in the Wordpress administrative section to get started.

= Contributors =
* Jeff Brand, Delta Factory

== Installation ==

1. Download the plugin and extract the archive.
1. Upload the `textwise` folder and its contents to `/wp-content/plugins/` of your blog.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Register for an API Token at [TextWise.com](http://textwise.com/api)
1. Check out the `Settings` page in WordPress for plugin options.

= Server Requirements =
* PHP 4 or 5
* PHP configuration should have `allow_url_fopen = On` or cURL support.


== Frequently Asked Questions ==

= What is Similarity Search? =

Similarity Search analyzes a body of text in order to find the concepts within it. The responses we provide from our database are the results that closely match those same concepts.

At the core of this process is Semantic Gist&reg;, a new way of representing and analyzing semantic information in text. Semantic Gist provides a representation of the multiple concepts and topics contained in a body of text, whether a few words or an entire document.

Learn more about our Similarity Search technologies at [TextWise.com](http://www.textwise.com/)

= How does the plugin work? =

By using the freely available SemanticHacker API to access our Semantic Search technology, the content of a blog post is analyzed and matched with related content. Those suggestions are displayed as you edit your post.

Learn more about our SemanticHacker API at [TextWise.com](http://www.textwise.com/api)

= How can I use images and video into my post? =

Click-on or drag-and-drop the image thumbnail to add it to your post. Click a video suggestion to insert it. Remove the image or video from the post by clicking the selection again.

Image settings and formatting can be changed by using the image controls built in to WordPress. Select the image and then click the ***Edit Image*** icon.

Video settings can be changed through the Wordpress embedded media settings. Select the placeholder for the video and then click the ***Insert / edit embedded media*** button on the second row of buttons in the editor. *If the second row is not visible, show the "Kitchen Sink" by clicking the last button on the first row of the editor.*

= How can I move an image or video once it is in my post? =

To move the image and its caption, be sure to highlight both the image and caption with your mouse. Then cut &amp; paste.

= How can I include Relevant Content Suggestions in my post? =

Click to select related content. Your highlighted sections will be shown in the published post.

= Will changes made with the plugin remain if I decide to uninstall it? =

Videos, images, and content links added to the body of your posts will remain unless you remove them. Selected Tags and Categories will remain as well.  Related news, articles, and product listings will no longer be displayed.

= Can I hide some suggestions if I don't want to use them? =

You may change these options in the Settings section. Please note that videos, images, content links in the body of your post as well as tags and categories that were selected on existing posts will remain. When you hide related news, articles, or product suggestions, those areas will be hidden on all posts across the blog.

= How will the results appear on my customized theme? =

Elements added to the blog by the TextWise plugin have been designed to conform to your selected theme as seamlessly as possible. To select the logo that best fits with your theme, please see the Settings screen for variations.

To further customize the stylesheet, you may modify the `textwise_post.css` file in the plugin directory, `<blog directory>/wp-content/plugins/textwise/`.

= Can the plugin be used along with other plugins? =

While the plugin has been tested with different themes and plugins, we cannot guarantee that conflicts won't occur. We [encourage your feedback](http://www.textwise.com/forums/bugs) to help us improve the plugin.

== Changelog ==

= 1.3.3 - December 2012 =
* Remove use of API's Signature call
* Fixed checking of empty/new posts based on TinyMCE changes
* Only load UI for standard "post" post types.
* Removal of some conditional code for WP 3.0 and earlier.
* Better checking for unset variables for use in debug mode

= 1.2.3 - July 2012 =
* Update content link text selections

= 1.2.2 - April 2012 =
* Update TextWise branding
* Use capability in admin menu definition

= 1.2.1 - August 2011 =
* Improved back-end look and feel to work with WP 3.2 redesign
* Fixed embed flash support again for WP 3.2.

= 1.1.7 - June 2011 =
* Remove underscores from Category suggestions
* Improved UI CSS for Internet Explorer, Chrome, and Safari
* Better support of HTML editor
* Fixed embed flash support for YouTube removed in WP 3.1

= 1.1.6 - February 2011 =
* Removed all short tags
* Updated registration URL

= 1.1.5 - July 2010 =
* Updated screenshots
* Added cURL fallback support for API requests
* Better error checking for invalid tokens

= 1.1.3 - July 2010 =
* Add warning/better handling for servers with `allow_url_fopen = Off`

= 1.1.1 - July 2010 =
* Support for WordPress 3.0 - Single and Multi-Site
* No longer testing with verions earlier than WordPress 2.8.6. A warning interface will be displayed to those users of the plugin
* Added warning that disabling a feature won't remove certain previously posted content
* Improved error message for connection issues to API server

= 1.0.4 - March 2010 =
* Support for WordPress 2.9.2 post edit interface

== Upgrade Notice ==

= 1.3.3 =
* Remove the Signature API call and other improvements.

= 1.2.3 =
* Update content link text selections

= 1.2.2 =
Updated TextWise branding. Updated admin menu call to use capability instead of level.

= 1.2.1 =
Improved appearance with 3.2 back-end, video embed tag bug fixes

= 1.1.7 =
Fixed video support in WP 3.1, UI enhancements and bug fixes

= 1.1.6 =
Removed short tags per plugin coding compatibility standards

= 1.1.5 =
Added cURL fallback support for API requests, better error checking

= 1.1.3 =
Better handling for restricted PHP configurations

= 1.1.1 =
WordPress 3.0 compatibility release

= 1.0.4 =
Fixes Tag and Category suggestions under Wordpress 2.9


== Screenshots ==

1. An example of the TextWise plugin publishing interface
1. Image and Video Suggestions
1. Relevant Content Suggestions
1. Tag and Category Suggestions
