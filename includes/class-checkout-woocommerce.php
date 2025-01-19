<?php
/**
 * Plugin functions and definitions for Admin.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * @package yourpropfirm-checkout
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Yourpropfirm_Checkout_Woocommerce {

    public function __construct() {
        // Disable payment options on checkout page
        add_filter('woocommerce_cart_needs_payment', '__return_false');

        // Modify checkout fields
        add_filter('woocommerce_checkout_fields', [$this, 'ypf_customize_checkout_fields']);

        // Set order status based on total at checkout
        add_action('woocommerce_checkout_order_processed', [$this, 'ypf_set_order_status_based_on_total'], 10, 3);

        // Redirect to appropriate page after checkout
        add_action('woocommerce_thankyou', [$this, 'ypf_redirect_after_checkout'], 10);

        // Ensure completed orders remain completed
        add_filter('woocommerce_payment_complete_order_status', [$this, 'ypf_ensure_completed_orders_remain_completed'], 10, 3);
    }

    /**
     * Customize WooCommerce checkout fields
     *
     * @param array $fields
     * @return array
     */
    public function ypf_customize_checkout_fields($fields) {
        // Remove shipping fields
        unset($fields['shipping']);
        
        // Unset all billing fields
        unset($fields['billing']);

        // Add customized billing fields with WooCommerce classes
        $fields['billing'] = [
            'billing_first_name' => [
                'label' => __('First Name', 'multistep-checkout'),
                'required' => true,
                'class' => ['form-row-first'],
                'input_class' => ['input-text'],
                'placeholder' => __('First Name', 'multistep-checkout'),
            ],
            'billing_last_name' => [
                'label' => __('Last Name', 'multistep-checkout'),
                'required' => true,
                'class' => ['form-row-last'],
                'input_class' => ['input-text'],
                'placeholder' => __('Last Name', 'multistep-checkout'),
                'clear' => true,
            ],
            'billing_email' => [
                'label' => __('Email', 'multistep-checkout'),
                'required' => true,
                'class' => ['form-row-first'],
                'input_class' => ['input-text'],
                'placeholder' => __('Email', 'multistep-checkout'),
            ],
            'billing_phone' => [
                'label' => __('Phone Number', 'multistep-checkout'),
                'required' => true,
                'class' => ['form-row-last'],
                'input_class' => ['input-text'],
                'placeholder' => __('Phone Number', 'multistep-checkout'),
                'clear' => true,
            ],
            'billing_address_1' => [
                'label' => __('Address', 'multistep-checkout'),
                'required' => true,
                'class' => ['form-row-wide'],
                'input_class' => ['input-text'],
                'placeholder' => __('Address', 'multistep-checkout'),
            ],
            'billing_country' => [
                'label' => __('Country', 'multistep-checkout'),
                'required' => true,
                'type' => 'select',
                'class' => ['form-row-first'],
                'input_class' => ['input-text'],
                'options' => WC()->countries->get_countries(),
            ],
            'billing_state' => [
                'label' => __('State/Region', 'multistep-checkout'),
                'required' => true,
                'class' => ['form-row-last'],
                'input_class' => ['input-text'],
                'placeholder' => __('State/Region', 'multistep-checkout'),
                'clear' => true,
            ],
            'billing_city' => [
                'label' => __('City', 'multistep-checkout'),
                'required' => true,
                'class' => ['form-row-first'],
                'input_class' => ['input-text'],
                'placeholder' => __('City', 'multistep-checkout'),
            ],
            'billing_postcode' => [
                'label' => __('Postal Code', 'multistep-checkout'),
                'required' => true,
                'class' => ['form-row-last'],
                'input_class' => ['input-text'],
                'placeholder' => __('Postal Code', 'multistep-checkout'),
                'clear' => true,
            ],
        ];

        return $fields;
    }

    /**
     * Set order status based on total at checkout
     *
     * @param int $order_id
     * @param array $posted_data
     * @param WC_Order $order
     */
    public function ypf_set_order_status_based_on_total($order_id, $posted_data, $order) {
        if ($order->get_total() == 0) {
            // If order total is 0, set status to completed
            $order->update_status('completed');
        } else {
            // If order total > 0, set status to pending
            $order->add_order_note( sprintf( __( 'Order Created. Order ID: #%d', 'multistep-checkout' ), $order->get_id() ) );
            $order->update_status('pending');
        }
    }

    /**
     * Redirect to appropriate page after checkout
     *
     * @param int $order_id
     */
    public function ypf_redirect_after_checkout($order_id) {
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);

        if ($order->get_total() == 0) {
            // If order total is 0, let WooCommerce handle the flow to Thank You page
            return;
        }

        if ($order->get_status() === 'pending') {
            // Redirect unpaid orders to order-pay page
            $redirect_url = $order->get_checkout_payment_url();
            $order->add_order_note(__('Redirecting to Order-Pay Page', 'multistep-checkout'));
            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    /**
     * Ensure completed orders remain completed
     *
     * @param string $status
     * @param int $order_id
     * @param WC_Order $order
     * @return string
     */
    public function ypf_ensure_completed_orders_remain_completed($status, $order_id, $order) {
        // Only adjust orders that are not already completed
        if ($order->get_status() === 'pending') {
            return 'pending'; // Keep pending for unpaid orders
        }
        return $status; // Return default status for other cases
    }
}