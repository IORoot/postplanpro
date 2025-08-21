<?php

namespace postplanpro\lib;


class update_json
{

    public $fields;
    public $caption = '';

    public function __construct($fields)
    {
        if (!$fields){ return false; }
        $this->fields = $fields;
        $this->create_content();
        $this->update_content();
    }

    private function create_content() {
        
        if (!isset($this->fields["schedule"]["ppp_social_platforms"]) || !$this->fields["schedule"]["ppp_social_platforms"]) { 
            error_log('No social platforms found in schedule for JSON generation');
            return; 
        }

        
        foreach ($this->fields["schedule"]["ppp_social_platforms"] as $platform_name => $platform) {
            if (!isset($platform["ppp_social_platform_character_limit"]) || 
                !isset($platform["ppp_social_platform_template_header"]) || 
                !isset($platform["ppp_social_platform_template_footer"]) || 
                !isset($platform["ppp_social_platform_template_hashtags"])) {
                error_log('Missing required platform fields for platform: ' . $platform_name);
                continue;
            }

            // Populate the social content and limit the length
            $char_limit = (int) $platform["ppp_social_platform_character_limit"];
            $this->fields["schedule"]["ppp_social_platforms"][$platform_name]['ppp_social_platform_trimmed_content'] =  substr(
                    $platform["ppp_social_platform_template_header"] . PHP_EOL . PHP_EOL .
                    $this->fields["post"]->post_content  . PHP_EOL . PHP_EOL .
                    $platform["ppp_social_platform_template_footer"] . PHP_EOL . PHP_EOL .
                    $platform["ppp_social_platform_template_hashtags"]
                , 0, $char_limit);

            // Convert hashtags to array
            $this->fields["schedule"]["ppp_social_platforms"][$platform_name]['ppp_social_platform_template_hashtags'] = explode(' ', $platform["ppp_social_platform_template_hashtags"]);
        }

        // Clean and prepare data for JSON encoding
        $this->prepare_data_for_json($this->fields);

        $this->json = json_encode($this->fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
        
        // Check for JSON encoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON encoding error in update_json: ' . json_last_error_msg());
            // Fallback: try with more aggressive encoding options
            $this->json = json_encode($this->fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_PARTIAL_OUTPUT_ON_ERROR);
        }
        
        $this->json = $this->replace_placeholders($this->json);
    }

    /**
     * Recursively prepare data for JSON encoding by ensuring all strings are properly encoded
     */
    private function prepare_data_for_json(&$data) {
        if (is_array($data)) {
            foreach ($data as $key => &$value) {
                $this->prepare_data_for_json($value);
            }
        } elseif (is_object($data)) {
            foreach ($data as $key => &$value) {
                $this->prepare_data_for_json($value);
            }
        } elseif (is_string($data)) {
            // Ensure proper UTF-8 encoding and clean the string
            $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
            // Convert actual newlines and carriage returns to JSON-compatible \n characters
            $data = str_replace(["\r\n", "\r", "\n"], "\\n", $data);
            // Remove null bytes
            $data = str_replace("\0", '', $data);
            // Trim whitespace
            $data = trim($data);
        }
    }

    private function update_content() {
        if (!isset($this->json) || empty($this->json)) {
            error_log('No JSON content to update for post ' . $this->fields["post"]->ID);
            return;
        }
        update_field('ppp_json', $this->json, $this->fields["post"]->ID);
        error_log('Successfully updated ppp_json for post ' . $this->fields["post"]->ID);
    }



    /**
     * Following functions below are for moustache placeholders replacement
     */



    // Function to replace moustache placeholders with actual field values
    private function replace_placeholders($json) {
        // Use regex to match {{field_name}} patterns
        $pattern = '/\{\{(.*?)\}\}/';
    
        // Perform the replacement
        return preg_replace_callback($pattern, function($matches) {
            // $matches[1] contains the field name inside the moustache brackets
            $field_name = $matches[1];
    
            // Search for the field name in the nested array
            $value = $this->search_nested_array($this->fields, $field_name);
    
            // Return the found value or the original placeholder if not found
            return $value !== null ? $value : $matches[0];
        }, $json);
    }
    
    // Recursive function to search for a field in a multi-dimensional array
    private function search_nested_array($data, $field_name) {
        // If the data is an array, loop through its elements
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                // If the current key matches the field name, return the value
                if ($key === $field_name) {
                    return $value;
                }
                // If the value is an array, recursively search within it
                if (is_array($value) || is_object($value)) {
                    $result = $this->search_nested_array($value, $field_name);
                    if ($result !== null) {
                        return $result;
                    }
                }
            }
        }
        // If the data is an object, loop through its properties
        elseif (is_object($data)) {
            foreach ($data as $key => $value) {
                // If the current key matches the field name, return the value
                if ($key === $field_name) {
                    return $value;
                }
                // If the value is an array or object, recursively search within it
                if (is_array($value) || is_object($value)) {
                    $result = $this->search_nested_array($value, $field_name);
                    if ($result !== null) {
                        return $result;
                    }
                }
            }
        }
    
        // Return null if the field was not found
        return null;
    }

}
