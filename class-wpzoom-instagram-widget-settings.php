<?php

class Wpzoom_Instagram_Widget_Settings {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'settings_init' ) );
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
        register_setting( 'wpzoom-instagram-widget-settings-group', 'wpzoom-instagram-widget-settings' );

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
        ?>
            <input class="regular-text code" type="text">
        <?php
    }

    public function settings_page() {
        ?>

            <div class="wrap">

                <h1>Instagram Widget by WPZOOM</h1>

                <?php
                settings_fields( 'wpzoom-instagram-widget-settings-group' );
                do_settings_sections( 'wpzoom-instagram-widget-settings-group' );
                submit_button();
                ?>

            </div>

        <?php
    }
}

new Wpzoom_Instagram_Widget_Settings();
