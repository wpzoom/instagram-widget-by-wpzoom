<?php

/**
 * Plugin Name: WPZOOM Instagram Widget
 * Plugin URI: https://www.wpzoom.com/plugins/instagram-widget/
 * Description: Simple and responsive widget for WordPress to display your Instagram feed
 * Author: WPZOOM
 * Author URI: https://www.wpzoom.com/
 * Version: 1.6.3
 * License: GPLv2 or later
 */

require_once( plugin_dir_path( __FILE__ ) . 'class-wpzoom-instagram-widget-api.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-wpzoom-instagram-widget-settings.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-wpzoom-instagram-widget.php' );

add_action( 'widgets_init', 'zoom_instagram_widget_register' );
function zoom_instagram_widget_register() {
	register_widget( 'Wpzoom_Instagram_Widget' );
}

/* Display a notice that can be dismissed */

add_action('admin_notices', 'wpzoom_instagram_admin_notice');

function wpzoom_instagram_admin_notice() {
	global $current_user ;
	$user_id = $current_user->ID;
	/* Check that the user hasn't already clicked to ignore the message */
	if ( ! get_user_meta($user_id, 'wpzoom_instagram_admin_notice') ) {
		echo '<div class="error notice" style="position:relative"><p>';
		printf(__('<strong>Please configure Instagram Widget</strong><br /><br/> If you have just installed or updated this plugin, please go to the <a href="options-general.php?page=wpzoom-instagram-widget">Settings page</a> and <strong>connect</strong> it with your Instagram account.<br/> You can ignore this message if you have already configured it. <a style="text-decoration: none" class="notice-dismiss" href="%1$s"></a>'), '?wpzoom_instagram_ignore_admin_notice=0');
		echo "</p></div>";
	}
}

add_action('admin_init', 'wpzoom_instagram_ignore_admin_notice');

function wpzoom_instagram_ignore_admin_notice() {
	global $current_user;
	$user_id = $current_user->ID;
	/* If user clicks to ignore the notice, add that to their user meta */
	if ( isset($_GET['wpzoom_instagram_ignore_admin_notice']) && '0' == $_GET['wpzoom_instagram_ignore_admin_notice'] ) {
		add_user_meta($user_id, 'wpzoom_instagram_admin_notice', 'true', true);
	}
}

function wpzoom_instagram_legacy_api_admin_notice() {

	global $current_user ;
	$user_id = $current_user->ID;

	$options = get_option( 'wpzoom-instagram-widget-settings', wpzoom_instagram_get_default_settings() );

	$request_type = !empty($options['request-type']) ? $options['request-type'] : '';

	/* Check that the user hasn't already clicked to ignore the message */
	if ( ! get_user_meta($user_id, 'wpzoom_instagram_legacy_api_admin_notice') && $request_type === 'with-access-token') {
		echo '<div class="error notice" style="position:relative"><p>';
		printf(__('You’re using an <strong>outdated method</strong> to display your <strong>Instagram feed</strong>. This method will be <a target="_blank" href="https://www.instagram.com/developer/">deprecated</a> on <strong>June 29, 2020</strong>, and we recommend go to the <a href="options-general.php?page=wpzoom-instagram-widget">Settings page</a> and <strong>switch</strong> to the new method.<a style="text-decoration: none" class="notice-dismiss" href="%1$s"></a>'), '?wpzoom_instagram_legacy_api_admin_notice=0');
		echo "</p></div>";
	}
}

function wpzoom_instagram_get_default_settings() {
	return [
		'access-token'             => '',
		'basic-access-token'       => '',
		'request-type'             => 'with-basic-access-token',
		'username'                 => '',
		'transient-lifetime-value' => 1,
		'transient-lifetime-type'  => 'days',
		'is-embed-stream'          => '',
		'is-forced-timeout'        => '',
		'request-timeout-value'    => 15
	];
}

function wpzoom_instagram_ignore_legacy_api_admin_notice() {
	global $current_user;
	$user_id = $current_user->ID;
	/* If user clicks to ignore the notice, add that to their user meta */

	if ( isset($_GET['wpzoom_instagram_legacy_api_admin_notice']) && '0' == $_GET['wpzoom_instagram_legacy_api_admin_notice'] ) {
		add_user_meta($user_id, 'wpzoom_instagram_legacy_api_admin_notice', 'true', true);
	}
}


add_action('admin_notices', 'wpzoom_instagram_legacy_api_admin_notice');
add_action('admin_init', 'wpzoom_instagram_ignore_legacy_api_admin_notice');
