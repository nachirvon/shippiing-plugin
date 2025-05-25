<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function amdsp_array_flatten($array) {
    $result = array();
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $result = $result + amdsp_array_flatten($value);
        } else {
            $result[$key] = $value;
        }
    }
    return $result;
}
