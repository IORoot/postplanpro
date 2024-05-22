<?php

namespace postplanpro\acf;

use postplanpro\lib\update_instagram;
use postplanpro\lib\update_facebook;
use postplanpro\lib\update_youtube;
use postplanpro\lib\update_twitter;
use postplanpro\lib\update_gmb;
use postplanpro\lib\update_slack;

/**
 * This will generate the social media text when a releases 
 * post is updated and saved.
 */
class acf_on_update_releases
{

    public $page_name = 'release';
    public $post_id;
    public $fields;



    public function __construct(){
        add_action( 'acf/save_post', [$this, 'update'], 20 );
    }



    public function update($post_id) {
        $this->post_id = $post_id;

        # Check that this is the correct page
        $screen = get_current_screen();
        if ($screen->id !== $this->page_name) { return; }

        // Update the social platforms content
        $this->update_platforms();

        // Update published status & datetime
        $this->update_published_date();
    }


    public function REST_update($post_id) {
        $this->post_id = $post_id;

        // Update the social platforms content
        $this->update_platforms();

        // Update published status & datetime
        $this->update_published_date();
    }



    // ╭──────────────────────────────────────────────────────────────────────────╮
    // │                                                                          │░
    // │                   Update the content of the platforms                    │░
    // │                                                                          │░
    // ╰░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
    private function update_platforms() {
        $this->get_release_post();
        $this->get_release_title();
        $this->get_release_content();
        $this->get_release_date();
        $this->get_release_modified();
        $this->get_release_method();
        $this->get_release_schedule();
        $this->get_schedule();
        $this->get_templates();
        $this->update_content();
    }


        /**
     * Normal post published date
     */
    private function get_release_post() {
        $this->fields['post'] = get_post($this->post_id);
    }


    /**
     * Normal post title
     */
    private function get_release_title() {
        $this->fields['release_title'] = get_post_field('post_title', $this->post_id);
    }




    /**
     * Normal post content
     */
    private function get_release_content() {
        $this->fields['release_content'] = get_post_field('post_content', $this->post_id);
    }



    /**
     * Normal post published date
     */
    private function get_release_date() {
        $this->fields['release_date'] = get_post_field('post_date', $this->post_id);
    }



    /**
     * Normal post modified date
     */
    private function get_release_modified() {
        $this->fields['release_modified'] = get_post_field('post_modified', $this->post_id);
    }



    /**
     * Retrieve the name of the selected schedule.
     */
    private function get_release_method() {
        $this->fields['release_method'] = get_field('ppp_release_method', $this->post_id);
    }



    /**
     * Retrieve the name of the selected schedule.
     */
    private function get_release_schedule() {
        $this->fields['release_schedule'] = get_field('ppp_release_schedule', $this->post_id);
    }




    /**
     * Retrieve all the fields of the selected schedule for this release
     */
    private function get_schedule() {
        $schedules = get_field('ppp_schedule', 'option');

        if ($schedules) {
            foreach ($schedules as $schedule) {
                if ($schedule['ppp_schedule_name'] == $this->fields['release_schedule']) {
                    $this->fields['schedule'] = $schedule;
                    break;
                }
            }
        }
    }
    


    /**
     * Retrieve all templates that have been selected for the schedule
     */
    private function get_templates() {
        if (!$this->fields["schedule"]["ppp_schedule_social_template"]){ return; }
        
        // loop through each template specified in the schedule
        foreach ($this->fields["schedule"]["ppp_schedule_social_template"] as $index => $template_name) {

            if (!$template_name){ 
                continue; 
            }

            // get all the templates
            $templates = get_field('ppp_templates', 'option');

            if (!$templates) { return; }

            // loop through each template and return the ones specified
            foreach ($templates as $template) {
                if ($template['ppp_template_name'] == $template_name) {
                    $this->fields[$template['acf_fc_layout']] = $template;
                }
            }

        }

    }



    /**
     * Update the social platform content field
     */
    private function update_content() {
        if ($this->fields['instagram']) { new update_instagram($this->fields); }
        if ($this->fields['facebook'])  { new update_facebook($this->fields);  }
        if ($this->fields['youtube'])   { new update_youtube($this->fields);   }
        if ($this->fields['twitter'])   { new update_twitter($this->fields);   }
        if ($this->fields['slack'])     { new update_slack($this->fields);     }
        if ($this->fields['gmb'])       { new update_gmb($this->fields);       }
    }


    // ╭──────────────────────────────────────────────────────────────────────────╮
    // │                                                                          │░
    // │          Update the status of the release, including date/time           │░
    // │                                                                          │░
    // ╰░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░


    # Place the release on the next future date in regards to the schedule.
    # If there is already a post for that schedule, add it to the next date.
    private function update_published_date()
    {
        
        # If scheduling is disabled, skip.
        if (!$this->fields["release_method"]){ return; }


        # Get the schedule details
        $schedule_name = $this->fields["release_schedule"];                   # Name of schedule
        $schedule_day = $this->fields["schedule"]["ppp_schedule_day"];        # Which day to schedule on
        $schedule_time = $this->fields["schedule"]["ppp_schedule_post_time"]; # Time of day to schedule on
        $schedule_delay = $this->fields["schedule"]["ppp_schedule_repeat"];   # Minutes until next schedule

        // Convert schedule day to a numerical day of the week (0 = Sunday, 6 = Saturday)
        $schedule_day_num = date('N', strtotime($schedule_day));

        // Calculate the initial proposed publish date
        $dateToday = date('Y-m-d');
        $current_date = new \DateTime($dateToday);
        $current_day_num = $current_date->format('N');

        // If today is after the scheduled day, move to the next week
        if ($current_day_num > $schedule_day_num) {
            $days_to_add = 7 - ($current_day_num - $schedule_day_num);
        } else {
            $days_to_add = $schedule_day_num - $current_day_num;
        }

        # Set the proposed datetime
        $proposed_date = (clone $current_date)->modify("+$days_to_add days");
        $proposed_date->setTime(...explode(':', $schedule_time));

        # Get all the releases with same schedule
        $releases = get_posts(array(
            'post_type' => 'release',
            'posts_per_page' => -1,
            'post_status' => array('publish', 'pending', 'future', 'private'),
            'meta_query' => array(
                array(
                    'key' => 'ppp_release_schedule',
                    'value' => $schedule_name,
                    'compare' => '='
                )
            )
        ));

        // Find the next available date
        while ($this->is_date_taken($proposed_date, $releases)) {
            // $proposed_date->modify('+7 days');
            $proposed_date->modify("+{$schedule_delay} minutes");
        }

        // Combine the proposed date with the scheduled time
        $proposed_datetime = $proposed_date->format('Y-m-d H:i:s');

        // Update the current post.
        wp_update_post(array(
            'ID' => $this->post_id,
            'post_date' => $proposed_datetime,
            'post_date_gmt' => get_gmt_from_date($proposed_datetime)
        ));
    }



    // Function to check if a date/time is already used
    private function is_date_taken($date, $releases) {

        foreach ($releases as $release) {
            $release_date = new \DateTime(get_post_field('post_date', $release->ID));
            if ($release_date->format('Y-m-d H:i:s') == $date->format('Y-m-d H:i:s')) {
                return true;
            }
        }

        return false;
    }


}
