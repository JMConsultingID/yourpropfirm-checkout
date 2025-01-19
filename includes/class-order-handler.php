<?php
/**
 * Plugin functions and definitions for Admin.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * @package yourpropfirm-checkout
 */
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Yourpropfirm_Checkout_Order_Handler
 *
 * Handles form submission, creates orders programmatically,
 * and redirects users to the order-pay page.
 */
class Yourpropfirm_Checkout_Order_Handler {
    public function __construct() {
        add_action('wp_ajax_ypf_process_checkout', [$this, 'handle_form_submission']);
        add_action('wp_ajax_nopriv_ypf_process_checkout', [$this, 'handle_form_submission']);
        add_action('template_redirect', [$this, 'clear_notices_on_order_pay']);
        add_action('init', [$this, 'init_session']);
    }

    public function init_session() {
        if (!is_admin() && !WC()->session) {
            WC()->session = new WC_Session_Handler();
            WC()->session->init();
        }
    }

    public function handle_form_submission() {
        check_ajax_referer('ypf_checkout_nonce', 'nonce');

        $response = [
            'success' => false,
            'redirect' => ''
        ];

        // Store form data
        $form_data = [
            'first_name' => isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '',
            'last_name' => isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '',
            'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
            'phone' => isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '',
            'address' => isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '',
            'country' => isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '',
            'state' => isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '',
            'city' => isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '',
            'postal_code' => isset($_POST['postal_code']) ? sanitize_text_field($_POST['postal_code']) : '',
        ];
        
        WC()->session->set('ypf_checkout_form_data', $form_data);

        // Verify cart is not empty
        if (WC()->cart->is_empty()) {
            wc_add_notice(__('Your cart is empty. Please add a product to proceed.', 'yourpropfirm-checkout'), 'error');
            $response['redirect'] = wc_get_cart_url();
            wp_send_json($response);
            return;
        }

        // Handle coupon if provided
        if (!empty($_POST['coupon_code'])) {
            $coupon_code = sanitize_text_field($_POST['coupon_code']);
            $coupon = new WC_Coupon($coupon_code);
            
            if (!$coupon->is_valid()) {
                wc_add_notice(__('Invalid coupon code.', 'yourpropfirm-checkout'), 'error');
                wp_send_json($response);
                return;
            }

            $result = WC()->cart->apply_coupon($coupon_code);
            
            if (is_wp_error($result)) {
                wc_add_notice($result->get_error_message(), 'error');
                wp_send_json($response);
                return;
            }

            // Recalculate totals after applying coupon
            WC()->cart->calculate_totals();
        }

        // Verify required fields
        $required_fields = ['first_name', 'last_name', 'email', 'phone', 'address', 'country', 'city', 'postal_code'];
        $missing_fields = false;
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields = true;
            }
        }
        
        if ($missing_fields) {
            wc_add_notice(__('Please fill in all required fields.', 'yourpropfirm-checkout'), 'error');
            wp_send_json($response);
            return;
        }

        // Check Terms and Privacy Policy
        if (empty($_POST['terms']) || empty($_POST['privacy_policy'])) {
            wc_add_notice(__('Please accept the Terms and Conditions and Privacy Policy to proceed.', 'yourpropfirm-checkout'), 'error');
            wp_send_json($response);
            return;
        }

        try {
            // Initialize WooCommerce order
            $order = wc_create_order();

            // Add cart items to the order
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];
                $quantity = $cart_item['quantity'];
                $order->add_product($product, $quantity);
            }

            // Set billing fields
            $order->set_billing_first_name($form_data['first_name']);
            $order->set_billing_last_name($form_data['last_name']);
            $order->set_billing_email($form_data['email']);
            $order->set_billing_phone($form_data['phone']);
            $order->set_billing_address_1($form_data['address']);
            $order->set_billing_city($form_data['city']);
            $order->set_billing_postcode($form_data['postal_code']);
            $order->set_billing_country($form_data['country']);
            $order->set_billing_state($form_data['state']);

            // Set customer ID
            if (is_user_logged_in()) {
                $order->set_customer_id(get_current_user_id());
            } else {
                $order->set_customer_id(0);
            }

            // Apply coupon(s) to the order
            if (!empty(WC()->cart->get_applied_coupons())) {
                foreach (WC()->cart->get_applied_coupons() as $coupon_code) {
                    $order->apply_coupon($coupon_code);
                }
            }

            // Calculate totals
            $order->calculate_totals();

            // Set appropriate status and redirect based on total
            if ($order->get_total() == 0) {
                $order->update_status('completed');
                $order->payment_complete();
                $response['redirect'] = $order->get_checkout_order_received_url();
            } else {
                $order->update_status('pending');
                $response['redirect'] = add_query_arg(
                    ['pay_for_order' => 'true', 'key' => $order->get_order_key()],
                    $order->get_checkout_payment_url()
                );
            }

            // Save the order
            $order->save();

            // Clear cart and session data
            WC()->cart->empty_cart();
            WC()->session->set('ypf_checkout_form_data', null);

            $response['success'] = true;
            wp_send_json($response);

        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            wp_send_json($response);
        }
    }

    public function clear_notices_on_order_pay() {
        if (is_wc_endpoint_url('order-pay')) {
            wc_clear_notices();
        }
    }
}