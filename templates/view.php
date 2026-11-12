<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'dd_wpc_rendered' );
?>

<div id="dd-wpc-drawer" class="dd-wpc-drawer" role="complementary" aria-label="<?php esc_attr_e( 'Mini podgląd konfiguratora', 'dd-wpc-preview' ); ?>" aria-hidden="true">
	<div class="dd-wpc-drawer-inner">
		<div class="dd-wpc-drawer-body">
			<div class="dd-wpc-drawer-media">
				<div id="dd-wpc-preview-stage" class="dd-wpc-preview-stage">
					<img id="dd-wpc-main-image" class="dd-wpc-preview-image dd-wpc-fallback-image" src="<?php echo esc_url( $default_image_url ); ?>" alt="<?php esc_attr_e( 'Podgląd konfiguracji', 'dd-wpc-preview' ); ?>" />
				</div>
			</div>
		</div>
	</div>

	<div class="dd-wpc-pills">
		<div class="dd-wpc-pill-actions">
			<button type="button" class="dd-wpc-btn dd-wpc-btn-scroll" title="<?php esc_attr_e( 'Przewiń do konfiguratora', 'dd-wpc-preview' ); ?>" aria-label="<?php esc_attr_e( 'Przewiń do konfiguratora', 'dd-wpc-preview' ); ?>">
				<?php if ( ! empty( $icon_scroll_svg ) ) : ?>
					<img class="dd-wpc-custom-icon dd-wpc-icon-scroll" src="<?php echo esc_url( $icon_scroll_svg ); ?>" alt="" aria-hidden="true" />
				<?php else : ?>
					<svg class="dd-wpc-icon dd-wpc-icon-scroll" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<circle cx="12" cy="12" r="10"></circle>
						<polyline points="12 8 8 12 12 16"></polyline>
						<line x1="16" y1="12" x2="8" y2="12"></line>
					</svg>
				<?php endif; ?>
			</button>

			<button type="button" class="dd-wpc-btn dd-wpc-btn-toggle" aria-expanded="true" title="<?php esc_attr_e( 'Zwiń / rozwiń podgląd', 'dd-wpc-preview' ); ?>" aria-label="<?php esc_attr_e( 'Zwiń / rozwiń podgląd', 'dd-wpc-preview' ); ?>">
				<?php if ( ! empty( $icon_toggle_svg ) ) : ?>
					<img class="dd-wpc-custom-icon dd-wpc-icon-toggle" src="<?php echo esc_url( $icon_toggle_svg ); ?>" alt="" aria-hidden="true" />
				<?php else : ?>
					<svg class="dd-wpc-icon dd-wpc-icon-toggle-svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<line class="dd-wpc-toggle-line-h" x1="5" y1="12" x2="19" y2="12"></line>
						<line class="dd-wpc-toggle-line-v" x1="12" y1="5" x2="12" y2="19"></line>
					</svg>
				<?php endif; ?>
			</button>
		</div>

		<div class="dd-wpc-zoom-pill">
			<button type="button" class="dd-wpc-btn dd-wpc-btn-zoom-out" aria-controls="dd-wpc-drawer" title="<?php esc_attr_e( 'Pomniejsz podgląd', 'dd-wpc-preview' ); ?>" aria-label="<?php esc_attr_e( 'Pomniejsz podgląd', 'dd-wpc-preview' ); ?>">
				<svg class="dd-wpc-icon dd-wpc-icon-zoom-out" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
					<line x1="5" y1="12" x2="19" y2="12"></line>
				</svg>
			</button>

			<span class="dd-wpc-zoom-level" aria-live="polite">10%</span>

			<button type="button" class="dd-wpc-btn dd-wpc-btn-zoom-in" aria-controls="dd-wpc-drawer" title="<?php esc_attr_e( 'Powiększ podgląd', 'dd-wpc-preview' ); ?>" aria-label="<?php esc_attr_e( 'Powiększ podgląd', 'dd-wpc-preview' ); ?>">
				<svg class="dd-wpc-icon dd-wpc-icon-zoom-in" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
					<line x1="5" y1="12" x2="19" y2="12"></line>
					<line x1="12" y1="5" x2="12" y2="19"></line>
				</svg>
			</button>
		</div>
	</div>
</div>
