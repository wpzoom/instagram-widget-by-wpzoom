<?php

/**
 * Plugin Name: Instagram Widget by WPZOOM
 * Plugin URI: http://www.wpzoom.com/plugins/instagram-widget/
 * Description: Fully customisable and responsive Instagram timeline widget for WordPress
 * Author: WPZOOM
 * Author URI: http://www.wpzoom.com/
 * Version: 1.0.4
 * License: GPLv2 or later
 */

require_once( plugin_dir_path( __FILE__ ) . 'class-wpzoom-instagram-widget-api.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-wpzoom-instagram-widget-settings.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-wpzoom-instagram-widget.php' );

add_action( 'widgets_init', 'zoom_instagram_widget_register' );
function zoom_instagram_widget_register() {
	register_widget( 'Wpzoom_Instagram_Widget' );
}
