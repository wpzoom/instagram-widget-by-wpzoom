<?php
/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPZOOM_Instagram_Image_Uploader {
	public static $ajax_action_name = 'wpzoom_instagram_get_image_async';
	/**
	 * @var
	 */
	private static $instance;
	private static $media_metakey_name = 'wpzoom_instagram_media_id';
	private static $prefix_name = 'wpzoom-share-buttons';
	private static $post_status_name = 'wpzoom-hidden';

	/**
	 * WPZOOM_Instagram_Image_Uploader constructor.
	 */
	private function __construct() {
		// Private to disabled instantiation.
	}

	/**
	 * @return mixed
	 */
	public static function getInstance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			add_action( 'init', self::$instance, 'custom_post_status' );
			add_action( 'wp_ajax_' . self::$ajax_action_name, [ self::$instance, 'get_image_async' ] );
			add_action( 'wp_ajax_' . self::$ajax_action_name, [ self::$instance, 'get_image_async' ] );

		}

		return self::$instance;
	}

	static function get_image( $media_size, $media_url, $media_id ) {

		$args  = [
			'post_type'      => 'attachment',
			'posts_per_page' => 1,
			'post_status'    => self::$post_status_name,
			'meta_query'     => [
				[
					'key'   => self::$media_metakey_name,
					'value' => $media_id,
				],
			],
		];
		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			$post          = array_shift( $query->posts );
			$attachment_id = $post->ID;

			$image_src = wp_get_attachment_image_src( $attachment_id, self::get_image_size_name( $media_size ) );

			return ! empty( $image_src ) ? $image_src[0] : $media_url;

		}

		return false;
	}

	public static function get_image_size_name( $size ) {

		return self::$prefix_name . '-' . $size;

	}

	function get_image_async() {

		$sliced = wp_array_slice_assoc( $_POST, [ 'media-id', 'last', 'nonce', 'image-resolution', 'image-width' ] );
		$sliced = array_map( 'sanitize_text_field', $sliced );

		if ( ! wp_verify_nonce( $sliced['nonce'], self::get_nonce_action( $sliced['media-id'] ) ) ) {
			$error = new WP_Error( '001', __( 'Invalid nonce.', 'wpzoom-instagram-widget' ), __( 'Invalid nonce provided for this action', 'wpzoom-instagram-widget' ) );

			wp_send_json_error( $error, 500 );
		}

		$media_url = self::get_media_url_by_id( $sliced['media-id'] );

		if ( empty( $media_url ) ) {
			$error = new WP_Error( '002', __( 'Invalid media id.', 'wpzoom-instagram-widget' ), __( 'Could not retrieve image url with provided media id', 'wpzoom-instagram-widget' ) );

			wp_send_json_error( $error, 500 );
		}

		$args  = array(
			'post_type'      => 'attachment',
			'posts_per_page' => 1,
			'post_status'    => self::$post_status_name,
			'meta_query'     => array(
				array(
					'key'   => self::$media_metakey_name,
					'value' => $sliced['media-id'],
				),
			),
		);
		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			$post          = array_shift( $query->posts );
			$attachment_id = $post->ID;

		} else {
			$attachment_id = self::upload_image( $media_url, $sliced['media-id'] );
		}

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( $attachment_id, 500 );
		}

		$media_size = self::$instance->get_best_size( $sliced['image-width'], $sliced['image-resolution'] );

		$image_src = wp_get_attachment_image_src( $attachment_id, self::get_image_size_name( $media_size ) );

		$image_src = ! empty( $image_src ) ? $image_src[0] : $media_url;

		if ( wp_validate_boolean( $sliced['last'] ) ) {
			delete_transient( 'zoom_instagram_is_configured' );

		}

		wp_send_json_success( [ 'image_src' => $image_src ] );
	}

	public static function get_nonce_action( $id ) {
		return self::$ajax_action_name . '_' . $id;
	}

	public static function get_media_url_by_id( $media_id ) {

		$transient = get_transient( 'zoom_instagram_is_configured' );

		if ( empty( $transient->data ) ) {
			return false;
		}
		$plucked = wp_list_pluck( $transient->data, 'media_url', 'id' );

		return array_key_exists( $media_id, $plucked ) ? $plucked[ $media_id ] : false;
	}

	/**
	 * @param $media_url
	 * @param $media_id
	 *
	 * @return string|WP_Error
	 */
	static function upload_image( $media_url, $media_id ) {

		require_once( ABSPATH . 'wp-admin/includes/media.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		add_filter( 'intermediate_image_sizes_advanced', [ self::$instance, 'set_image_sizes' ], 10 );
		add_filter( 'wp_insert_attachment_data', [ self::$instance, 'insert_post_data' ], 10 );

		$attachment_id = media_sideload_image( $media_url, null, null, 'id' );

		remove_filter( 'intermediate_image_sizes_advanced', [ self::$instance, 'set_image_sizes' ], 10 );
		remove_filter( 'wp_insert_attachment_data', [ self::$instance, 'insert_post_data' ], 10 );


		update_post_meta( $attachment_id, self::$media_metakey_name, $media_id );

		return $attachment_id;

	}

//	function get_image_async( $media_size, $media_url, $media_id ) {
//
//		$args  = array(
//			'post_type'      => 'attachment',
//			'posts_per_page' => 1,
//			'post_status'    => self::$post_status_name,
//			'meta_query'     => array(
//				array(
//					'key'   => self::$media_metakey_name,
//					'value' => $media_id,
//				),
//			),
//		);
//		$query = new WP_Query( $args );
//
//		if ( $query->have_posts() ) {
//			$post          = array_shift( $query->posts );
//			$attachment_id = $post->ID;
//
//		} else {
//			$attachment_id = self::upload_image( $media_url, $media_id );
//		}
//
//
//		$image_src = wp_get_attachment_image_src( $attachment_id, self::get_image_size_name( $media_size ) );
//
//		return ! empty( $image_src ) ? $image_src[0] : $media_url;
//	}

	public function insert_post_data( $post_data ) {

		$post_data['post_status'] = self::$post_status_name;

		return $post_data;
	}

	function custom_post_status() {
		register_post_status( self::$post_status_name, [
			'public'              => true,
			'exclude_from_search' => true,
			'internal'            => true,
		] );
	}

	/**
	 * Disable the cloning of this class.
	 *
	 * @return void
	 */
	final public function __clone() {
		throw new Exception( 'Feature disabled.' );
	}

	/**
	 * Disable the wakeup of this class.
	 *
	 * @return void
	 */
	final public function __wakeup() {
		throw new Exception( 'Feature disabled.' );
	}

	/**
	 * @param $sizes
	 *
	 * @return array
	 */
	function set_image_sizes( $sizes ) {

		return [
			self::get_image_size_name( 'thumbnail' )           => [
				'width'  => 150,
				'height' => 150
			],
			self::get_image_size_name( 'low_resolution' )      => [
				'width'  => 320,
				'height' => 320
			],
			self::get_image_size_name( 'standard_resolution' ) => [
				'width'  => 640,
				'height' => 640
			]
		];

	}

	protected function get_best_size( $desired_width, $image_resolution = 'default_algorithm' ) {

		$size = 'thumbnail';

		$sizes = [
			'thumbnail'           => 150,
			'low_resolution'      => 320,
			'standard_resolution' => 640
		];

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
}
