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
        // Redirect and display a notice if the cart is empty.
        if (WC()->cart->is_empty()) {
            wc_add_notice(__('Your cart is empty. Please add items to your cart before proceeding.', 'yourpropfirm-checkout'), 'error');
            wp_safe_redirect(wc_get_cart_url());
            exit;
        }

        // Validate form fields if the form is submitted.
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ypf_submit_billing_form'])) {
            $this->validate_billing_form();
        }

        ob_start();

        // WooCommerce country and state data.
        $wc_countries = new WC_Countries();
        $countries = $wc_countries->get_countries();
        $states = $wc_countries->get_states();

        ?>
        <?php // Display WooCommerce notices (if any). ?>
        <div class="woocommerce-notices-wrapper"><?php wc_print_notices(); ?></div>

        <form id="ypf-billing-form" method="post" target="_blank">
            <h3><?php esc_html_e('Billing Address', 'yourpropfirm-checkout'); ?></h3>

            <!-- First Name -->
            <label for="first_name"><?php esc_html_e('First Name', 'yourpropfirm-checkout'); ?></label>
            <input type="text" name="first_name" id="first_name" value="<?php echo isset($_POST['first_name']) ? esc_attr($_POST['first_name']) : ''; ?>" required>

            <!-- Last Name -->
            <label for="last_name"><?php esc_html_e('Last Name', 'yourpropfirm-checkout'); ?></label>
            <input type="text" name="last_name" id="last_name" value="<?php echo isset($_POST['last_name']) ? esc_attr($_POST['last_name']) : ''; ?>" required>

            <!-- Email -->
            <label for="email"><?php esc_html_e('Email', 'yourpropfirm-checkout'); ?></label>
            <input type="email" name="email" id="email" value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>" required>

            <!-- Phone -->
            <label for="phone"><?php esc_html_e('Phone Number', 'yourpropfirm-checkout'); ?></label>
            <input type="text" name="phone" id="phone" value="<?php echo isset($_POST['phone']) ? esc_attr($_POST['phone']) : ''; ?>" required>

            <!-- Address -->
            <label for="address"><?php esc_html_e('Address', 'yourpropfirm-checkout'); ?></label>
            <input type="text" name="address" id="address" value="<?php echo isset($_POST['address']) ? esc_attr($_POST['address']) : ''; ?>" required>

            <!-- Country -->
            <label for="country"><?php esc_html_e('Country', 'yourpropfirm-checkout'); ?></label>
            <select name="country" id="country" required>
                <option value=""><?php esc_html_e('Select Country', 'yourpropfirm-checkout'); ?></option>
                <?php foreach ($countries as $code => $name) : ?>
                    <option value="<?php echo esc_attr($code); ?>" <?php selected(isset($_POST['country']) ? $_POST['country'] : '', $code); ?>>
                        <?php echo esc_html($name); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- State -->
            <label for="state"><?php esc_html_e('State', 'yourpropfirm-checkout'); ?></label>
            <select name="state" id="state">
                <option value=""><?php esc_html_e('Select State', 'yourpropfirm-checkout'); ?></option>
            </select>

            <!-- City -->
            <label for="city"><?php esc_html_e('City', 'yourpropfirm-checkout'); ?></label>
            <input type="text" name="city" id="city" value="<?php echo isset($_POST['city']) ? esc_attr($_POST['city']) : ''; ?>" required>

            <!-- Postal Code -->
            <label for="postal_code"><?php esc_html_e('Postal Code', 'yourpropfirm-checkout'); ?></label>
            <input type="text" name="postal_code" id="postal_code" value="<?php echo isset($_POST['postal_code']) ? esc_attr($_POST['postal_code']) : ''; ?>" required>

            <!-- Submit Button -->
            <button type="submit" id="ypf-submit-button" name="ypf_submit_billing_form"><?php esc_html_e('Proceed', 'yourpropfirm-checkout'); ?></button>
        </form>

        <script>
            jQuery(document).ready(function ($) {
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

    /**
     * Validate billing form fields and add WooCommerce notices.
     */
    private function validate_billing_form() {
        $required_fields = [
            'first_name' => __('First Name is required.', 'yourpropfirm-checkout'),
            'last_name'  => __('Last Name is required.', 'yourpropfirm-checkout'),
            'email'      => __('Email is required.', 'yourpropfirm-checkout'),
            'phone'      => __('Phone Number is required.', 'yourpropfirm-checkout'),
            'address'    => __('Address is required.', 'yourpropfirm-checkout'),
            'country'    => __('Country is required.', 'yourpropfirm-checkout'),
            'city'       => __('City is required.', 'yourpropfirm-checkout'),
            'postal_code'=> __('Postal Code is required.', 'yourpropfirm-checkout'),
        ];

        foreach ($required_fields as $field => $error_message) {
            if (empty($_POST[$field])) {
                wc_add_notice($error_message, 'error');
            }
        }
    }
}
