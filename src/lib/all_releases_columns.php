<?php

namespace postplanpro\lib;


class all_releases_columns
{


    public function __construct()
    {
        // Hook into the columns filter to add a custom column
        add_filter('manage_release_posts_columns', [$this,'add_custom_columns']);

        // Hook into the action to populate the custom column with data
        add_action('manage_release_posts_custom_column', [$this,'custom_column_content'], 10, 2);

        // Make the custom column sortable
        add_filter('manage_edit-release_sortable_columns', [$this,'custom_column_sortable']);

        // Handle the sorting of the custom column
        add_action('pre_get_posts', [$this,'custom_column_orderby']);

    }


    public function add_custom_columns($columns) {
        // Add a new column for Instagram Caption
        $columns['ppp_instagram_caption'] = __('Instagram Caption', 'your-text-domain');
        $columns['ppp_youtube_caption'] = __('Youtube Description', 'your-text-domain');
        $columns['ppp_facebook_caption'] = __('Facebook Description', 'your-text-domain');
        $columns['ppp_twitter_caption'] = __('Twitter Status', 'your-text-domain');
        $columns['ppp_gmb_caption'] = __('GMB Summary', 'your-text-domain');
        $columns['ppp_slack_caption'] = __('Slack Text', 'your-text-domain');
        return $columns;
    }
   


    public function custom_column_content($column, $post_id) {
        if ($column == 'ppp_instagram_caption') { echo nl2br(get_field('ppp_instagram_caption', $post_id)); }
        if ($column == 'ppp_youtube_caption') { echo nl2br(get_field('ppp_youtube_description', $post_id)); }
        if ($column == 'ppp_facebook_caption') { echo nl2br(get_field('ppp_facebook_description', $post_id)); }
        if ($column == 'ppp_twitter_caption') { echo nl2br(get_field('ppp_twitter_status', $post_id)); }
        if ($column == 'ppp_gmb_caption') { echo nl2br(get_field('ppp_gmb_summary', $post_id)); }
        if ($column == 'ppp_slack_caption') { echo nl2br(get_field('ppp_slack_text', $post_id)); }
    }
    

    function custom_column_sortable($columns) {
        $columns['ppp_instagram_caption'] = 'ppp_instagram_caption';
        $columns['ppp_youtube_caption'] = 'ppp_youtube_caption';
        $columns['ppp_facebook_caption'] = 'ppp_facebook_caption';
        $columns['ppp_twitter_caption'] = 'ppp_twitter_caption';
        $columns['ppp_gmb_caption'] = 'ppp_gmb_caption';
        $columns['ppp_slack_caption'] = 'ppp_slack_caption';
        return $columns;
    }
    

    function custom_column_orderby($query) {
        if (!is_admin()) return;
    
        $orderby = $query->get('orderby');

        if ($orderby == 'ppp_instagram_caption') {
            $query->set('meta_key', 'ppp_instagram_caption');
            $query->set('orderby', 'meta_value');
        }
        if ($orderby == 'ppp_youtube_caption') {
            $query->set('meta_key', 'ppp_youtube_caption');
            $query->set('orderby', 'meta_value');
        }
        if ($orderby == 'ppp_facebook_caption') {
            $query->set('meta_key', 'ppp_facebook_caption');
            $query->set('orderby', 'meta_value');
        }
        if ($orderby == 'ppp_twitter_caption') {
            $query->set('meta_key', 'ppp_twitter_caption');
            $query->set('orderby', 'meta_value');
        }
        if ($orderby == 'ppp_gmb_caption') {
            $query->set('meta_key', 'ppp_gmb_caption');
            $query->set('orderby', 'meta_value');
        }
        if ($orderby == 'ppp_slack_caption') {
            $query->set('meta_key', 'ppp_slack_caption');
            $query->set('orderby', 'meta_value');
        }
    }
    
}
