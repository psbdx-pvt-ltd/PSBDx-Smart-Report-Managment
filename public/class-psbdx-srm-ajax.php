<?php
/**
 * AJAX handler for PSBDx Smart Report Management.
 *
 * @package PSBDx_Smart_Report_Management
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PSBDX_SRM_Ajax
 *
 * Handles the AJAX submission of report forms from the frontend.
 * Both logged-in and logged-out (nopriv) submissions are accepted;
 * rate limiting is applied only to logged-in users.
 *
 * @since 1.0.0
 */
class PSBDX_SRM_Ajax {

	/**
	 * AJAX action name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ACTION = 'psbdx_srm_submit';

	/**
	 * Nonce action name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const NONCE_ACTION = 'psbdx_srm_submit_nonce';

	/**
	 * AJAX action name for a reporter posting a reply to their own report.
	 *
	 * @since 1.4.2
	 * @var string
	 */
	const REPLY_ACTION = 'psbdx_srm_submit_reply';

	/**
	 * Nonce action name for reporter replies.
	 *
	 * @since 1.4.2
	 * @var string
	 */
	const REPLY_NONCE_ACTION = 'psbdx_srm_submit_reply_nonce';

	/**
	 * AJAX action name for polling a report's thread for new messages
	 * while the page is open, so a reply from the other side shows up
	 * without needing a manual reload.
	 *
	 * @since 1.4.2
	 * @var string
	 */
	const POLL_ACTION = 'psbdx_srm_poll_thread';

	/**
	 * Nonce action name for polling a thread.
	 *
	 * @since 1.4.2
	 * @var string
	 */
	const POLL_NONCE_ACTION = 'psbdx_srm_poll_thread_nonce';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'wp_ajax_' . self::ACTION,        array( $this, 'handle' ) );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, array( $this, 'handle' ) );

		// Replies are open to guests too: access is verified per-request
		// against report ownership (logged-in) or a matching reporter email
		// (guest) — see PSBDX_SRM_Replies::can_access_report().
		add_action( 'wp_ajax_' . self::REPLY_ACTION,        array( $this, 'handle_reply' ) );
		add_action( 'wp_ajax_nopriv_' . self::REPLY_ACTION, array( $this, 'handle_reply' ) );
		add_action( 'wp_ajax_' . self::POLL_ACTION,        array( $this, 'handle_poll_thread' ) );
		add_action( 'wp_ajax_nopriv_' . self::POLL_ACTION, array( $this, 'handle_poll_thread' ) );

		add_action( self::DEFERRED_HOOK, array( $this, 'process_deferred' ), 10, 2 );
	}

	/**
	 * Hook used to run deferred, slow work (AI classification, AI
	 * auto-reply, email notifications) on a separate request via WP-Cron,
	 * for hosts where fastcgi_finish_request() isn't available and the
	 * visitor's own request would otherwise have to sit and wait for it.
	 *
	 * @since 1.4.4
	 * @var string
	 */
	const DEFERRED_HOOK = 'psbdx_srm_process_deferred';

	/**
	 * Sends a JSON success response to the browser immediately and, where
	 * the server supports it (PHP-FPM), closes out the HTTP connection —
	 * so the caller can keep running slow, synchronous work afterward
	 * (AI classification, AI auto-reply, email notifications) without any
	 * chance of it delaying the visitor's response or — on a host with a
	 * tight `max_execution_time` — fataling the request they're watching.
	 *
	 * Unlike wp_send_json_success(), this does NOT terminate execution:
	 * the caller is expected to do its follow-up work (if this returned
	 * true) or defer it (if it returned false), then call exit() itself.
	 *
	 * @since 1.4.3
	 * @param  array $data  Response payload (same shape passed to wp_send_json_success()).
	 * @return bool  True if the HTTP connection was actually closed out
	 *               already (fastcgi_finish_request() ran) — safe to keep
	 *               doing slow work inline below. False if the connection
	 *               is still open — the caller should hand any slow work
	 *               off to defer_work() instead of running it here, or the
	 *               visitor's browser will keep waiting on it regardless.
	 */
	private function respond_then_continue( array $data ) {
		if ( ! headers_sent() ) {
			header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		}

		echo wp_json_encode( array( 'success' => true, 'data' => $data ) );

		if ( function_exists( 'fastcgi_finish_request' ) ) {
			// Best case (PHP-FPM, most modern hosts): the browser gets its
			// response right now, and everything below this call runs
			// server-side with nobody waiting on it.
			fastcgi_finish_request();
			$finished = true;
		} else {
			// No FPM available — flush what we can, but the connection to
			// the browser genuinely stays open until this request ends, so
			// anything slow still needs to be run out-of-band (see
			// defer_work()) rather than inline here.
			while ( ob_get_level() > 0 ) {
				ob_end_flush();
			}
			flush();
			$finished = false;
		}

		// A slow or briefly-degraded AI provider (or a slow page fetch for
		// reply-context) should never be able to fatal this request with a
		// "Maximum execution time exceeded" error. @-suppressed because
		// set_time_limit() is disabled outright on some hosts, which is
		// fine — it's a best-effort safety net, not a requirement.
		@set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_time_limit

		return $finished;
	}

	/**
	 * Hands slow, non-essential work off to a one-off WP-Cron event on its
	 * own separate request, instead of running it inline and keeping the
	 * visitor's connection open until it finishes. Used as the fallback
	 * for hosts where respond_then_continue() couldn't already close the
	 * connection out via fastcgi_finish_request().
	 *
	 * spawn_cron() nudges that event to run right away via a non-blocking
	 * loopback request, rather than waiting for the next site visit to
	 * trigger WP-Cron; if the loopback can't fire for some reason (some
	 * locked-down hosts), the event still runs on the next normal page
	 * load — later than ideal, but never blocking, and never lost.
	 *
	 * @since 1.4.4
	 * @param  string $type  'submission' or 'reply' — see process_deferred().
	 * @param  int    $id    Report log post ID.
	 * @return void
	 */
	private function defer_work( $type, $id ) {
		wp_schedule_single_event( time(), self::DEFERRED_HOOK, array( $type, (int) $id ) );

		if ( function_exists( 'spawn_cron' ) ) {
			spawn_cron();
		}
	}

	/**
	 * WP-Cron callback for defer_work() — runs on its own request, well
	 * away from whichever visitor originally triggered it.
	 *
	 * @since 1.4.4
	 * @param  string $type  'submission' or 'reply'.
	 * @param  int    $id    Report log post ID.
	 * @return void
	 */
	public function process_deferred( $type, $id ) {
		$id = (int) $id;

		if ( 'submission' === $type ) {
			do_action( 'psbdx_srm_report_submitted', $id );
		} elseif ( 'reply' === $type ) {
			PSBDX_SRM_AI::generate_reply( $id );
		}
	}

	/**
	 * Handle a background poll for a report's thread — lets the report
	 * detail page (and the admin Conversation box) pick up a reply from
	 * the other side while the page is still open, without a manual reload.
	 * Read-only: just returns the current thread and its message count.
	 *
	 * @since  1.4.2
	 * @return void  Terminates with wp_send_json_success() or wp_send_json_error().
	 */
	public function handle_poll_thread() {
		if ( ! check_ajax_referer( self::POLL_NONCE_ACTION, 'security', false ) ) {
			wp_send_json_error( __( 'Your session has expired. Please refresh the page.', 'psbdx-smart-report-management' ) );
		}

		$report_id = absint( $_POST['report_id'] ?? 0 );
		$email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( ! $report_id || 'psbdx_report_log' !== get_post_type( $report_id ) ) {
			wp_send_json_error( __( 'Invalid report.', 'psbdx-smart-report-management' ) );
		}

		if ( ! PSBDX_SRM_Replies::can_access_report( $report_id, $email ) ) {
			wp_send_json_error( __( 'Not allowed.', 'psbdx-smart-report-management' ) );
		}

		$is_admin_view = current_user_can( 'edit_posts' );

		wp_send_json_success( array(
			'count'              => count( PSBDX_SRM_Replies::get_thread( $report_id ) ),
			'thread_html'        => PSBDX_SRM_Shortcodes::render_thread_html( $report_id, true, $is_admin_view ),
			'thread_html_inner'  => PSBDX_SRM_Shortcodes::render_thread_html( $report_id, false, $is_admin_view ),
		) );
	}

	/**
	 * Handle a reporter (or admin) posting a reply from the frontend report
	 * detail page. Accepts either a logged-in report owner, an admin, or a
	 * guest who supplies the email address the report was filed with.
	 *
	 * @since  1.4.2
	 * @return void  Terminates with wp_send_json_success() or wp_send_json_error().
	 */
	public function handle_reply() {
		if ( ! check_ajax_referer( self::REPLY_NONCE_ACTION, 'security', false ) ) {
			wp_send_json_error( __( 'Your session has expired. Please refresh the page and try again.', 'psbdx-smart-report-management' ) );
		}

		$report_id = absint( $_POST['report_id'] ?? 0 );
		$message   = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
		$email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( ! $report_id || 'psbdx_report_log' !== get_post_type( $report_id ) ) {
			wp_send_json_error( __( 'Invalid report.', 'psbdx-smart-report-management' ) );
		}

		if ( ! PSBDX_SRM_Replies::can_access_report( $report_id, $email ) ) {
			wp_send_json_error( __( 'You can only reply to your own reports.', 'psbdx-smart-report-management' ) );
		}

		if ( ! PSBDX_SRM_Replies::replies_allowed( $report_id ) ) {
			wp_send_json_error( __( 'Replies are not enabled for this report.', 'psbdx-smart-report-management' ) );
		}

		if ( '' === trim( $message ) ) {
			wp_send_json_error( __( 'Please write a message before sending.', 'psbdx-smart-report-management' ) );
		}

		if ( current_user_can( 'edit_posts' ) ) {
			$user        = wp_get_current_user();
			$author_type = 'admin';
			$author_id   = $user->ID;
			$author_name = $user->display_name;
		} elseif ( is_user_logged_in() ) {
			$user        = wp_get_current_user();
			$author_type = 'user';
			$author_id   = $user->ID;
			$author_name = $user->display_name;
		} else {
			$author_type = 'user';
			$author_id   = 0;
			$author_name = get_post_meta( $report_id, '_psbdx_reporter_email', true ) ?: __( 'Customer', 'psbdx-smart-report-management' );
		}

		$reply_id = PSBDX_SRM_Replies::add_reply( $report_id, $author_type, $author_id, $author_name, $message );

		if ( ! $reply_id ) {
			wp_send_json_error( __( 'Failed to save your reply. Please try again.', 'psbdx-smart-report-management' ) );
		}

		$thread_html = PSBDX_SRM_Shortcodes::render_thread_html( $report_id, true );

		// Give the AI a chance to respond automatically to the follow-up,
		// same as it does for the initial report — gated the same way, and
		// only when it wasn't the admin themselves who just replied. This
		// runs AFTER the response below, so a slow AI provider can't turn
		// posting a reply into a fatal timeout for the person replying;
		// the existing thread-polling mechanism picks up the AI's reply a
		// moment later, the same way it already picks up a reply from
		// whichever side didn't just send this one.
		$should_auto_reply = ( 'admin' !== $author_type && PSBDX_SRM_Replies::ai_reply_allowed( $report_id ) );

		$finished = $this->respond_then_continue(
			array(
				'thread_html' => $thread_html,
				'message'     => __( 'Your reply has been sent.', 'psbdx-smart-report-management' ),
			)
		);

		if ( $should_auto_reply ) {
			if ( $finished ) {
				PSBDX_SRM_AI::generate_reply( $report_id );
			} else {
				$this->defer_work( 'reply', $report_id );
			}
		}

		exit;
	}

	/**
	 * Handle the AJAX report submission.
	 *
	 * @since  1.0.0
	 * @return void  Terminates with wp_send_json_success() or wp_send_json_error().
	 */
	public function handle() {
		// 1. Verify nonce.
		check_ajax_referer( self::NONCE_ACTION, 'security' );

		// 2. Collect and sanitize user input.
		$form_id      = absint( $_POST['form_id']      ?? 0 );
		$source_url   = esc_url_raw( wp_unslash( $_POST['source_url']   ?? '' ) );
		$source_title = sanitize_text_field( wp_unslash( $_POST['source_title'] ?? '' ) );
		$woo_order_id = absint( $_POST['woo_order_id'] ?? 0 );

		// Detect v2 vs v1 form.
		$form_version = (int) get_post_meta( $form_id, PSBDX_SRM_Form_Builder::VERSION_META_KEY, true );
		$is_v2        = ( $form_version >= PSBDX_SRM_Form_Builder::SCHEMA_VERSION );

		// V1-specific fields.
		$contact       = '';
		$reason        = '';
		$custom_reason = '';
		$details       = '';

		if ( ! $is_v2 ) {
			$contact       = sanitize_text_field( wp_unslash( $_POST['contact_value']  ?? '' ) );
			$reason        = sanitize_text_field( wp_unslash( $_POST['report_reason']  ?? '' ) );
			$custom_reason = sanitize_text_field( wp_unslash( $_POST['custom_reason']  ?? '' ) );
			$details       = sanitize_textarea_field( wp_unslash( $_POST['report_details'] ?? '' ) );
		}

		// 3. Basic required-field validation (v1 only; v2 validated per-schema below).
		if ( ! $form_id ) {
			wp_send_json_error(
				__( 'Please fill in all required fields.', 'psbdx-smart-report-management' )
			);
		}
		if ( ! $is_v2 && ( empty( $reason ) || empty( $details ) ) ) {
			wp_send_json_error(
				__( 'Please fill in all required fields.', 'psbdx-smart-report-management' )
			);
		}

		// 4. Validate form exists.
		if ( 'psbdx_report_form' !== get_post_type( $form_id ) ) {
			wp_send_json_error(
				__( 'Invalid form.', 'psbdx-smart-report-management' )
			);
		}

		// 4b. Captcha verification (if enabled on this form).
		$captcha_on_form = ( 'yes' === get_post_meta( $form_id, '_psbdx_captcha_enabled', true ) );
		$provider        = PSBDX_SRM_Captcha::active_provider();

		if ( $captcha_on_form && '' !== $provider ) {
			$response_field = PSBDX_SRM_Captcha::response_field( $provider );
			$token          = isset( $_POST[ $response_field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $response_field ] ) ) : '';

			if ( ! PSBDX_SRM_Captcha::verify( $provider, $token ) ) {
				wp_send_json_error(
					__( 'Captcha verification failed. Please try again.', 'psbdx-smart-report-management' )
				);
			}
		}

		// 5. Validate required contact field.
		$contact_label = get_post_meta( $form_id, '_psbdx_contact_label',    true ) ?: __( 'WhatsApp Number', 'psbdx-smart-report-management' );
		$contact_req   = ( 'yes' === get_post_meta( $form_id, '_psbdx_contact_required', true ) );

		if ( $contact_req && empty( $contact ) ) {
			wp_send_json_error(
				sprintf(
					/* translators: %s: field label */
					__( 'The "%s" field is required.', 'psbdx-smart-report-management' ),
					$contact_label
				)
			);
		}

		// 6. Rate limiting — logged-in users only.
		$user         = wp_get_current_user();
		$is_logged_in = ( $user->ID > 0 );

		$cooldown_mins = PSBDX_SRM_Helpers::get_effective_cooldown_mins( $form_id );

		if ( $cooldown_mins > 0 && $is_logged_in ) {
			$transient_key = 'psbdx_cd_' . $user->ID . '_' . $form_id;

			if ( false !== get_transient( $transient_key ) ) {
				wp_send_json_error(
					__( 'You submitted a report too recently. Please wait before trying again.', 'psbdx-smart-report-management' )
				);
			}
		}

		// 7. Resolve identity server-side (never from POST data).
		$reporter_name  = $is_logged_in ? $user->display_name : __( 'Guest', 'psbdx-smart-report-management' );
		$reporter_email = $is_logged_in ? $user->user_email   : '';

		// 8. Build post title and content.
		$content_parts = array();
		$post_title    = '';

		if ( $reporter_email ) {
			$content_parts[] = '<strong>' . esc_html__( 'Email', 'psbdx-smart-report-management' ) . ':</strong> ' . esc_html( $reporter_email );
		}

		if ( $is_v2 ) {
			// ── V2: read and validate psrm_v2 fields against the stored schema ──
			$schema      = json_decode( get_post_meta( $form_id, PSBDX_SRM_Form_Builder::FIELDS_META_KEY, true ), true );
			$schema      = is_array( $schema ) ? $schema : array();
			$v2_parts    = array();
			$post_title  = $reporter_name;

			// Unslash the whole psrm_v2 array first; sanitize per-field below.
			$raw_v2 = isset( $_POST['psrm_v2'] ) && is_array( $_POST['psrm_v2'] )
				? wp_unslash( (array) $_POST['psrm_v2'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized field-by-field below.
				: array();

			foreach ( $schema as $field_def ) {
				$handle   = sanitize_key( $field_def['handle'] ?? '' );
				$label    = sanitize_text_field( $field_def['label'] ?? '' );
				$required = ! empty( $field_def['required'] );
				$type     = sanitize_key( $field_def['type'] ?? '' );

				if ( '' === $handle || 'captcha' === $type ) {
					continue;
				}

				$value = '';

				if ( 'name' === $type ) {
					$first = sanitize_text_field( $raw_v2[ $handle . '_first' ] ?? '' );
					$last  = sanitize_text_field( $raw_v2[ $handle . '_last' ]  ?? '' );
					$value = trim( $first . ' ' . $last );
					if ( $required && '' === trim( $first ) ) {
						wp_send_json_error(
							/* translators: %s: field label */
							sprintf( __( 'The "%s" first name field is required.', 'psbdx-smart-report-management' ), $label )
						);
					}
				} elseif ( 'checkbox' === $type ) {
					$raw_arr  = isset( $raw_v2[ $handle ] ) && is_array( $raw_v2[ $handle ] )
						? array_map( 'sanitize_text_field', $raw_v2[ $handle ] )
						: array();
					$other_v  = sanitize_text_field( $raw_v2[ $handle . '_other' ] ?? '' );
					if ( ( $key = array_search( '__other__', $raw_arr, true ) ) !== false ) {
						unset( $raw_arr[ $key ] );
						if ( '' !== $other_v ) {
							$raw_arr[] = esc_html__( 'Other', 'psbdx-smart-report-management' ) . ': ' . $other_v;
						}
					}
					$value = implode( ', ', $raw_arr );
					if ( $required && empty( $raw_arr ) ) {
						wp_send_json_error(
							sprintf(
								/* translators: %s: form field label, e.g. "Reason" */
								__( 'The "%s" field is required.', 'psbdx-smart-report-management' ),
								$label
							)
						);
					}
				} else {
					$raw_val = sanitize_text_field( $raw_v2[ $handle ] ?? '' );
					// Handle "Other" for select and radio.
					if ( '__other__' === $raw_val ) {
						$other_v = sanitize_text_field( $raw_v2[ $handle . '_other' ] ?? '' );
						$raw_val = '' !== $other_v
							? esc_html__( 'Other', 'psbdx-smart-report-management' ) . ': ' . $other_v
							: esc_html__( 'Other', 'psbdx-smart-report-management' );
					}
					$value = 'paragraph' === $type
						? sanitize_textarea_field( $raw_v2[ $handle ] ?? '' )
						: $raw_val;
					if ( $required && '' === trim( $value ) ) {
						wp_send_json_error(
							sprintf(
								/* translators: %s: form field label, e.g. "Email Address" */
								__( 'The "%s" field is required.', 'psbdx-smart-report-management' ),
								$label
							)
						);
					}
				}

				if ( '' !== trim( $value ) ) {
					$v2_parts[] = '<strong>' . esc_html( $label ) . ':</strong> '
						. ( 'paragraph' === $type ? '<br>' . nl2br( esc_html( $value ) ) : esc_html( $value ) );
				}
			}

			if ( $v2_parts ) {
				$content_parts[] = '<hr>' . implode( '<br>', $v2_parts );
			}

		} else {
			// ── V1 legacy content building ──────────────────────────────────────

			// Build final reason string (validate "Other" requires a specification).
			$other_label = __( 'Other', 'psbdx-smart-report-management' );
			if ( $other_label === $reason ) {
				if ( '' === trim( $custom_reason ) ) {
					wp_send_json_error(
						__( 'Please specify your reason when selecting "Other".', 'psbdx-smart-report-management' )
					);
				}
				$reason = sprintf(
					/* translators: %s: custom reason text */
					__( 'Other: %s', 'psbdx-smart-report-management' ),
					$custom_reason
				);
			}

			$contact_label = get_post_meta( $form_id, '_psbdx_contact_label', true ) ?: __( 'WhatsApp Number', 'psbdx-smart-report-management' );

			if ( $contact ) {
				$content_parts[] = '<strong>' . esc_html( $contact_label ) . ':</strong> ' . esc_html( $contact );
			}

			$content_parts[] = '<strong>' . esc_html__( 'Details', 'psbdx-smart-report-management' ) . ':</strong><br>' . nl2br( esc_html( $details ) );

			// Extra custom fields (only labels configured on this form).
			if ( isset( $_POST['psbdx_custom'] ) && is_array( $_POST['psbdx_custom'] ) ) {
				$extra_parts    = array();
				$allowed_labels = array_flip( PSBDX_SRM_Helpers::get_custom_fields( $form_id ) );
				$raw_custom     = wp_unslash( (array) $_POST['psbdx_custom'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized on the next line.
				$posted_custom  = array_map( 'sanitize_text_field', $raw_custom );

				foreach ( $posted_custom as $lbl => $val ) {
					$lbl = sanitize_text_field( $lbl );
					if ( '' === $lbl || ! isset( $allowed_labels[ $lbl ] ) ) {
						continue;
					}
					if ( is_array( $val ) ) {
						continue;
					}
					$extra_parts[] = '<strong>' . esc_html( $lbl ) . ':</strong> ' . esc_html( sanitize_text_field( $val ) );
				}

				if ( $extra_parts ) {
					$content_parts[] = '<hr><strong>' . esc_html__( 'Extra Info', 'psbdx-smart-report-management' ) . ':</strong><br>' . implode( '<br>', $extra_parts );
				}
			}

			$post_title = sanitize_text_field( $reporter_name . ' | ' . $reason );
		}

		// 9. Finalise post content.

		$post_content = implode( '<br>', $content_parts );

		// 10. Resolve WooCommerce order ID (with security validation).
		$validated_order_id = PSBDX_SRM_Helpers::resolve_woo_order_id(
			$woo_order_id,
			$source_url,
			$is_logged_in ? $user->ID : 0
		);

		// 11. Insert the report log post.
		$log_id = wp_insert_post( array(
			'post_type'    => 'psbdx_report_log',
			'post_title'   => $post_title,
			'post_content' => wp_kses_post( $post_content ),
			'post_status'  => 'publish',
			'post_author'  => $is_logged_in ? $user->ID : 0,
		), true );

		if ( is_wp_error( $log_id ) ) {
			wp_send_json_error(
				__( 'Failed to save the report. Please try again.', 'psbdx-smart-report-management' )
			);
		}

		// Rate limit only after a successful save (avoids locking users out on failures).
		if ( $cooldown_mins > 0 && $is_logged_in ) {
			set_transient( 'psbdx_cd_' . $user->ID . '_' . $form_id, time(), $cooldown_mins * MINUTE_IN_SECONDS );
		}

		// 12. Save report meta.
		$ticket_id = PSBDX_SRM_Helpers::generate_ticket_id();
		update_post_meta( $log_id, PSBDX_SRM_Helpers::TICKET_ID_META, $ticket_id );
		update_post_meta( $log_id, '_psbdx_source_url',     $source_url );
		update_post_meta( $log_id, '_psbdx_source_title',   $source_title );
		update_post_meta( $log_id, '_psbdx_reporter_email', $reporter_email );
		update_post_meta( $log_id, PSBDX_SRM_Replies::SOURCE_FORM_META, $form_id );
		PSBDX_SRM_Helpers::update_report_status( $log_id, 'Processing', array( 'source' => 'submission' ) );

		if ( $validated_order_id ) {
			update_post_meta( $log_id, '_psbdx_woo_order_id', $validated_order_id );
		}

		/**
		 * Fires after a report log has been created and its core meta saved.
		 *
		 * PSBDX_SRM_AI hooks in here to (optionally) suggest a category and
		 * priority, and to generate an automatic reply. Both can involve a
		 * slow, blocking call to an external AI provider (and, for the
		 * auto-reply, an extra page fetch first) — so the success response
		 * below is sent to the browser first. Where the host supports
		 * fastcgi_finish_request() this fires immediately afterward, in the
		 * same request, with the connection already closed out. Where it
		 * doesn't, running it here would still leave the visitor's browser
		 * waiting on the AI calls to finish — so instead it's handed off to
		 * a separate WP-Cron request via defer_work(), which is what
		 * actually decouples report submission from AI/email latency on
		 * every host, not just PHP-FPM ones.
		 *
		 * @since 1.4.1
		 * @param int $log_id Newly created report log post ID.
		 */
		$finished = $this->respond_then_continue(
			array(
				'message'   => __( 'Your report has been submitted successfully. Thank you!', 'psbdx-smart-report-management' ),
				'ticket_id' => $ticket_id,
			)
		);

		if ( $finished ) {
			do_action( 'psbdx_srm_report_submitted', $log_id );
		} else {
			$this->defer_work( 'submission', $log_id );
		}

		exit;
	}
}
