<?php
/**
 * A tip on the Instagram Feeds list screen: place feeds and edit the site by chatting with Claude or ChatGPT through the
 * free WPZOOM AI Connect plugin. Shown only on that screen, to people who can manage the site, on WordPress 6.9+ (where AI Connect runs),
 * until they hide it.
 *
 * @package WPZOOM_Instagram_Widget
 * @since   2.4.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPZOOM_Instagram_AI_Tip {

	const SCREEN      = 'edit-wpz-insta_feed';
	const HIDDEN_META = 'wpz_insta_ai_tip_hidden';
	const PLUGIN_FILE = 'wpzoom-ai-connect/wpzoom-ai-connect.php';

	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'styles' ), 110 );
		add_action( 'all_admin_notices', array( __CLASS__, 'render' ), 20 );
		add_action( 'admin_post_wpz_insta_hide_ai_tip', array( __CLASS__, 'hide' ) );
	}

	/** Whether to show the tip on the current screen. */
	private static function visible() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen && self::SCREEN === $screen->id
			&& function_exists( 'wp_register_ability' )
			&& current_user_can( 'manage_options' )
			&& ! get_user_meta( get_current_user_id(), self::HIDDEN_META, true );
	}

	/** Install, activate or open AI Connect, depending on its state; null when the user can do none of them. */
	private static function action() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( is_plugin_active( self::PLUGIN_FILE ) ) {
			return array( __( 'Open AI Connect', 'instagram-widget-by-wpzoom' ), add_query_arg( array( 'page' => 'wpzoom-ai-connect', 'ref' => 'instagram-widget' ), admin_url( 'admin.php' ) ) );
		}
		if ( file_exists( WP_PLUGIN_DIR . '/' . self::PLUGIN_FILE ) ) {
			return current_user_can( 'activate_plugin', self::PLUGIN_FILE )
				? array( __( 'Activate AI Connect', 'instagram-widget-by-wpzoom' ), wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( self::PLUGIN_FILE ) ), 'activate-plugin_' . self::PLUGIN_FILE ) )
				: null;
		}
		return current_user_can( 'install_plugins' )
			? array( __( 'Install AI Connect', 'instagram-widget-by-wpzoom' ), admin_url( 'plugin-install.php?s=wpzoom%2520ai%2520connect&tab=search&type=term' ) )
			: null;
	}

	/** An example request that works as written: it names one of the user's feeds by its shortcode. */
	private static function example() {
		$feeds = get_posts(
			array(
				'post_type'      => 'wpz-insta_feed',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		if ( $feeds ) {
			/* translators: %d: an Instagram feed's ID */
			return sprintf( __( '"Add [instagram feed="%d"] to the end of my About page"', 'instagram-widget-by-wpzoom' ), (int) $feeds[0] );
		}
		return __( '"Write a post about our latest project and add it to the menu"', 'instagram-widget-by-wpzoom' );
	}

	public static function styles() {
		if ( ! self::visible() ) {
			return;
		}
		$css = '.wpz-insta-ai-tip{display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin:16px 20px 0 2px;padding:14px 18px;background:#f0f7ff;border:1px solid #c5ddfa;border-radius:6px}'
			. '.wpz-insta-ai-tip__icon{flex:0 0 auto;color:#3496fe}'
			. '.wpz-insta-ai-tip__text{flex:1 1 320px;margin:0;font-size:13px;line-height:1.6;color:#242628}'
			. '.wpz-insta-ai-tip__text em{font-style:normal;font-weight:600}'
			. '.wpz-insta-ai-tip__actions{display:flex;align-items:center;gap:14px;flex:0 0 auto}'
			. '.wpz-insta-ai-tip__actions a.wpz-insta-ai-tip__hide{color:#646970;text-decoration:none}'
			. '.wpz-insta-ai-tip__actions a.wpz-insta-ai-tip__hide:hover{color:#1d2327}';
		wp_register_style( 'wpz-insta-ai-tip', false, array(), WPZOOM_INSTAGRAM_VERSION );
		wp_enqueue_style( 'wpz-insta-ai-tip' );
		wp_add_inline_style( 'wpz-insta-ai-tip', $css );
	}

	public static function render() {
		if ( ! self::visible() ) {
			return;
		}
		$action   = self::action();
		$hide_url = wp_nonce_url( add_query_arg( 'action', 'wpz_insta_hide_ai_tip', admin_url( 'admin-post.php' ) ), 'wpz_insta_hide_ai_tip' );
		?>
		<div class="wpz-insta-ai-tip" role="note">
			<svg class="wpz-insta-ai-tip__icon" width="22" height="22" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M3.98359 8.70108L5.58302 9.30884C6.78512 9.76562 7.73437 10.7149 8.19115 11.917L8.7989 13.5164C8.90953 13.8075 9.18856 14 9.49999 14C9.81143 14 10.0905 13.8075 10.2011 13.5164L10.8088 11.917C11.2656 10.7149 12.2149 9.76562 13.417 9.30884L15.0164 8.70108C15.3075 8.59046 15.5 8.31143 15.5 7.99999C15.5 7.68856 15.3075 7.40953 15.0164 7.2989L13.417 6.69115C12.2149 6.23437 11.2656 5.28512 10.8088 4.08302L10.2011 2.48359C10.0905 2.19247 9.81143 2 9.49999 2C9.18856 2 8.90953 2.19247 8.7989 2.48359L8.19115 4.08302C7.73437 5.28512 6.78513 6.23437 5.58302 6.69115L3.98359 7.2989C3.69247 7.40953 3.5 7.68856 3.5 7.99999C3.5 8.31143 3.69247 8.59046 3.98359 8.70108Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
				<path d="M3.5 14.75V11.75M2 13.25H5M2.75 4.25V1.25M1.25 2.75H4.25" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
			<p class="wpz-insta-ai-tip__text">
				<strong><?php esc_html_e( 'Manage your site by chatting with AI.', 'instagram-widget-by-wpzoom' ); ?></strong>
				<?php
				printf(
					/* translators: %s: an example request, in quotes */
					esc_html__( 'With our free WPZOOM AI Connect plugin, ask Claude or ChatGPT something like %s and it does it on your site.', 'instagram-widget-by-wpzoom' ),
					'<em>' . esc_html( self::example() ) . '</em>'
				);
				?>
			</p>
			<span class="wpz-insta-ai-tip__actions">
				<?php if ( $action ) : ?>
					<a class="button button-primary" href="<?php echo esc_url( $action[1] ); ?>"><?php echo esc_html( $action[0] ); ?></a>
				<?php endif; ?>
				<a href="https://www.wpzoom.com/ai-connect/" target="_blank"><?php esc_html_e( 'Learn more', 'instagram-widget-by-wpzoom' ); ?></a>
				<a class="wpz-insta-ai-tip__hide" href="<?php echo esc_url( $hide_url ); ?>"><?php esc_html_e( 'Hide', 'instagram-widget-by-wpzoom' ); ?></a>
			</span>
		</div>
		<?php
	}

	/** admin-post.php?action=wpz_insta_hide_ai_tip: hides the tip for this user and goes back to the feeds list. */
	public static function hide() {
		check_admin_referer( 'wpz_insta_hide_ai_tip' );
		if ( current_user_can( 'manage_options' ) ) {
			update_user_meta( get_current_user_id(), self::HIDDEN_META, 1 );
		}
		wp_safe_redirect( admin_url( 'edit.php?post_type=wpz-insta_feed' ) );
		exit;
	}
}

WPZOOM_Instagram_AI_Tip::init();
