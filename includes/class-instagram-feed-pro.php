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
     * For time_series metrics (like follower_count, reach): concatenate all data points
     * For total_value metrics (like accounts_engaged, views, likes): sum the values
     *
     * @param array $chunks_data Array of chunk data
     * @return array Merged and sorted data
     */
    private function merge_metric_data( $chunks_data ) {
        $merged = array();

        // Metrics that return total_value (single value per chunk) - these need to be summed
        $total_value_metrics = array(
            'accounts_engaged',
            'views',
            'impressions',
            'profile_views',
            'profile_links_taps',
            'likes',
            'shares',
        );

        foreach ( $chunks_data as $chunk ) {
            foreach ( $chunk as $metric_name => $metric_data ) {
                if ( ! isset( $merged[ $metric_name ] ) ) {
                    $merged[ $metric_name ] = array();
                }

                // Check if this is a total_value metric that should be summed
                if ( in_array( $metric_name, $total_value_metrics, true ) ) {
                    // Sum the values across chunks
                    $chunk_value = 0;
                    foreach ( $metric_data as $item ) {
                        $chunk_value += (int) ( $item['value'] ?? 0 );
                    }

                    if ( empty( $merged[ $metric_name ] ) ) {
                        // First chunk - initialize with the value
                        $merged[ $metric_name ] = array( array(
                            'end_time' => gmdate( 'Y-m-d\TH:i:s+0000' ),
                            'value'    => $chunk_value,
                        ) );
                    } else {
                        // Subsequent chunks - add to existing value
                        $merged[ $metric_name ][0]['value'] += $chunk_value;
                    }
                } else {
                    // Time series metrics - concatenate all data points
                    $merged[ $metric_name ] = array_merge( $merged[ $metric_name ], $metric_data );
                }
            }
        }

        // Sort time series data by date for each metric
        foreach ( $merged as $metric_name => &$metric_data ) {
            // Only sort if it's not a total_value metric (those have single items)
            if ( ! in_array( $metric_name, $total_value_metrics, true ) && count( $metric_data ) > 1 ) {
                usort( $metric_data, function( $a, $b ) {
                    return strtotime( $a['end_time'] ) - strtotime( $b['end_time'] );
                });
            }
        }

        return $merged;
    }

    /**
     * Merge profile metrics from multiple date chunks
     *
     * All profile metrics are total_value type and need to be summed across chunks.
     *
     * @param array $chunks_data Array of profile metric chunks
     * @return array Merged profile metrics with summed values
     */
    private function merge_profile_metrics( $chunks_data ) {
        $merged = array();

        foreach ( $chunks_data as $chunk ) {
            foreach ( $chunk as $metric_name => $metric_data ) {
                // Get the value from the first (and usually only) item in the array
                $chunk_value = 0;
                if ( ! empty( $metric_data[0]['value'] ) ) {
                    $chunk_value = (int) $metric_data[0]['value'];
                }

                if ( ! isset( $merged[ $metric_name ] ) ) {
                    // First chunk - initialize with the structure
                    $merged[ $metric_name ] = array( array(
                        'end_time' => $metric_data[0]['end_time'] ?? gmdate( 'Y-m-d\TH:i:s+0000' ),
                        'value'    => $chunk_value,
                    ) );
                } else {
                    // Subsequent chunks - add to existing value
                    $merged[ $metric_name ][0]['value'] += $chunk_value;
                }
            }
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

        // Add profile metrics FIRST (we need net_followers for accurate chart calculation)
        // Profile metrics also need to be fetched in chunks for longer periods
        $all_profile_chunks = array();
        foreach ( $date_chunks as $chunk ) {
            $chunk_profile_metrics = $this->get_profile_metrics(
                $instagram_account_id,
                $access_token,
                $chunk['since'],
                $chunk['until']
            );
            if ( ! empty( $chunk_profile_metrics ) ) {
                $all_profile_chunks[] = $chunk_profile_metrics;
            }
        }

        // Merge profile metrics, summing the total_value metrics
        if ( ! empty( $all_profile_chunks ) ) {
            $profile_metrics = $this->merge_profile_metrics( $all_profile_chunks );
            $merged_data = array_merge( $merged_data, $profile_metrics );
        }

        // Process followers data
        if ( $current_followers !== null ) {
            // Use net_followers from follows_and_unfollows breakdown (most accurate)
            // This accounts for both new followers AND unfollows
            $follower_change = 0;
            if ( ! empty( $merged_data['net_followers'][0]['value'] ) ) {
                $follower_change = (int) $merged_data['net_followers'][0]['value'];
            } elseif ( ! empty( $merged_data['follower_count'] ) ) {
                // Fallback: sum daily follower_count values (less accurate, may not include unfollows)
                foreach ( $merged_data['follower_count'] as $day ) {
                    $follower_change += (int) $day['value'];
                }
            }

            // Sort follower_count data by date for chart display
            if ( ! empty( $merged_data['follower_count'] ) ) {
                usort( $merged_data['follower_count'], function( $a, $b ) {
                    return strtotime( $a['end_time'] ) - strtotime( $b['end_time'] );
                });
            }

            // Calculate the starting followers by subtracting net change from current total
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
     * Uses Instagram Graph API v21.0+ with updated metric formats.
     * Note: As of 2025, some metrics require metric_type parameter.
     *
     * @param string $instagram_account_id Instagram business account ID
     * @param string $access_token Access token
     * @param string $since_date Start date (Y-m-d)
     * @param string $until_date End date (Y-m-d)
     * @return array Formatted insights data
     */
    private function get_chunk_insights( $instagram_account_id, $access_token, $since_date, $until_date ) {
        $formatted_data = array();

        // Convert dates to Unix timestamps for API
        $since_timestamp = strtotime( $since_date );
        $until_timestamp = strtotime( $until_date );

        // 1. Get follower_count (time series)
        $follower_params = array(
            'metric'       => 'follower_count',
            'period'       => 'day',
            'access_token' => $access_token,
            'since'        => $since_timestamp,
            'until'        => $until_timestamp,
        );

        $follower_url = "https://graph.facebook.com/v21.0/{$instagram_account_id}/insights?" . http_build_query( $follower_params );
        $follower_response = wp_remote_get( $follower_url );

        if ( ! is_wp_error( $follower_response ) && 200 === wp_remote_retrieve_response_code( $follower_response ) ) {
            $follower_data = json_decode( wp_remote_retrieve_body( $follower_response ), true );
            if ( ! empty( $follower_data['data'] ) ) {
                foreach ( $follower_data['data'] as $metric ) {
                    if ( ! empty( $metric['values'] ) ) {
                        $formatted_data[ $metric['name'] ] = $metric['values'];
                    }
                }
            }
        }

        // 2. Get reach (time_series for chart data)
        $reach_params = array(
            'metric'       => 'reach',
            'period'       => 'day',
            'metric_type'  => 'time_series',
            'access_token' => $access_token,
            'since'        => $since_timestamp,
            'until'        => $until_timestamp,
        );

        $reach_url = "https://graph.facebook.com/v21.0/{$instagram_account_id}/insights?" . http_build_query( $reach_params );
        $reach_response = wp_remote_get( $reach_url );

        if ( ! is_wp_error( $reach_response ) && 200 === wp_remote_retrieve_response_code( $reach_response ) ) {
            $reach_data = json_decode( wp_remote_retrieve_body( $reach_response ), true );
            if ( ! empty( $reach_data['data'] ) ) {
                foreach ( $reach_data['data'] as $metric ) {
                    if ( ! empty( $metric['values'] ) ) {
                        $formatted_data[ $metric['name'] ] = $metric['values'];
                    }
                }
            }
        }

        // 3. Get views (replacement for impressions)
        // Note: 'views' metric only supports total_value, not time_series
        $views_params = array(
            'metric'       => 'views',
            'period'       => 'day',
            'metric_type'  => 'total_value',
            'access_token' => $access_token,
            'since'        => $since_timestamp,
            'until'        => $until_timestamp,
        );

        $views_url = "https://graph.facebook.com/v21.0/{$instagram_account_id}/insights?" . http_build_query( $views_params );
        $views_response = wp_remote_get( $views_url );

        if ( ! is_wp_error( $views_response ) && 200 === wp_remote_retrieve_response_code( $views_response ) ) {
            $views_data = json_decode( wp_remote_retrieve_body( $views_response ), true );
            if ( ! empty( $views_data['data'] ) ) {
                foreach ( $views_data['data'] as $metric ) {
                    if ( isset( $metric['total_value']['value'] ) ) {
                        // Store as total value since views doesn't support time_series
                        $formatted_data['views'] = array( array(
                            'end_time' => gmdate( 'Y-m-d\TH:i:s+0000' ),
                            'value'    => $metric['total_value']['value'],
                        ) );
                        $formatted_data['impressions'] = $formatted_data['views'];
                    }
                }
            }
        }

        // 4. Get accounts_engaged (total_value only)
        $engagement_params = array(
            'metric'       => 'accounts_engaged',
            'period'       => 'day',
            'metric_type'  => 'total_value',
            'access_token' => $access_token,
            'since'        => $since_timestamp,
            'until'        => $until_timestamp,
        );

        $engagement_url = "https://graph.facebook.com/v21.0/{$instagram_account_id}/insights?" . http_build_query( $engagement_params );
        $engagement_response = wp_remote_get( $engagement_url );

        if ( ! is_wp_error( $engagement_response ) && 200 === wp_remote_retrieve_response_code( $engagement_response ) ) {
            $engagement_data = json_decode( wp_remote_retrieve_body( $engagement_response ), true );
            if ( ! empty( $engagement_data['data'] ) ) {
                foreach ( $engagement_data['data'] as $metric ) {
                    if ( isset( $metric['total_value']['value'] ) ) {
                        $formatted_data[ $metric['name'] ] = array( array(
                            'end_time' => gmdate( 'Y-m-d\TH:i:s+0000' ),
                            'value'    => $metric['total_value']['value'],
                        ) );
                    }
                }
            }
        }

        // 5. Get profile_links_taps (replacement for profile_views)
        $taps_params = array(
            'metric'       => 'profile_links_taps',
            'period'       => 'day',
            'metric_type'  => 'total_value',
            'access_token' => $access_token,
            'since'        => $since_timestamp,
            'until'        => $until_timestamp,
        );

        $taps_url = "https://graph.facebook.com/v21.0/{$instagram_account_id}/insights?" . http_build_query( $taps_params );
        $taps_response = wp_remote_get( $taps_url );

        if ( ! is_wp_error( $taps_response ) && 200 === wp_remote_retrieve_response_code( $taps_response ) ) {
            $taps_data = json_decode( wp_remote_retrieve_body( $taps_response ), true );
            if ( ! empty( $taps_data['data'] ) ) {
                foreach ( $taps_data['data'] as $metric ) {
                    if ( isset( $metric['total_value']['value'] ) ) {
                        // Store as profile_links_taps and also as profile_views for UI compatibility
                        $formatted_data['profile_links_taps'] = array( array(
                            'end_time' => gmdate( 'Y-m-d\TH:i:s+0000' ),
                            'value'    => $metric['total_value']['value'],
                        ) );
                        $formatted_data['profile_views'] = $formatted_data['profile_links_taps'];
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

            // Get metrics available for all media types: reach, saved, total_interactions, views
            // Note: 'views' replaces deprecated 'impressions' as of 2025
            $basic_metrics = array( 'reach', 'saved', 'total_interactions', 'views' );

            $basic_params = array(
                'metric'       => implode( ',', $basic_metrics ),
                'access_token' => $access_token,
            );

            $basic_url      = "https://graph.facebook.com/v21.0/{$media['id']}/insights?" . http_build_query( $basic_params );
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

            // Store views as impressions for backward compatibility with UI
            if ( isset( $insights_data['views'] ) ) {
                $insights_data['impressions'] = $insights_data['views'];
            }

            // Get additional metrics for reels (ig_reels_avg_watch_time, shares)
            if ( 'REEL' === $media['media_type'] ) {
                $reel_params = array(
                    'metric'       => 'ig_reels_avg_watch_time,shares,likes,comments',
                    'access_token' => $access_token,
                );

                $reel_url      = "https://graph.facebook.com/v21.0/{$media['id']}/insights?" . http_build_query( $reel_params );
                $reel_response = wp_remote_get( $reel_url );

                if ( ! is_wp_error( $reel_response ) && 200 === wp_remote_retrieve_response_code( $reel_response ) ) {
                    $reel_insights = json_decode( wp_remote_retrieve_body( $reel_response ), true );
                    if ( ! empty( $reel_insights['data'] ) ) {
                        foreach ( $reel_insights['data'] as $metric ) {
                            if ( ! empty( $metric['values'] ) ) {
                                $insights_data[ $metric['name'] ] = $metric['values'][0]['value'];
                            }
                        }
                    }
                }

                // Use views as video_views equivalent for reels
                if ( isset( $insights_data['views'] ) ) {
                    $insights_data['video_views'] = $insights_data['views'];
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
     * Get profile metrics (follows/unfollows, shares, likes, etc.)
     *
     * Note: As of 2025, profile_views is deprecated. Using follows_and_unfollows,
     * shares, likes as alternative metrics.
     *
     * @param string $instagram_account_id Instagram business account ID
     * @param string $access_token Access token
     * @param string $since_date Start date (Y-m-d)
     * @param string $until_date End date (Y-m-d)
     * @return array Profile metrics data
     */
    private function get_profile_metrics( $instagram_account_id, $access_token, $since_date, $until_date ) {
        $profile_data      = array();
        $since_timestamp   = strtotime( $since_date );
        $until_timestamp   = strtotime( $until_date );

        // Get follows_and_unfollows with breakdown by follow_type
        $follows_params = array(
            'metric'       => 'follows_and_unfollows',
            'period'       => 'day',
            'metric_type'  => 'total_value',
            'breakdown'    => 'follow_type',
            'access_token' => $access_token,
            'since'        => $since_timestamp,
            'until'        => $until_timestamp,
        );

        $follows_url      = "https://graph.facebook.com/v21.0/{$instagram_account_id}/insights?" . http_build_query( $follows_params );
        $follows_response = wp_remote_get( $follows_url );

        if ( ! is_wp_error( $follows_response ) && 200 === wp_remote_retrieve_response_code( $follows_response ) ) {
            $follows_data = json_decode( wp_remote_retrieve_body( $follows_response ), true );
            if ( ! empty( $follows_data['data'] ) ) {
                foreach ( $follows_data['data'] as $metric ) {
                    if ( isset( $metric['total_value'] ) ) {
                        // Total follows + unfollows
                        $profile_data['follows_and_unfollows'] = array( array(
                            'end_time' => gmdate( 'Y-m-d\TH:i:s+0000' ),
                            'value'    => $metric['total_value']['value'] ?? 0,
                        ) );

                        // Parse breakdown for follows vs unfollows (FOLLOWER = new, NON_FOLLOWER = lost)
                        $new_followers  = 0;
                        $lost_followers = 0;

                        if ( ! empty( $metric['total_value']['breakdowns'] ) ) {
                            foreach ( $metric['total_value']['breakdowns'] as $breakdown ) {
                                if ( ! empty( $breakdown['results'] ) ) {
                                    foreach ( $breakdown['results'] as $result ) {
                                        $dimension = $result['dimension_values'][0] ?? '';
                                        $value     = $result['value'] ?? 0;

                                        if ( 'FOLLOWER' === $dimension ) {
                                            $new_followers = $value;
                                        } elseif ( 'NON_FOLLOWER' === $dimension ) {
                                            $lost_followers = $value;
                                        }
                                    }
                                }
                            }
                        }

                        // Store separate values for new and lost followers
                        $profile_data['new_followers'] = array( array(
                            'end_time' => gmdate( 'Y-m-d\TH:i:s+0000' ),
                            'value'    => $new_followers,
                        ) );
                        $profile_data['lost_followers'] = array( array(
                            'end_time' => gmdate( 'Y-m-d\TH:i:s+0000' ),
                            'value'    => $lost_followers,
                        ) );
                        // Net change (new - lost)
                        $profile_data['net_followers'] = array( array(
                            'end_time' => gmdate( 'Y-m-d\TH:i:s+0000' ),
                            'value'    => $new_followers - $lost_followers,
                        ) );
                    }
                }
            }
        }

        // Get shares and likes totals
        $engagement_params = array(
            'metric'       => 'shares,likes',
            'period'       => 'day',
            'metric_type'  => 'total_value',
            'access_token' => $access_token,
            'since'        => $since_timestamp,
            'until'        => $until_timestamp,
        );

        $engagement_url      = "https://graph.facebook.com/v21.0/{$instagram_account_id}/insights?" . http_build_query( $engagement_params );
        $engagement_response = wp_remote_get( $engagement_url );

        if ( ! is_wp_error( $engagement_response ) && 200 === wp_remote_retrieve_response_code( $engagement_response ) ) {
            $engagement_data = json_decode( wp_remote_retrieve_body( $engagement_response ), true );
            if ( ! empty( $engagement_data['data'] ) ) {
                foreach ( $engagement_data['data'] as $metric ) {
                    if ( isset( $metric['total_value']['value'] ) ) {
                        $profile_data[ $metric['name'] ] = array( array(
                            'end_time' => gmdate( 'Y-m-d\TH:i:s+0000' ),
                            'value'    => $metric['total_value']['value'],
                        ) );
                    }
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
