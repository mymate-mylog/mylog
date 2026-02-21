<?php
/**
 * DIAGNOSTIC: Check if notes are being saved
 * 
 * INSTRUCTIONS:
 * 1. Upload this to /wp-content/themes/kadence-child/
 * 2. Visit: yoursite.com/wp-content/themes/kadence-child/check-notes.php
 * 3. This will show the last 5 entries and what data is saved
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!is_user_logged_in()) {
    die('Please log in first');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Notes Diagnostic</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; background: #f5f5f5; }
        .entry { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .entry h3 { margin: 0 0 10px 0; color: #1a73e8; }
        .field { margin: 10px 0; padding: 10px; background: #f8f9fa; border-left: 3px solid #1a73e8; }
        .field-name { font-weight: bold; color: #5f6368; font-size: 12px; }
        .field-value { margin-top: 5px; }
        .empty { color: #dc3545; font-style: italic; }
        .present { color: #28a745; font-weight: bold; }
    </style>
</head>
<body>
    <h1>📊 MyLog Notes Diagnostic</h1>
    <p>Checking the last 5 entries for note data...</p>

    <?php
    // Get last 5 entries
    $entries = get_posts([
        'post_type' => 'mylog_entry',
        'posts_per_page' => 5,
        'orderby' => 'date',
        'order' => 'DESC'
    ]);

    if (empty($entries)) {
        echo '<p style="color:red;">No entries found!</p>';
    } else {
        foreach ($entries as $entry) {
            $quick_notes = get_post_meta($entry->ID, 'quick_notes', true);
            $extra_notes = get_post_meta($entry->ID, 'extra_notes', true);
            
            // Get ALL meta to see what's actually there
            $all_meta = get_post_meta($entry->ID);
            
            echo '<div class="entry">';
            echo '<h3>Entry #' . $entry->ID . ' - ' . get_the_date('M j, Y g:ia', $entry) . '</h3>';
            
            echo '<div class="field">';
            echo '<div class="field-name">QUICK_NOTES (Today\'s Notes):</div>';
            if (!empty($quick_notes)) {
                echo '<div class="field-value present">✅ PRESENT: ' . esc_html($quick_notes) . '</div>';
            } else {
                echo '<div class="field-value empty">❌ EMPTY or NOT SAVED</div>';
            }
            echo '</div>';
            
            echo '<div class="field">';
            echo '<div class="field-name">EXTRA_NOTES (Additional Notes):</div>';
            if (!empty($extra_notes)) {
                echo '<div class="field-value present">✅ PRESENT: ' . esc_html($extra_notes) . '</div>';
            } else {
                echo '<div class="field-value empty">❌ EMPTY or NOT SAVED</div>';
            }
            echo '</div>';
            
            // Show support detail fields
            $support_type_fields = array_filter($all_meta, function($key) {
                return strpos($key, 'support_type_') === 0;
            }, ARRAY_FILTER_USE_KEY);
            
            if (!empty($support_type_fields)) {
                echo '<div class="field">';
                echo '<div class="field-name">SUPPORT DETAIL FIELDS (Moderate dropdowns):</div>';
                foreach ($support_type_fields as $key => $value) {
                    $field_name = str_replace('support_type_', '', $key);
                    $support_type = $value[0];
                    $support_time = get_post_meta($entry->ID, 'support_time_' . $field_name, true);
                    
                    if (!empty($support_type)) {
                        echo '<div class="field-value present">✅ ' . esc_html($field_name) . ': ' . esc_html($support_type);
                        if ($support_time) echo ' (' . $support_time . ' min)';
                        echo '</div>';
                    }
                }
                echo '</div>';
            }
            
            // Show ALL meta keys for debugging
            echo '<details style="margin-top:15px;"><summary style="cursor:pointer;color:#666;">🔍 Show ALL meta fields (debug)</summary>';
            echo '<pre style="background:#f8f9fa;padding:10px;overflow-x:auto;font-size:11px;">';
            foreach ($all_meta as $key => $values) {
                if (strpos($key, '_') !== 0) { // Skip WordPress internal meta
                    echo esc_html($key) . ': ' . esc_html(print_r($values[0], true)) . "\n";
                }
            }
            echo '</pre></details>';
            
            echo '</div>';
        }
    }
    ?>

    <hr style="margin: 30px 0;">
    <h2>🧪 Test Form Submission</h2>
    <p>Fill this in and submit to test if the handler is working:</p>
    
    <form method="POST" style="background:white;padding:20px;border-radius:8px;">
        <div style="margin-bottom:15px;">
            <label><strong>Quick Notes:</strong></label><br>
            <textarea name="quick_notes" rows="3" style="width:100%;padding:8px;"></textarea>
        </div>
        
        <div style="margin-bottom:15px;">
            <label><strong>Extra Notes:</strong></label><br>
            <textarea name="extra_notes" rows="3" style="width:100%;padding:8px;"></textarea>
        </div>
        
        <?php wp_nonce_field('mylog_add_entry', '_wpnonce'); ?>
        <input type="hidden" name="mylog_user_id" value="<?php echo get_posts(['post_type' => 'mylog_user', 'posts_per_page' => 1])[0]->ID ?? 0; ?>">
        
        <button type="submit" name="detailed_submit" style="background:#1a73e8;color:white;border:none;padding:12px 24px;border-radius:6px;cursor:pointer;">
            Test Submit
        </button>
    </form>
    
    <?php
    if (isset($_POST['detailed_submit'])) {
        echo '<div style="background:#d4edda;border:1px solid #c3e6cb;padding:15px;margin-top:15px;border-radius:6px;">';
        echo '<strong>Form submitted!</strong> Refresh page to see if it saved.';
        echo '<br>Posted quick_notes: ' . esc_html($_POST['quick_notes'] ?? 'EMPTY');
        echo '<br>Posted extra_notes: ' . esc_html($_POST['extra_notes'] ?? 'EMPTY');
        echo '</div>';
    }
    ?>
</body>
</html>