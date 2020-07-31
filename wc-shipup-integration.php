<?php

namespace WC_Shipup;

use WC_Integration;

/**
 * Woocommerce integration.
 *
 * @package WC_Shipup
 * @category Integration
 * @author Martin PAUCOT <contact@martin-paucot.fr
 */
class WC_Shipup_Integration extends WC_Integration
{

    private $api_token;

    public function __construct()
    {
        $this->id = 'wc_shipup_integration';
        $this->method_title = __('WC Shipup', 'wc-shipup');
        $this->method_description = __('Integrate Woocommerce with Shipup.', 'wc-shipup');
        $this->init_form_fields();
        $this->init_settings();

        $this->api_token = $this->get_option('private_api_key');

        add_action('woocommerce_update_options_integration_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'private_api_key' => [
                'title' => __('Private API Key', 'wc-shipup'),
                'type' => 'text',
                'description' => __('Your Shipup private API Key. You can get it here: https://app.shipup.co/settings/general/#api-keys', 'wc-shipup'),
                'desc_tip' => true,
                'default' => ''
            ],
            'public_api_key' => [
                'title' => __('Public API Key', 'wc-shipup'),
                'type' => 'text',
                'description' => __('Your Shipup public API Key. You can get it here: https://app.shipup.co/settings/general/#api-keys', 'wc-shipup'),
                'desc_tip' => true,
                'default' => ''
            ],
        ];
    }
}
