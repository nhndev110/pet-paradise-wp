<?php

namespace WLL\App\Controller;

use WLL\App\Helper\Input;
use WLL\App\Helper\Loyalty;
use WLL\App\Helper\Settings;
use WLL\App\Helper\Translate;
use WLL\App\Helper\Util;
use WLL\App\Helper\WC;

defined( 'ABSPATH' ) or die;

class Common {
	/**
	 * Add admin menu.
	 *
	 * @return void
	 */
	public static function addMenu() {
		if ( WC::hasAdminPrivilege() ) {
			add_menu_page(
				WLL_PLUGIN_NAME, WLL_PLUGIN_NAME, 'manage_woocommerce', WLL_PLUGIN_SLUG, [
				self::class,
				'displayMenuContent'
			], 'dashicons-megaphone', 57
			);
		}
	}

	/**
	 * Hide add-on menu.
	 *
	 * @return void
	 */
	public static function hideMenu() {
		?>
        <style>
            #toplevel_page_wll-loyalty-launcher {
                display: none !important;
            }
        </style>
		<?php
	}

	/**
	 * Display menu content.
	 *
	 * @return void
	 */
	public static function displayMenuContent() {
		if ( ! WC::hasAdminPrivilege() ) {
			return;
		}
		$params = apply_filters( 'wll_before_launcher_admin_page', [] );
		wc_get_template( 'main.php', $params, WLL_PLUGIN_SLUG, WLL_PLUGIN_DIR . '/App/View/Admin/' );
	}

	/**
	 * Loads necessary admin scripts and styles.
	 *
	 * This method enqueues required scripts and styles for the admin area. It removes unwanted actions,
	 * enqueues specific stylesheets and scripts, loads JavaScript files, and localizes data for the scripts.
	 *
	 * @return void
	 */
	public static function adminScripts() {
		if ( Input::get( 'page' ) != WLL_PLUGIN_SLUG ) {
			return;
		}
		$suffix = '.min';
		if ( defined( 'SCRIPT_DEBUG' ) ) {
			$suffix = SCRIPT_DEBUG ? '' : '.min';
		}
		// Remove other actions
		array_map( 'remove_all_actions', apply_filters( 'wll_remove_other_plugin_enqueue_actions', [
			'admin_head',
			'admin_enqueue_scripts'
		] ) );
		// remove admin notice
		remove_all_actions( 'admin_notices' );
		// media library for launcher icon image
		wp_enqueue_media();
		wp_enqueue_style( WLL_PLUGIN_SLUG . '-wlr-font', WLR_PLUGIN_URL . 'Assets/Site/Css/wlr-fonts' . $suffix . '.css', [], WLR_PLUGIN_VERSION . '&t=' . time() );
		wp_enqueue_style( WLR_PLUGIN_SLUG . '-alertify', WLR_PLUGIN_URL . 'Assets/Admin/Css/alertify.css', [], WLR_PLUGIN_VERSION );
		wp_enqueue_script( WLR_PLUGIN_SLUG . '-alertify', WLR_PLUGIN_URL . 'Assets/Admin/Js/alertify.js', [ 'jquery' ], WLR_PLUGIN_VERSION . '&t=' . time() );
		$common_path   = WLL_PLUGIN_DIR . '/assets/admin/js/dist';
		$js_files      = Util::getDirFileLists( $common_path );
		$localize_name = '';
		foreach ( $js_files as $file ) {
			$path         = str_replace( WLL_PLUGIN_PATH, '', $file );
			$js_file_name = str_replace( $common_path . '/', '', $file );
			$js_name      = WLL_PLUGIN_SLUG . '-react-ui-' . substr( $js_file_name, 0, - 3 );
			$js_file_url  = WLL_PLUGIN_URL . $path;
			if ( $js_file_name == 'main.bundle.js' ) {
				$localize_name = $js_name;
				wp_register_script( $js_name, $js_file_url, [ 'jquery' ], WLL_PLUGIN_VERSION . '&t=' . time() );
				wp_enqueue_script( $js_name );
			}
		}

		//register the scripts
		$localize_data = [
			'ajax_url'            => admin_url( 'admin-ajax.php' ),
			//nonce
			'reset_setting_nonce' => wp_create_nonce( 'reset_settings' ),
			'local_data_nonce'    => wp_create_nonce( 'local_data' ),
			'render_page_nonce'   => wp_create_nonce( 'render_page_nonce' ),
		];
		wp_localize_script( $localize_name, 'wll_settings_form', $localize_data );
	}

	/**
	 * Adds internal addons to the provided list of addons.
	 *
	 * This method adds internal addons to the list of addons passed as a parameter. It checks for a specific addon,
	 * updates the addon list accordingly, and then adds a new internal addon to the list.
	 *
	 * @param array $add_ons An array containing the list of addons.
	 *
	 * @return array The updated list of addons with internal addons added.
	 */
	public static function addInternalAddons( $add_ons ) {
		if ( ! empty( $add_ons['wp-loyalty-launcher'] ) ) {
			unset( $add_ons['wp-loyalty-launcher'] );
		}
		update_option( 'wll_is_launcher_plugin_activated', true );
		$add_ons['wll-loyalty-launcher'] = [
			'name'         => esc_html__( 'WPLoyalty - Launcher', 'wll-loyalty-launcher' ),
			'description'  => __( 'Launcher widget for WPLoyalty. Let your customers easily discover your loyalty rewards.', 'wll-loyalty-launcher' ),
			'icon_url'     => \Wlr\App\Helpers\Util::getImageUrl( 'wll-loyalty-launcher' ),
			'page_url'     => '{addon_page}',
			'document_url' => '',
			'is_external'  => true,
			'is_pro'       => false,
			'dependencies' => [],
			'plugin_file'  => 'wll-loyalty-launcher/wll-loyalty-launcher.php',
		];

		return $add_ons;
	}

	/**
	 * Determines if the URL is valid for loading the launcher.
	 *
	 * This method checks the show conditions set in the settings to decide if the launcher should be loaded based on the current URL.
	 * It verifies the URL conditions specified in the settings array and returns a boolean indicating if the launcher should be displayed.
	 *
	 * @return bool Returns true if the URL meets the conditions to load the launcher, false otherwise.
	 */
	public static function isUrlValidToLoadLauncher() {
		$show_condition = Settings::opt( 'launcher.show_conditions', [], 'launcher_button' );
		if ( empty( $show_condition ) ) {
			return true;
		}
		$all_condition_status = [];
		if ( ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ) || ( $_SERVER['SERVER_PORT'] == 443 ) ) {
			$protocol = "https://";
		} else {
			$protocol = "http://";
		}
		$current_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		foreach ( $show_condition as $condition ) {
			if ( empty( $condition['operator']['value'] ) ) {
				$all_condition_status[] = false;
				continue;
			}
			$url    = ! empty( $condition['url_path'] ) ? $condition['url_path'] : '';
			$status = false;
			switch ( $condition['operator']['value'] ) {
				case 'home_page':
					$url    = site_url() . "/";
					$status = ( $current_url === $url );
					break;
				case 'contains':
					$status = ( strpos( $current_url, $url ) !== false );
					break;
				case 'do_not_contains':
					$status = ( strpos( $current_url, $url ) !== false ) ? false : true;
					break;
			}
			$all_condition_status[] = $status;
		}
		$condition_relationship = Settings::opt( 'launcher.condition_relationship', 'and', 'launcher_button' );
		$condition_status       = true;
		if ( $condition_relationship === 'and' && ! empty( $all_condition_status ) && in_array( false, $all_condition_status ) ) {
			$condition_status = false;
		} elseif ( $condition_relationship === 'or' && ! empty( $all_condition_status ) && ! in_array( true, $all_condition_status ) ) {
			$condition_status = false;
		}

		return $condition_status;
	}

	/**
	 * Enqueue site-specific scripts and styles for the application.
	 *
	 * This method checks if the user is banned or if the filter for loading assets before the launcher is set to false.
	 * If conditions are not met, the method returns early.
	 *
	 * Depending on the SCRIPT_DEBUG constant, a suffix for asset files is defined and appended.
	 *
	 * It fetches a list of JavaScript files from the specified directory and registers/enqueues the main bundle script.
	 * Localizes data for scripts, setting the ajax URL for AJAX requests.
	 *
	 * @return void
	 */
	public static function siteScripts() {
		if ( Loyalty::isBannedUser() || ! apply_filters( 'wll_before_launcher_assets', true ) ) {
			return;
		}
		$suffix = '.min';
		if ( defined( 'SCRIPT_DEBUG' ) ) {
			$suffix = SCRIPT_DEBUG ? '' : '.min';
		}
		$cache_fix     = apply_filters( 'wlr_load_asset_with_time', true );
		$add_cache_fix = ( $cache_fix ) ? '&t=' . time() : '';
		wp_enqueue_style( WLL_PLUGIN_SLUG . '-wlr-font', WLR_PLUGIN_URL . 'Assets/Site/Css/wlr-fonts' . $suffix . '.css', [], WLR_PLUGIN_VERSION . $add_cache_fix );
		wp_enqueue_style( WLL_PLUGIN_SLUG . '-wlr-launcher', WLL_PLUGIN_URL . 'assets/site/css/launcher-ui.css', [], WLR_PLUGIN_VERSION . $add_cache_fix );
		$common_path   = WLL_PLUGIN_DIR . '/assets/site/js/dist';
		$js_files      = Util::getDirFileLists( $common_path );
		$localize_name = "";
		foreach ( $js_files as $file ) {
			$path         = str_replace( WLL_PLUGIN_PATH, '', $file );
			$js_file_name = str_replace( $common_path . '/', '', $file );
			$js_name      = WLL_PLUGIN_SLUG . '-react-ui-' . substr( $js_file_name, 0, - 3 );
			$js_file_url  = WLL_PLUGIN_URL . $path;
			if ( $js_file_name == 'bundle.js' ) {
				$localize_name = $js_name;
				wp_register_script( $js_name, $js_file_url, [ 'jquery' ], WLR_PLUGIN_VERSION . $add_cache_fix );
				wp_enqueue_script( $js_name );
			}
		}
		$localize = [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		];
		wp_localize_script( $localize_name, 'wll_localize_data', $localize );
	}

	/**
	 * Get the launcher widget to display on the site.
	 *
	 * This method checks if the user is banned or if the filter for displaying the launcher widget is set to false.
	 * If conditions are not met, the method returns early.
	 *
	 * It allows modification of arguments before rendering the launcher widget through the 'wll_before_launcher_site_page' filter.
	 *
	 * It retrieves the path to the main site template file and renders the template with the specified arguments.
	 * The rendered template is then passed through the 'wll_launcher_widget' filter before being displayed.
	 *
	 * @return void
	 */
	public static function getLauncherWidget() {
		if ( Loyalty::isBannedUser() || ! apply_filters( 'wll_before_launcher_display', true ) ) {
			return;
		}
		$args         = apply_filters( 'wll_before_launcher_site_page', [
			//'style' => Util::renderTemplate( WLL_PLUGIN_DIR . '/assets/site/css/launcher.css' ),
		] );
		$path         = WLL_PLUGIN_DIR . '/App/View/Site/main_site.php';
		$content      = apply_filters( 'wll_launcher_widget', Util::renderTemplate( $path, $args, false ), $args );
		$allowed_html = [
			'div' => [ 'class' => [], 'id' => [] ]
		];
		echo wp_kses( $content, $allowed_html );
	}

	/**
	 * Retrieve data for the launcher widget.
	 *
	 * Fetches design settings, guest and member content data, and popup button content data to compile the widget settings.
	 * Sets various user-related flags and details.
	 * Configures labels and texts displayed in the launcher widget.
	 * Generates nonces required for security purposes.
	 * Configures additional settings for JavaScript functionalities.
	 * Sends the compiled settings as a JSON response.
	 *
	 * @return void
	 */
	public static function getLauncherWidgetData() {
		//design
		$design_settings = Settings::getDesignSettings();
		//content admin side translated values fetch
		$guest_content    = \WLL\App\Helper\Guest::getGuestContentData();
		$member_content   = \WLL\App\Helper\Member::getMemberContentData();
		$content_settings = [ 'content' => array_merge( $guest_content, $member_content ) ];
		//popup button
		$popup_button_settings                    = Settings::getLauncherButtonContentData();
		$settings                                 = array_merge( $design_settings, $content_settings, $popup_button_settings );
		$settings['is_member']                    = ! empty( WC::getLoginUserEmail() );
		$settings['is_edit_after_birth_day_date'] = Settings::get( 'is_one_time_birthdate_edit', 'wlr_settings', 'no' );
		$settings['is_pro']                       = Loyalty::isPro();
		$user                                     = Loyalty::getUserDetails();
		$settings['available_point']              = ( ! empty( $user->points ) ) ? $user->points : 0;
		$settings['labels']                       = apply_filters( 'wll_launcher_widget_labels', [
			'birth_date_label'        => [
				'day'   => __( 'Day', 'wll-loyalty-launcher' ),
				'month' => __( 'Month', 'wll-loyalty-launcher' ),
				'year'  => __( 'Year', 'wll-loyalty-launcher' ),
			],
			'footer'                  => [
				"powered_by"            => __( 'Powered by', 'wll-loyalty-launcher' ),
				'launcher_power_by_url' => 'https://wployalty.net/?utm_campaign=wployalty-link&utm_medium=launcher&utm_source=powered_by',
				"title"                 => __( 'WPLoyalty', "wll-loyalty-launcher" ),
			],
			'reward_text'             => ucfirst( Loyalty::getRewardLabel( 3 ) ),
			'coupon_text'             => __( 'Coupons', 'wll-loyalty-launcher' ),
			'loading_text'            => __( 'Loading...', 'wll-loyalty-launcher' ),
			'loading_timer_text'      => __( 'If loading takes a while, please refresh the screen...!', 'wll-loyalty-launcher' ),
			// translators: %s will replace the reward label configured in the settings
			'reward_opportunity_text' => sprintf( __( '%s Opportunities', 'wll-loyalty-launcher' ), ucfirst( Loyalty::getRewardLabel() ) ),
			// translators: %s will replace the reward label configured in the settings
			'my_rewards_text'         => sprintf( __( 'My %s', 'wll-loyalty-launcher' ), ucfirst( Loyalty::getRewardLabel( 3 ) ) ),
			'apply_button_text'       => __( 'Apply', 'wll-loyalty-launcher' ),
			'read_more_text'          => __( 'Read more', 'wll-loyalty-launcher' ),
			'read_less_text'          => __( 'Read less', 'wll-loyalty-launcher' ),
		] );
		$settings['nonces']                       = [
			'render_page_nonce'   => WC::createNonce( 'render_page_nonce' ),
			'wlr_redeem_nonce'    => WC::createNonce( 'wlr_redeem_nonce' ),
			'wlr_reward_nonce'    => WC::createNonce( 'wlr_reward_nonce' ),
			'apply_share_nonce'   => WC::createNonce( 'wlr_social_share_nonce' ),
			'revoke_coupon_nonce' => WC::createNonce( 'wlr_revoke_coupon_nonce' ),
		];
		$settings['is_redirect_self']             = apply_filters( 'wll_is_redirect_self', false );
		$settings['js_date_format']               = self::getJsDateFormat();
		$settings['is_followup_redirect']         = apply_filters( 'wlr_before_followup_share_window_open', true );
		$settings['is_reward_opportunities_show'] = apply_filters( 'wlr_launcher_show_reward_opportunities', true );
		$settings['points_conversion_round']      = apply_filters( 'wlr_launcher_points_conversion_round', 2 );
		wp_send_json_success( $settings );
	}

	/**
	 * Get the JavaScript date format based on the PHP date format setting.
	 *
	 * This method retrieves the date format option from WordPress settings.
	 * It then maps PHP date format characters to their equivalent JavaScript date format for frontend usage.
	 * Finally, it transforms the PHP date format into a JavaScript-compatible format using the mapping.
	 *
	 * @return string The JavaScript date format generated from the PHP date format.
	 */
	public static function getJsDateFormat() {
		$date_format    = get_option( 'date_format' );
		$format_mapping = apply_filters( 'wll_date_format_mapping_list', [
			// Year
			'Y' => 'yyyy', // 4-digit year (e.g., 2023)
			'y' => 'yy',   // 2-digit year (e.g., 23)
			// Month
			'm' => 'mm',   // Numeric month with leading zeros (e.g., 06)
			'n' => 'm',    // Numeric month without leading zeros (e.g., 6)
			'M' => 'mmm',  // Short month name (e.g., Jun)
			'F' => 'mmmm', // Full month name (e.g., June)
			// Day
			'd' => 'dd',   // Day of the month with leading zeros (e.g., 23)
			'j' => 'd',    // Day of the month without leading zeros (e.g., 23)
			'D' => 'ddd',  // Short day name (e.g., Sat)
			'l' => 'dddd', // Full day name (e.g., Saturday)
			// Hour
			'H' => 'HH',   // 24-hour format with leading zeros (e.g., 14)
			'h' => 'hh',   // 12-hour format with leading zeros (e.g., 02)
			'G' => 'H',    // 24-hour format without leading zeros (e.g., 14)
			'g' => 'h',    // 12-hour format without leading zeros (e.g., 2)
			'a' => 'tt',   // Lowercase am/pm marker (e.g., pm)
			'A' => 'TT',   // Uppercase AM/PM marker (e.g., PM)
			// Minute
			'i' => 'mm',   // Minutes with leading zeros (e.g., 30)
			// Second
			's' => 'ss',   // Seconds with leading zeros (e.g., 45)
		] );

		return strtr( $date_format, $format_mapping );
	}

	/**
	 * Get dynamic strings for the Loyalty Launcher based on provided input.
	 *
	 * This method checks if the input strings are in the correct format and the text domain matches 'wll-loyalty-launcher'.
	 * If conditions are not met, it returns the input strings as is.
	 *
	 * Calls Translate::getGuestDynamicStrings(), Translate::getMemberDynamicStrings(), and Translate::getLauncherDynamicStrings()
	 * to append additional dynamic strings to the input array.
	 *
	 * @param array $new_strings An array of new strings to include in the Launcher dynamic content.
	 * @param string $text_domain The text domain of the strings.
	 *
	 * @return array The updated array of dynamic strings for the Loyalty Launcher.
	 */
	public static function getLauncherDynamicStrings( $new_strings, $text_domain ) {
		if ( ! is_array( $new_strings ) || ! is_string( $text_domain ) || $text_domain != 'wll-loyalty-launcher' ) {
			return $new_strings;
		}
		Translate::getGuestDynamicStrings( $new_strings );
		Translate::getMemberDynamicStrings( $new_strings );
		Translate::getLauncherDynamicStrings( $new_strings );

		return $new_strings;
	}


}