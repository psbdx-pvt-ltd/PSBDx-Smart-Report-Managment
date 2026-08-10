<?php
/**
 * Frontend report detail page for PSBDx Smart Report Management.
 *
 * A lightweight "virtual" page — no dedicated WordPress page or rewrite
 * rule needed — reachable by adding `?psbdx_ticket=TICKET-ID` to any URL
 * on the site. Shows a report's full status/category/priority/details and
 * its reply thread (when replies are enabled). Only the person who filed
 * the report (matched by account or, for guests, by email) or an admin
 * can view it; everyone else gets a genuine 404, exactly like requesting
 * a page that doesn't exist.
 *
 * @package PSBDx_Smart_Report_Management
 * @since   1.4.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PSBDX_SRM_Report_Page
 *
 * @since 1.4.2
 */
class PSBDX_SRM_Report_Page {

	/**
	 * The query string parameter that identifies a report to view.
	 *
	 * @since 1.4.2
	 * @var string
	 */
	const PARAM = 'psbdx_ticket';

	/**
	 * The query string parameter carrying a guest's email for verification.
	 *
	 * @since 1.4.2
	 * @var string
	 */
	const EMAIL_PARAM = 'psbdx_email';

	/**
	 * Constructor.
	 *
	 * @since 1.4.2
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_render' ) );
	}

	/**
	 * Builds the frontend URL for viewing a report, for use in emails and
	 * the report history table. Guest (non-logged-in) links include the
	 * reporter's email so the page can verify identity without an account;
	 * logged-in owners don't need it (their session already proves it).
	 *
	 * @since 1.4.2
	 * @param  int  $report_id     Report log post ID.
	 * @param  bool $include_email Whether to embed the reporter's email in the URL.
	 * @return string
	 */
	public static function get_url( $report_id, $include_email = false ) {
		$ticket_id = PSBDX_SRM_Helpers::get_ticket_id( $report_id );

		if ( '' === $ticket_id ) {
			return '';
		}

		$args = array( self::PARAM => $ticket_id );

		if ( $include_email ) {
			$email = get_post_meta( $report_id, '_psbdx_reporter_email', true );
			if ( $email ) {
				$args[ self::EMAIL_PARAM ] = rawurlencode( $email );
			}
		}

		return add_query_arg( $args, home_url( '/' ) );
	}

	/**
	 * Intercepts requests carrying the ticket query param and renders the
	 * detail page (or a genuine 404) in place of whatever page was requested.
	 *
	 * @since  1.4.2
	 * @return void  Does not return when it handles the request (exits after rendering or 404ing).
	 */
	public function maybe_render() {
		if ( empty( $_GET[ self::PARAM ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page view, identity is verified separately below.
			return;
		}

		$ticket_id = sanitize_text_field( wp_unslash( $_GET[ self::PARAM ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$report_id = PSBDX_SRM_Helpers::get_report_by_ticket_id( $ticket_id );

		$email = isset( $_GET[ self::EMAIL_PARAM ] ) ? sanitize_email( wp_unslash( $_GET[ self::EMAIL_PARAM ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $report_id || ! PSBDX_SRM_Replies::can_access_report( $report_id, $email ) ) {
			$this->send_404();
			return;
		}

		$this->render( $report_id, $email );
		exit;
	}

	/**
	 * Serves a genuine 404 response using the active theme's 404 template,
	 * so an unauthorized visitor sees exactly what they'd see for any other
	 * nonexistent page on the site — no hint a report exists at all.
	 *
	 * @since  1.4.2
	 * @return void  Does not return.
	 */
	private function send_404() {
		global $wp_query;

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();

		$template = get_query_template( '404' );

		if ( $template ) {
			include $template;
		} else {
			wp_die( esc_html__( 'Page not found.', 'psbdx-smart-report-management' ), '', array( 'response' => 404 ) );
		}

		exit;
	}

	/**
	 * Renders the report detail page.
	 *
	 * @since  1.4.2
	 * @param  int    $report_id  Report log post ID.
	 * @param  string $email      The (already-verified) email the visitor used to access the page, if any — carried forward into the reply/solve forms for guests.
	 * @return void
	 */
	private function render( $report_id, $email ) {
		$post       = get_post( $report_id );
		$ticket_id  = PSBDX_SRM_Helpers::get_ticket_id( $report_id );
		$status     = get_post_meta( $report_id, '_psbdx_report_status', true ) ?: 'Processing';
		$statuses   = PSBDX_SRM_Helpers::get_statuses();
		$s_label    = isset( $statuses[ $status ] ) ? $statuses[ $status ]['label'] : $status;
		$s_style    = PSBDX_SRM_Helpers::get_status_inline_style( $status );
		$category   = get_post_meta( $report_id, '_psbdx_report_category', true );
		$priority   = get_post_meta( $report_id, '_psbdx_report_priority', true ) ?: 'Medium';
		$priorities = PSBDX_SRM_Helpers::get_report_priorities();
		$p_label    = isset( $priorities[ $priority ] ) ? $priorities[ $priority ] : $priority;
		$src_title  = get_post_meta( $report_id, '_psbdx_source_title', true );
		$src_url    = get_post_meta( $report_id, '_psbdx_source_url',   true );
		$can_reply  = PSBDX_SRM_Replies::replies_allowed( $report_id );

		get_header();

		// Defensive fallback: this plugin's CSS/JS are normally loaded via
		// the standard wp_enqueue_scripts hook, which core only fires when
		// the active theme calls wp_head() in header.php. Some minimal or
		// custom themes skip that call, which would otherwise leave this
		// entire page unstyled and non-interactive with no visible error.
		// If that's the case here, load them directly instead.
		if ( ! wp_style_is( 'psbdx-srm-public', 'done' ) && ! wp_style_is( 'psbdx-srm-public', 'enqueued' ) ) {
			PSBDX_SRM_Assets::print_style_tag( 'dashicons', includes_url( 'css/dashicons.min.css' ) );
			PSBDX_SRM_Assets::print_style_tag( 'psbdx-srm-public', PSBDX_SRM_URL . 'assets/css/public.css?ver=' . psbdx_srm_asset_ver( 'assets/css/public.css' ) );
		}
		?>
		<div class="psbdx-report-page">

			<div class="psbdx-report-hero">
				<div class="psbdx-report-hero-top">
					<span class="psbdx-report-hero-eyebrow"><?php esc_html_e( 'Report', 'psbdx-smart-report-management' ); ?></span>
					<span class="psbdx-status-chip psbdx-status-chip-lg" id="psbdx-report-page-status" style="<?php echo esc_attr( trim( $s_style ) ); ?>"><?php echo esc_html( $s_label ); ?></span>
				</div>
				<code class="psbdx-report-hero-ticket"><?php echo esc_html( $ticket_id ); ?></code>
				<p class="psbdx-report-hero-date"><?php echo esc_html( get_the_date( '', $post ) ); ?></p>
			</div>

			<div class="psbdx-report-page-card">
				<div class="psbdx-report-page-grid">
					<div class="psbdx-report-page-field">
						<span class="psbdx-report-page-label">
							<span class="dashicons dashicons-category" aria-hidden="true"></span>
							<?php esc_html_e( 'Category', 'psbdx-smart-report-management' ); ?>
						</span>
						<span class="psbdx-report-page-value"><?php echo esc_html( $category ?: __( 'Uncategorized', 'psbdx-smart-report-management' ) ); ?></span>
					</div>
					<div class="psbdx-report-page-field">
						<span class="psbdx-report-page-label">
							<span class="dashicons dashicons-flag" aria-hidden="true"></span>
							<?php esc_html_e( 'Priority', 'psbdx-smart-report-management' ); ?>
						</span>
						<span class="psbdx-report-page-value psbdx-priority-<?php echo esc_attr( strtolower( $priority ) ); ?>"><?php echo esc_html( $p_label ); ?></span>
					</div>
					<?php if ( $src_title ) : ?>
					<div class="psbdx-report-page-field psbdx-report-page-field-wide">
						<span class="psbdx-report-page-label">
							<span class="dashicons dashicons-admin-links" aria-hidden="true"></span>
							<?php esc_html_e( 'Item', 'psbdx-smart-report-management' ); ?>
						</span>
						<?php if ( $src_url ) : ?>
							<a class="psbdx-report-page-value" href="<?php echo esc_url( $src_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $src_title ); ?> ↗</a>
						<?php else : ?>
							<span class="psbdx-report-page-value"><?php echo esc_html( $src_title ); ?></span>
						<?php endif; ?>
					</div>
					<?php endif; ?>
				</div>

				<div class="psbdx-report-page-details">
					<span class="psbdx-report-page-label">
						<span class="dashicons dashicons-text-page" aria-hidden="true"></span>
						<?php esc_html_e( 'Details', 'psbdx-smart-report-management' ); ?>
					</span>
					<div class="psbdx-report-page-content"><?php echo wp_kses_post( wpautop( $post->post_content ) ); ?></div>
				</div>
			</div>

			<?php if ( $can_reply ) : ?>
			<div class="psbdx-report-page-card">
				<h2 class="psbdx-report-page-heading">
					<span class="dashicons dashicons-format-chat" aria-hidden="true"></span>
					<?php esc_html_e( 'Conversation', 'psbdx-smart-report-management' ); ?>
				</h2>
				<?php echo PSBDX_SRM_Shortcodes::render_thread_html( $report_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped internally per-field. ?>
				<form class="psbdx-thread-reply-form" data-report-id="<?php echo (int) $report_id; ?>" data-email="<?php echo esc_attr( $email ); ?>">
					<textarea class="psbdx-thread-reply-input" rows="3" placeholder="<?php esc_attr_e( 'Write a reply…', 'psbdx-smart-report-management' ); ?>"></textarea>
					<div class="psbdx-thread-reply-attach-row">
						<label class="psbdx-thread-reply-attach-btn">
							<span class="dashicons dashicons-paperclip" aria-hidden="true"></span>
							<?php esc_html_e( 'Attach a file', 'psbdx-smart-report-management' ); ?>
							<input type="file" class="psbdx-thread-reply-file" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf" hidden>
						</label>
						<span class="psbdx-thread-reply-file-name"></span>
					</div>
					<div class="psbdx-thread-reply-footer">
						<span class="psbdx-thread-reply-status" role="status" aria-live="polite"></span>
						<button type="submit" class="psbdx-thread-reply-send">
							<span class="psbdx-thread-reply-send-label"><?php esc_html_e( 'Send Reply', 'psbdx-smart-report-management' ); ?></span>
							<span class="psbdx-thread-reply-send-spinner" aria-hidden="true"></span>
						</button>
					</div>
				</form>
			</div>
			<?php endif; ?>
		</div>
		<?php
		get_footer();

		// Same fallback reasoning as the stylesheet above, for the script
		// this page's buttons and reply form depend on entirely. Checked
		// only now, *after* get_footer() has had its chance to print it via
		// wp_footer() — checking any earlier would wrongly assume a theme
		// with a working header but a footer.php that never calls
		// wp_footer() will still print it later, when it never does.
		// public.js guards itself against running twice, so it's always
		// safe to fall back here even in the rare case both copies load.
		if ( ! wp_script_is( 'psbdx-srm-public', 'done' ) ) {
			echo '<script id="psbdx-srm-public-js-extra">window.psbdxSrm = ' . wp_json_encode( PSBDX_SRM_Assets::get_localized_data() ) . ';</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode() output is safe JS; no user input here.
			PSBDX_SRM_Assets::print_script_tag( 'psbdx-srm-public', PSBDX_SRM_URL . 'assets/js/public.js?ver=' . psbdx_srm_asset_ver( 'assets/js/public.js' ) );
		}
	}
}
