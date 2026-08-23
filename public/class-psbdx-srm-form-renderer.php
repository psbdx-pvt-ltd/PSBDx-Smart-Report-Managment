<?php
/**
 * Frontend renderer for v2 Form Builder fields.
 *
 * Reads the JSON field schema stored by PSBDX_SRM_Form_Builder and outputs
 * the corresponding HTML fields inside the existing modal structure.
 *
 * The old shortcode continues to work for v1 forms so nothing is broken.
 *
 * @package PSBDx_Smart_Report_Management
 * @since   1.3.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PSBDX_SRM_Form_Renderer
 *
 * Static helpers consumed by PSBDX_SRM_Shortcodes to render v2 fields.
 *
 * @since 1.3.0
 */
class PSBDX_SRM_Form_Renderer {

	/**
	 * Render all v2 builder fields for a given form.
	 *
	 * Called from the shortcode render method when the form has
	 * `_psrm_form_version = 2`.
	 *
	 * @since  1.3.0
	 * @param  int    $form_id  Report Form post ID.
	 * @param  string $uid      Unique instance suffix (prevents ID collisions).
	 * @return string           HTML output (escaped).
	 */
	public static function render_fields( $form_id, $uid ) {
		$fields = self::get_schema( $form_id );

		if ( empty( $fields ) ) {
			return '';
		}

		$captcha_provider = PSBDX_SRM_Captcha::active_provider();
		$captcha_site_key = $captcha_provider ? PSBDX_SRM_Captcha::get_opt( $captcha_provider, 'site_key' ) : '';

		if ( self::schema_has_sections( $fields ) ) {
			return self::render_paginated_fields( $fields, $uid, $captcha_provider, $captcha_site_key );
		}

		$out = '';
		foreach ( $fields as $field ) {
			$out .= self::render_field_markup( $field, $uid, $captcha_provider, $captcha_site_key );
		}

		return $out;
	}

	/**
	 * Read and decode a form's v2 field schema.
	 *
	 * @since  1.4.8
	 * @param  int $form_id  Report Form post ID.
	 * @return array[]       Field definitions, or an empty array.
	 */
	private static function get_schema( $form_id ) {
		$json   = get_post_meta( $form_id, PSBDX_SRM_Form_Builder::FIELDS_META_KEY, true );
		$fields = json_decode( $json, true );

		return is_array( $fields ) ? $fields : array();
	}

	/**
	 * Whether a schema contains at least one Section field, i.e. whether the
	 * form needs to be split across multiple pages.
	 *
	 * @since  1.4.8
	 * @param  array[] $fields  Field schema.
	 * @return bool
	 */
	private static function schema_has_sections( array $fields ) {
		foreach ( $fields as $field ) {
			if ( 'section' === sanitize_key( $field['type'] ?? '' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Whether a given form's schema uses Sections (and therefore renders its
	 * own page-nav/submit buttons — see render_paginated_fields()). Used by
	 * PSBDX_SRM_Shortcodes to decide whether to print the default trailing
	 * submit button, so a paginated form doesn't end up with two.
	 *
	 * @since  1.4.8
	 * @param  int $form_id  Report Form post ID.
	 * @return bool
	 */
	public static function has_sections( $form_id ) {
		return self::schema_has_sections( self::get_schema( $form_id ) );
	}

	/**
	 * Group a schema into pages split on Section fields, and render each
	 * page with its own heading, fields, and Back/Next/Submit controls.
	 *
	 * Fields before the first Section field form page 0 (no heading, since
	 * there's no Section field to supply one). Each subsequent Section field
	 * opens a new page and supplies that page's heading — same idea as a
	 * Google Forms section break.
	 *
	 * @since  1.4.8
	 * @param  array[] $fields            Field schema (known to contain a 'section' entry).
	 * @param  string  $uid               Unique instance suffix.
	 * @param  string  $captcha_provider  Active captcha provider slug, or ''.
	 * @param  string  $captcha_site_key  Active captcha site key, or ''.
	 * @return string  HTML output (escaped).
	 */
	private static function render_paginated_fields( array $fields, $uid, $captcha_provider, $captcha_site_key ) {
		$pages   = array();
		$current = array( 'section' => null, 'fields' => array() );

		foreach ( $fields as $field ) {
			if ( 'section' === sanitize_key( $field['type'] ?? '' ) ) {
				$pages[]  = $current;
				$current  = array( 'section' => $field, 'fields' => array() );
				continue;
			}
			$current['fields'][] = $field;
		}
		$pages[] = $current;

		$total = count( $pages );
		$out   = '<div class="psrm-pages" data-total-pages="' . esc_attr( $total ) . '">';

		foreach ( $pages as $i => $page ) {
			$is_first    = ( 0 === $i );
			$is_last     = ( $i === $total - 1 );
			$section     = $page['section'];
			$next_action = $section ? sanitize_key( $section['next_action'] ?? 'next' ) : 'next';
			// A page ends in a Submit button if it's the last page, or if its
			// own section was explicitly set to submit early (skipping any
			// sections that would otherwise follow) — same as Google Forms
			// letting a section jump straight to "Submit form".
			$ends_in_submit = $is_last || 'submit' === $next_action;

			$out .= '<div class="psrm-page' . ( $is_first ? ' psrm-page-active' : '' ) . '"'
				. ' data-page-index="' . esc_attr( $i ) . '"'
				. ( $is_first ? '' : ' hidden' ) . '>';

			if ( $section ) {
				$sec_label = esc_html( $section['label'] ?? '' );
				$sec_desc  = esc_html( $section['description'] ?? '' );
				$out .= '<div class="psrm-section-heading">';
				if ( '' !== $sec_label ) {
					$out .= '<h3 class="psrm-section-heading-title">' . $sec_label . '</h3>';
				}
				if ( '' !== $sec_desc ) {
					$out .= '<p class="psrm-section-heading-desc">' . $sec_desc . '</p>';
				}
				$out .= '<span class="psrm-section-progress">' . esc_html( sprintf(
					/* translators: 1: current section number, 2: total number of sections */
					__( 'Section %1$d of %2$d', 'psbdx-smart-report-management' ),
					$i + 1,
					$total
				) ) . '</span>';
				$out .= '</div>';
			}

			foreach ( $page['fields'] as $field ) {
				$out .= self::render_field_markup( $field, $uid, $captcha_provider, $captcha_site_key );
			}

			$out .= '<div class="psrm-page-nav">';
			if ( ! $is_first ) {
				$out .= '<button type="button" class="psrm-prev-btn">' . esc_html__( 'Back', 'psbdx-smart-report-management' ) . '</button>';
			}
			if ( $ends_in_submit ) {
				$out .= '<button type="submit" class="psbdx-submit-btn">'
					. '<span class="psbdx-btn-label">' . esc_html__( 'Submit Report', 'psbdx-smart-report-management' ) . '</span>'
					. '<span class="psbdx-btn-spinner" aria-hidden="true" style="display:none;">'
						. '<span class="psbdx-spinner"></span> ' . esc_html__( 'Sending\u2026', 'psbdx-smart-report-management' )
					. '</span>'
					. '</button>';
			} else {
				$out .= '<button type="button" class="psrm-next-btn" data-next-page="' . esc_attr( $i + 1 ) . '">'
					. esc_html__( 'Next', 'psbdx-smart-report-management' ) . '</button>';
			}
			$out .= '</div>'; // .psrm-page-nav

			// Thin progress line under the button, so the visitor can see
			// how far through the form they are at a glance — separate from
			// the "Section X of Y" text above, which some themes may hide.
			$progress_pct = (int) round( ( ( $i + 1 ) / $total ) * 100 );
			$out .= '<div class="psrm-page-progress" role="progressbar" aria-valuenow="' . esc_attr( $progress_pct ) . '" aria-valuemin="0" aria-valuemax="100" aria-label="' . esc_attr__( 'Form progress', 'psbdx-smart-report-management' ) . '">'
				. '<div class="psrm-page-progress-bar" style="width:' . esc_attr( $progress_pct ) . '%;"></div>'
				. '</div>';

			$out .= '</div>'; // .psrm-page
		}

		$out .= '</div>'; // .psrm-pages

		return $out;
	}

	/**
	 * Render the HTML for a single field definition.
	 *
	 * Extracted from render_fields() so both the flat (no Sections) and
	 * paginated (has Sections) render paths share the exact same per-field
	 * markup.
	 *
	 * @since  1.4.8
	 * @param  array  $field             Single field definition.
	 * @param  string $uid               Unique instance suffix.
	 * @param  string $captcha_provider  Active captcha provider slug, or ''.
	 * @param  string $captcha_site_key  Active captcha site key, or ''.
	 * @return string  HTML output (escaped).
	 */
	private static function render_field_markup( $field, $uid, $captcha_provider, $captcha_site_key ) {
			$type     = sanitize_key( $field['type'] ?? '' );
			$label    = esc_html( $field['label'] ?? '' );
			$handle   = sanitize_key( $field['handle'] ?? '' );
			$required = ! empty( $field['required'] );
			$field_id = 'psrm-field-' . esc_attr( $uid ) . '-' . esc_attr( $handle );

			$req_attr  = $required ? ' required' : '';
			$req_badge = $required
				? '<span class="psbdx-required" aria-label="' . esc_attr__( 'required', 'psbdx-smart-report-management' ) . '">*</span>'
				: '<span class="psbdx-optional">' . esc_html__( 'optional', 'psbdx-smart-report-management' ) . '</span>';

			// Help text — shown directly under the field's label, above its
			// input, for every input type. Title/Section render their own
			// description as a heading subtitle instead (see their cases
			// below), and Captcha has no label to hang it off of.
			$description = trim( (string) ( $field['description'] ?? '' ) );
			$desc_html   = '' !== $description
				? '<p class="psrm-field-hint psrm-field-desc">' . esc_html( $description ) . '</p>'
				: '';

			$out = '';

			switch ( $type ) {

				case 'title':
					// Read-only heading — no input, so no required badge and
					// nothing is ever submitted for it.
					$desc = esc_html( $field['description'] ?? '' );
					$out .= '<div class="psbdx-field psrm-title-field">';
					$out .= '<h3 class="psrm-title-field-heading">' . $label . '</h3>';
					if ( '' !== $desc ) {
						$out .= '<p class="psrm-title-field-desc">' . $desc . '</p>';
					}
					$out .= '</div>';
					break;

				case 'section':
					// Section fields are purely structural page breaks — the
					// paginated render path above consumes them to build page
					// headings and never reaches this switch for them. Nothing
					// to output here; this case only guards against a stray
					// 'section' entry ever reaching this method directly.
					break;

				case 'name':
					$out .= '<div class="psbdx-field psrm-field-name-row">';
					$out .= '<div class="psrm-name-col">';
					$out .= '<label for="' . esc_attr( $field_id ) . '-first">'
						. esc_html__( 'First Name', 'psbdx-smart-report-management' )
						. ( $required ? ' <span class="psbdx-required" aria-label="' . esc_attr__( 'required', 'psbdx-smart-report-management' ) . '">*</span>' : '' )
						. '</label>';
					$out .= $desc_html;
					$out .= '<input type="text" id="' . esc_attr( $field_id ) . '-first"'
						. ' name="psrm_v2[' . esc_attr( $handle ) . '_first]"'
						. ' placeholder="' . esc_attr__( 'First Name', 'psbdx-smart-report-management' ) . '"'
						. $req_attr . ' autocomplete="given-name">';
					$out .= '</div>';
					$out .= '<div class="psrm-name-col">';
					$out .= '<label for="' . esc_attr( $field_id ) . '-last">'
						. esc_html__( 'Last Name', 'psbdx-smart-report-management' )
						. ( $required ? ' <span class="psbdx-required" aria-label="' . esc_attr__( 'required', 'psbdx-smart-report-management' ) . '">*</span>' : '' )
						. '</label>';
					$out .= $desc_html;
					$out .= '<input type="text" id="' . esc_attr( $field_id ) . '-last"'
						. ' name="psrm_v2[' . esc_attr( $handle ) . '_last]"'
						. ' placeholder="' . esc_attr__( 'Last Name', 'psbdx-smart-report-management' ) . '"'
						. ' autocomplete="family-name">';
					$out .= '</div>';
					$out .= '</div>';
					break;

				case 'email':
					$out .= '<div class="psbdx-field">';
					$out .= '<label for="' . esc_attr( $field_id ) . '">' . $label . ' ' . $req_badge . '</label>';
					$out .= $desc_html;
					$out .= '<input type="email" id="' . esc_attr( $field_id ) . '"'
						. ' name="psrm_v2[' . esc_attr( $handle ) . ']"'
						. ' placeholder="' . esc_attr__( 'email@example.com', 'psbdx-smart-report-management' ) . '"'
						. ' autocomplete="email"' . $req_attr . '>';
					$out .= '</div>';
					break;

				case 'mobile':
					$out .= '<div class="psbdx-field">';
					$out .= '<label for="' . esc_attr( $field_id ) . '">' . $label . ' ' . $req_badge . '</label>';
					$out .= $desc_html;
					$out .= '<input type="tel" id="' . esc_attr( $field_id ) . '"'
						. ' name="psrm_v2[' . esc_attr( $handle ) . ']"'
						. ' placeholder="' . esc_attr__( '+1 555 000 0000', 'psbdx-smart-report-management' ) . '"'
						. ' autocomplete="tel"' . $req_attr . '>';
					$out .= '</div>';
					break;

				case 'text':
					$out .= '<div class="psbdx-field">';
					$out .= '<label for="' . esc_attr( $field_id ) . '">' . $label . ' ' . $req_badge . '</label>';
					$out .= $desc_html;
					$out .= '<input type="text" id="' . esc_attr( $field_id ) . '"'
						. ' name="psrm_v2[' . esc_attr( $handle ) . ']"'
						. ' placeholder="' . $label . '"' . $req_attr . '>';
					$out .= '</div>';
					break;

				case 'paragraph':
					$out .= '<div class="psbdx-field">';
					$out .= '<label for="' . esc_attr( $field_id ) . '">' . $label . ' ' . $req_badge . '</label>';
					$out .= $desc_html;
					$out .= '<textarea id="' . esc_attr( $field_id ) . '"'
						. ' name="psrm_v2[' . esc_attr( $handle ) . ']"'
						. ' rows="4" placeholder="' . $label . '"' . $req_attr . '></textarea>';
					$out .= '</div>';
					break;

				case 'number':
					$out .= '<div class="psbdx-field">';
					$out .= '<label for="' . esc_attr( $field_id ) . '">' . $label . ' ' . $req_badge . '</label>';
					$out .= $desc_html;
					$out .= '<input type="number" id="' . esc_attr( $field_id ) . '"'
						. ' name="psrm_v2[' . esc_attr( $handle ) . ']"'
						. ' placeholder="0"' . $req_attr . '>';
					$out .= '</div>';
					break;

				case 'select':
					$choices      = is_array( $field['choices'] ?? null ) ? $field['choices'] : array();
					$other_option = ! empty( $field['other_option'] );
					$out .= '<div class="psbdx-field">';
					$out .= '<label for="' . esc_attr( $field_id ) . '">' . $label . ' ' . $req_badge . '</label>';
					$out .= $desc_html;
					$out .= '<select id="' . esc_attr( $field_id ) . '"'
						. ' name="psrm_v2[' . esc_attr( $handle ) . ']"'
						. ' class="psrm-select-field"'
						. ' data-other="' . ( $other_option ? '1' : '0' ) . '"'
						. $req_attr . '>';
					$out .= '<option value="">' . esc_html__( '— Select —', 'psbdx-smart-report-management' ) . '</option>';
					foreach ( $choices as $choice ) {
						$out .= '<option value="' . esc_attr( $choice ) . '">' . esc_html( $choice ) . '</option>';
					}
					if ( $other_option ) {
						$out .= '<option value="__other__">' . esc_html__( 'Other', 'psbdx-smart-report-management' ) . '</option>';
					}
					$out .= '</select>';
					if ( $other_option ) {
						$out .= '<div class="psrm-other-input" style="display:none;" data-for="' . esc_attr( $field_id ) . '">';
						$out .= '<label for="' . esc_attr( $field_id ) . '-other">'
							. esc_html__( 'Please specify', 'psbdx-smart-report-management' )
							. ' <span class="psbdx-required">*</span></label>';
						$out .= '<input type="text" id="' . esc_attr( $field_id ) . '-other"'
							. ' name="psrm_v2[' . esc_attr( $handle ) . '_other]"'
							. ' placeholder="' . esc_attr__( 'Please describe…', 'psbdx-smart-report-management' ) . '">';
						$out .= '</div>';
					}
					$out .= '</div>';
					break;

				case 'radio':
					$choices      = is_array( $field['choices'] ?? null ) ? $field['choices'] : array();
					$other_option = ! empty( $field['other_option'] );
					$out .= '<div class="psbdx-field psrm-choice-field">';
					$out .= '<label>' . $label . ' ' . $req_badge . '</label>';
					$out .= $desc_html;
					$out .= '<div class="psrm-choices-list">';
					foreach ( $choices as $idx => $choice ) {
						$cid  = esc_attr( $field_id ) . '-' . $idx;
						$out .= '<label class="psrm-choice-label"><input type="radio"'
							. ' id="' . $cid . '"'
							. ' name="psrm_v2[' . esc_attr( $handle ) . ']"'
							. ' value="' . esc_attr( $choice ) . '"'
							. ( $required && 0 === $idx ? ' required' : '' ) . '>'
							. ' ' . esc_html( $choice ) . '</label>';
					}
					if ( $other_option ) {
						$other_id = esc_attr( $field_id ) . '-other-opt';
						$out .= '<label class="psrm-choice-label"><input type="radio"'
							. ' id="' . $other_id . '"'
							. ' name="psrm_v2[' . esc_attr( $handle ) . ']"'
							. ' value="__other__"'
							. ' class="psrm-radio-other-trigger"'
							. ' data-target="' . esc_attr( $field_id ) . '-other-input"> '
							. esc_html__( 'Other', 'psbdx-smart-report-management' ) . '</label>';
						$out .= '<div class="psrm-other-input psrm-other-input-inline" id="' . esc_attr( $field_id ) . '-other-input" style="display:none;">';
						$out .= '<input type="text" name="psrm_v2[' . esc_attr( $handle ) . '_other]"'
							. ' placeholder="' . esc_attr__( 'Please specify…', 'psbdx-smart-report-management' ) . '">';
						$out .= '</div>';
					}
					$out .= '</div>';
					$out .= '</div>';
					break;

				case 'checkbox':
					$choices      = is_array( $field['choices'] ?? null ) ? $field['choices'] : array();
					$other_option = ! empty( $field['other_option'] );
					$out .= '<div class="psbdx-field psrm-choice-field">';
					$out .= '<label>' . $label . ' ' . $req_badge . '</label>';
					$out .= $desc_html;
					$out .= '<div class="psrm-choices-list">';
					foreach ( $choices as $idx => $choice ) {
						$cid  = esc_attr( $field_id ) . '-' . $idx;
						$out .= '<label class="psrm-choice-label"><input type="checkbox"'
							. ' id="' . $cid . '"'
							. ' name="psrm_v2[' . esc_attr( $handle ) . '][]"'
							. ' value="' . esc_attr( $choice ) . '"> '
							. esc_html( $choice ) . '</label>';
					}
					if ( $other_option ) {
						$other_id = esc_attr( $field_id ) . '-other-opt';
						$out .= '<label class="psrm-choice-label"><input type="checkbox"'
							. ' id="' . $other_id . '"'
							. ' class="psrm-checkbox-other-trigger"'
							. ' data-target="' . esc_attr( $field_id ) . '-other-input"> '
							. esc_html__( 'Other', 'psbdx-smart-report-management' ) . '</label>';
						$out .= '<div class="psrm-other-input psrm-other-input-inline" id="' . esc_attr( $field_id ) . '-other-input" style="display:none;">';
						$out .= '<input type="text" name="psrm_v2[' . esc_attr( $handle ) . '_other]"'
							. ' placeholder="' . esc_attr__( 'Please specify…', 'psbdx-smart-report-management' ) . '">';
						$out .= '</div>';
					}
					$out .= '</div>';
					$out .= '</div>';
					break;

				case 'attachment':
					$allowed_types = is_array( $field['allowed_types'] ?? null ) && ! empty( $field['allowed_types'] )
						? $field['allowed_types']
						: PSBDX_SRM_Form_Builder::ATTACHMENT_DEFAULT_TYPES;
					$min_kb  = (int) ( $field['min_size_kb'] ?? 0 );
					$max_kb  = (int) ( $field['max_size_kb'] ?? 5120 );
					$accept  = implode( ',', array_map( function ( $ext ) { return '.' . $ext; }, $allowed_types ) );

					$hint_parts = array(
						sprintf(
							/* translators: %s: comma-separated list of allowed extensions */
							esc_html__( 'Allowed: %s', 'psbdx-smart-report-management' ),
							esc_html( strtoupper( implode( ', ', $allowed_types ) ) )
						),
					);
					if ( $max_kb > 0 ) {
						$hint_parts[] = sprintf(
							/* translators: %s: max size, human readable */
							esc_html__( 'Max size: %s', 'psbdx-smart-report-management' ),
							esc_html( size_format( $max_kb * 1024 ) )
						);
					}
					if ( $min_kb > 0 ) {
						$hint_parts[] = sprintf(
							/* translators: %s: min size, human readable */
							esc_html__( 'Min size: %s', 'psbdx-smart-report-management' ),
							esc_html( size_format( $min_kb * 1024 ) )
						);
					}

					$out .= '<div class="psbdx-field">';
					$out .= '<label for="' . esc_attr( $field_id ) . '">' . $label . ' ' . $req_badge . '</label>';
					$out .= $desc_html;
					$out .= '<input type="file" id="' . esc_attr( $field_id ) . '"'
						. ' name="psrm_v2[' . esc_attr( $handle ) . ']"'
						. ' accept="' . esc_attr( $accept ) . '"'
						. $req_attr . '>';
					$out .= '<p class="psrm-field-hint">' . esc_html( implode( ' · ', $hint_parts ) ) . '</p>';
					$out .= '</div>';
					break;

				case 'review':
					$max_stars = (int) ( $field['max_stars'] ?? 5 );
					$max_stars = $max_stars > 0 ? $max_stars : 5;

					$out .= '<div class="psbdx-field psrm-review-field">';
					$out .= '<label>' . $label . ' ' . $req_badge . '</label>';
					$out .= $desc_html;
					$out .= '<div class="psrm-star-rating" data-max="' . esc_attr( $max_stars ) . '">';
					// Rendered high-to-low so the CSS "highlight this star and
					// everything after it in DOM order" sibling trick lights
					// up the correct stars regardless of which one is chosen.
					for ( $star = $max_stars; $star >= 1; $star-- ) {
						$star_id = esc_attr( $field_id ) . '-' . $star;
						$out    .= '<input type="radio"'
							. ' id="' . $star_id . '"'
							. ' name="psrm_v2[' . esc_attr( $handle ) . ']"'
							. ' value="' . esc_attr( $star ) . '"'
							. ( $required && 1 === $star ? ' required' : '' ) . '>';
						$out    .= '<label for="' . $star_id . '" class="psrm-star" title="'
							/* translators: %d: number of stars */
							. esc_attr( sprintf( _n( '%d star', '%d stars', $star, 'psbdx-smart-report-management' ), $star ) )
							. '"></label>';
					}
					$out .= '</div>';
					$out .= '</div>';
					break;

				case 'captcha':
					if ( '' !== $captcha_provider ) {
						$out .= '<div class="psbdx-captcha-widget"'
							. ' data-provider="' . esc_attr( $captcha_provider ) . '"'
							. ' data-sitekey="' . esc_attr( $captcha_site_key ) . '"'
							. ' id="psrm-captcha-' . esc_attr( $uid ) . '"></div>';
					}
					break;
			}

		return $out;
	}
}
