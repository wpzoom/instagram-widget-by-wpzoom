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
			$message = '';
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );

			if( ! empty( $instagram_profiles ) ) {
				foreach( $instagram_profiles as $profile ) {
					
					$message = '<html>
									<head>
										<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
										<meta http-equiv="X-UA-Compatible" content="IE=edge">
										<meta name="viewport" content="width=device-width">

										<style type="text/css">
											body {
												-ms-text-size-adjust: 100%; width: 100% !important; height: 100%; line-height: 1.6;
												font-family: -apple-system, BlinkMacSystemFont, avenir next, avenir, segoe ui, helvetica neue, helvetica, Cantarell, Ubuntu, roboto, noto, arial, sans-serif;
											}
											a { color: #4477bd; }
											a:hover {
											color: #e2911a !important;
											}
											a:active {
											color: #0d3d62 !important;
											}
											p{
												margin:10px 0;
												padding:0;
												font-family: -apple-system, BlinkMacSystemFont, avenir next, avenir, segoe ui, helvetica neue, helvetica, Cantarell, Ubuntu, roboto, noto, arial, sans-serif;
											}
											table{
												border-collapse:collapse;
											}
											h1,h2,h3,h4,h5,h6{
												display:block;
												margin:0;
												padding:0;
											}
											img,a img{
												border:0;
												height:auto;
												outline:none;
												text-decoration:none;
											}
											body,#bodyTable,#bodyCell{
												height:100%;
												margin:0;
												padding:0;
												width:100%;
											}
											#outlook a{
												padding:0;
											}
											img{
												-ms-interpolation-mode:bicubic;
											}
											table{
												mso-table-lspace:0pt;
												mso-table-rspace:0pt;
											}
											p,a,li,td,blockquote{
												mso-line-height-rule:exactly;
											}
											a[href^=tel],a[href^=sms]{
												color:inherit;
												cursor:default;
												text-decoration:none;
											}
											p,a,li,td,body,table,blockquote{
												-ms-text-size-adjust:100%;
												-webkit-text-size-adjust:100%;
											}
											a[x-apple-data-detectors]{
												color:inherit !important;
												text-decoration:none !important;
												font-size:inherit !important;
												font-family:inherit !important;
												font-weight:inherit !important;
												line-height:inherit !important;
											}
											@media only screen and (max-width: 480px){
												body,table,td,p,a,li,blockquote{
													-webkit-text-size-adjust:none !important;
												}
											}
											@media only screen and (max-width: 480px){
												body{
													width:100% !important;
													min-width:100% !important;
												}
											}
										</style>
									</head>
									<body style="height: 100%;margin: 0;padding: 0;width: 100%;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
										<div style="font-family:-apple-system, BlinkMacSystemFont, avenir next, avenir, segoe ui, helvetica neue, helvetica, Cantarell, Ubuntu, roboto, noto, arial, sans-serif;box-sizing:border-box;font-size:14px;line-height:1.5;max-width:600px;overflow:visible;display:block;margin:0">
											<table width="100%" cellpadding="0" cellspacing="0" style="font-family:-apple-system, BlinkMacSystemFont, avenir next, avenir, segoe ui, helvetica neue, helvetica, Cantarell, Ubuntu, roboto, noto, arial, sans-serif;box-sizing:border-box;font-size:14px;line-height:1.5">
											<tbody>
											<tr style="font-family:-apple-system, BlinkMacSystemFont, avenir next, avenir, segoe ui, helvetica neue, helvetica, Cantarell, Ubuntu, roboto, noto, arial, sans-serif;box-sizing:border-box;font-size:14px;line-height:1.5">
												<td style="font-family:-apple-system, BlinkMacSystemFont, avenir next, avenir, segoe ui, helvetica neue, helvetica, Cantarell, Ubuntu, roboto, noto, arial, sans-serif;box-sizing:border-box;font-size:14px;line-height:1.5;vertical-align:top;color:#222222;padding:25px" valign="top">

												<p>Hello there,</p>

												<p>This is a notification email to inform you that the Access Token for your Instagram profile <strong>' . $profile['name'] . '</strong> used by the <strong>Instagram Widget by WPZOOM</strong> plugin <strong>is due to expire or has already expired</strong>.</p>

												<p>To prevent any disruptions in the display of your Instagram feed on <a href="' . get_bloginfo( 'url' ) . '">' . get_bloginfo( 'url' ) . '</a>, we kindly ask you to <a href="https://www.wpzoom.com/documentation/instagram-widget/instagram-widget-how-to-reconnect-instagram-account-access-token-expired/">reconnect your Access Token</a>.</p>

												<br style="font-family:&quot;Helvetica Neue&quot;,&quot;Helvetica&quot;,Helvetica,Arial,sans-serif;box-sizing:border-box;font-size:14px;line-height:1.5">

												<p>Best regards,<br/>The <a href="https://www.wpzoom.com/" target="_blank">WPZOOM</a> team</p>

												</td>
											</tr>
											</tbody></table>
										</div>

										<div style="font-family:-apple-system, BlinkMacSystemFont, avenir next, avenir, segoe ui, helvetica neue, helvetica, Cantarell, Ubuntu, roboto, noto, arial, sans-serif;box-sizing:border-box;font-size:14px;line-height:1.5;max-width:600px;overflow:visible;display:block;margin:0">
											<table width="100%" cellpadding="0" cellspacing="0" style="font-family:&quot;Helvetica Neue&quot;,&quot;Helvetica&quot;,Helvetica,Arial,sans-serif;box-sizing:border-box;font-size:14px;line-height:1.5">
												<tbody><tr style="font-family:-apple-system, BlinkMacSystemFont, avenir next, avenir, segoe ui, helvetica neue, helvetica, Cantarell, Ubuntu, roboto, noto, arial, sans-serif;box-sizing:border-box;font-size:14px;line-height:1.5">
												<td style="font-family:-apple-system, BlinkMacSystemFont, avenir next, avenir, segoe ui, helvetica neue, helvetica, Cantarell, Ubuntu, roboto, noto, arial, sans-serif;box-sizing:border-box;font-size:14px;line-height:1.5;vertical-align:top;width:100%;clear:both;color:#777;border-top-width:1px;border-top-color:#d0d0d0;border-top-style:solid;padding:25px" valign="top">
													<p>Sent from <a href="' . get_bloginfo( 'url' ) . '">' . get_bloginfo( 'name' ) . '</a> using the <strong>Instagram Widget by WPZOOM</strong> plugin.</p>
													<br style="font-family:&quot;Helvetica Neue&quot;,&quot;Helvetica&quot;,Helvetica,Arial,sans-serif;box-sizing:border-box;font-size:14px;line-height:1.5">
												</td>
												</tr>
											</tbody></table>
										</div>
									</body>
								</html>';

					//Check if there is email and token is expired
					if ( $sendto && 'expired' == $profile['token_status'] ) {
						wp_mail( 
							$sendto, 
                            sprintf( esc_html__( '[%s] Action Required: Your Instagram Access Token expired or is about to expire!', 'instagram-widget-by-wpzoom' ), get_bloginfo( 'name' ) ),
							$message, 
							sprintf( "Content-Type: text/html; charset=UTF-8\r\nFrom: %s <%s>\r\nReply-To: %s\r\n", get_bloginfo( 'name' ), esc_html( $sendto ), 'no-reply:' . get_site_url() )
						);
					}

				}
			}

		}

		public function get_profiles_data() {

			$settings = get_option( 'wpzoom-instagram-general-settings' );
			$sent_email_notification_days = ! empty( $settings['send-email-notification-days-before'] ) ?  $settings['send-email-notification-days-before'] : '1 day';

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

					$days_before = '-' . $sent_email_notification_days . '';
					$new_date = date( 'Y-m-d', strtotime( $days_before, strtotime( $token_expire ) ) );

					if( $token_expire < $current_date || $token_expire <= $new_date ) {
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
