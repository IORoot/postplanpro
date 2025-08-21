<?php

namespace postplanpro;

class initialise
{

    private $config;

    public function run()
    {
        $this->acf_init();
    }

    public function acf_init()
    {
        
        # Create the options page on admin
        new acf\acf_options_page();

        # build the custom post types
        new acf\acf_cpt_releases();
        new acf\acf_cpt_schedules();

        # Populate the release schedule select box
        new acf\acf_populate_release_schedule_select();

        # Populate targets select box
        new acf\acf_populate_release_target_select();

        # Regenerate social media when a release is updated
        new acf\acf_on_update_releases();

        # When the reschedule is run
        new acf\acf_on_update_schedules();

        # Add custom columns to the 'all releases' listings
        new lib\all_releases_columns();
        
        # Add bulk actions for releases
        new lib\bulk_actions();
        
        # Add html calendar to the calendar options page
        new acf\acf_field_html_calendar();

        # Add a custom HTML meta box to be updated with the preview
        new hooks\action_add_meta_box_for_html_preview();
        
        # When a release switches to published, run webhook
        new hooks\action_on_transition_status_change();

        # Register REST API Routes
        new hooks\action_release_rest_routes();

        # Register webhook receiver endpoint
        new hooks\action_webhook_receiver();

        # Display the server time in the publish box.
        new hooks\action_show_server_time();
    }


}