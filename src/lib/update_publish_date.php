<?php

namespace postplanpro\lib;


// ╭──────────────────────────────────────────────────────────────────────────╮
// │                                                                          │░
// │          Update the status of the release, including date/time           │░
// │                                                                          │░
// ╰░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░

class update_publish_date
{

    public $post_id;
    public $fields;

    public function __construct($post_id, $fields)
    {
        if (!$post_id){ return false; }
        if (!$fields){ return false; }
        $this->post_id = $post_id;
        $this->fields = $fields;

        $this->update_published_date();
    }

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