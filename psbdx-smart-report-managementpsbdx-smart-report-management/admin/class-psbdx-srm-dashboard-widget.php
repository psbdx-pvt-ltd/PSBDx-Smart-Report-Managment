<?php
/**
 * Admin dashboard widget for PSBDx Smart Report Management.
 *
 * @package PSBDx_Smart_Report_Management
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PSBDX_SRM_Dashboard_Widget
 *
 * Registers and renders a dashboard widget showing report status
 * counts and a list of recent report submissions.
 *
 * @since 1.0.0
 */
class PSBDX_SRM_Dashboard_Widget {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'wp_dashboard_setup', array( $this, 'register' ) );
	}

	/**
	 * Register the dashboard widget — admins only.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'psbdx_srm_widget',
			__( 'PSBDx Smart Report Management — Overview', 'psbdx-smart-report-management' ),
			array( $this, 'render' )
		);
	}

	/**
	 * Render the dashboard widget content.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render() {
		$statuses  = PSBDX_SRM_Helpers::get_statuses();
		$total     = (int) wp_count_posts( 'psbdx_report_log' )->publish;
		$counts    = PSBDX_SRM_Helpers::get_report_status_counts();
		$recent    = $this->get_recent_reports( 5 );
		$logs_url  = admin_url( 'edit.php?post_type=psbdx_report_log' );
		?>

		<div class="psbdx-dw">

			<!-- Total -->
			<div class="psbdx-dw-total-row">
				<span class="psbdx-dw-total"><?php echo esc_html( $total ); ?></span>
				<span class="psbdx-dw-total-label"><?php esc_html_e( 'total reports', 'psbdx-smart-report-management' ); ?></span>
			</div>

			<!-- Status cards -->
			<div class="psbdx-dw-grid">
				<?php foreach ( $statuses as $key => $s ) : ?>
				<div class="psbdx-dw-card" style="background:<?php echo esc_attr( $s['bg'] ); ?>;color:<?php echo esc_attr( $s['color'] ); ?>;">
					<div class="psbdx-dw-card-count"><?php echo esc_html( (string) ( $counts[ $key ] ?? 0 ) ); ?></div>
					<div class="psbdx-dw-card-label"><?php echo esc_html( $s['label'] ); ?></div>
				</div>
				<?php endforeach; ?>
			</div>

			<!-- Recent reports -->
			<?php if ( ! empty( $recent ) ) : ?>
			<p class="psbdx-dw-section-heading"><?php esc_html_e( 'Recent Reports', 'psbdx-smart-report-management' ); ?></p>
			<ul class="psbdx-dw-list">
				<?php foreach ( $recent as $report ) :
					$rs    = get_post_meta( $report->ID, '_psbdx_report_status', true ) ?: 'Processing';
					$sd    = isset( $statuses[ $rs ] ) ? $statuses[ $rs ] : array(
						'label' => PSBDX_SRM_Helpers::get_status_label( $rs ),
						'bg'    => '#e2e8f0',
						'color' => '#475569',
					);
					$elink = get_edit_post_link( $report->ID );
				?>
				<li class="psbdx-dw-list-item">
					<?php if ( $elink ) : ?>
					<a href="<?php echo esc_url( $elink ); ?>" class="psbdx-dw-list-title">
						<?php echo esc_html( $report->post_title ); ?>
					</a>
					<?php else : ?>
					<span class="psbdx-dw-list-title"><?php echo esc_html( $report->post_title ); ?></span>
					<?php endif; ?>
					<span class="psbdx-dw-badge" style="background:<?php echo esc_attr( $sd['bg'] ); ?>;color:<?php echo esc_attr( $sd['color'] ); ?>;">
						<?php echo esc_html( $sd['label'] ); ?>
					</span>
					<span class="psbdx-dw-date"><?php echo esc_html( get_the_date( 'd M', $report->ID ) ); ?></span>
				</li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>

			<div class="psbdx-dw-footer">
				<a href="<?php echo esc_url( $logs_url ); ?>" class="button button-primary button-small">
					<?php esc_html_e( 'View All Reports', 'psbdx-smart-report-management' ); ?> &rarr;
				</a>
			</div>

		</div>
		<?php
	}

	/**
	 * Retrieve the most recent report log posts.
	 *
	 * @since  1.0.0
	 * @param  int $limit  Number of posts to retrieve.
	 * @return WP_Post[]   Array of post objects.
	 */
	private function get_recent_reports( $limit ) {
		return get_posts( array(
			'post_type'      => 'psbdx_report_log',
			'post_status'    => 'publish',
			'numberposts'    => absint( $limit ),
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
	}
}
