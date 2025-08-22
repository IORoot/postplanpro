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

        // Add CSS and JavaScript for highlighting published rows
        add_action('admin_head', [$this,'add_custom_css']);
        add_action('admin_footer', [$this,'add_custom_js']);

        // Add AJAX handlers for webhook button
        add_action('wp_ajax_ppp_run_webhook', [$this, 'handle_run_webhook']);
        add_action('wp_ajax_nopriv_ppp_run_webhook', [$this, 'handle_run_webhook']);
    }


    public function add_custom_columns($columns) {
        // Insert the Time Until Publish column immediately after the Date column
        $reordered_columns = [];
        foreach ($columns as $key => $label) {
            $reordered_columns[$key] = $label;
            if ($key === 'date') {
                $reordered_columns['ppp_time_until_publish'] = __('Time Until Publish', 'your-text-domain');
            }
        }

        // Append existing custom columns
        // $reordered_columns['ppp_social_platforms'] = __('Social Platforms', 'your-text-domain');
        $reordered_columns['ppp_youtube_id'] = __('YouTube Link', 'your-text-domain');
        $reordered_columns['ppp_youtube_thumbnail'] = __('YouTube Thumbnail', 'your-text-domain');
        // $reordered_columns['ppp_award_level'] = __('Award Level', 'your-text-domain');
        $reordered_columns['ppp_run_webhook'] = __('Run', 'your-text-domain');
        return $reordered_columns;
    }
   


    public function custom_column_content($column, $post_id) {

        $ppp_json = get_field('ppp_json', $post_id);
        $ppp_json = json_decode($ppp_json, true);
            
        if ($column == 'ppp_social_platforms') { 

            if (!$ppp_json["schedule"]["ppp_social_platforms"]) { 
                return; 
            }

            foreach ($ppp_json["schedule"]["ppp_social_platforms"] as $platform_name => $platform) {
                $platforms[] = $platform_name;
            }
            echo implode(", ", $platforms);
        }


        if ($column == 'ppp_youtube_id') {
            foreach ($ppp_json["extra_data"] as $extra_data_key => $extra_data_value) {
                if ($extra_data_key !== "youtube_id") { continue; }
                echo '<a href="https://www.youtube.com/watch?v='.$extra_data_value .'" target="_blank" style="text-decoration:underline">'.$extra_data_value.'</a>';  
            }
        }


        if ($column == 'ppp_youtube_thumbnail') {
            foreach ($ppp_json["extra_data"] as $extra_data_key => $extra_data_value) {
                if ($extra_data_key !== "youtube_id") { continue; }
                echo '<a href="https://www.youtube.com/watch?v='.$extra_data_value .'" target="_blank" style="text-decoration:underline">'; 
                    echo '<img src="https://i.ytimg.com/vi/'.$extra_data_value.'/hqdefault.jpg" style="width: 160px;">';
                echo '</a>';
            }   
        }

        if ($column == 'ppp_time_until_publish') {
            // Use GMT timestamps to avoid timezone drift
            $post_time_gmt = get_post_time('U', true, $post_id);
            $now_gmt = current_time('timestamp', true);

            if (!$post_time_gmt) { echo '—'; return; }

            if ($post_time_gmt > $now_gmt) {
                echo 'in ' . human_time_diff($now_gmt, $post_time_gmt);
            } else {
                echo human_time_diff($post_time_gmt, $now_gmt) . ' ago';
            }
        }

        if ($column == 'ppp_award_level') {
            foreach ($ppp_json["extra_data"] as $extra_data_key => $extra_data_value) {
                if ($extra_data_key == "taxonomies") { $tax = $ppp_json["extra_data"]["taxonomies"]; }
                if ($extra_data_key == "award_level") { $level = $ppp_json["extra_data"]["award_level"]; }
            }
            echo $tax . ' - ' . $level;
        }

        if ($column == 'ppp_run_webhook') {
            $nonce = wp_create_nonce('ppp_run_webhook_' . $post_id);
            echo '<button class="ppp-run-webhook-btn button button-primary" data-post-id="' . $post_id . '" data-nonce="' . $nonce . '">';
            echo '<svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">';
            echo '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path>';
            echo '</svg>';
            echo 'Run';
            echo '</button>';
            echo '<span class="ppp-webhook-status" id="webhook-status-' . $post_id . '"></span>';
        }
    }

    /**
     * Handle AJAX request to run webhook
     */
    public function handle_run_webhook() {
        // Check nonce for security
        $post_id = intval($_POST['post_id']);
        $nonce = $_POST['nonce'];
        
        if (!wp_verify_nonce($nonce, 'ppp_run_webhook_' . $post_id)) {
            wp_die('Security check failed');
        }

        // Check if user has permission
        if (!current_user_can('edit_post', $post_id)) {
            wp_die('Permission denied');
        }

        // Get the post
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'release') {
            wp_die('Invalid post');
        }

        try {
            // Send the webhook
            $webhook = new \postplanpro\lib\send_webhook($post, 'Manual');
            
            // Check if webhook was sent successfully
            if (isset($webhook->response)) {
                if (is_wp_error($webhook->response)) {
                    wp_send_json_error('Webhook error: ' . $webhook->response->get_error_message());
                } else {
                    $response_code = wp_remote_retrieve_response_code($webhook->response);
                    if ($response_code >= 200 && $response_code < 300) {
                        wp_send_json_success('Webhook sent successfully! (Response: ' . $response_code . ')');
                    } else {
                        wp_send_json_error('Webhook sent but received response code: ' . $response_code);
                    }
                }
            } else {
                wp_send_json_error('No webhook response received');
            }
        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }

    function custom_column_sortable($columns) {
        $columns['ppp_award_level'] = 'ppp_award_level';
        return $columns;
    }
    

    function custom_column_orderby($query) {
        if (!is_admin() || !$query->is_main_query()) return;

        // Ensure the Releases list defaults to sorting by date (DESC) instead of menu_order/title
        $post_type = $query->get('post_type') ?: (isset($_GET['post_type']) ? $_GET['post_type'] : null);
        if ($post_type === 'release' && empty($_GET['orderby']) && !$query->get('orderby')) {
            $query->set('orderby', 'date');
            $query->set('order', 'DESC');
        }

        $orderby = $query->get('orderby');

        if ($orderby == 'ppp_award_level') {
            // Use a custom approach to sort by award level
            add_filter('posts_join', function($join) {
                global $wpdb;
                $join .= " LEFT JOIN {$wpdb->postmeta} ppp_award_meta ON ({$wpdb->posts}.ID = ppp_award_meta.post_id AND ppp_award_meta.meta_key = 'ppp_json')";
                return $join;
            });
            
            add_filter('posts_orderby', function($orderby_clause) use ($query) {
                global $wpdb;
                
                // Extract taxonomies and award level for proper sorting
                // Sort by taxonomies first, then by award level within each taxonomy
                $taxonomies_extract = "JSON_UNQUOTE(JSON_EXTRACT(ppp_award_meta.meta_value, '$.extra_data.taxonomies'))";
                $award_level_extract = "JSON_UNQUOTE(JSON_EXTRACT(ppp_award_meta.meta_value, '$.extra_data.award_level'))";
                
                $orderby_clause = "CAST({$taxonomies_extract} AS CHAR) " . ($query->get('order') === 'DESC' ? 'DESC' : 'ASC') . ", 
                                  CAST({$award_level_extract} AS UNSIGNED) " . ($query->get('order') === 'DESC' ? 'DESC' : 'ASC');
                
                return $orderby_clause;
            });
        }
    }
     
    /**
     * Add CSS for highlighting published rows and webhook button styling
     */
    public function add_custom_css() {
        ?>
        <style>
            .ppp-published-row {
                background-color: #e8f5e9 !important; /* Light green background */
            }
            .ppp-published-row td {
                background-color: #e8f5e9 !important;
            }
            .ppp-published-row.alt {
                background-color: #e8f5e9 !important;
            }
            .ppp-published-row.alt td {
                background-color: #e8f5e9 !important;
            }
            
            /* Webhook button styling */
            .ppp-run-webhook-btn {
                display: inline-flex !important;
                align-items: center;
                padding: 6px 12px !important;
                font-size: 12px !important;
                line-height: 1.4 !important;
                border-radius: 4px !important;
                transition: all 0.2s ease !important;
                background: linear-gradient(135deg, #0073aa, #005a87) !important;
                border: 1px solid #005a87 !important;
                color: white !important;
                text-decoration: none !important;
                cursor: pointer !important;
            }
            
            .ppp-run-webhook-btn:hover {
                background: linear-gradient(135deg, #005a87, #004a73) !important;
                border-color: #004a73 !important;
                transform: translateY(-1px);
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .ppp-run-webhook-btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
                transform: none;
                box-shadow: none;
            }
            
            .ppp-run-webhook-btn svg {
                width: 14px;
                height: 14px;
                margin-right: 4px;
            }
            
            .ppp-webhook-status {
                display: block;
                margin-top: 4px;
                font-size: 11px;
                font-weight: 500;
            }
        </style>
        <?php
    }

    /**
     * Add JavaScript for highlighting published rows and webhook button functionality
     */
    public function add_custom_js() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Highlight published rows
            $('tr').each(function() {
                var $row = $(this);
                var isPublished = false;
                
                // Check multiple possible status selectors
                var $statusCell = $row.find('.column-status, .post-state, .column-post_status, .post-status, .status');
                if ($statusCell.length > 0) {
                    var statusText = $statusCell.text().toLowerCase().trim();
                    if (statusText.includes('published') || statusText.includes('publish')) {
                        isPublished = true;
                    }
                }
                
                // Check for the post status in the row data or attributes
                if (!isPublished) {
                    var postStatus = $row.attr('data-status') || $row.find('[data-status]').attr('data-status');
                    if (postStatus && (postStatus.toLowerCase() === 'publish' || postStatus.toLowerCase() === 'published')) {
                        isPublished = true;
                    }
                }
                
                // Check for the actual post status in the row
                if (!isPublished) {
                    var $titleLink = $row.find('.row-title');
                    if ($titleLink.length > 0) {
                        var href = $titleLink.attr('href');
                        if (href && href.includes('post_status=publish')) {
                            isPublished = true;
                        }
                    }
                }
                
                // Check for the post status in the row's class or data attributes
                if (!isPublished) {
                    var rowClasses = $row.attr('class') || '';
                    if (rowClasses.includes('status-publish') || rowClasses.includes('publish')) {
                        isPublished = true;
                    }
                }
                
                // Check for the post status in the row's ID or data attributes
                if (!isPublished) {
                    var rowId = $row.attr('id') || '';
                    if (rowId.includes('post-')) {
                        // Try to get the post status from the row's data
                        var $postStatusElement = $row.find('[data-post-status], .post-status, .status');
                        if ($postStatusElement.length > 0) {
                            var status = $postStatusElement.text().toLowerCase().trim();
                            if (status === 'publish' || status === 'published') {
                                isPublished = true;
                            }
                        }
                    }
                }
                
                if (isPublished) {
                    $row.addClass('ppp-published-row');
                }
            });

            // Handle webhook button clicks
            $('.ppp-run-webhook-btn').on('click', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var postId = $button.data('post-id');
                var nonce = $button.data('nonce');
                var $status = $('#webhook-status-' + postId);
                
                // Disable button and show loading state
                $button.prop('disabled', true).text('Running...');
                $status.html('<span style="color: #0073aa;">Sending webhook...</span>');
                
                // Send AJAX request
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ppp_run_webhook',
                        post_id: postId,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.html('<span style="color: #46b450;">✓ ' + response.data + '</span>');
                        } else {
                            $status.html('<span style="color: #dc3232;">✗ ' + response.data + '</span>');
                        }
                    },
                    error: function(xhr, status, error) {
                        $status.html('<span style="color: #dc3232;">✗ Error: ' + error + '</span>');
                    },
                    complete: function() {
                        // Re-enable button and restore original text
                        $button.prop('disabled', false).html('<svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>Run');
                        
                        // Clear status after 5 seconds
                        setTimeout(function() {
                            $status.html('');
                        }, 5000);
                    }
                });
            });
        });
        </script>
        <?php
    }
}

