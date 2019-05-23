<?php

class Wpzoom_Instagram_Widget_Settings {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'settings_init' ) );

        add_filter( 'plugin_action_links', array( $this, 'add_action_links' ), 10, 2 );

        add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ),9 );
    }

    function add_action_links( $links, $file ) {
        if ( $file != plugin_basename( dirname( __FILE__ ) . '/instagram-widget-by-wpzoom.php' ) ) {
            return $links;
        }

        $settings_link = sprintf(
            '<a href="%1$s">%2$s</a>',
            menu_page_url( 'wpzoom-instagram-widget', false ),
            esc_html__( 'Settings', 'wpzoom-instagram-widget' )
        );

        array_unshift( $links, $settings_link );

        return $links;
    }

    public function add_admin_menu() {
        add_options_page(
            'Instagram Widget',
            'Instagram Widget',
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
            'wpzoom-instagram-widget-request-type',
            __( 'Request Type', 'wpzoom-instagram-widget' ),
            array( $this, 'settings_field_request_type' ),
            'wpzoom-instagram-widget-settings-group',
            'wpzoom-instagram-widget-settings-general',
            array('class' => 'wpzoom-instagram-widget-with-token-group')
        );

        add_settings_field(
            'wpzoom-instagram-widget-access-token-button',
            __( '', 'wpzoom-instagram-widget' ),
            array( $this, 'settings_field_access_token_button' ),
            'wpzoom-instagram-widget-settings-group',
            'wpzoom-instagram-widget-settings-general',
            array('class' => 'wpzoom-instagram-widget-with-access-token-group')

        );

        add_settings_field(
            'wpzoom-instagram-widget-access-token-input',
            __( 'Access Token', 'wpzoom-instagram-widget' ),
            array( $this, 'settings_field_access_token_input' ),
            'wpzoom-instagram-widget-settings-group',
            'wpzoom-instagram-widget-settings-general',
            array('class' => 'wpzoom-instagram-widget-with-access-token-group')

        );

        add_settings_field(
            'wpzoom-instagram-widget-username-description',
            __( '', 'wpzoom-instagram-widget' ),
            array( $this, 'settings_field_username_description' ),
            'wpzoom-instagram-widget-settings-group',
            'wpzoom-instagram-widget-settings-general',
            array('class' => 'wpzoom-instagram-widget-without-access-token-group')
        );

        add_settings_field(
            'wpzoom-instagram-widget-username',
            __( 'Username', 'wpzoom-instagram-widget' ),
            array( $this, 'settings_field_username' ),
            'wpzoom-instagram-widget-settings-group',
            'wpzoom-instagram-widget-settings-general',
            array('class' => 'wpzoom-instagram-widget-without-access-token-group')

        );

        add_settings_field(
            'wpzoom-instagram-widget-transient-lifetime',
            __( 'Check for new Instagram posts every', 'wpzoom-instagram-widget' ),
            array( $this, 'settings_field_transient_lifetime' ),
            'wpzoom-instagram-widget-settings-group',
            'wpzoom-instagram-widget-settings-general'
        );


    }

    public function settings_field_access_token_button() {
        $oauth_url = 'https://instagram.com/oauth/authorize/?client_id=955bdb2319484968b93de8d6a1032c66&response_type=token&redirect_uri=https://www.wpzoom.com/instagram/';
        $oauth_url .= '?auth_site=' . esc_url( admin_url( 'options-general.php?page=wpzoom-instagram-widget' ) );
        $oauth_url.='&hl=en';
        ?>

        <p class="description"><?php _e( 'Using this method, you will be prompted to authorize the plugin to access your Instagram photos. The widget will automatically display the latest photos of the account which was authorized on this page.', 'wpzoom-instagram-widget' ); ?></p>

        <br />

        <a class="button button-connect" href="<?php echo esc_url( $oauth_url ); ?>">
            <?php if ( ! Wpzoom_Instagram_Widget_API::getInstance()->is_configured() ) : ?>
                <span><?php _e( 'Connect with Instagram', 'wpzoom-instagram-widget' ); ?></span>
            <?php else: ?>
                <span class="zoom-instagarm-widget-connected"><?php _e( 'Re-connect with Instagram', 'wpzoom-instagram-widget' ); ?></span>
            <?php endif; ?>
        </a>
        </p>
        <?php
    }

    public function settings_field_transient_lifetime() {
        $settings       = get_option( 'wpzoom-instagram-widget-settings' );
        $lifetime_value = ! empty( $settings['transient-lifetime-value'] ) ? $settings['transient-lifetime-value'] : 1;
        $lifetime_type  = ! empty( $settings['transient-lifetime-type'] ) ? $settings['transient-lifetime-type'] : 'days';
        ?>
        <input  class="regular-text code"
                id="wpzoom-instagram-widget-settings_transient-lifetime-value"
                name="wpzoom-instagram-widget-settings[transient-lifetime-value]"
                value="<?php echo esc_attr( $lifetime_value ) ?>"
                type="number"
                min="1">

        <select class="regular-text code"
                id="wpzoom-instagram-widget-settings_transient-lifetime-type"
                name="wpzoom-instagram-widget-settings[transient-lifetime-type]">
            <option <?php selected( $lifetime_type, 'hours' ); ?> value="hours"><?php _e( 'Hours', 'wpzoom-instagram-widget' ) ?></option>
            <option <?php selected( $lifetime_type, 'days' ); ?> value="days"><?php _e( 'Days', 'wpzoom-instagram-widget' ) ?></option>
            <option <?php selected( $lifetime_type, 'minutes' ); ?> value="minutes"><?php _e( 'Minutes', 'wpzoom-instagram-widget' ) ?></option>
        </select>
        <?php
    }

    public function settings_field_access_token_input() {
        $settings = get_option( 'wpzoom-instagram-widget-settings' );
        ?>
            <input class="regular-text code" id="wpzoom-instagram-widget-settings_access-token" name="wpzoom-instagram-widget-settings[access-token]" value="<?php echo esc_attr( $settings['access-token'] ) ?>" type="text">
            <p class="description">
                <?php
                printf(
                    __(
                        'The Instagram Access Token is a long string of characters unique to your account that grants other applications access to your Instagram feed. You can also get it manually from <a href="%1$s">here</a>.',
                        'wpzoom-instagram-widget'
                    ),
                    'https://www.wpzoom.com/instagram/'
                );
                ?>
            </p>
        <?php
    }

    public function settings_field_username_description() {
        ?>
        <p class="description"><?php _e( 'Using this method, a public feed, limited to <strong>12 photos</strong>, will be displayed in the widget.<br/>This option is useful if you want to display the feed of an Instagram account which you don\'t own or you have troubles getting your Access Token.', 'wpzoom-instagram-widget' ); ?></p>

        </p>
        <?php
    }

    public function settings_field_username() {
        $settings = get_option( 'wpzoom-instagram-widget-settings' );
        ?>
        <input class="regular-text code" id="wpzoom-instagram-widget-settings_username" name="wpzoom-instagram-widget-settings[username]" value="<?php echo esc_attr( $settings['username'] ) ?>" type="text">
        <p class="description">
            <?php
            printf(
                __(
                    'The username entered here will be used in the Instagram feed, unless a different username will be entered in the widget settings.',
                    'wpzoom-instagram-widget'
                )
            );
            ?>
        </p>
        <?php
    }

    public function settings_field_request_type() {
        $settings     = get_option( 'wpzoom-instagram-widget-settings' );
        $request_type = empty( $settings['request-type'] ) ? 'with-access-token' : $settings['request-type'];
        ?>

        <div class="wpzoom-instagram-widget-settins-request-type-wrapper">
            <p><label for="wpzoom-instagram-widget-settings_with-access-token"><input class="code" id="wpzoom-instagram-widget-settings_with-access-token"
                   name="wpzoom-instagram-widget-settings[request-type]"
                   value="with-access-token" <?php checked( $request_type, 'with-access-token' ) ?> type="radio"> <?php _e('With Access Token', 'wpzoom-instagram-widget')?>&nbsp;&nbsp;</label>
               <label for="wpzoom-instagram-widget-settings_without-access-token"><input class="code" id="wpzoom-instagram-widget-settings_without-access-token"
                   name="wpzoom-instagram-widget-settings[request-type]" value="without-access-token"
                   <?php checked( $request_type, 'without-access-token' ) ?>type="radio"><?php _e('Public Feed (12 photos)', 'wpzoom-instagram-widget')?></label>
           </p>
        </div>

        <?php
    }

    public function settings_page() {
        ?>

            <div class="wrap">

                <h1><?php _e( 'Instagram Widget by WPZOOM', 'wpzoom-instagram-widget' ); ?></h1>

                <div class="zoom-instagram-widget">

                    <h2><?php _e( 'Connect your account', 'wpzoom-instagram-widget' ); ?></h2>

                    <p><?php _e( 'To get started, select an option below. If you want to show your own feed, use the first option: <strong>With Access Token</strong>. If you want to show the feed of an Instagram account which you don\'t own, use the option <strong>Public Feed</strong>.', 'wpzoom-instagram-widget' ); ?></p>

                    <form action="options.php" method="post">

                        <?php
                            settings_fields( 'wpzoom-instagram-widget-settings-group' );
                            do_settings_sections( 'wpzoom-instagram-widget-settings-group' );
                            submit_button();
                        ?>

                    </form>

                </div>

                <div class="zoom-themes-link">

                    <h2><?php _e( 'Premium WordPress Themes by WPZOOM', 'wpzoom-instagram-widget' ); ?></h2>

                    <p><?php _e( 'Are you looking to give your website a new look?<br/> Check out our collection of <strong>40 expertly-crafted themes</strong> and find the perfect one for your needs!', 'wpzoom-instagram-widget' ); ?></p>

                    <p><?php printf( __( '<a class="cta-button" target="_blank" href="%1$s"">View our Themes &rarr;</a>' ), 'https://www.wpzoom.com/themes/' ); ?></p>

                </div>

            </div>

        <?php
    }

    public function scripts( $hook ) {
        if ( $hook != 'settings_page_wpzoom-instagram-widget' ) {
            return;
        }

        wp_enqueue_style( 'zoom-instagram-widget-admin', plugin_dir_url( dirname( __FILE__ ) . '/instagram-widget-by-wpzoom.php' ) . 'css/admin-instagram-widget.css', array(), '1.4.2' );
        wp_enqueue_script( 'zoom-instagram-widget-admin', plugin_dir_url( dirname( __FILE__ ) . '/instagram-widget-by-wpzoom.php' ) . 'js/admin-instagram-widget.js', array( 'jquery' ), '1.4.2' );
        wp_localize_script( 'zoom-instagram-widget-admin', 'zoom_instagram_widget_admin', array(
            'i18n_connect_confirm' => __( "Instagram Widget is already connected to Instagram.\r\n\r\nDo you want to connect again?", 'wpzoom-instagram-widget' ),
        ) );
    }

    public function sanitize( $input ) {
        $result = array();

        $result['access-token'] = sanitize_text_field( $input['access-token'] );

        if ( ! empty( $result['access-token'] ) ) {
            $validation_result = Wpzoom_Instagram_Widget_API::is_access_token_valid( $result['access-token'] );

            if ( $validation_result !== true ) {
                $access_token_error_message = __( 'Provided Access Token has been rejected by Instagram API. Please try again or use the other option.', 'wpzoom-instagram-widget' );

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
        }

        $result['username'] = sanitize_text_field( $input['username'] );
        $result['request-type'] = sanitize_text_field( $input['request-type'] );
        $result['transient-lifetime-value'] = sanitize_text_field( $input['transient-lifetime-value'] );
        $result['transient-lifetime-type'] = sanitize_text_field( $input['transient-lifetime-type'] );

        Wpzoom_Instagram_Widget_API::reset_cache();

        return $result;
    }
}

new Wpzoom_Instagram_Widget_Settings();
