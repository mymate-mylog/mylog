/**
 * MyLog Enhanced Time Picker & User Profile Popup
 * File: mylog-enhancements.js
 */

(function($) {
    'use strict';
    
    // IMMEDIATELY hide error messages before anything else
    $('.mylog-required-error').hide();
    
    /**
     * ENHANCED TIME PICKER
     * Replaces native HTML5 time input with user-friendly dropdown selectors
     */
    function initEnhancedTimePickers() {
        $('.mylog-time-picker-native').each(function() {
            const $input = $(this);
            const fieldName = $input.attr('name');
            const currentValue = $input.val();
            const isRequired = $input.prop('required');
            
            // Find and store the error message if it exists
            const $errorMsg = $input.siblings('.mylog-required-error');
            
            // Generate hour options (00-23)
            let hourOptions = '<option value="">HH</option>';
            for (let i = 0; i <= 23; i++) {
                const hour = i.toString().padStart(2, '0');
                hourOptions += `<option value="${hour}">${hour}</option>`;
            }
            
            // Generate minute options (00, 15, 30, 45)
            const minuteOptions = `
                <option value="">MM</option>
                <option value="00">00</option>
                <option value="15">15</option>
                <option value="30">30</option>
                <option value="45">45</option>
            `;
            
            // Parse current value
            let selectedHour = '';
            let selectedMinute = '';
            if (currentValue && currentValue.match(/^\d{2}:\d{2}/)) {
                [selectedHour, selectedMinute] = currentValue.split(':');
            }
            
            // Create enhanced picker HTML
            const $enhancedPicker = $(`
                <div class="mylog-time-picker-enhanced">
                    <select class="time-hour" data-field="${fieldName}">
                        ${hourOptions}
                    </select>
                    <span class="time-separator">:</span>
                    <select class="time-minute" data-field="${fieldName}">
                        ${minuteOptions}
                    </select>
                    <input type="hidden" name="${fieldName}" class="time-combined" value="${currentValue}" ${isRequired ? 'required' : ''}>
                </div>
            `);
            
            // Set selected values
            $enhancedPicker.find('.time-hour').val(selectedHour);
            $enhancedPicker.find('.time-minute').val(selectedMinute);
            
            // Replace native input
            $input.replaceWith($enhancedPicker);
            
            // Move error message after the new time picker
            if ($errorMsg.length) {
                $enhancedPicker.after($errorMsg);
            }
        });
        
        // Handle time picker changes
        $(document).on('change', '.mylog-time-picker-enhanced select', function() {
            const $container = $(this).closest('.mylog-time-picker-enhanced');
            const hour = $container.find('.time-hour').val();
            const minute = $container.find('.time-minute').val();
            
            if (hour && minute) {
                const timeValue = `${hour}:${minute}`;
                $container.find('.time-combined').val(timeValue);
            } else {
                $container.find('.time-combined').val('');
            }
        });
    }
    
    /**
     * USER CARE PROFILE POPUP
     * "Learn More About [User]" button functionality
     */
    function initUserProfilePopup() {
        // Add "Learn More" button after user selection
        $(document).on('change', '#mylog_user_select', function() {
            const userId = $(this).val();
            const userName = $(this).find('option:selected').text();
            
            // Remove existing button
            $('.mylog-learn-more-btn').remove();
            
            if (userId) {
                const $button = $(`
                    <button type="button" class="mylog-learn-more-btn" data-user-id="${userId}">
                        Learn more about ${userName}
                    </button>
                `);
                
                $(this).after($button);
            }
        });
        
        // Handle "Learn More" button click
        $(document).on('click', '.mylog-learn-more-btn', function(e) {
            e.preventDefault();
            const userId = $(this).data('user-id');
            
            $.ajax({
                url: mylog_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_user_care_profile',
                    user_id: userId,
                    nonce: mylog_ajax.nonce
                },
                beforeSend: function() {
                    $('.mylog-learn-more-btn').prop('disabled', true).text('Loading...');
                },
                success: function(response) {
                    if (response.success) {
                        showUserProfileModal(response.data);
                    } else {
                        alert('Could not load user profile');
                    }
                },
                error: function() {
                    alert('An error occurred');
                },
                complete: function() {
                    $('.mylog-learn-more-btn').prop('disabled', false).text('Learn more about user');
                }
            });
        });
    }
    
    /**
     * Display user profile modal
     */
    function showUserProfileModal(profile) {
        // Format communication preference
        const commPrefLabels = {
            'fully_verbal': 'I am fully verbal',
            'uses_tablet': 'I use a tablet',
            'watch_gestures': 'Watch my hands/gestures',
            'limited_speech': 'Limited speech',
            'other': 'Other (see notes)'
        };
        
        const commPrefText = commPrefLabels[profile.communication_prefs] || profile.communication_prefs;
        
        // Calculate age from DOB
        let ageText = '';
        if (profile.dob) {
            const birthDate = new Date(profile.dob);
            const age = Math.floor((new Date() - birthDate) / 31557600000);
            ageText = ` (${age} years old)`;
        }
        
        const modalHtml = `
            <div class="mylog-modal-overlay">
                <div class="mylog-modal-content">
                    <button class="mylog-modal-close">&times;</button>
                    
                    <div class="mylog-profile-header">
                        ${profile.profile_photo ? `<img src="${profile.profile_photo}" alt="Profile photo" class="mylog-profile-photo">` : ''}
                        <div class="mylog-profile-names">
                            <h2>${profile.display_name}</h2>
                            ${profile.nickname ? `<p class="nickname">Preferred name: <strong>${profile.nickname}</strong></p>` : ''}
                            ${profile.dob ? `<p class="dob">Born: ${new Date(profile.dob).toLocaleDateString()}${ageText}</p>` : ''}
                        </div>
                    </div>
                    
                    <div class="mylog-profile-sections">
                        ${profile.happy_when ? `
                            <div class="profile-section green-light">
                                <h3>✓ I am happy when...</h3>
                                <p>${profile.happy_when.replace(/\n/g, '<br>')}</p>
                            </div>
                        ` : ''}
                        
                        ${profile.unhappy_if ? `
                            <div class="profile-section red-light">
                                <h3>⚠ I am not happy if...</h3>
                                <p>${profile.unhappy_if.replace(/\n/g, '<br>')}</p>
                            </div>
                        ` : ''}
                        
                        ${profile.communication_prefs ? `
                            <div class="profile-section communication">
                                <h3>Communication</h3>
                                <p><strong>${commPrefText}</strong></p>
                            </div>
                        ` : ''}
                        
                        ${profile.additional_info ? `
                            <div class="profile-section additional">
                                <h3>Additional Information</h3>
                                <p>${profile.additional_info.replace(/\n/g, '<br>')}</p>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
    }
    
    // Close modal
    $(document).on('click', '.mylog-modal-close, .mylog-modal-overlay', function(e) {
        if (e.target === this) {
            $('.mylog-modal-overlay').remove();
        }
    });
    
    // ESC key closes modal
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('.mylog-modal-overlay').remove();
        }
    });
    
    /**
     * FORM VALIDATION - Show errors only after submission attempt
     */
    function initFormValidation() {
        // Hide all error messages again when DOM is ready
        $('.mylog-required-error').hide();
        
        // Handle form submission
        $('#mylog-entry-form').on('submit', function(e) {
            var form = this;
            
            // Add validation-attempted class to form
            $(form).addClass('validation-attempted');
            
            // Check if form is valid
            if (!form.checkValidity()) {
                e.preventDefault();
                
                // Scroll to first invalid field
                var firstInvalid = $(form).find(':invalid').first();
                if (firstInvalid.length) {
                    $('html, body').animate({
                        scrollTop: firstInvalid.offset().top - 100
                    }, 500);
                }
            }
        });
    }
    
    /**
     * Initialize everything on page load
     */
    $(document).ready(function() {
        initEnhancedTimePickers();
        initUserProfilePopup();
        initFormValidation();
        // Removed initTextareaFix - textareas work natively now
    });
    
})(jQuery);