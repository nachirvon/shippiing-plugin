<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function amdsp_round_up_to_nearest_multiple($n, $increment = 1000) {
    return (int)($increment * ceil($n / $increment));
}
