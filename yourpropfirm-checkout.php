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

function yourpropfirm_add_admin_styles() {
    ?>
    <style type="text/css">
        .yellowpencil-notice { display: none !important; }
    </style>
    <?php
}
add_action('admin_head', 'yourpropfirm_add_admin_styles');

/**
 * Initialize the plugin.
 */
class Yourpropfirm_Checkout {
    /**
     * Constructor: Register hooks and load dependencies.
     */
    public function __construct() {
        // Ensure WooCommerce is active
        add_action('plugins_loaded', array($this, 'check_woocommerce'));
        // Load plugin text domain for translations.
        add_action('plugins_loaded', [$this, 'load_text_domain']);

        // Hook into WooCommerce template loader
        add_filter('woocommerce_locate_template', array($this, 'override_checkout_template'), 10, 3);
        
        // Include necessary files.
        $this->includes();

        // Initialize the plugin features.
        $this->init();
    }


    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function() {
                ?>
                <div class="error">
                    <p><?php _e('Custom Checkout Form requires WooCommerce to be installed and active.', 'custom-checkout'); ?></p>
                </div>
                <?php
            });
        }
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
        // require_once YPF_CHECKOUT_DIR . 'includes/class-order-handler.php';
        // require_once YPF_CHECKOUT_DIR . 'includes/class-shortcodes.php';
        // require_once YPF_CHECKOUT_DIR . 'includes/class-redirects.php';
        require_once YPF_CHECKOUT_DIR . 'includes/class-single-product-checkout.php';
        require_once YPF_CHECKOUT_DIR . 'includes/class-coupon-code-checkout.php';
        require_once YPF_CHECKOUT_DIR . 'includes/class-woocommerce-helper.php';
    }

    /**
     * Initialize core functionality.
     */
    private function init() {
        // Instantiate the classes.
        // new Yourpropfirm_Checkout_Order_Handler();
        // new Yourpropfirm_Checkout_Shortcodes();
        // new Yourpropfirm_Checkout_Redirects();
        new YourPropfirm_Single_Product_Checkout();
        new YourPropfirm_Coupon_Code_Checkout();
        new YourPropfirm_Woocommerce_Helper();
        
    }

    public function override_checkout_template($template, $template_name, $template_path) {
        // Check if we're looking for the checkout form template
        if ($template_name === 'checkout/form-checkout.php') {
            // Define path to your custom template
            $plugin_template = YPF_CHECKOUT_DIR . 'templates/woocommerce/checkout/form-checkout.php';
            
            // Use custom template if it exists
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
}

// Initialize the plugin.
new Yourpropfirm_Checkout();