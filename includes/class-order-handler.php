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
        add_action('init', [$this, 'handle_form_submission']);
        // Clear WooCommerce notices on the order-pay page
        // add_action('template_redirect', [$this, 'clear_notices_on_order_pay']);
    }

    /**
     * Handle form submission.
     */
    public function handle_form_submission() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['first_name']) && isset($_POST['email'])) {
            // Verify WooCommerce cart is not empty.
            if (WC()->cart->is_empty()) {
                wc_add_notice(__('Your cart is empty. Please add a product to proceed.', 'yourpropfirm-checkout'), 'error');
                wp_redirect(wc_get_cart_url());
                exit;
            }

            // Verify required fields.
            $required_fields = ['first_name', 'last_name', 'email', 'phone', 'address', 'country', 'city', 'postal_code'];
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    wc_add_notice(__('Please fill in all required fields.', 'yourpropfirm-checkout'), 'error');
                    wp_redirect(wp_get_referer());
                    exit;
                }
            }

            // Sanitize form inputs.
            $data = [
                'first_name'   => sanitize_text_field($_POST['first_name']),
                'last_name'    => sanitize_text_field($_POST['last_name']),
                'email'        => sanitize_email($_POST['email']),
                'phone'        => sanitize_text_field($_POST['phone']),
                'address'      => sanitize_text_field($_POST['address']),
                'country'      => sanitize_text_field($_POST['country']),
                'state'        => isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '',
                'city'         => sanitize_text_field($_POST['city']),
                'postal_code'  => sanitize_text_field($_POST['postal_code']),
            ];

            // Create WooCommerce order.
            $order_id = $this->create_wc_order($data);

            if (is_wp_error($order_id)) {
                wc_add_notice(__('Unable to create order. Please try again.', 'yourpropfirm-checkout'), 'error');
                wp_redirect(wp_get_referer());
                exit;
            }

            // Redirect to the order-pay page.
            $order = wc_get_order($order_id);
            $order_pay_url = add_query_arg(
			    ['pay_for_order' => 'true', 'key' => $order->get_order_key()],
			    $order->get_checkout_payment_url()
			);

            wp_redirect($order_pay_url);
            exit;
        }
    }

    /**
     * Clear WooCommerce notices on the order-pay page.
     */
    public function clear_notices_on_order_pay() {
        if (is_wc_endpoint_url('order-pay')) {
            wc_clear_notices();
        }
    }

    /**
     *
     * @param array $data User billing data.
     * @return int|\WP_Error Order ID or error.
     */
    private function create_wc_order($data) {
	    try {
	        // Step 1: Check if the customer already exists
	        $customer_id = email_exists($data['email']); // Check if email is registered
	        if (!$customer_id) {
	            // Step 2: Create a new customer if it doesn't exist
	            $random_password = wp_generate_password(); // Generate a random password
	            $username = sanitize_user(current(explode('@', $data['email']))); // Use the part before "@" as username

	            $customer_id = wc_create_new_customer($data['email'], $username, $random_password);

	            if (is_wp_error($customer_id)) {
	                throw new Exception(__('Unable to create customer: ', 'yourpropfirm-checkout') . $customer_id->get_error_message());
	            }

	            // Optionally send an email to the new customer with their account credentials
	            wp_new_user_notification($customer_id, null, 'both');
	        }

	        // Step 3: Initialize the WooCommerce order
	        $order = wc_create_order();

	        // Step 4: Add cart items to the order
	        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
	            $product = $cart_item['data'];
	            $quantity = $cart_item['quantity'];
	            $order->add_product($product, $quantity);
	        }

	        // Step 5: Set billing fields
	        $order->set_billing_first_name($data['first_name']);
	        $order->set_billing_last_name($data['last_name']);
	        $order->set_billing_email($data['email']);
	        $order->set_billing_phone($data['phone']);
	        $order->set_billing_address_1($data['address']);
	        $order->set_billing_city($data['city']);
	        $order->set_billing_postcode($data['postal_code']);
	        $order->set_billing_country($data['country']);
	        $order->set_billing_state($data['state']);

	        // Step 6: Attach the order to the customer
	        $order->set_customer_id($customer_id);

	        // Step 7: Set order status to pending payment
	        $order->set_status('pending');

	        // Step 8: Calculate totals and save the order
	        $order->calculate_totals();
	        $order->save();

	        // Step 9: Clear the WooCommerce cart
	        WC()->cart->empty_cart();

	        return $order->get_id();
	    } catch (Exception $e) {
	        return new WP_Error('order_error', $e->getMessage());
	    }
	}
}
