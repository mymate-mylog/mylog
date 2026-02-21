<?php
/**
 * MyLog PDF Report Engine — Version 3.0
 * Production-grade assessment report with trend analysis and stability scoring
 * Available to all subscription levels
 */

add_action('init', 'mylog_generate_condensed_pdf_download');

function mylog_generate_condensed_pdf_download() {
    if (!isset($_GET['mylog_pdf']) || !isset($_GET['view_user'])) {
        return;
    }

    if (!is_user_logged_in()) {
        wp_die('Please log in to export reports.');
    }

    $tcpdf_path = get_stylesheet_directory() . '/vendor/tcpdf/tcpdf.php';
    if (!file_exists($tcpdf_path)) {
        wp_die('PDF library not found. Please contact support.');
    }

    try {
        require_once $tcpdf_path;
    } catch (Exception $e) {
        wp_die('Failed to load PDF library.');
    }

    $user_id     = intval($_GET['view_user']);
    $date_range  = sanitize_text_field($_GET['choose_view'] ?? 'last_30');
    $date_from   = sanitize_text_field($_GET['date_from'] ?? '');
    $date_to     = sanitize_text_field($_GET['date_to'] ?? '');

    if (!mylog_user_is_accessible($user_id)) wp_die('Access denied.');

    // Person details
    $person      = get_post($user_id);
    $person_name = $person ? $person->post_title : 'Unknown';
    $person_dob  = get_post_meta($user_id, 'mylog_user_dob', true);
    $person_goals = get_post_meta($user_id, 'mylog_person_goals', true);

    // Resolve date range
    $dates = mylog_pdf_resolve_dates($date_range, $date_from, $date_to);
    $from  = $dates['from'];
    $to    = $dates['to'];

    // Get entries
    $entries = mylog_pdf_get_entries($user_id, $from, $to);

    // For 'all entries' the query uses a wide 2000-01-01 anchor. Now override
    // $from/$to to the actual span of real entries so the period label is
    // meaningful (e.g. "21 Feb 2026 – 22 Feb 2026" not "1 Jan 2000 – today").
    if (!empty($entries)) {
        $entry_dates = array_column($entries, 'date');
        sort($entry_dates);
        $from = $entry_dates[0];
        $to   = end($entry_dates);
    }

    if (empty($entries)) {
        wp_die('No entries found for the selected period.');
    }

    // Get comparison entries (previous equal period)
    $period_days  = max(1, (strtotime($to) - strtotime($from)) / 86400);
    $prev_from    = wp_date('Y-m-d', strtotime($from) - ($period_days * 86400));
    $prev_to      = wp_date('Y-m-d', strtotime($from) - 86400);
    $prev_entries = mylog_pdf_get_entries($user_id, $prev_from, $prev_to);

    // Calculate stats
    try {
        $stats          = mylog_pdf_calculate_stats($entries);
        $prev_stats     = mylog_pdf_calculate_stats($prev_entries);
        $enhanced_stats = mylog_pdf_calculate_enhanced_stats($entries);
    } catch (Exception $e) {
        wp_die('Failed to calculate statistics.');
    }

    try {
        $pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);
    } catch (Exception $e) {
        wp_die('Failed to initialize PDF.');
    }
    $pdf->SetCreator('MyLog — mymate.co.nz');
    $pdf->SetAuthor('MyLog');
    $pdf->SetTitle('Daily Support Report — ' . $person_name);
    $pdf->SetSubject('Holistic Daily Support Log');
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 10);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetFont('dejavusans', '', 10); // DejaVu Sans supports Te Reo macrons
    
    // === PAGE 1: EXECUTIVE SUMMARY (Legend, Goals, Key Findings, Trends, Carer, Coverage, Disclaimer) ===
    $pdf->AddPage();
    $exec_html = mylog_pdf_build_executive_summary_page1($person_name, $person_dob, $from, $to, $entries, $enhanced_stats, $prev_stats, $person_goals);
    $pdf->writeHTML($exec_html, true, false, true, false, '');
    
    // === PAGE 2: WELLBEING SNAPSHOT + FUNCTIONAL CAPACITY + RISK ASSESSMENT ===
    $pdf->AddPage();
    $exec_p2_html = mylog_pdf_build_executive_summary_page2($person_name, $entries, $enhanced_stats);
    $pdf->writeHTML($exec_p2_html, true, false, true, false, '');
    
    // === PAGE 2: RECOMMENDATIONS ===
    $recommendations = mylog_pdf_generate_recommendations($enhanced_stats, $entries);
    if (!empty($recommendations)) {
        $pdf->AddPage();
        $rec_html = mylog_pdf_build_recommendations_page($recommendations, true); // Full page version
        $pdf->writeHTML($rec_html, true, false, true, false, '');
    }
    
    // === PAGES 3+: WEEKLY DIGEST TABLES ===
    $weekly_pages = mylog_pdf_build_weekly_digests($entries, $from, $to);
    foreach ($weekly_pages as $week_html) {
        $pdf->AddPage();
        $pdf->writeHTML($week_html, true, false, true, false, '');
    }
    
    // === CRITICAL INCIDENTS (if any red days) ===
    $critical_html = mylog_pdf_build_critical_incidents($entries, $enhanced_stats);
    if ($critical_html) {
        $pdf->AddPage();
        $pdf->writeHTML($critical_html, true, false, true, false, '');
    }
    
    // === SUPPORT REQUIREMENTS BY DOMAIN ===
    $pdf->AddPage();
    $support_html = mylog_pdf_build_support_requirements_by_domain($enhanced_stats, $entries);
    $pdf->writeHTML($support_html, true, false, true, false, '');
    
    // === APPENDIX A: COMPLETE RECOMMENDATIONS (if more than 5) ===
    if (count($recommendations) > 5) {
        $pdf->AddPage();
        $appendix_html = mylog_pdf_build_recommendations_page($recommendations, false); // Appendix version
        $pdf->writeHTML($appendix_html, true, false, true, false, '');
    }
    
    // === APPENDIX B: AUDIT TRAIL ===
    $pdf->AddPage();
    $audit_html = mylog_pdf_build_audit_trail($entries, $person_name, $from, $to);
    $pdf->writeHTML($audit_html, true, false, true, false, '');
    
    // === APPENDIX C: DETAILED NOTES (if any) ===
    $notes_entries = array_filter($entries, function($e) {
        return !empty($e['quick_notes']) || !empty($e['extra_notes']) || !empty($e['carer_notes']);
    });

    if (!empty($notes_entries)) {
        $pdf->AddPage();
        $notes_html = mylog_pdf_build_notes_page($notes_entries, $person_name);
        $pdf->writeHTML($notes_html, true, false, true, false, '');
    }
    
    // File naming: MyLog_FirstName_LastName_Period.pdf
    $name_parts = explode(' ', $person_name);
    $first_name = $name_parts[0];
    $last_name = count($name_parts) > 1 ? end($name_parts) : '';
    $period = wp_date('MY', strtotime($from)) . '-' . wp_date('MY', strtotime($to));

    $filename = 'MyLog_' . sanitize_file_name($first_name) . ($last_name ? '_' . sanitize_file_name($last_name) : '') . '_' . $period . '.pdf';
    $pdf->Output($filename, 'D');
    exit;
}

/**
 * Resolve date range to from/to strings
 */
function mylog_pdf_resolve_dates($range, $custom_from, $custom_to) {
    // Use wp_date() throughout so all calculations respect the site's configured
    // timezone (e.g. NZ UTC+13) rather than the server's UTC clock. Without this,
    // "today" at 6am NZ resolves to yesterday UTC and entries get excluded.
    $today     = wp_date('Y-m-d');
    $yesterday = wp_date('Y-m-d', strtotime('-1 day'));

    switch ($range) {
        // Native PDF range keys
        case 'last_7':        return ['from' => wp_date('Y-m-d', strtotime('-7 days')),   'to' => $today];
        case 'last_30':       return ['from' => wp_date('Y-m-d', strtotime('-30 days')),  'to' => $today];
        case 'last_90':       return ['from' => wp_date('Y-m-d', strtotime('-90 days')),  'to' => $today];
        case 'last_180':      return ['from' => wp_date('Y-m-d', strtotime('-180 days')), 'to' => $today];
        // Diary filter labels — passed through when PDF button is clicked
        case 'all':           return ['from' => '2000-01-01', 'to' => $today]; // Wide net — period label corrected from actual entries
        case 'today':         return ['from' => $today,     'to' => $today];
        case 'yesterday':     return ['from' => $yesterday,  'to' => $yesterday];
        case 'this_week':     return ['from' => wp_date('Y-m-d', strtotime('monday this week')), 'to' => $today];
        case 'this_month':    return ['from' => wp_date('Y-m-01'), 'to' => $today];
        case 'last_3_months': return ['from' => wp_date('Y-m-d', strtotime('-3 months')), 'to' => $today];
        case 'last_6_months': return ['from' => wp_date('Y-m-d', strtotime('-6 months')), 'to' => $today];
        case 'custom':
            if ($custom_from && $custom_to) return ['from' => $custom_from, 'to' => $custom_to];
            // Fall through to default if dates missing
        default:              return ['from' => wp_date('Y-m-d', strtotime('-30 days')), 'to' => $today];
    }
}

/**
 * Fetch and flatten entries
 */
function mylog_pdf_get_entries($user_id, $from, $to) {
    // Use column-based date_query with explicit >= / <= comparisons.
    // WP's 'after'/'before' with Y-m-d strings can exclude the boundary day
    // itself because WordPress compares against midnight of that date.
    // The 'compare' approach matches any post whose date falls on or between
    // the two dates, which is exactly what every filter option needs.
    $query = new WP_Query([
        'post_type'      => 'mylog_entry',
        'meta_key'       => 'mylog_user_id',
        'meta_value'     => $user_id,
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'ASC',
        'date_query'     => [[
            'after'     => $from . ' 00:00:00',
            'before'    => $to   . ' 23:59:59',
            'inclusive' => true,
        ]],
    ]);

    $entries = [];
    $score_map = ['green' => 2, 'orange' => 1, 'red' => 0];
    $all_fields = [
        'tinana_mealtime','tinana_hygiene','tinana_bathing','tinana_dressing','tinana_mobility',
        'hinengaro_memory','hinengaro_focus','hinengaro_comms','hinengaro_problem','hinengaro_household',
        'whanau_family','whanau_community','whanau_digital','whanau_hobbies','whanau_group',
        'wairua_karakia','wairua_nature','wairua_culture','wairua_reflection','wairua_identity',
        'carer_battery','support_window','coverage_absence',
    ];

    while ($query->have_posts()) {
        $query->the_post();
        $id = get_the_ID();
        $row = [
            'id'               => $id,
            'date'             => get_the_date('Y-m-d'),
            'date_display'     => get_the_date('d/m'),
            'day'              => get_the_date('D'),
            'overall_rating'   => get_post_meta($id, 'overall_rating', true),
            'support_summary'  => get_post_meta($id, 'support_summary', true),
            'carer_start'      => get_post_meta($id, 'carer_start_time', true),
            'carer_end'        => get_post_meta($id, 'carer_end_time', true),
            'carer_duration'   => get_post_meta($id, 'carer_duration_minutes', true),
            'entry_maker'      => get_post_meta($id, 'entry_maker_name', true) ?: get_the_author_meta('display_name') ?: 'Unknown',
            'quick_notes'      => get_post_meta($id, 'quick_notes', true),
            'extra_notes'      => get_post_meta($id, 'extra_notes', true),
            'carer_notes'      => get_post_meta($id, 'carer_notes', true),
            'stability_score'  => get_post_meta($id, 'stability_score', true),
            'wellbeing_score'  => get_post_meta($id, 'wellbeing_score', true),
            'carer_score'      => get_post_meta($id, 'carer_score', true),
            'friction_index'   => get_post_meta($id, 'friction_index', true),
            'sustainability_alert' => get_post_meta($id, 'sustainability_alert', true),
        ];

        foreach ($all_fields as $f) {
            $row[$f] = get_post_meta($id, $f, true);
        }
        
        // V4.1: Support detail fields (for orange/red activities)
        $row['support_details'] = [];
        foreach ($all_fields as $f) {
            if (strpos($f, 'carer_') === false && strpos($f, 'support_') === false && strpos($f, 'coverage_') === false) {
                $type = get_post_meta($id, 'support_type_' . $f, true);
                $time = get_post_meta($id, 'support_time_' . $f, true);
                $equipment = get_post_meta($id, 'equipment_' . $f, true);
                
                if ($type || $time || $equipment) {
                    $row['support_details'][$f] = [
                        'type' => $type,
                        'time' => $time,
                        'equipment' => $equipment
                    ];
                }
            }
        }

        // Domain scores
        $domains = [
            'tinana'    => ['tinana_mealtime','tinana_hygiene','tinana_bathing','tinana_dressing','tinana_mobility'],
            'hinengaro' => ['hinengaro_memory','hinengaro_focus','hinengaro_comms','hinengaro_problem','hinengaro_household'],
            'whanau'    => ['whanau_family','whanau_community','whanau_digital','whanau_hobbies','whanau_group'],
            'wairua'    => ['wairua_karakia','wairua_nature','wairua_culture','wairua_reflection','wairua_identity'],
        ];

        foreach ($domains as $domain => $fields) {
            $total = 0; $count = 0;
            foreach ($fields as $f) {
                if (!empty($row[$f]) && isset($score_map[$row[$f]])) {
                    $total += $score_map[$row[$f]]; $count++;
                }
            }
            $row[$domain . '_score'] = $count > 0 ? round(($total / ($count * 2)) * 100) : null;
        }

        $entries[] = $row;
    }
    wp_reset_postdata();
    return $entries;
}

/**
 * Calculate aggregate stats from entries
 */
function mylog_pdf_calculate_stats($entries) {
    if (empty($entries)) return null;

    $total = count($entries);
    $red_days = 0; $difficult = 0; $sustainability_alerts = 0;
    $stability_sum = 0; $stability_count = 0;
    $carer_sum = 0; $carer_count = 0;
    $wellbeing_sum = 0; $wellbeing_count = 0;
    $domain_sums = ['tinana' => 0, 'hinengaro' => 0, 'whanau' => 0, 'wairua' => 0];
    $domain_counts = ['tinana' => 0, 'hinengaro' => 0, 'whanau' => 0, 'wairua' => 0];

    foreach ($entries as $e) {
        if ($e['overall_rating'] === 'red') $red_days++;
        if ($e['overall_rating'] === 'red' || $e['support_summary'] === 'red') $difficult++;
        if ($e['sustainability_alert']) $sustainability_alerts++;

        if ($e['stability_score'] !== '') { $stability_sum += $e['stability_score']; $stability_count++; }
        if ($e['carer_score'] !== '')     { $carer_sum     += $e['carer_score'];     $carer_count++; }
        if ($e['wellbeing_score'] !== '') { $wellbeing_sum += $e['wellbeing_score']; $wellbeing_count++; }

        foreach ($domain_sums as $domain => $_) {
            if ($e[$domain . '_score'] !== null) {
                $domain_sums[$domain]   += $e[$domain . '_score'];
                $domain_counts[$domain]++;
            }
        }
    }

    $domain_avgs = [];
    foreach ($domain_sums as $d => $sum) {
        $domain_avgs[$d] = $domain_counts[$d] > 0 ? round($sum / $domain_counts[$d]) : null;
    }

    return [
        'total'                => $total,
        'red_days'             => $red_days,
        'difficult_days'       => $difficult,
        'sustainability_alerts'=> $sustainability_alerts,
        'avg_stability'        => $stability_count > 0 ? round($stability_sum / $stability_count) : null,
        'avg_carer'            => $carer_count    > 0 ? round($carer_sum    / $carer_count)    : null,
        'avg_wellbeing'        => $wellbeing_count > 0 ? round($wellbeing_sum / $wellbeing_count) : null,
        'domain_avgs'          => $domain_avgs,
    ];
}

/**
 * Score to traffic light colour
 */
function mylog_score_color($score) {
    if ($score === null) return '#9ca3af';
    if ($score >= 70) return '#16a34a';
    if ($score >= 40) return '#f59e0b';
    return '#dc2626';
}

function mylog_value_color($val) {
    if ($val === 'green')  return '#16a34a';
    if ($val === 'orange') return '#f59e0b';
    if ($val === 'red')    return '#dc2626';
    return '#9ca3af';
}

function mylog_value_symbol($val) {
    $val = strtolower($val);
    if ($val === 'green')  return '<span style="color:#10b981;">●</span>';
    if ($val === 'orange') return '<span style="color:#f59e0b;">●</span>';
    if ($val === 'red')    return '<span style="color:#dc2626;">●</span>';
    return '<span style="color:#d1d5db;">○</span>';
}

/**
 * Build main report HTML
 */
function mylog_pdf_build_html($person_name, $person_dob, $from, $to, $entries, $stats, $prev_stats, $period_days) {

    $period_label = wp_date('j M Y', strtotime($from)) . ' – ' . wp_date('j M Y', strtotime($to));
    $total_days   = count($entries);
    $friction_status = '';
    $friction_color  = '#16a34a';

    if ($stats['sustainability_alerts'] > 0) {
        $friction_status = '⚠ SUSTAINABILITY ALERT';
        $friction_color  = '#dc2626';
    } elseif ($stats['avg_carer'] !== null && $stats['avg_carer'] < 50) {
        $friction_status = 'CARER STRAIN DETECTED';
        $friction_color  = '#f59e0b';
    } else {
        $friction_status = 'STABLE';
        $friction_color  = '#16a34a';
    }

    // Comparison arrows
    function cmp_arrow($curr, $prev) {
        if ($prev === null || $curr === null) return '';
        $diff = $curr - $prev;
        if ($diff > 3)  return ' <span style="color:#16a34a">▲' . abs($diff) . '%</span>';
        if ($diff < -3) return ' <span style="color:#dc2626">▼' . abs($diff) . '%</span>';
        return ' <span style="color:#9ca3af">→</span>';
    }

    $html = '<style>
        body { font-family: helvetica, sans-serif; font-size: 8pt; color: #1f2937; }
        h1   { font-size: 15pt; color: #1e40af; margin: 0 0 4px 0; }
        h2   { font-size: 10pt; color: #1e40af; margin: 12px 0 6px 0; border-bottom: 1px solid #bfdbfe; padding-bottom: 3px; }
        h3   { font-size: 9pt;  color: #374151; margin: 8px 0 4px 0; }
        table { border-collapse: collapse; width: 100%; font-size: 7.5pt; margin-bottom: 10px; }
        th   { background: #1e40af; color: white; padding: 5px 4px; text-align: left; font-size: 7pt; }
        td   { padding: 4px 4px; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
        tr.alert-row td { background: #fee2e2 !important; }
        tr.orange-row td { background: #fffbeb; }
        tr:nth-child(even) td { background: #f9fafb; }
        .score-box { display: inline-block; padding: 2px 6px; border-radius: 4px; font-weight: bold; color: white; font-size: 7pt; }
        .stat-grid { width: 100%; margin-bottom: 10px; }
        .stat-cell { padding: 8px; text-align: center; border-radius: 6px; }
        .domain-bar { height: 8px; border-radius: 4px; display: inline-block; }
        .alert-box { border: 2px solid #dc2626; background: #fee2e2; padding: 8px 12px; border-radius: 6px; margin: 8px 0; }
        .info-strip { background: #f0f9ff; border: 1px solid #bfdbfe; padding: 6px 10px; border-radius: 6px; margin-bottom: 10px; font-size: 7.5pt; }
    </style>';

    // ── PAGE 1 ─────────────────────────────────────────────────────

    // Header
    $html .= '<table style="margin-bottom:8px;border:none;"><tr>';
    $html .= '<td style="border:none;width:70%;vertical-align:top;">';
    $html .= '<h1>Daily Support Log</h1>';
    $html .= '<div style="font-size:8pt;color:#374151;">Person Supported: <strong>' . esc_html($person_name) . '</strong>';
    if ($person_dob) $html .= ' &nbsp;|&nbsp; DOB: ' . date('d/m/Y', strtotime($person_dob));
    $html .= '<br>Report Period: <strong>' . $period_label . '</strong> &nbsp;|&nbsp; Total Days Logged: <strong>' . $total_days . '</strong>';
    $html .= '<br>Generated: ' . wp_date('d/m/Y \a\t g:ia') . ' &nbsp;|&nbsp; www.mylog.co.nz</div>';
    $html .= '</td>';
    $html .= '<td style="border:none;width:30%;text-align:right;vertical-align:top;">';
    $html .= '<div style="background:' . $friction_color . ';color:white;padding:10px 14px;border-radius:8px;font-weight:bold;font-size:9pt;text-align:center;">';
    $html .= 'SYSTEM STATUS<br><span style="font-size:11pt;">' . $friction_status . '</span>';
    $html .= '</div>';
    $html .= '</td></tr></table>';

    // Sustainability alert box
    if ($stats['sustainability_alerts'] > 0) {
        $html .= '<div class="alert-box"><strong>⚠ SUSTAINABILITY ALERT:</strong> ';
        $html .= 'High-functioning care is being maintained at the cost of Carer Wellbeing. ';
        $html .= 'Carer support or respite should be considered urgently. ';
        $html .= '(' . $stats['sustainability_alerts'] . ' of ' . $total_days . ' days flagged)';
        $html .= '</div>';
    }

    // Summary stats grid
    $html .= '<h2>Summary Statistics — At a Glance</h2>';
    $html .= '<table style="border:none;" class="stat-grid"><tr>';

    $stat_items = [
        ['label' => 'Days Logged',      'value' => $total_days,                    'color' => '#1e40af'],
        ['label' => 'High Need Days',   'value' => $stats['red_days'],             'color' => $stats['red_days'] > 3 ? '#dc2626' : '#16a34a'],
        ['label' => 'Stability Score',  'value' => ($stats['avg_stability'] ?? '—') . ($stats['avg_stability'] ? '%' : ''), 'color' => mylog_score_color($stats['avg_stability'])],
        ['label' => 'Wellbeing Score',  'value' => ($stats['avg_wellbeing'] ?? '—') . ($stats['avg_wellbeing'] ? '%' : ''), 'color' => mylog_score_color($stats['avg_wellbeing'])],
        ['label' => 'Carer Score',      'value' => ($stats['avg_carer'] ?? '—') . ($stats['avg_carer'] ? '%' : ''),         'color' => mylog_score_color($stats['avg_carer'])],
        ['label' => 'Sustainability',   'value' => $stats['sustainability_alerts'] . ' alerts',                              'color' => $stats['sustainability_alerts'] > 0 ? '#dc2626' : '#16a34a'],
    ];

    foreach ($stat_items as $item) {
        $html .= '<td style="border:none;width:16.6%;padding:4px;">';
        $html .= '<div class="stat-cell" style="background:' . $item['color'] . '20;border:2px solid ' . $item['color'] . ';">';
        $html .= '<div style="font-size:14pt;font-weight:800;color:' . $item['color'] . ';">' . $item['value'] . '</div>';
        $html .= '<div style="font-size:6.5pt;color:#374151;">' . $item['label'] . '</div>';
        $html .= '</div></td>';
    }
    $html .= '</tr></table>';

    // Domain scores + comparison
    $html .= '<h2>Domain Scores — Current Period vs Previous Period</h2>';
    $html .= '<table style="border:none;"><tr>';

    $domains = [
        'tinana'    => ['label' => 'Taha Tinana · Physical',   'icon' => '💪'],
        'hinengaro' => ['label' => 'Taha Hinengaro · Mind',    'icon' => '🧠'],
        'whanau'    => ['label' => 'Taha Whānau · Social',     'icon' => '👥'],
        'wairua'    => ['label' => 'Taha Wairua · Spiritual',  'icon' => '✨'],
    ];

    foreach ($domains as $key => $info) {
        $curr_score = $stats['domain_avgs'][$key] ?? null;
        $prev_score = $prev_stats ? ($prev_stats['domain_avgs'][$key] ?? null) : null;
        $color      = mylog_score_color($curr_score);
        $arrow      = cmp_arrow($curr_score, $prev_score);

        $html .= '<td style="border:none;width:25%;padding:4px;">';
        $html .= '<div style="background:#f9fafb;border:2px solid ' . $color . ';border-radius:8px;padding:8px;text-align:center;">';
        $html .= '<div style="font-size:13pt;">' . $info['icon'] . '</div>';
        $html .= '<div style="font-size:7pt;color:#374151;font-weight:600;">' . $info['label'] . '</div>';
        $html .= '<div style="font-size:14pt;font-weight:800;color:' . $color . ';">';
        $html .= $curr_score !== null ? $curr_score . '%' : '—';
        $html .= '</div>';
        if ($prev_score !== null) {
            $html .= '<div style="font-size:6.5pt;color:#6b7280;">Prev: ' . $prev_score . '%' . $arrow . '</div>';
        }
        $html .= '</div></td>';
    }
    $html .= '</tr></table>';

    // Taha Hinengaro alert
    $hinge_reds = 0;
    $recent = array_slice(array_reverse($entries), 0, 10);
    foreach ($recent as $e) {
        $hinge_fields = ['hinengaro_memory','hinengaro_focus','hinengaro_comms','hinengaro_problem','hinengaro_household'];
        foreach ($hinge_fields as $f) {
            if ($e[$f] === 'red') { $hinge_reds++; break; }
        }
    }
    if ($hinge_reds >= 5) {
        $html .= '<div class="alert-box"><strong>⚠ COGNITIVE CONCERN:</strong> ';
        $html .= $hinge_reds . ' of the last 10 entries show high-need ratings in Taha Hinengaro (Mind & Memory). This pattern warrants attention.';
        $html .= '</div>';
    }

    // ── DAILY LOG TABLE ────────────────────────────────────────────
    $html .= '<h2>Daily Log — Full Period</h2>';
    $html .= '<table cellpadding="3" cellspacing="0">';
    $html .= '<thead><tr>';
    $html .= '<th width="6%">Date</th>';
    $html .= '<th width="4%">Day</th>';
    $html .= '<th width="7%">Overall</th>';
    $html .= '<th width="7%">Support</th>';
    $html .= '<th width="10%">Tinana</th>';
    $html .= '<th width="10%">Hinengaro</th>';
    $html .= '<th width="10%">Whānau</th>';
    $html .= '<th width="10%">Wairua</th>';
    $html .= '<th width="9%">Carer</th>';
    $html .= '<th width="8%">Shift</th>';
    $html .= '<th width="7%">Score</th>';
    $html .= '<th width="12%">Logged By</th>';
    $html .= '</tr></thead><tbody>';

    foreach ($entries as $e) {
        $is_red    = ($e['overall_rating'] === 'red' || $e['support_summary'] === 'red');
        $is_orange = ($e['overall_rating'] === 'orange' || $e['support_summary'] === 'orange');
        $row_class = $is_red ? 'alert-row' : ($is_orange ? 'orange-row' : '');

        $shift = '';
        if ($e['carer_start'] && $e['carer_end']) {
            $shift = $e['carer_start'] . '–' . $e['carer_end'];
            if ($e['carer_duration']) $shift .= ' (' . round($e['carer_duration'] / 60, 1) . 'h)';
        }

        // Domain mini-scores
        $d_scores = '';
        foreach (['tinana','hinengaro','whanau','wairua'] as $d) {
            $s = $e[$d . '_score'];
            $c = mylog_score_color($s);
            $d_scores_arr[$d] = $s !== null
                ? '<span class="score-box" style="background:' . $c . ';">' . $s . '%</span>'
                : '<span style="color:#9ca3af;">—</span>';
        }

        $overall_color  = mylog_value_color($e['overall_rating']);
        $support_color  = mylog_value_color($e['support_summary']);
        $carer_color    = mylog_value_color($e['carer_battery']);
        $score_color    = mylog_score_color($e['stability_score']);

        $html .= '<tr class="' . $row_class . '">';
        $html .= '<td><strong>' . $e['date_display'] . '</strong></td>';
        $html .= '<td>' . $e['day'] . '</td>';
        $html .= '<td style="color:' . $overall_color . ';font-weight:bold;">' . mylog_value_symbol($e['overall_rating']) . '</td>';
        $html .= '<td style="color:' . $support_color . ';font-weight:bold;">' . mylog_value_symbol($e['support_summary']) . '</td>';
        $html .= '<td>' . ($d_scores_arr['tinana']    ?? '—') . '</td>';
        $html .= '<td>' . ($d_scores_arr['hinengaro'] ?? '—') . '</td>';
        $html .= '<td>' . ($d_scores_arr['whanau']    ?? '—') . '</td>';
        $html .= '<td>' . ($d_scores_arr['wairua']    ?? '—') . '</td>';
        $html .= '<td style="color:' . $carer_color . ';">' . mylog_value_symbol($e['carer_battery']) . '</td>';
        $html .= '<td style="font-size:6.5pt;">' . esc_html($shift) . '</td>';
        $html .= '<td><span class="score-box" style="background:' . $score_color . ';">' . ($e['stability_score'] ?? '—') . ($e['stability_score'] ? '%' : '') . '</span></td>';
        $html .= '<td style="font-size:6.5pt;">' . esc_html($e['entry_maker']) . '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';

    // Legend
    $html .= '<div style="background:#f9fafb;padding:6px 10px;border-radius:6px;font-size:6.5pt;margin-top:6px;">';
    $html .= '<strong>Legend:</strong> &nbsp;';
    $html .= '🟢 Good/Independent &nbsp; 🟡 Moderate/Some Difficulty &nbsp; 🔴 High Need/Incident &nbsp;&nbsp;';
    $html .= '<span style="background:#dc2626;color:white;padding:1px 5px;border-radius:3px;font-weight:bold;">Red row</span> = High need day &nbsp;';
    $html .= 'Scores: 70%+ Good · 40-69% Moderate · &lt;40% Concern';
    $html .= '</div>';

    return $html;
}

/**
 * Build notes/detailed entries page
 */
function mylog_pdf_build_notes_page($entries, $person_name) {
    $html = '<style>
        h2 { font-size: 11pt; color: #007cba; margin-bottom: 8px; }
        .note-block { background: #f9fafb; border-left: 2px solid #3b82f6; padding: 4px 6px; margin-bottom: 4px; font-size: 7pt; }
        .note-date { font-weight: bold; color: #1e40af; font-size: 8pt; }
        .note-label { font-weight: bold; color: #374151; }
    </style>';

    $html .= '<h2>APPENDIX: DETAILED NOTES & OBSERVATIONS</h2>';
    $html .= '<div style="font-size:7pt;color:#6b7280;margin-bottom:8px;">Chronological notes from caregivers - newest to oldest</div>';
    
    // Sort newest first
    $sorted = $entries;
    usort($sorted, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    // Filter entries with notes
    $with_notes = array_filter($sorted, function($e) {
        return !empty($e['quick_notes']) || !empty($e['extra_notes']) || !empty($e['carer_notes']);
    });

    // Build 2-column layout
    $html .= '<table style="width:100%;"><tr>';
    $html .= '<td style="width:48%;vertical-align:top;border:none;">';
    
    $count = 0;
    $half = ceil(count($with_notes) / 2);
    
    foreach ($with_notes as $e) {
        // Switch to second column
        if ($count == $half) {
            $html .= '</td><td style="width:4%;border:none;"></td><td style="width:48%;vertical-align:top;border:none;">';
        }

        $html .= '<div class="note-block">';
        $html .= '<div class="note-date">' . date('D j M Y', strtotime($e['date'])) . '</div>';

        if ($e['quick_notes']) {
            $html .= '<div style="margin-top:2px;"><span class="note-label">Daily Notes:</span> ' . esc_html(substr($e['quick_notes'], 0, 150)) . '</div>';
        }
        if ($e['extra_notes']) {
            $html .= '<div style="margin-top:2px;"><span class="note-label">Additional:</span> ' . esc_html(substr($e['extra_notes'], 0, 100)) . '</div>';
        }
        if ($e['carer_notes']) {
            $html .= '<div style="margin-top:2px;"><span class="note-label">Carer Context:</span> ' . esc_html(substr($e['carer_notes'], 0, 100)) . '</div>';
        }

        $html .= '</div>';
        $count++;
    }

    $html .= '</td></tr></table>';

    return $html;
}
/**
 * ========================================================================
 * V4.1 ENHANCEMENT: NEW PAGE BUILDERS FOR PROFESSIONAL REPORTS
 * ========================================================================
 */

/**
 * Build Executive Summary — PAGE 1
 * Contains: Header, Legend, Goals box, Key Findings, Overall Trends, Carer Sustainability, Data Coverage, Disclaimer
 */
function mylog_pdf_build_executive_summary_page1($person_name, $person_dob, $from, $to, $entries, $stats, $prev_stats, $person_goals = '') {
    $period_label = wp_date('j M Y', strtotime($from)) . ' – ' . wp_date('j M Y', strtotime($to));
    $generated = wp_date('j M Y');
    
    // Logo and header
    $html = '<style>
        body { font-family: dejavusans, sans-serif; font-size: 9pt; color: #1f2937; line-height: 1.3; }
        h1 { font-size: 15pt; color: #007cba; margin: 0 0 5px 0; }
        h2 { font-size: 10pt; color: #007cba; margin: 0 0 5px 0; font-weight: bold; border-bottom: 1px solid #007cba; padding-bottom: 2px; }
        table { border-collapse: collapse; }
        .grid-cell { vertical-align: top; padding: 6px; border: 1px solid #bfdbfe; background: #f9fafb; }
        .finding-line { margin: 2px 0; font-size: 8pt; line-height: 1.3; }
        .stat-line { margin: 2px 0; font-size: 8pt; line-height: 1.3; }
        .legend { background: #fffbeb; border: 1px solid #fbbf24; padding: 4px; font-size: 7pt; margin: 5px 0; }
        .disclaimer { background: #fef2f2; border: 1px solid #fecaca; padding: 6px; font-size: 7pt; margin-top: 5px; }
    </style>';
    
    // Header with logo (left) and details (right)
    $logo_url = 'https://mymate.co.nz/mylog/wp-content/uploads/2026/01/MyMate-MyLog-Logo.png';
    $html .= '<table style="width:100%;margin-bottom:8px;border:none;"><tr>';
    $html .= '<td style="width:35%;vertical-align:top;border:none;">';
    $html .= '<img src="' . $logo_url . '" width="160" height="64" />';
    $html .= '</td>';
    $html .= '<td style="width:65%;vertical-align:top;text-align:right;border:none;">';
    $html .= '<h1>MYLOG SUMMARY REPORT</h1>';
    $html .= '<div style="font-size:9pt;color:#374151;">';
    $html .= '<strong>Person:</strong> ' . esc_html($person_name) . ' | ';
    $html .= '<strong>Period:</strong> ' . $period_label . ' | ';
    $html .= '<strong>Generated:</strong> ' . $generated;
    $html .= '</div></td></tr></table>';
    
    // === LEGEND (renamed, full width) ===
    $html .= '<div class="legend">';
    $html .= '<strong>LEGEND USED THROUGHOUT THIS SUMMARY:</strong> &nbsp;';
    $html .= '<span style="color:#10b981;font-weight:bold;">●</span> Independent (Green) &nbsp;|&nbsp; ';
    $html .= '<span style="color:#f59e0b;font-weight:bold;">●</span> Some Support (Orange) &nbsp;|&nbsp; ';
    $html .= '<span style="color:#dc2626;font-weight:bold;">●</span> Full Support (Red)';
    $html .= '</div>';
    
    // === PERSON-CENTERED GOALS (always shown, full width) ===
    $first_name = trim(explode(' ', $person_name)[0]);
    $goals_title = esc_html($first_name) . '\'s Goals &amp; Aspirations';
    if (!empty($person_goals)) {
        $html .= '<div style="border:1px solid #6366f1;padding:6px 10px;margin:3px 0;background:#f5f3ff;">';
        $html .= '<h2 style="margin:0 0 3px 0;color:#6366f1;border-bottom:1px solid #6366f1;">' . $goals_title . '</h2>';
        $html .= '<div style="font-size:7pt;color:#6b7280;margin-bottom:3px;">Goals expressed by the person or their whānau — as recorded on the Add Person form</div>';
        $html .= '<div style="font-size:8pt;color:#1f2937;">' . nl2br(esc_html($person_goals)) . '</div>';
        $html .= '</div>';
    } else {
        $html .= '<div style="border:1px solid #6366f1;padding:6px 10px;margin:3px 0;background:#f5f3ff;">';
        $html .= '<h2 style="margin:0 0 3px 0;color:#6366f1;border-bottom:1px solid #6366f1;">' . $goals_title . '</h2>';
        $html .= '<div style="font-size:8pt;color:#9ca3af;font-style:italic;">Not Entered — to be recorded on the Add Person form</div>';
        $html .= '</div>';
    }
    
    // === PAGE 1: ROW 1 — Key Findings | Overall Trends ===
    $html .= '<table style="width:100%;margin-bottom:3px;"><tr>';
    
    // LEFT: Key Findings
    $html .= '<td class="grid-cell" style="width:48%;">';
    $html .= '<h2>KEY FINDINGS</h2>';
    $findings = mylog_pdf_generate_key_findings($stats, $entries);
    $finding_count = 0;
    foreach ($findings as $finding) {
        if ($finding_count >= 5) break;
        $icon_color = $finding['priority'] === 'critical' ? '#dc2626' : ($finding['priority'] === 'moderate' ? '#f59e0b' : '#10b981');
        $html .= '<div class="finding-line"><span style="color:' . $icon_color . ';font-weight:bold;">●</span> ' . esc_html($finding['text']) . '</div>';
        $finding_count++;
    }
    if (empty($findings)) {
        $html .= '<div class="finding-line" style="color:#9ca3af;">No critical findings identified</div>';
    }
    $html .= '</td>';
    
    $html .= '<td style="width:4%;border:none;"></td>';
    
    // RIGHT: Overall Trends
    $html .= '<td class="grid-cell" style="width:48%;">';
    $html .= '<h2>OVERALL TRENDS</h2>';
    
    $total_entries = count($entries);
    $overall_green  = isset($stats['overall_day_green'])  ? $stats['overall_day_green']  : 0;
    $overall_orange = isset($stats['overall_day_orange']) ? $stats['overall_day_orange'] : 0;
    $overall_red    = isset($stats['overall_day_red'])    ? $stats['overall_day_red']    : 0;
    
    $green_pct  = $total_entries > 0 ? round(($overall_green  / $total_entries) * 100) : 0;
    $orange_pct = $total_entries > 0 ? round(($overall_orange / $total_entries) * 100) : 0;
    $red_pct    = $total_entries > 0 ? round(($overall_red    / $total_entries) * 100) : 0;
    
    $html .= '<div style="font-size:8pt;margin-bottom:2px;"><strong>Overall Day:</strong> Shows daily independence</div>';
    $html .= mylog_pdf_draw_bar('Good',      $green_pct,  '#10b981');
    $html .= mylog_pdf_draw_bar('Moderate',  $orange_pct, '#f59e0b');
    $html .= mylog_pdf_draw_bar('High Need', $red_pct,    '#dc2626');
    
    $support_green_pct  = $total_entries > 0 ? round(($stats['support_level_green']  / $total_entries) * 100) : 0;
    $support_orange_pct = $total_entries > 0 ? round(($stats['support_level_orange'] / $total_entries) * 100) : 0;
    $support_red_pct    = $total_entries > 0 ? round(($stats['support_level_red']    / $total_entries) * 100) : 0;
    
    $html .= '<div style="font-size:8pt;margin:4px 0 2px 0;"><strong>Support Level:</strong> Hours/equipment needed</div>';
    $html .= mylog_pdf_draw_bar('Good',      $support_green_pct,  '#10b981');
    $html .= mylog_pdf_draw_bar('Moderate',  $support_orange_pct, '#f59e0b');
    $html .= mylog_pdf_draw_bar('High Need', $support_red_pct,    '#dc2626');
    
    $html .= '</td>';
    $html .= '</tr></table>';
    
    // === PAGE 1: ROW 2 — Carer Sustainability | Data Coverage ===
    $html .= '<table style="width:100%;margin-bottom:3px;"><tr>';
    
    // LEFT: Carer Sustainability
    $html .= '<td class="grid-cell" style="width:48%;">';
    $html .= '<h2>CARER SUSTAINABILITY</h2>';
    $sustainability = mylog_pdf_calculate_carer_sustainability($stats, $entries);
    
    $score_color = $sustainability['score'] >= 70 ? '#10b981' : ($sustainability['score'] >= 50 ? '#f59e0b' : '#dc2626');
    $html .= '<div style="font-size:9pt;margin-bottom:2px;"><strong style="color:' . $score_color . ';">Score: ' . $sustainability['score'] . '%</strong> (' . $sustainability['status'] . ')</div>';
    $html .= '<div style="font-size:7pt;color:#6b7280;margin-bottom:3px;">Burnout risk indicator (70%+ = healthy)</div>';
    $html .= '<div class="stat-line">Battery: <strong>' . $sustainability['battery_green'] . '%</strong> green</div>';
    $html .= '<div class="stat-line">Max consecutive: <strong>' . $sustainability['max_consecutive'] . '</strong> days</div>';
    $html .= '<div class="stat-line">Coverage: <strong>' . $sustainability['coverage_adequate'] . '%</strong></div>';
    $html .= '</td>';
    
    $html .= '<td style="width:4%;border:none;"></td>';
    
    // RIGHT: Data Coverage
    $html .= '<td class="grid-cell" style="width:48%;">';
    $html .= '<h2>DATA COVERAGE</h2>';
    $unique_days = count(array_unique(array_column($entries, 'date')));
    $html .= '<div style="font-size:8pt;margin-bottom:2px;"><strong>Overall:</strong> ' . $stats['coverage_percent'] . '% (logged ' . $unique_days . ' of ' . $stats['date_range_days'] . ' days)</div>';
    $html .= '<div style="font-size:7pt;color:#6b7280;margin-bottom:3px;">Completeness of activity logging per domain</div>';
    $html .= '<div class="stat-line"><strong>Taha Tinana (Physical):</strong> ' . $stats['domain_completeness_tinana'] . '%</div>';
    $html .= '<div class="stat-line"><strong>Taha Hinengaro (Mental):</strong> ' . $stats['domain_completeness_hinengaro'] . '%</div>';
    $html .= '<div class="stat-line"><strong>Taha Whānau (Social):</strong> ' . $stats['domain_completeness_whanau'] . '%</div>';
    $html .= '<div class="stat-line"><strong>Taha Wairua (Spiritual):</strong> ' . $stats['domain_completeness_wairua'] . '%</div>';
    $html .= '</td>';
    $html .= '</tr></table>';
    
    // === DISCLAIMER ===
    $html .= '<div class="disclaimer">';
    $html .= '<strong>DISCLAIMER:</strong> This MyLog report reflects goals and activity logs entered by the user, caregivers and family. ';
    $html .= 'Not to be deemed as a clinical assessment. All data verifiable at www.mylog.co.nz';
    $html .= '</div>';
    
    return $html;
}

/**
 * Build Executive Summary — PAGE 2
 * Contains: Wellbeing Snapshot (Te Whare Tapa Whā), Functional Capacity Scores, Risk Assessment
 */
function mylog_pdf_build_executive_summary_page2($person_name, $entries, $stats) {
    $html = '<style>
        body { font-family: dejavusans, sans-serif; font-size: 9pt; color: #1f2937; line-height: 1.3; }
        h2 { font-size: 10pt; color: #007cba; margin: 0 0 5px 0; font-weight: bold; border-bottom: 1px solid #007cba; padding-bottom: 2px; }
        table { border-collapse: collapse; }
        .grid-cell { vertical-align: top; padding: 6px; border: 1px solid #bfdbfe; background: #f9fafb; }
    </style>';
    
    // === WELLBEING SNAPSHOT (4 mini domain boxes) ===
    $html .= '<div style="border:1px solid #007cba;padding:5px;margin-bottom:6px;background:#f0f9ff;">';
    $html .= '<h2 style="margin:0 0 4px 0;">WELLBEING SNAPSHOT — TE WHARE TAPA WHĀ</h2>';
    $html .= '<div style="font-size:7pt;color:#6b7280;margin-bottom:4px;">Holistic health across four domains (NZ Ministry of Health framework)</div>';
    
    $html .= '<table style="width:100%;"><tr>';
    $domains = [
        'tinana'    => ['name' => 'Tinana',    'full' => 'Physical',  'color' => '#16a34a'],
        'hinengaro' => ['name' => 'Hinengaro', 'full' => 'Mental',    'color' => '#0284c7'],
        'whanau'    => ['name' => 'Whānau',    'full' => 'Social',    'color' => '#f59e0b'],
        'wairua'    => ['name' => 'Wairua',    'full' => 'Spiritual', 'color' => '#9333ea'],
    ];
    
    $count = 0;
    foreach ($domains as $key => $domain) {
        if ($count > 0) $html .= '<td style="width:2%;border:none;"></td>';
        $html .= '<td style="width:23%;vertical-align:top;padding:4px;border:1px solid ' . $domain['color'] . ';text-align:center;">';
        $html .= '<div style="font-weight:bold;font-size:8pt;color:' . $domain['color'] . ';">' . $domain['name'] . '</div>';
        $html .= '<div style="font-size:6pt;color:#6b7280;">' . $domain['full'] . '</div>';
        $activities = mylog_pdf_get_domain_activities($key);
        $green = 0; $total = 0;
        foreach ($activities as $activity) {
            $field = $key . '_' . $activity;
            if (isset($stats['activities'][$field])) {
                $green += $stats['activities'][$field]['green'];
                $total += $stats['activities'][$field]['green'] + $stats['activities'][$field]['orange'] + $stats['activities'][$field]['red'];
            }
        }
        $pct = $total > 0 ? round(($green / $total) * 100) : 0;
        $html .= '<div style="font-size:14pt;font-weight:bold;color:' . $domain['color'] . ';margin:2px 0;">' . $pct . '%</div>';
        $html .= '<div style="font-size:6pt;">independent</div>';
        $html .= '</td>';
        $count++;
    }
    $html .= '</tr></table>';
    $html .= '</div>';
    
    // === FUNCTIONAL CAPACITY SCORES | RISK ASSESSMENT (side by side) ===
    $html .= '<table style="width:100%;margin-bottom:3px;"><tr>';
    
    // LEFT: Functional Capacity
    $html .= '<td class="grid-cell" style="width:48%;">';
    $html .= '<h2>FUNCTIONAL CAPACITY SCORES</h2>';
    $html .= '<div style="font-size:7pt;color:#6b7280;margin-bottom:4px;">Based on logged independence across each domain</div>';
    
    $capacity_domains = [
        'tinana'    => ['label' => 'Physical (Tinana)',   'color' => '#16a34a'],
        'hinengaro' => ['label' => 'Mental (Hinengaro)',  'color' => '#0284c7'],
        'whanau'    => ['label' => 'Social (Whānau)',     'color' => '#f59e0b'],
        'wairua'    => ['label' => 'Spiritual (Wairua)',  'color' => '#9333ea'],
    ];
    
    $html .= '<table style="width:100%;font-size:7.5pt;"><thead><tr>';
    $html .= '<th style="text-align:left;background:#374151;color:white;padding:3px 5px;">Domain</th>';
    $html .= '<th style="background:#374151;color:white;padding:3px 5px;">Independence</th>';
    $html .= '<th style="background:#374151;color:white;padding:3px 5px;">Classification</th>';
    $html .= '</tr></thead><tbody>';
    
    foreach ($capacity_domains as $key => $info) {
        $activities = mylog_pdf_get_domain_activities($key);
        $green = 0; $total = 0;
        foreach ($activities as $act) {
            $field = $key . '_' . $act;
            if (isset($stats['activities'][$field])) {
                $green += $stats['activities'][$field]['green'];
                $total += $stats['activities'][$field]['green'] + $stats['activities'][$field]['orange'] + $stats['activities'][$field]['red'];
            }
        }
        $pct = $total > 0 ? round(($green / $total) * 100) : 0;
        
        if ($pct >= 90)     { $class = 'Independent';           $class_color = '#16a34a'; }
        elseif ($pct >= 70) { $class = 'Modified Independence'; $class_color = '#16a34a'; }
        elseif ($pct >= 50) { $class = 'Minimal Assistance';    $class_color = '#f59e0b'; }
        elseif ($pct >= 30) { $class = 'Moderate Assistance';   $class_color = '#f59e0b'; }
        else                { $class = 'Maximum Assistance';    $class_color = '#dc2626'; }
        
        $html .= '<tr>';
        $html .= '<td style="padding:3px 5px;border:1px solid #e5e7eb;font-weight:bold;color:' . $info['color'] . ';">' . $info['label'] . '</td>';
        $html .= '<td style="padding:3px 5px;border:1px solid #e5e7eb;text-align:center;font-weight:bold;">' . $pct . '%</td>';
        $html .= '<td style="padding:3px 5px;border:1px solid #e5e7eb;color:' . $class_color . ';font-weight:bold;">' . $class . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    $html .= '<div style="font-size:6pt;color:#9ca3af;margin-top:3px;">90-100%: Independent · 70-89%: Modified Independence · 50-69%: Minimal Assistance · 30-49%: Moderate · &lt;30%: Maximum</div>';
    $html .= '</td>';
    
    $html .= '<td style="width:4%;border:none;"></td>';
    
    // RIGHT: Risk Assessment
    $html .= '<td class="grid-cell" style="width:48%;">';
    $html .= '<h2>RISK ASSESSMENT</h2>';
    $html .= '<div style="font-size:7pt;color:#6b7280;margin-bottom:4px;">Calculated from logged activity patterns</div>';
    
    // Carer Burnout Risk
    $sustainability = mylog_pdf_calculate_carer_sustainability($stats, $entries);
    $burnout_score = $sustainability['score'];
    if ($burnout_score >= 70)     { $burnout_level = 'LOW';      $burnout_color = '#16a34a'; }
    elseif ($burnout_score >= 50) { $burnout_level = 'MODERATE'; $burnout_color = '#f59e0b'; }
    else                          { $burnout_level = 'HIGH';     $burnout_color = '#dc2626'; }
    
    $mob_data     = $stats['activities']['tinana_mobility'] ?? ['green'=>0,'orange'=>0,'red'=>0];
    $bath_data    = $stats['activities']['tinana_bathing']  ?? ['green'=>0,'orange'=>0,'red'=>0];
    $mob_total    = $mob_data['green']  + $mob_data['orange']  + $mob_data['red'];
    $bath_total   = $bath_data['green'] + $bath_data['orange'] + $bath_data['red'];
    $mob_concern  = $mob_total  > 0 ? ($mob_data['orange']  + $mob_data['red'])  / $mob_total  : 0;
    $bath_concern = $bath_total > 0 ? ($bath_data['orange'] + $bath_data['red']) / $bath_total : 0;
    $falls_avg    = ($mob_concern + $bath_concern) / 2;
    if ($falls_avg > 0.3)      { $falls_level = 'MODERATE–HIGH'; $falls_color = '#dc2626'; }
    elseif ($falls_avg > 0.15) { $falls_level = 'LOW–MODERATE';  $falls_color = '#f59e0b'; }
    else                       { $falls_level = 'LOW';           $falls_color = '#16a34a'; }
    
    $critical_count = 0;
    foreach ($entries as $e) {
        foreach ($e as $k => $v) {
            if (!is_string($v)) continue;
            if (strpos($k, '_') !== false && strtolower($v) === 'red') { $critical_count++; break; }
        }
    }
    if ($critical_count >= 4)     { $safety_level = 'HIGH';     $safety_color = '#dc2626'; }
    elseif ($critical_count >= 2) { $safety_level = 'MODERATE'; $safety_color = '#f59e0b'; }
    else                          { $safety_level = 'LOW';      $safety_color = '#16a34a'; }
    
    $html .= '<table style="width:100%;font-size:7.5pt;"><thead><tr>';
    $html .= '<th style="text-align:left;background:#374151;color:white;padding:3px 5px;">Risk Factor</th>';
    $html .= '<th style="background:#374151;color:white;padding:3px 5px;">Level</th>';
    $html .= '<th style="text-align:left;background:#374151;color:white;padding:3px 5px;">Basis</th>';
    $html .= '</tr></thead><tbody>';
    
    $risks = [
        ['label' => 'Carer Burnout',  'level' => $burnout_level, 'color' => $burnout_color, 'basis' => 'Sustainability score: ' . $burnout_score . '%'],
        ['label' => 'Falls / Safety', 'level' => $falls_level,   'color' => $falls_color,   'basis' => 'Mobility & bathing: ' . round($falls_avg * 100) . '% non-green'],
        ['label' => 'Incident Risk',  'level' => $safety_level,  'color' => $safety_color,  'basis' => $critical_count . ' high-need day(s) in period'],
    ];
    
    foreach ($risks as $risk) {
        $html .= '<tr>';
        $html .= '<td style="padding:3px 5px;border:1px solid #e5e7eb;font-weight:bold;">' . $risk['label'] . '</td>';
        $html .= '<td style="padding:3px 5px;border:1px solid #e5e7eb;text-align:center;font-weight:bold;color:' . $risk['color'] . ';">' . $risk['level'] . '</td>';
        $html .= '<td style="padding:3px 5px;border:1px solid #e5e7eb;font-size:7pt;">' . $risk['basis'] . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    $html .= '</td>';
    $html .= '</tr></table>';
    
    return $html;
}

/**
 * Draw horizontal bar chart - TCPDF SAFE VERSION
 */
function mylog_pdf_draw_bar($label, $percentage, $color) {
    // Use simple HTML table for bars that TCPDF can actually render
    $filled_cells = round($percentage / 10); // 10 cells total
    $empty_cells = 10 - $filled_cells;
    
    $html = '<table style="width:100%;margin:2px 0;border:none;"><tr>';
    $html .= '<td style="width:70px;border:none;font-size:8pt;padding:2px;">' . $label . ':</td>';
    $html .= '<td style="border:none;padding:0;">';
    $html .= '<table style="width:100%;border:none;"><tr>';
    
    // Filled cells
    for ($i = 0; $i < $filled_cells; $i++) {
        $html .= '<td style="width:10%;background-color:' . $color . ';height:10px;border:1px solid ' . $color . ';padding:0;"></td>';
    }
    
    // Empty cells
    for ($i = 0; $i < $empty_cells; $i++) {
        $html .= '<td style="width:10%;background-color:#f3f4f6;height:10px;border:1px solid #e5e7eb;padding:0;"></td>';
    }
    
    $html .= '</tr></table></td>';
    $html .= '<td style="width:50px;border:none;text-align:right;font-size:8pt;padding:2px;"><strong>' . $percentage . '%</strong></td>';
    $html .= '</tr></table>';
    
    return $html;
}

/**
 * Draw mini inline bar - TCPDF SAFE VERSION
 */
function mylog_pdf_mini_bar($percentage, $color) {
    $filled = round($percentage / 10); // 10 cells
    $empty = 10 - $filled;
    
    $html = '<span style="display:inline-block;">';
    for ($i = 0; $i < $filled; $i++) {
        $html .= '<span style="display:inline-block;width:8px;height:8px;background-color:' . $color . ';border:1px solid ' . $color . ';margin:0 1px;"></span>';
    }
    for ($i = 0; $i < $empty; $i++) {
        $html .= '<span style="display:inline-block;width:8px;height:8px;background-color:#f3f4f6;border:1px solid #e5e7eb;margin:0 1px;"></span>';
    }
    $html .= '</span>';
    
    return $html;
}

/**
 * Generate key findings from data patterns
 */
function mylog_pdf_generate_key_findings($stats, $entries) {
    $findings = [];
    
    // Check for declining trends in each activity
    // For MVP, we'll focus on high-red activities
    foreach ($stats['activities'] as $activity => $data) {
        $total = $data['green'] + $data['orange'] + $data['red'];
        if ($total > 0) {
            $red_pct = ($data['red'] / $total) * 100;
            if ($red_pct > 40) {
                $name = ucwords(str_replace('_', ' ', $activity));
                $findings[] = [
                    'priority' => 'critical',
                    'text' => $name . ' requires high support in ' . round($red_pct) . '% of entries'
                ];
            }
        }
    }
    
    // Carer burnout risk
    $battery_total = $stats['carer']['battery']['green'] + $stats['carer']['battery']['orange'] + $stats['carer']['battery']['red'];
    if ($battery_total > 0) {
        $red_pct = ($stats['carer']['battery']['red'] / $battery_total) * 100;
        $orange_pct = ($stats['carer']['battery']['orange'] / $battery_total) * 100;
        if ($red_pct > 20) {
            $findings[] = [
                'priority' => 'critical',
                'text' => 'Carer burnout risk: Battery red in ' . round($red_pct) . '% of entries'
            ];
        } elseif ($orange_pct > 50) {
            $findings[] = [
                'priority' => 'moderate',
                'text' => 'Carer strain detected: Battery orange/red in ' . round($orange_pct + $red_pct) . '% of entries'
            ];
        }
    }
    
    // Positive trends
    foreach ($stats['activities'] as $activity => $data) {
        $total = $data['green'] + $data['orange'] + $data['red'];
        if ($total > 0) {
            $green_pct = ($data['green'] / $total) * 100;
            if ($green_pct > 90) {
                $name = ucwords(str_replace('_', ' ', $activity));
                $findings[] = [
                    'priority' => 'positive',
                    'text' => $name . ' performing well (green in ' . round($green_pct) . '% of entries)'
                ];
            }
        }
    }
    
    // Limit to most important findings
    usort($findings, function($a, $b) {
        $priority_order = ['critical' => 0, 'moderate' => 1, 'positive' => 2];
        return $priority_order[$a['priority']] - $priority_order[$b['priority']];
    });
    
    return array_slice($findings, 0, 10);
}

/**
 * Calculate carer sustainability metrics
 */
function mylog_pdf_calculate_carer_sustainability($stats, $entries) {
    $battery_total = $stats['carer']['battery']['green'] + $stats['carer']['battery']['orange'] + $stats['carer']['battery']['red'];
    
    if ($battery_total == 0) {
        return [
            'score' => 0,
            'status' => 'No Data',
            'battery_green' => 0,
            'battery_orange' => 0,
            'battery_red' => 0,
            'max_consecutive' => 0,
            'coverage_adequate' => 0
        ];
    }
    
    $battery_green_pct = round(($stats['carer']['battery']['green'] / $battery_total) * 100);
    $battery_orange_pct = round(($stats['carer']['battery']['orange'] / $battery_total) * 100);
    $battery_red_pct = round(($stats['carer']['battery']['red'] / $battery_total) * 100);
    
    // Calculate base score from battery status
    $battery_score = ($battery_green_pct * 0.9) + ($battery_orange_pct * 0.5) + ($battery_red_pct * 0.1);
    
    // Penalty for no respite
    $max_consecutive = $stats['carer']['max_consecutive_days'];
    $respite_penalty = max(0, min(20, ($max_consecutive - 7) * 2));
    
    // Coverage bonus
    $coverage_total = $stats['carer']['coverage']['green'] + $stats['carer']['coverage']['orange'] + $stats['carer']['coverage']['red'];
    $coverage_adequate_pct = $coverage_total > 0 ? round(($stats['carer']['coverage']['green'] / $coverage_total) * 100) : 0;
    $coverage_bonus = ($coverage_adequate_pct / 100) * 18;
    
    $final_score = min(100, max(0, round($battery_score - $respite_penalty + $coverage_bonus)));
    
    $status = 'Good';
    if ($final_score < 50) $status = 'High Risk';
    elseif ($final_score < 70) $status = 'Moderate Risk';
    
    return [
        'score' => $final_score,
        'status' => $status,
        'battery_green' => $battery_green_pct,
        'battery_orange' => $battery_orange_pct,
        'battery_red' => $battery_red_pct,
        'max_consecutive' => $max_consecutive,
        'coverage_adequate' => $coverage_adequate_pct
    ];
}


/**
 * Build Domain Graphs Page (Text-based summary for TCPDF)
 */
function mylog_pdf_build_domain_graphs($stats, $entries, $from, $to) {
    $html = '<style>
        h1 { font-size: 13pt; color: #007cba; text-align: center; margin-bottom: 10px; font-weight: bold; }
    </style>';
    
    $html .= '<h1>DOMAIN TRENDS - TE WHARE TAPA WHĀ</h1>';
    
    $domains = [
        'tinana' => ['name' => 'Taha Tinana | Physical Wellbeing', 'color' => '#16a34a'],
        'hinengaro' => ['name' => 'Taha Hinengaro | Mental & Emotional', 'color' => '#0284c7'],
        'whanau' => ['name' => 'Taha Whānau | Social & Connection', 'color' => '#f59e0b'],
        'wairua' => ['name' => 'Taha Wairua | Spiritual & Personal', 'color' => '#9333ea']
    ];
    
    // First row: Tinana | Hinengaro
    $html .= '<table style="width:100%;margin-bottom:5px;"><tr>';
    
    $count = 0;
    foreach ($domains as $key => $domain) {
        if ($count == 2) {
            $html .= '</tr></table><table style="width:100%;"><tr>'; // New row
        }
        
        if ($count % 2 == 1) {
            $html .= '<td style="width:4%;border:none;"></td>'; // Spacer
        }
        
        $html .= '<td style="width:48%;vertical-align:top;padding:8px;border:2px solid ' . $domain['color'] . ';">';
        $html .= '<div style="font-weight:bold;font-size:10pt;color:' . $domain['color'] . ';margin-bottom:6px;text-align:center;">' . $domain['name'] . '</div>';
        
        // Calculate domain statistics
        $activities = mylog_pdf_get_domain_activities($key);
        $domain_stats = ['green' => 0, 'orange' => 0, 'red' => 0, 'total' => 0];
        
        foreach ($activities as $activity) {
            $field = $key . '_' . $activity;
            if (isset($stats['activities'][$field])) {
                $domain_stats['green'] += $stats['activities'][$field]['green'];
                $domain_stats['orange'] += $stats['activities'][$field]['orange'];
                $domain_stats['red'] += $stats['activities'][$field]['red'];
                $domain_stats['total'] += $stats['activities'][$field]['green'] + $stats['activities'][$field]['orange'] + $stats['activities'][$field]['red'];
            }
        }
        
        if ($domain_stats['total'] > 0) {
            $green_pct = round(($domain_stats['green'] / $domain_stats['total']) * 100);
            $orange_pct = round(($domain_stats['orange'] / $domain_stats['total']) * 100);
            $red_pct = round(($domain_stats['red'] / $domain_stats['total']) * 100);
            
            $html .= mylog_pdf_draw_bar('Good', $green_pct, '#10b981');
            $html .= mylog_pdf_draw_bar('Moderate', $orange_pct, '#f59e0b');
            $html .= mylog_pdf_draw_bar('High Need', $red_pct, '#dc2626');
            
            $html .= '<div style="font-size:7pt;color:#6b7280;margin-top:4px;text-align:center;">';
            $html .= 'Total ratings: ' . $domain_stats['total'];
            $html .= '</div>';
        } else {
            $html .= '<div style="color:#9ca3af;font-size:8pt;text-align:center;padding:20px 0;">No data recorded</div>';
        }
        
        $html .= '</td>';
        $count++;
    }
    
    $html .= '</tr></table>';
    
    return $html;
}

function mylog_pdf_get_domain_activities($domain) {
    $activities = [
        'tinana' => ['mealtime', 'hygiene', 'bathing', 'dressing', 'mobility'],
        'hinengaro' => ['memory', 'focus', 'comms', 'problem', 'household'],
        'whanau' => ['family', 'community', 'digital', 'hobbies', 'group'],
        'wairua' => ['karakia', 'nature', 'culture', 'reflection', 'identity']
    ];
    return $activities[$domain] ?? [];
}

/**
 * Build Weekly Digest Tables — only for weeks that contain actual entries.
 * Never scaffolds empty weeks, preventing hundreds of blank pages when
 * choose_view=all spans years.
 */
function mylog_pdf_build_weekly_digests($entries, $from, $to) {
    $all_weeks = [];

    foreach ($entries as $entry) {
        $ts       = strtotime($entry['date']);
        $year     = date('Y', $ts);
        $week_num = date('W', $ts);
        $week_key = $year . '-' . str_pad($week_num, 2, '0', STR_PAD_LEFT);

        if (!isset($all_weeks[$week_key])) {
            $monday = date('Y-m-d', strtotime('monday this week', $ts));
            $all_weeks[$week_key] = ['start' => $monday, 'entries' => []];
        }
        $all_weeks[$week_key]['entries'][] = $entry;
    }

    ksort($all_weeks);

    $pages = [];
    foreach ($all_weeks as $week_key => $week_data) {
        $pages[] = mylog_pdf_build_single_week_table($week_key, $week_data);
    }

    return $pages;
}

function mylog_pdf_build_single_week_table($week_key, $week_data) {
    $start_date = $week_data['start'];
    $entries = $week_data['entries'];
    
    $html = '<style>
        h2 { font-size: 14pt; color: #007cba; }
        table { border-collapse: collapse; width: 100%; font-size: 8pt; }
        th { background: #007cba; color: white; padding: 4px; text-align: center; font-size: 7.5pt; }
        td { border: 1px solid #e5e7eb; padding: 3px; text-align: center; }
        .domain-header { background: #dbeafe; font-weight: bold; text-align: left; padding-left: 8px; }
    </style>';
    
    $html .= '<h2>WEEK ' . date('W', strtotime($start_date)) . ': ' . date('j M', strtotime($start_date)) . ' - ' . date('j M Y', strtotime($start_date . ' +6 days')) . '</h2>';
    
    // Build entries by date - keep ALL entries per day, then merge them intelligently
    $entries_by_date = [];
    foreach ($entries as $entry) {
        $date = $entry['date'];
        if (!isset($entries_by_date[$date])) {
            $entries_by_date[$date] = $entry; // First entry for this day
        } else {
            // Merge entries - take non-empty values, prioritize later entries (more recent data)
            foreach ($entry as $key => $value) {
                // Skip meta fields
                if (in_array($key, ['id', 'date', 'entry_maker', 'date_display'])) continue;
                
                // Update if new value is not empty
                if (!empty($value) && $value !== 'empty') {
                    $entries_by_date[$date][$key] = $value;
                }
            }
        }
    }
    
    // Table header — avoid thead/tbody as TCPDF silently drops them in some versions
    $hdr_style = 'background-color:#007cba;color:#ffffff;padding:4px;font-size:7.5pt;font-weight:bold;text-align:center;border:1px solid #007cba;';
    $html .= '<table><tr>';
    $html .= '<td style="' . $hdr_style . 'text-align:left;">Activity</td>';
    for ($i = 0; $i < 7; $i++) {
        $day_date = date('Y-m-d', strtotime($start_date . ' +' . $i . ' days'));
        $day_name = date('D', strtotime($day_date));
        $day_ddmm = date('d/m', strtotime($day_date));
        $html .= '<td style="' . $hdr_style . '">' . $day_name . ' ' . $day_ddmm . '</td>';
    }
    $html .= '</tr>';
    
    // All activities grouped by domain
    $all_activities = [
        'TAHA TINANA (Physical)' => [
            'tinana_mealtime' => 'Mealtime & Eating',
            'tinana_hygiene' => 'Personal Hygiene',
            'tinana_bathing' => 'Bathing/Showering',
            'tinana_dressing' => 'Dressing',
            'tinana_mobility' => 'Mobility'
        ],
        'TAHA HINENGARO (Mental)' => [
            'hinengaro_memory' => 'Memory/Remembering',
            'hinengaro_focus' => 'Focus/Attention',
            'hinengaro_comms' => 'Communication',
            'hinengaro_problem' => 'Problem Solving',
            'hinengaro_household' => 'Household Tasks'
        ],
        'TAHA WHĀNAU (Social)' => [
            'whanau_family' => 'Family & Whānau Time',
            'whanau_community' => 'Community Outing',
            'whanau_digital' => 'Digital Connection',
            'whanau_hobbies' => 'Active Play/Hobbies',
            'whanau_group' => 'Group Participation'
        ],
        'TAHA WAIRUA (Spiritual)' => [
            'wairua_karakia' => 'Karakia/Prayer',
            'wairua_nature' => 'Connection with Nature',
            'wairua_culture' => 'Cultural Expression',
            'wairua_reflection' => 'Personal Reflection',
            'wairua_identity' => 'Identity/Whakapapa'
        ]
    ];
    
    foreach ($all_activities as $domain_name => $activities) {
        $html .= '<tr><td colspan="8" class="domain-header">' . $domain_name . '</td></tr>';
        
        foreach ($activities as $field => $label) {
            $html .= '<tr><td style="text-align:left;">' . $label . '</td>';
            
            for ($i = 0; $i < 7; $i++) {
                $day_date = date('Y-m-d', strtotime($start_date . ' +' . $i . ' days'));
                
                if (isset($entries_by_date[$day_date])) {
                    $value = strtolower($entries_by_date[$day_date][$field] ?? '');
                    if ($value === 'green') {
                        $symbol = '<span style="color:#10b981;">●</span>';
                    } elseif ($value === 'orange') {
                        $symbol = '<span style="color:#f59e0b;">●</span>';
                    } elseif ($value === 'red') {
                        $symbol = '<span style="color:#dc2626;">●</span>';
                    } else {
                        $symbol = '<span style="color:#d1d5db;">○</span>';
                    }
                    $html .= '<td>' . $symbol . '</td>';
                } else {
                    $html .= '<td><span style="color:#d1d5db;">○</span></td>';
                }
            }
            
            $html .= '</tr>';
        }
    }
    
    $html .= '</table>';
    
    // Note incomplete days
    $logged_days = count($entries);
    if ($logged_days < 7) {
        $html .= '<div style="margin-top:8px;color:#f59e0b;"><strong>⚠️ Incomplete:</strong> ' . (7 - $logged_days) . ' days without entries</div>';
    }
    
    return $html;
}

/**
 * Build Critical Incidents Page
 */
function mylog_pdf_build_critical_incidents($entries, $stats) {
    $critical = [];
    foreach ($entries as $entry) {
        // Check if ANY activity is red
        $has_red = false;
        foreach ($entry as $key => $value) {
            // Skip non-string values (arrays, objects, etc)
            if (!is_string($value)) continue;
            
            if (strpos($key, '_') !== false && strtolower($value) === 'red') {
                $has_red = true;
                break;
            }
        }
        
        if ($has_red || (isset($entry['overall_rating']) && strtolower($entry['overall_rating']) === 'red')) {
            $critical[] = $entry;
        }
    }
    
    if (empty($critical)) {
        return null;
    }
    
    $html = '<style>
        h1 { font-size: 13pt; color: #007cba; margin-bottom: 10px; }
        .incident { border: 1px solid #ef4444; padding: 6px; margin: 4px 0; background: #fef2f2; font-size: 7pt; }
        .incident-header { font-weight: bold; color: #dc2626; margin-bottom: 3px; font-size: 8pt; }
    </style>';
    
    $html .= '<h1>CRITICAL INCIDENTS / HIGH-NEED DAYS (' . count($critical) . ' days)</h1>';
    $html .= '<div style="font-size:7pt;color:#6b7280;margin-bottom:8px;">Days requiring significant support or equipment</div>';
    
    // Build 2-column layout
    $html .= '<table style="width:100%;"><tr>';
    $html .= '<td style="width:48%;vertical-align:top;border:none;">';
    
    $count = 0;
    foreach ($critical as $entry) {
        // Switch to second column after half
        if ($count == ceil(count($critical) / 2)) {
            $html .= '</td><td style="width:4%;border:none;"></td><td style="width:48%;vertical-align:top;border:none;">';
        }
        
        $html .= '<div class="incident">';
        $html .= '<div class="incident-header">' . date('D j M', strtotime($entry['date'])) . ' | #' . $entry['id'] . ' | ' . $entry['entry_maker'] . '</div>';
        
        // List red activities (compact)
        $red_activities = [];
        foreach ($entry as $key => $value) {
            if (!is_string($value)) continue;
            if (strpos($key, '_') !== false && strtolower($value) === 'red') {
                $name = ucwords(str_replace('_', ' ', $key));
                $red_activities[] = $name;
            }
        }
        
        if (!empty($red_activities)) {
            $html .= '<div style="margin:2px 0;"><strong>High Need:</strong> ' . implode(', ', array_slice($red_activities, 0, 3));
            if (count($red_activities) > 3) $html .= '... +' . (count($red_activities) - 3);
            $html .= '</div>';
        }
        
        if (!empty($entry['quick_notes'])) {
            $html .= '<div style="margin:2px 0;"><strong>Notes:</strong> ' . esc_html(substr($entry['quick_notes'], 0, 100)) . '...</div>';
        }
        
        $html .= '</div>';
        $count++;
    }
    
    $html .= '</td></tr></table>';
    
    return $html;
}

/**
 * Build Support Requirements Summary
 */
function mylog_pdf_build_support_requirements($stats, $entries) {
    $html = '<style>
        h1 { font-size: 16pt; color: #007cba; margin-bottom: 15px; }
        table { border-collapse: collapse; width: 100%; font-size: 9pt; }
        th { background: #007cba; color: white; padding: 6px; }
        td { border: 1px solid #e5e7eb; padding: 5px; }
    </style>';
    
    $html .= '<h1>SUPPORT REQUIREMENTS SUMMARY</h1>';
    $html .= '<table><thead><tr>';
    $html .= '<th style="text-align:left;">Activity</th>';
    $html .= '<th>Green</th><th>Orange</th><th>Red</th>';
    $html .= '<th>Avg Support Time</th><th>Equipment</th>';
    $html .= '</tr></thead><tbody>';
    
    foreach ($stats['activities'] as $field => $data) {
        $total = $data['green'] + $data['orange'] + $data['red'];
        if ($total == 0) continue;
        
        $name = ucwords(str_replace('_', ' ', $field));
        $green_pct = round(($data['green'] / $total) * 100);
        $orange_pct = round(($data['orange'] / $total) * 100);
        $red_pct = round(($data['red'] / $total) * 100);
        
        // Calculate average support time from entries
        $support_times = [];
        $equipment_used = [];
        foreach ($entries as $entry) {
            if (isset($entry['support_details'][$field])) {
                if (!empty($entry['support_details'][$field]['time'])) {
                    $support_times[] = intval($entry['support_details'][$field]['time']);
                }
                if (!empty($entry['support_details'][$field]['equipment'])) {
                    $equip = $entry['support_details'][$field]['equipment'];
                    if (is_array($equip)) {
                        $equipment_used = array_merge($equipment_used, $equip);
                    } else {
                        $equipment_used[] = $equip;
                    }
                }
            }
        }
        
        $avg_time = !empty($support_times) ? round(array_sum($support_times) / count($support_times)) . ' min' : 'N/A';
        $top_equipment = !empty($equipment_used) ? array_count_values($equipment_used) : [];
        arsort($top_equipment);
        $equipment_str = !empty($top_equipment) ? array_key_first($top_equipment) . ' (' . reset($top_equipment) . '×)' : 'None';
        
        $html .= '<tr>';
        $html .= '<td style="text-align:left;">' . $name . '</td>';
        $html .= '<td>' . $green_pct . '%</td>';
        $html .= '<td>' . $orange_pct . '%</td>';
        $html .= '<td>' . $red_pct . '%</td>';
        $html .= '<td>' . $avg_time . '</td>';
        $html .= '<td>' . esc_html($equipment_str) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    
    return $html;
}

/**
 * Generate Recommendations
 */
function mylog_pdf_generate_recommendations($stats, $entries) {
    $recommendations = [];
    
    // Equipment recommendations based on high-need activities
    foreach ($stats['activities'] as $field => $data) {
        $total = $data['green'] + $data['orange'] + $data['red'];
        if ($total > 0) {
            $red_pct = ($data['red'] / $total) * 100;
            $orange_pct = ($data['orange'] / $total) * 100;
            
            // Red threshold (30%+)
            if ($red_pct > 30) {
                $name = ucwords(str_replace('_', ' ', $field));
                
                if (strpos($field, 'bathing') !== false) {
                    $recommendations[] = [
                        'priority' => 1,
                        'category' => 'Equipment',
                        'text' => 'Hoist or bath equipment assessment for safety (' . round($red_pct) . '% high need)'
                    ];
                }
                elseif (strpos($field, 'mobility') !== false) {
                    $recommendations[] = [
                        'priority' => 1,
                        'category' => 'Equipment',
                        'text' => 'Mobility aid assessment (walker/wheelchair) recommended (' . round($red_pct) . '% high need)'
                    ];
                }
                elseif (strpos($field, 'comms') !== false || strpos($field, 'communication') !== false) {
                    $recommendations[] = [
                        'priority' => 2,
                        'category' => 'Training',
                        'text' => 'Communication support tools or NZSL training (' . round($red_pct) . '% high need)'
                    ];
                }
                else {
                    $recommendations[] = [
                        'priority' => 2,
                        'category' => 'Support Hours',
                        'text' => 'Increase support hours for ' . $name . ' (' . round($red_pct) . '% requires full assistance)'
                    ];
                }
            }
            // Orange threshold (50%+)
            elseif ($orange_pct > 50) {
                $name = ucwords(str_replace('_', ' ', $field));
                $recommendations[] = [
                    'priority' => 3,
                    'category' => 'Support Hours',
                    'text' => 'Monitor ' . $name . ' - trending toward increased support needs (' . round($orange_pct) . '% moderate need)'
                ];
            }
        }
    }
    
    // Carer respite recommendations
    $sustainability = mylog_pdf_calculate_carer_sustainability($stats, $entries);
    if ($sustainability['score'] < 50) {
        $recommendations[] = [
            'priority' => 1,
            'category' => 'Carer Respite',
            'text' => 'URGENT: Immediate carer respite required (burnout risk: ' . $sustainability['score'] . '%)'
        ];
    }
    elseif ($sustainability['score'] < 70) {
        $recommendations[] = [
            'priority' => 2,
            'category' => 'Carer Respite',
            'text' => 'Schedule regular respite care (sustainability at ' . $sustainability['score'] . '%)'
        ];
    }
    
    if ($sustainability['max_consecutive'] > 14) {
        $recommendations[] = [
            'priority' => 1,
            'category' => 'Carer Respite',
            'text' => 'Regular breaks essential (current: ' . $sustainability['max_consecutive'] . ' consecutive days without respite)'
        ];
    }
    
    // Data quality recommendations
    if ($stats['coverage_percent'] < 60) {
        $recommendations[] = [
            'priority' => 3,
            'category' => 'Data Quality',
            'text' => 'Increase logging frequency for accurate assessment (currently ' . $stats['coverage_percent'] . '% coverage)'
        ];
    }
    
    // Domain-specific recommendations
    if ($stats['domain_completeness_wairua'] < 50) {
        $recommendations[] = [
            'priority' => 3,
            'category' => 'Holistic Care',
            'text' => 'Consider spiritual/cultural support activities (Taha Wairua only ' . $stats['domain_completeness_wairua'] . '% logged)'
        ];
    }
    
    // Sort by priority
    usort($recommendations, function($a, $b) {
        return $a['priority'] - $b['priority'];
    });
    
    return $recommendations;
}

/**
 * Build Recommendations Page
 */
function mylog_pdf_build_recommendations_page($recommendations, $is_full_page = false) {
    $html = '<style>
        h1 { font-size: ' . ($is_full_page ? '14pt' : '12pt') . '; color: #007cba; margin-bottom: ' . ($is_full_page ? '10px' : '8px') . '; }
        h2 { font-size: 10pt; color: #374151; margin: 10px 0 5px 0; font-weight: bold; }
        .rec-item { margin: ' . ($is_full_page ? '6px' : '4px') . ' 0; padding-left: 15px; font-size: ' . ($is_full_page ? '9pt' : '8pt') . '; line-height: 1.4; }
    </style>';
    
    if ($is_full_page) {
        $html .= '<h1>RECOMMENDATIONS FOR SUPPORT PLANNING</h1>';
        $html .= '<div style="font-size:8pt;color:#6b7280;margin-bottom:10px;">Auto-generated recommendations based on identified support needs, carer sustainability, and activity patterns</div>';
    } else {
        $html .= '<h1>APPENDIX A: COMPLETE RECOMMENDATIONS</h1>';
    }
    
    // Group by priority
    $critical = array_filter($recommendations, function($r) { return $r['priority'] === 1; });
    $high = array_filter($recommendations, function($r) { return $r['priority'] === 2; });
    $moderate = array_filter($recommendations, function($r) { return $r['priority'] === 3; });
    
    if (!empty($critical)) {
        $html .= '<h2>CRITICAL PRIORITY (Immediate Action Required)</h2>';
        foreach ($critical as $i => $rec) {
            $html .= '<div class="rec-item"><strong>' . ($i+1) . '. [' . $rec['category'] . ']</strong> ' . esc_html($rec['text']) . '</div>';
        }
    }
    
    if (!empty($high)) {
        $html .= '<h2>HIGH PRIORITY (Within 3 Months)</h2>';
        foreach ($high as $i => $rec) {
            $html .= '<div class="rec-item"><strong>' . ($i+1) . '. [' . $rec['category'] . ']</strong> ' . esc_html($rec['text']) . '</div>';
        }
    }
    
    if (!empty($moderate)) {
        $html .= '<h2>MODERATE PRIORITY (Within 6 Months)</h2>';
        foreach ($moderate as $i => $rec) {
            $html .= '<div class="rec-item"><strong>' . ($i+1) . '. [' . $rec['category'] . ']</strong> ' . esc_html($rec['text']) . '</div>';
        }
    }
    
    if ($is_full_page) {
        $html .= '<div style="margin-top:15px;padding:8px;background:#fffbeb;border:1px solid #fbbf24;font-size:7pt;">';
        $html .= '<strong>NOTE:</strong> These recommendations are algorithmically generated based on logged data patterns. ';
        $html .= 'They should be considered alongside clinical assessment, family input, and the person\'s goals and preferences.';
        $html .= '</div>';
    }
    
    return $html;
}

/**
 * Build Audit Trail - 2 columns, newest first
 */
function mylog_pdf_build_audit_trail($entries, $person_name, $from, $to) {
    $html = '<style>
        h1 { font-size: 13pt; color: #007cba; margin-bottom: 10px; }
        .audit-list { font-size: 7pt; line-height: 1.5; }
    </style>';
    
    $html .= '<h1>APPENDIX: AUDIT TRAIL</h1>';
    $html .= '<div style="font-size:7pt;color:#6b7280;margin-bottom:8px;">Complete log of all entries - newest to oldest</div>';
    
    // Sort newest first
    $sorted_entries = $entries;
    usort($sorted_entries, function($a, $b) {
        return strtotime($b['date'] . ' ' . ($b['carer_start'] ?: '00:00')) - strtotime($a['date'] . ' ' . ($a['carer_start'] ?: '00:00'));
    });
    
    // Build 2-column layout
    $html .= '<table style="width:100%;"><tr>';
    $html .= '<td style="width:48%;vertical-align:top;border:none;"><div class="audit-list">';
    
    $count = 0;
    $half = ceil(count($sorted_entries) / 2);
    $contributors = [];
    
    foreach ($sorted_entries as $entry) {
        // Switch to second column
        if ($count == $half) {
            $html .= '</div></td><td style="width:4%;border:none;"></td><td style="width:48%;vertical-align:top;border:none;"><div class="audit-list">';
        }
        
        $time = $entry['carer_start'] ?: '00:00';
        $html .= '<div>#' . $entry['id'] . ' | ' . wp_date('j M Y H:i', strtotime($entry['date'] . ' ' . $time)) . ' | ' . esc_html($entry['entry_maker']) . '</div>';
        
        if (!isset($contributors[$entry['entry_maker']])) {
            $contributors[$entry['entry_maker']] = 0;
        }
        $contributors[$entry['entry_maker']]++;
        $count++;
    }
    
    $html .= '</div></td></tr></table>';
    
    $html .= '<div style="margin-top:10px;padding:8px;background:#f0f9ff;border:1px solid #bfdbfe;font-size:7pt;">';
    $html .= '<strong>SUMMARY</strong><br>';
    $html .= 'Date Range: ' . wp_date('j M Y', strtotime($from)) . ' – ' . wp_date('j M Y', strtotime($to)) . '<br>';
    $html .= 'Total Entries: ' . count($entries) . '<br>';
    $html .= 'Contributors: ';
    $contrib_list = [];
    foreach ($contributors as $name => $count) {
        $contrib_list[] = $name . ' (' . $count . ')';
    }
    $html .= implode(', ', $contrib_list) . '<br>';
    $html .= 'All entries verifiable at: www.mylog.co.nz';
    $html .= '</div>';
    
    return $html;
}


/**
 * Enhanced stats calculation for V4.1 pages
 */
function mylog_pdf_calculate_enhanced_stats($entries) {
    $stats = [
        'total_entries' => count($entries),
        'date_range_days' => 0,
        'coverage_percent' => 0,
        'overall_day_green' => 0,
        'overall_day_orange' => 0,
        'overall_day_red' => 0,
        'support_level_green' => 0,
        'support_level_orange' => 0,
        'support_level_red' => 0,
        'activities' => [],
        'carer' => [
            'battery' => ['green' => 0, 'orange' => 0, 'red' => 0],
            'window' => ['green' => 0, 'orange' => 0, 'red' => 0],
            'coverage' => ['green' => 0, 'orange' => 0, 'red' => 0],
            'max_consecutive_days' => 0
        ],
        'domain_completeness_tinana' => 0,
        'domain_completeness_hinengaro' => 0,
        'domain_completeness_whanau' => 0,
        'domain_completeness_wairua' => 0
    ];
    
    if (empty($entries)) {
        return $stats;
    }
    
    // Calculate date range and unique days
    $first_date = $entries[0]['date'];
    $last_date = $entries[count($entries) - 1]['date'];
    $stats['date_range_days'] = (strtotime($last_date) - strtotime($first_date)) / 86400 + 1;
    
    // Count unique days with entries
    $unique_days = array_unique(array_column($entries, 'date'));
    $stats['coverage_percent'] = round((count($unique_days) / $stats['date_range_days']) * 100);
    
    // All activity fields
    $all_fields = [
        'tinana_mealtime', 'tinana_hygiene', 'tinana_bathing', 'tinana_dressing', 'tinana_mobility',
        'hinengaro_memory', 'hinengaro_focus', 'hinengaro_comms', 'hinengaro_problem', 'hinengaro_household',
        'whanau_family', 'whanau_community', 'whanau_digital', 'whanau_hobbies', 'whanau_group',
        'wairua_karakia', 'wairua_nature', 'wairua_culture', 'wairua_reflection', 'wairua_identity'
    ];
    
    // Initialize activity stats
    foreach ($all_fields as $field) {
        $stats['activities'][$field] = ['green' => 0, 'orange' => 0, 'red' => 0, 'empty' => 0];
    }
    
    // Process entries
    $prev_date = null;
    $consecutive = 1;
    $max_consecutive = 1;
    
    foreach ($entries as $entry) {
        // Overall ratings
        $overall = isset($entry['overall_rating']) && is_string($entry['overall_rating']) ? strtolower($entry['overall_rating']) : '';
        if ($overall === 'green') $stats['overall_day_green']++;
        elseif ($overall === 'orange') $stats['overall_day_orange']++;
        elseif ($overall === 'red') $stats['overall_day_red']++;
        
        $support = isset($entry['support_summary']) && is_string($entry['support_summary']) ? strtolower($entry['support_summary']) : '';
        if ($support === 'green') $stats['support_level_green']++;
        elseif ($support === 'orange') $stats['support_level_orange']++;
        elseif ($support === 'red') $stats['support_level_red']++;
        
        // Carer metrics
        $battery = isset($entry['carer_battery']) && is_string($entry['carer_battery']) ? strtolower($entry['carer_battery']) : '';
        if ($battery === 'green') $stats['carer']['battery']['green']++;
        elseif ($battery === 'orange') $stats['carer']['battery']['orange']++;
        elseif ($battery === 'red') $stats['carer']['battery']['red']++;
        
        $window = isset($entry['support_window']) && is_string($entry['support_window']) ? strtolower($entry['support_window']) : '';
        if ($window === 'green') $stats['carer']['window']['green']++;
        elseif ($window === 'orange') $stats['carer']['window']['orange']++;
        elseif ($window === 'red') $stats['carer']['window']['red']++;
        
        $coverage = isset($entry['coverage_absence']) && is_string($entry['coverage_absence']) ? strtolower($entry['coverage_absence']) : '';
        if ($coverage === 'green') $stats['carer']['coverage']['green']++;
        elseif ($coverage === 'orange') $stats['carer']['coverage']['orange']++;
        elseif ($coverage === 'red') $stats['carer']['coverage']['red']++;
        
        // Consecutive days
        if ($prev_date) {
            $day_diff = (strtotime($entry['date']) - strtotime($prev_date)) / 86400;
            if ($day_diff == 1) {
                $consecutive++;
                if ($consecutive > $max_consecutive) $max_consecutive = $consecutive;
            } else {
                $consecutive = 1;
            }
        }
        $prev_date = $entry['date'];
        
        // Activities
        foreach ($all_fields as $field) {
            $value = isset($entry[$field]) && is_string($entry[$field]) ? strtolower($entry[$field]) : '';
            if ($value === 'green') $stats['activities'][$field]['green']++;
            elseif ($value === 'orange') $stats['activities'][$field]['orange']++;
            elseif ($value === 'red') $stats['activities'][$field]['red']++;
            else $stats['activities'][$field]['empty']++;
        }
    }
    
    $stats['carer']['max_consecutive_days'] = $max_consecutive;
    
    // Calculate domain completeness
    $domains = [
        'tinana' => ['tinana_mealtime', 'tinana_hygiene', 'tinana_bathing', 'tinana_dressing', 'tinana_mobility'],
        'hinengaro' => ['hinengaro_memory', 'hinengaro_focus', 'hinengaro_comms', 'hinengaro_problem', 'hinengaro_household'],
        'whanau' => ['whanau_family', 'whanau_community', 'whanau_digital', 'whanau_hobbies', 'whanau_group'],
        'wairua' => ['wairua_karakia', 'wairua_nature', 'wairua_culture', 'wairua_reflection', 'wairua_identity']
    ];
    
    foreach ($domains as $domain => $fields) {
        $total = 0;
        $completed = 0;
        foreach ($fields as $field) {
            $total += count($entries);
            $completed += (count($entries) - $stats['activities'][$field]['empty']);
        }
        $pct = $total > 0 ? round(($completed / $total) * 100) : 0;
        $stats['domain_completeness_' . $domain] = $pct;
    }
    
    return $stats;
}


/**
 * Add HTML footer to page
 */
function mylog_pdf_add_footer($person_name, $from, $to, $page_num = null) {
    $period = wp_date('M Y', strtotime($from)) . ' - ' . wp_date('M Y', strtotime($to));
    
    $html = '<div style="position:absolute;bottom:15mm;left:12.7mm;right:12.7mm;text-align:center;font-size:8pt;color:#6b7280;">';
    $html .= '<div style="border-top:1px solid #e5e7eb;padding-top:5px;margin-top:10px;">';
    $html .= 'This report reflects user activities and caregiver logs - not to be deemed as a clinical assessment | © www.mylog.co.nz<br>';
    $html .= 'MyLog Report | ' . esc_html($person_name) . ' | ' . $period;
    $html .= '</div></div>';
    
    return $html;
}

/**
 * Build Support Requirements BY DOMAIN
 */
function mylog_pdf_build_support_requirements_by_domain($stats, $entries) {
    $html = '<style>
        h1 { font-size: 13pt; color: #007cba; margin-bottom: 10px; text-align: center; }
        h2 { font-size: 11pt; color: #007cba; margin: 8px 0 5px 0; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; font-size: 8pt; margin-bottom: 8px; }
        th { background: #007cba; color: white; padding: 4px; font-size: 7pt; }
        td { border: 1px solid #e5e7eb; padding: 3px; }
    </style>';
    
    $html .= '<h1>SUPPORT REQUIREMENTS BY DOMAIN</h1>';
    $html .= '<div style="font-size:7pt;color:#6b7280;text-align:center;margin-bottom:10px;">Detailed breakdown showing support needs across all activities</div>';
    
    $domains = [
        'tinana' => ['name' => 'TAHA TINANA (Physical Wellbeing)', 'activities' => ['mealtime' => 'Mealtime & Eating', 'hygiene' => 'Personal Hygiene', 'bathing' => 'Bathing/Showering', 'dressing' => 'Dressing', 'mobility' => 'Mobility']],
        'hinengaro' => ['name' => 'TAHA HINENGARO (Mental & Emotional)', 'activities' => ['memory' => 'Memory/Remembering', 'focus' => 'Focus/Attention', 'comms' => 'Communication', 'problem' => 'Problem Solving', 'household' => 'Household Tasks']],
        'whanau' => ['name' => 'TAHA WHĀNAU (Social & Connection)', 'activities' => ['family' => 'Family & Whānau Time', 'community' => 'Community Outing', 'digital' => 'Digital Connection', 'hobbies' => 'Active Play/Hobbies', 'group' => 'Group Participation']],
        'wairua' => ['name' => 'TAHA WAIRUA (Spiritual & Personal)', 'activities' => ['karakia' => 'Karakia/Prayer', 'nature' => 'Connection with Nature', 'culture' => 'Cultural Expression', 'reflection' => 'Personal Reflection', 'identity' => 'Identity/Whakapapa']]
    ];
    
    foreach ($domains as $domain_key => $domain) {
        $html .= '<h2>' . $domain['name'] . '</h2>';
        $html .= '<table><thead><tr>';
        $html .= '<th style="text-align:left;">Activity</th>';
        $html .= '<th>Green %</th><th>Orange %</th><th>Red %</th>';
        $html .= '<th>Avg Time</th><th>Equipment</th>';
        $html .= '</tr></thead><tbody>';
        
        foreach ($domain['activities'] as $activity_key => $activity_name) {
            $field = $domain_key . '_' . $activity_key;
            
            if (!isset($stats['activities'][$field])) continue;
            
            $data = $stats['activities'][$field];
            $total = $data['green'] + $data['orange'] + $data['red'];
            if ($total == 0) continue;
            
            $green_pct = round(($data['green'] / $total) * 100);
            $orange_pct = round(($data['orange'] / $total) * 100);
            $red_pct = round(($data['red'] / $total) * 100);
            
            // Calculate average support time
            $support_times = [];
            $equipment_used = [];
            foreach ($entries as $entry) {
                if (isset($entry['support_details'][$field])) {
                    if (!empty($entry['support_details'][$field]['time'])) {
                        $support_times[] = intval($entry['support_details'][$field]['time']);
                    }
                    if (!empty($entry['support_details'][$field]['equipment'])) {
                        $equip = $entry['support_details'][$field]['equipment'];
                        if (is_array($equip)) {
                            $equipment_used = array_merge($equipment_used, $equip);
                        } else {
                            $equipment_used[] = $equip;
                        }
                    }
                }
            }
            
            $avg_time = !empty($support_times) ? round(array_sum($support_times) / count($support_times)) . ' min' : 'N/A';
            $top_equipment = !empty($equipment_used) ? array_count_values($equipment_used) : [];
            arsort($top_equipment);
            $equipment_str = !empty($top_equipment) ? array_key_first($top_equipment) : 'None';
            
            $html .= '<tr>';
            $html .= '<td style="text-align:left;">' . $activity_name . '</td>';
            $html .= '<td>' . $green_pct . '%</td>';
            $html .= '<td>' . $orange_pct . '%</td>';
            $html .= '<td>' . $red_pct . '%</td>';
            $html .= '<td>' . $avg_time . '</td>';
            $html .= '<td>' . esc_html($equipment_str) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
    }
    
    return $html;
}