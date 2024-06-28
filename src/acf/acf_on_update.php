<?php

namespace videoconstructor\acf;

class acf_on_update
{




    public function __construct(){
        add_action( 'acf/save_post', [$this, 'update'], 20 );
    }



    public function update() {

        # Check that this is the correct page
        $screen = get_current_screen();
        if ($screen->id !== "toplevel_page_videoconstructor") { return; }

        $this->get_fields();
        $this->iterate_instances();
    }



    private function get_fields()
    {
        $this->fields = get_field('videoconstructor_instance', 'option');
    }



    private function iterate_instances()
    {
        foreach ($this->fields as $this->loop_instance_index => $this->loop_instance)
        {
            $this->iterate_fields();
        }
    }



    private function iterate_fields()
    {
        foreach ($this->loop_instance as $this->loop_field_key => $this->loop_field_value)
        {
            $this->generate_json();
        }
        $this->update_json_field();
    }



    private function generate_json()
    {
        $this->remove_vc();
        $this->get_category_and_key();
        $this->get_value();
    
        if ($this->current_category == "search" && $this->loop_instance["vc_search_run"] == false){
            return;
        }
        if ($this->current_category == "download" && $this->loop_instance["vc_download_run"] == false){
            return;
        }
        if ($this->current_category == "overlay" && $this->loop_instance["vc_overlay_run"] == false){
            return;
        }
        if ($this->current_category == "video" && $this->loop_instance["vc_video_run"] == false){
            return;
        }
        if ($this->current_category == "output" && $this->loop_instance["vc_output_run"] == false){
            return;
        }

        $this->set_entry();
    }



    private function remove_vc()
    {
        $this->current_field_value = substr($this->loop_field_key, 3);
    }



    private function get_category_and_key()
    {
        list($this->current_category, $this->current_key) = explode('_', $this->current_field_value, 2); 
    }



    private function get_value()
    {
        if (is_array($this->loop_field_value)){

            if ($this->current_key == "inputs"){
                $this->handle_overlay_inputs();
            }

            if ($this->current_key == "config"){
                $this->handle_video_inputs();
            }

            if ($this->current_key == "outputs"){
                $this->handle_outputs();
            }

            if ($this->current_key == "searches"){
                $this->handle_searches();
            }

            if ($this->current_key == "downloads"){
                $this->handle_downloads();
            }

        }
        $this->current_value = $this->loop_field_value;
    }



    private function set_entry()
    {
        if ($this->current_value == ""){
            return;
        }

        # If there is no key, set the category to the value.
        if (!$this->current_key){
            $this->new_array[$this->current_category] = $this->current_value;
            return;
        }

        $this->current_key = str_replace('-', '_', $this->current_key);

        $this->new_array[$this->current_category][$this->current_key] = $this->current_value;

    }


    private function update_json_field()
    {
        # remove JSON from the JSON.
        unset($this->new_array['json']);

        $this->json = json_encode($this->new_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->b64 = base64_encode($this->json);

        $this->fields[$this->loop_instance_index]['vc_json_group']['vc_json_json'] = $this->json;
        $this->fields[$this->loop_instance_index]['vc_json_group']['vc_json_base64'] = $this->b64;

        update_field('videoconstructor_instance', $this->fields, 'option');
        
    }



    // ╭───────────────────────────────────────────────────────────────────────────╮
    // │                         Handle any OVERLAY inputs                         │
    // ╰───────────────────────────────────────────────────────────────────────────╯

    private function handle_overlay_inputs()
    {
        $this->loop_field_value = $this->removePrefixFromKeys($this->loop_field_value, "vc_overlay_inputs_");

        # First loop - relabel array keys
        foreach( $this->loop_field_value as $loop_index => $loop_value)
        {
            $classname = $this->loop_field_value[$loop_index]['class'];
            $this->loop_field_value[$classname] = $loop_value;
            unset($this->loop_field_value[$loop_index]);

            $this->loop_field_value[$classname]['style'] = $this->overlay_styles($this->loop_field_value[$classname]['style']);
        }
    }



    private function removePrefixFromKeys($array, $prefix = "vc_") {
        $modifiedArray = [];
        foreach ($array as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $newKey = substr($key, strlen($prefix));
            } else {
                $newKey = $key;
            }
            if (is_array($value)) {
                $modifiedArray[$newKey] = $this->removePrefixFromKeys($value, $prefix);
            } else {
                $modifiedArray[$newKey] = $value;
            }
        }
        return $modifiedArray;
    }



    private function overlay_styles($array)
    {
        $modifiedArray = [];
        
        foreach ($array as $key => $value) {
            $modifiedArray[$array[$key]['style_key']] = $array[$key]['style_value'];
        }

        return $modifiedArray;
    }



    // ╭───────────────────────────────────────────────────────────────────────────╮
    // │                          Handle any VIDEO inputs                          │
    // ╰───────────────────────────────────────────────────────────────────────────╯

    private function handle_video_inputs()
    {

        $this->loop_field_value = $this->add_suffix_to_duplicates($this->loop_field_value);

        foreach ($this->loop_field_value as $this->loop_config_key => $this->loop_config_value)
        {
            $ffmpeg_script_name = $this->loop_config_value['acf_fc_layout'];
            $ffmpeg_script_name_no_numbers = preg_replace('/\d+$/', '', $this->loop_config_value['acf_fc_layout']);
            $ffmpeg_script_config = $this->loop_config_value[$ffmpeg_script_name_no_numbers];
            $ffmpeg_script_config = $this->removePrefixFromKeys($ffmpeg_script_config, $ffmpeg_script_name_no_numbers."_");

            # Change the inputs array format
            if (array_key_exists('inputs', $ffmpeg_script_config)){
                $new_inputs = $this->fix_ffmpeg_inputs($ffmpeg_script_config['inputs']);
                unset($ffmpeg_script_config['inputs']);
                $ffmpeg_script_config = array_merge($ffmpeg_script_config, $new_inputs);
            }

            # remove empty keys / values
            $ffmpeg_script_config = $this->removeEmpty($ffmpeg_script_config);

            # Set back the correct values
            $this->loop_field_value[$ffmpeg_script_name_no_numbers] = $ffmpeg_script_config;
            unset($this->loop_field_value[$this->loop_config_key]);

        }

    }



    private function fix_ffmpeg_inputs($inputs_array)
    {
        $new_array = [];
        foreach ($inputs_array as $input_key => $filename)
        {
            $new_array["input".$input_key] = $filename['input'];
        }
        return $new_array;
    }



    private function removeEmpty($config)
    {
        foreach ($config as $config_key => $config_value)
        {
            if (!$config_key){
                unset($config[$config_key]);
            }
            if (!$config_value){
                unset($config[$config_key]);
            }
        } 
        return $config;
    }



    private function add_suffix_to_duplicates($array) {
        $counts = [];
        
        foreach ($array as $key => $value) {

            $ffmpeg_script_name = $value['acf_fc_layout'];

            if ( isset($counts[$ffmpeg_script_name]) ) {
                $counts[$ffmpeg_script_name]++;
                $array[$key]['acf_fc_layout'] = $ffmpeg_script_name.$counts[$ffmpeg_script_name];
            } 
            
            if ( !isset($counts[$ffmpeg_script_name]) ) {
                $counts[$ffmpeg_script_name] = 1;
            }

        }
        
        return $array;
    }



    // ╭───────────────────────────────────────────────────────────────────────────╮
    // │                         Handle the output arrays                          │
    // ╰───────────────────────────────────────────────────────────────────────────╯
    private function handle_outputs()
    {  
        $this->loop_field_value = $this->add_suffix_to_duplicates($this->loop_field_value);

        foreach ($this->loop_field_value as $this->loop_output_key => $this->loop_output_value)
        {
            $output_script_name = $this->loop_output_value['acf_fc_layout'];
            $output_script_name_no_prefix = str_replace('vc_output_','',$output_script_name);
            $output_script_name_no_numbers = preg_replace('/\d+$/', '', $output_script_name);
            $output_script_config = $this->loop_output_value[$output_script_name_no_numbers];
            $output_script_config = $this->removePrefixFromKeys($output_script_config, $output_script_name_no_numbers."_");

            # set new value
            $this->loop_field_value[$output_script_name_no_prefix] = $output_script_config;

            # remove old one
            unset($this->loop_field_value[$this->loop_output_key]);
        }
    }


    // ╭───────────────────────────────────────────────────────────────────────────╮
    // │                            Handle the Searches                            │
    // ╰───────────────────────────────────────────────────────────────────────────╯
    private function handle_searches()
    {
        $this->loop_field_value = $this->add_suffix_to_duplicates($this->loop_field_value);

        foreach ($this->loop_field_value as $this->loop_search_key => $this->loop_search_value)
        {
            $search_script_name = $this->loop_search_value['acf_fc_layout'];
            $search_script_name_no_prefix = str_replace('vc_search_','',$search_script_name);
            $search_script_name_no_numbers = preg_replace('/\d+$/', '', $search_script_name);
            $search_script_config = $this->loop_search_value[$search_script_name_no_numbers];
            $search_script_config = $this->removePrefixFromKeys($search_script_config, $search_script_name_no_numbers."_");

            # set new value
            $this->loop_field_value[$search_script_name_no_prefix] = $search_script_config;

            # remove old one
            unset($this->loop_field_value[$this->loop_search_key]);
        }
    }





    // ╭───────────────────────────────────────────────────────────────────────────╮
    // │                           Handle the Downloads                            │
    // ╰───────────────────────────────────────────────────────────────────────────╯
    private function handle_downloads()
    {
        $this->loop_field_value = $this->add_suffix_to_duplicates($this->loop_field_value);

        foreach ($this->loop_field_value as $this->loop_search_key => $this->loop_search_value)
        {
            $search_script_name = $this->loop_search_value['acf_fc_layout'];
            $search_script_name_no_prefix = str_replace('vc_download_','',$search_script_name);
            $search_script_name_no_numbers = preg_replace('/\d+$/', '', $search_script_name);
            $search_script_config = $this->loop_search_value[$search_script_name_no_numbers];
            $search_script_config = $this->removePrefixFromKeys($search_script_config, $search_script_name_no_numbers."_");

            # set new value
            $this->loop_field_value[$search_script_name_no_prefix] = $search_script_config;

            # remove old one
            unset($this->loop_field_value[$this->loop_search_key]);
        }
    }
}