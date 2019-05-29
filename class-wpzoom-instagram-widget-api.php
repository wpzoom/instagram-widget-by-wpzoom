<?php

class Wpzoom_Instagram_Widget_API {
    /**
     * @var Wpzoom_Instagram_Widget_API The reference to *Singleton* instance of this class
     */
    private static $instance;

    /**
     * Instagram Access Token
     *
     * @var string
     */
    protected $access_token;

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Wpzoom_Instagram_Widget_API The *Singleton* instance.
     */
    public static function getInstance()
    {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    protected function __construct() {
        $options = get_option( 'wpzoom-instagram-widget-settings' );
        $this->access_token = $options['access-token'];
        $this->username = !empty($options['username']) ? $options['username'] : '';
        $this->request_type = !empty($options['request_type']) ? $options['request_type'] : '';
        $this->transient_lifetime_type = !empty($options['transient-lifetime-type']) ? $options['transient-lifetime-type'] : 'days';
        $this->transient_lifetime_value = !empty($options['transient-lifetime-value']) ? $options['transient-lifetime-value'] : 1;

    }

    function get_transient_lifetime() {

        $values = array( 'minutes' => MINUTE_IN_SECONDS, 'hours' => HOUR_IN_SECONDS, 'days' => DAY_IN_SECONDS );
        $keys   = array_keys( $values );
        $type   = in_array( $this->transient_lifetime_type, $keys ) ? $values[ $this->transient_lifetime_type ] : $values['minutes'];

        return $type * $this->transient_lifetime_value;
    }

    function get_user_info_without_token( $user ) {

        $response = $this->get_response_without_token( $user );

        if ( empty( $response ) ) {
            return new WP_Error( 'empty-json', __( 'Empty json decoded data.', 'wpzoom-instagram-widget' ) );
        }

        if ( isset( $response->entry_data->ProfilePage[0]->graphql->user ) ) {
            $user_info = $response->entry_data->ProfilePage[0]->graphql->user;
        } else {
            return new WP_Error( 'empty-json', __( 'Empty json decoded data.', 'wpzoom-instagram-widget' ) );
        }

        $converted = new stdClass;

        $converted->data = (object) array(
            'bio'             => ! empty( $user_info->biography ) ? $user_info->biography : '',
            'counts'          => (object) array(
                'followed_by' => ! empty( $user_info->edge_followed_by->count ) ? $user_info->edge_followed_by->count : 0,
                'follows'     => ! empty( $user_info->edge_follow->count ) ? $user_info->edge_follow->count : 0,
                'media'       => ! empty( $user_info->edge_owner_to_timeline_media->count ) ? $user_info->edge_owner_to_timeline_media->count : 0,
            ),
            'full_name'       => ! empty( $user_info->full_name ) ? $user_info->full_name : '',
            'id'              => ! empty( $user_info->id ) ? $user_info->id : '',
            'is_business'     => ! empty( $user_info->is_business_account ) ? $user_info->is_business_account : '',
            'profile_picture' => ! empty( $user_info->profile_pic_url ) ? $user_info->profile_pic_url : '',
            'username'        => ! empty( $user_info->username ) ? $user_info->username : '',
            'website'         => ! empty( $user_info->external_url ) ? $user_info->external_url : ''
        );

        return $converted;

    }

    function get_response_without_token( $user ) {

        $user = trim( $user );
        $url  = $url = 'https://instagram.com/' . str_replace( '@', '', $user );

        $request = wp_remote_get( $url );

        if ( is_wp_error( $request ) || empty( $request ) ) {
            return new WP_Error( 'invalid_response', __( 'Invalid response from Instagram', 'wpzoom-instagram-widget' ) );
        }

        $body = wp_remote_retrieve_body( $request );

        $doc = new DOMDocument();

        @$doc->loadHTML( $body );

        $script_tags = $doc->getElementsByTagName( 'script' );

        $json = '';

        foreach ( $script_tags as $script_tag ) {
            if ( strpos( $script_tag->nodeValue, 'window._sharedData = ' ) !== false ) {
                $json = $script_tag->nodeValue;
                break;
            }
        }

        $json   = str_replace( array( 'window._sharedData = ', '};' ), array( '', '}' ), $json );
        $result = json_decode( $json );

        if ( empty( $result ) ) {
            return new WP_Error( 'empty-json', __( 'Empty json decoded data.', 'wpzoom-instagram-widget' ) );
        }

        return $result;
    }

    function get_items_without_token( $user ) {


        $result = $this->get_response_without_token( $user );

        if ( empty( $result ) ) {
            return new WP_Error( 'empty-json', __( 'Empty json decoded data.', 'wpzoom-instagram-widget' ) );
        }

        if ( isset( $result->entry_data->ProfilePage[0]->graphql->user->edge_owner_to_timeline_media->edges ) ) {
            $edges = $result->entry_data->ProfilePage[0]->graphql->user->edge_owner_to_timeline_media->edges;
        } else {
            return new WP_Error( 'empty-json', __( 'Empty json decoded data.', 'wpzoom-instagram-widget' ) );
        }

        $converted       = new stdClass;
        $converted->data = array();
        foreach ( $edges as $edge ) {

            $node = $edge->node;

            $converted->data[] = (object) array(
                'user'         => (object) array(
                    'id'              => $node->owner->id,
                    'fullname'        => '',
                    'profile_picture' => '',
                    'username'        => $node->owner->username
                ),
                'images'       => (object) array(
                    'thumbnail'           => (object) array(
                        'url'    => $node->thumbnail_resources[0]->src,
                        'width'  => $node->thumbnail_resources[0]->config_width,
                        'height' => $node->thumbnail_resources[0]->config_height
                    ),
                    'low_resolution'      => (object) array(
                        'url'    => $node->thumbnail_resources[2]->src,
                        'width'  => $node->thumbnail_resources[2]->config_width,
                        'height' => $node->thumbnail_resources[2]->config_height
                    ),
                    'standard_resolution' => (object) array(
                        'url'    => $node->thumbnail_resources[4]->src,
                        'width'  => $node->thumbnail_resources[4]->config_width,
                        'height' => $node->thumbnail_resources[4]->config_height
                    ),
                ),
                'type' => empty($node->is_video) ? 'image': 'video',
                'likes'        => isset( $node->edge_liked_by ) ? $node->edge_liked_by : 0,
                'comments'     => isset( $node->edge_media_to_comment ) ? $node->edge_media_to_comment : 0,
                'created_time' => $node->taken_at_timestamp,
                'link'         => sprintf( 'https://www.instagram.com/p/%s/', $node->shortcode ),
                'caption'      => (object) array(
                    'text' => isset( $node->edge_media_to_caption->edges[0]->node->text ) ? $node->edge_media_to_caption->edges[0]->node->text : ''
                )
            );
        }

        return $converted;
    }

    /**
     * @param $screen_name string Instagram username
     * @param $image_limit int    Number of images to retrieve
     * @param $image_width int    Desired image width to retrieve
     *
     * @return array|bool Array of tweets or false if method fails
     */
    public function get_items( $instance ) {

        $sliced               = wp_array_slice_assoc( $instance, array(
            'image-limit',
            'image-width',
            'image-resolution',
            'username',
            'disable-video-thumbs'
        ) );

        $image_limit          = $sliced['image-limit'];
        $image_width          = $sliced['image-width'];
        $image_resolution     = ! empty( $sliced['image-resolution'] ) ? $sliced['image-resolution'] : 'default_algorithm';
        $injected_username    = ! empty( $sliced['username'] ) ? $sliced['username'] : '';
        $disable_video_thumbs = ! empty( $sliced['disable-video-thumbs'] );

        $transient = 'zoom_instagram_is_configured';

        $injected_username = trim( $injected_username );

        if ( ! empty( $injected_username ) ) {
            $injected_username = str_replace( '@', '', $injected_username );
            $transient         = $transient . '_' . $injected_username;
        }

        if ( false !== ( $data = get_transient( $transient ) ) && is_object( $data ) && ! empty( $data->data ) ) {

            return $this->processing_response_data( $data, $image_width, $image_resolution, $image_limit, $disable_video_thumbs );
        }

        $is_external_username = ! empty( $this->username ) || ! empty( $injected_username );
        $external_username    = ! empty( $injected_username ) ? $injected_username : $this->username;


        if ( ! empty( $this->access_token ) ) {
            $api_image_limit = 30;
            $response        = wp_remote_get( sprintf( 'https://api.instagram.com/v1/users/self/media/recent/?access_token=%s&count=%s', $this->access_token, $api_image_limit ) );

            if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) ) {
                set_transient( $transient, false, MINUTE_IN_SECONDS );

                return false;
            }

            $data = json_decode( wp_remote_retrieve_body( $response ) );

            $token_username = ! empty( $data->data[0]->user->username ) ? $data->data[0]->user->username : '';


            if ( ! empty( $token_username ) && ! empty( $is_external_username ) ) {

                if ( $external_username !== $token_username ) {

                    $data = $this->get_items_without_token( $external_username );

                    if ( is_wp_error( $data ) ) {
                        set_transient( $transient, false, MINUTE_IN_SECONDS );

                        return false;
                    }
                }
            }
        }

        if ( empty( $this->access_token ) && ! empty( $is_external_username ) ) {


            $data = $this->get_items_without_token( $external_username );

            if ( is_wp_error( $data ) ) {
                set_transient( $transient, false, MINUTE_IN_SECONDS );

                return false;
            }

        }

        if ( ! empty( $data->data ) ) {
            set_transient( $transient, $data, $this->get_transient_lifetime() );
        } else {
            set_transient( $transient, false, MINUTE_IN_SECONDS );

            return false;
        }

        return $this->processing_response_data( $data, $image_width, $image_resolution, $image_limit, $disable_video_thumbs );
    }

    public function processing_response_data( $data, $image_width, $image_resolution = 'default_algorithm', $image_limit, $disable_video_thumbs = false ) {

        $result   = array();
        $username = '';

        foreach ( $data->data as $key => $item ) {

            if ( empty( $username ) ) {
                $username = $item->user->username;
            }

            if ( $key === $image_limit ) {
                break;
            }

            if ( ! empty( $disable_video_thumbs ) && isset( $item->type ) && 'video' == $item->type ) {
                $image_limit++;
                continue;

            }

            $result[] = array(
                'link'           => $item->link,
                'image-url'      => $item->images->{$this->get_best_size( $image_width, $image_resolution )}->url,
                'image-caption'  => ! empty( $item->caption->text ) ? esc_attr( $item->caption->text ) : '',
                'likes_count'    => ! empty( $item->likes->count ) ? esc_attr( $item->likes->count ) : 0,
                'comments_count' => ! empty( $item->comments->count ) ? esc_attr( $item->comments->count ) : 0
            );
        }

        $result = array( 'items' => $result, 'username' => $username );

        return $result;
    }

    public function get_user_info( $injected_username = '' ) {


        $transient = 'zoom_instagram_user_info';

        $injected_username = rtrim( $injected_username );

        if ( ! empty( $injected_username ) ) {
            $injected_username = str_replace( '@', '', $injected_username );
            $transient         = $transient . '_' . $injected_username;
        }

        if ( false !== ( $data = get_transient( $transient ) ) && is_object( $data ) && ! empty( $data->data ) ) {

            return $data;
        }

        $is_external_username = ! empty( $this->username ) || ! empty( $injected_username );
        $external_username    = ! empty( $injected_username ) ? $injected_username : $this->username;

        if ( ! empty( $this->access_token ) ) {

            $response = wp_remote_get( sprintf( 'https://api.instagram.com/v1/users/self/?access_token=%s', $this->access_token ) );

            if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) ) {
                set_transient( $transient, false, MINUTE_IN_SECONDS );

                return false;
            }

            $data = json_decode( wp_remote_retrieve_body( $response ) );

            $token_username = ! empty( $data->data->username ) ? $data->data->username : '';

            if ( ! empty( $token_username ) && ! empty( $is_external_username ) ) {

                if ( $external_username !== $token_username ) {

                    $data = $this->get_user_info_without_token( $external_username );

                    if ( is_wp_error( $data ) ) {
                        set_transient( $transient, false, MINUTE_IN_SECONDS );

                        return false;
                    }
                }
            }

        }

        if ( empty( $this->access_token ) && ! empty( $is_external_username ) ) {

            $data = $this->get_user_info_without_token( $external_username );

            if ( is_wp_error( $data ) ) {
                set_transient( $transient, false, MINUTE_IN_SECONDS );

                return false;
            }

        }

        if ( ! empty( $data->data ) ) {
            set_transient( $transient, $data, $this->get_transient_lifetime() );
        } else {
            set_transient( $transient, false, MINUTE_IN_SECONDS );

            return false;
        }

        return $data;

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

        if ( ! isset( $result->data ) ) {
            return false;
        }

        $user_id = false;

        foreach ( $result->data as $user ) {
            if ( $user->username === $screen_name ) {
                $user_id = $user->id;

                break;
            }
        }

        update_option( $user_id_option, $user_id );

        return $user_id;
    }

    /**
     * @param $desired_width int Desired image width in pixels
     *
     * @return string Image size for Instagram API
     */
    protected function get_best_size( $desired_width, $image_resolution = 'default_algorithm' ) {
        $size = 'thumbnail';
        $sizes = array(
            'thumbnail'           => 150,
            'low_resolution'      => 306,
            'standard_resolution' => 640
        );

        $diff = PHP_INT_MAX;

        if ( array_key_exists( $image_resolution, $sizes ) ) {
            return $image_resolution;
        }

        foreach ( $sizes as $key => $value ) {
            if ( abs( $desired_width - $value ) < $diff ) {
                $size = $key;
                $diff = abs( $desired_width - $value );
            }
        }

        return $size;
    }

    /**
     * Check if given access token is valid for Instagram Api.
     */
    public static function is_access_token_valid( $access_token ) {
        $response = wp_remote_get( sprintf( 'https://api.instagram.com/v1/users/self/?access_token=%s', $access_token ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }

        return true;
    }

    public function is_configured() {
        $transient = 'zoom_instagram_is_configured';

        if ( false !== ( $result = get_transient( $transient ) ) ) {
            if ( 'yes' === $result ) {
                return true;
            }

            if ( 'no' === $result ) {
                return false;
            }
        }

        if(empty($this->username)){
            $condition = $this->is_access_token_valid( $this->access_token );

        } else{
            $condition = true;
        }


        if ( true === $condition ) {
            set_transient( $transient, 'yes', DAY_IN_SECONDS );

            return true;
        }

        set_transient( $transient, 'no', DAY_IN_SECONDS );

        return false;
    }

    public static function reset_cache() {
        delete_transient( 'zoom_instagram_is_configured' );
        delete_transient( 'zoom_instagram_user_info' );
    }

    public function get_access_token() {
        return $this->access_token;
    }

    public function set_access_token( $access_token ) {
        $this->access_token = $access_token;
    }
}
