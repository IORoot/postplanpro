<?php

namespace postplanpro\lib;


class update_youtube
{

    public $fields;
    public $caption = '';

    public function __construct($fields)
    {
        if (!$fields){ return false; }
        $this->fields = $fields;
        $this->create_content();
        $this->update_content();
    }

    private function create_content() {

        if ($this->fields["release_content"] != ""){
            $this->caption .= $this->fields['release_content'] . PHP_EOL . PHP_EOL;
        }

        if ($this->fields["release_content"] == ""){
            $this->caption .= $this->fields["youtube"]["ppp_fallback_description"] . PHP_EOL . PHP_EOL;
        }
        
        $this->caption .= $this->fields['youtube']['ppp_post_footer'] . PHP_EOL;
    }

    private function update_content() {
        update_field('ppp_youtube_title', $this->fields['youtube']['ppp_post_header'], $this->fields["post"]->ID);
        update_field('ppp_youtube_description', $this->caption, $this->fields["post"]->ID);
        update_field('ppp_youtube_tags', $this->fields['youtube']['ppp_hashtags'], $this->fields["post"]->ID);
        update_field('ppp_youtube_publish_at', $this->fields["release_date"], $this->fields["post"]->ID);
        update_field('ppp_youtube_recording_date', $this->fields["release_modified"], $this->fields["post"]->ID);
    }

}
