<?php

namespace postplanpro\hooks;

class action_show_server_time
{

    public function __construct()
    {
        add_action( 'post_submitbox_misc_actions', [$this,'show_server_time'] );
    }


    public function show_server_time() {
        // Get the server date and time
        $server_date_time = date('j F Y \a\t H:i:s'); // Adjust the format as needed

        // Output the server date and time in the publish box
        echo '<div class="misc-pub-section misc-pub-section-last">';
        echo '<span>Now: ' . esc_html($server_date_time) . '</span>';
        echo '</div>';
    }


}