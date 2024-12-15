<?php
class Yourprofirm_Order_Handler {

    public static function init() {
        add_action('wp', [__CLASS__, 'process_order_creation']);
    }

    public static function process_order_creation() {
    // Ensure WooCommerce session is available
    if (!WC()->session) {
        return;
    }

     // Check if cart is empty
    if (WC()->cart->is_empty()) {
        wc_add_notice(__('Your cart is empty. Please add products to your cart before proceeding.', 'yourprofirm-checkout'), 'error');
        wp_redirect(wc_get_cart_url()); // Redirect to the cart page
        exit;
    }

    // Check if session data exists
    $billing_data = WC()->session->get('yourprofirm_billing_data');
    if (!$billing_data) {
        return;
    }

    // Create a WooCommerce order
    $order = wc_create_order();

    // Add products from the cart to the order
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product_id = $cart_item['product_id'];
        $quantity = $cart_item['quantity'];
        $order->add_product(wc_get_product($product_id), $quantity);
    }

    // Set billing details
    $order->set_address($billing_data, 'billing');

    // Set order status to 'Pending Payment'
    $order->set_status('pending');
    $order->calculate_totals();
    $order->save();

    // Empty the cart after order creation
    WC()->cart->empty_cart();

    // Generate WooCommerce Order Pay URL
    $order_id   = $order->get_id();
    $order_key  = $order->get_order_key();
    $order_pay_url = wc_get_checkout_url() . "order-pay/{$order_id}/?pay_for_order=true&key={$order_key}";

    // Clean up session data
    WC()->session->__unset('yourprofirm_billing_data');

    // Redirect to the Order Pay page
    wp_redirect($order_pay_url);
    exit;
}

}
