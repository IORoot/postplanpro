<?php

namespace postplanpro\lib;


class bulk_actions
{


    public function __construct()
    {
        // Add bulk actions for release post type
        add_filter('bulk_actions-edit-release', [$this, 'register_bulk_actions']);
        add_filter('handle_bulk_actions-edit-release', [$this, 'handle_bulk_actions'], 10, 3);
        add_action('admin_notices', [$this, 'bulk_action_admin_notice']);
        add_action('admin_footer', [$this, 'add_bulk_action_form_fields']);
    }


    /**
     * Register custom bulk actions
     */
    public function register_bulk_actions($bulk_actions) {
        $bulk_actions['change_send_target'] = __('Change Send Target', 'postplanpro');
        $bulk_actions['change_release_schedule'] = __('Change Release Schedule', 'postplanpro');
        return $bulk_actions;
    }

    /**
     * Handle the bulk action
     */
    public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if ($doaction === 'change_send_target') {
            return $this->handle_send_target_bulk_action($redirect_to, $post_ids);
        } elseif ($doaction === 'change_release_schedule') {
            return $this->handle_release_schedule_bulk_action($redirect_to, $post_ids);
        }
        
        return $redirect_to;
    }

    /**
     * Handle the send target bulk action
     */
    private function handle_send_target_bulk_action($redirect_to, $post_ids) {
        // Check if we have the new send target value (check both POST and REQUEST)
        $send_target_value = '';
        if (isset($_POST['ppp_send_target_value']) && !empty($_POST['ppp_send_target_value'])) {
            $send_target_value = $_POST['ppp_send_target_value'];
        } elseif (isset($_REQUEST['ppp_send_target_value']) && !empty($_REQUEST['ppp_send_target_value'])) {
            $send_target_value = $_REQUEST['ppp_send_target_value'];
        }

        if (empty($send_target_value)) {
            return $redirect_to;
        }

        $new_send_target_url = sanitize_text_field($send_target_value);
        $updated_count = 0;

        // Get the target name for the URL
        $target_name = '';
        $ppp_targets = get_field('ppp_targets', 'options');
        if ($ppp_targets && is_array($ppp_targets)) {
            foreach ($ppp_targets as $target) {
                if ($target['ppp_target_url'] === $new_send_target_url) {
                    $target_name = $target['ppp_target_name'];
                    break;
                }
            }
        }

        // Create the array format that ACF expects for return_format => array
        $new_send_target_array = array(
            'value' => $new_send_target_url,
            'label' => $target_name
        );

        foreach ($post_ids as $post_id) {
            // Update the ACF field using the field key (most reliable method)
            $result = update_field('field_677be2ba05476', $new_send_target_array, $post_id);
            
            if ($result) {
                $updated_count++;
            }
        }

        // Store the count for the admin notice
        set_transient('bulk_action_send_target_updated', $updated_count, 30);

        return $redirect_to;
    }

    /**
     * Handle the release schedule bulk action
     */
    private function handle_release_schedule_bulk_action($redirect_to, $post_ids) {
        // Check if we have the new release schedule value (check both POST and REQUEST)
        $schedule_value = '';
        if (isset($_POST['ppp_release_schedule_value']) && !empty($_POST['ppp_release_schedule_value'])) {
            $schedule_value = $_POST['ppp_release_schedule_value'];
        } elseif (isset($_REQUEST['ppp_release_schedule_value']) && !empty($_REQUEST['ppp_release_schedule_value'])) {
            $schedule_value = $_REQUEST['ppp_release_schedule_value'];
        }

        if (empty($schedule_value)) {
            return $redirect_to;
        }

        $new_schedule = sanitize_text_field($schedule_value);
        $updated_count = 0;

        foreach ($post_ids as $post_id) {
            // Update the ACF field using the field key
            $result = update_field('field_6649f9d6ef9fe', $new_schedule, $post_id);
            
            if ($result) {
                $updated_count++;
            }
        }

        // Store the count for the admin notice
        set_transient('bulk_action_release_schedule_updated', $updated_count, 30);

        return $redirect_to;
    }

    /**
     * Display admin notice after bulk action
     */
    public function bulk_action_admin_notice() {
        if (!isset($_REQUEST['post_type']) || $_REQUEST['post_type'] !== 'release') {
            return;
        }

        // Check for send target updates
        $send_target_updated_count = get_transient('bulk_action_send_target_updated');
        if ($send_target_updated_count !== false) {
            delete_transient('bulk_action_send_target_updated');
            $message = sprintf(
                _n(
                    '%d release updated with new send target.',
                    '%d releases updated with new send target.',
                    $send_target_updated_count,
                    'postplanpro'
                ),
                $send_target_updated_count
            );
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }

        // Check for release schedule updates
        $schedule_updated_count = get_transient('bulk_action_release_schedule_updated');
        if ($schedule_updated_count !== false) {
            delete_transient('bulk_action_release_schedule_updated');
            $message = sprintf(
                _n(
                    '%d release updated with new schedule.',
                    '%d releases updated with new schedule.',
                    $schedule_updated_count,
                    'postplanpro'
                ),
                $schedule_updated_count
            );
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }
    }

    /**
     * Add custom bulk action form fields
     */
    public function add_bulk_action_form_fields() {
        global $post_type;
        
        if ($post_type !== 'release') {
            return;
        }

        // Get the available targets from ACF options
        $ppp_targets = get_field('ppp_targets', 'options');
        $target_options = '';
        
        if ($ppp_targets && is_array($ppp_targets)) {
            foreach ($ppp_targets as $target) {
                $target_name = esc_attr($target['ppp_target_name']);
                $target_url = esc_attr($target['ppp_target_url']);
                $target_options .= '<option value="' . $target_url . '">' . $target_name . '</option>';
            }
        }

        // Get the available schedules
        $schedule_options = '';
        $ppp_schedules = new \WP_Query([
            'post_type'      => 'schedule',
            'post_status'    => 'publish', 
            'posts_per_page' => -1,       
        ]);

        if ($ppp_schedules->have_posts()) {
            foreach ($ppp_schedules->posts as $post) {
                $acf_fields = get_fields($post->ID);
                $schedule_name = $acf_fields["ppp_schedule_name"];
                if ($schedule_name) {
                    $schedule_options .= '<option value="' . esc_attr($schedule_name) . '">' . esc_attr($schedule_name) . '</option>';
                }
            }
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Add form fields when bulk action is selected
            $('select[name="action"]').on('change', function() {
                var selectedAction = $(this).val();
                
                // Remove existing forms
                $('.bulk-send-target-form, .bulk-release-schedule-form').remove();
                
                if (selectedAction === 'change_send_target') {
                    // Add send target form fields
                    var formHtml = '<div class="bulk-send-target-form" style="margin: 10px 0; padding: 10px; background: #f9f9f9; border: 1px solid #ddd;">';
                    formHtml += '<label for="ppp_send_target_value"><strong>New Send Target:</strong></label> ';
                    formHtml += '<select name="ppp_send_target_value" id="ppp_send_target_value" style="margin-left: 10px;" required>';
                    formHtml += '<option value="">Select Send Target</option>';
                    formHtml += '<?php echo $target_options; ?>';
                    formHtml += '</select>';
                    formHtml += '</div>';
                    
                    // Add the form field directly to the posts-filter form
                    $('#posts-filter').prepend(formHtml);
                } else if (selectedAction === 'change_release_schedule') {
                    // Add release schedule form fields
                    var formHtml = '<div class="bulk-release-schedule-form" style="margin: 10px 0; padding: 10px; background: #f9f9f9; border: 1px solid #ddd;">';
                    formHtml += '<label for="ppp_release_schedule_value"><strong>New Release Schedule:</strong></label> ';
                    formHtml += '<select name="ppp_release_schedule_value" id="ppp_release_schedule_value" style="margin-left: 10px;" required>';
                    formHtml += '<option value="">Select Release Schedule</option>';
                    formHtml += '<?php echo $schedule_options; ?>';
                    formHtml += '</select>';
                    formHtml += '</div>';
                    
                    // Add the form field directly to the posts-filter form
                    $('#posts-filter').prepend(formHtml);
                }
            });
            
            // Validate form submission
            $('form#posts-filter').on('submit', function(e) {
                var selectedAction = $('select[name="action"]').val();
                if (selectedAction === 'change_send_target') {
                    var targetValue = $('#ppp_send_target_value').val();
                    if (!targetValue) {
                        alert('Please select a send target.');
                        e.preventDefault();
                        return false;
                    }
                } else if (selectedAction === 'change_release_schedule') {
                    var scheduleValue = $('#ppp_release_schedule_value').val();
                    if (!scheduleValue) {
                        alert('Please select a release schedule.');
                        e.preventDefault();
                        return false;
                    }
                }
            });
        });
        </script>
        <?php
    }
}

