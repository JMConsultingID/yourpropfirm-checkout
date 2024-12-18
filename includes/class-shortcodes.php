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
    	$form_data = WC()->session->get('ypf_checkout_form_data', array());

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
        	'states' => $wc_countries->get_states(),
	        'select_state_text' => __('Select State', 'yourpropfirm-checkout'),
	        'enter_state_text'  => __('Enter State/Region', 'yourpropfirm-checkout'), // For text input placeholder
	        'no_states_text'    => __('No states available', 'yourpropfirm-checkout'),
	        'ajax_url' 			=> admin_url('admin-ajax.php'),
            'checkout_nonce' 	=> wp_create_nonce('ypf_checkout_nonce'),
            'order_page_url' 	=> get_permalink(get_page_by_path('order')),
            'saved_state'       => isset($form_data['state']) ? $form_data['state'] : '',
        	'saved_country'     => isset($form_data['country']) ? $form_data['country'] : ''
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
	    // Get stored form data
        $form_data = WC()->session->get('ypf_checkout_form_data', array());

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

	    <div class="ypf-steps mb-4">
		    <div class="step-card">
		        <!-- Current Step -->
		        <div class="current-step-container">
		            <div class="step-label">You Are Here</div>
		            <div class="step-text">
		                <span>1.</span>Billing Information
		            </div>
		        </div>

		        <!-- Next Step -->
		        <div class="next-step-container">
		            <div class="step-label">Next Step</div>
		            <div class="step-text">
		                <span>2.</span>Review Order & Payment
		            </div>
		        </div>
		    </div>
		</div>

		<style>
		.ypf-steps {
		    margin: 20px 0;
		}

		.step-card {
		    display: flex;
		    border-radius: 16px;
		    overflow: hidden;
		    background: #1e1f2d;
		    border: 1px solid rgba(88, 88, 115, 0.3);
		    box-shadow: 0 0 0 1px rgba(88, 88, 115, 0.1);
		}

		.current-step-container {
		    flex: 1;
		    padding: 16px 24px;
		    background: linear-gradient(90deg, 
		        rgba(79, 107, 182, 0.2) 0%,
		        rgba(79, 107, 182, 0.1) 100%
		    );
		    position: relative;
		}

		.current-step-container::after {
		    content: '';
		    position: absolute;
		    left: 0;
		    right: 0;
		    bottom: 0;
		    height: 1px;
		    background: linear-gradient(90deg, 
		        rgba(123, 97, 255, 0.5) 0%, 
		        rgba(123, 97, 255, 0) 100%
		    );
		}

		.next-step-container {
		    flex: 1;
		    padding: 16px 24px;
		    background: linear-gradient(90deg, 
		        rgba(47, 46, 65, 0.3) 0%, 
		        rgba(47, 46, 65, 0.1) 100%
		    );
		}

		.step-label {
		    font-size: 12px;
		    color: rgba(255, 255, 255, 0.5);
		    margin-bottom: 4px;
		    text-transform: uppercase;
		    letter-spacing: 0.5px;
		}

		.step-text {
		    color: #fff;
		    font-size: 16px;
		    font-weight: 500;
		}

		.next-step-container .step-text {
		    color: rgba(255, 255, 255, 0.5);
		}

		.step-text span {
		    margin-right: 4px;
		}

		@media (max-width: 576px) {
		    .step-card {
		        flex-direction: column;
		    }
		    
		    .current-step-container,
		    .next-step-container {
		        padding: 12px 16px;
		    }

		    .current-step-container::after {
		        display: none;
		    }
		}
		</style>

	    <div class="yourpropfirm-checkout-container mt-4">

		<form id="ypf-billing-form" method="post" class="needs-validation" novalidate>
		    <h3 class="mb-3"><?php esc_html_e('Billing Information', 'yourpropfirm-checkout'); ?></h3>
		    <div class="row g-3">
		        <!-- First Name -->
		        <div class="col-md-6">
		            <label for="first_name" class="form-label"><?php esc_html_e('First Name', 'yourpropfirm-checkout'); ?></label>
		            <input type="text" name="first_name" id="first_name" class="form-control" value="<?php echo esc_attr($form_data['first_name'] ?? ''); ?>" required>
		            <div class="invalid-feedback"><?php esc_html_e('Please enter your first name.', 'yourpropfirm-checkout'); ?></div>
		        </div>

		        <!-- Last Name -->
		        <div class="col-md-6">
		            <label for="last_name" class="form-label"><?php esc_html_e('Last Name', 'yourpropfirm-checkout'); ?></label>
		            <input type="text" name="last_name" id="last_name" class="form-control" value="<?php echo esc_attr($form_data['last_name'] ?? ''); ?>" required>
		            <div class="invalid-feedback"><?php esc_html_e('Please enter your last name.', 'yourpropfirm-checkout'); ?></div>
		        </div>

		        <!-- Email -->
		        <div class="col-md-6">
		            <label for="email" class="form-label"><?php esc_html_e('Email', 'yourpropfirm-checkout'); ?></label>
		            <input type="email" name="email" id="email" class="form-control" value="<?php echo esc_attr($form_data['email'] ?? ''); ?>" required>
		            <div class="invalid-feedback"><?php esc_html_e('Please enter a valid email address.', 'yourpropfirm-checkout'); ?></div>
		        </div>

		        <!-- Phone -->
		        <div class="col-md-6">
		            <label for="phone" class="form-label"><?php esc_html_e('Phone Number', 'yourpropfirm-checkout'); ?></label>
		            <input type="text" name="phone" id="phone" class="form-control" value="<?php echo esc_attr($form_data['phone'] ?? ''); ?>" required>
		            <div class="invalid-feedback"><?php esc_html_e('Please enter your phone number.', 'yourpropfirm-checkout'); ?></div>
		        </div>

		        <!-- Address -->
		        <div class="col-12">
		            <label for="address" class="form-label"><?php esc_html_e('Address', 'yourpropfirm-checkout'); ?></label>
		            <input type="text" name="address" id="address" class="form-control" value="<?php echo esc_attr($form_data['address'] ?? ''); ?>" required>
		            <div class="invalid-feedback"><?php esc_html_e('Please enter your address.', 'yourpropfirm-checkout'); ?></div>
		        </div>

		        <!-- Country -->
		        <div class="col-md-6">
		            <label for="country" class="form-label"><?php esc_html_e('Country', 'yourpropfirm-checkout'); ?></label>
		            <select name="country" id="country" class="form-select" value="<?php echo esc_attr($form_data['first_name'] ?? ''); ?>" required>
		                <option value=""><?php esc_html_e('Select Country', 'yourpropfirm-checkout'); ?></option>
		                <?php 
		                $selected_country = $form_data['country'] ?? '';
		                ?>
		                <?php foreach ($countries as $code => $name) : ?>
		                    <option value="<?php echo esc_attr($code); ?>" 
                            	<?php selected($selected_country, $code); ?>>
		                        <?php echo esc_html($name); ?>
		                    </option>
		                <?php endforeach; ?>
		            </select>
		            <div class="invalid-feedback"><?php esc_html_e('Please select your country.', 'yourpropfirm-checkout'); ?></div>
		        </div>

		        <!-- State -->
				<div class="col-md-6">
				    <label for="state" class="form-label"><?php esc_html_e('State/Region', 'yourpropfirm-checkout'); ?></label>
				    <div id="state-container">
				        <!-- State field will be dynamically inserted here -->
				    </div>
				    <div class="invalid-feedback"><?php esc_html_e('Please enter your state/region.', 'yourpropfirm-checkout'); ?></div>
				</div>

		        <!-- City -->
		        <div class="col-md-6">
		            <label for="city" class="form-label"><?php esc_html_e('City', 'yourpropfirm-checkout'); ?></label>
		            <input type="text" name="city" id="city" class="form-control" value="<?php echo esc_attr($form_data['city'] ?? ''); ?>" required>
		            <div class="invalid-feedback"><?php esc_html_e('Please enter your city.', 'yourpropfirm-checkout'); ?></div>
		        </div>

		        <!-- Postal Code -->
		        <div class="col-md-6">
		            <label for="postal_code" class="form-label"><?php esc_html_e('Postal Code', 'yourpropfirm-checkout'); ?></label>
		            <input type="text" name="postal_code" id="postal_code" class="form-control" value="<?php echo esc_attr($form_data['postal_code'] ?? ''); ?>" required>
		            <div class="invalid-feedback"><?php esc_html_e('Please enter your postal code.', 'yourpropfirm-checkout'); ?></div>
		        </div>

		        

		        <div class="d-flex justify-content-center my-4">
				    <div class="card text-white bg-dark p-3 shadow-sm" style="width: 80%;">
				        <div class="card-body">

				            <?php
				            // Get the last item in cart
				            $cart_items = WC()->cart->get_cart();
				            $last_item = end($cart_items);
				            
				            if ($last_item) {
				                $_product = apply_filters('woocommerce_cart_item_product', $last_item['data'], $last_item, key($cart_items));
				                $product_name = $_product->get_name();
				                ?>
				                <div class="d-flex flex-rowmb-3">
				                    <div>
				                        <strong><?php echo wp_kses_post($product_name); ?></strong>
				                    </div>
				                    <div>
				                    	<strong>
				                        <?php 
				                        echo apply_filters(
				                            'woocommerce_cart_item_subtotal',
				                            WC()->cart->get_product_subtotal($_product, $last_item['quantity']),
				                            $last_item,
				                            key($cart_items)
				                        ); 
				                        ?>
				                        </strong>
				                    </div>
				                </div>
				            <?php } ?>

				        	<!-- Coupon Code -->
					        <div class="col-12">
					            <label for="coupon_code" class="form-label"><?php esc_html_e('Coupon Code', 'yourpropfirm-checkout'); ?></label>
					            <input type="text" name="coupon_code" id="coupon_code" class="form-control" placeholder="<?php esc_html_e('Enter Coupon Code', 'yourpropfirm-checkout'); ?>">
					        </div>
         
				            <!-- Terms and Conditions -->
							<div class="mt-4 mb-3">
							    <div class="d-flex flex-column align-items-center">
							        <div class="form-check">
							            <input class="form-check-input me-2" type="checkbox" name="terms" id="terms" required>
							            <label class="form-check-label" for="terms">
							                <?php printf(
							                    __('I agree to the <a href="%s" target="_blank" class="text-warning">Terms and Conditions</a>', 'yourpropfirm-checkout'),
							                    esc_url(get_permalink(wc_get_page_id('terms')))
							                ); ?>
							            </label>
							            <div class="invalid-feedback">
							                <?php esc_html_e('You must agree to the terms and conditions.', 'yourpropfirm-checkout'); ?>
							            </div>
							        </div>
							    </div>
							</div>

							<!-- Privacy Policy -->
							<div class="mb-3">
							    <div class="d-flex flex-column align-items-center">
							        <div class="form-check">
							            <input class="form-check-input me-2" type="checkbox" name="privacy_policy" id="privacy_policy" required>
							            <label class="form-check-label" for="privacy_policy">
							                <?php printf(
							                    __('I have read and agree to the <a href="%s" target="_blank" class="text-warning">Privacy Policy</a>', 'yourpropfirm-checkout'),
							                    esc_url(get_privacy_policy_url())
							                ); ?>
							            </label>
							            <div class="invalid-feedback">
							                <?php esc_html_e('You must agree to the privacy policy.', 'yourpropfirm-checkout'); ?>
							            </div>
							        </div>
							    </div>
							</div>

				            <!-- Submit Button -->
					        <div class="col-12 text-center">
					            <button type="submit" id="ypf-submit-button" class="btn btn-primary mt-4"><?php esc_html_e('Proceed With Payment', 'yourpropfirm-checkout'); ?></button>
					        </div>
				        </div>
				    </div>
				</div>


		        
		    </div>
		</form>

		</div>

	    <?php
	    return ob_get_clean();
	}
}