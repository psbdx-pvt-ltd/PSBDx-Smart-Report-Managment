<?php
/**
 * Uninstall handler.
 *
 * Runs once, only when the plugin is deleted from the Plugins screen (not
 * on ordinary deactivation) — WordPress core requires this exact filename
 * and calls it directly rather than firing a hook.
 *
 * By default this only removes this plugin's own SETTINGS (options,
 * scheduled cron events, transients) — it deliberately leaves your actual
 * data alone: report forms, submitted reports/tickets, replies, agents,
 * and API keys/sessions all stay in the database untouched. That data
 * represents real support history, not plugin configuration, and silently
 * wiping it out just because the plugin was deleted would be surprising
 * and, for tickets, potentially compliance-relevant.
 *
 * If you deliberately want a full wipe (e.g. decommissioning a staging
 * site), define this constant in wp-config.php *before* deleting the
 * plugin from the Plugins screen:
 *
 *     define( 'PSBDX_SRM_UNINSTALL_DELETE_ALL_DATA', true );
 *
 * @package PSBDx_Smart_Report_Management
 */

// This file is only ever loaded by WordPress core during an uninstall —
// bail immediately if accessed any other way.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

/**
 * Deletes every option (and, on multisite, every site option) whose name
 * starts with a given prefix — covers all of this plugin's settings,
 * which are consistently namespaced `psbdx_srm_*` / `psbdx_global_*`.
 *
 * @param string $prefix  Option-name prefix, already SQL-LIKE-escaped by the caller if needed.
 * @return void
 */
function psbdx_srm_uninstall_delete_options_like( $prefix ) {
	global $wpdb;

	$like = $wpdb->esc_like( $prefix ) . '%';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- uninstall.php runs once, outside normal request flow; there is no option-name index to use via the Options API for a prefix wildcard, and no need to cache a query that never runs again.
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );

	if ( is_multisite() ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- see above.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s", $like ) );
	}
}

/**
 * Core, always-safe cleanup: this plugin's own settings, transients, and
 * scheduled cron events. Never touches forms, reports, replies, agents,
 * or API keys/sessions.
 *
 * @return void
 */
function psbdx_srm_uninstall_settings() {
	psbdx_srm_uninstall_delete_options_like( 'psbdx_srm_' );
	psbdx_srm_uninstall_delete_options_like( 'psbdx_global_' );
	psbdx_srm_uninstall_delete_options_like( '_transient_psbdx_srm_' );
	psbdx_srm_uninstall_delete_options_like( '_transient_timeout_psbdx_srm_' );

	wp_clear_scheduled_hook( 'psbdx_srm_ai_log_cleanup' );
	wp_clear_scheduled_hook( 'psbdx_srm_hosting_initial_check' );
}

/**
 * Optional full wipe — forms, reports, replies, agents/handover history,
 * and this plugin's custom tables. Only runs if the site owner explicitly
 * opted in via the PSBDX_SRM_UNINSTALL_DELETE_ALL_DATA constant (see the
 * file doc-comment above).
 *
 * @return void
 */
function psbdx_srm_uninstall_all_data() {
	global $wpdb;

	// Report-form definitions and submitted reports/tickets (both custom
	// post types), plus all of their postmeta.
	foreach ( array( 'psbdx_report_form', 'psbdx_report_log' ) as $post_type ) {
		$post_ids = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'numberposts'    => -1,
				'fields'         => 'ids',
				'suppress_filters' => true,
			)
		);

		foreach ( $post_ids as $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}

	// This plugin's own custom tables (replies, agents, agent activity
	// log, handover requests, AI request/response log, API keys/sessions).
	foreach (
		array(
			'psbdx_srm_replies',
			'psbdx_srm_agents',
			'psbdx_srm_agent_log',
			'psbdx_srm_handover_requests',
			'psbdx_srm_ai_log',
			'psbdx_srm_api_keys',
			'psbdx_srm_api_sessions',
		) as $table
	) {
		// The 7 table-name literals above are hardcoded in this file, never
		// derived from user input — but this extra whitelist check (plus
		// stripping anything that isn't [a-z0-9_]) keeps `$table` provably
		// safe to interpolate even if this array is ever extended carelessly.
		$table = preg_replace( '/[^a-z0-9_]/', '', $table );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- uninstall-time DROP TABLE of this plugin's own tables, name sanitized to [a-z0-9_] immediately above; $wpdb->prepare() has no placeholder for identifiers (table names) pre-WP-6.2's %i, and this runs once, so there's no dbDelta/Options-API equivalent to reach for instead.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" );
	}
}

psbdx_srm_uninstall_settings();

if ( defined( 'PSBDX_SRM_UNINSTALL_DELETE_ALL_DATA' ) && true === PSBDX_SRM_UNINSTALL_DELETE_ALL_DATA ) {
	psbdx_srm_uninstall_all_data();
}
