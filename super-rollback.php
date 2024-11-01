<?php

/*
Plugin Name: Super Rollback
Description: Save snapshots of plugins before updating, enabling rollbacks to previous versions.
Version: 1.0.8
Author: SuperWP
Author URI:  https://superwp.io/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin assets paths
define('SWPF_SUPER_ROLLBACK_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SWPF_SUPER_ROLLBACK_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-main.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-assets.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-ajax.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-backup.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-hooks.php';

// Initialize the plugin
new SWPF_Super_Rollback();