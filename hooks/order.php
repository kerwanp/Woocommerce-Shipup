<?php

use WC_Shipup\WC_Shipup;

function wc_shipup_add_order_shipping_meta_box()
{
    global $post;

    $carrier = get_post_meta($post->ID, 'wc_shipup_carrier', true);
    $trackingCode = get_post_meta($post->ID, 'wc_shipup_tracking_code', true);

    ?>

    <input type="hidden" name="shipping_meta_field_nonce" value="<?= wp_create_nonce() ?>">
    <p style="border-bottom: solid 1px #eee; padding-bottom: 13px; ">
        <label for="wc-shipup-carrier"><?= __('Carrier', 'wc-shipup') ?></label>
        <input id="wc-shipup-carrier" type="text" style="width: 250px;" name="wc-shipup-carrier" placeholder="colissimo"
               value="<?= $carrier ?>">

        <label for="wc-shipup-tracking"><?= __('Tracking Code', 'wc-shipup') ?></label>
        <input id="wc-shipup-tracking" type="text" style="width: 250px;" name="wc-shipup-tracking"
               placeholder="XXXXXXXXX" value="<?= $trackingCode ?>">
    </p>
    <?php
}

add_action('add_meta_boxes', function () {
    add_meta_box('wc_shipup_shipping', __('Shipping', 'woocommerce'), 'wc_shipup_add_order_shipping_meta_box', 'shop_order', 'side', 'core');
});

/**
 * Saves WC Shipup values from the order.
 */
add_action('save_post_shop_order', function ($post_id) {
    if (!isset($_POST['shipping_meta_field_nonce']))
        return $post_id;

    $nonce = $_REQUEST['shipping_meta_field_nonce'];

    if (!wp_verify_nonce($nonce))
        return $post_id;

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return $post_id;

    update_post_meta($post_id, 'wc_shipup_carrier', $_POST['wc-shipup-carrier']);
    update_post_meta($post_id, 'wc_shipup_tracking_code', $_POST['wc-shipup-tracking']);

    return $post_id;
}, 10, 1);

/**
 * Checks if the tracking and/or carrier is updated.
 * Creates a note and trigger the Shipup order update.
 */
add_action('save_post_shop_order', function ($post_id) {
    $carrier = get_post_meta($post_id, 'wc_shipup_carrier', true);
    $trackingCode = get_post_meta($post_id, 'wc_shipup_tracking_code', true);
    $shipped = !empty($carrier) && $carrier !== null && !empty($trackingCode) && $trackingCode !== null;
    $order = wc_get_order($post_id);

    if ($shipped) {
        $order->add_order_note('Shipping informations updated.');
    }

    WC_Shipup::get_instance()->synchronyze($order);

}, 10, 1);


add_action('wc_shipup_sync_order', function (WC_Order $order) {

    $user = $order->get_user();

    $carrier = get_post_meta($order->get_id(), 'wc_shipup_carrier', true);
    $trackingCode = get_post_meta($order->get_id(), 'wc_shipup_tracking_code', true);
    $shipped = !empty($carrier) && $carrier !== null && !empty($trackingCode) && $trackingCode !== null;

    $data = [
        'merchant_id' => strval($order->get_id()),
        'ordered_at' => $order->get_date_created()->getTimestamp(),
        'language_code' => get_user_meta($user->ID, 'user_lang', true),
        'email' => $order->get_billing_email(),
        'phone' => $order->get_billing_phone(),
        'order_number' => $order->get_order_number(),
        'first_name' => $order->get_billing_first_name(),
        'last_name' => $order->get_billing_last_name(),
        'shipping_address' => [
            'address1' => $order->get_shipping_address_1(),
            'address2' => $order->get_shipping_address_2(),
            'city' => $order->get_shipping_city(),
            'zip' => $order->get_shipping_postcode(),
            'country' => $order->get_shipping_country(),
            'state' => $order->get_shipping_state(),
            'first_name' => $order->get_shipping_first_name(),
            'last_name' => $order->get_shipping_last_name(),
            'company_title' => $order->get_shipping_company()
        ],
        'fulfillments' => [],
    ];

    $items = [];

    /** @var WC_Order_Item_Product $item */
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        $items[] = [
            'merchant_id' => strval($item->get_id()),
            'title' => $product->get_type() === 'simple' ? $product->get_title() : wc_get_product($product->get_parent_id())->get_title(),
            'variant_title' => $product->get_type() === 'simple' ? null : $product->get_title(),
            'name' => $product->get_name(),
            'description' => $product->get_description(),
            'quantity' => $item->get_quantity(),
            'sku' => $product->get_sku(),
            'thumbnail' => ['src' => wp_get_attachment_image_src($product->get_image_id())[0], 'width' => 150, 'height' => 150]
        ];
    }

    if ($shipped) {
        $data['fulfillments'][] = [
            'merchant_id' => strval($order->get_id()) . "_1",
            'status_code' => 'shipped',
            'trackers' => [
                [
                    'tracking_number' => $trackingCode,
                    'carrier_code' => $carrier,
                    'language_code' => get_user_meta($user->ID, 'user_lang', true),
                    'line_items' => $items
                ]
            ],
        ];
    } else {
        $data['fulfillments'][] = [
            'merchant_id' => strval($order->get_id()) . "_1",
            'line_items' => $items
        ];
    }

    WC_Shipup::get_instance()->get_api()->post_order($data);
}, 1, 1);

add_filter('wc_shipup_show_tracking', function ($order_id) {

    $carrier = get_post_meta($order_id, 'wc_shipup_carrier', true);
    $trackingCode = get_post_meta($order_id, 'wc_shipup_tracking_code', true);
    $shipped = !empty($carrier) && $carrier !== null && !empty($trackingCode) && $trackingCode !== null;

    if (!$shipped)
        return;

    ?>
    <h2><?= __('Follow your order', 'wc-shipup') ?></h2>
    <script charset="UTF-8" type="text/javascript" src="https://cdn.shipup.co/latest_v2/shipup-js.js"></script>
    <link rel="stylesheet" href="https://cdn.shipup.co/latest_v2/shipup.css"/>
    <div id="shipup-container"></div>
    <script>
      var shipup = new ShipupJS.default('O26JWNqNkgm3mh0H3D2KtQ')
      var element = document.getElementById('shipup-container')
      shipup.render(element, {
        language: '<?= get_user_locale() ?>',
        orderNumber: <?= $order_id ?>,
        searchEnabled: false
      })
    </script>
    <?php
}, 1);