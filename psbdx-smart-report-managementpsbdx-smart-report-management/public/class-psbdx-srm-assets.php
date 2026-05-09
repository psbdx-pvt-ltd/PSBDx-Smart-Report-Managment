<?php
/**
 * Frontend asset loading for PSBDx Smart Report Management.
 *
 * @package PSBDx_Smart_Report_Management
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PSBDX_SRM_Assets
 *
 * Enqueues the public CSS and JS files needed for the report modal
 * and user history table.
 *
 * @since 1.0.0
 */
class PSBDX_SRM_Assets {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue public CSS and JS, and pass AJAX data to the script.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function enqueue() {
		wp_enqueue_style(
			'psbdx-srm-public',
			PSBDX_SRM_URL . 'assets/css/public.css',
			array(),
			PSBDX_SRM_VERSION
		);

		wp_enqueue_script(
			'psbdx-srm-public',
			PSBDX_SRM_URL . 'assets/js/public.js',
			array(),
			PSBDX_SRM_VERSION,
			true
		);

		wp_localize_script(
			'psbdx-srm-public',
			'psbdxSrm',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'i18n'     => array(
					'sending'       => __( 'Sending\u2026',                              'psbdx-smart-report-management' ),
					'submitted'     => __( 'Report Submitted!',                          'psbdx-smart-report-management' ),
					'thankyou'      => __( 'Thank you. We will get back to you soon.',   'psbdx-smart-report-management' ),
					'error'         => __( 'Submission failed. Please try again.',       'psbdx-smart-report-management' ),
					'networkError'  => __( 'Network error. Please check your connection.', 'psbdx-smart-report-management' ),
					'closeLabel'    => __( 'Close',                                      'psbdx-smart-report-management' ),
					'otherReason'   => __( 'Other',                                      'psbdx-smart-report-management' ),
				),
			)
		);
	}
}
