<?php
/**
 * Diary Page User Info Integration - PHP 8.4 Compatible
 * MyLog v3.0.2 - Simple version
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Render user info button on diary page
function mylog_render_diary_user_button() {
    if (!isset($_GET['selected_user']) || empty($_GET['selected_user'])) {
        return;
    }
    
    if (!is_user_logged_in()) {
        return;
    }
    
    $current_user_id = get_current_user_id();
    $selected_user_id = sanitize_text_field($_GET['selected_user']);
    
    $supported_users = get_user_meta($current_user_id, 'mylog_supported_users', true);
    
    if (!is_array($supported_users) || !isset($supported_users[$selected_user_id])) {
        return;
    }
    
    $user_data = $supported_users[$selected_user_id];
    $display_name = !empty($user_data['preferred_name']) ? $user_data['preferred_name'] : $user_data['full_name'];
    
    ?>
    <div class="mylog-diary-user-info">
        <button type="button" 
                class="mylog-learn-more-btn mylog-btn mylog-btn--secondary mylog-btn--small" 
                data-user-id="<?php echo esc_attr($selected_user_id); ?>">
            Learn more about <?php echo esc_html($display_name); ?>
        </button>
    </div>
    <?php
}
add_action('mylog_after_user_selection', 'mylog_render_diary_user_button');

// Ensure assets are loaded on diary pages
function mylog_diary_enqueue_assets() {
    if (is_page('mylog') || is_page(array('mylog', 'diary', 'entries'))) {
        wp_enqueue_script('mylog-user-form');
        wp_enqueue_style('mylog-user-form');
    }
}
add_action('wp_footer', 'mylog_diary_enqueue_assets');