<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class YourPropfirm_Woocommerce_Helper
 *
 * Handles WooCommerce order status display and payment method columns.
 */
class YourPropfirm_Woocommerce_Helper {

    /**
     * Constructor: Register hooks and filters.
     */
    public function __construct() {
        // Add admin styles
        add_action('admin_head', [$this, 'add_admin_styles']);

        // Register shortcode
        add_shortcode('yourpropfirm_order_status', [$this, 'display_order_status_shortcode']);

        // Add payment method column to orders list
        add_filter('manage_edit-shop_order_columns', [$this, 'add_payment_method_column']);
        add_action('manage_shop_order_posts_custom_column', [$this, 'display_payment_method_column'], 10, 2);

        // Add payment method column to orders list (new admin UI)
        add_filter('woocommerce_shop_order_list_table_columns', [$this, 'add_payment_method_list_table_column']);
        add_action('woocommerce_shop_order_list_table_custom_column', [$this, 'display_payment_method_list_table_column'], 10, 2);
    }
    
    /**
     * Add inline styles to admin head.
     */
    public function add_admin_styles() {
        ?>
        <style type="text/css">
            .yellowpencil-notice { display: none !important; }
        </style>
        <?php
    }

    /**
     * Display order status shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function display_order_status_shortcode($atts) {
        global $wp;

        // Get order ID from URL
        if (!isset($wp->query_vars['order-received'])) {
            return esc_html__('No order found.', 'yourpropfirm-checkout');
        }

        $order_id = absint($wp->query_vars['order-received']);
        $order = wc_get_order($order_id);

        if (!$order) {
            return esc_html__('Invalid order.', 'yourpropfirm-checkout');
        }

        // Get order status
        $order_status = wc_get_order_status_name($order->get_status());
        $order_status_var = $order->get_status();

        // Build and return HTML
        return sprintf(
            '<div class="order-status order-status-%s">%s</div>',
            esc_attr($order_status_var),
            esc_html($order_status)
        );
    }

    /**
     * Add payment method column to orders list.
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public function add_payment_method_column($columns) {
        $new_columns = [];

        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            if ('order_status' === $key) {
                $new_columns['payment_method'] = __('Payment Method', 'yourpropfirm-checkout');
            }
        }

        return $new_columns;
    }

    /**
     * Display payment method in column.
     *
     * @param string $column Column name.
     */
    public function display_payment_method_column($column) {
        global $post;

        if ('payment_method' === $column) {
            $order = wc_get_order($post->ID);
            if ($order) {
                $payment_method = $order->get_payment_method_title();
                echo !empty($payment_method) 
                    ? esc_html($payment_method) 
                    : esc_html__('N/A', 'yourpropfirm-checkout');
            }
        }
    }

    /**
     * Add payment method column to new orders list table.
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public function add_payment_method_list_table_column($columns) {
        $columns['payment_method'] = __('Payment Method', 'yourpropfirm-checkout');
        return $columns;
    }

    /**
     * Display payment method in new orders list table.
     *
     * @param string $column Column name.
     * @param WC_Order $order Order object.
     */
    public function display_payment_method_list_table_column($column, $order) {
        if ('payment_method' !== $column) {
            return;
        }

        echo esc_html($order->get_payment_method_title());
    }
}