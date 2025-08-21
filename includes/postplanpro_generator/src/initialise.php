<?php

namespace ppp_gen;

class initialise
{

    private $config;

    public function run()
    {
        $this->acf_init();
    }

    public function acf_init()
    {
        
        new acf\acf_cpt_generators();

        new acf\acf_on_update();

        new acf\acf_populate_release_schedule_select();
        new acf\acf_populate_release_target_select();

        # Add custom columns to the generators listings
        new lib\generators_columns();

        # Add bulk actions for generators
        new lib\bulk_actions();

    }


}