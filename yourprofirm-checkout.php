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

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include necessary files
require_once plugin_dir_path( __FILE__ ) . 'includes/class-billing-form.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-order-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-redirect-handler.php';

// Initialize the plugin
function yourprofirm_checkout_init() {
    // Initialize each class
    Yourprofirm_Billing_Form::init();
    Yourprofirm_Order_Handler::init();
    Yourprofirm_Redirect_Handler::init();
}
add_action( 'plugins_loaded', 'yourprofirm_checkout_init' );
