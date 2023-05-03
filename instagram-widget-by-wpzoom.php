<?php
/**
 * Plugin Name: WPZOOM Instagram Widget & Block
 * Plugin URI: https://www.wpzoom.com/plugins/instagram-widget/
 * Description: Instagram Widget is a customizable and responsive plugin, made to help you gain even more followers by showcasing your Instagram feed on your WordPress website.
 * Version: 2.1.4
 * Author: WPZOOM
 * Author URI: https://www.wpzoom.com/
 * Text Domain: instagram-widget-by-wpzoom
 * Domain Path: /languages
 * License: GPLv2 or later
 */

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WPZOOM_INSTAGRAM_VERSION' ) ) {
	define( 'WPZOOM_INSTAGRAM_VERSION', get_file_data( __FILE__, [ 'Version' ] )[0] ); // phpcs:ignore
}

require_once plugin_dir_path( __FILE__ ) . 'class-wpzoom-instagram-image-uploader.php';
require_once plugin_dir_path( __FILE__ ) . 'class-wpzoom-instagram-widget-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'class-wpzoom-instagram-general-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'class-wpzoom-instagram-widget-api.php';
require_once plugin_dir_path( __FILE__ ) . 'class-wpzoom-instagram-widget-display.php';
require_once plugin_dir_path( __FILE__ ) . 'class-wpzoom-instagram-widget.php';
require_once plugin_dir_path( __FILE__ ) . 'class-wpzoom-instagram-block.php';
require_once plugin_dir_path( __FILE__ ) . 'class-wpzoom-instagram-assets.php';

require_once plugin_dir_path( __FILE__ ) . 'class-wpzoom-instagram-widget-after-setup.php';

add_action( 'widgets_init', 'zoom_instagram_widget_register' );
function zoom_instagram_widget_register() {
	register_widget( 'Wpzoom_Instagram_Widget' );
}

/* Display a notice that can be dismissed */

// add_action( 'admin_notices', 'wpzoom_instagram_admin_notice' );

function wpzoom_instagram_admin_notice() {
	global $current_user, $pagenow;

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$options = WPZOOM_Instagram_Widget_Settings::get_instance()->get_settings();

	if ( ! isset( $options['basic-access-token'] ) || empty( $options['basic-access-token'] ) ) {
		$hide_notices_url = wpzoom_instagram_get_notice_dismiss_url();

		$notice_message  = '<strong>' . __( 'Please configure Instagram Widget', 'instagram-widget-by-wpzoom' ) . '</strong><br/>';
		$notice_message .= sprintf( __( 'If you have just installed or updated this plugin, please go to the %1$s and %2$s it with your Instagram account.', 'instagram-widget-by-wpzoom' ), '<a href="edit.php?post_type=wpz-insta_user">' . __( 'Settings page', 'instagram-widget-by-wpzoom' ) . '</a>', '<strong>' . __( 'connect', 'instagram-widget-by-wpzoom' ) . '</strong>' ) . '&nbsp;';
		$notice_message .= __( 'You have to generate Instagram Access Token to allow widget to display your media.', 'instagram-widget-by-wpzoom' );
		$notice_message .= '<a style="text-decoration: none" class="notice-dismiss" href="' . $hide_notices_url . '"></a>';

		$options['admin-notice-message'] = $notice_message;

		update_option( WPZOOM_Instagram_Widget_Settings::get_instance()->get_option_name(), $options );
	}

	/* Check that the user hasn't already clicked to ignore the message */
	$user_id = $current_user->ID;
	if ( ! get_user_meta( $user_id, 'wpzoom_instagram_admin_notice', true ) ) {
		if ( isset( $options['admin-notice-message'] ) && ! empty( $options['admin-notice-message'] ) ) {
			echo '<div class="notice-warning notice" style="position:relative"><p>';
			echo wp_kses_post( $options['admin-notice-message'] );
			echo '</p></div>';
		}
	}

	if ( 'options-general.php' === $pagenow && ( isset( $_GET['page'] ) && 'wpzoom-instagram-widget' === $_GET['page'] ) ) {
		if ( isset( $options['refresh-access-token'] ) && ! empty( $options['refresh-access-token'] ) ) {
			// Inform user in settings page when Access Token was refreshed.
			add_settings_error(
				'wpzoom-instagram-refresh-access-token',
				esc_attr( 'wpzoom-instagram-widget-refresh-access-token' ),
				$options['refresh-access-token'],
				'info'
			);

			$options['refresh-access-token'] = '';

			update_option( WPZOOM_Instagram_Widget_Settings::get_instance()->get_option_name(), $options );
		}
	}
}

// add_action( 'admin_init', 'wpzoom_instagram_ignore_admin_notice' );

function wpzoom_instagram_ignore_admin_notice() {
	global $current_user;
	$user_id = $current_user->ID;
	/* If user clicks to ignore the notice, add that to their user meta */
	if ( isset( $_GET['wpzoom_instagram_ignore_admin_notice'] ) && '0' == $_GET['wpzoom_instagram_ignore_admin_notice'] ) {
		add_user_meta( $user_id, 'wpzoom_instagram_admin_notice', 'true', true );
	}
}

function wpzoom_instagram_get_notice_dismiss_url() {
	/**
	 * Fixed dismiss url
	 *
	 * @since 1.7.5
	 */
	$hide_notices_url = html_entity_decode( // to convert &amp;s to normal &, otherwise produces invalid link.
		add_query_arg(
			array(
				'wpzoom_instagram_ignore_admin_notice' => '0',
			),
			wpzoom_instagram_get_current_admin_url() ? wpzoom_instagram_get_current_admin_url() : admin_url( 'edit.php?post_type=wpz-insta_user' )
		)
	);

	return $hide_notices_url;
}

function wpzoom_instagram_get_default_settings() {
	return array(
		'access-token'             => '',
		'basic-access-token'       => '',
		'request-type'             => 'with-basic-access-token',
		'username'                 => '',
		'transient-lifetime-value' => 1,
		'transient-lifetime-type'  => 'days',
		'is-forced-timeout'        => '',
		'request-timeout-value'    => 15,
		'user-info-avatar'         => '',
		'user-info-fullname'       => '',
		'user-info-biography'      => '',
		'load-css-js'              => '',
	);
}

add_action(
	'init',
	function () {
		$option_name = 'wpzoom-instagram-transition-between-4_7-4_8-versions';
		if ( empty( get_option( $option_name ) ) ) {
			update_option( $option_name, true );
			delete_transient( 'zoom_instagram_is_configured' );
		}

		Wpzoom_Instagram_Widget_Display::getInstance()->init();
	}
);

/**
 * Get current admin page URL.
 *
 * Returns an empty string if it cannot generate a URL.
 *
 * @internal
 * @since 1.7.5
 * @return string
 */
function wpzoom_instagram_get_current_admin_url() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	$uri = preg_replace( '|^.*/wp-admin/|i', '', $uri );

	if ( ! $uri ) {
		return '';
	}

	return remove_query_arg( array( '_wpnonce', 'wpzoom_instagram_ignore_admin_notice' ), admin_url( $uri ) );
}

/**
 * Load textdomain
 *
 * @since 1.7.7
 */
function wpzoom_instagram_load_plugin_textdomain() {
	load_plugin_textdomain( 'instagram-widget-by-wpzoom', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'wpzoom_instagram_load_plugin_textdomain' );

register_deactivation_hook( __FILE__, 'wpzoom_instagram_plugin_deactivation' );

function wpzoom_instagram_plugin_deactivation() {
	wp_clear_scheduled_hook( 'wpzoom_instagram_widget_cron_hook' );
}
