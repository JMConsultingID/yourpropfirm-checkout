<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class YourPropfirm_Coupon_Code_Checkout
 *
 * Handles WooCommerce checkout modifications for coupon functionality.
 */
class YourPropfirm_Coupon_Code_Checkout {

    /**
     * Constructor: Register hooks and filters.
     */
    public function __construct() {
        // Move coupon field
        add_action('woocommerce_checkout_init', [$this, 'move_coupon_field_below_order_review']);
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_coupon_script']);
        
        // Ajax handlers
        add_action('wp_ajax_apply_coupon_action', [$this, 'apply_coupon_action']);
        add_action('wp_ajax_nopriv_apply_coupon_action', [$this, 'apply_coupon_action']);
        
        // Add coupon form
        add_action('woocommerce_review_order_before_payment', [$this, 'add_coupon_form_before_payment']);
    }

    /**
     * Move coupon field below order review.
     */
    public function move_coupon_field_below_order_review() {
        remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
    }

    /**
     * Enqueue coupon script.
     */
    public function enqueue_coupon_script() {
        if (is_checkout()) {
            wp_enqueue_script(
                'yourpropfirm-single-checkout-coupon-ajax', 
                plugin_dir_url(__FILE__) . 'assets/js/yourpropfirm-single-checkout.js', 
                array('jquery'), 
                null, 
                true
            );
            wp_localize_script(
                'yourpropfirm-single-checkout-coupon-ajax', 
                'ajax_object', 
                array('ajax_url' => admin_url('admin-ajax.php'))
            );
        }
    }

    /**
     * Handle AJAX coupon application.
     */
    public function apply_coupon_action() {
        if (!isset($_POST['coupon_code'])) {
            wp_send_json_error('Coupon code not provided.');
        }

        $coupon_code = sanitize_text_field($_POST['coupon_code']);
        WC()->cart->add_discount($coupon_code);

        if (wc_notice_count('error') > 0) {
            $errors = wc_get_notices('error');
            wc_clear_notices();
            wp_send_json_error(join(', ', wp_list_pluck($errors, 'notice')));
        }

        wp_send_json_success();
    }

    /**
     * Add coupon form before payment section.
     */
    public function add_coupon_form_before_payment() {
        echo '<div class="yourpropfirm-coupon-form">
            <label for="coupon_code_field" style="display: block; margin-bottom: 15px;">' . 
                esc_html__('If you have a coupon code, please apply it below.', 'yourpropfirm-checkout') . 
            '</label>
            <div style="display: flex; align-items: center;">
                <input type="text" id="coupon_code_field" name="coupon_code" placeholder="' . 
                    esc_attr__('Apply Coupon Code', 'yourpropfirm-checkout') . '"/>
                <button type="button" id="apply_coupon_button">' . 
                    esc_html__('Apply Coupon', 'yourpropfirm-checkout') . 
                '</button>
            </div>
        </div>';
    }
}