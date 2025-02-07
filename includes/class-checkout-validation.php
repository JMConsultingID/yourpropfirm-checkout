<?php
/**
 * Plugin functions and definitions for Public.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * @package yourpropfirm-checkout
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class YourPropfirm_Checkout_Validation
 *
 * Handles WooCommerce checkout field validation.
 */
class YourPropfirm_Checkout_Validation {

    /**
     * Fields that should be checked for non-Latin characters.
     *
     * @var array
     */
    private $fields_to_check = [
        'billing_first_name' => 'First Name',
        'billing_last_name' => 'Last Name',
        'billing_address_1' => 'Address Line 1',
        'billing_address_2' => 'Address Line 2',
        'billing_city' => 'City',
        'billing_postcode' => 'Postcode',
        'billing_email' => 'Email'
    ];

    /**
     * Constructor: Register hooks and filters.
     */
    public function __construct() {
        add_action('woocommerce_checkout_process', [$this, 'validate_checkout_fields']);
    }

    /**
     * Validate checkout fields for non-Latin characters.
     */
    public function validate_checkout_fields() {
        foreach ($this->fields_to_check as $field_key => $field_label) {
            if (!isset($_POST[$field_key])) {
                continue;
            }

            $field_value = sanitize_text_field($_POST[$field_key]);

            if ($this->contains_non_latin($field_value)) {
                wc_add_notice(
                    sprintf(
                        /* translators: %s: field name */
                        __('Only Latin letters (A-Z, a-z) are allowed in the %s field.', 'yourpropfirm-checkout'),
                        $field_label
                    ),
                    'error'
                );
            }
        }
    }

    /**
     * Check if a string contains non-Latin characters.
     *
     * @param string $string The string to check.
     * @return boolean True if contains non-Latin characters, false otherwise.
     */
    private function contains_non_latin($string) {
        return preg_match('/[^a-zA-Z\s]/u', $string);
    }

    /**
     * Add a field to check for non-Latin characters.
     *
     * @param string $field_key The field key.
     * @param string $field_label The human-readable field label.
     */
    public function add_field_to_check($field_key, $field_label) {
        $this->fields_to_check[$field_key] = $field_label;
    }

    /**
     * Remove a field from validation.
     *
     * @param string $field_key The field key to remove.
     */
    public function remove_field_to_check($field_key) {
        if (isset($this->fields_to_check[$field_key])) {
            unset($this->fields_to_check[$field_key]);
        }
    }

    /**
     * Get all fields being checked.
     *
     * @return array Array of fields being checked.
     */
    public function get_fields_to_check() {
        return $this->fields_to_check;
    }
}
