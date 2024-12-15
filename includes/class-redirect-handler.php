<?php
class Yourprofirm_Redirect_Handler {

    public static function init() {
        add_action('template_redirect', [__CLASS__, 'redirect_default_checkout']);
    }

    public static function redirect_default_checkout() {
        if (is_checkout() && !is_wc_endpoint_url('order-pay')) {
            wp_redirect(home_url());
            exit;
        }
    }
}
