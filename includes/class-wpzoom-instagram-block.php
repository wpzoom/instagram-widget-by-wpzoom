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
					'align' => array(
						'type'    => 'string',
						'default' => 'none',
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
						'title' => esc_html__( 'WPZOOM - Blocks', 'instagram-widget-by-wpzoom' ),
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
			return $this->display->output_feed( $feed_id, false, $block_attributes );
		} else {
			return wp_kses_post( __( '<p class="error"><strong>Please select a feed to display...</strong></p>', 'instagram-widget-by-wpzoom' ) );
		}
	}
}

Wpzoom_Instagram_Block::get_instance();
