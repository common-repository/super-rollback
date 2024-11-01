<?php

class SWPF_Super_Rollback_Ajax {
    public function __construct() {
        add_action( 'wp_ajax_fetch_rollback_data', array($this, 'fetch_rollback_data') );
        add_action( 'wp_ajax_initiate_rollback', array($this, 'initiate_rollback') );
    }

    public function fetch_rollback_data() {
        check_ajax_referer( 'super_rollback_nonce', 'nonce' );
        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Unauthorised', 'super-rollback' ),
            ) );
        }
        if ( !isset( $_POST['plugin'] ) ) {
            wp_send_json_error( array(
                'message' => __( 'Invalid request', 'super-rollback' ),
            ) );
        }
        $plugin = sanitize_text_field( $_POST['plugin'] );
        $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
        $current_version = $plugin_data['Version'];
        $plugin_name = $plugin_data['Name'];
        $rollback_dir = WP_CONTENT_DIR . '/rollbacks/' . basename( dirname( $plugin ) );
        if ( !is_dir( $rollback_dir ) ) {
            wp_send_json_error( array(
                'message' => __( 'No rollbacks found for this plugin. Once you perform an update, rollbacks will start to appear.', 'super-rollback' ),
            ) );
        }
        $backups = array_filter( scandir( $rollback_dir ), function ( $file ) {
            return pathinfo( $file, PATHINFO_EXTENSION ) === 'zip';
        } );
        $versions = array();
        $is_not_paying = swpf()->is_not_paying();
        $available_count = 0;
        foreach ( $backups as $backup ) {
            if ( preg_match( '/-(\\d+\\.\\d+(\\.\\d+)*)\\.zip$/', $backup, $matches ) ) {
                $is_current = $matches[1] === $current_version;
                $is_available = true;
                if ( $is_not_paying && $available_count >= 1 ) {
                    $is_available = false;
                } else {
                    $available_count++;
                }
                $versions[] = array(
                    'file'      => $backup,
                    'version'   => $matches[1],
                    'current'   => $is_current,
                    'available' => $is_available,
                );
            }
        }
        if ( empty( $versions ) ) {
            wp_send_json_error( array(
                'message' => __( 'No rollbacks found for this plugin. Once you perform an update, rollbacks will start to appear.', 'super-rollback' ),
            ) );
        }
        wp_send_json_success( array(
            'plugin'          => $plugin,
            'plugin_name'     => $plugin_name,
            'current_version' => $current_version,
            'backups'         => $versions,
        ) );
    }

    public function initiate_rollback() {
        check_ajax_referer( 'super_rollback_nonce', 'nonce' );
        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Unauthorised', 'super-rollback' ),
            ) );
            wp_die( __( 'Unauthorised', 'super-rollback' ) );
        }
        if ( isset( $_POST['plugin'] ) && isset( $_POST['backup'] ) && !empty( $_POST['backup'] ) ) {
            $plugin = sanitize_text_field( $_POST['plugin'] );
            $backup = sanitize_text_field( $_POST['backup'] );
            $plugin_dir = WP_PLUGIN_DIR . '/' . dirname( $plugin );
            $backup_dir = WP_CONTENT_DIR . '/rollbacks/' . basename( dirname( $plugin ) ) . '/' . $backup;
            $zip_file = $backup_dir;
            // Check if the backup zip exists
            if ( !file_exists( $zip_file ) ) {
                wp_send_json_error( array(
                    'message' => 'Rollback not found.',
                ) );
                wp_die( __( 'Rollback not found', 'super-rollback' ) );
            }
            // Validate the ZIP file before proceeding
            if ( !$this->validate_zip( $zip_file ) ) {
                wp_delete_file( $zip_file );
                // Delete the invalid ZIP file
                wp_send_json_error( array(
                    'message' => 'The rollback Zip file is invalid.',
                ) );
                wp_die( __( 'The rollback Zip file is invalid.', 'super-rollback' ) );
            }
            global $wp_filesystem;
            WP_Filesystem();
            if ( !$wp_filesystem->delete( $plugin_dir, true ) ) {
                wp_send_json_error( array(
                    'message' => 'Removing the old version of the plugin failed.',
                ) );
            }
            $result = unzip_file( $zip_file, $plugin_dir );
            // Check if the result is a WP_Error and handle it accordingly
            if ( is_wp_error( $result ) ) {
                $error_message = $result->get_error_message();
                wp_send_json_error( array(
                    'message' => $error_message,
                ) );
            } else {
                wp_send_json_success( array(
                    'message' => __( 'Plugin rollback successful', 'super-rollback' ),
                ) );
            }
            exit;
        }
        wp_send_json_error( array(
            'message' => 'Make sure you have chosen a version!',
        ) );
        wp_die( __( 'Make sure you have chosen a version!', 'super-rollback' ) );
    }

    private function validate_zip( $zip_file ) {
        $zip = new ZipArchive();
        if ( $zip->open( $zip_file ) === true ) {
            $valid = $zip->numFiles > 0;
            $zip->close();
            return $valid;
        }
        return false;
    }

}
