<?php
/**
 * Plugin functions and definitions for Admin.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * @package yourpropfirm-checkout
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class YourPropFirm_Admin_Panel {
    public function __construct() {
        // Hook to add the submenu under the existing menu.
        add_action('admin_menu', [$this, 'add_admin_menu'], 20);
        
        // Hook to save the settings.
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Add the submenu page under the main menu.
     */
    public function add_admin_menu() {
        add_submenu_page(
            'yourpropfirm_dashboard', // Parent menu slug
            __('YPF Checkout', 'yourpropfirm-checkout'), // Page title
            __('YPF Checkout', 'yourpropfirm-checkout'), // Menu title
            'manage_options', // Capability required
            'yourpropfirm-checkout', // Submenu slug
            [$this, 'render_admin_page'] // Callback function to display the page
        );
    }

    /**
     * Register settings for the admin panel.
     */
    public function register_settings() {
        // Register the setting
        register_setting('yourpropfirm_checkout_settings', 'yourpropfirm_checkout_enabled');
        register_setting('yourpropfirm_checkout_settings', 'yourpropfirm_checkout_type');
        register_setting('yourpropfirm_checkout_settings', 'yourpropfirm_restricted_category_ids');
        register_setting('yourpropfirm_checkout_settings', 'yourpropfirm_homepage_redirect_domain');
    }

    /**
     * Render the admin panel page.
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('YourPropFirm Checkout Settings', 'yourpropfirm-checkout'); ?></h1>
            <form method="post" action="options.php">
                <?php
                // Display settings fields
                settings_fields('yourpropfirm_checkout_settings');
                do_settings_sections('yourpropfirm_checkout_settings');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="yourpropfirm_checkout_enabled"><?php esc_html_e('Enable YourPropFirm Checkout', 'yourpropfirm-checkout'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="yourpropfirm_checkout_enabled" id="yourpropfirm_checkout_enabled" value="1" <?php checked(get_option('yourpropfirm_checkout_enabled'), 1); ?>>
                            <p class="description"><?php esc_html_e('Enable or disable the YourPropFirm Checkout feature.', 'yourpropfirm-checkout'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="yourpropfirm_checkout_type"><?php esc_html_e('Checkout Type', 'yourpropfirm-checkout'); ?></label>
                        </th>
                        <td>
                            <select name="yourpropfirm_checkout_type" id="yourpropfirm_checkout_type">
                                <option value="default" <?php selected(get_option('yourpropfirm_checkout_type'), 'default'); ?>>
                                    <?php esc_html_e('Checkout Default Woocommerce', 'yourpropfirm-checkout'); ?>
                                </option>
                                <option value="custom" <?php selected(get_option('yourpropfirm_checkout_type'), 'custom'); ?>>
                                    <?php esc_html_e('Checkout Custom Woocommerce', 'yourpropfirm-checkout'); ?>
                                </option>
                            </select>
                            <p class="description"><?php esc_html_e('Select the checkout type to use.', 'yourpropfirm-checkout'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="yourpropfirm_restricted_category_ids"><?php esc_html_e('Prevent Repurchase by Category IDs', 'yourpropfirm-checkout'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="yourpropfirm_restricted_category_ids" id="yourpropfirm_restricted_category_ids" value="<?php echo esc_attr(get_option('yourpropfirm_restricted_category_ids', '')); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e('Enter the category IDs that should restrict repeated purchases, separated by commas (e.g., 12,34,56).', 'yourpropfirm-checkout'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="yourpropfirm_homepage_redirect_domain"><?php esc_html_e('Homepage Redirect Domain', 'yourpropfirm-checkout'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="yourpropfirm_homepage_redirect_domain" id="yourpropfirm_homepage_redirect_domain" value="<?php echo esc_attr(get_option('yourpropfirm_homepage_redirect_domain', '')); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e('Enter the domain to which the homepage should redirect (e.g., example.com).', 'yourpropfirm-checkout'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); // Save button ?>
            </form>
        </div>
        <?php
    }
}

function yourpropfirm_render_restricted_category_ids_field() {
    // Get the saved value from the database
    $value = get_option('yourpropfirm_restricted_category_ids', '');
    ?>
    <input type="text" name="yourpropfirm_restricted_category_ids" value="<?php echo esc_attr($value); ?>" class="regular-text">
    <p class="description">Enter the category IDs (comma-separated) that are restricted for multiple purchases.</p>
    <?php
}