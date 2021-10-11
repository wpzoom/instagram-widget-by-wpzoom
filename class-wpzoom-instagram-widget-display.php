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
		add_action( 'wp_ajax_wpz-insta_feed-styles', array( $this, 'dynamic_styles' ) );
		add_action( 'wp_ajax_nopriv_wpz-insta_feed-styles', array( $this, 'dynamic_styles' ) );
	}

	/**
	 * Outputs the markup for the feed with the given ID.
	 *
	 * @param  int  $feed_id The ID of the feed to output.
	 * @return void
	 */
	public function output_feed( int $feed_id ) {
		if ( $feed_id > -1 ) {
			$feed = get_post( $feed_id, OBJECT, 'display' );

			if ( null !== $feed && $feed instanceof WP_Post ) {
				$user_id = intval( get_post_meta( $feed_id, '_wpz-insta_user-id', true ) );

				echo $this->feed_content( array(
					'user-id'                         => $user_id,
					'check-new-posts-interval-number' => intval( get_post_meta( $feed_id, '_wpz-insta_check-new-posts-interval-number', true ) ?: 1 ),
					'check-new-posts-interval-suffix' => intval( get_post_meta( $feed_id, '_wpz-insta_check-new-posts-interval-suffix', true ) ?: 1 ),
					'enable-request-timeout'          => boolval( get_post_meta( $feed_id, '_wpz-insta_enable-request-timeout', true ) ?: false ),
					'layout'                          => intval( get_post_meta( $feed_id, '_wpz-insta_layout', true ) ?: 0 ),
					'item-num'                        => intval( get_post_meta( $feed_id, '_wpz-insta_item-num', true ) ?: 9 ),
					'col-num'                         => intval( get_post_meta( $feed_id, '_wpz-insta_col-num', true ) ?: 3 ),
					'spacing-between'                 => intval( get_post_meta( $feed_id, '_wpz-insta_spacing-between', true ) ?: -1 ),
					'spacing-between-suffix'          => intval( get_post_meta( $feed_id, '_wpz-insta_spacing-between-suffix', true ) ?: 0 ),
					'feed-width'                      => intval( get_post_meta( $feed_id, '_wpz-insta_feed-width', true ) ?: 100 ),
					'feed-width-suffix'               => intval( get_post_meta( $feed_id, '_wpz-insta_feed-width-suffix', true ) ?: 2 ),
					'feed-height'                     => intval( get_post_meta( $feed_id, '_wpz-insta_feed-height', true ) ?: -1 ),
					'feed-height-suffix'              => intval( get_post_meta( $feed_id, '_wpz-insta_feed-height-suffix', true ) ?: 0 ),
					'bg-color'                        => $this->validate_color( get_post_meta( $feed_id, '_wpz-insta_bg-color', true ) ?: '' ),
					'spacing-around'                  => intval( get_post_meta( $feed_id, '_wpz-insta_spacing-around', true ) ?: -1 ),
					'spacing-around-suffix'           => intval( get_post_meta( $feed_id, '_wpz-insta_spacing-around-suffix', true ) ?: 0 ),
					'font-size'                       => intval( get_post_meta( $feed_id, '_wpz-insta_font-size', true ) ?: -1 ),
					'font-size-suffix'                => intval( get_post_meta( $feed_id, '_wpz-insta_font-size-suffix', true ) ?: 0 ),
					'lightbox'                        => boolval( get_post_meta( $feed_id, '_wpz-insta_lightbox', true ) ?: true ),
					'show-overlay'                    => boolval( get_post_meta( $feed_id, '_wpz-insta_show-overlay', true ) ?: true ),
					'show-media-type-icons'           => boolval( get_post_meta( $feed_id, '_wpz-insta_show-media-type-icons', true ) ?: true ),
					'image-size'                      => intval( get_post_meta( $feed_id, '_wpz-insta_image-size', true ) ?: -1 ),
					'hover-likes'                     => boolval( get_post_meta( $feed_id, '_wpz-insta_hover-likes', true ) ?: true ),
					'hover-link'                      => boolval( get_post_meta( $feed_id, '_wpz-insta_hover-link', true ) ?: true ),
					'hover-caption'                   => boolval( get_post_meta( $feed_id, '_wpz-insta_hover-caption', true ) ?: false ),
					'hover-username'                  => boolval( get_post_meta( $feed_id, '_wpz-insta_hover-username', true ) ?: false ),
					'hover-date'                      => boolval( get_post_meta( $feed_id, '_wpz-insta_hover-date', true ) ?: false ),
					'hover-text-color'                => $this->validate_color( get_post_meta( $feed_id, '_wpz-insta_hover-text-color', true ) ?: '' ),
					'hover-bg-color'                  => $this->validate_color( get_post_meta( $feed_id, '_wpz-insta_hover-bg-color', true ) ?: '' ),
				) );
			}
		}

		esc_html_e( 'There was a problem displaying the selected feed. Please check the configuration.', 'instagram-widget-by-wpzoom' );
	}

	/**
	 * Outputs the markup for the preview of a feed configured with the given arguments.
	 *
	 * @param  array $args The arguments to define how to output the feed preview.
	 * @return void
	 */
	public function output_preview( array $args ) {
		echo $this->feed_content( $args );
	}

	/**
	 * Returns the markup for a feed configured with the given arguments.
	 *
	 * @param  array  $args The arguments to define how to return the feed content.
	 * @return string
	 */
	private function feed_content( array $args ) {
		$this->api = Wpzoom_Instagram_Widget_API::getInstance();
		$output = '';
		$user_id = isset( $args['user-id'] ) ? intval( $args['user-id'] ) : -1;

		if ( $user_id > 0 ) {
			$user = get_post( $user_id );

			if ( $user instanceof WP_Post ) {
				$user_name = get_the_title( $user );
				$user_display_name = sprintf( '@%s', $user_name );
				$user_image = get_the_post_thumbnail_url( $user ) ?: plugin_dir_url( __FILE__ ) . 'dist/images/backend/user-avatar.jpg';
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
					$image_width = isset( $args['image-size'] ) && intval( $args['image-size'] ) > -1 ? intval( $args['image-size'] ) : 600;
					$small_class = $image_width <= 180 ? 'small' : '';
					$svg_icons = plugin_dir_url( __FILE__ ) . 'dist/images/frontend/wpzoom-instagram-icons.svg';

					if ( $lightbox ) {
						$attrs .= ' data-lightbox="1"';
					}

					//$this->api->set_access_token( $user_account_token );
$this->api->set_access_token( 'IGQVJYLXVaWHZA3YU9GcmdUZAEx2d3lGMTExMVRPV3l0R0V3Y1BUM2pMWDBYRUdQVWtVN21lM0J0YkN6Y0JBejhaSmR1OFlPV2tXeEE2eUFsaFJQWVdmWWVIX1BJdFM5LUxuZAXdHTi1R' );

					$items  = $this->api->get_items( array( 'image-limit' => $amount, 'image-width' => $image_width ) );
					$errors = $this->api->errors->get_error_messages();

					if ( ! is_array( $items ) ) {
						return $this->get_errors( $errors );
					} else {
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
							//$inline_style  = 'width:' . esc_attr( $image_width ) . 'px;';
							//$inline_style .= 'height:' . esc_attr( $image_width ) . 'px;';
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

						if ( $lightbox ) {
							$output .= '<div class="wpz-insta-lightbox-wrapper mfp-hide"><div class="swiper-container"><div class="swiper-wrapper">';

							$amount = count( $items );
							$count = 0;

							foreach ( $items as $item ) {
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
										<img src="' . esc_url( $user_image ) . '" alt="' . esc_attr( $user_display_name ) . '" width="42" height="42"/>
									</div>
									<div class="wpz-insta-buttons">
										<div class="wpz-insta-username">
											<a rel="noopener" target="_blank" href="' . sprintf( 'https://instagram.com/%s', esc_attr( $user_name ) ) . '">' . esc_html( $user_display_name ) . '</a>
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

					return $output;
				}
			}
		}

		return esc_html__( 'There was a problem displaying the selected feed. Please check the configuration.', 'instagram-widget-by-wpzoom' );
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
		$layout                 = isset( $args['layout'] ) ? intval( $args['layout'] ) : 0;
		$col_num                = isset( $args['col-num'] ) ? ( intval( $args['col-num'] ) ?: 3 ) : 3;
		$spacing_between        = isset( $args['spacing-between'] ) ? ( intval( $args['spacing-between'] ) ?: -1 ) : -1;
		$spacing_between_suffix = $this->get_suffix( isset( $args['spacing-between-suffix'] ) ? intval( $args['spacing-between-suffix'] ) : 0 );
		$feed_width             = isset( $args['feed-width'] ) ? ( intval( $args['feed-width'] ) ?: 100 ) : 100;
		$feed_width_suffix      = $this->get_suffix( isset( $args['feed-width-suffix'] ) ? intval( $args['feed-width-suffix'] ) : 2 );
		$feed_height            = isset( $args['feed-height'] ) ? ( intval( $args['feed-height'] ) ?: -1 ) : -1;
		$feed_height_suffix     = $this->get_suffix( isset( $args['feed-height-suffix'] ) ? intval( $args['feed-height-suffix'] ) : 0 );
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

		if ( $font_size > -1 || ! empty( $bg_color ) || $spacing_around > -1 ) {
			$output .= ".zoom-new-instagram-widget {\n";

			if ( $font_size > -1 ) {
				$output .= "\tfont-size: " . $font_size . $font_size_suffix . ";\n";
			}

			if ( ! empty( $bg_color ) ) {
				$output .= "\tbackground-color: " . $bg_color . ";\n";
			}

			if ( $spacing_around > -1 ) {
				$output .= "\tpadding: " . $spacing_around . $spacing_around_suffix . ";\n";
			}

			$output .= "}\n\n";
		}

		if ( 3 !== $col_num || $spacing_between > -1 || $feed_width > -1 || $feed_height > -1 ) {
			$output .= ".zoom-new-instagram-widget .zoom-instagram-widget__items {\n";

			if ( 3 !== $col_num ) {
				$output .= "\tgrid-template-columns: repeat(" . $col_num . ", 1fr);\n";
			}

			if ( $spacing_between > -1 ) {
				$output .= "\tgap: " . $spacing_between . $spacing_between_suffix . ";\n";
			}

			if ( $feed_width > -1 ) {
				$output .= "\twidth: " . $feed_width . $feed_width_suffix . ";\n";
			}

			if ( $feed_height > -1 ) {
				$output .= "\theight: " . $feed_height . $feed_height_suffix . ";\n";
			}

			$output .= "}\n";
		}

		return $output;
	}

	/**
	 * Outputs the CSS styles for the feed with the given ID.
	 *
	 * @param  int  $feed_id The ID of the feed to output the styles for.
	 * @return void
	 */
	public function output_styles( int $feed_id ) {
		if ( $feed_id > -1 ) {
			$feed = get_post( $feed_id, OBJECT, 'display' );

			if ( null !== $feed && $feed instanceof WP_Post ) {
				echo $this->style_content( array(
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
	}

	/**
	 * Outputs the CSS styles for the preview of a feed configured with the given arguments.
	 *
	 * @param  array $args The arguments to define how to output the feed preview CSS.
	 * @return void
	 */
	public function output_preview_styles( array $args ) {
		echo $this->style_content( $args );
	}

	/**
	 * Outputs dynamic CSS for a feed based on certain $_GET variables.
	 *
	 * @return void
	 */
	public function dynamic_styles() {
		header( 'Content-type: text/css; charset: UTF-8' );

		if ( isset( $_GET['feed'] ) ) {
			$this->output_styles( intval( $_GET['feed'] ) );
		} elseif ( current_user_can( 'manage_options' ) && isset( $_GET['wpz-insta-widget-preview'] ) ) {
			$this->output_preview_styles(
				array(
					'layout'                 => isset( $_GET['layout'] ) ? intval( $_GET['layout'] ) : 0,
					'item-num'               => isset( $_GET['item-num'] ) ? ( intval( $_GET['item-num'] ) ?: 9 ) : 9,
					'col-num'                => isset( $_GET['col-num'] ) ? ( intval( $_GET['col-num'] ) ?: 3 ) : 3,
					'spacing-between'        => isset( $_GET['spacing-between'] ) ? ( intval( $_GET['spacing-between'] ) ?: -1 ) : -1,
					'spacing-between-suffix' => isset( $_GET['spacing-between-suffix'] ) ? intval( $_GET['spacing-between-suffix'] ) : 0,
					'feed-width'             => isset( $_GET['feed-width'] ) ? ( intval( $_GET['feed-width'] ) ?: 100 ) : 100,
					'feed-width-suffix'      => isset( $_GET['feed-width-suffix'] ) ? intval( $_GET['feed-width-suffix'] ) : 2,
					'feed-height'            => isset( $_GET['feed-height'] ) ? ( intval( $_GET['feed-height'] ) ?: -1 ) : -1,
					'feed-height-suffix'     => isset( $_GET['feed-height-suffix'] ) ? intval( $_GET['feed-height-suffix'] ) : 0,
					'bg-color'               => isset( $_GET['bg-color'] ) ? $this->validate_color( $_GET['bg-color'] ) : '',
					'spacing-around'         => isset( $_GET['spacing-around'] ) ? ( intval( $_GET['spacing-around'] ) ?: -1 ) : -1,
					'spacing-around-suffix'  => isset( $_GET['spacing-around-suffix'] ) ? intval( $_GET['spacing-around-suffix'] ) : 0,
					'font-size'              => isset( $_GET['font-size'] ) ? ( intval( $_GET['font-size'] ) ?: -1 ) : -1,
					'font-size-suffix'       => isset( $_GET['font-size-suffix'] ) ? intval( $_GET['font-size-suffix'] ) : 0,
					'hover-likes'            => isset( $_GET['hover-likes'] ) ? boolval( $_GET['hover-likes'] ) : true,
					'hover-link'             => isset( $_GET['hover-link'] ) ? boolval( $_GET['hover-link'] ) : true,
					'hover-caption'          => isset( $_GET['hover-caption'] ) ? boolval( $_GET['hover-caption'] ) : false,
					'hover-username'         => isset( $_GET['hover-username'] ) ? boolval( $_GET['hover-username'] ) : false,
					'hover-date'             => isset( $_GET['hover-date'] ) ? boolval( $_GET['hover-date'] ) : false,
					'hover-text-color'       => isset( $_GET['hover-text-color'] ) ? $this->validate_color( $_GET['hover-text-color'] ) : '',
					'hover-bg-color'         => isset( $_GET['hover-bg-color'] ) ? $this->validate_color( $_GET['hover-bg-color'] ) : '',
				)
			);
		}

		exit;
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
