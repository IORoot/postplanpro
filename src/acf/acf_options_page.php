<?php

namespace postplanpro\acf;

/**
 * This will generate the parent page for postplanpro with 
 * and icon as well as all of the sub-pages underneath.
 */
class acf_options_page
{

    public $parent;
    public $parent_title = 'PostPlanPro';
    public $parent_b64_icon = 'data:image/svg+xml;base64,PHN2ZyB2aWV3Qm94PScwIDAgMjQgMjQnIHhtbG5zPSdodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2Zyc+PHBhdGggZmlsbD0nIzBkOTQ4OCcgZD0nTTE3IDdWOUgyMlY3SDE3TTIgOVYxNUg3VjlIMk0xMiA5VjExSDlWMTNIMTJWMTVMMTUgMTJMMTIgOU0xNyAxMVYxM0gyMlYxMUgxN00xNyAxNVYxN0gyMlYxNUgxN1onLz48L3N2Zz4=';

    public $settings;
    public $settings_title = 'Release Settings';

    public $calendar;
    public $calendar_title = 'Release Calendar';



    public function __construct(){
        add_action('acf/init', [$this,'initialise']);
    }



    public function initialise() {
        $this->add_parent_page();
        $this->add_calendar_page();
        $this->add_settings_page();
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



    public function add_settings_page()
    {
        if (!function_exists('acf_add_options_sub_page')) { return; }

        $this->settings = acf_add_options_sub_page(array(
            'page_title'    => __($this->settings_title),
            'menu_slug'     => 'ppp_settings',
            'parent_slug'   => $this->parent['menu_slug'],
        ));
        
    }



    public function add_calendar_page()
    {
        if (!function_exists('acf_add_options_sub_page')) { return; }

        $this->calendar = acf_add_options_sub_page(array(
            'page_title'    => __($this->calendar_title),
            'menu_slug'     => 'ppp_calendar',
            'parent_slug'   => $this->parent['menu_slug'],
        ));
        
    }
}



