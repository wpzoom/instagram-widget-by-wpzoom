<?php

class WPZOOM_Instagram_Widget_API {

    public function get_account_insights($account_id, $metrics, $period, $days) {
        $instagram_account_id = get_post_meta($account_id, '_wpz-insta_page_id', true);
        $token = get_post_meta($account_id, '_wpz-insta_token', true);

        if (empty($instagram_account_id) || empty($token)) {
            return new WP_Error('invalid_account', 'Invalid account configuration');
        }

        $until_date = date('Y-m-d');
        $since_date = date('Y-m-d', strtotime("-{$days} days"));

        // Create a new instance or use a stored one
        if (!isset($this->feed_pro)) {
            $this->feed_pro = new Instagram_Feed_Pro();
        }
        return $this->feed_pro->get_daily_insights($instagram_account_id, $token, $since_date, $until_date);
    }
} 