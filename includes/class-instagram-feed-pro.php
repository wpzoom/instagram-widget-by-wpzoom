<?php
/**
 * Instagram Feed Pro - Insights Data Fetching
 *
 * Handles fetching and caching of Instagram insights data
 * for business accounts connected via Facebook Graph API.
 *
 * @package suspended
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Instagram_Feed_Pro {

    /**
     * Cache TTL for insights data (15 minutes)
     */
    const CACHE_TTL = 15 * MINUTE_IN_SECONDS;

    /**
     * Cache TTL for media insights (30 minutes)
     */
    const MEDIA_CACHE_TTL = 30 * MINUTE_IN_SECONDS;

    /**
     * Chunk date range into 29-day segments (API limitation)
     *
     * @param string $since_date Start date (Y-m-d)
     * @param string $until_date End date (Y-m-d)
     * @return array Array of date chunks
     */
    private function chunk_date_range( $since_date, $until_date ) {
        $chunks = array();
        $start = strtotime( $since_date );
        $end = strtotime( $until_date );

        while ( $start < $end ) {
            $chunk_end = min( $start + ( 29 * 86400 ), $end ); // 29 days to be safe
            $chunks[] = array(
                'since' => date( 'Y-m-d', $start ),
                'until' => date( 'Y-m-d', $chunk_end )
            );
            $start = $chunk_end + 86400; // Move to next day
        }

        return $chunks;
    }

    /**
     * Merge metric data from multiple date chunks
     *
     * @param array $chunks_data Array of chunk data
     * @return array Merged and sorted data
     */
    private function merge_metric_data( $chunks_data ) {
        $merged = array();

        foreach ( $chunks_data as $chunk ) {
            foreach ( $chunk as $metric_name => $metric_data ) {
                if ( ! isset( $merged[ $metric_name ] ) ) {
                    $merged[ $metric_name ] = array();
                }
                $merged[ $metric_name ] = array_merge( $merged[ $metric_name ], $metric_data );
            }
        }

        // Sort data by date for each metric
        foreach ( $merged as $metric_name => &$metric_data ) {
            usort( $metric_data, function( $a, $b ) {
                return strtotime( $a['end_time'] ) - strtotime( $b['end_time'] );
            });
        }

        return $merged;
    }

    /**
     * Get total followers count for an account
     *
     * @param string $instagram_account_id Instagram business account ID
     * @param string $access_token Access token
     * @return int|null Followers count or null on failure
     */
    private function get_total_followers( $instagram_account_id, $access_token ) {
        $params = array(
            'fields' => 'followers_count',
            'access_token' => $access_token
        );

        $url = "https://graph.facebook.com/v21.0/{$instagram_account_id}?" . http_build_query( $params );

        $response = wp_remote_get( $url );
        $current_followers = null;

        if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( ! empty( $data['followers_count'] ) ) {
                $current_followers = (int) $data['followers_count'];
            }
        }

        return $current_followers;
    }

    /**
     * Get daily insights data for an Instagram business account
     *
     * This is the main method that aggregates all insights data including
     * followers, reach, impressions, engagement, profile metrics, and recent media.
     *
     * @param string $instagram_account_id Instagram business account ID
     * @param string $access_token Access token
     * @param string $since_date Start date (Y-m-d)
     * @param string $until_date End date (Y-m-d)
     * @return array|false Insights data or false on failure
     */
    public function get_daily_insights( $instagram_account_id, $access_token, $since_date, $until_date ) {
        // Create unique cache key based on account and date range
        $cache_key = 'wpz_insta_insights_' . md5( $instagram_account_id . $since_date . $until_date );

        // Check cache first
        $cached_data = get_transient( $cache_key );
        if ( false !== $cached_data ) {
            return $cached_data;
        }

        // Get current total followers first
        $current_followers = $this->get_total_followers( $instagram_account_id, $access_token );

        $date_chunks = $this->chunk_date_range( $since_date, $until_date );
        $all_chunks_data = array();

        foreach ( $date_chunks as $chunk ) {
            $chunk_data = $this->get_chunk_insights(
                $instagram_account_id,
                $access_token,
                $chunk['since'],
                $chunk['until']
            );

            if ( $chunk_data ) {
                $all_chunks_data[] = $chunk_data;
            }
        }

        if ( empty( $all_chunks_data ) ) {
            return false;
        }

        $merged_data = $this->merge_metric_data( $all_chunks_data );

        // Process followers data
        if ( $current_followers !== null ) {
            // Get the change in followers over the period
            $follower_change = 0;
            if ( ! empty( $merged_data['follower_count'] ) ) {
                usort( $merged_data['follower_count'], function( $a, $b ) {
                    return strtotime( $a['end_time'] ) - strtotime( $b['end_time'] );
                });

                $first_day_count = $merged_data['follower_count'][0]['value'];
                $last_day_count = end( $merged_data['follower_count'] )['value'];
                $follower_change = $last_day_count - $first_day_count;
            }

            // Calculate the starting followers by subtracting the change from current total
            $period_start_followers = $current_followers - $follower_change;

            $merged_data['followers_stats'] = array(
                'total' => $current_followers,
                'change' => $follower_change,
                'change_percentage' => $period_start_followers > 0 ?
                    ( $follower_change / $period_start_followers ) * 100 : 0,
                'period_start' => $period_start_followers,
                'period_end' => $current_followers
            );
        }

        // Add profile metrics
        $profile_metrics = $this->get_profile_metrics( $instagram_account_id, $access_token, $since_date, $until_date );
        if ( ! empty( $profile_metrics ) ) {
            $merged_data = array_merge( $merged_data, $profile_metrics );
        }

        // Add recent media insights
        $media_insights = $this->get_media_insights( $instagram_account_id, $access_token );
        if ( ! empty( $media_insights ) ) {
            $merged_data['recent_media'] = $media_insights;
        }

        // Cache the result
        if ( ! empty( $merged_data ) ) {
            set_transient( $cache_key, $merged_data, self::CACHE_TTL );
        }

        return $merged_data;
    }

    /**
     * Get insights data for a specific date chunk
     *
     * @param string $instagram_account_id Instagram business account ID
     * @param string $access_token Access token
     * @param string $since_date Start date (Y-m-d)
     * @param string $until_date End date (Y-m-d)
     * @return array Formatted insights data
     */
    private function get_chunk_insights( $instagram_account_id, $access_token, $since_date, $until_date ) {
        $formatted_data = array();

        // 1. Get follower_count
        $user_metrics_params = array(
            'metric' => 'follower_count',
            'period' => 'day',
            'access_token' => $access_token,
            'since' => $since_date,
            'until' => $until_date
        );

        $user_metrics_url = "https://graph.facebook.com/v21.0/{$instagram_account_id}/insights?" . http_build_query( $user_metrics_params );

        $user_response = wp_remote_get( $user_metrics_url );
        if ( ! is_wp_error( $user_response ) && 200 === wp_remote_retrieve_response_code( $user_response ) ) {
            $user_data = json_decode( wp_remote_retrieve_body( $user_response ), true );
            if ( ! empty( $user_data['data'] ) ) {
                foreach ( $user_data['data'] as $metric ) {
                    $formatted_data[ $metric['name'] ] = $metric['values'];
                }
            }
        }

        // 2. Get account metrics (reach, impressions)
        $account_metrics = array( 'reach', 'impressions' );

        foreach ( $account_metrics as $metric ) {
            $params = array(
                'metric' => $metric,
                'period' => 'day',
                'access_token' => $access_token,
                'since' => $since_date,
                'until' => $until_date
            );

            $url = "https://graph.facebook.com/v21.0/{$instagram_account_id}/insights?" . http_build_query( $params );

            $response = wp_remote_get( $url );
            if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
                $data = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( ! empty( $data['data'] ) ) {
                    foreach ( $data['data'] as $metric_data ) {
                        $formatted_data[ $metric_data['name'] ] = $metric_data['values'];
                    }
                }
            }
        }

        // 3. Get engagement metrics (accounts_engaged)
        $engagement_params = array(
            'metric' => 'accounts_engaged',
            'period' => 'day',
            'metric_type' => 'total_value',
            'access_token' => $access_token,
            'since' => $since_date,
            'until' => $until_date
        );

        $engagement_url = "https://graph.facebook.com/v21.0/{$instagram_account_id}/insights?" . http_build_query( $engagement_params );

        $engagement_response = wp_remote_get( $engagement_url );
        if ( ! is_wp_error( $engagement_response ) && 200 === wp_remote_retrieve_response_code( $engagement_response ) ) {
            $engagement_data = json_decode( wp_remote_retrieve_body( $engagement_response ), true );
            if ( ! empty( $engagement_data['data'] ) ) {
                foreach ( $engagement_data['data'] as $metric ) {
                    if ( isset( $metric['total_value']['value'] ) ) {
                        $formatted_data[ $metric['name'] ] = array( array(
                            'end_time' => date( 'Y-m-d\TH:i:s+0000' ),
                            'value' => $metric['total_value']['value']
                        ) );
                    }
                }
            }
        }

        return $formatted_data;
    }

    /**
     * Get insights for recent media posts
     *
     * @param string $instagram_account_id Instagram business account ID
     * @param string $access_token Access token
     * @return array Array of media items with insights
     */
    private function get_media_insights( $instagram_account_id, $access_token ) {
        // Check cache first
        $cache_key = 'wpz_insta_media_insights_' . md5( $instagram_account_id );
        $cached_data = get_transient( $cache_key );
        if ( false !== $cached_data ) {
            return $cached_data;
        }

        $params = array(
            'fields' => 'id,media_type,media_url,permalink,thumbnail_url,timestamp,caption,like_count,comments_count',
            'limit' => 10,
            'access_token' => $access_token
        );

        $url = "https://graph.facebook.com/v21.0/{$instagram_account_id}/media?" . http_build_query( $params );

        $response = wp_remote_get( $url );
        $media_items = array();

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return $media_items;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $data['data'] ) ) {
            return $media_items;
        }

        foreach ( $data['data'] as $media ) {
            $insights_data = array();

            // First request for basic metrics that work for all types
            $basic_metrics = array( 'reach', 'saved', 'total_interactions' );

            $basic_params = array(
                'metric' => implode( ',', $basic_metrics ),
                'access_token' => $access_token
            );

            $basic_url = "https://graph.facebook.com/v21.0/{$media['id']}/insights?" . http_build_query( $basic_params );

            $basic_response = wp_remote_get( $basic_url );
            if ( ! is_wp_error( $basic_response ) && 200 === wp_remote_retrieve_response_code( $basic_response ) ) {
                $basic_insights = json_decode( wp_remote_retrieve_body( $basic_response ), true );

                if ( ! empty( $basic_insights['data'] ) ) {
                    foreach ( $basic_insights['data'] as $metric ) {
                        if ( ! empty( $metric['values'] ) ) {
                            $insights_data[ $metric['name'] ] = $metric['values'][0]['value'];
                        }
                    }
                }
            }

            // Additional metrics based on media type
            if ( $media['media_type'] === 'VIDEO' || $media['media_type'] === 'REEL' ) {
                // Video-specific metrics
                $video_params = array(
                    'metric' => 'video_views',
                    'access_token' => $access_token
                );

                $video_url = "https://graph.facebook.com/v21.0/{$media['id']}/insights?" . http_build_query( $video_params );
                $video_response = wp_remote_get( $video_url );

                if ( ! is_wp_error( $video_response ) && 200 === wp_remote_retrieve_response_code( $video_response ) ) {
                    $video_insights = json_decode( wp_remote_retrieve_body( $video_response ), true );
                    if ( ! empty( $video_insights['data'] ) ) {
                        foreach ( $video_insights['data'] as $metric ) {
                            if ( ! empty( $metric['values'] ) ) {
                                $insights_data[ $metric['name'] ] = $metric['values'][0]['value'];
                            }
                        }
                    }
                }
            } else {
                // Photo/Carousel-specific metrics
                $photo_params = array(
                    'metric' => 'impressions',
                    'access_token' => $access_token
                );

                $photo_url = "https://graph.facebook.com/v21.0/{$media['id']}/insights?" . http_build_query( $photo_params );
                $photo_response = wp_remote_get( $photo_url );

                if ( ! is_wp_error( $photo_response ) && 200 === wp_remote_retrieve_response_code( $photo_response ) ) {
                    $photo_insights = json_decode( wp_remote_retrieve_body( $photo_response ), true );
                    if ( ! empty( $photo_insights['data'] ) ) {
                        foreach ( $photo_insights['data'] as $metric ) {
                            if ( ! empty( $metric['values'] ) ) {
                                $insights_data[ $metric['name'] ] = $metric['values'][0]['value'];
                            }
                        }
                    }
                }
            }

            // Calculate engagement from likes and comments
            $likes = isset( $media['like_count'] ) ? (int) $media['like_count'] : 0;
            $comments = isset( $media['comments_count'] ) ? (int) $media['comments_count'] : 0;
            $engagement = $likes + $comments;

            $media_items[] = array(
                'id' => $media['id'],
                'type' => $media['media_type'],
                'url' => $media['permalink'],
                'thumbnail' => isset( $media['thumbnail_url'] ) ? $media['thumbnail_url'] : $media['media_url'],
                'timestamp' => $media['timestamp'],
                'caption' => isset( $media['caption'] ) ? mb_strimwidth( $media['caption'], 0, 100, '...' ) : '',
                'likes' => $likes,
                'comments' => $comments,
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

        // Cache the result
        if ( ! empty( $media_items ) ) {
            set_transient( $cache_key, $media_items, self::MEDIA_CACHE_TTL );
        }

        return $media_items;
    }

    /**
     * Get profile metrics (profile views, website clicks, etc.)
     *
     * @param string $instagram_account_id Instagram business account ID
     * @param string $access_token Access token
     * @param string $since_date Start date (Y-m-d)
     * @param string $until_date End date (Y-m-d)
     * @return array Profile metrics data
     */
    private function get_profile_metrics( $instagram_account_id, $access_token, $since_date, $until_date ) {
        $params = array(
            'metric' => 'profile_views,website_clicks,email_contacts,get_directions_clicks',
            'period' => 'day',
            'access_token' => $access_token,
            'since' => $since_date,
            'until' => $until_date
        );

        $url = "https://graph.facebook.com/v21.0/{$instagram_account_id}/insights?" . http_build_query( $params );
        $response = wp_remote_get( $url );
        $profile_data = array();

        if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( ! empty( $data['data'] ) ) {
                foreach ( $data['data'] as $metric ) {
                    $profile_data[ $metric['name'] ] = $metric['values'];
                }
            }
        }

        return $profile_data;
    }

    /**
     * Clear insights cache for a specific account
     *
     * @param string $instagram_account_id Instagram business account ID
     * @return void
     */
    public function clear_cache( $instagram_account_id ) {
        global $wpdb;

        // Delete all transients matching this account
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_wpz_insta_insights_%',
                '_transient_timeout_wpz_insta_insights_%'
            )
        );

        // Also clear media insights cache
        delete_transient( 'wpz_insta_media_insights_' . md5( $instagram_account_id ) );
    }
}
