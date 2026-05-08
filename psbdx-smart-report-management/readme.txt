=== PSBDx Smart Report Management ===
Contributors:      psbdx, atwfarhan, mfhamim
Tags:              report, complaint, issue tracker, ajax, order report
Requires at least: 5.8
Tested up to:      6.9
Requires PHP:      7.4
Stable tag:        1.0.1
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

AJAX-powered report management for e-commerce orders, products, and courses. Rate limiting, order auto-linking, and admin dashboard widget included.

== Description ==

**PSBDx Smart Report Management** gives your customers a clean, fast way to report issues directly from your site â€” no page reloads, no clunky forms, no leaving the page.

Reports are submitted via AJAX and stored in a dedicated admin interface, where your team can track statuses and update them. An admin dashboard widget gives you an at-a-glance overview at all times.

**Key Features**

* AJAX-powered modal report form â€” no page reload
* Mobile-first responsive design with iOS safe-area support
* Reporter identity collected server-side from WordPress session (name and email are never editable by users)
* Admin toggle to show or hide the reporter identity card in the form
* Per-form cooldown / rate limiting (enforced on both frontend and server)
* E-commerce order auto-link â€” reports from order pages are automatically linked to the order in the admin
* Admin dashboard widget with live status counts and recent reports
* Fully configurable report reasons (comma-separated, "Other" always appended)
* Optional extra fields (e.g. Transaction ID, Coupon Code)
* Configurable contact field with required/optional toggle
* Five report statuses with colour-coded badges (Processing, Contacting, Waiting, Solved, Failed)
* Admin list table columns: Reporter (with avatar), Linked Order, Status, Reported Item
* Shortcodes: `[psbdx_report id="X"]` and `[psbdx_user_reports]`
* HPOS (High-Performance Order Storage) compatible
* LearnPress course, lesson, and quiz pages supported
* Auto-display on all products/courses or assign a form per-item

**Shortcodes**

`[psbdx_report id="X"]`
Display a report button and modal form. Replace X with the Report Form post ID shown in the Shortcode box.

`[psbdx_user_reports]`
Display a paginated table of the currently logged-in user's report history.

== Installation ==

1. Upload the `psbdx-smart-report-management` folder to `/wp-content/plugins/`.
2. Activate the plugin through **Plugins > Installed Plugins**.
3. Go to **Report Forms** in the admin sidebar and click **Add New Form**.
4. Configure the form, then copy the shortcode from the **Shortcode** meta box.
5. Paste the shortcode on any page, post, or widget area.

Alternatively, enable global auto-display in the form settings to show the report button on all product or order pages automatically.

== Frequently Asked Questions ==

= Can guests submit reports? =
Yes. Guest reports are logged without a user association. The reporter name defaults to "Guest".

= Can I disable the reporter identity card shown in the form? =
Yes. Each form has an "User Identity Display" toggle in its configuration. When turned off, the read-only name and email card is hidden from the form â€” but identity is still collected server-side for the admin log.

= How does rate limiting work? =
Each form has a configurable cooldown (in minutes, default 30). Once a logged-in user submits a report via a form, they cannot submit again through that same form until the cooldown expires. The cooldown is enforced both on the frontend (form is hidden) and in the AJAX handler (server rejects the request even if the UI is bypassed).

= Does the plugin require any other plugins? =
No. PSBDx Smart Report Management works as a standalone plugin. E-commerce and LearnPress integrations activate automatically when those plugins are present.

= What is the order auto-link feature? =
When a user submits a report from an order page (e.g. My Account > Orders > View Order), the plugin automatically detects and stores the order ID. The admin Report Log shows a direct link to the order, and the user's report history table shows the order number instead of a URL.

= Is it compatible with High-Performance Order Storage (HPOS)? =
Yes. The plugin declares HPOS compatibility and uses `wc_get_order()` with `get_edit_order_url()` for all order links.

= From where I can read all the documentations? =
We are happy to see that you are interested to read the documentations. Please visit https://dev.psbdx.xyz/documentations/psbdx-smart-report-managment/

== Screenshots ==

1. Frontend report button and modal form on a product page.
2. Admin dashboard showing Report Form Management Screen.
3. Admin Report Logs list table with status badges, reporter, and order link columns.
4. Admin Report Form configuration screen.
5. Admin dashboard widget showing report customization screen.

== Changelog ==
= 1.1.1 Beta =

**Improved: Optimization**
* Now admin can unlimited custom statuses.

= 1.1.0 Beta =

**New: Unified admin menu**
* Report Logs are now grouped under the main PSBDx Reports menu for easier management.

**New: Settings page (custom statuses)**
* Admins can add custom report statuses (name + background/text colours).

**New: Repair & Reset tools**
* Added a maintenance screen with a read-only diagnostic scan.
* Added tools to clear rate-limit transients and normalize invalid status values.

**New: Conflict guard**
* Detects plugin activation conflicts and automatically deactivates the conflicting plugin to avoid fatal errors, with an admin notice showing the plugin name.

**Improved: Optimization**
* Dashboard status counts are now calculated more efficiently to reduce database load.

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

* **Email Notifications** â€” Notify the admin on new submissions, and send a confirmation email to the reporter.
* **Status Change Emails** â€” Email the reporter automatically when their report status is updated.
* **CSV Export** â€” Export all report logs as a CSV file from the admin screen.
* **File / Screenshot Attachment** â€” Let users attach a screenshot or file to their report.
* **Internal Admin Notes** â€” Private notes on each report log, visible only to admins.
* **Report Categories / Tags** â€” Organise reports with WordPress taxonomies for easier filtering.
* **Guest Email Verification** â€” Allow non-logged-in users to submit with email verification before saving.
* **Duplicate Detection** â€” Alert admins when a new report closely matches an existing open one.
* **Report Priority Levels** â€” Assign Low / Medium / High / Critical priority to reports.
* **REST API Endpoints** â€” Query and manage reports programmatically via the WordPress REST API.
