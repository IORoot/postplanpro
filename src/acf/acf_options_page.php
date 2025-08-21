<?php

namespace postplanpro\acf;

/**
 * This will generate the parent page for postplanpro with 
 * and icon as well as all of the sub-pages underneath.
 */
class acf_options_page
{

    public $parent_title = 'PostPlanPro';
    public $parent_b64_icon = 'data:image/svg+xml;base64,PHN2ZyB2aWV3Qm94PScwIDAgMjQgMjQnIHhtbG5zPSdodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2Zyc+PHBhdGggZmlsbD0nIzBkOTQ4OCcgZD0nTTE3IDdWOUgyMlY3SDE3TTIgOVYxNUg3VjlIMk0xMiA5VjExSDlWMTNIMTJWMTVMMTUgMTJMMTIgOU0xNyAxMVYxM0gyMlYxMUgxN00xNyAxNVYxN0gyMlYxNUgxN1onLz48L3N2Zz4=';


    public function __construct(){
        add_action('acf/init', [$this,'initialise']);
    }



    public function initialise() {
        $this->add_parent_page();

        $this->add_child_page('Calendar', 'ppp_calendar');
        $this->add_child_page('Settings', 'ppp_settings');

    }


    public function add_parent_page()
    {    
        if (!function_exists('acf_add_options_page')) { return; }

        $this->parent = acf_add_options_page(array(
            'page_title'    => __($this->parent_title),
            'menu_slug'     => strtolower($this->parent_title),
            'icon_url'      => $this->parent_b64_icon,
            'capability'    => 'manage_options',
            'redirect'      => false,
        ));
        
    }

    public function add_child_page($title, $slug)
    {    
        if (!function_exists('acf_add_options_sub_page')) { return; }

        return acf_add_options_sub_page(array(
            'page_title'    => __($title),
            'menu_slug'     => $slug,
            'parent_slug'   => $this->parent['menu_slug'],
        ));
    }

}



