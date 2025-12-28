<?php
/**
 * Plugin Name: Owneor Analyze
 * Plugin URI: 
 * Description: WooCommerce order analysis plugin that calculates profit of all sales with date range.
 * Version: 1.0.0
 * Author: 
 * License: GPL v2 or later
 * Text Domain: owneor-analize
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include the main class
include_once plugin_dir_path(__FILE__) . 'includes/class-owneor-analize.php';

// Initialize the plugin
function owneor_analize_init() {
    $plugin = new Owneor_Analize();
    $plugin->run();
}
add_action('plugins_loaded', 'owneor_analize_init');