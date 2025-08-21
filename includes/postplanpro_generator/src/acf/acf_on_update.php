<?php

namespace ppp_gen\acf;

use postplanpro\acf\acf_on_update_releases;

/**
 * When the update button is pressed, do this.
 */
class acf_on_update
{

    public $page_name = 'generator';
    public $post_id;
    public $template;

    public $wp_query_result;

    /**
     * Constructor to add the action hook.
     */
    public function __construct()
    {
        add_action( 'acf/save_post', [$this, 'update'], 20 );
    }

    /**
     * Update function called when the post is saved.
     *
     * @param int $post_id The ID of the post being saved.
     */
    public function update($post_id) 
    {
        $this->post_id = $post_id;

        # Check that this is the correct page
        $screen = get_current_screen();
        if ($screen->id !== $this->page_name) { return; }

        $this->get_template();

        $this->run_query();

        $this->generate_releases();

    }

    /**
     * Get the template fields for the current post.
     */
    private function get_template()
    {
        $this->template = get_fields( $this->post_id );
    }

    /**
     * Run the WP_Query based on the template arguments.
     */
    private function run_query()
    {

        $wp_query_args = $this->template['ppp_gen_wp_query_args'];

        $args = eval( "return $wp_query_args;" );

        try {
            $this->wp_query_result = new \WP_Query($args);
        } catch (\Exception $e) {
            error_log('Error: WP_Query failed. Probably malformed query args. '.$e->getMessage());
            return;
        }

    }

    /**
     * Generate releases based on the query results.
     */
    private function generate_releases()
    {

        if (!$this->wp_query_result->have_posts()){ return; }
        
        foreach ($this->wp_query_result->posts as $post) {
            $this->generate_release($post);
        }

    }

    /**
     * Generate a single release for a given post.
     *
     * @param WP_Post $post The post object.
     */
    private function generate_release($post)
    {
        $acf_values = get_fields( $post->ID );

        $image = get_the_post_thumbnail_url($post->ID, 'full');

        $taxonomies = get_object_taxonomies('tutorial', 'names');
        foreach ($taxonomies as $taxonomy_slug) {
            $taxonomy_terms = wp_get_post_terms($post->ID, $taxonomy_slug);
            if (!empty($taxonomy_terms) && !is_wp_error($taxonomy_terms)) {
                $terms[$taxonomy_slug] = wp_list_pluck($taxonomy_terms, 'name'); // Store only term names.
            } else {
                $terms[$taxonomy_slug] = []; // No terms for this taxonomy.
            }
        }

        $release = $this->template;

        foreach ($release as $release_key => $release_value)
        {
            // Handle arrays (extra_data)
            if (is_array($release_value)) { 
                foreach ($release_value as $sub_key => $sub_value)
                {
                    $release[$release_key][$sub_key] = $this->replace_moustaches($sub_value, $post, $acf_values, $terms, $image);
                }
                continue;
            }

            // Handle normal fields.
            $release[$release_key] = $this->replace_moustaches($release_value, $post, $acf_values, $terms, $image);
        }

        $this->create_release($release);
    }

    /**
     * Replace moustache tokens in a string with post or ACF values.
     *
     * @param string $string The string containing moustache tokens.
     * @param WP_Post $post The post object.
     * @param array $acf_values The ACF values for the post.
     * @return string The string with replaced values.
     */
    private function replace_moustaches($string, $post, $acf_values, $terms, $image)
    {
        return preg_replace_callback('/\{\{([a-zA-Z0-9_]+)\}\}/', function ($matches) use ($post, $acf_values, $terms, $image) {
            $key = $matches[1]; // Extract the token name (e.g., "post_title")
    
            // Check if the key exists in $post
            if (isset($post->$key)) {
                return $post->$key;
            }
    
            // Check if the key exists in $acf_values
            if (isset($acf_values[$key])) {
                return $acf_values[$key];
            }

            if (isset($terms[$key])) {
                return implode(', ', $terms[$key]);
            }

            if ($key == 'image') {
                // If DEV, Switch to live URL
                if (strpos($image, 'localhost:8443') !== false) {
                    $image = str_replace('localhost:8443', 'londonparkour.com', $image);
                }
                return $image;
            }
    
            // If not found, return empty string
            return '';
        }, $string);
    }

    /**
     * Create a release post with the given release data.
     *
     * @param array $release The release data.
     */
    private function create_release($release)
    {

        // Check if the release already exists
        if ($this->template["ppp_gen_duplicate_check"]){
            $existing_release = get_page_by_title($release['ppp_gen_release_title'], OBJECT, 'release');
            if ($existing_release){
                error_log('Error: Release already exists. '.$release['ppp_gen_release_title']);
                return;
            }
        }

        // Create the post
        $post_id = wp_insert_post([
            'post_title' => $release['ppp_gen_release_title'],
            'post_content' => $release['ppp_gen_release_content'],
            'post_status' => 'publish',
            'post_type' => 'release',
        ]);

        if (is_wp_error($post_id)) {
            error_log('Error: Failed to create release. '.$post_id->get_error_message());
            return;
        }

        // Update the post with the ACF values
        foreach ($release as $key => $value) {

            if ($key == 'ppp_gen_release_title' || $key == 'ppp_gen_release_content') { continue; }

            // handle repeaters
            if (is_array($value)) {

                $repeater_key = str_replace('gen_', '', $key);
                $repeater_array = [];
                foreach ($value as $index => $sub_values)
                {
                    foreach ($sub_values as $sub_key => $sub_value)
                    {
                        $new_key = str_replace('gen_', '', $sub_key);
                        $repeater_array[$index][$new_key] = $sub_value;
                    }
                }

                $returned = update_field($repeater_key, $repeater_array, $post_id);
                continue;   
            }

            $new_key = str_replace('gen_', '', $key);
            
            update_field($new_key, $value, $post_id);
        }

        // Run update on the new post
        $release_object = new acf_on_update_releases;
        $release_object->update($post_id);

    }

}
