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
 * Class Yourpropfirm_Checkout_Shortcodes
 *
 * Handles the custom shortcode for displaying the billing form.
 */
class Yourpropfirm_Checkout_Shortcodes {
    /**
     * Constructor: Register the shortcode.
     */
    public function __construct() {
        add_shortcode('ypf_custom_billing_form', [$this, 'render_billing_form']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Enqueue CSS and JS for the billing form.
     */
    public function enqueue_assets() {
	    // Enqueue styles
	    wp_enqueue_style('ypf-checkout-css', YPF_CHECKOUT_URL . 'assets/css/yourpropfirm-checkout.css', [], YPF_CHECKOUT_VERSION);
	    // Enqueue scripts
	    wp_enqueue_script('ypf-checkout-js', YPF_CHECKOUT_URL . 'assets/js/yourpropfirm-checkout.js', ['jquery'], YPF_CHECKOUT_VERSION, true);
	    // Pass PHP data to JavaScript
	    $wc_countries = new WC_Countries();
	    wp_localize_script('ypf-checkout-js', 'ypf_data', [
	        'home_url'          => esc_url(home_url()),
	        'states'            => $wc_countries->get_states(),
	        'select_state_text' => __('Select State', 'yourpropfirm-checkout'),
	        'enter_state_text'  => __('Enter State/Region', 'yourpropfirm-checkout'), // For text input placeholder
	        'no_states_text'    => __('No states available', 'yourpropfirm-checkout'),
	    ]);
	}

    /**
     * Render the custom billing form.
     */
	public function render_billing_form() {
		if (WC()->cart->is_empty()) {
	        // Display a WooCommerce notice
	        wc_add_notice(__('Your cart is empty. Please add items to your cart before proceeding.', 'yourpropfirm-checkout'), 'error');
	        // Ensure WooCommerce session persists notices across requests
    		WC()->session->set('wc_notices', wc_get_notices());
	        
	        // Redirect to the cart page
	        wp_safe_redirect(wc_get_cart_url());
	        // Output JavaScript for fallback redirection
		    echo '<script>window.location.href = "' . esc_url(wc_get_cart_url()) . '";</script>';
		    exit;
	    }

	    ob_start();

	    // WooCommerce country and state data
	    $wc_countries = new WC_Countries();
	    $countries = $wc_countries->get_countries();
	    $states = $wc_countries->get_states();

	    ?>

	    <div class="ypf-cart-review">
	        <h3><?php esc_html_e('Order Summary', 'yourpropfirm-checkout'); ?></h3>
	        <table class="shop_table shop_table_responsive">
	            <thead>
	                <tr>
	                    <th><?php esc_html_e('Product', 'yourpropfirm-checkout'); ?></th>
	                    <th><?php esc_html_e('Subtotal', 'yourpropfirm-checkout'); ?></th>
	                </tr>
	            </thead>
	            <tbody>
	                <?php
	                foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
	                    $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
	                    $product_name = $_product->get_name();
	                    ?>
	                    <tr>
	                        <td>
	                            <?php 
	                            echo wp_kses_post($product_name); 
	                            echo ' × ' . $cart_item['quantity'];
	                            ?>
	                        </td>
	                        <td>
	                            <?php
	                            echo apply_filters('woocommerce_cart_item_subtotal', 
	                                WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), 
	                                $cart_item, 
	                                $cart_item_key
	                            );
	                            ?>
	                        </td>
	                    </tr>
	                    <?php
	                }
	                ?>
	            </tbody>
	            <tfoot>
	                <tr>
	                    <th><?php esc_html_e('Subtotal', 'yourpropfirm-checkout'); ?></th>
	                    <td><?php echo WC()->cart->get_cart_subtotal(); ?></td>
	                </tr>
	                <?php if (WC()->cart->get_total_tax() > 0) : ?>
	                    <tr>
	                        <th><?php esc_html_e('Tax', 'yourpropfirm-checkout'); ?></th>
	                        <td><?php echo WC()->cart->get_total_tax(); ?></td>
	                    </tr>
	                <?php endif; ?>
	                <tr>
	                    <th><?php esc_html_e('Total', 'yourpropfirm-checkout'); ?></th>
	                    <td><?php echo WC()->cart->get_total(); ?></td>
	                </tr>
	            </tfoot>
	        </table>
	    </div>

	    <form id="ypf-billing-form" method="post" target="_blank"> <!-- Add target="_blank" -->
	        <h3><?php esc_html_e('Billing Information', 'yourpropfirm-checkout'); ?></h3>
	        
	        <!-- First Name and Last Name -->
	        <label for="first_name"><?php esc_html_e('First Name', 'yourpropfirm-checkout'); ?></label>
	        <input type="text" name="first_name" id="first_name" required>
	        
	        <label for="last_name"><?php esc_html_e('Last Name', 'yourpropfirm-checkout'); ?></label>
	        <input type="text" name="last_name" id="last_name" required>

	        <!-- Email and Phone -->
	        <label for="email"><?php esc_html_e('Email', 'yourpropfirm-checkout'); ?></label>
	        <input type="email" name="email" id="email" required>

	        <label for="phone"><?php esc_html_e('Phone Number', 'yourpropfirm-checkout'); ?></label>
	        <input type="text" name="phone" id="phone" required>

	        <!-- Address -->
	        <label for="address"><?php esc_html_e('Address', 'yourpropfirm-checkout'); ?></label>
	        <input type="text" name="address" id="address" required>

	        <!-- Country and State -->
	        <label for="country"><?php esc_html_e('Country', 'yourpropfirm-checkout'); ?></label>
	        <select name="country" id="country" required>
	            <option value=""><?php esc_html_e('Select Country', 'yourpropfirm-checkout'); ?></option>
	            <?php foreach ($countries as $code => $name) : ?>
	                <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
	            <?php endforeach; ?>
	        </select>

	        <label for="state"><?php esc_html_e('State/Region', 'yourpropfirm-checkout'); ?></label>
			<div id="state-container">
			    <select name="state" id="state" required>
			        <option value=""><?php esc_html_e('Select State', 'yourpropfirm-checkout'); ?></option>
			        <?php
			        $wc_countries = new WC_Countries();
			        foreach ($wc_countries->get_states('US') as $code => $name) : // Default to US states
			            echo '<option value="' . esc_attr($code) . '">' . esc_html($name) . '</option>';
			        endforeach;
			        ?>
			    </select>
			</div>

	        <!-- City and Postal Code -->
	        <label for="city"><?php esc_html_e('City', 'yourpropfirm-checkout'); ?></label>
	        <input type="text" name="city" id="city" required>

	        <label for="postal_code"><?php esc_html_e('Postal Code', 'yourpropfirm-checkout'); ?></label>
	        <input type="text" name="postal_code" id="postal_code" required>

	        <!-- Terms and Conditions -->
	        <div class="terms-and-conditions">
		        <?php
		        // Terms and Conditions checkbox
		        woocommerce_form_field('terms', array(
		            'type' => 'checkbox',
		            'class' => array('form-row-wide', 'terms-field'),
		            'label_class' => array('woocommerce-form__label', 'woocommerce-form__label-for-checkbox', 'checkbox'),
		            'input_class' => array('woocommerce-form__input', 'woocommerce-form__input-checkbox'),
		            'required' => true,
		            'label' => sprintf(
		                __('I agree to the <a href="%s" target="_blank">Terms and Conditions</a>', 'yourpropfirm-checkout'),
		                esc_url(get_permalink(wc_get_page_id('terms')))
		            ),
		        ));

		        // Privacy Policy checkbox
		        woocommerce_form_field('privacy_policy', array(
		            'type' => 'checkbox',
		            'class' => array('form-row-wide', 'privacy-field'),
		            'label_class' => array('woocommerce-form__label', 'woocommerce-form__label-for-checkbox', 'checkbox'),
		            'input_class' => array('woocommerce-form__input', 'woocommerce-form__input-checkbox'),
		            'required' => true,
		            'label' => sprintf(
		                __('I have read and agree to the <a href="%s" target="_blank">Privacy Policy</a>', 'yourpropfirm-checkout'),
		                get_privacy_policy_url()
		            ),
		        ));
		        ?>
		    </div>

	        <!-- Submit Button -->
	        <button type="submit" id="ypf-submit-button"><?php esc_html_e('Proceed', 'yourpropfirm-checkout'); ?></button>
	    </form>
	    <?php

	    return ob_get_clean();
	}
}