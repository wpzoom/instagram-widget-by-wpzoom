<?php

class Wpzoom_Instagram_Widget_API {
	/**
	 * @var Wpzoom_Instagram_Widget_API The reference to *Singleton* instance of this class
	 */
	private static $instance;

	/**
	 * Instagram Access Token
	 *
	 * @var string
	 */
	protected $access_token;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Wpzoom_Instagram_Widget_API The *Singleton* instance.
	 */
	public static function getInstance()
	{
		if (null === static::$instance) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	protected function __construct() {
		$options = get_option( 'wpzoom-instagram-widget-settings' );
		$this->access_token = $options['access-token'];
	}

	/**
	 * @param $screen_name string Instagram username
	 * @param $image_limit int    Number of images to retrieve
	 * @param $image_width int    Desired image width to retrieve
	 *
	 * @return array|bool Array of tweets or false if method fails
	 */
	public function get_items( $screen_name, $image_limit, $image_width ) {
		$transient = 'zoom_instagram-' . substr( md5( serialize( func_get_args() ) ), 0, 20 );

		if ( false !== ( $result = get_transient( $transient ) ) ) {
			return $result;
		}

		$user_id = $this->get_user_id( $screen_name );

		$response = wp_remote_get( sprintf( 'https://api.instagram.com/v1/users/%s/media/recent/?access_token=%s&count=%s', $user_id, $this->access_token, $image_limit ) );

		if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) ) {
			set_transient( $transient, false, MINUTE_IN_SECONDS );

			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ) );

		$result = array();
		foreach ( $data->data as $item ) {
			$result[] = array(
				'link'      => $item->link,
				'image-url' => $item->images->{ $this->get_best_size( $image_width ) }->url
			);
		}

		set_transient( $transient, $result, 30 * MINUTE_IN_SECONDS );

		return $result;
	}

	/**
	 * @param $screen_name string Instagram username
	 *
	 * @return bool|int Instagram user id or false on error
	 */
	protected function get_user_id( $screen_name ) {
		$user_id_option = 'zoom_instagram_uid_' . $screen_name;

		if ( false !== ( $user_id = get_option( $user_id_option ) ) ) {
			return $user_id;
		}

		$response = wp_remote_get( sprintf( 'https://api.instagram.com/v1/users/search?q=%s&access_token=%s', $screen_name, $this->access_token ) );

		if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$result = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! isset( $result->data ) ) {
			return false;
		}

		$user_id = false;

		foreach ( $result->data as $user ) {
			if ( $user->username === $screen_name ) {
				$user_id = $user->id;

				break;
			}
		}

		update_option( $user_id_option, $user_id );

		return $user_id;
	}

	/**
	 * @param $desired_width int Desired image width in pixels
	 *
	 * @return string Image size for Instagram API
	 */
	protected function get_best_size( $desired_width ) {
		$size = 'thumbnail';
		$sizes = array(
			'thumbnail'           => 150,
			'low_resolution'      => 306,
			'standard_resolution' => 640
		);

		$diff = PHP_INT_MAX;

		foreach ( $sizes as $key => $value ) {
			if ( abs( $desired_width - $value ) < $diff ) {
				$size = $key;
				$diff = abs( $desired_width - $value );
			}
		}

		return $size;
	}
}
