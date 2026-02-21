/**
 * MyLog Edit User Handler
 * Handles the edit modal on Manage Users page
 */

jQuery(document).ready(function($) {
    
    // Open edit modal when Edit button clicked
    $(document).on('click', '.mylog-edit-user-btn', function(e) {
        e.preventDefault();
        
        const userId = $(this).data('user-id');
        const $modal = $('#mylog-edit-user-modal');
        
        // Show modal
        $modal.show();
        
        // Fetch user details
        $.ajax({
            url: mylogUserForm.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mylog_get_user_details',
                nonce: mylogUserForm.nonce,
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    populateEditForm(response.data.user);
                } else {
                    alert(response.data.message);
                    $modal.hide();
                }
            },
            error: function() {
                alert('Failed to load user details.');
                $modal.hide();
            }
        });
    });
    
    // Populate the edit form
    function populateEditForm(user) {
        $('#edit_user_id').val(user.user_id);
        $('#edit_full_name').val(user.full_name || '');
        $('#edit_preferred_name').val(user.preferred_name || '');
        $('#edit_person_goals').val(user.person_goals || '');
        $('#edit_happy_when').val(user.happy_when || '');
        $('#edit_unhappy_when').val(user.unhappy_when || '');
        $('#edit_comm_method').val(user.comm_method || '');
        $('#edit_comm_notes').val(user.comm_notes || '');
        $('#edit_additional_info').val(user.additional_info || '');
        $('#edit_profile_photo').val(user.profile_photo_id || '');
        
        // Photo preview
        if (user.profile_photo_url) {
            $('#edit-photo-preview').html('<img src="' + user.profile_photo_url + '" alt="Profile photo">');
            $('#edit-remove-photo-btn').show();
        } else {
            $('#edit-photo-preview').html(
                '<span class="mylog-photo-placeholder">' +
                '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>' +
                '<circle cx="12" cy="7" r="4"></circle>' +
                '</svg>' +
                '</span>'
            );
            $('#edit-remove-photo-btn').hide();
        }
    }
    
    // Close modal
    $(document).on('click', '.mylog-modal-close, .mylog-modal-overlay', function(e) {
        e.preventDefault();
        $('#mylog-edit-user-modal').hide();
    });
    
    // Close on escape
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('#mylog-edit-user-modal').hide();
        }
    });
    
    // Handle edit form submission
    $('#mylog-edit-user-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const $response = $('#edit-mylog-form-response');
        
        $submitBtn.addClass('is-loading').prop('disabled', true);
        $response.hide();
        
        const formData = {
            action: 'mylog_update_user',
            nonce: mylogUserForm.nonce,
            user_id: $('#edit_user_id').val(),
            full_name: $('#edit_full_name').val(),
            preferred_name: $('#edit_preferred_name').val(),
            profile_photo: $('#edit_profile_photo').val(),
            person_goals: $('#edit_person_goals').val(),
            happy_when: $('#edit_happy_when').val(),
            unhappy_when: $('#edit_unhappy_when').val(),
            comm_method: $('#edit_comm_method').val(),
            comm_notes: $('#edit_comm_notes').val(),
            additional_info: $('#edit_additional_info').val()
        };
        
        $.ajax({
            url: mylogUserForm.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $response
                        .removeClass('error')
                        .addClass('success')
                        .html('<p>' + response.data.message + '</p>')
                        .show();
                    
                    setTimeout(function() {
                        $('#mylog-edit-user-modal').hide();
                        location.reload();
                    }, 1500);
                } else {
                    $response
                        .removeClass('success')
                        .addClass('error')
                        .html('<p>' + response.data.message + '</p>')
                        .show();
                    
                    $submitBtn.removeClass('is-loading').prop('disabled', false);
                }
            },
            error: function() {
                $response
                    .removeClass('success')
                    .addClass('error')
                    .html('<p>An error occurred. Please try again.</p>')
                    .show();
                
                $submitBtn.removeClass('is-loading').prop('disabled', false);
            }
        });
    });
    
    // Edit photo upload
    const $editFileInput = $('<input type="file" id="edit-photo-file-input" accept="image/*" style="display:none;">');
    $('body').append($editFileInput);
    
    $(document).on('click', '#edit-upload-photo-btn', function(e) {
        e.preventDefault();
        $editFileInput.click();
    });
    
    $editFileInput.on('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        if (!file.type.match('image.*')) {
            alert('Please select an image file.');
            return;
        }
        
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB.');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'mylog_upload_photo');
        formData.append('nonce', mylogUserForm.nonce);
        formData.append('file', file);
        
        const $uploadBtn = $('#edit-upload-photo-btn');
        const originalText = $uploadBtn.text();
        $uploadBtn.prop('disabled', true).text('Uploading...');
        
        $.ajax({
            url: mylogUserForm.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#edit_profile_photo').val(response.data.attachment_id);
                    $('#edit-photo-preview').html('<img src="' + response.data.url + '" alt="Profile photo">');
                    $('#edit-remove-photo-btn').show();
                } else {
                    alert(response.data.message || 'Upload failed.');
                }
                $uploadBtn.prop('disabled', false).text(originalText);
            },
            error: function() {
                alert('Upload failed.');
                $uploadBtn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    $(document).on('click', '#edit-remove-photo-btn', function(e) {
        e.preventDefault();
        $('#edit_profile_photo').val('');
        $('#edit-photo-preview').html(
            '<span class="mylog-photo-placeholder">' +
            '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
            '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>' +
            '<circle cx="12" cy="7" r="4"></circle>' +
            '</svg>' +
            '</span>'
        );
        $(this).hide();
        $editFileInput.val('');
    });
});