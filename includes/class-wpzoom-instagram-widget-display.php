<?php
/**
 * Exit if accessed directly.
 */
defined( 'ABSPATH' ) or die;

/**
 * WPZOOM Instagram Widget Display class
 *
 * @package WPZOOM_Instagram_Widget
 */
class Wpzoom_Instagram_Widget_Display {
	/**
	 * @var Wpzoom_Instagram_Widget_Display The reference to *Singleton* instance of this class
	 */
	private static $instance;

	/**
	 * @var Wpzoom_Instagram_Widget_API
	 */
	protected $api;

	/**
	 * Is this the pro version?
	 */
	private $is_pro = false;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Wpzoom_Instagram_Widget_Display The *Singleton* instance.
	 */
	public static function getInstance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Runs some intialization functions.
	 *
	 * @return void
	 */
	public function init() {
		// add_image_size( 'wpzoom-instagram-profile-photo-size', 128, 128, true );

		$this->is_pro = apply_filters( 'wpz-insta_is-pro', false );

		add_shortcode( 'instagram', array( $this, 'get_shortcode_output' ) );
		
		// Add AJAX handlers for fast load more functionality
		add_action( 'wp_ajax_wpzoom_instagram_load_more', array( $this, 'ajax_load_more_posts' ) );
		add_action( 'wp_ajax_nopriv_wpzoom_instagram_load_more', array( $this, 'ajax_load_more_posts' ) );

		// Add AJAX handler for preview load more (admin only - serves cached posts for product linking)
		add_action( 'wp_ajax_wpzoom_instagram_preview_load_more', array( $this, 'ajax_preview_load_more_posts' ) );

	}

	/**
	 * AJAX handler for load more posts functionality.
	 * Supports two modes:
	 * 1. Cache-based: When 'offset' is provided, serves posts from transient cache first.
	 *    This ensures posts the user has linked products to in the preview are shown.
	 * 2. API-based: Falls back to Instagram API pagination when cache is exhausted.
	 */
	public function ajax_load_more_posts() {
		// Prevent caching of AJAX responses by optimization plugins
		if ( ! headers_sent() ) {
			header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
			header( 'Pragma: no-cache' );
			header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
		}
		
		// Verify nonce
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wpzinsta-pro-load-more' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Sanitize input data
		$feed_id            = isset( $_POST['feed_id'] ) ? intval( $_POST['feed_id'] ) : 0;
		$item_amount        = isset( $_POST['item_amount'] ) ? intval( $_POST['item_amount'] ) : 9;
		$image_size         = isset( $_POST['image_size'] ) ? sanitize_text_field( $_POST['image_size'] ) : 'standard_resolution';
		$allowed_post_types = isset( $_POST['allowed_post_types'] ) ? sanitize_text_field( $_POST['allowed_post_types'] ) : 'IMAGE,VIDEO,CAROUSEL_ALBUM';
		$next_url           = isset( $_POST['next'] ) ? sanitize_text_field( $_POST['next'] ) : '';
		$cache_offset       = isset( $_POST['cache_offset'] ) ? intval( $_POST['cache_offset'] ) : -1;

		// Get feed settings
		$feed_post = get_post( $feed_id );
		if ( ! $feed_post || 'wpz-insta_feed' !== $feed_post->post_type ) {
			wp_send_json_error( 'Invalid feed ID' );
		}

		// Get user account details for this feed
		$user_id               = WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'user-id' );
		$user_account_token    = get_post_meta( $user_id, '_wpz-insta_token', true );
		$user_business_page_id = get_post_meta( $user_id, '_wpz-insta_page_id', true );

		if ( empty( $user_account_token ) ) {
			wp_send_json_error( 'No valid access token found' );
		}

		// Initialize API
		$this->api = Wpzoom_Instagram_Widget_API::getInstance();
		$this->api->set_access_token( $user_account_token );
		$this->api->set_feed_id( $feed_id );

		if ( ! empty( $user_business_page_id ) ) {
			$this->api->set_business_page_id( $user_business_page_id );
		}

		// Get feed layout settings
		$image_width      = (int) WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'image-width' );
		$image_resolution = ! empty( $image_size ) ? $image_size : 'standard_resolution';

		// --- Cache-based loading: try to serve from cached posts first ---
		if ( $cache_offset >= 0 ) {
			// Use a high image-limit to get ALL cached items so we can accurately check has_more.
			// IMPORTANT: skip-likes-comments must be false to match the transient key from the initial fetch
			// (the initial fetch uses skip-likes-comments=false, which adds '_lc' to the transient key).
			$all_items = $this->api->get_items(
				array(
					'image-limit'         => 100, // Get all cached items
					'image-resolution'    => $image_resolution,
					'image-width'         => $image_width,
					'include-pagination'  => true,
					'allowed-post-types'  => $allowed_post_types,
					'bypass-transient'    => false, // Read from cache only
					'skip-likes-comments' => false, // Must match initial fetch transient key (_lc suffix)
					'access-token'        => $user_account_token,
					'feed-id'             => $feed_id,
					'business-page-id'    => $user_business_page_id,
				)
			);

			if ( is_array( $all_items ) && ! empty( $all_items['items'] ) ) {
				// Over-slice to compensate for hidden posts that will be filtered out by items_html.
				// This ensures we get enough visible items to fill the requested $item_amount.
				$hidden_count = 0;
				if ( self::$instance->is_pro && $feed_id > 0 ) {
					$hidden_posts_for_slice = get_post_meta( $feed_id, '_wpz-insta_hidden-posts', true );
					if ( is_array( $hidden_posts_for_slice ) ) {
						$hidden_count = count( $hidden_posts_for_slice );
					}
				}
				$slice_amount = $item_amount + $hidden_count;
				$cached_slice = array_slice( $all_items['items'], $cache_offset, $slice_amount );

				if ( ! empty( $cached_slice ) ) {
					// Ensure images use valid URLs: local thumbnails may not exist for cached items
					foreach ( $cached_slice as &$ci ) {
						if ( ! empty( $ci['image-url'] ) && ! empty( $ci['original-image-url'] ) ) {
							$local_path = self::convert_url_to_path( $ci['image-url'] );
							if ( ! file_exists( $local_path ) ) {
								$ci['image-url'] = $ci['original-image-url'];
							}
						} elseif ( empty( $ci['image-url'] ) && ! empty( $ci['original-image-url'] ) ) {
							$ci['image-url'] = $ci['original-image-url'];
						}
					}
					unset( $ci );

					// Prepare args for HTML generation (includes product linking data)
					$args = array(
						'layout'                 => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'layout' ),
						'item-num'               => $item_amount,
						'col-num'                => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'col-num' ),
						'show-overlay'           => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'show-overlay' ),
						'hover-link'             => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'hover-link' ),
						'show-media-type-icons'  => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'show-media-type-icons' ),
						'hover-media-type-icons' => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'hover-media-type-icons' ),
						'hover-date'             => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'hover-date' ),
						'allowed-post-types'     => $allowed_post_types,
						'image-size'             => $image_size,
						'show-likes'             => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'show-likes' ),
						'show-comments'          => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'show-comments' ),
						'feed-id'                => $feed_id,
					);

					// When load more is from backend preview iframe, include "Link to a product" and moderate (eye) buttons
					if ( ! empty( $_POST['preview'] ) && is_user_logged_in() && current_user_can( 'manage_options' ) ) {
						$args['preview'] = true;
					}

					$items_consumed_count = 0;
					$html = self::items_html( $cached_slice, $args, $items_consumed_count );

					// Use items_consumed (actual items processed) for offset, not the full slice count.
					// This prevents skipping unprocessed items when hidden posts cause early count-limit break.
					$new_offset   = $cache_offset + $items_consumed_count;
					$total_cached = count( $all_items['items'] );
					// Cache has more items, or API has more pages
					$api_has_more   = ! empty( $all_items['paging'] ) && property_exists( $all_items['paging'], 'next' ) && ! empty( $all_items['paging']->next );
					$cache_has_more = $new_offset < $total_cached;
					$has_more       = $cache_has_more || $api_has_more;

					// Generate lightbox content for cached items (pass $args so feed-id is available for product tags)
					$lightbox_html = '';
					$lightbox_enabled = WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'lightbox' );
					if ( false === $lightbox_enabled || boolval( $lightbox_enabled ) ) {
						$lightbox_html = self::lightbox_items_html( $cached_slice, $user_id, $args );
					}

					wp_send_json_success( array(
						'html'         => $html,
						'lightbox_html' => $lightbox_html,
						'has_more'     => $has_more,
						'next_url'     => $next_url, // Keep the API next URL for when cache is exhausted
						'from_cache'   => true,
						'cache_offset' => $new_offset,
					) );
				}
			}
			// If cache didn't have items at this offset, fall through to API-based loading
		}

		// --- API-based loading: fetch from Instagram API ---
		// Extract pagination cursor from the next URL
		$pagination_cursor = '';
		if ( ! empty( $next_url ) ) {
			$parsed_url = parse_url( $next_url );
			if ( isset( $parsed_url['query'] ) ) {
				parse_str( $parsed_url['query'], $params );
				if ( isset( $params['after'] ) ) {
					$pagination_cursor = $params['after'];
				}
			}
		}

		// Fallback: if button had no usable next_url, try the cached transient paging data.
		// The transient stores the raw API response including paging.next from the initial fetch.
		if ( empty( $pagination_cursor ) && $cache_offset >= 0 && isset( $all_items ) && is_array( $all_items ) && ! empty( $all_items['paging'] ) ) {
			$cached_paging = $all_items['paging'];
			if ( property_exists( $cached_paging, 'next' ) && ! empty( $cached_paging->next ) ) {
				$parsed_url = parse_url( $cached_paging->next );
				if ( isset( $parsed_url['query'] ) ) {
					parse_str( $parsed_url['query'], $params );
					if ( isset( $params['after'] ) ) {
						$pagination_cursor = $params['after'];
					}
				}
			}
		}

		// Guard: If cache was attempted but exhausted AND there's no API pagination cursor,
		// don't fetch from the beginning (which would return duplicates of already-displayed posts).
		if ( $cache_offset >= 0 && empty( $pagination_cursor ) ) {
			wp_send_json_error( 'No more posts to load' );
		}

		// Don't over-fetch for API-based load more: items_html limits output to $item_amount
		// visible items, but the API cursor moves past ALL fetched items. Over-fetching here
		// would cause items between the rendered count and the over-fetch count to be permanently
		// lost (never shown to the user). It's acceptable to show fewer items per batch if some
		// are hidden — the user can simply click load more again.
		// Fetch likes/comments from API when the feed has them enabled (PRO)
		$show_likes    = WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'show-likes' );
		$show_comments = WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'show-comments' );
		$skip_lc       = ! $show_likes && ! $show_comments;

		$items = $this->api->get_items(
			array(
				'image-limit'          => $item_amount,
				'image-resolution'     => $image_size,
				'image-width'          => $image_width,
				'include-pagination'   => true,
				'allowed-post-types'   => $allowed_post_types,
				'pagination-cursor'    => $pagination_cursor,
				'bypass-transient'     => true, // Always get fresh data for load more
				'skip-likes-comments'  => $skip_lc,
				'access-token'         => $user_account_token,
				'feed-id'              => $feed_id,
				'business-page-id'     => $user_business_page_id,
			)
		);

		if ( ! is_array( $items ) || empty( $items['items'] ) ) {
			wp_send_json_error( 'No more posts to load' );
		}

		// Prepare args for HTML generation
		$args = array(
			'layout'                 => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'layout' ),
			'item-num'               => $item_amount,
			'col-num'                => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'col-num' ),
			'show-overlay'           => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'show-overlay' ),
			'hover-link'             => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'hover-link' ),
			'show-media-type-icons'  => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'show-media-type-icons' ),
			'hover-media-type-icons' => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'hover-media-type-icons' ),
			'hover-date'             => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'hover-date' ),
			'allowed-post-types'     => $allowed_post_types,
			'image-size'             => $image_size,
			'show-likes'             => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'show-likes' ),
			'show-comments'          => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'show-comments' ),
			'feed-id'                => $feed_id,
		);

		// When load more is triggered from the backend preview iframe, include moderate (eye) buttons
		if ( ! empty( $_POST['preview'] ) && is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			$args['preview'] = true;
		}

		// Generate HTML for new items
		$html = self::items_html( $items['items'], $args );

		// Generate lightbox content for new items if lightbox is enabled (pass $args so feed-id is available for product tags)
		$lightbox_html = '';
		$lightbox_enabled = isset( $args['lightbox'] ) ? boolval( $args['lightbox'] ) : true;
		if ( $lightbox_enabled ) {
			$lightbox_html = self::lightbox_items_html( $items['items'], $user_id, $args );
		}

		// Prepare response data
		$response = array(
			'html'         => $html,
			'lightbox_html' => $lightbox_html,
			'has_more'     => ! empty( $items['paging'] ) && property_exists( $items['paging'], 'next' ) && ! empty( $items['paging']->next ),
			'next_url'     => ! empty( $items['paging'] ) && property_exists( $items['paging'], 'next' ) ? $items['paging']->next : '',
			'from_cache'   => false,
			'cache_offset' => -1, // Cache exhausted, use API pagination from now on
		);

		wp_send_json_success( $response );
	}

	/**
	 * AJAX handler for loading more cached posts in the backend preview.
	 * This serves posts from the transient cache so users can see and link products
	 * to posts beyond the initial display count, without hitting the Instagram API.
	 *
	 * @since 2.3.0
	 * @return void
	 */
	public function ajax_preview_load_more_posts() {
		// Only admins can use preview load more
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'wpzinsta-preview-load-more' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		$feed_id    = intval( $_POST['feed_id'] );
		$offset     = intval( $_POST['offset'] );
		$amount     = intval( $_POST['amount'] );
		$image_size = sanitize_text_field( $_POST['image_size'] );

		if ( $amount < 1 ) {
			$amount = 9;
		}

		// Validate feed
		$feed_post = get_post( $feed_id );
		if ( ! $feed_post || 'wpz-insta_feed' !== $feed_post->post_type ) {
			wp_send_json_error( 'Invalid feed ID' );
		}

		// Use the account currently selected in the preview (from iframe URL / form).
		// When the user switches account without saving, POST user_id reflects the new selection;
		// prefer it so Load more fetches the correct account's posts.
		$user_id = ! empty( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		if ( $user_id < 1 ) {
			$user_id = intval( WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'user-id' ) );
		}

		$user_account_token    = get_post_meta( $user_id, '_wpz-insta_token', true );
		$user_business_page_id = get_post_meta( $user_id, '_wpz-insta_page_id', true );

		if ( empty( $user_account_token ) ) {
			wp_send_json_error( 'No valid access token found' );
		}

		// Build the transient key to read cached data
		$this->api = Wpzoom_Instagram_Widget_API::getInstance();
		$this->api->set_access_token( $user_account_token );
		$this->api->set_feed_id( $feed_id );
		if ( ! empty( $user_business_page_id ) ) {
			$this->api->set_business_page_id( $user_business_page_id );
		}

		$allowed_post_types = WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'allowed-post-types' ) ?: 'IMAGE,VIDEO,CAROUSEL_ALBUM';
		$image_width        = (int) WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'image-width' );
		$image_resolution   = ! empty( $image_size ) ? $image_size : 'standard_resolution';

		// Fetch all cached items.
		// IMPORTANT: skip-likes-comments must be false to match the transient key from the initial fetch
		// (the initial fetch uses skip-likes-comments=false, which adds '_lc' to the transient key).
		$all_items = $this->api->get_items(
			array(
				'image-limit'         => 100, // Get all cached items
				'image-resolution'    => $image_resolution,
				'image-width'         => $image_width,
				'include-pagination'  => true,
				'allowed-post-types'  => $allowed_post_types,
				'bypass-transient'    => false, // Read from cache only
				'skip-likes-comments' => false, // Must match initial fetch transient key (_lc suffix)
				'access-token'        => $user_account_token,
				'feed-id'             => $feed_id,
				'business-page-id'    => $user_business_page_id,
			)
		);

		if ( ! is_array( $all_items ) || empty( $all_items['items'] ) ) {
			wp_send_json_error( 'No cached posts available' );
		}

		// Slice to get only the items beyond the offset
		$cached_items = array_slice( $all_items['items'], $offset, $amount );

		if ( empty( $cached_items ) ) {
			wp_send_json_error( 'No more cached posts' );
		}

		// Check if there are more items in cache beyond what we're returning
		$total_cached = count( $all_items['items'] );
		$has_more     = ( $offset + $amount ) < $total_cached;

		// Ensure images use valid URLs: local thumbnails may not exist for cached items
		// that haven't been displayed before. Fall back to the Instagram CDN URL.
		foreach ( $cached_items as &$ci ) {
			if ( ! empty( $ci['image-url'] ) && ! empty( $ci['original-image-url'] ) ) {
				$local_path = self::convert_url_to_path( $ci['image-url'] );
				if ( ! file_exists( $local_path ) ) {
					$ci['image-url'] = $ci['original-image-url'];
				}
			} elseif ( empty( $ci['image-url'] ) && ! empty( $ci['original-image-url'] ) ) {
				$ci['image-url'] = $ci['original-image-url'];
			}
		}
		unset( $ci );

		// Prepare args for preview-mode HTML generation (includes "Link to a product" buttons)
		$args = array(
			'layout'                 => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'layout' ),
			'item-num'               => $amount,
			'col-num'                => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'col-num' ),
			'show-overlay'           => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'show-overlay' ),
			'hover-link'             => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'hover-link' ),
			'show-media-type-icons'  => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'show-media-type-icons' ),
			'hover-media-type-icons' => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'hover-media-type-icons' ),
			'hover-date'             => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'hover-date' ),
			'allowed-post-types'     => $allowed_post_types,
			'image-size'             => $image_size,
			'show-likes'             => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'show-likes' ),
			'show-comments'          => WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, 'show-comments' ),
			'feed-id'                => $feed_id,
			'preview'                => true,  // Preview mode: includes "Link to a product" buttons
		);

		// Generate preview-mode HTML for cached items
		$html = self::items_html( $cached_items, $args );

		wp_send_json_success( array(
			'html'     => $html,
			'has_more' => $has_more,
			'offset'   => $offset + count( $cached_items ),
		) );
	}

	/**
	 * Returns the markup for the feed with the given ID.
	 *
	 * @param  int   $feed_id     The ID of the feed to return the markup for.
	 * @param  array $extra_attrs Any extra attributes to pass for the block output.
	 * @return string             The markup for the given feed.
	 */
	public function get_feed_output( int $feed_id, array $extra_attrs = array() ) {
		if ( $feed_id > -1 ) {
			$feed = get_post( $feed_id, OBJECT, 'display' );

			if ( null !== $feed && $feed instanceof WP_Post ) {
				$user_id = intval( get_post_meta( $feed_id, '_wpz-insta_user-id', true ) );
				$feed_settings = array();

				foreach( WPZOOM_Instagram_Widget_Settings::$feed_settings as $setting_name => $setting_args ) {
					$feed_settings[ $setting_name ] = WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, $setting_name );
				}

				if ( isset( $extra_attrs ) && ! empty( $extra_attrs ) && is_array( $extra_attrs ) ) {
					$feed_settings['extra-attrs'] = $extra_attrs;
				}

				$feed_settings['feed-id'] = $feed_id;
				$feed_settings['user-id'] = $user_id;

				return $this->feed_content( $feed_settings );
			}
		}

        if ( current_user_can( 'edit_theme_options' ) ) {
			return is_admin() ? sprintf(
				'<p class="error" style="color:red"><strong>%s</strong></p>',
				esc_html__( 'There was a problem displaying the selected feed. Please check the configuration...', 'instagram-widget-by-wpzoom' )
			) : '';
    	}
	}

	/**
	 * Returns the markup for the feed shortcode.
	 *
	 * @param  array  $atts    The attributes on the shortcode.
	 * @param  string $content The content (if any) in the shortcode.
	 * @param  string $tag     The shortcode tag.
	 * @return string
	 */
	public function get_shortcode_output( array $atts, string $content, string $tag ) {
		if ( ! empty( $atts ) && is_array( $atts ) && array_key_exists( 'feed', $atts ) ) {
			$feed_id = intval( $atts['feed'] );

			if ( $feed_id > -1 ) {
				return sprintf(
					"<style type=\"text/css\">%s</style>\n%s",
					$this->output_styles( $feed_id, false ),
					$this->get_feed_output( $feed_id )
				);
			}
		}

		return is_admin() ? sprintf(
			'<p class="error" style="color:red"><strong>%s</strong></p>',
			esc_html__( 'There was a problem displaying the selected feed. Please check the configuration...', 'instagram-widget-by-wpzoom' )
		) : '';
	}

	/**
	 * Outputs the markup for the feed with the given ID.
	 *
	 * @param  int   $feed_id     The ID of the feed to output.
	 * @param  bool  $echo        Whether to output the feed or return it.
	 * @param  array $extra_attrs Any extra attributes to pass for the block output.
	 * @return void
	 */
	public function output_feed( int $feed_id, bool $echo = true, array $extra_attrs = array() ) {
		$output = sprintf(
			"<style type=\"text/css\">%s</style>\n%s",
			$this->output_styles( $feed_id, false ),
			$this->get_feed_output( $feed_id, $extra_attrs )
		);

		if ( $echo ) {
			echo $output;
		} else {
			return $output;
		}
	}

	/**
	 * Outputs the markup for the preview of a feed configured with the given arguments.
	 *
	 * @param  array $args The arguments to define how to output the feed preview.
	 * @return void
	 */
	public function output_preview( array $args ) {
		printf(
			"<style type=\"text/css\">%s</style>\n%s",
			$this->output_preview_styles( $args, false ),
			$this->feed_content( $args, true )
		);
	}

	/**
	 * Returns the markup for the preview of a feed configured with the given arguments.
	 *
	 * @param  array  $args The arguments to define how to return the feed preview.
	 * @return string
	 */
	public function get_preview( array $args ) {
		return sprintf(
			"<style type=\"text/css\">%s</style>\n%s",
			$this->output_preview_styles( $args, false ),
			$this->feed_content( $args, true )
		);
	}

	/**
	 * Check if we are in any Elementor editing context.
	 *
	 * @return bool
	 */
	public static function is_elementor_editor() {
		if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
			return false;
		}

		// Elementor preview URL parameter (most reliable check)
		if ( isset( $_GET['elementor-preview'] ) ) {
			return true;
		}

		// AJAX widget rendering in editor
		if ( wp_doing_ajax() && ( ! empty( $_REQUEST['editor_post_id'] ) || ( isset( $_REQUEST['action'] ) && 'elementor_ajax' === $_REQUEST['action'] ) ) ) {
			return true;
		}

		// Editor or preview mode via Elementor API
		if ( isset( \Elementor\Plugin::$instance->editor ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return true;
		}
		if ( isset( \Elementor\Plugin::$instance->preview ) && \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns the markup for a feed configured with the given arguments.
	 *
	 * @param  array  $args The arguments to define how to return the feed content.
	 * @return string
	 */
	private function feed_content( array $args, bool $preview = false ) {
		$this->api = Wpzoom_Instagram_Widget_API::getInstance();
		$output = '';
		$user_id = isset( $args['user-id'] ) ? intval( $args['user-id'] ) : -1;

		if( $preview ) {
			$args['preview'] = true;
		};

		if ( $user_id > 0 ) {
			$user = get_post( $user_id );

			if ( $user instanceof WP_Post ) {
				$show_user_name = isset( $args['show-account-username'] ) && boolval( $args['show-account-username'] );
				$show_user_badge = $this->is_pro && isset( $args['show-account-badge'] ) && boolval( $args['show-account-badge'] );
                $show_user_stats = $this->is_pro && isset( $args['show-account-stats'] ) && boolval( $args['show-account-stats'] );
				$show_stories = $this->is_pro && ( ! isset( $args['show-stories'] ) || boolval( $args['show-stories'] ) );
				$user_name = get_the_title( $user );
				$user_name = preg_replace( '/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $user_name );
				$user_name_display = sprintf( '@%s', $user_name );
				$user_link = 'https://www.instagram.com/' . $user_name;
				$show_user_nname = isset( $args['show-account-name'] ) && boolval( $args['show-account-name'] );
				$user_display_name = get_post_meta( $user_id, '_wpz-insta_user_name', true );
				$show_user_bio = isset( $args['show-account-bio'] ) && boolval( $args['show-account-bio'] );
				$user_bio = get_the_content( null, false, $user );
				$show_user_image = isset( $args['show-account-image'] ) && boolval( $args['show-account-image'] );
				$user_image = get_the_post_thumbnail_url( $user, 'thumbnail' ) ?: WPZOOM_INSTAGRAM_PLUGIN_URL . 'dist/images/backend/icon-insta.png';
				$user_account_token = get_post_meta( $user_id, '_wpz-insta_token', true ) ?: '-1';
				$user_business_page_id = get_post_meta( $user_id, '_wpz-insta_page_id', true ) ?: null;

				// In preview, use placeholder text for empty values so header elements can be shown/hidden via design options.
				if ( $preview ) {
					if ( '' === trim( (string) $user_display_name ) ) {
						$user_display_name = __( 'Account Name', 'instagram-widget-by-wpzoom' );
					}
					if ( '' === trim( (string) $user_name ) ) {
						$user_name_display = __( '@username', 'instagram-widget-by-wpzoom' );
						$user_link         = 'https://www.instagram.com/';
					}
					if ( '' === trim( (string) $user_bio ) ) {
						$user_bio = __( 'Preview bio text.', 'instagram-widget-by-wpzoom' );
					}
				}

				if ( '-1' !== $user_account_token ) {
					/**
					 * Filter to allow PRO plugin to handle multi-account feeds.
					 * Return non-null to bypass single-account rendering.
					 *
					 * @since 2.3.0
					 * @param string|null $output      The output markup. Return null to continue with default.
					 * @param array       $args        Feed arguments.
					 * @param int         $user_id     Primary user ID.
					 * @param bool        $preview     Whether this is a preview.
					 */
					$multi_account_output = apply_filters( 'wpz-insta_multi-account-feed-output', null, $args, $user_id, $preview );
					if ( null !== $multi_account_output ) {
						return $multi_account_output;
					}

					$attrs = '';
					$wrapper_classes = '';
					$is_editor_preview = ( defined( 'REST_REQUEST' ) && true === REST_REQUEST && 'edit' === filter_input( INPUT_GET, 'context', FILTER_SANITIZE_SPECIAL_CHARS ) )
						|| self::is_elementor_editor();
					if ( $is_editor_preview ) {
						$wrapper_classes .= ' is-editor-preview';
					}
					$layout_names = array( 0 => 'grid', 1 => 'fullwidth', 2 => 'masonry', 3 => 'carousel' );
					$raw_layout = isset( $args['layout'] ) ? intval( $args['layout'] ) : 0;
					$layout_int = $this->is_pro ? $raw_layout : ( $raw_layout > 1 ? 0 : $raw_layout );
					$layout = isset( $layout_names[ $layout_int ] ) ? $layout_names[ $layout_int ] : 'grid';
					$col_num = isset( $args['col-num'] ) && intval( $args['col-num'] ) !== 3 ? intval( $args['col-num'] ) : 3;
					$new_posts_interval_number = isset( $args['check-new-posts-interval-number'] ) ? intval( $args['check-new-posts-interval-number'] ) : 1;
					$new_posts_interval_suffix = isset( $args['check-new-posts-interval-suffix'] ) ? intval( $args['check-new-posts-interval-suffix'] ) : 1;
					$enable_request_timeout = isset( $args['enable-request-timeout'] ) ? boolval( $args['enable-request-timeout'] ) : false;
					$amount = isset( $args['item-num'] ) ? intval( $args['item-num'] ) : 9;
					$perpage = isset( $args['perpage-num'] ) ? intval( $args['perpage-num'] ) : 3;
					$perpage_num_rspnsve_enbld  = $this->is_pro && isset( $args['perpage-num_responsive-enabled'] ) ? boolval( $args['perpage-num_responsive-enabled'] ) : false;
					$perpage_table = isset( $args['perpage-num_tablet'] ) ? intval( $args['perpage-num_tablet'] ) : 2;
					$perpage_mobile = isset( $args['perpage-num_mobile'] ) ? intval( $args['perpage-num_mobile'] ) : 2;

					$spacing_between = isset( $args['spacing-between'] ) && floatval( $args['spacing-between'] ) > -1 ? floatval( $args['spacing-between'] ) : -1;
					$feat_layout_enabled = isset( $args['featured-layout-enable'] ) ? boolval( $args['featured-layout-enable'] ) : false;
					$featured_layout = $feat_layout_enabled && isset( $args['featured-layout'] ) ? intval( $args['featured-layout'] ) : 0;
					$featured_layout_class = $featured_layout > 0 ? sprintf( ' featured-layout featured-layout-%s', $featured_layout ) : '';
					$lightbox = isset( $args['lightbox'] ) ? boolval( $args['lightbox'] ) : true;
					$show_view_on_insta_button = isset( $args['show-view-button' ] ) ? boolval( $args['show-view-button' ] ) : true;
					$show_load_more_button = ( ! $this->is_pro && $preview ) || ( $this->is_pro && isset( $args['show-load-more'] ) && boolval( $args['show-load-more'] ) );
					$image_size = isset( $args['image-size'] ) && in_array( $args['image-size'], array( 'thumbnail', 'low_resolution', 'standard_resolution', 'full_resolution' ) ) ? $args['image-size'] : 'standard_resolution';
					$image_width = isset( $args['image-width'] ) ? intval( $args['image-width'] ) : 320;
					$allowed_post_types = isset( $args['allowed-post-types'] ) ? $args['allowed-post-types'] : 'IMAGE,VIDEO,CAROUSEL_ALBUM';
                    $lazy_load = isset( $args['lazy-load'] ) ? boolval( $args['lazy-load'] ) : true;

					$attrs .= ' data-layout="' . $layout . '"';

					if ( $lightbox ) {
						$attrs .= ' data-lightbox="1"';
					}

					if ( $spacing_between > -1 ) {
						$attrs .= ' data-spacing="' . $spacing_between . '"';
					}

					if ( $perpage > 0 ) {
						$attrs .= ' data-perpage="' . $perpage . '"';
					}

					if( $perpage_num_rspnsve_enbld ) {
						if( $perpage_table > 0 ) {
							$attrs .= ' data-perpage-tablet="' . $perpage_table . '"';
						}
						if( $perpage_mobile > 0 ) { 
							$attrs .= ' data-perpage-mobile="' . $perpage_mobile . '"';
						}
					}

					if ( $featured_layout > 0 ) {
						$attrs .= ' data-featured-layout="' . $featured_layout . '"';
					}

					// Instead of setting API instance state (which causes collisions), 
					// we'll pass parameters directly to get_items() call

					$wrapper_style = '';
					if ( isset( $args['feed-id'] ) ) {
						$wrapper_classes .= sprintf( ' feed-%d', intval( $args['feed-id'] ) );
						if ( class_exists( 'WooCommerce' ) && apply_filters( 'wpz-insta_is-pro', false ) ) {
							$feed_id = intval( $args['feed-id'] );
							$buy_now_bg    = get_post_meta( $feed_id, '_wpz-insta_buy-now-bg', true ) ?: get_post_meta( $feed_id, '_wpz-insta_add-to-cart-bg', true ) ?: '#111111';
							$buy_now_color = get_post_meta( $feed_id, '_wpz-insta_buy-now-color', true ) ?: get_post_meta( $feed_id, '_wpz-insta_add-to-cart-color', true ) ?: '#ffffff';
							$buy_now_hover = get_post_meta( $feed_id, '_wpz-insta_buy-now-hover-bg', true ) ?: get_post_meta( $feed_id, '_wpz-insta_add-to-cart-hover-bg', true ) ?: '#3496ff';
							$buy_now_radius_raw   = get_post_meta( $feed_id, '_wpz-insta_buy-now-border-radius', true );
							if ( '' === (string) $buy_now_radius_raw ) {
								$buy_now_radius_raw = get_post_meta( $feed_id, '_wpz-insta_add-to-cart-border-radius', true );
							}
							$buy_now_radius_suffix = get_post_meta( $feed_id, '_wpz-insta_buy-now-border-radius-suffix', true );
							if ( '' === (string) $buy_now_radius_suffix ) {
								$buy_now_radius_suffix = get_post_meta( $feed_id, '_wpz-insta_add-to-cart-border-radius-suffix', true );
							}
							if ( '' !== (string) $buy_now_radius_suffix && is_numeric( $buy_now_radius_suffix ) ) {
								$buy_now_radius_num = is_numeric( $buy_now_radius_raw ) ? (float) $buy_now_radius_raw : 3;
								$buy_now_radius     = $buy_now_radius_num . $this->get_suffix( (int) $buy_now_radius_suffix );
							} else {
								$buy_now_radius = $buy_now_radius_raw ?: '3px';
							}
							$icon_size  = get_post_meta( $feed_id, '_wpz-insta_product-icon-size', true ) ?: '36px';
							$icon_bg    = get_post_meta( $feed_id, '_wpz-insta_product-icon-bg', true ) ?: '#333333';
							$icon_color = get_post_meta( $feed_id, '_wpz-insta_product-icon-color', true ) ?: '#ffffff';
							$wrapper_style = sprintf(
								'--wpz-insta-buy-now-bg:%s;--wpz-insta-buy-now-color:%s;--wpz-insta-buy-now-hover-bg:%s;--wpz-insta-buy-now-radius:%s;--wpz-insta-icon-size:%s;--wpz-insta-icon-bg:%s;--wpz-insta-icon-color:%s;',
								esc_attr( $buy_now_bg ),
								esc_attr( $buy_now_color ),
								esc_attr( $buy_now_hover ),
								esc_attr( $buy_now_radius ),
								esc_attr( $icon_size ),
								esc_attr( $icon_bg ),
								esc_attr( $icon_color )
							);
						}
					}

					if ( isset( $args['extra-attrs'] ) && is_array( $args['extra-attrs'] ) && isset( $args['extra-attrs']['align'] ) ) {
						$align = $args['extra-attrs']['align'];
						$wrapper_classes .= sprintf( ' align%s', in_array( $align, array( 'left', 'right', 'wide', 'full' ) ) ? $align : 'center' );
					}

					// In preview use grid so frontend does not init Swiper/masonry.
					$layout_class = ( $preview && ( 'carousel' === $layout || 'masonry' === $layout ) ) ? 'grid' : $layout;
					$wrapper_classes .= sprintf( ' layout-%s', $layout_class );
					$wrapper_classes .= $featured_layout_class;
					$wrapper_classes .= ' columns-' . $col_num;

					if ( $lightbox ) {
						$wrapper_classes .= ' with-lightbox';
					}

					if ( $spacing_between > -1 ) {
						$wrapper_classes .= ' spacing-' . $spacing_between;
					}

					if ( $perpage > 0 ) {
						$wrapper_classes .= ' perpage-' . $perpage;
					}

					// Check if this is a load-more request to optimize API calls
					$is_load_more_request = isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'wpzinsta-pro-load-more' );
					
					// Override parameters with POST data for load more requests
					$pagination_cursor = '';
					if ( $is_load_more_request ) {
						if ( isset( $_POST['item_amount'] ) ) {
							$amount = intval( $_POST['item_amount'] );
						}
						if ( isset( $_POST['image_size'] ) ) {
							$image_size = sanitize_text_field( $_POST['image_size'] );
						}
						if ( isset( $_POST['allowed_post_types'] ) ) {
							$allowed_post_types = sanitize_text_field( $_POST['allowed_post_types'] );
						}
						if ( isset( $_POST['next'] ) ) {
							$next_url = sanitize_text_field( $_POST['next'] );
							// Extract cursor from URL - the 'after' parameter contains the cursor
							$parsed_url = parse_url( $next_url );
							if ( isset( $parsed_url['query'] ) ) {
								parse_str( $parsed_url['query'], $params );
								if ( isset( $params['after'] ) ) {
									$pagination_cursor = $params['after'];
								}
							}
						}
					}
					
					// Over-fetch to compensate for hidden/moderated posts (PRO only).
					$feed_id_for_api = isset( $args['feed-id'] ) ? intval( $args['feed-id'] ) : -1;
					$api_limit = $amount;
					if ( $this->is_pro && $feed_id_for_api > 0 && ! $preview ) {
						$hidden_posts_meta = get_post_meta( $feed_id_for_api, '_wpz-insta_hidden-posts', true );
						if ( is_array( $hidden_posts_meta ) && ! empty( $hidden_posts_meta ) ) {
							$api_limit = min( $amount + count( $hidden_posts_meta ), $amount * 3 );
						}
					}
					$items  = $this->api->get_items(
						array(
							'image-limit'          => $api_limit,
							'image-resolution'     => $image_size,
							'image-width'          => $image_width,
							'include-pagination'   => true,
							'allowed-post-types'   => $allowed_post_types,
							'pagination-cursor'    => $pagination_cursor,
							'bypass-transient'     => $preview && ! empty( $_GET['wpz-insta-preview-refresh'] ),
							'skip-likes-comments'  => $is_load_more_request, // Skip likes/comments for load-more to improve performance
							'access-token'         => $user_account_token,   // Pass token directly to avoid state collision
							'feed-id'             => $feed_id_for_api,
							'business-page-id'    => $user_business_page_id, // Pass business page ID directly
							'preview'              => $preview,               // Skip image uploading during preview rendering
						)
					);
					$errors = $this->api->errors->get_error_messages();

					$output .= '<div class="zoom-instagram' . $wrapper_classes . '"' . ( $wrapper_style ? ' style="' . $wrapper_style . '"' : '' ) . '>';

					if ( ! is_array( $items ) ) {
						return $this->get_errors( $errors );
					} else {
						// In preview always output header so design options (show/hide name, username, badge, etc.) can be toggled.
						if ( $preview || $show_user_image || $show_user_nname || $show_user_name || $show_user_bio ) {
							$output .= '<header class="zoom-instagram-widget__header">';

							// Get all account stats in a single API call (reduces 3 API calls to 1)
							$account_stats = array(
								'followers_count' => 0,
								'follows_count'   => 0,
								'media_count'     => 0,
							);
							if ( ! empty( $user_business_page_id ) ) {
								$account_stats = $this->api->get_account_stats( $user_business_page_id, $user_account_token );
							}
							$followers_count = $account_stats['followers_count'];
							$following_count = $account_stats['follows_count'];
							$media_count = $account_stats['media_count'];

							if ( ( $preview || $show_user_image ) && ! empty( $user_image ) ) {
								// Stories feature is only available in Pro version and when enabled in feed settings
								$stories = array();
								$has_stories = false;
								$story_ring_class = '';

								if ( $show_stories ) {
									// Get stories in a single API call (has_stories now uses cached data from get_stories)
									$stories = $this->api->get_stories( $user_business_page_id, $user_account_token );
									$has_stories = ! empty( $stories );
									$story_ring_class = $has_stories ? ' has-stories' : '';
								}

								$output .= '<div class="zoom-instagram-widget__header-column-left' . esc_attr( $story_ring_class ) . '">';

								if ( $has_stories ) {
									// Build Zuck.js compatible data structure
									$stories_data = array(
										'id'          => 'wpz-insta-' . $user_business_page_id,
										'photo'       => $user_image,
										'name'        => $user_name_display,
										'link'        => $user_link,
										'lastUpdated' => time(),
										'items'       => array(),
									);

									// Reverse order so oldest stories appear first (like Instagram)
									$stories = array_reverse( $stories );

									foreach ( $stories as $story ) {
										$is_video = isset( $story->media_type ) && 'VIDEO' === $story->media_type;
										$stories_data['items'][] = array(
											'id'       => isset( $story->id ) ? $story->id : uniqid( 'story-' ),
											'type'     => $is_video ? 'video' : 'photo',
											'src'      => $story->media_url,
											'preview'  => $is_video && ! empty( $story->thumbnail_url ) ? $story->thumbnail_url : $story->media_url,
											'length'   => $is_video ? 0 : 5, // 0 = use video duration, 5 = 5 seconds for images
											'link'     => isset( $story->permalink ) ? $story->permalink : '',
											'linkText' => __( 'View on Instagram', 'instagram-widget-by-wpzoom' ),
											'time'     => isset( $story->timestamp ) ? strtotime( $story->timestamp ) : time(),
										);
									}

									// Add aria-label for accessibility
									$aria_label = sprintf(
										/* translators: %s: username */
										esc_attr__( '%s has stories available. Click to view.', 'instagram-widget-by-wpzoom' ),
										$user_name_display
									);

									// Output clickable image with Zuck.js data
									$output .= '<div class="wpz-insta-stories" data-stories="' . esc_attr( wp_json_encode( $stories_data ) ) . '" aria-label="' . $aria_label . '" role="button" tabindex="0">';
									$output .= '<img src="' . esc_url( $user_image ) . '" alt="' . esc_attr( $user_name_display ) . '" width="70" />';
									$output .= '</div>';
								} else {
									// No stories - just show the image
									$output .= '<img src="' . esc_url( $user_image ) . '" alt="' . esc_attr( $user_name_display ) . '" width="70" />';
								}

								$output .= '</div>';
							}

							if ( $preview || $show_user_nname || $show_user_name || $show_user_bio ) {
								$output .= '<div class="zoom-instagram-widget__header-column-right">';

								if ( $preview || $show_user_nname ) {
                                    $output .= '<h5 class="zoom-instagram-widget__header-name">' . esc_html( $user_display_name ) . '</h5>';
								}

								if ( $preview || $show_user_name ) {
									$the_badge = '';
									// In preview always output badge HTML so JS can show/hide it; on frontend only if option is on.
									if ( $preview || $show_user_badge ) {
                                        $the_badge = '<span class="wpz-insta-badge"><svg width=\'24\' height=\'24\' viewBox=\'0 0 24 24\' xmlns=\'http://www.w3.org/2000/svg\' xmlns:xlink=\'http://www.w3.org/1999/xlink\'><rect width=\'24\' height=\'24\' stroke=\'none\' fill=\'#000000\' opacity=\'0\'/><g transform="matrix(0.42 0 0 0.42 12 12)" ><g style="" ><g transform="matrix(1 0 0 1 0 0)" ><polygon style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-dashoffset: 0; stroke-linejoin: miter; stroke-miterlimit: 4; fill: #0095f6; fill-rule: nonzero; opacity: 1;" points="5.62,-21 9.05,-15.69 15.37,-15.38 15.69,-9.06 21,-5.63 18.12,0 21,5.62 15.69,9.05 15.38,15.37 9.06,15.69 5.63,21 0,18.12 -5.62,21 -9.05,15.69 -15.37,15.38 -15.69,9.06 -21,5.63 -18.12,0 -21,-5.62 -15.69,-9.05 -15.38,-15.37 -9.06,-15.69 -5.63,-21 0,-18.12 " /></g><g transform="matrix(1 0 0 1 -0.01 0.51)" ><polygon style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-dashoffset: 0; stroke-linejoin: miter; stroke-miterlimit: 4; fill: rgb(255,255,255); fill-rule: nonzero; opacity: 1;" points="-2.6,6.74 -9.09,0.25 -6.97,-1.87 -2.56,2.53 7,-6.74 9.09,-4.59 " /></g></g></g></svg></span>';									}
                                    $output .= '<p class="zoom-instagram-widget__header-user"><a href="' . esc_url( $user_link ) . '" target="_blank" rel="nofollow">' . esc_html( $user_name_display ) . '</a>' . $the_badge . '</p>';
								}


                                if ( $preview || $show_user_stats ) {

    								// Add all three counts in a stats wrapper (in preview show even when 0 so design option can be toggled)
    								if ( $preview || $followers_count > 0 || $following_count > 0 || $media_count > 0 ) {
    									$output .= '<div class="wpz-insta-stats">';

    									if ( $preview || $media_count > 0 ) {
    										$output .= '<div class="wpz-insta-posts">';
    										$output .= '<strong>' . self::format_number( $media_count ) . '</strong> ';
    										$output .= esc_html__( 'posts', 'instagram-widget-by-wpzoom' );
    										$output .= '</div>';
    									}

    									if ( $preview || $followers_count > 0 ) {
    										$output .= '<div class="wpz-insta-followers">';
    										$output .= '<strong>' . self::format_number( $followers_count ) . '</strong> ';
    										$output .= esc_html__( 'followers', 'instagram-widget-by-wpzoom' );
    										$output .= '</div>';
    									}

    									if ( $preview || $following_count > 0 ) {
    										$output .= '<div class="wpz-insta-following">';
    										$output .= '<strong>' . self::format_number( $following_count ) . '</strong> ';
    										$output .= esc_html__( 'following', 'instagram-widget-by-wpzoom' );
    										$output .= '</div>';
    									}

    									$output .= '</div>';
    								}
                                }

								if ( $preview || $show_user_bio ) {
                                    $output .= '<div class="zoom-instagram-widget__header-bio">' . esc_html( $user_bio ) . '</div>';
								}

								$output .= '</div>';
							}

							$output .= '</header>';
						}

						// In preview use grid layout class so frontend does not init Swiper/masonry (scripts not enqueued).
						$classes = 'zoom-instagram-widget__items zoom-instagram-widget__items--no-js' . sprintf( ' layout-%s', $layout_class );

						// In preview, skip carousel/masonry markup (preview shows grid/static; no Swiper/masonry scripts).
						if ( $this->is_pro && 'carousel' === $layout && ! $preview ) {
							$classes .= ' swiper-wrapper';
						}

						$wrapper_swiper_class = ( $this->is_pro && 'carousel' === $layout && ! $preview ) ? ' swiper' : '';
						$masonry_sizer         = ( $this->is_pro && 'masonry' === $layout && ! $preview ) ? '<li class="masonry-items-sizer"></li>' : '';
						$items_consumed_count = 0;
						$output .= '<div class="zoom-instagram-widget__items-wrapper' . $wrapper_swiper_class . '"><ul class="' . $classes . '"' . $attrs . '>' . $masonry_sizer;
						$output .= self::items_html( $items['items'], $args, $items_consumed_count );
						$output .= '</ul>';
						if ( $this->is_pro && 'carousel' === $layout && ! $preview ) {
							$output .= '<div class="swiper-button-prev"></div><div class="swiper-button-next"></div>';
						}
						$output .= '</div>';

						if ( $show_view_on_insta_button || $show_load_more_button ) {
							$output .= '<div class="zoom-instagram-widget__footer">';

							if ( $show_view_on_insta_button ) {
								$view_on_insta_label = isset( $args['view-button-text'] ) ? trim( $args['view-button-text'] ) : __( 'View on Instagram', 'instagram-widget-by-wpzoom' );
								$output .= '<a href="' . esc_url( $user_link ) . '" target="_blank" rel="noopener nofollow" class="wpz-button wpz-button-primary wpz-insta-view-on-insta-button">';
								$output .= '<span class="button-icon zoom-svg-instagram-stroke"></span> ';
								$output .= esc_html( $view_on_insta_label );
								$output .= '</a>';
							}

							// Always output Load more in DOM when option is on (hidden via CSS for fullwidth/carousel).
							if ( $show_load_more_button ) {
								$next_url = ! empty( $items ) && array_key_exists( 'paging', $items ) && is_object( $items['paging'] ) && property_exists( $items['paging'], 'next' ) ? $items['paging']->next : '';
								// Use items_consumed_count (actual items processed by items_html) instead of
								// total fetched count, so load more doesn't skip unprocessed items from the over-fetch gap.
								$initial_display_count = $items_consumed_count > 0 ? $items_consumed_count : ( is_array( $items ) && ! empty( $items['items'] ) ? count( $items['items'] ) : 0 );
								// Has more: either more cached items (we fetch ~30 initially) or more from API.
								// Use total_items (pre-truncation count from processing_response_data) to detect
								// items beyond what get_items() returned (which is limited to $api_limit).
								$total_cached = isset( $items['total_items'] ) ? intval( $items['total_items'] ) : ( is_array( $items ) && ! empty( $items['items'] ) ? count( $items['items'] ) : 0 );
								$has_more_in_cache = $initial_display_count > 0 && $initial_display_count < $total_cached;
								$has_more = $has_more_in_cache || ! empty( $next_url );

								// New fast button-based AJAX system (always generate for performance)
								$output .= '<div class="wpzinsta-pro-load-more-wrapper"' . ( ! $this->is_pro ? ' data-disabled="true"' : '' ) . '>';
								$output .= '<button type="button" class="wpzinsta-pro-load-more-btn"' .
										   ' data-feed-id="' . esc_attr( isset( $args['feed-id'] ) ? intval( $args['feed-id'] ) : -1 ) . '"' .
										   ' data-item-amount="' . esc_attr( $amount ) . '"' .
										   ' data-image-size="' . esc_attr( $image_size ) . '"' .
										   ' data-allowed-post-types="' . esc_attr( $allowed_post_types ) . '"' .
										   ' data-next-url="' . esc_attr( $next_url ) . '"' .
										   ' data-nonce="' . wp_create_nonce( 'wpzinsta-pro-load-more' ) . '"' .
										   ' data-cache-offset="' . esc_attr( $initial_display_count ) . '"' .
										   ( ! $has_more ? ' disabled style="display:none;"' : '' ) .
										   ( ! $this->is_pro ? ' disabled' : '' ) . '>';
								$output .= '<span class="button-text">' . esc_html( ( isset( $args['load-more-text'] ) ? trim( $args['load-more-text'] ) : __( 'Load More&hellip;', 'instagram-widget-by-wpzoom' ) ) . ( ! $this->is_pro ? __( ' [PRO only]', 'instagram-widget-by-wpzoom' ) : '' ) ) . '</span>';
								$output .= '</button>';
								$output .= '</div>';
								
								// Legacy form system for PRO version compatibility (hidden, PRO JavaScript expects this)
								if ( $this->is_pro ) {
									$feed_id_for_form = isset( $args['feed-id'] ) ? intval( $args['feed-id'] ) : -1;
									$output .= '<form method="post" action="" class="wpzinsta-pro-load-more" style="display:none;" data-feed-id="' . esc_attr( $feed_id_for_form ) . '">';
									$output .= '<input type="hidden" name="feed_id" value="' . esc_attr( $feed_id_for_form ) . '" />';
									$output .= '<input type="hidden" name="item_amount" value="' . esc_attr( $amount ) . '" />';
									$output .= '<input type="hidden" name="image_size" value="' . esc_attr( $image_size ) . '" />';
									$output .= '<input type="hidden" name="allowed_post_types" value="' . esc_attr( $allowed_post_types ) . '" />';
									$output .= '<input type="hidden" name="next" value="' . esc_attr( $next_url ) . '" />';
									$output .= wp_nonce_field( 'wpzinsta-pro-load-more', '_wpnonce', true, false );
									$output .= '<button type="submit" style="display:none;">Load More (Hidden)</button>';
									$output .= '</form>';
								}
							}

							$output .= '</div>';
						}

						if ( $lightbox ) {
							$output .= '<div class="wpz-insta-lightbox-wrapper mfp-hide"><div class="swiper"><div class="swiper-wrapper">';
							$output .= self::lightbox_items_html( $items['items'], $user_id, $args );
							$output .= '</div><div class="swiper-button-prev"></div><div class="swiper-button-next"></div></div></div>';
						}
					}

					$output .= '</div>';

					return $output;
				}
			}
		}

		if ( $preview ) {
			return sprintf(
				'<div class="zoom-instagram"><p class="select-a-feed">%s%s</p></div>',
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M20 10.8H6.7l4.1-4.5-1.1-1.1-5.8 6.3 5.8 5.8 1.1-1.1-4-3.9H20z" fill="currentColor" stroke="currentColor" stroke-width="1.5"/></svg>',
				__( 'Please select an account in the panel to the left&hellip;', 'instagram-widget-by-wpzoom' )
			);
		} else {
			return '';
		}
	}

	/**
	 * Returns the markup for the given feed items, configured with the given arguments.
	 *
	 * @param  array  $items The items to generate the markup for.
	 * @param  array  $args  The arguments to define how to return the feed items.
	 * @return string        The markup for the given feed items, empty string otherwise.
	 */
	public static function items_html( $items, $args, &$items_consumed = null ) {
		$output = '';
		$items_consumed = 0;

		if ( ! empty( $items ) && is_array( $items ) ) {
			$is_editor = ( defined( 'REST_REQUEST' ) && true === REST_REQUEST && 'edit' === filter_input( INPUT_GET, 'context', FILTER_SANITIZE_SPECIAL_CHARS ) )
				|| self::is_elementor_editor();
			$count = 0;
			$layout = isset( $args['layout'] ) ? intval( $args['layout'] ) : 0;
			$amount = isset( $args['item-num'] ) ? intval( $args['item-num'] ) : 9;
			$col_num = isset( $args['col-num'] ) && intval( $args['col-num'] ) !== 3 ? intval( $args['col-num'] ) : 3;
			$show_overlay = isset( $args['show-overlay'] ) ? boolval( $args['show-overlay'] ) : true;
            $show_insta_icon = isset( $args['hover-link'] ) ? boolval( $args['hover-link'] ) : true;
			$show_media_type_icons = isset( $args['show-media-type-icons'] ) ? boolval( $args['show-media-type-icons'] ) : true;
			$show_media_type_icons_on_hover = isset( $args['hover-media-type-icons'] ) ? boolval( $args['hover-media-type-icons'] ) : true;
			$show_date_on_hover = isset( $args['hover-date'] ) ? boolval( $args['hover-date'] ) : true;
			$allowed_post_types = isset( $args['allowed-post-types'] ) ? $args['allowed-post-types'] : 'IMAGE,VIDEO,CAROUSEL_ALBUM';
			$image_size = isset( $args['image-size'] ) && in_array( $args['image-size'], array( 'thumbnail', 'low_resolution', 'standard_resolution', 'full_resolution' ) ) ? $args['image-size'] : 'standard_resolution';
			$small_class = $image_size <= 180 ? 'small' : '';
			$svg_icons = WPZOOM_INSTAGRAM_PLUGIN_URL . 'dist/images/frontend/wpzoom-instagram-icons.svg';
			$preview = isset( $args['preview'] ) ? true : false;

			$show_likes    = self::$instance->is_pro && isset( $args['show-likes'] ) && boolval( $args['show-likes'] );
			$show_comments = self::$instance->is_pro && isset( $args['show-comments'] ) && boolval( $args['show-comments'] );

			// Get hidden posts for this feed (PRO only — used in both preview and frontend)
			$feed_id = isset( $args['feed-id'] ) ? intval( $args['feed-id'] ) : 0;
			$hidden_posts = array();
			if ( self::$instance->is_pro && $feed_id > 0 ) {
				$hidden_posts_meta = get_post_meta( $feed_id, '_wpz-insta_hidden-posts', true );
				if ( is_array( $hidden_posts_meta ) ) {
					$hidden_posts = $hidden_posts_meta;
				}
			}

			foreach ( $items as $item ) {
				$items_consumed++;

				$inline_attrs  = '';
				$overwrite_src = false;
				$link          = isset( $item['link'] ) ? $item['link'] : '';
				$src           = isset( $item['image-url'] ) ? $item['image-url'] : '';
				$media_id      = isset( $item['image-id'] ) ? $item['image-id'] : '';
				$alt           = isset( $item['image-caption'] ) ? esc_attr( $item['image-caption'] ) : '';
				$typ           = isset( $item['type'] ) ? strtolower( $item['type'] ) : 'image';
				$type          = in_array( $typ, array( 'video', 'carousel_album' ) ) ? $typ : false;
				$is_album      = 'carousel_album' == $type;
				$is_video      = 'video' == $type;
				$likes         = isset( $item['likes'] ) ? intval( $item['likes'] ) : 0;
				$comments      = isset( $item['comments'] ) ? intval( $item['comments'] ) : 0;

				// On the frontend (non-preview), skip hidden posts
				$is_post_hidden = ! empty( $media_id ) && in_array( $media_id, $hidden_posts, true );
				if ( ! $preview && $is_post_hidden ) {
					continue;
				}

				// Post type filtering is now handled in the API layer

				if ( ! empty( $media_id ) && empty( $src ) ) {
					$inline_attrs  = 'data-media-id="' . esc_attr( $media_id ) . '"';
					$inline_attrs .= 'data-nonce="' . wp_create_nonce( WPZOOM_Instagram_Image_Uploader::get_nonce_action( $media_id ) ) . '"';
					$overwrite_src = true;
				}

				if (
					! empty( $media_id ) &&
					! empty( $src ) &&
					! file_exists( self::convert_url_to_path( $src ) )
				) {
					$inline_attrs  = 'data-media-id="' . esc_attr( $media_id ) . '"';
					$inline_attrs .= 'data-nonce="' . wp_create_nonce( WPZOOM_Instagram_Image_Uploader::get_nonce_action( $media_id ) ) . '"';
					$inline_attrs .= 'data-regenerate-thumbnails="1"';
					//$overwrite_src = true;
				}

				$inline_attrs .= 'data-media-type="' . esc_attr( $type ?: 'image' ) . '"';

				if ( $overwrite_src ) {
					$src = $item['original-image-url'];
				}

				$width = 100;
				$height = 100;
				if ( ! empty( $src ) ) {
					$local = self::attachment_url_to_path( $src );
					$image_size = @wp_getimagesize( false !== $local ? $local : $src );

					if ( false !== $image_size ) {
						$width = $image_size[0];
						$height = $image_size[1];
					}
				}

				$classes = '';

                if ( $show_media_type_icons ) {
                    $classes .= ' media-icons-normal';
                }

				if ( $show_media_type_icons_on_hover ) {
					$classes .= ' media-icons-hover';
				}

				if ( $show_date_on_hover ) {
					$classes .= ' date-hover';
				}

			// In preview, add hidden class for posts that are hidden
				if ( $preview && $is_post_hidden ) {
					$classes .= ' wpz-insta-post-hidden';
				}

				if ( self::$instance->is_pro && 3 === $layout && ! $preview ) {
					$classes .= ' swiper-slide';
				}

				// Add has-linked-products class if the item has linked products
				$feed_id_for_links = isset( $args['feed-id'] ) ? intval( $args['feed-id'] ) : 0;
				if ( $feed_id_for_links > 0 && class_exists( 'WooCommerce' ) && ! empty( $media_id ) ) {
					$linked_ids_for_class = self::get_linked_product_ids( $feed_id_for_links, $media_id );
					if ( ! empty( $linked_ids_for_class ) ) {
						$classes .= ' has-linked-products';
					}
				}

				$src_attr = $is_editor ? sprintf( 'src="%s"', esc_url( $src ) ) : '';

				if ( $is_editor || $preview ) {
					$classes .= ' wpz-insta-loaded';
				}

				$output .= '<li class="zoom-instagram-widget__item' . $classes . '" ' . $inline_attrs . '><div class="zoom-instagram-widget__item-inner-wrap">';

				// Add moderate (eye) button in backend preview (PRO only)
				if ( $preview && ! empty( $media_id ) && self::$instance->is_pro ) {
					$eye_title = $is_post_hidden ? __( 'Show post', 'instagram-widget-by-wpzoom' ) : __( 'Hide post', 'instagram-widget-by-wpzoom' );
					if ( $is_post_hidden ) {
						// Eye-off icon (post is hidden)
						$eye_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
					} else {
						// Eye icon (post is visible)
						$eye_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
					}
					$output .= '<button type="button" class="wpz-insta-moderate-btn" data-media-id="' . esc_attr( $media_id ) . '" title="' . esc_attr( $eye_title ) . '">';
					$output .= $eye_svg;
					$output .= '</button>';
				}

                if ( self::$instance->is_pro && 2 === $layout ) {

                    $output .= sprintf( '<img class="zoom-instagram-link" %5$s src="%1$s" width="%2$d" height="%3$d" alt="%4$s" />', esc_url( $src ), esc_attr( $width ), esc_attr( $height ), esc_attr( $alt ), $src_attr );

                } else {

					if( $preview ) {
						$output .= sprintf( '<img class="zoom-instagram-link zoom-instagram-link-new" %5$s src="%1$s" width="%2$d" height="%3$d" alt="%4$s" />', esc_url( $src ), esc_attr( $width ), esc_attr( $height ), esc_attr( $alt ), $src_attr );
					}
					else {
						$output .= sprintf( '<img class="zoom-instagram-link zoom-instagram-link-new" %5$s data-src="%1$s" data-mfp-src="%6$s" width="%2$d" height="%3$d" alt="%4$s" />', esc_url( $src ), esc_attr( $width ), esc_attr( $height ), esc_attr( $alt ), $src_attr,  $media_id );
					}

                }


				if ( $show_overlay ) {
					$output .= '<div class="hover-layout zoom-instagram-widget__overlay zoom-instagram-widget__black ' . $small_class . '">';

					if ( ( $show_media_type_icons || $show_media_type_icons_on_hover ) && ! empty( $type ) ) {
						$output .= '<svg class="svg-icon" shape-rendering="geometricPrecision"><use xlink:href="' . esc_url( $svg_icons ) . '#' . $type . '"></use></svg>';
					}

					if ( $show_likes && ! empty( $likes ) || $show_comments && ! empty( $comments ) ) {
						$output .= '<a class="zoom-instagram-link" data-src="' . $src . '" data-mfp-src="' . $media_id . '" href="' . $link . '" target="_blank" rel="noopener nofollow" title="' . $alt . '">';
						$output .= '<div class="hover-controls">';
							if ( $show_likes && ! empty( $likes ) ) {
								$output .= '<span class="zoom-instagram-icon icon-heart-outline"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 25 25" id="heart-outline">
  <path fill="none" stroke="#ffffff" stroke-width="2" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path>
</svg></span>';
								$output .= '<span class="counter">' . self::format_number( $likes ) . '</span>';
							}
							if ( $show_comments && ! empty( $comments ) ) {
								$output .= '<span class="zoom-instagram-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 28" id="comment">
  <path fill="#ffffff" d="M25.784 21.017A10.992 10.992 0 0 0 27 16c0-6.065-4.935-11-11-11S5 9.935 5 16s4.935 11 11 11c1.742 0 3.468-.419 5.018-1.215l4.74 1.185a.996.996 0 0 0 .949-.263 1 1 0 0 0 .263-.95l-1.186-4.74zm-2.033.11.874 3.498-3.498-.875a1.006 1.006 0 0 0-.731.098A8.99 8.99 0 0 1 16 25c-4.963 0-9-4.038-9-9s4.037-9 9-9 9 4.038 9 9a8.997 8.997 0 0 1-1.151 4.395.995.995 0 0 0-.098.732z"></path>
</svg>
</span>';
								$output .= '<span class="counter">' . self::format_number( $comments ) . '</span>';
							}
						$output .= '</div>';
						$output .= '</a>';
					}

					if ( $show_date_on_hover && isset( $item['timestamp'] ) ) {
						$output .= '<div class="zoom-instagram-date">' . sprintf( _x( '%1$s ago', '%2$s = human-readable time difference', 'instagram-widget-by-wpzoom' ), human_time_diff( strtotime( $item['timestamp'] ), current_time( 'timestamp' ) ) ) . '</div>';
					}

                    if ( ! empty ( $show_insta_icon ) ) {
						if( ( ! $show_likes || empty( $likes ) ) && ( ! $show_comments || empty( $comments ) ) ) {
    						$output .= '<div class="zoom-instagram-icon-wrap"><a class="zoom-svg-instagram-stroke" href="' . $link . '" rel="noopener nofollow" target="_blank" title="' . $alt . '"></a></div>';
						}

    					$output .= '<a class="zoom-instagram-link" data-src="' . $src . '" data-mfp-src="' . $media_id . '" href="' . $link . '" target="_blank" rel="noopener nofollow" title="' . $alt . '"></a>';
                    }
					$output .= '</div>'; // Close .zoom-instagram-widget__overlay
				} else {
					$output .= '<a class="zoom-instagram-link" data-src="' . $src . '" data-mfp-src="' . $media_id . '" href="' . $link . '" target="_blank" rel="noopener nofollow" title="' . $alt . '">';

					if ( ( $show_media_type_icons || $show_media_type_icons_on_hover ) && ! empty( $type ) ) {
						$output .= '<svg class="svg-icon" shape-rendering="geometricPrecision"><use xlink:href="' . esc_url( $svg_icons ) . '#' . $type . '"></use></svg>';
					}

					$output .= '</a>';
				}

				// Add "Link to a product" button in backend preview if WooCommerce is installed
				if ( $preview && class_exists( 'WooCommerce' ) && ! empty( $media_id ) ) {
					$feed_id = isset( $args['feed-id'] ) ? intval( $args['feed-id'] ) : 0;
					$linked_product_id = self::get_linked_product_id( $feed_id, $media_id );
					$is_linked = $linked_product_id > 0;
					$button_text = $is_linked ? __( 'Edit Product Link', 'instagram-widget-by-wpzoom' ) : __( 'Link to a product', 'instagram-widget-by-wpzoom' );
					$button_class = 'wpz-insta-link-product-btn' . ( $is_linked ? ' wpz-insta-link-product-btn--linked' : '' );
					$icon_class  = $is_linked ? 'dashicons-edit' : 'dashicons-cart';
					$output .= '<button type="button" class="' . esc_attr( $button_class ) . '" data-media-id="' . esc_attr( $media_id ) . '" data-feed-id="' . esc_attr( $feed_id ) . '" data-product-id="' . esc_attr( $linked_product_id ) . '" title="' . esc_attr( $button_text ) . '">';
					$output .= '<span class="dashicons ' . esc_attr( $icon_class ) . '"></span> ' . esc_html( $button_text );
					$output .= '</button>';

					if ( $is_linked > 0 ) {
						// Get badge label from settings (default: "Product")
						$preview_badge_label = get_post_meta( $feed_id, '_wpz-insta_product-badge-label', true );
						if ( false === $preview_badge_label || '' === $preview_badge_label ) {
							$preview_badge_label = __( 'Product', 'instagram-widget-by-wpzoom' );
						}
						if ( '' !== $preview_badge_label ) {
							$output .= '<span class="wpz-insta-product-badge"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 6v.75H5.513c-.96 0-1.764.724-1.865 1.679l-1.263 12A1.875 1.875 0 0 0 4.25 22.5h15.5a1.875 1.875 0 0 0 1.865-2.071l-1.263-12a1.875 1.875 0 0 0-1.865-1.679H16.5V6a4.5 4.5 0 1 0-9 0ZM12 3a3 3 0 0 0-3 3v.75h6V6a3 3 0 0 0-3-3Zm-3 8.25a3 3 0 1 0 6 0v-.75a.75.75 0 0 1 1.5 0v.75a4.5 4.5 0 1 1-9 0v-.75a.75.75 0 0 1 1.5 0v.75Z" clip-rule="evenodd"/></svg>' . esc_html( $preview_badge_label ) . '</span>';
						}
					}
				}

				$output .= '</div>';

				// Add product badge/icon+popover or "Buy now" button (link to product) in frontend (PRO + valid license only)
				if ( ! $preview && class_exists( 'WooCommerce' ) && ! empty( $media_id ) && apply_filters( 'wpz-insta_is-pro', false ) ) {
					$feed_id = isset( $args['feed-id'] ) ? intval( $args['feed-id'] ) : 0;
					$linked_ids = self::get_linked_product_ids( $feed_id, $media_id );
					if ( ! empty( $linked_ids ) ) {
							$display_type = get_post_meta( $feed_id, '_wpz-insta_product-links-display-type', true ) ?: 'icon';
							$icon_position = get_post_meta( $feed_id, '_wpz-insta_product-links-icon-position', true ) ?: 'bottom-right';
							$popover_title = get_post_meta( $feed_id, '_wpz-insta_product-links-popover-title', true ) ?: __( 'Related products', 'instagram-widget-by-wpzoom' );
							// Get the "open in new tab" setting for all product links
							$product_links_new_tab = ( '1' === get_post_meta( $feed_id, '_wpz-insta_buy-now-new-tab', true ) );
							$product_links_rel     = $product_links_new_tab ? 'noopener noreferrer' : 'noopener';
							$product_links_target  = $product_links_new_tab ? ' target="_blank"' : '';

							// Display badge for all display types (if label is not empty)
							$badge_label = get_post_meta( $feed_id, '_wpz-insta_product-badge-label', true );
							// Use "Product" as default if not set
							if ( false === $badge_label || '' === $badge_label ) {
								$badge_label = __( 'Product', 'instagram-widget-by-wpzoom' );
							}
							if ( '' !== $badge_label ) {
								$output .= '<span class="wpz-insta-product-badge"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 6v.75H5.513c-.96 0-1.764.724-1.865 1.679l-1.263 12A1.875 1.875 0 0 0 4.25 22.5h15.5a1.875 1.875 0 0 0 1.865-2.071l-1.263-12a1.875 1.875 0 0 0-1.865-1.679H16.5V6a4.5 4.5 0 1 0-9 0ZM12 3a3 3 0 0 0-3 3v.75h6V6a3 3 0 0 0-3-3Zm-3 8.25a3 3 0 1 0 6 0v-.75a.75.75 0 0 1 1.5 0v.75a4.5 4.5 0 1 1-9 0v-.75a.75.75 0 0 1 1.5 0v.75Z" clip-rule="evenodd"/></svg>' . esc_html( $badge_label ) . '</span>';
							}

							if ( 'icon' === $display_type ) {
								$output .= '<div class="wpz-insta-product-icon-wrap wpz-insta-product-icon-position-' . esc_attr( $icon_position ) . '">';
								$output .= '<span class="wpz-insta-product-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 6v.75H5.513c-.96 0-1.764.724-1.865 1.679l-1.263 12A1.875 1.875 0 0 0 4.25 22.5h15.5a1.875 1.875 0 0 0 1.865-2.071l-1.263-12a1.875 1.875 0 0 0-1.865-1.679H16.5V6a4.5 4.5 0 1 0-9 0ZM12 3a3 3 0 0 0-3 3v.75h6V6a3 3 0 0 0-3-3Zm-3 8.25a3 3 0 1 0 6 0v-.75a.75.75 0 0 1 1.5 0v.75a4.5 4.5 0 1 1-9 0v-.75a.75.75 0 0 1 1.5 0v.75Z" clip-rule="evenodd"/></svg></span>';
								$output .= '<div class="wpz-insta-product-popover">';
								$output .= '<div class="wpz-insta-product-popover-wrapper">';
								$output .= '<div class="wpz-insta-product-popover-title">' . esc_html( $popover_title ) . '</div>';
								$output .= '<ul class="wpz-insta-product-popover-list">';
								foreach ( $linked_ids as $pid ) {
									$product = wc_get_product( $pid );
									if ( ! $product || ! $product->is_visible() ) {
										continue;
									}
									$product_link = get_permalink( $pid );
									$product_title = $product->get_name();
									$product_price = $product->get_price_html();
									$product_image_id = $product->get_image_id();
									$product_image_url = $product_image_id ? wp_get_attachment_image_url( $product_image_id, 'woocommerce_thumbnail' ) : wc_placeholder_img_src( 'woocommerce_thumbnail' );
									$chevron_svg = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor" aria-hidden="true"><path d="M504-480 348-636q-11-11-11-28t11-28q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L404-268q-11 11-28 11t-28-11q-11-11-11-28t11-28l156-156Z"/></svg>';
									$output .= '<li class="wpz-insta-product-popover-item">';
									$output .= '<a href="' . esc_url( $product_link ) . '" class="wpz-insta-product-popover-item-link" rel="' . esc_attr( $product_links_rel ) . '"' . $product_links_target . '>';
									$output .= '<img class="wpz-insta-product-popover-item-img" src="' . esc_url( $product_image_url ) . '" alt="' . esc_attr( $product_title ) . '" loading="lazy" />';
									$output .= '<div class="wpz-insta-product-popover-item-info">';
									$output .= '<span class="wpz-insta-product-popover-item-title">' . esc_html( $product_title ) . '</span>';
									$output .= '<span class="wpz-insta-product-popover-item-price">' . $product_price . '</span>';
									$output .= '</div>';
									$output .= '<span class="wpz-insta-product-popover-item-chevron">' . $chevron_svg . '</span>';
									$output .= '</a>';
									$output .= '</li>';
								}
								$output .= '</ul></div></div></div>';
							} else {
								$buy_now_text = get_post_meta( $feed_id, '_wpz-insta_buy-now-text', true ) ?: __( 'Buy now', 'instagram-widget-by-wpzoom' );
								$buy_now_new_tab = ( '1' === get_post_meta( $feed_id, '_wpz-insta_buy-now-new-tab', true ) );
								$buy_now_rel     = $buy_now_new_tab ? 'noopener noreferrer' : 'noopener';
								$buy_now_target  = $buy_now_new_tab ? ' target="_blank"' : '';
								foreach ( $linked_ids as $linked_product_id ) {
									$product = wc_get_product( $linked_product_id );
									if ( $product && $product->is_visible() ) {
										$product_link = get_permalink( $linked_product_id );
										$output .= '<div class="wpz-insta-buy-now-wrapper">';
										$output .= '<a href="' . esc_url( $product_link ) . '" class="wpz-insta-buy-now-btn button" data-product-id="' . esc_attr( $linked_product_id ) . '" rel="' . esc_attr( $buy_now_rel ) . '"' . $buy_now_target . '>' . esc_html( $buy_now_text ) . '</a>';
										$output .= '</div>';
										break;
									}
								}
							}
					}
				}

				$output .= '</li>';

				if ( ++ $count === $amount ) {
					break;
				}
			}
		}

		return $output;
	}

	/**
	 * Returns the lightbox markup for the given feed items.
	 *
	 * @param  array  $items    The items to generate the markup for.
	 * @param  int    $user_id  The ID of the user to disaply in the user info area.
	 * @param  array  $args    Optional. Feed args including feed-id for product links.
	 * @return string           The lightbox markup for the given feed items, empty string otherwise.
	 */
	public static function lightbox_items_html( $items, $user_id, $args = array() ) {
		$output = '';

		if ( ! empty( $items ) && is_array( $items ) ) {
			$user = get_post( $user_id );
			$feed_id = isset( $args['feed-id'] ) ? intval( $args['feed-id'] ) : 0;
			$preview = isset( $args['preview'] ) ? true : false;

			// Get hidden posts to skip in lightbox (must match items_html filtering)
			$hidden_posts = array();
			if ( self::$instance->is_pro && $feed_id > 0 ) {
				$hidden_posts_meta = get_post_meta( $feed_id, '_wpz-insta_hidden-posts', true );
				if ( is_array( $hidden_posts_meta ) ) {
					$hidden_posts = $hidden_posts_meta;
				}
			}

			if ( $user instanceof WP_Post ) {
				$amount = count( $items );
				$count = 0;
				$user_name = get_the_title( $user );
				$user_name_display = sprintf( '@%s', $user_name );
				$user_image = get_the_post_thumbnail_url( $user, 'thumbnail' ) ?: WPZOOM_INSTAGRAM_PLUGIN_URL . 'dist/images/backend/icon-insta.png';

				foreach ( $items as $item ) {
					$link     = isset( $item['link'] ) ? $item['link'] : '';
					$src      = isset( $item['original-image-url'] ) ? $item['original-image-url'] : '';
					$src_local = isset( $item['local-image-url'] ) ? $item['local-image-url'] : '';
					$media_id = isset( $item['image-id'] ) ? $item['image-id'] : '';

					// Skip hidden posts in lightbox (matches items_html filtering)
					if ( ! $preview && ! empty( $media_id ) && in_array( $media_id, $hidden_posts, true ) ) {
						continue;
					}

					$count++;
					$alt      = isset( $item['image-caption'] ) ? esc_attr( $item['image-caption'] ) : '';
					$typ      = isset( $item['type'] ) ? strtolower( $item['type'] ) : 'image';
					$type     = in_array( $typ, array( 'video', 'carousel_album' ) ) ? $typ : false;
					$is_album = 'carousel_album' == $type;
					$is_video = 'video' == $type;
					// Handle both data structures: $item['children']->data (legacy) and $item['children'] (direct)
					$children = false;
					if ( $is_album && isset( $item['children'] ) ) {
						if ( is_object( $item['children'] ) && isset( $item['children']->data ) ) {
							// Legacy structure: children wrapped in data property
							$children = $item['children']->data;
						} elseif ( is_object( $item['children'] ) && property_exists( $item['children'], 'data' ) ) {
							// Alternative data property check
							$children = $item['children']->data;
						} elseif ( is_array( $item['children'] ) || ( is_object( $item['children'] ) && ! property_exists( $item['children'], 'data' ) ) ) {
							// Direct structure: children are the data itself
							$children = $item['children'];
						}

						if ( $children ) {
							$children_count = is_array( $children ) ? count( $children ) : ( is_object( $children ) && property_exists( $children, 'data' ) ? count( $children->data ) : 1 );
						}
					}

					$output .= '<div data-uid="' . $media_id . '" class="swiper-slide wpz-insta-lightbox-item"><div class="wpz-insta-lightbox"><div class="image-wrapper">';

					if ( $is_album && false !== $children ) {
						$output .= '<div class="swiper" style="height: 100%;"><div class="swiper-wrapper wpz-insta-album-images">';

						foreach ( $children as $child ) {
							$child_type = property_exists( $child, 'media_type' ) && in_array( $child->media_type, array( 'VIDEO', 'CAROUSEL_ALBUM' ) ) ? strtolower( $child->media_type ) : 'image';
							$thumb = 'video' == $child_type && property_exists( $child, 'thumbnail_url' ) ? $child->thumbnail_url : '';
							$output .= '<div class="swiper-slide wpz-insta-album-image" data-media-type="' . esc_attr( $child_type ) . '">';
							if ( 'video' == $child_type ) {
								$output .= '<video controls muted preload="none"><source src="' . esc_url( $child->media_url ) . '" type="video/mp4"/></video>';
							} else {
								$output .= '<img class="wpzoom-swiper-image swiper-lazy" data-src="' . esc_url( $child->media_url ) . '" alt="' . esc_attr( $alt ) . '"/><div class="swiper-lazy-preloader swiper-lazy-preloader-white"></div>';
							}

							$output .= '</div>';
						}

						$output .= '</div><div class="swiper-pagination"></div><div class="swiper-button-prev"></div><div class="swiper-button-next"></div></div>';
					} else {
						
						if ( $is_video ) {

							$thumb = isset( $item['local-image-url'] ) ? $item['local-image-url'] : '';

							if( ! self::is_video_url( $src ) ) {
								$src = isset( $item['local-image-url'] ) ? $item['local-image-url'] : '';
								$output .= '<img class="wpzoom-swiper-image swiper-lazy blurred" data-src="' . esc_url( $src_local ) . '" alt="' . esc_attr( $alt ) . '"/>';
								$output .= '<div class="wpz-no-reel-link-wrapper"><a class="wpz-no-reel-link" target="_blank" href="' . esc_url( $link ) . '"><span class="dashicons dashicons-controls-play"></span>' . esc_html__( 'View Reel on Instagram', 'instagram-widget-by-wpzoom' ) . '</a></div>';
							} else {
								$output .= '<video controls muted preload="none"><source src="' . esc_url( $src ) . '" type="video/mp4"/></video>';
							}

						} else {
							$output .= '<img class="wpzoom-swiper-image swiper-lazy" data-src="' . esc_url( $src_local ) . '" alt="' . esc_attr( $alt ) . '"/><div class="swiper-lazy-preloader swiper-lazy-preloader-white"></div>';
						}
					}

					// Add product tag markers inside image-wrapper (for tagged products)
					$product_tags_html = self::get_lightbox_product_tags_html( $feed_id, $media_id );
					if ( ! empty( $product_tags_html ) ) {
						$output .= $product_tags_html;
					}

					$output .= '</div>
					<div class="details-wrapper">';

					// Build product block HTML for lightbox (will be inserted based on position setting)
					$product_block_html = self::get_lightbox_product_block_html( $feed_id, $media_id );

					// Get lightbox product position setting (default: top)
					$lightbox_product_position = $feed_id > 0 ? ( get_post_meta( $feed_id, '_wpz-insta_lightbox-product-position', true ) ?: 'top' ) : 'top';

					// Output product block at top position (before header)
					if ( 'top' === $lightbox_product_position && ! empty( $product_block_html ) ) {
						$output .= $product_block_html;
					}

					$output .= '
					<div class="wpz-insta-header">
						<div class="wpz-insta-avatar">
							<img src="' . esc_url( $user_image ) . '" alt="' . esc_attr( $user_name_display ) . '" width="42" height="42"/>
						</div>
						<div class="wpz-insta-buttons">
							<div class="wpz-insta-username">
								<a rel="noopener" target="_blank" href="' . sprintf( 'https://instagram.com/%s', esc_attr( $user_name ) ) . '">' . esc_html( $user_name_display ) . '</a>
							</div>
							<div>&bull;</div>
							<div class="wpz-insta-follow">
								<a target="_blank" rel="noopener"
								href="' . sprintf( 'https://instagram.com/%s?ref=badge', esc_attr( $user_name ) ) . '">
									' . __( 'Follow', 'instagram-widget-by-wpzoom' ) . '
								</a>
							</div>
						</div>
					</div>';

					// Output product block before caption position
					if ( 'before-caption' === $lightbox_product_position && ! empty( $product_block_html ) ) {
						$output .= $product_block_html;
					}

					if ( ! empty( $item['image-caption'] ) ) {
						$output .= '<div class="wpz-insta-caption">' . self::filter_caption( $item['image-caption'] ) . '</div>';
					}

					if ( ! empty( $item['timestamp'] ) ) {
						$output .= '<div class="wpz-insta-date">' . sprintf( __( '%s ago', 'instagram-widget-by-wpzoom' ), human_time_diff( strtotime( $item['timestamp'] ) ) ) . '</div>';
					}

					// Add likes and comments counts
					$likes    = isset( $item['likes'] ) ? intval( $item['likes'] ) : 0;
					$comments = isset( $item['comments'] ) ? intval( $item['comments'] ) : 0;
					if ( $likes > 0 || $comments > 0 ) {
						$output .= '<div class="wpz-insta-counts">';
						if ( $likes > 0 ) {
							$output .= '<span class="wpz-insta-likes"><svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 25 25" id="heart-outline"><path fill="none" stroke="#c0c7ca" stroke-width="2" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path></svg>' . self::format_number( $likes ) . '</span>';
						}
						if ( $comments > 0 ) {
							$output .= '<span class="wpz-insta-comments"><svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 28" id="comment"><path fill="#c0c7ca" d="M25.784 21.017A10.992 10.992 0 0 0 27 16c0-6.065-4.935-11-11-11S5 9.935 5 16s4.935 11 11 11c1.742 0 3.468-.419 5.018-1.215l4.74 1.185a.996.996 0 0 0 .949-.263 1 1 0 0 0 .263-.95l-1.186-4.74zm-2.033.11.874 3.498-3.498-.875a1.006 1.006 0 0 0-.731.098A8.99 8.99 0 0 1 16 25c-4.963 0-9-4.038-9-9s4.037-9 9-9 9 4.038 9 9a8.997 8.997 0 0 1-1.151 4.395.995.995 0 0 0-.098.732z"></path></svg>' . self::format_number( $comments ) . '</span>';
						}
						$output .= '</div>';
					}

					// Output product block at footer position (after date/counts)
					if ( 'footer' === $lightbox_product_position && ! empty( $product_block_html ) ) {
						$output .= $product_block_html;
					}

					$output .= '<div class="view-post">
					<a href="' . esc_url( $link ) . '" target="_blank" rel="noopener"><span class="dashicons dashicons-instagram"></span>' . __( 'View on Instagram', 'instagram-widget-by-wpzoom' ) . '</a>
					<span class="delimiter">|</span>
					<div class="wpz-insta-pagination">' . sprintf( '%d/%d', $count, $amount ) . '</div>
					</div></div></div></div>';
				}
			}
		}

		return $output;
	}

	// Check if the URL is a video URL
	protected static function is_video_url( $url ) {
		return preg_match( '/(\.mp4|\.mov|video_dash|video_dashinit|\/vs=|\/o1\/v\/t16\/)/i' , $url );
	}

	/**
	 * Return errors if widget is misconfigured and current user can manage options (plugin settings).
	 *
	 * @return void
	 */
	protected function get_errors( $errors ) {
		$output = '';

		if ( current_user_can( 'edit_theme_options' ) ) {
			$output .= sprintf(
				'<p style="font-size: 12px; font-weight: 500;">%s <strong><a href="%s" target="_blank">%s</a></strong> %s</p>',
				__( '[Admin-only Notice] Instagram Widget misconfigured or your Access Token <strong>expired</strong>. Please check', 'instagram-widget-by-wpzoom' ),
				admin_url( 'edit.php?post_type=wpz-insta_user' ),
				__( 'Instagram Settings Page', 'instagram-widget-by-wpzoom' ),
				__( 'and reconnect your account.', 'instagram-widget-by-wpzoom' )
			);

			if ( ! empty( $errors ) ) {
				$output .= '<ul style="font-size: 12px; font-weight: 500;">';

				foreach ( $errors as $error ) {
					$output .= '<li>' . esc_html( $error ) . '</li>';
				}

				$output .= '</ul>';
			}
		}
		return $output;
	}

	/**
	 * Returns the CSS markup for a feed configured with the given arguments.
	 *
	 * @param  array  $args The arguments to define how to return the feed CSS.
	 * @return string
	 */
	public function style_content( array $args ) {
		$output                 = '';
		$is_preview             = ! empty( $args['preview'] );
		$feed_id                = isset( $args['feed-id'] ) ? ".feed-" . $args['feed-id'] : "";
		$raw_layout             = isset( $args['layout'] ) ? intval( $args['layout'] ) : 0;
		$layout                 = $this->is_pro ? $raw_layout : ( $raw_layout > 1 ? 0 : $raw_layout );
        $feed_items_num         = isset( $args['item-num'] ) ? ( intval( $args['item-num'] ) ?: 10 ) : 10;
        $col_num                = isset( $args['col-num'] ) && intval( $args['col-num'] ) !== 3 ? intval( $args['col-num'] ) : 3;
		$col_num_tablet         = isset( $args['col-num_tablet'] ) && intval( $args['col-num_tablet'] ) !== 2 ? intval( $args['col-num_tablet'] ) : 2;
		$col_num_mobile         = isset( $args['col-num_mobile'] ) && intval( $args['col-num_mobile'] ) !== 1 ? intval( $args['col-num_mobile'] ) : 1;
		$col_num_rspnsve_enbld  = $this->is_pro && isset( $args['col-num_responsive-enabled'] ) ? boolval( $args['col-num_responsive-enabled'] ) : false;
		$spacing_between        = isset( $args['spacing-between'] ) && floatval( $args['spacing-between'] ) > -1 ? floatval( $args['spacing-between'] ) : -1;
		$spacing_between_suffix = $this->get_suffix( isset( $args['spacing-between-suffix'] ) ? intval( $args['spacing-between-suffix'] ) : 0 );
		$feat_layout_enabled    = isset( $args['featured-layout-enable'] ) ? boolval( $args['featured-layout-enable'] ) : false;
		$featured_layout        = isset( $args['featured-layout'] ) ? intval( $args['featured-layout'] ) : 0;
		$image_aspect_ratio     = isset( $args['image-aspect-ratio'] ) ? $args['image-aspect-ratio'] : 'square';
		$button_bg              = isset( $args['view-button-bg-color'] ) ? $this->validate_color( $args['view-button-bg-color'] ) : '';
        $loadmore_bg            = isset( $args['load-more-color'] ) ? $this->validate_color( $args['load-more-color'] ) : '';
		$bg_color               = isset( $args['bg-color'] ) ? $this->validate_color( $args['bg-color'] ) : '';
		$border_radius          = isset( $args['border-radius'] ) ? ( intval( $args['border-radius'] ) ?: -1 ) : -1;
		$border_radius_suffix   = $this->get_suffix( isset( $args['border-radius-suffix'] ) ? intval( $args['border-radius-suffix'] ) : 0 );
		$spacing_around         = isset( $args['spacing-around'] ) ? ( intval( $args['spacing-around'] ) ?: -1 ) : -1;
		$spacing_around_suffix  = $this->get_suffix( isset( $args['spacing-around-suffix'] ) ? intval( $args['spacing-around-suffix'] ) : 0 );
		$font_size              = isset( $args['font-size'] ) ? ( intval( $args['font-size'] ) ?: -1 ) : -1;
		$font_size_suffix       = $this->get_suffix( isset( $args['font-size-suffix'] ) ? intval( $args['font-size-suffix'] ) : 0 );
		$image_width            = isset( $args['image-width'] ) ? ( intval( $args['image-width'] ) ?: 240 ) : 240;
		$image_width_suffix     = $this->get_suffix( isset( $args['image-width-suffix'] ) ? intval( $args['image-width-suffix'] ) : 0 );
		$hover_likes            = isset( $args['hover-likes'] ) ? boolval( $args['hover-likes'] ) : true;
		$hover_link             = isset( $args['hover-link'] ) ? boolval( $args['hover-link'] ) : true;
		$hover_caption          = isset( $args['hover-caption'] ) ? boolval( $args['hover-caption'] ) : false;
		$hover_username         = isset( $args['hover-username'] ) ? boolval( $args['hover-username'] ) : false;
		$hover_date             = isset( $args['hover-date'] ) ? boolval( $args['hover-date'] ) : false;
		$hover_text_color       = isset( $args['hover-text-color'] ) ? $this->validate_color( $args['hover-text-color'] ) : '';
		$hover_bg_color         = isset( $args['hover-bg-color'] ) ? $this->validate_color( $args['hover-bg-color'] ) : '';

		if ( $font_size > -1 || ! empty( $bg_color ) || $spacing_around > -1 ) {
			$output .= ".zoom-instagram{$feed_id}{";

			if ( $font_size > -1 ) {
				$output .= "font-size:{$font_size}{$font_size_suffix}!important;";
			}

			if ( ! empty( $bg_color ) ) {
				$output .= "background-color:{$bg_color}!important;";
			}

			if ( $spacing_around > -1 ) {
				$output .= "padding:{$spacing_around}{$spacing_around_suffix}!important;";
			}

			$output .= "}";
		}
		
		if ( 0 === $layout || ( 2 === $layout && $is_preview ) ) {
			$output .= ".zoom-instagram{$feed_id} .zoom-instagram-widget__items{";
			$output .= "display:grid!important;";
			$output .= "grid-template-columns:repeat({$col_num},1fr);";
			$output .= "}";
		}

		if ( ( 0 === $layout || 1 === $layout || ( 2 === $layout && $is_preview ) ) && $spacing_between > -1 ) {
			$output .= ".zoom-instagram{$feed_id} .zoom-instagram-widget__items{";
			$output .= "gap:{$spacing_between}{$spacing_between_suffix}!important;";
			$output .= "}";
		}

		if ( $this->is_pro ) {
			if ( 2 === $layout && ! $is_preview ) {
				$output .= ".zoom-instagram{$feed_id} .zoom-instagram-widget__item,.zoom-instagram{$feed_id} .masonry-items-sizer{";
				$output .= "width:calc(1/{$col_num}*100%" . ( $spacing_between > 0 ? " - (1 - 1/{$col_num})*{$spacing_between}{$spacing_between_suffix}" : "" ) . ")!important;";
				$output .= "}";

				if ( $spacing_between > -1 ) {
					$output .= ".zoom-instagram{$feed_id} .zoom-instagram-widget__item{";
					$output .= "margin:0 0 {$spacing_between}{$spacing_between_suffix}!important;";
					$output .= "}";
				}

				// CSS columns fallback for masonry in editor previews (where JS masonry doesn't run)
				// :not(.masonry-active) ensures this steps aside if JS masonry initializes (e.g. in Elementor)
				$output .= ".zoom-instagram{$feed_id}.is-editor-preview:not(.masonry-active) .zoom-instagram-widget__items{";
				$output .= "display:block!important;column-count:{$col_num}!important;";
				if ( $spacing_between > -1 ) {
					$output .= "column-gap:{$spacing_between}{$spacing_between_suffix}!important;";
				}
				$output .= "}";
				$output .= ".zoom-instagram{$feed_id}.is-editor-preview:not(.masonry-active) .zoom-instagram-widget__item{";
				$output .= "break-inside:avoid!important;width:100%!important;";
				$output .= "}";
				$output .= ".zoom-instagram{$feed_id}.is-editor-preview:not(.masonry-active) .masonry-items-sizer{display:none!important;}";
			}

			if ( $border_radius > -1 ) {
				$output .= ".zoom-instagram{$feed_id} .zoom-instagram-widget__item .zoom-instagram-widget__item-inner-wrap{";
				$output .= "border-radius:{$border_radius}{$border_radius_suffix}!important;";
				$output .= "}";
			}

			if ( '' != $loadmore_bg ) {
				// Target both old form-based button and new AJAX button
				$output .= ".zoom-instagram{$feed_id} .wpzinsta-pro-load-more button[type=submit],";
				$output .= ".zoom-instagram{$feed_id} .zoom-instagram-widget__footer .wpzinsta-pro-load-more-wrapper .wpzinsta-pro-load-more-btn{";
				$output .= "background-color:{$loadmore_bg}!important;";
				$output .= "}";
			}
		}

		if ( $image_width > -1 && 1 === $layout ) {
			$output .= ".zoom-instagram{$feed_id} .zoom-instagram-widget__items img {";
			$output .= "width:{$image_width}{$image_width_suffix}";
			$output .= "}";
		}

		if ( $image_width > -1 && 1 === $layout ) {
			$output .= ".zoom-instagram{$feed_id} .zoom-instagram-widget__items.layout-fullwidth{";
			$output .= "grid-template-columns:repeat({$feed_items_num},1fr);";
			$output .= "}";
		}

		if ( '' != $button_bg ) {
			$output .= ".zoom-instagram{$feed_id} .wpz-insta-view-on-insta-button{";
			$output .= "background-color:{$button_bg}!important;";
			$output .= "}";
		}

		// Add aspect ratio CSS for grid layout
		if ( ( 0 === $layout || 2 === $layout ) && 'portrait' === $image_aspect_ratio ) {
			$output .= ".zoom-instagram{$feed_id} .zoom-instagram-widget__items.layout-grid .zoom-instagram-widget__item img{";
			$output .= "aspect-ratio:3/4!important;";
			$output .= "}";
		}

		if ( $col_num_rspnsve_enbld ) {
			$output .= "@media screen and (min-width:1200px){";
			if ( 2 === $layout ) {
				$output .= ".zoom-instagram{$feed_id} .zoom-instagram-widget__item,.zoom-instagram{$feed_id} .masonry-items-sizer{";
				$output .= "width:calc(1/{$col_num}*100%" . ( $spacing_between > 0 ? " - (1 - 1/{$col_num})*{$spacing_between}{$spacing_between_suffix}" : "" ) . ")!important;";
				$output .= "}";
			} else {
				$output .= ".zoom-instagram{$feed_id} .zoom-instagram-widget__items{";
				$output .= "grid-template-columns:repeat({$col_num},1fr);";
				$output .= "}";
			}
			$output .= "}";

			$output .= "@media screen and (max-width:768px){";
			if ( 2 === $layout ) {
				$output .= ".zoom-instagram{$feed_id} .zoom-instagram-widget__item,.zoom-instagram{$feed_id} .masonry-items-sizer{";
				$output .= "width:calc(1/{$col_num_tablet}*100%" . ( $spacing_between > 0 ? " - (1 - 1/{$col_num_tablet})*{$spacing_between}{$spacing_between_suffix}" : "" ) . ")!important;";
				$output .= "}";
			} else {
				$output .= ".zoom-instagram{$feed_id} .zoom-instagram-widget__items{";
				$output .= "grid-template-columns:repeat({$col_num_tablet},1fr);";
				$output .= "}";
				$output .= ".zoom-instagram{$feed_id} .zoom-instagram-widget__item{";
				$output .= "grid-row:auto!important;grid-column:auto!important;";
				$output .= "}";
			}
			$output .= "}";

			$output .= "@media screen and (max-width:480px){";
			if ( 2 === $layout ) {
				$output .= ".zoom-instagram{$feed_id} .zoom-instagram-widget__item,.zoom-instagram{$feed_id} .masonry-items-sizer{";
				$output .= "width:calc(1/{$col_num_mobile}*100%" . ( $spacing_between > 0 ? " - (1 - 1/{$col_num_mobile})*{$spacing_between}{$spacing_between_suffix}" : "" ) . ")!important;";
				$output .= "}";
			} else {
				$output .= ".zoom-instagram{$feed_id} .zoom-instagram-widget__items{";
				$output .= "grid-template-columns:repeat({$col_num_mobile},1fr);";
				$output .= "}";
			}
			$output .= "}";
		}
	

		return trim( $output );
	}

	/**
	 * Outputs the CSS styles for the feed with the given ID.
	 *
	 * @param  int  $feed_id The ID of the feed to output the styles for.
	 * @param  bool $echo    Whether to output the styles (default) or return them.
	 * @return void
	 */
	public function output_styles( int $feed_id, bool $echo = true ) {
		$output = '';

		if ( $feed_id > -1 ) {
			$feed = get_post( $feed_id, OBJECT, 'display' );

			if ( null !== $feed && $feed instanceof WP_Post ) {
				$args = WPZOOM_Instagram_Widget_Settings::get_all_feed_settings_values( $feed_id );
				$args['feed-id'] = $feed_id;
				$output = $this->style_content( $args );
			}
		}

		if ( $echo ) {
			echo $output;
		} else {
			return $output;
		}
	}

	/**
	 * Outputs the CSS styles for the preview of a feed configured with the given arguments.
	 *
	 * @param  array $args The arguments to define how to output the feed preview CSS.
	 * @param  bool  $echo Whether to output the preview (default) or return it.
	 * @return void
	 */
	public function output_preview_styles( array $args, bool $echo = true ) {
		$args['preview'] = true;
		$output = $this->style_content( $args );

		if ( $echo ) {
			echo $output;
		} else {
			return $output;
		}
	}

	/**
	 * Returns a suffix string (e.g. px, em, etc) from the given index.
	 *
	 * @param  int    $index The index to get the suffix value for.
	 * @return string        The suffix value as a string.
	 */
	public function get_suffix( int $index ) {
		return 2 === $index ? '%' : ( 1 === $index ? 'em' : 'px' );
	}

	/**
	 * Returns a validated color value.
	 *
	 * @param  string $color The raw color string to validate.
	 * @return string        The validated color string.
	 */
	function validate_color( string $color ) {
		return preg_match( '/^(\#[\da-f]{3}|\#[\da-f]{6}|rgba\(((\d{1,2}|1\d\d|2([0-4]\d|5[0-5]))\s*,\s*){2}((\d{1,2}|1\d\d|2([0-4]\d|5[0-5]))\s*)(,\s*(0\.\d+|1))\)|hsla\(\s*((\d{1,2}|[1-2]\d{2}|3([0-5]\d|60)))\s*,\s*((\d{1,2}|100)\s*%)\s*,\s*((\d{1,2}|100)\s*%)(,\s*(0\.\d+|1))\)|rgb\(((\d{1,2}|1\d\d|2([0-4]\d|5[0-5]))\s*,\s*){2}((\d{1,2}|1\d\d|2([0-4]\d|5[0-5]))\s*)|hsl\(\s*((\d{1,2}|[1-2]\d{2}|3([0-5]\d|60)))\s*,\s*((\d{1,2}|100)\s*%)\s*,\s*((\d{1,2}|100)\s*%)\))$/i', $color ) ? $color : '';
	}

	/**
	 * Formats a number for display.
	 *
	 * @param  int    $num The number to format.
	 * @return string      The formatted number in a string.
	 */
	public static function format_number( int $num ) {
		if ( $num < 10000 ) {
			return number_format( $num );
		}

		$units = array( '', 'k', 'm', 'b', 't' );
		for ( $i = 0; $num >= 1000; $i ++ ) {
			$num /= 1000;
		}

		return round( $num, 1 ) . $units[ $i ];
	}

	/**
	 * Convert $url to file path.
	 *
	 * @param  string          $url
	 * @return string|string[]
	 */
	public static function convert_url_to_path( string $url ) {
		return str_replace(
			wp_get_upload_dir()['baseurl'],
			wp_get_upload_dir()['basedir'],
			$url
		);
	}

	/**
	 * Convert attachment $url to file path.
	 *
	 * @param  string       $url
	 * @return string|false
	 */
	public static function attachment_url_to_path( string $url ) {
		$parsed_url = parse_url( $url );

		if ( empty( $parsed_url['path'] ) ) {
			return false;
		}

		$file = ABSPATH . ltrim( $parsed_url['path'], '/' );

		if ( file_exists( $file ) ) {
			return $file;
		}

		return false;
	}

	/**
	 * Sanitizes and prepares caption content for display.
	 * 
	 * @param  string $caption The raw caption text to filter.
	 * @return string          The filtered caption text.
	 */
	public static function filter_caption( string $caption = '' ) {
		if ( ! empty( $caption ) ) {
			$filters = array(
				'wp_kses_post',
				'autoembed',
				'wptexturize',
				'wpautop',
				'wp_filter_content_tags',
				'capital_P_dangit',
				'convert_chars',
				'convert_smilies',
				'force_balance_tags',
			);

			foreach ( $filters as $filter ) {
				$caption = apply_filters( $filter, $caption );
			}
		}

		return trim( $caption );
	}

	/**
	 * Get the first linked WooCommerce product ID for an Instagram media item.
	 *
	 * @param int    $feed_id  The feed post ID.
	 * @param string $media_id The Instagram media ID.
	 * @return int The product ID, or 0 if not linked.
	 */
	public static function get_linked_product_id( $feed_id, $media_id ) {
		$ids = self::get_linked_product_ids( $feed_id, $media_id );
		return ! empty( $ids ) ? (int) $ids[0] : 0;
	}

	/**
	 * Get linked WooCommerce product IDs for an Instagram media item.
	 *
	 * @param int    $feed_id  The feed post ID.
	 * @param string $media_id The Instagram media ID.
	 * @return int[] Array of product IDs (empty if none linked).
	 */
	public static function get_linked_product_ids( $feed_id, $media_id ) {
		$products = self::get_linked_products( $feed_id, $media_id );
		return array_map( function( $p ) {
			return $p['id'];
		}, $products );
	}

	/**
	 * Build the lightbox linked-products block HTML (list or card layout with buy-now styling).
	 * Used by the free plugin lightbox and by the Pro plugin for consistent output and position support.
	 *
	 * @param int    $feed_id  The feed post ID.
	 * @param string $media_id The Instagram media ID.
	 * @return string HTML fragment, or empty string if no linked products or not Pro/WooCommerce.
	 */
	public static function get_lightbox_product_block_html( $feed_id, $media_id ) {
		if ( $feed_id <= 0 || ! class_exists( 'WooCommerce' ) || empty( $media_id ) || ! apply_filters( 'wpz-insta_is-pro', false ) ) {
			return '';
		}
		$linked_ids = self::get_linked_product_ids( $feed_id, $media_id );
		if ( empty( $linked_ids ) ) {
			return '';
		}

		$lightbox_product_layout = get_post_meta( $feed_id, '_wpz-insta_lightbox-product-layout', true ) ?: 'list';
		$buy_now_text_lt        = get_post_meta( $feed_id, '_wpz-insta_buy-now-text', true ) ?: __( 'Buy now', 'instagram-widget-by-wpzoom' );
		$buy_now_new_tab_lt     = ( '1' === get_post_meta( $feed_id, '_wpz-insta_buy-now-new-tab', true ) );
		$buy_now_rel_lt         = $buy_now_new_tab_lt ? 'noopener noreferrer' : 'noopener';
		$buy_now_target_lt      = $buy_now_new_tab_lt ? ' target="_blank"' : '';
		$buy_now_bg_lt          = get_post_meta( $feed_id, '_wpz-insta_buy-now-bg', true ) ?: get_post_meta( $feed_id, '_wpz-insta_add-to-cart-bg', true ) ?: '#111111';
		$buy_now_color_lt       = get_post_meta( $feed_id, '_wpz-insta_buy-now-color', true ) ?: get_post_meta( $feed_id, '_wpz-insta_add-to-cart-color', true ) ?: '#ffffff';
		$buy_now_hover_lt       = get_post_meta( $feed_id, '_wpz-insta_buy-now-hover-bg', true ) ?: get_post_meta( $feed_id, '_wpz-insta_add-to-cart-hover-bg', true ) ?: '#3496ff';
		$buy_now_radius_raw_lt  = get_post_meta( $feed_id, '_wpz-insta_buy-now-border-radius', true );
		if ( '' === (string) $buy_now_radius_raw_lt ) {
			$buy_now_radius_raw_lt = get_post_meta( $feed_id, '_wpz-insta_add-to-cart-border-radius', true );
		}
		$buy_now_radius_suffix_lt = get_post_meta( $feed_id, '_wpz-insta_buy-now-border-radius-suffix', true );
		if ( '' === (string) $buy_now_radius_suffix_lt ) {
			$buy_now_radius_suffix_lt = get_post_meta( $feed_id, '_wpz-insta_add-to-cart-border-radius-suffix', true );
		}
		if ( '' !== (string) $buy_now_radius_suffix_lt && is_numeric( $buy_now_radius_suffix_lt ) ) {
			$buy_now_radius_num_lt     = is_numeric( $buy_now_radius_raw_lt ) ? (float) $buy_now_radius_raw_lt : 3;
			$buy_now_radius_suffix_str = ( 2 === (int) $buy_now_radius_suffix_lt ) ? '%' : ( ( 1 === (int) $buy_now_radius_suffix_lt ) ? 'em' : 'px' );
			$buy_now_radius_lt         = $buy_now_radius_num_lt . $buy_now_radius_suffix_str;
		} else {
			$buy_now_radius_lt = $buy_now_radius_raw_lt ?: '3px';
		}
		$lightbox_buy_now_style = sprintf(
			'background-color:%s;color:%s;border-radius:%s;',
			esc_attr( $buy_now_bg_lt ),
			esc_attr( $buy_now_color_lt ),
			esc_attr( $buy_now_radius_lt )
		);

		$product_block_html = '';
		if ( 'card' === $lightbox_product_layout ) {
			$is_carousel = count( $linked_ids ) > 1;
			$product_block_html .= '<div class="wpz-insta-lightbox-product wpz-insta-lightbox-product--card' . ( $is_carousel ? ' wpz-insta-lightbox-product--carousel' : '' ) . '" style="--wpz-insta-buy-now-hover:' . esc_attr( $buy_now_hover_lt ) . '">';
			if ( $is_carousel ) {
				$product_block_html .= '<div class="wpz-insta-lightbox-product__carousel">';
				$product_block_html .= '<div class="wpz-insta-lightbox-product__carousel-inner">';
			}
			foreach ( $linked_ids as $linked_product_id ) {
				$product = wc_get_product( $linked_product_id );
				if ( ! $product || ! $product->is_visible() ) {
					continue;
				}
				$product_link     = get_permalink( $linked_product_id );
				$product_title    = $product->get_name();
				$product_price    = $product->get_price_html();
				$product_image_id = $product->get_image_id();
				$product_image_url = $product_image_id ? wp_get_attachment_image_url( $product_image_id, 'woocommerce_thumbnail' ) : wc_placeholder_img_src( 'woocommerce_thumbnail' );
				$product_block_html .= '<div class="wpz-insta-lightbox-product__card">';
				$product_block_html .= '<a href="' . esc_url( $product_link ) . '" class="wpz-insta-lightbox-product__card-link" rel="noopener"' . $buy_now_target_lt . '>';
				$product_block_html .= '<img class="wpz-insta-lightbox-product__card-img" src="' . esc_url( $product_image_url ) . '" alt="' . esc_attr( $product_title ) . '" loading="lazy"/>';
				$product_block_html .= '</a>';
				$product_block_html .= '<div class="wpz-insta-lightbox-product__card-info">';
				$product_block_html .= '<span class="wpz-insta-lightbox-product__card-title">' . esc_html( $product_title ) . '</span>';
				$product_block_html .= '<span class="wpz-insta-lightbox-product__card-price">' . $product_price . '</span>';
				$product_block_html .= '</div>';
				$product_block_html .= '<a href="' . esc_url( $product_link ) . '" class="wpz-insta-lightbox-product__card-button button" style="' . $lightbox_buy_now_style . '" data-product-id="' . esc_attr( $linked_product_id ) . '" rel="' . esc_attr( $buy_now_rel_lt ) . '"' . $buy_now_target_lt . '>' . esc_html( $buy_now_text_lt ) . '</a>';
				$product_block_html .= '</div>';
			}
			if ( $is_carousel ) {
				$product_block_html .= '</div>';
				$product_block_html .= '<button type="button" class="wpz-insta-lightbox-product__carousel-prev" aria-label="' . esc_attr__( 'Previous product', 'instagram-widget-by-wpzoom' ) . '"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>';
				$product_block_html .= '<button type="button" class="wpz-insta-lightbox-product__carousel-next" aria-label="' . esc_attr__( 'Next product', 'instagram-widget-by-wpzoom' ) . '"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>';
				$product_block_html .= '<div class="wpz-insta-lightbox-product__carousel-dots"></div>';
				$product_block_html .= '</div>';
			}
			$product_block_html .= '</div>';
		} else {
			$product_block_html .= '<div class="wpz-insta-lightbox-product" style="--wpz-insta-buy-now-hover:' . esc_attr( $buy_now_hover_lt ) . '">';
			foreach ( $linked_ids as $linked_product_id ) {
				$product = wc_get_product( $linked_product_id );
				if ( ! $product || ! $product->is_visible() ) {
					continue;
				}
				$product_link      = get_permalink( $linked_product_id );
				$product_title     = $product->get_name();
				$product_price     = $product->get_price_html();
				$product_image_id  = $product->get_image_id();
				$product_image_url = $product_image_id ? wp_get_attachment_image_url( $product_image_id, 'woocommerce_thumbnail' ) : wc_placeholder_img_src( 'woocommerce_thumbnail' );
				$product_block_html .= '<div class="wpz-insta-lightbox-product__item">';
				$product_block_html .= '<a href="' . esc_url( $product_link ) . '" class="wpz-insta-lightbox-product__link" rel="' . esc_attr( $buy_now_rel_lt ) . '"' . $buy_now_target_lt . '>';
				$product_block_html .= '<img class="wpz-insta-lightbox-product__img" src="' . esc_url( $product_image_url ) . '" alt="' . esc_attr( $product_title ) . '" loading="lazy"/>';
				$product_block_html .= '<div class="wpz-insta-lightbox-product__info">';
				$product_block_html .= '<span class="wpz-insta-lightbox-product__title">' . esc_html( $product_title ) . '</span>';
				$product_block_html .= '<span class="wpz-insta-lightbox-product__price">' . $product_price . '</span>';
				$product_block_html .= '</div></a>';
				$product_block_html .= '<a href="' . esc_url( $product_link ) . '" class="wpz-insta-lightbox-product__buy-now button" style="' . $lightbox_buy_now_style . '" data-product-id="' . esc_attr( $linked_product_id ) . '" rel="' . esc_attr( $buy_now_rel_lt ) . '"' . $buy_now_target_lt . '>' . esc_html( $buy_now_text_lt ) . '</a>';
				$product_block_html .= '</div>';
			}
			$product_block_html .= '</div>';
		}
		return $product_block_html;
	}

	/**
	 * Build the lightbox product tag markers HTML (dots + popovers on the image).
	 * Used by the free plugin lightbox and by the Pro plugin for consistent output.
	 *
	 * @param int    $feed_id  The feed post ID.
	 * @param string $media_id The Instagram media ID.
	 * @return string HTML fragment (wrapper div with tags), or empty string if no tagged products or not Pro/WooCommerce.
	 */
	public static function get_lightbox_product_tags_html( $feed_id, $media_id ) {
		if ( $feed_id <= 0 || ! class_exists( 'WooCommerce' ) || empty( $media_id ) || ! apply_filters( 'wpz-insta_is-pro', false ) ) {
			return '';
		}
		$linked_products_for_tags = self::get_linked_products( $feed_id, $media_id );
		$tags_html = '';
		foreach ( $linked_products_for_tags as $linked_product ) {
			if ( ! empty( $linked_product['tag'] ) && isset( $linked_product['tag']['x'] ) && isset( $linked_product['tag']['y'] ) ) {
				$product_for_tag = wc_get_product( $linked_product['id'] );
				if ( $product_for_tag && $product_for_tag->is_visible() ) {
					$tag_x = floatval( $linked_product['tag']['x'] );
					$tag_y = floatval( $linked_product['tag']['y'] );
					$tag_album_index = isset( $linked_product['tag']['album_index'] ) && $linked_product['tag']['album_index'] !== null ? intval( $linked_product['tag']['album_index'] ) : -1;
					$product_title_tag = $product_for_tag->get_name();
					$product_price_tag = $product_for_tag->get_price_html();
					$product_link_tag = get_permalink( $linked_product['id'] );
					$product_image_id_tag = $product_for_tag->get_image_id();
					$product_image_url_tag = $product_image_id_tag ? wp_get_attachment_image_url( $product_image_id_tag, 'thumbnail' ) : wc_placeholder_img_src( 'thumbnail' );

					$tags_html .= '<div class="wpz-insta-lightbox-tag" data-album-index="' . esc_attr( $tag_album_index ) . '" style="left:' . esc_attr( $tag_x ) . '%;top:' . esc_attr( $tag_y ) . '%;">';
					$tags_html .= '<span class="wpz-insta-lightbox-tag__dot"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 6v.75H5.513c-.96 0-1.764.724-1.865 1.679l-1.263 12A1.875 1.875 0 0 0 4.25 22.5h15.5a1.875 1.875 0 0 0 1.865-2.071l-1.263-12a1.875 1.875 0 0 0-1.865-1.679H16.5V6a4.5 4.5 0 1 0-9 0ZM12 3a3 3 0 0 0-3 3v.75h6V6a3 3 0 0 0-3-3Zm-3 8.25a3 3 0 1 0 6 0v-.75a.75.75 0 0 1 1.5 0v.75a4.5 4.5 0 1 1-9 0v-.75a.75.75 0 0 1 1.5 0v.75Z" clip-rule="evenodd"/></svg></span>';
					$tags_html .= '<div class="wpz-insta-lightbox-tag__popover">';
					$tags_html .= '<a href="' . esc_url( $product_link_tag ) . '" class="wpz-insta-lightbox-tag__link" target="_blank" rel="noopener">';
					$tags_html .= '<img class="wpz-insta-lightbox-tag__img" src="' . esc_url( $product_image_url_tag ) . '" alt="' . esc_attr( $product_title_tag ) . '" />';
					$tags_html .= '<div class="wpz-insta-lightbox-tag__info">';
					$tags_html .= '<span class="wpz-insta-lightbox-tag__title">' . esc_html( $product_title_tag ) . '</span>';
					$tags_html .= '<span class="wpz-insta-lightbox-tag__price">' . $product_price_tag . '</span>';
					$tags_html .= '</div>';
					$tags_html .= '</a></div>';
					$tags_html .= '</div>';
				}
			}
		}
		if ( empty( $tags_html ) ) {
			return '';
		}
		return '<div class="wpz-insta-lightbox-tags">' . $tags_html . '</div>';
	}

	/**
	 * Get linked products with tag data for an Instagram media item.
	 * New format: array of { 'id' => int, 'tag' => array|null }
	 *
	 * @param int    $feed_id  The feed post ID.
	 * @param string $media_id The Instagram media ID.
	 * @return array Array of product objects with tag data (empty if none linked).
	 */
	public static function get_linked_products( $feed_id, $media_id ) {
		if ( empty( $feed_id ) || empty( $media_id ) || ! class_exists( 'WooCommerce' ) ) {
			return array();
		}

		$product_links = get_post_meta( $feed_id, '_wpz-insta_product-links', true );
		if ( ! is_array( $product_links ) || ! isset( $product_links[ $media_id ] ) ) {
			return array();
		}

		$val = $product_links[ $media_id ];
		if ( ! is_array( $val ) ) {
			return array();
		}

		// Check if it's the new format (array of objects with 'id' key) or old format (array of IDs)
		$result = array();
		foreach ( $val as $item ) {
			if ( is_array( $item ) && isset( $item['id'] ) ) {
				// New format: { id, tag }
				$result[] = array(
					'id'  => intval( $item['id'] ),
					'tag' => isset( $item['tag'] ) ? $item['tag'] : null,
				);
			} elseif ( is_numeric( $item ) && intval( $item ) > 0 ) {
				// Old format: just product ID - convert to new format
				$result[] = array(
					'id'  => intval( $item ),
					'tag' => null,
				);
			}
		}

		return $result;
	}

	/**
	 * Save linked products with tag data for an Instagram media item.
	 * Accepts new format: array of { 'id' => int, 'tag' => array|null }
	 *
	 * @param int    $feed_id  The feed post ID.
	 * @param string $media_id The Instagram media ID.
	 * @param array  $products Array of product objects { id, tag }. Empty array to remove all links.
	 * @return bool True on success, false on failure.
	 */
	public static function save_linked_products( $feed_id, $media_id, array $products ) {
		if ( empty( $feed_id ) || empty( $media_id ) || ! class_exists( 'WooCommerce' ) ) {
			return false;
		}

		$product_links = get_post_meta( $feed_id, '_wpz-insta_product-links', true );
		if ( ! is_array( $product_links ) ) {
			$product_links = array();
		}

		// Validate and sanitize products
		$valid_products = array();
		foreach ( $products as $product ) {
			if ( is_array( $product ) && isset( $product['id'] ) && intval( $product['id'] ) > 0 ) {
				$sanitized = array(
					'id'  => intval( $product['id'] ),
					'tag' => null,
				);
				if ( isset( $product['tag'] ) && is_array( $product['tag'] ) ) {
					$sanitized['tag'] = array(
						'x'           => isset( $product['tag']['x'] ) ? floatval( $product['tag']['x'] ) : 0,
						'y'           => isset( $product['tag']['y'] ) ? floatval( $product['tag']['y'] ) : 0,
						'album_index' => isset( $product['tag']['album_index'] ) ? intval( $product['tag']['album_index'] ) : null,
					);
				}
				$valid_products[] = $sanitized;
			}
		}

		if ( ! empty( $valid_products ) ) {
			$product_links[ $media_id ] = $valid_products;
		} else {
			unset( $product_links[ $media_id ] );
		}

		return update_post_meta( $feed_id, '_wpz-insta_product-links', $product_links );
	}

	/**
	 * Save the linked WooCommerce product IDs for an Instagram media item.
	 * Legacy method - converts to new format internally.
	 *
	 * @param int    $feed_id     The feed post ID.
	 * @param string $media_id    The Instagram media ID.
	 * @param int[]  $product_ids Array of WooCommerce product IDs. Empty array to remove all links.
	 * @return bool True on success, false on failure.
	 */
	public static function save_linked_product_ids( $feed_id, $media_id, array $product_ids ) {
		// Convert old format to new format
		$products = array();
		foreach ( $product_ids as $id ) {
			if ( is_numeric( $id ) && intval( $id ) > 0 ) {
				$products[] = array(
					'id'  => intval( $id ),
					'tag' => null,
				);
			}
		}
		return self::save_linked_products( $feed_id, $media_id, $products );
	}
}
