<?php

namespace WLL\App\Controller;

use WLL\App\Helper\Loyalty;
use WLL\App\Helper\Util;
use WLL\App\Helper\WC;

defined( 'ABSPATH' ) or die;

class Guest {
	/**
	 * Get earn campaigns based on security validation and user role.
	 *
	 * @return void
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
	 * Retrieve redeemable rewards data for the logged-in user.
	 *
	 * This method fetches the list of redeemable rewards for the user based on certain conditions.
	 * It performs security validation before processing the rewards data.
	 * If the user is not an admin, it retrieves the rewards list and processes each reward item.
	 * It sends a success response with the reward list and a message if rewards are found, otherwise, it sends a dummy reward list.
	 *
	 * @return void
	 */
	public static function getRedeemRewards() {
		if ( ! WC::isSiteSecurityValid( 'render_page_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Basic check failed', 'wll-loyalty-launcher' ) ] );
		}
		if ( ! Util::isAdminSide() ) {
			$user_email    = WC::getLoginUserEmail();
			$customer_page = new \Wlr\App\Controllers\Site\CustomerPage();
			$rewards       = $customer_page->getRewardList();
			if ( ! empty( $rewards ) && is_array( $rewards ) ) {
				foreach ( $rewards as $reward ) {
					//phpcs:ignore
					$reward->name = ! empty( $reward->name ) ? __( $reward->name, 'wll-loyalty-launcher' ) : '';
					//phpcs:ignore
					$reward->description = ! empty( $reward->description ) ? __( $reward->description, 'wll-loyalty-launcher' ) : '';
					$reward->action_text = Loyalty::getUserRewardText( $reward );
				}
			}
			// translators: %s will be replaced with the reward label
			$message     = empty( $rewards ) ? sprintf( __( 'No %s found!', 'wll-loyalty-launcher' ), Loyalty::getRewardLabel( 3 ) ) : '';
			$reward_list = apply_filters( 'wll_before_launcher_rewards_data', $rewards, $user_email );
			wp_send_json_success( [ 'redeem_data' => $reward_list, 'message' => $message ] );
		}
		wp_send_json_success( [ 'redeem_data' => Loyalty::getDummyRewardList() ] );
	}

}