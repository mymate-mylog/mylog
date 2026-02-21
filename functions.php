<?php

/**
 * MyLog — Wellbeing and support diary for disabled people and their whānau.
 * Copyright (C) 2026 | Ajit Kumar Nair | MyMate Limited
 * Licensed under GNU AGPL v3 — https://www.gnu.org/licenses/agpl-3.0.html
 */

// ── PMS RESTRICTION BYPASS ────────────────────────────────────────────────────
// Ensure logged-in family_admin and caregiver users can always access MyLog pages
// regardless of PMS content restriction settings.

add_filter('pms_restriction_check', function($is_restricted, $post_id, $user_id) {
    $post = get_post($post_id);
    if ($post && strpos($post->post_name, 'mylog') !== false) {
        return false;
    }
    return $is_restricted;
}, 999, 3);

add_filter('pms_check_restriction_by_post_id', function($is_restricted, $post_id) {
    $post = get_post($post_id);
    if ($post && strpos($post->post_name, 'mylog') !== false) {
        return false;
    }
    return $is_restricted;
}, 999, 2);

add_filter('pms_restriction_content_restriction_check', function($is_restricted, $post_id) {
    $post = get_post($post_id);
    if ($post && $post->post_name === 'mylog') {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            if (current_user_can('administrator') ||
                in_array('family_admin', $user->roles) ||
                in_array('caregiver', $user->roles)) {
                return false;
            }
        }
    }
    return $is_restricted;
}, 999, 2);

add_filter('pms_post_content_restriction_message_output', function($message, $post_id) {
    $post = get_post($post_id);
    if ($post && $post->post_name === 'mylog') {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            if (current_user_can('administrator') ||
                in_array('family_admin', $user->roles) ||
                in_array('caregiver', $user->roles)) {
                return '';
            }
        }
    }
    return $message;
}, 999, 2);

// Force shortcode execution for authorised users on the MyLog page
add_filter('the_content', function($content) {
    if (!is_page('mylog')) {
        return $content;
    }
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        if (current_user_can('administrator') ||
            in_array('family_admin', $user->roles) ||
            in_array('caregiver', $user->roles)) {
            return do_shortcode('[mylog_diary_with_filter]');
        }
    }
    return $content;
}, 999);


// ── SESSION MANAGEMENT ────────────────────────────────────────────────────────
// Start PHP session only on non-REST, non-AJAX requests.

if (!session_id() &&
    !defined('REST_REQUEST') &&
    (empty($_SERVER['REQUEST_URI']) ||
     (strpos($_SERVER['REQUEST_URI'], 'wp-json') === false &&
      strpos($_SERVER['REQUEST_URI'], 'admin-ajax.php') === false))) {
    session_start();
}

// Close session for REST API requests at every available hook to prevent conflicts.
$_mylog_close_session = function() {
    if (session_id() && (defined('REST_REQUEST') ||
        (!empty($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'wp-json') !== false))) {
        session_write_close();
    }
};
add_action('muplugins_loaded', $_mylog_close_session, 1);
add_action('plugins_loaded',   $_mylog_close_session, 1);
add_action('init',             $_mylog_close_session, 1);

add_action('rest_api_init', function() {
    if (session_id()) session_write_close();
}, 1);

add_action('parse_request', function($wp) {
    if (session_id() && !empty($wp->query_vars['rest_route'])) {
        session_write_close();
    }
}, 1);


// ── MODULE LOADER ─────────────────────────────────────────────────────────────

require_once get_stylesheet_directory() . '/inc/enqueue.php';
require_once get_stylesheet_directory() . '/inc/custom-post-types.php';
require_once get_stylesheet_directory() . '/inc/helpers.php';
require_once get_stylesheet_directory() . '/inc/subscription-limits.php';
require_once get_stylesheet_directory() . '/inc/mylog-hybrid-form.php';
require_once get_stylesheet_directory() . '/inc/mylog-form-handlers.php';
require_once get_stylesheet_directory() . '/inc/mylog-professional-pdf-v4.1.php';
require_once get_stylesheet_directory() . '/inc/add-user-form-enhanced.php';
require_once get_stylesheet_directory() . '/inc/diary-user-info-integration.php';
require_once get_stylesheet_directory() . '/inc/hooks.php';
require_once get_stylesheet_directory() . '/inc/shortcodes.php';
require_once get_stylesheet_directory() . '/inc/enhancements.php';

if (current_user_can('manage_options')) {
    require_once get_stylesheet_directory() . '/inc/diagnostics.php';
}


// ── ADMIN: MYLOG SUBSCRIBERS MENU ────────────────────────────────────────────

add_action('admin_menu', function() {
    add_users_page(
        'MyLog Subscribers',
        'MyLog Subscribers',
        'manage_options',
        'mylog-subscribers',
        'mylog_subscribers_page'
    );
});

function mylog_subscribers_page() {
    wp_redirect(admin_url('users.php?role=subscriber'));
    exit;
}

add_filter('views_users', function($views) {
    $count = count(get_users([
        'meta_query' => [['key' => 'subscription_price', 'compare' => 'EXISTS']]
    ]));
    $views['mylog_subscribers'] = sprintf(
        '<a href="%s">MyLog Subscribers <span class="count">(%d)</span></a>',
        admin_url('users.php?mylog_subscribers=1'),
        $count
    );
    return $views;
});

add_action('pre_get_users', function($query) {
    if (isset($_GET['mylog_subscribers'])) {
        $query->set('meta_query', [
            [['key' => 'subscription_price', 'compare' => 'EXISTS']]
        ]);
    }
});


// ── NAVIGATION ────────────────────────────────────────────────────────────────

add_filter('wp_nav_menu_objects', function($items) {
    foreach ($items as $item) {
        if (in_array('menu-logout', (array)$item->classes)) {
            $item->url = wp_logout_url('https://mymate.co.nz/mylog');
        }
    }
    return $items;
}, 20, 1);

add_action('login_form_logout', function() {
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'log-out')) {
        return;
    }
    check_admin_referer('log-out');
    wp_logout();
    wp_safe_redirect('https://mymate.co.nz/mylog');
    exit;
}, 1);

add_filter('login_redirect', function($redirect_to, $request, $user) {
    if (!isset($user->roles) || !is_array($user->roles)) {
        return $redirect_to;
    }
    if (in_array('caregiver', $user->roles)) {
        return 'https://mymate.co.nz/mylog/caregiver-dashboard/';
    }
    if (in_array('family_admin', $user->roles) || in_array('administrator', $user->roles)) {
        return 'https://mymate.co.nz/mylog/dashboard/';
    }
    return 'https://mymate.co.nz/mylog/';
}, 10, 3);

add_action('wp_logout', function() {
    wp_safe_redirect('https://mymate.co.nz/mylog');
    exit;
}, 999);