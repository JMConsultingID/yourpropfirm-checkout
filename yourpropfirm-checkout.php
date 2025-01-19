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
        require_once YPF_CHECKOUT_DIR . 'includes/class-order-handler.php';        
        require_once YPF_CHECKOUT_DIR . 'includes/class-shortcodes.php';
        require_once YPF_CHECKOUT_DIR . 'includes/class-redirects.php';
        require_once YPF_CHECKOUT_DIR . 'includes/class-single-product-checkout.php';
        require_once YPF_CHECKOUT_DIR . 'includes/class-checkout-woocommerce.php';
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

            if ($checkout_type === 'default') {
                new Yourpropfirm_Checkout_Woocommerce();
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