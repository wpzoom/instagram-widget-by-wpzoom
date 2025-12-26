<?php
namespace WPZOOMElementorInstagram;

use Elementor\Widget_Base;
use Elementor\Group_Control_Background;
use Elementor\Repeater;
use Elementor\Control_Media;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Css_Filter;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Plugin;
use Elementor\Utils;
use Elementor\Icons_Manager;
use Elementor\Modules\DynamicTags\Module as TagsModule;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * WPZOOM Elementor Recipe Card Widget
 *
 * Elementor widget that inserts a customizable recipe card.
 *
 * @since 1.0.0
 */
class WPZOOM_Instagram_Feed extends Widget_Base {

	/**
	 * @var Wpzoom_Instagram_Widget_Display
	 */
	protected $display;


	/**
	 * @var \WP_Query
	 */
	private $query = null;

	/**
	 * $post_type
	 * @var string
	 */
	private $post_type = 'wpz-insta_feed';	

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct( $data = array(), $args = null ) {
		parent::__construct( $data, $args );

		$this->display = \Wpzoom_Instagram_Widget_Display::getInstance();
	}

	/**
	 * Get widget name.
	 *
	 * Retrieve widget name.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'wpzoom-elementor-instagram-widget';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve widget title.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'WPZOOM Instagram Feed', 'instagram-widget-by-wpzoom' );
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve widget icon.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-instagram-gallery';
	}

	/**
	 * Get widget categories.
	 *
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return array( 'wpzoom-elementor-instagram' );
	}

	/**
	 * Get the query
	 *
	 * Returns the current query.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return \WP_Query The current query.
	 */
	public function get_query() {
		return $this->query;
	}

	/**
	 * Register Controls.
	 *
	 * Registers all the controls for this widget.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	protected function register_controls() {
		$this->register_content_controls();
	}

	/**
	 * Register Content Controls.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	protected function register_content_controls() {

		$this->start_controls_section(
			'_section_instagram_feed',
			array(
				'label' => esc_html__( 'Instagram Feed', 'instagram-widget-by-wpzoom' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'instagram_feed_id',
			array(
				'label'    => esc_html__( 'Select a Instagram Feed', 'instagram-widget-by-wpzoom' ),
				'type'     => Controls_Manager::SELECT2,
				'label_block' => true,
				'options'  => $this->get_instagram_feed_posts(),
			)
		);

		$this->end_controls_section();
	}
	

	/**
	 * Get instagram feed posts.
	 *
	 * Retrieve a list of all instagram feed posts.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array All instagram feed posts.
	 */
	protected function get_instagram_feed_posts() {

		$instagram_feed_posts = array();

		$args = array(
			'post_type'   => $this->post_type,
			'numberposts' => -1
		);

		$posts = get_posts( $args );

		if ( !empty( $posts ) && !is_wp_error( $posts ) ) {
			foreach ( $posts as $key => $post ) {
				if ( is_object( $post ) && property_exists( $post, 'ID' ) ) {
					$instagram_feed_posts[ $post->ID ] = get_the_title( $post );
				}
			}
		}

		return $instagram_feed_posts;

	}

	/**
	 * Render the Widget.
	 *
	 * Renders the widget on the frontend.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	protected function render() {

		$settings = $this->get_settings_for_display();
		$block_attributes = array();

		$feed_id = isset( $settings['instagram_feed_id'] ) ? $settings['instagram_feed_id'] : null;

		$output = '';

		if ( $feed_id > -1 ) {
			$output = $this->display->output_feed( $feed_id, false, $block_attributes );
		} else {
			$output = wp_kses_post( __( '<p class="error"><strong>Please select a feed to display...</strong></p>', 'instagram-widget-by-wpzoom' ) );
		}

		printf( 
			'<div class="wpzoom-custom-instagram-feed-post wpzoom-instagram-feed-shortcode" data-instagram-feed-post="%2$d">%1$s</div>',
			$output,
			intval( $feed_id )
		);

	}

}