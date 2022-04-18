<?php
/**
 * Exit if accessed directly.
 */
defined( 'ABSPATH' ) or die;

/**
 * WPZOOM Instagram Block class
 *
 * @package Wpzoom_Instagram_Block
 */
class Wpzoom_Instagram_Block {
	/**
	 * @var WPZOOM_Instagram_Widget_Settings The reference to *Singleton* instance of this class
	 *
	 * @since 1.8.4
	 */
	private static $instance;

	/**
	 * @var Wpzoom_Instagram_Widget_Display
	 */
	protected $display;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return WPZOOM_Instagram_Widget_Settings The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->display = Wpzoom_Instagram_Widget_Display::getInstance();

		add_action( 'init', array( $this, 'init' ) );
		add_filter( 'block_categories_all', array( $this, 'block_categories' ), 10, 2 );
	}

	/**
	 * Initialize the block.
	 */
	public function init() {
		$script_asset_file = include( plugin_dir_path( __FILE__ ) . 'dist/scripts/backend/block.asset.php' );
		$style_asset_file = include( plugin_dir_path( __FILE__ ) . 'dist/styles/frontend/block.asset.php' );

		wp_register_script(
			'wpz-insta_block-backend-script',
			plugins_url( 'dist/scripts/backend/block.js', __FILE__ ),
			$script_asset_file['dependencies'],
			$script_asset_file['version']
		);

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
			'7.0.0-alpha.21'
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

		wp_enqueue_style(
			'swiper-css',
			plugins_url( 'dist/styles/library/swiper.css', __FILE__ ),
			array(),
			'7.0.0-alpha.21'
		);

		wp_register_style(
			'wpz-insta_block-frontend-style',
			plugins_url( 'dist/styles/frontend/block.css', __FILE__ ),
			array( 'magnific-popup', 'swiper-css' ),
			$style_asset_file['version']
		);

		register_block_type(
			'wpzoom/instagram-block',
			array(
				'api_version'     => 2,
				'category'        => 'wpzoom-blocks',
				'editor_script'   => 'wpz-insta_block-backend-script',
				'script'          => 'wpz-insta_block-frontend-script',
				'style'           => 'wpz-insta_block-frontend-style',
				'render_callback' => array( $this, 'render' ),
				'attributes'      => array(
					'feed' => array(
						'type'    => 'integer',
						'default' => -1,
					),
				),
			)
		);
	}

	/**
	 * Add the WPZOOM block category if needed.
	 */
	public function block_categories( $categories ) {
		if ( empty( $categories ) || ( ! empty( $categories ) && is_array( $categories ) && ! in_array( 'wpzoom-blocks', wp_list_pluck( $categories, 'slug' ) ) ) ) {
			$categories = array_merge(
				$categories,
				array(
					array(
						'slug'  => 'wpzoom-blocks',
						'title' => __( 'WPZOOM - Blocks', 'instagram-widget-by-wpzoom' ),
					),
				)
			);
		}

		return $categories;
	}

	/**
	 * Render the block content.
	 */
	public function render( $block_attributes, $content ) {
		$feed_id = isset( $block_attributes['feed'] ) ? intval( $block_attributes['feed'] ) : -1;

		if ( $feed_id > -1 ) {
			return $this->display->output_feed( $feed_id, false );
		} else {
			return __( '<p class="error"><strong>Please select a feed to display...</strong></p>', 'instagram-widget-by-wpzoom' );
		}
	}
}

Wpzoom_Instagram_Block::get_instance();
