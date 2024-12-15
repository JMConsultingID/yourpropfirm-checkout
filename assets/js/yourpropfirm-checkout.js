(function( $ ) {
    'use strict';

    /**
     * All of the code for your public-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */
    
    jQuery(document).ready(function($) {
 // Handle form submission
	    $('#ypf-billing-form').on('submit', function (e) {
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
	            window.location.href = "<?php echo esc_url(home_url()); ?>";
	        }, 2000); // 2-second delay
	    });

        // Dynamic state selection
        $('#country').on('change', function () {
            var country = $(this).val();
            var states = <?php echo json_encode($states); ?>;

            if (states[country]) {
                var options = '<option value=""><?php esc_html_e('Select State', 'yourpropfirm-checkout'); ?></option>';
                $.each(states[country], function (key, value) {
                    options += '<option value="' + key + '">' + value + '</option>';
                });
                $('#state').html(options).prop('disabled', false);
            } else {
                $('#state').html('<option value=""><?php esc_html_e('No states available', 'yourpropfirm-checkout'); ?></option>').prop('disabled', true);
            }
        });
    });

})( jQuery );
