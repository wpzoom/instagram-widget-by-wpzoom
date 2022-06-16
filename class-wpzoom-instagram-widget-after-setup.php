<?php
/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Wpzoom_Instagram_Widget_After_Setup {

	/**
	 * @var Wpzoom_Instagram_Widget_After_Setup The reference to *Singleton* instance of this class
	 *
	 * @since 1.8.4
	 */
	private static $instance;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Wpzoom_Instagram_Widget_After_Setup The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Construct.
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {

		//Run only once
		if ( get_option( 'wpzoom_run_only_once_01' ) ) {
			return;
		}

		$getOldSettings = get_option( 'wpzoom-instagram-widget-settings' );

		if( is_array( $getOldSettings ) && !empty( $getOldSettings ) ) {
			
			$token      = isset( $getOldSettings['basic-access-token'] )  ? $getOldSettings['basic-access-token'] : '';
			$user_name  = isset( $getOldSettings['user-info-fullname'] )  ? $getOldSettings['user-info-fullname'] : '';
			$user_bio   = isset( $getOldSettings['user-info-biography'] ) ? $getOldSettings['user-info-biography'] : '';
			$user_image = isset( $getOldSettings['user-info-avatar'] )    ? $getOldSettings['user-info-avatar'] : '';

			if ( ! empty( $token ) ) {
				$info = Wpzoom_Instagram_Widget_API::get_basic_user_info_from_token( $token );

				if ( false !== $info && is_object( $info ) && property_exists( $info, 'username' ) && property_exists( $info, 'account_type' ) ) {
						$user = wp_strip_all_tags( $info->username );
						$insert_post = wp_insert_post( array(
							'post_title'   => $user,
							'post_type'    => 'wpz-insta_user',
							'post_status'  => 'publish',
							'post_content' => $user_bio
						), true );

						if ( ! is_wp_error( $insert_post ) ) {
							update_post_meta( $insert_post, '_wpz-insta_token', $token );
							update_post_meta( $insert_post, '_wpz-insta_token_expire', strtotime( '+60 days' ) );
							update_post_meta( $insert_post, '_wpz-insta_account-type', sanitize_text_field( $info->account_type ) );

							update_post_meta( $insert_post, '_wpz-insta_user_name', sanitize_text_field( $user_name ) );
							update_post_meta( $insert_post, '_thumbnail_id', $user_image );

							if ( property_exists( $info, 'profile_picture' ) && ! empty( $info->profile_picture ) ) {
								WPZOOM_Instagram_Widget_Settings()->generate_featured_image( $info->profile_picture, $insert_post, $user );
							}
						}

				}
			}
		}

		add_option( 'wpzoom_run_only_once_01', true );

	}

}

Wpzoom_Instagram_Widget_After_Setup::get_instance();