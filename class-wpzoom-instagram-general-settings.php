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
			<?php _e( 'Allows insecure requests to the Instagram API. Normally this should be disabled, but it may be required for the plugin to work, </br>depending on the server configuration.', 'instagram-widget-by-wpzoom' ); ?>
			</br></br>
			<span class="notice notice-warning">
				<?php _e( '<strong>Warning!</strong> Potential security risk. Only enable if you&rsquo;re having issues.', 'instagram-widget-by-wpzoom' ); ?>
			</span>
		</p>
		<?php
	}

}

WPZOOM_Instagram_General_Settings::get_instance();
