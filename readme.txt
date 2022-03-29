=== WPZOOM Social Feed Widget ===
Contributors: WPZOOM, nvartolomei, ciorici
Donate link: https://www.wpzoom.com/
Tags: instagram, instagram feed, instagram gallery, instagram photos, instagram widget, instagram stories, widget, timeline, social network, latest images, feed, story, stories, insta
Requires at least: 5.5
Tested up to: 5.9
Requires PHP: 7.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Formerly known as "Instagram Widget by WPZOOM". Simple and lightweight widget for WordPress to display your Instagram feed.

== Description ==

Formerly known as *Instagram Widget by WPZOOM*, **WPZOOM Social Feed Widget** is a simple and lightweight widget for WordPress to display your **Instagram feed**.


**[View Demo](https://demo.wpzoom.com/instagram-widget/)**


> Did you find this plugin helpful? Please consider [leaving a 5-star review](https://wordpress.org/support/view/plugin-reviews/instagram-widget-by-wpzoom).


**WPZOOM Social Feed Widget** gives you a WordPress Widget that you can place anywhere you want to display your Instagram Feed. Easy setup and configuration!


= Features =

* **Lightbox** 🆕
* Add your custom avatar and bio
* Supports Lazy Loading
* Lighweight plugin
* Works with the new WordPress 5.8 block-based widgets screen


= Where I can view a Demo? =

You can view the widget live in all our themes at [WPZOOM](http://www.wpzoom.com/themes/).


= Get Involved =

Looking to contribute code to this plugin? Go ahead and [fork the repository over at GitHub](https://github.com/wpzoom/instagram-widget/).

== Installation ==

Simply search for the plugin via the **Plugins -> Add New** dialog and click install, or download and extract the plugin, and copy the plugin folder into your wp-content/plugins directory and activate.

After installation go to the **Settings > Instagram Widget** page and connect the plugin with your Instagram account.

Once connected, go to the **Widgets** page and add the widget **Instagram Widget by WPZOOM** to a widget area like Sidebar.



== Frequently Asked Questions ==

= I just installed plugin and widget shows nothing =

Make sure to connect your Instagram account with the plugin. You can do that in the **Settings > Instagram Widget** page from the Dashboard.


== Screenshots ==

1. Examples of how the widget can be used
2. More examples
3. Perfect for Sidebar or Footer column
4. Customized button using CSS
5. Settings


== Changelog ==

= 1.9.5 =
* Fixed a bug with the lightbox not working in specific themes, including Divi

= 1.9.4 =
* Minor improvements

= 1.9.3 =
* Bug fix with the lightbox in Safari

= 1.9.2 =
* Minor bug fixes and improvements with the lightbox

= 1.9.1 =
* Added support for album posts in the lightbox (showing multiple photos per slide)
* Added swipe support in the lightbox
* Multiple bug fixes

= 1.9.0 =
* Added a new Lightbox Feature

= 1.8.3 =
* Fixed error 400 Bad Request

= 1.8.2 =
* Fixed compatibility with upcoming WordPress 5.8
* Fixed a bug with Beaver Builder

= 1.8.1 =
* Fixed a minor bug for PHP 8.0

= 1.8.0 =
* NEW: Automatically refresh Instagram access token before it expires
* NOTE: There are limitations to refresh access token for Instagram private accounts! You will need to reauthorize manually after access token expires
* Improved admin notices

= 1.7.7 =
* Load plugin text domain
* Removed old .pot file from /languages

= 1.7.6 =
* Change textdomain to match with plugin slug

= 1.7.5 =
* Fixed strings text domain
* Fixed dismiss url for admin notice
* Added Text Domain and Domain Path to plugin description

= 1.7.4 =
* Added the "nofollow" parameter to all links from the widget.
* Fixing issues with thumbnails that were deleted by third-party plugins.

= 1.7.3 =
* Multiple improvements and bug fixes

= 1.7.2 =
* Fixing issues with images not loading on specific websites

= 1.7.1 =
* Minor bug fixes

= 1.7.0 =
* Added support for the new Facebook oEmbed endpoints due to deprecation of the old Instagram oEmbed on October 24, 2020.

= 1.6.4 =
* Minor bug fixes for PHP 7.4

= 1.6.3 =
* Minor bug fix when switching from Public Feed to the new API method.

= 1.6.2 =
* Minor bug fix with cached plugin assets when updating from an older version

= 1.6.1 =
* Minor bug fixes

= 1.6.0 =
* Added support for the new Instagram Basic Display API.
* IMPORTANT: On June 29, Instagram will stop supporting its old API which will disrupt feeds created using the old API. If your Instagram account is connected in the plugin settings, you will need to reconnect it again using the new API.

= 1.5.0 =
* Refactor of the Public Feed method.
* Added 2 new options in the settings page to have more control on the connection with the Instagram API on specific hosting

= 1.4.4 =
* New option to hide video thumbnails. Sometimes video thubmanils may show as blank squares, so the new option will help to fix this problem.

= 1.4.3 =
* Minor bug fix

= 1.4.2 =
* New feature: "Lazy Load Images". You can enable it the widget settings.

= 1.4.1 =
* New option in the settings page to control the refresh rate of your Instagram feed.
* A few more fixes and improvements to the Instagram API integration.

= 1.4.0 =
* Added an alternative option to display the public feed limited to 12 photos of your account or any other Instagram user.

= 1.3.1 =
* Minor bug fix with a caching issue

= 1.3.0 =
* Added new option: Display User Details
* Added new option: Display User Bio

= 1.2.11 =
* Fixed a conflict with some CSS classes

= 1.2.10 =
* Minor fixes to new overlay feature

= 1.2.9 =
* New option: show number of likes and comments on image hover
* Minor bug fixes

= 1.2.8 =
* Minor bug fix

= 1.2.7 =
* Minor bug fix

= 1.2.6 =
* Fixing a bug to prevent exceeding of the Instagram API rate limit (200 request per hour as of March 30, 2018).

= 1.2.5 =
* Minor bug fix

= 1.2.4 =
* Minor bug fix with missing images

= 1.2.3 =
* Minor bug fix

= 1.2.2 =
* Bug fix with incorrect thumbnail size on non-square images

= 1.2.1 =
* Minor modification to show Alt text when hovering images

= 1.2.0 =
* Updated "View on Instagram" button
* Support for WordPress 4.6

= 1.1.0 =
* IMPORTANT: Due to the recent Instagram API changes, in order for the Instagram Feed plugin to continue working after June 1st you must obtain a new Access Token by using the Instagram button on the plugin's Settings page. This is true even if you recently already obtained a new token. Apologies for any inconvenience.
* Compatible with Instagram's new API changes effective June 1st

= 1.0.4 =
* Look for exact matching username when searching for user id.

= 1.0.3 =
* Make Instagram image links open a new tab.

= 1.0.2 =
* Load higher quality images when needed.
* Unique cache key for each widget, previously all widgets on the page used same options on subsequent page loads.

= 1.0.1 =
* Work directly with image list and do not rely on widget ids and classes handled by theme. This broke widget when theme sidebars weren't properly registered.
* Use requestAnimationFrame for updating image sizes, improved performance and also fixes safari bug.
* Fix error caused by boolean to string conversion (get|set)_transient.

= 1.0 =
* Initial release.


== Upgrade Notice ==

= 1.6.0 =
⚠️ IMPORTANT: On June 29, Instagram will stop supporting its old API which will disrupt feeds created using the old API. If your Instagram account is connected in the plugin settings, you will need to reconnect it again using the new API.
