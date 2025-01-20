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

}