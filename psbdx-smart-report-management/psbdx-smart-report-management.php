<?php
/**
 * Plugin Name:       PSBDx Smart Report Management
 * Plugin URI:        https://dev.psbdx.xyz/documentations/psbdx-smart-report-managment/
 * Description:       AJAX-powered smart report management system for e-commerce orders, products, and online courses. HPOS compatible. Includes rate limiting, order auto-linking, and an admin dashboard widget.
 * Version:           1.1.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            PSBDx
 * Author URI:        https://psbdx.xyz/author/mf-hamim/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       psbdx-smart-report-management
 * Domain Path:       /languages
 * Network:           false
 *
 * @package PSBDx_Smart_Report_Management
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'PSBDX_SRM_VERSION',     '1.1.0' );
define( 'PSBDX_SRM_FILE',        __FILE__ );
define( 'PSBDX_SRM_DIR',         plugin_dir_path( __FILE__ ) );
define( 'PSBDX_SRM_URL',         plugin_dir_url( __FILE__ ) );
define( 'PSBDX_SRM_BASENAME',    plugin_basename( __FILE__ ) );

// ─── Includes ────────────────────────────────────────────────────────────────

require_once PSBDX_SRM_DIR . 'includes/class-psbdx-srm-post-types.php';
require_once PSBDX_SRM_DIR . 'includes/class-psbdx-srm-helpers.php';
require_once PSBDX_SRM_DIR . 'includes/class-psbdx-srm-conflict-guard.php';
require_once PSBDX_SRM_DIR . 'admin/class-psbdx-srm-admin.php';
require_once PSBDX_SRM_DIR . 'admin/class-psbdx-srm-admin-tools.php';
require_once PSBDX_SRM_DIR . 'admin/class-psbdx-srm-meta-boxes.php';
require_once PSBDX_SRM_DIR . 'admin/class-psbdx-srm-dashboard-widget.php';
require_once PSBDX_SRM_DIR . 'admin/class-psbdx-srm-woo-integration.php';
require_once PSBDX_SRM_DIR . 'admin/class-psbdx-srm-review-notice.php';
require_once PSBDX_SRM_DIR . 'public/class-psbdx-srm-shortcodes.php';
require_once PSBDX_SRM_DIR . 'public/class-psbdx-srm-ajax.php';
require_once PSBDX_SRM_DIR . 'public/class-psbdx-srm-assets.php';

// ─── Activation ──────────────────────────────────────────────────────────────

/**
 * Runs on single-site activation or per-site when not network-activated.
 *
 * @since 1.1.0
 * @return void
 */
function psbdx_srm_activate() {
	PSBDX_SRM_Review_Notice::on_activation();
}

/**
 * Runs on activation — handles both single-site and multisite network activation.
 *
 * On a multisite network-wide activation WordPress only fires the hook once
 * (for the current site).  We iterate every site so each gets its own
 * activation timestamp, keeping review notice state per-site.
 *
 * @since 1.1.0
 * @param  bool $network_wide  True when activated across the entire network.
 * @return void
 */
function psbdx_srm_activate_multisite( $network_wide ) {
	if ( ! $network_wide || ! is_multisite() ) {
		psbdx_srm_activate();
		return;
	}

	$site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( (int) $site_id );
		PSBDX_SRM_Review_Notice::on_activation();
		restore_current_blog();
	}
}
register_activation_hook( PSBDX_SRM_FILE, 'psbdx_srm_activate_multisite' );

/**
 * When a brand-new site is added to a network where the plugin is already
 * network-active, stamp its activation time immediately.
 *
 * @since 1.1.0
 * @param  WP_Site $new_site  The newly created site.
 * @return void
 */
function psbdx_srm_new_blog( $new_site ) {
	if ( ! is_plugin_active_for_network( PSBDX_SRM_BASENAME ) ) {
		return;
	}

	switch_to_blog( (int) $new_site->blog_id );
	PSBDX_SRM_Review_Notice::on_activation();
	restore_current_blog();
}
add_action( 'wp_insert_site', 'psbdx_srm_new_blog' );

// ─── WooCommerce HPOS compatibility ──────────────────────────────────────────

/**
 * Declare WooCommerce HPOS compatibility.
 *
 * @since 1.0.0
 */
function psbdx_srm_declare_hpos_compat() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			PSBDX_SRM_FILE,
			true
		);
	}
}
add_action( 'before_woocommerce_init', 'psbdx_srm_declare_hpos_compat' );

// ─── Initialisation ───────────────────────────────────────────────────────────

/**
 * Initialise all plugin components.
 *
 * plugins_loaded fires per-site on multisite automatically — each sub-site
 * gets its own instance of every component with no extra work required.
 *
 * @since 1.0.0
 */
function psbdx_srm_init() {
	// Load text domain (respects per-site language settings on multisite).
	load_plugin_textdomain(
		'psbdx-smart-report-management',
		false,
		dirname( PSBDX_SRM_BASENAME ) . '/languages'
	);

	// Lazily stamp activation for sites that existed before v1.1.0.
	PSBDX_SRM_Review_Notice::maybe_set_activated_time();

	new PSBDX_SRM_Post_Types();
	new PSBDX_SRM_Conflict_Guard();
	new PSBDX_SRM_Admin();
	new PSBDX_SRM_Admin_Tools();
	new PSBDX_SRM_Meta_Boxes();
	new PSBDX_SRM_Dashboard_Widget();
	new PSBDX_SRM_Woo_Integration();
	new PSBDX_SRM_Review_Notice();
	new PSBDX_SRM_Shortcodes();
	new PSBDX_SRM_Ajax();
	new PSBDX_SRM_Assets();
}
add_action( 'plugins_loaded', 'psbdx_srm_init', 20 );
