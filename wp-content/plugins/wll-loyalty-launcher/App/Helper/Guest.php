<?php

namespace WLL\App\Helper;


use Wlr\App\Models\EarnCampaign;

defined( 'ABSPATH' ) || exit;

class Guest {
	/**
	 * Retrieves guest content data based on the provided parameters.
	 *
	 * @param bool $is_admin_side Specifies if the request is coming from the admin side.
	 *
	 * @return array Returns an array containing guest content data after processing shortcodes and translations.
	 */
	public static function getGuestContentData( $is_admin_side = false ) {
		$short_code_data = [
			'guest' => [
				'welcome'   => [
					'texts'  => [
						'title'        => Settings::opt( 'content.guest.welcome.texts.title', 'Join and Earn Rewards', 'content' ),
						'description'  => Settings::opt( 'content.guest.welcome.texts.description', 'Get exclusive perks by becoming a member of our rewards program.', 'content' ),
						'have_account' => Settings::opt( 'content.guest.welcome.texts.have_account', 'Already have an account?', 'content' ),
						'sign_in'      => Settings::opt( 'content.guest.welcome.texts.sign_in', 'Sign in', 'content' ),
						'sign_in_url'  => Settings::opt( 'content.guest.welcome.texts.sign_in_url', '{wlr_signin_url}', 'content' ),
					],
					'button' => [
						'text' => Settings::opt( 'content.guest.welcome.button.text', 'Join Now!', 'content' ),
						'url'  => Settings::opt( 'content.guest.welcome.button.url', '{wlr_signup_url}', 'content' ),
					],
				],
				'points'    => [
					'earn'   => [
						'title' => Settings::opt( 'content.guest.points.earn.title', 'Earn', 'content' ),
					],
					'redeem' => [
						'title' => Settings::opt( 'content.guest.points.redeem.title', 'Redeem', 'content' ),
					],
				],
				'referrals' => [
					'title'       => Settings::opt( 'content.guest.referrals.title', 'Refer and earn', 'content' ),
					'description' => Settings::opt( 'content.guest.referrals.description', 'Refer your friends and earn rewards. Your friend can get a reward as well!', 'content' ),
				],
			],
		];
		array_walk_recursive( $short_code_data, function ( &$value, $key ) use ( $is_admin_side ) {
			// phpcs:ignore
			$value = ( ! $is_admin_side ) ? __( $value, 'wll-loyalty-launcher' ) : $value;
			$value = ( ! $is_admin_side ) ? Settings::processShortCodes( $value ) : $value;
		} );

		$is_campaign_display          = Settings::get( 'is_campaign_display', 'wlr_settings', 'yes' );
		$is_referral_action_available = false;
		if ( $is_campaign_display === 'yes' ) {
			$earn_campaign_model          = new EarnCampaign();
			$referral_campaign            = $earn_campaign_model->getCampaignByAction( 'referral' );
			$is_referral_action_available = ! empty( $referral_campaign );
		}
		$data = [
			'guest' => [
				'welcome'   => [
					'icon' => [
						'image' => Settings::opt( 'content.guest.welcome.icon.image', '', 'content' ),
					],
				],
				'points'    => [
					'earn'   => [
						'icon' => [
							'image' => Settings::opt( 'content.guest.points.earn.icon.image', '', 'content' ),
						],
					],
					'redeem' => [
						'icon' => [
							'image' => Settings::opt( 'content.guest.points.redeem.icon.image', '', 'content' ),
						],
					],
				],
				'referrals' => [
					'is_referral_action_available' => $is_referral_action_available,
				]
			]
		];

		return apply_filters( 'wll_launcher_guest_content_data', array_merge_recursive( $short_code_data, $data ) );

	}
}