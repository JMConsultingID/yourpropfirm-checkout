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

class YourPropFirm_Helper {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'ypf_enqueue_scripts']);
        add_action('init', [$this, 'remove_terms_and_conditions']);
        add_action('woocommerce_checkout_process', [$this, 'ypf_prevent_repurchase_by_category_at_checkout']);
    }

    /**
     * Enqueue CSS and JS for the billing form.
     */
    public function ypf_enqueue_scripts() { 
        $checkout_type = get_option('yourpropfirm_checkout_type', 'default'); // Default to 'default'.            

        // Enqueue Bootstrap 5 CSS
        wp_enqueue_style('ypf-bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css', [], '5.3.0-alpha3');
        wp_enqueue_script('ypf-bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js', ['jquery'], '5.3.0-alpha3', true);

        if ($checkout_type === 'custom') {
            // Enqueue custom plugin styles
            wp_enqueue_style('ypf-checkout-css', YPF_CHECKOUT_URL . 'assets/css/yourpropfirm-checkout.css', [], YPF_CHECKOUT_VERSION);
            // Enqueue custom plugin scripts
            wp_enqueue_script('ypf-checkout-js', YPF_CHECKOUT_URL . 'assets/js/yourpropfirm-checkout.js', ['jquery'], YPF_CHECKOUT_VERSION, true);
            

            // Pass PHP data to JavaScript
            $form_data = WC()->session->get('ypf_checkout_form_data', array());
            $wc_countries = new WC_Countries();
            wp_localize_script('ypf-checkout-js', 'ypf_data', [
                'home_url'          => 'https://forfx.com',
                'states' => $wc_countries->get_states(),
                'select_state_text' => __('Select State', 'yourpropfirm-checkout'),
                'enter_state_text'  => __('Enter State/Region', 'yourpropfirm-checkout'), // For text input placeholder
                'no_states_text'    => __('No states available', 'yourpropfirm-checkout'),
                'ajax_url'          => admin_url('admin-ajax.php'),
                'checkout_nonce'    => wp_create_nonce('ypf_checkout_nonce'),
                'order_page_url'    => get_permalink(get_page_by_path('order')),
                'saved_state'       => isset($form_data['state']) ? $form_data['state'] : '',
                'saved_country'     => isset($form_data['country']) ? $form_data['country'] : ''
            ]);
        } elseif (is_checkout() && $checkout_type === 'default') {         
            wp_enqueue_style('ypf-checkout-woocommerce-css', YPF_CHECKOUT_URL . 'assets/css/yourpropfirm-checkout.css', [], YPF_CHECKOUT_VERSION);
            wp_enqueue_script('ypf-checkout-woocommerce-js', YPF_CHECKOUT_URL . 'assets/js/yourpropfirm-woocommerce.js', ['jquery'], YPF_CHECKOUT_VERSION, true);

            // Localize script for Coupon Ajax
            wp_enqueue_script('ypf-checkout-coupon-ajax', YPF_CHECKOUT_URL . '/assets/js/yourpropfirm-coupon-ajax.js', array('jquery'), YPF_CHECKOUT_VERSION, true);
            wp_localize_script('ypf-checkout-coupon-ajax', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));

            // Localize script for WooCommerce country and state data
            wp_localize_script('ypf-checkout-woocommerce-js', 'wc_country_states', [
                'countries' => WC()->countries->get_allowed_countries(),
                'states' => WC()->countries->get_states(),
            ]);
        }
    }

    /**
     * Remove terms and conditions actions from the WooCommerce checkout.
     */
    public function remove_terms_and_conditions() {
        remove_action('woocommerce_checkout_terms_and_conditions', 'wc_checkout_privacy_policy_text', 20);
        remove_action('woocommerce_checkout_terms_and_conditions', 'wc_terms_and_conditions_page_content', 30);
    }

    

    public function ypf_prevent_repurchase_by_category_at_checkout() {
        // Get the customer's email from the checkout form
        $customer_email = isset($_POST['billing_email']) ? sanitize_email($_POST['billing_email']) : '';

        // If no email is provided, prevent checkout
        if (empty($customer_email)) {
            wc_add_notice(__('Please provide your email address to proceed with the checkout.', 'woocommerce'), 'error');
            return;
        }

        // Get all items in the cart
        $cart_items = WC()->cart->get_cart();

        // Retrieve restricted category IDs from the admin settings
        $restricted_category_ids = explode(',', get_option('yourpropfirm_restricted_category_ids', ''));

        // If no restricted categories are defined, skip the validation
        if (empty($restricted_category_ids)) {
            return;
        }

        // Retrieve all orders made by the customer using their email
        $customer_orders = wc_get_orders([
            'billing_email' => $customer_email,
            'status'        => ['completed', 'processing'], // Check only completed or processing orders
            'limit'         => -1, // Retrieve all matching orders
        ]);

        // Track purchased categories
        $purchased_categories = [];

        // Check previous orders for restricted categories
        foreach ($customer_orders as $order) {
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();

                // Get the product's categories
                $product_categories = wc_get_product_terms($product_id, 'product_cat', ['fields' => 'ids']);

                // Check if the product belongs to a restricted category
                foreach ($restricted_category_ids as $restricted_id) {
                    if (in_array($restricted_id, $product_categories)) {
                        $purchased_categories[] = $restricted_id; // Mark the category as purchased
                    }
                }
            }
        }

        // Prevent checkout if cart contains products from purchased categories
        foreach ($cart_items as $cart_item) {
            $product_id = $cart_item['product_id'];

            // Get the product's categories
            $product_categories = wc_get_product_terms($product_id, 'product_cat', ['fields' => 'ids']);

            // Check if the product belongs to a purchased category
            foreach ($purchased_categories as $purchased_category) {
                if (in_array($purchased_category, $product_categories)) {
                    wc_add_notice(
                        sprintf(
                            __('You have already purchased a product from the category "%s". You cannot purchase any products from this category again.', 'woocommerce'),
                            get_term($purchased_category)->name
                        ),
                        'error'
                    );
                    return;
                }
            }
        }

    }

}