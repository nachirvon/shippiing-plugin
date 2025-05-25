<?php

if (!defined('ABSPATH')) exit;

if (!function_exists('amdsp_need_translation')) {
    function amdsp_need_translation(): bool {
        return get_locale() !== 'fa_IR';
    }
}
