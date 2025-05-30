<?php
namespace Brightplugins_COS;

class Settings {

	public function __construct() {

		add_action( 'admin_menu', array( $this, 'bp_admin_menu' ) );
		add_filter( "plugin_row_meta", [$this, 'pluginMetaLinks'], 20, 2 );
		add_action( 'widgets_init', [$this, 'pluginOptions'], 9999999 );
		add_filter( "plugin_action_links_" . BVOS_PLUGIN_BASE, [$this, 'add_settings_link'] );

	}
	/**
	 * @param  $settings_tabs
	 * @return mixed
	 */
	public static function add_settings_tab( $settings_tabs ) {
		$settings_tabs['settings_tab_demo'] = __( 'Custom Status Settings', 'bp-custom-order-status' );
		return $settings_tabs;
	}

	/**
	 * Settings link
	 *
	 * @since 0.8.0
	 *
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$row_meta = array(
			'settings' => '<a href="' . get_admin_url( null, 'admin.php?page=wcbv-order-status-setting' ) . '">' . __( 'Settings', 'bp-custom-order-status' ) . '</a>',
		);

		return array_merge( $links, $row_meta );
	}

	public function pluginOptions() {

		// Set a unique slug-like ID
		$prefix = 'wcbv_status_default';

		// Create options
		\CSF::createOptions( $prefix, array(
			'menu_title'      => 'Order Status Settings',
			'menu_slug'       => 'wcbv-order-status-setting',
			'framework_title' => 'Custom Order Status Manager for WooCommerce <small> version: ' . BVOS_PLUGIN_VER . '</small>',
			'menu_type'       => 'submenu',
			'menu_parent'     => 'brightplugins',
			'nav'             => 'inline',
			'theme'           => 'dark',
			'footer_after'    => '',
			'footer_credit'   => 'Please rate <strong>Custom Order Status Manager for WooCommerce</strong> on  <a href="https://wordpress.org/support/plugin/bp-custom-order-status-for-woocommerce/reviews/?filter=5" target="_blank">WordPress.org</a> to help us spread the word. Thank you from the Bright Plugins team!',
			'show_footer'     => false,
			'show_bar_menu'   => false,
		) );

		// Create a section
		\CSF::createSection( $prefix, array(
			'title'  => 'General Settings',
			'fields' => array(
				array(
					'id'      => 'orderstatus_default_status',
					'type'    => 'select',
					'title'   => __( 'Default Order Status', 'bp-custom-order-status' ),
					'default' => 'bpos_disabled',
					'options' => 'bpcosOrderStatusList',
				),
				array(
					'id'      => 'preorder_status',
					'type'    => 'select',
					'class'   => ( !defined( 'WCPO_PLUGIN_VER' ) ) ? 'hidden' : '',
					'title'   => __( 'Preorder Transition Status', 'bp-custom-order-status' ),
					'default' => 'completed',
					'options' => 'bpcosOrderStatusList',
				),
				array(
					'id'      => 'enable_wpml',
					'type'    => 'switcher',
					'title'   => __( 'Enable WPML compatibility', 'bp-custom-order-status' ),
					'class'   => ( !class_exists( 'sitepress' ) ) ? 'hidden' : '',
					'default' => ( !class_exists( 'sitepress' ) ) ? false : true,
					'desc'    => __( 'It shows the status name on the current language', 'bp-custom-order-status' ),
					'label'   => __( 'Keep disabled if find any issue', 'bp-custom-order-status' ),
				),
				array(
					'type'    => 'notice',
					'style'   => 'info',
					'content' => apply_filters('cosm_upsale_notice',''),
				),
			),
		) );

		// Create a section
		\CSF::createSection( $prefix, array(
			'title'  => 'Payment Methods',
			'fields' => $this->getPaymentOptions(),
		) );

		do_action( 'bvos_setting_section', $prefix );

	}

	/**
	 * Option list for all payment methods
	 *
	 * @return array
	 */
	public function getPaymentOptions() {
		$payment_gateways = [];
		if ( is_admin() ) {
			$available_payment_gateways = WC()->payment_gateways->payment_gateways();
			$payment_gateways           = array();
			foreach ( $available_payment_gateways as $key => $gateway ) {
				$payment_gateways[] = array(
					'title'   => "Default Status for: " . $gateway->title,
					'id'      => 'orderstatus_default_statusgateway_' . $key,
					'default' => 'bpos_disabled',
					'type'    => 'select',
					'desc'    => __( 'Order on this payment method will change to this status ', 'bp-custom-order-status' ),
					'options' => 'bpcosOrderStatusList',
				);
			}
		}
		return $payment_gateways;
	}

	/**
	 * Get all woocommerce order status
	 *
	 * @return array
	 */
	public function wcbv_get_all_status() {
		$result = array();
		if ( $_REQUEST["page"] ?? '' == 'wcbv-order-status-setting' ) {
			$statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
			foreach ( $statuses as $status => $status_name ) {
				$result[substr( $status, 3 )] = $status_name;
			}
		}
		return $result;
	}

	/**
	 * Add links to plugin's description in plugins table
	 *
	 * @param  array   $links Initial list of links.
	 * @param  string  $file  Basename of current plugin.
	 * @return array
	 */
	public function pluginMetaLinks( $links, $file ) {
		if ( BVOS_PLUGIN_BASE !== $file ) {
			return $links;
		}
		$rate_cos     = '<a target="_blank" href="https://wordpress.org/support/plugin/bp-custom-order-status-for-woocommerce/reviews/?filter=5"> Rate this plugin » </a>';
		$support_link = '<a style="color:red;" target="_blank" href="https://brightplugins.com/support/">' . __( 'Support', 'bp-custom-order-status' ) . '</a>';

		$links[] = $rate_cos;
		$links[] = $support_link;

		return $links;
	}

	public function bp_admin_menu() {

		add_menu_page( 'Bright Plugins', 'Bright Plugins', '#manage_options', 'brightplugins', null, plugin_dir_url( __DIR__ ) . 'assets/img/bp-logo-icon.png', 60 );

		do_action( 'bp_sub_menu' );
	}

}
