<?php

namespace videoconstructor\acf;

/**
 * This will generate the parent page for videoconstructor with 
 * and icon as well as all of the sub-pages underneath.
 */
class acf_options_page
{

    public $parent;
    public $parent_title = 'VideoConstructor';
    public $parent_b64_icon = 'data:image/svg+xml;base64,PHN2ZyB2aWV3Qm94PScwIDAgMjQgMjQnIHhtbG5zPSdodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2Zyc+PHBhdGggZmlsbD0nb3JhbmdlJyBkPSdNMTMuMTMgMjIuMTlMMTEuNSAxOC4zNkMxMy4wNyAxNy43OCAxNC41NCAxNyAxNS45IDE2LjA5TDEzLjEzIDIyLjE5TTUuNjQgMTIuNUwxLjgxIDEwLjg3TDcuOTEgOC4xQzcgOS40NiA2LjIyIDEwLjkzIDUuNjQgMTIuNU0xOS4yMiA0QzE5LjUgNCAxOS43NSA0IDE5Ljk2IDQuMDVDMjAuMTMgNS40NCAxOS45NCA4LjMgMTYuNjYgMTEuNThDMTQuOTYgMTMuMjkgMTIuOTMgMTQuNiAxMC42NSAxNS40N0w4LjUgMTMuMzdDOS40MiAxMS4wNiAxMC43MyA5LjAzIDEyLjQyIDcuMzRDMTUuMTggNC41OCAxNy42NCA0IDE5LjIyIDRNMTkuMjIgMkMxNy4yNCAyIDE0LjI0IDIuNjkgMTEgNS45M0M4LjgxIDguMTIgNy41IDEwLjUzIDYuNjUgMTIuNjRDNi4zNyAxMy4zOSA2LjU2IDE0LjIxIDcuMTEgMTQuNzdMOS4yNCAxNi44OUM5LjYyIDE3LjI3IDEwLjEzIDE3LjUgMTAuNjYgMTcuNUMxMC44OSAxNy41IDExLjEzIDE3LjQ0IDExLjM2IDE3LjM1QzEzLjUgMTYuNTMgMTUuODggMTUuMTkgMTguMDcgMTNDMjMuNzMgNy4zNCAyMS42MSAyLjM5IDIxLjYxIDIuMzlTMjAuNyAyIDE5LjIyIDJNMTQuNTQgOS40NkMxMy43NiA4LjY4IDEzLjc2IDcuNDEgMTQuNTQgNi42M1MxNi41OSA1Ljg1IDE3LjM3IDYuNjNDMTguMTQgNy40MSAxOC4xNSA4LjY4IDE3LjM3IDkuNDZDMTYuNTkgMTAuMjQgMTUuMzIgMTAuMjQgMTQuNTQgOS40Nk04Ljg4IDE2LjUzTDcuNDcgMTUuMTJMOC44OCAxNi41M002LjI0IDIyTDkuODggMTguMzZDOS41NCAxOC4yNyA5LjIxIDE4LjEyIDguOTEgMTcuOTFMNC44MyAyMkg2LjI0TTIgMjJIMy40MUw4LjE4IDE3LjI0TDYuNzYgMTUuODNMMiAyMC41OVYyMk0yIDE5LjE3TDYuMDkgMTUuMDlDNS44OCAxNC43OSA1LjczIDE0LjQ3IDUuNjQgMTQuMTJMMiAxNy43NlYxOS4xN1onLz48L3N2Zz4=';

    public $settings;
    public $settings_title = 'VideoConstructor Settings';

    public $calendar;
    public $calendar_title = 'VideoConstructor Calendar';



    public function __construct(){
        add_action('acf/init', [$this,'initialise']);
    }



    public function initialise() {
        $this->add_parent_page();
        // $this->add_calendar_page();
        // $this->add_settings_page();
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



