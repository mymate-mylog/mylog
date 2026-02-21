/**
 * MyLog Enhanced User Form JavaScript
 * Version: 3.0.0
 * Handles: Photo upload, form submission, user details modal, edit functionality
 */

(function($) {
    'use strict';
    
    const MyLogUserForm = {
        
        /**
         * Initialize
         */
        init: function() {
            this.setupPhotoUpload();
            this.setupFormSubmission();
            this.setupUserDetailsModal();
            this.setupEditUser();
        },
        
        /**
         * Native File Upload for Profile Photo
         */
        setupPhotoUpload: function() {
            // Create hidden file input
            const $fileInput = $('<input type="file" id="mylog-photo-file" accept="image/*" style="display:none;">');
            $('body').append($fileInput);
            
            // Upload button click - trigger file browser
            $('#upload-photo-btn').on('click', function(e) {
                e.preventDefault();
                $fileInput.click();
            });
            
            // When file is selected
            $fileInput.on('change', function(e) {
                const file = e.target.files[0];
                
                if (!file) return;
                
                // Validate file type
                if (!file.type.match('image.*')) {
                    alert('Please select an image file.');
                    return;
                }
                
                // Validate file size (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB.');
                    return;
                }
                
                // Create form data for upload
                const formData = new FormData();
                formData.append('action', 'mylog_upload_photo');
                formData.append('nonce', mylogUserForm.nonce);
                formData.append('file', file);
                
                // Show loading state
                const $uploadBtn = $('#upload-photo-btn');
                const originalText = $uploadBtn.text();
                $uploadBtn.prop('disabled', true).text('Uploading...');
                
                // Upload via AJAX
                $.ajax({
                    url: mylogUserForm.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // Set hidden input value
                            $('#profile_photo').val(response.data.attachment_id);
                            
                            // Update preview
                            $('#photo-preview').html(
                                '<img src="' + response.data.url + '" alt="Profile photo">'
                            );
                            
                            // Show remove button
                            $('#remove-photo-btn').show();
                        } else {
                            alert(response.data.message || 'Upload failed. Please try again.');
                        }
                        
                        $uploadBtn.prop('disabled', false).text(originalText);
                    },
                    error: function() {
                        alert('Upload failed. Please try again.');
                        $uploadBtn.prop('disabled', false).text(originalText);
                    }
                });
            });
            
            // Remove photo
            $('#remove-photo-btn').on('click', function(e) {
                e.preventDefault();
                
                $('#profile_photo').val('');
                $('#photo-preview').html(
                    '<span class="mylog-photo-placeholder">' +
                    '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                    '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>' +
                    '<circle cx="12" cy="7" r="4"></circle>' +
                    '</svg>' +
                    '</span>'
                );
                
                $(this).hide();
                $fileInput.val(''); // Reset file input
            });
        },
        
        /**
         * Handle form submission via AJAX
         */
        setupFormSubmission: function() {
            $('#mylog-add-user-form').on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const $submitBtn = $form.find('button[type="submit"]');
                const $response = $('#mylog-form-response');
                const isEdit = $form.attr('id') === 'mylog-edit-user-form';
                const action = isEdit ? 'mylog_update_user' : 'mylog_add_user';
                
                // Disable submit button
                $submitBtn.addClass('is-loading').prop('disabled', true);
                $response.hide();
                
                // Prepare form data
                const formData = {
                    action: action,
                    nonce: mylogUserForm.nonce,
                    full_name: $('#full_name').val(),
                    preferred_name: $('#preferred_name').val(),
                    profile_photo: $('#profile_photo').val(),
                    person_goals: $('#person_goals').val(),
                    happy_when: $('#happy_when').val(),
                    unhappy_when: $('#unhappy_when').val(),
                    comm_method: $('#comm_method').val(),
                    comm_notes: $('#comm_notes').val(),
                    additional_info: $('#additional_info').val()
                };
                
                // Add user_id for edit
                if (isEdit) {
                    formData.user_id = $('#user_id').val();
                }
                
                // AJAX request
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
                            
                            // Redirect after short delay
                            if (response.data.redirect) {
                                setTimeout(function() {
                                    window.location.href = response.data.redirect;
                                }, 1500);
                            } else if (isEdit) {
                                // Close modal and refresh page
                                setTimeout(function() {
                                    $('.mylog-modal').removeClass('is-active');
                                    location.reload();
                                }, 1500);
                            }
                        } else {
                            $response
                                .removeClass('success')
                                .addClass('error')
                                .html('<p>' + response.data.message + '</p>')
                                .show();
                            
                            $submitBtn.removeClass('is-loading').prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        $response
                            .removeClass('success')
                            .addClass('error')
                            .html('<p>An error occurred. Please try again.</p>')
                            .show();
                        
                        $submitBtn.removeClass('is-loading').prop('disabled', false);
                        
                        console.error('Form submission error:', error);
                    }
                });
            });
        },
        
        /**
         * User Details Modal (Learn More About User)
         */
        setupUserDetailsModal: function() {
            // Create modal HTML if it doesn't exist
            if ($('#mylog-user-details-modal').length === 0) {
                $('body').append(`
                    <div id="mylog-user-details-modal" class="mylog-modal">
                        <div class="mylog-modal-content">
                            <div class="mylog-modal-header">
                                <h2 class="mylog-modal-title">
                                    <span id="modal-user-name"></span>
                                </h2>
                                <button type="button" class="mylog-modal-close" aria-label="Close">&times;</button>
                            </div>
                            <div class="mylog-modal-body" id="modal-user-details">
                                <!-- Content loaded via AJAX -->
                            </div>
                        </div>
                    </div>
                `);
            }
            
            // Open modal
            $(document).on('click', '.mylog-learn-more-btn', function(e) {
                e.preventDefault();
                
                const userId = $(this).data('user-id');
                const $modal = $('#mylog-user-details-modal');
                
                // Show loading state
                $('#modal-user-details').html('<p>Loading...</p>');
                $modal.addClass('is-active');
                
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
                            const user = response.data.user;
                            MyLogUserForm.renderUserDetails(user);
                        } else {
                            $('#modal-user-details').html('<p class="error">' + response.data.message + '</p>');
                        }
                    },
                    error: function() {
                        $('#modal-user-details').html('<p class="error">Failed to load user details.</p>');
                    }
                });
            });
            
            // Close modal
            $(document).on('click', '.mylog-modal-close, .mylog-modal', function(e) {
                if (e.target === this) {
                    $('.mylog-modal').removeClass('is-active');
                }
            });
            
            // Close on escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('.mylog-modal').removeClass('is-active');
                }
            });
        },
        
        /**
         * Render user details in modal
         */
        renderUserDetails: function(user) {
            const displayName = user.preferred_name || user.full_name;
            const commMethodLabels = {
                'fully_verbal': 'I am fully verbal',
                'limited_verbal': 'I use some words/phrases',
                'gestures': 'Watch my hands/gestures',
                'tablet': 'I use a tablet/communication device',
                'visual': 'I respond to pictures/visual aids',
                'other': 'Other'
            };
            
            let html = '';
            
            // Photo
            if (user.profile_photo_url) {
                html += `
                    <div class="mylog-user-detail" style="text-align: center;">
                        <img src="${user.profile_photo_url}" alt="${displayName}" class="mylog-user-photo-large">
                    </div>
                `;
            }
            
            // Full Name
            html += `
                <div class="mylog-user-detail">
                    <div class="mylog-user-detail-label">Full Name</div>
                    <div class="mylog-user-detail-value">${user.full_name}</div>
                </div>
            `;
            
            // Preferred Name
            if (user.preferred_name) {
                html += `
                    <div class="mylog-user-detail">
                        <div class="mylog-user-detail-label">Preferred Name</div>
                        <div class="mylog-user-detail-value">${user.preferred_name}</div>
                    </div>
                `;
            }
            
            // Goals & Aspirations
            if (user.person_goals) {
                html += `
                    <div class="mylog-user-detail" style="background:#f5f3ff;border-left:3px solid #6366f1;padding:10px 12px;border-radius:0 6px 6px 0;margin-bottom:8px;">
                        <div class="mylog-user-detail-label" style="color:#6366f1;font-weight:700;">⭐ My Current Goals & Aspirations</div>
                        <div class="mylog-user-detail-value" style="white-space:pre-wrap;"></div>
                    </div>
                `;
                // Set via DOM to avoid XSS and encoding issues
                $('#modal-user-details .mylog-user-detail:last-of-type .mylog-user-detail-value').text(user.person_goals);
            }
            
            // Happy When
            if (user.happy_when) {
                html += `
                    <div class="mylog-user-detail">
                        <div class="mylog-user-detail-label">
                            <span class="mylog-traffic-light mylog-traffic-light--green">●</span> I am happy if
                        </div>
                        <div class="mylog-user-detail-value">${this.escapeHtml(user.happy_when)}</div>
                    </div>
                `;
            }
            
            // Unhappy When
            if (user.unhappy_when) {
                html += `
                    <div class="mylog-user-detail">
                        <div class="mylog-user-detail-label">
                            <span class="mylog-traffic-light mylog-traffic-light--red">●</span> I am not happy if
                        </div>
                        <div class="mylog-user-detail-value">${this.escapeHtml(user.unhappy_when)}</div>
                    </div>
                `;
            }
            
            // Communication
            if (user.comm_method) {
                const commLabel = commMethodLabels[user.comm_method] || user.comm_method;
                html += `
                    <div class="mylog-user-detail">
                        <div class="mylog-user-detail-label">Communication</div>
                        <div class="mylog-user-detail-value">${commLabel}</div>
                    </div>
                `;
            }
            
            if (user.comm_notes) {
                html += `
                    <div class="mylog-user-detail">
                        <div class="mylog-user-detail-label">Communication Notes</div>
                        <div class="mylog-user-detail-value">${this.escapeHtml(user.comm_notes)}</div>
                    </div>
                `;
            }
            
            // Additional Info
            if (user.additional_info) {
                html += `
                    <div class="mylog-user-detail">
                        <div class="mylog-user-detail-label">Additional Information</div>
                        <div class="mylog-user-detail-value">${this.escapeHtml(user.additional_info)}</div>
                    </div>
                `;
            }
            
            $('#modal-user-name').text(displayName);
            $('#modal-user-details').html(html);
        },
        
        /**
         * Setup Edit User functionality
         * Edit modal and form handling is managed entirely by mylog-edit-handler.js
         * which targets the hardcoded modal in shortcodes.php. This function is a
         * no-op to prevent duplicate AJAX calls and conflicting field writes.
         */
        setupEditUser: function() {
            // Intentionally empty — handled by mylog-edit-handler.js
        },
        
        /**
         * Populate edit form with user data
         */
        populateEditForm: function(user) {
            // Build skeleton with NO user data inline in the template literal.
            // All values are set below via jQuery .val() which handles any characters
            // safely — backticks, apostrophes, HTML entities, newlines — without corruption.
            const formHtml = `
                <form id="mylog-edit-user-form" class="mylog-form mylog-form--lean">
                    <input type="hidden" id="user_id" name="user_id" value="${user.user_id}">
                    
                    <div class="mylog-form-group">
                        <label for="full_name" class="mylog-label mylog-label--required">Full Name (Legal/Official)</label>
                        <input type="text" id="full_name" name="full_name" class="mylog-input" required>
                    </div>
                    
                    <div class="mylog-form-group">
                        <label for="preferred_name" class="mylog-label">Nickname / Preferred Name</label>
                        <input type="text" id="preferred_name" name="preferred_name" class="mylog-input">
                    </div>
                    
                    <div class="mylog-form-group">
                        <label for="person_goals" class="mylog-label">My Current Goals &amp; Aspirations</label>
                        <textarea id="person_goals" name="person_goals" class="mylog-textarea" rows="4" maxlength="1000"
                                  placeholder="What would you like to achieve?"></textarea>
                        <span class="mylog-help-text">What matters most to you? What would you like to achieve?</span>
                    </div>
                    
                    <div class="mylog-form-group">
                        <label for="happy_when" class="mylog-label"><span style="color:#2e7d32;">●</span> I am happy if</label>
                        <textarea id="happy_when" name="happy_when" class="mylog-textarea" rows="3"></textarea>
                    </div>
                    
                    <div class="mylog-form-group">
                        <label for="unhappy_when" class="mylog-label"><span style="color:#d32f2f;">●</span> I am not happy if</label>
                        <textarea id="unhappy_when" name="unhappy_when" class="mylog-textarea" rows="3"></textarea>
                    </div>
                    
                    <div class="mylog-form-group">
                        <label for="comm_method" class="mylog-label">Communication Preferences</label>
                        <select id="comm_method" name="comm_method" class="mylog-select">
                            <option value="">Select primary method...</option>
                            <option value="fully_verbal">I am fully verbal</option>
                            <option value="limited_verbal">I use some words/phrases</option>
                            <option value="gestures">Watch my hands/gestures</option>
                            <option value="tablet">I use a tablet/communication device</option>
                            <option value="visual">I respond to pictures/visual aids</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mylog-form-group">
                        <label for="comm_notes" class="mylog-label">Additional Communication Notes</label>
                        <textarea id="comm_notes" name="comm_notes" class="mylog-textarea" rows="2"></textarea>
                    </div>
                    
                    <div class="mylog-form-group">
                        <label for="additional_info" class="mylog-label">Other Important/Emergency Information</label>
                        <textarea id="additional_info" name="additional_info" class="mylog-textarea" rows="3"></textarea>
                    </div>
                    
                    <div class="mylog-form-actions">
                        <button type="submit" class="mylog-btn mylog-btn--primary mylog-btn--block" style="width:100%;background:#3b82f6;color:white;border:none;padding:14px 20px;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;">
                            <span class="mylog-btn-text">Save Changes</span>
                            <span class="mylog-btn-loading" style="display:none;">Saving...</span>
                        </button>
                    </div>
                    
                    <div id="mylog-form-response" class="mylog-form-response" style="display:none;margin-top:15px;"></div>
                </form>
            `;
            
            $('#edit-user-form-container').html(formHtml);
            
            // Set all values safely via jQuery after DOM insertion
            $('#full_name').val(user.full_name || '');
            $('#preferred_name').val(user.preferred_name || '');
            $('#person_goals').val(user.person_goals || '');
            $('#happy_when').val(user.happy_when || '');
            $('#unhappy_when').val(user.unhappy_when || '');
            $('#comm_method').val(user.comm_method || '');
            $('#comm_notes').val(user.comm_notes || '');
            $('#additional_info').val(user.additional_info || '');
        },
        
        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml: function(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        MyLogUserForm.init();
    });
    
})(jQuery);