<?php
/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPZOOM_Instagram_Widget_Settings {
	/**
	 * @var WPZOOM_Instagram_Widget_Settings The reference to *Singleton* instance of this class
	 *
	 * @since 1.8.4
	 */
	private static $instance;

	/**
	 * Stores settings options
	 *
	 * @since 1.8.0
	 * @var array
	 */
	public static $settings = array();

	/**
	 * Settings option name
	 *
	 * @since 1.8.0
	 * @var string
	 */
	public static $option_name = 'wpzoom-instagram-widget-settings';

	/**
	 * If there are any registered users
	 *
	 * @since 2.0.0
	 * @var string
	 */
	public static $any_users = false;

	/**
	 * If there are any feeds
	 *
	 * @since 2.0.0
	 * @var string
	 */
	public static $any_feeds = false;

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
	 * Construct.
	 */
	public function __construct() {
		self::$settings = get_option( 'wpzoom-instagram-widget-settings', wpzoom_instagram_get_default_settings() );

		add_action( 'init', array( $this, 'init' ) );

		add_filter( 'views_edit-wpz-insta_feed', array( $this, 'views_filter' ) );
		add_filter( 'views_edit-wpz-insta_user', array( $this, 'views_filter' ) );

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'settings_init' ) );

		add_filter( 'plugin_action_links', array( $this, 'add_action_links' ), 10, 2 );

		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ), 9 );
	}

	public function init() {
		register_post_type(
			'wpz-insta_user',
			array(
				'label'               => __( 'User', 'instagram-widget-by-wpzoom' ),
				'description'         => __( 'Instagram User', 'instagram-widget-by-wpzoom' ),
				'labels'              => array(
					'name'                  => _x( 'Instagram User', 'Post Type General Name', 'instagram-widget-by-wpzoom' ),
					'singular_name'         => _x( 'User', 'Post Type Singular Name', 'instagram-widget-by-wpzoom' ),
					'menu_name'             => __( 'Instagram User', 'instagram-widget-by-wpzoom' ),
					'name_admin_bar'        => __( 'User', 'instagram-widget-by-wpzoom' ),
					'archives'              => __( 'User Archives', 'instagram-widget-by-wpzoom' ),
					'attributes'            => __( 'User Attributes', 'instagram-widget-by-wpzoom' ),
					'parent_item_colon'     => __( 'Parent User:', 'instagram-widget-by-wpzoom' ),
					'all_items'             => __( 'Instagram User', 'instagram-widget-by-wpzoom' ),
					'add_new_item'          => __( 'Add New User', 'instagram-widget-by-wpzoom' ),
					'add_new'               => __( 'Add New User', 'instagram-widget-by-wpzoom' ),
					'new_item'              => __( 'New User', 'instagram-widget-by-wpzoom' ),
					'edit_item'             => __( 'Edit User', 'instagram-widget-by-wpzoom' ),
					'update_item'           => __( 'Update User', 'instagram-widget-by-wpzoom' ),
					'view_item'             => __( 'View User', 'instagram-widget-by-wpzoom' ),
					'view_items'            => __( 'View Users', 'instagram-widget-by-wpzoom' ),
					'search_items'          => __( 'Search User', 'instagram-widget-by-wpzoom' ),
					'not_found'             => __( 'Not found', 'instagram-widget-by-wpzoom' ),
					'not_found_in_trash'    => __( 'Not found in Trash', 'instagram-widget-by-wpzoom' ),
					'featured_image'        => __( 'Featured Image', 'instagram-widget-by-wpzoom' ),
					'set_featured_image'    => __( 'Set featured image', 'instagram-widget-by-wpzoom' ),
					'remove_featured_image' => __( 'Remove featured image', 'instagram-widget-by-wpzoom' ),
					'use_featured_image'    => __( 'Use as featured image', 'instagram-widget-by-wpzoom' ),
					'insert_into_item'      => __( 'Insert into user', 'instagram-widget-by-wpzoom' ),
					'uploaded_to_this_item' => __( 'Uploaded to this user', 'instagram-widget-by-wpzoom' ),
					'items_list'            => __( 'Users list', 'instagram-widget-by-wpzoom' ),
					'items_list_navigation' => __( 'Users list navigation', 'instagram-widget-by-wpzoom' ),
					'filter_items_list'     => __( 'Filter users list', 'instagram-widget-by-wpzoom' ),
				),
				'supports'            => array(
					'title',
					'thumbnail',
					'custom-fields',
				),
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_admin_bar'   => false,
				'show_in_nav_menus'   => false,
				'can_export'          => false,
				'has_archive'         => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => true,
				'capability_type'     => 'post',
				'show_in_rest'        => true,
			)
		);

		remove_post_type_support( 'wpz-insta_user', 'revisions' );

		register_post_meta(
			'wpz-insta_user',
			'_wpz-insta_token',
			array(
				'single'       => true,
				'type'         => 'string',
				'default'      => '',
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_user',
			'_wpz-insta_account-type',
			array(
				'single'       => true,
				'type'         => 'string',
				'default'      => 'personal',
				'show_in_rest' => true,
			)
		);

		$users_count = wp_count_posts( 'wpz-insta_user' );
		self::$any_users = property_exists( $users_count, 'publish' ) ? intval( $users_count->publish ) > 0 : false;

		register_post_type(
			'wpz-insta_feed',
			array(
				'label'               => __( 'Feed', 'instagram-widget-by-wpzoom' ),
				'description'         => __( 'Instagram Feed', 'instagram-widget-by-wpzoom' ),
				'labels'              => array(
					'name'                  => _x( 'Instagram Widget', 'Post Type General Name', 'instagram-widget-by-wpzoom' ),
					'singular_name'         => _x( 'Feed', 'Post Type Singular Name', 'instagram-widget-by-wpzoom' ),
					'menu_name'             => __( 'Instagram Widget', 'instagram-widget-by-wpzoom' ),
					'name_admin_bar'        => __( 'Feed', 'instagram-widget-by-wpzoom' ),
					'archives'              => __( 'Feed Archives', 'instagram-widget-by-wpzoom' ),
					'attributes'            => __( 'Feed Attributes', 'instagram-widget-by-wpzoom' ),
					'parent_item_colon'     => __( 'Parent Feed:', 'instagram-widget-by-wpzoom' ),
					'all_items'             => __( 'Instagram Widget', 'instagram-widget-by-wpzoom' ),
					'add_new_item'          => __( 'Add New Feed', 'instagram-widget-by-wpzoom' ),
					'add_new'               => __( 'Add New Feed', 'instagram-widget-by-wpzoom' ),
					'new_item'              => __( 'New Feed', 'instagram-widget-by-wpzoom' ),
					'edit_item'             => __( 'Edit Feed', 'instagram-widget-by-wpzoom' ),
					'update_item'           => __( 'Update Feed', 'instagram-widget-by-wpzoom' ),
					'view_item'             => __( 'View Feed', 'instagram-widget-by-wpzoom' ),
					'view_items'            => __( 'View Feeds', 'instagram-widget-by-wpzoom' ),
					'search_items'          => __( 'Search Feed', 'instagram-widget-by-wpzoom' ),
					'not_found'             => __( 'Not found', 'instagram-widget-by-wpzoom' ),
					'not_found_in_trash'    => __( 'Not found in Trash', 'instagram-widget-by-wpzoom' ),
					'featured_image'        => __( 'Featured Image', 'instagram-widget-by-wpzoom' ),
					'set_featured_image'    => __( 'Set featured image', 'instagram-widget-by-wpzoom' ),
					'remove_featured_image' => __( 'Remove featured image', 'instagram-widget-by-wpzoom' ),
					'use_featured_image'    => __( 'Use as featured image', 'instagram-widget-by-wpzoom' ),
					'insert_into_item'      => __( 'Insert into feed', 'instagram-widget-by-wpzoom' ),
					'uploaded_to_this_item' => __( 'Uploaded to this feed', 'instagram-widget-by-wpzoom' ),
					'items_list'            => __( 'Feeds list', 'instagram-widget-by-wpzoom' ),
					'items_list_navigation' => __( 'Feeds list navigation', 'instagram-widget-by-wpzoom' ),
					'filter_items_list'     => __( 'Filter feeds list', 'instagram-widget-by-wpzoom' ),
				),
				'supports'            => array(
					'custom-fields',
				),
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => ( self::$any_users ? 'options-general.php' : '' ),
				'show_in_admin_bar'   => false,
				'show_in_nav_menus'   => false,
				'can_export'          => false,
				'has_archive'         => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => true,
				'capability_type'     => 'post',
				'show_in_rest'        => true,
			)
		);

		remove_post_type_support( 'wpz-insta_feed', 'revisions' );

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_user-id',
			array(
				'single'       => true,
				'type'         => 'integer',
				'default'      => -1,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_feed-title',
			array(
				'single'       => true,
				'type'         => 'string',
				'default'      => __( 'Feed Title', 'instagram-widget-by-wpzoom' ),
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_check-new-posts-interval-number',
			array(
				'single'       => true,
				'type'         => 'integer',
				'default'      => 1,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_check-new-posts-interval-suffix',
			array(
				'single'       => true,
				'type'         => 'integer',
				'default'      => 1,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_enable-request-timeout',
			array(
				'single'       => true,
				'type'         => 'boolean',
				'default'      => false,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_layout',
			array(
				'single'       => true,
				'type'         => 'integer',
				'default'      => 0,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_item-num',
			array(
				'single'       => true,
				'type'         => 'integer',
				'default'      => 9,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_col-num',
			array(
				'single'       => true,
				'type'         => 'integer',
				'default'      => 3,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_spacing-between',
			array(
				'single'       => true,
				'type'         => 'integer',
				'default'      => 10,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_spacing-between-suffix',
			array(
				'single'       => true,
				'type'         => 'integer',
				'default'      => 0,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_feed-width',
			array(
				'single'       => true,
				'type'         => 'integer',
				'default'      => 100,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_feed-width-suffix',
			array(
				'single'       => true,
				'type'         => 'integer',
				'default'      => 2,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_feed-height',
			array(
				'single'       => true,
				'type'         => 'integer',
				'default'      => 100,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_feed-height-suffix',
			array(
				'single'       => true,
				'type'         => 'integer',
				'default'      => 2,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_bg-color',
			array(
				'single'       => true,
				'type'         => 'string',
				'default'      => '',
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_spacing-around',
			array(
				'single'       => true,
				'type'         => 'integer',
				'default'      => 10,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_spacing-around-suffix',
			array(
				'single'       => true,
				'type'         => 'integer',
				'default'      => 0,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_font-size',
			array(
				'single'       => true,
				'type'         => 'integer',
				'default'      => 16,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_font-size-suffix',
			array(
				'single'       => true,
				'type'         => 'integer',
				'default'      => 0,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_lightbox',
			array(
				'single'       => true,
				'type'         => 'boolean',
				'default'      => true,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_show-overlay',
			array(
				'single'       => true,
				'type'         => 'boolean',
				'default'      => true,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_show-media-type-icons',
			array(
				'single'       => true,
				'type'         => 'boolean',
				'default'      => true,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_image-size',
			array(
				'single'       => true,
				'type'         => 'integer',
				'default'      => 600,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_hover-likes',
			array(
				'single'       => true,
				'type'         => 'boolean',
				'default'      => true,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_hover-link',
			array(
				'single'       => true,
				'type'         => 'boolean',
				'default'      => true,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_hover-caption',
			array(
				'single'       => true,
				'type'         => 'boolean',
				'default'      => false,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_hover-username',
			array(
				'single'       => true,
				'type'         => 'boolean',
				'default'      => false,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_hover-date',
			array(
				'single'       => true,
				'type'         => 'boolean',
				'default'      => false,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_hover-text-color',
			array(
				'single'       => true,
				'type'         => 'string',
				'default'      => '',
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_hover-bg-color',
			array(
				'single'       => true,
				'type'         => 'string',
				'default'      => '',
				'show_in_rest' => true,
			)
		);

		$feeds_count = wp_count_posts( 'wpz-insta_feed' );
		self::$any_feeds = property_exists( $feeds_count, 'publish' ) ? intval( $feeds_count->publish ) > 0 : false;

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_filter( 'admin_body_class', array( $this, 'admin_body_class_filter' ) );
		add_filter( 'parent_file', array( $this, 'parent_file_menu_filter' ) );
		add_filter( 'submenu_file', array( $this, 'submenu_filter' ) );
		add_filter( 'manage_wpz-insta_feed_posts_columns', array( $this, 'set_custom_edit_columns_feed' ) );
		add_filter( 'manage_wpz-insta_user_posts_columns', array( $this, 'set_custom_edit_columns_user' ) );
		add_filter( 'manage_edit-wpz-insta_feed_sortable_columns', array( $this, 'set_custom_edit_columns_sortable_feed' ) );
		add_filter( 'manage_edit-wpz-insta_user_sortable_columns', array( $this, 'set_custom_edit_columns_sortable_user' ) );
		add_filter( 'screen_options_show_screen', array( $this, 'disable_screen_options' ), 10, 2 );
		add_filter( 'hidden_meta_boxes', array( $this, 'hide_meta_boxes' ), 10, 3 );
		//add_filter( 'wp_insert_post_data', array( $this, 'insert_feed' ) );
		add_filter( 'view_mode_post_types', array( $this, 'view_mode_post_types' ) );
		add_action( 'manage_wpz-insta_feed_posts_custom_column' , array( $this, 'custom_column_feed' ), 10, 2 );
		add_action( 'manage_wpz-insta_user_posts_custom_column' , array( $this, 'custom_column_user' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'sort_column_query' ) );
		add_action( 'edit_form_top', array( $this, 'edit_feed_header' ) );
		add_action( 'edit_form_after_title', array( $this, 'edit_feed_content' ) );
		add_action( 'in_admin_footer', array( $this, 'page_footer' ) );
		add_action( 'wp_ajax_wpz-insta_connect-user', array( $this, 'ajax_connect_user' ) );
		add_action( 'save_post_wpz-insta_feed', array( $this, 'save_feed' ), 15, 3 );
		add_action( 'save_post_wpz-insta_user', array( $this, 'save_user' ), 15, 3 );
		add_action( 'quick_edit_custom_box', array( $this, 'user_quick_edit_box' ), 10, 3 );

		if ( current_user_can( 'manage_options' ) && isset( $_GET['wpz-insta-widget-preview'] ) ) {
			remove_action( 'admin_enqueue_scripts', 'wp_auth_check_load' );
			remove_filter( 'heartbeat_send', 'wp_auth_check' );
			remove_filter( 'heartbeat_nopriv_send', 'wp_auth_check' );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_preview_scripts' ), 999 );

			ob_start();

			add_action( 'shutdown', function() {
				$final = '';
				$levels = ob_get_level();

				for ( $i = 0; $i < $levels; $i++ ) {
					$final .= ob_get_clean();
				}

				echo apply_filters( 'final_output', $final );
			}, 0 );

			add_filter( 'final_output', array( $this, 'replace_preview_content' ) );
		}
	}

	static function is_wpzinsta_screen() {
		$screen = get_current_screen();

		if ( $screen instanceof WP_Screen ) {
			$screen_id = $screen->id;
			return 'wpz-insta_feed' == $screen_id || 'edit-wpz-insta_feed' == $screen_id || 'edit-wpz-insta_user' == $screen_id || 'settings_page_wpz-insta-support' == $screen_id || 'settings_page_wpz-insta-connect' == $screen_id;
		}
	}

	function admin_enqueue_scripts() {
		$post_type = get_post_type();

		if ( 'wpz-insta_feed' == $post_type || 'wpz-insta_user' == $post_type ) {
			wp_dequeue_script( 'autosave' );
		}
	}

	function admin_body_class_filter( $classes ) {
		return $classes . ( self::is_wpzinsta_screen() ? ' wpz-insta-admin' : '' );
	}

	function parent_file_menu_filter( $parent_file ) {
		global $submenu_file;

		if ( 'edit.php?post_type=wpz-insta_user' == $parent_file ) {
			$parent_file = 'options-general.php';
			$submenu_file = 'edit.php?post_type=wpz-insta_feed';
		}

		return $parent_file;
	}

	function submenu_filter( $submenu_file ) {
		global $plugin_page;

		if ( $plugin_page && 'wpz-insta-support' == $plugin_page ) {
			$submenu_file = 'edit.php?post_type=wpz-insta_feed';
		}

		remove_submenu_page( 'options-general.php', 'wpz-insta-support' );

		return $submenu_file;
	}

	function set_custom_edit_columns_feed( $columns ) {
		unset( $columns['cb'], $columns['date'] );

		$columns['wpz-insta_account'] = __( 'Show posts from', 'instagram-widget-by-wpzoom' );
		$columns['wpz-insta_actions'] = __( 'Actions', 'instagram-widget-by-wpzoom' );

		return $columns;
	}

	function set_custom_edit_columns_user( $columns ) {
		unset( $columns['cb'], $columns['title'], $columns['date'] );

		$columns['wpz-insta_account-username'] = __( 'Username', 'instagram-widget-by-wpzoom' );
		$columns['wpz-insta_account-type'] = __( 'Type', 'instagram-widget-by-wpzoom' );
		$columns['wpz-insta_account-photo'] = __( 'Picture', 'instagram-widget-by-wpzoom' );
		$columns['wpz-insta_account-bio'] = __( 'Bio', 'instagram-widget-by-wpzoom' );
		$columns['wpz-insta_account-token'] = __( 'Access Token', 'instagram-widget-by-wpzoom' );
		$columns['wpz-insta_account-feeds'] = __( 'Feeds', 'instagram-widget-by-wpzoom' );
		$columns['wpz-insta_account-id'] = __( 'ID', 'instagram-widget-by-wpzoom' );
		$columns['wpz-insta_actions'] = __( 'Actions', 'instagram-widget-by-wpzoom' );

		return $columns;
	}

	function set_custom_edit_columns_sortable_feed( $columns ) {
		$columns['wpz-insta_account'] = 'wpz-insta_account';

		return $columns;
	}

	function set_custom_edit_columns_sortable_user( $columns ) {
		$columns['wpz-insta_account-username'] = 'wpz-insta_account-username';
		$columns['wpz-insta_account-type'] = 'wpz-insta_account-type';
		$columns['wpz-insta_account-id'] = 'wpz-insta_account-id';

		return $columns;
	}

	function disable_screen_options( $show, $screen ) {
		return 'post' == $screen->base && ( 'wpz-insta_feed' == $screen->post_type || 'wpz-insta_user' == $screen->post_type ) ? false : $show;
	}

	function hide_meta_boxes( $hidden, $screen, $use_defaults ) {
		if ( 'post' == $screen->base && 'wpz-insta_feed' == $screen->post_type ) {
			$hidden[] = 'postcustom';
			$hidden[] = 'submitdiv';
			$hidden[] = 'slugdiv';
		}

		return $hidden;
	}

	function view_mode_post_types( $post_types ) {
		unset( $post_types['wpz-insta_feed'], $post_types['wpz-insta_user'] );

		return $post_types;
	}

	function user_quick_edit_box( $column_name, $post_type, $taxonomy ) {
		if ( 'wpz-insta_user' == $post_type ) {
			switch ( $column_name ) {
				case 'wpz-insta_account-photo' :
					$photo_id = get_post_thumbnail_id( get_the_ID() ) ?: -1;
					$photo_url = get_the_post_thumbnail_url( get_the_ID(), array( 100, 100 ) ) ?: plugins_url( '/dist/images/backend/user-avatar.jpg', __FILE__ );

					?><div class="wpz-insta_quick-edit"><ul class="wpz-insta_quick-edit-columns">
					<li>
						<strong><?php _e( 'Profile picture', 'instagram-widget-by-wpzoom' ); ?></strong>
						<input type="hidden" name="_thumbnail_id" id="wpz-insta_account-photo" value="<?php echo esc_attr( $photo_id ); ?>" />
						<ul class="wpz-insta_two-columns wpz-insta_two-columns-left-small">
							<li><img src="<?php echo esc_url( $photo_url ); ?>" height="100" width="100" alt="<?php esc_attr_e( 'Profile Photo', 'instagram-widget-by-wpzoom' ); ?>" class="wpz-insta_profile-photo" /></li>
							<li>
								<button type="button" id="wpz-insta_edit-account-photo" class="button button-primary"><?php _e( 'Change profile picture', 'instagram-widget-by-wpzoom' ); ?></button><br/>
								<button type="button" id="wpz-insta_reset-account-photo" class="button button-link wpz-insta_button-link"><?php _e( 'Reset picture', 'instagram-widget-by-wpzoom' ); ?></button>
							</li>
						</ul>
					</li><?php

					break;

				case 'wpz-insta_account-bio' :
					?><li>
						<label>
							<strong><?php _e( 'Bio', 'instagram-widget-by-wpzoom' ); ?></strong><br/>
							<textarea name="post_content" id="wpz-insta_user-bio" cols="35" rows="4"><?php echo esc_textarea( get_the_content() ); ?></textarea>
						</label>
					</li><?php

					break;

				case 'wpz-insta_account-token' :
					$raw_token = get_post_meta( get_the_ID(), '_wpz-insta_token', true );
					$token_expire = 'Saturday, June 12th, 2021'; // TODO: Fix this

					?><li>
						<label>
							<strong><?php _e( 'Access Token', 'instagram-widget-by-wpzoom' ); ?></strong><br/>
							<input type="text" name="_wpz-insta_token" id="wpz-insta_token" class="widefat wpz-insta_input wpz-insta_input-nobg" readonly value="<?php echo esc_attr( false !== $raw_token && ! empty( $raw_token ) ? $raw_token : '-1' ); ?>" />
						</label>

						<ul class="wpz-insta_two-columns">
							<li><?php printf( __( '<strong>Expires on:</strong> %s', 'instagram-widget-by-wpzoom' ), $token_expire ); ?></li>
							<li><button type="button" id="wpz-insta_reconnect" class="button button-primary"><?php _e( 'Re-connect', 'instagram-widget-by-wpzoom' ); ?></button></li>
						</ul>
					</li>
					</ul>

					<div class="wpz-insta_notice"><p><?php _e( 'Due to restrictions set by Instagram, Instagram Widget cannot import the profile photo and bio text for Personal accounts. <a href="#" target="_blank">Click here to learn more</a>.', 'instagram-widget-by-wpzoom' ); ?></p></div></div><?php

					break;
			}
		}
	}

	function custom_column_feed( $column, $post_id ) {
		switch ( $column ) {
			case 'wpz-insta_account' :
				$user_id = intval( get_post_meta( $post_id, '_wpz-insta_user-id', true ) );
				$user = $user_id > 0 ? get_post( $user_id ) : null;
				$user_display_name = $user instanceof WP_Post ? sprintf( '@%s', get_the_title( $user ) ) : __( '&mdash;', 'instagram-widget-by-wpzoom' );
				$user_edit_link = $user instanceof WP_Post ? get_edit_post_link( $user_id ) : false;

				if ( false !== $user_edit_link ) echo '<a href="' . $user_edit_link . '">';
				echo $user_display_name;
				if ( false !== $user_edit_link ) echo '</a>';

				break;

			case 'wpz-insta_actions' :
				?>
				<nav class="wpz-insta_actions-menu">
					<strong>&hellip;</strong>
					<ul class="wpz-insta_hidden">
						<?php if ( current_user_can( 'edit_post', $post_id ) ) { ?><li class="wpz-insta_actions-menu_edit-feed"><a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>"><?php _e( 'Edit feed', 'instagram-widget-by-wpzoom' ); ?></a></li><?php } ?>
						<li class="wpz-insta_actions-menu_duplicate-feed"><a href="<?php echo esc_url( '' ); ?>"><?php _e( 'Duplicate feed', 'instagram-widget-by-wpzoom' ); ?></a></li>
						<li class="wpz-insta_actions-menu_copy-shortcode"><a href="<?php echo esc_url( '' ); ?>"><?php _e( 'Copy shortcode', 'instagram-widget-by-wpzoom' ); ?></a></li>
						<li class="wpz-insta_actions-menu_divider"></li>
						<li class="wpz-insta_actions-menu_update-posts"><a href="<?php echo esc_url( '' ); ?>"><?php _e( 'Update posts', 'instagram-widget-by-wpzoom' ); ?></a></li>
						<li class="wpz-insta_actions-menu_clear-cache"><a href="<?php echo esc_url( '' ); ?>"><?php _e( 'Clear cache', 'instagram-widget-by-wpzoom' ); ?></a></li>
						<li class="wpz-insta_actions-menu_divider"></li>
						<?php if ( current_user_can( 'delete_post', $post_id ) ) { ?><li class="wpz-insta_actions-menu_delete"><a href="<?php echo esc_url( get_delete_post_link( $post_id, '', true ) ); ?>"><?php _e( 'Delete feed', 'instagram-widget-by-wpzoom' ); ?></a></li><?php } ?>
					</ul>
				</nav>
				<?php

				break;
		}
	}

	function custom_column_user( $column, $post_id ) {
		switch ( $column ) {
			case 'wpz-insta_account-username' :
				$user = $post_id > 0 ? get_post( $post_id ) : null;
				$user_display_name = $user instanceof WP_Post ? sprintf( '@%s', get_the_title( $user ) ) : __( '&mdash;', 'instagram-widget-by-wpzoom' );

				echo '<button type="button" class="button-link editinline"><strong>' . $user_display_name . '</strong></button>';

				break;

			case 'wpz-insta_account-type' :
				$user_type = ucwords( strtolower( esc_html( get_post_meta( $post_id, '_wpz-insta_account-type', true ) ?: __( 'None', 'instagram-widget-by-wpzoom' ) ) ) );

				echo $user_type;

				break;

			case 'wpz-insta_account-feeds' :
				$latest_feeds = get_posts( array( 'numberposts' => 1, 'author' => $post_id, 'post_status' => 'publish', 'post_type' => 'wpz-insta_feed' ) );
				$latest_feed = ! empty( $latest_feeds ) && is_array( $latest_feeds ) && $latest_feeds[0] instanceof WP_Post ? $latest_feeds[0]->ID : -1;

				if ( $latest_feed > 0 ) {
					echo '<a href="' . get_permalink( $latest_feed ) . '">' . get_the_title( $latest_feed ) . '</a>';
				} else {
					echo __( 'None', 'instagram-widget-by-wpzoom' );
				}

				break;

			case 'wpz-insta_account-id' :
				$user_posts = wp_count_posts( 'wpz-insta_user' );
				$user_amount = property_exists( $user_posts, 'publish' ) ? intval( $user_posts->publish ) : 0;
				$latest_user = get_posts( array( 'numberposts' => 1, 'orderby' => 'ID', 'post_status' => 'publish', 'post_type' => 'wpz-insta_user' ) );
				$last_id = ! empty( $latest_user ) && is_array( $latest_user ) && $latest_user[0] instanceof WP_Post ? $latest_user[0]->ID : 1;
				$scaled_id = ( $user_amount - 1 ) * ( $post_id - 1 ) / ( $last_id - 1 ) + 1;

				printf( '%03d', $scaled_id );

				break;

			case 'wpz-insta_actions' :
				$post_type = get_post_type( $post_id );
				?>
				<nav class="wpz-insta_actions-menu">
					<strong>&hellip;</strong>
					<ul class="wpz-insta_hidden">
						<?php if ( current_user_can( 'edit_post', $post_id ) ) { ?><li class="wpz-insta_actions-menu_edit-user"><button type="button" class="button-link editinline"><?php _e( 'Edit user', 'instagram-widget-by-wpzoom' ); ?></button></li><?php } ?>
						<li class="wpz-insta_actions-menu_divider"></li>
						<?php if ( current_user_can( 'delete_post', $post_id ) ) { ?><li class="wpz-insta_actions-menu_delete wpz-insta_actions-menu_delete-user"><a href="<?php echo esc_url( get_delete_post_link( $post_id, '', true ) ); ?>"><?php _e( 'Delete user', 'instagram-widget-by-wpzoom' ); ?></a></li><?php } ?>
					</ul>
				</nav>
				<?php

				break;
		}
	}

	function sort_column_query( $query ) {
		$orderby = $query->get( 'orderby' );

		switch ( $orderby ) {
			case 'wpz-insta_account' :
				$meta_query = array(
					'relation' => 'OR',
					array(
						'key' => '_wpz-insta_user-id',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key' => '_wpz-insta_user-id',
					),
				);

				$query->set( 'meta_query', $meta_query );
				$query->set( 'orderby', 'meta_value' );

				break;

			case 'wpz-insta_account-username' :
				$query->set( 'orderby', 'title' );

				break;

			case 'wpz-insta_account-type' :
				$meta_query = array(
					'relation' => 'OR',
					array(
						'key' => '_wpz-insta_account-type',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key' => '_wpz-insta_account-type',
					),
				);

				$query->set( 'meta_query', $meta_query );
				$query->set( 'orderby', 'meta_value' );

				break;

			case 'wpz-insta_account-id' :
				$query->set( 'orderby', 'id' );

				break;
		}
	}

	function validate_color( $color ) {
		return preg_match( '/^(\#[\da-f]{3}|\#[\da-f]{6}|rgba\(((\d{1,2}|1\d\d|2([0-4]\d|5[0-5]))\s*,\s*){2}((\d{1,2}|1\d\d|2([0-4]\d|5[0-5]))\s*)(,\s*(0\.\d+|1))\)|hsla\(\s*((\d{1,2}|[1-2]\d{2}|3([0-5]\d|60)))\s*,\s*((\d{1,2}|100)\s*%)\s*,\s*((\d{1,2}|100)\s*%)(,\s*(0\.\d+|1))\)|rgb\(((\d{1,2}|1\d\d|2([0-4]\d|5[0-5]))\s*,\s*){2}((\d{1,2}|1\d\d|2([0-4]\d|5[0-5]))\s*)|hsl\(\s*((\d{1,2}|[1-2]\d{2}|3([0-5]\d|60)))\s*,\s*((\d{1,2}|100)\s*%)\s*,\s*((\d{1,2}|100)\s*%)\))$/i', $color ) ? $color : '';
	}

	function edit_feed_header( $post ) {
		if ( 'wpz-insta_feed' == $post->post_type ) {
			$feed_title = get_post_meta( $post->ID, '_wpz-insta_feed-title', true );
			$user_id = intval( get_post_meta( $post->ID, '_wpz-insta_user-id', true ) );
			$user = $user_id > 0 ? get_post( $user_id ) : null;
			$disabled_class = $user instanceof WP_Post ? '' : 'class="disable"';

			?>
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wpz-insta_feed' ) ); ?>" class="wpz-insta_back-button">
				<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
					<path d="M19.9019 11H7.79167L13.3542 5.41L11.9412 4L3.98047 12L11.9412 20L13.3443 18.59L7.79167 13H19.9019V11Z" />
				</svg>

				<?php _e( 'Go to all feeds', 'instagram-widget-by-wpzoom' ); ?>
			</a>

			<header class="wpz-insta-wrap wpz-insta-wrap-sides wpz-insta_settings-header">
				<div class="wpz-insta-wrap-left">
					<h1 class="wpz-insta_settings-main-title wp-heading">
						<input type="text" name="post_title" size="30" value="<?php echo esc_attr( $feed_title ? $feed_title : __( 'Feed Title', 'instagram-widget-by-wpzoom' ) ); ?>" id="title" spellcheck="true" autocomplete="off" />
					</h1>

					<nav class="wpz-insta_settings-main-nav wpz-insta_feed-edit-nav">
						<ul>
							<li class="active"><a href="<?php echo esc_url( '#config' ); ?>"><?php _e( 'Configure', 'instagram-widget-by-wpzoom' ); ?></a></li>
							<li <?php echo $disabled_class; ?>><a href="<?php echo esc_url( '#design' ); ?>"><?php _e( 'Design', 'instagram-widget-by-wpzoom' ); ?></a></li>
							<li <?php echo $disabled_class; ?>><a href="<?php echo esc_url( '#embed' ); ?>"><?php _e( 'Embed', 'instagram-widget-by-wpzoom' ); ?></a></li>
						</ul>
					</nav>
				</div>

				<div id="submitpost" class="wpz-insta-wrap-right">
					<div id="major-publishing-actions">
						<div id="publishing-action">
							<span class="spinner"></span>
							<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wpz-insta_feed' ) ); ?>" class="button button-secondary button-large"><?php _e( 'Cancel', 'instagram-widget-by-wpzoom' ); ?></a>
							<input type="submit" name="save" id="publish" class="button button-primary button-large button-positive disabled" value="<?php _e( 'Save', 'instagram-widget-by-wpzoom' ); ?>" />
						</div>
					</div>
				</div>
			</header>
			<?php
		}
	}

	function edit_feed_content( $post ) {
		if ( 'wpz-insta_feed' == $post->post_type ) {
			$none_label = __( 'None', 'instagram-widget-by-wpzoom' );
			$user_id = intval( get_post_meta( $post->ID, '_wpz-insta_user-id', true ) );
			$user = $user_id > 0 ? get_post( $user_id ) : null;
			$user_edit_link = $user instanceof WP_Post ? get_edit_post_link( $user_id ) : false;
			$user_display_name = $user instanceof WP_Post ? sprintf( '@%s', get_the_title( $user ) ) : $none_label;
			$user_account_type = $user instanceof WP_Post ? ucwords( strtolower( get_post_meta( $user_id, '_wpz-insta_account-type', true ) ?: $none_label ) ) : $none_label;
			$raw_token = get_post_meta( $user_id, '_wpz-insta_token', true );
			$user_account_token = $user instanceof WP_Post ? ( false !== $raw_token && ! empty( $raw_token ) ? $raw_token : '-1' ) : '-1';
			$new_posts_interval_number = intval( get_post_meta( $post->ID, '_wpz-insta_check-new-posts-interval-number', true ) ?: 1 );
			$new_posts_interval_suffix = intval( get_post_meta( $post->ID, '_wpz-insta_check-new-posts-interval-suffix', true ) ?: 1 );
			$enable_request_timeout = boolval( get_post_meta( $post->ID, '_wpz-insta_enable-request-timeout', true ) ?: false );
			$feed_layout = intval( get_post_meta( $post->ID, '_wpz-insta_layout', true ) ?: 0 );
			$feed_items_num = intval( get_post_meta( $post->ID, '_wpz-insta_item-num', true ) ?: 9 );
			$feed_cols_num = intval( get_post_meta( $post->ID, '_wpz-insta_col-num', true ) ?: 3 );
			$feed_spacing_between = intval( get_post_meta( $post->ID, '_wpz-insta_spacing-between', true ) ?: 10 );
			$feed_spacing_between_suffix = intval( get_post_meta( $post->ID, '_wpz-insta_spacing-between-suffix', true ) ?: 0 );
			$feed_width = intval( get_post_meta( $post->ID, '_wpz-insta_feed-width', true ) ?: 100 );
			$feed_width_suffix = intval( get_post_meta( $post->ID, '_wpz-insta_feed-width-suffix', true ) ?: 2 );
			$feed_height = intval( get_post_meta( $post->ID, '_wpz-insta_feed-height', true ) ?: 100 );
			$feed_height_suffix = intval( get_post_meta( $post->ID, '_wpz-insta_feed-height-suffix', true ) ?: 2 );
			$feed_bg_color = $this->validate_color( get_post_meta( $post->ID, '_wpz-insta_bg-color', true ) ?: '' );
			$feed_spacing_around = intval( get_post_meta( $post->ID, '_wpz-insta_spacing-around', true ) ?: 10 );
			$feed_spacing_around_suffix = intval( get_post_meta( $post->ID, '_wpz-insta_spacing-around-suffix', true ) ?: 0 );
			$feed_font_size = intval( get_post_meta( $post->ID, '_wpz-insta_font-size', true ) ?: 16 );
			$feed_font_size_suffix = intval( get_post_meta( $post->ID, '_wpz-insta_font-size-suffix', true ) ?: 0 );
			$lightbox = boolval( get_post_meta( $post->ID, '_wpz-insta_lightbox', true ) ?: true );
			$show_overlay = boolval( get_post_meta( $post->ID, '_wpz-insta_show-overlay', true ) ?: true );
			$show_media_type_icons = boolval( get_post_meta( $post->ID, '_wpz-insta_show-media-type-icons', true ) ?: true );
			$image_size = intval( get_post_meta( $post->ID, '_wpz-insta_image-size', true ) ?: 600 );
			$feed_hover_likes = boolval( get_post_meta( $post->ID, '_wpz-insta_hover-likes', true ) ?: true );
			$feed_hover_link = boolval( get_post_meta( $post->ID, '_wpz-insta_hover-link', true ) ?: true );
			$feed_hover_caption = boolval( get_post_meta( $post->ID, '_wpz-insta_hover-caption', true ) ?: false );
			$feed_hover_username = boolval( get_post_meta( $post->ID, '_wpz-insta_hover-username', true ) ?: false );
			$feed_hover_date = boolval( get_post_meta( $post->ID, '_wpz-insta_hover-date', true ) ?: false );
			$feed_hover_text_color = $this->validate_color( get_post_meta( $post->ID, '_wpz-insta_hover-text-color', true ) ?: '' );
			$feed_hover_bg_color = $this->validate_color( get_post_meta( $post->ID, '_wpz-insta_hover-bg-color', true ) ?: '' );
			$feed_shortcode = sprintf( _x( '[instagram feed="%s"]', 'Instagram Feed Shortcode', 'instagram-widget-by-wpzoom' ), $post->ID );
			$all_users = get_posts( array(
				'numberposts' => -1,
				'post_type'   => 'wpz-insta_user',
			) );

			?>
			<div class="wpz-insta_tabs-content">
				<div class="wpz-insta_sidebar active show-pro">
					<div class="wpz-insta_sidebar-left">
						<div class="wpz-insta_sidebar-left-section-head">
							<label>
								<input type="checkbox" name="_wpz-insta_show-pro" id="wpz-insta_show-pro" value="1" checked class="preview-exclude" />
								<strong><?php _e( 'Show PRO options', 'instagram-widget-by-wpzoom' ); ?></strong>
							</label>
						</div>

						<div class="wpz-insta_sidebar-left-section active" data-id="#config">
							<div class="wpz-insta_sidebar-section wpz-insta_sidebar-section-account">
								<h4 class="wpz-insta_sidebar-section-title"><?php _e( 'Instagram Account', 'instagram-widget-by-wpzoom' ); ?></h4>
								<p class="wpz-insta_sidebar-section-description"><?php _e( 'Select an account', 'instagram-widget-by-wpzoom' ); ?></p>

								<div class="wpz-insta_feed-user-select<?php echo $user_id > 0 ? ' is-set' : ''; ?>">
									<div class="wpz-insta_feed-user-select-input">
										<input type="hidden" name="_wpz-insta_user-id" id="wpz-insta_user-id" value="<?php echo esc_attr( $user_id > 0 ? $user_id : '-1' ); ?>" />

										<div class="wpz-insta_feed-user-select-info">
											<div class="wpz-insta_feed-user-select-info-left">
												<h5 class="wpz-insta_feed-user-select-info-name"><?php echo esc_html( $user_display_name ); ?></h5>
												<p class="wpz-insta_feed-user-select-info-type"><?php echo esc_html( $user_account_type ); ?></p>
											</div>
											<div id="wpz-insta_feed-user-remove-btn" class="wpz-insta_feed-user-remove-button button button-secondary"><?php _e( 'Remove', 'instagram-widget-by-wpzoom' ); ?></div>
										</div>

										<div id="wpz-insta_feed-user-select-btn" class="wpz-insta_feed-user-select-button button button-primary"><?php _e( 'Select an Account', 'instagram-widget-by-wpzoom' ); ?></div>
									</div>

									<a href="<?php echo esc_url( $user_edit_link ); ?>" class="wpz-insta_feed-user-select-edit-link"><?php _e( 'Edit account details', 'instagram-widget-by-wpzoom' ); ?></a>
								</div>
							</div>

							<div class="wpz-insta_sidebar-section wpz-insta_sidebar-section-token<?php echo $user_id > 0 ? ' active' : ''; ?>">
								<h4 class="wpz-insta_sidebar-section-title"><?php _e( 'Access Token', 'instagram-widget-by-wpzoom' ); ?></h4>
								<p class="wpz-insta_sidebar-section-description"><?php _e( 'The Instagram Access Token is a long string of characters unique to your account that grants other applications access to your Instagram feed.', 'instagram-widget-by-wpzoom' ); ?></p>

								<input type="text" name="_wpz-insta_user-token" id="wpz-insta_user-token" value="<?php echo esc_attr( $user_account_token ); ?>" readonly />
							</div>

							<div class="wpz-insta_sidebar-section wpz-insta_sidebar-section-check-new<?php echo $user_id > 0 ? ' active' : ''; ?>">
								<h4 class="wpz-insta_sidebar-section-title"><?php _e( 'Check for new posts every', 'instagram-widget-by-wpzoom' ); ?></h4>

								<div class="wpz-insta_suffixed-number-input">
									<input type="number" name="_wpz-insta_check-new-posts-interval-number" id="wpz-insta_check-new-posts-interval-number" value="<?php echo esc_attr( $new_posts_interval_number ); ?>" min="1" max="100" step="1" />

									<select name="_wpz-insta_check-new-posts-interval-suffix" id="wpz-insta_check-new-posts-interval-suffix">
										<option value="0"<?php selected( $new_posts_interval_suffix, 0 ); ?>><?php _e( 'Hours', 'instagram-widget-by-wpzoom' ); ?></option>
										<option value="1"<?php selected( $new_posts_interval_suffix, 1 ); ?>><?php _e( 'Days', 'instagram-widget-by-wpzoom' ); ?></option>
										<option value="2"<?php selected( $new_posts_interval_suffix, 2 ); ?>><?php _e( 'Weeks', 'instagram-widget-by-wpzoom' ); ?></option>
										<option value="3"<?php selected( $new_posts_interval_suffix, 3 ); ?>><?php _e( 'Months', 'instagram-widget-by-wpzoom' ); ?></option>
									</select>
								</div>
							</div>

							<div class="wpz-insta_sidebar-section wpz-insta_sidebar-section-request-timeout<?php echo $user_id > 0 ? ' active' : ''; ?>">
								<label>
									<input type="hidden" name="_wpz-insta_enable-request-timeout" value="0" />
									<input type="checkbox" name="_wpz-insta_enable-request-timeout" id="wpz-insta_enable-request-timeout" value="1"<?php echo $enable_request_timeout ? ' checked' : ''; ?> />
									<strong><?php _e( 'Enable request timeout', 'instagram-widget-by-wpzoom' ); ?></strong>
								</label>
							</div>
						</div>

						<div class="wpz-insta_sidebar-left-section" data-id="#design">
							<div class="wpz-insta_sidebar-section wpz-insta_sidebar-section-layout">
								<h4 class="wpz-insta_sidebar-section-title"><?php _e( 'Layout', 'instagram-widget-by-wpzoom' ); ?></h4>

								<div class="wpz-insta_feed-layout wpz-insta_pro-only">
									<label class="wpz-insta_feed-layout-option wpz-insta_feed-layout-1">
										<input type="radio" name="_wpz-insta_layout" value="0"<?php echo 0 === $feed_layout ? ' checked' : ''; ?> />

										<svg width="92" height="110" viewBox="0 0 92 110" xmlns="http://www.w3.org/2000/svg">
											<rect x="0.5" y="0.5" width="91" height="109" rx="3.5" fill-opacity="0.2" stroke="currentColor" />
											<rect x="9" y="27" width="22" height="22" rx="2" />
											<rect x="9" y="53" width="22" height="22" rx="2" />
											<rect x="9" y="79" width="22" height="22" rx="2" />
											<rect x="35" y="27" width="22" height="22" rx="2" />
											<rect x="35" y="53" width="22" height="22" rx="2" />
											<rect x="35" y="79" width="22" height="22" rx="2" />
											<rect x="61" y="27" width="22" height="22" rx="2" />
											<rect x="61" y="53" width="22" height="22" rx="2" />
											<rect x="61" y="79" width="22" height="22" rx="2" />
											<path d="M37.6384 11.5646H39.3834C39.0926 9.70881 37.4861 8.41619 35.3948 8.41619C32.9204 8.41619 31.0645 10.2351 31.0645 13.282C31.0645 16.2734 32.8373 18.1293 35.4456 18.1293C37.7861 18.1293 39.4619 16.6243 39.4619 14.1868V13.0511H35.6256V14.3899H37.8046C37.7769 15.7379 36.8767 16.592 35.4548 16.592C33.8714 16.592 32.7865 15.4055 32.7865 13.2635C32.7865 11.1353 33.8898 9.95348 35.4179 9.95348C36.5581 9.95348 37.3337 10.5629 37.6384 11.5646ZM41.1181 18H42.8308V14.5376H44.5758L46.4316 18H48.3429L46.2978 14.2514C47.4103 13.8036 47.9966 12.8526 47.9966 11.5692C47.9966 9.7642 46.8333 8.54545 44.6635 8.54545H41.1181V18ZM42.8308 13.1158V9.97656H44.4004C45.6792 9.97656 46.2516 10.5629 46.2516 11.5692C46.2516 12.5756 45.6792 13.1158 44.4096 13.1158H42.8308ZM51.2859 8.54545H49.5732V18H51.2859V8.54545ZM56.3444 18C59.2251 18 60.9332 16.218 60.9332 13.2635C60.9332 10.3182 59.2251 8.54545 56.409 8.54545H53.1405V18H56.3444ZM54.8532 16.5181V10.0273H56.3121C58.2325 10.0273 59.2343 11.0984 59.2343 13.2635C59.2343 15.4379 58.2325 16.5181 56.2613 16.5181H54.8532Z" />
										</svg>
									</label>

									<fieldset class="wpz-insta_feed-layout-pro">
										<legend><?php _e( 'PRO', 'instagram-widget-by-wpzoom' ); ?></legend>

										<label class="wpz-insta_feed-layout-option wpz-insta_feed-layout-2">
											<input type="radio" name="_wpz-insta_layout" value="1"<?php echo 1 === $feed_layout ? ' checked' : ''; ?> />

											<svg width="92" height="110" viewBox="0 0 92 110" xmlns="http://www.w3.org/2000/svg">
												<rect x="0.5" y="0.5" width="91" height="109" rx="3.5" fill-opacity="0.2" stroke="currentColor" />
												<rect x="9" y="27" width="22" height="46" rx="2" />
												<rect x="9" y="79" width="22" height="22" rx="2" />
												<rect x="35" y="27" width="22" height="22" rx="2" />
												<rect x="35" y="53" width="22" height="22" rx="2" />
												<rect x="35" y="79" width="48" height="22" rx="2" />
												<rect x="61" y="27" width="22" height="22" rx="2" />
												<rect x="61" y="53" width="22" height="22" rx="2" />
												<path d="M13.7216 8.54545V18H15.3651V11.4908H15.4528L18.0657 17.9723H19.2937L21.9066 11.5046H21.9943V18H23.6378V8.54545H21.5419L18.7351 15.3963H18.6243L15.8175 8.54545H13.7216ZM26.717 18L27.4972 15.6687H31.0518L31.8366 18H33.6648L30.3317 8.54545H28.2173L24.8888 18H26.717ZM27.9588 14.293L29.2376 10.4844H29.3114L30.5902 14.293H27.9588ZM39.8959 11.1445H41.5439C41.5116 9.54723 40.1498 8.41619 38.137 8.41619C36.1519 8.41619 34.6608 9.53338 34.6654 11.2045C34.6654 12.5618 35.6302 13.342 37.186 13.7436L38.2616 14.0206C39.2819 14.2791 39.9559 14.5977 39.9605 15.3317C39.9559 16.1396 39.1895 16.6797 38.0724 16.6797C37.0013 16.6797 36.1704 16.1996 36.1011 15.207H34.4161C34.4854 17.0859 35.8841 18.1431 38.0862 18.1431C40.3529 18.1431 41.6686 17.0121 41.6732 15.3455C41.6686 13.7067 40.316 12.9588 38.8941 12.6218L38.0077 12.4002C37.2322 12.2156 36.392 11.8878 36.4012 11.0984C36.4058 10.3874 37.0429 9.86577 38.1139 9.86577C39.1341 9.86577 39.8081 10.3413 39.8959 11.1445ZM51.6483 13.2727C51.6483 10.2166 49.7832 8.41619 47.3042 8.41619C44.8205 8.41619 42.96 10.2166 42.96 13.2727C42.96 16.3242 44.8205 18.1293 47.3042 18.1293C49.7832 18.1293 51.6483 16.3288 51.6483 13.2727ZM49.9263 13.2727C49.9263 15.424 48.8461 16.592 47.3042 16.592C45.7669 16.592 44.682 15.424 44.682 13.2727C44.682 11.1214 45.7669 9.95348 47.3042 9.95348C48.8461 9.95348 49.9263 11.1214 49.9263 13.2727ZM61.037 8.54545H59.3335V14.9901H59.2504L54.8002 8.54545H53.2675V18H54.9802V11.56H55.0587L59.5136 18H61.037V8.54545ZM62.9032 18H64.6159V14.5376H66.361L68.2168 18H70.128L68.0829 14.2514C69.1955 13.8036 69.7818 12.8526 69.7818 11.5692C69.7818 9.7642 68.6184 8.54545 66.4487 8.54545H62.9032V18ZM64.6159 13.1158V9.97656H66.1855C67.4643 9.97656 68.0368 10.5629 68.0368 11.5692C68.0368 12.5756 67.4643 13.1158 66.1948 13.1158H64.6159ZM70.3438 8.54545L73.7831 14.473V18H75.4912V14.473L78.9305 8.54545H76.9962L74.6833 12.728H74.591L72.2781 8.54545H70.3438Z" />
											</svg>
										</label>

										<label class="wpz-insta_feed-layout-option wpz-insta_feed-layout-3">
											<input type="radio" name="_wpz-insta_layout" value="2"<?php echo 2 === $feed_layout ? ' checked' : ''; ?> />

											<svg width="92" height="110" viewBox="0 0 92 110" xmlns="http://www.w3.org/2000/svg">
												<rect x="0.5" y="0.5" width="91" height="109" rx="3.5" fill-opacity="0.2" stroke="currentColor" />
												<rect x="9" y="27" width="48" height="46" rx="2" />
												<rect x="9" y="79" width="22" height="22" rx="2" />
												<rect x="35" y="79" width="22" height="22" rx="2" />
												<rect x="61" y="27" width="22" height="22" rx="2" />
												<rect x="61" y="53" width="22" height="22" rx="2" />
												<rect x="61" y="79" width="22" height="22" rx="2" />
												<path d="M11.5683 18H13.281V13.9837H17.662V18H19.3794V8.54545H17.662V12.5479H13.281V8.54545H11.5683V18ZM22.9548 8.54545H21.2421V18H22.9548V8.54545ZM31.1433 11.5646H32.8883C32.5975 9.70881 30.9909 8.41619 28.8997 8.41619C26.4252 8.41619 24.5694 10.2351 24.5694 13.282C24.5694 16.2734 26.3422 18.1293 28.9505 18.1293C31.291 18.1293 32.9668 16.6243 32.9668 14.1868V13.0511H29.1305V14.3899H31.3095C31.2818 15.7379 30.3816 16.592 28.9597 16.592C27.3762 16.592 26.2914 15.4055 26.2914 13.2635C26.2914 11.1353 27.3947 9.95348 28.9228 9.95348C30.063 9.95348 30.8386 10.5629 31.1433 11.5646ZM34.623 18H36.3357V13.9837H40.7167V18H42.434V8.54545H40.7167V12.5479H36.3357V8.54545H34.623V18ZM44.2968 18H50.1736V16.5643H46.0095V8.54545H44.2968V18ZM53.3728 8.54545H51.6601V18H53.3728V8.54545ZM61.5613 11.5646H63.3063C63.0154 9.70881 61.4089 8.41619 59.3176 8.41619C56.8432 8.41619 54.9874 10.2351 54.9874 13.282C54.9874 16.2734 56.7601 18.1293 59.3684 18.1293C61.709 18.1293 63.3848 16.6243 63.3848 14.1868V13.0511H59.5485V14.3899H61.7275C61.6998 15.7379 60.7995 16.592 59.3777 16.592C57.7942 16.592 56.7093 15.4055 56.7093 13.2635C56.7093 11.1353 57.8127 9.95348 59.3407 9.95348C60.481 9.95348 61.2566 10.5629 61.5613 11.5646ZM65.0409 18H66.7536V13.9837H71.1347V18H72.852V8.54545H71.1347V12.5479H66.7536V8.54545H65.0409V18ZM74.2993 9.98118H77.2215V18H78.9204V9.98118H81.8426V8.54545H74.2993V9.98118Z" />
											</svg>
										</label>

										<label class="wpz-insta_feed-layout-option wpz-insta_feed-layout-4">
											<input type="radio" name="_wpz-insta_layout" value="3"<?php echo 3 === $feed_layout ? ' checked' : ''; ?> />

											<svg width="92" height="110" viewBox="0 0 92 110" xmlns="http://www.w3.org/2000/svg">
												<rect x="0.5" y="0.5" width="91" height="109" rx="3.5" fill-opacity="0.2" stroke="currentColor" />
												<rect x="21" y="38" width="50" height="50" rx="2" />
												<path d="M79.3333 67.5846L83.4999 63.0013L79.3333 58.418" stroke-width="1.5" />
												<path d="M12.6667 58.4154L8.50008 62.9987L12.6667 67.582" stroke-width="1.5" />
												<path d="M20.1688 11.7354C19.8779 9.63494 18.2622 8.41619 16.1663 8.41619C13.6965 8.41619 11.836 10.2166 11.836 13.2727C11.836 16.3242 13.6734 18.1293 16.1663 18.1293C18.4145 18.1293 19.9056 16.6751 20.1688 14.87L18.4422 14.8608C18.2252 15.9688 17.3204 16.592 16.1894 16.592C14.6567 16.592 13.558 15.4425 13.558 13.2727C13.558 11.1399 14.6475 9.95348 16.194 9.95348C17.3435 9.95348 18.2437 10.6044 18.4422 11.7354H20.1688ZM22.8371 18L23.6173 15.6687H27.172L27.9568 18H29.7849L26.4518 8.54545H24.3374L21.009 18H22.8371ZM24.0789 14.293L25.3577 10.4844H25.4316L26.7103 14.293H24.0789ZM31.0302 18H32.7429V14.5376H34.4879L36.3438 18H38.255L36.2099 14.2514C37.3224 13.8036 37.9087 12.8526 37.9087 11.5692C37.9087 9.7642 36.7454 8.54545 34.5756 8.54545H31.0302V18ZM32.7429 13.1158V9.97656H34.3125C35.5913 9.97656 36.1637 10.5629 36.1637 11.5692C36.1637 12.5756 35.5913 13.1158 34.3217 13.1158H32.7429ZM47.9334 13.2727C47.9334 10.2166 46.0684 8.41619 43.5893 8.41619C41.1056 8.41619 39.2452 10.2166 39.2452 13.2727C39.2452 16.3242 41.1056 18.1293 43.5893 18.1293C46.0684 18.1293 47.9334 16.3288 47.9334 13.2727ZM46.2115 13.2727C46.2115 15.424 45.1312 16.592 43.5893 16.592C42.052 16.592 40.9672 15.424 40.9672 13.2727C40.9672 11.1214 42.052 9.95348 43.5893 9.95348C45.1312 9.95348 46.2115 11.1214 46.2115 13.2727ZM55.5125 8.54545V14.5792C55.5125 15.7656 54.6862 16.6151 53.3889 16.6151C52.0871 16.6151 51.2654 15.7656 51.2654 14.5792V8.54545H49.5526V14.7223C49.5526 16.7536 51.0761 18.1431 53.3889 18.1431C55.6926 18.1431 57.2252 16.7536 57.2252 14.7223V8.54545H55.5125ZM64.225 11.1445H65.873C65.8407 9.54723 64.4789 8.41619 62.4661 8.41619C60.481 8.41619 58.9899 9.53338 58.9945 11.2045C58.9945 12.5618 59.9593 13.342 61.5151 13.7436L62.5907 14.0206C63.611 14.2791 64.285 14.5977 64.2896 15.3317C64.285 16.1396 63.5186 16.6797 62.4015 16.6797C61.3304 16.6797 60.4995 16.1996 60.4302 15.207H58.7452C58.8145 17.0859 60.2132 18.1431 62.4153 18.1431C64.682 18.1431 65.9977 17.0121 66.0023 15.3455C65.9977 13.7067 64.6451 12.9588 63.2232 12.6218L62.3368 12.4002C61.5613 12.2156 60.7211 11.8878 60.7303 11.0984C60.7349 10.3874 61.372 9.86577 62.443 9.86577C63.4632 9.86577 64.1373 10.3413 64.225 11.1445ZM67.5292 18H73.7153V16.5643H69.2419V13.9837H73.3598V12.5479H69.2419V9.98118H73.6784V8.54545H67.5292V18ZM75.4257 18H81.3025V16.5643H77.1384V8.54545H75.4257V18Z" />
											</svg>
										</label>
									</fieldset>
								</div>

								<div class="wpz-insta_feed-layout-etc wpz-insta_table">
									<label class="wpz-insta_table-row">
										<strong class="wpz-insta_table-cell"><?php _e( 'Number of photos', 'instagram-widget-by-wpzoom' ); ?></strong>
										<div class="wpz-insta_table-cell"><input type="number" name="_wpz-insta_item-num" value="<?php echo esc_attr( $feed_items_num ); ?>" size="3" min="1" max="100" step="1" /></div>
									</label>

									<label class="wpz-insta_table-row">
										<strong class="wpz-insta_table-cell"><?php _e( 'Number of columns', 'instagram-widget-by-wpzoom' ); ?></strong>
										<div class="wpz-insta_table-cell"><input type="number" name="_wpz-insta_col-num" value="<?php echo esc_attr( $feed_cols_num ); ?>" size="3" min="1" max="100" step="1" /></div>
									</label>

									<label class="wpz-insta_table-row">
										<strong class="wpz-insta_table-cell"><?php _e( 'Padding around images', 'instagram-widget-by-wpzoom' ); ?></strong>
										<div class="wpz-insta_table-cell">
											<div class="wpz-insta_suffixed-number-input">
												<input type="number" name="_wpz-insta_spacing-between" value="<?php echo esc_attr( $feed_spacing_between ); ?>" size="3" min="1" max="100" step="1" />

												<select name="_wpz-insta_spacing-between-suffix">
													<option value="0"<?php selected( $feed_spacing_between_suffix, 0 ); ?>><?php _e( 'px', 'instagram-widget-by-wpzoom' ); ?></option>
													<option value="1"<?php selected( $feed_spacing_between_suffix, 1 ); ?>><?php _e( 'em', 'instagram-widget-by-wpzoom' ); ?></option>
													<option value="2"<?php selected( $feed_spacing_between_suffix, 2 ); ?>><?php _e( '%', 'instagram-widget-by-wpzoom' ); ?></option>
												</select>
											</div>
										</div>
									</label>
								</div>
							</div>

							<div class="wpz-insta_sidebar-section wpz-insta_sidebar-section-layout">
								<h4 class="wpz-insta_sidebar-section-title"><?php _e( 'Feed', 'instagram-widget-by-wpzoom' ); ?></h4>

								<div class="wpz-insta_feed-general wpz-insta_table">
									<label class="wpz-insta_table-row">
										<strong class="wpz-insta_table-cell"><?php _e( 'Width', 'instagram-widget-by-wpzoom' ); ?></strong>
										<div class="wpz-insta_table-cell">
											<div class="wpz-insta_suffixed-number-input">
												<input type="number" name="_wpz-insta_feed-width" value="<?php echo esc_attr( $feed_width ); ?>" size="3" min="1" max="100" step="1" />

												<select name="_wpz-insta_feed-width-suffix">
													<option value="0"<?php selected( $feed_width_suffix, 0 ); ?>><?php _e( 'px', 'instagram-widget-by-wpzoom' ); ?></option>
													<option value="1"<?php selected( $feed_width_suffix, 1 ); ?>><?php _e( 'em', 'instagram-widget-by-wpzoom' ); ?></option>
													<option value="2"<?php selected( $feed_width_suffix, 2 ); ?>><?php _e( '%', 'instagram-widget-by-wpzoom' ); ?></option>
												</select>
											</div>
										</div>
									</label>

									<label class="wpz-insta_table-row">
										<strong class="wpz-insta_table-cell"><?php _e( 'Height', 'instagram-widget-by-wpzoom' ); ?></strong>
										<div class="wpz-insta_table-cell">
											<div class="wpz-insta_suffixed-number-input">
												<input type="number" name="_wpz-insta_feed-height" value="<?php echo esc_attr( $feed_height ); ?>" size="3" min="1" max="100" step="1" />

												<select name="_wpz-insta_feed-height-suffix">
													<option value="0"<?php selected( $feed_height_suffix, 0 ); ?>><?php _e( 'px', 'instagram-widget-by-wpzoom' ); ?></option>
													<option value="1"<?php selected( $feed_height_suffix, 1 ); ?>><?php _e( 'em', 'instagram-widget-by-wpzoom' ); ?></option>
													<option value="2"<?php selected( $feed_height_suffix, 2 ); ?>><?php _e( '%', 'instagram-widget-by-wpzoom' ); ?></option>
												</select>
											</div>
										</div>
									</label>

									<label class="wpz-insta_table-row">
										<strong class="wpz-insta_table-cell"><?php _e( 'Background color', 'instagram-widget-by-wpzoom' ); ?></strong>
										<div class="wpz-insta_table-cell"><input type="text" name="_wpz-insta_bg-color" value="<?php echo esc_attr( $feed_bg_color ); ?>" size="8" class="wpz-insta_color-picker" /></div>
									</label>

									<label class="wpz-insta_table-row">
										<input type="hidden" name="_wpz-insta_lightbox" value="0" />
										<input type="checkbox" name="_wpz-insta_lightbox" value="1"<?php echo $lightbox ? ' checked' : ''; ?> />
										<strong><?php _e( 'Show lightbox on click', 'instagram-widget-by-wpzoom' ); ?></strong>
									</label>

									<label class="wpz-insta_table-row">
										<input type="hidden" name="_wpz-insta_show-overlay" value="0" />
										<input type="checkbox" name="_wpz-insta_show-overlay" value="1"<?php echo $show_overlay ? ' checked' : ''; ?> />
										<strong><?php _e( 'Show overlay on hover', 'instagram-widget-by-wpzoom' ); ?></strong>
									</label>

									<label class="wpz-insta_table-row">
										<input type="hidden" name="_wpz-insta_show-media-type-icons" value="0" />
										<input type="checkbox" name="_wpz-insta_show-media-type-icons" value="1"<?php echo $show_media_type_icons ? ' checked' : ''; ?> />
										<strong><?php _e( 'Show media type icons', 'instagram-widget-by-wpzoom' ); ?></strong>
									</label>

									<label class="wpz-insta_table-row">
										<strong class="wpz-insta_table-cell"><?php _e( 'Image size', 'instagram-widget-by-wpzoom' ); ?></strong>
										<div class="wpz-insta_table-cell">
											<input type="number" name="_wpz-insta_image-size" value="<?php echo esc_attr( $image_size ); ?>" size="3" min="1" max="1000" step="1" class="widefat" />
										</div>
									</label>

									<label class="wpz-insta_table-row">
										<strong class="wpz-insta_table-cell"><?php _e( 'Outside padding', 'instagram-widget-by-wpzoom' ); ?></strong>
										<div class="wpz-insta_table-cell">
											<div class="wpz-insta_suffixed-number-input">
												<input type="number" name="_wpz-insta_spacing-around" value="<?php echo esc_attr( $feed_spacing_around ); ?>" size="3" min="1" max="100" step="1" />

												<select name="_wpz-insta_spacing-around-suffix">
													<option value="0"<?php selected( $feed_spacing_around_suffix, 0 ); ?>><?php _e( 'px', 'instagram-widget-by-wpzoom' ); ?></option>
													<option value="1"<?php selected( $feed_spacing_around_suffix, 1 ); ?>><?php _e( 'em', 'instagram-widget-by-wpzoom' ); ?></option>
													<option value="2"<?php selected( $feed_spacing_around_suffix, 2 ); ?>><?php _e( '%', 'instagram-widget-by-wpzoom' ); ?></option>
												</select>
											</div>
										</div>
									</label>

									<label class="wpz-insta_table-row">
										<strong class="wpz-insta_table-cell"><?php _e( 'Text size', 'instagram-widget-by-wpzoom' ); ?></strong>
										<div class="wpz-insta_table-cell">
											<div class="wpz-insta_suffixed-number-input">
												<input type="number" name="_wpz-insta_font-size" value="<?php echo esc_attr( $feed_font_size ); ?>" size="3" min="1" max="100" step="1" />

												<select name="_wpz-insta_font-size-suffix">
													<option value="0"<?php selected( $feed_font_size_suffix, 0 ); ?>><?php _e( 'px', 'instagram-widget-by-wpzoom' ); ?></option>
													<option value="1"<?php selected( $feed_font_size_suffix, 1 ); ?>><?php _e( 'em', 'instagram-widget-by-wpzoom' ); ?></option>
													<option value="2"<?php selected( $feed_font_size_suffix, 2 ); ?>><?php _e( 'pt', 'instagram-widget-by-wpzoom' ); ?></option>
												</select>
											</div>
										</div>
									</label>

									<div class="wpz-insta_show-on-hover">
										<strong><?php _e( 'Show on hover', 'instagram-widget-by-wpzoom' ); ?></strong>

										<div class="wpz-insta_table">
											<label class="wpz-insta_table-row">
												<input type="hidden" name="_wpz-insta_hover-likes" value="0" />
												<input type="checkbox" name="_wpz-insta_hover-likes" value="1"<?php echo $feed_hover_likes ? ' checked' : ''; ?> />
												<span><?php _e( 'Likes &amp; comments', 'instagram-widget-by-wpzoom' ); ?></span>
											</label>

											<label class="wpz-insta_table-row">
												<input type="hidden" name="_wpz-insta_hover-link" value="0" />
												<input type="checkbox" name="_wpz-insta_hover-link" value="1"<?php echo $feed_hover_link ? ' checked' : ''; ?> />
												<span><?php _e( 'Instagram link', 'instagram-widget-by-wpzoom' ); ?></span>
											</label>

											<fieldset class="wpz-insta_feed-hover-pro wpz-insta_pro-only">
												<legend><strong><?php _e( 'PRO', 'instagram-widget-by-wpzoom' ); ?></strong></legend>

												<label class="wpz-insta_table-row">
													<input type="hidden" name="_wpz-insta_hover-caption" value="0" />
													<input type="checkbox" name="_wpz-insta_hover-caption" value="1"<?php echo $feed_hover_caption ? ' checked' : ''; ?> disabled />
													<span><?php _e( 'Caption', 'instagram-widget-by-wpzoom' ); ?></span>
												</label>

												<label class="wpz-insta_table-row">
													<input type="hidden" name="_wpz-insta_hover-username" value="0" />
													<input type="checkbox" name="_wpz-insta_hover-username" value="1"<?php echo $feed_hover_username ? ' checked' : ''; ?> disabled />
													<span><?php _e( 'Username', 'instagram-widget-by-wpzoom' ); ?></span>
												</label>

												<label class="wpz-insta_table-row">
													<input type="hidden" name="_wpz-insta_hover-date" value="0" />
													<input type="checkbox" name="_wpz-insta_hover-date" value="1"<?php echo $feed_hover_date ? ' checked' : ''; ?> disabled />
													<span><?php _e( 'Date', 'instagram-widget-by-wpzoom' ); ?></span>
												</label>

												<label class="wpz-insta_table-row">
													<strong class="wpz-insta_table-cell"><?php _e( 'Hover text color', 'instagram-widget-by-wpzoom' ); ?></strong>
													<div class="wpz-insta_table-cell"><input type="text" name="_wpz-insta_hover-text-color" value="<?php echo esc_attr( $feed_hover_text_color ); ?>" size="8" class="wpz-insta_color-picker" disabled /></div>
												</label>

												<label class="wpz-insta_table-row">
													<strong class="wpz-insta_table-cell"><?php _e( 'Background color', 'instagram-widget-by-wpzoom' ); ?></strong>
													<div class="wpz-insta_table-cell"><input type="text" name="_wpz-insta_hover-bg-color" value="<?php echo esc_attr( $feed_hover_bg_color ); ?>" size="8" class="wpz-insta_color-picker" disabled /></div>
												</label>
											</fieldset>
										</div>
									</div>
								</div>
							</div>
						</div>

						<div class="wpz-insta_sidebar-left-section" data-id="#embed">
							<h4 class="wpz-insta_sidebar-section-big-title"><?php _e( 'Display your Feed', 'instagram-widget-by-wpzoom' ); ?></h4>

							<div class="wpz-insta_sidebar-section wpz-insta_sidebar-section-shortcode no-top-border">
								<h5 class="wpz-insta_sidebar-section-title smaller-title"><?php _e( 'Shortcode', 'instagram-widget-by-wpzoom' ); ?></h5>
								<p class="wpz-insta_sidebar-section-description"><?php _e( 'Copy and paste the following shortcode directly into the page, post or widget where you&rsquo;d like the feed to show up:', 'instagram-widget-by-wpzoom' ); ?></p>

								<div class="wpz-insta_text-btn-aligned">
									<input type="text" id="wpz-insta_shortcode" value="<?php echo esc_attr( $feed_shortcode ); ?>" readonly class="wpz-insta_shortcode" />
									<span id="wpz-insta_shortcode-copy-btn" class="button button-secondary wpz-insta_shortcode-copy-btn">
										<span class="wpz-insta_shortcode-copy-btn-text-normal"><?php _e( 'COPY', 'instagram-widget-by-wpzoom' ); ?></span>
										<span class="wpz-insta_shortcode-copy-btn-text-success"><?php _e( 'COPIED!', 'instagram-widget-by-wpzoom' ); ?></span>
									</span>
								</div>
							</div>

							<div class="wpz-insta_sidebar-section wpz-insta_sidebar-section-wp-block">
								<h5 class="wpz-insta_sidebar-section-title smaller-title"><?php _e( 'WordPress Block', 'instagram-widget-by-wpzoom' ); ?></h5>
								<div class="wpz-insta_sidebar-section-description">
									<p><?php _e( 'To embed this feed in the WordPress block editor:', 'instagram-widget-by-wpzoom' ); ?></p>
									<ol>
										<li><?php _e( 'Search for the <strong>Instagram Widget</strong> block.', 'instagram-widget-by-wpzoom' ); ?></li>
										<li><?php _e( 'Add it to your post or page.', 'instagram-widget-by-wpzoom' ); ?></li>
										<li><?php _e( 'Since this is your only feed, <strong>Instagram Widget</strong> will automatically show this feed.', 'instagram-widget-by-wpzoom' ); ?></li>
									</ol>
								</div>
							</div>

							<div class="wpz-insta_sidebar-section wpz-insta_sidebar-section-wp-widget">
								<h5 class="wpz-insta_sidebar-section-title smaller-title"><?php _e( 'WordPress Widget', 'instagram-widget-by-wpzoom' ); ?></h5>
								<div class="wpz-insta_sidebar-section-description">
									<p><?php _e( 'To embed this feed as a WordPress widget:', 'instagram-widget-by-wpzoom' ); ?></p>
									<ol>
										<li><?php printf( __( 'Go to the <a href="%s">Appearance &raquo; Widgets</a> page or the <a href="%s">Widgets</a> section of the Customizer.', 'instagram-widget-by-wpzoom' ), esc_attr( admin_url( 'widgets.php' ) ), esc_attr( admin_url( 'customize.php?autofocus[panel]=widgets' ) ) ); ?></li>
										<li><?php _e( 'Then, add a <strong>Instagram Widget</strong>.', 'instagram-widget-by-wpzoom' ); ?></li>
										<li><?php _e( 'In the widget&rsquo;s settings, choose the WPZ Insta feed as the feed to be shown.', 'instagram-widget-by-wpzoom' ); ?></li>
									</ol>
								</div>
							</div>
						</div>
					</div>

					<div class="wpz-insta_sidebar-right">
						<div class="wpz-insta_widget-preview">
							<div class="wpz-insta_widget-preview-header">
								<h3 class="wpz-insta_widget-preview-header-title"><?php _e( 'Preview', 'instagram-widget-by-wpzoom' ); ?></h3>

								<ul id="wpz-insta_widget-preview-links" class="wpz-insta_widget-preview-header-links">
									<li class="wpz-insta_widget-preview-header-link wpz-insta_widget-preview-header-links-desktop active" title="<?php _e( 'Desktop', 'instagram-widget-by-wpzoom' ); ?>">
										<span class="screen-reader-text"><?php _e( 'Desktop', 'instagram-widget-by-wpzoom' ); ?></span>
										<svg width="24" height="16" viewBox="0 0 24 16" xmlns="http://www.w3.org/2000/svg">
											<path d="M20 14C21.1 14 21.99 13.1 21.99 12L22 2C22 0.9 21.1 0 20 0H4C2.9 0 2 0.9 2 2V12C2 13.1 2.9 14 4 14H0V16H24V14H20ZM4 2H20V12H4V2Z"/>
										</svg>
									</li>

									<li class="wpz-insta_widget-preview-header-link wpz-insta_widget-preview-header-links-tablet" title="<?php _e( 'Tablet', 'instagram-widget-by-wpzoom' ); ?>">
										<span class="screen-reader-text"><?php _e( 'Tablet', 'instagram-widget-by-wpzoom' ); ?></span>
										<svg width="19" height="24" viewBox="0 0 19 24" xmlns="http://www.w3.org/2000/svg">
											<path d="M16.5 0H2.5C1.12 0 0 1.12 0 2.5V21.5C0 22.88 1.12 24 2.5 24H16.5C17.88 24 19 22.88 19 21.5V2.5C19 1.12 17.88 0 16.5 0ZM9.5 23C8.67 23 8 22.33 8 21.5C8 20.67 8.67 20 9.5 20C10.33 20 11 20.67 11 21.5C11 22.33 10.33 23 9.5 23ZM17 19H2V3H17V19Z"/>
										</svg>
									</li>

									<li class="wpz-insta_widget-preview-header-link wpz-insta_widget-preview-header-links-mobile" title="<?php _e( 'Mobile', 'instagram-widget-by-wpzoom' ); ?>">
										<span class="screen-reader-text"><?php _e( 'Mobile', 'instagram-widget-by-wpzoom' ); ?></span>
										<svg width="14" height="22" viewBox="0 0 14 22" xmlns="http://www.w3.org/2000/svg">
											<path d="M11.99 0.00999999L1.99 0C0.89 0 0 0.9 0 2V20C0 21.1 0.89 22 1.99 22H11.99C13.09 22 13.99 21.1 13.99 20V2C13.99 0.9 13.09 0.00999999 11.99 0.00999999ZM11.99 18H1.99V4H11.99V18Z"/>
										</svg>
									</li>
								</ul>
							</div>

							<div class="wpz-insta_widget-preview-view wpz-insta_widget-preview-size-desktop">
								<div id="wpz-insta_widget-preview-view" class="wpz-insta_widget-preview-view-inner">
									<iframe src="<?php echo esc_url( site_url( '?wpz-insta-widget-preview=true' ) ); ?>"></iframe>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div id="wpz-insta_tabs-config-cnnct" class="wpz-insta_tabs-config-connect">
					<h2 class="wpz-insta_tabs-config-connect-title"><?php _e( 'Select an Account', 'instagram-widget-by-wpzoom' ); ?></h2>
					<p class="wpz-insta_tabs-config-connect-description"><?php _e( 'Show posts from this account:', 'instagram-widget-by-wpzoom' ); ?></p>

					<ul class="wpz-insta_tabs-config-connect-accounts">
						<?php foreach ( $all_users as $user ) :
							$user_id = $user->ID;
							$user_name = sprintf( '@%s', get_the_title( $user ) );
							$user_type = ucwords( strtolower( esc_html( get_post_meta( $user_id, '_wpz-insta_account-type', true ) ?: $none_label ) ) );
							$raw_token = get_post_meta( $user_id, '_wpz-insta_token', true );
							$user_token = sanitize_text_field( false !== $raw_token && ! empty( $raw_token ) ? $raw_token : '-1' );

							?>
							<li data-user-id="<?php echo esc_attr( $user_id ); ?>" data-user-name="<?php echo esc_attr( $user_name ); ?>" data-user-type="<?php echo esc_attr( $user_type ); ?>" data-user-token="<?php echo esc_attr( $user_token ); ?>">
								<h3><?php echo $user_name; ?></h3>
								<p><?php echo $user_type; ?></p>
							</li>
						<?php endforeach; ?>
					</ul>

					<hr/>

					<h3 class="wpz-insta_tabs-config-connect-subtitle"><?php _e( 'Or add another account&hellip;', 'instagram-widget-by-wpzoom' ); ?></h3>
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wpz-insta_user' ) ); ?>" class="wpz-insta_tabs-config-connect-add button button-primary disabled"><?php _e( 'Add New Account', 'instagram-widget-by-wpzoom' ); ?></a>
				</div>
			</div>
			<?php
		}
	}

	function enqueue_preview_scripts() {
		wp_enqueue_style(
			'zoom-instagram-widget-preview',
			plugin_dir_url( dirname( __FILE__ ) . '/instagram-widget-by-wpzoom.php' ) . 'dist/styles/frontend/preview.css',
			array(),
			'2.0.0'
		);
	}

	function preview_frame() {
		the_widget(
			'Wpzoom_New_Instagram_Widget',
			array(
				'preview' => array(
					'user-id'                         => isset( $_GET['_wpz-insta_user-id'] ) ? intval( $_GET['_wpz-insta_user-id'] ) : -1,
					'check-new-posts-interval-number' => isset( $_GET['_wpz-insta_check-new-posts-interval-number'] ) ? intval( $_GET['_wpz-insta_check-new-posts-interval-number'] ) : 1,
					'check-new-posts-interval-suffix' => isset( $_GET['_wpz-insta_check-new-posts-interval-suffix'] ) ? intval( $_GET['_wpz-insta_check-new-posts-interval-suffix'] ) : 1,
					'enable-request-timeout'          => isset( $_GET['_wpz-insta_enable-request-timeout'] ) ? boolval( $_GET['_wpz-insta_enable-request-timeout'] ) : false,
					'layout'                          => isset( $_GET['_wpz-insta_layout'] ) ? intval( $_GET['_wpz-insta_layout'] ) : 0,
					'item-num'                        => isset( $_GET['_wpz-insta_item-num'] ) ? intval( $_GET['_wpz-insta_item-num'] ) : 9,
					'col-num'                         => isset( $_GET['_wpz-insta_col-num'] ) ? intval( $_GET['_wpz-insta_col-num'] ) : 3,
					'spacing-between'                 => isset( $_GET['_wpz-insta_spacing-between'] ) ? intval( $_GET['_wpz-insta_spacing-between'] ) : -1,
					'spacing-between-suffix'          => isset( $_GET['_wpz-insta_spacing-between-suffix'] ) ? intval( $_GET['_wpz-insta_spacing-between-suffix'] ) : 0,
					'feed-width'                      => isset( $_GET['_wpz-insta_feed-width'] ) ? intval( $_GET['_wpz-insta_feed-width'] ) : 100,
					'feed-width-suffix'               => isset( $_GET['_wpz-insta_feed-width-suffix'] ) ? intval( $_GET['_wpz-insta_feed-width-suffix'] ) : 2,
					'feed-height'                     => isset( $_GET['_wpz-insta_feed-height'] ) ? intval( $_GET['_wpz-insta_feed-height'] ) : -1,
					'feed-height-suffix'              => isset( $_GET['_wpz-insta_feed-height-suffix'] ) ? intval( $_GET['_wpz-insta_feed-height-suffix'] ) : 0,
					'bg-color'                        => isset( $_GET['_wpz-insta_bg-color'] ) ? $this->validate_color( $_GET['_wpz-insta_bg-color'] ) : '',
					'spacing-around'                  => isset( $_GET['_wpz-insta_spacing-around'] ) ? intval( $_GET['_wpz-insta_spacing-around'] ) : -1,
					'spacing-around-suffix'           => isset( $_GET['_wpz-insta_spacing-around-suffix'] ) ? intval( $_GET['_wpz-insta_spacing-around-suffix'] ) : 0,
					'font-size'                       => isset( $_GET['_wpz-insta_font-size'] ) ? intval( $_GET['_wpz-insta_font-size'] ) : -1,
					'font-size-suffix'                => isset( $_GET['_wpz-insta_font-size-suffix'] ) ? intval( $_GET['_wpz-insta_font-size-suffix'] ) : 0,
					'lightbox'                        => isset( $_GET['_wpz-insta_lightbox'] ) ? boolval( $_GET['_wpz-insta_lightbox'] ) : true,
					'show-overlay'                    => isset( $_GET['_wpz-insta_show-overlay'] ) ? boolval( $_GET['_wpz-insta_show-overlay'] ) : true,
					'show-media-type-icons'           => isset( $_GET['_wpz-insta_show-media-type-icons'] ) ? boolval( $_GET['_wpz-insta_show-media-type-icons'] ) : true,
					'image-size'                      => isset( $_GET['_wpz-insta_image-size'] ) ? intval( $_GET['_wpz-insta_image-size'] ) : 600,
					'hover-likes'                     => isset( $_GET['_wpz-insta_hover-likes'] ) ? boolval( $_GET['_wpz-insta_hover-likes'] ) : true,
					'hover-link'                      => isset( $_GET['_wpz-insta_hover-link'] ) ? boolval( $_GET['_wpz-insta_hover-link'] ) : true,
					'hover-caption'                   => isset( $_GET['_wpz-insta_hover-caption'] ) ? boolval( $_GET['_wpz-insta_hover-caption'] ) : false,
					'hover-username'                  => isset( $_GET['_wpz-insta_hover-username'] ) ? boolval( $_GET['_wpz-insta_hover-username'] ) : false,
					'hover-date'                      => isset( $_GET['_wpz-insta_hover-date'] ) ? boolval( $_GET['_wpz-insta_hover-date'] ) : false,
					'hover-text-color'                => isset( $_GET['_wpz-insta_hover-text-color'] ) ? $this->validate_color( $_GET['_wpz-insta_hover-text-color'] ) : '',
					'hover-bg-color'                  => isset( $_GET['_wpz-insta_hover-bg-color'] ) ? $this->validate_color( $_GET['_wpz-insta_hover-bg-color'] ) : '',
				)
			)
		);
	}

	public function get_preview_frame() {
		ob_start();
		$this->preview_frame();
		return ob_get_clean();
	}

	function replace_preview_content( $output ) {
		if ( preg_match( '/^(.*?)<body([^>]*)>(?:.*?)\s*((?:(?:(?:<script[^>]*>(?:.*?)<\/script>)|(?:<link[^>]*>))\s*)*)\s*<\/body>(.*)$/is', $output, $matches ) ) {
			$widget_output = WPZOOM_Instagram_Widget_Settings::get_instance()->get_preview_frame();
			$output = $matches[1] . '<body ' . $matches[2] . '>' . $widget_output . $matches[3] . '</body>' . $matches[4];
		}

		return $output;
	}

	function page_footer() {
		$current_screen = get_current_screen();

		if ( 'wpz-insta_feed' == $current_screen->post_type || 'wpz-insta_user' == $current_screen->post_type || 'settings_page_wpz-insta-support' == $current_screen->id || 'settings_page_wpz-insta-connect' == $current_screen->id ) {
			?>
			<footer class="wpz-insta_settings-footer">
				<div class="wpz-insta_settings-footer-wrap">
					<h3 class="wpz-insta_settings-footer-logo"><a href="https://wpzoom.com/" target="_blank" title="<?php _e( 'WPZOOM - WordPress themes with modern features and professional support', 'instagram-widget-by-wpzoom' ); ?>"><?php _e( 'WPZOOM', 'instagram-widget-by-wpzoom' ); ?></a></h3>

					<ul class="wpz-insta_settings-footer-links">
						<li class="wpz-insta_settings-footer-links-themes"><a href="https://wpzoom.com/themes/" target="_blank" title="<?php _e( 'Check out our themes', 'instagram-widget-by-wpzoom' ); ?>"><?php _e( 'Themes', 'instagram-widget-by-wpzoom' ); ?></a></li>
						<li class="wpz-insta_settings-footer-links-blog"><a href="https://wpzoom.com/blog/" target="_blank" title="<?php _e( 'See the latest updates on our blog', 'instagram-widget-by-wpzoom' ); ?>"><?php _e( 'Blog', 'instagram-widget-by-wpzoom' ); ?></a></li>
						<li class="wpz-insta_settings-footer-links-support"><a href="https://wpzoom.com/support/" target="_blank" title="<?php _e( 'Get support', 'instagram-widget-by-wpzoom' ); ?>"><?php _e( 'Support', 'instagram-widget-by-wpzoom' ); ?></a></li>
					</ul>
				</div>
			</footer>
			<?php
		}
	}

	public function views_filter( $views ) {
		$current_page = isset( $_GET['post_type'] ) ? $_GET['post_type'] : 'wpz-insta_feed';

		?>
		<header class="wpz-insta-wrap wpz-insta_settings-header">
			<h1 class="wpz-insta_settings-main-title wp-heading">
				<?php
				printf(
					__( 'Instagram Widget <small>by <a href="%s" target="_blank" title="WPZOOM - WordPress themes with modern features and professional support">WPZOOM</a></small>', 'instagram-widget-by-wpzoom' ),
					esc_url( 'https://wpzoom.com' )
				);
				?>
			</h1>

			<nav class="wpz-insta_settings-main-nav">
				<ul>
					<li <?php echo 'wpz-insta_feed' == $current_page ? 'class="active"' : ''; ?>><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wpz-insta_feed' ) ); ?>"><?php _e( 'Feeds', 'instagram-widget-by-wpzoom' ); ?></a></li>
					<li <?php echo 'wpz-insta_user' == $current_page ? 'class="active"' : ''; ?>><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wpz-insta_user' ) ); ?>"><?php _e( 'Users', 'instagram-widget-by-wpzoom' ); ?></a></li>
					<li <?php echo 'support' == $current_page ? 'class="active"' : ''; ?>><a href="<?php echo esc_url( admin_url( 'options-general.php?page=wpz-insta-support' ) ); ?>"><?php _e( 'Support', 'instagram-widget-by-wpzoom' ); ?></a></li>
				</ul>
			</nav>
		</header>

		<div class="wpz-insta-wrap wpz-insta_settings-add-new">
			<?php if ( 'wpz-insta_feed' == $current_page ) : ?>
				<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wpz-insta_feed' ) ); ?>" class="button-primary"><?php _e( 'Add new feed', 'instagram-widget-by-wpzoom' ); ?></a>
			<?php else : ?>
				<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wpz-insta_user' ) ); ?>" class="button-primary<?php echo self::$any_users ? ' disabled' : ''; ?>"><?php _e( 'Add new user', 'instagram-widget-by-wpzoom' ); ?></a>
			<?php endif; ?>
		</div>
		<?php

		return array();
	}

	public function add_action_links( $links, $file ) {
		if ( $file != plugin_basename( dirname( __FILE__ ) . '/instagram-widget-by-wpzoom.php' ) ) {
			return $links;
		}

		$settings_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			menu_page_url( 'wpzoom-instagram-widget', false ),
			esc_html__( 'Settings', 'instagram-widget-by-wpzoom' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	public function ajax_connect_user() {
		if ( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], 'ajax-nonce' ) && isset( $_POST['token'] ) && ! empty( $_POST['token'] ) ) {
			$token = sanitize_text_field( $_POST['token'] );

			if ( ! empty( $token ) ) {
				$info = Wpzoom_Instagram_Widget_API::get_basic_user_info_from_token( $token );

				if ( false !== $info && is_object( $info ) && property_exists( $info, 'username' ) && property_exists( $info, 'account_type' ) ) {
					$user = wp_strip_all_tags( $info->username );
					$insert_post = wp_insert_post( array(
						'post_title'  => $user,
						'post_type'   => 'wpz-insta_user',
						'post_status' => 'publish',
					), true );

					if ( ! is_wp_error( $insert_post ) ) {
						update_post_meta( $insert_post, '_wpz-insta_token', $token );
						update_post_meta( $insert_post, '_wpz-insta_account-type', sanitize_text_field( $info->account_type ) );

						if ( property_exists( $info, 'profile_picture' ) && ! empty( $info->profile_picture ) ) {
							$this->generate_featured_image( $info->profile_picture, $insert_post, $user );
						}

						wp_send_json_success( null, 200 );
					}
				}
			}
		}

		wp_send_json_error( null, 500 );
	}

	/*function insert_feed( $post ) {
		if ( 'wpz-insta_feed' == $post['post_type'] ) {
			$post['post_status'] = 'publish';
		}

		return $post;
	}*/

	public function save_feed( int $post_ID, WP_Post $post, bool $update ) {
		if ( ! wp_is_post_revision( $post ) && ! wp_is_post_autosave( $post ) && 'auto-draft' != get_post_status( $post ) && isset( $_POST ) && ! empty( $_POST ) ) {
			$meta_keys = get_registered_meta_keys( 'post', 'wpz-insta_feed' );

			if ( ! empty( $meta_keys ) ) {
				$meta_keys = array_filter( $meta_keys, function( $key ) { return strpos( $key, 'wpz-insta_' ) !== false; }, ARRAY_FILTER_USE_KEY );

				foreach ( $meta_keys as $key => $args ) {
					if ( isset( $_POST[ $key ] ) ) {
						$value = wp_unslash( $_POST[ $key ] );

						switch ( $args['type'] ) {
							case 'integer':
								$value = intval( $value );
								break;

							case 'boolean':
								$value = boolval( $value );
								break;

							default:
								$value = sanitize_textarea_field( $value );
								break;
						}

						update_post_meta( $post_ID, $key, $value );
					} else {
						update_post_meta( $post_ID, $key, $args['default'] );
					}
				}
			}
		}
	}

	public function save_user( int $post_ID, WP_Post $post, bool $update ) {
		if ( ! wp_is_post_revision( $post ) && ! wp_is_post_autosave( $post ) && 'auto-draft' != get_post_status( $post ) && isset( $_POST ) && ! empty( $_POST ) ) {
			$meta_keys = get_registered_meta_keys( 'post', 'wpz-insta_user' );

			if ( ! empty( $meta_keys ) ) {
				$meta_keys = array_filter( $meta_keys, function( $key ) { return strpos( $key, 'wpz-insta_' ) !== false; }, ARRAY_FILTER_USE_KEY );

				foreach ( $meta_keys as $key => $args ) {
					if ( isset( $_POST[ $key ] ) ) {
						$value = wp_unslash( $_POST[ $key ] );

						switch ( $args['type'] ) {
							case 'integer':
								$value = intval( $value );
								break;

							case 'boolean':
								$value = boolval( $value );
								break;

							default:
								$value = sanitize_textarea_field( $value );
								break;
						}

						update_post_meta( $post_ID, $key, $value );
					} else {
						update_post_meta( $post_ID, $key, $args['default'] );
					}
				}
			}
		}
	}

	public function generate_featured_image( $file, $post_id, $desc ) {
		$file = esc_url_raw( trim( $file ) );
		preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );

		if ( ! $matches ) {
			return false;
		}

		$file_array = array();
		$file_array['name'] = basename( $matches[0] );
		$file_array['tmp_name'] = download_url( $file );

		if ( is_wp_error( $file_array['tmp_name'] ) ) {
			return false;
		}

		$id = media_handle_sideload( $file_array, $post_id, $desc );

		if ( is_wp_error( $id ) ) {
			@unlink( $file_array['tmp_name'] );
			return $id;
		}

		return set_post_thumbnail( $post_id, $id );
	}

	public function add_admin_menu() {
		if ( ! self::$any_users ) {
			add_submenu_page(
				'options-general.php',
				'Instagram Widget',
				'Instagram Widget',
				'manage_options',
				'wpz-insta-connect',
				array( $this, 'connect_page' )
			);
		} else {
			add_submenu_page(
				'options-general.php',
				'Instagram Widget Support',
				'Instagram Widget Support',
				'manage_options',
				'wpz-insta-support',
				array( $this, 'support_page' )
			);
		}
	}

	public function settings_init() {
		register_setting(
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings',
			array( $this, 'sanitize' )
		);

		add_settings_section(
			'wpzoom-instagram-widget-settings-general',
			null,
			'__return_false',
			'wpzoom-instagram-widget-settings-group'
		);

		add_settings_section(
			'wpzoom-instagram-widget-settings-user-info',
			__( 'User Details (optional)', 'instagram-widget-by-wpzoom' ),
			array( $this, 'settings_field_user_details' ),
			'wpzoom-instagram-widget-settings-group'
		);

		add_settings_field(
			'wpzoom-instagram-widget-user-info-avatar',
			__( 'Profile Picture', 'instagram-widget-by-wpzoom' ),
			array( $this, 'settings_field_user_info_avatar' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-user-info'
		);

		add_settings_field(
			'wpzoom-instagram-widget-user-info-fullname',
			__( 'Your Name', 'instagram-widget-by-wpzoom' ),
			array( $this, 'settings_field_user_info_fullname' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-user-info'
		);

		add_settings_field(
			'wpzoom-instagram-widget-user-info-biography',
			__( 'Bio', 'instagram-widget-by-wpzoom' ),
			array( $this, 'settings_field_user_info_biography' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-user-info'
		);

		add_settings_field(
			'wpzoom-instagram-widget-request-type',
			__( 'Request Type', 'instagram-widget-by-wpzoom' ),
			array( $this, 'settings_field_request_type' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-general',
			array( 'class' => 'wpzoom-instagram-widget-with-token-group' )
		);

		/**
		 * Instagram with basic api token.
		 */
		add_settings_field(
			'wpzoom-instagram-widget-basic-access-token-button',
			__( '', 'instagram-widget-by-wpzoom' ),
			array( $this, 'settings_field_basic_access_token_button' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-general',
			array( 'class' => 'wpzoom-instagram-widget-with-basic-access-token-group' )
		);

		add_settings_field(
			'wpzoom-instagram-widget-basic-access-token-input',
			__( 'Access Token', 'instagram-widget-by-wpzoom' ),
			array( $this, 'settings_field_basic_access_token_input' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-general',
			array( 'class' => 'wpzoom-instagram-widget-with-basic-access-token-group' )
		);

		add_settings_field(
			'wpzoom-instagram-widget-username-description',
			__( '', 'instagram-widget-by-wpzoom' ),
			array( $this, 'settings_field_username_description' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-general',
			array( 'class' => 'wpzoom-instagram-widget-without-access-token-group' )
		);

		add_settings_field(
			'wpzoom-instagram-widget-username',
			__( 'Username', 'instagram-widget-by-wpzoom' ),
			array( $this, 'settings_field_username' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-general',
			array( 'class' => 'wpzoom-instagram-widget-without-access-token-group' )
		);

		add_settings_field(
			'wpzoom-instagram-widget-transient-lifetime',
			__( 'Check for new posts every', 'instagram-widget-by-wpzoom' ),
			array( $this, 'settings_field_transient_lifetime' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-general'
		);

		add_settings_field(
			'wpzoom-instagram-widget-is-forced-timeout',
			__( 'Enable request timeout', 'instagram-widget-by-wpzoom' ),
			array( $this, 'settings_field_is_forced_timeout' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-general'
		);

		add_settings_field(
			'wpzoom-instagram-widget-request-timeout',
			__( 'Request timeout in seconds', 'instagram-widget-by-wpzoom' ),
			array( $this, 'settings_field_request_timeout' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-general',
			array( 'class' => 'wpzoom-instagram-widget-request-timeout' )
		);

		add_settings_field(
			'wpzoom-instagram-widget-request-timeout',
			__( 'Request timeout in seconds', 'instagram-widget-by-wpzoom' ),
			array( $this, 'settings_field_request_timeout' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-general',
			array( 'class' => 'wpzoom-instagram-widget-request-timeout' )
		);
	}

	public function settings_field_basic_access_token_button() {
		$settings = self::$settings;

		$oauth_url  = add_query_arg(
			array(
				'client_id'     => '1242932982579434',
				'redirect_uri'  => 'https://wpzoom.com/instagram-auth/',
				'scope'         => 'user_profile,user_media',
				'response_type' => 'code',
			),
			'https://api.instagram.com/oauth/authorize'
		);
		$oauth_url .= '&state=' . base64_encode( urlencode( admin_url( 'options-general.php?page=wpzoom-instagram-widget' ) ) );
		?>

		<p class="description"><?php _e( 'Using this method, you will be prompted to authorize the plugin to access your Instagram photos. The widget will automatically display the latest photos of the account which was authorized on this page.', 'instagram-widget-by-wpzoom' ); ?></p>
		<p class="description" style="color:#185373;"><strong><?php _e( 'Access tokens are valid for <u>60 days</u>. If the widget stops working, please generate a new Access Token below.', 'instagram-widget-by-wpzoom' ); ?></strong></p>

		<br/>

		<a class="button button-connect" href="<?php echo esc_url( $oauth_url ); ?>">
			<?php if ( empty( $settings['basic-access-token'] ) ) : ?>
				<span><?php _e( 'Connect with Instagram', 'instagram-widget-by-wpzoom' ); ?></span>
			<?php else : ?>
				<span class="zoom-instagarm-widget-connected"><?php _e( 'Re-connect with Instagram', 'instagram-widget-by-wpzoom' ); ?></span>
			<?php endif; ?>
		</a>
		</p>
		<?php
	}

	public function settings_field_transient_lifetime() {
		$settings       = self::$settings;
		$lifetime_value = ! empty( $settings['transient-lifetime-value'] ) ? $settings['transient-lifetime-value'] : 1;
		$lifetime_type  = ! empty( $settings['transient-lifetime-type'] ) ? $settings['transient-lifetime-type'] : 'days';
		?>
		<input class="regular-text code"
			   id="wpzoom-instagram-widget-settings_transient-lifetime-value"
			   name="wpzoom-instagram-widget-settings[transient-lifetime-value]"
			   value="<?php echo esc_attr( $lifetime_value ); ?>"
			   type="number"
			   min="1">

		<select class="regular-text code"
				id="wpzoom-instagram-widget-settings_transient-lifetime-type"
				name="wpzoom-instagram-widget-settings[transient-lifetime-type]">
			<option <?php selected( $lifetime_type, 'hours' ); ?>
					value="hours"><?php _e( 'Hours', 'instagram-widget-by-wpzoom' ); ?></option>
			<option <?php selected( $lifetime_type, 'days' ); ?>
					value="days"><?php _e( 'Days', 'instagram-widget-by-wpzoom' ); ?></option>
			<option <?php selected( $lifetime_type, 'minutes' ); ?>
					value="minutes"><?php _e( 'Minutes', 'instagram-widget-by-wpzoom' ); ?></option>
		</select>
		<?php
	}

	public function settings_field_is_forced_timeout() {
		$settings          = self::$settings;
		$is_forced_timeout = ! empty( $settings['is-forced-timeout'] ) ? wp_validate_boolean( $settings['is-forced-timeout'] ) : false;
		?>
		<input class="regular-text code"
			   id="wpzoom-instagram-widget-settings_is-forced-timeout"
			   name="wpzoom-instagram-widget-settings[is-forced-timeout]"
			<?php checked( true, $is_forced_timeout ); ?>
			   value="1"
			   type="checkbox">

		<?php
	}

	public function settings_field_request_timeout() {
		$settings      = self::$settings;
		$timeout_value = ! empty( $settings['request-timeout-value'] ) ? $settings['request-timeout-value'] : 15;
		?>
		<input class="regular-text code"
			   id="wpzoom-instagram-widget-settings_request-timeout-value"
			   name="wpzoom-instagram-widget-settings[request-timeout-value]"
			   value="<?php echo esc_attr( $timeout_value ); ?>"
			   type="number"
			   min="1"
			   max="30">


		   <p class="description"><?php _e( 'The default timeout to get your Instagram feed is 15 seconds, but on some servers, this might not be enough time. Enter a higher value like 30 (seconds) and save changes to see if there is a difference.', 'instagram-widget-by-wpzoom' ); ?></p>

		<?php
	}

	public function settings_field_basic_access_token_input() {
		$settings           = self::$settings;
		$basic_access_token = ! empty( $settings['basic-access-token'] ) ? $settings['basic-access-token'] : '';
		?>
		<input class="regular-text code" id="wpzoom-instagram-widget-settings_basic-access-token"
			   name="wpzoom-instagram-widget-settings[basic-access-token]"
			   value="<?php echo esc_attr( $basic_access_token ); ?>" type="text">
		<p class="description">
			<?php
			printf(
				__(
					'The Instagram Access Token is a long string of characters unique to your account that grants other applications access to your Instagram feed. You can also get it manually from <a href="%1$s">here</a>.',
					'instagram-widget-by-wpzoom'
				),
				'https://www.wpzoom.com/instagram-auth/'
			);
			?>
		</p>
		<?php
	}

	public function settings_field_username_description() {
		?>
		<p class="description"><?php _e( '<strong style="color:#e44;">This method is no longer supported by Instagram and it will be soon deprecated.</strong>', 'instagram-widget-by-wpzoom' ); ?></p>
		<p class="description"><?php _e( 'Using this method, a public feed, limited to <strong>12 photos</strong>, will be displayed in the widget.<br/>This option is useful if you want to display the feed of an Instagram account which you don\'t own or you have troubles getting your Access Token.', 'instagram-widget-by-wpzoom' ); ?></p>

		</p>
		<?php
	}

	public function settings_field_user_details() {
		?>
		<p class="description"><?php _e( 'Below you can add additional details which you can display in the header of the Instagram Widget.', 'instagram-widget-by-wpzoom' ); ?></p>

		</p>
		<?php
	}

	public function settings_field_username() {
		$settings = self::$settings;
		?>
		<input class="regular-text code" id="wpzoom-instagram-widget-settings_username"
			   name="wpzoom-instagram-widget-settings[username]" value="<?php echo esc_attr( $settings['username'] ); ?>"
			   type="text">
		<p class="description">
			<?php
			printf(
				__(
					'The username entered here will be used in the Instagram feed, unless a different username will be entered in the widget settings.',
					'instagram-widget-by-wpzoom'
				)
			);
			?>
		</p>
		<?php
	}

	public function settings_field_request_type() {
		$settings     = self::$settings;
		$request_type = empty( $settings['request-type'] ) ? 'with-basic-access-token' : $settings['request-type'];
		?>

		<div class="wpzoom-instagram-widget-settings-request-type-wrapper">

			<div class="label-wrap">
				<input class="code"
					   id="wpzoom-instagram-widget-settings_with-basic-access-token"
					   name="wpzoom-instagram-widget-settings[request-type]"
					   value="with-basic-access-token" <?php checked( $request_type, 'with-basic-access-token' ); ?>
					   type="radio">
				<label for="wpzoom-instagram-widget-settings_with-basic-access-token">
					<?php _e( 'With Access Token (Instagram API)', 'instagram-widget-by-wpzoom' ); ?>
				</label>
			</div>
			<div class="label-wrap">
				<input class="code"
					   id="wpzoom-instagram-widget-settings_without-access-token"
					   name="wpzoom-instagram-widget-settings[request-type]"
					   value="without-access-token"
					<?php checked( $request_type, 'without-access-token' ); ?>
					   type="radio">
				<label for="wpzoom-instagram-widget-settings_without-access-token">
					<?php _e( 'Public Feed (12 photos)', 'instagram-widget-by-wpzoom' ); ?>
				</label>
			</div>

		</div>

		<?php
	}

	public function settings_field_user_info_fullname() {
		$settings           = self::$settings;
		$user_info_fullname = empty( $settings['user-info-fullname'] ) ? '' : $settings['user-info-fullname'];
		?>
		<input class="code"
			   id="wpzoom-instagram-widget-settings-user-info-fullname"
			   name="wpzoom-instagram-widget-settings[user-info-fullname]"
			   value="<?php echo esc_attr( $user_info_fullname ); ?>"
			   type="text">
		<?php
	}

	public function settings_field_user_info_avatar() {
		$settings         = self::$settings;
		$user_info_avatar = empty( $settings['user-info-avatar'] ) ? '' : $settings['user-info-avatar'];
		?>
		<div class="zoom-instagram-user-avatar-media-uploader"
			 data-type="image"
			 data-button-add-text="<?php _e( 'Upload a picture', 'instagram-widget-by-wpzoom' ); ?>"
			 data-button-replace-text="<?php _e( 'Replace Profile Picture', 'instagram-widget-by-wpzoom' ); ?>">
			<a href="#" class="button add-media" title="Upload Profile Picture">
				<span class="wp-media-buttons-icon"></span>
				<?php _e( 'Upload a picture', 'instagram-widget-by-wpzoom' ); ?>
			</a>
			<button type="button" class="remove-avatar button-link delete-attachment">
				<?php _e( 'Remove Profile Picture', 'instagram-widget-by-wpzoom' ); ?>
			</button>
			<div class="file-wrapper"></div>
			<input class="attachment-input"
				   type="hidden"
				   name="wpzoom-instagram-widget-settings[user-info-avatar]"
				   value="<?php echo esc_attr( $user_info_avatar ); ?>">
		</div>
		<?php
	}

	public function settings_field_user_info_biography() {
		$settings            = self::$settings;
		$user_info_biography = empty( $settings['user-info-biography'] ) ? '' : $settings['user-info-biography'];
		?>
		<textarea class="code"
				  id="wpzoom-instagram-widget-settings-user-info-biography"
				  name="wpzoom-instagram-widget-settings[user-info-biography]"
				  type="text"><?php echo esc_attr( $user_info_biography ); ?></textarea>
		<?php
	}

	public function connect_page() {
		$oauth_url  = add_query_arg(
			array(
				'client_id'     => '1242932982579434',
				'redirect_uri'  => 'https://wpzoom.com/instagram-auth/',
				'scope'         => 'user_profile,user_media',
				'response_type' => 'code',
			),
			'https://api.instagram.com/oauth/authorize'
		);
		$oauth_url .= '&state=' . base64_encode( urlencode( admin_url( 'edit.php?post_type=wpz-insta_feed' ) ) );

		?>
		<div class="wrap">
			<header class="wpz-insta-wrap wpz-insta_settings-header">
				<h1 class="wpz-insta_settings-main-title wp-heading">
					<?php
					printf(
						__( 'Instagram Widget <small>by <a href="%s" target="_blank" title="WPZOOM - WordPress themes with modern features and professional support">WPZOOM</a></small>', 'instagram-widget-by-wpzoom' ),
						esc_url( 'https://wpzoom.com' )
					);
					?>
				</h1>

				<h2 class="wpz-insta_settings-sub-title wp-heading"><?php _e( 'Connect account', 'instagram-widget-by-wpzoom' ); ?></h2>
			</header>

			<div class="wpz-insta-wrap wpz-insta_settings-connect">
				<h3 class="section-title"><?php _e( 'Let&rsquo;s connect your Instagram account', 'instagram-widget-by-wpzoom' ); ?></h3>
				<p class="section-description"><?php _e( 'Are you connecting a Personal or Business Instagram Profile?  Unsure which button applies to you?  <a href="#" target="_blank">Learn the difference.</a>', 'instagram-widget-by-wpzoom' ); ?></p>

				<div class="account-options">
					<div class="account-option account-option_personal">
						<h4 class="account-option-title"><?php _e( 'Personal account', 'instagram-widget-by-wpzoom' ); ?></h4>

						<ul class="account-option-checklist">
							<li><?php _e( 'Connects directly through Instagram', 'instagram-widget-by-wpzoom' ); ?></li>
							<li><?php _e( 'Show posts from your account', 'instagram-widget-by-wpzoom' ); ?></li>
						</ul>

						<a href="<?php echo esc_attr( $oauth_url ); ?>" id="wpz-insta_connect-personal" class="button button-primary account-option-button">
							<svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
								<path d="M15.9582 4.70406C15.9208 3.85391 15.7833 3.26945 15.5863 2.763C15.3832 2.22542 15.0706 1.74412 14.6611 1.34402C14.261 0.937686 13.7765 0.62195 13.2452 0.421959C12.7358 0.22502 12.1544 0.0875418 11.3042 0.0500587C10.4478 0.00940127 10.1758 0 8.00354 0C5.83123 0 5.55933 0.00940127 4.70601 0.0468843C3.85587 0.0843673 3.2714 0.221968 2.76507 0.418784C2.22737 0.62195 1.74607 0.934512 1.34597 1.34402C0.939639 1.74412 0.624025 2.22859 0.423912 2.75995C0.226973 3.26945 0.0894949 3.85074 0.0520119 4.70088C0.0113544 5.55738 0.00195312 5.82928 0.00195312 8.00159C0.00195312 10.1739 0.0113544 10.4458 0.0488374 11.2991C0.0863205 12.1493 0.223921 12.7337 0.420859 13.2402C0.624025 13.7778 0.939639 14.2591 1.34597 14.6592C1.74607 15.0655 2.23054 15.3812 2.7619 15.5812C3.2714 15.7782 3.85269 15.9156 4.70296 15.9531C5.55616 15.9907 5.82818 16 8.00049 16C10.1728 16 10.4447 15.9907 11.298 15.9531C12.1482 15.9156 12.7326 15.7782 13.239 15.5812C14.3142 15.1655 15.1644 14.3153 15.5801 13.2402C15.7769 12.7307 15.9145 12.1493 15.952 11.2991C15.9895 10.4458 15.9989 10.1739 15.9989 8.00159C15.9989 5.82928 15.9957 5.55738 15.9582 4.70406ZM14.5174 11.2366C14.483 12.018 14.3517 12.44 14.2423 12.7213C13.9735 13.4183 13.4203 13.9715 12.7232 14.2404C12.4419 14.3498 12.0169 14.481 11.2386 14.5153C10.3946 14.5529 10.1415 14.5622 8.00671 14.5622C5.87189 14.5622 5.61562 14.5529 4.77475 14.5153C3.99335 14.481 3.57139 14.3498 3.29008 14.2404C2.94321 14.1122 2.62747 13.909 2.3712 13.6433C2.10552 13.3839 1.90235 13.0713 1.77416 12.7244C1.66476 12.4431 1.53351 12.018 1.4992 11.2398C1.46159 10.3959 1.45231 10.1426 1.45231 8.00781C1.45231 5.87299 1.46159 5.61671 1.4992 4.77597C1.53351 3.99457 1.66476 3.57261 1.77416 3.2913C1.90235 2.94431 2.10552 2.6287 2.37437 2.3723C2.6337 2.10662 2.94626 1.90345 3.29326 1.77538C3.57456 1.66598 3.99969 1.53473 4.77792 1.5003C5.62184 1.46281 5.87507 1.45341 8.00977 1.45341C10.1478 1.45341 10.4009 1.46281 11.2417 1.5003C12.0231 1.53473 12.4451 1.66598 12.7264 1.77538C13.0733 1.90345 13.389 2.10662 13.6453 2.3723C13.911 2.63175 14.1141 2.94431 14.2423 3.2913C14.3517 3.57261 14.483 3.99762 14.5174 4.77597C14.5549 5.61989 14.5643 5.87299 14.5643 8.00781C14.5643 10.1426 14.5549 10.3927 14.5174 11.2366Z" fill="#fff" />
								<path d="M8.00375 3.89062C5.73462 3.89062 3.89355 5.73157 3.89355 8.00082C3.89355 10.2701 5.73462 12.111 8.00375 12.111C10.273 12.111 12.1139 10.2701 12.1139 8.00082C12.1139 5.73157 10.273 3.89062 8.00375 3.89062ZM8.00375 10.667C6.53165 10.667 5.33757 9.47303 5.33757 8.00082C5.33757 6.5286 6.53165 5.33464 8.00375 5.33464C9.47596 5.33464 10.6699 6.5286 10.6699 8.00082C10.6699 9.47303 9.47596 10.667 8.00375 10.667Z" fill="#fff" />
								<path d="M13.2356 3.72907C13.2356 4.25896 12.806 4.68861 12.2759 4.68861C11.7461 4.68861 11.3164 4.25896 11.3164 3.72907C11.3164 3.19906 11.7461 2.76953 12.2759 2.76953C12.806 2.76953 13.2356 3.19906 13.2356 3.72907Z" fill="#fff" />
							</svg>

							<?php _e( 'Connect your personal account', 'instagram-widget-by-wpzoom' ); ?>
						</a>
					</div>

					<div class="account-option account-option_business">
						<h4 class="account-option-title"><?php _e( 'Business account', 'instagram-widget-by-wpzoom' ); ?></h4>

						<ul class="account-option-checklist">
							<li><?php _e( 'Connects through your Facebook page', 'instagram-widget-by-wpzoom' ); ?></li>
							<li><?php _e( 'Show posts from your account', 'instagram-widget-by-wpzoom' ); ?></li>
							<li><?php _e( 'Show posts where you are tagged', 'instagram-widget-by-wpzoom' ); ?></li>
							<li><?php _e( 'Show posts with a specific hashtag', 'instagram-widget-by-wpzoom' ); ?></li>
						</ul>

						<a href="<?php echo esc_attr( $oauth_url ); ?>" id="wpz-insta_connect-business" class="button button-primary account-option-button">
							<svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
								<path d="M15.9582 4.70406C15.9208 3.85391 15.7833 3.26945 15.5863 2.763C15.3832 2.22542 15.0706 1.74412 14.6611 1.34402C14.261 0.937686 13.7765 0.62195 13.2452 0.421959C12.7358 0.22502 12.1544 0.0875418 11.3042 0.0500587C10.4478 0.00940127 10.1758 0 8.00354 0C5.83123 0 5.55933 0.00940127 4.70601 0.0468843C3.85587 0.0843673 3.2714 0.221968 2.76507 0.418784C2.22737 0.62195 1.74607 0.934512 1.34597 1.34402C0.939639 1.74412 0.624025 2.22859 0.423912 2.75995C0.226973 3.26945 0.0894949 3.85074 0.0520119 4.70088C0.0113544 5.55738 0.00195312 5.82928 0.00195312 8.00159C0.00195312 10.1739 0.0113544 10.4458 0.0488374 11.2991C0.0863205 12.1493 0.223921 12.7337 0.420859 13.2402C0.624025 13.7778 0.939639 14.2591 1.34597 14.6592C1.74607 15.0655 2.23054 15.3812 2.7619 15.5812C3.2714 15.7782 3.85269 15.9156 4.70296 15.9531C5.55616 15.9907 5.82818 16 8.00049 16C10.1728 16 10.4447 15.9907 11.298 15.9531C12.1482 15.9156 12.7326 15.7782 13.239 15.5812C14.3142 15.1655 15.1644 14.3153 15.5801 13.2402C15.7769 12.7307 15.9145 12.1493 15.952 11.2991C15.9895 10.4458 15.9989 10.1739 15.9989 8.00159C15.9989 5.82928 15.9957 5.55738 15.9582 4.70406ZM14.5174 11.2366C14.483 12.018 14.3517 12.44 14.2423 12.7213C13.9735 13.4183 13.4203 13.9715 12.7232 14.2404C12.4419 14.3498 12.0169 14.481 11.2386 14.5153C10.3946 14.5529 10.1415 14.5622 8.00671 14.5622C5.87189 14.5622 5.61562 14.5529 4.77475 14.5153C3.99335 14.481 3.57139 14.3498 3.29008 14.2404C2.94321 14.1122 2.62747 13.909 2.3712 13.6433C2.10552 13.3839 1.90235 13.0713 1.77416 12.7244C1.66476 12.4431 1.53351 12.018 1.4992 11.2398C1.46159 10.3959 1.45231 10.1426 1.45231 8.00781C1.45231 5.87299 1.46159 5.61671 1.4992 4.77597C1.53351 3.99457 1.66476 3.57261 1.77416 3.2913C1.90235 2.94431 2.10552 2.6287 2.37437 2.3723C2.6337 2.10662 2.94626 1.90345 3.29326 1.77538C3.57456 1.66598 3.99969 1.53473 4.77792 1.5003C5.62184 1.46281 5.87507 1.45341 8.00977 1.45341C10.1478 1.45341 10.4009 1.46281 11.2417 1.5003C12.0231 1.53473 12.4451 1.66598 12.7264 1.77538C13.0733 1.90345 13.389 2.10662 13.6453 2.3723C13.911 2.63175 14.1141 2.94431 14.2423 3.2913C14.3517 3.57261 14.483 3.99762 14.5174 4.77597C14.5549 5.61989 14.5643 5.87299 14.5643 8.00781C14.5643 10.1426 14.5549 10.3927 14.5174 11.2366Z" fill="#fff" />
								<path d="M8.00375 3.89062C5.73462 3.89062 3.89355 5.73157 3.89355 8.00082C3.89355 10.2701 5.73462 12.111 8.00375 12.111C10.273 12.111 12.1139 10.2701 12.1139 8.00082C12.1139 5.73157 10.273 3.89062 8.00375 3.89062ZM8.00375 10.667C6.53165 10.667 5.33757 9.47303 5.33757 8.00082C5.33757 6.5286 6.53165 5.33464 8.00375 5.33464C9.47596 5.33464 10.6699 6.5286 10.6699 8.00082C10.6699 9.47303 9.47596 10.667 8.00375 10.667Z" fill="#fff" />
								<path d="M13.2356 3.72907C13.2356 4.25896 12.806 4.68861 12.2759 4.68861C11.7461 4.68861 11.3164 4.25896 11.3164 3.72907C11.3164 3.19906 11.7461 2.76953 12.2759 2.76953C12.806 2.76953 13.2356 3.19906 13.2356 3.72907Z" fill="#fff" />
							</svg>

							<?php _e( 'Connect your business account', 'instagram-widget-by-wpzoom' ); ?>
						</a>
					</div>

					<div class="account-option account-option_token">
						<h4 class="account-option-title"><?php _e( 'Connect without a login', 'instagram-widget-by-wpzoom' ); ?></h4>

						<input type="text" id="wpz-insta_account-token-input" name="wpz-insta_account-token-input" value="" class="account-option-token-input" placeholder="<?php _e( 'Facebook/Instagram access token', 'instagram-widget-by-wpzoom' ); ?>" />

						<button id="wpz-insta_account-token-button" class="account-option-button disabled">
							<svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
								<path d="M15.9582 4.70406C15.9208 3.85391 15.7833 3.26945 15.5863 2.763C15.3832 2.22542 15.0706 1.74412 14.6611 1.34402C14.261 0.937686 13.7765 0.62195 13.2452 0.421959C12.7358 0.22502 12.1544 0.0875418 11.3042 0.0500587C10.4478 0.00940127 10.1758 0 8.00354 0C5.83123 0 5.55933 0.00940127 4.70601 0.0468843C3.85587 0.0843673 3.2714 0.221968 2.76507 0.418784C2.22737 0.62195 1.74607 0.934512 1.34597 1.34402C0.939639 1.74412 0.624025 2.22859 0.423912 2.75995C0.226973 3.26945 0.0894949 3.85074 0.0520119 4.70088C0.0113544 5.55738 0.00195312 5.82928 0.00195312 8.00159C0.00195312 10.1739 0.0113544 10.4458 0.0488374 11.2991C0.0863205 12.1493 0.223921 12.7337 0.420859 13.2402C0.624025 13.7778 0.939639 14.2591 1.34597 14.6592C1.74607 15.0655 2.23054 15.3812 2.7619 15.5812C3.2714 15.7782 3.85269 15.9156 4.70296 15.9531C5.55616 15.9907 5.82818 16 8.00049 16C10.1728 16 10.4447 15.9907 11.298 15.9531C12.1482 15.9156 12.7326 15.7782 13.239 15.5812C14.3142 15.1655 15.1644 14.3153 15.5801 13.2402C15.7769 12.7307 15.9145 12.1493 15.952 11.2991C15.9895 10.4458 15.9989 10.1739 15.9989 8.00159C15.9989 5.82928 15.9957 5.55738 15.9582 4.70406ZM14.5174 11.2366C14.483 12.018 14.3517 12.44 14.2423 12.7213C13.9735 13.4183 13.4203 13.9715 12.7232 14.2404C12.4419 14.3498 12.0169 14.481 11.2386 14.5153C10.3946 14.5529 10.1415 14.5622 8.00671 14.5622C5.87189 14.5622 5.61562 14.5529 4.77475 14.5153C3.99335 14.481 3.57139 14.3498 3.29008 14.2404C2.94321 14.1122 2.62747 13.909 2.3712 13.6433C2.10552 13.3839 1.90235 13.0713 1.77416 12.7244C1.66476 12.4431 1.53351 12.018 1.4992 11.2398C1.46159 10.3959 1.45231 10.1426 1.45231 8.00781C1.45231 5.87299 1.46159 5.61671 1.4992 4.77597C1.53351 3.99457 1.66476 3.57261 1.77416 3.2913C1.90235 2.94431 2.10552 2.6287 2.37437 2.3723C2.6337 2.10662 2.94626 1.90345 3.29326 1.77538C3.57456 1.66598 3.99969 1.53473 4.77792 1.5003C5.62184 1.46281 5.87507 1.45341 8.00977 1.45341C10.1478 1.45341 10.4009 1.46281 11.2417 1.5003C12.0231 1.53473 12.4451 1.66598 12.7264 1.77538C13.0733 1.90345 13.389 2.10662 13.6453 2.3723C13.911 2.63175 14.1141 2.94431 14.2423 3.2913C14.3517 3.57261 14.483 3.99762 14.5174 4.77597C14.5549 5.61989 14.5643 5.87299 14.5643 8.00781C14.5643 10.1426 14.5549 10.3927 14.5174 11.2366Z" fill="#fff" />
								<path d="M8.00375 3.89062C5.73462 3.89062 3.89355 5.73157 3.89355 8.00082C3.89355 10.2701 5.73462 12.111 8.00375 12.111C10.273 12.111 12.1139 10.2701 12.1139 8.00082C12.1139 5.73157 10.273 3.89062 8.00375 3.89062ZM8.00375 10.667C6.53165 10.667 5.33757 9.47303 5.33757 8.00082C5.33757 6.5286 6.53165 5.33464 8.00375 5.33464C9.47596 5.33464 10.6699 6.5286 10.6699 8.00082C10.6699 9.47303 9.47596 10.667 8.00375 10.667Z" fill="#fff" />
								<path d="M13.2356 3.72907C13.2356 4.25896 12.806 4.68861 12.2759 4.68861C11.7461 4.68861 11.3164 4.25896 11.3164 3.72907C11.3164 3.19906 11.7461 2.76953 12.2759 2.76953C12.806 2.76953 13.2356 3.19906 13.2356 3.72907Z" fill="#fff" />
							</svg>

							<?php _e( 'Connect', 'instagram-widget-by-wpzoom' ); ?>
						</button>
					</div>
				</div>

				<p class="section-notice">
					<svg width="14" height="14" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg">
						<path d="M6.3 3.5H7.7V4.9H6.3V3.5ZM6.3 6.3H7.7V10.5H6.3V6.3ZM7 0C3.136 0 0 3.136 0 7C0 10.864 3.136 14 7 14C10.864 14 14 10.864 14 7C14 3.136 10.864 0 7 0ZM7 12.6C3.913 12.6 1.4 10.087 1.4 7C1.4 3.913 3.913 1.4 7 1.4C10.087 1.4 12.6 3.913 12.6 7C12.6 10.087 10.087 12.6 7 12.6Z" />
					</svg>

					<?php _e( 'If needed, you can convert a Personal account into a Business account by following the directions.&emsp;<a href="#" target="_blank">Learn more about Business accounts</a>', 'instagram-widget-by-wpzoom' ); ?>
				</p>
			</div>

			<div id="wpz-insta_modal-dialog" class="success">
				<div class="wpz-insta_modal-dialog_wrap">
					<div class="wpz-insta_modal-dialog_header">
						<h4 class="wpz-insta_modal-dialog_header-title"><?php _e( 'You&rsquo;ve successfully connected your account!', 'instagram-widget-by-wpzoom' ); ?></h4>
						<span class="wpz-insta_modal-dialog_header-button wpz-insta_modal-dialog_close-button"><?php _e( 'Close', 'instagram-widget-by-wpzoom' ); ?></span>
					</div>

					<div class="wpz-insta_modal-dialog_content">
						<?php _e( 'Your account is now connected. You can now add a feed and customize it on the next screens.', 'instagram-widget-by-wpzoom' ); ?>
					</div>

					<div class="wpz-insta_modal-dialog_footer">
						<span class="wpz-insta_modal-dialog_footer-button wpz-insta_modal-dialog_ok-button button button-primary"><?php _e( 'Ok', 'instagram-widget-by-wpzoom' ); ?></span>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public function support_page() {
		?>
		<div class="wrap">
			<header class="wpz-insta-wrap wpz-insta_settings-header">
				<h1 class="wpz-insta_settings-main-title wp-heading">
					<?php
					printf(
						__( 'Instagram Widget <small>by <a href="%s" target="_blank" title="WPZOOM - WordPress themes with modern features and professional support">WPZOOM</a></small>', 'instagram-widget-by-wpzoom' ),
						esc_url( 'https://wpzoom.com' )
					);
					?>
				</h1>

				<nav class="wpz-insta_settings-main-nav">
					<ul>
						<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wpz-insta_feed' ) ); ?>"><?php _e( 'Feeds', 'instagram-widget-by-wpzoom' ); ?></a></li>
						<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wpz-insta_user' ) ); ?>"><?php _e( 'Users', 'instagram-widget-by-wpzoom' ); ?></a></li>
						<li class="active"><a href="<?php echo esc_url( admin_url( 'options-general.php?page=wpz-insta-support' ) ); ?>"><?php _e( 'Support', 'instagram-widget-by-wpzoom' ); ?></a></li>
					</ul>
				</nav>
			</header>

			<div class="wpz-insta-wrap wpz-insta_settings-support">
				<h2 class="section-title">
					<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M15.9216 2H2.98533C2.43803 2 1.99023 2.45 1.99023 3V17L5.97062 13H15.9216C16.4689 13 16.9167 12.55 16.9167 12V3C16.9167 2.45 16.4689 2 15.9216 2ZM14.9265 4V11H5.14473L3.98047 12.17V4H14.9265ZM18.9068 6H20.897C21.4443 6 21.8921 6.45 21.8921 7V22L17.9117 18H6.96568C6.41837 18 5.97058 17.55 5.97058 17V15H18.9068V6Z"/>
					</svg>

					<?php _e( 'Need assistance?', 'instagram-widget-by-wpzoom' ); ?>
				</h2>

				<p class="section-description"><?php _e( 'Need help setting up your widget or have a question? Get in touch with our Support Team.<br/> We’d love the opportunity to help you.', 'instagram-widget-by-wpzoom' ); ?></p>

				<a href="<?php echo esc_url( 'https://wpzoom.com/support/tickets/' ); ?>" target="_blank" class="button-primary"><?php _e( 'Open Support Desk', 'instagram-widget-by-wpzoom' ); ?></a>
			</div>
		</div>
		<?php
	}

	public function scripts( $hook ) {
		if ( self::is_wpzinsta_screen() ) {
			wp_enqueue_media();
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'zoom-instagram-widget-admin', plugin_dir_url( dirname( __FILE__ ) . '/instagram-widget-by-wpzoom.php' ) . 'dist/styles/backend/index.css', array( 'wp-color-picker' ), '1.7.3' );
			wp_enqueue_script( 'zoom-instagram-widget-admin', plugin_dir_url( dirname( __FILE__ ) . '/instagram-widget-by-wpzoom.php' ) . 'dist/scripts/backend/index.js', array( 'jquery', 'wp-color-picker' ), '1.7.3' );
			wp_localize_script(
				'zoom-instagram-widget-admin',
				'zoom_instagram_widget_admin',
				array(
					'i18n_connect_confirm' => __( "Instagram Widget is already connected to Instagram.\r\n\r\nDo you want to connect again?", 'instagram-widget-by-wpzoom' ),
					'i18n_connect_success_title' => __( 'You&rsquo;ve successfully connected your account!', 'instagram-widget-by-wpzoom' ),
					'i18n_connect_success_content' => __( 'Your account is now connected. You can now add a feed and customize it on the next screens.', 'instagram-widget-by-wpzoom' ),
					'i18n_connect_fail_title' => __( 'Your account could not be connected!', 'instagram-widget-by-wpzoom' ),
					'i18n_connect_fail_content' => __( 'There was a problem connecting your account. Please try again!', 'instagram-widget-by-wpzoom' ),
					'nonce' => wp_create_nonce( 'ajax-nonce' ),
					'feeds_url' => admin_url( self::$any_feeds ? 'edit.php?post_type=wpz-insta_feed' : 'post-new.php?post_type=wpz-insta_feed' ),
					'edit_user_url' => admin_url( 'post.php?action=edit&post=' ),
					'preview_url' => site_url( '?wpz-insta-widget-preview=true' ),
				)
			);
		}
	}

	public function sanitize( $input ) {
		$result = array();

		$result['basic-access-token'] = sanitize_text_field( $input['basic-access-token'] );
		$result['request-type']       = sanitize_text_field( $input['request-type'] );

		if ( ! empty( $result['basic-access-token'] ) && ! empty( $result['request-type'] ) && 'with-basic-access-token' === $result['request-type'] ) {
			$validation_result = Wpzoom_Instagram_Widget_API::is_access_token_valid( $result['basic-access-token'], $result['request-type'] );

			if ( $validation_result !== true ) {
				$access_token_error_message = __( 'Provided Access Token expired. Please connect the plugin with your Instagram account again.', 'instagram-widget-by-wpzoom' );

				if ( is_wp_error( $validation_result ) ) {
					$access_token_error_message = $validation_result->get_error_message();
				}

				if ( $validation_result !== true ) {
					add_settings_error(
						'wpzoom-instagram-widget-access-token',
						esc_attr( 'wpzoom-instagram-widget-access-token-invalid' ),
						$access_token_error_message,
						'error'
					);
				}

				$result['basic-access-token'] = '';
			}
		}

		$result['username']                 = sanitize_text_field( $input['username'] );
		$result['transient-lifetime-value'] = sanitize_text_field( $input['transient-lifetime-value'] );
		$result['transient-lifetime-type']  = sanitize_text_field( $input['transient-lifetime-type'] );
		$result['is-forced-timeout']        = ! empty( $input['is-forced-timeout'] ) ? wp_validate_boolean( $input['is-forced-timeout'] ) : false;
		$result['request-timeout-value']    = sanitize_text_field( $input['request-timeout-value'] );
		$result['user-info-avatar']         = sanitize_text_field( $input['user-info-avatar'] );
		$result['user-info-fullname']       = sanitize_text_field( $input['user-info-fullname'] );
		$result['user-info-biography']      = sanitize_text_field( $input['user-info-biography'] );

		Wpzoom_Instagram_Widget_API::reset_cache( $result );

		return $result;
	}

	/**
	 * Get settings
	 *
	 * @since 1.8.4
	 * @return array
	 */
	public function get_settings() {
		return self::$settings;
	}

	/**
	 * Get settings option name
	 *
	 * @since 1.8.4
	 * @return string
	 */
	public function get_option_name() {
		return self::$option_name;
	}
}

WPZOOM_Instagram_Widget_Settings::get_instance();
