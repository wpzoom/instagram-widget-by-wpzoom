<?php

class Wpzoom_Instagram_Widget extends WP_Widget {
	/**
	 * @var Wpzoom_Instagram_Widget_API
	 */
	protected $api;

	/**
	 * @var array Default widget settings.
	 */
	protected $defaults;

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
			'title'                         => esc_html__( 'Instagram', 'wpzoom-instagram-widget' ),
			'button_text'                   => esc_html__( 'View on Instagram', 'wpzoom-instagram-widget' ),
			'image-limit'                   => 9,
			'show-view-on-instagram-button' => true,
			'show-counts-on-hover'          => false,
            'show-user-info'                => false,
			'show-user-bio'                 => false,
			'lazy-load-images'              => false,
			'disable-video-thumbs'          => false,
			'images-per-row'                => 3,
			'image-width'                   => 120,
			'image-spacing'                 => 10,
			'image-resolution'              => 'default_algorithm',
			'username'                      => '',
		);

		$this->api = Wpzoom_Instagram_Widget_API::getInstance();

		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
	}

	/**
	 * Widget specific scripts & styles
	 */
	public function scripts() {
		wp_enqueue_style( 'zoom-instagram-widget', plugin_dir_url( dirname( __FILE__ ) . '/instagram-widget-by-wpzoom.php' ) . 'css/instagram-widget.css', array('dashicons'), '1.4.2' );
		wp_enqueue_script( 'zoom-instagram-widget-lazy-load', plugin_dir_url( dirname( __FILE__ ) . '/instagram-widget-by-wpzoom.php' ) . 'js/jquery.lazy.min.js', array( 'jquery' ), '1.4.2' );
		wp_enqueue_script( 'zoom-instagram-widget', plugin_dir_url( dirname( __FILE__ ) . '/instagram-widget-by-wpzoom.php' ) . 'js/instagram-widget.js', array( 'jquery' ), '1.4.2' );
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

		echo $args['before_widget'];

		if ( $instance['title'] ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}


		$items = $this->api->get_items( $instance );

		if ( ! is_array( $items ) ) {
			$this->display_errors();
		} else {

			if ( ! empty( $instance['show-user-info'] ) ) {
				$user_info = $this->api->get_user_info($instance['username']);

				if (
					is_object( $user_info ) &&
					! empty( $user_info ) &&
					! empty( $user_info->data )
				) {
					$this->display_user_info( $instance, $user_info );
				}
			}

			$this->display_items( $items['items'], $instance );
			$this->display_instagram_button( $instance, $items['username'] );
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
		$instance['button_text'] = sanitize_text_field( $new_instance['button_text'] );

		$instance['image-limit'] = ( 0 !== (int) $new_instance['image-limit'] ) ? (int) $new_instance['image-limit'] : null;

		$instance['images-per-row'] = ( 0 !== (int) $new_instance['images-per-row'] ) ? (int) $new_instance['images-per-row'] : null;
		$instance['image-width'] = ( 0 !== (int) $new_instance['image-width'] ) ? (int) $new_instance['image-width'] : null;
		$instance['image-spacing'] = ( 0 <= (int) $new_instance['image-spacing'] ) ? (int) $new_instance['image-spacing'] : null;
		$instance['image-resolution'] = !empty($new_instance['image-resolution']) ?  $new_instance['image-resolution'] : $this->defaults['image-resolution'];
		$instance['username'] = !empty($new_instance['username']) ?  $new_instance['username'] : $this->defaults['username'];

		$instance['show-view-on-instagram-button'] = ! empty( $new_instance['show-view-on-instagram-button'] );
		$instance['show-counts-on-hover']          = ! empty( $new_instance['show-counts-on-hover'] );
        $instance['show-user-info']                = ! empty( $new_instance['show-user-info'] );
		$instance['show-user-bio']                 = ! empty( $new_instance['show-user-bio'] );
		$instance['lazy-load-images']              = ! empty( $new_instance['lazy-load-images'] );
		$instance['disable-video-thumbs']          = ! empty( $new_instance['disable-video-thumbs'] );


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

		<?php if ( empty($instance['username']) && ! $this->api->is_configured() ) : ?>

			<p style="color: #d54e21">
				<?php
				printf( __( 'You need to configure <a href="%1$s">plugin settings</a> before using this widget.', 'wpzoom-instagram-widget' ),
					menu_page_url( 'wpzoom-instagram-widget', false ) );
				 ?>
			</p>

		<?php endif; ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'wpzoom-instagram-widget' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>"/>
		</p>


		<p>
			<label for="<?php echo $this->get_field_id( 'image-limit' ); ?>"><?php esc_html_e( 'Number of Images Shown:', 'wpzoom-instagram-widget' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'image-limit' ); ?>" name="<?php echo $this->get_field_name( 'image-limit' ); ?>" type="number" min="1" max="30" value="<?php echo esc_attr( $instance['image-limit'] ); ?>"/>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'images-per-row' ); ?>"><?php esc_html_e( 'Desired number of Images per row:', 'wpzoom-instagram-widget' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'images-per-row' ); ?>" name="<?php echo $this->get_field_name( 'images-per-row' ); ?>" type="number" min="1" max="20" value="<?php echo esc_attr( $instance['images-per-row'] ); ?>"/>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'image-width' ); ?>"><?php esc_html_e( 'Desired Image width in pixels:', 'wpzoom-instagram-widget' ); ?> <small>(Just integer)</small></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'image-width' ); ?>" name="<?php echo $this->get_field_name( 'image-width' ); ?>" type="number" min="20" value="<?php echo esc_attr( $instance['image-width'] ); ?>"/>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'image-spacing' ); ?>"><?php esc_html_e( 'Image spacing in pixels:', 'wpzoom-instagram-widget' ); ?> <small>(Just integer)</small></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'image-spacing' ); ?>" name="<?php echo $this->get_field_name( 'image-spacing' ); ?>" type="number" min="0" max="50" value="<?php echo esc_attr( $instance['image-spacing'] ); ?>"/>
		</p>

		<p class="description">
			<?php
			echo wp_kses_post(
				__( 'Fields above do not influence directly widget appearance. Final number of images per row and image width is calculated depending on browser resolution. This ensures your photos look beautiful on all devices.', 'wpzoom-instagram-widget' )
			);
			?>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'username' ); ?>"><strong><?php esc_html_e( 'Instagram @username:', 'wpzoom-instagram-widget' ); ?></strong></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'username' ); ?>" name="<?php echo $this->get_field_name( 'username' ); ?>" type="text" value="<?php echo esc_attr( $instance['username'] ); ?>"/>
		</p>

        <p class="description">

            <?php
            printf( __( 'If you have already connected your Instagram account in the <a href="%1$s">plugin settings</a>, leave this field empty. You can use this option if you want to display the feed of a different Instagram account.', 'wpzoom-instagram-widget' ),
                menu_page_url( 'wpzoom-instagram-widget', false ) );
             ?>

        </p>

		<p>
			<label for="<?php echo $this->get_field_id( 'image-resolution' ); ?>"><?php esc_html_e( 'Set forced image resolution:', 'wpzoom-instagram-widget' ); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id( 'image-resolution' ); ?>" name="<?php echo $this->get_field_name( 'image-resolution' ); ?>">
				<option value="default_algorithm" <?php selected( $instance['image-resolution'], "default_algorithm" ); ?>>
					<?php _e('By Default Algorithm', 'wpzoom-instagram-widget' ); ?>
				</option>
				<option value="thumbnail" <?php selected( $instance['image-resolution'], "thumbnail" ); ?>>
					<?php _e('Thumbnail ( 150x150px )', 'wpzoom-instagram-widget' ); ?>
				</option>
				<option value="low_resolution" <?php selected( $instance['image-resolution'], "low_resolution" ); ?>>
					<?php _e('Low Resolution ( 320x320px )', 'wpzoom-instagram-widget' ); ?>

				</option>
				<option value="standard_resolution" <?php selected( $instance['image-resolution'], "standard_resolution" ); ?>>
					<?php _e('Standard Resolution ( 640x640px )', 'wpzoom-instagram-widget' ); ?>
				</option>
			</select>
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['show-user-info'] ); ?> id="<?php echo $this->get_field_id( 'show-user-info' ); ?>" name="<?php echo $this->get_field_name( 'show-user-info' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'show-user-info' ); ?>"><?php _e(' Display <strong>User Details</strong>', 'wpzoom-instagram-widget' ); ?></label>
		</p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked( $instance['show-user-bio'] ); ?> id="<?php echo $this->get_field_id( 'show-user-bio' ); ?>" name="<?php echo $this->get_field_name( 'show-user-bio' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'show-user-bio' ); ?>"><?php _e(' Display <strong>Bio in User Details</strong>', 'wpzoom-instagram-widget' ); ?></label>
        </p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['show-view-on-instagram-button'] ); ?> id="<?php echo $this->get_field_id( 'show-view-on-instagram-button' ); ?>" name="<?php echo $this->get_field_name( 'show-view-on-instagram-button' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'show-view-on-instagram-button' ); ?>"><?php _e(' Display <strong>View on Instagram</strong> button', 'wpzoom-instagram-widget' ); ?></label>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['show-counts-on-hover'] ); ?> id="<?php echo $this->get_field_id( 'show-counts-on-hover' ); ?>" name="<?php echo $this->get_field_name( 'show-counts-on-hover' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'show-counts-on-hover' ); ?>"><?php _e(' Show <strong>overlay with number of comments and likes</strong> on hover', 'wpzoom-instagram-widget' ); ?></label>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['lazy-load-images'] ); ?> id="<?php echo $this->get_field_id( 'lazy-load-images' ); ?>" name="<?php echo $this->get_field_name( 'lazy-load-images' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'lazy-load-images' ); ?>"><?php _e('Lazy Load <strong>images</strong>', 'wpzoom-instagram-widget' ); ?></label>
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['disable-video-thumbs'] ); ?> id="<?php echo $this->get_field_id( 'disable-video-thumbs' ); ?>" name="<?php echo $this->get_field_name( 'disable-video-thumbs' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'disable-video-thumbs' ); ?>"><?php _e('Hide video <strong>thumbnails</strong>', 'wpzoom-instagram-widget' ); ?></label>
		</p>
        <p>
            <label for="<?php echo $this->get_field_id( 'button_text' ); ?>"><?php esc_html_e( 'Button Text:', 'wpzoom-instagram-widget' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'button_text' ); ?>" name="<?php echo $this->get_field_name( 'button_text' ); ?>" type="text" value="<?php echo esc_attr( $instance['button_text'] ); ?>"/>
        </p>



	<?php
	}

	protected function display_items( $items, $instance ) {
		$count = 0;
		$show_overlay = wp_validate_boolean($instance['show-counts-on-hover']);
		$small_class = (!empty($instance['image-width']) && $instance['image-width'] <= 180) ? 'small' : '';
		?>
		<ul class="zoom-instagram-widget__items zoom-instagram-widget__items--no-js"
		    data-images-per-row="<?php echo esc_attr( $instance['images-per-row'] ); ?>"
		    data-image-width="<?php echo esc_attr( $instance['image-width'] ); ?>"
			data-image-spacing="<?php echo esc_attr( $instance['image-spacing'] ); ?>"
			data-image-lazy-loading="<?php echo esc_attr( $instance['lazy-load-images'] ); ?>">

			<?php foreach ( $items as $item ) : ?>
				<?php
				$link = $item['link'];
                $src = $item['image-url'];
				$alt = esc_attr($item['image-caption']);
				$likes    = $item['likes_count'];
				$comments = $item['comments_count'];
				?>

                <li class="zoom-instagram-widget__item">

	                <?php
	                $inline_style = 'width:'.esc_attr( $instance['image-width']).'px;';
	                $inline_style .= 'height:'.esc_attr( $instance['image-width']).'px;';
	                if(empty( $instance['lazy-load-images'] )){
		                $inline_style.= "background-image: url('".$src."');";
	                }

	                if($show_overlay):?>
	                <div class='hover-layout zoom-instagram-widget__overlay zoom-instagram-widget__black <?php echo $small_class?>'>

		                <div class='hover-controls'>
			                <span  class="dashicons dashicons-heart"></span>
			                <span class="counter"><?php echo $this->format_number($likes); ?></span>
			                <span class="dashicons dashicons-format-chat"></span>
			                <span class="counter"><?php echo $this->format_number($comments); ?></span>
		                </div>
		                <div class='zoom-instagram-icon-wrap'>
			                <a class="zoom-svg-instagram-stroke" href="<?php echo $link; ?>" rel="noopener" target="_blank" title="<?php echo $alt; ?>"></a>
		                </div>


						<a class='zoom-instagram-link' data-src="<?php echo $src; ?>" style="<?php echo $inline_style; ?>"
                        href="<?php echo $link; ?>" target="_blank" rel="noopener" title="<?php echo $alt; ?>"
                    >
                    </a>
	                </div>
	                <?php else: ?>
		                <a class='zoom-instagram-link' data-src="<?php echo $src; ?>" style="<?php echo $inline_style; ?>"
		                   href="<?php echo $link; ?>" target="_blank" rel="noopener" title="<?php echo $alt; ?>"
		                >
		                </a>
	                <?php endif; ?>
                </li>

				<?php if ( ++$count === $instance['image-limit'] ) break; ?>

			<?php endforeach; ?>

		</ul>

		<div style="clear:both;"></div>
	<?php
	}


	protected function display_user_info( $instance, $user_info ) {
		?>
		<div class="zoom-instagram-widget-user-info">
			<?php if ( ! empty( $user_info->data->profile_picture ) ): ?>
				<div class="zoom-instagram-widget-user-info-picture">
					<a target="_blank" rel="noopener" href="<?php printf( 'http://instagram.com/%s?ref=badge', esc_attr( $user_info->data->username ) ); ?>"><img width="90" src="<?php echo $user_info->data->profile_picture ?>" alt="<?php echo esc_attr( $user_info->data->full_name ) ?>"/></a>
				</div>
			<?php endif; ?>
			<div class="zoom-instagram-widget-user-info-meta">
				<div class="zoom-instagram-widget-user-info-about-data">
					<div class="zoom-instagram-widget-user-info-names-wrapper">
						<?php if ( ! empty( $user_info->data->full_name ) ): ?>
							<div class="zoom-instagram-widget-user-info-fullname">
								<?php esc_html_e( $user_info->data->full_name ) ?>
							</div>
						<?php endif; ?>
						<?php if ( ! empty( $user_info->data->username ) ): ?>
							<div class="zoom-instagram-widget-user-info-username">
								<?php esc_html_e( '@' . $user_info->data->username ) ?>
							</div>
						<?php endif; ?>
					</div>
					<div>
						<a class="zoom-instagram-widget-user-info-follow-button" target="_blank" rel="noopener" href="<?php printf( 'http://instagram.com/%s?ref=badge', esc_attr( $user_info->data->username ) ); ?>">
							<?php _e( 'Follow', 'wpzoom-instagram-widget' ) ?>
						</a>
					</div>
				</div>
				<div class="zoom-instagram-widget-user-info-stats">
					<?php if ( ! empty( $user_info->data->counts->media ) ): ?>
						<div>
							<div class="zoom-instagram-widget-user-info-counts"
							     title="<?php echo number_format($user_info->data->counts->media)?>">
								<?php echo $this->format_number($user_info->data->counts->media); ?>
							</div>
							<div class="zoom-instagram-widget-user-info-counts-subhead">
								<?php _e( 'posts', 'wpzoom-instagram-widget' ); ?>
							</div>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $user_info->data->counts->followed_by ) ): ?>
						<div class="zoom-instagram-widget-user-info-middle-cell">
							<div class="zoom-instagram-widget-user-info-counts"
							     title="<?php echo number_format($user_info->data->counts->followed_by)?>">
								<?php echo $this->format_number($user_info->data->counts->followed_by); ?>
							</div>
							<div class="zoom-instagram-widget-user-info-counts-subhead">
								<?php _e( 'followers', 'wpzoom-instagram-widget' ); ?>
							</div>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $user_info->data->counts->follows ) ): ?>
						<div>
							<div class="zoom-instagram-widget-user-info-counts"
							     title="<?php echo number_format($user_info->data->counts->follows)?>">
								<?php echo $this->format_number($user_info->data->counts->follows); ?>
							</div>
							<div class="zoom-instagram-widget-user-info-counts-subhead">
								<?php _e( 'following', 'wpzoom-instagram-widget' ); ?>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>

		</div>
		<?php
            if ( ! empty( $instance['show-user-bio'] ) ) {

                if ( ! empty( $user_info->data->bio ) ): ?>
        			<div class="zoom-instagram-widget-user-info-bio"><?php echo nl2br( $user_info->data->bio ) ?></div>
        		<?php endif;

            } ?>

		<?php
	}

	public function format_number( $num ) {

		if ( $num < 10000 ) {
			return number_format( $num );
		}

		$units = array( '', 'k', 'm', 'b', 't' );
		for ( $i = 0; $num >= 1000; $i ++ ) {
			$num /= 1000;
		}

		return round( $num, 1 ) . $units[ $i ];
	}

	protected function display_instagram_button( $instance, $username) {
		$show_view_on_instagram_button   = $instance['show-view-on-instagram-button'];

		if ( ! $show_view_on_instagram_button ) {
			return;
		}

		?>
		<div class="zoom-instagram-widget__follow-me">
			<a href="<?php printf( 'http://instagram.com/%s?ref=badge', esc_attr( $username ) ); ?>" class="ig-b- ig-b-v-24" rel="noopener" target="_blank"><?php echo esc_attr( $instance['button_text'] ); ?></a>
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
				<?php _e( 'Instagram Widget misconfigured, check plugin &amp; widget settings.', 'wpzoom-instagram-widget' ); ?>
			</p>
		<?php
		} else {
			echo "&#8230;";
		}
	}
}
