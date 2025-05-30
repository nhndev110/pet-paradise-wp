<?php

namespace WLL\App\Controller;

use WLL\App\Helper\Loyalty;
use WLL\App\Helper\Util;
use WLL\App\Helper\WC;
use Wlr\App\Controllers\Site\CustomerPage;
use Wlr\App\Models\UserRewards;

defined( 'ABSPATH' ) or die;

class Member {
	/**
	 * Get the earn campaigns for the current user.
	 *
	 * This method checks security validity using a nonce and returns different earn campaigns
	 * based on user permissions.
	 *
	 * @return mixed The earn campaigns as an array or json response.
	 */
	public static function getEarnCampaigns() {
		if ( ! WC::isSiteSecurityValid( 'render_page_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Basic check failed', 'wll-loyalty-launcher' ) ] );
		}
		if ( ! Util::isAdminSide() ) {
			wp_send_json_success( Loyalty::getCampaigns() );
		}
		wp_send_json_success( [ 'earn_points' => Loyalty::getDummyCampaigns() ] );
	}

	/**
	 * Get the available redeem rewards for the current user.
	 *
	 * This method checks security validity using a nonce and retrieves available redeem rewards
	 * for the user. It processes the reward list and applies necessary filters before returning
	 * the data in a json response.
	 *
	 * @return mixed The redeem data as an array or json response.
	 */
	public static function getAvailableRedeemReward() {
		if ( ! WC::isSiteSecurityValid( 'render_page_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Basic check failed', 'wll-loyalty-launcher' ) ] );
		}
		if ( ! Util::isAdminSide() ) {
			$user_email        = WC::getLoginUserEmail();
			$available_rewards = CustomerPage::getAvailableRewards( $user_email );
			if ( empty( $available_rewards ) ) {
				wp_send_json_success( [
					'redeem_data' => [],
					// translators: %s will be replaced with the reward label
					'message'     => sprintf( __( 'No %s found!', 'wll-loyalty-launcher' ), Loyalty::getRewardLabel( 3 ) )
				] );
			}
			foreach ( $available_rewards as $user_reward ) {
				//phpcs:ignore
				$user_reward->name = ! empty( $user_reward->name ) ? __( $user_reward->name, 'wll-loyalty-launcher' ) : '';
				//phpcs:ignore
				$user_reward->description = ! empty( $user_reward->description ) ? __( $user_reward->description, 'wll-loyalty-launcher' ) : '';
				$user_reward->button_text = __( 'Redeem', 'wll-loyalty-launcher' );
				$user_reward->action_text = Loyalty::getUserRewardText( $user_reward );
				if ( ! empty( $user_reward->discount_code ) ) {
					$user_reward->button_text = "";
					$user_reward->action_text = Loyalty::getUserCouponText( $user_reward );
				}
				$user_reward->is_point_convertion_reward = false;
				$user_reward->is_redirect_to_coupon      = true;
				if ( isset( $user_reward->discount_type ) && $user_reward->discount_type == 'points_conversion' && isset( $user_reward->reward_table ) && $user_reward->reward_table != 'user_reward' ) {
					$user_reward->is_point_convertion_reward = true;
					$user_reward->is_redirect_to_coupon      = false;
				}
				$user_reward->expiry_date_text = "";
				if ( ! empty( $user_reward->expiry_date ) && ! empty( $user_reward->discount_code ) ) {
					// translators: %s will be replaced with the expiry date
					$user_reward->expiry_date_text = sprintf( __( 'Expires on %s', "wll-loyalty-launcher" ), $user_reward->expiry_date );
				}
				if ( empty( $user_reward->discount_code ) ) {
					$user_reward->is_show_reward = 1;
				}
			}
			$available_rewards = apply_filters( 'wll_before_launcher_user_rewards_data', $available_rewards, $user_email );
			$customer_page     = new \Wlr\App\Controllers\Site\CustomerPage();
			$user              = Loyalty::getLoyaltyUserByEmail( $user_email );
			$available_rewards = $customer_page->processRewardList( $available_rewards, [ 'wp_user' => $user ] );
			foreach ( $available_rewards as &$user_reward ) {
				if ( isset( $user_reward->discount_type ) && $user_reward->discount_type == 'points_conversion' && isset( $user_reward->reward_table ) && $user_reward->reward_table != 'user_reward' ) {
					$user_reward->discount_value = ( $user_reward->coupon_type == 'percent' ) ? $user_reward->discount_value : WC::getCustomPrice( $user_reward->discount_value, false );
				}
			}
			wp_send_json_success( [ 'redeem_data' => $available_rewards, ] );
		}
		wp_send_json_success( [ 'redeem_data' => Loyalty::getDummyRewardList() ] );
	}

	/**
	 * Get the earned coupons for the current user.
	 *
	 * This method checks security validity using a nonce and retrieves earned coupons for the user.
	 * If the user is an admin, dummy coupon data is returned; otherwise, actual coupon rewards are fetched.
	 * The coupon rewards are processed and returned as a JSON response.
	 *
	 * @return mixed The earned coupons as an array or json response.
	 */
	public static function getEarnedCoupons() {
		if ( ! WC::isSiteSecurityValid( 'render_page_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Basic check failed', 'wll-loyalty-launcher' ) ] );
		}
		if ( ! Util::isAdminSide() ) {
			$user_rewards   = new UserRewards();
			$user_email     = WC::getLoginUserEmail();
			$coupon_rewards = $user_rewards->getCustomerCouponRewardByEmail( $user_email, [
				'limit'  => - 1,
				'offset' => 0
			] );
			$customer_page  = new \Wlr\App\Controllers\Site\CustomerPage();
			$coupon_rewards = $customer_page->processRewardList( $coupon_rewards );
			if ( empty( $coupon_rewards ) ) {
				wp_send_json_success( [
					'redeem_coupons' => [],
					'message'        => __( 'No coupons found!', 'wll-loyalty-launcher' )
				] );
			}
			$coupon_rewards = array_values( $coupon_rewards );// For React, key must start from 0
			foreach ( $coupon_rewards as &$coupon_reward ) {
				$coupon_reward->reward_table = 'user_reward';
			}
			wp_send_json_success( [
				'redeem_coupons' => $coupon_rewards,
				'message'        => ''
			] );
		}
		wp_send_json_success( [
			'redeem_coupons' => Loyalty::getDummyCouponData(),
			'message'        => ''
		] );
	}

	/**
	 * Get the reward opportunities for the current user.
	 *
	 * This method checks security validity using a nonce and returns different reward opportunities
	 * based on user permissions.
	 *
	 * @return mixed The reward opportunities as an array or json response.
	 */
	public static function getRewardOpportunities() {
		if ( ! WC::isSiteSecurityValid( 'render_page_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Basic check failed', 'wll-loyalty-launcher' ) ] );
		}
		if ( ! Util::isAdminSide() ) {
			wp_send_json_success( Loyalty::getRewardOpportunities() );
		}
		wp_send_json_success( [ 'reward_opportunity' => Loyalty::getDummyRewardOpportunities() ] );
	}
}