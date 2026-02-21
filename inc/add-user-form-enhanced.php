<?php
/**
 * Enhanced Add User Form - PHP 8.4 Compatible (Standalone)
 * MyLog v3.0.2 - MyMate Platform
 * Ultra-simple version for maximum compatibility
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Shortcode to display form
function mylog_add_user_form_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Please log in to add users.</p>';
    }
    
    // No max users limit - admin can add unlimited
    
    ob_start();
    ?>
    <div class="mylog-form-container">
        <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem; color: #1a202c; font-weight: 600;">Add Person to Support</h2>
        <form id="mylog-add-user-form" class="mylog-form mylog-form--lean" method="post">
            
            <?php wp_nonce_field('mylog_add_user_action', 'mylog_add_user_nonce'); ?>
            
            <div class="mylog-form-group">
                <label for="full_name" class="mylog-label mylog-label--required">
                    Full Name (Legal/Official)
                </label>
                <input type="text" id="full_name" name="full_name" class="mylog-input" required 
                       placeholder="As appears on official identification">
                <span class="mylog-help-text">Used for official records and documentation</span>
            </div>
            
            <div class="mylog-form-group">
                <label for="preferred_name" class="mylog-label">
                    Nickname / Preferred Name
                </label>
                <input type="text" id="preferred_name" name="preferred_name" class="mylog-input" 
                       placeholder="The name used in daily interactions">
                <span class="mylog-help-text">How carers should address this person</span>
            </div>
            
            <div class="mylog-form-group mylog-form-group--photo">
                <label for="profile_photo" class="mylog-label">Profile Photo</label>
                <div class="mylog-photo-upload">
                    <div class="mylog-photo-preview" id="photo-preview">
                        <span class="mylog-photo-placeholder">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </span>
                    </div>
                    <div class="mylog-photo-actions">
                        <button type="button" class="mylog-btn mylog-btn--secondary" id="upload-photo-btn">
                            Upload Photo
                        </button>
                        <button type="button" class="mylog-btn mylog-btn--text" id="remove-photo-btn" style="display:none;">
                            Remove
                        </button>
                    </div>
                    <input type="hidden" id="profile_photo" name="profile_photo" value="">
                    <span class="mylog-help-text">Helps carers provide consistent, personalized support</span>
                </div>
            </div>
            
            <div class="mylog-form-group">
                <label for="person_goals" class="mylog-label">
                    My Current Goals &amp; Aspirations
                </label>
                <textarea id="person_goals" name="person_goals" class="mylog-textarea" rows="4"
                          maxlength="1000"
                          placeholder="E.g., 'I want to shower independently', 'Join community activities twice a week', 'Do shopping on my own'"></textarea>
                <span class="mylog-help-text">What matters most to you? What would you like to achieve?</span>
                <span class="mylog-char-counter" id="person_goals_counter" style="font-size:0.75rem;color:#6b7280;display:block;text-align:right;margin-top:2px;">0 / 1000 characters</span>
            </div>

            <div class="mylog-form-group">
                <label for="happy_when" class="mylog-label">
                    <span class="mylog-traffic-light mylog-traffic-light--green">●</span>
                    I am happy if
                </label>
                <textarea id="happy_when" name="happy_when" class="mylog-textarea" rows="3"
                          placeholder="E.g., when we listen to jazz, when I'm in the sun..."></textarea>
                <span class="mylog-help-text">What brings joy, comfort, and engagement</span>
            </div>
            
            <div class="mylog-form-group">
                <label for="unhappy_when" class="mylog-label">
                    <span class="mylog-traffic-light mylog-traffic-light--red">●</span>
                    I am not happy if
                </label>
                <textarea id="unhappy_when" name="unhappy_when" class="mylog-textarea" rows="3"
                          placeholder="E.g., if I'm rushed, if the TV is too loud..."></textarea>
                <span class="mylog-help-text">Triggers, sensory dislikes, or frustrations to avoid</span>
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
                    <option value="other">Other (please specify below)</option>
                </select>
            </div>
            
            <div class="mylog-form-group">
                <label for="comm_notes" class="mylog-label">Additional Communication Notes</label>
                <textarea id="comm_notes" name="comm_notes" class="mylog-textarea" rows="2"
                          placeholder="Specific details about how this person communicates best..."></textarea>
            </div>
            
            <div class="mylog-form-group">
                <label for="additional_info" class="mylog-label">Other Important/Emergency Information</label>
                <textarea id="additional_info" name="additional_info" class="mylog-textarea" rows="3"
                          placeholder="Daily routines, Cultural considerations, Emergency Procedures..."></textarea>
                <span class="mylog-help-text">Non-clinical information that helps provide personalized support</span>
            </div>
            
            <div class="mylog-form-actions">
                <button type="submit" class="mylog-btn mylog-btn--primary mylog-btn--block">
                    <span class="mylog-btn-text">Add Person</span>
                    <span class="mylog-btn-loading" style="display:none;">Saving...</span>
                </button>
            </div>
            
            <div id="mylog-form-response" class="mylog-form-response" style="display:none;"></div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('mylog_add_user_form', 'mylog_add_user_form_shortcode');

// Character counter script for person_goals
add_action('wp_footer', function() {
    if (!is_page(['add-user', 'manage-users'])) return;
    ?>
    <script>
    (function() {
        var ta = document.getElementById('person_goals');
        var counter = document.getElementById('person_goals_counter');
        if (ta && counter) {
            ta.addEventListener('input', function() {
                var len = this.value.length;
                counter.textContent = len + ' / 1000 characters';
                counter.style.color = len > 900 ? '#dc2626' : '#6b7280';
            });
        }
    })();
    </script>
    <?php
});

// Enqueue assets
function mylog_enqueue_user_form_assets() {
    if (!is_page('add-user') && !is_page('manage-users') && !is_page('add-entry')) {
        return;
    }
    
    wp_enqueue_media();
    
    wp_enqueue_style(
        'mylog-user-form',
        get_stylesheet_directory_uri() . '/css/mylog-user-form.css',
        array(),
        '3.0.2'
    );
    
    wp_enqueue_script(
        'mylog-user-form',
        get_stylesheet_directory_uri() . '/js/mylog-user-form.js',
        array('jquery'),
        '3.0.2',
        true
    );
    
    // Enqueue edit handler for manage users page
    if (is_page('manage-users')) {
        wp_enqueue_script(
            'mylog-edit-handler',
            get_stylesheet_directory_uri() . '/js/mylog-edit-handler.js',
            array('jquery', 'mylog-user-form'),
            '1.0.0',
            true
        );
    }
    
    wp_localize_script('mylog-user-form', 'mylogUserForm', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mylog_user_form'),
        'maxUsers' => 999 // No limit for admin
    ));
}
add_action('wp_enqueue_scripts', 'mylog_enqueue_user_form_assets');

// AJAX: Add user
function mylog_ajax_add_user() {
    check_ajax_referer('mylog_user_form', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in.'));
    }
    
    $current_user_id = get_current_user_id();
    $supported_users = get_user_meta($current_user_id, 'mylog_supported_users', true);
    
    if (!is_array($supported_users)) {
        $supported_users = array();
    }
    
    // Admins have no limit — all others go through subscription-aware check
    if (!current_user_can('administrator')) {
        if (!mylog_check_user_limit()) {
            $limit = mylog_get_user_limit();
            wp_send_json_error(array(
                'message' => 'Maximum users reached for your plan (limit: ' . $limit . '). Please upgrade your subscription to add more people.'
            ));
        }
    }
    
    $full_name = isset($_POST['full_name']) ? sanitize_text_field($_POST['full_name']) : '';
    
    if (empty($full_name)) {
        wp_send_json_error(array('message' => 'Full name is required.'));
    }
    
    // Create user as custom post type (for compatibility with existing system)
    $post_data = array(
        'post_title' => $full_name,
        'post_type' => 'mylog_user',
        'post_status' => 'publish',
        'post_author' => $current_user_id
    );
    
    $post_id = wp_insert_post($post_data);
    
    if (is_wp_error($post_id)) {
        wp_send_json_error(array('message' => 'Failed to create user.'));
    }
    
    // Save all the enhanced metadata
    update_post_meta($post_id, 'mylog_full_name', $full_name);
    update_post_meta($post_id, 'mylog_preferred_name', isset($_POST['preferred_name']) ? sanitize_text_field($_POST['preferred_name']) : '');
    update_post_meta($post_id, 'mylog_profile_photo_id', isset($_POST['profile_photo']) ? absint($_POST['profile_photo']) : 0);
    update_post_meta($post_id, 'mylog_person_goals', isset($_POST['person_goals']) ? sanitize_textarea_field(mb_substr($_POST['person_goals'], 0, 1000, 'UTF-8')) : '');
    update_post_meta($post_id, 'mylog_happy_when', isset($_POST['happy_when']) ? sanitize_textarea_field($_POST['happy_when']) : '');
    update_post_meta($post_id, 'mylog_unhappy_when', isset($_POST['unhappy_when']) ? sanitize_textarea_field($_POST['unhappy_when']) : '');
    update_post_meta($post_id, 'mylog_comm_method', isset($_POST['comm_method']) ? sanitize_text_field($_POST['comm_method']) : '');
    update_post_meta($post_id, 'mylog_comm_notes', isset($_POST['comm_notes']) ? sanitize_textarea_field($_POST['comm_notes']) : '');
    update_post_meta($post_id, 'mylog_additional_info', isset($_POST['additional_info']) ? sanitize_textarea_field($_POST['additional_info']) : '');
    update_post_meta($post_id, 'mylog_family_admin', $current_user_id);
    
    // Also save in user meta for the new system (if needed later)
    $user_data = array(
        'post_id' => $post_id,
        'full_name' => $full_name,
        'preferred_name' => isset($_POST['preferred_name']) ? sanitize_text_field($_POST['preferred_name']) : '',
        'profile_photo_id' => isset($_POST['profile_photo']) ? absint($_POST['profile_photo']) : 0,
        'person_goals' => isset($_POST['person_goals']) ? sanitize_textarea_field(mb_substr($_POST['person_goals'], 0, 1000, 'UTF-8')) : '',
        'happy_when' => isset($_POST['happy_when']) ? sanitize_textarea_field($_POST['happy_when']) : '',
        'unhappy_when' => isset($_POST['unhappy_when']) ? sanitize_textarea_field($_POST['unhappy_when']) : '',
        'comm_method' => isset($_POST['comm_method']) ? sanitize_text_field($_POST['comm_method']) : '',
        'comm_notes' => isset($_POST['comm_notes']) ? sanitize_textarea_field($_POST['comm_notes']) : '',
        'additional_info' => isset($_POST['additional_info']) ? sanitize_textarea_field($_POST['additional_info']) : '',
        'date_added' => current_time('mysql'),
        'added_by' => $current_user_id
    );
    
    $supported_users[$post_id] = $user_data;
    update_user_meta($current_user_id, 'mylog_supported_users', $supported_users);
    
    wp_send_json_success(array(
        'message' => 'Person added successfully!',
        'redirect' => home_url('/manage-users/')
    ));
}
add_action('wp_ajax_mylog_add_user', 'mylog_ajax_add_user');

// AJAX: Get user details
function mylog_ajax_get_user_details() {
    check_ajax_referer('mylog_user_form', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in.'));
    }
    
    $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
    
    if (!$user_id) {
        wp_send_json_error(array('message' => 'Invalid user ID.'));
    }
    
    // Get post
    $post = get_post($user_id);
    
    if (!$post || $post->post_type !== 'mylog_user') {
        wp_send_json_error(array('message' => 'User not found.'));
    }
    
    // Build user data from post meta
    $person_goals = get_post_meta($user_id, 'mylog_person_goals', true);
    
    // Fallback: check user_meta (old records saved before this fix)
    if (empty($person_goals)) {
        $current_user_id = get_current_user_id();
        $supported_users = get_user_meta($current_user_id, 'mylog_supported_users', true);
        if (!empty($supported_users[$user_id]['person_goals'])) {
            $person_goals = $supported_users[$user_id]['person_goals'];
            // Migrate it to post_meta so it's found next time
            update_post_meta($user_id, 'mylog_person_goals', $person_goals);
        }
    }
    
    $user_data = array(
        'user_id' => $user_id,
        'full_name' => $post->post_title,
        'preferred_name' => get_post_meta($user_id, 'mylog_preferred_name', true),
        'profile_photo_id' => get_post_meta($user_id, 'mylog_profile_photo_id', true),
        'person_goals' => $person_goals,
        'happy_when' => get_post_meta($user_id, 'mylog_happy_when', true),
        'unhappy_when' => get_post_meta($user_id, 'mylog_unhappy_when', true),
        'comm_method' => get_post_meta($user_id, 'mylog_comm_method', true),
        'comm_notes' => get_post_meta($user_id, 'mylog_comm_notes', true),
        'additional_info' => get_post_meta($user_id, 'mylog_additional_info', true)
    );
    
    // Get photo URL if exists
    if (!empty($user_data['profile_photo_id'])) {
        $photo_url = wp_get_attachment_image_url($user_data['profile_photo_id'], 'thumbnail');
        if ($photo_url) {
            $user_data['profile_photo_url'] = $photo_url;
        }
    }
    
    wp_send_json_success(array('user' => $user_data));
}
add_action('wp_ajax_mylog_get_user_details', 'mylog_ajax_get_user_details');

// AJAX: Update user
function mylog_ajax_update_user() {
    check_ajax_referer('mylog_user_form', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in.'));
    }
    
    $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
    $full_name = isset($_POST['full_name']) ? sanitize_text_field($_POST['full_name']) : '';
    
    if (!$user_id || empty($full_name)) {
        wp_send_json_error(array('message' => 'Invalid data.'));
    }
    
    // Get post
    $post = get_post($user_id);
    
    if (!$post || $post->post_type !== 'mylog_user') {
        wp_send_json_error(array('message' => 'User not found.'));
    }
    
    // Update post title (full name)
    wp_update_post(array(
        'ID' => $user_id,
        'post_title' => $full_name
    ));
    
    // Update all meta fields
    update_post_meta($user_id, 'mylog_full_name', $full_name);
    update_post_meta($user_id, 'mylog_preferred_name', isset($_POST['preferred_name']) ? sanitize_text_field($_POST['preferred_name']) : '');
    update_post_meta($user_id, 'mylog_profile_photo_id', isset($_POST['profile_photo']) ? absint($_POST['profile_photo']) : 0);
    update_post_meta($user_id, 'mylog_person_goals', isset($_POST['person_goals']) ? sanitize_textarea_field(mb_substr($_POST['person_goals'], 0, 1000, 'UTF-8')) : '');
    update_post_meta($user_id, 'mylog_happy_when', isset($_POST['happy_when']) ? sanitize_textarea_field($_POST['happy_when']) : '');
    update_post_meta($user_id, 'mylog_unhappy_when', isset($_POST['unhappy_when']) ? sanitize_textarea_field($_POST['unhappy_when']) : '');
    update_post_meta($user_id, 'mylog_comm_method', isset($_POST['comm_method']) ? sanitize_text_field($_POST['comm_method']) : '');
    update_post_meta($user_id, 'mylog_comm_notes', isset($_POST['comm_notes']) ? sanitize_textarea_field($_POST['comm_notes']) : '');
    update_post_meta($user_id, 'mylog_additional_info', isset($_POST['additional_info']) ? sanitize_textarea_field($_POST['additional_info']) : '');
    
    wp_send_json_success(array('message' => 'User details updated!'));
}
add_action('wp_ajax_mylog_update_user', 'mylog_ajax_update_user');

// AJAX: Remove user
function mylog_ajax_remove_user() {
    check_ajax_referer('mylog_user_form', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in.'));
    }
    
    $current_user_id = get_current_user_id();
    $user_id = isset($_POST['user_id']) ? sanitize_text_field($_POST['user_id']) : '';
    
    $supported_users = get_user_meta($current_user_id, 'mylog_supported_users', true);
    
    if (!is_array($supported_users) || !isset($supported_users[$user_id])) {
        wp_send_json_error(array('message' => 'User not found.'));
    }
    
    unset($supported_users[$user_id]);
    update_user_meta($current_user_id, 'mylog_supported_users', $supported_users);
    
    wp_send_json_success(array('message' => 'Person removed successfully.'));
}
add_action('wp_ajax_mylog_remove_user', 'mylog_ajax_remove_user');

// AJAX: Upload photo
function mylog_ajax_upload_photo() {
    check_ajax_referer('mylog_user_form', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in.'));
    }
    
    if (empty($_FILES['file'])) {
        wp_send_json_error(array('message' => 'No file uploaded.'));
    }
    
    // Handle file upload
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    $file = $_FILES['file'];
    
    // Upload file
    $upload = wp_handle_upload($file, array('test_form' => false));
    
    if (isset($upload['error'])) {
        wp_send_json_error(array('message' => $upload['error']));
    }
    
    // Create attachment
    $attachment = array(
        'post_mime_type' => $upload['type'],
        'post_title' => sanitize_file_name($file['name']),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    
    $attachment_id = wp_insert_attachment($attachment, $upload['file']);
    
    if (is_wp_error($attachment_id)) {
        wp_send_json_error(array('message' => 'Failed to create attachment.'));
    }
    
    // Generate attachment metadata
    $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
    wp_update_attachment_metadata($attachment_id, $attachment_data);
    
    wp_send_json_success(array(
        'attachment_id' => $attachment_id,
        'url' => $upload['url']
    ));
}
add_action('wp_ajax_mylog_upload_photo', 'mylog_ajax_upload_photo');