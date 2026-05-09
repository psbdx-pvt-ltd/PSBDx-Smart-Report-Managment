<?php
/**
 * Meta boxes for PSBDx Smart Report Management.
 *
 * @package PSBDx_Smart_Report_Management
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PSBDX_SRM_Meta_Boxes
 *
 * Registers and renders all meta boxes for psbdx_report_form
 * and psbdx_report_log post types, and handles saving their data.
 *
 * @since 1.0.0
 */
class PSBDX_SRM_Meta_Boxes {

	/**
	 * Nonce action used for all meta box saves.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const NONCE_ACTION = 'psbdx_srm_save_meta';

	/**
	 * Nonce field name used for all meta box saves.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const NONCE_FIELD = 'psbdx_srm_meta_nonce';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post',      array( $this, 'save' ) );
	}

	/**
	 * Register all meta boxes.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register() {
		// Report Form meta boxes.
		add_meta_box(
			'psbdx_srm_form_config',
			__( 'Report Form Configuration', 'psbdx-smart-report-management' ),
			array( $this, 'render_form_config' ),
			'psbdx_report_form',
			'normal',
			'high'
		);

		add_meta_box(
			'psbdx_srm_shortcode',
			__( 'Shortcode', 'psbdx-smart-report-management' ),
			array( $this, 'render_shortcode_box' ),
			'psbdx_report_form',
			'side',
			'high'
		);

		// Report Log meta boxes.
		add_meta_box(
			'psbdx_srm_log_details',
			__( 'Report Details', 'psbdx-smart-report-management' ),
			array( $this, 'render_log_details' ),
			'psbdx_report_log',
			'normal',
			'high'
		);

		add_meta_box(
			'psbdx_srm_log_status',
			__( 'Report Status', 'psbdx-smart-report-management' ),
			array( $this, 'render_log_status' ),
			'psbdx_report_log',
			'side',
			'high'
		);

		// LearnPress integration meta box (only when LearnPress CPTs exist).
		if ( post_type_exists( 'lp_course' ) ) {
			foreach ( array( 'lp_course', 'lp_lesson', 'lp_quiz' ) as $screen ) {
				if ( ! post_type_exists( $screen ) ) {
					continue;
				}
				add_meta_box(
					'psbdx_srm_lp_integration',
					__( 'PSBDx Report Button', 'psbdx-smart-report-management' ),
					array( $this, 'render_lp_integration' ),
					$screen,
					'side',
					'low'
				);
			}
		}
	}

	// =========================================================================
	// RENDER — REPORT FORM
	// =========================================================================

	/**
	 * Render the Shortcode helper meta box.
	 *
	 * @since  1.0.0
	 * @param  WP_Post $post  Current post object.
	 * @return void
	 */
	public function render_shortcode_box( $post ) {
		$shortcode = sprintf( '[psbdx_report id="%d"]', $post->ID );
		?>
		<p class="psbdx-meta-hint"><?php esc_html_e( 'Copy and paste anywhere on your site:', 'psbdx-smart-report-management' ); ?></p>
		<div class="psbdx-copy-row">
			<code id="psbdx-sc-<?php echo esc_attr( $post->ID ); ?>"><?php echo esc_html( $shortcode ); ?></code>
			<button type="button" class="button button-small psbdx-copy-btn" data-target="psbdx-sc-<?php echo esc_attr( $post->ID ); ?>">
				<?php esc_html_e( 'Copy', 'psbdx-smart-report-management' ); ?>
			</button>
		</div>
		<p class="psbdx-meta-hint" style="margin-top:12px;">
			<?php esc_html_e( 'User reports table:', 'psbdx-smart-report-management' ); ?>
			<code>[psbdx_user_reports]</code>
		</p>
		<?php
	}

	/**
	 * Render the Report Form configuration meta box.
	 *
	 * @since  1.0.0
	 * @param  WP_Post $post  Current post object.
	 * @return void
	 */
	public function render_form_config( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$btn_text      = get_post_meta( $post->ID, '_psbdx_btn_text',         true ) ?: __( 'Report Issue', 'psbdx-smart-report-management' );
		$show_identity = get_post_meta( $post->ID, '_psbdx_show_identity',     true );
		$show_identity = ( '' === $show_identity ) ? 'yes' : $show_identity;
		$contact_label = get_post_meta( $post->ID, '_psbdx_contact_label',     true ) ?: __( 'WhatsApp Number', 'psbdx-smart-report-management' );
		$contact_req   = get_post_meta( $post->ID, '_psbdx_contact_required',  true );
		$reasons       = get_post_meta( $post->ID, '_psbdx_reasons',           true ) ?: __( 'Product not Working, Order not Delivered, Want to Cancel', 'psbdx-smart-report-management' );
		$custom_fields = get_post_meta( $post->ID, '_psbdx_custom_fields',     true );
		$cooldown_raw  = get_post_meta( $post->ID, '_psbdx_cooldown_mins',     true );
		$cooldown_mins = PSBDX_SRM_Helpers::get_effective_cooldown_mins( $post->ID );
		$is_global_cd  = ( '' === $cooldown_raw || null === $cooldown_raw );
		$is_order_form   = ( get_option( 'psbdx_global_order_form_id' )   == $post->ID );
		$is_product_form = ( get_option( 'psbdx_global_product_form_id' ) == $post->ID );
		?>

		<div class="psbdx-meta-sections">

			<?php $this->section_open( 'dashicons-button', __( 'Button Settings', 'psbdx-smart-report-management' ) ); ?>
				<?php $this->field_text( 'psbdx_btn_text', __( 'Button Label', 'psbdx-smart-report-management' ), $btn_text, __( 'e.g. Report Issue', 'psbdx-smart-report-management' ) ); ?>
			<?php $this->section_close(); ?>

			<?php $this->section_open( 'dashicons-id', __( 'User Identity Display', 'psbdx-smart-report-management' ) ); ?>
				<p class="psbdx-meta-hint"><?php esc_html_e( 'Name and email are always collected server-side from the WordPress session. This controls whether the user sees a read-only identity card in the form.', 'psbdx-smart-report-management' ); ?></p>
				<?php $this->field_checkbox( 'psbdx_show_identity', __( "Show reporter's name and email in the form (read-only)", 'psbdx-smart-report-management' ), 'yes', ( 'yes' === $show_identity ) ); ?>
			<?php $this->section_close(); ?>

			<?php $this->section_open( 'dashicons-phone', __( 'Contact Field', 'psbdx-smart-report-management' ) ); ?>
				<?php $this->field_text( 'psbdx_contact_label', __( 'Field Label', 'psbdx-smart-report-management' ), $contact_label, __( 'e.g. WhatsApp Number', 'psbdx-smart-report-management' ) ); ?>
				<?php $this->field_checkbox( 'psbdx_contact_required', __( 'Make this field required', 'psbdx-smart-report-management' ), 'yes', ( 'yes' === $contact_req ) ); ?>
			<?php $this->section_close(); ?>

			<?php $this->section_open( 'dashicons-list-view', __( 'Report Reasons', 'psbdx-smart-report-management' ) ); ?>
				<p class="psbdx-meta-hint"><?php esc_html_e( 'Enter reasons separated by commas. "Other" is always appended automatically.', 'psbdx-smart-report-management' ); ?></p>
				<?php $this->field_textarea( 'psbdx_reasons', '', $reasons, __( 'Product not Working, Order not Delivered', 'psbdx-smart-report-management' ), 3 ); ?>
			<?php $this->section_close(); ?>

			<?php $this->section_open( 'dashicons-plus-alt2', __( 'Extra Fields (Optional)', 'psbdx-smart-report-management' ) ); ?>
				<p class="psbdx-meta-hint"><?php esc_html_e( 'Additional text fields shown in the form, comma-separated. e.g. Transaction ID, Coupon Code', 'psbdx-smart-report-management' ); ?></p>
				<?php $this->field_textarea( 'psbdx_custom_fields', '', $custom_fields, __( 'Transaction ID, Coupon Code', 'psbdx-smart-report-management' ), 2 ); ?>
			<?php $this->section_close(); ?>

			<?php $this->section_open( 'dashicons-clock', __( 'Report Cooldown (Rate Limiting)', 'psbdx-smart-report-management' ) ); ?>
				<p class="psbdx-meta-hint"><?php esc_html_e( 'Prevents the same logged-in user from re-submitting via this form until the cooldown expires. Set to 0 to disable.', 'psbdx-smart-report-management' ); ?></p>
				<p class="psbdx-meta-hint">
					<?php
					if ( $is_global_cd ) {
						/* translators: %d: global minutes */
						printf( esc_html__( 'This form is currently using the global rate limit (%d minutes). Saving a value here will override the global setting for this form.', 'psbdx-smart-report-management' ), (int) $cooldown_mins );
					} else {
						esc_html_e( 'This form has its own rate limit and overrides the global setting.', 'psbdx-smart-report-management' );
					}
					?>
				</p>
				<div class="psbdx-inline-field">
					<input type="number" name="psbdx_cooldown_mins" id="psbdx_cooldown_mins"
						value="<?php echo esc_attr( $cooldown_mins ); ?>"
						min="0" max="1440" class="small-text">
					<label for="psbdx_cooldown_mins"><?php esc_html_e( 'minutes', 'psbdx-smart-report-management' ); ?></label>
				</div>
			<?php $this->section_close(); ?>

			<?php $this->section_open( 'dashicons-admin-settings', __( 'Global Display Settings', 'psbdx-smart-report-management' ) ); ?>
				<?php $this->field_checkbox( 'psbdx_is_order_form',   __( 'Show automatically on all e-commerce Order pages',  'psbdx-smart-report-management' ), '1', $is_order_form ); ?>
				<?php $this->field_checkbox( 'psbdx_is_product_form', __( 'Show automatically on all Product and Course pages', 'psbdx-smart-report-management' ), '1', $is_product_form ); ?>
			<?php $this->section_close(); ?>

		</div>
		<?php
	}

	// =========================================================================
	// RENDER — REPORT LOG
	// =========================================================================

	/**
	 * Render the Report Details meta box (read-only display).
	 *
	 * @since  1.0.0
	 * @param  WP_Post $post  Current post object.
	 * @return void
	 */
	public function render_log_details( $post ) {
		$source_url   = get_post_meta( $post->ID, '_psbdx_source_url',   true );
		$source_title = get_post_meta( $post->ID, '_psbdx_source_title', true );
		$order_id     = get_post_meta( $post->ID, '_psbdx_woo_order_id', true );
		?>
		<div class="psbdx-log-detail-wrap">

			<?php if ( $source_url ) : ?>
			<p class="psbdx-detail-row">
				<strong><?php esc_html_e( 'Reported Item:', 'psbdx-smart-report-management' ); ?></strong>
				<a href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo esc_html( $source_title ? $source_title : $source_url ); ?>
					<span class="dashicons dashicons-external" aria-hidden="true"></span>
				</a>
			</p>
			<?php endif; ?>

			<?php if ( $order_id ) : ?>
			<p class="psbdx-detail-row">
				<strong><?php esc_html_e( 'Linked Order:', 'psbdx-smart-report-management' ); ?></strong>
				<a href="<?php echo esc_url( PSBDX_SRM_Helpers::get_order_edit_url( (int) $order_id ) ); ?>" target="_blank" rel="noopener noreferrer">
					#<?php echo esc_html( $order_id ); ?>
					<span class="dashicons dashicons-external" aria-hidden="true"></span>
				</a>
				<span class="psbdx-badge psbdx-badge-purple" style="margin-left:6px;">e-commerce</span>
			</p>
			<?php endif; ?>

			<div class="psbdx-log-content">
				<?php echo wp_kses_post( $post->post_content ); ?>
			</div>

		</div>
		<?php
	}

	/**
	 * Render the Report Status meta box.
	 *
	 * @since  1.0.0
	 * @param  WP_Post $post  Current post object.
	 * @return void
	 */
	public function render_log_status( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$current  = get_post_meta( $post->ID, '_psbdx_report_status', true );
		$current  = $current ? $current : 'Processing';
		$statuses = PSBDX_SRM_Helpers::get_statuses();
		$s        = isset( $statuses[ $current ] ) ? $statuses[ $current ] : array(
			'label' => PSBDX_SRM_Helpers::get_status_label( $current ),
			'bg'    => '#e2e8f0',
			'color' => '#475569',
		);
		?>
		<div class="psbdx-status-wrap">
			<span class="psbdx-current-status-badge"
				style="background:<?php echo esc_attr( $s['bg'] ); ?>;color:<?php echo esc_attr( $s['color'] ); ?>;">
				<?php echo esc_html( $s['label'] ); ?>
			</span>

			<label for="psbdx_report_status" class="psbdx-status-update-label">
				<?php esc_html_e( 'Update status:', 'psbdx-smart-report-management' ); ?>
			</label>
			<select name="psbdx_report_status" id="psbdx_report_status" class="widefat">
				<?php foreach ( $statuses as $key => $data ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>>
						<?php echo esc_html( $data['label'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	// =========================================================================
	// RENDER — LEARNPRESS INTEGRATION
	// =========================================================================

	/**
	 * Render LearnPress course/lesson/quiz report form selector.
	 *
	 * @since  1.0.0
	 * @param  WP_Post $post  Current post object.
	 * @return void
	 */
	public function render_lp_integration( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$selected = get_post_meta( $post->ID, '_psbdx_product_report_btn', true );
		$forms    = PSBDX_SRM_Helpers::get_published_report_forms();
		?>
		<p>
			<label for="psbdx_lp_report_btn">
				<strong><?php esc_html_e( 'Select Report Form:', 'psbdx-smart-report-management' ); ?></strong>
			</label>
		</p>
		<select name="_psbdx_product_report_btn" id="psbdx_lp_report_btn" class="widefat">
			<option value=""><?php esc_html_e( '— None (use global default) —', 'psbdx-smart-report-management' ); ?></option>
			<?php foreach ( $forms as $form ) : ?>
				<option value="<?php echo esc_attr( $form->ID ); ?>" <?php selected( $selected, $form->ID ); ?>>
					<?php echo esc_html( $form->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	// =========================================================================
	// SAVE
	// =========================================================================

	/**
	 * Save all meta box data on post save.
	 *
	 * All $_POST reads happen here, after nonce verification, so the full
	 * verification chain is visible to static analysis tools.
	 *
	 * @since  1.0.0
	 * @param  int $post_id  Post ID being saved.
	 * @return void
	 */
	public function save( $post_id ) {
		// Verify nonce.
		if ( ! isset( $_POST[ self::NONCE_FIELD ] )
			|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION )
		) {
			return;
		}

		// Bail on autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Verify capability.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Collect and sanitize all expected POST fields here, after nonce
		// verification, so PHPCS can confirm the full security chain.
		$data = array(
			'psbdx_btn_text'            => isset( $_POST['psbdx_btn_text'] )            ? sanitize_text_field( wp_unslash( $_POST['psbdx_btn_text'] ) )            : '',
			'psbdx_reasons'             => isset( $_POST['psbdx_reasons'] )             ? sanitize_text_field( wp_unslash( $_POST['psbdx_reasons'] ) )             : '',
			'psbdx_custom_fields'       => isset( $_POST['psbdx_custom_fields'] )       ? sanitize_text_field( wp_unslash( $_POST['psbdx_custom_fields'] ) )       : '',
			'psbdx_contact_label'       => isset( $_POST['psbdx_contact_label'] )       ? sanitize_text_field( wp_unslash( $_POST['psbdx_contact_label'] ) )       : '',
			'psbdx_contact_required'    => isset( $_POST['psbdx_contact_required'] )    ? 'yes' : 'no',
			'psbdx_show_identity'       => isset( $_POST['psbdx_show_identity'] )       ? 'yes' : 'no',
			'psbdx_cooldown_mins'       => isset( $_POST['psbdx_cooldown_mins'] )       ? min( 1440, max( 0, (int) $_POST['psbdx_cooldown_mins'] ) ) : null,
			'psbdx_is_order_form'       => isset( $_POST['psbdx_is_order_form'] )       && '1' === $_POST['psbdx_is_order_form'],
			'psbdx_is_product_form'     => isset( $_POST['psbdx_is_product_form'] )     && '1' === $_POST['psbdx_is_product_form'],
			'psbdx_report_status'       => isset( $_POST['psbdx_report_status'] )       ? sanitize_text_field( wp_unslash( $_POST['psbdx_report_status'] ) )       : '',
			'_psbdx_product_report_btn' => isset( $_POST['_psbdx_product_report_btn'] ) ? sanitize_text_field( wp_unslash( $_POST['_psbdx_product_report_btn'] ) ) : null,
		);

		$post_type = get_post_type( $post_id );

		if ( 'psbdx_report_form' === $post_type ) {
			$this->save_form_meta( $post_id, $data );
		}

		if ( 'psbdx_report_log' === $post_type ) {
			$this->save_log_meta( $post_id, $data );
		}

		// LearnPress integration — save per-item form assignment.
		if ( null !== $data['_psbdx_product_report_btn'] ) {
			update_post_meta( $post_id, '_psbdx_product_report_btn', $data['_psbdx_product_report_btn'] );
		}
	}

	/**
	 * Save Report Form meta fields.
	 *
	 * @since  1.0.0
	 * @param  int   $post_id  Post ID.
	 * @param  array $data     Pre-sanitized POST values collected in save().
	 * @return void
	 */
	private function save_form_meta( $post_id, array $data ) {
		// Simple text fields.
		$text_fields = array(
			'psbdx_btn_text'      => '_psbdx_btn_text',
			'psbdx_reasons'       => '_psbdx_reasons',
			'psbdx_custom_fields' => '_psbdx_custom_fields',
			'psbdx_contact_label' => '_psbdx_contact_label',
		);

		foreach ( $text_fields as $key => $meta_key ) {
			if ( '' !== $data[ $key ] ) {
				update_post_meta( $post_id, $meta_key, $data[ $key ] );
			}
		}

		// Checkboxes.
		update_post_meta( $post_id, '_psbdx_contact_required', $data['psbdx_contact_required'] );
		update_post_meta( $post_id, '_psbdx_show_identity',    $data['psbdx_show_identity'] );

		// Cooldown — integer clamped 0–1440.
		if ( null !== $data['psbdx_cooldown_mins'] ) {
			update_post_meta( $post_id, '_psbdx_cooldown_mins', $data['psbdx_cooldown_mins'] );
		}

		// Global order form option.
		if ( $data['psbdx_is_order_form'] ) {
			update_option( 'psbdx_global_order_form_id', $post_id );
		} elseif ( (int) get_option( 'psbdx_global_order_form_id' ) === $post_id ) {
			delete_option( 'psbdx_global_order_form_id' );
		}

		// Global product form option.
		if ( $data['psbdx_is_product_form'] ) {
			update_option( 'psbdx_global_product_form_id', $post_id );
		} elseif ( (int) get_option( 'psbdx_global_product_form_id' ) === $post_id ) {
			delete_option( 'psbdx_global_product_form_id' );
		}
	}

	/**
	 * Save Report Log status field (the only editable log field).
	 *
	 * @since  1.0.0
	 * @param  int   $post_id  Post ID.
	 * @param  array $data     Pre-sanitized POST values collected in save().
	 * @return void
	 */
	private function save_log_meta( $post_id, array $data ) {
		if ( empty( $data['psbdx_report_status'] ) ) {
			return;
		}

		if ( PSBDX_SRM_Helpers::is_valid_status_key( $data['psbdx_report_status'] ) ) {
			update_post_meta( $post_id, '_psbdx_report_status', $data['psbdx_report_status'] );
		}
	}

	// =========================================================================
	// PRIVATE RENDERING HELPERS
	// =========================================================================

	/**
	 * Open a styled meta box section.
	 *
	 * @since  1.0.0
	 * @param  string $icon   Dashicon class (e.g. 'dashicons-button').
	 * @param  string $title  Section heading text.
	 * @return void
	 */
	private function section_open( $icon, $title ) {
		?>
		<div class="psbdx-meta-section">
			<div class="psbdx-meta-section-header">
				<span class="dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
				<strong><?php echo esc_html( $title ); ?></strong>
			</div>
			<div class="psbdx-meta-section-body">
		<?php
	}

	/**
	 * Close a styled meta box section.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function section_close() {
		echo '</div></div>';
	}

	/**
	 * Render a labelled text input field.
	 *
	 * @since  1.0.0
	 * @param  string $name         Input name attribute.
	 * @param  string $label        Visible label text.
	 * @param  string $value        Current value.
	 * @param  string $placeholder  Placeholder text.
	 * @return void
	 */
	private function field_text( $name, $label, $value, $placeholder = '' ) {
		$id = 'psbdx_' . $name;
		?>
		<p>
			<label for="<?php echo esc_attr( $id ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label><br>
			<input type="text" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				class="large-text">
		</p>
		<?php
	}

	/**
	 * Render a labelled textarea field.
	 *
	 * @since  1.0.0
	 * @param  string $name         Input name attribute.
	 * @param  string $label        Visible label text (empty string = no label).
	 * @param  string $value        Current value.
	 * @param  string $placeholder  Placeholder text.
	 * @param  int    $rows         Number of rows.
	 * @return void
	 */
	private function field_textarea( $name, $label, $value, $placeholder = '', $rows = 3 ) {
		$id = 'psbdx_' . $name;
		?>
		<p>
			<?php if ( $label ) : ?>
				<label for="<?php echo esc_attr( $id ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label><br>
			<?php endif; ?>
			<textarea name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>"
				rows="<?php echo esc_attr( $rows ); ?>"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
		</p>
		<?php
	}

	/**
	 * Render a checkbox field.
	 *
	 * @since  1.0.0
	 * @param  string $name    Input name attribute.
	 * @param  string $label   Visible label text.
	 * @param  string $value   Value when checked.
	 * @param  bool   $checked Whether the checkbox is currently checked.
	 * @return void
	 */
	private function field_checkbox( $name, $label, $value, $checked ) {
		?>
		<p>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( $name ); ?>"
					value="<?php echo esc_attr( $value ); ?>"
					<?php checked( $checked, true ); ?>>
				<?php echo esc_html( $label ); ?>
			</label>
		</p>
		<?php
	}
}
