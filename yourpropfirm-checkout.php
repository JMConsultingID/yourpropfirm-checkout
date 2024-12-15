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

// Load custom form-pay template from the plugin
add_filter('woocommerce_locate_template', function ($template, $template_name, $template_path) {
    if ($template_name === 'checkout/form-pay.php') {
        $custom_template = YPF_CHECKOUT_DIR . 'templates/checkout/form-pay.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    return $template;
}, 10, 3);


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
        require_once YPF_CHECKOUT_DIR . 'includes/class-order-handler.php';
        require_once YPF_CHECKOUT_DIR . 'includes/class-shortcodes.php';
        require_once YPF_CHECKOUT_DIR . 'includes/class-redirects.php';
    }

    /**
     * Initialize core functionality.
     */
    private function init() {
        // Instantiate the classes.
        new Yourpropfirm_Checkout_Order_Handler();
        new Yourpropfirm_Checkout_Shortcodes();
        new Yourpropfirm_Checkout_Redirects();
    }
}

// Initialize the plugin.
new Yourpropfirm_Checkout();
