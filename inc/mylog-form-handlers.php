<?php
/**
 * MyLog Form Handler — Version 4.1
 * Handles unified traffic light entry submissions + Add User with extended profile
 * 
 * CHANGES IN V4.1:
 * - New fields: achievement, incidents[], incident_details
 * - Support detail: support_type_*, support_time_*, equipment_*
 * - Renamed carer fields: support_adequacy, support_window, coverage_absence
 * - New: support_notes (replaces carer_notes)
 * - Consolidated Wairua: wairua_cultural, wairua_nature, wairua_identity
 * - Backward compatible: old entries still work
 * - Updated score calculation for new field structure
 */

add_action('init', 'mylog_handle_hybrid_entry_submission');
add_action('init', 'mylog_handle_add_user_submission');

/**
 * HANDLE ADD USER FORM SUBMISSION
 * (No changes from V3.0)
 */
function mylog_handle_add_user_submission() {
    if (!isset($_POST['mylog_add_user_submit'])) return;
    
    if (!isset($_POST['_wpnonce_add_user']) || !wp_verify_nonce($_POST['_wpnonce_add_user'], 'mylog_add_user')) {
        wp_die('Security check failed.');
    }
    
    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url());
        exit;
    }
    
    if (!current_user_can('administrator') && !in_array('family_admin', wp_get_current_user()->roles)) {
        wp_die('You do not have permission to add users.');
    }
    
    $limit = mylog_get_user_limit();
    $remaining = mylog_get_remaining_slots();
    if ($remaining !== null && $remaining <= 0) {
        wp_redirect(add_query_arg('limit_reached', '1', home_url('/add-user/')));
        exit;
    }
    
    $user_name = sanitize_text_field($_POST['mylog_new_user_name']);
    if (empty($user_name)) {
        wp_die('User name is required.');
    }
    
    $post_id = wp_insert_post([
        'post_type'   => 'mylog_user',
        'post_title'  => $user_name,
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
    ]);
    
    if (!$post_id || is_wp_error($post_id)) {
        wp_die('Failed to create user.');
    }
    
    // Save all fields
    if (!empty($_POST['mylog_user_dob'])) {
        update_post_meta($post_id, 'mylog_user_dob', sanitize_text_field($_POST['mylog_user_dob']));
    }
    if (!empty($_POST['mylog_user_notes'])) {
        update_post_meta($post_id, 'mylog_user_notes', sanitize_textarea_field($_POST['mylog_user_notes']));
    }
    if (!empty($_POST['mylog_nickname'])) {
        update_post_meta($post_id, 'mylog_nickname', sanitize_text_field($_POST['mylog_nickname']));
    }
    if (!empty($_POST['mylog_profile_photo'])) {
        update_post_meta($post_id, 'mylog_profile_photo', esc_url_raw($_POST['mylog_profile_photo']));
    }
    if (!empty($_POST['mylog_happy_when'])) {
        update_post_meta($post_id, 'mylog_happy_when', sanitize_textarea_field($_POST['mylog_happy_when']));
    }
    if (!empty($_POST['mylog_unhappy_if'])) {
        update_post_meta($post_id, 'mylog_unhappy_if', sanitize_textarea_field($_POST['mylog_unhappy_if']));
    }
    if (!empty($_POST['mylog_communication_prefs'])) {
        update_post_meta($post_id, 'mylog_communication_prefs', sanitize_text_field($_POST['mylog_communication_prefs']));
    }
    
    update_post_meta($post_id, 'mylog_family_admin', get_current_user_id());
    
    wp_redirect(add_query_arg('user_added', '1', home_url('/add-user/')));
    exit;
}

/**
 * HANDLE ENTRY FORM SUBMISSION (V4.1 ENHANCED)
 */
function mylog_handle_hybrid_entry_submission() {

    if (!isset($_POST['detailed_submit'])) return;
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'mylog_add_entry')) {
        wp_die('Security check failed.');
    }
    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url());
        exit;
    }

    $user_id = intval($_POST['mylog_user_id'] ?? 0);
    if (!$user_id || !mylog_user_is_accessible($user_id)) {
        wp_die('Invalid user access.');
    }

    // ── CREATE POST ───────────────────────────────────────────────
    $current_user = wp_get_current_user();
    $entry_id = wp_insert_post([
        'post_type'   => 'mylog_entry',
        'post_status' => 'publish',
        'post_title'  => 'Entry: ' . get_the_title($user_id) . ' — ' . date('d/m/Y H:i'),
        'post_author' => get_current_user_id(),
        'post_date'   => current_time('mysql'),
    ]);

    if (!$entry_id || is_wp_error($entry_id)) {
        wp_die('Failed to create entry. Please try again.');
    }

    // ── ENTRY METADATA ────────────────────────────────────────────
    update_post_meta($entry_id, 'mylog_user_id',     $user_id);
    update_post_meta($entry_id, 'entry_maker_id',    get_current_user_id());
    update_post_meta($entry_id, 'entry_maker_name',  $current_user->display_name);
    update_post_meta($entry_id, 'entry_date_time',   current_time('Y-m-d H:i:s'));
    update_post_meta($entry_id, 'has_detailed_data', true);
    update_post_meta($entry_id, 'form_version',      '4.1'); // NEW: Track form version

    // ── SECTION 1 — THE PERSON & THE DAY ─────────────────────────
    $s1_fields = ['overall_rating', 'support_summary'];
    foreach ($s1_fields as $field) {
        if (!empty($_POST[$field])) {
            update_post_meta($entry_id, $field, sanitize_text_field($_POST[$field]));
        }
    }

    if (!empty($_POST['carer_start_time'])) {
        update_post_meta($entry_id, 'carer_start_time', sanitize_text_field($_POST['carer_start_time']));
    }
    if (!empty($_POST['carer_end_time'])) {
        update_post_meta($entry_id, 'carer_end_time', sanitize_text_field($_POST['carer_end_time']));

        // Auto-calculate shift duration
        if (!empty($_POST['carer_start_time'])) {
            $start = strtotime($_POST['carer_start_time']);
            $end   = strtotime($_POST['carer_end_time']);
            if ($end > $start) {
                $mins = round(($end - $start) / 60);
                update_post_meta($entry_id, 'carer_duration_minutes', $mins);
            }
        }
    }

    // NEW: Achievement field
    if (!empty($_POST['achievement'])) {
        update_post_meta($entry_id, 'achievement', sanitize_text_field($_POST['achievement']));
    }

    // ── SECTION 2 — TAHA TINANA (PHYSICAL) ───────────────────────
    $tinana_fields = [
        'tinana_mealtime',
        'tinana_hygiene',
        'tinana_bathing',
        'tinana_dressing',
        'tinana_mobility',
    ];
    mylog_save_traffic_light_fields($entry_id, $tinana_fields);

    // ── SECTION 3 — TAHA HINENGARO (MIND) ────────────────────────
    $hinengaro_fields = [
        'hinengaro_memory',
        'hinengaro_focus',
        'hinengaro_comms',
        'hinengaro_problem',
        'hinengaro_household',
    ];
    mylog_save_traffic_light_fields($entry_id, $hinengaro_fields);

    // ── SECTION 4 — TAHA WHĀNAU (SOCIAL) ─────────────────────────
    $whanau_fields = [
        'whanau_family',
        'whanau_community',
        'whanau_digital',
        'whanau_hobbies',
        'whanau_group',
    ];
    mylog_save_traffic_light_fields($entry_id, $whanau_fields);

    // ── SECTION 5 — TAHA WAIRUA (SPIRITUAL) — CONSOLIDATED ───────
    $wairua_fields = [
        'wairua_cultural',  // NEW: Combined karakia/prayer/cultural
        'wairua_nature',     // NEW: Combined nature/quiet time
        'wairua_identity',   // NEW: Combined identity/belonging
    ];
    mylog_save_traffic_light_fields($entry_id, $wairua_fields);

    // BACKWARD COMPATIBILITY: Also save old wairua fields if present (for migration period)
    $old_wairua_fields = ['wairua_karakia', 'wairua_culture', 'wairua_reflection'];
    foreach ($old_wairua_fields as $field) {
        if (!empty($_POST[$field])) {
            update_post_meta($entry_id, $field, sanitize_text_field($_POST[$field]));
        }
    }

    // ── SECTION 2A — SUPPORT DETAIL (NEW) ─────────────────────────
    // Collect all support detail fields dynamically
    $all_activity_fields = array_merge($tinana_fields, $hinengaro_fields, $whanau_fields, $wairua_fields);
    
    foreach ($all_activity_fields as $activity_field) {
        // Support type
        $support_type_key = 'support_type_' . $activity_field;
        if (!empty($_POST[$support_type_key])) {
            update_post_meta($entry_id, $support_type_key, sanitize_text_field($_POST[$support_type_key]));
        }
        
        // Support time (minutes)
        $support_time_key = 'support_time_' . $activity_field;
        if (!empty($_POST[$support_time_key])) {
            update_post_meta($entry_id, $support_time_key, intval($_POST[$support_time_key]));
        }
        
        // Equipment used (array)
        $equipment_key = 'equipment_' . $activity_field;
        if (!empty($_POST[$equipment_key]) && is_array($_POST[$equipment_key])) {
            $equipment_array = array_map('sanitize_text_field', $_POST[$equipment_key]);
            update_post_meta($entry_id, $equipment_key, $equipment_array);
        }
    }

    // ── SECTION 6 — SAFETY & INCIDENTS (NEW) ──────────────────────
    if (!empty($_POST['incidents']) && is_array($_POST['incidents'])) {
        $incidents = array_map('sanitize_text_field', $_POST['incidents']);
        update_post_meta($entry_id, 'incidents', $incidents);
        
        // Flag if there were actual incidents (not just "none")
        $has_real_incidents = !in_array('none', $incidents) && count($incidents) > 0;
        update_post_meta($entry_id, 'has_incidents', $has_real_incidents);
    }
    
    if (!empty($_POST['incident_details'])) {
        update_post_meta($entry_id, 'incident_details', sanitize_textarea_field($_POST['incident_details']));
    }

    // ── SECTION 7 — NOTES & PHOTO ─────────────────────────────────
    if (!empty($_POST['quick_notes'])) {
        update_post_meta($entry_id, 'quick_notes', sanitize_textarea_field($_POST['quick_notes']));
    }
    if (!empty($_POST['extra_notes'])) {
        update_post_meta($entry_id, 'extra_notes', sanitize_textarea_field($_POST['extra_notes']));
    }

    // ── SECTION 8 — SUPPORT ENVIRONMENT (RENAMED CARER CONTEXT) ───
    $support_env_fields = [
        'support_adequacy',  // NEW: Was 'carer_battery'
        'support_window',    // RENAMED: More neutral framing
        'coverage_absence',  // KEPT: Same name
    ];
    mylog_save_traffic_light_fields($entry_id, $support_env_fields);

    // BACKWARD COMPATIBILITY: Also save as old field name for existing dashboards
    if (!empty($_POST['support_adequacy'])) {
        update_post_meta($entry_id, 'carer_battery', sanitize_text_field($_POST['support_adequacy']));
    }

    // Support environment notes (renamed from carer_notes)
    if (!empty($_POST['support_notes'])) {
        update_post_meta($entry_id, 'support_notes', sanitize_textarea_field($_POST['support_notes']));
        // BACKWARD COMPATIBILITY
        update_post_meta($entry_id, 'carer_notes', sanitize_textarea_field($_POST['support_notes']));
    }

    // ── CALCULATE STABILITY SCORES ────────────────────────────────
    $scores = mylog_calculate_entry_scores($entry_id);
    update_post_meta($entry_id, 'stability_score',     $scores['stability']);
    update_post_meta($entry_id, 'wellbeing_score',     $scores['wellbeing']);
    update_post_meta($entry_id, 'carer_score',         $scores['carer']);
    update_post_meta($entry_id, 'friction_index',      $scores['friction']);
    update_post_meta($entry_id, 'sustainability_alert',$scores['sustainability_alert']);

    // NEW: Calculate total support time from support detail
    $total_support_minutes = mylog_calculate_total_support_time($entry_id);
    update_post_meta($entry_id, 'total_support_minutes', $total_support_minutes);

    // ── PHOTO UPLOAD — up to 4 photos ────────────────────────────
    if (!empty($_FILES['photos']['name'][0])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $uploaded_ids = [];
        $field        = $_FILES['photos'];
        $max_photos   = 4;
        $max_bytes    = 10 * 1024 * 1024; // 10MB per photo

        for ($i = 0; $i < min(count($field['name']), $max_photos); $i++) {

            // Skip blank slots and failed uploads
            if (empty($field['name'][$i]) || $field['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            // Size guard
            if ($field['size'][$i] > $max_bytes) {
                continue;
            }

            // Remap to single-file shape for media_handle_upload()
            $_FILES['_mylog_photo_upload'] = [
                'name'     => $field['name'][$i],
                'type'     => $field['type'][$i],
                'tmp_name' => $field['tmp_name'][$i],
                'error'    => $field['error'][$i],
                'size'     => $field['size'][$i],
            ];

            $attachment_id = media_handle_upload('_mylog_photo_upload', $entry_id);

            if (!is_wp_error($attachment_id)) {
                $uploaded_ids[] = $attachment_id;
            }
        }

        unset($_FILES['_mylog_photo_upload']);

        if (!empty($uploaded_ids)) {
            update_post_meta($entry_id, '_mylog_photos', $uploaded_ids);
            set_post_thumbnail($entry_id, $uploaded_ids[0]);
        }
    }

    wp_redirect(add_query_arg('entry_saved', '1', home_url('/mylog/')));
    exit;
}

/**
 * Save a set of traffic light fields (green/orange/red)
 */
function mylog_save_traffic_light_fields($entry_id, $fields) {
    foreach ($fields as $field) {
        if (!empty($_POST[$field]) && in_array($_POST[$field], ['green', 'orange', 'red'])) {
            update_post_meta($entry_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}

/**
 * Calculate total support time from support detail fields (NEW)
 */
function mylog_calculate_total_support_time($entry_id) {
    $total_minutes = 0;
    
    // Get all activity fields that might have support time
    $all_fields = [
        'tinana_mealtime', 'tinana_hygiene', 'tinana_bathing', 'tinana_dressing', 'tinana_mobility',
        'hinengaro_memory', 'hinengaro_focus', 'hinengaro_comms', 'hinengaro_problem', 'hinengaro_household',
        'whanau_family', 'whanau_community', 'whanau_digital', 'whanau_hobbies', 'whanau_group',
        'wairua_cultural', 'wairua_nature', 'wairua_identity',
    ];
    
    foreach ($all_fields as $field) {
        $time_key = 'support_time_' . $field;
        $time = get_post_meta($entry_id, $time_key, true);
        if ($time && is_numeric($time)) {
            $total_minutes += intval($time);
        }
    }
    
    return $total_minutes;
}

/**
 * Calculate stability and friction scores for an entry (UPDATED FOR V4.1)
 * Green = 2, Orange = 1, Red = 0
 */
function mylog_calculate_entry_scores($entry_id) {

    // All rated fields grouped by domain (UPDATED: New wairua fields)
    $wellbeing_fields = [
        // Tinana
        'tinana_mealtime','tinana_hygiene','tinana_bathing','tinana_dressing','tinana_mobility',
        // Hinengaro
        'hinengaro_memory','hinengaro_focus','hinengaro_comms','hinengaro_problem','hinengaro_household',
        // Whānau
        'whanau_family','whanau_community','whanau_digital','whanau_hobbies','whanau_group',
        // Wairua (NEW FIELDS)
        'wairua_cultural','wairua_nature','wairua_identity',
    ];

    // Support environment fields (RENAMED)
    $support_env_fields = [
        'support_adequacy',  // Was 'carer_battery'
        'support_window',
        'coverage_absence',
    ];

    // BACKWARD COMPATIBILITY: Check for old field names if new ones don't exist
    $carer_fields = [];
    foreach ($support_env_fields as $field) {
        $carer_fields[] = $field;
    }
    
    // If old 'carer_battery' exists but not 'support_adequacy', use old field
    if (!get_post_meta($entry_id, 'support_adequacy', true) && get_post_meta($entry_id, 'carer_battery', true)) {
        $carer_fields = ['carer_battery', 'support_window', 'coverage_absence'];
    }

    $score_map = ['green' => 2, 'orange' => 1, 'red' => 0];

    // Wellbeing score
    $wb_total  = 0;
    $wb_count  = 0;
    foreach ($wellbeing_fields as $field) {
        $val = get_post_meta($entry_id, $field, true);
        if ($val && isset($score_map[$val])) {
            $wb_total += $score_map[$val];
            $wb_count++;
        }
    }
    $wellbeing_score = $wb_count > 0 ? round(($wb_total / ($wb_count * 2)) * 100) : null;

    // Carer/Support environment score
    $c_total = 0;
    $c_count = 0;
    foreach ($carer_fields as $field) {
        $val = get_post_meta($entry_id, $field, true);
        if ($val && isset($score_map[$val])) {
            $c_total += $score_map[$val];
            $c_count++;
        }
    }
    $carer_score = $c_count > 0 ? round(($c_total / ($c_count * 2)) * 100) : null;

    // Overall stability score (all fields)
    $overall_val = get_post_meta($entry_id, 'overall_rating', true);
    $support_val = get_post_meta($entry_id, 'support_summary', true);

    $all_scores = [];
    if ($overall_val && isset($score_map[$overall_val])) $all_scores[] = $score_map[$overall_val];
    if ($support_val && isset($score_map[$support_val])) $all_scores[] = $score_map[$support_val];
    foreach ($wellbeing_fields as $f) {
        $v = get_post_meta($entry_id, $f, true);
        if ($v && isset($score_map[$v])) $all_scores[] = $score_map[$v];
    }
    foreach ($carer_fields as $f) {
        $v = get_post_meta($entry_id, $f, true);
        if ($v && isset($score_map[$v])) $all_scores[] = $score_map[$v];
    }

    $stability_score = count($all_scores) > 0
        ? round((array_sum($all_scores) / (count($all_scores) * 2)) * 100)
        : null;

    // Friction index: absolute difference between wellbeing and carer scores
    $friction = null;
    if ($wellbeing_score !== null && $carer_score !== null) {
        $friction = $wellbeing_score - $carer_score;
        // High positive friction = person doing well but carer struggling
    }

    // Sustainability alert
    $sustainability_alert = false;
    if ($wellbeing_score !== null && $carer_score !== null) {
        if ($wellbeing_score >= 80 && $carer_score <= 33) {
            $sustainability_alert = true;
        }
    }

    return [
        'stability'          => $stability_score,
        'wellbeing'          => $wellbeing_score,
        'carer'              => $carer_score,
        'friction'           => $friction,
        'sustainability_alert' => $sustainability_alert,
    ];
}