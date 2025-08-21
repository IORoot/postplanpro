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
        $this->fields = array(); // Initialize fields array
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
        
        // Only proceed if we have the required fields
        if (isset($this->fields['post']) && isset($this->fields['schedule'])) {
            new update_json($this->fields);
            new update_preview($this->fields);
        } else {
            error_log('Missing required fields for post ' . $this->post_id . ' - post: ' . (isset($this->fields['post']) ? 'YES' : 'NO') . ', schedule: ' . (isset($this->fields['schedule']) ? 'YES' : 'NO'));
        }
    }



    /**
     * Normal post published date
     */
    private function get_release() {
        $this->fields['post'] = get_post($this->post_id);
        $this->fields['release_method'] = get_field('ppp_release_method', $this->post_id);
        $this->fields['release_schedule'] = get_field('ppp_release_schedule', $this->post_id);

        // Extra Data Fields
        $extra_data = get_field('ppp_extra_data', $this->post_id);
        $formatted_data = array();
        if ($extra_data && is_array($extra_data)) {
            foreach ($extra_data as $data) {
                if (isset($data['ppp_extra_data_key']) && isset($data['ppp_extra_data_value'])) {
                    $formatted_data[$data['ppp_extra_data_key']] = $data['ppp_extra_data_value'];
                }
            }
        }
        $this->fields['extra_data'] = $formatted_data;

        // Global Variables
        $global_variables = get_field('ppp_global_variables', 'options');
        $formatted_variables = array();
        if ($global_variables && is_array($global_variables)) {
            foreach ($global_variables as $variable) {
                if (isset($variable['ppp_global_field_name']) && isset($variable['ppp_global_field_value'])) {
                    $formatted_variables[$variable['ppp_global_field_name']] = $variable['ppp_global_field_value'];
                }
            }
        }
        $this->fields['global_variables'] = $formatted_variables;
    }



    /**
     * Retrieve all the fields of the selected schedule for this release
     */
    private function get_schedule() {
        $schedule_title = get_field('ppp_release_schedule', $this->post_id);
        if (!$schedule_title) { 
            error_log('No schedule title found for post ' . $this->post_id);
            return; 
        }

        $schedule_query = new \WP_Query([
            'post_type'      => 'schedule',
            'post_status'    => 'publish',
            'posts_per_page' => 1, 
            'meta_query'     => [
                [
                    'key'   => 'ppp_schedule_name', 
                    'value' => $schedule_title,
                    'compare' => '=',
                ],
            ],
        ]);

        if (!$schedule_query->have_posts()) {
            error_log('No schedule found with title: ' . $schedule_title . ' for post ' . $this->post_id);
            return;
        }

        // convert the additional fields to a more readable format name=>value
        $this->fields['schedule'] = get_fields($schedule_query->posts[0]->ID);
        
        if (!isset($this->fields['schedule']['ppp_social_platforms']) || !is_array($this->fields['schedule']['ppp_social_platforms'])) {
            error_log('No social platforms found in schedule for post ' . $this->post_id);
            return;
        }

        foreach ($this->fields["schedule"]["ppp_social_platforms"] as $platform_id => $platform) {
            if (!isset($platform["ppp_social_platform_posts"]["ppp_social_platform_additional_fields"]) || !is_array($platform["ppp_social_platform_posts"]["ppp_social_platform_additional_fields"])) {
                continue;
            }
            
            foreach ($platform["ppp_social_platform_posts"]["ppp_social_platform_additional_fields"] as $field_id => $field) {
                if (isset($field["ppp_schedule_social_platform_additional_field_title"]) && isset($field["ppp_schedule_social_platform_additional_field_content"])) {
                    $this->fields["schedule"]["ppp_social_platforms"][$platform_id]["ppp_social_platform_posts"]["ppp_social_platform_additional_fields"][$field["ppp_schedule_social_platform_additional_field_title"]] = $field["ppp_schedule_social_platform_additional_field_content"];
                    unset($this->fields["schedule"]["ppp_social_platforms"][$platform_id]["ppp_social_platform_posts"]["ppp_social_platform_additional_fields"][$field_id]);
                }
            }
        }

        // Make the social platforms more readable
        foreach ($this->fields["schedule"]["ppp_social_platforms"] as $platform_id => $platform) {
            if (isset($platform["ppp_social_platform_name"]) && isset($platform["ppp_social_platform_posts"])) {
                $this->fields["schedule"]["ppp_social_platforms"][strtoupper(str_replace(' ', '', $platform["ppp_social_platform_name"]))] = $platform["ppp_social_platform_posts"];
                unset($this->fields["schedule"]["ppp_social_platforms"][$platform_id]);
            }
        }
    }
    

}