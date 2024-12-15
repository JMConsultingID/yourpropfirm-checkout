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
        wp_enqueue_style('ypf-checkout-css', YPF_CHECKOUT_URL . 'assets/css/yourpropfirm-checkout.css', [], YPF_CHECKOUT_VERSION);
        wp_enqueue_script('ypf-checkout-js', YPF_CHECKOUT_URL . 'assets/js/yourpropfirm-checkout.js', ['jquery'], YPF_CHECKOUT_VERSION, true);
    }

    /**
     * Render the custom billing form.
     */
	public function render_billing_form() {
		if (WC()->cart->is_empty()) {
	        // Display a WooCommerce notice
	        wc_add_notice(__('Your cart is empty. Please add items to your cart before proceeding.', 'yourpropfirm-checkout'), 'error');
	        
	        // Redirect to the cart page
	        wp_safe_redirect(wc_get_cart_url());
	        exit;
	    }

	    ob_start();

	    // WooCommerce country and state data
	    $wc_countries = new WC_Countries();
	    $countries = $wc_countries->get_countries();
	    $states = $wc_countries->get_states();

	    ?>
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

	        <label for="state"><?php esc_html_e('State', 'yourpropfirm-checkout'); ?></label>
	        <select name="state" id="state">
	            <option value=""><?php esc_html_e('Select State', 'yourpropfirm-checkout'); ?></option>
	        </select>

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

	    <script>
	        jQuery(document).ready(function ($) {
	            // Update form's target dynamically
	            $('#ypf-billing-form').on('submit', function (e) {

	            	// Get checkbox elements
		        const termsCheckbox = $('#terms');
		        const privacyCheckbox = $('#privacy_policy');
		        
		        // Check if both checkboxes are checked
		        if (!termsCheckbox.is(':checked') || !privacyCheckbox.is(':checked')) {
		            e.preventDefault(); // Prevent form submission
		            
		            // Show error messages
		            if (!termsCheckbox.is(':checked')) {
		                alert('Please accept the Terms and Conditions to proceed.');
		            }
		            if (!privacyCheckbox.is(':checked')) {
		                alert('Please accept the Privacy Policy to proceed.');
		            }
		            
		            return false;
		        }
		        
		        return true; // Allow form submission if both are checked

		            // Set form target to open in a new tab
		            $(this).attr('target', '_blank');

		            // Delay for 2 seconds to allow the new tab to open
		            setTimeout(function () {
		                // Clear all form fields
		                $('#ypf-billing-form').find('input, select').val('');

		                // Redirect to the home page in the current tab
		                window.location.href = "<?php echo esc_url(home_url()); ?>";
		            }, 2000); // 2-second delay
		        });

	            // Dynamic state selection
	            $('#country').on('change', function () {
	                var country = $(this).val();
	                var states = <?php echo json_encode($states); ?>;

	                if (states[country]) {
	                    var options = '<option value=""><?php esc_html_e('Select State', 'yourpropfirm-checkout'); ?></option>';
	                    $.each(states[country], function (key, value) {
	                        options += '<option value="' + key + '">' + value + '</option>';
	                    });
	                    $('#state').html(options).prop('disabled', false);
	                } else {
	                    $('#state').html('<option value=""><?php esc_html_e('No states available', 'yourpropfirm-checkout'); ?></option>').prop('disabled', true);
	                }
	            });
	        });
	    </script>
	    <?php

	    return ob_get_clean();
	}
}