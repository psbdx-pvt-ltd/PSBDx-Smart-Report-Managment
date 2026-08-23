<?php
/**
 * Theme Manager for PSBDx Smart Report Management.
 *
 * Handles discovery of "form themes" (built-in Light/Dark, plus any themes
 * supplied by the companion "PSRM Themes by PSBDx" addon or manually
 * dropped in by an admin), per-form theme selection, and enqueueing the
 * right CSS/JS for whichever themes are actually in use on the current
 * request.
 *
 * A "theme" is a folder containing at minimum a `main.php` header file
 * (Theme Name / Description / Version / Author, WordPress-plugin-header
 * style) and optionally `icon.png`, `style.css`, and `script.js`. Two
 * built-in themes ship inside this plugin: `light` (the plugin's original
 * look — no override CSS needed) and `dark`.
 *
 * Addons or custom integrations register additional folders to scan via
 * the `psbdx_srm_theme_sources` filter — each source is
 * `array( 'dir' => <absolute path with trailing slash>, 'url' => <matching URL>, 'builtin' => bool )`.
 * This plugin never reaches into another plugin's files to find themes;
 * it only scans directories that are explicitly registered with it, which
 * keeps every theme's storage location under that theme provider's own
 * control.
 *
 * @package PSBDx_Smart_Report_Management
 * @since   1.4.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PSBDX_SRM_Themes
 *
 * @since 1.4.7
 */
class PSBDX_SRM_Themes {

	/**
	 * Post meta key storing the selected theme slug for a given form.
	 *
	 * @since 1.4.7
	 */
	const THEME_META = '_psbdx_srm_theme';

	/**
	 * Post meta key: '1' if this form's theme animations are disabled.
	 *
	 * @since 1.4.7
	 */
	const NO_ANIMATIONS_META = '_psbdx_srm_theme_no_animations';

	/**
	 * Slug used for the built-in default (original) look.
	 *
	 * @since 1.4.7
	 */
	const DEFAULT_THEME = 'light';

	/**
	 * Where admins go to browse/buy additional themes.
	 *
	 * @since 1.4.7
	 */
	const MARKETPLACE_URL = 'https://psbdx.xyz/psrm-theme-by-psbdx';

	/**
	 * In-request cache of discovered themes, keyed by slug.
	 *
	 * @since 1.4.7
	 * @var array|null
	 */
	private static $themes_cache = null;

	/**
	 * Constructor — hooks the front-end asset loader.
	 *
	 * @since 1.4.7
	 */
	public function __construct() {
		// Priority 11: after PSBDX_SRM_Assets::enqueue() (default priority 10)
		// registers the core 'psbdx-srm-public' handle these depend on. Only
		// the dependency being *registered* by print time actually matters,
		// but running after it keeps the intent obvious.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_all_theme_assets' ), 11 );
	}

	/**
	 * Returns the list of directories to scan for themes.
	 *
	 * Always includes this plugin's own `themes/` folder (Light + Dark).
	 * The "PSRM Themes by PSBDx" addon and any other integration add their
	 * own storage location by hooking this filter — nothing here ever
	 * writes into or reads from another plugin's directory directly.
	 *
	 * @since  1.4.7
	 * @return array[]  Each item: array( 'dir' => string, 'url' => string, 'builtin' => bool ).
	 */
	public static function get_theme_sources() {
		$core = array(
			array(
				'dir'     => PSBDX_SRM_DIR . 'themes/',
				'url'     => PSBDX_SRM_URL . 'themes/',
				'builtin' => true,
			),
		);

		/**
		 * Filters the list of directories scanned for form themes.
		 *
		 * @since 1.4.7
		 * @param array[] $sources  List of array( 'dir', 'url', 'builtin' ).
		 */
		return apply_filters( 'psbdx_srm_theme_sources', $core );
	}

	/**
	 * Discovers every available theme across all registered sources.
	 *
	 * @since  1.4.7
	 * @param  bool $force_refresh  Bypass the in-request cache.
	 * @return array  slug => array( name, description, version, author, requires, icon_url, style_url, script_url, dir, url, builtin ).
	 */
	public static function get_all_themes( $force_refresh = false ) {
		if ( null !== self::$themes_cache && ! $force_refresh ) {
			return self::$themes_cache;
		}

		$themes = array();

		foreach ( self::get_theme_sources() as $source ) {
			$dir = isset( $source['dir'] ) ? trailingslashit( $source['dir'] ) : '';
			$url = isset( $source['url'] ) ? trailingslashit( $source['url'] ) : '';

			if ( '' === $dir || ! is_dir( $dir ) ) {
				continue;
			}

			$entries = @scandir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- scanning an optional, possibly-missing/unreadable directory; a warning here would break the page for something purely cosmetic (theme discovery).

			if ( ! is_array( $entries ) ) {
				continue;
			}

			foreach ( $entries as $entry ) {
				if ( '.' === $entry || '..' === $entry ) {
					continue;
				}

				// Checked without a trailing slash and suppressed: on some
				// restricted hosts (e.g. InfinityFree's open_basedir setup)
				// calling is_dir() with a trailing slash appended to a
				// plain file's name (README.txt/, index.php/) raises an
				// open_basedir warning instead of just returning false —
				// harmless, but this avoids it and skips non-directory
				// entries (like this folder's own index.php/README.txt)
				// before ever building a trailing-slash path from them.
				if ( ! @is_dir( $dir . $entry ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- see comment above; a restricted host's open_basedir warning here would clutter the page for something purely cosmetic (theme discovery).
					continue;
				}

				$theme_dir  = $dir . $entry . '/';
				$main_file  = $theme_dir . 'main.php';

				if ( ! file_exists( $main_file ) ) {
					continue;
				}

				$slug = sanitize_key( $entry );

				// Guard against two sources shipping a folder with the same
				// name — keep whichever was discovered first rather than
				// silently overwriting it (e.g. an admin's custom-uploaded
				// theme should never clobber a same-named built-in one).
				if ( isset( $themes[ $slug ] ) ) {
					continue;
				}

				$headers = get_file_data(
					$main_file,
					array(
						'name'        => 'Theme Name',
						'description' => 'Description',
						'version'     => 'Version',
						'author'      => 'Author',
						'requires'    => 'Requires SRM',
					)
				);

				if ( '' === $headers['name'] ) {
					continue; // Not a valid theme package.
				}

				$theme_url = $url . $entry . '/';

				$themes[ $slug ] = array(
					'name'        => $headers['name'],
					'description' => $headers['description'],
					'version'     => $headers['version'],
					'author'      => $headers['author'],
					'requires'    => $headers['requires'],
					'icon_url'    => file_exists( $theme_dir . 'icon.png' ) ? $theme_url . 'icon.png' : '',
					'style_url'   => file_exists( $theme_dir . 'style.css' ) ? $theme_url . 'style.css' : '',
					'style_path'  => file_exists( $theme_dir . 'style.css' ) ? $theme_dir . 'style.css' : '',
					'script_url'  => file_exists( $theme_dir . 'script.js' ) ? $theme_url . 'script.js' : '',
					'script_path' => file_exists( $theme_dir . 'script.js' ) ? $theme_dir . 'script.js' : '',
					'dir'         => $theme_dir,
					'url'         => $theme_url,
					'builtin'     => ! empty( $source['builtin'] ),
				);
			}
		}

		// The built-in Light theme always exists even if, for some reason,
		// its folder/header couldn't be read — it's what "no theme" means.
		if ( ! isset( $themes[ self::DEFAULT_THEME ] ) ) {
			$themes[ self::DEFAULT_THEME ] = array(
				'name'        => __( 'Light (Default)', 'psbdx-smart-report-management' ),
				'description' => __( "The plugin's original built-in look.", 'psbdx-smart-report-management' ),
				'version'     => PSBDX_SRM_VERSION,
				'author'      => 'PSBDx',
				'requires'    => '',
				'icon_url'    => '',
				'style_url'   => '',
				'style_path'  => '',
				'script_url'  => '',
				'script_path' => '',
				'dir'         => '',
				'url'         => '',
				'builtin'     => true,
			);
		}

		self::$themes_cache = $themes;

		return $themes;
	}

	/**
	 * Fetches a single theme's data.
	 *
	 * @since  1.4.7
	 * @param  string $slug  Theme slug.
	 * @return array|null
	 */
	public static function get_theme( $slug ) {
		$themes = self::get_all_themes();
		return isset( $themes[ $slug ] ) ? $themes[ $slug ] : null;
	}

	/**
	 * Returns the theme slug selected for a given form, falling back to
	 * Light if unset or if the previously-selected theme is no longer
	 * available (e.g. its addon was deactivated).
	 *
	 * @since  1.4.7
	 * @param  int $form_id  Report-form post ID.
	 * @return string
	 */
	public static function get_form_theme( $form_id ) {
		$slug   = sanitize_key( get_post_meta( (int) $form_id, self::THEME_META, true ) );
		$themes = self::get_all_themes();

		return ( $slug && isset( $themes[ $slug ] ) ) ? $slug : self::DEFAULT_THEME;
	}

	/**
	 * Whether this form's admin has disabled theme animations (entrance
	 * effects, canvas/particle backgrounds, button/field motion). Themes
	 * are expected to respect the `psbdx-theme-no-anim` wrapper class this
	 * drives — see theme_class() / theme_wrapper_classes().
	 *
	 * @since  1.4.7
	 * @param  int $form_id  Report-form post ID.
	 * @return bool
	 */
	public static function animations_disabled( $form_id ) {
		return '1' === get_post_meta( (int) $form_id, self::NO_ANIMATIONS_META, true );
	}

	/**
	 * The CSS class applied to a form/report-page wrapper for a given theme,
	 * so a theme's stylesheet can scope every rule under it (e.g.
	 * `.psbdx-theme-dark .psbdx-modal { ... }`) without leaking into other
	 * forms on the same page that use a different theme.
	 *
	 * @since  1.4.7
	 * @param  string $slug  Theme slug.
	 * @return string
	 */
	public static function theme_class( $slug ) {
		return 'psbdx-theme-' . sanitize_html_class( $slug ?: self::DEFAULT_THEME );
	}

	/**
	 * The full set of wrapper classes for a given form — its theme class
	 * plus (when the admin has disabled animations for it) the
	 * `psbdx-theme-no-anim` class every theme's CSS/JS is expected to
	 * check for. Convenience wrapper around theme_class() +
	 * animations_disabled() for the two places that render a form/report
	 * wrapper (shortcodes.php, report-page.php).
	 *
	 * @since  1.4.7
	 * @param  int $form_id  Report-form post ID.
	 * @return string  Space-separated class list.
	 */
	public static function theme_wrapper_classes( $form_id ) {
		$classes = array( self::theme_class( self::get_form_theme( $form_id ) ) );

		if ( self::animations_disabled( $form_id ) ) {
			$classes[] = 'psbdx-theme-no-anim';
		}

		return implode( ' ', $classes );
	}

	/**
	 * Persists the theme selected in the Form Builder's Appearance section.
	 * Called from PSBDX_SRM_Form_Builder::save_meta_box().
	 *
	 * @since  1.4.7
	 * @param  int $post_id  Report-form post ID being saved.
	 * @return void
	 */
	public static function save_from_request( $post_id ) {
		$posted = isset( $_POST['psbdx_srm_theme'] ) ? sanitize_key( wp_unslash( $_POST['psbdx_srm_theme'] ) ) : self::DEFAULT_THEME; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce already verified by the caller (PSBDX_SRM_Form_Builder::save_meta_box) before any $_POST field, including this one, is read.

		$themes = self::get_all_themes();
		if ( ! isset( $themes[ $posted ] ) ) {
			$posted = self::DEFAULT_THEME;
		}

		update_post_meta( $post_id, self::THEME_META, $posted );

		$no_anim = ! empty( $_POST['psbdx_srm_theme_no_animations'] ) ? '1' : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above.
		update_post_meta( $post_id, self::NO_ANIMATIONS_META, $no_anim );
	}

	/**
	 * Renders the Form Builder's dedicated Themes tab: a theme picker
	 * (with thumbnails where a theme provides icon.png), an "Add More
	 * Themes" button linking to the PSBDx theme marketplace, and an
	 * animations on/off toggle.
	 *
	 * @since  1.4.7
	 * @param  WP_Post $post  The report-form post being edited.
	 * @return void
	 */
	public static function render_form_builder_section( $post ) {
		$themes      = self::get_all_themes();
		$selected    = self::get_form_theme( $post->ID );
		$no_anim_now = self::animations_disabled( $post->ID );
		?>
		<div class="psrm-settings-section">
			<div class="psrm-settings-section-header">
				<span class="dashicons dashicons-admin-appearance" aria-hidden="true"></span>
				<strong><?php esc_html_e( 'Theme', 'psbdx-smart-report-management' ); ?></strong>
			</div>
			<div class="psrm-settings-section-body">
				<p class="psrm-hint">
					<?php esc_html_e( 'Choose how this specific form looks and behaves — including its fields, colours, and the response/ticket view page. Other forms are unaffected.', 'psbdx-smart-report-management' ); ?>
				</p>

				<div class="psrm-theme-picker" role="radiogroup" aria-label="<?php esc_attr_e( 'Form theme', 'psbdx-smart-report-management' ); ?>">
					<?php foreach ( $themes as $slug => $theme ) : ?>
						<label class="psrm-theme-option <?php echo ( $slug === $selected ) ? 'psrm-theme-option-selected' : ''; ?>">
							<input type="radio" name="psbdx_srm_theme" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $slug, $selected ); ?>>
							<span class="psrm-theme-thumb">
								<?php if ( $theme['icon_url'] ) : ?>
									<img src="<?php echo esc_url( $theme['icon_url'] ); ?>" alt="">
								<?php else : ?>
									<span class="dashicons dashicons-admin-customizer" aria-hidden="true"></span>
								<?php endif; ?>
							</span>
							<span class="psrm-theme-meta">
								<strong><?php echo esc_html( $theme['name'] ); ?></strong>
								<?php if ( ! empty( $theme['builtin'] ) ) : ?>
									<span class="psrm-theme-badge"><?php esc_html_e( 'Built-in', 'psbdx-smart-report-management' ); ?></span>
								<?php endif; ?>
								<?php if ( $theme['description'] ) : ?>
									<span class="psrm-theme-desc"><?php echo esc_html( $theme['description'] ); ?></span>
								<?php endif; ?>
							</span>
						</label>
					<?php endforeach; ?>
				</div>

				<p class="psrm-theme-add-more">
					<a href="<?php echo esc_url( self::MARKETPLACE_URL ); ?>" target="_blank" rel="noopener noreferrer" class="button">
						<span class="dashicons dashicons-plus-alt2" aria-hidden="true" style="vertical-align:text-bottom;"></span>
						<?php esc_html_e( 'Add More Themes', 'psbdx-smart-report-management' ); ?>
					</a>
					<span class="description">
						<?php esc_html_e( 'Browse premium themes from PSBDx — install the free "PSRM Themes by PSBDx" addon to unlock one-click installs here.', 'psbdx-smart-report-management' ); ?>
					</span>
				</p>

				<hr>

				<label class="psrm-checkbox-row" for="psbdx_srm_theme_no_animations">
					<input type="checkbox" id="psbdx_srm_theme_no_animations" name="psbdx_srm_theme_no_animations" value="1" <?php checked( $no_anim_now ); ?>>
					<?php esc_html_e( 'Disable theme animations for this form', 'psbdx-smart-report-management' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Turns off entrance effects, background animations, and animated button/field styling for the selected theme — the theme\u2019s colours and layout still apply. Useful for accessibility, slower devices, or a calmer look.', 'psbdx-smart-report-management' ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueues the CSS/JS for every theme that has any (Light needs none —
	 * it *is* the plugin's normal public.css/public.js).
	 *
	 * Every theme's assets are loaded site-wide, the same way the plugin's
	 * own public.css/public.js already are, rather than only on pages that
	 * happen to contain a given form: because forms are inserted by
	 * shortcode, we can't reliably know in advance which forms/themes a
	 * page will render before wp_head() prints enqueued styles. Each
	 * theme's stylesheet is expected to scope its rules under
	 * `.psbdx-theme-{slug}` (see theme_class()) so an unused theme's CSS
	 * simply never matches anything on the page.
	 *
	 * @since  1.4.7
	 * @return void
	 */
	public function enqueue_all_theme_assets() {
		foreach ( self::get_all_themes() as $slug => $theme ) {
			if ( $theme['style_url'] ) {
				wp_enqueue_style(
					'psbdx-srm-theme-' . $slug,
					$theme['style_url'],
					array( 'psbdx-srm-public' ),
					$theme['style_path'] ? filemtime( $theme['style_path'] ) : $theme['version']
				);
			}

			if ( $theme['script_url'] ) {
				wp_enqueue_script(
					'psbdx-srm-theme-' . $slug,
					$theme['script_url'],
					array( 'psbdx-srm-public' ),
					$theme['script_path'] ? filemtime( $theme['script_path'] ) : $theme['version'],
					true
				);
			}
		}
	}

	/**
	 * Fallback printer for the report detail page, mirroring
	 * PSBDX_SRM_Assets::print_style_tag() — used only when the active theme
	 * never calls wp_head() so wp_enqueue_scripts-based loading silently
	 * never happens.
	 *
	 * @since  1.4.7
	 * @param  string $slug  Theme slug currently in use on the page.
	 * @return void
	 */
	public static function print_fallback_style( $slug ) {
		$theme = self::get_theme( $slug );

		if ( $theme && $theme['style_url'] ) {
			$ver = $theme['style_path'] ? filemtime( $theme['style_path'] ) : $theme['version'];
			PSBDX_SRM_Assets::print_style_tag( 'psbdx-srm-theme-' . $slug, $theme['style_url'] . '?ver=' . rawurlencode( (string) $ver ) );
		}

		if ( $theme && $theme['script_url'] ) {
			$ver = $theme['script_path'] ? filemtime( $theme['script_path'] ) : $theme['version'];
			PSBDX_SRM_Assets::print_script_tag( 'psbdx-srm-theme-' . $slug, $theme['script_url'] . '?ver=' . rawurlencode( (string) $ver ) );
		}
	}
}
