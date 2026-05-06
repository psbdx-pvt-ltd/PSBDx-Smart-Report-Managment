<?php
/**
 * Settings and maintenance (repair) tools for PSBDx Smart Report Management.
 *
 * @package PSBDx_Smart_Report_Management
 * @since   1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PSBDX_SRM_Admin_Tools
 *
 * Registers Settings (custom statuses) and Repair & Reset admin pages.
 *
 * @since 1.2.0
 */
class PSBDX_SRM_Admin_Tools {

	/**
	 * Settings submenu slug.
	 *
	 * @since 1.2.0
	 * @var string
	 */
	const PAGE_SETTINGS = 'psbdx-srm-settings';

	/**
	 * Repair submenu slug.
	 *
	 * @since 1.2.0
	 * @var string
	 */
	const PAGE_REPAIR = 'psbdx-srm-repair';

	/**
	 * Constructor.
	 *
	 * @since 1.2.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_pages' ), 100 );
		add_action( 'admin_init', array( $this, 'handle_settings_save' ) );
		add_action( 'admin_init', array( $this, 'handle_repair_actions' ) );
	}

	/**
	 * Registers submenu pages under the main PSBDx Reports menu.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function register_pages() {
		add_submenu_page(
			PSBDX_SRM_Post_Types::ADMIN_MENU_SLUG,
			__( 'Report Settings', 'psbdx-smart-report-management' ),
			__( 'Settings', 'psbdx-smart-report-management' ),
			'manage_options',
			self::PAGE_SETTINGS,
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			PSBDX_SRM_Post_Types::ADMIN_MENU_SLUG,
			__( 'Repair & Reset', 'psbdx-smart-report-management' ),
			__( 'Repair & Reset', 'psbdx-smart-report-management' ),
			'manage_options',
			self::PAGE_REPAIR,
			array( $this, 'render_repair_page' )
		);
	}

	/**
	 * Saves custom statuses from the settings form.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function handle_settings_save() {
		if ( ! isset( $_POST['psbdx_srm_save_settings'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'psbdx_srm_settings' );

		$keys    = isset( $_POST['psbdx_srm_status_key'] ) ? wp_unslash( $_POST['psbdx_srm_status_key'] ) : array();
		$labels  = isset( $_POST['psbdx_srm_status_label'] ) ? wp_unslash( $_POST['psbdx_srm_status_label'] ) : array();
		$bgs     = isset( $_POST['psbdx_srm_status_bg'] ) ? wp_unslash( $_POST['psbdx_srm_status_bg'] ) : array();
		$colors  = isset( $_POST['psbdx_srm_status_color'] ) ? wp_unslash( $_POST['psbdx_srm_status_color'] ) : array();
		$remove  = isset( $_POST['psbdx_srm_status_remove'] ) ? array_map( 'sanitize_key', wp_unslash( (array) $_POST['psbdx_srm_status_remove'] ) ) : array();

		if ( ! is_array( $keys ) || ! is_array( $labels ) || ! is_array( $bgs ) || ! is_array( $colors ) ) {
			return;
		}

		$count   = count( $labels );
		$new_map = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$label = isset( $labels[ $i ] ) ? sanitize_text_field( $labels[ $i ] ) : '';

			if ( '' === $label ) {
				continue;
			}

			$key = isset( $keys[ $i ] ) ? sanitize_key( $keys[ $i ] ) : '';

			if ( '' === $key || 0 !== strpos( $key, 'psbdx_c_', 0 ) ) {
				$key = 'psbdx_c_' . strtolower( wp_generate_password( 10, false, false ) );
			}

			if ( in_array( $key, $remove, true ) ) {
				continue;
			}

			$bg_raw    = isset( $bgs[ $i ] ) ? (string) $bgs[ $i ] : '';
			$color_raw = isset( $colors[ $i ] ) ? (string) $colors[ $i ] : '';

			$new_map[ $key ] = array(
				'label' => $label,
				'bg'    => $bg_raw,
				'color' => $color_raw,
			);
		}

		$new_map = PSBDX_SRM_Helpers::sanitize_custom_status_map( $new_map );

		update_option( PSBDX_SRM_Helpers::CUSTOM_STATUSES_OPTION, $new_map, false );

		add_settings_error(
			'psbdx_srm_settings',
			'settings_saved',
			__( 'Custom statuses saved.', 'psbdx-smart-report-management' ),
			'success'
		);
	}

	/**
	 * Handles repair POST actions (scan is rendered on GET; destructive ops on POST).
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function handle_repair_actions() {
		if ( ! is_admin() ) {
			return;
		}

		if ( empty( $_POST['psbdx_srm_repair_action'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'psbdx_srm_repair' );

		$action = sanitize_key( wp_unslash( $_POST['psbdx_srm_repair_action'] ) );

		if ( 'clear_rate_limits' === $action ) {
			$deleted = $this->delete_rate_limit_transients();
			add_settings_error(
				'psbdx_srm_repair',
				'cleared_limits',
				sprintf(
					/* translators: %d: number of deleted options rows */
					__( 'Cleared %d rate-limit entries from the database.', 'psbdx-smart-report-management' ),
					(int) $deleted
				),
				'success'
			);
		}

		if ( 'fix_status_meta' === $action ) {
			$fixed = $this->fix_invalid_status_meta();
			add_settings_error(
				'psbdx_srm_repair',
				'fixed_meta',
				sprintf(
					/* translators: %d: number of updated meta rows */
					__( 'Normalized %d report status meta value(s) to “Processing”.', 'psbdx-smart-report-management' ),
					(int) $fixed
				),
				'success'
			);
		}
	}

	/**
	 * Settings screen: custom statuses.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		settings_errors( 'psbdx_srm_settings' );

		$custom = PSBDX_SRM_Helpers::sanitize_custom_status_map( PSBDX_SRM_Helpers::get_custom_statuses_stored() );
		$built  = PSBDX_SRM_Helpers::get_default_statuses();
		?>
		<div class="wrap psbdx-srm-tools">
			<h1><?php esc_html_e( 'Report settings', 'psbdx-smart-report-management' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Add custom report statuses with their own label and colours. Built-in statuses cannot be removed here but remain available everywhere reports are shown.', 'psbdx-smart-report-management' ); ?>
			</p>

			<h2 class="title"><?php esc_html_e( 'Built-in statuses', 'psbdx-smart-report-management' ); ?></h2>
			<table class="widefat striped" style="max-width:920px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Key', 'psbdx-smart-report-management' ); ?></th>
						<th><?php esc_html_e( 'Label', 'psbdx-smart-report-management' ); ?></th>
						<th><?php esc_html_e( 'Preview', 'psbdx-smart-report-management' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $built as $key => $row ) : ?>
					<tr>
						<td><code><?php echo esc_html( $key ); ?></code></td>
						<td><?php echo esc_html( $row['label'] ); ?></td>
						<td>
							<span class="psbdx-badge" style="<?php echo esc_attr( PSBDX_SRM_Helpers::get_status_inline_style( $key ) ); ?>padding:4px 10px;border-radius:999px;">
								<?php echo esc_html( $row['label'] ); ?>
							</span>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<form method="post" action="" style="margin-top:24px;max-width:920px;">
				<?php wp_nonce_field( 'psbdx_srm_settings' ); ?>
				<h2 class="title"><?php esc_html_e( 'Custom statuses', 'psbdx-smart-report-management' ); ?></h2>
				<table class="widefat psbdx-srm-status-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Label', 'psbdx-smart-report-management' ); ?></th>
							<th><?php esc_html_e( 'Background', 'psbdx-smart-report-management' ); ?></th>
							<th><?php esc_html_e( 'Text', 'psbdx-smart-report-management' ); ?></th>
							<th><?php esc_html_e( 'Remove', 'psbdx-smart-report-management' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $custom as $key => $row ) : ?>
						<tr>
							<td>
								<input type="hidden" name="psbdx_srm_status_key[]" value="<?php echo esc_attr( $key ); ?>">
								<input type="text" class="regular-text" name="psbdx_srm_status_label[]" value="<?php echo esc_attr( $row['label'] ); ?>" required>
							</td>
							<td><input type="text" class="psbdx-srm-color" name="psbdx_srm_status_bg[]" value="<?php echo esc_attr( $row['bg'] ); ?>" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" placeholder="#e2e8f0"></td>
							<td><input type="text" class="psbdx-srm-color" name="psbdx_srm_status_color[]" value="<?php echo esc_attr( $row['color'] ); ?>" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" placeholder="#475569"></td>
							<td><label><input type="checkbox" name="psbdx_srm_status_remove[]" value="<?php echo esc_attr( $key ); ?>"> <?php esc_html_e( 'Remove', 'psbdx-smart-report-management' ); ?></label></td>
						</tr>
						<?php endforeach; ?>

						<?php for ( $n = 0; $n < 3; $n++ ) : ?>
						<tr class="psbdx-srm-new-row">
							<td>
								<input type="hidden" name="psbdx_srm_status_key[]" value="">
								<input type="text" class="regular-text" name="psbdx_srm_status_label[]" value="" placeholder="<?php esc_attr_e( 'New status label', 'psbdx-smart-report-management' ); ?>">
							</td>
							<td><input type="text" class="psbdx-srm-color" name="psbdx_srm_status_bg[]" value="#e2e8f0" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$"></td>
							<td><input type="text" class="psbdx-srm-color" name="psbdx_srm_status_color[]" value="#475569" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$"></td>
							<td><span class="description">&mdash;</span></td>
						</tr>
						<?php endfor; ?>
					</tbody>
				</table>
				<p>
					<button type="submit" name="psbdx_srm_save_settings" class="button button-primary" value="1">
						<?php esc_html_e( 'Save custom statuses', 'psbdx-smart-report-management' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Repair & reset screen.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function render_repair_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		settings_errors( 'psbdx_srm_repair' );

		$scan = $this->run_diagnostic_scan();
		?>
		<div class="wrap psbdx-srm-tools">
			<h1><?php esc_html_e( 'Repair & reset', 'psbdx-smart-report-management' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Run a read-only scan to verify plugin data, then use reset tools when you need to recover from rate-limit locks or invalid status values.', 'psbdx-smart-report-management' ); ?>
			</p>

			<h2 class="title"><?php esc_html_e( 'Diagnostic scan', 'psbdx-smart-report-management' ); ?></h2>
			<table class="widefat striped" style="max-width:920px;">
				<tbody>
					<?php foreach ( $scan['lines'] as $line ) : ?>
					<tr>
						<td><?php echo esc_html( $line['label'] ); ?></td>
						<td>
							<?php if ( ! empty( $line['ok'] ) ) : ?>
								<span class="dashicons dashicons-yes-alt" style="color:#16a34a;" aria-hidden="true"></span>
							<?php else : ?>
								<span class="dashicons dashicons-warning" style="color:#ca8a04;" aria-hidden="true"></span>
							<?php endif; ?>
							<?php echo esc_html( $line['detail'] ); ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( ! empty( $scan['invalid_status_sample'] ) ) : ?>
			<p><strong><?php esc_html_e( 'Invalid status samples:', 'psbdx-smart-report-management' ); ?></strong>
				<code><?php echo esc_html( implode( ', ', $scan['invalid_status_sample'] ) ); ?></code>
			</p>
			<?php endif; ?>

			<h2 class="title" style="margin-top:2em;"><?php esc_html_e( 'Maintenance actions', 'psbdx-smart-report-management' ); ?></h2>
			<ul style="max-width:920px;">
				<li>
					<form method="post" style="display:inline;" onsubmit="return confirm('<?php echo esc_js( __( 'Clear all report cooldown / rate-limit locks? Users will be able to submit again immediately.', 'psbdx-smart-report-management' ) ); ?>');">
						<?php wp_nonce_field( 'psbdx_srm_repair' ); ?>
						<input type="hidden" name="psbdx_srm_repair_action" value="clear_rate_limits">
						<button type="submit" class="button"><?php esc_html_e( 'Clear rate-limit transients', 'psbdx-smart-report-management' ); ?></button>
					</form>
					<p class="description"><?php esc_html_e( 'Removes stored cooldown entries (psbdx_cd_*) from the options table so logged-in users are no longer blocked by old rate limits.', 'psbdx-smart-report-management' ); ?></p>
				</li>
				<li style="margin-top:16px;">
					<form method="post" style="display:inline;" onsubmit="return confirm('<?php echo esc_js( __( 'Normalize unknown status values to “Processing”? This updates report log meta in the database.', 'psbdx-smart-report-management' ) ); ?>');">
						<?php wp_nonce_field( 'psbdx_srm_repair' ); ?>
						<input type="hidden" name="psbdx_srm_repair_action" value="fix_status_meta">
						<button type="submit" class="button" <?php disabled( empty( $scan['invalid_status_count'] ) ); ?>>
							<?php esc_html_e( 'Fix invalid status meta', 'psbdx-smart-report-management' ); ?>
						</button>
					</form>
					<p class="description"><?php esc_html_e( 'Sets any report status not recognized by the plugin back to “Processing”.', 'psbdx-smart-report-management' ); ?></p>
				</li>
			</ul>
		</div>
		<?php
	}

	/**
	 * Runs checks that WordPress and this plugin expose reliably.
	 *
	 * @since 1.2.0
	 * @return array{lines: array<int, array{label: string, detail: string, ok: bool}>, invalid_status_count: int, invalid_status_sample: string[]}
	 */
	private function run_diagnostic_scan() {
		global $wpdb;

		$lines   = array();
		$allowed = array_keys( PSBDX_SRM_Helpers::get_statuses() );

		$lines[] = array(
			'label'  => __( 'Database connection', 'psbdx-smart-report-management' ),
			'detail' => __( 'wpdb is available.', 'psbdx-smart-report-management' ),
			'ok'     => true,
		);

		$posts_table = $wpdb->posts;
		$ok_posts    = (bool) $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $posts_table )
		);
		$lines[]     = array(
			'label'  => __( 'Core posts table', 'psbdx-smart-report-management' ),
			'detail' => $ok_posts ? $posts_table : __( 'Posts table not found (unexpected).', 'psbdx-smart-report-management' ),
			'ok'     => $ok_posts,
		);

		$form_ok = post_type_exists( 'psbdx_report_form' );
		$log_ok  = post_type_exists( 'psbdx_report_log' );
		$lines[] = array(
			'label'  => __( 'Plugin post types registered', 'psbdx-smart-report-management' ),
			'detail' => $form_ok && $log_ok
				? __( 'psbdx_report_form and psbdx_report_log are registered.', 'psbdx-smart-report-management' )
				: __( 'One or both post types are missing from this request.', 'psbdx-smart-report-management' ),
			'ok'     => $form_ok && $log_ok,
		);

		$order_form = (int) get_option( 'psbdx_global_order_form_id', 0 );
		$prod_form  = (int) get_option( 'psbdx_global_product_form_id', 0 );

		$lines[] = array(
			'label'  => __( 'Global form options', 'psbdx-smart-report-management' ),
			'detail' => $this->describe_form_option( $order_form, $prod_form ),
			'ok'     => $this->form_options_ok( $order_form, $prod_form ),
		);

		$log_counts_obj = wp_count_posts( 'psbdx_report_log' );
		$published_logs = isset( $log_counts_obj->publish ) ? (int) $log_counts_obj->publish : 0;

		$lines[] = array(
			'label'  => __( 'Report logs', 'psbdx-smart-report-management' ),
			/* translators: %d: number of published logs */
			'detail' => sprintf( __( '%d published report(s) in the database.', 'psbdx-smart-report-management' ), $published_logs ),
			'ok'     => true,
		);

		$invalid_values = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE p.post_type = %s AND pm.meta_key = %s",
				'psbdx_report_log',
				'_psbdx_report_status'
			)
		);

		$invalid_sample = array();
		$invalid_count  = 0;

		if ( is_array( $invalid_values ) ) {
			foreach ( $invalid_values as $value ) {
				$value = (string) $value;

				if ( '' === $value ) {
					continue;
				}

				if ( in_array( $value, $allowed, true ) ) {
					continue;
				}

				++$invalid_count;

				if ( count( $invalid_sample ) < 8 ) {
					$invalid_sample[] = $value;
				}
			}
		}

		$lines[] = array(
			'label'  => __( 'Report status meta', 'psbdx-smart-report-management' ),
			'detail' => $invalid_count
				/* translators: %d: count of invalid meta values */
				? sprintf( __( '%d distinct invalid status value(s) detected.', 'psbdx-smart-report-management' ), $invalid_count )
				: __( 'All stored statuses match the plugin’s allowed list.', 'psbdx-smart-report-management' ),
			'ok'     => 0 === $invalid_count,
		);

		$rate_rows = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_psbdx_cd_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_psbdx_cd_' ) . '%'
			)
		);

		$lines[] = array(
			'label'  => __( 'Rate-limit rows', 'psbdx-smart-report-management' ),
			/* translators: %d: rows in options table */
			'detail' => sprintf( __( '%d matching option row(s) for report cooldowns.', 'psbdx-smart-report-management' ), $rate_rows ),
			'ok'     => true,
		);

		return array(
			'lines'                 => $lines,
			'invalid_status_count'  => $invalid_count,
			'invalid_status_sample' => $invalid_sample,
		);
	}

	/**
	 * @since 1.2.0
	 * @param int $order_form Global order form ID.
	 * @param int $prod_form  Global product form ID.
	 * @return string
	 */
	private function describe_form_option( $order_form, $prod_form ) {
		$parts = array();

		if ( $order_form ) {
			$parts[] = sprintf(
				/* translators: %d: post ID */
				__( 'Order form ID %d — %s', 'psbdx-smart-report-management' ),
				$order_form,
				$this->form_exists_label( $order_form )
			);
		} else {
			$parts[] = __( 'No global order form selected.', 'psbdx-smart-report-management' );
		}

		if ( $prod_form ) {
			$parts[] = sprintf(
				/* translators: %d: post ID */
				__( 'Product form ID %d — %s', 'psbdx-smart-report-management' ),
				$prod_form,
				$this->form_exists_label( $prod_form )
			);
		} else {
			$parts[] = __( 'No global product form selected.', 'psbdx-smart-report-management' );
		}

		return implode( ' ', $parts );
	}

	/**
	 * @since 1.2.0
	 * @param int $order_form Global order form ID.
	 * @param int $prod_form  Global product form ID.
	 * @return bool
	 */
	private function form_options_ok( $order_form, $prod_form ) {
		if ( $order_form && ! $this->form_post_ok( $order_form ) ) {
			return false;
		}

		if ( $prod_form && ! $this->form_post_ok( $prod_form ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @since 1.2.0
	 * @param int $post_id Form post ID.
	 * @return bool
	 */
	private function form_post_ok( $post_id ) {
		return 'psbdx_report_form' === get_post_type( $post_id );
	}

	/**
	 * @since 1.2.0
	 * @param int $post_id Form post ID.
	 * @return string
	 */
	private function form_exists_label( $post_id ) {
		return $this->form_post_ok( $post_id )
			? __( 'valid', 'psbdx-smart-report-management' )
			: __( 'missing or wrong type', 'psbdx-smart-report-management' );
	}

	/**
	 * Deletes cooldown-related transients from the options table.
	 *
	 * @since 1.2.0
	 * @return int Rows deleted.
	 */
	private function delete_rate_limit_transients() {
		global $wpdb;

		$sql = $wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_psbdx_cd_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_psbdx_cd_' ) . '%'
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- built with prepare().
		return (int) $wpdb->query( $sql );
	}

	/**
	 * Sets unrecognized status meta values back to Processing.
	 *
	 * @since 1.2.0
	 * @return int Number of meta rows updated.
	 */
	private function fix_invalid_status_meta() {
		global $wpdb;

		$allowed  = array_keys( PSBDX_SRM_Helpers::get_statuses() );
		$values   = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE p.post_type = %s AND pm.meta_key = %s",
				'psbdx_report_log',
				'_psbdx_report_status'
			)
		);

		if ( ! is_array( $values ) ) {
			return 0;
		}

		$invalid = array();

		foreach ( $values as $value ) {
			$value = (string) $value;

			if ( '' === $value || in_array( $value, $allowed, true ) ) {
				continue;
			}

			$invalid[] = $value;
		}

		if ( ! $invalid ) {
			return 0;
		}

		$updated = 0;

		foreach ( $invalid as $bad ) {
			$updated += (int) $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->postmeta} pm
					INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
					SET pm.meta_value = %s
					WHERE p.post_type = %s AND pm.meta_key = %s AND pm.meta_value = %s",
					'Processing',
					'psbdx_report_log',
					'_psbdx_report_status',
					$bad
				)
			);
		}

		return $updated;
	}
}
