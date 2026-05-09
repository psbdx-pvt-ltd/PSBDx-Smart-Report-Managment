<?php
/**
 * Detects activation conflicts and deactivates offending plugins to avoid fatals.
 *
 * @package PSBDx_Smart_Report_Management
 * @since   1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PSBDX_SRM_Conflict_Guard
 *
 * After any third-party plugin is activated, runs a lightweight health check.
 * If this plugin’s post types or core class are missing, the last-activated
 * plugin is deactivated and an admin notice explains why.
 *
 * @since 1.2.0
 */
class PSBDX_SRM_Conflict_Guard {

	/**
	 * Option key storing the plugin file pending a post-activation check.
	 *
	 * @since 1.2.0
	 * @var string
	 */
	const PENDING_OPTION = 'psbdx_srm_pending_activation_check';

	/**
	 * Constructor.
	 *
	 * @since 1.2.0
	 */
	public function __construct() {
		add_action( 'activated_plugin', array( $this, 'on_activated_plugin' ), 20, 2 );
		add_action( 'admin_notices', array( $this, 'render_conflict_notice' ) );
	}

	/**
	 * Schedules a shutdown health check when another plugin is activated.
	 *
	 * @since 1.2.0
	 * @param string $plugin        Relative plugin path from WP_PLUGIN_DIR.
	 * @param bool   $network_wide  Whether the plugin was network-activated.
	 * @return void
	 */
	public function on_activated_plugin( $plugin, $network_wide ) {
		if ( $network_wide ) {
			return;
		}

		if ( ! apply_filters( 'psbdx_srm_conflict_guard_enabled', true ) ) {
			return;
		}

		if ( plugin_basename( PSBDX_SRM_FILE ) === $plugin ) {
			return;
		}

		update_option(
			self::PENDING_OPTION,
			array(
				'plugin' => $plugin,
				'time'   => time(),
			),
			false
		);

		add_action( 'shutdown', array( $this, 'verify_after_activation' ), 999 );
	}

	/**
	 * Runs at shutdown: if health fails, deactivate the plugin that was just activated.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function verify_after_activation() {
		$pending = get_option( self::PENDING_OPTION );

		delete_option( self::PENDING_OPTION );

		if ( ! is_array( $pending ) || empty( $pending['plugin'] ) ) {
			return;
		}

		if ( $this->health_ok() ) {
			return;
		}

		$target = $pending['plugin'];

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		deactivate_plugins( $target, true );

		$notice = array(
			'plugin' => $target,
			'time'   => isset( $pending['time'] ) ? (int) $pending['time'] : time(),
		);

		set_transient( 'psbdx_srm_conflict_notice_' . get_current_user_id(), $notice, HOUR_IN_SECONDS );

		/**
		 * Fires after this plugin auto-deactivated another plugin due to a failed health check.
		 *
		 * @since 1.2.0
		 * @param string $target  Plugin basename that was deactivated.
		 * @param array  $pending Pending activation payload.
		 */
		do_action( 'psbdx_srm_auto_deactivated_plugin', $target, $pending );
	}

	/**
	 * Whether core plugin surfaces are still registered and loadable.
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	private function health_ok() {
		return post_type_exists( 'psbdx_report_form' )
			&& post_type_exists( 'psbdx_report_log' )
			&& class_exists( 'PSBDX_SRM_Helpers', false );
	}

	/**
	 * Admin notice after an auto-deactivation.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function render_conflict_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$key     = 'psbdx_srm_conflict_notice_' . get_current_user_id();
		$payload = get_transient( $key );

		if ( ! is_array( $payload ) || empty( $payload['plugin'] ) ) {
			return;
		}

		delete_transient( $key );

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$data   = get_plugin_data( WP_PLUGIN_DIR . '/' . $payload['plugin'], false, false );
		$name   = ! empty( $data['Name'] ) ? $data['Name'] : $payload['plugin'];
		$plugin = esc_html( $name );

		printf(
			'<div class="notice notice-warning is-dismissible"><p><strong>%s</strong> %s</p><p>%s</p></div>',
			esc_html__( 'PSBDx Smart Report Management:', 'psbdx-smart-report-management' ),
			sprintf(
				/* translators: %s: plugin name */
				esc_html__( 'To avoid a fatal error, the plugin %s was deactivated automatically after activation because it conflicted with PSBDx Smart Report Management.', 'psbdx-smart-report-management' ),
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				$plugin
			),
			esc_html__( 'You can try enabling it again from the Plugins screen. If the problem returns, leave that plugin inactive or contact its author.', 'psbdx-smart-report-management' )
		);
	}
}
