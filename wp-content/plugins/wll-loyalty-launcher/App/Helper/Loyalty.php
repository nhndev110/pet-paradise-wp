<?php

namespace WLL\App\Helper;

use Wlr\App\Models\EarnCampaignTransactions;
use Wlr\App\Models\Users;

defined( 'ABSPATH' ) || exit;

class Loyalty {
	/**
	 * Retrieves the point value associated with a given user email.
	 *
	 * @param string $user_email The email address of the user to retrieve points for.
	 *
	 * @return int The point value associated with the user if found, otherwise 0.
	 */
	public static function getUserPoint( string $user_email ) {
		if ( empty( $user_email ) ) {
			return 0;
		}
		$user = self::getLoyaltyUserByEmail( $user_email );

		return ! empty( $user ) ? $user->points : 0;
	}

	/**
	 * Retrieves the loyalty user by their email.
	 *
	 * @param string $user_email The email of the loyalty user to retrieve.
	 *
	 * @return mixed The loyalty user data if found, otherwise an empty string.
	 */
	public static function getLoyaltyUserByEmail( string $user_email ) {
		if ( empty( $user_email ) ) {
			return '';
		}
		$user_model = new Users();
		global $wpdb;
		$query = $wpdb->prepare( "user_email = %s", sanitize_email( $user_email ) );

		return $user_model->getWhere( $query );
	}

	/**
	 * Determines if the plugin is in the Pro version based on filters.
	 *
	 * @return bool True if the plugin is in the Pro version, false otherwise.
	 */
	public static function isPro() {
		return apply_filters( 'wlr_is_pro', false );
	}

	/**
	 * Checks if a user is banned based on the user email.
	 *
	 * @param string $user_email The email of the user to check if banned. Defaults to an empty string.
	 *
	 * @return bool Whether the user is banned (true) or not (false).
	 */
	public static function isBannedUser( $user_email = "" ) {
		if ( empty( $user_email ) ) {
			$user_email = WC::getLoginUserEmail();
			if ( empty( $user_email ) ) {
				return false;
			}
		}
		$user    = get_user_by( 'email', $user_email );
		$user_id = ! empty( $user->ID ) ? $user->ID : 0;
		if ( ! apply_filters( 'wlr_before_add_to_loyalty_customer', true, $user_id, $user_email ) ) {
			return true;
		}

		$user_modal = new Users();
		global $wpdb;
		$where = $wpdb->prepare( "user_email = %s AND is_banned_user = %d ", array( $user_email, 1 ) );
		$user  = $user_modal->getWhere( $where, "*", true );

		return ( ! empty( $user ) && is_object( $user ) && isset( $user->is_banned_user ) );
	}

	/**
	 * Retrieves the campaigns list for the current user.
	 *
	 * @return array An array containing information about the campaigns.
	 */
	public static function getCampaigns() {
		$customer_page = new \Wlr\App\Controllers\Site\CustomerPage();
		$user_email    = WC::getLoginUserEmail();
		$campaign_list = $customer_page->getCampaignList();

		if ( empty( $campaign_list ) || ! is_array( $campaign_list ) ) {
			return [
				'earn_points' => $campaign_list,
				'message'     => __( 'Create a first campaign.', 'wll-loyalty-launcher' )
			];
		}

		$message               = '';
		$is_show_campaign_list = [];
		$user                  = self::getUserDetails();
		foreach ( $campaign_list as &$active_campaigns ) {
			//phpcs:ignore
			$active_campaigns->name = ! empty( $active_campaigns->name ) ? __( $active_campaigns->name, 'wll-loyalty-launcher' ) : '';
			//phpcs:ignore
			$active_campaigns->description = ! empty( $active_campaigns->description ) ? __( $active_campaigns->description, 'wll-loyalty-launcher' ) : '';
			//phpcs:ignore
			$active_campaigns->campaign_title_discount = ! empty( $active_campaigns->campaign_title_discount ) ? __( $active_campaigns->campaign_title_discount, 'wll-loyalty-launcher' ) : '';
			if ( ! empty( $active_campaigns->action_type ) ) {
				self::getCampaignActions( $active_campaigns, $user );
			}
			if ( ! empty( $active_campaigns->action_type ) && $active_campaigns->action_type == 'birthday' ) {
				$active_campaigns->birthday_date_format = apply_filters( 'wlr_my_account_birthday_date_format', [
					'format'    => [ 'd', 'm', 'Y' ],
					'separator' => '-'
				] );
			}
			$is_show_campaign_list[] = $active_campaigns;
		}

		if ( count( $is_show_campaign_list ) == 0 ) {
			$message = __( 'Create a first campaign.', 'wll-loyalty-launcher' );
		}
		$campaign_list = apply_filters( 'wll_before_launcher_earn_points_data', array_values( $campaign_list ), $user_email );

		return [ 'earn_points' => $campaign_list, 'message' => $message ];
	}

	/**
	 * Retrieves user details based on the user's email.
	 *
	 * @return stdClass Returns an instance of stdClass containing the user details.
	 */
	public static function getUserDetails() {
		$user_email = WC::getLoginUserEmail();
		if ( empty( $user_email ) ) {
			return new \stdClass();
		}
		$customer_page = new \Wlr\App\Controllers\Site\CustomerPage();

		return $customer_page->getPageUserDetails( $user_email, 'launcher' );
	}

	/**
	 * Retrieves the actions for a given campaign and user.
	 *
	 * @param object $active_campaigns The active campaign object.
	 * @param object $user The user object.
	 *
	 * @return void
	 */
	public static function getCampaignActions( &$active_campaigns, $user ) {
		if ( empty( $active_campaigns ) || ! is_object( $active_campaigns ) ) {
			return;
		}

		$point_rule  = Util::isJson( $active_campaigns->point_rule ) ? json_decode( $active_campaigns->point_rule ) : new \stdClass();
		$user_email  = is_object( $user ) && ! empty( $user->user_email ) ? $user->user_email : '';
		$campaign_id = ! empty( $active_campaigns->id ) ? $active_campaigns->id : 0;
		$action_type = ! empty( $active_campaigns->action_type ) && is_string( $active_campaigns->action_type ) ? $active_campaigns->action_type : '';
		switch ( $action_type ) {
			case 'followup_share':
				$active_campaigns->share_url     = ! empty( $point_rule->share_url ) ? $point_rule->share_url : '';
				$active_campaigns->button_text   = __( 'Follow', 'wll-loyalty-launcher' );
				$active_campaigns->is_achieved   = ! empty( $user_email ) && $campaign_id > 0 && self::isCampaignAchieved( $user_email, $campaign_id );
				$active_campaigns->achieved_text = $active_campaigns->is_achieved ? __( 'Earned', 'wll-loyalty-launcher' ) : "";
				break;
			case 'birthday':
				$active_campaigns->is_allow_edit           = self::canShowBirthdateField();
				$active_campaigns->birth_date              = ! empty( $user->birthday_date ) && $user->birthday_date != '0000-00-00' ? Util::convertDateFormat( $user->birthday_date, "Y-m-d" ) : ( ! empty( $user->birth_date ) ? Util::beforeDisplayDate( $user->birth_date, "Y-m-d" ) : '' );
				$active_campaigns->display_birth_date      = ! empty( $user->birthday_date ) && $user->birthday_date != '0000-00-00' ? Util::convertDateFormat( $user->birthday_date ) : ( ! empty( $user->birth_date ) ? Util::beforeDisplayDate( $user->birth_date ) : '' );
				$active_campaigns->user_can_edit_birthdate = ( isset( $user->id ) && $user->id > 0 );
				$active_campaigns->show_edit_birthday      = apply_filters( "wlr_allow_my_account_edit_birth_date", true, $active_campaigns->user_can_edit_birthdate, ! empty( $user ) ? $user : new \stdClass() );
				$active_campaigns->edit_text               = ! empty( $active_campaigns->birth_date ) ? __( 'Edit', 'wll-loyalty-launcher' ) : __( 'Set', 'wll-loyalty-launcher' );
				$active_campaigns->update_text             = __( 'Update', 'wll-loyalty-launcher' );
				if ( isset( $point_rule->birthday_earn_type ) && ! empty( $point_rule->birthday_earn_type ) && $point_rule->birthday_earn_type == "update_birth_date" ) {
					$active_campaigns->is_achieved   = ! empty( $user_email ) && $campaign_id > 0 && self::isCampaignAchieved( $user_email, $campaign_id );
					$active_campaigns->achieved_text = $active_campaigns->is_achieved ? __( 'Earned', 'wll-loyalty-launcher' ) : '';
				}
				break;
			case 'referral':
				$referral_url_check = ! empty( $user ) && is_object( $user ) && ! empty( $user->refer_code );
				if ( $referral_url_check ) {
					$active_campaigns->referral_url = self::getReferralUrl( $user->refer_code );
				}
				break;
			case 'facebook_share':
			case 'twitter_share':
			case 'whatsapp_share':
			case 'email_share':
				$referral_url_check = ! empty( $user ) && is_object( $user ) && ! empty( $user->refer_code );
				if ( $referral_url_check ) {
					$referral_url = self::getReferralUrl( $user->refer_code );
				}
				$social_actions = ( ! empty( $referral_url ) && ! empty( $user->user_email ) ) ? self::getSocialIconList( $user->user_email, $referral_url ) : [];
				foreach ( $social_actions as $social_action ) {
					if ( ! empty( $social_action['action_type'] ) && $social_action['action_type'] == $active_campaigns->action_type ) {
						$active_campaigns->action_url  = ! empty( $social_action['url'] ) ? $social_action['url'] : '';
						$active_campaigns->button_text = __( 'Share', 'wll-loyalty-launcher' );
					}
				}
				$active_campaigns->is_achieved   = ! empty( $user_email ) && $campaign_id > 0 && self::isCampaignAchieved( $user_email, $campaign_id );
				$active_campaigns->achieved_text = $active_campaigns->is_achieved ? __( 'Earned', 'wll-loyalty-launcher' ) : '';
				break;
			case 'signup':
				$is_member_user = ( isset( $user->id ) && $user->id > 0 );
				if ( ! $is_member_user ) {
					$active_campaigns->action_url  = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) );
					$active_campaigns->button_text = __( 'Sign Up', 'wll-loyalty-launcher' );
				}
				$active_campaigns->is_achieved   = ! empty( $user_email ) && $campaign_id > 0 && self::isCampaignAchieved( $user_email, $campaign_id );
				$active_campaigns->achieved_text = $active_campaigns->is_achieved ? __( 'Earned', 'wll-loyalty-launcher' ) : '';
				break;
			default;
		}
	}

	/**
	 * Check if a user has achieved a specific campaign.
	 *
	 * @param string $user_email The email of the user to check.
	 * @param int $campaign_id The ID of the campaign to check.
	 *
	 * @return bool Returns true if the user has achieved the campaign, false otherwise.
	 */
	public static function isCampaignAchieved( $user_email, $campaign_id ) {
		if ( empty( $user_email ) || ! is_string( $user_email ) || empty( $campaign_id ) || $campaign_id <= 0 ) {
			return false;
		}
		$campaign_transaction_model = new EarnCampaignTransactions();
		global $wpdb;
		$where  = $wpdb->prepare( 'user_email = %s AND campaign_id = %s', [ $user_email, $campaign_id ] );
		$result = $campaign_transaction_model->getWhere( $where, 'COUNT(*) as count', true );

		return ! empty( $result ) && isset( $result->count ) && ( $result->count > 0 );
	}

	/**
	 * Determines if the birthdate field should be shown based on settings and user data.
	 *
	 * @return bool True if the birthdate field should be shown, false otherwise.
	 */
	public static function canShowBirthdateField() {
		$is_one_time_birthdate_edit = Settings::get( 'is_one_time_birthdate_edit', 'wlr_settings', 'no' );
		$show_birthdate             = true;
		if ( $is_one_time_birthdate_edit == 'no' ) {
			$user_email    = WC::getLoginUserEmail();
			$user          = Loyalty::getLoyaltyUserByEmail( $user_email );
			$birthday_date = is_object( $user ) && ! empty( $user->birthday_date ) && $user->birthday_date != '0000-00-00' ? $user->birthday_date : ( is_object( $user ) && ! empty( $user->birth_date ) ? Util::beforeDisplayDate( $user->birth_date, 'Y-m-d' ) : '' );
			if ( ! empty( $birthday_date ) && $birthday_date != '0000-00-00' ) {
				$show_birthdate = false;
			}
		}

		return $show_birthdate;
	}

	/**
	 * Get the referral URL for a given referral code or current user.
	 *
	 * @param string $code The referral code to generate the URL for. If empty, the current user's referral code will be used.
	 *
	 * @return string The referral URL based on the provided code or current user's referral code.
	 */
	public static function getReferralUrl( $code = '' ) {
		if ( empty( $code ) ) {
			$user_email = WC::getLoginUserEmail();
			$user       = self::getLoyaltyUserByEmail( $user_email );
			$code       = ! empty( $user ) && ! empty( $user->refer_code ) ? $user->refer_code : '';
		}
		$url = '';
		if ( ! empty( $code ) ) {
			$url = site_url() . '?wlr_ref=' . $code;
		}

		return apply_filters( 'wlr_get_referral_url', $url, $code );
	}

	/**
	 * Retrieves the social icon list based on the provided user email and URL.
	 *
	 * @param string $user_email The email of the user.
	 * @param string $url The URL for which the social icons are retrieved.
	 *
	 * @return array Returns an array of social icons with additional data merged from the social share list.
	 */
	public static function getSocialIconList( $user_email, $url ) {
		if ( empty( $user_email ) || empty( $url ) ) {
			return [];
		}
		$customer_page      = new \Wlr\App\Controllers\Site\CustomerPage();
		$social_share_lists = $customer_page->getSocialShareList( $user_email, $url );
		$social_icon_list   = [];
		foreach ( $social_share_lists as $key => &$social_share_list ) {
			$social_icon_list[] = array_merge( $social_share_list, [ 'action_type' => $key ] );
		}

		return $social_icon_list;
	}

	/**
	 * Retrieves a list of dummy campaigns.
	 *
	 * @return array Returns an array of dummy campaign information including ID, icon, title, and description.
	 */
	public static function getDummyCampaigns() {
		return [
			[
				'id'          => 1,
				'icon'        => 'point_for_purchase',
				'title'       => __( 'Point for purchase', 'wll-loyalty-launcher' ),
				'description' => __( '+10 Points for every $1 spent', 'wll-loyalty-launcher' )
			],
			[
				'id'          => 2,
				'icon'        => 'birthday',
				'title'       => __( 'Celebrate a birthday', 'wll-loyalty-launcher' ),
				'description' => __( '+30 points', 'wll-loyalty-launcher' )
			],
			[
				'id'          => 3,
				'icon'        => 'twitter_share',
				'title'       => __( 'Twitter Share', 'wll-loyalty-launcher' ),
				'description' => __( '+70 points', 'wll-loyalty-launcher' )
			],
			[
				'id'          => 4,
				'icon'        => 'facebook_share',
				'title'       => __( 'Follow on Facebook', 'wll-loyalty-launcher' ),
				'description' => __( '+50 points', 'wll-loyalty-launcher' )
			],
			[
				'id'          => 5,
				'icon'        => 'product_review',
				'title'       => __( 'Review a Product', 'wll-loyalty-launcher' ),
				'description' => __( '+800 points', 'wll-loyalty-launcher' )
			],
		];
	}

	/**
	 * Retrieves the text representation of the user reward based on the provided user reward object.
	 *
	 * @param object $user_reward The user reward object containing information about the reward.
	 *
	 * @return string The text representation of the user reward based on its type and values.
	 */
	public static function getUserRewardText( $user_reward ) {
		if ( empty( $user_reward ) || ! is_object( $user_reward ) ) {
			return '';
		}
		$text          = '';
		$discount_type = ! empty( $user_reward->discount_type ) ? $user_reward->discount_type : '';
		switch ( $discount_type ) {
			case 'fixed_cart':
			case 'points_conversion':
				if ( $user_reward->coupon_type == 'percent' ) {
					// translators: First %s will replace discount value followed by %, Second %d will replace required point, Third %s will replace point label, Fourth %s will replace discount value followed by %
					$text = ( $user_reward->reward_type == 'redeem_coupon' ) ? sprintf( __( '%1$s Off', 'wll-loyalty-launcher' ), $user_reward->discount_value . '%' ) : sprintf( __( '%2$d %3$s = %4$s Off', 'wll-loyalty-launcher' ), $user_reward->require_point, self::getPointLabel( $user_reward->require_point ), $user_reward->discount_value . '%' );
				} else {
					$discount_value = WC::getCustomPrice( $user_reward->discount_value );
					// translators: %s will replace discount value
					$text = ( $user_reward->reward_type == 'redeem_coupon' ) ? sprintf( __( '%s Off', 'wll-loyalty-launcher' ), $discount_value )
						// translators: First %d will replace required point, Second %s will replace point label, Third %s will replace discount value
						: sprintf( __( '%1$d %2$s = %3$s Off', 'wll-loyalty-launcher' ), $user_reward->require_point, self::getPointLabel( $user_reward->require_point ), $discount_value );
				}

				break;
			case 'percent':
				// translators: First %d will replace discount value, Second %s will replace % symbol
				$text = ( $user_reward->reward_type == 'redeem_coupon' ) ? sprintf( __( '%1$d%2$s Off', 'wll-loyalty-launcher' ), $user_reward->discount_value, '%' )
					// translators: First %d will replace required point, Second %s will replace point label, Third %d will replace discount value, Fourth %s will replace % symbol
					: sprintf( __( '%1$d %2$s = %3$d%4$s Off', 'wll-loyalty-launcher' ), $user_reward->require_point, self::getPointLabel( $user_reward->require_point ), $user_reward->discount_value, '%' );
				break;
			case 'free_product':
				//phpcs:ignore
				$text = ( $user_reward->reward_type == 'redeem_coupon' ) ? __( 'Free product', 'wll-loyalty-launcher' ) : __( $user_reward->require_point . ' ' . self::getPointLabel( $user_reward->require_point ), 'wll-loyalty-launcher' );
				break;
			case 'free_shipping':
				//phpcs:ignore
				$text = ( $user_reward->reward_type == 'redeem_coupon' ) ? __( 'Free shipping', 'wll-loyalty-launcher' ) : __( $user_reward->require_point . ' ' . self::getPointLabel( $user_reward->require_point ), 'wll-loyalty-launcher' );
				break;
		}

		return $text;
	}

	/**
	 * Retrieves the label for a given point value.
	 *
	 * @param int $point The point value to retrieve the label for.
	 * @param bool $label_translate Whether to translate the label. Default is true.
	 *
	 * @return string The label for the specified point value.
	 */
	public static function getPointLabel( $point, $label_translate = true ) {
		$settings = get_option( 'wlr_settings', [] );
		$singular = ! empty( $settings['wlr_point_singular_label'] ) ? $settings['wlr_point_singular_label'] : 'point';
		if ( $label_translate ) {
			//phpcs:ignore
			$singular = __( $singular, 'wll-loyalty-launcher' );
		}
		$plural = ! empty( $settings['wlr_point_label'] ) ? $settings['wlr_point_label'] : 'points';
		if ( $label_translate ) {
			//phpcs:ignore
			$plural = __( $plural, 'wll-loyalty-launcher' );
		}
		$point_label = ( $point == 0 || $point > 1 ) ? $plural : $singular;

		return apply_filters( 'wlr_get_point_label', $point_label, $point );
	}

	/**
	 * Retrieves the coupon text based on the user's reward object.
	 *
	 * @param object $user_reward The reward object containing information about the coupon.
	 *
	 * @return string Returns the formatted coupon text based on the discount value and type in the user's reward object.
	 */
	public static function getUserCouponText( $user_reward ) {
		if ( empty( $user_reward ) || ! is_object( $user_reward ) ) {
			return '';
		}
		$text           = '';
		$discount_value = ! empty( $user_reward->discount_value ) && ( $user_reward->discount_value != 0 ) ? ( $user_reward->discount_value ) : '';
		$discount_type  = ! empty( $user_reward->discount_type ) ? $user_reward->discount_type : '';
		switch ( $discount_type ) {
			case 'fixed_cart':
			case 'points_conversion':
				$text = WC::convertPrice( $discount_value, true, $user_reward->reward_currency );
				if ( $user_reward->coupon_type == 'percent' ) {
					$text = number_format( $discount_value, 2 ) . '%';
				}
				break;
			case 'percent':
				$text = round( $discount_value ) . '%';
				break;
			case 'free_product':
				$text = __( 'Free product', 'wll-loyalty-launcher' );
				break;
			case 'free_shipping':
				$text = __( 'Free shipping', 'wll-loyalty-launcher' );
				break;
		}

		return $text;
	}

	/**
	 * Retrieve a list of dummy rewards for display.
	 *
	 * This method returns an array of dummy reward items with the following structure:
	 * - 'id': The ID of the reward item.
	 * - 'icon': The icon representing the reward.
	 * - 'title': The title of the reward.
	 * - 'description': The description of the reward.
	 * - 'action_text': The action text related to the reward.
	 *
	 * @return array An array of dummy reward items.
	 */
	public static function getDummyRewardList() {
		return [
			[
				'id'          => 1,
				'icon'        => 'points_conversion',
				'title'       => __( 'Point conversion', 'wll-loyalty-launcher' ),
				'description' => __( 'Covert point into coupons', 'wll-loyalty-launcher' ),
				'action_text' => __( '10 points = $15.00 Off', 'wll-loyalty-launcher' ),
			],
			[
				'id'          => 2,
				'icon'        => 'percent',
				'title'       => __( 'Percentage discount', 'wll-loyalty-launcher' ),
				'description' => __( 'Redeem points and get percentage discount', 'wll-loyalty-launcher' ),
				'action_text' => __( '100 points = 10% Off', 'wll-loyalty-launcher' ),
			],
			[
				'id'          => 3,
				'icon'        => 'free_shipping',
				'title'       => __( 'Free shipping', 'wll-loyalty-launcher' ),
				'description' => __( 'Redeem points and get free shipping', 'wll-loyalty-launcher' ),
				'action_text' => __( 'free shipping', 'wll-loyalty-launcher' ),
			],
			[
				'id'          => 4,
				'icon'        => 'fixed_cart',
				'title'       => __( 'Fixed discount', 'wll-loyalty-launcher' ),
				'description' => __( 'Redeem points and get fixed discount', 'wll-loyalty-launcher' ),
				'action_text' => __( '100 points = $10.00 Off', 'wll-loyalty-launcher' ),
			],
		];
	}

	/**
	 * Retrieve a list of dummy coupon data for display.
	 *
	 * This method returns an array of dummy coupon items with the following structure:
	 * - 'id': The ID of the coupon item.
	 * - 'icon': The icon representing the coupon.
	 * - 'title': The title of the coupon.
	 * - 'description': The description of the coupon.
	 * - 'expired_text': Text indicating the expiration date of the coupon (if applicable).
	 * - 'coupon_code': The unique coupon code.
	 * - 'action_text': The action text related to the coupon.
	 *
	 * @return array An array of dummy coupon items.
	 */
	public static function getDummyCouponData() {
		return [
			[
				'id'           => 1,
				'icon'         => 'fixed_cart',
				'title'        => __( 'Fixed discount', 'wll-loyalty-launcher' ),
				'description'  => __( 'Fixed discount description', 'wll-loyalty-launcher' ),
				'expired_text' => __( 'Expires on 2024-04-12', 'wll-loyalty-launcher' ),
				'coupon_code'  => "wlr-cd6-jrt-eaz",
				'action_text'  => __( '$10.00', 'wll-loyalty-launcher' )
			],
			[
				'id'           => 2,
				'icon'         => 'percent',
				'title'        => __( 'Percentage discount', 'wll-loyalty-launcher' ),
				'description'  => __( 'Percentage discount description', 'wll-loyalty-launcher' ),
				'expired_text' => __( 'Expires on 2023-05-12', 'wll-loyalty-launcher' ),
				'coupon_code'  => "wlr-wrs-m5a-y5q",
				'action_text'  => __( '10%', 'wll-loyalty-launcher' )
			],
			[
				'id'           => 3,
				'icon'         => 'free_shipping',
				'title'        => __( 'Free Shipping', 'wll-loyalty-launcher' ),
				'description'  => __( 'Free shipping description', 'wll-loyalty-launcher' ),
				'expired_text' => "",
				'coupon_code'  => "wlr-zhn-4z6-efz",
				'action_text'  => __( 'Free shipping', 'wll-loyalty-launcher' )
			],

		];
	}

	/**
	 * Retrieve reward opportunities for the user.
	 *
	 * This method retrieves a list of reward opportunities based on the user's status:
	 * - If the user is logged in, it gets the rewards from the customer page.
	 * - If the user is a guest, it returns an empty array.
	 * - If no rewards are found or the rewards array is empty, a message is displayed.
	 * - Each reward's name and description are translated using the text domain 'wll-loyalty-launcher'.
	 *
	 * @return array An array containing:
	 *               - 'reward_opportunity': The list of reward opportunities.
	 *               - 'message': A message indicating the availability of rewards.
	 */
	public static function getRewardOpportunities() {
		$user_email    = WC::getLoginUserEmail();
		$is_guest      = empty( $user_email );
		$customer_page = new \Wlr\App\Controllers\Site\CustomerPage();
		$rewards       = $customer_page->getRewardList();
		if ( empty( $rewards ) || ! is_array( $rewards ) ) {
			return [
				'reward_opportunity' => [],
				// translators: %s will be replaced with the reward label
				'message'            => sprintf( __( 'No %s found!', 'wll-loyalty-launcher' ), self::getRewardLabel( 3 ) )
			];
		}
		foreach ( $rewards as $reward ) {
			//phpcs:ignore
			$reward->name = ! empty( $reward->name ) ? __( $reward->name, 'wll-loyalty-launcher' ) : '';
			//phpcs:ignore
			$reward->description = ! empty( $reward->description ) ? __( $reward->description, 'wll-loyalty-launcher' ) : '';
		}
		$message = "";
		if ( count( $rewards ) == 0 ) {
			// translators: %s will be replaced with the reward label
			$message = sprintf( __( 'No %s found!', 'wll-loyalty-launcher' ), self::getRewardLabel( 3 ) );
		}

		return apply_filters( 'wll_before_launcher_reward_opportunities', [
			'reward_opportunity' => $rewards,
			'message'            => $message
		], $user_email, $is_guest );
	}

	/**
	 * Retrieve the label for rewards based on the reward count.
	 *
	 * Retrieves the singular or plural label for rewards based on the 'reward_singular_label'
	 * and 'reward_plural_label' settings in the 'wlr_settings' option. If these settings are
	 * not defined, default labels are used.
	 *
	 * @param int $reward_count The count of rewards for which the label is being determined.
	 *
	 * @return string The label for the rewards based on the reward count.
	 */
	public static function getRewardLabel( $reward_count = 0 ) {
		$setting_option = get_option( 'wlr_settings', [] );
		//phpcs:ignore
		$singular = ( ! empty( $setting_option['reward_singular_label'] ) ) ? __( $setting_option['reward_singular_label'], 'wll-loyalty-launcher' ) : __( 'reward', 'wll-loyalty-launcher' );
		//phpcs:ignore
		$plural       = ( ! empty( $setting_option['reward_plural_label'] ) ) ? __( $setting_option['reward_plural_label'], 'wll-loyalty-launcher' ) : __( 'rewards', 'wll-loyalty-launcher' );
		$reward_label = ( $reward_count == 0 || $reward_count > 1 ) ? $plural : $singular;

		return apply_filters( 'wlr_get_reward_label', $reward_label, $reward_count );
	}

	/**
	 * Retrieve a list of dummy reward opportunities for display.
	 *
	 * This method returns an array of dummy reward opportunity items with the following structure:
	 * - 'id': The ID of the reward opportunity.
	 * - 'icon': The icon representing the reward opportunity.
	 * - 'title': The title of the reward opportunity.
	 * - 'description': The description of the reward opportunity.
	 * - 'action_text': The action text related to the reward opportunity.
	 *
	 * @return array An array of dummy reward opportunity items.
	 */
	public static function getDummyRewardOpportunities() {
		return [
			[
				'id'          => 1,
				'icon'        => 'fixed_cart',
				'title'       => __( 'Fixed coupon discount', 'wll-loyalty-launcher' ),
				'description' => __( 'Coupon reward', 'wll-loyalty-launcher' ),
				'action_text' => '',
			],
			[
				'id'          => 2,
				'icon'        => 'percent',
				'title'       => __( 'Percentage discount', 'wll-loyalty-launcher' ),
				'description' => __( 'Percentage discount', 'wll-loyalty-launcher' ),
				'action_text' => '',
			],
			[
				'id'          => 3,
				'icon'        => 'free_product',
				'title'       => __( 'Free product', 'wll-loyalty-launcher' ),
				'description' => __( 'Free product', 'wll-loyalty-launcher' ),
				'action_text' => '',
			],
			[
				'id'          => 4,
				'icon'        => 'points_conversion',
				'title'       => __( 'Points conversion', 'wll-loyalty-launcher' ),
				'description' => __( 'Points conversion', 'wll-loyalty-launcher' ),
				'action_text' => '',
			],
		];
	}
}