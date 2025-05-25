<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function amdsp_file_put_contents($path, $content) {
    global $wp_filesystem;

    if (!$wp_filesystem) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
    }

    return $wp_filesystem->put_contents($path, $content);
}

function amdsp_file_delete($path) {
    wp_delete_file($path);

    return true;
}

function amdsp_mkdir($path, $permissions = 777) {
    global $wp_filesystem;

    if (!$wp_filesystem) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
    }

    if ($wp_filesystem->exists($path)) {
        return true;
    }

    $parent_dir = dirname($path);

    if ($parent_dir !== '.' && !$wp_filesystem->exists($parent_dir)) {
        amdsp_mkdir($parent_dir);
    }

    return $wp_filesystem->mkdir($path, $permissions);
}

function amdsp_rmdir($dir) {
    global $wp_filesystem;

    if (!$wp_filesystem) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
    }

    if (!is_dir($dir)) {
        error_log("Directory not found: $dir");
        return false;
    } else {
        if (!$wp_filesystem->delete($dir, true)) {
            error_log("Failed to delete directory: $dir");
            return false;
        }
    }

    return true;
}
