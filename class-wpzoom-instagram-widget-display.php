<?php
/**
 * Exit if accessed directly.
 */
defined( 'ABSPATH' ) or die;

/**
 * WPZOOM Instagram Widget Display class
 *
 * @package Wpzoom_Instagram_Widget
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
	private const IS_PRO = false;

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
		add_image_size( 'wpzoom-instagram-profile-photo-size', 128, 128, false );

		add_shortcode( 'instagram', array( $this, 'get_shortcode_output' ) );
	}

	/**
	 * Returns the markup for the feed with the given ID.
	 *
	 * @param  int    $feed_id The ID of the feed to return the markup for.
	 * @return string          The markup for the given feed.
	 */
	public function get_feed_output( int $feed_id ) {
		if ( $feed_id > -1 ) {
			$feed = get_post( $feed_id, OBJECT, 'display' );

			if ( null !== $feed && $feed instanceof WP_Post ) {
				$user_id = intval( get_post_meta( $feed_id, '_wpz-insta_user-id', true ) );
				$feed_settings = array();

				foreach( WPZOOM_Instagram_Widget_Settings::$feed_settings as $setting_name => $setting_args ) {
					$feed_settings[ $setting_name ] = WPZOOM_Instagram_Widget_Settings::get_feed_setting_value( $feed_id, $setting_name );
				}

				$feed_settings['feed-id'] = $feed_id;
				$feed_settings['user-id'] = $user_id;

				return $this->feed_content( $feed_settings );
			}
		}

		return sprintf(
			'<p class="error" style="color:red"><strong>%s</strong></p>',
			esc_html__( 'There was a problem displaying the selected feed. Please check the configuration...', 'instagram-widget-by-wpzoom' )
		);
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
				return $this->get_feed_output( $feed_id );
			}
		}

		return sprintf(
			'<p class="error" style="color:red"><strong>%s</strong></p>',
			esc_html__( 'There was a problem displaying the selected feed. Please check the configuration...', 'instagram-widget-by-wpzoom' )
		);
	}

	/**
	 * Outputs the markup for the feed with the given ID.
	 *
	 * @param  int  $feed_id The ID of the feed to output.
	 * @param  bool $echo    Whether to output the feed or return it.
	 * @return void
	 */
	public function output_feed( int $feed_id, bool $echo = true ) {
		$output = sprintf(
			"<style type=\"text/css\">%s</style>\n%s",
			$this->output_styles( $feed_id, false ),
			$this->get_feed_output( $feed_id )
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
	 * Returns the markup for a feed configured with the given arguments.
	 *
	 * @param  array  $args The arguments to define how to return the feed content.
	 * @return string
	 */
	private function feed_content( array $args, bool $preview = false ) {
		$this->api = Wpzoom_Instagram_Widget_API::getInstance();
		$output = '';
		$user_id = isset( $args['user-id'] ) ? intval( $args['user-id'] ) : -1;

		if ( $user_id > 0 ) {
			$user = get_post( $user_id );

			if ( $user instanceof WP_Post ) {
				$show_user_name = isset( $args['show-account-username'] ) && boolval( $args['show-account-username'] );
				$user_name = get_the_title( $user );
				$user_name_display = sprintf( '@%s', $user_name );
				$user_link = 'https://www.instagram.com/' . $user_name;
				$show_user_nname = isset( $args['show-account-name'] ) && boolval( $args['show-account-name'] );
				$user_display_name = get_post_meta( $user_id, '_wpz-insta_user_name', true );
				$show_user_bio = isset( $args['show-account-bio'] ) && boolval( $args['show-account-bio'] );
				$user_bio = get_the_content( null, false, $user );
				$show_user_image = isset( $args['show-account-image'] ) && boolval( $args['show-account-image'] );
				$user_image = get_the_post_thumbnail_url( $user, 'wpzoom-instagram-profile-photo-size' ) ?: plugin_dir_url( __FILE__ ) . 'dist/images/backend/user-avatar.jpg';
				$user_account_token = get_post_meta( $user_id, '_wpz-insta_token', true ) ?: '-1';

				if ( '-1' !== $user_account_token ) {
					$attrs = '';
					$new_posts_interval_number = isset( $args['check-new-posts-interval-number'] ) ? intval( $args['check-new-posts-interval-number'] ) : 1;
					$new_posts_interval_suffix = isset( $args['check-new-posts-interval-suffix'] ) ? intval( $args['check-new-posts-interval-suffix'] ) : 1;
					$enable_request_timeout = isset( $args['enable-request-timeout'] ) ? boolval( $args['enable-request-timeout'] ) : false;
					$count = 0;
					$amount = isset( $args['item-num'] ) ? intval( $args['item-num'] ) : 9;
					$lightbox = isset( $args['lightbox'] ) ? boolval( $args['lightbox'] ) : true;
					$show_overlay = isset( $args['show-overlay'] ) ? boolval( $args['show-overlay'] ) : true;
					$show_media_type_icons = isset( $args['show-media-type-icons'] ) ? boolval( $args['show-media-type-icons'] ) : true;
					$show_view_on_insta_button = isset( $args['show-view-button' ] ) ? boolval( $args['show-view-button' ] ) : true;
					$image_size = isset( $args['image-size'] ) && in_array( $args['image-size'], array( 'thumbnail', 'low_resolution', 'standard_resolution' ) ) ? $args['image-size'] : 'default_algorithm';
					$small_class = $image_size <= 180 ? 'small' : '';
					$svg_icons = plugin_dir_url( __FILE__ ) . 'dist/images/frontend/wpzoom-instagram-icons.svg';

					if ( $lightbox ) {
						$attrs .= ' data-lightbox="1"';
					}

					$this->api->set_access_token( $user_account_token );

					$items  = $this->api->get_items( array( 'image-limit' => $amount, 'image-resolution' => $image_size, 'image-width' => 320 ) );
					$errors = $this->api->errors->get_error_messages();

					$output .= '<div class="zoom-instagram' . ( isset( $args['feed-id'] ) ? sprintf( ' feed-%d', intval( $args['feed-id'] ) ) : '' ) . '">';

					if ( ! is_array( $items ) ) {
						return $this->get_errors( $errors );
					} else {
						if ( $show_user_image || $show_user_nname || $show_user_name || $show_user_bio ) {
							$output .= '<header class="zoom-instagram-widget__header">';

							if ( $show_user_image && ! empty( $user_image ) ) {
								$output .= '<div class="zoom-instagram-widget__header-column-left">';
								$output .= '<img src="' . esc_url( $user_image ) . '" alt="' . esc_attr( $user_name_display ) . '" width="70" height="70"/>';
								$output .= '</div>';
							}

							if ( $show_user_nname || $show_user_name || $show_user_bio ) {
								$output .= '<div class="zoom-instagram-widget__header-column-right">';

								if ( $show_user_nname ) {
									$output .= '<h5 class="zoom-instagram-widget__header-name">' . esc_html( $user_display_name ) . '</h5>';
								}

								if ( $show_user_name ) {
									$output .= '<p class="zoom-instagram-widget__header-user"><a href="' . esc_url( $user_link ) . '" target="_blank" rel="nofollow">' . esc_html( $user_name_display ) . '</a></p>';
								}

								if ( $show_user_bio ) {
									$output .= '<div class="zoom-instagram-widget__header-bio">' . esc_html( $user_bio ) . '</div>';
								}

								$output .= '</div>';
							}

							$output .= '</header>';
						}

						$output .= '<ul class="zoom-instagram-widget__items zoom-instagram-widget__items--no-js"' . $attrs . '>';

						foreach ( $items['items'] as $item ) {
							$inline_attrs  = '';
							$overwrite_src = false;
							$link          = isset( $item['link'] ) ? $item['link'] : '';
							$src           = isset( $item['image-url'] ) ? $item['image-url'] : '';
							$media_id      = isset( $item['image-id'] ) ? $item['image-id'] : '';
							$alt           = isset( $item['image-caption'] ) ? esc_attr( $item['image-caption'] ) : '';
							$likes         = isset( $item['likes_count'] ) ? intval( $item['likes_count'] ) : 0;
							$typ           = isset( $item['type'] ) ? strtolower( $item['type'] ) : 'image';
							$type          = in_array( $typ, array( 'video', 'carousel_album' ) ) ? $typ : false;
							$is_album      = 'carousel_album' == $type;
							$is_video      = 'video' == $type;
							$comments      = isset( $item['comments_count'] ) ? intval( $item['comments_count'] ) : 0;

							if ( ! empty( $media_id ) && empty( $src ) ) {
								$inline_attrs  = 'data-media-id="' . esc_attr( $media_id ) . '"';
								$inline_attrs .= 'data-nonce="' . wp_create_nonce( WPZOOM_Instagram_Image_Uploader::get_nonce_action( $media_id ) ) . '"';
								$overwrite_src = true;
							}

							if (
								! empty( $media_id ) &&
								! empty( $src ) &&
								! file_exists( $this->convert_url_to_path( $src ) )
							) {
								$inline_attrs  = 'data-media-id="' . esc_attr( $media_id ) . '"';
								$inline_attrs .= 'data-nonce="' . wp_create_nonce( WPZOOM_Instagram_Image_Uploader::get_nonce_action( $media_id ) ) . '"';
								$inline_attrs .= 'data-regenerate-thumbnails="1"';
								$overwrite_src = true;
							}

							$inline_attrs .= 'data-media-type="' . esc_attr( $type ?: 'image' ) . '"';

							if ( $overwrite_src ) {
								$src = $item['original-image-url'];
							}

							$output .= '<li class="zoom-instagram-widget__item" ' . $inline_attrs . '>';

							$inline_style = '';
							if ( empty( $instance['lazy-load-images'] ) ) {
								$inline_style .= "background-image: url('" . $src . "');";
							}

							if ( $show_overlay ) {
								$output .= '<div class="hover-layout zoom-instagram-widget__overlay zoom-instagram-widget__black ' . $small_class . '">';

								if ( $show_media_type_icons && ! empty( $type ) ) {
									$output .= '<svg class="svg-icon" shape-rendering="geometricPrecision"><use xlink:href="' . esc_url( $svg_icons ) . '#' . $type . '"></use></svg>';
								}

								if ( ! empty( $likes ) && ! empty( $comments ) ) {
									$output .= '<div class="hover-controls">
										<span class="dashicons dashicons-heart"></span>
										<span class="counter">' . $this->format_number( $likes ) . '</span>
										<span class="dashicons dashicons-format-chat"></span>
										<span class="counter">' . $this->format_number( $comments ) . '</span>
									</div>';
								}

								$output .= '<div class="zoom-instagram-icon-wrap"><a class="zoom-svg-instagram-stroke" href="' . $link . '" rel="noopener nofollow" target="_blank" title="' . $alt . '"></a></div>
								<a class="zoom-instagram-link" data-src="' . $src . '" style="' . $inline_style . '" data-mfp-src="' . $media_id . '" href="' . $link . '" target="_blank" rel="noopener nofollow" title="' . $alt . '"></a>
								</div>';
							} else {
								$output .= '<a class="zoom-instagram-link" data-src="' . $src . '" style="' . $inline_style . '" data-mfp-src="' . $media_id . '" href="' . $link . '" target="_blank" rel="noopener nofollow" title="' . $alt . '">';

								if ( $show_media_type_icons && ! empty( $type ) ) {
									$output .= '<svg class="svg-icon" shape-rendering="geometricPrecision"><use xlink:href="' . esc_url( $svg_icons ) . '#' . $type . '"></use></svg>';
								}

								$output .= '</a>';
							}

							$output .= '</li>';

							if ( ++ $count === $amount ) {
								break;
							}
						}

						$output .= '</ul>';

						if ( $show_view_on_insta_button || ( $preview || ( self::IS_PRO && isset( $args['show-load-more'] ) && boolval( $args['show-load-more'] ) ) ) ) {
							$output .= '<footer class="zoom-instagram-widget__footer">';

							if ( $show_view_on_insta_button ) {
								$view_on_insta_label = isset( $args['view-button-text'] ) ? trim( $args['view-button-text'] ) : __( 'View on Instagram', 'instagram-widget-by-wpzoom' );
								$output .= '<a href="' . esc_url( $user_link ) . '" target="_blank" class="button button-primary wpz-insta-view-on-insta-button">';
								$output .= '<span class="button-icon zoom-svg-instagram-stroke"></span> ';
								$output .= esc_html( $view_on_insta_label );
								$output .= '</a>';
							}

							if ( $preview || ( self::IS_PRO && isset( $args['show-load-more'] ) && boolval( $args['show-load-more'] ) ) ) {
								$load_more_label = isset( $args['load-more-text'] ) ? trim( $args['load-more-text'] ) : __( 'Load More', 'instagram-widget-by-wpzoom' );
								$output .= '<a href="" target="_blank" class="button button-primary wpz-insta-load-more-button' . ( !self::IS_PRO ? ' disabled' : '' ) . '">';
								$output .= esc_html( $load_more_label . ( !self::IS_PRO ? __( ' [PRO only]', 'instagram-widget-by-wpzoom' ) : '' ) );
								$output .= '</a>';
							}

							$output .= '</footer>';
						}

						if ( $lightbox ) {
							$output .= '<div class="wpz-insta-lightbox-wrapper mfp-hide"><div class="swiper-container"><div class="swiper-wrapper">';

							$amount = count( $items );
							$count = 0;

							foreach ( $items['items'] as $item ) {
								$count++;
								$link     = isset( $item['link'] ) ? $item['link'] : '';
								$src      = isset( $item['original-image-url'] ) ? $item['original-image-url'] : '';
								$media_id = isset( $item['image-id'] ) ? $item['image-id'] : '';
								$alt      = isset( $item['image-caption'] ) ? esc_attr( $item['image-caption'] ) : '';
								$typ      = isset( $item['type'] ) ? strtolower( $item['type'] ) : 'image';
								$type     = in_array( $typ, array( 'video', 'carousel_album' ) ) ? $typ : false;
								$is_album = 'carousel_album' == $type;
								$is_video = 'video' == $type;
								$children = $is_album && isset( $item['children'] ) && is_object( $item['children'] ) && isset( $item['children']->data ) ? $item['children']->data : false;

								$output .= '<div data-uid="' . $media_id . '" class="swiper-slide wpz-insta-lightbox-item"><div class="wpz-insta-lightbox"><div class="image-wrapper">';

								if ( $is_album && false !== $children ) {
									$output .= '<div class="swiper-container"><div class="swiper-wrapper wpz-insta-album-images">';

									foreach ( $children as $child ) {
										$child_type = property_exists( $child, 'media_type' ) && in_array( $child->media_type, array( 'VIDEO', 'CAROUSEL_ALBUM' ) ) ? strtolower( $child->media_type ) : 'image';
										$thumb = 'video' == $child_type && property_exists( $child, 'thumbnail_url' ) ? strtolower( $child->thumbnail_url ) : '';

										$output .= '<div class="swiper-slide wpz-insta-album-image" data-media-type="' . esc_attr( $child_type ) . '">';

										if ( 'video' == $child_type ) {
											$output .= '<video controls preload="metadata" poster="' . esc_attr( $thumb ) . '"><source src="' . esc_url( $child->media_url ) . '" type="video/mp4"/>' . esc_html( $alt ) . '</video>';
										} else {
											$output .= '<img src="' . esc_url( $child->media_url ) . '" alt="' . esc_attr( $alt ) . '"/>';
										}

										$output .= '</div>';
									}

									$output .= '</div><div class="swiper-pagination"></div><div class="swiper-button-prev"></div><div class="swiper-button-next"></div></div>';
								} else {
									$output .= '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '"/>';
								}

								$output .= '</div>
								<div class="details-wrapper">
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
												' . __( 'Follow', 'wpzoom-instagram-widget' ) . '
											</a>
										</div>
									</div>
								</div>';

								if ( ! empty( $item['image-caption'] ) ) {
									$output .= '<div class="wpz-insta-caption">' . self::filter_caption( $item['image-caption'] ) . '</div>';
								}

								if ( ! empty( $item['timestamp'] ) ) {
									$output .= '<div class="wpz-insta-date">' . sprintf( __( '%s ago' ), human_time_diff( strtotime( $item['timestamp'] ) ) ) . '</div>';
								}

								$output .= '<div class="view-post">
								<a href="' . esc_url( $link ) . '" target="_blank" rel="noopener"><span class="dashicons dashicons-instagram"></span>' . __( 'View on Instagram', 'wpzoom-instagram-widget' ) . '</a>
								<span class="delimiter">|</span>
								<div class="wpz-insta-pagination">' . sprintf( '%d/%d', $count, $amount ) . '</div>
								</div></div></div></div>';
							}

							$output .= '</div><div class="swiper-button-prev"></div><div class="swiper-button-next"></div></div></div>';
						}
					}

					$output .= '</div>';

					return $output;
				}
			}
		}

		return sprintf(
			'<div class="zoom-instagram"><p class="select-a-feed">%s%s</p></div>',
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M20 10.8H6.7l4.1-4.5-1.1-1.1-5.8 6.3 5.8 5.8 1.1-1.1-4-3.9H20z" fill="currentColor" stroke="currentColor" stroke-width="1.5"/></svg>',
			__( 'Please select an account in the panel to the left&hellip;', 'instagram-widget-by-wpzoom' )
		);
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
				'<p>%s <strong><a href="%s" target="_blank">%s</a></strong> %s</p>',
				__( 'Instagram Widget misconfigured or your Access Token <strong>expired</strong>. Please check', 'instagram-widget-by-wpzoom' ),
				admin_url( 'options-general.php?page=wpzoom-instagram-widget' ),
				__( 'Instagram Settings Page', 'instagram-widget-by-wpzoom' ),
				__( 'and make sure the plugin is properly configured', 'instagram-widget-by-wpzoom' )
			);

			if ( ! empty( $errors ) ) {
				$output .= '<ul>';

				foreach ( $errors as $error ) {
					$output .= '<li>' . esc_html( $error ) . '</li>';
				}

				$output .= '</ul>';
			}
		} else {
			$output .= '&#8230;';
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
		$feed_id                = isset( $args['feed-id'] ) ? ".feed-" . $args['feed-id'] : "";
		$layout                 = isset( $args['layout'] ) ? intval( $args['layout'] ) : 0;
		$col_num                = isset( $args['col-num'] ) && intval( $args['col-num'] ) !== 3 ? intval( $args['col-num'] ) : 3;
		$spacing_between        = isset( $args['spacing-between'] ) && intval( $args['spacing-between'] ) > -1 ? intval( $args['spacing-between'] ) : -1;
		$spacing_between_suffix = $this->get_suffix( isset( $args['spacing-between-suffix'] ) ? intval( $args['spacing-between-suffix'] ) : 0 );
		$feed_width             = isset( $args['feed-width'] ) ? ( intval( $args['feed-width'] ) ?: 100 ) : 100;
		$feed_width_suffix      = $this->get_suffix( isset( $args['feed-width-suffix'] ) ? intval( $args['feed-width-suffix'] ) : 2 );
		$feed_width_full        = $feed_width == 100 && $feed_width_suffix == '%';
		$feed_height            = isset( $args['feed-height'] ) ? ( intval( $args['feed-height'] ) ?: -1 ) : -1;
		$feed_height_suffix     = $this->get_suffix( isset( $args['feed-height-suffix'] ) ? intval( $args['feed-height-suffix'] ) : 0 );
		$feed_height_full       = $feed_height == 100 && $feed_height_suffix == '%';
		$bg_color               = isset( $args['bg-color'] ) ? $this->validate_color( $args['bg-color'] ) : '';
		$spacing_around         = isset( $args['spacing-around'] ) ? ( intval( $args['spacing-around'] ) ?: -1 ) : -1;
		$spacing_around_suffix  = $this->get_suffix( isset( $args['spacing-around-suffix'] ) ? intval( $args['spacing-around-suffix'] ) : 0 );
		$font_size              = isset( $args['font-size'] ) ? ( intval( $args['font-size'] ) ?: -1 ) : -1;
		$font_size_suffix       = $this->get_suffix( isset( $args['font-size-suffix'] ) ? intval( $args['font-size-suffix'] ) : 0 );
		$hover_likes            = isset( $args['hover-likes'] ) ? boolval( $args['hover-likes'] ) : true;
		$hover_link             = isset( $args['hover-link'] ) ? boolval( $args['hover-link'] ) : true;
		$hover_caption          = isset( $args['hover-caption'] ) ? boolval( $args['hover-caption'] ) : false;
		$hover_username         = isset( $args['hover-username'] ) ? boolval( $args['hover-username'] ) : false;
		$hover_date             = isset( $args['hover-date'] ) ? boolval( $args['hover-date'] ) : false;
		$hover_text_color       = isset( $args['hover-text-color'] ) ? $this->validate_color( $args['hover-text-color'] ) : '';
		$hover_bg_color         = isset( $args['hover-bg-color'] ) ? $this->validate_color( $args['hover-bg-color'] ) : '';

		if ( $font_size > -1 || ! empty( $bg_color ) || $feed_width > -1 || $feed_height > -1 || $spacing_around > -1 ) {
			$output .= ".zoom-instagram" . $feed_id . " {\n";

			if ( $font_size > -1 ) {
				$output .= "\tfont-size: " . $font_size . $font_size_suffix . ";\n";
			}

			if ( ! empty( $bg_color ) ) {
				$output .= "\tbackground-color: " . $bg_color . ";\n";
			}

			if ( $feed_width > -1 && ! $feed_width_full ) {
				$output .= "\twidth: " . $feed_width . $feed_width_suffix . ";\n";
			}

			if ( $feed_height > -1 && ! $feed_height_full ) {
				$output .= "\theight: " . $feed_height . $feed_height_suffix . ";\n";
			}

			if ( $spacing_around > -1 ) {
				$output .= "\tpadding: " . $spacing_around . $spacing_around_suffix . ";\n";
			}

			$output .= "}\n\n";
		}

		if ( 3 !== $col_num || $spacing_between > -1 ) {
			$output .= ".zoom-instagram" . $feed_id . " .zoom-instagram-widget__items {\n";

			if ( 3 !== $col_num ) {
				$output .= "\tgrid-template-columns: repeat(" . $col_num . ", 1fr);\n";
			}

			if ( $spacing_between > -1 ) {
				$output .= "\tgap: " . $spacing_between . $spacing_between_suffix . ";\n";
			}

			$output .= "}\n";
		}

		return $output;
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
				$output = $this->style_content( array(
					'feed-id'                => $feed_id,
					'layout'                 => intval( get_post_meta( $feed_id, '_wpz-insta_layout', true ) ?: 0 ),
					'item-num'               => intval( get_post_meta( $feed_id, '_wpz-insta_item-num', true ) ?: 9 ),
					'col-num'                => intval( get_post_meta( $feed_id, '_wpz-insta_col-num', true ) ?: 3 ),
					'spacing-between'        => intval( get_post_meta( $feed_id, '_wpz-insta_spacing-between', true ) ?: -1 ),
					'spacing-between-suffix' => intval( get_post_meta( $feed_id, '_wpz-insta_spacing-between-suffix', true ) ?: 0 ),
					'feed-width'             => intval( get_post_meta( $feed_id, '_wpz-insta_feed-width', true ) ?: 100 ),
					'feed-width-suffix'      => intval( get_post_meta( $feed_id, '_wpz-insta_feed-width-suffix', true ) ?: 2 ),
					'feed-height'            => intval( get_post_meta( $feed_id, '_wpz-insta_feed-height', true ) ?: -1 ),
					'feed-height-suffix'     => intval( get_post_meta( $feed_id, '_wpz-insta_feed-height-suffix', true ) ?: 0 ),
					'bg-color'               => $this->validate_color( get_post_meta( $feed_id, '_wpz-insta_bg-color', true ) ?: '' ),
					'spacing-around'         => intval( get_post_meta( $feed_id, '_wpz-insta_spacing-around', true ) ?: -1 ),
					'spacing-around-suffix'  => intval( get_post_meta( $feed_id, '_wpz-insta_spacing-around-suffix', true ) ?: 0 ),
					'font-size'              => intval( get_post_meta( $feed_id, '_wpz-insta_font-size', true ) ?: -1 ),
					'font-size-suffix'       => intval( get_post_meta( $feed_id, '_wpz-insta_font-size-suffix', true ) ?: 0 ),
					'hover-likes'            => boolval( get_post_meta( $feed_id, '_wpz-insta_hover-likes', true ) ?: true ),
					'hover-link'             => boolval( get_post_meta( $feed_id, '_wpz-insta_hover-link', true ) ?: true ),
					'hover-caption'          => boolval( get_post_meta( $feed_id, '_wpz-insta_hover-caption', true ) ?: false ),
					'hover-username'         => boolval( get_post_meta( $feed_id, '_wpz-insta_hover-username', true ) ?: false ),
					'hover-date'             => boolval( get_post_meta( $feed_id, '_wpz-insta_hover-date', true ) ?: false ),
					'hover-text-color'       => $this->validate_color( get_post_meta( $feed_id, '_wpz-insta_hover-text-color', true ) ?: '' ),
					'hover-bg-color'         => $this->validate_color( get_post_meta( $feed_id, '_wpz-insta_hover-bg-color', true ) ?: '' ),
				) );
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
	public function format_number( int $num ) {
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
	function convert_url_to_path( string $url ) {
		return str_replace(
			wp_get_upload_dir()['baseurl'],
			wp_get_upload_dir()['basedir'],
			$url
		);
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
}
