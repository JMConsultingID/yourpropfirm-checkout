<?php
/**
 * Plugin functions and definitions for Admin.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * @package yourpropfirm-checkout
 */
class Yourprofirm_Billing_Form {

    public static function init() {
        // Register the shortcode
        add_shortcode('custom_billing_checkout', [__CLASS__, 'render_billing_form']);
        // Handle form submission
        add_action('admin_post_nopriv_yourprofirm_billing_form', [__CLASS__, 'handle_form_submission']);
        add_action('admin_post_yourprofirm_billing_form', [__CLASS__, 'handle_form_submission']);
    }

    public static function render_billing_form() {
        // Ensure this check runs only on the /order/ page
        if (is_page('order') && WC()->cart->is_empty()) {
            wc_add_notice(__('Your cart is empty. Please add products to proceed.', 'yourprofirm-checkout'), 'error');
            wp_redirect(wc_get_cart_url()); // Redirect to the cart page
            exit;
        }

        // WooCommerce checkout fields
        $checkout = WC()->checkout();
        $fields = $checkout->get_checkout_fields('billing');

        ob_start();
        ?>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST">
            <input type="hidden" name="action" value="yourprofirm_billing_form">
            <?php wp_nonce_field('yourprofirm_billing_form_nonce'); ?>

            <?php
            // Loop through all billing fields
            foreach ($fields as $key => $field) {
                woocommerce_form_field($key, $field);
            }
            ?>
            <button type="submit"><?php _e('Submit Order', 'yourprofirm-checkout'); ?></button>
        </form>
        <?php
        return ob_get_clean();
    }


    public static function handle_form_submission() {
        // Verify nonce for security
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'yourprofirm_billing_form_nonce')) {
            wp_die(__('Security check failed. Please try again.', 'yourprofirm-checkout'));
        }

        // Sanitize and validate form inputs
        $billing_first_name = isset($_POST['billing_first_name']) ? sanitize_text_field($_POST['billing_first_name']) : '';
        $billing_last_name  = isset($_POST['billing_last_name']) ? sanitize_text_field($_POST['billing_last_name']) : '';
        $billing_email      = isset($_POST['billing_email']) ? sanitize_email($_POST['billing_email']) : '';
        $billing_phone      = isset($_POST['billing_phone']) ? sanitize_text_field($_POST['billing_phone']) : '';
        $billing_country    = isset($_POST['billing_country']) ? sanitize_text_field($_POST['billing_country']) : '';
        $billing_state      = isset($_POST['billing_state']) ? sanitize_text_field($_POST['billing_state']) : '';

        // Validate required fields
        if (empty($billing_first_name) || empty($billing_last_name) || empty($billing_email) || empty($billing_phone) || empty($billing_country) || empty($billing_state)) {
            wp_die(__('Please fill in all required fields.', 'yourprofirm-checkout'));
        }

        if (!is_email($billing_email)) {
            wp_die(__('Please enter a valid email address.', 'yourprofirm-checkout'));
        }

        // Create a new WooCommerce order
        $order = wc_create_order();

        // Set billing details
        $order->set_address([
            'first_name' => $billing_first_name,
            'last_name'  => $billing_last_name,
            'email'      => $billing_email,
            'phone'      => $billing_phone,
            'country'    => $billing_country,
            'state'      => $billing_state,
        ], 'billing');

        // Set order status to 'Pending Payment'
        $order->set_status('pending');
        $order->save();

        // Generate WooCommerce Order Pay URL
        $order_id   = $order->get_id();
        $order_key  = $order->get_order_key();
        $order_pay_url = wc_get_checkout_url() . "order-pay/{$order_id}/?pay_for_order=true&key={$order_key}";

        // Redirect to the Order Pay page
        wp_redirect($order_pay_url);
        exit;
    }
}
