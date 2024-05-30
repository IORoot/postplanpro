<?php

namespace postplanpro\hooks;

use postplanpro\lib\send_webhook_makecom;

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
        if ('release' !== $post->post_type){ return; }

        # If this wasn't a scheduled post, skip.
        if ('future'  !== $old_status ){ return; }

        # If the new status isn't 'publish', skip.
        if ('publish' !== $new_status){ return; }

        # Trigger or not?
        if (! get_field('ppp_send_webhook', $post->ID)){ return; }

        # Send to Make.com
        new send_webhook_makecom($post);
    }


}
