<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'dd_wpc_custom_css' );
delete_option( 'dd_wpc_pill_bg_color' );
delete_option( 'dd_wpc_icon_scroll_svg' );
delete_option( 'dd_wpc_icon_toggle_svg' );
