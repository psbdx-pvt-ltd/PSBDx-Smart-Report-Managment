<?php
/**
 * Frontend shortcodes for PSBDx Smart Report Management.
 *
 * @package PSBDx_Smart_Report_Management
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PSBDX_SRM_Shortcodes
 *
 * Registers and renders the [psbdx_report] and [psbdx_user_reports] shortcodes.
 *
 * @since 1.0.0
 */
class PSBDX_SRM_Shortcodes {

	/**
	 * Build the current front-end request URL for report source tracking.
	 *
	 * @since 1.1.0
	 * @return string Full URL (scheme + host + path + query), or home URL as fallback.
	 */
	private static function get_current_page_url() {
		if ( isset( $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'] ) ) {
			$scheme = is_ssl() ? 'https' : 'http';
			$host   = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) );
			$path   = preg_replace( '#[\r\n\0]#', '', wp_unslash( $_SERVER['REQUEST_URI'] ) );

			if ( '' !== $host && '' !== $path ) {
				return esc_url_raw( $scheme . '://' . $host . $path );
			}
		}

		return home_url( '/' );
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_shortcode( 'psbdx_report',       array( $this, 'render_report_button' ) );
		add_shortcode( 'psbdx_user_reports', array( $this, 'render_user_reports' ) );
	}

	// =========================================================================
	// [psbdx_report id="X"]
	// =========================================================================

	/**
	 * Render the report button + modal form shortcode.
	 *
	 * @since  1.0.0
	 * @param  array $atts  Shortcode attributes.
	 * @return string       HTML output.
	 */
	public function render_report_button( $atts ) {
		global $wp;

		$atts    = shortcode_atts( array( 'id' => 0 ), $atts, 'psbdx_report' );
		$form_id = absint( $atts['id'] );

		if ( ! $form_id || 'psbdx_report_form' !== get_post_type( $form_id ) ) {
			return '';
		}

		// Form settings.
		$btn_text      = get_post_meta( $form_id, '_psbdx_btn_text',        true ) ?: __( 'Report Issue', 'psbdx-smart-report-management' );
		$contact_label = get_post_meta( $form_id, '_psbdx_contact_label',   true ) ?: __( 'WhatsApp Number', 'psbdx-smart-report-management' );
		$contact_req   = ( 'yes' === get_post_meta( $form_id, '_psbdx_contact_required', true ) );
		$show_identity = get_post_meta( $form_id, '_psbdx_show_identity',   true );
		$show_identity = ( '' === $show_identity ) ? 'yes' : $show_identity;
		$cooldown_mins = (int) ( get_post_meta( $form_id, '_psbdx_cooldown_mins', true ) ?: 30 );

		$reasons       = PSBDX_SRM_Helpers::get_form_reasons( $form_id );
		$custom_fields = PSBDX_SRM_Helpers::get_custom_fields( $form_id );

		// Current page context (full URL including path and query; works with subfolder installs).
		$current_url = self::get_current_page_url();
		$woo_order_id = 0;

		if ( function_exists( 'is_account_page' ) && is_account_page() && isset( $wp->query_vars['view-order'] ) ) {
			$woo_order_id  = absint( $wp->query_vars['view-order'] );
			$current_title = sprintf(
				/* translators: %d: order number */
				__( 'Order #%d', 'psbdx-smart-report-management' ),
				$woo_order_id
			);
		} else {
			$current_title = get_the_title();
		}

		// Current user.
		$user         = wp_get_current_user();
		$is_logged_in = ( $user->ID > 0 );
		$user_name    = $is_logged_in ? $user->display_name : '';
		$user_email   = $is_logged_in ? $user->user_email   : '';

		// Rate limit check (display-only; server also enforces).
		$cooldown_msg = '';
		if ( $cooldown_mins > 0 && $is_logged_in ) {
			$transient = get_transient( 'psbdx_cd_' . $user->ID . '_' . $form_id );

			if ( false !== $transient ) {
				$remaining    = (int) ceil( ( $transient + $cooldown_mins * 60 - time() ) / 60 );
				$cooldown_msg = sprintf(
					/* translators: %d: minutes remaining */
					__( 'You already submitted a report recently. Please wait %d more minute(s) before trying again.', 'psbdx-smart-report-management' ),
					max( 1, $remaining )
				);
			}
		}

		$uid   = $form_id . '-' . wp_rand( 1000, 9999 );
		$nonce = wp_create_nonce( 'psbdx_srm_submit_nonce' );

		ob_start();
		?>
		<div class="psbdx-btn-wrap">
			<button class="psbdx-trigger-btn"
				type="button"
				data-target="psbdx-modal-<?php echo esc_attr( $uid ); ?>"
				aria-haspopup="dialog">
				<?php
				// Inline SVG flag icon.
				echo '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
				<?php echo esc_html( $btn_text ); ?>
			</button>
		</div>

		<div id="psbdx-modal-<?php echo esc_attr( $uid ); ?>"
			class="psbdx-modal"
			role="dialog"
			aria-modal="true"
			aria-labelledby="psbdx-modal-title-<?php echo esc_attr( $uid ); ?>">

			<div class="psbdx-modal-panel">

				<button class="psbdx-modal-close" type="button"
					aria-label="<?php esc_attr_e( 'Close report form', 'psbdx-smart-report-management' ); ?>">
					&times;
				</button>

				<div class="psbdx-modal-header">
					<div class="psbdx-modal-icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
					</div>
					<h2 class="psbdx-modal-title" id="psbdx-modal-title-<?php echo esc_attr( $uid ); ?>">
						<?php echo esc_html( $btn_text ); ?>
					</h2>
				</div>

				<div class="psbdx-context-bar" role="note">
					<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
					<span>
						<?php esc_html_e( 'Reporting:', 'psbdx-smart-report-management' ); ?>
						<strong><?php echo esc_html( $current_title ); ?></strong>
					</span>
					<?php if ( $woo_order_id ) : ?>
						<span class="psbdx-order-tag"><?php esc_html_e( 'Order', 'psbdx-smart-report-management' ); ?></span>
					<?php endif; ?>
				</div>

				<?php if ( $cooldown_msg ) : ?>
				<div class="psbdx-notice psbdx-notice-warn" role="alert">
					<?php echo esc_html( $cooldown_msg ); ?>
				</div>
				<?php endif; ?>

				<form class="psbdx-report-form" novalidate <?php echo $cooldown_msg ? 'style="display:none;"' : ''; ?>>
					<input type="hidden" name="action"        value="psbdx_srm_submit">
					<input type="hidden" name="security"      value="<?php echo esc_attr( $nonce ); ?>">
					<input type="hidden" name="form_id"       value="<?php echo esc_attr( $form_id ); ?>">
					<input type="hidden" name="source_url"    value="<?php echo esc_url( $current_url ); ?>">
					<input type="hidden" name="source_title"  value="<?php echo esc_attr( $current_title ); ?>">
					<input type="hidden" name="woo_order_id"  value="<?php echo esc_attr( $woo_order_id ); ?>">

					<?php if ( ! $is_logged_in ) : ?>
					<div class="psbdx-notice psbdx-notice-info">
						<?php esc_html_e( 'You are submitting as a guest. Log in to link reports to your account.', 'psbdx-smart-report-management' ); ?>
					</div>
					<?php elseif ( 'yes' === $show_identity ) : ?>
					<div class="psbdx-identity-card" aria-label="<?php esc_attr_e( 'Submitting as', 'psbdx-smart-report-management' ); ?>">
						<?php echo get_avatar( $user->ID, 36, '', '', array( 'class' => 'psbdx-identity-avatar' ) ); ?>
						<div class="psbdx-identity-meta">
							<span class="psbdx-identity-name"><?php echo esc_html( $user_name ); ?></span>
							<span class="psbdx-identity-email"><?php echo esc_html( $user_email ); ?></span>
						</div>
						<span class="psbdx-identity-badge" title="<?php esc_attr_e( 'Verified from your WordPress account', 'psbdx-smart-report-management' ); ?>" aria-label="<?php esc_attr_e( 'Verified', 'psbdx-smart-report-management' ); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#22c55e" aria-hidden="true"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5l-3.5-3.5 1.41-1.41L10 13.67l6.09-6.09 1.41 1.41L10 16.5z"/></svg>
						</span>
					</div>
					<?php endif; ?>

					<?php
					$contact_id = 'psbdx-contact-' . $uid;
					?>
					<div class="psbdx-field">
						<label for="<?php echo esc_attr( $contact_id ); ?>">
							<?php echo esc_html( $contact_label ); ?>
							<?php if ( $contact_req ) : ?>
								<span class="psbdx-required" aria-label="<?php esc_attr_e( 'required', 'psbdx-smart-report-management' ); ?>">*</span>
							<?php else : ?>
								<span class="psbdx-optional"><?php esc_html_e( 'optional', 'psbdx-smart-report-management' ); ?></span>
							<?php endif; ?>
						</label>
						<input type="text"
							id="<?php echo esc_attr( $contact_id ); ?>"
							name="contact_value"
							<?php echo $contact_req ? 'required' : ''; ?>
							autocomplete="off"
							placeholder="<?php echo esc_attr( $contact_label ); ?>">
					</div>

					<?php foreach ( $custom_fields as $field ) : ?>
					<div class="psbdx-field">
						<label><?php echo esc_html( $field ); ?></label>
						<input type="text"
							name="psbdx_custom[<?php echo esc_attr( $field ); ?>]"
							placeholder="<?php echo esc_attr( $field ); ?>">
					</div>
					<?php endforeach; ?>

					<?php $reason_id = 'psbdx-reason-' . $uid; ?>
					<div class="psbdx-field">
						<label for="<?php echo esc_attr( $reason_id ); ?>">
							<?php esc_html_e( 'Reason', 'psbdx-smart-report-management' ); ?>
							<span class="psbdx-required" aria-label="<?php esc_attr_e( 'required', 'psbdx-smart-report-management' ); ?>">*</span>
						</label>
						<select id="<?php echo esc_attr( $reason_id ); ?>"
							name="report_reason"
							class="psbdx-reason-select"
							required>
							<?php foreach ( $reasons as $reason ) : ?>
								<option value="<?php echo esc_attr( $reason ); ?>"><?php echo esc_html( $reason ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="psbdx-field psbdx-other-reason" style="display:none;">
						<label for="psbdx-other-reason-<?php echo esc_attr( $uid ); ?>">
							<?php esc_html_e( 'Please specify', 'psbdx-smart-report-management' ); ?>
							<span class="psbdx-required" aria-label="<?php esc_attr_e( 'required', 'psbdx-smart-report-management' ); ?>">*</span>
						</label>
						<input type="text"
							id="psbdx-other-reason-<?php echo esc_attr( $uid ); ?>"
							name="custom_reason"
							placeholder="<?php esc_attr_e( 'Describe your reason...', 'psbdx-smart-report-management' ); ?>">
					</div>

					<?php $details_id = 'psbdx-details-' . $uid; ?>
					<div class="psbdx-field">
						<label for="<?php echo esc_attr( $details_id ); ?>">
							<?php esc_html_e( 'Details', 'psbdx-smart-report-management' ); ?>
							<span class="psbdx-required" aria-label="<?php esc_attr_e( 'required', 'psbdx-smart-report-management' ); ?>">*</span>
						</label>
						<textarea id="<?php echo esc_attr( $details_id ); ?>"
							name="report_details"
							required
							rows="4"
							placeholder="<?php esc_attr_e( 'Describe the issue in detail...', 'psbdx-smart-report-management' ); ?>"></textarea>
					</div>

					<button type="submit" class="psbdx-submit-btn">
						<span class="psbdx-btn-label"><?php esc_html_e( 'Submit Report', 'psbdx-smart-report-management' ); ?></span>
						<span class="psbdx-btn-spinner" aria-hidden="true" style="display:none;">
							<span class="psbdx-spinner"></span>
							<?php esc_html_e( 'Sending&hellip;', 'psbdx-smart-report-management' ); ?>
						</span>
					</button>
				</form>

				<div class="psbdx-form-response" role="alert" aria-live="polite"></div>

			</div><!-- .psbdx-modal-panel -->
		</div><!-- .psbdx-modal -->
		<?php

		return ob_get_clean();
	}

	// =========================================================================
	// [psbdx_user_reports]
	// =========================================================================

	/**
	 * Render the user's report history table shortcode.
	 *
	 * @since  1.0.0
	 * @param  array $atts  Shortcode attributes (unused).
	 * @return string       HTML output.
	 */
	public function render_user_reports( $atts ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		if ( ! is_user_logged_in() ) {
			return '<p class="psbdx-notice psbdx-notice-warn">'
				. esc_html__( 'Please log in to view your report history.', 'psbdx-smart-report-management' )
				. '</p>';
		}

		$paged = max( 1, get_query_var( 'paged' ) );
		$query = new WP_Query( array(
			'post_type'      => 'psbdx_report_log',
			'post_status'    => 'publish',
			'author'         => get_current_user_id(),
			'posts_per_page' => 10,
			'paged'          => $paged,
		) );

		ob_start();
		?>
		<div class="psbdx-history-wrap">

			<div class="psbdx-history-header">
				<h3 class="psbdx-history-title">
					<?php esc_html_e( 'My Reports', 'psbdx-smart-report-management' ); ?>
				</h3>
				<span class="psbdx-history-total">
					<?php
					printf(
						/* translators: %d: total number of reports */
						esc_html( _n( '%d report', '%d reports', $query->found_posts, 'psbdx-smart-report-management' ) ),
						(int) $query->found_posts
					);
					?>
				</span>
			</div>

			<?php if ( $query->have_posts() ) : ?>

			<div class="psbdx-table-scroll">
				<table class="psbdx-history-table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Date',   'psbdx-smart-report-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Reason', 'psbdx-smart-report-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Status', 'psbdx-smart-report-management' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Item',   'psbdx-smart-report-management' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php while ( $query->have_posts() ) :
						$query->the_post();
						$status    = get_post_meta( get_the_ID(), '_psbdx_report_status', true ) ?: 'Processing';
						$src_title = get_post_meta( get_the_ID(), '_psbdx_source_title',  true );
						$src_url   = get_post_meta( get_the_ID(), '_psbdx_source_url',    true );
						$order_id  = get_post_meta( get_the_ID(), '_psbdx_woo_order_id',  true );
						$sc        = PSBDX_SRM_Helpers::get_status_class( $status );
						$statuses  = PSBDX_SRM_Helpers::get_statuses();
						$s_label   = isset( $statuses[ $status ] ) ? $statuses[ $status ]['label'] : $status;
						$s_style   = PSBDX_SRM_Helpers::get_status_inline_style( $status );
					?>
					<tr>
						<td data-label="<?php esc_attr_e( 'Date', 'psbdx-smart-report-management' ); ?>">
							<time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>">
								<?php echo esc_html( get_the_date( 'd M Y' ) ); ?>
							</time>
						</td>
						<td data-label="<?php esc_attr_e( 'Reason', 'psbdx-smart-report-management' ); ?>">
							<?php echo esc_html( wp_trim_words( get_the_title(), 6, '&hellip;' ) ); ?>
						</td>
						<td data-label="<?php esc_attr_e( 'Status', 'psbdx-smart-report-management' ); ?>">
							<span class="psbdx-status-chip <?php echo esc_attr( $sc ); ?>" style="<?php echo esc_attr( $s_style ); ?>">
								<?php echo esc_html( $s_label ); ?>
							</span>
						</td>
						<td data-label="<?php esc_attr_e( 'Item', 'psbdx-smart-report-management' ); ?>">
							<?php if ( $order_id ) : ?>
								<span class="psbdx-status-chip psbdx-status-contacting">
									<?php
									printf(
										/* translators: %s: order number */
										esc_html__( 'Order #%s', 'psbdx-smart-report-management' ),
										esc_html( $order_id )
									);
									?>
								</span>
							<?php elseif ( $src_url ) : ?>
								<a href="<?php echo esc_url( $src_url ); ?>"
									target="_blank"
									rel="noopener noreferrer"
									class="psbdx-item-link">
									<?php echo esc_html( $src_title ?: __( 'View Item', 'psbdx-smart-report-management' ) ); ?>
									<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
								</a>
							<?php else : ?>
								<span class="psbdx-muted">&mdash;</span>
							<?php endif; ?>
						</td>
					</tr>
					<?php endwhile; wp_reset_postdata(); ?>
					</tbody>
				</table>
			</div>

			<?php if ( $query->max_num_pages > 1 ) : ?>
			<nav class="psbdx-pagination" aria-label="<?php esc_attr_e( 'Reports pagination', 'psbdx-smart-report-management' ); ?>">
				<?php
				echo paginate_links( array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'total'   => $query->max_num_pages,
					'current' => $paged,
					'format'  => '?paged=%#%',
				) );
				?>
			</nav>
			<?php endif; ?>

			<?php else : ?>
			<div class="psbdx-empty">
				<p><?php esc_html_e( "You haven't submitted any reports yet.", 'psbdx-smart-report-management' ); ?></p>
			</div>
			<?php endif; ?>

		</div>
		<?php
		return ob_get_clean();
	}
}
