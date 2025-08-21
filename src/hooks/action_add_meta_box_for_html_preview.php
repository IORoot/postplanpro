<?php

namespace postplanpro\hooks;

class action_add_meta_box_for_html_preview
{


    public function __construct( )
    {
        add_action( 'add_meta_boxes', [$this,'my_custom_meta_box'], 10, 3 );
    }

    public function my_custom_meta_box() {
        add_meta_box(
            'custom_info_box',
            'Post Preview',
            [$this, 'my_custom_meta_box_callback'],
            'release',
            'normal',  // Position: 'normal', 'side', 'advanced'
            'high'
        );
    }
    
    public function my_custom_meta_box_callback($post) {

        $preview = get_field('ppp_preview', $post->ID);
        if ($preview) {
            echo $preview;
        } else {
            echo '<p>No preview available</p>';
        }
    }


}
