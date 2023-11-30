<?php
/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPZOOM_Instagram_General_Settings {
	/**
	 * @var WPZOOM_Instagram_General_Settings The reference to *Singleton* instance of this class
	 *
	 * @since 1.8.4
	 */
	private static $instance;

	/**
	 * Main WPZOOM_Instagram_General_Settings Instance.
	 *
	 * Insures that only one instance of WPZOOM_Instagram_General_Settings exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since 1.0.0
	 * @static
	 * @return object|WPZOOM_Instagram_General_Settings The one true WPZOOM_Instagram_General_Settings
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new WPZOOM_Instagram_General_Settings();
		}
		return self::$instance;
	}

	/**
	 * Plugin constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		add_action( 'admin_init', array( $this, 'option_panel_init' ) );

		add_action( 'wp_ajax_wpzoom_instagram_clear_data', array( $this, 'wpzoom_instagram_clear_data' ) );
	}
	

	/**
	 * Add settings panel to select the size for the feature image
	 *
	 * @since 1.0.0
	 */
	public static function settings_page() {

		if ( ! current_user_can( 'manage_options' ) ){
			wp_die( __( 'You do not have enough permission to view this page', 'instagram-widget-by-wpzoom' ) );
		} 
		?>
		<div class="wrap">
			<?php 

				$widget_settings = new WPZOOM_Instagram_Widget_Settings;
				$widget_settings->page_header();
				
				echo '<div class="wpz-insta-wrap with-bg wpz-insta_settings-general">';
				echo '<div class="inner-wrap fit-max-content">';

					printf( '<form method="post" action="options.php">' );
					settings_fields( 'wpzoom_instagram_general_settings_group' );
					do_settings_sections( 'wpzoom-instagram-general-settings' );
					submit_button();
					printf( '</form>' ); 

				echo '</div>';
				echo '</div>';
		
		?>
		</div>
		<?php
	}

	/**
	 * Init options fields and sections
	 *
	 * @since 1.0.0
	 */
	public function option_panel_init() {

		register_setting(
				'wpzoom_instagram_general_settings_group',
				'wpzoom-instagram-general-settings',
				array( $this, 'sanitize_field' )
		);
		add_settings_section(
			'wpzoom_instagram_general_settings_section',
			'',
			array( $this, 'section_info' ),
			'wpzoom-instagram-general-settings'
		);
		add_settings_field(
				'wpzoom_instagram_general_settings_load_css_js',
				esc_html__( 'Load CSS and JS on all pages', 'instagram-widget-by-wpzoom'), 
				array( $this, 'settings_field_load_css_js' ),
				'wpzoom-instagram-general-settings',
				'wpzoom_instagram_general_settings_section'
		);
		add_settings_field(
				'wpzoom_instagram_general_settings_enable_unsafe_requests',
				esc_html__( 'Enable Insecure API Requests', 'instagram-widget-by-wpzoom'), 
				array( $this, 'settings_field_enable_unsafe_requests' ),
				'wpzoom-instagram-general-settings',
				'wpzoom_instagram_general_settings_section'
		);
		add_settings_field(
			'wpzoom_instagram_general_settings_field_clear_data',
			esc_html__( 'Delete All Images', 'instagram-widget-by-wpzoom'),
			array( $this, 'settings_field_clear_data' ),
			'wpzoom-instagram-general-settings',
			'wpzoom_instagram_general_settings_section'
		);
		add_settings_section(
			'wpzoom_instagram_email_notification_section',
			'',
			array( $this, 'section_email_notification' ),
			'wpzoom-instagram-general-settings'
		);
		add_settings_field(
			'wpzoom_instagram_general_settings_enable_email_notification',
			esc_html__( 'Notify if Access Token is about to expire', 'instagram-widget-by-wpzoom'),
			array( $this, 'settings_field_enable_email_notification' ),
			'wpzoom-instagram-general-settings',
			'wpzoom_instagram_email_notification_section'
		);
		add_settings_field(
			'wpzoom_instagram_general_settings_field_send_email_notification_days_before',
			esc_html__( 'Email Period', 'instagram-widget-by-wpzoom'),
			array( $this, 'settings_field_send_email_notification_days_before' ),
			'wpzoom-instagram-general-settings',
			'wpzoom_instagram_email_notification_section'
		);

		
	}

	/**
	 * Output the section info
	 *
	 * @since 1.0.0
	 */
	public function section_info( $args ) {
		echo '<h2 class="section-title">' . esc_html__( 'Global Settings', 'instagram-widget-by-wpzoom' ) . '</h2>';
	}

	/**
	 * Saniteze values from the inputs of the options form
	 *
	 * @since 1.0.0
	 */
	public function sanitize_field( $values ) {
		return $values;
	}

	public function settings_field_load_css_js() {

		$settings = get_option( 'wpzoom-instagram-general-settings' );

		$load_css_js = ! empty( $settings['load-css-js'] ) ? wp_validate_boolean( $settings['load-css-js'] ) : false;
		?>
		<input class="regular-text code"
			   id="wpzoom-instagram-widget-settings_load-css-js"
			   name="wpzoom-instagram-general-settings[load-css-js]"
			<?php checked( true, $load_css_js ); ?>
			   value="1"
			   type="checkbox">

       <p class="description" id="insta-global-assets-description"><?php _e( 'The plugin loads the CSS/JS assets only if an Instagram block, widget or shortcode is detected on a page. </br>If this doesn\'t happen, enable this option to load the assets on all pages.', 'instagram-widget-by-wpzoom' ); ?></p>


		<?php
	}

	public function settings_field_enable_unsafe_requests() {
		$settings = get_option( 'wpzoom-instagram-general-settings' );

		$enable_unsafe_requests = ! empty( $settings['enable-unsafe-requests'] ) ? wp_validate_boolean( $settings['enable-unsafe-requests'] ) : false;
		?>
		<input class="regular-text code"
			   id="wpzoom-instagram-widget-settings_enable-unsafe-requests"
			   name="wpzoom-instagram-general-settings[enable-unsafe-requests]"
			<?php checked( true, $enable_unsafe_requests ); ?>
			   value="1"
			   type="checkbox">

		<p class="description">
			<?php _e( 'Allows insecure requests to the Instagram API. Normally this should be disabled, but it may be required for the plugin to work, </br>depending on the server configuration.', 'instagram-widget-by-wpzoom' ); ?> <?php _e( '<strong>Warning!</strong> Enable only if you know what you&rsquo;re doing.', 'instagram-widget-by-wpzoom' ); ?>
		</p>
        <br/>
        <hr/>
		<?php
	}


	/**
	 * Output the Email Notificaiton section info
	 *
	 * @since 2.1.5
	 */
	public function section_email_notification( $args ) {
		echo '<h2 class="section-title">' . esc_html__( 'Email Notifications', 'instagram-widget-by-wpzoom' ) . '</h2>';
	}

	/**
	 * Output the settings field for the email notification
	 *
	 * @since 2.1.5
	 */
	public function settings_field_enable_email_notification() {
		
		$settings = get_option( 'wpzoom-instagram-general-settings' );
		$enable_email_notification = ! empty( $settings['enable-email-notification'] ) ? wp_validate_boolean( $settings['enable-email-notification'] ) : false;

		?>
		<input class="regular-text code"
			   id="wpzoom-instagram-widget-settings_enable_email_notification"
			   name="wpzoom-instagram-general-settings[enable-email-notification]"
			<?php checked( true, $enable_email_notification ); ?>
			   value="1"
			   type="checkbox">

			<p class="description">
				<?php _e( 'An email will be sent before the Access Token expires', 'instagram-widget-by-wpzoom' ); ?>
			</p>

		<?php

	}

	/**
	 * Output the settings field for the email notification days
	 *
	 * @since 2.1.5
	 */
	public function settings_field_send_email_notification_days_before() {

		$settings = get_option( 'wpzoom-instagram-general-settings' );
		$sent_email_notification_days = ! empty( $settings['send-email-notification-days-before'] ) ?  $settings['send-email-notification-days-before'] : '1 day';

		$settings_opts = array(
			'1 day'   => esc_html__( '1 Day', 'instagram-widget-by-wpzoom' ),
			'3 days'  => esc_html__( '3 Days', 'instagram-widget-by-wpzoom' ),
			'5 days'  => esc_html__( '5 Days', 'instagram-widget-by-wpzoom' ),
			'10 days' => esc_html__( '10 Days', 'instagram-widget-by-wpzoom' ),
		);

		?>
		<select lass="regular-text code"
			id="wpzoom-instagram-widget-settings_enable_email_notification"
			name="wpzoom-instagram-general-settings[send-email-notification-days-before]"
		>
		<?php
			foreach( $settings_opts as $key => $value ) {
		?>
			<option value="<?php echo $key; ?>" <?php selected( $key, $sent_email_notification_days ); ?>><?php echo $value; ?></option>
		<?php 
			}
		?>
		</select>
		<p class="description">
				<?php _e( 'When should this email be sent?', 'instagram-widget-by-wpzoom' ); ?>
			</p>
		<?php
		
	}

	/**
	 * Output the settings field for the email notification days
	 *
	 * @since 2.1.5
	 */
	public function settings_field_clear_data() {
		?>
		<a href="#" id="wpzoom_instagra_clear_data" class="button button-primary"><?php esc_html_e( 'Delete all Instagram Images from the Media Library', 'instagram-widget-by-wpzoom' ); ?> <span class="wpzoom-loading"></span></a>
		<p class="description">
			<?php _e( 'You can use this option to free up space on your server from images saved from your Instagram accounts. Other images from your Media Library will not be deleted.', 'instagram-widget-by-wpzoom' ); ?>
		</p>
		<br/>
        <hr/>
		<?php
		
	}

	public function wpzoom_instagram_clear_data() {
		
		// Define the arguments for the posts query to get only posts generated by wpzoom instagram plugin
		$args = array(
			'post_type'   => 'attachment',
			'post_status' => 'wpzoom-hidden',
			'numberposts' => -1
		);

		// Get wpzoom instagram media attachments
		$instagram_posts = get_posts( $args );

		// Check if there are attachments to delete
		if( $instagram_posts ) {
			foreach( $instagram_posts as $instagram_post ) {
				// Delete the attachment
				wp_delete_attachment( $instagram_post->ID, true ); // Set the second parameter to true to permanently delete the media file
			}
		}

		$get_users = get_posts(
			array(
				'numberposts' => -1,
				'orderby'     => 'date',
        		'order'       => 'ASC',
				'post_type'   => 'wpz-insta_user'
			)
		);

		$transient = 'zoom_instagram_is_configured';

		foreach( (array)$get_users as $user ) {

			$user_id            = isset( $user->ID ) ? intval( $user->ID ) : -1;
			$user_account_token = get_post_meta( $user_id, '_wpz-insta_token', true ) ?: '-1';

			$transient = $transient . '_' . substr( $user_account_token, 0, 20 );

			if( get_transient( $transient ) ) {
				delete_transient(  $transient );
			}
		}

		$response = array(
			'message' => esc_html__( 'All images have been removed', 'recipe-card-blocks-by-wpzoom' ),
		);

		wp_send_json_success( $response );
	}

}

WPZOOM_Instagram_General_Settings::get_instance();
