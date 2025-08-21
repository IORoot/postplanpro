<?php

/*
 * @wordpress-plugin
 * Plugin Name:       _ANDYP - Post Plan Pro - Generator
 * Plugin URI:        http://londonparkour.com
 * Description:       <strong>🎥 PostPlanPro Generator</strong> | Extension to create releases automatically.
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
define( 'POSTPLANPRO_GENERATOR_PATH', __DIR__ );
define( 'POSTPLANPRO_GENERATOR_URL', plugins_url( '/', __FILE__ ) );
define( 'POSTPLANPRO_GENERATOR_FILE',  __FILE__ );

// ┌─────────────────────────────────────────────────────────────────────────┐
// │                        	   Initialise    		                     │
// └─────────────────────────────────────────────────────────────────────────┘
$cpt = new ppp_gen\initialise;
$cpt->run();
