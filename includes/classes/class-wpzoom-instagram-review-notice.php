<?php
/**
 * Plugin admin review notice
 *
 * @package wpzoom/instagram-widget-by-wpzoom
 * @since 2.2.12
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for displaying review notice in admin
 */
class WPZOOM_Instagram_Review_Notice {

	/**
	 * Option name for storing install time
	 */
	const INSTALL_TIME_OPTION = 'wpzoom_instagram_installed_time';

	/**
	 * Option name for storing dismissed notices
	 */
	const DISMISSED_OPTION = 'wpzoom_instagram_dismissed_notices';

	/**
	 * Notice ID
	 */
	const NOTICE_ID = 'review';

	/**
	 * Days to wait before showing the notice
	 */
	const DAYS_BEFORE_NOTICE = 5;

	/**
	 * Current user ID
	 *
	 * @var int
	 */
	private $current_user_id;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_loaded', array( $this, 'init' ), 20 );
		add_action( 'wp_loaded', array( $this, 'handle_dismiss' ), 15 );
	}

	/**
	 * Initialize the notice
	 */
	public function init() {
		global $pagenow;

		$this->current_user_id = get_current_user_id();

		// Set install time if not already set
		if ( ! get_option( self::INSTALL_TIME_OPTION ) ) {
			update_option( self::INSTALL_TIME_OPTION, time() );
		}

		// Check if notice was dismissed by this user
		if ( $this->is_notice_dismissed() ) {
			return;
		}

		// Only show to users who can manage options
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Show on dashboard and plugin pages
		$allowed_pages = array( 'index.php', 'plugins.php', 'edit.php' );
		$is_plugin_page = 'edit.php' === $pagenow && isset( $_GET['post_type'] ) && 'wpz-insta_user' === $_GET['post_type']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! in_array( $pagenow, $allowed_pages, true ) && ! $is_plugin_page ) {
			return;
		}

		// Only show after specified days
		$install_time = get_option( self::INSTALL_TIME_OPTION );
		if ( $install_time && $install_time > strtotime( '-' . self::DAYS_BEFORE_NOTICE . ' days' ) ) {
			return;
		}

		add_action( 'admin_notices', array( $this, 'display_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Check if notice has been dismissed
	 *
	 * @return bool
	 */
	private function is_notice_dismissed() {
		$dismissed = get_option( self::DISMISSED_OPTION, array() );
		$notice_key = self::NOTICE_ID . '-user-' . $this->current_user_id;
		return is_array( $dismissed ) && in_array( $notice_key, $dismissed, true );
	}

	/**
	 * Handle notice dismissal
	 */
	public function handle_dismiss() {
		if ( ! isset( $_GET['wpzoom-instagram-hide-notice'] ) || ! isset( $_GET['_wpzoom_instagram_notice_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpzoom_instagram_notice_nonce'] ) ), 'wpzoom_instagram_hide_notice' ) ) {
			wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'instagram-widget-by-wpzoom' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'instagram-widget-by-wpzoom' ) );
		}

		$notice_id = sanitize_text_field( wp_unslash( $_GET['wpzoom-instagram-hide-notice'] ) );
		$dismissed = get_option( self::DISMISSED_OPTION, array() );

		if ( ! is_array( $dismissed ) ) {
			$dismissed = array();
		}

		if ( ! in_array( $notice_id, $dismissed, true ) ) {
			$dismissed[] = $notice_id;
			update_option( self::DISMISSED_OPTION, $dismissed );
		}

		// Redirect to remove query args
		wp_safe_redirect( remove_query_arg( array( 'wpzoom-instagram-hide-notice', '_wpzoom_instagram_notice_nonce' ) ) );
		exit;
	}

	/**
	 * Enqueue notice styles
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			'wpzoom-instagram-review-notice',
			WPZOOM_INSTAGRAM_PLUGIN_URL . 'assets/css/admin/review-notice.css',
			array(),
			WPZOOM_INSTAGRAM_VERSION
		);
	}

	/**
	 * Display the review notice
	 */
	public function display_notice() {
		$dismiss_url = wp_nonce_url(
			add_query_arg( 'wpzoom-instagram-hide-notice', self::NOTICE_ID . '-user-' . $this->current_user_id ),
			'wpzoom_instagram_hide_notice',
			'_wpzoom_instagram_notice_nonce'
		);

		$review_url = 'https://wordpress.org/support/plugin/instagram-widget-by-wpzoom/reviews/#new-post';
		?>
		<div class="notice wpzoom-instagram-review-notice">
			<a class="wpzoom-instagram-notice-dismiss notice-dismiss" href="<?php echo esc_url( $dismiss_url ); ?>"></a>

			<div class="wpzoom-instagram-notice-image">
				<img src="<?php echo esc_url( WPZOOM_INSTAGRAM_PLUGIN_URL ); ?>dist/images/backend/icon-insta.png" width="60" alt="<?php esc_attr_e( 'Instagram Widget', 'instagram-widget-by-wpzoom' ); ?>" />
			</div>
			<div class="wpzoom-instagram-notice-text">
				<h3><?php esc_html_e( 'Enjoying Instagram Widget? Leave Us a Review! ⭐', 'instagram-widget-by-wpzoom' ); ?></h3>

				<p>
					<?php
					esc_html_e(
						'We hope you\'re enjoying displaying your Instagram feed on your website! If this plugin has been helpful, we\'d really appreciate a quick review on WordPress.org. Your feedback helps other users discover the plugin and motivates us to keep improving it.',
						'instagram-widget-by-wpzoom'
					);
					?>
				</p>

				<div class="wpzoom-instagram-notice-buttons">
					<a href="<?php echo esc_url( $review_url ); ?>" class="button button-primary" target="_blank">
						<span class="dashicons dashicons-star-filled"></span>
						<?php esc_html_e( 'Leave a Review', 'instagram-widget-by-wpzoom' ); ?>
					</a>
					<a href="<?php echo esc_url( $dismiss_url ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Maybe Later', 'instagram-widget-by-wpzoom' ); ?>
					</a>
					<a href="<?php echo esc_url( $dismiss_url ); ?>" class="wpzoom-instagram-already-reviewed">
						<?php esc_html_e( 'I\'ve Already Reviewed', 'instagram-widget-by-wpzoom' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}
}

new WPZOOM_Instagram_Review_Notice();
