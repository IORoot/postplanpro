<?php

namespace postplanpro\hooks;

use postplanpro\acf\acf_on_update_releases;

class action_release_rest_routes
{

    private $token;

    public function __construct()
    {
        add_action( 'rest_api_init', [$this,'register_custom_api_endpoints'] );
    }


    public function register_custom_api_endpoints() {
        $this->register_post();
        $this->register_put();
    }


    /**
     * Register the POST route
     */
    public function register_post(){
        register_rest_route( 'custom/v1', '/release', array(
            'methods' => 'POST',
            'callback' => [ $this, 'create_release' ],
            'permission_callback' => [ $this, 'permissions_check' ],
        ) );
    }
    

    /**
     * Register the PUT route
     */
    public function register_put()
    {
        register_rest_route( 'custom/v1', '/release/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => [ $this, 'update_release' ],
            'permission_callback' => [ $this, 'permissions_check' ],
        ) );
    }


    /**
     * Check for API Token
     */
    public function permissions_check( $request ) {
        $token = $request->get_header('X-API-TOKEN');

        if ( empty( $token ) || ! $this->is_token_valid( $token ) ) {
            return new \WP_Error( 'rest_forbidden', 'Invalid token', array( 'status' => 401 ) );
        }

        return true;
    }


    /**
     * Check if the token is valid
     */
    private function is_token_valid( $token ) {

        if( have_rows('ppp_api_tokens', 'option') ) {
            while ( have_rows('ppp_api_tokens', 'option') ) {
                the_row();
                $stored_token = get_sub_field('ppp_api_token', 'option');

                if ( $stored_token === $token ) {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * Create a new release
     */
    public function create_release( \WP_REST_Request $request ) {

        /** Check that the token is correct */
        // $token = sanitize_text_field( $request->get_param( 'token' ) );
        // if ($token !== $this->token){ 
        //     return new \WP_Error( 'post_creation_failed', 'Access token incorrect', array( 'status' => 500 ) );
        // }

        /** Get fields */
        $title = sanitize_text_field( $request->get_param( 'title' ) );
        $content = sanitize_textarea_field( $request->get_param( 'content' ) );
        $acf_fields = $request->get_param( 'acf' );
    
        /** create an array of fields */
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'release',
        );
    
        /** create a post */
        $post_id = wp_insert_post( $post_data );
    
        /** error on fail */
        if ( is_wp_error( $post_id ) ) {
            return new \WP_Error( 'post_creation_failed', 'Failed to create post', array( 'status' => 500 ) );
        }
    
        // Save ACF fields
        if ( ! empty( $acf_fields ) && function_exists( 'update_field' ) ) {
            foreach ( $acf_fields as $field_key => $field_value ) {
                update_field( $field_key, $field_value, $post_id );
            }
        }

        /**
         * Run the ACF update so that all the social platform details and
         * the Published dates are all correct
         */
        $update_object = new acf_on_update_releases();
        $update_object->REST_update($post_id);
    
        return new \WP_REST_Response( array( 'post_id' => $post_id ), 201 );
    }

    
    /**
     * Update an existing release
     */
    public function update_release( \WP_REST_Request $request ) {

        /** Check that the token is correct */
        // $token = sanitize_text_field( $request->get_param( 'token' ) );
        // if ($token !== $this->token){ 
        //     return new \WP_Error( 'post_creation_failed', 'Access token incorrect', array( 'status' => 500 ) );
        // }

        /** Get fields */
        $post_id = (int) $request->get_param( 'id' );
        $title = sanitize_text_field( $request->get_param( 'title' ) );
        $content = sanitize_textarea_field( $request->get_param( 'content' ) );
        $acf_fields = $request->get_param( 'acf' );
    
        if ( get_post_type( $post_id ) !== 'release' ) {
            return new \WP_Error( 'invalid_post', 'Invalid post ID', array( 'status' => 404 ) );
        }
    
        /** create an array of fields */
        $post_data = array(
            'ID' => $post_id,
            'post_title' => $title,
            'post_content' => $content,
        );
    
        /** update a post */
        $updated_post_id = wp_update_post( $post_data );
    
        /** error on fail */
        if ( is_wp_error( $updated_post_id ) ) {
            return new \WP_Error( 'post_update_failed', 'Failed to update post', array( 'status' => 500 ) );
        }
    
        // Save ACF fields
        if ( ! empty( $acf_fields ) && function_exists( 'update_field' ) ) {
            foreach ( $acf_fields as $field_key => $field_value ) {
                update_field( $field_key, $field_value, $post_id );
            }
        }

        /**
         * Run the ACF update so that all the social platform details and
         * the Published dates are all correct
         */
        $update_object = new acf_on_update_releases();
        $update_object->REST_update($post_id);
    
        return new \WP_REST_Response( array( 'post_id' => $post_id ), 200 );
    }

}