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
    /**
     * Constructor: Hook into form submission handling.
     */
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

    /**
     * Handle form submission.
     */
     public function handle_form_submission() {
        check_ajax_referer('ypf_checkout_nonce', 'nonce');

        $response = [
            'success' => false,
            'redirect' => ''
        ];

        // Store form data in session
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

        // Check Terms and Privacy Policy
        if (empty($_POST['terms']) || empty($_POST['privacy_policy'])) {
            wc_add_notice(__('Please accept the Terms and Conditions and Privacy Policy to proceed.', 'yourpropfirm-checkout'), 'error');
            wp_send_json($response);
            return;
        }

        // Handle coupon if provided
        if (!empty($_POST['coupon_code'])) {
            $coupon_code = sanitize_text_field($_POST['coupon_code']);
            $coupon_result = $this->apply_coupon($coupon_code);
            
            if (is_wp_error($coupon_result)) {
                wc_add_notice($coupon_result->get_error_message(), 'error');
                wp_send_json($response);
                return;
            }
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

        // Create order
        $order_id = $this->create_wc_order($form_data);

        if (is_wp_error($order_id)) {
            wc_add_notice($order_id->get_error_message(), 'error');
            wp_send_json($response);
            return;
        }

        $order = wc_get_order($order_id);
        
        if ($order->get_total() == 0) {
            $order->payment_complete();
            $response['success'] = true;
            $response['redirect'] = $order->get_checkout_order_received_url();
            // Clear session data on success
            WC()->session->set('ypf_checkout_form_data', null);
        } else {
            $response['success'] = true;
            $response['redirect'] = add_query_arg(
                ['pay_for_order' => 'true', 'key' => $order->get_order_key()],
                $order->get_checkout_payment_url()
            );
            // Clear session data on success
            WC()->session->set('ypf_checkout_form_data', null);
        }

        // Ensure notices are stored in session
        WC()->session->set('wc_notices', wc_get_notices());

        wp_send_json($response);
    }

    private function apply_coupon($coupon_code) {
        $coupon = new WC_Coupon($coupon_code);
        
        if (!$coupon->is_valid()) {
            return new WP_Error('invalid_coupon', __('Invalid coupon code.', 'yourpropfirm-checkout'));
        }

        $result = WC()->cart->apply_coupon($coupon_code);
        
        if (is_wp_error($result)) {
            return $result;
        }

        return true;
    }

    /**
     * Clear WooCommerce notices on the order-pay page.
     */
    public function clear_notices_on_order_pay() {
        if (is_wc_endpoint_url('order-pay')) {
            // Get all current notices
            $all_notices = wc_get_notices();

            // Preserve specific notices (e.g., error and success notices for coupons)
            $preserved_notices = [];
            if (!empty($all_notices['error'])) {
                $preserved_notices['error'] = $all_notices['error'];
            }

            // Clear all notices
            wc_clear_notices();

            // Add back the preserved notices
            foreach ($preserved_notices as $type => $notices) {
                foreach ($notices as $notice) {
                    wc_add_notice($notice['notice'], $type);
                }
            }
        }
    }


    /**
     *
     * @param array $data User billing data.
     * @return int|\WP_Error Order ID or error.
     */
    private function create_wc_order($data) {
        try {
            // Initialize WooCommerce order.
            $order = wc_create_order();

            // Add cart items to the order.
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];
                $quantity = $cart_item['quantity'];
                $order->add_product($product, $quantity);
            }

            // Set billing fields.
            $order->set_billing_first_name($data['first_name']);
            $order->set_billing_last_name($data['last_name']);
            $order->set_billing_email($data['email']);
            $order->set_billing_phone($data['phone']);
            $order->set_billing_address_1($data['address']);
            $order->set_billing_city($data['city']);
            $order->set_billing_postcode($data['postal_code']);
            $order->set_billing_country($data['country']);
            $order->set_billing_state($data['state']);

            // Attach the order to the logged-in customer (if logged in).
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                $order->set_customer_id($user_id);
            } else {
                $order->set_customer_id(0); // Guest order.
            }

            // Apply coupon(s) to the order.
            if (!empty(WC()->cart->get_applied_coupons())) {
                foreach (WC()->cart->get_applied_coupons() as $coupon_code) {
                    $coupon = new WC_Coupon($coupon_code);
                    $order->apply_coupon($coupon);
                }
            }

            // Calculate totals and save order.
            $order->calculate_totals();

            // Check if the total is 0
            if ($order->get_total() == 0) {
                // Mark the order as completed and redirect to the Thank You page
                $order->set_status('completed');
                $order->save();

                // Redirect to the Thank You page
                $thank_you_url = wc_get_endpoint_url('order-received', $order->get_id(), wc_get_checkout_url());
                wp_redirect($thank_you_url);
                exit;
            }

            // If total is not 0, set the order status to pending payment
            $order->set_status('pending');
            $order->save();

            // Clear the WooCommerce cart.
            WC()->cart->empty_cart();

            return $order->get_id();
        } catch (Exception $e) {
            return new WP_Error('order_error', $e->getMessage());
        }
    }
}
