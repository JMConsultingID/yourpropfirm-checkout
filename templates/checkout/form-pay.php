<?php
defined('ABSPATH') || exit;

global $wp;

$order_id  = absint($wp->query_vars['order-pay']);
$order     = wc_get_order($order_id);

if (!$order) {
    return;
}

wc_print_notices();
?>

<h2><?php esc_html_e('Order Summary', 'woocommerce'); ?></h2>
<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
    <thead>
        <tr>
            <th><?php esc_html_e('Product', 'woocommerce'); ?></th>
            <th><?php esc_html_e('Total', 'woocommerce'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($order->get_items() as $item_id => $item) {
            echo '<tr>';
            echo '<td>' . esc_html($item->get_name()) . '</td>';
            echo '<td>' . wc_price($item->get_total()) . '</td>';
            echo '</tr>';
        }
        ?>
    </tbody>
</table>

<h3><?php esc_html_e('Choose Payment Method', 'woocommerce'); ?></h3>

<form id="order-pay-form" method="post" action="<?php echo esc_url($order->get_checkout_payment_url()); ?>">
    <?php
    // Get all available payment gateways
    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();

    if (!empty($available_gateways)) {
        foreach ($available_gateways as $gateway) {
            ?>
            <div class="payment-option">
                <input type="radio" id="payment_method_<?php echo esc_attr($gateway->id); ?>" name="payment_method" value="<?php echo esc_attr($gateway->id); ?>" required />
                <label for="payment_method_<?php echo esc_attr($gateway->id); ?>">
                    <?php echo esc_html($gateway->get_title()); ?>
                </label>
            </div>
            <?php
        }
    } else {
        echo '<p>' . esc_html__('No available payment methods. Please contact us.', 'woocommerce') . '</p>';
    }
    ?>

    <button type="submit" class="button alt"><?php esc_html_e('Pay Now', 'woocommerce'); ?></button>
</form>
