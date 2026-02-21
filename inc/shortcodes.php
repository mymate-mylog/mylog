<?php
/**
 * Shortcodes: Main UI
 * Updated: Feb 17, 2026 - V4.1
 * Note: mylog_add_user_form moved to /inc/add-user-form-enhanced.php
 */

// ═══════════════════════════════════════════════════════════════════
// MANAGE USERS SHORTCODE
// ═══════════════════════════════════════════════════════════════════
add_shortcode('mylog_manage_users', function() {
    $users = mylog_get_accessible_users();
    
    // Get caregivers list
    $current_user_id = get_current_user_id();
    $is_admin = current_user_can('administrator');
    $caregivers = [];
    
    if ($is_admin) {
        // Admin sees all caregivers
        $caregivers = get_users(['role' => 'caregiver']);
    } else {
        // Family admin sees caregivers they've invited
        $all_caregivers = get_users(['role' => 'caregiver']);
        foreach ($all_caregivers as $caregiver) {
            $allowed_users = get_user_meta($caregiver->ID, 'mylog_allowed_users', true);
            if (!empty($allowed_users)) {
                // Check if any of the allowed users belong to this family admin
                foreach ((array)$allowed_users as $user_id) {
                    $family_admin_id = get_post_meta($user_id, 'mylog_family_admin', true);
                    if ($family_admin_id == $current_user_id) {
                        $caregivers[] = $caregiver;
                        break;
                    }
                }
            }
        }
    }
    
    ob_start(); ?>
    <div style="margin-bottom: 20px;">
        <a href="<?php echo home_url('/dashboard/'); ?>" class="button" style="text-decoration: none;">
            ⬅️ Back to Dashboard | Hoki ki te Papatono
        </a>
    </div>
    
    <div class="mylog-form-wrap" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%) !important; padding: 24px 20px !important; border-radius: 16px;">
    
    <div class="mylog-manage-section">
        <h3>People Supported | Ngā Tāngata</h3>
        <?php if (!empty($users)): ?>
            <table class="widefat striped">
                <thead><tr><th>Name</th><th>Date Added</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach($users as $u): ?>
                    <tr>
                        <td><?php echo esc_html($u->post_title); ?></td>
                        <td><?php echo get_the_date('d/m/Y', $u); ?></td>
                        <td style="white-space: nowrap;">
                            <button type="button" class="mylog-edit-user-btn" data-user-id="<?php echo $u->ID; ?>" style="background:#3b82f6; color:white; border:none; padding:6px 12px; border-radius:4px; font-size:13px; cursor:pointer; margin-right:6px;">Edit</button>
                            <a href="<?php echo esc_url(add_query_arg(['remove_user' => $u->ID, '_wpnonce' => wp_create_nonce('mylog_remove_user_' . $u->ID)])); ?>" onclick="return confirm('Confirm removal of this person and all their entries?')" style="background:#dc3545; color:white; border:none; padding:6px 12px; border-radius:4px; font-size:13px; text-decoration:none; display:inline-block;">Remove</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No people added yet. <a href="<?php echo home_url('/add-user/'); ?>">Add your first person</a></p>
        <?php endif; ?>
    </div>
    
    <div class="mylog-manage-section" style="margin-top: 40px;">
        <h3>Current Caregivers | Ngā Kaitiaki</h3>
        <?php if (!empty($caregivers)): ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Access To</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($caregivers as $caregiver): 
                        $allowed_user_ids = get_user_meta($caregiver->ID, 'mylog_allowed_users', true);
                        $allowed_names = [];
                        if (!empty($allowed_user_ids)) {
                            foreach ((array)$allowed_user_ids as $uid) {
                                $user_post = get_post($uid);
                                if ($user_post) {
                                    $allowed_names[] = $user_post->post_title;
                                }
                            }
                        }
                    ?>
                    <tr>
                        <td><?php echo esc_html($caregiver->display_name); ?></td>
                        <td><?php echo esc_html($caregiver->user_email); ?></td>
                        <td><?php echo !empty($allowed_names) ? implode(', ', $allowed_names) : 'None'; ?></td>
                        <td>
                            <a href="?edit_caregiver=<?php echo $caregiver->ID; ?>" class="button-small">Edit</a>
                            <a href="<?php echo esc_url(add_query_arg(['remove_caregiver' => $caregiver->ID, '_wpnonce' => wp_create_nonce('mylog_remove_caregiver_' . $caregiver->ID)])); ?>" class="remove-link" onclick="return confirm('Remove this caregiver\'s access?')">Remove</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No caregivers invited yet. <a href="<?php echo home_url('/invite-caregiver/'); ?>">Invite a caregiver</a></p>
        <?php endif; ?>
    </div>
    
    <!-- Edit User Modal -->
    <div id="mylog-edit-user-modal" class="mylog-modal" style="display:none;">
        <div class="mylog-modal-overlay"></div>
        <div class="mylog-modal-content" style="max-width: 600px; margin: 50px auto; background: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); position: relative; z-index: 1000;">
            <div class="mylog-modal-header" style="padding: 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0; font-size: 1.5rem;">Edit Person</h2>
                <button type="button" class="mylog-modal-close" style="background: none; border: none; font-size: 28px; cursor: pointer; color: #6b7280;">&times;</button>
            </div>
            <div class="mylog-modal-body" style="padding: 20px; max-height: 70vh; overflow-y: auto;">
                <form id="mylog-edit-user-form" class="mylog-form mylog-form--lean">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    
                    <div class="mylog-form-group">
                        <label for="edit_full_name" class="mylog-label mylog-label--required">Full Name (Legal/Official)</label>
                        <input type="text" id="edit_full_name" name="full_name" class="mylog-input" required>
                    </div>
                    
                    <div class="mylog-form-group">
                        <label for="edit_preferred_name" class="mylog-label">Nickname / Preferred Name</label>
                        <input type="text" id="edit_preferred_name" name="preferred_name" class="mylog-input">
                    </div>
                    
                    <div class="mylog-form-group mylog-form-group--photo">
                        <label class="mylog-label">Profile Photo</label>
                        <div class="mylog-photo-upload">
                            <div class="mylog-photo-preview" id="edit-photo-preview">
                                <span class="mylog-photo-placeholder">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                </span>
                            </div>
                            <div class="mylog-photo-actions">
                                <button type="button" class="mylog-btn mylog-btn--secondary" id="edit-upload-photo-btn">Upload Photo</button>
                                <button type="button" class="mylog-btn mylog-btn--text" id="edit-remove-photo-btn" style="display:none;">Remove</button>
                            </div>
                            <input type="hidden" id="edit_profile_photo" name="profile_photo">
                        </div>
                    </div>
                    
                    <div class="mylog-form-group">
                        <label for="edit_person_goals" class="mylog-label">My Current Goals &amp; Aspirations</label>
                        <textarea id="edit_person_goals" name="person_goals" class="mylog-textarea" rows="4" maxlength="1000"
                                  placeholder="E.g., 'I want to shower independently', 'Join community activities twice a week'"></textarea>
                        <span class="mylog-help-text">What matters most to you? What would you like to achieve?</span>
                        <span style="font-size:0.75rem;color:#6b7280;display:block;text-align:right;margin-top:2px;" id="edit_person_goals_counter">0 / 1000 characters</span>
                    </div>
                    
                    <div class="mylog-form-group">
                        <label for="edit_happy_when" class="mylog-label">
                            <span class="mylog-traffic-light mylog-traffic-light--green">●</span>
                            I am happy if
                        </label>
                        <textarea id="edit_happy_when" name="happy_when" class="mylog-textarea" rows="3"></textarea>
                    </div>
                    
                    <div class="mylog-form-group">
                        <label for="edit_unhappy_when" class="mylog-label">
                            <span class="mylog-traffic-light mylog-traffic-light--red">●</span>
                            I am not happy if
                        </label>
                        <textarea id="edit_unhappy_when" name="unhappy_when" class="mylog-textarea" rows="3"></textarea>
                    </div>
                    
                    <div class="mylog-form-group">
                        <label for="edit_comm_method" class="mylog-label">Communication Preferences</label>
                        <select id="edit_comm_method" name="comm_method" class="mylog-select">
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
                        <label for="edit_comm_notes" class="mylog-label">Additional Communication Notes</label>
                        <textarea id="edit_comm_notes" name="comm_notes" class="mylog-textarea" rows="2"></textarea>
                    </div>
                    
                    <div class="mylog-form-group">
                        <label for="edit_additional_info" class="mylog-label">Other Important/Emergency Information</label>
                        <textarea id="edit_additional_info" name="additional_info" class="mylog-textarea" rows="3"></textarea>
                    </div>
                    
                    <div class="mylog-form-actions" style="margin-top: 20px; display: flex; flex-direction: column; gap: 10px;">
                        <button type="submit" class="mylog-btn mylog-btn--primary" style="width: 100%; background:#3b82f6; color:white; border:none; padding:14px 20px; border-radius:8px; font-size:15px; font-weight:600; cursor:pointer;">
                            <span class="mylog-btn-text">Save Changes</span>
                            <span class="mylog-btn-loading" style="display:none;">Saving...</span>
                        </button>
                        <button type="button" class="mylog-modal-close" style="width: 100%; background:#f3f4f6; color:#374151; border:1px solid #d1d5db; padding:14px 20px; border-radius:8px; font-size:15px; font-weight:600; cursor:pointer;">Cancel</button>
                    </div>
                    
                    <div id="edit-mylog-form-response" class="mylog-form-response" style="display:none; margin-top: 15px;"></div>
                </form>
            </div>
        </div>
    </div>
    
    <style>
    .mylog-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 999;
    }
    .mylog-modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
    }
    .mylog-form-response.success {
        background: #d1fae5;
        border-left: 4px solid #10b981;
        padding: 12px 16px;
        border-radius: 6px;
        color: #065f46;
    }
    .mylog-form-response.error {
        background: #fee2e2;
        border-left: 4px solid #ef4444;
        padding: 12px 16px;
        border-radius: 6px;
        color: #991b1b;
    }
    </style>
    <script>
    // Goals character counter for edit modal
    (function() {
        var ta = document.getElementById('edit_person_goals');
        var counter = document.getElementById('edit_person_goals_counter');
        if (ta && counter) {
            ta.addEventListener('input', function() {
                counter.textContent = this.value.length + ' / 1000 characters';
            });
        }
        // Re-init when modal opens (value may be set by JS after DOM ready)
        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('mylog-edit-user-btn')) {
                setTimeout(function() {
                    var ta2 = document.getElementById('edit_person_goals');
                    var c2  = document.getElementById('edit_person_goals_counter');
                    if (ta2 && c2) c2.textContent = ta2.value.length + ' / 1000 characters';
                }, 400);
            }
        });
    })();
    </script>
    
    </div><?php return ob_get_clean();
});

// ═══════════════════════════════════════════════════════════════════
// USER SELECTOR SHORTCODE
// ═══════════════════════════════════════════════════════════════════
add_shortcode('mylog_user_selector', function () {
    if (!is_user_logged_in()) return '';
    $users = mylog_get_accessible_users();
    if (empty($users)) return '';
    $preselected = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    ob_start(); ?>
    <select name="mylog_user_id" required>
        <option value="">-- Select Person | Kōwhiria te Tangata --</option>
        <?php foreach ($users as $u): ?>
            <option value="<?php echo $u->ID; ?>" <?php selected($preselected, $u->ID); ?>><?php echo esc_html($u->post_title); ?></option>
        <?php endforeach; ?>
    </select>
    <?php return ob_get_clean();
});

// ═══════════════════════════════════════════════════════════════════
// ADD ENTRY FORM SHORTCODE (V4.1)
// ═══════════════════════════════════════════════════════════════════
add_shortcode('mylog_add_entry_form', function() {
    if (!is_user_logged_in()) {
        return '<p>Please log in to continue | Tēnā koa takiuru.</p>';
    }
    
    // Call the V4.1 form function
    return mylog_render_hybrid_entry_form();
});

// ═══════════════════════════════════════════════════════════════════
// INVITE CAREGIVER FORM SHORTCODE
// ═══════════════════════════════════════════════════════════════════
add_shortcode('mylog_invite_caregiver_form', function() {
    // Check permissions
    if (!is_user_logged_in() || (!current_user_can('administrator') && !in_array('family_admin', wp_get_current_user()->roles))) {
        return '<p>You do not have permission to invite caregivers.</p>';
    }
    
    $users = mylog_get_accessible_users();
    
    ob_start(); 
    ?>
    <div style="margin-bottom: 20px;">
        <a href="<?php echo home_url('/dashboard/'); ?>" class="button" style="text-decoration: none;">
            ⬅️ Back to Dashboard | Hoki ki te Papatono
        </a>
    </div>
    
    <?php
    // Show success/error messages
    if (isset($_GET['invite_status'])):
        if ($_GET['invite_status'] == 'success'): 
            $email_failed = isset($_GET['email_failed']) && $_GET['email_failed'] == '1';
            $temp_password = isset($_GET['temp_pass']) ? urldecode($_GET['temp_pass']) : '';
            ?>
            <div class="success-message" style="background:#d1fae5; border-left:5px solid #10b981; padding:15px 20px; margin-bottom:20px; border-radius:8px;">
                <p style="margin:0; color:#065f46; font-weight:bold;">✅ Caregiver account created successfully! | Kua hangaia te pūkete kaitiaki!</p>
                <?php if ($email_failed && $temp_password): ?>
                    <div style="background:#fff3cd; padding:15px; margin:15px 0; border-radius:5px; border:1px solid #ffc107;">
                        <p style="margin:0 0 10px 0; color:#856404; font-weight:bold;">⚠️ Email may not have been delivered due to server settings.</p>
                        <p style="margin:0; color:#856404;">Please share these login details with the caregiver manually:</p>
                        <div style="background:#fff; padding:10px; margin:10px 0; border-radius:3px; font-family:monospace;">
                            <p style="margin:5px 0;"><strong>Email:</strong> (as entered above)</p>
                            <p style="margin:5px 0;"><strong>Password:</strong> <code style="background:#f4f4f4; padding:3px 6px; border-radius:3px; font-size:14px;"><?php echo esc_html($temp_password); ?></code></p>
                            <p style="margin:5px 0;"><strong>Login:</strong> <a href="<?php echo wp_login_url(); ?>"><?php echo wp_login_url(); ?></a></p>
                        </div>
                    </div>
                <?php else: ?>
                    <p style="margin:5px 0 0 0; color:#065f46;">✅ The caregiver has been sent an email with their login details.</p>
                <?php endif; ?>
            </div>
        <?php elseif ($_GET['invite_status'] == 'existing'): ?>
            <div class="success-message" style="background:#dbeafe; border-left:5px solid #3b82f6; padding:15px 20px; margin-bottom:20px; border-radius:8px;">
                <p style="margin:0; color:#1e3a8a; font-weight:bold;">ℹ️ Caregiver access updated!</p>
                <p style="margin:5px 0 0 0; color:#1e3a8a;">This user already had an account. Their access has been updated.</p>
            </div>
        <?php endif;
    endif;
    ?>
    <div class="mylog-form-wrap">
        <h3>Invite a Caregiver | Pōwhiri Kaitiaki</h3>
        <form method="post">
            <label>Caregiver Email</label>
            <input type="email" name="caregiver_email" required placeholder="caregiver@example.com">
            
            <label>Caregiver Name</label>
            <input type="text" name="caregiver_name" required placeholder="Full name">
            
            <label>Grant Access To</label>
            <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; max-height: 200px; overflow-y: auto;">
                <?php if (!empty($users)): ?>
                    <?php foreach($users as $user): ?>
                        <label style="display: block; margin-bottom: 8px;">
                            <input type="checkbox" name="allowed_users[]" value="<?php echo $user->ID; ?>">
                            <?php echo esc_html($user->post_title); ?>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No users available. Please add people first.</p>
                <?php endif; ?>
            </div>
            
            <label>Personal Message (optional)</label>
            <textarea name="invitation_message" rows="4" placeholder="Add a personal message to the invitation email..."></textarea>
            
            <?php wp_nonce_field('mylog_invite_caregiver', '_wpnonce_invite'); ?>
            <input type="submit" name="mylog_invite_submit" value="Send Invitation | Tukuna te Pōwhiri" class="button button-primary">
        </form>
    </div>
    <?php return ob_get_clean();
});

// ═══════════════════════════════════════════════════════════════════
// CAREGIVER DASHBOARD SHORTCODE
// ═══════════════════════════════════════════════════════════════════
add_shortcode('mylog_caregiver_dashboard', function() {
    // Check if user is logged in and is a caregiver
    if (!is_user_logged_in()) {
        return '<p>Please log in to view your dashboard.</p>';
    }
    
    $user = wp_get_current_user();
    $is_caregiver = in_array('caregiver', (array)$user->roles);
    
    if (!$is_caregiver && !current_user_can('administrator')) {
        return '<p>This dashboard is only available to caregivers.</p>';
    }
    
    $accessible_users = mylog_get_accessible_users();
    
    // Check if caregiver still has temp password
    $has_temp_password = get_user_meta($user->ID, 'mylog_temp_password', true);
    $password_already_changed = get_user_meta($user->ID, 'mylog_password_changed', true);
    
    ob_start(); ?>
    <div class="mylog-caregiver-dashboard">
        <?php 
        // Password change success message
        if (isset($_GET['password_changed']) && $_GET['password_changed'] == '1'): ?>
            <div class="success-message" style="background:#d1fae5; border-left:5px solid #10b981; padding:15px 20px; margin-bottom:20px; border-radius:8px;">
                <p style="margin:0; color:#065f46; font-weight:bold;">✅ Password changed successfully! | Kua huria te kupuhipa!</p>
            </div>
        <?php endif; ?>
        
        <?php 
        // Password change error messages
        if (isset($_GET['password_error'])): 
            $error = $_GET['password_error'];
            $error_msg = '';
            if ($error == 'incorrect') $error_msg = 'Current password is incorrect. | He hē te kupuhipa o nāianei.';
            if ($error == 'mismatch') $error_msg = 'New passwords do not match. | Kāore e ōrite ngā kupuhipa hou.';
            if ($error == 'weak') $error_msg = 'Password must be at least 8 characters. | Me 8 pūāhua te kupuhipa.';
        ?>
            <div class="error-message" style="background:#fee2e2; border-left:5px solid #ef4444; padding:15px 20px; margin-bottom:20px; border-radius:8px;">
                <p style="margin:0; color:#991b1b; font-weight:bold;">⚠️ <?php echo $error_msg; ?></p>
            </div>
        <?php endif; ?>
        
        <h2>Kia ora, <?php echo esc_html($user->display_name); ?></h2>
        
        <?php 
        // Only show password change section if they still have temp password AND haven't changed it yet
        if ($has_temp_password && !$password_already_changed): 
        ?>
            <div class="password-change-section" style="background:#fff3cd; border-left:4px solid #ffc107; padding:20px; border-radius:8px; margin-bottom:30px;">
                <h3 style="margin-top:0;">🔒 Change Your Password | Huria tō Kupuhipa</h3>
                <p style="margin:0 0 15px 0; color:#856404;">⚠️ You're using a temporary password. Please change it now for security.</p>
                <form method="post" style="max-width:500px;">
                    <div style="margin-bottom:15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Current Password | Kupuhipa o nāianei</label>
                        <input type="password" name="current_password" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                    </div>
                    <div style="margin-bottom:15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">New Password | Kupuhipa Hou</label>
                        <input type="password" name="new_password" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                    </div>
                    <div style="margin-bottom:15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Confirm New Password | Whakapūmautia te Kupuhipa Hou</label>
                        <input type="password" name="confirm_password" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                    </div>
                    <?php wp_nonce_field('mylog_change_password', '_wpnonce_password'); ?>
                    <input type="submit" name="mylog_change_password_submit" value="Change Password | Huri Kupuhipa" class="button button-primary">
                </form>
            </div>
        <?php endif; ?>
        
        <div class="dashboard-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
            <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; border-left: 4px solid #0073aa;">
                <h4 style="margin: 0 0 10px 0;">People You Support</h4>
                <p style="font-size: 2em; margin: 0; font-weight: bold;"><?php echo count($accessible_users); ?></p>
            </div>
            
            <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; border-left: 4px solid #16a34a;">
                <h4 style="margin: 0 0 10px 0;">Total Entries</h4>
                <p style="font-size: 2em; margin: 0; font-weight: bold;">
                    <?php 
                    $total_entries = 0;
                    foreach($accessible_users as $u) {
                        $entries = get_posts(['post_type' => 'mylog_entry', 'meta_key' => 'mylog_user_id', 'meta_value' => $u->ID, 'posts_per_page' => -1]);
                        $total_entries += count($entries);
                    }
                    echo $total_entries;
                    ?>
                </p>
            </div>
        </div>
        
        <div class="people-list" style="margin-top: 30px;">
            <h3>Your Assigned People | Ō Tāngata Tautoko</h3>
            <?php if (!empty($accessible_users)): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <?php foreach($accessible_users as $person): 
                        $entry_count = count(get_posts(['post_type' => 'mylog_entry', 'meta_key' => 'mylog_user_id', 'meta_value' => $person->ID, 'posts_per_page' => -1]));
                        $dob = get_post_meta($person->ID, 'mylog_user_dob', true);
                    ?>
                        <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                            <h4 style="margin: 0 0 10px 0;"><?php echo esc_html($person->post_title); ?></h4>
                            <?php if ($dob): ?>
                                <p style="margin: 5px 0; color: #666;">DOB: <?php echo esc_html(date('d/m/Y', strtotime($dob))); ?></p>
                            <?php endif; ?>
                            <p style="margin: 5px 0; color: #666;">Entries: <?php echo $entry_count; ?></p>
                            <a href="<?php echo home_url('/add-entry/'); ?>" class="button" style="margin-top: 10px;">Add Entry | Tāpiri Tuhinga</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>You haven't been assigned to support anyone yet. Please contact your family admin.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php return ob_get_clean();
});

// ═══════════════════════════════════════════════════════════════════
// MAIN DASHBOARD SHORTCODE (Admin & Family Admin)
// ═══════════════════════════════════════════════════════════════════
add_shortcode('mylog_dashboard', function() {
    if (!is_user_logged_in()) {
        return '<p>Please log in to view your dashboard.</p>';
    }
    
    $user = wp_get_current_user();
    $is_admin = current_user_can('administrator');
    $is_family_admin = in_array('family_admin', (array)$user->roles);
    
    if (!$is_admin && !$is_family_admin) {
        return '<p>This dashboard is only available to administrators and family admins.</p>';
    }
    
    $accessible_users = mylog_get_accessible_users();
    
    // Get caregivers count
    $caregivers_count = 0;
    if ($is_admin) {
        $caregivers_count = count(get_users(['role' => 'caregiver']));
    } else {
        $all_caregivers = get_users(['role' => 'caregiver']);
        foreach ($all_caregivers as $caregiver) {
            $allowed_users = get_user_meta($caregiver->ID, 'mylog_allowed_users', true);
            if (!empty($allowed_users)) {
                foreach ((array)$allowed_users as $user_id) {
                    $family_admin_id = get_post_meta($user_id, 'mylog_family_admin', true);
                    if ($family_admin_id == get_current_user_id()) {
                        $caregivers_count++;
                        break;
                    }
                }
            }
        }
    }
    
    // Count total entries
    $total_entries = 0;
    foreach($accessible_users as $u) {
        $entries = get_posts(['post_type' => 'mylog_entry', 'meta_key' => 'mylog_user_id', 'meta_value' => $u->ID, 'posts_per_page' => -1]);
        $total_entries += count($entries);
    }
    
    ob_start(); ?>
    <div class="mylog-main-dashboard">
        <h1 style="font-size: 28px; text-align: center; margin-bottom: 10px;">Welcome back - <?php echo esc_html($user->display_name); ?>! 👋</h1>
        <p style="color: #64748b; text-align: center; color: #0a5da1; margin-bottom: 10px;">Nau mai ki tō papatono | Here's your overview</p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 10px;">
            <div style="background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%); color: white; padding: 16px 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(59,130,246,0.3);">
                <div style="font-size: 18px; opacity: 0.9; margin-bottom: 4px;">👥 People Supported</div>
                <div style="font-size: 24px; font-weight: 800;"><?php echo count($accessible_users); ?></div>
            </div>
            
            <div style="background: linear-gradient(135deg, #34d399 0%, #10b981 100%); color: white; padding: 16px 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(16,185,129,0.3);">
                <div style="font-size: 18px; opacity: 0.9; margin-bottom: 4px;">📝 MyLog Entries</div>
                <div style="font-size: 24px; font-weight: 800;"><?php echo $total_entries; ?></div>
            </div>
            
            <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 16px 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(245,158,11,0.3);">
                <div style="font-size: 18px; opacity: 0.9; margin-bottom: 4px;">🤝 Caregivers</div>
                <div style="font-size: 24px; font-weight: 800;"><?php echo $caregivers_count; ?></div>
            </div>
        </div>
        
        <h2 style="font-size: 22px; text-align: center; margin-bottom: 10px; color: #0a5da1;">Quick Actions | Ngā Mahi Tere</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 10px;">
            
            <a href="<?php echo home_url('/mylog/'); ?>" style="text-decoration: none;">
                <div class="dashboard-action-card" style="background: #bdebff; padding: 14px; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 2px solid transparent; transition: all 0.3s ease; cursor: pointer;">
                    <div style="font-size: 30px; margin-bottom: 10px;">📖</div>
                    <h3 style="font-size: 16px; margin: 0 0 8px 0; color: #1a202c;">View MyLog</h3>
                    <p style="margin: 0; font-size: 14px; color: #06354a; line-height: 1.5;">View all entries and activity history</p>
                </div>
            </a>
            
            <a href="<?php echo home_url('/add-entry/'); ?>" style="text-decoration: none;">
                <div class="dashboard-action-card" style="background: #bdebff; padding: 14px; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 2px solid transparent; transition: all 0.3s ease; cursor: pointer;">
                    <div style="font-size: 30px; margin-bottom: 10px;">➕</div>
                    <h3 style="font-size: 16px; margin: 0 0 8px 0; color: #1a202c;">Add New Entry</h3>
                    <p style="margin: 0; font-size: 14px; color: #06354a; line-height: 1.5;">Record MyLog entry</p>
                </div>
            </a>
            
            <a href="<?php echo home_url('/add-user/'); ?>" style="text-decoration: none;">
                <div class="dashboard-action-card" style="background: #bdebff; padding: 14px; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 2px solid transparent; transition: all 0.3s ease; cursor: pointer;">
                    <div style="font-size: 30px; margin-bottom: 10px;">👤</div>
                    <h3 style="font-size: 16px; margin: 0 0 8px 0; color: #1a202c;">Add Person/User</h3>
                    <p style="margin: 0; font-size: 14px; color: #06354a; line-height: 1.5;">Add a new person/user to support</p>
                </div>
            </a>
            
            <a href="<?php echo home_url('/invite-caregiver/'); ?>" style="text-decoration: none;">
                <div class="dashboard-action-card" style="background: #bdebff; padding: 14px; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 2px solid transparent; transition: all 0.3s ease; cursor: pointer;">
                    <div style="font-size: 30px; margin-bottom: 10px;">✉️</div>
                    <h3 style="font-size: 16px; margin: 0 0 8px 0; color: #1a202c;">Invite Caregiver</h3>
                    <p style="margin: 0; font-size: 14px; color: #06354a; line-height: 1.5;">Send invitation to support worker</p>
                </div>
            </a>
            
            <a href="<?php echo home_url('/manage-users/'); ?>" style="text-decoration: none;">
                <div class="dashboard-action-card" style="background: #bdebff; padding: 14px; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 2px solid transparent; transition: all 0.3s ease; cursor: pointer;">
                    <div style="font-size: 30px; margin-bottom: 10px;">⚙️</div>
                    <h3 style="font-size: 16px; margin: 0 0 8px 0; color: #1a202c;">Manage Users</h3>
                    <p style="margin: 0; font-size: 14px; color: #06354a; line-height: 1.5;">Manage people and caregivers</p>
                </div>
            </a>

           <a href="<?php echo home_url('/account/'); ?>" style="text-decoration: none;">
                <div class="dashboard-action-card" style="background: #bdebff; padding: 14px; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 2px solid transparent; transition: all 0.3s ease; cursor: pointer;">
                    <div style="font-size: 30px; margin-bottom: 10px;">&#11088;</div>
                    <h3 style="font-size: 16px; margin: 0 0 8px 0; color: #1a202c;">Manage Your Account</h3>
                    <p style="margin: 0; font-size: 14px; color: #06354a; line-height: 1.5;">View your Account details</p>
                </div>
            </a>


            
        </div>
    </div>
    
    <style>
    .dashboard-action-card:hover {
        border-color: #3b82f6 !important;
        transform: translateY(-4px) !important;
        box-shadow: 0 8px 20px rgba(0,0,0,0.12) !important;
    }
    </style>
    <?php return ob_get_clean();
});

// ═══════════════════════════════════════════════════════════════════
// PRICING PAGE - UPGRADE NOTIFICATION
// ═══════════════════════════════════════════════════════════════════
add_action('wp_footer', function() {
    if (!is_page('pricing')) {
        return;
    }
    
    if (isset($_GET['limit_reached']) && $_GET['limit_reached'] == '1'): ?>
        <script>
        jQuery(document).ready(function($) {
            // Scroll to pricing section
            $('html, body').animate({
                scrollTop: $('.mylog-pricing-container').offset().top - 100
            }, 1000);
            
            // Add highlight to recommended plan
            $('.mylog-plan.featured').css({
                'animation': 'pulse 1s ease-in-out 3',
                'box-shadow': '0 0 0 3px rgba(239, 68, 68, 0.5)'
            });
        });
        </script>
        
        <div style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background: #fee2e2; border: 2px solid #ef4444; padding: 15px 30px; border-radius: 12px; z-index: 9999; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
            <p style="margin: 0; color: #991b1b; font-weight: 700; font-size: 16px;">
                ⚠️ You've reached your plan limit. Upgrade below to add more people! ⬇️
            </p>
        </div>
    <?php endif;
});