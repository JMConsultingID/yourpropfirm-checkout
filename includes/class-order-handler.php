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
        add_action('template_redirect', [$this, 'clear_notices_on_order_pay']);
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

            // Check if Terms and Privacy Policy are accepted
            if (empty($_POST['terms']) || empty($_POST['privacy_policy'])) {
                wc_add_notice(__('Please accept the Terms and Conditions and Privacy Policy to proceed.', 'yourpropfirm-checkout'), 'error');
                wp_redirect(wp_get_referer());
                exit;
            }

            // Validate and apply coupon code if provided
            if (!empty($_POST['coupon_code'])) {
                $coupon_code = sanitize_text_field($_POST['coupon_code']);

                // Check if the coupon is valid
                $coupon = new WC_Coupon($coupon_code);
                $coupon_validation = WC()->cart->apply_coupon($coupon_code);

                if (is_wp_error($coupon_validation)) {
                    // Display the error message from WooCommerce
                    wc_add_notice($coupon_validation->get_error_message(), 'error');
                   // Output JavaScript for redirection
                    echo '<script type="text/javascript">
                            window.location.href = "' . esc_url(wp_get_referer() ?: home_url('/order/')) . '";
                          </script>';
                    exit;
                }

                // Check if the coupon was successfully applied
                if (!WC()->cart->has_discount($coupon_code)) {
                    wc_add_notice(__('Invalid coupon code.', 'yourpropfirm-checkout'), 'error');
                    // Output JavaScript for redirection
                    echo '<script type="text/javascript">
                            window.location.href = "' . esc_url(wp_get_referer() ?: home_url('/order/')) . '";
                          </script>';
                    exit;
                }

                // Add a success message
                wc_add_notice(__('Coupon applied successfully!', 'yourpropfirm-checkout'), 'success');
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

            // Output JavaScript for redirection and form clearing
            echo '<script type="text/javascript">
                    // Open Order Pay page in a new tab
                    window.open("' . esc_url($order_pay_url) . '", "_blank");

                    // Delay before clearing form fields and redirecting back to the /order/ page
                    setTimeout(function () {
                        // Clear all form fields
                        const form = document.getElementById("ypf-billing-form");
                        if (form) {
                            form.reset();
                        }

                        // Redirect to the current page (/order/)
                        window.location.href = "' . esc_url(wp_get_referer()) . '";
                    }, 2000); // 2-second delay
                  </script>';
            exit;
        }
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
