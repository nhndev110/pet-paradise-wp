<?php

namespace WLL\App\Helper;


use Wlr\App\Models\EarnCampaign;
use Wlr\App\Models\Levels;

defined( 'ABSPATH' ) || exit;

class Member {
	/**
	 * Retrieves member content data based on the given parameters.
	 *
	 * @param bool $is_admin_side Whether the request is from the admin side.
	 *
	 * @return array An array containing member content data.
	 */
	public static function getMemberContentData( $is_admin_side = false ) {
		$is_campaign_display          = Settings::get( 'is_campaign_display', 'wlr_settings', 'yes' );
		$is_referral_action_available = false;
		if ( $is_campaign_display === 'yes' ) {
			$earn_campaign_model          = new EarnCampaign();
			$referral_campaign            = $earn_campaign_model->getCampaignByAction( 'referral' );
			$is_referral_action_available = ! empty( $referral_campaign );
		}
		$user                = Loyalty::getUserDetails();
		$level_modal         = new Levels();
		$is_levels_available = $level_modal->checkLevelsAvailable();
		$level_data          = ( Loyalty::isPro() ) ? self::getUserLevelData( $user ) : new \stdClass();
		$referral_url        = ( $is_admin_side ) ? Loyalty::getReferralUrl( 'dummy' ) : '';
		if ( ! $is_admin_side && $is_referral_action_available === true && ! empty( $user ) && is_object( $user ) && ! empty( $user->refer_code ) ) {
			$referral_url = Loyalty::getReferralUrl( $user->refer_code );
		}
		$is_referral_action_available = $is_referral_action_available === true && ! empty( $referral_url ) && ! empty( $user ) && is_object( $user ) && ! empty( $user->refer_code );
		$short_code_data              = [
			'member' => [
				'banner'    => [
					'texts' => [
						'welcome'        => Settings::opt( 'content.member.banner.texts.welcome', sprintf( 'Hello %s', '{wlr_user_name}' ), 'content' ),
						'points'         => Settings::opt( 'content.member.banner.texts.points', '{wlr_user_points}', 'content' ),
						'points_label'   => Settings::opt( 'content.member.banner.texts.points_label', '{wlr_point_label}', 'content' ),
						'points_content' => Settings::opt( 'content.member.banner.texts.points_content', 'Your outstanding balance', 'content' ),
						'points_text'    => Settings::opt( 'content.member.banner.texts.points_text', Loyalty::getPointLabel( 3, ! $is_admin_side ), 'content' ),
					],
				],
				'points'    => [
					'earn'   => [
						'title' => Settings::opt( 'content.member.points.earn.title', 'Earn', 'content' ),
					],
					'redeem' => [
						'title' => Settings::opt( 'content.member.points.redeem.title', 'Redeem', 'content' ),
					],
				],
				'referrals' => [
					'title'       => Settings::opt( 'content.member.referrals.title', 'Refer and earn', 'content' ),
					'description' => Settings::opt( 'content.member.referrals.description', 'Refer your friends and earn rewards. Your friend can get a reward as well!', 'content' ),
				],
			],
		];
		array_walk_recursive( $short_code_data, function ( &$value, $key ) use ( $is_admin_side ) {
			//phpcs:ignore
			$value = ( ! $is_admin_side ) ? __( $value, 'wll-loyalty-launcher' ) : $value;
			$value = ( ! $is_admin_side ) ? Settings::processShortCodes( $value ) : $value;
		} );
		$data = [
			'member' => [
				'banner'    => [
					'levels' => [
						'is_levels_available' => $is_levels_available,
						'is_show'             => Settings::opt( 'content.member.banner.levels.is_show', 'show', 'content' ),
						'level_data'          => $level_data,
					],
					'points' => [
						'is_show' => Settings::opt( 'content.member.banner.points.is_show', 'show', 'content' ),
					],
				],
				'points'    => [
					'earn'   => [
						'icon' => [
							'image' => Settings::opt( 'content.member.points.earn.icon.image', '', 'content' ),
						],
					],
					'redeem' => [
						'icon' => [
							'image' => Settings::opt( 'content.member.points.redeem.icon.image', '', 'content' ),
						],
					],
				],
				'referrals' => [
					'is_referral_action_available' => $is_referral_action_available,
					'referral_url'                 => $referral_url,
				],
			]
		];
		if ( ! $is_admin_side ) {
			$social_share_list = ! empty( $referral_url ) && ! empty( $user ) && is_object( $user ) && ( ! empty( $user->user_email ) ) ? Loyalty::getSocialIconList( $user->user_email, $referral_url ) : Settings::getDummySocialShareList();
			$social_share_data = [
				'member' => [
					'referrals' => [
						'social_share_list' => $social_share_list,
					],
				]
			];
			$data              = array_merge_recursive( $data, $social_share_data );
		}

		return apply_filters( 'wll_launcher_member_content_data', array_merge_recursive( $short_code_data, $data ) );

	}

	/**
	 * Retrieves user level data based on the given user object.
	 *
	 * @param object $user The user object containing level data.
	 *
	 * @return array An array containing user level data including current level information and progress.
	 */
	public static function getUserLevelData( $user ) {
		if ( empty( $user ) ) {
			return [ 'user_has_level' => false ];
		}
		$is_user_available = ( isset( $user->id ) && is_object( $user ) && $user->id > 0 );
		$level_check       = $is_user_available && isset( $user->level_data ) && is_object( $user->level_data ) && isset( $user->level_data->current_level_name ) && ! empty( $user->level_data->current_level_name );
		$has_level         = ( isset( $user->level_id ) && $user->level_id > 0 );
		$level_data        = [
			'user_has_level' => $has_level,
		];
		if ( $is_user_available && $has_level && $level_check ) {
			$level_data['current_level_image'] = isset( $user->level_data->current_level_image ) && ! empty( $user->level_data->current_level_image ) ? $user->level_data->current_level_image : '';
			//phpcs:ignore
			$level_data['current_level_name'] = ! empty( $user->level_data ) && ! empty( $user->level_data->current_level_name ) ? __( $user->level_data->current_level_name, 'wll-loyalty-launcher' ) : '';
			if ( isset( $user->level_data->current_level_start ) && isset( $user->level_data->next_level_start ) && $user->level_data->next_level_start > 0 ) {
                $points = apply_filters('wll_points_to_get_level', $user->earn_total_point, $user);
				$level_data['level_range'] = round( ( ( $points - $user->level_data->current_level_start ) / ( $user->level_data->next_level_start - $user->level_data->current_level_start ) ) * 100 );
				$needed_point              = $user->level_data->next_level_start - $points;
				// translators: First %1$d will replace the needed point to reach next level, Second %2$s will replace the point label
				$level_data['progress_content']       = sprintf( __( '%1$d %2$s more needed to unlock next level', 'wll-loyalty-launcher' ), (int) $needed_point, Loyalty::getPointLabel( $needed_point ) );
				$level_data['is_reached_final_level'] = false;
			} else {
				$level_data['is_reached_final_level'] = true;
				$level_data['progress_content']       = __( 'Congratulations! You have reached the final level', 'wll-loyalty-launcher' );
			}
		}

		return $level_data;
	}
}