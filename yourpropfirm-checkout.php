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
        require_once YPF_CHECKOUT_DIR . 'includes/class-order-handler.php';
        require_once YPF_CHECKOUT_DIR . 'includes/class-shortcodes.php';
        require_once YPF_CHECKOUT_DIR . 'includes/class-redirects.php';
        require_once YPF_CHECKOUT_DIR . 'includes/class-single-product-checkout.php';
    }

    /**
     * Initialize core functionality.
     */
    private function init() {
        // Instantiate the classes.
        new YourPropfirm_Single_Product_Checkout();
        new Yourpropfirm_Checkout_Order_Handler();
        new Yourpropfirm_Checkout_Shortcodes();
        new Yourpropfirm_Checkout_Redirects();
    }
}

// Initialize the plugin.
new Yourpropfirm_Checkout();


add_filter('woocommerce_locate_template', function ($template, $template_name, $template_path) {
    // Check if the requested template is form-pay.php
    if ($template_name === 'checkout/form-pay.php') {
        // Define the path to the plugin's custom template
        $plugin_template = YPF_CHECKOUT_DIR . 'templates/woocommerce/checkout/form-pay.php';
        
        // Return the plugin template if it exists
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }

    // Return the original template if no override
    return $template;
}, 10, 3);


add_filter( 'woocommerce_checkout_show_terms', '__return_false' );

add_action( 'init', function() {
    remove_action( 'woocommerce_checkout_terms_and_conditions', 'wc_checkout_privacy_policy_text', 20 );
    remove_action( 'woocommerce_checkout_terms_and_conditions', 'wc_terms_and_conditions_page_content', 30 );
});