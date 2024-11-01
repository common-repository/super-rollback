<?php

if ( !function_exists( 'swpf' ) ) {
    function swpf() {
        global $swpf;
        if ( !isset( $swpf ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $swpf = fs_dynamic_init( array(
                'id'             => '16094',
                'slug'           => 'super-rollback',
                'type'           => 'plugin',
                'public_key'     => 'pk_ab3f997c431f29646151194335c58',
                'is_premium'     => false,
                'premium_suffix' => 'Pro',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'is_live'        => true,
            ) );
        }
        return $swpf;
    }

    // Init Freemius.
    swpf();
    // Signal that SDK was initiated.
    do_action( 'swpf_loaded' );
    fs_override_i18n( array(
        'yee-haw' => __( "Super", 'super-rollback' ),
        'woot'    => __( "Nice", 'super-rollback' ),
    ), 'super-rollback' );
}
// Load WordPress Upgrader class
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
// Load the plugin and update functions
if ( !function_exists( 'get_plugins' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
if ( !function_exists( 'get_plugin_updates' ) ) {
    require_once ABSPATH . 'wp-admin/includes/update.php';
}