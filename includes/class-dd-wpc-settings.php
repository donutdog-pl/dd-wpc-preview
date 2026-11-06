<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DD_WPC_Preview_Settings {

	const OPTION_CSS         = 'dd_wpc_custom_css';
	const OPTION_PILL_BG     = 'dd_wpc_pill_bg_color';
	const OPTION_ICON_SCROLL = 'dd_wpc_icon_scroll_svg';
	const OPTION_ICON_TOGGLE = 'dd_wpc_icon_toggle_svg';
	const SETTINGS_GROUP     = 'dd_wpc_preview_settings_group';

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_filter( 'upload_mimes', array( __CLASS__, 'allow_svg_upload' ) );
	}

	public static function allow_svg_upload( $mimes ) {
		if ( current_user_can( 'manage_options' ) ) {
			$mimes['svg']  = 'image/svg+xml';
			$mimes['svgz'] = 'image/svg+xml';
		}
		return $mimes;
	}

	public static function register_settings() {
		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_CSS,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'wp_strip_all_tags',
				'default'           => '',
			)
		);

		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_PILL_BG,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'rgba(255, 255, 255, 0.85)',
			)
		);

		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_ICON_SCROLL,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);

		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_ICON_TOGGLE,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);
	}

	public static function add_settings_menu() {
		add_options_page(
			__( 'WPC Mini Preview Settings', 'dd-wpc-preview' ),
			__( 'WPC Mini Preview', 'dd-wpc-preview' ),
			'manage_options',
			'dd-wpc-preview-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function enqueue_admin_assets( $hook ) {
		if ( 'settings_page_dd-wpc-preview-settings' !== $hook ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		$settings        = wp_enqueue_code_editor( array( 'type' => 'text/css' ) );
		$editor_settings = false !== $settings ? wp_json_encode( $settings ) : '{}';

		$inline_script = "
		jQuery(document).ready(function($) {
			var textarea = $('#dd_wpc_custom_css');
			if (textarea.length && wp.codeEditor) {
				wp.codeEditor.initialize(textarea, {$editor_settings});
			}

			$('.dd-wpc-color-picker').wpColorPicker();

			$('.dd-wpc-upload-btn').on('click', function(e) {
				e.preventDefault();
				var button = $(this);
				var targetInput = $('#' + button.data('target'));
				var previewBox = $('#' + button.data('preview'));

				var customUploader = wp.media({
					title: 'Wybierz lub prześlij ikonę SVG',
					button: { text: 'Użyj tej ikony' },
					library: { type: 'image/svg+xml' },
					multiple: false
				}).on('select', function() {
					var attachment = customUploader.state().get('selection').first().toJSON();
					targetInput.val(attachment.url);
					previewBox.html('<img src=\"' + encodeURI(attachment.url) + '\" style=\"max-width:32px;max-height:32px;display:block;\" />');
				}).open();
			});

			$('.dd-wpc-clear-btn').on('click', function(e) {
				e.preventDefault();
				var button = $(this);
				var targetInput = $('#' + button.data('target'));
				var previewBox = $('#' + button.data('preview'));
				targetInput.val('');
				previewBox.html('<span style=\"color:#888;font-size:12px;\">Domyślna ikona</span>');
			});
		});";

		wp_add_inline_script( 'wp-color-picker', $inline_script );
	}

	public static function get_custom_css() {
		return (string) get_option( self::OPTION_CSS, '' );
	}

	public static function get_pill_bg_color() {
		return (string) get_option( self::OPTION_PILL_BG, 'rgba(255, 255, 255, 0.85)' );
	}

	public static function get_icon_scroll_svg() {
		return (string) get_option( self::OPTION_ICON_SCROLL, '' );
	}

	public static function get_icon_toggle_svg() {
		return (string) get_option( self::OPTION_ICON_TOGGLE, '' );
	}

	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$custom_css      = self::get_custom_css();
		$pill_bg_color   = self::get_pill_bg_color();
		$icon_scroll_svg = self::get_icon_scroll_svg();
		$icon_toggle_svg = self::get_icon_toggle_svg();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WPC Mini Preview Settings', 'dd-wpc-preview' ); ?></h1>
			<?php settings_errors(); ?>

			<div style="display: flex; gap: 24px; margin-top: 20px; flex-wrap: wrap;">
				<div style="flex: 2; min-width: 420px;">
					<form method="post" action="options.php">
						<?php
						settings_fields( self::SETTINGS_GROUP );
						?>
						<h2><?php esc_html_e( 'Wygląd i Ikony (Pill / Przyciski)', 'dd-wpc-preview' ); ?></h2>
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">
										<label for="dd_wpc_pill_bg_color"><?php esc_html_e( 'Kolor tła kapsułki', 'dd-wpc-preview' ); ?></label>
									</th>
									<td>
										<input type="text" name="<?php echo esc_attr( self::OPTION_PILL_BG ); ?>" id="dd_wpc_pill_bg_color" value="<?php echo esc_attr( $pill_bg_color ); ?>" class="dd-wpc-color-picker" data-default-color="rgba(255, 255, 255, 0.85)" />
										<p class="description">
											<?php esc_html_e( 'Kolor tła kapsułki z przyciskami (zaokrąglenie: prawa strona 100%, lewa 0%). Obsługuje HEX oraz RGBA.', 'dd-wpc-preview' ); ?>
										</p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="dd_wpc_icon_scroll_svg"><?php esc_html_e( 'Ikona przycisku Przejdź (SVG)', 'dd-wpc-preview' ); ?></label>
									</th>
									<td>
										<div style="display: flex; align-items: center; gap: 12px;">
											<input type="text" name="<?php echo esc_attr( self::OPTION_ICON_SCROLL ); ?>" id="dd_wpc_icon_scroll_svg" value="<?php echo esc_url( $icon_scroll_svg ); ?>" class="regular-text code" placeholder="https://.../icon.svg" />
											<button type="button" class="button dd-wpc-upload-btn" data-target="dd_wpc_icon_scroll_svg" data-preview="dd-wpc-scroll-preview"><?php esc_html_e( 'Wybierz SVG', 'dd-wpc-preview' ); ?></button>
											<button type="button" class="button dd-wpc-clear-btn" data-target="dd_wpc_icon_scroll_svg" data-preview="dd-wpc-scroll-preview"><?php esc_html_e( 'Usuń', 'dd-wpc-preview' ); ?></button>
										</div>
										<div id="dd-wpc-scroll-preview" style="margin-top: 8px;">
											<?php if ( ! empty( $icon_scroll_svg ) ) : ?>
												<img src="<?php echo esc_url( $icon_scroll_svg ); ?>" style="max-width:32px;max-height:32px;display:block;" alt="" />
											<?php else : ?>
												<span style="color:#888;font-size:12px;"><?php esc_html_e( 'Domyślna ikona', 'dd-wpc-preview' ); ?></span>
											<?php endif; ?>
										</div>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="dd_wpc_icon_toggle_svg"><?php esc_html_e( 'Ikona przycisku Zwiń / Rozwiń (SVG)', 'dd-wpc-preview' ); ?></label>
									</th>
									<td>
										<div style="display: flex; align-items: center; gap: 12px;">
											<input type="text" name="<?php echo esc_attr( self::OPTION_ICON_TOGGLE ); ?>" id="dd_wpc_icon_toggle_svg" value="<?php echo esc_url( $icon_toggle_svg ); ?>" class="regular-text code" placeholder="https://.../icon.svg" />
											<button type="button" class="button dd-wpc-upload-btn" data-target="dd_wpc_icon_toggle_svg" data-preview="dd-wpc-toggle-preview"><?php esc_html_e( 'Wybierz SVG', 'dd-wpc-preview' ); ?></button>
											<button type="button" class="button dd-wpc-clear-btn" data-target="dd_wpc_icon_toggle_svg" data-preview="dd-wpc-toggle-preview"><?php esc_html_e( 'Usuń', 'dd-wpc-preview' ); ?></button>
										</div>
										<div id="dd-wpc-toggle-preview" style="margin-top: 8px;">
											<?php if ( ! empty( $icon_toggle_svg ) ) : ?>
												<img src="<?php echo esc_url( $icon_toggle_svg ); ?>" style="max-width:32px;max-height:32px;display:block;" alt="" />
											<?php else : ?>
												<span style="color:#888;font-size:12px;"><?php esc_html_e( 'Domyślna ikona', 'dd-wpc-preview' ); ?></span>
											<?php endif; ?>
										</div>
									</td>
								</tr>
							</tbody>
						</table>

						<h2 style="margin-top: 30px;"><?php esc_html_e( 'Custom CSS Editor', 'dd-wpc-preview' ); ?></h2>
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">
										<label for="dd_wpc_custom_css"><?php esc_html_e( 'Własny kod CSS', 'dd-wpc-preview' ); ?></label>
									</th>
									<td>
										<textarea name="<?php echo esc_attr( self::OPTION_CSS ); ?>" id="dd_wpc_custom_css" rows="16" class="large-text code" style="width: 100%; font-family: monospace;"><?php echo esc_textarea( $custom_css ); ?></textarea>
										<p class="description">
											<?php esc_html_e( 'Wpisz reguły CSS, aby nadpisać lub dostosować wygląd mini podglądu.', 'dd-wpc-preview' ); ?>
										</p>
									</td>
								</tr>
							</tbody>
						</table>

						<?php submit_button( __( 'Zapisz zmiany', 'dd-wpc-preview' ) ); ?>
					</form>
				</div>

				<div style="flex: 1; min-width: 300px; background: #fff; border: 1px solid #c3c4c7; padding: 18px 22px; border-radius: 4px; height: fit-content; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
					<h2 style="margin-top: 0; font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 8px;">
						<?php esc_html_e( 'Struktura klas CSS', 'dd-wpc-preview' ); ?>
					</h2>
					<table class="widefat striped" style="margin-top: 10px; font-size: 12px;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Selektor', 'dd-wpc-preview' ); ?></th>
								<th><?php esc_html_e( 'Opis', 'dd-wpc-preview' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><code>.dd-wpc-drawer</code></td>
								<td><?php esc_html_e( 'Główny kontener (lewy górny róg)', 'dd-wpc-preview' ); ?></td>
							</tr>
							<tr>
								<td><code>.dd-wpc-drawer-inner</code></td>
								<td><?php esc_html_e( 'Wewnętrzna ramka podglądu', 'dd-wpc-preview' ); ?></td>
							</tr>
							<tr>
								<td><code>.dd-wpc-preview-stage</code></td>
								<td><?php esc_html_e( 'Obszar klonowanego obrazu', 'dd-wpc-preview' ); ?></td>
							</tr>
							<tr>
								<td><code>.dd-wpc-pill-actions</code></td>
								<td><?php esc_html_e( 'Kapsułka z przyciskami podglądu', 'dd-wpc-preview' ); ?></td>
							</tr>
							<tr>
								<td><code>.dd-wpc-btn-scroll</code></td>
								<td><?php esc_html_e( 'Przycisk przewijania do konfiguratora', 'dd-wpc-preview' ); ?></td>
							</tr>
							<tr>
								<td><code>.dd-wpc-btn-toggle</code></td>
								<td><?php esc_html_e( 'Przycisk zwijania / rozwijania', 'dd-wpc-preview' ); ?></td>
							</tr>
							<tr>
								<td><code>.dd-wpc-drawer.is-collapsed</code></td>
								<td><?php esc_html_e( 'Stan zwinięty', 'dd-wpc-preview' ); ?></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<?php
	}
}
