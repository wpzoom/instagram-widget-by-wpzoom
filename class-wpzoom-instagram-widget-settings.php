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
		//add_action( 'admin_init', array( $this, 'settings_init' ) );

		add_filter( 'plugin_action_links', array( $this, 'add_action_links' ), 10, 2 );

		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ), 9 );
	}

	public function init() {
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
					'title',
					'editor',
					'custom-fields',
				),
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'options-general.php',
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
				'type'         => 'string',
				'default'      => '10px',
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_feed-width',
			array(
				'single'       => true,
				'type'         => 'string',
				'default'      => '',
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_feed-height',
			array(
				'single'       => true,
				'type'         => 'string',
				'default'      => '',
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
				'type'         => 'string',
				'default'      => '',
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'wpz-insta_feed',
			'_wpz-insta_font-size',
			array(
				'single'       => true,
				'type'         => 'string',
				'default'      => '',
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
					'editor',
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

		add_filter( 'admin_body_class', array( $this, 'admin_body_class_filter' ) );
		add_filter( 'parent_file', array( $this, 'parent_file_menu_filter' ) );
		add_filter( 'submenu_file', array( $this, 'submenu_filter' ) );
		add_filter( 'manage_wpz-insta_feed_posts_columns', array( $this, 'set_custom_edit_columns' ) );
		add_filter( 'manage_edit-wpz-insta_feed_sortable_columns', array( $this, 'set_custom_edit_columns_sortable' ) );
		add_action( 'manage_wpz-insta_feed_posts_custom_column' , array( $this, 'custom_column' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'sort_column_query' ) );
	}

	function admin_body_class_filter( $classes ) {
		$screen_id = get_current_screen()->id;
		$is_our_admin = 'edit-wpz-insta_feed' == $screen_id || 'edit-wpz-insta_user' == $screen_id || 'settings_page_wpz-insta-support' == $screen_id;

		return $classes . ( $is_our_admin ? ' wpz-insta-admin' : '' );
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

	function set_custom_edit_columns( $columns ) {
		unset( $columns['date'] );

		$columns['wpz-insta_account'] = __( 'Show posts from', 'instagram-widget-by-wpzoom' );
		$columns['wpz-insta_actions'] = __( 'Actions', 'instagram-widget-by-wpzoom' );

		return $columns;
	}

	function set_custom_edit_columns_sortable( $columns ) {
		$columns['wpz-insta_account'] = 'wpz-insta_account';

		return $columns;
	}

	function custom_column( $column, $post_id ) {
		switch ( $column ) {
			case 'wpz-insta_account' :
				$meta = get_post_meta( $post_id , 'wpz-insta_account-name' , true );
				echo ! $meta ? '&mdash;' : '@' . esc_html( $meta ); 
				break;

			case 'wpz-insta_actions':
				?>
				<nav class="wpz-insta_actions-menu">
					<strong>&hellip;</strong>
					<ul class="wpz-insta_hidden">
						<li class="wpz-insta_actions-menu_edit-feed"><?php _e( 'Edit feed', 'instagram-widget-by-wpzoom' ); ?></li>
						<li class="wpz-insta_actions-menu_duplicate-feed"><?php _e( 'Duplicate feed', 'instagram-widget-by-wpzoom' ); ?></li>
						<li class="wpz-insta_actions-menu_copy-shortcode"><?php _e( 'Copy shortcode', 'instagram-widget-by-wpzoom' ); ?></li>
						<li class="wpz-insta_actions-menu_divider"></li>
						<li class="wpz-insta_actions-menu_update-posts"><?php _e( 'Update posts', 'instagram-widget-by-wpzoom' ); ?></li>
						<li class="wpz-insta_actions-menu_clear-cache"><?php _e( 'Clear cache', 'instagram-widget-by-wpzoom' ); ?></li>
						<li class="wpz-insta_actions-menu_divider"></li>
						<li class="wpz-insta_actions-menu_delete"><?php _e( 'Delete feed', 'instagram-widget-by-wpzoom' ); ?></li>
					</ul>
				</nav>
				<?php
				break;
		}
	}

	function sort_column_query( $query ) {
		$orderby = $query->get( 'orderby' );

		if ( 'wpz-insta_account' == $orderby ) {
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
					<li class="disable" <?php /*echo 'wpz-insta_user' == $current_page ? 'class="active"' : '';*/ ?>><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wpz-insta_user' ) ); ?>"><?php _e( 'Users', 'instagram-widget-by-wpzoom' ); ?></a></li>
					<li <?php echo 'support' == $current_page ? 'class="active"' : ''; ?>><a href="<?php echo esc_url( admin_url( 'options-general.php?page=wpz-insta-support' ) ); ?>"><?php _e( 'Support', 'instagram-widget-by-wpzoom' ); ?></a></li>
				</ul>
			</nav>
		</header>

		<div class="wpz-insta-wrap wpz-insta_settings-add-new">
			<?php if ( 'wpz-insta_feed' == $current_page ) : ?>
				<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wpz-insta_feed' ) ); ?>" class="button-primary disabled"><?php _e( 'Add new feed', 'instagram-widget-by-wpzoom' ); ?></a>
			<?php else : ?>
				<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wpz-insta_user' ) ); ?>" class="button-primary disabled"><?php _e( 'Add new user', 'instagram-widget-by-wpzoom' ); ?></a>
			<?php endif; ?>
		</div>

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

	public function add_admin_menu() {
		add_submenu_page(
			'options-general.php',
			'Instagram Widget Support',
			'Instagram Widget Support',
			'manage_options',
			'wpz-insta-support',
			array( $this, 'support_page' )
		);
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
						<li class="disable"><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wpz-insta_user' ) ); ?>"><?php _e( 'Users', 'instagram-widget-by-wpzoom' ); ?></a></li>
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
		</div>
		<?php
	}

	public function scripts( $hook ) {
		$screen_id = get_current_screen()->id;

		if ( ( 'edit.php' == $hook && ( 'edit-wpz-insta_feed' == $screen_id || 'edit-wpz-insta_user' == $screen_id ) ) || 'settings_page_wpz-insta-support' == $hook ) {
			wp_enqueue_media();
			wp_enqueue_style( 'zoom-instagram-widget-admin', plugin_dir_url( dirname( __FILE__ ) . '/instagram-widget-by-wpzoom.php' ) . 'dist/styles/backend/index.css', array(), '1.7.3' );
			wp_enqueue_script( 'zoom-instagram-widget-admin', plugin_dir_url( dirname( __FILE__ ) . '/instagram-widget-by-wpzoom.php' ) . 'dist/scripts/backend/index.js', array( 'jquery' ), '1.7.3' );
			wp_localize_script(
				'zoom-instagram-widget-admin',
				'zoom_instagram_widget_admin',
				array(
					'i18n_connect_confirm' => __( "Instagram Widget is already connected to Instagram.\r\n\r\nDo you want to connect again?", 'instagram-widget-by-wpzoom' ),
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
