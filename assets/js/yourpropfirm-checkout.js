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

            // If validation passes, proceed with form submission logic
            // Set form target to open in a new tab
            $(this).attr('target', '_blank');

            // Allow the form to submit after setting the target
            this.submit();

            // Perform delayed actions in the current tab
            setTimeout(function () {
                // Clear all form fields
                $('#ypf-billing-form').find('input, select').val('');

                // Redirect to the home page in the current tab
                window.location.href = ypf_data.home_url;
            }, 2000); // 2-second delay
        });

        // Dynamic state selection or text input
        $('#country').on('change', function () {
            const country = $(this).val(); // Selected country code
            const states = ypf_data.states; // Get states from localized data

            // Clear any existing state field
            $('#state-container').empty();

            if (states[country]) {
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
    });

})( jQuery );