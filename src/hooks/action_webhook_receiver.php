<?php

namespace postplanpro\hooks;

class action_webhook_receiver
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_webhook_endpoint']);
    }

    /**
     * Register the webhook endpoint
     */
    public function register_webhook_endpoint()
    {
        register_rest_route('postplanpro/v1', '/webhook', array(
            'methods' => 'POST',
            'callback' => [$this, 'handle_webhook'],
            'permission_callback' => [$this, 'verify_webhook'],
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return get_post($param) && get_post_type($param) === 'release';
                    }
                ),
                'message' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                )
            )
        ));
    }

    /**
     * Verify the webhook request
     */
    public function verify_webhook($request)
    {
        // Check for API token in headers
        $token = $request->get_header('X-API-TOKEN');
        
        if (empty($token)) {
            return false;
        }

        // Verify token against stored tokens
        return $this->is_token_valid($token);
    }

    /**
     * Check if the API token is valid
     */
    private function is_token_valid($token)
    {
        if (have_rows('ppp_api_tokens', 'option')) {
            while (have_rows('ppp_api_tokens', 'option')) {
                the_row();
                $stored_token = get_sub_field('ppp_api_token', 'option');
                
                if ($stored_token === $token) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Handle the webhook request
     */
    public function handle_webhook($request)
    {
        $post_id = $request->get_param('post_id');
        $message = $request->get_param('message');
        
        // Get current history
        $current_history = get_field('ppp_receive_hist', $post_id);
        
        // Create new entry
        $new_entry = array(
            'timestamp' => current_time('mysql'),
            'message' => $message,
            'source' => 'webhook'
        );
        
        // Initialize history array if it doesn't exist
        if (empty($current_history)) {
            $current_history = array();
        } else {
            // If it's a JSON string, decode it first
            if (is_string($current_history)) {
                $decoded = json_decode($current_history, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $current_history = $decoded;
                } else {
                    // If JSON decode fails, start fresh
                    $current_history = array();
                }
            }
        }
        
        // Append new entry
        $current_history[] = $new_entry;
        
        // Update the ACF field - store as JSON string to avoid ACF Code Field errors
        $history_json = json_encode($current_history, JSON_PRETTY_PRINT);
        $update_result = update_field('ppp_receive_hist', $history_json, $post_id);
        
        if ($update_result) {
            return new \WP_REST_Response(array(
                'success' => true,
                'message' => 'Message appended successfully',
                'post_id' => $post_id,
                'timestamp' => $new_entry['timestamp']
            ), 200);
        } else {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => 'Failed to update history',
                'post_id' => $post_id
            ), 500);
        }
    }
}
