=== JSON Data Shortcode ===
Contributors: ddean
Tags: json, data, shortcode, seo-friendly
Requires at least: 3.3
Tested up to: 3.5
Stable tag: 1.3

Load data via JSON and display it in your posts and pages - even to spiders or visitors with JavaScript disabled

== Description ==

This shortcode lets you pull data into your pages via JSON. Supports unlimited levels of nesting!
Built-in caching of results keeps your data providers happy, but can be tailored to your needs with a configurable lifetime.

You can use it one of two ways (but not necessarily on the same page - see <a href="http://codex.wordpress.org/Shortcode_API#Unclosed_Shortcodes">this article</a>!)

`[json src="http://example.com/my_data_src?format=json" key="Data.mykey"]`
 --> outputs contents of `mykey` in the `Data` object

`[json src="http://example.com/my_data_src?format=json"]I want my value to appear right here {Data.mysubdata.otherKey} in the middle of my content.[/json]`
 --> replaces the text in `{}` with contents of `otherKey` in the `mysubdata` object in the `Data` object

*Note:* this plugin allows you to bring content from remote sites into your posts. Please exercise caution, especially if you allow posting by untrusted users.

== Installation ==

Download and install using the built in WordPress plugin installer.
Activate in the "Plugins" admin panel using the "Activate" link.

== Frequently Asked Questions ==

= URLs are too long! Can I shorten my JSON shortcodes? =

Use the `name` attribute to define a friendly name for your data source!  The scope of this name is only the post / page you name it.
Also note that using the `name` attribute more or less restricts you to the self-closing format, because of <a href="http://codex.wordpress.org/Shortcode_API#Unclosed_Shortcodes">shortcode parser limitations</a>.

`[json src="http://example.com/my_data_src?format=json" name="ExampleData"]`

Later in the same page...

`[json name="ExampleData" key="Data.mykey"]`
 --> outputs contents of `mykey` in the `Data` object

= How can I control how frequently my data is refreshed? =

Use the lifetime parameter! This parameter is in seconds, is per source, and should be set the same for all references to that source on a post / page for best results.

`[json src="http://example.com/my_data_src?format=json" lifetime="300"]I want my value to appear right here {myData} in the middle of my content.[/json]`
 --> Will expire the cached data after 5 minutes.
 
 = Does this work with caching? =
 
 Yes! Any output caching system will cache your post with JSON data included. 
 However, your data will only be checked for expiration when your site builds the post, so time your cache expiration accordingly.

== Changelog ==

= 1.3 =
* Added: JSON Shortcode Diagnostic page to test shortcode syntax

= 1.2 =
* Changed: decode HTML entities in src URLs (to ensure support for multiple query parameters) - thanks, bakedbeing

= 1.1 =
* Fixed: fatal error when `wp_remote_get` returned a WP_Error object
* Changed: use native json_encode/json_decode when available for better performance

= 1.0 =
* Initial release

== Upgrade Notice ==

= 1.3 =
Added a diagnostic page to let users validate JSON requests without creating a post

= 1.2 =
Improved compatibility with copy / pasted URLs