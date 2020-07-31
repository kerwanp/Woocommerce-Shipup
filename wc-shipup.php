<?php
/**
 * Plugin Name: WC Shipup
 * Description: Shipup integration plugin for Woocommerce.
 * Author: Martin PAUCOT <contact@martin-paucot.fr>
 * Version: 1.0.0
 */

namespace WC_Shipup;

class WC_Shipup {

    private static $instance;

    private $api;

    public function __construct()
    {
        include __DIR__ . '/hooks/order.php';

        include_once 'wc-shipup-integration.php';
        include_once 'wc-shipup-api.php';

        add_filter( 'woocommerce_integrations', function ($integrations) {
            $integrations[] = 'WC_Shipup\WC_Shipup_Integration';
            return $integrations;
        } );
    }

    public function synchronyze (\WC_Order $order) {
        do_action('wc_shipup_sync_order', $order);
    }

    public function get_api ()
    {
        if ($this->api === null)
            $this->api = new WC_Shipup_Api();
        return $this->api;
    }

    public static function get_private_api_key () {
        $settings = get_option('woocommerce_wc_shipup_integration_settings');
        return $settings['private_api_key'];
    }

    public static function get_public_api_key () {
        $settings = get_option('woocommerce_wc_shipup_integration_settings');
        return $settings['public_api_key'];
    }

    public static function get_instance () {
        if (self::$instance === null)
            self::$instance = new WC_Shipup();
        return self::$instance;
    }

}

add_filter('plugins_loaded', '\WC_Shipup\WC_Shipup::get_instance');