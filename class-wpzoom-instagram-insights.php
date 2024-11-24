<?php
/**
 * Instagram Insights Class
 */
class WPZOOM_Instagram_Insights {
    private static $instance = null;
    private $api;
    private $feed_pro;

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->api = WPZOOM_Instagram_Widget_API::getInstance();
        $this->feed_pro = new Instagram_Feed_Pro();
        
        add_action('admin_menu', array($this, 'add_insights_submenu'), 20);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_insights_scripts'));
        add_action('wp_ajax_wpzoom_instagram_fetch_insights', array($this, 'fetch_insights_data'));
    }

    /**
     * Add Insights submenu page
     */
    public function add_insights_submenu() {
        add_submenu_page(
            'edit.php?post_type=wpz-insta_feed',
            __('Insights', 'instagram-widget-by-wpzoom'),
            __('Insights', 'instagram-widget-by-wpzoom'),
            'manage_options',
            'wpzoom-instagram-insights',
            array($this, 'render_insights_page')
        );
    }

    /**
     * Enqueue necessary scripts and styles
     */
    public function enqueue_insights_scripts($hook) {
        if ('wpz-insta_feed_page_wpzoom-instagram-insights' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'wpzoom-instagram-insights',
            WPZOOM_INSTAGRAM_PLUGIN_URL . 'dist/styles/backend/insights.css',
            array(),
            WPZOOM_INSTAGRAM_VERSION
        );

        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.7.0',
            true
        );

        wp_enqueue_script(
            'wpzoom-instagram-insights',
            WPZOOM_INSTAGRAM_PLUGIN_URL . 'dist/scripts/backend/insights.js',
            array('jquery', 'chart-js'),
            WPZOOM_INSTAGRAM_VERSION,
            true
        );

        wp_localize_script('wpzoom-instagram-insights', 'wpzoomInsights', array(
            'nonce' => wp_create_nonce('wpzoom_instagram_insights'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'i18n' => array(
                'followers' => __('Followers', 'instagram-widget-by-wpzoom'),
                'reach' => __('Reach', 'instagram-widget-by-wpzoom'),
                'impressions' => __('Impressions', 'instagram-widget-by-wpzoom'),
                'accounts_engaged' => __('Accounts Engaged', 'instagram-widget-by-wpzoom'),
                'dateRangeFormat' => __('Data for %s - %s', 'instagram-widget-by-wpzoom')
            )
        ));
    }

    /**
     * Render the insights page
     */
    public function render_insights_page() {
        $accounts = $this->get_connected_accounts();
        ?>
        <div class="wrap wpzoom-instagram-insights">
            <h1><?php _e('Instagram Insights', 'instagram-widget-by-wpzoom'); ?></h1>
            
            <?php if (empty($accounts)) : ?>
                <div class="notice notice-warning">
                    <p>
                        <?php _e('No Instagram Business accounts found. Insights are only available for Business accounts.', 'instagram-widget-by-wpzoom'); ?>
                    </p>
                    <p>
                        <?php _e('To use Insights:', 'instagram-widget-by-wpzoom'); ?>
                        <ol>
                            <li><?php _e('Convert your Instagram account to a Business account', 'instagram-widget-by-wpzoom'); ?></li>
                            <li><?php _e('Go to Instagram Users and reconnect your account', 'instagram-widget-by-wpzoom'); ?></li>
                            <li><?php _e('Make sure to use the "Connect Business Account" option', 'instagram-widget-by-wpzoom'); ?></li>
                        </ol>
                        <a href="https://www.wpzoom.com/documentation/instagram-widget/how-to-convert-instagram-account-to-business-account/" target="_blank" class="button button-secondary">
                            <?php _e('Learn How to Convert to Business Account', 'instagram-widget-by-wpzoom'); ?>
                        </a>
                        <a href="<?php echo admin_url('edit.php?post_type=wpz-insta_user'); ?>" class="button button-primary">
                            <?php _e('Go to Instagram Users', 'instagram-widget-by-wpzoom'); ?>
                        </a>
                    </p>
                </div>
            <?php else : ?>
                <div class="insights-container">
                    <div class="insights-header">
                        <div class="account-selector">
                            <select id="account-selector">
                                <?php foreach ($accounts as $account) : ?>
                                    <option value="<?php echo esc_attr($account->ID); ?>">
                                        <?php echo esc_html($account->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="period-selector">
                            <select id="period-selector">
                                <option value="7"><?php _e('Last 7 days', 'instagram-widget-by-wpzoom'); ?></option>
                                <option value="14"><?php _e('Last 14 days', 'instagram-widget-by-wpzoom'); ?></option>
                                <option value="30" selected><?php _e('Last 30 days', 'instagram-widget-by-wpzoom'); ?></option>
                                <option value="90"><?php _e('Last 90 days', 'instagram-widget-by-wpzoom'); ?></option>
                                <option value="this_month"><?php _e('This month', 'instagram-widget-by-wpzoom'); ?></option>
                                <option value="last_month"><?php _e('Last month', 'instagram-widget-by-wpzoom'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="date-range-display">
                        <span id="date-range-text"></span>
                    </div>

                    <div class="insights-metrics">
                        <div class="metric-card followers">
                            <h3><?php _e('Total Followers', 'instagram-widget-by-wpzoom'); ?></h3>
                            <div class="metric-value" id="followers-count">-</div>
                            <div class="metric-change" id="followers-change">-</div>
                            <div class="metric-period" id="followers-period"></div>
                        </div>
                        
                        <div class="metric-card reach">
                            <h3><?php _e('Reach', 'instagram-widget-by-wpzoom'); ?></h3>
                            <div class="metric-value" id="reach-count">-</div>
                            <div class="metric-change" id="reach-change">-</div>
                        </div>
                        
                        <div class="metric-card impressions">
                            <h3><?php _e('Impressions', 'instagram-widget-by-wpzoom'); ?></h3>
                            <div class="metric-value" id="impressions-count">-</div>
                            <div class="metric-change" id="impressions-change">-</div>
                        </div>

                        <div class="metric-card engagement">
                            <h3><?php _e('Accounts Engaged', 'instagram-widget-by-wpzoom'); ?></h3>
                            <div class="metric-value" id="engagement-count">-</div>
                            <div class="metric-change" id="engagement-change">-</div>
                        </div>

                        <div class="metric-card profile-views">
                            <h3><?php _e('Profile Views', 'instagram-widget-by-wpzoom'); ?></h3>
                            <div class="metric-value" id="profile-views-count">-</div>
                            <div class="metric-change" id="profile-views-change">-</div>
                        </div>
                    </div>
                    
                    <div class="insights-charts">
                        <div class="chart-container">
                            <canvas id="followers-chart"></canvas>
                        </div>
                        <div class="chart-container">
                            <canvas id="engagement-chart"></canvas>
                        </div>
                    </div>

                    <div class="recent-posts-section">
                        <h2><?php _e('Recent Posts Performance', 'instagram-widget-by-wpzoom'); ?></h2>
                        <div id="recent-posts" class="recent-posts-grid"></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Get all connected Instagram business accounts
     */
    private function get_connected_accounts() {
        $all_accounts = get_posts(array(
            'post_type' => 'wpz-insta_user',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));

        // Filter to only show Facebook-connected business accounts
        $business_accounts = array_filter($all_accounts, function($account) {
            $connection_type = get_post_meta($account->ID, '_wpz-insta_connection-type', true);
            $account_type = get_post_meta($account->ID, '_wpz-insta_account-type', true);
            
            // For Facebook-connected business accounts
            if ($connection_type === 'facebook_graph_api' && $account_type === 'business') {
                $instagram_account_id = get_post_meta($account->ID, '_wpz-insta_page_id', true);
                $token = get_post_meta($account->ID, '_wpz-insta_token', true);
                
                // Debug information
                error_log('Account ID: ' . $account->ID);
                error_log('Connection Type: ' . $connection_type);
                error_log('Account Type: ' . $account_type);
                error_log('Instagram Account ID: ' . $instagram_account_id);
                error_log('Has Token: ' . (!empty($token) ? 'yes' : 'no'));
                
                return !empty($instagram_account_id) && !empty($token);
            }
            
            return false;
        });

        return array_values($business_accounts);
    }

    /**
     * AJAX handler for fetching insights data
     */
    public function fetch_insights_data() {
        check_ajax_referer('wpzoom_instagram_insights', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $account_id = isset($_POST['account_id']) ? sanitize_text_field($_POST['account_id']) : '';
        $since_date = isset($_POST['since_date']) ? sanitize_text_field($_POST['since_date']) : '';
        $until_date = isset($_POST['until_date']) ? sanitize_text_field($_POST['until_date']) : '';

        if (empty($account_id)) {
            wp_send_json_error('Invalid account ID');
        }

        if (empty($since_date) || empty($until_date)) {
            wp_send_json_error('Invalid date range');
        }

        $instagram_account_id = get_post_meta($account_id, '_wpz-insta_page_id', true);
        $token = get_post_meta($account_id, '_wpz-insta_token', true);

        if (empty($instagram_account_id) || empty($token)) {
            wp_send_json_error('Account not properly configured');
        }

        $data = $this->feed_pro->get_daily_insights($instagram_account_id, $token, $since_date, $until_date);
        
        if (!$data) {
            wp_send_json_error('Failed to fetch insights data');
            return;
        }

        wp_send_json_success($data);
    }
}

// Initialize the insights class
WPZOOM_Instagram_Insights::getInstance(); 