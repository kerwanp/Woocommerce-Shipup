<?php

namespace WC_Shipup;


use WP_HTTP_Requests_Response;

class WC_Shipup_Api
{

    /**
     * @var string
     */
    private $private_api_key;

    /**
     * @var string
     */
    private $uri;

    /**
     * WC_Shipup_Api constructor.
     * @param string $uri
     */
    public function __construct($uri = 'https://api.shipup.co/v2')
    {
        $this->private_api_key = WC_Shipup::get_private_api_key();
        $this->uri = $uri;
    }

    public function post_order($data)
    {
        /** @var WP_HTTP_Requests_Response $result */
        wp_remote_post("$this->uri/orders", [
            'headers' => [
                'Authorization' => "Bearer $this->private_api_key",
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($data),
        ]);
    }

}