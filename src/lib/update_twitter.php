<?php

namespace postplanpro\lib;


class update_twitter
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
        // $this->caption .= $this->fields['release_title'] . PHP_EOL;
        $this->caption .= $this->fields['twitter']['ppp_post_header'] . PHP_EOL;
        
        if ($this->fields["release_content"] != ""){
            $this->caption .= $this->fields['release_content'] . PHP_EOL . PHP_EOL;
        }

        if ($this->fields["release_content"] == ""){
            $this->caption .= $this->fields["twitter"]["ppp_fallback_description"] . PHP_EOL . PHP_EOL;
        }

        $this->caption .= $this->fields['twitter']['ppp_post_footer'] . PHP_EOL;
        $this->caption .= $this->fields['twitter']['ppp_hashtags'] . PHP_EOL;
    }

    private function update_content() {
        update_field('ppp_twitter_status', $this->caption, $this->fields["post"]->ID);
    }

}
