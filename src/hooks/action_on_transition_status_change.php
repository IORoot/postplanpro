<?php

namespace postplanpro\hooks;

use postplanpro\lib\send_webhook;

/**
 * This action monitors for when posts change their status.
 * If a 'release' switches to 'publish' status, send a
 * webhook off to the target with all the correct data.
 */
class action_on_transition_status_change
{


    public function __construct( )
    {
        add_action( 'transition_post_status', [$this,'run_to_publish'], 10, 3 );
    }


    /**
     * Changing from [future] -> [publish]
     */
    public function run_to_publish($new_status, $old_status, $post) {
        
        # If this isn't a 'release', skip.
        if ('release' !== $post->post_type){ 
            error_log('Transition hook: Not a release post type');
            return; 
        }

        # If this wasn't a scheduled post, skip.
        if ('future'  !== $old_status ){ 
            error_log('Transition hook: Not from future status (was: ' . $old_status . ')');
            return; 
        }

        # If the new status isn't 'publish', skip.
        if ('publish' !== $new_status){ 
            error_log('Transition hook: Not to publish status (is: ' . $new_status . ')');
            return; 
        }

        # Trigger or not?
        $send_webhook = get_field('ppp_send_webhook', $post->ID);
        error_log('Transition hook: ppp_send_webhook field value: ' . ($send_webhook ? 'TRUE' : 'FALSE'));
        
        # If the field is not set (null) or explicitly set to true, send the webhook
        # Only skip if the field is explicitly set to false
        if ($send_webhook === false) {
            error_log('Transition hook: Webhook explicitly disabled for post ' . $post->ID);
            return; 
        }

        error_log('Transition hook: Sending webhook for post ' . $post->ID);
        
        # Ensure JSON is generated before sending webhook
        $this->ensure_json_generated($post);
        
        # Send to Make.com
        new send_webhook($post, 'Status Change');
    }

    /**
     * Ensure JSON is generated for the post
     */
    private function ensure_json_generated($post) {
        // Check if ppp_json exists
        $existing_json = get_field('ppp_json', $post->ID);
        if (!$existing_json) {
            error_log('Transition hook: ppp_json not found, generating it for post ' . $post->ID);
            
            // Generate the JSON by calling the update_release_acf_social_fields class
            $release_fields = new \postplanpro\lib\update_release_acf_social_fields($post->ID);
            $fields = $release_fields->get_fields();
            
            if ($fields) {
                error_log('Transition hook: Successfully generated JSON for post ' . $post->ID);
            } else {
                error_log('Transition hook: Failed to generate JSON for post ' . $post->ID);
            }
        } else {
            error_log('Transition hook: ppp_json already exists for post ' . $post->ID);
        }
    }


}
