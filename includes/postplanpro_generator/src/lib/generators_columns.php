<?php

namespace ppp_gen\lib;

class generators_columns
{
    public function __construct()
    {
        // Hook into the columns filter to add a custom column
        add_filter('manage_generator_posts_columns', [$this, 'add_custom_columns']);

        // Hook into the action to populate the custom column with data
        add_action('manage_generator_posts_custom_column', [$this, 'custom_column_content'], 10, 2);

        // Add AJAX handlers for duplicate button
        add_action('wp_ajax_ppp_gen_duplicate', [$this, 'handle_duplicate_generator']);
        add_action('wp_ajax_nopriv_ppp_gen_duplicate', [$this, 'handle_duplicate_generator']);

        // Add CSS and JavaScript for the duplicate button
        add_action('admin_head', [$this, 'add_custom_css']);
        add_action('admin_footer', [$this, 'add_custom_js']);
    }

    public function add_custom_columns($columns) {
        // Add the duplicate column at the end
        $columns['ppp_gen_duplicate'] = __('Duplicate', 'your-text-domain');
        return $columns;
    }

    public function custom_column_content($column, $post_id) {
        if ($column == 'ppp_gen_duplicate') {
            $nonce = wp_create_nonce('ppp_gen_duplicate_' . $post_id);
            echo '<button class="ppp-gen-duplicate-btn button button-secondary" data-post-id="' . $post_id . '" data-nonce="' . $nonce . '">';
            echo '<svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">';
            echo '<path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"></path>';
            echo '<path d="M3 7a1 1 0 011-1h2a1 1 0 110 2H4a1 1 0 01-1-1z"></path>';
            echo '<path d="M3 11a1 1 0 011-1h2a1 1 0 110 2H4a1 1 0 01-1-1z"></path>';
            echo '<path d="M8 15a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"></path>';
            echo '<path d="M13 7a1 1 0 011-1h2a1 1 0 110 2h-2a1 1 0 01-1-1z"></path>';
            echo '<path d="M13 11a1 1 0 011-1h2a1 1 0 110 2h-2a1 1 0 01-1-1z"></path>';
            echo '<path d="M13 15a1 1 0 011-1h2a1 1 0 110 2h-2a1 1 0 01-1-1z"></path>';
            echo '</svg>';
            echo 'Duplicate';
            echo '</button>';
            echo '<span class="ppp-gen-duplicate-status" id="duplicate-status-' . $post_id . '"></span>';
        }
    }

    /**
     * Handle AJAX request to duplicate generator
     */
    public function handle_duplicate_generator() {
        // Check nonce for security
        $post_id = intval($_POST['post_id']);
        $nonce = $_POST['nonce'];
        
        if (!wp_verify_nonce($nonce, 'ppp_gen_duplicate_' . $post_id)) {
            wp_die('Security check failed');
        }

        // Check if user has permission
        if (!current_user_can('edit_post', $post_id)) {
            wp_die('Permission denied');
        }

        // Get the original post
        $original_post = get_post($post_id);
        if (!$original_post || $original_post->post_type !== 'generator') {
            wp_die('Invalid post');
        }

        try {
            // Create the duplicate post
            $duplicate_post_id = wp_insert_post(array(
                'post_title' => $original_post->post_title . ' (Copy)',
                'post_content' => $original_post->post_content,
                'post_status' => 'draft',
                'post_type' => 'generator',
                'post_parent' => $original_post->post_parent,
                'menu_order' => $original_post->menu_order
            ));

            if (is_wp_error($duplicate_post_id)) {
                wp_send_json_error('Failed to create duplicate: ' . $duplicate_post_id->get_error_message());
            }

            // Copy ACF fields
            $this->duplicate_acf_fields($post_id, $duplicate_post_id);

            // Copy post meta (excluding ACF fields which are handled above)
            $this->duplicate_post_meta($post_id, $duplicate_post_id);

            wp_send_json_success(array(
                'message' => 'Generator duplicated successfully!',
                'duplicate_id' => $duplicate_post_id,
                'edit_url' => get_edit_post_link($duplicate_post_id, 'raw')
            ));

        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate ACF fields from original to duplicate post
     */
    private function duplicate_acf_fields($original_id, $duplicate_id) {
        $fields = get_fields($original_id);
        if ($fields) {
            foreach ($fields as $field_name => $field_value) {
                update_field($field_name, $field_value, $duplicate_id);
            }
        }
    }

    /**
     * Duplicate post meta from original to duplicate post
     */
    private function duplicate_post_meta($original_id, $duplicate_id) {
        $meta_keys = get_post_custom_keys($original_id);
        if ($meta_keys) {
            foreach ($meta_keys as $meta_key) {
                // Skip ACF fields as they're handled separately
                if (strpos($meta_key, '_') === 0) {
                    continue;
                }
                
                $meta_values = get_post_meta($original_id, $meta_key, false);
                foreach ($meta_values as $meta_value) {
                    add_post_meta($duplicate_id, $meta_key, $meta_value);
                }
            }
        }
    }

    /**
     * Add CSS for duplicate button styling
     */
    public function add_custom_css() {
        ?>
        <style>
            .ppp-gen-duplicate-btn {
                display: inline-flex !important;
                align-items: center;
                padding: 6px 12px !important;
                font-size: 12px !important;
                line-height: 1.4 !important;
                border-radius: 4px !important;
                transition: all 0.2s ease !important;
                background: linear-gradient(135deg, #6c757d, #5a6268) !important;
                border: 1px solid #5a6268 !important;
                color: white !important;
                text-decoration: none !important;
                cursor: pointer !important;
            }
            
            .ppp-gen-duplicate-btn:hover {
                background: linear-gradient(135deg, #5a6268, #495057) !important;
                border-color: #495057 !important;
                transform: translateY(-1px);
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .ppp-gen-duplicate-btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
                transform: none;
                box-shadow: none;
            }
            
            .ppp-gen-duplicate-btn svg {
                width: 14px;
                height: 14px;
                margin-right: 4px;
            }
            
            .ppp-gen-duplicate-status {
                display: block;
                margin-top: 4px;
                font-size: 11px;
                font-weight: 500;
            }
        </style>
        <?php
    }

    /**
     * Add JavaScript for duplicate button functionality
     */
    public function add_custom_js() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Handle duplicate button clicks
            $('.ppp-gen-duplicate-btn').on('click', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var postId = $button.data('post-id');
                var nonce = $button.data('nonce');
                var $status = $('#duplicate-status-' + postId);
                
                // Disable button and show loading state
                $button.prop('disabled', true).text('Duplicating...');
                $status.html('<span style="color: #6c757d;">Creating duplicate...</span>');
                
                // Send AJAX request
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ppp_gen_duplicate',
                        post_id: postId,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.html('<span style="color: #28a745;">✓ ' + response.data.message + '</span>');
                            
                            // Add a link to edit the duplicate
                            if (response.data.edit_url) {
                                $status.append('<br><a href="' + response.data.edit_url + '" style="color: #007cba; text-decoration: underline;">Edit Duplicate</a>');
                            }
                        } else {
                            $status.html('<span style="color: #dc3545;">✗ ' + response.data + '</span>');
                        }
                    },
                    error: function(xhr, status, error) {
                        $status.html('<span style="color: #dc3545;">✗ Error: ' + error + '</span>');
                    },
                    complete: function() {
                        // Re-enable button and restore original text
                        $button.prop('disabled', false).html('<svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"></path><path d="M3 7a1 1 0 011-1h2a1 1 0 110 2H4a1 1 0 01-1-1z"></path><path d="M3 11a1 1 0 011-1h2a1 1 0 110 2H4a1 1 0 01-1-1z"></path><path d="M8 15a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"></path><path d="M13 7a1 1 0 011-1h2a1 1 0 110 2h-2a1 1 0 01-1-1z"></path><path d="M13 11a1 1 0 011-1h2a1 1 0 110 2h-2a1 1 0 01-1-1z"></path><path d="M13 15a1 1 0 011-1h2a1 1 0 110 2h-2a1 1 0 01-1-1z"></path></svg>Duplicate');
                        
                        // Clear status after 10 seconds
                        setTimeout(function() {
                            $status.html('');
                        }, 10000);
                    }
                });
            });
        });
        </script>
        <?php
    }
}
