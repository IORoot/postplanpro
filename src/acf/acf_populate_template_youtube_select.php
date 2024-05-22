<?php

namespace postplanpro\acf;

/**
 * When you create a new template it needs to reference all
 * of the schedules that have been generates. This will
 * populate that select box from all of the entries.
 */
class acf_populate_template_youtube_select
{

    public $platform = 'youtube';


    public function __construct(){
        add_action('acf/load_field/name=ppp_schedule_youtube_template', [$this,'populate']);
    }


    /**
     * This will get a list of all entries in the schedules
     * repeater and use the schedule_name to populate
     * the select field in the template.
     */
    public function populate($field) {

        // reset choices
        $field['choices'] = array();

        // Check if the repeater field has rows
        if (have_rows('ppp_templates', 'option')) {

            // Loop through each row in the repeater field
            while (have_rows('ppp_templates', 'option')) {

                $row = the_row();

                // Check that this is the correct social platform so
                // we don't list instagram templates in the youtube
                // select list.
                if ($row['acf_fc_layout'] !== $this->platform){ continue; }

                $template_name = get_sub_field('ppp_template_name');

                // Avoid duplicate entries
                if (!in_array($template_name, $field['choices'])) {
                    $field['choices'][$template_name] = $template_name;
                }
            }
        }
    
        // return the field
        return $field;
        
    }

}



