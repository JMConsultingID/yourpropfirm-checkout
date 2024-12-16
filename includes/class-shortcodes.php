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
	    // Enqueue custom plugin styles
	    wp_enqueue_style('ypf-checkout-css', YPF_CHECKOUT_URL . 'assets/css/yourpropfirm-checkout.css', [], YPF_CHECKOUT_VERSION);
	    // Enqueue Bootstrap 5 CSS
	    wp_enqueue_style(
	        'bootstrap-css',
	        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css',
	        [],
	        '5.3.0-alpha1'
	    );
	    // Enqueue custom plugin scripts
	    wp_enqueue_script('ypf-checkout-js', YPF_CHECKOUT_URL . 'assets/js/yourpropfirm-checkout.js', ['jquery'], YPF_CHECKOUT_VERSION, true);
	    // Enqueue Bootstrap 5 JS (Optional, for interactive components like modals or dropdowns)
	    wp_enqueue_script(
	        'bootstrap-js',
	        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js',
	        ['jquery'],
	        '5.3.0-alpha1',
	        true
	    );
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
	    // Display WooCommerce notices
	    if (function_exists('wc_print_notices')) {
	        wc_print_notices();
	    }
	    // WooCommerce country and state data
	    $wc_countries = new WC_Countries();
	    $countries = $wc_countries->get_countries();
	    $states = $wc_countries->get_states();
	    ?>

	    <div class="ypf-cart-review mb-4">
		    <h3 class="mb-3"><?php esc_html_e('Order Summary', 'yourpropfirm-checkout'); ?></h3>
		    <table class="table table-bordered">
		        <thead class="table-light">
		            <tr>
		                <th><?php esc_html_e('Product', 'yourpropfirm-checkout'); ?></th>
		                <th><?php esc_html_e('Subtotal', 'yourpropfirm-checkout'); ?></th>
		            </tr>
		        </thead>
		        <tbody>
		            <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) : 
		                $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
		                $product_name = $_product->get_name(); ?>
		                <tr>
		                    <td>
		                        <?php 
		                        echo wp_kses_post($product_name);
		                        echo ' × ' . $cart_item['quantity'];
		                        ?>
		                    </td>
		                    <td>
		                        <?php echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); ?>
		                    </td>
		                </tr>
		            <?php endforeach; ?>
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

		<form id="ypf-billing-form" method="post" class="needs-validation" novalidate>
		    <h3 class="mb-3"><?php esc_html_e('Billing Information', 'yourpropfirm-checkout'); ?></h3>
		    <div class="row g-3">
		        <!-- First Name -->
		        <div class="col-md-6">
		            <label for="first_name" class="form-label"><?php esc_html_e('First Name', 'yourpropfirm-checkout'); ?></label>
		            <input type="text" name="first_name" id="first_name" class="form-control" required>
		            <div class="invalid-feedback"><?php esc_html_e('Please enter your first name.', 'yourpropfirm-checkout'); ?></div>
		        </div>

		        <!-- Last Name -->
		        <div class="col-md-6">
		            <label for="last_name" class="form-label"><?php esc_html_e('Last Name', 'yourpropfirm-checkout'); ?></label>
		            <input type="text" name="last_name" id="last_name" class="form-control" required>
		            <div class="invalid-feedback"><?php esc_html_e('Please enter your last name.', 'yourpropfirm-checkout'); ?></div>
		        </div>

		        <!-- Email -->
		        <div class="col-md-6">
		            <label for="email" class="form-label"><?php esc_html_e('Email', 'yourpropfirm-checkout'); ?></label>
		            <input type="email" name="email" id="email" class="form-control" required>
		            <div class="invalid-feedback"><?php esc_html_e('Please enter a valid email address.', 'yourpropfirm-checkout'); ?></div>
		        </div>

		        <!-- Phone -->
		        <div class="col-md-6">
		            <label for="phone" class="form-label"><?php esc_html_e('Phone Number', 'yourpropfirm-checkout'); ?></label>
		            <input type="text" name="phone" id="phone" class="form-control" required>
		            <div class="invalid-feedback"><?php esc_html_e('Please enter your phone number.', 'yourpropfirm-checkout'); ?></div>
		        </div>

		        <!-- Address -->
		        <div class="col-12">
		            <label for="address" class="form-label"><?php esc_html_e('Address', 'yourpropfirm-checkout'); ?></label>
		            <input type="text" name="address" id="address" class="form-control" required>
		            <div class="invalid-feedback"><?php esc_html_e('Please enter your address.', 'yourpropfirm-checkout'); ?></div>
		        </div>

		        <!-- Country -->
		        <div class="col-md-6">
		            <label for="country" class="form-label"><?php esc_html_e('Country', 'yourpropfirm-checkout'); ?></label>
		            <select name="country" id="country" class="form-select" required>
		                <option value=""><?php esc_html_e('Select Country', 'yourpropfirm-checkout'); ?></option>
		                <?php foreach ($countries as $code => $name) : ?>
		                    <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
		                <?php endforeach; ?>
		            </select>
		            <div class="invalid-feedback"><?php esc_html_e('Please select your country.', 'yourpropfirm-checkout'); ?></div>
		        </div>

		        <!-- State -->
		        <div class="col-md-6">
		            <label for="state" class="form-label"><?php esc_html_e('State/Region', 'yourpropfirm-checkout'); ?></label>
		            <div id="state-container">
		                <input type="text" name="state" id="state" class="form-control" required>
		            </div>
		            <div class="invalid-feedback"><?php esc_html_e('Please enter your state/region.', 'yourpropfirm-checkout'); ?></div>
		        </div>

		        <!-- City -->
		        <div class="col-md-6">
		            <label for="city" class="form-label"><?php esc_html_e('City', 'yourpropfirm-checkout'); ?></label>
		            <input type="text" name="city" id="city" class="form-control" required>
		            <div class="invalid-feedback"><?php esc_html_e('Please enter your city.', 'yourpropfirm-checkout'); ?></div>
		        </div>

		        <!-- Postal Code -->
		        <div class="col-md-6">
		            <label for="postal_code" class="form-label"><?php esc_html_e('Postal Code', 'yourpropfirm-checkout'); ?></label>
		            <input type="text" name="postal_code" id="postal_code" class="form-control" required>
		            <div class="invalid-feedback"><?php esc_html_e('Please enter your postal code.', 'yourpropfirm-checkout'); ?></div>
		        </div>

		        <!-- Coupon Code -->
		        <div class="col-12">
		            <label for="coupon_code" class="form-label"><?php esc_html_e('Coupon Code', 'yourpropfirm-checkout'); ?></label>
		            <input type="text" name="coupon_code" id="coupon_code" class="form-control" placeholder="<?php esc_html_e('Enter Coupon Code', 'yourpropfirm-checkout'); ?>">
		        </div>

		        <!-- Terms and Conditions -->
			    <div class="form-check mb-2">
			        <input class="form-check-input" type="checkbox" name="terms" id="terms" required>
			        <label class="form-check-label" for="terms">
			            <?php printf(
			                __('I agree to the <a href="%s" target="_blank">Terms and Conditions</a>', 'yourpropfirm-checkout'),
			                esc_url(get_permalink(wc_get_page_id('terms')))
			            ); ?>
			        </label>
			        <div class="invalid-feedback">
			            <?php esc_html_e('You must agree to the terms and conditions.', 'yourpropfirm-checkout'); ?>
			        </div>
			    </div>

			    <!-- Privacy Policy -->
			    <div class="form-check">
			        <input class="form-check-input" type="checkbox" name="privacy_policy" id="privacy_policy" required>
			        <label class="form-check-label" for="privacy_policy">
			            <?php printf(
			                __('I have read and agree to the <a href="%s" target="_blank">Privacy Policy</a>', 'yourpropfirm-checkout'),
			                esc_url(get_privacy_policy_url())
			            ); ?>
			        </label>
			        <div class="invalid-feedback">
			            <?php esc_html_e('You must agree to the privacy policy.', 'yourpropfirm-checkout'); ?>
			        </div>
			    </div>

		        <!-- Submit Button -->
		        <div class="col-12 text-center">
		            <button type="submit" id="ypf-submit-button" class="btn btn-primary mt-4"><?php esc_html_e('Proceed With Payment', 'yourpropfirm-checkout'); ?></button>
		        </div>
		    </div>
		</form>

	    <?php
	    return ob_get_clean();
	}
}