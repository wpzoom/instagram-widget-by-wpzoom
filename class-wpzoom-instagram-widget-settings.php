<?php

class Wpzoom_Instagram_Widget_Settings {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'settings_init' ) );

        add_filter( 'plugin_action_links', array( $this, 'add_action_links' ), 10, 2 );

        add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
    }

    function add_action_links( $links, $file ) {
        if ( $file != plugin_basename( dirname( __FILE__ ) . '/instagram-widget-by-wpzoom.php' ) ) {
            return $links;
        }

        $settings_link = sprintf(
            '<a href="%1$s">%2$s</a>',
            menu_page_url( 'wpzoom-instagram-widget', false ),
            esc_html__( 'Settings', 'zoom-instagram-widget' )
        );

        array_unshift( $links, $settings_link );

        return $links;
    }

    public function add_admin_menu() {
        add_options_page(
            'Instagram Widget by WPZOOM',
            'Instagram Widget by WPZOOM',
            'manage_options',
            'wpzoom-instagram-widget',
            array( $this, 'settings_page' )
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

        add_settings_field(
            'wpzoom-instagram-widget-access-token',
            __( 'Access Token', 'wpzoom-instagram-widget' ),
            array( $this, 'settings_field_access_token' ),
            'wpzoom-instagram-widget-settings-group',
            'wpzoom-instagram-widget-settings-general'
        );
    }

    public function settings_field_access_token() {
        $settings = get_option( 'wpzoom-instagram-widget-settings' );
        ?>
            <input class="regular-text code" id="wpzoom-instagram-widget-settings_access-token" name="wpzoom-instagram-widget-settings[access-token]" value="<?php echo esc_attr( $settings['access-token'] ) ?>" type="text">
            <p class="description">
                <?php
                printf(
                    __(
                        'Access Token is used as key to access your photos from Instagram so they can be displayed. You can also get it manually from <a href="%1$s">there</a>.',
                        'zoom-instagram-widget'
                    ),
                    'http://www.wpzoom.com/instagram/'
                );
                ?>
            </p>
        <?php
    }

    public function settings_page() {
        $oauth_url = 'https://instagram.com/oauth/authorize/?client_id=fd92b82737834e0595eae6476dc53f07&response_type=token&redirect_uri=http://www.wpzoom.com/instagram/';
        $oauth_url .= '?auth_site=' . esc_url( admin_url( 'options-general.php?page=wpzoom-instagram-widget' ) );
        ?>

            <div class="wrap zoom-instagram-widget">

                <h1><?php _e( 'Instagram Widget by WPZOOM', 'zoom-instagram-widget' ); ?></h1>

                <p><?php _e( 'Configure your Instagram Widget here. First auth, then design.', 'zoom-instagram-widget' ); ?></p>

                <a class="button button-connect" href="<?php echo esc_url( $oauth_url ); ?>">
                    <?php if ( ! Wpzoom_Instagram_Widget_API::getInstance()->is_configured() ) : ?>
                        <span><?php _e( 'Connect with Instagram', 'zoom-instagram-widget' ); ?></span>
                    <?php else: ?>
                        <span class="zoom-instagarm-widget-connected"><?php _e( 'Re-connect with Instagram', 'zoom-instagram-widget' ); ?></span>
                    <?php endif; ?>
                </a>

                <form action="options.php" method="post">

                    <?php
                    settings_fields( 'wpzoom-instagram-widget-settings-group' );
                    do_settings_sections( 'wpzoom-instagram-widget-settings-group' );
                    submit_button();
                    ?>

                </form>

            </div>

        <?php
    }

    public function scripts( $hook ) {
        if ( $hook != 'settings_page_wpzoom-instagram-widget' ) {
            return;
        }

        wp_enqueue_style( 'zoom-instagram-widget-admin', plugin_dir_url( dirname( __FILE__ ) . '/instagram-widget-by-wpzoom.php' ) . 'css/admin-instagram-widget.css', array(), '20151012' );
        wp_enqueue_script( 'zoom-instagram-widget-admin', plugin_dir_url( dirname( __FILE__ ) . '/instagram-widget-by-wpzoom.php' ) . 'js/admin-instagram-widget.js', array( 'jquery' ), '20151012' );
    }

    public function sanitize( $input ) {
        $result = array();

        $result['access-token'] = sanitize_text_field( $input['access-token'] );

        $validation_result = Wpzoom_Instagram_Widget_API::is_access_token_valid( $result['access-token'] );

        if ( $validation_result !== true ) {
            $access_token_error_message = __( 'Provided access token is has been rejected by Instagram Api. Please check your input data.', 'zoom-instagram-widget' );

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

            $result['access-token'] = '';
        }

        Wpzoom_Instagram_Widget_API::reset_cache();

        return $result;
    }
}

new Wpzoom_Instagram_Widget_Settings();
