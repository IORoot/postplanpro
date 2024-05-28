<?php

namespace postplanpro\lib;


class update_gmb
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
            $this->caption .= $this->fields["gmb"]["ppp_fallback_description"] . PHP_EOL . PHP_EOL;
        }

        $this->caption .= $this->fields['gmb']['ppp_post_footer'] . PHP_EOL . PHP_EOL;
        $this->caption .= $this->fields['gmb']['ppp_hashtags'];
    }

    private function update_content() {
        update_field('ppp_gmb_title', $this->fields['gmb']['ppp_post_header'], $this->fields["post"]->ID);
        update_field('ppp_gmb_summary', $this->caption, $this->fields["post"]->ID);
    }

}
