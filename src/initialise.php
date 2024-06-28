<?php

namespace videoconstructor;

class initialise
{
    public function run()
    {
        $this->acf_init();
    }

    public function acf_init()
    {
        
        # Create the options page on admin
        new acf\acf_options_page();

        # On update - build JSON
        new acf\acf_on_update();

        # Add Tailwind and Material Icons to admin
        new acf\style_admin();     
    }


}