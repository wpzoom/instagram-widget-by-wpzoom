<?php
/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Wpzoom_Instagram_Widget_Settings {
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'settings_init' ) );

		add_filter( 'plugin_action_links', array( $this, 'add_action_links' ), 10, 2 );

		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ), 9 );
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

		add_settings_section(
			'wpzoom-instagram-widget-settings-user-info',
			__( 'User Details (optional)', 'wpzoom-instagram-widget' ),
			array( $this, 'settings_field_user_details' ),
			'wpzoom-instagram-widget-settings-group'
		);

		add_settings_field(
			'wpzoom-instagram-widget-user-info-avatar',
			__( 'Profile Picture', 'wpzoom-instagram-widget' ),
			array( $this, 'settings_field_user_info_avatar' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-user-info'
		);

		add_settings_field(
			'wpzoom-instagram-widget-user-info-fullname',
			__( 'Your Name', 'wpzoom-instagram-widget' ),
			array( $this, 'settings_field_user_info_fullname' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-user-info'
		);

		add_settings_field(
			'wpzoom-instagram-widget-user-info-biography',
			__( 'Bio', 'wpzoom-instagram-widget' ),
			array( $this, 'settings_field_user_info_biography' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-user-info'
		);

		add_settings_field(
			'wpzoom-instagram-widget-request-type',
			__( 'Request Type', 'wpzoom-instagram-widget' ),
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
			__( '', 'wpzoom-instagram-widget' ),
			array( $this, 'settings_field_basic_access_token_button' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-general',
			array( 'class' => 'wpzoom-instagram-widget-with-basic-access-token-group' )

		);

		add_settings_field(
			'wpzoom-instagram-widget-basic-access-token-input',
			__( 'Access Token', 'wpzoom-instagram-widget' ),
			array( $this, 'settings_field_basic_access_token_input' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-general',
			array( 'class' => 'wpzoom-instagram-widget-with-basic-access-token-group' )

		);

		add_settings_field(
			'wpzoom-instagram-widget-username-description',
			__( '', 'wpzoom-instagram-widget' ),
			array( $this, 'settings_field_username_description' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-general',
			array( 'class' => 'wpzoom-instagram-widget-without-access-token-group' )
		);

		add_settings_field(
			'wpzoom-instagram-widget-username',
			__( 'Username', 'wpzoom-instagram-widget' ),
			array( $this, 'settings_field_username' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-general',
			array( 'class' => 'wpzoom-instagram-widget-without-access-token-group' )

		);

		add_settings_field(
			'wpzoom-instagram-widget-transient-lifetime',
			__( 'Check for new posts every', 'wpzoom-instagram-widget' ),
			array( $this, 'settings_field_transient_lifetime' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-general'
		);

		add_settings_field(
			'wpzoom-instagram-widget-is-forced-timeout',
			__( 'Enable request timeout', 'wpzoom-instagram-widget' ),
			array( $this, 'settings_field_is_forced_timeout' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-general'
		);

		add_settings_field(
			'wpzoom-instagram-widget-request-timeout',
			__( 'Request timeout in seconds', 'wpzoom-instagram-widget' ),
			array( $this, 'settings_field_request_timeout' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-general',
			array( 'class' => 'wpzoom-instagram-widget-request-timeout' )
		);

		add_settings_field(
			'wpzoom-instagram-widget-request-timeout',
			__( 'Request timeout in seconds', 'wpzoom-instagram-widget' ),
			array( $this, 'settings_field_request_timeout' ),
			'wpzoom-instagram-widget-settings-group',
			'wpzoom-instagram-widget-settings-general',
			array( 'class' => 'wpzoom-instagram-widget-request-timeout' )
		);


	}

	public function settings_field_basic_access_token_button() {

		$settings = get_option( 'wpzoom-instagram-widget-settings', wpzoom_instagram_get_default_settings() );

		$oauth_url = add_query_arg( [
			'client_id'     => '1242932982579434',
			'redirect_uri'  => 'https://wpzoom.com/instagram-auth/',
			'scope'         => 'user_profile,user_media',
			'response_type' => 'code',
		], 'https://api.instagram.com/oauth/authorize' );
		$oauth_url .= '&state=' . base64_encode( urlencode( admin_url( 'options-general.php?page=wpzoom-instagram-widget' ) ) );
		?>

        <p class="description"><?php _e( 'Using this method, you will be prompted to authorize the plugin to access your Instagram photos. The widget will automatically display the latest photos of the account which was authorized on this page.', 'wpzoom-instagram-widget' ); ?></p>
        <p class="description" style="color:#185373;"><strong><?php _e( 'Access tokens are valid for <u>60 days</u>. If the widget stops working, please generate a new Access Token below.', 'wpzoom-instagram-widget' ); ?></strong></p>

        <br/>

        <a class="button button-connect" href="<?php echo esc_url( $oauth_url ); ?>">
			<?php if ( empty( $settings['basic-access-token'] ) ) : ?>
                <span><?php _e( 'Connect with Instagram', 'wpzoom-instagram-widget' ); ?></span>
			<?php else: ?>
                <span class="zoom-instagarm-widget-connected"><?php _e( 'Re-connect with Instagram', 'wpzoom-instagram-widget' ); ?></span>
			<?php endif; ?>
        </a>
        </p>
		<?php
	}

	public function settings_field_transient_lifetime() {
		$settings       = get_option( 'wpzoom-instagram-widget-settings', wpzoom_instagram_get_default_settings() );
		$lifetime_value = ! empty( $settings['transient-lifetime-value'] ) ? $settings['transient-lifetime-value'] : 1;
		$lifetime_type  = ! empty( $settings['transient-lifetime-type'] ) ? $settings['transient-lifetime-type'] : 'days';
		?>
        <input class="regular-text code"
               id="wpzoom-instagram-widget-settings_transient-lifetime-value"
               name="wpzoom-instagram-widget-settings[transient-lifetime-value]"
               value="<?php echo esc_attr( $lifetime_value ) ?>"
               type="number"
               min="1">

        <select class="regular-text code"
                id="wpzoom-instagram-widget-settings_transient-lifetime-type"
                name="wpzoom-instagram-widget-settings[transient-lifetime-type]">
            <option <?php selected( $lifetime_type, 'hours' ); ?>
                    value="hours"><?php _e( 'Hours', 'wpzoom-instagram-widget' ) ?></option>
            <option <?php selected( $lifetime_type, 'days' ); ?>
                    value="days"><?php _e( 'Days', 'wpzoom-instagram-widget' ) ?></option>
            <option <?php selected( $lifetime_type, 'minutes' ); ?>
                    value="minutes"><?php _e( 'Minutes', 'wpzoom-instagram-widget' ) ?></option>
        </select>
		<?php
	}

	public function settings_field_is_forced_timeout() {
		$settings          = get_option( 'wpzoom-instagram-widget-settings', wpzoom_instagram_get_default_settings() );
		$is_forced_timeout = ! empty( $settings['is-forced-timeout'] ) ? wp_validate_boolean( $settings['is-forced-timeout'] ) : false;
		?>
        <input class="regular-text code"
               id="wpzoom-instagram-widget-settings_is-forced-timeout"
               name="wpzoom-instagram-widget-settings[is-forced-timeout]"
			<?php checked( true, $is_forced_timeout ) ?>
               value="1"
               type="checkbox">

		<?php
	}

	public function settings_field_request_timeout() {
		$settings      = get_option( 'wpzoom-instagram-widget-settings', wpzoom_instagram_get_default_settings() );
		$timeout_value = ! empty( $settings['request-timeout-value'] ) ? $settings['request-timeout-value'] : 15;
		?>
        <input class="regular-text code"
               id="wpzoom-instagram-widget-settings_request-timeout-value"
               name="wpzoom-instagram-widget-settings[request-timeout-value]"
               value="<?php echo esc_attr( $timeout_value ) ?>"
               type="number"
               min="1"
               max="30">


           <p class="description"><?php _e( 'The default timeout to get your Instagram feed is 15 seconds, but on some servers, this might not be enough time. Enter a higher value like 30 (seconds) and save changes to see if there is a difference.', 'wpzoom-instagram-widget' ); ?></p>

		<?php
	}

	public function settings_field_basic_access_token_input() {
		$settings           = get_option( 'wpzoom-instagram-widget-settings', wpzoom_instagram_get_default_settings() );
		$basic_access_token = ! empty( $settings['basic-access-token'] ) ? $settings['basic-access-token'] : '';
		?>
        <input class="regular-text code" id="wpzoom-instagram-widget-settings_basic-access-token"
               name="wpzoom-instagram-widget-settings[basic-access-token]"
               value="<?php echo esc_attr( $basic_access_token ) ?>" type="text">
        <p class="description">
			<?php
			printf(
				__(
					'The Instagram Access Token is a long string of characters unique to your account that grants other applications access to your Instagram feed. You can also get it manually from <a href="%1$s">here</a>.',
					'wpzoom-instagram-widget'
				),
				'https://www.wpzoom.com/instagram-auth/'
			);
			?>
        </p>
		<?php
	}

	public function settings_field_username_description() {
		?>
        <p class="description"><?php _e( '<strong style="color:#e44;">This method is no longer supported by Instagram and it will be soon deprecated.</strong>', 'wpzoom-instagram-widget' ); ?></p>
        <p class="description"><?php _e( 'Using this method, a public feed, limited to <strong>12 photos</strong>, will be displayed in the widget.<br/>This option is useful if you want to display the feed of an Instagram account which you don\'t own or you have troubles getting your Access Token.', 'wpzoom-instagram-widget' ); ?></p>

        </p>
		<?php
	}

    public function settings_field_user_details() {
        ?>
        <p class="description"><?php _e( 'Below you can add additional details which you can display in the header of the Instagram Widget.', 'wpzoom-instagram-widget' ); ?></p>

        </p>
        <?php
    }


	public function settings_field_username() {
		$settings = get_option( 'wpzoom-instagram-widget-settings', wpzoom_instagram_get_default_settings() );
		?>
        <input class="regular-text code" id="wpzoom-instagram-widget-settings_username"
               name="wpzoom-instagram-widget-settings[username]" value="<?php echo esc_attr( $settings['username'] ) ?>"
               type="text">
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
		$settings     = get_option( 'wpzoom-instagram-widget-settings', wpzoom_instagram_get_default_settings() );
		$request_type = empty( $settings['request-type'] ) ? 'with-basic-access-token' : $settings['request-type'];
		?>

        <div class="wpzoom-instagram-widget-settings-request-type-wrapper">

            <div class="label-wrap">
                <input class="code"
                       id="wpzoom-instagram-widget-settings_with-basic-access-token"
                       name="wpzoom-instagram-widget-settings[request-type]"
                       value="with-basic-access-token" <?php checked( $request_type, 'with-basic-access-token' ) ?>
                       type="radio">
                <label for="wpzoom-instagram-widget-settings_with-basic-access-token">
					<?php _e( 'With Access Token (Instagram API)', 'wpzoom-instagram-widget' ) ?>
                </label>
            </div>
            <div class="label-wrap">
                <input class="code"
                       id="wpzoom-instagram-widget-settings_without-access-token"
                       name="wpzoom-instagram-widget-settings[request-type]"
                       value="without-access-token"
					<?php checked( $request_type, 'without-access-token' ) ?>
                       type="radio">
                <label for="wpzoom-instagram-widget-settings_without-access-token">
					<?php _e( 'Public Feed (12 photos)', 'wpzoom-instagram-widget' ) ?>
                </label>
            </div>

        </div>

		<?php
	}

	public function settings_field_user_info_fullname() {
		$settings           = get_option( 'wpzoom-instagram-widget-settings', wpzoom_instagram_get_default_settings() );
		$user_info_fullname = empty( $settings['user-info-fullname'] ) ? '' : $settings['user-info-fullname'];
		?>
        <input class="code"
               id="wpzoom-instagram-widget-settings-user-info-fullname"
               name="wpzoom-instagram-widget-settings[user-info-fullname]"
               value="<?php echo esc_attr( $user_info_fullname ) ?>"
               type="text">
		<?php
	}

	public function settings_field_user_info_avatar() {
		$settings         = get_option( 'wpzoom-instagram-widget-settings', wpzoom_instagram_get_default_settings() );
		$user_info_avatar = empty( $settings['user-info-avatar'] ) ? '' : $settings['user-info-avatar'];
		?>
        <div class="zoom-instagram-user-avatar-media-uploader"
             data-type="image"
             data-button-add-text="<?php _e( 'Upload a picture', 'wpzoom-instagram-widget' ); ?>"
             data-button-replace-text="<?php _e( 'Replace Profile Picture', 'wpzoom-instagram-widget' ); ?>">
            <a href="#" class="button add-media" title="Upload Profile Picture">
                <span class="wp-media-buttons-icon"></span>
				<?php _e( 'Upload a picture', 'wpzoom-instagram-widget' ); ?>
            </a>
            <button type="button" class="remove-avatar button-link delete-attachment">
				<?php _e( 'Remove Profile Picture', 'wpzoom-instagram-widget' ) ?>
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
		$settings            = get_option( 'wpzoom-instagram-widget-settings', wpzoom_instagram_get_default_settings() );
		$user_info_biography = empty( $settings['user-info-biography'] ) ? '' : $settings['user-info-biography'];
		?>
        <textarea class="code"
                  id="wpzoom-instagram-widget-settings-user-info-biography"
                  name="wpzoom-instagram-widget-settings[user-info-biography]"
                  type="text"><?php echo esc_attr( $user_info_biography ) ?></textarea>
		<?php
	}

	public function settings_page() {
		?>

        <div class="wrap">

            <h1><?php _e( 'Instagram Widget by WPZOOM', 'wpzoom-instagram-widget' ); ?></h1>

            <div class="zoom-instagram-widget">

                <h2><?php _e( 'Connect your Instagram account', 'wpzoom-instagram-widget' ); ?></h2>

                <p><?php _e( 'To get started, select an option below. If you want to show <strong>your own feed</strong>, use the first option. If you want to show the feed of an Instagram account which you don\'t own, use the option <strong>Public Feed</strong>.', 'wpzoom-instagram-widget' ); ?></p>

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

                <p><?php _e( 'Are you looking to give your website a new look?<br/> Check out our collection of <strong>45 expertly-crafted themes</strong> and find the perfect one for your needs!', 'wpzoom-instagram-widget' ); ?></p>

                <p><?php printf( __( '<a class="cta-button" target="_blank" href="%1$s"">View our Themes &rarr;</a>' ), 'https://www.wpzoom.com/themes/' ); ?></p>

            </div>

        </div>

		<?php
	}

	public function scripts( $hook ) {
		if ( $hook != 'settings_page_wpzoom-instagram-widget' ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'zoom-instagram-widget-admin', plugin_dir_url( dirname( __FILE__ ) . '/instagram-widget-by-wpzoom.php' ) . 'css/admin-instagram-widget.css', array(), '1.7.3' );
		wp_enqueue_script( 'zoom-instagram-widget-admin', plugin_dir_url( dirname( __FILE__ ) . '/instagram-widget-by-wpzoom.php' ) . 'js/admin-instagram-widget.js', array( 'jquery' ), '1.7.3' );
		wp_localize_script( 'zoom-instagram-widget-admin', 'zoom_instagram_widget_admin', array(
			'i18n_connect_confirm' => __( "Instagram Widget is already connected to Instagram.\r\n\r\nDo you want to connect again?", 'wpzoom-instagram-widget' ),
		) );
	}

	public function sanitize( $input ) {
		$result = array();

		$result['basic-access-token'] = sanitize_text_field( $input['basic-access-token'] );
		$result['request-type']       = sanitize_text_field( $input['request-type'] );

		if ( ! empty( $result['basic-access-token'] ) && ! empty( $result['request-type'] ) && 'with-basic-access-token' === $result['request-type'] ) {
			$validation_result = Wpzoom_Instagram_Widget_API::is_access_token_valid( $result['basic-access-token'], $result['request-type'] );

			if ( $validation_result !== true ) {
				$access_token_error_message = __( 'Provided Access Token expired. Please connect the plugin with your Instagram account again.', 'wpzoom-instagram-widget' );

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

		Wpzoom_Instagram_Widget_API::reset_cache();

		return $result;
	}
}

new Wpzoom_Instagram_Widget_Settings();
