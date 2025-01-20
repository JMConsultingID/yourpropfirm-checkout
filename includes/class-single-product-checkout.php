<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class YourPropfirm_Single_Product_Checkout
 *
 * Handles WooCommerce checkout modifications for single-product checkout mode.
 */
class YourPropfirm_Single_Product_Checkout {

    /**
     * Constructor: Register hooks and filters.
     */
    public function __construct() {
        add_action('init', [$this, 'setup_single_product_checkout_mode']);
        add_action('wp', [$this, 'remove_terms_and_conditions']);
    }

    /**
     * Setup single product checkout mode.
     */
    public function setup_single_product_checkout_mode() {
        // Disable add to cart messages.
        add_filter('wc_add_to_cart_message_html', '__return_false');

        // Remove previous product before adding a new one.
        add_filter('woocommerce_add_cart_item_data', [$this, 'remove_previous_product'], 10, 2);

        // Empty the cart before adding a new product.
        //add_filter('woocommerce_add_cart_item_data', [$this, 'empty_cart_before_adding_product']);

        // Redirect to checkout after adding product.
        add_filter('woocommerce_add_to_cart_redirect', [$this, 'redirect_to_checkout']);

        // Check for multiple products in the cart at checkout.
        //add_action('woocommerce_before_checkout_form', [$this, 'check_for_multiple_products']);

        // Disable order notes field.
        add_filter('woocommerce_enable_order_notes_field', '__return_false');

        // Change the order button text.
        add_filter('woocommerce_order_button_text', [$this, 'customize_order_button_text']);

        // Modify WooCommerce billing fields.
        add_filter('woocommerce_checkout_fields', [$this, 'modify_billing_fields']);

        // Hide specific countries on checkout.
        add_filter('woocommerce_countries', [$this, 'hide_countries_on_checkout'], 10, 1);
    }

    /**
     * Empty the cart before adding a new product.
     */
    public function empty_cart_before_adding_product($cart_item_data) {
        WC()->cart->empty_cart(); // Clear the cart.
        return $cart_item_data;
    }

    /**
     * Remove the previous product before adding a new one.
     */
    public function remove_previous_product($cart_item_key, $product_id) {
        // Loop through the cart and remove all other products
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if ($cart_item['product_id'] != $product_id) {
                WC()->cart->remove_cart_item($cart_item_key);
            }
        }

        // Set the quantity to 1 for the new product
        $cart_item_data['quantity'] = 1;

        return $cart_item_data;
    }


    /**
     * Redirect to checkout after adding a product to the cart.
     */
    public function redirect_to_checkout() {
        return wc_get_checkout_url();
    }

    /**
     * Check for multiple products in the cart and handle refresh.
     */
    public function check_for_multiple_products() {
        if (WC()->cart->get_cart_contents_count() > 1) {
            // Display error notice.
            wc_print_notice(__('Only 1 product can be checked out at a time. Please refresh the cart to keep only the last product.', 'yourpropfirm-checkout'), 'error');

            // Display refresh button.
            echo '<form method="post">';
            echo '<button type="submit" name="refresh_cart" class="button">' . __('Refresh Cart', 'yourpropfirm-checkout') . '</button>';
            echo '</form>';

            // Refresh the cart to keep only the last product.
            if (isset($_POST['refresh_cart'])) {
                $this->refresh_cart_keep_last_product();
            }
        }
    }

    /**
     * Refresh the cart and keep only the last product.
     */
    public function refresh_cart_keep_last_product() {
        $cart_items = WC()->cart->get_cart();

        // Get the last added product key.
        $last_product_key = array_key_last($cart_items);

        // Clear the cart and re-add the last product.
        WC()->cart->empty_cart();
        $last_product = $cart_items[$last_product_key];
        WC()->cart->add_to_cart(
            $last_product['product_id'],
            $last_product['quantity'],
            $last_product['variation_id'],
            $last_product['variation'],
            $last_product['cart_item_data']
        );

        // Refresh the page.
        wp_safe_redirect(wc_get_checkout_url());
        exit;
    }

    /**
     * Customize the WooCommerce order button text.
     */
    public function customize_order_button_text() {
        return __('PROCEED TO PAYMENT', 'yourpropfirm-checkout');
    }

    /**
     * Modify billing fields.
     */
    public function modify_billing_fields($fields) {
        $fields['billing']['billing_email']['priority'] = 5;
        return $fields;
    }

    /**
     * Remove WooCommerce terms and conditions from the checkout page.
     */
    public function remove_terms_and_conditions() {
        remove_action('woocommerce_checkout_terms_and_conditions', 'wc_terms_and_conditions_page_content', 30);
    }

    /**
     * Hide specific countries on WooCommerce checkout.
     */
    public function hide_countries_on_checkout($countries) {
        $countries_to_hide = []; // Add the country codes you want to hide.

        foreach ($countries_to_hide as $country_code) {
            if (isset($countries[$country_code])) {
                unset($countries[$country_code]);
            }
        }

        return $countries;
    }
}