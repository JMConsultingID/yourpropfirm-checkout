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

        // Check if session data exists
        $billing_data = WC()->session->get('yourprofirm_billing_data');
        if (!$billing_data) {
            return;
        }

        // Create a WooCommerce order
        $order = wc_create_order();

        // Set billing details
        $order->set_address([
            'first_name' => $billing_data['billing_first_name'],
            'last_name'  => $billing_data['billing_last_name'],
            'email'      => $billing_data['billing_email'],
            'phone'      => $billing_data['billing_phone'],
            'country'    => $billing_data['billing_country'],
            'state'      => $billing_data['billing_state'],
        ], 'billing');

        // Set order status to 'Pending Payment'
        $order->set_status('pending');
        $order->save();

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
