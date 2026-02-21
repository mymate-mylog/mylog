<?php
/**
 * Enhancements: Diary Filtering & Display
 * Note: PDF Export moved to mylog-professional-pdf-v4.1.php
 */

add_shortcode('mylog_diary_with_filter', function() {
    $users = mylog_get_accessible_users();
    $selected_user = isset($_GET['view_user']) ? intval($_GET['view_user']) : 0;
    $choose_view = isset($_GET['choose_view']) ? sanitize_text_field($_GET['choose_view']) : 'all';
    $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
    $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
    
    // Check if admin
    $is_admin = current_user_can('administrator');

// Check if user has Total plan (PDF export access)
$user_subscriptions = pms_get_member_subscriptions(array('user_id' => get_current_user_id()));
$has_matanga = false;

if (!empty($user_subscriptions)) {
    foreach ($user_subscriptions as $subscription) {
        if ($subscription->status === 'active') {
            $plan = pms_get_subscription_plan($subscription->subscription_plan_id);
            if ($plan && strpos(strtolower($plan->name), 'total') !== false) {
                $has_matanga = true;
                break;
            }
        }
    }
}

$can_export_pdf = $is_admin || $has_matanga;



    ob_start(); 
    
    // Show success message if entry was just saved
    if (isset($_GET['entry_saved']) && $_GET['entry_saved'] == '1'): ?>
        <div class="success-message" style="background:#d1fae5; border-left:5px solid #10b981; padding:15px 20px; margin-bottom:10px; border-radius:8px;">
            <p style="margin:0; color:#065f46; font-weight:bold;">✅ Entry saved successfully! | Kua tiakina te tuhinga!</p>
            <p style="margin:10px 0 0 0;">
                <a href="<?php echo home_url('/mylog/'); ?>" class="button button-primary">📖 View MyLog Entries | Tirohia ngā Tuhinga</a>
            </p>
        </div>
    <?php endif; ?>
    
    <style>
        @media print {
            @page {
                margin: 12.7mm;
                size: auto;
            }
            
            body {
                margin: 0;
                padding: 0;
            }
            
            html {
                margin: 0;
                padding: 0;
            }
            
            /* Hide controls on print */
            .diary-controls {
                display: none !important;
            }
            
            .diary-entry-content {
                column-count: 2;
                column-gap: 30px;
                column-rule: 1px solid #ddd;
            }
            
            .diary-entry-content > div {
                break-inside: avoid;
            }
            
            .diary-entry-content p,
            .diary-entry-content h4,
            .diary-entry-content ul {
                break-inside: avoid;
            }
            
            .diary-entry-content img {
                max-width: 100%;
                break-inside: avoid;
            }
        }
    </style>
    
    <div class="diary-controls" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); padding:10px; border-radius:12px; margin-bottom:10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <form method="get" id="diary-filter-form" onchange="this.submit()">
            <!-- Responsive container: grid on desktop, flex-column on mobile -->
            <div class="diary-filter-main" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:16px;">
                
                <!-- User Selector -->
                <div>
                    <label style="font-weight:700; display:block; margin-bottom:8px; color:#1e40af;">View For | Tirohia mō:</label>
                    <select name="view_user" style="width:100%; padding:12px; border:2px solid #93c5fd; border-radius:8px; font-size:15px;">
                        <option value="">-- Select Person | Kōwhiria te Tangata --</option>
                        <?php foreach($users as $u): ?>
                            <option value="<?php echo $u->ID; ?>" <?php selected($selected_user, $u->ID); ?>>
                                <?php echo esc_html($u->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Date View Selector -->
                <div>
                    <label style="font-weight:700; display:block; margin-bottom:8px; color:#1e40af;">Choose View | Kōwhiria te Tirohanga</label>
                    <select name="choose_view" id="choose_view_selector" style="width:100%; padding:12px; border:2px solid #93c5fd; border-radius:8px; font-size:15px;">
                        <option value="all" <?php selected($choose_view, 'all'); ?>>All Entries | Ngā urunga katoa</option>
                        <option value="today" <?php selected($choose_view, 'today'); ?>>Today | I Tēnei Rā</option>
                        <option value="yesterday" <?php selected($choose_view, 'yesterday'); ?>>Yesterday | Inanahi</option>
                        <option value="this_week" <?php selected($choose_view, 'this_week'); ?>>This Week | I Tēnei Wiki</option>
                        <option value="this_month" <?php selected($choose_view, 'this_month'); ?>>This Month | I tenei Marama</option>
                        <option value="last_3_months" <?php selected($choose_view, 'last_3_months'); ?>>Last 3 Months | 3 Marama kua hipa</option>
                        <option value="last_6_months" <?php selected($choose_view, 'last_6_months'); ?>>Last 6 Months | 6 Marama kua hipa</option>
                        <option value="custom" <?php selected($choose_view, 'custom'); ?>>Custom Range | Awhe Ritenga</option>
                    </select>
                </div>
            </div>
            
            <!-- Custom Date Range Picker (Shows/Hides with JavaScript) -->
            <div id="custom-date-range" class="diary-date-range" style="display:<?php echo ($choose_view === 'custom') ? 'flex' : 'none'; ?>; flex-direction:column; gap:12px; margin-bottom:16px; padding-top:16px; border-top:2px solid #93c5fd;">
                <div>
                    <label style="font-weight:600; display:block; margin-bottom:6px; color:#1e40af;">From | Mai:</label>
                    <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" style="width:100%; padding:10px; border:2px solid #93c5fd; border-radius:8px;">
                </div>
                
                <div>
                    <label style="font-weight:600; display:block; margin-bottom:6px; color:#1e40af;">To | Ki:</label>
                    <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" style="width:100%; padding:10px; border:2px solid #93c5fd; border-radius:8px;">
                </div>
            </div>
            
            <!-- PDF Export Button - Always at bottom, full width -->
            <div style="margin-bottom:0;">
                <?php if ($can_export_pdf): ?>
                    <!-- Active PDF Export for Mātanga ($35) and Admin -->
                    <button type="button" onclick="window.location.href='<?php echo add_query_arg('mylog_pdf', '1'); ?>'" class="button" style="width:100%; background:#dc2626; color:white; font-weight:600; padding:12px 24px; border:none; border-radius:8px; cursor:pointer;">
                        📄 Export to PDF | Kaweake ki PDF
                    </button>
                <?php else: ?>
                    <!-- Upgrade Prompt for Takitahi ($15) and Whānau ($25) -->
                    <button type="button" onclick="if(confirm('📄 PDF Export is a Total Plan feature.\n\nUpgrade to the Total Plan ($35/month) to unlock PDF export for NASC reports and funding applications.\n\nWould you like to view pricing?')) { window.location.href='<?php echo home_url('/pricing/'); ?>'; }" class="button" style="width:100%; background:#dc2626; color:white; font-weight:600; padding:12px 24px; border:none; border-radius:8px; cursor:pointer;">
                        📄 Export to PDF | Kaweake ki PDF
                    </button>
                <?php endif; ?>
            </div>
        </form>
        
    <style>
    /* Mobile responsive - stack vertically under 768px */
    @media screen and (max-width: 767px) {
        .diary-filter-main {
            display: flex !important;
            flex-direction: column !important;
            gap: 12px !important;
        }
    }
    </style>

    </div>
    
<script>
// Show/hide custom date range when dropdown changes
document.getElementById('choose_view_selector').addEventListener('change', function() {
    const customDateRange = document.getElementById('custom-date-range');
    if (this.value === 'custom') {
        customDateRange.style.display = 'flex';
        // Don't auto-submit when switching to custom - let them pick dates first
        event.preventDefault();
        event.stopPropagation();
        return false;
    } else {
        customDateRange.style.display = 'none';
        // Auto-submit for other options
        this.form.submit();
    }
});
</script>

    <div class="diary-list">
        <?php 
        if ($selected_user) {
            // Build query args with date filter
            $query_args = array(
                'post_type' => 'mylog_entry', 
                'meta_key' => 'mylog_user_id', 
                'meta_value' => $selected_user, 
                'posts_per_page' => 10,
                'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
                'orderby' => 'date', 
                'order' => 'DESC'
            );
            
            // Add date filter based on view selection
            $date_query = array();
            
            switch($choose_view) {
                case 'today':
                    $date_query = array('after' => 'today', 'inclusive' => true);
                    break;
                
                case 'yesterday':
                    $date_query = array('after' => 'yesterday', 'before' => 'today', 'inclusive' => true);
                    break;
                
                case 'this_week':
                    $date_query = array('after' => 'monday this week', 'inclusive' => true);
                    break;
                
                case 'this_month':
                    $date_query = array('after' => 'first day of this month', 'inclusive' => true);
                    break;
                
                case 'last_3_months':
                    $date_query = array('after' => date('Y-m-d', strtotime('-3 months')), 'inclusive' => true);
                    break;
                
                case 'last_6_months':
                    $date_query = array('after' => date('Y-m-d', strtotime('-6 months')), 'inclusive' => true);
                    break;
                
                case 'custom':
                    if ($date_from && $date_to) {
                        $date_query = array('after' => $date_from, 'before' => $date_to, 'inclusive' => true);
                    }
                    break;
            }
            
            if (!empty($date_query)) {
                $query_args['date_query'] = array($date_query);
            }
            
            $diary_query = new WP_Query($query_args);
            
            
            // Helper functions - defined ONCE outside the loop
            if (!function_exists('get_traffic_light')) {
                function get_traffic_light($value) {
                    // Normalize and trim the value
                    $value = strtolower(trim($value));
                    
                    if ($value === 'green') return '🟢';
                    if ($value === 'orange') return '🟡';
                    if ($value === 'red') return '🔴';
                    
                    // Empty or invalid value
                    return '⚪';
                }
            }
            
            if (!function_exists('get_traffic_light_text')) {
                function get_traffic_light_text($value) {
                    // Normalize and trim the value
                    $value = strtolower(trim($value));
                    
                    if ($value === 'green') return 'Good';
                    if ($value === 'orange') return 'Moderate';
                    if ($value === 'red') return 'High Need';
                    
                    if (!empty($value)) {
                        return '<span style="color:#9ca3af; font-style:italic;">Not Entered/Not Relevant</span>';
                    }
                    
                    // Empty value
                    return '<span style="color:#9ca3af; font-style:italic;">Not Entered/Not Relevant</span>';
                }
            }
            
            if ($diary_query->have_posts()) {
                while ($diary_query->have_posts()) {
                    $diary_query->the_post();
                    $e = get_post();
                    
                    // Get all v3.0 metadata
                    $overall_rating = get_post_meta($e->ID, 'overall_rating', true);
                    $support_summary = get_post_meta($e->ID, 'support_summary', true);
                    $carer_start_time = get_post_meta($e->ID, 'carer_start_time', true);
                    $carer_end_time = get_post_meta($e->ID, 'carer_end_time', true);
                    
                    // Taha Tinana (Physical)
                    $tinana_mealtime = get_post_meta($e->ID, 'tinana_mealtime', true);
                    $tinana_hygiene = get_post_meta($e->ID, 'tinana_hygiene', true);
                    $tinana_bathing = get_post_meta($e->ID, 'tinana_bathing', true);
                    $tinana_dressing = get_post_meta($e->ID, 'tinana_dressing', true);
                    $tinana_mobility = get_post_meta($e->ID, 'tinana_mobility', true);
                    
                    // Taha Hinengaro (Mind & Memory) - CORRECTED field names
                    $hinengaro_memory = get_post_meta($e->ID, 'hinengaro_memory', true);
                    $hinengaro_focus = get_post_meta($e->ID, 'hinengaro_focus', true);
                    $hinengaro_comms = get_post_meta($e->ID, 'hinengaro_comms', true);
                    $hinengaro_problem = get_post_meta($e->ID, 'hinengaro_problem', true);
                    $hinengaro_household = get_post_meta($e->ID, 'hinengaro_household', true);
                    
                    // Taha Whānau (Social & Connection) - CORRECTED field names
                    $whanau_family = get_post_meta($e->ID, 'whanau_family', true);
                    $whanau_community = get_post_meta($e->ID, 'whanau_community', true);
                    $whanau_digital = get_post_meta($e->ID, 'whanau_digital', true);
                    $whanau_hobbies = get_post_meta($e->ID, 'whanau_hobbies', true);
                    $whanau_group = get_post_meta($e->ID, 'whanau_group', true);
                    
                    // Taha Wairua — V4.1 field names + V3 backward compat
                    $wairua_cultural   = get_post_meta($e->ID, 'wairua_cultural', true);
                    $wairua_nature     = get_post_meta($e->ID, 'wairua_nature', true);
                    $wairua_identity   = get_post_meta($e->ID, 'wairua_identity', true);
                    $wairua_karakia    = get_post_meta($e->ID, 'wairua_karakia', true);
                    $wairua_culture    = get_post_meta($e->ID, 'wairua_culture', true);
                    $wairua_reflection = get_post_meta($e->ID, 'wairua_reflection', true);
                    
                    // Carer Context - CORRECTED field names
                    $carer_battery = get_post_meta($e->ID, 'carer_battery', true);
                    $support_window = get_post_meta($e->ID, 'support_window', true);
                    $coverage_absence = get_post_meta($e->ID, 'coverage_absence', true);
                    
                    // Notes & Photo
                    $quick_notes = get_post_meta($e->ID, 'quick_notes', true);
                    $extra_notes = get_post_meta($e->ID, 'extra_notes', true);
                    $carer_notes = get_post_meta($e->ID, 'carer_notes', true);
                    
                    // Check if any data exists (V4.1 + backward compat)
                    $has_data = $overall_rating || $support_summary || $tinana_mealtime || $hinengaro_memory ||
                                $whanau_family || $wairua_cultural || $wairua_karakia ||
                                $carer_battery || $support_window || $extra_notes || $quick_notes ||
                                !empty(get_post_meta($e->ID, '_mylog_photos', true)) ||
                                has_post_thumbnail($e->ID);
                ?>
                    <div class="diary-card" style="border:1px solid #045a85; padding:20px; margin-bottom:20px; background:#fff; border-radius:12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">

                        <!-- Entry Header -->
                        <h3 style="margin:0 0 16px 0; color:#007cba; border-bottom: 2px solid #045a85; padding-bottom:10px; font-size:18px;">
                            <?php echo get_the_date('l, j F Y \a\t g:i a', $e) . ' - by ' . get_the_author_meta('display_name', $e->post_author); ?>
                        </h3>
                        
                        <?php if ($has_data): ?>
                        
                        <div class="diary-entry-content">
                            
                            <!-- Overall Summary Section -->
                            <?php if ($overall_rating || $support_summary || $carer_start_time): ?>
                                <div style="margin-bottom:16px; padding:12px; background:linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius:8px;">
                                    
                                    <?php if ($carer_start_time && $carer_end_time): ?>
                                        <p style="margin:0 0 8px 0; color:#1e40af; font-weight:600;">
                                            ⏰ Shift: <?php echo esc_html($carer_start_time); ?> - <?php echo esc_html($carer_end_time); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if ($overall_rating): ?>
                                        <p style="margin:0 0 8px 0;">
                                            <strong>How was today overall?</strong><br>
                                            <?php echo get_traffic_light($overall_rating); ?> <?php echo get_traffic_light_text($overall_rating); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if ($support_summary): ?>
                                        <p style="margin:0;">
                                            <strong>Support Level Required:</strong><br>
                                            <?php echo get_traffic_light($support_summary); ?> <?php echo get_traffic_light_text($support_summary); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Taha Tinana (Physical) - Always show all fields -->
                            <div style="margin-bottom:12px; padding:12px; background:#f0fdf4; border-left:4px solid #16a34a; border-radius:8px;">
                                <h4 style="margin:0 0 10px 0; color:#16a34a; font-size:16px;">💪 Taha Tinana | Physical Wellbeing</h4>
                                
                                <p style="margin:5px 0;"><?php echo get_traffic_light($tinana_mealtime); ?> <strong>Mealtime & Eating:</strong> <?php echo get_traffic_light_text($tinana_mealtime); ?></p>
                                
                                <p style="margin:5px 0;"><?php echo get_traffic_light($tinana_hygiene); ?> <strong>Personal Hygiene:</strong> <?php echo get_traffic_light_text($tinana_hygiene); ?></p>
                                
                                <p style="margin:5px 0;"><?php echo get_traffic_light($tinana_bathing); ?> <strong>Bathing/Showering:</strong> <?php echo get_traffic_light_text($tinana_bathing); ?></p>
                                
                                <p style="margin:5px 0;"><?php echo get_traffic_light($tinana_dressing); ?> <strong>Dressing:</strong> <?php echo get_traffic_light_text($tinana_dressing); ?></p>
                                
                                <p style="margin:5px 0;"><?php echo get_traffic_light($tinana_mobility); ?> <strong>Mobility:</strong> <?php echo get_traffic_light_text($tinana_mobility); ?></p>
                            </div>
                            
                            <!-- Taha Hinengaro (Mind & Memory) - Always show all fields -->
                            <div style="margin-bottom:12px; padding:12px; background:#f0f9ff; border-left:4px solid #0284c7; border-radius:8px;">
                                <h4 style="margin:0 0 10px 0; color:#0284c7; font-size:16px;">🧠 Taha Hinengaro | Mind & Memory</h4>
                                
                                <p style="margin:5px 0;"><?php echo get_traffic_light($hinengaro_memory); ?> <strong>Memory / Remembering:</strong> <?php echo get_traffic_light_text($hinengaro_memory); ?></p>
                                
                                <p style="margin:5px 0;"><?php echo get_traffic_light($hinengaro_focus); ?> <strong>Focus / Attention on Tasks:</strong> <?php echo get_traffic_light_text($hinengaro_focus); ?></p>
                                
                                <p style="margin:5px 0;"><?php echo get_traffic_light($hinengaro_comms); ?> <strong>Communication / Using Devices:</strong> <?php echo get_traffic_light_text($hinengaro_comms); ?></p>
                                
                                <p style="margin:5px 0;"><?php echo get_traffic_light($hinengaro_problem); ?> <strong>Problem Solving / Daily Choices:</strong> <?php echo get_traffic_light_text($hinengaro_problem); ?></p>
                                
                                <p style="margin:5px 0;"><?php echo get_traffic_light($hinengaro_household); ?> <strong>Household Tasks:</strong> <?php echo get_traffic_light_text($hinengaro_household); ?></p>
                            </div>
                            
                            <!-- Taha Whānau (Social & Connection) - Always show all fields -->
                            <div style="margin-bottom:12px; padding:12px; background:#fef3c7; border-left:4px solid #f59e0b; border-radius:8px;">
                                <h4 style="margin:0 0 10px 0; color:#d97706; font-size:16px;">👨‍👩‍👧‍👦 Taha Whānau | Social & Connection</h4>
                                
                                <p style="margin:5px 0;"><?php echo get_traffic_light($whanau_family); ?> <strong>Family & Whānau Time:</strong> <?php echo get_traffic_light_text($whanau_family); ?></p>
                                
                                <p style="margin:5px 0;"><?php echo get_traffic_light($whanau_community); ?> <strong>Community Outing / Transport:</strong> <?php echo get_traffic_light_text($whanau_community); ?></p>
                                
                                <p style="margin:5px 0;"><?php echo get_traffic_light($whanau_digital); ?> <strong>Digital Connection (calls / video):</strong> <?php echo get_traffic_light_text($whanau_digital); ?></p>
                                
                                <p style="margin:5px 0;"><?php echo get_traffic_light($whanau_hobbies); ?> <strong>Active Play / Hobbies:</strong> <?php echo get_traffic_light_text($whanau_hobbies); ?></p>
                                
                                <p style="margin:5px 0;"><?php echo get_traffic_light($whanau_group); ?> <strong>Group Participation / Events:</strong> <?php echo get_traffic_light_text($whanau_group); ?></p>
                            </div>
                            
                            <!-- Taha Wairua — V4.1 consolidated fields, V3 backward compat -->
                            <div style="margin-bottom:12px; padding:12px; background:#faf5ff; border-left:4px solid #9333ea; border-radius:8px;">
                                <h4 style="margin:0 0 10px 0; color:#9333ea; font-size:16px;">✨ Taha Wairua | Spiritual & Personal</h4>
                                <?php
                                $disp_cultural = $wairua_cultural ?: $wairua_karakia;
                                $disp_identity = $wairua_identity ?: $wairua_reflection;
                                ?>
                                <p style="margin:5px 0;"><?php echo get_traffic_light($disp_cultural); ?> <strong>Cultural / Spiritual Participation:</strong> <?php echo get_traffic_light_text($disp_cultural); ?></p>
                                <p style="margin:5px 0;"><?php echo get_traffic_light($wairua_nature); ?> <strong>Nature &amp; Quiet Time:</strong> <?php echo get_traffic_light_text($wairua_nature); ?></p>
                                <p style="margin:5px 0;"><?php echo get_traffic_light($disp_identity); ?> <strong>Identity &amp; Belonging:</strong> <?php echo get_traffic_light_text($disp_identity); ?></p>
                                <?php if ($wairua_culture && !$wairua_cultural): ?>
                                    <p style="margin:5px 0;"><?php echo get_traffic_light($wairua_culture); ?> <strong>Cultural Expression:</strong> <?php echo get_traffic_light_text($wairua_culture); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Support Detail Fields (V4.1) - Show if ANY support detail exists -->
                            <?php
                            // Check if any support detail fields exist
                            $all_activity_fields = [
                                'tinana_mealtime', 'tinana_hygiene', 'tinana_bathing', 'tinana_dressing', 'tinana_mobility',
                                'hinengaro_memory', 'hinengaro_focus', 'hinengaro_comms', 'hinengaro_problem', 'hinengaro_household',
                                'whanau_family', 'whanau_community', 'whanau_digital', 'whanau_hobbies', 'whanau_group',
                                'wairua_cultural', 'wairua_nature', 'wairua_identity'  // V4.1 consolidated fields
                            ];

                            $has_support_details = false;
                            $support_details_html = '';

                            $support_type_labels = [
                                'supervision'  => 'Supervision only (watching, nearby)',
                                'verbal'       => 'Verbal prompts (reminders, encouragement)',
                                'guidance'     => 'Physical guidance (light touch, hand-over-hand)',
                                'partial'      => 'Partial assistance (helping with some steps)',
                                'full_1carer'  => 'Full assistance — 1 carer, hands-on',
                                'full_2carers' => 'Full assistance — 2+ carers needed',
                                'refused'      => 'Activity refused or unable to complete',
                            ];
                            $field_display_labels = [
                                'tinana_mealtime'     => 'Mealtime Participation',
                                'tinana_hygiene'      => 'Personal Hygiene',
                                'tinana_bathing'      => 'Bathing / Showering',
                                'tinana_dressing'     => 'Dressing / Getting Ready',
                                'tinana_mobility'     => 'Moving / Walking / Mobility',
                                'hinengaro_memory'    => 'Memory / Remembering',
                                'hinengaro_focus'     => 'Focus / Attention',
                                'hinengaro_comms'     => 'Communication / Using Devices',
                                'hinengaro_problem'   => 'Problem Solving / Daily Choices',
                                'hinengaro_household' => 'Household Tasks',
                                'whanau_family'       => 'Family & Whanau Time',
                                'whanau_community'    => 'Community Outing / Transport',
                                'whanau_digital'      => 'Digital Connection',
                                'whanau_hobbies'      => 'Active Play / Hobbies',
                                'whanau_group'        => 'Group Participation / Events',
                                'wairua_cultural'     => 'Cultural / Spiritual Participation',
                                'wairua_nature'       => 'Nature & Quiet Time',
                                'wairua_identity'     => 'Identity & Belonging',
                            ];
                            foreach ($all_activity_fields as $field) {
                                $support_type = get_post_meta($e->ID, 'support_type_' . $field, true);
                                $support_time = get_post_meta($e->ID, 'support_time_' . $field, true);
                                $equipment    = get_post_meta($e->ID, 'equipment_' . $field, true);
                                if (!empty($support_type) || !empty($support_time) || !empty($equipment)) {
                                    $has_support_details = true;
                                    $field_label = isset($field_display_labels[$field]) ? $field_display_labels[$field] : ucwords(str_replace('_', ' ', $field));
                                    $type_label  = isset($support_type_labels[$support_type]) ? $support_type_labels[$support_type] : ucfirst($support_type);
                                    $support_details_html .= '<div style="margin-bottom:8px;padding:10px;background:#fff;border-radius:6px;border-left:3px solid #fcd34d;">';
                                    $support_details_html .= '<strong style="color:#92400e;">' . esc_html($field_label) . ':</strong> ';
                                    if ($support_type) {
                                        $support_details_html .= '<span style="color:#1f2937;">' . esc_html($type_label) . '</span>';
                                    }
                                    if ($support_time) {
                                        $support_details_html .= ' <span style="color:#6b7280;font-size:13px;">(' . intval($support_time) . ' min)</span>';
                                    }
                                    if (!empty($equipment) && is_array($equipment)) {
                                        $equipment_clean = array_filter($equipment, function($eq) { return $eq !== 'none_na'; });
                                        if (!empty($equipment_clean)) {
                                            $support_details_html .= '<br><span style="color:#6b7280;font-size:12px;">🔧 Equipment: ' . esc_html(implode(', ', $equipment_clean)) . '</span>';
                                        } elseif (in_array('none_na', $equipment)) {
                                            $support_details_html .= '<br><span style="color:#9ca3af;font-size:12px;">🔧 Equipment: None / N/A</span>';
                                        }
                                    }
                                    $support_details_html .= '</div>';
                                }
                            }

                            if ($has_support_details):
                            ?>
                            <div style="margin-bottom:12px; padding:12px; background:#fef3c7; border-left:4px solid #f59e0b; border-radius:8px;">
                                <h4 style="margin:0 0 10px 0; color:#d97706; font-size:16px;">🔧 Support Detail | Ngā Taipitopito Tautoko</h4>
                                <?php echo $support_details_html; ?>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Carer Context - Always show all fields -->
                            <div style="margin-bottom:12px; padding:12px; background:#fef2f2; border-left:4px solid #ef4444; border-radius:8px;">
                                <h4 style="margin:0 0 10px 0; color:#ef4444; font-size:16px;">🩺 Carer Context</h4>
                                
                                <p style="margin:5px 0;"><?php echo get_traffic_light($carer_battery); ?> <strong>Carer Energy / Battery Status:</strong> <?php echo get_traffic_light_text($carer_battery); ?></p>
                                
                                <p style="margin:5px 0;"><?php echo get_traffic_light($support_window); ?> <strong>Support Window Today:</strong> <?php echo get_traffic_light_text($support_window); ?></p>
                                
                                <p style="margin:5px 0;"><?php echo get_traffic_light($coverage_absence); ?> <strong>Coverage During Carer Absence:</strong> <?php echo get_traffic_light_text($coverage_absence); ?></p>
                                
                                <?php if ($carer_notes): ?>
                                    <div style="margin-top:12px; padding-top:12px; border-top:1px solid #fee2e2;">
                                        <p style="margin:0; color:#374151;"><strong>Carer Notes:</strong></p>
                                        <p style="margin:4px 0 0 0; white-space:pre-wrap; line-height:1.6; color:#4b5563;"><?php echo nl2br(esc_html($carer_notes)); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Today's Notes (V4.1) - Always show -->
                            <div style="margin-bottom:12px; padding:12px; background:#ecfdf5; border-left:4px solid #10b981; border-radius:8px;">
                                <h4 style="margin:0 0 10px 0; color:#10b981; font-size:16px;">📝 Today's Notes | Ngā Tuhipoka o Te Rā</h4>
                                <?php if ($quick_notes): ?>
                                    <p style="margin:0; white-space:pre-wrap; line-height:1.6;"><?php echo nl2br(esc_html($quick_notes)); ?></p>
                                <?php else: ?>
                                    <p style="margin:0; color:#9ca3af; font-style:italic;">Not entered</p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Extra Notes (Audio Notes) - Always show -->
                            <div style="margin-bottom:12px; padding:12px; background:#f9fafb; border-left:4px solid #6b7280; border-radius:8px;">
                                <h4 style="margin:0 0 10px 0; color:#374151; font-size:16px;">📝 Extra Notes | Tuhipoka Taapiri</h4>
                                <?php if ($extra_notes): ?>
                                    <p style="margin:0; white-space:pre-wrap; line-height:1.6;"><?php echo nl2br(esc_html($extra_notes)); ?></p>
                                <?php else: ?>
                                    <p style="margin:0; color:#9ca3af; font-style:italic;">Not entered / Not Supported</p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Photos — multi (V4.1+), single fallback for older entries -->
                            <?php
                            $view_photo_ids = get_post_meta($e->ID, '_mylog_photos', true);
                            if (empty($view_photo_ids) && has_post_thumbnail($e->ID)) {
                                $view_photo_ids = [get_post_thumbnail_id($e->ID)];
                            }
                            if (!empty($view_photo_ids)):
                            ?>
                                <div style="margin-top:12px;">
                                    <h4 style="margin:0 0 10px 0; color:#374151; font-size:16px;">📷 Photos | Ngā Whakaahua</h4>
                                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                        <?php foreach ($view_photo_ids as $view_photo_id):
                                            $thumb_url = wp_get_attachment_image_url($view_photo_id, 'medium');
                                            $full_url  = wp_get_attachment_image_url($view_photo_id, 'full');
                                            if (!$thumb_url) continue;
                                        ?>
                                            <a href="<?php echo esc_url($full_url); ?>" target="_blank" rel="noopener"
                                               style="display:block;width:calc(50% - 4px);max-width:180px;">
                                                <img src="<?php echo esc_url($thumb_url); ?>"
                                                     style="width:100%;height:auto;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);display:block;"
                                                     loading="lazy" alt="Entry photo" />
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                        </div>
                        
                        <?php else: ?>
                            <p style="color:#6b7280; font-style:italic;">No details added for this entry.</p>
                        <?php endif; ?>
                    </div>
                <?php 
                }
                
                // Pagination
                echo '<div style="margin-top:20px; text-align:center;">';
                echo paginate_links(array(
                    'total' => $diary_query->max_num_pages,
                    'current' => max(1, get_query_var('paged')),
                    'prev_text' => '← Previous | Mua',
                    'next_text' => 'Next | Panuku →',
                    'type' => 'plain'
                ));
                echo '</div>';
                
                wp_reset_postdata();
            } else { 
                echo '<p>No entries found for this date range. | Kāore i kitea he tuhinga mō tēnei wā.</p>'; 
            }
        } else { 
            echo '<p>Please select a person from the menu above to view their MyLog. | Kōwhiria tētahi tangata i te tahua i runga ake nei ki te tiro i tō rātou pukapuka.</p>'; 
        }
        ?>
    </div>
    <?php return ob_get_clean();
});