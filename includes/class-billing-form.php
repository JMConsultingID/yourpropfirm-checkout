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
        ob_start();
        ?>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST">
            <input type="hidden" name="action" value="yourprofirm_billing_form">
            <?php wp_nonce_field('yourprofirm_billing_form_nonce'); ?>

            <div>
                <label for="billing_first_name">First Name</label>
                <input type="text" name="billing_first_name" id="billing_first_name" required>
            </div>
            <div>
                <label for="billing_last_name">Last Name</label>
                <input type="text" name="billing_last_name" id="billing_last_name" required>
            </div>
            <div>
                <label for="billing_email">Email</label>
                <input type="email" name="billing_email" id="billing_email" required>
            </div>
            <div>
                <label for="billing_phone">Phone</label>
                <input type="tel" name="billing_phone" id="billing_phone" required>
            </div>
            <div>
                <label for="billing_country">Country</label>
                <select name="billing_country" id="billing_country" required>
                    <?php foreach (WC()->countries->get_allowed_countries() as $code => $name) : ?>
                        <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="billing_state">State</label>
                <select name="billing_state" id="billing_state" required>
                    <?php foreach (WC()->countries->get_states('US') as $code => $name) : ?>
                        <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Submit</button>
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
