<?php
/**
 * Template Name: MyLog FAQ Page
 * Template Post Type: page
 * Description: Interactive FAQ page for MyLog with accordion functionality
 */

get_header();

// Disable auto-formatting for this page only
remove_filter('the_content', 'wpautop');
remove_filter('the_content', 'wptexturize');

// FAQ data arrays
$faq_data = array(
    'families' => array(
        'title' => 'FOR FAMILIES & WHĀNAU',
        'questions' => array(
            1 => array(
                'q' => 'How much does MyLog cost?',
                'a' => 'MyLog has three affordable subscription tiers. All plans are month-to-month with no long-term contracts. Cancel anytime with one click. <a href="https://mymate.co.nz/mylog/pricing/" target="_blank" rel="noopener noreferrer">Refer to our Pricing Page</a>'),
            2 => array(
                'q' => 'Who owns the data I enter into MyLog?',
                'a' => 'You do. All information entered into MyLog belongs to you and your whānau. You control who has access, and you can export your data as a professional report at any time.'),
            3 => array(
                'q' => 'Is MyLog focused on disabilities or on the person?',
                'a' => 'MyLog is person-first. It captures daily life, preferences, routines, and what works, not diagnoses or clinical notes. It helps carers understand the person behind the support, supporting dignity, consistency, and better relationships.'),
            4 => array(
                'q' => 'Is MyLog private and secure?',
                'a' => 'Yes. MyLog is hosted on HostPapa VPS (Virtual Private Server) with full data encryption. We comply with New Zealand\'s Privacy Act 2020 and use secure, encrypted storage. Only people you invite can see your log, and we never share your data with third parties.<a href="https://mymate.co.nz/mylog/privacypolicy/" target="_blank" rel="noopener noreferrer">Refer to our Privacy Policy</a>'
            ),
            5 => array(
                'q' => 'Does MyLog store medical or clinical information?',
                'a' => 'No. MyLog is designed specifically for personal, day-to-day information. Routines, preferences, what works, what doesn\'t. It records the individual, not the disability. It does not replace medical records or clinical documentation.'
            ),
            6 => array(
                'q' => 'Can I use MyLog for funding applications or NASC assessments?',
                'a' => 'Yes. With the Total Plan, your data is exportable to PDF, which you can use for funding meetings, NASC assessments, or reviews with Kaikaranga and other support providers.'
            ),
            7 => array(
                'q' => 'How do I invite a carer or support worker to access my log?',
                'a' => 'Go to your Dashboard > Invite Caregiver, enter their email address, and they\'ll receive an invite link. They can start viewing and adding entries immediately.'
            ),
            8 => array(
                'q' => 'Can I remove a carer\'s access if they\'re no longer supporting my loved one?',
                'a' => 'Yes. You control all access via your Dashboard > Manage Users. You can remove a carer at any time, and they will no longer be able to view or add entries.'
            ),
            9 => array(
                'q' => 'How long does it take to add a daily entry?',
                'a' => 'Most entries take under 3 minutes, subject to your internet speed. The platform is designed to be fast and simple, quick taps, minimal typing.'
            ),
            10 => array(
                'q' => 'Why use MyLog instead of a paper diary or notebook?',
                'a' => 'Paper diaries are easy to lose, forget, or leave behind when support changes. MyLog keeps everything in one secure place, accessible to the people you choose, and portable across carers and organisations. Your story doesn’t disappear when circumstances change.'
            ),
            11 => array(
                'q' => 'What happens to our information if we change carers or support organisations?',
                'a' => 'Nothing changes. Your MyLog stays with your family. Unlike agency-owned systems or portals, MyLog belongs to you. When carers or organisations change, your history, routines, and insights stay intact and can be shared with the next team - no starting over.'
            ),
            12 => array(
                'q' => 'What happens if I stop using MyLog?',
                'a' => 'You can export all entries to PDF (Total Plan only) at any time and take them with you. If you cancel your subscription, your access to the platform will end. Your data is deleted from our servers in accordance with our backup routines and privacy policy.'
            ),
            13 => array(
                'q' => 'Is MyLog available on mobile?',
                'a' => 'Yes. MyLog works on any device - desktop, tablet, or smartphone. It\'s designed to be easy to use on the go.'
            )
        )
    ),
    
    'carers' => array(
        'title' => 'FOR CARERS & SUPPORT WORKERS',
        'questions' => array(
            14 => array(
                'q' => 'Do I need to create my own account or pay to use MyLog?',
                'a' => 'No. When a family invites you, you\'ll receive an email link. You can view and add entries without creating a separate account, and you don\'t have to pay anything.'
            ),
            15 => array(
                'q' => 'Can I see entries made by other carers?',
                'a' => 'Yes. All carers invited by the family can see the full log of the user they\'re supporting. This ensures everyone is on the same page and knows what\'s been happening.'
            ),
            16 => array(
                'q' => 'What if I don\'t have time to write long entries every day?',
                'a' => 'You don\'t need to. MyLog is designed for quick, structured entries, just the key details. Most carers complete an entry in under 3 minutes, subject to internet speeds. There is also an audio note provision if you prefer to speak rather than type.'
            ),
            17 => array(
                'q' => 'How does MyLog help when multiple carers are involved?',
                'a' => 'Everyone sees the same up-to-date information. MyLog reduces handover gaps by keeping routines, preferences, and recent notes in one place, so care stays consistent even when shifts or staff change.'
            ),
            18 => array(
                'q' => 'Will using MyLog slow me down during busy shifts?',
                'a' => 'No. MyLog is designed for speed. Entries are structured and quick. Most carers complete them in under 3 minutes (subject to internet speeds) helping you record what matters without adding administrative burden.'
            ),
            19 => array(
                'q' => 'Is MyLog used to monitor or assess carers?',
                'a' => 'No. MyLog is a shared communication tool, not a performance system. It exists to support continuity, understanding, and better care, not compliance, rostering, or auditing.'
            ),
            20 => array(
                'q' => 'Can I also add photos to an entry?',
                'a' => 'Yes. You can upload photos to the log entry. Document activities, achievements, or anything visual that helps tell the story.'
            )
        )
    ),
    
    'organizations' => array(
        'title' => 'FOR DISABILITY SUPPORT ORGANISATIONS',
        'questions' => array(
            21 => array(
                'q' => 'How is MyLog different from medical or clinical record systems?',
                'a' => 'MyLog doesn\'t replace medical records. It\'s designed specifically for the personal, day-to-day information that gets lost. Routines, preferences, what works, what doesn\'t. MyLog records the individual, not the disability. It\'s based on Te Whare Tapa Whā, capturing taha hinengaro (mental/emotional), taha whānau (social), and taha wairua (spiritual) alongside everyday routines.'
            ),
            22 => array(
                'q' => 'Is MyLog compliant with NZ privacy laws?',
                'a' => 'Yes. MyLog complies with the Privacy Act 2020 and the Health Information Privacy Code 2020. All data is hosted on HostPapa VPS with full encryption, securely stored, and only accessible to people the family invites.'
            ),
            23 => array(
                'q' => 'Can we recommend MyLog to the families we support?',
                'a' => 'Absolutely. MyLog is a subscription-based model designed to work alongside your existing services. We welcome support organisations sharing MyLog with the whānau and carers they work with.'
            ),
            24 => array(
                'q' => 'Can MyLog integrate with our existing systems?',
                'a' => 'Not yet. MyLog currently operates as a standalone platform. However, we\'re open to conversations about partnerships and integrations in the future.'
            ),
            25 => array(
                'q' => 'Does MyLog replace our internal systems or documentation?',
                'a' => 'No. MyLog is not a rostering, billing, or compliance system. It complements your existing tools by preserving family-owned, person-centred knowledge that often sits outside formal documentation.'
            ),
            26 => array(
                'q' => 'Who owns the data in MyLog?',
                'a' => 'Families do. MyLog is intentionally family-owned and provider-agnostic. Organisations can be invited to contribute, but control always remains with the whānau.'
            ),
            27 => array(
                'q' => 'How does MyLog benefit my organisation?',
                'a' => 'MyLog reduces onboarding friction, improves continuity of care, and helps staff quickly understand the person beyond formal records. This leads to better relationships, fewer misunderstandings, and smoother transitions.'
            ),
            28 => array(
                'q' => 'How can we get in touch if we want to explore a partnership?',
                'a' => 'Email <a href="mailto:info@mymate.co.nz">info@mymate.co.nz</a>. We\'re open to kōrero about how MyLog can support the work you do with your whānau and tāngata whaikaha.'
            )
        )
    )
);
?>

<style>
/* FAQ CSS - Embedded directly to avoid file path issues */
.mylog-faq-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.mylog-faq-intro h1 {
    color: #1e40af;
    text-align: center;
    margin-bottom: 10px;
    font-size: 28px;
    line-height: 1.3;
}

.mylog-faq-intro p {
    text-align: center;
    color: #64748b;
    margin-bottom: 30px;
    font-size: 16px;
    line-height: 1.5;
}

/* Section headings */
.mylog-faq-section-heading {
    background: #5dade2;
    color: #fff;
    padding: 12px 20px;
    margin: 40px 0 20px 0;
    font-size: 18px;
    font-weight: 700;
    border-radius: 8px;
    line-height: 1.4;
}

/* FAQ item */
.mylog-faq-item {
    margin-bottom: 15px;
    border: 3px solid #64748b;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
}

/* Question button */
.mylog-faq-question {
    width: 100%;
    background: #f8fafc;
    border: none;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    text-align: left;
    font-size: 17px;
    font-weight: 600;
    color: #1e293b;
    cursor: pointer;
    min-height: 60px;
    line-height: 1.4;
    transition: background 0.2s;
}

.mylog-faq-question:hover {
    background: #c9ddf0 !important;
    color: #1e293b !important;
    opacity: 1 !important;
    border: none !important;
    box-shadow: none !important;
    text-shadow: none !important;
    transform: none !important;
    filter: none !important;
}

.mylog-faq-question:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Plus / minus icon */
.mylog-faq-icon {
    font-size: 24px;
    color: #5dade2;
    flex-shrink: 0;
    margin-left: 15px;
    font-weight: bold;
    min-width: 20px;
    text-align: center;
    transition: transform 0.3s;
}

/* Answer content - HIDDEN by default */
.mylog-faq-answer {
    display: none;
    padding: 0 20px 20px 20px;
    background: #fff;
    color: #475569;
    line-height: 1.6;
    animation: fadeIn 0.3s ease;
}

.mylog-faq-answer.open {
    display: block;
}

.mylog-faq-answer p {
    margin: 0 0 15px 0;
    font-size: 16px;
    line-height: 1.6;
}

.mylog-faq-answer p:last-child {
    margin-bottom: 0;
}

/* Animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Status message */
#faq-status {
    text-align: center;
    padding: 10px;
    color: #3b82f6;
    font-weight: 600;
    font-size: 15px;
    background: #f0f9ff;
    border-radius: 8px;
    margin: 20px 0;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .mylog-faq-container {
        padding: 15px;
        border-radius: 12px;
        margin: 0 10px;
    }
    
    .mylog-faq-intro h1 {
        font-size: 24px;
    }
    
    .mylog-faq-intro p {
        font-size: 15px;
    }
    
    .mylog-faq-section-heading {
        font-size: 16px;
        padding: 10px 15px;
        margin: 30px 0 15px 0;
    }
    
    .mylog-faq-question {
        font-size: 15px;
        padding: 15px;
        min-height: 55px;
    }
    
    .mylog-faq-icon {
        font-size: 22px;
        margin-left: 12px;
    }
    
    .mylog-faq-answer {
        padding: 0 15px 15px 15px;
    }
    
    .mylog-faq-answer p {
        font-size: 15px;
    }
    
    #faq-status {
        font-size: 14px;
        padding: 8px;
    }
}

@media (max-width: 480px) {
    .mylog-faq-container {
        padding: 12px;
        border-radius: 8px;
    }
    
    .mylog-faq-intro h1 {
        font-size: 20px;
    }
    
    .mylog-faq-intro p {
        font-size: 16px;
    }
    
    .mylog-faq-section-heading {
        font-size: 15px;
        padding: 10px 12px;
    }
    
    .mylog-faq-question {
        font-size: 14px;
        padding: 12px;
    }
    
    .mylog-faq-icon {
        font-size: 20px;
        margin-left: 10px;
    }
}
</style>

<div class="site-content">
    <div class="content-area">
        <main class="site-main">
            
            <!-- FAQ Container -->
            <div class="mylog-faq-container">
                <div class="mylog-faq-intro">
                    <h1>Frequently Asked Questions | Ngā Pātai Auau</h1>
    <p>For just around <strong>50 Cents/day </strong> you get ownership and continuity to keep the story, routines, and knowledge safe, <br> no matter who comes or goes in your loved one's life.</p>
                </div>
                
                <div id="faq-status">Click any question to see the answer</div>
                
                <?php foreach ($faq_data as $section): ?>
                    <div class="mylog-faq-section">
                        <h2 class="mylog-faq-section-heading"><?php echo esc_html($section['title']); ?></h2>
                        
                        <?php foreach ($section['questions'] as $num => $faq): ?>
                            <div class="mylog-faq-item" id="faq-item-<?php echo $num; ?>">
                                <button class="mylog-faq-question" type="button" onclick="mylogToggleFAQ(<?php echo $num; ?>)">
                                    <?php echo $num; ?>. <?php echo esc_html($faq['q']); ?>
                                    <span class="mylog-faq-icon" id="faq-icon-<?php echo $num; ?>">+</span>
                                </button>
                                <div class="mylog-faq-answer" id="faq-answer-<?php echo $num; ?>" style="display: none;">
                                    <p><?php echo wp_kses_post($faq['a']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
        </main>
    </div>
</div>

<script>
// FAQ Toggle Function - SIMPLE and RELIABLE
function mylogToggleFAQ(num) {
    
    var answer = document.getElementById('faq-answer-' + num);
    var icon = document.getElementById('faq-icon-' + num);
    var status = document.getElementById('faq-status');
    
    if (!answer || !icon) {
        console.error('FAQ elements not found for #' + num);
        return;
    }
    
    // Check if answer is currently open
    var isOpen = answer.style.display === 'block' || answer.classList.contains('open');
    
    // Close ALL other FAQs first (accordion behavior)
    document.querySelectorAll('.mylog-faq-answer').forEach(function(otherAnswer) {
        if (otherAnswer !== answer) {
            otherAnswer.style.display = 'none';
            otherAnswer.classList.remove('open');
        }
    });
    
    // Reset ALL icons
    document.querySelectorAll('.mylog-faq-icon').forEach(function(otherIcon) {
        if (otherIcon !== icon) {
            otherIcon.textContent = '+';
        }
    });
    
    // Toggle current FAQ
    if (!isOpen) {
        // Open this FAQ
        answer.style.display = 'block';
        answer.classList.add('open');
        icon.textContent = '−';
        
        // Smooth scroll to make sure FAQ is visible
        answer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
        if (status) {
            status.textContent = 'Answer opened';
            status.style.background = '#d1fae5';
            status.style.color = '#065f46';
        }
    } else {
        // Close this FAQ
        answer.style.display = 'none';
        answer.classList.remove('open');
        icon.textContent = '+';
        
        if (status) {
            status.textContent = 'Click any question to see the answer';
            status.style.background = '#f0f9ff';
            status.style.color = '#3b82f6';
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    
    // Hide all answers on page load
    document.querySelectorAll('.mylog-faq-answer').forEach(function(answer) {
        answer.style.display = 'none';
    });
    
    // Optional: Open first FAQ by default (uncomment if wanted)
    // setTimeout(function() {
    //     var firstButton = document.querySelector('.mylog-faq-question');
    //     if (firstButton) firstButton.click();
    // }, 500);
});

// Fallback for if DOMContentLoaded already fired
if (document.readyState === 'interactive' || document.readyState === 'complete') {
    document.dispatchEvent(new Event('DOMContentLoaded'));
}
</script>

<?php
// Re-enable auto-formatting for other pages
add_filter('the_content', 'wpautop');
add_filter('the_content', 'wptexturize');

get_footer();
?>