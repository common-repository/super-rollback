<?php

class SWPF_Super_Rollback_Hooks {
    public function __construct() {
        add_filter(
            'plugin_action_links',
            array($this, 'add_rollback_link'),
            10,
            4
        );
        if ( !extension_loaded( 'zip' ) ) {
            add_action( 'admin_notices', function () {
                echo '<div class="notice notice-error"><p><strong>Error!</strong> The server is missing the <strong>zip</strong> module required to support rollbacks. Please contact your hosting provider to enable.</p></div>';
            } );
        }
    }

    public function add_plugin_update_warning( $plugin_data, $response ) {
    }

    public function add_rollback_link(
        $links,
        $file,
        $plugin_data,
        $context
    ) {
        $rollback_link = '<a href="#" class="super-rollback-link" data-plugin="' . esc_attr( $file ) . '">Rollback</a>';
        array_push( $links, $rollback_link );
        return $links;
    }

}
