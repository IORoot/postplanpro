<?php

namespace postplanpro\lib;


// ╭──────────────────────────────────────────────────────────────────────────╮
// │                                                                          │░
// │                    Send a webhook to make.com                            │░
// │                                                                          │░
// ╰░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░

class send_webhook
{

    public $post;
    public $response;
    public $context;

    
    public function __construct($post, $context = 'Automatic')
    {
        $this->post = $post;
        $this->context = $context;

        # Get target
        $this->get_webhook_target();

        # create payload
        $this->build_payload();

        # Send the webhook
        $this->send_webhook();
    }


    /**
     * Get the setting on whether to send or not.
     */
    private function get_webhook_target()
    {
        $this->send_in_dev_mode = get_field('ppp_send_in_dev', 'option');
        $this->target = get_field('ppp_send_target', $this->post->ID);
        
        // If no target set on post, try to get from options
        if (!$this->target) {
            $this->target = get_field('ppp_send_target', 'option');
        }
        
        // Debug logging
        error_log('Webhook target for post ' . $this->post->ID . ': ' . ($this->target ? $this->target['value'] : 'NOT SET'));
        error_log('Dev mode setting: ' . ($this->send_in_dev_mode ? 'ENABLED' : 'DISABLED'));
    }



    /**
     * Build the payload to send to the target.
     */
    private function build_payload()
    {
        # Get Post Information
        $this->payload = array(
            'ID' => $this->post->ID,
            'post_title' => $this->post->post_title,
            'post_content' => $this->post->post_content,
            'post_excerpt' => $this->post->post_excerpt,
            'post_status' => $this->post->post_status,
            'post_date' => $this->post->post_date,
            'post_date_gmt' => $this->post->post_date_gmt,
        );

        // Include ACF fields if ACF is installed and active
        if ( function_exists( 'get_fields' ) ) {
            $acf_fields = get_fields( $this->post->ID );
            if ( $acf_fields ) {
                $this->payload = array_merge( $this->payload, $acf_fields );
            }
        }
        
        // Debug logging
        error_log('Payload built for post ' . $this->post->ID . ' - ppp_json exists: ' . (isset($this->payload['ppp_json']) ? 'YES' : 'NO'));
    }



    /**
     * Prepare the request and send the webhook.
     */
    private function send_webhook()
    {
        if (!$this->target){ 
            error_log( 'Webhook Target not set.' );
            $this->log_webhook_event('Send', 'Failed: Webhook Target not set');
            return;
        }

        if ($_SERVER['SERVER_NAME'] == 'localhost') {
            // You are on localhost
            if (!$this->send_in_dev_mode) {
                error_log( 'Webhook not sent because you are on localhost and send_in_dev_mode is false.' );
                $this->log_webhook_event('Send', 'Failed: Localhost mode disabled');
                return;
            }
        }

        // Ensure ppp_json exists before sending
        if (!isset($this->payload['ppp_json']) || empty($this->payload['ppp_json'])) {
            error_log('ppp_json not found, attempting to generate it for post ' . $this->post->ID);
            $this->generate_json_if_missing();
        }

        // Check again if ppp_json exists
        if (!isset($this->payload['ppp_json']) || empty($this->payload['ppp_json'])) {
            error_log('Failed to generate ppp_json for post ' . $this->post->ID);
            $this->log_webhook_event('Send', 'Failed: Could not generate ppp_json');
            return;
        }

        // Log the webhook send attempt
        $this->log_webhook_event('Send', 'Webhook URL: ' . $this->target['value']);

        // Prepare the webhook request
        $this->response = wp_remote_post( $this->target['value'], array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => $this->payload["ppp_json"],
        ));

        // Check for errors in the response
        if ( is_wp_error( $this->response ) ) {
            error_log( 'Webhook error: ' . $this->response->get_error_message() );
            $this->log_webhook_event('Receive', 'Error: ' . $this->response->get_error_message());
        } else {
            $response_code = wp_remote_retrieve_response_code($this->response);
            $response_body = wp_remote_retrieve_body($this->response);
            
            if ($response_code >= 200 && $response_code < 300) {
                error_log( 'Webhook sent successfully for post ID: ' . $this->post->ID );
                $this->log_webhook_event('Receive', 'Success: HTTP ' . $response_code . ' - ' . substr($response_body, 0, 100) . (strlen($response_body) > 100 ? '...' : ''));
            } else {
                error_log( 'Webhook sent but received error response: ' . $response_code );
                $this->log_webhook_event('Receive', 'Error: HTTP ' . $response_code . ' - ' . substr($response_body, 0, 100) . (strlen($response_body) > 100 ? '...' : ''));
            }
        }
    }

    /**
     * Generate JSON if it's missing
     */
    private function generate_json_if_missing()
    {
        // Get the release fields
        $release_fields = new \postplanpro\lib\update_release_acf_social_fields($this->post->ID);
        $fields = $release_fields->get_fields();
        
        if ($fields) {
            // Generate the JSON
            $update_json = new \postplanpro\lib\update_json($fields);
            
            // Get the generated JSON
            $generated_json = get_field('ppp_json', $this->post->ID);
            if ($generated_json) {
                $this->payload['ppp_json'] = $generated_json;
                error_log('Successfully generated ppp_json for post ' . $this->post->ID);
            } else {
                error_log('Failed to generate ppp_json for post ' . $this->post->ID);
            }
        } else {
            error_log('Could not get release fields for post ' . $this->post->ID);
        }
    }

    /**
     * Log webhook events to the ppp_send_history field
     */
    private function log_webhook_event($action, $detail)
    {
        $timestamp = current_time('Y-m-d H:i:s');
        $context_detail = $this->context . ': ' . $detail;
        $csv_line = sprintf('"%s","%s","%s"', $timestamp, $action, $context_detail);
        
        // Get current history
        $current_history = get_field('ppp_send_history', $this->post->ID);
        if (!$current_history) {
            $current_history = '';
        }
        
        // Add new line to the top
        $new_history = $csv_line . "\n" . $current_history;
        
        // Limit history to last 1000 lines to prevent the field from getting too large
        $lines = explode("\n", $new_history);
        if (count($lines) > 1000) {
            $lines = array_slice($lines, 0, 1000);
            $new_history = implode("\n", $lines);
        }
        
        // Update the field
        update_field('ppp_send_history', $new_history, $this->post->ID);
    }

}
