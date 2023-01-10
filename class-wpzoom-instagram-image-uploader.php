<?php
/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPZOOM_Instagram_Image_Uploader
 */
class WPZOOM_Instagram_Image_Uploader {
	public static $ajax_action_name = 'wpzoom_instagram_get_image_async';
	private static $instance;
	private static $media_metakey_name = 'wpzoom_instagram_media_id';
	private static $prefix_name        = 'wpzoom-share-buttons';
	private static $post_status_name   = 'wpzoom-hidden';
	private static $transient_name     = 'zoom_instagram_is_configured';

	/**
	 * WPZOOM_Instagram_Image_Uploader constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'custom_post_status' ) );
		add_action( 'wp_ajax_' . self::$ajax_action_name, array( $this, 'get_image_async' ) );
		add_action( 'wp_ajax_nopriv_' . self::$ajax_action_name, array( $this, 'get_image_async' ) );
	}

	/**
	 * @return mixed
	 */
	public static function getInstance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get image.
	 *
	 * @param $media_size
	 * @param $media_url
	 * @param $media_id
	 *
	 * @return bool
	 */
	static function get_image( $media_size, $media_url, $media_id ) {
		$args  = array(
			'post_type'      => 'attachment',
			'posts_per_page' => 1,
			'post_status'    => self::$post_status_name,
			'meta_query'     => array(
				array(
					'key'   => self::$media_metakey_name,
					'value' => $media_id,
				),
			),
		);
		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			$post          = array_shift( $query->posts );
			$attachment_id = $post->ID;

			$image_src = wp_get_attachment_image_src( $attachment_id, self::get_image_size_name( $media_size ) );

			return ! empty( $image_src ) ? $image_src[0] : $media_url;
		} else {
			$attachment_id = self::upload_image( $media_url, $media_id );
			self::$instance->set_images_to_transient( $attachment_id, $media_id );

			$image_src = wp_get_attachment_image_src( $attachment_id, self::get_image_size_name( $media_size ) );

			return ! empty( $image_src ) ? $image_src[0] : $media_url;
		}

		return false;
	}

	/**
	 * Get prefixed image size name.
	 *
	 * @param $size
	 *
	 * @return string
	 */
	public static function get_image_size_name( $size ) {
		return self::$prefix_name . '-' . $size;
	}

	/**
	 * Get response api from transient.
	 *
	 * @return mixed
	 */
	function get_api_transient() {
		return json_decode( get_transient( self::$transient_name ) );
	}

	/**
	 * Get transient lifetime from settings.
	 *
	 * @return float|int
	 */
	function get_transient_lifetime() {
		$interval = (int) WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( get_the_ID(), 'check-new-posts-interval-number' );
		$interval_suffix = (int) WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( get_the_ID(), 'check-new-posts-interval-suffix' );

		$values = array(
			MINUTE_IN_SECONDS,
			HOUR_IN_SECONDS,
			DAY_IN_SECONDS,
			WEEK_IN_SECONDS,
			MONTH_IN_SECONDS,
		);
		$keys   = array_keys( $values );
		$type   = in_array( $interval_suffix, $keys ) ? $values[ $interval_suffix ] : $values[2];

		return $type * $interval;
	}

	/**
	 * Get image from ajax.
	 */
	function get_image_async() {
		$sliced = wp_array_slice_assoc( $_POST, array( 'media-id', 'nonce', 'image-resolution', 'image-width', 'regenerate-thumbnails' ) );
		$sliced = array_map( 'sanitize_text_field', $sliced );

		if ( ! wp_verify_nonce( $sliced['nonce'], self::get_nonce_action( $sliced['media-id'] ) ) ) {
			$error = new WP_Error( '001', __( 'Invalid nonce.', 'instagram-widget-by-wpzoom' ), __( 'Invalid nonce provided for this action', 'instagram-widget-by-wpzoom' ) );

			wp_send_json_error( $error, 500 );
		}

		$media_url = self::get_media_url_by_id( $sliced['media-id'] );

		if ( empty( $media_url ) ) {
			$error = new WP_Error( '002', __( 'Invalid media id.', 'instagram-widget-by-wpzoom' ), __( 'Could not retrieve image url with provided media id', 'instagram-widget-by-wpzoom' ) );

			wp_send_json_error( $error, 500 );
		}

		$args = array(
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
			self::$instance->set_images_to_transient( $attachment_id, $sliced['media-id'] );
		}

		if ( wp_validate_boolean( $sliced['regenerate-thumbnails'] ) ) {
			$metadata = $this->regenerate_thumbnails( $attachment_id );

			if ( ! is_wp_error( $metadata ) && ! empty( $metadata ) ) {
				self::$instance->set_images_to_transient( $attachment_id, $sliced['media-id'] );
			}
		}

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( $attachment_id, 500 );
		}

		$media_size = self::$instance->get_best_size( $sliced['image-width'], $sliced['image-resolution'] );

		$image_src = wp_get_attachment_image_src( $attachment_id, self::get_image_size_name( $media_size ) );

		$image_src = ! empty( $image_src ) ? $image_src[0] : $media_url;

		wp_send_json_success( array( 'image_src' => $image_src ) );
	}

	/**
	 * @param $id
	 *
	 * @return string
	 */
	public static function get_nonce_action( $id ) {
		return self::$ajax_action_name . '_' . $id;
	}

	public function regenerate_thumbnails( $attachment_id ) {
		$fullsizepath = get_attached_file( $attachment_id );

		add_filter( 'intermediate_image_sizes_advanced', array( self::$instance, 'set_image_sizes' ), 10 );

		$metadata = wp_generate_attachment_metadata( $attachment_id, $fullsizepath );

		remove_filter( 'intermediate_image_sizes_advanced', array( self::$instance, 'set_image_sizes' ), 10 );
		return $metadata;
	}

	/**
	 * @param $media_id
	 *
	 * @return bool
	 */
	public static function get_media_url_by_id( $media_id ) {
		$transient = self::$instance->get_api_transient();

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
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		add_filter( 'intermediate_image_sizes_advanced', array( self::$instance, 'set_image_sizes' ), 10 );
		add_filter( 'wp_insert_attachment_data', array( self::$instance, 'insert_post_data' ), 10 );

		$attachment_id = media_sideload_image( $media_url, null, null, 'id' );

		remove_filter( 'intermediate_image_sizes_advanced', array( self::$instance, 'set_image_sizes' ), 10 );
		remove_filter( 'wp_insert_attachment_data', array( self::$instance, 'insert_post_data' ), 10 );

		update_post_meta( $attachment_id, self::$media_metakey_name, $media_id );

		return $attachment_id;
	}

	/**
	 * @param $post_data
	 *
	 * @return mixed
	 */
	public function insert_post_data( $post_data ) {
		$post_data['post_status'] = self::$post_status_name;

		return $post_data;
	}

	/**
	 * Register custom post status.
	 */
	function custom_post_status() {
		register_post_status(
			self::$post_status_name,
			array(
				'public'              => true,
				'exclude_from_search' => true,
				'internal'            => true,
			)
		);
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
	 * Set image sizes.
	 *
	 * @param $sizes
	 *
	 * @return array
	 */
	function set_image_sizes( $sizes ) {
		return array(
			self::get_image_size_name( 'thumbnail' )      => array(
				'width'  => 150,
				'height' => 150,
			),
			self::get_image_size_name( 'low_resolution' ) => array(
				'width'  => 320,
				'height' => 320,
			),
			self::get_image_size_name( 'standard_resolution' ) => array(
				'width'  => 640,
				'height' => 640,
			),
			self::get_image_size_name( 'full_resolution' ) => array(
				'width'  => 9999,
				'height' => 9999,
			),
		);
	}

	/**
	 * Alter transient data object with the new values.
	 *
	 * @param $attachment_id
	 * @param $media_id
	 */
	protected function set_images_to_transient( $attachment_id, $media_id ) {
		$transient = self::$instance->get_api_transient();

		if ( ! empty( $transient->data ) ) {
			foreach ( $transient->data as $key => $item ) {
				if ( $item->id === $media_id ) {
					$thumbnail                         = wp_get_attachment_image_src( $attachment_id, self::get_image_size_name( 'thumbnail' ) );
					$low_resolution                    = wp_get_attachment_image_src( $attachment_id, self::get_image_size_name( 'low_resolution' ) );
					$standard_resolution               = wp_get_attachment_image_src( $attachment_id, self::get_image_size_name( 'standard_resolution' ) );
					$full_resolution                   = wp_get_attachment_image_src( $attachment_id, self::get_image_size_name( 'full_resolution' ) );
					$item->images->thumbnail->url      = ! empty( $thumbnail ) ? $thumbnail[0] : '';
					$item->images->low_resolution->url = ! empty( $low_resolution ) ? $low_resolution[0] : '';
					$item->images->standard_resolution->url = ! empty( $standard_resolution ) ? $standard_resolution[0] : '';
					$item->images->full_resolution->url = ! empty( $full_resolution ) ? $full_resolution[0] : '';

					$transient->data[ $key ] = $item;
				}
			}

			$this->set_api_transient( $transient );
		}
	}

	/**
	 * Set api transient.
	 *
	 * @param $data
	 *
	 * @return bool
	 */
	function set_api_transient( $data ) {
		return set_transient( self::$transient_name, wp_json_encode( $data ), self::$instance->get_transient_lifetime() );
	}

	/**
	 * Get best image size.
	 *
	 * @param $desired_width
	 * @param string        $image_resolution
	 *
	 * @return int|string
	 */
	protected function get_best_size( $desired_width, $image_resolution = 'low_resolution' ) {
		$size = 'thumbnail';

		$sizes = array(
			'thumbnail'           => 150,
			'low_resolution'      => 320,
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
}

WPZOOM_Instagram_Image_Uploader::getInstance();
