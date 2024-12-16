(function( $ ) {
    'use strict';

    jQuery(document).ready(function($) {
       // Handle form submission
        $('#ypf-billing-form').on('submit', function (e) {
            const form = this;

            // Bootstrap validation: Check form validity
            if (!form.checkValidity()) {
                e.preventDefault(); // Prevent submission if form is invalid
                e.stopPropagation(); // Stop further event propagation
                form.classList.add('was-validated'); // Add Bootstrap validation class
                return false;
            }

            // Submit the form
            setTimeout(function () {
                form.submit();
            }, 2000); // Add delay before submission if needed
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