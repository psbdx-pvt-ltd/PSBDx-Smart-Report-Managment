# PSBDx Smart Report Management

<div align="center">

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://www.php.net/)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org/)
![Stable Release](https://img.shields.io/badge/stable%20release-1.0.1-brightgreen.svg)

**Contributors:** [psbdx](https://github.com/psbdx-pvt-ltd), atwfarhan, [mfhamim](https://github.com/m-farhan-hamim)

</div>

---

## ⚠️ BETA VERSION WARNING

> **Version 1.1.0 is currently in BETA and may contain:**
> - **Security vulnerabilities** that could expose your site to attacks
> - **Stability issues** that may cause unexpected behavior or crashes
> - **Data loss risks** from incomplete features or breaking changes
> - **Breaking changes** between versions without migration paths
> 
> **Use at your own risk in production environments.** We recommend testing thoroughly in a staging/development environment first. Please report any issues to help us improve stability and security before the stable release.

---

## 📋 Overview

**PSBDx Smart Report Management** is an AJAX-powered report management system for WordPress e-commerce and learning platforms. It provides your customers with a seamless, non-disruptive way to report issues, submit complaints, or track order problems directly from your site.

### ✨ Key Highlights

- **Zero Page Reloads** — AJAX-powered modal forms for instant submissions
- **Mobile-First Design** — Responsive with iOS safe-area support
- **Built-in Rate Limiting** — Prevent spam with per-form cooldowns
- **Auto-Order Linking** — Automatically connect reports to WooCommerce orders
- **Admin Dashboard Widget** — Real-time overview of report statuses
- **Fully Configurable** — Customize reasons, fields, statuses, and more
- **No Dependencies** — Works standalone; optional WooCommerce & LearnPress integration
- **HPOS Compatible** — Full support for WordPress High-Performance Order Storage

---

## 🚀 Features

### Core Features

- ✅ **AJAX Modal Report Form** — No page reloads, clean user experience
- ✅ **Mobile-First Responsive Design** — With iOS safe-area support
- ✅ **Server-Side Identity Collection** — Reporter name and email collected securely from WordPress session (never editable by users)
- ✅ **Admin Identity Toggle** — Show or hide reporter identity card in the form
- ✅ **Per-Form Rate Limiting** — Enforced on frontend and server (configurable cooldown in minutes)
- ✅ **E-Commerce Order Auto-Linking** — Reports from order pages automatically link to the order in admin
- ✅ **Admin Dashboard Widget** — Live status counts and recent reports at a glance
- ✅ **Configurable Report Reasons** — Comma-separated list with automatic "Other" option
- ✅ **Optional Extra Fields** — Transaction ID, Coupon Code, custom fields, etc.
- ✅ **Flexible Contact Field** — Required or optional per form
- ✅ **Five Color-Coded Status Badges** — Processing, Contacting, Waiting, Solved, Failed
- ✅ **Rich Admin List Columns** — Reporter (with avatar), Linked Order, Status, Reported Item
- ✅ **HPOS Support** — Compatible with High-Performance Order Storage
- ✅ **LearnPress Integration** — Support for courses, lessons, and quizzes

### Shortcodes

- `[psbdx_report id="X"]` — Display report button and modal form
- `[psbdx_user_reports]` — Show paginated table of logged-in user's reports

---

## 📦 Requirements

| Requirement | Version |
|------------|---------|
| **WordPress** | 5.8+ |
| **PHP** | 7.4+ |
| **License** | [GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html) |

### Optional Dependencies

- **WooCommerce 3.0+** — For e-commerce order linking and features
- **LearnPress** — For course, lesson, and quiz page support

---

## 📥 Installation

### Via WordPress Admin Panel

1. Go to **Plugins → Add New**
2. Search for "PSBDx Smart Report Management"
3. Click **Install Now**, then **Activate**
4. Navigate to **Report Forms** in the sidebar
5. Click **Add New Form** and configure your form
6. Copy the shortcode from the **Shortcode** meta box
7. Paste it on any page, post, or widget area

### Manual Installation

1. Download the latest release from GitHub
2. Extract to `/wp-content/plugins/psbdx-smart-report-management/`
3. Go to **Plugins → Installed Plugins**
4. Click **Activate** next to "PSBDx Smart Report Management"
5. Configure forms under **Report Forms** in the admin sidebar

### Enable Global Auto-Display

To show the report button on all product/order pages automatically:
1. Edit a report form
2. Enable **Auto-Display on Products/Orders** in the form settings
3. Save — the form will now appear on all applicable pages

---

## 🔧 Configuration

### Create a New Report Form

1. Go to **Report Forms → Add New**
2. Enter a form title (e.g., "Order Issues", "Product Feedback")
3. Configure in the settings panel:
   - **Report Reasons** — Comma-separated list
   - **Cooldown Period** — Minutes between submissions (default: 30)
   - **Show Reporter Identity** — Toggle visibility of name/email card
   - **Extra Fields** — Add custom fields (Transaction ID, etc.)
   - **Contact Field** — Mark as required or optional
   - **Auto-Display** — Show on all products/courses or per-item

4. Click **Publish**
5. Copy the shortcode and paste where needed

### Custom Report Statuses

1. Go to **PSBDx Reports → Settings**
2. Click **Add New Status**
3. Enter status name and choose background/text colors
4. Save — admins can now assign this status to reports

### Maintenance Tools

**PSBDx Reports → Tools & Repair**
- **Diagnostic Scan** — Read-only system health check
- **Clear Rate Limits** — Reset cooldown transients
- **Normalize Statuses** — Fix invalid status values

---

## ❓ FAQ

### Can guests submit reports?
**Yes.** Guest reports are logged without a user association. The reporter name defaults to "Guest".

### Can I hide the reporter identity card?
**Yes.** Each form has a "User Identity Display" toggle. When disabled, the name/email card is hidden from the form — but identity is still collected server-side for admin records.

### How does rate limiting work?
Each form has a configurable cooldown (in minutes, default 30). After a submission, users cannot submit via that form again until the cooldown expires. This is enforced both on the frontend (form hidden) and server-side (request rejected even if UI is bypassed).

### Does this plugin require other plugins?
**No.** PSBDx Smart Report Management works standalone. WooCommerce and LearnPress integrations activate automatically when detected.

### What is the order auto-link feature?
When a user submits a report from an order page (e.g., My Account > Orders > View Order), the plugin automatically detects and stores the order ID. The admin Report Log displays a direct link to the order, and the user's report history shows the order number.

### Is it HPOS compatible?
**Yes.** The plugin declares HPOS compatibility and uses `wc_get_order()` with `get_edit_order_url()` for all order interactions.

### Where can I find full documentation?
Visit our documentation portal: https://dev.psbdx.xyz/documentations/psbdx-smart-report-managment/

---

## 📝 Usage Examples

### Basic Shortcode Usage

```
[psbdx_report id="123"]
```

Displays a report button that opens a modal form. Replace `123` with your Report Form post ID (shown in the Shortcode meta box).

### Display User's Report History

```
[psbdx_user_reports]
```

Shows a paginated table of reports submitted by the currently logged-in user.

### Auto-Display on All Products

1. Create a report form in **Report Forms → Add New**
2. Toggle **Auto-Display on All Products** in the form settings
3. Save — the report button now appears on all product pages automatically

---

## 📂 Plugin Structure

```
psbdx-smart-report-management/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── includes/
│   ├── class-*.php
│   └── functions.php
├── admin/
│   ├── pages/
│   ├── metaboxes/
│   └── class-admin.php
├── public/
│   ├── shortcodes/
│   └── class-public.php
├── psbdx-smart-report-management.php
├── README.md
└── LICENSE
```

---

## 🐛 Changelog

### [1.1.1](https://dev.psbdx.xyz/v1-1-0-summary-psrm/) — Beta

**Improvements:**
- ⚡ Now admin can add unlimited custom statuses.
  
### [1.1.0](https://dev.psbdx.xyz/v1-1-0-summary-psrm/) — Beta

**New Features:**
- ✨ Unified admin menu — Report Logs now grouped under main PSBDx Reports menu
- ✨ Custom statuses — Admins can add custom report statuses with custom colors
- ✨ Repair & Reset tools — Maintenance screen with diagnostic scan and utilities
- ✨ Conflict guard — Auto-detects plugin conflicts and prevents fatal errors

**Improvements:**
- ⚡ Optimized dashboard status counts to reduce database load

### [1.0.1](https://dev.psbdx.xyz/v1-0-1-summary-psrm/)

**New Features:**
- 📢 Admin Review Notice — Dismissible notification requesting WordPress.org review
- 📚 Documentation Link — Quick access link on WordPress Plugins page

**Improvements:**
- 🔧 Enhanced multisite compatibility
- 🌍 Per-site language settings support
- 🔄 Lazy activation stamping for backward compatibility

### [1.0.0](https://dev.psbdx.xyz/v1-0-0-summary-psrm/) — Launch

- 🎉 Initial release
- Full plugin architecture following WordPress standards
- AJAX modal with mobile-first design
- Server-side identity collection
- Per-form rate limiting
- WooCommerce order auto-link
- Admin dashboard widget
- LearnPress integration

---
