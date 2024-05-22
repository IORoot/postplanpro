<?php

namespace postplanpro\acf;

/**
 * Disable these fields so you can't manually change the contents.
 */
class acf_read_only_fields
{


    public function __construct(){

        //Readonly acf fields
        $disabled_fields = [
            'ppp_instagram_caption',
            'ppp_facebook_description',
            'ppp_twitter_status',
            'ppp_slack_text',
            'ppp_gmb_title',
            'ppp_gmb_summary',
            'ppp_youtube_title',
            'ppp_youtube_description',
            'ppp_youtube_tags',
            'ppp_youtube_publish_at',
            'ppp_youtube_recording_date',
            'ppp_google_calendar_event_id',
        ];

        foreach($disabled_fields as $disabled_field){
            add_filter('acf/load_field/name='.$disabled_field, [$this,'disable_fields_in_backend']);
        }

    }

    public function disable_fields_in_backend($field) {
        $field['disabled'] = 1;
        return $field;
        
    }

}



