(function( $ ) {
    'use strict';

    jQuery(document).ready(function($) {
        const form = $('#ypf-billing-form');
        const submitButton = $('#ypf-submit-button');

        form.on('submit', function(e) {
            e.preventDefault();

            if (!form[0].checkValidity()) {
                e.stopPropagation();
                form.addClass('was-validated');
                return;
            }

            submitButton.prop('disabled', true);
            
            const formData = new FormData(this);
            formData.append('action', 'ypf_process_checkout');
            formData.append('nonce', ypf_data.checkout_nonce);

            $.ajax({
                url: ypf_data.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Open payment page in new tab
                        window.open(response.redirect, '_blank');
                        
                        // Clear form and redirect current page after delay
                        setTimeout(function() {
                            form[0].reset();
                            window.location.href = ypf_data.order_page_url;
                        }, 2000);
                    } else {
                        window.location.reload();
                    }
                },
                error: function() {
                    window.location.reload();
                },
                complete: function() {
                    submitButton.prop('disabled', false);
                }
            });
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