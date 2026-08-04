<div align="center">

# PSBDx Smart Report Management

**A fast, AI-assisted support ticket & complaint system for WordPress.**

Instant AJAX forms · Ticket IDs · AI triage · WooCommerce & LearnPress integration · External REST API

[![Version](https://img.shields.io/badge/version-1.4.4-1a3cff?style=flat-square)](#changelog)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759b?style=flat-square&logo=wordpress&logoColor=white)](#requirements)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4?style=flat-square&logo=php&logoColor=white)](#requirements)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-00c896?style=flat-square)](#license)
[![HPOS](https://img.shields.io/badge/WooCommerce%20HPOS-compatible-96588a?style=flat-square)](#requirements)

[Features](#-features) · [Installation](#-installation) · [Quick Start](#-quick-start) · [External API](#-external-api) · [Developer Hooks](#-developer-hooks) · [FAQ](#-faq)

</div>

---

## What it does

Customers report a problem — with an order, a product, a course, or anything else — through a fast AJAX modal that never reloads the page. Every submission lands in a clean, organized admin inbox with its own human-readable **Ticket ID**, ready to be triaged, replied to, and resolved — optionally with an AI doing the sorting for you.

No support-desk bloat, no separate helpdesk to learn, no per-agent pricing. Just a focused report/complaint system that plugs straight into WooCommerce and LearnPress, and hands off to an external system via its own REST API when you need it to.

<br>

## ✨ Features

### Core reporting
- ⚡ **AJAX modal form** — submits with no page reload, dropped in anywhere via shortcode
- 🎫 **Unique Ticket IDs** on every report (`PSRM-20260714-8K3F2A`) — the reporter's reference for following up
- 🧱 **Drag-and-drop Form Builder** — 10 field types (name, email, phone, text, paragraph, number, select, radio, checkboxes, captcha), fully reorderable
- 🟢 **Five built-in statuses** with colour-coded badges (Processing, Contacting, Waiting, Solved, Failed), plus unlimited custom statuses
- 🏷️ **Admin-defined categories** and Low/Medium/High priority — set manually or by AI
- 🔒 **Server-verified identity** — reporter name/email are pulled from the WordPress session, never editable by the user, so they can't be spoofed
- 🚦 **Per-form rate limiting**, enforced on both the frontend and the server
- 🤖 **Captcha support** — Google reCAPTCHA, hCaptcha, and Cloudflare Turnstile

### Conversations
- 💬 **Threaded replies** — reporters and admins can go back and forth on a report, with live polling so a reply from either side shows up without a manual reload
- 🌐 **A dedicated, shareable report detail page** — no admin login required, built for the reporter to follow their own ticket

### AI (WordPress 7.0+, fully optional)
- 🧠 **Auto-classification** — every new report gets a suggested category and priority via the built-in WordPress AI Client
- ✍️ **AI auto-reply** — the AI can respond directly in the conversation thread, right after submission and after each follow-up
- 📝 **"Summarize with AI"** on the report edit screen for long or vaguely-worded reports
- 🪵 **AI Response Log** — a rolling audit trail of every AI request and response
- Gracefully disabled with zero impact on the rest of the plugin if you're on an older WordPress version or have no provider connected

### Notifications
- 📧 **Configurable email notifications** — new report, reporter confirmation, new reply (to whichever side didn't just send it), and AI-error alerts, each with its own editable subject/body template and placeholder tokens
- ✉️ **Custom sender name & email**, independent per email address — falls back to your site's normal default when left blank

### E-commerce & LMS
- 🛒 **WooCommerce order auto-link**, HPOS-compatible
- 🎓 **LearnPress** course, lesson, and quiz support alongside WooCommerce

### Data
- 📤 **CSV import/export** for both report forms and report logs
- 🗄️ Custom database tables for reply threads and (with the API) keys/sessions — nothing bolted onto core tables

### External REST API
- 🔑 Admin-issued **API keys**, each restricted to whitelisted domains and/or server IPs
- 🧩 **Session-based flow** — start a session, fill fields one at a time, verify an emailed OTP for an email field, then submit for a ticket ID
- 🛡️ **Automatic restricted-hosting detection** — some free hosts (the InfinityFree family, most commonly) block inbound API-style requests at the network edge; the plugin self-tests for this and switches the API off with a clear explanation instead of leaving it silently broken
- See [External API](#-external-api) below

### Everywhere else
- 📊 Admin dashboard widget and admin-bar shortcut with live unsolved-report counts
- ❔ Built-in FAQ builder with a `[psbdx_faq]` accordion shortcode
- 📱 Mobile-first responsive design throughout, including iOS safe-area support

<br>

## 📦 Requirements

| | Minimum |
|---|---|
| WordPress | 5.8 (7.0+ for AI features) |
| PHP | 7.4 |
| WooCommerce | Optional — auto-integrates when present, HPOS-compatible |
| LearnPress | Optional — auto-integrates when present |

<br>

## 🚀 Installation

1. Upload the `psbdx-smart-report-management` folder to `/wp-content/plugins/`.
2. Activate the plugin through **Plugins → Installed Plugins**.
3. Go to **Report Forms** in the admin sidebar and click **Add New Form**.
4. Configure the form, then copy the shortcode from the **Shortcode** box.
5. Paste the shortcode on any page, post, or widget area.

Alternatively, turn on global auto-display in the form settings to show the report button on all product, order, or course pages automatically — no shortcode needed.

<br>

## 🧩 Quick Start

```
[psbdx_report id="X"]        Show a report button + modal form (X = Report Form post ID)
[psbdx_user_reports]         The logged-in user's own report history, with ticket IDs
[psbdx_faq]                  Your admin-managed FAQ, as a clean accordion
```

Turn on AI triage under **Settings → AI** (requires WordPress 7.0+ and a connected provider under **Settings → Connectors**) and every new report will arrive pre-sorted with a suggested category and priority.

<br>

## 🔌 External API

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

## 🛠 Developer Hooks

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

## 📁 Project Structure

```
psbdx-smart-report-management/
├── admin/          Admin screens — form builder, settings, meta boxes, CSV, dashboard widget
├── includes/        Core logic — post types, helpers, AI, email, replies, API, hosting guard
├── public/          Frontend — AJAX handlers, asset loading, shortcodes, report detail page
├── assets/          CSS/JS
└── languages/       Translation files
```

<br>

## ❓ FAQ

**Does this plugin require WooCommerce or LearnPress?**
No. It works standalone — those integrations activate automatically when the respective plugin is present.

**What happens on an older WordPress version, or with no AI provider connected?**
Nothing breaks. AI controls are automatically greyed out, and admins can still set Category/Priority manually on every report, exactly as if AI were never part of the plugin.

**Can guests submit reports?**
Yes — logged without a user association, reporter name defaults to "Guest".

**Is it HPOS-compatible?**
Yes — the plugin declares HPOS compatibility and uses `wc_get_order()` / `get_edit_order_url()` for all order links.

**Where can I read the full documentation?**
[dev.psbdx.xyz/documentations/psbdx-smart-report-managment](https://dev.psbdx.xyz/documentations/psbdx-smart-report-managment/)

<br>

## 📋 Changelog

Highlights from the latest release — full history in [`readme.txt`](./readme.txt).

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

## 🤝 Contributing

Issues and pull requests are welcome. If you're proposing a larger change, please open an issue first to discuss the direction.

## 📄 License

GPL-2.0-or-later — see the [full license text](https://www.gnu.org/licenses/gpl-2.0.html).

## 👤 Author

Built by [PSBDx](https://dev.psbdx.xyz) — [M. Farhan Hamim](https://profiles.wordpress.org/mfhamim)
