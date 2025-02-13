<?php
/**
 * @link              https://yourpropfirm.com
 * @since             1.1.0
 * @package           yourpropfirm-checkout
 * GitHub Plugin URI: https://github.com/JMConsultingID/your-propfirm-addon
 * GitHub Branch: develop
 * @wordpress-plugin
 * Plugin Name:       Yourpropfirm Multistep Checkout
 * Plugin URI:        https://yourpropfirm.com
 * Description:       This Plugin to Create multi-step checkout for WooCommerce.
 * Version:           1.1.0
 * Author:            YourPropfirm Team
 * Author URI:        https://yourpropfirm.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       yourpropfirm-checkout
 * Domain Path:       /languages
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define constants for plugin paths.
define('YPF_CHECKOUT_VERSION', '1.0');
define('YPF_CHECKOUT_DIR', plugin_dir_path(__FILE__));
define('YPF_CHECKOUT_URL', plugin_dir_url(__FILE__));

/**
 * Initialize the plugin.
 */
class Yourpropfirm_Checkout {
    /**
     * Constructor: Register hooks and load dependencies.
     */
    public function __construct() {
        // Load plugin text domain for translations.
        add_action('plugins_loaded', [$this, 'load_text_domain']);

        // Include necessary files.
        $this->includes();

        // Initialize the plugin features.
        $this->init();
    }

    /**
     * Load plugin text domain for translations.
     */
    public function load_text_domain() {
        load_plugin_textdomain('yourpropfirm-checkout', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    /**
     * Include core functionality files.
     */
    private function includes() {
        require_once YPF_CHECKOUT_DIR . 'includes/class-admin-panel.php';
        require_once YPF_CHECKOUT_DIR . 'includes/class-helper.php';
        require_once YPF_CHECKOUT_DIR . 'includes/class-order-handler.php';        
        require_once YPF_CHECKOUT_DIR . 'includes/class-shortcodes.php';
        require_once YPF_CHECKOUT_DIR . 'includes/class-redirects.php';
        require_once YPF_CHECKOUT_DIR . 'includes/class-single-product-checkout.php';
        require_once YPF_CHECKOUT_DIR . 'includes/class-checkout-woocommerce.php';
        require_once YPF_CHECKOUT_DIR . 'includes/class-checkout-validation.php';
    }

    /**
     * Initialize core functionality.
     */
    private function init() {
        // Always load the admin panel.
        new YourPropFirm_Admin_Panel();

        // Get plugin settings.
        $checkout_enabled = get_option('yourpropfirm_checkout_enabled', 0); // Default to disabled.
        $checkout_type = get_option('yourpropfirm_checkout_type', 'default'); // Default to 'default'.

        // Run conditionally based on settings.
        if ($checkout_enabled == 1) {
            // Load single product checkout and redirects.
            new YourPropfirm_Single_Product_Checkout();
            new Yourpropfirm_Checkout_Redirects();
            new YourPropFirm_Helper();

            if ($checkout_type === 'default') {
                new Yourpropfirm_Checkout_Woocommerce();
                new YourPropfirm_Checkout_Validation();
            } elseif ($checkout_type === 'custom') {
                new Yourpropfirm_Checkout_Order_Handler();
                new Yourpropfirm_Checkout_Shortcodes();
                
            } else{
                 new Yourpropfirm_Checkout_Woocommerce();
            }
        }
    }
}

// Initialize the plugin.
new Yourpropfirm_Checkout();
// Register custom query variable
add_filter('query_vars', 'add_custom_query_vars');
function add_custom_query_vars($vars) {
    $vars[] = 'utm_source';
    return $vars;
}

// Retrieve custom query parameter and save to session
add_action('wp', 'get_custom_url_parameter');
function get_custom_url_parameter() {
    if (is_checkout()) {
        $utm_source = get_query_var('utm_source');
        if (!empty($utm_source)) {
            WC()->session->set('yourpropfirm_utm', sanitize_text_field($utm_source));
        }
    }
}

// Save session data to order meta
add_action('woocommerce_checkout_update_order_meta', 'save_utm_to_order_meta');
function save_utm_to_order_meta($order_id) {
    if ($utm_source = WC()->session->get('yourpropfirm_utm')) {
        update_post_meta($order_id, '_yourpropfirm_utm', $utm_source);
        update_post_meta($order_id, '_yourpropfirm_checkout_utm', $utm_source);
        
        // Clear the session data after saving
        WC()->session->__unset('yourpropfirm_utm');
    }
}

// Debugging: Output the query variable
add_action('wp_footer', 'debug_custom_query_var');
function debug_custom_query_var() {
    if (is_checkout()) {
        $utm_source = get_query_var('utm_source');
        echo '<pre>UTM Source from Query Var: ' . esc_html($utm_source) . '</pre>';
    }
}