<?php
/**
 *
 * Send Email Notification regard the API Key.
 *
 * @since 2.1.5
 * @package WPZOOM_Instagram_Widget
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPZOOM_Instagram_Email_Notification' ) ) {
	
	/**
	 * Main WPZOOM_Instagram_Email_Notification Class.
	 *
	 * @since 2.1.5
	 */
	class WPZOOM_Instagram_Email_Notification  {

		/**
		 * This plugin's instance.
		 *
		 * @var WPZOOM_Instagram_Email_Notification
		 * @since 2.1.5
		 */
		private static $instance;

		/**
		 * Provides singleton instance.
		 *
		 * @since 2.1.5
		 * @return self instance
		 */
		public static function instance() {

			if ( null === self::$instance ) {
				self::$instance = new WPZOOM_Instagram_Email_Notification();
			}

			return self::$instance;
		}

		/**
		 * WPZOOM_Instagram_Email_Notification constructor.
		 *
		 * @since 2.1.5
		 */
		function __construct() {

			// Schedule the email notification event
			add_action( 'init', array( $this, 'schedule_event' ) );

			// Hook for sending the email notification
			add_action( 'wpzoom_instagram_api_key_status', array( $this, 'send_email_notification_callback' ), 10, 2  );

			$this->send_email_notification_callback();
		
		}


		/**
		 * Create the scheduled event to send email notification
		 *
		 * @since 2.1.5
		 */
		public function schedule_event() {
			if ( ! wp_next_scheduled( 'wpzoom_instagram_api_key_status' ) ) {
				wp_schedule_event( time(), 'daily', 'wpzoom_instagram_api_key_status' );
			}
		}

		public function send_email_notification_callback() { 

			$settings = get_option( 'wpzoom-instagram-general-settings' );
			$enable_email_notification = ! empty( $settings['enable-email-notification'] ) ? wp_validate_boolean( $settings['enable-email-notification'] ) : false;

			if( ! $enable_email_notification ) {
				return;	
			}
			
			$instagram_profiles = $this->get_profiles_data();

			$sendto  = get_option( 'admin_email', '' );
			$message = 'This is a reminder email for the upcoming update of the Instragram API Key which expires on date';
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );

			if( ! empty( $instagram_profiles ) ) {
				foreach( $instagram_profiles as $profile ) {

				}
			}

		
			// // Send the email
			// if ( $sendto ) {
			// 	wp_mail( 
			// 		$sendto, 
			// 		sprintf( esc_html__( '%s - Reminder!', 'recipe-card-blocks-by-wpzoom' ), 'WPZOOM Instagram Widget & Block' ),
			// 		$message, 
			// 		sprintf( "Content-Type: text/html; charset=UTF-8\r\nFrom: %s <%s>\r\nReply-To: %s\r\n", 'WPZOOM Instagram Widget & Block', esc_html( $sendto ), 'no-reply:' . get_site_url() )
			// 	);
			// }

		}

		public function get_profiles_data() {

			$settings = get_option( 'wpzoom-instagram-general-settings' );
			$sent_email_notification_days = ! empty( $settings['send-email-notification-days-before'] ) ?  $settings['send-email-notification-days-before'] : '1 day';
			
			$days_before_expire = strtotime( '-' . $sent_email_notification_days );

			$profiles_data = array();
			$profiles = get_posts( 
				array(
					'post_type' => 'wpz-insta_user'
				)
			);

			$current_date = date( 'Y-m-d' ); // Current date

			$token_status = 'valid';

			if( ! empty( $profiles ) ) {
				foreach( $profiles as $profile ) {

					$token_expire_raw = intval( get_post_meta( $profile->ID, '_wpz-insta_token_expire', true ) );
					$time_diff = $token_expire_raw > 0 ? (int)( $token_expire_raw - time() ) : 0;
					$expires_soon = $time_diff > 0 && $time_diff < WEEK_IN_SECONDS;
					$already_expired = $time_diff <= 0;
					$token_expire = ! $already_expired && $expires_soon ? human_time_diff( time(), $token_expire_raw ) : date( 'Y-m-d', $token_expire_raw );

					if( $token_expire < $current_date ) {
						$token_status = 'expired';
					}

					$profiles_data[] = array(
						'name'             => $profile->post_title,
						'ID'               => $profile->ID,
						'raw_token'        => get_post_meta( $profile->ID, '_wpz-insta_token', true ),
						'token_expire_raw' => intval( get_post_meta( $profile->ID, '_wpz-insta_token_expire', true ) ),
						'token_expire'     => $token_expire,
						'token_status'     => $token_status,
					);
				} 
			}

			return $profiles_data;

		}

	}


}

WPZOOM_Instagram_Email_Notification::instance();
