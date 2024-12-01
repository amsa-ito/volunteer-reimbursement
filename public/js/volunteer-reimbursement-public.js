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
	var spinner='<div class="loading-spinner" id="loading-spinnner"></div>';

	let formSubmittedSuccessfully = false;

	$('#form-content').on('focus', '.vr-form-group input[data-helper], .vr-form-group textarea[data-helper]', function() {
		// Check if helper text already exists and remove it if necessary
		const existingHelper = $(this).siblings('.helper-text');
		if (existingHelper.length) existingHelper.remove();
	
		// Create a new helper text element
		const helperText = $('<div></div>')
			.addClass('helper-text')
			.html($(this).data('helper'));
	
		// Append the helper text to the end of the .vr-form-group container
		$(this).parent().append(helperText);
	});
	
	// Remove helper text when input loses focus
	$('#form-content').on('blur', '.vr-form-group input[data-helper], .vr-form-group textarea[data-helper]', function() {
		$(this).siblings('.helper-text').remove();
	});
	
	// Use event delegation to watch for changes on #payee_committee within #form-content
	$('#form-content').on('change', '#payee_committee', function() {
		const committeeSelect = $(this); // #payee_committee element
		const otherCommitteeInput = $('#payee-other-committee'); // #payee-other-committee element

		if (committeeSelect.val() === 'Other') {
			// Show the other committee input field if "Other" is selected
			committeeSelect.prop('required', false); // Remove required from #payee_committee
			otherCommitteeInput.show().prop('required', true); // Show and require #payee-other-committee
		} else {
			// Hide the other committee input field if another option is selected
			committeeSelect.prop('required', true); // Add required to #payee_committee
			otherCommitteeInput.hide().prop('required', false).val(''); // Hide, remove required, and clear value
		}
	});

	// Initial check if elements are dynamically added and need the initial setup
	if ($('#payee_committee').length && $('#payee-other-committee').length) {
		// Trigger the change event to apply the logic when elements are present on load
		$('#payee_committee').trigger('change');
	}

	$('#form-content').on('focus', '.amount-input', function () {
		if ($(this).val() === "0" || $(this).val() === "00") {
			$(this).val('');
		}
	});

	$('#form-content').on('blur', '.amount-input', function () {
		if ($(this).val() === '') {
			$(this).val($(this).attr('name') === 'dollars' ? '0' : '00');
		}
	});

	// Open Modal on Thumbnail Click
	$('#form-content').on('click', '#vr-example-invoice-thumbnail', function () {
		$('#vr-example-invoice-modal').css("display","flex");
	});

	// Close Modal on Close Button or Background Click
	$('#form-content').on('click', '.vr-modal-close, #vr-example-invoice-modal', function (event) {
		if (event.target !== this) return; // Prevent closing when clicking on modal content
		$('#vr-example-invoice-modal').css("display", "none");
	});
	

	$('#payment-type-form').on('submit', function(e) {
		e.preventDefault();
		
		var paymentType = $('#payment_type').val();
		
		$('#form-content').html(spinner);

		// AJAX request to process the form
		$.ajax({
			url: Theme_Variables.ajax_url,
			type: 'POST',
			data: {
				action: 'payment_type_selection',
				payment_type: paymentType,
				nonce: Theme_Variables.nonce
			},
			success: function(response) {
				// Display the response inside the #form-content div
				$('#form-content').html(response.data['content']);
			},
			error: function() {
				$('#form-content').html('<p style="color:red;">There was an error processing your request. Please try again.</p>');
			}
		}).always(function(){
			$('.loading-spinner').remove();
		});
	});

	$('#form-content').on('submit', '#reimbursement-form', function(e) {
		e.preventDefault();
		$('#reimbursement-form').append('<div id="form-response"></div>');

		$('#form-response').append(spinner);
		// Collect form data
		var formData = new FormData(this); // Use FormData to handle files and text fields automatically
		
		// Append additional AJAX action and nonce information
		formData.append('nonce', Theme_Variables.nonce);

		formData.delete('attachments[]');

		uploadedFiles.forEach((file, index) => {
            formData.append('attachments[]', file);
        });
		// Send AJAX request to process the form
		$.ajax({
			url: Theme_Variables.ajax_url,
			type: 'POST',
			data: formData,
			processData: false, // Necessary for FormData
			contentType: false, // Necessary for FormData
			success: function(response) {
				// console.log(response);
				if (response.data['status'] === 'success') {
					$('#form-response').html('<p style="color:green;">' + response.data['message'] + '</p>');
					$('#reimbursement-form')[0].reset(); // Clear the form on success
					$('#vr-file-list').empty();
					uploadedFiles=[];
					formSubmittedSuccessfully = true;
				} else {
					$('#form-response').html('<p style="color:red;">' + response.data['message'] + '</p>');
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.error("AJAX Error:", textStatus, errorThrown);
				$('#form-response').html('<p style="color:red;">There was an error processing your request. Please try again later.</p>');
			}
		}).always(function(){
			$('#form-response').find('.loading-spinner').remove();

		});
	});

	// Clear form-response only if the form was successfully submitted
	$('#reimbursement-form').on('focus click', 'input, textarea, select', function () {
		if (formSubmittedSuccessfully) {
			$('#form-response').empty();
			formSubmittedSuccessfully = false; // Reset the flag after clearing the response
		}
	});

	let uploadedFiles = [];
	$('#form-content').on('change', '#vr-multiple-file-input', function (e) {
		let newFiles = Array.from(e.target.files);

		// Add new files to the uploaded files list
		newFiles.forEach(file => {
			if (!uploadedFiles.some(f => f.name === file.name && f.size === file.size)) {
				uploadedFiles.push(file);
			}
		});

		// Clear the file input to allow re-uploading the same file
		$('#vr-multiple-file-input').val('');

		// Update the file list display
		updateFileList();
	});

	// Function to display the list of uploaded files
	function updateFileList() {
		$('#vr-file-list').empty();
		uploadedFiles.forEach((file, index) => {
			$('#vr-file-list').append(`<li>${file.name} (${(file.size / 1024).toFixed(1)} KB) 
				<button data-index="${index}" class="remove-btn">Remove</button></li>`);
		});
	}

	// Remove a file from the uploaded files list
	$('#form-content').on('click', '.remove-btn', function () {
		const fileIndex = $(this).data('index');
		uploadedFiles.splice(fileIndex, 1);
		updateFileList();
	});



	
})( jQuery );

