<?php
/**
 * MyLog Entry Form — Version 4.1
 * Enhanced for NZ Funding Assessments
 * 
 * CHANGES IN V4.1:
 * - Achievement field moved to Section 1 (higher completion rate)
 * - Support detail section (dynamic, appears when orange/red selected)
 * - Contextual equipment lists (activity-appropriate)
 * - Safety incident tracking with checkboxes
 * - Consolidated Taha Wairua (3 items instead of 5)
 * - Reframed carer context (system vs. personal failure)
 * - Clarified time field (active support minutes)
 * - Help text throughout for better UX
 * - Soft validation for support detail completion
 * - Fixed keyboard shortcuts (disabled when typing in fields)
 */

function mylog_render_hybrid_entry_form() {
    if (!is_user_logged_in()) {
        return '<p>Please log in to continue | Tēnā koa takiuru.</p>';
    }

    ob_start(); ?>

    <!-- STICKY LEGEND -->
    <div id="mylog-legend" style="position:sticky;top:0;z-index:999;background:white;border-bottom:3px solid #e5e7eb;padding:10px 16px;margin-bottom:24px;display:flex;gap:20px;align-items:center;flex-wrap:wrap;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
        <span style="font-weight:700;color:#374151;font-size:13px;">RATING GUIDE:</span>
        <span style="font-size:13px;">🟢 <strong>Good</strong> — Independent / Routine / Successful</span>
        <span style="font-size:13px;">🟡 <strong>Moderate</strong> — Some Difficulty / Prompting Required</span>
        <span style="font-size:13px;">🔴 <strong>High Need</strong> — Major Issue / Refusal / Incident</span>
    </div>

    <div style="margin-bottom:20px;">
        <a href="<?php echo home_url('/dashboard/'); ?>" class="button" style="text-decoration:none;">
            ⬅️ Back to Dashboard
        </a>
    </div>

<div style="background:#fff3cd;border-left:4px solid #f59e0b;padding:14px 18px;margin-bottom:24px;border-radius:10px;">
        <p style="margin:0;font-weight:700; text-align: center; color:#ff0303;font-size:16px;">⚠️ MyLog is for Daily Activities Only - Not a Medical Record</p>
        <p style="margin:6px 0 0 0; text-align: center; color:062a7d;font-size:14px;line-height:1.5;">
            MyLog records daily activities and interaction with Carers. DO NOT record medical information, diagnoses or clinical observations. <br>Submissions certify that all information provided is true, accurate, and complete to the best of knowledge .</p>
    </div>

    <div class="mylog-form-wrap" style="background:linear-gradient(135deg,#dbeafe 0%,#bfdbfe 100%);padding:24px 20px;border-radius:16px;">
        <form method="post" enctype="multipart/form-data" id="mylog-entry-form">


            <!-- ═══════════════════════════════════════════
                 SECTION 1 — THE PERSON & THE DAY
            ═══════════════════════════════════════════ -->
            <fieldset class="mylog-section" style="border:2px solid #3b82f6;border-radius:12px;padding:20px;margin-bottom:20px;background:white;">
                <legend class="mylog-legend-title" style="font-weight:800;color:#3b82f6;font-size:17px;padding:0 8px;">
                    📋 Section 1 — The Person & The Day
                </legend>
                <p class="mylog-help-text">Record who you supported and the basic shift details.</p>

                <label>Person Supported * <span style="color:#ef4444;font-weight:700;">[REQUIRED]</span></label>
                <?php echo do_shortcode('[mylog_user_selector]'); ?>
                <p class="mylog-required-error">⚠️ This field is required</p>

                <!-- Learn More Button - appears after user selection -->
                <div id="mylog-learn-more-container" style="display:none; margin: 12px 0 16px 0;">
                    <button type="button" id="mylog-learn-more-btn" class="button" style="width: 100%; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; padding: 12px 16px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 16v-4"></path>
                            <path d="M12 8h.01"></path>
                        </svg>
                        <span id="mylog-learn-more-text">Learn More about Person</span>
                    </button>
                </div>

                <div style="margin-top:16px;padding:12px;background:#f0f9ff;border-radius:8px;border:1px solid #bfdbfe;">
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <label style="margin:0;font-weight:600;font-size:15px;">Carer Shift * <span style="color:#ef4444;font-weight:700;">[REQUIRED]</span></label>
                        <div style="display:flex;align-items:center;gap:4px;">
                            <span style="font-weight:600;font-size:14px;">Start:</span>
                            <input type="number" name="carer_start_hour" min="0" max="23" placeholder="HH" required style="width:45px;padding:6px 4px;border:2px solid #93c5fd;border-radius:6px;font-size:14px;text-align:center;">
                            <span style="font-weight:bold;">:</span>
                            <input type="number" name="carer_start_minute" min="0" max="59" placeholder="MM" required style="width:45px;padding:6px 4px;border:2px solid #93c5fd;border-radius:6px;font-size:14px;text-align:center;">
                        </div>
                        <div style="display:flex;align-items:center;gap:4px;">
                            <span style="font-weight:600;font-size:14px;">End:</span>
                            <input type="number" name="carer_end_hour" min="0" max="23" placeholder="HH" required style="width:45px;padding:6px 4px;border:2px solid #93c5fd;border-radius:6px;font-size:14px;text-align:center;">
                            <span style="font-weight:bold;">:</span>
                            <input type="number" name="carer_end_minute" min="0" max="59" placeholder="MM" required style="width:45px;padding:6px 4px;border:2px solid #93c5fd;border-radius:6px;font-size:14px;text-align:center;">
                        </div>
                    </div>
                    <p class="mylog-required-error" style="margin:4px 0 0 0;">⚠️ Both times are required</p>
                </div>
                
                <!-- Hidden fields to convert to HH:MM format for backend -->
                <input type="hidden" name="carer_start_time" id="carer_start_time_hidden">
                <input type="hidden" name="carer_end_time" id="carer_end_time_hidden">
                
                <script>
                // Convert number inputs to HH:MM format before submit
                document.querySelector('form').addEventListener('submit', function(e) {
                    const startHour = document.querySelector('[name="carer_start_hour"]').value.padStart(2, '0');
                    const startMin = document.querySelector('[name="carer_start_minute"]').value.padStart(2, '0');
                    const endHour = document.querySelector('[name="carer_end_hour"]').value.padStart(2, '0');
                    const endMin = document.querySelector('[name="carer_end_minute"]').value.padStart(2, '0');
                    
                    document.getElementById('carer_start_time_hidden').value = startHour + ':' + startMin;
                    document.getElementById('carer_end_time_hidden').value = endHour + ':' + endMin;
                });
                </script>

                <label>How was today overall? * <span style="color:#ef4444;font-weight:700;">[REQUIRED]</span></label>
                <p class="mylog-help-text">Think about the whole day — was it mostly smooth, challenging, or very difficult?</p>
                <?php mylog_traffic_light_field('overall_rating', 'Overall Day', true); ?>

                <label style="margin-top:16px;">Support Level Required Today * <span style="color:#ef4444;font-weight:700;">[REQUIRED]</span></label>
                <p class="mylog-help-text">Overall, how much support did this person need across all activities?</p>
                <?php mylog_traffic_light_field('support_summary', 'Support Level', true); ?>

                <!-- ACHIEVEMENT FIELD (MOVED TO SECTION 1 FOR VISIBILITY) -->
                <div style="background:linear-gradient(135deg,#d1fae5 0%,#a7f3d0 100%);border:3px solid #10b981;border-radius:12px;padding:16px;margin-top:20px;">
                    <label style="font-size:16px;color:#065f46;margin-bottom:6px;">💪 Today's Win or Achievement (Optional)</label>
                    <p class="mylog-help-text" style="color:#047857;margin-bottom:10px;">Any progress, improvements, or proud moments? Even small wins count! This shows positive development over time.</p>
                    <input type="text" name="achievement" 
                        placeholder="e.g., 'First independent shower in 2 weeks' or 'Tried new food at dinner'"
                        style="width:100%;padding:12px;border:2px solid #10b981;border-radius:8px;font-size:15px;font-weight:600;box-sizing:border-box;">
                </div>

            </fieldset>

            <!-- ═══════════════════════════════════════════
                 SECTION 2 — TAHA TINANA (PHYSICAL)
            ═══════════════════════════════════════════ -->
            <fieldset class="mylog-section" style="border:2px solid #16a34a;border-radius:12px;padding:20px;margin-bottom:20px;background:white;">
                <legend class="mylog-legend-title" style="font-weight:800;color:#16a34a;font-size:17px;padding:0 8px;">
                    💪 Section 2 — Taha Tinana · Physical Wellbeing
                </legend>
                <p class="mylog-help-text">Rate how the person managed physical activities today. Green = did well on their own, Orange = needed some help, Red = needed a lot of help or refused.</p>

                <?php
                $tinana_items = [
                    'tinana_mealtime'  => ['label' => 'Mealtime Participation', 'help' => 'Eating, drinking, sitting at table'],
                    'tinana_hygiene'   => ['label' => 'Personal Hygiene', 'help' => 'Teeth brushing, face washing, toileting'],
                    'tinana_bathing'   => ['label' => 'Bathing / Showering', 'help' => 'Getting in/out, washing body, drying'],
                    'tinana_dressing'  => ['label' => 'Dressing / Getting Ready', 'help' => 'Choosing clothes, putting on, fastening'],
                    'tinana_mobility'  => ['label' => 'Moving / Walking / Mobility', 'help' => 'Walking, transfers, stairs, wheelchair use'],
                ];
                foreach ($tinana_items as $key => $data) {
                    mylog_traffic_light_row($key, $data['label'], $data['help']);
                }
                ?>
            </fieldset>

            <!-- ═══════════════════════════════════════════
                 SECTION 3 — TAHA HINENGARO (MIND & MEMORY)
            ═══════════════════════════════════════════ -->
            <fieldset class="mylog-section" style="border:2px solid #0284c7;border-radius:12px;padding:20px;margin-bottom:20px;background:white;">
                <legend class="mylog-legend-title" style="font-weight:800;color:#0284c7;font-size:17px;padding:0 8px;">
                    🧠 Section 3 — Taha Hinengaro · Mind & Memory
                </legend>
                <p class="mylog-help-text">Rate how the person managed thinking, remembering, and communication tasks today.</p>

                <?php
                $hinengaro_items = [
                    'hinengaro_memory'    => ['label' => 'Memory / Remembering', 'help' => 'Recalling information, following routines, recognizing people'],
                    'hinengaro_focus'     => ['label' => 'Focus / Attention on Tasks', 'help' => 'Staying on task, completing activities, not getting distracted'],
                    'hinengaro_comms'     => ['label' => 'Communication / Using Devices', 'help' => 'Speaking, understanding, phone/tablet use, expressing needs'],
                    'hinengaro_problem'   => ['label' => 'Problem Solving / Daily Choices', 'help' => 'Making decisions, figuring things out, choosing what to do'],
                    'hinengaro_household' => ['label' => 'Household Tasks', 'help' => 'Cooking, cleaning, organizing, simple chores'],
                ];
                foreach ($hinengaro_items as $key => $data) {
                    mylog_traffic_light_row($key, $data['label'], $data['help']);
                }
                ?>
            </fieldset>

            <!-- ═══════════════════════════════════════════
                 SECTION 4 — TAHA WHĀNAU (SOCIAL & CONNECTION)
            ═══════════════════════════════════════════ -->
            <fieldset class="mylog-section" style="border:2px solid #f59e0b;border-radius:12px;padding:20px;margin-bottom:20px;background:white;">
                <legend class="mylog-legend-title" style="font-weight:800;color:#f59e0b;font-size:17px;padding:0 8px;">
                    👥 Section 4 — Taha Whānau · Social & Connection
                </legend>
                <p class="mylog-help-text">Rate the quality of social engagement and community participation today.</p>

                <?php
                $whanau_items = [
                    'whanau_family'     => ['label' => 'Family & Whānau Time', 'help' => 'Quality time with family, conversations, shared activities'],
                    'whanau_community'  => ['label' => 'Community Outing / Transport', 'help' => 'Going out, using transport, being in public spaces'],
                    'whanau_digital'    => ['label' => 'Digital Connection (calls / video)', 'help' => 'Phone calls, video chats, messaging'],
                    'whanau_hobbies'    => ['label' => 'Active Play / Hobbies', 'help' => 'Sports, games, crafts, creative activities'],
                    'whanau_group'      => ['label' => 'Group Participation / Events', 'help' => 'Church, clubs, classes, social groups'],
                ];
                foreach ($whanau_items as $key => $data) {
                    mylog_traffic_light_row($key, $data['label'], $data['help']);
                }
                ?>
            </fieldset>

            <!-- ═══════════════════════════════════════════
                 SECTION 5 — TAHA WAIRUA (SPIRITUAL) — CONSOLIDATED
            ═══════════════════════════════════════════ -->
            <fieldset class="mylog-section" style="border:2px solid #9333ea;border-radius:12px;padding:20px;margin-bottom:20px;background:white;">
                <legend class="mylog-legend-title" style="font-weight:800;color:#9333ea;font-size:17px;padding:0 8px;">
                    ✨ Section 5 — Taha Wairua · Spiritual & Personal Wellbeing
                </legend>
                <p class="mylog-help-text">Rate the presence and quality of spiritual, cultural, and personal connection today.</p>

                <?php
                $wairua_items = [
                    'wairua_cultural'   => ['label' => 'Cultural / Spiritual Participation', 'help' => 'Karakia, prayer, church, cultural events, waiata, te reo use'],
                    'wairua_nature'     => ['label' => 'Nature & Quiet Time', 'help' => 'Outdoor connection, garden time, peaceful moments, reflection'],
                    'wairua_identity'   => ['label' => 'Identity & Belonging', 'help' => 'Expressing self, connecting to heritage, feeling part of community'],
                ];
                foreach ($wairua_items as $key => $data) {
                    mylog_traffic_light_row($key, $data['label'], $data['help']);
                }
                ?>
            </fieldset>

            <!-- ═══════════════════════════════════════════
                 SECTION 2A — SUPPORT DETAIL (CONDITIONAL, DYNAMIC)
            ═══════════════════════════════════════════ -->
            <div class="mylog-section mylog-support-detail" id="support_detail_section" style="border:2px solid #f59e0b;border-radius:12px;padding:20px;margin-bottom:20px;background:#fffbeb;display:none;">
                <div style="font-weight:800;color:#f59e0b;font-size:17px;padding:0 0 10px 0;border-bottom:1px solid #fde68a;margin-bottom:12px;">
                    🔍 Support Detail
                </div>
                <p class="mylog-help-text">You selected 🟡 Moderate or 🔴 High Need for some activities. Please give us a bit more detail to help assessors understand the support provided.</p>

                <div id="support_detail_container">
                    <!-- Dynamically populated based on orange/red selections -->
                </div>

                <p style="font-size:12px;color:#92400e;margin-top:16px;font-style:italic;">
                    💡 Tip: This detail helps funding assessors understand exactly what support is needed and for how long.
                </p>
            </div>

            <!-- ═══════════════════════════════════════════
                 SECTION 6 — SAFETY & INCIDENTS
            ═══════════════════════════════════════════ -->
            <fieldset class="mylog-section" style="border:2px solid #dc2626;border-radius:12px;padding:20px;margin-bottom:20px;background:white;">
                <legend class="mylog-legend-title" style="font-weight:800;color:#dc2626;font-size:17px;padding:0 8px;">
                    🚨 Section 6 — Safety & Incidents
                </legend>
                <p class="mylog-help-text">Record any safety concerns or incidents today. Being honest helps ensure proper support is in place. Select all that apply.</p>

                <div style="display:grid;grid-template-columns:1fr;gap:10px;margin-bottom:16px;">
                    <label class="mylog-checkbox-label">
                        <input type="checkbox" name="incidents[]" value="none" id="incident_none">
                        <span>✅ No incidents today</span>
                    </label>
                    <label class="mylog-checkbox-label">
                        <input type="checkbox" name="incidents[]" value="fall">
                        <span>⚠️ Fall / Near-miss / Balance Issue</span>
                    </label>
                    <label class="mylog-checkbox-label">
                        <input type="checkbox" name="incidents[]" value="behavioral">
                        <span>⚠️ Behavioral Incident (agitation, distress, refusal)</span>
                    </label>
                    <label class="mylog-checkbox-label">
                        <input type="checkbox" name="incidents[]" value="wandering">
                        <span>⚠️ Wandering / Left Supervision Area</span>
                    </label>
                    <label class="mylog-checkbox-label">
                        <input type="checkbox" name="incidents[]" value="equipment">
                        <span>⚠️ Equipment Issue / Malfunction</span>
                    </label>
                    <label class="mylog-checkbox-label">
                        <input type="checkbox" name="incidents[]" value="emergency">
                        <span>🚨 Emergency Situation (111 called, urgent assistance needed)</span>
                    </label>
                </div>

                <div id="incident_detail_box" style="display:none;margin-top:16px;">
                    <label>Describe the incident briefly:</label>
                    <p class="mylog-help-text">What happened, when, and how was it handled? Keep it factual.</p>
                    <textarea name="incident_details" rows="3" 
                        placeholder="Example: 'Lost balance while walking to bathroom at 2pm. Caught by carer, no injury. Person was rushing and not using walker as prompted.'"
                        style="width:100%;padding:10px;border:2px solid #e5e7eb;border-radius:8px;font-size:15px;box-sizing:border-box;"></textarea>
                </div>
            </fieldset>

            <!-- ═══════════════════════════════════════════
                 SECTION 7 — NOTES & PHOTO
            ═══════════════════════════════════════════ -->
            <fieldset class="mylog-section" style="border:2px solid #6b7280;border-radius:12px;padding:20px;margin-bottom:20px;background:white;">
                <legend class="mylog-legend-title" style="font-weight:800;color:#6b7280;font-size:17px;padding:0 8px;">
                    📝 Section 7 — Notes & Documentation
                </legend>

                <!-- VOICE NOTE -->
                <label>Voice Note</label>
                <p class="mylog-help-text">Tap to speak. Your note will be transcribed automatically. Great for quick updates!<br>
                <span style="font-size:0.82em; color:#6b7280;">💡 Say: <strong>&ldquo;full stop&rdquo;</strong> &middot; <strong>&ldquo;comma&rdquo;</strong> &middot; <strong>&ldquo;question mark&rdquo;</strong> &middot; <strong>&ldquo;new line&rdquo;</strong> &middot; <strong>&ldquo;new paragraph&rdquo;</strong></span></p>

                <button type="button" id="voice_record_btn" class="mylog-voice-btn">
                    🎤 Tap to Record Voice Note
                </button>
                <div id="voice_status"></div>
                <div id="keyword_tags" style="margin-top:8px;display:flex;flex-wrap:wrap;gap:6px;"></div>

                <!-- WRITTEN NOTES -->
                <label style="margin-top:20px;">Today's Notes</label>
                <p class="mylog-help-text">Describe today's support, activities, and any observations worth recording.</p>
                <textarea name="quick_notes" id="quick_notes_field" rows="4"
                    placeholder="Example: 'Had a great morning routine. Needed extra prompting for shower but went well once started. Enjoyed lunch with family. Afternoon walk to park — used walker independently for first time this week!'"
                    style="width:100%;padding:10px;border:2px solid #e5e7eb;border-radius:8px;font-size:15px;box-sizing:border-box;"></textarea>

                <!-- ADDITIONAL NOTES -->
                <label style="margin-top:16px;">Additional Notes</label>
                <p class="mylog-help-text">Anything else to add — observations, things to follow up on, or context.</p>
                <textarea name="extra_notes" rows="3"
                    placeholder="Example: 'Physio appointment next Tuesday. Remember to bring walker. Person mentioned wanting to try swimming again — worth exploring.'"
                    style="width:100%;padding:10px;border:2px solid #e5e7eb;border-radius:8px;font-size:15px;box-sizing:border-box;"></textarea>

                <!-- PHOTO — Dynamic UI, normal form POST (no fetch) -->
                <label style="margin-top:16px;">Photos (Optional — up to 4)</label>
                <p class="mylog-help-text">Capture achievements, activities, or happy moments. Great for showing participation and engagement!</p>

                <!-- The real file inputs — always in the form, submitted normally -->
                <div id="photo-inputs-container" style="display:none;">
                    <input type="file" name="photos[]" id="photo-file-1" accept="image/*" style="display:none;">
                    <input type="file" name="photos[]" id="photo-file-2" accept="image/*" style="display:none;">
                    <input type="file" name="photos[]" id="photo-file-3" accept="image/*" style="display:none;">
                    <input type="file" name="photos[]" id="photo-file-4" accept="image/*" style="display:none;">
                </div>

                <!-- Preview grid — populated by JS -->
                <div id="photo-preview-grid" style="display:none;flex-wrap:wrap;gap:10px;margin-bottom:12px;"></div>

                <!-- Upload button -->
                <button type="button" id="photo-add-btn"
                    style="width:100%;padding:12px;background:#f0f9ff;border:2px dashed #93c5fd;border-radius:10px;color:#1d4ed8;font-size:15px;font-weight:600;cursor:pointer;transition:all 0.2s;">
                    📷 Upload Photo &nbsp;<span id="photo-count-label"></span>
                </button>
            </fieldset>

            <!-- ═══════════════════════════════════════════
                 SECTION 8 — SUPPORT ENVIRONMENT (REFRAMED)
            ═══════════════════════════════════════════ -->
            <fieldset class="mylog-section" id="section8" style="border:2px solid #7c3aed;border-radius:12px;padding:20px;margin-bottom:20px;background:white;">
                <legend class="mylog-legend-title" style="font-weight:800;color:#7c3aed;font-size:17px;padding:0 8px;">
                    🏠 Section 8 — Support Environment Today
                </legend>
                <p class="mylog-help-text">This section helps identify when the support SYSTEM (not you!) needs strengthening. Honest answers help justify additional resources.</p>

                <?php mylog_traffic_light_row('support_adequacy', 'Support System Today', [
                    'green'  => '🟢 Well-resourced — Had what was needed',
                    'orange' => '🟡 Stretched — Could have used more',
                    'red'    => '🔴 Under-resourced — Gaps in coverage/tools'
                ], 'Were the right supports in place — enough time, backup, and equipment?'); ?>

                <?php mylog_traffic_light_row('support_window', 'Focus & Interruptions', [
                    'green'  => '🟢 Could focus — Few interruptions',
                    'orange' => '🟡 Some distractions — Juggling other demands',
                    'red'    => '🔴 Fragmented — Constant interruptions'
                ], 'Were you able to give this person your full attention when needed?'); ?>

                <?php mylog_traffic_light_row('coverage_absence', 'Safety Net If Needed', [
                    'green'  => '🟢 Covered — Backup available',
                    'orange' => '🟡 Partial — Limited backup',
                    'red'    => '🔴 Uncovered — No backup if emergency arose'
                ], 'If you needed urgent help or a break, was someone available?'); ?>

                <label style="margin-top:16px;">Support Environment Notes</label>
                <p class="mylog-help-text">What would have made today easier? What resources or support were missing? This feedback helps improve the support system.</p>
                <textarea name="support_notes" rows="3"
                    placeholder="Example: 'Would have been helpful to have second carer during bathing. Person's needs increasing but support hours haven't adjusted.' or 'Great team support today — handover was clear and comprehensive.'"
                    style="width:100%;padding:10px;border:2px solid #e5e7eb;border-radius:8px;font-size:15px;box-sizing:border-box;"></textarea>
            </fieldset>

            <?php wp_nonce_field('mylog_add_entry', '_wpnonce'); ?>

            <button type="submit" name="detailed_submit" id="mylog-submit-btn"
                style="width:100%;background:linear-gradient(135deg,#10b981 0%,#059669 100%);color:white;border:none;padding:20px;font-size:19px;font-weight:800;border-radius:14px;cursor:pointer;box-shadow:0 4px 14px rgba(16,185,129,0.4);transition:all 0.3s;">
                💾 Save to MyLog
            </button>

        </form>
    </div>

    <!-- Learn More Modal -->
    <div id="mylog-learn-more-modal" class="mylog-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; z-index:9999;">
        <div class="mylog-modal-overlay" style="position:absolute; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.7);"></div>
        <div class="mylog-modal-content" style="max-width: 600px; margin: 50px auto; background: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); position: relative; z-index: 10000; max-height: calc(100vh - 100px); display: flex; flex-direction: column;">
            <div class="mylog-modal-header" style="padding: 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                <h2 style="margin: 0; font-size: 1.5rem;" id="mylog-learn-more-modal-title">Person Profile</h2>
                <button type="button" class="mylog-modal-close" style="background: none; border: none; font-size: 28px; cursor: pointer; color: #6b7280; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; padding: 0;">&times;</button>
            </div>
            <div class="mylog-modal-body" style="padding: 20px; overflow-y: auto; flex: 1;" id="mylog-learn-more-content">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>

    <?php mylog_entry_form_styles(); ?>
    <?php mylog_entry_form_scripts(); ?>

    <?php
    return ob_get_clean();
}

/**
 * Render a traffic light row with help text
 */
function mylog_traffic_light_row($name, $label, $custom_labels_or_help = false, $help_text = '') {
    // Handle both old signature (custom_labels) and new signature (with help text)
    $custom_labels = false;
    if (is_array($custom_labels_or_help) && isset($custom_labels_or_help['green'])) {
        $custom_labels = $custom_labels_or_help;
    } elseif (is_string($custom_labels_or_help)) {
        $help_text = $custom_labels_or_help;
    }

    $green_label  = $custom_labels ? $custom_labels['green']  : '🟢 Good';
    $orange_label = $custom_labels ? $custom_labels['orange'] : '🟡 Moderate';
    $red_label    = $custom_labels ? $custom_labels['red']    : '🔴 High Need';
    ?>
    <div class="mylog-tl-row" data-field="<?php echo esc_attr($name); ?>" style="margin-bottom:14px;">
        <div style="font-weight:600;color:#1f2937;font-size:14px;margin-bottom:4px;">
            <?php echo esc_html($label); ?>
        </div>
        <?php if ($help_text): ?>
            <p class="mylog-help-text" style="margin-bottom:8px;"><?php echo esc_html($help_text); ?></p>
        <?php endif; ?>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;">
            <label class="mylog-tl-btn green-btn" data-value="green">
                <input type="radio" name="<?php echo esc_attr($name); ?>" value="green" style="position:absolute;opacity:0;width:0;height:0;">
                <?php echo $green_label; ?>
            </label>
            <label class="mylog-tl-btn orange-btn" data-value="orange">
                <input type="radio" name="<?php echo esc_attr($name); ?>" value="orange" style="position:absolute;opacity:0;width:0;height:0;">
                <?php echo $orange_label; ?>
            </label>
            <label class="mylog-tl-btn red-btn" data-value="red">
                <input type="radio" name="<?php echo esc_attr($name); ?>" value="red" style="position:absolute;opacity:0;width:0;height:0;">
                <?php echo $red_label; ?>
            </label>
        </div>
    </div>
    <?php
}

/**
 * Render a required traffic light field (Section 1)
 */
function mylog_traffic_light_field($name, $label, $required = false) {
    ?>
    <div class="mylog-tl-row" data-field="<?php echo esc_attr($name); ?>" style="margin-bottom:14px;">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
            <label class="mylog-tl-btn green-btn tl-large" data-value="green">
                <input type="radio" name="<?php echo esc_attr($name); ?>" value="green" <?php echo $required ? 'required' : ''; ?> style="position:absolute;opacity:0;width:0;height:0;">
                🟢 Good
            </label>
            <label class="mylog-tl-btn orange-btn tl-large" data-value="orange">
                <input type="radio" name="<?php echo esc_attr($name); ?>" value="orange" style="position:absolute;opacity:0;width:0;height:0;">
                🟡 Moderate
            </label>
            <label class="mylog-tl-btn red-btn tl-large" data-value="red">
                <input type="radio" name="<?php echo esc_attr($name); ?>" value="red" style="position:absolute;opacity:0;width:0;height:0;">
                🔴 High Need
            </label>
        </div>
        <?php if ($required): ?>
            <p class="mylog-required-error">⚠️ This field is required</p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Get contextual equipment list for activity
 */
function mylog_get_equipment_for_activity($activity_field) {
    $equipment_map = [
        'tinana_bathing'    => ['Shower chair', 'Hoist', 'Grab rails', 'Bath bench', 'Non-slip mat'],
        'tinana_mobility'   => ['Walker', 'Wheelchair', 'Cane', 'Ramp', 'Grab rails', 'Stairlift'],
        'tinana_dressing'   => ['Dressing stick', 'Sock aid', 'Button hook', 'Shoe horn', 'Reacher'],
        'tinana_mealtime'   => ['Adapted cutlery', 'Plate guard', 'Non-slip mat', 'Drinking aid', 'Cup with handles'],
        'tinana_hygiene'    => ['Raised toilet seat', 'Commode', 'Grab rails', 'Wiping aid', 'Urinal'],
        'hinengaro_memory'  => ['Reminder board', 'Calendar', 'Clock', 'Labels', 'Checklist'],
        'hinengaro_comms'   => ['AAC device', 'Picture cards', 'Whiteboard', 'Hearing aid', 'Amplifier'],
        'whanau_community'  => ['Wheelchair', 'Walker', 'Mobility scooter', 'Vehicle ramp', 'Companion support'],
    ];
    
    return $equipment_map[$activity_field] ?? ['Walker', 'Wheelchair', 'None', 'Other'];
}

/**
 * Styles
 */
function mylog_entry_form_styles() { ?>
<style>
/* Help text styling */
.mylog-help-text {
    font-size: 12px;
    color: #6b7280;
    font-style: italic;
    margin: 0 0 8px 0;
    line-height: 1.4;
}

/* Traffic light buttons */
.mylog-tl-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px 6px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: 3px solid transparent;
    transition: all 0.2s ease;
    text-align: center;
    position: relative;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
}

/* Large buttons for Section 1 */
.tl-large {
    padding: 18px 10px !important;
    font-size: 16px !important;
    border-radius: 12px !important;
}

.green-btn  { background: #f0fdf4; border-color: #86efac; color: #166534; }
.orange-btn { background: #fffbeb; border-color: #fcd34d; color: #92400e; }
.red-btn    { background: #fef2f2; border-color: #fca5a5; color: #991b1b; }

.green-btn.selected  { background: #22c55e; border-color: #16a34a; color: white; box-shadow: 0 3px 10px rgba(34,197,94,0.4); }
.orange-btn.selected { background: #f59e0b; border-color: #d97706; color: white; box-shadow: 0 3px 10px rgba(245,158,11,0.4); }
.red-btn.selected    { background: #ef4444; border-color: #dc2626; color: white; box-shadow: 0 3px 10px rgba(239,68,68,0.4); }

.green-btn:hover  { background: #dcfce7; border-color: #4ade80; }
.orange-btn:hover { background: #fef3c7; border-color: #fbbf24; }
.red-btn:hover    { background: #fee2e2; border-color: #f87171; }

/* Checkbox labels */
.mylog-checkbox-label {
    display: flex;
    align-items: center;
    padding: 10px 12px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    background: #f9fafb;
}

.mylog-checkbox-label:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.mylog-checkbox-label input[type="checkbox"] {
    margin-right: 10px;
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.mylog-checkbox-label input[type="checkbox"]:checked {
    accent-color: #ef4444;
}

/* Voice button */
.mylog-voice-btn {
    width: 100% !important;
    padding: 20px !important;
    font-size: 18px !important;
    font-weight: 700 !important;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    color: white !important;
    border: none !important;
    border-radius: 14px !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 4px 12px rgba(16,185,129,0.3) !important;
}

.mylog-voice-btn.recording {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
    animation: voicePulse 1.2s ease-in-out infinite !important;
    box-shadow: 0 4px 20px rgba(239,68,68,0.5) !important;
}

.mylog-voice-btn.pulsing-red {
    animation: voicePulse 1.2s ease-in-out infinite !important;
    box-shadow: 0 0 0 0 rgba(239,68,68,0.7) !important;
}

@keyframes voicePulse {
    0%   { transform: scale(1);    box-shadow: 0 0 0 0 rgba(239,68,68,0.4); }
    50%  { transform: scale(1.02); box-shadow: 0 0 0 12px rgba(239,68,68,0); }
    100% { transform: scale(1);    box-shadow: 0 0 0 0 rgba(239,68,68,0); }
}

#voice_status {
    text-align: center;
    font-size: 14px;
    font-weight: 600;
    margin-top: 8px;
    min-height: 20px;
}

/* Keyword tags */
.keyword-tag {
    background: #fef3c7;
    border: 1px solid #f59e0b;
    color: #92400e;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.keyword-tag.alert {
    background: #fee2e2;
    border-color: #ef4444;
    color: #991b1b;
}

/* Form labels and inputs */
label {
    display: block;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
    font-size: 14px;
}

fieldset select,
fieldset input[type="time"],
fieldset input[type="text"],
fieldset input[type="number"],
fieldset textarea {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 15px;
    transition: border-color 0.2s;
    box-sizing: border-box;
}

fieldset select:focus,
fieldset input[type="time"]:focus,
fieldset input[type="text"]:focus,
fieldset input[type="number"]:focus,
fieldset textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}

/* Support detail items */
.support-detail-item {
    background: #fefce8;
    border: 2px solid #fde047;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 16px;
}

.support-detail-item h4 {
    margin: 0 0 12px 0;
    color: #854d0e;
    font-size: 15px;
    font-weight: 700;
}

.support-detail-item label {
    font-size: 13px;
    color: #713f12;
}

.support-detail-item select,
.support-detail-item input {
    font-size: 14px;
}

/* Equipment checkboxes responsive */
.equipment-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    font-size: 13px;
}

.equipment-grid label {
    font-weight: 400;
    display: flex;
    align-items: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Submit button hover */
#mylog-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16,185,129,0.5) !important;
}

/* Mobile adjustments */
@media (max-width: 600px) {
    .mylog-tl-btn { font-size: 12px; padding: 12px 4px; }
    .tl-large { padding: 16px 6px !important; font-size: 15px !important; }
    #mylog-legend { font-size: 11px; gap: 10px; }
    .mylog-help-text { font-size: 11px; }
    .equipment-grid {
        grid-template-columns: 1fr !important;
    }
}

/* Learn More button hover */
#mylog-learn-more-btn:hover {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

/* Learn More modal profile styles */
.mylog-user-profile-photo {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #e5e7eb;
    margin: 0 auto 20px;
    display: block;
}

.mylog-profile-section {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e5e7eb;
}

.mylog-profile-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.mylog-profile-label {
    font-size: 13px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.mylog-profile-value {
    font-size: 15px;
    color: #1f2937;
    line-height: 1.6;
    white-space: pre-wrap;
}

.mylog-profile-value.large {
    font-size: 20px;
    font-weight: 600;
    color: #111827;
}
</style>
<?php }

/**
 * Scripts
 */
function mylog_entry_form_scripts() { ?>
<script>
(function() {

    // Equipment lists for contextual display
    const equipmentLists = {
        'tinana_bathing': ['Shower chair', 'Hoist', 'Grab rails', 'Bath bench', 'Non-slip mat'],
        'tinana_mobility': ['Walker', 'Wheelchair', 'Cane', 'Ramp', 'Grab rails', 'Stairlift'],
        'tinana_dressing': ['Dressing stick', 'Sock aid', 'Button hook', 'Shoe horn', 'Reacher'],
        'tinana_mealtime': ['Adapted cutlery', 'Plate guard', 'Non-slip mat', 'Drinking aid', 'Cup with handles'],
        'tinana_hygiene': ['Raised toilet seat', 'Commode', 'Grab rails', 'Wiping aid', 'Urinal'],
        'hinengaro_memory': ['Reminder board', 'Calendar', 'Clock', 'Labels', 'Checklist'],
        'hinengaro_comms': ['AAC device', 'Picture cards', 'Whiteboard', 'Hearing aid', 'Amplifier'],
        'whanau_community': ['Wheelchair', 'Walker', 'Mobility scooter', 'Vehicle ramp', 'Companion support'],
        'default': ['Walker', 'Wheelchair', 'None', 'Other']
    };

    // ── TRAFFIC LIGHT SELECTION ──────────────────────────────────
    document.querySelectorAll('.mylog-tl-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const row = this.closest('.mylog-tl-row');
            row.querySelectorAll('.mylog-tl-btn').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
            this.querySelector('input[type="radio"]').checked = true;

            updateSupportDetailSection();
            checkForRedTrigger();
        });
    });

    // ── DYNAMIC SUPPORT DETAIL SECTION ───────────────────────────
    function updateSupportDetailSection() {
        const orangeOrRedActivities = [];
        
        // Scan Sections 2-5 for orange/red selections
        const activityRows = document.querySelectorAll('.mylog-tl-row[data-field^="tinana_"], .mylog-tl-row[data-field^="hinengaro_"], .mylog-tl-row[data-field^="whanau_"], .mylog-tl-row[data-field^="wairua_"]');
        
        activityRows.forEach(row => {
            const selected = row.querySelector('.mylog-tl-btn.selected');
            if (selected && (selected.dataset.value === 'orange' || selected.dataset.value === 'red')) {
                const fieldName = row.dataset.field;
                const labelText = row.querySelector('div[style*="font-weight:600"]').textContent.trim();
                orangeOrRedActivities.push({
                    field: fieldName,
                    label: labelText,
                    rating: selected.dataset.value
                });
            }
        });

        const detailSection = document.getElementById('support_detail_section');
        const detailContainer = document.getElementById('support_detail_container');

        if (orangeOrRedActivities.length > 0) {
            detailSection.style.display = 'block';

            // ── SNAPSHOT existing values before wiping the container ──
            // This preserves data the user already entered when they change
            // another traffic light rating and the section rebuilds.
            const savedValues = {};
            detailContainer.querySelectorAll('[name]').forEach(el => {
                const name = el.name;
                if (el.type === 'checkbox') {
                    if (!savedValues[name]) savedValues[name] = [];
                    if (el.checked) savedValues[name].push(el.value);
                } else {
                    savedValues[name] = el.value;
                }
            });

            detailContainer.innerHTML = '';

            orangeOrRedActivities.forEach(activity => {
                const equipmentOptions = equipmentLists[activity.field] || equipmentLists['default'];
                
                const itemDiv = document.createElement('div');
                itemDiv.className = 'support-detail-item';
                itemDiv.innerHTML = `
                    <h4>${activity.rating === 'orange' ? '🟡' : '🔴'} ${activity.label}</h4>
                    
                    <label>Type of support provided:</label>
                    <select name="support_type_${activity.field}" style="width:100%;margin-bottom:12px;">
                        <option value="">Select support type...</option>
                        <option value="supervision">Supervision only (watching, nearby)</option>
                        <option value="verbal">Verbal prompts (reminders, encouragement)</option>
                        <option value="guidance">Physical guidance (light touch, hand-over-hand)</option>
                        <option value="partial">Partial assistance (helping with some steps)</option>
                        <option value="full_1carer">Full assistance (1 carer, hands-on)</option>
                        <option value="full_2carers">Full assistance (2+ carers needed)</option>
                        <option value="refused">Activity refused or unable to complete</option>
                        <option value="na">None / Not Applicable</option>
                    </select>

                    <label>Active support time for this activity (optional but helpful):</label>
                    <p class="mylog-help-text">How many minutes were you actively helping? Not total time — just the time you were hands-on or giving prompts.</p>
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                        <input type="number" name="support_time_${activity.field}" 
                            placeholder="e.g. 15" min="1" max="180" 
                            style="width:120px;">
                        <span style="font-size:12px;color:#6b7280;">minutes</span>
                    </div>

                    <label>Equipment or aids used:</label>
                    <div class="equipment-grid">
                        <label style="font-weight:600;color:#6b7280;grid-column:1/-1;margin-bottom:4px;">
                            <input type="checkbox" name="equipment_${activity.field}[]" value="none_na"
                                style="margin-right:6px;"
                                onchange="if(this.checked){this.closest('.equipment-grid').querySelectorAll('input[type=checkbox]:not([value=none_na])').forEach(c=>c.checked=false);}"> 
                            None / N/A
                        </label>
                        ${equipmentOptions.map(eq => `
                            <label style="font-weight:400;">
                                <input type="checkbox" name="equipment_${activity.field}[]" value="${eq}"
                                    onchange="if(this.checked){var grid=this.closest('.equipment-grid');var noneChk=grid.querySelector('input[value=none_na]');if(noneChk)noneChk.checked=false;}"> ${eq}
                            </label>
                        `).join('')}
                    </div>
                `;
                detailContainer.appendChild(itemDiv);
            });

            // ── RESTORE previously entered values ──────────────────
            detailContainer.querySelectorAll('[name]').forEach(el => {
                const name = el.name;
                if (!(name in savedValues)) return;
                if (el.type === 'checkbox') {
                    el.checked = Array.isArray(savedValues[name]) && savedValues[name].includes(el.value);
                } else if (el.tagName === 'SELECT') {
                    el.value = savedValues[name];
                } else {
                    el.value = savedValues[name];
                }
            });

        } else {
            detailSection.style.display = 'none';
        }
    }

    // ── INCIDENT CHECKBOXES ──────────────────────────────────────
    const incidentCheckboxes = document.querySelectorAll('input[name="incidents[]"]');
    const incidentNone = document.getElementById('incident_none');
    const incidentDetailBox = document.getElementById('incident_detail_box');

    incidentCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.value === 'none' && this.checked) {
                incidentCheckboxes.forEach(cb => {
                    if (cb !== this) cb.checked = false;
                });
                incidentDetailBox.style.display = 'none';
            } else if (this.checked && this.value !== 'none') {
                if (incidentNone) incidentNone.checked = false;
                incidentDetailBox.style.display = 'block';
            }

            const anyChecked = Array.from(incidentCheckboxes).some(cb => cb.checked && cb.value !== 'none');
            incidentDetailBox.style.display = anyChecked ? 'block' : 'none';
        });
    });

    // ── FORM VALIDATION (SOFT) ───────────────────────────────────
    // No blocking validation on support details — all fields are optional.
    // The form submits cleanly; whatever is filled gets saved.

    // ── KEYBOARD SHORTCUTS (1=Green, 2=Orange, 3=Red) ────────────
    let currentRowIndex = 0;
    const allRows = document.querySelectorAll('.mylog-tl-row');

    document.addEventListener('keydown', function(e) {
        // Don't trigger if user is typing in a field
        if (e.target.tagName === 'INPUT' || 
            e.target.tagName === 'SELECT' || 
            e.target.tagName === 'TEXTAREA') {
            return;
        }
        
        if (['1','2','3'].includes(e.key)) {
            const row = allRows[currentRowIndex];
            if (!row) return;
            const btns = row.querySelectorAll('.mylog-tl-btn');
            const map = {'1': 0, '2': 1, '3': 2};
            btns.forEach(b => b.classList.remove('selected'));
            btns[map[e.key]].classList.add('selected');
            btns[map[e.key]].querySelector('input[type="radio"]').checked = true;
            
            updateSupportDetailSection();
            checkForRedTrigger();

            currentRowIndex = Math.min(currentRowIndex + 1, allRows.length - 1);
            allRows[currentRowIndex].scrollIntoView({behavior:'smooth', block:'center'});
            e.preventDefault();
        }
    });

    // ── RED TRIGGER — VOICE BUTTON PULSES ───────────────────────
    function checkForRedTrigger() {
        const voiceBtn = document.getElementById('voice_record_btn');
        const anyRed = document.querySelector('.red-btn.selected');
        if (anyRed) {
            if (!voiceBtn.classList.contains('recording')) {
                voiceBtn.classList.add('pulsing-red');
            }
        } else {
            voiceBtn.classList.remove('pulsing-red');
        }
    }

    // ── KEYWORD DETECTION ────────────────────────────────────────
    const ALERT_KEYWORDS = [
        'incident','fall','fell','injury','injured','hospital','ambulance','emergency',
        'agitation','agitated','aggressive','aggression','violent','violence',
        'seizure','fit','collapse','unconscious','unresponsive',
        'wandering','missing','escaped','elopement',
        'refused','refusal','non-compliant',
        'distressed','crying','screaming','meltdown','breakdown',
        'choking','swallowing','aspiration',
        'skin','wound','bruise','sore','rash','bleed',
        'bowel','constipation','diarrhoea','accident',
        'respite','crisis','urgent','concern'
    ];

    const GENERAL_KEYWORDS = [
        'happy','calm','settled','good day','great','positive','enjoyed','laughed',
        'walked','exercise','ate well','good sleep','social','family','outing',
        'TV','music','garden','craft','prayer','church','video call'
    ];

    function detectKeywords(text) {
        const lower = text.toLowerCase();
        const found = { alert: [], general: [] };

        ALERT_KEYWORDS.forEach(kw => {
            if (lower.includes(kw)) found.alert.push(kw);
        });
        GENERAL_KEYWORDS.forEach(kw => {
            if (lower.includes(kw)) found.general.push(kw);
        });

        return found;
    }

    function renderKeywordTags(keywords) {
        const container = document.getElementById('keyword_tags');
        container.innerHTML = '';

        keywords.alert.forEach(kw => {
            const tag = document.createElement('span');
            tag.className = 'keyword-tag alert';
            tag.textContent = '⚠️ ' + kw;
            container.appendChild(tag);
        });

        keywords.general.forEach(kw => {
            const tag = document.createElement('span');
            tag.className = 'keyword-tag';
            tag.textContent = kw;
            container.appendChild(tag);
        });
    }

    // ── VOICE RECORDING ──────────────────────────────────────────
    const recordBtn = document.getElementById('voice_record_btn');
    const textarea  = document.getElementById('quick_notes_field');
    const statusDiv = document.getElementById('voice_status');

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (!SpeechRecognition) {
        recordBtn.textContent = '🎤 Voice notes not available in this browser';
        recordBtn.disabled = true;
        recordBtn.style.opacity = '0.5';
        recordBtn.style.cursor = 'not-allowed';
        statusDiv.innerHTML = '<span style="color:#dc2626;font-size:12px;">💡 Voice recording works best in Chrome or Edge. Use the text box below instead.</span>';
        statusDiv.style.display = 'block';
    } else {
        const recognition = new SpeechRecognition();
        recognition.continuous     = true;
        recognition.interimResults = true;
        recognition.lang           = 'en-NZ';

        let isRecording     = false;
        let finalTranscript = '';

        recordBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (!isRecording) {
                recognition.start();
                isRecording = true;
                recordBtn.classList.add('recording');
                recordBtn.classList.remove('pulsing-red');
                recordBtn.textContent = '⏹️ Tap to Stop Recording';
                statusDiv.textContent = '🔴 Recording — speak clearly...';
                statusDiv.style.color = '#ef4444';
                finalTranscript = textarea.value ? textarea.value + ' ' : '';
            } else {
                recognition.stop();
                isRecording = false;
                recordBtn.classList.remove('recording');
                recordBtn.textContent = '🎤 Tap to Record Voice Note';
                statusDiv.textContent = '✅ Note saved';
                statusDiv.style.color = '#10b981';
                checkForRedTrigger();
                setTimeout(() => { statusDiv.textContent = ''; }, 3000);
            }
        });

        // Convert spoken punctuation words into symbols.
        // Only runs on finalised segments — interim preview stays natural.
        function applySpokenPunctuation(text) {
            return text
                .replace(/\bfull stop\b/gi,           '.')
                .replace(/\bperiod\b/gi,               '.')
                .replace(/\bcomma\b/gi,                ',')
                .replace(/\bquestion mark\b/gi,        '?')
                .replace(/\bexclamation mark\b/gi,     '!')
                .replace(/\bexclamation point\b/gi,    '!')
                .replace(/\bcolon\b/gi,                ':')
                .replace(/\bsemicolon\b/gi,            ';')
                .replace(/\bdash\b/gi,                 ' —')
                .replace(/\bnew line\b/gi,             '\n')
                .replace(/\bnew paragraph\b/gi,        '\n\n')
                // Remove space before punctuation that was just inserted
                .replace(/ ([.,?!:;])/g,               '$1')
                // Capitalise first letter after sentence-ending punctuation
                .replace(/([.?!]\s*)([a-z])/g, (_, p, c) => p + c.toUpperCase());
        }

        recognition.onresult = function(event) {
            let interim = '';
            for (let i = event.resultIndex; i < event.results.length; i++) {
                const t = event.results[i][0].transcript;
                if (event.results[i].isFinal) {
                    finalTranscript += applySpokenPunctuation(t) + ' ';
                } else {
                    interim += t;
                }
            }
            textarea.value = finalTranscript + interim;

            const keywords = detectKeywords(textarea.value);
            renderKeywordTags(keywords);
        };

        recognition.onerror = function(event) {
            isRecording = false;
            recordBtn.classList.remove('recording');
            recordBtn.textContent = '🎤 Tap to Record Voice Note';
            statusDiv.textContent = 'Error: ' + event.error;
            statusDiv.style.color = '#ef4444';
        };

        recognition.onend = function() {
            if (isRecording) recognition.start();
        };
    }

    // ── TEXTAREA KEYWORD DETECTION (typed notes) ─────────────────
    if (textarea) {
        textarea.addEventListener('input', function() {
            const keywords = detectKeywords(this.value);
            renderKeywordTags(keywords);
        });
    }

    // ── PHOTO UPLOAD — real file inputs, normal POST, natural thumbnails ─
    (function() {
        var MAX    = 4;
        var addBtn = document.getElementById('photo-add-btn');
        var grid   = document.getElementById('photo-preview-grid');
        var label  = document.getElementById('photo-count-label');

        if (!addBtn) return;

        var inputs = [
            document.getElementById('photo-file-1'),
            document.getElementById('photo-file-2'),
            document.getElementById('photo-file-3'),
            document.getElementById('photo-file-4')
        ];

        function countFilled() {
            return inputs.filter(function(i) { return i && i.files && i.files.length; }).length;
        }

        function updateButton() {
            var n = countFilled();
            label.textContent = n > 0 ? '(' + n + ' of ' + MAX + ')' : '';
            addBtn.textContent = n === 0 ? '📷 Upload Photo\u00a0' : n < MAX ? '📷 Upload Another Photo\u00a0' : '📷 Maximum reached\u00a0';
            addBtn.appendChild(label);
            addBtn.style.opacity = n >= MAX ? '0.5' : '1';
            addBtn.style.cursor  = n >= MAX ? 'default' : 'pointer';
        }

        function renderPreviews() {
            grid.innerHTML = '';
            var hasAny = false;
            inputs.forEach(function(inp, idx) {
                if (!inp || !inp.files || !inp.files.length) return;
                hasAny = true;
                var url  = URL.createObjectURL(inp.files[0]);
                var wrap = document.createElement('div');
                wrap.style.cssText = 'position:relative;display:inline-block;margin:0;';
                var img  = document.createElement('img');
                img.src  = url;
                img.alt  = 'Photo ' + (idx + 1);
                img.style.cssText = 'display:block;max-width:100%;height:auto;max-height:140px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.15);';
                var xBtn = document.createElement('button');
                xBtn.type = 'button';
                xBtn.dataset.idx = idx;
                xBtn.innerHTML = '&times;';
                xBtn.style.cssText = 'position:absolute;top:-6px;right:-6px;width:24px;height:24px;border-radius:50%;' +
                    'background:#dc2626;color:white;border:none;font-size:15px;line-height:1;cursor:pointer;' +
                    'display:flex;align-items:center;justify-content:center;font-weight:bold;padding:0;';
                xBtn.addEventListener('click', function() {
                    var i = parseInt(this.dataset.idx);
                    // Replace the input with a fresh clone (only way to clear a file input)
                    var fresh = inputs[i].cloneNode(false);
                    inputs[i].parentNode.replaceChild(fresh, inputs[i]);
                    inputs[i] = fresh;
                    inputs[i].addEventListener('change', onchange);
                    renderPreviews();
                    updateButton();
                });
                wrap.appendChild(img);
                wrap.appendChild(xBtn);
                grid.appendChild(wrap);
            });
            grid.style.display = hasAny ? 'flex' : 'none';
            if (hasAny) {
                grid.style.flexWrap = 'wrap';
                grid.style.gap = '10px';
            }
            updateButton();
        }

        function onchange() { renderPreviews(); }

        inputs.forEach(function(inp) {
            if (inp) inp.addEventListener('change', onchange);
        });

        addBtn.addEventListener('click', function() {
            if (countFilled() >= MAX) return;
            for (var i = 0; i < inputs.length; i++) {
                if (inputs[i] && (!inputs[i].files || !inputs[i].files.length)) {
                    inputs[i].click();
                    return;
                }
            }
        });

        updateButton();
    })();

})();

// ── LEARN MORE BUTTON FUNCTIONALITY (jQuery) ─────────────────────
jQuery(document).ready(function($) {
    // Check if mylogUserForm is defined
    if (typeof mylogUserForm === 'undefined') {
        console.warn('mylogUserForm not defined - Learn More will not work. This is OK if user has no people added yet.');
        return;
    }
    
    // Show/hide Learn More button based on user selection
    $('select[name="mylog_user_id"]').on('change', function() {
        const userId = $(this).val();
        const userName = $(this).find('option:selected').text();
        
        if (userId && userName !== '-- Select Person | Kōwhiria te Tangata --') {
            $('#mylog-learn-more-text').text('Learn More about ' + userName);
            $('#mylog-learn-more-container').slideDown(300);
        } else {
            $('#mylog-learn-more-container').slideUp(300);
        }
    });
    
    // Open Learn More modal
    $('#mylog-learn-more-btn').on('click', function(e) {
        e.preventDefault();
        
        const userId = $('select[name="mylog_user_id"]').val();
        if (!userId) return;
        
        const $modal = $('#mylog-learn-more-modal');
        const userName = $('select[name="mylog_user_id"] option:selected').text();
        
        $('#mylog-learn-more-modal-title').text(userName);
        $('#mylog-learn-more-content').html('<p style="text-align:center; padding:40px; color:#6b7280;">Loading...</p>');
        $modal.css('display', 'block');
        
        // Fetch user details
        $.ajax({
            url: mylogUserForm.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mylog_get_user_details',
                nonce: mylogUserForm.nonce,
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    displayUserProfile(response.data.user);
                } else {
                    $('#mylog-learn-more-content').html('<p style="color:#ef4444; text-align:center; padding:20px;">Failed to load profile: ' + (response.data.message || 'Unknown error') + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                $('#mylog-learn-more-content').html('<p style="color:#ef4444; text-align:center; padding:20px;">An error occurred loading the profile.</p>');
            }
        });
    });
    
    // Display user profile in modal
    function displayUserProfile(user) {
        let html = '';
        
        // Photo
        if (user.profile_photo_url) {
            html += '<img src="' + user.profile_photo_url + '" alt="Profile photo" class="mylog-user-profile-photo">';
        }
        
        // Goals & Aspirations
        if (user.person_goals) {
            html += '<div class="mylog-profile-section" style="border-left:3px solid #6366f1;padding-left:12px;background:#f5f3ff;border-radius:0 6px 6px 0;margin-bottom:12px;">';
            html += '<div class="mylog-profile-label" style="color:#6366f1;font-weight:700;">⭐ My Current Goals &amp; Aspirations</div>';
            html += '<div class="mylog-profile-value" style="white-space:pre-wrap;">' + escapeHtml(user.person_goals) + '</div>';
            html += '</div>';
        }
        
        // Full Name
        html += '<div class="mylog-profile-section">';
        html += '<div class="mylog-profile-label">Full Name</div>';
        html += '<div class="mylog-profile-value large">' + escapeHtml(user.full_name) + '</div>';
        html += '</div>';
        
        // Preferred Name
        if (user.preferred_name) {
            html += '<div class="mylog-profile-section">';
            html += '<div class="mylog-profile-label">Preferred Name / Nickname</div>';
            html += '<div class="mylog-profile-value large">' + escapeHtml(user.preferred_name) + '</div>';
            html += '</div>';
        }
        
        // Happy When
        if (user.happy_when) {
            html += '<div class="mylog-profile-section">';
            html += '<div class="mylog-profile-label"><span style="color:#2e7d32; font-size:16px;">●</span> I am happy if</div>';
            html += '<div class="mylog-profile-value">' + escapeHtml(user.happy_when) + '</div>';
            html += '</div>';
        }
        
        // Unhappy When
        if (user.unhappy_when) {
            html += '<div class="mylog-profile-section">';
            html += '<div class="mylog-profile-label"><span style="color:#d32f2f; font-size:16px;">●</span> I am not happy if</div>';
            html += '<div class="mylog-profile-value">' + escapeHtml(user.unhappy_when) + '</div>';
            html += '</div>';
        }
        
        // Communication
        if (user.comm_method) {
            const commLabels = {
                'fully_verbal': 'I am fully verbal',
                'limited_verbal': 'I use some words/phrases',
                'gestures': 'Watch my hands/gestures',
                'tablet': 'I use a tablet/communication device',
                'visual': 'I respond to pictures/visual aids',
                'other': 'Other'
            };
            
            html += '<div class="mylog-profile-section">';
            html += '<div class="mylog-profile-label">Communication Preference</div>';
            html += '<div class="mylog-profile-value">' + (commLabels[user.comm_method] || user.comm_method) + '</div>';
            html += '</div>';
        }
        
        // Communication Notes
        if (user.comm_notes) {
            html += '<div class="mylog-profile-section">';
            html += '<div class="mylog-profile-label">Communication Notes</div>';
            html += '<div class="mylog-profile-value">' + escapeHtml(user.comm_notes) + '</div>';
            html += '</div>';
        }
        
        // Additional Info
        if (user.additional_info) {
            html += '<div class="mylog-profile-section">';
            html += '<div class="mylog-profile-label">Other Important/Emergency Information</div>';
            html += '<div class="mylog-profile-value">' + escapeHtml(user.additional_info) + '</div>';
            html += '</div>';
        }
        
        if (html === '') {
            html = '<p style="text-align:center; color:#6b7280; padding:20px;">No additional information available.</p>';
        }
        
        $('#mylog-learn-more-content').html(html);
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Close modal
    $(document).on('click', '#mylog-learn-more-modal .mylog-modal-close, #mylog-learn-more-modal .mylog-modal-overlay', function(e) {
        e.preventDefault();
        $('#mylog-learn-more-modal').css('display', 'none');
    });
    
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#mylog-learn-more-modal').is(':visible')) {
            $('#mylog-learn-more-modal').css('display', 'none');
        }
    });
});
</script>
<?php }