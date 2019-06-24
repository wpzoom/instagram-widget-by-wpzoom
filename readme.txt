=== WPZOOM Widget for Instagram ===
Contributors: WPZOOM, nvartolomei, ciorici
Donate link: https://www.wpzoom.com/
Tags: instagram, widget, timeline, social network, latest images, feed, instagram feed, story, stories
Requires at least: 4.3
Tested up to: 5.2
Requires PHP: 5.6
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Fully customisable and responsive Instagram timeline widget for WordPress.

== Description ==

**[View Demo](http://demo.wpzoom.com/instagram-widget/)**


> Did you find this plugin helpful? Please consider [leaving a 5-star review](https://wordpress.org/support/view/plugin-reviews/instagram-widget-by-wpzoom).


**WPZOOM Widget for Instagram** gives you a WordPress Widget that you can place anywhere you want and be able to fully customize it's design using CSS.


= What's New in version 1.4? =

You can now display a public feed limited to 12 photos of any Instagram account you want. You are no longer limited to display just your own feed.


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
