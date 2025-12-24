# Contact Form Integration - User Stories

This document contains the user stories for the TCS Woodwork Contact Form Integration system, which captures leads from the website, displays them in the Kanban inbox, and enables conversion to Partners + Projects with HubSpot sync.

---

## US-CF-1: Submit Contact Form (Website Visitor)

**As a** potential customer visiting tcswoodwork.com
**I want to** submit a detailed contact form with my project information
**So that** TCS Woodwork can understand my needs and follow up with a consultation

### Acceptance Criteria:
- [x] Multi-step form with 3 sections: Personal Info, Project Details, Review & Submit
- [x] Required fields: firstname, lastname, email, phone, processing_consent
- [x] Optional project fields: project_type, project_description, budget_range, timeline, design_style, wood_species
- [x] Address fields for project location
- [x] Cloudflare Turnstile CAPTCHA validation
- [x] Honeypot fields for spam detection
- [x] Form submission creates record in `leads` table
- [x] Success confirmation message displayed
- [x] Email notification sent to TCS team

---

## US-CF-2: API Endpoint for External Form

**As a** website developer
**I want to** post form data to an AureusERP API endpoint
**So that** the TCS website can capture leads without hosting the form internally

### Acceptance Criteria:
- [x] POST `/api/contact` endpoint accepts JSON form data
- [x] CORS headers configured for tcswoodwork.com domain
- [x] Turnstile token validation on server
- [x] Rate limiting (5 submissions/hour per IP)
- [x] Returns JSON response with success/error status
- [x] Spam protection middleware validates honeypot fields
- [x] Lead record created in database on success
- [x] HubSpot contact/deal created asynchronously

---

## US-CF-3: Native Contact Form Page

**As a** potential customer
**I want to** access a contact form directly on the AureusERP platform
**So that** I can submit my project inquiry without visiting the main website

### Acceptance Criteria:
- [x] Livewire multi-step form component
- [x] Same validation rules as API endpoint
- [x] Pre-fills returning customer data if logged in
- [x] Mobile-responsive design
- [ ] Public page at `/contact` (no auth required) - Route needs to be exposed

---

## US-CF-4: Spam Protection

**As a** system administrator
**I want to** prevent spam and bot submissions
**So that** the leads database contains only legitimate inquiries

### Acceptance Criteria:
- [x] Honeypot fields (website, url, _gotcha) must be empty
- [x] Timestamp check - form must take >3 seconds to submit
- [x] Rate limiting: 5 submissions/hour per IP
- [x] Bot user-agent detection (curl, python, wget, etc.)
- [x] Content-based spam scoring (spam keywords, disposable emails, gibberish detection)
- [x] IP blocking for repeat offenders (24-hour block)
- [x] Turnstile CAPTCHA validation
- [x] Spam score >= 5 rejects submission silently

---

## US-CF-5: View Leads in Kanban Inbox

**As a** project manager
**I want to** see new leads in a collapsible "Leads" column on the Kanban board
**So that** I can review and qualify incoming project inquiries

### Acceptance Criteria:
- [x] "Leads" column shows leads (not projects) with NEW badge
- [x] Lead cards display: name, email, project type, budget range, days since submission
- [x] Cards show lead source (website, referral, etc.)
- [x] Click card to expand lead details panel
- [x] Leads count badge in column header
- [x] Sort by submission date (newest first)
- [ ] Filter by lead source and date range - Future enhancement

---

## US-CF-6: View Lead Details

**As a** sales coordinator
**I want to** view complete lead information in a detail panel
**So that** I can understand the prospect's needs before reaching out

### Acceptance Criteria:
- [x] Slide-over panel opens when clicking lead card
- [x] Displays all form fields in readable format
- [x] Shows contact info: name, email, phone, company, preferred contact method
- [x] Shows project details: type, description, budget, timeline, design preferences
- [x] Shows address information if provided
- [ ] Shows HubSpot contact/deal links if synced - Future enhancement
- [ ] Activity log of all interactions - Future enhancement
- [ ] Notes/comments section for internal use - Future enhancement

---

## US-CF-7: Convert Lead to Project

**As a** project manager
**I want to** convert a qualified lead into a Partner and Project
**So that** I can begin the formal project workflow

### Acceptance Criteria:
- [x] "Convert to Project" action button on lead detail panel
- [x] Creates Partner record from lead contact info (or links to existing Partner by email)
- [x] Creates Project in "To Do" stage with lead data
- [x] Maps lead fields to project fields:
  - lead.first_name + last_name -> partner.name
  - lead.email -> partner.email
  - lead.phone -> partner.phone
  - lead.company_name -> partner.company
  - lead.project_type -> project.project_type
  - lead.budget_range -> project.budget_range
  - lead.message + project_description -> project.description
  - lead.source -> project.lead_source
- [x] Creates project address from lead address fields
- [x] Updates lead status to "converted"
- [x] Links lead record to created project
- [x] Lead disappears from Inbox, Project appears in "To Do" column
- [ ] Triggers HubSpot deal stage update - Future enhancement

---

## US-CF-8: Update Lead Status

**As a** sales coordinator
**I want to** change a lead's status (new, contacted, qualified, disqualified)
**So that** I can track lead progress and filter the inbox

### Acceptance Criteria:
- [x] Status options: new, contacted, qualified, disqualified, converted
- [x] Disqualify action on lead detail panel
- [ ] Status dropdown in lead detail panel - Future enhancement
- [ ] Disqualified status requires reason selection - Future enhancement
- [ ] Status change logged in activity history - Future enhancement
- [ ] Filter inbox by status - Future enhancement

---

## US-CF-9: HubSpot Contact Sync

**As a** marketing coordinator
**I want to** automatically sync leads to HubSpot CRM
**So that** our marketing team can track lead sources and nurture campaigns

### Acceptance Criteria:
- [x] Lead creation triggers HubSpot contact create/update
- [x] Maps form fields to HubSpot contact properties
- [x] Stores hubspot_contact_id on lead record
- [x] Creates HubSpot deal in "Initial Contact" stage
- [x] Stores hubspot_deal_id on lead record
- [x] Deal value set from budget_range mapping
- [x] Lead source tracked in HubSpot
- [x] Handles existing contacts (update vs. create)
- [x] Async job processing (queue) for reliability

---

## US-CF-10: Email Notifications

**As a** project manager
**I want to** receive email notifications for new leads
**So that** I can respond quickly to potential customers

### Acceptance Criteria:
- [x] Email sent to configured notification address on lead creation
- [x] Email includes: contact name, email, phone, project type, message excerpt
- [x] Email includes link to lead detail in admin panel
- [x] Email template matches TCS branding
- [x] Configurable notification recipients via env variable

---

## US-CF-11: Lead Analytics Dashboard (Future)

**As a** business owner
**I want to** see lead conversion metrics and source analytics
**So that** I can measure marketing effectiveness and sales performance

### Acceptance Criteria:
- [ ] Dashboard widget showing: total leads, conversion rate, avg response time
- [ ] Lead source breakdown (pie chart)
- [ ] Lead status distribution
- [ ] Leads by month trend chart
- [ ] Budget range distribution
- [ ] Time-to-conversion metric

---

## US-CF-12: Lead Assignment (Future)

**As a** sales manager
**I want to** assign leads to specific team members
**So that** I can distribute workload and track accountability

### Acceptance Criteria:
- [ ] Assign user dropdown in lead detail panel
- [ ] Assignment notification email to assigned user
- [ ] Filter inbox by assigned user
- [ ] "My Leads" quick filter option
- [ ] Unassigned leads highlighted
- [ ] Assignment logged in activity history

---

## Implementation Summary

### Completed Features:
1. **Leads Plugin** - Full plugin architecture with models, migrations, resources
2. **Contact Form** - Livewire multi-step form with validation
3. **API Endpoint** - POST /api/contact with spam protection
4. **Spam Protection** - Honeypots, rate limiting, bot detection, content scoring
5. **Turnstile CAPTCHA** - Cloudflare integration for bot prevention
6. **Kanban Integration** - Leads inbox column on project board
7. **Lead Details Modal** - Slide-over panel with full lead info
8. **Lead Conversion** - Partner + Project creation from leads
9. **HubSpot Sync** - Async job for CRM integration
10. **Email Notifications** - Team alerts on new leads

### Files Created:
- `plugins/webkul/leads/` - Complete plugin directory
- `plugins/webkul/leads/src/Models/Lead.php` - Lead Eloquent model
- `plugins/webkul/leads/src/Enums/LeadStatus.php` - Status enum
- `plugins/webkul/leads/src/Enums/LeadSource.php` - Source enum
- `plugins/webkul/leads/src/Filament/Resources/LeadResource.php` - Admin CRUD
- `plugins/webkul/leads/src/Http/Controllers/Api/ContactController.php` - API
- `plugins/webkul/leads/src/Http/Middleware/SpamProtectionMiddleware.php` - Spam filter
- `plugins/webkul/leads/src/Rules/SpamProtection.php` - Content validation
- `plugins/webkul/leads/src/Services/LeadConversionService.php` - Conversion logic
- `plugins/webkul/leads/src/Jobs/SyncLeadToHubSpotJob.php` - CRM sync
- `plugins/webkul/leads/src/Jobs/SendLeadNotificationJob.php` - Email job
- `plugins/webkul/leads/src/Mail/NewLeadNotification.php` - Mailable
- `plugins/webkul/leads/src/Livewire/ContactForm.php` - Form component
- `plugins/webkul/leads/resources/views/livewire/contact-form.blade.php` - Form UI
- `plugins/webkul/leads/resources/views/emails/new-lead-notification.blade.php` - Email template

### Files Modified:
- `bootstrap/providers.php` - Added LeadServiceProvider
- `bootstrap/plugins.php` - Added LeadPlugin
- `config/services.php` - Added Turnstile and leads config
- `plugins/webkul/projects/src/Filament/Pages/ProjectsKanbanBoard.php` - Leads integration
- `plugins/webkul/projects/resources/views/kanban/kanban-board.blade.php` - Leads inbox UI

### Environment Variables Required:
```env
# Cloudflare Turnstile
TURNSTILE_SITE_KEY=your_site_key
TURNSTILE_SECRET_KEY=your_secret_key

# Lead Notifications
LEAD_NOTIFICATION_EMAIL=leads@tcswoodwork.com

# HubSpot (existing)
HUBSPOT_ACCESS_TOKEN=your_token
HUBSPOT_OWNER_ID=your_owner_id
```

---

## Testing Checklist

- [ ] Form submission creates lead record
- [ ] Spam protection blocks bot submissions
- [ ] Honeypot fields reject filled values
- [ ] Rate limiting enforced
- [ ] Turnstile validation works
- [ ] HubSpot contact created
- [ ] HubSpot deal created
- [ ] Email notification sent
- [ ] Lead appears in Kanban inbox
- [ ] Lead detail panel displays all data
- [ ] Convert to Project creates Partner
- [ ] Convert to Project creates Project
- [ ] Converted lead removed from inbox
- [ ] Project appears in To Do column
