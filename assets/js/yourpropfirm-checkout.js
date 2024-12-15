(function( $ ) {
    'use strict';

    jQuery(document).ready(function($) {
        // Handle form submission
        $('#ypf-billing-form').on('submit', function(e) {
            e.preventDefault(); // Prevent default submission to ensure proper handling

            // Get checkbox elements
            const termsCheckbox = $('#terms');
            const privacyCheckbox = $('#privacy_policy');

            // Validate checkboxes
            if (!termsCheckbox.is(':checked') || !privacyCheckbox.is(':checked')) {
                // Show error messages if not checked
                if (!termsCheckbox.is(':checked')) {
                    alert('Please accept the Terms and Conditions to proceed.');
                }
                if (!privacyCheckbox.is(':checked')) {
                    alert('Please accept the Privacy Policy to proceed.');
                }
                return false; // Stop further processing
            }
             // Submit the form via AJAX or let PHP handle redirection
            this.submit();
        });

        // Handle dynamic state selection or input
        $('#country').on('change', function () {
            const country = $(this).val(); // Selected country code
            const states = ypf_data.states; // Get states from localized data

            // Clear any existing state field
            $('#state-container').empty();

            if (states[country] && Object.keys(states[country]).length) {
                // Create a dropdown if states exist
                let options = '<option value="">' + ypf_data.select_state_text + '</option>';
                $.each(states[country], function(key, value) {
                    options += '<option value="' + key + '">' + value + '</option>';
                });
                $('#state-container').append('<select name="state" id="state" required>' + options + '</select>');
            } else {
                // Create a text input if no states exist
                $('#state-container').append('<input type="text" name="state" id="state" placeholder="' + ypf_data.enter_state_text + '" required>');
            }
        });

        // Trigger change event on page load to initialize the state field
        $('#country').trigger('change');
    });

})( jQuery );