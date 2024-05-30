<?php

namespace postplanpro\acf;

use postplanpro\lib\update_instagram;
use postplanpro\lib\update_facebook;
use postplanpro\lib\update_youtube;
use postplanpro\lib\update_twitter;
use postplanpro\lib\update_gmb;
use postplanpro\lib\update_slack;
use postplanpro\lib\update_publish_date;
use postplanpro\lib\send_webhook_makecom;
use postplanpro\lib\update_release_acf_social_fields;

/**
 * This will generate the social media text when a releases 
 * post is updated and saved.
 */
class acf_on_update_releases
{
    public $post;
    public $post_id;
    public $release;


    public function __construct(){
        add_action( 'acf/save_post', [$this, 'update'], 20 );
    }



    public function update($post_id) {
        $this->post_id = $post_id;
        $this->post = get_post($post_id);

        # Check that this is the correct page
        $screen = get_current_screen();
        if ($screen->id !== 'release') { return; }

        // Update the social platforms content
        $release = new update_release_acf_social_fields($this->post_id);
        $this->release = $release->get_fields();

        // Update published status & datetime
        new update_publish_date($this->post_id, $this->release);

        // Send the webhook if override is set.
        if (get_field('ppp_schedule_override', $this->post_id)){
            new send_webhook_makecom($this->post);
        }
        
    }



    # Trigger without checking the current screen.
    # Used from a REST request.
    public function REST_update($post_id) {
        $this->post_id = $post_id;
        $this->post = get_post($post_id);

        // Update the social platforms content
        $release = new update_release_acf_social_fields($this->post_id);
        $this->release = $release->get_fields();

        // Update published status & datetime
        new update_publish_date($this->post_id, $this->release);

        // Send the webhook
        if (get_field('ppp_schedule_override', $this->post_id)){
            new send_webhook_makecom($this->post);
        }
    }



}