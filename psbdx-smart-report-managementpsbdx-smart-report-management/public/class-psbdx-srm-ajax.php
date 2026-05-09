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
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'wp_ajax_' . self::ACTION,        array( $this, 'handle' ) );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, array( $this, 'handle' ) );
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
		$contact      = sanitize_text_field( wp_unslash( $_POST['contact_value']  ?? '' ) );
		$reason       = sanitize_text_field( wp_unslash( $_POST['report_reason']  ?? '' ) );
		$custom_reason= sanitize_text_field( wp_unslash( $_POST['custom_reason']  ?? '' ) );
		$details      = sanitize_textarea_field( wp_unslash( $_POST['report_details'] ?? '' ) );
		$source_url   = esc_url_raw( wp_unslash( $_POST['source_url']   ?? '' ) );
		$source_title = sanitize_text_field( wp_unslash( $_POST['source_title'] ?? '' ) );
		$woo_order_id = absint( $_POST['woo_order_id'] ?? 0 );

		// 3. Basic required-field validation.
		if ( ! $form_id || empty( $reason ) || empty( $details ) ) {
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

		// 8. Build final reason string (validate "Other" requires a specification).
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

		// 9. Build the log post content.
		$content_parts = array();

		if ( $reporter_email ) {
			$content_parts[] = '<strong>' . esc_html__( 'Email', 'psbdx-smart-report-management' ) . ':</strong> ' . esc_html( $reporter_email );
		}

		if ( $contact ) {
			$content_parts[] = '<strong>' . esc_html( $contact_label ) . ':</strong> ' . esc_html( $contact );
		}

		$content_parts[] = '<strong>' . esc_html__( 'Details', 'psbdx-smart-report-management' ) . ':</strong><br>' . nl2br( esc_html( $details ) );

		// Extra custom fields (only labels configured on this form).
		if ( isset( $_POST['psbdx_custom'] ) && is_array( $_POST['psbdx_custom'] ) ) {
			$extra_parts    = array();
			$allowed_labels = array_flip( PSBDX_SRM_Helpers::get_custom_fields( $form_id ) );
			$posted_custom  = wp_unslash( $_POST['psbdx_custom'] );

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
			'post_title'   => sanitize_text_field( $reporter_name . ' | ' . $reason ),
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
		update_post_meta( $log_id, '_psbdx_source_url',     $source_url );
		update_post_meta( $log_id, '_psbdx_source_title',   $source_title );
		update_post_meta( $log_id, '_psbdx_report_status',  'Processing' );
		update_post_meta( $log_id, '_psbdx_reporter_email', $reporter_email );

		if ( $validated_order_id ) {
			update_post_meta( $log_id, '_psbdx_woo_order_id', $validated_order_id );
		}

		wp_send_json_success(
			__( 'Your report has been submitted successfully. Thank you!', 'psbdx-smart-report-management' )
		);
	}
}
