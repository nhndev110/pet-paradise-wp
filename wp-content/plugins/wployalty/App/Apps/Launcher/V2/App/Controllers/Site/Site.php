<?php
/**
 * @author      Wployalty (Ilaiyaraja)
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 * @link        https://www.wployalty.net
 * */

namespace Wll\V2\App\Controllers\Site;

use Wll\V2\App\Controllers\Base;
use Wll\V2\App\Controllers\Guest;
use Wll\V2\App\Controllers\Member;
use Wlr\App\Helpers\EarnCampaign;
use Wlr\App\Helpers\Util;
use Wlr\App\Helpers\Woocommerce;

defined( 'ABSPATH' ) or die();

class Site extends Base {
	/**
	 * Loading site scripts and styles
	 * @return void
	 */
	public function enqueueSiteAssets() {
		if ( self::$woocommerce->isBannedUser() || ! apply_filters( 'wll_before_launcher_assets', true ) ) {
			return;
		}
		$suffix = '.min';
		if ( defined( 'SCRIPT_DEBUG' ) ) {
			$suffix = SCRIPT_DEBUG ? '' : '.min';
		}
		$cache_fix     = apply_filters( 'wlr_load_asset_with_time', true );
		$add_cache_fix = ( $cache_fix ) ? '&t=' . time() : '';
		wp_register_style( WLL_PLUGIN_SLUG . '-wlr-font', WLR_PLUGIN_URL . 'Assets/Site/Css/wlr-fonts' . $suffix . '.css', array(), WLR_PLUGIN_VERSION . $add_cache_fix );
		wp_enqueue_style( WLL_PLUGIN_SLUG . '-wlr-font' );
		wp_register_style( WLL_PLUGIN_SLUG . '-wlr-launcher', WLL_PLUGIN_URL . 'V2/Assets/Site/Css/launcher_site_ui.css', array(), WLR_PLUGIN_VERSION . $add_cache_fix );
		wp_enqueue_style( WLL_PLUGIN_SLUG . '-wlr-launcher' );
		$common_path   = WLL_PLUGIN_DIR . '/V2/Assets/Site/Js/dist';
		$js_files      = Woocommerce::getDirFileLists( $common_path );
		$localize_name = "";
		foreach ( $js_files as $file ) {
			$path         = str_replace( WLR_PLUGIN_PATH, '', $file );
			$js_file_name = str_replace( $common_path . '/', '', $file );
			$js_name      = WLR_PLUGIN_SLUG . '-react-ui-' . substr( $js_file_name, 0, - 3 );
			$js_file_url  = WLR_PLUGIN_URL . $path;
			if ( $js_file_name == 'bundle.js' ) {
				$localize_name = $js_name;
				wp_register_script( $js_name, $js_file_url, array( 'jquery' ), WLR_PLUGIN_VERSION . $add_cache_fix );// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
				wp_enqueue_script( $js_name );
			}
		}
		$localize = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		);
		wp_localize_script( $localize_name, 'wll_localize_data', $localize );
	}

	/**
	 * @return void
	 */
	public function getLauncherWidget() {
		if ( self::$woocommerce->isBannedUser() || ! apply_filters( 'wll_before_launcher_display', true ) ) {
			return;
		}
		$args = [
			'style' => Util::renderTemplate( WLL_PLUGIN_DIR . '/V2/Assets/Site/Css/launcher_site.css', [], false ),
		];
		$args = apply_filters( "wll_before_launcher_site_page", $args );
		$path = WLL_PLUGIN_DIR . '/V2/App/Views/Site/main_site.php';
		echo wp_kses_post( apply_filters( 'wll_launcher_widget', Util::renderTemplate( $path, $args, false ), $args ) );
	}

	public function launcherWidgetData() {
		$response = array(
			'success' => false,
			'data'    => array(),
		);
		//design
		$design_settings = $this->getDesignSettings();
		//content admin side translated values fetch
		$guest_base       = new Guest();
		$guest_content    = $guest_base->getGuestContentData( false );
		$member_base      = new Member();
		$member_content   = $member_base->getMemberContentData( false );
		$content_settings = array( 'content' => array_merge( $guest_content, $member_content ) );
		//popup button
		$popup_button_settings                    = $this->getLauncherButtonContentData( false );
		$settings                                 = array_merge( $design_settings, $content_settings, $popup_button_settings );
		$settings['is_member']                    = ! empty( self::$woocommerce->get_login_user_email() );
		$wlr_settings                             = self::$woocommerce->getOptions( 'wlr_settings', '' );
		$settings['is_edit_after_birth_day_date'] = isset( $wlr_settings['is_one_time_birthdate_edit'] ) && ! empty( $wlr_settings['is_one_time_birthdate_edit'] ) ? $wlr_settings['is_one_time_birthdate_edit'] : 'no';
		$earn_campaign_helper                     = EarnCampaign::getInstance();
		$settings['is_pro']                       = $earn_campaign_helper->isPro();
		$user                                     = $this->getUserDetails();
		$settings['available_point']              = ( isset( $user ) && isset( $user->points ) && ! empty( $user->points ) ) ? $user->points : 0;
		$settings['labels']                       = apply_filters( 'wll_launcher_widget_labels', [
			'birth_date_label'        => [
				'day'   => __( 'Day', 'wp-loyalty-rules' ),
				'month' => __( 'Month', 'wp-loyalty-rules' ),
				'year'  => __( 'Year', 'wp-loyalty-rules' ),
			],
			'footer'                  => [
				"powered_by"            => __( "Powered by", 'wp-loyalty-rules' ),
				'launcher_power_by_url' => 'https://wployalty.net/?utm_campaign=wployalty-link&utm_medium=launcher&utm_source=powered_by',
				"title"                 => __( "WPLoyalty", "wp-loyalty-rules" ),
			],
			'reward_text'             => ucfirst( $earn_campaign_helper->getRewardLabel( 3 ) ),
			'coupon_text'             => __( "Coupons", 'wp-loyalty-rules' ),
			'loading_text'            => __( "Loading...", 'wp-loyalty-rules' ),
			'loading_timer_text'      => __( "If loading takes a while, please refresh the screen...!", 'wp-loyalty-rules' ),
			/* translators: %s: reward label */
			'reward_opportunity_text' => sprintf( __( '%s Opportunities', 'wp-loyalty-rules' ), ucfirst( $earn_campaign_helper->getRewardLabel() ) ),
			/* translators: %s: reward label */
			'my_rewards_text'         => sprintf( __( 'My %s', 'wp-loyalty-rules' ), ucfirst( $earn_campaign_helper->getRewardLabel( 3 ) ) ),
			'apply_button_text'       => __( 'Apply', 'wp-loyalty-rules' ),
			'read_more_text'          => __( 'Read more', 'wp-loyalty-rules' ),
			'read_less_text'          => __( 'Read less', 'wp-loyalty-rules' ),
		] );
		$settings['nonces']                       = array(
			'render_page_nonce'   => wp_create_nonce( 'render_page_nonce' ),
			'wlr_redeem_nonce'    => wp_create_nonce( 'wlr_redeem_nonce' ),
			'wlr_reward_nonce'    => wp_create_nonce( 'wlr_reward_nonce' ),
			'apply_share_nonce'   => wp_create_nonce( 'wlr_social_share_nonce' ),
			'revoke_coupon_nonce' => wp_create_nonce( 'wlr_revoke_coupon_nonce' ),
		);
		$settings['is_redirect_self']             = apply_filters( 'wll_is_redirect_self', false );
		$settings['js_date_format']               = $this->getJsDateFormat();
		$settings['is_followup_redirect']         = apply_filters( 'wlr_before_followup_share_window_open', true );
		$settings['is_reward_opportunities_show'] = apply_filters( 'wlr_launcher_show_reward_opportunities', true );
		$settings['points_conversion_round']      = apply_filters( 'wlr_launcher_points_conversion_round', 2 );
		$response["success"]                      = true;
		$response["data"]                         = $settings;
		wp_send_json( $response );
	}

	function getJsDateFormat() {
		$date_format    = get_option( 'date_format' );
		$format_mapping = apply_filters( 'wll_date_format_mapping_list', array(
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
		) );

		return strtr( $date_format, $format_mapping );
	}

	function isUrlValidToLoadLauncher() {
		$show_condition = self::$settings->opt( 'launcher.show_conditions', [], 'launcher_button' );
		if ( empty( $show_condition ) ) {
			return true;
		}
		$all_condition_status = [];
		if ( ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ) || ( ! empty( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] == 443 ) ) {
			$protocol = "https://";
		} else {
			$protocol = "http://";
		}
		$host        = ! empty( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$request_uri = ! empty( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$current_url = $protocol . $host . $request_uri;
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
		$condition_relationship = self::$settings->opt( 'launcher.condition_relationship', 'and', 'launcher_button' );
		//$condition_relationship = ! empty( $settings['launcher']['condition_relationship'] ) && $settings['launcher']['condition_relationship'] === 'and' ? 'and' : 'or';
		$condition_status = true;
		if ( $condition_relationship === 'and' && ! empty( $all_condition_status ) && in_array( false, $all_condition_status ) ) {
			$condition_status = false;
		} elseif ( $condition_relationship === 'or' && ! empty( $all_condition_status ) && ! in_array( true, $all_condition_status ) ) {
			$condition_status = false;
		}

		return $condition_status;
		/*$current_url = site_url() . $_SERVER['REQUEST_URI'];
		$settings    = $this->getLauncherButtonContentData( false );
		if ( empty( $settings ) || ! is_array( $settings ) || ! isset( $settings['launcher'] ) || ! is_array( $settings['launcher'] ) ) {
			return true;
		}
		$condition_status       = true;
		$condition_relationship = isset( $settings['launcher']['condition_relationship'] ) && ! empty( $settings['launcher']['condition_relationship'] )
		                          && $settings['launcher']['condition_relationship'] === 'and' ? 'and' : 'or';
		if ( isset( $settings['launcher']['show_conditions'] ) && ! empty( $settings['launcher']['show_conditions'] ) && is_array( $settings['launcher']['show_conditions'] ) ) {
			$all_condition_status = array();
			foreach ( $settings['launcher']['show_conditions'] as $condition ) {
				$status = false;
				$type   = is_array( $condition ) && isset( $condition['operator'] ) && is_array( $condition['operator'] ) &&
				          isset( $condition['operator']['value'] ) && ! empty( $condition['operator']['value'] ) ? $condition['operator']['value'] : '';
				$url    = is_array( $condition ) && isset( $condition['url_path'] ) && ! empty( $condition['url_path'] ) ? $condition['url_path'] : '';
				switch ( $type ) {
					case 'home_page':
						$status = $current_url == site_url() . "/";
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
			if ( $condition_relationship === 'and' && ! empty( $all_condition_status ) && in_array( false, $all_condition_status ) ) {
				$condition_status = false;
			} elseif ( $condition_relationship === 'or' && ! empty( $all_condition_status ) && ! in_array( true, $all_condition_status ) ) {
				$condition_status = false;
			}
		}

		return $condition_status;*/
	}

}