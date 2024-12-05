(function( $ ) {
	'use strict';

	var spinner='<div class="loading-spinner" id="loading-spinnner"></div>';

	let formSubmittedSuccessfully = false;

	/**
	 * Displays helper text below an input or textarea field when it gains focus.
	 * Removes any existing helper text and appends a new one based on the `data-helper` attribute.
	 *
	 * Event: Focus
	 * Selector: `.vr-form-group input[data-helper], .vr-form-group textarea[data-helper]`
	 *
	 * @since 1.0.0
	 */
	$('#vr-form-content').on('focus', '.vr-form-group input[data-helper], .vr-form-group textarea[data-helper]', function() {
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
	
	/**
	 * Removes the helper text when an input or textarea field loses focus.
	 *
	 * Event: Blur
	 * Selector: `.vr-form-group input[data-helper], .vr-form-group textarea[data-helper]`
	 *
	 * @since 1.0.0
	 */
	$('#vr-form-content').on('blur', '.vr-form-group input[data-helper], .vr-form-group textarea[data-helper]', function() {
		$(this).siblings('.helper-text').remove();
	});
	
	/**
	 * Toggles the visibility and required attribute of the "Other Committee" input field 
	 * based on the selected value of the committee dropdown (`#payee_committee`).
	 * If "Other" is selected, the additional input is shown and required.
	 *
	 * Event: Change
	 * Selector: `#payee_committee`
	 *
	 * @since 1.0.0
	 */
	$('#vr-form-content').on('change', '#payee_committee', function() {
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

	if ($('#payee_committee').length && $('#payee-other-committee').length) {
		// Trigger the change event to apply the logic when elements are present on load
		$('#payee_committee').trigger('change');
	}

	/**
	 * Clears "0" or "00" placeholder values from the `.amount-input` fields when they gain focus.
	 *
	 * Event: Focus
	 * Selector: `.amount-input`
	 *
	 * @since 1.0.0
	 */
	$('#vr-form-content').on('focus', '.amount-input', function () {
		if ($(this).val() === "0" || $(this).val() === "00") {
			$(this).val('');
		}
	});

	/**
	 * Restores default "0" or "00" placeholder values in the `.amount-input` fields 
	 * if they are empty when losing focus.
	 *
	 * Event: Blur
	 * Selector: `.amount-input`
	 *
	 * @since 1.0.0
	 */
	$('#vr-form-content').on('blur', '.amount-input', function () {
		if ($(this).val() === '') {
			$(this).val($(this).attr('name') === 'dollars' ? '0' : '00');
		}
	});

	/**
	 * Displays the example invoice modal when the thumbnail is clicked.
	 *
	 * Event: Click
	 * Selector: `#vr-example-invoice-thumbnail`
	 *
	 * @since 1.0.0
	 */
	$('#vr-form-content').on('click', '#vr-example-invoice-thumbnail', function () {
		$('#vr-example-invoice-modal').css("display","flex");
	});

	/**
	 * Closes the modal when the user clicks on the background or the close button.
	 * Prevents closing if the click is inside the modal content.
	 *
	 * Event: Click
	 * Selector: `.vr-modal-close, #vr-example-invoice-modal`
	 *
	 * @since 1.0.0
	 */
	$('#vr-form-content').on('click', '.vr-modal-close, #vr-example-invoice-modal', function (event) {
		if (event.target !== this) return; // Prevent closing when clicking on modal content
		$('#vr-example-invoice-modal').css("display", "none");
	});
	

	/**
	 * Submits the claim type form via AJAX, displaying the appropriate form content 
	 * based on the selected claim type.
	 *
	 * Event: Submit
	 * Selector: `#claim-type-form`
	 *
	 * @since 1.0.0
	 */
	$('#claim-type-form').on('submit', function(e) {
		e.preventDefault();
		
		var claim_type = $('#claim_type').val();
		
		$('#vr-form-content').html(spinner);

		// AJAX request to process the form
		$.ajax({
			url: Theme_Variables.ajax_url,
			type: 'POST',
			data: {
				action: 'claim_type_selection',
				claim_type: claim_type,
				nonce: Theme_Variables.nonce
			},
			success: function(response) {
				// Display the response inside the #vr-form-content div
				$('#vr-form-content').html(response.data['content']);
			},
			error: function() {
				$('#vr-form-content').html('<p style="color:red;">There was an error processing your request. Please try again.</p>');
			}
		}).always(function(){
			$('.loading-spinner').remove();
		});
	});

	/**
	 * Submits the reimbursement form via AJAX, processes files and text fields, and 
	 * displays a success or error message based on the server response.
	 *
	 * Event: Submit
	 * Selector: `#reimbursement-form`
	 *
	 * @since 1.0.0
	 */
	$('#vr-form-content').on('submit', '#reimbursement-form', function(e) {
		e.preventDefault();
		$('#reimbursement-form').append('<div id="vr-form-response"></div>');

		$('#vr-form-response').append(spinner);
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
					$('#vr-form-response').html('<p style="color:green;">' + response.data['message'] + '</p>');
					$('#reimbursement-form')[0].reset(); // Clear the form on success
					$('#vr-file-list').empty();
					uploadedFiles=[];
					formSubmittedSuccessfully = true;
				} else {
					$('#vr-form-response').html('<p style="color:red;">' + response.data['message'] + '</p>');
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.error("AJAX Error:", textStatus, errorThrown);
				$('#vr-form-response').html('<p style="color:red;">There was an error processing your request. Please try again later.</p>');
			}
		}).always(function(){
			$('#vr-form-response').find('.loading-spinner').remove();

		});
	});

	/**
	 * Clears the response message from the reimbursement form when an input, textarea, 
	 * or select element is interacted with, if the form was previously submitted successfully.
	 *
	 * Event: Focus, Click
	 * Selector: `#reimbursement-form input, #reimbursement-form textarea, #reimbursement-form select`
	 *
	 * @since 1.0.0
	 */
	$('#reimbursement-form').on('focus click', 'input, textarea, select', function () {
		if (formSubmittedSuccessfully) {
			$('#vr-form-response').empty();
			formSubmittedSuccessfully = false; // Reset the flag after clearing the response
		}
	});

	/**
	 * Handles the selection of files for upload. Adds files to the `uploadedFiles` list 
	 * and prevents duplicate entries. Clears the file input after selection.
	 *
	 * Event: Change
	 * Selector: `#vr-multiple-file-input`
	 *
	 * @since 1.0.0
	 */
	let uploadedFiles = [];
	$('#vr-form-content').on('change', '#vr-multiple-file-input', function (e) {
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

	/**
	 * Updates and displays the list of uploaded files, including their names, sizes, 
	 * and a remove button for each file.
	 *
	 * @since 1.0.0
	 */
	function updateFileList() {
		$('#vr-file-list').empty();
		uploadedFiles.forEach((file, index) => {
			$('#vr-file-list').append(`<li>${file.name} (${(file.size / 1024).toFixed(1)} KB) 
				<button data-index="${index}" class="remove-btn">Remove</button></li>`);
		});
	}

	/**
	 * Removes a file from the `uploadedFiles` list based on its index and updates the display.
	 *
	 * Event: Click
	 * Selector: `.remove-btn`
	 *
	 * @since 1.0.0
	 */
	$('#vr-form-content').on('click', '.remove-btn', function () {
		const fileIndex = $(this).data('index');
		uploadedFiles.splice(fileIndex, 1);
		updateFileList();
	});



	
})( jQuery );

