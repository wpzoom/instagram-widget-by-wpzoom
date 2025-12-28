<?php
/**
 * Instagram Insights Class
 *
 * Handles the Insights admin page for Instagram business accounts.
 * Displays analytics data including followers, reach, impressions,
 * engagement, and recent posts performance.
 *
 * @package suspended
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPZOOM_Instagram_Insights {
    /**
     * Single instance of the class
     *
     * @var WPZOOM_Instagram_Insights|null
     */
    private static $instance = null;

    /**
     * Instagram API instance
     *
     * @var Wpzoom_Instagram_Widget_API
     */
    private $api;

    /**
     * Instagram Feed Pro instance for insights data
     *
     * @var Instagram_Feed_Pro
     */
    private $feed_pro;

    /**
     * Get singleton instance
     *
     * @return WPZOOM_Instagram_Insights
     */
    public static function getInstance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->api = WPZOOM_Instagram_Widget_API::getInstance();
        $this->feed_pro = new Instagram_Feed_Pro();

        add_action( 'admin_menu', array( $this, 'add_insights_submenu' ), 20 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_insights_scripts' ) );
        add_action( 'admin_head', array( $this, 'add_menu_badge_styles' ) );
        add_action( 'wp_ajax_wpzoom_instagram_fetch_insights', array( $this, 'fetch_insights_data' ) );
        add_action( 'wp_ajax_wpzoom_instagram_load_more_posts', array( $this, 'load_more_posts' ) );
    }

    /**
     * Add Insights submenu page
     */
    public function add_insights_submenu() {
        $menu_title = sprintf(
            '%s <span class="wpz-insta-menu-badge">%s</span>',
            __( 'Insights', 'instagram-widget-by-wpzoom' ),
            __( 'NEW', 'instagram-widget-by-wpzoom' )
        );

        add_submenu_page(
            'edit.php?post_type=wpz-insta_feed',
            __( 'Insights', 'instagram-widget-by-wpzoom' ),
            $menu_title,
            'manage_options',
            'wpzoom-instagram-insights',
            array( $this, 'render_insights_page' )
        );
    }

    /**
     * Add inline styles for the menu badge
     */
    public function add_menu_badge_styles() {
        ?>
        <style>
            .wpz-insta-menu-badge {
                display: inline-block;
                background: linear-gradient(135deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
                color: #fff;
                font-size: 9px;
                font-weight: 600;
                line-height: 1;
                padding: 3px 5px;
                border-radius: 3px;
                margin-left: 5px;
                vertical-align: middle;
                text-transform: uppercase;
                letter-spacing: 0.3px;
            }
        </style>
        <?php
    }

    /**
     * Enqueue necessary scripts and styles
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_insights_scripts( $hook ) {
        if ( 'wpz-insta_feed_page_wpzoom-instagram-insights' !== $hook ) {
            return;
        }

        // Get the asset file for dependencies and version
        $asset_file = WPZOOM_INSTAGRAM_PLUGIN_PATH . 'dist/scripts/backend/insights.asset.php';
        $asset = file_exists( $asset_file ) ? require $asset_file : array(
            'dependencies' => array(),
            'version' => WPZOOM_INSTAGRAM_VERSION
        );

        // Enqueue main backend styles (includes footer styles)
        wp_enqueue_style(
            'wpzoom-instagram-widget-backend',
            WPZOOM_INSTAGRAM_PLUGIN_URL . 'dist/styles/backend/index.css',
            array(),
            WPZOOM_INSTAGRAM_VERSION
        );

        // Enqueue insights-specific styles
        wp_enqueue_style(
            'wpzoom-instagram-insights',
            WPZOOM_INSTAGRAM_PLUGIN_URL . 'dist/styles/backend/insights.css',
            array( 'wpzoom-instagram-widget-backend' ),
            WPZOOM_INSTAGRAM_VERSION
        );

        // Enqueue script (Chart.js is now bundled)
        wp_enqueue_script(
            'wpzoom-instagram-insights',
            WPZOOM_INSTAGRAM_PLUGIN_URL . 'dist/scripts/backend/insights.js',
            array_merge( array( 'jquery' ), $asset['dependencies'] ),
            $asset['version'],
            true
        );

        // Localize script with data and translations
        wp_localize_script( 'wpzoom-instagram-insights', 'wpzoomInsights', array(
            'nonce' => wp_create_nonce( 'wpzoom_instagram_insights' ),
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'i18n' => array(
                // Chart labels
                'followers' => __( 'Followers', 'instagram-widget-by-wpzoom' ),
                'followerGrowth' => __( 'Follower growth', 'instagram-widget-by-wpzoom' ),
                'reach' => __( 'Reach', 'instagram-widget-by-wpzoom' ),
                'impressions' => __( 'Views', 'instagram-widget-by-wpzoom' ),
                'views' => __( 'Views', 'instagram-widget-by-wpzoom' ),
                'accounts_engaged' => __( 'Accounts Engaged', 'instagram-widget-by-wpzoom' ),

                // Date format
                'dateRangeFormat' => __( 'Data for %s - %s', 'instagram-widget-by-wpzoom' ),

                // Followers change
                'followersChange' => __( 'followers', 'instagram-widget-by-wpzoom' ),
                'gained' => __( 'gained', 'instagram-widget-by-wpzoom' ),
                'lost' => __( 'lost', 'instagram-widget-by-wpzoom' ),
                'startedWith' => __( 'Started with', 'instagram-widget-by-wpzoom' ),
                'endedWith' => __( ', ended with', 'instagram-widget-by-wpzoom' ),

                // Metrics
                'totalPeriod' => __( 'Total for period', 'instagram-widget-by-wpzoom' ),

                // Post labels
                'impressionsLabel' => __( 'Impressions', 'instagram-widget-by-wpzoom' ),
                'reachLabel' => __( 'Reach', 'instagram-widget-by-wpzoom' ),
                'likes' => __( 'Likes', 'instagram-widget-by-wpzoom' ),
                'comments' => __( 'Comments', 'instagram-widget-by-wpzoom' ),
                'saved' => __( 'Saved', 'instagram-widget-by-wpzoom' ),
                'videoViews' => __( 'Video Views', 'instagram-widget-by-wpzoom' ),
                'interactions' => __( 'Total Interactions', 'instagram-widget-by-wpzoom' ),
                'viewPost' => __( 'View Post', 'instagram-widget-by-wpzoom' ),
                'noPosts' => __( 'No recent posts found.', 'instagram-widget-by-wpzoom' ),
                'loadMore' => __( 'Load More Posts', 'instagram-widget-by-wpzoom' ),
                'loading' => __( 'Loading...', 'instagram-widget-by-wpzoom' ),
                'noMorePosts' => __( 'No more posts to load.', 'instagram-widget-by-wpzoom' ),

                // Error messages
                'error' => __( 'Error', 'instagram-widget-by-wpzoom' ),
                'dismiss' => __( 'Dismiss this notice.', 'instagram-widget-by-wpzoom' ),
                'noAccount' => __( 'No account selected.', 'instagram-widget-by-wpzoom' ),
                'fetchError' => __( 'Failed to fetch insights data. Please try again.', 'instagram-widget-by-wpzoom' ),
                'networkError' => __( 'Network error occurred. Please try again.', 'instagram-widget-by-wpzoom' ),
                'connectionError' => __( 'Could not connect to server. Please check your connection.', 'instagram-widget-by-wpzoom' ),
                'permissionError' => __( 'You do not have permission to access this data.', 'instagram-widget-by-wpzoom' ),
                'serverError' => __( 'Server error occurred. Please try again later.', 'instagram-widget-by-wpzoom' ),
            )
        ) );
    }

    /**
     * Render the insights page
     */
    public function render_insights_page() {
        $accounts = $this->get_connected_accounts();
        ?>
        <div class="wrap wpzoom-instagram-insights">
            <h1><?php esc_html_e( 'Instagram Insights', 'instagram-widget-by-wpzoom' ); ?></h1>

            <?php if ( empty( $accounts ) ) : ?>
                <div class="notice notice-warning">
                    <p>
                        <strong><?php esc_html_e( 'No accounts connected via Facebook found.', 'instagram-widget-by-wpzoom' ); ?></strong>
                    </p>
                    <p>
                        <?php esc_html_e( 'Instagram Insights are only available for Business or Creator accounts that are connected via Facebook.', 'instagram-widget-by-wpzoom' ); ?>
                        <?php esc_html_e( 'Accounts connected directly via Instagram do not have access to Insights data.', 'instagram-widget-by-wpzoom' ); ?>
                    </p>
                    <p>
                        <?php esc_html_e( 'To use Insights:', 'instagram-widget-by-wpzoom' ); ?>
                        <ol>
                            <li><?php esc_html_e( 'Make sure your Instagram account is a Business or Creator account', 'instagram-widget-by-wpzoom' ); ?></li>
                            <li><?php esc_html_e( 'Connect your Instagram account to a Facebook Page', 'instagram-widget-by-wpzoom' ); ?></li>
                            <li><?php esc_html_e( 'Go to Instagram Users and use the "Connect with Facebook" button', 'instagram-widget-by-wpzoom' ); ?></li>
                        </ol>

                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wpz-insta_user' ) ); ?>" class="button button-primary">
                            <?php esc_html_e( 'Go to Instagram Users', 'instagram-widget-by-wpzoom' ); ?>
                        </a>
                    </p>
                </div>
            <?php else : ?>
                <div class="insights-container">
                    <div class="insights-header">
                        <div class="account-selector">
                            <label for="account-selector" class="screen-reader-text"><?php esc_html_e( 'Select Account', 'instagram-widget-by-wpzoom' ); ?></label>
                            <select id="account-selector">
                                <?php foreach ( $accounts as $account ) : ?>
                                    <option value="<?php echo esc_attr( $account->ID ); ?>">
                                        <?php echo esc_html( $account->post_title ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="period-selector">
                            <label for="period-selector" class="screen-reader-text"><?php esc_html_e( 'Select Period', 'instagram-widget-by-wpzoom' ); ?></label>
                            <select id="period-selector">
                                <option value="7"><?php esc_html_e( 'Last 7 days', 'instagram-widget-by-wpzoom' ); ?></option>
                                <option value="14"><?php esc_html_e( 'Last 14 days', 'instagram-widget-by-wpzoom' ); ?></option>
                                <option value="30" selected><?php esc_html_e( 'Last 30 days', 'instagram-widget-by-wpzoom' ); ?></option>
                                <option value="90"><?php esc_html_e( 'Last 90 days', 'instagram-widget-by-wpzoom' ); ?></option>
                                <option value="this_month"><?php esc_html_e( 'This month', 'instagram-widget-by-wpzoom' ); ?></option>
                                <option value="last_month"><?php esc_html_e( 'Last month', 'instagram-widget-by-wpzoom' ); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="date-range-display">
                        <span id="date-range-text"></span>
                    </div>

                    <div class="insights-metrics">
                        <div class="metric-card followers">
                            <h3><?php esc_html_e( 'Total Followers', 'instagram-widget-by-wpzoom' ); ?></h3>
                            <div class="metric-value" id="followers-count">-</div>
                            <div class="followers-breakdown">
                                <div class="breakdown-item new">
                                    <span class="breakdown-label"><?php esc_html_e( 'New', 'instagram-widget-by-wpzoom' ); ?></span>
                                    <span class="breakdown-value" id="new-followers">-</span>
                                </div>
                                <div class="breakdown-item lost">
                                    <span class="breakdown-label"><?php esc_html_e( 'Lost', 'instagram-widget-by-wpzoom' ); ?></span>
                                    <span class="breakdown-value" id="lost-followers">-</span>
                                </div>
                                <div class="breakdown-item net">
                                    <span class="breakdown-label"><?php esc_html_e( 'Net', 'instagram-widget-by-wpzoom' ); ?></span>
                                    <span class="breakdown-value" id="net-followers">-</span>
                                </div>
                            </div>
                        </div>

                        <div class="metric-card reach">
                            <h3><?php esc_html_e( 'Accounts Reached', 'instagram-widget-by-wpzoom' ); ?></h3>
                            <div class="metric-value" id="reach-count">-</div>
                            <div class="metric-change" id="reach-change">-</div>
                        </div>

                        <div class="metric-card impressions">
                            <h3><?php esc_html_e( 'Views', 'instagram-widget-by-wpzoom' ); ?></h3>
                            <div class="metric-value" id="impressions-count">-</div>
                            <div class="metric-change" id="impressions-change">-</div>
                        </div>

                        <div class="metric-card engagement">
                            <h3><?php esc_html_e( 'Accounts Engaged', 'instagram-widget-by-wpzoom' ); ?></h3>
                            <div class="metric-value" id="engagement-count">-</div>
                            <div class="metric-change" id="engagement-change">-</div>
                        </div>

                        <div class="metric-card total-likes">
                            <h3><?php esc_html_e( 'Total Likes', 'instagram-widget-by-wpzoom' ); ?></h3>
                            <div class="metric-value" id="total-likes-count">-</div>
                            <div class="metric-change" id="total-likes-change">-</div>
                        </div>
                    </div>

                    <div class="insights-charts">
                        <div class="chart-container">
                            <h3 class="chart-title"><?php esc_html_e( 'Follower Growth', 'instagram-widget-by-wpzoom' ); ?></h3>
                            <p class="chart-note" id="followers-chart-note" style="display: none;"><?php esc_html_e( 'Note: Instagram limits follower data to the last 30 days.', 'instagram-widget-by-wpzoom' ); ?></p>
                            <canvas id="followers-chart" aria-label="<?php esc_attr_e( 'Follower Growth Chart', 'instagram-widget-by-wpzoom' ); ?>" role="img"></canvas>
                        </div>
                        <div class="chart-container">
                            <h3 class="chart-title"><?php esc_html_e( 'Reach', 'instagram-widget-by-wpzoom' ); ?></h3>
                            <canvas id="engagement-chart" aria-label="<?php esc_attr_e( 'Reach Chart', 'instagram-widget-by-wpzoom' ); ?>" role="img"></canvas>
                        </div>
                    </div>

                    <div class="recent-posts-section">
                        <h2><?php esc_html_e( 'Recent Posts Performance', 'instagram-widget-by-wpzoom' ); ?></h2>
                        <div id="recent-posts" class="recent-posts-grid"></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Get all connected Instagram business accounts
     *
     * @return array Array of WP_Post objects for business accounts
     */
    private function get_connected_accounts() {
        $all_accounts = get_posts( array(
            'post_type' => 'wpz-insta_user',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ) );

        // Filter to only show Facebook-connected business accounts
        $business_accounts = array_filter( $all_accounts, function( $account ) {
            $connection_type = get_post_meta( $account->ID, '_wpz-insta_connection-type', true );
            $account_type = get_post_meta( $account->ID, '_wpz-insta_account-type', true );

            // For Facebook-connected business accounts
            if ( $connection_type === 'facebook_graph_api' && $account_type === 'business' ) {
                $instagram_account_id = get_post_meta( $account->ID, '_wpz-insta_page_id', true );
                $token = get_post_meta( $account->ID, '_wpz-insta_token', true );

                return ! empty( $instagram_account_id ) && ! empty( $token );
            }

            return false;
        } );

        return array_values( $business_accounts );
    }

    /**
     * AJAX handler for fetching insights data
     */
    public function fetch_insights_data() {
        check_ajax_referer( 'wpzoom_instagram_insights', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have permission to access this data.', 'instagram-widget-by-wpzoom' ) );
        }

        $account_id = isset( $_POST['account_id'] ) ? absint( $_POST['account_id'] ) : 0;
        $since_date = isset( $_POST['since_date'] ) ? sanitize_text_field( $_POST['since_date'] ) : '';
        $until_date = isset( $_POST['until_date'] ) ? sanitize_text_field( $_POST['until_date'] ) : '';

        if ( empty( $account_id ) ) {
            wp_send_json_error( __( 'Invalid account ID.', 'instagram-widget-by-wpzoom' ) );
        }

        // Validate date format
        if ( empty( $since_date ) || empty( $until_date ) ||
             ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $since_date ) ||
             ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $until_date ) ) {
            wp_send_json_error( __( 'Invalid date range.', 'instagram-widget-by-wpzoom' ) );
        }

        $instagram_account_id = get_post_meta( $account_id, '_wpz-insta_page_id', true );
        $token = get_post_meta( $account_id, '_wpz-insta_token', true );

        if ( empty( $instagram_account_id ) || empty( $token ) ) {
            wp_send_json_error( __( 'Account not properly configured.', 'instagram-widget-by-wpzoom' ) );
        }

        $data = $this->feed_pro->get_daily_insights( $instagram_account_id, $token, $since_date, $until_date );

        if ( ! $data ) {
            wp_send_json_error( __( 'Failed to fetch insights data. The Instagram API may be temporarily unavailable.', 'instagram-widget-by-wpzoom' ) );
        }

        wp_send_json_success( $data );
    }

    /**
     * AJAX handler for loading more posts with pagination
     */
    public function load_more_posts() {
        check_ajax_referer( 'wpzoom_instagram_insights', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have permission to access this data.', 'instagram-widget-by-wpzoom' ) );
        }

        $account_id = isset( $_POST['account_id'] ) ? absint( $_POST['account_id'] ) : 0;
        $cursor = isset( $_POST['cursor'] ) ? sanitize_text_field( $_POST['cursor'] ) : '';

        if ( empty( $account_id ) ) {
            wp_send_json_error( __( 'Invalid account ID.', 'instagram-widget-by-wpzoom' ) );
        }

        if ( empty( $cursor ) ) {
            wp_send_json_error( __( 'No cursor provided.', 'instagram-widget-by-wpzoom' ) );
        }

        $instagram_account_id = get_post_meta( $account_id, '_wpz-insta_page_id', true );
        $token = get_post_meta( $account_id, '_wpz-insta_token', true );

        if ( empty( $instagram_account_id ) || empty( $token ) ) {
            wp_send_json_error( __( 'Account not properly configured.', 'instagram-widget-by-wpzoom' ) );
        }

        $media_data = $this->feed_pro->get_media_insights( $instagram_account_id, $token, $cursor, 10 );

        if ( empty( $media_data['items'] ) ) {
            wp_send_json_error( __( 'No more posts found.', 'instagram-widget-by-wpzoom' ) );
        }

        wp_send_json_success( array(
            'posts'       => $media_data['items'],
            'next_cursor' => $media_data['next_cursor'],
        ) );
    }
}

// Initialize the insights class
WPZOOM_Instagram_Insights::getInstance();
