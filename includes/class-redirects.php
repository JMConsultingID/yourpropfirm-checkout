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
        add_action('template_redirect', [$this, 'redirect_default_checkout']);
        add_action('wp_footer', [$this, 'yourpropfirm_checkout_affiliate_redirect_by_page_id'], 10);
    }

    /**
     * Redirect default WooCommerce checkout to the custom billing form page.
     */
    public function redirect_default_checkout() {
        if ( class_exists( 'WooCommerce' ) ) {
        // Check if WooCommerce checkout page is being accessed.
            if (is_checkout() && !is_wc_endpoint_url() && !isset($_GET['pay_for_order'])) {
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
                // Add try-catch for error handling
                try {
                    setTimeout(function() {
                        var urlParams = new URLSearchParams(window.location.search);
                        var refParam = urlParams.get('ref');
                        
                        // Sanitize the ref parameter
                        if (refParam) {
                            refParam = encodeURIComponent(refParam.replace(/[^\w-]/g, ''));
                        }
                        
                        var redirectUrl = "<?php echo esc_js($escaped_url); ?>";
                        
                        // Build the final URL
                        var finalUrl = redirectUrl;
                        if (refParam) {
                            finalUrl += (finalUrl.indexOf('?') === -1 ? '?' : '&') + 'ref=' + refParam;
                        }
                        
                        // Perform the redirect
                        window.location.href = finalUrl;
                    }, 100); // 1 second delay
                } catch (error) {
                    console.error('Redirect error:', error);
                }
            });
        </script>
            <?php
        }
    }
}
