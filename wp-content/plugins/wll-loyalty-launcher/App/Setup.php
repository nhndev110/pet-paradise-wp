<?php

namespace WLL\App;

use WLL\App\Helper\Plugin;

defined( 'ABSPATH' ) or die;

class Setup {
	/**
	 * Setup hooks
	 *
	 * @return void
	 */
	public static function init() {
		register_activation_hook( WLL_PLUGIN_FILE, [ __CLASS__, 'activate' ] );
		register_deactivation_hook( WLL_PLUGIN_FILE, [ __CLASS__, 'deactivate' ] );
		//register_uninstall_hook( WLL_PLUGIN_FILE, [ __CLASS__, 'uninstall' ] );
		add_filter( 'plugin_row_meta', [ __CLASS__, 'getPluginRowMeta' ], 10, 2 );
	}

	/**
	 * Run plugin activation scripts.
	 */
	public static function activate() {
		Plugin::checkDependencies( true );

	}

	/**
	 * Run plugin activation scripts.
	 */
	public static function deactivate() {
		// silence is golden
	}

	/**
	 * Run plugin activation scripts.
	 */
	public static function uninstall() {
		// silence is golden
	}

	/**
	 * Retrieves the plugin row meta to be displayed on the Woocommerce Back in Stock plugin page.
	 *
	 * @param array $links The existing plugin row meta links.
	 * @param string $file The path to the plugin file.
	 *
	 * @return array
	 */
	public static function getPluginRowMeta( $links, $file ) {
		if ( $file != plugin_basename( WLL_PLUGIN_FILE ) ) {
			return $links;
		}
		$row_meta = [
			'support' => '<a href="' . esc_url( 'https://wployalty.net/support/' ) . '" aria-label="' . esc_attr__( 'Support', 'wll-loyalty-launcher' ) . '">' . esc_html__( 'Support', 'wll-loyalty-launcher' ) . '</a>',
		];

		return array_merge( $links, $row_meta );
	}
}