<?php

/*
 * @wordpress-plugin
 * Plugin Name:       _ANDYP - Video Constructor
 * Plugin URI:        http://londonparkour.com
 * Description:       <strong>🗓️ Video Constructor</strong> | Auto-build Videos.
 * Version:           1.0.0
 * Author:            Andy Pearson
 * Author URI:        https://londonparkour.com
 */

// ┌─────────────────────────────────────────────────────────────────────────┐
// │                         Use composer autoloader                         │
// └─────────────────────────────────────────────────────────────────────────┘
require __DIR__.'/vendor/autoload.php';

//  ┌─────────────────────────────────────────────────────────────────────────┐
//  │                           Register CONSTANTS                            │
//  └─────────────────────────────────────────────────────────────────────────┘
define( 'VIDEOCONSTRUCTOR_PATH', __DIR__ );
define( 'VIDEOCONSTRUCTOR_URL', plugins_url( '/', __FILE__ ) );
define( 'VIDEOCONSTRUCTOR_FILE',  __FILE__ );

// ┌─────────────────────────────────────────────────────────────────────────┐
// │                        	   Initialise    		                     │
// └─────────────────────────────────────────────────────────────────────────┘
if (is_plugin_active('advanced-custom-fields-pro/acf.php')) {
    $cpt = new videoconstructor\initialise;
    $cpt->run();
}


// ╭───────────────────────────────────────────────────────────────────────────╮
// │                       Notices if ACF not installed                        │
// ╰───────────────────────────────────────────────────────────────────────────╯
class videoconstructor_notices {
    public function __construct() {
        // Add action to check required plugins on admin init
        add_action('admin_init', array($this, 'check_required_plugins'));
    }

    public function check_required_plugins() {
        // Check if ACF is active
        if (!is_plugin_active('advanced-custom-fields-pro/acf.php')) {
            add_action('admin_notices', array($this, 'acf_missing_notice'));
        }
    }

    public function acf_missing_notice() {
        echo '<div class="error"><p><strong>videoconstructor Plugin</strong> requires <strong>Advanced Custom Fields</strong> to be installed and active.</p></div>';
    }

}

// Initialize the plugin
new videoconstructor_notices();