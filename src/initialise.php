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

        # build the custom post type 'releases'
        new acf\acf_cpt_releases();

        # Whenever new schedule added, update select box for
        # social platform template list.
        new acf\acf_populate_template_instagram_select();
        new acf\acf_populate_template_youtube_select();
        new acf\acf_populate_template_facebook_select();
        new acf\acf_populate_template_twitter_select();
        new acf\acf_populate_template_gmb_select();
        new acf\acf_populate_template_slack_select();

        # Populate the release schedule select box
        new acf\acf_populate_release_schedule_select();

        # Regenerate social media when a release is updated
        new acf\acf_on_update_releases();

        # prevent manual edit of text on these fields
        new acf\acf_read_only_fields();

        # Add custom columns to the 'all releases' listings
        new lib\all_releases_columns();

        # Add html calendar to the calendar options page
        new acf\acf_field_html_calendar();

        # When a release switches to published, run webhook
        new hooks\action_on_transition_status_change();

        # Register REST API Routes
        new hooks\action_release_rest_routes();

        # Display the server time in the publish box.
        new hooks\action_show_server_time();
    }


}