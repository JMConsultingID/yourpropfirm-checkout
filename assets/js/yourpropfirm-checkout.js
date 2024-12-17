(function( $ ) {
    'use strict';

    jQuery(document).ready(function($) {
        const form = $('#ypf-billing-form');
        const submitButton = $('#ypf-submit-button');

        form.on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'ypf_process_checkout');
            formData.append('nonce', ypf_data.checkout_nonce);

            if (!form[0].checkValidity()) {
                e.stopPropagation();
                form.addClass('was-validated');
                return;
            }

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
                            window.location.href = ypf_data.home_url;
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
        const savedState = ypf_data.saved_state;
        const savedCountry = $('#country').val(); // Get the pre-selected country
        
        function updateStateField(initialLoad = false) {
            const country = $('#country').val();
            const states = ypf_data.states;
            const container = $('#state-container');
            
            container.empty();
            
            if (states[country] && Object.keys(states[country]).length) {
                // Create select field for states
                let select = $('<select>', {
                    name: 'state',
                    id: 'state',
                    class: 'form-select',
                    required: true
                });
                
                // Add default option
                select.append($('<option>', {
                    value: '',
                    text: ypf_data.select_state_text
                }));
                
                // Add state options
                $.each(states[country], function(code, name) {
                    let option = $('<option>', {
                        value: code,
                        text: name
                    });
                    
                    // Select the saved state if it matches and we're handling the same country
                    if (savedState === code && (initialLoad || country === savedCountry)) {
                        option.prop('selected', true);
                    }
                    
                    select.append(option);
                });
                
                container.append(select);
            } else {
                // Create text input for states
                let input = $('<input>', {
                    type: 'text',
                    name: 'state',
                    id: 'state',
                    class: 'form-control',
                    required: true,
                    placeholder: ypf_data.enter_state_text,
                    // Only set the value if we're handling the same country or initial load
                    value: (initialLoad || country === savedCountry) ? savedState : ''
                });
                
                container.append(input);
            }

            // Add bootstrap classes
            $('#state').addClass('form-control');
        }

        // Handle country change
        $('#country').on('change', function() {
            updateStateField(false);
        });

        // Initialize state field if there's a saved country
        if (savedCountry) {
            // Use a very small delay to ensure the DOM is ready
            setTimeout(function() {
                updateStateField(true);
            }, 10);
        } else {
            // If no saved country, just trigger the change event
            $('#country').trigger('change');
        }
    });

})( jQuery );