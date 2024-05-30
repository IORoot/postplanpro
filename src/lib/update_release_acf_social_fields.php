<?php

namespace postplanpro\lib;


// ╭──────────────────────────────────────────────────────────────────────────╮
// │                                                                          │░
// │          Update the social media fields of the release                    │░
// │                                                                          │░
// ╰░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░

class update_release_acf_social_fields
{
   
    public $fields;
    public $post_id;

    public function __construct($post_id)
    {
        $this->post_id = $post_id;
        $this->update_platforms();
    }

    public function get_fields()
    {
        return $this->fields;
    }

    // ╭──────────────────────────────────────────────────────────────────────────╮
    // │                                                                          │░
    // │                   Update the content of the platforms                    │░
    // │                                                                          │░
    // ╰░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
    private function update_platforms() {
        $this->get_release();
        $this->get_schedule();
        $this->get_templates();
        
        if ($this->fields['instagram']) { new update_instagram($this->fields); }
        if ($this->fields['facebook'])  { new update_facebook($this->fields);  }
        if ($this->fields['youtube'])   { new update_youtube($this->fields);   }
        if ($this->fields['twitter'])   { new update_twitter($this->fields);   }
        if ($this->fields['slack'])     { new update_slack($this->fields);     }
        if ($this->fields['gmb'])       { new update_gmb($this->fields);       }

    }



    /**
     * Normal post published date
     */
    private function get_release() {
        $this->fields['post'] = get_post($this->post_id);
        $this->fields['release_title'] = get_post_field('post_title', $this->post_id);
        $this->fields['release_content'] = get_post_field('post_content', $this->post_id);
        $this->fields['release_date'] = get_post_field('post_date', $this->post_id);
        $this->fields['release_modified'] = get_post_field('post_modified', $this->post_id);
        $this->fields['release_method'] = get_field('ppp_release_method', $this->post_id);
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
}