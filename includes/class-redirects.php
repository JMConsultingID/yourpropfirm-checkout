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
 * Class Yourpropfirm_Checkout_Redirects
 *
 * Redirect users from the default WooCommerce checkout page to the custom billing form page.
 */
class Yourpropfirm_Checkout_Redirects {
    /**
     * Constructor: Hook into WooCommerce template redirect.
     */
    public function __construct() {
        add_filter('woocommerce_locate_template', [$this, 'ypf_override_templates'], 10, 3);
        add_action('template_redirect', [$this, 'redirect_default_checkout']);
        add_action('wp_footer', [$this, 'yourpropfirm_checkout_affiliate_redirect_by_page_id'], 10);
    }

    /**
     * Override WooCommerce templates
     *
     * @param string $template
     * @param string $template_name
     * @param string $template_path
     * @return string
     */

    public function ypf_override_templates($template, $template_name, $template_path) {
        // Array of templates to override
        $override_templates = [        
            'checkout/form-checkout.php',
            'checkout/form-billing.php',
            'checkout/form-pay.php',
        ];

        // Check if the requested template is in our override list
        if (in_array($template_name, $override_templates)) {
            // Define the path to the plugin's custom template
            $plugin_template = YPF_CHECKOUT_DIR . 'templates/woocommerce/' . $template_name;

            // Return the plugin template if it exists
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        // Return the original template if no override
        return $template;
    }

    /**
     * Redirect default WooCommerce checkout to the custom billing form page.
     */
    public function redirect_default_checkout() {
        if ( class_exists( 'WooCommerce' ) ) {
        // Check if WooCommerce checkout page is being accessed.
            $checkout_type = get_option('yourpropfirm_checkout_type', 'default'); // Default to 'default'.
            if ($checkout_type === 'custom' && is_checkout() && !is_wc_endpoint_url() && !isset($_GET['pay_for_order'])) {
                $custom_checkout_url = home_url('/order/');
                // Clear WooCommerce notices.
    	        wc_clear_notices();
                wp_safe_redirect($custom_checkout_url);
                exit;
            }


            if ( is_page( 'cart' ) || ( isset( $_GET['cancel_order'] ) && $_GET['cancel_order'] === 'true' ) ) {
                $home_page_url = home_url();
                wp_safe_redirect( $home_page_url );
                exit;
            }

            // Check if shop page is disabled
            if ( is_shop()) {
                $home_page_url = home_url();
                wp_safe_redirect( $home_page_url );
                exit;
            }

            // Check if product page is disabled
            if ( is_product()) {
                $home_page_url = home_url();
                wp_safe_redirect( $home_page_url );
                exit;
            }
        }

    }

    public function yourpropfirm_checkout_affiliate_redirect_by_page_id() {
        $redirect_referral_url = 'https://www.forfx.com/';
        if (is_front_page() || is_home()) {
            ?>
            <script type="text/javascript">
                document.addEventListener("DOMContentLoaded", function() {
                    setTimeout(function() {
                        var urlParams = new URLSearchParams(window.location.search);
                        var refParam = urlParams.get('ref');
                        var redirectUrl = "<?php echo esc_js($redirect_referral_url); ?>";
                        if (refParam) {
                            window.location.href = redirectUrl + "?ref=" + refParam;
                        } else {
                            window.location.href = redirectUrl;
                        }
                    }, 10);
                });
            </script>
            <?php
        }
    }
}
