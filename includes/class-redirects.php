<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Yourpropfirm_Checkout_Redirects
 *
 * Redirect users from the default WooCommerce checkout page to the custom billing form page.
 */
class Yourpropfirm_Checkout_Redirects {
    /**
     * Constructor: Hook into WooCommerce template redirect.
     */
    public function __construct() {
        add_action('template_redirect', [$this, 'redirect_default_checkout']);
    }

    /**
     * Redirect default WooCommerce checkout to the custom billing form page.
     */
    public function redirect_default_checkout() {
        // Check if WooCommerce checkout page is being accessed.
        if (is_checkout() && !is_wc_endpoint_url() && !isset($_GET['pay_for_order'])) {
            // Replace with your custom billing form page URL.
            $custom_checkout_url = home_url('/order/'); // Update the slug if necessary.

            wp_safe_redirect($custom_checkout_url);
            exit;
        }
    }
}
