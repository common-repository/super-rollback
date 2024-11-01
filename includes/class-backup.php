<?php

class SWPF_Super_Rollback_Backup {
    public function __construct() {
        add_action('upgrader_pre_install', array($this, 'backup_plugin_before_update'), 10, 2);
    }

    public function backup_plugin_before_update($response, $hook_extra) {
        global $wp_filesystem;
        WP_Filesystem();

        if (isset($hook_extra['plugin'])) {
            $plugin = $hook_extra['plugin'];
            $plugin_dir = WP_PLUGIN_DIR . '/' . dirname($plugin);
            $version = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin)['Version'];
            $rollbacks_dir = WP_CONTENT_DIR . '/rollbacks/';
            $backup_dir = $rollbacks_dir . basename($plugin_dir);

            if (!is_dir($rollbacks_dir)) {
                if (!is_dir($rollbacks_dir)) {
                    $wp_filesystem->mkdir($rollbacks_dir, 0755);
                }
            }
            if (!file_exists($rollbacks_dir . '/index.php')) {
                if (!file_exists($rollbacks_dir . '/index.php')) {
                    $wp_filesystem->put_contents($rollbacks_dir . '/index.php', '<?php // Silence is golden.');
                }
            }

            // Check if a backup for this version already exists
            $existing_backups = glob($backup_dir . '/*-' . $version . '.zip');
            if (!empty($existing_backups)) {
                error_log('Backup for version ' . $version . ' already exists.');
                return $response;
            }

            // Limit the number of backups to 5
            $all_backups = glob($backup_dir . '/*.zip');
            if (count($all_backups) >= 5) {
                // Delete the oldest backup
                usort($all_backups, function($a, $b) {
                    return filemtime($a) - filemtime($b);
                });
                unlink($all_backups[0]);
            }

            $random_string = $this->generate_random_string(32);
            $backup_version_dir = $backup_dir . '/' . $random_string . '-' . $version;

            if (!is_dir($backup_dir)) {
                $wp_filesystem->mkdir($backup_dir, 0755);
                $wp_filesystem->put_contents($backup_dir . '/index.php', '<?php // Silence is golden.');
            }

            $this->create_zip($plugin_dir, $backup_version_dir . '.zip');
        }
        return $response;
    }

    private function create_zip($source, $destination) {
        if (!extension_loaded('zip') || !file_exists($source)) {
            return false;
        }

        $zip = new ZipArchive();
        if (!$zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            return false;
        }

        $source = realpath($source);
        if (is_dir($source)) {
            $this->add_dir_to_zip($source, $source, $zip);
        } else if (is_file($source)) {
            $zip->addFile($source, basename($source));
        }

        return $zip->close();
    }

    private function add_dir_to_zip($rootPath, $source, $zip) {
        $files = scandir($source);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $filePath = $source . '/' . $file;
                $relativePath = str_replace($rootPath . '/', '', $filePath);
                if (is_dir($filePath)) {
                    $zip->addEmptyDir($relativePath);
                    $this->add_dir_to_zip($rootPath, $filePath, $zip); // Recurse into directories
                } else if (is_file($filePath)) {
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }
    }

    private function generate_random_string($length) {
        return substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
    }
}