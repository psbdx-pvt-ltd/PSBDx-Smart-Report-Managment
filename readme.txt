=== PSBDx Smart Report Management ===
Contributors:      psbdx, atwfarhan, mfhamim
Tags:              support ticket, helpdesk, ai, woocommerce, complaint
Requires at least: 5.8
Tested up to:      7.0
Requires PHP:      7.4
Stable tag:        1.4.4
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

AI-assisted support ticket & complaint system for WordPress — instant AJAX forms, smart triage, ticket IDs, deep WooCommerce/LearnPress support.

== Description ==

**PSBDx Smart Report Management** turns any WordPress site into a lightweight support desk. Customers report a problem — with an order, a product, a course, or anything else — through a fast AJAX modal that never reloads the page, and every submission lands in a clean, organized admin inbox your team actually enjoys working from.

No support-ticket plugin bloat, no separate helpdesk to learn, no per-agent pricing. Just a focused, fast, genuinely useful report and complaint management system that plugs straight into WooCommerce and LearnPress — and, when you want it, hands the busywork of sorting and prioritizing incoming reports to AI.

**Why site owners choose it**

* **It's instant.** Reports submit over AJAX with no page reload, so customers actually finish the form instead of abandoning it.
* **It sorts itself.** Turn on AI-assisted classification and every new report gets a suggested category and priority (Low / Medium / High) automatically — you triage a full inbox in minutes, not hours.
* **It gives customers a reference.** Every report gets its own unique, human-readable Ticket ID, so a customer can follow up by email or phone and your team can find the exact report instantly.
* **It already knows your store.** Reports from a WooCommerce order page auto-link to that order; reports from LearnPress courses, lessons, and quizzes are just as seamless.
* **It fits your workflow, not the other way around.** Custom statuses, custom categories, per-form rate limiting, captcha, and a drag-and-drop form builder mean the plugin adapts to how you already work.

**Key Features**

* AJAX-powered modal report form — no page reload, works anywhere via shortcode
* Optional AI-assisted triage (WordPress 7.0+): automatically suggests a category and priority for every new report using the built-in WordPress AI Client — gracefully disabled with no impact on the rest of the plugin if you're on an older WordPress version or haven't connected a provider
* "Summarize with AI" on the report edit screen — a plain-language explanation of what the customer is actually reporting, for reports that are long or vaguely worded
* AI Response Log: a rolling 3-hour audit trail of every AI request and response, so you can see exactly what the AI decided and why
* Unique, human-readable Ticket ID on every report — the customer's reference for following up, shown in their confirmation and report history
* Admin-defined report Categories and Low/Medium/High Priority, settable manually or by AI, editable any time from the report edit screen
* Drag-and-drop Form Builder: ten field types (name, email, phone, text, paragraph, number, select, radio, checkboxes, captcha), fully reorderable, mobile-friendly
* Five built-in report statuses with colour-coded badges (Processing, Contacting, Waiting, Solved, Failed), plus unlimited custom statuses
* E-commerce order auto-link — reports from an order page are automatically linked to that order in the admin, HPOS-compatible
* Per-form cooldown / rate limiting enforced on both the frontend and the server, so the same user can't spam a form
* Captcha support: Google reCAPTCHA, hCaptcha, and Cloudflare Turnstile
* Admin dashboard widget and admin-bar shortcut with live, at-a-glance unsolved-report counts
* Built-in FAQ builder with a `[psbdx_faq]` accordion shortcode for your own site's visitors
* Mobile-first responsive design throughout, including iOS safe-area support and a touch-friendly admin experience
* LearnPress course, lesson, and quiz page support, alongside WooCommerce
* Reporter identity (name and email) collected server-side from the WordPress session — never editable by the user, so it can't be spoofed
* Shortcodes: `[psbdx_report id="X"]`, `[psbdx_user_reports]`, and `[psbdx_faq]`

**Also configurable, per form**

* Fully custom report reasons (comma-separated, "Other" always appended)
* Optional extra fields (e.g. Transaction ID, Coupon Code)
* Contact field with a required/optional toggle
* Show or hide the reporter identity card in the form
* Auto-display on all products/orders/courses, or assign a form per item

**Perfect for**

* WooCommerce stores that need a fast, structured way for customers to report an order or product problem
* Online course sites (LearnPress) fielding student questions about a specific course, lesson, or quiz
* Any WordPress site that wants a lightweight complaint/report/support-ticket system without a heavyweight external helpdesk

**Shortcodes**

`[psbdx_report id="X"]`
Display a report button and modal form. Replace X with the Report Form post ID shown in the Shortcode box.

`[psbdx_user_reports]`
Display a paginated table of the currently logged-in user's report history, including their ticket IDs.

`[psbdx_faq]`
Display your admin-managed FAQ as a clean accordion, anywhere on your site.

== Installation ==

1. Upload the `psbdx-smart-report-management` folder to `/wp-content/plugins/`.
2. Activate the plugin through **Plugins > Installed Plugins**.
3. Go to **Report Forms** in the admin sidebar and click **Add New Form**.
4. Configure the form, then copy the shortcode from the **Shortcode** meta box.
5. Paste the shortcode on any page, post, or widget area.

Alternatively, enable global auto-display in the form settings to show the report button on all product or order pages automatically.

== Frequently Asked Questions ==

= Does this plugin use AI? =
Optionally, yes. If you're on WordPress 7.0+ and have an AI provider connected under Settings → Connectors, you can turn on AI-assisted classification (Settings → AI) so every new report automatically gets a suggested category and priority, and you can click "Summarize with AI" on any report for a plain-language explanation of the issue. It's entirely opt-in — the plugin works fully without it.

= What happens if I'm on an older WordPress version or don't have AI set up? =
Nothing breaks. The AI controls are automatically greyed out and disabled if your WordPress version is below 7.0 or no AI provider is connected. You (and your admins) can still set a Category and Priority manually on every report, exactly as if AI were never part of the plugin.

= What is the Ticket ID, and who sees it? =
Every submitted report gets a unique, human-readable Ticket ID (e.g. "PSRM-20260714-8K3F2A"). The reporting user sees it in their submission confirmation and their report history, so they have something concrete to reference if they follow up by email or phone. Admins can search for a ticket ID an user gives them to jump straight to that report.

= Can I set my own report categories? =
Yes. Add your own categories under Settings → Categories. They'll appear in the manual Category dropdown on every report, and — if AI is enabled — the AI will be constrained to pick from that exact list instead of inventing its own.

= Can I add a FAQ section for my own site's visitors? =
Yes. Go to the FAQ admin page, add your questions and answers, and drop the `[psbdx_faq]` shortcode anywhere on your site to display them as a clean accordion.

= Can guests submit reports? =
Yes. Guest reports are logged without a user association. The reporter name defaults to "Guest".

= Can I disable the reporter identity card shown in the form? =
Yes. Each form has an "User Identity Display" toggle in its configuration. When turned off, the read-only name and email card is hidden from the form — but identity is still collected server-side for the admin log.

= How does rate limiting work? =
Each form has a configurable cooldown (in minutes, default 30). Once a logged-in user submits a report via a form, they cannot submit again through that same form until the cooldown expires. The cooldown is enforced both on the frontend (form is hidden) and in the AJAX handler (server rejects the request even if the UI is bypassed).

= Does the plugin require any other plugins? =
No. PSBDx Smart Report Management works as a standalone plugin. E-commerce and LearnPress integrations activate automatically when those plugins are present.

= What is the order auto-link feature? =
When a user submits a report from an order page (e.g. My Account > Orders > View Order), the plugin automatically detects and stores the order ID. The admin Report Log shows a direct link to the order, and the user's report history table shows the order number instead of a URL.

= Is it compatible with High-Performance Order Storage (HPOS)? =
Yes. The plugin declares HPOS compatibility and uses `wc_get_order()` with `get_edit_order_url()` for all order links.

= Is it mobile-friendly, for both visitors and admins? =
Yes. The report form and modal are mobile-first with iOS safe-area support, and the admin screens (report list, response view, AI Response Log, settings) are all built with responsive breakpoints so you can manage reports comfortably from a phone.

= From where I can read all the documentations? =
We are happy to see that you are interested to read the documentations. Please visit https://dev.psbdx.xyz/documentations/psbdx-smart-report-managment/

== Developer Hooks ==

For developers building integrations or extensions on top of this plugin.

**Report status changes**

Instead of polling the database, listen for these action hooks — they fire from the one place in the plugin that ever writes a report's status, so they're guaranteed to fire exactly once per real change:

`psbdx_srm_report_status_changed( $report_id, $old_status, $new_status, $context )`

Fires for every status change, whatever the new status is.

`psbdx_srm_report_status_changed_to_{$new_status}( $report_id, $old_status, $context )`

Fires only for one specific new status (status run through `sanitize_key()`), e.g. `psbdx_srm_report_status_changed_to_solved`. Use this instead of the generic hook if you only care about one transition.

`$context` is an associative array:

* `report_id` (int) — same as the first hook argument, included for convenience.
* `ticket_id` (string) — the report's human-readable ticket ID.
* `submitter_id` (int) — numeric WP user ID of whoever filed the report, or `0` for a guest.
* `submitter_email` (string) — the reporter's email address, if one was collected.
* `old_status` (string|null) — the previous status key, or `null` if this report never had one before (i.e. this is the very first status it's ever had, from a brand-new submission).
* `new_status` (string) — the new status key.
* `changed_by` (int) — numeric WP user ID of whoever triggered the change (typically the logged-in admin editing the report), or `0` for a guest's own submission or an automated process.
* `updated_at` (string) — MySQL datetime in GMT/UTC.
* `updated_at_local` (string) — MySQL datetime in the site's local timezone.
* `source` (string) — where the change came from: `submission` (a brand-new report), `admin` (the report edit screen), or another value a specific integration may pass.

Example:

`
add_action( 'psbdx_srm_report_status_changed', function( $report_id, $old_status, $new_status, $context ) {
    error_log( sprintf(
        'Ticket %s (user #%d): %s -> %s',
        $context['ticket_id'],
        $context['submitter_id'],
        $context['old_status'] ?? '(new)',
        $new_status
    ) );
}, 10, 4 );
`

Note: bulk/maintenance operations that write status directly to the database (CSV import, and the Repair & Reset page's "fix invalid status values" tool) intentionally do not fire these hooks, since they're bulk restores rather than individual live status changes.

== Screenshots ==

1. Frontend report button and modal form on a product page.
2. Admin dashboard showing Report Form Management Screen.
3. Admin Report Logs list table with status badges, reporter, and order link columns.
4. Admin Report Form configuration screen.
5. Admin dashboard widget showing report customization screen.

== Changelog ==

= 1.4.4 =

* **New — always-verified email option for the API:** Under Settings → API, a new "Always require a verified email on every API submission" checkbox adds an extra "Verified Email" field to every API-enabled form's schema for API callers — independent of whatever the form itself collects, including any email field it may already have. When on, that field always requires OTP verification before `/submit` succeeds, guaranteeing every report created through the API can be reliably linked to a real email address, even for forms that don't collect one on the frontend at all. Off by default.
* **Security — API hardening pass:**
    * **Brute-force lockout:** an IP that fails API authentication (wrong key or wrong secret) 15 times within 15 minutes is now locked out from authenticating for 15 minutes, independent of which key it was trying. A successful request clears the count.
    * **Peppered secret hashing:** newly created/rotated API keys now have their secret hashed with `hash_hmac( 'sha256', $secret, wp_salt( 'auth' ) )` — a site-specific pepper — instead of a plain hash, so a stolen database alone isn't enough to verify guesses offline. Existing keys created before this change keep working via a backward-compatible check against the old scheme.
    * **OTP send throttling:** an email field can now only trigger an OTP send 3 times per session, and 30 times per API key per hour, whichever comes first — closing off a way the endpoint could otherwise be used to repeatedly send "verification code" emails to an arbitrary address at no cost to the caller.

= 1.4.3 =

* **New — External API:** Under Settings → API, admins can generate API keys (each restricted to whitelisted domains and/or server IPs) that let an external system — a chatbot, another app — fill in and submit a report form programmatically. A per-form "Allow this form to be filled via the API" checkbox controls which forms are exposed; if exactly one form has it enabled, callers can start a session without specifying a form ID. The flow is session-based: `POST /start` returns a session ID and the form's field schema, `POST /field` fills one field at a time (an Email field automatically triggers a one-time code sent to that address instead of being stored directly), `POST /verify-otp` confirms that code, and `POST /submit` creates the report once everything required is present, returning a ticket ID. `GET /ticket/{id}/status` looks up a ticket's current status. All endpoints require an API key + secret via the `X-PSRM-Api-Key` / `X-PSRM-Api-Secret` headers (or `Authorization: Bearer key:secret`).
* **Critical fix — report/reply submission could fatal on some hosts:** Since 1.4.2, submitting a report (or a follow-up reply) ran AI classification and/or AI auto-reply synchronously — including, for auto-reply, an extra page fetch — all before the browser got its response. On hosts with a modest `max_execution_time` (many shared hosts default to 20–30 seconds), a slow or briefly-degraded AI provider could push the total past that limit and fatal the request with "Maximum execution time exceeded," which looked to a visitor like the site had crashed while submitting a report. The success response is now sent to the browser first — instantly finishing the connection where the host supports it (PHP-FPM) — with classification, auto-reply, and email notifications continuing afterward, off to the side, where a slow provider can no longer affect what the visitor sees. A reply's AI auto-response, if any, now arrives via the same thread-polling mechanism that already picks up a reply from the other side, instead of holding up the reply confirmation. The API's `/submit` endpoint got the same safety net.
* **New — Sender name & email:** Under Settings → Email, admins can set a Sender name and Sender email that every outgoing notification is sent under. Either field can be left blank to fall back to the site's normal default for that half.
* **Fix — email template saving:** The Settings → Email save handler was unslashing the posted template data twice, which could silently strip a literal backslash an admin typed into a subject or body (e.g. a Windows path) every time they saved.
* **Fix — submission still slow on non-PHP-FPM hosts:** The earlier fix in this same release sent the response to the browser before running AI classification/auto-reply/email — but on hosts without PHP-FPM (no `fastcgi_finish_request()`), the connection stayed open regardless, so the visitor's browser still waited on the AI calls either way. Report submission and follow-up replies now hand that work off to a separate WP-Cron request (nudged to run immediately) on any host where the connection can't already be closed out early — so submitting a report responds quickly everywhere, not just on PHP-FPM.
* **New — restricted-hosting detection for the API:** Some free hosting providers (the InfinityFree family being the most commonly reported) block or JS-challenge inbound API-style requests from other domains at the network edge, before WordPress ever sees them — so the External API's endpoints would exist and look configured, but silently never work for any real caller. Settings → API now runs a live self-test (the same technique Core's own Site Health "REST API availability" check uses: WordPress asks itself to fetch its own API over the public internet) plus a quick hostname check against known-restrictive hosting patterns. When either flags a problem, the sensitive API endpoints stop registering and a clear notice explains why, with a "Re-check now" button and a manual "enable anyway" override for anyone who's confirmed their host is fine. Existing API keys are never touched.
* **Fix — CSV form import used a deprecated function:** `get_page_by_title()` (deprecated since WordPress 6.2) has been replaced with a `get_posts()`-based exact-title lookup, so importing forms from CSV no longer risks a visible deprecation notice on hosts that display them.
* **Fix — defensive hardening on CSV upload validation:** the upload check now confirms `$_FILES[...]['error']` is actually set before reading it, rather than assuming a well-formed array.
* **Change — API submissions always verify email:** an Email field on an API-enabled form is now always treated as mandatory to OTP-verify before `/submit` succeeds, regardless of that field's own "Required" toggle on the form — a report created via the API can otherwise have no reliable way to confirm who actually submitted it. The field schema returned by `/start` and `/fields` reflects this (`required` is `true` for any email field).
* **New — `GET /fields`:** returns a form's field schema — the same information `/start` returns — without opening a session, for a caller that just wants to inspect what a form collects (to build a UI, or decide what to send) without committing to the 30-minute session lifecycle.

= 1.4.2 =

* **New — Reply threads on reports:** Each report form now has an "Allow Replies" option (Settings tab). When turned on, both the reporter (from `[psbdx_user_reports]`, via a new "View & Reply" panel per report) and admins (from a new "Conversation" box on the report edit screen) can exchange follow-up messages on a report. When turned off, reply attempts are rejected. Reports submitted before this update don't have a known source form yet, so replies can't be enabled retroactively for them.
* **New — AI can reply automatically:** A report form with "Allow Replies" on can also enable "Allow AI to reply", which posts an AI-generated reply into the conversation automatically — right after the report is submitted, and again after the reporter posts a follow-up. This only takes effect once the new site-wide "Allow AI to reply" switch under Settings → AI → Manage is also turned on; an info icon next to that switch explains that enabling it lets the plugin send the AI some content from the page the customer reported from, so its reply can be grounded in what they were actually looking at, alongside the report text and conversation so far.
* **New — Improve with AI (admin replies):** When replying to a report, admins now have an "Improve with AI" button that polishes their drafted reply (grammar, tone, clarity) without changing its meaning, plus a separate "Generate AI Reply" button (shown only when this report's AI-reply gate is fully open) that drafts a full reply for review before sending. Both are independent of the automatic AI reply feature and never post anything without the admin clicking Send.
* **Improved — FAQ frontend redesign:** The `[psbdx_faq]` accordion has a refreshed look (Q/A badges, hover and open-state highlighting, subtle open animation) and a live search box that filters questions as you type once there are more than a few.
* **New — Ticket ID shown in admin:** The report edit screen's ticket header now shows the ticket ID as its own badge, and the AI Response Log table has a Ticket column again.
* **New — Email notifications:** Under Settings → Email, admins can now enable/disable and fully rewrite (subject + HTML body, with placeholders) five notifications: new report received (to admin), report confirmation (to reporter), AI error (to admin, when an automated classification or auto-reply fails), and new reply (to whichever side — admin or reporter — didn't just send it).
* **New — CSV import/export:** The Repair & Reset page can now export reports and report forms to CSV (a `meta_json` column carries every setting/field for a full-fidelity round trip) and import them back in — reports are matched for update by ticket ID, forms by exact title.
* **New — Dedicated report detail page:** "View & Reply" on `[psbdx_user_reports]` now opens a full detail page (ticket ID, date, status, category, priority, item, and the full submitted details) instead of an inline panel — which fixes the previous issue where that panel could appear open by default on narrower screens. Only the reporter (matched by account, or by email for guests) or an admin can view it — anyone else gets a normal 404. Confirmation and reply-notification emails now link straight to this page.
* **New — Per-report "Turn off AI replies" switch:** On the report edit screen's Conversation box, admins can now turn off automatic AI replies for one specific report (e.g. once they've personally taken over the conversation) without changing the form's or the site's AI settings. The "Improve with AI" and "Generate AI Reply" buttons still work as manual, on-demand tools regardless of this switch.
* **New — Report status change hooks for developers:** Two new action hooks, `psbdx_srm_report_status_changed` and the status-specific `psbdx_srm_report_status_changed_to_{status}`, fire whenever a report's status changes — with the ticket ID, the submitter's numeric user ID and email, old/new status, who made the change, and the update time. Lets other plugins react to status changes instead of polling the database. See "Developer Hooks" above for details.

= 1.4.1 =

* **New — Unique Ticket IDs:** Every submitted report is now assigned a unique, human-readable ticket ID (e.g. "PSRM-20260714-8K3F2A"), shown to the reporting user in the submission confirmation and their report history ([psbdx_user_reports]) as their reference for follow-up, and shown to admins on the report edit screen and in the AI Response Log (as of 1.4.2) so a ticket a user quotes can be found and matched immediately.
* **New — AI responses are matched by ticket ID:** When AI classification runs, the ticket ID is included in the request and the AI is required to echo it back in its JSON response. The plugin verifies the returned ticket ID matches before applying the category/priority, so a response can never be misapplied to the wrong report — important on busy sites processing many reports at once.
* **New — AI-assisted report classification (WordPress 7.0+):** New Settings → AI tab (Manage / Knowledgebase) lets admins turn on AI-powered classification, choose preferred model(s), set a token limit and temperature, and send a test request to verify the connection. Uses the WordPress 7.0 AI Client and Connectors API. On sites running WordPress below 7.0, or where no AI provider is connected, the controls are automatically greyed out and disabled — nothing else in the plugin is affected.
* **New — Automatic category and priority suggestions:** When AI features are enabled, every newly submitted report is automatically sent to the connected AI provider, which selects a Category (constrained to the admin-defined list when one exists, or proposed by the AI when the list is empty) and a Priority (Low / Medium / High).
* **New — Report Categories settings tab:** Admins can define and manage the list of report categories from Settings → Categories. This list powers both the manual Category dropdown on the report edit screen and the AI's category suggestion.
* **New — Manual Category & Priority meta box:** A "Category & Priority" box on the report edit screen lets admins set or override both fields by hand at any time, independently of WordPress version or whether AI features are enabled or available. A small indicator shows whether the current values were suggested by AI or set manually.
* **New — Category and Priority admin columns:** The Responses list table now shows Category and colour-coded Priority badges alongside Status.
* **New — AI Response Log:** A new "AI Response Log" submenu records every request/response sent to the AI Client — classifications, summaries, and Settings → AI test requests — in a small dedicated table, with the model used and a viewable request/response excerpt. Only the last 3 hours are kept; older entries are purged automatically (hourly, and opportunistically on every new entry), so it stays lightweight even on busy sites.
* **New — Summarize with AI:** On the report edit screen, an admin can click "Summarize with AI" to have the AI explain in plain language what the customer is actually reporting — handy for quickly triaging longer or vaguely-worded reports. Only shown when AI features are enabled and available.
* **Improved — "Report Logs" renamed to "Responses":** The admin submenu and all related labels (search, empty states, etc.) now read "Responses" instead of "Report Logs" for clarity.
* **Improved — Redesigned response view:** The report edit screen has a new "at a glance" header (status, category, priority, reporter, and date right under the title) and the message itself is shown in a cleaner, more readable card. The redundant default WordPress editor box — which just duplicated the read-only message — has been removed from this screen.
* **New — FAQ tab under Support:** Support now has a "FAQ" tab where admins can add their own common questions and answers, plus a `[psbdx_faq]` shortcode (with a one-click copy button) to display them as an accordion anywhere on the site.
* **Improved — Support request emails:** The email sent from Support → Contact Support is now a properly formatted HTML email (branded header, a details table, the description in its own card, and system information in a readable monospace block) instead of a bare plain-text dump.
* **Fixed — Critical mobile/responsive issues in the admin:** The admin side of the plugin had no responsive styles at all. Added proper breakpoints so admin tables scroll horizontally instead of breaking the page layout on narrow screens, the Support page's two-column layout collapses to a single column below ~900px, the Captcha settings' label/field rows stack on phones, and all newly-added UI (response view, AI Response Log, FAQ tab) was built mobile-first from the start.
* **Improved — Security/problem-detection scan:** The plugin's background integrity scan now also catches AI-related drift: AI enabled on a site that no longer meets the WordPress/AI Client requirements (e.g. after a downgrade), the AI Response Log database table going missing (it now attempts to self-repair this automatically before reporting it), and a run of consecutive AI request failures — all surfaced as an admin notice rather than silently failing.

= 1.4.0 =

* **New — Field Settings is now a popup on every viewport:** Field Settings was previously a permanently visible third grid column on desktop (220px library + flexible canvas + 280px settings), which left too little room for the canvas at anything narrower than a wide monitor. It's now a popup everywhere: a right-side drawer on desktop, a bottom sheet on mobile. Selecting a field opens it; the "X" button, the backdrop, or Esc closes it. Desktop is a simple 2-column grid now (library + canvas), so the canvas gets all the space it needs regardless of window width.
* **Fixed — Duplicate/non-functional display checkboxes (1.3.2_beta):** The Settings tab had two sections that appeared to do the same thing: "Global Display Settings" (which actually works, via the site-wide `psbdx_global_order_form_id` / `psbdx_global_product_form_id` options) and "Plugin Integrations" (which saved post meta that nothing in the plugin ever read back — checking those boxes silently did nothing). Merged into one working "Automatic Display" section, correctly disabled with a warning badge when the required plugin isn't active. The dead post meta is cleaned up automatically on save.
* **New — Fully functional mobile Form Builder (1.3.2):** Replaced the old "please switch to Desktop Mode" mobile block with a real touch-optimized builder: a floating "Add Field" button opens the field library as a bottom sheet, selecting a field opens its settings, and every field card has Up/Down buttons for reordering (drag-and-drop remains available on desktop).
* **Fixed — Desktop builder overflowing into other fields (1.3.3_beta):** The Up/Down reorder buttons are non-shrinking, and the builder's grid/flex children were missing `min-width: 0` — a CSS gotcha where grid/flex items refuse to shrink below their content's intrinsic width unless told to. Wider field cards were bleeding out of the canvas column into the settings panel. Fixed by adding `min-width: 0` so cards shrink and truncate instead of overflowing.
* **Fixed — Mobile "Add Field" overlay not receiving taps (1.3.3_beta):** Two compounding bugs: the builder layout had a mobile flex-column rule and an unscoped desktop grid rule with equal CSS specificity, and because the desktop rule came later in the file it silently won on mobile too, so the layout never actually switched to a single column; and jQuery UI's `.draggable()`/`.sortable()` (mouse-event based, no touch support) were still bound on mobile, which could intercept a tap before the tap-to-add handler fired, so the touch fell through to the canvas underneath. Fixed by restructuring the layout mobile-first, skipping drag/sort initialization on mobile, and hardening the sheets with explicit `pointer-events`.
* **Fixed — Canvas collapsing to an unreadable sliver at "in-between" widths (1.3.4_beta):** At moderate viewport widths (a phone's "Desktop Mode", a narrow browser window, a small laptop) the fixed-width side columns left so little room that the canvas collapsed toward zero — the field card's non-shrinking buttons stayed visible while the icon and label had nowhere to render. Superseded in this release: with Field Settings now a popup instead of a third grid column, the canvas always gets the full remaining width.

= 1.3.1 =

* **Fixed — Plugin Integrations section:** The integrations section in the Form Builder Settings tab is now always visible. "Add to all order details pages" shows a WooCommerce badge and is disabled (with a "Plugin not installed" warning badge) when WooCommerce is not active. "Add to all product and course pages" shows the relevant badges for WooCommerce and/or LearnPress and is disabled when neither is installed.
* **Improved — Security system:** `PSBDX_SRM_Conflict_Guard` now runs a multi-point security scan on `admin_init` (cached via 5-minute transient): checks post-type registration, core class presence, legacy form count, and captcha misconfiguration. Results are shown in both the Repair & Reset page and as a non-dismissible admin notice.
* **Improved — Auto conflict detection:** Plugin deactivation now busts the security scan cache so the next page load reflects the updated plugin state. The health check after third-party plugin activation also busts the security scan cache on pass.
* **Improved — Repair & Reset page:** Added a security status banner at the top of the page — green "all clear" when no issues are found, red list of issues when any are detected. Added a "Refresh security scan" action to manually bust the cached scan result.
* **Improved — Admin dashboard widget:** Added an unsolved reports banner showing how many reports are not marked as "Solved" (with a direct link to the reports list). Shows a green "all clear" message when every report is solved.
* **New — Admin bar shortcut:** A "Reports" link with a red count badge (unsolved reports) is now shown in the WordPress admin bar for administrators, providing a quick path to the reports list from any admin page.
* **New — Support submenu page:** Added a "Support" page under the Reports menu. Includes a contact form (Name, Contact Email, Attachments, Description) and a checkbox to include automatic system information (WordPress + PHP version, all installed and active plugins, active theme). On submission the email is sent to support@dev.psbdx.xyz via the site's configured mail. A collapsible preview shows exactly what system data will be attached before submission.

= 1.3.0 =

**Learn more (launch overview):** https://dev.psbdx.xyz/v1-3-0-summary-psrm/

* **New — v2 Form Builder:** Introduced a full drag-and-drop Form Builder replacing the flat v1 configuration meta box. Admin interface is now divided into two tabs: "Fields / Builder" (drag-and-drop canvas) and "Settings" (all configuration, integrations, and notifications).
* **New — Field Library:** Ten field types now available in the builder: Name (split First/Last), Email, Mobile Number, Text (single-line), Paragraph (textarea), Number, Drop-down/Select, Radio Buttons, Checkboxes, and Captcha.
* **New — Granular Field Settings panel:** Clicking any canvas field opens a live settings panel with Field Name (label), Field Handle (database slug), and a Required toggle.
* **New — "Other" option for choice fields:** Radio, Select, and Drop-down fields now support an "Enable Other Option" toggle in admin. On the frontend, selecting "Other" dynamically reveals a text input for a custom response.
* **New — Mobile builder restriction:** Builder canvas is hidden on viewports < 768 px with a clear user-facing message; Settings tab remains accessible at all screen sizes.
* **New — Post-update global security notice:** On `admin_init`, the plugin checks whether any published report forms still use the legacy v1 schema. If any are found, a non-dismissible `notice-error` banner is displayed across the entire WordPress admin until every form is migrated and saved.
* **New — Legacy form migration gate:** Editing a v1 form now shows a blocking "Legacy Form Detected" prompt. Clicking "Update form to the new builder" triggers an AJAX parser that maps the old data structure into a v2 field schema, allowing editing and saving in the new format. Saving applies the `_psrm_form_version = 2` flag, removing the form from the legacy count.
* **New — Dynamic plugin integrations (Settings tab):** "Enable WooCommerce Integration" checkbox is now shown only when WooCommerce is active; "Enable LearnPress Integration" only when LearnPress is active. Both are detected via PHP class-existence checks.
* **New — Conditional Captcha field:** The Captcha entry in the field library is disabled and shows a warning badge ("Please configure Captcha settings in PSRM global settings first.") when no captcha API keys are configured in global PSRM settings.
* **New — Frontend v2 field renderer:** `PSBDX_SRM_Form_Renderer::render_fields()` renders all v2 field types from the JSON schema, including the "Other" reveal behaviour via delegated JS event listeners.
* **New — v2 AJAX submission handler:** `class-psbdx-srm-ajax.php` now detects `_psrm_form_version` and routes v2 submissions through a per-field schema validator that sanitizes, validates required fields, and resolves "Other" values before building the report-log post content. V1 submission path is fully preserved for backward compatibility.
* **Improved — Security:** Form builder fields are sanitized through `sanitize_fields_schema()` before being stored as JSON. Nonce verification precedes all `$_POST` reads. v2 field handles are constrained to `sanitize_key()`.
* **Improved — Legacy count caching:** `psrm_legacy_form_count` transient (5-minute TTL) prevents a raw DB query on every admin page load; transient is busted immediately after any form is saved as v2.
* **Compat — Backward compatible:** All v1 (legacy) form data, meta keys, shortcodes, and AJAX submission logic remain intact. No existing form breaks on update.

= 1.2.0 =

**Learn more (full release story):** https://dev.psbdx.xyz/v1-2-0-summary-psrm/

*New Added:*
* Added Captcha support for Google reCaptcha, hCaptcha, Cloudflare Turnstile.

*Fixed:*
* Fixed known bugs and visual glitches.

= 1.1.0 =

**Learn more (full release story):** https://dev.psbdx.xyz/v1-1-0-summary-psrm/

**Admin menu and navigation**
* New top-level **PSBDx Reports** menu groups Report Forms and Report Logs in one place for easier management.
* **Settings** and **Repair & Reset** appear under this menu (requires manage_options).

**Settings page (tabbed)**
* **Status** — Add unlimited custom report statuses (label plus background and text colours). Built-in statuses remain fixed; custom rows can be removed with a Remove action and saved to delete them from storage.
* **Global Rate Limiting** — Site-wide default cooldown (minutes) for logged-in users; use **0** to disable globally. Per-form cooldown in each Report Form still **overrides** the global value when set on the form.
* **Captcha** — Placeholder tab (“Coming soon”).
* **Email** — Placeholder tab (“Coming soon”).

**Repair & Reset**
* Read-only diagnostic scan (database connectivity, posts table, CPT registration, global form options, report counts, invalid status meta, rate-limit option rows).
* **Clear rate-limit transients** — Removes stored cooldown entries (`psbdx_cd_*`) from the options table.
* **Fix invalid status meta** — Normalizes unknown stored status values back to “Processing”.

**Conflict guard**
* After another plugin is activated, a lightweight health check runs; if this plugin’s post types or helpers fail to load, that plugin is automatically deactivated and an admin notice names it (filter `psbdx_srm_conflict_guard_enabled` to disable).

**Plugin row action links**
* On **Plugins → Installed Plugins**, administrators see **Settings**, **Repair & Reset**, and **Documentation** next to Activate/Deactivate.

**Custom statuses (frontend and admin)**
* Custom statuses merge with the five built-in statuses everywhere (meta boxes, list tables, dashboard widget, `[psbdx_user_reports]` chips).

**Performance**
* Dashboard status totals use a single aggregated query instead of many separate queries.
* Published report forms list is cached per request where selectors need it (e.g. WooCommerce, LearnPress).

**Multisite / network**
* Plugin header **Network: true** for WordPress.org compatibility.
* New blogs on a network use `active_sitewide_plugins` to detect network activation when stamping review-notice activation time.

**Other fixes**
* Ensure the main plugin file starts with `<?php` only (no stray characters before the opening tag) to prevent “headers already sent” warnings.

= 1.0.1 =

**Learn more (full release story):** https://dev.psbdx.xyz/v1-0-1-summary-psrm/

**New: Admin Review Notice**
* Added a dismissible admin panel notification that appears 24 hours after plugin activation, asking the site admin to leave a review on WordPress.org.
* Three response options:
  * "Yes, you deserve it!" — Opens the WordPress.org review page in a new tab and permanently dismisses the notice.
  * "Nope, I'll review later" — Snoozes the notice for 7 days, after which it reappears.
  * "I already reviewed" — Permanently dismisses the notice without any redirect.
* Dismiss state is stored per-site (not network-wide), so each site on a multisite network manages its own notice independently.
* Notice is only shown to users with the manage_options capability.
* All AJAX requests are nonce-verified for security.

**New: Documentation Link on Plugins Page**
* Added a Documentation link next to the Deactivate/Activate action on the WordPress Plugins page, linking directly to the plugin's documentation at dev.psbdx.xyz.

**Improved: Multisite Compatibility**
* Activation hook now handles network-wide activation on multisite — iterates every site using switch_to_blog / restore_current_blog to write per-site options correctly.
* New sites added to a network where the plugin is already network-active automatically receive their own activation timestamp via the wp_insert_site hook.
* Plugin text domain is now loaded inside plugins_loaded so per-site language settings are respected on multisite.
* Backward-compatible lazy activation stamping — existing sites active before v1.1.0 receive a timestamp on first load with no manual action required.

= 1.0.0 =

**Learn more (launch overview):** https://dev.psbdx.xyz/v1-0-0-summary-psrm/

* Initial release.
* Full multi-file plugin architecture following WordPress coding standards.
* AJAX report modal with mobile-first responsive design.
* Server-side identity collection (name and email never from form input).
* Admin toggle to show/hide reporter identity card.
* Per-form rate limiting using WordPress transients.
* WooCommerce / e-commerce order auto-link with HPOS support.
* Admin dashboard widget with status counts and recent reports.
* Configurable reasons, extra fields, contact field, and cooldown per form.
* Five report statuses with colour-coded admin badges.
* `[psbdx_report]` and `[psbdx_user_reports]` shortcodes.
* LearnPress course, lesson, and quiz page integration.

== Upcoming Features ==

The following features are planned for future releases:

* **Email Notifications** — Notify the admin on new submissions, and send a confirmation email to the reporter.
* **Status Change Emails** — Email the reporter automatically when their report status is updated.
* **CSV Export** — Export all report logs as a CSV file from the admin screen.
* **File / Screenshot Attachment** — Let users attach a screenshot or file to their report. (Added ✅)
* **Internal Admin Notes** — Private notes on each report log, visible only to admins.
* **Report Categories / Tags** — Organise reports with admin-defined categories for easier filtering. (Added ✅)
* **Guest Email Verification** — Allow non-logged-in users to submit with email verification before saving. (Added ✅)
* **Duplicate Detection** — Alert admins when a new report closely matches an existing open one. (Added ✅)
* **Report Priority Levels** — Assign Low / Medium / High priority to reports, manually or via AI. (Added ✅)
* **REST API Endpoints** — Query and manage reports programmatically via the WordPress REST API.
* **AI Knowledgebase Suggestions** — Recommend existing help-article answers to reporters based on their report content. (Settings → AI → Knowledgebase — coming soon)
