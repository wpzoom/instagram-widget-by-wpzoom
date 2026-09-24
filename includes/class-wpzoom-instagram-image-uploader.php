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

	/** Option key (inside wpzoom-instagram-general-settings) that turns WebP storage on. */
	const WEBP_SETTING = 'webp-cached-images';

	/** WebP compression used for cached feed images; the JPEG originals are never rewritten. */
	const WEBP_QUALITY = 75;

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
		}

		$attachment_id = self::upload_image( $media_url, $media_id );

		if ( ! is_wp_error( $attachment_id ) ) {
			self::$instance->set_images_to_transient( $attachment_id, $media_id );
			$image_src = wp_get_attachment_image_src( $attachment_id, self::get_image_size_name( $media_size ) );
		}

		return ! empty( $image_src ) ? $image_src[0] : $media_url;
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
		// Always resize from the original download: once WebP is on, WordPress makes the
		// converted full-size copy the "attached" file, and re-encoding from that would
		// stack lossy generations. wp_get_original_image_path() falls back to the attached file.
		$fullsizepath = function_exists( 'wp_get_original_image_path' ) ? wp_get_original_image_path( $attachment_id ) : '';
		if ( ! $fullsizepath ) {
			$fullsizepath = get_attached_file( $attachment_id );
		}

		add_filter( 'intermediate_image_sizes_advanced', array( self::$instance, 'set_image_sizes' ), 10 );
		self::start_webp();

		$metadata = wp_generate_attachment_metadata( $attachment_id, $fullsizepath );

		self::stop_webp();
		remove_filter( 'intermediate_image_sizes_advanced', array( self::$instance, 'set_image_sizes' ), 10 );
		return $metadata;
	}

	/* ------------------------------------------------------------------ *
	 *  WebP storage for cached feed images
	 *
	 *  Scoped to this plugin's own sideloads/regenerations via core's
	 *  image_editor_output_format filter, so the rest of the media library
	 *  is untouched. Originals stay as downloaded; only the generated feed
	 *  sizes change format, which makes the option fully reversible.
	 * ------------------------------------------------------------------ */

	/** Can this server write WebP at all (GD or Imagick with WebP support)? */
	public static function webp_supported() {
		return wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) );
	}

	/** Option on and server capable. */
	public static function webp_enabled() {
		$settings = get_option( 'wpzoom-instagram-general-settings' );
		$enabled  = ! empty( $settings[ self::WEBP_SETTING ] ) && wp_validate_boolean( $settings[ self::WEBP_SETTING ] );

		return $enabled && self::webp_supported();
	}

	private static function start_webp() {
		if ( ! self::webp_enabled() ) {
			return;
		}
		add_filter( 'image_editor_output_format', array( __CLASS__, 'webp_output_format' ), 10, 3 );
		add_filter( 'wp_editor_set_quality', array( __CLASS__, 'webp_quality' ), 10, 2 );
	}

	private static function stop_webp() {
		remove_filter( 'image_editor_output_format', array( __CLASS__, 'webp_output_format' ), 10 );
		remove_filter( 'wp_editor_set_quality', array( __CLASS__, 'webp_quality' ), 10 );
	}

	public static function webp_output_format( $formats, $filename = '', $mime_type = '' ) {
		$formats['image/jpeg'] = 'image/webp';
		$formats['image/png']  = 'image/webp';

		return $formats;
	}

	public static function webp_quality( $quality, $mime_type ) {
		return 'image/webp' === $mime_type ? (int) apply_filters( 'wpz_insta_webp_quality', self::WEBP_QUALITY ) : $quality;
	}

	/** IDs of every attachment this plugin downloaded from Instagram, oldest first. */
	public static function cached_attachment_ids() {
		return get_posts(
			array(
				'post_type'      => 'attachment',
				// 'any' skips statuses flagged exclude_from_search — which is exactly our own wpzoom-hidden.
				'post_status'    => array_keys( get_post_stati() ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'meta_key'       => self::$media_metakey_name, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			)
		);
	}

	/**
	 * Regenerate the feed sizes of a slice of cached attachments (WebP or JPEG,
	 * depending on the current setting). Driven in batches from the settings
	 * screen so it works without WP-Cron and shows progress.
	 *
	 * Each call is bounded by wall time as well as count: encoding large photos
	 * (WebP through GD especially) can take seconds apiece, and hosts commonly
	 * kill PHP after ~30 s regardless of set_time_limit(). At least one image is
	 * always processed so the job cannot stall.
	 *
	 * @return array{next:int,total:int,done:bool,failed:int}
	 */
	public static function regenerate_cached_batch( $offset, $limit = 8, $time_budget = 10 ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$ids     = self::cached_attachment_ids();
		$total   = count( $ids );
		$slice   = array_slice( $ids, max( 0, (int) $offset ), max( 1, (int) $limit ) );
		$failed  = 0;
		$did     = 0;
		$started = microtime( true );
		$budget  = (float) apply_filters( 'wpz_insta_regenerate_time_budget', $time_budget );

		foreach ( $slice as $id ) {
			if ( $did > 0 && ( microtime( true ) - $started ) > $budget ) {
				break;
			}
			$did++;
			$file = get_attached_file( $id );
			if ( ! $file || ! file_exists( $file ) ) {
				$failed++;
				continue;
			}
			$metadata = self::getInstance()->regenerate_thumbnails( $id );
			if ( is_wp_error( $metadata ) || empty( $metadata ) ) {
				$failed++;
				continue;
			}
			wp_update_attachment_metadata( $id, $metadata );
			// Keep the attached file in step with the (possibly converted) full-size file, as core does on upload.
			if ( ! empty( $metadata['file'] ) ) {
				$uploads = wp_get_upload_dir();
				update_attached_file( $id, trailingslashit( $uploads['basedir'] ) . ltrim( $metadata['file'], '/' ) );
			}
		}

		$next = (int) $offset + $did;

		return array(
			'next'   => $next,
			'total'  => $total,
			'done'   => $next >= $total,
			'failed' => $failed,
		);
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
	 * @param $post_name
	 *
	 * @return string|WP_Error
	 */
	public static function wp_get_attachment_by_post_name( $post_name ) {

		$args           = array(
			'posts_per_page' => 1,
			'post_type'      => 'attachment',
			'name'           => trim( $post_name ),
		);

		$get_attachment = new WP_Query( $args );

		if ( ! $get_attachment || ! isset( $get_attachment->posts, $get_attachment->posts[0] ) ) {
			return false;
		}

		return $get_attachment->posts[0];
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

		// Download url to a temp file
		$tmp = download_url( $media_url );

		if ( ! is_wp_error( $tmp ) ) {
			
			$filename = '';
			$image_meta = wp_read_image_metadata( $tmp );
		
			if ( $image_meta ) {
				if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
					$filename = $image_meta['title'];
				}
			}
			
			if( empty( $filename ) ) {
				// Get the filename and extension ("photo.png" => "photo", "png")
				$filename  = pathinfo( $media_url, PATHINFO_FILENAME );
				$filename = sanitize_file_name( $filename );
			}
	
			$pattern = '/^(.*?)(\.jpeg|\.jpg_)/';
			preg_match( $pattern, $filename, $matches );
			if ( ! empty( $matches[1] ) ) {
				$filename = $matches[1];
			}

			//Check if it doesn't exist
			$get_local_image   = self::wp_get_attachment_by_post_name( $filename );
	
			if( $get_local_image && isset( $get_local_image->ID ) ) {
				update_post_meta( $get_local_image->ID, self::$media_metakey_name, $media_id );
				return $get_local_image->ID;
			}
		}

		add_filter( 'intermediate_image_sizes_advanced', array( self::$instance, 'set_image_sizes' ), 10 );
		add_filter( 'wp_insert_attachment_data', array( self::$instance, 'insert_post_data' ), 10 );
		self::start_webp();

		$attachment_id = media_sideload_image( $media_url, null, null, 'id' );

		self::stop_webp();
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
	protected function get_best_size( $desired_width, $image_resolution = 'standard_resolution' ) {
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
