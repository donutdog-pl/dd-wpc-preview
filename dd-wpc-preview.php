<?php
/**
 * Plugin Name:     WP Configurator Previewer
 * Plugin URI:      https://sultanowski.com
 * Description:     Floating collapsible mini-preview drawer for WP Configurator that tracks scroll and syncs real-time configuration.
 * Author:          Dariusz Sułtanowski
 * Author URI:      https://sultanowski.com
 * Text Domain:     dd-wpc-preview
 * Domain Path:     /languages
 * Version:         0.2.4
 *
 * @package         Dd_Wpc_Preview
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DD_WPC_VERSION', '0.2.4' );
define( 'DD_WPC_FILE', __FILE__ );
define( 'DD_WPC_DIR', plugin_dir_path( __FILE__ ) );
define( 'DD_WPC_URL', plugin_dir_url( __FILE__ ) );

require_once DD_WPC_DIR . 'includes/class-dd-wpc-settings.php';

function dd_wpc_is_configurator_active() {
	return defined( 'WPC_VERSION' ) || class_exists( 'WPC' ) || did_action( 'wpc_loaded' );
}

/**
 * Check if the current page could potentially contain a configurator.
 * Avoids enqueuing assets and rendering drawer on completely unrelated pages (e.g., standard blog posts).
 */
function dd_wpc_should_load() {
	if ( ! dd_wpc_is_configurator_active() ) {
		return false;
	}

	// Always allow overriding via filter for flexibility
	$should_load = null;

	if ( is_admin() ) {
		return false;
	}

	// Check WooCommerce single product page
	if ( function_exists( 'is_product' ) && is_product() ) {
		global $post;
		if ( $post && get_post_meta( $post->ID, '_wpc_config_id', true ) ) {
			$should_load = true;
		}
	}

	// Check if configurator post type
	if ( is_singular( 'amz_configurator' ) ) {
		$should_load = true;
	}

	// Check if post content has relevant shortcodes
	if ( null === $should_load && is_singular() ) {
		global $post;
		if ( $post && ! empty( $post->post_content ) ) {
			$shortcodes = array(
				'wpc_config',
				'wpc_config_preview',
				'dd_wpc_configurator',
			);
			foreach ( $shortcodes as $sc ) {
				if ( has_shortcode( $post->post_content, $sc ) ) {
					$should_load = true;
					break;
				}
			}
		}
	}

	// Default fallback to true to ensure backward-compatibility if detection cannot be certain
	if ( null === $should_load ) {
		$should_load = true;
	}

	return (bool) apply_filters( 'dd_wpc_should_load', $should_load );
}

function dd_wpc_admin_notices() {
	if ( ! dd_wpc_is_configurator_active() ) {
		?>
		<div class="notice notice-error is-dismissible">
			<p>
				<strong><?php esc_html_e( 'WP Configurator Previewer:', 'dd-wpc-preview' ); ?></strong>
				<?php esc_html_e( 'This plugin requires WP Configurator to be installed and active in order to work properly.', 'dd-wpc-preview' ); ?>
			</p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'dd_wpc_admin_notices' );

function dd_wpc_init() {
	if ( is_admin() ) {
		DD_WPC_Preview_Settings::init();
	}
}
add_action( 'init', 'dd_wpc_init' );

function dd_wpc_preview_enqueue_assets() {
	if ( ! dd_wpc_should_load() ) {
		return;
	}

	wp_enqueue_style(
		'dd-wpc-preview-styles',
		DD_WPC_URL . 'styles/global.css',
		array(),
		DD_WPC_VERSION
	);

	$pill_bg_color = DD_WPC_Preview_Settings::get_pill_bg_color();
	$dynamic_css   = ':root { --dd-wpc-pill-bg: ' . esc_attr( $pill_bg_color ) . '; }';

	$custom_css = DD_WPC_Preview_Settings::get_custom_css();
	if ( ! empty( $custom_css ) ) {
		$dynamic_css .= "\n" . $custom_css;
	}

	wp_add_inline_style( 'dd-wpc-preview-styles', $dynamic_css );

	wp_enqueue_script(
		'dd-wpc-preview-scripts',
		DD_WPC_URL . 'scripts/main.js',
		array(),
		DD_WPC_VERSION,
		true
	);

	wp_localize_script(
		'dd-wpc-preview-scripts',
		'ddWpcData',
		array(
			'defaultImage' => esc_url( DD_WPC_URL . 'assets/default.png' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'dd_wpc_preview_enqueue_assets' );

function dd_wpc_render_view() {
	if ( ! dd_wpc_is_configurator_active() ) {
		return '';
	}

	$default_image_url = DD_WPC_URL . 'assets/default.png';
	$icon_scroll_svg   = DD_WPC_Preview_Settings::get_icon_scroll_svg();
	$icon_toggle_svg   = DD_WPC_Preview_Settings::get_icon_toggle_svg();

	ob_start();
	include DD_WPC_DIR . 'templates/view.php';
	return ob_get_clean();
}
add_shortcode( 'dd_wpc_configurator', 'dd_wpc_render_view' );

/**
 * Ensure WP Configurator Pro does not dequeue our assets when running in full window mode.
 */
function dd_wpc_full_window_allowed_assets( $allowed ) {
	if ( ! is_array( $allowed ) ) {
		$allowed = array();
	}
	$allowed[] = 'dd-wpc-preview-styles';
	$allowed[] = 'dd-wpc-preview-scripts';
	return array_unique( $allowed );
}
add_filter( 'wpc_full_window_allowed_styles', 'dd_wpc_full_window_allowed_assets' );
add_filter( 'wpc_full_window_allowed_scripts', 'dd_wpc_full_window_allowed_assets' );

function dd_wpc_auto_render_drawer() {
	static $rendered = false;
	if ( $rendered || ! dd_wpc_should_load() ) {
		return;
	}

	if ( did_action( 'dd_wpc_rendered' ) ) {
		return;
	}

	echo dd_wpc_render_view(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$rendered = true;
}
add_action( 'wp_footer', 'dd_wpc_auto_render_drawer' );
