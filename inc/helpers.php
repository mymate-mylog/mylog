<?php
/**
 * Helpers: Access Control
 */
if ( ! function_exists( 'mylog_get_accessible_users' ) ) {
    function mylog_get_accessible_users() {
        if (!is_user_logged_in()) return array();
        $user = wp_get_current_user();
        if (current_user_can('administrator')) {
            return get_posts(array('post_type' => 'mylog_user', 'posts_per_page' => -1));
        }
        if (in_array('family_admin', (array)$user->roles)) {
            return get_posts(array('post_type' => 'mylog_user', 'meta_key' => 'mylog_family_admin', 'meta_value' => $user->ID, 'posts_per_page' => -1));
        }
        if (in_array('caregiver', (array)$user->roles)) {
            $allowed = get_user_meta($user->ID, 'mylog_allowed_users', true);
            if (empty($allowed)) return array();
            return get_posts(array('post_type' => 'mylog_user', 'post__in' => (array)$allowed, 'posts_per_page' => -1));
        }
        return array();
    }
}

if ( ! function_exists( 'mylog_user_is_accessible' ) ) {
    function mylog_user_is_accessible($user_id) {
        $users = mylog_get_accessible_users();
        $ids = wp_list_pluck($users, 'ID');
        return in_array(intval($user_id), $ids);
    }
}