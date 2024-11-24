<?php

class Instagram_Feed_Pro {
    private function chunk_date_range($since_date, $until_date) {
        $chunks = [];
        $start = strtotime($since_date);
        $end = strtotime($until_date);
        
        while ($start < $end) {
            $chunk_end = min($start + (29 * 86400), $end); // 29 days to be safe
            $chunks[] = [
                'since' => date('Y-m-d', $start),
                'until' => date('Y-m-d', $chunk_end)
            ];
            $start = $chunk_end + 86400; // Move to next day
        }
        
        return $chunks;
    }

    private function merge_metric_data($chunks_data) {
        $merged = [];
        
        foreach ($chunks_data as $chunk) {
            foreach ($chunk as $metric_name => $metric_data) {
                if (!isset($merged[$metric_name])) {
                    $merged[$metric_name] = [];
                }
                $merged[$metric_name] = array_merge($merged[$metric_name], $metric_data);
            }
        }
        
        // Sort data by date for each metric
        foreach ($merged as $metric_name => &$metric_data) {
            usort($metric_data, function($a, $b) {
                return strtotime($a['end_time']) - strtotime($b['end_time']);
            });
        }
        
        return $merged;
    }

    private function get_total_followers($instagram_account_id, $access_token) {
        // Get both current followers count and daily follower counts
        $params = array(
            'fields' => 'followers_count',
            'access_token' => $access_token
        );

        $url = "https://graph.facebook.com/v18.0/{$instagram_account_id}?" . http_build_query($params);
        error_log("Fetching total followers count");
        
        $response = wp_remote_get($url);
        $current_followers = null;
        
        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($data['followers_count'])) {
                $current_followers = $data['followers_count'];
            }
        }
        
        return $current_followers;
    }

    public function get_daily_insights($instagram_account_id, $access_token, $since_date, $until_date) {
        // Get current total followers first
        $current_followers = $this->get_total_followers($instagram_account_id, $access_token);
        
        $date_chunks = $this->chunk_date_range($since_date, $until_date);
        $all_chunks_data = [];

        foreach ($date_chunks as $chunk) {
            $chunk_data = $this->get_chunk_insights(
                $instagram_account_id, 
                $access_token, 
                $chunk['since'], 
                $chunk['until']
            );
            
            if ($chunk_data) {
                $all_chunks_data[] = $chunk_data;
            }
        }

        if (empty($all_chunks_data)) {
            error_log('No insights data returned from any chunk');
            return false;
        }

        $merged_data = $this->merge_metric_data($all_chunks_data);
        
        // Process followers data
        if ($current_followers !== null) {
            // Get the change in followers over the period
            $follower_change = 0;
            if (!empty($merged_data['follower_count'])) {
                usort($merged_data['follower_count'], function($a, $b) {
                    return strtotime($a['end_time']) - strtotime($b['end_time']);
                });
                
                $first_day_count = $merged_data['follower_count'][0]['value'];
                $last_day_count = end($merged_data['follower_count'])['value'];
                $follower_change = $last_day_count - $first_day_count;
            }
            
            // Calculate the starting followers by subtracting the change from current total
            $period_start_followers = $current_followers - $follower_change;
            
            $merged_data['followers_stats'] = array(
                'total' => $current_followers,
                'change' => $follower_change,
                'change_percentage' => $period_start_followers > 0 ? 
                    ($follower_change / $period_start_followers) * 100 : 0,
                'period_start' => $period_start_followers,
                'period_end' => $current_followers
            );
        }

        // Add profile metrics
        $profile_metrics = $this->get_profile_metrics($instagram_account_id, $access_token, $since_date, $until_date);
        if (!empty($profile_metrics)) {
            $merged_data = array_merge($merged_data, $profile_metrics);
        }

        // Add recent media insights
        $media_insights = $this->get_media_insights($instagram_account_id, $access_token);
        if (!empty($media_insights)) {
            $merged_data['recent_media'] = $media_insights;
        }

        return $merged_data;
    }

    private function get_chunk_insights($instagram_account_id, $access_token, $since_date, $until_date) {
        $formatted_data = array();
        
        // 1. Get follower_count
        $user_metrics_params = array(
            'metric' => 'follower_count',
            'period' => 'day',
            'access_token' => $access_token,
            'since' => $since_date,
            'until' => $until_date
        );

        $user_metrics_url = "https://graph.facebook.com/v18.0/{$instagram_account_id}/insights?" . http_build_query($user_metrics_params);
        error_log("Fetching follower data for {$since_date} to {$until_date}");
        
        $user_response = wp_remote_get($user_metrics_url);
        if (!is_wp_error($user_response)) {
            $user_data = json_decode(wp_remote_retrieve_body($user_response), true);
            if (!empty($user_data['data'])) {
                foreach ($user_data['data'] as $metric) {
                    $formatted_data[$metric['name']] = $metric['values'];
                }
            }
        }

        // 2. Get account metrics
        $account_metrics = array('reach', 'impressions');
        
        foreach ($account_metrics as $metric) {
            $params = array(
                'metric' => $metric,
                'period' => 'day',
                'access_token' => $access_token,
                'since' => $since_date,
                'until' => $until_date
            );

            $url = "https://graph.facebook.com/v18.0/{$instagram_account_id}/insights?" . http_build_query($params);
            error_log("Fetching {$metric} for {$since_date} to {$until_date}");
            
            $response = wp_remote_get($url);
            if (!is_wp_error($response)) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($data['data'])) {
                    foreach ($data['data'] as $metric_data) {
                        $formatted_data[$metric_data['name']] = $metric_data['values'];
                    }
                }
            }
        }

        // 3. Get engagement metrics
        $engagement_params = array(
            'metric' => 'accounts_engaged',
            'period' => 'day',
            'metric_type' => 'total_value',
            'access_token' => $access_token,
            'since' => $since_date,
            'until' => $until_date
        );

        $engagement_url = "https://graph.facebook.com/v18.0/{$instagram_account_id}/insights?" . http_build_query($engagement_params);
        error_log("Fetching engagement for {$since_date} to {$until_date}");
        
        $engagement_response = wp_remote_get($engagement_url);
        if (!is_wp_error($engagement_response)) {
            $engagement_data = json_decode(wp_remote_retrieve_body($engagement_response), true);
            if (!empty($engagement_data['data'])) {
                foreach ($engagement_data['data'] as $metric) {
                    $formatted_data[$metric['name']] = array(array(
                        'end_time' => date('Y-m-d\TH:i:s+0000'),
                        'value' => $metric['total_value']['value']
                    ));
                }
            }
        }

        return $formatted_data;
    }

    private function get_media_insights($instagram_account_id, $access_token) {
        $params = array(
            'fields' => 'id,media_type,media_url,permalink,thumbnail_url,timestamp,caption,like_count,comments_count',
            'limit' => 10,
            'access_token' => $access_token
        );

        $url = "https://graph.facebook.com/v18.0/{$instagram_account_id}/media?" . http_build_query($params);
        error_log("Fetching recent media: " . $url);
        
        $response = wp_remote_get($url);
        $media_items = array();

        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($data['data'])) {
                foreach ($data['data'] as $media) {
                    $insights_data = array();
                    
                    // First request for basic metrics that work for all types
                    $basic_metrics = array(
                        'reach',
                        'saved',
                        'total_interactions'
                    );

                    $basic_params = array(
                        'metric' => implode(',', $basic_metrics),
                        'access_token' => $access_token
                    );

                    $basic_url = "https://graph.facebook.com/v18.0/{$media['id']}/insights?" . http_build_query($basic_params);
                    error_log("Fetching basic insights for {$media['media_type']} {$media['id']} with metrics: " . implode(',', $basic_metrics));
                    
                    $basic_response = wp_remote_get($basic_url);
                    if (!is_wp_error($basic_response)) {
                        $basic_insights = json_decode(wp_remote_retrieve_body($basic_response), true);
                        error_log("Basic insights response: " . wp_remote_retrieve_body($basic_response));
                        
                        if (!empty($basic_insights['data'])) {
                            foreach ($basic_insights['data'] as $metric) {
                                if (!empty($metric['values'])) {
                                    $insights_data[$metric['name']] = $metric['values'][0]['value'];
                                }
                            }
                        }
                    }

                    // Additional metrics based on media type
                    if ($media['media_type'] === 'VIDEO' || $media['media_type'] === 'REEL') {
                        // Video-specific metrics
                        $video_metrics = array('video_views');
                        $video_params = array(
                            'metric' => implode(',', $video_metrics),
                            'access_token' => $access_token
                        );

                        $video_url = "https://graph.facebook.com/v18.0/{$media['id']}/insights?" . http_build_query($video_params);
                        $video_response = wp_remote_get($video_url);
                        
                        if (!is_wp_error($video_response)) {
                            $video_insights = json_decode(wp_remote_retrieve_body($video_response), true);
                            if (!empty($video_insights['data'])) {
                                foreach ($video_insights['data'] as $metric) {
                                    if (!empty($metric['values'])) {
                                        $insights_data[$metric['name']] = $metric['values'][0]['value'];
                                    }
                                }
                            }
                        }
                    } else {
                        // Photo/Carousel-specific metrics
                        $photo_metrics = array('impressions');
                        $photo_params = array(
                            'metric' => implode(',', $photo_metrics),
                            'access_token' => $access_token
                        );

                        $photo_url = "https://graph.facebook.com/v18.0/{$media['id']}/insights?" . http_build_query($photo_params);
                        $photo_response = wp_remote_get($photo_url);
                        
                        if (!is_wp_error($photo_response)) {
                            $photo_insights = json_decode(wp_remote_retrieve_body($photo_response), true);
                            if (!empty($photo_insights['data'])) {
                                foreach ($photo_insights['data'] as $metric) {
                                    if (!empty($metric['values'])) {
                                        $insights_data[$metric['name']] = $metric['values'][0]['value'];
                                    }
                                }
                            }
                        }
                    }

                    // Calculate engagement from likes and comments
                    $engagement = (isset($media['like_count']) ? $media['like_count'] : 0) + 
                                (isset($media['comments_count']) ? $media['comments_count'] : 0);

                    $media_items[] = array(
                        'id' => $media['id'],
                        'type' => $media['media_type'],
                        'url' => $media['permalink'],
                        'thumbnail' => isset($media['thumbnail_url']) ? $media['thumbnail_url'] : $media['media_url'],
                        'timestamp' => $media['timestamp'],
                        'caption' => isset($media['caption']) ? mb_strimwidth($media['caption'], 0, 100, '...') : '',
                        'likes' => isset($media['like_count']) ? $media['like_count'] : 0,
                        'comments' => isset($media['comments_count']) ? $media['comments_count'] : 0,
                        'insights' => array_merge(
                            array(
                                'impressions' => 0,
                                'reach' => 0,
                                'saved' => 0,
                                'video_views' => 0,
                                'total_interactions' => 0,
                                'engagement' => $engagement
                            ),
                            $insights_data
                        )
                    );
                }
            }
        }

        return $media_items;
    }

    private function get_profile_metrics($instagram_account_id, $access_token, $since_date, $until_date) {
        $params = array(
            'metric' => 'profile_views,website_clicks,email_contacts,get_directions_clicks',
            'period' => 'day',
            'access_token' => $access_token,
            'since' => $since_date,
            'until' => $until_date
        );

        $url = "https://graph.facebook.com/v18.0/{$instagram_account_id}/insights?" . http_build_query($params);
        $response = wp_remote_get($url);
        $profile_data = array();

        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($data['data'])) {
                foreach ($data['data'] as $metric) {
                    $profile_data[$metric['name']] = $metric['values'];
                }
            }
        }

        return $profile_data;
    }
} 