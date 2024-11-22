(function( $ ) {
	'use strict';

	var spinner='<div class="loading-spinner" id="loading-spinnner"></div>';

	// let uploadedFiles = {};
	// variable uploadedFiles is passed from php

	$(document).ready(function () {
		if ($('#vr-file-list').length) {
			updateFileList(); // Display files from `uploadedFiles` on page load
		}
	});

	$('#vr-multiple-file-input').on('change', function (e) {
		let newFiles = Array.from(e.target.files);

		// Add new files to the uploaded files list
		newFiles.forEach(file => {
			let fileExists = Object.values(uploadedFiles).some(f => f.name === file.name && f.size === file.size);
			if (!fileExists) {
				uploadedFiles[file.name] = {
					name: file.name,
					url: URL.createObjectURL(file), // temporary URL for preview
					size: file.size,
					file: file
				};
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
		for (let fileName in uploadedFiles) {
			if (uploadedFiles.hasOwnProperty(fileName)) {
				let fileData = uploadedFiles[fileName];
				$('#vr-file-list').append(`<li><a href="${fileData.url}" target="_blank">${fileData.name}</a>
					<button data-index="${fileName}" class="remove-btn button">Remove</button></li>`);
			}
		}
	}

    	// Remove a file from the uploaded files list
	$('#vr-file-list').on('click', '.remove-btn', function () {
		const fileIndex = $(this).data('index');
		delete uploadedFiles[fileIndex]; // Remove from the uploadedFiles object
		updateFileList();
	});

	$('#vr-claim-form').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
		$('#form-response').html(spinner);
        // let formData = $(this).serializeArray();
		var formData = new FormData(this);

		formData.set('action', 'save_admin_claim_form');

		formData.delete('meta[attachments][]');
		for (let fileName in uploadedFiles) {
			if (uploadedFiles.hasOwnProperty(fileName)) {
				let fileData = uploadedFiles[fileName];
				if (fileData.hasOwnProperty('file')){
					// this will go into $_FILE
					formData.append(`attachments[]`, fileData.file);

				}else{
					formData.append(`meta[attachments][${fileName}]`, fileData.url);
				}

			}
		}
        // formData.push({ name: 'nonce', value: vr_ajax.nonce });

        $.ajax({
			url: Theme_Variables.ajax_url,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response){
				if (response.data['status'] ==='success'){
					$('#form-response').html('<p style="color:green;">' + response.data['message'] + '</p>');
				}else{
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

	// Use event delegation to watch for changes on #payee_committee within #form-content
	$('#payee_committee').on('change', function() {
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

	let export_aba_button_status=false;

	// modalHtml is defined in php class-volunteer-reimbursement-admin.php
	$('#export_aba').on('click', function(e){
		e.preventDefault();

		if (export_aba_button_status){
			$('#export-aba-modal').remove();
			export_aba_button_status=false;

		}else{
			$('.tablenav.top').after(modalHtml);
			export_aba_button_status=true;
		}

	});

	$("#vr_reimbursement_table").on('click', "#submit_aba_export", function(e){
        e.preventDefault(); // Prevent default form submission

		var submitButton = $(this);
		var wrapper = submitButton.parent();

		submitButton.prop('disabled', true);
		wrapper.append(spinner);

		var formData = new FormData($('#vr_reimbursement_table').get(0));
		formData.set('action', 'submit_aba_export');
		$.ajax({
			url: Theme_Variables.ajax_url,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response){
				if (response.data['status'] ==='success'){
					$('#form-response').html('<p style="color:green;">' + response.data['message'] + '</p>');

					const blob = new Blob([response.data.file_content], { type: 'text/plain' });
					const downloadLink = document.createElement('a');
					downloadLink.href = window.URL.createObjectURL(blob);
					downloadLink.download = 'exported_file.aba'; // Specify the file name
					document.body.appendChild(downloadLink);
					downloadLink.click(); // Trigger the download
					document.body.removeChild(downloadLink); // Clean up the DOM
					
				}else{
					$('#form-response').html('<p style="color:red;">' + response.data['message'] + '</p>');
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.error("AJAX Error:", textStatus, errorThrown);
				$('#form-response').html('<p style="color:red;">There was an error processing your request. Please try again later.</p>');
			}
		}).always(function(){
			submitButton.prop('disabled', false);
			wrapper.find('.loading-spinner').remove();

		});

	});

	$('#export_xero').on('click', function(e){
		e.preventDefault();
        var submitButton = $(this);
		var wrapper = submitButton.parent();

        submitButton.prop('disabled', true);
		wrapper.append(spinner);

		// Get the form element
		var actionSelect = $('#bulk-action-selector-top');

		// Add 'export_xero' as an option if it doesn't already exist
		if (actionSelect.find('option[value="export_xero"]').length === 0) {
			actionSelect.append('<option value="export_xero">Export to Xero</option>');
		}
	
		// Select the 'export_xero' option
		actionSelect.val('export_xero');

		// Find an existing input[name="action"], if any
		// form.action='export_xero';
		$('#vr_reimbursement_table').get(0).submit();
		
		actionSelect.find('option[value="export_xero"]').remove();
		submitButton.prop('disabled', false);
		wrapper.find('.loading-spinner').remove();

	});


})( jQuery );
