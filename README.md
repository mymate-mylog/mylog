MyLog
A wellbeing and support diary for disabled people, their whānau, and caregivers - built on the Te Whare Tapa Whā holistic health framework.
He aha te mea nui o te ao? He tāngata, he tāngata, he tāngata. What is the greatest thing in the world? It is people, it is people, it is people.

What is MyLog?
MyLog is a purpose-built digital support diary for Aotearoa New Zealand's disability and aged care community. It gives family administrators, caregivers, and support workers a simple, structured way to record daily observations - and turns those records into meaningful reports for NASC assessments, funding reviews, and care planning meetings.
Built by a one-person company, for real families, with real needs.

Who is it for?
•	Family Sdministrators - parents, guardians, and whānau managing care for a disabled or elderly family member
•	Caregivers and Support workers - daily frontline staff recording observations and shift notes
•	NASC/ACC coordinators and Support providers - receiving structured, exportable reports as evidence of need

Core Features
📖 Daily Support Diary
Record each support shift with structured traffic light indicators across four Te Whare Tapa Whā domains:
•	Taha Tinana (Physical) - meals, hygiene, bathing, dressing, mobility
•	Taha Hinengaro (Mind) - memory, focus, communication, problem-solving
•	Taha Whānau (Social) - family contact, community, hobbies, group activities
•	Taha Wairua (Spiritual) - cultural connection, nature, identity and belonging
Each entry captures a full picture of the person's day - not just tasks completed, but how the person was, how the support environment felt, and what mattered.
🎤 Voice-to-Text Notes
Caregivers can dictate notes hands-free during or after a shift. Spoken punctuation commands are supported:
•	Say "full stop" → .
•	Say "comma" → ,
•	Say "question mark" → ?
•	Say "new line" → line break
•	Say "new paragraph" → paragraph break
📄 PDF Export 
Generate a professional, print-ready report covering any date range - today, this week, a custom period, or all entries. Reports include:
•	Wellbeing trend analysis across all four domains
•	Stability scores and friction index
•	Weekly digest tables
•	Caregiver sustainability indicators
•	Suitable for ACC/NASC assessments, funding applications, and MDT meetings
👥 Multi-User Management
•	One Family Administrator manages multiple people being supported
•	Invite caregivers by email with controlled access per person
•	Temporary password system with forced change on first login
📊 Wellbeing Scoring
Every entry automatically calculates:
•	Wellbeing Score - overall across all four domains
•	Carer Score - support environment health
•	Stability Score - combined indicator
•	Friction Index - gap between person wellbeing and carer sustainability
•	Sustainability Alert - flags when a person is thriving but their carer is struggling
🌐 Bilingual Interface
English and Te Reo Māori throughout - labels, headings, success messages, and navigation.

Subscription Plans
Plan	Price	Profiles
Individual	$15/month	1 Family Administrator + 1 User Profile
Family	$25/month	1 Family Administrator + 3 User Profiles
Total	$35/month	1 Family Administrator + 5 User Profiles + PDF Export

Tech Stack
•	Platform - WordPress (Kadence child theme)
•	Subscriptions - Paid Member Subscriptions (PMS)
•	PDF Generation - TCPDF
•	Speech Recognition - Web Speech API (Chrome / Edge)
•	Language - PHP 8.4, JavaScript (jQuery), CSS3
________________________________________
Architecture Overview
mylog/
├── functions.php                        # Module loader, session management, nav
├── style.css                            # Mobile-first app stylesheet
├── faq-template.php                     # FAQ page template
├── inc/
│   ├── custom-post-types.php            # mylog_user and mylog_entry post types
│   ├── helpers.php                      # Access control helpers
│   ├── subscription-limits.php         # Plan tier enforcement
│   ├── hooks.php                        # Security, form processing, admin pages
│   ├── shortcodes.php                   # Dashboard, manage users, pricing UX
│   ├── enhancements.php                 # Diary filter, entry display, PDF gate
│   ├── mylog-hybrid-form.php            # Main entry form (Te Whare Tapa Whā v4.1)
│   ├── mylog-form-handlers.php          # Entry and user form submission handlers
│   ├── mylog-professional-pdf-v4.1.php  # PDF report generation
│   ├── add-user-form-enhanced.php       # Add/edit person profile
│   ├── diary-user-info-integration.php  # Person profile popup on diary page
│   ├── enqueue.php                      # Script and style registration
│   └── diagnostics.php                 # Admin-only diagnostic panel
├── js/
│   ├── mylog-enhancements.js            # Time picker, voice UX, form validation
│   ├── mylog-user-form.js               # Add/edit user form behaviour
│   └── mylog-edit-handler.js            # Edit person modal handler
└── css/
    ├── mylog-user-form.css              # Person profile form styles
    └── diary-user-info-styles.css       # Diary page person info styles

Security
•	All form submissions protected by WordPress nonces
•	Destructive GET actions (remove person, remove caregiver) nonce-verified
•	Role-based access control - administrator, family_admin, caregiver
•	All user input sanitized via WordPress sanitization functions
•	No debug output or error display in production
•	Session management safe for REST API and AJAX contexts

Deployment
MyLog is a WordPress child theme. To deploy:
1.	Install WordPress with the Kadence parent theme
2.	Install Paid Member Subscriptions plugin
3.	Install TCPDF in /vendor/tcpdf/
4.	Upload this theme to /wp-content/themes/kadence-child/
5.	Activate the theme
6.	Create subscription plans: Individual ($15), Family ($25), Total ($35)
7.	Set WordPress timezone to your local timezone (critical for date accuracy)
8.	Configure WP Mail SMTP for caregiver invitation emails

Licence
GNU Affero General Public License v3.0
This program is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
This means: you are free to use, study, and modify this code. If you run a modified version as a hosted service, you must publish your modifications under the same licence.
See LICENSE for full terms - https://www.gnu.org/licenses/agpl-3.0.html

Copyright
Copyright © 2026 | Ajit Kumar Nair | MyMate Limited | www.mylog.co.nz

Contributing
MyLog was built for and with the NZ disability community. Contributions that improve accessibility, cultural responsiveness, or caregiver experience are welcome.
If you work in disability support, aged care, or digital health in Aotearoa and want to contribute - open an issue or get in touch via www.mylog.co.nz.

Acknowledgements
Built on Te Whare Tapa Whā - the four-sided house model of Māori health developed by Sir Mason Durie. This framework grounds MyLog in a holistic, culturally responsive view of wellbeing that goes beyond clinical checklists.
Nō reira, tēnā koutou, tēnā koutou, tēnā koutou katoa.

