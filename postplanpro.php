<?php

/*
 * @wordpress-plugin
 * Plugin Name:       _ANDYP - Post Plan Pro
 * Plugin URI:        http://londonparkour.com
 * Description:       <strong>🗓️ PostPlanPro</strong> | Schedule and Auto-Post local & remote content to MAKE.COM for social platform delivery.
 * Version:           2.0.0
 * Author:            Andy Pearson
 * Author URI:        https://londonparkour.com
 */



// ┌─────────────────────────────────────────────────────────────────────────┐
// │                                Run ACF                                  │
// └─────────────────────────────────────────────────────────────────────────┘
// Load ACF from plugin folder if it’s not already active
add_action('plugins_loaded', 'ppp_include_acf');

function ppp_include_acf() {
    // Prevent conflicts if ACF is already installed
    if ( ! class_exists('ACF') ) {
        include_once plugin_dir_path(__FILE__) . 'includes/acfp/acf.php';
        include_once plugin_dir_path(__FILE__) . 'includes/acfp-code-field/acf-code-field.php';
    }
}

// Hide ACF from the plugins list
add_filter('all_plugins', function($plugins) {
    if ( isset($plugins['includes/acf/acf.php']) ) {
        unset($plugins['includes/acf/acf.php']);
    }
    return $plugins;
});

// Save ACF field groups in your plugin folder
add_filter('acf/settings/save_json', function($path) {
    return plugin_dir_path(__FILE__) . 'acf-json';
});

// Load ACF field groups from your plugin folder
add_filter('acf/settings/load_json', function($paths) {
    $paths[] = plugin_dir_path(__FILE__) . 'acf-json';
    return $paths;
});

add_filter('acf/settings/show_admin', '__return_false');


// ┌─────────────────────────────────────────────────────────────────────────┐
// │                         Use composer autoloader                         │
// └─────────────────────────────────────────────────────────────────────────┘
require __DIR__.'/vendor/autoload.php';

//  ┌─────────────────────────────────────────────────────────────────────────┐
//  │                           Register CONSTANTS                            │
//  └─────────────────────────────────────────────────────────────────────────┘
define( 'POSTPLANPRO_PATH', __DIR__ );
define( 'POSTPLANPRO_URL', plugins_url( '/', __FILE__ ) );
define( 'POSTPLANPRO_FILE',  __FILE__ );

// ┌─────────────────────────────────────────────────────────────────────────┐
// │                        	   Initialise    		                     │
// └─────────────────────────────────────────────────────────────────────────┘
// if (is_plugin_active('advanced-custom-fields-pro/acf.php')) {
    $cpt = new postplanpro\initialise;
    $cpt->run();
// }


// ╭───────────────────────────────────────────────────────────────────────────╮
// │                       Notices if ACF not installed                        │
// ╰───────────────────────────────────────────────────────────────────────────╯
// class postplanpro_notices {
//     public function __construct() {
//         // Add action to check required plugins on admin init
//         add_action('admin_init', array($this, 'check_required_plugins'));
//     }

//     public function check_required_plugins() {
//         // Check if ACF is active
//         if (!is_plugin_active('advanced-custom-fields-pro/acf.php')) {
//             add_action('admin_notices', array($this, 'acf_missing_notice'));
//         }
//     }

//     public function acf_missing_notice() {
//         echo '<div class="error"><p><strong>PostPlanPro Plugin</strong> requires <strong>Advanced Custom Fields</strong> to be installed and active.</p></div>';
//     }

// }

// // Initialize the plugin
// new postplanpro_notices();