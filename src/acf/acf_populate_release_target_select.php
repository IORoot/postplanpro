<?php

namespace postplanpro\acf;

/**
 * When you create a new release, you need to pick a 
 * target to apply to it. This populates the select box
 * with all targets that have been created in settings.
 */
class acf_populate_release_target_select
{


    public function __construct(){
        add_action('acf/load_field/name=ppp_send_target', [$this,'populate']);
    }


    /**
     * This will get a list of all entries in the schedules
     * repeater and use the schedule_name to populate
     * the select field in the template.
     */
    public function populate($field) {

        // reset choices
        $field['choices'] = array();

        $ppp_targets = get_field('ppp_targets', 'options');

        foreach ($ppp_targets as $index => $target) {
            $target_name = $target['ppp_target_name'];
            $target_url = $target['ppp_target_url'];
            $field['choices'][$target_url] = $target_name;
        }
    
        // return the field
        return $field;
        
    }

}