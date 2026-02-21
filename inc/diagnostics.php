<?php
/**
 * Admin Diagnostics — MyLog
 * Only loaded for administrators (gated in functions.php).
 */
add_action('admin_menu', function() {
    add_menu_page(
        'MyLog Diagnostics',
        'MyLog Diag',
        'manage_options',
        'mymate-diagnostics',
        function() {
            if (!current_user_can('manage_options')) wp_die('Access denied.');
            $users   = wp_count_posts('mylog_user')->publish;
            $entries = wp_count_posts('mylog_entry')->publish;
            ?>
            <div class="wrap">
                <h1>🔍 MyLog Diagnostics</h1>
                <table class="widefat" style="max-width:400px;">
                    <tr><th>People Supported</th><td><?php echo intval($users); ?></td></tr>
                    <tr><th>Log Entries</th><td><?php echo intval($entries); ?></td></tr>
                    <tr><th>WP Timezone</th><td><?php echo esc_html(get_option('timezone_string') ?: get_option('gmt_offset') . ' UTC'); ?></td></tr>
                    <tr><th>Current Time (Site TZ)</th><td><?php echo esc_html(wp_date('Y-m-d H:i:s')); ?></td></tr>
                </table>
            </div>
            <?php
        },
        'dashicons-admin-tools',
        100
    );
});