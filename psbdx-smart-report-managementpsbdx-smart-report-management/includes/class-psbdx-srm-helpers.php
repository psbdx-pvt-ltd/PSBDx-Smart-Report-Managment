<?php
/**
 * Shared helper functions for PSBDx Smart Report Management.
 *
 * @package PSBDx_Smart_Report_Management
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PSBDX_SRM_Helpers
 *
 * Static utility methods used across admin and public components.
 *
 * @since 1.0.0
 */
class PSBDX_SRM_Helpers {

	/**
	 * Option key for admin-defined custom report statuses.
	 *
	 * @since 1.2.0
	 * @var string
	 */
	const CUSTOM_STATUSES_OPTION = 'psbdx_srm_custom_statuses';
	const GLOBAL_RATE_LIMIT_OPTION = 'psbdx_srm_global_rate_limit_mins';

	/**
	 * Built-in status keys (not removable via settings UI).
	 *
	 * @since 1.2.0
	 * @return string[]
	 */
	public static function get_builtin_status_keys() {
		return array(
			'Processing',
			'Contacting user',
			'Waiting for user response',
			'Solved',
			'Failed to Contact user',
		);
	}

	/**
	 * Default report statuses (bundled with the plugin).
	 *
	 * @since 1.2.0
	 * @return array<string, array{label: string, bg: string, color: string}>
	 */
	public static function get_default_statuses() {
		return array(
			'Processing'                => array(
				'label' => __( 'Processing', 'psbdx-smart-report-management' ),
				'bg'    => '#e2e8f0',
				'color' => '#475569',
			),
			'Contacting user'           => array(
				'label' => __( 'Contacting User', 'psbdx-smart-report-management' ),
				'bg'    => '#f3e8ff',
				'color' => '#6b21a8',
			),
			'Waiting for user response' => array(
				'label' => __( 'Waiting Response', 'psbdx-smart-report-management' ),
				'bg'    => '#ffedd5',
				'color' => '#9a3412',
			),
			'Solved'                    => array(
				'label' => __( 'Solved', 'psbdx-smart-report-management' ),
				'bg'    => '#dcfce7',
				'color' => '#166534',
			),
			'Failed to Contact user'    => array(
				'label' => __( 'Failed to Contact', 'psbdx-smart-report-management' ),
				'bg'    => '#fee2e2',
				'color' => '#991b1b',
			),
		);
	}

	/**
	 * Raw custom statuses from the options table.
	 *
	 * @since 1.2.0
	 * @return array<string, array{label: string, bg: string, color: string}>
	 */
	public static function get_custom_statuses_stored() {
		$raw = get_option( self::CUSTOM_STATUSES_OPTION, array() );
		return is_array( $raw ) ? $raw : array();
	}

	/**
	 * Normalizes and validates stored custom statuses (keys, colours, labels).
	 *
	 * @since 1.2.0
	 * @param  array $map  Raw map status_key => data.
	 * @return array<string, array{label: string, bg: string, color: string}>
	 */
	public static function sanitize_custom_status_map( array $map ) {
		$defaults = self::get_default_statuses();
		$out      = array();

		foreach ( $map as $key => $row ) {
			$key = sanitize_key( (string) $key );

			if ( '' === $key || isset( $defaults[ $key ] ) ) {
				continue;
			}

			if ( 0 !== strpos( $key, 'psbdx_c_', 0 ) ) {
				continue;
			}

			if ( ! is_array( $row ) ) {
				continue;
			}

			$label = isset( $row['label'] ) ? sanitize_text_field( (string) $row['label'] ) : '';

			if ( '' === $label ) {
				continue;
			}

			$bg    = isset( $row['bg'] ) ? sanitize_hex_color( (string) $row['bg'] ) : '';
			$color = isset( $row['color'] ) ? sanitize_hex_color( (string) $row['color'] ) : '';

			if ( ! $bg ) {
				$bg = '#e2e8f0';
			}

			if ( ! $color ) {
				$color = '#475569';
			}

			$out[ $key ] = array(
				'label' => $label,
				'bg'    => $bg,
				'color' => $color,
			);
		}

		return $out;
	}

	/**
	 * Returns the full list of available report statuses with display labels
	 * and badge colours.
	 *
	 * @since 1.0.0
	 * @return array<string, array{label: string, bg: string, color: string}>
	 */
	public static function get_statuses() {
		return array_merge(
			self::get_default_statuses(),
			self::sanitize_custom_status_map( self::get_custom_statuses_stored() )
		);
	}

	/**
	 * Whether a status key exists (built-in or custom).
	 *
	 * @since 1.2.0
	 * @param  string $status  Status key.
	 * @return bool
	 */
	public static function is_valid_status_key( $status ) {
		$status = (string) $status;

		return '' !== $status && isset( self::get_statuses()[ $status ] );
	}

	/**
	 * Human-readable label for a stored status value.
	 *
	 * @since 1.2.0
	 * @param  string $status  Status key or legacy free-text value.
	 * @return string
	 */
	public static function get_status_label( $status ) {
		$all = self::get_statuses();

		if ( isset( $all[ $status ] ) ) {
			return $all[ $status ]['label'];
		}

		return $status ? (string) $status : __( 'Unknown', 'psbdx-smart-report-management' );
	}

	/**
	 * Returns the CSS class name for a given status key.
	 *
	 * @since  1.0.0
	 * @param  string $status  Status key (e.g. 'Solved').
	 * @return string          CSS class name.
	 */
	public static function get_status_class( $status ) {
		$map = array(
			'Processing'                => 'psbdx-status-processing',
			'Contacting user'           => 'psbdx-status-contacting',
			'Waiting for user response' => 'psbdx-status-waiting',
			'Solved'                    => 'psbdx-status-solved',
			'Failed to Contact user'    => 'psbdx-status-failed',
		);

		if ( isset( $map[ $status ] ) ) {
			return $map[ $status ];
		}

		return 0 === strpos( (string) $status, 'psbdx_c_', 0 )
			? 'psbdx-status-custom'
			: 'psbdx-status-unknown';
	}

	/**
	 * Inline styles for a status chip (used when custom colours must apply).
	 *
	 * @since 1.2.0
	 * @param  string $status  Status key.
	 * @return string          Safe style attribute value (no "style=" wrapper).
	 */
	public static function get_status_inline_style( $status ) {
		$all = self::get_statuses();

		if ( isset( $all[ $status ] ) ) {
			return sprintf(
				'background:%1$s;color:%2$s;',
				esc_attr( $all[ $status ]['bg'] ),
				esc_attr( $all[ $status ]['color'] )
			);
		}

		return 'background:#e2e8f0;color:#475569;';
	}

	/**
	 * Aggregated report counts per status in one database round-trip.
	 *
	 * Posts without a status meta row are counted as "Processing".
	 *
	 * @since 1.2.0
	 * @return array<string, int>
	 */
	public static function get_report_status_counts() {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT pm.meta_value AS st, COUNT(DISTINCT p.ID) AS cnt
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
			WHERE p.post_type = %s AND p.post_status = 'publish'
			GROUP BY pm.meta_value",
			'_psbdx_report_status',
			'psbdx_report_log'
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql built with prepare().
		$rows = $wpdb->get_results( $sql, ARRAY_A );

		$counts = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$key = isset( $row['st'] ) ? (string) $row['st'] : '';

				if ( '' === $key ) {
					$key = 'Processing';
				}

				$counts[ $key ] = isset( $counts[ $key ] ) ? $counts[ $key ] + (int) $row['cnt'] : (int) $row['cnt'];
			}
		}

		$counts_obj = wp_count_posts( 'psbdx_report_log' );
		$published  = isset( $counts_obj->publish ) ? (int) $counts_obj->publish : 0;

		$assigned = array_sum( $counts );

		if ( $published > $assigned ) {
			$counts['Processing'] = isset( $counts['Processing'] )
				? $counts['Processing'] + ( $published - $assigned )
				: ( $published - $assigned );
		}

		return $counts;
	}

	/**
	 * Global report cooldown (minutes) used when a form-level cooldown
	 * is not configured.
	 *
	 * @since 1.2.0
	 * @return int
	 */
	public static function get_global_rate_limit_mins() {
		$value = get_option( self::GLOBAL_RATE_LIMIT_OPTION, 30 );
		return min( 1440, max( 0, (int) $value ) );
	}

	/**
	 * Effective cooldown for a specific form.
	 *
	 * Form meta value overrides global settings. If form meta is missing/empty,
	 * the global rate limit is used.
	 *
	 * @since 1.2.0
	 * @param  int $form_id Report form post ID.
	 * @return int
	 */
	public static function get_effective_cooldown_mins( $form_id ) {
		$raw = get_post_meta( (int) $form_id, '_psbdx_cooldown_mins', true );

		if ( '' === $raw || null === $raw ) {
			return self::get_global_rate_limit_mins();
		}

		return min( 1440, max( 0, (int) $raw ) );
	}

	/**
	 * Retrieves the parsed list of report reasons for a given form.
	 *
	 * Always appends "Other" as the last option.
	 *
	 * @since  1.0.0
	 * @param  int $form_id  Post ID of the report form.
	 * @return string[]      Array of reason strings.
	 */
	public static function get_form_reasons( $form_id ) {
		$raw = get_post_meta( $form_id, '_psbdx_reasons', true );

		if ( ! empty( $raw ) ) {
			$reasons = array_map( 'trim', explode( ',', $raw ) );
			$reasons = array_filter( $reasons );
			$reasons = array_values( array_diff( $reasons, array( 'Other' ) ) );
		} else {
			$reasons = array(
				__( 'Product not Working',   'psbdx-smart-report-management' ),
				__( 'Order not Delivered',   'psbdx-smart-report-management' ),
				__( 'Want to Cancel',        'psbdx-smart-report-management' ),
			);
		}

		$reasons[] = __( 'Other', 'psbdx-smart-report-management' );

		return $reasons;
	}

	/**
	 * Retrieves the parsed list of extra custom fields for a given form.
	 *
	 * @since  1.0.0
	 * @param  int $form_id  Post ID of the report form.
	 * @return string[]      Array of field label strings.
	 */
	public static function get_custom_fields( $form_id ) {
		$raw = get_post_meta( $form_id, '_psbdx_custom_fields', true );

		if ( empty( $raw ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map( 'trim', explode( ',', $raw ) )
			)
		);
	}

	/**
	 * Validates and resolves a WooCommerce order ID from a submitted report,
	 * ensuring the order belongs to the currently logged-in user.
	 *
	 * @since  1.0.0
	 * @param  int    $order_id     Order ID passed from the form (may be 0).
	 * @param  string $source_url   Source URL, used as a fallback to detect order ID.
	 * @param  int    $user_id      ID of the currently logged-in user (0 = guest).
	 * @return int                  Validated order ID, or 0 if invalid/unverifiable.
	 */
	public static function resolve_woo_order_id( $order_id, $source_url, $user_id ) {
		// Guests cannot link orders.
		if ( ! $user_id ) {
			return 0;
		}

		// Attempt to detect order ID from URL if not passed explicitly.
		if ( ! $order_id && ! empty( $source_url ) ) {
			if ( preg_match( '#/view-order/(\d+)#', $source_url, $matches ) ) {
				$order_id = (int) $matches[1];
			}
		}

		if ( ! $order_id ) {
			return 0;
		}

		// Never trust a submitted order ID unless WooCommerce can verify ownership.
		if ( ! function_exists( 'wc_get_order' ) ) {
			return 0;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order || (int) $order->get_customer_id() !== $user_id ) {
			return 0;
		}

		return $order_id;
	}

	/**
	 * Returns the WooCommerce order edit URL, with HPOS support.
	 *
	 * @since  1.0.0
	 * @param  int $order_id  WooCommerce order ID.
	 * @return string         Admin URL to edit/view the order.
	 */
	public static function get_order_edit_url( $order_id ) {
		if ( function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				return $order->get_edit_order_url();
			}
		}

		return admin_url( 'post.php?post=' . absint( $order_id ) . '&action=edit' );
	}

	/**
	 * Published report forms (cached per request).
	 *
	 * @since 1.2.0
	 * @return WP_Post[]
	 */
	public static function get_published_report_forms() {
		static $cache = null;

		if ( null !== $cache ) {
			return $cache;
		}

		$cache = get_posts(
			array(
				'post_type'      => 'psbdx_report_form',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		return $cache;
	}
}
