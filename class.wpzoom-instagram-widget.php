<?php

class Wpzoom_Instagram_Widget extends WP_Widget {
	/**
	 * @var array
	 */
	protected $defaults;

	/**
	 * Instagram Access Token
	 *
	 * @var string
	 */
	protected $access_token;

	public function __construct() {
		parent::__construct(
			'wpzoom_instagram_widget',
			esc_html__( 'Instagram Widget by WPZOOM', 'wpzoom-instagram-widget' ),
			array(
				'classname'   => 'zoom-instagram-widget',
				'description' => __( 'Displays a user\'s Instagram timeline.', 'wpzoom-instagram-widget' ),
			)
		);

		$this->defaults = array(
			'title'                           => esc_html__( 'Instagram', 'wpzoom-instagram-widget' ),
			'screen-name'                     => '',
			'image-limit'                     => 9,
			'show-view-on-instagram-button'   => true,
			'center-view-on-instagram-button' => true,
			'images-per-row'                  => 3,
			'image-width'                     => 120,
			'access-token'                    => ''
		);

		if ( is_active_widget( false, false, $this->id_base ) || is_active_widget( false, false, 'monster' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
		}
	}

	/**
	 * Widget specific scripts & styles
	 */
	public function scripts() {
		wp_enqueue_style( 'zoom-instagram-widget', plugin_dir_url( dirname( __FILE__ ) . '/instagram-widget-by-wpzoom.php' ) . 'css/instagram-widget.css', array(), '20150129' );
		wp_enqueue_script( 'zoom-instagram-widget', plugin_dir_url( dirname( __FILE__ ) . '/instagram-widget-by-wpzoom.php' ) . 'js/instagram-widget.js', array( 'jquery' ), '20150202' );
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		$instance = wp_parse_args( (array) $instance, $this->defaults );

		$this->access_token = $instance['access-token'];

		echo $args['before_widget'];

		if ( $instance['title'] ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

		$items = $this->get_items( $instance['screen-name'] );

		if ( false === $items ) {
			$this->display_errors();
		} else {
			$this->display_items( $items, $instance );
			$this->display_instagram_button( $instance );
		}


		echo $args['after_widget'];
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance['title'] = sanitize_text_field( $new_instance['title'] );

		$instance['screen-name'] = sanitize_text_field( $new_instance['screen-name'] );

		$instance['image-limit'] = ( 0 !== (int) $new_instance['image-limit'] ) ? (int) $new_instance['image-limit'] : null;

		$instance['images-per-row'] = ( 0 !== (int) $new_instance['images-per-row'] ) ? (int) $new_instance['images-per-row'] : null;
		$instance['image-width'] = ( 0 !== (int) $new_instance['image-width'] ) ? (int) $new_instance['image-width'] : null;

		$instance['show-view-on-instagram-button']   = (bool) $new_instance['show-view-on-instagram-button'];
		$instance['center-view-on-instagram-button'] = (bool) $new_instance['center-view-on-instagram-button'];

		$instance['access-token'] = sanitize_text_field( $new_instance['access-token'] );

		delete_transient( 'zoom_instagram_t6e_' . $instance['screen-name'] );
		delete_option( 'zoom_instagram_uid_' . $instance['screen-name'] );

		return $instance;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 *
	 * @return string|void
	 */
	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, $this->defaults );
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'wpzoom-instagram-widget' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>"/>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'screen-name' ); ?>"><?php esc_html_e( 'Instagram Username:', 'wpzoom-instagram-widget' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'screen-name' ); ?>" name="<?php echo $this->get_field_name( 'screen-name' ); ?>" type="text" value="<?php echo esc_attr( $instance['screen-name'] ); ?>"/>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'image-limit' ); ?>"><?php esc_html_e( '# of Images Shown:', 'wpzoom-instagram-widget' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'image-limit' ); ?>" name="<?php echo $this->get_field_name( 'image-limit' ); ?>" type="number" min="1" max="20" value="<?php echo esc_attr( $instance['image-limit'] ); ?>"/>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'images-per-row' ); ?>"><?php esc_html_e( 'Desired # of Images per row:', 'wpzoom-instagram-widget' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'images-per-row' ); ?>" name="<?php echo $this->get_field_name( 'images-per-row' ); ?>" type="number" min="1" max="20" value="<?php echo esc_attr( $instance['images-per-row'] ); ?>"/>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'image-width' ); ?>"><?php esc_html_e( 'Desired Image width in pixels:', 'wpzoom-instagram-widget' ); ?> <small>(Just integer)</small></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'image-width' ); ?>" name="<?php echo $this->get_field_name( 'image-width' ); ?>" type="number" min="20" max="300" value="<?php echo esc_attr( $instance['image-width'] ); ?>"/>
		</p>

		<p>
			<small>
				<?php
				echo wp_kses_post(
					__( 'Fields above do not influence directly widget appearance. Final number of images per row and image width is calculated depending on browser resolution.', 'wpzoom-instagram-widget' )
				);
				?>
			</small>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['show-view-on-instagram-button'] ); ?> id="<?php echo $this->get_field_id( 'show-view-on-instagram-button' ); ?>" name="<?php echo $this->get_field_name( 'show-view-on-instagram-button' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'show-view-on-instagram-button' ); ?>"><?php _e(' Display View on Instagram button', 'wpzoom-instagram-widget' ); ?></label>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['center-view-on-instagram-button'] ); ?> id="<?php echo $this->get_field_id( 'center-view-on-instagram-button' ); ?>" name="<?php echo $this->get_field_name( 'center-view-on-instagram-button' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'center-view-on-instagram-button' ); ?>"><?php _e(' Center View on Instagram button', 'wpzoom-instagram-widget' ); ?></label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'access-token' ); ?>"><?php esc_html_e( 'Access Token:', 'wpzoom-instagram-widget' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'access-token' ); ?>" name="<?php echo $this->get_field_name( 'access-token' ); ?>" type="text" value="<?php echo esc_attr( $instance['access-token'] ); ?>"/>

			<small>
				<?php
				echo wp_kses_post(
					sprintf(
						__( 'You can find your Access Token on this <a href="%1$s">address</a>.', 'wpzoom-instagram-widget' ),
						'http://www.wpzoom.com/instagram/'
					)
				);
				?>
			</small>
		</p>

	<?php
	}

	protected function display_items( $items, $instance ) {
		$count = 0;
		?>
		<ul class="zoom-instagram-widget__items" data-images-per-row="<?php echo esc_attr( $instance['images-per-row'] ); ?>" data-image-width="<?php echo esc_attr( $instance['image-width'] ); ?>">

			<?php foreach ( $items->data as $item ) : ?>
				<?php
				$link = $item->link;
				$src = $item->images->thumbnail->url;
				?>

				<li class="zoom-instagram-widget__item">
					<a href="<?php echo $link; ?>">
						<img src="<?php echo $src; ?>" alt="">
					</a>
				</li>

				<?php if ( ++$count == $instance['image-limit'] ) break; ?>

			<?php endforeach; ?>

		</ul>

		<div style="clear:both;"></div>
	<?php
	}

	protected function display_instagram_button( $instance ) {
		$screen_name                     = $instance['screen-name'];
		$show_view_on_instagram_button   = $instance['show-view-on-instagram-button'];
		$center_view_on_instagram_button = $instance['center-view-on-instagram-button'];

		if ( ! $show_view_on_instagram_button ) {
			return;
		}

		?>
		<div class="zoom-instagram-widget__follow-me <?php echo ($center_view_on_instagram_button ? 'zoom-instagram-widget__follow-me--center' : ''); ?>">
			<a href="<?php printf( 'http://instagram.com/%s?ref=badge', esc_attr( $screen_name ) ); ?>" class="ig-b- ig-b-v-24" target="_blank"><img src="//badges.instagram.com/static/images/ig-badge-view-24.png" alt="Instagram" /></a>
		</div>
	<?php
	}

	/**
	 * Output errors if widget is misconfigured and current user can manage options (plugin settings).
	 *
	 * @return void
	 */
	protected function display_errors() {
		if ( current_user_can( 'edit_theme_options' ) ) {
			?>
			<p>
				<?php _e( 'Instagram Widget misconfigured, check widget settings.', 'wpzoom-instagram-widget' ); ?>
			</p>
		<?php
		} else {
			echo "&#8230;";
		}
	}

	/**
	 * @param $screen_name string Instagram username
	 *
	 * @return array|bool Array of tweets or false if method fails
	 */
	protected function get_items( $screen_name ) {
		$transient = 'zoom_instagram_t6e_' . $screen_name;

		if ( false !== ( $result = get_transient( $transient ) ) ) {
			return $result;
		}

		$user_id = $this->get_user_id( $screen_name );

		$response = wp_remote_get( sprintf( 'https://api.instagram.com/v1/users/%s/media/recent/?access_token=%s', $user_id, $this->access_token ) );

		if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) ) {
			set_transient( $transient, false, MINUTE_IN_SECONDS );

			return false;
		}

		$result = json_decode( wp_remote_retrieve_body( $response ) );

		set_transient( $transient, $result, 30 * MINUTE_IN_SECONDS );

		return $result;
	}

	/**
	 * @param $screen_name string Instagram username
	 *
	 * @return bool|int Instagram user id or false on error
	 */
	protected function get_user_id( $screen_name ) {
		$user_id_option = 'zoom_instagram_uid_' . $screen_name;

		if ( false !== ( $user_id = get_option( $user_id_option ) ) ) {
			return $user_id;
		}

		$response = wp_remote_get( sprintf( 'https://api.instagram.com/v1/users/search?q=%s&access_token=%s', $screen_name, $this->access_token ) );

		if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$result = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! isset( $result->data[0] ) ) {
			return false;
		}

		$user_id = $result->data[0]->id;

		update_option( $user_id_option, $user_id );

		return $user_id;
	}
}
