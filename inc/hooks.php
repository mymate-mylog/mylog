<?php
/**
 * Hooks: Security, Access Control & Form Processing
 */

add_action('template_redirect', function() {
    if (is_admin()) return;

    // ── 1. MyLog Page Access Control ─────────────────────────────────────────
    if (is_page('mylog') ||
        strpos($_SERVER['REQUEST_URI'], '/mylog/mylog') !== false ||
        strpos($_SERVER['REQUEST_URI'], 'entry_saved=1') !== false) {

        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }

        $user           = wp_get_current_user();
        $is_admin       = current_user_can('administrator');
        $is_family_admin = in_array('family_admin', (array)$user->roles);
        $is_caregiver   = in_array('caregiver', (array)$user->roles);

        if (!$is_admin && !$is_family_admin && !$is_caregiver) {
            wp_redirect(home_url('/pricing/'));
            exit;
        }
        return;
    }

    // ── 2. Password Change ────────────────────────────────────────────────────
    if (isset($_POST['mylog_change_password_submit']) &&
        wp_verify_nonce($_POST['_wpnonce_password'], 'mylog_change_password')) {

        $user = wp_get_current_user();

        if (!wp_check_password($_POST['current_password'], $user->data->user_pass, $user->ID)) {
            wp_redirect(add_query_arg('password_error', 'incorrect', home_url('/caregiver-dashboard/')));
            exit;
        }

        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            wp_redirect(add_query_arg('password_error', 'mismatch', home_url('/caregiver-dashboard/')));
            exit;
        }

        if (strlen($_POST['new_password']) < 8) {
            wp_redirect(add_query_arg('password_error', 'weak', home_url('/caregiver-dashboard/')));
            exit;
        }

        wp_set_password($_POST['new_password'], $user->ID);
        wp_set_auth_cookie($user->ID);
        delete_user_meta($user->ID, 'mylog_temp_password');
        update_user_meta($user->ID, 'mylog_password_changed', '1');
        wp_redirect(add_query_arg('password_changed', '1', home_url('/caregiver-dashboard/')));
        exit;
    }

    // ── 3. Remove User (nonce-protected) ──────────────────────────────────────
    if (isset($_GET['remove_user']) && isset($_GET['_wpnonce'])) {
        $id = intval($_GET['remove_user']);
        if (wp_verify_nonce($_GET['_wpnonce'], 'mylog_remove_user_' . $id) &&
            mylog_user_is_accessible($id)) {
            wp_trash_post($id);
            wp_redirect(remove_query_arg(['remove_user', '_wpnonce']));
            exit;
        }
        wp_die('Security check failed.');
    }

    // ── 4. Remove Caregiver (nonce-protected) ────────────────────────────────
    if (isset($_GET['remove_caregiver']) && isset($_GET['_wpnonce'])) {
        $caregiver_id = intval($_GET['remove_caregiver']);

        if (!wp_verify_nonce($_GET['_wpnonce'], 'mylog_remove_caregiver_' . $caregiver_id)) {
            wp_die('Security check failed.');
        }

        $can_remove = false;
        if (current_user_can('administrator')) {
            $can_remove = true;
        } else {
            $allowed_users = get_user_meta($caregiver_id, 'mylog_allowed_users', true);
            foreach ((array)$allowed_users as $uid) {
                if (get_post_meta($uid, 'mylog_family_admin', true) == get_current_user_id()) {
                    $can_remove = true;
                    break;
                }
            }
        }

        if ($can_remove) {
            $user = new WP_User($caregiver_id);
            $user->remove_role('caregiver');
            delete_user_meta($caregiver_id, 'mylog_allowed_users');
            wp_redirect(add_query_arg('caregiver_removed', '1',
                remove_query_arg(['remove_caregiver', '_wpnonce'])));
            exit;
        }
    }

    // ── 5. Add User Form ──────────────────────────────────────────────────────
    if (isset($_POST['mylog_add_user_submit']) &&
        wp_verify_nonce($_POST['_wpnonce_add_user'], 'mylog_add_user')) {

        if (!mylog_check_user_limit()) {
            wp_redirect(add_query_arg(['limit_reached' => '1'], home_url('/pricing/')));
            exit;
        }

        $new_user_id = wp_insert_post([
            'post_type'   => 'mylog_user',
            'post_status' => 'publish',
            'post_title'  => sanitize_text_field($_POST['mylog_new_user_name']),
        ]);

        if ($new_user_id && !is_wp_error($new_user_id)) {
            update_post_meta($new_user_id, 'mylog_user_dob',   sanitize_text_field($_POST['mylog_user_dob']));
            update_post_meta($new_user_id, 'mylog_user_notes', sanitize_textarea_field($_POST['mylog_user_notes']));
            if (!current_user_can('administrator')) {
                update_post_meta($new_user_id, 'mylog_family_admin', get_current_user_id());
            }
            wp_redirect(add_query_arg('user_added', '1', home_url('/add-user/')));
            exit;
        }
    }

    // ── 6. Invite Caregiver ───────────────────────────────────────────────────
    if (isset($_POST['mylog_invite_submit']) &&
        wp_verify_nonce($_POST['_wpnonce_invite'], 'mylog_invite_caregiver')) {

        $caregiver_email = sanitize_email($_POST['caregiver_email']);
        $caregiver_name  = sanitize_text_field($_POST['caregiver_name']);
        $allowed_users   = isset($_POST['allowed_users']) ? array_map('intval', $_POST['allowed_users']) : [];
        $message         = sanitize_textarea_field($_POST['invitation_message']);

        if (email_exists($caregiver_email)) {
            $user = get_user_by('email', $caregiver_email);
            $user->add_role('caregiver');
            update_user_meta($user->ID, 'mylog_allowed_users', $allowed_users);
            wp_redirect(add_query_arg('invite_status', 'existing', home_url('/invite-caregiver/')));
            exit;
        }

        $random_password = wp_generate_password(12, true);
        $user_id         = wp_create_user($caregiver_email, $random_password, $caregiver_email);

        if (!is_wp_error($user_id)) {
            wp_update_user([
                'ID'           => $user_id,
                'display_name' => $caregiver_name,
                'first_name'   => $caregiver_name,
            ]);

            $new_user = new WP_User($user_id);
            $new_user->set_role('caregiver');
            update_user_meta($user_id, 'mylog_allowed_users', $allowed_users);
            update_user_meta($user_id, 'mylog_temp_password',  $random_password);

            $login_url = wp_login_url();
            $subject   = "You've been invited to MyLog | Kua pōwhiritia koe ki MyLog";
            $body      = "Kia ora {$caregiver_name},<br><br>";
            $body     .= "You've been invited to join MyLog as a caregiver.<br><br>";

            if (!empty($message)) {
                $body .= "<strong>Personal message from your family admin:</strong><br>";
                $body .= "<em>" . nl2br($message) . "</em><br><br>";
            }

            $body .= "<strong>Your login details:</strong><br>";
            $body .= "Login here: <a href='{$login_url}'>{$login_url}</a><br>";
            $body .= "Email: {$caregiver_email}<br>";
            $body .= "Temporary Password: <strong>{$random_password}</strong><br><br>";
            $body .= "Please change your password after logging in.<br><br>";
            $body .= "Ngā mihi,<br>The MyLog Team";

            $mail_sent = wp_mail($caregiver_email, $subject, $body,
                ['Content-Type: text/html; charset=UTF-8']);

            if ($mail_sent) {
                wp_redirect(add_query_arg('invite_status', 'success', home_url('/invite-caregiver/')));
            } else {
                wp_redirect(add_query_arg(
                    ['invite_status' => 'success', 'email_failed' => '1',
                     'temp_pass'     => urlencode($random_password)],
                    home_url('/invite-caregiver/')
                ));
            }
            exit;
        }
    }
});


// ── MENU VISIBILITY ───────────────────────────────────────────────────────────

add_filter('wp_nav_menu_objects', function($items, $args) {
    if (is_admin()) return $items;

    $is_logged_in    = is_user_logged_in();
    $roles           = $is_logged_in ? (array)wp_get_current_user()->roles : [];
    $is_admin        = current_user_can('administrator');
    $is_family_admin = in_array('family_admin', $roles);
    $is_caregiver    = in_array('caregiver', $roles);

    foreach ($items as $key => $item) {
        $classes     = (array)$item->classes;
        $should_hide = false;

        if (in_array('menu-mylog', $classes) || in_array('menu-add-entry', $classes)) {
            if (!$is_logged_in || (!$is_admin && !$is_family_admin && !$is_caregiver)) {
                $should_hide = true;
            }
        }

        if (in_array('menu-invite-caregiver', $classes) || in_array('menu-add-user', $classes)) {
            $should_hide = true;
        }

        if (in_array('menu-manage-users', $classes) ||
            in_array('menu-account', $classes) ||
            in_array('menu-dashboard', $classes)) {
            if (!$is_logged_in || (!$is_admin && !$is_family_admin)) $should_hide = true;
        }

        if (in_array('menu-caregiver-dashboard', $classes)) {
            if (!$is_logged_in || !$is_caregiver) $should_hide = true;
        }

        if (in_array('menu-pricing', $classes) ||
            in_array('menu-login', $classes) ||
            in_array('menu-register', $classes)) {
            if ($is_logged_in) $should_hide = true;
        }

        if (in_array('menu-logout', $classes)) {
            if (!$is_logged_in) $should_hide = true;
        }

        if ($should_hide) unset($items[$key]);
    }
    return $items;
}, 10, 2);


// ── ADMIN PAGES ───────────────────────────────────────────────────────────────

add_action('admin_menu', 'mylog_custom_user_pages', 999);

function mylog_custom_user_pages() {
    add_menu_page(
        'MyLog Subscribers', 'MyLog Subscribers', 'manage_options',
        'mylog-subscribers-list', 'mylog_subscribers_page_content',
        'dashicons-groups', 70
    );
    add_menu_page(
        'Caregivers', 'Caregivers', 'manage_options',
        'mylog-caregivers-list', 'mylog_caregivers_page_content',
        'dashicons-admin-users', 71
    );
}

function mylog_subscribers_page_content() {
    if (!current_user_can('manage_options')) wp_die('Access denied.');

    echo '<div class="wrap"><h1>MyLog Subscribers</h1>';
    $subscribers = get_users(['role' => 'family_admin', 'orderby' => 'registered', 'order' => 'DESC']);

    if (empty($subscribers)) {
        echo '<p>No subscribers found.</p>';
    } else {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Name</th><th>Email</th><th>Registered</th><th>Actions</th></tr></thead><tbody>';
        foreach ($subscribers as $user) {
            echo '<tr>';
            echo '<td>' . esc_html($user->display_name) . '</td>';
            echo '<td>' . esc_html($user->user_email) . '</td>';
            echo '<td>' . esc_html(wp_date('Y-m-d', strtotime($user->user_registered))) . '</td>';
            echo '<td><a href="' . esc_url(admin_url('user-edit.php?user_id=' . $user->ID)) . '">Edit</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
    echo '</div>';
}

function mylog_caregivers_page_content() {
    if (!current_user_can('manage_options')) wp_die('Access denied.');

    echo '<div class="wrap"><h1>Caregivers</h1>';
    $caregivers = get_users(['role' => 'caregiver', 'orderby' => 'registered', 'order' => 'DESC']);

    if (empty($caregivers)) {
        echo '<p>No caregivers found.</p>';
    } else {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Name</th><th>Email</th><th>Registered</th><th>Actions</th></tr></thead><tbody>';
        foreach ($caregivers as $user) {
            echo '<tr>';
            echo '<td>' . esc_html($user->display_name) . '</td>';
            echo '<td>' . esc_html($user->user_email) . '</td>';
            echo '<td>' . esc_html(wp_date('Y-m-d', strtotime($user->user_registered))) . '</td>';
            echo '<td><a href="' . esc_url(admin_url('user-edit.php?user_id=' . $user->ID)) . '">Edit</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
    echo '</div>';
}

add_action('admin_menu', 'mylog_hide_users_menu_for_non_admins', 999);
function mylog_hide_users_menu_for_non_admins() {
    if (!current_user_can('administrator')) {
        remove_menu_page('users.php');
    }
}

add_action('pre_get_users', 'mylog_filter_users_page_to_admins_only');
function mylog_filter_users_page_to_admins_only($query) {
    if (!is_admin() || !function_exists('get_current_screen')) return;
    $screen = get_current_screen();
    if ($screen && $screen->id === 'users') {
        $query->set('role', 'administrator');
    }
}


// ── PRICING PAGE — UPGRADE NOTIFICATION ──────────────────────────────────────

add_action('wp_footer', function() {
    if (!is_page('pricing')) return;
    if (!isset($_GET['limit_reached']) || $_GET['limit_reached'] !== '1') return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('html, body').animate({ scrollTop: $('.mylog-pricing-container').offset().top - 100 }, 1000);
        $('.mylog-plan.featured').css({
            'animation': 'pulse 1s ease-in-out 3',
            'box-shadow': '0 0 0 3px rgba(239,68,68,0.5)'
        });
    });
    </script>
    <div style="position:fixed;top:20px;left:50%;transform:translateX(-50%);background:#fee2e2;border:2px solid #ef4444;padding:15px 30px;border-radius:12px;z-index:9999;box-shadow:0 4px 15px rgba(0,0,0,0.2);">
        <p style="margin:0;color:#991b1b;font-weight:700;font-size:16px;">
            ⚠️ You've reached your plan limit. Upgrade below to add more people! ⬇️
        </p>
    </div>
    <?php
});