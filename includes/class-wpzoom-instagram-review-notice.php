<?php
/**
 * Plugin admin review notice
 *
 * Registers the review notice with WPZOOM Notice Center when available.
 *
 * @package wpzoom/instagram-widget-by-wpzoom
 * @since 2.2.12
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for displaying review notice in admin via Notice Center
 */
class WPZOOM_Instagram_Review_Notice {

	/**
	 * Option name for storing install time
	 */
	const INSTALL_TIME_OPTION = 'wpzoom_instagram_installed_time';

	/**
	 * Notice ID for Notice Center
	 */
	const NOTICE_ID = 'wpzoom_instagram_review';

	/**
	 * Days to wait before showing the notice
	 */
	const DAYS_BEFORE_NOTICE = 5;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'wpzoom_notice_center_notices', array( $this, 'register_notice_center' ) );
	}

	/**
	 * Register the review notice with WPZOOM Notice Center.
	 *
	 * @param array $notices Existing notices from the filter.
	 * @return array Notices with review notice added when applicable.
	 */
	public function register_notice_center( $notices ) {
		if ( ! is_array( $notices ) ) {
			$notices = array();
		}

		// Set install time if not already set.
		if ( ! get_option( self::INSTALL_TIME_OPTION ) ) {
			update_option( self::INSTALL_TIME_OPTION, time() );
		}

		// Only show after specified days.
		$install_time = get_option( self::INSTALL_TIME_OPTION );
		if ( $install_time && $install_time > strtotime( '-' . self::DAYS_BEFORE_NOTICE . ' days' ) ) {
			return $notices;
		}

		$review_url = 'https://wordpress.org/support/plugin/instagram-widget-by-wpzoom/reviews/#new-post';

		$content = '<p>' . esc_html__(
			'We hope you\'re enjoying displaying your Instagram feed on your website! If this plugin has been helpful, we\'d really appreciate a quick review on WordPress.org. Your feedback helps other users discover the plugin and motivates us to keep improving it.',
			'instagram-widget-by-wpzoom'
		) . '</p>';

		$notices[] = array(
			'id'               => self::NOTICE_ID,
			'heading'          => __( 'Enjoying Instagram Widget? Leave Us a Review! ⭐', 'instagram-widget-by-wpzoom' ),
			'content'          => $content,
			'icon'             => array(
				'type'             => 'image',
				'src'              => WPZOOM_INSTAGRAM_PLUGIN_URL . 'assets/backend/img/plugin-icon.svg',
				'background_color' => 'transparent',
			),
			'primary_button'   => array(
				'label'   => __( 'Leave a Review', 'instagram-widget-by-wpzoom' ),
				'url'     => $review_url,
				'new_tab' => true,
			),
			'secondary_button' => array(
				'label' => __( 'Maybe Later', 'instagram-widget-by-wpzoom' ),
				'url'   => '',
			),
			'capability'       => 'manage_options',
			'screens'          => array( 'dashboard', 'plugins', 'edit-wpz-insta_user' ),
			'source'           => 'Instagram Widget',
			'priority'         => 20,
		);

		return $notices;
	}
}

new WPZOOM_Instagram_Review_Notice();
