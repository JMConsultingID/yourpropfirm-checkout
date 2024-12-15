<?php
class Yourprofirm_Order_Handler {

    public static function init() {
        add_action('init', [__CLASS__, 'process_order_creation']);
    }

    public static function process_order_creation() {
        // Check if session data exists
        $billing_data = WC()->session->get('yourprofirm_billing_data');
        if (!$billing_data) {
            return;
        }

        // Create a WooCommerce order
        $order = wc_create_order();
        $order->set_address($billing_data, 'billing');
        $order->set_status('pending');
        $order->save();

        // Get the Order Pay URL
        $order_pay_url = $order->get_checkout_payment_url();

        // Clean up session
        WC()->session->__unset('yourprofirm_billing_data');

        // Redirect to the WooCommerce Order Pay page
        wp_redirect($order_pay_url);
        exit;
    }
}
