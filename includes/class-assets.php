<?php

class SWPF_Super_Rollback_Assets {
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_footer', array($this, 'add_rollback_modal_div'));
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'plugins.php') {
            return;
        }
        wp_enqueue_script('wp-components');
        wp_enqueue_style('wp-components');
        wp_enqueue_style('super-rollback', SWPF_SUPER_ROLLBACK_PLUGIN_URL . 'assets/super-rollback.css');
        wp_enqueue_script('super-rollback', SWPF_SUPER_ROLLBACK_PLUGIN_URL . 'assets/super-rollback.js', array('wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n'), '1.0.2', true);
        wp_localize_script('super-rollback', 'pluginRollback', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('super_rollback_nonce')
        ));
    }

    public function add_rollback_modal_div() {
        echo '<div id="rollback-modal"></div>';
    }
}