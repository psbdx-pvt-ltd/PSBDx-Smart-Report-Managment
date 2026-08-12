<div align="center">

<img src="https://ps.w.org/psbdx-smart-report-management/assets/banner-1544x500.png?rev=3521480" alt="PSBDx Smart Report Management banner" width="100%">

<img src="https://ps.w.org/psbdx-smart-report-management/assets/icon-256x256.png?rev=3521480" alt="PSBDx Smart Report Management icon" width="72" height="72">

# PSBDx Smart Report Management

**A fast, AI-assisted support ticket & complaint system for WordPress.**

Instant AJAX forms · Ticket IDs · AI triage · WooCommerce & LearnPress integration · External REST API

[![Version](https://img.shields.io/badge/version-1.4.6-1a3cff?style=flat-square)](#-changelog)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759b?style=flat-square&logo=wordpress&logoColor=white)](#-requirements)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4?style=flat-square&logo=php&logoColor=white)](#-requirements)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-00c896?style=flat-square)](#-license)
[![HPOS](https://img.shields.io/badge/WooCommerce%20HPOS-compatible-96588a?style=flat-square)](#-requirements)

[![VirusTotal](https://img.shields.io/badge/VirusTotal-0%2F65%20clean-3bb143?style=flat-square)](https://www.virustotal.com/gui/file/56afc5cc9b6c24a3be3c2f3328d28bc916eb93b8a4a184ce1340410fd524c60a/summary)
[![amwscan](https://img.shields.io/badge/amwscan-no%20threats%20found-2ecc71?style=flat-square)](#-security--verification)
[![Plugin Check](https://img.shields.io/badge/WordPress%20Plugin%20Check-passed-21759b?style=flat-square&logo=wordpress&logoColor=white)](#-security--verification)
[![WordPress.org](https://img.shields.io/badge/WordPress.org-directory%20ready-21759b?style=flat-square&logo=wordpress&logoColor=white)](#-security--verification)
[![WPCS](https://img.shields.io/badge/WPCS-coding%20standards-8892bf?style=flat-square)](#-security--verification)
[![PHP Lint](https://img.shields.io/badge/PHP%20Lint-no%20syntax%20errors-8892bf?style=flat-square)](#-security--verification)

[Features](#-features) · [Installation](#-installation) · [Quick Start](#-quick-start) · [Security](#-security--verification) · [External API](#-external-api) · [Developer Hooks](#-developer-hooks) · [FAQ](#-faq)

</div>

<br>

## What it does

Customers report a problem — with an order, a product, a course, or anything else — through a fast AJAX modal that never reloads the page. Every submission lands in a clean, organized admin inbox with its own human-readable **Ticket ID**, ready to be triaged, replied to, and resolved — optionally with an AI doing the sorting for you.

No support-desk bloat, no separate helpdesk to learn, no per-agent pricing. Just a focused report/complaint system that plugs straight into WooCommerce and LearnPress, and hands off to an external system via its own REST API when you need it to.

<br>

## <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f6e1.png" width="18" height="18" style="vertical-align:-3px" alt=""> Security & Verification

Every release is scanned before it ships. Nothing here replaces your own review — but it's a running record of what's been checked.

| Check | Tool | Result |
|---|---|---|
| <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f9a0.png" width="18" height="18" style="vertical-align:-3px" alt=""> Malware / virus scan | [VirusTotal](https://www.virustotal.com/gui/file/56afc5cc9b6c24a3be3c2f3328d28bc916eb93b8a4a184ce1340410fd524c60a/summary) (65 AV engines) | <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/2705.png" width="18" height="18" style="vertical-align:-3px" alt=""> 0/65 — [view report](https://www.virustotal.com/gui/file/56afc5cc9b6c24a3be3c2f3328d28bc916eb93b8a4a184ce1340410fd524c60a/summary) |
| <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f50d.png" width="18" height="18" style="vertical-align:-3px" alt=""> WordPress-specific malware scan | [amwscan](https://github.com/wpsec/amwscan) | <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/2705.png" width="18" height="18" style="vertical-align:-3px" alt=""> No threats found |
| <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/2705.png" width="18" height="18" style="vertical-align:-3px" alt=""> Official plugin standards check | [WordPress Plugin Check (PCP)](https://wordpress.org/plugins/plugin-check/) | <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/2705.png" width="18" height="18" style="vertical-align:-3px" alt=""> Passed — no blocking errors |
| <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f4c1.png" width="18" height="18" style="vertical-align:-3px" alt=""> Directory readiness | [WordPress Plugin Directory](https://wordpress.org/plugins/) guidelines | <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/2705.png" width="18" height="18" style="vertical-align:-3px" alt=""> Meets submission requirements |
| <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f9f9.png" width="18" height="18" style="vertical-align:-3px" alt=""> Coding standards | PHP_CodeSniffer + [WPCS](https://github.com/WordPress/WordPress-Coding-Standards) | <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/2705.png" width="18" height="18" style="vertical-align:-3px" alt=""> No unresolved sniffs |
| <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f9ea.png" width="18" height="18" style="vertical-align:-3px" alt=""> Static analysis | PHP lint (`php -l`) across all source files | <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/2705.png" width="18" height="18" style="vertical-align:-3px" alt=""> No syntax errors |
| <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f510.png" width="18" height="18" style="vertical-align:-3px" alt=""> Escaping & sanitization | Manual review — nonces, capability checks, `$wpdb->prepare()` on every custom query | <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/2705.png" width="18" height="18" style="vertical-align:-3px" alt=""> Reviewed |

<br>

## <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/2728.png" width="18" height="18" style="vertical-align:-3px" alt=""> Features

### Core reporting
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/26a1.png" width="18" height="18" style="vertical-align:-3px" alt=""> **AJAX modal form** — submits with no page reload, dropped in anywhere via shortcode
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f3ab.png" width="18" height="18" style="vertical-align:-3px" alt=""> **Unique Ticket IDs** on every report (`PSRM-20260714-8K3F2A`) — the reporter's reference for following up
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f9f1.png" width="18" height="18" style="vertical-align:-3px" alt=""> **Drag-and-drop Form Builder** — 10 field types (name, email, phone, text, paragraph, number, select, radio, checkboxes, captcha), fully reorderable
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f7e2.png" width="18" height="18" style="vertical-align:-3px" alt=""> **Five built-in statuses** with colour-coded badges (Processing, Contacting, Waiting, Solved, Failed), plus unlimited custom statuses
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f3f7.png" width="18" height="18" style="vertical-align:-3px" alt=""> **Admin-defined categories** and Low/Medium/High priority — set manually or by AI
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f512.png" width="18" height="18" style="vertical-align:-3px" alt=""> **Server-verified identity** — reporter name/email are pulled from the WordPress session, never editable by the user, so they can't be spoofed
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f6a6.png" width="18" height="18" style="vertical-align:-3px" alt=""> **Per-form rate limiting**, enforced on both the frontend and the server
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f916.png" width="18" height="18" style="vertical-align:-3px" alt=""> **Captcha support** — Google reCAPTCHA, hCaptcha, and Cloudflare Turnstile

### Conversations
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f4ac.png" width="18" height="18" style="vertical-align:-3px" alt=""> **Threaded replies** — reporters and admins can go back and forth on a report, with live polling so a reply from either side shows up without a manual reload
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f310.png" width="18" height="18" style="vertical-align:-3px" alt=""> **A dedicated, shareable report detail page** — no admin login required, built for the reporter to follow their own ticket

### AI (WordPress 7.0+, fully optional)
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f9e0.png" width="18" height="18" style="vertical-align:-3px" alt=""> **Auto-classification** — every new report gets a suggested category and priority via the built-in WordPress AI Client
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/270d.png" width="18" height="18" style="vertical-align:-3px" alt=""> **AI auto-reply** — the AI can respond directly in the conversation thread, right after submission and after each follow-up
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f4dd.png" width="18" height="18" style="vertical-align:-3px" alt=""> **"Summarize with AI"** on the report edit screen for long or vaguely-worded reports
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1fab5.png" width="18" height="18" style="vertical-align:-3px" alt=""> **AI Response Log** — a rolling audit trail of every AI request and response
- Gracefully disabled with zero impact on the rest of the plugin if you're on an older WordPress version or have no provider connected

### Notifications
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f4e7.png" width="18" height="18" style="vertical-align:-3px" alt=""> **Configurable email notifications** — new report, reporter confirmation, new reply (to whichever side didn't just send it), and AI-error alerts, each with its own editable subject/body template and placeholder tokens
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/2709.png" width="18" height="18" style="vertical-align:-3px" alt=""> **Custom sender name & email**, independent per email address — falls back to your site's normal default when left blank

### E-commerce & LMS
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f6d2.png" width="18" height="18" style="vertical-align:-3px" alt=""> **WooCommerce order auto-link**, HPOS-compatible
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f393.png" width="18" height="18" style="vertical-align:-3px" alt=""> **LearnPress** course, lesson, and quiz support alongside WooCommerce

### Data
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f4e4.png" width="18" height="18" style="vertical-align:-3px" alt=""> **CSV import/export** for both report forms and report logs
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f5c4.png" width="18" height="18" style="vertical-align:-3px" alt=""> Custom database tables for reply threads and (with the API) keys/sessions — nothing bolted onto core tables

### External REST API
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f511.png" width="18" height="18" style="vertical-align:-3px" alt=""> Admin-issued **API keys**, each restricted to whitelisted domains and/or server IPs
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f9e9.png" width="18" height="18" style="vertical-align:-3px" alt=""> **Session-based flow** — start a session, fill fields one at a time, verify an emailed OTP for an email field, then submit for a ticket ID
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f6e1.png" width="18" height="18" style="vertical-align:-3px" alt=""> **Automatic restricted-hosting detection** — some free hosts (the InfinityFree family, most commonly) block inbound API-style requests at the network edge; the plugin self-tests for this and switches the API off with a clear explanation instead of leaving it silently broken
- See [External API](#-external-api) below

### Everywhere else
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f4ca.png" width="18" height="18" style="vertical-align:-3px" alt=""> Admin dashboard widget and admin-bar shortcut with live unsolved-report counts
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/2754.png" width="18" height="18" style="vertical-align:-3px" alt=""> Built-in FAQ builder with a `[psbdx_faq]` accordion shortcode
- <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f4f1.png" width="18" height="18" style="vertical-align:-3px" alt=""> Mobile-first responsive design throughout, including iOS safe-area support

<br>

## <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f4e6.png" width="18" height="18" style="vertical-align:-3px" alt=""> Requirements

| | Minimum |
|---|---|
| WordPress | 5.8 (7.0+ for AI features) |
| PHP | 7.4 |
| WooCommerce | Optional — auto-integrates when present, HPOS-compatible |
| LearnPress | Optional — auto-integrates when present |

<br>

## <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f680.png" width="18" height="18" style="vertical-align:-3px" alt=""> Installation

1. Upload the `psbdx-smart-report-management` folder to `/wp-content/plugins/`.
2. Activate the plugin through **Plugins → Installed Plugins**.
3. Go to **Report Forms** in the admin sidebar and click **Add New Form**.
4. Configure the form, then copy the shortcode from the **Shortcode** box.
5. Paste the shortcode on any page, post, or widget area.

Alternatively, turn on global auto-display in the form settings to show the report button on all product, order, or course pages automatically — no shortcode needed.

<br>

## <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f9e9.png" width="18" height="18" style="vertical-align:-3px" alt=""> Quick Start

```text
[psbdx_report id="X"]        Show a report button + modal form (X = Report Form post ID)
[psbdx_user_reports]         The logged-in user's own report history, with ticket IDs
[psbdx_faq]                  Your admin-managed FAQ, as a clean accordion
```

Turn on AI triage under **Settings → AI** (requires WordPress 7.0+ and a connected provider under **Settings → Connectors**) and every new report will arrive pre-sorted with a suggested category and priority.

<br>

## <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f50c.png" width="18" height="18" style="vertical-align:-3px" alt=""> External API

Let an external system — a chatbot, another app, an integration partner — fill in and submit a report form without touching the WordPress dashboard.

**1. Generate a key** — under **Settings → API**, restricted to whitelisted domains and/or server IPs.
**2. Enable a form** — check "Allow this form to be filled via the API" in that form's Settings tab.
**3. Walk the session:**

```bash
API="https://yoursite.com/wp-json/psbdx-srm/v1"
KEY="psrm_9f2k7..." ; SECRET="7bQ2mZ..."

# Start a session — returns a session_id and the form's field schema
SESSION=$(curl -s -X POST "$API/start" \
  -H "X-PSRM-Api-Key: $KEY" -H "X-PSRM-Api-Secret: $SECRET" \
  -H "Content-Type: application/json" -d '{}' | jq -r '.session_id')

# Fill a field
curl -s -X POST "$API/field" \
  -H "X-PSRM-Api-Key: $KEY" -H "X-PSRM-Api-Secret: $SECRET" \
  -H "Content-Type: application/json" \
  -d "{\"session_id\":\"$SESSION\",\"handle\":\"report_details\",\"value\":\"Charged twice.\"}"

# An email field triggers a one-time code instead of storing it directly —
# confirm it with /verify-otp, then...

# Submit — creates the report and returns a ticket_id
curl -s -X POST "$API/submit" \
  -H "X-PSRM-Api-Key: $KEY" -H "X-PSRM-Api-Secret: $SECRET" \
  -H "Content-Type: application/json" -d "{\"session_id\":\"$SESSION\"}"

# Check on it later — no session needed
curl "$API/ticket/PSRM-20260731-7K3F2A/status" \
  -H "X-PSRM-Api-Key: $KEY" -H "X-PSRM-Api-Secret: $SECRET"
```

| Endpoint | Purpose |
|---|---|
| `GET /fields` | Get a form's field schema — no session required |
| `POST /start` | Open a session, get the field schema |
| `POST /field` | Fill one field at a time |
| `POST /verify-otp` | Confirm an emailed code for an email field |
| `POST /submit` | Create the report, get a `ticket_id` back |
| `GET /ticket/{id}/status` | Look up a ticket's current status |
| `GET /ping` | Unauthenticated health check |

> **On a hosting provider that blocks inbound API calls?** The plugin runs a live self-test (the same technique Core's own Site Health "REST API availability" check uses) and switches the sensitive endpoints off automatically rather than leaving them silently broken — with a manual override if you've confirmed your host is fine.

<br>

## <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f6e0.png" width="18" height="18" style="vertical-align:-3px" alt=""> Developer Hooks

Listen for report status changes instead of polling the database — these fire from the one place in the plugin that ever writes a report's status:

```php
add_action( 'psbdx_srm_report_status_changed', function ( $report_id, $old_status, $new_status, $context ) {
    error_log( sprintf(
        'Ticket %s (user #%d): %s -> %s',
        $context['ticket_id'],
        $context['submitter_id'],
        $context['old_status'] ?? '(new)',
        $new_status
    ) );
}, 10, 4 );

// Or listen for one specific transition only:
add_action( 'psbdx_srm_report_status_changed_to_solved', function ( $report_id, $old_status, $context ) {
    // ...
}, 10, 3 );
```

`$context` includes `ticket_id`, `submitter_id`, `submitter_email`, `old_status`, `new_status`, `changed_by`, `updated_at` / `updated_at_local`, and `source` (`submission`, `admin`, or another value a specific integration passes).

> Bulk operations (CSV import, the Repair & Reset "fix invalid status values" tool) intentionally skip these hooks, since they're bulk restores rather than individual live changes.

<br>

## <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f4c1.png" width="18" height="18" style="vertical-align:-3px" alt=""> Project Structure

```text
psbdx-smart-report-management/
├── admin/          Admin screens — form builder, settings, meta boxes, CSV, dashboard widget
├── includes/       Core logic — post types, helpers, AI, email, replies, API, hosting guard
├── public/         Frontend — AJAX handlers, asset loading, shortcodes, report detail page
├── assets/         CSS/JS
└── languages/      Translation files
```

<br>

## <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/2753.png" width="18" height="18" style="vertical-align:-3px" alt=""> FAQ

**Does this plugin require WooCommerce or LearnPress?**
No. It works standalone — those integrations activate automatically when the respective plugin is present.

**What happens on an older WordPress version, or with no AI provider connected?**
Nothing breaks. AI controls are automatically greyed out, and admins can still set Category/Priority manually on every report, exactly as if AI were never part of the plugin.

**Can guests submit reports?**
Yes — logged without a user association, reporter name defaults to "Guest".

**Is it HPOS-compatible?**
Yes — the plugin declares HPOS compatibility and uses `wc_get_order()` / `get_edit_order_url()` for all order links.

**Is the plugin scanned for malware or vulnerabilities?**
Yes — see [Security & Verification](#-security--verification) above. Every release is checked with VirusTotal, amwscan, and WordPress Plugin Check before it ships.

**Where can I read the full documentation?**
[dev.psbdx.xyz/documentations/psbdx-smart-report-managment](https://dev.psbdx.xyz/documentations/psbdx-smart-report-managment/)

<br>

## <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f4cb.png" width="18" height="18" style="vertical-align:-3px" alt=""> Changelog

Highlights from the latest release — full history in [`readme.txt`](./readme.txt).

**1.4.6**
- New: Support Agents system — add users as support agents from a dedicated admin submenu, set their work hours, and let the plugin auto-assign new reports to a free agent (notifying only that agent) when replies are enabled. An abandoned report is automatically handed to another free agent instead of sitting unassigned.
- New: Administrators are added as agents automatically; a plugin-level Super Administrator designation is the only one that can manage/edit/remove other administrators from the agent list.
- New: `[psbdx_user_reports]` now shows a full agent portal (My Reports, Assigned Reports, Search Ticket, and Manage Agents for admins) to any support agent or administrator, with per-report reply/status/abandon/handover tools and an admin-only activity log.
- New: Agent Rating — a 2.5-star starting score per agent, shown in Agent Management, that rises with completed tickets and falls when a report is abandoned or left for an admin to reassign.
- New: once a report is marked Solved, neither the reporter nor any agent can send another message until someone reopens it (a "Reopen This Report" button for the reporter; agents just change the status). Reports from a form with replies disabled are marked Solved automatically.
- Fix: mobile layout of the report view & reply page — agent tools and reply buttons had no dedicated styling (inherited unstyled wp-admin button classes on the frontend), causing cramped padding and unreliable taps on small screens; now use the plugin's own responsive button/row styles.

**1.4.5**
- New: Setup Wizard for first-time installs (mailing setup, starter form, reopenable anytime via the "Setup Wizard" action link or Repair & Reset).
- New: inline form embedding (`mode="inline"`) and URL popup links (append `?<form id>` to any page).
- New: Attachment field (admin-configurable file types/size limits) and Review (star rating) field types.
- New: file attachments in reply threads, a full "Attachments" management box (manual delete anytime), and optional auto-delete on Solved per field.
- New: optional real email attachments for reply notifications (Settings → Email), off by default.
- Fix: popup links now work on hosts that inject extra query parameters (e.g. some free hosts).
- Fix: asset cache-busting no longer depends on the plugin version number, so CSS/JS fixes reach browsers immediately.
- Improved: mobile touch targets and layout across PSBDx's own admin screens.

**1.4.4**
- New site-wide "Always require a verified email on every API submission" option — adds an extra Email field to every API-enabled form for API callers, independent of what the form itself already collects
- Security hardening pass on the External API: brute-force lockout after repeated failed authentication, peppered secret hashing for new keys, and OTP send throttling to prevent the verification endpoint being used to spam arbitrary addresses

**1.4.3**
- New External REST API with session-based submission and email OTP verification
- Automatic restricted-hosting detection for the API (e.g. InfinityFree-family hosts)
- Custom sender name/email for outgoing notifications
- Fixed a critical issue where AI classification/auto-reply could fatal a report submission on hosts with a tight `max_execution_time`
- Fixed a double-unslash bug in email template saving, and a deprecated `get_page_by_title()` call in CSV import

<br>

## <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f91d.png" width="18" height="18" style="vertical-align:-3px" alt=""> Contributing

Issues and pull requests are welcome. If you're proposing a larger change, please open an issue first to discuss the direction.

## <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f4c4.png" width="18" height="18" style="vertical-align:-3px" alt=""> License

GPL-2.0-or-later — see the [full license text](https://www.gnu.org/licenses/gpl-2.0.html).

## <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f464.png" width="18" height="18" style="vertical-align:-3px" alt=""> Author

Built by [PSBDx](https://dev.psbdx.xyz) — [M. Farhan Hamim](https://profiles.wordpress.org/mfhamim)
