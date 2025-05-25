<?php

if ( ! defined( 'ABSPATH' ) ) exit;

foreach (glob(__DIR__ . "/*.php") as $filename) {
    if ($filename === './index.php') return;
    include_once $filename;
}
