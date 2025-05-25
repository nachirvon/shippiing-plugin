<?php

if (!defined('ABSPATH')) exit;

require AMDSP_DIR . '/plugins/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

defined('ABSPATH') || exit;

class AMDSP_Update {

    public function __construct() {

        PucFactory::buildUpdateChecker(
            'https://amadast-file.storage.iran.liara.space/wp-plugin/info.json',
            AMDSP_FILE,
            'amdsp-amadast-plugin',
            8,
        );
    }
}

new AMDSP_Update();
