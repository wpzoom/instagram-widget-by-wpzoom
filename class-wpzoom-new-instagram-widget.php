<?php
/**
 * Exit if accessed directly.
 */
defined( 'ABSPATH' ) or die;

/**
 * WPZOOM New Instagram Widget class
 *
 * @package Wpzoom_Instagram_Widget
 */
class Wpzoom_New_Instagram_Widget extends WP_Widget {
	/**
	 * @var Wpzoom_New_Instagram_Widget_Display
	 */
	protected $display;

	/**
	 * @var array Default widget settings.
	 */
	protected $defaults;

	public function __construct() {
		parent::__construct(
			'wpzoom_new_instagram_widget',
			esc_html__( 'Instagram Widget by WPZOOM (NEW!)', 'instagram-widget-by-wpzoom' ),
			array(
				'classname'   => 'zoom-new-instagram-widget',
				'description' => __( 'Displays a user\'s Instagram timeline.', 'instagram-widget-by-wpzoom' ),
			)
		);

		$this->defaults = array(
			'title'   => esc_html__( 'Instagram', 'instagram-widget-by-wpzoom' ),
			'feed'    => -1,
			'preview' => array(),
		);
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
		$this->display = Wpzoom_Instagram_Widget_Display::getInstance();

		$instance = wp_parse_args( (array) $instance, $this->defaults );
		$feed = isset( $instance['feed'] ) ? intval( $instance['feed'] ) : -1;
		$preview = isset( $instance['preview'] ) ? (array) $instance['preview'] : array();
		$preview_args = $preview;

		if ( ! empty( $preview ) ) {
			unset(
				$preview_args['user-id'],
				$preview_args['check-new-posts-interval-number'],
				$preview_args['check-new-posts-interval-suffix'],
				$preview_args['enable-request-timeout']
			);
		}

		wp_enqueue_style(
			'wpz-insta_feed-styles',
			admin_url( 'admin-ajax.php?action=wpz-insta_feed-styles&' . ( ! empty( $preview ) ? 'wpz-insta-widget-preview=true&' . http_build_query( $preview_args ) : 'feed=' . $feed ) ),
			array(),
			WPZOOM_INSTAGRAM_VERSION
		);

		echo $args['before_widget'];

		if ( $instance['title'] ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

		if ( $feed > -1 ) {
			$this->display->output_feed( $feed );
		} elseif ( ! empty( $preview ) ) {
			$this->display->output_preview( $preview );
		} else {
			esc_html_e( '<p>There was a problem displaying a feed. Please check the configuration.</p>', 'instagram-widget-by-wpzoom' );
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
		$instance['feed']  = intval( $new_instance['feed'] );

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
		$current_feed = isset( $instance['feed'] ) && intval( $instance['feed'] ) > -1 ? intval( $instance['feed'] ) : -1;
		$all_feeds = get_posts( array( 'numberposts' => -1, 'post_type' => 'wpz-insta_feed' ) );

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'instagram-widget-by-wpzoom' ); ?></label>
			<input type="text" name="<?php echo $this->get_field_name( 'title' ); ?>" id="<?php echo $this->get_field_id( 'title' ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'feed' ); ?>"><?php esc_html_e( 'Feed to Display:', 'instagram-widget-by-wpzoom' ); ?></label>
			<?php if ( ! is_wp_error( $all_feeds ) && ! empty( $all_feeds ) && ( ! array_key_exists( 'no_found_rows', $all_feeds ) || ( array_key_exists( 'no_found_rows', $all_feeds ) && true !== $all_feeds['no_found_rows'] ) ) ) : ?>
				<select name="<?php echo $this->get_field_name( 'feed' ); ?>" id="<?php echo $this->get_field_id( 'feed' ); ?>" class="widefat">
					<option value="-1" <?php selected( $current_feed, -1 ); ?> disabled hidden><?php esc_html_e( '-- Select a Feed --', 'instagram-widget-by-wpzoom' ); ?></option>
					<?php foreach ( $all_feeds as $feed ) : ?>
						<option value="<?php echo esc_attr( $feed->ID ); ?>" <?php selected( $current_feed, $feed->ID ); ?>>
							<?php echo get_the_title( $feed ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php else : ?>
				<strong><?php esc_html_e( 'You must create some feeds to use this widget properly.', 'instagram-widget-by-wpzoom' ); ?></strong>
			<?php endif; ?>
		</p>
		<?php
	}
}
