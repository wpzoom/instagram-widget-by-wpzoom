<?php
/**
 *
 * Enqueue CSS/JS of the plugin.
 *
 * @since   2.0.2
 * @package WPZOOM_Instagram_Widget
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPZOOM_Instagram_Widget_Assets ' ) ) {
	/**
	 * Main WPZOOM_Instagram_Widget_Assets Class.
	 *
	 * @since 2.0.2
	 */
	class WPZOOM_Instagram_Widget_Assets  {

		/**
		 * This plugin's instance.
		 *
		 * @var WPZOOM_Instagram_Widget_Assets
		 * @since 2.0.2
		 */
		private static $instance;

		/**
		 * Provides singleton instance.
		 *
		 * @since 2.0.2
		 * @return self instance
		 */
		public static function instance() {

			if ( null === self::$instance ) {
				self::$instance = new WPZOOM_Instagram_Widget_Assets();
			}

			return self::$instance;
		}

		/**
		 * The base directory path.
		 *
		 * @var string $_dir
		 */
		private $_dir;

		/**
		 * The base URL path.
		 *
		 * @var string $_url
		 */
		private $_url;

		/**
		 * The Constructor.
		 */
		public function __construct() {

			add_action( 'enqueue_block_assets', array( $this, 'frontend_register_scripts' ), 5 );
			add_action( 'enqueue_block_assets', array( $this, 'widget_styles' ), 5 );
			
			add_action( 'enqueue_block_editor_assets', array( $this, 'register_block_assets' ) );
			add_action( 'enqueue_block_editor_assets', array( $this, 'widget_styles' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'widget_styles' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'register_widget_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_widget_scripts' ) );

			/**
			 * Enqueue styles and scripts for SiteOrigin Page Builder.
			 */
			add_action( 'siteorigin_panel_enqueue_admin_scripts', array( $this, 'widget_styles' ) );
			add_action( 'siteorigin_panel_enqueue_admin_scripts', array( $this, 'register_widget_scripts' ) );
			add_action( 'siteorigin_panel_enqueue_admin_scripts', array( $this, 'enqueue_widget_scripts' ) );

		}


		public function frontend_register_scripts() {

			global $post;
			$general_options    = get_option( 'wpzoom-instagram-general-settings' );

			$should_enqueue     = has_block( 'wpzoom/instagram-block' );
			$has_reusable_block = self::has_reusable_block( 'wpzoom/instagram-block' );
			$is_active_widget   = is_active_widget( false, false, 'wpzoom_instagram_widget', false );
			$has_shortcode      = ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'instagram' ) );
			$has_widget_block   = self::is_active_block_widget( 'wpzoom/instagram-block' ); 
			$load_css_js        = isset( $general_options['load-css-js'] ) ? true : false;

			$script_asset_file = include( plugin_dir_path( __FILE__ ) . 'dist/scripts/backend/block.asset.php' );
			$style_asset_file = include( plugin_dir_path( __FILE__ ) . 'dist/styles/frontend/index.asset.php' );

			if( is_admin() || $load_css_js || $should_enqueue || $has_reusable_block || $is_active_widget || $has_shortcode || $has_widget_block || isset( $_GET['wpz-insta-widget-preview'] ) ) {
				wp_register_script(
					'magnific-popup',
					plugins_url( 'dist/scripts/library/magnific-popup.js', __FILE__ ),
					array( 'jquery', 'underscore', 'wp-util' ),
					filemtime( plugin_dir_path( __FILE__ ) . 'dist/scripts/library/magnific-popup.js' ),
					true
				);
		
				wp_register_script(
					'swiper-js',
					plugins_url( 'dist/scripts/library/swiper.js', __FILE__ ),
					array(),
					'7.4.1'
				);
		
				wp_register_script(
					'wpz-insta_block-frontend-script',
					plugins_url( 'dist/scripts/frontend/block.js', __FILE__ ),
					array( 'jquery', 'underscore', 'magnific-popup', 'swiper-js' ),
					$script_asset_file['version']
				);
		
				wp_register_style(
					'magnific-popup',
					plugins_url( 'dist/styles/library/magnific-popup.css', __FILE__ ),
					array( 'dashicons' ),
					WPZOOM_INSTAGRAM_VERSION
				);
			
				wp_register_style(
					'wpz-insta_block-frontend-style',
					plugins_url( 'dist/styles/frontend/index.css', __FILE__ ),
					array( 'magnific-popup', 'swiper-css' ),
					$style_asset_file['version']
				);
			}

		}


		public function register_block_assets() {

			$script_asset_file = include( plugin_dir_path( __FILE__ ) . 'dist/scripts/backend/block.asset.php' );
			$style_asset_file = include( plugin_dir_path( __FILE__ ) . 'dist/styles/frontend/index.asset.php' );
	
			wp_register_script(
				'wpz-insta_block-backend-script',
				plugins_url( 'dist/scripts/backend/block.js', __FILE__ ),
				$script_asset_file['dependencies'],
				$script_asset_file['version']
			);

		}

		/**
		 * Load widget specific styles.
		 */
		public function widget_styles() {

			global $post;
			$general_options    = get_option( 'wpzoom-instagram-general-settings' );

			$should_enqueue     = has_block( 'wpzoom/instagram-block' );
			$has_reusable_block = self::has_reusable_block( 'wpzoom/instagram-block' );
			$is_active_widget   = is_active_widget( false, false, 'wpzoom_instagram_widget', false );
			$has_shortcode      = ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'instagram' ) );
			$has_widget_block   = self::is_active_block_widget( 'wpzoom/instagram-block' ); 
			$load_css_js        = isset( $general_options['load-css-js'] ) ? true : false;

			if( is_admin() || $load_css_js || $should_enqueue || $has_reusable_block || $is_active_widget || $has_shortcode || $has_widget_block || isset( $_GET['wpz-insta-widget-preview'] ) ) {

                wp_enqueue_style(
                    'swiper-css',
                    plugin_dir_url( __FILE__ ) . 'dist/styles/library/swiper.css',
                    array(),
                    '7.4.1'
                );

				wp_enqueue_style(
					'wpz-insta_block-frontend-style',
					plugin_dir_url( __FILE__ ) . 'dist/styles/frontend/index.css',
					array( 'dashicons' ),
					WPZOOM_INSTAGRAM_VERSION
				);

				wp_enqueue_style(
					'magnific-popup',
					plugin_dir_url( __FILE__ ) . 'dist/styles/library/magnific-popup.css',
					array( 'dashicons' ),
					WPZOOM_INSTAGRAM_VERSION
				);

			}
		}

		/**
		 * Register widget specific scripts.
		 */
		public function register_widget_scripts() {
			wp_register_script(
				'zoom-instagram-widget-lazy-load',
				plugin_dir_url( __FILE__ ) . 'dist/scripts/library/lazy.js',
				array( 'jquery' ),
				filemtime( plugin_dir_path( __FILE__ ) . 'dist/scripts/library/lazy.js' ),
				true
			);

			wp_register_script(
				'magnific-popup',
				plugin_dir_url( __FILE__ ) . 'dist/scripts/library/magnific-popup.js',
				array( 'jquery', 'underscore', 'wp-util' ),
				filemtime( plugin_dir_path( __FILE__ ) . 'dist/scripts/library/magnific-popup.js' ),
				true
			);

			wp_register_script(
				'swiper-js',
				plugin_dir_url( __FILE__ ) . 'dist/scripts/library/swiper.js',
				array(),
				'7.0.0-alpha.21',
				true
			);

			wp_register_script(
				'zoom-instagram-widget',
				plugin_dir_url( __FILE__ ) . 'dist/scripts/frontend/index.js',
				array( 'jquery', 'underscore', 'wp-util', 'magnific-popup', 'swiper-js' ),
				WPZOOM_INSTAGRAM_VERSION,
				true
			);
		}

		/**
		 * Load widget specific scripts.
		 */
		public function enqueue_widget_scripts() {

			global $post;
			$general_options    = get_option( 'wpzoom-instagram-general-settings' );

			$should_enqueue     = has_block( 'wpzoom/instagram-block' );
			$has_reusable_block = self::has_reusable_block( 'wpzoom/instagram-block' );
			$is_active_widget   = is_active_widget( false, false, 'wpzoom_instagram_widget', false );
			$has_shortcode      = ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'instagram' ) );
			$has_widget_block   = self::is_active_block_widget( 'wpzoom/instagram-block' );
			$load_css_js        = isset( $general_options['load-css-js'] ) ? true : false;

			if( is_admin() || $load_css_js || $should_enqueue || $has_reusable_block || $is_active_widget || $has_shortcode || $has_widget_block || isset( $_GET['wpz-insta-widget-preview'] ) ) {
				wp_enqueue_script( 'zoom-instagram-widget-lazy-load' );
				wp_enqueue_script( 'magnific-popup' );
				wp_enqueue_script( 'swiper-js' );
				wp_enqueue_script( 'zoom-instagram-widget' );
				wp_enqueue_script( 'wpz-insta_block-frontend-script' );
			}

		}


		/**
		 * Check the widget block based area has the block
		 *
		 * @since  2.0.2
		 * @param  string      $block_name The block name.
		 * @return boolean     Return true if post content has provided block name as reusable block, else return false.
		 */
		public static function is_active_block_widget( $blockname ) {

			$allwidgets = [];

			$widget_blocks = get_option( 'widget_block' );
			$sidebars_widgets = get_option('sidebars_widgets');
		
			if( is_array( $sidebars_widgets ) ) {
				foreach ( $sidebars_widgets as $key => $value ) {
		
					if( is_array( $value ) ) {
						foreach ($value as $widget_id) {
							$pieces       = explode( '-', $widget_id );
							$multi_number = array_pop( $pieces );
							$id_base      = implode( '-', $pieces );
							$widget_data  = get_option( 'widget_' . $id_base );
							
							// Remove inactive widgets 
							if( $key != 'wp_inactive_widgets' ) {
								unset( $widget_data['_multiwidget'] );
								$allwidgets[ $key ] = $widget_data;
							}
						}
					}
				}
			}

			foreach( (array) $allwidgets as $widget ) {
				foreach( (array) $widget as $widget_element ) {
					foreach( (array)$widget_element as $value ) {
						if( is_string( $value ) && has_shortcode( $value, 'instagram' ) ) {
							return true;
						}
					}
				}

			}

			foreach( (array) $widget_blocks as $widget_block ) {
				if ( ! empty( $widget_block['content'] ) && ( has_block( $blockname, $widget_block['content'] ) || has_shortcode( $widget_block['content'], 'instagram' ) ) ) {
					return true;
				}
			}
			
			return false;

		}

		/**
		 * Check the post content has reusable block
		 *
		 * @since  2.0.2
		 * @param  string      $block_name The block name.
		 * @param  int         $post_id The post ID.
		 * @param  int         $reusable_block_id The reusable block post ID.
		 * @param  boolean|int $content The post content.
		 * @return boolean     Return true if post content has provided block name as reusable block, else return false.
		 */
		public static function has_reusable_block( $block_name, $post_id = 0, $reusable_block_id = 0, $content = '' ) {
			$has_reusable_block = false;
			$post_id            = $post_id > 0 ? $post_id : get_the_ID();

			/**
			 * Loop reusable blocks to get needed block
			 *
			 * @since 2.0.2
			 */
			if ( ! empty( self::get_reusable_block( absint( $reusable_block_id ) ) ) ) {
				$args  = array(
					'post_type'      => 'wp_block',
					'posts_per_page' => -1,
					'post_status'    => 'publish',
				);
				$query = new WP_Query( $args );

				while ( $query->have_posts() ) {
					$query->the_post();
					if ( absint( $reusable_block_id ) === get_the_ID() ) {
						$content = get_post_field( 'post_content', get_the_ID() );
						if ( has_block( $block_name, $content ) ) {
							$has_reusable_block = true;
							return $has_reusable_block;
						}
					}
				}

				// Reset global post variable. After this point, we are back to the Main Query object.
				wp_reset_postdata();
			}

			// Early return if $has_reusable_block is true.
			if ( true === $has_reusable_block ) {
				return;
			}

			if ( empty( $content ) ) {
				$content = get_post_field( 'post_content', $post_id );
			}

			if ( $content ) {
				if ( has_block( 'block', $content ) ) {
					// Check reusable blocks.
					$blocks = parse_blocks( $content );

					if ( ! is_array( $blocks ) || empty( $blocks ) ) {
						return false;
					}

					foreach ( $blocks as $block ) {
						if ( $block['blockName'] === 'core/block' && ! empty( $block['attrs']['ref'] ) ) {
							$reusable_block_id = absint( $block['attrs']['ref'] );

							if ( has_block( $block_name, $reusable_block_id ) ) {
								return true;
							} elseif ( ! empty( self::get_reusable_block( $reusable_block_id ) ) ) {
								return true;
							}
						}
					}
				} elseif ( has_block( $block_name, $content ) ) {
					return true;
				} elseif ( has_shortcode( $content, 'reblex' ) ) {
					return true;
				} else {
					return false;
				}
			}

			return false;
		}

		/**
		 * Get reusable block.
		 *
		 * @since 2.0.2
		 * @param int $id Reusable block id.
		 * @return string Reusable block post content.
		 */
		public static function get_reusable_block( $id ) {
			$post = '';

			if ( ! is_string( $id ) && $id > 0 ) {
				$wp_post = get_post( $id );
				if ( $wp_post instanceof WP_Post ) {
					$post = $wp_post->post_content;
				}
			}

			return $post;
		}

	}

}

WPZOOM_Instagram_Widget_Assets::instance();
