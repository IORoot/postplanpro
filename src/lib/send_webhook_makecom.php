<?php

namespace postplanpro\lib;


// ╭──────────────────────────────────────────────────────────────────────────╮
// │                                                                          │░
// │                    Send a webhook to make.com                            │░
// │                                                                          │░
// ╰░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░

class send_webhook_makecom
{

    public $post;

    
    public function __construct($post)
    {
        $this->post = $post;

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
        $this->target = get_field('ppp_makecom_webhook_url', 'option');
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


        // JSON encode the payload
        $this->payload_json = json_encode( $this->payload );

    }



    /**
     * Prepare the request and send the webhook.
     */
    private function send_webhook()
    {
        if (!$this->target){ 
            error_log( 'Webhook Target not set.' );
            return;
        }

        // Prepare the webhook request
        $this->response = wp_remote_post( $this->target, array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => $this->payload_json,
        ));


        // Check for errors in the response
        if ( is_wp_error( $this->response ) ) {
            error_log( 'Webhook error: ' . $this->response->get_error_message() );
        } else {
            error_log( 'Webhook sent successfully for post ID: ' . $this->post->ID );
        }
    }

}
