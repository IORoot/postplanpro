<?php

namespace postplanpro\acf;

/**
 * When you create a new release, you need to pick a 
 * schedule to apply to it. This populates the select box
 * with all schedules that have been created.
 */
class acf_populate_release_schedule_select
{


    public function __construct(){
        add_action('acf/load_field/name=ppp_release_schedule', [$this,'populate']);
    }


    /**
     * This will get a list of all entries in the schedules
     * repeater and use the schedule_name to populate
     * the select field in the template.
     */
    public function populate($field) {

        // reset choices
        $field['choices'] = array();

        $ppp_schedules = new \WP_Query([
            'post_type'      => 'schedule',
            'post_status'    => 'publish', 
            'posts_per_page' => -1,       
        ]);

        foreach ($ppp_schedules->posts as $index => $post) {
            $acf_fields = get_fields($post->ID);

            $schedule_name = $acf_fields["ppp_schedule_name"];

            // Avoid duplicate entries
            if (!in_array($schedule_name, $field['choices'])) {
                $field['choices'][$schedule_name] = $schedule_name;
            }
        }
    
        // return the field
        return $field;
        
    }

}



