<?php

namespace postplanpro\acf;
/**
 * This will generate the social media text when a releases 
 * post is updated and saved.`
 */
class acf_on_update_schedules
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
        $this->acf_fields = get_fields($post_id);
        $this->post_meta = get_post_meta($post_id);

        # Check that this is the correct page
        $screen = get_current_screen();
        $skip = true;
        if ($screen->id == 'schedule') { $skip = false; }
        if ($skip) { return; }

        if (!$this->acf_fields["ppp_reschedule_all"] ) { return; }

        $this->reschedule();
    }


    private function reschedule() {
    
        # push all scheduled dates far into the future
        $releases = get_posts(array(
            'post_type' => 'release',
            'posts_per_page' => -1,
            'post_status' => array('publish', 'pending', 'future', 'private'),
        ));

        foreach ($releases as $release) {
            $release_date = get_post_field('post_date', $release->ID);
            $release_date = new \DateTime($release_date);
            $release_date->modify('+10 years');
            wp_update_post(array(
                'ID' => $release->ID,
                'post_date' => $release_date->format('Y-m-d H:i:s'),
                'post_modified' => $release_date->format('Y-m-d H:i:s'),
                'post_date_gmt' => get_gmt_from_date($release_date->format('Y-m-d H:i:s')),
                'post_modified_gmt' => get_gmt_from_date($release_date->format('Y-m-d H:i:s'))
            ));
        }
        
        
        foreach ($releases as $release) {
            $this->update_publish_date($release->ID);
        }

    }


    
    # Place the release on the next future date in regards to the schedule.
    # If there is already a post for that schedule, add it to the next date.
    private function update_publish_date($release_id)
    {


        # Get the schedule details
        $schedule_name = $this->acf_fields["ppp_schedule_name"];      # Name of schedule
        $schedule_day = $this->acf_fields["ppp_schedule_day"];        # Which day to schedule on
        $schedule_time = $this->acf_fields["ppp_schedule_post_time"]; # Time of day to schedule on
        $schedule_delay = $this->acf_fields["ppp_schedule_repeat"];   # Minutes until next schedule

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
            'ID' => $release_id,
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