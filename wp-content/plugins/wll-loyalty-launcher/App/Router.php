<?php

namespace WLL\App;

use WLL\App\Controller\Admin\Labels;
use WLL\App\Controller\Admin\Settings;
use WLL\App\Controller\Common;
use WLL\App\Controller\Guest;
use WLL\App\Controller\Member;

defined( 'ABSPATH' ) or die;

class Router {
	/**
	 * Initialize the necessary actions and filters based on the context.
	 *
	 * @return void
	 */
	public static function init() {
		if ( is_admin() ) {
			add_action( 'admin_menu', [ Common::class, 'addMenu' ] );
			add_action( 'admin_footer', [ Common::class, 'hideMenu' ] );
			add_action( 'admin_enqueue_scripts', [ Common::class, 'adminScripts' ], 100 );

			//common
			add_action( 'wp_ajax_wll_launcher_local_data', [ Labels::class, 'getLocalData' ] );
			add_action( 'wp_ajax_wll_get_launcher_labels', [ Labels::class, 'getLabels' ] );
			// setting
			add_action( 'wp_ajax_wll_launcher_settings', [ Settings::class, 'getSettings' ] );
			//save settings
			add_action( 'wp_ajax_wll_launcher_save_design', [ Settings::class, 'saveDesign' ] );
			add_action( 'wp_ajax_wll_launcher_save_content', [ Settings::class, 'saveContent' ] );
			add_action( 'wp_ajax_wll_launcher_save_launcher', [ Settings::class, 'saveLauncher' ] );
		}
		add_filter( 'wlr_internal_addons_list', [ Common::class, 'addInternalAddons' ] );
		if ( Common::isUrlValidToLoadLauncher() ) {
			add_action( 'wp_enqueue_scripts', [ Common::class, 'siteScripts' ] );
			add_action( 'wp_footer', [ Common::class, 'getLauncherWidget' ] );
		}

		//Guest
		add_action( 'wp_ajax_wll_get_guest_earn_points', [ Guest::class, 'getEarnCampaigns' ] );
		add_action( 'wp_ajax_nopriv_wll_get_guest_earn_points', [ Guest::class, 'getEarnCampaigns' ] );
		add_action( 'wp_ajax_wll_get_guest_redeem_rewards', [ Guest::class, 'getRedeemRewards' ] );
		add_action( 'wp_ajax_nopriv_wll_get_guest_redeem_rewards', [ Guest::class, 'getRedeemRewards' ] );

		//Member
		add_action( 'wp_ajax_wll_get_member_earn_points', [ Member::class, 'getEarnCampaigns' ] );
		add_action( 'wp_ajax_wll_get_member_redeem_rewards', [ Member::class, 'getAvailableRedeemReward' ] );
		add_action( 'wp_ajax_wll_get_member_redeem_coupons', [ Member::class, 'getEarnedCoupons' ] );
		add_action( 'wp_ajax_wll_get_reward_opportunity_rewards', [
			Member::class,
			'getRewardOpportunities'
		] );
		add_action( 'wp_ajax_nopriv_wll_get_reward_opportunity_rewards', [
			Member::class,
			'getRewardOpportunities'
		] );
		add_action( 'wp_ajax_wll_get_launcher_popup_details', [ Common::class, 'getLauncherWidgetData' ] );
		add_action( 'wp_ajax_nopriv_wll_get_launcher_popup_details', [ Common::class, 'getLauncherWidgetData' ] );

		// Translate
		add_filter( 'wlt_dynamic_string_list', [ Common::class, 'getLauncherDynamicStrings' ], 10, 2 );
	}
}