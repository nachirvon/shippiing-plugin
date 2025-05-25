<?php

if (!defined('ABSPATH')) exit;

class AMDSP_ShippingMethod {
    const GATEWAY_PISHTAZ = 13;
    const MAHEX = 16;
    const TIPAX = 4;
    const FORWARD = 19;

    public static function get_methods(): array {
        return [
            self::GATEWAY_PISHTAZ,
            self::MAHEX,
            self::TIPAX,
            self::FORWARD,
        ];
    }

    public static function get_shipping_method_title($method_id): string {
        return [
            self::GATEWAY_PISHTAZ => "پست پیشتاز",
            self::MAHEX           => "ماهکس",
            self::TIPAX           => "تیپاکس",
            self::FORWARD         => "فوروارد",
        ][$method_id];
    }

    public static function get_shipping_method_options(): array {
        return [
            self::GATEWAY_PISHTAZ => self::get_shipping_method_title(self::GATEWAY_PISHTAZ),
            self::MAHEX           => self::get_shipping_method_title(self::MAHEX),
            self::TIPAX           => self::get_shipping_method_title(self::TIPAX),
            self::FORWARD         => self::get_shipping_method_title(self::FORWARD),
        ];
    }
}