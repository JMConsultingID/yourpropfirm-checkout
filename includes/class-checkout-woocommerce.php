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

class Yourpropfirm_Checkout_Woocommerce {

    public function __construct() {
        // Disable payment options on checkout page
        add_filter('woocommerce_cart_needs_payment', '__return_false');

        // Add Heading before checkout form.
        add_filter('woocommerce_before_checkout_form', [$this, 'ypf_heading_woocommerce_before_checkout_form']);        

        // Modify checkout fields
        add_filter('woocommerce_checkout_fields', [$this, 'ypf_customize_checkout_fields']);

     	// Add MT_Version after the billing form.
        add_action('woocommerce_after_checkout_billing_form', [$this, 'mt_version_after_checkout_billing']);

        // Coupon Code Action.
        add_action('woocommerce_checkout_init', [$this, 'ypf_checkout_move_coupon_field_below_order_review']);
        add_action('wp_ajax_apply_coupon_action', [$this, 'ypf_checkout_apply_coupon_action']);
        add_action('wp_ajax_nopriv_apply_coupon_action', [$this, 'ypf_checkout_apply_coupon_action']);
        add_action('woocommerce_review_order_before_payment', [$this, 'ypf_checkout_add_coupon_form_before_payment']);

        // Remove terms and conditions checkbox on order-pay page
        add_filter('woocommerce_checkout_show_terms', [$this, 'remove_terms_and_conditions_on_order_pay']);

        // Set order status based on total at checkout
        add_action('woocommerce_checkout_order_processed', [$this, 'ypf_set_order_status_based_on_total'], 10, 3);

        // Redirect to appropriate page after checkout
        add_action('woocommerce_thankyou', [$this, 'ypf_redirect_after_checkout'], 10);

        // Ensure completed orders remain completed
        add_filter('woocommerce_payment_complete_order_status', [$this, 'ypf_ensure_completed_orders_remain_completed'], 10, 3);
    }   

    /**
     * Add Heading before checkout form.
     */
    public function ypf_heading_woocommerce_before_checkout_form() {
        ?>
	    <div class="yourpropfirm-checkout-step mt-4">
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
		</div>
	<?php
	}

	/**
     * Customize WooCommerce checkout fields
     *
     * @param array $fields
     * @return array
     */
    public function ypf_customize_checkout_fields($fields) {
        // Remove shipping fields
        unset($fields['shipping']);
        
        // Unset all billing fields
        unset($fields['billing']);

        // Add customized billing fields with WooCommerce classes
        $fields['billing'] = [
            'billing_first_name' => [
                'label' => __('First Name', 'multistep-checkout'),
                'required' => true,
                'class' => ['form-row-first'],
                'input_class' => ['input-text'],
                'placeholder' => __('First Name', 'multistep-checkout'),
            ],
            'billing_last_name' => [
                'label' => __('Last Name', 'multistep-checkout'),
                'required' => true,
                'class' => ['form-row-last'],
                'input_class' => ['input-text'],
                'placeholder' => __('Last Name', 'multistep-checkout'),
                'clear' => true,
            ],
            'billing_email' => [
                'label' => __('Email', 'multistep-checkout'),
                'required' => true,
                'class' => ['form-row-first'],
                'input_class' => ['input-text'],
                'placeholder' => __('Email', 'multistep-checkout'),
            ],
            'billing_phone' => [
                'label' => __('Phone Number', 'multistep-checkout'),
                'required' => true,
                'class' => ['form-row-last'],
                'input_class' => ['input-text'],
                'placeholder' => __('Phone Number', 'multistep-checkout'),
                'clear' => true,
            ],
            'billing_address_1' => [
                'label' => __('Address', 'multistep-checkout'),
                'required' => true,
                'class' => ['form-row-wide'],
                'input_class' => ['input-text'],
                'placeholder' => __('Address', 'multistep-checkout'),
            ],
            'billing_country' => [
                'label' => __('Country', 'multistep-checkout'),
                'required' => true,
                'type' => 'select',
                'class' => ['form-row-first'],
                'input_class' => ['input-text'],
                'options' => WC()->countries->get_countries(),
            ],
            'billing_state' => [
                'label' => __('State/Region', 'multistep-checkout'),
                'required' => true,
                'class' => ['form-row-last'],
                'input_class' => ['input-text'],
                'placeholder' => __('State/Region', 'multistep-checkout'),
                'clear' => true,
            ],
            'billing_city' => [
                'label' => __('City', 'multistep-checkout'),
                'required' => true,
                'class' => ['form-row-first'],
                'input_class' => ['input-text'],
                'placeholder' => __('City', 'multistep-checkout'),
            ],
            'billing_postcode' => [
                'label' => __('Postal Code', 'multistep-checkout'),
                'required' => true,
                'class' => ['form-row-last'],
                'input_class' => ['input-text'],
                'placeholder' => __('Postal Code', 'multistep-checkout'),
                'clear' => true,
            ],
        ];

        return $fields;
    }

    /**
     * Add MT_Version after the billing form.
     */
    public function mt_version_after_checkout_billing() {
        ?>
        <div class="col-12 mb-3 mt-5 text-center">
            <label class="form-label">
                <?php esc_html_e('Trading Platforms', 'yourpropfirm-checkout'); ?> <span class="text-danger">*</span>
            </label>
            <div class="d-flex justify-content-center gap-4">
                <div class="form-check">
                    <input class="form-check-input mt-3 me-3" type="radio" name="yourpropfirm_mt_version" id="yourpropfirm_mt_version_MT4" value="MT4" checked>
                    <label class="form-check-label" for="yourpropfirm_mt_version_MT4">
                        <?php esc_html_e('MT4', 'yourpropfirm-checkout'); ?>
                    </label>
                </div>
                
                <div class="form-check">
                    <input class="form-check-input mt-3 me-3" type="radio" name="yourpropfirm_mt_version" id="yourpropfirm_mt_version_MT5" value="MT5">
                    <label class="form-check-label" for="yourpropfirm_mt_version_MT5">
                        <?php esc_html_e('MT5', 'yourpropfirm-checkout'); ?>
                    </label>
                </div>
                
                <div class="form-check">
                    <input class="form-check-input mt-3 me-3" type="radio" name="yourpropfirm_mt_version" id="yourpropfirm_mt_version_CTrader" value="CTrader">
                    <label class="form-check-label" for="yourpropfirm_mt_version_CTrader">
                        <?php esc_html_e('CTrader', 'yourpropfirm-checkout'); ?>
                    </label>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Set Coupon Code at checkout
     */

    public function ypf_checkout_move_coupon_field_below_order_review()
    {
        remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
    }

    public function ypf_checkout_apply_coupon_action()
    {
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

    public function ypf_checkout_add_coupon_form_before_payment()
    {
        echo '<div class="yourpropfirm-checkout-coupon-form text-center">
            <label for="coupon_code_field" style="display: block; margin-bottom: 15px;">If you have a coupon code, please apply it below.</label>
            <div style="display: flex; align-items: center; gap: 10px;">
                <input type="text" id="coupon_code_field" class="input-text input-text" name="coupon_code" placeholder="Apply Coupon Code"/>
                <button type="button" id="apply_coupon_button">Apply Coupon</button>
            </div>
        </div>';
    }

    /**
     * Set order status based on total at checkout
     *
     * @param int $order_id
     * @param array $posted_data
     * @param WC_Order $order
     */
    public function ypf_set_order_status_based_on_total($order_id, $posted_data, $order) {
        if ($order->get_total() == 0) {
            // If order total is 0, set status to completed
            $order->update_status('completed');
        } else {
            // If order total > 0, set status to pending
            $order->add_order_note( sprintf( __( 'Order Created. Order ID: #%d', 'multistep-checkout' ), $order->get_id() ) );
            $order->update_status('pending');
        }
    }

    /**
     * Redirect to appropriate page after checkout
     *
     * @param int $order_id
     */
    public function ypf_redirect_after_checkout($order_id) {
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);

        if ($order->get_total() == 0) {
            // If order total is 0, let WooCommerce handle the flow to Thank You page
            return;
        }

        if ($order->get_status() === 'pending') {
            // Redirect unpaid orders to order-pay page
            $redirect_url = $order->get_checkout_payment_url();
            $order->add_order_note(__('Redirecting to Order-Pay Page', 'multistep-checkout'));
            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    /**
     * Ensure completed orders remain completed
     *
     * @param string $status
     * @param int $order_id
     * @param WC_Order $order
     * @return string
     */
    public function ypf_ensure_completed_orders_remain_completed($status, $order_id, $order) {
        // Only adjust orders that are not already completed
        if ($order->get_status() === 'pending') {
            return 'pending'; // Keep pending for unpaid orders
        }
        return $status; // Return default status for other cases
    }

    /**
     * Remove terms and conditions checkbox on order-pay page
     *
     * @param bool $show_terms
     * @return bool
     */
    public function remove_terms_and_conditions_on_order_pay($show_terms) {
        if (is_wc_endpoint_url('order-pay')) {
            return false;
        }
        return $show_terms;
    }
}