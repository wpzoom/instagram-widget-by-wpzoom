<?php
/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
#[AllowDynamicProperties]
class Wpzoom_Instagram_Widget_API {
	/**
	 * @var Wpzoom_Instagram_Widget_API The reference to *Singleton* instance of this class
	 */
	private static $instance;

	/**
	 * Request headers.
	 *
	 * @var array
	 */
	public $headers = array();

	/**
	 * Errors collector.
	 *
	 * @var array|WP_Error
	 */
	public $errors = array();

	/**
	 * Instagram Settings
	 *
	 * @var array
	 */
	public $settings;

	/**
	 * Instagram Access Token
	 *
	 * @var string
	 */
	protected $access_token;

	/**
	 * Feed ID
	 *
	 * @var string
	 */
	protected $feed_id;

	/**
	 * Business Page ID
	 *
	 * @var string
	 */
	protected $business_page_id;

	/**
	 * Class constructor
	 */
	protected function __construct() {
		$this->is_forced_timeout     = (bool) WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( get_the_ID(), 'enable-request-timeout' );
		$this->request_timeout_value = 15;

		if ( $this->is_forced_timeout && ! empty( $this->request_timeout_value ) ) {
			$this->headers['timeout'] = $this->request_timeout_value;
		}

		$this->image_uploader = WPZOOM_Instagram_Image_Uploader::getInstance();

		$this->errors = new WP_Error();
	}

	public function init() {
		add_action( 'init', array( $this, 'set_schedule' ) );
		add_action( 'wpzoom_instagram_widget_cron_hook', array( $this, 'execute_cron' ) );
		add_filter( 'cron_schedules', array( $this, 'add_cron_interval' ) );

		$this->maybe_show_api_deprecation_notice();
	}

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Wpzoom_Instagram_Widget_API The *Singleton* instance.
	 */
	public static function getInstance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Manually set the access token.
	 *
	 * @since 2.0.0
	 *
	 * @param string $token The access token to set.
	 * @return void
	 */
	public function set_access_token( $token ) {
		$this->access_token = $token;
	}

	/**
	 * Manually set the access token.
	 *
	 * @since 2.0.0
	 *
	 * @param string $token The access token to set.
	 * @return void
	 */
	public function set_feed_id( $id ) {
		$this->feed_id = $id;
	}

	/**
	 * Manually set the bussines page id.
	 *
	 * @since 2.0.0
	 *
	 * @param string $id The page id to set.
	 * @return void
	 */
	public function set_business_page_id( $id ) {
		$this->business_page_id = $id;
	}

	/**
	 * Fetches a remote URL either safely or not, depending on a setting.
	 *
	 * @since 2.0.6
	 *
	 * @param  string         $url  URL to retrieve.
	 * @param  array          $args Optional. Request arguments. Default empty array.
	 * @return array|WP_Error                 The response or WP_Error on failure.
	 */
	public static function remote_get( $url, $args = array() ) {
		$settings = get_option( 'wpzoom-instagram-general-settings' );
		$enable_unsafe_requests = ! empty( $settings['enable-unsafe-requests'] ) ? wp_validate_boolean( $settings['enable-unsafe-requests'] ) : false;

		return $enable_unsafe_requests ? wp_remote_get( $url, $args ) : wp_safe_remote_get( $url, $args );
	}

	/**
	 * Register custom cron intervals
	 *
	 * @since 1.8.0
	 *
	 * @param array $schedules Registered schedules array.
	 * @return array
	 */
	public function add_cron_interval( $schedules ) {

		$schedules['before_access_token_expires'] = array(
			'interval' => 5097600, // 59 days.
			'display'  => esc_attr__( 'Before Access Token Expires', 'instagram-widget-by-wpzoom' ),
		);

		return $schedules;
	}

	/**
	 * Register schedule event
	 *
	 * @return void
	 */
	public function set_schedule() {

		if ( ! wp_next_scheduled( 'wpzoom_instagram_widget_cron_hook' ) ) {
			wp_schedule_event( time(), 'before_access_token_expires', 'wpzoom_instagram_widget_cron_hook' );
		}

	}

	/**
	 * Execute cron event
	 *
	 * @return boolean
	 */
	public function execute_cron() {
		$all_users = get_posts( array(
			'numberposts' => -1,
			'post_type'   => 'wpz-insta_user',
		) );

		if ( ! empty( $all_users ) && is_array( $all_users ) ) {
			foreach ( $all_users as $user ) {
				if ( $user instanceof WP_Post ) {
					$user_name    = get_the_title( $user );
					$user_display = sprintf( '@%s', $user_name );
					$token        = get_post_meta( $user->ID, '_wpz-insta_token', true );
					$connection_type   = get_post_meta( $user->ID, '_wpz-insta_connection-type', true );


					if ( false !== $token && ! empty( $token ) ) {

						$request_url = add_query_arg(
							array(
								'grant_type'   => 'ig_refresh_token',
								'access_token' => $token,
							),
							'https://graph.instagram.com/refresh_access_token'
						);

						if( 'facebook_graph_api' == $connection_type ) {
							$request_url = add_query_arg(
								array(
									'access_token' => $token,
								),
								'https://www.wpzoom.com/graph-auth'
							);
						}

						$response      = self::remote_get( $request_url, $this->headers );
						$response_code = wp_remote_retrieve_response_code( $response );

						if ( ! is_wp_error( $response ) ) {
							$body = wp_remote_retrieve_body( $response );
							$data = json_decode( $body );
						}

						if ( 200 === $response_code ) {
							$date_format    = get_option( 'date_format' );
							$time_format    = get_option( 'time_format' );
							$notice_status  = 'success';
							$notice_message = sprintf( __( '<strong>WPZOOM Instagram Widget:</strong> The Instagram Access Token was refreshed automatically on %1$s at %2$s for the account <em>%3$s</em>.', 'instagram-widget-by-wpzoom' ), date( $date_format ), date( $time_format ), esc_html( $user_display ) );

							update_post_meta( $user->ID, '_wpz-insta_token', $data->access_token );
							update_post_meta( $user->ID, '_wpz-insta_token_expire', strtotime( '+60 days' ) );
						} else {
							if ( ! isset( $data->error ) ) {
								error_log( __( 'Something wrong! Doesn\'t isset $data->error.', 'instagram-widget-by-wpzoom' ) );
								return false;
							} else {
								error_log( $data->error->error_user_msg );
							}

							$notice_status  = 'error';
							$notice_message = '';
							$settings_url   = admin_url( 'edit.php?post_type=wpz-insta_user' );

							if ( 190 === $data->error->code ) {
								// Error validating access token: Session has expired.
								$notice_message = wp_kses_post( __( '<strong>WPZOOM Instagram Widget:</strong> ', 'instagram-widget-by-wpzoom' ) ) . $data->error->message;
							} elseif ( 10 === $data->error->code && ! self::is_access_token_valid( $token ) ) {
								// Application does not have permission for this action.
								// User need to generate new Access Token manually.
								$notice_message  = sprintf( __( '<strong>WPZOOM Instagram Widget:</strong> The Access Token for the account <em>%1$s</em> has expired!<br/>', 'instagram-widget-by-wpzoom' ), $user_display );
								$notice_message .= sprintf( __( 'We cannot update access tokens automatically for Instagram private accounts. You need to manually generate a new access token, reauthorize here: %1$s', 'instagram-widget-by-wpzoom' ), '<a href="' . esc_url( $settings_url ) . '">' . __( 'Instagram Widget Settings', 'instagram-widget-by-wpzoom' ) . '</a>' );
							}
						}

						update_option(
							'_wpz-insta_cron-result',
							array( $user->ID => array( 'status'  => $notice_status, 'message' => $notice_message ) ) + (array) get_option( '_wpz-insta_cron-result', array() )
						);
					}
				}
			}
		}
	}

	public static function reset_cache( $sanitized_data ) {
		delete_transient( 'zoom_instagram_is_configured' );
		delete_transient( 'zoom_instagram_user_info' );

		// Remove schedule hook `wpzoom_instagram_widget_cron_hook`.
		if ( empty( $sanitized_data['basic-access-token'] ) ) {
			wp_clear_scheduled_hook( 'wpzoom_instagram_widget_cron_hook' );
		}
	}

	/**
	 * @param $screen_name string Instagram username
	 * @param $image_limit int    Number of images to retrieve
	 * @param $image_width int    Desired image width to retrieve
	 *
	 * @return array|bool Array of tweets or false if method fails
	 */
	public function get_items( $instance ) {

		$sliced = wp_array_slice_assoc(
			$instance,
			array(
				'image-limit',
				'image-width',
				'image-resolution',
				'username',
				'disable-video-thumbs',
				'include-pagination',
				'bypass-transient',
				'preview',
				'preview-id',
				'user-id'
			)
		);

		$image_limit          = $sliced['image-limit'];
		$image_width          = $sliced['image-width'];
		$image_resolution     = ! empty( $sliced['image-resolution'] ) ? $sliced['image-resolution'] : 'standard_resolution';
		$injected_username    = ! empty( $sliced['username'] ) ? $sliced['username'] : '';
		$disable_video_thumbs = ! empty( $sliced['disable-video-thumbs'] );
		$include_pagination   = ! empty( $sliced['include-pagination'] );
		$bypass_transient     = ! empty( $sliced['bypass-transient'] );
		$user_id              = ! empty( $sliced['user-id'] ) ? $sliced['user-id'] : '';
		
		$preview              = ! empty( $sliced['preview'] );
		$preview_id           = ! empty( $sliced['preview-id'] ) ? $sliced['preview-id'] : '';		

		if( isset( $instance['widget-id'] ) ) {
			$transient = 'zoom_instagram_is_configured_' . $instance['widget-id'];
		}
		else {
			$transient = 'zoom_instagram_is_configured';
		}

		if( ! empty( $this->access_token ) && $preview && ! empty( $preview_id ) ) {
			if( $image_limit <= 20 ) {
				$image_limit = 20;
				$bypass_transient = false;
			}
			$transient = 'zoom_instagram_preview_' . substr( $preview_id, 0, 20 ) . '_' . $user_id;
			
		}

		if ( ! empty( $this->access_token ) && ! empty( $this->feed_id ) && ! $preview ) {
			$transient = $transient . '_' . substr( $this->feed_id, 0, 20 );
		}

		$injected_username = trim( $injected_username );

		if ( ! $bypass_transient ) {
			$data = json_decode( get_transient( $transient ) );
			if ( false !== $data && is_object( $data ) && ! empty( $data->data ) ) {
				return self::processing_response_data( $data, $image_width, $image_resolution, $image_limit, $disable_video_thumbs, $include_pagination );
			}
		}

		if ( ! empty( $this->access_token ) ) {

			$request_url = add_query_arg(
				array(
					'fields'       => 'media_url,media_type,caption,username,permalink,thumbnail_url,timestamp,children{media_url,media_type,thumbnail_url}',
					'access_token' => $this->access_token,
					'limit'        => $image_limit,
				),
				'https://graph.instagram.com/me/media'
			);

			if( ! empty( $this->business_page_id ) ) {
				$request_url = add_query_arg(
					array(
						'fields'       => 'media_url,media_product_type,thumbnail_url,caption,id,media_type,timestamp,username,permalink,children%7Bmedia_url,id,media_type,timestamp,permalink,thumbnail_url%7D',
						'access_token' => $this->access_token,
						'limit'        => $image_limit,
					),
					'https://graph.facebook.com/v21.0/' . $this->business_page_id . '/media'
				);
			}

			$response = self::remote_get( $request_url, $this->headers );

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				if ( ! $bypass_transient ) {
					set_transient( $transient, wp_json_encode( false ), MINUTE_IN_SECONDS );
				}

				$error_data = $this->get_error( 'items-with-token-invalid-response' );
				$this->errors->add( $error_data['code'], $error_data['message'] );

				return false;
			}

			$raw_data = json_decode( wp_remote_retrieve_body( $response ) );

			$data = self::convert_items_to_old_structure( $raw_data, $bypass_transient, $this->access_token );

			if ( $include_pagination && property_exists( $raw_data, 'paging' ) ) {
				$data->paging = $raw_data->paging;
			}
		}

		if ( ! empty( $data->data ) ) {
			if ( ! $bypass_transient ) {
				set_transient(
					$transient,
					wp_json_encode( $data ),
					$preview ? DAY_IN_SECONDS : $this->get_transient_lifetime( $this->feed_id )
				);

			}
		} else {
			if ( ! $bypass_transient ) {
				set_transient( $transient, wp_json_encode( false ), MINUTE_IN_SECONDS );
			}

			$error_data = $this->get_error( 'items-with-token-invalid-data-structure' );
			$this->errors->add( $error_data['code'], $error_data['message'] );

			return false;
		}

		return self::processing_response_data( $data, $image_width, $image_resolution, $image_limit, $disable_video_thumbs, $include_pagination );
	}

	public static function processing_response_data( $data, $image_width, $image_resolution, $image_limit, $disable_video_thumbs = false, $include_pagination = false ) {
		$result   = array();
		$username = '';
		$defaults = array(
			'link'               => '',
			'image-url'          => '',
			'original-image-url' => '',
			'type'               => '',
			'timestamp'          => '',
			'children'           => '',
			'image-id'           => '',
			'image-caption'      => '',
			'likes'        => 0,
			'comments'     => 0,
		);

		if ( empty( $image_resolution ) ) {
			$image_resolution = 'standard_resolution';
		}

		foreach ( $data->data as $key => $item ) {

			$item = (object) wp_parse_args( $item, $defaults );            

			if ( empty( $username ) ) {
				$username = $item->user->username;
			}

			if ( $key === $image_limit ) {
				break;
			}

			if ( ! empty( $disable_video_thumbs ) && isset( $item->type ) && 'VIDEO' == $item->type ) {
				$image_limit ++;
				continue;
			}

			$best_size = self::get_best_size( $image_width, $image_resolution );
			$image_url = $item->images->{$best_size}->url;

			$regexPattern = '/-\d+[Xx]\d+\./';
			$subst = '.';

			$local_image_url = preg_replace( $regexPattern, $subst, $image_url, 1 );

			$result[] = array(
				'link'               => $item->link,
				'image-url'          => $image_url,
				'local-image-url'    => $local_image_url,
				'original-image-url' => property_exists( $item, 'media_url' ) && ! empty( $item->media_url ) ? $item->media_url : '',
				'type'               => $item->type,
				'timestamp'          => property_exists( $item, 'timestamp' ) && ! empty( $item->timestamp ) ? $item->timestamp : '',
				'children'           => property_exists( $item, 'children' ) && ! empty( $item->children ) ? $item->children : '',
				'image-id'           => ! empty( $item->id ) ? esc_attr( $item->id ) : '',
				'image-caption'      => ! empty( $item->caption->text ) ? esc_attr( $item->caption->text ) : '',
				'likes'        => ! empty( $item->likes ) ? esc_attr( $item->likes ) : 0,
				'comments'     => ! empty( $item->comments ) ? esc_attr( $item->comments ) : 0,
			);
		}

		$result = array(
			'items'    => $result,
			'username' => $username,
		);

		if ( $include_pagination && property_exists( $data, 'paging' ) ) {
			$result['paging'] = $data->paging;
		}

		return $result;
	}

    /**
     * Get likes and comments count for a media item
     * 
     * @param string $media_id The ID of the media item
     * @return array Array containing likes_count and comments_count
     */ 
    public static function get_likes_comments_count( $media_id, $access_token ) {

        // Default values
        $likes_comments_count = array(
            'like_count'    => 0,
            'comments_count' => 0,
        );

        if( empty( $media_id ) ) {
            return $likes_comments_count;
        }

        $request_url = add_query_arg(
            array(
                'fields'       => 'like_count,comments_count',
                'access_token' => $access_token,
            ),
            'https://graph.facebook.com/v21.0/' . $media_id  
        );

        $response = wp_remote_get( $request_url );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return $likes_comments_count;
        }

        $raw_data = json_decode( wp_remote_retrieve_body( $response ) );

        $likes_comments_count['like_count'] = $raw_data->like_count;
        $likes_comments_count['comments_count'] = $raw_data->comments_count;

        return $likes_comments_count;
    }

	/**
	 * @param $desired_width int Desired image width in pixels
	 *
	 * @return string Image size for Instagram API
	 */
	public static function get_best_size( $desired_width, $image_resolution = 'standard_resolution' ) {
		$size  = 'thumbnail';
		$sizes = array(
			'thumbnail'           => 150,
			'low_resolution'      => 306,
			'standard_resolution' => 640,
			'full_resolution'     => 9999,
		);

		$diff = PHP_INT_MAX;

		if ( array_key_exists( $image_resolution, $sizes ) ) {
			return $image_resolution;
		}

		foreach ( $sizes as $key => $value ) {
			if ( abs( $desired_width - $value ) < $diff ) {
				$size = $key;
				$diff = abs( $desired_width - $value );
			}
		}

		return $size;
	}

	/**
	 * Retrieve error message by key.
	 *
	 * @param $key
	 *
	 * @return bool|mixed
	 */
	public function get_error( $key ) {
		$errors = $this->get_errors();

		return array_key_exists( $key, $errors ) ? $errors[ $key ] : false;
	}

	/**
	 * Get error messages collection.
	 *
	 * @return array
	 */
	public function get_errors() {
		return array(
			'user-info-without-token'                    => array(
				'code'    => 'user-info-without-token',
				'message' => esc_html__( 'Empty json user info from Public Feed.', 'instagram-widget-by-wpzoom' ),
			),
			'response-data-without-token-from-json-invalid-response' => array(
				'code'    => 'response-data-without-token-from-json-invalid-response',
				'message' => esc_html__( 'The request from the Public Feed failed. Invalid server response from Public JSON API url.', 'instagram-widget-by-wpzoom' ),
			),
			'response-data-without-token-from-json-invalid-json-format' => array(
				'code'    => 'response-data-without-token-from-json-invalid-json-format',
				'message' => esc_html__( 'The request from the Public Feed failed. Invalid JSON format from Public JSON API url.', 'instagram-widget-by-wpzoom' ),
			),
			'response-data-without-token-from-html-invalid-response' => array(
				'code'    => 'response-data-without-token-from-html-invalid-response',
				'message' => esc_html__( 'The request from the Public Feed failed. Check username.', 'instagram-widget-by-wpzoom' ),
			),
			'response-data-without-token-from-html-invalid-json-format' => array(
				'code'    => 'response-data-without-token-from-html-invalid-json-format',
				'message' => esc_html__( 'The request from the Public Feed failed. Invalid JSON format from parsed html body.', 'instagram-widget-by-wpzoom' ),
			),
			'items-without-token-invalid-response'       => array(
				'code'    => 'items-without-token-invalid-response',
				'message' => esc_html__( 'Get items from the Public Feed failed. Invalid response.', 'instagram-widget-by-wpzoom' ),
			),
			'items-without-token-invalid-json-structure' => array(
				'code'    => 'items-without-token-invalid-json-structure',
				'message' => esc_html__( 'Get items from the Public Feed failed. Malformed data structure.', 'instagram-widget-by-wpzoom' ),
			),
			'items-with-token-invalid-response'          => array(
				'code'    => 'items-with-token-invalid-response',
				'message' => esc_html__( 'Getting items from the Instagram API Feed failed. Invalid response.', 'instagram-widget-by-wpzoom' ),
			),
			'items-with-token-invalid-data-structure'    => array(
				'code'    => 'items-with-token-invalid-data-structure',
				'message' => esc_html__( 'Get items from the Instagram API Feed failed. Malformed data structure.', 'instagram-widget-by-wpzoom' ),
			),
			'user-with-token-invalid-response'           => array(
				'code'    => 'user-with-token-invalid-response',
				'message' => esc_html__( 'Get user data from the Instagram API Feed failed. Invalid response.', 'instagram-widget-by-wpzoom' ),
			),
			'user-with-token-invalid-data-structure'     => array(
				'code'    => 'user-with-token-invalid-data-structure',
				'message' => esc_html__( 'Get user data from the Instagram API Feed failed. Malformed data structure.', 'instagram-widget-by-wpzoom' ),
			),

		);
	}

	public static function convert_items_to_old_structure( $data, $preview = false, $access_token = '' ) {

		$converted       = new stdClass();
		$converted->data = array();
		$image_uploader = WPZOOM_Instagram_Image_Uploader::getInstance();

		foreach ( $data->data as $key => $item ) {
			$is_video = property_exists( $item, 'media_type' ) && 'VIDEO' === $item->media_type;
			$media_url = $is_video && property_exists( $item, 'thumbnail_url' ) && ! empty( $item->thumbnail_url ) ? $item->thumbnail_url : $item->media_url;

			$converted->data[] = (object) array(
				'id'           => $item->id,
				'media_url'    => ( $is_video && property_exists( $item, 'media_url' ) ? $item->media_url : $media_url ),
				'user'         => (object) array(
					'id'              => null,
					'fullname'        => null,
					'profile_picture' => null,
					'username'        => $item->username,
				),
				'images'       => (object) array(
					'thumbnail'           => (object) array(
						'url'    => $preview ? $media_url : $image_uploader->get_image( 'thumbnail', $media_url, $item->id ),
						'width'  => 150,
						'height' => 150,
					),
					'low_resolution'      => (object) array(
						'url'    => $preview ? $media_url : $image_uploader->get_image( 'low_resolution', $media_url, $item->id ),
						'width'  => 320,
						'height' => 320,
					),
					'standard_resolution' => (object) array(
						'url'    => $preview ? $media_url : $image_uploader->get_image( 'standard_resolution', $media_url, $item->id ),
						'width'  => 640,
						'height' => 640,
					),
					'full_resolution' => (object) array(
						'url'    => $preview ? $media_url : $image_uploader->get_image( 'full_resolution', $media_url, $item->id ),
						'width'  => 9999,
						'height' => 9999,
					),
				),
				'type'         => $item->media_type,
				'likes'        => self::get_likes_comments_count( $item->id, $access_token )['like_count'],
				'comments'     => self::get_likes_comments_count( $item->id, $access_token )['comments_count'],
				'created_time' => null,
				'timestamp'    => $item->timestamp,
				'children'     => ( isset( $item->children ) ? $item->children : null ),
				'link'         => $item->permalink,
				'caption'      => (object) array(
					'text' => isset( $item->caption ) ? $item->caption : '',
				),
			);
		}

		return $converted;
	}

	function get_transient_lifetime( $id ) {

		$feed_id = isset( $id ) ? $id : 0;

		$interval = (int) WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'check-new-posts-interval-number' );
		$interval_suffix = (int) WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'check-new-posts-interval-suffix' );

		$values = array(
			MINUTE_IN_SECONDS,
			HOUR_IN_SECONDS,
			DAY_IN_SECONDS,
			WEEK_IN_SECONDS,
			MONTH_IN_SECONDS,
		);
		$keys   = array_keys( $values );
		$type   = in_array( $interval_suffix, $keys ) ? $values[ $interval_suffix ] : $values[2];

		return intval( $type * $interval ) ;
	}

	public function get_user_info( $injected_username = '' ) {
		$transient = 'zoom_instagram_user_info';

		$injected_username = rtrim( $injected_username );

		if ( false !== ( $data = json_decode( get_transient( $transient ) ) ) && is_object( $data ) && ! empty( $data->data ) ) {
			return $data;
		}

		if ( ! empty( $this->access_token ) ) {
			$request_url = add_query_arg(
				array(
					'access_token' => $this->access_token,
					'fields'       => 'account_type,id,media_count,username',
				),
				'https://graph.instagram.com/me'
			);

			if( ! empty( $this->business_page_id ) ) {
				$request_url = add_query_arg(
					array(
						'fields'       => 'name,username,profile_picture_url,biography',
						'access_token' => $this->access_token,
					),
					'https://graph.facebook.com/' . $this->business_page_id
				);
			}

			$response = self::remote_get( $request_url, $this->headers );

			if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) ) {
				set_transient( $transient, wp_json_encode( false ), MINUTE_IN_SECONDS );

				$error_data = $this->get_error( 'user-with-token-invalid-response' );
				$this->errors->add( $error_data['code'], $error_data['message'] );

				return false;
			}

			$data = json_decode( wp_remote_retrieve_body( $response ) );
			$data = $this->convert_user_info_to_old_structure( $data );
		}

		if ( ! empty( $data->data ) ) {
			set_transient( $transient, wp_json_encode( $data ), $this->get_transient_lifetime( $this->feed_id ) );
		} else {
			set_transient( $transient, wp_json_encode( false ), MINUTE_IN_SECONDS );

			$error_data = $this->get_error( 'user-with-token-invalid-data-structure' );
			$this->errors->add( $error_data['code'], $error_data['message'] );

			return false;
		}

		return $data;
	}

	// Get Instagram Business Pages
	public static function get_business_accounts_from_token( $access_token ) {

		$accountsData = array(
			'accounts' => array(),
			'error'    => false,
		);

		if ( ! empty( $access_token ) ) {

			// Get Instagram Business Account ID
			$request_url = add_query_arg(
				array(
					'fields'       => 'instagram_business_account,access_token',
					'limit'        => 500,
					'access_token' => $access_token,
					'timeout'      => 60,
				),
				'https://graph.facebook.com/me/accounts'
			);

			$response =  wp_remote_get( $request_url );

			if ( ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
				$pages_arr = json_decode( wp_remote_retrieve_body( $response ) );
			}
			else {
				$page_error = json_decode( wp_remote_retrieve_body( $response ) );
			}

			if ( isset( $page_error ) && isset( $page_error->error ) ) {
				$error_message = '';
				if( isset( $page_error->error->message )  ) {
					$error_message .= $page_error->error->message;
				}

				return $error_message;
			}

			if ( ! empty( $pages_arr ) && is_object( $pages_arr ) && ! empty( $pages_arr->data ) ) {
				foreach ( $pages_arr->data as $key => $page ) {
					if( isset( $page->instagram_business_account ) ) {
						$instagram_business_account_id  = $page->instagram_business_account->id;
						//$instagram_business_account_token = $page->token;
						$request_account_url = add_query_arg(
							array(
								'fields'       => 'name,username,profile_picture_url,biography',
								'access_token' => $access_token,
							),
							'https://graph.facebook.com/' . $instagram_business_account_id
						);

						$response_account = wp_remote_get( $request_account_url );

						if ( ! is_wp_error( $response_account ) && 200 == wp_remote_retrieve_response_code( $response_account ) ) {
							$account_data = json_decode( wp_remote_retrieve_body( $response_account ) );
							$accountsData['accounts'][] = $account_data;
						}

					}
				}

			}

		}

		return $accountsData;

	}

	public static function get_basic_user_info_from_token( $access_token ) {
		$output = false;

		if ( ! empty( $access_token ) ) {
			$request_url = add_query_arg(
				array(
					'access_token' => $access_token,
					'fields'       => 'name,account_type,username,biography,profile_picture_url',
				),
				'https://graph.instagram.com/me'
			);

			$response = self::remote_get( $request_url );

			if ( ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
				$output = json_decode( wp_remote_retrieve_body( $response ) );
			}
		}

		return $output;
	}

	function convert_user_info_to_old_structure( $user_info ) {
		$converted = new stdClass();

		$user_info_from_settings = WPZOOM_Instagram_Widget_Settings::get_instance()->get_settings();

		$avatar = property_exists( $user_info, 'profile_picture' ) ? $user_info->profile_picture : null;

		if ( ! empty( $user_info_from_settings['user-info-avatar'] ) ) {
			$img_src = wp_get_attachment_image_src( $user_info_from_settings['user-info-avatar'] );
			if ( ! empty( $img_src ) && is_array( $img_src ) ) {
				$avatar = $img_src[0];
			}
		}

		$fullname = ! empty( $user_info->username ) ? $user_info->username : null;

		if ( ! empty( $user_info_from_settings['user-info-fullname'] ) ) {
			$fullname = $user_info_from_settings['user-info-fullname'];
		}

		$converted->data = (object) array(
			'bio'             => ! empty( $user_info_from_settings['user-info-biography'] ) ? $user_info_from_settings['user-info-biography'] : null,
			'counts'          => (object) array(
				'followed_by' => null,
				'follows'     => null,
				'media'       => null,
			),
			'full_name'       => $fullname,
			'id'              => ! empty( $user_info->id ) ? $user_info->id : '',
			'is_business'     => null,
			'profile_picture' => $avatar,
			'username'        => ! empty( $user_info->username ) ? $user_info->username : '',
			'website'         => null,
		);

		return $converted;
	}

	public function is_configured() {
		$transient = 'zoom_instagram_is_configured';

		if ( false !== ( $result = json_decode( get_transient( $transient ) ) ) ) {
			if ( 'yes' === $result ) {
				return true;
			}

			if ( 'no' === $result ) {
				return false;
			}

			if ( ! empty( $result ) ) {
				return true;
			}
		}

		$condition = $this->is_access_token_valid( $this->access_token );

		if ( true === $condition ) {
			set_transient( $transient, wp_json_encode( 'yes' ), DAY_IN_SECONDS );

			return true;
		}

		set_transient( $transient, wp_json_encode( 'no' ), DAY_IN_SECONDS );

		return false;
	}

	/**
	 * Check if given access token is valid for Instagram Api.
	 */
	public static function is_access_token_valid( $access_token ) {
		if ( empty( $access_token ) ) {
			return false;
		}

		$request_url = add_query_arg(
			array(
				'fields'       => 'username',
				'access_token' => $access_token,

			),
			'https://graph.instagram.com/me'
		);

		$response = self::remote_get( $request_url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		return true;
	}

	// Add this new method to get and cache followers count
	public function get_followers_count($user_business_page_id, $user_account_token) {
		if (empty($user_business_page_id) || empty($user_account_token)) {
			return 0;
		}

		// Create a unique transient key for this account
		$transient_key = 'wpz-insta_followers_' . $user_business_page_id;

		// Try to get cached followers count
		$followers_count = get_transient($transient_key);

		if (false === $followers_count) {
			// If no cached data, fetch from API
			$graph_api_url = add_query_arg(
				array(
					'fields'       => 'followers_count',
					'access_token' => $user_account_token,
				),
				'https://graph.facebook.com/v21.0/' . $user_business_page_id
			);

			$response = wp_remote_get($graph_api_url);

			if (!is_wp_error($response) && 200 === wp_remote_retrieve_response_code($response)) {
				$data = json_decode(wp_remote_retrieve_body($response));
				if (isset($data->followers_count)) {
					$followers_count = intval($data->followers_count);
					// Cache for 24 hours
					set_transient($transient_key, $followers_count, DAY_IN_SECONDS);
				} else {
					$followers_count = 0;
				}
			} else {
				$followers_count = 0;
			}
		}

		return $followers_count;
	}


    // Add this new method to get and cache following count
    public function get_following($user_business_page_id, $user_account_token) {
        if (empty($user_business_page_id) || empty($user_account_token)) {
            return 0;
        }

        // Create a unique transient key for this account
        $transient_key = 'wpz-insta_following_' . $user_business_page_id;

        // Try to get cached following count
        $following_count = get_transient($transient_key);

        if (false === $following_count) {
            // If no cached data, fetch from API
            $graph_api_url = add_query_arg(
                array(
                    'fields'       => 'follows_count',
                    'access_token' => $user_account_token,
                ),
                'https://graph.facebook.com/v21.0/' . $user_business_page_id
            );

            $response = wp_remote_get($graph_api_url);

            if (!is_wp_error($response) && 200 === wp_remote_retrieve_response_code($response)) {
                $data = json_decode(wp_remote_retrieve_body($response));
                if (isset($data->follows_count)) {
                    $following_count = intval($data->follows_count);
                    // Cache for 24 hours
                    set_transient($transient_key, $following_count, DAY_IN_SECONDS);
                } else {
                    $following_count = 0;
                }
            } else {
                $following_count = 0;
            }
        }

        return $following_count;
    }

    // Add this new method to get and cache media count
    public function get_media_count($user_business_page_id, $user_account_token) {
        if (empty($user_business_page_id) || empty($user_account_token)) {
            return 0;
        }

        // Create a unique transient key for this account
        $transient_key = 'wpz-insta_media_count_' . $user_business_page_id;

        // Try to get cached media count
        $media_count = get_transient($transient_key);

        if (false === $media_count) {
            // If no cached data, fetch from API
            $graph_api_url = add_query_arg(
                array(
                    'fields'       => 'media_count',
                    'access_token' => $user_account_token,
                ),
                'https://graph.facebook.com/v21.0/' . $user_business_page_id
            );

            $response = wp_remote_get($graph_api_url);

            if (!is_wp_error($response) && 200 === wp_remote_retrieve_response_code($response)) {
                $data = json_decode(wp_remote_retrieve_body($response));
                if (isset($data->media_count)) {
                    $media_count = intval($data->media_count);
                    // Cache for 24 hours
                    set_transient($transient_key, $media_count, DAY_IN_SECONDS);
                } else {
                    $media_count = 0;
                }
            } else {
                $media_count = 0;
            }
        }

        return $media_count;
    }

	// Add this new method to check if using Basic Display API
	public static function is_using_basic_display_api( $user_id ) {
		
		if ( empty ( $user_id ) ) 
			return false;

		$connection_type = get_post_meta( $user_id, '_wpz-insta_connection-type', true );

		if ( empty( $connection_type ) || 'facebook_graph_api' !== $connection_type && 'business_instagram_login_api' !== $connection_type ) {
			return true;
		}

	}

	// Add this method to show the admin notice
	public function maybe_show_api_deprecation_notice() {
		// Only show on admin pages
		
		if ( ! is_admin() ) 
			return;

		// Get all Instagram users
		$users = get_posts(array(
			'post_type' => 'wpz-insta_user',
			'posts_per_page' => -1,
		));

		$has_basic_api_users = false;
		foreach ($users as $user) {
			if ( self::is_using_basic_display_api( $user->ID ) ) {
				$has_basic_api_users = true;
				break;
			}
		}

		// If we found any users using Basic Display API, show the notice
		if ($has_basic_api_users) {
			add_action('admin_notices', function() {
				$notice_dismissed = get_option('wpz_insta_basic_api_notice_dismissed');
				if ($notice_dismissed) return;

				?>
				<div class="notice notice-warning is-dismissible" id="wpz-insta-api-deprecation-notice">
					<h3 style="margin-bottom: 0;">
						<svg style="vertical-align: middle;" width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
							<path fill="#f0b849" d="M12 5.99L19.53 19H4.47L12 5.99M12 2L1 21h22L12 2zm1 14h-2v2h2v-2zm0-6h-2v4h2v-4z"/>
						</svg>
						<?php _e('Major Instagram API Update', 'instagram-widget-by-wpzoom'); ?>
					</h3>

					<p style="font-size: 14px;">
						<?php _e("We've detected that you connected your Instagram account using a method that will soon be discontinued or that you're using a <strong>personal account</strong> to display your Instagram feed. Instagram will discontinue this method on <strong>December 4, 2024</strong>, and will no longer allow the use of personal accounts within our plugin.", 'instagram-widget-by-wpzoom'); ?>
					</p>

					<p style="font-size: 14px;">
                        <?php
                        printf(
                            __('To continue displaying your Instagram feed, you\'ll need to: <br>1. Convert your Personal Instagram account to a <a href="%1$s" target="_blank">Professional account</a> (Creator or Business).<br>2. Go to the <a href="%2$s">Instagram Accounts</a> page<br/>3. Click on the Instagram account you want to reconnect and reconnect using the new Facebook or Instagram methods.', 'instagram-widget-by-wpzoom'),
                            'https://help.instagram.com/502981923235522',
                            admin_url('edit.php?post_type=wpz-insta_user')
                        );
                        ?>
                    </p>

					<p style="font-size: 14px;">
						<a href="<?php echo admin_url('edit.php?post_type=wpz-insta_user'); ?>" class="button button-primary">
							<?php _e('Go to Instagram Accounts', 'instagram-widget-by-wpzoom'); ?>
						</a>

						<a href="https://www.wpzoom.com/documentation/instagram-widget/basic-display-api-deprecation/" target="_blank" class="button button-secondary">
							<?php _e('Learn More About This Change', 'instagram-widget-by-wpzoom'); ?>
						</a>
					</p>
				</div>

				<script>
				jQuery(document).ready(function($) {
					$(document).on('click', '#wpz-insta-api-deprecation-notice .notice-dismiss', function() {
						$.ajax({
							url: ajaxurl,
							data: {
								action: 'dismiss_wpz_insta_basic_api_notice'
							}
						});
					});
				});
				</script>
				<?php
			});

			// Add AJAX handler for dismissing the notice
			add_action('wp_ajax_dismiss_wpz_insta_basic_api_notice', function() {
				update_option('wpz_insta_basic_api_notice_dismissed', true);
				wp_die();
			});
		}
	}
}

Wpzoom_Instagram_Widget_API::getInstance();
